---=[ Index ]=------------------------------------------------------------------
1 - General Information
2 - License
3 - Installation
4 - Configuration

---=[ 1 - General Information ]=----------------------------------------
  
  Plugin Name: Upload Files Manager
  
  Description: Simple way to upload and manage files.
  
  Author: Luis Lino, Siemens Networks, S.A.
  
  Version: 1.1 


---=[ 2- License ]=-----------------------------------------------------

This plugin is protected under the GNU General Public License.

---=[ 3 - Installation ]=------------------------------------------------

  After downloading this plugin, extract the directory "wp-publications-archive".

  Go to the Wordpress back-office and activate the plugin. 

  This will create the needed tables in the database and the "publications" folder in your "wp-content\uploads" directory.

------

in the file "search.php" for your current theme, 

where is:
  posts_nav_link(' &minus; ', __('&laquo; Previous Page'), __('Next Page &raquo;'));

must be this:
  if (class_exists('wp_uploader'))
    wpup_posts_nav_link(' ', __('&laquo; Previous Page'), __('Next Page &raquo;'));
  else
    posts_nav_link(' ', __('&laquo; Previous Page'), __('Next Page &raquo;'));

------

where is:
  while (have_posts()) : the_post(); 

must be this:
  while (have_posts()) : the_post(); 
   if ($post->post_type == "doc"){
     $test = new wp_uploader();
     $test->wpup_print_doc($post);
   }
   else{

------

where is:
    endif;
  endwhile;

must be this:
    endif;
  }
  endwhile;

------

also have to put this in the top of the page:
  <?php 
   include_once("wp-content/plugins/wp-publications-archive/wpup_FaceLinks.php"); 
  ?> 



---=[ 4 - Configuration]=-------------------------------------------

  To show publications on the front office you have to create a page and add the following content in the body:
  <!--wp_uploader_FO-->
  Save and publish the page and you are done.
 





  
  
