<?php
/**
 * Module toggle card (used inside settings page module grid).
 *
 * Expected variables:
 * - string $slug
 * - string $title
 * - string $description
 * - bool   $enabled
 * - string $badge (optional)
 *
 * @package ThemeSeoCore
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$slug  = isset( $slug ) ? sanitize_key( $slug ) : '';
$title = isset( $title ) ? (string) $title : '';
$desc  = isset( $description ) ? (string) $description : '';
$on    = ! empty( $enabled );
$toggle_id = 'tsc-module-' . sanitize_html_class( $slug );
$panel_id  = 'tsc-panel-' . sanitize_html_class( $slug );
?>
<div class="tsc-module">
	<div>
		<strong><?php echo esc_html( $title ); ?></strong>
		<?php if ( ! empty( $badge ) ) : ?>
			<span class="tsc-badge"><?php echo esc_html( (string) $badge ); ?></span>
		<?php endif; ?>
	</div>

	<label class="tsc-toggle" for="<?php echo esc_attr( $toggle_id ); ?>">
		<input id="<?php echo esc_attr( $toggle_id ); ?>"
			name="tsc_settings[modules][<?php echo esc_attr( $slug ); ?>]"
			type="checkbox"
			value="1"
			<?php checked( $on ); ?>
			data-module-toggle="<?php echo esc_attr( $slug ); ?>" />
		<span class="tsc-knob"></span>
		<span class="tsc-bg" aria-hidden="true"></span>
		<span class="screen-reader-text">
			<?php
			/* translators: %s: module name */
			printf( esc_html__( 'Toggle %s module', 'theme-seo-core' ), esc_html( $title ) );
			?>
		</span>
	</label>

	<?php if ( $desc ) : ?>
		<p class="tsc-module-desc"><?php echo esc_html( $desc ); ?></p>
	<?php endif; ?>

	<div id="<?php echo esc_attr( $panel_id ); ?>" class="tsc-module-panel" <?php echo $on ? '' : 'hidden'; ?>></div>
</div>

