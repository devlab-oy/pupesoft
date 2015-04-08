<?php

/*
 * Siirret‰‰n tuotemasterdata Relexiin
 * 4.2 PRODUCT MASTER DATA
*/

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiˆ on annettava!!");
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

$ajopaiva  = date("Y-m-d");
$paiva_ajo = FALSE;
$weekly_ajo = FALSE;
$ajotext = "";

if (isset($argv[2]) and $argv[2] != '') {

  if (strpos($argv[2], "-") !== FALSE) {
    list($y, $m, $d) = explode("-", $argv[2]);
    if (is_numeric($y) and is_numeric($m) and is_numeric($d) and checkdate($m, $d, $y)) {
      $ajopaiva = $argv[2];
    }
  }

  if (strtoupper($argv[2]) == 'WEEKLY') {
    $weekly_ajo = TRUE;
    $ajotext = "weekly_";
  }
  else {
    $paiva_ajo = TRUE;
  }
}

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

$tuoteupdrajaus = "";
$tuotetoimupdrajaus = "";

// Haetaan aika jolloin t‰m‰ skripti on viimeksi ajettu
$datetime_checkpoint = cron_aikaleima("RELEX_PROD_CRON");

// Otetaan mukaan vain edellisen ajon j‰lkeen muuttuneet
if ($paiva_ajo and $datetime_checkpoint != "") {
  $tuoteupdrajaus = " AND (tuote.muutospvm > '$datetime_checkpoint' OR tuote.luontiaika > '$datetime_checkpoint')";
  $tuotetoimupdrajaus = " AND (tuotteen_toimittajat.muutospvm > '$datetime_checkpoint' OR tuotteen_toimittajat.luontiaika > '$datetime_checkpoint')";
}
elseif ($paiva_ajo) {
  $tuoteupdrajaus = " AND (tuote.muutospvm  >= date_sub(now(), interval 24 HOUR) OR tuote.luontiaika >= date_sub(now(), interval 24 HOUR))";
  $tuotetoimupdrajaus = " AND (tuotteen_toimittajat.muutospvm  >= date_sub(now(), interval 24 HOUR) OR tuotteen_toimittajat.luontiaika >= date_sub(now(), interval 24 HOUR))";
}

$tuoterajaus = rakenna_relex_tuote_parametrit();

// Jos relex tuoterajauksia tehd‰‰n "update-kentill‰",
// niin katsotaan tarviiko tehd‰ erillinen raportti, mik‰li
// Relexiin ei mene kenttien rajauksiin osumattomia tuotteita. T‰m‰ siksi,
// ett‰ tuotteella kent‰n arvot on voinut muuttua, ja saadaan muutoksesta update.

$update_kentat = array("ostoehdotus", "status");

if (!function_exists("relex_product_ostoehdotus_update")) {
  function relex_product_ostoehdotus_update($hakukentta, $tuoterajaus, $paiva_ajo) {
    global $kukarow, $yhtiorow;

    // tehd‰‰n spessukent‰st‰ k‰‰ntˆ
    $_hakukentta_loytyi = strpos($tuoterajaus, "tuote.$hakukentta");

    if ($_hakukentta_loytyi !== FALSE and $paiva_ajo) {

      $_rajaus_alkaa_hakukentalla = substr($tuoterajaus, $_hakukentta_loytyi);
      $_rajaukset = explode(" AND", $_rajaus_alkaa_hakukentalla);

      $_rajaus_alkper = $_rajaukset[0];
      $_rajaus_siivottu = str_replace(" not in ", " notin ", $_rajaus_alkper);

      list($kentta, $oper, $arvo) = explode(" ", $_rajaus_siivottu, 3);

      if ($oper == "=") {
        $oper = '!=';
        $kentta_update = TRUE;
      }
      elseif ($oper == "!=") {
        $oper = '=';
        $kentta_update = TRUE;
      }
      elseif ($oper == "in") {
        $oper = 'not in';
        $kentta_update = TRUE;
      }
      elseif ($oper == "notin") {
        $oper = 'in';
        $kentta_update = TRUE;
      }
      else {
        $kentta_update = FALSE;
      }

      if ($kentta_update) {

        $_tuoterajaus_kentta = "$kentta $oper $arvo";
        return $_tuoterajaus_kentta;
      }
      else {
        return "";
      }
    }
    return "";
  }
}

