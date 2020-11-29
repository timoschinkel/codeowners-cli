# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Changed
- Updated Psalm requirements to `^4.2`

## [1.1.0] - 2020-01-23
### Added
- Added command `list-owners` that lists all owners specified in the CODEOWNERS file
- Add `.gitlab/` to the locations where a CODEOWNERS file is searched ([#6](https://github.com/timoschinkel/codeowners-cli/issues/6))

### Changed
- Renamed `\CodeOwners\Cli\Tests\Command\ListCommandTest` to `\CodeOwners\Cli\Tests\Command\ListFilesCommandTest`
- Updated required version of `timoschinkel/codeowners` to `^1.1.0`
- Changed order for searching `CODEOWNERS` file to `.github/|.gitlab/|.bitbucket/` > `root` > `docs/` ([#6](https://github.com/timoschinkel/codeowners-cli/issues/6))

## [1.0.0] - 2020-01-08
### Added
- Travis inspections
- PSR-12 coding standard using PHP CodeSniffer
- Static analysis using Psalm
- Unit tests

### Changed
- Set PHP 7.2 as hard minimum requirement

### Fixed
- Fixed issue [#3](https://github.com/timoschinkel/codeowners-cli/issues/3) `list-files` appears to struggle with leading slashes in CODEOWNERS file

## [0.0.2] - 2020-01-06
### Added
- `CHANGELOG.md`
- `CONTRIBUTING.md`

### Changed
- Changed location to be searched for `autoload.php` to actually allow global usage

## [0.0.1] - 2020-01-06
### Added
- Initial version of code, excluding unit tests
- README
- Composer configuration
