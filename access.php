<?
  include_once('../../../wp-config.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>
      <? echo get_bloginfo("name");?>
    </title>
    <link rel="stylesheet" 			href="http://141.29.131.193/wp/wp-admin/wp-admin.css" 			type="text/css" />
  <style type="text/css">  
    #info h1{
			text-align: center;
		}
		
		.errorTitle{
			margin: 10px 0px;
			background: #FFEFF7;
			border: 1px solid #c69;
			padding: .5em;
		}
		
		.success{
			margin: 10px 0px;
			background: #CFEBF7;
			border: 1px solid #2580B2;
			padding: .5em;
		}
		
		#info {
			background: #fff; 
			border: 1px solid #a2a2a2; 
			margin: 5em auto; 
			padding: 2em; 
			width: 80%;
			min-width: 35em;
		}
		
		#info ul{
			list-style:disc;
			margin: 0px;
			padding: 0px;
		}
		
		#info ul li {
			display: list-item;
			margin-left: 1.4em;
			text-align: left;
		}
		
		#inlineList ul{
			list-style: none;
			margin: 0px;
			padding: 0px;
		}
		
		#inlineList ul li {
			display: inline;
			margin-right: 1.4em;
			margin-left: 0px;
			text-align: center;
		}
  </style>
  </head>
  <body>
    <div id="info">
      <!--<h1><? //echo get_bloginfo("name"); ?> - wp-publications-archive</h1>-->
      <h1 style="text-align:center;"><? echo get_bloginfo("name");?>
        - Publications Archive</h1>
      <p class="errorTitle">
        Access denied.
      </p>
      
      <p>
        Ensure that you have enough previleges to access this publication. 
      </p>
      <br />
      <br />
      <div id="inlineList">
        <ul>
          <li>
          <a href="<? echo get_bloginfo("url");?>            " title="Return to blog">&laquo; 
            Go to <? echo get_bloginfo("name");?></a></li>
        </ul>
      </div>
    </div>
  </body>
</html>
