# Changelog

## [1.0.0](https://github.com/tomylana93/jalara-starter-kit/compare/v0.4.1...v1.0.0) (2026-08-05)


### ⚠ BREAKING CHANGES

* **models:** strict models are enabled outside production. An application built on this kit whose factories do not set every column will now see a `MissingAttributeException` where an unset column previously read as null. The fix is to name the column in `definition()` with an explicit null; see `.ai/rules/factories.md`.

### Features

* **api:** authenticate the versioned API with Sanctum tokens ([a737a39](https://github.com/tomylana93/jalara-starter-kit/commit/a737a3971b08e74d05deaff833c6ccbfaf20b88a))
* **models:** enable strict Eloquent models outside production ([71c3c27](https://github.com/tomylana93/jalara-starter-kit/commit/71c3c27bea452288f3f5f9e7602ef20c1ea16db2))


### Bug Fixes

* **e2e:** force fill the chat peer's verification timestamp ([61e332a](https://github.com/tomylana93/jalara-starter-kit/commit/61e332ae21efebcc465ede42c21e76b1ffc447f5))
* **ui:** restore the explicit button type on the notification mark-all control ([a6329f6](https://github.com/tomylana93/jalara-starter-kit/commit/a6329f6ab3f4b17154d5b1bc963528b16b3cbe15))

## [0.4.1](https://github.com/tomylana93/jalara-starter-kit/compare/v0.4.0...v0.4.1) (2026-08-05)


### Bug Fixes

* **test:** keep Vitest on the forks pool so the fixed timezone applies ([129942e](https://github.com/tomylana93/jalara-starter-kit/commit/129942ed6a5a85dd34718a6e536894ad16a4bfc0))

## [0.4.0](https://github.com/tomylana93/jalara-starter-kit/compare/v0.3.0...v0.4.0) (2026-08-05)


### Features

* add scheduled backups, editor context menu, and navigation improvements ([2b42fe5](https://github.com/tomylana93/jalara-starter-kit/commit/2b42fe50a6871d6b29eda9e9213b29c513fc2359))
* **backups:** add scheduled database and media backups ([3fb0d4d](https://github.com/tomylana93/jalara-starter-kit/commit/3fb0d4df3899377bd5766bf70435b64c48b6674e))
* **editor:** reach every toolbar action from a context menu ([8ec2137](https://github.com/tomylana93/jalara-starter-kit/commit/8ec2137167dd338659df3f99251f406bfa84a864))
* **navigation:** give global search an input-shaped desktop trigger ([52f4986](https://github.com/tomylana93/jalara-starter-kit/commit/52f4986eee3393b3430a655f8e0de622bf6a9991))
* **navigation:** group the header menu and restore the footer tooltip ([dfb0959](https://github.com/tomylana93/jalara-starter-kit/commit/dfb0959f42a3722b33e0c0d65109ca257c749701))


### Bug Fixes

* **documentation:** stretch the editor selects to the full field width ([2ef0d42](https://github.com/tomylana93/jalara-starter-kit/commit/2ef0d423c268f3a3228a102bb5827624e2502fc3))
* **navigation:** keep long breadcrumbs on one line on mobile ([491219d](https://github.com/tomylana93/jalara-starter-kit/commit/491219dbe1e7acb8fc073dab022ba4427cfc7115))

## [0.3.0](https://github.com/tomylana93/jalara-starter-kit/compare/v0.2.0...v0.3.0) (2026-08-04)


### Features

* **hooks:** gate built-in code reads and edits behind Serena ([a265ca9](https://github.com/tomylana93/jalara-starter-kit/commit/a265ca91914635f96069ed70cd917452f2e91f0a))

## [0.2.0](https://github.com/tomylana93/jalara-starter-kit/compare/v0.1.1...v0.2.0) (2026-08-04)


### Features

* **hooks:** add precondition to check if Serena's manual has been read ([6c115fe](https://github.com/tomylana93/jalara-starter-kit/commit/6c115fe6e1b06ba4160189071caa83894f17aaf7))

## [0.1.1](https://github.com/tomylana93/jalara-starter-kit/compare/v0.1.0...v0.1.1) (2026-08-04)


### Bug Fixes

* **release:** support root manifest publication ([3c7a3f9](https://github.com/tomylana93/jalara-starter-kit/commit/3c7a3f95e08f81d01d79698fef8af074ab48336a))
* **release:** support root manifest publication ([e24931d](https://github.com/tomylana93/jalara-starter-kit/commit/e24931dedd2df003fa564a3fd11b935de3e0628d))

## 0.1.0 (2026-08-04)


### Miscellaneous Chores

* prepare initial release ([b5b49bc](https://github.com/tomylana93/jalara-starter-kit/commit/b5b49bcf8bf1abc782f0307e1ee3c873131f7949))
