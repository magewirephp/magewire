# Phase 0 Investigation: Magewire Flakes

## Scope and baseline

This document locks the behavior found on `feat/magewire-flakes` before the
namespace and definition work begins.

- Repository: `vendor/magewirephp/magewire`
- Baseline: Magewire `3.5.0`, commit `f0193e37187add88d5dfcf06682eddd911115bf9`
- Runtime theme used for inspection: `Hyva/default-csp`
- Runtime definition provider: `app/code/Magewirephp/MagewireFlake`

`VERIFIED` means the behavior is present in source or runtime-merged Magento
configuration. `ASSUMED` means it is a compatibility precaution. `UNKNOWN`
must be settled by a regression test before relying on it.

## Current public surface

### Tag syntax — VERIFIED

| Form | Current behavior | Evidence |
| --- | --- | --- |
| `<flake:name>...</flake:name>` | Compiles to a tracked `flake` fragment component. Component names permit letters, numbers, `_`, `-`, and `.`. | `lib/Magewire/Mechanisms/HandleCompiling/View/Compiler/Middleware/Flake.php:28`; `lib/Magewire/Mechanisms/HandleCompiling/View/Compiler/Middleware/AbstractTagCompiler.php:82` |
| `<flake:name />` | Opening and closing directives are emitted together. | `lib/Magewire/Mechanisms/HandleCompiling/View/Compiler/Middleware/AbstractTagCompiler.php:224` |
| `<slot:name>...</slot:name>` | Captures a named slot in the current component area. Both `</slot:name>` and `</slot>` close it. | `lib/Magewire/Mechanisms/HandleCompiling/View/Compiler/Middleware/Slots.php:28` |
| `name="value"` | Routed to the HTML attributes bag. | `lib/Magewire/Mechanisms/HandleCompiling/View/Compiler/Middleware/AbstractTagCompiler.php:186` |
| `prop:name="value"` | Routed to the properties bag. | `lib/Magewire/Mechanisms/HandleCompiling/View/Compiler/Middleware/AbstractTagCompiler.php:188` |
| `magewire:name="value"` | Routed to the Magewire metadata bag. | `lib/Magewire/Mechanisms/HandleCompiling/View/Compiler/Middleware/AbstractTagCompiler.php:191` |
| `:name="$expression"` | The value is emitted as a PHP expression. It composes with `prop:` and `magewire:`. | `lib/Magewire/Mechanisms/HandleCompiling/View/Compiler/Middleware/AbstractTagCompiler.php:176` |
| `::name="value"` | The name loses the first two colons and the literal value is escaped at compile time. | `lib/Magewire/Mechanisms/HandleCompiling/View/Compiler/Middleware/AbstractTagCompiler.php:178` |
| `disabled` | The tag regex accepts it, but the parameter parser silently drops it because that parser only accepts `key=value`. This is a defect, not a compatibility guarantee. | `lib/Magewire/Mechanisms/HandleCompiling/View/Compiler/Middleware/AbstractTagCompiler.php:39`; `lib/Magewire/Mechanisms/HandleCompiling/View/Compiler/Middleware/AbstractTagCompiler.php:163` |

The stable namespace for this work is `flake`, backed by the
`magewire_flakes` layout handle. The registry container currently has the name
`magewire.flakes`; changing that name is unnecessary because each namespace
will use its own pageless layout instance.

### PHP and DI surface — VERIFIED

The following classes are the existing Flakes routing surface:

- `Component\FlakeFactory`: public `create()` and `createByName()` methods;
- `Component\Flake`: the empty Magewire component currently injected for a
  presentation Flake;
- `ComponentResolver\FlakeResolver`: reconstruction route named `flake`;
- `View\Fragment\Component\Flake`: closes slot capture, fetches a layout block,
  and renders it;
- `View\Action\Magewire\FlakeViewAction`: action-based block creation path;
- `SupportMagewireFlakes`: persists `magewire:flake` snapshot metadata;
- compiler middleware `Flake`: owns the literal `flake` prefix.

