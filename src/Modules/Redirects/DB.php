<?php
namespace ThemeSeoCore\Modules\Redirects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Low-level CRUD for the redirects table.
 */
class DB {

	const TABLE = 'tsc_redirects';

	protected function table(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Insert a rule.
	 *
	 * @param array{source:string,target:string,status:int,match_type:string} $data
	 * @return int|false insert id
	 */
	public function insert( array $data ) {
		global $wpdb;
		$row = array(
			'source'     => (string) ( $data['source'] ?? '' ),
			'target'     => (string) ( $data['target'] ?? '' ),
			'status'     => (int) ( $data['status'] ?? 301 ),
			'match_type' => in_array( $data['match_type'] ?? 'exact', array( 'exact','prefix','regex' ), true ) ? $data['match_type'] : 'exact',
		);
		$ok = $wpdb->insert( $this->table(), $row, array( '%s','%s','%d','%s' ) );
		return $ok ? (int) $wpdb->insert_id : false;
	}

	public function update( int $id, array $data ): bool {
		global $wpdb;
		$fields = array();
		$formats = array();
		foreach ( array( 'source'=>'%s','target'=>'%s','status'=>'%d','match_type'=>'%s' ) as $k => $fmt ) {
			if ( isset( $data[ $k ] ) ) {
				$fields[ $k ]  = $data[ $k ];
				$formats[] = $fmt;
			}
		}
		if ( empty( $fields ) ) {
			return false;
		}
		return (bool) $wpdb->update( $this->table(), $fields, array( 'id' => $id ), $formats, array( '%d' ) );
	}

	public function delete( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( $this->table(), array( 'id' => $id ), array( '%d' ) );
	}

	public function bulk_delete( array $ids ): int {
		global $wpdb;
		$ids = array_map( 'intval', $ids );
		if ( empty( $ids ) ) return 0;
		$in  = implode( ',', $ids );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->query( "DELETE FROM {$this->table()} WHERE id IN ($in)" );
	}

	public function get( int $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id = %d", $id ), ARRAY_A );
	}

	public function all( int $paged = 1, int $per_page = 20 ): array {
		global $wpdb;
		$offset = max( 0, ( $paged - 1 ) * $per_page );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table()} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset ), ARRAY_A );
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table()}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array( 'rows' => $rows, 'total' => $count );
	}

	/**
	 * Match current request (path?query) against rules.
	 *
	 * @param string $request_uri e.g. "/foo?bar=baz"
	 * @param string $host        request host
	 * @return array<string,mixed>|null
	 */
	public function match_rule( string $request_uri, string $host ) {
		global $wpdb;

		$path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
		$query= (string) ( wp_parse_url( $request_uri, PHP_URL_QUERY ) ?? '' );

		// Fetch candidate rules (exact and prefix by first path segment), plus regex.
		$first = ltrim( explode( '/', trim( $path, '/' ), 2 )[0] ?? '', '/' );
		$like  = $wpdb->esc_like( '/' . $first ) . '%';
		$table = $this->table();

		$sql = "
			(SELECT * FROM {$table} WHERE match_type = 'exact'   AND source = %s)
			UNION ALL
			(SELECT * FROM {$table} WHERE match_type = 'prefix'  AND source LIKE %s)
			UNION ALL
			(SELECT * FROM {$table} WHERE match_type = 'regex')
			ORDER BY match_type = 'exact' DESC, match_type = 'prefix' DESC, id DESC
		";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$candidates = $wpdb->get_results( $wpdb->prepare( $sql, $request_uri, $like ), ARRAY_A );

		foreach ( $candidates as $row ) {
			if ( Matcher::matches( $row, $request_uri, $host ) ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * Increment hits/last_hit for a rule id.
	 */
	public function track_hit( int $id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE {$this->table()} SET hits = hits + 1, last_hit = %s WHERE id = %d", gmdate( 'Y-m-d H:i:s' ), $id ) );
	}
}

