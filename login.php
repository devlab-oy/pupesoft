<?php

if(isset($_POST['user'])) 		$user		= $_POST['user'];
else 							$user		= "";
if(isset($_POST['yhtio'])) 		$yhtio		= $_POST['yhtio'];
else 							$yhtio		= "";
if(isset($_POST['salamd5'])) 	$salamd5	= $_POST['salamd5'];
else 							$salamd5	= "";
if(isset($_POST['salasana'])) 	$salasana	= $_POST['salasana'];
else 							$salasana	= "";
if(isset($_POST['uusi1'])) 		$uusi1		= $_POST['uusi1'];
else 							$uusi1		= "";
if(isset($_POST['uusi2'])) 		$uusi2		= $_POST['uusi2'];
else 							$uusi2		= "";

require ("inc/functions.inc");

if ($user != '') {	//kayttaja on syottanyt tietonsa login formiin

	$login="yes";
	require("inc/parametrit.inc");

	$session = "";
	srand ((double) microtime() * 1000000);

	$query = "	SELECT kuka, session, salasana, yhtio
				FROM kuka
				where kuka='$user'";
	$result = mysql_query($query) or die("Kysely epäonnistui");
	$krow = mysql_fetch_array ($result);

	if($salamd5!='')
		$vertaa=$salamd5;
	elseif($salasana == '')
		$vertaa=$salasana;
	else
		$vertaa = md5(trim($salasana));

	if ((mysql_num_rows ($result) > 0) and ($vertaa == $krow['salasana'])) {

		// jos meillä on vaan kaks yhtiotä ja ollaan tulossa firman vaihdosta, vaihdetaan suoraan toiseen
		if (mysql_num_rows($result) == 2 and $mikayhtio != "") {

			mysql_data_seek($result,0); // ressu alkuun

			while ($vaihdarow = mysql_fetch_array($result)) {

				if ($mikayhtio != $vaihdarow["yhtio"]) {
					$krow = $vaihdarow;
					$yhtio = $vaihdarow["yhtio"];
					$usea = 0;
				}
			}
		}

		// Onko monta sopivaa käyttäjätietuetta == samalla henkilöllä monta yritystä!
		if (mysql_num_rows($result) > 1) {
			$usea = 1;
		}

		if (strlen(trim($uusi1)) > 0)
		{
			if (trim($uusi1) != trim($uusi2))
			{
				$errormsg = t("Uudet salasanasi olivat erilaiset")."! ".t("Salasanaasi ei vaihdettu")."!";
				$err = 1;
				$usea = 0;
			}
			else {
				$uusi1=md5(trim($uusi1));
				$query = "	UPDATE kuka
							SET salasana = '$uusi1'
							WHERE kuka = '$user'";
				$result = mysql_query($query)
					or die ("Salasanapäivitys epäonnistui kuka");

				$vertaa=trim($uusi1);
				$salasana=trim($uusi2);
				$errormsg = t("Salasanasi vaihdettiin onnistuneesti")."!";
			}
		}

		// Kaikki ok!
		if (!isset($err) or $err != 1) {
			// Pitääkö vielä kysyä yritystä???
			if ($usea != 1 or strlen($yhtio) > 0) {
				for ($i=0; $i<25; $i++) {
					$session = $session . chr(rand(65,90)) ;
				}
				$query = "	UPDATE kuka
							SET session = '$session',
							lastlogin = now()
							WHERE kuka = '$user'";
				if (strlen($yhtio) > 0) {
					$query .= " and yhtio = '$yhtio'";
				}
				$result = mysql_query($query) or die ("Päivitys epäonnistui kuka $query");

				$bool = setcookie("pupesoft_session", $session, time()+43200); // 12 tuntia voimassa

				if ($bool === FALSE) {
					$errormsg = t("Selaimesi ei ilmeisesti tue cookieta",$browkieli).".";
				}
				else {
					// katsotaan onko käyttäjällä oletus_ohjelma.. jos on mennään suoraan siihen.
					$query = "select oletus_ohjelma from kuka where session = '$session'";
					$result = mysql_query($query) or die ("Päivitys epäonnistui kuka $query");
					$row = mysql_fetch_array($result);

					if ($row["oletus_ohjelma"] != "") {
						$palvelin2 .= "?go=$row[oletus_ohjelma]";
					}

					echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$palvelin2'>";
					exit;
				}
			}
		}
	}
	else {
		$errormsg = t("Käyttäjätunnusta ei löydy ja/tai salasana on virheellinen",$browkieli)."!";
	}
}
$formi = "login"; // Kursorin ohjaus
$kentta = "user";