$_tuoterajaus_ilman_hakukenttia = $tuoterajaus;
$_tuoterajaus = "";
$tehdaan_updatefile = FALSE;

foreach ($update_kentat as $_kentta) {

  // siivotaan eka tuoterajaukset ilman spessukentti‰
  $_hakukentta_loytyi = strpos($_tuoterajaus_ilman_hakukenttia, "tuote.$_kentta");

  if ($_hakukentta_loytyi !== FALSE and $paiva_ajo) {

    $_rajaus_alkaa_hakukentalla = substr($_tuoterajaus_ilman_hakukenttia, $_hakukentta_loytyi);
    $_rajaukset = explode(" AND", $_rajaus_alkaa_hakukentalla);
    $kentan_siivous = " AND ".$_rajaukset[0];
    $_tuoterajaus_ilman_hakukenttia = str_replace($kentan_siivous, "", $_tuoterajaus_ilman_hakukenttia);
  }

  // spessukent‰t l‰pi
  $tuoterajaus_kentta = relex_product_ostoehdotus_update($_kentta, $tuoterajaus, $paiva_ajo);

  if ($tuoterajaus_kentta) {
    $_tuoterajaus .= "({$tuoterajaus_kentta}) OR ";
  }
}

if ($_tuoterajaus) {

  // yhdistet‰‰n rajaukset
  $_tuoterajaus = $_tuoterajaus_ilman_hakukenttia ." AND (". substr($_tuoterajaus, 0, -4).")";

  // Tallennetaan rivit tiedostoon
  $ofilepath = "/tmp/product_ostoehdotus_update_{$yhtio}_{$ajotext}{$ajopaiva}.csv";

  if (!$ofp = fopen($ofilepath, 'w+')) {
    die("Tiedoston avaus ep‰onnistui: $ofilepath\n");
  }

  $select_lisa = "";

  // Otsikkotieto
  $header  = "code";

  foreach ($update_kentat as $kentta) {

    $header .= ";$kentta";
    if ($kentta == "ostoehdotus") {
      $select_lisa .= "if(tuote.ostoehdotus != 'E', 'K', 'E') ostoehdotus, ";
    }
    else {
      $select_lisa .= "tuote.$kentta, ";
    }
  }

  $header .= "\n";

  fwrite($ofp, $header);

  $query = "SELECT $select_lisa tuote.tuoteno, yhtio.maa
            FROM tuote
            JOIN yhtio ON (tuote.yhtio = yhtio.yhtio)
            WHERE tuote.yhtio = '{$yhtio}'
            {$_tuoterajaus}
            {$tuoteupdrajaus}";
  $res = pupe_query($query);

  $k_rivi = 0;

  while ($row = mysql_fetch_assoc($res)) {

    $rivi  = $row['maa']."-".pupesoft_csvstring($row['tuoteno']);
    foreach ($update_kentat as $kentta) {
      $rivi .= ";{$row[$kentta]}";
    }
    $rivi .= "\n";

    fwrite($ofp, $rivi);

    $k_rivi++;
  }

  fclose($ofp);

  // Tehd‰‰n FTP-siirto
  if ($paiva_ajo and !empty($relex_ftphost)) {
    // Tuotetiedot
    $ftphost = $relex_ftphost;
    $ftpuser = $relex_ftpuser;
    $ftppass = $relex_ftppass;
    $ftppath = "/data/input";
    $ftpfile = $ofilepath;
    require "inc/ftp-send.inc";
  }
}

$tecd = FALSE;

if (@include "inc/tecdoc.class.php") {
  $tecd = TRUE;
}

// Vastaavat tuotteet infraa
require "vastaavat.class.php";

// Tallennetaan tuoterivit tiedostoon
$filepath = "/tmp/product_update_{$yhtio}_{$ajotext}{$ajopaiva}.csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

$tuotteet = "";

