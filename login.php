<?php

//kayttaja on syottanyt tietonsa login formiin
if (isset($_REQUEST["user"]) and $_REQUEST["user"] != '') {

	$login = "yes";
	require("inc/parametrit.inc");

<<<<<<< HEAD
	$session = "";
	$usea 	 = 0;

	srand((double) microtime() * 1000000);

	$query = "	SELECT kuka.kuka, kuka.session, kuka.salasana, kuka.yhtio
				FROM kuka
				JOIN oikeu ON oikeu.yhtio=kuka.yhtio and oikeu.kuka=kuka.kuka
				where kuka.kuka		= '$user'
				and kuka.extranet 	= ''
				GROUP BY 1,2,3,4";
	$result = mysql_query($query) or pupe_error($query);
	$krow = mysql_fetch_array($result);

	if (isset($salamd5) and $salamd5 != '') $vertaa = $salamd5;
	elseif (isset($salasana) and $salasana == '') $vertaa = $salasana;
	else $vertaa = md5(trim($salasana));

	if (mysql_num_rows($result) > 0 and $vertaa == $krow['salasana']) {

		// jos meill‰ on vaan kaks yhtiot‰ ja ollaan tulossa firman vaihdosta, vaihdetaan suoraan toiseen
		if (mysql_num_rows($result) == 2 and isset($mikayhtio) and $mikayhtio != "") {

			mysql_data_seek($result,0); // ressu alkuun

			while ($vaihdarow = mysql_fetch_array($result)) {

				if ($mikayhtio != $vaihdarow["yhtio"]) {
					$krow = $vaihdarow;
					$yhtio = $vaihdarow["yhtio"];
					$usea = 0;
				}
			}
		}

		// Onko monta sopivaa k‰ytt‰j‰tietuetta == samalla henkilˆll‰ monta yrityst‰!
		if (mysql_num_rows($result) > 1) {
			$usea = 1;
		}

		if (isset($uusi1) and strlen(trim($uusi1)) > 0) {
			if (trim($uusi1) != trim($uusi2)) {
				$errormsg = t("Uudet salasanasi olivat erilaiset")."! ".t("Salasanaasi ei vaihdettu")."!";
				$err = 1;
				$usea = 0;
			}
			elseif (strlen(trim($uusi1)) < 6) {
				$errormsg = t("Uusi salasanasi on liian lyhyt").". ".t("Salasanan pit‰‰ olla v‰hint‰‰n 6 merkki‰ pitk‰").". ".t("Salasanaasi ei vaihdettu")."! ";
				$err = 1;
				$usea = 0;
			}
			elseif (stristr($uusi1, $krow["kuka"])) {
				$errormsg = t("Salasanasi ei saa sis‰lt‰‰ k‰ytt‰j‰tunnustasi").". ".t("Salasanaasi ei vaihdettu")."!";
				$err = 1;
				$usea = 0;
			}
			else {
				$uusi1 = md5(trim($uusi1));
				$query = "	UPDATE kuka
							SET salasana = '$uusi1'
							WHERE kuka = '$user'";
				$result = mysql_query($query) or pupe_error($query);

				$vertaa = trim($uusi1);
				$salasana = trim($uusi2);
				$errormsg = t("Salasanasi vaihdettiin onnistuneesti")."!";
			}
		}

		// Kaikki ok!
		if (!isset($err) or $err != 1) {
			// Pit‰‰kˆ viel‰ kysy‰ yrityst‰???
			if ($usea != 1 or (isset($yhtio) and strlen($yhtio) > 0)) {

				for ($i=0; $i<25; $i++) {
					$session = $session . chr(rand(65,90)) ;
				}

				$query = "	UPDATE kuka
							SET session = '$session',
							lastlogin = now()
							WHERE kuka = '$user'";

				if (isset($yhtio) and strlen($yhtio) > 0) $query .= " and yhtio = '$yhtio'";
				else $query .= " and yhtio = '$krow[yhtio]'";

				$result = mysql_query($query) or pupe_error($query);

				$bool = setcookie("pupesoft_session", $session, time()+43200, "/"); // 12 tuntia voimassa

				if ($bool === FALSE) {
					$errormsg = t("Selaimesi ei ilmeisesti tue cookieta",$browkieli).".";
				}
				else {
					// katsotaan onko k‰ytt‰j‰ll‰ oletus_ohjelma.. jos on menn‰‰n suoraan siihen.
					$query = "SELECT oletus_ohjelma from kuka where session = '$session'";
					$result = mysql_query($query) or pupe_error($query);
					$row = mysql_fetch_array($result);

					if ($row["oletus_ohjelma"] != "") {

						$oletus_ohjelman_osat = explode("##", $row["oletus_ohjelma"]);

						$palvelin2 .= "?goso=$oletus_ohjelman_osat[0]&go=$oletus_ohjelman_osat[1]";

						if ($oletus_ohjelman_osat[2] != "") {
							$palvelin2 .= "?toim=$oletus_ohjelman_osat[2]";
						}
					}

					echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$palvelin2'>";
					exit;
				}
			}
		}
	}
	else {
		$errormsg = t("K‰ytt‰j‰tunnusta ei lˆydy ja/tai salasana on virheellinen", $browkieli)."!";

		// Kirjataan ep‰onnistunut kirjautuminen virhelokiin...
		error_log ("user $user: authentication failure for \"/pupesoft/\": Password Mismatch", 0);
	}
=======
	if (!isset($salamd5)) $salamd5 = '';
	if (!isset($mikayhtio)) $mikayhtio = '';
	if (!isset($uusi1)) $uusi1 = '';
	if (!isset($uusi2)) $uusi2 = '';
	if (!isset($yhtio)) $yhtio = '';

	$params = array(
		'user' => $user,
		'salasana' => $salasana,
		'salamd5' => $salamd5,
		'mikayhtio' => $mikayhtio,
		'uusi1' => $uusi1,
		'uusi2' => $uusi2,
		'yhtio' => $yhtio,
		'browkieli' => $browkieli,
		'palvelin' => $palvelin,
		'palvelin2' => $palvelin2
	);

	$return = pupesoft_login($params);
>>>>>>> master
}
else {
	require_once("inc/parametrit.inc");
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
	    echo "\n<link rel='shortcut icon' href='{$palvelin2}devlab-shortcut.png'>\n";
	}

