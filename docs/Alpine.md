# Magewire - Alpine

## Magewire Component Interaction
From any Alpine component inside a Magewire component, you can access a magic `$wire` object to access and manipulate the
Magewire component.
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

> **Note**: `$wire` gives you access to the Magewire component properties using AlpineJS. To avoid misunderstandings, you can
> also use `wire:click="$set('foo', Math.random())"` to set the value of the `foo` property. This is just a
> demonstration of how `x-text` can be used in combination with Magewire.

## Syncing Magewire & AlpineJS property data
Syncing data between AlpineJS and Magewire is made easy with the `$wire.entangle()` method. This powerful method creates
a seamless two-way binding between an AlpineJS property and a Magewire property, ensuring that any changes made to one
property are automatically reflected in the other. This simplifies data synchronization and helps maintain consistency
between the two frameworks.

```php
class Explanation extends \Magewirephp\Magewire\Component
{
    public $foo = 'bar';
}
```

```html
<?php /** @var Explanation $magewire */ ?>
<div x-data="{ foo: $wire.entangle('foo') }">
  <input type="text" x-model="foo">
  <!-- The value of the `foo` property can be accessed using Alpine JS: -->
  <p x-text="foo"></p>
  <!-- Alternatively, it can also be accessed using Magewire: -->
  <?= $magewire->foo ?>
</div>
```

We bind an input field to an AlpineJS property called 'foo', and use `$wire.entangle` to bind it to a Magewire property with the same name. When the input field is updated, the 'foo' property is updated in both AlpineJS and Magewire.

> **Note**: The public property you want to sync must be declared in both Magewire (via a public property) and AlpineJS (via x-data).

## Accessing Component Array Properties
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
