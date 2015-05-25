<?php

if (php_sapi_name() != 'cli') {
   die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!\n");
}

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));
// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";
  
$query = "SELECT * FROM yhtio";
$row = pupe_query($query);

echo "\nSuoritetaan tiliˆintien korjaus inventointeihin!\n";

while ($yhtio = mysql_fetch_assoc($row)) {
  
  $yhtiorow = hae_yhtion_parametrit($yhtio['yhtio']);
  
  ///* Luetaan tapahtuma *///
  $query = "  SELECT tapahtuma.*, lasku.tunnus AS laskutunnus 
              FROM tapahtuma 
              LEFT JOIN lasku ON (lasku.yhtio = tapahtuma.yhtio 
                AND tila       = 'X'
                AND alatila    = 'I'
                AND viite      = tapahtuma.tunnus)
              WHERE tapahtuma.yhtio       =  '$yhtiorow[yhtio]'
              AND tapahtuma.laji      = 'Inventointi'
              AND tapahtuma.laadittu  > '2015-05-21 00:00:00'
              AND lasku.tunnus is NULL";
  $tresult = pupe_query($query);

  while ($tapahtumarow = mysql_fetch_assoc($tresult)) {

    $tapahtumaid = $tapahtumarow["tunnus"];
    $selite = $tapahtumarow["selite"];
    $tiliointisumma = round($tapahtumarow["kpl"] * $tapahtumarow["hinta"],2);
    $tapvm = $tapahtumarow["laadittu"];
    $hyllyalue = $tapahtumarow["hyllyalue"];
    $hyllynro = $tapahtumarow["hyllynro"];
    $tuoteno = $tapahtumarow['tuoteno'];

    echo "\nYhtio: {$yhtiorow['yhtio']},tapahtuma: $tapahtumaid, $tapahtumarow[tuoteno], $tapahtumarow[laadittu], $tiliointisumma, $yhtiorow[varastonmuutos], $yhtiorow[varastonmuutos_inventointi]  \n";

    // P‰iv‰m‰‰r‰ll‰ inventoitaessa laitetaan t‰m‰p‰iv‰m‰‰r‰,

    $query = "INSERT INTO lasku SET
              yhtio      = '$yhtiorow[yhtio]',
              tapvm      = '{$tapvm}',
              tila       = 'X',
              alatila    = 'I',
              laatija    = '$tapahtumarow[laatija]',
              viite      = '$tapahtumaid',
              luontiaika = '$tapahtumarow[laadittu]'";
    $result = pupe_query($query);
    $laskuid = mysql_insert_id($GLOBALS["masterlink"]);

    // Seuraako myyntitiliˆinti tuotteen tyyppi‰ ja onko kyseess‰ raaka-aine?
    $raaka_aine_tiliointi = $yhtiorow["raaka_aine_tiliointi"];
    $raaka_ainetililta = ($raaka_aine_tiliointi == "Y" and $row["tuotetyyppi"] == "R");

    // M‰‰ritet‰‰n varastonmuutostili
    if ($raaka_ainetililta) {
      $varastonmuutos_tili = $yhtiorow["raaka_ainevarastonmuutos"];
    }
    elseif ($yhtiorow["varastonmuutos_inventointi"] != "") {
        $varastonmuutos_tili = $yhtiorow["varastonmuutos_inventointi"];
    }
    else {
      $varastonmuutos_tili = $yhtiorow["varastonmuutos"];
    }

    // M‰‰ritet‰‰n varastotili
    if ($raaka_ainetililta) {
      $varastotili = $yhtiorow["raaka_ainevarasto"];
    }
    else {
      $varastotili = $yhtiorow["varasto"];
    }

    $query = "  SELECT * FROM tuote WHERE yhtio = '$yhtiorow[yhtio]' AND tuoteno = '$tuoteno'";
    $tuoteres = pupe_query($query);
    $tuote_row = mysql_fetch_assoc($tuoteres);

    if ($yhtiorow["tarkenteiden_prioriteetti"] == "T") {

      $query = "SELECT toimipaikka
                FROM varastopaikat
                WHERE yhtio = '$yhtiorow[yhtio]'
                AND concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper('$hyllyalue'), 5, '0'),lpad(upper('$hyllynro'), 5, '0'))
                AND concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper('$hyllyalue'), 5, '0'),lpad(upper('$hyllynro'), 5, '0'))";
      $varcheckres = pupe_query($query);
      $varcheckrow = mysql_fetch_assoc($varcheckres);

      unset($toimipaikkarow);

      if ($varcheckrow['toimipaikka'] > 0) {
        $query = "SELECT kustp, kohde, projekti
                  FROM yhtion_toimipaikat
                  WHERE yhtio = '{$yhtiorow['yhtio']}'
                  AND tunnus  = {$varcheckrow['toimipaikka']}";
        $toimipaikkares = pupe_query($query);
        $toimipaikkarow = mysql_fetch_assoc($toimipaikkares);
      }

      // Otetaan ensisijaisesti kustannuspaikka toimipaikan takaa
      $kustp_ins     = (isset($toimipaikkarow) and $toimipaikkarow["kustp"] > 0) ? $toimipaikkarow["kustp"] : $tuote_row["kustp"];
      $kohde_ins     = (isset($toimipaikkarow) and $toimipaikkarow["kohde"] > 0) ? $toimipaikkarow["kohde"] : $tuote_row["kohde"];
      $projekti_ins  = (isset($toimipaikkarow) and $toimipaikkarow["projekti"] > 0) ? $toimipaikkarow["projekti"] : $tuote_row["projekti"];
    }
    else {
      // Otetaan ensisijaisesti kustannuspaikka tuotteen takaa
      $kustp_ins     = $tuote_row["kustp"];
      $kohde_ins     = $tuote_row["kohde"];
      $projekti_ins   = $tuote_row["projekti"];
    }

    // Kokeillaan varastonmuutos tilin oletuskustannuspaikalle
    list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastonmuutos_tili, $kustp_ins, $kohde_ins, $projekti_ins);

    // Toissijaisesti kokeillaan viel‰ varasto-tilin oletuskustannuspaikkaa
    list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varasto"], $kustp_ins, $kohde_ins, $projekti_ins);

    //$tiliointisumma = round($summa, 2);

    $query = "INSERT INTO tiliointi SET
              yhtio    = '$yhtiorow[yhtio]',
              ltunnus  = '$laskuid',
              tilino   = '{$varastotili}',
              kustp    = '{$kustp_ins}',
              kohde    = '{$kohde_ins}',
              projekti = '{$projekti_ins}',
              tapvm    = '{$tapvm}',
              summa    = $tiliointisumma,
              vero     = 0,
              lukko    = '',
              selite   = 'Inventointi: ".t("Tuotteen")." {$tapahtumarow["tuoteno"]} $selite',
              laatija  = '$tapahtumarow[laatija]',
              laadittu = '$tapahtumarow[laadittu]'";
    $result = pupe_query($query);

    $query = "INSERT INTO tiliointi SET
              yhtio    = '$yhtiorow[yhtio]',
              ltunnus  = '$laskuid',
              tilino   = '$varastonmuutos_tili',
              kustp    = '{$kustp_ins}',
              kohde    = '{$kohde_ins}',
              projekti = '{$projekti_ins}',
              tapvm    = '{$tapvm}',
              summa    = $tiliointisumma * -1,
              vero     = 0,
              lukko    = '',
              selite   = 'Inventointi: ".t("Tuotteen")." {$tapahtumarow["tuoteno"]} $selite',
              laatija  = '$tapahtumarow[laatija]',
              laadittu = '$tapahtumarow[laadittu]'";
    $result = pupe_query($query);
  }
}
