/**
 * Theme SEO Core – Public JS
 * Lightweight, no dependencies. Keep it safe and idempotent.
 */
(function (w, d) {
	'use strict';

	const $$ = (sel, root = d) => Array.from(root.querySelectorAll(sel));
	const on = (el, ev, fn, opts) => el && el.addEventListener(ev, fn, opts || false);

	/**
	 * 1) Breadcrumbs: mark last crumb as aria-current="page" if not already.
	 */
	function enhanceBreadcrumbs() {
		$$('.tsc-breadcrumbs ol').forEach((list) => {
			const items = $$('.tsc-breadcrumbs li', list);
			if (!items.length) return;
			const last = items[items.length - 1];
			const link = last.querySelector('a');
			if (link && !link.hasAttribute('aria-current')) {
				link.setAttribute('aria-current', 'page');
			} else if (!link) {
				last.setAttribute('aria-current', 'page');
			}
		});
	}

	/**
	 * 2) External links: add rel="noopener noreferrer" when target=_blank.
	 *    (SEO-safe; prevents reverse tabnabbing; does NOT add nofollow.)
	 */
	function hardenExternalLinks() {
		$$('a[target="_blank"]').forEach((a) => {
			const rel = (a.getAttribute('rel') || '').toLowerCase().split(/\s+/).filter(Boolean);
			if (!rel.includes('noopener')) rel.push('noopener');
			if (!rel.includes('noreferrer')) rel.push('noreferrer');
			a.setAttribute('rel', rel.join(' '));
		});
	}

	/**
	 * 3) Lazy decode images lacking decoding attribute for faster LCP paint.
	 *    (Server-side should add width/height; this is a progressive enhancement.)
	 */
	function hintImageDecoding() {
		$$('img:not([decoding])').forEach((img) => {
			img.setAttribute('decoding', 'async');
		});
	}

	/**
	 * 4) FAQ accordion (schema-compatible): toggles visibility without changing DOM structure.
	 *    HTML structure expected:
	 *    <div class="tsc-faq">
	 *      <div class="tsc-faq-item">
	 *        <button class="tsc-faq-q" aria-expanded="false" aria-controls="a1" id="q1">Question</button>
	 *        <div class="tsc-faq-a" id="a1" role="region" aria-labelledby="q1">Answer</div>
	 *      </div>
	 *    </div>
	 */
	function faqAccordion() {
		$$('.tsc-faq .tsc-faq-q').forEach((btn) => {
			const contentId = btn.getAttribute('aria-controls');
			const content = contentId ? d.getElementById(contentId) : btn.nextElementSibling;
			const item = btn.closest('.tsc-faq-item');
			if (!content || !item) return;

			const open = (state) => {
				item.classList.toggle('is-open', state);
				btn.setAttribute('aria-expanded', state ? 'true' : 'false');
				content.hidden = !state;
			};

			// Init state
			content.hidden = content.hidden ?? true;
			if (item.classList.contains('is-open')) {
				open(true);
			}

			on(btn, 'click', () => open(btn.getAttribute('aria-expanded') !== 'true'));
			on(btn, 'keydown', (e) => {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					btn.click();
				}
			});
		});
	}

	/**
	 * 5) Smooth scroll for hash links while accounting for WP admin bar.
	 */
	function smoothHashScroll() {
		const adminBar = d.getElementById('wpadminbar');
		const offset = adminBar ? adminBar.offsetHeight : 0;

		function scrollToHash(hash) {
			const el = hash && d.getElementById(hash.slice(1));
			if (!el) return;
			const top = el.getBoundingClientRect().top + w.scrollY - offset - 8;
			w.scrollTo({ top, behavior: 'smooth' });
		}

		// On click
		on(d, 'click', (e) => {
			const a = e.target.closest('a[href^="#"]');
			if (!a) return;
			const href = a.getAttribute('href');
			if (href === '#') return;
			const url = new URL(a.href, w.location.href);
			if (url.pathname === w.location.pathname && url.hash) {
				e.preventDefault();
				history.pushState(null, '', url.hash);
				scrollToHash(url.hash);
			}
		});

		// On load with hash
		if (w.location.hash) {
			setTimeout(() => scrollToHash(w.location.hash), 0);
		}
	}

	/**
	 * 6) IntersectionObserver: add `loading="lazy"` to below-the-fold images without it.
	 *    Keeps LCP hero images eager.
	 */
	function lazyLoadBelowFold() {
		if (!('IntersectionObserver' in w)) return;
		const imgs = $$('img:not([loading])');
		if (!imgs.length) return;

		const io = new IntersectionObserver((entries, obs) => {
			entries.forEach((entry) => {
				const img = entry.target;
				if (entry.intersectionRatio === 0) {
					// Outside viewport; ensure lazy
					if (!img.hasAttribute('loading')) img.setAttribute('loading', 'lazy');
				}
				if (entry.isIntersecting) {
					obs.unobserve(img);
				}
			});
		}, { rootMargin: '256px' });

		imgs.forEach((img) => io.observe(img));
	}

	// Init on DOMReady
	on(d, 'DOMContentLoaded', () => {
		enhanceBreadcrumbs();
		hardenExternalLinks();
		hintImageDecoding();
		faqAccordion();
		smoothHashScroll();
		lazyLoadBelowFold();
	});
})(window, document);

