# Magewire Flakes Architecture

## Boundary

Flakes provides one production namespace:

```text
prefix: flake
handle: magewire_flakes
registry root: magewire.flakes
```

The infrastructure is configured rather than hard-coded so a separate module
can register another prefix and handle later. Flakes itself contains no
third-party implementation or product name.

## Render flow

```text
Compiled Magento or Magewire host template
  -> namespaced tag compiler
  -> component namespace pool
  -> namespace definition
  -> immutable layout component definition
  -> occurrence-local render context
  -> default/named slots and ancestor frame
  -> compiled PHTML render function
  -> host Magewire DOM/snapshot
```

Phase 1 retains a compatibility block renderer behind this target flow:

```text
prefix
  -> FlakeNamespacePool
  -> cached FlakeDefinitionRegistry definition
  -> new pageless namespace layout for the occurrence
  -> new block + FlakeRenderContext
  -> block::toHtml()
```

The fresh occurrence layout is intentional isolation, not the performance end
state. Phase 4 replaces it with the compiled render function shown in the
target flow.

Every compatibility block receives an occurrence-specific `nameInLayout` and
custom cache key. Compiler tag IDs are stable across renders and receive a
deterministic `-1`, `-2`, ... suffix when the same tag executes repeatedly.
These values isolate repeated server render contexts. Presentation Flakes do
not emit `wire:id`; only definitions that explicitly bind a Magewire component
gain independent browser identity and snapshots.

## Namespace definition

A namespace definition owns only the data/services required to route a tag:

```text
ComponentNamespace
├── prefix
├── layout handle
├── registry root
├── definition registry
└── fragment renderer
```

The compiler selects a namespace by exact prefix. The registry lazily loads one
definition layout per namespace and resolves an exact component definition.
The compatibility renderer creates a different layout and block for every
occurrence so definition discovery never becomes mutable occurrence state.

A test-only namespace uses `fixture`, `magewire_flakes_fixture`, and a distinct
registry root. It must require no conditional in production PHP.

Phase 1 proves this using virtual types for the shared compiler, factory,
fragment, and resolver plus additive DI array entries. Removing the fixture
module leaves only the production `flake` prefix.

The developer-only `/magewire/playwright/flakes` route is the browser proof for
the production namespace. It composes nested and named-slot examples, repeats
one definition in a PHP loop, proves presentation occurrences have no child
snapshot, performs a real parent Magewire update, and updates one explicitly
reactive Flake independently. Its definitions use a `playwright-*` prefix
within the Flakes namespace to avoid collisions with application components.

## Flake lifecycle selection

Flake occurrences are compiled Magento blocks by default. The factory adds
`magewire:compile=true` to occurrence data and deliberately does not create an
empty Magewire component. Definitions therefore retain compiler directives,
tag middleware, slots, props, attributes, and layout lifecycle visibility
without paying component construction, mount, snapshot, or dehydration costs.

A definition opts into an independent Magewire lifecycle through the ordinary
layout argument:

```xml
<block name="counter" template="Vendor_Module::flakes/counter.phtml">
    <arguments>
        <argument name="magewire" xsi:type="object" shared="false">
            Vendor\Module\Magewire\Counter
        </argument>
    </arguments>
</block>
```

The occurrence factory preserves that component and assigns the `flake`
resolver, alias, and stable occurrence name. The resolver is used only for
these explicitly reactive definitions and reconstructs them from
`magewire_flakes.xml` on subsequent requests. The empty `Component\Flake`
factory method remains available for backwards-compatible explicit callers,
but it is no longer an automatic fallback.

The compiler occurrence name is also the reactive Flake's component ID. There
is no secondary random `magewire:id`: changing IDs during reconstruction would
make the browser unregister the child component and its directive listeners.
The Playwright proof updates the child, checks its ID remains stable, then
updates the parent and confirms the child state is preserved.

## Opt-in host template compilation

Ordinary Magento blocks can use the compiler without becoming Magewire
components:

```xml
<block name="example.compiled" template="Vendor_Module::example.phtml">
    <arguments>
        <argument name="magewire:compile" xsi:type="boolean">true</argument>
    </arguments>
</block>
```

`magewire:compile` boots the Magewire runtime early enough for the template
render hook, but the observer exits before component construction and mounting.
The block is already present on `LayoutLifecycle`, so compiler middleware and
render services can inspect its active route and Magento parent/child blocks.
The compiled template receives `__magewire`; it receives a local `magewire`
dictionary value only when the block actually owns a Magewire component.

