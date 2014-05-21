<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  return;
}

$data = $_GET;
error_reporting(E_ALL ^E_NOTICE);
ini_set("display_errors", 0);
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");

require ("inc/connect.inc");
require ("inc/functions.inc");

function rest_virhe_header($viesti) {
  // Mikäli kutsutaan esimerkiksi "asiakastarkista-funktiota" ja se palauttaa tekstimuodossa virheen, niin $virhe pitää myös utf8-encodata, tai tulee "500"-virhettä.
  $viesti = $viesti;
  header("HTTP/1.0 400 Bad Request");
  echo json_encode(array("status" => $viesti));
  die();
}

function rest_ok_header($viesti) {
  // Malli ratkaisu:
  // rest_ok_header("Päivitit asiakkaan: $muuttuja");
  $viesti = $viesti;
  header("HTTP/1.0 200 OK");
  echo json_encode(array("statuscode" => "OK", "status" => $viesti));
  die();
}

function rest_tilaa($params) {

  global $kukarow, $yhtiorow;

  // Hyväksytään seuraavat parametrit
  $kpl      = isset($params["kpl"])        ? (float) trim($params["kpl"]) : "";
  $tilausnumero  = isset($params["tilausnumero"])  ? mysql_real_escape_string(trim($params["tilausnumero"])) : 0;
  $tuoteno    = isset($params["tuoteno"])      ? mysql_real_escape_string(trim($params["tuoteno"])) : "";
  $tunnus      = isset($params["asiakastunnus"])      ? (int) trim($params["asiakastunnus"]) : "";
  $kommentti    = isset($params["tilauskommentti"])    ? mysql_real_escape_string(trim($params["tilauskommentti"])) : "";
  $toim        = "RIVISYOTTO";

  // Määritellään luo_myyntitilausotsikko -funkkari
  require("tilauskasittely/luo_myyntitilausotsikko.inc");

  if ($tuoteno == "") {
    rest_virhe_header("Tuotenumero puuttuu");
  }

  if ($kpl <= 0) {
    rest_virhe_header("Kappalemäärä ei saa olla 0 tai negatiivinen");
  }

  // tähän haaraan ei voida edes teoriassakaan tulla.
  if ($tunnus == "" or $tunnus == 0) {
    rest_virhe_header("Asiakastunnus puuttuu");
  }

  // asiakas tarkistus
  // Haetaan asiakkaan tiedot
  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tunnus  = '{$tunnus}'";
  $tulos = pupe_query($query);

  if (mysql_num_rows($tulos) == 0) {
    rest_virhe_header("Asiakasta ei löytynyt järjestelmästä");
  }

  // haetaan tuotteen tiedot
  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tuoteno = '$tuoteno'";
  $tuoteres = pupe_query($query);

  if (mysql_num_rows($tuoteres) == 0) {
    rest_virhe_header("Tuotetta \"{$tuoteno}\" ei löytynyt järjestelmästä");
  }

  // tuote löytyi ok
  $trow = mysql_fetch_assoc($tuoteres);

  // ei löytynyt tilausta tällä tunnisteella, pitää tehä uus!
  if ($tilausnumero == 0) {
    // varmistetaan, että käyttäjällä ei ole mitään kesken
    $kukarow["kesken"] = 0;

    $query  = "UPDATE kuka
               SET kesken = 0
               WHERE yhtio = '{$kukarow["yhtio"]}'
               AND kuka    = '{$kukarow["kuka"]}'";
    $update = pupe_query($query);

    // tässä kaattuuu
    $tilausnumero = luo_myyntitilausotsikko($toim, $tunnus, "", "", $kommentti, "", "");
  }

  $kukarow["kesken"] = $tilausnumero;

  $query = "SELECT *
            FROM lasku
            WHERE yhtio      = '{$kukarow["yhtio"]}'
            AND laatija      = '{$kukarow["kuka"]}'
            AND liitostunnus = '{$tunnus}'
            AND tila         = 'N'
            AND tunnus       = '{$tilausnumero}'";
  $kesken = pupe_query($query);

  if (mysql_num_rows($kesken) == 0) {
    rest_virhe_header("Tilausta ei löytynyt järjestelmästä");
  }

  $laskurow = mysql_fetch_assoc($kesken);

  // Tarkistetaan saldo
  list($saldo, $hyllyssa, $myytavissa, $bool) = saldo_myytavissa($tuoteno);

  if ($myytavissa < $kpl) {
    rest_virhe_header("Virhe. Saldo ei riitä");
  }

  $ytunnus      = $laskurow["ytunnus"];
  $kpl        = $kpl;
  $tuoteno      = $trow["tuoteno"];
  $toimaika      = $laskurow["toimaika"];
  $kerayspvm      = $laskurow["kerayspvm"];
  $hinta         = "";
  $netto        = "";
  $var        = "";
  $alv        = "";
  $paikka        = "";
  $varasto      = "";
  $rivitunnus      = "";
  $korvaavakielto    = "";
  $jtkielto       = $laskurow["jtkielto"];
  $varataan_saldoa  = "EI";
  $kommentti      = $kommentti;

  for ($alepostfix = 1; $alepostfix <= $yhtiorow["myynnin_alekentat"]; $alepostfix++) {
    ${"ale".$alepostfix} = "";
  }

  require("tilauskasittely/lisaarivi.inc");

  rest_ok_header($tilausnumero);
}

