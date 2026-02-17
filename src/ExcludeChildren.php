<?php

namespace micschk;

use Exception;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Extension to hide specific page types from the SiteTree in the CMS.
 *
 * Configure via YAML on a holder page class:
 *
 *     MyHolderPage:
 *       extensions:
 *         excludechildren: micschk\ExcludeChildren
 *       excluded_children:
 *         - MyChildPage
 *       force_exclusion_beyond_cms: false
 */
class ExcludeChildren extends Extension
{
    protected array $hiddenChildren = [];

    public function getExcludedClasses(): array
    {
        $hiddenChildren = [];
        $configClasses = $this->owner->config()->get('excluded_children');
        if ($configClasses) {
            foreach ($configClasses as $class) {
                $hiddenChildren = array_merge($hiddenChildren, array_values(ClassInfo::subclassesFor($class)));
            }
        }
        $this->hiddenChildren = $hiddenChildren;

        return $this->hiddenChildren;
    }

    public function getFilteredChildren(DataList $children): DataList
    {
        $controller = Controller::curr();
        $action = $controller->getAction();

        $allParams = $controller->getRequest()->allParams();
        $treeDropdownFieldAction = $allParams['Action'] ?? null;

        if (
            $this->owner->config()->get('force_exclusion_beyond_cms')
            || ($controller instanceof LeftAndMain
                && ($treeDropdownFieldAction === 'tree' || in_array($action, ['treeview', 'listview', 'getsubtree'])))
        ) {
            if ($this->owner->hasMethod('getExcludedChildren')) {
                return $this->owner->getExcludedChildren($children);
            }

            return $children->exclude('ClassName', $this->getExcludedClasses());
        }

        return $children;
    }

    public function stageChildren(bool $showAll = false): DataList
    {
        $children = $this->hierarchyStageChildren($showAll);

        return $this->getFilteredChildren($children);
    }

    public function liveChildren(bool $showAll = false, bool $onlyDeletedFromStage = false): DataList
    {
        $children = $this->hierarchyLiveChildren($showAll, $onlyDeletedFromStage);

        return $this->getFilteredChildren($children);
    }

    /**
     * Return children from the stage site.
     *
     * Duplicated from Hierarchy::stageChildren() because we override the original method.
     */
    public function hierarchyStageChildren(bool $showAll = false): DataList
    {
        $baseClass = DataObject::getSchema()->baseDataClass(get_class($this->owner));
        $staged = $baseClass::get()
            ->filter('ParentID', (int) $this->owner->ID)
            ->exclude('ID', (int) $this->owner->ID);
        if (!$showAll && $this->owner->db('ShowInMenus')) {
            $staged = $staged->filter('ShowInMenus', 1);
        }
        $this->owner->extend('augmentStageChildren', $staged, $showAll);

        return $staged;
    }

    /**
     * Return children from the live site, if it exists.
     *
     * Duplicated from Hierarchy::liveChildren() because we override the original method.
     */
    public function hierarchyLiveChildren(bool $showAll = false, bool $onlyDeletedFromStage = false): DataList
    {
        if (!$this->owner->hasExtension(Versioned::class)) {
            throw new Exception('ExcludeChildren::liveChildren() requires the Versioned extension');
        }

        $baseClass = DataObject::getSchema()->baseDataClass(get_class($this->owner));
        $children = $baseClass::get()
            ->filter('ParentID', (int) $this->owner->ID)
            ->exclude('ID', (int) $this->owner->ID)
            ->setDataQueryParam([
                'Versioned.mode' => $onlyDeletedFromStage ? 'stage_unique' : 'stage',
                'Versioned.stage' => 'Live',
            ]);

        if (!$showAll) {
            $children = $children->filter('ShowInMenus', 1);
        }

        return $children;
    }
}
