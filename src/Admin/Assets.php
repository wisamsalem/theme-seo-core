<?php
namespace ThemeSeoCore\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues admin assets outside of our settings screen (e.g., metabox helpers).
 */
class Assets {

	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Editor/metabox assets on post screens.
	 *
	 * @param string $hook
	 */
	public function enqueue_editor_assets( string $hook ): void {
		// Post edit screens only.
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		// Reuse admin styles for counters/toggles.
		wp_enqueue_style( 'tsc-admin', TSC_URL . 'assets/admin/css/admin.css', array(), TSC_VERSION );

		// A tiny inline helper: live counters inside metabox.
		$js = <<<JS
document.addEventListener('input', function(e){
  var el = e.target;
  if (!el || !el.dataset || !el.dataset.counterMax) return;
  var wrap = el.closest('p') || el.parentElement;
  if (!wrap) return;
  var max = parseInt(el.dataset.counterMax, 10) || 0;
  var counter = wrap.querySelector('.tsc-counter');
  if (!counter) {
    counter = document.createElement('span');
    counter.className = 'tsc-counter';
    wrap.appendChild(counter);
  }
  var len = (el.value||'').trim().length;
  counter.textContent = len + '/' + max;
  counter.style.color = len > max ? '#b91c1c' : '';
});
JS;
		wp_add_inline_script( 'jquery-core', $js ); // ensure it prints; depends on core-registered handle
	}
}

