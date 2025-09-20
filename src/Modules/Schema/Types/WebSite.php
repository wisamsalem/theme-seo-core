<?php
namespace ThemeSeoCore\Modules\Schema\Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebSite entity with potential SearchAction.
 */
class WebSite {

	public static function build(): array {
		$site_url = home_url( '/' );

		$data = [
			'@type' => 'WebSite',
			'@id'   => $site_url . '#website',
			'url'   => $site_url,
			'name'  => get_bloginfo( 'name' ),
		];

		// Add a Site SearchAction
		$data['potentialAction'] = [
			'@type'       => 'SearchAction',
			'target'      => add_query_arg( 's', '{search_term_string}', $site_url ),
			'query-input' => 'required name=search_term_string',
		];

		/**
		 * Filter WebSite data.
		 *
		 * @param array $data
		 */
		return apply_filters( 'tsc/schema/website', $data );
	}
}

