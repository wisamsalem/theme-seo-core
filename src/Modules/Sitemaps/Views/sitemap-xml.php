<?php
/**
 * Shared XML view for sitemap index and urlsets.
 *
 * Inputs:
 * - string $kind        'index' or 'urlset'
 * - array  $entries     For 'index': [ ['loc'=>..., 'lastmod'=>...], ... ]
 *                       For 'urlset': [ ['loc'=>..., 'lastmod'=>..., 'changefreq'=>..., 'priority'=>..., 'images'=>[ ['loc'=>..., 'title'=>...], ... ] ], ... ]
 * - string $stylesheet  Absolute URL to XSL (optional)
 * - int    $total_pages (optional) for urlset
 * - int    $current_page (optional) for urlset
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
if ( ! empty( $stylesheet ) ) :
	echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( $stylesheet ) . '"?>' . "\n";
endif;

if ( 'index' === $kind ) : ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<?php foreach ( (array) $entries as $e ) : ?>
	<sitemap>
		<loc><?php echo esc_url( $e['loc'] ); ?></loc>
		<?php if ( ! empty( $e['lastmod'] ) ) : ?>
		<lastmod><?php echo esc_html( $e['lastmod'] ); ?></lastmod>
		<?php endif; ?>
	</sitemap>
	<?php endforeach; ?>
</sitemapindex>
<?php else : ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
	<?php foreach ( (array) $entries as $e ) : ?>
	<url>
		<loc><?php echo esc_url( $e['loc'] ); ?></loc>
		<?php if ( ! empty( $e['lastmod'] ) ) : ?>
		<lastmod><?php echo esc_html( $e['lastmod'] ); ?></lastmod>
		<?php endif; ?>
		<?php if ( ! empty( $e['changefreq'] ) ) : ?>
		<changefreq><?php echo esc_html( $e['changefreq'] ); ?></changefreq>
		<?php endif; ?>
		<?php if ( ! empty( $e['priority'] ) ) : ?>
		<priority><?php echo esc_html( $e['priority'] ); ?></priority>
		<?php endif; ?>

		<?php if ( ! empty( $e['images'] ) && is_array( $e['images'] ) ) : ?>
			<?php foreach ( $e['images'] as $img ) : if ( empty( $img['loc'] ) ) continue; ?>
			<image:image>
				<image:loc><?php echo esc_url( $img['loc'] ); ?></image:loc>
				<?php if ( ! empty( $img['title'] ) ) : ?>
				<image:title><![CDATA[<?php echo wp_kses_post( $img['title'] ); ?>]]></image:title>
				<?php endif; ?>
			</image:image>
			<?php endforeach; ?>
		<?php endif; ?>
	</url>
	<?php endforeach; ?>
</urlset>
<?php endif; ?>

