# Magewire PHP
[![Latest Stable Version](https://img.shields.io/packagist/v/magewirephp/magewire)](https://packagist.org/packages/magewirephp/magewire)
[![Downloads](https://img.shields.io/packagist/dm/magewirephp/magewire)](https://packagist.org/packages/magewirephp/magewire)
![Style CI](https://github.styleci.io/repos/414967404/shield?style=flat&branch=main)
[![License](https://img.shields.io/packagist/l/magewirephp/magewire)](https://packagist.org/packages/magewirephp/magewire)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/wpoortman)](https://github.com/sponsors/wpoortman)
![GitHub contributors](https://img.shields.io/github/contributors/magewirephp/magewire)

Looking for an easy way to build modern, reactive, and dynamic interfaces in Magento 2? Meet Magewire - the
[Laravel Livewire](https://laravel-livewire.com/) port for Magento 2. With Magewire, you can create engaging user
experiences without leaving the comfort of Magento's core layout and template systems. Add dynamic and reactive
features to your store without a full Javascript framework. Try Magewire today and take your store to the next level!

#### Join the official Discord server
[![Discord](https://dcbadge.vercel.app/api/server/RM5nnK5wxj)](https://discord.gg/RM5nnK5wxj)

## Updates
I've decided to move away from X (formerly Twitter). Going forward, all updates will be shared exclusively on Discord while I search for a platform that is both popular among developers and suitable for sharing technical updates.

#### Magewire V3
After over a year of working on Magewire V3, the release is finally approaching. While there are still some key technical decisions to be made, once they’re resolved, nothing will stand in the way of the first alpha release.

I've been sharing more in-depth videos on my [personal YouTube channel](https://www.youtube.com/@wpoortman) and invite you to check them out if you're interested.

In the meantime, I’m looking to expand the V3 team with passionate contributors willing to invest their time and become core contributors. There are opportunities at all levels—from writing tests and documentation to ensuring feature compatibility.

If you're interested, feel free to reach out to me on Discord or LinkedIn.

## Installation
```
composer require magewirephp/magewire
```

Please be aware that this extension is primarily build for themes running without Require JS like Hyvä Themes. It will
not work with RequireJS-dependent Magento theme out of the box. Don't worry – with a few simple tweaks, you can unleash
most of the richness in your Blank or Luma projects. Head over to the [compatibility](./docs/Compatibility.md) section
to learn more and start making your Magento 2 store more dynamic and engaging today!

## Documentation
- [Work with Alpine.js](./docs/Alpine.md)
- [Content Security Policy](./docs/ContentSecurityPolicy.md)
- [Features & Examples](./docs/Features.md)
- [More about Compatibility](./docs/Compatibility.md)
- [More about Components](./docs/Component.md)
- [More about Hydrators](./docs/Hydrators.md)
- [Developer Tools](./docs/Tools.md)

## Sponsors
Based on proven results, using Magewire consistently reduces work hours, offering significant benefits to agencies,
their developers and merchants. I extend my heartfelt appreciation to my current sponsors for their support creating
a win-win situation for all involved.

|   |   |
|---|---|
|<a align="center" href="https://vendic.nl/" title="Vendic" target="_blank"><img width="64" alt="Vendic" src="https://user-images.githubusercontent.com/5383956/228823594-d3344d87-dadc-4c36-a212-89cba8c7340b.jpg"/></a>|<a align="center" href="https://www.zero1.co.uk/" title="Zero 1" target="_blank"><img width="64" alt="Zero 1" src="https://github.com/magewirephp/magewire/assets/5383956/6f385d3c-87c9-433d-8921-c40de0f00573"/></a>|


Click [here](https://github.com/sponsors/wpoortman) to start sponsoring.

## Developers say
- **Marcus Venghaus — Freelance Developer** — This is a game-changing tool! You can build practically anything with almost no javascript. It saves a significant
amount of time and is incredibly easy to work with. It's not a matter of whether you should use it, you simply must
use it.

- **Vinai Kopp — Technical Director** — Magewire is like magic sauce that can save a ton of time implementing features! It’s great to see more tools from the
Laravel ecosystem being made available within Magento

- **Jesse de Boer — Frontend Developer** — Magewire gives you the ability to add reactivity without the use of a a bloated JS framework or package. Useing Magewire
is intuitive, quick and easy to learn. It feels like a breath of fresh air to work with and takes away a lot headaches
compared to using Luma and RequireJS.

- **Kiel Pykett — Technical Lead** — Magewire is one of my favourite tools! It even makes frontend morons like me capable of dynamic UIs! More people should
check it out. 

## More
Creating layers of abstraction and tools to simplify complex situations for other developers is a major source of motivation for me.
It's one of the reasons I love what I do. Each of my code repositories aims to achieve this. Take a look at my other repositories - they might be helpful for you.

- [Magehook](https://github.com/wpoortman/magehook)

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

_This Magewire port would not have been
possible without the the existence of the [Laravel Livewire](https://laravel-livewire.com/) project, it's creator [Caleb Porzio](https://github.com/calebporzio) and all contributors
with their tireless efforts and dedication. A big shoutout and thank you to all of them for the inspiration and
motivation to make this work in- and outside the Laravel ecosystem! :heart: :heart:_
