<script type="text/javascript">

function AskConfirm(id, name){
  var message= 'You are about to delete the \''+name+'\' category, are you ready to continue?';
  if (confirm(message)){
    document.location.href="?page=wp-publications-archive/manageCategories.php&action=delete&cat=" + id;
  }
}
</script>
<?php

/*
  This file creates the Categories tab
*/


global $wpdb, $table_prefix;

include_once("uploader_functions.php");
  
$action = $_REQUEST['action'];

if($action == 'delete'){

  $results = wpup_getDocCat();

  $id = (int) $_GET['cat'];
 
  if(!wpup_hasChilds($id)){ 

    $CatDocs =  wpup_getCatDocs($id);
    
    $delete = "";

    $edit = "";
    
    foreach($CatDocs as $CatDoc){

      $aux2 = wpup_getDocCatById($CatDoc->id1);
      
      if(count($aux2)>1){

        $delete .= $CatDoc->id1;

        $delete .= ";";

      }
      else{

        $edit .= $CatDoc->id1;

        $edit .= ";";

      } 

    }
    
    //Delete docCat entry
    $delete = explode(";", $delete);

    for ($i = 0 ; $i < count($delete)-1 ; $i+=1  ){

      $wpdb->query("DELETE FROM ".$table_prefix."docCat
                        WHERE id1 = ".$delete[$i]." AND id2 = ".$id." ;");

    }
    
    //Edit category
    $edit = explode(";", $edit);

    for ( $i = 0 ; $i <count($edit)-1 ; $i+=1){

      $wpdb->query("UPDATE ".$table_prefix."docCat 
                    SET id2 = '-1'
                    WHERE id1 = ".$edit[$i].";");

    }
    
    //Delete from DB
    $wpdb->query("DELETE FROM ".$table_prefix."category

                  WHERE id = ".$id.";"); 
  }

  else{

    $MessageError = 'Category not deleted. You can not delete categories with childs.';

  }

}

if($_REQUEST["mode"]=='update' && $_POST['title']!="" &&
          $_POST['parent']!="" && !$_POST['cancel'] == 'cancel'){
  
  
  if($_POST['description'] == ""){
   
    $desC = "";
  
  }
  else{
   
    $desC = wpup_unhtmlentities($_POST['description']);
  
  }   
  
  $created = false;
  
  $cats = wpup_getCats();

       
  $wpdb->query("UPDATE ".$table_prefix."category 
              SET title = '".$_POST['title']."', description = '".$desC."',
                  parent = ".$_POST['parent']."
              WHERE id =  ".$_POST['id'].";");

  $action = "";

  $Message = 'Category was updated successfully.';
}

else

  if ( $_POST['cancel'] == 'Cancel')

    $Message = 'Edit canceled.';

else

  if($_REQUEST["mode"]=='update' && ($_POST['catName']!="" && $_POST['parent']!="")){

    if( $_POST['catDesc']=="" )
      $description = "";

    else
      $description = wpup_unhtmlentities($_POST['catDesc']);
      
    $id = wpup_getNextFreeId('category');

    $ititle = wpup_unhtmlentities($_POST['catName']);
  
    $parent= (int) $_POST['parent'];

    // get all the existing categories
    $cats = wpup_getCats();

    $created = false;
  
    //validation of the name for the category that will be created, it will only create the category if the name doesn't exist
    foreach($cats as $cat){

      if(!$created){

        if( (strcmp($cat->title, $ititle) == 0) && $cat->parent == $parent )
            $created = true;

        else
          $created = false;

      }

    }
    if($created){

      $MessageError = 'Category already exists!';

    }

    if(!$created){

      $wpdb->query("INSERT INTO ".$table_prefix."category (id, title, description, parent)
                VALUES ('".$id."', '".$ititle."', '".$description."','".$parent."');");

      $Message = 'Category created successfully.';

      $description = "";

      $created = true;

    }

  }

  else

    if($_REQUEST["mode"]=='update' && $_POST['create'] == 'Create'){

      $etitle = $_POST['catName'];

      $description = $_POST['catDesc'];

      $MessageErrorTab1 = 'Category was not created. Please fill all the fields marked with *.';

    }
    
    else
    
      if($action == 'delete')
    
        if ( !$MessageError )
    
          $Message = 'Category deleted successfully.';
      
if($_REQUEST["mode"]=='update' && ($_POST['title']=="" || $_POST['parent']=="") && $_POST['value'] == 'Update'){
  
  $MessageErrorTab = 'Category was not updated. Please fill all the fields marked with *.';

  $action = "edit";

  $id = $_POST['id'];

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
?>
<div class="wrap">
  <h2>    Manage Categories
  <?
if($action != 'edit'){ ?> (<a href="#addcat">add new</a>) <? } ?></h2>
<?
  $results =  wpup_getCats();

  $rec = count($results);
    
  if( $rec > 0 ) { ?>
  <table >
    <tr class="thead">
      <th style="width: 325px">        Name
      </th>
      <th style="width: 325px">        Description
      </th>
      <th style="width: 100px">
      </th>
      <th style="width: 100px">
      </th>
    </tr>
<?
  
    $alt=true;
  
    foreach($results as $result){
    
    if($result->parent == -1 && $result->id >= -1){
    
      $tab = 0;
    
      if($alt){
    
        echo "<tr class='alternate'>";
    
      }else{
    
        echo "<tr>";
    
      }
    
    $alt = !$alt;
    
    echo "<td>".$result->title;
    
    echo "</td><td>";
    
    echo $result->description;
    
    echo "</td><td>";
    
    echo "<a href='?page=wp-publications-archive/manageCategories.php&amp;action=edit&amp;cat=".$result->id."#edit' class='edit'>Edit</a>";
    
    echo "</td><td>";
    
    echo "<a href='#' onclick=\"javascript:AskConfirm(".$result->id.",'".$result->title."');\" class=\"delete\">Delete</a>";
    
    echo "</td></tr>";
    
    $alt = wpup_printChilds($result->id, $tab, $alt);
    
    }
  }
  ?>
  </table>
<?
}
else echo "<b>There are no categories to display.</b>"; 
  ?>
  <hr />
  <p>
    <strong>Note:</strong>
    <br />    Deleting a category does not delete files from that category, it will just set them back to the default category
    <strong>Uncategorized</strong>.
  </p>  
</div>
<?
if($action == 'edit'){ 
  if (!isset($id))

  $id = (int) $_GET['cat'];
    
  $result = wpup_getCat($id);

  $etitle = $result->title;

  $description = $result->description;

  $parent = $result->parent;

  if ($MessageErrorTab){

    echo "<div id='messageError' class='error fade'>";

    echo "<p>".$MessageErrorTab."</p>";

    echo "</div>";

  }
 
?>
<div class="wrap" id="edit">
  <h2>Edit Category</h2>
  <form name="editcat" id="editcat" action="?page=wp-publications-archive/manageCategories.php&amp;mode=update#messageError" method="post">
    <input name="id" type="hidden" value="<? echo $id; ?>" />
    <table>
      <tr>
        <td style="vertical-align:top">
          <b>*</b>          &nbsp;
        </td>
        <td>
          <b style="display:inline">
            Name: </b>
        </td>
        <td>
          <input name="title" value="<? echo $etitle; ?>" type="text" maxlength="40" style="width: 470px" />
        </td>
      </tr>
      <tr>
        <td>
        </td>
        <td>
          <b style="display:inline">
            Description: </b>
        </td>
        <td>
<textarea name="description"  id="description" style="width: 470px; wrap:on" ><? echo $description; ?></textarea>
        </td>
      </tr>
      <tr>
        <td style="vertical-align:top">
          <b>*</b>          &nbsp;
        </td>
        <td>
          <b style="display:inline">
            Parent: </b>
        </td>
        <td>                    
          <select name="parent">
            <option value='-1'>            (none)
            </option>
<?
  wpup_parents($parent, false, $id);
            ?>
          </select>
        </td>
      </tr>
    </table>
    <div class="submit" align=right>
      <p class="submit">
        <input type="submit" name="value" value="<?php _e('Update', 'update') ?>"/>
        <input type="submit" name="cancel" value="<?php _e('Cancel', 'cancel') ?>" />
      </p>
    </div>
  </form>
</div>
<?
}
if($action != 'edit'){ 
  if ($MessageErrorTab1){
    echo "<div id='messageError' class='error fade'>";
    echo "<p>".$MessageErrorTab1."</p>";
    echo "</div>";
  }
?>
<div class="wrap" id="addcat">
  <h2>Create New Category</h2>
  <form name="createcat" id="createcat" action="?page=wp-publications-archive/manageCategories.php&amp;mode=update#messageError" method="post">
    <table>
      <tr>
        <td style="vertical-align:top">
          <b>*</b>          &nbsp;
        </td>
        <td style="vertical-align:top">
          <b style="display:inline">
            Name: </b>
        </td>
        <td>
          <input name="catName" id="catName" value="<? echo $etitle; ?>" type="text" maxlength="40" style="width: 470px" />
        </td>
        <tr>
          <td>
          </td>
          <td style="vertical-align:top">
            <b style="display:inline">
              Description: </b>
          </td>
          <td>
<textarea name="catDesc" id="catDesc" style="width: 470px;"><? echo $description; ?></textarea>
          </td>
        </tr>
        <tr>
          <td style="vertical-align:top">
            <b>*</b>            &nbsp;
          </td>
          <td style="vertical-align:top">
            <b style="display:inline">
              Parent: </b>
          </td>
          <td>
            <select name="parent">
              <option value='-1'>              (none)
              </option>
<?
  wpup_parents();
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <td class="submit" colspan=2 align=right>
        </tr>
    </table>
    <div class="submit" align=right>
      <input type="submit" name="create" value="<?php _e('Create', 'create') ?>"/>
    </div>
  </form>
</div>
<?
}
?>
