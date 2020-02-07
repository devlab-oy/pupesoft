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
ini_set("include_path", ini_get("include_path")
  .PATH_SEPARATOR.$pupesoft_root
  .PATH_SEPARATOR."/usr/share/pear");

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

// hae yhtiorow, kukarow ja setataan päivämäärä
$yhtiorow = hae_yhtion_parametrit('atarv');
$kukarow = hae_kukarow('admin', 'atarv');
$tanaan = "2014-05-20 00:00:00";

function is_log($str) {
  echo date("d.m.Y @ G:i:s") . ": $str\n";
}


is_log("Haetaan saldolliset tuotteet");

// Haetaan kaikki saldolliset tuotteet
$query = "SELECT tuotepaikat.tunnus AS tuotepaikkatunnus,
          tuotepaikat.tuoteno,
          tuotepaikat.hyllyalue,
          tuotepaikat.hyllynro,
          tuotepaikat.hyllyvali,
          tuotepaikat.hyllytaso,
          tuotepaikat.saldo,
          round(if (tuote.epakurantti100pvm = '0000-00-00',
              if (tuote.epakurantti75pvm = '0000-00-00',
                if (tuote.epakurantti50pvm = '0000-00-00',
                  if (tuote.epakurantti25pvm = '0000-00-00',
                    tuote.kehahin,
                  tuote.kehahin * 0.75),
                tuote.kehahin * 0.5),
              tuote.kehahin * 0.25),
            0),
          6) kehahin
          FROM tuotepaikat
          INNER JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio
            AND tuote.tuoteno     = tuotepaikat.tuoteno
            AND tuote.ei_saldoa   = '')
          WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
          AND tuotepaikat.saldo   <> 0";
$query_result = pupe_query($query);

$total_items = mysql_num_rows($query_result);
$current_item = 0;

is_log("Aloitetaan päivitys");

while ($query_row = mysql_fetch_assoc($query_result)) {

  $current_item++;
  progress_bar($current_item, $total_items);

  // Tsekkaa kuka on ykköstoimittaja, jolta saldot on hommattu
  $query = "SELECT liitostunnus
            FROM tuotteen_toimittajat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$query_row['tuoteno']}'
            ORDER BY if(jarjestys = 0, 999, jarjestys), tunnus
            LIMIT 1";
  $result = pupe_query($query);
  $toimitunnus_row = mysql_fetch_assoc($result);

  // Örumin tunnus
  if ($toimitunnus_row['liitostunnus'] == '27371') {
    $tapahtumalaji = 'tulo';
  }
  else {
    $tapahtumalaji = 'laskutus';
  }

  // Myydään tai palautetaan tuotteet Örumille
  $query = "INSERT INTO tapahtuma SET
            hinta      = {$query_row['kehahin']},
            kpl        = {$query_row['saldo']} * -1,
            kplhinta   = {$query_row['kehahin']},
            laadittu   = now(),
            laatija    = 'fuusio',
            laji       = '{$tapahtumalaji}',
            rivitunnus = 0,
            hyllyalue  = '{$query_row['hyllyalue']}',
            hyllynro   = '{$query_row['hyllynro']}',
            hyllyvali  = '{$query_row['hyllyvali']}',
            hyllytaso  = '{$query_row['hyllytaso']}',
            selite     = 'Liiketoimintakauppa Mas Orum',
            tuoteno    = '{$query_row['tuoteno']}',
            yhtio      = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  // Päivitetään saldo
  $query = "UPDATE tuotepaikat SET
            saldo       = 0
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$query_row['tuotepaikkatunnus']}'";
  $result = pupe_query($query);
}

echo "\n\n\n\n\n\n";

is_log("Haetaan laskutus -tapahtumat");

