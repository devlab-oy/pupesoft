<?php

// Kutsutaanko CLI:stä
$php_cli = (php_sapi_name() == 'cli') ? true : false;

if ($php_cli) {
  // Pupesoft root include_pathiin
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));

  // Otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  if (!isset($argv[1])) {
    echo "Anna yhtio!\n";
    die;
  }

  if (!isset($argv[2])) {
    echo "Anna tiedostonimi\n";
    die;
  }

  // Tehdään parametrit
  $tee = "file";

  // Haetaan yhtiörow ja kukarow
  $yhtio    = pupesoft_cleanstring($argv[1]);
  $yhtiorow = hae_yhtion_parametrit($yhtio);
  $kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);
}
else {
  require "inc/parametrit.inc";
}

// Tämä vaatii paljon muistia
error_reporting(E_ALL);
ini_set("memory_limit", "5G");
ini_set("display_errors", 1);
unset($pupe_query_debug);

function is_log($str) {
  global $php_cli;

  if ($php_cli) {
    echo date("d.m.Y @ G:i:s") . ": {$str}\n";
  }
  else {
    echo "<font class='message'>{$str}</font><br>";
  }
}

function kasittele_tuote_tiedosto($file_name, $real_name = '') {
  global $kukarow, $yhtiorow, $suuraakkosiin;

  $path_parts = ($real_name == '') ? pathinfo($file_name) : pathinfo($real_name);
  $name = strtoupper($path_parts['filename']);
  $ext  = strtoupper($path_parts['extension']);

  if ($ext != "TXT" and $ext != "CSV") {
    die ("<font class='error'><br>".t("Ainoastaan .txt ja .csv tiedostot sallittuja")."!</font>");
  }

  $file  = fopen($file_name , "r") or die (t("Tiedoston avaus epäonnistui")."!");
  $error = 0;
  $count = 0;

  while ($rivi = fgets($file)) {

    // luetaan rivi tiedostosta..
    $rivi = explode("\t", pupesoft_cleanstring($rivi));
    $count++;

    $vantuoteno = trim($rivi[0]);
    $uustuoteno = strtoupper(trim($rivi[1]));

    if ($vantuoteno != '' and $uustuoteno != '') {
      // Etsitään vanha
      $query = "SELECT tunnus
                FROM tuote
                WHERE yhtio = '$kukarow[yhtio]'
                AND tuoteno = '$vantuoteno'";
      $tuoteresult = pupe_query($query);

      if (mysql_num_rows($tuoteresult) == 0) {
        $error++;
        echo "<font class='message'>".t("VANHAA TUOTENUMEROA EI LÖYDY").": $vantuoteno</font><br>";
      }

      // Etsitään uusi
      $query  = "SELECT tunnus
                 FROM tuote
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND tuoteno = '$uustuoteno'";
      $tuoteuresult = pupe_query($query);

      if (mysql_num_rows($tuoteuresult) == 1) {

        // uusi tuoteno löytyy jo. tarkistetaan onko sama kuin vanha...
        if (strtoupper($vantuoteno) == $uustuoteno) {
          // uusi ja vanha tuoteno ovat samat. katsotaan onko vanha jo kokonaan uppercase...
          if (ctype_upper($vantuoteno)) {
            $error++;
            echo "<font class='message'>";
            echo t("Vanha ja uusi tuotenumero ovat identtiset");
            echo ": $uustuoteno = $vantuoteno</font><br>";
          }
          else {
            // sallitaan muutos suuraakkosiin
            $suuraakkosiin[] = strtoupper($vantuoteno."!¡!".$uustuoteno);
            echo "<font class='message'>";
            echo t("Tuotenumeron aakkoslaji vaihdetaan suuraakkosiin");
            echo ": $vantuoteno --> $uustuoteno</font><br>";
          }
        }
        else {
          $error++;
          echo "<font class='message'>".t("UUSI TUOTENUMERO LÖYTYY JO").": $uustuoteno</font><br>";
        }
      }
    }
    elseif ($vantuoteno == '' and $uustuoteno != '') {
      $error++;
      echo "<font class='message'>".t("Vanha tuotenumero puuttuu tiedostosta").": (tyhjä) --> $uustuoteno</font><br>";
    }
    elseif ($uustuoteno == '' and $vantuoteno != '') {
      $error++;
      echo "<font class='message'>".t("Uusi tuotenumero puuttuu tiedostosta").": $vantuoteno --> (tyhjä)</font><br>";
    }
  }

  fclose($file);

  if ($count == 0) {
    die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
  }

  return $error;
}

