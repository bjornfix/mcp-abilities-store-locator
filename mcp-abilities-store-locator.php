<?php
/**
 * Plugin Name: MCP Abilities - Store Locator
 * Plugin URI: https://devenia.com/plugins/mcp-abilities-store-locator/
 * Description: Narrow MCP abilities and maintained frontend template support for WP Store Locator.
 * Version: 0.1.22
 * Author: basicus
 * Author URI: https://profiles.wordpress.org/basicus/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * Text Domain: mcp-abilities-store-locator
 *
 * @package MCP_Abilities_WPSL
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_init', static function () {
	require_once __DIR__ . '/includes/devenia-updater-notice.php';
	mcp_abilities_store_locator_Updater_Notice::register( __FILE__ );
} );

const MCP_WPSL_COLUMNS_TEMPLATE = 'dynamic_columns';
const MCP_WPSL_VERSION             = '0.1.22';
const MCP_WPSL_BASE_TRANSLATIONS   = 'mcp_wpsl_permalink_base_translations';
const MCP_WPSL_LABEL_TRANSLATIONS  = 'mcp_wpsl_label_translations';
const MCP_WPSL_NAV_SOURCE_LINKS    = 'mcp_wpsl_nav_source_language_links';

/**
 * Return configured language codes that may be visible in frontend URL prefixes.
 *
 * @return array<int,string>
 */
function mcp_wpsl_get_configured_language_codes(): array {
	$codes = array();

	foreach ( array_keys( mcp_wpsl_get_permalink_base_translations() ) as $language_code ) {
		$codes[] = sanitize_key( (string) $language_code );
	}

	foreach ( array_keys( mcp_wpsl_get_label_translations() ) as $language_code ) {
		$codes[] = sanitize_key( (string) $language_code );
	}

	foreach ( mcp_wpsl_get_nav_source_language_links() as $language_code => $source_language_code ) {
		$codes[] = sanitize_key( (string) $language_code );
		$codes[] = sanitize_key( (string) $source_language_code );
	}

	return array_values( array_filter( array_unique( $codes ) ) );
}

/**
 * Check whether WP Store Locator is active enough for settings/template work.
 */
function mcp_wpsl_is_available(): bool {
	return function_exists( 'wpsl_get_templates' ) || post_type_exists( 'wpsl_stores' );
}

/**
 * Return sanitized WPSL settings.
 */
function mcp_wpsl_get_settings(): array {
	$settings = get_option( 'wpsl_settings', array() );
	return is_array( $settings ) ? $settings : array();
}

/**
 * Return configured language-specific WPSL store bases.
 *
 * @return array<string,string>
 */
function mcp_wpsl_get_permalink_base_translations(): array {
	$raw = get_option( MCP_WPSL_BASE_TRANSLATIONS, array() );
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$translations = array();
	foreach ( $raw as $language_code => $base ) {
		$language_code = sanitize_key( (string) $language_code );
		$base          = sanitize_title( (string) $base );
		if ( '' === $language_code || '' === $base ) {
			continue;
		}

		$translations[ $language_code ] = $base;
	}

	return $translations;
}

/**
 * Return one configured translated WPSL store base.
 */
function mcp_wpsl_get_translated_store_base( string $language_code ): string {
	$translations = mcp_wpsl_get_permalink_base_translations();
	return $translations[ sanitize_key( $language_code ) ] ?? '';
}

/**
 * Return the active frontend language code from WPML, Polylang, or locale.
 */
function mcp_wpsl_current_language_code(): string {

	$wpml_language = call_user_func_array( 'apply_filters', array( 'wpml_current_language', null ) );
	if ( is_string( $wpml_language ) && '' !== $wpml_language ) {
		return sanitize_key( $wpml_language );
	}

	if ( function_exists( 'pll_current_language' ) ) {
		$polylang_language = pll_current_language( 'slug' );
		if ( is_string( $polylang_language ) && '' !== $polylang_language ) {
			return sanitize_key( $polylang_language );
		}
	}

	$request_path = mcp_wpsl_get_request_path();
	if ( preg_match( '#^/([a-z]{2,3}(?:-[a-z0-9]+)?)(?:/|$)#i', $request_path, $matches ) ) {
		$request_language = sanitize_key( (string) $matches[1] );
		if ( in_array( $request_language, mcp_wpsl_get_configured_language_codes(), true ) ) {
			return $request_language;
		}
	}

	$locale = determine_locale();
	if ( is_string( $locale ) && '' !== $locale ) {
		return sanitize_key( explode( '_', str_replace( '-', '_', $locale ) )[0] );
	}

	return '';
}

/**
 * WPSL label keys supported by the language-specific label translation option.
 *
 * @return array<int,string>
 */
function mcp_wpsl_supported_label_keys(): array {
	return array( 'search_label', 'search_btn_label' );
}

/**
 * Sanitize language-specific WPSL label translations.
 *
 * @param mixed $raw Raw option/input.
 * @return array<string,array<string,string>>
 */
function mcp_wpsl_sanitize_label_translations( $raw ): array {
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$supported    = mcp_wpsl_supported_label_keys();
	$translations = array();
	foreach ( $raw as $language_code => $labels ) {
		$language_code = sanitize_key( (string) $language_code );
		if ( '' === $language_code || ! is_array( $labels ) ) {
			continue;
		}

		foreach ( $labels as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( ! in_array( $key, $supported, true ) ) {
				continue;
			}

			$value = sanitize_text_field( (string) $value );
			if ( '' === $value ) {
				continue;
			}

			$translations[ $language_code ][ $key ] = $value;
		}
	}

	return $translations;
}

/**
 * Return configured language-specific WPSL label translations.
 *
 * @return array<string,array<string,string>>
 */
function mcp_wpsl_get_label_translations(): array {
	return mcp_wpsl_sanitize_label_translations( get_option( MCP_WPSL_LABEL_TRANSLATIONS, array() ) );
}

/**
 * Sanitize language-specific nav source language mappings.
 *
 * @param mixed $raw Raw option/input.
 * @return array<string,string>
 */
function mcp_wpsl_sanitize_nav_source_language_links( $raw ): array {
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$mappings = array();
	foreach ( $raw as $language_code => $source_language_code ) {
		$language_code        = sanitize_key( (string) $language_code );
		$source_language_code = sanitize_key( (string) $source_language_code );
		if ( '' === $language_code || '' === $source_language_code || $language_code === $source_language_code ) {
			continue;
		}

		$mappings[ $language_code ] = $source_language_code;
	}

	return $mappings;
}

/**
 * Return configured language-specific native menu source language mappings.
 *
 * @return array<string,string>
 */
function mcp_wpsl_get_nav_source_language_links(): array {
	return mcp_wpsl_sanitize_nav_source_language_links( get_option( MCP_WPSL_NAV_SOURCE_LINKS, array() ) );
}

/**
 * Translate a WPSL setting label for the active frontend language when configured.
 */
function mcp_wpsl_translate_setting_label( string $key, string $fallback ): string {
	$language_code = mcp_wpsl_current_language_code();
	if ( '' === $language_code ) {
		return $fallback;
	}

	$translations = mcp_wpsl_get_label_translations();
	return $translations[ $language_code ][ sanitize_key( $key ) ] ?? $fallback;
}

/**
 * Return the WPML language code for a WPSL store post when WPML is available.
 */
function mcp_wpsl_get_store_language_code( int $post_id ): string {
	global $sitepress;

	if ( is_object( $sitepress ) && method_exists( $sitepress, 'get_language_for_element' ) ) {
		$language_code = $sitepress->get_language_for_element( $post_id, 'post_wpsl_stores' );
		return is_string( $language_code ) ? $language_code : '';
	}

	return '';
}

/**
 * Return a WPML/Polylang post language code for a post when available.
 */
