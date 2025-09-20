<?php
namespace ThemeSeoCore\Modules\Redirects;

use ThemeSeoCore\Security\Nonces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin list/add screen for redirects (under SEO menu).
 */
class Admin {

	/** @var DB */
	protected $db;

	public function __construct( DB $db ) {
		$this->db = $db;
	}

	public function render(): void {
		if ( ! current_user_can( 'tsc_manage_redirects' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage redirects.', 'theme-seo-core' ), 403 );
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

		$table = new class( $this->db ) extends \WP_List_Table {
			protected $db;
			public function __construct( $db ) {
				$this->db = $db;
				parent::__construct( array(
					'singular' => 'tsc_redirect',
					'plural'   => 'tsc_redirects',
					'ajax'     => false,
				) );
			}
			public function get_columns() {
				return array(
					'cb'         => '<input type="checkbox" />',
					'source'     => __( 'Source', 'theme-seo-core' ),
					'target'     => __( 'Target', 'theme-seo-core' ),
					'match_type' => __( 'Type', 'theme-seo-core' ),
					'status'     => __( 'Status', 'theme-seo-core' ),
					'hits'       => __( 'Hits', 'theme-seo-core' ),
					'last_hit'   => __( 'Last Hit', 'theme-seo-core' ),
				);
			}
			protected function column_cb( $item ) {
				printf( '<input type="checkbox" name="ids[]" value="%d" />', (int) $item['id'] );
			}
			protected function column_source( $item ) {
				$edit = wp_nonce_url( add_query_arg( array( 'page'=>'theme-seo-redirects','edit'=>(int)$item['id'] ), admin_url( 'admin.php' ) ), 'tsc_redirects_edit' );
				printf( '<a href="%s"><code>%s</code></a>', esc_url( $edit ), esc_html( $item['source'] ) );
			}
			protected function column_target( $item ) {
				printf( '<code>%s</code>', esc_html( $item['target'] ) );
			}
			protected function column_default( $item, $col ) {
				return esc_html( (string) ( $item[ $col ] ?? '' ) );
			}
			public function get_bulk_actions() {
				return array( 'delete' => __( 'Delete', 'theme-seo-core' ) );
			}
			public function prepare_items() {
				$per_page = 20;
				$paged = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
				$data = $this->db->all( $paged, $per_page );
				$this->items = $data['rows'];
				$this->_column_headers = array( $this->get_columns(), array(), array() );
				$this->set_pagination_args( array(
					'total_items' => (int) $data['total'],
					'per_page'    => $per_page,
				) );
			}
		};

		$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0; // phpcs:ignore
		$editing = $edit_id ? $this->db->get( $edit_id ) : null;

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Redirects', 'theme-seo-core' ); ?></h1>

			<hr class="wp-header-end"/>

			<div class="tsc-grid" style="display:grid;grid-template-columns:1fr 360px;gap:24px;">
				<div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php Nonces::field( 'redirects_bulk' ); ?>
						<input type="hidden" name="action" value="tsc_redirects_bulk" />
						<?php $table->prepare_items(); ?>
						<?php $table->display(); ?>
					</form>
				</div>

				<aside>
					<div class="card">
						<h2><?php echo $editing ? esc_html__( 'Edit Redirect', 'theme-seo-core' ) : esc_html__( 'Add Redirect', 'theme-seo-core' ); ?></h2>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="tsc_redirects_add"/>
							<?php Nonces::field( 'redirects_add' ); ?>
							<?php if ( $editing ) : ?>
								<input type="hidden" name="id" value="<?php echo (int) $editing['id']; ?>"/>
							<?php endif; ?>
							<p>
								<label><?php esc_html_e( 'Source (path or regex)', 'theme-seo-core' ); ?></label>
								<input type="text" name="source" class="regular-text" required
									placeholder="/old-path or #^/old/(.*)$#"
									value="<?php echo esc_attr( $editing['source'] ?? '' ); ?>"/>
							</p>
							<p>
								<label><?php esc_html_e( 'Target URL', 'theme-seo-core' ); ?></label>
								<input type="url" name="target" class="regular-text" required
									placeholder="https://example.com/new-path"
									value="<?php echo esc_attr( $editing['target'] ?? '' ); ?>"/>
							</p>
							<p>
								<label><?php esc_html_e( 'Match Type', 'theme-seo-core' ); ?></label><br/>
								<select name="match_type">
									<?php
									$types = array( 'exact' => 'Exact', 'prefix' => 'Prefix', 'regex' => 'Regex' );
									$sel = $editing['match_type'] ?? 'exact';
									foreach ( $types as $k => $label ) {
										printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $sel, $k, false ), esc_html__( $label, 'theme-seo-core' ) );
									}
									?>
								</select>
							</p>
							<p>
								<label><?php esc_html_e( 'HTTP Status', 'theme-seo-core' ); ?></label><br/>
								<select name="status">
									<?php
									$statuses = array( 301=>'301 Moved Permanently', 302=>'302 Found', 307=>'307 Temporary Redirect', 308=>'308 Permanent Redirect' );
									$sel = (int) ( $editing['status'] ?? 301 );
									foreach ( $statuses as $code => $label ) {
										printf( '<option value="%d"%s>%s</option>', $code, selected( $sel, $code, false ), esc_html( $label ) );
									}
									?>
								</select>
							</p>
							<?php submit_button( $editing ? __( 'Update Redirect', 'theme-seo-core' ) : __( 'Add Redirect', 'theme-seo-core' ) ); ?>
						</form>
					</div>

					<div class="card">
						<h2><?php esc_html_e( 'Import / Export', 'theme-seo-core' ); ?></h2>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="tsc_redirects_export"/>
							<?php submit_button( __( 'Export CSV', 'theme-seo-core' ), 'secondary', 'submit', false ); ?>
						</form>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
							<?php \ThemeSeoCore\Security\Nonces::field( 'redirects_import' ); ?>
							<input type="hidden" name="action" value="tsc_redirects_import"/>
							<input type="file" name="csv" accept=".csv" required/>
							<?php submit_button( __( 'Import CSV', 'theme-seo-core' ), 'secondary', 'submit', false ); ?>
							<p class="description"><?php esc_html_e( 'CSV columns: source,target,status,match_type', 'theme-seo-core' ); ?></p>
						</form>
					</div>
				</aside>
			</div>
		</div>
		<?php
	}

