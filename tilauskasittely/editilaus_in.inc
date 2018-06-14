<?php

$debug = 0;
$edi_ulos = "\n";

// Kutsutaanko CLI:st‰
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

// jos meill‰ ei ole sessiota ja meill‰ on komentoriviparametri annettuna
if ($php_cli) {
  // laitetaan pupe-rootti ja tilausk‰sittely mukaan includepathiin
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  // otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log($argv[1]);

  // ja parametri komentorivilt‰
  $filename = $argv[1];

  // ja toinen parametri komentorivilt‰
  $edi_tyyppi  = trim($argv[2]);

  // jos halutaan myyd‰ tilauksen kaikki rivit v‰kisin
  $editilaus_var_h  = trim($argv[3]);

  $verkkokauppa_filename = strtolower(basename($filename));

  // tunnistetaan magento -tyyppinen k‰sittely tiedostonimest‰
  if ($edi_tyyppi == 'futursoft' and preg_match("/^magento\-order/", $verkkokauppa_filename) === 1) {
    $edi_tyyppi = 'magento';
  }

  // tunnistetaan presta -tyyppinen k‰sittely tiedostonimest‰
  if ($edi_tyyppi == 'futursoft' and preg_match("/^presta\-order/", $verkkokauppa_filename) === 1) {
    $edi_tyyppi = 'presta';
  }

  // tunnistetaan woo commerce -tyyppinen k‰sittely tiedostonimest‰
  if ($edi_tyyppi == 'futursoft' and preg_match("/^woo\-order/", $verkkokauppa_filename) === 1) {
    $edi_tyyppi = 'woo';
  }

  // Pupeasennuksen root
  $pupe_root_polku = dirname(dirname(__FILE__));
}

if (!function_exists('jt_toimita_editilaus')) {
  function jt_toimita_editilaus($asiakasno, $asiakasid, $asiakasmaa, $toimipaikka = 0) {
    global $yhtiorow, $kukarow, $edi_ulos, $php_cli;

    $automaaginen                = "automaaginen";
    $tee                         = "JATKA";
    $oikeurow['paivitys']        = '1';
    $toimittaja                  = "";
    $toimittajaid                = "";
    $jarj                        = "tuoteno";
    $tuotenumero                 = "";
    $tilaus                      = "";
    $toimi                       = "";
    $suoratoimit                 = "";
    $tilaus_on_jo                = "KYLLA";
    $from_varastoon_inc          = "editilaus_in.inc";
    $ei_tehdastoimitus_tuotteita = "EI";
    $automaattinen_poiminta      = "JOO";
    $toimipaikka = (int) $toimipaikka;

    if ($asiakasmaa != '') {
      $asiakasmaalisa = "and (varastopaikat.sallitut_maat like '%{$asiakasmaa}%' or varastopaikat.sallitut_maat = '')";
    }

    // Tarkistetaan onko toimipaikalle varastoja
    if (!empty($toimipaikka) and $yhtiorow['myyntitilauksen_toimipaikka'] == 'A') {
      $query = "SELECT *
                FROM varastopaikat
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND tyyppi      = ''
                AND toimipaikka = '{$toimipaikka}'
                {$asiakasmaalisa}";
      $tp_var_chk_res = pupe_query($query);

      if (mysql_num_rows($tp_var_chk_res) == 0) {
        $toimipaikka = 0;
      }
    }

    $toimipaikkalisa = "";

    if ($yhtiorow['myyntitilauksen_toimipaikka'] == 'A') {
      $toimipaikkalisa = "and toimipaikka = '{$toimipaikka}'";
    }

    $query = "SELECT *
              FROM varastopaikat
              WHERE yhtio = '{$kukarow['yhtio']}'
              and tyyppi  = ''
              {$toimipaikkalisa}
              {$asiakasmaalisa}";
    $vtresult = pupe_query($query);

    $varastosta = array();

    while ($vrow = mysql_fetch_assoc($vtresult)) {
      $varastosta[$vrow["tunnus"]] = $vrow["tunnus"];
    }

    if ($asiakasid > 0 and count($varastosta) > 0) {
      require 'jtselaus.php';
    }
  }
}

if (!function_exists('hae_erikoiskaupan_parametrit')) {
  function hae_erikoiskaupan_parametrit($storename) {
    global $verkkokauppa_erikoiskasittely;

    foreach ($verkkokauppa_erikoiskasittely as $sp) {
      if (strpos($storename, $sp[0]) !== false) {
        return $sp;
      }
    }

    return array();
  }
}

if (!function_exists('hae_yhtiotiedot')) {
  function hae_yhtiotiedot($tieto) {
    global $yhtiorow, $kukarow, $edi_ulos, $php_cli, $argv, $editil_cli;

    $query = "SELECT * FROM yhtio WHERE ovttunnus = '$tieto' and ovttunnus != ''";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 1) {
      // Kokeillaan tehd‰ ovttunnuksesta ytunnus
      $tieto2 = substr($tieto, 4, 8);     // etunollilla
      $tieto  = substr($tieto, 4, 8) * 1; // ilman etunollio

      // H‰kki ÷rumia varten
      if ((int) $tieto == 0) $tieto = "20428100";

      // Toinen h‰kki
      if ((int) $tieto == 6270618) $tieto = "24955530";

      $query = "SELECT * FROM yhtio WHERE ytunnus in ('$tieto', '$tieto2') and ytunnus != ''";
      $result = pupe_query($query);
    }

    if (mysql_num_rows($result) != 1) {
      $edi_ulos .= "\n".t("Vastaanottavaa yrityst‰ ei lˆydy tunnuksella")." '$tieto', '$tieto2', '$mailfile'\n";
      return;
    }

    $yhtiorow = mysql_fetch_assoc($result);
    $yhtiorow = hae_yhtion_parametrit($yhtiorow["yhtio"]);

    // Jos ajetaan komentorivilt‰, niin haetaan kukarow
    if ($php_cli) {
      $kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);

      // Komentorivilt‰ ku ajetaan, niin ei haluta posteja admin-k‰ytt‰j‰lle
      $kukarow["eposti"] = "";
    }

    $kukarow['yhtio'] = $yhtiorow['yhtio']; // T‰m‰ on vaarallista, mutta t‰t‰ ilman ei mik‰n toimi!
    $kukarow['kuka']  = $edi_laatija;

    // nollataan argv ja laitetaan ensimm‰iseksi paratemetriksi yhtio, niin verkkolaskutus toimii!
    $argv       = array();
    $argv[0]    = "";
    $argv[1]    = $kukarow["yhtio"];
    $editil_cli = TRUE;

    $edi_ulos .= "-> $yhtiorow[nimi] ($kukarow[yhtio])\n";
  }
}

// nollataan kaikki laskun muuttujat (n‰m‰ samat pit‰‰ tehd‰ whilen lopussa)
unset($arow);
unset($arow_viite);
unset($arow_ostaja);
unset($arow_toimitus);
unset($arow_terminaali);
unset($yhtiorow);
unset($crossdock911);
unset($laskpp, $laskkk, $laskvv);

$asiakkaan_tilausnumero       = "";
$edi_kommentti                = "";
$editilaus_alatila            = "";
$editilaus_eilahetetta        = "";
$editilaus_hyvaksyttava       = "";
$editilaus_kassalipas         = "";
$editilaus_liitetiedosto      = "";
$editilaus_loppuhinta         = "";
$editilaus_maksuehto          = "";
$editilaus_maksuteksti        = "";
$editilaus_osatoimitus        = "";
$editilaus_otsikko_ok         = "";
$editilaus_rahtivapaa         = "";
$editilaus_sisainen           = "";
$editilaus_tila               = "N"; // laskun tila
$editilaus_tilausaika         = "";
$editilaus_tilaustyyppi       = "";
$editilaus_toimitustapa       = "";
$editilaus_tyyppi             = "L"; // tilausrivi tyyppi
$editilaus_valuuttakoodi      = "";
$editilaus_ytunnus            = "";
$ei_esteta_laskutusta         = true;
$id                           = "";
$ketjutus                     = "";
$kieltolisa                   = "";
$lnimi                        = "";
$lnimitark                    = "";
$lopnimi                      = "";
$lopnumero                    = "";
$loposoite                    = "";
$loppostino                   = "";
$loppostitp                   = "";
$lopviesti                    = "";
$losoite                      = "";
$lpostino                     = "";
$lpostitp                     = "";
$ltoim_maa                    = "";
$ltoim_puh                    = "";
$maksaja                      = "";
$merkkitieto                  = "";
$noutopisteen_tunnus          = "";
$ohjausmerkki                 = "";
$OT_ASIAKASNRO                = "";
$ot_verkkokauppa_asiakasnro   = "";
$ot_verkkokauppa_kohde        = "";
$ot_verkkokauppa_tilausviesti = "";
$ovt_tunnus                   = "";
$ovt_tunnuswhere              = "";
$suunnistus                   = "";
$til_vienti                   = "";
$tilausyhteyshenkilo          = "";
$tnimi                        = "";
$tnimitark                    = "";
$toimpvm                      = date("Y-m-d");
$tosoite                      = "";
$tpostino                     = "";
$tpostitp                     = "";
$vastaustapa                  = "";
$verkkokauppa                 = "";
$verkkokauppavarasto          = "";
$verkkokauppavarasto_li       = "";
$verokanta                    = "";
$veromaara                    = "";
$viite                        = "";
$yhteyshenkilon_puhelin       = "";

// nollataan kaikki rivin muuttujat (n‰m‰ samat pit‰‰ tehd‰ tuote insertin j‰lkeen)
$tuoteerror        = 1; // oletetaan aina ettei  tuotetta lˆydy
$tuoteerrortxt     = "";
$kpl               = "";
$rivinumero        = "";
$kommentti         = "";
$tuoteno           = "";
$olinsapi          = "";
$olinvarasto       = "";
$edi_nimitys       = "";
$sallitaanjt       = "";
$editilaus_hinta   = "";
$editilaus_alennus = "";
$rivikommentti     = "";
$verkkokauppa_alennus100 = false;

// Mihin tilaan laitetaan verkkokauppatilauksen tuotteet, jos saldo on loppu Pupesta
if (empty($verkkokauppa_oletus_loppuneet) or !in_array($verkkokauppa_oletus_loppuneet, array('H', 'J'))) {
  $verkkokauppa_oletus_loppuneet = 'H';
}

// fiilataan parametrej‰ joita tarvitaan require fileiss‰
$tulos_ulos = "edi"; // t‰‰ on ftp-send.inciss‰

// tarvitaan $filename
$filename = trim($filename);
if ($filename == "") die('Tiedostonimi puuttui!\n');

// Muutetaan oikeaan merkistˆˆn
$encoding = exec("file -b --mime-encoding '$filename'");

if (!PUPE_UNICODE and $encoding != "" and mb_strtoupper($encoding) != 'ISO-8859-1') {
  exec("recode $encoding..ISO-8859-1 '$filename'");
}
elseif (PUPE_UNICODE and $encoding != "" and mb_strtoupper($encoding) != 'UTF-8') {
  exec("recode $encoding..UTF8 '$filename'");
}

if (!$fd = fopen($filename, "r")) die("Filen '$filename' ".t("avaus ep‰onnistui")."!\n");

//jotta filenimi ei varmasti katoaisi matkalla
$mailfile = $filename;

// EDI-tilausten k‰sittelyhakemisto
$editilaus_hakemisto = dirname(realpath($filename));

if ($edi_tyyppi == "futursoft") {
  $edi_subj = "Sis‰‰ntuleva Futursoft-tilaus";
  $edi_laatija = "FuturSoft";
}
elseif ($edi_tyyppi == "ahkio") {
  $edi_subj = "Sis‰‰ntuleva Ahkio -tilaus";
  $edi_laatija = "Ahkio";
  $edi_tyyppi = "magento"; // Ahkio toimii t‰‰ll‰ samaan tapaan kuin magento
}
elseif ($edi_tyyppi == "magento") {
  $edi_subj = "Sis‰‰ntuleva Verkkokauppa-tilaus";
  $edi_laatija = "Magento";
  $editilaus_rahtivapaa = "o";
}
elseif ($edi_tyyppi == "presta") {
  $edi_subj = "Sis‰‰ntuleva Verkkokauppa-tilaus";
  $edi_laatija = "Presta";
  $edi_tyyppi = "magento"; // presta toimii t‰‰ll‰ samaan tapaan kuin magento
}
elseif ($edi_tyyppi == "woo") {
  $edi_subj = "Sis‰‰ntuleva Verkkokauppa-tilaus";
  $edi_laatija = "WooCommerce";
  $edi_tyyppi = "magento"; // woocommerce toimii t‰‰ll‰ samaan tapaan kuin magento
}
elseif ($edi_tyyppi == "edifact911") {

  $edi_subj = "Sis‰‰ntuleva Orders 91.1 tilaus";
  $edi_laatija = "Orders911";

  // Orders 91.1 tulee ostajan tiedot ennen vastaanottajaa, joten meid‰n pit‰‰ heti t‰ss‰ vaiheessa kaivaa jo vastaanottajan tiedot
  $order_tietosisalto = file_get_contents($filename);

  $order_tietosisalto = str_replace("\n", "", $order_tietosisalto);
  $order_tietosisalto = explode("'", $order_tietosisalto);

  // Tallennetaan NAD+SE -segmentti, koska se tulee muuten liian myˆh‰isess‰ vaiheessa...
  $nadse_segmentti = "";

  foreach ($order_tietosisalto as $key => $value) {

    $value = trim($value);

    if ($value != "") {
      $edi_rivi .= $value."\n";

      if (substr($value, 0, 6) == "NAD+SE") {
        $nadse_segmentti = $value;
      }
      if (substr($value, 0, 23) == "EDIFACT_CROSSDOCK_SPLIT") {
        $crossdock911 = "JOO";
      }
    }
  }

  unset($order_tietosisalto);

  // Luodaan arraysta => tiedosto
  $tiedosto = "/tmp/ediorders911".uniqid(time()."_").".txt";
  file_put_contents($tiedosto, $edi_rivi);

  // avataan tiedosto uudestaan
  if (!$fd = fopen($tiedosto, "r")) die("Filen '$tiedosto' ".t("avaus ep‰onnistui")."!\n");

  //jotta filenimi ei varmasti katoaisi matkalla, t‰m‰ ylikirjoittaa edellisen mailfilen.
  $mailfile = $tiedosto;
}
else {
  $edi_subj = t("Sis‰‰ntuleva EIH 1.4 EDI-tilaus");
  $edi_laatija = "EDI";
}

$edi_ulos .= "$edi_subj\n";