function mcp_wpsl_get_post_language_code( int $post_id, string $post_type ): string {
	$wpml_details = call_user_func_array(
		'apply_filters',
		array(
			'wpml_element_language_details',
			null,
			array(
				'element_id'   => $post_id,
				'element_type' => $post_type,
			),
		)
	);

	if ( is_object( $wpml_details ) && isset( $wpml_details->language_code ) && is_string( $wpml_details->language_code ) ) {
		return sanitize_key( $wpml_details->language_code );
	}

	if ( function_exists( 'pll_get_post_language' ) ) {
		$polylang_language = pll_get_post_language( $post_id, 'slug' );
		if ( is_string( $polylang_language ) && '' !== $polylang_language ) {
			return sanitize_key( $polylang_language );
		}
	}

	return '';
}

/**
 * Return a WPSL store post translated into the requested language when possible.
 */
function mcp_wpsl_get_store_post_in_language( int $post_id, string $language_code ): int {
	$language_code = sanitize_key( $language_code );
	if ( '' === $language_code || ! $post_id ) {
		return 0;
	}

	$wpml_post_id = false !== has_filter( 'wpml_object_id' ) ? call_user_func_array( 'apply_filters', array( 'wpml_object_id', $post_id, 'wpsl_stores', false, $language_code ) ) : null;
	if ( is_numeric( $wpml_post_id ) && (int) $wpml_post_id > 0 ) {
		return (int) $wpml_post_id;
	}

	if ( function_exists( 'pll_get_post' ) ) {
		$polylang_post_id = pll_get_post( $post_id, $language_code );
		if ( is_numeric( $polylang_post_id ) && (int) $polylang_post_id > 0 ) {
			return (int) $polylang_post_id;
		}
	}

	$current_language = mcp_wpsl_get_post_language_code( $post_id, 'wpsl_stores' );
	return $language_code === $current_language ? $post_id : 0;
}

/**
 * Find the native menu label used by a source-language WPSL store menu item.
 */
function mcp_wpsl_get_source_store_nav_label( int $source_post_id, string $source_language_code ): string {
	$source_language_code = sanitize_key( $source_language_code );
	if ( ! $source_post_id || '' === $source_language_code ) {
		return '';
	}

	static $labels = array();
	$cache_key = $source_language_code . ':' . $source_post_id;
	if ( array_key_exists( $cache_key, $labels ) ) {
		return $labels[ $cache_key ];
	}

	$fallback_label = '';
	$menus          = wp_get_nav_menus();
	foreach ( $menus as $menu ) {
		$menu_items = wp_get_nav_menu_items(
			$menu,
			array(
				'update_post_term_cache' => false,
			)
		);
		if ( ! is_array( $menu_items ) ) {
			continue;
		}

		foreach ( $menu_items as $menu_item ) {
			if (
				! is_object( $menu_item )
				|| 'post_type' !== ( $menu_item->type ?? '' )
				|| 'wpsl_stores' !== ( $menu_item->object ?? '' )
				|| $source_post_id !== (int) ( $menu_item->object_id ?? 0 )
			) {
				continue;
			}

			$label = trim( (string) ( $menu_item->title ?? '' ) );
			if ( '' === $label ) {
				continue;
			}

			if ( '' === $fallback_label ) {
				$fallback_label = $label;
			}

			$menu_item_language = mcp_wpsl_get_post_language_code( (int) $menu_item->ID, 'nav_menu_item' );
			if ( $source_language_code === $menu_item_language ) {
				$labels[ $cache_key ] = $label;
				return $labels[ $cache_key ];
			}
		}
	}

	$labels[ $cache_key ] = $fallback_label;
	return $labels[ $cache_key ];
}

/**
 * Return source-language render data for a native WPSL store menu item.
 *
 * @param object $item WordPress menu item object.
 * @return array{url:string,title:string,source_post_id:int}
 */
function mcp_wpsl_get_nav_store_source_render_data( object $item ): array {
	if ( ! isset( $item->object, $item->object_id ) || 'wpsl_stores' !== $item->object ) {
		return array( 'url' => '', 'title' => '', 'source_post_id' => 0 );
	}

	$language_code = mcp_wpsl_current_language_code();
	if ( '' === $language_code ) {
		return array( 'url' => '', 'title' => '', 'source_post_id' => 0 );
	}

	$mappings             = mcp_wpsl_get_nav_source_language_links();
	$source_language_code = $mappings[ $language_code ] ?? '';
	if ( '' === $source_language_code ) {
		return array( 'url' => '', 'title' => '', 'source_post_id' => 0 );
	}

	$original_post_id = (int) $item->object_id;
	static $render_data = array();
	$cache_key = $language_code . ':' . $source_language_code . ':' . $original_post_id;
	if ( array_key_exists( $cache_key, $render_data ) ) {
		return $render_data[ $cache_key ];
	}

	$source_post_id = mcp_wpsl_get_store_post_in_language( (int) $item->object_id, $source_language_code );
	if ( ! $source_post_id ) {
		$render_data[ $cache_key ] = array( 'url' => '', 'title' => '', 'source_post_id' => 0 );
		return $render_data[ $cache_key ];
	}

	$source_post = get_post( $source_post_id );
	if ( ! $source_post || 'wpsl_stores' !== $source_post->post_type || 'publish' !== $source_post->post_status ) {
		$render_data[ $cache_key ] = array( 'url' => '', 'title' => '', 'source_post_id' => 0 );
		return $render_data[ $cache_key ];
	}

	call_user_func_array( 'do_action', array( 'wpml_switch_language', $source_language_code ) );
	try {
		$source_permalink = get_permalink( $source_post_id );
		$source_label     = mcp_wpsl_get_source_store_nav_label( $source_post_id, $source_language_code );
	} finally {
		call_user_func_array( 'do_action', array( 'wpml_switch_language', $language_code ) );
	}

	$render_data[ $cache_key ] = array(
		'url'            => $source_permalink ? (string) $source_permalink : '',
		'title'          => $source_label,
		'source_post_id' => $source_post_id,
	);

	return $render_data[ $cache_key ];
}

/**
 * Register configured language-specific WPSL store rewrite bases.
 */
function mcp_wpsl_register_translated_store_rewrites(): void {
	if ( ! post_type_exists( 'wpsl_stores' ) ) {
		return;
	}
	foreach ( mcp_wpsl_get_permalink_base_translations() as $language_code => $base ) {
		add_rewrite_rule(
			'^' . preg_quote( $language_code, '#' ) . '/' . preg_quote( $base, '#' ) . '/([^/]+)/?$',
			'index.php?post_type=wpsl_stores&name=$matches[1]&lang=' . $language_code,
			'top'
		);
	}
}
add_action( 'init', 'mcp_wpsl_register_translated_store_rewrites', 11 );

/**
 * Return the current request path with basic sanitization.
 */
function mcp_wpsl_get_request_path(): string {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '';
	$path = (string) strtok( $request_uri, '?' );
	$home_path = rtrim( (string) wp_parse_url( (string) get_option( 'home' ), PHP_URL_PATH ), '/' );
	return $home_path && str_starts_with( $path, $home_path . '/' ) ? substr( $path, strlen( $home_path ) ) : $path;
}

/**
 * Flush rewrite rules once after a plugin version with rewrite changes is deployed.
 */
function mcp_wpsl_maybe_flush_rewrite_rules(): void {
	$stored_version = get_option( 'mcp_wpsl_version', '' );
	if ( MCP_WPSL_VERSION === $stored_version ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'mcp_wpsl_version', MCP_WPSL_VERSION, false );
}
add_action( 'init', 'mcp_wpsl_maybe_flush_rewrite_rules', 20 );

