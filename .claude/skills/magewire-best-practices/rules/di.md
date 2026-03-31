# Dependency Injection

## Area-Scoped DI for Features and Mechanisms

Features and Mechanisms must be registered in area-scoped DI files — `etc/frontend/di.xml` or `etc/adminhtml/di.xml` — never in the global `etc/di.xml`.

**Why:** Magento merges global DI config into every area. A Feature registered globally cannot be selectively disabled or replaced per area. Area-scoped registration allows frontend and admin to have different Feature sets, and lets theme compatibility modules override Features cleanly.

```xml
<!-- Correct — etc/frontend/di.xml -->
<type name="Magewirephp\Magewire\Features">
    <arguments>
        <argument name="items" xsi:type="array">
            <item name="my_feature" xsi:type="array">
                <item name="type" xsi:type="string">Vendor\Module\Magewire\Features\SupportMyFeature</item>
                <item name="sort_order" xsi:type="number">5050</item>
            </item>
        </argument>
    </arguments>
</type>

<!-- Wrong — etc/di.xml (global) -->
```

## Choose Sort Order Carefully

Sort order determines boot sequence and hook execution order. Check existing registrations before picking a number — collisions cause unpredictable behavior.

| Range | Convention |
|-------|-----------|
| 700–1600 | Core features (view model, exceptions, events, attributes) |
| 5000–5999 | Magento integration features (layouts, flash messages, loaders, notifications) |
| 99000–99999 | Late-stage lifecycle features (lifecycle hooks, backwards compatibility) |

Pick a number that places your feature in the correct execution phase relative to what it depends on.

## Use `boot_mode` Intentionally

```xml
<item name="my_feature" xsi:type="array">
    <item name="type" xsi:type="string">Vendor\Module\Features\SupportMyFeature</item>
    <item name="sort_order" xsi:type="number">5050</item>
    <item name="boot_mode" xsi:type="number">10</item> <!-- LAZY -->
</item>
```

| Mode | Value | Behavior |
|------|-------|----------|
| LAZY | 10 | Boots only when explicitly needed — use for rarely-triggered features |
| PERSISTENT | 20 | Boots during setup phase, persists across request modes — use for features that need early initialization |
| ALWAYS | 30 | Boots on every request (default) — use for features that must always be active |

Default is ALWAYS (30). Only override when you have a specific reason to defer or persist.

## Constructor Injection for Component Dependencies

Components are Magento DI-managed objects. Use constructor injection for services.

```php
class OrderHistory extends Component
{
    public array $orders = [];

    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {}

    public function mount(): void
    {
        $this->orders = $this->loadOrders();
    }
}
```

Never use `ObjectManager::getInstance()` in component code. If you need a service that isn't available through DI, fix the DI configuration rather than working around it.

## Virtual Types for Configuration Variants

When you need the same Feature class with different configuration, use Magento virtual types instead of creating empty subclasses.

```xml
<!-- Correct — virtual type for a configured variant -->
<virtualType name="Vendor\Module\Features\SupportMyFeatureWithLogging"
             type="Vendor\Module\Features\SupportMyFeature">
    <arguments>
        <argument name="enableLogging" xsi:type="boolean">true</argument>
    </arguments>
</virtualType>

<!-- Wrong — empty subclass just for DI config -->
```

## Register Synthesizers in Area-Scoped DI

Custom synthesizers follow the same area-scoped rule. Register them under the `HandleComponents` mechanism config.

```xml
<!-- etc/frontend/di.xml -->
<type name="Magewirephp\Magewire\Mechanisms\HandleComponents\HandleComponents">
    <arguments>
        <argument name="synthesizers" xsi:type="array">
            <item name="my_type" xsi:type="array">
                <item name="type" xsi:type="string">Vendor\Module\Synthesizers\MyTypeSynth</item>
                <item name="sort_order" xsi:type="number">500</item>
            </item>
        </argument>
    </arguments>
</type>
```

Sort order matters — the first synthesizer whose `match()` returns true wins.
