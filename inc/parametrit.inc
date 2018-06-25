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

// Tsekataan ajetaanko Pupea Latin1- vai UTF-8-moodissa
// (Ohjataan mssql.charset-muuttujalla php.ini-tiedostossa)
if (!defined("PUPE_UNICODE")) {
  if (get_cfg_var('mssql.charset') == "UTF-8" or @include_once ".PUPE_UNICODE") {
    define("PUPE_UNICODE", TRUE);
  }
  else {
    define("PUPE_UNICODE", FALSE);
  }
}

$alkumem   = memory_get_usage();
$timeparts = explode(" ", microtime());
$starttime = $timeparts[1].substr($timeparts[0], 1);

unset($_GET["param_clean"], $_POST["param_clean"], $_REQUEST["param_clean"]);

if (!PUPE_UNICODE) {
  if (!function_exists("pupe_iconv")) {
    function pupe_iconv(&$item, $key) {
      $item = iconv("UTF-8", "latin1//TRANSLIT", $item);
    }
  }

  if (!function_exists("pupe_register_globals")) {
    function pupe_register_globals($param_clean) {
      $src = array("\"", "'", "<", ">", "\\", ";");

      foreach ($param_clean as $key => $value) {

        if (isset($_REQUEST["sourceCharset"]) and $_REQUEST["sourceCharset"] == "UTF-8") {
          if (is_array($param_clean[$key])) {
            array_walk_recursive($param_clean[$key], "pupe_iconv");
          }
          else {
            $param_clean[$key] = iconv("UTF-8", "latin1//TRANSLIT", $param_clean[$key]);
          }
        }

        $param_clean[$key] = $GLOBALS[$key] = str_replace($src, "", $param_clean[$key]);
      }

      return $param_clean;
    }
  }
}
else {
  if (!function_exists("pupe_register_globals")) {
    function pupe_register_globals($param_clean) {
      $src = array("\"", "'", "<", ">", "\\", ";");
      $rpl = array("{DOULEPRIME}", "{PRIME}", "" , "" , ""  , "");

      foreach ($param_clean as $key => $value) {
        $param_clean[$key] = $GLOBALS[$key] = str_replace($src, $rpl, $param_clean[$key]);
      }

      return $param_clean;
    }
  }
}

$_GET     = pupe_register_globals($_GET);
$_POST    = pupe_register_globals($_POST);
$_REQUEST = pupe_register_globals($_REQUEST);

// Hoidetaan pakkaus
if ((isset($compression) and !$compression) or (isset($nayta_pdf) and $nayta_pdf == 1) or (isset($lataa_tiedosto) and $lataa_tiedosto == 1)) {
  ini_set('zlib.output_compression', 'Off');
}
else {
  ini_set('zlib.output_compression', 'On');
}

// Nollataan kaikki muuttujat joita voi setata salasanat.php:ssä
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

unset($sepa_pankkiyhteys_token);
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
unset($verkkokauppa_nollahintaok);

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

unset($kpexport);

unset($liitetaanko_editilaus_laskulle_hakemisto);

unset($siirrasuoritustilille);
unset($ohjewiki_url);
unset($synkronoi_ainoastaan);
unset($synkronoi_kiellot);
unset($alv_laskelman_sallittu_erotus);
unset($e3_params);
unset($tee_keraysera_debugdir);

unset($default_login_language);
unset($loginkieli);

unset($magento_api_tt_url);
unset($magento_api_tt_usr);
unset($magento_api_tt_pas);

unset($magento_api_te_url);
unset($magento_api_te_usr);
unset($magento_api_te_pas);

unset($magento_api_ht_url);
unset($magento_api_ht_usr);
unset($magento_api_ht_pas);
unset($magento_api_ht_edi);

unset($logmaster);
unset($woo_config);

unset($pupesoft_commandlinelog);

$pupe_query_debug = 0;
$aika_debug_array = array();
$quer_debug_array = array();

require "salasanat.php";

// haetaan jotain tarpeellisia funktioita mukaan..
if (@include_once "inc/functions.inc") {
  // Pupeasennuksen root polku
  $pupe_root_polku = dirname(dirname(__FILE__));
}
elseif (@include_once "functions.inc") {
  // Pupeasennuksen root polku
  $pupe_root_polku = dirname(__FILE__);
}
else exit;