// P‰iv‰ajoon otetaan mukaan vain viimeisen vuorokauden aikana muuttuneet
if ($paiva_ajo) {

  $tuotelista       = "''";
  $namaonjotsekattu = "";

  $query = "SELECT tuote.tuoteno
            FROM tuote
            WHERE tuote.yhtio = '{$yhtio}'
            {$tuoterajaus}
            {$tuoteupdrajaus}";
  $res = pupe_query($query);

  while ($row = mysql_fetch_assoc($res)) {
    $tuotelista .= ",'".pupesoft_cleanstring($row["tuoteno"])."'";
  }

  $query = "SELECT tuotteen_toimittajat.tuoteno
            FROM tuotteen_toimittajat
            WHERE tuotteen_toimittajat.yhtio = '{$yhtio}'
            AND tuotteen_toimittajat.tuoteno not in ($tuotelista)
            {$tuotetoimupdrajaus}";
  $res = pupe_query($query);

  while ($row = mysql_fetch_assoc($res)) {
    $tuotelista .= ",'".pupesoft_cleanstring($row["tuoteno"])."'";
  }

  $tuotteet = " AND tuote.tuoteno IN ({$tuotelista}) ";
}

// Tallennetaan aikaleima
cron_aikaleima("RELEX_PROD_CRON", date('Y-m-d H:i:s'));

// Otsikkotieto
$header  = "code;";
$header .= "clean_code;";
$header .= "name;";
$header .= "tuoteosasto;";
$header .= "group;";
$header .= "tuotemerkki;";
$header .= "malli;";
$header .= "mallitarkenne;";
$header .= "kuvaus;";
$header .= "lyhytkuvaus;";
$header .= "mainosteksti;";
$header .= "aleryhma;";
$header .= "purkukommentti;";
$header .= "keskihankintahinta;";
$header .= "viimeisin_hankintahinta;";
$header .= "viimeisin_hankintapaiva;";
$header .= "yksikko;";
$header .= "tuotetyyppi;";
$header .= "hinnastoon;";
$header .= "sarjanumeroseuranta;";
$header .= "status;";
$header .= "luontiaika;";
$header .= "epakuranttipvm;";
$header .= "halytysraja;";
$header .= "varmuusvarasto;";
$header .= "tilausmaara;";
$header .= "ostoehdotus;";
$header .= "tahtituote;";
$header .= "myyntiera;";
$header .= "minimiera;";
$header .= "tuotekorkeus;";
$header .= "tuoteleveys;";
$header .= "tuotesyvyys;";
$header .= "tuotemassa;";
$header .= "tilavuus;";
$header .= "ostajanro;";
$header .= "tuotepaallikko;";
$header .= "tullinimike;";
$header .= "tullinimikelisa;";
$header .= "tullikohtelukoodi;";
$header .= "vakadrkoodi;";
$header .= "vakmaara;";
$header .= "leimahduspiste;";
$header .= "tuotetunnus;";
$header .= "rekisteriosumat;";
$header .= "elinkaari;";
$header .= "supplier;";
$header .= "suppliers_code;";
$header .= "suppliers_name;";
$header .= "ostoera;";
$header .= "pakkauskoko;";
$header .= "lavakoko;";
$header .= "purchase_price;";
$header .= "alennus;";
$header .= "valuutta;";
$header .= "kuluprosentti;";
$header .= "suppliers_unit;";
$header .= "tuotekerroin;";
$header .= "jarjestys;";
$header .= "vastaavat";
$header .= "\n";
fwrite($fp, $header);

// Tallennetaan tuotteentoimittajarivit tiedostoon
$tfilepath = "/tmp/product_suppliers_update_{$yhtio}_{$ajotext}{$ajopaiva}.csv";

