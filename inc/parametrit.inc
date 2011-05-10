<?php

// otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear".PATH_SEPARATOR."/usr/share/php/");
error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
ini_set("display_errors", 0);

$alkumem		= memory_get_usage();
$timeparts		= explode(" ",microtime());
$starttime		= $timeparts[1].substr($timeparts[0],1);
unset($_GET["param_clean"], $_POST["param_clean"], $_REQUEST["param_clean"]);

if (!function_exists("pupe_iconv")) {
	function pupe_iconv(&$item, $key) {
		$item = iconv("UTF-8", "latin1//TRANSLIT", $item);
	}
}

if (!function_exists("pupe_register_globals")) {
	function pupe_register_globals($param_clean) {
		foreach ($param_clean as $key => $value) {

			if (isset($_REQUEST["sourceCharset"]) and $_REQUEST["sourceCharset"] == "UTF-8") {
				if (is_array($param_clean[$key])) {
					array_walk_recursive($param_clean[$key], "pupe_iconv");
				}
				else {
					$param_clean[$key] = iconv("UTF-8", "latin1//TRANSLIT", $param_clean[$key]);
				}
			}

			$param_clean[$key] = $GLOBALS[$key] = str_replace(array("\"","'","<",">","\\", ";"), "", $param_clean[$key]);
		}

		return $param_clean;
	}
}

$_GET		= pupe_register_globals($_GET);
$_POST		= pupe_register_globals($_POST);
$_REQUEST	= pupe_register_globals($_REQUEST);

// Nollataan kaikki muuttujat joita voi setata salasanat.php:ss‰
unset($palvelin);
unset($dbhost);
unset($dbkanta);
unset($dbuser);
unset($dbpass);

unset($dbhostvosa);
unset($dbkantavosa);
unset($dbuservosa);
unset($dbpassvosa);

unset($desadv_host);
unset($desadv_pass);
unset($desadv_path);
unset($desadv_user);

unset($dpd_delisprint_host);
unset($dpd_delisprint_pass);
unset($dpd_delisprint_path);
unset($dpd_delisprint_user);

unset($dpdss);
unset($dpdtun);

unset($edi_ftphost);
unset($edi_ftppass);
unset($edi_ftppath);
unset($edi_ftpuser);

unset($ekirje_config);

unset($elmatuotehost);
unset($elmatuotepass);
unset($elmatuotepath);
unset($elmatuoteuser);

unset($eta_yhtio);
unset($eta_yhtio_email);
unset($eta_yhtio_fax);
unset($eta_yhtio_kotipaikka);
unset($eta_yhtio_maa);
unset($eta_yhtio_nimi);
unset($eta_yhtio_osoite);
unset($eta_yhtio_ovttunnus);
unset($eta_yhtio_postino);
unset($eta_yhtio_postitp);
unset($eta_yhtio_puhelin);
unset($eta_yhtio_ytunnus);

unset($faxhost);
unset($faxpass);
unset($faxpath);
unset($faxuser);

unset($ftpget_dest);
unset($ftpget_host);
unset($ftpget_pass);
unset($ftpget_path);
unset($ftpget_user);

unset($ftpkuvahost);
unset($ftpkuvapass);
unset($ftpkuvapath);
unset($ftpkuvauser);
unset($ftpmuupath);

unset($futurtilvahhost);
unset($futurtilvahpass);
unset($futurtilvahuser);

unset($google_analytics);

unset($infoglove_host);
unset($infoglove_pass);
unset($infoglove_path);
unset($infoglove_user);

unset($koti_yhtio);

unset($kpvm_aikaa);

unset($logisticar);

unset($luettelo_dbhost);
unset($luettelo_dbkanta);
unset($luettelo_dbpass);
unset($luettelo_dbuser);

unset($normaali_size_x);
unset($normaali_size_y);
unset($thumb_size_x);
unset($thumb_size_y);

unset($nrahdemo);

unset($pankkitiedostot_polku);

unset($postiedihost);
unset($postiedipass);
unset($postiedipath);
unset($postiediuser);

unset($sisainenfoinvoice_ftphost);
unset($sisainenfoinvoice_ftppass);
unset($sisainenfoinvoice_ftppath);
unset($sisainenfoinvoice_ftpuser);

unset($slavedb);
unset($slavepass);
unset($slaveuser);

unset($sms_palvelin);
unset($sms_pass);
unset($sms_user);

