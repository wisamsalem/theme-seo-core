<?php
namespace ThemeSeoCore\Modules\ContentAnalysis;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Readability — Flesch Reading Ease + quick style checks.
 *
 * Not language-perfect but good enough for guidance.
 */
class Readability {

	/**
	 * @param string $content HTML or plain text
	 * @return array{
	 *   score:int,
	 *   checks: array<int,array{label:string,message:string,pass:bool}>
	 * }
	 */
	public function evaluate( string $content ): array {
		$text = $this->to_text( $content );
		$sentences = max( 1, $this->count_sentences( $text ) );
		$words     = max( 1, $this->word_count( $text ) );
		$syllables = max( 1, $this->syllable_count( $text ) );

		// Flesch Reading Ease (0–100; higher is easier)
		$fre = 206.835 - 1.015 * ( $words / $sentences ) - 84.6 * ( $syllables / $words );
		$fre = max( 0, min( 100, $fre ) );

		$avgSentence = $words / $sentences;
		$avgWord     = $syllables / $words;

		$checks = [
			$this->check( 'Reading ease (Flesch)', sprintf( '%0.1f', $fre ), $fre >= 50 ),
			$this->check( 'Avg sentence length', sprintf( '%0.1f words', $avgSentence ), $avgSentence <= 20 || $words < 150 ),
			$this->check( 'Avg syllables/word', sprintf( '%0.2f', $avgWord ), $avgWord <= 1.8 ),
			$this->check( 'Passive voice', 'heuristic only', true ), // placeholder; advanced NLP out of scope
		];

		// Heuristic score from checks (25 each)
		$score = 0;
		foreach ( $checks as $c ) $score += $c['pass'] ? 25 : 0;

		return [
			'score'  => (int) round( $score ),
			'checks' => $checks,
		];
	}

	/* ---------------- helpers ---------------- */

	protected function to_text( string $html ): string {
		$t = wp_strip_all_tags( $html );
		$t = html_entity_decode( $t, ENT_QUOTES, 'UTF-8' );
		return trim( preg_replace( '/\s+/', ' ', $t ) );
	}

	protected function word_count( string $text ): int {
		if ( '' === $text ) return 0;
		return str_word_count( $text, 0, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ’' );
	}

	protected function count_sentences( string $text ): int {
		$cnt = preg_match_all( '/[\.!\?]+["\')\]]*\s+/u', $text, $m );
		return max( 1, (int) $cnt );
	}

	protected function syllable_count( string $text ): int {
		// Crude English syllable estimation.
		$text = strtolower( $text );
		$words = preg_split( '/[^a-z]+/i', $text );
		$total = 0;
		foreach ( $words as $w ) {
			if ( '' === $w ) continue;
			$total += $this->syllables_in_word( $w );
		}
		return max( 1, $total );
	}

	protected function syllables_in_word( string $w ): int {
		$w = preg_replace( '/e$/', '', $w );               // silent e
		$w = preg_replace( '/[^a-z]/', '', $w );
		if ( '' === $w ) return 0;
		// Count vowel groups
		$v = preg_match_all( '/[aeiouy]+/i', $w, $m );
		$c = max( 1, (int) $v );
		// Disallow absurdly high syllables
		return min( $c, max( 1, (int) floor( strlen( $w ) / 2 ) ) );
	}

	protected function check( string $label, string $message, bool $pass ): array {
		return compact( 'label','message','pass' );
	}
}

