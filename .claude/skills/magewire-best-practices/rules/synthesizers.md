# Serialization & Synthesizers

## Built-in Synthesizers

Magewire includes synthesizers for common types:

| Type | Synthesizer | Location |
|------|------------|----------|
| `array` | `ArraySynth` | `dist/` (ported from Livewire) |
| `stdClass` | `StdClassSynth` | `dist/` (ported from Livewire) |
| PHP enums | `EnumSynth` | `dist/` (ported from Livewire) |
| `float` | `FloatSynth` | `dist/` (ported from Livewire) |
| `int` | `IntSynth` | `dist/` (ported from Livewire) |
| `DataObject` | `DataObjectSynth` | `lib/Magewire/` (Magento-specific) |

If your property type isn't in this list, you need a custom synthesizer or should restructure your data into supported types.

## Prefer Scalar Properties Over Custom Synthesizers

Before writing a synthesizer, ask whether you can restructure the data into scalars or arrays. Custom synthesizers add maintenance burden and can be fragile across Magewire upgrades.

```php
// Preferred — extract what you need into scalars
public string $customerName = '';
public string $customerEmail = '';
public int $customerId = 0;

// Only if the full object is genuinely needed at the serialization boundary
public ?DataObject $customerData = null; // Handled by DataObjectSynth
```

## Writing a Custom Synthesizer

Implement three methods: `match()`, `dehydrate()`, `hydrate()`.

```php
namespace Vendor\Module\Synthesizers;

use Magewirephp\Magewire\Mechanisms\HandleComponents\Synthesizers\Synth;

class MoneySynth extends Synth
{
    public static string $key = 'money';

    /**
     * Return true if this synthesizer handles the given type.
     */
    public static function match(mixed $target): bool
    {
        return $target instanceof \Vendor\Module\ValueObject\Money;
    }

    /**
     * Convert the object to a JSON-safe representation.
     * Returns [$data, $metadata].
     */
    public function dehydrate(mixed $target, callable $dehydrateChild): array
    {
        return [
            [
                'amount' => $target->getAmount(),
                'currency' => $target->getCurrency(),
            ],
            [] // metadata (empty if not needed)
        ];
    }

    /**
     * Reconstruct the object from dehydrated data.
     */
    public function hydrate(mixed $data, array $metadata, callable $hydrateChild): mixed
    {
        return new \Vendor\Module\ValueObject\Money(
            $data['amount'],
            $data['currency']
        );
    }
}
```

## Register in Area-Scoped DI

Synthesizers are registered on `HandleComponents` with a sort order. First matching synthesizer wins.

```xml
<!-- etc/frontend/di.xml -->
<type name="Magewirephp\Magewire\Mechanisms\HandleComponents\HandleComponents">
    <arguments>
        <argument name="synthesizers" xsi:type="array">
            <item name="money" xsi:type="array">
                <item name="type" xsi:type="string">Vendor\Module\Synthesizers\MoneySynth</item>
                <item name="sort_order" xsi:type="number">500</item>
            </item>
        </argument>
    </arguments>
</type>
```

## Ensure Round-Trip Fidelity

The fundamental contract: `hydrate(dehydrate($value))` must produce a value equivalent to the original. Test this explicitly.

```php
$original = new Money(1999, 'USD');
$synth = new MoneySynth();

[$data, $meta] = $synth->dehydrate($original, fn($k, $v) => $v);
$restored = $synth->hydrate($data, $meta, fn($k, $v) => $v);

assert($restored->getAmount() === $original->getAmount());
assert($restored->getCurrency() === $original->getCurrency());
```

## `dehydrate()` Must Return JSON-Safe Data

The dehydrated data is JSON-encoded into the snapshot. Only return scalars, arrays of scalars, and nested arrays. No objects, resources, closures, or circular references.

```php
// Wrong — object in dehydrated data
public function dehydrate(mixed $target, callable $dehydrateChild): array
{
    return [$target, []]; // Object isn't JSON-safe
}

// Correct — extract to scalars
public function dehydrate(mixed $target, callable $dehydrateChild): array
{
    return [
        ['amount' => $target->getAmount(), 'currency' => $target->getCurrency()],
        []
    ];
}
```

## Avoid Storing Magento Models Directly

Magento models (`Product`, `Category`, `Customer`, etc.) are complex objects with service dependencies, resource models, and event managers. Even with a synthesizer, serializing them is fragile and expensive. Extract the data you need into scalar properties.

```php
// Wrong — storing a full product model
public ?ProductInterface $product = null;

// Correct — extract only what's needed
public int $productId = 0;
public string $productName = '';
public float $productPrice = 0.0;
public string $productImage = '';
```
