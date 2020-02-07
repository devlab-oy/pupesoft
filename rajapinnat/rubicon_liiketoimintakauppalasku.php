<?php

if (php_sapi_name() != 'cli') {
  die('clionly!');
}

if (!isset($argv[1])) {
  echo "Anna Puperoot\n";
  exit(1);
}

$pupesoft_root = $argv[1];

if (!is_dir($pupesoft_root) or !is_file("{$pupesoft_root}/inc/salasanat.php")) {
  echo "Pupesoft root missing!";
  exit(1);
}

// Pupesoft root include_pathiin
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupesoft_root.PATH_SEPARATOR."/usr/share/pear");

// Otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";
require "tilauskasittely/luo_myyntitilausotsikko.inc";

// Logitetaan ajo
cron_log();

// Tämä vaatii paljon muistia
error_reporting(E_ALL);
ini_set("memory_limit", "5G");
ini_set("display_errors", 1);
unset($pupe_query_debug);

$tanaan = '2014-04-20';

// hae $yhtiorow
$yhtiorow = hae_yhtion_parametrit('artr');
$kukarow = hae_kukarow('admin', 'artr');

$erpcm = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));
$ltunnus = luo_myyntitilausotsikko('RIVISYOTTO', 102365, '', '', 'Liiketoimintakauppa');

$kukarow['kesken'] = $ltunnus;

$query = "SELECT * FROM lasku WHERE tunnus = $ltunnus";
$result = pupe_query($query);
$laskurow = mysql_fetch_assoc($result);

