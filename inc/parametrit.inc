<?php

if (substr(dirname(__FILE__), -3) == "inc") {
	// otetaan includepath aina rootista
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear".PATH_SEPARATOR."/usr/share/php/");
}
else {
	// otetaan includepath aina rootista
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear".PATH_SEPARATOR."/usr/share/php/");
}

error_reporting(E_ERROR);
ini_set("display_errors", 0);
ini_set("log_errors", 1);

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
unset($palvelin_ip);
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
unset($maksuaineiston_siirto);
unset($pankkiaineiston_haku);

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

unset($teccomkansio);
unset($teccomkansio_valmis);
unset($teccomkansio_error);

unset($DAY_ARRAY);
unset($AIKA_ARRAY);
unset($MONTH_ARRAY);

unset($VOIPURL);

unset($kpexport);

unset($liitetaanko_editilaus_laskulle_hakemisto);

unset($siirrasuoritustilille);
unset($ohjewiki_url);
unset($synkronoi_kiellot);
unset($alv_laskelman_sallittu_erotus);
unset($e3_params);
unset($tee_keraysera_debugdir);

$pupe_query_debug = 0;
$aika_debug_array = array();
$quer_debug_array = array();

require("salasanat.php");

// haetaan jotain tarpeellisia funktioita mukaan..
if (@include_once("inc/functions.inc"));
elseif (@include_once("functions.inc"));
else exit;

$link = @mysql_connect($dbhost, $dbuser, $dbpass) or die ("Tietokantapalvelimeen ei saada yhteytt‰. Se on pois p‰‰lt‰ tai k‰ytt‰j‰tietosi ovat virheelliset.");
mysql_select_db($dbkanta) or die ("Tietokantaa $dbkanta ei lˆydy palvelimelta $dbhost! (parametrit.inc)");
mysql_set_charset("latin1");

// Pupeasennuksen root polku
$pupe_root_polku = dirname(dirname(__FILE__));
$pupesoft_url = parse_url($palvelin);

// Otetaan tietoa t‰st‰ requestist‰
$PHP_SELF = $_SERVER["PHP_SELF"];
$phpnimi = basename($_SERVER["SCRIPT_NAME"]);
$pupesoft_scheme = (isset($_SERVER["HTTPS"]) and $_SERVER["HTTPS"] == 'on') ? "https://" : "http://";
$pupesoft_server = (isset($_SERVER['HTTP_X_FORWARDED_SERVER']) and isset($palvelin_ip) and $palvelin_ip == $_SERVER['SERVER_NAME']) ? $_SERVER['HTTP_X_FORWARDED_SERVER'] : $_SERVER['SERVER_NAME'];
$pupesoft_port = ($_SERVER['SERVER_PORT'] != '80' and $_SERVER['SERVER_PORT'] != '443') ? ":".$_SERVER['SERVER_PORT'] : "";

// $palvelin muuttujassa on Pupesoft root url, joka tulee salasat.php:st‰
// $palvelin2 muuttujassa on Pupesoft root url, mutta scheme/server/portti on muutettu tarpeen mukaan
$palvelin2 = $pupesoft_scheme.$pupesoft_server.$pupesoft_port.$pupesoft_url["path"];

// Haetaan requestin dirname
$pupesoft_dirname = str_replace(array($phpnimi, $palvelin2), array("", ""), $pupesoft_scheme.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]);

// Haetaan session
$session = (isset($_COOKIE["pupesoft_session"]) and $_COOKIE["pupesoft_session"] != "deleted") ? $_COOKIE["pupesoft_session"] : "";

//Initioidaan pari muuttujaa
if (!isset($login))		$login = "";
if (!isset($extranet)) 	$extranet = "";

