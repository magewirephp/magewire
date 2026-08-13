# Research and compatibility analysis

# Question

What behavior is missing from Magewire's `app()` helper, and what is the
narrowest Magento-native design that lets callers use it in the Laravel style
without introducing Laravel's application container?

# Upstream Laravel contract

Laravel 10.x, 11.x, 12.x, and 13.x all implement the helper with the same two
branches:

1. `app()` returns the global container instance.
2. `app($abstract, $parameters)` calls the container's
   `make($abstract, $parameters)`.

Official source:

- [Laravel 10.x helper](https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/helpers.php)
- [Laravel 11.x helper](https://github.com/laravel/framework/blob/11.x/src/Illuminate/Foundation/helpers.php)
- [Laravel 12.x helper](https://github.com/laravel/framework/blob/12.x/src/Illuminate/Foundation/helpers.php)
- [Laravel 13.x helper](https://github.com/laravel/framework/blob/13.x/src/Illuminate/Foundation/helpers.php)

Laravel's supported abstract is documented as
`string|class-string<TClass>|null`; an object is not part of the helper
contract.

Laravel's container `make()` delegates to `resolve()`. Explicit parameters make
the resolution contextual. A contextual build does not return an existing
singleton and does not replace the singleton after construction. Laravel also
documents `makeWith()` for named constructor parameters, while `make()` accepts
the same parameter array internally.

Sources:

- [Laravel 13.x service-container documentation](https://laravel.com/docs/13.x/container#the-make-method)
- [Laravel 13.x Container source](https://github.com/laravel/framework/blob/13.x/src/Illuminate/Container/Container.php)

# Magento resolution contract

Magento exposes two relevant ObjectManager operations:

- `get($type)` resolves preferences and returns a cached shared instance;
- `create($type, $arguments)` resolves preferences and creates a new instance
  with call-time constructor arguments.

Magento's runtime and compiled factories merge call-time values by constructor
parameter name over configured DI arguments. Magento DI also owns interface
preferences, virtual types, and object lifestyle configuration.

Sources:

- [Magento ObjectManager implementation](https://github.com/magento/magento2/blob/2.4-develop/lib/internal/Magento/Framework/ObjectManager/ObjectManager.php)
- [Adobe ObjectManager documentation](https://developer.adobe.com/commerce/php/development/components/object-manager/)
- [Adobe DI configuration and object lifestyles](https://developer.adobe.com/commerce/php/development/build/dependency-injection-file/)
- Local `Magento/Framework/ObjectManager/Factory/Dynamic/Developer.php:22`
- Local `Magento/Framework/ObjectManager/ConfigInterface.php:35`

Direct ObjectManager usage is normally discouraged. A framework compatibility
helper is an explicit boundary where it is justified, but the implementation
should isolate it in an injectable adapter rather than spreading more static
ObjectManager calls.

# Current Magewire behavior

Current implementation: `lib/magewire-helpers.php:247`.

| Input | Current path | Problem |
| --- | --- | --- |
| `app()` | Return raw Magento ObjectManager | Does not expose Laravel container methods |
| Concrete class string | `ObjectManager::get()` | Works and returns a shared object |
| Concrete class plus arguments | `ObjectManager::get()` | Arguments are silently discarded |
| Interface string | Magewire `Containers::item()` | `class_exists()` is false, so Magento never sees the preference |
| Virtual type string | Magewire `Containers::item()` | Virtual types are not PHP classes, so Magento never sees them |
| `livewire` / `redirect` | Magewire `Containers::item()` | Works after the container service type is assembled |
| Object abstract | `ObjectManager::get($object)` | Unsupported: Magento expects a type identifier string |
| Unknown string | Magewire alias exception | Error describes an operation item rather than service resolution |

The `$arguments` parameter is not referenced anywhere in the function.

# Existing repository compatibility pressure

The generated Livewire-derived code retained by Magewire already assumes
Laravel container methods on the object returned by `app()`:

- `dist/EventBus.php:19` calls `singleton()`;
- `dist/Mechanisms/Mechanism.php:16` calls `instance()`;
- `dist/Features/SupportRedirects/SupportRedirects.php:28` calls `has()`;
- `dist/Mechanisms/FrontendAssets/FrontendAssets.php:148` calls `has()`.

The raw Magento ObjectManager only exposes `get`, `create`, and `configure`, so
these are latent fatal paths whenever executed.

The repository also has an interface-resolution example inside the same helper:
`response()` calls `app(ResponseFactory::class)` at
`lib/magewire-helpers.php:327`. `ResponseFactory` is an interface, so the current
`class_exists()` gate prevents Magento preference resolution.

# Existing named bindings

Magewire deliberately owns two Laravel-style string aliases in area-scoped DI:

- `livewire` → `Magewirephp\Magewire\Containers\Livewire`;
- `redirect` → `Magewirephp\Magewire\Containers\Redirect`.

Evidence:

- `src/etc/frontend/di.xml:465`
- `src/etc/adminhtml/di.xml:420`
- `lib/Magewire/ServiceType.php:68`

These aliases should remain data-driven through DI and should not be hard-coded
into the helper or adapter.

# Recommended architecture

## Helper

Keep the public helper tiny and stable. It should obtain one Magento-managed
`ApplicationContainer` adapter, return it for `null`, and otherwise delegate to
its `make()` method with the supplied arguments.

## Adapter

The adapter owns compatibility semantics, not object construction. It delegates
construction to Magento and stores only the request-scoped singleton and
instance overrides needed by retained Livewire code. General application
bindings remain exclusively in Magento `di.xml`.

Responsibilities:

- route class, interface, virtual type, and named alias resolution;
- choose `get()` for current no-parameter behavior;
- choose `create()` for contextual parameterized builds;
- expose common Laravel-style resolution/inspection/runtime-binding methods;
- delegate the old ObjectManager-compatible methods;
- normalize exceptions;
- remain a normal Magento shared service, giving its runtime registry a
  per-request lifecycle.

It should not:

- parse or duplicate `di.xml` construction logic;
- become a second static binding configuration system;
- implement Laravel routing, paths, environment, locale, console detection, or
  other `Illuminate\Foundation\Application` APIs;
- claim the full `Illuminate\Contracts\Container\Container` interface with only
  partial method coverage.

## Named alias registry

`Containers` remains responsible for aliases. It needs a non-throwing `has()`
API and a parameterized make path that remembers the configured Magento type.
This prevents the adapter from inspecting `ServiceType` internals or using
exceptions for normal resolution routing.

# Resolution and lifecycle details

## Empty parameters

Use Magento `get()`. This intentionally preserves existing Magewire behavior,
even though Laravel auto-wired concrete classes are transient unless explicitly
bound as singletons. Exact lifecycle identity between the two frameworks is not
possible without violating the request to keep Magento behavior.

## Non-empty parameters

Use Magento `create()` with the array unchanged. Like Laravel's contextual
build, the result is fresh and must not overwrite any shared instance or
runtime singleton.

## Runtime `instance` and `singleton`

These are an adapter overlay, not mutations of Magento's protected shared
instance store or global DI configuration.

- `instance($abstract, $object)` stores and returns the exact object for later
  no-parameter resolutions.
- `singleton($abstract, $concrete = null)` records a request-scoped shared
  binding and caches its first non-contextual resolution.
- Explicit parameters bypass the cached runtime singleton for that one
  contextual build.

## `has` versus `bound`

Laravel documents `bound()` as explicit binding inspection, while PSR `has()`
asks whether the container can return an identifier. Define both deliberately:

- `bound()` checks adapter runtime singletons and instances, Magewire named aliases, Magento
  preferences, and virtual types;
- `has()` additionally recognizes instantiable concrete classes, without
  constructing them;
- an unbound interface with no Magento preference returns `false`;
- arbitrary Laravel-only keys such as `session.store` return `false` instead of
  fatalling.

# Backwards compatibility

Changing `app()` from the concrete Magento ObjectManager to an adapter is the
main risk.

Mitigations:

- expose and delegate `get`, `create`, and `configure`;
- consider implementing Magento's `ObjectManagerInterface` as well as PSR
  ContainerInterface if method signatures remain compatible across supported
  Magento versions;
- search public documentation and repository history for concrete type checks;
- call out that concrete `instanceof Magento\Framework\App\ObjectManager`
  cannot be preserved by composition;
- keep `$arguments` as the PHP parameter name because named arguments make it
  part of the effective public API.

# Alternatives considered

## Minimal conditional fix

Change class resolution to `create()` only when `$arguments` is non-empty and
add `interface_exists()`.

This is a useful emergency patch but does not meet the stated goal. It leaves
virtual types, `app()` container methods, named-binding inspection, and stable
error behavior incomplete.

## Return raw ObjectManager forever

This preserves concrete return identity but cannot provide Laravel's container
surface because PHP cannot add `make`, `has`, `singleton`, or `instance` to the
existing Magento class.

## Install or copy Laravel's container

Rejected. A second general container would not understand Magento's DI graph
without duplicated configuration and would introduce conflicting instance
lifecycles.

## Make `Containers` itself the entire adapter

Rejected. `Containers` is a Magewire service-type registry with boot ordering
responsibilities. Expanding it into the application container would mix named
Magewire aliases, Magento object construction, and runtime overrides in one
class.

# Scope boundary

"Works like Laravel" should mean Laravel-style helper and common container
resolution behavior backed by Magento. It cannot honestly mean the complete
Laravel Foundation Application API. Calls such as `app()->environment()`,
`runningInConsole()`, `getLocale()`, path helpers, and router APIs require
separate Magento-specific adapters if Magewire retains upstream code that uses
them.

Future Portman syncs should audit newly retained `app()->...` calls and either:

- map the method into the bounded adapter surface;
- augment the upstream feature to use Magento-native behavior; or
- keep that upstream feature excluded.
