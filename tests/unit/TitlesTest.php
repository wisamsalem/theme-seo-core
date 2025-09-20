<?php
declare(strict_types=1);

use ThemeSeoCore\Modules\Titles\Patterns;
use ThemeSeoCore\Modules\Titles\TitleGenerator;
use ThemeSeoCore\Support\Options;
use Brain\Monkey\Functions;

require_once __DIR__ . '/../bootstrap.php';

final class TitlesTest extends Tsc_Unit_TestCase
{
    public function test_patterns_front_page_defaults(): void
    {
        // WP env shims
        Functions\when('get_option')->alias(function ($key, $default = null) {
            // No stored settings -> defaults kick in (separator '–')
            return $default;
        });
        Functions\when('get_bloginfo')->alias(function ($what) {
            return $what === 'name' ? 'Test Site' : 'Just another WP site';
        });
        Functions\when('is_singular')->justReturn(false);
        Functions\when('is_front_page')->justReturn(true);
        Functions\when('__')->returnArg(1); // passthrough translations

        $opts     = new Options('tsc_settings'); // will call get_option()
        $patterns = new Patterns($opts);

        $out = $patterns->replace('%%sitename%% %%sep%% %%tagline%%');
        $this->assertSame('Test Site – Just another WP site', $out);
    }

    public function test_title_generator_singular_uses_metabox_override(): void
    {
        // Query context → singular post with custom meta title
        Functions\when('is_singular')->justReturn(true);
        Functions\when('get_queried_object_id')->justReturn(123);
        Functions\when('get_post_meta')->alias(function ($id, $key) {
            if ($id === 123 && $key === \ThemeSeoCore\Admin\MetaBox::META_TITLE) {
                return 'Custom Title';
            }
            return '';
        });
        Functions\when('get_query_var')->justReturn(1); // not paged
        Functions\when('__')->returnArg(1);

        // Options used for separator (not needed here)
        Functions\when('get_option')->justReturn([]);
        $opts = new Options('tsc_settings');

        $gen = new TitleGenerator($opts);
        $this->assertSame('Custom Title', $gen->generate());
    }

    public function test_title_generator_applies_pagination_suffix(): void
    {
        // No custom meta, paged=3 → add “Page 3”
        Functions\when('is_singular')->justReturn(true);
        Functions\when('get_queried_object_id')->justReturn(456);
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_the_excerpt')->justReturn('');
        Functions\when('get_post')->alias(function ($id) {
            return (object)['post_content' => 'Hello world'];
        });
        Functions\when('get_query_var')->alias(function ($key) {
            return $key === 'paged' ? 3 : 0;
        });
        Functions\when('get_option')->justReturn([]); // so separator defaults to "–"
        Functions\when('__')->returnArg(1);
        Functions\when('number_format_i18n')->alias(fn($n) => (string)$n);

        $opts = new Options('tsc_settings');
        $gen  = new TitleGenerator($opts);
        $this->assertSame('Hello world – Page 3', $gen->generate());
    }
}

