# Layout feature override investigation

# Question

Which Magewire component properties or feature settings are suitable for
per-placement `magewire:` layout overrides with additive, replacement, and
`false`/`null` removal semantics, and which should be implemented first?

# Current argument model

`MagewireArguments` currently recognizes three forms:

| Form | Current role | Evidence |
|---|---|---|
| `magewire:<name>` | Top-level component/resolver metadata such as `id`, `name`, and `alias` | `lib/Magewire/Mechanisms/ResolveComponents/ComponentArguments/MagewireArguments.php:79-95`, `lib/Magewire/Mechanisms/ResolveComponents/ComponentResolver/LayoutResolver.php:173-192` |
| `magewire:<group>:<name>` | Named configuration groups; currently `mount` and `component` are consumed | `lib/Magewire/Mechanisms/ResolveComponents/ComponentArguments/MagewireArguments.php:48-61`, `lib/Magewire/Mechanisms/ResolveComponents/ComponentArguments/MagewireArguments.php:117-135` |
| `magewire.<name>` | Collected into a `public` subset, but no current consumer was found | `lib/Magewire/Mechanisms/ResolveComponents/ComponentArguments/MagewireArguments.php:98-115` |

The argument collection is reassembled after Magento constructs or reconstructs
the block and before component build hooks run. `magewire:mount:*` is passed into
the component mount lifecycle. Feature code can read other groups directly.

Evidence:

- `lib/Magewire/Mechanisms/ResolveComponents/ResolveComponents.php:84-108`
- `src/Observer/ViewBlockAbstractToHtmlBefore.php:102-108`
- `portman/Livewire/Features/SupportLazyLoading/SupportLazyLoading.php:22-69`

# Why tombstones work with Magento layout merging

Magento reads later block/referenceBlock argument declarations into the existing
argument array with `array_replace_recursive`. Named nested items are therefore
replaced by later declarations instead of merely appended. The `xsi:type="null"`
interpreter evaluates to PHP `null`.

That makes this layout composition viable:

```xml
<!-- Earlier declaration -->
<argument name="magewire:listeners" xsi:type="array">
    <item name="checkout:updated" xsi:type="string">refreshTotals</item>
</argument>

<!-- Later declaration -->
<argument name="magewire:listeners" xsi:type="array">
    <item name="checkout:updated" xsi:type="null"/>
</argument>
```

The final block data can contain `['checkout:updated' => null]`; Magewire must
interpret it as removal instead of forwarding it to the feature.

Evidence from the host Magento application:

- `vendor/magento/framework/View/Layout/Reader/Block.php:250-260`
- `vendor/magento/framework/View/Layout/Reader/Block.php:356-370`
- `vendor/magento/framework/Data/Argument/Interpreter/ArrayType.php:44-55`
- `vendor/magento/framework/Data/Argument/Interpreter/NullType.php:13-23`

One framework caveat matters for whole-value `null`: Magewire's
`DataCollection::has()` delegates to `isset()`, and `get()` uses null coalescing.
An explicit top-level `null` is indistinguishable from absence through these
methods. A nested `null` inside a top-level array remains inspectable via the
array value.

Evidence:

- `lib/Magewire/Support/DataCollection.php:188-215`
- `lib/Magewire/Support/DataCollection.php:300-305`

# Candidate assessment

## 1. Event listeners — strongest first slice

Sources currently include:

1. component `$listeners` via `HandlesEvents::getListeners()`;
2. `#[On]` attributes stored as `listenersFromAttributes`;
3. no layout source yet.

`SupportEvents::getComponentListeners()` is the central merge point. Its result
controls both whether an incoming `__dispatch` event is authorized and which
event names are sent to the browser on mount.

Evidence:

- `dist/Features/SupportEvents/HandlesEvents.php:13-25`
- `dist/Features/SupportEvents/BaseOn.php:16-27`
- `dist/Features/SupportEvents/SupportEvents.php:21-72`

Recommended effective-source order:

```text
class $listeners
  -> #[On] listeners
  -> layout listener overlay
  -> remove false/null entries
  -> expand dynamic placeholders
```

All sources should be normalized to `event => method` before the overlay is
applied. This lets a named tombstone remove either an associative listener or a
numeric shorthand such as `['checkout:updated']`.

Recommended initial shape:

```xml
<argument name="magewire:listeners" xsi:type="array">
    <item name="checkout:updated" xsi:type="string">refreshTotals</item>
    <item name="legacy:event" xsi:type="boolean">false</item>
    <item name="attribute:event" xsi:type="null"/>
</argument>
```

Why first:

- It is the requested use case.
- It has a single server-side resolution point.
- A false/null entry is currently unsafe: the event key can remain visible while
  its method is not callable.
- It establishes normalization, precedence, tombstone, and validation semantics
  that can later inform other features.

### Browser boundary

For initial layout composition, filtering before dehydration is sufficient: the
browser never receives removed listener names.

Live post-mount changes are different. `SupportEvents` sends listener names only
while mounting, and the browser registers every received name additively. It
removes handlers only during component cleanup. Supporting a shrinking listener
set after mount would require a reconciliation effect and per-listener cleanup,
not only a PHP merge.

Evidence:

- `dist/Features/SupportEvents/SupportEvents.php:40-47`
- `src/view/base/web/js/magewire.esm.js:10631-10654`