unset($tilvahhost);
unset($tilvahpass);
unset($tilvahpath);
unset($tilvahuser);

unset($ups_host);
unset($ups_pass);
unset($ups_path);
unset($ups_user);

unset($verkkohost_lah);
unset($verkkopath_lah);

unset($verkkokauppa);
unset($verkkokauppa_anon);
unset($verkkokauppa_hakualkuun);
unset($verkkokauppa_saldotsk);
unset($verkkokauppa_tuotemerkit);
unset($verkkokauppa_hinnatedifailista);
unset($verkkokauppa_sallitutvarastot);

unset($verkkolaskut_out);
unset($verkkolaskut_in);
unset($verkkolaskut_ok);
unset($verkkolaskut_orig);
unset($verkkolaskut_error);
unset($verkkolaskut_reject);

unset($DAY_ARRAY);
unset($AIKA_ARRAY);
unset($MONTH_ARRAY);

unset($VOIPURL);

unset($liitetaanko_editilaus_laskulle_hakemisto);

unset($siirrasuoritustilille);
unset($ohjewiki_url);

$pupe_query_debug = 0;
$aika_debug_array = array();
$quer_debug_array = array();

require("salasanat.php");

$link = @mysql_connect($dbhost, $dbuser, $dbpass) or die ("Tietokantapalvelimeen ei saada yhteytt‰. Se on pois p‰‰lt‰ tai k‰ytt‰j‰tietosi ovat virheelliset.");
mysql_select_db($dbkanta) or die ("Tietokantaa $dbkanta ei lˆydy palvelimelta $dbhost! (parametrit.inc)");

$PHP_SELF = $_SERVER["SCRIPT_NAME"];

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

if (isset($_COOKIE["pupesoft_session"]) and $_COOKIE["pupesoft_session"] != "deleted") {
	$session = $_COOKIE["pupesoft_session"];
}
else {
	$session = "";
}

//Pupeasennuksen root
$pupe_root_polku = dirname(dirname(__FILE__));

$palvelin2 = "http://$apunimi";

//Onko sittenkin https k‰ytˆss‰
if (isset($_SERVER["HTTPS"]) and $_SERVER["HTTPS"] == 'on') {
	$palvelin2 = "https://$apunimi";
}

//Initioidaan pari muuttujaa
if(!isset($login))		$login = "";
if(!isset($extranet)) 	$extranet = "";

// jos saamme n‰m‰ muuttujat getiss‰, kokeillaan logata niill‰ sis‰‰n
if (isset($_GET["user"]) and $_GET["user"] != "" and $_GET["pass"] != "" and $_GET["yhtio"] != "") {

	$_GET["yhtio"] = mysql_real_escape_string($_GET["yhtio"]);
	$_GET["user"]  = mysql_real_escape_string($_GET["user"]);
	$_GET["pass"]  = mysql_real_escape_string($_GET["pass"]);
	$ostoskori     = (int) $_REQUEST["ostoskori"];

	$query = "SELECT * FROM kuka WHERE yhtio='$_GET[yhtio]' AND kuka='$_GET[user]' AND salasana='$_GET[pass]'";
	$result = mysql_query($query) or die ("Sis‰‰nkirjaus ep‰onnistui!");

	if (mysql_num_rows($result) != 1) {
		$query = "SELECT * FROM kuka WHERE yhtio='$_GET[yhtio]' AND kuka='$_GET[user]' AND salasana=md5('$_GET[pass]')";
		$result = mysql_query($query) or die ("Sis‰‰nkirjaus ep‰onnistui!");
	}

	if (mysql_num_rows($result) == 1) {

		$apukukarow = mysql_fetch_assoc($result);

		// jos meill‰ on sessio niin poistetaan se tietokannasta jo aluks
		if ($session != "") {
			$query = "	UPDATE kuka
						SET session = ''
						WHERE session = '" . mysql_real_escape_string($session) . "'";
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

		$bool = setcookie("pupesoft_session", $session, time()+43200, parse_url($palvelin, PHP_URL_PATH)); // 12 tuntia voimassa

		if ($bool === FALSE) {
			die ("Sis‰‰nkirjaus ep‰onnistui!");
		}

		// refreshataan vain jos ei ole ostoskoria mukana
		if ($ostoskori == 0) {
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$palvelin2$phpnimi'>";
			exit;
		}
	}
	else {
		die ("Sis‰‰nkirjaus ep‰onnistui!");
	}
}
// jos sessionia ei ole ja ei olla loginissa, menn‰‰n login ruutuun ja exit...
elseif ($session == '' and $login != 'yes' and (!isset($verkkokauppa) or $verkkokauppa == '')) {

	if ($phpnimi != 'index.php') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$palvelin2'>";
	}
	else {

		//haetaan browserin oletuskieli
		$browkieli = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2);

		if ($browkieli == 'sv') {
			$browkieli = 'se';
		}
		else {
			$browkieli = 'fi';
		}

		// haetaan jotain tarpeellisia funktioita mukaan..
		if (@include_once("inc/functions.inc"));
		elseif (@include_once("functions.inc"));
		else exit;

		if (@include_once("login.php"));
		elseif (@include_once("login_extranet.php"));
	}

	exit;
}

