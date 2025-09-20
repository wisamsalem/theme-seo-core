# Images for Theme SEO Core

This folder contains SVG sources for icons and share art. Export PNGs where required.

## Files
- `plugin-icon.svg` — Square app-style icon used in admin screens, plugin header, etc.
- `plugin-logo-horizontal.svg` — Horizontal lockup (icon + wordmark) for docs/marketing.
- `favicon.svg` — Modern scalable favicon. Generate `.ico` from it for legacy.
- `mask-icon.svg` — Monochrome Safari pinned tab icon. Reference with `color` in HTML.
- `og-default-source.svg` — **Export to PNG 1200×630** for Open Graph/Twitter Card fallback.

## Recommended PNG/ICO exports
- Favicon `.ico`: 16×16, 32×32, 48×48 combined.
- Favicon PNGs: 180×180 (apple-touch-icon), 192×192, 512×512.
- OG/Twitter share: 1200×630 PNG (`og-default.png`).
- Web App Manifest icons: 192×192, 512×512 PNGs.

## WordPress snippets

### 1) Use OG default image in your plugin
```php
add_filter( 'tsc/schema/og_defaults', function ( $defaults ) {
    $defaults['image'] = TSC_URL . 'assets/images/og-default.png'; // export first
    return $defaults;
} );

