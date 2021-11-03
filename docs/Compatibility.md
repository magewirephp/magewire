# Magewire - Compatibility
Magewire is **not** compatible with any specific Laravel Livewire backend version. The Livewire JS package does have a
matching version as this is a identical copy of it's origin. The goal is to make as much practible front- & backend
features compatible.

## Magento - Backend
Currently Magewire can only be used on the frontend of your shop. Backend compatibility will be added in the future.

## Magento - 2.3.x
> **Important**: Version 2.0.0 will only be compatible with Magento 2.4.x.

As it's core, Magewire is build primarily for Magento 2.4.x to get rid of the deprated App\Action controller extend.
I've gave it a headspin to still be able to use Magewire in Magento 2.3.x. I call it the "vintage" concept of handling
HTTP subsequent requests. Therefore 2.3.x version will make Magewire HTTP requests to the ```Vintage``` instead of the
default ```Post``` controller. Just install the extension, no extra configuration required.

```
composer require magewirephp/magewire
```

## Magento - RequireJS
Magewire by default is meant as a Hyva Themes first extension. This means it will only work on Hyva based themes out of
the box and **will not** work on e.g. Luma or Blank based Magento 2 themes (RequireJS dependend themes).

Because most developers want to work with a more modern and fun tech-stack doesn't mean we forgot all those die-hards
who still work with the original Magento frontend. Magewire is made compatible via a so-called compatibility
extension.

Simply install the original Magewire extension and require the compatibility extension alongside. You should be good to
go after you're done with your default workflow when installed a new extension.

```
composer require magewirephp/magewire-requirejs
```

## Magento - Custom XHR requests
You can walk into a situations where you load HTML via a custom XHR request where the layout gets loaded, the block HTML
gets rendered and returned with a child block _(A)_ inside of it. When this child _(B)_ block is wired, it will use the
'magewire_post_livewire' handle for it's subsequent requests. Therefore it's required to define the child (B) block
inside the 'hyva_default.xml' (Hyvä Themes theme) or 'default.xml' (Luma/Blank theme) layout.
