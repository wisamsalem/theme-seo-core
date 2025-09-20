/**
 * Theme SEO Core – Admin JS
 * Assumes a localized object `tscAdmin`:
 * {
 *   ajaxUrl: string,
 *   nonce:   string,
 *   i18n: { saved: 'Settings saved.', error: 'Error saving.' }
 * }
 */
(function (w, d) {
	'use strict';

	const $ = (sel, root = d) => root.querySelector(sel);
	const $$ = (sel, root = d) => Array.from(root.querySelectorAll(sel));

	function on(el, ev, fn, opts) {
		el && el.addEventListener(ev, fn, opts || false);
	}

	// Module toggle: enable/disable detail panels
	function bindModuleToggles() {
		$$('.tsc-module [data-module-toggle]').forEach((input) => {
			on(input, 'change', () => {
				const slug = input.getAttribute('data-module-toggle');
				const panel = d.getElementById(`tsc-panel-${slug}`);
				if (panel) panel.hidden = !input.checked;
			});
		});
	}

	// Character counters for SEO title/description fields
	function bindCounters() {
		$$('[data-counter-max]').forEach((el) => {
			const max = parseInt(el.getAttribute('data-counter-max'), 10) || 0;
			const wrap = el.closest('.tsc-field') || el.parentElement;
			const counter = d.createElement('span');
			counter.className = 'tsc-counter';
			wrap && wrap.appendChild(counter);

			const update = () => {
				const len = (el.value || '').trim().length;
				counter.textContent = `${len}/${max}`;
				counter.style.color = len > max ? '#b91c1c' : '';
			};
			on(el, 'input', update);
			update();
		});
	}

	// Filter modules by search
	function bindModuleSearch() {
		const input = $('#tsc-module-search');
		if (!input) return;
		on(input, 'input', () => {
			const q = input.value.toLowerCase();
			$$('.tsc-module').forEach((card) => {
				const txt = card.textContent.toLowerCase();
				card.style.display = txt.includes(q) ? '' : 'none';
			});
		});
	}

	// AJAX save (works with a WP action hook on the backend)
	async function saveSettings(form) {
		const data = new FormData(form);
		data.append('action', 'tsc_save_settings');
		if (w.tscAdmin && w.tscAdmin.nonce) {
			data.append('_wpnonce', w.tscAdmin.nonce);
		}

		const res = await fetch((w.tscAdmin && w.tscAdmin.ajaxUrl) || w.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data
		});
		if (!res.ok) throw new Error('Network error');
		return res.json();
	}

	function bindSettingsForm() {
		const form = $('#tsc-settings-form');
		if (!form) return;

		let dirty = false;
		on(form, 'input', () => { dirty = true; });

		on(form, 'submit', async (e) => {
			e.preventDefault();
			const btn = form.querySelector('button[type="submit"], .button-primary');
			const original = btn ? btn.textContent : null;
			if (btn) {
				btn.disabled = true;
				btn.textContent = '…';
			}
			try {
				const json = await saveSettings(form);
				dirty = false;
				wp && wp.data && wp.data.dispatch('core/notices')?.createSuccessNotice?.(
					(w.tscAdmin && w.tscAdmin.i18n && w.tscAdmin.i18n.saved) || 'Settings saved.',
					{ isDismissible: true }
				);
				// Fallback inline notice:
				const fallback = $('#tsc-inline-notice');
				if (fallback) {
					fallback.className = 'notice notice-success';
					fallback.textContent = (w.tscAdmin?.i18n?.saved) || 'Settings saved.';
					fallback.hidden = false;
				}
			} catch (err) {
				wp && wp.data && wp.data.dispatch('core/notices')?.createErrorNotice?.(
					(w.tscAdmin && w.tscAdmin.i18n && w.tscAdmin.i18n.error) || 'Error saving.',
					{ isDismissible: true }
				);
			} finally {
				if (btn) {
					btn.disabled = false;
					btn.textContent = original;
				}
			}
		});

		// Warn when leaving with unsaved changes
		w.addEventListener('beforeunload', (e) => {
			if (!dirty) return;
			e.preventDefault();
			e.returnValue = '';
		});
	}

	// Clipboard helper for fields with data-copy
	function bindCopyButtons() {
		$$('[data-copy-target]').forEach((btn) => {
			on(btn, 'click', async () => {
				const target = $(btn.getAttribute('data-copy-target'));
				if (!target) return;
				try {
					await navigator.clipboard.writeText(target.value || target.textContent || '');
					btn.classList.add('is-copied');
					setTimeout(() => btn.classList.remove('is-copied'), 1000);
				} catch (_) {}
			});
		});
	}

	// Init
	on(d, 'DOMContentLoaded', () => {
		bindModuleToggles();
		bindCounters();
		bindModuleSearch();
		bindSettingsForm();
		bindCopyButtons();
	});
})(window, document);

