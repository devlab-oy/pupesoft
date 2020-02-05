<?php

// Tehdään SSH-tunneli Örskan palvelimelle
// ssh -f -L 4444:193.185.248.70:4444 -N devlab@193.185.248.70
echo "\nalotellaan..\n";
require_once 'PJBS.php';

$pupe_root_polku = dirname(dirname(__FILE__));
//  for debug
//  $pupe_root_polku = "/Users/joonas/Dropbox/Sites/pupesoft";
//  $pupe_root_polku = "/Users/satu/Sites/pupesoft";
//  $pupe_root_polku = "/Users/devlab/Sites/pupesoft";
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
error_reporting(0);
ini_set("display_errors", 0);

if (php_sapi_name() == 'cli') {
  // Pupetti includet
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $php_cli = true;
}
echo "\nconnect..\n";
$drv = new PJBS('UTF-8', 'UTF-8');
//$con = $drv->connect('jdbc:solid://mergs014:2000/pupesoft/pupesoft', 'pupesoft', 'pupesoft');
$con = $drv->connect('jdbc:solid://palvelin26:2000/pupesoft/mG1289R!', 'pupesoft', 'mG1289R!');

if ($con === false) {
  // jdbc ei nappaa
  echo "jdbc ei nappaa\n";
}

ini_set("memory_limit", "4G");
$yhtio = 'atarv';
$debug = 0;
$laatija = 'alakanta26';
$luontiaika = date("Y-m-d");
$poikkeusasiakasryhmat = array("TOTAL1", "TOTAL2", "TOTAL3", "TOTAL4");
$ASR_ASIAKRYHMKOODI = array();
echo "\nfuturmuunnos..\n";
if (!function_exists("futurmuunnos")) {
  function futurmuunnos($toimittaja, $jotain) {
    $valmis = $toimittaja != '3' ? $toimittaja.'-'.$jotain : $jotain;
    return $valmis;
  }


}

if (!function_exists("futurmuunnos_tuote")) {
  function futurmuunnos_tuote($toimittaja, $jotain) {
    $valmis = $toimittaja != '3' ? $jotain.'-'.$toimittaja : $jotain;
    return $valmis;
  }


}
/*
echo "\nasiakryhmät..\n";
$res = $drv->exec("SELECT * FROM ASIAKRYH "); //asiakasryhmät
while ($row = $drv->fetch_array($res)) {

  $row['ARY_KOODI'] = trim($row['ARY_KOODI']);
  $row['ARY_NIMI'] = pupesoft_cleanstring($row['ARY_NIMI']);

  // tehää vaa alennuksista asiakasryhmä
  if (strstr($row['ARY_TYYPPI'], 'ALENNUS') != "" and $row['ARY_KOODI'] != "") {
    $query = "SELECT *
              FROM avainsana use index (yhtio_laji_selite)
              WHERE yhtio = '$yhtio'
              AND laji    = 'asiakasryhma'
              AND selite  = '$row[ARY_KOODI]'
              AND kieli   = 'fi'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      $query = "INSERT INTO avainsana SET
                kieli      = 'fi',
                jarjestys  = '0',
                selite     = '$row[ARY_KOODI]',
                selitetark = '$row[ARY_NIMI]',
                laji       = 'ASIAKASRYHMA',
                yhtio      = '$yhtio',
                laatija    = '$laatija',
                luontiaika = '$luontiaika',
                muuttaja   = '$laatija',
                muutospvm  = '$luontiaika'";
      pupe_query($query);
    }
    elseif (mysql_num_rows($result) == 1) {
      $query = "UPDATE avainsana SET
                laji        = 'ASIAKASRYHMA',
                selitetark= '$row[ARY_NIMI]',
                muuttaja    = '$laatija',
                muutospvm   = '$luontiaika'
                WHERE yhtio = '$yhtio'
                AND laji    = 'ASIAKASRYHMA'
                AND selite  = '$row[ARY_KOODI]'
                AND kieli   = 'fi'";
      pupe_query($query);
    }
  }
}
echo "\nliitokset asiakkaisiin ja ryhmiin..\n";
$res = $drv->exec("SELECT * FROM ASIAKKAANRYHM WHERE ASR_ASIAKRYHMKOODI in ('TOTAL1','TOTAL2','TOTAL3','TOTAL4')"); //liitos asiakas/asiakasryhmä
while ($row = $drv->fetch_array($res)) {
/*
  $query = "SELECT *
            FROM avainsana use index (yhtio_laji_selite)
            WHERE yhtio = '$yhtio'
            AND laji    = 'asiakasryhma'
            AND selite  = '$row[ASR_ASIAKRYHMKOODI]'";
  $result = pupe_query($query);
  $query = "SELECT asiakasnro
            FROM asiakas
            WHERE yhtio    = '$yhtio'
            AND asiakasnro = '$row[ASR_ASIAKASNRO]'";
  $result2 = pupe_query($query);

  // jos asiakas ja ryhmä löytyy, päivitetään asiakkaalle ryhmä. HUOM total1-3 spessukaseja, ei tehdä niille mitään täs kohtaa!
  if (mysql_num_rows($result) == 1 and mysql_num_rows($result2) == 1 and !in_array($row['ASR_ASIAKRYHMKOODI'], $poikkeusasiakasryhmat)) {
    $query = "UPDATE asiakas SET
              ryhma          = '$row[ASR_ASIAKRYHMKOODI]'
              WHERE yhtio    = '$yhtio'
              AND asiakasnro = '$row[ASR_ASIAKASNRO]'";
    pupe_query($query);
  }

  if (mysql_num_rows($result) == 0) {
    echo "\nAsiakasryhmää $row[ASR_ASIAKRYHMKOODI] ei löydy!";
  }

  // rakennetaan tässä kohtaa vielä apu-array, että voidaan alennuksissa käyttää sitä hyväksi. vain poikkeusryhmille!
  if (in_array($row['ASR_ASIAKRYHMKOODI'], $poikkeusasiakasryhmat)) {
    echo "\n$row[ASR_ASIAKRYHMKOODI] - $row[ASR_ASIAKASNRO] jätettiin käsittelemättä, mutta laitetaan talteen taulukkoon!";
    $ASR_ASIAKRYHMKOODI[$row['ASR_ASIAKRYHMKOODI']][] = $row;
  }
}
*/
//echo "<pre>".var_dump($ASR_ASIAKRYHMKOODI)."</pre>";
//die;

