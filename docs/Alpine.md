# Magewire - Alpine

## Magewire Component Interaction
From any Alpine component inside a Livewire component, you can access a magic $wire object to access and manipulate the
Livewire component.
```php
class Explanation extends \Magewirephp\Magewire\Component
{
    public $foo = 'bar';
}
```

```html
<div>
    <div x-data>
        <h1 x-text="$wire.foo"></h1>

        <button x-on:click="$wire.set('foo', Math.random())">Increment</button>
    </div>
</div>
```

> ```$wire``` gives you access to the Magewire component properties using AlpineJS. To avoid a misunderstanding your could
> also use ```wire:click="$set('foo', Math.random())'"``` to set the ```foo``` property value. It just to demonstrate
> how ```x-text``` could be used in combination with Magewire.

## Syncing Magewire and AlpineJS data using Entangle
To sync data between AlpineJS and Magewire, we can use the `$wire.entangle` method. This method creates a two-way binding between an AlpineJS property and a Magewire property, allowing changes made to one property to be automatically reflected in the other.

Here is an example of how to use `$wire.entangle`:

```html
<?php /** @var Explanation $magewire */ ?>
<div x-data="{ foo: $wire.entangle('foo') }">
  <input type="text" x-model="foo">
  <!-- The value of the `foo` property can be accessed using AlpineJS: -->
  <p x-text="foo"></p>
  <!-- Alternatively, it can also be accessed using Magewire: -->
  <?= $magewire->foo ?>
</div>
```

```php
class Explanation extends \Magewirephp\Magewire\Component
{
    public $foo = 'bar';
}
```

We bind an input field to an AlpineJS property called 'foo', and use `$wire.entangle` to bind it to a Magewire property with the same name. When the input field is updated, the 'foo' property is updated in both AlpineJS and Magewire.

It's worth noting that the public property you want to sync must be declared in both Magewire (via a public property) and AlpineJS (via x-data).

### Accessing Component Array Properties
```php
class Explanation extends \Magewirephp\Magewire\Component
{
    public $foo = [
        0 => [    
            'foo' => 'bar',
        ],
        'nonNumericKey' => [
            0 => 'fooBar',
        ],
    ];
}
```

```html
<div>
    <div x-data>
        <!-- Output 'bar' -->
        <span x-text="$wire.foo[0].foo"></span>
        <span x-text="$wire.foo['0'].foo"></span>
        <span x-text="$wire.foo[0]['foo']"></span>
        
        <!-- Output 'fooBar' -->
        <span x-text="$wire.foo.nonNumericKey[0]"></span>
        <span x-text="$wire.foo.nonNumericKey['0']"><span>
        <span x-text="$wire.foo['nonNumericKey'][0]"></span>
    </div>
</div>
```