Runtime DI registers Flakes in four places: compiler middleware, fragment
component type, component resolver, and lifecycle hook
(`src/etc/frontend/di.xml:231`, `src/etc/frontend/di.xml:317`,
`src/etc/frontend/di.xml:508`, and `src/etc/frontend/di.xml:538`).

No documentation or focused automated test in this repository advertises
these PHP classes as a supported extension API. Nevertheless, Phase 1 will
adapt rather than arbitrarily rename the Flakes classes above where that is
practical.

### Layout registration — VERIFIED

Core creates `magewire.flakes` under the pageless layout root
(`src/view/frontend/layout/magewire_flakes.xml:14`). The current application
module contributes `button` and `counter` blocks. `button` has no `magewire`
argument; `counter` has a real component argument
(`app/code/Magewirephp/MagewireFlake/view/frontend/layout/magewire_flakes.xml:11`
and `:21`).

Magento's runtime-merged layout confirms both blocks. Runtime class inspection
found no plugins or preferences on either existing factory. Runtime DI
inspection found no plugins or preferences on the Flakes lifecycle hook.

## Current render route

```text
host PHTML containing <flake:*>
  -> Magewire template compiler middleware
  -> @magewireComponent(prefix: flake, ...)->track()
  -> occurrence fragment + mutable property bags
  -> PHP output buffers capture body and named slots
  -> FlakeFactory::createByName()
  -> cached pageless layout returns named block instance
  -> empty Magewire Component injected when absent
  -> block::toHtml()
  -> @magewireEndComponent calls end()->untrack()
```

### Rendering behavior that must be characterized

1. **The layout is cached and block instances are reused — VERIFIED.**
   `FlakeFactory` holds one layout (`FlakeFactory.php:24`) and calls
   `getBlock($name)` followed by `addData($data)` on that same block
   (`FlakeFactory.php:42-49`). Request-local data can therefore survive into a
   later occurrence. Phase 1 must prove and remove this leakage.

2. **Every current Flake becomes a Magewire component — VERIFIED.**
   The factory inserts an empty `Component\Flake` when the definition does not
   have one (`FlakeFactory.php:45`). The resolver repeats that fallback during
   reconstruction (`FlakeResolver.php:40-49`). Presentation Flakes therefore
   pay component construction/snapshot cost today.

3. **A template only enters HandleCompiling when its block already contains a
   Magewire `Component` — VERIFIED.** `HandleCompiling.php:42-46` returns for a
   plain block. Consequently the application module's `magewire:compile`
   argument on a plain demo block has no effect; no source code reads that
   argument.

4. **Nested rendering uses output-buffer nesting — VERIFIED.** The child
   fragment echoes rendered output into the surrounding buffer
   (`src/Model/View/Fragment/Component.php:103-129`). Slots are not a pure
   render tree.

5. **Slot areas are isolated, but repeated values are not occurrence objects —
   VERIFIED.** `SlotsRegistry::register()` creates a new area and default slot
   (`src/Model/View/SlotsRegistry.php:52-69`). A repeated named slot reuses the
   original `Slot` object (`:139-148`), whose entries are strings only
   (`src/Model/View/Slot.php:34-50`, `:101-109`). All entries therefore expose
   props/attributes from one owning fragment instead of occurrence-local data
   (`src/Model/View/Slot.php:133-160`).

6. **The slot snapshot is not deeply immutable — VERIFIED.** `SlotSnapshot` is
   readonly but contains mutable `Slot` objects
   (`src/Model/View/SlotSnapshot.php:26-33`).

7. **Context cleanup is not exception-safe — VERIFIED.** Compiled closing code
   calls `end()->untrack()` in one expression
   (`lib/Magewire/Mechanisms/HandleCompiling/View/Directive/Magewire/Component.php:31-35`).
   A throwing `end()` can leave the registry at the wrong area. New render
   contexts require `finally` cleanup.

