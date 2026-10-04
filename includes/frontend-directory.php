<?php
/**
 * Connect native WP Store Locator search to an editable Elementor Loop Grid.
 *
 * @package MCP_Abilities_WPSL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MCP_WPSL_ELEMENTOR_TEMPLATE = 'elementor_directory';

/** Return the current shortcode's template, with the saved template as fallback. */
function mcp_wpsl_active_template_id(): string {
	if ( function_exists( 'wpsl_get_service' ) ) {
		$shortcodes = wpsl_get_service( 'shortcodes' );
		if ( is_object( $shortcodes ) && method_exists( $shortcodes, 'get_att' ) ) {
			$template = $shortcodes->get_att( 'template', '' );
			if ( is_string( $template ) && '' !== $template ) {
				return $template;
			}
		}
	}

	$settings = mcp_wpsl_get_settings();
	return (string) ( $settings['template_id'] ?? '' );
}

/** Recognise only the two maintained frontend templates. */
function mcp_wpsl_is_maintained_template(): bool {
	return in_array( mcp_wpsl_active_template_id(), array( MCP_WPSL_COLUMNS_TEMPLATE, MCP_WPSL_ELEMENTOR_TEMPLATE ), true );
}

/** Separate a numeric postcode joined directly to a city, preserving other input. */
function mcp_wpsl_normalize_location( string $value ): string {
	$normalized = preg_replace( '/^(\d{3,10})(\p{L}[\p{L}\p{M}\s.\x{0027}-]*)$/u', '$1 $2', $value );
	return is_string( $normalized ) ? $normalized : $value;
}

/** Normalise the native input, including a validated footer submission. */
function mcp_wpsl_normalize_search_input( string $value ): string {
	if ( ! mcp_wpsl_is_maintained_template() ) {
		return $value;
	}
	if ( '' === $value ) {
		$value = mcp_wpsl_get_footer_search_post_value();
	}
	return mcp_wpsl_normalize_location( $value );
}
add_filter( 'wpsl_search_input', 'mcp_wpsl_normalize_search_input', 30 );

/** Apply configured plain-text labels to native JavaScript labels. */
function mcp_wpsl_translate_frontend_labels( array $labels ): array {
	if ( ! mcp_wpsl_is_maintained_template() ) {
		return $labels;
	}
	$translations = mcp_wpsl_get_label_translations();
	$current      = $translations[ mcp_wpsl_current_language_code() ] ?? array();
	if ( isset( $current['directions_label'] ) ) {
		$labels['directions'] = $current['directions_label'];
	}
	if ( isset( $current['adjust_search_label'] ) ) {
		$labels['adjustSearch'] = $current['adjust_search_label'];
	}
	if ( isset( $current['no_results_label'] ) ) {
		$labels['noResults'] = esc_html( $current['no_results_label'] );
		if ( ! empty( $labels['adjustSearch'] ) ) {
			$labels['noResults'] .= '<br><br>' . esc_html( $labels['adjustSearch'] );
		}
	}
	return $labels;
}
add_filter( 'wpsl_labels', 'mcp_wpsl_translate_frontend_labels', 30 );

/** Native Elementor produces all published store rows in the current language. */
function mcp_wpsl_elementor_directory_query( WP_Query $query ): void {
	$query->set( 'post_type', 'wpsl_stores' );
	$query->set( 'post_status', 'publish' );
	$query->set( 'posts_per_page', -1 );
	$query->set( 'suppress_filters', false );
}
add_action( 'elementor/query/mcp_wpsl_directory', 'mcp_wpsl_elementor_directory_query' );

/** Mark only the opt-in native Loop Grid for result synchronisation. */
function mcp_wpsl_prepare_directory_widget( $widget ): void {
	if ( 'loop-grid' !== $widget->get_name() || 'mcp_wpsl_directory' !== $widget->get_settings_for_display( 'post_query_query_id' ) ) {
		return;
	}
	$widget->add_render_attribute( '_wrapper', 'class', 'mcp-wpsl-directory' );
	$widget->add_render_attribute( '_wrapper', 'id', 'mcp-wpsl-directory-results', true );
	$widget->add_render_attribute( '_wrapper', 'tabindex', '-1' );
}
add_action( 'elementor/frontend/widget/before_render', 'mcp_wpsl_prepare_directory_widget' );

/** Keep the native error/status list, while Elementor owns the visible store rows. */
function mcp_wpsl_elementor_listing_template( string $template ): string {
	if ( MCP_WPSL_ELEMENTOR_TEMPLATE !== mcp_wpsl_active_template_id() ) {
		return $template;
	}
	return '<% /* Store rows are rendered by the native Elementor Loop Grid. */ %>';
}
add_filter( 'wpsl_listing_template', 'mcp_wpsl_elementor_listing_template', 40 );

/** Load the maintained input/result integration before native locator initialisation. */
function mcp_wpsl_add_directory_script( array $settings ): array {
	if ( ! in_array( $settings['ux']['templateId'] ?? '', array( MCP_WPSL_COLUMNS_TEMPLATE, MCP_WPSL_ELEMENTOR_TEMPLATE ), true ) || ! wp_script_is( 'wpsl', 'registered' ) ) {
		return $settings;
	}
	static $added = false;
	if ( ! $added ) {
		$script = file_get_contents( __DIR__ . '/../assets/elementor-directory.js' );
		if ( false !== $script ) {
			$added = wp_add_inline_script( 'wpsl', $script, 'before' );
		}
	}
	return $settings;
}
add_filter( 'wpsl_js_settings', 'mcp_wpsl_add_directory_script', 35 );
