<?php
/**
 * Keep native category maps within WPML's configured query language.
 *
 * @package MCP_Abilities_WPSL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wrap the existing shortcode after plugins have registered their callbacks.
 *
 * Only WPML-translated store types need this adapter. The original callback,
 * attributes, output and WordPress shortcode filters remain authoritative.
 */
function mcp_wpsl_register_category_map_language(): void {
	global $shortcode_tags;

	static $registered = false;
	if (
		$registered
		|| ! defined( 'ICL_SITEPRESS_VERSION' )
		|| ! apply_filters( 'wpml_is_translated_post_type', false, 'wpsl_stores' )
		|| ! isset( $shortcode_tags['wpsl_map'] )
		|| ! is_callable( $shortcode_tags['wpsl_map'] )
	) {
		return;
	}

	$original = $shortcode_tags['wpsl_map'];
	add_shortcode(
		'wpsl_map',
		static function ( $attributes, $content = null, $tag = 'wpsl_map' ) use ( $original ) {
			/*
			 * get_posts() defaults to suppress_filters=true. Enable WPML only
			 * for the native category-ID query while this map is rendering.
			 * No language switch, replacement query or fixed store list is used.
			 */
			$enable_language = static function ( $query ): void {
				if (
					! $query instanceof WP_Query
					|| 'wpsl_stores' !== $query->get( 'post_type' )
					|| 'publish' !== $query->get( 'post_status' )
					|| 'ids' !== $query->get( 'fields' )
				) {
					return;
				}

				$tax_query = $query->get( 'tax_query' );
				if ( ! is_array( $tax_query ) ) {
					return;
				}
				foreach ( $tax_query as $clause ) {
					if ( is_array( $clause ) && 'wpsl_store_category' === ( $clause['taxonomy'] ?? '' ) ) {
						$query->set( 'suppress_filters', false );
						return;
					}
				}
			};

			add_action( 'pre_get_posts', $enable_language, 1 );
			try {
				return call_user_func( $original, $attributes, $content, $tag );
			} finally {
				remove_action( 'pre_get_posts', $enable_language, 1 );
			}
		}
	);
	$registered = true;
}
add_action( 'wp_loaded', 'mcp_wpsl_register_category_map_language', 20 );
