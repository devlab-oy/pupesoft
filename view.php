<?php

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
$liiteres = mysql_query($query) or pupe_error($query);
$liiterow = mysql_fetch_assoc($liiteres);

if ($kuka_check_row['yhtio'] != $liiterow['yhtio'] and $liiterow['liitos'] != 'kalenteri') {
	exit;
}

if (mysql_num_rows($liiteres) > 0) {

	// Kerrotaan selaimelle, että tämä on tiedosto
	if (ini_get('zlib.output_compression')) {
		ini_set('zlib.output_compression', 'Off');
	}

	header("Pragma: public");
	header("Expires: 0");
	header("HTTP/1.1 200 OK");
	header("Status: 200 OK");
	header("Accept-Ranges: bytes");
	header("Content-Description: File Transfer");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private", false);
	header("Content-Transfer-Encoding: binary");
	
	header("Content-type: $liiterow[filetype]");
	header("Content-length: $liiterow[filesize]");
	header("Content-Disposition: inline; filename=$liiterow[filename]");
    
	echo $liiterow["data"];

}

?>