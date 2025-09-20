<?php
/**
 * General settings section (rendered inside the main settings page).
 *
 * Expected variables:
 * - array $settings
 *
 * @package ThemeSeoCore
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$settings = isset( $settings ) && is_array( $settings ) ? $settings : array();
$separator = $settings['separator'] ?? '–';
$noindex_search = ! empty( $settings['noindex_search'] );
?>
<section class="tsc-card">
	<h2><?php esc_html_e( 'General', 'theme-seo-core' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Global SEO preferences.', 'theme-seo-core' ); ?></p>

	<div class="tsc-field">
		<label for="tsc_separator"><?php esc_html_e( 'Title Separator', 'theme-seo-core' ); ?></label>
		<div>
			<input type="text"
				id="tsc_separator"
				name="tsc_settings[separator]"
				value="<?php echo esc_attr( $separator ); ?>"
				maxlength="10"
				placeholder="–"
			/>
			<p class="tsc-help"><?php esc_html_e( 'Character or text between title parts.', 'theme-seo-core' ); ?></p>
		</div>
	</div>

	<div class="tsc-field">
		<label for="tsc_noindex_search"><?php esc_html_e( 'Noindex Search Results', 'theme-seo-core' ); ?></label>
		<div>
			<input type="hidden" name="tsc_settings[noindex_search]" value="0"/>
			<label class="tsc-toggle" for="tsc_noindex_search">
				<input type="checkbox" id="tsc_noindex_search" name="tsc_settings[noindex_search]" value="1" <?php checked( $noindex_search ); ?> />
				<span class="tsc-knob"></span>
				<span class="tsc-bg" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Noindex Search Results', 'theme-seo-core' ); ?></span>
			</label>
			<p class="tsc-help"><?php esc_html_e( 'Adds meta robots noindex to /?s= queries.', 'theme-seo-core' ); ?></p>
		</div>
	</div>
</section>

