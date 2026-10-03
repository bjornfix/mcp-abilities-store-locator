<?php
/** Contract fixtures for the two supported multilingual Interfaces. */
define('ABSPATH',__DIR__.'/');
function add_action(...$args){}
function add_filter(...$args){}
function post_type_exists($type){return $GLOBALS['fixture_post_type_available']??true;}
function add_rewrite_rule($regex,$query,$position){$GLOBALS['fixture_rewrites'][$regex]=$query;}
function sanitize_key($v){return strtolower(preg_replace('/[^a-zA-Z0-9_-]/','',$v));}
function sanitize_title($v){return sanitize_key($v);}
function get_option($name,$default=false){return $GLOBALS['fixture_options'][$name]??$default;}
function trailingslashit($s){return rtrim($s,'/').'/';}
function wp_parse_url($u,$c=-1){return parse_url($u,$c);}
function wp_unslash($v){return $v;}
function sanitize_text_field($v){return $v;}
class WP_Post {public $ID=11;public $post_type='wpsl_stores';public $post_status='publish';public $post_name='branch';}
function apply_filters($name,$value,...$args){if('wpml_element_language_details'===$name){return 'wpsl_stores'===$args[0]['element_type']?(object)array('language_code'=>'de'):null;}return $value;}
function has_filter($name){return 'wpml_element_language_details'===$name;}
function pll_get_post($id,$language){return 22;}
function pll_get_post_language($id,$format){return 'fr';}
require dirname(__DIR__).'/mcp-abilities-store-locator.php';
$checks=array('Polylang mapping without WPML'=>22===mcp_wpsl_get_store_post_in_language(11,'fr'),'WPML receives raw post type'=>'de'===mcp_wpsl_get_post_language_code(11,'wpsl_stores'));
$GLOBALS['fixture_options']=array('home'=>'https://example.com/subsite','permalink_structure'=>'/%postname%/','wpsl_settings'=>array('permalink_slug'=>'stores'),MCP_WPSL_BASE_TRANSLATIONS=>array('de'=>'filialen'));
$GLOBALS['sitepress']=new class {public function get_language_for_element($id,$type){return 'de';}};
$post=new WP_Post();
$checks['Directory base preserves native suffix']='https://example.com/subsite/de/filialen/branch/?view=1'===mcp_wpsl_filter_translated_store_permalink('https://example.com/subsite/de/stores/branch/?view=1',$post);
foreach(array('https://de.example.com/stores/branch/','https://example.com/subsite/stores/branch/?lang=de') as $url){$checks['Native language URL unchanged '.$url]=$url===mcp_wpsl_filter_translated_store_permalink($url,$post);}
$post->post_status='draft';$url='https://example.com/subsite/de/stores/branch/';$checks['Draft link unchanged']=$url===mcp_wpsl_filter_translated_store_permalink($url,$post);
$_SERVER['REQUEST_URI']='/subsite/de/filialen/branch/?view=1';$checks['Subdirectory request path']='/de/filialen/branch/'===mcp_wpsl_get_request_path();
$GLOBALS['fixture_options'][MCP_WPSL_BASE_TRANSLATIONS]=array('de'=>'filialen','en'=>'stores','fr'=>'stores');
$GLOBALS['fixture_rewrites']=array();
mcp_wpsl_register_translated_store_rewrites();
$match=static function($path) {
 foreach($GLOBALS['fixture_rewrites'] as $regex=>$query) {
  if(preg_match('#'.$regex.'#',$path,$matches)) {
   parse_str(str_replace('$matches[1]',$matches[1],substr($query,strlen('index.php?'))),$vars);
   return $vars;
  }
 }
 return null;
};
$checks['Explicit language-directory route preserved']=array('post_type'=>'wpsl_stores','name'=>'branch','lang'=>'de')===$match('de/filialen/branch/');
$checks['WPML normalized directory route resolves store']=array('post_type'=>'wpsl_stores','name'=>'branch')===$match('stores/branch/');
$checks['Shared translated base keeps native request language']=is_array($match('stores/branch/')) && !isset($match('stores/branch/')['lang']);
$checks['Other translated base resolves after prefix normalization']=array('post_type'=>'wpsl_stores','name'=>'branch')===$match('filialen/branch');
$checks['Unrelated article route unchanged']=null===$match('articles/branch/');
$checks['Additional path segments unchanged']=null===$match('stores/branch/details/');
$GLOBALS['fixture_post_type_available']=false;
$GLOBALS['fixture_rewrites']=array();
mcp_wpsl_register_translated_store_rewrites();
$checks['Unavailable store type does not register routes']=array()===$GLOBALS['fixture_rewrites'];
foreach($checks as $name=>$ok){echo($ok?'PASS: ':'FAIL: ').$name."\n";}
exit(in_array(false,$checks,true)?1:0);
