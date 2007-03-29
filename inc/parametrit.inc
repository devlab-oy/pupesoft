<?php

// poistetaan hipsuja ja trimmataan kaikki get muuttujat!
$serc = array("\"","'","<",">","\\", ";");
$repl = array("", "", "", "", "", "");

foreach ($_GET as $key => $value) {
	//$_GET[$key] = addslashes(trim($_GET[$key]));
	$_GET[$key] = str_replace($serc, $repl, $_GET[$key]);
	${$key} = str_replace($serc, $repl, $_GET[$key]);
}

// poistetaan hipsuja ja trimmataan kaikki post muuttujat!
foreach ($_POST as $key => $value) {
	//$_POST[$key] = addslashes(trim($_POST[$key]));
	$_POST[$key] = str_replace($serc, $repl, $_POST[$key]);
	${$key} = str_replace($serc, $repl, $_POST[$key]);
}

$timeparts = explode(" ",microtime());
$starttime = $timeparts[1].substr($timeparts[0],1);

// luodaan tietokantayhteys
require("salasanat.php");

$link = mysql_connect ($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
mysql_select_db ($dbkanta) or die ("Tietokantaa $dbkanta löydy palvelimelta!");

// jos käytetään jotain kummallista porttia (ei http eikä https)
$portti = "";
if ($_SERVER['SERVER_PORT'] != '80' and $_SERVER['SERVER_PORT'] != '443') {
	$portti = ":".$_SERVER['SERVER_PORT'];
}

// otetaan palvelin muuttujasta mahdolliset turhat alut pois
$alut    = array("http://","https://");
$apunimi = str_replace($alut, "", $palvelin);

$tark    = $_SERVER['SERVER_NAME'].$portti.$_SERVER['SCRIPT_NAME'];
$phpnimi = str_replace($apunimi, "", $tark);

if (isset($_COOKIE["pupesoft_session"])) {
	$session = $_COOKIE["pupesoft_session"];
}
else {
	$session = "";
}

$palvelin2 = "http://$apunimi";

//Onko sittenkin https käytössä
if (isset($_SERVER["HTTPS"]) and $_SERVER["HTTPS"] == 'on') {
	$palvelin2 = "https://$apunimi";
}

//haetaan browserin oletuskieli
$browkieli = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2);

if ($browkieli == 'sv') {
	$browkieli = 'se';
}
elseif (isset($kieli) and $kieli == 'fi') {
	$browkieli = 'fi';
}
else {
	$browkieli = 'en';
}

//Initioidaan pari muuttujaa
if(!isset($login))		$login = "";
if(!isset($extranet)) 	$extranet = "";


// jos saamme nämä muuttujat getissä, kokeillaan logata niillä sisään
if (isset($_GET["user"]) and $_GET["user"] != "" and $_GET["pass"] != "" and $_GET["yhtio"] != "") {

	$query = "select * from kuka where yhtio='$_GET[yhtio]' and kuka='$_GET[user]' and salasana=md5('$_GET[pass]')";
	$result = mysql_query($query) or die ("Sisäänkirjaus epäonnistui!");

	if (mysql_num_rows($result) == 1) {

		$apukukarow = mysql_fetch_array($result);

		// jos meillä on sessio niin poistetaan se tietokannasta jo aluks
		if ($session != "") {
			$query = "	UPDATE kuka
						SET session = ''
						WHERE session = '$session'";
			$result = mysql_query($query) or die ("Päivitys epäonnistui sisäänkirjautumisessa");
		}

		$session = "";
		for ($i = 0; $i < 25; $i++) {
			$session .= chr(rand(65,90)) ;
		}

		$query = "	UPDATE kuka
					SET session = '$session',
					lastlogin = now()
					WHERE kuka = '$apukukarow[kuka]'
			 		and yhtio = '$apukukarow[yhtio]'";
		$result = mysql_query($query) or die ("Päivitys epäonnistui sisäänkirjautumisessa");

		$query = "	UPDATE kuka
					SET session = ''
					WHERE kuka = '$apukukarow[kuka]'
			 		and yhtio != '$apukukarow[yhtio]'";
		$result = mysql_query($query) or die ("Päivitys epäonnistui sisäänkirjautumisessa");

		$bool = setcookie("pupesoft_session", $session, time()+43200); // 12 tuntia voimassa

		if ($bool === FALSE) {
			die ("Sisäänkirjaus epäonnistui!");
		}
	}
	else {
		die ("Sisäänkirjaus epäonnistui!");
	}

}
// jos sessionia ei ole ja ei olla loginissa, mennään login ruutuun ja exit...
elseif ($session == '' and $login != 'yes' and $extranet == '') {
	require("login.php");
	exit;
}
// jos sessionia ei ole ja ei olla extranet loginissa, mennään extranet login ruutuun ja exit...
elseif ($session == '' and $login != 'yes' and $extranet == 1) {
	require("login_extranet.php");
	exit;
}

