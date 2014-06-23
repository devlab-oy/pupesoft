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

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

$tecd = FALSE;

if (@include("inc/tecdoc.inc")) {
  $tecd = TRUE;
}


// Tallannetan rivit tiedostoon
$filepath = "/tmp/product_update_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
$header  = "code;";
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
$header .= "jarjestys\n";
fwrite($fp, $header);

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
          tuote.ostoehdotus,
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
          WHERE tuote.yhtio     = '$yhtio'
          AND tuote.status     != 'P'
          AND tuote.ei_saldoa   = ''
          AND tuote.tuotetyyppi = ''
          AND tuote.ostoehdotus = ''
          ORDER BY tuote.tuoteno";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Tuoterivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  // Tuotetiedot
  $rivi  = pupesoft_csvstring($row['tuoteno']).";";
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

  // Tuotteen p‰‰toimittaja
  $ttq = "SELECT
          toimi.tunnus toimittaja,
          tuotteen_toimittajat.toim_tuoteno,
          tuotteen_toimittajat.toim_nimitys,
          if(tuotteen_toimittajat.osto_era = 0, 1, tuotteen_toimittajat.osto_era) osto_era,
          if(tuotteen_toimittajat.pakkauskoko = 0, '', tuotteen_toimittajat.pakkauskoko) pakkauskoko,
          tuotteen_toimittajat.ostohinta,
          tuotteen_toimittajat.alennus,
          toimi.oletus_valkoodi valuutta,
          tuotteen_toimittajat.toim_yksikko,
          if(tuotteen_toimittajat.tuotekerroin = 0, 1, tuotteen_toimittajat.tuotekerroin) tuotekerroin,
          tuotteen_toimittajat.jarjestys
          FROM tuotteen_toimittajat
          JOIN toimi ON (tuotteen_toimittajat.yhtio = toimi.yhtio AND tuotteen_toimittajat.liitostunnus = toimi.tunnus)
          WHERE tuotteen_toimittajat.yhtio = '{$yhtio}'
          AND tuotteen_toimittajat.tuoteno = '{$row['tuoteno']}'
          ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys)
          LIMIT 1";
  $ttres = pupe_query($ttq);
  $ttrow = mysql_fetch_assoc($ttres);

  if ($ttrow['toimittaja'] > 0) {
     $ttrow['toimittaja'] = $row['maa']."-".$ttrow['toimittaja'];
  }

  // Tuotteen p‰‰toimittajan tiedot
  $rivi .= "{$ttrow['toimittaja']};";
  $rivi .= pupesoft_csvstring($ttrow['toim_tuoteno']).";";
  $rivi .= pupesoft_csvstring($ttrow['toim_nimitys']).";";
  $rivi .= "{$ttrow['osto_era']};";
  $rivi .= "{$ttrow['pakkauskoko']};";
  $rivi .= "{$ttrow['ostohinta']};";
  $rivi .= "{$ttrow['alennus']};";
  $rivi .= "{$ttrow['valuutta']};";
  $rivi .= "{$ttrow['toim_yksikko']};";
  $rivi .= "{$ttrow['tuotekerroin']};";
  $rivi .= "{$ttrow['jarjestys']};";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
