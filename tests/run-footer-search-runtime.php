<?php
/** Native WordPress hook contract; run with wp eval-file. No persistent writes. */
if (!defined("ABSPATH") || !function_exists("mcp_wpsl_enable_footer_search")) { throw new RuntimeException("Load WordPress with the plugin active"); }
$base = array('api' => array('provider' => 'gmaps'), 'map' => array('startLatLng' => '63.7463571,11.3004934'), 'search' => array('widgetEnabled' => 0, 'autoLoad' => 1, 'restrictions' => array('radius' => '50', 'maxResults' => '25')), 'ux' => array('templateId' => 'dynamic_columns'), 'language' => 'de');
$cases = array(
 'ordinary GET' => array('GET', array(), false),
 'GET cannot start footer search' => array('GET', array('wpsl-widget-search' => '0256 Oslo'), false),
 'unrelated POST' => array('POST', array('other' => '0256 Oslo'), false),
 'Norwegian footer postcode and city' => array('POST', array('wpsl-widget-search' => '0256 Oslo'), true),
 'postcode preserves leading zero' => array('POST', array('wpsl-widget-search' => '0256'), true),
 'English footer postcode and city' => array('POST', array('wpsl-widget-search' => '0256 Oslo'), true),
 'Unicode location' => array('POST', array('wpsl-widget-search' => 'Lørenskog'), true),
 'surrounding whitespace' => array('POST', array('wpsl-widget-search' => ' 0256 Oslo '), true),
 'empty' => array('POST', array('wpsl-widget-search' => ''), false),
 'spaces only' => array('POST', array('wpsl-widget-search' => '   '), false),
 'Unicode spaces only' => array('POST', array('wpsl-widget-search' => "\u{00A0}\u{2003}"), false),
 'punctuation only' => array('POST', array('wpsl-widget-search' => '---'), false),
 'array' => array('POST', array('wpsl-widget-search' => array('0256 Oslo')), false),
 'nested array' => array('POST', array('wpsl-widget-search' => array(array('0256 Oslo'))), false),
 'integer' => array('POST', array('wpsl-widget-search' => 256), false),
 'boolean' => array('POST', array('wpsl-widget-search' => true), false),
 'object' => array('POST', array('wpsl-widget-search' => new stdClass()), false),
 'null' => array('POST', array('wpsl-widget-search' => null), false),
 'HTML payload' => array('POST', array('wpsl-widget-search' => '<img src=x onerror=alert(1)>Oslo'), false),
 'encoded controls' => array('POST', array('wpsl-widget-search' => '%0AOslo'), false),
 'newline' => array('POST', array('wpsl-widget-search' => "Oslo\n0256"), false),
 'malformed UTF-8' => array('POST', array('wpsl-widget-search' => "\xC3\x28Oslo"), false),
 '200 byte location' => array('POST', array('wpsl-widget-search' => str_repeat('a', 200)), true),
 'oversized location' => array('POST', array('wpsl-widget-search' => str_repeat('a', 201)), false),
 'quoted location remains plain data' => array('POST', array('wpsl-widget-search' => "O\\'Connell Street"), true),
);
$failures = 0; $passed = 0;
$check = static function($ok, $name) use (&$failures, &$passed) { echo ($ok ? 'PASS: ' : 'FAIL: ') . $name . "\n"; $ok ? ++$passed : ++$failures; };
foreach ($cases as $name => [$method, $post, $enabled]) {
 $_SERVER['REQUEST_METHOD'] = $method; $_POST = $post; $_GET = array('wpsl-widget-search' => 'cookie/GET must not win'); $_COOKIE = array('wpsl-widget-search' => 'cookie must not win');
 $settings = $base; if (str_starts_with($name, 'English')) { $settings['language'] = 'en'; }
 $actual = apply_filters('wpsl_js_settings', $settings); $expected = $settings; if ($enabled) { $expected['search']['widgetEnabled'] = 1; }
 $check($actual === $expected, $name . ': only widget flag may change');
}
$_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = array('wpsl-widget-search' => '0256 Oslo');
$other = $base; $other['ux']['templateId'] = 'default'; $check(apply_filters('wpsl_js_settings', $other) === $other, 'other native templates unchanged');
$existing = $base; $existing['search']['widgetEnabled'] = 1; $existing['search']['widgetLatLng'] = array('lat' => 59.918, 'lng' => 10.72); $check(apply_filters('wpsl_js_settings', $existing) === $existing, 'supported widget integration remains authoritative');
$missing = $base; unset($missing['search']); $check(apply_filters('wpsl_js_settings', $missing) === $missing, 'missing search settings unchanged');
$broken = $base; $broken['search'] = 'not an array'; $check(apply_filters('wpsl_js_settings', $broken) === $broken, 'invalid native search settings unchanged');
$_POST = array(); $check(apply_filters('wpsl_js_settings', $base) === $base, 'cookie value cannot start search');
$_POST = array('wpsl-widget-search' => '0256 Oslo');
$check(function_exists('esc_attr') && '&quot; data-injected=&quot;yes' === esc_attr('" data-injected="yes'), 'native WordPress attribute escaping remains active');
$check(function_exists('mcp_wpsl_get_footer_search_post_value') && '0256 Oslo' === mcp_wpsl_get_footer_search_post_value(), 'validated input preserves zero-prefixed postcode');
echo "Checks: $passed passed, $failures failed\n";
if ($failures) { throw new RuntimeException("Footer search contract failed"); }
