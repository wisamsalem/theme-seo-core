<?php
/**
 * Toggle (checkbox) field component
 *
 * Expected $field keys:
 * - id       (string, required)
 * - name     (string, required)
 * - label    (string, required)
 * - checked  (bool)
 * - help     (string)
 * - attrs    (array) extra attributes on the checkbox
 *
 * Sends hidden "0" + checkbox "1" to ensure a value is always posted.
 *
 * @package ThemeSeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$id      = (string) ( $field['id'] ?? '' );
$name    = (string) ( $field['name'] ?? '' );
$label   = (string) ( $field['label'] ?? '' );
$checked = ! empty( $field['checked'] );
$help    = isset( $field['help'] ) ? (string) $field['help'] : '';
$attrs   = isset( $field['attrs'] ) && is_array( $field['attrs'] ) ? $field['attrs'] : [];

if ( ! $id || ! $name ) {
	return;
}

$attr_html = '';
foreach ( $attrs as $k => $v ) {
	if ( is_bool( $v ) ) {
		if ( $v ) {
			$attr_html .= ' ' . esc_attr( $k );
		}
	} else {
		$attr_html .= ' ' . esc_attr( $k ) . '="' . esc_attr( (string) $v ) . '"';
	}
}
?>
<div class="tsc-field">
	<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
	<div>
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0" />
		<label class="tsc-toggle" for="<?php echo esc_attr( $id ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				value="1"
				<?php checked( $checked ); ?>
				<?php echo $attr_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			/>
			<span class="tsc-knob"></span>
			<span class="tsc-bg" aria-hidden="true"></span>
			<span class="screen-reader-text">
				<?php echo esc_html( $label ); ?>
			</span>
		</label>
		<?php if ( $help ) : ?>
			<p class="tsc-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</div>
</div>

