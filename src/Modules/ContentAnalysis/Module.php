<?php
namespace ThemeSeoCore\Modules\ContentAnalysis;

use ThemeSeoCore\Support\BaseModule;
use ThemeSeoCore\Security\Nonces;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Content Analysis Module
 *
 * - Meta box on post edit to show analysis results
 * - AJAX endpoint to analyze current content (unsaved editor content can be passed)
 */
class Module extends BaseModule {

	protected static $slug = 'content-analysis';
	protected static $title = 'Content Analysis';
	protected static $description = 'Inline SEO & readability checks while you write.';

	/** @var Analyzer */
	protected $analyzer;

	/** @var Readability */
	protected $readability;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		$this->analyzer    = new Analyzer();
		$this->readability = new Readability();

		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			'add_meta_boxes' => 'add_meta_box',
			'admin_enqueue_scripts' => 'admin_assets',
			'wp_ajax_tsc_analyze_content' => 'ajax_analyze',
		];
	}

	public function add_meta_box(): void {
		foreach ( get_post_types( [ 'public' => true ], 'names' ) as $pt ) {
			add_meta_box(
				'tsc_content_analysis',
				__( 'Content Analysis (TSC)', 'theme-seo-core' ),
				[ $this, 'render_box' ],
				$pt,
				'side',
				'default'
			);
		}
	}

	public function render_box( \WP_Post $post ): void {
		Nonces::field( 'tsc_analyze' );
		$ajax = admin_url( 'admin-ajax.php' );
		$nonce = Nonces::create( 'tsc_analyze' );

		echo '<div id="tsc-analysis" class="tsc-analysis" style="font-size:12px;line-height:1.5;">';
		echo '<p><em>' . esc_html__( 'Click "Analyze" to evaluate this draft for SEO & readability.', 'theme-seo-core' ) . '</em></p>';
		echo '<button type="button" class="button" id="tsc-analyze-btn">' . esc_html__( 'Analyze', 'theme-seo-core' ) . '</button>';
		echo '<div id="tsc-analysis-results" style="margin-top:10px;"></div>';
		echo '</div>';

		$script = <<<JS
(function(){
  var btn = document.getElementById('tsc-analyze-btn');
  if(!btn) return;
  btn.addEventListener('click', function(){
    var postId = document.getElementById('post_ID')?.value || 0;
    var titleEl = document.getElementById('title');
    var title = titleEl ? titleEl.value : (window.wp?.data?.select('core/editor')?.getEditedPostAttribute('title') || '');
    var content = '';
    try {
      // Try Gutenberg editor content
      content = window.wp?.data?.select('core/editor')?.getEditedPostContent() || '';
    } catch(e) {}

    var fd = new FormData();
    fd.append('action','tsc_analyze_content');
    fd.append('_tsc_nonce','{$nonce}');
    fd.append('post', postId);
    fd.append('title', title);
    fd.append('content', content);
    fd.append('focus', (document.getElementById('tsc_focus_keyword')?.value || ''));

    var results = document.getElementById('tsc-analysis-results');
    results.innerHTML = '<em>Analyzing…</em>';

    fetch('{$ajax}', { method:'POST', credentials:'same-origin', body: fd })
      .then(r => r.json())
      .then(json => {
        if(!json || !json.success) throw new Error();
        var d = json.data;
        var pills = function(items){
          return items.map(function(it){
            var cls = it.pass ? 'background:#e8f5e9;border-color:#c8e6c9;color:#256029' : 'background:#fff3f3;border-color:#ffcdd2;color:#b71c1c';
            return '<div style="margin:4px 0;padding:6px 8px;border:1px solid;border-radius:6px;'+cls+'"><strong>'+it.label+':</strong> '+it.message+'</div>';
          }).join('');
        };
        var html = '';
        html += '<div><strong>Score:</strong> '+d.score+'/100</div>';
        html += '<div style="margin-top:8px;"><strong>Basics</strong><div>'+pills(d.checks.basics)+'</div></div>';
        html += '<div style="margin-top:8px;"><strong>Headings</strong><div>'+pills(d.checks.headings)+'</div></div>';
        html += '<div style="margin-top:8px;"><strong>Links & Media</strong><div>'+pills(d.checks.links)+'</div></div>';
        html += '<div style="margin-top:8px;"><strong>Keyword</strong><div>'+pills(d.checks.keyword)+'</div></div>';
        html += '<div style="margin-top:8px;"><strong>Readability</strong><div>'+pills(d.checks.readability)+'</div></div>';
        results.innerHTML = html;
      })
      .catch(function(){
        results.innerHTML = '<em>Analysis failed.</em>';
      });
  });
})();
JS;
		echo '<script>' . $script . '</script>';

		// Optional focus keyword input (not stored here; used for ad-hoc analysis).
		echo '<p style="margin-top:10px;"><label for="tsc_focus_keyword"><strong>' . esc_html__( 'Focus Keyword', 'theme-seo-core' ) . '</strong></label><br/>';
		echo '<input type="text" id="tsc_focus_keyword" class="widefat" placeholder="' . esc_attr__( 'e.g., best hiking boots', 'theme-seo-core' ) . '" /></p>';
	}

	public function admin_assets( $hook ): void {
		// Intentionally minimal—no extra assets required.
	}

	public function ajax_analyze(): void {
		if ( ! Nonces::verify( 'tsc_analyze' ) ) {
			wp_send_json_error( [ 'message' => 'Bad nonce' ], 403 );
		}

		$post_id = isset( $_POST['post'] ) ? (int) $_POST['post'] : 0; // phpcs:ignore
		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => 'Cannot edit post' ], 403 );
		} elseif ( ! $post_id && ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Cannot edit posts' ], 403 );
		}

		$title   = isset( $_POST['title'] ) ? wp_unslash( (string) $_POST['title'] ) : '';   // phpcs:ignore
		$content = isset( $_POST['content'] ) ? wp_unslash( (string) $_POST['content'] ) : ''; // phpcs:ignore
		$focus   = isset( $_POST['focus'] ) ? sanitize_text_field( (string) $_POST['focus'] ) : '';

		if ( '' === $content && $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$title   = $title ?: $post->post_title;
				$content = $post->post_content;
			}
		}

		$data  = $this->analyzer->analyze( $title, $content, $focus );
		$read  = $this->readability->evaluate( $content );

		// Merge into a single payload + score.
		$score = $data['score'] * 0.7 + $read['score'] * 0.3;
		$out = [
			'score'  => (int) round( $score ),
			'checks' => [
				'basics'      => $data['checks']['basics'],
				'headings'    => $data['checks']['headings'],
				'links'       => $data['checks']['links'],
				'keyword'     => $data['checks']['keyword'],
				'readability' => $read['checks'],
			],
		];

		wp_send_json_success( $out );
	}
}

