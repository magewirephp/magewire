# Magewire - Hydrators

By default Magewire come's with a stack of core hydrators who can not be overwritten by default. These take precedence
over the rest to ensure the bootstrap lifecycle.

| Order | Hydration     | Dehydration   |
| ----- | --------------| --------------|
| 1     | Security      | Redirect      |
| 2     | Browser Event | Emit          |
| 3     | Flash Message | Loader        |
| 4     | Error         | Listener      |
| 5     | Hash          | Property      |
| 6     | Component     | QueryString   |
| 7     | QueryString   | Component     |
| 8     | Property      | Hash          |
| 9     | Listener      | Error         |
| 10    | Loader        | Flash Message |
| 11    | Emit          | Browser Event |
| 10    | Redirect      | Security      |

## Make Your Own
As a developer you can manipulate the Request and/or Response going back and forth. The core concept op hydration is
that it acts as a shell where the core hydrators will encapsulate all extended. This is done to ensure the core always
has precedent on its attendants.

All core hydrators are pluggable which means in theory you should be able to write Plugins on top of all core hydrators.
This is not best practice and we always encourage you to write your own to avoid problems on future updates.

### Example
**My/Module/etc/frontend/di.xml**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd"
>
    <type name="Magewirephp\Magewire\Model\ComponentManager">
        <arguments>
            <argument name="hydrationPool" xsi:type="array">
                <item name="myAwesomeHydrator" xsi:type="array">
                    <!-- Always try to add additional Magewire functionality inside a Magewire directory -->
                    <item name="class" xsi:type="object">My\Module\Model\Magewire\Hydrator\MyAwesomeHydrator</item>
                    <!-- A sort order is only useful when you have a ton of hydrators -->
                    <item name="order" xsi:type="number">50</item>
                </item>
            </argument>
        </arguments>
    </type>
</config>
```

**My/Module/Model/Magewire/Hydrator/MyAwesomeHydrator.php**
```php
<?php declare(strict_types=1);

namespace My\Module\Model\Magewire\Hydrator;

use Magewirephp\Magewire\Livewire\Component;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

class MyAwesomeHydrator implements \Magewirephp\Magewire\Model\HydratorInterface
{
    public function hydrate(Component $component, RequestInterface $request): void
    {
        // Handle your $request here
    }
    
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        // Handle your $response here
    }
}
```

## API

### Lifecycle
Check if the hydration lifecycle is in an initial or update request.
```php
public function hydrate(Component $component, RequestInterface $request): void
{
    // Subsequent hydration request
    if ($request->isSubsequent()) {}
    
    // Initial page load hydration request 
    if ($request->isPreceding()) {}
}

public function dehydrate(Component $component, ResponseInterface $response): void
{
    // Subsequent hydration request
    if ($response->getRequest()->isSubsequent()) {}
    
    // Initial page load hydration request 
    if ($response->getRequest()->isPreceding()) {}
}
```
