<!--
File to create the upload tab
-->
<script type="text/javascript">
  function toggleButton (button) {
  if (!this.clicked) {
if (typeof button.disabled != "undefined"){
button.disabled = !button.disabled;
}
else if (button.clicked) {
button.clicked = false;
button.value = button.oldValue;
}
else {
button.clicked = true;
button.oldValue = button.value;
button.value = 'DISABLED';
}
}
}
</script>
<script type="text/javascript" src="../wp-content/plugins/wp-publications-archive/ajax.js"></script>
<script type="text/javascript" src="../wp-content/plugins/wp-publications-archive/add.js"></script>
<?php
global $wpdb, $table_prefix;

global $selCats;

global $avGroups;

function wpup_has($in){

  for ( $i = 0 ; $i < count($in) ; $i+=1 )

    if( $in[$i] != "")

      return true;

  return false;

}
  
$Message = "";

$title = "";

include_once("uploader_functions.php");
 
$day = date("d");

$month = date("m");

$year = date("Y"); 

  
if($_REQUEST["mode"]=='update'){
  if( (isset( $_POST['title']) && $_POST['title']!="" &&
    @checkdate ( $_POST['month'], $_POST['day'], $_POST['year'] ) &&
    wpup_has( $_POST['authors']) && 
    wpup_has( $_POST['keywords']) &&  
    class_exists("userGroups") && ( $_FILES['file']['name'])) 
    || 
    (isset( $_POST['title']) && $_POST['title']!="" &&
    @checkdate ( $_POST['month'], $_POST['day'], $_POST['year'] ) &&
    wpup_has( $_POST['authors']) &&
    wpup_has( $_POST['keywords']) &&  
    ( $_FILES['file']['name']) &&
    !class_exists("userGroups") ) ){
        
    $iselCats = $_POST['selCats'];
      
    if( class_exists("userGroups") )
      $groups = $_POST['avGroups'];
       
    //NEXT AVAILABLE id TO ATTRIBUTE A FILE
    $id = wpup_getNextFreeId('document'); 
      
    $ititle = wpup_unhtmlentities($_POST['title']);  

    $isummary = wpup_unhtmlentities($_POST['summary']);

    $file = $_POST['file'];

    $uploaddir = ABSPATH.'wp-content\\uploads\\';

    $uploaddir = uploader_fix_windows_path($uploaddir);

    $uploaddir .= "/publications/";

    /* -------- Start block ---------
    This enables to distinguish between having or not extensions
    Thanks to brian.m.turnbull
    */
    $uploadbasefile = basename($_FILES['file']['name']);

    //Prevent '..' hack to get to unauthorized folders
    $uploadbasefile = preg_replace('/\./','_',$uploadbasefile);

    if(($extpos = strrpos($uploadbasefile,'_')) !== false){
     	//Has an extension
     	$typename = substr($uploadbasefile,$extpos+1); //The name for the database  
      $extname = $typename; //The ExtName for the database 
      
      $fileExtension = '.' . $extname; //The extension (with '.') for the filename  
      
      $uploadbasefile = substr($uploadbasefile,0,$extpos);

    }else{

      	//No Extension
      $fileExtension = $extname = ""; 

      $typename = "None";      	

    } 
     //Put the ID in the file name with the extension
    $uploadfile = $uploaddir . $uploadbasefile . "_#_#" . $id . $fileExtension; 
    /* -------- End block --------- */
    
     
    $results = wpup_getDocsOrderByTitle();

    $exists = false;

    $day = $_POST['day'];

    $month = $_POST['month'];

    $year = $_POST['year']; 

    $iauthors = "";

    $ikeywords = "";
      
    $date  = mktime(0, 0, 0, $month  , $day , $year);

    $date = date("Y,m,d",$date);
        
    $iiauthors = $_POST['authors'];

    for($i = 0 ; $i < count($iiauthors) ; $i += 1){

      if( $iiauthors[$i] != ""){

        if($i == 0)

          $iauthors .= $iiauthors[$i];

        else{

          $iauthors .= ";";

          $iauthors .= $iiauthors[$i];

          }

        }

      }

      $iikeywords = $_POST['keywords']; 

      for($i = 0 ; $i < count($iikeywords) ; $i += 1){

        if ($iikeywords[$i] != ""){

          if($i == 0)

            $ikeywords .= $iikeywords[$i];

          else{

            $ikeywords .= ";";

            $ikeywords .= $iikeywords[$i];

          }

        }

      }
        
      foreach($results as $res){

        if(!$exists){

          if( $res->fileName == $uploadfile ){

            $exists = true;

          }

        }

      } 

      $fileName = $uploadfile;

      if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {} 
       
      // use this to discover the file type
      //print_r($type);
        
      //TO GO TO THE db TO GET THE id OF THE GIVEN type
      $typeId = wpup_getTypeByExtName($extname); //thanks to brian.m.turnbull
          
      //In case of unknown file extension, create the file extension and alert the user.                    
      if (!isset($typeId)){
        //create the file extension and continues
        $typeId = wpup_getNextFreeId('type');

        $path = "";
          
        $wpdb->query("INSERT INTO ".$table_prefix."type (id, name, pathToIcon, extName)
                   VALUES ('".$typeId."', '".$typename."', '".$path."','".$extname."');");  //thanks to brian.m.turnbull
                   

        $Message = "Upload successfull. The file extension is unkown (<a href='?page=wp-publications-archive/config.php&action=edit&type=".$typeId."#editFileType'>configure file extension</a>).";

      }
        
      //insert document
      $wpdb->query("INSERT INTO ".$table_prefix."document (id, title, authors, summary, keywords, filename, date, type, downloads)
                         VALUES ('$id','$ititle','$iauthors','$isummary','$ikeywords','$fileName','$date','$typeId', '0');");
              
      //insert docCat
      $size = count($iselCats);

      for ( $i = 0 ; $i < $size ; $i += 1 ){

        if( $iselCats[$i] != 'on'){

          $wpdb->query("INSERT INTO ".$table_prefix."docCat (id1, id2) VALUES ('$id','$iselCats[$i]');");

        }

        else{

          $cats = wpup_available_categories();

          foreach($cats as $cat){

             $idCats = $cat->id;

             if($iselCats[$idCats]=='on')

               $wpdb->query("INSERT INTO ".$table_prefix."docCat (id1, id2) VALUES ('$id','$idCats');");

          }

        }

      }
      
      if(class_exists("userGroups")) {

        $userGroups = new userGroups();

        $plugName = "wp_uploader";

        $permission = "all";

        $description = "";

        $size =  count($groups);

        for( $i = 0; $i < $size ; $i += 1){

          $userGroups->ugSetAccess($groups[$i], $id, $permission, $plugName, $description );

        }

      }

      $iauthors = "";

      $ikeywords = "";

      if($Message == "")

        $Message= "Upload sucessfull.";  

      $day = date("d");

      $month = date("m");

      $year = date("Y"); 

    }

    else{

      if(isset($_POST['title']) && $_POST['title']!="")
        $title = wpup_unhtmlentities($_POST['title']);

      if(isset($_POST['summary']) && $_POST['summary']!="")
        $summary = wpup_unhtmlentities($_POST['summary']);

      if(isset($_POST['selCats']))
        $selCats = $_POST['selCats'];

      if(isset($_POST['avGroups']))
        $avGroups = $_POST['avGroups'];
    
      $var= wpup_available_categories();
        
      $day = $_POST['day'];

      $month = $_POST['month'];

      $year = $_POST['year'];  
        
      $iauthors = $_POST['authors'];

      $ikeywords = $_POST['keywords']; 

      $MessageError= "Upload failed. Please fill all fields marked with *.";

      if (!@checkdate ( $month, $day, $year ))
        $MessageError= "Upload failed. Please fill all fields marked with *, and insert a valid date.";
        
    }   

  }
  
if ($MessageError){

  echo "<div id='message' class='error fade'>";

  echo "<p>".$MessageError."</p>";

  echo "</div>";

}  

if ($Message){

  echo "<div id='message' class='updated fade'>";

  echo "<p>".$Message."</p>";

  echo "</div>";

}
?>
<div class="wrap">
  <h2>Upload</h2>
  <form id="uploadForm" enctype="multipart/form-data" method="post" action="?page=wp-publications-archive/wp-uploadpubs.php&amp;mode=update" onsubmit="toggleButton(getElementById('bt_upload')); return true;">
    <table>
      <tr>
        <td style="vertical-align:top">
          <b>*</b>          &nbsp;
        </td>
        <td style="vertical-align:top">
          <b style="display:inline">
            Title: </b>
        </td>
        <td>
          <input name="title" value="<? echo $title; ?>" id="title" type="text"  style="width: 470px"/>
          <br />
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
          <?php $month =1; ?>
          <select name="month">
            <option <? if($month == 1) echo "SELECTED"; ?> value='01'>January</option>
            <option <? if($month == 2) echo "SELECTED"; ?> value='02'>February</option>
            <option <? if($month == 3) echo "SELECTED"; ?> value='03'>March</option>
            <option <? if($month == 4) echo "SELECTED"; ?> value='04'>April</option>
            <option <? if($month == 5) echo "SELECTED"; ?> value='05'>May</option>
            <option <? if($month == 6) echo "SELECTED"; ?> value='06'>June</option>
            <option <? if($month == 7) echo "SELECTED"; ?> value='07'>July</option>
            <option <? if($month == 8) echo "SELECTED"; ?> value='08'>August</option>
            <option <? if($month == 9) echo "SELECTED"; ?> value='09'>September</option>
            <option <? if($month == 10) echo "SELECTED"; ?> value='10'>October</option>
            <option <? if($month == 11) echo "SELECTED"; ?> value='11'>November</option>
            <option <? if($month == 12) echo "SELECTED"; ?> value='12'>December</option>
          </select> <input name="day" value="<? echo "01"; ?>" id="day" type="text" maxlength="2" style="width: 18 px"/> 
<input name="year" value="<? echo $year; ?>" id="year" type="text" maxlength="4" style="width: 35 px"/> 
        </td>        
      </tr>
      <tr>
        <td>
        </td>
        <td style="vertical-align:top">
          <b  style="display:inline">
            Summary: </b>
          <br />          (Short description)
        </td>
        <td>
<textarea name="summary" id="summary" style="width: 470px; height: 100px; wrap:on"><? echo stripslashes($summary); ?></textarea>
          <br />
        </td>
      </tr>
      <tr>
        <td style="vertical-align:top">
          <b>*</b>          &nbsp;
        </td>
        <td style="vertical-align:top">
          <b style="display:inline">
            Authors: </b>
          <br />
        </td>
        <td>          
<?
echo "<div style='display: inline; margin-top: 1px;' >";

$first = true;

for( $i = 0 ; $i < count($iauthors) ; $i +=1 ){

  if($first){

    echo "<input type='text' name='authors[]'  size='20' value='".$iauthors[$i]."'/>";

    $first = false;

  }
  else
    echo "<br /><input type='text' name='authors[]'  size='20' value='".$iauthors[$i]."'/>";
}
  
if(!count($iauthors)){

  echo "<input type='text' name='authors[]'  size='20' />";

}

echo "<span id='moreAuthors'>&nbsp;</span></div>";

echo "<div style='display: inline; margin-top: 1ex;' id='moreAuthors_link'>
                    <a href='javascript:addNewAuthor();'>Add another author</a>
          </div>";
          ?>
        </td>
      </tr>
      <tr>
        <td style="vertical-align:top">
          <b>*</b>          &nbsp;
        </td>
        <td style="vertical-align:top">
          <b style="display:inline">
            Keywords:  </b>
          <br />
        </td>
        <td>
<?
echo "<div style='display: inline; margin-top: 1px;' >";

$first = true;

for( $i = 0 ; $i < count($ikeywords) ; $i +=1 ){
  
  if($first){
  
    echo "<input type='text' name='keywords[]'  size='20' value='".$ikeywords[$i]."'/>";
  
    $first = false;
  
  }
  
  else
  
    echo "<br /><input type='text' name='keywords[]'  size='20' value='".$ikeywords[$i]."'/>";

}
if(!count($ikeywords)){

   echo "<input type='text' name='keywords[]'  size='20' />";

}
  
echo "<span id='moreKeywords'>&nbsp;</span></div>";

echo "<div style='display:inline;margin-top: 1px;' id='moreKeywords_link'>
          <a href='javascript:addNewKeyWord();'>Add another keyword</a>
        </div>";
          ?>
          <br />
        </td>
      </tr>
      <tr>
        <td>
          <br />
        </td>
      </tr>
      <tr>
        <td>
        </td>
        <td style="vertical-align:top">
          <b style="display:inline">
            Available Categories:</b>
          <br />          (Select one or more)
        </td>
        <td style="vertical-align:top">
          <!--<div  id="texto" name="selCats"-->
          <div id="texto" style="width:300px; max-height:300px; overflow: auto;">
<?
if(count(wpup_getCats())>0){

  global $selCats;

  if(!isset($selCats))
  
    $selCats = array();
  
  wpup_print_cats_scroll($selCats, $addId);
  
}
else{
  
  echo "     No categories created ";
  
}
            ?>
          </div>
        </td>
      </tr>
      <tr>
        <td>
          <br />
        </td>
      </tr>
<?
if(class_exists("userGroups")){
      ?>
      <tr>
        <td>
        </td>
        <td style="vertical-align:top">
          <b style="display:inline">
            Groups:</b>
          <br />          (Restrict viewing to...)
        </td>
        <td style="vertical-align:top">
          <!--<div name="avGroups" -->
          <div id="avGroups" style="width:300px; max-height:300px; overflow: auto;">
<?
  $userGroups = new userGroups();
  
  $groups = $userGroups->getGroups(true);
  
  //$groups
  if($groups){
  
    global $avGroups;
  
    if(!isset($avGroups))
  
      $avGroups = array();
  
      wpup_groups($avGroups);
  
  }
  
  else{
  
    echo "No groups created ";
  
  } 
            ?>
          </div>
        </td>
      </tr>
      <? } ?>
      <tr>
        <td>
          <br />
        </td>
        <tr>
          <td>
            <b>*</b>
          </td>
          <td>
            <b style="display:inline">
              File to upload:</b>
          </td>
          <td>
            <input type="file" value="<? echo $file; ?>" name="file" size="60" />
          </td>
        </tr>
    </table>
    <br />
    <div class="submit" align=right>
      <!--onclick="toggleButton(this)"-->
      <input type="submit" id="bt_upload" name="value" value="<?php _e('Upload', 'upload') ?>" />
      <input type="reset" value="Reset" />
    </div>
  </form>  
</div>