/**
 * Use configured translated URL bases for WPML translations of WPSL stores.
 *
 * @param string  $post_link The generated permalink.
 * @param WP_Post $post      The store post.
 */
function mcp_wpsl_filter_translated_store_permalink( string $post_link, WP_Post $post ): string {
	if ( 'wpsl_stores' !== $post->post_type ) {
		return $post_link;
	}

	$language_code = mcp_wpsl_get_store_language_code( (int) $post->ID );
	$base          = mcp_wpsl_get_translated_store_base( $language_code );
	if ( '' === $language_code || '' === $base ) {
		return $post_link;
	}

	if ( in_array( $post->post_status, array( 'auto-draft', 'draft', 'pending' ), true ) || ! get_option( 'permalink_structure' ) ) {
		return $post_link;
	}
	$settings = mcp_wpsl_get_settings();
	$source_base = sanitize_title( (string) ( $settings['permalink_slug'] ?? '' ) );
	$home = trailingslashit( (string) get_option( 'home' ) );
	$prefix = $home . $language_code . '/' . $source_base . '/';
	if ( '' === $source_base || ! str_starts_with( $post_link, $prefix ) ) {
		return $post_link;
	}
	return $home . $language_code . '/' . $base . '/' . substr( $post_link, strlen( $prefix ) );
}
add_filter( 'post_type_link', 'mcp_wpsl_filter_translated_store_permalink', 20, 2 );

/**
 * Redirect old mixed-language store URLs to their configured translated canonical base.
 */
