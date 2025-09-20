<?php
/**
 * Text-like field component (text, url, number, etc.)
 *
 * Expected $field keys:
 * - id          (string, required)
 * - name        (string, required)
 * - label       (string, required)
 * - value       (mixed)
 * - placeholder (string)
 * - help        (string) small help text
 * - type        (string) default 'text' (supports 'text','url','number','email','password')
 * - required    (bool)
 * - maxlength   (int) if set also renders a live counter (admin.js reads data-counter-max)
 * - attrs       (array) extra attributes key => value
 *
 * @package ThemeSeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$id          = (string) ( $field['id'] ?? '' );
$name        = (string) ( $field['name'] ?? '' );
$label       = (string) ( $field['label'] ?? '' );
$value       = isset( $field['value'] ) ? $field['value'] : '';
$placeholder = isset( $field['placeholder'] ) ? (string) $field['placeholder'] : '';
$type        = (string) ( $field['type'] ?? 'text' );
$help        = isset( $field['help'] ) ? (string) $field['help'] : '';
$required    = ! empty( $field['required'] );
$maxlength   = isset( $field['maxlength'] ) ? (int) $field['maxlength'] : 0;
$attrs       = isset( $field['attrs'] ) && is_array( $field['attrs'] ) ? $field['attrs'] : [];

if ( ! $id || ! $name ) {
	return;
}

// Build attribute string.
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
		<input
			type="<?php echo esc_attr( $type ); ?>"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( is_scalar( $value ) ? (string) $value : '' ); ?>"
			<?php echo $placeholder ? 'placeholder="' . esc_attr( $placeholder ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $required ? 'required' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $maxlength ? 'maxlength="' . (int) $maxlength . '" data-counter-max="' . (int) $maxlength . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $attr_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		/>
		<?php if ( $help ) : ?>
			<p class="tsc-help"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</div>
</div>

