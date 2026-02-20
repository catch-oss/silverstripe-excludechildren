# Migration Plan: silverstripe-excludechildren

## Summary

- **Package**: micschk/silverstripe-excludechildren
- **Type**: B (Silverstripe module)
- **Tier**: 2
- **Risk Level**: Low
- **Estimated Scope**: 1 file, 1 class (ExcludeChildren DataExtension)

## Change Inventory

### Namespace Renames Required

No SS5→SS6 namespace renames needed. The imports used (`DataExtension`, `DataList`, `DataObject`, `LeftAndMain`, `ClassInfo`, `Controller`) are not in the SS6 rename list.

### Composer Dependency Changes

| Package | Current Version | Target Version |
|---|---|---|
| php | (not specified) | ^8.5 |
| silverstripe/cms | * | ^6.0 |
| silverstripe/framework | (not specified) | ^6.0 |
| silverstripe/vendor-plugin | (not specified) | ^3.0 |
| phpunit/phpunit (dev) | (not specified) | ^11.0 |

Also need:
- Add `config.allow-plugins` for `composer/installers` and `silverstripe/vendor-plugin`
- Move autoload from `code/` to `src/` (SS6 convention)

### API Changes Required

| Pattern | Migration | Files Affected |
|---|---|---|
| `$this->owner->class` | `get_class($this->owner)` | code/ExcludeChildren.php |
| `hasExtension('Versioned')` | `hasExtension(Versioned::class)` | code/ExcludeChildren.php |
| `setDataQueryParam()` | Verify still available in SS6 ORM | code/ExcludeChildren.php |
| `array()` syntax | `[]` short array syntax | code/ExcludeChildren.php |
| `\Exception` import | `use Exception;` (no leading backslash) | code/ExcludeChildren.php |

### PHP 8.5 Compatibility Fixes

| Issue | Fix | Files Affected |
|---|---|---|
| Missing return type declarations | Add `: array`, `: DataList`, `: void` etc. | code/ExcludeChildren.php |
| Missing property type declarations | Add types to `$hiddenChildren` | code/ExcludeChildren.php |

No implicit nullable parameters — all defaults are `false`, not `null`.

### PHPUnit Migration

No existing tests. Tests must be written from scratch.

### Config Changes

| File | Change Required |
|---|---|
| _config.php | Empty — no changes needed |
| No YAML config | Module is configured by consuming project |

## Risk Assessment

| Area | Risk | Notes |
|---|---|---|
| Namespace renames | Low | None needed |
| API changes | Low | Minor: `->class` to `get_class()`, `setDataQueryParam` check |
| PHP 8.5 compat | Low | Add return types, property types |
| Test migration | Medium | No tests exist — must write from scratch for 80% coverage |
| Config changes | Low | No config to migrate |
| Composer setup | Low | Standard dependency bumps |

## Migration Steps (Ordered)

### Phase 1: composer.json
- [ ] Add `php: ^8.5`
- [ ] Change `silverstripe/cms: *` to `^6.0`
- [ ] Add `silverstripe/framework: ^6.0`
- [ ] Add `silverstripe/vendor-plugin: ^3.0`
- [ ] Add dev dep `phpunit/phpunit: ^11.0`
- [ ] Add `config.allow-plugins` for `composer/installers` and `silverstripe/vendor-plugin`
- [ ] Move autoload from `code/` to `src/` namespace path
- [ ] Run composer validate

### Phase 2: Directory Restructure
- [ ] Move `code/ExcludeChildren.php` to `src/ExcludeChildren.php`
- [ ] Update autoload path in composer.json

### Phase 3: API Changes
- [ ] Replace `$this->owner->class` with `get_class($this->owner)`
- [ ] Replace `hasExtension('Versioned')` with `hasExtension(Versioned::class)`
- [ ] Add `use SilverStripe\Versioned\Versioned;` import
- [ ] Fix `use \Exception` to `use Exception`
- [ ] Replace `array()` with `[]` throughout
- [ ] Verify `setDataQueryParam()` still works in SS6

### Phase 4: PHP 8.5 Compatibility
- [ ] Add return type declarations to all methods
- [ ] Add typed property declaration for `$hiddenChildren`

### Phase 5: Logging Integration
- [ ] Not applicable — module has no logging needs

### Phase 6: Config & Cleanup
- [ ] No config changes needed
- [ ] Remove `.scrutinizer.yml` (obsolete, replaced by SonarCloud)

### Phase 6b: README Rewrite
- [ ] Remove all legacy SS3/SS3.5 content and migration notes
- [ ] Remove "Replaced by core functionality" messaging (this is now an active catch-oss fork)
- [ ] Keep CI/SonarCloud badges at top
- [ ] Write fresh SS6-compatible docs: requirements, installation, YAML config (named extension keys), usage
- [ ] Show modern PHP 8.5 code examples (typed properties, short arrays, return types)
- [ ] Document `excluded_children`, `force_exclusion_beyond_cms`, custom `getExcludedChildren()`
- [ ] Document `$Children` template behaviour and workarounds
- [ ] Remove references to defunct external modules (gridfieldsitetreebuttons, gridfieldpages)

### Phase 7: Test Suite (Silverstripe Best Practices)
- [ ] Create `phpunit.xml.dist` with SS framework bootstrap
- [ ] Create `tests/ExcludeChildrenTest.php` extending `SapphireTest`
- [ ] Test `getExcludedClasses()` with config-driven exclusions
- [ ] Test `getFilteredChildren()` filtering logic
- [ ] Test `stageChildren()` and `liveChildren()` delegation
- [ ] Test CMS context detection (LeftAndMain controller)
- [ ] Test `force_exclusion_beyond_cms` config option
- [ ] Test custom `getExcludedChildren` method delegation
- [ ] Use `$fixture_file` with SiteTree fixtures
- [ ] Use `$extra_dataobjects` for test page types
- [ ] Target 80% line coverage

## Dependencies

- Depends on: None (Tier 2, no internal catch-oss dependencies)
- Blocks: None directly (no higher-tier repos depend on this)