function mcp_wpsl_redirect_translated_store_canonical_base(): void {
	if ( ! is_singular( 'wpsl_stores' ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return;
	}

	$language_code = mcp_wpsl_get_store_language_code( (int) $post_id );
	$base          = mcp_wpsl_get_translated_store_base( $language_code );
	if ( '' === $language_code || '' === $base ) {
		return;
	}

	$settings    = mcp_wpsl_get_settings();
	$source_base = isset( $settings['permalink_slug'] ) ? sanitize_title( (string) $settings['permalink_slug'] ) : '';
	if ( '' === $source_base || $base === $source_base ) {
		return;
	}

	$request_path = mcp_wpsl_get_request_path();
	if ( ! str_starts_with( $request_path, '/' . $language_code . '/' . $source_base . '/' ) ) {
		return;
	}

	$canonical = get_permalink( $post_id );
	if ( $canonical && wp_parse_url( $canonical, PHP_URL_PATH ) !== wp_parse_url( (string) get_option( 'home' ) . $request_path, PHP_URL_PATH ) ) {
		wp_safe_redirect( $canonical, 301 );
		exit;
	}
}
add_action( 'template_redirect', 'mcp_wpsl_redirect_translated_store_canonical_base', 1 );

/**
 * Let configured frontend languages render native WPSL store menu items with source-language links and labels.
 *
 * The menu items remain normal WordPress post_type menu items. This only adjusts
 * the rendered output after multilingual plugins have resolved their objects.
 *
 * @param array<int,WP_Post> $items Menu item objects.
 * @return array<int,WP_Post>
 */
function mcp_wpsl_filter_nav_store_source_language_links( array $items ): array {
	foreach ( $items as $item ) {
		if ( ! is_object( $item ) ) {
			continue;
		}

		$source_data = mcp_wpsl_get_nav_store_source_render_data( $item );
		if ( '' !== $source_data['url'] ) {
			$item->url = $source_data['url'];
		}

		if ( '' !== $source_data['title'] ) {
			$item->title = $source_data['title'];
		}

		if ( $source_data['source_post_id'] ) {
			$item->object_id = $source_data['source_post_id'];
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'mcp_wpsl_filter_nav_store_source_language_links', 50 );

/**
 * Override WPSL store menu link href late in native menu rendering.
 *
 * @param array<string,string> $attributes Link attributes.
 * @param object               $item       Menu item object.
 * @return array<string,string>
 */
function mcp_wpsl_filter_nav_store_source_link_attributes( array $attributes, object $item ): array {
	$source_data = mcp_wpsl_get_nav_store_source_render_data( $item );
	if ( '' !== $source_data['url'] ) {
		$attributes['href'] = $source_data['url'];
	}

	return $attributes;
}
add_filter( 'nav_menu_link_attributes', 'mcp_wpsl_filter_nav_store_source_link_attributes', 1000, 2 );

/**
 * Override WPSL store menu labels late in native menu rendering.
 */
function mcp_wpsl_filter_nav_store_source_title( string $title, object $item ): string {
	$source_data = mcp_wpsl_get_nav_store_source_render_data( $item );
	return '' !== $source_data['title'] ? $source_data['title'] : $title;
}
add_filter( 'nav_menu_item_title', 'mcp_wpsl_filter_nav_store_source_title', 1000, 2 );

/**
 * Register the maintained columns store-locator template with WP Store Locator.
 *
 * @param array<int,array<string,string>> $templates Existing WPSL templates.
 * @return array<int,array<string,string>>
 */
function mcp_wpsl_register_columns_template( array $templates ): array {
	$templates[] = array(
		'id'   => MCP_WPSL_COLUMNS_TEMPLATE,
		'name' => __( 'Dynamic columns', 'mcp-abilities-store-locator' ),
		'path' => plugin_dir_path( __FILE__ ) . 'templates/store-listings-columns.php',
	);

	return $templates;
}
add_filter( 'wpsl_templates', 'mcp_wpsl_register_columns_template' );

/**
 * Accept a bounded location submitted by the legacy footer search form.
 *
 * This is a read-only search, not an authenticated state-changing action.
 * GET requests and cookies must never start a footer search.
 */
function mcp_wpsl_get_footer_search_post_value(): string {
	$request_method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public read-only location search; no account or persistent state is changed.
	if ( 'POST' !== $request_method || ! isset( $_POST['wpsl-widget-search'] ) || ! is_string( $_POST['wpsl-widget-search'] ) ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw scalar is bounded and compared with sanitization below; changed input is rejected and never output here.
	$value = trim( wp_unslash( $_POST['wpsl-widget-search'] ) );
	if ( '' === $value || strlen( $value ) > 200 || 1 !== preg_match( '/[\p{L}\p{N}]/u', $value ) || sanitize_text_field( $value ) !== $value ) {
		return '';
	}

	return $value;
}

/**
 * Start the native search for a valid footer submission to our columns template.
 *
 * Native WP Store Locator still owns geocoding, AJAX, radius and sorting.
 * Existing widget integrations and unrelated settings remain authoritative.
 *
 * @param array<string,mixed> $settings Native Store Locator JavaScript settings.
 * @return array<string,mixed>
 */
function mcp_wpsl_enable_footer_search( array $settings ): array {
	if ( MCP_WPSL_COLUMNS_TEMPLATE !== ( $settings['ux']['templateId'] ?? '' ) || ! isset( $settings['search'] ) || ! is_array( $settings['search'] ) || ! empty( $settings['search']['widgetEnabled'] ) || '' === mcp_wpsl_get_footer_search_post_value() ) {
		return $settings;
	}

	$settings['search']['widgetEnabled'] = 1;
	return $settings;
}
add_filter( 'wpsl_js_settings', 'mcp_wpsl_enable_footer_search', 20 );

/**
 * Keep native OpenStreetMap directions aligned with the current postal search.
 *
 * @param array<string,mixed> $settings Native Store Locator JavaScript settings.
 * @return array<string,mixed>
 */
function mcp_wpsl_add_direction_origin_script( array $settings ): array {
	if ( 'osm' !== ( $settings['api']['provider'] ?? '' ) || MCP_WPSL_COLUMNS_TEMPLATE !== ( $settings['ux']['templateId'] ?? '' ) || ! wp_script_is( 'wpsl', 'registered' ) ) {
		return $settings;
	}

	static $added = false;
	if ( $added ) {
		return $settings;
	}

	$path = __DIR__ . '/assets/directions-origin.js';
	if ( ! is_readable( $path ) ) {
		return $settings;
	}

	$script = file_get_contents( $path );
	if ( false !== $script ) {
		$added = wp_add_inline_script( 'wpsl', $script, 'before' );
	}

	return $settings;
}
add_filter( 'wpsl_js_settings', 'mcp_wpsl_add_direction_origin_script', 30 );

/**
 * Check if the current WPSL setting selects the maintained columns template.
 */
function mcp_wpsl_uses_columns_template(): bool {
	$settings = mcp_wpsl_get_settings();
	return isset( $settings['template_id'] ) && MCP_WPSL_COLUMNS_TEMPLATE === (string) $settings['template_id'];
}

/**
 * Add the minimal frontend layout required by the maintained WPSL columns template.
 */
function mcp_wpsl_enqueue_columns_template_style(): void {
	if ( ! mcp_wpsl_uses_columns_template() ) {
		return;
	}

	$css  = "#wpsl-wrap.mcp-wpsl-columns #wpsl-result-list{width:100%;margin:12px 0 0;}\n";
	$css .= "#wpsl-wrap.mcp-wpsl-columns #wpsl-stores,#wpsl-wrap.mcp-wpsl-columns #wpsl-direction-details{height:auto!important;overflow:visible;}\n";
	$css .= "#wpsl-wrap.mcp-wpsl-columns #wpsl-stores>ul{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;margin:0;padding:0;}\n";
	$css .= "#wpsl-wrap.mcp-wpsl-columns #wpsl-result-list li{box-sizing:border-box;width:auto;padding:0;border-bottom:0;}\n";
	$css .= "#wpsl-wrap.mcp-wpsl-columns #wpsl-result-list li.mcp-wpsl-card{height:100%;padding:0 0 20px;border:0;border-bottom:1px solid #e5e5e5;background:#fff;}\n";
	$css .= "#wpsl-wrap.mcp-wpsl-columns #wpsl-result-list li.mcp-wpsl-card:last-child{border-bottom:0;}\n";
	$css .= "#wpsl-wrap.mcp-wpsl-columns .wpsl-gmap-canvas{margin-bottom:0;}\n";
	$css .= "@media (max-width:1024px){#wpsl-wrap.mcp-wpsl-columns #wpsl-stores>ul{grid-template-columns:repeat(2,minmax(0,1fr));}}\n";
	$css .= "@media (max-width:767px){#wpsl-wrap.mcp-wpsl-columns .wpsl-search{padding-top:14px;}#wpsl-wrap.mcp-wpsl-columns #wpsl-stores>ul{grid-template-columns:1fr;gap:20px;}}\n";

	if ( wp_style_is( 'wpsl-styles', 'enqueued' ) || wp_style_is( 'wpsl-styles', 'registered' ) ) {
		wp_add_inline_style( 'wpsl-styles', $css );
		return;
	}

	wp_register_style( 'mcp-wpsl-columns-template', false, array(), MCP_WPSL_VERSION );
	wp_enqueue_style( 'mcp-wpsl-columns-template' );
	wp_add_inline_style( 'mcp-wpsl-columns-template', $css );
}
add_action( 'wp_enqueue_scripts', 'mcp_wpsl_enqueue_columns_template_style', 30 );

/**
 * Add a stable card class to WPSL result items when the maintained columns template is active.
 *
 * @param string $template Existing Underscore.js listing template.
 */
function mcp_wpsl_columns_listing_template( string $template ): string {
	if ( ! mcp_wpsl_uses_columns_template() ) {
		return $template;
	}

	return str_replace( '<li data-store-id="<%= id %>">', '<li class="mcp-wpsl-card" data-store-id="<%= id %>">', $template );
}
add_filter( 'wpsl_listing_template', 'mcp_wpsl_columns_listing_template', 20 );

/**
 * Let Elementor-rendered Store Locator store posts use their Elementor content.
 *
 * @param bool $skip Whether WPSL should skip its CPT template.
 */
function mcp_wpsl_skip_cpt_template_for_elementor_store( bool $skip ): bool {
	if ( ! is_singular( 'wpsl_stores' ) ) {
		return $skip;
	}

	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return $skip;
	}

	$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
	return $elementor_data ? true : $skip;
}
add_filter( 'wpsl_skip_cpt_template', 'mcp_wpsl_skip_cpt_template_for_elementor_store', 20 );

/**
 * Check if the Abilities API is available.
 */
function mcp_wpsl_check_dependencies(): bool {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		add_action(
			'admin_notices',
			static function (): void {
				echo '<div class="notice notice-error"><p><strong>MCP Abilities - Store Locator</strong> requires WordPress 6.9 or later with the Abilities API available.</p></div>';
			}
		);
		return false;
	}

	return true;
}

/**
 * Return normalized WPSL template records.
 *
 * @return array<int,array<string,string>>
 */
function mcp_wpsl_list_templates(): array {
	$templates = function_exists( 'wpsl_get_templates' ) ? wpsl_get_templates() : array();
	if ( ! is_array( $templates ) ) {
		return array();
	}

	$normalized = array();
	foreach ( $templates as $template ) {
		if ( ! is_array( $template ) ) {
			continue;
		}

		$path = (string) ( $template['path'] ?? '' ) . (string) ( $template['file_name'] ?? '' );
		$normalized[] = array(
			'id'     => isset( $template['id'] ) ? (string) $template['id'] : '',
			'name'   => isset( $template['name'] ) ? wp_strip_all_tags( (string) $template['name'] ) : '',
			'exists' => is_file( $path ) ? 'yes' : 'no',
		);
	}

	return $normalized;
}

/**
 * Count published stores.
 */
function mcp_wpsl_count_published_stores(): int {
	$count = wp_count_posts( 'wpsl_stores' );
	if ( is_object( $count ) && isset( $count->publish ) ) {
		return (int) $count->publish;
	}

	return 0;
}

/**
 * WPSL store meta keys supported by this ability add-on.
 *
 * @return array<string,string>
 */
function mcp_wpsl_store_meta_fields(): array {
	return array(
		'address'     => 'text',
		'address2'    => 'text',
		'city'        => 'text',
		'state'       => 'text',
		'zip'         => 'text',
		'country'     => 'text',
		'country_iso' => 'text',
		'lat'         => 'float',
		'lng'         => 'float',
		'phone'       => 'text',
		'fax'         => 'text',
		'email'       => 'email',
		'url'         => 'url',
	);
}

/**
 * Sanitize one supported WPSL store meta value.
 *
 * @param string $field Field name without wpsl_ prefix.
 * @param mixed  $value Input value.
 * @return string
 */
function mcp_wpsl_sanitize_store_meta_value( string $field, $value ): string {
	$fields = mcp_wpsl_store_meta_fields();
	$type   = $fields[ $field ] ?? 'text';
	$value  = is_scalar( $value ) ? (string) $value : '';

	if ( 'float' === $type ) {
		return is_numeric( $value ) ? (string) (float) $value : '';
	}

	if ( 'email' === $type ) {
		return sanitize_email( $value );
	}

	if ( 'url' === $type ) {
		return esc_url_raw( $value );
	}

	return sanitize_text_field( $value );
}

/**
 * Return normalized WPSL meta for a store.
 */
function mcp_wpsl_get_store_meta( int $post_id ): array {
	$meta = array();

	foreach ( mcp_wpsl_store_meta_fields() as $field => $_type ) {
		$meta[ $field ] = (string) get_post_meta( $post_id, 'wpsl_' . $field, true );
	}

	$hours = get_post_meta( $post_id, 'wpsl_hours', true );
	if ( is_array( $hours ) ) {
		$meta['hours'] = $hours;
	}

	return $meta;
}

/**
 * Return a normalized WPSL store record.
 */
function mcp_wpsl_store_response( WP_Post $post ): array {
	$terms = wp_get_object_terms( $post->ID, 'wpsl_store_category' );

	$categories = array();
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$categories[] = array(
				'id'   => (int) $term->term_id,
				'name' => (string) $term->name,
				'slug' => (string) $term->slug,
			);
		}
	}

	return array(
		'id'         => (int) $post->ID,
		'title'      => get_the_title( $post ),
		'slug'       => (string) $post->post_name,
		'status'     => (string) $post->post_status,
		'content'    => (string) $post->post_content,
		'excerpt'    => (string) $post->post_excerpt,
		'permalink'  => get_permalink( $post ),
		'meta'       => mcp_wpsl_get_store_meta( (int) $post->ID ),
		'categories' => $categories,
	);
}

