<?php
/*
  File with all the functions necessary to the plugin 
*/



/*
* Function to get all the available categories from the DB
* @return array with the results of the query
*/
function wpup_available_categories(){
  global $wpdb, $table_prefix;
  return $wpdb->get_results("SELECT id, title
                               FROM ".$table_prefix."category
                               ;");
}

/*
* Function to create URLs in a way to give valid html
* @param string array  formdata, url to be created.
* @param int muneric_prefix, prefix to an array base value
* @param string step, separator
* @param int key
* @return string url
*/
function wp_up_http_build_query( $formdata, $numeric_prefix = null, $sep='', $key = null ) {
  $res = array();
  foreach ((array)$formdata as $k=>$v) {
  	 $tmp_key = urlencode(is_int($k) ? $numeric_prefix.$k : $k);
  	 if ($key) $tmp_key = $key.'['.$tmp_key.']';
  	 $res[] = ( ( is_array($v) || is_object($v) ) ? http_build_query($v, null, $tmp_key) : $tmp_key."=".urlencode($v) );
  }
  if(empty($sep)) {
     		$sep = ini_get("arg_separator.output");
  };
  return implode($sep, $res);
}

/*
* funtion to return humam readable file size
* @param $size file size to convert
* @return  string with human readable file size
**/ 
 function wpup_size_hum_read($size){
          /*
          Returns a human readable size
          */
            $i=0;
            $iec = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
            while (($size/1024)>1) {
             $size=$size/1024;
             $i++;
            }
            $pos = strpos($size,'.');
            if ($pos == 0)
              $pos = 4;

              
            return substr($size,0,$pos).$iec[$i];
            
}
         

/*
* Function to print the parent categories into a combo box
* @param int selected, id with the selected category
* @param bool hideEmpty, if true, hide empty categories
* @param int idFrom, id with the category that is calling the function
* @return string, htlm containning the available parent categories
**/
function wpup_parents($selected = "", $hideEmpty = false, $idFrom = -3){
  $cats = wpup_getCats();


  foreach($cats as $cat){
    if ( $cat->parent == -1 && $cat->id > -1 ){ 
      $tab = 0;  
      $results = wpup_childs($cat->id, $tab, $selected, $hideEmpty, $idFrom);

      if( (( $hideEmpty && $results != "" ) || ( $hideEmpty && wpup_hasFiles($cat->id) ))
        
        
        
        || ( !$hideEmpty || wpup_hasFiles($cat->id) ))
      
      {

        if($idFrom != $cat->id){
          echo "<option";
          if($selected == $cat->id )
            echo " SELECTED";
          
          echo " value='$cat->id'>$cat->title</option>";
          
          //child categories
     
     
          echo $results;      
        }
      }
    }
  }
}


/*
* Function to print the child categories into a combo box
* @param int id of the parent category
* @param int tab information about the tabulations
* @param int selected, id with the selected category
* @param bool hideEmpty, if true, hide empty categories
* @param int idFrom, id with the category that is calling the function
* @return string, htlm containning the available child categories
**/ 
function wpup_childs($id, $tab, $selected, $hideEmpty, $idFrom){
  $tab += 1;
  $childs = wpup_getChilds($id);
  $string = "";
  
  foreach ($childs as $child){
    $stringAux = wpup_childs($child->id, $tab, $selected, $hideEmpty, $idFrom);
    if( !$hideEmpty || (($stringAux != "" || wpup_hasFiles($child->id)) && $hideEmpty) ){
      if( $child->id != $idFrom){
        $string .= "<option";
        if( $selected == $child->id)
          $string .= " SELECTED";
        
        $string .=" value='$child->id'>";
        for( $i = 0; $i < $tab ; $i += 1)
          $string .= "&nbsp;";
        $string .= "$child->title </option>";
        $string .= $stringAux;
      }
    }
  }
  
  return $string;
}


/*
* Fuction used to check if a category has files
* @param int $id id of the category
* @return true if the category has files or false if not
*/
function wpup_hasFiles($id){
  global $user_ID, $wpdb, $table_prefix;
  $vars = $wpdb->get_results("SELECT *
                         FROM ".$table_prefix."docCat
                         WHERE id2 = ".$id."
                         ;");
  $found = false;
                       
  if (class_exists("userGroups")){
    $plugin_name = "wp_uploader";
    $groups = new userGroups();
    //the ones that do not have access.
    $excludes = $groups->ugGetRestrictedResources($user_ID, $plugin_name);
    print_r($excludes); 
    
    
    $found = false;                       
    foreach($vars as $var){
      foreach($excludes as $exclude){
        if ($var->id1 == $exclude)
          $found = true;
      }
    }
  }           
  
  if (!$found && count($vars) > 0 )
    return true;
  else
    return false;
}




/*
* Function used to get one document by the given id
* @param int id document id
* @return array with document
*/
function wpup_getDocById($id){
  global $wpdb, $table_prefix;
  return $wpdb->get_row("SELECT *
                         FROM ".$table_prefix."document
                         WHERE id = ".$id."
                         ;");
}

/*
* Function used to get one type by the given extension name
* @param string $type extension name
* @retun int with the id of the file extension
*/
function wpup_getTypeByExtName($type){
  global $wpdb, $table_prefix;
  return $wpdb->get_var("SELECT id
                          FROM ".$table_prefix."type
                          WHERE '".$type."' LIKE extName
                          ;");
}

/*
* Function to get the documents ordered by title
* @return array of documents ordered by title
*/
function wpup_getDocsOrderByTitle(){
  global $wpdb, $table_prefix;
  return $wpdb->get_results("SELECT *
                              FROM ".$table_prefix."document
                              ORDER BY title ASC
                              ;");
}

/*
* Function used to get types ordered by name
* @return array of all available types
*/
function wpup_getTypes(){
  global $wpdb, $table_prefix;
  return $wpdb->get_results("SELECT *
                               FROM ".$table_prefix."type
                               ORDER BY name ASC
                               ;"); 
}

/*
* Funtion used to get the path to icon by the given id
* @param id, id of the file extension
* @return string containning the path to the extension icon
*/
function wpup_getPathToIcon($id){
  global $wpdb, $table_prefix;
  return $wpdb->get_var("SELECT pathToIcon
                        FROM ".$table_prefix."type
                        WHERE  id = ".$id."
                        ;");
}

/*
* Function used to get the categories
* @return array of all the available categories ordered by title
*/
function wpup_getCats(){
  global $wpdb, $table_prefix;
  return $wpdb->get_results("SELECT *
                              FROM ".$table_prefix."category
                              ORDER BY title ASC
                              ;");
}

//Function to get documents in a given interval
function wpup_getDocsOrderByTitleSS($start, $stop){
  global $wpdb, $table_prefix;
  return $wpdb->get_results("SELECT *
                               FROM ".$table_prefix."document
                               ORDER BY title ASC
                               LIMIT ".$start.", ".$stop."
                               ;");
}


/*
* Function used to get the values of docCat table
* @return array containning all the values in the DocCat table
*/
function wpup_getDocCat(){
  global $wpdb, $table_prefix;
  return $wpdb->get_results("SELECT *
                              FROM ".$table_prefix."docCat
                              ;");
}

/*
* Function to check if a given category has childs
* @param id of the category
* @return true if the category has chils or false if not
*/
function wpup_hasChilds($id){
  $cats = wpup_getCats();
  foreach($cats as $cat){
    if($cat->parent == $id)
      return true;  
  }
  return false;
}

/*
* Function used to print groups in the Upload
* @param array containing selected groups
* @return string hitn html code with groups as checkboxes
*/
function wpup_groups($avGroups){
  $userGroups = new userGroups();
  $groups = $userGroups->getGroups(true);
  foreach($groups as $group){
    echo "<label><input name ='avGroups[]' value ='".$group->id."' type='checkbox'";
    for( $i=0 ; $i < count($avGroups) ; $i = $i+1)
      if($avGroups[$i] == $group->id)
        echo " CHECKED ";
    echo " /> &nbsp;";
    echo $group->name;
    echo "</label><br />";
  }

}

/*
* Function used to get the details of a category by the given document id
* @param id of the document
* @return array contanning all the categories of a given document
*/
function wpup_getDocCatById($id){
  global $wpdb, $table_prefix;
  return $wpdb->get_results("SELECT *
                                FROM ".$table_prefix."docCat
                                WHERE id1 = ".$id."
                               ;");
}

/*
* Function to get all the documents with a certain category
* @param id of the category
* @return array with all the id of all the documents with the given category
*/
function wpup_getCatDocs($id){
  global $wpdb, $table_prefix;
  return $wpdb->get_results("SELECT *
                                FROM ".$table_prefix."docCat
                                WHERE id2 = ".$id."
                               ;");
}

/*
* Function to get the fileName of a file by the given file id
* @param file id
* @return array containing the fileName and the Type of the given file
*/
function wpup_getFilenameType($id){
  global $wpdb, $table_prefix;
  return $wpdb->get_row("SELECT fileName, type 
                              FROM ".$table_prefix."document
                              WHERE id = ".$id."
                             ;");
}

/*
* Function to get all the files by a given file type
* @param id of the file type
* @return array contaninning all the documents with the given file type
*/
function wpup_getDocsByFile($id){
  global $wpdb, $table_prefix;
  $docs = $wpdb->get_results("SELECT *
                                FROM ".$table_prefix."document
                                WHERE ".$id." = type
                               ;");
  return $docs;
}

/*
* Funtion to get all the details of category by the given category id
* @param id of the category
* @return array containning all the details of a given category
*/ 
function wpup_getCat($id){
  global $wpdb, $table_prefix;
  return $wpdb->get_row("SELECT *
                              FROM ".$table_prefix."category
                              WHERE id = ".$id."
                              ;");
}

/*
* Funtion used to get all the details of a file by the given id
* @param id of the file type
* @return array contaninning all the details of the given file type
*/
function wpup_upGetType($id){
  global $wpdb, $table_prefix;
  return $type = $wpdb->get_row("SELECT *
                              FROM ".$table_prefix."type
                              WHERE id = ".$id."
                              ;");
}

/*
* Function used to get all the categories of a given document id.
* @param id of the document
* @return array containning all the results of the query
*/
function wpup_getDocCats($id){
  global $wpdb, $table_prefix;
  return $cats = $wpdb->get_results("SELECT *
                              FROM ".$table_prefix."docCat a, ".$table_prefix."category b 
                              WHERE a.id1 = ".$id." AND a.id2 = b.id 
                              ;");
}

/*
* Funtion used to get the childs of a category by the given id
* @param id of the category
* @param array containing all the child categories of the given category
*/
function wpup_getChilds($id){
  global $wpdb, $table_prefix;
  $childs = $wpdb->get_results("SELECT *
                                FROM ".$table_prefix."category
                                WHERE ".$id." = parent
                                ORDER BY title
                               ;");
  return $childs;
}



/*
* Funtion to fix the / and \ in the windows paths
* @param path to fix
* @return string containning the fixed path
*/ 
function uploader_fix_windows_path($path){
	$path = str_replace("\\","/",$path);
	$path = str_replace("//","/",$path);
	$path = str_replace("//","/",$path);
	$path = str_replace("//","/",$path);
	if(substr($path, -1)=='/'){
		$path = substr($path,0,strlen($path)-1);
	}
	return $path;
}
 
/*
* Funtion used to print the groups without checkboxes
* @param id of the document
* @param name of the plugin
* @return string with the groups of a given document  
*/
function wpup_printG($id){
  echo "<td>";
  
  $userGroups = new userGroups();
  $plugName = "wp_uploader";
  //function ugGetGroupsOfPlugin($plugin_name, $resource="", $permission=""){
  $results = $userGroups->ugGetGroupsOfPlugin($plugName, $id);
  
  $size = count($results);
  if($size > 0){
    foreach($results as $result){
      echo "- ";
      echo $result->name;
      echo "<br />";
    }
  }
  else{
    echo "(none)";
  }
    
  echo "</td>";
  
} 
 
 
/*
* Funtion used to print the categories and respective childs without checkboxes
* @param id of the document
*/
function wpup_printC($id){
  $string="";
  $cats = wpup_getDocCats($id);
  $tab = -1;
  //$prev = 0;
  if(count($cats)){
    foreach($cats as $cat){
      $string="";
      $tab = 0;
      $string .= "- ";
      $string .= $cat->title;
      $string .= "<br />";
      //$prev = $cat->id;
      echo $string;
    }
  }
  else{
    echo "(none)";
  }
}

/*
* Funtion to print the print the categories with a given array of selected categories
* @param selCats array containning the selected categories
* @param id of the created category
*/
function wpup_print_cats_scroll($selCats, $addId){
  $string="";
  $tab = 0;
  $cats = wpup_getCats();
  if(isset($addId))
    $add = wpup_getCat($addId);
  foreach($cats as $cat){
    $string="";
    if(($cat->parent == -1 && $cat->id >= -1)){
      //parents
      $tab = 0;
      $string .= "<label for='cat_".$cat->id."'><input id='cat_".$cat->id."' name ='selCats[]' value = '".$cat->id."' type='checkbox'";
      for( $i=0 ; $i < count($selCats) ; $i = $i+1)
        if($selCats[$i] == $cat->id)
          $string .= ' CHECKED ';
      $string .= "/> &nbsp;";
      $string .= $cat->title;
      $string .= "</label><br />";
      echo $string;
      //childs
      $tab = $tab + 1;
      wp_up_print_childs_scroll($cat->id, $tab, $selCats, $addId);
    }
  }
}

/*
* Function used to print the category childs with scroll for the upload categories box
* @param catId id of the category
* @param tab value of the tab of the table
* @param selCats array with the selected categories
* @param addId id of the created category
*/
function wp_up_print_childs_scroll($catId, $tab, $selCats, $addId ){
  $string="";
  $childs = wpup_getChilds($catId);
  if (isset($childs)){
    foreach($childs as $child){
      $string="";
      $string .= "<label for='cat_".$child->id."'><input id='cat_".$child->id."' name ='selCats[]' value = '".$child->id."' type='checkbox'";
      for( $i=0 ; $i < count($selCats) ; $i = $i+1)
        if($selCats[$i] == $child->id )
          $string .= ' CHECKED ';
      $string .= "/>";
      for ( $i = 0 ; $i <= $tab ; $i += 1 ){
        $string .= "  &nbsp;";
      }
      $string .= $child->title;
      $string .= "</label><br />";
      echo $string;
      $tab += 1;
      wp_up_print_childs_scroll($child->id, $tab, $selCats, $addId);
      $tab -= 1;
    }
  }  
}
  
/*
* Funtion used to print the category childs by the given id, tab and alt
* @param id of the category
* @param tab value of the table tab
* @param alt value of the indentation
*/
function wpup_printChilds($id, $tab, $alt){
  $childs = wpup_getChilds($id);
  if (isset($childs)){
    if($result->parent == 0){
      foreach($childs as $child){
        if($alt){
          echo "<tr class='alternate'>";
        }else{
          echo "<tr>";
        }
        $alt = !$alt;      
        echo "<td>";
        for ( $i = 0 ; $i <= $tab ; $i += 1 ){
          echo " &nbsp; &nbsp; &nbsp;";
        }
        echo $child->title;
        echo "</td><td>";
        echo $child->description;
        echo "</td><td>";
        echo "<a href='?page=wp-publications-archive/manageCategories.php&amp;action=edit&amp;cat=".$child->id."#edit' class='edit'>Edit</a>";     
        echo "</td><td>";
         echo "<a href='#' onclick=\"javascript:AskConfirm(".$child->id.",'".$child->title."');\" class=\"delete\">Delete</a>";
        echo "</td></tr>";
        $tab += 1;
        $alt = wpup_printChilds($child->id, $tab, $alt);
        $tab -= 1;
      }
    }
    return $alt;
  }
  else{
    return $alt;
  }
}

/*
* Funtion used to get the next free id in a given DB table name
* @param string with the table name
* @return int id
*/
function wpup_getNextFreeId($table){
  global $wpdb, $table_prefix;
  $results = $wpdb->get_results("SELECT id
                                FROM ".$table_prefix."".$table."
                                ORDER BY id ASC
                                ;");
  
  //get the bigger id
  $maior = 1;
  foreach($results as $res){
    if($res->id > $maior){
      $maior = $res->id;
    }
  }
  

  $found = false;

  $a = 0;
  foreach($results as $result){
    $array[$a]=$result->id;
    $a+=1;
  }
  
  $i = 1;
  $size = count($array);
  for( $i ; $i <= $maior && !$found  && $size > 0; $i += 1 ){
 
    if(array_search( $i ,$array) === false){
      $found = true;
  //    print_r($array);
  //    echo $i;
      return $i;
    }
  } 
  
  if(!$found){
    $i = $maior + 1;
    return $i;
  }
}

/*
* Function that transforms a string to valid string with no special characters
* @param string to change
* @return string converted to htmlentities
*/
function wpup_unhtmlentities ($string)
{
   $trans_tbl = get_html_translation_table (HTML_ENTITIES);
   $trans_tbl = array_flip ($trans_tbl);
   return strtr ($string, $trans_tbl);
}
