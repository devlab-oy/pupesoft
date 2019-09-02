<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$PHP_CLI = true;

if (!isset($argv[1])) {
  die ("Anna yhtio parametriksi!");
}

$pupe_root_polku = dirname(__FILE__);
date_default_timezone_set('Europe/Helsinki');

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");

// Otetaan tietokanta connect
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// Tehdään oletukset
$yhtio = mysql_real_escape_string($argv[1]);
$yhtiorow = hae_yhtion_parametrit($yhtio);
if (empty($yhtiorow)) {
  die("Yhtiö ei löydy.");
}

$kukarow = hae_kukarow('admin', $yhtio);
if (empty($kukarow)) {
  die("Admin -käyttäjä ei löydy.");
}

$asiakas_lasku = array();
$laskuja = 0;
$pulloja = 0;
$virheita = 0;

$query = "SELECT sns.tunnus, sns.sarjanumero, sns.myyntirivitunnus,
            DATEDIFF(NOW(), l.toimaika) paivia, l.tunnus laskutunnus, l.liitostunnus asiakasid, l.ketjutus,
            r.tunnus rivitunnus, r.tuoteno
          FROM sarjanumeroseuranta sns
          INNER JOIN tilausrivi r
            ON (r.yhtio = sns.yhtio AND r.tunnus = sns.myyntirivitunnus)
          INNER JOIN lasku l
            ON (l.yhtio = sns.yhtio AND l.tunnus = r.otunnus)
          INNER JOIN tuote t
            on (t.yhtio = sns.yhtio AND t.tuoteno = r.tuoteno)
          WHERE sns.yhtio = '{$kukarow['yhtio']}'
            AND sns.panttirivitunnus IS NULL
            AND sns.ostorivitunnus = 0
            AND r.hinta = 0
            AND t.pullopanttitarratulostus_kerayksessa = 'T'
          HAVING paivia >= 151";
$result = pupe_query($query);
while ($row = mysql_fetch_assoc($result)) {
  $asiakasid = $row['asiakasid'];

  $query = "SELECT * FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = {$row['tuoteno']}";
  $subresult = pupe_query($query);
  $tuoterow = mysql_fetch_assoc($subresult);
  $laskutustuoteno = t_tuotteen_avainsanat($tuoterow, "lisatieto_pantinlaskutustuote");
  echo "  -> {$tuoterow['tuoteno']} -> {$laskutustuoteno}\n";

  if ($laskutustuoteno != "" and $laskutustuoteno != "lisatieto_pantinlaskutustuote") {
    if (array_key_exists($asiakasid, $asiakas_lasku)) {
      $uusilaskutunnus = $asiakas_lasku[$asiakasid];
    }
    else {
      require "tilauskasittely/luo_myyntitilausotsikko.inc";
      $uusilaskutunnus = luo_myyntitilausotsikko("RIVISYOTTO", $row['asiakasid'], '', '', '', '', '', 'N', '', $row['ketjutus']);
      $asiakas_lasku[$asiakasid] = $uusilaskutunnus;
      $laskuja++;
    }

    $pulloja++;

    echo "  -> {$uusilaskutunnus}\n";
    $kukarow['kesken'] = $uusilaskutunnus;

    $query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = {$uusilaskutunnus}";
    $subresult = pupe_query($query);
    $laskurow = mysql_fetch_assoc($subresult);
    echo "  -> {$laskurow['tunnus']}\n";

    $query = "SELECT * FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$laskutustuoteno}'";
    $subresult = pupe_query($query);
    $lrow = mysql_fetch_assoc($subresult);

    $parametrit = array(
      'kpl'      => 1,
      'laskurow' => $laskurow,
      'trow'     => $lrow,
      'kommentti' => "# {$row['sarjanumero']}",
      'tuoteno'  => $lrow['tuoteno'],
    );
    $rivit = lisaa_rivi($parametrit);
    $rivitunnus = $rivit[0][0];

    $query = "UPDATE sarjanumeroseuranta SET panttirivitunnus = {$rivitunnus} WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = {$row['tunnus']}";
    pupe_query($query);

    $kukarow['kesken'] = "0";
    $query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' AND kuka = '{$kukarow['kuka']}'";
    pupe_query($query);

    echo "{$row['laskutunnus']}/{$row['rivitunnus']} -> {$laskurow['tunnus']}/{$rivitunnus} {$laskurow['nimi']}: {$tuoterow['nimitys']}, {$row['paivia']} pv" . ($row['ketjutus'] != " (ei ketjutusta)" ? "" : "") . "\n";
  }
  else {
    $virheita++;
    echo "Sarjanumerolle {$row['sarjanumero']} ei voitu luoda laskua, koska siihen liittyvän tuotteen tiedoista puuttuu tuotenumero, jolla laskutus tehdään.\n";
  }
}

foreach ($asiakas_lasku as $item => $laskutunnus) {
  echo "Merkitään tilaus {$laskutunnus} valmiiksi.";

  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus = '$laskutunnus'";
  $hyvitystilaukset = pupe_query($query);
  $laskurow = mysql_fetch_assoc($hyvitystilaukset);

  $kukarow["kesken"] = $laskutunnus;

  // tilaus valmis
  require "tilauskasittely/tilaus-valmis.inc";
}

$kukarow['kesken'] = "0";
$query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' AND kuka = '{$kukarow['kuka']}'";
pupe_query($query);

if ($pulloja == 0) {
  echo "Ei laksutettavia pulloja.";
}
else {
  echo "Luotiin {$laskuja} lasku" . ($laskuja != 1 ? "a" : "") . ", jo" . ($laskuja > 1 ? "i" : "") . "lla {$pulloja} pullo" . ($pulloja != 1 ? "a" : "")  . ".";
  if ($virheita > 0) {
    echo " Epäonnistuineita pulloja {$virheita} kappale" . ($virheita != 1 ? "tta" : "") . ".";
  }
}
echo "\n";
