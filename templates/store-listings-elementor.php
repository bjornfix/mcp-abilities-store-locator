<?php
/**
 * WP Store Locator 3 map/search with native Elementor store rows.
 *
 * @package MCP_Abilities_WPSL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Native WPSL template services are passed in scope.
$output = $assets_manager->get_custom_css();
$output .= '<div id="wpsl-wrap" class="wpsl-store-below mcp-wpsl-elementor">';
$output .= $frontend->maybe_show_gdpr_checkpoint();
$output .= '<a href="#mcp-wpsl-directory-results" class="wpsl-skip-to-results">' . esc_html( mcp_wpsl_translate_setting_label( 'skip_to_results_label', $i18n->get_translation( 'skip_to_results_label', __( 'Skip map, jump to search results', 'mcp-abilities-store-locator' ) ) ) ) . '</a>';
$output .= '<div id="wpsl-map" class="wpsl-canvas-' . esc_attr( $wpsl_settings['api']['active_map_service'] ) . '"></div>';
$output .= '<div class="wpsl-search wpsl-clearfix ' . esc_attr( $assets_manager->get_css_classes() ) . '"><div id="wpsl-search-wrap"><form autocomplete="off">';
$output .= '<div class="wpsl-input"><div><label for="wpsl-search-input">' . esc_html( mcp_wpsl_translate_setting_label( 'search_label', $i18n->get_translation( 'search_label', __( 'Your location', 'mcp-abilities-store-locator' ) ) ) ) . '</label></div>';
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Native WPSL template filter.
$output .= '<input id="wpsl-search-input" type="text" value="' . esc_attr( apply_filters( 'wpsl_search_input', '' ) ) . '" name="wpsl-search-input" placeholder="' . esc_attr( $wpsl_settings['search']['input_placeholder'] ) . '" aria-required="true"></div>';

if ( $template_filters->is_radius_enabled() || $template_filters->is_results_enabled() ) {
	$output .= '<div class="wpsl-select-wrap">';
	if ( $template_filters->is_radius_enabled() ) {
		$output .= '<div id="wpsl-radius"><label for="wpsl-radius-dropdown">' . esc_html( $i18n->get_translation( 'radius_label', __( 'Search radius', 'mcp-abilities-store-locator' ) ) ) . '</label><select id="wpsl-radius-dropdown" class="wpsl-dropdown" name="wpsl-radius">' . $search_filters->get_dropdown_list( 'search_radius' ) . '</select></div>';
	}
	if ( $template_filters->is_results_enabled() ) {
		$output .= '<div id="wpsl-results"><label for="wpsl-results-dropdown">' . esc_html( $i18n->get_translation( 'results_label', __( 'Results', 'mcp-abilities-store-locator' ) ) ) . '</label><select id="wpsl-results-dropdown" class="wpsl-dropdown" name="wpsl-results">' . $search_filters->get_dropdown_list( 'max_results' ) . '</select></div>';
	}
	$output .= '</div>';
}
if ( $template_filters->is_category_enabled() ) {
	$output .= $template_filters->category_list();
}
$output .= '<div class="wpsl-search-btn-wrap"><input id="wpsl-search-btn" type="submit" value="' . esc_attr( mcp_wpsl_translate_setting_label( 'search_btn_label', $i18n->get_translation( 'search_btn_label', __( 'Search', 'mcp-abilities-store-locator' ) ) ) ) . '"></div></form></div></div>';
$output .= '<div id="wpsl-result-list" hidden role="status" aria-live="polite"><div id="wpsl-stores"><ul></ul></div><div id="wpsl-direction-details"><ul></ul></div></div>';
if ( $wpsl_settings['map']['show_credits'] ) {
	/* translators: %1$s: opening link tag, %2$s: closing link tag */
	$output .= '<div class="wpsl-provided-by">' . wp_kses_post( sprintf( __( 'Search provided by %1$sStore Locator%2$s', 'mcp-abilities-store-locator' ), '<a target="_blank" href="https://wpstorelocator.co">', '</a>' ) ) . '</div>';
}
$output .= '</div>';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dynamic values are escaped when composed; native services own dropdown, category and map markup.
echo $output;
