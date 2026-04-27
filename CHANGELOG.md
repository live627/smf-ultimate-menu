# Changelog

## [5.0.0](https://github.com/live627/smf-ultimate-menu/compare/v4.1.1...v5.0.0) (2026-04-27)


### ⚠ BREAKING CHANGES

* Require SMF 2.1 and PHP 7.4

### Features

* Add page title when editing a button ([b50ee74](https://github.com/live627/smf-ultimate-menu/commit/b50ee747e1c440ff88126641f80dd2e94c558a9a))
* Check session when editing a button ([3eb69de](https://github.com/live627/smf-ultimate-menu/commit/3eb69debe07829242d9ef6bc6b23c423b31c6970))


### Bug Fixes

* Avoid fatal errors if no groups were selected ([104e2d9](https://github.com/live627/smf-ultimate-menu/commit/104e2d95d613a10f2fe733bf0c810f7d93977115))
* code to check integration was backwards ([695a246](https://github.com/live627/smf-ultimate-menu/commit/695a24605d8de56436ac9596149cf4e558c1db3c))
* Do not load menu twice when reordering menu hooks ([#25](https://github.com/live627/smf-ultimate-menu/issues/25)) ([95db0ab](https://github.com/live627/smf-ultimate-menu/commit/95db0ab592682950d5dd792162a2ab50e00b49ae))
* Each individual button has its own cache entry ([49f39e1](https://github.com/live627/smf-ultimate-menu/commit/49f39e195db30e3ab573b8a1bed412d0c14ab68c))
* End usage of the deprecated function `create_function()` ([fc2135b](https://github.com/live627/smf-ultimate-menu/commit/fc2135be33688b96d42c3a540066bd4d87a0c9c0))
* Error upon installing if no buttons exist ([06dfef4](https://github.com/live627/smf-ultimate-menu/commit/06dfef4530cc2f268073e74e26bea37573b4e989))
* Fill the radio button with default value when creating new button ([f92da20](https://github.com/live627/smf-ultimate-menu/commit/f92da20518583d762fcd3070fcc5ebb959758462))
* Redirect ([6e5d0a7](https://github.com/live627/smf-ultimate-menu/commit/6e5d0a7f3aa66fca8e6636efc2c3f39c096bcded))
* Remove PHP 7 code that should wait for a future version ([2002cd0](https://github.com/live627/smf-ultimate-menu/commit/2002cd0eeb70992c197be7425dc5009aaa4a9c82))
* Show button position even if it's nested too deep for SMF's menu ([ae9c87e](https://github.com/live627/smf-ultimate-menu/commit/ae9c87e95ebd3cad6eb4d87813070e97d436d410))
* Show hidden buttons in the positions list (register et al) ([a9671bd](https://github.com/live627/smf-ultimate-menu/commit/a9671bdd32749a68d6817ea89fda43e7f040ced5))
* Work around a bug in SMF preventing installing this sometimes ([15bc58d](https://github.com/live627/smf-ultimate-menu/commit/15bc58da80d118b8057f177dfdf57b854084e2c3))


### Miscellaneous Chores

* release 1.0.4 ([5ffdb6c](https://github.com/live627/smf-ultimate-menu/commit/5ffdb6c1c981abc480aa79704a94bfcc8b5b1e76))
* release 1.0.4 ([af9ab49](https://github.com/live627/smf-ultimate-menu/commit/af9ab497eab47c2282364e9700f4a754e09dc049))
* release 1.0.5 ([#6](https://github.com/live627/smf-ultimate-menu/issues/6)) ([760c106](https://github.com/live627/smf-ultimate-menu/commit/760c1061eab71bee99ad7a59eadcc6d25737eea7))
* release 1.1.0 ([#7](https://github.com/live627/smf-ultimate-menu/issues/7)) ([865d6ef](https://github.com/live627/smf-ultimate-menu/commit/865d6efb151e77cea1e1dc368e9a83c97095f832))
* release 1.1.1 ([#8](https://github.com/live627/smf-ultimate-menu/issues/8)) ([5c48533](https://github.com/live627/smf-ultimate-menu/commit/5c485337d3022aed68714212a7ed2e88a20a73ea))
* release 1.1.2 ([#9](https://github.com/live627/smf-ultimate-menu/issues/9)) ([25c35d3](https://github.com/live627/smf-ultimate-menu/commit/25c35d3d28ce11d2d0217446cde94d84936cd0ab))
* release 1.1.3 ([#10](https://github.com/live627/smf-ultimate-menu/issues/10)) ([f38e0dc](https://github.com/live627/smf-ultimate-menu/commit/f38e0dc61aecf672400b6fca49cf66eca48aa39d))
* release 1.1.4 ([#11](https://github.com/live627/smf-ultimate-menu/issues/11)) ([14f94c1](https://github.com/live627/smf-ultimate-menu/commit/14f94c186c3a1afeb375733cd41de9008dbf7760))
* release 2.0.0 ([#12](https://github.com/live627/smf-ultimate-menu/issues/12)) ([393ba66](https://github.com/live627/smf-ultimate-menu/commit/393ba66ec541605814795030aabfc50648be2ba0))
* release 2.0.1 ([#13](https://github.com/live627/smf-ultimate-menu/issues/13)) ([6681aef](https://github.com/live627/smf-ultimate-menu/commit/6681aef9c399847bafeaa249fc088237cf3f42f4))
* release 2.0.2 ([#14](https://github.com/live627/smf-ultimate-menu/issues/14)) ([f25fdb8](https://github.com/live627/smf-ultimate-menu/commit/f25fdb82ea644829af7c4a0f72e2a3dbb166a039))


### Code Refactoring

* Require SMF 2.1 and PHP 7.4 ([a7a6974](https://github.com/live627/smf-ultimate-menu/commit/a7a69746494603b3a76842ae15e401812e67a93f))

### [2.0.2](https://www.github.com/live627/smf-ultimate-menu/compare/v2.0.1...v2.0.2) (2022-03-20)


### Bug Fixes

* Work around a bug in SMF preventing installing this sometimes ([15bc58d](https://www.github.com/live627/smf-ultimate-menu/commit/15bc58da80d118b8057f177dfdf57b854084e2c3))

### [2.0.1](https://www.github.com/live627/smf-ultimate-menu/compare/v2.0.0...v2.0.1) (2021-10-16)


### Bug Fixes

* Error upon installing if no buttons exist ([06dfef4](https://www.github.com/live627/smf-ultimate-menu/commit/06dfef4530cc2f268073e74e26bea37573b4e989))

## [2.0.0](https://www.github.com/live627/smf-ultimate-menu/compare/v1.1.4...v2.0.0) (2021-10-11)


### ⚠ BREAKING CHANGES

* Require SMF 2.1 and PHP 7.4

### Code Refactoring

* Require SMF 2.1 and PHP 7.4 ([a7a6974](https://www.github.com/live627/smf-ultimate-menu/commit/a7a69746494603b3a76842ae15e401812e67a93f))

### [1.1.4](https://www.github.com/live627/smf-ultimate-menu/compare/v1.1.3...v1.1.4) (2021-10-11)


### Bug Fixes

* Avoid fatal errors if no groups were selected ([104e2d9](https://www.github.com/live627/smf-ultimate-menu/commit/104e2d95d613a10f2fe733bf0c810f7d93977115))

### [1.1.3](https://www.github.com/live627/smf-ultimate-menu/compare/v1.1.2...v1.1.3) (2021-10-10)


### Bug Fixes

* Redirect ([6e5d0a7](https://www.github.com/live627/smf-ultimate-menu/commit/6e5d0a7f3aa66fca8e6636efc2c3f39c096bcded))

### [1.1.2](https://www.github.com/live627/smf-ultimate-menu/compare/v1.1.1...v1.1.2) (2021-09-04)


### Bug Fixes

* Remove PHP 7 code that should wait for a future version ([2002cd0](https://www.github.com/live627/smf-ultimate-menu/commit/2002cd0eeb70992c197be7425dc5009aaa4a9c82))
* Show hidden buttons in the positions list (register et al) ([a9671bd](https://www.github.com/live627/smf-ultimate-menu/commit/a9671bdd32749a68d6817ea89fda43e7f040ced5))

### [1.1.1](https://www.github.com/live627/smf-ultimate-menu/compare/v1.1.0...v1.1.1) (2021-09-01)


### Bug Fixes

* Show button position even if it's nested too deep for SMF's menu ([ae9c87e](https://www.github.com/live627/smf-ultimate-menu/commit/ae9c87e95ebd3cad6eb4d87813070e97d436d410))

## [1.1.0](https://www.github.com/live627/smf-ultimate-menu/compare/v1.0.5...v1.1.0) (2021-08-22)


### Features

* Add page title when editing a button ([b50ee74](https://www.github.com/live627/smf-ultimate-menu/commit/b50ee747e1c440ff88126641f80dd2e94c558a9a))
* Check session when editing a button ([3eb69de](https://www.github.com/live627/smf-ultimate-menu/commit/3eb69debe07829242d9ef6bc6b23c423b31c6970))


### Bug Fixes

* Each individual button has its own cache entry ([49f39e1](https://www.github.com/live627/smf-ultimate-menu/commit/49f39e195db30e3ab573b8a1bed412d0c14ab68c))

### [1.0.5](https://www.github.com/live627/smf-ultimate-menu/compare/v1.0.4...v1.0.5) (2021-08-21)


### Bug Fixes

* code to check integration was backwards ([695a246](https://www.github.com/live627/smf-ultimate-menu/commit/695a24605d8de56436ac9596149cf4e558c1db3c))
* Fill the radio button with default value when creating new button ([f92da20](https://www.github.com/live627/smf-ultimate-menu/commit/f92da20518583d762fcd3070fcc5ebb959758462))

### [1.0.4](https://www.github.com/live627/smf-ultimate-menu/compare/v1.0.3...v1.0.4) (2021-08-21)


### Bug Fixes

* End usage of the deprecated function `create_function()` ([fc2135b](https://www.github.com/live627/smf-ultimate-menu/commit/fc2135be33688b96d42c3a540066bd4d87a0c9c0))
