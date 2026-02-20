# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Changed
- Upgraded to Silverstripe 6 compatibility
- Updated PHP requirement to ^8.5
- Migrated from `SilverStripe\ORM\DataExtension` to `SilverStripe\Core\Extension`
- Moved source from `code/` to `src/` directory (SS6 convention)
- Replaced `$this->owner->class` with `get_class($this->owner)`
- Replaced string-based `hasExtension('Versioned')` with `hasExtension(Versioned::class)`
- Added typed parameters and return types to all methods
- Added typed property declaration for `$hiddenChildren`
- Replaced `array()` syntax with `[]` short arrays throughout

### Added
- Test suite (PHPUnit 11, SapphireTest) with 86% line coverage
- `.gitignore` for vendor, cache, and generated directories
- `phpunit.xml.dist` configuration
- `MIGRATION-PLAN.md` documenting all changes
- Complete README rewrite with SS6 documentation

### Removed
- `.scrutinizer.yml` (replaced by SonarCloud via CI workflows)
- Legacy SS3/SS3.5 documentation and migration notes