//#####################################################
//# ALENNUKSET TÄÄLLÄ! eka try ym jutut muuttujiin, joita voidaan myöhemmin käyttää pääryhmän käännössä aleryhmiksi
//#####################################################

echo"\nlaitetaan tuoteryhmä arraynä muuttujaan, jotta voidaan sitä sieltä käytttää";
$res = $drv->exec("SELECT * FROM TUOTRYHM WHERE TUR_TOIMITTAJANRO != 2"); // tuoteryhmän tietoja
while ($row = $drv->fetch_array($res)) {

  if (strstr($row['TUR_TYYPPI'], 'ALENNUS') != "") {
    $tur[$row['TUR_OSASTO_YLEINEN']][] = $row;
  }
}

echo "\nperusaleja koivuselta..";
$res = $drv->exec("SELECT * FROM TUOTRYHMPR WHERE TUP_TOIMITTAJANRO = 2"); //try / pääryhmä liitokset, pääryhmä on ASIAKALE.ASA_PAARYHM. try = tuote.try Pupetissa (?)
while ($row = $drv->fetch_array($res)) {
  $aleryhma = $row['TUP_SEURANTARYHMA']."#".$row['TUP_ALERYHMA'];
  $tup[$row['TUP_PAARYHMA']][$aleryhma] = $aleryhma;

  // lisäksi tehdään täs samal näistä perusalennus!
  $query = "SELECT *
            FROM perusalennus use index (yhtio_ryhma)
            WHERE yhtio = '$yhtio'
            AND ryhma   = '$aleryhma'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    $query = "INSERT INTO perusalennus SET
              yhtio      = '$yhtio',
              ryhma      = '$aleryhma',
              selite     = '',
              alennus    = '0',
              laatija    = '$laatija',
              luontiaika = '$luontiaika',
              muuttaja   = '$laatija',
              muutospvm  = '$luontiaika'";
    pupe_query($query);
  }
}