/**
 * Save one native store after checking its permissions and all supplied fields.
 *
 * @param array $input Ability input.
 * @param int   $post_id Existing store ID, or zero to create a draft.
 * @return array
 */
function mcp_wpsl_save_store( array $input, int $post_id = 0 ): array {
	$type = get_post_type_object( 'wpsl_stores' );
	$post = $post_id ? get_post( $post_id ) : null;
	if ( ! $type || ( $post_id && ( ! $post || 'wpsl_stores' !== $post->post_type ) ) ) {
		return array( 'success' => false, 'message' => 'WP Store Locator or the requested store is unavailable.' );
	}
	if ( $post_id ? ! current_user_can( 'edit_post', $post_id ) : ! current_user_can( $type->cap->create_posts ) ) {
		return array( 'success' => false, 'message' => 'You cannot edit this store or create a store.' );
	}
	$updates = $post_id ? array( 'ID' => $post_id ) : array( 'post_type' => 'wpsl_stores', 'post_status' => 'draft' );
	if ( array_key_exists( 'status', $input ) ) {
		$status = sanitize_key( (string) $input['status'] );
		if ( ! in_array( $status, array( 'draft', 'pending', 'private', 'publish' ), true ) ) {
			return array( 'success' => false, 'message' => 'Use draft, pending, private or publish status.' );
		}
		if ( in_array( $status, array( 'private', 'publish' ), true ) && ! current_user_can( $type->cap->publish_posts ) ) {
			return array( 'success' => false, 'message' => 'You cannot publish stores.' );
		}
		$updates['post_status'] = $status;
	}
	foreach ( array( 'title', 'content', 'excerpt', 'slug' ) as $field ) {
		if ( ! array_key_exists( $field, $input ) ) {
			continue;
		}
		$value = (string) $input[ $field ];
		switch ( $field ) {
			case 'title':
				$updates['post_title'] = sanitize_text_field( $value );
				break;
			case 'content':
				$updates['post_content'] = wp_kses_post( $value );
				break;
			case 'excerpt':
				$updates['post_excerpt'] = sanitize_textarea_field( $value );
				break;
			case 'slug':
				$updates['post_name'] = sanitize_title( $value );
				break;
		}
	}
	if ( ( ! $post_id || isset( $input['title'] ) ) && '' === trim( $updates['post_title'] ?? '' ) ) {
		return array( 'success' => false, 'message' => 'A non-empty store title is required.' );
	}
	$meta = array();
	foreach ( $input['meta'] ?? array() as $field => $value ) {
		if ( ! array_key_exists( $field, mcp_wpsl_store_meta_fields() ) || ! is_scalar( $value ) ) {
			return array( 'success' => false, 'message' => 'Unsupported store metadata field or value: ' . sanitize_key( (string) $field ) );
		}
		if ( in_array( $field, array( 'lat', 'lng' ), true ) && '' !== (string) $value ) {
			$limit = 'lat' === $field ? 90 : 180;
			if ( ! is_numeric( $value ) || ! is_finite( (float) $value ) || abs( (float) $value ) > $limit ) {
				return array( 'success' => false, 'message' => 'Invalid coordinate: ' . $field );
			}
		}
		if ( $post_id && ! current_user_can( 'edit_post_meta', $post_id, 'wpsl_' . $field ) ) {
			return array( 'success' => false, 'message' => 'You cannot edit the supplied store metadata.' );
		}
		$meta[ $field ] = mcp_wpsl_sanitize_store_meta_value( $field, $value );
		if ( in_array( $field, array( 'email', 'url' ), true ) && '' !== (string) $value && '' === $meta[ $field ] ) {
			return array( 'success' => false, 'message' => 'Invalid contact field: ' . $field );
		}
	}
	$term_ids = array();
	if ( array_key_exists( 'categories', $input ) ) {
		$taxonomy = get_taxonomy( 'wpsl_store_category' );
		if ( ! $taxonomy || ! current_user_can( $taxonomy->cap->assign_terms ) ) {
			return array( 'success' => false, 'message' => 'You cannot assign store categories.' );
		}
		foreach ( $input['categories'] as $item ) {
			$term = is_numeric( $item ) ? get_term( (int) $item, 'wpsl_store_category' ) : ( is_string( $item ) ? get_term_by( 'slug', sanitize_title( $item ), 'wpsl_store_category' ) : false );
			if ( ! $term || is_wp_error( $term ) ) {
				return array( 'success' => false, 'message' => 'A supplied store category does not exist.' );
			}
			$term_ids[] = (int) $term->term_id;
		}
	}
	$result = $post_id ? wp_update_post( wp_slash( $updates ), true ) : wp_insert_post( wp_slash( $updates ), true );
	if ( is_wp_error( $result ) || ! $result ) {
		return array( 'success' => false, 'message' => is_wp_error( $result ) ? $result->get_error_message() : 'Store could not be saved.' );
	}
	$post_id = (int) $result;
	$error   = '';
	foreach ( $meta as $field => $value ) {
		if ( ! current_user_can( 'edit_post_meta', $post_id, 'wpsl_' . $field ) ) {
			$error = 'Store saved, but metadata permission was denied.';
			break;
		}
		update_post_meta( $post_id, 'wpsl_' . $field, wp_slash( $value ) );
		if ( (string) get_post_meta( $post_id, 'wpsl_' . $field, true ) !== $value ) {
			$error = 'Store saved, but metadata was not saved: ' . $field;
			break;
		}
	}
	if ( '' === $error && array_key_exists( 'categories', $input ) ) {
		$result = wp_set_object_terms( $post_id, array_values( array_unique( $term_ids ) ), 'wpsl_store_category', false );
		if ( is_wp_error( $result ) ) {
			$error = 'Store saved, but categories were not saved: ' . $result->get_error_message();
		}
	}
	$cleared = mcp_wpsl_clear_transients();
	return array(
		'success' => '' === $error,
		'store' => mcp_wpsl_store_response( get_post( $post_id ) ),
		'cache_cleared' => $cleared > 0,
		'message' => $error ? $error : ( $cleared ? 'Store saved and locator cache cleared.' : 'Store saved. Native locator cache cleanup is unavailable.' ),
	);
}

/**
 * Clear WPSL autoload transients using the plugin method when available.
 */
function mcp_wpsl_clear_transients(): int {
	$admin = $GLOBALS['wpsl_admin'] ?? null;

	if ( ! is_object( $admin ) && defined( 'WPSL_PLUGIN_DIR' ) && is_readable( WPSL_PLUGIN_DIR . 'admin/class-admin.php' ) ) {
		require_once WPSL_PLUGIN_DIR . 'admin/class-admin.php';
		$admin = $GLOBALS['wpsl_admin'] ?? null;
		if ( ! is_object( $admin ) && class_exists( 'WPSL_Admin' ) ) {
			$admin = new WPSL_Admin();
		}
	}

	if ( is_object( $admin ) && method_exists( $admin, 'delete_autoload_transient' ) ) {
		$admin->delete_autoload_transient();
		return 1;
	}

	return 0;
}