if (!$tfp = fopen($tfilepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $tfilepath\n");
}

// Otsikkotieto
$header  = "code;";
$header .= "clean_code;";
$header .= "supplier;";
$header .= "suppliers_code;";
$header .= "suppliers_name;";
$header .= "ostoera;";
$header .= "pakkauskoko;";
$header .= "lavakoko;";
$header .= "purchase_price;";
$header .= "alennus;";
$header .= "valuutta;";
$header .= "kuluprosentti;";
$header .= "suppliers_unit;";
$header .= "tuotekerroin;";
$header .= "jarjestys;";
$header .= "toimitusaika_ema\n";
fwrite($tfp, $header);

// Haetaan tuotteet
$query = "SELECT
          yhtio.maa,
          tuote.tuoteno,
          tuote.nimitys,
          tuote.osasto,
          tuote.try,
          tuote.tuotemerkki,
          tuote.malli,
          tuote.mallitarkenne,
          tuote.kuvaus,
          tuote.lyhytkuvaus,
          tuote.mainosteksti,
          tuote.aleryhma,
          tuote.purkukommentti,
          tuote.kehahin,
          tuote.vihahin,
          tuote.vihapvm,
          upper(tuote.yksikko) yksikko,
          tuote.tuotetyyppi,
          tuote.hinnastoon,
          tuote.sarjanumeroseuranta,
          tuote.status,
          left(tuote.luontiaika, 10) luontiaika,
          tuote.epakurantti25pvm,
          if(tuote.halytysraja = 0, '', tuote.halytysraja) halytysraja,
          if(tuote.varmuus_varasto = 0, '', tuote.varmuus_varasto) varmuus_varasto,
          if(tuote.tilausmaara = 0, 1, tuote.tilausmaara) tilausmaara,
          if(tuote.epakurantti25pvm != '0000-00-00', 'E', if(tuote.ostoehdotus != 'E', 'K', 'E')) ostoehdotus,
          tuote.tahtituote,
          if(tuote.myynti_era = 0, 1, tuote.myynti_era) myynti_era,
          if(tuote.minimi_era = 0, '', tuote.minimi_era) minimi_era,
          tuote.tuotekorkeus,
          tuote.tuoteleveys,
          tuote.tuotesyvyys,
          tuote.tuotemassa,
          round(tuote.tuotekorkeus * tuote.tuoteleveys * tuote.tuotesyvyys, 5) tilavuus,
          tuote.ostajanro,
          tuote.tuotepaallikko,
          tuote.tullinimike1,
          tuote.tullinimike2,
          tuote.tullikohtelu,
          tuote.vakkoodi,
          tuote.vakmaara,
          tuote.leimahduspiste,
          tuote.tunnus
          FROM tuote
          JOIN yhtio ON (tuote.yhtio = yhtio.yhtio)
          WHERE tuote.yhtio = '$yhtio'
          {$tuoterajaus}
          {$tuotteet}
          ORDER BY tuote.tuoteno";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo date("d.m.Y @ G:i:s") . ": Relex tuoterivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  // Tuotetiedot
  $rivi  = $row['maa']."-".pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= pupesoft_csvstring($row['tuoteno']).";";
  $rivi .= pupesoft_csvstring($row['nimitys']).";";
  $rivi .= "{$row['osasto']};";
  $rivi .= "{$row['try']};";
  $rivi .= pupesoft_csvstring($row['tuotemerkki']).";";
  $rivi .= pupesoft_csvstring($row['malli']).";";
  $rivi .= pupesoft_csvstring($row['mallitarkenne']).";";
  $rivi .= pupesoft_csvstring($row['kuvaus']).";";
  $rivi .= pupesoft_csvstring($row['lyhytkuvaus']).";";
  $rivi .= pupesoft_csvstring($row['mainosteksti']).";";
  $rivi .= "{$row['aleryhma']};";
  $rivi .= pupesoft_csvstring($row['purkukommentti']).";";
  $rivi .= "{$row['kehahin']};";
  $rivi .= "{$row['vihahin']};";
  $rivi .= "{$row['vihapvm']};";
  $rivi .= "{$row['yksikko']};";
  $rivi .= "{$row['tuotetyyppi']};";
  $rivi .= "{$row['hinnastoon']};";
  $rivi .= "{$row['sarjanumeroseuranta']};";
  $rivi .= "{$row['status']};";
  $rivi .= "{$row['luontiaika']};";
  $rivi .= "{$row['epakurantti25pvm']};";
  $rivi .= "{$row['halytysraja']};";
  $rivi .= "{$row['varmuus_varasto']};";
  $rivi .= "{$row['tilausmaara']};";
  $rivi .= "{$row['ostoehdotus']};";
  $rivi .= pupesoft_csvstring($row['tahtituote']).";";
  $rivi .= "{$row['myynti_era']};";
  $rivi .= "{$row['minimi_era']};";
  $rivi .= "{$row['tuotekorkeus']};";
  $rivi .= "{$row['tuoteleveys']};";
  $rivi .= "{$row['tuotesyvyys']};";
  $rivi .= "{$row['tuotemassa']};";
  $rivi .= "{$row['tilavuus']};";
  $rivi .= "{$row['ostajanro']};";
  $rivi .= "{$row['tuotepaallikko']};";
  $rivi .= "{$row['tullinimike1']};";
  $rivi .= "{$row['tullinimike2']};";
  $rivi .= "{$row['tullikohtelu']};";

  if (empty($row['vakkoodi'])) {
    $vak_row['yk_nro'] = '';
  }
  elseif ($yhtiorow['vak_kasittely'] == 'P') {
    $query = "SELECT yk_nro
              FROM vak
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = {$row['vakkoodi']}";
    $vak_res = pupe_query($query);
    $vak_row = mysql_fetch_assoc($vak_res);
  }
  else {
    $vak_row['yk_nro'] = $row['vakkoodi'];
  }

  $rivi .= "{$vak_row['yk_nro']};";
  $rivi .= "{$row['vakmaara']};";
  $rivi .= "{$row['leimahduspiste']};";
  $rivi .= "{$row['tunnus']};";

  if ($tecd) {
    $td = new tecdoc('pc', false);
    $rivi .= $td->getRegSumForProduct($row['tuoteno']).";";
  }
  else {
    $rivi .= "0;";
  }

  // Tuotteen elinkaari
  $ttq = "SELECT tuotteen_avainsanat.selite elinkaari
          FROM tuotteen_avainsanat
          WHERE tuotteen_avainsanat.yhtio = '{$yhtio}'
          AND tuotteen_avainsanat.laji    = 'Elinkaari'
          AND tuotteen_avainsanat.tuoteno = '{$row['tuoteno']}'
          LIMIT 1";
  $ttres = pupe_query($ttq);

  if (mysql_num_rows($ttres) == 1) {
    $ttrow = mysql_fetch_assoc($ttres);
    $rivi .= "{$ttrow['elinkaari']};";
  }
  else {
    $rivi .= ";";
  }

  // haetaan kaikki tuotteen toimittajat ja valitaan sitten edullisin
  $ttq = "SELECT
          tuotteen_toimittajat.tunnus tutotunnus,
          toimi.tunnus toimittaja,
          toimi.ytunnus ytunnus,
          if(tuotteen_toimittajat.toimitusaika = 0, toimi.oletus_toimaika, tuotteen_toimittajat.toimitusaika) toimitusaika,
          tuotteen_toimittajat.toim_tuoteno,
          tuotteen_toimittajat.toim_nimitys,
          if(tuotteen_toimittajat.valuutta = '', toimi.oletus_valkoodi, tuotteen_toimittajat.valuutta) valuutta,
          if(tuotteen_toimittajat.osto_era = 0, 1, tuotteen_toimittajat.osto_era) osto_era,
          if(tuotteen_toimittajat.pakkauskoko = 0, '', tuotteen_toimittajat.pakkauskoko) pakkauskoko,
          tuotteen_toimittajat.ostohinta,
          tuotteen_toimittajat.alennus,
          toimi.oletus_kulupros,
          toimi.oletus_valkoodi valuutta,
          tuotteen_toimittajat.toim_yksikko,
          if(tuotteen_toimittajat.tuotekerroin = 0, 1, tuotteen_toimittajat.tuotekerroin) tuotekerroin,
          if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) jarjestys
          FROM tuotteen_toimittajat
          JOIN toimi ON (tuotteen_toimittajat.yhtio = toimi.yhtio
            AND tuotteen_toimittajat.liitostunnus = toimi.tunnus
            AND toimi.oletus_vienti               in ('C','F','I')
            AND toimi.toimittajanro               not in ('0','')
            AND toimi.tyyppi                      = '')
          WHERE tuotteen_toimittajat.yhtio        = '{$yhtio}'
          AND tuotteen_toimittajat.tuoteno        = '{$row['tuoteno']}'
          ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys)";
  $ttres = pupe_query($ttq);

  $toimittajat_a_hinta = array();
  $toimittajat_a       = array(0 => array(
      'toimittaja'                      => '',
      'toim_tuoteno'                    => '',
      'toim_nimitys'                    => '',
      'osto_era'                        => '',
      'pakkauskoko'                     => '',
      'lavakoko'                        => '',
      'ostohinta_oletusvaluutta'        => '',
      'alennukset_oletusvaluutta_netto' => '',
      'valuutta'                        => '',
      'kuluprosentti'                   => '',
      'toim_yksikko'                    => '',
      'tuotekerroin'                    => '',
      'jarjestys'                       => '',
      'toimitusaika_ema'                => '')
  );

  $parastoimittaja = array(
    "toimittaja" => '',
    "toim_tuoteno" => '',
    "toim_nimitys" => '',
    "osto_era" => '',
    "pakkauskoko" => '',
    "lavakoko" => '',
    "ostohinta_oletusvaluutta" => '',
    "alennukset_oletusvaluutta_netto" => '',
    "valuutta" => '',
    "oletus_kulupros" => '',
    "toim_yksikko" => '',
    "tuotekerroin" => '',
    "jarjestys" => '',
  );

  if (mysql_num_rows($ttres) > 0) {

    // Nollataan defaultit pois
    $toimittajat_a = array();

    while ($ttrow = mysql_fetch_assoc($ttres)) {

      // Haetaan t‰n toimittajan viimeisimm‰t tulot.
      $emaq = "SELECT
               datediff(tilausrivi.laskutettuaika, tilausrivi.laadittu) toimitusaika
               FROM tilausrivi
               JOIN lasku ON (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.uusiotunnus and lasku.liitostunnus = {$ttrow['toimittaja']})
               WHERE tilausrivi.yhtio        = '{$yhtio}'
               AND tilausrivi.tyyppi         = 'O'
               AND tilausrivi.tuoteno        = '{$row['tuoteno']}'
               AND tilausrivi.laskutettuaika > date_sub(current_date, interval 1 year)
               HAVING toimitusaika > 0
               ORDER BY laskutettuaika desc
               LIMIT 5";
      $emares = pupe_query($emaq);

      $ema_tulot    = array();
      $ema          = 0;
      $alfa         = 0.35;
      $korjattu_ema = 0;

      if (mysql_num_rows($emares)) {
        while ($emarow = mysql_fetch_assoc($emares)) {
          $ema_tulot[] = $emarow["toimitusaika"];
        }

        $ema_tulot = array_reverse($ema_tulot);
        $ema_maara = count($ema_tulot);

        // Ema ekan tulos perusteella on sama kuin ekan tulon toimitusaika
        $ema = $ema_tulot[0];

        if ($ema_maara > 1) {

          $poikpros = array();

          for ($i = 1; $i < $ema_maara; $i++) {
            $ema = $alfa * $ema_tulot[$i] + (1 - $alfa) * $ema;
            $poikpros[] = abs($ema - $ema_tulot[$i]) / $ema;
          }

          // EMA:n ja toimitusajan poikkeamaprossat keskim‰‰rin
          $avg_poikpros = array_sum($poikpros) / count($poikpros);

          $korjattu_ema = round($ema * (1 + $avg_poikpros / 2), 2);
        }
        else {
          $korjattu_ema = round($ema, 2);
        }
      }
      else {
        // laitetaan tuotteen toimittajan takana oleva toimitusaika, tai toimittajan oletus,
        // mik‰li tuotteella ei ole yht‰‰n tuloa
        $korjattu_ema = round($ttrow['toimitusaika'], 2);
      }

      unset($valtrow);

      if ($ttrow['valuutta'] != $yhtiorow['valkoodi']) {

        // haetaan vienti_kurssi
        $query = "SELECT nimi, kurssi, tunnus
                  FROM valuu
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND nimi    = '{$ttrow['valuutta']}'
                  ORDER BY jarjestys";
        $vresult = pupe_query($query);
        if (mysql_num_rows($vresult) == 1) {
          $valtrow = mysql_fetch_assoc($vresult);
        }
      }

      if (!isset($valtrow)) $valtrow['kurssi'] = 1;

      // alehinta_ostoa varten tehd‰‰n pieni kikka ja k‰‰nnet‰‰n kurssi
      // t‰m‰ siksi ett‰ toimittajan valuuttaa katsotaan funkkarissa ns kotivaluuttana vs oikea kotivaluutta
      $valtrow['kurssi'] = 1 / $valtrow['kurssi'];

      // Hetaan kaikki ostohinnat yhtiˆn oletusvaluutassa
      $laskurow = array(
        'liitostunnus'  => $ttrow['toimittaja'],
        'valkoodi'      => $yhtiorow["valkoodi"],
        'vienti_kurssi' => $valtrow['kurssi'],
        'ytunnus'       => $ttrow['ytunnus'],
      );

      // Haetaan ostohinta
      list($ostohinta, $netto, $alennus, $valuutta) = alehinta_osto($laskurow, array("tuoteno" => $row['tuoteno']), 1, '', '', '');

      $alennukset      = 1;
      $ostohinta_netto = $ostohinta;

      // lis‰t‰‰n kuluprosentti hintaan jos sit‰ k‰ytet‰‰n saapumisellakin
      if (in_array($yhtiorow['jalkilaskenta_kuluperuste'], array('KP', 'VS', 'PX'))) {
        $ostohinta_netto = $ostohinta_netto * (1 + ($ttrow['oletus_kulupros'] / 100));
      }

      // Nolla tai pienempi on virhe, laitetaan ne vikaks
      if ($ostohinta <= 0) {
        $ostohinta = 0;
        $ostohinta_netto = 9999999;
        $netto     = "N";
      }

      // Jos ei ole nettohinta, niin lasketaan alennukset
      if (empty($netto)) {
        $alennukset = generoi_alekentta_php($alennus, 'O', 'kerto', 'EI');

        $ostohinta_netto = $ostohinta_netto * $alennukset;
      }

      $ostohinta_netto  = round($ostohinta_netto, 6);
      $alennukset       = round((1 - $alennukset) * 100, 2);

      $ttrow['ostohinta_oletusvaluutta']        = $ostohinta;
      $ttrow['ostohinta_oletusvaluutta_netto']  = $ostohinta_netto;
      $ttrow['alennukset_oletusvaluutta_netto'] = $alennukset;

      $pakkaukset = tuotteen_toimittajat_pakkauskoot($ttrow['tutotunnus'], 'suurin');
      $ttrow['lavakoko'] = !empty($pakkaukset) ? $pakkaukset[0][0] : '0';

      $toimittajat_a_hinta[] = $ostohinta_netto;
      $toimittajat_a[]       = $ttrow;

      // Tuotteen toimittajatiedot omaan failiin
      $trivi  = $row['maa']."-".pupesoft_csvstring($row['tuoteno']).";";
      $trivi .= pupesoft_csvstring($row['tuoteno']).";";
      $trivi .= "{$row['maa']}-{$ttrow['toimittaja']};";
      $trivi .= pupesoft_csvstring($ttrow['toim_tuoteno']).";";
      $trivi .= pupesoft_csvstring($ttrow['toim_nimitys']).";";
      $trivi .= "{$ttrow['osto_era']};";
      $trivi .= "{$ttrow['pakkauskoko']};";
      $trivi .= "{$ttrow['lavakoko']};";
      $trivi .= "{$ttrow['ostohinta_oletusvaluutta']};";
      $trivi .= "{$ttrow['alennukset_oletusvaluutta_netto']};";
      $trivi .= "{$yhtiorow["valkoodi"]};";
      $trivi .= "{$ttrow["oletus_kulupros"]};";
      $trivi .= "{$ttrow['toim_yksikko']};";
      $trivi .= "{$ttrow['tuotekerroin']};";
      $trivi .= "{$ttrow['jarjestys']};";
      $trivi .= "$korjattu_ema";
      $trivi .= "\n";

      fwrite($tfp, $trivi);
    }

    // p‰‰toimittaja talteen t‰s vaihees
    $parastoimittaja = $toimittajat_a[0];

    // jos j‰rjestys parastoimitajal on 1, ei katsota ollenkaan halvempia toimittajia vaan menn‰‰n aina p‰‰toimittajalla
    if ($parastoimittaja['jarjestys'] != 1) {

      array_multisort($toimittajat_a_hinta, SORT_ASC, $toimittajat_a);

      // jos parastoimittajan j‰rjestys on 2 eli "ehdollinen p‰‰toimittaja",
      // katsotaan onko halvin toimittaja yli 5% halvempi ja jos, niin k‰ytet‰‰n sit‰
      if ($parastoimittaja['jarjestys'] == 2) {
        if ($parastoimittaja['ostohinta_oletusvaluutta_netto'] > ($toimittajat_a[0]['ostohinta_oletusvaluutta_netto'] * 1.05)) {
          $parastoimittaja = $toimittajat_a[0];
        }
      }
      else {
        // muussa tapauksessa otetaan aina halvin toimittaja
        $parastoimittaja = $toimittajat_a[0];
      }
    }
  }

  if ($parastoimittaja['toimittaja'] > 0) {
    $parastoimittaja['toimittaja'] = $row['maa']."-".$parastoimittaja['toimittaja'];
  }

  // Tuotteen toimittajan tiedot
  $rivi .= "{$parastoimittaja['toimittaja']};";
  $rivi .= pupesoft_csvstring($parastoimittaja['toim_tuoteno']).";";
  $rivi .= pupesoft_csvstring($parastoimittaja['toim_nimitys']).";";
  $rivi .= "{$parastoimittaja['osto_era']};";
  $rivi .= "{$parastoimittaja['pakkauskoko']};";
  $rivi .= "{$parastoimittaja['lavakoko']};";
  $rivi .= "{$parastoimittaja['ostohinta_oletusvaluutta']};";
  $rivi .= "{$parastoimittaja['alennukset_oletusvaluutta_netto']};";
  $rivi .= "{$parastoimittaja['valuutta']};";
  $rivi .= "{$parastoimittaja['oletus_kulupros']};";
  $rivi .= "{$parastoimittaja['toim_yksikko']};";
  $rivi .= "{$parastoimittaja['tuotekerroin']};";
  $rivi .= "{$parastoimittaja['jarjestys']};";

  // Vastaavat tuotteet
  $vastaavat = new Vastaavat($row['tuoteno']);

  $vastaavat_t = "";

  // Jos tuote kuulu useampaan kuin yhteen vastaavuusketjuun
  if ($vastaavat->onkovastaavia()) {

    // Ketjujen id:t
    foreach (explode(",", $vastaavat->getIDt()) as $ketju) {

      // Haetaan tuotteet ketjukohtaisesti
      $_tuotteet = $vastaavat->tuotteet($ketju);

      // Lis‰t‰‰n lˆydetyt vastaavat mahdollisten myyt‰vien joukkoon
      foreach ($_tuotteet as $_tuote) {
        $vastaavat_t .= pupesoft_csvstring($_tuote["tuoteno"]).":";
      }
    }

    // Vika : pois
    $vastaavat_t = substr($vastaavat_t, 0, -1);
  }

  $rivi .= $vastaavat_t;
  $rivi .= "\n";
  fwrite($fp, $rivi);

  $k_rivi++;
}

fclose($fp);
fclose($tfp);

// Tehd‰‰n FTP-siirto
if (($paiva_ajo or $weekly_ajo) and !empty($relex_ftphost)) {
  // Tuotetiedot
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftppath = "/data/input";
  $ftpfile = $filepath;
  require "inc/ftp-send.inc";

  // Tuotteen toimittajatiedot
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftppath = "/data/input";
  $ftpfile = $tfilepath;
  require "inc/ftp-send.inc";
}

echo date("d.m.Y @ G:i:s") . ": Relex tuotteet valmis.\n\n";
