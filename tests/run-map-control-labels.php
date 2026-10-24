<?php
/** Native settings stay optional;no domain,page,form or store identifiers are needed. */
define('ABSPATH',__DIR__.'/');
function add_action(...$args){}
function add_filter(...$args){}
function sanitize_key($value){return strtolower(preg_replace('/[^a-zA-Z0-9_-]/','',$value));}
function sanitize_text_field($value){return strip_tags($value);}
function get_option($key,$default=false){return $GLOBALS['fixture_options'][$key]??$default;}
function apply_filters($hook,$value,...$args){return $hook==='wpml_current_language'?$GLOBALS['fixture_language']:$value;}
function wp_script_is($name,$state){return $GLOBALS['fixture_script_registered'];}
function wp_add_inline_script($name,$script,$position){$GLOBALS['fixture_inline'][]=[$name,$script,$position];return true;}
function wp_json_encode($value){return json_encode($value,JSON_UNESCAPED_UNICODE);}
$GLOBALS['fixture_options']=[];$GLOBALS['fixture_inline']=[];$GLOBALS['fixture_script_registered']=true;$case=$argv[1]??'empty-no';$GLOBALS['fixture_language']=str_ends_with($case,'en')?'en':'no';
if(str_starts_with($case,'configured')){if(empty($argv[2])){throw new RuntimeException('Native label fixture file required');}$GLOBALS['fixture_options']['mcp_wpsl_label_translations']=json_decode(file_get_contents($argv[2]),true,512,JSON_THROW_ON_ERROR);}
require dirname(__DIR__).'/mcp-abilities-store-locator.php';
$settings=['ux'=>['templateId'=>'native-default'],'unrelated'=>'Keep'];$result=mcp_wpsl_add_map_control_language($settings);$checks=['Native settings returned unchanged'=>$result===$settings];
if(str_starts_with($case,'empty')){$checks['No customer configuration adds no behavior']=$GLOBALS['fixture_inline']===[];}
else{$checks['One native script adapter added']=count($GLOBALS['fixture_inline'])===1;$script=$GLOBALS['fixture_inline'][0][1]??'';$expected=$GLOBALS['fixture_options']['mcp_wpsl_label_translations'][$GLOBALS['fixture_language']];foreach($expected as $key=>$text){$checks[$key.' from own saved language']=str_contains($script,json_encode($key).':'.json_encode($text,JSON_UNESCAPED_UNICODE));}foreach($GLOBALS['fixture_options']['mcp_wpsl_label_translations'] as $code=>$labels){if($code!==$GLOBALS['fixture_language']){$checks['Other language not injected']=!str_contains($script,json_encode($labels['popup_close_label'],JSON_UNESCAPED_UNICODE));}}}
$failed=array_keys(array_filter($checks,static fn($ok)=>!$ok));echo json_encode(['passed'=>!$failed,'case'=>$case,'checks'=>$checks,'failed'=>$failed],JSON_UNESCAPED_UNICODE).PHP_EOL;exit($failed?1:0);