/**
 * Sanitize the supported native WPSL settings, preserving dropdown lists.
 *
 * @param string $key Setting key.
 * @param mixed  $value Setting value.
 * @return mixed Null means unsupported or invalid.
 */
function mcp_wpsl_sanitize_setting_value( string $key, $value ) {
	if ( ! is_scalar( $value ) ) {
		return null;
	}
	$booleans = array( 'autoload', 'debug', 'hide_country', 'hide_distance', 'hide_hours', 'listing_below_no_scroll', 'permalinks', 'reset_map', 'show_contact_details', 'show_credits', 'store_url' );
	if ( in_array( $key, $booleans, true ) ) {
		$boolean = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		return null === $boolean ? null : (int) $boolean;
	}
	if ( in_array( $key, array( 'max_results', 'search_radius' ), true ) ) {
		$list = preg_replace( '/\s+/', '', (string) $value );
		$number = 'max_results' === $key ? '[1-9][0-9]*' : '[0-9]+(?:\.[0-9]+)?';
		$item = '(?:' . $number . '|\[' . $number . '\])';
		if ( ! preg_match( '/^' . $item . '(?:,' . $item . ')*$/', $list ) || substr_count( $list, '[' ) > 1 ) {
			return null;
		}
		foreach ( explode( ',', $list ) as $entry ) {
			if ( (float) trim( $entry, '[]' ) <= 0 ) {
				return null;
			}
		}
		return $list;
	}
	if ( in_array( $key, array( 'height', 'autoload_limit' ), true ) ) {
		return is_numeric( $value ) && (int) $value > 0 ? (int) $value : null;
	}
	if ( 'zoom_level' === $key ) {
		return function_exists( 'wpsl_valid_zoom_level' ) ? wpsl_valid_zoom_level( $value ) : null;
	}
	if ( 'auto_zoom_level' === $key ) {
		return function_exists( 'wpsl_get_max_zoom_levels' ) && in_array( (int) $value, wpsl_get_max_zoom_levels(), true ) ? (int) $value : null;
	}
	if ( 'distance_unit' === $key ) {
		return in_array( $value, array( 'km', 'mi' ), true ) ? $value : null;
	}
	if ( 'start_latlng' === $key ) {
		if ( '' === trim( (string) $value ) ) {
			return '';
		}
		$parts = array_map( 'trim', explode( ',', (string) $value ) );
		if ( 2 !== count( $parts ) || ! is_numeric( $parts[0] ) || ! is_numeric( $parts[1] ) || abs( (float) $parts[0] ) > 90 || abs( (float) $parts[1] ) > 180 ) {
			return null;
		}
		return implode( ',', $parts );
	}
	if ( 'template_id' === $key ) {
		return sanitize_key( (string) $value );
	}
	if ( in_array( $key, array( 'start_name', 'api_region' ), true ) ) {
		return sanitize_text_field( (string) $value );
	}
	return null;
}

/** Report whether map credentials exist without returning their values. */
function mcp_wpsl_public_settings( array $settings ): array {
	foreach ( array( 'api_browser_key', 'api_server_key' ) as $key ) {
		$settings[ $key . '_configured' ] = ! empty( $settings[ $key ] );
		unset( $settings[ $key ] );
	}
	return $settings;
}

/**
 * Register WPSL abilities.
 */
