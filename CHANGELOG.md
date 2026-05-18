# Changelog - Magewire

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [3.1.0](https://github.com/magewirephp/magewire/compare/3.0.0...v3.1.0) (2026-05-18)


### Features

* Blade-like echo compilers ([364f5d2](https://github.com/magewirephp/magewire/commit/364f5d28bfefefcd12366ed17991d04652b9442a))
* Compiled views resource path including area and theme ([5d322d7](https://github.com/magewirephp/magewire/commit/5d322d73a9a6076592d8aea30a54695ef5223a1c))


### Bug Fixes

* complies check continues, even if resolver was already found ([069be4f](https://github.com/magewirephp/magewire/commit/069be4fd89e1e9711ddbbdf20a3a519fab99a311))
* HTML fragment attribute render restored ([81c8e9d](https://github.com/magewirephp/magewire/commit/81c8e9dd7d8c0f86f31043e6b262c4896c343141))


### Miscellaneous Chores

* Code revert ([cf4acfa](https://github.com/magewirephp/magewire/commit/cf4acfa34b30fa0c9ac23eb9ce0eb228f7e5f415))
* Compilation refactoring ([6be4fd7](https://github.com/magewirephp/magewire/commit/6be4fd789139e5f071216b1d587bf78489c67249))
* Component fragment property improvements ([a821297](https://github.com/magewirephp/magewire/commit/a821297fc7144de1450a1cc03c1e4648890aa33c))
* Explanation above BC event dispatchment ([a8aba93](https://github.com/magewirephp/magewire/commit/a8aba93e9bd6c30efcf023889871eded836cc0da))
* Magewire resolver arguments data-object -&gt; data collection migration ([81c0426](https://github.com/magewirephp/magewire/commit/81c0426252f57bc3732cfbbcad0572165ba1c104))
* Mago fixes ([037ae6b](https://github.com/magewirephp/magewire/commit/037ae6b5e77ac4415d982bdf5a09ed4b9db03ac4))
* Mago Github action ([645c9ef](https://github.com/magewirephp/magewire/commit/645c9efcae595a4f1e448a4dae337c8cc96ff92a))
* Mago improvements ([490df65](https://github.com/magewirephp/magewire/commit/490df65fa9d3964a09f95631d72103c4cf71ce4a))
* Mago run result badge for main ([d98c2dd](https://github.com/magewirephp/magewire/commit/d98c2ddd38a36f4eabf58f096df2b91be40bbcc6))
* Mago test action fixes ([7070a9e](https://github.com/magewirephp/magewire/commit/7070a9e02d551939eb17ab1f7f5afe4e4841b3bf))
* Minor resolver management improvements ([e6ea76b](https://github.com/magewirephp/magewire/commit/e6ea76b6421c2ba96b1cae802d2e1ed17837352a))
* Removal of BC component check on magewireMakeComponentBackwardsCompatible method ([36d6629](https://github.com/magewirephp/magewire/commit/36d6629f663a337512746b5fb8b1e3ed9d030c9b))
* Removed -o on sh command for Mago Github action ([fc45270](https://github.com/magewirephp/magewire/commit/fc45270cf64c7a3b17b82d95f2e31a01fad2c353))
* Removed Mago config entity and removed installation of Magento from Mago Github action ([b335034](https://github.com/magewirephp/magewire/commit/b3350347cb05ef8e61e9778a1eccc3bce4f29f26))
* Removing the Flake compiler backup (deprecated) ([f53ff53](https://github.com/magewirephp/magewire/commit/f53ff53d7b494c24592b51b4c6ac37ca2bad3eaa))
* Replaced not-found-exception with replacement ([52dfa04](https://github.com/magewirephp/magewire/commit/52dfa048950a3e0b7a59f7f139c41b13c2a1dd5e))
* Setup addition for Data Collections ([5b7b9e9](https://github.com/magewirephp/magewire/commit/5b7b9e902e8a44c467a16b932d4fba2eeee4f405))
* Support class improvements ([5a30555](https://github.com/magewirephp/magewire/commit/5a3055528a9dc920f21363dc3e6fc0d3429f5fc1))

## [Unreleased]

[Unreleased]: https://github.com/magewirephp/magewire/compare/3.0.0...main

## [3.0.0] - 2026-04-23

[3.0.0]: https://github.com/magewirephp/magewire/compare/1.13.3...3.0.0

Magewire 3.0.0 is a full rewrite that ports the Laravel Livewire v3 core into Magento 2,
replaces the hand-written v1 runtime with a formalised Mechanisms and Features pipeline,
and introduces a template compiler, a snapshot-based state flow,
and a first-class backwards compatibility layer for v1 components.

Upgrading from 1.x? See [UPGRADING.md](UPGRADING.md).
Full reference in the [docs](https://magewirephp.github.io/magewire-docs/).

### Added

- Nothing added (new baseline).

### Changed

- Nothing changed (new baseline).

### Removed

- Nothing removed (new baseline).

## [3.0.0-beta1] - 2025-06-02


### Added

- Nothing added.

### Changed

- Now based on Livewire V3.

### Removed

- Support for all PHP version below 8.2.
