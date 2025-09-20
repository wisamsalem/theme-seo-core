<?php
/**
 * Admin Notice component
 *
 * Expected variables:
 * - string $type        One of: 'success', 'error', 'warning', 'info' (default 'info')
 * - string $message     HTML allowed (sanitized via wp_kses)
 * - bool   $dismissible Whether the notice is dismissible (default true)
 * - string $id          Optional DOM id
 *
 * @package ThemeSeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$type        = $type ?? 'info';
$dismissible = isset( $dismissible ) ? (bool) $dismissible : true;
$id_attr     = ! empty( $id ) ? ' id="' . esc_attr( $id ) . '"' : '';

$allowed = array(
	'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
	'strong' => array(),
	'em'     => array(),
	'code'   => array(),
	'br'     => array(),
	'span'   => array( 'class' => array() ),
);

$types = array(
	'success' => 'notice-success',
	'error'   => 'notice-error',
	'warning' => 'notice-warning',
	'info'    => 'notice-info',
);
$cls = $types[ $type ] ?? $types['info'];
$cls .= $dismissible ? ' is-dismissible' : '';
?>
<div class="notice <?php echo esc_attr( $cls ); ?>"<?php echo $id_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<p><?php echo wp_kses( (string) ( $message ?? '' ), $allowed ); ?></p>
</div>

