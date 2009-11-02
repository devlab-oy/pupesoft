<?php

require ("inc/parametrit.inc");

$go = $goso = '';

if (isset($_REQUEST['go'])) {
	$go = $_REQUEST['go'];
}

if (isset($_REQUEST['goso'])) {
	$goso = $_REQUEST['goso'];
}

if ($go == '') {
	$go = 'tervetuloa.php';
	$goso = '';
}

$colwidth = '175';

if ($kukarow['resoluutio'] == 'P') {
	$colwidth = '45';
}

echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html>
<head>
<title>$yhtiorow[nimi]</title>
";

if (file_exists("pics/pupeicon.gif")) {
	echo "<link rel='shortcut icon' href='pics/pupeicon.gif'>\n";
}
else {
	echo "<link rel='shortcut icon' href='http://www.devlab.fi/devlab-shortcut.png'>\n";
}

echo "<meta http-equiv='Pragma' content='no-cache'>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
</head>
<noscript>
Your browser does not support Javascript!
</noscript>
<frameset cols='$colwidth,*' border='0' frameborder='no'>
<frameset rows='*,0' border='0' frameborder='no'>
<frame noresize src='indexvas.php?goso=$goso' name='menu'>
<frame noresize src='' name='alamenu' id='alamenuFrame'>
</frameset>
<frame src='$go' name='main'>
<noframes>Your browser does not support frames!</noframes>
</frameset>
</html>";

?>