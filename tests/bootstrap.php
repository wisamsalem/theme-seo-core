<?php
/**
 * PHPUnit bootstrap for Theme SEO Core.
 * - Loads Composer autoloader
 * - Sets up Brain Monkey for WP function stubbing
 * - Defines a tiny base TestCase you can extend
 */

declare(strict_types=1);

$autoloader = __DIR__ . '/../vendor/autoload.php';
if (! file_exists($autoloader)) {
    fwrite(STDERR, "Composer autoload not found. Run `composer install`.\n");
    exit(1);
}
require_once $autoloader;

// Some plugins/tests assume these exist.
if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}
date_default_timezone_set('UTC');

// Minimal base test case with Brain Monkey lifecycle.
abstract class Tsc_Unit_TestCase extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }
}