## 2. Magewire loaders — good second slice

The loader feature reads protected `$loader` through `getLoader()` and emits a
normalized client effect. The client matches configuration against component
method calls and property updates.

Evidence:

- `lib/Magewire/Features/SupportMagewireLoaders/HandlesMagewireLoaders.php:14-25`
- `lib/Magewire/Features/SupportMagewireLoaders/SupportMagewireLoaders.php:19-43`
- `src/view/base/templates/magewire-features/support-magewire-loaders/support-magewire-loaders.phtml:146-246`

Per-action layout additions and removals are useful, for example disabling an
inherited loader for one action on one placement. This should follow listeners
because loader configuration is polymorphic:

- `false` disables all loaders;
- `true` enables general loading behavior;
- a string is a general message;
- a list selects calls/updates;
- an associative map selects and optionally supplies messages.

A correct overlay must define how a scalar layout value interacts with a class
map, how numeric entries are normalized, and whether false/null entries mean
"remove inherited selector" or "keep a disabled selector." These are more
branches than the listener contract needs.

## 3. Lazy loading — precedent, not the best new slice

`magewire:component:lazy` already:

- opts a component into lazy loading;
- disables `#[Lazy]` with false-like values;
- overrides an attribute's trigger mode per layout placement.

The main unexposed option is `#[Lazy(isolate: ...)]`. Adding a layout override
for isolation is feasible, but it is a scalar override rather than the requested
keyed add/remove model.

Evidence:

- `portman/Livewire/Features/SupportLazyLoading/SupportLazyLoading.php:22-69`
- `portman/Livewire/Features/SupportLazyLoading/BaseLazy.php:7-15`
- `tests/Playwright/tests/lazy-loading.spec.js:121-126`
- `tests/Playwright/tests/lazy-loading.spec.js:377-410`

## 4. Validation maps — defer

The legacy `Component\Form` has protected `$rules` and `$messages` maps. They are
technically compatible with named layout overlays and tombstones.

They should not lead this work because:

- this is a legacy form path;
- the ported Livewire validation traits are present in generated source but are
  not enabled on the current base `Component`;
- removing or replacing validation rules changes a correctness/security policy,
  so its contract needs stricter validation and documentation than presentation
  configuration.

Evidence:

- `src/Component/Form.php:21-68`
- `src/Component.php:30-49`
- `dist/Features/SupportValidation/HandlesValidation.php:25-127`

## Excluded candidates

### Pagination

`$paginators` is public reactive state. Layout defaults can already reach mount
parameters, but generic runtime-state overlays should not be mixed with feature
configuration.

- `dist/Features/SupportPagination/HandlesPagination.php:14-65`

### Locked properties

`#[Locked]` prevents client updates. A generic layout-property override must not
be able to remove or weaken this security boundary.

- `dist/Features/SupportLockedProperties/BaseLocked.php:13-18`

### Redirects, streams, notifications, flash messages, and errors

These are actions, transient stores, effects, or queues produced during a
request. They do not represent declarative component configuration to merge at
block construction time.

- `dist/Features/SupportRedirects/HandlesRedirects.php:14-24`
- `lib/Magewire/Features/SupportMagewireNotifications/HandlesMagewireNotifications.php:16-23`
- `lib/Magewire/Features/SupportMagentoFlashMessages/HandlesMagewireFlashMessages.php:17-24`
- `lib/MagewireBc/Model/Concern/Error.php:19-29`

# Recommended first acceptance contract

If listeners are selected, the first slice should aim for these conditions:

1. A layout map can add a listener by event name.
2. A later layout declaration can replace the handler for an event name.
3. `false` or `null` removes the named listener regardless of whether it came
   from `$listeners`, `#[On]`, or an earlier layout declaration.
4. Removed names are absent from the initial browser `listeners` effect.
5. Removed names fail server-side event authorization exactly like any unknown
   listener.
6. Numeric shorthand and associative listener definitions normalize to the same
   event-keyed model before layout overlay.
7. Dynamic placeholders are expanded only after layout removal is resolved.
8. Existing components without the layout argument behave identically.
9. Live post-mount reconciliation remains out of scope for this first slice.

# Testing seams

The implementation plan should cover:

- pure listener normalization/overlay cases, including false and null;
- class listener addition, replacement, and removal;
- `#[On]` removal and precedence;
- dynamic placeholder ordering;
- server rejection of a tombstoned listener;
- absence of tombstoned names from initial `wire:effects`;
- Magento layout composition with two declarations for the same named item;
- regression coverage for components without `magewire:listeners`.

Although `SupportEvents` is Portman-generated under `dist/`, Magewire-specific
integration must remain external to the Portman source. The implementation
keeps the original event feature registered and adds `SupportMagewireEvents` as
an independent, later-running feature under `lib/Magewire`.

A dehydration-only overlay would be incomplete: it can replace the browser
effect, but event authorization and method resolution already occur in
`SupportEvents::call()`. The companion feature therefore adds active layout
handlers during `boot()`, rejects tombstoned calls through a pre-call guard, and
uses its later `dehydrate()` hook only for the final browser effect.

Evidence:

- `lib/Magewire/Features/SupportMagewireEvents/SupportMagewireEvents.php`
- `src/etc/frontend/di.xml`
- `src/etc/adminhtml/di.xml`
