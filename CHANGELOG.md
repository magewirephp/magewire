# Changelog - Magewire

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [3.0.1](https://github.com/magewirephp/magewire/compare/3.0.0...v3.0.1) (2026-05-17)


### Bug Fixes

* complies check continues, even if resolver was already found ([069be4f](https://github.com/magewirephp/magewire/commit/069be4fd89e1e9711ddbbdf20a3a519fab99a311))
* HTML fragment attribute render restored ([81c8e9d](https://github.com/magewirephp/magewire/commit/81c8e9dd7d8c0f86f31043e6b262c4896c343141))


### Miscellaneous Chores

* Compilation refactoring ([6be4fd7](https://github.com/magewirephp/magewire/commit/6be4fd789139e5f071216b1d587bf78489c67249))
* Component fragment property improvements ([a821297](https://github.com/magewirephp/magewire/commit/a821297fc7144de1450a1cc03c1e4648890aa33c))
* Explanation above BC event dispatchment ([a8aba93](https://github.com/magewirephp/magewire/commit/a8aba93e9bd6c30efcf023889871eded836cc0da))
* Magewire resolver arguments data-object -&gt; data collection migration ([81c0426](https://github.com/magewirephp/magewire/commit/81c0426252f57bc3732cfbbcad0572165ba1c104))
* Minor resolver management improvements ([e6ea76b](https://github.com/magewirephp/magewire/commit/e6ea76b6421c2ba96b1cae802d2e1ed17837352a))
* Removal of BC component check on magewireMakeComponentBackwardsCompatible method ([36d6629](https://github.com/magewirephp/magewire/commit/36d6629f663a337512746b5fb8b1e3ed9d030c9b))
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
