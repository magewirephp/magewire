# Changelog - Magewire

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [3.3.0](https://github.com/magewirephp/magewire/compare/3.2.0...3.3.0) (2026-07-02)


### Features

* Add magewire:compile:clear CLI command ([ac3a815](https://github.com/magewirephp/magewire/commit/ac3a8156c46610c1e5d685c06d15a01ae936b2a1))
* added the developer view fragment modifier ([bb54d86](https://github.com/magewirephp/magewire/commit/bb54d8667f46c7bc6747d97255f0a1f8d71f64ba))
* Blade-like echo compilers ([4637734](https://github.com/magewirephp/magewire/commit/46377348986f61af3a8576b95e0cf07fa36c2ec0))
* Compiled views resource path including area and theme ([4ce303a](https://github.com/magewirephp/magewire/commit/4ce303adc26e68fa57ceb07575b69f4689cf76c0))
* DOM, Loader and Str utilities ([f603e07](https://github.com/magewirephp/magewire/commit/f603e07df7b6d2f3adf527e5b748e9248b7990e5))
* Include Tailwindcss (minimum) ([a17b327](https://github.com/magewirephp/magewire/commit/a17b3273e417ecea32cc7c3efcfabc72c0edb66a))
* Magewire compiler resource path from generated to var ([44e4361](https://github.com/magewirephp/magewire/commit/44e4361a39ed33294691639faaa1ec62915d3ed9))
* notification option to keep it visible for a day when duration is set to 0 or false ([1a54a7f](https://github.com/magewirephp/magewire/commit/1a54a7f06cb0e3e9fc1e86066c8472a7420de9b6))
* Notifier refactor ([81ba4d2](https://github.com/magewirephp/magewire/commit/81ba4d2a6cda59d2194c2ccdd4704abdc109d042))
* Rate limiting dev-mode disabled ([e41ad96](https://github.com/magewirephp/magewire/commit/e41ad96579ed4561601a9051f302fc08016d60a0))
* Removed all theme compatibility modules from this package ([d1da05d](https://github.com/magewirephp/magewire/commit/d1da05de9060116608b980516938d5d241e9759a))


### Bug Fixes

* adding symfony/http-foundation require ([#98](https://github.com/magewirephp/magewire/issues/98)) ([dbbbdf3](https://github.com/magewirephp/magewire/commit/dbbbdf3d3c60b05d376d6510f56973c8bbc5c329))
* catch any serialisation related error and return/response as json ([#157](https://github.com/magewirephp/magewire/issues/157)) ([dddbd1d](https://github.com/magewirephp/magewire/commit/dddbd1dc9bfc96228cf1ee8a0630d2f07d15e8bd))
* complies check continues, even if resolver was already found ([780a3e3](https://github.com/magewirephp/magewire/commit/780a3e3a586f866eceb1deb28a5a45e703ab785d))
* Correct v1 emit() param spreading and implement empty emit methods ([5c1dcbc](https://github.com/magewirephp/magewire/commit/5c1dcbca3464bb03acd85ceabd2e49a0f8823776))
* Correct v1 emit() param spreading and implement empty emit methods (2/2) ([318c02d](https://github.com/magewirephp/magewire/commit/318c02d0828b0a0d080c6278bd7caf080810fc78))
* HTML fragment attribute render restored ([6997511](https://github.com/magewirephp/magewire/commit/69975117e218515a757a2b8de69bf476a22010a2))
* Map config('app.debug') to the real Magewire debug toggle ([3dde770](https://github.com/magewirephp/magewire/commit/3dde770c586342b199d4539a2990d2e6c4de97fd))
* type annotations for dispatchBrowserEvent ([#41](https://github.com/magewirephp/magewire/issues/41)) ([afb5fe0](https://github.com/magewirephp/magewire/commit/afb5fe0d8ca70747e068490bf1a319e1e04db7de))


### Miscellaneous Chores

* Added missing wire attributes CSS ([19b5c7e](https://github.com/magewirephp/magewire/commit/19b5c7ef7ebf2fda7854fca3d932e347e958ffa6))
* Added updating argument swapping for backwards compatible components ([130252b](https://github.com/magewirephp/magewire/commit/130252b5923cfea72310c639620a458d03ac85a4))
* Backwards compatibility improvements (Hyvä) ([a00c1f3](https://github.com/magewirephp/magewire/commit/a00c1f3190b90f4b3efcd73a4be2c3b85d5ac638))
* Better magewire.legacy explanation (layout XML) ([ff4ad18](https://github.com/magewirephp/magewire/commit/ff4ad18cb0cb7164f1b164ba0fc62b56b2d4b3af))
* Code revert ([1b3f938](https://github.com/magewirephp/magewire/commit/1b3f93846951f6fcebbfdefefd724368312a8283))
* Compilation refactoring ([213403d](https://github.com/magewirephp/magewire/commit/213403d5a3a5cbdcadbeb0bf4fcd4c3471dd39d5))
* Component call wrapper bugfixes ([27c4009](https://github.com/magewirephp/magewire/commit/27c40093d671b68096c95e2f232e80f258177c0a))
* Component fragment property improvements ([2fbc52e](https://github.com/magewirephp/magewire/commit/2fbc52ed72705d05335eaa15b00956cfc28df352))
* Component resolver edge-case enhancements ([7c59ec1](https://github.com/magewirephp/magewire/commit/7c59ec18810c9948ecad714ff34dd09a20b0758c))
* **deps:** bump actions/checkout from 4 to 6 ([#200](https://github.com/magewirephp/magewire/issues/200)) ([0cecae5](https://github.com/magewirephp/magewire/commit/0cecae530aa22ad9d553e7c4808167935543fd2d))
* **deps:** bump actions/checkout from 6 to 7 ([#237](https://github.com/magewirephp/magewire/issues/237)) ([e9c40d2](https://github.com/magewirephp/magewire/commit/e9c40d2d2b71722c9e03db2441c84e1aa771a671))
* **deps:** bump stefanzweifel/git-auto-commit-action from 5 to 7 ([#194](https://github.com/magewirephp/magewire/issues/194)) ([0ecd5de](https://github.com/magewirephp/magewire/commit/0ecd5dec6abc26fa0402495ef4703bf84cb22166))
* Explanation above BC event dispatchment ([7bb7fdd](https://github.com/magewirephp/magewire/commit/7bb7fdd1b9eb2ab556e0a89d810e2a37f74dfdf3))
* fragment modifier output limitations ([42ccd03](https://github.com/magewirephp/magewire/commit/42ccd03ad58699dde8a5966e8ec7aefb1c57dd9b))
* Ignore .claude directory entirely ([e91b35c](https://github.com/magewirephp/magewire/commit/e91b35ca8da7fd31418da20313e5ddb846965eed))
* Include Developer fragment modifier (developer mode only) ([12158df](https://github.com/magewirephp/magewire/commit/12158df45341349ae8da2136db1d653261f4e1ac))
* JS-lib rebuild ([2a2b256](https://github.com/magewirephp/magewire/commit/2a2b2568b23c3c29b17e65d5af0271cdf8476dd3))
* Magewire priority block for early JS-code execution (after body starts) ([3cb2767](https://github.com/magewirephp/magewire/commit/3cb2767d39ca344c3a13df8bfafad34126b17945))
* Magewire resolver arguments data-object -&gt; data collection migration ([a8fc897](https://github.com/magewirephp/magewire/commit/a8fc8976fd8b715581412c7ebc38127a3b488c13))
* Mago fixes ([e86f977](https://github.com/magewirephp/magewire/commit/e86f977de538898dc236423dcf43c048369f8c34))
* Mago Github action ([5d7d33d](https://github.com/magewirephp/magewire/commit/5d7d33d6540591196a981b109d84444f5750fd3c))
* Mago improvements ([3d8af5f](https://github.com/magewirephp/magewire/commit/3d8af5fd98a74bce9d7f32dcf5f059b2cf22ea90))
* Mago run result badge for main ([df11411](https://github.com/magewirephp/magewire/commit/df1141101e6b208a920fc6d6cccf736d754d328b))
* Mago test action fixes ([64fc307](https://github.com/magewirephp/magewire/commit/64fc307589809c89474dafde7787a47af9b41295))
* **main:** Ignore version "v" in repo tags ([425c814](https://github.com/magewirephp/magewire/commit/425c814962f58e9049ba50c1cbcb1fdd945a5747))
* **main:** release 3.1.0 ([#216](https://github.com/magewirephp/magewire/issues/216)) ([c3e91c3](https://github.com/magewirephp/magewire/commit/c3e91c37ac9dd9d62ff624b1e61c1233fa00d29c))
* **main:** release 3.2.0 ([#233](https://github.com/magewirephp/magewire/issues/233)) ([845e3b5](https://github.com/magewirephp/magewire/commit/845e3b5db0a068bcfd971fed87de604273ca6f36))
* Minor resolver management improvements ([d5865de](https://github.com/magewirephp/magewire/commit/d5865deef0f3ef0a7d058f3c7094adffe40288a0))
* Modifiers array became readonly for HTML fragments ([dd4b027](https://github.com/magewirephp/magewire/commit/dd4b02722ae38db9bc2e0acfe4b1be45e928de6f))
* Rate limiting system config improvements ([a7b2d15](https://github.com/magewirephp/magewire/commit/a7b2d153d26dc2c088725fccae65a8fe9eba42b6))
* Removal of BC component check on magewireMakeComponentBackwardsCompatible method ([457f897](https://github.com/magewirephp/magewire/commit/457f8974e17f6cb40ae739d7c2c7167634144e40))
* Remove tests/Playwright/tests/.gitkeep ([005a840](https://github.com/magewirephp/magewire/commit/005a84065d54c0badc413ae7e751b2ac1647329a))
* Removed -o on sh command for Mago Github action ([fd484e9](https://github.com/magewirephp/magewire/commit/fd484e922385e8fc2ffe8372b068512cecb5d151))
* Removed backup file from Mago config ([a6b5fc6](https://github.com/magewirephp/magewire/commit/a6b5fc69a9bd056323ad4ea8687bc4e00faee967))
* Removed Mago config entity and removed installation of Magento from Mago Github action ([62b8790](https://github.com/magewirephp/magewire/commit/62b87908bf2a358e7f22cc0a09a2884495037be7))
* Removing the Flake compiler backup (deprecated) ([fd4af23](https://github.com/magewirephp/magewire/commit/fd4af239768fb67a989e5288f64c705ce26d563f))
* Replaced not-found-exception with replacement ([ac17515](https://github.com/magewirephp/magewire/commit/ac1751500a76401d5837bb0c1f0786a9a9275d2b))
* Runtime lifecycle improvements ([085dea6](https://github.com/magewirephp/magewire/commit/085dea6791f360e35420bce77056c060e5df6598))
* Setup addition for Data Collections ([c81e4d5](https://github.com/magewirephp/magewire/commit/c81e4d5e9aa416f89ca516052b2a0b34af22f369))
* Support class improvements ([54ef2fb](https://github.com/magewirephp/magewire/commit/54ef2fb358023a57c4e0fa3c82c576fb0d3daa90))
* Typo ([f415ccc](https://github.com/magewirephp/magewire/commit/f415ccc621ddf15f33b8d7c8faac8b51f50649c1))
* Untrack portman/lib and ignore it ([ae0979c](https://github.com/magewirephp/magewire/commit/ae0979cf6639e6373230a31d69e062b554e52716))
* Upgrade docs URL to docs.magewirephp.nl ([#234](https://github.com/magewirephp/magewire/issues/234)) ([6e6f49a](https://github.com/magewirephp/magewire/commit/6e6f49adce0f0ba1357100acd766e549ff43d6fa))
* view fragment modifier architectural improvements ([29df80b](https://github.com/magewirephp/magewire/commit/29df80b2cdd3b497639da48d3ba47a581631b8c1))

## [3.2.0](https://github.com/magewirephp/magewire/compare/3.1.0...3.2.0) (2026-06-14)


### Features

* Magewire compiler resource path from generated to var ([812f6ae](https://github.com/magewirephp/magewire/commit/812f6ae6ee1d7628b546a17f5aa197b5d4fdce18))
* Rate limiting dev-mode disabled ([502706a](https://github.com/magewirephp/magewire/commit/502706afe932781640e4cc4a1c9d3761c8ff07f8))
* Removed all theme compatibility modules from this package ([118d518](https://github.com/magewirephp/magewire/commit/118d518342f26463ed374ad9160a7ce56e26cbb7))


### Bug Fixes

* Correct v1 emit() param spreading and implement empty emit methods ([0eadd6c](https://github.com/magewirephp/magewire/commit/0eadd6c98e81b7e6ce8c558a05096a855caeb4ce))
* Correct v1 emit() param spreading and implement empty emit methods (2/2) ([10c00fc](https://github.com/magewirephp/magewire/commit/10c00fc5bf576b1850f625142de535781c237e06))


### Miscellaneous Chores

* Added updating argument swapping for backwards compatible components ([2377627](https://github.com/magewirephp/magewire/commit/23776279a09be38f14b111394a32f7eced063ff4))
* Rate limiting system config improvements ([0f52472](https://github.com/magewirephp/magewire/commit/0f524725be7f8f1274ee3bf33d33ec90ee553f3b))
* Upgrade docs URL to docs.magewirephp.nl ([#234](https://github.com/magewirephp/magewire/issues/234)) ([9bf650b](https://github.com/magewirephp/magewire/commit/9bf650b29bfb6023277db1db86e7c96755ee8777))

## [3.1.0](https://github.com/magewirephp/magewire/compare/3.0.0...v3.1.0) (2026-06-02)


### Features

* Blade-like echo compilers ([364f5d2](https://github.com/magewirephp/magewire/commit/364f5d28bfefefcd12366ed17991d04652b9442a))
* Compiled views resource path including area and theme ([5d322d7](https://github.com/magewirephp/magewire/commit/5d322d73a9a6076592d8aea30a54695ef5223a1c))


### Bug Fixes

* complies check continues, even if resolver was already found ([069be4f](https://github.com/magewirephp/magewire/commit/069be4fd89e1e9711ddbbdf20a3a519fab99a311))
* HTML fragment attribute render restored ([81c8e9d](https://github.com/magewirephp/magewire/commit/81c8e9dd7d8c0f86f31043e6b262c4896c343141))


### Miscellaneous Chores

* Added missing wire attributes CSS ([3b2d704](https://github.com/magewirephp/magewire/commit/3b2d7042b57452205c77290f52d78f76277e42fb))
* Backwards compatibility improvements (Hyvä) ([b6bf1aa](https://github.com/magewirephp/magewire/commit/b6bf1aae472781548719446f2e747a839ce4cc7c))
* Code revert ([cf4acfa](https://github.com/magewirephp/magewire/commit/cf4acfa34b30fa0c9ac23eb9ce0eb228f7e5f415))
* Compilation refactoring ([6be4fd7](https://github.com/magewirephp/magewire/commit/6be4fd789139e5f071216b1d587bf78489c67249))
* Component call wrapper bugfixes ([6cd2707](https://github.com/magewirephp/magewire/commit/6cd2707dfcee85b415cfb412b0259164ad70689c))
* Component fragment property improvements ([a821297](https://github.com/magewirephp/magewire/commit/a821297fc7144de1450a1cc03c1e4648890aa33c))
* Component resolver edge-case enhancements ([3c4d8cd](https://github.com/magewirephp/magewire/commit/3c4d8cdf2b3626547c5c8d25047a78b30120d0d6))
* Explanation above BC event dispatchment ([a8aba93](https://github.com/magewirephp/magewire/commit/a8aba93e9bd6c30efcf023889871eded836cc0da))
* JS-lib rebuild ([69fc50b](https://github.com/magewirephp/magewire/commit/69fc50bad99925f3918f28d3fa7154f2e8362d3a))
* Magewire priority block for early JS-code execution (after body starts) ([c60353e](https://github.com/magewirephp/magewire/commit/c60353e16c20fc3d2e43879f7096e4c15e702e6d))
* Magewire resolver arguments data-object -&gt; data collection migration ([81c0426](https://github.com/magewirephp/magewire/commit/81c0426252f57bc3732cfbbcad0572165ba1c104))
* Mago fixes ([037ae6b](https://github.com/magewirephp/magewire/commit/037ae6b5e77ac4415d982bdf5a09ed4b9db03ac4))
* Mago Github action ([645c9ef](https://github.com/magewirephp/magewire/commit/645c9efcae595a4f1e448a4dae337c8cc96ff92a))
* Mago improvements ([490df65](https://github.com/magewirephp/magewire/commit/490df65fa9d3964a09f95631d72103c4cf71ce4a))
* Mago run result badge for main ([d98c2dd](https://github.com/magewirephp/magewire/commit/d98c2ddd38a36f4eabf58f096df2b91be40bbcc6))
* Mago test action fixes ([7070a9e](https://github.com/magewirephp/magewire/commit/7070a9e02d551939eb17ab1f7f5afe4e4841b3bf))
* Minor resolver management improvements ([e6ea76b](https://github.com/magewirephp/magewire/commit/e6ea76b6421c2ba96b1cae802d2e1ed17837352a))
* Removal of BC component check on magewireMakeComponentBackwardsCompatible method ([36d6629](https://github.com/magewirephp/magewire/commit/36d6629f663a337512746b5fb8b1e3ed9d030c9b))
* Removed -o on sh command for Mago Github action ([fc45270](https://github.com/magewirephp/magewire/commit/fc45270cf64c7a3b17b82d95f2e31a01fad2c353))
* Removed backup file from Mago config ([eda215a](https://github.com/magewirephp/magewire/commit/eda215a1f81cfb8b3575aa4fa51eb6ae3360f9e2))
* Removed Mago config entity and removed installation of Magento from Mago Github action ([b335034](https://github.com/magewirephp/magewire/commit/b3350347cb05ef8e61e9778a1eccc3bce4f29f26))
* Removing the Flake compiler backup (deprecated) ([f53ff53](https://github.com/magewirephp/magewire/commit/f53ff53d7b494c24592b51b4c6ac37ca2bad3eaa))
* Replaced not-found-exception with replacement ([52dfa04](https://github.com/magewirephp/magewire/commit/52dfa048950a3e0b7a59f7f139c41b13c2a1dd5e))
* Runtime lifecycle improvements ([24dd03f](https://github.com/magewirephp/magewire/commit/24dd03fdd598180090619af23ddc7d48eb9d2f2a))
* Setup addition for Data Collections ([5b7b9e9](https://github.com/magewirephp/magewire/commit/5b7b9e902e8a44c467a16b932d4fba2eeee4f405))
* Support class improvements ([5a30555](https://github.com/magewirephp/magewire/commit/5a3055528a9dc920f21363dc3e6fc0d3429f5fc1))
* Typo ([60a4ef5](https://github.com/magewirephp/magewire/commit/60a4ef5d640daeaf5b7b6a2148152a9b0adaf4ab))

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
