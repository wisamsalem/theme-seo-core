<?php
namespace ThemeSeoCore\Modules\Redirects;

use ThemeSeoCore\Security\Nonces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV import/export for redirects.
 */
class ImportExport {

	/** @var DB */
	protected $db;

	public function __construct( DB $db ) {
		$this->db = $db;
	}

	public function export_csv(): void {
		if ( ! current_user_can( 'tsc_manage_redirects' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'theme-seo-core' ), 403 );
		}

		// Grab all rows.
		$rows = array();
		$page = 1;
		do {
			$chunk = $this->db->all( $page, 2000 );
			$rows  = array_merge( $rows, $chunk['rows'] );
			$total = (int) $chunk['total'];
			$page++;
		} while ( count( $rows ) < $total );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="redirects.csv"' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'source','target','status','match_type' ) );
		foreach ( $rows as $r ) {
			fputcsv( $out, array( $r['source'], $r['target'], $r['status'], $r['match_type'] ) );
		}
		fclose( $out );
		exit;
	}

	public function import_csv(): void {
		if ( ! current_user_can( 'tsc_manage_redirects' ) || ! Nonces::verify( 'redirects_import' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'theme-seo-core' ), 403 );
		}

		if ( empty( $_FILES['csv']['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $_FILES['csv']['error'] ) { // phpcs:ignore
			wp_die( esc_html__( 'Upload failed.', 'theme-seo-core' ), 400 );
		}

		$fh = fopen( $_FILES['csv']['tmp_name'], 'r' ); // phpcs:ignore
		if ( ! $fh ) {
			wp_die( esc_html__( 'Unable to read file.', 'theme-seo-core' ), 400 );
		}

		// Skip header
		fgetcsv( $fh );

		$count = 0;
		while ( ( $row = fgetcsv( $fh ) ) !== false ) {
			list( $source, $target, $status, $match_type ) = array_pad( $row, 4, '' );
			$source     = sanitize_text_field( $source );
			$target     = esc_url_raw( $target );
			$status     = (int) $status ?: 301;
			$match_type = in_array( $match_type, array( 'exact','prefix','regex' ), true ) ? $match_type : 'exact';

			if ( $source && $target ) {
				$this->db->insert( compact( 'source','target','status','match_type' ) );
				$count++;
			}
		}
		fclose( $fh );

		wp_safe_redirect( add_query_arg( array( 'page' => 'theme-seo-redirects', 'imported' => $count ), admin_url( 'admin.php' ) ) );
		exit;
	}
}

