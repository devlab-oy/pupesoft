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

if (isset($argv[2]) and $argv[2] != '') {
  $paiva_ajo = TRUE;

  if ($argv[2] == "edpaiva") {
    $ajopaiva = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  }
}

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

$tuoterajaus = rakenna_relex_tuote_parametrit();

$tecd = FALSE;

if (@include "inc/tecdoc.inc") {
  $tecd = TRUE;
}

// Vastaavat tuotteet infraa
require "vastaavat.class.php";

// Tallennetaan tuoterivit tiedostoon
$filepath = "/tmp/product_update_{$yhtio}_$ajopaiva.csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

$tuotteet = "";

// P‰iv‰ajoon otetaan mukaan vain viimeisen vuorokauden aikana muuttuneet
if ($paiva_ajo) {

  $tuotelista       = "NULL";
  $namaonjotsekattu = "";

  $query = "SELECT tuote.tuoteno
            FROM tuote
            WHERE tuote.yhtio     = '{$yhtio}'
            {$tuoterajaus}
            AND (tuote.muutospvm  >= date_sub(now(), interval 24 HOUR)
              OR tuote.luontiaika >= date_sub(now(), interval 24 HOUR))";
  $res = pupe_query($query);

  while ($row = mysql_fetch_assoc($res)) {
    $tuotelista .= ",'".pupesoft_cleanstring($row["tuoteno"])."'";
  }

  $query = "SELECT tuotteen_toimittajat.tuoteno
            FROM tuotteen_toimittajat
            WHERE tuotteen_toimittajat.yhtio    = '{$yhtio}'
            AND tuotteen_toimittajat.tuoteno    not in ($tuotelista)
            AND (tuotteen_toimittajat.muutospvm >= date_sub(now(), interval 24 HOUR)
             OR tuotteen_toimittajat.luontiaika >= date_sub(now(), interval 24 HOUR))";
  $res = pupe_query($query);

  while ($row = mysql_fetch_assoc($res)) {
    $tuotelista .= ",'".pupesoft_cleanstring($row["tuoteno"])."'";
  }

  $tuotteet = " AND tuote.tuoteno IN ({$tuotelista}) ";
}

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
$header .= "ostajanro;";
$header .= "tuotepaallikko;";
$header .= "tuotetunnus;";
$header .= "rekisteriosumat;";
$header .= "elinkaari;";
$header .= "supplier;";
$header .= "suppliers_code;";
$header .= "suppliers_name;";
$header .= "ostoera;";
$header .= "pakkauskoko;";
$header .= "purchase_price;";
$header .= "alennus;";
$header .= "valuutta;";
$header .= "suppliers_unit;";
$header .= "tuotekerroin;";
$header .= "jarjestys;";
$header .= "vastaavat";
$header .= "\n";
fwrite($fp, $header);

// Tallennetaan tuotteentoimittajarivit tiedostoon
$tfilepath = "/tmp/product_suppliers_update_{$yhtio}_$ajopaiva.csv";

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
$header .= "purchase_price;";
$header .= "alennus;";
$header .= "valuutta;";
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
          if(tuote.ostoehdotus != 'E', 'K', 'E') ostoehdotus,
          tuote.tahtituote,
          if(tuote.myynti_era = 0, 1, tuote.myynti_era) myynti_era,
          if(tuote.minimi_era = 0, '', tuote.minimi_era) minimi_era,
          tuote.tuotekorkeus,
          tuote.tuoteleveys,
          tuote.tuotesyvyys,
          tuote.tuotemassa,
          tuote.ostajanro,
          tuote.tuotepaallikko,
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