if ($php_cli) {
  echo "\n";
  is_log("Tuotenumeroiden vaihto");
}
else {
  echo "<font class='head'>".t("Tuotenumeroiden vaihto")."</font><hr>";
  flush();
}

$vikaa          = 0;
$tarkea         = 0;
$kielletty      = 0;
$lask           = 0;
$postoiminto    = "X";
$tyhjatok       = "";
$chekatut       = 0;
$taulut         = "";
$error          = 0;
$failista       = "";
$uusi_on_jo     = "";
$vanmyyntihinta = "";
$vankehahin     = "";
$vanvihahin     = "";
$vanvihapvm     = "";
$vanyksikko     = "";
$tee            = (isset($tee)) ? $tee : "";
$jatavanha      = (isset($jatavanha)) ? $jatavanha : "";
$postit         = (isset($postit)) ? $postit : array();
$suuraakkosiin  = array();
$muutataulut    = array(
  "tuotteen_alv"          => "tuotteen_alv",
  "yhteensopivuus_tuote"  => "yhteensopivuus_tuote",
);

if (!isset($muistutus)) $muistutus = "";

if ($php_cli) {
  $uploaded_filename = $argv[2];
  $error = kasittele_tuote_tiedosto($uploaded_filename);
  $failista = "JOO";
}
elseif (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE and $tee == "file") {
  echo "<font class='message'>".t("Tutkaillaan mitä olet lähettänyt").".<br></font>";
  flush();

  $uploaded_filename = $_FILES['userfile']['tmp_name'];
  $error = kasittele_tuote_tiedosto($uploaded_filename, $_FILES['userfile']['name']);
  $failista = "JOO";
}
elseif (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) !== TRUE and $tee == "file") {

  $vantuoteno = strtoupper(trim($vantuoteno));
  $uustuoteno = strtoupper(trim($uustuoteno));

  //Tuotenumerot tulevat käyttöliittymästä
  $query1  = "SELECT tunnus, kehahin, tuoteno from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$vantuoteno'";
  $tuoteresult = pupe_query($query1);

  if (mysql_num_rows($tuoteresult) != 0) {
    $vantuoterow = mysql_fetch_array($tuoteresult);

    $query2  = "SELECT tunnus, kehahin, tuoteno from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
    $tuoteuresult = pupe_query($query2);

    if (mysql_num_rows($tuoteuresult) != 0) {
      $uustuoterow = mysql_fetch_array($tuoteuresult);

      echo "<font class='message'>".t("UUSI TUOTENUMERO LÖYTYY JO").": $uustuoteno</font><br>";

      if (strtoupper(trim($vantuoterow["tuoteno"])) == strtoupper(trim($uustuoterow["tuoteno"]))) {
        //sallitaan myös se, että uusi tuote ja vanhatuote ovat samat (silloin vain strtoupperataan tuoteno)
        $uusi_on_jo = "SAMA";
      }
      else {
        //Jos molempien tuotteiden varastonarvo on nolla niin ei haittaa vaikka uusi tuote löytyy jo
        if ($uustuoterow["kehahin"] == 0 and $vantuoterow["kehahin"] == 0) {
          $uusi_on_jo = "OK";
        }
        else {
          $error++;
        }
      }
    }
  }
  else {
    $error++;
    echo "<font class='message'>".t("VANHAA TUOTENUMEROA EI LÖYDY").": $vantuoteno</font><br>";
  }

  $failista = "EI";
}

