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
mysql_select_db ($dbkanta) or die ("Tietokantaa $dbkanta lˆydy palvelimelta!");

// HUOMHUOM!!
$query = "SET SESSION group_concat_max_len = 100000, max_allowed_packet = 2095104";
$result = mysql_query($query) or pupe_error($query);

// jos k‰ytet‰‰n jotain kummallista porttia (ei http eik‰ https)
$portti = "";
if ($_SERVER['SERVER_PORT'] != '80' and $_SERVER['SERVER_PORT'] != '443') {
	$portti = ":".$_SERVER['SERVER_PORT'];
}

// otetaan palvelin muuttujasta mahdolliset turhat alut pois
$alut    = array("http://","https://");
$apunimi = str_replace($alut, "", $palvelin);

$tark    = $_SERVER['SERVER_NAME'].$portti.$_SERVER['SCRIPT_NAME'];
$phpnimi = str_replace($apunimi, "", $tark);

$session = $_COOKIE["pupesoft_session"];

if ($_SERVER["HTTPS"] == 'on') {
	$palvelin2 = "https://$apunimi";
}
else {
	$palvelin2 = "http://$apunimi";
}

//haetaan browserin oletuskieli
$browkieli = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2);
if ($browkieli == 'sv') {
	$browkieli = 'se';
}
elseif ($kieli == 'fi') {
	$browkieli = 'fi';
}
else {
	$browkieli = 'en';
}


// jos saamme n‰m‰ muuttujat getiss‰, kokeillaan logata niill‰ sis‰‰n
if ($_GET["user"] != "" and $_GET["pass"] != "" and $_GET["yhtio"] != "") {

	$query = "select * from kuka where yhtio='$_GET[yhtio]' and kuka='$_GET[user]' and salasana=md5('$_GET[pass]')";
	$result = mysql_query($query) or die ("Sis‰‰nkirjaus ep‰onnistui!");

	if (mysql_num_rows($result) == 1) {

		$apukukarow = mysql_fetch_array($result);

		// jos meill‰ on sessio niin poistetaan se tietokannasta jo aluks
		if ($session != "") {
			$query = "	UPDATE kuka
						SET session = ''
						WHERE session = '$session'";
			$result = mysql_query($query) or die ("P‰ivitys ep‰onnistui sis‰‰nkirjautumisessa");
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
		$result = mysql_query($query) or die ("P‰ivitys ep‰onnistui sis‰‰nkirjautumisessa");

		$query = "	UPDATE kuka
					SET session = ''
					WHERE kuka = '$apukukarow[kuka]'
			 		and yhtio != '$apukukarow[yhtio]'";
		$result = mysql_query($query) or die ("P‰ivitys ep‰onnistui sis‰‰nkirjautumisessa");

		$bool = setcookie("pupesoft_session", $session, time()+43200); // 12 tuntia voimassa

		if ($bool === FALSE) {
			die ("Sis‰‰nkirjaus ep‰onnistui!");
		}
	}
	else {
		die ("Sis‰‰nkirjaus ep‰onnistui!");
	}

}
// jos sessionia ei ole ja ei olla loginissa, menn‰‰n login ruutuun ja exit...
elseif ($session == '' and $login != 'yes' and $extranet == '') {
	require("login.php");
	exit;
}
// jos sessionia ei ole ja ei olla extranet loginissa, menn‰‰n extranet login ruutuun ja exit...
elseif ($session == '' and $login != 'yes' and $extranet == 1) {
	require("login_extranet.php");
	exit;
}

// jos meill‰ on sessio
if ($session != '') {

	$query = "	SELECT *
				FROM kuka
				WHERE session='$session'";
	$result = mysql_query($query) or die ("Kysely ei onnistu kuka");

	if (mysql_num_rows($result) != 1 or strlen($session) != 25) {
		if ($nayta_pdf == 1) {
			echo t("Pyydettiin pdf-tiedostoa, mutta cookie oli viallinen, jokseenkin paha juttu");
			exit;
		}
		$bool = setcookie("pupesoft_session", "", time()-432000);

		if ($bool === FALSE) {
			echo t("Selaimesi ei ilmeisesti tue cookieta").".";
		}
		else {
			echo "<script>setTimeout(\"parent.location.href='$palvelin2'\",0);</script>";
		}
		exit;
	}
	$kukarow = mysql_fetch_array($result);

 	// T‰nne p‰‰see aina!
	if ($phpnimi != 'indexvas.php' and $phpnimi != 'index.php' and $phpnimi != 'tervetuloa.php' and $phpnimi != 'logout.php') {

		// No niin nyt meill‰ on k‰ytt‰j‰, mutta saako h‰n tehd‰ t‰t‰????
		$query = "	SELECT *
					FROM oikeu
					WHERE yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi='$phpnimi' and alanimi='$toim'";
		$result = mysql_query($query)
			or die ("Kysely ei onnistu kuka $query");

		if (mysql_num_rows($result) == 0) {

			echo "<table width='100%' height='70%'>
					<tr><td style='text-align:center;font-size:9pt;font-family:Lucida,Verdana,Helvetica,Arial; color: #666;'>
					<img src='http://www.pupesoft.com/pupesoft.gif'><br><br>";
			if (strlen($toim) == 0) {
				echo "K‰ytt‰j‰ $kukarow[kuka] pyysi toimintoa $phpnimi, joka on kielletty!";
			}
			else {
				echo "K‰ytt‰j‰ $kukarow[kuka] pyysi toimintoa $phpnimi alamenu $toim, joka on kielletty!";
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
		echo "<b>K‰ytt‰j‰n yritys ei lˆydy! ($kukarow[yhtio])";
		exit;
	}
	$yhtiorow = mysql_fetch_array($result);

	$query = "	SELECT *
				FROM yhtion_parametrit
				WHERE yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

	if (mysql_num_rows($result) == 1) {
		$yhtion_parametritrow = mysql_fetch_array($result);
		// lis‰t‰‰n kaikki yhtiorow arrayseen, niin ollaan taaksep‰inyhteensopivia
		foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
			$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
		}
	}

	// haetaan jotain tarpeellisia funktioita mukaan..
	require ("functions.inc");

	// luodaan tietokantayhteys uudelleen, niin saadaan slave k‰yttˆˆn jos sellaista tarvitaan
	require("connect.inc");
	
	if ($nayta_pdf == 1) {
		header("Content-Type: application/pdf");
		header('Content-Disposition: inline; filename="pupesoft.pdf"');
	}
	elseif($lataa_tiedosto == 1) {
		// Kerrotaan selaimelle, ett‰ k‰ytt‰j‰n kuuluu downloadata t‰m‰ tiedosto
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
		
		if ($filenimi != '') {
			header('Content-Disposition: attachment; filename='.$kaunisnimi);		
		}
		else {
			header('Content-Disposition: attachment; filename=pupesoft.txt');	
		}
				
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".filesize("dataout/".$filenimi));
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
		echo "<title>$yhtiorow[nimi] - ".date('Y-m-d H:i:s')."</title>";
		echo "</head>";
		echo "<body>";
		
		if (basename($phpnimi) != 'indexvas.php') {
			if ($toim != '') {
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

			echo "<div align='right'><a class='menu' target='_blank' href='$hreffi'>".t("OHJE")."</a><br></div>";
		}
	}
}
?>
