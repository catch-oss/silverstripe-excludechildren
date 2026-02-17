<?php

namespace micschk\Tests;

use micschk\ExcludeChildren;
use Page;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataList;

class ExcludeChildrenTest extends SapphireTest
{
    protected static $required_extensions = [
        Page::class => [ExcludeChildren::class],
    ];

    private Page $holder;

    /**
     * Set up test fixtures:
     * - A holder Page with the ExcludeChildren extension
     * - A normal Page child (should never be excluded)
     * - A RedirectorPage child (configured as the excluded type)
     * - A front-end controller context (non-CMS) for Controller::curr()
     * - Config: excluded_children = [RedirectorPage], force_exclusion_beyond_cms = false
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Push a controller so Controller::curr() works in getFilteredChildren().
        // This simulates a front-end request context (not LeftAndMain/CMS).
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller = Controller::create();
        $controller->setRequest($request);
        $controller->pushCurrent();

        Config::modify()->set(Page::class, 'excluded_children', [
            RedirectorPage::class,
        ]);
        Config::modify()->set(Page::class, 'force_exclusion_beyond_cms', false);

        $this->holder = Page::create();
        $this->holder->Title = 'Test Holder';
        $this->holder->write();

        $normalChild = Page::create();
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
        // Given: excluded_children config contains RedirectorPage
        // When: getExcludedClasses() is called
        // Then: the returned array includes RedirectorPage (and its subclasses)
        //       but does not include the holder's own class (Page)
        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $excluded = $extension->getExcludedClasses();

        $this->assertIsArray($excluded);
        $this->assertContains(RedirectorPage::class, $excluded);
        $this->assertNotContains(Page::class, $excluded);
    }

    public function testGetExcludedClassesReturnsEmptyWhenNoConfig(): void
    {
        // Given: excluded_children config is null (no exclusions configured)
        // When: getExcludedClasses() is called
        // Then: an empty array is returned — nothing is excluded
        Config::modify()->set(Page::class, 'excluded_children', null);

        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $excluded = $extension->getExcludedClasses();

        $this->assertIsArray($excluded);
        $this->assertEmpty($excluded);
    }

    public function testGetFilteredChildrenReturnsUnfilteredOutsideCMS(): void
    {
        // Given: force_exclusion_beyond_cms is false and the current controller
        //        is a plain Controller (not LeftAndMain/CMS)
        // When: getFilteredChildren() is called with the holder's children
        // Then: all children are returned unfiltered — exclusion only applies in the CMS
        $children = SiteTree::get()->filter('ParentID', $this->holder->ID);

        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $filtered = $extension->getFilteredChildren($children);

        $this->assertCount($children->count(), $filtered);
    }

    public function testGetFilteredChildrenFiltersWhenForced(): void
    {
        // Given: force_exclusion_beyond_cms is true (exclusion applies everywhere)
        //        and the holder has both a Page child and a RedirectorPage child
        // When: getFilteredChildren() is called
        // Then: RedirectorPage children are excluded from the result,
        //       but Page children remain
        Config::modify()->set(Page::class, 'force_exclusion_beyond_cms', true);

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
        // Given: the holder page has child pages in the Stage (draft) site
        // When: hierarchyStageChildren() is called with showAll=true
        // Then: a non-empty DataList of child pages is returned
        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $children = $extension->hierarchyStageChildren(true);

        $this->assertInstanceOf(DataList::class, $children);
        $this->assertGreaterThan(0, $children->count());
    }

    public function testHierarchyStageChildrenExcludesParent(): void
    {
        // Given: the holder page has child pages
        // When: hierarchyStageChildren() is called
        // Then: the holder's own ID is not in the result set
        //       (the query excludes ID = owner ID to prevent self-inclusion)
        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $children = $extension->hierarchyStageChildren(true);
        $childIDs = $children->column('ID');

        $this->assertNotContains($this->holder->ID, $childIDs);
    }

    public function testStageChildrenAppliesFiltering(): void
    {
        // Given: force_exclusion_beyond_cms is true and RedirectorPage is excluded
        // When: stageChildren() is called (which delegates to hierarchyStageChildren
        //       then passes the result through getFilteredChildren)
        // Then: RedirectorPage children are excluded from the final result
        Config::modify()->set(Page::class, 'force_exclusion_beyond_cms', true);

        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $children = $extension->stageChildren(true);
        $classNames = $children->column('ClassName');

        $this->assertNotContains(RedirectorPage::class, $classNames);
    }

    public function testHierarchyLiveChildrenReturnsDataList(): void
    {
        // Given: the holder page has the Versioned extension (SiteTree default)
        // When: hierarchyLiveChildren() is called with showAll=true
        // Then: a DataList is returned (querying the Live stage via Versioned)
        $extension = $this->holder->getExtensionInstance(ExcludeChildren::class);
        $extension->setOwner($this->holder);

        $children = $extension->hierarchyLiveChildren(true);

        $this->assertInstanceOf(DataList::class, $children);
    }
}
