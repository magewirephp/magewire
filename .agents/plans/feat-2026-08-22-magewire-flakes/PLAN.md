# TL;DR

Develop Magewire Flakes into a composable Magento component system backed by
layout XML, isolated render contexts, props, slots, optional child policies,
ancestor awareness, and a compiled stateless render path.

Flakes is the active product. Third-party compatibility is not implemented or
advertised, but the renderer must allow a separate module to register another
tag prefix and layout handle without changing Flakes core.

Phases 0 and 1 are complete. Current behavior and compatibility boundaries are
locked, and the namespace pool, immutable definitions, occurrence contexts,
fresh-block renderer, neutral fixture namespace, visual Playwright gallery,
and regression tests are in place. Phase 2 is the next implementation boundary.

# Context

- Started: 2026-08-22
- Initial type: Feature
- Current type: Feature
- Branch: `feat/magewire-flakes`
- Repository: `magewirephp/magewire`
- Magewire baseline: `3.5.0` / `f0193e37187add88d5dfcf06682eddd911115bf9`
- Livewire source baseline: `3.7.11`

# Goal

Allow globally registered `<flake:*>` presentation components to compose in
author order, inherit explicitly declared context from their closest compatible
ancestor, and render efficiently inside the closest real Magewire component.

Magento layout XML remains the registration and override mechanism. A future
separate adapter must be able to register its own prefix and layout handle using
the same public contracts and no product-specific conditionals in Flakes.

# Acceptance

1. `<flake:card><flake:heading>…` renders through isolated component contexts.
2. Props declared in layout XML are separated from the HTML attribute bag.
3. Default, named, self-closing, and repeated slots retain occurrence-local data.
4. Child policies are optional, unordered, and never silently remove production content.
5. An aware child inherits only from the closest declared ancestor family.
6. Presentation Flakes remain inside one closest Magewire component lifecycle.
7. Repeated occurrences do not leak block data, props, attributes, slots, or context.
8. A neutral test namespace registers a second prefix and handle through DI/XML only.
9. Existing Flakes examples and public behavior remain compatible.
10. Focused unit, integration, and browser/CSP tests pass.

# Non-goals

- Third-party component names, source, assets, or runtime dependencies.
- A Blade or Laravel runtime.
- One Magewire component instance per presentation Flake.
- Hard runtime removal of undeclared children.
- Static folding before the direct renderer is correct and benchmarked.
- Speculative stateful Flake modes.

# Tasks

- [x] Create `feat/magewire-flakes` from `main`
- [x] Verify the runtime-merged `magewire_flakes` and experimental layout handles
- [x] Verify runtime plugin/preference state for the existing factories
- [x] Trace the Flakes compiler, factory, resolver, fragment, slots, and layout lifecycle
- [x] Inventory current public syntax and backward-compatibility requirements
- [x] Separate verified Flakes behavior from experimental third-party-specific code
- [x] Define the minimal namespace, registry, definition, context, and renderer contracts
- [x] Define the layout XML metadata schema for props, children, and awareness
- [x] Define focused regression and namespace-fixture tests
- [x] Implement the namespace/definition foundation
- [x] Add a developer-only Playwright route for nested, slotted, and repeated Flakes
- [ ] Implement props, attributes, and isolated slots
- [ ] Implement composition, awareness, and child diagnostics
- [ ] Implement and benchmark the compiled stateless fast path
- [ ] Add one original interactive CSP-safe Flake
- [ ] Run focused verification and review the diff

# Decisions

## ✅ Flakes is the active product

The feature exposes the `<flake:*>` namespace and `magewire_flakes.xml`. No
third-party compatibility is part of this branch.

**Reasoning**

Flakes has independent value and can be developed without waiting for an
external agreement.

**Owner**

Willem

## ✅ Magento layout XML owns component registration

Component definitions are loaded from `magewire_flakes.xml` and remain
extendable by modules and themes through normal Magento layout merging.

**Evidence**

- `src/view/frontend/layout/magewire_flakes.xml`
- runtime `layout-inspect` for `magewire_flakes`

## ✅ Presentation Flakes are stateless fragments

Nested Flakes render inside the closest real Magewire host and do not create an
empty Magewire component/snapshot per tag.

**Reasoning**

Presentation composition does not require isolated reactive state. Keeping one
host lifecycle avoids hydration and serialization overhead.

## ✅ Future namespaces are separate registrations

Reusable infrastructure accepts a prefix, layout handle, registry root, and
renderer through a small namespace definition. A neutral test-only namespace
proves the seam.

**Reasoning**

This preserves a future adapter path without adding product-specific code now.

## ✅ Child policies are unordered and non-destructive

Missing child metadata means open content. Declared policies provide developer
diagnostics and optimizer metadata; production rendering never silently drops
content.

## ✅ Backward-compatible prop resolution

Explicit `prop:name` wins over a regular declared prop, then closest compatible
aware value, then the definition default. Regular attributes are consumed only
when `flake.props` declares them. Definitions without that metadata retain the
current attributes-bag behavior.

## ✅ Existing experimental namespace code

Remove the experimental second namespace from production DI and the Flakes
contract in Phase 1. It has no focused tests or repository documentation. Any
deleted autoloadable classes remain a release-note compatibility risk and will
be listed separately during review.

## ✅ Phase 0 fixture set

- Stateless: `card` + `heading` + arbitrary HTML.
- Aware: `navigation` + `navigation.item`, inheriting `density` from the closest
  `navigation` family.
- Interactive/CSP: `disclosure`, deferred to Phase 5.

## ✅ Phase 1 namespace and definition foundation

- `FlakeNamespacePool` selects exact prefixes configured through frontend DI.
- `FlakeDefinitionRegistry` caches normalized immutable definitions from one
  namespace layout.
