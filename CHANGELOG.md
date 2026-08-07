# Changelog

## [1.4.0](https://github.com/tomylana93/jalara-starter-kit/compare/v1.3.0...v1.4.0) (2026-08-07)


### Features

* **deploy:** add manual VPS deployment scripts ([399772a](https://github.com/tomylana93/jalara-starter-kit/commit/399772ad17205e16d1446ca170413a1b2da2f1a3))
* **deploy:** add manual VPS deployment scripts ([a5bcf70](https://github.com/tomylana93/jalara-starter-kit/commit/a5bcf70953a24abd9704b05dc5cb9e9acbd11d3a))


### Bug Fixes

* **deps:** pin js-yaml to 4.3.1 through overrides ([8b28e86](https://github.com/tomylana93/jalara-starter-kit/commit/8b28e86480c334db3a4e4f3bd03702564e95c38a))

## [1.3.0](https://github.com/tomylana93/jalara-starter-kit/compare/v1.2.0...v1.3.0) (2026-08-06)


### Features

* **backups:** restore an archive and upload one from elsewhere ([e07efae](https://github.com/tomylana93/jalara-starter-kit/commit/e07efae33095aba4b93a9dfc6069dab5267d182e))
* **maintenance:** serve maintenance and error states as full pages ([5d934bb](https://github.com/tomylana93/jalara-starter-kit/commit/5d934bbea94b07e9b11c0c18de387fa1de503f23))
* **master-data:** export selected users as pdf ([2a9299c](https://github.com/tomylana93/jalara-starter-kit/commit/2a9299cde6eeb06d5d61fc45513282c42b19494d))
* **master-data:** import users from a spreadsheet ([ede7cc4](https://github.com/tomylana93/jalara-starter-kit/commit/ede7cc4543c43e7df7d2391c88d0dca4d7811100))
* **master-data:** import users from a spreadsheet and export them as pdf ([cd4a7d6](https://github.com/tomylana93/jalara-starter-kit/commit/cd4a7d650bc9a0f384eb8fea0283188dde571190))

## [1.2.0](https://github.com/tomylana93/jalara-starter-kit/compare/v1.1.0...v1.2.0) (2026-08-06)


### Features

* action-boundary data layer and architecture cleanup ([3099900](https://github.com/tomylana93/jalara-starter-kit/commit/3099900802fb929b5725b17ebf96ed65c111488c))
* complete the action-boundary data layer ([2271ff4](https://github.com/tomylana93/jalara-starter-kit/commit/2271ff438bda09b7b4f0a5e88475f429310892e3))
* **settings:** type the settings actions with data objects ([2dda4d7](https://github.com/tomylana93/jalara-starter-kit/commit/2dda4d7d71c126b8ebaf34d9debfe35a2d58a584))


### Bug Fixes

* **settings:** render the help text every settings field already had ([3bc936e](https://github.com/tomylana93/jalara-starter-kit/commit/3bc936ebfc33e887bf7ebb719ed58782b1845074))

## [1.1.0](https://github.com/tomylana93/jalara-starter-kit/compare/v1.0.0...v1.1.0) (2026-08-06)


### Features

* **ui:** rework chat and notification timestamps ([6dd3911](https://github.com/tomylana93/jalara-starter-kit/commit/6dd39116f49cc900668bf1d792f5896a21ff6bba))
* **ui:** rework timestamp placement in chat and notifications ([f596e80](https://github.com/tomylana93/jalara-starter-kit/commit/f596e80d472a2f293d972cc1b02e0f95f192d192))


### Bug Fixes

* **notifications:** open a notification in a single request ([0ee4867](https://github.com/tomylana93/jalara-starter-kit/commit/0ee48677b5471ae47e3c9de854e6171bbb8d916d))

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
