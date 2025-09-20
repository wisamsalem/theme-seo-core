<?php
declare(strict_types=1);

use ThemeSeoCore\Modules\Redirects\Matcher;

require_once __DIR__ . '/../bootstrap.php';

final class RedirectsTest extends Tsc_Unit_TestCase
{
    public function test_exact_match(): void
    {
        $row = [
            'source'     => '/old-path?ref=abc',
            'target'     => '/new-path',
            'match_type' => 'exact',
        ];
        $this->assertTrue(Matcher::matches($row, '/old-path?ref=abc', 'example.com'));
        $this->assertFalse(Matcher::matches($row, '/old-path?ref=xyz', 'example.com'));
    }

    public function test_prefix_match(): void
    {
        $row = [
            'source'     => '/legacy/',
            'target'     => '/modern/',
            'match_type' => 'prefix',
        ];
        $this->assertTrue(Matcher::matches($row, '/legacy/page-1?x=1', 'example.com'));
        $this->assertFalse(Matcher::matches($row, '/other/page', 'example.com'));
    }

    public function test_regex_match(): void
    {
        $row = [
            'source'     => '#^/docs/(v\d+)/(.+)$#',
            'target'     => '/kb/$1/$2',
            'match_type' => 'regex',
        ];
        $this->assertTrue(Matcher::matches($row, '/docs/v2/install', 'example.com'));
        $this->assertFalse(Matcher::matches($row, '/docs/latest/install', 'example.com'));
    }
}

