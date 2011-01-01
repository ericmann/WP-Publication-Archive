<?php

/*
  This file creates the settings tab
*/

global $wpdb, $table_prefix;
require_once(ABSPATH . 'wp-admin/upgrade-functions.php'); 
include_once("uploader_functions.php");

if($_REQUEST["mode"]=='update' && isset ($_POST['numberOfFiles'])){

  $number = $_POST['numberOfFiles'];

  update_option('wp-publications-archive-number-of-files-per-page-on-front-office', $number);

  $Message = "Number of files to display, per page, was updated to $number.";
 
}
    
if ($Message){

  echo "<div id='message' class='updated fade'>";

  echo "<p>".$Message."</p>";

  echo "</div>";

}

$valuePages = get_option('wp-publications-archive-number-of-files-per-page-on-front-office');

$valuePages += 0;

?>
<div class="wrap" id="numberOfFiles">
  <h2>Files per Page on Front Office Search </h2>
  <form name="numberOfFile" id="numberOfFile" action="?page=wp-publications-archive/frontOfficeConfig.php&amp;mode=update" method="post">
    <table>
      <tr>
        <td>          Select the number of files to display per page:          &nbsp; &nbsp;
        </td>
        <td>
          <select name="numberOfFiles">
          
          <option value="5" <? if( $valuePages == 5 ) echo "SELECTED" ?> >
          5
          </option>
          <option value="10" <? if( $valuePages == 10 ) echo "SELECTED" ?> >
          10
          </option>
          <option value="15" <? if( $valuePages == 15 ) echo "SELECTED" ?> >
          15        
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

<div class="wrap" id="howTo">
  <h2>Front Office Page </h2>
 
In order to display the Publications in the front office, create a page <a href='<? echo get_bloginfo("url");?>/wp-admin/page-new.php'>create a page</a>  and and place <input READONLY style="width: 13em" value="&lt;!--wp_uploader_FO--&gt; " /> in the page body (in Code/HTML mode).
</div>
