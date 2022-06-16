# Magewire - Features

## Index
- [Best Practices](#best-practices)
- [Folder Structure](#folder-structure)
- [Javascript](#javascript)
    - [Document Events](#document-events)
    - [Lifecycle Hooks](#lifecycle-hooks)
- [Block Structure](#block-structure)
- [Templates](#templates)
  - [Switch Template](#switch-template)
- [Wire Ignore](#wire-ignore)
  - [Childr Block Rendering](#child-block-rendering)
- [Component Types](#component-types)
- [Magic Actions & Properties](#magic-actions--properties)
  - [Overwrites](#overwrites)
- [Properties](#properties)
  - [Name Specific](#name-specific)
  - [Nesting](#nesting)
- [Flash Messages](#flash-messages)
- [Redirects](#redirects)
- [Listeners & Emits](#listeners--emits)
  - [Javascript](#javascript-el)
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
- [Set A Predictable wire:id](#set-a-predictable-wireid)
- [Display Loading State](#display-loading-state)
  - [Indicator Customization](#indicator-customization)
  - [Indicator Removal](#indicator-removal)
  - [Custom Example](#custom-example-dls)
- [Plugins](#plugins)
- [Reset](#reset)
- [Forms](#forms)
  - [Message Translations](#message-translations-f)
  - [Message Displayment](#message-displayment-f)
    - [Example 1](#example-1-md)
    - [Example 2](#example-2-md)
    - [Example 3](#example-3-md)
    - [Example 4](#example-4-md)

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
- magewire:loader:stop ```(event) => {}```

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

## Wire Ignore
Ignore smart DOM diffing on specified elements within a Magewire component.

```html
<!-- Thanks to the wire:ignore, this button won't be rerenderd when fresh HTML comes in. -->
<button onclick="this.innerText = Number(this.innerText) + 1" wire:ignore>0</button>
<!-- Wire model the foo value (your component needs a public $foo property). -->
<input type="text" wire:model="foo"/>
<!-- Magewire will return fresh HTML thanks to this echoing out the foo value. -->
<?= 'Foo: ' . $magewire->getFoo() ?>
```

### Child Block Rendering
This can be very powerful when you are in the situation where you want to keep the child blocks intact.
```html
<div>
    <!-- We assume property value of $foo is unequal to 'bar' on page load. -->
    <button wire:click="$set('foo', 'bar')">Set Foo</button>
    <!-- Fresh HTML will be injected after you've clicked the Set Foo button. -->
    <span>
        <?= 'Foo: ' . $getFoo() ?>
    </span>
    <!-- Wont change to its original state as long as the wire:ignore sits there. -->
    <div wire:ignore>
        <?= $block->getChildHtml('child.block.name') ?>
    </div>
</div>
```

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
    protected $listeners = ['youCanCallMe'];
    public function youCanCallMe($value) {}
    
    // OR
    
    protected $listeners = ['youCanCallMe' => 'toDoSomething'];
    public function toDoSomething($value) {}
}

/**
 * Component B.
 */
class B extends \Magewirephp\Magewire\Component
{
    public function letsCallSomeone()
    {
        // Emit to every component who listens to 'youCanCallMe'.
        $this->emit('youCanCallMe', ['value' => 'hi there']);
        // Emit only to the 'my.custom.block.name' component.
        $this->emitTo('my.custom.block.name', 'youCanCallMe', ['value' => 'hi there']);
    }
}
```

### JavaScript (E&L)
```js
// Emit to every component who listens to 'youCanCallMe'.
Magewire.emit('youCanCallMe', {value: 'hi there'})
// Emit only to the 'my.custom.block.name' component.
Magewire.emitTo('my.custom.block.name', 'youCanCallMe', {value: 'hi there'})
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
    public $stringProperty;
    public $boolProperty = false;
    
    protected $listeners = ['$refresh']; // OR ['myEventName' => '$refresh']
    // OR
    protected $listeners = ['$set']; // OR ['myEventName' => '$set']
    // OR
    protected $listeners = ['$toggle']; // OR ['myEventName' => '$toggle']
}

/**
 * Component B.
 */
class B extends \Magewirephp\Magewire\Component
{
    public function setSomeProperties()
    {
        // Force refresh for a separate component who is listening.
        $this->emit('$refresh', []);
        // Set a public property value for a separate component who is listening.
        $this->emitTo('layout.block.name', '$set', ['stringProperty', 'hello world']);
        // Toggle a property value for a separate component who is listening.
        $this->emitTo('layout.block.name', '$toggle', ['boolProperty']);
    }
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
> You can also use vanilla JS instead of a PHP class method.
> 
> **Quick List**
> - backspace
> - escape
> - shift
> - tab
> - arrow- right / left / up / down

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

## Set A Predictable wire:id
Magewire generates a SHA1 hash wire:id attribute value by default. This is based on the component's layout block name.
```php
class Explanation extends \Magewirephp\Magewire\Component
{
    public $id = 'my-predictable-wire-id';
    
    public $bar;
    
    public function foo(string $bar)
    {
        $this->bar($bar);
    }
}
```

Will output as:
```html
<div wire:id="my-predictable-wire-id"></div>
```

> **Note**: SHA1 hashing the wire:id value is an idea which can change in the future. I'm still tumbling around the
> acceptance of just using the block name which has to be unique which is the most important part. I need to look into
> the security aspect when switching to an un-hashed version of the wire:id attribute.
> 
> On of the benefits would be that Magewire components are more predictable when it comes to trying to find them with
> for example Livewire.find().

Find the component and trigger the ```foo``` method:
```js
document.addEventListener('livewire:load', function () {
    Magewire.find(['my-predictable-wire-id']).foo('Some Value')
});
```

## Display Loading State
Display a loading state only when performing a (targeted) subsequent method call.
```php
class Explanation extends \Magewirephp\Magewire\Component
{
    // Show a loading state for all methods.
    protected $loader = true;
    // Show a loading state for specific methods.
    protected $loader = ['foo'];
    
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
> **Note:** Keep in mind that the ```$loader``` mapping only understands subsequent executable methods.

```html
<!-- A loading bar will appear and disappear on load when method foo is mapped -->
<button wire:click="foo">Execute "foo"</button>
```

### Indicator Customization
```xml
<body>
    <referenceBlock name="magewire.loader" template="My_Module::html/magewire/loader.phtml"/>
</body>
```
**File**: view/frontend/layout/default_hyva.xml
```html
<script>
    window.addEventListener('magewire:loader:start', () => {
        document.body.style.cursor = 'wait'
    })
    window.addEventListener('magewire:loader:stop', () => {
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
Ofcourse you're able to build it more custom for your wired component only.
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

## Plugins
> **Important**: This is still a proof of concept. It's possible this won't make it into the first official release.

It's a best practice to add your custom additions to Magewire inside the designated ```magewire.plugin``` container.
This can come in handy when you need to check if a plugin gives any trouble after installation to just temporary remove
it.

```xml
<referenceContainer name="magewire.plugin" remove="true"/>
```

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

#### Exmaple 2 (md)
Display a stack of error messages on above the form.
```html
<?php if ($magewire->hasErrors(): ?>
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
