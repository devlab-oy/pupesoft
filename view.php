<?php

//* Tהmה skripti kהyttהה slave-tietokantapalvelinta *//
$useslave = 1;

if (file_exists("inc/connect.inc")) {
	require ("inc/connect.inc");
}
else {
	require ("connect.inc");
}

$session = mysql_real_escape_string($_COOKIE["pupesoft_session"]);

$query = "	SELECT *
			FROM kuka
			WHERE session = '$session'";
$result = mysql_query($query) or die(mysql_error());
$kuka_check_row = mysql_fetch_assoc($result);

if (mysql_num_rows($result) != 1) {
	exit;
}

$id = (int) $_GET["id"];

$query = "SELECT * FROM liitetiedostot where tunnus = '$id'";
$liiteres = mysql_query($query) or die(mysql_error());
$liiterow = mysql_fetch_assoc($liiteres);

if ($kuka_check_row['yhtio'] != $liiterow['yhtio'] and $liiterow['liitos'] != 'kalenteri') {
	exit;
}

if (mysql_num_rows($liiteres) > 0) {
	header("Content-type: $liiterow[filetype]");
	header("Content-length: $liiterow[filesize]");
	header("Content-Disposition: inline; filename=$liiterow[filename]");
	header("Content-Description: $liiterow[selite]");

	echo $liiterow["data"];
}

?>