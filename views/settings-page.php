<?php
/**
 * Settings Page View
 *
 * Expected variables (set by your controller):
 * - string $page_title
 * - array  $modules      Each: [ 'slug' => 'schema', 'title' => 'Schema', 'description' => '…', 'enabled' => true ]
 * - array  $sections     Each: [ 'id' => 'general', 'title' => 'General', 'fields' => [ [ ...field spec... ] ] ]
 * - string $option_group (optional) e.g. 'tsc_settings' for settings_fields()
 * - string $submit_label (optional)
 *
 * Uses components in views/components.
 *
 * @package ThemeSeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$submit_label = $submit_label ?? __( 'Save Changes', 'theme-seo-core' );
?>
<div class="wrap tsc-admin">
	<h1 class="wp-heading-inline tsc-title"><?php echo esc_html( $page_title ?? __( 'Theme SEO Core', 'theme-seo-core' ) ); ?></h1>

	<div id="tsc-inline-notice" class="notice" hidden></div>

	<div class="tsc-wrap">
		<?php if ( ! empty( $modules ) && is_array( $modules ) ) : ?>
			<div class="tsc-card" style="margin-bottom:16px;">
				<h2><?php esc_html_e( 'Modules', 'theme-seo-core' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Enable or disable individual SEO modules. Changes are saved when you click “Save Changes”.', 'theme-seo-core' ); ?></p>

				<p>
					<label for="tsc-module-search" class="screen-reader-text"><?php esc_html_e( 'Search modules', 'theme-seo-core' ); ?></label>
					<input id="tsc-module-search" type="search" placeholder="<?php esc_attr_e( 'Search modules…', 'theme-seo-core' ); ?>" style="max-width:320px;">
				</p>

				<div class="tsc-modules">
					<?php foreach ( $modules as $m ) :
						$slug        = $m['slug'] ?? '';
						$title       = $m['title'] ?? '';
						$desc        = $m['description'] ?? '';
						$enabled     = ! empty( $m['enabled'] );
						$toggle_id   = 'tsc-module-' . sanitize_html_class( $slug );
						$panel_id    = 'tsc-panel-' . sanitize_html_class( $slug );
						?>
						<div class="tsc-module">
							<div>
								<strong><?php echo esc_html( $title ); ?></strong>
								<?php if ( ! empty( $m['badge'] ) ) : ?>
									<span class="tsc-badge"><?php echo esc_html( $m['badge'] ); ?></span>
								<?php endif; ?>
							</div>

							<label class="tsc-toggle" for="<?php echo esc_attr( $toggle_id ); ?>">
								<input id="<?php echo esc_attr( $toggle_id ); ?>"
									name="tsc_modules[<?php echo esc_attr( $slug ); ?>]"
									type="checkbox"
									value="1"
									<?php checked( $enabled ); ?>
									data-module-toggle="<?php echo esc_attr( $slug ); ?>" />
								<span class="tsc-knob"></span>
								<span class="tsc-bg" aria-hidden="true"></span>
								<span class="screen-reader-text">
									<?php
									/* translators: %s: module name */
									echo esc_html( sprintf( __( 'Toggle %s module', 'theme-seo-core' ), $title ) );
									?>
								</span>
							</label>

							<?php if ( $desc ) : ?>
								<p class="tsc-module-desc"><?php echo esc_html( $desc ); ?></p>
							<?php endif; ?>

							<?php
							// Optional per-module settings panel placeholder (controller can populate via actions).
							?>
							<div id="<?php echo esc_attr( $panel_id ); ?>" class="tsc-module-panel" <?php echo $enabled ? '' : 'hidden'; ?>></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<form id="tsc-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="tsc_save_settings"/>
			<?php
			// Nonce for AJAX and admin-post fallback.
			wp_nonce_field( 'tsc_save_settings', '_tsc_nonce' );

			// If using Settings API, output fields for your group.
			if ( ! empty( $option_group ) ) {
				settings_fields( $option_group );
				// do_settings_sections() can be used by the controller instead of manual $sections rendering.
			}
			?>

			<?php if ( ! empty( $sections ) && is_array( $sections ) ) : ?>
				<div class="tsc-grid">
					<?php foreach ( $sections as $section ) : ?>
						<section class="tsc-card">
							<?php if ( ! empty( $section['title'] ) ) : ?>
								<h2><?php echo esc_html( $section['title'] ); ?></h2>
							<?php endif; ?>

							<?php if ( ! empty( $section['description'] ) ) : ?>
								<p class="description"><?php echo esc_html( $section['description'] ); ?></p>
							<?php endif; ?>

							<?php if ( ! empty( $section['fields'] ) && is_array( $section['fields'] ) ) : ?>
								<?php foreach ( $section['fields'] as $field ) :
									$type = $field['type'] ?? 'text';
									$path = TSC_PATH . 'views/components/';
									if ( 'toggle' === $type ) {
										include $path . 'field-toggle.php';
									} else {
										include $path . 'field-text.php';
									}
								endforeach; ?>
							<?php endif; ?>
						</section>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="tsc-actions">
				<button type="submit" class="button button-primary"><?php echo esc_html( $submit_label ); ?></button>
				<button type="reset" class="button"><?php esc_html_e( 'Reset', 'theme-seo-core' ); ?></button>
			</div>
		</form>
	</div>
</div>

