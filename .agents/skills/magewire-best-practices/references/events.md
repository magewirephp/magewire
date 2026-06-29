# Events & Communication

## Use `dispatch()` for Cross-Component Communication

Components communicate via events. The dispatching component fires, listeners on other components react.

```php
// Dispatch from one component
public function addToCart(int $productId): void
{
    $this->cartService->add($productId);
    $this->dispatch('cart-updated', itemCount: $this->getCartCount());
}
```

```php
// Listen in another component
#[On('cart-updated')]
public function onCartUpdated(int $itemCount): void
{
    $this->cartCount = $itemCount;
}
```

## Prefer `#[On()]` Over `$listeners`

The `#[On()]` attribute is declarative, co-located with the handler method, and easier to find when reading code. Use it over the `$listeners` property array.

```php
// Preferred
#[On('cart-updated')]
public function refreshCart(int $itemCount): void
{
    $this->itemCount = $itemCount;
}

// Acceptable but less clear
protected $listeners = ['cart-updated' => 'refreshCart'];

public function refreshCart(int $itemCount): void
{
    $this->itemCount = $itemCount;
}
```

## Use Kebab-Case Event Names

Event names should be `kebab-case`, consistent with HTML attribute conventions and `wire:` directive naming.

```php
// Correct
$this->dispatch('shipping-method-selected');
$this->dispatch('payment-form-validated');

// Wrong
$this->dispatch('shippingMethodSelected');
$this->dispatch('PAYMENT_VALIDATED');
```

## Scope Events Appropriately

| Method | Scope | Use when |
|--------|-------|----------|
| `$this->dispatch('name')` | All components on the page | Broadcasting to any interested listener |
| `$this->dispatch('name')->self()` | Only the dispatching component | Internal state management, self-refresh |
| `$this->dispatch('name')->up()` | Parent component only | Child-to-parent communication |
| `$this->dispatch('name')->to(Component::class)` | Specific component type | Targeted updates |

Default to the narrowest scope that works. Broadcasting to all components when only the parent needs the event wastes processing.

## Don't Use Events for Parent-to-Child Data Flow

If a parent needs to pass data to a child, use layout XML arguments (for initial data) or public properties on the child that the parent sets. Events are for decoupled, sibling-to-sibling or child-to-parent communication.

```php
// Wrong — parent dispatching to child
public function selectCategory(int $id): void
{
    $this->selectedCategory = $id;
    $this->dispatch('category-selected', categoryId: $id); // Unnecessary
}

// Correct — child reads parent state via its own properties
// Layout XML nests the child inside the parent, and the child
// receives data through its mount() parameters or re-renders
// when the parent re-renders.
```

## Keep Event Payloads Serializable

Event data travels through JavaScript. Only pass scalar values and simple arrays — no objects, no Magento models.

```php
// Correct
$this->dispatch('order-placed', orderId: $order->getId(), total: (float) $order->getGrandTotal());

// Wrong — object not serializable to JSON
$this->dispatch('order-placed', order: $order);
```
