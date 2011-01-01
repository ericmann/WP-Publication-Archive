<?php
  /*
  
  Plugin Name: Publications Archive Manager
  
  Description: Allows to upload, manage, search, and download publications (PDF, Power-Point, etc.).
  
  Plugin URI: http://code.google.com/p/wp-publications-archive/ 
  
  Author: Luis Lino, Siemens Networks, S.A; Eric Mann
  
  Version: 1.1.1
  
  */

include_once("uploader_functions.php");
include_once("wp_up_face.php");

class wp_uploader {

  /*
    Constructor:
    adds the actions and sets the filters for integration with wordpress
  */
  function wp_uploader(){
  
    add_action('activate_wp-publications-archive/wp-uploader.php',  array(&$this,'wp_uploader_install'));
  
    add_action('admin_menu',  array(&$this,'wp_uploader_menu'));
  
    add_action('admin_head', array(&$this,'wp_uploader_style'));
    
    //for the front office
    add_filter('the_content', array(&$this, 'wp_uploader_FO'));
    
    //for friendly URLs
    add_action('init', array(&$this,'wpup_friendly'));
    
    add_filter('posts_request', array (&$this, 'wpup_request' ));
    
    add_option('wp-publications-archive-number-of-files-per-page', 25);
  
    add_option('wp-publications-archive-number-of-files-per-page-on-front-office', 5);
    
  }
  
  /**
	* Friendly URL
	*/
	function wpup_friendly() {
	
  	global $wpdb, $user_ID;
		
    if($_REQUEST["wp-pub"]){
    
      $id = $_REQUEST["wp-pub"];
    
    }  
		
    $found = true;
     
    $info = get_bloginfo("url");

		if( $found ) {
    	//redirect and die :P
		  if( is_numeric($id)){

        header("Location: ".$info."/wp-content/plugins/wp-publications-archive/openfile.php?action=open&file=".$id);
        exit(); //stops wordpress from showing the index
      
      }
      
  	}
		
		return; //wordpress continues normally
	
  }
  
  /*
  * Function to print the publication in search results
  * @param array post with all the publication information
  */    
  function wpup_print_doc($post){

    global $user_ID;

    $doc = wpup_getDocById($post->ID);

    $cats = wpup_getDocCatById($post->ID);
    
    $permission = "all";

    $plugName = "wp_uploader";
    
    if(class_exists("userGroups"))
      $userGroups = new userGroups();

    if( (class_exists("userGroups") && $userGroups->ugHasAccess($user_ID, $post->ID, $permission, $plugName)) || !(class_exists("userGroups"))){
    
      echo "<div class='post'>";
      
      echo "<div class='post-title'>";

      echo "<span class='file_face_type'>[";

      $type = wpup_upGetType($doc->type);

      echo strtoupper(htmlentities($type->name));

      echo "] </span>";

      $face = new wp_uploader_FrontOffice();

      echo "<a href='".$face->url."/wp-content/plugins/wp-publications-archive/openfile.php?action=open&amp;file=".$doc->id."' title='Permanent Link to ";
      
      echo htmlentities($doc->title); 
      
      echo "'>";
      
      echo($doc->title); 
      
      echo "</a>";
      
      echo "<span class='file_face_size'> (";
      
      $final_size = wpup_size_hum_read(filesize($doc->fileName));
      
      echo $final_size;
      
      echo ")</span>";
      
      echo "</div>";
      
      echo "</div>";

    }
  }
  