$link = @mysql_connect($dbhost, $dbuser, $dbpass) or die ("Tietokantapalvelimeen ei saada yhteyttä. Se on pois päältä tai käyttäjätietosi ovat virheelliset.");
mysql_select_db($dbkanta) or die ("Tietokantaa $dbkanta ei löydy palvelimelta $dbhost! (parametrit.inc)");
mysql_set_charset("latin1", $link);
mysql_query("set group_concat_max_len=1000000", $link);

// pupesoft_cleanstring ALL THE COOKIES!!
$_COOKIE = array_map("pupesoft_cleanstring", $_COOKIE);

// Palvelin-muuttujassa pitää olla lopussa kauttaviiva
$palvelin = rtrim($palvelin, "/")."/";

$pupesoft_url = parse_url($palvelin);

// Otetaan tietoa tästä requestistä
$PHP_SELF = $_SERVER["PHP_SELF"];
$phpnimi = basename($_SERVER["SCRIPT_NAME"]);
$pupesoft_scheme = (isset($_SERVER["HTTPS"]) and $_SERVER["HTTPS"] == 'on') ? "https://" : "http://";
$pupesoft_server = (isset($_SERVER['HTTP_X_FORWARDED_SERVER']) and isset($palvelin_ip) and $palvelin_ip == $_SERVER['SERVER_NAME']) ? $_SERVER['HTTP_X_FORWARDED_SERVER'] : $_SERVER['SERVER_NAME'];
$pupesoft_port = ($_SERVER['SERVER_PORT'] != '80' and $_SERVER['SERVER_PORT'] != '443') ? ":".$_SERVER['SERVER_PORT'] : "";

// $palvelin muuttujassa on Pupesoft root url, joka tulee salasat.php:stä
// $palvelin2 muuttujassa on Pupesoft root url, mutta scheme/server/portti on muutettu tarpeen mukaan
$palvelin2 = $pupesoft_scheme.$pupesoft_server.$pupesoft_port.$pupesoft_url["path"];
$palvelin_pupenext = "{$palvelin2}pupenext/";

// Haetaan requestin dirname
$pupesoft_dirname = str_replace(array($phpnimi, $palvelin2), array("", ""), $pupesoft_scheme.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]);

// Haetaan session
$session = (isset($_COOKIE["pupesoft_session"]) and $_COOKIE["pupesoft_session"] != "deleted") ? $_COOKIE["pupesoft_session"] : "";

// käytössä olevat kielet
$sanakirja_kielet = array("fi" => "Suomi",
  "en" => "Englanti",
  "se" => "Ruotsi",
  "ee" => "Viro",
  "de" => "Saksa",
  "dk" => "Tanska",
  "no" => "Norja",
  "ru" => "Venäjä");

// Pupenext ylläpitonakymät aakkosjärjestyksessä
$pupenext_yllapitonakymat = array(
  "kassalipas"         => "{$palvelin2}pupenext/cash_registers",
  "kirjoittimet"       => "{$palvelin2}pupenext/printers",
  "kustannuspaikka"    => "{$palvelin2}pupenext/qualifiers",
  "maksuehto"          => "{$palvelin2}pupenext/terms_of_payments",
  "pakkaamo"           => "{$palvelin2}pupenext/packing_areas",
  "pakkaus"            => "{$palvelin2}pupenext/packages",
  "pankkiyhteystiedot" => "{$palvelin2}pupenext/bank_details",
  "rahdinkuljettajat"  => "{$palvelin2}pupenext/carriers",
  "taso"               => "{$palvelin2}pupenext/sum_levels",
  "tili"               => "{$palvelin2}pupenext/accounts",
  "tilikaudet"         => "{$palvelin2}pupenext/fiscal_years",
  "valuu"              => "{$palvelin2}pupenext/currencies",
  "yriti"              => "{$palvelin2}pupenext/bank_accounts",
);

//Initioidaan pari muuttujaa
if (!isset($login))    $login = "";
if (!isset($extranet)) $extranet = "";

