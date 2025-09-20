<?php
namespace ThemeSeoCore\Admin;

use ThemeSeoCore\Container\Container;
use ThemeSeoCore\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers plugin settings, sections, and fields via the WP Settings API.
 * This keeps it minimal so the Settings page always loads.
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
        $cap = Capabilities::manage_seo(); // ← FIX: use method, not a constant

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

        // Known key example: "sep" (title separator)
        if ( isset( $in['sep'] ) ) {
            $out['sep'] = sanitize_text_field( (string) $in['sep'] );
            // basic length guard
            if ( strlen( $out['sep'] ) > 3 ) {
                $out['sep'] = mb_substr( $out['sep'], 0, 3 );
            }
        }

        return $out;
    }

    /**
     * Render: Title Separator field.
     */
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
}