  /*
  * Function to create the query
  * @param string arg with the query
  * @return string with the new query  
  */    
  function wpup_request($arg){
    
    global $user_ID, $wpdb, $table_prefix;
    
    $as = "";
    
    if($_REQUEST['s'] == "")
      return $arg;

    $query = "describe ".$wpdb->posts.";";
    
    $results = $wpdb->get_results($query);
    
    $first = true; 
    
    foreach($results as $result){
    
      if($first){
      
        $first = false;
        
      }
      
      else{ 
       
        $as.= ", ";
      
      }
      
      switch($result->Field) {
        case "ID":  $as .=" d.id AS ID";
                  break;
        case "post_date": $as .=" d.date AS post_date";
                  break;
        case "post_type": $as .=" 'doc' AS post_type";
                  break;
        default: $as .=" 1 AS ".$result->Field;
      }
    
    }
   
    $search_string =  $_REQUEST['s'];
    
    $search_string = explode(" ", $search_string);
    
    $where = "";
    
    $size = count($search_string);
    
    $where .= "(";
    
    $first = true;
    
    for( $i = 0 ; $i < $size; $i += 1 ){
    
      if($search_string[$i] != ""){
        
        if ( !$first ){
        
          $where .= " OR ";
        
        }
        
        else{
         
          $first = false;
        
        }
        
        $where .= " keywords LIKE '%".$search_string[$i]."%' OR";
        
        $where .= " title LIKE '%".$search_string[$i]."%' OR";
        
        $where .= " summary LIKE '%".$search_string[$i]."%' OR";
        
        $where .= " authors LIKE '%".$search_string[$i]."%' ";
     
      }
    }
    
    $where .= " ) ";
    
    if (class_exists("userGroups")){
    
      $groups = new userGroups();
    
      $plugin_name = "wp_uploader";
    
      $excludes = $groups->ugGetRestrictedResources($user_ID, $plugin_name);
    
      $where .= " AND (";
    
      $where .= " 1=1 ";
    
      $plugin_name = "wp_uploader";
    
      foreach ($excludes as $exclude){
      
        $where .= " AND id <> ".$exclude;
      
      }
      
      $where .= ")";
   
    }
    
    $results =  "SELECT ".$as." 
                  FROM ".$table_prefix."document d
                  WHERE ".$where;
    
    $my_nothing = " (";
    
    $my_select = ") UNION (";
    
    $my_select .= $results;
    
    $my_select .= ")";
    
    $string = explode( "LIMIT" , $arg);
    
    $size = count($string);
    
    $string_aux1 = $string[0];
    
    if ($size >1){
     
      $string_aux2 = $string[1];
    
    }
    
    $string[0] = $my_nothing;
    
    $string[1] = $string_aux1; 
    
    $string[2] = $my_select;
    
    if ($size >1){
    
      $string[3] = " LIMIT ".$string_aux2;
    }
    
    return implode($string);
  
  }
  
  /*
  * Function to return part of the query
  * @param string with the FROM statement
  * @return string with the complete FROM statement  
  */  
  function wpup_from($arg){
  
    global $wpdb, $table_prefix;
  
    $from = $arg.", ".$table_prefix."document ";

    return $from;
  }
  
  /*
  * Function to return part of the query
  * @param string with the WHERE statement
  * @return string with the complete WHERE statement  
  */  
  function wpup_where($arg){
  
    $search_string =  $_REQUEST['s'];
  
    $search_string = explode(" ", $search_string);
    
    $where = "";
  
    $size = count($search_string);
  
    for( $i = 0 ; $i < $size; $i += 1 ){
  
      if ($i != $size-1){
  
        $where .= " (keywords LIKE '%".$search_string[$i]."%') OR";
  
        $where .= " (title LIKE '%".$search_string[$i]."%') OR";
  
        $where .= " (summary LIKE '%".$search_string[$i]."%') OR";
  
      }
  
      else{
  
        $where .= " (keywords LIKE '%".$search_string[$i]."%') OR";
  
        $where .= " (title LIKE '%".$search_string[$i]."%') OR";
  
        $where .= " (summary LIKE '%".$search_string[$i]."%')";
  
      }
    }
  
    $return = " AND (( 1=1 ".$arg.") OR (".$where."))";
  
    return $return;
  
  }
  
  /*
    Function to add menu and submenu pages
  */
  function wp_uploader_menu(){
  
    $location = "../wp-content/plugins/wp-publications-archive/";
  
    add_menu_page('Publications', 'Publications',1, "wp-publications-archive/wp-uploadpubs.php");
   
    add_submenu_page('wp-publications-archive/wp-uploadpubs.php', 'Upload',           'Upload'           ,1, "wp-publications-archive/wp-uploadpubs.php", array("wp_uploader", "uploadPage") );
  
    add_submenu_page('wp-publications-archive/wp-uploadpubs.php', 'Files',     'Files'     ,1, "wp-publications-archive/manageFiles.php");
  
    add_submenu_page('wp-publications-archive/wp-uploadpubs.php', 'Categories','Categories',1, "wp-publications-archive/manageCategories.php");
  
    add_submenu_page('wp-publications-archive/wp-uploadpubs.php', ' Extensions  ',           ' Extensions '           ,1, "wp-publications-archive/config.php");
  
    add_submenu_page('wp-publications-archive/wp-uploadpubs.php', 'Settings',     'Settings'     ,1, "wp-publications-archive/frontOfficeConfig.php");
    
  }
  
