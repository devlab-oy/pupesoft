<?php

$user     = $_POST['user'];
$yhtio    = $_POST['yhtio'];
$salamd5  = $_POST['salamd5'];
$salasana = $_POST['salasana'];
$uusi1    = $_POST['uusi1'];
$uusi2    = $_POST['uusi2'];

require ("functions.inc");

if ($user != '') {	//kayttaja on syottanyt tietonsa login formiin

	$login    = "yes";
	$extranet = 1;
	require("parametrit.inc");

	$session = "";
	srand ((double) microtime() * 1000000);

	$query = "	SELECT kuka, session, salasana
				FROM kuka
				where kuka = '$user' and extranet != '' and oletus_asiakas != ''";
	$result = mysql_query($query) or die("VIRHE: Kysely ei onnistu!");
	$krow = mysql_fetch_array ($result);

	if($salamd5!='')
		$vertaa=$salamd5;
	elseif($salasana == '')
		$vertaa=$salasana;
	else
		$vertaa = md5(trim($salasana));

	if ((mysql_num_rows ($result) > 0) and ($vertaa == $krow['salasana'])) {

		// Onko monta sopivaa käyttäjätietuetta == samalla henkilöllä monta yritystä!
		if (mysql_num_rows($result) > 1) {
			$usea = 1;
		}

		// Kaikki ok!
		if ($err != 1)
		{
			// Pitääkö vielä kysyä yritystä???
			if (($usea != 1) or (strlen($yhtio) > 0))
			{
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
				$result = mysql_query($query)
					or die ("Päivitys epäonnistui kuka $query");

				$bool = setcookie("pupesoft_session", $session, time()+43200, parse_url($palvelin, PHP_URL_PATH));
				
				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$palvelin2'>";
				exit;
			}
		}
	}
	else {
		$errormsg = "<br><font class='error'>".t("Käyttäjätunnusta ei löydy ja/tai",$browkieli)."<br>".t("Salasana on virheellinen",$browkieli)."!</font><br>";
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

if ($usea=='1') {
	$query = "	SELECT yhtio.nimi, yhtio.yhtio
				FROM kuka, yhtio
				WHERE kuka='$user' and yhtio.yhtio=kuka.yhtio";
	$result = mysql_query($query)
		or die ("Kysely ei onnistu $query");

	if (mysql_num_rows($result) == 0) {
		echo "".t("Sinulle löytyi monta käyttäjätunnusta, muttei yhtään yritystä")."!";
		exit;
	}

	echo "<table class='login'>";
	echo "<tr><td colspan='2'><font class='menu'>".t("Valitse yritys").":</font></td></tr>";
	echo "<tr>";

	while ($yrow=mysql_fetch_array ($result))
	{
		for ($i=0; $i<mysql_num_fields($result)-1; $i++)
		{
			echo "<td><font class='menu'>$yrow[$i]</font></td>";
		}
		echo "<form action = 'login_extranet.php' method='post'>";
		echo "<input type='hidden' name='user'     value='$user'>";
		echo "<input type='hidden' name='salamd5' value='$vertaa'>";
		echo "<input type='hidden' name='yhtio'    value='$yrow[yhtio]'>";
		echo "<td><input type='submit' value='".t("Valitse")."'></td></tr></form>";
	}
	echo "</table>";

	echo "$errormsg<br>";
	echo "<font class='info'>Copyright &copy; 2002-".date("Y")." <a href='http://www.pupesoft.com/'>pupesoft.com</a> - <a href='license.php'>Licence Agreement</a></font>";
}
else {
	echo "
			<table class='login'>
				<form name='login' target='_top' action='index.php' method='post'>

				<tr><td><font class='menu'>".t("Käyttäjätunnus",$browkieli).":</font></td><td><input type='text' value='' name='user' size='15' maxlength='30'></td></tr>
				<tr><td><font class='menu'>".t("Salasana",$browkieli).":</font></td><td><input type='password' name='salasana' size='15' maxlength='30'></td></tr>
			</table>
			$errormsg
			<br><input type='submit' value='".t("Kirjaudu sisään",$browkieli)."'>
			<br><br>
			<font class='info'>Copyright &copy; 2002-".date("Y")." <a href='http://www.pupesoft.com/'>pupesoft.com</a> - <a href='license.php'>Licence Agreement</a></font>
			</form>
	";
}
echo "

</td>
</tr>
</table>
";

echo "<script LANGUAGE='JavaScript'>
window.document.$formi.$kentta.focus();
</script>";

echo "</body></html>";

?>