	public function handle_add(): void {
		if ( ! current_user_can( 'tsc_manage_redirects' ) || ! Nonces::verify( 'redirects_add' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'theme-seo-core' ), 403 );
		}
		$id   = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0; // phpcs:ignore
		$src  = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$tgt  = isset( $_POST['target'] ) ? esc_url_raw( wp_unslash( $_POST['target'] ) ) : '';
		$type = isset( $_POST['match_type'] ) ? sanitize_text_field( wp_unslash( $_POST['match_type'] ) ) : 'exact';
		$st   = isset( $_POST['status'] ) ? (int) $_POST['status'] : 301;

		if ( $id ) {
			$this->db->update( $id, compact( 'source','target','match_type','status' ) );
		} else {
			$this->db->insert( compact( 'source','target','match_type','status' ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=theme-seo-redirects' ) );
		exit;
	}

	public function handle_bulk(): void {
		if ( ! current_user_can( 'tsc_manage_redirects' ) || ! Nonces::verify( 'redirects_bulk' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'theme-seo-core' ), 403 );
		}
		$action = $_POST['action'] ?? ''; // phpcs:ignore
		$ids    = isset( $_POST['ids'] ) ? array_map( 'intval', (array) $_POST['ids'] ) : array(); // phpcs:ignore
		if ( 'delete' === $action && $ids ) {
			$this->db->bulk_delete( $ids );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=theme-seo-redirects' ) );
		exit;
	}
}

