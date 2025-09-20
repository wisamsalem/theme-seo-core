<?php
namespace ThemeSeoCore\Modules\Robots;

use ThemeSeoCore\Security\Nonces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI for editing robots.txt rules.
 */
class Editor {

	const OPTION = RobotsRenderer::OPTION;

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage robots.txt.', 'theme-seo-core' ), 403 );
		}

		$settings = get_option( self::OPTION, array() );
		$settings = is_array( $settings ) ? $settings : array();

		$disallow       = (string) ( $settings['disallow'] ?? "/wp-admin/\n/wp-includes/\n" );
		$allow          = (string) ( $settings['allow'] ?? "/wp-admin/admin-ajax.php\n" );
		$crawl_delay    = (int) ( $settings['crawl_delay'] ?? 0 );
		$include_sitemap= ! empty( $settings['include_sitemap'] );
		$extra          = (string) ( $settings['extra'] ?? '' );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Robots.txt', 'theme-seo-core' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Customize the virtual robots.txt served by WordPress.', 'theme-seo-core' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tsc_save_robots" />
				<?php Nonces::field( 'save_robots' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="tsc_robots_disallow"><?php esc_html_e( 'Disallow rules', 'theme-seo-core' ); ?></label></th>
						<td>
							<textarea id="tsc_robots_disallow" name="tsc_robots[disallow]" rows="8" class="large-text code" placeholder="/private/&#10;/tmp/"><?php echo esc_textarea( $disallow ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One path per line. Lines beginning with "#" are comments.', 'theme-seo-core' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="tsc_robots_allow"><?php esc_html_e( 'Allow rules', 'theme-seo-core' ); ?></label></th>
						<td>
							<textarea id="tsc_robots_allow" name="tsc_robots[allow]" rows="4" class="large-text code" placeholder="/wp-admin/admin-ajax.php"><?php echo esc_textarea( $allow ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Optional. One path per line that should be allowed explicitly.', 'theme-seo-core' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="tsc_robots_crawl_delay"><?php esc_html_e( 'Crawl-delay', 'theme-seo-core' ); ?></label></th>
						<td>
							<input type="number" min="0" step="1" id="tsc_robots_crawl_delay" name="tsc_robots[crawl_delay]" value="<?php echo esc_attr( (string) $crawl_delay ); ?>" class="small-text" />
							<p class="description"><?php esc_html_e( 'Optional seconds between requests (not supported by all crawlers).', 'theme-seo-core' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Sitemap line', 'theme-seo-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="tsc_robots[include_sitemap]" value="1" <?php checked( $include_sitemap ); ?> />
								<?php esc_html_e( 'Include "Sitemap: /sitemap.xml"', 'theme-seo-core' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="tsc_robots_extra"><?php esc_html_e( 'Extra lines', 'theme-seo-core' ); ?></label></th>
						<td>
							<textarea id="tsc_robots_extra" name="tsc_robots[extra]" rows="4" class="large-text code" placeholder="User-agent: BadBot&#10;Disallow: /"><?php echo esc_textarea( $extra ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Additional raw lines appended at the end. Use with care.', 'theme-seo-core' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Robots.txt', 'theme-seo-core' ) ); ?>
			</form>

			<h2 style="margin-top:2em"><?php esc_html_e( 'Preview', 'theme-seo-core' ); ?></h2>
			<pre class="code" style="padding:12px;background:#fff;border:1px solid #e5e7eb;max-width:900px;overflow:auto"><?php
				$preview = ( new RobotsRenderer() )->render( '', (bool) get_option( 'blog_public', 1 ) );
				echo esc_html( $preview );
			?></pre>
			<p><a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open live robots.txt', 'theme-seo-core' ); ?></a></p>
		</div>
		<?php
	}

	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage robots.txt.', 'theme-seo-core' ), 403 );
		}
		if ( ! Nonces::verify( 'save_robots' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'theme-seo-core' ), 403 );
		}

		$in = isset( $_POST['tsc_robots'] ) ? wp_unslash( $_POST['tsc_robots'] ) : array(); // phpcs:ignore WordPress.Security
		$in = is_array( $in ) ? $in : array();

		$clean = array(
			'disallow'        => isset( $in['disallow'] ) ? $this->sanitize_textarea( $in['disallow'] ) : '',
			'allow'           => isset( $in['allow'] ) ? $this->sanitize_textarea( $in['allow'] ) : '',
			'crawl_delay'     => isset( $in['crawl_delay'] ) ? max( 0, (int) $in['crawl_delay'] ) : 0,
			'include_sitemap' => ! empty( $in['include_sitemap'] ),
			'extra'           => isset( $in['extra'] ) ? $this->sanitize_textarea( $in['extra'], false ) : '',
		);

		update_option( self::OPTION, $clean );

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => 'theme-seo-robots', 'updated' => '1' ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	protected function sanitize_textarea( $text, $strip_comments = true ): string {
		$text = (string) $text;
		$lines = preg_split( '/\r\n|\r|\n/', $text );
		$out = array();

		foreach ( $lines as $line ) {
			$line = (string) $line;
			if ( $strip_comments ) {
				$line = preg_replace( '/#.*/', '', $line );
			}
			$line = trim( $line );
			// Only allow basic ASCII plus common URL/path chars.
			$line = preg_replace( '/[^A-Za-z0-9_\-\/\.\*\?\=\:\s]/u', '', $line );
			$out[] = $line;
		}

		$out = array_filter( $out, 'strlen' );
		return implode( "\n", $out );
	}
}

