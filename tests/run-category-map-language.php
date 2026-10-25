<?php
/** Isolated shortcode contract; real WPML integration is a separate runtime gate. */
define( 'ABSPATH', __DIR__ . '/' );
$case = $argv[1] ?? 'wpml';
if ( 'no-wpml' !== $case ) {
	define( 'ICL_SITEPRESS_VERSION', 'fixture' );
}
$GLOBALS['hooks'] = array();
$GLOBALS['translated'] = 'untranslated' !== $case;
$GLOBALS['language'] = 'nb';
$GLOBALS['fallback'] = false;
function add_action( $tag, $callback, $priority = 10, $accepted = 1 ) { $GLOBALS['hooks'][$tag][$priority][] = $callback; }
function add_filter( ...$args ) { add_action( ...$args ); }
function remove_action( $tag, $callback, $priority = 10 ) {
	foreach ( $GLOBALS['hooks'][$tag][$priority] ?? array() as $key => $value ) {
		if ( $callback === $value ) { unset( $GLOBALS['hooks'][$tag][$priority][$key] ); }
	}
}
function do_action( $tag, ...$args ) {
	$priorities = $GLOBALS['hooks'][$tag] ?? array(); ksort( $priorities );
	foreach ( $priorities as $callbacks ) { foreach ( $callbacks as $callback ) { $callback( ...$args ); } }
}
function apply_filters( $tag, $value, ...$args ) { return 'wpml_is_translated_post_type' === $tag ? $GLOBALS['translated'] : $value; }
function add_shortcode( $tag, $callback ) { $GLOBALS['shortcode_tags'][$tag] = $callback; }
class WP_Query {
	public $query_vars;
	public function __construct( $args ) { $this->query_vars = $args + array( 'suppress_filters' => true ); }
	public function get( $key ) { return $this->query_vars[$key] ?? ''; }
	public function set( $key, $value ) { $this->query_vars[$key] = $value; }
}
$stores = array(
	array( 'id' => 11, 'lang' => 'nb', 'category' => 'branches', 'status' => 'publish' ),
	array( 'id' => 12, 'lang' => 'en', 'category' => 'branches', 'status' => 'publish' ),
	array( 'id' => 13, 'lang' => 'nb', 'category' => 'branches', 'status' => 'draft' ),
	array( 'id' => 14, 'lang' => 'nb', 'category' => 'other', 'status' => 'publish' ),
	array( 'id' => 15, 'lang' => 'nb', 'category' => 'branches', 'status' => 'publish', 'untranslated' => true ),
);
$category_query = static function ( $category = 'branches', $overrides = array() ) use ( &$stores ) {
	$args = array_replace( array( 'post_type' => 'wpsl_stores', 'post_status' => 'publish', 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'wpsl_store_category', 'field' => 'slug', 'terms' => array( $category ) ) ) ), $overrides );
	$q = new WP_Query( $args ); do_action( 'pre_get_posts', $q );
	$GLOBALS['last_query'] = $q->query_vars;
	return array_values( array_column( array_filter( $stores, static function ( $store ) use ( $category, $q ) {
		return $store['category'] === $category && 'publish' === $store['status'] && ( $q->get( 'suppress_filters' ) || $GLOBALS['language'] === $store['lang'] || ( $GLOBALS['fallback'] && ! empty( $store['untranslated'] ) ) );
	} ), 'id' ) );
};
$original = static function ( $attributes, $content, $tag ) use ( $category_query ) {
	if ( ! empty( $attributes['throw'] ) ) { $category_query(); throw new RuntimeException( 'Native callback failed' ); }
	if ( ! empty( $attributes['nested'] ) ) { $inner = $GLOBALS['shortcode_tags']['wpsl_map']( array( 'category' => 'branches' ), 'inner', 'wpsl_map' ); }
	$ids = ! empty( $attributes['category'] ) ? $category_query( $attributes['category'] ) : explode( ',', $attributes['id'] ?? '' );
	return array( 'ids' => $ids, 'attributes' => $attributes, 'content' => $content, 'tag' => $tag, 'nested' => $inner ?? null );
};
add_shortcode( 'wpsl_map', $original );
require dirname( __DIR__ ) . '/mcp-abilities-store-locator.php';
do_action( 'wp_loaded' );
$callback = $GLOBALS['shortcode_tags']['wpsl_map'];
$checks = array();
$active = 'wpml' === $case;
$checks['Only translated WPML store types are adapted'] = $active ? $callback !== $original : $callback === $original;
$checks['Repeated registration is idempotent'] = true;
mcp_wpsl_register_category_map_language();
$checks['Repeated registration is idempotent'] = $callback === $GLOBALS['shortcode_tags']['wpsl_map'];
$atts = array( 'category' => 'branches', 'width' => '100%', 'zoom' => '8' );
$result = $callback( $atts, 'Native content', 'wpsl_map' );
$checks['Attributes, content and shortcode tag preserved'] = $result['attributes'] === $atts && 'Native content' === $result['content'] && 'wpsl_map' === $result['tag'];
$checks['Norwegian category excludes other language, draft and other category'] = $result['ids'] === ( $active ? array( 11, 15 ) : array( 11, 12, 15 ) );
$GLOBALS['language'] = 'en';
$result = $callback( $atts, null, 'wpsl_map' );
$checks['English category follows current query language'] = $result['ids'] === ( $active ? array( 12 ) : array( 11, 12, 15 ) );
$GLOBALS['fallback'] = true;
$checks['Configured untranslated fallback stays with query owner'] = $callback( $atts, null, 'wpsl_map' )['ids'] === ( $active ? array( 12, 15 ) : array( 11, 12, 15 ) );
$GLOBALS['fallback'] = false;
$stores[] = array( 'id' => 21, 'lang' => 'nb', 'category' => 'branches', 'status' => 'publish' );
$stores[] = array( 'id' => 22, 'lang' => 'en', 'category' => 'branches', 'status' => 'publish' );
$checks['New stores automatically enter current category map'] = $callback( $atts, null, 'wpsl_map' )['ids'] === ( $active ? array( 12, 22 ) : array( 11, 12, 15, 21, 22 ) );
$category_query();
$checks['Category query outside shortcode remains unchanged'] = true === $GLOBALS['last_query']['suppress_filters'];
$checks['Explicit ID map remains unchanged'] = $callback( array( 'id' => '11,12' ), null, 'wpsl_map' )['ids'] === array( '11', '12' );
$result = $callback( $atts + array( 'nested' => true ), null, 'wpsl_map' );
$checks['Nested maps retain language scope'] = $result['ids'] === $result['nested']['ids'];
$category_query();
$checks['Nested map hooks are removed'] = true === $GLOBALS['last_query']['suppress_filters'];
try { $callback( array( 'throw' => true ), null, 'wpsl_map' ); } catch ( RuntimeException $e ) { $checks['Native exception preserved'] = 'Native callback failed' === $e->getMessage(); }
$category_query();
$checks['Exception removes temporary query hook'] = true === $GLOBALS['last_query']['suppress_filters'];
// Exercise unrelated queries inside the original callback scope using a separate observer.
$unrelated_unchanged = true;
$observer = static function () use ( &$unrelated_unchanged ) {
	static $running = false;
	if ( $running ) { return; } $running = true;
	foreach ( array( array( 'post_type' => 'post' ), array( 'post_status' => 'draft' ), array( 'fields' => '' ), array( 'tax_query' => array( array( 'taxonomy' => 'category' ) ) ) ) as $override ) {
		$q = new WP_Query( array_replace( array( 'post_type' => 'wpsl_stores', 'post_status' => 'publish', 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'wpsl_store_category' ) ) ), $override ) );
		do_action( 'pre_get_posts', $q ); $unrelated_unchanged = $unrelated_unchanged && true === $q->get( 'suppress_filters' );
	}
	$running = false;
};
add_action( 'pre_get_posts', $observer, 5 );
$callback( $atts, null, 'wpsl_map' );
remove_action( 'pre_get_posts', $observer, 5 );
$checks['Unrelated post, status, result type and taxonomy queries unchanged'] = $unrelated_unchanged;
$failed = array_keys( array_filter( $checks, static fn( $ok ) => ! $ok ) );
echo json_encode( array( 'case' => $case, 'passed' => ! $failed, 'checks' => $checks, 'failed' => $failed ), JSON_PRETTY_PRINT ) . PHP_EOL;
exit( $failed ? 1 : 0 );
