<?php

$useslave = 1;

if (file_exists("inc/connect.inc")) {
	require ("inc/connect.inc");
}
else {
	require ("connect.inc");
}

/*
	Diasbloitu rikkoo extranetin
	
//	Tarkastetaan oikeudet
if (isset($_COOKIE["pupesoft_session"])) {
	$session = $_COOKIE["pupesoft_session"];
}
else {
	$session = "";
}

//	Luotetaan että meidän käyttäjät ei feikkaa referer urlia!
list($refererurl)=explode("?",$_SERVER["HTTP_REFERER"]);

if($refererurl!="") {
	$query = "	SELECT oikeu.tunnus
				FROM kuka
				JOIN oikeu ON kuka.yhtio=oikeu.yhtio and oikeu.kuka=kuka.kuka and oikeu.nimi='".basename($refererurl)."'
				WHERE session='$session'";
	$result = mysql_query($query) or die ("Kysely ei onnistu kuka $query");
	if(mysql_num_rows($result) <> 1 and $refererurl != 'indexvas.php' and $refererurl != 'index.php' and $refererurl != 'tervetuloa.php' and $refererurl != 'logout.php') {
		die();
	}
}
else {
	die();
}
*/
$id = (int) $_GET["id"];

$query = "SELECT * FROM liitetiedostot where tunnus = '$id'";
$liiteres = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($liiteres) > 0) {

	$liiterow = mysql_fetch_array($liiteres);

	header("Content-type: $liiterow[filetype]");
	header("Content-length: $liiterow[filesize]");
	header("Content-Disposition: inline; filename=$liiterow[filename]");
	header("Content-Description: $liiterow[selite]");

	echo $liiterow["data"];

}

?>
