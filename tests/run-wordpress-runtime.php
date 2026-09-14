<?php
/** Run with wp eval-file on a validation site with native WP Store Locator. */
if (!defined('ABSPATH') || !function_exists('wp_get_ability') || !post_type_exists('wpsl_stores')) { throw new RuntimeException('WordPress and WP Store Locator required.'); }
$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
if (!$admins) { throw new RuntimeException('Administrator required.'); }
wp_set_current_user((int)$admins[0]);
$ids=array();$failures=array();$settings=get_option('wpsl_settings');
$check=static function($ok,$name) use (&$failures) { echo ($ok?'PASS: ':'FAIL: ').$name."\n";if(!$ok){$failures[]=$name;} };
$run=static function($name,$input=array()) { $a=wp_get_ability($name);if(!$a){throw new RuntimeException('Missing '.$name);}return $a->execute($input); };
$failed=static function($r) { return is_wp_error($r) || (isset($r['success']) && !$r['success']); };
try {
 $id=wp_insert_post(array('post_type'=>'wpsl_stores','post_status'=>'draft','post_title'=>'MCP locator validation fixture'),true);
 if(is_wp_error($id)){throw new RuntimeException($id->get_error_message());}$ids[]=$id;
 $deny=static function($caps,$cap,$user_id,$args) use ($id) {return in_array($cap,array('edit_post','read_post'),true) && (int)($args[0]??0)===$id?array('do_not_allow'):$caps;};
 add_filter('map_meta_cap',$deny,999,4);
 try {
  $r=$run('wpsl/update-store',array('id'=>$id,'title'=>'MCP locator validation fixture changed'));$check($failed($r) && 'MCP locator validation fixture'===get_post($id)->post_title,'Object edit permission');
  $r=$run('wpsl/get-store',array('id'=>$id));$check($failed($r),'Object read permission');
  $r=$run('wpsl/list-stores',array('status'=>'draft','search'=>'MCP locator validation fixture'));$check(!$failed($r) && !in_array($id,array_column($r['stores']??array(),'id'),true),'List excludes unreadable store');
 } finally {remove_filter('map_meta_cap',$deny,999);}
 $body='<p>Collection code C:\\branch\\shelf</p>';
 $r=$run('wpsl/update-store',array('id'=>$id,'content'=>$body,'meta'=>array('address2'=>'Shelf C:\\branch')));
 $check(!$failed($r) && get_post($id)->post_content===$body && get_post_meta($id,'wpsl_address2',true)==='Shelf C:\\branch','Update preserves literal backslashes');
 $r=$run('wpsl/create-store',array('title'=>'MCP new fixture','content'=>$body));
 if(!is_wp_error($r) && isset($r['store']['id'])){$ids[]=$r['store']['id'];}
 $check(!$failed($r) && get_post($r['store']['id'])->post_content===$body,'Create preserves literal backslashes');
 $deny_create=static function($caps,$cap) {return 'edit_stores'===$cap?array('do_not_allow'):$caps;};
 add_filter('map_meta_cap',$deny_create,999,2);
 try {$r=$run('wpsl/create-store',array('title'=>'MCP denied fixture'));if(!is_wp_error($r)&&isset($r['store']['id'])){$ids[]=$r['store']['id'];}$check($failed($r),'Native store create capability');}finally{remove_filter('map_meta_cap',$deny_create,999);}
 $r=$run('wpsl/update-settings',array('settings'=>array('search_radius'=>'10,25,[50],100','max_results'=>'[25],50,100'),'dry_run'=>true));
 $check(!$failed($r) && ($r['updated']['search_radius']??null)==='10,25,[50],100' && ($r['updated']['max_results']??null)==='[25],50,100','Native dropdown setting values');
 $r=$run('wpsl/update-store',array('id'=>$id,'content'=>'Must not save','categories'=>array('mcp-no-such-category-fixture')));$check($failed($r) && get_post($id)->post_content===$body,'Unknown category rejected before writing content');
 $r=$run('wpsl/update-store',array('id'=>$id,'meta'=>array('lat'=>'91','lng'=>'181')));$check($failed($r),'Coordinates outside geographic range rejected');
 $block_meta=static function($check,$object_id,$key) use ($id){return $object_id===$id && 'wpsl_phone'===$key?false:$check;};add_filter('update_post_metadata',$block_meta,999,3);
 try{$r=$run('wpsl/update-store',array('id'=>$id,'meta'=>array('phone'=>'fixture-rejected')));$check($failed($r),'Rejected metadata write is reported');}finally{remove_filter('update_post_metadata',$block_meta,999);}
 $search_value=static function(){return '" data-mcp-injected="yes';};add_filter('wpsl_search_input',$search_value);
 try{$markup=$GLOBALS['wpsl']->frontend->show_store_locator(array('template'=>'dynamic_columns'));$html=new WP_HTML_Tag_Processor($markup);$safe=false;while($html->next_tag('INPUT')){if('wpsl-search-input'===$html->get_attribute('id')){$safe=null===$html->get_attribute('data-mcp-injected') && '" data-mcp-injected="yes'===$html->get_attribute('value');break;}}$check($safe && str_contains($markup,'mcp-wpsl-columns'),'Native columns template escapes its search value');}finally{remove_filter('wpsl_search_input',$search_value);}
 $missing_template=static function($templates){$templates[]=array('id'=>'mcp_missing_fixture','name'=>'Missing fixture','path'=>'/tmp/mcp-nonexistent-template.php');return $templates;};add_filter('wpsl_templates',$missing_template);
 try{$r=$run('wpsl/set-template',array('template_id'=>'mcp_missing_fixture','dry_run'=>true));$check($failed($r),'Missing registered template file rejected');}finally{remove_filter('wpsl_templates',$missing_template);}
 $test_settings=get_option('wpsl_settings');$test_settings['api_server_key']='fixture-private-value';update_option('wpsl_settings',$test_settings);
 $r=$run('wpsl/get-status');$check(!$failed($r) && !isset($r['settings']['api_server_key']) && !empty($r['settings']['api_server_key_configured']),'Settings report credential presence without values');
 $block_save=static function($new,$old){return $old;};add_filter('pre_update_option_wpsl_settings',$block_save,999,2);
 try{$r=$run('wpsl/update-settings',array('settings'=>array('start_name'=>'MCP rejected settings fixture')));$check($failed($r),'Rejected settings write is reported');}finally{remove_filter('pre_update_option_wpsl_settings',$block_save,999);}
 $r=$run('wpsl/update-label-translations',array('translations'=>array('en'=>array('hours_label'=>'Unsupported fixture')),'dry_run'=>true));$check($failed($r),'Unsupported template label rejected');
 $admin=$GLOBALS['wpsl_admin'];$GLOBALS['wpsl_admin']=null;
 try {set_transient('wpsl_autoload_99_mcpfixture','old',60);$r=$run('wpsl/clear-transients');$check(!$failed($r) && false===get_transient('wpsl_autoload_99_mcpfixture'),'Native cache method loads when no admin instance exists');}finally{$GLOBALS['wpsl_admin']=$admin;}
 set_transient('wpsl_autoload_99_mcpfixture','old',60);$r=$run('wpsl/clear-transients');$check(!$failed($r) && false===get_transient('wpsl_autoload_99_mcpfixture'),'Native autoload cache invalidation');
} finally {
 foreach(array_unique($ids) as $id){wp_delete_post($id,true);}
 update_option('wpsl_settings',$settings);delete_transient('wpsl_autoload_99_mcpfixture');
}
if($failures){throw new RuntimeException(count($failures).' public-interface checks failed.');}
echo "PASS: native WordPress and WP Store Locator runtime checks\n";
