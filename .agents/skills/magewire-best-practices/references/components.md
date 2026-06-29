# Component Design

## Extend the Correct Base Class

Always extend `Magewirephp\Magewire\Component`. Never extend Magento block classes (`AbstractBlock`, `Template`) — Magewire handles the block layer internally via `Magewirephp\Magewire\Block\Magewire`.

```php
// Correct
namespace Vendor\Module\Magewire;

use Magewirephp\Magewire\Component;

class ShippingAddress extends Component
{
    public string $city = '';
}
```

```php
// Wrong — extending a Magento block class
use Magento\Framework\View\Element\Template;

class ShippingAddress extends Template { }
```

## Use Typed Properties with Defaults

Every public property must have a type declaration and a default value. Uninitialized properties break snapshot serialization because the framework cannot determine the expected type when hydrating from JSON.

```php
// Correct
public string $name = '';
public int $quantity = 1;
public array $items = [];
public bool $active = false;

// Wrong — no type or no default
public $name;
public string $email;
```

## Keep Components Small and Focused

A component should represent one interactive unit. If a component grows beyond ~10 public properties or ~5 public methods, split it into parent-child components. The parent coordinates, the children handle specific concerns.

```php
// Correct — focused component
class ShippingMethod extends Component
{
    public string $selectedMethod = '';
    public array $availableMethods = [];

    public function select(string $method): void
    {
        $this->selectedMethod = $method;
        $this->dispatch('shipping-method-selected', method: $method);
    }
}
```

## Initialize in `mount()`, Not the Constructor

The constructor is called during hydration (every request). Use `mount()` for one-time initialization logic — it only runs on the initial render.

```php
// Correct
public function mount(array $params): void
{
    $this->items = $this->itemRepository->getList($params['category_id']);
}

// Wrong — runs on every request, not just the first
public function __construct(ItemRepository $itemRepository)
{
    $this->items = $itemRepository->getAll();
}
```

Constructor injection via Magento DI is fine for injecting dependencies — just don't execute initialization logic there.

## Use `skipRender()` for State-Only Updates

When a method only changes internal state without needing to update the DOM, call `$this->skipRender()` to avoid an unnecessary re-render cycle.

```php
public function addToCompare(int $productId): void
{
    $this->compareList[] = $productId;
    $this->skipRender(); // No visual change needed yet
}
```

## Inject Dependencies via Magento DI

Use constructor injection for services your component needs. Magento's DI handles instantiation.

```php
class ProductSearch extends Component
{
    public string $query = '';
    public array $results = [];

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {}

    public function search(): void
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('name', "%{$this->query}%", 'like')
            ->create();

        $this->results = $this->productRepository
            ->getList($criteria)
            ->getItems();
    }
}
```

Never use `ObjectManager::getInstance()` directly in component code.
