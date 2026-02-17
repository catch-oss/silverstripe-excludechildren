<?php

// Create Page/PageController stubs needed by silverstripe/cms
// These must exist before the CMS bootstrap and be registered with the autoloader
$appDir = dirname(__DIR__) . '/app/code';
if (!is_dir($appDir)) {
    @mkdir(dirname(__DIR__) . '/app', 0775);
    @mkdir(dirname(__DIR__) . '/app/_config', 0775);
    @mkdir($appDir, 0775);
}
if (!file_exists($appDir . '/Page.php')) {
    file_put_contents(
        $appDir . '/Page.php',
        "<?php\nuse SilverStripe\\CMS\\Model\\SiteTree;\nclass Page extends SiteTree\n{\n}\n"
    );
}
if (!file_exists($appDir . '/PageController.php')) {
    file_put_contents(
        $appDir . '/PageController.php',
        "<?php\nuse SilverStripe\\CMS\\Controllers\\ContentController;\nclass PageController extends ContentController\n{\n}\n"
    );
}

// Register stubs with Composer's autoloader so PHP can find them
$loader = require dirname(__DIR__) . '/vendor/autoload.php';
$loader->addClassMap([
    'Page' => $appDir . '/Page.php',
    'PageController' => $appDir . '/PageController.php',
]);

// Run the CMS test bootstrap (sets up SS kernel, DB config, etc.)
require dirname(__DIR__) . '/vendor/silverstripe/cms/tests/bootstrap.php';