echo "\nitse alennukset..\n";
if (!function_exists("tee_aletjahinnat")) {
  function tee_aletjahinnat($row, $taa_on_alennus, $saapaivittaa, $asiakasliitos_select, $tuoteliitos_select, $asiakasliitos, $tuoteliitos) {
    global $yhtio, $laatija, $luontiaika;

    $loppupv = $row['ASA_LOPPUPVM'];

    if ($loppupv == '1899-12-30') {
      $loppupv='';
    }
    if ($taa_on_alennus == 1 and ($loppupv == '' or $loppupv > '2013-12-16')) {
      // echo "\n $loppupv, $asiakasliitos, $tuoteliitos \n";
      $alequery = "SELECT *
                   FROM asiakasalennus
                   WHERE yhtio     = '$yhtio'
                   AND alennuslaji = '1'
                   AND alkupvm     = '0000-00-00'
                   AND loppupvm    in ('$loppupv', '0000-00-00')
                   AND minkpl      = '0'
                   $asiakasliitos_select
                   $tuoteliitos_select";
      $alequeryresult = pupe_query($alequery);
      //echo "\n$alequery\n";
      if (mysql_num_rows($alequeryresult) == 0) {

        $insert = "INSERT INTO asiakasalennus SET
                   $asiakasliitos
                   $tuoteliitos
                   alkupvm     = '',
                   loppupvm    = '$loppupv',
                   alennuslaji = '1',
                   alennus     = '$row[ASA_ALEPROS]',
                   yhtio       = '$yhtio',
                   laatija     = '$laatija',
                   luontiaika  = '$luontiaika',
                   muutospvm   = '$luontiaika',
                   muuttaja    = '$laatija'";
        pupe_query($insert);
      }
      elseif ($saapaivittaa == 1) {
        $alequeryresult_row =  mysql_fetch_assoc($alequeryresult);

        // jos tuleva alennus on isompi kuin löytynyt alennus, päivitetään, muuten ei
        if ($alequeryresult_row['alennus'] < $row['ASA_ALEPROS']) {
          $insert = "UPDATE asiakasalennus SET
                     alennus     = '$row[ASA_ALEPROS]',
                     alkupvm     = '',
                     loppupvm    = '$loppupv',
                     muutospvm   = '$luontiaika',
                     muuttaja    = '$laatija'
                     WHERE yhtio = '$yhtio'
                     AND tunnus  = '$alequeryresult_row[tunnus]'";
          pupe_query($insert);
        }
      }
    }
    elseif ($taa_on_alennus == 0 and ($loppupv == '' or $loppupv > '2013-12-16')) {
      // echo "\n $loppupv, $asiakasliitos, $tuoteliitos \n";
      // Hinnat verottomiksi
      $row['ASA_KMYYNTIHINTA'] = $row['ASA_KMYYNTIHINTA'] / 1.24;

      $asiakashintatest_query = "SELECT *
                                 FROM asiakashinta
                                 WHERE yhtio  = '$yhtio'
                                 $asiakasliitos_select
                                 $tuoteliitos_select
                                 AND minkpl   = '0'
                                 AND maxkpl   = '0'
                                 AND alkupvm  = '0000-00-00'
                                 AND loppupvm in ('$loppupv', '0000-00-00')";
      $asiakashintatest_result = pupe_query($asiakashintatest_query);
      //echo "\n$asiakashintatest_query\n";
      if (mysql_num_rows($asiakashintatest_result) == 0) {
        $sopimushinta_insert = "INSERT INTO asiakashinta SET
                                $asiakasliitos
                                       $tuoteliitos
                                alkupvm    = '',
                                loppupvm   = '$loppupv',
                                valkoodi   = 'EUR',
                                hinta      = '$row[ASA_KMYYNTIHINTA]',
                                yhtio      = '$yhtio',
                                laatija    = '$laatija',
                                luontiaika = '$luontiaika',
                                muutospvm  = '$luontiaika',
                                muuttaja   = '$laatija'";
        pupe_query($sopimushinta_insert);
      }
      elseif ($saapaivittaa == 1) {
        $asiakashintatest_result_row =  mysql_fetch_assoc($asiakashintatest_result);
        $insert = "UPDATE asiakashinta SET
                   valkoodi    = 'EUR',
                   hinta       = '$row[ASA_KMYYNTIHINTA]',
                   alkupvm     = '',
                   loppupvm    = '$loppupv',
                   muutospvm   = '$luontiaika',
                   muuttaja    = '$laatija'
                   WHERE yhtio = '$yhtio'
                   AND tunnus  = '$asiakashintatest_result_row[tunnus]'";
        pupe_query($insert);
      }
    }
  }


}

