# Magewire PHP

[![Discord](https://dcbadge.vercel.app/api/server/RM5nnK5wxj)](https://discord.gg/RM5nnK5wxj)

[![Latest Stable Version](http://poser.pugx.org/magewirephp/magewire/v)](https://packagist.org/packages/magewirephp/magewire)
[![Total Downloads](http://poser.pugx.org/magewirephp/magewire/downloads)](https://packagist.org/packages/magewirephp/magewire)
[![style CI](https://github.styleci.io/repos/414967404/shield?style=flat&branch=main)
[![License](http://poser.pugx.org/magewirephp/magewire/license)](https://packagist.org/packages/magewirephp/magewire)

MagewirePHP brings the power of reactive, server-driven UI development to Magento 2—without writing JavaScript.
Inspired by Laravel Livewire, MagewirePHP lets you build dynamic, interactive frontend components using only PHP,
fully integrated with Magento’s architecture.

Whether you're creating real-time search, dynamic product forms, or interactive checkout steps, MagewirePHP enables a clean,
component-based approach that stays true to Magento’s conventions while simplifying complex frontend behavior.

- ✅ Write less JavaScript
- ✅ Maintain component logic in PHP
- ✅ Ideal for dynamic UIs like filters, modals, and configurators

MagewirePHP helps you deliver modern UX experiences in Magento—faster, cleaner, and with less frontend overhead.

## Documentation

- [Gettings Started](https://magewirephp.github.io/magewire-docs/index.html)
- [Essentials](https://magewirephp.github.io/magewire-docs/pages/essentials/components.html)
- [Features](https://magewirephp.github.io/magewire-docs/pages/features/alpine.html)
- [Directives](https://magewirephp.github.io/magewire-docs/pages/html-directives/wire-click.html)
- [Concepts](https://magewirephp.github.io/magewire-docs/pages/concepts/morphing.html)
- [Advanced](https://magewirephp.github.io/magewire-docs/pages/advanced/troubleshooting.html)

## Installation

   ```shell
   composer config repositories.magewirephp/magewire-three git git@github.com:magewirephp/magewire-three.git
   ```

To install Magewire in your Magento 2 project, follow these steps:

1. Require Magewire via Composer:
   ```shell
   composer require magewirephp/magewire
   ```
2. Enable the module:
   ```shell
   bin/magento module:enable Magewirephp_Magewire
   ```
3. Enable the theme compatibility module (determined per theme, in this case Hyvä):
   ```shell
   bin/magento module:enable Magewirephp_MagewireCompatibilityWithHyva
   ```
4. Run the setup upgrade command:
   ```shell
   bin/magento setup:upgrade
   ```
5. Deploy static content (when in production mode):
   ```shell
   bin/magento setup:static-content:deploy
   ```
6. Flush the cache:
   ```shell
   bin/magento cache:flush
   ```

## Sponsors

|   |   |   |
|---|---|---|
|<a align="center" href="https://github.com/ootri/" title="ootri" target="_blank"><img width="64" alt="ootri" src="https://avatars.githubusercontent.com/u/3450878?v=4"/></a>|<a align="center" href="https://vendic.nl/" title="Vendic" target="_blank"><img width="64" alt="Vendic" src="https://user-images.githubusercontent.com/5383956/228823594-d3344d87-dadc-4c36-a212-89cba8c7340b.jpg"/></a>|<a align="center" href="https://www.zero1.co.uk/" title="Zero 1" target="_blank"><img width="64" alt="Zero 1" src="https://github.com/magewirephp/magewire/assets/5383956/6f385d3c-87c9-433d-8921-c40de0f00573"/></a>|

Click [here](https://github.com/sponsors/wpoortman) to start sponsoring.

## Contributing
Thank you for considering contributing to Magewire! Please read the [contribution guide](https://github.com/magewirephp/magewire/blob/main/CONTRIBUTING.md) to know how to behave, install and use Magewire for contributors.

## Code of Conduct
In order to ensure that the Magewire is welcoming to all, please review and abide by the [Code of Conduct](https://github.com/magewirephp/magewire/blob/main/CODE_OF_CONDUCT.md).

## Security Vulnerabilities
If you discover a security vulnerability within Magewire, please create a
[merge request](https://github.com/magewirephp/magewire/pulls) or an
[discussion](https://github.com/magewirephp/magewire/discussions). All security vulnerabilities will be promptly
addressed.

## License
Copyright © [Willem Poortman](https://github.com/wpoortman)

Magewire is open-sourced software licensed under the [MIT license](LICENSE.md).

> It's important to emphasize that this package is completely independent of any business entities. There is absolutely
> no involvement or interference from other companies expressing their preferences. This package is created by the
> community, for the community, ensuring its integrity and unbiased nature.
