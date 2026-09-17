# TL;DR

Introduce selective, per-block feature configuration through `magewire:` layout
arguments, including keyed removal through `false` or `null` tombstones.

The implemented first slice is event listeners. A standalone Magewire feature
now composes with the ported event feature through ordered lifecycle hooks; it
does not extend, replace, or add Magento knowledge to `SupportEvents`. Loaders
are the next credible candidate. Arbitrary component-property mutation is not
recommended.

For this slice, "from that point on" means later layout XML declarations before
initial render. Live reconciliation after a component has mounted in the browser
is explicitly deferred.

# Context

- Started: 2026-09-15
- Initial type: Feature
- Current type: Feature
- Status: Listener slice implemented and verified
- Branch: `feat/layout-listener-overrides`
- Draft PR: #307

# Goal

Allow approved Magewire features to layer per-placement layout configuration over
component and attribute defaults, so later layout XML can add, replace, or remove
keyed entries without leaking removal markers into feature behavior.

# Tasks

- [x] Trace the `magewire:` layout-argument grammar and lifecycle
- [x] Verify Magento's array-merge and `null` interpretation behavior
- [x] Inventory active property- and attribute-backed feature configuration
- [x] Rank viable configuration candidates
- [x] Confirm the first feature/property slice
- [x] Define the listener overlay contract and precedence
- [x] Define the static layout-time versus live browser boundary
- [x] Define invalid-value and whole-configuration behavior
- [x] Plan focused unit/integration/browser coverage
- [x] Implement the accepted slice
- [x] Test
- [x] Review

# Decisions

## ✅ Use feature-owned adapters, not generic property mutation

Each participating feature should explicitly consume and validate its own layout
configuration. The mechanism should not reflect into arbitrary protected
properties or assign arbitrary public component state.

**Reasoning**

- Listeners and loaders have different normalization and removal rules.
- Public properties are reactive state, not feature configuration.
- Some attribute-backed behavior, especially locked properties, is a security
  boundary and must not be weakened by a generic override mechanism.
- Starting with explicit feature consumers keeps the compatibility surface small
  and lets a shared abstraction emerge only after multiple proven consumers.

**Evidence**

- `src/Component.php:30-49`
- `dist/Features/SupportEvents/SupportEvents.php:49-72`
- `lib/Magewire/Features/SupportMagewireLoaders/SupportMagewireLoaders.php:21-42`
- `dist/Features/SupportLockedProperties/BaseLocked.php:13-18`

**Owner**

Magewire

## ✅ Compose with the ported event feature instead of replacing it

The ported `SupportEvents` feature remains registered unchanged. The external
`SupportMagewireEvents` feature is registered separately at a later sort order.
It contributes active layout handlers during `boot()`, rejects tombstoned
dispatches through a pre-call guard, and filters the browser listener effect in
its later `dehydrate()` hook.

If the core event feature is disabled, the Magewire feature skips itself. The
Portman source and generated `SupportEvents` class remain unaware of Magento
layout arguments and Magewire resolver methods.

**Reasoning**

- Portman-owned files must remain portable representations of upstream
  Livewire behavior.
- Magento layout arguments and `magewireResolver()` belong to Magewire's
  integration layer.
- Dehydration alone can change browser subscriptions, but server dispatch
  authorization and method resolution happen earlier in `SupportEvents::call()`.
- Ordered composition preserves both server and browser behavior without
  replacing the core feature.
- Keeping active layout handlers in the existing attribute-backed listener
  source preserves dynamic component `getListeners()` implementations.

**Evidence**

- `lib/Magewire/Features/SupportMagewireEvents/SupportMagewireEvents.php`
- `src/etc/frontend/di.xml`
- `src/etc/adminhtml/di.xml`
- `dist/ComponentHookRegistry.php:42-75`
- `dist/Features/SupportEvents/SupportEvents.php:21-72`

**Owner**

Willem

## ✅ Implement listeners first

Recommended first slice: layer a layout listener map over class and `#[On]`
listeners, then remove entries whose final layout value is `false` or `null`.

**Reasoning**

- It is the motivating use case.
- The feature already centralizes listener lookup for both request authorization
  and the initial browser effect.
- Tombstones solve a concrete Magento layout-composition problem.
- The work will establish reusable precedence and normalization semantics before
  attempting the more polymorphic loader configuration.

**Evidence**

- `dist/Features/SupportEvents/HandlesEvents.php:13-19`
- `dist/Features/SupportEvents/BaseOn.php:16-26`
- `dist/Features/SupportEvents/SupportEvents.php:21-72`

**Owner**

Willem

## ✅ Limit the first slice to final server-side layout composition

Recommended scope: a later layout XML declaration can override or tombstone an
earlier layout/class/attribute listener before initial browser registration.

Changing the effective listener set on an already-mounted component should be a
separate follow-up unless it is explicitly required. Current browser handling
only adds listeners from effects and removes them during component cleanup; it
does not reconcile a changing listener set.

**Evidence**

- `dist/Features/SupportEvents/SupportEvents.php:40-47`
- `src/view/base/web/js/magewire.esm.js:10631-10654`

**Owner**

Willem

## ✅ Use `magewire:listeners` as the public argument shape

Recommended starting shape: `magewire:listeners`, an associative array keyed by
event name. String values add or replace a method; `false` and `null` values
remove that event after all sources have been normalized.

The alternative is a feature group such as `magewire:events:listeners`. Existing
code supports both top-level and grouped argument grammar, so naming should be
settled before implementation.

Accepted contract:

- The value must be an associative listener array; a non-array top-level value
  is ignored for this first slice.
- String values add or replace handlers.
- Keyed `false` and `null` values remove that event from earlier class,
  attribute, or layout sources.
- Whole-map clearing is not included.

**Evidence**

- `lib/Magewire/Mechanisms/ResolveComponents/ComponentArguments/MagewireArguments.php:79-133`

**Owner**

Willem

# Investigation

## VERIFIED

- Magewire assembles top-level `magewire:*`, legacy `magewire.*`, and grouped
  `magewire:<group>:<key>` values from block data. Arguments are reassembled from
  the reconstructed Magento block on subsequent requests.
- Magento merges repeated block argument arrays with `array_replace_recursive`,
  and its `null` argument interpreter preserves an explicit `null`. A later
  named listener item can therefore survive layout merging as a tombstone.
- `magewire:component:lazy` already provides a working feature-level precedent:
  false-like values disable attribute-driven lazy loading and a layout mode can
  override `#[Lazy]`.
- The active listener feature combines class listeners with `#[On]` listeners in
  one resolution method. That method is used for both event-call authorization
  and the listener names sent to the browser.
- The browser listener implementation is additive. Listener cleanup is tied to
  component destruction, not to a later effect containing a smaller set.
- Loader configuration is also property-backed and centralized, but accepts
  several shapes (`bool`, `string`, list, or map), making its overlay contract
  more complex than listeners.
- Validation `$rules` and `$messages` are configurable maps only on the legacy
  `Component\Form` path in the current base component. The ported validation
  traits are not enabled on `Component`, so they are a poor first target.
- Pagination data is public component state, while redirects, streams,
  notifications, flash messages, and errors are runtime commands/queues. They
  are not static configuration-overlay candidates.
- `DataCollection::has()` and `get()` use `isset`/null-coalescing semantics. A
  whole argument explicitly set to `null` cannot currently be distinguished from
  a missing argument through those accessors. Nested `null` values inside an
  argument array do survive and can be inspected.

## DECIDED

- "From that point on" primarily refers to Magento layout merge order before a
  component is delivered to the browser.
- Layout configuration should have the highest precedence because its purpose is
  to customize one component placement without changing the component class.
- Tombstones should be processed before dynamic listener placeholders are
  expanded, so a layout can remove the same declarative event key used by a
  component or `#[On]` attribute.

## DEFERRED

- Whole-map clearing through `magewire:listeners="false|null"`; this slice only
  supports keyed tombstones inside the listener array.
- Live, post-mount listener reconciliation; this slice resolves the final
  Magento layout composition before initial browser registration.
- Earlier validation of invalid listener methods; values other than strict
  `false`/`null` retain the existing failure-on-dispatch behavior.

# Candidate summary

| Priority | Feature/property | Fit | Direction |
|---|---|---|---|
| 1 | Events / `$listeners` | Strong keyed map and central resolver | Implemented |
| 2 | Magewire loaders / `$loader` | Useful keyed removals, but polymorphic input | Follow after listener semantics settle |
| 3 | Lazy loading / `#[Lazy]` options | Layout override already exists; `isolate` is the main gap | Small independent extension |
| 4 | Legacy form / `$rules`, `$messages` | Technically mergeable, but legacy and validation-sensitive | Defer |
| — | Pagination / `$paginators` | Reactive state, not configuration | Exclude |
| — | Locked properties | Security boundary | Exclude |
| — | Redirects, streams, notifications, flash messages, errors | Runtime effects or queues | Exclude |

# Tooling

- Repository source and tests
- Magento framework layout reader and argument interpreters in the host application
- Git history for Magewire V1 and V3 behavior
- `caveman-explore` for cross-file localization

# Supporting documents

- [Candidate investigation](./investigation.md)

# Implementation

- Registered the original ported `SupportEvents` feature unchanged and added an
  independent `SupportMagewireEvents` feature immediately after it in frontend
  and adminhtml areas.
- Kept the Portman source and generated `SupportEvents` class free of
  Magewire-specific resolver access.
- Active layout handlers are layered into the existing attribute listener
  source during `boot()`, before server calls and dehydration.
- Tombstoned server dispatches are rejected by a pre-call guard, while the
  later dehydration hook removes tombstoned names from the browser effect.
- The Magewire adapter skips itself when the core event feature is disabled.
- Included the upstream `EventHandlerDoesNotExist` exception that the active
  server authorization path already references, so rejected tombstoned events
  fail with the intended error instead of a missing-class error.
- Listener sources are normalized and layered in this order: component class,
  `#[On]` attributes, then `magewire:listeners`.
- Tombstones are resolved before dynamic event placeholders are expanded.
- Added focused unit coverage for normalization, precedence, and tombstones.
- Added a Magento layout and Playwright fixture covering class listeners,
  attributes, later layout replacement/removal, browser effects, and real event
  dispatch.

# Verification

- `mago lint` on all changed PHP source and test files
- `mago format --check` on all changed PHP source and test files
- Portman build with no generated `SupportEvents` diff
- `php-cs-fixer --dry-run` on the generated event-handler exception
- Magento dependency-injection compilation
- PHPUnit: 28 tests passed (full unit suite)
- Playwright events spec: 5 tests passed

# Change log

- 2026-09-15: Created the feature work item, completed candidate discovery, and
  recommended listeners as the first implementation slice.
- 2026-09-16: Accepted and implemented the `magewire:listeners` slice with
  keyed `false`/`null` tombstones, generated output, and focused unit/browser
  coverage.
- 2026-09-16: Moved layout-aware event resolution out of the Portman layer into
  an externally registered Magewire feature subclass.
- 2026-09-16: Created `feat/layout-listener-overrides` from `main` for draft
  review.
- 2026-09-16: Opened draft pull request #307.
- 2026-09-17: Reworked the listener integration into an independently ordered
  Magewire feature, restoring the original ported event feature registration.
