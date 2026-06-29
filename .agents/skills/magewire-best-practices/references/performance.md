# Performance

## Skip Unnecessary Re-renders

Every public method call triggers a full re-render by default. When a method only updates internal state without visible changes, call `$this->skipRender()`.

```php
public function trackEvent(string $eventName): void
{
    $this->analytics->track($eventName);
    $this->skipRender(); // No DOM changes needed
}
```

## Choose the Right `wire:model` Variant

`wire:model.live` sends an AJAX request on every keystroke. For most inputs, the default `wire:model` (syncs on next action) or `wire:model.blur` (syncs on focus loss) is sufficient.

```html
<!-- Default — syncs on submit/next action (recommended for most forms) -->
<input wire:model="email" type="email">

<!-- Blur — validates when user leaves field (good for form validation) -->
<input wire:model.blur="email" type="email">

<!-- Live with debounce — only when instant feedback is needed -->
<input wire:model.live.debounce.300ms="searchQuery" type="text">

<!-- Avoid — fires on every keystroke without debounce -->
<input wire:model.live="searchQuery" type="text">
```

## Minimize Public Properties

Every public property is serialized into the snapshot on every single request — even if it didn't change. Keep the public property count low.

```php
// Avoid — large dataset as a public property
public array $allProducts = []; // 500 items serialized every request

// Better — paginated, minimal data
public array $products = []; // Only current page
public int $currentPage = 1;
public int $totalPages = 1;
```

If data doesn't need to survive between requests, use a computed property instead of storing it as a public property.

## Use `wire:loading` Instead of Polling

Show feedback during server roundtrips with `wire:loading` directives. Don't implement custom polling loops or JavaScript spinners.

```html
<button wire:click="save" wire:loading.attr="disabled">
    <span wire:loading.remove wire:target="save">Save Order</span>
    <span wire:loading wire:target="save">Processing...</span>
</button>
```

## Lazy Boot Mode for Rarely-Used Features

If you're creating a Feature that only triggers in specific scenarios, register it with `boot_mode: 10` (LAZY) so it doesn't boot on every request.

```xml
<item name="my_feature" xsi:type="array">
    <item name="type" xsi:type="string">Vendor\Module\Features\SupportMyFeature</item>
    <item name="sort_order" xsi:type="number">5050</item>
    <item name="boot_mode" xsi:type="number">10</item>
</item>
```

## Avoid Heavy Operations in Lifecycle Hooks

`boot()`, `hydrate()`, and `dehydrate()` run on every request. Don't put database queries, API calls, or complex calculations there without guarding.

```php
// Wrong — database query on every request
public function boot(): void
{
    $this->categories = $this->categoryRepository->getList()->getItems();
}

// Correct — load once in mount, cache in property
public function mount(): void
{
    $this->categories = $this->loadCategories();
}
```

## Keep Component Nesting Shallow

Each nested Magewire component adds to the serialization and rendering cost. Avoid deeply nested component trees — prefer flat compositions with event-based communication.

```xml
<!-- Prefer: flat sibling components communicating via events -->
<referenceContainer name="checkout.content">
    <block ... name="checkout.shipping.address" .../>
    <block ... name="checkout.shipping.method" .../>
    <block ... name="checkout.payment" .../>
</referenceContainer>

<!-- Avoid: deeply nested component hierarchies -->
```
