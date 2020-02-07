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

// Hardcoodataan nämä
$myyntitilauksen_tunnus = 11782767;
$myyntilaskun_tunnus = 11782769;
$ostolaskun_tunnus = 11782770;

// Varmistetaan laskut
$query = "SELECT tunnus
          FROM lasku
          WHERE yhtio = 'artr'
          AND viesti  = 'Liiketoimintakauppa'
          AND tunnus  in ($myyntitilauksen_tunnus, $myyntilaskun_tunnus, $ostolaskun_tunnus)";
$result = pupe_query($query);

if (mysql_num_rows($result) != 3) {
  die("väärät!");
}

// Nollataan laskujen summat
$query = "UPDATE lasku set
          summa            = 0,
          summa_valuutassa = 0
          WHERE tunnus     in ($myyntitilauksen_tunnus, $myyntilaskun_tunnus, $ostolaskun_tunnus)";
$result = pupe_query($query);

// Yliviivatataan osto ja myyntilaskun tiliöinnit
$query = "UPDATE tiliointi set korjattu = 'admin', korjausaika = now()
          WHERE yhtio  = 'artr'
          and ltunnus  in ($myyntilaskun_tunnus, $ostolaskun_tunnus)
          and korjattu = ''";
$result = pupe_query($query);

// Poistetaan tilausrivit
$query = "UPDATE tilausrivi set tyyppi = 'D'
          WHERE yhtio = 'artr'
          and otunnus = $myyntitilauksen_tunnus
          and tyyppi  = 'L'";
$result = pupe_query($query);

$kukarow['kesken'] = $myyntitilauksen_tunnus;

$query = "SELECT * FROM lasku WHERE tunnus = $myyntitilauksen_tunnus";
$result = pupe_query($query);
$laskurow = mysql_fetch_assoc($result);

echo "Haetaan myyntitapahtumat\n";

// Haetaan hyvitystapahtumat
$query = "SELECT tapahtuma.laji,
          ifnull(yhtion_toimipaikat.postitp, 'MUUT') postitoimipaikka,
          ifnull(yhtion_toimipaikat.nimi, 'Muut') toimipaikkanimi,
          ifnull(yhtion_toimipaikat.kustp, 0) kustp,
          ifnull(yhtion_toimipaikat.kohde, 0) kohde,
          ifnull(yhtion_toimipaikat.projekti, 0) projekti,
          sum(tapahtuma.kpl * tapahtuma.kplhinta) summa,
          sum(tapahtuma.kpl * tapahtuma.hinta) arvo
          FROM tapahtuma
          INNER JOIN varastopaikat ON (varastopaikat.yhtio = tapahtuma.yhtio
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

echo "Lisätään tuotteet\n";

// Lisätään niistä tuotteet myyntitilaukselle
while ($query_row = mysql_fetch_assoc($query_result)) {

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

  error_reporting(E_ALL ^E_NOTICE);
  lisaa_rivi($parametrit);
  error_reporting(E_ALL);
}

echo "Haetaan kaikki tapahtumat\n";

// Haetaan kaikki fuusio tapahtumat ja tehdään tiliöinnit
$query = "SELECT tapahtuma.laji,
          ifnull(yhtion_toimipaikat.postitp, 'MUUT') postitoimipaikka,
          ifnull(yhtion_toimipaikat.nimi, 'Muut') toimipaikkanimi,
          ifnull(yhtion_toimipaikat.kustp, 0) kustp,
          ifnull(yhtion_toimipaikat.kohde, 0) kohde,
          ifnull(yhtion_toimipaikat.projekti, 0) projekti,
          sum(tapahtuma.kpl * tapahtuma.kplhinta) summa,
          sum(tapahtuma.kpl * tapahtuma.hinta) arvo
          FROM tapahtuma
          INNER JOIN varastopaikat ON (varastopaikat.yhtio = tapahtuma.yhtio
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

echo "Tehdään tiliöinnit\n";

while ($query_row = mysql_fetch_assoc($query_result)) {

  $summa_varasto = $query_row['arvo'];
  $avoin_saldo = $query_row['summa'] * 1.24;

  // Jos tulo, tehdään ostovelat/osto
  if ($query_row['laji'] == 'tulo') {
    $ltunnus = $ostolaskun_tunnus;

    // Tehdään ostovelat -tiliöinti (miinusta)
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

    // Tehdään osto -tiliöinti (plussaa)
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
  }
  // Jos myynti, tehdään myyntisaamiset/myynti
  else {
    $ltunnus = $myyntilaskun_tunnus;

    // Tehdään myyntisaamiset -tiliöinti (miinusta)
    $params = array(
      "tunnus" => $ltunnus,
      "tili" => '16211',
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

    // Tehdään myynti -tiliöinti (plussaa)
    $params = array(
      "tunnus" => $ltunnus,
      "tili" => '31111',
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

    // Jos laskutus, niin laskun summa miinusta
    $avoin_saldo *= -1;
  }

  // Tehdään varasto -tiliöinti (plussaa)
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

  // Tehdään Varastonmuutos -tiliöinti (miinusta)
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

  // päivitetään laskulle summat tiliöinneistä
  $query2 = "UPDATE lasku SET
             summa            = summa + $avoin_saldo,
             summa_valuutassa = summa
             WHERE yhtio      = 'artr'
             AND tunnus       = $ltunnus";
  $result2 = pupe_query($query2);
}

// Päivitetään summa myyntitilauksellekin
$query = "SELECT summa
          FROM lasku
          WHERE tunnus = $myyntilaskun_tunnus";
$result = pupe_query($query);
$laskurow = mysql_fetch_assoc($result);

$query = "UPDATE lasku SET
          summa            = {$laskurow['summa']},
          summa_valuutassa = summa
          WHERE tunnus     = $myyntitilauksen_tunnus";
$result = pupe_query($query);
