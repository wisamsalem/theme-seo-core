<?php
namespace ThemeSeoCore\Admin;

use ThemeSeoCore\Container\Container;
use ThemeSeoCore\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers plugin settings, sections, and fields via the WP Settings API.
 * Keep it minimal so the Settings page always loads.
 */
class SettingsRegistry
{
    public function register( Container $c ): void
    {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Settings API declarations.
     *
     * - Option group:  "tsc"
     * - Option name:   "tsc_settings" (array)
     * - Capability:    manage_options (via Capabilities::manage_seo())
     */
    public function register_settings(): void
    {
        $cap = Capabilities::manage_seo();

        // 1) Register the main option as an array
        register_setting(
            'tsc',
            'tsc_settings',
            [
                'type'              => 'array',
                'description'       => 'Theme SEO Core settings.',
                'sanitize_callback' => [ $this, 'sanitize' ],
                'default'           => [],
                'show_in_rest'      => false,
                'capability'        => $cap,
            ]
        );

        // 2) Sections on our main settings page slug: "theme-seo-core"
        add_settings_section(
            'tsc_general',
            __( 'General', 'theme-seo-core' ),
            function () {
                echo '<p>' . esc_html__( 'Global defaults and behavior.', 'theme-seo-core' ) . '</p>';
            },
            'theme-seo-core'
        );

        add_settings_section(
            'tsc_content',
            __( 'Content', 'theme-seo-core' ),
            function () {
                echo '<p>' . esc_html__( 'Per-content rules (titles/meta, etc.).', 'theme-seo-core' ) . '</p>';
            },
            'theme-seo-core'
        );

        // NEW: Modules section (Sitemaps toggle)
        add_settings_section(
            'tsc_modules',
            __( 'Modules', 'theme-seo-core' ),
            function () {
                echo '<p>' . esc_html__( 'Turn individual modules on or off.', 'theme-seo-core' ) . '</p>';
            },
            'theme-seo-core'
        );

        // Sitemaps toggle (defaults OFF unless explicitly enabled)
        add_settings_field(
            'tsc_modules_sitemaps',
            __( 'Sitemaps', 'theme-seo-core' ),
            [ $this, 'field_modules_sitemaps' ],
            'theme-seo-core',
            'tsc_modules',
            [ 'label_for' => 'tsc_modules_sitemaps' ]
        );

        // NEW: Compatibility section (override)
        add_settings_section(
            'tsc_compat',
            __( 'Compatibility', 'theme-seo-core' ),
            function () {
                echo '<p>' . esc_html__( 'When another SEO plugin is active, Theme SEO Core suppresses duplicate Meta/OG/Schema. You can override that here (not recommended).', 'theme-seo-core' ) . '</p>';
            },
            'theme-seo-core'
        );

        add_settings_field(
            'tsc_compat_override',
            __( 'Compatibility Override', 'theme-seo-core' ),
            [ $this, 'field_compat_override' ],
            'theme-seo-core',
            'tsc_compat',
            [ 'label_for' => 'tsc_compat_override' ]
        );

        // 3) Example field so the page always has at least one control
        add_settings_field(
            'tsc_sep',
            __( 'Title Separator', 'theme-seo-core' ),
            [ $this, 'field_separator' ],
            'theme-seo-core',
            'tsc_general',
            [ 'label_for' => 'tsc_sep' ]
        );
    }

    /**
     * Sanitize the whole settings array.
     * Keep it tolerant: only clean known keys, pass through others.
     *
     * @param array|string $value
     * @return array
     */
    public function sanitize( $value ): array
    {
        $in  = is_array( $value ) ? $value : [];
        $out = $in;

        // Known key: "sep" (title separator)
        if ( isset( $in['sep'] ) ) {
            $out['sep'] = sanitize_text_field( (string) $in['sep'] );
            if ( strlen( $out['sep'] ) > 3 ) {
                $out['sep'] = mb_substr( $out['sep'], 0, 3 );
            }
        }

        // Known key: modules.sitemaps (checkbox)
        if ( isset( $in['modules']['sitemaps'] ) ) {
            $out['modules']['sitemaps'] = (int) (bool) $in['modules']['sitemaps'];
        }

        // Known key: compatibility_override (checkbox)
        if ( isset( $in['compatibility_override'] ) ) {
            $out['compatibility_override'] = (int) (bool) $in['compatibility_override'];
        }

        return $out;
    }

    /** Render: Title Separator field. */
    public function field_separator(): void
    {
        $opts = get_option( 'tsc_settings', [] );
        $val  = isset( $opts['sep'] ) ? (string) $opts['sep'] : '–';

        printf(
            '<input type="text" id="tsc_sep" name="tsc_settings[sep]" value="%s" class="regular-text" maxlength="3" />',
            esc_attr( $val )
        );
        echo '<p class="description">' .
            esc_html__( 'Character placed between parts of the title. Example: "Post Title – Site Name".', 'theme-seo-core' ) .
            '</p>';
    }

    /** Render: Modules → Sitemaps toggle. */
    public function field_modules_sitemaps(): void
    {
        $opts = get_option( 'tsc_settings', [] );
        $val  = isset( $opts['modules']['sitemaps'] ) ? (int) $opts['modules']['sitemaps'] : 0;

        printf(
            '<label><input type="checkbox" id="tsc_modules_sitemaps" name="tsc_settings[modules][sitemaps]" value="1" %s /> %s</label>',
            checked( 1, $val, false ),
            esc_html__( 'Enable XML sitemaps (optional)', 'theme-seo-core' )
        );
        echo '<p class="description">' .
            esc_html__( 'Off by default to avoid conflicts with WordPress core or other SEO plugins.', 'theme-seo-core' ) .
            '</p>';
    }

    /** Render: Compatibility → Override toggle. */
    public function field_compat_override(): void
    {
        $opts = get_option( 'tsc_settings', [] );
        $val  = ! empty( $opts['compatibility_override'] ) ? 1 : 0;

        printf(
            '<label><input type="checkbox" id="tsc_compat_override" name="tsc_settings[compatibility_override]" value="1" %s /> %s</label>',
            checked( 1, $val, false ),
            esc_html__( 'Force Theme SEO Core to output Meta/OG/Schema even if another SEO plugin is active (not recommended).', 'theme-seo-core' )
        );
    }
}

