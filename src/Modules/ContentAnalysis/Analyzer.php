<?php
namespace ThemeSeoCore\Modules\ContentAnalysis;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Analyzer — quick heuristics for on-page SEO signals.
 */
class Analyzer {

	/**
	 * @param string $title
	 * @param string $content (may include blocks/shortcodes)
	 * @param string $focus   Optional focus keyword/phrase
	 * @return array{
	 *   score:int,
	 *   checks: array{
	 *     basics: array<int,array{label:string,message:string,pass:bool}>,
	 *     headings: array<int,array{label:string,message:string,pass:bool}>,
	 *     links: array<int,array{label:string,message:string,pass:bool}>,
	 *     keyword: array<int,array{label:string,message:string,pass:bool}>
	 *   }
	 * }
	 */
	public function analyze( string $title, string $content, string $focus = '' ): array {
		$html  = $this->render_content( $content ); // render shortcodes minimally
		$text  = $this->to_text( $html );
		$words = $this->word_count( $text );
		$chars = mb_strlen( trim( preg_replace( '/\s+/', ' ', $text ) ) );

		// Headings
		$h = $this->headings( $html );

		// Links/Images
		$linkStats = $this->links_and_media( $html );

		// Keyword density / presence
		$kw = $this->keyword_stats( $title, $text, $focus );

		$checks_basics = [
			$this->check( 'Title length', sprintf( '%d chars', mb_strlen( trim( $title ) ) ), mb_strlen( $title ) >= 30 && mb_strlen( $title ) <= 60 ),
			$this->check( 'Content length', sprintf( '%d words', $words ), $words >= 300 ),
			$this->check( 'Paragraph length', sprintf( 'avg %0.1f sentences/paragraph', $this->avg_sentences_per_paragraph( $text ) ), true ),
			$this->check( 'Images', sprintf( '%d images', $linkStats['images'] ), $linkStats['images'] >= 1 || $words < 300 ),
		];

		$checks_headings = [
			$this->check( 'Single H1', sprintf( '%d H1', $h['h1'] ), $h['h1'] === 1 ),
			$this->check( 'Subheadings (H2/H3)', sprintf( 'H2:%d H3:%d', $h['h2'], $h['h3'] ), ($h['h2'] + $h['h3']) >= 1 || $words < 300 ),
			$this->check( 'Heading length', sprintf( 'avg %0.1f chars', $h['avg_len'] ), $h['avg_len'] <= 80 || 0 === ($h['h1']+$h['h2']+$h['h3']) ),
		];

		$checks_links = [
			$this->check( 'Internal links', sprintf( '%d', $linkStats['internal'] ), $linkStats['internal'] >= 1 || $words < 300 ),
			$this->check( 'External links', sprintf( '%d', $linkStats['external'] ), $linkStats['external'] >= 1 || $words < 300 ),
			$this->check( 'No broken anchors', 'N/A in editor', true ),
		];

		$kwChecks = [
			$this->check( 'Focus in title', $focus ? (mb_stripos( $title, $focus ) !== false ? 'found' : 'missing') : 'n/a', $focus ? (mb_stripos( $title, $focus ) !== false) : true ),
			$this->check( 'Focus in first paragraph', $focus ? ($this->in_first_paragraph( $html, $focus ) ? 'found' : 'missing') : 'n/a', $focus ? $this->in_first_paragraph( $html, $focus ) : true ),
			$this->check( 'Keyword density', $focus ? sprintf( '%0.2f%%', $kw['density'] * 100 ) : 'n/a', $focus ? ($kw['density'] >= 0.005 && $kw['density'] <= 0.03) : true ),
		];

		// Simple scoring (weighted)
		$score = 0;
		foreach ( [ $checks_basics, $checks_headings, $checks_links, $kwChecks ] as $group ) {
			foreach ( $group as $c ) $score += $c['pass'] ? 5 : 0;
		}
		$score = min( 100, max( 0, $score ) );

		return [
			'score'  => $score,
			'checks' => [
				'basics'   => $checks_basics,
				'headings' => $checks_headings,
				'links'    => $checks_links,
				'keyword'  => $kwChecks,
			],
		];
	}

