<?php
namespace ThemeSeoCore\Modules\LinkManager;

use ThemeSeoCore\Support\BaseModule;
use ThemeSeoCore\Security\Nonces;
use ThemeSeoCore\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Link Manager Module
 *
 * - Suggests internal links on post edit screens.
 * - Adds rel="nofollow|sponsored|ugc" to outbound links based on rules.
 */
class Module extends BaseModule {

	protected static $slug = 'link-manager';
	protected static $title = 'Link Manager';
	protected static $description = 'Internal link suggestions and external rel rules.';

	/** @var InternalLinker */
	protected $linker;

	/** @var ExternalRel */
	protected $external;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		$this->linker  = new InternalLinker();
		$this->external = new ExternalRel();

		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			// External rel processing (front-end and RSS)
			'the_content'     => [ [ $this->external, 'filter_content' ], 11, 1, 'filter' ],
			'the_excerpt'     => [ [ $this->external, 'filter_content' ], 11, 1, 'filter' ],
			'comment_text'    => [ [ $this->external, 'filter_comment' ], 11, 1, 'filter' ],

			// Admin: meta box & AJAX for suggestions
			'add_meta_boxes'  => 'add_meta_box',
			'wp_ajax_tsc_link_suggestions' => 'ajax_suggestions',
		];
	}

	/* ---------------- Admin (suggestions UI) ---------------- */

	public function add_meta_box(): void {
		$screens = get_post_types( array( 'public' => true ), 'names' );
		foreach ( $screens as $pt ) {
			add_meta_box(
				'tsc_internal_links',
				__( 'Internal Link Suggestions', 'theme-seo-core' ),
				[ $this, 'render_box' ],
				$pt,
				'side',
				'default'
			);
		}
	}

	public function render_box( \WP_Post $post ): void {
		Nonces::field( 'link_suggestions' );
		echo '<div id="tsc-link-suggestions"><em>' . esc_html__( 'Loading…', 'theme-seo-core' ) . '</em></div>';
		// Small inline JS to fetch via AJAX.
		$ajax = admin_url( 'admin-ajax.php' );
		$nonce = Nonces::create( 'link_suggestions' );
		$js = <<<JS
(function(){
  function render(items){
    var wrap = document.getElementById('tsc-link-suggestions');
    if (!wrap) return;
    if (!items || !items.length){
      wrap.innerHTML = '<em>No suggestions yet.</em>';
      return;
    }
    var html = '<ul>';
    items.forEach(function(it){
      html += '<li><a href="'+it.url+'" target="_blank" rel="noopener">'+it.title+'</a>' +
              ' <small style="opacity:.7">(' + it.score.toFixed(2) + ')</small></li>';
    });
    html += '</ul>';
    wrap.innerHTML = html;
  }
  fetch('{$ajax}?action=tsc_link_suggestions&post=' + (document.getElementById('post_ID')?.value||0) + '&_tsc_nonce={$nonce}')
    .then(function(r){return r.json()})
    .then(function(json){ if(json && json.success){ render(json.items||[]);} else { throw new Error(); } })
    .catch(function(){ var wrap=document.getElementById('tsc-link-suggestions'); if(wrap) wrap.innerHTML='<em>Unable to load.</em>'; });
})();
JS;
		echo '<script>' . $js . '</script>';
	}

	public function ajax_suggestions(): void {
		if ( ! current_user_can( Capabilities::manage_seo() ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
		}
		if ( ! Nonces::verify( 'link_suggestions' ) ) {
			wp_send_json_error( [ 'message' => 'Bad nonce.' ], 403 );
		}
		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0; // phpcs:ignore
		$limit   = isset( $_GET['limit'] ) ? (int) $_GET['limit'] : 10; // phpcs:ignore
		$items   = $this->linker->suggest( $post_id, $limit );
		wp_send_json_success( [ 'items' => $items ] );
	}
}

