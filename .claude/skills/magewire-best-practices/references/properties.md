# Properties & State

## Keep Properties Serializable

Public properties are serialized into the snapshot on every request. Only use types that the framework can serialize: scalars (`string`, `int`, `float`, `bool`), `array`, `null`, and types with registered synthesizers (`DataObject`, PHP enums, `stdClass`).

```php
// Correct — serializable types
public string $email = '';
public array $addresses = [];
public ?int $selectedId = null;

// Wrong — Magento model is not serializable without a custom synthesizer
public ?ProductInterface $product = null;
```

If you need data from a Magento model, extract the relevant fields into scalar properties or an array.

```php
// Correct — extract what you need
public function mount(int $productId): void
{
    $product = $this->productRepository->getById($productId);
    $this->productName = $product->getName();
    $this->productPrice = (float) $product->getPrice();
    $this->productSku = $product->getSku();
}
```

## Use `wire:model` Variants Intentionally

Each `wire:model` variant triggers different request behavior:

| Variant | Behavior | Use when |
|---------|----------|----------|
| `wire:model` | Syncs on form submit or next action | Default for most inputs |
| `wire:model.live` | Syncs on every keystroke/change | Real-time search, character counters |
| `wire:model.blur` | Syncs when input loses focus | Validation on field exit |
| `wire:model.live.debounce.300ms` | Debounced live sync | Search with rate limiting |

Default to `wire:model` unless you have a specific reason for live updates. Every `.live` binding generates an AJAX request.

## Use `fill()` for Bulk Assignment

When setting multiple properties at once from an array or DataObject, use `fill()` instead of individual assignments.

```php
// Correct
public function loadAddress(int $addressId): void
{
    $address = $this->addressRepository->getById($addressId);
    $this->fill([
        'street' => $address->getStreet(),
        'city' => $address->getCity(),
        'postcode' => $address->getPostcode(),
    ]);
}

// Verbose alternative
public function loadAddress(int $addressId): void
{
    $address = $this->addressRepository->getById($addressId);
    $this->street = $address->getStreet();
    $this->city = $address->getCity();
    $this->postcode = $address->getPostcode();
}
```

Both work, but `fill()` is cleaner for 3+ properties and triggers the updating/updated hooks correctly.

## Use `reset()` and `resetExcept()` to Restore State

When you need to clear a form or restore defaults, use the built-in reset methods instead of manually assigning each property.

```php
// Correct — reset specific properties
public function clearForm(): void
{
    $this->reset('email', 'name', 'message');
}

// Correct — reset everything except the category filter
public function clearFilters(): void
{
    $this->resetExcept('categoryId');
}

// Correct — get value and reset in one call
public function consumeMessage(): string
{
    return $this->pull('pendingMessage');
}
```

## Use Dot Notation for Nested Properties

Magewire supports dot notation for accessing and binding nested array properties.

```html
<input wire:model="address.street" type="text">
<input wire:model="address.city" type="text">
```

```php
public array $address = [
    'street' => '',
    'city' => '',
    'postcode' => '',
];
```

Property-specific lifecycle hooks use StudlyCase for dot notation: `updatingAddressCity($value)` corresponds to `address.city` (dots are replaced with underscores, then the whole string is converted to StudlyCase).

## Never Store Sensitive Data in Public Properties

Public properties are serialized into the HTML as a JSON snapshot visible in the page source. Never store passwords, API keys, tokens, or session identifiers as public properties.

```php
// Wrong — password visible in page source
public string $password = '';

// Wrong — API key exposed to client
public string $apiKey = '';
```

If a form collects sensitive input, process it immediately in the action method and don't store it as a property, or use a non-public property that won't be part of the snapshot.