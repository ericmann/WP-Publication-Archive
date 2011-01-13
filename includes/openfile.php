<?php
require_once('class.mimetype.php');
$mime = new mimetype();

$fPath = $_GET['file'];

$fType = $mime->getType( $fPath );

$fName = basename($fPath);

$origname = preg_replace('/_#_#\d*/','',$fName);

header('Content-type: "'.$fType.'"');

header('Content-Disposition: attachment; filename="'.$origname.'"');

readfile($fPath); 