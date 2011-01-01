<?php
function wpup_posts_nav_link($sep=' &nbsp; ', $prelabel='&laquo; Previous Page', $nxtlabel='Next Page &raquo;') {

	global $request, $posts_per_page, $wpdb, $max_num_pages;
	
  if ( !is_single() ) {

		if ( 'posts' == get_query_var('what_to_show') ) {
	
  		if ( !isset($max_num_pages) ) {
			
			  $request = explode("LIMIT", $request);

				$numposts = count($wpdb->get_results($request[0]));
				
				$max_num_pages = ceil($numposts / $posts_per_page);
			}
	
  	}
    
    else{
    
			$max_num_pages = 999999;
		
    }

		if ( $max_num_pages > 1 ) {
			
      previous_posts_link($prelabel);
			
      echo preg_replace('/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $sep);
			
      next_posts_link($nxtlabel, $max_page);
		
    }

	}

}


function wpup_next_posts_link($label='Next Page &raquo;', $max_page=0) {
	
  global $paged, $result, $request, $posts_per_page, $wpdb, $max_num_pages;
	
  if ( !$max_page ) {
	
  		if ( isset($max_num_pages) ) {
	
  			$max_page = $max_num_pages; 
	
  		} else {
	
  			$numposts = count($wpdb->get_var($request));
	
  			echo $numposts;
	
  			$max_page = $max_num_pages = ceil($numposts / $posts_per_page);
	
  		}
	
  }
	if ( !$paged ){
	
  	$paged = 1;
	
  }
	
  $nextpage = intval($paged) + 1;
	
  if ( (! is_single()) && (empty($paged) || $nextpage <= $max_page) ) {
	
  	echo '<a href="';
	
  	next_posts($max_page);
	
  	echo '">'. preg_replace('/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $label) .'</a>';
	
  }
  
}
?>
