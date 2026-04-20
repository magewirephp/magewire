# Conventions & Style

## Naming Conventions

| What | Convention | Good | Bad |
|------|-----------|------|-----|
| Component class | PascalCase, singular | `ShippingAddress` | `shipping_address`, `Addresses` |
| Component namespace | `Vendor\Module\Magewire\` | `Vendor\Module\Magewire\Cart` | `Vendor\Module\Block\Cart` |
| Public property | camelCase, typed with default | `public string $firstName = ''` | `public $first_name` |
| Public method | camelCase, verb-first | `addToCart()`, `loadItems()` | `cart_add()`, `Items()` |
| Event name | kebab-case | `cart-updated` | `cartUpdated`, `CART_UPDATED` |
| Block name (layout XML) | snake_case with dots | `checkout.shipping.address` | `shippingAddress`, `checkout-shipping` |
| Template file | kebab-case `.phtml` | `shipping-address.phtml` | `ShippingAddress.phtml` |
| Feature class | `Support` + scope prefix + name | `SupportMagewireLoaders` | `LoaderFeature`, `MyFeature` |
| Directive name | `mage:` prefix, kebab-case | `mage:throttle` | `magewire-throttle` |
| JS utility | `magewire{Name}Utility()` | `magewireStrUtility()` | `strUtility()` |
| JS addon | `magewire{Name}Addon()` | `magewireNotifierAddon()` | `notifierAddon()` |
| Alpine component | `magewire{Name}()` | `magewireNotifier()` | `notifierComponent()` |

## Component Class Organization

Place component classes in the `Magewire` subdirectory of your module — not in `Block`, `Model`, or `Controller`.

```
Vendor/Module/
├── Magewire/
│   ├── ShippingAddress.php      ← Component
│   ├── ShippingMethod.php       ← Component
│   └── Cart/
│       ├── Summary.php          ← Grouped component
│       └── Items.php            ← Grouped component
├── Block/                       ← Magento blocks (not Magewire components)
├── Model/                       ← Business models
└── etc/
```

## One Component Per File

Each component class gets its own file, named after the class.

## Template Files Match Purpose

Name template files after what they render, not after the component class. Use kebab-case.

```
Vendor/Module/view/frontend/templates/
├── shipping-address.phtml       ← Good
├── shipping-method.phtml
├── cart/
│   ├── summary.phtml
│   └── items.phtml
```

## PHP Code Style

Follow PSR-12 with Magento conventions:

- `declare(strict_types=1)` at the top of every PHP file
- Type declarations on all method parameters and return types
- `readonly` on constructor-promoted properties that don't change
- `private` by default, `protected` when subclasses need access, `public` only for component API

```php
<?php

declare(strict_types=1);

namespace Vendor\Module\Magewire;

use Magewirephp\Magewire\Component;

class ShippingAddress extends Component
{
    public string $street = '';
    public string $city = '';
    public string $postcode = '';

    public function __construct(
        private readonly AddressRepositoryInterface $addressRepository
    ) {}

    public function mount(int $addressId = 0): void
    {
        if ($addressId > 0) {
            $this->loadAddress($addressId);
        }
    }

    public function save(): void
    {
        $this->addressRepository->save($this->toAddressData());
        $this->magewireNotifications()->make(__('Address saved'))->asSuccess();
    }

    private function loadAddress(int $id): void
    {
        $address = $this->addressRepository->getById($id);
        $this->fill([
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'postcode' => $address->getPostcode(),
        ]);
    }

    private function toAddressData(): array
    {
        return [
            'street' => $this->street,
            'city' => $this->city,
            'postcode' => $this->postcode,
        ];
    }
}
```

## Keep Logic Out of Templates

Templates render state — they don't compute it. If you find `foreach` loops with calculations, `if` chains with business rules, or service calls in PHTML, move that logic to the component class.

## No HTML in PHP Classes

Component methods return data, not markup. Rendering is the template's job.

```php
// Wrong
public function getStatusBadge(): string
{
    return '<span class="badge badge-' . $this->status . '">' . $this->status . '</span>';
}

// Correct — return data, let the template render
public function getStatusProperty(): string
{
    return $this->order->getStatus();
}
```