- `FlakeFactory` creates a fresh pageless layout, block, and render context for
  every occurrence; it no longer mutates a cached block instance or injects an
  empty Magewire component into presentation definitions.
- The existing factory, resolver, fragment, compiler, action route, and
  `magewire_flakes` handle remain the Flakes compatibility adapters.
- A dormant Magento test module registers `fixture:` plus
  `magewire_flakes_fixture` using virtual types and layout XML only.
- Experimental product-specific runtime registrations and classes were removed
  from production source.
- `/magewire/playwright/flakes` renders original card, heading, badge, and
  button definitions inside a real Magewire host and is unavailable in
  production mode through the shared developer-action guard.
- Compiler-generated fragment IDs gain a deterministic occurrence suffix when
  a tag repeats in a loop. Every fresh Flake block receives the matching layout
  name and custom cache key so render contexts remain independent and are
  preserved through parent updates without one `wire:id` per presentation tag.
- Template-scope compilation begins after the PHTML PHP prologue but before any
  conditional markup, keeping `declare(strict_types=1)` valid and ensuring the
  scope closes on every control-flow branch.
- An ordinary Magento block can opt into the same template compiler with the
  boolean `magewire:compile` layout argument. It participates in the existing
  layout lifecycle but is never constructed, mounted, rendered, or dehydrated
  as a Magewire component.
- `magewire:compiler` selects a block-specific `Compiler` implementation when
  combined with `magewire:compile`; without it, the compiler factory creates the
  default compiler. The selector alone does not opt a stateless block in.
- Flake occurrences receive `magewire:compile=true` automatically and no
  fallback component. A layout definition becomes independently reactive only
  when it explicitly binds a `magewire` argument; the Flake resolver remains
  its reconstruction path.
- Reactive Flakes reuse the compiler occurrence name as their sole component
  ID, keeping browser registration stable across child and parent updates.

**Phase 1 performance boundary**

Fresh occurrence layouts deliberately trade speed for correctness. Phase 4
replaces this legacy block path with direct compiled rendering after props,
slots, composition, and awareness are correct.

# Investigation

## VERIFIED

- The active repository is the Git checkout at `vendor/magewirephp/magewire`.
- Branch `feat/magewire-flakes` was created from `main` at Magewire `3.5.0`.
- Magewire is generated from Livewire `3.7.11`.
- Runtime `magewire_flakes` contains the core registry container plus `button`
  and `counter` definitions contributed by `app/code/Magewirephp/MagewireFlake`.
- `counter` supplies a `magewire` layout argument; `button` does not.
- `FlakeFactory` has no runtime plugins or preferences in the current Magento installation.
- The experimental alternate factory has no runtime plugins or preferences either.
- The current experimental alternate handle reuses the `magewire.flakes` container,
  demonstrating layout reuse but not sufficient product separation.
- Frontend runtime DI resolves exactly one production namespace: `flake`,
  `magewire_flakes`, and registry root `magewire.flakes`.
- The runtime definition registry resolves the merged application `button`
  definition and template.
- Two real factory calls produced different Magento block and render-context
  identities.
- A real fragment lifecycle rendered the existing button and its `Save` default
  slot through the new Phase 1 path.
- The Playwright gallery records all 16 nested occurrences under distinct
  parent snapshot keys and preserves the complete composition after a real
  `magewire/update` round trip.
- `/magewire/playwright/compiler` covers default and custom compilers, compiled
  parent/child/grandchild blocks, a mixed plain child, nested Flake middleware,
  layout lifecycle routes, and the selector-without-opt-in negative case. Its
  ordinary hosts have neither `wire:id` nor `wire:snapshot`.

## ASSUMED

- Existing Flakes users may depend on `prop:*` authoring and current factory public classes.
- An external consumer may have instantiated the experimental autoloadable
  classes even though the repository does not document or test them.

## UNKNOWN

- The exact Magento integration-test bootstrap available to this repository.
- Whether real stateful Flakes remain a long-term product feature; Phase 1
  preserves their current route.

# Tooling

- Magewire `magewire-work` project skill
- `lean-build` scope guidance
- Magento Bricklayer runtime layout, class, and DI introspection
- Magewire repository source and tests
- Magento application modules providing current Flake examples

# Supporting documents

- [Architecture](./architecture.md)
- [Phase 0 investigation](./investigation.md)

# Change log

- 2026-08-22: Created the feature branch and repository-local work item.
- 2026-08-22: Recorded the active Flakes-only direction and future namespace boundary.
- 2026-08-22: Verified current runtime layouts and factory plugin/preference state.
- 2026-08-22: Completed Phase 0 contract inventory, schema, fixtures, and test design.
- 2026-08-22: Completed Phase 1 namespace/definition foundation and runtime isolation path.
- 2026-08-22: Verified 25 unit tests / 97 assertions, PHPStan level 6,
  Magento DI compilation, runtime definition resolution, occurrence isolation,
  and end-to-end Flake fragment rendering.
- 2026-08-22: Added the developer-only Playwright Flakes gallery and fixed
  conditional-first template scope plus repeated/nested occurrence identity.
- 2026-08-23: Added opt-in compilation for ordinary Magento block templates,
  including default/custom compiler resolution and a dedicated Playwright route.
- 2026-08-23: Made presentation Flakes compiled and stateless by default while
  retaining explicit layout-bound Magewire Flakes; added browser coverage for
  nested stateless rendering, parent rerenders, and an independent child update.
- 2026-08-23: Verified 31 unit tests / 116 assertions, PHPStan level 6,
  Magento DI compilation, and all 94 Playwright tests after lifecycle selection.
