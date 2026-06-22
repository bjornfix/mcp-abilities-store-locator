<?php
/**
 * Plugin Name: MCP Abilities - Store Locator
 * Plugin URI: https://devenia.com
 * Description: Narrow MCP abilities and maintained frontend template support for WP Store Locator.
 * Version: 0.1.6
 * Author: Devenia
 * Author URI: https://devenia.com
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

const MCP_WPSL_G1_COLUMNS_TEMPLATE = 'g1_columns';
const MCP_WPSL_VERSION             = '0.1.6';
const MCP_WPSL_EN_STORE_BASE       = 'stores';

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
 * Register the English WPSL store rewrite base.
 */
function mcp_wpsl_register_english_store_rewrite(): void {
	add_rewrite_rule(
		'^en/' . MCP_WPSL_EN_STORE_BASE . '/([^/]+)/?$',
		'index.php?wpsl_stores=$matches[1]&lang=en',
		'top'
	);
}
add_action( 'init', 'mcp_wpsl_register_english_store_rewrite', 8 );

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
 * Use an English URL base for English WPML translations of WPSL stores.
 *
 * @param string  $post_link The generated permalink.
 * @param WP_Post $post      The store post.
 */
function mcp_wpsl_filter_english_store_permalink( string $post_link, WP_Post $post ): string {
	if ( 'wpsl_stores' !== $post->post_type || 'en' !== mcp_wpsl_get_store_language_code( (int) $post->ID ) ) {
		return $post_link;
	}

	return home_url( user_trailingslashit( 'en/' . MCP_WPSL_EN_STORE_BASE . '/' . $post->post_name ) );
}
add_filter( 'post_type_link', 'mcp_wpsl_filter_english_store_permalink', 20, 2 );

/**
 * Redirect old mixed-language English store URLs to the English canonical base.
 */
