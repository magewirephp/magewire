# TL;DR

Make Magewire's namespaced `app()` helper behave like Laravel's helper at the
service-resolution boundary while keeping Magento's object manager, DI
configuration, and existing Magewire container aliases as the source of truth.

The current helper resolves concrete classes and the `livewire` / `redirect`
aliases, but it drops every supplied constructor argument, cannot resolve
interfaces or virtual types, returns an object without the Laravel-style
container methods retained in ported code, and contains an unsupported object
branch.

The recommended implementation is a small Magento-backed application-container
adapter. It should not import Laravel's container or attempt to emulate the
entire Laravel Foundation application.

# Context

- Started: 2026-08-13
- Initial type: Feature
- Current type: Feature
- Status: In review; cross-version and adminhtml matrix coverage remains
- Branch: `feat/app-helper-parity`
- Remote branch: `origin/feat/app-helper-parity`
- Pull request: [#274](https://github.com/magewirephp/magewire/pull/274)
- Laravel compatibility range: 10.x through 13.x
- Ported Livewire baseline: 3.7.11

# Goal

Allow Magewire consumers and retained Livewire code to use both Laravel helper
forms:

```php
app();
app(Service::class, ['constructorParameter' => $value]);
```

Resolution must use Magento preferences, virtual types, configured constructor
arguments, shared instances, and Magewire's named container aliases without
adding Laravel's service container as a runtime dependency.

# Compatibility target

| Usage | Target behavior |
| --- | --- |
| `app()` | Return Magewire's Magento-backed application-container adapter |
| `app(Foo::class)` | Preserve current behavior by resolving through Magento `get()` |
| `app(Foo::class, $parameters)` | Resolve a fresh object through Magento `create()` and forward the named constructor parameters |
| `app(FooInterface::class)` | Resolve the Magento `di.xml` preference |
| `app('configuredVirtualType')` | Resolve the Magento virtual type |
| `app('livewire')` / `app('redirect')` | Preserve the existing area-scoped Magewire container aliases |
| `app()->make(...)` / `makeWith(...)` / `get(...)` | Use the same resolution pipeline as direct `app(...)` calls |
| `app()->has(...)` / `bound(...)` | Inspect runtime overrides, Magewire aliases, and Magento DI metadata without instantiating the service |
| `app()->singleton(...)` / `instance(...)` | Provide request-scoped compatibility needed by retained Livewire internals |
| Unsupported abstract | Throw one stable, contextual resolution exception rather than leaking an alias lookup failure |

# Tasks

- [x] Trace the current helper and its repository call sites
- [x] Compare Laravel 10.x, 11.x, 12.x, and 13.x helper behavior
- [x] Trace Laravel's parameterized resolution and shared-instance behavior
- [x] Trace Magento `get()`, `create()`, preferences, virtual types, and constructor argument overrides
- [x] Identify current Magewire aliases and retained container method calls
- [x] Confirm the adapter return-type compatibility decision
- [x] Define the exact public adapter contract and exception types
- [x] Add a boot-safe lookup API to `Containers` for `has`, shared resolution, and parameterized alias construction
- [x] Implement the Magento-backed application-container adapter in hand-written `lib/Magewire/` code
- [x] Reduce `app()` to Laravel's two-branch shape: return the adapter for `null`, otherwise call `make($abstract, $arguments)`
- [x] Preserve Magento ObjectManager compatibility methods that existing `app()` consumers may use (`get`, `create`, and `configure`)
- [x] Add focused unit tests for routing, runtime overrides, identity, and failure behavior
- [x] Add a separate GitHub Actions check that runs the focused unit tests on PHP 8.2
- [x] Run a Magento-booting integration probe for preferences, virtual types, frontend aliases, and call-time constructor parameters
- [ ] Exercise the feature against Illuminate 10 and 13 CI endpoints and the supported Magento/Mage-OS matrix
- [ ] Document the supported Laravel-style surface and the intentionally unsupported Laravel Application APIs
- [x] Run Portman and verify that no generated `dist/` changes are required for this hand-written bridge
- [x] Review backwards compatibility of the implementation before handoff
- [ ] Add release notes before implementation is merged

# Implementation plan

## Phase 1 — Lock the contract with tests

Add contract tests before changing the helper. Cover the complete compatibility
table above, including object identity and failure behavior.

Use small fixture types with:

- an injectable object dependency;
- a required scalar constructor parameter;
- an interface plus Magento preference;
- a virtual type;
- shared and transient resolution expectations;
- a runtime singleton and an already-created runtime instance.

The Magento-booting test is required. A mocked ObjectManager can prove routing,
but only a booted Magento container can prove that preferences, virtual types,
compiled/developer factories, and area-scoped aliases behave the same way.

## Phase 2 — Make `Containers` a safe named-binding registry

Keep `Containers` as the owner of Magewire aliases registered in
`etc/frontend/di.xml` and `etc/adminhtml/di.xml`. Add explicit APIs instead of
using exceptions as lookup control flow:

- `has(string $name): bool`;
- shared lookup for the current `item()` behavior;
- parameterized construction that retains the configured Magento type and calls
  Magento creation with the supplied parameters.

Lookup must be safe before the normal service-type boot has assembled the
registry. Existing aliases must retain precedence over Magento fallback names,
except that real class and interface identifiers keep their current direct
resolution behavior.

## Phase 3 — Add the Magento-backed adapter

Create a hand-written `ApplicationContainer` (final name to be confirmed) under
`lib/Magewire/`. Inject `ObjectManagerInterface`, Magento's ObjectManager
`ConfigInterface`, and Magewire `Containers`.

Recommended resolution precedence:

1. request-scoped instance or singleton registered on the adapter;
2. concrete class or interface resolved through Magento;
3. Magewire named alias resolved through `Containers`;
4. Magento fallback for virtual types and other configured identifiers;
5. a stable Magewire binding-resolution exception.

When parameters are empty, preserve the existing `ObjectManager::get()` path.
When parameters are present, use `ObjectManager::create($abstract,
$parameters)`. This is the Magento equivalent of Laravel's contextual build:
call-time parameters create a fresh object and must not replace the shared
instance.

The first public surface should be intentionally bounded:

- resolution: `make`, `makeWith`, `get`, `create`;
- inspection: `has`, `bound`;
- runtime compatibility required by retained Livewire code: `singleton`,
  `instance`;
- legacy Magento delegation: `configure`;
- optional ArrayAccess only if it can be covered without widening the design.

Implement `Psr\Container\ContainerInterface` if exceptions can honor its
contract. Consider also implementing Magento's `ObjectManagerInterface` to
reduce the return-type break from the current `app()` behavior. Do not claim
`Illuminate\Contracts\Container\Container` compatibility unless every method
in that interface is genuinely implemented across Illuminate 10–13.

## Phase 4 — Simplify the helper

Mirror Laravel's stable helper control flow:

```php
if ($abstract === null) {
    return $container;
}

return $container->make($abstract, $arguments);
```

Keep the existing `$arguments` parameter name for backwards compatibility with
PHP named arguments unless repository or usage evidence justifies a breaking
rename to Laravel's `$parameters`. Update PHPDoc to use
`string|class-string|null` and a conditional generic return type.

Remove the object branch. Laravel documents a string/class-string/null abstract,
and Magento ObjectManager `get()` accepts a type identifier, not an object.

## Phase 5 — Regression and compatibility proof

Explicitly prove the retained generated calls:

- `app()->has('session.store')` returns `false` without a fatal method call;
- `app()->singleton(EventBus::class)` registers a request-scoped shared binding;
- `app()->instance(Mechanism::class, $mechanism)` returns that exact object on
  later resolution;
- `app('livewire')` and `app('redirect')` still return the existing configured
  container instances;
- `app(ResponseFactory::class)` reaches Magento preference resolution rather
  than treating the interface name as a Magewire alias.

Run formatting/static analysis, the focused PHP tests, the Magento integration
test, and the existing Playwright suite. Run `portman build` as a verification
step only; changes belong in `lib/`, not generated `dist/`.

# Decisions

## ✅ Magento remains the source of truth

Static bindings, interface preferences, virtual types, constructor defaults,
and object lifestyles remain configured by Magento DI. The adapter must not
embed a second general-purpose dependency injection container.

**Reasoning**

Magewire runs inside Magento, and the current code already documents `di.xml`
as the binding mechanism. Duplicating those bindings in a Laravel container
would create divergent object graphs and lifecycle rules.

**Evidence**

- `src/etc/frontend/di.xml:454`
- `src/etc/adminhtml/di.xml:409`
- `lib/magewire-helpers.php:247`

## ✅ Preserve ordinary no-parameter resolution

Continue using Magento `get()` when no call-time parameters are supplied.

**Reasoning**

This retains the helper's current shared-instance behavior, as requested, and
lets Magento preferences resolve before the adapter considers Magewire aliases.

**Evidence**

- `lib/magewire-helpers.php:249`
- Magento `ObjectManager.php:65`

## ✅ Parameters mean contextual fresh construction

Any non-empty parameter array routes through Magento `create()` and is passed
unchanged by constructor parameter name.

**Reasoning**

Laravel treats explicit parameters as a contextual build and bypasses a cached
singleton for that resolution. Magento's `create()` is the only public API that
both accepts call-time parameters and returns a fresh object.

**Evidence**

- Magento `ObjectManager.php:54`
- Magento `Factory/Dynamic/Developer.php:22`
- [Laravel 13 container resolution](https://github.com/laravel/framework/blob/13.x/src/Illuminate/Container/Container.php)

## ✅ Return an adapter from `app()`

The implementation replaces the raw ObjectManager return value with a
Magento-backed adapter and delegates ObjectManager-compatible methods through
it.

**Reasoning**

This is the only approach that matches Laravel's `app()` shape and supports the
`has`, `singleton`, and `instance` calls already retained in generated Magewire
code. The compatibility audit must confirm whether consumers depend on the
concrete `Magento\Framework\App\ObjectManager` return type.

**Evidence**

- `dist/EventBus.php:17`
- `dist/Mechanisms/Mechanism.php:14`
- `dist/Features/SupportRedirects/SupportRedirects.php:23`
- `dist/Mechanisms/FrontendAssets/FrontendAssets.php:148`

## ❌ Rejected: add `illuminate/container`

Do not add Laravel's container as a second runtime container.

**Reasoning**

It would not know Magento preferences, virtual types, generated factories, or
area-scoped DI without duplicating configuration and would create ambiguous
ownership of shared instances.

## ❌ Rejected: expose runtime `bind()`

Application bindings remain in Magento `di.xml`. A runtime `bind()` would only
affect later resolutions through Magewire's `app()` adapter and not services
injected directly by Magento, creating two inconsistent dependency graphs.

The adapter keeps only the runtime `singleton()` and `instance()` methods
required by retained Livewire internals.

## ❌ Rejected: change only `get()` to `create()`

Using Magento `create()` for every class would consume `$arguments`, but it
would silently change every existing no-argument class lookup from shared to
fresh and would leave interfaces, virtual types, aliases, and `app()` container
methods unresolved.

## ✅ Run focused unit tests as a separate pull-request check

The PHPUnit-compatible tests under `tests/Unit` run in a dedicated GitHub
Actions workflow on PHP 8.2. The workflow installs Magewire's dependencies from
the public Mage-OS mirror without development packages and provisions a pinned
PHPUnit 11 CLI without adding a repository-level test-runner dependency.

**Reasoning**

This makes the 14 tests visible as an independent merge check while preserving
the package's PHP 8.2 floor. Selecting and wiring the repository's eventual Pest
runner remains separate work.

**Evidence**

- `.github/workflows/unit-tests.yml`
- `tests/Unit/ApplicationContainerTest.php`
- `tests/Unit/ContainersTest.php`

# Acceptance criteria

- The same two helper branches used by Laravel 10–13 are visible in Magewire:
  return the container for `null`, otherwise delegate to `make` with parameters.
- Existing no-parameter class calls and `livewire` / `redirect` aliases retain
  their current identity behavior.
- Call-time constructor parameters reach Magento by their named keys and produce
  a fresh contextual object without replacing the normal shared object.
- Magento class, interface preference, and virtual-type identifiers resolve.
- The retained `has`, `singleton`, and `instance` call sites execute without
  fatal errors and have covered request-scoped behavior.
- Unknown identifiers fail consistently with the requested identifier in the
  exception chain.
- No Laravel container package or duplicate static binding registry is added.
- The implementation is hand-written under `lib/`; `dist/` remains generated
  and unchanged unless Portman independently proves otherwise.
- Behavior is tested at Illuminate 10 and 13 endpoints and in both frontend and
  adminhtml Magento areas.
- The focused unit suite runs as a separate GitHub Actions pull-request check.

# Investigation

## VERIFIED

- Laravel 10, 11, 12, and 13 use the same `app()` control flow and forward the
  second parameter directly to container `make()`.
- Laravel treats explicit parameters as a contextual build and does not reuse or
  replace the cached singleton for that build.
- Magento `get()` returns a cached shared instance and accepts no arguments;
  Magento `create()` accepts named arguments and creates a new object.
- Magewire's helper never reads `$arguments`.
- The current `class_exists()` gate excludes interfaces and Magento virtual
  types before the ObjectManager can resolve them.
- The current object branch passes an object into an API documented for a type
  string.
- Current area DI exposes only the `livewire` and `redirect` named Magewire
  aliases.
- Retained generated code calls `app()->has()`, `app()->singleton()`, and
  `app()->instance()`, none of which exists on the raw ObjectManager returned by
  the helper.

## ASSUMED

- External Magewire consumers may depend on `app()->get()`, `create()`, or
  `configure()` because `app()` currently returns the concrete ObjectManager.
  The adapter plan preserves these methods, but repository source cannot prove
  external usage.
- Keeping `$arguments` as the PHP parameter name is safer than renaming it to
  Laravel's `$parameters`, because PHP named arguments make the current name
  externally observable.

## UNKNOWN

- Whether any external consumer requires `app()` to be an `instanceof
  Magento\Framework\App\ObjectManager` rather than only using its public
  methods.
- Whether ArrayAccess belongs in a future compatibility surface. It is
  intentionally not part of this implementation.
- Whether parameterized construction of a named Magewire alias is used outside
  tests; it should still be designed correctly so the helper has one coherent
  contract.
- The repository-level test runner remains separate work in
  `.agents/plans/pest-test-runner.md`. Pull request #274 uses a pinned PHPUnit
  CLI supplied by CI so its focused tests can run without deciding that wider
  migration.

# Tooling

- `magewire-work`
- Magewire, architecture, backwards-compatibility, best-practices, and Portman
  skill guidance
- Repository source and Git history
- Laravel 10–13 framework source and Laravel 13 service-container documentation
- Magento framework source and Adobe Commerce ObjectManager / DI documentation

# Supporting documents

- [Research and compatibility analysis](./investigation.md)

# Change log

- 2026-08-13: Work item created; Laravel 10–13 and Magento resolution behavior
  verified; adapter-based implementation plan proposed.
- 2026-08-13: Implemented `ApplicationContainer`, PSR-compatible not-found
  failures, safe Magewire alias inspection, contextual constructor parameters,
  runtime overrides, and ObjectManager method delegation. Added 14 focused
  PHPUnit tests, verified the frontend Magento DI path, and confirmed Portman
  leaves `dist/` unchanged. Illuminate/Magento matrix CI and adminhtml-area
  coverage remain follow-up work.
- 2026-08-13: Removed public runtime `bind()` compatibility. Magento `di.xml`
  remains the only application binding source; the adapter retains the
  `singleton()` and `instance()` overlays required by ported Livewire code.
- 2026-08-13: Created `feat/app-helper-parity` from `origin/main` and pushed it
  to `origin` for review.
- 2026-08-13: Opened pull request
  [#274](https://github.com/magewirephp/magewire/pull/274).
- 2026-08-17: Added a separate PHP 8.2 / PHPUnit 11 GitHub Actions check for
  the 14 focused unit tests while leaving the repository-level Pest runner as
  separate work.