// jos saamme nämä muuttujat getissä, kokeillaan logata niillä sisään
if (isset($_GET["user"]) and $_GET["user"] != "" and $_GET["pass"] != "" and $_GET["yhtio"] != "") {

  $_GET["yhtio"] = mysql_real_escape_string($_GET["yhtio"]);
  $_GET["user"]  = mysql_real_escape_string($_GET["user"]);
  $_GET["pass"]  = mysql_real_escape_string($_GET["pass"]);
  $ostoskori     = isset($_REQUEST["ostoskori"]) ? (int) $_REQUEST["ostoskori"] : 0;

  $query = "SELECT * FROM kuka WHERE yhtio='$_GET[yhtio]' AND kuka='$_GET[user]' AND salasana='$_GET[pass]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    $query = "SELECT * FROM kuka WHERE yhtio='$_GET[yhtio]' AND kuka='$_GET[user]' AND salasana=md5('$_GET[pass]')";
    $result = pupe_query($query);
  }

  if (mysql_num_rows($result) == 1) {

    $apukukarow = mysql_fetch_assoc($result);

    // jos meillä on sessio niin poistetaan se tietokannasta jo aluks
    if ($session != "") {
      $query = "UPDATE kuka
                SET session = ''
                WHERE session = '" . mysql_real_escape_string($session) . "'";
      $result = pupe_query($query);
    }

    $session = "";
    for ($i = 0; $i < 25; $i++) {
      $session .= chr(rand(65, 90)) ;
    }

    $query = "UPDATE kuka
              SET session = '$session',
              lastlogin  = now()
              WHERE kuka = '$apukukarow[kuka]'
               and yhtio = '$apukukarow[yhtio]'";
    $result = pupe_query($query);

    $query = "UPDATE kuka
              SET session = ''
              WHERE kuka  = '$apukukarow[kuka]'
               and yhtio != '$apukukarow[yhtio]'";
    $result = pupe_query($query);

    $bool = setcookie("pupesoft_session", $session, time()+43200, "/"); // 12 tuntia voimassa

    if ($bool === FALSE) {
      die ("Sisäänkirjaus epäonnistui!");
    }

    // refreshataan vain jos ei ole ostoskoria mukana
    if ($ostoskori == 0) {
      echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL={$palvelin2}{$pupesoft_dirname}{$phpnimi}'>";
      exit;
    }
  }
  else {
    die ("Sisäänkirjaus epäonnistui!");
  }
}
// jos sessionia ei ole ja ei olla loginissa, mennään login ruutuun ja exit...
elseif ($session == '' and $login != 'yes' and (!isset($verkkokauppa) or $verkkokauppa == '')) {

  if ($phpnimi != 'index.php') {
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$palvelin2'>";
  }
  else {
    if (isset($loginkieli)) {
      $browkieli = $loginkieli;
    }
    else {
      //haetaan browserin oletuskieli
      $browkieli = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2);

      if (isset($default_login_language) and $browkieli != 'fi') {
        $browkieli = $default_login_language;
      }
      elseif ($browkieli == 'sv') {
        $browkieli = 'se';
      }
      elseif ($browkieli == 'ee') {
        $browkieli = 'ee';
      }
      else {
        $browkieli = 'fi';
      }
    }

    if (isset($mobile) and $mobile and include_once "mobiili/login.php");
    elseif (@include_once "login.php");
    elseif (@include_once "login_extranet.php");

  }

  exit;
}

//  Verkkokauppamoodissa ei koskaan ohjetta!
if (isset($verkkokauppa) and $verkkokauppa != "") $_GET["ohje"] = "off";

