# Lifecycle Hooks

## Choose the Right Hook

| Hook | When | Use for |
|------|------|---------|
| `mount($params)` | Initial render only | Loading data, setting defaults from layout XML arguments |
| `boot()` | Every request, before hydrate | Authorization checks, setting up request-scoped state |
| `booted()` | Every request, after hydrate | Logic that needs the component fully hydrated |
| `hydrate()` | Subsequent requests only | Restoring non-serializable state (e.g., re-fetching a service) |
| `updating($prop, $value)` | Before any property change | Validation, access control on property writes |
| `updated($prop, $value)` | After any property change | Side effects, cascading updates |
| `rendering($view, $data)` | Before template render | Injecting extra template data |
| `rendered($view, $html)` | After template render | Post-processing HTML |
| `dehydrate()` | Before snapshot serialization | Cleanup, adding effects |
| `exception($e, $stop)` | On exception | Custom error handling, user-facing messages |

## Use `mount()` for One-Time Setup

`mount()` receives parameters passed from layout XML and only runs on the initial render — not on subsequent AJAX requests.

```php
public function mount(int $categoryId, string $sortOrder = 'position'): void
{
    $this->categoryId = $categoryId;
    $this->sortOrder = $sortOrder;
    $this->products = $this->loadProducts();
}
```

Layout XML passes arguments via the block's `<arguments>`:

```xml
<block class="Magewirephp\Magewire\Block\Magewire"
       name="product.listing"
       template="Vendor_Module::product-listing.phtml">
    <arguments>
        <argument name="data" xsi:type="array">
            <item name="magewire" xsi:type="string">Vendor\Module\Magewire\ProductListing</item>
        </argument>
        <argument name="category_id" xsi:type="number">42</argument>
        <argument name="sort_order" xsi:type="string">price</argument>
    </arguments>
</block>
```

## Use `boot()` for Authorization

`boot()` runs on every request before any data processing. Use it for access control.

```php
public function boot(): void
{
    if (!$this->customerSession->isLoggedIn()) {
        $this->skipRender();
        $this->redirect('/customer/account/login');
    }
}
```

## Prefer Property-Specific Hooks Over Generic Ones

When you only care about one property changing, use the property-specific variant. It's clearer and avoids conditional branching.

```php
// Correct — targeted hook
public function updatedCountry(string $country): void
{
    $this->regions = $this->regionSource->getRegions($country);
    $this->region = '';
}

// Avoid — generic hook with conditional
public function updated(string $property, mixed $value): void
{
    if ($property === 'country') {
        $this->regions = $this->regionSource->getRegions($value);
        $this->region = '';
    }
}
```

## Never Call `render()` Manually

The framework manages the render cycle automatically. Calling `$this->render()` directly bypasses lifecycle hooks and can cause inconsistent state.

```php
// Wrong
public function refresh(): void
{
    $this->items = $this->loadItems();
    $this->render(); // Don't do this
}

// Correct — the framework re-renders after any public method call
public function refresh(): void
{
    $this->items = $this->loadItems();
}
```

## Keep Hooks Lightweight

Hooks run on every request. Avoid expensive operations in `boot()`, `hydrate()`, or `dehydrate()`. If you need data from an external source, cache it or load it lazily.

```php
// Correct — conditional loading
public function hydrate(): void
{
    if ($this->needsRefresh) {
        $this->categories = $this->categorySource->toOptionArray();
        $this->needsRefresh = false;
    }
}

// Avoid — loads on every single request
public function hydrate(): void
{
    $this->categories = $this->categorySource->toOptionArray();
}
```
