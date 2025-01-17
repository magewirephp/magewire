# Magewire - Tools

## Magewire Developer Tools
Below is a collection of tools that work well together when developing with Magewire.

## Tools
- [Livewire Devtools](https://chromewebstore.google.com/detail/livewire-devtools/dnociedgpnpfnbkafoiilldfhpcjmikd) by [Beyond Code](https://beyondco.de/)
- [The VarDumper Component](https://symfony.com/doc/current/components/var_dumper.html) by [Symfony](https://symfony.com/)
- [Ray](https://spatie.be/docs/ray/v1/introduction) by [Spatie](https://spatie.be/)

## Livewire Devtools
To Install: [Visit and Add to Chrome](https://chrome.google.com/webstore/detail/livewire-devtools/ahcmcdmhdcgbpklkdhpejphjekpmhkll)

Chrome and Firefox DevTools extension for debugging Livewire applications.

The Livewire Devtools extension allows you to quickly inspect all available Livewire components and it's state on your Laravel sites. All changes that occurred in your Livewire component will be visible in the devtools as they happen.

## The VarDumper Component
The VarDumper component creates a global `dump()` && `dd()` function that you can use instead of e.g. `var_dump`

To Install: 
```composer require --dev symfony/var-dumper```

By using it, you'll gain:

- Configurable output formats: HTML or colored command line output.
- Ability to dump out large objects without the browser crashing. 
- Step inside of objects/arrays and look at the attributes within a component.
- [For a full list of features]('https://symfony.com/doc/current/components/var_dumper.html')

### Example Usage
```php
<?php

public function getUser($user): void
{
     // dump() returns the passed value, so you can dump an object and keep using it.
    dump($user);
    
    // dd() ("dump and die") helper function preventing execution after the following code.
    dd($user->getData());   
}
```

## Ray
> Please note that this is a paid for application.

Ray is a desktop app that vastly improves your debugging experience. It is a dedicated window to send debugging information to.

To Install:
```composer require --dev spatie/ray```

You should be able to use the `ray()` function without any other steps.

This package can be installed in any PHP application to send messages to the Ray app.

Ray is similar to `The VarDumper Component` listed above, but is an independent application that runs along side your Magewire project.
Allowing to dump data during requests. 

### Example Usage
```php
<?php

public function example($data): void
{
    // Simple string output
    ray('Welcome to Magewire!');
    
    // Output of Array, But also using the color function to display with a blue tag
    ray(['foo' => foo, 'bar' => bar])->color('blue');
    
    // Multiple argument Output
    ray('multiple', 'arguments', 'are', 'welcome');
    
    // Output a variable
    ray($data);
}
```