// jos meillä on sessio
if ($session != '' or ((isset($verkkokauppa) and $verkkokauppa != "") and $login != 'yes')) {

  //  Kirjataan verkkokauppakäyttäjä
  if (isset($verkkokauppa) and $verkkokauppa != "" and $session == "") {
    //  Täällä ei ole mitään oikeuksia
    $oikeurow = array();
    $kukarow["kuka"]     = "www";
    $kukarow["nimi"]     = "Verkkokauppa";
    $kukarow["extranet"]   = "X";
    $kukarow["yhtio"]     = $verkkokauppa;
  }
  else {

    $query = "SELECT *
              FROM kuka
              WHERE session='" . mysql_real_escape_string($session) . "'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 1 or strlen($session) != 25) {
      if (isset($nayta_pdf) and $nayta_pdf == 1) {
        echo "Your browser does not seem to support cookies.";
        exit;
      }

      $bool = setcookie("pupesoft_session", "", time()-43200, "/");

      if ($bool === FALSE) {
        echo "Your browser does not seem to support cookies.";
      }
      else {
        $palvelin2 = (isset($mobile) and $mobile) ? $palvelin2.'mobiili/' : $palvelin2;
        echo "<script>setTimeout(\"parent.location.href='$palvelin2'\",0);</script>";
      }
      exit;
    }

    $kukarow = mysql_fetch_assoc($result);

    // Tehdään cookieita, jos tarvitaan

    // Muokka Tilaus
    if ($phpnimi == "muokkaatilaus.php") {
      // Muistetaan asiakastietojen valinta
      if (!empty($asiakastiedot)) {
        setcookie("pupesoft_muokkaatilaus", $asiakastiedot, time()+60*60*24*90, "/");
      }

      // Näytetään riittääkö saldo keräyspäivänä
      if ($naytetaanko_saldot == 'kylla') {
        setcookie("naytetaanko_saldot", $naytetaanko_saldot, time()+60*60*24*90, "/");
      }
      elseif ($naytetaanko_saldot == 'ei') {
        setcookie("naytetaanko_saldot", '', time()+60*60*24*90, "/");
      }

      // Näytetään viite/asiakkaan tilausnumero lisätietoja
      if ($viitetiedot == 'kylla') {
        setcookie("viitetiedot", $viitetiedot, time()+60*60*24*90, "/");
      }
      elseif ($viitetiedot == 'ei') {
        setcookie("viitetiedot", '', time()+60*60*24*90, "/");
      }
    }

    if ($phpnimi == "asiakasmemo.php" or $phpnimi == "tilaus_myynti.php") {
      if (isset($tallenna_keksiin) and isset($nayta_kaikki_merkinnat)) {
        $_COOKIE["pupesoft_asiakasmemo"] = count($nayta_kaikki_merkinnat) > 1 ? "nayta_kaikki_merkinnat" : "vain_omat";
        setcookie("pupesoft_asiakasmemo", $_COOKIE["pupesoft_asiakasmemo"], time()+60*60*24*90, "/");
      }
    }

    // Tuoteperhe
    if ($phpnimi == "tuoteperhe.php") {
      // Muistetaan keräyksen ohituksen valinta
      if (isset($tallenna_keksiin) and $ohita_kerays == "on") {
        setcookie("pupesoft_tuoteperhe", "ohitetaan", time()+60*60*24*90, "/");
        $_COOKIE["pupesoft_tuoteperhe"] = "ohitetaan";
      }
      elseif (isset($tallenna_keksiin) and !isset($ohita_kerays)) {
        setcookie("pupesoft_tuoteperhe", "ei", time()+60*60*24*90, "/");
        $_COOKIE["pupesoft_tuoteperhe"] = "ei";
      }
    }

    if ($phpnimi == 'kuukautisostot.php') {
      if (isset($valitut)) {
        if (isset($valitut["EIVIENTEJA"])) {
          setcookie('valitut_EIVIENTEJA', $valitut["EIVIENTEJA"], time()+60*60*24*90, "/");
        }
        if (isset($valitut["EIASIAKKAANMYYNTEJA"])) {
          setcookie('valitut_EIASIAKKAANMYYNTEJA', $valitut["EIASIAKKAANMYYNTEJA"], time()+60*60*24*90, "/");
        }
      }
    }

    if ($phpnimi == 'tilaus_myynti.php') {
      if (!empty($_POST["tilausrivi_alvillisuus"])) {
        setcookie('tilausrivi_alvillisuus', $_POST["tilausrivi_alvillisuus"], time()+60*60*24*90, "/");
      }
      if (isset($lajit)) {
        setcookie("avainsanalaji_keksi", urlencode(serialize($lajit)), strtotime('+90 days'), "/");
        $_COOKIE["avainsanalaji_keksi"] = urlencode(serialize($lajit));
      }
      if (isset($valitut_sarakkeet)) {
        setcookie("laiteluettelo_keksi", urlencode(serialize($valitut_sarakkeet)), strtotime('+90 days'), "/");
        $_COOKIE["laiteluettelo_keksi"] = urlencode(serialize($valitut_sarakkeet));
      }
      if (isset($valmiste_vai_raakaaine) and $toim == "VALMISTAVARASTOON") {
        setcookie('valmiste_vai_raakaaine', $valmiste_vai_raakaaine, time()+60*60*24*90, "/");
        $_COOKIE["valmiste_vai_raakaaine"] = urlencode(serialize($valmiste_vai_raakaaine));
      }
    }

    if ($phpnimi == 'raportit.php' and $toim == 'avoimet') {
      if (isset($tallenna_keksiin)) {
        if (!empty($_POST['karhutieto'])) {
          setcookie('karhutieto', $karhutieto, time()+60*60*24*90, "/");
          $_COOKIE["karhutieto"] = $karhutieto;
        }
        else {
          setcookie('karhutieto', '', 1, "/");
          $_COOKIE["karhutieto"] = '';
        }
      }
    }

    if ($phpnimi == 'generoi_siirtolista.php') {
      setcookie('lapsituotteet', $lapsituotteet, time()+60*60*24*90, "/");
      setcookie('myyntiera', $myyntiera, time()+60*60*24*90, "/");
      setcookie('kesken', $kesken, time()+60*60*24*90, "/");
    }

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

      if (basename($phpnimi) == "keikka.php" or basename($phpnimi) == "tilauskanta.php") {
        $dtpalvelin .= "tilauskasittely/";
      }

      if (basename($phpnimi) == "tyojono.php") {
        $dtpalvelin .= "tyomaarays/";
      }

      if (basename($phpnimi) == "selaasopimuksia.php" or basename($phpnimi) == "asiakkaantilaukset.php" or basename($phpnimi) == "varastotilasto.php") {
        $dtpalvelin .= "raportit/";
      }

      if (basename($phpnimi) == "luottotappiot.php" or basename($phpnimi) == "myyntilaskuhaku.php" or basename($phpnimi) == "myyntilaskut_asiakasraportti.php") {
        $dtpalvelin .= "myyntires/";
      }

      foreach ($pupe_DataTables_cookie as $datatable) {
        //error_log("SpryMedia_DataTables_".$datatable."_".basename($phpnimi)." ".(time()+43200)." ".$dtpalvelin);
        $bool = setcookie("SpryMedia_DataTables_".$datatable."_".basename($phpnimi), "", time()+43200, $dtpalvelin);
      }
    }

    // Tänne pääsee aina!
    if ($phpnimi != 'indexvas.php'
      and $phpnimi != 'index.php'
      and $phpnimi != 'tervetuloa.php'
      and $phpnimi != 'logout.php'
      and $phpnimi != 'ylaframe.php'
      and $phpnimi != 'pikavalinnat.php'
      and $phpnimi != 'pupesoft_changelog.php') {

      if (!isset($toim)) $toim = "";

      $oikeurow = tarkista_oikeus($phpnimi, $toim, '', 'X');

      if ($oikeurow === FALSE) {
        echo "<table width='100%' height='70%'>";
        echo "<tr>";
        echo "<td style='text-align:center;font-size:9pt;font-family:Lucida,Verdana,Helvetica,Arial; color: #666;'>";
        echo "<img src='{$palvelin2}pics/facelift/pupe.gif'><br><br>";
        echo "Käyttäjä {$kukarow["kuka"]} pyysi toimintoa $phpnimi $toim, joka on kielletty!";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        exit;
      }
    }
  }

  $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

  if ((isset($nayta_pdf) and $nayta_pdf == 1) or (isset($lataa_tiedosto) and $lataa_tiedosto == 1)) {
    // Kerrotaan selaimelle, että tämä on tiedosto
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

        // Onko maksuaineistoille annettu salasanat.php:ssä oma polku jonne tallennetaan
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
        // Tässä on "//NO_MB_OVERLOAD"-kommentti
        // jotta UTF8-konversio ei osu tähän riviin
        $_file_size = strlen($file); //NO_MB_OVERLOAD

        header("Content-Length: {$_file_size}");
      }
    }
  }
  elseif (($phpnimi != 'index.php' and $phpnimi != 'futursoft.php') or (isset($futuvanha) and $futuvanha == '1')) {

    if ((isset($verkkokauppa) and $verkkokauppa != "") and $yhtiorow['css_verkkokauppa'] != "") {
      $yhtiorow['active_css'] = $yhtiorow['css_verkkokauppa'];
    }
    elseif ($kukarow["extranet"] != "" and $yhtiorow["css_extranet"] != "") {
      $yhtiorow['active_css'] = $yhtiorow["css_extranet"];
    }
    elseif (($yhtiorow["kayttoliittyma"] == "" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "C") {
      $yhtiorow['active_css'] = $yhtiorow['css_classic'];
    }
    else {
      $yhtiorow['active_css'] = $yhtiorow['css'];
    }

    if (!PUPE_UNICODE) {
      if ($kukarow["kieli"] == "ru" and $yhtiorow['yhtio'] == 'allr') {
        $charset = "utf-8";
      }
      else {
        $charset = "iso-8859-1";
      }
    }
    else {
      $charset = "utf-8";
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
      echo "<meta http-equiv='cache-control' content='no-cache'>\n";
      echo "<meta http-equiv='pragma' content='no-cache'>\n";
      echo "<meta http-equiv='expires' content='-1'>\n";
      echo "<meta http-equiv='content-type' content='text/html; charset=$charset'>\n";

      // Jos mobile on setattu, otetaan auto-zoom pois käytöstä
      if (isset($mobile) and $mobile) {
        echo "<meta name='viewport' content='width=250,height=246, user-scalable=no, maximum-scale=1'>\n";
        echo "<link rel='stylesheet' type='text/css' href='kasipaate.css' />\n";
      }

      if (!isset($_GET["no_css"]) or $_GET["no_css"] != "yes") {
        echo "<link rel='stylesheet' type='text/css' href='{$pupesoft_scheme}fonts.googleapis.com/css?family=Lato:400,700' >";

        echo "<style type='text/css'>\n";
        echo $yhtiorow['active_css'];
        echo "</style>\n";

        if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
          echo "<link rel='stylesheet' type='text/css' href='{$palvelin2}css/pupesoft.css' />\n";
          echo "<link rel='stylesheet' type='text/css' href='{$palvelin2}css/pupesoft_left.css' />\n";
          echo "<link rel='stylesheet' type='text/css' href='{$palvelin2}css/pupesoft_top.css' />\n";

          if (isset($_SERVER['HTTP_USER_AGENT']) and mb_stripos($_SERVER['HTTP_USER_AGENT'], "FIREFOX") !== FALSE) {
            echo "<link rel='stylesheet' type='text/css' href='{$palvelin2}css/pupesoft_mozilla.css' />\n";
          }

          if (isset($_SERVER['HTTP_USER_AGENT']) and mb_stripos($_SERVER['HTTP_USER_AGENT'], "WINDOWS") !== FALSE) {

            if (isset($_SERVER['HTTP_USER_AGENT']) and mb_stripos($_SERVER['HTTP_USER_AGENT'], "TRIDENT") !== FALSE) {
              echo "<link rel='stylesheet' type='text/css' href='{$palvelin2}css/pupesoft_ms_ie.css' />\n";
            }
            elseif (isset($_SERVER['HTTP_USER_AGENT']) and mb_stripos($_SERVER['HTTP_USER_AGENT'], "FIREFOX") !== FALSE) {
              echo "<link rel='stylesheet' type='text/css' href='{$palvelin2}css/pupesoft_ms_mozilla.css' />\n";
            }
            else {
              echo "<link rel='stylesheet' type='text/css' href='{$palvelin2}css/pupesoft_ms_chrome.css' />\n";
            }
          }
        }
        else {
          echo "<link rel='stylesheet' type='text/css' href='{$palvelin2}css/pupesoft_legacy.css' />\n";
        }

        if (isset($pupe_DataTables)) {
          echo "<style type=\"text/css\" title=\"currentStyle\">\n";
          require "DataTables/media/css/pupe_datatables.php";
          echo "</style>\n";
        }
      }

      if (substr(dirname(__FILE__), -3) == "inc") {
        echo "<script language='javascript' src='{$palvelin2}inc/jquery.min.js'></script>";
        echo "<link rel='stylesheet' href='{$palvelin2}css/jquery-ui.css' />";
        echo "<script src='{$palvelin2}inc/jquery-ui.js'></script>";
      }
      else {
        echo "<script language='javascript' src='{$palvelin2}jquery.min.js'></script>";
        echo "<link rel='stylesheet' href='{$palvelin2}css/jquery-ui-extranet.css' />";
        echo "<script src='{$palvelin2}jquery-ui.js'></script>";
      }

      if (file_exists("$pupe_root_polku/zhat.php") and !empty($kukarow['extranet'])) {
        require_once "$pupe_root_polku/zhat.php";

        if (!stristr($phpnimi, 'indexvas')) {
          echo livezhat_js_sources();
        }
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

          var hyllyalue_function = function() {

            var hyllyalue, hyllyalue_val;

            if ($(this).is(':text')) {
              hyllyalue_tmp = $(this).attr('id');
              hyllyalue_tmp = hyllyalue_tmp.replace(/(\[|\])/g,\"\\\\$1\");

              hyllyalue = hyllyalue_tmp.split(\"_\", 1);

              hyllyalue_val = $(this).val();
            }
            else {
              hyllyalue_tmp = $(this).attr('id');
              hyllyalue_tmp = hyllyalue_tmp.replace(/(\[|\])/g,\"\\\\$1\");

              hyllyalue = hyllyalue_tmp.split(\"_\", 1);

              hyllyalue_val = $('#'+hyllyalue).val();
            }

            var varastontunniste = $('#'+hyllyalue+'_select_varastontunniste > option:selected').val();

            if (varastontunniste == undefined) varastontunniste = '';

            $('#hidden_'+hyllyalue).val(varastontunniste+hyllyalue_val);
          };

          $('input.hyllyalue').on('keyup change blur', hyllyalue_function);
          $('select.select_varastontunniste').on('change', hyllyalue_function);

        });</script>";

      echo "<title>\n$yhtiorow[nimi]</title>\n";
      echo "</head>\n";

      echo "<body>\n";
      echo "<noscript>Your browser does not support Javascript!</noscript>\n";

      if (basename($phpnimi) != 'indexvas.php' and (!isset($_GET["ohje"]) or $_GET["ohje"] != "off")) {
        if (!empty($ohjewiki_url)) {
          $urltoim = "";

          if (!empty($toim)) {
            $urltoim = "/".$toim;
          }
          $hreffi = $ohjewiki_url."/Ohjeet:".basename($phpnimi).$urltoim;

          echo "<div class='ohje'><a target='_blank' href='$hreffi'>".t("OHJE")."</a></div>";
        }
        elseif (!empty($oikeurow['usermanualurl'])) {
          echo "<div class='ohje'><a href='#' onclick=\"window.open('{$oikeurow['usermanualurl']}', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100'); return false;\">".t("OHJE")."</a></div>";
        }

        if ($kukarow['toimipaikka'] > 0 and tarkista_oikeus('infobar.php')) {
          require 'infobar.php';
        }
      }
      elseif (isset($_GET["sulje"]) and $_GET["sulje"] != "") {
        echo "<div><a href='#' onclick=\"divi=document.getElementById('$_GET[sulje]'); document.body.removeChild(divi); return false;\"><font style='color: red;'>x</font></a><br></div>";
      }

      // Autetaan sanakirjan ylläpidossa
      if ($kukarow["yhtio"] != "" and $kukarow["kuka"] != "") {
        $query = "SELECT yhtio
                  FROM oikeu
                  WHERE yhtio = '$kukarow[yhtio]'
                  and kuka    = '$kukarow[kuka]'
                  and nimi    = 'sanakirja.php'
                  and alanimi = ''";
        $ksres = pupe_query($query);

        if (mysql_num_rows($ksres) > 0) $kaannetyt_sanat = array();
      }
    }

    if (isset($tee) and $tee == "message_luettu") {
      $message_tunnus = mysql_real_escape_string($message_tunnus);
      $query = "UPDATE messenger SET status='' WHERE vastaanottaja='$kukarow[kuka]' AND tunnus='$message_tunnus'";
      $result = pupe_query($query);
      exit;
    }

    // Messenger-viestit (X = ei luettu)
    if (basename($phpnimi) != 'indexvas.php' and (!isset($_GET["ohje"]) or $_GET["ohje"] != "off") and (!isset($no_head) or $no_head != "yes")) {

      $message_query = "SELECT messenger.tunnus, left(messenger.viesti, 25) kooste, messenger.viesti, kuka.nimi, messenger.luontiaika
                        FROM messenger USE INDEX (yhtio_vastaanottaja_status)
                        JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = messenger.yhtio AND kuka.kuka = messenger.kuka)
                        WHERE messenger.yhtio       in ($konserni_yhtiot)
                        AND messenger.vastaanottaja = '$kukarow[kuka]'
                        AND kuka.extranet           = ''
                        AND messenger.status        = 'X'";
      if ($message_result = pupe_query($message_query)) {
        if (mysql_num_rows($message_result) > 0) {
          enable_ajax();
          echo "<table>";

          while ($message_row = mysql_fetch_assoc($message_result)) {
            echo "<tr id='{$message_row['tunnus']}'>";
            echo "<td>".t("Viesti käyttäjältä")."</td>";
            echo "<td>{$message_row['nimi']} @ ".tv1dateconv($message_row['luontiaika'], 'yes', 'LYHYT')."</td>";
            echo "<td>{$message_row['viesti']}</td>";
            echo "<td class='back'><a href='#' onClick=\"javascript:sndReq('{$message_row['tunnus']}','?tee=message_luettu&amp;toim={$toim}&amp;message_tunnus={$message_row['tunnus']}&amp;ohje=off&amp;no_css=yes', false, false); return false;\">".t("Kuittaa")."</a></td>";
            echo "</tr>";
          }

          echo "</table><br>";
        }
      }
    }

    if ((!isset($no_head) or (isset($no_head) and $no_head != "yes")) and isset($lopetus) and $lopetus != '') {

      echo "<div class='lopetus'>";
      lopetus($lopetus);
      echo "</div>";
    }
  }

  // luodaan tietokantayhteys uudelleen, niin saadaan slave käyttöön jos sellaista tarvitaan
  require "connect.inc";

  // Katsotaan onko Pupesoftissa SEPA rajapinta asetettuna, eli pankkiyhteys käytössä
  $_sepa_pankkiyhteys = true;
  $_sepa_pankkiyhteys &= (isset($_SERVER["HTTPS"]) and $_SERVER["HTTPS"] == 'on');
  $_sepa_pankkiyhteys &= ($yhtiorow["pankkitiedostot"] == "F");
  $_sepa_pankkiyhteys &= (!empty($sepa_pankkiyhteys_token));

  define('SEPA_PANKKIYHTEYS', $_sepa_pankkiyhteys == 1 ? true : false);

  # Katsotaan onko Pupesoftissa Logmaster-rajapinta asetettuna
  $_logmaster_rajapinta = true;
  $_logmaster_rajapinta &= (!empty($logmaster['host']));
  $_logmaster_rajapinta &= (!empty($logmaster['user']));
  $_logmaster_rajapinta &= (!empty($logmaster['pass']));
  $_logmaster_rajapinta &= (!empty($logmaster['path']));
  $_logmaster_rajapinta &= ($yhtiorow['lahetteen_tulostustapa'] == 'K');
  $_logmaster_rajapinta &= ($yhtiorow['siirtolistan_tulostustapa'] == 'K');

  define('LOGMASTER_RAJAPINTA', $_logmaster_rajapinta == 1 ? true : false);
}