function mcp_wpsl_register_abilities(): void {
	if ( ! mcp_wpsl_check_dependencies() ) {
		return;
	}

	wp_register_ability(
		'wpsl/get-status',
		array(
			'label'               => 'Get WP Store Locator Status',
			'description'         => 'Returns WP Store Locator availability, settings, templates, and published store count.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'available'        => array( 'type' => 'boolean' ),
					'active_template'  => array( 'type' => 'string' ),
					'templates'        => array( 'type' => 'array' ),
					'published_stores' => array( 'type' => 'integer' ),
					'settings'         => array( 'type' => 'object' ),
					'permalink_base_translations' => array( 'type' => 'object' ),
					'label_translations' => array( 'type' => 'object' ),
					'navigation_source_language_links' => array( 'type' => 'object' ),
				),
			),
			'execute_callback'    => static function (): array {
				$settings = mcp_wpsl_get_settings();

				return array(
					'available'        => mcp_wpsl_is_available(),
					'active_template'  => isset( $settings['template_id'] ) ? (string) $settings['template_id'] : '',
					'templates'        => mcp_wpsl_list_templates(),
					'published_stores' => mcp_wpsl_count_published_stores(),
					'settings'         => mcp_wpsl_public_settings( $settings ),
					'permalink_base_translations' => (object) mcp_wpsl_get_permalink_base_translations(),
					'label_translations' => (object) mcp_wpsl_get_label_translations(),
					'navigation_source_language_links' => (object) mcp_wpsl_get_nav_source_language_links(),
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_wpsl_settings' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	wp_register_ability(
		'wpsl/list-stores',
		array(
			'label'               => 'List WP Store Locator Stores',
			'description'         => 'Lists WPSL store posts with normalized address/contact/location metadata.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'status'   => array( 'type' => 'string' ),
					'search'   => array( 'type' => 'string' ),
					'per_page' => array( 'type' => 'integer' ),
					'page'     => array( 'type' => 'integer' ),
					'orderby'  => array( 'type' => 'string' ),
					'order'    => array( 'type' => 'string' ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'stores' => array( 'type' => 'array' ),
					'total'  => array( 'type' => array( 'integer', 'null' ) ),
					'pages'  => array( 'type' => array( 'integer', 'null' ) ),
					'has_more' => array( 'type' => 'boolean' ),
				),
			),
			'execute_callback'    => static function ( $input = array() ): array {
				$input    = is_array( $input ) ? $input : array();
				$per_page = isset( $input['per_page'] ) ? min( 100, max( 1, absint( $input['per_page'] ) ) ) : 50;
				$page     = isset( $input['page'] ) ? max( 1, absint( $input['page'] ) ) : 1;
				$status   = isset( $input['status'] ) ? sanitize_key( (string) $input['status'] ) : 'publish';
				$order    = isset( $input['order'] ) && 'ASC' === strtoupper( (string) $input['order'] ) ? 'ASC' : 'DESC';
				$orderby  = isset( $input['orderby'] ) ? sanitize_key( (string) $input['orderby'] ) : 'title';

				$query = new WP_Query(
					array(
						'post_type'      => 'wpsl_stores',
						'post_status'    => $status,
						'perm'           => 'readable',
						'posts_per_page' => $per_page,
						'paged'          => $page,
						'orderby'        => in_array( $orderby, array( 'title', 'date', 'modified', 'menu_order', 'id' ), true ) ? ( 'id' === $orderby ? 'ID' : $orderby ) : 'title',
						'order'          => $order,
						's'              => isset( $input['search'] ) ? sanitize_text_field( (string) $input['search'] ) : '',
					)
				);

				$type = get_post_type_object( 'wpsl_stores' );
				$can_count = $type && current_user_can( $type->cap->edit_others_posts ) && current_user_can( $type->cap->read_private_posts );
				return array(
					'stores' => array_values( array_map( 'mcp_wpsl_store_response', array_filter( $query->posts, static function ( $post ): bool { return current_user_can( 'read_post', $post->ID ); } ) ) ),
					'total'  => $can_count ? (int) $query->found_posts : null,
					'pages'  => $can_count ? (int) $query->max_num_pages : null,
					'has_more' => count( $query->posts ) === $per_page,
				);
			},
			'permission_callback' => static function (): bool {
				$type = get_post_type_object( 'wpsl_stores' );
				return $type && current_user_can( $type->cap->edit_posts );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	wp_register_ability(
		'wpsl/get-store',
		array(
			'label'               => 'Get WP Store Locator Store',
			'description'         => 'Gets one WPSL store post with normalized WPSL metadata.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'id' ),
				'properties'           => array(
					'id' => array( 'type' => 'integer' ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => static function ( $input = array() ): array {
				$input = is_array( $input ) ? $input : array();
				$id    = isset( $input['id'] ) ? absint( $input['id'] ) : 0;
				$post  = $id ? get_post( $id ) : null;

				if ( ! $post || 'wpsl_stores' !== $post->post_type ) {
					return array( 'success' => false, 'message' => 'WPSL store not found.' );
				}

				if ( ! current_user_can( 'read_post', $id ) ) {
					return array( 'success' => false, 'message' => 'You cannot read this store.' );
				}

				return array( 'success' => true, 'store' => mcp_wpsl_store_response( $post ) );
			},
			'permission_callback' => static function (): bool {
				$type = get_post_type_object( 'wpsl_stores' );
				return $type && current_user_can( $type->cap->edit_posts );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	wp_register_ability(
		'wpsl/list-categories',
		array(
			'label'               => 'List WP Store Locator Categories',
			'description'         => 'Lists WPSL store categories.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'hide_empty' => array( 'type' => 'boolean' ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => static function ( $input = array() ): array {
				$input = is_array( $input ) ? $input : array();
				$terms = get_terms(
					array(
						'taxonomy'   => 'wpsl_store_category',
						'hide_empty' => ! empty( $input['hide_empty'] ),
					)
				);

				if ( is_wp_error( $terms ) ) {
					return array( 'success' => false, 'message' => $terms->get_error_message(), 'categories' => array() );
				}

				return array(
					'success'    => true,
					'categories' => array_map(
						static function ( WP_Term $term ): array {
							return array(
								'id'    => (int) $term->term_id,
								'name'  => (string) $term->name,
								'slug'  => (string) $term->slug,
								'count' => (int) $term->count,
							);
						},
						$terms
					),
				);
			},
			'permission_callback' => static function (): bool {
				$type = get_post_type_object( 'wpsl_stores' );
				return $type && current_user_can( $type->cap->edit_posts );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	wp_register_ability(
		'wpsl/set-template',
		array(
			'label'               => 'Set WP Store Locator Template',
			'description'         => 'Updates the WP Store Locator search template setting to an installed template ID.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'template_id' ),
				'properties'           => array(
					'template_id'             => array(
						'type'        => 'string',
						'description' => 'Template ID to activate, such as below_map or dynamic_columns.',
					),
					'listing_below_no_scroll' => array(
						'type'        => 'boolean',
						'description' => 'Optional WPSL no-scroll setting for below-map style templates.',
					),
					'dry_run'                 => array(
						'type'        => 'boolean',
						'description' => 'Return the planned update without saving it.',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'           => array( 'type' => 'boolean' ),
					'previous_template' => array( 'type' => 'string' ),
					'active_template'   => array( 'type' => 'string' ),
					'message'           => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => static function ( $input = array() ): array {
				$input       = is_array( $input ) ? $input : array();
				$template_id = isset( $input['template_id'] ) ? sanitize_key( (string) $input['template_id'] ) : '';

				if ( '' === $template_id ) {
					return array( 'success' => false, 'message' => 'template_id is required.' );
				}

				$available_ids = array_column( array_filter( mcp_wpsl_list_templates(), static function ( $template ): bool { return 'yes' === $template['exists']; } ), 'id' );
				if ( ! in_array( $template_id, $available_ids, true ) ) {
					return array( 'success' => false, 'message' => 'Unknown WPSL template_id.' );
				}

				$settings          = mcp_wpsl_get_settings();
				$previous_template = isset( $settings['template_id'] ) ? (string) $settings['template_id'] : '';
				$settings['template_id'] = $template_id;

				if ( array_key_exists( 'listing_below_no_scroll', $input ) ) {
					$settings['listing_below_no_scroll'] = ! empty( $input['listing_below_no_scroll'] ) ? 1 : 0;
				}

				if ( ! empty( $input['dry_run'] ) ) {
					return array(
						'success'           => true,
						'previous_template' => $previous_template,
						'active_template'   => $template_id,
						'message'           => 'Dry run only. No settings saved.',
					);
				}

				update_option( 'wpsl_settings', $settings );
				if ( mcp_wpsl_get_settings() !== $settings ) {
					return array( 'success' => false, 'message' => 'WPSL settings were not saved.' );
				}
				mcp_wpsl_clear_transients();

				return array(
					'success'           => true,
					'previous_template' => $previous_template,
					'active_template'   => $template_id,
					'message'           => 'WPSL template updated.',
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_wpsl_settings' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	wp_register_ability(
		'wpsl/update-settings',
		array(
			'label'               => 'Update WP Store Locator Settings',
			'description'         => 'Updates supported WPSL settings with WPSL-aware validation.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'settings' ),
				'properties'           => array(
					'settings' => array( 'type' => 'object' ),
					'dry_run'  => array( 'type' => 'boolean' ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => static function ( $input = array() ): array {
				$input = is_array( $input ) ? $input : array();
				$patch = isset( $input['settings'] ) && is_array( $input['settings'] ) ? $input['settings'] : array();
				if ( empty( $patch ) ) {
					return array( 'success' => false, 'message' => 'settings object is required.' );
				}

				$settings = mcp_wpsl_get_settings();
				$updated  = array();
				$skipped  = array();

				foreach ( $patch as $key => $value ) {
					$key       = sanitize_key( (string) $key );
					$sanitized = mcp_wpsl_sanitize_setting_value( $key, $value );
					if ( null === $sanitized ) {
						$skipped[] = $key;
						continue;
					}

					if ( 'template_id' === $key ) {
						$available_ids = array_column( array_filter( mcp_wpsl_list_templates(), static function ( $template ): bool { return 'yes' === $template['exists']; } ), 'id' );
						if ( ! in_array( $sanitized, $available_ids, true ) ) {
							$skipped[] = $key;
							continue;
						}
					}

					$settings[ $key ] = $sanitized;
					$updated[ $key ]  = $sanitized;
				}

				if ( empty( $updated ) ) {
					return array( 'success' => false, 'updated' => array(), 'skipped' => $skipped, 'message' => 'No supported valid settings were supplied.' );
				}
				if ( empty( $input['dry_run'] ) ) {
					$previous = mcp_wpsl_get_settings();
					update_option( 'wpsl_settings', $settings );
					if ( mcp_wpsl_get_settings() !== $settings ) {
						return array( 'success' => false, 'message' => 'WPSL settings were not saved.' );
					}
					if ( isset( $updated['permalinks'] ) && ( $previous['permalinks'] ?? null ) !== $updated['permalinks'] ) {
						delete_option( 'mcp_wpsl_version' );
					}
					mcp_wpsl_clear_transients();
				}

				return array(
					'success'  => true,
					'updated'  => $updated,
					'skipped'  => $skipped,
					'settings' => mcp_wpsl_public_settings( $settings ),
					'message'  => empty( $input['dry_run'] ) ? 'WPSL settings updated.' : 'Dry run only. No settings saved.',
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_wpsl_settings' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	wp_register_ability(
		'wpsl/update-permalink-base-translations',
		array(
			'label'               => 'Update WP Store Locator Permalink Base Translations',
			'description'         => 'Configures language-specific WPSL store permalink bases, such as mapping en to stores, without hardcoding site-specific paths in the plugin.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'translations' ),
				'properties'           => array(
					'translations' => array(
						'type'        => 'object',
						'description' => 'Object keyed by language code with slug base values, for example {"en":"stores"}. Empty values remove mappings.',
					),
					'dry_run'      => array( 'type' => 'boolean' ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'      => array( 'type' => 'boolean' ),
					'previous'     => array( 'type' => 'object' ),
					'translations' => array( 'type' => 'object' ),
					'message'      => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => static function ( $input = array() ): array {
				$input = is_array( $input ) ? $input : array();
				$raw   = isset( $input['translations'] ) && is_array( $input['translations'] ) ? $input['translations'] : array();

				$translations = array();
				foreach ( $raw as $language_code => $base ) {
					$language_code = sanitize_key( (string) $language_code );
					$base          = sanitize_title( (string) $base );
					if ( '' === $language_code || '' === $base ) {
						continue;
					}

					$translations[ $language_code ] = $base;
				}

				$previous = mcp_wpsl_get_permalink_base_translations();
				if ( empty( $input['dry_run'] ) ) {
					update_option( MCP_WPSL_BASE_TRANSLATIONS, $translations, false );
					if ( mcp_wpsl_get_permalink_base_translations() !== $translations ) {
						return array( 'success' => false, 'message' => 'Permalink mappings were not saved.' );
					}
					delete_option( 'mcp_wpsl_version' );
				}

				return array(
					'success'      => true,
					'previous'     => (object) $previous,
					'translations' => (object) $translations,
					'message'      => empty( $input['dry_run'] ) ? 'WPSL permalink base translations updated.' : 'Dry run only. No settings saved.',
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => true,
				),
			),
		)
	);

	wp_register_ability(
		'wpsl/update-label-translations',
		array(
			'label'               => 'Update WP Store Locator Label Translations',
			'description'         => 'Configures the search field label and search button text per language for the dynamic_columns template. Other labels remain owned by WP Store Locator.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'translations' ),
				'properties'           => array(
					'translations' => array(
						'type'        => 'object',
						'description' => 'Object keyed by language code with WPSL label keys, for example {"en":{"search_label":"Location/city","search_btn_label":"Search"}}. Empty values remove mappings.',
					),
					'dry_run'      => array( 'type' => 'boolean' ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'      => array( 'type' => 'boolean' ),
					'previous'     => array( 'type' => 'object' ),
					'translations' => array( 'type' => 'object' ),
					'message'      => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => static function ( $input = array() ): array {
				$input        = is_array( $input ) ? $input : array();
				foreach ( $input['translations'] ?? array() as $labels ) {
					if ( ! is_array( $labels ) || array_diff( array_keys( $labels ), mcp_wpsl_supported_label_keys() ) ) {
						return array( 'success' => false, 'message' => 'Only search_label and search_btn_label are supported by the columns template.' );
					}
				}
				$translations = mcp_wpsl_sanitize_label_translations( $input['translations'] ?? array() );
				$previous     = mcp_wpsl_get_label_translations();

				if ( empty( $input['dry_run'] ) ) {
					update_option( MCP_WPSL_LABEL_TRANSLATIONS, $translations, false );
					if ( mcp_wpsl_get_label_translations() !== $translations ) {
						return array( 'success' => false, 'message' => 'Label translations were not saved.' );
					}
					mcp_wpsl_clear_transients();
				}

				return array(
					'success'      => true,
					'previous'     => (object) $previous,
					'translations' => (object) $translations,
					'message'      => empty( $input['dry_run'] ) ? 'WPSL label translations updated.' : 'Dry run only. No settings saved.',
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => true,
				),
			),
		)
	);

	wp_register_ability(
		'wpsl/update-navigation-source-language-links',
		array(
			'label'               => 'Update WP Store Locator Navigation Source Language Links',
			'description'         => 'Configures frontend languages where native WPSL store menu items should render with source-language store links and native source menu labels.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'mappings' ),
				'properties'           => array(
					'mappings' => array(
						'type'        => 'object',
						'description' => 'Object keyed by frontend language code with source language code values, for example {"fr":"en"}. Empty or same-language values remove mappings.',
					),
					'dry_run'  => array( 'type' => 'boolean' ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'  => array( 'type' => 'boolean' ),
					'previous' => array( 'type' => 'object' ),
					'mappings' => array( 'type' => 'object' ),
					'message'  => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => static function ( $input = array() ): array {
				$input    = is_array( $input ) ? $input : array();
				$mappings = mcp_wpsl_sanitize_nav_source_language_links( $input['mappings'] ?? array() );
				$previous = mcp_wpsl_get_nav_source_language_links();

				if ( empty( $input['dry_run'] ) ) {
					update_option( MCP_WPSL_NAV_SOURCE_LINKS, $mappings, false );
					if ( mcp_wpsl_get_nav_source_language_links() !== $mappings ) {
						return array( 'success' => false, 'message' => 'Navigation language mappings were not saved.' );
					}
				}

				return array(
					'success'  => true,
					'previous' => (object) $previous,
					'mappings' => (object) $mappings,
					'message'  => empty( $input['dry_run'] ) ? 'WPSL navigation source language links updated.' : 'Dry run only. No settings saved.',
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => true,
				),
			),
		)
	);

	wp_register_ability(
		'wpsl/create-store',
		array(
			'label'               => 'Create WP Store Locator Store',
			'description'         => 'Creates a WPSL store post and supported WPSL metadata.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'title' ),
				'properties'           => array(
					'title'      => array( 'type' => 'string' ),
					'content'    => array( 'type' => 'string' ),
					'excerpt'    => array( 'type' => 'string' ),
					'status'     => array( 'type' => 'string' ),
					'slug'       => array( 'type' => 'string' ),
					'meta'       => array( 'type' => 'object' ),
					'categories' => array( 'type' => 'array' ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => static function ( $input = array() ): array {
				return mcp_wpsl_save_store( is_array( $input ) ? $input : array() );
			},
			'permission_callback' => static function (): bool {
				$type = get_post_type_object( 'wpsl_stores' );
				return $type && current_user_can( $type->cap->create_posts );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
			),
		)
	);

	wp_register_ability(
		'wpsl/update-store',
		array(
			'label'               => 'Update WP Store Locator Store',
			'description'         => 'Updates a WPSL store post and supported WPSL metadata.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'required'             => array( 'id' ),
				'properties'           => array(
					'id'         => array( 'type' => 'integer' ),
					'title'      => array( 'type' => 'string' ),
					'content'    => array( 'type' => 'string' ),
					'excerpt'    => array( 'type' => 'string' ),
					'status'     => array( 'type' => 'string' ),
					'slug'       => array( 'type' => 'string' ),
					'meta'       => array( 'type' => 'object' ),
					'categories' => array( 'type' => 'array' ),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => static function ( $input = array() ): array {
				$input = is_array( $input ) ? $input : array();
				$id = isset( $input['id'] ) ? absint( $input['id'] ) : 0;
				if ( ! $id ) {
					return array( 'success' => false, 'message' => 'Store ID is required.' );
				}
				return mcp_wpsl_save_store( $input, $id );
			},
			'permission_callback' => static function (): bool {
				$type = get_post_type_object( 'wpsl_stores' );
				return $type && current_user_can( $type->cap->edit_posts );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
			),
		)
	);

	wp_register_ability(
		'wpsl/clear-transients',
		array(
			'label'               => 'Clear WP Store Locator Transients',
			'description'         => 'Clears WP Store Locator autoload transient cache.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(),
				'additionalProperties' => false,
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => static function (): array {
				$cleared = mcp_wpsl_clear_transients();
				return array(
					'success' => $cleared > 0,
					'deleted' => $cleared,
					'message' => $cleared ? 'Native WPSL cache invalidation completed; the native method does not return a deletion count.' : 'Native WPSL cache invalidation is unavailable.',
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_wpsl_settings' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
			),
		)
	);
}
add_action( 'wp_abilities_api_init', 'mcp_wpsl_register_abilities' );