$res = $drv->exec("SELECT * FROM ASIAKALE WHERE ASA_ALENNUSNRO>2600000 and ASA_ASIAKRYHMKOODI=''");

$i = 1;
$testi = array();

while ($row = $drv->fetch_array($res)) {

  //if (!in_array($row['ASA_ASIAKRYHMKOODI'], $poikkeusasiakasryhmat)) continue;

  // pitäisi tietää onko kyse alennuksesta vai nettohinnasta
  $taa_on_alennus = 0;
  $ok = 0;
  $ok2 = 0;
  $saapaivittaa = 0;
  $asiakasliitos_select = "";
  $asiakasliitos = "";
  $tuoteliitos_select = "";
  $tuoteliitos = "";

  if ($row['ASA_ALEPROS'] > 0) {
    $taa_on_alennus = 1;
  }

  if ($row['ASA_ASIAKASNRO'] != '' and $row['ASA_ASIAKASNRO'] != '-1') {
    $asiakas_query = "SELECT *
                      FROM asiakas use index (asno_index)
                      WHERE yhtio     = '$yhtio'
                      AND asiakasnro  = '$row[ASA_ASIAKASNRO]'
                      AND laji       != 'P'";
    $asiakas_result = pupe_query($asiakas_query);

    if (mysql_num_rows($asiakas_result) == 1) {
      $asiakas_row = mysql_fetch_assoc($asiakas_result);

      $asiakasliitos_select = "AND asiakas = '$asiakas_row[tunnus]'";
      $asiakasliitos = "asiakas = '$asiakas_row[tunnus]',";
      $ok = 1;
    }
    else {
      //echo "\nLöytyi ".mysql_num_rows($asiakas_result) ." asiakasta asiakasnumerolla $row[ASA_ASIAKASNRO], ei tehdä mitään";
    }
  }
  /*
  elseif ($row['ASA_ASIAKRYHMKOODI'] != '') {

    // jos asiakasryhmä on TOTAL1, TOTAL2, TOTAL3, TOTAL4 ja petikko80k (jos asiakasnro on 70606) -> muutetaan asiakasalennuksiksi.


    $asiakasliitos_select = "AND asiakas_ryhma = '$row[ASA_ASIAKRYHMKOODI]'";
    $asiakasliitos = "asiakas_ryhma = '$row[ASA_ASIAKRYHMKOODI]',";
    $ok = 1;
  }
  */
  if ($row['ASA_TUOTEKOODI'] != '') {
    $tuoteno = pupesoft_cleanstring($row['ASA_TUOTEKOODI']);
    $tuoteno = futurmuunnos_tuote($row['ASA_TOIMITTAJANRO'], $tuoteno);
    $tuoteliitos_select = "AND tuoteno = '$tuoteno'";
    $tuoteliitos = "tuoteno = '$tuoteno',";
    $ok2 = 1;
  }
  // tuoteryhmistä ja pääryhmistä tehdään aleryhmät, joten nää menee molemmat samaan putkeen (eivät voi olla samaa aikaa samal rivil)
  elseif ($row['ASA_TUOTERYHMKOODI'] != '') {
    $row['ASA_TUOTERYHMKOODI'] = futurmuunnos($row['ASA_TOIMITTAJANRO'], $row['ASA_TUOTERYHMKOODI']);
    $tuoteliitos_select = "AND ryhma = '$row[ASA_TUOTERYHMKOODI]'";
    $tuoteliitos = "ryhma = '$row[ASA_TUOTERYHMKOODI]',";
    $ok2 = 1;
    $saapaivittaa = 1;
    /*
    if ($row['ASA_TUOTERYHMKOODI'] == '033#02') {
      echo "\nRyhmäkoodi 033#02! ja asiakasliitos = $asiakasliitos ja alennus/hinta $row[ASA_ALEPROS] / $row[ASA_KMYYNTIHINTA]";
    }
    */
  }
  elseif ($row['ASA_PAARYHMA'] != '') {
    // tee_aletjahinnat tarkistaa onko tuleva alennus isompi kuin mahdollisesti löytyvä, niin annetaan tässäkin päivittää
    $saapaivittaa = 1;

    // Joo eli, $tup muuttujassa on koivusen jutut ja $tur muuttujassa on kaikki muut toimittajat
    if (isset($tup[$row['ASA_PAARYHMA']])) {
      foreach ($tup[$row['ASA_PAARYHMA']] as $tup_row) {
        $tuoteliitos_select = "AND ryhma = '$tup_row'";
        $tuoteliitos = "ryhma = '$tup_row',";
        /*
        if ($row['ASA_PAARYHMA'] == '133') {
          echo "\nPääryhmä 133! ja ryhmäksi tuli $tup_row ja asiakasliitos = $asiakasliitos ja alennus/hinta $row[ASA_ALEPROS] / $row[ASA_KMYYNTIHINTA] (Koivunen)";
        }
*/
        if ($ok == 1) {
          // jos asiakkaan asiakasryhmä on poikkeusryhmien joukossa, niin pitää kääntää ne asiakasliitoksiksi, koska asiakkaat ei ole liitetty noihin ryhmiin asiakasylläpidossa
          if (in_array($row['ASA_ASIAKRYHMKOODI'], $poikkeusasiakasryhmat)) {
            if (!isset($ASR_ASIAKRYHMKOODI[$row['ASA_ASIAKRYHMKOODI']])) {
              echo "\n".$row['ASA_ASIAKRYHMKOODI']." ei löyry keltään asiakkaalta, vaikka on määritelty poikkeusryhmäksi, skipataan! (ok1 + tup)";
              continue;
            }
            foreach ($ASR_ASIAKRYHMKOODI[$row['ASA_ASIAKRYHMKOODI']] as $row_asry) {

              $asiakas_query = "SELECT tunnus
                                FROM asiakas
                                WHERE yhtio    = '$yhtio'
                                AND asiakasnro = '$row_asry[ASR_ASIAKASNRO]'";
              $asiakas_result = pupe_query($asiakas_query);
              if (mysql_num_rows($asiakas_result) == 1) {
                $asiakas_row = mysql_fetch_assoc($asiakas_result);

                $asiakasliitos_select = "AND asiakas = '$asiakas_row[tunnus]'";
                $asiakasliitos = "asiakas = '$asiakas_row[tunnus]',";
                tee_aletjahinnat($row, $taa_on_alennus, $saapaivittaa, $asiakasliitos_select, $tuoteliitos_select, $asiakasliitos, $tuoteliitos);
              }
              else {
                echo "\nok1=1 tup: Löytyi ".mysql_num_rows($asiakas_result) ." asiakasta asiakasnumerolla $row[ASA_ASIAKASNRO], ei tehdä mitään (poikkeusryhmä $row[ASA_ASIAKRYHMKOODI])";
              }
            }
          }
          else {
            tee_aletjahinnat($row, $taa_on_alennus, $saapaivittaa, $asiakasliitos_select, $tuoteliitos_select, $asiakasliitos, $tuoteliitos);
          }
        }
      }
    }

    if (isset($tur[$row['ASA_PAARYHMA']])) {
      foreach ($tur[$row['ASA_PAARYHMA']] as $tur_row) {
        $aleryhma = futurmuunnos($tur_row['TUR_TOIMITTAJANRO'], $tur_row['TUR_KOODI']);
        $tuoteliitos_select = "AND ryhma = '$aleryhma'";
        $tuoteliitos = "ryhma = '$aleryhma',";

        if ($row['ASA_PAARYHMA'] == '133') {
          echo "\nPääryhmä 133! ja ryhmäksi tuli $tup_row ja asiakasliitos = $asiakasliitos ja alennus/hinta $row[ASA_ALEPROS] / $row[ASA_KMYYNTIHINTA] (ei Koivunen)";
        }

        if ($ok == 1) {
          // jos asiakkaan asiakasryhmä on poikkeusryhmien joukossa, niin pitää kääntää ne asiakasliitoksiksi, koska asiakkaat ei ole liitetty noihin ryhmiin asiakasylläpidossa
          if (in_array($row['ASA_ASIAKRYHMKOODI'], $poikkeusasiakasryhmat)) {
            if (!isset($ASR_ASIAKRYHMKOODI[$row['ASA_ASIAKRYHMKOODI']])) {
              echo "\n".$row['ASA_ASIAKRYHMKOODI']." ei löyry keltään asiakkaalta, vaikka on määritelty poikkeusryhmäksi, skipataan! (ok1 tur)";
              continue;
            }
            foreach ($ASR_ASIAKRYHMKOODI[$row['ASA_ASIAKRYHMKOODI']] as $row_asry) {

              $asiakas_query = "SELECT tunnus
                                FROM asiakas
                                WHERE yhtio    = '$yhtio'
                                AND asiakasnro = '$row_asry[ASR_ASIAKASNRO]'";
              $asiakas_result = pupe_query($asiakas_query);
              if (mysql_num_rows($asiakas_result) == 1) {
                $asiakas_row = mysql_fetch_assoc($asiakas_result);

                $asiakasliitos_select = "AND asiakas = '$asiakas_row[tunnus]'";
                $asiakasliitos = "asiakas = '$asiakas_row[tunnus]',";
                tee_aletjahinnat($row, $taa_on_alennus, $saapaivittaa, $asiakasliitos_select, $tuoteliitos_select, $asiakasliitos, $tuoteliitos);
              }
              else {
                echo "\nok1=1 tur: Löytyi ".mysql_num_rows($asiakas_result) ." asiakasta asiakasnumerolla $row[ASA_ASIAKASNRO], ei tehdä mitään (poikkeusryhmä $row[ASA_ASIAKRYHMKOODI])";
              }
            }
          }
          else {
            tee_aletjahinnat($row, $taa_on_alennus, $saapaivittaa, $asiakasliitos_select, $tuoteliitos_select, $asiakasliitos, $tuoteliitos);
          }
        }
      }
    }
  }

  // tänne menee kaikki muut paitsi pääryhmän kautta tehdyt alet ($ok2)
  if ($ok == 1 and $ok2 == 1) {
    // jos asiakkaan asiakasryhmä on poikkeusryhmien joukossa, niin pitää kääntää ne asiakasliitoksiksi, koska asiakkaat ei ole liitetty noihin ryhmiin asiakasylläpidossa
    if (in_array($row['ASA_ASIAKRYHMKOODI'], $poikkeusasiakasryhmat)) {
      if (!isset($ASR_ASIAKRYHMKOODI[$row['ASA_ASIAKRYHMKOODI']])) {
        echo "\n".$row['ASA_ASIAKRYHMKOODI']." ei löyry keltään asiakkaalta, vaikka on määritelty poikkeusryhmäksi, skipataan! (ok1 + ok2)";
        continue;
      }
      foreach ($ASR_ASIAKRYHMKOODI[$row['ASA_ASIAKRYHMKOODI']] as $row_asry) {

        $asiakas_query = "SELECT tunnus
                          FROM asiakas
                          WHERE yhtio    = '$yhtio'
                          AND asiakasnro = '$row_asry[ASR_ASIAKASNRO]'";
        $asiakas_result = pupe_query($asiakas_query);
        if (mysql_num_rows($asiakas_result) == 1) {
          $asiakas_row = mysql_fetch_assoc($asiakas_result);

          $asiakasliitos_select = "AND asiakas = '$asiakas_row[tunnus]'";
          $asiakasliitos = "asiakas = '$asiakas_row[tunnus]',";
          tee_aletjahinnat($row, $taa_on_alennus, $saapaivittaa, $asiakasliitos_select, $tuoteliitos_select, $asiakasliitos, $tuoteliitos);
        }
        else {
          echo "\nok=1 ok2=1: Löytyi ".mysql_num_rows($asiakas_result) ." asiakasta asiakasnumerolla $row[ASA_ASIAKASNRO], ei tehdä mitään (poikkeusryhmä $row[ASA_ASIAKRYHMKOODI])";
        }
      }
    }
    else {
      tee_aletjahinnat($row, $taa_on_alennus, $saapaivittaa, $asiakasliitos_select, $tuoteliitos_select, $asiakasliitos, $tuoteliitos);
    }
  }
}
$drv->free_result($res);
echo "\n";