if ($error == 0 and $tee == "file") {

  is_log(t("Syötetyt tiedot ovat ok"));
  flush();

  is_log(t("Aloitellaan päivitys, tämä voi kestää hetken"));
  flush();

  $tulos = array();

  $locktables = array();
  $locktables['tuote_muutokset'] = "tuote_muutokset";
  $locktables['sanakirja'] = "sanakirja";
  $locktables['avainsana'] = "avainsana";

  //$dbkanta --> tulee salasanat.php:stä
  $query  = "SHOW TABLES FROM $dbkanta";
  $tabresult = pupe_query($query);

  while ($tables = mysql_fetch_array($tabresult)) {
    $query  = "describe $tables[0]";
    $fieldresult = pupe_query($query);

    while ($fields = mysql_fetch_array($fieldresult)) {
      if ((strpos($fields[0], "tuotenumero") !== false or strpos($fields[0], "tuoteno") !== false) and $tables[0] != 'tuote_muutokset' and $fields[0] != 'toim_tuoteno') {
        $locktables[$tables[0]] = $tables[0];
        $tulos[] = $tables[0]."##".$fields[0];
      }
    }
    // puun_alkioissa on liitos-kentässä tuotenumeroita, huomioidaan nekin
    if ($tables[0] == 'puun_alkio') {
      $locktables[$tables[0]] = $tables[0];
      $tulos[] = $tables[0]."##liitos";
    }
  }

  foreach ($locktables as $ltable) {
    $taulut .= $ltable.' WRITE,';
  }

  $taulut = substr($taulut, 0, -1);

  $montako = count($tulos);

  if ($montako > 0) {
    is_log(t("Löydettiin paikat joita pitää muuttaa").": $montako kappaletta.");
    flush();
  }
  else {
    die ("<font class='error'><br>".t("Ei löydetty muutettavia paikkoja, ei uskalleta tehdä mitään")."!</font>");
  }

  // Haetaan tilausrivien kaikki tyypit
  $query  = "SELECT group_concat(distinct concat('\'',tyyppi,'\'')) tyyppi
             from tilausrivi
             where yhtio = '$kukarow[yhtio]'";
  $tyypitresult = pupe_query($query);
  $tyypitrow = mysql_fetch_assoc($tyypitresult);

  $tyyppi = $tyypitrow["tyyppi"];

  // Haetaan tuoteperheiden kaikki tyypit
  $query  = "SELECT group_concat(distinct concat('\'',tyyppi,'\'')) tyyppi
             from tuoteperhe
             where yhtio = '$kukarow[yhtio]'";
  $tyypitresult = pupe_query($query);
  $tyypitrow = mysql_fetch_assoc($tyypitresult);

  $perhetyyppi = $tyypitrow["tyyppi"];

  // Haetaan tuotteen_avainsanat-taulus kaikki kielet
  $query  = "SELECT group_concat(distinct concat('\'',kieli,'\'')) kieli
             from tuotteen_avainsanat
             where yhtio = '$kukarow[yhtio]'";
  $tyypitresult = pupe_query($query);
  $tyypitrow = mysql_fetch_assoc($tyypitresult);

  $kielet = $tyypitrow["kieli"];

  if ($tyyppi == "") $tyyppi = "''";
  if ($perhetyyppi == "") $perhetyyppi = "''";
  if ($kielet == "") $kielet = "''";

  is_log(t("Nyt ollan kerätty tietokannasta kaikki tarpeellinen")."<br>".t("Aloitellaan muutos")."...");
  flush();

  if ($failista == "JOO") {
    $file = fopen($uploaded_filename, "r") or die (t("Tiedoston avaus epäonnistui")."!");
  }
  else {
    $tmpfname = tempnam("/tmp", "Vaihdatuoteno");
    file_put_contents($tmpfname, "$vantuoteno\t$uustuoteno");
    $file = fopen($tmpfname, "r") or die (t("Tiedoston avaus epäonnistui")."!");
  }

  while ($rivi = fgets($file)) {
    // luetaan rivi tiedostosta..
    $rivi = explode("\t", pupesoft_cleanstring($rivi));

    if (trim($rivi[0]) != '' and trim($rivi[1]) != '') {

      $lokki = "LOCK TABLES $taulut";
      pupe_query($lokki);

      $vantuoteno = strtoupper(trim($rivi[0]));
      $uustuoteno = strtoupper(trim($rivi[1]));

      $query  = "SELECT tunnus, myyntihinta, kehahin, vihahin, vihapvm,yksikko,tuotepaallikko
                 FROM tuote
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND tuoteno = '$vantuoteno'";
      $tuoteresult = pupe_query($query);

      if (mysql_num_rows($tuoteresult) == 1) {

        $trivi = mysql_fetch_assoc($tuoteresult);

        $vanmyyntihinta    = $trivi['myyntihinta'];
        $vankehahin        = $trivi['kehahin'];
        $vanvihahin        = $trivi['vihahin'];
        $vanvihapvm        = $trivi['vihapvm'];
        $vanyksikko        = $trivi['yksikko'];
        $vantuotepaallikko = $trivi['tuotepaallikko'];

        if ($vantuotepaallikko > 0 and $muistutus == "KYLLA") {
          $postit[$vantuotepaallikko][] = $vantuoteno."###".$uustuoteno;
        }

        $query  = "SELECT tunnus
                   FROM tuote
                   WHERE yhtio = '$kukarow[yhtio]'
                   AND tuoteno = '$uustuoteno'";
        $tuoteuresult = pupe_query($query);

        if (in_array(strtoupper($vantuoteno."!¡!".$uustuoteno), $suuraakkosiin)) {
          $uusi_on_jo = "SAMA";
        }

        if (mysql_num_rows($tuoteuresult) == 0 or $uusi_on_jo == "OK" or $uusi_on_jo == "SAMA") {

          $query = "INSERT INTO tuote_muutokset
                    SET
                    yhtio         = '$kukarow[yhtio]',
                    tuoteno       = '$uustuoteno',
                    alkup_tuoteno = '$vantuoteno',
                    muutospvm     = now(),
                    kuka          = '$kukarow[kuka]'";
          $result2 = pupe_query($query);

          is_log(t("Vaihdetaan tuotenumero ja siirretään historiatiedot").": $vantuoteno --> $uustuoteno.");
          flush();

          foreach ($tulos as $saraketaulu) {

            list($taulu, $sarake) = explode("##", $saraketaulu);

            if ($taulu == 'tilausrivi') {
              $query = "UPDATE tilausrivi
                        SET tuoteno = '$uustuoteno'
                        WHERE yhtio = '$kukarow[yhtio]'
                        and tyyppi  in ($tyyppi)
                        and tuoteno = '$vantuoteno'";
              pupe_query($query);
            }
            elseif ($taulu == 'tuotepaikat' and $uusi_on_jo != "SAMA") {
              // Tuotepaikat käsitellään hieman eri lailla
              $query = "SELECT *
                        FROM tuotepaikat
                        WHERE yhtio = '$kukarow[yhtio]'
                        and tuoteno = '$vantuoteno'";
              $paires = pupe_query($query);

              while ($pairow = mysql_fetch_array($paires)) {

                // Tutkitaan löytyykö vanhan tuotteen paikka uudella tuoteella
                $query = "SELECT *
                          FROM tuotepaikat
                          WHERE yhtio   = '$kukarow[yhtio]'
                          and tuoteno   = '$uustuoteno'
                          and hyllyalue = '$pairow[hyllyalue]'
                          and hyllynro  = '$pairow[hyllynro]'
                          and hyllyvali = '$pairow[hyllyvali]'
                          and hyllytaso = '$pairow[hyllytaso]'";
                $paires2 = pupe_query($query);

                if (mysql_num_rows($paires2) == 0) {
                  $query = "UPDATE tuotepaikat
                            SET tuoteno = '$uustuoteno'
                            WHERE yhtio   = '$kukarow[yhtio]'
                            and tuoteno   = '$vantuoteno'
                            and hyllyalue = '$pairow[hyllyalue]'
                            and hyllynro  = '$pairow[hyllynro]'
                            and hyllyvali = '$pairow[hyllyvali]'
                            and hyllytaso = '$pairow[hyllytaso]'";
                  pupe_query($query);
                }
                else {
                  $query = "UPDATE tuotepaikat
                            SET saldo = saldo+$pairow[saldo]
                            WHERE yhtio   = '$kukarow[yhtio]'
                            and tuoteno   = '$uustuoteno'
                            and hyllyalue = '$pairow[hyllyalue]'
                            and hyllynro  = '$pairow[hyllynro]'
                            and hyllyvali = '$pairow[hyllyvali]'
                            and hyllytaso = '$pairow[hyllytaso]'";
                  pupe_query($query);

                  $query = "DELETE from tuotepaikat
                            WHERE yhtio   = '$kukarow[yhtio]'
                            and tuoteno   = '$vantuoteno'
                            and hyllyalue = '$pairow[hyllyalue]'
                            and hyllynro  = '$pairow[hyllynro]'
                            and hyllyvali = '$pairow[hyllyvali]'
                            and hyllytaso = '$pairow[hyllytaso]'
                            and tunnus    = '$pairow[tunnus]'";
                  pupe_query($query);
                }
              }

              // Fixaataan jottei meille jäisi useita oletuspaikkoja
              $query = "SELECT *
                        FROM tuotepaikat
                        WHERE yhtio  = '$kukarow[yhtio]'
                        and tuoteno  = '$uustuoteno'
                        and oletus  != ''";
              $paires = pupe_query($query);

              if (mysql_num_rows($paires) == 0) {
                $query = "UPDATE tuotepaikat
                          SET oletus = 'X'
                          WHERE yhtio = '$kukarow[yhtio]'
                          and tuoteno = '$uustuoteno'
                          ORDER BY tunnus
                          LIMIT 1";
                pupe_query($query);
              }
              elseif (mysql_num_rows($paires) > 1) {
                $query = "UPDATE tuotepaikat
                          SET oletus  = ''
                          WHERE yhtio  = '$kukarow[yhtio]'
                          and tuoteno  = '$uustuoteno'
                          and oletus  != ''
                          ORDER BY tunnus
                          LIMIT ".(mysql_num_rows($paires)-1);
                pupe_query($query);
              }
            }
            elseif ($uusi_on_jo == "" or ($uusi_on_jo == "OK" and $taulu != 'tuote') or $uusi_on_jo == "SAMA") {
              if ($taulu == 'tuotteen_toimittajat' and $uusi_on_jo != "SAMA") {
                $query = "SELECT *
                          FROM $taulu
                          WHERE yhtio = '$kukarow[yhtio]'
                          and tuoteno = '$uustuoteno'";
                $result2 = pupe_query($query);

                if (mysql_num_rows($result2) > 0) {
                  $query = "DELETE
                            FROM $taulu
                            WHERE yhtio = '$kukarow[yhtio]'
                            AND tuoteno = '$vantuoteno'";
                  pupe_query($query);
                }
              }

              if ($taulu == 'tuoteperhe') {
                $query = "UPDATE $taulu
                          SET $sarake  = '$uustuoteno'
                          WHERE yhtio = '$kukarow[yhtio]'
                          AND tyyppi  in ($perhetyyppi)
                          AND $sarake  = '$vantuoteno'";
                $result2 = pupe_query($query);
              }
              elseif ($taulu == 'tuotteen_avainsanat') {
                $query = "UPDATE IGNORE $taulu
                          SET $sarake = '$uustuoteno'
                          WHERE yhtio = '$kukarow[yhtio]'
                          AND kieli   in ($kielet)
                          AND $sarake = '$vantuoteno'";
                pupe_query($query);

                if ($uusi_on_jo != "SAMA") {
                  $query = "DELETE FROM $taulu
                            WHERE yhtio = '$kukarow[yhtio]'
                            AND kieli   in ($kielet)
                            AND $sarake = '$vantuoteno'";
                  pupe_query($query);
                }
              }
              elseif ($taulu == 'puun_alkio') {
                $query = "UPDATE IGNORE puun_alkio
                          SET liitos  = '$uustuoteno'
                          WHERE yhtio = '$kukarow[yhtio]'
                          AND laji    = 'Tuote'
                          AND liitos  = '$vantuoteno'";
                pupe_query($query);

                $query = "DELETE FROM puun_alkio
                          WHERE yhtio = '$kukarow[yhtio]'
                          AND laji    = 'Tuote'
                          AND liitos  = '$vantuoteno'";
                pupe_query($query);
              }
              elseif ($taulu == 'asn_sanomat') {
                $query = "UPDATE asn_sanomat
                          SET {$sarake} = '{$uustuoteno}'
                          WHERE yhtio = '{$kukarow['yhtio']}'
                          and {$sarake} = '{$vantuoteno}'
                          AND status  not in ('E', 'D')";
                pupe_query($query);
              }
              elseif (in_array($taulu, $muutataulut)) {
                $query = "UPDATE IGNORE $taulu
                          SET $sarake = '$uustuoteno'
                          WHERE yhtio = '$kukarow[yhtio]'
                          AND $sarake = '$vantuoteno'";
                pupe_query($query);

                if ($uusi_on_jo != "SAMA") {
                  $query = "DELETE FROM $taulu
                            WHERE yhtio = '$kukarow[yhtio]'
                            AND $sarake = '$vantuoteno'";
                  pupe_query($query);
                }
              }
              else {
                $query = "UPDATE $taulu
                          SET $sarake = '$uustuoteno'
                          WHERE yhtio = '$kukarow[yhtio]'
                          AND $sarake = '$vantuoteno'";
                pupe_query($query);
              }
            }
          }

          if ($jatavanha != '' and $uusi_on_jo != "SAMA") {
            if ($uusi_on_jo == "") {
              $query = "SELECT *
                        FROM avainsana
                        WHERE yhtio    = '$kukarow[yhtio]'
                        AND laji       = 'alv'
                        AND selitetark = 'o'
                        LIMIT 1";
              $alvresult = pupe_query($query);
              $alv = '0.00';

              if (mysql_num_rows($alvresult) != '0') {
                $alvrow = mysql_fetch_array($alvresult);
                $alv = $alvrow['selite'];
              }

              $query = "INSERT INTO tuote
                        SET
                        tuoteno     = '$vantuoteno',
                        nimitys     = '". t("Korvaava tuoteno", $yhtiorow["kieli"]) ." $uustuoteno',
                        osasto      = '999999',
                        try         = '999999',
                        alv         = '$alv',
                        status      = '$status',
                        hinnastoon  = '$hinnastoon',
                        ostoehdotus = '$ostoehdotus',
                        yhtio       = '$kukarow[yhtio]'";
              pupe_query($query);
            }

            $querykorv = "SELECT max(id)+1 maxi
                          FROM korvaavat
                          WHERE yhtio = '$kukarow[yhtio]'";
            $korvresult = pupe_query($querykorv);
            $korvid = mysql_fetch_array($korvresult);

            $loytyikorv = '';

            $querykorvv  = "SELECT id maxi, jarjestys
                            FROM korvaavat
                            WHERE yhtio = '$kukarow[yhtio]'
                            AND tuoteno = '$uustuoteno'";
            $korvvresult = pupe_query($querykorvv);
            $jarjestys = '2';

            if (mysql_num_rows($korvvresult) != '0') {
              $korvid = mysql_fetch_array($korvvresult);
              //echo "löytyi korvid"."$korvid[maxi]"." $vantuoteno --> $uustuoteno<br>";
              $loytyikorv = '1';
              $jarjestys = $korvid['jarjestys'] + 1;
            }

            $query = "INSERT INTO korvaavat
                      SET
                      tuoteno    = '$vantuoteno',
                      id         = '$korvid[maxi]',
                      jarjestys  = '$jarjestys',
                      yhtio      = '$kukarow[yhtio]',
                      laatija    = '$kukarow[kuka]',
                      luontiaika = now(),
                      muuttaja   = '$kukarow[kuka]',
                      muutospvm  = now()";
            $result4 = pupe_query($query);

            if ($loytyikorv != '1') {
              $query = "INSERT INTO korvaavat
                        SET
                        tuoteno    = '$uustuoteno',
                        id         = '$korvid[maxi]',
                        jarjestys  = '1',
                        yhtio      = '$kukarow[yhtio]',
                        laatija    = '$kukarow[kuka]',
                        luontiaika = now(),
                        muuttaja   = '$kukarow[kuka]',
                        muutospvm  = now()";
              $result4 = pupe_query($query);
            }

            // päivitetään järjestykset muille paitsi "päätuotteelle", "vanhatuotteelle", nollille ja isommille järjestyksille kuin mikä on "vanhatuotteella"
            $query = "UPDATE korvaavat
                      SET jarjestys = jarjestys + 1
                      WHERE id       = '$korvid[maxi]'
                      AND yhtio      = '$kukarow[yhtio]'
                      AND jarjestys != 0
                      AND jarjestys  >= '$jarjestys'
                      AND tuoteno   != '$vantuoteno'
                      AND tuoteno   != '$uustuoteno'";
            $result4 = pupe_query($query);
          }
          $lask++;
        }
        else {
          is_log(t("UUSI TUOTENUMERO LÖYTYY JO")." $uustuoteno");
        }
      }
      else {
        is_log(t("VANHAA TUOTENUMEROA EI LÖYDY")." $vantuoteno");
      }
    }

    // Nollataan tämä, koska sitä muutetaan loopissa
    $uusi_on_jo = "";

    $unlokki = "UNLOCK TABLES";
    $res     = pupe_query($unlokki);
  }

  if (count($postit) > 0 and $muistutus == "KYLLA") {

    $vva = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
    $kka = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
    $ppa = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));

    foreach ($postit as $key => $values) {
      $lista = "";

      foreach ($values as $tuote => $value) {
        $apu = explode("###", $value);
        $lista .= "\n- Tuote (".$apu[0]. ") tuotenumero on vaihdettu/korvattu tuotteella (".$apu[1]."), tarkista asiakkaiden alennukset ja hinnat varmuudeksi";
        $lista  .= "\n Linkki tuotteen asiakashintoihin: {$palvelin2}yllapito.php?toim=asiakashinta&indexvas=1&haku[6]=$apu[0] (".$apu[0].")";
        $lista  .= "\n Linkki tuotteen myynninseurantaan: {$palvelin2}raportit/myyntiseuranta.php?ruksit[70]=checked&ruksit[80]=checked&nimitykset=checked&ppa=$ppa&kka=$kka&vva=$vva&tuotteet_lista=$apu[0]\n";
      }

      // Haetaan tuotepäälliköiden sähköpostiosoitteet esille.
      $postisql  = "SELECT kuka, nimi, eposti
                    FROM kuka
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND myyja   = '$key'";
      $resuposti = pupe_query($postisql);

      while ($posti = mysql_fetch_assoc($resuposti)) {

        $meili = t("Tuotteiden tuotenumerot on vaihtuneet")."\n";
        $meili .= "\nTervehdys $posti[nimi] \n";
        $meili .= "\nKäyttäjä $kukarow[nimi] on vaihtanut tuotteiden tuotenumeroita\n";
        $meili .= t("Pyyntö").":\n".str_replace("\r\n", "\n", "Tarkista seuraavilta tuotteilta hinnat ja asiakasalennukset\n");
        $meili .= $lista;

        if ($posti['eposti'] == "") {
          $email_osoite = $yhtiorow['alert_email'];
        }
        else {
          $email_osoite = $posti['eposti'];
        }

        $tulos = mail($email_osoite, mb_encode_mimeheader(t("Tuotteiden tuotenumerot on vaihtuneet")." $yhtiorow[nimi]", "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["postittaja_email"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
      }
    }
  }

  fclose($file);

  is_log(t("Valmis, muutettiin")." $lask ".t("tuotetta")."!");
  $tee = "";
}
elseif ($tee == "file") {
  echo "<font class='error'>".t("Edellämainitut viat pitää korjata ennenkuin voidaan jatkaa")."!!!<br>".t("Mitään ei päivitetty")."!!!<br><br>";
  $tee = "";
}


