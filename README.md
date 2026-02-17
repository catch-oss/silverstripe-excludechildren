# silverstripe-excludechildren

<!-- PROJECT SHIELDS -->
[![SonarCloud](https://github.com/catch-oss/silverstripe-excludechildren/actions/workflows/sonar.yml/badge.svg)](https://github.com/catch-oss/silverstripe-excludechildren/actions/workflows/sonar.yml)
[![Test](https://github.com/catch-oss/silverstripe-excludechildren/actions/workflows/test.yml/badge.svg)](https://github.com/catch-oss/silverstripe-excludechildren/actions/workflows/test.yml)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-excludechildren&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=catch-design_catch-oss-silverstripe-excludechildren)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-excludechildren&metric=bugs)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-excludechildren)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-excludechildren&metric=code_smells)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-excludechildren)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-excludechildren&metric=coverage)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-excludechildren)
[![Duplicated Lines Density](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-excludechildren&metric=duplicated_lines_density)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-excludechildren)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-excludechildren&metric=ncloc)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-excludechildren)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-excludechildren&metric=reliability_rating)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-excludechildren)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-excludechildren&metric=security_rating)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-excludechildren)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-excludechildren&metric=sqale_index)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-excludechildren)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-excludechildren&metric=sqale_rating)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-excludechildren)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-excludechildren&metric=vulnerabilities)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-excludechildren)

A Silverstripe 6 extension that hides specific child page types from the CMS SiteTree, while keeping them accessible via the ORM and front-end templates.

## Requirements

- PHP 8.5+
- Silverstripe CMS 6.0+
- Silverstripe Framework 6.0+

## Installation

```bash
composer require micschk/silverstripe-excludechildren
```

## Configuration

Apply the extension to a holder page class and list the child page types to hide:

```yaml
# app/_config/excludechildren.yml
App\Pages\SubPageHolder:
  extensions:
    excludechildren: micschk\ExcludeChildren
  excluded_children:
    - App\Pages\SubPage
    - App\Pages\AnotherChildType
  force_exclusion_beyond_cms: false
```

### Options

| Option | Type | Default | Description |
|---|---|---|---|
| `excluded_children` | `array` | `[]` | Page class names to hide from the SiteTree |
| `force_exclusion_beyond_cms` | `bool` | `false` | Also hide from `$Children` in templates |

## How it works

By default the extension only filters children in the CMS SiteTree view (treeview, listview, getsubtree actions, and TreeDropdownField). Pages remain fully accessible via the ORM and front-end `$Children` loops.

When `force_exclusion_beyond_cms` is `true`, the extension also filters `$Children` in templates. Use a custom getter to retrieve hidden children in that case:

```php
public function AllChildren(): DataList
{
    return SiteTree::get()->filter('ParentID', $this->ID)->sort('Sort');
}
```

## Custom filtering

For filtering logic beyond class name matching, implement `getExcludedChildren()` on your holder page class. It receives the full children DataList and should return the filtered result:

```php
use SilverStripe\ORM\DataList;

class SubPageHolder extends \Page
{
    public function getExcludedChildren(DataList $children): DataList
    {
        return $children->filter('ShowInMenus', true);
    }
}
```

## License

BSD-3-Clause