function rest_login($params) {

  global $kukarow, $yhtiorow;

  // Hyväksytään seuraavat parametrit
  $user = isset($params["user"]) ? mysql_real_escape_string(trim($params["user"])) : "";
  $pass = isset($params["pass"]) ? md5($params["pass"]) : "";
  $yhtio = isset($params["yhtio"]) ? mysql_real_escape_string(trim($params["yhtio"])) : "";
  $versio  = isset($params["versio"]) ? (float) pupesoft_cleannumber($params["versio"]) : 0;

  // Tehdään tarkistukset tähän väliin.
  if (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != 'on')  rest_virhe_header("Vain https on sallittu.");
  if ($versio != 0.1) rest_virhe_header("Versionumero ei ole sallittu.");

  // Vasta virhetarkistuksien jälkeen.
  // haetaan ensin käyttäjätiedot, sen jälkeen yhtiön kaikki tiedot ja yhtion_parametrit

  $query = "SELECT kuka.*
            FROM kuka
            WHERE kuka.yhtio  = '{$yhtio}'
            AND kuka.kuka     = '{$user}'
            AND kuka.salasana = '{$pass}'
            AND kuka.kuka !=''
            AND kuka.salasana !=''";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    rest_virhe_header("Syötetty käyttäjätunnus tai salasana on virheellinen");
  }

  $kukarow = mysql_fetch_assoc($result);

  // Haetaan yhtiörow
  $yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);
}

// Kirjataan käyttäjä sisään
rest_login($data);

$tyyppi = isset($data["tyyppi"]) ? pupesoft_cleanstring($data["tyyppi"]) : "";

// Tarkistetaan "tilauspuolen" muuttujat
if ($tyyppi == "order") {

  rest_tilaa($data);
}
elseif ($tyyppi == "customer") {
  $toiminto = isset($data["toiminto"]) ? strtoupper(pupesoft_cleanstring($data["toiminto"])) : "";

  $api_kentat = array();

  // Tehdään arrayn ekalle riville kenttien otsikot ja tokalle valuet
  foreach ($data as $key => $value) {
    if ($key != 'user' and $key != 'pass' and $key != 'tyyppi' and $key != 'versio' and $key !='yhtio' and $key != 'tunnus' and $key != 'toiminto') {
      $api_kentat[0][] = "asiakas.".$key;
      $api_kentat[1][] = $value;
    }
  }

  if(count($api_kentat) == 0) {
    rest_virhe_header("Data puuttuu");
  }

  // Vikaksi sarakkeeksi toiminto
  $api_kentat[0][] = "TOIMINTO";
  $api_kentat[1][] = $toiminto;

  require("lue_data.php");

  $api_output = strip_tags($api_output);

  if ($api_status === FALSE) {
    rest_virhe_header($api_output);
  }

  rest_ok_header($api_output);

}
else {
  rest_virhe_header("Valittu tyyppi ei ole sallittu");
}
