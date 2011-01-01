<?php

/* 
  This file is responsible for the download of the files
*/

require_once('../../../wp-config.php');

global $user_ID, $wpdb, $table_prefix;

require_once(ABSPATH . 'wp-admin/upgrade-functions.php'); 

include_once("uploader_functions.php");

$id = (int) $_GET['file'];
   
$result = @$wpdb->get_row("SELECT fileName, type, title, downloads
                            FROM ".$table_prefix."document
                            WHERE id = ".$id."
                           ;");
                           
if(!isset($result->title)){

  $info = get_bloginfo("url");

  header("Location: ".$info."/wp-content/plugins/wp-publications-archive/notFound.php");

  return;

} 
  
$downloads = $result->downloads + 1;
$wpdb->query("UPDATE ".$table_prefix."document 
                    SET downloads = ".$downloads."
                    WHERE id = ".$id.";");

$fTitle = $result->title;                         

$fPath = $result->fileName;

$fType = wpup_upGetType($result->type);

$fName = basename($fPath); //Get filename //thanks to brian.m.turnbull

$origname = preg_replace('/_#_#\d*/','',$fName); //Remove the _#_#$id //thanks to brian.m.turnbull

$permission = "all";

$plugin_name = "wp_uploader";

if (class_exists("userGroups"))
  $user = new userGroups;
 
if (class_exists("userGroups") && $user->ugHasAccess($user_ID, $id, $permission, $plugin_name)){

  header('Content-type: "'.$fType->extName.'"');
  
  header('Content-Disposition: attachment; filename="'.$origname.'"'); //thanks to brian.m.turnbull
  
  readfile($fPath);

}  
  
if (class_exists("userGroups") && !$user->ugHasAccess($user_ID, $id, $permission, $plugin_name)){

  $info = get_bloginfo("url");

  header("Location: ".$info."/wp-content/plugins/wp-publications-archive/access.php");

}

if (!class_exists("userGroups")){


  header('Content-type: "'.$fType->extName.'"');

  header('Content-Disposition: attachment; filename="'.$origname.'"'); //thanks to brian.m.turnbull

  readfile($fPath);

}

?> 
