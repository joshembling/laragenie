# Changelog

All notable changes to `laragenie` will be documented in this file.

## v1.2.0 - 2024-03-17

- Support for Laravel 11
- Minimum PHP version now 8.2

**Full Changelog**: https://github.com/joshembling/laragenie/compare/v1.1.0...v1.2.0

## v1.1.0 - 2024-02-25

**NOTE: v1.1.0 is a major version change. There is a breaking change to the previous minor version (v1.0.63). You must add `PINECONE_INDEX_HOST` to your .env file, please read the docs for more information.**

- Upgrade to latest Pinecone SDK, which enables the use of serverless accounts, as well as pod-based indexes
- Introduces one new environment variable `PINECONE_INDEX_HOST`
- Removes legacy environment variables `PINECONE_ENVIRONMENT` and `PINECONE_INDEX`

**Full Changelog**: https://github.com/joshembling/laragenie/compare/v1.0.63...v1.1.0

## v1.0.63 - 2024-02-17

### What's Changed

- Debugging instructions added to documentation as per issues https://github.com/joshembling/laragenie/issues/4 and https://github.com/joshembling/laragenie/issues/6
- Instructions from config now passed correctly, issue https://github.com/joshembling/laragenie/issues/8
- All chunks now used in AI response, issue https://github.com/joshembling/laragenie/issues/9
- Update OpenAI models in config to latest versions by default by @zbora23 in https://github.com/joshembling/laragenie/pull/11

**Full Changelog**: https://github.com/joshembling/laragenie/compare/v1.0.62...v1.0.63

## v1.0.62 - 2024-02-10

- Formatted question input by removing excess whitespace
- Updated package description

**Full Changelog**: https://github.com/joshembling/laragenie/compare/v1.0.61...v1.0.62

## v1.0.61 - 2024-02-10

### What's Changed

* remove hasViews() by @zbora23 in https://github.com/joshembling/laragenie/pull/7

### New Contributors

* @zbora23 made their first contribution in https://github.com/joshembling/laragenie/pull/7

**Full Changelog**: https://github.com/joshembling/laragenie/compare/v1.0.6...v1.0.61

## v1.0.6 - 2024-01-29

- Minor bug fixes
- Return types

**Full Changelog**: https://github.com/joshembling/laragenie/compare/v1.0.5...v1.0.6

## v1.0.5 - 2024-01-29

- Updates CLI styles
- Test updates

**Full Changelog**: https://github.com/joshembling/laragenie/compare/v1.0.4...v1.0.5

## v1.0.4 - 2024-01-26

- Minor styling changes

**Full Changelog**: https://github.com/joshembling/laragenie/compare/v1.0.3...v1.0.4

## v1.0.3 - 2024-01-25

- Ability to configure indexed files and directories, instead of the sole option of manually typing
- Major code refactor

**Full Changelog**: https://github.com/joshembling/laragenie/compare/v1.0.2...v1.0.3

## v1.0.2 - 2024-01-21

### What's Changed

* Bump aglipanci/laravel-pint-action from 2.3.0 to 2.3.1 by @dependabot in https://github.com/joshembling/laragenie/pull/3

- Index multiple files and directories by passing in a comma separated list
- Strict file types to index set in config
- Updated config variables

**Full Changelog**: https://github.com/joshembling/laragenie/compare/v1.0.1...v1.0.2

## v1.0.1 - 2023-12-21

### What's Changed

* Bump actions/checkout from 3 to 4 by @dependabot in https://github.com/joshembling/laragenie/pull/1
* Bump stefanzweifel/git-auto-commit-action from 4 to 5 by @dependabot in https://github.com/joshembling/laragenie/pull/2
* New config var "welcome"

**Full Changelog**: https://github.com/joshembling/laragenie/compare/v1.0.0...v1.0.1

## v1.0.0 - 2023-12-17

**Full Changelog**: https://github.com/joshembling/laragenie/commits/v1.0.0
