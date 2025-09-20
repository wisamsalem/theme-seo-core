<?php
namespace ThemeSeoCore\Modules\Schema\Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Organization entity — optional but common.
 */
class Organization {

	/**
	 * Build Organization schema if site has enough data, else null.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function maybe_build() {
		$name = get_bloginfo( 'name' );
		if ( ! $name ) {
			return null;
		}

		$logo = '';
		if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
			$logo = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
		} elseif ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
			$logo = get_site_icon_url( 512 );
		}

		$org = [
			'@type' => 'Organization',
			'@id'   => home_url( '/' ) . '#organization',
			'name'  => $name,
		];

		if ( $logo ) {
			$org['logo'] = [
				'@type' => 'ImageObject',
				'url'   => $logo,
			];
		}

		/**
		 * Filter Organization data.
		 *
		 * @param array $org
		 */
		return apply_filters( 'tsc/schema/organization', $org );
	}
}