//	Verkkokauppamoodissa ei koskaan ohjetta!
if (isset($verkkokauppa) and $verkkokauppa != "") $_GET["ohje"] = "off";

// jos meill‰ on sessio
if ($session != '' or ((isset($verkkokauppa) and $verkkokauppa != "") and $login != 'yes')) {

	//	Kirjataan verkkokauppak‰ytt‰j‰
	if (isset($verkkokauppa) and $verkkokauppa != "" and $session == "") {
		//	T‰‰ll‰ ei ole mit‰‰n oikeuksia
		$oikeurow = array();
		$kukarow["kuka"] 		= "www";
		$kukarow["nimi"] 		= "Verkkokauppa";
		$kukarow["extranet"] 	= "X";
		$kukarow["yhtio"] 		= $verkkokauppa;
	}
	else {

		$query = "	SELECT *
					FROM kuka
					WHERE session='" . mysql_real_escape_string($session) . "'";
		$result = mysql_query($query) or die ("Kysely ei onnistu kuka $query");

		if (mysql_num_rows($result) != 1 or strlen($session) != 25) {
			if (isset($nayta_pdf) and $nayta_pdf == 1) {
				echo "Your browser does not seem to support cookies.";
				exit;
			}

			$bool = setcookie("pupesoft_session", "", time()-432000, parse_url($palvelin, PHP_URL_PATH));

			if ($bool === FALSE) {
				echo "Your browser does not seem to support cookies.";
			}
			else {
				echo "<script>setTimeout(\"parent.location.href='$palvelin2'\",0);</script>";
			}
			exit;
		}

		$kukarow = mysql_fetch_assoc($result);

		// Unohdetaan tallennettu state jos tullaan suoraan indexsvassista
		if (isset($pupe_DataTables) and $pupe_DataTables != "" and isset($indexvas) and $indexvas == 1) {
			// DataTables cookie name SpryMedia_DataTables_TAULUNID_SKRIPTIN-NIMI
			$dtpalvelin = parse_url($palvelin, PHP_URL_PATH);
			$dtpathparts = pathinfo($phpnimi);

			if ($dtpathparts["dirname"] != "" and $dtpathparts["dirname"] != ".") {
				$dtpalvelin .= $dtpathparts["dirname"]."/";
			}

			$bool = setcookie("SpryMedia_DataTables_".$pupe_DataTables."_".basename($phpnimi), "", time()+43200, $dtpalvelin);
		}

	 	// T‰nne p‰‰see aina!
		if ($phpnimi != 'indexvas.php' and $phpnimi != 'index.php' and $phpnimi != 'tervetuloa.php' and $phpnimi != 'logout.php') {

			// No niin nyt meill‰ on k‰ytt‰j‰, mutta saako h‰n tehd‰ t‰t‰????
			if(isset($toim)) {
				$toim = mysql_real_escape_string($toim);
				// Luetaan alanime‰ vain ekaan &-merkkiin saakka koska joskus kantaan on tallennettu myˆs jotain lis‰tietoja toisessa muuttujassa
				$toimlisa = " and substring(alanimi, 1, if(LOCATE('&',alanimi) > 0, LOCATE('&',alanimi)-1, CHAR_LENGTH(alanimi))) ='$toim'";
			}
			else {
				$toimlisa = "";
			}

			$query = "	SELECT *, substring(alanimi, 1, if(LOCATE('&',alanimi) > 0, LOCATE('&',alanimi)-1, CHAR_LENGTH(alanimi)))
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
					echo "K‰ytt‰j‰ $kukarow[kuka] pyysi toimintoa $phpnimi, joka on kielletty!";
				}
				else {
					echo "K‰ytt‰j‰ $kukarow[kuka] pyysi toimintoa $phpnimi alamenu $toim, joka on kielletty!";
				}
				echo "</td></tr></table>";
				exit;
			}
			$oikeurow = mysql_fetch_assoc($result);
		}
	}

	// haetaan jotain tarpeellisia funktioita mukaan..
	if (@include_once("inc/functions.inc"));
	elseif (@include_once("functions.inc"));
	else exit;

	$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

	if ((isset($nayta_pdf) and $nayta_pdf == 1) or (isset($lataa_tiedosto) and $lataa_tiedosto == 1)) {
		// Kerrotaan selaimelle, ett‰ t‰m‰ on tiedosto
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

		if (isset($nayta_pdf) and $nayta_pdf == 1) {
			header("Content-Type: application/pdf");
			header('Content-Disposition: inline; filename="pupesoft.pdf"');
		}
		else {
			header("Content-Type: application/force-download");

			if ($kaunisnimi != '') {
				header('Content-Disposition: attachment; filename='.basename($kaunisnimi));
			}
			else {
				header('Content-Disposition: attachment; filename=pupesoft.txt');
			}

			if (isset($filenimi) and $filenimi != '') {
				header("Content-Length: ".filesize("$pupe_root_polku/dataout/".basename($filenimi)));
			}
			elseif (isset($tmpfilenimi) and $tmpfilenimi != '') {
				header("Content-Length: ".filesize("/tmp/".basename($tmpfilenimi)));
			}
			elseif (isset($pankkifilenimi) and $pankkifilenimi != '') {

				// Onko maksuaineistoille annettu salasanat.php:ss‰ oma polku jonne tallennetaan
				if (isset($pankkitiedostot_polku) and trim($pankkitiedostot_polku) != "") {

					$pankkitiedostot_polku = trim($pankkitiedostot_polku);

					if (substr($pankkitiedostot_polku, -1) != "/") {
						$pankkitiedostot_polku .= "/";
					}
				}
				else {
					$pankkitiedostot_polku = $pupe_root_polku."/dataout/";
				}

				header("Content-Length: ".filesize($pankkitiedostot_polku.basename($pankkifilenimi)));
			}
			else {
				header("Content-Length: ".strlen($file));
			}
		}
	}
	elseif (($phpnimi != 'index.php' and $phpnimi != 'futursoft.php') or (isset($futuvanha) and $futuvanha == '1')) {

		if ((isset($verkkokauppa) and $verkkokauppa != "") and $yhtiorow['css_verkkokauppa'] != "") {
			$css = $yhtiorow['css_verkkokauppa'];
		}
		elseif ($kukarow["extranet"] != "" and $yhtiorow["css_extranet"] != "") {
			$css = $yhtiorow["css_extranet"];
		}
		elseif ($kukarow['resoluutio'] == 'P' and $yhtiorow['css_pieni'] != "") {
			$css = $yhtiorow['css_pieni'];
		}
		else {
			$css = $yhtiorow['css'];
		}

		if ($kukarow["kieli"] == "ru") {
			$charset = "utf-8";
		}
		else {
			$charset = "iso-8859-1";
		}

		if (!headers_sent()) {
			header("Content-Type: text/html; charset=$charset");
			header("Pragma: public");
			header("Expires: 0");
			header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
		}

		if (!isset($no_head) or (isset($no_head) and $no_head != "yes")) {

			if (isset($verkkokauppa) and $verkkokauppa != "") {
				echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
			}
			else {
				echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"\n\"http://www.w3.org/TR/html4/loose.dtd\">\n";
			}

			echo "<html>\n";
			echo "<head>\n";
			echo "<META Http-Equiv='Cache-Control' Content='no-cache'>\n";
			echo "<META Http-Equiv='Pragma' Content='no-cache'>\n";
			echo "<META Http-Equiv='Expires' Content='-1'>\n";
			echo "<meta http-equiv='Content-Type' content='text/html; charset=$charset'>\n";

			if (!isset($_GET["no_css"]) or $_GET["no_css"] != "yes") {
				echo "<style type='text/css'>\n";
				echo $css;
				echo "</style>\n";

				if (isset($pupe_DataTables) and $pupe_DataTables != "") {
					echo "<style type=\"text/css\" title=\"currentStyle\">\n";
					require("DataTables/media/css/pupe_datatables.php");
					echo "</style>\n";
				}
			}

			echo "<title>\n$yhtiorow[nimi]</title>\n";
			echo "</head>\n";

			echo "<body>\n";
			echo "<noscript>Your browser does not support Javascript!</noscript>\n";

			if (basename($phpnimi) != 'indexvas.php' and (!isset($_GET["ohje"]) or $_GET["ohje"] != "off")) {
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

				if (isset($ohjewiki_url)) {
					$hreffi = $ohjewiki_url."/Ohjeet:".basename($phpnimi).$urltoim;
				}
				else {
					$hreffi = "http://www.devlab.fi/wiki/index.php/Ohjeet:".basename($phpnimi).$urltoim;
				}

				echo "<div style='text-align:right; vertical-align:top;'><a target='_blank' href='$hreffi'>".t("OHJE")."</a></div>";
			}
			elseif(isset($_GET["sulje"]) and $_GET["sulje"] != "") {
				echo "<div><a href='#' onclick=\"divi=document.getElementById('$_GET[sulje]'); document.body.removeChild(divi); return false;\"><font style='color: red;'>x</font></a><br></div>";
			}

			// Autetaan sanakirjan yll‰pidossa
			if ($kukarow["yhtio"] != "" and $kukarow["kuka"] != "") {
				$query = "	SELECT yhtio
							FROM oikeu
							WHERE yhtio	= '$kukarow[yhtio]'
							and kuka	= '$kukarow[kuka]'
							and nimi	= 'sanakirja.php'
							and alanimi = ''";
				$ksres = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($ksres) > 0) $kaannetyt_sanat = array();
			}
		}

		if (isset($tee) and $tee == "message_luettu") {
			$message_tunnus = mysql_real_escape_string($message_tunnus);
			$query = "UPDATE messenger SET status='' WHERE vastaanottaja='$kukarow[kuka]' AND tunnus='$message_tunnus'";
			$result = mysql_query($query) or pupe_error($query);
			exit;
		}

		// Messenger-viestit (X = ei luettu)
		if (basename($phpnimi) != 'indexvas.php' and (!isset($_GET["ohje"]) or $_GET["ohje"] != "off")) {

			$message_query = "	SELECT messenger.tunnus, left(messenger.viesti, 25) kooste, messenger.viesti, kuka.nimi, messenger.luontiaika
								FROM messenger USE INDEX (yhtio_vastaanottaja_status)
								JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = messenger.yhtio AND kuka.kuka = messenger.kuka)
								WHERE messenger.yhtio in ($konserni_yhtiot) AND messenger.vastaanottaja = '$kukarow[kuka]' AND extranet='' AND messenger.status = 'X'";
			if ($message_result = mysql_query($message_query)) {
				if (mysql_num_rows($message_result) > 0) {

					enable_ajax();

					while ($message_row = mysql_fetch_assoc($message_result)) {
						echo "<div id='$message_row[tunnus]'>";
						echo "<input type='button' onClick=\"javascript:sndReq('$message_row[tunnus]','?tee=message_luettu&amp;message_tunnus=$message_row[tunnus]&amp;ohje=off&amp;no_css=yes', false, false)\" value='".t("Kuittaa")."'>";
						echo "<font class='info'> $message_row[nimi] @ ".tv1dateconv($message_row['luontiaika'],'yes').": ";
						echo "<b>$message_row[viesti]</b></font><br>";
						echo "</div>";
					}
				}
			}
		}

		if (isset($lopetus) and $lopetus != '') {
			lopetus($lopetus);
			echo "<br>";
		}
	}

	// luodaan tietokantayhteys uudelleen, niin saadaan slave k‰yttˆˆn jos sellaista tarvitaan
	require("connect.inc");

	if ($yhtiorow["dokumentaatiohallinta"] != "") {
		/*
			tehd‰‰n linkki dokumentaationhallintaan!
			Funktiot voi kutsua suoraan koodista jos dokumentaationhallinta ei ole k‰ytˆss‰ mit‰‰n ei ko funktioilla tapahdu
		*/

		@(include_once("inc/dokumentinhallinta.inc")) or $yhtiorow["dokumentaatiohallinta"] = "";
	}
}

?>