<?php

//* Tהmה skripti kהyttהה slave-tietokantapalvelinta *//
$useslave = 1;

if (file_exists("inc/connect.inc")) {
	require ("inc/connect.inc");
}
else {
	require ("connect.inc");
}

$id = (int) $_GET["id"];

$query = "	SELECT *
			from liitetiedostot
			where tunnus = '$id'
			and liitos in ('kalenteri','tuote','sarjanumeron_lisatiedot','yllapito')";
$liiteres = mysql_query($query) or die(mysql_error());

if (mysql_num_rows($liiteres) > 0) {
	$liiterow = mysql_fetch_assoc($liiteres);

	header("Content-type: $liiterow[filetype]");
	header("Content-length: $liiterow[filesize]");
	header("Content-Disposition: inline; filename=$liiterow[filename]");
	header("Content-Description: $liiterow[selite]");

	echo $liiterow["data"];
}

?>