while ($tietue = fgets($fd)) {

  // Tyhj‰t rivit skipataan
  if (trim($tietue) == "") continue;

  // t‰‰ on futurikeisi
  if ($edi_tyyppi == "futursoft" or $edi_tyyppi == "magento") {

    if (substr($tietue, 0, 1) != '*') {
      list($tunnus, $tieto)  = explode(':', $tietue);
    }
    else {
      $tunnus = $tietue;
      $tieto  = "";
    }

    $tunnus = trim($tunnus);
    $tieto  = trim($tieto);

    if (preg_match('/\*RE *OSTOTILRIV/', $tunnus)) {
      $tunnus = '*RE OSTOTILRIV'; // Poistetaan h‰iritsev‰ nro
    }
  }
  elseif ($edi_tyyppi == "edifact911") {
    $tunnus = substr($tietue, 0, 3);
    $tieto  = trim(substr($tietue, 4));
  }
  else {
    $tunnus = substr($tietue, 0, 9);
    $tieto  = trim(substr($tietue, 10));
  }

  // tehd‰‰n kaikille tiedoille 7 -> 8 bit ‰‰kkˆskonversio..
  $from     = array('{', '}', '|', '[', ']', '\\', chr(179)); // 7bit
  $to       = array('‰', 'Â', 'ˆ', 'ƒ', '≈', '÷', '|');     // 8bit
  $orig_tieto = str_replace($from, $to, $tieto);
  $tieto    = str_replace($from, $to, pupesoft_cleanstring($tieto));

  // Jos kyseess‰ on UNH (ensimm‰inen Orders 91.1 segmentti), niin etsit‰‰n t‰ss‰ vaiheessa NAD SE
  if ($tunnus == "UNH") {
    $tunnus = "NADSE"; // Myyj‰

    $nad_segmentit = explode("+", $nadse_segmentti);
    $nad_kentta = explode(":", $nad_segmentit[2]);
    $tieto = $nad_kentta[0];
  }

  // Jos kyseess‰ NAD segmentti, tarkennetaan mik‰ NAD on kyseess‰ ja laitetaan $tieto kuntoon
  if ($tunnus == "NAD") {
    switch (substr($tieto, 0, 2)) {
    case 'BY':
      $tunnus = "NADBY"; // Ostaja
      $nad_segmentit = explode("+", $tieto);
      $nad_kentat = explode(":", $nad_segmentit[1]);
      $tieto = $nad_kentat[0];
      break;
    case 'PL':
      $tunnus = "NADPL"; // Edelleenlaskutettava
      $nad_segmentit = explode("+", $tieto);
      $nad_kentat = explode(":", $nad_segmentit[1]);
      $tieto = $nad_kentat[0];
      break;
    case 'CN':
      $tunnus = "NADCN"; // Tavaran vastaanottaja
      $nad_segmentit = explode("+", $tieto);
      $nad_kentat = explode(":", $nad_segmentit[1]);
      $tieto = $nad_kentat[0];
      break;
    case 'DP':
      $tunnus = "NADDP"; // Toimitusosoite
      break;
    }
  }

  // Otetaan seuraava rivi muuttujaan
  $positio   = ftell($fd);
  $next_tietue = fgets($fd);
  $next_tunnus = trim(substr($next_tietue, 0, 3));
  fseek($fd, $positio);

  $rivinvaihto = $edi_tyyppi == "edifact911";
  $rivinvaihto = ($rivinvaihto and $tunnus == "QTY");

  if (isset($crossdock911) and $crossdock911 == "JOO") {
    $rivinvaihto = ($rivinvaihto and $next_tunnus == "LIN");
  }

  // Tuoterivi vaihtuu ilman selke‰t‰ rivinloppumismerkki‰....
  if ($rivinvaihto) {
    $tunnus = "QTYLIN";
  }

  switch ($tunnus) {
  case 'ICHG_RCPT':
  case 'OSTOTIL.OT_TOIMITTAJANRO':
  case 'OSTOTIL.OT_OVTTUNNUS':
  case 'NADSE':
    //Tilauksen vastaanottaja
    $type = explode("@", $tieto);
    $tieto = trim($type[0]);

    $edi_ulos .= t("Tilaus yritykselle")." '$tieto' ";

    hae_yhtiotiedot($tieto);
    break;

  case 'ONADBY_DO' :
  case 'OSTOTIL.OT_VAHVISTUS_FAKSILLA' :
    //Faxvastaus
    $tieto = substr($tieto, 4, 2);
    if ($tieto == 'FX' or $tieto == '1') {
      $vastaustapa .= 'F';
      $edi_ulos .= t("Pyyt‰‰ Fax-tilausvahvistuksen").".\n";
    }
    break;

  case 'OSTOTIL.OT_TILAUSTYYPPI' :
    //Tilaustyyppi
    $editilaus_tilaustyyppi = trim($tieto);
    break;

  case 'OSTOTIL.OT_VALUUTTAKOODI' :
    // Tilauksen valuutta
    $editilaus_valuuttakoodi = $tieto;

    if (!empty($tieto)) {
      $edi_ulos .= t("Valuuttakoodi")." $tieto\n";
    }

    break;

  case 'OSTOTIL.VERKKOKAUPPA' :
    // Verkkokaupan nimi
    $verkkokauppa = $tieto;
    break;

  case 'OSTOTIL.OT_VERKKOKAUPPA_ASIAKASNRO' :
    // Verkkokaupan asiakasnumero
    $ot_verkkokauppa_asiakasnro = $tieto;
    break;

  case 'OSTOTIL.OT_VERKKOKAUPPA_TILAUSVIITE' :
    // Verkkokauppassa asiakkaan antama tilausviite
    $ot_verkkokauppa_tilausviesti = $tieto;
    break;

  case 'OSTOTIL.OT_VERKKOKAUPPA_TILAUSNUMERO' :
    // Verkkokauppassa asiakkaan antama tilausnumero
    $viestilisa = '';
    if (!empty($ot_verkkokauppa_tilausviesti)) {
      $viestilisa = " / ";
    }
    if (!empty($tieto)) {
      $ot_verkkokauppa_tilausviesti .= $viestilisa.t("Tilausnro").": ".$tieto;
    }
    break;

  case 'OSTOTIL.OT_VERKKOKAUPPA_KOHDE' :
    // Verkkokaupassa asiakkaan antama kohde
    $ot_verkkokauppa_kohde = $tieto;
    break;

  case 'OSTOTIL.OT_LIITETIEDOSTO' :
    // Tilaukseen liittyv‰ liitetiedosto
    $editilaus_liitetiedosto = basename($tieto);
    break;

  case 'OBGMIACKR' :
    //Edivastaus
    if ($tieto == "AB") {
      $vastaustapa .= 'E';
      $edi_ulos .= t("Pyyt‰‰ EDI-tilausvahvistuksen").".\n";
    }
    if ($tieto == "S") {
      $vastaustapa .= 'S';
      $edi_ulos .= t("Pyyt‰‰ s‰hkˆposti-tilausvahvistuksen").".\n";
    }
    if ($tieto == "F") {
      $vastaustapa .= 'F';
      $edi_ulos .= t("Pyyt‰‰ fax-tilausvahvistuksen").".\n";
    }
    break;

  case 'OBGMITYPE' :

    $type = explode("@", $tieto);

    // jos terminaalitoimitus
    if (trim($type[1]) == "TKT") {
      $edi_ulos .= t("Terminaalitoimitus")." '$type[1]'\n";
      $editilaus_tila   = "Z";
      $editilaus_tyyppi = "Z";
    }
    else {
      $editilaus_tila   = "N";
      $editilaus_tyyppi = "L";
    }

    // jos TRM ni ei kai mit‰‰ spessua
    if (trim($type[1]) == "TRM") {

    }

    // Pupesoft tilaustyyppi
    if ($tieto == '1') {
      $edi_ulos .= t("Laskua ei saa ketjuttaa")."!\n";
      $ketjutus = "o";
    }
    else {
      $ketjutus = "";
    }
    break;

  case 'OLINSTART' :
  case 'OSTOTILRIV.OTR_RIVINRO' :
    //Rivinumero
    $rivinumero=$tieto;
    break;

  case 'OFTXDELTX' :
  case 'OSTOTIL.OT_TOIMITUSTAPA' :
    //Toimitustapa
    if (trim($tieto) != '') {
      $editilaus_toimitustapa = $tieto;
    }
    break;

  case 'OSTOTIL.OT_RAHTIVAPAA' :
    // halutaanko tilaus rahtivapaana
    if ($edi_tyyppi == "magento" and $tieto == "1") {
      $editilaus_rahtivapaa = "o";
    }
    break;

  case 'OFTXGENTX' :
    //Kommentti
    $edi_kommentti .= $tieto." \n";
    break;

  case 'ODTM2__DT' :
  case 'DTM' :
    // Toimitusp‰iv‰
    if ($edi_tyyppi == "edifact911") {
      $kentat = explode(":", $tieto);
      if ($kentat[0] == 2 and $kentat[2] == 102) {
        $toimpvm = tv3dateconv($kentat[1]);
      }
    }
    else {
      $type = explode("@", $tieto);

      // 102 on p‰iv‰m‰‰r‰, 616 on viikkonumero
      if (trim($type[1]) == "102") {
        $tieto = trim($type[0]);
        $editilaus_toimitusvv = substr($tieto, 0, 4);
        $editilaus_toimituskk = substr($tieto, 4, 2);
        $editilaus_toimituspp = substr($tieto, 6, 2);
        if (checkdate($editilaus_toimituskk, $editilaus_toimituspp, $editilaus_toimitusvv)) {
          $toimpvm = "$editilaus_toimitusvv-$editilaus_toimituskk-$editilaus_toimituspp";
        }
      }
    }
    break;

  case 'OBGMINUMB' :
  case 'OSTOTIL.OT_NRO' :
    //Asiakkkaan tilausnumero
    $asiakkaan_tilausnumero = $tieto;
    $viite = $tieto. ' '; // laitetaan asiakkaan tilausnumero talteen myˆs tilausviite-kentt‰‰n
    break;

  case 'ORFFZZ1NU'  :
    // suunnistustieto ?
    $suunnistus = $tieto;
    break;

  case 'ORFFCT_NU'  :
    //Kommentteja
    $edi_kommentti .= $tieto." \n";
    break;

  case 'ORFFCR_NU'  :
    //Kommentteja
    $merkkitieto = $tieto;
    break;

  case 'OSTOTIL.OT_MAKSUEHTO' :
    //Magento keississ‰ maksuehdon koko nimi verkkokaupassa
    if ($edi_tyyppi == "magento" and $tieto != '') {

      if ($editilaus_maksuehto == "") {
        $query = "SELECT *
                  FROM maksuehto
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND teksti  = '{$tieto}'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 1) {
          $editilaus_maksuehto_row = mysql_fetch_assoc($result);
          $editilaus_maksuehto   = $editilaus_maksuehto_row['tunnus'];
        }
      }

      $editilaus_maksuteksti .= $tieto;
    }
    break;

  case 'ORFFABONU' :
  case 'OSTOTIL.OT_VIITTEEMME' :
    //Asiakkaan viite
    $viite .= $tieto. ' ';

    // laitetaan t‰m‰ myˆs kohde-kentt‰‰n jos se on ennest‰‰n tyhj‰
    if (empty($ot_verkkokauppa_kohde)) {
      $ot_verkkokauppa_kohde = $tieto;
    }

    break;

  case 'OSTOTIL.OT_TILAUSVIESTI' :
    //Asiakkaan tilausviesti
    $edi_kommentti .= $tieto." \n";
    break;

  case 'ICHG_SNDR' :
    // Otetaan asiakkaan ovttunnus talteen
    if (isset($tieto) and $tieto != "") {
      $pos = strpos($tieto, "@");
      $ovt_tunnus = substr($tieto, 0, $pos);
      $ovt_tunnus = trim($ovt_tunnus);
    }
    break;

  case 'ONADBY_RF' :
    if (substr($tieto, 0, 4) == 'IT @') {
      $tieto = substr($tieto, 4, 10) * 1;
    }
  case 'ONADCN_PC' :
    $type = explode("@", $tieto);
    $lopnumero = trim($type[0]);
  case 'ONADBY_PC' :
  case 'OSTOTIL.OT_ASIAKASNRO' :
  case 'ONADDP_PC' :
  case 'NADBY' :
  case 'NADPL' :
  case 'NADCN' :
    // ONADBY_PC - Ostajan osapuolen tunniste
    // ONADDP_PC - Toimitusosoitteen osapuolen tunniste
    // ONADCN_PC - Ostajan logistiikkakeskuksen tunniste (CN -tarkenne ei ole Itellan speksiss‰, joten t‰m‰ on itse p‰‰telty‰!)
    // ONADBY_RF - Ostajan osapuolen viite (meid‰n asiakasnumero)
    // OSTOTIL.OT_ASIAKASNRO - Ostajan meid‰n asiakasnumero

    if ($edi_tyyppi != "edifact911") {
      $tieto = trim(substr($tieto, 0, 17));
    }

    if ($tieto == "") {
      break;
    }

    if (isset($crossdock911) and $tunnus == "NADCN") {
      // Tarkistetaan onko kyseess‰ koontikuljetuskeissi
      // haetaan toimitustavan tiedot tiedot
      $toimiq = "SELECT *
                 FROM toimitustapa
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND selite  = '$arow[toimitustapa]'";
      $toimir = pupe_query($toimiq);
      $toimiro = mysql_fetch_assoc($toimir);

      // Jos kyseess‰ on koontikuljetuskeissi niin setataan v‰lietappi (terminaali/varasto)
      if ($toimiro['tulostustapa'] == "L" or $toimiro['tulostustapa'] == "K") {
        $query = "SELECT *
                  FROM asiakas
                  WHERE yhtio        = '$kukarow[yhtio]'
                  and toim_ovttunnus = '$tieto'
                  and toim_ovttunnus not in ('','0')
                  and laji           not in ('R','P')";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 1) {
          $abrow = mysql_fetch_assoc($result);
          //laskun sisviesti1 kentt‰‰n menee tavaran ensimm‰inen vastaanottaja(terminaali/varasto)
          $lopnumero = $abrow['toim_ovttunnus'];
          $lopnimi = $abrow['toim_nimi'];
          $loppostitp = $abrow['toim_postitp'];
          $loppostino = $abrow['toim_postino'];
          $loposoite = $abrow['toim_osoite'];
        }
        // Koontikeississ‰ ei haluta en‰‰ jatkaa ettei vahingossa ylikirjoiteta tilaavaa asiakasta
        break;
      }
    }

    // K‰ytet‰‰n Magenton asiakasnumeroa jos sellainen lˆytyy ennalta m‰‰ritellyist‰ kaupoista tulevilla tilauksilla
    // ja on setattu $verkkokauppa_erikoiskasittely:n
    if ($edi_tyyppi == "magento" and $ot_verkkokauppa_asiakasnro != ''
      and $verkkokauppa != '' and isset($verkkokauppa_erikoiskasittely)
      and count($verkkokauppa_erikoiskasittely) > 0) {

      $e_parametrit = hae_erikoiskaupan_parametrit($verkkokauppa);
      if (count($e_parametrit) > 0) {

        $verkkokauppamyyjanro = $e_parametrit[3];
        // Haetaan asiakasta magento_id:ll‰ avainsanoista
        if (!is_array($arow)) {
          $edi_ulos .= t("Tilaava asiakas (magento_id)")." $ot_verkkokauppa_asiakasnro -> ";

          $query = "SELECT asiakas.*
                    FROM asiakas
                    JOIN yhteyshenkilo ON asiakas.yhtio = yhteyshenkilo.yhtio
                      AND asiakas.tunnus                       = yhteyshenkilo.liitostunnus
                      AND yhteyshenkilo.rooli                  = 'Magento'
                      AND yhteyshenkilo.ulkoinen_asiakasnumero = '{$ot_verkkokauppa_asiakasnro}'
                    WHERE asiakas.yhtio                        = '{$kukarow['yhtio']}'
                    AND asiakas.laji                           not in ('R','P')";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 1) {
            $arow = mysql_fetch_assoc($result);
            // Jos myyj‰numero on spesifioitu $verkkokauppa_erikoiskasittelyyn, k‰ytet‰‰n sit‰
            if (!empty($verkkokauppamyyjanro)) $arow['myyjanro'] = $verkkokauppamyyjanro;
            $vastaustapa .= $arow['tilausvahvistus'];
            $edi_ulos .= "$arow[nimi] $arow[nimitark] $arow[toim_nimi] $arow[toim_nimitark] $arow[toim_postitp]\n";
          }
        }
      }
    }

    // Otetaan talteen
    $OT_ASIAKASNRO = $tieto;

    // ÷rum h‰kki
    if (!isset($yhtiorow) and strpos($palvelin, "orum.devlab.fi") !== FALSE) {
      $yhtiorow = hae_yhtion_parametrit("artr");

      // Copy pastettu ylh‰‰lt‰ koko setti. :(
      $kukarow['yhtio'] = $yhtiorow['yhtio']; // T‰m‰ on vaarallista, mutta t‰t‰ ilman ei mik‰n toimi!
      $kukarow['kuka']  = $edi_laatija;

      // nollataan argv ja laitetaan ensimm‰iseksi paratemetriksi yhtio, niin verkkolaskutus toimii!
      $argv       = array();
      $argv[0]    = "";
      $argv[1]    = $kukarow["yhtio"];
      $editil_cli = TRUE;

      $edi_ulos .= "VASTAANOTTTAVAN YHTION TIETO PUUTTUU! -> $yhtiorow[nimi] ($kukarow[yhtio])\n";
    }

    if (!is_array($arow)) {
      $edi_ulos .= t("Tilaava asiakas")." $tieto -> ";

      $query = "SELECT *
                FROM asiakas
                WHERE yhtio        = '$kukarow[yhtio]'
                and toim_ovttunnus = '$tieto'
                and toim_ovttunnus not in ('','0')
                and laji           not in ('R','P')";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $arow = mysql_fetch_assoc($result);
        $vastaustapa .= $arow['tilausvahvistus'];
        $edi_ulos .= "$arow[nimi] $arow[nimitark] $arow[toim_nimi] $arow[toim_nimitark] $arow[toim_postitp]\n";
      }
    }

    if (!is_array($arow) and !empty($ovt_tunnus)) {

      $query = "SELECT *
                FROM asiakas
                WHERE yhtio        = '$kukarow[yhtio]'
                and toim_ovttunnus = '$tieto'
                and toim_ovttunnus not in ('','0')
                and ovttunnus      = '$ovt_tunnus'
                and laji           not in ('R','P')";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $arow = mysql_fetch_assoc($result);
        $vastaustapa .= $arow['tilausvahvistus'];
        $edi_ulos .= "$arow[nimi] $arow[nimitark] $arow[toim_nimi] $arow[toim_nimitark] $arow[toim_postitp]\n";
      }
    }

    if (!is_array($arow)) {
      $query = "SELECT *
                FROM asiakas
                WHERE yhtio   = '$kukarow[yhtio]'
                and ovttunnus = '$tieto'
                and ovttunnus not in ('','0')
                and laji      not in ('R','P')";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $arow = mysql_fetch_assoc($result);
        $vastaustapa .= $arow['tilausvahvistus'];
        $edi_ulos .= "$arow[nimi] $arow[nimitark] $arow[toim_nimi] $arow[toim_nimitark] $arow[toim_postitp]\n";
      }
    }

    if (($tunnus == "ONADBY_RF" or $tunnus == "OSTOTIL.OT_ASIAKASNRO") and !is_array($arow)) {
      $query = "SELECT *
                FROM asiakas
                WHERE yhtio    = '$kukarow[yhtio]'
                and asiakasnro = '$tieto'
                and asiakasnro not in ('','0')
                and laji       not in ('R','P')";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $arow = mysql_fetch_assoc($result);
        $vastaustapa .= $arow['tilausvahvistus'];
        $edi_ulos .= "$arow[nimi] $arow[nimitark] $arow[toim_nimi] $arow[toim_nimitark] $arow[toim_postitp]\n";
      }
    }

    if (!is_array($arow)) {
      $edi_ulos .= t("Asiakas ei lˆydy tunnuksella")." '$tieto'\n";
    }

    // otetaan talteen kaikki eri arowt, valitaan paras lopuksi.
    if (is_array($arow) and $tunnus == 'ONADBY_RF') {
      $arow_viite = $arow;
      unset($arow);
    }
    elseif (is_array($arow) and $tunnus == 'ONADBY_PC') {
      $arow_ostaja = $arow;
      unset($arow);
    }
    elseif (is_array($arow) and $tunnus == 'ONADDP_PC') {
      $arow_toimitus = $arow;
      unset($arow);
    }
    elseif (is_array($arow) and $tunnus == 'ONADCN_PC') {
      $arow_terminaali = $arow;
      unset($arow);
    }

    break;

  case 'OSTOTIL.OT_YRITYS':
    $lnimitark = trim($tieto);
    break;

  case 'OSTOTIL.OT_ASIAKASOVT':
    $editilaus_ytunnus = $tieto;
    break;

  case 'OSTOTIL.OT_TILAUSAIKA':
    $editilaus_tilausaika = $tieto;
    $toimpvm = $tieto;
    break;

  case 'OSTOTIL.OT_KATUOSOITE':
    $losoite = trim($tieto);
    break;

  case 'OSTOTIL.OT_POSTITOIMIPAIKKA':
    $lpostitp = trim($tieto);
    break;

  case 'OSTOTIL.OT_POSTINRO':
    $lpostino = trim($tieto);
    break;

  case 'OSTOTIL.OT_YHTEYSHENKILO':
    $lnimi = trim($tieto);
    break;

  case 'OSTOTIL.OT_HYVAKSYTTAVA':
    $editilaus_hyvaksyttava = trim($tieto);
    break;

  case 'ONADPL_PC' :
    // Asiakkaan joku toimitus ovttunnus tms... t‰‰ ylikirjottaa kaiken...
    if ($tieto != "") {

      $type = explode("@", $tieto);
      $tieto = trim($type[0]);

      $edi_ulos .= t("Tilaava toim_asiakas")." $tieto -> ";

      $query = "SELECT *
                FROM asiakas
                WHERE yhtio        = '$kukarow[yhtio]'
                and toim_ovttunnus = '$tieto'
                and toim_ovttunnus not in ('','0')
                and laji           not in ('R','P')";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $arow = mysql_fetch_assoc($result);
        $vastaustapa .= $arow['tilausvahvistus'];
        $edi_ulos .= "$arow[toim_nimi]\n";
      }

    }

    break;

  case 'ONADCN_NA' :
    // lopullisen asiakkkaan nimi
    $lopnimi = $tieto;
    break;

  case 'ONADCN_SA' :
    // lopullisen asiakkkaan osoite
    $loposoite = $tieto;
    break;

  case 'ONADCN_CI' :
    // lopullisen asiakkkaan postitp
    $loppostitp = $tieto;
    break;

  case 'ONADCN_PO' :
    // lopullisen asiakkkaan postitn
    $loppostino = $tieto;
    break;

  case 'NADDP' :
    // NAD-DP segmentiss‰, tulee kerralla kaikki toimitusosoitteen tiedot
    // Toimitusosoitteen nimi-kent‰ss‰ on kovakoodattu teksti "TAVARAN VASTAANOTTAJA" (by Servinet)
    $kentat = explode("+", $tieto);

    if ($kentat[3] != "TAVARAN VASTAANOTTAJA") {
      $tnimi = $kentat[3];
      $tnimitark = "";
      $tpostitp = $kentat[5];
      $tpostino = $kentat[7];
      $tosoite = $kentat[4];

      // Jos kentt‰ ei sis‰lt‰nyt toimitusosoitetta niin koitetaan oliko siell‰ toim_ovttunnus
      if (isset($crossdock911) and (empty($tosoite) or empty($tpostino) or empty($tpostitp) or empty($tnimi))) {

        list ($toimovttunnus) = explode(':', $kentat[1]);
        if ($toimovttunnus != '') {

          $query = "SELECT *
                    FROM asiakas
                    WHERE yhtio        = '$kukarow[yhtio]'
                    and toim_ovttunnus = '$toimovttunnus'
                    and toim_ovttunnus not in ('','0')
                    and laji           not in ('R','P')";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 1) {
            $abrow = mysql_fetch_assoc($result);
            $edi_ulos .= t("Tilaus toimitetaan")." $toimovttunnus -> ";

            $tnimi     = $abrow['toim_nimi'];
            $tnimitark = $abrow['toim_nimitark'];
            $tpostitp  = $abrow['toim_postitp'];
            $tpostino  = $abrow['toim_postino'];
            $tosoite   = $abrow['toim_osoite'];

            $edi_ulos .= "$abrow[toim_nimi]\n";
          }
          else {
            $edi_ulos .= t("Toimitusosoitteita lˆytyi %s kpl tunnuksella", "", mysql_num_rows($result))." $toimovttunnus -> ";
          }
        }
      }
    }
    break;

  case 'ONADDP_NA' :
  case 'OSTOTIL.OT_TOIMITUS_NIMI' :
    // toimitusasiakkaan nimi
    $tnimi = $tieto;
    break;

  case 'ONADDP_NB' :
  case 'OSTOTIL.OT_TOIMITUS_YRITYS' :
    // toimitusasiakkaan nimitark
    $tnimitark = $tieto;
    break;

  case 'ONADDP_SA' :
  case 'OSTOTIL.OT_TOIMITUS_KATUOSOITE' :
    // toimitusasiakkaan osoite
    $tosoite = $tieto;
    break;

  case 'ONADDP_PO' :
  case 'OSTOTIL.OT_TOIMITUS_POSTINRO' :
    // toimitusasiakkaan postinro
    $tpostino = $tieto;
    break;

  case 'ONADDP_CI' :
  case 'OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA' :
    // toimitusasiakkaan postitoimipaikka
    $tpostitp = $tieto;
    break;

  case 'OSTOTIL.OT_TOIMITUS_MAAKOODI' :
    // toimitusosoitteen maakoodi
    $ltoim_maa = substr(trim($tieto), 0, 2);
    break;

  case 'OSTOTIL.OT_TOIMITUS_PUH' :
    // toimitusosoitteen puh
    $ltoim_puh = trim($tieto);
    break;

  case 'OSTOTIL.OT_TOIMITUS_EMAIL' :
    // toimitusosoitteen email
    $ltoim_email = trim($tieto);
    break;

  case 'OSTOTIL.OT_YHTEYSHENKILONPUH' :
    // yhteyshenkilˆn puhelinnumero
    $yhteyshenkilon_puhelin = trim($tieto);
    break;

  case 'OSTOTIL.OT_TOIMITUS_NOUTOPISTE_TUNNUS' :
    // noutopisteen tunnus unifaunia varten
    $noutopisteen_tunnus = trim($tieto);
    break;

  case 'OSTOTIL.OT_MAKSETTU' :
    // Jos Magenton tilaus ja osutaan erikoiskauppak‰sittelyyn
    if ($edi_tyyppi == 'magento' and $verkkokauppa != '' and isset($verkkokauppa_erikoiskasittely)
      and count($verkkokauppa_erikoiskasittely) > 0) {

      $result = hae_erikoiskaupan_parametrit($verkkokauppa);

      if (!empty($result[6])) {
        // Tyhj‰t‰‰n t‰m‰ ja haetaan maksuehto myˆhemmin asiakkaan takaa
        $tieto = '';
        break;
      }
    }

    // jos Magenton tilaus on jo maksettu luottokortilla niin p‰ivitet‰‰n maksuehto
    if ($edi_tyyppi == "magento" and ($tieto == 'processing' or $tieto == 'complete')) {
      $kukarow["kassamyyja"] = "";
      $editilaus_kassalipas  = "";

      $query = "SELECT tunnus
                FROM maksuehto
                WHERE yhtio  = '$kukarow[yhtio]'
                AND kateinen = 'o'
                AND kaytossa != 'E'
                LIMIT 1";
      $editilaus_kateinen_res = pupe_query($query);
      $editilaus_kateinen_row = mysql_fetch_assoc($editilaus_kateinen_res);

      if (mysql_num_rows($editilaus_kateinen_res) == 1) {
        $editilaus_maksuehto = $editilaus_kateinen_row["tunnus"];

        $query = "SELECT tunnus
                  FROM kassalipas
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND nimi    = 'Verkkokauppa'";
        $kassalipas_res = pupe_query($query);

        if (mysql_num_rows($kassalipas_res) == 1) {
          $kassalipas_row = mysql_fetch_assoc($kassalipas_res);
          $kukarow["kassamyyja"] = $editilaus_kassalipas = $kassalipas_row["tunnus"];
          $edi_ulos .= t("Tilaus on maksettu")."\n";
        }
        else {
          $editilaus_maksuehto = "";
          $edi_ulos .= t("Verkkokaupan kassalipasta ei lˆytynyt")."!\n";
          $edi_ulos .= t("Tilaus on maksettu, mutta j‰i avoimeksi")."!\n";
        }
      }
      else {
        $editilaus_maksuehto = "";
        $edi_ulos .= t("Luottokorttimaksuehtoa ei lˆytynyt")."!\n";
        $edi_ulos .= t("Tilaus on maksettu, mutta j‰i avoimeksi")."!\n";
      }
    }

    if ($edi_tyyppi == "magento" and $tieto == 'pending_cashondelivery_asp') {
      // t‰m‰ on j‰lkivaatimus
      $kukarow["kassamyyja"] = "";
      $editilaus_kassalipas  = "";

      $query = "SELECT tunnus
                FROM maksuehto
                WHERE yhtio = '$kukarow[yhtio]'
                AND jv      = 'o'
                ORDER BY jarjestys
                LIMIT 1";
      $editilaus_kateinen_res = pupe_query($query);
      $editilaus_kateinen_row = mysql_fetch_assoc($editilaus_kateinen_res);

      if (mysql_num_rows($editilaus_kateinen_res) == 1) {
        $editilaus_maksuehto = $editilaus_kateinen_row["tunnus"];
      }
      else {
        $editilaus_maksuehto = "";
        $edi_ulos .= t("J‰lkivaatimus-maksuehtoa ei lˆytynyt")."!\n";
      }

      $query = "SELECT selite
                FROM toimitustapa
                WHERE yhtio  = '$kukarow[yhtio]'
                AND jvkielto = ''
                ORDER BY jarjestys
                LIMIT 1";
      $editilaus_kateinen_res = pupe_query($query);
      $editilaus_kateinen_row = mysql_fetch_assoc($editilaus_kateinen_res);

      if (mysql_num_rows($editilaus_kateinen_res) == 1) {
        $editilaus_toimitustapa = $editilaus_kateinen_row["selite"];
      }
      else {
        $editilaus_toimitustapa = "";
        $edi_ulos .= t("J‰lkivaatimus-toimitustapaa ei lˆytynyt")."!\n";
      }
    }
    break;

  case 'OSTOTIL.OT_SUMMA' :
    // Kun tullaan magentosta ja meill‰ on laskun loppusumma
    // Tallennetaan t‰m‰ laskun 'hinta'-kentt‰‰n niin jos se j‰‰ riitt‰v‰n l‰helle totuutta niin pupe pyˆrist‰‰ loppusumman t‰h‰n
    if ($edi_tyyppi == "magento" and (isset($verkkokauppa_hinnatedifailista) and $verkkokauppa_hinnatedifailista == "JOO") and $tieto != '') {
      $editilaus_loppuhinta = (float) trim($tieto);
    }
    break;

  case 'OSTOTIL.OT_VIITTEENNE':
    // halutaan futur ja magento-aineistoista t‰m‰ tieto sisviesti2-kentt‰‰n
    $orig_tieto = pupesoft_cleanstring($orig_tieto, array(";"));
    $lopviesti = trim($orig_tieto);
    break;

  case 'OSTOTIL.OT_VEROMAARA':
    // magento-keisiss‰, veron m‰‰r‰
    $veromaara = (int) trim($orig_tieto);
    break;

    //Tilaus p‰‰ttyy. Aika tehd‰ se kantaan
  case 'OHDR__END' :
  case '*RE OSTOTIL' :
  case 'UNS':

    // Orders 91.1 tulee kaksi UNS segmentti‰ (D ja S). D tarkoittaa otsikko p‰‰tty, S tarkoittaa sanoma p‰‰ttyy.
    if ($edi_tyyppi == "edifact911" and substr($tieto, 0, 1) != "D") {
      break;
    }

    // valitaan paras arow!
    if (!is_array($arow)) {
      if (isset($arow_viite)) {
        $arow = $arow_viite;        // ONADBY_RF
      }
      elseif (isset($arow_terminaali)) {
        $arow = $arow_terminaali;      // ONADCN_PC
      }
      elseif (isset($arow_toimitus)) {
        $arow = $arow_toimitus;        // ONADDP_PC
      }
      elseif (isset($arow_ostaja)) {
        $arow = $arow_ostaja;        // ONADBY_PC
      }
    }

    // lis‰t‰‰n otsikko vain jos asiakas lˆytyi
    if (!is_array($arow)) {
      $edi_ulos .= t("Tilaukselta ei lˆytynyt asiakastietoja")."!\n";
      $editilaus_otsikko_ok = "EI";
    }
    elseif (!isset($yhtiorow)) {
      $edi_ulos .= t("Tilaukselta ei lˆytynyt yhtiˆtietoja")."!\n";
      $editilaus_otsikko_ok = "EI";
    }
    else {
      $editilaus_otsikko_ok = "KYLLA";

      // haetaan tomitustavan oletusmaksajan tiedot
      $apuqu = "SELECT *
                FROM toimitustapa
                WHERE yhtio = '$kukarow[yhtio]'
                AND selite  = '$arow[toimitustapa]'";
      $meapu = pupe_query($apuqu);
      $apuro = mysql_fetch_assoc($meapu);

      $maksaja = $apuro['merahti'];

      // Verkkokauppatilauksissa luotamme tiedoston laskutustietoihin
      if ($edi_tyyppi == "magento") {
        $arow["nimi"]       = $lnimi;
        $arow["nimitark"]   = $lnimitark;
        $arow["osoite"]     = $losoite;
        $arow["postitp"]    = $lpostitp;
        $arow["postino"]    = $lpostino;
        $arow["maa"]        = $ltoim_maa;
        $arow["toim_puh"]   = $ltoim_puh;
        $arow["toim_email"] = $ltoim_email;
        $arow["vienti"]     = "";

        if (!empty($editilaus_ytunnus)) {
          // jos t‰‰ll‰ tuli ovt/ytunnus, k‰ytet‰‰n sit‰
          $arow["ytunnus"] = $editilaus_ytunnus;
          $arow["ovttunnus"] = $editilaus_ytunnus;
        }

        $arow['laskutus_nimi']     = $lnimi;
        $arow['laskutus_nimitark'] = $lnimitark;
        $arow['laskutus_osoite']   = $losoite;
        $arow['laskutus_postitp']  = $lpostitp;
        $arow['laskutus_postino']  = $lpostino;
        $arow['laskutus_maa']      = $ltoim_maa;

        // jos Magento tilauksella on erillinen tilausyhteyshenkilˆ, niin
        // laitetaan se tilauksen yhteyshenkilˆksi, mik‰li ei ole yritys ($lnimitark)
        if (empty($lnimitark)) {
          $tilausyhteyshenkilo = $lnimi;
        }

        if ($arow["maa"] != "" and $arow["maa"] != $yhtiorow["maa"]) {
          // katsotaan mimmosesta viennist‰ on kysymys
          $maa_eu_query = "SELECT DISTINCT eu
                           FROM maat
                           WHERE koodi = '$arow[maa]'";
          $maa_eu_res = pupe_query($maa_eu_query);
          $maa_eu_row = mysql_fetch_assoc($maa_eu_res);

          if ($maa_eu_row["eu"] == "") {
            $edi_ulos .= t("Vientitilaus EU:n ulkopuolelle")."\n";

            $arow["vienti"] = "K";
            $arow["alv"] = 0;
          }
          else {
            $edi_ulos .= t("Vientitilaus EU -maahan")."\n";

            if ($arow["laji"] != "H") {
              $arow["alv"] = 0;
              $arow["vienti"] = "E";
            }
          }
        }
      }

      $til_vienti = $arow["vienti"];

      if (trim($arow['laskutus_nimi']) == '') {
        $arow['laskutus_nimi']     = $arow['nimi'];
        $arow['laskutus_nimitark'] = $arow['nimitark'];
        $arow['laskutus_osoite']   = $arow['osoite'];
        $arow['laskutus_postitp']  = $arow['postitp'];
        $arow['laskutus_postino']  = $arow['postino'];
        $arow['laskutus_maa']      = $arow['maa'];
      }

      // jos asiakkalla ei ole toimitusosoitetietoja, kopsataan laskutustiedot
      if (trim($arow['toim_nimi']) == "") {
        $arow['toim_nimi']      = $arow['nimi'];
        $arow['toim_nimitark']  = $arow['nimitark'];
        $arow['toim_osoite']    = $arow['osoite'];
        $arow['toim_postitp']   = $arow['postitp'];
        $arow['toim_postino']   = $arow['postino'];
        $arow['toim_maa']       = $arow['maa'];
      }

      // jos tilauksessa on tullut poikkeava nimi, postino ja postitp otetaan kaikki tiedot tilaukselta (PAITSI JO KYSESSƒ ON KOONTIKULJETUSKEISSI)
      if (isset($crossdock911) or ($tnimi != "" or ($tnimi == "" and $tnimitark != "")) and $tpostino != "" and $tpostitp != "" and $apuro["tulostustapa"] != "K" and $apuro["tulostustapa"] != "L") {
        if ($tnimi == "" and $tnimitark != "") {
          $tnimi = $tnimitark;
          $tnimitark = "";
        }

        $arow['toim_nimi']     = $tnimi;
        $arow['toim_nimitark'] = $tnimitark;
        $arow['toim_osoite']   = $tosoite;
        $arow['toim_postino']  = $tpostino;
        $arow['toim_postitp']  = $tpostitp;
      }

      // jos tilauksella ei ole maksuehtoa, k‰ytet‰‰n asiakkaan oletusta
      if ($editilaus_maksuehto == '') {
        $editilaus_maksuehto = $arow["maksuehto"];
      }

      if (trim($editilaus_toimitustapa) != '') {
        // tarkistetaan lˆytyykˆ toimitustapa pupesta suoraan
        if ($edi_tyyppi == "magento") {

          // Lˆytyykˆ k‰‰nnˆstaulukosta?
          $query = "SELECT selite
                    FROM avainsana
                    WHERE yhtio  = '{$kukarow['yhtio']}'
                    AND laji = 'TOIMTAPAVKN'
                    AND selitetark = '{$editilaus_toimitustapa}'
                    AND selite != ''
                    AND selitetark != ''";
          $ressu = pupe_query($query);

          if (mysql_num_rows($ressu) == 1) {
            $ftc = mysql_fetch_assoc($ressu);
            $editilaus_toimitustapa = $ftc['selite'];
          }

          $query = "SELECT *
                    FROM toimitustapa
                    WHERE yhtio  = '{$kukarow['yhtio']}'
                    AND selite   = '{$editilaus_toimitustapa}'
                    AND selite  != ''
                    ORDER BY muutospvm DESC
                    LIMIT 1";
          $ressu = pupe_query($query);

          // - jos lˆytyy niin k‰ytet‰‰n sit‰
          if (mysql_num_rows($ressu) == 1) {
            $ftc = mysql_fetch_assoc($ressu);
            $editilaus_toimitustapa = $ftc['selite'];

            $edi_ulos .= t("Valittu asiakkaan pyyt‰m‰ toimitustapa")." {$editilaus_toimitustapa}\n";
          }
          else {
            $edi_ulos .= t("Ei lˆydetty asiakkaan pyyt‰m‰‰ toimitustapaa")." {$editilaus_toimitustapa}\n";

            $editilaus_toimitustapa = '';
          }
        }
        else {
          $query = "SELECT *
                    FROM asiakkaan_avainsanat
                    WHERE yhtio       = '{$kukarow['yhtio']}'
                    AND tarkenne      = '{$editilaus_toimitustapa}'
                    AND laji          = 'editilaus_toimitustapa'
                    AND tarkenne     != ''
                    AND avainsana    != ''
                    AND liitostunnus  = '{$arow['tunnus']}'
                    ORDER BY muutospvm DESC
                    LIMIT 1";
          $toimitustapa_chk_res = pupe_query($query);

          if (mysql_num_rows($toimitustapa_chk_res) == 1) {
            $toimitustapa_chk_row = mysql_fetch_assoc($toimitustapa_chk_res);
            $editilaus_toimitustapa = $toimitustapa_chk_row['avainsana'];
          }
          else {
            $edi_kommentti .= t("Asiakas pyysi toimitustapaa", $arow["kieli"]).": $editilaus_toimitustapa \n";
            $editilaus_toimitustapa = '';
          }
        }
      }

      // jos tilauksella ei ole toimitustapaa, k‰ytet‰‰n asiakkaan oletusta
      if ($editilaus_toimitustapa == '') {
        $editilaus_toimitustapa = $arow["toimitustapa"];
      }

      $rahtivapaus = hae_erikoiskaupan_parametrit($verkkokauppa);
      $rahtivapaus = $rahtivapaus[5];

      // jos ei olla pakotettu rahtivapaata, tai poistettu erikoisparametreiss‰ rahtivapautta,
      // niin otetaan asiakkaan oletus
      if ($editilaus_rahtivapaa == '' or $rahtivapaus == "E") {
        $editilaus_rahtivapaa = $arow["rahtivapaa"];
      }

      // katsotaan magentotilauksilta tilaustyyppi
      if ($edi_tyyppi == "magento" and $editilaus_tilaustyyppi != "") {
        // tyyppi 9, ohitetaan aina varastoprosessi ja aina sis‰inen
        if ($editilaus_tilaustyyppi == "9") {
          $editilaus_eilahetetta = "o";
          $editilaus_sisainen = "o";
        }
        elseif ($editilaus_tilaustyyppi == 'K') {
          $editilaus_tila   = 'N';
          $editilaus_alatila   = '';
        }
      }

      $tilaustyyppilisa = '';

      if ($edi_tyyppi == "magento" and $verkkokauppa != '' and isset($verkkokauppa_erikoiskasittely) and count($verkkokauppa_erikoiskasittely) > 0) {
        list($nimi, $editilaus_tilaustyyppi, $tilaustyyppilisa, $editilaus_myyja) = hae_erikoiskaupan_parametrit($verkkokauppa);

        // Jos lˆytyy erikoisk‰sittely niin n‰ytet‰‰n se myˆs k‰yttˆliittym‰ss‰
        if (isset($nimi) and !empty($nimi)) {
          $edi_ulos .= t("Tilauksen erikoisk‰sittely")."-> {$nimi},
              ".t("tilaustyyppi").": {$editilaus_tilaustyyppi},
              ".t("tilaustyyppilis‰").": {$tilaustyyppilisa},
              ".t("myyj‰numero").": {$editilaus_myyja} \n\n";
        }
      }

      // jos tilauksella on tullut lopullisen asikkaan tiedot, laitetaan ne talteen sisviesti ykkˆseen
      if ($lopnimi != "") {
        $arow["sisviesti1"] = "$lopnumero\n$lopnimi\n$loposoite\n$loppostino\n$loppostitp";

        // jos kyseess‰ on terminaalitoimitus
        if ($editilaus_tila == "Z") {
          $lopviesti = "$lopnumero#$lopnimi"; // laitetaan viel‰ numero ja nimi omaan paikkaan..
        }
      }

      // jos tilauksella oli toivottu, ett‰ laskua *EI SAA* ketjuttaa ni tehd‰‰n niin
      if ($ketjutus != "") {
        $arow["ketjutus"] = $ketjutus;
      }

      $edimyyja = "";

      // Haetaan viel‰ myyyj‰numero
      $query = "SELECT tunnus
                FROM kuka
                WHERE yhtio = '$kukarow[yhtio]'
                and kuka    = 'EDI'";
      $meapu = pupe_query($query);

      if (mysql_num_rows($meapu) == 1) {
        $apuro = mysql_fetch_assoc($meapu);
        $edimyyja = $apuro['tunnus'];
      }
      elseif ($arow["myyjanro"] != "") {
        $query = "SELECT tunnus
                  FROM kuka
                  WHERE yhtio = '$kukarow[yhtio]'
                  and myyja   = '$arow[myyjanro]'";
        $meapu = pupe_query($query);

        if (mysql_num_rows($meapu) == 1) {
          $apuro = mysql_fetch_assoc($meapu);
          $edimyyja = $apuro['tunnus'];
        }
      }

      if ($yhtiorow["splittauskielto"] == "E") {
        $splittauskielto = "E";
      }
      else {
        $splittauskielto = "";
      }

      if ($edi_tyyppi == 'futursoft' and ($editilaus_tilaustyyppi == 2 or $editilaus_tilaustyyppi == 3)) {
        $tilaustyyppilisa = $editilaus_tilaustyyppi;

        if ($editilaus_tilaustyyppi == 3) {
          $editilaus_tila   = 'C';
          $editilaus_alatila   = 'A';
          $tilaustyyppilisa   = 'R';
        }
      }

      // Tallennetaan ohjelmamoduli
      $ohjelmamoduli = strtoupper($edi_tyyppi);

      // Rahtivapaat tilaukset l‰hetet‰‰n aina l‰hett‰j‰n rahtisopimuksella
      if ($maksaja == "" and $editilaus_rahtivapaa != "") {
        $maksaja = "K";
      }

      $prioriteettinro = t_avainsana("ASIAKASLUOKKA", "", " and avainsana.selite='{$arow['luokka']}'", "", "", "selitetark_3");

      // Default 9 myˆs kannassa, niin laitetaan t‰nnekki 9 jos avainsanoista ei lˆydy prioa
      if (!is_numeric($prioriteettinro)) $prioriteettinro = 9;

      // Oletuksena n‰in Futursoft keississ‰
      if ($edi_tyyppi == 'futursoft') {
        $ohjausmerkki = $lopviesti;
      }

      if ($tilaustyyppilisa == 2) {
        $ohjausmerkki = 'VARASTOTƒYDENNYS';
      }
      elseif (trim($ohjausmerkki) != "") {
        $_ohjausmerkki = $ohjausmerkki;
        $_ohjausmerkki = str_replace(array(" ", ",", ".", "-", "/", "+", ":"), "", $_ohjausmerkki);
        $_ohjausmerkki = strtolower($_ohjausmerkki);
        $_ohjausmerkki = str_replace(array("‰", "ƒ"), "a", $_ohjausmerkki);
        $_ohjausmerkki = str_replace(array("ˆ", "÷"), "o", $_ohjausmerkki);

        // FRSTTT = varastotayd joka on minimiss‰‰n se mik‰ hyv‰ksyt‰‰n varastot‰ydennys tekstin‰
        if (metaphone($_ohjausmerkki, 6) == 'FRSTTT') {
          $ohjausmerkki     = 'VARASTOTƒYDENNYS';
          $tilaustyyppilisa = 2;
        }
      }

      if ($arow["myynti_kommentti1"] != "") {
        $edi_kommentti .= $arow["myynti_kommentti1"]." \n";
      }

      $edi_kommentti = trim($edi_kommentti);

      if (empty($tilausyhteyshenkilo)) {
        // Onko asiakkaalla oletusyhteyshenkilˆ?
        $yhteysquery = "SELECT nimi
                        FROM yhteyshenkilo
                        where yhtio              = '$kukarow[yhtio]'
                        and liitostunnus         = '$arow[tunnus]'
                        and tyyppi               = 'A'
                        and tilausyhteyshenkilo != ''
                        and oletusyhteyshenkilo != ''
                        ORDER BY nimi
                        LIMIT 1";
        $yhteysresult = pupe_query($yhteysquery);

        if ($yhteysrow = mysql_fetch_assoc($yhteysresult)) {
          $tilausyhteyshenkilo = $yhteysrow['nimi'];
        }
      }

      if ($arow['toimipaikka'] != 0) {
        $kukarow_toimipaikka_tmp = $kukarow['toimipaikka'];
        $kukarow['toimipaikka'] = $arow['toimipaikka'];
        $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

        if ($yhtiorow['myyntitilauksen_toimipaikka'] == '') {
          $kukarow['toimipaikka'] = $kukarow_toimipaikka_tmp;
          $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
        }
      }

      $toimitetaan_tanne = (isset($toimovttunnus) and !empty($toimovttunnus)) ? $toimovttunnus : $arow['toim_ovttunnus'];

      $varasto = array();

      if ($edi_tyyppi == "magento") {
        // N‰m‰ ovat ainoat varastot joista verkkokauppa saa myyd‰
        if ($verkkokauppavarasto == "") {
          $query  = "SELECT GROUP_CONCAT(tunnus) tunnukset
                     FROM varastopaikat
                     WHERE yhtio      = '$kukarow[yhtio]'
                     AND prioriteetti > 0 AND tyyppi != 'P'";
          $pres = pupe_query($query);
          $prow = mysql_fetch_assoc($pres);

          if ($prow["tunnukset"] != "") {
            $verkkokauppavarasto   = explode(",", $prow["tunnukset"]);
            $verkkokauppavarasto_li = " and varastopaikat.tunnus in ($prow[tunnukset]) ";
          }

        }
        $varasto = $verkkokauppavarasto;
      }
      elseif ($yhtiorow['myyntitilauksen_toimipaikka'] == 'A' and $kukarow['toimipaikka'] == 0) {
        $query = "SELECT *
                  FROM varastopaikat
                  WHERE yhtio     = '{$kukarow['yhtio']}'
                  AND tyyppi      = ''
                  AND toimipaikka = 0";
        $varresult = pupe_query($query);

        while ($varrow = mysql_fetch_assoc($varresult)) {
          array_push($varasto, $varrow['tunnus']);
        }
      }
      elseif ($yhtiorow['myyntitilauksen_toimipaikka'] == 'A') {

        $args = array(
          'asiakas_tunnus' => $arow['tunnus'],
          'toimipaikka_tunnus' => $kukarow['toimipaikka'],
          'toim' => "",
          'tilaustyyppi' => $editilaus_tilaustyyppi,
          'varasto' => "",
          'toimitus_maa' => $arow['maa'],
        );

        list($oletus_varasto, $varasto) = palauta_varasto($args);
        $_onko_varasto_setattu = (isset($oletus_varasto) and $oletus_varasto != '0' and is_numeric($oletus_varasto));

        array_unshift($varasto, $oletus_varasto);

        if ($_onko_varasto_setattu) {

          $varasto = array($oletus_varasto);

          $v_toimipaikka = hae_varaston_toimipaikka($oletus_varasto);

          if ($v_toimipaikka['tunnus'] != $kukarow['toimipaikka']) {
            $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio'], $v_toimipaikka['tunnus']);
          }
        }
      }

      if ($yhtiorow['myyntitilaus_osatoimitus'] == 'E') {
        $editilaus_osatoimitus = 'o';
      }

      if ($edi_tyyppi != "magento" and $yhtiorow['lahetteen_tulostustapa'] == "I") {
        $editilaus_eilahetetta = "o";
        $editilaus_alatila = 'F';
      }

      if ($editilaus_hyvaksyttava != "") {
        $editilaus_alatila = 'F';
      }

      // Jos asiakas on syˆtt‰nyt verkkokaupassa oman tilausnumeron tai viitteen yliajetaan viesti
      if (trim($ot_verkkokauppa_tilausviesti) != "") {
        $viite = $ot_verkkokauppa_tilausviesti;
      }

      // T‰ss‰ hallitaan se tapaus jos varasto muuttuja onkin tyhj‰
      $insertvarasto = array(0);
      if (!empty($varasto)) {
        $insertvarasto = $varasto;
      }

      // Jos toim.puhelinnro tulee OSTOTIL.OT_YHTEYSHENKILONPUH:ss‰
      if ($yhteyshenkilon_puhelin != '' and $ltoim_puh == '') {
        $tpuh = $yhteyshenkilon_puhelin;
      }
      else {
        $tpuh = $arow['toim_puh'];
      }

      // Katsotaan verkkokauppatapauksessa tilauksen valuutta
      if ($edi_tyyppi == 'magento'
        and isset($verkkokauppa_hinnatedifailista) and $verkkokauppa_hinnatedifailista == "JOO"
        and !empty($editilaus_valuuttakoodi)) {
        // escapetaan
        $valkoodi = mysql_real_escape_string($editilaus_valuuttakoodi);

        $query = "SELECT tunnus, kurssi
                  FROM valuu
                  WHERE yhtio = '{$kukarow["yhtio"]}'
                  AND nimi    = '{$valkoodi}'";
        $valuu_result = pupe_query($query);

        if (mysql_num_rows($valuu_result) != 1) {
          $valkoodi = $yhtiorow['valkoodi'];
          $valkurssi = 1;
        }
        else {
          $valrow = mysql_fetch_assoc($valuu_result);
          $valkurssi = $valrow['kurssi'];
        }
      }
      else {
        $valkoodi = $yhtiorow['valkoodi'];
        $valkurssi = 1;
      }

      // Lis‰t‰‰n otsikko
      $query = "INSERT into lasku SET
                aktiivinen_kuljetus     = '$suunnistus',
                alatila                 = '$editilaus_alatila',
                alv                     = '$arow[alv]',
                alv_tili                = '',
                asiakkaan_tilausnumero  = '$asiakkaan_tilausnumero',
                chn                     = '$arow[chn]',
                clearing                = '',
                comments                = '$edi_kommentti',
                eilahetetta             = '$editilaus_eilahetetta',
                erikoisale              = '$arow[erikoisale]',
                erpcm                   = '',
                hinta                   = '$editilaus_loppuhinta',
                huolitsija              = '',
                hyvaksynnanmuutos       = '{$arow['luokka']}',
                jakelu                  = '',
                jaksotettu              = '',
                jtkielto                = '$arow[jtkielto]',
                kassalipas              = '$editilaus_kassalipas',
                kauppatapahtuman_luonne = '$arow[kauppatapahtuman_luonne]',
                kerayspvm               = '$toimpvm',
                keraysvko               = '',
                ketjutus                = '$arow[ketjutus]',
                kohde                   = '$ot_verkkokauppa_kohde',
                kohdistettu             = '$maksaja',
                kuljetus                = '',
                kuljetusmuoto           = '$arow[kuljetusmuoto]',
                laatija                 = '$edi_laatija',
                laskutusvkopv           = '$arow[laskutusvkopv]',
                laskutyyppi             = '$arow[laskutyyppi]',
                liitostunnus            = '$arow[tunnus]',
                luontiaika              =  now(),
                maa                     = '$arow[maa]',
                maa_maara               = '$arow[maa_maara]',
                maksuehto               = '$editilaus_maksuehto',
                maksuteksti             = '$editilaus_maksuteksti',
                myyja                   = '$edimyyja',
                nimi                    = '$arow[nimi]',
                nimitark                = '$arow[nimitark]',
                ohjausmerkki            = '{$ohjausmerkki}',
                olmapvm                 = '',
                osatoimitus             = '{$editilaus_osatoimitus}',
                osoite                  = '$arow[osoite]',
                ovttunnus               = '$arow[ovttunnus]',
                piiri                   = '$arow[piiri]',
                postino                 = '$arow[postino]',
                postitp                 = '$arow[postitp]',
                prioriteettinro         = '{$prioriteettinro}',
                rahtivapaa              = '$editilaus_rahtivapaa',
                sisainen                = '$editilaus_sisainen',
                sisviesti1              = '$arow[sisviesti1]',
                sisviesti2              = '$lopviesti',
                sisviesti3              = '{$merkkitieto}',
                splittauskielto         = '$splittauskielto',
                tila                    = '$editilaus_tila',
                tilaustyyppi            = '$tilaustyyppilisa',
                tilausvahvistus         = '$vastaustapa',
                tilausyhteyshenkilo     = '$tilausyhteyshenkilo',
                toimaika                = '$toimpvm',
                toimitusehto            = '$arow[toimitusehto]',
                toimitustapa            = '$editilaus_toimitustapa',
                toimvko                 = '',
                toim_maa                = '$arow[toim_maa]',
                toim_puh                = '$tpuh',
                toim_nimi               = '$arow[toim_nimi]',
                toim_nimitark           = '$arow[toim_nimitark]',
                toim_osoite             = '$arow[toim_osoite]',
                toim_ovttunnus          = '$toimitetaan_tanne',
                toim_postino            = '$arow[toim_postino]',
                toim_postitp            = '$arow[toim_postitp]',
                toim_email              = '$arow[toim_email]',
                tunnusnippu             = '',
                valkoodi                = '{$valkoodi}',
                varasto                 = '{$insertvarasto[0]}',
                verkkotunnus            = '$arow[verkkotunnus]',
                vienti                  = '$til_vienti',
                vienti_kurssi           = '{$valkurssi}',
                viesti                  = '$viite',
                viikorkopros            = '$yhtiorow[viivastyskorko]',
                yhtio                   = '$kukarow[yhtio]',
                yhtio_kotipaikka        = '$yhtiorow[kotipaikka]',
                yhtio_maa               = '$yhtiorow[maa]',
                yhtio_nimi              = '$yhtiorow[nimi]',
                yhtio_osoite            = '$yhtiorow[osoite]',
                yhtio_ovttunnus         = '$yhtiorow[ovttunnus]',
                yhtio_postino           = '$yhtiorow[postino]',
                yhtio_postitp           = '$yhtiorow[postitp]',
                yhtio_toimipaikka       = '$kukarow[toimipaikka]',
                ytunnus                 = '$arow[ytunnus]',
                ohjelma_moduli          = '$ohjelmamoduli'";
      $result = pupe_query($query);
      $id = mysql_insert_id($GLOBALS["masterlink"]);

      $query = "INSERT INTO laskun_lisatiedot SET
                laskutus_nimi       = '$arow[laskutus_nimi]',
                laskutus_nimitark   = '$arow[laskutus_nimitark]',
                laskutus_osoite     = '$arow[laskutus_osoite]',
                laskutus_postitp    = '$arow[laskutus_postitp]',
                laskutus_postino    = '$arow[laskutus_postino]',
                laskutus_maa        = '$arow[laskutus_maa]',
                noutopisteen_tunnus = '{$noutopisteen_tunnus}',
                laatija             = '$edi_laatija',
                luontiaika          = now(),
                yhtio               = '$kukarow[yhtio]',
                otunnus             = '$id'";
      $lisatiedot_result = pupe_query($query);

      if ($edi_tyyppi == 'futursoft' and $editilaus_tilaustyyppi == 3) {
        $query = "INSERT INTO tyomaarays SET
                  yhtio   = '{$kukarow['yhtio']}',
                  otunnus = '{$id}'";
        $tyomaarays_ins_res = pupe_query($query);
      }

      $edi_ulos .= t("Tilausnumero")." $id\n\n";

      // haetaan tilauksen tiedot
      $query    = "SELECT *
                   FROM lasku
                   WHERE tunnus = '$id'
                   AND yhtio    = '$kukarow[yhtio]'";
      $result   = pupe_query($query);
      $laskurow = mysql_fetch_assoc($result);

      //vientikieltotarkistus
      $query  = "SELECT if(toim_maa != '', toim_maa, maa) maa
                 FROM asiakas
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$laskurow[liitostunnus]'";
      $vieres = pupe_query($query);
      $vierow = mysql_fetch_assoc($vieres);

      if (isset($vierow) and $vierow["maa"] != "") {
        $kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
      }
      else {
        $kieltolisa = "";
      }

      // Tallennetaan alkuper‰inen aineisto tilauksen liitetiedostoksi
      $liitefile = file_get_contents($mailfile);
      $filename  = basename($mailfile);
      $liitefile = mysql_real_escape_string($liitefile);
      $fileslite = t("Tilausnumero")." $id";

      $query = "INSERT INTO liitetiedostot SET
                yhtio           = '$kukarow[yhtio]',
                liitos          = 'lasku',
                liitostunnus    = '$id',
                data            = '$liitefile',
                selite          = '$fileslite',
                kieli           = '$yhtiorow[kieli]',
                filename        = '$filename',
                filesize        = length(data),
                filetype        = 'text/plain',
                image_width     = '',
                image_height    = '',
                image_bits      = '',
                image_channels  = '',
                kayttotarkoitus = 'EDI',
                jarjestys       = '1',
                laatija         = '$edi_laatija',
                luontiaika      = now()";
      $Xresult = pupe_query($query);

      // jos meill‰ on liitetiedosto tilauksella, tallennetaan se tilauksen liitetiedostoksi
      if (!empty($editilaus_liitetiedosto)) {
        // liitetiedostot tulee siirt‰‰ editilaus -hakemiston alle liitetiedostot -hakemistoon
        $full_path = "{$editilaus_hakemisto}/liitetiedostot/{$editilaus_liitetiedosto}";

        if (is_readable($full_path)) {
          // params: filename, liitos, liitostunnus, selite, k‰yttˆtarkoitus
          tallenna_liite($full_path, 'lasku', $id, 'Asiakkaan l‰hett‰m‰ liite', 'VK');

          // poistetaan liitetiedosto hakemistosta
          unlink($full_path);

          $edi_ulos .= t("Tallennettiin liite")." {$editilaus_liitetiedosto}\n";
        }
        else {
          $edi_ulos .= t("Ilmoitettua liitetiedostoa ei lˆytynyt")." {$editilaus_liitetiedosto}\n";
        }
      }

      // HUOM: Laitetaan messu myyntiin jos asiakkaan nimi meid‰n kannassa on eri kuin tilaukselta tullut nimi
      if ($lnimitark != "" and substr($lnimitark, 0, 7) != "Autoasi" and $arow["yhtio"] == "artr" and gethostname() != 'demo.devlab.fi') {

        $asnokonflikti = "";

        // Kauttalaskutus
        if (strtoupper($arow["toim_nimi"]) != strtoupper($lnimitark) and $arow["yhtio"] == "artr" and $arow["osasto"] == "6") {
          $asnokonflikti = $arow["toim_nimi"];
        }
        elseif (strtoupper($arow["nimi"]) != strtoupper($lnimitark)) {
          $asnokonflikti = $arow["nimi"];
        }

        if ($asnokonflikti != "") {
          // l‰hetet‰‰n meili
          $bound   = uniqid(time()."_") ;

          $header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
          $header .= "MIME-Version: 1.0\n" ;
          $header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

          $mail  = "--$bound\n";
          $mail .= "Content-Type: text/plain;charset=iso-8859-1\n" ;
          $mail .= "Content-Transfer-Encoding: quoted-printable\n";
          $mail .= "\n";
          $mail .= "Tilausnumero: $id\n";
          $mail .= "Pvm: ".date("d.m.Y")."\n\n";
          $mail .= "Asiakkaan nimi Futursoft-aineistossa: $lnimitark\n";
          $mail .= "Asiakasnumero Futursoft-aineistossa: $OT_ASIAKASNRO\n\n";
          $mail .= "Asiakkaan nimi Pupesoftissa: $asnokonflikti\n";
          $mail .= "Asiakasnumero Pupesoftissa: $arow[asiakasnro]\n";
          $mail .= "--$bound--\n";

          //$boob = mail("myynti@orum.fi,messages@devlab.fi", mb_encode_mimeheader("Asiakasnumero-konflikti Futursoft-tilauksen sis‰‰nluvussa", "ISO-8859-1", "Q"), $mail, $header, "-f $yhtiorow[postittaja_email]");
        }
      }
    }

    break;

  case 'OLINSA_NU' :
  case 'OSTOTILRIV.OTR_TUOTEKOODI' :
  case 'PIA':
    //Tuotenumero

    if ($edi_tyyppi == "edifact911") {
      $kaikki_kentat = explode(":", $tieto);
      $kentta = explode("+", $kaikki_kentat[1]);
      $tieto = $kentta[1];
    }

    // jos ei olla saatu tuoteinfoa aikasemmin, kokeillaan etsi‰ tuote tuotenumerolla
    if ($tuoteno == "" and $tieto != "") {

      if ($edi_tyyppi != "magento" and $edi_tyyppi != "futursoft" and $edi_tyyppi != "edifact911" and $yhtiorow["yhtio"] == "artr") {
        // poistetaan jos kolmas merkki jos se on viiva
        if (substr($tieto, 2, 1) == '-') {
          $tieto = substr($tieto, 0, 2) . substr($tieto, 3, 15);
        }
      }

      // Poistetaan spacet
      if (in_array($yhtiorow["yhtio"], array("allr", "mast"))) {
        $tieto = str_replace(" ", "", $tieto);
      }

      if (strpos($tieto, "@") !== FALSE) {
        list($tieto) = explode("@", $tieto);
        $tieto = trim($tieto);
      }

      // Spessukase
      if ($yhtiorow["yhtio"] == "artr") {
        // 1. Etsit‰‰n aktiivisia tuotteita kaikki spacet poistettuina
        $query = "SELECT *
                  FROM tuote
                  WHERE yhtio  = '{$kukarow["yhtio"]}'
                  and tuoteno  = '{$tieto}'
                  and status  != 'P'
                  {$kieltolisa}";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 0) {
          // Laitetaan kolmanneksi merkiksi space...
          $tieto = substr($tieto, 0, 2)." ".substr($tieto, 2);

          // 2. Laitetaan kolmanneksi merkiksi space ja tsekataan lˆytyykˆ aktiivinen
          $query = "SELECT *
                    FROM tuote
                    WHERE yhtio  = '{$kukarow["yhtio"]}'
                    and tuoteno  = '{$tieto}'
                    and status  != 'P'
                    {$kieltolisa}";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 0) {
            // Poistetaan kolmannesta merkist‰ space...
            $tieto = substr($tieto, 0, 2).substr($tieto, 3);

            // 3. Poistetaan kolmannesta merkist‰ space ja tsekataan lˆytyykˆ poistettu
            $query = "SELECT *
                      FROM tuote
                      WHERE yhtio = '{$kukarow["yhtio"]}'
                      and tuoteno = '{$tieto}'
                      {$kieltolisa}";
            $result = pupe_query($query);

            if (mysql_num_rows($result) == 0) {
              // Laitetaan kolmanneksi merkiksi space...
              $tieto = substr($tieto, 0, 2)." ".substr($tieto, 2);

              // 4. Laitetaan kolmanneksi merkiksi space ja tsekataan lˆytyykˆ poistettu
              $query = "SELECT *
                        FROM tuote
                        WHERE yhtio = '{$kukarow["yhtio"]}'
                        and tuoteno = '{$tieto}'
                        {$kieltolisa}";
              $result = pupe_query($query);

              if (mysql_num_rows($result) == 0) {
                // Jos ei mill‰‰n lˆydy niin laitetaan ilman spaceja eteenp‰in...
                $tieto = substr($tieto, 0, 2).substr($tieto, 3);
              }
            }
          }
        }

        // spessukeissi, hinnastoon E tuotteita ei lˆydet‰
        $kieltolisa .= " and hinnastoon != 'E'";
      }

      $query = "SELECT *
                FROM tuote
                WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
                and tuote.tuoteno = '{$tieto}'
                {$kieltolisa}";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        // Spessukase
        if ($yhtiorow["yhtio"] == "mast" and $arow["kerayserat"] == 'H') {
          $pos = strpos($tieto, "-EU");
          if ($pos > 0) {
            $tuotetieto = substring($tieto, 0, $pos - 1);
            $query = "SELECT *
                      FROM tuote
                      WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
                      and tuote.tuoteno = '{$tuotetieto}'
                      {$kieltolisa}";
            $result = pupe_query($query);

            if (mysql_num_rows($result) == 0) {
              ///* Lis‰t‰‰n tilaukselle dummytuote ja kommentti *///
              $edi_ulos .= t("Rivi")." $rivinumero ".t("Tuote")." '$tieto' ".t("ei lˆydy")."!\n";
              $tuoteerrortxt .= " $tieto";
              $tuoteerror = 1;
            }
          }
        }
      } 
      if (mysql_num_rows($result) == 0) {     
        ///* Lis‰t‰‰n tilaukselle dummytuote ja kommentti *///
        $edi_ulos .= t("Rivi")." $rivinumero ".t("Tuote")." '$tieto' ".t("ei lˆydy")."!\n";
        $tuoteerrortxt .= " $tieto";
        $tuoteerror = 1;
      }
      else {
        $trow = mysql_fetch_assoc($result);

        if ($trow['panttitili'] == 'K') {
          $query = "SELECT *
                    from tuoteperhe
                    where tuoteperhe.yhtio       = '{$kukarow["yhtio"]}'
                    and tuoteperhe.tuoteno       = '{$trow["tuoteno"]}'
                    and tuoteperhe.ohita_kerays != ''";
          $result = pupe_query($query);

          // Runkotuotteita ei saa tilata suoraan!
          if (mysql_num_rows($result) != 0) {
            ///* Lis‰t‰‰n tilaukselle kommentti *///
            $edi_ulos .= t("Rivi")." $rivinumero ".t("Tuote")." '$tieto' ".t("on runko")."!\n";
            $tuoteerrortxt .= " $tieto";
            $tuoteno = $trow['tuoteno'];
            $tuoteerror = 2;
          }
          else {
            $edi_ulos .= t("Rivi")." $rivinumero ".t("Tuote")." $tieto ";
            $tuoteno = $trow['tuoteno'];
            $tuoteerror = 0; // tuote ok
          }
        }
        else {
          $edi_ulos .= t("Rivi")." $rivinumero ".t("Tuote")." $tieto ";
          $tuoteno = $trow['tuoteno'];
          $tuoteerror = 0; // tuote ok
        }
      }
    }
    break;

  case 'OLINSA_PI' :
    // t‰m‰n rivin asiakkaan oma tuotenumero
    $type     = explode("@", $tieto);
    $olinsapi = trim($type[0]);
    $varasto  = trim($type[2]); // kolmannessa segmentiss‰ tulee meid‰n varastopaikka jos l‰hett‰j‰ tiet‰‰ sen
    break;

  case 'OLINEN_NU' :
  case 'OSTOTILRIV.OTR_EANKOODI' :
  case 'LIN':
    // tuotteen EAN-koodi

    if ($edi_tyyppi == "edifact911") {
      $kaikki_kentat = explode("++", $tieto);
      $ean_kentta = explode(':', $kaikki_kentat[1]);
      $tieto = $ean_kentta[0];
      $rivinumero = $kaikki_kentat[0]; // T‰st‰ segmentist‰ saadaan myˆs rivinumero
    }

    // jos ei olla saatu tuoteinfoa aikasemmin, kokeillaan etsi‰ tuote EAN koodilla
    if ($tuoteno == "" and $tieto != "") {

      $query = "SELECT *
                FROM tuote USE INDEX (yhtio_eankoodi_index)
                WHERE yhtio  = '$kukarow[yhtio]'
                and eankoodi = '$tieto'
                $kieltolisa
                ORDER BY tunnus";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        // Spessukase
        if ($yhtiorow["yhtio"] == "mast" and $arow["kerayserat"] == 'H') {
          $query2 = "SELECT *
                    FROM tuote USE INDEX (yhtio_eankoodi_index)
                    WHERE yhtio  = '$kukarow[yhtio]'
                    and eankoodi = '$tieto'
                    ORDER BY tunnus";
          $result2 = pupe_query($query2);
          
          if (mysql_num_rows($result2) == 1) {
            $t2row = mysql_fetch_assoc($result2);
            $pos = strpos($t2row["tuoteno"], "-EU");

            if ($pos > 0) {
              $tuotetieto = substr($t2row["tuoteno"], 0, $pos);
              
              $query = "SELECT *
                        FROM tuote
                        WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
                        and tuote.tuoteno = '{$tuotetieto}'
                        $kieltolisa
                        ORDER BY tunnus";
              $result = pupe_query($query);
            
              if (mysql_num_rows($result) == 0) {
                ///* Lis‰t‰‰n tilaukselle dummytuote ja kommentti *///
                $edi_ulos .= t("Rivi")." $rivinumero ".t("Tuote")." '$tuotetieto' ".t("ei lˆydy")."!\n";
                $tuoteerrortxt .= " $tuotetieto";
                $tuoteerror = 1;
              }
            }
          }
        }  
      } 
      if (mysql_num_rows($result) == 0) {     
        
        ///* Lis‰t‰‰n tilaukselle dummytuote ja kommentti *///
        $edi_ulos .= t("Rivi")." $rivinumero EAN '$tieto' ".t("ei lˆydy")."!\n";
        $tuoteerrortxt .= " $tieto";
        $tuoteerror = 1;
      }
      else {
        $trow = mysql_fetch_assoc($result);
        $edi_ulos .= t("Rivi")." $rivinumero ".t("Tuote")." $trow[tuoteno] / EAN: $tieto ";
        $tuoteno = $trow['tuoteno'];
        $tuoteerror = 0; // tuote ok
      }
    }

    break;

  case 'OLIN8__IF' :
    // asiakkaan antama nimitys tuotteelle
    $type = explode("@", $tieto);
    $edi_nimitys = $type[4]; // rivin nelj‰nness‰ segmentiss‰ tulee oikee info
    break;

  case 'OSTOTILRIV.OTR_VEROKANTA':
    // magento-keisiss‰, tilausrivin veron m‰‰r‰
    $verokanta = (int) trim($tieto);
    break;

  case 'OSTOTILRIV.OTR_OSTOHINTA' :

    $_hinnatedifailista = (isset($verkkokauppa_hinnatedifailista) and $verkkokauppa_hinnatedifailista == "JOO");

    // Kun tullaan magentosta ja meill‰ on rahtikulu, niin otetaan hinta talteeen
    // TAI kun tullaan magentosta ja me luotetaan verkkokaupan hintoihin.
    if ($edi_tyyppi == "magento" and ($_hinnatedifailista or strtolower($tuoteno) == strtolower($yhtiorow['rahti_tuotenumero'])) and $tieto != '') {
      // T‰m‰ hinta on aina veroton, joten lasketaan vero p‰‰lle jos myyntihinnat ovat verollisia
      $editilaus_hinta = (float) trim(str_replace( ",", ".", $tieto));

      // K‰ytˆss‰ on verolliset myyntihinnat, mutta edifailissa hinnat ovat verottomia
      if ($yhtiorow["alv_kasittely"] == '' and !isset($verkkokauppa_hinnatedifailistaverollisina)) {
        $editilaus_hinta = round($editilaus_hinta * (1 + $laskurow["alv"]/100), 2);
      }

      // K‰ytˆss‰ on verottomat myyntihinnat, mutta edifailissa hinta kuitenkin sis‰lt‰‰ verot ja halutaan laskea ne pois
      if ($yhtiorow["alv_kasittely"] != '' and isset($verkkokauppa_hinnatedifailistaverollisina) and !empty($verokanta)) {
        $editilaus_hinta = round($editilaus_hinta / (1 + $verokanta/100), 2);
      }
    }

    // Jos tilaus tulee ennalta m‰‰ritellyist‰ kaupoista
    // jotka on setattu $verkkokauppa_erikoiskasittely:n ja
    // ei olla setattu $verkkokauppa_hinnatedifailista, nollataan hinta
    if ($edi_tyyppi == "magento" and $verkkokauppa != '' and isset($verkkokauppa_erikoiskasittely)
      and count($verkkokauppa_erikoiskasittely) > 0) {

      $result = hae_erikoiskaupan_parametrit($verkkokauppa);
      if (count($result) > 0  and !isset($verkkokauppa_hinnatedifailista)) {
        $editilaus_hinta = 0.00;
      }
    }

    $verkkokauppa_nollahintaok = (isset($verkkokauppa_nollahintaok) and $verkkokauppa_nollahintaok == 'JOO');

    // Jos erikseen sallitaan nollahintainen myynti, niin laitetaan riville alennus 100%
    if ($verkkokauppa_nollahintaok and $_hinnatedifailista and $editilaus_hinta === 0.00) {
      $verkkokauppa_alennus100 = true;
    }

    break;

  case 'OSTOTILRIV.OTR_ALENNUS' :
    // Kun tullaan magentosta ja meill‰ on alennus, niin otetaan alennus talteen jos me luotetaan verkkokaupan hintoihin.
    if ($edi_tyyppi == "magento" and (isset($verkkokauppa_hinnatedifailista) and $verkkokauppa_hinnatedifailista == "JOO") and $tieto != '') {
      $editilaus_alennus = (float) trim($tieto);
    }

    if ($verkkokauppa_alennus100) {
      $editilaus_alennus = 100;
    }

    break;

  case 'OSTOTILRIV.OTR_SALLITAANJT' :
    // Sallitaan JT-Rivit
    if ($tieto == '1') {
      $sallitaanjt = '1';
    }
    else {
      $sallitaanjt = '0';
    }

    break;

  case 'OSTOTILRIV.OTR_VIITE' :
    $rivikommentti = pupesoft_cleanstring($tieto);
    break;

  case 'OLIN21_QT' :
  case 'OSTOTILRIV.OTR_TILATTUMAARA' :
  case 'QTY':
  case 'QTYLIN':

    if ($edi_tyyppi != "edifact911") {
      //Tilattava m‰‰r‰
      $tieto = str_replace(",", ".", $tieto);
    }

    if ($edi_tyyppi == "magento" and (strtolower($tuoteno) == strtolower($yhtiorow['rahti_tuotenumero']) or strtolower($tuoteno) == strtolower($yhtiorow['alennus_tuotenumero'])) and $tieto != '') {
      $kpl = (float) substr($tieto, 0, 16);
    }
    elseif ($edi_tyyppi == "edifact911") {
      $kentat = explode(":", $tieto);
      $kpl = (float) abs($kentat[1]);
    }
    else {
      $kpl = (float) abs(substr($tieto, 0, 16)); // abs() ettei asiakkaat pysty tekem‰‰n hyvityksi‰!
    }

    $edi_ulos .= t("tilattiin")." $kpl ".t("kpl").".\n";

    if ($tunnus != "QTYLIN") {
      break;
    }

  case 'OLIN__END' :
  case '*RE OSTOTILRIV' :
  case 'RFF':
  case 'PCD':
  case 'BGM':
  case 'QTYLIN':

    if ($edi_tyyppi == "edifact911") {
      $kentat = explode(":", $tieto);
      $kentat_bgm = explode("+", $tieto);

      // BGM kent‰ss‰ tulee 'BGM+QQQ+ABCDEFGHIJKLM'
      // QQQ = arvot, 105=tilaus, 640=toimituskehoitus, 221=plokkitilaus/ennakkotilaus
      // ABCDEFGHIJKLM = tossa tulee myym‰l‰n tilausnumero, joka pit‰isi olla sama kuin RFF+VN kent‰n arvo.
      if ($asiakkaan_tilausnumero == "" and $kentat_bgm[0] == '105' and $kentat_bgm[1] != "") {
        $asiakkaan_tilausnumero = $kentat_bgm[1];
        $viite = $kentat_bgm[1]. ' ';
        break;
      }

      // Orders 91.1 tulee kaksi RFF segmentti‰ (VN ja WS). VN otsikon viite, joten ei liity tilausriveihin. WS on tilausrivin loppumisen merkki.
      if ($kentat[0] == "VN" and $asiakkaan_tilausnumero == "") {
        $asiakkaan_tilausnumero = $kentat[1];
        $viite = $kentat[1]. ' ';
        break;
      }
    }

    if ($editilaus_otsikko_ok == "KYLLA") {
      // jos tuote on lˆytynyt ok
      if ($tuoteerror == 0) {

        $tuoteno_array = array($tuoteno);

        $hyvityssaanto_indeksi         = 0;
        $hyvityssaanto_hinta_array       = "";
        $hyvityssaanto_ale_array       = "";
        $hyvityssaanto_kpl_array       = "";
        $hyvityssaanto_kommentti_array     = "";
        $hyvityssaanto_palautuskielto_array  = "";

        // Jos k‰ytet‰‰n reklamaatioiden hinnoittelus‰‰ntˆ‰ ja k‰ytt‰j‰ ei ole v‰kisinhyv‰ksynyt rivi‰
        if ($yhtiorow["reklamaation_hinnoittelu"] == "K" and $editilaus_tilaustyyppi == 3) {
          $hyvityssaanto_hinta_array = array();
          $hyvityssaanto_ale_array = array();
          $hyvityssaanto_kpl_array = array();
          $hyvityssaanto_kommentti_array = array();
          $hyvityssaanto_palautuskielto_array = array();

          $palautus = hae_hyvityshinta($laskurow["liitostunnus"], $tuoteno, $kpl);

          if (count($palautus) > 0) $tuoteno_array = array();

          foreach ($palautus as $index => $arvot) {
            $tuoteno_array[] = $palautus[$index]["tuoteno"];
            $hyvityssaanto_hinta_array[$index][$tuoteno] = $palautus[$index]["hinta"];
            $hyvityssaanto_ale_array[$index][$tuoteno] = $palautus[$index]["ale"];
            $hyvityssaanto_kpl_array[$index][$tuoteno] = $palautus[$index]["kpl"] * -1;
            $hyvityssaanto_kommentti_array[$index][$tuoteno] = $palautus[$index]["kommentti"];
            $hyvityssaanto_palautuskielto_array[$index][$tuoteno] = $palautus[$index]["palautuskielto"];
          }
        }

        foreach ($tuoteno_array as $tuoteno) {

          // halutaan $tuoteno
          // $kpl kappaletta
          // $trow jossa on tuotten tiedot
          $ytunnus          = $laskurow["ytunnus"];
          $hinta           = "";
          $netto           = "";

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            ${'ale'.$alepostfix} = "";
          }

          $var                 = "";
          $alv                 = "";
          $toimaika            = $laskurow["toimaika"];
          $kerayspvm           = $laskurow["kerayspvm"];
          $paikka              = "";
          $kukarow["kesken"]   = $laskurow["tunnus"];
          $jtkielto            = $laskurow['jtkielto'];
          $kommentti           = $rivikommentti;

          // jos terminaalitoimitus niin ohitetaan kaikki saldozekit, sek‰ laitetaan kommenttiriville asiakkaan tuotenumero ja nimitys
          if ($editilaus_tila == "Z") {
            $var = "Y";
            $kommentti = "$olinsapi#$edi_nimitys";
          }

          if (!empty($editilaus_var_h)) {
            $var = "H";
          }

          // jos tullaan magentosta ja hinta on tiedossa ja kyseess‰ on rahtikulu, niin uskalletaan luottaa magenton antamaan hintaan
          if ($edi_tyyppi == "magento" and ((isset($verkkokauppa_hinnatedifailista) and $verkkokauppa_hinnatedifailista == "JOO") or strtolower($tuoteno) == strtolower($yhtiorow['rahti_tuotenumero'])) and $editilaus_hinta > 0) {
            $hinta = $editilaus_hinta;
          }
          elseif (is_array($hyvityssaanto_hinta_array) and isset($hyvityssaanto_hinta_array[$hyvityssaanto_indeksi]) and isset($hyvityssaanto_hinta_array[$hyvityssaanto_indeksi][$tuoteno])) {
            $hinta = $hyvityssaanto_hinta_array[$hyvityssaanto_indeksi][$tuoteno];
          }

          // jos tullaan magentosta ja alennus on tiedossa
          if ($edi_tyyppi == "magento" and (isset($verkkokauppa_hinnatedifailista) and $verkkokauppa_hinnatedifailista == "JOO") and $editilaus_alennus >= 0) {
            $ale1 = $editilaus_alennus;

            for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              ${'ale'.$alepostfix} = 0;
            }
          }
          elseif (is_array($hyvityssaanto_ale_array) and isset($hyvityssaanto_ale_array[$hyvityssaanto_indeksi]) and isset($hyvityssaanto_ale_array[$hyvityssaanto_indeksi][$tuoteno])) {

            // hyvityss‰‰nnˆn alennus yliajaa k‰ytt‰j‰n syˆtt‰m‰n alennuksen
            $ale1 = $hyvityssaanto_ale_array[$hyvityssaanto_indeksi][$tuoteno];

            for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              ${'ale'.$alepostfix} = 0;
            }
          }

          // Magento-tilauksessa katsotaan onko tilattavalla tuotteella saldoa.
          // Jos tilattu kpl-m‰‰r‰ on suurempi kuin myyt‰viss‰ oleva kpl-m‰‰r‰, niin pakotetaan rivi hyv‰ksytyksi tai j‰lkk‰riksi
          if ($edi_tyyppi == "magento" and $tuoteno != '' and $kpl > 0 and $trow["ei_saldoa"] == "") {

            list($chk_saldo, $chk_hyllyssa, $chk_myytavissa, $chk_true) = saldo_myytavissa($tuoteno, "", $verkkokauppavarasto);

            // jos saldo ei riit‰ normivarastoista, pit‰‰ katsoa miss‰ varastossa riitt‰‰ ja myyd‰‰n sielt‰ (koska jossain riitt‰‰, muuten tilaus ei olisi edes tullu t‰nne asti)
            if ($chk_myytavissa < $kpl) {

              $suoratoimitus_varastoon = (
                in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('V', 'W', 'I', 'J')) and
                in_array($trow['suoratoimitus'], array('S', 'X'))
              );

              $suoratoimitus_asiakkaalle = (
                in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('W', 'J')) and
                in_array($trow['suoratoimitus'], array('S', 'U'))
              );

              // Suoratoimitukset sallitaan jos k‰ytˆss‰ vain Magento
              $salllitaanko_suoratoimitus = ($suoratoimitus_varastoon or $suoratoimitus_asiakkaalle);
              $salllitaanko_suoratoimitus &= ($edi_laatija == "Magento");

              // jos parametri on p‰‰ll‰ ett‰ halutaan tehd‰ suoratoimitusrivi kun saldo ei riit‰
              if (!empty($yhtiorow['editilaus_suoratoimitus']) and $salllitaanko_suoratoimitus) {
                // haetaan p‰‰toimittaja
                $query = "SELECT tt.liitostunnus, toimi.suoratoimitus
                          FROM tuotteen_toimittajat AS tt USE INDEX (yhtio_tuoteno)
                          JOIN toimi ON (toimi.yhtio = tt.yhtio and toimi.tunnus = tt.liitostunnus)
                          WHERE tt.yhtio = '{$kukarow['yhtio']}'
                          and tt.tuoteno = '{$tuoteno}'
                          ORDER BY if (tt.jarjestys = 0, 9999, tt.jarjestys)";
                $ttres = pupe_query($query);
                $ttrow = mysql_fetch_assoc($ttres);

                if ($suoratoimitus_varastoon and in_array($ttrow["suoratoimitus"], array('', 'X')) and
                    !empty($ttrow['liitostunnus'])) {
                  $sallitaanjt = '';
                  $paikka = "°°°{$ttrow['liitostunnus']}";
                  $ei_esteta_laskutusta = false;
                }

                if ($suoratoimitus_asiakkaalle and in_array($ttrow["suoratoimitus"], array('', 'U')) and
                  !empty($ttrow['liitostunnus'])) {
                  $sallitaanjt = '';
                  $paikka = "!!!{$ttrow['liitostunnus']}";
                  $ei_esteta_laskutusta = false;
                }
              }
              else {
                $query = "SELECT distinct varastopaikat.tunnus varasto, varastopaikat.prioriteetti
                          FROM tuotepaikat
                          JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                            AND varastopaikat.tyyppi       != ''
                            AND varastopaikat.prioriteetti  > 0
                            AND varastopaikat.tunnus        = tuotepaikat.varasto
                            $verkkokauppavarasto_li)
                          WHERE tuotepaikat.yhtio           = '$kukarow[yhtio]'
                          AND tuotepaikat.tuoteno           = '$tuoteno'
                          ORDER BY prioriteetti";
                $sres = pupe_query($query);

                $erikoisvarastojen_saldot = array();
                $erikois_i = 0;

                while ($erikoisasaldo_row = mysql_fetch_assoc($sres)) {
                  list($chk_saldo, $chk_hyllyssa, $chk_myytavissa, $chk_true) = saldo_myytavissa($tuoteno, '', $erikoisasaldo_row["varasto"]);

                  if ($chk_myytavissa >= $kpl) {
                    $erikoisvarastojen_saldot[$erikois_i]["varasto"] = (int) $erikoisasaldo_row["varasto"];
                    $erikoisvarastojen_saldot[$erikois_i]["myytavissa"] = (float) $chk_myytavissa;
                    $erikoisvarastojen_saldot[$erikois_i]["prioriteetti"] = (int) $erikoisasaldo_row["prioriteetti"];
                    $erikois_i++;
                  }
                }

                if (count($erikoisvarastojen_saldot) > 0) {
                  // Sortataan 2 dimensoinen array. pit‰‰ ensiksi tehd‰ sortattavista keyst‰ omat arrayt
                  $apusort_jarj0 = $apusort_jarj1 = array();
                  foreach ($erikoisvarastojen_saldot as $apusort_key => $apusort_row) {
                    $apusort_jarj0[$apusort_key] = $apusort_row['prioriteetti'];
                    $apusort_jarj1[$apusort_key] = $apusort_row['myytavissa'];
                  }

                  // sortataan by myytavissa, sorttauskentt‰
                  array_multisort($apusort_jarj0, SORT_ASC, $apusort_jarj1, SORT_DESC, $erikoisvarastojen_saldot);

                  $varasto = $erikoisvarastojen_saldot[0]["varasto"]; // ekassa varastossa on pienin prioriteetti
                  $laskurow["varasto"] = $varasto; // joudutaan spooffaamaan laskurow varasto

                  // Tilausrivin systeemikommentti
                  $kommentti .= t("HUOM: Tuote toimitetaan erikoisvarastosta!", $arow["kieli"]);
                }
                else {
                  $var = $verkkokauppa_oletus_loppuneet;
                }
              }
            }
          }

          $kutsuja = "EDITILAUS";

          // Jos meill‰ on t‰pp‰ p‰‰ll‰, ett‰ asiakas haluaa kamat j‰lkitoimitukseen
          if ($sallitaanjt == '1') {
            $var     = "J";
            $kommentti   = ""; // HUOM: nollataan kommentti
          }

          if (is_array($hyvityssaanto_kpl_array) and isset($hyvityssaanto_kpl_array[$hyvityssaanto_indeksi]) and isset($hyvityssaanto_kpl_array[$hyvityssaanto_indeksi][$tuoteno])) {
            $kpl = $hyvityssaanto_kpl_array[$hyvityssaanto_indeksi][$tuoteno];
          }

          if ($edi_tyyppi == 'futursoft' and $editilaus_tilaustyyppi == 3) {
            $kpl = abs($kpl) * -1;
          }

          if (is_array($hyvityssaanto_kommentti_array) and isset($hyvityssaanto_kommentti_array[$hyvityssaanto_indeksi]) and isset($hyvityssaanto_kommentti_array[$hyvityssaanto_indeksi][$tuoteno])) {

            // jos myyj‰ on reklamaatiossa syˆtt‰nyt riville kommentin niin se laitetaan ensimm‰iselle sille tarkoitetulle riville
            if ($kommentti != "") {
              $kommentti .= " ";
            }

            $kommentti .= $hyvityssaanto_kommentti_array[$hyvityssaanto_indeksi][$tuoteno];
          }

          if (is_array($hyvityssaanto_palautuskielto_array) and isset($hyvityssaanto_palautuskielto_array[$hyvityssaanto_indeksi]) and isset($hyvityssaanto_palautuskielto_array[$hyvityssaanto_indeksi][$tuoteno])) {
            $hyvityssaannon_palautuskielto =  $hyvityssaanto_palautuskielto_array[$hyvityssaanto_indeksi][$tuoteno];
          }
          else {
            $hyvityssaannon_palautuskielto = "";
          }

          require "lisaarivi.inc";

          $edi_ulos .= "\n";

          $hyvityssaanto_indeksi++;
        }

      } // end jos tuote on lˆydetty oikein
      else {

        if ($tuoteerror == 2) {
          // Tilausrivin systeemikommentti
          $kommentti   .= t("Yritettiin tilata pelkk‰‰ runkoa", $arow["kieli"]).$tuoteerrortxt;
          $virhetuoteno = $tuoteno;
        }
        else {
          // Tilausrivin systeemikommentti
          $kommentti   .= t("Yritettiin tilata tuntematonta tuotetta", $arow["kieli"]).$tuoteerrortxt;
          $virhetuoteno = "TUNTEMATON";
        }

        $query = "INSERT into tilausrivi set
                  tilaajanrivinro = '$rivinumero',
                  laatija         = 'edi',
                  laadittu        = now(),
                  yhtio           = '$kukarow[yhtio]',
                  tuoteno         = '$virhetuoteno',
                  var             = 'P',
                  kpl             = '0',
                  alv             = '".alv_oletus()."',
                  tilkpl          = '$kpl',
                  otunnus         = '$id',
                  tyyppi          = '$editilaus_tyyppi',
                  kommentti       = '$kommentti'";
        $result = pupe_query($query);

      }
    }

    // nollataan kaikki rivin muuttujat (n‰m‰ samat pit‰‰ tehd‰ filen alussa)
    $tuoteerror        = 1; // oletetaan aina ettei  tuotetta lˆydy
    $tuoteerrortxt     = "";
    $kpl               = "";
    $rivinumero        = "";
    $kommentti         = "";
    $tuoteno           = "";
    $olinsapi          = "";
    $olinvarasto       = "";
    $edi_nimitys       = "";
    $sallitaanjt       = "";
    $editilaus_hinta   = "";
    $editilaus_alennus = "";
    $rivikommentti     = "";
    $verkkokauppa_alennus100 = false;
    break;

  case 'OMSG__END' :
  case '*IE' :
  case 'UNT':

    if ($editilaus_otsikko_ok == "KYLLA" and $edi_tyyppi != "magento" and $yhtiorow['lahetteen_tulostustapa'] == "I") {
      $editilaus_otsikko_ok = "HYVAKSYTTAVAKSI";
    }

    if ($editilaus_hyvaksyttava != "") {
      $editilaus_otsikko_ok = "HYVAKSYTTAVAKSI";
    }

    // Asiakkaiden tilaukset menev‰t ensin omaan hyv‰ksynt‰jonoon
    if ($arow['kerayserat'] == 'H') {
      $editilaus_otsikko_ok = "HYVAKJONOON";
    }

    // jos meilla on otsikko voidaan laittaa sen valmiiksi
    if ($editilaus_otsikko_ok == "KYLLA") {
      //tarkistetaan toimitustapa vakin takia
      $query = "SELECT *
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.vakkoodi not in ('','0'))
                WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                and tilausrivi.otunnus = '$id'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        $query = "SELECT vak_kielto
                  FROM toimitustapa
                  WHERE yhtio = '$kukarow[yhtio]'
                  and selite  = '$laskurow[toimitustapa]'";
        $result = pupe_query($query);
        $toimtapatmp = mysql_fetch_assoc($result);

        if ($toimtapatmp['vak_kielto'] != '' and $toimtapatmp['vak_kielto'] != 'K') {
          $query = "SELECT selite
                    FROM toimitustapa
                    WHERE yhtio    = '$kukarow[yhtio]'
                    AND selite     = '$toimtapatmp[vak_kielto]'
                    AND vak_kielto = ''";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 1) {
            $query = "UPDATE lasku SET toimitustapa = '$toimtapatmp[vak_kielto]' where yhtio = '$kukarow[yhtio]' and tunnus = '$id'";
            $result = pupe_query($query);
          }
        }
      }

      // haetaan tilauksen tiedot
      $query    = "SELECT * FROM lasku WHERE tunnus='$id' AND yhtio='$kukarow[yhtio]'";
      $result   = pupe_query($query);
      $laskurow = mysql_fetch_assoc($result);

      // tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow ja $yhtiorow
      $kukarow["kesken"] = $laskurow["tunnus"];
      $editilaus_lasku_tunnus = $laskurow["tunnus"];

      if ($editilaus_tila != "Z" and $editilaus_tilaustyyppi != 3 and $editilaus_tilaustyyppi != 'K') {

        if (in_array($yhtiorow['jt_automatiikka'], array('Y', 'W', 'K'))) {

          //lis‰t‰‰n tilaukseen ne jt-rivit jotka riitt‰v‰t kaikille
          $edi_ulos .= "\n";

          $_ytunnus = $laskurow['ytunnus'];
          $_ltun = $laskurow['liitostunnus'];
          $_maa = $laskurow['maa'];
          $_ytp = $laskurow['yhtio_toimipaikka'];

          jt_toimita_editilaus($_ytunnus, $_ltun, $_maa, $_ytp);
          $edi_ulos .= "\n";
        }

        $edi_ulos .= "\n";

        $silent = "SILENT";
        $tilausvalmiskutsuja = "EDITILAUS";

        if (!empty($ltoim_email)) {
          $saate_query = "SELECT tunnus
                          FROM avainsana
                          WHERE yhtio = '$kukarow[yhtio]'
                          AND laji    = 'LASKUTUS_SAATE'
                          AND selite  = 'Verkkokauppa'";
          $saate_res = pupe_query($saate_query);

          if ($saaterow = mysql_fetch_assoc($saate_res)) {
            $saatekirje = $saaterow["tunnus"];
            $valittu_kopio_tulostin = "asiakasemail$ltoim_email";
          }
        }

        // magentotilaus on laskutettu, jos meill‰ on kassalipas
        $laskutettu_magento_tilaus = ($edi_tyyppi == "magento" and $editilaus_kassalipas != "");

        // jos etuk‰teen maksettu mutta ei haluta laskuttaa, p‰ivitet‰‰n tilaukselle tunnistustiedot
        if (!$ei_esteta_laskutusta and $laskutettu_magento_tilaus) {
          $query = "UPDATE lasku SET
                    ohjelma_moduli = 'MAGENTOJT'
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tunnus  = '$editilaus_lasku_tunnus'";
          pupe_query($query);
        }

        // jos magentosta tullut tilaus on jo maksettu, p‰ivitet‰‰n se takaisin ker‰ysjonoon
        // paitsi tilaustyyppi 9, jossa halutaan ohittaa varastoprosessi
        $paivita_takaisin_keraysjonoon = ($laskutettu_magento_tilaus and $editilaus_tilaustyyppi != "9" and $ei_esteta_laskutusta);

        $kateisohitus = $ei_esteta_laskutusta ? "" : "X";

        // jos meill‰ on maksettu magento tilaus
        if ($laskutettu_magento_tilaus and $ei_esteta_laskutusta) {

          // jos on annettu toimitusaika, asetetaan se laskun tapahtumap‰iv‰ksi
          if (!empty($editilaus_tilausaika)) {
            $_temp_date = strtotime($editilaus_tilausaika);

            $laskpp = date("d", $_temp_date);
            $laskkk = date("m", $_temp_date);
            $laskvv = date("Y", $_temp_date);
          }

          // jos tilaus on vienti‰, pit‰‰ pakottaa sis‰inen t‰pp‰ jotta saadaan tilaus laskutettua.
          // ei setata kuitenkaan kantaan, jotta saadaan l‰pi myˆhemmin varastoprosessi, jos niin halutaan
          if ($laskurow["vienti"] != "") {
            $laskurow['sisainen'] = 'o';
          }
        }

        // Laitetaan tilaus valmiiksi
        require "tilaus-valmis.inc";

        $valittu_kopio_tulostin = "";
        $saatekirje = 0;

        // P‰ivitet‰‰n tilaus takaisin ker‰ysjonoon
        if ($paivita_takaisin_keraysjonoon) {
          laskutettu_myyntitilaus_tulostusjonoon($editilaus_lasku_tunnus);
        }

        $edi_ulos .= "$tilaus_valmis_ulos\n";
        $edi_ulos .= "$tilaus_valmis_message\n";
        $edi_ulos .= "\n".t("Merkittiin tilaus valmiiksi").": $editilaus_lasku_tunnus.\n\n";
      }
      elseif ($editilaus_tila == "Z") {
        $query = "UPDATE lasku SET
                  alatila     = 'A'
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tunnus  = '$editilaus_lasku_tunnus'";
        pupe_query($query);
        $edi_ulos .= "\n".t("Terminaalitoimitus vastaanotettu").": $editilaus_lasku_tunnus.\n\n";
      }
      elseif ($editilaus_tilaustyyppi == 3) {
        $edi_ulos .= "\n".t("Reklamaatio vastaanotettu").": $editilaus_lasku_tunnus.\n\n";
      }
    }
    elseif ($editilaus_otsikko_ok == "HYVAKSYTTAVAKSI") {
      $edi_ulos .= "\n".t("Merkittiin tilaus hyv‰ksytt‰v‰ksi").": {$laskurow['tunnus']}.\n\n";
    }
    elseif ($editilaus_otsikko_ok == "HYVAKJONOON") {
      $edi_ulos .= "\n".t("Merkittiin tilaus hyv‰ksytt‰v‰ksi").": {$laskurow['tunnus']}.\n\n";

      $query = "UPDATE lasku SET
                alatila     = 'FF'
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$id'";
      pupe_query($query);
    }
    else {
      $edi_ulos .= "\n".t("Otsikko oli virheellinen tilausta/rivej‰ ei lis‰tty")."!\n\n";
    }

    // nollataan kaikki laskun muuttujat (kaikki n‰m‰ samat pit‰‰ nollata filen alussa)
    // Paitsi ovt_tunnusta, koska se tulee vain tiedoston ihan alussa kerran (ICHG_SNDR)
    unset($arow);
    unset($arow_viite);
    unset($arow_ostaja);
    unset($arow_toimitus);
    unset($arow_terminaali);
    unset($crossdock911);
    unset($laskpp, $laskkk, $laskvv);

    $asiakkaan_tilausnumero       = "";
    $edi_kommentti                = "";
    $editilaus_alatila            = "";
    $editilaus_eilahetetta        = "";
    $editilaus_kassalipas         = "";
    $editilaus_liitetiedosto      = "";
    $editilaus_loppuhinta         = "";
    $editilaus_maksuehto          = "";
    $editilaus_maksuteksti        = "";
    $editilaus_osatoimitus        = "";
    $editilaus_otsikko_ok         = "";
    $editilaus_rahtivapaa         = "";
    $editilaus_sisainen           = "";
    $editilaus_tila               = "N"; // laskun tila
    $editilaus_tilausaika         = "";
    $editilaus_tilaustyyppi       = "";
    $editilaus_toimitustapa       = "";
    $editilaus_tyyppi             = "L"; // tilausrivi tyyppi
    $editilaus_valuuttakoodi      = "";
    $editilaus_ytunnus            = "";
    $ei_esteta_laskutusta         = true;
    $id                           = "";
    $ketjutus                     = "";
    $kieltolisa                   = "";
    $lnimi                        = "";
    $lnimitark                    = "";
    $lopnimi                      = "";
    $lopnumero                    = "";
    $loposoite                    = "";
    $loppostino                   = "";
    $loppostitp                   = "";
    $lopviesti                    = "";
    $losoite                      = "";
    $lpostino                     = "";
    $lpostitp                     = "";
    $ltoim_maa                    = "";
    $ltoim_puh                    = "";
    $maksaja                      = "";
    $merkkitieto                  = "";
    $noutopisteen_tunnus          = "";
    $ohjausmerkki                 = "";
    $OT_ASIAKASNRO                = "";
    $ot_verkkokauppa_asiakasnro   = "";
    $ot_verkkokauppa_kohde        = "";
    $ot_verkkokauppa_tilausviesti = "";
    $ovt_tunnuswhere              = "";
    $suunnistus                   = "";
    $til_vienti                   = "";
    $tilausyhteyshenkilo          = "";
    $tnimi                        = "";
    $tnimitark                    = "";
    $toimpvm                      = date("Y-m-d");
    $tosoite                      = "";
    $tpostino                     = "";
    $tpostitp                     = "";
    $vastaustapa                  = "";
    $verkkokauppa                 = "";
    $verkkokauppavarasto          = "";
    $verkkokauppavarasto_li       = "";
    $verokanta                    = "";
    $veromaara                    = "";
    $viite                        = "";
    $yhteyshenkilon_puhelin       = "";
    break;
  }
}