// jos saamme n‰m‰ muuttujat getiss‰, kokeillaan logata niill‰ sis‰‰n
if (isset($_GET["user"]) and $_GET["user"] != "" and $_GET["pass"] != "" and $_GET["yhtio"] != "") {

	$_GET["yhtio"] = mysql_real_escape_string($_GET["yhtio"]);
	$_GET["user"]  = mysql_real_escape_string($_GET["user"]);
	$_GET["pass"]  = mysql_real_escape_string($_GET["pass"]);
	$ostoskori     = isset($_REQUEST["ostoskori"]) ? (int) $_REQUEST["ostoskori"] : 0;

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
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL={$palvelin2}{$pupesoft_dirname}{$phpnimi}'>";
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

		if ($mobile and include_once("mobiili/login.php"));
		elseif (@include_once("login.php"));
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
				$palvelin2 = $mobile ? $palvelin2.'mobiili/' : $palvelin2;
				echo "<script>setTimeout(\"parent.location.href='$palvelin2'\",0);</script>";
			}
			exit;
		}

		$kukarow = mysql_fetch_assoc($result);

		// Unohdetaan tallennettu state jos tullaan suoraan indexsvassista
		if (isset($pupe_DataTables) and isset($indexvas) and $indexvas == 1) {

			if (!is_array($pupe_DataTables)) {
				$pupe_DataTables_cookie = array($pupe_DataTables);
			}
			else {
				$pupe_DataTables_cookie = $pupe_DataTables;
			}

			// DataTables cookie name SpryMedia_DataTables_TAULUNID_SKRIPTIN-NIMI
			$dtpalvelin = parse_url($palvelin, PHP_URL_PATH);
			$dtpathparts = pathinfo($phpnimi);

			if ($dtpathparts["dirname"] != "" and $dtpathparts["dirname"] != ".") {
				$dtpalvelin .= $dtpathparts["dirname"]."/";
			}

			if (basename($phpnimi) == "keikka.php") {
				$dtpalvelin .= "tilauskasittely/";
			}

			if (basename($phpnimi) == "tyojono.php") {
				$dtpalvelin .= "tyomaarays/";
			}

			if (basename($phpnimi) == "selaasopimuksia.php" or basename($phpnimi) == "asiakkaantilaukset.php") {
				$dtpalvelin .= "raportit/";
			}

			if (basename($phpnimi) == "luottotappiot.php" or basename($phpnimi) == "myyntilaskuhaku.php") {
				$dtpalvelin .= "myyntires/";
			}

			foreach ($pupe_DataTables_cookie as $datatable) {
				#error_log("SpryMedia_DataTables_".$datatable."_".basename($phpnimi)." ".(time()+43200)." ".$dtpalvelin);
				$bool = setcookie("SpryMedia_DataTables_".$datatable."_".basename($phpnimi), "", time()+43200, $dtpalvelin);
			}
		}

	 	// T‰nne p‰‰see aina!
		if ($phpnimi != 'indexvas.php' and $phpnimi != 'index.php' and $phpnimi != 'tervetuloa.php' and $phpnimi != 'logout.php') {

			if (!isset($toim)) $toim = "";

			$oikeurow = tarkista_oikeus($phpnimi, $toim, '', 'X');

			if ($oikeurow === FALSE) {
				echo "<table width='100%' height='70%'>";
				echo "<tr>";
				echo "<td style='text-align:center;font-size:9pt;font-family:Lucida,Verdana,Helvetica,Arial; color: #666;'>";
				echo "<img src='http://api.devlab.fi/pupesoft.gif'><br><br>";
				echo "K‰ytt‰j‰ {$kukarow["kuka"]} pyysi toimintoa $phpnimi $toim, joka on kielletty!";
				echo "</td>";
				echo "</tr>";
				echo "</table>";
				exit;
			}
		}
	}

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
			elseif (isset($datain_filenimi) and $datain_filenimi != '') {
				header("Content-Length: ".filesize("$pupe_root_polku/datain/".basename($datain_filenimi)));
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
			# Jos mobile on setattu, otetaan auto-zoom pois k‰ytˆst‰
			if ($mobile) echo "<meta name='viewport' content='width=250,height=246, user-scalable=no, maximum-scale=1'>\n";
			echo "<meta http-equiv='cache-control' content='no-cache'>\n";
			echo "<meta http-equiv='pragma' content='no-cache'>\n";
			echo "<meta http-equiv='expires' content='-1'>\n";
			echo "<meta http-equiv='content-type' content='text/html; charset=$charset'>\n";

			if (!isset($_GET["no_css"]) or $_GET["no_css"] != "yes") {
				echo "<style type='text/css'>\n";
				echo $css;
				echo "</style>\n";

				if (isset($pupe_DataTables)) {
					echo "<style type=\"text/css\" title=\"currentStyle\">\n";
					require("DataTables/media/css/pupe_datatables.php");
					echo "</style>\n";
				}
			}

			if (substr(dirname(__FILE__), -3) == "inc") {
				echo "<script language='javascript' src='{$palvelin2}inc/jquery.min.js'></script>";
			}
			else {
				echo "<script language='javascript' src='{$palvelin2}jquery.min.js'></script>";
			}

			if (isset($pupe_DataTables)) {
				echo "<script language='javascript' src='{$palvelin2}DataTables/media/js/jquery.dataTables.min.js'></script>";
			}

			// Salitaan VAIN YKSI submitti per sivu! Bye bye tuplaklikkaajat...
			echo "<script language='javascript' type='text/javascript'>
				var submitattu = false;
				var skippaa_tama_submitti = false;

				$(document).ready(function(){
					$('form').submit(function(){

						if (skippaa_tama_submitti) {
							skippaa_tama_submitti = true;
							return true;
						}

						// Erikseen speksattu, etta saa submitata useita kertoja
						if ($(this).attr('class') == 'multisubmit') {
							return true;
						}

						if (!submitattu) {
							$(this).children('[type=\"submit\"]').each(function() {
								$(this).css('width', $(this).outerWidth()).val('Ladataan...');
							});

							submitattu = true;
						}
						else {
							return false;
						}
					});
				});</script>";

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

		if ((!isset($no_head) or (isset($no_head) and $no_head != "yes")) and isset($lopetus) and $lopetus != '') {
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