	/* -------------------- helpers -------------------- */

	protected function render_content( string $content ): string {
		// Let WP expand blocks/shortcodes if available.
		if ( function_exists( 'do_blocks' ) ) {
			$content = do_blocks( $content );
		}
		$content = do_shortcode( $content );
		return (string) apply_filters( 'the_content', $content );
	}

	protected function to_text( string $html ): string {
		$t = wp_strip_all_tags( $html );
		$t = html_entity_decode( $t, ENT_QUOTES, 'UTF-8' );
		return trim( preg_replace( '/\s+/', ' ', $t ) );
	}

	protected function word_count( string $text ): int {
		if ( '' === $text ) return 0;
		return str_word_count( $text, 0, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ’' );
	}

	protected function avg_sentences_per_paragraph( string $text ): float {
		$paras = array_values( array_filter( preg_split( '/\n{2,}/', $text ) ?: [] ) );
		if ( empty( $paras ) ) return 0.0;
		$sentences = 0;
		foreach ( $paras as $p ) $sentences += max( 1, preg_match_all( '/[\.!\?]+["\']?\s/u', $p, $m ) );
		return $sentences / max( 1, count( $paras ) );
	}

	protected function headings( string $html ): array {
		$h1 = preg_match_all( '/<h1[^>]*>(.*?)<\/h1>/is', $html, $m1 );
		$h2 = preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', $html, $m2 );
		$h3 = preg_match_all( '/<h3[^>]*>(.*?)<\/h3>/is', $html, $m3 );
		$all = array_merge( $m1[1] ?? [], $m2[1] ?? [], $m3[1] ?? [] );
		$len = 0; $n = 0;
		foreach ( $all as $t ) { $len += mb_strlen( trim( wp_strip_all_tags( $t ) ) ); $n++; }
		return [
			'h1' => (int) $h1,
			'h2' => (int) $h2,
			'h3' => (int) $h3,
			'avg_len' => $n ? $len / $n : 0.0,
		];
	}

	protected function links_and_media( string $html ): array {
		$internal = 0; $external = 0; $images = 0;
		$host = parse_url( home_url( '/' ), PHP_URL_HOST );

		if ( preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\']/i', $html, $m ) ) {
			foreach ( $m[1] as $href ) {
				$h = parse_url( $href, PHP_URL_HOST );
				if ( ! $h || strtolower( $h ) === strtolower( (string) $host ) ) $internal++; else $external++;
			}
		}
		$images = preg_match_all( '/<img\s[^>]*src=["\']([^"\']+)["\']/i', $html ) ?: 0;

		return compact( 'internal', 'external', 'images' );
	}

	protected function keyword_stats( string $title, string $text, string $focus ): array {
		$focus = trim( mb_strtolower( $focus ) );
		if ( '' === $focus ) return [ 'density' => 0.0 ];
		$wc = max( 1, $this->word_count( $text ) );
		$count = 0;
		if ( $focus ) {
			// naive count of phrase occurrences
			$count = substr_count( mb_strtolower( ' ' . $text . ' ' ), ' ' . $focus . ' ' );
			if ( 0 === $count ) {
				// fallback: anywhere in string (not word-bounded)
				$count = substr_count( mb_strtolower( $text ), $focus );
			}
		}
		return [
			'density' => min( 1, $count / $wc ),
		];
	}

	protected function in_first_paragraph( string $html, string $focus ): bool {
		$focus = mb_strtolower( trim( $focus ) );
		if ( '' === $focus ) return false;
		$first = '';
		if ( preg_match( '/<p[^>]*>(.*?)<\/p>/is', $html, $m ) ) {
			$first = $this->to_text( $m[1] );
		} else {
			$first = mb_substr( $this->to_text( $html ), 0, 300 );
		}
		return ( false !== mb_stripos( $first, $focus ) );
	}

	protected function check( string $label, string $message, bool $pass ): array {
		return compact( 'label','message','pass' );
	}
}