// Haetaan kaikki just tehdyt laskutus -tapahtumat ja tehdään niistä lasku
$query = "SELECT
          ifnull(yhtion_toimipaikat.postitp, 'MUUT') postitoimipaikka,
          ifnull(yhtion_toimipaikat.nimi, 'Muut') toimipaikkanimi,
          ifnull(yhtion_toimipaikat.kustp, 0) kustp,
          ifnull(yhtion_toimipaikat.kohde, 0) kohde,
          ifnull(yhtion_toimipaikat.projekti, 0) projekti,
          sum(tapahtuma.kpl * tapahtuma.kplhinta * -1) summa,
          sum(tapahtuma.kpl * tapahtuma.hinta * -1) arvo
          FROM tapahtuma
          JOIN varastopaikat ON (varastopaikat.yhtio = tapahtuma.yhtio
            AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0'))
                                          <= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'),lpad(upper(tapahtuma.hyllynro), 5, '0'))
            AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0'))
                                          >= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'),lpad(upper(tapahtuma.hyllynro), 5, '0')))
          LEFT JOIN yhtion_toimipaikat ON (yhtion_toimipaikat.yhtio = tapahtuma.yhtio
            AND yhtion_toimipaikat.tunnus = varastopaikat.toimipaikka)
          WHERE tapahtuma.yhtio           = '{$kukarow['yhtio']}'
          AND tapahtuma.laji              = 'laskutus'
          AND tapahtuma.laadittu          >= '$tanaan'
          AND tapahtuma.laatija           = 'fuusio'
          AND tapahtuma.selite            = 'Liiketoimintakauppa Mas Orum'
          GROUP BY 1,2,3,4,5
          ORDER BY 1,2,3,4,5";
$query_result = pupe_query($query);

is_log("Tehdään myyntilasku");

// Örumin asiakas.tunnus myyntilaskulle = 281057
error_reporting(E_ALL ^E_NOTICE);
$ltunnus = luo_myyntitilausotsikko('RIVISYOTTO', 281057, '', '', 'Liiketoimintakauppa');
error_reporting(E_ALL);

$kukarow['kesken'] = $ltunnus;

$query = "SELECT * FROM lasku WHERE tunnus = $ltunnus";
$result = pupe_query($query);
$laskurow = mysql_fetch_assoc($result);

is_log("Tilausnumero $ltunnus");

while ($query_row = mysql_fetch_assoc($query_result)) {

  // Perustetaan tuote per toimipaikka
  $tuoteno = "LIIKETOIMINTAKAUPPA-{$query_row["postitoimipaikka"]}";

  // lisätään vielä yksi saldoton tuote laskulle suoraan
  $query = "INSERT INTO tuote SET
            yhtio      = '{$kukarow['yhtio']}',
            tuoteno    = '$tuoteno',
            nimitys    = 'Liiketoimintakauppa {$query_row["toimipaikkanimi"]}',
            status     = 'P',
            kustp      = {$query_row["kustp"]},
            ei_saldoa  = 'o',
            alv        = 24,
            luontiaika = now(),
            laatija    = 'fuusio'
            ON duplicate key UPDATE muutospvm = now(), muuttaja = 'fuusio'";
  pupe_query($query);

  $query = "SELECT * FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '$tuoteno'";
  $result = pupe_query($query);
  $trow = mysql_fetch_assoc($result);

  $parametrit = array(
    'trow' => $trow,
    'laskurow' => $laskurow,
    'kpl' => 1,
    'hinta' => $query_row['summa'],
    'tuoteno' => $trow['tuoteno'],
  );

  error_reporting(E_ALL ^E_NOTICE);
  lisaa_rivi($parametrit);
  error_reporting(E_ALL);
}

error_reporting(E_ALL ^E_NOTICE);
require "tilauskasittely/tilaus-valmis.inc";
error_reporting(E_ALL);

$laskutettavat = $ltunnus;
$tee = "TARKISTA";
$laskutakaikki = "KYLLA";
$silent = "VIENTI";
$argv[1] = $kukarow['yhtio'];

error_reporting(E_ALL ^E_NOTICE);
require "tilauskasittely/verkkolasku.php";
error_reporting(E_ALL);

$query = "SELECT laskunro
          FROM lasku
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tunnus  = '$ltunnus'";
$result = pupe_query($query);
$lxrow = mysql_fetch_assoc($result);

$query = "SELECT tunnus
          FROM lasku
          WHERE yhtio  = '{$kukarow['yhtio']}'
          AND tila     = 'U'
          AND alatila  = 'X'
          AND laskunro = '{$lxrow['laskunro']}'";
$result = pupe_query($query);
$uxrow = mysql_fetch_assoc($result);

// Tätä tarvitaan kun tehdään tiliöinnit
$myyntilasku_tunnus = $uxrow["tunnus"];

is_log("\nMyyntilaskunumero {$lxrow['laskunro']}");
is_log("Tehdään ostolasku");

// Örum toimi.tunnus ostolaskulle = 27371, ytunnus FI20428100
$query = "SELECT *
          FROM toimi
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tunnus  = '27371'";
$query_result = pupe_query($query);
$trow = mysql_fetch_assoc($query_result);

// Lisätään lasku
$query1 = "INSERT into lasku set
           yhtio             = '{$kukarow['yhtio']}',
           summa             = '',
           kasumma           = '',
           erpcm             = date_add(CURRENT_DATE, interval 14 day),
           kapvm             = '',
           olmapvm           = date_add(CURRENT_DATE, interval 14 day),
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
$ostolasku_tunnus = mysql_insert_id();

is_log("Ostolasku $ostolasku_tunnus");

// Haetaan kaikki just tehdyt laskutus ja tulo -tapahtumat ja kirjanpito
$query = "SELECT tapahtuma.laji,
          ifnull(yhtion_toimipaikat.postitp, 'MUUT') postitoimipaikka,
          ifnull(yhtion_toimipaikat.nimi, 'Muut') toimipaikkanimi,
          ifnull(yhtion_toimipaikat.kustp, 0) kustp,
          ifnull(yhtion_toimipaikat.kohde, 0) kohde,
          ifnull(yhtion_toimipaikat.projekti, 0) projekti,
          sum(tapahtuma.kpl * tapahtuma.kplhinta * -1) summa,
          sum(tapahtuma.kpl * tapahtuma.hinta * -1) arvo
          FROM tapahtuma
          JOIN varastopaikat ON (varastopaikat.yhtio = tapahtuma.yhtio
            AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0'))
                                          <= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'),lpad(upper(tapahtuma.hyllynro), 5, '0'))
            AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0'))
                                          >= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'),lpad(upper(tapahtuma.hyllynro), 5, '0')))
          LEFT JOIN yhtion_toimipaikat ON (yhtion_toimipaikat.yhtio = tapahtuma.yhtio
            AND yhtion_toimipaikat.tunnus = varastopaikat.toimipaikka)
          WHERE tapahtuma.yhtio           = '{$kukarow['yhtio']}'
          AND tapahtuma.laji              in ('laskutus','tulo')
          AND tapahtuma.laadittu          >= '$tanaan'
          AND tapahtuma.laatija           = 'fuusio'
          AND tapahtuma.selite            = 'Liiketoimintakauppa Mas Orum'
          GROUP BY 1,2,3,4,5,6
          ORDER BY 1,2,3,4,5,6";
$query_result = pupe_query($query);

while ($query_row = mysql_fetch_assoc($query_result)) {

  $summa_varasto = $query_row['arvo'];
  $avoin_saldo = $query_row['summa'] * 1.24;
  $ltunnus = $myyntilasku_tunnus;

  // Jos kyseessä onkin tulo
  if ($query_row['laji'] == 'tulo') {
    $ltunnus = $ostolasku_tunnus;

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
    error_reporting(E_ALL ^E_NOTICE);
    tee_tiliointi($params);
    error_reporting(E_ALL);

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
    error_reporting(E_ALL ^E_NOTICE);
    tee_tiliointi($params);
    error_reporting(E_ALL);

    // päivitetään laskulle summat tiliöinneistä
    $query2 = "UPDATE lasku SET
               summa            = summa + ($avoin_saldo * -1),
               summa_valuutassa = summa
               WHERE yhtio      = '{$kukarow['yhtio']}'
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
  error_reporting(E_ALL ^E_NOTICE);
  tee_tiliointi($params);
  error_reporting(E_ALL);

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
  error_reporting(E_ALL ^E_NOTICE);
  tee_tiliointi($params);
  error_reporting(E_ALL);
}

is_log("Valmis");
