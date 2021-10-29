# Magewire - Features
> Please keep in mind that Magewire is currently in a Beta-phase. Therefore not all architectural choices are set in
> concrete. So make sure you are aware of the risks of building on top of the platform in it's current state.

## Best Practices
1. Use the Magewire naming conventions and structures.
2. Use Hydrators to manipulate data before or after a method gets called.
3. Contribute or create an issue when you found bugs or sucurity issues.
4. Keep components small and clean
5. Use the ```My/Module/Magewire/Component``` folder when you introduce a new component type.

## Folder Structure
| Folder | Description                                                                                        |
|--------|----------------------------------------------------------------------------------------------------|
| /src   | Root folder inside your module                                                                     |
| /src/Magewire | All Magewire components live here (subfolder allowed)                                       |
| /src/view/frontend/templates/magewire | All Magewire component template files live here (subfolder allowed) |

## JavaScript
The ```Magewire``` (alias for ```Livewire```) object is globally available on the page after DomContentLoaded. More
information can be found inside the [Livewire docs](https://laravel-livewire.com/docs/2.x/reference#global-livewire-js).

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

## Templates
Options within your block template.
```php
<?php

/** @var Explanation $magewire */
use My\Module\Magewire\Explanation;

$magewire = $block->getMagewire();

// Check if property exists
$magewire->hasFoo();
// Get property value
$magewire->getFoo();
// Call a custom method inside your component
$magewire->myCustomMethod();
// Overwrite a property after the (optional) mount method was executed
$magewire->foo('barbar');
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

### Skip Rendering
Set some data but prevent a frontend DOM replacement while on a subsequent request.
```php
public function setSomeProperties(string $value = 'bar')
{
    // Will set the data but will return null as effects html value.
    $this->foo($value)->skipRender();
}
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
public function myCustomSetMethod(string $value)
{
    $this->publicProperty($value);
}

/**
 * Mass assign the given property/value array.
 */
public function setDataBatch()
{
    $this->fill([
        'publicPropertyOne' => 'valueOne',
        'publicPropertyTwo' => 'valueTwo',
    ]);
}

/**
 * OPTIONAL METHOD: Gets executed right before the property gets assigned.
 */
public function updating($value, string $name)
{
    // Bad practice on listening for a name specific property.
    if ($name === 'publicProperty') { return ucfirst($value); }
    // Best practice
    return ucfirst($value);
}

/**
 * OPTIONAL METHOD: Gets executed immediately after the property has been assigned.
 */
public function updated($value, string $name)
{
    // Bad practice on listening for a name specific property.
    if ($name === 'publicProperty') { return ucfirst($value); }
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
 * OPTIONAL METHOD: Gets executed when the property gets assigned.
 */
public function defineFoo(string $value): string
{
    // $value: Updating-foo
    return strtolower('define-' . $value);
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
    
    // These nested methods will also work for the 'updating' & 'define' lifecycle methods
    public function updatedNestedFooBar(array $value): array
    {
        // A updated $nested array will be given as value of $value 
        $value['foo']['bar'] = strtoupper($value['foo']['bar']);
        
        // Will result in a uppercased value for $nested['foo']['bar']
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
}
```
> **Translations**: Messages will automatically be transformed into a translatable phrase.

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

### JavaScript
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
// Runs first and both on subsequent and preceding requests.
public function boot(...$params) {}
// Runs once, immediately after the component is instantiated, but before render() is called.
public function mount(...$params) {}
// Runs on every request, after the component is hydrated, but before an action is performed, or render() is called.
public function hydrate($request) {}
// Runs after a property called $foo is hydrated.
public function hydrateFoo($value, $request) {}
// Runs before a property called $foo is updated.
public function updatingFoo($value) {}
// Runs before any update to the Livewire component's data (Using wire:model, not directly inside PHP).
public function updating($value, $name) {}
// Runs after any update to the Livewire component's data (Using wire:model, not directly inside PHP).
public function updated($value, $name) {}
// Runs after a property called $foo is updated.
public function updatedFoo($value) {}
// Runs on every request, before the component is dehydrated, but after render() is called.
public function dehydrate($response) {}
// Runs before a property called $foo is dehydrated (COMING SOON).
public function dehydrateFoo($value, $response) {}
```
> **Note**: Be aware of the fact that a Magewire component state will get cached when for example FPC is enabled. This
> means the ```mount()``` method will only run once during an initial page load.

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
Perform actions on keydown
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

### Global
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

### Component
```php
class Explanation extends \Magewirephp\Magewire\Component
{
    private $uncallables = ['myCustomSetMethod'];
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
    `// Show a loading state for all methods
    protected $loader = true;
    // Show a loading state for specific methods
    protected $loader = ['foo'];`
    
    public function foo() {
    
        // Loading state will stay active until the listener has run
        // Loading state will disapear when there are no active listeners
        $this->emit('some_event')
        
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
        document.body.style.cursor = 'wait';
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
    <referenceBlock name="magewire.loader.script" remove="true"/>
</body>
```
**File**: view/frontend/layout/default_hyva.xml

### Custom Example
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

### Intersect Directive Plugin
> **Under construction**: This features is still under construction. Don't use this feature in any project until a
> first and final release.

Magewire has an (unique) ```intersect``` directive. This is a custom plugin can be compared with the ```init``` directive but
only when the Magewire block is inside the viewport. This can really speed up the page when for instance it's
dealing with a large dataset on the bottom of the page.

```html
<div wire:intersect="foo">
    <input type="text" wire:model="fooPropertyValue"/>
</div>

<!-- OR -->

<div wire:intersect="bar('hello', 'world')">
    <input type="text" wire:model="barPropertyValue"/>
</div>
```

```php
class Explanation extends \Magewirephp\Magewire\Component
{
    public $fooPropertyValue;
    
    public function foo()
    {
        $this->fooPropertyValue('bar');
    }
    
    public function bar(string $textOne, string $textTwo)
    {
        $this->barPropertyValue($textOne . ' & ' . $textTwo);
    }
}
```
