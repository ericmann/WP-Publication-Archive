<?php 
/*
  File responsible to create the front office contents
*/

include_once("uploader_functions.php"); 
 
global $wpdb, $table_prefix;


class wp_uploader_FrontOffice{

  var $step;

  var $url;

  var $link;
  
  /*
  * Constructor of the class
  * Initialization of the variables
  */
  function wp_uploader_FrontOffice(){

    $this->step = get_option('wp-publications-archive-number-of-files-per-page-on-front-office');

    $this->url = get_bloginfo("url");

    $this->link = true;

  }

  
  /*
  * Function responsible to execute the front office querys in all categories except "uncategorized"
  * @param string where containing part of the query string
  * @param string order_final containing part of the query string
  * @param string limits containing part of the query string
  * @return array with the results of the query
  */
  function wpup_getQuery($where, $order_final, $limits){

    global $wpdb, $table_prefix, $user_ID;
  
    if (class_exists("userGroups")){

      $where .= "AND (1=1 ";

      $plugin_name = "wp_uploader";

      $groups = new userGroups();

      $excludes = $groups->ugGetRestrictedResources($user_ID, $plugin_name);

      foreach ($excludes as $exclude){

        $where .= " AND d.id <> ".$exclude;

      }

      $where .= ")";

    }
  
    return $wpdb->get_results("SELECT DISTINCT d.title AS dtitle, d.id as did,d.authors, d.summary, d.keywords, d.fileName, d.date, d.type, t.name, t.pathToIcon 
           FROM ".$table_prefix."type t, ".$table_prefix."document d, ".$table_prefix."category c, ".$table_prefix."docCat dc
           WHERE d.type = t.id AND c.id = dc.id2 AND dc.id1 = d.id ".$where." ".$order_final." ".$limits);
           
  }
    
  /*
  * Function responsible to execute the front office query to category "uncategorized"
  * @param string where containing part of the query string
  * @param string order_final containing part of the query string
  * @param string limits containing part of the query string
  * @return array with the results of the query
  */
  function wpup_getQueryU($where, $order_final, $limits){
  
    global $wpdb, $table_prefix, $user_ID;
  
    if (class_exists("userGroups")){
    
      $where .= "AND (1=1 ";
    
      $plugin_name = "wp_uploader";
    
      $groups = new userGroups();
    
      $excludes = $groups->ugGetRestrictedResources($user_ID, $plugin_name);
    
      foreach ($excludes as $exclude){
    
        $where .= " AND d.id <> ".$exclude;
    
      }
    
      $where .= ")";
    
    }
  
    return $wpdb->get_results("SELECT d.title AS dtitle, d.id as did,d.authors, d.summary, d.keywords, d.fileName, d.date, d.type, t.name, t.pathToIcon 
          FROM ".$table_prefix."type t, ".$table_prefix."document d
          WHERE d.type = t.id ".$where." ".$order_final." ".$limits);
  
  }
  
  /*
  * Function to get the total results of the query to know the values to the calculations
  * @param int category_id with the id of the category
  * @param string where with part of the query clause
  * @return array with the results of the query
  */
  function wpup_getCount($category_id, $where){
  
    global $wpdb, $table_prefix;
    
    if($category_id == 0){
    
      return $wpdb->get_results("SELECT DISTINCT d.id 
              FROM ".$table_prefix."type t, ".$table_prefix."document d 
              WHERE d.type = t.id ".$where);
    }
    
    else
    {
      return $wpdb->get_results("SELECT DISTINCT d.id 
              FROM ".$table_prefix."type t, ".$table_prefix."document d, ".$table_prefix."category c, ".$table_prefix."docCat dc
              WHERE d.type = t.id AND c.id = dc.id2 AND dc.id1 = d.id ".$where);
    }
  }
  
  /*
  * Function to get the documents in a child category of the selected category
  * @param int with the category id
  * @param array with results of previous querys
  * @return array with the results of the query
  */
  function wpup_filhos($category_id, $results){
    
    global $wpdb, $table_prefix, $user_ID;
  
    if (class_exists("userGroups")){
    
      $where .= "AND (1=1 ";
    
      $plugin_name = "wp_uploader";
    
      $groups = new userGroups();
    
      $excludes = $groups->ugGetRestrictedResources($user_ID, $plugin_name);
    
      foreach ($excludes as $exclude){
    
        $where .= " AND id <> ".$exclude;
    
      }
    
      $where .= ")";
    
    }
  
    $ids =  $wpdb->get_results("SELECT id
                FROM ".$table_prefix."category
                WHERE ".$category_id." = parent ".$where.";");
    
    $results = array_merge($results, $ids);
  
    foreach($ids as $id){
      
      $results = $this->wpup_filhos($id->id, $results);
    
    }
    
    return $results;
  
  }
  
  /*
  * Function to create the URLs 
  * @param string $string with part of the URL to be created
  * @return string $string with the created URL
  */
  function create_Url($string){
  
    $url_array = array(); 
  
    parse_str($_SERVER["QUERY_STRING"], $url_array);
  
    unset($url_array['pagesnext']);
  
    unset($url_array['pagesprevious']);
  
    unset($url_array['start']);
  
    unset($url_array['category']);
  
    unset($url_array['order']);
  
    unset($url_array['page']);
  
    unset($url_array['action']);
   
    $url_array_aux = array();
    
    parse_str($string, $url_array_aux);
  
    $url_array = array_merge($url_array, $url_array_aux);
    
    return "?".wp_up_http_build_query($url_array, null , '&amp;');
    
  }

  /*
  * Function to print the contents of the front office
  */
  function get_Content(){
  
    global $wpdb, $table_prefix, $user_ID;

    ob_start();
  
    $action = $_REQUEST['action'];
  
    if(!isset($orderi)){
     
      $orderi = 0;
    
    }
    
    if(!isset($category_id)){
     
      $category_id = 0;
    
    }
    
    $start = 0;
    
    $stop = $this->step;
    
    $call = false;
    
    if( isset($_POST['category']) || isset($_POST['order']) ||  $action == 'jump' ){
    
      $call = true;
      
      if( isset($_POST['category']) ){
       
        $category_id = $_POST['category'];
      
      }
      
      if( isset($_POST['order']) ){
       
        $orderi = $_POST['order'] ;
      
      }
  
      if($action == 'jump' && isset($_GET['page'])){
      
        $aux = (int) $_GET['page'];
      
        $start = ($aux-1) * $this->step;
      
        $stop = $start + $this->step;
      
        $category_id = (int) $_GET['category'];
      
        $orderi = (int) $_GET['order'] ;        
      
      }
          
      if($action == 'jump' && isset($_GET['pagesnext'])){
      
        $start = (int) $_GET['pagesnext'];
      
        $aux = $start/$this->step;
      
        $aux += 1; 
      
        $stop = $start + $this->step;
      
        $category_id = (int) $_GET['category'];
      
        $orderi = (int) $_GET['order'] ;
      
      }
      
      if ($action == 'jump' && isset($_GET['pagesprevious'])){
      
        $stop = (int) $_GET['pagesprevious'];
      
        $start = $stop - $this->step ;
      
        $aux = $start/$this->step;
      
        $aux += 1; 
      
        $category_id = (int) $_GET['category'];
      
        $orderi = (int) $_GET['order'] ;
      
      } 
        
      // ORDER
      // 0 -> Category
      // 1 -> Date
      // 2 -> File Type
      // 3 -> Title
      switch($orderi){
        case 0: $order = "d.date DESC ";
                $orderTxt = "Date";
                break;
        case 1: $order = "d.title ";
                $orderTxt = "Title";
                break;
        case 2: $order = "t.name ";
                $orderTxt = "File Type";
                break;
        default:  $order = "d.date DESC ";
                  $orderTxt = "Date";
                  break;
      }
              
      $order_final = " ORDER BY ".$order; 
      
      $stopLimit = $stop + 1;
      
      $limits = "LIMIT ".$start.", ".$stopLimit.";"; 
    }
       
    if ($category_id == 0){


      if (class_exists("userGroups")){
        $where .= "AND (1=1 ";
        $plugin_name = "wp_uploader";
        $groups = new userGroups();
        $excludes = $groups->ugGetRestrictedResources($user_ID, $plugin_name);
        foreach ($excludes as $exclude){
      
            $where .= " AND d.id <> ".$exclude;
      
        }
        
        $where .= ")";
      
      }  

      $results = $wpdb->get_results("SELECT d.title AS dtitle, d.id as did, t.name, t.pathToIcon, t.name, d.authors, d.summary, d.keywords, d.fileName, d.date, d.type
                  FROM ".$table_prefix."type t, ".$table_prefix."document d
                  WHERE d.type = t.id ".$where." 
                       ".$order_final." ".$limits); 
     
      $res = $this->wpup_getCount($category_id, $where);
    }
    else{
      if ($category_id == -1){
    
        if (class_exists("userGroups")){
          
          $where .= "AND (1=1 ";
          
          $plugin_name = "wp_uploader";
          
          $groups = new userGroups();
          
          $excludes = $groups->ugGetRestrictedResources($user_ID, $plugin_name);
          
          foreach ($excludes as $exclude){
          
            $where .= " AND id <> ".$exclude;
          
          }
          
          $where .= ")";
        
        }  
        
        $results =  $wpdb->get_results("SELECT id FROM ".$table_prefix."document
                                         WHERE id NOT IN (SELECT id1 
                                                          FROM ".$table_prefix."docCat )"
                                                          .$where." ".$limits);      
              
      }
      
      else {
       
        if (class_exists("userGroups")){
      
          $where .= "AND (1=1 ";
      
          $plugin_name = "wp_uploader";
      
          $groups = new userGroups();
      
          $excludes = $groups->ugGetRestrictedResources($user_ID, $plugin_name);
      
          foreach ($excludes as $exclude){
      
            $where .= " AND id <> ".$exclude;
      
          }
      
          $where .= ")";
      
        }
      
        $results =  $wpdb->get_results("SELECT id
                                      FROM ".$table_prefix."category
                                      WHERE ".$category_id." = id ;");
      
        $results = $this->wpup_filhos($category_id, $results);
      
      }
      
          $where = "";
      
          $first = true;
      
          if( $category_id != -1){
      
            foreach($results as $result){
      
              if($first){
      
                $where .= "  AND ( dc.id2 = ";
      
                $where .= $result->id;
      
                $first = false;
      
              }
      
              else{
      
                $where .= " OR dc.id2 = ";
      
                $where .= $result->id;
      
              }
      
            }
      
          }
      
          else{
      
            foreach($results as $result){
      
              if($first){
      
                $where .= " AND ( d.id = ";
      
                $where .= $result->id;
      
                $first = false;
      
              }
      
              else{
      
                $where .= " OR d.id = ";
      
                $where .= $result->id;
      
              }
      
            }
      
          }
      
          if (!$first)
            $where .= ")";
          
          $results = array();
          
          if (class_exists("userGroups")){
      
            $where .= " AND (1=1 ";
      
            $plugin_name = "wp_uploader";
      
            $groups = new userGroups();
      
            $excludes = $groups->ugGetRestrictedResources($user_ID, $plugin_name);
      
            foreach ($excludes as $exclude){
      
              $where .= " AND d.id <> ".$exclude;
      
            }
      
            $where .= ")";
      
          }
  
          if($category_id != -1 && $where != "")
      
            $results = $this->wpup_getQuery($where, $order_final, $limits);
            
          if($category_id == -1 && $where != "")
      
            $results = $this->wpup_getQueryU($where, $order_final, $limits);
          
          $res = $this->wpup_getCount($category_id, $where);
           
          //$results = array_unique($results);
          $ids = array();
          
          $resTemp = array();
          
          //Remove duplicate entries
          foreach($results as $result){
          
          	$pubId = $result->did;
          
          	if(!$ids[$pubId]){
          
          		$ids[$pubId] = true;
          
          		$resTemp[] = $result;
          
          	}
          
          }
          
          unset($ids);
          
          $results = $resTemp;  
      
      }  
?>

  <form id="uploadForm" enctype="multipart/form-data" method="post" action="<? echo $this->create_Url('mode=update'); ?>">
        <table>
          <tr>
            <td>
              <b>Category:</b>
            </td>
            <td>
               <select id="category" name="category">
              
      <?
      if ( $category_id == 0){
       
        echo "<option value='0' SELECTED>All</option>";
      
      }   
      else{
                  
        echo "<option value='0'>All</option>";
      
      }
      
      $hide = true;
      
      wpup_parents($category_id, $hide);
      
      
      $query = "SELECT id FROM ".$table_prefix."document;";
      
      $files = $wpdb->get_results( $query );
      
      $query = "SELECT DISTINCT id1 AS id FROM ".$table_prefix."docCat;";
      
      $filesWcats = $wpdb->get_results( $query );
      
      if(count($files) > count($filesWcats)){
           
      ?>
      <option <? if( $category_id == -1) echo " SELECTED"; ?> value='-1'>                Uncategorized
      </option>
                
      <?
      }
      ?>
          
      </select>
              
            </td>
            <td>
              <b>Sort By: </b>
            </td>
            <td>
              <select id="order" name="order">
                 <option <? if( $orderi == 0) echo " SELECTED"; ?>  value='0'>                Date
                </option>

                 <option <? if( $orderi == 1) echo " SELECTED"; ?> value='1'>                Title
                </option>       


                <option <? if( $orderi == 2) echo " SELECTED"; ?>  value='2'>                File Type
                </option>
              </select>
            </td>
            <td>
              <input type="submit" name="value" value="<?php _e('Go', 'upload') ?>"/>
            </td>
          </tr>
        </table>
      </form>
<?
    
      $count = 0;
    
      if(!count($results)){   
       
         echo "There are no documents available under this category.";
      
      }
      else{
      
        $format = get_option('date_format');
      
        foreach($results as $result){
         
          if($count < $this->step ){
            
         ?> 
      <br>
      <div class="publications_title">
        <? 
          echo $result->dtitle;
        ?>
      </div>
      <div class="publications_authors">
      <?
        if ($result->authors != ""){
      ?>
        <span class="authorslist">
      <? 
      $results = explode(";", $result->authors);
      
      for( $i = 0 ; $i < count($results) ; $i+=1){
      
        if($i != 0){
          
          echo "&nbsp;";
        
        }
        
        echo $results[$i];
        
        if($i < count($results)-1){
         
          echo ";";
        
        }
      }
      unset($results);
        
        ?>
      </span>
      <?
      }
      ?>
      <span class="date">
        <? 
      $date = strtotime($result->date);
      
      $dateString = date($format, $date);
      
      echo " (".$dateString.")";
        ?>
      </span>
    </div>
    <div class="publications_download">      Download:
      <a 
      <? 
        
        echo " href='".$this->url."/wp-content/plugins/wp-publications-archive/openfile.php?action=open&amp;file=".$result->did."'";
        ?>
      
       >
       
       <?
       $type = $result->pathToIcon;
      
       if ($type != ""){
       ?>
        <img width="16" height="16" title="<? 
          echo $result->name;
        ?>" alt="<? 
          echo $result->name;
        ?>" src="<? 
           
          $string = get_bloginfo('url');
      
          $string .=$type;
      
          echo $string;
        ?>" border="0">
        
        <? } echo $result->name;
          
          $final_size = wpup_size_hum_read(filesize($result->fileName));
      
          echo " (".$final_size.")";
        ?>
        
        </a>
    </div>
    <?
    if($result->summary != ""){
    ?>
    <div class="publications_summary">
      <span class="title">        Summary: 
      
      
      </span>
      <span class="description">
      <? 
          echo $result->summary;
        ?>
      </span>
    </div>
    <?
    }
    ?>
    <div class="publications_keywords">
      <span class="title">        Keywords:
      
      </span>
      <span class="description">
      
      <? 
      $results = explode(";", $result->keywords);
      
      for( $i = 0 ; $i < count($results) ; $i+=1 ){
      
        if($i != 0){
         
          echo "&nbsp;";
        
        }
        echo $results[$i];
        
        if($i < count($results)-1){
         
          echo ";";
        
        }
      
      }
      
      unset($results);
        ?>       
      </span>
    </div>
    <div class="publications_categories">
      <span class="title">        Categories:
      
      </span>
      <span class="description">
      
      <? 
      $categories =  $wpdb->get_results("SELECT id, title
                                          FROM ".$table_prefix."docCat dc,
                                               ".$table_prefix."category c
                                          WHERE dc.id2= c.id AND
                                                dc.id1 = ".$result->did.";");
    
    $cCount = 0;
    
    if( count($categories) > 0){
    
      foreach($categories as $category){
    
      if ($cCount == 0){
    
         $url = $this->create_Url("action=jump&pagesnext=0&category=".$category->id."&order=0");
            ?>
            <a class="button" <?php echo("href='".$url."'"); ?> >        <?php echo $category->title; $cCount+=1; ?>    </a> 
            <?
            }
            else{
              echo ",";
              $url = $this->create_Url("action=jump&pagesnext=0&category=".$category->id."&order=0");      
  ?>
            <a class="button" <?php echo("href='".$url."'"); ?> >        <?php echo $category->title;?>    </a> 
            
            <?
            
            }
          }
        }
        else{
          $url = $this->create_Url("action=jump&pagesnext=0&category=-1&order=0");
            ?>
            <a class="button" <?php echo("href='".$url."'"); ?> >    Uncategorized    </a> 
        
          <?       
 
        }
        ?>       
      </span>
    </div>
<?
        }
    
        $count = $count + 1;
    
      }
    
    }
    ?>    
    <br />
    <br />
    <br />
    <?
    
    if($start > 0){

      $urlp = $this->create_Url("action=jump&pagesprevious=".$start."&category=".$category_id."&order=".$orderi);
   ?>
              <a class="button" <?php echo("href='".$urlp."'"); ?> >            &laquo;  Previous</a> 
    
    <?
    }
    else{
      $this->link = false; 
    }
     
    $val = 0;
    
    $res = count($res);
    
    if($res/$this->step < 1){
     
      $one = true;
    
    }
    else{
    
      $one = false;
    
    }
  
  if( $res % $this->step != 0 ){
    
    while( $res > 0  ){
    
      $res = $res - $this->step;
    
      $val +=1;
    
    }
  
  }
  else{
  
    $val = $res/$this->step;

  }
  
  $toPrint = 0;

  if(!$count > $this->step){
  
    $this->link = false;  
  
  }
  for($i = 1 ; $i <= $val && !$one ; $i+=1 ){
  
    if(($start <= 0 && $i == 1)||
       ($count <= $this->step && $i == $val) || 
       ($aux == $i)){

      echo $i;

      $this->link = true;
  
    }
  
    else{
  
    $url = "";
    
    $urlm = $this->create_Url("action=jump&page=".$i."&category=".$category_id."&order=".$orderi);
      ?>
             <a class="button" <?php echo("href='".$urlm."'"); ?> ><? echo $i; ?></a> 
    
    <?
    
    }
  }
    if($count > $this->step){
    
      $urln = $this->create_Url("action=jump&pagesnext=".$stop."&category=".$category_id."&order=".$orderi);
 ?>

              <a class="button" <?php echo("href='".$urln."'")  ; ?> >             Next &raquo;</a> 
  
              
<?
    }
    $contents = ob_get_contents();
    
    ob_end_clean();
    
    return $contents;
  }
}
?>
