<?php

namespace micschk\Tests;

use Exception;
use micschk\ExcludeChildren;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataList;
use SilverStripe\Versioned\Versioned;

class ExcludeChildrenTest extends SapphireTest
{
    protected static $required_extensions = [
        SiteTree::class => [ExcludeChildren::class],
    ];

    private SiteTree $holder;

    protected function setUp(): void
    {
        parent::setUp();

        // Push a controller so Controller::curr() works in getFilteredChildren()
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller = Controller::create();
        $controller->setRequest($request);
        $controller->pushCurrent();

        Config::modify()->set(SiteTree::class, 'excluded_children', [
            RedirectorPage::class,
        ]);
        Config::modify()->set(SiteTree::class, 'force_exclusion_beyond_cms', false);

        $this->holder = SiteTree::create();
        $this->holder->Title = 'Test Holder';
        $this->holder->write();

        $normalChild = SiteTree::create();
        $normalChild->Title = 'Normal Child';
        $normalChild->ParentID = $this->holder->ID;
        $normalChild->write();

        $excludedChild = RedirectorPage::create();
        $excludedChild->Title = 'Excluded Child';
        $excludedChild->ParentID = $this->holder->ID;
        $excludedChild->RedirectionType = 'External';
        $excludedChild->ExternalURL = 'https://example.com';
        $excludedChild->write();
    }

    protected function tearDown(): void
    {
        $controller = Controller::curr();
        if ($controller) {
            $controller->popCurrent();
        }
        parent::tearDown();
    }

    public function testGetExcludedClassesReturnsConfiguredClasses(): void
    {
        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $excluded = $extension->getExcludedClasses();

        $this->assertIsArray($excluded);
        $this->assertContains(RedirectorPage::class, $excluded);
        $this->assertNotContains(SiteTree::class, $excluded);
    }

    public function testGetExcludedClassesReturnsEmptyWhenNoConfig(): void
    {
        Config::modify()->set(SiteTree::class, 'excluded_children', null);

        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $excluded = $extension->getExcludedClasses();

        $this->assertIsArray($excluded);
        $this->assertEmpty($excluded);
    }

    public function testGetFilteredChildrenReturnsUnfilteredOutsideCMS(): void
    {
        $children = SiteTree::get()->filter('ParentID', $this->holder->ID);

        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $filtered = $extension->getFilteredChildren($children);

        // Outside CMS with force_exclusion_beyond_cms=false, all children returned
        $this->assertCount($children->count(), $filtered);
    }

    public function testGetFilteredChildrenFiltersWhenForced(): void
    {
        Config::modify()->set(SiteTree::class, 'force_exclusion_beyond_cms', true);

        $children = SiteTree::get()->filter('ParentID', $this->holder->ID);
        $originalCount = $children->count();

        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $filtered = $extension->getFilteredChildren($children);

        $this->assertGreaterThan(0, $originalCount);
        $this->assertLessThan($originalCount, $filtered->count());
        $classNames = $filtered->column('ClassName');
        $this->assertNotContains(RedirectorPage::class, $classNames);
    }

    public function testHierarchyStageChildrenReturnsChildPages(): void
    {
        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $children = $extension->hierarchyStageChildren(true);

        $this->assertInstanceOf(DataList::class, $children);
        $this->assertGreaterThan(0, $children->count());
    }

    public function testHierarchyStageChildrenExcludesParent(): void
    {
        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $children = $extension->hierarchyStageChildren(true);
        $childIDs = $children->column('ID');

        $this->assertNotContains($this->holder->ID, $childIDs);
    }

    public function testStageChildrenAppliesFiltering(): void
    {
        Config::modify()->set(SiteTree::class, 'force_exclusion_beyond_cms', true);

        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $children = $extension->stageChildren(true);
        $classNames = $children->column('ClassName');

        $this->assertNotContains(RedirectorPage::class, $classNames);
    }

    public function testHierarchyLiveChildrenReturnsDataList(): void
    {
        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $children = $extension->hierarchyLiveChildren(true);

        $this->assertInstanceOf(DataList::class, $children);
    }
}