A block can select a specialized compiler while retaining the explicit opt-in:

```xml
<arguments>
    <argument name="magewire:compile" xsi:type="boolean">true</argument>
    <argument name="magewire:compiler" xsi:type="object" shared="false">
        Vendor\Module\View\Compiler
    </argument>
</arguments>
```

The selected object must extend the abstract Magewire `Compiler`. Resolution
order is block `magewire:compiler`, existing component-owned compiler, then a
fresh default compiler from the factory. A custom compiler argument alone does
not opt an ordinary block into compilation. Existing Magewire component blocks
continue to compile implicitly and retain their mount/snapshot lifecycle.

The abstract `Compiler` implements Magento's `ArgumentInterface`, making every
valid compiler subclass legal as an `xsi:type="object"` layout argument. The
developer-only `/magewire/playwright/compiler` route verifies the default and
custom paths, three-level compiled block nesting, mixed uncompiled children,
active lifecycle routes, nested Flake middleware, and the explicit opt-in rule.
For a stateless compile, `magewire:view:compile` receives `null` as its component
argument and the rendered block as its third argument.

## Layout metadata

Definitions remain Magento blocks in the merged layout, but block instances are
treated as definition sources rather than mutable occurrence state.

```xml
<block name="navlist.item" template="Vendor_Module::flakes/navlist/item.phtml">
    <arguments>
        <argument name="flake" xsi:type="array">
            <item name="family" xsi:type="string">navlist</item>
            <item name="props" xsi:type="array">
                <item name="variant" xsi:type="string">default</item>
            </item>
            <item name="children" xsi:type="array">
                <item name="html" xsi:type="string">@html</item>
            </item>
            <item name="aware" xsi:type="array">
                <item name="variant" xsi:type="string">navlist.variant</item>
            </item>
        </argument>
    </arguments>
</block>
```

Definitions normalize into immutable values containing name, template,
declared props/defaults, child policy, aware bindings, and dependencies.

## Props and attributes

Incoming attributes are tokenized without losing boolean presence, bound
values, shorthand bindings, or directive modifiers.

Resolution order is expected to be:

1. explicit `prop:*` value for backwards compatibility;
2. regular author attribute matching a declared prop;
3. closest compatible aware ancestor value;
4. definition default.

Regular attributes are consumed as props only when declared by `flake.props`.
Without metadata they remain HTML attributes, preserving current definitions.

Attributes not consumed as props remain in an immutable HTML attribute bag.
Templates can safely merge classes and render directive/ARIA/data attributes.

## Slots

Every occurrence owns an isolated slot area:

```text
SlotArea
├── default: SlotValue
└── named: map<string, list<SlotValue>>

SlotValue
├── rendered content
└── immutable attributes
```

This supports default, named, inline self-closing, and repeated slots without
sharing mutable state across siblings or occurrences.

## Ancestor context

The renderer maintains a render-local stack of immutable frames. Each frame
contains component family, resolved props, descendant exports, and slots.

Aware lookup is family-scoped and nearest-first. Explicit child props win.
Frames are removed in a `finally` path even when a nested render throws.

Awareness is server render context. Browser reactivity continues to belong to
the closest real Magewire component through rendered `wire:*` directives.

## Child policies

Child policy metadata supports exact names, family wildcards, `@html`, and `*`.
Omission means open content. Policy order never affects render order.

The first implementation reports developer/compile-time diagnostics. It does
not silently remove content in production.

## Compilation and invalidation

The target fast path compiles tags and component templates into direct PHP
render calls. It does not create an empty Magewire component per Flake.

Each compiled artifact records dependencies on:

- source host template;
- selected namespace configuration;
- component definition/layout data;
- component template;
- nested component definitions/templates;
- theme override identity.

Memoization and folding are later optimizations. They are disallowed when
output depends on slots, awareness, translations, store/customer/request state,
view models, `wire:*`, Alpine, or non-deterministic helpers.

## Compatibility strategy

Existing Flakes tag syntax and public rendering behavior are characterized in
`investigation.md`. New infrastructure is introduced behind adapters where
needed.

Experimental third-party-specific classes are not used as the generic contract.
They are removed, deprecated, or quarantined only after their exposure and
usage are verified.