// jos meillä on sessio
if ($session != '') {

	$query = "	SELECT *
				FROM kuka
				WHERE session='$session'";
	$result = mysql_query($query) or die ("Kysely ei onnistu kuka $query");

	if (mysql_num_rows($result) != 1 or strlen($session) != 25) {
		if (isset($nayta_pdf) and $nayta_pdf == 1) {
			echo "Your browser does not seem to support cookies.";
			exit;
		}
		$bool = setcookie("pupesoft_session", "", time()-432000);

		if ($bool === FALSE) {
			echo "Your browser does not seem to support cookies.";
		}
		else {
			echo "<script>setTimeout(\"parent.location.href='$palvelin2'\",0);</script>";
		}
		exit;
	}
	$kukarow = mysql_fetch_array($result);

 	// Tänne pääsee aina!
	if ($phpnimi != 'indexvas.php' and $phpnimi != 'index.php' and $phpnimi != 'tervetuloa.php' and $phpnimi != 'logout.php') {

		// No niin nyt meillä on käyttäjä, mutta saako hän tehdä tätä????
		if(isset($toim)) {
			$toimlisa = " and alanimi='$toim'";
		}
		else {
			$toimlisa = "";
		}

		$query = "	SELECT *
					FROM oikeu
					WHERE yhtio = '$kukarow[yhtio]'
					and kuka = '$kukarow[kuka]'
					and nimi = '$phpnimi'
					$toimlisa";
		$result = mysql_query($query) or die ("Kysely ei onnistu kuka $query");

		if (mysql_num_rows($result) == 0) {

			echo "<table width='100%' height='70%'>
					<tr><td style='text-align:center;font-size:9pt;font-family:Lucida,Verdana,Helvetica,Arial; color: #666;'>
					<img src='http://www.pupesoft.com/pupesoft.gif'><br><br>";
			if (strlen($toim) == 0) {
				echo "Käyttäjä $kukarow[kuka] pyysi toimintoa $phpnimi, joka on kielletty!";
			}
			else {
				echo "Käyttäjä $kukarow[kuka] pyysi toimintoa $phpnimi alamenu $toim, joka on kielletty!";
			}
			echo "</td></tr></table>";
			exit;
		}
		$oikeurow = mysql_fetch_array($result);
	}

	$query = "	SELECT *
				FROM yhtio
				WHERE yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

	if (mysql_num_rows($result) == 0) {
		echo "<b>Käyttäjän yritys ei löydy! ($kukarow[yhtio])";
		exit;
	}
	$yhtiorow = mysql_fetch_array($result);

	$query = "	SELECT *
				FROM yhtion_parametrit
				WHERE yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

	if (mysql_num_rows($result) == 1) {
		$yhtion_parametritrow = mysql_fetch_array($result);

		// lisätään kaikki yhtiorow arrayseen
		foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
			$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
		}
	}


	if (isset($kukarow["toimipaikka"]) and $kukarow["toimipaikka"] != 0) {
		$query = "	SELECT *
					FROM yhtion_toimipaikat
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[toimipaikka]'";
		$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

		if (mysql_num_rows($result) == 1) {
			$yhtion_toimipaikkarow = mysql_fetch_array($result);

			// lisätään kaikki yhtiorow arrayseen
			foreach ($yhtion_toimipaikkarow as $parametrit_nimi => $parametrit_arvo) {
				$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
			}
	 	}
	}

	// haetaan jotain tarpeellisia funktioita mukaan..
	require ("functions.inc");

	// luodaan tietokantayhteys uudelleen, niin saadaan slave käyttöön jos sellaista tarvitaan
	require("connect.inc");

	if (isset($nayta_pdf) and $nayta_pdf == 1) {
		header("Content-Type: application/pdf");
		header('Content-Disposition: inline; filename="pupesoft.pdf"');
	}
	elseif(isset($lataa_tiedosto) and $lataa_tiedosto == 1) {
		// Kerrotaan selaimelle, että käyttäjän kuuluu downloadata tämä tiedosto
		// Hardcoodattu polku dataout/

		if(ini_get('zlib.output_compression')) {
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
		header("Content-Type: application/force-download");

		if ($kaunisnimi != '') {
			header('Content-Disposition: attachment; filename='.$kaunisnimi);
		}
		else {
			header('Content-Disposition: attachment; filename=pupesoft.txt');
		}

		header("Content-Transfer-Encoding: binary");

		if (isset($filenimi) and $filenimi != '') {
			header("Content-Length: ".filesize("dataout/".$filenimi));
		}
		elseif (isset($tmpfilenimi) and $tmpfilenimi != '') {
			header("Content-Length: ".filesize("/tmp/".$tmpfilenimi));
		}
		else {
			header("Content-Length: ".strlen($file));
		}
	}
	elseif ($phpnimi != 'index.php') {

		if ($kukarow["extranet"] != "" and $yhtiorow["css_extranet"] != "") {
			$css = $yhtiorow["css_extranet"];
		}
		elseif ($kukarow['resoluutio'] == 'P' and $yhtiorow['css_pieni'] != "") {
			$css = $yhtiorow['css_pieni'];
		}
		else {
			$css = $yhtiorow['css'];
		}

		$charset = "iso-8859-1";
		//$charset = "utf-8";

		if ($kukarow["kieli"] == "ru" or $kukarow["kieli"] == "ee") {
			$charset = "utf-8";
		}

		header("Content-Type: text/html; charset=$charset");

		echo "<html>";
		echo "<head>";
		echo "<META Http-Equiv='Cache-Control' Content='no-cache'>";
		echo "<META Http-Equiv='Pragma' Content='no-cache'>";
		echo "<META Http-Equiv='Expires' Content='0'>";
		echo "<meta http-equiv='Content-Type' content='text/html; charset=$charset'>";
		echo "<style type='text/css'>";
		echo $css;
		echo "</style>";
		echo "<script type='text/javascript' language='javascript' src='$palvelin/inc/pupe.js'></script>";
		echo "<title>$yhtiorow[nimi] - ".date('Y-m-d H:i:s')."</title>";
		echo "</head>";

		if($suljeYllapito!="") {
			$body_js="onblur='window.close();'";
		}
		else {
			$body_js="";
		}
		echo "<body $body_js>";
		
		if (basename($phpnimi) != 'indexvas.php') {
			if (isset($toim) and $toim != '') {
				if (basename($phpnimi) == 'varauskalenteri.php') {
					$urltoim = "";
				}
				else {
					$urltoim = "/".$toim;
				}
			}
			else {
				$urltoim = "";
			}

			if (basename($phpnimi) == 'tervetuloa.php' and $kukarow['extranet'] != '') {
				$urltoim = "/EXTRANET";
			}

			$hreffi = "http://www.pupesoft.com/trac/wiki/$kukarow[kieli]/".basename($phpnimi).$urltoim;

			echo "<div align='right'><a target='_blank' href='$hreffi'>".t("OHJE")."</a><br></div>";
		}
	}
}
?>
