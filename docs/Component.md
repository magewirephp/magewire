# Magewire - Component

## Making components
**My/Module/Magewire/Explanation.php**
```php
<?php declare(strict_types=1);

namespace My\Module\Livewire;

use Magewirephp\Magewire\Livewire\Component;

class Explanation extends Component
{
    public $foo = 'bar';
}
```

**My/Module/view/frontend/templates/magewire/explanation.phtml**
```phtml
<?php declare(strict_types=1);

/** @var \My\Module\Magewire\Explanation $magewire */
$magewire = $block->getMagewire();

?>
<div>
    <?php if ($magewire->hasFoo()): ?>
        <?= $magewire->getFoo() ?>
    <?php endif; ?>
</div>
```
You will have $magewire at your disposal as long as you're inside a Magewire component. You're able to validate if a public property is set with the ```has``` prefix. Use a ```get``` prefix to get a public property value.

## Layout
The idea behind Magewire is that, like Hyva, it uses the strengths of the Magento layout structure. A block can be converted to a Magewire component in an instant just by assigning a Magewire component class. It's the exact same concept as done with ViewModels.

### Register components
You're not obliged to set a template if the block has a Magewire component. When it's parent block doesn't have a template, Magewire will set the templates based on the component class.

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd"
>
    <body>
        <referenceContainer name="content">
            <block name="magewire.explanation"
                   template="My_Module::magewire/explanation.phtml"
            >
                <arguments>
                    <argument name="magewire" xsi:type="object">\My\Module\Magewire\Explanation</argument>
                </arguments>
            </block>
        </referenceContainer>
    </body>
</page>
```

### Data
Public properties can be set on page load by an optional ```mount()``` method or via the layout XML. When you want to set it via the layout, you have to use a specific structure.

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd"
>
    <body>
        <referenceContainer name="content">
            <block name="magewire.explanation"
                   template="My_Module::magewire/explanation.phtml"
            >
                <arguments>
                    <argument name="magewire" xsi:type="array">
                        <item name="type">\My\Module\Magewire\Explanation</item>
                        <item name="foo" xsi:type="string">bar</item>
                    </argument>
                </arguments>
            </block>
        </referenceContainer>
    </body>
</page>
```
**Note**: The public ```$foo``` property will be set if it exists inside you component.
