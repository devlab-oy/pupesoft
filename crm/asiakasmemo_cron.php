<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut yhtiötä!\n");
}

// lisätään includepathiin pupe-root
ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock();

$yhtio    = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);

$query = "SELECT kalenteri.*,
          IF(kuka.nimi != '',kuka.nimi, kalenteri.kuka) laatija,
          LEFT(kalenteri.pvmalku, 10) paivamaara,
          kalenteri.kentta01 AS viesti,
          lasku.tila laskutila,
          lasku.alatila laskualatila,
          kuka2.nimi laskumyyja,
          lasku.muutospvm laskumpvm
          FROM kalenteri
          LEFT JOIN kuka ON (kuka.yhtio = kalenteri.yhtio AND kuka.kuka = kalenteri.kuka)
          LEFT JOIN lasku ON (lasku.yhtio = kalenteri.yhtio AND lasku.tunnus = kalenteri.otunnus)
          LEFT JOIN kuka AS kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
          WHERE kalenteri.yhtio  = '{$yhtio}'
          AND kalenteri.tyyppi   = 'Muistutus'
          AND kalenteri.kuittaus = 'K'
          AND LEFT(kalenteri.pvmalku, 10) < CURDATE()";
$res = pupe_query($query);

while ($row = mysql_fetch_assoc($res)) {

  $kukarow = hae_kukarow($row['kuka'], $yhtiorow['yhtio']);

  if (empty($kukarow['eposti'])) continue;

  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$row['liitostunnus']}'";
  $asiakasres = pupe_query($query);
  $asiakasrow = mysql_fetch_assoc($asiakasres);

  //asiakkaan toimitusosoite
  if (empty($asiakasrow['toim_osoite'])) {
    $asiakasrow['toim_nimi']     = $asiakasrow['nimi'];
    $asiakasrow['toim_nimitark'] = $asiakasrow['nimitark'];
    $asiakasrow['toim_osoite']   = $asiakasrow['osoite'];
    $asiakasrow['toim_postino']  = $asiakasrow['postino'];
    $asiakasrow['toim_postitp']  = $asiakasrow['postitp'];
  }

  $ynimi    = t("Yleistiedot");
  $yemail   = $asiakasrow["email"];
  $ygsm     = $asiakasrow["gsm"];
  $ypuh     = $asiakasrow["puhelin"];
  $yfax     = $asiakasrow["fax"];
  $ywww     = "";
  $ytitteli = "";
  $yfakta   = $asiakasrow["fakta"];

  if (!empty($row['henkilo'])) {
    $query = "SELECT *
              FROM yhteyshenkilo
              WHERE yhtio      = '{$kukarow['yhtio']}'
              AND liitostunnus = '{$row['liitostunnus']}'
              AND tyyppi       = 'A'
              AND tunnus       = '{$row['henkilo']}'";
    $yhres = pupe_query($query);
    $yhrow = mysql_fetch_assoc($yhres);

    $yemail   = $yhrow["email"];
    $ynimi    = $yhrow["nimi"];
    $yfax     = $yhrow["fax"];
    $ygsm     = $yhrow["gsm"];
    $ypuh     = $yhrow["puh"];
    $ywww     = $yhrow["www"];
    $ytitteli = $yhrow["titteli"];
    $yfakta   = $yhrow["fakta"];
  }

  ///* Asiakaan tiedot ja yhteyshenkilön tiedot *///
  $body = "<table>";

  $body .= "<tr>";
  $body .= "<th align='left'>".t("Laskutusasiakas").":</th>";
  $body .= "<th align='left'>".t("Toimitusasiakas").":</th>";
  $body .= "<th align='left'>".t("Muut tiedot").":</th>";
  $body .= "</tr>";

  $body .= "<tr>";
  $body .= "<td>{$asiakasrow['nimi']}</td>";
  $body .= "<td>{$asiakasrow['toim_nimi']}</td>";
  $body .= "<td>{$ynimi}</td>";
  $body .= "</tr>";

  $body .= "<tr>";
  $body .= "<td>{$asiakasrow['nimitark']}</td>";
  $body .= "<td>{$asiakasrow['toim_nimitark']}</td>";
  $body .= "<td>".t("Puh").": {$ypuh}</td>";
  $body .= "</tr>";

  $body .= "<tr>";
  $body .= "<td>{$asiakasrow['osoite']}</td>";
  $body .= "<td>{$asiakasrow['toim_osoite']}</td>";
  $body .= "<td>".t("Fax").": {$yfax}</td>";
  $body .= "</tr>";

  $body .= "<tr>";
  $body .= "<td>{$asiakasrow['postino']} {$asiakasrow['postitp']}</td>";
  $body .= "<td>{$asiakasrow['toim_postino']} {$asiakasrow['toim_postitp']}</td>";
  $body .= "<td>".t("Gsm").": {$ygsm}</td>";
  $body .= "</tr>";

  $body .= "<tr>";
  $body .= "<td>{$asiakasrow['fakta']}</td>";
  $body .= "<td></td>";
  $body .= "<td>".t("Email").": {$yemail}";

  if ($yemail != "") {
    $body .= " &nbsp; <a href=\"mailto:{$yemail}\">".t("Email")."</a>";
  }

  $body .= "</td>";
  $body .= "</tr>";

  $body .= "<tr>";
  $body .= "<td colspan='2'></td>";
  $body .= "<td>".t("Tila").": ";

  $asosresult = t_avainsana("ASIAKASTILA", '', "and selite = '{$asiakasrow['tila']}'");
  $asosrow = mysql_fetch_assoc($asosresult);

  $body .= "{$asosrow['selite']} - {$asosrow['selitetark']}</td>";

  if ($yfakta != '' or $ytitteli != '' or $ynimi != '') {
    $body .= "<tr>";
    $body .= "<td colspan='2'>".t("Valittu yhteyshenkilö").": {$ytitteli} {$ynimi}</td>";
    $body .= "<td colspan='2'>{$yfakta}</td>";
    $body .= "</tr>";
  }

  $body .= "</table>";

  $body .= "<br><br>";

  $body .= "<table>";
  $body .= "<tr>";
  $body .= "<th>{$row['tyyppi']}</th>";
  $body .= "<th>{$row['laatija']}</th>";
  $body .= "<th>".tv1dateconv($row["paivamaara"])."</th>";
  $body .= "<th>".t("Tapa").": {$row['tapa']}</th>";
  $body .= "</tr>";

  $body .= "<tr>";
  $body .= "<td colspan='4'>";
  $body .= str_replace("\n", "<br>", trim($row["viesti"]));

  if ($row["laskutunnus"] > 0) {
    $laskutyyppi = $row["laskutila"];
    $alatila     = $row["laskualatila"];

    //tehdään selväkielinen tila/alatila
    require "inc/laskutyyppi.inc";

    $body .= "<br><br>";
    $body .= t("$laskutyyppi")." ".t("$alatila")." / ".tv1dateconv($row["laskumpvm"])." ({$row['laskumyyja']})";
  }

  $body .= "</td>";

  $body .= "</table>";

  // Sähköpostin lähetykseen parametrit
  $params = array(
    "to"      => $kukarow['eposti'],
    "cc"      => "",
    "subject" => t("CRM - Asiakasmemo - Muistutus"),
    "ctype"   => "html",
    "body"    => $body,
  );

  $boob = pupesoft_sahkoposti($params);
}