echo "
	<meta http-equiv='Pragma' content='no-cache'>
	<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
	</head>

<style type='text/css'>
<!--
	A				{color: #c0c0c0; text-decoration:none;}
	A:hover			{color: #ff0000; text-decoration:none;}
	IMG				{padding:10pt;}
	BODY			{background:#fff;}
	FONT.info		{font-size:8pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #c0c0c0;}
	FONT.head		{font-size:15pt; font-family:Lucida,Verdana,Helvetica,Arial; color: #666; font-weight:bold; letter-spacing: .05em;}
	FONT.menu		{font-size:10pt; font-family:Lucida,Verdana,Helvetica,Arial; color: #666;}
	FONT.error		{font-size:9pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #ff6666;}
	TD				{padding:3pt;}
	TABLE.login		{padding:7pt; border-width: 1px 1px 1px 1px; /* top right bottom left */ border-style: solid; border-color: #a0a0a0; vertical-align: top; background: #eee; -moz-border-radius: 10pt; -webkit-border-radius: 10pt;}
	INPUT			{font-size:10pt;}
-->
</style>

<table border='0'>
<tr>
<td valign='top'><br>";

if (file_exists("pics/pupesoft_logo.jpg")) {
	echo "<a target='_top' href='{$palvelin2}'><img src='pics/pupesoft_logo.jpg' border='0'>";
}
elseif (file_exists("pics/pupesoft_logo.gif")) {
	echo "<a target='_top' href='{$palvelin2}'><img src='pics/pupesoft_logo.gif' border='0'>";
}
elseif (file_exists("pics/pupesoft_logo.png")) {
	echo "<a target='_top' href='{$palvelin2}'><img src='pics/pupesoft_logo.png' border='0'>";
}
else {
	echo "<a target='_top' href='{$palvelin2}'><img src='{$pupesoft_scheme}api.devlab.fi/pupesoft_large.png' border='0' style='margin-top:30px;'>";
}

echo "</td><td><font class='head'>",t("Sis‰‰nkirjautuminen", $browkieli),"</font><br><br>";

if (isset($return['usea_yhtio']) and $return['usea_yhtio'] == 1) {

	if (count($return['usea']) == 0) {
		echo t("Sinulle lˆytyi monta k‰ytt‰j‰tunnusta, muttei yht‰‰n yrityst‰", $browkieli),"!";
		exit;
	}

	echo "<table class='login'>";
	echo "<tr><td colspan='2'><font class='menu'>",t("Valitse k‰sitelt‰v‰ yritys", $browkieli),":</font></td></tr>";
	echo "<tr>";

	foreach ($return['usea'] as $_yhtio => $_yhtionimi) {

		echo "<td><font class='menu'>{$_yhtionimi}</font></td>";

		echo "<td>";
		echo "<form action = '' method='post'>";

		if (isset($return['error'])) {
			echo "<input type='hidden' name='return[error]' value='{$return['error']}'>";
		}

		echo "<input type='hidden' name='user'     value='{$user}'>";
		echo "<input type='hidden' name='salamd5' value='{$return['vertaa']}'>";
		echo "<input type='hidden' name='yhtio'    value='{$_yhtio}'>";
		echo "<input type='submit' value='",t("Valitse"),"'></form></td></tr>";
	}

	echo "</table><br>";

	if (isset($return['error']) and $return['error'] != "") {
		echo "<font class='error'>{$return['error']}</font><br><br>";
	}
	echo "<font class='info'>Copyright &copy; 2002-",date("Y")," <a href='http://www.devlab.fi/'>Devlab Oy</a> - <a href='license.php'>Licence Agreement</a></font>";
}
else {

	echo "<table class='login'>
			<form name='login' target='_top' action='index.php' method='post'>

			<tr><td><font class='menu'>",t("K‰ytt‰j‰tunnus",$browkieli),":</font></td><td><input type='text' value='' name='user' size='15' maxlength='30'></td></tr>
			<tr><td><font class='menu'>",t("Salasana",$browkieli),":</font></td><td><input type='password' name='salasana' size='15' maxlength='30'></td></tr>

			<tr><td colspan='2'><font class='menu'>",t("Jos haluat vaihtaa salasanasi",$browkieli),",<br>",t("anna se kahteen kertaan alla olevin kenttiin",$browkieli),"</font></td></tr>

			<tr><td><font class='menu'>",t("Uusi salasana",$browkieli),":</font></td><td><input type='password' name='uusi1' size='15' maxlength='30'></td></tr>
			<tr><td><font class='menu'>",t("ja uudestaan",$browkieli),":</font></td><td><input type='password' name='uusi2' size='15' maxlength='30'></td></tr>
		</table>";

	if (isset($return['error']) and $return['error'] != "") {
			echo "<br><font class='error'>{$return['error']}</font><br>";
	}

	echo "	<br><input type='submit' value='",t("Sis‰‰n",$browkieli),"'>
			<br><br>
			<font class='info'>Copyright &copy; 2002-",date("Y")," <a href='http://www.devlab.fi/'>Devlab Oy</a> - <a href='license.php'>Licence Agreement</a></font>
			</form>";

	echo "<script LANGUAGE='JavaScript'>window.document.{$formi}.{$kentta}.focus();</script>";
}

echo "</td></tr></table>";
echo "</body></html>";

?>