8. **Compiled invalidation only watches the source host template — VERIFIED.**
   `Compiler::requiresRecompile()` compares one source mtime to one compiled
   file (`lib/Magewire/Mechanisms/HandleCompiling/View/Compiler.php:112-121`).
   It does not track nested Flake definitions, templates, theme identity, or
   layout configuration.

9. **The closest real Magewire host is already discoverable — VERIFIED.** The
   layout lifecycle tracks block routes and exposes `closestComponent()`
   (`lib/Magewire/Mechanisms/ResolveComponents/Layout/LayoutLifecycle.php:63-94`
   and `:156-182`). `SupportMagewireNestingComponents` injects that component
   into descendant template dictionaries (`SupportMagewireNestingComponents.php:34-45`).
   Flakes awareness must use its own occurrence stack, while browser reactivity
   can remain owned by this closest real Magewire host.

## Compatibility classification

### Preserve

- the `flake` prefix and `magewire_flakes` handle;
- paired and self-closing component tags;
- dotted component names;
- default and named slot authoring, including repeated named slots;
- explicit `prop:*` and `magewire:*` routing;
- literal, bound (`:`), and escaped (`::`) value modes;
- module/theme layout merging and template overrides;
- a real Magewire component explicitly assigned to a definition.

### Correct behind an opt-in definition contract

- boolean and shorthand attribute preservation;
- immutable, occurrence-local props, attributes, slots, and ancestor frames;
- regular attributes matching declared props;
- exception-safe stack cleanup;
- presentation rendering without an empty Magewire component;
- dependency-aware compiled invalidation.

Existing definitions without `flake.props` remain on legacy routing during the
transition. A definition opts into declared-prop consumption. Explicit
`prop:name` wins; undeclared regular attributes stay in the HTML bag.

### Do not preserve as a contract

- mutable block data leaking between occurrences;
- the ineffective `magewire:compile` demo argument;
- stale application comments using `<magewire:button>` for Flakes;
- current omission of boolean attributes;
- application demo templates whose regular `variant` attribute is read from
  the props bag and therefore falls back unexpectedly.

## Experimental third-party-specific code

### VERIFIED

The baseline contains a second branded compiler middleware, component,
factory, resolver, fragment, layout handle, DI registrations, and application
demo templates. They were added as an experiment in May 2026, have no focused
tests, and are not mentioned in the repository README. They duplicate the
Flakes routing path rather than provide a neutral namespace contract.

### Decision

Phase 1 removes the experimental second namespace from production DI and from
the generic Flakes contract. Product-specific code is not used to design or
prove the seam. Before release, the diff will separately list any deleted
autoloadable classes as a compatibility risk; there is no evidence that they
were a documented API.

The application module is outside this Git repository. Its experimental demo
will not be modified by the branch unless it is later brought into explicit
scope. It is evidence only, not a Phase 1 fixture.

## Proposed minimal contracts

The contracts live under `Features\SupportMagewireFlakes\Contracts`. Names are
provisional until Phase 1 coding begins, but responsibilities are locked.

```php
interface FlakeNamespaceInterface
{
    public function prefix(): string;
    public function handles(): array;
    public function registryRoot(): string;
}

interface FlakeDefinitionInterface
{
    public function name(): string;
    public function template(): string;
    public function metadata(): FlakeDefinitionMetadata;
}

interface FlakeRenderContextInterface
{
    public function namespace(): FlakeNamespaceInterface;
    public function definition(): FlakeDefinitionInterface;
    public function props(): FlakePropBag;
    public function attributes(): FlakeAttributeBag;
    public function slots(): FlakeSlotBag;
    public function ancestors(): FlakeAncestorFrames;
}

interface FlakeRendererInterface
{
    public function render(FlakeRenderContextInterface $context): string;
}
```

Two services remain concrete unless a second implementation is proven useful:

- `FlakeNamespacePool`: exact-prefix lookup and duplicate rejection;
- `FlakeDefinitionRegistry`: loads one namespace's pageless layout and
  normalizes named blocks into immutable definitions.

This is the stop condition for abstraction: Flakes is the sole production
consumer; a test-only namespace proves registration. No third implementation,
general UI framework, or Laravel-shaped service container is introduced.

## Layout metadata contract

Metadata is one nested block argument named `flake`. Its absence keeps a
definition in legacy-compatible/open-content mode.

```xml
<block name="navigation.item"
       template="Vendor_Module::flakes/navigation/item.phtml">
    <arguments>
        <argument name="flake" xsi:type="array">
            <item name="family" xsi:type="string">navigation</item>
            <item name="props" xsi:type="array">
                <item name="density" xsi:type="string">comfortable</item>
                <item name="active" xsi:type="boolean">false</item>
            </item>
            <item name="children" xsi:type="array">
                <item name="html" xsi:type="string">@html</item>
            </item>
            <item name="aware" xsi:type="array">
                <item name="density" xsi:type="string">navigation.density</item>
            </item>
        </argument>
    </arguments>
</block>
```

- `family` declares the ancestor family used by awareness.
- `props` declares prop names and defaults. Magento's XML value type supplies
  the default's type.
- `children` is an unordered allow-policy. Values may be exact component names,
  `family:*`, `@html`, or `*`. Omission means open content.
- `aware` maps a child prop to `family.prop`; lookup is closest-first.

Prop precedence is locked as:

1. explicit `prop:name`;
2. regular `name` matching a declared prop;
3. closest compatible aware ancestor;
4. definition default.

Consumed declared props do not appear in the rendered HTML attribute bag.
Definitions without `flake.props` consume no regular attributes, preserving the
existing routing path.

## Original fixtures

1. **Stateless composition:** `card` containing `heading` plus arbitrary HTML.
   It proves author order, default content, classes, and separate child
   definitions without borrowing third-party markup.
2. **Aware nesting:** `navigation` containing `navigation.item`. The parent
   supplies `density`; the child declares `aware density="navigation.density"`.
   Nested navigation proves closest-family wins and unrelated ancestors do not
   leak.
3. **Interactive/CSP:** `disclosure` with accessible trigger/panel behavior.
   It belongs to Phase 5 and is not required to design the Phase 1 registry.

## Phase 1 regression design

### Unit

- exact namespace prefix selection and duplicate-prefix rejection;
- immutable normalization of a layout block into a definition;
- legacy definition behavior when `flake` metadata is absent;
- fresh context identity and empty bags for every occurrence;
- factory adapters use namespace configuration instead of hard-coded handles;
- missing definitions fail with the existing developer-mode diagnostic shape.

### Magento integration

- `flake` loads `magewire_flakes` through normal merged layout XML;
- two occurrences of one block do not share input data;
- a test-only `fixture` prefix loads only `magewire_flakes_fixture` and a
  distinct registry root through test DI/XML;
- removing test DI removes the fixture namespace without a production
  conditional;
- a real Magewire definition still resolves and reconstructs through the
  Flakes adapter.

### Deferred to the owning phases

- tokenizer and boolean/bound attribute matrices: Phase 2;
- repeated slot occurrence attributes: Phase 2;
- child policy and awareness behavior: Phase 3;
- dependency manifests and benchmarks: Phase 4;
- interactive browser/CSP behavior: Phase 5.

## Remaining unknowns before Phase 1 edits

- Whether any external consumer instantiated the experimental branded classes
  released in `3.5.0`; no repository evidence can answer this.
- The exact Magento integration-test bootstrap available to this repository;
  the current `tests/Unit` suite is minimal and `tests/Pest` is empty.
- Whether a real stateful Flake should remain supported long-term or be split
  into a normal Magewire component plus presentation Flakes. Phase 1 preserves
  it without expanding it.
