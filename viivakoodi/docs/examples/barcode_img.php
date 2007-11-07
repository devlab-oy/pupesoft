<?php

require_once("Image/Barcode.php");

$num = isset($_REQUEST['num']) ? $_REQUEST['num'] : '15101967';
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'int25';
$imgtype = isset($_REQUEST['imgtype']) ? $_REQUEST['imgtype'] : 'png';

Image_Barcode::draw($num, $type, $imgtype);

?>