function mcp_wpsl_redirect_english_store_canonical_base(): void {
	if ( ! is_singular( 'wpsl_stores' ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( ! $post_id || 'en' !== mcp_wpsl_get_store_language_code( (int) $post_id ) ) {
		return;
	}

	$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '';
	$request_path = $request_uri;
	$request_path = (string) strtok( $request_path, '?' );
	if ( ! str_starts_with( $request_path, '/en/butikker/' ) ) {
		return;
	}

	$canonical = get_permalink( $post_id );
	if ( $canonical ) {
		wp_safe_redirect( $canonical, 301 );
		exit;
	}
}
add_action( 'template_redirect', 'mcp_wpsl_redirect_english_store_canonical_base', 1 );

/**
 * Register the maintained G1 columns store-locator template with WP Store Locator.
 *
 * @param array<int,array<string,string>> $templates Existing WPSL templates.
 * @return array<int,array<string,string>>
 */
function mcp_wpsl_register_g1_columns_template( array $templates ): array {
	$templates[] = array(
		'id'   => MCP_WPSL_G1_COLUMNS_TEMPLATE,
		'name' => __( 'G1 dealer columns', 'mcp-abilities-store-locator' ),
		'path' => plugin_dir_path( __FILE__ ) . 'templates/g1-store-listings-columns.php',
	);

	return $templates;
}
add_filter( 'wpsl_templates', 'mcp_wpsl_register_g1_columns_template' );

/**
 * Check if the current WPSL setting selects the maintained columns template.
 */
function mcp_wpsl_uses_g1_columns_template(): bool {
	$settings = mcp_wpsl_get_settings();
	return isset( $settings['template_id'] ) && MCP_WPSL_G1_COLUMNS_TEMPLATE === (string) $settings['template_id'];
}

/**
 * Add the minimal frontend layout required by the maintained WPSL columns template.
 */
function mcp_wpsl_enqueue_columns_template_style(): void {
	if ( ! mcp_wpsl_uses_g1_columns_template() ) {
		return;
	}

	$css  = "#wpsl-wrap.g1-wpsl-columns #wpsl-result-list{width:100%;margin:12px 0 0;}\n";
	$css .= "#wpsl-wrap.g1-wpsl-columns #wpsl-stores,#wpsl-wrap.g1-wpsl-columns #wpsl-direction-details{height:auto!important;overflow:visible;}\n";
	$css .= "#wpsl-wrap.g1-wpsl-columns #wpsl-stores>ul{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;margin:0;padding:0;}\n";
	$css .= "#wpsl-wrap.g1-wpsl-columns #wpsl-result-list li{box-sizing:border-box;width:auto;padding:0;border-bottom:0;}\n";
	$css .= "#wpsl-wrap.g1-wpsl-columns #wpsl-result-list li.g1-wpsl-card{height:100%;padding:0 0 20px;border:0;border-bottom:1px solid #e5e5e5;background:#fff;}\n";
	$css .= "#wpsl-wrap.g1-wpsl-columns #wpsl-result-list li.g1-wpsl-card:last-child{border-bottom:0;}\n";
	$css .= ".elementor-widget-shortcode .wpsl-gmap-canvas{margin-bottom:0;}\n";
	$css .= "@media (max-width:1024px){#wpsl-wrap.g1-wpsl-columns #wpsl-stores>ul{grid-template-columns:repeat(2,minmax(0,1fr));}}\n";
	$css .= "@media (max-width:767px){#wpsl-wrap.g1-wpsl-columns .wpsl-search{padding-top:14px;}#wpsl-wrap.g1-wpsl-columns #wpsl-stores>ul{grid-template-columns:1fr;gap:20px;}}\n";

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
	if ( ! mcp_wpsl_uses_g1_columns_template() ) {
		return $template;
	}

	return str_replace( '<li data-store-id="<%= id %>">', '<li class="g1-wpsl-card" data-store-id="<%= id %>">', $template );
}
add_filter( 'wpsl_listing_template', 'mcp_wpsl_columns_listing_template', 20 );

/**
 * Return Norwegian frontend labels for WP Store Locator strings that can bypass saved settings.
 *
 * @return array<string,string>
 */
function mcp_wpsl_get_norwegian_labels(): array {
	return array(
		'Your location' => 'Sted/by',
		'Search' => 'Søk',
		'Searching...' => 'Søker...',
		'Search radius' => 'Søkeradius',
		'No results found' => 'Beklager, ingen butikk funnet!',
		'Results' => 'resultater',
		'More info' => 'Mer informasjon',
		'Directions' => '',
		'No route could be found between the origin and destination' => 'Ingen rute ble funnet mellom opprinnelses- og destinasjonsstedet',
		'Back' => 'Tilbake',
		'Street view' => 'Gatevisning',
		'Zoom here' => 'Zoom her',
		'Something went wrong, please try again!' => 'Noe gikk galt, vennligst prøv igjen!',
		'API usage limit reached' => 'Grensen for API-bruk nådd',
		'Phone' => 'Telefon',
		'Fax' => 'Faks',
		'Email' => 'E-post',
		'Url' => 'url',
		'Hours' => 'Timer',
		'Start location' => 'Startsted',
		'Category filter' => 'Kategorifilter',
		'All' => 'Alle',
	);
}

/**
 * Keep Store Locator frontend labels Norwegian when WPML String Translation bypasses WPSL settings.
 *
 * @param string $translation Translated string.
 * @param string $text        Original string.
 * @param string $domain      Text domain.
 */
function mcp_wpsl_translate_norwegian_label( string $translation, string $text, string $domain ): string {
	if ( ! in_array( $domain, array( 'wp-store-locator', 'wpsl' ), true ) ) {
		return $translation;
	}

	$locale = determine_locale();
	if ( ! in_array( $locale, array( 'nb_NO', 'nn_NO' ), true ) && ! str_starts_with( (string) $locale, 'no' ) ) {
		return $translation;
	}

	$labels = mcp_wpsl_get_norwegian_labels();
	return array_key_exists( $text, $labels ) ? $labels[ $text ] : $translation;
}
add_filter( 'gettext', 'mcp_wpsl_translate_norwegian_label', 20, 3 );

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
				echo '<div class="notice notice-error"><p><strong>MCP Abilities - Store Locator</strong> requires the Abilities API plugin to be installed and activated.</p></div>';
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

		$normalized[] = array(
			'id'     => isset( $template['id'] ) ? (string) $template['id'] : '',
			'name'   => isset( $template['name'] ) ? wp_strip_all_tags( (string) $template['name'] ) : '',
			'exists' => isset( $template['path'] ) && file_exists( (string) $template['path'] ) ? 'yes' : 'no',
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
 * Apply supported WPSL store meta fields.
 *
 * @param int   $post_id Store post ID.
 * @param array $meta Input meta keyed without wpsl_ prefix.
 */
function mcp_wpsl_update_store_meta( int $post_id, array $meta ): void {
	foreach ( mcp_wpsl_store_meta_fields() as $field => $_type ) {
		if ( ! array_key_exists( $field, $meta ) ) {
			continue;
		}

		update_post_meta( $post_id, 'wpsl_' . $field, mcp_wpsl_sanitize_store_meta_value( $field, $meta[ $field ] ) );
	}
}

/**
 * Apply WPSL store categories by IDs and/or slugs.
 *
 * @param int   $post_id Store post ID.
 * @param array $input Category input.
 */
function mcp_wpsl_update_store_categories( int $post_id, array $input ): void {
	$term_ids = array();

	foreach ( $input as $item ) {
		if ( is_numeric( $item ) ) {
			$term_ids[] = (int) $item;
			continue;
		}

		if ( is_string( $item ) && '' !== $item ) {
			$term = get_term_by( 'slug', sanitize_title( $item ), 'wpsl_store_category' );
			if ( $term ) {
				$term_ids[] = (int) $term->term_id;
			}
		}
	}

	wp_set_object_terms( $post_id, array_values( array_unique( $term_ids ) ), 'wpsl_store_category', false );
}

/**
 * Clear WPSL autoload transients using the plugin method when available.
 */
function mcp_wpsl_clear_transients(): int {
	global $wpsl_admin;

	if ( is_object( $wpsl_admin ) && method_exists( $wpsl_admin, 'delete_autoload_transient' ) ) {
		$wpsl_admin->delete_autoload_transient();
		return 1;
	}

	return 0;
}

/**
 * Sanitize a supported WPSL setting by key.
 *
 * @param string $key Setting key.
 * @param mixed  $value Setting value.
 * @return mixed
 */
function mcp_wpsl_sanitize_setting_value( string $key, $value ) {
	$boolean_keys = array(
		'autoload',
		'debug',
		'hide_country',
		'hide_distance',
		'hide_hours',
		'listing_below_no_scroll',
		'permalinks',
		'reset_map',
		'show_contact_details',
		'show_credits',
		'store_url',
	);

	$integer_keys = array(
		'height',
		'max_autoload_results',
		'max_results',
		'search_radius',
		'zoom_level',
		'auto_zoom_level',
	);

	if ( in_array( $key, $boolean_keys, true ) ) {
		return ! empty( $value ) ? 1 : 0;
	}

	if ( in_array( $key, $integer_keys, true ) ) {
		return absint( $value );
	}

	if ( 'template_id' === $key ) {
		return sanitize_key( (string) $value );
	}

	if ( 'start_name' === $key || 'latlng' === $key || 'map_region' === $key || 'distance_unit' === $key ) {
		return sanitize_text_field( (string) $value );
	}

	return null;
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
				),
			),
			'execute_callback'    => static function (): array {
				$settings = mcp_wpsl_get_settings();

				return array(
					'available'        => mcp_wpsl_is_available(),
					'active_template'  => isset( $settings['template_id'] ) ? (string) $settings['template_id'] : '',
					'templates'        => mcp_wpsl_list_templates(),
					'published_stores' => mcp_wpsl_count_published_stores(),
					'settings'         => $settings,
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
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
					'total'  => array( 'type' => 'integer' ),
					'pages'  => array( 'type' => 'integer' ),
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
						'posts_per_page' => $per_page,
						'paged'          => $page,
						'orderby'        => in_array( $orderby, array( 'title', 'date', 'modified', 'menu_order', 'ID' ), true ) ? $orderby : 'title',
						'order'          => $order,
						's'              => isset( $input['search'] ) ? sanitize_text_field( (string) $input['search'] ) : '',
					)
				);

				return array(
					'stores' => array_map( 'mcp_wpsl_store_response', $query->posts ),
					'total'  => (int) $query->found_posts,
					'pages'  => (int) $query->max_num_pages,
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_posts' );
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

				return array( 'success' => true, 'store' => mcp_wpsl_store_response( $post ) );
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_posts' );
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
				return current_user_can( 'edit_posts' );
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
						'description' => 'Template ID to activate, such as below_map or g1_columns.',
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

				$available_ids = array_column( mcp_wpsl_list_templates(), 'id' );
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

				return array(
					'success'           => true,
					'previous_template' => $previous_template,
					'active_template'   => $template_id,
					'message'           => 'WPSL template updated.',
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
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
						$available_ids = array_column( mcp_wpsl_list_templates(), 'id' );
						if ( ! in_array( $sanitized, $available_ids, true ) ) {
							$skipped[] = $key;
							continue;
						}
					}

					$settings[ $key ] = $sanitized;
					$updated[ $key ]  = $sanitized;
				}

				if ( empty( $input['dry_run'] ) ) {
					update_option( 'wpsl_settings', $settings );
				}

				return array(
					'success'  => true,
					'updated'  => $updated,
					'skipped'  => $skipped,
					'settings' => $settings,
					'message'  => empty( $input['dry_run'] ) ? 'WPSL settings updated.' : 'Dry run only. No settings saved.',
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
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
				$input = is_array( $input ) ? $input : array();
				$title = isset( $input['title'] ) ? sanitize_text_field( (string) $input['title'] ) : '';
				if ( '' === $title ) {
					return array( 'success' => false, 'message' => 'title is required.' );
				}

				$post_id = wp_insert_post(
					array(
						'post_type'    => 'wpsl_stores',
						'post_status'  => isset( $input['status'] ) ? sanitize_key( (string) $input['status'] ) : 'draft',
						'post_title'   => $title,
						'post_name'    => isset( $input['slug'] ) ? sanitize_title( (string) $input['slug'] ) : '',
						'post_content' => isset( $input['content'] ) ? wp_kses_post( (string) $input['content'] ) : '',
						'post_excerpt' => isset( $input['excerpt'] ) ? sanitize_textarea_field( (string) $input['excerpt'] ) : '',
					),
					true
				);

				if ( is_wp_error( $post_id ) ) {
					return array( 'success' => false, 'message' => $post_id->get_error_message() );
				}

				if ( isset( $input['meta'] ) && is_array( $input['meta'] ) ) {
					mcp_wpsl_update_store_meta( (int) $post_id, $input['meta'] );
				}

				if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
					mcp_wpsl_update_store_categories( (int) $post_id, $input['categories'] );
				}

				mcp_wpsl_clear_transients();
				return array( 'success' => true, 'store' => mcp_wpsl_store_response( get_post( (int) $post_id ) ) );
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'publish_posts' );
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
				$id    = isset( $input['id'] ) ? absint( $input['id'] ) : 0;
				$post  = $id ? get_post( $id ) : null;
				if ( ! $post || 'wpsl_stores' !== $post->post_type ) {
					return array( 'success' => false, 'message' => 'WPSL store not found.' );
				}

				$updates = array( 'ID' => $id );
				if ( isset( $input['title'] ) ) {
					$updates['post_title'] = sanitize_text_field( (string) $input['title'] );
				}
				if ( isset( $input['content'] ) ) {
					$updates['post_content'] = wp_kses_post( (string) $input['content'] );
				}
				if ( isset( $input['excerpt'] ) ) {
					$updates['post_excerpt'] = sanitize_textarea_field( (string) $input['excerpt'] );
				}
				if ( isset( $input['status'] ) ) {
					$updates['post_status'] = sanitize_key( (string) $input['status'] );
				}
				if ( isset( $input['slug'] ) ) {
					$updates['post_name'] = sanitize_title( (string) $input['slug'] );
				}

				if ( count( $updates ) > 1 ) {
					$result = wp_update_post( $updates, true );
					if ( is_wp_error( $result ) ) {
						return array( 'success' => false, 'message' => $result->get_error_message() );
					}
				}

				if ( isset( $input['meta'] ) && is_array( $input['meta'] ) ) {
					mcp_wpsl_update_store_meta( $id, $input['meta'] );
				}
				if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
					mcp_wpsl_update_store_categories( $id, $input['categories'] );
				}

				mcp_wpsl_clear_transients();
				return array( 'success' => true, 'store' => mcp_wpsl_store_response( get_post( $id ) ) );
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_posts' );
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
				return array(
					'success' => true,
					'deleted' => mcp_wpsl_clear_transients(),
					'message' => 'WPSL transients cleared.',
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
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
