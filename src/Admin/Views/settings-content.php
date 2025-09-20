<?php
/**
 * Per-content defaults (titles/descriptions patterns etc.)
 *
 * Expected variables:
 * - array $settings
 *
 * @package ThemeSeoCore
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$settings = isset( $settings ) && is_array( $settings ) ? $settings : array();
$pattern_post   = $settings['patterns']['post']   ?? '%%title%% %%sep%% %%sitename%%';
$pattern_page   = $settings['patterns']['page']   ?? '%%title%% %%sep%% %%sitename%%';
$pattern_archive= $settings['patterns']['archive']?? '%%term_title%% %%sep%% %%sitename%%';
$pattern_home   = $settings['patterns']['home']   ?? '%%sitename%% %%sep%% %%tagline%%';
?>
<section class="tsc-card">
	<h2><?php esc_html_e( 'Content Defaults', 'theme-seo-core' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Title template patterns for different contexts.', 'theme-seo-core' ); ?></p>

	<div class="tsc-field">
		<label for="tsc_pattern_post"><?php esc_html_e( 'Single Post Title', 'theme-seo-core' ); ?></label>
		<div>
			<input type="text" id="tsc_pattern_post" name="tsc_settings[patterns][post]" value="<?php echo esc_attr( $pattern_post ); ?>" maxlength="160" data-counter-max="160"/>
			<p class="tsc-help"><?php esc_html_e( 'Available tags: %%title%%, %%sep%%, %%sitename%%.', 'theme-seo-core' ); ?></p>
		</div>
	</div>

	<div class="tsc-field">
		<label for="tsc_pattern_page"><?php esc_html_e( 'Page Title', 'theme-seo-core' ); ?></label>
		<div>
			<input type="text" id="tsc_pattern_page" name="tsc_settings[patterns][page]" value="<?php echo esc_attr( $pattern_page ); ?>" maxlength="160" data-counter-max="160"/>
			<p class="tsc-help"><?php esc_html_e( 'Available tags: %%title%%, %%sep%%, %%sitename%%.', 'theme-seo-core' ); ?></p>
		</div>
	</div>

	<div class="tsc-field">
		<label for="tsc_pattern_archive"><?php esc_html_e( 'Archive Title', 'theme-seo-core' ); ?></label>
		<div>
			<input type="text" id="tsc_pattern_archive" name="tsc_settings[patterns][archive]" value="<?php echo esc_attr( $pattern_archive ); ?>" maxlength="160" data-counter-max="160"/>
			<p class="tsc-help"><?php esc_html_e( 'Available tags: %%term_title%%, %%sep%%, %%sitename%%.', 'theme-seo-core' ); ?></p>
		</div>
	</div>

	<div class="tsc-field">
		<label for="tsc_pattern_home"><?php esc_html_e( 'Homepage Title', 'theme-seo-core' ); ?></label>
		<div>
			<input type="text" id="tsc_pattern_home" name="tsc_settings[patterns][home]" value="<?php echo esc_attr( $pattern_home ); ?>" maxlength="160" data-counter-max="160"/>
			<p class="tsc-help"><?php esc_html_e( 'Available tags: %%sitename%%, %%sep%%, %%tagline%%.', 'theme-seo-core' ); ?></p>
		</div>
	</div>
</section>

