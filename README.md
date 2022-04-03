# Magewire
[![Latest Stable Version](http://poser.pugx.org/magewirephp/magewire/v)](https://packagist.org/packages/magewirephp/magewire)
[![Total Downloads](http://poser.pugx.org/magewirephp/magewire/downloads)](https://packagist.org/packages/magewirephp/magewire)
![style CI](https://github.styleci.io/repos/414967404/shield?style=flat&branch=main)
![hyva](https://img.shields.io/badge/Hyva_Themes-Compatible-1abc9c)
[![License](http://poser.pugx.org/magewirephp/magewire/license)](https://packagist.org/packages/magewirephp/magewire)

Magewire is a [Laravel Livewire](https://laravel-livewire.com/) port for Magento 2. The goal is to make it fun and easy
to build modern, reactive and dynamic interfaces, without leaving the comfort of Magento's core layout and template
systems. Magewire can be the missing piece when you intend to build dynamic and reactive features, but don't require or
feel comfortable working with a full JavaScript framework like Vue or React.

## Installation
```
composer require magewirephp/magewire
```
Magewire is a [Hyva Themes](https://hyva.io/) first Magento 2 extension and won't work on a RequireJS dependent
Magento theme out of the box. Go and check out the [compatibility](./docs/Compatibility.md#magewire---compatibility)
section to enable all the Magewire richness in your Blank or Luma projects.

## Documentation
- [Work with Alpine.js](./docs/Alpine.md)
- [Features & Examples](./docs/Features.md)
- [More about Compatibility](./docs/Compatibility.md)
- [More about Components](./docs/Component.md)
- [More about Hydrators](./docs/Hydrators.md)
- [Developer Tools](./docs/Tools.md)

## Roadmap
- Unit & Integration tests
- Single and Multi file upload integrations
- Throttling capabilities
- Wireable Interface concept for public object properties like e.g. DataObject
- Parent / Children system for Emit Up compatibility
- Enrich pagination functionality (long term)
- Advanced Query String manipulation (long term)

## Security Vulnerabilities
If you discover a security vulnerability within Magewire, please create a PR or send an e-mail to Willem Poortman via
[magewirephp@wpoortman.nl](mailto:magewirephp@wpoortman.nl). All security vulnerabilities will be promptly addressed.

## License
Copyright © [Willem Poortman](https://github.com/wpoortman)

Magewire is open-sourced software licensed under the [MIT license](LICENSE.md).

> This Magewire port would not have been
possible without the the existence of the [Laravel Livewire](https://laravel-livewire.com/) project, it's creator [Caleb Porzio](https://github.com/calebporzio) and all contributors
with their tireless efforts and dedication. A big shoutout and thank you to all of them for the inspiration and
motivation to make this work in- and outside the Laravel ecosystem! :heart: :heart:
