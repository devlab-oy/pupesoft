<?php

//kayttaja on syottanyt tietonsa login formiin
if (isset($_REQUEST["user"]) and $_REQUEST["user"] != '') {

	$login    = "yes";
	$extranet = 1;
	$_GET["no_css"] = "yes";

	require("parametrit.inc");

	$session = "";
	srand ((double) microtime() * 1000000);

	$query = "	SELECT kuka.kuka, kuka.session, kuka.salasana
				FROM kuka
				JOIN asiakas ON (asiakas.yhtio = kuka.yhtio AND asiakas.tunnus = kuka.oletus_asiakas AND asiakas.laji != 'P')
				WHERE kuka.kuka = '$user'
				AND kuka.extranet != ''
				AND kuka.oletus_asiakas != ''";
	$result = mysql_query($query) or pupe_error($query);
	$krow = mysql_fetch_array($result);

	if ($salamd5!='')
		$vertaa=$salamd5;
	elseif ($salasana == '')
		$vertaa=$salasana;
	else
		$vertaa = md5(trim($salasana));

	if ((mysql_num_rows ($result) > 0) and ($vertaa == $krow['salasana'])) {

		// Onko monta sopivaa k�ytt�j�tietuetta == samalla henkil�ll� monta yrityst�!
		if (mysql_num_rows($result) > 1) {
			$usea = 1;
		}

		// Kaikki ok!
		if ($err != 1) {
			// Pit��k� viel� kysy� yrityst�???
			if (($usea != 1) or (strlen($yhtio) > 0)) {
				for ($i=0; $i<25; $i++) {
					$session = $session . chr(rand(65,90)) ;
				}

				$query = "	UPDATE kuka
							SET session = '$session',
							lastlogin = now()
							WHERE kuka = '$user' and extranet != '' and oletus_asiakas != ''";
				if (strlen($yhtio) > 0) {
					$query .= " and yhtio = '$yhtio'";
				}
				$result = mysql_query($query) or pupe_error($query);

				$bool = setcookie("pupesoft_session", $session, time()+43200, parse_url($palvelin, PHP_URL_PATH));

				if ($location != "") {
					echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$location'>";
				}
				elseif (file_exists(basename($_SERVER["SCRIPT_URI"]))) {
					echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$palvelin2?go=".basename($_SERVER["SCRIPT_URI"])."'>";
				}
				else {
					echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$palvelin2'>";
				}

				exit;
			}
		}
	}
	else {
		$errormsg = "<br><font class='error'>".t("K�ytt�j�tunnusta ei l�ydy ja/tai",$browkieli)."<br>".t("Salasana on virheellinen",$browkieli)."!</font><br>";

		// Kirjataan ep�onnistunut kirjautuminen virhelokiin...
		error_log ("user $user: authentication failure for \"/pupesoft/\": Password Mismatch", 0);
	}
}
else {
	require_once("parametrit.inc");
}

$formi = "login"; // Kursorin ohjaus
$kentta = "user";

if (!headers_sent()) {
	header("Content-Type: text/html; charset=iso-8859-1");
	header("Pragma: public");
	header("Expires: 0");
	header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\"\n\"http://www.w3.org/TR/html4/frameset.dtd\">";
}

echo "
<html>
	<head>
	<title>Login</title>";

	if (file_exists("pics/pupeicon.gif")) {
	    echo "\n<link rel='shortcut icon' href='pics/pupeicon.gif'>\n";
	}
	else {
	    echo "\n<link rel='shortcut icon' href='".$palvelin2."devlab-shortcut.png'>\n";
	}

	echo "<meta http-equiv='Pragma' content='no-cache'>
	<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>";
	if (isset($google_analytics)) {
		if ($_REQUEST["google_analytics"] == '') {
			echo $google_analytics;
		}
	}
	echo "</head>

<style type='text/css'>
<!--
	A				{color: #c0c0c0; text-decoration:none;}
	A:hover			{color: #ff0000; text-decoration:none;}
	IMG				{padding:10pt;}
	BODY			{background:#fff;}
	FONT.info		{font-size:8pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #c0c0c0;}
	FONT.head		{font-size:15pt; font-family:Lucida,Verdana,Helvetica,Arial; color: #666699; font-weight:bold; letter-spacing: .05em;}
	FONT.menu		{font-size:10pt; font-family:Lucida,Verdana,Helvetica,Arial; color: #666;}
	FONT.error		{font-size:9pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #ff6666;}
	TD				{padding:3pt;}
	TABLE.login		{padding:7pt; border-width: 1px 1px 1px 1px; /* top right bottom left */ border-style: solid; border-color: #a0a0a0; vertical-align: top; background: #eee; -moz-border-radius: 10pt; -webkit-border-radius: 10pt;}
	INPUT			{font-size:10pt;}
-->
</style>

<table width='550' border='0'>
<tr>
<td valign='top'><br>";

if (file_exists("pics/pupesoft_logo.jpg")) {
	echo "<a target='_top' href='/'><img src='pics/pupesoft_logo.jpg' border='0'>";
}
elseif (file_exists("pics/pupesoft_logo.gif")) {
	echo "<a target='_top' href='/'><img src='pics/pupesoft_logo.gif' border='0'>";
}
else {
	echo "<a target='_top' href='/'><img src='http://www.pupesoft.com/pupesoft.gif' border='0'>";
}

echo "</a></td>
<td>
<font class='head'>".t("Sis��nkirjautuminen",$browkieli)."</font><br><br>
";

if (isset($usea) and $usea == 1) {
	$query = "	SELECT yhtio.nimi, yhtio.yhtio
				FROM kuka, yhtio
				WHERE kuka='$user'
				and yhtio.yhtio=kuka.yhtio";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "".t("Sinulle l�ytyi monta k�ytt�j�tunnusta, muttei yht��n yrityst�")."!";
		exit;
	}

	echo "<table class='login'>";
	echo "<tr><td colspan='2'><font class='menu'>".t("Valitse yritys").":</font></td></tr>";
	echo "<tr>";

	while ($yrow=mysql_fetch_array ($result)) {
		for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
			echo "<td><font class='menu'>$yrow[$i]</font></td>";
		}
		echo "<form action = 'login_extranet.php' method='post'>";
		echo "<input type='hidden' name='go'       value='$go'>";
		echo "<input type='hidden' name='location' value='$location'>";
		echo "<input type='hidden' name='user'     value='$user'>";
		echo "<input type='hidden' name='salamd5'  value='$vertaa'>";
		echo "<input type='hidden' name='yhtio'    value='$yrow[yhtio]'>";
		echo "<td><input type='submit' value='".t("Valitse")."'></td></tr></form>";
	}
	echo "</table>";

	echo "$errormsg<br>";
	echo "<font class='info'>Copyright &copy; 2002-".date("Y")." <a href='http://www.pupesoft.com/'>pupesoft.com</a> - <a href='license.php'>Licence Agreement</a></font>";
}
else {

	//	Ei tehd� framesetti� jos hyp�t��n suoraan muualle
	if ($location != "") {
		$target = "login_extranet.php";
	}
	else {
		$target = "index.php";
	}

	echo "
			<table class='login'>
				<form name='login' target='_top' action='$target' method='post'>
				<input type='hidden' name='go' value='$go'>
				<input type='hidden' name='location' value='$location'>
				<tr><td><font class='menu'>".t("K�ytt�j�tunnus",$browkieli).":</font></td><td><input type='text' value='' name='user' size='15' maxlength='30'></td></tr>
				<tr><td><font class='menu'>".t("Salasana",$browkieli).":</font></td><td><input type='password' name='salasana' size='15' maxlength='30'></td></tr>
			</table>
			$errormsg
			<br><input type='submit' value='".t("Kirjaudu sis��n",$browkieli)."'>
			<br><br>
			<font class='info'>Copyright &copy; 2002-".date("Y")." <a href='http://www.pupesoft.com/'>pupesoft.com</a> - <a href='license.php'>Licence Agreement</a></font>
			</form>
	";
}
echo "</td></tr></table>";

echo "<script LANGUAGE='JavaScript'>
window.document.$formi.$kentta.focus();
</script>";

echo "</body></html>";

?>