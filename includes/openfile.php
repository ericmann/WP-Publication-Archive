<?php
if ( ! isset($_GET['file']) )
	die();
	
if ( strpos( $_GET['file'], (isset($_SERVER['HTTPS']) ? 'https|' : 'http|') . $_SERVER['SERVER_NAME'] ) === false )
	die();

require_once('../lib/class.mimetype.php');
$mime = new mimetype();

$fPath = str_replace('http|', 'http://', $_GET['file']);
$fPath = str_replace('https|', 'https://', $fPath);
$fType = $mime->getType( $fPath );
$fName = basename($fPath);

$origname = preg_replace('/_#_#\d*/','',$fName);

$fContent = fetch_content( $fPath );

output_content( $fContent, $origname );

function fetch_content( $url ) {
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_HEADER, 0 );

	ob_start();

	curl_exec( $ch );
	curl_close( $ch );

	$fContent = ob_get_contents();
	
	ob_end_clean();
	
	return $fContent;
}

function output_content( $content, $name ) {
	header( "Expires: Wed, 9 Nov 1983 05:00:00 GMT" );
	header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
	header( "Content-Disposition: attachment; filename=" . $name );
	header( "Content-type: application/octet-stream" );
	header( "Content-Transfer-Encoding: binary" );

	echo $content;
}
?>