  /* 
    Includes CSS for backoffice header
  */
  function wp_uploader_style(){
    ?>
      <style type="text/css"> 
      .submit input:disabled,
      .button:disabled{
        background: #f0f0f0;
        color: lightgrey;
        border-left-color: #999;
        border-top-color: #999;
        border: 3px double #ccc;
      } 
      #leftcontent { 
           float:left;
           width:300px;
           margin-right:15px;
      }
      #rightcontent {
      }
      #leftGroup{
            float:left;
            width:300px;
            margin-right:15px; 
      }
      #rightGroup {
      }
      </style>
      
    <?
  }
  
  /* 
    Page to display when we access the plugin tab (Publications)
  */
  function uploadPage(){
  
    include ("wp-uploadpubs.php");
  
  }
  
  /*
    Function to install the plugin, create the tables in the DB
  */
  function wp_uploader_install(){
  
    global  $table_prefix, $wpdb;
  
    include_once("uploader_functions.php");
  
    /*plugin tables*/
    $table_doc = $table_prefix."document";
    $table_cat = $table_prefix."category";
    $table_type = $table_prefix."type";
    $table_docCat = $table_prefix."docCat";
    
    require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
    
    //Create the "TYPE" table    
    if (strcasecmp($wpdb->get_var("show tables like '$table_type'"), $table_type)) {
      $sql = "CREATE TABLE ".$table_type." (
      id bigint(20),
      name text,
      pathToIcon text,
      extName text,
      Primary Key(id)
      );";
      dbDelta($sql);
    }
    else{
      dbDelta("UPDATE ".$table_type." SET pathToIcon = REPLACE (pathToIcon, '/wp-uploader/', '/wp-publications-archive/');");
    }
    
    //Create the "DOCUMENT" table
    if (strcasecmp($wpdb->get_var("show tables like '$table_doc'"), $table_doc)) {
      $sql = "CREATE TABLE ".$table_doc." (
      id bigint(20),
      title text,
      authors text,
      summary text,
      keywords text,
      fileName text,
      date date,
      downloads bigint(20),
      type bigint(20),
      Primary Key(id),
      Foreign Key (type) references TYPE(id)
      );";
      dbDelta($sql);
    }

    //Create the "CATEGORY" table      
    if (strcasecmp($wpdb->get_var("show tables like '$table_cat'"), $table_cat)) {
      $sql = "CREATE TABLE ".$table_cat." (
      id bigint(20),
      title text,
      description text,
      parent bigint(20),
      Primary Key(id),
      Foreign Key (id) references ".$table_cat."(id)
      );";
      dbDelta($sql);
    }
    
    //Create the relation document/category "DOCCAT" table  
    if (strcasecmp($wpdb->get_var("show tables like '$table_docCat'") , $table_docCat)) {
      $sql = "CREATE TABLE ".$table_docCat." (
      id1 bigint(20),
      id2 bigint(20),
      Primary Key(id1, id2),
      Foreign Key (id1) references ".$table_doc."(id),
      Foreign Key (id2) references ".$table_cat."(id)
      );";
      dbDelta($sql);
    }
    
    add_option('wp-publications-archive-number-of-files-per-page-on-front-office-search', 5);
      
    $uploaddir = ABSPATH."wp-content/uploads/";
  
    $uploaddir = uploader_fix_windows_path($uploaddir);
  
    $uploaddir .= "/publications";
  
    @mkdir($uploaddir);
    
    
  }
  
  /*
  * Function to create the front office page
  * @param string content string with all the source of the page  
  * @return string $content of the page.   
  */
  function wp_uploader_FO($content){
  
    global $post;
    if($post->post_type == "page" || $post->post_status == "static" ){
  
      if (strpos($content, "<!--wp_uploader_FO-->" ) !== false) {
  
        include_once("wp_up_face.php");
  
        $page = new wp_uploader_FrontOffice();
  
        return $page->get_Content();
        
      }
    }
  
    return $content;
  
  }
  

}
$wp_uploader= new wp_uploader();
?>
