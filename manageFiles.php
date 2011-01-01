<script type="text/javascript">
function AskConfirm(id, name){
var message= 'You are about to delete the \''+name+'\' file, are you ready to continue?';
if (confirm(message)){
document.location.href='?page=wp-publications-archive/manageFiles.php&  action=delete&file=' + id;
}
}
</script>
<script type="text/javascript" src="../wp-content/plugins/wp-publications-archive/add.js"></script>
<?php
/*
* This file creates the Files tab
*/
global $wpdb, $table_prefix;
require_once(ABSPATH . 'wp-admin/upgrade-functions.php'); 
include_once("uploader_functions.php");
$action = $_REQUEST['action'];
//funtion to create the default value of pages to display per page in the "files" page
function install(){
  delete_option('wp-publications-archive-number-of-files-per-page');
  add_option('wp-publications-archive-number-of-files-per-page', 25);
}
if($_REQUEST["mode"]=='update' && isset ($_POST['numberOfFiles'])){
  
  $number = $_POST['numberOfFiles'];
  update_option('wp-publications-archive-number-of-files-per-page', $number);
  $Message = "Number of files to display, per page, was updated to $number.";
}
/*
* Function to check if a given input is filled
* @param string atribute, field of the form
* @return boolean, true if field is filled, false if not filled
*/
function check($in){
  $found = false;
  
  for ( $i = 0 ; $i < count($in) && !$found ; $i += 1 )
    if ( $in[$i] != "" )
      $found = true;
  
  return $found;
}
if($_REQUEST["mode"]=='update' && check($_POST['authors']) &&
        $_POST['title']!="" &&
        check($_POST['keywords']) &&
        @checkdate ( $_POST['month'], $_POST['day'], $_POST['year'] ) &&
        $_POST['id']!="" &&  !$_POST['cancel'] == 'Cancel'){
      
  $day = $_POST['day'];
  $month = $_POST['month'];
  $year = $_POST['year']; 
  $date  = mktime(0, 0, 0, $month  , $day , $year);
  $date = date("Y,m,d",$date);
  $iauthors = "";
  $ikeywords = "";
  $iiauthors = $_POST['authors'];
  
  for($i = 0 ; $i < count($iiauthors) ; $i += 1){
    
    if($iiauthors[$i] != ""){
      
      if($i == 0){
      
        $iauthors .= $iiauthors[$i];
      
      }
      
      else{
  
        $iauthors .= ";";
        $iauthors .= $iiauthors[$i];
  
      }
    }
  }
      
  $iikeywords = $_POST['keywords']; 
  
  for($i = 0 ; $i < count($iikeywords) ; $i += 1){
    
    if($iikeywords[$i] != ""){  
      
      if($i == 0){
       
        $ikeywords .= $iikeywords[$i];
      }
      
      else{
      
        $ikeywords .= ";";
        $ikeywords .= $iikeywords[$i];
    
      }
    }
  }
     
  if(isset($_FILES['file']['name'])){
    
    //if the file already exists, delete de existing and replace with the new one
    /*
    $toDeleteId = $_POST['id'];
    $result = getDocById($toDeleteId);
    unlink($result->fileName);*/
    
    if(class_exists("userGroups"))
      $avaGroups = $_POST['avGroups'];
    
    //create the path to the new file
    $uploaddir = ABSPATH.'wp-content\\uploads\\';
    $uploaddir = uploader_fix_windows_path($uploaddir);
    $uploaddir .= "/";
    $uploadfile = $uploaddir . basename($_FILES['file']['name']);
    
    $uploadfile = explode('.', $uploadfile);
    $size = count($uploadfile);
    $uploadfile[$size]=$uploadfile[$size-1];
    $uploadfile[$size-1] = "_#_#".$id.".";
    $uploadfile = implode("", $uploadfile);
    $fileName = $uploadfile;
    
    //upload the new file
    $results = wpup_getDocsOrderByTitle();
    $exists = false;
    
    foreach($results as $res){
    
      if(!$exists){
    
        if( $res->fileName == $uploadfile )
    
          $exists = true;
    
      }
    }
    
    if (!$exists){
    
      if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
    
        $fileName = $uploadfile;
    
        $wpdb->query("UPDATE ".$table_prefix."document 
                SET title = '".$_POST['title']."', date = '".$date."',  summary = '".$_POST['summary']."',
                keywords = '".$ikeywords."', fileName = '".$fileName."' , authors = '".$iauthors."'
                WHERE id =  ".$_POST['id'].";");
    
      } 
      else{
    
        $wpdb->query("UPDATE ".$table_prefix."document 
                  SET title = '".$_POST['title']."', date = '".$date."', summary = '".$_POST['summary']."',
                   keywords = '".$ikeywords."', authors = '".$iauthors."'
                   WHERE id =  ".$_POST['id'].";");
    
      }
      
      $wpdb->query("DELETE FROM ".$table_prefix."docCat
                    WHERE id1 = ".$_POST['id'].";");
      
      if(isset($_POST['selCats'])){
    
        $cats = array_values($_POST['selCats']);
      
        for( $i = 0 ; $i < count($_POST['selCats']) ; $i+=1 ){
    
          $wpdb->query("INSERT INTO ".$table_prefix."docCat (id1, id2)
                        VALUES ('".$_POST['id']."','".$cats[$i]."') 
                       ;");
    
        }
      }
      
      //Groups
        if(class_exists("userGroups") && isset($_POST['avGroups'])) {
    
          $userGroups = new userGroups();
          $plugName = "wp_uploader";
          $permission = "all";
          $description = "";
          $userGroups->ugSetGroupsAccess( $avaGroups, $_POST['id'], $permission, $plugName, $description );
          
        }
      
      $Message = 'File was updated successfully.';
    
    }
    
    else {
      
      if($exists){
      
        $MessageError = 'File already exists!';
        $result = wpup_getDocById($toDeleteId);
        $mtitle = $result->title;
             
        $authors = $_POST['authors'];
        $keywords = $_POST['keywords'];
     
        //$keywords = $result->keywords;
        $selCats =wpup_getDocCatById($toDeleteId);
        $i=0;
      
        foreach($selCats as $cat){
      
          $var[$i] = $cat->id2;
          $i = $i+1;
      
        }
      
        echo "href='?page=wp-publications-archive/manageFiles.php&amp;action=edit&amp;file=".$result->id."#edit'";
      
      }
    }
  }
}
else{
  if($_REQUEST["mode"]=='update' && !isset($_POST['numberOfFiles']) && ( !check($_POST['authors']) ||
      $_POST['title'] =="" ||
      !check($_POST['keywords']) ||
      !@checkdate ( $_POST['month'], $_POST['day'], $_POST['year'] ) ||
      $_POST['id']=="" ) &&  !$_POST['cancel'] == 'Cancel'){
      
        $MessageErrorTab= "Edit failed. Please insert a valid date.";
        $action = "incomplete";
  
  }
  else{  
    if($_REQUEST["mode"]=='update' && !$_POST['cancel'] == 'Cancel' && !isset($_POST['numberOfFiles'])){
    
      $MessageErrorTab = 'Edit failed. Please fill all fields marked with *.';
      $action = 'incomplete';
      
      $day = $_POST['day'];
      $month = $_POST['month'];
      $year = $_POST['year']; 
      
      if (!@checkdate ( $month, $day, $year )){
      
        $MessageErrorTab= "Edit failed. Please fill all fields marked with *, and insert a valid date.";
      
      }
      
      $mtitle = wpup_unhtmlentities($_POST['title']);
      $summary = wpup_unhtmlentities($_POST['summary']);
      $id = $_POST['id'];
      $var = $_POST['selCats'];
       
      $authors = $_POST['authors'];
      $keywords = $_POST['keywords']; 
    }
  }
}
if($_POST['cancel'] == 'Cancel'){
 
  $Message = 'Edit canceled.';
}
if($action == 'delete') {
  $id = (int) $_GET['file'];
  
  $result = wpup_getDocById($id);
  
  $wpdb->query("DELETE FROM ".$table_prefix."document
              WHERE id = ".$id.";");
  
  $wpdb->query("DELETE FROM ".$table_prefix."docCat
              WHERE id1 = ".$id.";");
              
  $file = $result->fileName;          
  @unlink($file);
  
  //Groups    
  if(class_exists("userGroups")){
    $userGroups = new userGroups();
    $plugName = "wp_uploader";
    $userGroups->ugDeleteResource($plugName, $id);
  }
  $Message ='File deleted successfully.';
}
if($action == 'open') {
  $id = (int) $_GET['file'];
  $result = wpup_getFilenameType($id); 
  $fPath = $result->fileName;
  $fType = wpup_upGetType($result->type);
  $fType = $fType->extName;
  echo $fPath;
  echo "<br>";
  echo $fType;
}
if($action == 'jump' && isset($_GET['pagesnext'])){
  $start = (int) $_GET['pagesnext'];
}
else{
  $start = 0;
}
if ($action == 'jump' && isset($_GET['pagesprevious'])){
  $start = (int) $_GET['pagesprevious'];
  $start = $start - get_option('wp-publications-archive-number-of-files-per-page'); 
}
if ($Message){
  echo "<div id='message' class='updated fade'>";
  echo "<p>".$Message."</p>";
  echo "</div>";
}
if ($MessageError){
  
  echo "<div id='message' class='error fade'>";
  echo "<p>".$MessageError."</p>";
  echo "</div>";
} 
$step = get_option('wp-publications-archive-number-of-files-per-page');
$stop2 = $start + $step;
$results = wpup_getDocsOrderByTitleSS( $start, $step); 
?>
<div class="wrap">
  <h2>    Manage Files
    <? if($action != 'edit'){  ?>    (<a href='?page=wp-publications-archive/upload.php'>upload new file</a>)
    <? } ?></h2>
<? if($results){ 
    
    $count = $wpdb->get_var("SELECT COUNT(*) as COUNT
                         FROM ".$table_prefix."document;");
    $numRes = count($results); 
    
  ?>
  <div style="text-align:right;">    Showing
    <? echo $numRes; ?>    from
    <? echo $count; ?>    publications.
  </div>
<? }
  ?>
  <!--<br />-->
  <form method="post" action="?page=wp-publications-archive/manageFiles.php&amp;mode=update">
    <? if($results){ ?>
    <br />
    <table >
      <tr class="thead">
        <th style="width: 210px">          Title
        </th>
        <th style="width: 210px">          Authors
        </th>
        <th style="width: 220px">          Categories
        </th>
<?
        if(class_exists("userGroups")) { ?>
        <th style="width: 220px">          Groups
        </th>
        <? } ?>
        <th style="width: 50px">          Type
        </th>
        <th style="width: 50px">          Downloads
        </th>
        <th style="width: 60px">
        </th>
        <th style="width: 60px">
        </th>
        <th style="width: 60px">
        </th>
      </tr>
<?    
  
  $count = count(wpup_getDocsOrderByTitle());
  
  $alt=true;
  foreach($results as $result ){
  if($alt){
    echo "<tr class='alternate'>";
  }else{
    echo "<tr>";
  }
    $alt = !$alt;
    $res = wpup_upGetType($result->type);
    $type = $res->pathToIcon;
    $extName = $res->extName;
    
    $authors = explode(';',$result->authors);
    
    echo "<td>".$result->title."</td><td>"; 
    for ($i = 0 ; $i < count($authors) ; $i+=1){
      echo "- ";  
      echo $authors[$i];
      echo "<br />";  
    }
    unset ($authors);
    echo "</td><td>";
    wpup_printC($result->id);
    echo "</td>";
    
if(class_exists("userGroups")) {
      wpup_printG($result->id);
    }
    $string = get_bloginfo('url');
    if($type != ""){
      $string .=$type;
      echo "<td><img alt='$extName' src='$string' /></td><td style='text-align:right; padding-right:20px;'>";
    }
    else{
    
      if ($extName){
      
        echo "<td>".$extName."</td><td style='text-align:right; padding-right:20px;'>";
      }
      else{
        
        echo "<td>(none)</td><td style='text-align:right; padding-right:20px;'>";    
         
      }
    
    }
    echo $result->downloads;
    echo "</td><td>";
    echo "<a href='../wp-content/plugins/wp-publications-archive/openfile.php?action=open&amp;file=".$result->id."' class='edit'>Download</a>";     
    echo "</td><td>";
    echo "<a href='?page=wp-publications-archive/manageFiles.php&amp;action=edit&amp;file=".$result->id."#edit' class='edit'>Edit</a>";     
    echo "</td><td>";
    echo "<a href='#' onclick=\"javascript:AskConfirm(".$result->id.",'".$result->title."');\" class=\"delete\">Delete</a>";
    echo "</td></tr>";
  }
      ?>
    </table>
    <br />
    <div class="submit" align=right>
      <table align=right>
        <tr>
          <td style="width: 90px">
            <? if ($start != 0){ ?>
            <a class="button" <?php echo("href='?page=wp-publications-archive/manageFiles.php&amp;action=jump&amp;pagesprevious=".$start."'"); ?> >&laquo;&nbsp;Previous&nbsp;</a>
<? }
            ?>
          </td>
          <td style="width: 90px">
<?       
            $aux = $start + $step;            
            if ( $aux < $count ){                ?>
            <a class="button" <?php echo("href='?page=wp-publications-archive/manageFiles.php&amp;action=jump&amp;pagesnext=".$stop2."'"); ?> >&nbsp;&nbsp;&nbsp;Next&nbsp;&raquo;&nbsp;&nbsp;</a>
<?
   }
            ?>
          </td>
        </tr>
      </table>
<? } else echo "<b>There are no files to display.</b>";
      
      ?>
      <br />
      <br />
    </div>
  </form>
</div>
<?
    if($action == 'edit' || $action == 'incomplete'){
      if ($action == 'edit'){
        $id = (int) $_GET['file'];
        $result = wpup_getDocById($id);
        $mtitle = $result->title;
        $date = $result->date; 
        $date = explode("-", $date);
        $day = $date[2];
        $month = $date[1];
        $year = $date[0];
        
        
        if(!isset($authors)){
          $authors = $result->authors;
          $authors = explode( ";" , $authors );
        }
        
        if(!isset($keywords)){
        $keywords = $result->keywords;
        $keywords = explode( ";" , $keywords );
        }
        
        $summary = $result->summary;
        
        $selCats = wpup_getDocCatById($id);
        $i=0;
        
        foreach($selCats as $cat){
         $var[$i] = $cat->id2;
         $i = $i+1;
        }
      }
      if ($action == 'incomplete'){
        $id = (int) $_GET['file'];
        $result = wpup_getDocById($id);
        $mtitle = $_POST['title'];
        
        $day = $_POST['day'];
        $month = $_POST['month'];
        $year = $_POST['year'];
        
        $authors = $_POST['authors'];
        $keywords = $_POST['keywords'];
        
        
        $summary = $_POST['summary'];
        
        $selCats = $_POST['selCats'];
        $i=0;
        
        if(isset($selCats))
          foreach($selCats as $cat){
           $var[$i] = $cat->id2;
           $i = $i+1;
          }
      
      }
?>
<?  if ($MessageErrorTab){ ?>
<div id='messageError' class='error fade'>
  <p>
    <? echo $MessageErrorTab; ?>
  </p>
</div>
<? } ?>
<div class="wrap" id="edit">
  <h2>Edit File</h2>
  <form enctype="multipart/form-data" name="editfiles" id="editfiles" action="?page=wp-publications-archive/manageFiles.php&amp;mode=update#messageError" method="post">
    <input name="id" type="hidden" value="<? echo $id; ?>" />
    <table>
      <tr>
        <td style="vertical-align:top">
          <b>*</b>          &nbsp;
        </td>
        <td style="vertical-align:top">
          <b style="display:inline">
            Title:</b>
        </td>
        <td>
          <input name="title" value="<? echo $mtitle; ?>" id="title" type="text" maxlength="70" style="width: 470px" />
        </td>
      </tr>
      <tr>
        <td style="vertical-align:top">
          <b>*</b>          &nbsp;
        </td>
        <td style="vertical-align:top">
          <b style="display:inline">
            Date: </b>
        </td>
        <td style="vertical-align:top">
          <?php $month +=0; ?>
          <select name="month">
            <option <? if($month == 1) echo "SELECTED"; ?> value='01'>            January
            </option>
            <option <? if($month == 2) echo "SELECTED"; ?> value='02'>            February
            </option>
            <option <? if($month == 3) echo "SELECTED"; ?> value='03'>            March
            </option>
            <option <? if($month == 4) echo "SELECTED"; ?> value='04'>            April
            </option>
            <option <? if($month == 5) echo "SELECTED"; ?> value='05'>            May
            </option>
            <option <? if($month == 6) echo "SELECTED"; ?> value='06'>            June
            </option>
            <option <? if($month == 7) echo "SELECTED"; ?> value='07'>            July
            </option>
            <option <? if($month == 8) echo "SELECTED"; ?> value='08'>            August
            </option>
            <option <? if($month == 9) echo "SELECTED"; ?> value='09'>            September
            </option>
            <option <? if($month == 10) echo "SELECTED"; ?> value='10'>            October
            </option>
            <option <? if($month == 11) echo "SELECTED"; ?> value='11'>            November
            </option>
            <option <? if($month == 12) echo "SELECTED"; ?> value='12'>            December
            </option>
          </select>
          <input name="day" value="<? echo $day; ?>" id="day" type="text" maxlength="2" style="width: 18px"/>
          <input name="year" value="<? echo $year; ?>  " id="year" type="text" maxlength="4" style="width: 35px"/>
        </td>
      </tr>
      <tr>
        <td>
        </td>
        <td style="vertical-align:top">
          <b style="display:inline">
            Summary: </b>
          <br />          (Short description)
        </td>
        <td>
<textarea name="summary" id="summary" style="width: 470px; height: 100px; wrap:on"><? echo $summary; ?></textarea>
          <br />
        </td>
      </tr>
      <tr>
        <td style="vertical-align:top">
          <b>*</b>          &nbsp;
          </td style="vertical-align:top">
          <td style="vertical-align:top">
            <b style="display:inline">
              Authors:</b>
          </td>
          <td>
            <div style='margin-top: 1ex;'>
<?
echo "<div style='display: inline; margin-top: 1px;' >";
  for( $i = 0 ; $i < count($authors) ; $i +=1 ){
     if( $i != 0)
      echo "<br />"; 
    echo "<input type='text' name='authors[]'  size='20' value='".$authors[$i]."'/>";
  }
  
if(!count($authors)){
  echo "<input type='text' name='authors[]'  size='20' />";
}
echo "<span id='moreAuthors'>&nbsp;</span></div>";
  echo "
          <div style='display: inline; margin-top: 1ex;' id='moreAuthors_link'>
                    <a href='javascript:addNewAuthor();'>Add another author</a>
          </div>";
              ?>
              <br />
          </td>
      </tr>
      <tr>
        <td style="vertical-align:top">
          <b>*</b>          &nbsp;
        </td>
        <td style="vertical-align:top">
          <b style="display:inline">
            Keywords:  </b>
          <br />          (Ex: Keyword; Keyword; ...)
        </td>
        <td>
<?
echo "<div style='display: inline; margin-top: 1px;' >";
          
  for( $i = 0 ; $i < count($keywords) ; $i +=1 ){
    if( $i != 0)
      echo "<br />"; 
    echo "<input type='text' name='keywords[]'  size='20' value='".$keywords[$i]."'/>";
  }
  if(!count($keywords)){
     echo "<input type='text' name='keywords[]'  size='20' />";
  
  }
  
  echo "<span id='moreKeywords'>&nbsp;</span></div>";
  
  
  echo "
          <div style='display:inline;margin-top: 1px;' id='moreKeywords_link'>
          <a href='#;' onclick='addNewKeyWord(); return false;'>Add another keyword</a>
          </div>        
        
        </div>";
          ?>
          <br />
          <br />
        </td>
      </tr>
      <tr>
        <td>
        </td>
        <td style="vertical-align:top">
          <b style="display:inline">
            Category: </b>
        </td>
        <td>
          <!-- <div name="selCats" -->
          <div id="texto" style="width:300px; max-height:300px; overflow: auto;">
<?
                if (count(wpup_getCats())>0){
                  wpup_print_cats_scroll($var, $addId);
                }
                else{
                  echo "No categories created ";
                }
                
            ?>
          </div>
          <br />
        </td>
      </tr>
<?
      
      if(class_exists("userGroups")) {
      
      ?>
      <tr>
        <td>
        </td>
        <td style="vertical-align:top">
          <b style="display:inline">
            Groups: </b>
        </td>
        <td>
<?
      $userGroups = new userGroups();
      $plugName = "wp_uploader";
      $results = $userGroups->ugGetGroupsOfPlugin($plugName, $id);
      $i = 0;
      foreach($results as $result){
        $avGroups[$i] = $result->id;
        $i+=1;
      }
      $groups = $userGroups->getGroups(true);
                //$groups
      if($groups){
        if(!isset($avGroups))
          $avGroups = array();
        wpup_groups($avGroups);
      }
      else{
        echo "     No groups created ";
      }
          ?>
        </td>
      </tr>
<?
      }
      ?>
      <tr>
        <td>
        </td>
        <td>
          <b style="display:inline">
            File to upload:</b>
        </td>
        <td>
          <br />
          <input type="file" value="<? echo $file; ?>" name="file" size="60" />
          <br />          (only use this option if you want to upload and replace the older version)
        </td>
      </tr>
    </table>
    <div class="submit" align=right>
      <input type="submit" name="value" value="<?php _e('Update', 'update') ?>"/>
      <input type="submit" name="cancel" value="<?php _e('Cancel', 'cancel') ?>" />
    </div>
  </form>
</div>
<?
}
if($action != 'edit'){
$valuePages = get_option('wp-publications-archive-number-of-files-per-page');
?>
<div class="wrap" id="numberOfFiles">
  <h2>Files per Page </h2>
  <form name="numberOfFile" id="numberOfFile" action="?page=wp-publications-archive/manageFiles.php&amp;mode=update" method="post">
    <table>
      <tr>
        <td>          Select the number of files to display per page:          &nbsp; &nbsp;
        </td>
        <td>
          <select name="numberOfFiles" >
            <option value="10"  <? if( $valuePages == 10 ) echo "SELECTED" ?> >            10
            </option>
            <option value="25" <? if( $valuePages == 25 ) echo "SELECTED" ?> >            25
            </option>
            <option value="50" <? if( $valuePages == 50 ) echo "SELECTED" ?> >            50
            </option>
          </select>
        </td>
      </tr>
    </table>
    <div class="submit" align=right>
      <input type="submit" name="value" value="<?php _e('Update', 'update') ?>"/>
    </div>
  </form>
</div>
<?
}
?>
