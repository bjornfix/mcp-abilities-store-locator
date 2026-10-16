<?php
/** Run with wp eval-file. Exercise registered hooks without persistent writes. */
if (!defined('ABSPATH') || !function_exists('mcp_wpsl_normalize_location')) { throw new RuntimeException('Active addon required'); }
$passed=0;$failed=0;
$check=static function($ok,$name)use(&$passed,&$failed){echo($ok?'PASS: ':'FAIL: ').$name."\n";$ok?++$passed:++$failed;};
foreach(array('0256Oslo'=>'0256 Oslo','0256'=>'0256','0256 Oslo'=>'0256 Oslo','12345Berlin'=>'12345 Berlin','12345Lørenskog'=>'12345 Lørenskog','12345678901Town'=>'12345678901Town','W1A 1AA'=>'W1A 1AA','12 Main Street'=>'12 Main Street','59.9,10.7'=>'59.9,10.7',"\xC3\x28"=>"\xC3\x28")as$input=>$expected){$check($expected===mcp_wpsl_normalize_location((string)$input),'Location input '.json_encode($input));}
$option=static function(){return array('template_id'=>'elementor_directory');};
$labels=static function(){return array('no'=>array('directions_label'=>'Veibeskrivelse','no_results_label'=>'Ingen forhandlere funnet.','adjust_search_label'=>'Endre søket og prøv igjen.'));};
$lang=static function(){return 'no';};
add_filter('pre_option_wpsl_settings',$option);add_filter('pre_option_mcp_wpsl_label_translations',$labels);add_filter('wpml_current_language',$lang);
try{
 $base=array('directions'=>'Directions','noResults'=>'No results.','adjustSearch'=>'Adjust search.','unrelated'=>'Keep');
 $result=apply_filters('wpsl_labels',$base);
 $check('Veibeskrivelse'===($result['directions']??''),'Configured directions label');
 $check('Ingen forhandlere funnet.<br><br>Endre søket og prøv igjen.'===($result['noResults']??''),'Configured no-results and help labels');
 $check('Keep'===($result['unrelated']??''),'Unrelated native label preserved');
 $query=new WP_Query();do_action('elementor/query/mcp_wpsl_directory',$query);
 $check('wpsl_stores'===$query->get('post_type')&&'publish'===$query->get('post_status')&&-1===$query->get('posts_per_page')&&false===$query->get('suppress_filters'),'Native query includes all published current-language stores');
 $check(str_contains(apply_filters('wpsl_listing_template','Original listing'),'Elementor Loop Grid'),'Opt-in suppresses duplicate store renderer');
 $_SERVER['REQUEST_METHOD']='POST';$_POST=array('wpsl-widget-search'=>'0256Oslo');
 $check('0256 Oslo'===apply_filters('wpsl_search_input',''),'Native input normalises valid footer postcode/city');
 $check('" data-mcp-injected="yes'===apply_filters('wpsl_search_input','" data-mcp-injected="yes'),'Template escaping retains ownership of arbitrary literal input');
}finally{remove_filter('pre_option_wpsl_settings',$option);remove_filter('pre_option_mcp_wpsl_label_translations',$labels);remove_filter('wpml_current_language',$lang);$_POST=array();}
$english_labels=static function(){return array('en'=>array('preloader_label'=>'Searching...','start_label'=>'Starting point','back_label'=>'Back','zoom_here_label'=>'Zoom here'));};
$english_language=static function(){return 'en';};
add_filter('pre_option_wpsl_settings',$option);add_filter('pre_option_mcp_wpsl_label_translations',$english_labels);add_filter('wpml_current_language',$english_language);
try{
 $native=array('preloader'=>'Original loading','startPoint'=>'Original start','back'=>'Original back','zoomHere'=>'Original zoom','unrelated'=>'Keep');
 $translated=apply_filters('wpsl_labels',$native);
 $check(array('preloader'=>'Searching...','startPoint'=>'Starting point','back'=>'Back','zoomHere'=>'Zoom here','unrelated'=>'Keep')===$translated,'Configured English status/navigation values reach native WPSL labels');
 remove_filter('wpml_current_language',$english_language);add_filter('wpml_current_language',$lang);
 $check($native===apply_filters('wpsl_labels',$native),'Unconfigured Norwegian labels are preserved exactly');
}finally{remove_filter('pre_option_wpsl_settings',$option);remove_filter('pre_option_mcp_wpsl_label_translations',$english_labels);remove_filter('wpml_current_language',$english_language);remove_filter('wpml_current_language',$lang);}
echo"Checks: $passed passed, $failed failed\n";
if($failed){throw new RuntimeException('Directory runtime contract failed');}
