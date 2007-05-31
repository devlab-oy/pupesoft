<?php

require ("inc/parametrit.inc"); 

if(isset($_POST['go'])) 		$go		= $_POST['go'];
else 							$go		= "";

if ($go == '') {
	if(isset($_GET['go'])) 		$go		= $_GET['go'];
	else 						$go		= "";	
}

$go2 = $go; // ei laiteta tervetuloa.phptä oletukseksi indexvassiin
if ($go == '') $go = 'tervetuloa.php';

$colwidth = '180';

if ($kukarow['resoluutio'] == 'P') {
	$colwidth = '45';
}

echo "<html>
	<head>
	<title>$yhtiorow[nimi]</title>
	<link rel='shortcut icon' href='http://www.pupesoft.com/pupeicon.gif'>
	<meta http-equiv='Pragma' content='no-cache'>
	<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
	</head>

	<frameset cols='$colwidth,*' border='0' frameborder='no'>
		<frame noresize src='indexvas.php?go=$go2' name='menu'>
		<frame src='$go' name='main'>
		<noframes>Your browser does not support frames!</noframes>
	</frameset> 
	</html>"; 

?>