$query = "SELECT tapahtuma.laji,
          ifnull(yhtion_toimipaikat.postitp, 'MUUT') postitoimipaikka,
          ifnull(yhtion_toimipaikat.nimi, 'Muut') toimipaikkanimi,
          ifnull(yhtion_toimipaikat.kustp, 0) kustp,
          ifnull(yhtion_toimipaikat.kohde, 0) kohde,
          ifnull(yhtion_toimipaikat.projekti, 0) projekti,
          sum(tapahtuma.kpl * tapahtuma.kplhinta) summa,
          sum(tapahtuma.kpl * tapahtuma.hinta) arvo
          FROM tapahtuma
          JOIN varastopaikat ON (varastopaikat.yhtio = tapahtuma.yhtio
            AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0'))
                                          <= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'),lpad(upper(tapahtuma.hyllynro), 5, '0'))
            AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0'))
                                          >= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'),lpad(upper(tapahtuma.hyllynro), 5, '0')))
          LEFT JOIN yhtion_toimipaikat ON (yhtion_toimipaikat.yhtio = tapahtuma.yhtio
            AND yhtion_toimipaikat.tunnus = varastopaikat.toimipaikka)
          WHERE tapahtuma.yhtio           = 'artr'
          AND tapahtuma.laji              in ('laskutus')
          AND tapahtuma.laadittu          >= '$tanaan'
          AND tapahtuma.laatija           = 'fuusio'
          AND tapahtuma.selite            = 'Liiketoimintakauppa Mas Orum'
          GROUP BY 1,2,3,4,5,6
          ORDER BY 1,2,3,4,5,6";
$query_result = pupe_query($query);

$total_items = mysql_num_rows($query_result);
$current_item = 0;

while ($query_row = mysql_fetch_assoc($query_result)) {

  $current_item++;
  progress_bar($current_item, $total_items);

  $tuoteno = "LIIKETOIMINTAKAUPPA-$query_row[postitoimipaikka]";

  // lisätään vielä yksi saldoton tuote laskulle suoraan
  $query = "INSERT INTO tuote SET
            yhtio      = 'artr',
            tuoteno    = '$tuoteno',
            nimitys    = 'Liiketoimintakauppa $query_row[toimipaikkanimi]',
            osasto     = '',
            try        = '',
            status     = 'P',
            kustp      = '532',
            kohde      = '',
            projekti   = '',
            ei_saldoa  = 'o',
            alv        = 24,
            luontiaika = now(),
            laatija    = 'fuusio'
            ON duplicate key UPDATE muutospvm = now(), muuttaja = 'fuusio'";
  pupe_query($query);

  $query = "SELECT * FROM tuote WHERE yhtio = 'artr' AND tuoteno = '$tuoteno'";
  $result = pupe_query($query);
  $trow = mysql_fetch_assoc($result);

  $parametrit = array(
    'trow' => $trow,
    'laskurow' => $laskurow,
    'kpl' => -1,
    'hinta' => $query_row['summa'],
    'tuoteno' => $trow['tuoteno'],
  );

  lisaa_rivi($parametrit);
}

echo "\n\n\n\n\n\n";

require "tilauskasittely/tilaus-valmis.inc";

$laskutettavat = $ltunnus;
$tee = "TARKISTA";
$laskutakaikki = "KYLLA";
$silent = "VIENTI";

$argv[1] = "artr";

require "tilauskasittely/verkkolasku.php";

$query = "SELECT laskunro
          FROM lasku
          WHERE yhtio = 'artr'
          AND tunnus  = '$ltunnus'";
$result = pupe_query($query);
$lxrow = mysql_fetch_assoc($result);

$query = "SELECT tunnus
          FROM lasku
          WHERE yhtio  = 'artr'
          AND tila     = 'U'
          AND alatila  = 'X'
          AND laskunro = '{$lxrow['laskunro']}'";
$result = pupe_query($query);
$uxrow = mysql_fetch_assoc($result);

$data = file_get_contents($path_orum);
$data  = mysql_real_escape_string($data);
$path_parts = pathinfo($path_muut);
$filename = $path_parts['basename'];
$filesize = filesize($path_muut);

// liitetiedosto
$query = "INSERT INTO liitetiedostot SET
          yhtio           = 'artr',
          liitos          = 'lasku',
          liitostunnus    = '$uxrow[tunnus]',
          data            = '$data',
          selite          = 'Liiketoimintakaupan tuotelista',
          kieli           = 'fi',
          filename        = '$filename',
          filesize        = '$filesize',
          filetype        = 'text',
          image_width     = '',
          image_height    = '',
          image_bits      = '',
          image_channels  = '',
          kayttotarkoitus = 'fuusio',
          jarjestys       = '1',
          laatija         = 'fuusio',
          luontiaika      = now()";
$editilaus_laskulle_result = pupe_query($query);

// Mas toimittaja toimi.tunnus = 27403 (täs vaihees toimittajat jo artr:ssä, tsekkaa viel toi toimittajakonversioissa!)
$query = "SELECT *
          FROM toimi
          WHERE yhtio = 'artr'
          AND tunnus  = '27403'";
$query_result = pupe_query($query);
$trow = mysql_fetch_assoc($query_result);

// Lisätään lasku
$query1 = "INSERT into lasku set
           yhtio             = 'artr',
           summa             = '',
           kasumma           = '',
           erpcm             = '$erpcm',
           kapvm             = '',
           olmapvm           = '$erpcm',
           valkoodi          = 'EUR',
           hyvak1            = '$trow[oletus_hyvak1]',
           hyvak2            = '$trow[oletus_hyvak2]',
           hyvak3            = '$trow[oletus_hyvak3]',
           hyvak4            = '$trow[oletus_hyvak4]',
           hyvak5            = '$trow[oletus_hyvak5]',
           hyvaksyja_nyt     = '$trow[oletus_hyvak1]',
           ytunnus           = '$trow[ytunnus]',
           tilinumero        = '$trow[tilinumero]',
           nimi              = '$trow[nimi]',
           nimitark          = '$trow[nimitark]',
           osoite            = '$trow[osoite]',
           osoitetark        = '$trow[osoitetark]',
           postino           = '$trow[postino]',
           postitp           = '$trow[postitp]',
           maa               = '$trow[maa]',
           viite             = '',
           viesti            = 'Liiketoimintakauppa',
           vienti            = 'A',
           tapvm             = now(),
           ebid              = '',
           tila              = 'H',
           ultilno           = '$trow[ultilno]',
           pankki_haltija    = '$trow[pankki_haltija]',
           swift             = '$trow[swift]',
           pankki1           = '$trow[pankki1]',
           pankki2           = '$trow[pankki2]',
           pankki3           = '$trow[pankki3]',
           pankki4           = '$trow[pankki4]',
           vienti_kurssi     = '1',
           laatija           = 'fuusio',
           liitostunnus      = '$trow[tunnus]',
           hyvaksynnanmuutos = 'o',
           suoraveloitus     = '',
           luontiaika        = now(),
           comments          = '',
           laskunro          = '',
           sisviesti1        = '',
           alv_tili          = '24'";
pupe_query($query1);
$ostolasku = mysql_insert_id();

echo "Luotiin ostolasku (tunnus: $ostolasku)\n";

$data = file_get_contents($path_muut);
$data  = mysql_real_escape_string($data);
$path_parts = pathinfo($path_muut);
$filename = $path_parts['basename'];
$filesize = filesize($path_muut);

// liitetiedosto
$query = "INSERT INTO liitetiedostot SET
          yhtio           = 'artr',
          liitos          = 'lasku',
          liitostunnus    = '$ostolasku',
          data            = '$data',
          selite          = 'Liiketoimintakaupan tuotelista',
          kieli           = 'fi',
          filename        = '$filename',
          filesize        = '$filesize',
          filetype        = 'text',
          image_width     = '',
          image_height    = '',
          image_bits      = '',
          image_channels  = '',
          kayttotarkoitus = 'fuusio',
          jarjestys       = '1',
          laatija         = 'fuusio',
          luontiaika      = now()";
$editilaus_laskulle_result = pupe_query($query);

// haetaan tiliöinnit
// TODO kato onko tapahtuma.hinta väärin, vai pitäskö olla tuote.kehahin?
// Höms, onko varastotiliöinneissä varasto ja varastonmuutos laskutuksessa ja tulossa samalla summalla?
$query = "SELECT tapahtuma.laji,
          ifnull(yhtion_toimipaikat.postitp, 'MUUT') postitoimipaikka,
          ifnull(yhtion_toimipaikat.nimi, 'Muut') toimipaikkanimi,
          ifnull(yhtion_toimipaikat.kustp, 0) kustp,
          ifnull(yhtion_toimipaikat.kohde, 0) kohde,
          ifnull(yhtion_toimipaikat.projekti, 0) projekti,
          sum(tapahtuma.kpl * tapahtuma.kplhinta) summa,
          sum(tapahtuma.kpl * tapahtuma.hinta) arvo
          FROM tapahtuma
          JOIN varastopaikat ON (varastopaikat.yhtio = tapahtuma.yhtio
            AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0'))
                                          <= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'),lpad(upper(tapahtuma.hyllynro), 5, '0'))
            AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0'))
                                          >= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'),lpad(upper(tapahtuma.hyllynro), 5, '0')))
          LEFT JOIN yhtion_toimipaikat ON (yhtion_toimipaikat.yhtio = tapahtuma.yhtio
            AND yhtion_toimipaikat.tunnus = varastopaikat.toimipaikka)
          WHERE tapahtuma.yhtio           = 'artr'
          AND tapahtuma.laji              in ('laskutus','tulo')
          AND tapahtuma.laadittu          >= '$tanaan'
          AND tapahtuma.laatija           = 'fuusio'
          AND tapahtuma.selite            = 'Liiketoimintakauppa Mas Orum'
          GROUP BY 1,2,3,4,5,6
          ORDER BY 1,2,3,4,5,6";
$query_result = pupe_query($query);

$total_items = mysql_num_rows($query_result);
$current_item = 0;

while ($query_row = mysql_fetch_assoc($query_result)) {

  $current_item++;
  progress_bar($current_item, $total_items);

  $summa_varasto = $query_row['arvo'];
  $avoin_saldo = $query_row['summa'] * 1.24;

  if ($query_row['laji'] != 'tulo') {
    $ltunnus = $uxrow['tunnus'];
  }
  else {
    $ltunnus = $ostolasku;

    // Tehdään ostovelat -tiliöinti
    $params = array(
      "tunnus" => $ltunnus,
      "tili" => '26417',
      "summa" => ($avoin_saldo * -1),
      "vero" => 0,
      "kustp" => $query_row['kustp'],
      "kohde" => $query_row['kohde'],
      "projekti" => $query_row['projekti'],
      "selite" => "Liiketoimintakauppa {$query_row['toimipaikkanimi']}",
    );
    tee_tiliointi($params);

    // Tehdään osto -tiliöinti
    $params = array(
      "tunnus" => $ltunnus,
      "tili" => '44131',
      "summa" => $avoin_saldo,
      "vero" => 24,
      "kustp" => $query_row['kustp'],
      "kohde" => $query_row['kohde'],
      "projekti" => $query_row['projekti'],
      "selite" => "Liiketoimintakauppa {$query_row['toimipaikkanimi']}",
    );
    tee_tiliointi($params);

    // päivitetään laskulle summat tiliöinneistä
    $query2 = "UPDATE lasku SET
               summa            = summa + ($avoin_saldo * -1),
               summa_valuutassa = summa
               WHERE yhtio      = 'artr'
               AND tunnus       = $ltunnus";
    $result2 = pupe_query($query2);
  }

  // Tehdään varasto -tiliöinti
  $params = array(
    "tunnus" => $ltunnus,
    "tili" => '14311',
    "summa" => $summa_varasto,
    "vero" => 0,
    "kustp" => $query_row['kustp'],
    "kohde" => $query_row['kohde'],
    "projekti" => $query_row['projekti'],
    "selite" => "Liiketoimintakauppa {$query_row['toimipaikkanimi']}",
  );
  tee_tiliointi($params);

  // Tehdään Varastonmuutos -tiliöinti
  $params = array(
    "tunnus" => $ltunnus,
    "tili" => '44311',
    "summa" => ($summa_varasto * -1),
    "vero" => 0,
    "kustp" => $query_row['kustp'],
    "kohde" => $query_row['kohde'],
    "projekti" => $query_row['projekti'],
    "selite" => "Liiketoimintakauppa {$query_row['toimipaikkanimi']}",
  );
  tee_tiliointi($params);
}

echo "\n\n\n\n\n\n";

/* Näillä voi varmistaa, että luvut täsmää:

SELECT
ifnull(yhtion_toimipaikat.nimi,'') nimi,
sum(round(tapahtuma.kpl * tapahtuma.kplhinta)) masin_varastonarvo
FROM tapahtuma
INNER JOIN varastopaikat ON
(varastopaikat.yhtio = tapahtuma.yhtio
AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0'))
<= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'),lpad(upper(tapahtuma.hyllynro), 5, '0'))
AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0'))
>= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'),lpad(upper(tapahtuma.hyllynro), 5, '0')))
LEFT JOIN yhtion_toimipaikat ON
(yhtion_toimipaikat.yhtio = tapahtuma.yhtio
AND yhtion_toimipaikat.tunnus = varastopaikat.toimipaikka)
WHERE tapahtuma.yhtio = 'artr'
AND tapahtuma.laji in ('laskutus','tulo')
AND tapahtuma.laadittu >= '2014-04-25'
AND tapahtuma.laatija = 'fuusio'
AND tapahtuma.selite = 'Liiketoimintakauppa Mas Orum'
GROUP BY yhtion_toimipaikat.nimi
ORDER BY masin_varastonarvo desc, yhtion_toimipaikat.nimi;

SELECT varastopaikat.nimitys nimi,
sum(round(tuotepaikat.saldo *
if(tuote.epakurantti100pvm='0000-00-00',
  if(tuote.epakurantti75pvm='0000-00-00',
    if(tuote.epakurantti50pvm='0000-00-00',
      if(tuote.epakurantti25pvm='0000-00-00', tuote.kehahin, tuote.kehahin * 0.75),
    tuote.kehahin * 0.5),
  tuote.kehahin * 0.25),
0))) masin_varastonarvo
FROM tuotepaikat
INNER JOIN tuote ON (tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio)
INNER JOIN varastopaikat ON (varastopaikat.yhtio=tuotepaikat.yhtio
  AND concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0'))
  <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
  AND concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0'))
  >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')))
WHERE tuotepaikat.yhtio = 'atarv'
AND tuotepaikat.saldo <> 0
GROUP BY varastopaikat.nimitys
ORDER BY masin_varastonarvo desc, varastopaikat.nimitys;

*/
