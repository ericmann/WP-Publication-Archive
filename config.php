<script type="text/javascript">

function AskConfirm(id, name){
  var message= 'You are about to delete the \''+name+'\' type, are you ready to continue?';
  if (confirm(message)){
    document.location.href='?page=wp-publications-archive/config.php&action=delete&type=' + id;
  }
}
</script>
<?php
/*
  This file creates the extensions tab
*/

global $wpdb, $table_prefix;

require_once(ABSPATH . 'wp-admin/upgrade-functions.php'); 

include_once("uploader_functions.php");

//get the action
$action = $_REQUEST['action'];

if($_REQUEST["mode"]=='update' && $_POST['name']!="" && $_POST['value'] == 'Update'){

  $id = $_POST['id'];

  $results = wpup_getTypes();

  $exists = false;
  
  $type = wpup_upGetType($id);
  
  if( $_POST['extension'] )
    $string = ", extName = '".$_POST['extension']."'";
  
  if(basename($_FILES['file']['name'])){

    $uploaddir = ABSPATH.'wp-content\\plugins\\wp-publications-archive\\icons';

    $uploaddir2 = '\\wp-content\\plugins\\wp-publications-archive\\icons';

    $uploaddir = uploader_fix_windows_path($uploaddir);

    $uploaddir2 = uploader_fix_windows_path($uploaddir2);

    $uploaddir .= "/";

    $uploaddir2 .= "/";
   
    $uploadbasefile = basename($_FILES['file']['name']);
    
    $exte = explode(".", $uploadbasefile );
    
    $fileExtension = ".".$exte[count($exte)-1];
  	
  	$exte[count($exte)-1] = "";
  	
  	$uploadbasefile = implode($exte);
    
    $uploadfile = $uploaddir . $uploadbasefile . $id . $fileExtension; 
    
    $uploadfile2 = $uploaddir2 .  $uploadbasefile . $id . $fileExtension; 
  
    foreach($results as $res){

      if(!$exists){

        if( $res->pathToIcon == $uploadfile ){

          $exists = true;

        }

      }

    }
    
    if(!$exists){

      $path = ABSPATH.$type->pathToIcon;

      @unlink($path);
      
      if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {}

    }

    else{

      echo "href='?page=wp-publications-archive/config.php&amp;action=edit&amp;type=".$id."#editFileType'";

      $Message = 'Icon already exists, but file extension updated.';

    }
    
    $wpdb->query("UPDATE ".$table_prefix."type 
                    SET name = '".$_POST['name']."', pathToIcon = '".$uploadfile2."' ". $string."
                    WHERE id =  ".$_POST['id'].";");
    
    $Message = 'Extension was updated successfully.';
    
  }

  else{

    $wpdb->query("UPDATE ".$table_prefix."type 
                  SET name = '".$_POST['name']."' ".$string."
                  WHERE id =  ".$_POST['id'].";");

    $Message = 'Extension was updated successfully.';

  }

}

if($_REQUEST["mode"]=='update' && $_POST['create'] == 'Create' && $_POST['name']!="" ){

  $id = wpup_getNextFreeId('type');
  
  if(basename($_FILES['file']['name'])){

    $uploaddir = ABSPATH.'wp-content\\plugins\\wp-publications-archive\\icons';

    $uploaddir2 = '\\wp-content\\plugins\\wp-publications-archive\\icons';

    $uploaddir = uploader_fix_windows_path($uploaddir);

    $uploaddir2 = uploader_fix_windows_path($uploaddir2);

    $uploaddir .= "/";

    $uploaddir2 .= "/";

    $uploadbasefile = basename($_FILES['file']['name']);

    $exte = explode(".", $uploadbasefile );
    
    $fileExtension = ".".$exte[count($exte)-1];
  	
  	$exte[count($exte)-1] = "";
  	
  	$uploadbasefile = implode($exte);
    
    $uploadfile = $uploaddir . $uploadbasefile . $id . $fileExtension; 
    
    $uploadfile2 = $uploaddir2 .  $uploadbasefile . $id . $fileExtension; 
    
  }

  else{

    $uploadfile2= "";

  }

  $id = wpup_getNextFreeId('type');
  
  //check if the extension already exists
  $exists = $wpdb->get_results("SELECT *
                                FROM ".$table_prefix."type
                                WHERE extName LIKE '".$_POST['extension']."';");
  $exists = count($exists);

  if( $exists > 0 ){
      
    $name = $_POST['name'];

    $extension = $_POST['extension'];
    
    if (  $_POST['extension'] != "" ){
     
      $MessageErrorTab1 = 'File Extension already exists.';

    }
    else{
    
      $MessageErrorTab1 = 'Empty File Extension already exists.';
    
    }

  }

  else{

    if ($uploadfile2 != ""){

      if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
       
      $wpdb->query("INSERT INTO ".$table_prefix."type (id, name, pathToIcon, extName)
                      VALUES ('".$id."', '".$_POST['name']."', '".$uploadfile2."','".$_POST['extension']."');");

        $Message = 'File Extension was created successfully.';

      }

    }

    if( $uploadfile2 == "" ){

    $wpdb->query("INSERT INTO ".$table_prefix."type (id, name, pathToIcon, extName)
                      VALUES ('".$id."', '".$_POST['name']."', '".$uploadfile2."','".$_POST['extension']."');");

       $Message = 'File Extension was created successfully.';

    }

  }

}

else{

  if(($_REQUEST["mode"]=='update' && $_POST['create'] == 'Create') && ($_POST['name']=="") ){ //thanks to brian.m.turnbull

    $name = $_POST['name'];

    $extension = $_POST['extension'];

    $MessageErrorTab1 = "Extension was not created. Please fill all fields marked with *.";
  
  }

}

// DELETE
if($action == 'delete'){

  $id = (int) $_GET['type'];

  $type = wpup_upGetType($id);
  
  if(count(wpup_getDocsByFile($id))){

    $MessageError = 'You can not delete this File Extension because it has one or more files associated.';

  }

  else{

    $wpdb->query("DELETE FROM ".$table_prefix."type
                  WHERE id = ".$id.";");

    $string = ABSPATH;

    $string .= $type->pathToIcon;
    
    @unlink($string);

    $Message = 'Extension successfully deleted.';

  }
  
}

// EDIT
if($action == 'edit'){

  $id = (int) $_GET['type'];

  $type = getType($id);

}

if($_REQUEST["mode"]=='update' && $_POST['name']=="" && $_POST['value'] == 'Update'){ //thanks to brian.m.turnbull

    $MessageErrorTab = 'Extension not updated. Please fill all the fields marked with *.';

    $action = "edit";

    $id = $_POST['id'];

}

if($_REQUEST["cancel"]=='Cancel')
  $Message = "Edit canceled.";
  
  

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

$types = wpup_getTypes();


?>


<div class="wrap">
  <h2>    Config File Extensions 
<?
if($action != 'edit'){ ?>
  (<a href="#addFileExt">add new</a>)
    <? } ?>
    
    </h2>
    
  <?
  if(count($types) > 0){
  ?>  
  <table>
    <tr class="thead">
      <th style="width: 400px">        Name
      </th>
      <th style="width: 125px">        Extension
      </th>
      <th style="width: 125px">        Icon
      </th>
      <th style="width: 100px">
      </th>
      <th style="width: 100px">
      </th>
    </tr>
<?
    $alt=true;
  
    $types = wpup_getTypes();
    foreach($types as $type){
      
      if($alt){
        
        echo "<tr class='alternate'>";
      
      }else{
        
        echo "<tr>";
      }
    
      $alt = !$alt;
      
      echo "<td>";
      
      echo $type->name;
    
      echo "</td>";
    
      echo "<td style='text-align:center;'>";
    
      if ( $type->extName != "" ){
      
        echo $type->extName;    
      
      }
      else{
        
        echo "(no extension)";
      
      }
    
      echo "</td>";    
    
      $typest = $type->pathToIcon;
      
      $string = get_bloginfo('url');
      
      $string .=$typest;
      
      if($typest != ""){
        
        echo "<td style='text-align:center;'><img alt='".$type->extName."' src='".$string."' /></td>";
      
      }
      else{
        
        echo "<td style='text-align:center;'>(none)</td>";
      
      }
      
      echo "<td>";
      
      echo "<a href='?page=wp-publications-archive/config.php&amp;action=edit&amp;type=".$type->id."#editFileType' class='edit'>Edit</a>";
      
      echo "</td>";
    
      echo "<td>";
      
      echo "<a href='#' onclick=\"javascript:AskConfirm(".$type->id.",'".$type->name."');\" class=\"delete\">Delete</a>";
    
      echo "</td>";
    
      echo "</tr>";  
    }
?>
  </table>
<?
  }
  else echo "<b>There are no file extensions to display.</b>"; 


?>  
  
  <hr /><p>
    <strong>Note:</strong><br />
    It is not possible to delete a File Extension if it has one or more files associated.
   
   
  </p>
</div>

<?

 if($action != 'edit'){ 
  
   if ($MessageErrorTab1){
  
    echo "<div id='messageError' class='error fade'>";
  
    echo "<p>".$MessageErrorTab1."</p>";
  
    echo "</div>";
  
  }
  ?>

<div class="wrap" id="addFileExt">
  <h2>Add New File Extension </h2>
  <form enctype="multipart/form-data" name="editType" id="editType" action="?page=wp-publications-archive/config.php&amp;mode=update#messageError" method="post">
    <input name="id" type="hidden" value="<? echo $id; ?>" />
    <table>
      <tr>
              <td style="vertical-align:top">
          <b>*</b>          &nbsp;
        </td>
        <td style="vertical-align:top">      <b style="display:inline">    Name: </b>
        </td>
        <td>
          <input name="name" id="name" value="<? echo $name; ?>"type="text" maxlength="40" style="width: 200px"/>
        </td>
      </tr>
      <tr>
              <td style="vertical-align:top">
                  &nbsp;
        </td>
        <td style="vertical-align:top">       <b style="display:inline">   Extension: </b>
        </td>
        <td>
          <input name="extension" id="extension" type="text" value="<? echo $extension; ?>" maxlength="40" style="width: 200px"/> (Include only the extension, e.g.: "doc")
        </td>
      </tr>
      <tr>
      <td>
      </td>
        <td style="vertical-align:top">      <b style="display:inline">    Icon to upload: </b>
        </td>
        <td>
          <input type="file" name="file" size="60" />
        </td>
      </tr>
    </table>
    <div class="submit" align=right>
      <input type="submit" name="create" value="<?php _e('Create', 'create') ?>"/>
    </div>
  </form>
</div>
<?
}
if($action == 'edit'){
  
  if(!isset($id))
  
    $id = (int) $_GET['type'];
  
  $type = wpup_upGetType($id);
  
  $name = $type->name;
  
  $ext = $type->extName;
  
  if ($MessageErrorTab){
  
    echo "<div id='messageError' class='error fade'>";
  
    echo "<p>".$MessageErrorTab."</p>";
  
    echo "</div>";
  
  }
  
?>
<div class="wrap" id="editFileType">
  <h2>Edit File Extension </h2>
  <form enctype="multipart/form-data" name="editType" id="editType" action="?page=wp-publications-archive/config.php&amp;mode=update#messageError" method="post">
    <input name="id" type="hidden" value="<? echo $id; ?>" />
    <table>
      <tr>
       <td style="vertical-align:top">
          <b>*</b>          &nbsp;
        </td>
        <td>      <b style="display:inline">    Name: </b>
        </td>
        <td>
          <input name="name" value="<? echo $name; ?>" id="name" type="text" maxlength="40" style="width: 200px"/>
        </td>
      </tr>
      <tr>
       <td style="vertical-align:top">
                    &nbsp;
        </td>
        <td>       <b style="display:inline">   Extension: </b>
        </td>
        <td>
        <?  if (!count(wpup_getDocsByFile($id))) { ?>
          <input name="extension" value="<? echo $ext; ?>" id="extension" type="text" maxlength="40" style="width: 200px"/> (Include only the extension, e.g.: "doc")
        <? }
        else{
        ?>
        <input DISABLED name="extension" value="<? echo $ext; ?>" id="extension" type="text" maxlength="40" style="width: 200px" /> (edit is disabled because it has one or more files associated)
        <? 
        }
        ?>
        
        </td>
      </tr>
      <tr>
       <td style="vertical-align:top">
  
        </td>
        <td>       <b style="display:inline">   Icon to upload: </b>
        </td>
        <td>
          <input type="file" name="file" size="60">
          <br />          (only use this option if you want to upload or replace the older version)
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
?>