if ($tee == "" and $php_cli === false) {

  echo "<form method='post' name='sendfile' enctype='multipart/form-data'>

      <table>

      <tr>
        <td class='back' colspan='2'><br><font class='message'>".t("Sisäänlue tiedostosta")."</font><hr></td>
      </tr>

      <tr>
        <th colspan='2'>".t("Tabulaattorilla eroteltu tekstitiedosto").". ".t("Tiedoston sarakkeet").":</th>
      </tr>

      <tr>
        <td>".t("VANHA tuotenumero")."</td>
        <td>".t("UUSI tuotenumero")."</td>
      </tr>

      <tr>
        <th>".t("Valitse tiedosto").":</th>
        <td><input name='userfile' type='file'></td>
      </tr>

      <tr>
        <td class='back' colspan='2'><br><font class='message'>".t("Tai syöta tuotenumerot")."</font><hr></td>
      </tr>

      <tr>
        <th>".t("Vanha tuotenumero").":</th>
        <td><input type='text' name='vantuoteno' size='25'></td>
      </tr>

      <tr>
        <th>".t("Uusi tuotenumero").":</th>
        <td><input type='text' name='uustuoteno' size='25'></td>
      </tr>

      <tr>
        <td class='back' colspan='2'><br><font class='message'>".t("Lisävalinnat")."</font><hr></td>
      </tr>

      <tr>
        <th>".t("Jätä vanha tuotenumero uuden tuotteen korvaavaksi tuotteeksi")."</th>
        <td><input type='checkbox' name='jatavanha' value='jatavanha'</td>
      </tr>

      <tr>
        <th>".t("Valitse vanhan tuotteen status")."</th>
        <td><select name='status'>";
  echo product_status_options($vrow["selite"]);
  echo "</select></td>";
  echo "</tr>";

  echo "  <tr><th>".t("Valitse vanhan tuotteen näkyvyys")."</th>";
  echo "<td><select name='hinnastoon' ".js_alasvetoMaxWidth("hinnastoon", 200).">";
  echo "  <option value='E'>".t("Tuotetta ei näytetä hinnastossa, eikä verkkokaupassa")."</option>
         <option value=''>".t("Tuote näytetään hinnastossa, mutta ei verkkokaupassa")."</option>
         <option value='W'>".t("Tuote näkyy hinnastossa ja verkkokaupassa")."</option>
         <option value='V'>".t("Tuote näkyy hinnastossa sekä verkkokaupassa jos asiakkaalla asiakasalennus tai asiakashinta")."</option>";

  echo "  </select></td></tr>";

  echo "  <tr><th>".t("Valitse vanhan tuotteen ostoehdotus")."</th>";
  echo "<td><select name='ostoehdotus' ".js_alasvetoMaxWidth("ostoehdotus", 200).">";
  echo "  <option value='E'>".t("Ei ehdoteta ostoehdotusohjelmissa tilattavaksi")."</option>
         <option value=''>".t("Ehdotetaan ostoehdotusohjelmissa tilattavaksi")."</option>";

  echo "  </select></td></tr>";

  echo "  <tr>
      <th>".t("Lähetä sähköposti tuotepäällikköille muutoksista")."</th>";
  echo "  <td><input type='checkbox' name='muistutus' value='KYLLA'></td>";
  echo "  </tr>";

  echo "  </table>
      <br>
      <input type='hidden' name='tee' value='file'>
      <input type='submit' value='".t("Siirrä tuotteen historia")."'>
      </form>";
}

require "inc/footer.inc";