// l‰hetet‰‰n meili
if (strpos($_SERVER['SCRIPT_NAME'], "tilaus_in.php") === FALSE and isset($yhtiorow) and $yhtiorow["edi_email"] != "") {

  $edi_ulos = strip_tags($edi_ulos);

  $bound   = uniqid(time()."_") ;
  $kutsu   = $yhtiorow["yhtio"]."-ediorder.txt";

  $header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
  $header .= "MIME-Version: 1.0\n" ;
  $header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

  $content  = "--$bound\n";
  $content .= "Content-Type: text/plain;charset=iso-8859-1\n" ;
  $content .= "Content-Transfer-Encoding: quoted-printable\n";
  $content .= $edi_ulos.t("K‰sitelty tiedosto liitteen‰").".\n\n";

  $content .= "--$bound\n";
  $content .= "Content-Type: text/plain; name=\"".$kutsu."\"\n" ;
  $content .= "Content-Transfer-Encoding: base64\n" ;
  $content .= "Content-Disposition: attachment; filename=\"".$kutsu."\"\n\n";

  $handle  = fopen($mailfile, "r");
  $sisalto = fread($handle, filesize($mailfile));
  fclose($handle);

  $content .= chunk_split(base64_encode($sisalto));
  $content .= "\n";
  $content .= "--$bound--\n";

  $boob = mail($yhtiorow["edi_email"], mb_encode_mimeheader($edi_subj." - $yhtiorow[nimi]", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
}
else {
  echo "\n";
  echo $filename;
  echo "\n";
  echo $edi_ulos;
  echo "\n";
}