echo "Tuoterivej‰ {$rows} kappaletta.\n";

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
  $rivi .= "{$row['ostajanro']};";
  $rivi .= "{$row['tuotepaallikko']};";
  $rivi .= "{$row['tunnus']};";

  if ($tecd) {
    $rivi .= td_regcarsum($row['tuoteno']).";";
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
          toimi.tunnus toimittaja,
          toimi.ytunnus ytunnus,
          tuotteen_toimittajat.toim_tuoteno,
          tuotteen_toimittajat.toim_nimitys,
          if(tuotteen_toimittajat.osto_era = 0, 1, tuotteen_toimittajat.osto_era) osto_era,
          if(tuotteen_toimittajat.pakkauskoko = 0, '', tuotteen_toimittajat.pakkauskoko) pakkauskoko,
          tuotteen_toimittajat.ostohinta,
          tuotteen_toimittajat.alennus,
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
      'ostohinta_oletusvaluutta'        => '',
      'alennukset_oletusvaluutta_netto' => '',
      'valuutta'                        => '',
      'toim_yksikko'                    => '',
      'tuotekerroin'                    => '',
      'jarjestys'                       => '',
      'toimitusaika_ema'                => '')
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
      }

      // Hetaan kaikki ostohinnat yhtiˆn oletusvaluutassa
      $laskurow = array(
        'liitostunnus'  => $ttrow['toimittaja'],
        'valkoodi'      => $yhtiorow["valkoodi"],
        'vienti_kurssi' => 1,
        'ytunnus'       => $ttrow['ytunnus'],
      );

      // Haetaan ostohinta
      list($ostohinta, $netto, $alennus, $valuutta) = alehinta_osto($laskurow, array("tuoteno" => $row['tuoteno']), 1, '', '', '');

      $alennukset      = 1;
      $ostohinta_netto = $ostohinta;

      // Nolla tai pienempi on virhe, laitetaan ne vikaks
      if ($ostohinta <= 0) {
        $ostohinta = $ostohinta_netto = 9999999999.99;
        $netto     = "N";
      }

      // Jos ei ole nettohinta, niin lasketaan alennukset
      if (empty($netto)) {
        $alennukset = generoi_alekentta_php($alennus, 'O', 'kerto', 'EI');

        $ostohinta_netto = $ostohinta_netto * $alennukset;
      }

      $ostohinta_netto = round($ostohinta_netto, 6);
      $alennukset = round((1 - $alennukset) * 100, 2);

      $ttrow['ostohinta_oletusvaluutta']        = $ostohinta;
      $ttrow['ostohinta_oletusvaluutta_netto']  = $ostohinta_netto;
      $ttrow['alennukset_oletusvaluutta_netto'] = $alennukset;

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
      $trivi .= "{$ttrow['ostohinta_oletusvaluutta']};";
      $trivi .= "{$ttrow['alennukset_oletusvaluutta_netto']};";
      $trivi .= "{$yhtiorow["valkoodi"]};";
      $trivi .= "{$ttrow['toim_yksikko']};";
      $trivi .= "{$ttrow['tuotekerroin']};";
      $trivi .= "{$ttrow['jarjestys']};";
      $trivi .= "$korjattu_ema";
      $trivi .= "\n";

      fwrite($tfp, $trivi);
    }

    // Valitaan edullisin toimittaja
    array_multisort($toimittajat_a_hinta, SORT_ASC, $toimittajat_a);
  }

  $parastoimittaja = $toimittajat_a[0];

  if ($parastoimittaja['toimittaja'] > 0) {
    $parastoimittaja['toimittaja'] = $row['maa']."-".$parastoimittaja['toimittaja'];
  }

  // Tuotteen toimittajan tiedot
  $rivi .= "{$parastoimittaja['toimittaja']};";
  $rivi .= pupesoft_csvstring($parastoimittaja['toim_tuoteno']).";";
  $rivi .= pupesoft_csvstring($parastoimittaja['toim_nimitys']).";";
  $rivi .= "{$parastoimittaja['osto_era']};";
  $rivi .= "{$parastoimittaja['pakkauskoko']};";
  $rivi .= "{$parastoimittaja['ostohinta_oletusvaluutta']};";
  $rivi .= "{$parastoimittaja['alennukset_oletusvaluutta_netto']};";
  $rivi .= "{$parastoimittaja['valuutta']};";
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

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp);
fclose($tfp);

// Tehd‰‰n FTP-siirto
if ($paiva_ajo and !empty($relex_ftphost)) {
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

echo "Valmis.\n";
