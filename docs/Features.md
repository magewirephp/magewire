# Magewire - Features

## Index
- [Best Practices](#best-practices)
- [Folder Structure](#folder-structure)
- [Javascript](#javascript)
    - [Document Events](#document-events)
    - [Lifecycle Hooks](#lifecycle-hooks)
- [Block Structure](#block-structure)
- [Dynamic Components](#dynamic-components)
  - [Blocks](#blocks--dc-)
  - [Widgets](#widgets--dc-)
- [Templates](#templates)
  - [Switch Template](#switch-template)
- [Directives](#directives)
  - [Wire Ignore](#wire-ignore)
    - [Children Block Rendering](#child-block-rendering)
  - [Wire Select](#wire-select)
- [Component Types](#component-types)
- [Magic Actions & Properties](#magic-actions--properties)
  - [Overwrites](#overwrites)
- [Properties](#properties)
  - [Name Specific](#name-specific)
  - [Nesting](#nesting)
- [Flash Messages](#flash-messages)
- [Redirects](#redirects)
- [Listeners & Emits](#listeners--emits)
  - [Javascript](#javascript--el-)
  - [Magic Actions Compatibility](#magic-actions-compatibility)
  - [Global Refresh Listener](#global-refresh-listener)
- [Lifecycle Hooks](#lifecycle-hooks)
- [Browser Events](#browser-events)
- [Prefetching](#prefetching)
- [Lazy Updating](#lazy-updating)
- [Keydown Modifiers](#keydown-modifiers)
- [Restricted Public Methods](#restricted-public-methods)
  - [Global](#global-rpm)
  - [Component](#component-rpm)
- [Pagination](#pagination)
- [Query String](#query-string)
- [Display Loading State](#display-loading-state)
  - [Indicator Customization](#indicator-customization)
  - [Indicator Removal](#indicator-removal)
  - [Custom Example](#custom-example-dls)
  - [Notification Type Styling](#notification-type-styling)
- [Plugins](#plugins)
  - [Plugin: Loader](#plugin--loader)
    - [Loader System Settings](#loader-system-settings)
  - [Plugin: Error](#plugin--error)
    - [Custom onError callback](#custom-onerror-callback)
- [Component Resolvers](#component-resolvers)
  - [Custom Resolvers](#custom-resolver)
- [Reset](#reset)
- [Forms](#forms)
  - [Message Translations](#message-translations--f-)
  - [Message Displayment](#message-displayment--f-)
    - [Example 1](#example-1--md-)
    - [Example 2](#example-2--md-)
    - [Example 3](#example-3--md-)
    - [Example 4](#example-4--md-)

## Best Practices
1. Use the Magewire naming conventions and structures.
2. Use Hydrators to manipulate data before or after a method gets called.
3. Contribute or create an issue when you found bugs or sucurity issues.
4. Keep components small and clean.
5. Use the ```My/Module/Magewire/Component``` folder when you introduce a new component type.
6. The ```$magewire``` component object is available in the template by default.

## Folder Structure
| Folder | Description                                                                                        |
|--------|----------------------------------------------------------------------------------------------------|
| /src   | Root folder inside your module                                                                     |
| /src/Magewire | All Magewire components live here (subfolder allowed)                                       |
| /src/view/frontend/templates/magewire | All Magewire component template files live here (subfolder allowed) |

## JavaScript
The ```Magewire``` (alias for ```Livewire```) object is globally available on the page. More information can be found
inside the [Livewire docs](https://laravel-livewire.com/docs/2.x/reference#global-livewire-js).

### Document Events
- magewire:load
- magewire:update
- magewire:available
- magewire:loader:start ```(event) => {}```
- magewire:loader:done ```(event) => {}```

### Lifecycle Hooks
[Read all about hooks](https://laravel-livewire.com/docs/2.x/reference#js-hooks)

## Block Structure
```xml
<block name="magewire.explanation">
    <arguments>
        <argument name="magewire" xsi:type="object">\My\Module\Magewire\Explanation</argument>
    </arguments>
</block>

<!--OR (with additional data)-->

<block name="magewire.explanation">
    <arguments>
        <argument name="magewire" xsi:type="array">
            <item name="type" xsi:type="object">\My\Module\Magewire\Explanation</item>
        </argument>
    </arguments>
</block>

<!--OR (with a custom template path)-->

<block name="magewire.explanation" template="My_Module::magewire/my/child/explanation.phtml">
    <arguments>
        <argument name="magewire" xsi:type="object">\My\Module\Magewire\Explanation</argument>
    </arguments>
</block>
```
> **Note**: Template will automatically be set if a Magewire component has been set. When your component is named Foo,
> just  create a foo.phtml inside the /view/templates/magewire folder.

## Dynamic Components
There are cases where you want to use the full power of Magewire, but the block is not configured in a layout XML.

### Blocks (dc)
```php
$this->layout->createBlock(Template::class)->toHtml()
```

To achieve this you have to add the same configuration as you do in a layout XML.

```php
use Magento\Framework\View\Element\Template;
use \Magento\Framework\App\ObjectManager;

$this->layout->createBlock(Template::class)
    // Setting a template is optional since Magewire can auto-bind the belonging template.
    ->setTemplate('My_Module::path/to/component/phtml.phtml')
    // Bind a valid Magewire component onto the block so it can be recognized by the layout.
    ->setData('magewire', ObjectManager::getInstance()->create(\My\Module\Magewire\Explanation::class))
    
    ->toHtml()
```

> **Note**: It's important to use create, otherwise when you try to use this Magewire component multiple they will
> share the data.

### Widgets (dc)

For widgets, the principle is the same. But here Magento does the rendering. So you cannot use ```setData()``` to assign the
Magewire component. But we can set the component via the ```constructor``` method.

```php
class MyWidget extends Template implements BlockInterface
{
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $data['magewire'] = ObjectManager::getInstance()->create(\My\Module\Magewire\Explanation::class);

        parent::__construct($context, $data);
    }
}
```
### How does it work under the hood?
On the initial page request all information needed are here and we have no problem. 
But on subsequent request Magento doesn't find the layout information.

To solve this issue all information that are needed to create the block are transported in the fingerprint "dynamic_layout". The information contains the magewire component and all block data (widget configuration).
With this information it is possible to dynamically create the missing block on a subsequent request. 


## Templates
Options within your block template. The ```$magewire``` variable is by default available in your Component template.
```php
<?php

/** @var Explanation $magewire */
use My\Module\Magewire\Explanation;

// Check if property exists or is not null.
$magewire->hasFoo();
// Get property value.
$magewire->getFoo();
// Call a custom method inside your component (if not set as uncallable).
$magewire->myCustomMethod();
// Overwrite a property after the (optional) mount method was executed.
$magewire->foo = 'barbar';
?>
```

### Switch Template
Switch to another template during a subsequent request.
```html
<div id="my-magewire-component">
    <button type="button" wire:click="login">Login</button>
</div>
```

```php
public function login()
{
    $this->switchTemplate('My_Module::customer/account/dashboard.phtml');
}
```
> **Tip**: Use the power of the layout xml to assign a "switch" template path as a data param assigned to the component.
> This way your component becomes more dynamic and extensible for other developers.

## Directives

### Wire Ignore
Ignore smart DOM diffing on specified elements within a Magewire component.

```html
<!-- Thanks to the wire:ignore, this button won't be rerenderd when fresh HTML comes in. -->
<button onclick="this.innerText = Number(this.innerText) + 1" wire:ignore>0</button>
<!-- Wire model the foo value (your component needs a public $foo property). -->
<input type="text" wire:model="foo"/>
<!-- Magewire will return fresh HTML thanks to this echoing out the foo value. -->
<?= 'Foo: ' . $magewire->getFoo() ?>
```

#### Child Block Rendering
This can be very powerful when you are in the situation where you want to keep the child blocks intact.
```html
<div>
    <!-- We assume property value of $foo is unequal to 'bar' on page load. -->
    <button wire:click="$set('foo', 'bar')">Set Foo</button>
    <!-- Fresh HTML will be injected after you've clicked the Set Foo button. -->
    <span>
        <?= 'Foo: ' . $magewire->getFoo() ?>
    </span>
    <!-- Wont change to its original state as long as the wire:ignore sits there. -->
    <div wire:ignore>
        <?= $block->getChildHtml('child.block.name') ?>
    </div>
</div>
```

### Wire Select
There are some specific cases where you have a select element including a ```wire:model``` attribute without any
modifiers. In these cases, you want to save a selected option on change. This works fine for typical mobile or mouse
interactions. But it has a couple of issues when using a keyboard where you have multiple options starting with the same
couple of letters.

The ```wire:select``` directive has two modifiers: ```debounce``` and ```blur```. By default, debounce uses a
```1500ms``` delay, but you can change this by specifying a different delay in milliseconds, such as
```debounce.2000ms```. blur syncs the model on element blur.

**Modifiers**
- Blur: Syncs model on element blur
- Debounce: Debounce on each keydown or select change

> **Important**: To use wire:select, you must always defer the model to let wire:select take over, and you cannot use a
> value with the directive.

Here's an example of how to use ```wire:select```:
```html
<!-- Only syncs on blur -->
<select wire:model.defer="country" wire:select.blur>
<!-- Syncs both on blur and on debounce -->
<select wire:model.defer="country" wire:select.debounce.blur>
<!-- Syncs both on blur and on debounce (waiting 3 seconds) -->
<select wire:model.defer="country" wire:select.debounce.3000ms.blur>    
    <option value="UA">Ukraine</option>
    <option value="AE">United Arab Emirates</option>
    <option value="GB">United Kingdom</option>
    <option value="US">United States</option>
</select>
```
Using the wire:select directive improves the user experience by allowing them to select options with ease and continue
typing without the options changing.

## Component Types
The base idea behind de default component is to keep things as simple and clean as possible without any constructor
dependencies. Therefore I've decided to create multiple component to inherit from, who give you the option to use stuff
like for instance property validation.

```php
class Explanation extends Magewirephp\Magewire\Component {}
// OR
class Explanation extends \Magewirephp\Magewire\Component\Pagination {}
```
> **Note**: More core component types will be added over time.

## Magic Actions & Properties
Toggle, set or emit without writing any PHP.
```html
<!-- Toggle properties -->
<button wire:click="$toggle('foo')">Toggle Foo</button>

<!-- Set properties -->
<button wire:click="$set('foo', 'bar')">Set Foo</button>
<button wire:click="$set('foo', '$bar')">Set Foo with $bar property value</button>
<button wire:click="$set('foo', <?= $magewire->getBar() ?>)">Set Foo with $bar property value</button>

<!-- Emit to listeners -->
<button wire:click="$emit('someListener', [123, 'bar', true])"
<button wire:click="$emitTo('layout.block.name', 'someListener', [123, 'bar', true])"
<button wire:click="$emitSelf('someListener', [123, 'bar', true])"

<!-- Refresh -->
<button wire:click="$refresh()"
```

### Overwrites
Change the behavior of magic methods for a single component with an overwrite.
```php
public function set($key, $value) {}

public function toggle($key) {}

public function refresh() {}
```

## Properties
Assign properties including a lifecycle ```updating``` and ```updated``` method.
```php
public $foo = 'bar'

/**
 * OPTIONAL METHOD: Gets executed right before the property gets assigned.
 * You should in this case use a magic method called updatingFoo(string $value): string {}
 */
public function updating($value, string $name)
{
    // Bad practice on listening for a name specific property (updatingFoo(string $value)).
    if ($name === 'foo') { return ucfirst($value); }
    // Best practice
    return ucfirst($value);
}

/**
 * OPTIONAL METHOD: Gets executed immediately after the property has been assigned.
 * You should in this case use a magic method called updatedFoo(string $value): string {}
 */
public function updated($value, string $name)
{
    // Bad practice on listening for a name specific property (updatedFoo(string $value)).
    if ($name === 'foo') { return ucfirst($value); }
    // Best practice
    return ucfirst($value);
}
```
> **Note**: Trap your public property value into a variable when you use for instance array functions, who accept
> variables as a reference, to avoid a lifecycle interruption.

### Name specific
Listen for updates on targeted properties.
```php
public $foo;

/**
 * OPTIONAL METHOD: Before the property gets updated.
 * Gets executed before the updating() lifecycle hook.
 */
public function updatingFoo(string $value): string
{
    // $value: foo
    return ucfirst('updating-' . $value); 
}

/**
 * OPTIONAL METHOD: After the property has been updated.
 * Gets executed after the updated() lifecycle hook.
 */
public function updatedFoo(string $value): string
{
    // $value: set-updating-foo
    return strtoupper('updated-' . $value);
}
```
> **Note**: Final result of this property lifecycle would be "**UPDATED-DEFINE-UPDATING-FOO**"

### Nesting
Nested array properties can be targeted specifically.
```php
class Explanation extends \Magewirephp\Magewire\Component
{
    public $nested = ['foo' => ['bar' => 'Hello world']];
    
    // Since v1.10.6: Before nested array properties are updating.
    public function updatingNested(array $value): array
    {
        // Make sure "nested.foo.bar" is always strtolower (helloworld)
        $value['foo']['bar'] = strtolower($value['foo']['bar']);
        return $value;
    }
    
    // Before it's getting updated.
    public function updatingNestedFooBar(string $value): string
    {
        // Returns ['foo' => ['bar' => 'HELLO WORLD']]
        return strtoupper($value);
    }
    
    // After it has been updated.
    public function updatedNestedFooBar(string $value): string
    {
        // Returns ['foo' => ['bar' => 'hello world']]
        return strtolower($value);
    }
    
    // Since v1.10.6: After nested array properties are updated.
    public function updatedNested(array $value): array
    {
        // Evantually strtoupper "nested.foo.bar" again (HELLOWORLD). 
        $value['foo']['bar'] = strtoupper($value['foo']['bar']);
        return $value;
    }
}
```

```html
<!-- Input value will be 'Hello world' on initialization. -->
<!-- Input value will be synced onto the component on change. -->
<input wire:model="nested.foo.bar"/>
<!-- OR -->
<button wire:click="$set('nested.foo.bar', 'Hello outerspace')">Set</button>
```

## Flash Messages
Show a flash message on the page without a reload.
```php
public function myCustomMessageMethod(string $message)
{
    $this->dispatchErrorMessage($message);
    $this->dispatchWarningMessage($message);
    $this->dispatchNoticeMessage($message);
    $this->dispatchSuccessMessage($message);
    $this->dispatchMessage($messageType, $message);
}
```
> **Translations**: Messages will automatically be transformed into a translatable phrase.

> **Message Types**: When using the `dispatchMessage()` function, the first parameter must be [one of the message types implemented by `Magento\Framework\Message\MessageInterface`](https://github.com/magento/magento2/blob/2.4-develop/lib/internal/Magento/Framework/Message/MessageInterface.php#L24-L39).

## Redirects
Redirect your customer with or without additional parameters.
```php
public function myCustomRedirectMethod()
{
    $this->redirect('/some/custom/path');
}
```

```html
<button wire:click="myCustomRedirectMethod">Redirect</button>

<!-- OR -->

<div class="checkout-success-page" wire:poll.5000ms="myCustomRedirectMethod">
    Thanks for your purchase! You will be redirected after 5 seconds.
</div>
```

## Listeners & Emits
Emit functionality in targeted or non-targeted components based on event listeners.
```php
/**
 * Component A.
 * @block my.custom.block.name
 */
class A extends \Magewirephp\Magewire\Component
{
    public function letsCallSomeone()
    {
        // Emit to every component who listens to 'youCanCallMe'.
        $this->emit('youCanCallMe', ['value' => 'hi there']);
        // Emit only to the 'my.custom.block.name' component.
        $this->emitTo('my.custom.block.name', 'youCanCallMe', ['value' => 'hi there']);
    }
}

/**
 * Component B.
 */
class B extends \Magewirephp\Magewire\Component
{
    protected $listeners = ['youCanCallMe'];
    public function youCanCallMe($value) {}
    
    // OR
    
    protected $listeners = ['youCanCallMe' => 'toDoSomething'];
    public function toDoSomething($value) {}
}
```

### JavaScript (E&L)
```js
// Emit to every component who listens to 'youCanCallMe'.
Magewire.emit('youCanCallMe', {value: 'hi there'})
// Emit only to the 'my.custom.block.name' component.
Magewire.emitTo('my.custom.block.name', 'youCanCallMe', {value: 'hi there'})
```

[As documented in the Livewire documentation](https://laravel-livewire.com/docs/2.x/events#in-js), it is possible to
listen to messages emit by components in JavaScript:
```js
// Listen for the 'youCanCallMe' event
Magewire.on('youCanCallMe', event => {
    console.log(event.value); // outputs 'hi there'
});
```

### Magic Actions Compatibility
You are able to use magic methods within your emits if this is required. Thanks to this feature you are able to for example refresh a component or set data without having to write this functionality inside your targeted component.
```php
/**
 * Component A.
 * @block my.custom.block.name
 */
class A extends \Magewirephp\Magewire\Component
{    
    public function setSomeProperties()
    {
        // Force refreshes for a separate component who is listening.
        $this->emit('$refresh', []);
        // Set a public property value for a separate component who is listening.
        $this->emitTo('layout.block.name', '$set', ['property' => 'someProperty', 'message' => 'hello world']);
        // Toggle a property value for a separate component who is listening.
        $this->emitTo('layout.block.name', '$toggle', ['value' => 'boolProperty']);
    }
}

/**
 * Component B.
 */
class B extends \Magewirephp\Magewire\Component
{
    public $stringProperty;
    public $boolProperty = false;
    
    protected $listeners = ['$refresh']; // OR ['myEventName' => '$refresh']
    // OR
    protected $listeners = ['$set']; // OR ['myEventName' => '$set']
    // OR
    protected $listeners = ['$toggle']; // OR ['myEventName' => '$toggle']
}
```
> **Note**: Emits only work during subsequent requests. They won't be dispatched on page load when you emit them
> in for example the ```mount()``` method. Use ```wire:init``` to dispatch a method on page load where an emit could
> take place.

### Global Refresh Listener
Each Magewire component has by default no ```$listeners``` attached to itself. Still, you're able to refresh a
component from within another component thanks to a global ```refresh``` listener who get's injected during a preceding
request.

Thanks to this global listener, Magewire introduces the emitToRefresh method. This gives the option to refresh any
component on the page from within your own component.
```php
public function refreshSomeOtherComponent()
{
    $this->emitToRefresh('layout.block.name');
}
```
OR
```html
<button wire:click="$emitTo('layout.block.name', 'refresh')"
```

### Observer events (since v1.7.4)
Emits can also be caught as an Observer Event. By default, all emits are automatically transformed into an observable
event prefixed with ```magewire_```. This is deliberately done, so it doesn't accidentally interfere with existing
Magento events and also to make it more recognizable.

```php
class A extends \Magewirephp\Magewire\Component
{
    public function save()
    {
        $entity = $this->fooEntityFactory->create(['data' => ['foo' => 'bar']]);
        $this->fooRepository->save($entity);
        
        $this->emit('foo_entity_saved', ['entity' => $entity]);
    }
}
```

Event ```foo_entity_saved``` is now published as ```magewire_foo_entity_saved```.
```xml
<event name="magewire_foo_entity_saved">
    <observer name="MyModuleMagewireFooEntitySaved"
              instance="My\Module\Observer\Frontend\MyModuleMagewireFooEntitySaved"/>
</event>
```

```php
class MyModuleMagewireFooEntitySaved implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $event): void
    {
        $entity = $event->getData('entity');
        $this->session->setData('foo_entity_id' => $entity->getId());
        
        // Check if the event was targeted to parent Magewire components only.
        $event->getMetaData()->isAncestorsOnly(); // boolean
        // Check if the event was targeted to itself only.
        $event->getMetaData()->isSelfOnly(); // boolean
        // Check if the event was targeted for a specific Magewire component.
        $event->getMetaData()->isToComponent() // boolean
        // Gets the layout block name of the targeted component.
        $event->getMetaData()->getToComponent() // string (layout block name)
    }
}
```

More info about Events and observers can be found [here](https://developer.adobe.com/commerce/php/development/components/events-and-observers/).

## Lifecycle Hooks
Each component undergoes a lifecycle. Lifecycle hooks allow you to run code at any part of the component's lifecyle, or before specific properties are updated.
```php
// Called on all requests, immediately after the component is instantiated, but before any other lifecycle methods are called.
public function boot() {}
// Called on all requests, after the component is mounted or hydrated, but before any update methods are called
public function booted() {}
// Called when a Livewire component is newed up (think of it like a constructor)
public function mount(...$params) {}
// Called on subsequent Livewire requests after the component has been hydrated, but before any other action occurs
public function hydrate() {}
// Runs after a property called $foo is hydrated
public function hydrateFoo($value, $request) {}
// Runs before a property called $foo is updated
public function updatingFoo($value) {}
// Runs before updating a nested property bar on the $foo property
public function updatingFooBar($value) {}
// Runs before any update to the Livewire component's data (Using wire:model, not directly inside PHP)
public function updating($value, $name) {}
// Called after a property has been updated
public function updated($value, $name) {}
// Called after the "foo" property has been updated
public function updatedFoo($value) {}
// Called after the nested "bar" key on the "foo" property has been updated
public function updatedFooBar($value) {}
// Called after rendering, but before the component has been dehydrated and sent to the frontend
public function dehydrate() {}
// Runs before a property called $foo is dehydrated
public function dehydrateFoo($value, $response) {}
```
> **Notes**:
> - Be aware of the fact that a Magewire component state will get cached when for example FPC is enabled. This
> means the ```mount()``` method will only run once during an initial page load.
> - Lifecycle hooks that take a `$value` parameter must return a value - this is usually the `$value` parameter itself.

## Browser Events
You're able to trigger browser events from within your component.
```php
public function openSubscribeModal()
{
    // With data
    $this->dispatchBrowserEvent('open-subscribe-modal', ['user' => $user->getFullName()]);
    // Without data
    $this->dispatchBrowserEvent('open-subscribe-modal');
}
```

```html
<div x-data="{ open: false }" @open-subscribe-modal.window="open = true" x-show="open">
    Thanks for your purchase! You will be redirected after 5 seconds.
</div>

<!-- OR -->

<div x-data="subscribeModal()" x-show="isOpen()">
    Thanks for your purchase! You will be redirected after 5 seconds.
</div>

<script>
    function subscribeModal() {
        let self = this

        // 'user' will exist inside event.detail like event.detail.user
        window.addEventListener('open-subscribe-modal', event => {
            self.show = !self.show
        })

        return {
            show: false,
            isOpen() { return self.show },
        }
    }
</script>
```

## Prefetching
Prefetch a component and show differences on click.
```php
public function prefetchMyContent()
{
    $this->myContent('Hello world');
}
```

```html
<button wire:click.prefetch="prefetchMyContent">Prefetch</button>
<?php if ($magewire->hasMyContent()): echo $magewire->getMyContent(); endif; ?>
```

## Lazy Updating
Prevent sending out requests for every press of a button.
```php
class Explanation extends \Magewirephp\Magewire\Component
{
    public $lazyProperty; 
}
```

```html
<input type="text" wire:model.lazy="lazyProperty"/>
```

## Keydown Modifiers
Perform actions on keydown.
```php
public function keyUp()
{
    $this->random(random_int(100, 999));
}

public function keyDown()
{
    $this->random(random_int(10, 99));
}
```

```html
<input type="text" wire:model="random" wire:keydown.arrow-up="keyUp" wire:keydown.arrow-down="keyDown"/>
```
You can also use vanilla JS instead of a PHP class method.

**Quick List**
- backspace
- escape
- shift
- tab
- arrow- right / left / up / down

## Restricted Public Methods
Public methods can be restricted from subsequent request executions. Prevent method executions who are meant for
inside the phtml template only. It's not a best practice and you should use a ViewModel in most cases.

### Global (rpm)
```xml
<type name="Magewirephp\Magewire\Model\Action\CallMethod">
    <arguments>
        <argument name="uncallableMethods" xsi:type="array">
            <item name="my_custom_set_method" xsi:type="string">myCustomSetMethod</item>
        </argument>
    </arguments>
</type>
```
**File**: etc/frontend/di.xml

### Component (rpm)
```php
class Explanation extends \Magewirephp\Magewire\Component
{
    protected array $uncallables = ['myCustomSetMethod'];
}
```

## Pagination
Render a pagination pager inside your Component's view.
```php
class Explanation extends \Magewirephp\Magewire\Component\Pagination
{
    public $page = 1;
    public $pageSize = 20;
}
```

```html
<div id="my-magewire-component">
    <?= $magewire->renderPagination() ?>
    <!-- Switch the default template with a custom one -->
    <?= $magewire->renderPagination('My_Module::html/pagination/custom_pager') ?>
</div>
```

## Query String
> **Note**: The query string feature is currently incomplete compared to the original Laravel Livewire implementation.
> At the moment you can only set public properties values via the URL when loading a page.

Define public properties via URL params on a page load
```php
class MySearchForm extends \Magewirephp\Magewire\Component
{
    public $searchText;
    
    /**
     * @url https://your.domain/customer/account/dashboard?searchText=foo
     */
    protected $queryString = [
        'searchText'
    ];
    
    // OR
    
    /**
     * @url https://your.domain/customer/account/dashboard?q=foo
     */
    protected $queryString = [
        'searchText' => [
           'alias' => 'q' // map 'searchText' as 'q'
        ]
    ];
}
```

## Display Loading State
Display a loading state only when performing a (targeted) subsequent method call.
```php
class Explanation extends \Magewirephp\Magewire\Component
{
    public $bar = null

    // Show a loading state for specific methods.
    protected $loader = ['foo'];
    // Shows 'Updating foo' as a notification when 'foo' gets re-synced.
    protected $loader = ['foo' => 'Updating foo'];
    // Shows 'Loading...' as a notification when there is a interaction with the component.
    protected $loader = 'Loading...';
    // Prevent any loading activity displayment.
    protected $loader = false;
    // Prevent notification displayment and only show the main loader.
    protected $loader = true;
    // Show both messages for method execution and property syncing.
    protected $loader = [
        '{public_property}' => 'Updating something', // e.g. 'bar'
        '{public_method}'   => 'Executing something', // e.g. 'foo'
        '{listener_event}'  => 'Some event was executed, I\'m executing method foo' // e.g. 'some_event'
    ];
    
    protected $listeners = ['some_event' => 'foo'];
    
    public function foo() {
    
        // Loading state will stay active until the listener has run
        // Loading state will disapear when there are no active listeners
        $this->emit('some_event');
        
    }
    
    public function bar() {
        //
    }
}
```
> **Note**: Keep in mind that the ```$loader``` mapping only understands subsequent executable methods.

```html
<!-- A loading bar will appear and disappear on load when method foo is mapped -->
<button wire:click="foo">Execute "foo"</button>
```

### Indicator Customization
Since version: `1.10.0`

```xml
<body>
    <!-- Change the global loading indicator -->
    <referenceBlock name="magewire.loader.overlay-spinner" template="My_Module::html/loader/custom-spinner.phtml"/>
    <!-- Change the global loading overlay (please use the original as a reference) -->
    <referenceBlock name="magewire.loader.overlay" template="My_Module::html/loader/custom-overlay.phtml"/>
    <!-- Change the global notification messenger (please use the original as a reference) -->
    <referenceBlock name="magewire.loader.notifications.messenger" template="My_Module::html/loader/notifications/custom-messenger.phtml"/>
    <!-- Change (only) the notification messenger loading spinner -->
    <referenceBlock name="magewire.loader.notifications.messenger.spinner" template="My_Module::html/loader/custom-message-spinner.phtml"/>
</body>
```
**File**: view/frontend/layout/default_hyva.xml
```html
<script>
    window.addEventListener('magewire:loader:start', () => {
        document.body.style.cursor = 'wait'
    })
    window.addEventListener('magewire:loader:done', () => {
        document.body.style.cursor = 'pointer'
    })
</script>
```
**File**: html/magewire/loader.phtml

### Indicator Removal
In some cases you want to implement your own loader because you have a global one in place or your just don't need to
notify your customer. Whatever the case, I centered all frontend logic into two phtml files to let you do whatever's
needed for the project.
```xml
<body>
    <!-- Remove only the indicator to still be able to hook into the available events -->
    <referenceBlock name="magewire.loader" remove="true"/>
    <!-- Remove all JavaScript beloning to the loader indicator -->
    <referenceBlock name="magewire.plugin.loader" remove="true"/>
</body>
```
**File**: view/frontend/layout/default_hyva.xml

### Custom Example (dls)
You're able to build it more custom for your wired component only.
```html
<div x-data="{d: false}">
    <button wire:click="start(5)" x-on:click="d = true" :disabled="d" x-on:switch-state.window="d = !d">
        Start
    </button>
</div>
```

```php
public function start(int $seconds)
{
    // Let's take a nap.
    sleep($seconds);
    // Unlock the disabled state.
    $this->dispatchBrowserEvent('switch-state');
}
```
> **Note**: This is just an example. For a disabled state you should or could use the ```wire:loading``` directive.

### Notification Type Styling
Each notification item can be one of three types (syncInput, fireEvent, callAction). By default, a notification item
has the ```magewire-notification``` class. A related (kebab-cased) subclass will be bind dynamically based on the
notification type.

- ```magewire-notification fire-event```
- ```magewire-notification sync-input```
- ```magewire-notification call-method```

## Plugins
Plugins are a good and easy way to create frontend functionality which can hook into initial page loads, subsequent
request hooks.

### Register a custom plugin
To register a custom plugin, you can reference a specific container which will take care of rendering your custom
code at the right place.

An example on how you should register a custom frontend plugin:
```xml
<referenceContainer name="magewire.plugin.scripts">
    <block name="magewire.plugin.my-custom-plugin"
           template="My_Module::page/js/magewire/plugin/my-custom-plugin.phtml"/>
</referenceContainer>
```

### Plugin: Loader
```xml
<block name="magewire.plugin.loader"...
```

The Loader plugin is closely related to the ```$loader``` property within a component. To disable the loader, you can
either access the system configuration at **Store > Settings > Advanced > Developer > Magewire** or remove the block
through layout XML.

#### Loader System Settings
> **Note**: All Magewire specific settings by default can be found at Store > Settings > Advanced > Developer > Magewire.

- **Loader / Show:** Show or hide the global loading spinner.
- **Loader / Enable Notifications:** Show or hide optional notification messages.
- **Loader / Notifications / Message Fadeout Timeout:** Determine the duration for the message to fade out after its
    target component has fully loaded.

The loader is divided into several elements, giving you greater flexibility in customizing the appearance of both
the global spinner and notifications, without having to overwrite everything.

All elements can be found in the Magewire core layout `default_hyva.xml` or be found in `Magewirephp_Magewire::html/loader`.

### Plugin: Error
```xml
<block name="magewire.plugin.error"...
```

The Error plugin disables the native exception modal in Magento's **Production** mode and instead displays exceptions
in the dev-console. Customization of the message for each HTTP status code can be achieved using the layout XML by
searching for the ```status_messages``` argument. This feature enables you to easily modify the HTTP status code
messages for specific pages according to your needs.

For handling page expiration, the ```Magewire.onPageExpired(callback)``` method is used. By default, this method
throws an ```alert()``` with a default message. Just like exceptions in production, this message can be overwritten.
Page expirations are represented by a [419](https://http.dev/419) status code.

Magewire's default 419 behavior can be overridden, allowing you to modify it according to your requirements.
```xml
<referenceContainer name="magewire.plugin.scripts">
    <!-- Make sure it's loaded after Magewire's default page expired handling. -->
    <block name="magewire.plugin.on-page-expired"
           after="magewire.plugin.error"
           template="Example_Module::page/js/magewire/plugin/on-page-expired.phtml"
    />
</referenceContainer>
```

```html
<script>
    'use strict';

    // Please be aware of the fact that this will overwrite the original callback.
    Magewire.onPageExpired(() => {
        // A new onPageExpired callback function is registered for Magewire. Therefore, this will
        // be used when a page session expires. There is no return value required. You just need
        // to make the use aware and in a way the page should be reloaded.
    })
</script>
```

#### Custom onError callback
You can also overwrite or extend Magewire's onError callback.

```xml
<referenceContainer name="magewire.plugin.scripts">
    <block name="my-custom.magewire.plugin.error"
           after="magewire.plugin.error"
           template="Example_Module::page/js/magewire/plugin/error.phtml"
    />
</referenceContainer>
```

```html
<script>
    'use strict';

    (() => {
        const magewireOriginOnErrorCallback = Magewire.components.onErrorCallback;

        // Variable status is the HTTP response code (500, 404, 301 etc.)
        Magewire.onError((status, response) => {
            magewireOriginOnErrorCallback(status, response)
            
            // Make sure to clone the response to avoid locking.
            response.clone().text().then((result) => {
                result = JSON.parse(result)
                console.error(result.message || 'Something went wrong')
            }).catch((exception) => {
                console.error(exception)
            })
        })
    })()
</script>
```

## Component Resolvers
Since version: `1.9.0`

The ResolverInterface enables you to implement your own method for constructing and reconstructing a component. This
pattern is useful for examples like Dynamic Blocks and Widgets, which are not typically implemented using Layout XML.

With this pattern, you can inject custom ResolverInterfaces that implement a function called complies(). In this
function, you can verify if a given Block belongs to your custom Resolver. If it does, the Resolver will grab a unique
name from that Resolver and automatically bind it onto the request fingerprint.

This way, the right Resolver can be re-used when a component is reconstructed on a subsequent request. "Reconstruct"
means that it uses all the ingredients to try and rebuild the component in the exact same way as it was constructed on
page load.

The gateway into Magewire remains the same, requiring a Block "magewire" data key. As soon as Magewire finds the
required data key, it passes the block. However, it does require some custom logic to ensure your block complies with
this requirement.

If a block does have a magewire key, but none of the Resolvers comply, it will automatically fall back to the original
Layout Resolver. This pattern is fully backwards compatible with older Magewire versions.

### Custom Resolver
```php
use \Magento\Widget\Block\BlockInterface as WidgetBlockInterface

class Widget implements ResolverInterface
{
    public function getName() {
        return 'widget';
    }

    /**
     * Check if the given block is of instance type WidgetBlockInterface
     */
    public function complies(BlockInterface $block): bool {
        return $block instanceof WidgetBlockInterface;
    }
    
    public function construct(Template $block): Component {
        // Load a widget, construct and return the Component.
    }
    
    public function reconstruct(RequestInterface $request): Component {
        // Use what's available in the RequestInterface to reconstruct and return the component.
    }
}
```

> **Important**: When it complies, the resolver name will be cached inside the Magewire Resolver cache based on the Block cache key.
> This way, the Component Resolver doesnt have to verify each block over and over to check which Resolver complies to
> the given block. Therefor it's important to be aware of this caching layer.

```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd"
>
    <!-- Register custom Component Resolver -->
    <type name="Magewirephp\Magewire\Model\ComponentResolver">
        <arguments>
            <argument name="resolvers" xsi:type="array">
                <item name="widget" xsi:type="object">Example\Module\Model\Magewire\Component\Resolver\Widget</item>
            </argument>
        </arguments>
    </type>
</config>
```
**File**: etc/frontend/di.xml

## Reset
Reset public property values to their initial state.
```php
class Explanation extends \Magewirephp\Magewire\Component
{
    public $foo;
    public $bar = true;
    
    public function boot(): void
    {
        $this->foo = 1337;
    }
    
    // Will reset all available pulbic properties.
    public function resetAll()
    {
        $this->reset();
    }
    
    // Will only reset the 'foo' property.
    public function resetFoo()
    {
        $this->reset(['foo']);
    }
    
    // Will only reset the 'foo' property and run the boot() method afterwards.
    public function resetFooWithBoot()
    {
        $this->reset(['foo'], true);
    }
}
```

## Forms
Validate forms based on optional rules and messages.
```php
class Explanation extends \Magewirephp\Magewire\Component\Form
{
    public $foo;
    
    // Always make sure the nested 'bar' property has a default value to avoid
    // bar being seen as a value of key zero.
    public $nesting = ['bar' => ''];
    
    // Determine the rules for your properties (optional).
    protected $rules = [
        'foo' => 'required|min:2',
        'nesting.bar' => 'required|min:2|max:6',
    ];
    
    // Overwrite default rule messages or define a global for each property (optional).
    protected $messages = [
        'foo:min' => 'He! the minimal input length of :attribute needs to be 2 instead of :value.',
        'nesting.bar:required' => 'The "Nesting Bar" property can\'t be empty...',
        'nesting.bar:max' => 'Take it easy, just six characters allowed.',
    ];
    
    public function save()
    {
        // Will throw a ValidationException which extends from AcceptableException who won't break the lifecycle when
        // it gets thrown. Still you can catch it and change course if you need to.
        $this->validate();
        
        $this->dispatchSuccessMessage('Validation succes');
    }
}
```
Go and read the [Rakit/Validation](https://github.com/rakit/validation) documentation for more information.

### Message Translations (f)
Use Magento's regular i18n translations to translate form validation messages.
```csv
":attribute value (:value) has a minimal length of two.",":attribute value (:value) has a minimal length of two."
```
> **Note**: Both ```:attribute``` and ```:value``` can be used when required.

### Message Displayment (f)
By default, messages aren't shown on the page after a validation failure. You have to put in some work in order to let
the user know what happend. This can be done in several ways.

#### Example 1 (md)
Show corresponding error messages below the field.
```html
<form>
    <input type="text" wire:model="foo"/>
    
    <?php if ($magewire->hasError('foo')): ?>
    <span class="text-red-800">
        <?= $magewire->getError('foo') ?>
    </span>
    <?php endif ?>
</form>
```

#### Example 2 (md)
Display a stack of error messages on above the form.
```html
<?php if ($magewire->hasErrors()): ?>
<ul>
    <?php foreach ($magewire->getErrors() as $error): ?>
    <li class="text-red-800"><?= $error ?></li>    
    <?php endforeach ?>
</ul>
<?php endif ?>

<form>
    <input type="text" wire:model="foo"/>
</form>
```

#### Example 3 (md)
Within this example, we use a try-catch structure to catch optional validation failure. This isn't required by default
where the lifecycle is able to handle these ValidationException's by default.
```php
class Explanation extends \Magewirephp\Magewire\Component\Form
{
    public $foo;
    
    public $rules = [
        'foo' => 'required|min:2',
    ];
    
    public function save()
    {
        try {
            $this->validate();
        } catch (\Magewirephp\Magewire\Exception\ValidationException $exception) {
            foreach ($this->getErrors() as $error) {
                $this->dispatchErrorMessage($error);
            }
        }
    }
}
```

#### Example 4 (md)
Catch global validation exceptions.
```php
class Explanation extends \Magewirephp\Magewire\Component\Form
{
    public $foo;
    
    public $rules = [
        'foo' => 'required|min:2',
    ];
    
    public function save()
    {
        try {
            $this->validate();
        } catch (\Magewirephp\Magewire\Exception\AcceptableException $exception) {
            // When you want to render the error in the view (key can be changed if required).
            $this->clearErrors()->error('validation_exception', $exception->getMessage());
            // When you want to notify the customer with a flash message.
            $this->dispatchErrorMessage($exception->getMessage());
        }
    }
}
```
```html
<form>
    <?php if ($magewire->hasError('validation_exception'): ?>
    <p>Form exception thrown: <?= $magewire->getError('validation_exception') ?></p>
    <?php endif ?>
    
    <input type="text" wire:model="foo"/>
</form>
```