echo "
<html>
	<head>
	<title>Login</title>
	<link rel='shortcut icon' href='http://www.pupesoft.com/pupeicon.gif'>
	<meta http-equiv='Pragma' content='no-cache'>
	<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
	</head>

<style type='text/css'>
<!--
	A				{color: #c0c0c0; text-decoration:none;}
	A:hover			{color: #ff0000; text-decoration:none;}
	BODY			{background:#fff;}
	FONT.info		{font-size:8pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #c0c0c0;}
	FONT.head		{font-size:15pt; font-family:Lucida,Verdana,Helvetica,Arial; color: #666699; font-weight:bold; letter-spacing: .05em;}
	FONT.menu		{font-size:9pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #666;}
	FONT.error		{font-size:9pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #ff6666;}
	TD				{padding:5px;}
	TABLE.login		{border-width: 1px 1px 1px 1px; /* top right bottom left */ border-style: solid; border-color: #000; vertical-align: top; background: #eee;}
-->
</style>

<table width='550' border='0'>
<tr>
<td valign='top'><br><a target='_top' href='/'><img src='http://www.pupesoft.com/pupesoft.gif' border='0'></a></td>
<td>

<font class='head'>".t("Sisäänkirjautuminen",$browkieli)."</font><br><br>

";

if (isset($usea) and $usea == 1) {
	$query = "	SELECT yhtio.nimi, yhtio.yhtio
				FROM kuka, yhtio
				WHERE kuka='$user' and yhtio.yhtio=kuka.yhtio 
				ORDER BY nimi";
	$result = mysql_query($query)
		or die ("Kysely ei onnistu $query");

	if (mysql_num_rows($result) == 0) {
		echo t("Sinulle löytyi monta käyttäjätunnusta, muttei yhtään yritystä",$browkieli)."!";
		exit;
	}

	echo "<table class='login'>";
	echo "<tr><td colspan='2'><font class='menu'>".t("Valitse käsiteltävä yritys",$browkieli).":</font></td></tr>";
	echo "<tr>";

	while ($yrow=mysql_fetch_array ($result)) {
		for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
			echo "<td><font class='menu'>$yrow[$i]</font></td>";
		}
		echo "<form action = 'login.php' method='post'>";
		
		if (isset($errormsg)) {
			echo "<input type='hidden' name='errormsg' value='$errormsg'>";
		}
		
		echo "<input type='hidden' name='user'     value='$user'>";
		echo "<input type='hidden' name='salamd5' value='$vertaa'>";
		echo "<input type='hidden' name='yhtio'    value='$yrow[yhtio]'>";
		echo "<td><input type='submit' value='".t("Valitse")."'></td></tr></form>";
	}
	echo "</table><br>";

	if (isset($errormsg) and $errormsg != "") {
		echo "<font class='error'>$errormsg</font><br><br>";
	}
	echo "<font class='info'>Copyright &copy; 2002-".date("Y")." <a href='http://www.pupesoft.com/'>pupesoft.com</a> - <a href='license.php'>Licence Agreement</a></font>";
}
else {

	echo "<table class='login'>
			<form name='login' target='_top' action='index.php' method='post'>

			<tr><td><font class='menu'>".t("Käyttäjätunnus",$browkieli).":</font></td><td><input type='text' value='' name='user' size='15' maxlength='30'></td></tr>
			<tr><td><font class='menu'>".t("Salasana",$browkieli).":</font></td><td><input type='password' name='salasana' size='15' maxlength='30'></td></tr>

			<tr><td colspan='2'><font class='menu'>".t("Jos haluat vaihtaa salasanasi",$browkieli).",<br>".t("anna se kahteen kertaan alla olevin kenttiin",$browkieli)."</font></td></tr>

			<tr><td><font class='menu'>".t("Uusi salasana",$browkieli).":</font></td><td><input type='password' name='uusi1' size='15' maxlength='30'></td></tr>
			<tr><td><font class='menu'>".t("ja uudestaan",$browkieli).":</font></td><td><input type='password' name='uusi2' size='15' maxlength='30'></td></tr>
		</table>";
	
	if (isset($errormsg) and $errormsg != "") {
			echo "<br><font class='error'>$errormsg</font><br>";
	}
	
	echo "	<br><input type='submit' value='".t("Sisään",$browkieli)."'>
			<br><br>
			<font class='info'>Copyright &copy; 2002-".date("Y")." <a href='http://www.pupesoft.com/'>pupesoft.com</a> - <a href='license.php'>Licence Agreement</a></font>
			</form>";
}

echo "</td></tr></table>";
echo "<script LANGUAGE='JavaScript'>window.document.$formi.$kentta.focus();</script>";
echo "</body></html>";

?>
