<?php

// Tiedoston tallennusta varten
$lataa_tiedosto = (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == "lataa_tiedosto") ? 1 : 0;
$_REQUEST["kaunisnimi"] = isset($_REQUEST["kaunisnimi"]) ? str_replace("/", "", $_REQUEST["kaunisnimi"]) : "";

ini_set("memory_limit", "5G");

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 2;

require "inc/parametrit.inc";

// Käytettävät muuttujat
$alkukk         = isset($alkukk) ? trim($alkukk) : "";
$alkuvv         = isset($alkuvv) ? trim($alkuvv) : "";
$budj_kohtelu       = isset($budj_kohtelu) ? trim($budj_kohtelu) : "";
$budjetointi_taso     = isset($budjetointi_taso) ? trim($budjetointi_taso) : "";
$loppukk         = isset($loppukk) ? trim($loppukk) : "";
$loppuvv         = isset($loppuvv) ? trim($loppuvv) : "";
$muutparametrit     = isset($muutparametrit) ? trim($muutparametrit) : "";
$onko_ilman_budjettia = isset($onko_ilman_budjettia) ? trim($onko_ilman_budjettia) : "";
$submit_button       = isset($submit_button) ? trim($submit_button) : "";
$summabudjetti       = isset($summabudjetti) ? trim($summabudjetti) : "";
$tee           = isset($tee) ? trim($tee) : "";
$tuoteno         = isset($tuoteno) ? mysql_real_escape_string(trim($tuoteno)) : "";
$osastotryttain     = isset($osastotryttain) ? trim($osastotryttain) : "";
$lisa           = isset($lisa) ? trim($lisa) : "";
$lisa_parametri     = isset($lisa_parametri) ? trim($lisa_parametri) : "";
$ytunnus         = isset($ytunnus) ? trim($ytunnus) : "";
$asiakasid         = isset($asiakasid) ? (int) $asiakasid : 0;
$toimittajaid       = isset($toimittajaid) ? (int) $toimittajaid : 0;
$myyntiennustekerroin = isset($myyntiennustekerroin) ? (float) str_replace(",", ".", $myyntiennustekerroin) : 12;
$myyntitavoitekerroin = (isset($myyntitavoitekerroin) and $myyntitavoitekerroin != "") ? (float) str_replace(",", ".", $myyntitavoitekerroin) : "";
$naytamyyntiennuste   = isset($naytamyyntiennuste) ? trim($naytamyyntiennuste) : "";

$edellinen_vuosi_alku     = date("Y-m-d", mktime(0, 0, 0, 1, 1, date("Y")-1));
$edellinen_vuosi_loppu     = date("Y-m-d", mktime(0, 0, 0, 1, 0, date("Y")));
$edellinen_kuukausi_loppu = date("Y-m-d", mktime(0, 0, 0, date("m"), 0, date("Y")));

$maxrivimaara = 5000;

// Tiedoston tallennusta varten
if ($tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

// Tämä funkkari palauttaa syötetyn tuotteen sen kuukauden myynnin arvon ja kpl-määrän
function tuotteenmyynti($tuoteno, $alkupvm) {

  // Tarvitaan vain tuoteno ja kuukauden ensimmäinen päivä
  global $kukarow;

  $query = "SELECT ifnull(round(sum(if(tyyppi='L', rivihinta, 0)), 2), 0) summa,
            ifnull(round(sum(kpl), 0), 0) maara
            FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
            WHERE yhtio        = '{$kukarow["yhtio"]}'
            AND tyyppi         in ('L','V')
            AND tuoteno        = '{$tuoteno}'
            AND laskutettuaika >= '{$alkupvm}'
            AND laskutettuaika <= last_day('{$alkupvm}')";
  $result = pupe_query($query);
  $rivi = mysql_fetch_assoc($result);

  return array($rivi["summa"], $rivi["maara"]);
}

function asiakkaanmyynti($tunnukset, $try, $osasto, $vaintamavuosi=FALSE) {
  global $kukarow, $yhtiorow, $edellinen_vuosi_alku, $edellinen_kuukausi_loppu, $edellinen_vuosi_loppu, $myyntiennustekerroin, $myyntitavoitekerroin, $ostryntuotteet, $toim;

  $rivilisa = "";

  if ($try != "" and isset($ostryntuotteet[$try]) and $ostryntuotteet[$try] != "") {
    $rivilisa = " and tilausrivi.tuoteno in ({$ostryntuotteet[$try]}) ";
  }
  elseif ($osasto != "" and isset($ostryntuotteet[$osasto]) and $ostryntuotteet[$osasto] != "") {
    $rivilisa = " and tilausrivi.tuoteno in ({$ostryntuotteet[$osasto]}) ";
  }

  if ($vaintamavuosi) {
    $alkupaiva = " and lasku.tapvm > '{$edellinen_vuosi_loppu}' ";
  }
  else {
    $alkupaiva = " and lasku.tapvm >= '{$edellinen_vuosi_alku}' ";
  }

  if ($toim == "MYYJA") {
    $liitostunnuslisa = "and lasku.myyja in ({$tunnukset})";
    $use_index = "yhtio_tila_tapvm";
  }
  elseif ($toim == "ASIAKASMYYJA") {
    $liitostunnuslisa = " and asiakas.myyjanro in ({$tunnukset})";
    $use_index = "yhtio_tila_tapvm";
  }
  else {
    $liitostunnuslisa = "and lasku.liitostunnus in ({$tunnukset})";
    $use_index = "yhtio_tila_liitostunnus_tapvm";
  }

  $query = "SELECT ROUND(SUM(IF(tapvm <= '{$edellinen_vuosi_loppu}', tilausrivi.rivihinta, 0))) edvuodenkokonaismyynti,
            ROUND(SUM(IF(tapvm > '{$edellinen_vuosi_loppu}', tilausrivi.rivihinta, 0))) tanvuodenalustamyynti
            FROM lasku USE INDEX ({$use_index})
            JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.uusiotunnus = lasku.tunnus {$rivilisa})
            LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            and lasku.tila    = 'U'
            and lasku.alatila = 'X'
            {$liitostunnuslisa}
            {$alkupaiva}
            and lasku.tapvm   <= '{$edellinen_kuukausi_loppu}'";
  $result = pupe_query($query);
  $laskurow = mysql_fetch_assoc($result);

  $edvuodenkokonaismyynti = (float) $laskurow['edvuodenkokonaismyynti'] == 0 ? "" : $laskurow['edvuodenkokonaismyynti'];
  $tanvuodenalustamyynti  = (float) $laskurow['tanvuodenalustamyynti']  == 0 ? "" : $laskurow['tanvuodenalustamyynti'];
  $tanvuodenennuste     = "";
  $ensvuodenennuste     = "";

  if ((float) $tanvuodenalustamyynti != 0) {
    $tanvuodenennuste = round($tanvuodenalustamyynti / substr($edellinen_kuukausi_loppu, 5, 2) * $myyntiennustekerroin);
    if ($myyntitavoitekerroin > 0 and $tanvuodenennuste > 0) $ensvuodenennuste = round($tanvuodenalustamyynti / substr($edellinen_kuukausi_loppu, 5, 2) * $myyntiennustekerroin * $myyntitavoitekerroin);
  }

  return array($edvuodenkokonaismyynti, $tanvuodenalustamyynti, $tanvuodenennuste, $ensvuodenennuste);
}

// funkkarin $org_sar passataan alkuperäiset sarakkeet, jota taas käytetään "ohituksessa"
function piirra_budj_rivi($row, $ostryrow = "", $ohitus = "", $org_sar = "") {
  global  $kukarow, $yhtiorow, $toim, $worksheet, $excelrivi, $budj_taulu, $rajataulu, $budj_taulunrivit, $xx, $budj_sarak,
  $sarakkeet, $rivimaara, $maxrivimaara, $grouppaus, $haen, $passaan, $budj_kohtelu, $osastotryttain,
  $edellinen_vuosi_alku, $edellinen_kuukausi_loppu, $edellinen_vuosi_loppu, $summabudjetti, $myyntiennustekerroin,
  $naytamyyntiennuste, $myyntitavoitekerroin, $budjetointi_taso, $ostryntuotteet;

  $excelsarake = 0;
  $worksheet->write($excelrivi, $excelsarake, $row[$budj_sarak]);
  $excelsarake++;

  if ($toim == "ASIAKAS" or $toim == "TOIMITTAJA") {
    $worksheet->writeString($excelrivi, $excelsarake, $row['ytunnus']);
    $excelsarake++;
  }

  if ($toim == "MAA") {
    $worksheet->writeString($excelrivi, $excelsarake, $row['nimi']);
    $excelsarake++;

    if ($rivimaara < $maxrivimaara) echo "<tr><td>{$row['nimi']}</td>";
  }

  if ($toim == "MYYJA") {
    $worksheet->writeString($excelrivi, $excelsarake, "{$row['myyja']} {$row['nimi']}");
    $excelsarake++;

    if ($rivimaara < $maxrivimaara) echo "<tr><td>$row[myyja] $row[nimi]</td>";
  }

  if ($toim == "ASIAKASMYYJA") {
    $worksheet->writeString($excelrivi, $excelsarake, "{$row['asiakasmyyja']} {$row['nimi']}");
    $excelsarake++;

    if ($rivimaara < $maxrivimaara) echo "<tr><td>$row[myyja] $row[nimi]</td>";
  }

  if ($toim == "ASIAKAS") {
    $worksheet->writeString($excelrivi, $excelsarake, $row['asiakasnro']);
    $excelsarake++;
  }

  if ($toim == "TUOTE") {
    $worksheet->writeString($excelrivi, $excelsarake, $row['nimitys']);
    $excelsarake++;

    if ($grouppaus != "") {
      if ($rivimaara < $maxrivimaara) echo "<tr><td>$row[selitetark]</td>";
    }
    else {
      if ($rivimaara < $maxrivimaara) echo "<tr><td>$row[tuoteno] $row[nimitys]</td>";
    }
  }
  elseif ($toim == "ASIAKAS" or $toim == "TOIMITTAJA") {
    $worksheet->writeString($excelrivi, $excelsarake, $row['nimi'].' '.$row['nimitark']);
    $excelsarake++;
    if ($rivimaara < $maxrivimaara) echo "<tr><td>$row[ytunnus] $row[asiakasnro]<br>$row[nimi] $row[nimitark]<br>$row[toim_nimi] $row[toim_nimitark]</td>";
  }

  $try    = "";
  $osasto    = "";
  $ostry_ind  = 0;

  if (is_array($ostryrow)) {
    if ($rivimaara < $maxrivimaara) echo "<td>$ostryrow[selite] $ostryrow[selitetark]</td>";

    $worksheet->write($excelrivi, $excelsarake, $ostryrow["selite"]);
    $excelsarake++;

    if (isset($osastotryttain) and $osastotryttain == "tuoteryhmittain") {
      $try      = $ostryrow["selite"];
      $ostry_ind  = $try;
    }
    elseif (isset($osastotryttain) and $osastotryttain == "osastoittain") {
      $osasto    = $ostryrow["selite"];
      $ostry_ind = $osasto;
    }
  }

  if ($toim == "ASIAKAS" or $toim == "MYYJA" or $toim == "ASIAKASMYYJA") {
    if ($naytamyyntiennuste != "") {

      if ($toim == "MYYJA") {
        list($edvuodenkokonaismyynti, $tanvuodenalustamyynti, $tanvuodenennuste, $ensvuodenennuste) = asiakkaanmyynti($row["myyjan_tunnus"], $try, $osasto);
      }
      elseif ($toim == "ASIAKASMYYJA") {
        list($edvuodenkokonaismyynti, $tanvuodenalustamyynti, $tanvuodenennuste, $ensvuodenennuste) = asiakkaanmyynti($row["asiakasmyyjan_tunnus"], $try, $osasto);
      }
      else {
        list($edvuodenkokonaismyynti, $tanvuodenalustamyynti, $tanvuodenennuste, $ensvuodenennuste) = asiakkaanmyynti($row["asiakkaan_tunnus"], $try, $osasto);
      }

      if ($rivimaara < $maxrivimaara) {
        echo "<td align='right'>{$edvuodenkokonaismyynti}</td>";
        echo "<td align='right'>{$tanvuodenalustamyynti}</td>";
        echo "<td align='right'>{$tanvuodenennuste}</td>";
      }

      $worksheet->writeNumber($excelrivi, $excelsarake, $edvuodenkokonaismyynti);
      $excelsarake++;
      $worksheet->writeNumber($excelrivi, $excelsarake, $tanvuodenalustamyynti);
      $excelsarake++;
      $worksheet->writeNumber($excelrivi, $excelsarake, $tanvuodenennuste);
      $excelsarake++;
    }
  }

  if ($ohitus != "") {
    $sarakkeet = 1;
  }

  for ($k = 0; $k < $sarakkeet; $k++) {
    $ik = $rajataulu[$k];

    if ($myyntitavoitekerroin > 0) {
      if ($budj_kohtelu == "indeksi") {
        $nro = $myyntitavoitekerroin;
      }
      elseif ($ensvuodenennuste > 0) {
        $nro = round($ensvuodenennuste/12);
      }
    }
    elseif (isset($budj_taulunrivit[$row[$budj_sarak]][$ik][$ostry_ind])) {
      $nro = trim($budj_taulunrivit[$row[$budj_sarak]][$ik][$ostry_ind]) == "" ? "" : (float) trim($budj_taulunrivit[$row[$budj_sarak]][$ik][$ostry_ind]);
    }
    else {

      if ($toim == "ASIAKAS" and $summabudjetti == "on" and $row[$budj_sarak] != "") {
        $query = "SELECT sum(summa) summa,
                  sum(maara) maara,
                  avg(indeksi) indeksi
                  FROM $budj_taulu
                  WHERE yhtio = '$kukarow[yhtio]'
                  and kausi   = '$ik'
                  and $budj_sarak    in ($row[$budj_sarak])
                  and try     = '$try'
                  and osasto  = '$osasto'";
      }
      else {
        $query = "SELECT *
                  FROM $budj_taulu
                  WHERE yhtio = '$kukarow[yhtio]'
                  and kausi   = '$ik'
                  and $budj_sarak    = '$row[$budj_sarak]'
                  and try     = '$try'
                  and osasto  = '$osasto'";
      }
      $xresult = pupe_query($query);

      $nro = '';

      if (mysql_num_rows($xresult) == 1) {
        $brow = mysql_fetch_assoc($xresult);
        $nro  = $brow['summa'];

        // normaalisti näytetään summaa, mutta tuotteella, mikäli käsitellään määrää niin näytetään määrä tai indeksissä indeksi
        if ($budj_kohtelu == "maara") {
          $nro = $brow['maara'];
        }
        if ($budj_kohtelu == "indeksi") {
          $nro = $brow['indeksi'];
        }
      }
    }

    if ($rivimaara < $maxrivimaara) echo "<td>";

    if ($ohitus != "") {
      // mikäli muutat "poikkeus" tai "poikkeus_haku" nimityksiä tai arvoja tai lisäät haaroja, niin muuta/lisää ne myös kohtaan riville 258
      // jossa käsitellään ne arrayn luonnissa
      // ÄLÄ MUUTA "<input type='text' class = '{$row[$budj_sarak]}'"
      // Mikäli teet muutoksia niin muuta myös jqueryyn riville noin 17
      if ($nro != "" and $budjetointi_taso == "summa_jaetaan") {
        $nro = $nro*$org_sar;
      }

      $classi = preg_replace("/[^0-9]/", "", $row[$budj_sarak].$ostry_ind);

      if ($rivimaara < $maxrivimaara) {
        if ($grouppaus != "" and !empty($passaan)) {
          if ($haen == "try" and $passaan == "yksi") {
            echo "<input type='text' class = '{$classi}' name = 'luvut[{$row["try"]}][{$ik}][{$ostry_ind}]' value='' size='10'>";
            echo "<input type='hidden' name = 'poikkeus' value='totta'>";
            echo "<input type='hidden' name = 'poikkeus_haku' value='try'>";
          }
          elseif ($haen == "osasto" and $passaan == "yksi") {
            echo "<input type='text' class = '{$classi}' name = 'luvut[{$row["osasto"]}][{$ik}][{$ostry_ind}]' value='' size='10'>";
            echo "<input type='hidden' name = 'poikkeus' value='totta'>";
            echo "<input type='hidden' name = 'poikkeus_haku' value='osasto'>";
          }
          elseif ($haen == "try" and $passaan == "kaksi") {
            echo "<input type='text' class = '{$classi}' name = 'luvut[{$row["osasto"]},{$row["try"]}][{$ik}][{$ostry_ind}]' value='' size='10'>";
            echo "<input type='hidden' name = 'poikkeus' value='totta'>";
            echo "<input type='hidden' name = 'poikkeus_haku' value='kummatkin'>";
          }
        }
        else {
          echo "<input type='text' class = '{$classi}' name = 'luvut[{$row[$budj_sarak]}][{$ik}][{$ostry_ind}]' value='{$nro}' size='10'>";
        }

        for ($a = 1; $a < $org_sar; $a++) {
          $ik = $rajataulu[$a];

          if ($grouppaus != "" and !empty($passaan)) {
            if ($haen == "try" and $passaan == "yksi") {
              echo "<input type='hidden' id = '{$classi}_{$ik}' name = 'luvut[{$row["try"]}][{$ik}][{$ostry_ind}]' value='{$nro}' size='10'>";
            }
            elseif ($haen == "osasto" and $passaan == "yksi") {
              echo "<br>input type='hidden' id = '{$classi}_{$ik}' name = 'luvut[{$row["osasto"]}][{$ik}][{$ostry_ind}]' value='{$nro}' size='10'
                <input type='hidden' id = '{$classi}_{$ik}' name = 'luvut[{$row["osasto"]}][{$ik}][{$ostry_ind}]' value='{$nro}' size='10'>";
            }
            elseif ($haen == "try" and $passaan == "kaksi") {
              echo "<input type='hidden' id = '{$classi}_{$ik}' name = 'luvut[{$row["osasto"]},{$row["try"]}][{$ik}][{$ostry_ind}]' value='{$nro}' size='10'>";
            }
          }
          else {
            echo "<input type='hidden' id = '{$classi}_{$ik}' name = 'luvut[{$row[$budj_sarak]}][{$ik}][{$ostry_ind}]' value='{$nro}' size='10'>";
          }
        }

        echo "</td>";
      }
    }
    else {
      if ($rivimaara < $maxrivimaara) echo "<input type='text' name = 'luvut[{$row[$budj_sarak]}][{$ik}][$ostry_ind]' value='{$nro}' size='10'>";
    }

    if ($rivimaara < $maxrivimaara) echo "</td>";

    $worksheet->write($excelrivi, $excelsarake, $nro);
    $excelsarake++;
  }

  if ($rivimaara < $maxrivimaara) {
    echo "</tr>";
    ob_flush();
    flush();
  }

  $xx++;
  $excelrivi++;
}

// Tässä katsotaan mitä syötetään ja kopioidaan kaikkiin samannimisiin ja ID:llä varustettuihin hidden syöttökenttiin samat arvot.
// Käytetään kokonaisbudjetissa, samasumma per kk / summa jaetaan per kk
// Mikäli teet muutoksia niin muuta myös riveille alkaen 1170 !!!
echo "<script type='text/javascript'>
    $(document).ready(function() {
     $('input[name^=luvut]').keyup(function() {
      var id = $(this).attr('class');
      $('input[id^='+id+'_]').val($(this).val());
     });
    });
    </script>";

// Ohjelman headerit
if ($toim == "TUOTE") {
  echo "<font class='head'>".t("Tuotteiden myyntitavoitteet")."</font><hr>";

  $budj_taulu = "budjetti_tuote";
  $budj_sarak = "tuoteno";
}
elseif ($toim == "TOIMITTAJA") {
  echo "<font class='head'>".t("Toimittajien ostotavoitteet")."</font><hr>";

  $budj_taulu = "budjetti_toimittaja";
  $budj_sarak = "toimittajan_tunnus";
}
elseif ($toim == "ASIAKAS") {
  echo "<font class='head'>".t("Asiakkaiden myyntitavoitteet")."</font><hr>";

  $budj_taulu = "budjetti_asiakas";
  $budj_sarak = "asiakkaan_tunnus";
}
elseif ($toim == "MYYJA") {
  echo "<font class='head'>", t("Myyjien myyntitavoitteet"), "</font><hr>";

  $budj_taulu = "budjetti_myyja";
  $budj_sarak = "myyjan_tunnus";
}
elseif ($toim == "MAA") {
  echo "<font class='head'>", t("Maiden myyntitavoitteet"), "</font><hr>";

  $budj_taulu = "budjetti_maa";
  $budj_sarak = "maa_id";
}
elseif ($toim == "ASIAKASMYYJA") {
  echo "<font class='head'>", t("Asiakasmyyjien myyntitavoitteet"), "</font><hr>";

  $budj_taulu = "budjetti_asiakasmyyja";
  $budj_sarak = "asiakasmyyjan_tunnus";
}
else {
  echo "<font class='error'>", t("Anna ohjelmalle alanimi: TUOTE, TOIMITTAJA, ASIAKAS, MYYJA, ASIAKASMYYJA tai MAA"), ".</font>";
  exit;
}

// Ollaan uploadattu Excel
if (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE and $budjetointi_taso != "kuukausittain" and $toim == "TUOTE") {
  echo "<font class='error'>".t("Tiedostoja voidaan ajaa sisään vain kuukausittain aikavälillä")."!</font><br><br>";
  $tee = "";
}
elseif (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

  $path_parts = pathinfo($_FILES['userfile']['name']);
  $ext = strtoupper($path_parts['extension']);

  $retval = tarkasta_liite("userfile", array("XLSX", "XLS", "ODS", "SLK", "XML", "GNUMERIC", "CSV", "TXT", "DATAIMPORT"));

  if ($retval !== TRUE) {
    die ("<font class='error'><br>".t("Väärä tiedostomuoto")."!</font>");
  }

  if ($_FILES['userfile']['size'] == 0) {
    die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
  }

  $excelrivit = pupeFileReader($_FILES['userfile']['tmp_name'], $ext);

  $headers       = array();
  $budj_taulunrivit   = array();
  $liitostunnukset   = "";

  $headers = array_shift($excelrivit);

  for ($excej = (count($headers)-1); $excej > 0 ; $excej--) {
    if ($headers[$excej] != "") {
      break;
    }
    else {
      unset($headers[$excej]);
    }
  }

  // Huomaa nämä jos muutat excel-failin sarakkeita!!!!
  if ($toim == "TUOTE" or $toim == "MYYJA" or $toim == "ASIAKASMYYJA") {
    $lukualku = 2;
  }
  elseif ($toim == "MAA") {
    $lukualku = 1;
  }
  elseif ($toim == "TOIMITTAJA") {
    $lukualku = 3;
  }
  elseif ($toim == "ASIAKAS") {
    $lukualku = 4;
  }

  if ($headers[$lukualku] == "Tuoteryhmä") {
    $tryindeksi = $lukualku;
    $lukualku++;
    $osastotryttain = "tuoteryhmittain";
  }
  elseif ($headers[$lukualku] == "Osasto") {
    $tryindeksi = $lukualku;
    $lukualku++;
    $osastotryttain = "osastoittain";
  }

  // Hypätään myös myyntisarakkeiden yli
  if (($toim == "ASIAKAS" or $toim == "MYYJA" or $toim == "ASIAKASMYYJA") and stripos($headers[$lukualku], "Myynti")  !== FALSE) {
    $lukualku += 3;
  }

  $insert_rivimaara = 0;

  foreach ($excelrivit as $rivinro => $rivi) {

    $liitun = $rivi[0];
    $liitostunnukset .= "'$liitun',";

    if ($osastotryttain != "") {
      $try = $rivi[$tryindeksi];
    }

    for ($excej = $lukualku; $excej < count($headers); $excej++) {
      $kasiind = str_replace("-", "", $headers[$excej]);

      if ($osastotryttain != "") {
        $budj_taulunrivit[$liitun][$kasiind][$try] = (isset($rivi[$excej])) ? trim($rivi[$excej]) : "";
      }
      else {
        $budj_taulunrivit[$liitun][$kasiind][] = (isset($rivi[$excej])) ? trim($rivi[$excej]) : "";
      }

      $insert_rivimaara++;
    }
  }

  if ($insert_rivimaara >= $maxrivimaara) {
    // Viedään suoraan kantaan
    $luvut = $budj_taulunrivit;
    $tee   = "TALLENNA_BUDJETTI_TARKISTA";
    $submit_button = "";

    echo "<br><font class='error'>".t("HUOM: Maksimirivimäärä ylittyi, rivejä ei näytetä ruudulla. Rivit tallennetaan suoraan tietokantaan")."!<br><br></font>";
  }
  else {
    echo "<font class='error'>".t("HUOM: Excel-tiedoston luvut eivät vielä tallennettu tietokantaan")."!<br>".t("Klikkaa")." '", t("Tallenna tavoiteluvut"), "' ".t("tallentaaksesi luvut")."!</font><br><br></font>";
  }

  $liitostunnukset = substr($liitostunnukset, 0, -1);
}

// Lisätään/Päivitetään budjetti, tarkastukset
if ($tee == "TALLENNA_BUDJETTI_TARKISTA") {

  // Oletetaan, että ei virheitä
  $tee = "TALLENNA_BUDJETTI";

  if (!isset($luvut) or count($luvut) == 0) {
    echo "<font class='error'>".t("Et syöttänyt yhtään lukua")."!<br><br></font>";
    $tee = "";
  }

  if (isset($poikkeus_haku) and $poikkeus_haku != "try" and $poikkeus_haku != "osasto" and $poikkeus_haku != "kummatkin") {
    echo "<font class='error'>".t("VIRHE: Tavoitelukujen tallennuksessa on tapahtunut virhe, keskeytetään prosessi")."!<br><br></font>";
    $tee = "";
  }
}


if (($tee == "TALLENNA_BUDJETTI" or $submit_button != "") and ($toim == "ASIAKAS" or $toim == "MYYJA") and isset($osastotryttain) and $osastotryttain != "") {
  if ($osastotryttain == "tuoteryhmittain") {
    $query = "SELECT try, group_concat(concat('\'',tuoteno,'\'')) tuotteet
              FROM tuote
              WHERE yhtio      = '$kukarow[yhtio]'
              and tuotetyyppi  in ('','R','K','M')
              and tuoteno     != '{$yhtiorow['ennakkomaksu_tuotenumero']}'
              AND status      != 'P'
              GROUP BY try";
    $result = pupe_query($query);
    $ostryntuotteet = array();

    while ($tryrow = mysql_fetch_assoc($result)) {
      $ostryntuotteet[$tryrow["try"]] = $tryrow["tuotteet"];
    }
  }
  elseif ($osastotryttain == "osastoittain") {
    $query = "SELECT osasto, group_concat(concat('\'',tuoteno,'\'')) tuotteet
              FROM tuote
              WHERE yhtio      = '$kukarow[yhtio]'
              and tuotetyyppi  in ('','R','K','M')
              and tuoteno     != '{$yhtiorow['ennakkomaksu_tuotenumero']}'
              AND status      != 'P'
              GROUP BY osasto";
    $result = pupe_query($query);
    $ostryntuotteet = array();

    while ($tryrow = mysql_fetch_assoc($result)) {
      $ostryntuotteet[$tryrow["osasto"]] = $tryrow["tuotteet"];
    }
  }
}

// Lisätään/Päivitetään budjetti
if ($tee == "TALLENNA_BUDJETTI") {

  // Jos halutaan, että syötetty arvo jaetaan tasan valitulle ryhmälle, niin lasketaan rajattujen kuukausien lukumäärä
  if ($budjetointi_taso == "summa_jaetaan") {

    $alkaakk = substr($kausi_alku, 0, 4).substr($kausi_alku, 5, 2);
    $loppuukk = substr($kausi_loppu, 0, 4).substr($kausi_loppu, 5, 2);

    $sql = "SELECT PERIOD_DIFF('{$loppuukk}','{$alkaakk}')+1 as jakaja";
    $result = pupe_query($sql);
    $rivi = mysql_fetch_assoc($result);

    $kuukausien_maara = $rivi["jakaja"];
  }

  // muuttuja "poikkeus" tulee aina kun ollaan valittu 'Anna Kokonaisbudjetti Osastolle Tai Tuoteryhmälle'
  // Normaalisti $luvut[] ensimmäisessä solussa tulee tuotteen tuoteno, nyt sieltä tulee TRY numero.
  // Haetaan kaikki sen tuoteryhmän tuotteet ja tehdään arrayksi.

  // otetaan $luvut talteen ja prosessoidaan ne "poikkeus-haarassa"
  $muunto_luvut = $luvut;

  if (isset($poikkeus) and $poikkeus == "totta") {

    unset($luvut); // poistetaan ja alustetaan sekä rakennetaan uudestaan.

    // Normaali tapaus missä on TRY tai OSASTO
    if ($poikkeus_haku == "try" or $poikkeus_haku == "osasto") {
      foreach ($muunto_luvut as $litunnus => $rivit) {

        $query = "SELECT DISTINCT tuote.tuoteno, tuote.nimitys, tuote.try, tuote.osasto
                  FROM tuote
                  WHERE tuote.yhtio  = '{$kukarow["yhtio"]}'
                  AND tuote.status  != 'P'
                  and tuote.{$poikkeus_haku} = '$litunnus' ";
        $result = pupe_query($query);

        while ($tuoterivi = mysql_fetch_assoc($result)) {
          foreach ($rivit as $kausi => $arvo) {
            $luvut[$tuoterivi["tuoteno"]][$kausi][0] = $arvo[0];
          }
        }
      }
    }

    // "kummatkin" tarkoittaa että tulee sekä TRY ja OSASTO
    if ($poikkeus_haku == "kummatkin") {
      foreach ($muunto_luvut as $litunnus => $rivit) {

        $palaset = explode(",", $litunnus);
        $osasto_tunnus   = trim($palaset[0]);
        $try_tunnus    = trim($palaset[1]);

        $query = "SELECT DISTINCT tuote.tuoteno, tuote.nimitys, tuote.try, tuote.osasto
                  FROM tuote
                  WHERE tuote.yhtio  = '{$kukarow["yhtio"]}'
                  AND tuote.status  != 'P'
                  and tuote.osasto   = '$osasto_tunnus'
                  and tuote.try      = '$try_tunnus'";
        $result = pupe_query($query);

        while ($tuoterivi = mysql_fetch_assoc($result)) {
          foreach ($rivit as $kausi => $arvo) {
            $luvut[$tuoterivi["tuoteno"]][$kausi][0] = $arvo[0];
          }
        }
      }
    }
  }

  $paiv  = 0;
  $pois  = 0;
  $lisaa = 0;

  // tätä käytetään kun laitetaan koko osastolle/try:lle yksi summa joka jaetaan per tuote per kk
  $sarakkeiden_lukumaara = count($luvut);

  foreach ($luvut as $liitostunnarit => $rivi) {
    foreach ($rivi as $kausi => $solut) {
      foreach ($solut as $osasto_try => $solu_orig) {

        $solu_orig = str_replace(",", ".", trim($solu_orig));

        $try   = "";
        $osasto  = "";

        if (isset($osastotryttain) and $osastotryttain == "tuoteryhmittain") {
          $try   = $osasto_try;
          $osasto  = "";
        }
        elseif (isset($osastotryttain) and $osastotryttain == "osastoittain") {
          $try   = "";
          $osasto  = $osasto_try;
        }
        else {
          $osasto_try = 0;
        }

        if ($solu_orig == '!' or is_numeric($solu_orig)) {

          if (($toim == "ASIAKAS" or $toim == "MYYJA" or $toim == "ASIAKASMYYJA") and $summabudjetti == "on") {
            $liitostunnarit_array = explode(",", $liitostunnarit);
          }
          else {
            $liitostunnarit_array = array($liitostunnarit);
          }

          foreach ($liitostunnarit_array as $liitostunnari) {

            $update_vai_insert = "";

            // Huutomerkillä poistetaan budjetti
            if ($solu_orig == "!") {
              $update_vai_insert = "DELETE";
            }
            else {
              $update_vai_insert = "INSERT/UPDATE";
            }

            // $solu_orig on käyttäjän syöttämä luku
            // $tall_summa on kantaan tallennettava summa
            // $tall_index on kantaan tallennettava indeksi-luku
            // $tall_maara on kantaan tallennettava kappalemäärä
            if ($budj_kohtelu != "indeksi" and count($liitostunnarit_array) > 1) {
              $solu = (float) round($solu_orig/count($liitostunnarit_array));
            }
            else {
              $solu = (float) $solu_orig;
            }

            $tall_summa = 0;
            $tall_index = 0;
            $tall_maara = 0;

            if ($budjetointi_taso == "summa_jaetaan") {
              // Jaetaan syötty luku kuukausien ja tuotteiden määrän mukaan
              if (isset($poikkeus) and $poikkeus == "totta") {
                $solu = round($solu / ($kuukausien_maara * $sarakkeiden_lukumaara), 2);
              }
              else {
                $solu = round($solu / $kuukausien_maara, 2);
              }
            }

            // Toimittaja ja asiakasbudjetti tehdään aina euroissa
            if ($toim == "TOIMITTAJA" or $toim == "MAA") {
              $tall_summa = $solu;
            }
            elseif ($toim == "ASIAKAS" or $toim == "MYYJA" or $toim == "ASIAKASMYYJA") {
              if ($budj_kohtelu == "euro") {
                // Syötetty arvo summa kenttään
                $tall_summa = round($solu, 2);
              }
              elseif ($budj_kohtelu == "indeksi") {
                if (!isset($tanvuodenennuste[$liitostunnari][$osasto_try])) {
                  list($null, $null, $tanvuodenennuste[$liitostunnari][$osasto_try], $null) = asiakkaanmyynti($liitostunnari, $try, $osasto, TRUE);
                }

                $tall_index = round($solu, 2);
                $tall_summa = $tanvuodenennuste[$liitostunnari][$osasto_try] > 0 ? round($tanvuodenennuste[$liitostunnari][$osasto_try]/12*$tall_index, 2): 0;

              }
              else {
                // Virheellinen kohtelu, ei tehdä mitään
                $update_vai_insert = "";
              }
            }
            // Tuotebudjetissa on muitakin vaihtoehtoja
            elseif ($toim == "TUOTE") {

              // Budjettiluvun voi syöttää eri tasoilla. budjetointitasoja on: kuukausittain, joka_kk_sama ja summa_jaetaan
              // Kuukausittain tarkoittaa, että jokaisen kauden arvo on syötetty erikseen
              // Joka_kk_sama tarkoittaa, että jokaiselle kaudelle on annettu sama arvo
              // Summa_jaetaan tarkoittaa, että syötetty arvo jaetaan rajattujen kuukausien

              // Kohtelu tarkoittaa, minkätyyppistä lukua syötetään. Kohteluita on: euro, maara ja indeksi
              if ($budj_kohtelu == "euro") {
                // Syötetty arvo summa kenttään
                $tall_summa = round($solu, 2);
              }
              elseif ($budj_kohtelu == "maara") {
                // Syötetty arvo määrä kenttään
                $tall_maara = round($solu, 2);
              }
              elseif ($budj_kohtelu == "indeksi") {
                // Syötetty arvo indeksi kenttään, lisäksi tulee hakea edellisen vuoden vastaavan kauden myynti/kulutus ja kertoa se indeksillä

                // Kausi on muotoa VVVVKK, tehdään siitä edellisen vuoden vastaavan kauden eka päivä muotoon VVVV-KK-PP
                $ed_kausi = (substr($kausi, 0, 4) -1).'-'.substr($kausi, 4, 2).'-01';

                list($myyntihistoriassa, $maarahistoriassa) = tuotteenmyynti($liitostunnari, $ed_kausi);

                $tall_index = round($solu, 2);
                $tall_maara = round($maarahistoriassa * $tall_index, 2);
                $tall_summa = round($myyntihistoriassa * $tall_index, 2);

                if ($tall_summa == 0 or $tall_maara == 0) {
                  echo "<font class='error'>$liitostunnari: ".t("Indeksi")." $tall_index";
                  if ($tall_summa == 0) echo " ".t("summa");
                  if ($tall_summa == 0 and $tall_maara == 0) echo " ".t("ja");
                  if ($tall_maara == 0) echo " ".t("määrä");
                  echo " ".t("jäi nollaksi")."!</font><br>";
                }
              }
              else {
                // Virheellinen kohtelu, ei tehdä mitään
                $update_vai_insert = "";
              }
            }
            // Virheellinen TOIM, ei tehdä mitään
            else {
              $update_vai_insert = "";
            }

            // Poistetaan tietue jos on syötetty huutomerkki
            if ($update_vai_insert == "DELETE") {
              $query = "DELETE FROM $budj_taulu
                        WHERE yhtio = '{$kukarow["yhtio"]}'
                        AND $budj_sarak  = '$liitostunnari'
                        AND kausi   = '$kausi'
                        AND try     = '$try'
                        AND osasto  = '$osasto'";
              $result = pupe_query($query, $GLOBALS["masterlink"]);
              $pois++;
            }
            elseif ($update_vai_insert != "") {
              $query = "INSERT INTO $budj_taulu SET
                        summa      = '$tall_summa',
                        maara      = '$tall_maara',
                        indeksi    = '$tall_index',
                        yhtio      = '{$kukarow["yhtio"]}',
                        kausi      = '$kausi',
                        $budj_sarak       = '$liitostunnari',
                        try        = '$try',
                        osasto     = '$osasto',
                        laatija    = '{$kukarow["kuka"]}',
                        luontiaika = now(),
                        muutospvm  = now(),
                        muuttaja   = '{$kukarow["kuka"]}'
                        ON DUPLICATE KEY UPDATE
                        summa      = '$tall_summa',
                        maara      = '$tall_maara',
                        indeksi    = '$tall_index',
                        muutospvm  = now(),
                        muuttaja   = '{$kukarow["kuka"]}'";
              $result = pupe_query($query, $GLOBALS["masterlink"]);
              $lisaa++;
            }
          }
        }
      }
      unset($solut);
    }
    unset($rivi);
  }

  echo "<font class='message'>".t("Päivitin/lisäsin")." $lisaa. ".t("Poistin")." $pois.</font><br /><br />";
  $tee = "";
}

// Käyttöliittymä
if ($tee == "") {

  // Muutparametrit toimittaja-/asiakashakua varten
  if ($muutparametrit != "") {
    foreach (explode("##", $muutparametrit) as $muutparametri) {
      list($a, $b) = explode("=", $muutparametri);

      if (strpos($a, "[") !== FALSE) {
        $i = substr($a, strpos($a, "[")+1, strpos($a, "]")-(strpos($a, "[")+1));
        $a = substr($a, 0, strpos($a, "["));

        ${$a}[$i] = $b;
      }
      else {
        ${$a} = $b;
      }
    }
  }

  // Toimittajahaku
  if ($toim == "TOIMITTAJA" and $ytunnus != '' and $toimittajaid == 0) {

    $muutparametrit = "";

    unset($_POST["toimittajaid"]);

    foreach ($_POST as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $a => $b) {
          $muutparametrit .= $key."[".$a."]=".$b."##";
        }
      }
      else {
        $muutparametrit .= $key."=".$value."##";
      }
    }

    require "inc/kevyt_toimittajahaku.inc";

    echo "<br />";

    if (trim($ytunnus) == '') {
      $submit_button = '';
    }
    else {
      $submit_button = 'OK';
    }
  }

  // Asiakashaku
  if ($toim == "ASIAKAS" and $ytunnus != '' and $asiakasid == 0) {

    $muutparametrit = "";

    unset($_POST["asiakasid"]);

    foreach ($_POST as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $a => $b) {
          $muutparametrit .= $key."[".$a."]=".$b."##";
        }
      }
      else {
        $muutparametrit .= $key."=".$value."##";
      }
    }

    require "inc/asiakashaku.inc";

    echo "<br />";

    if (trim($ytunnus) == '') {
      $submit_button = '';
    }
    else {
      $submit_button = 'OK';
    }
  }

  echo "<form method='post' enctype='multipart/form-data' autocomplete='off'>";
  echo "<input type='hidden' name='toim' value='$toim'>";

  echo "<table>";

  $query = "SELECT *
            FROM tilikaudet
            WHERE yhtio = '{$kukarow["yhtio"]}'
            ORDER BY tilikausi_alku DESC";
  $vresult = pupe_query($query);

  echo "<tr>";
  echo "<th>", t("Tilikausi"), "</th>";
  echo "<td><select name='tkausi'>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel = $tkausi == $vrow['tunnus'] ? ' selected' : '';
    echo "<option value = '$vrow[tunnus]'$sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"])."</option>";
  }

  echo "</select></td>";
  echo "</tr>";

  // Tavoitteiden aikaväli
  echo "<tr>";
  echo "<th>".t("Tavoitteiden aikaväli (kk-vuosi)")."</th>";
  echo "  <td>
      <input type='text' name='alkukk' value='$alkukk' size='3'>-
      <input type='text' name='alkuvv' value='$alkuvv' size='4'>
      &nbsp;
      <input type='text' name='loppukk' value='$loppukk' size='3'>-
      <input type='text' name='loppuvv' value='$loppuvv' size='4'>
      </td>";
  echo "</tr>";

  if ($toim == "TUOTE" or $toim == "ASIAKAS" or $toim == "MYYJA" or $toim == "ASIAKASMYYJA") {

    // indeksi vai euromäärä
    echo "<tr>";
    echo "<th>".t("Tavoitelukujen käsittelytyyppi")."</th>";
    echo "<td>";

    if ($budj_kohtelu == "indeksi") {
      $bkcheck = "SELECTED";
      $bkcheckb = "";
    }
    elseif ($budj_kohtelu == "maara") {
      $bkcheck = "";
      $bkcheckb = "SELECTED";
    }
    else {
      $bkcheck = "";
      $bkcheckb = "";
    }

    echo "<select name='budj_kohtelu' onchange='submit()';>";
    echo "<option value = 'euro'>".t("Tavoitteet syötetään euroilla")."</option>";
    echo "<option value = 'indeksi' $bkcheck>".t("Tavoitteet syötetään indekseillä")."</option>";
    if ($toim == "TUOTE") echo "<option value = 'maara' $bkcheckb>".t("Tavoitteet syötetään määrillä")."</option>";
    echo "</td>";
    echo "</tr>";
  }

  // Millä tasolla budjetti tehdään
  echo "<tr>";
  echo "<th>".t("Tavoiteluku")."</th>";
  echo "<td>";

  if ($budjetointi_taso == "joka_kk_sama") {
    $btcheck1 = "SELECTED";
    $btcheck2 = "";
  }
  elseif ($budjetointi_taso == "summa_jaetaan") {
    $btcheck1 = "";
    $btcheck2 = "SELECTED";
  }
  else {
    $btcheck1 = "";
    $btcheck2 = "";
  }

  echo "<select name='budjetointi_taso' onchange='submit()';>";
  echo "<option value = 'kuukausittain'>".t("Tavoiteluku syötetään per kuukausi")."</option>";
  echo "<option value = 'joka_kk_sama' $btcheck1>".t("Syötetty tavoiteluku tallennetaan jokaiselle kuukaudelle")."</option>";
  if ($budj_kohtelu != "indeksi") echo "<option value = 'summa_jaetaan' $btcheck2>".t("Syötetty tavoiteluku jaetaan kuukausille tasan")."</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  if ($toim == "TUOTE") {
    // Tuoteosasto tai ryhmätason budjetti.
    echo "<tr>";
    echo "<th>".t("Anna kokonaistavoitteet osastolle tai tuoteryhmälle")."</th>";

    $scheck = ($summabudjetti != "") ? "CHECKED": "";
    echo "<td><input type='checkbox' name='summabudjetti' $scheck></td>";
    echo "</tr>";

    echo "<tr><th>", t("Valitse tuote"), "</th>";
    echo "<td><input type='text' name='tuoteno' value='$tuoteno' /></td></tr>";

    echo "<tr><th>".t("tai rajaa tuotekategorialla")."</th><td>";

    $monivalintalaatikot = array('DYNAAMINEN_TUOTE', 'OSASTO', 'TRY');
    $monivalintalaatikot_normaali = array();

    require "tilauskasittely/monivalintalaatikot.inc";

    echo "</td></tr>";
  }

  if ($toim == "TOIMITTAJA") {
    echo "<tr>";
    echo "<th>", t("Valitse toimittaja"), "</th>";

    if ($toimittajaid > 0) {
      $query = "SELECT *
                FROM toimi
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND tunnus  = '$toimittajaid'";
      $result = pupe_query($query);
      $toimirow = mysql_fetch_assoc($result);

      echo "<td>{$toimirow["nimi"]} {$toimirow["nimitark"]}";
      echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'></td>";
    }
    else {
      echo "<td><input type='text' name='ytunnus' value='$ytunnus'></td>";
    }

    echo "</tr>";
  }

  if ($toim == "MYYJA") {
    // Tuoteosasto tai ryhmätason budjetti.
    echo "<tr>";
    echo "<th>", t("Anna kokonaistavoitteet valituille myyjille"), "</th>";

    $scheck = ($summabudjetti != "") ? "CHECKED": "";
    echo "<td><input type='checkbox' name='summabudjetti' {$scheck}></td>";
    echo "</tr>";

    echo "<tr><th>", t("Myyjä"), "</th><td>";

    $mulselprefix = "laskumyyja";
    $monivalintalaatikot = array('LASKUMYYJA');
    $monivalintalaatikot_normaali = array();

    require "tilauskasittely/monivalintalaatikot.inc";

    echo "</td></tr>";
  }

  if ($toim == "ASIAKASMYYJA") {
    echo "<tr>";
    echo "<th>", t("Anna kokonaistavoitteet valituille asiakasmyyjille"), "</th>";

    $scheck = ($summabudjetti != "") ? "CHECKED": "";
    echo "<td><input type='checkbox' name='summabudjetti' {$scheck}></td>";
    echo "</tr>";

    echo "<tr><th>", t("Asiakasmyyjä"), "</th><td>";

    $mulselprefix = "asiakasmyyja";
    $monivalintalaatikot = array('ASIAKASMYYJA');
    $monivalintalaatikot_normaali = array();

    require "tilauskasittely/monivalintalaatikot.inc";

    echo "</td></tr>";
  }

  if ($toim == "MAA") {
    // Tuoteosasto tai ryhmätason budjetti.
    echo "<tr>";
    echo "<th>", t("Anna kokonaistavoitteet valituille maille"), "</th>";

    $scheck = ($summabudjetti != "") ? "CHECKED": "";
    echo "<td><input type='checkbox' name='summabudjetti' {$scheck}></td>";
    echo "</tr>";

    echo "<tr><th>", t("Maa"), "</th><td>";

    $mulselprefix = "maa";
    $monivalintalaatikot = array('maa');
    $monivalintalaatikot_normaali = array();
    $piirra_otsikot = false;

    require "tilauskasittely/monivalintalaatikot.inc";

    echo "</td></tr>";
  }

  if ($toim == "ASIAKAS") {

    // Tuoteosasto tai ryhmätason budjetti.
    echo "<tr>";
    echo "<th>".t("Anna kokonaistavoitteet valituille asiakkaille")."</th>";

    $scheck = ($summabudjetti != "") ? "CHECKED": "";
    echo "<td><input type='checkbox' name='summabudjetti' $scheck></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>", t("Valitse asiakas"), "</th>";

    if ($asiakasid > 0) {
      $query = "SELECT *
                FROM asiakas
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND tunnus  = '$asiakasid'";
      $result = pupe_query($query);
      $asiakasrow = mysql_fetch_assoc($result);

      echo "<td>$asiakasrow[nimi] $asiakasrow[nimitark]<br>";
      echo "$asiakasrow[toim_nimi] $asiakasrow[toim_nimitark]";
      echo "<input type='hidden' name='asiakasid' value='$asiakasid' /></td>";
    }
    else {
      echo "<td><input type='text' name='ytunnus' value='$ytunnus' /></td>";
    }

    echo "</tr>";
    echo "<tr><th>".t("tai rajaa asiakaskategorialla")."</th><td>";

    $mulselprefix = "asiakas";
    $monivalintalaatikot = array('DYNAAMINEN_ASIAKAS', '<br>ASIAKASOSASTO', 'ASIAKASRYHMA', 'ASIAKASMYYJA', "<br>KUSTP", "KOHDE", "PROJEKTI");
    $monivalintalaatikot_normaali = array();

    require "tilauskasittely/monivalintalaatikot.inc";

    echo "</td></tr>";
  }

  $tcheck = ($onko_ilman_budjettia != "") ? "CHECKED" : "";

  echo "<tr>";
  echo "<th>".t("Näytä vain rivit, joilla ei ole tavoitetta")."</th>";
  echo "<td><input type='checkbox' name='onko_ilman_budjettia' $tcheck></td>";
  echo "</tr>";

  if ($toim == "ASIAKAS" or $toim == "TOIMITTAJA" or $toim == "MYYJA" or $toim == "ASIAKASMYYJA") {

    echo "<tr><th>", t("Tavoitetaso"), "</th><td>";

    $btcheck1 = "";
    $btcheck2 = "";

    if ($osastotryttain == "tuoteryhmittain") {
      $btcheck1 = "SELECTED";
      $btcheck2 = "";
    }
    elseif ($osastotryttain == "osastoittain") {
      $btcheck1 = "";
      $btcheck2 = "SELECTED";
    }

    if ($toim == "MYYJA") {
      $toim_selite = t("Myyjän");
    }
    elseif ($toim == "TOIMITTAJA") {
      $toim_selite = t("Toimittajan");
    }
    elseif ($toim == "ASIAKASMYYJA") {
      $toim_selite = t("Asiakasmyyjän");
    }
    else {
      $toim_selite = t("Asiakkaan");
    }

    echo "<select name='osastotryttain' onchange='submit()';>";
    echo "<option value = ''>", t("{$toim_selite} kokonaistavoite"), "</option>";
    echo "<option value = 'tuoteryhmittain' {$btcheck1}>", t("{$toim_selite} tuoteryhmäkohtainen tavoite"), "</option>";
    echo "<option value = 'osastoittain' {$btcheck2}>", t("{$toim_selite} osastokohtainen tavoite"), "</option>";
    echo "</select>";

    echo "</td></tr>";
  }

  if ($toim == "ASIAKAS" or $toim == "MYYJA" or $toim == "ASIAKASMYYJA") {
    // Keroin jolla interpoloidaan asiakkaan kuluvan vuoden myynnit koko vuoden myynneiksi
    echo "<tr><th>", t("Myyntiennustekerroin, kuluva vuosi"), "</th><td>";
    echo "<input type='text' name='myyntiennustekerroin' value='$myyntiennustekerroin' size='5'>";

    $scheck = ($naytamyyntiennuste != "") ? "CHECKED": "";
    echo "&nbsp;&nbsp;&nbsp;".t("Näytä myyntiennusteet").": <input type='checkbox' name='naytamyyntiennuste' $scheck>";


    echo "</td></tr>";

    echo "<tr><th>", t("Myyntitavoitekerroin"), "</th><td>";
    echo "<input type='text' name='myyntitavoitekerroin' value='$myyntitavoitekerroin' size='5'>";
    echo "&nbsp;&nbsp;&nbsp;".t("Myyntitavoitekertoimen avulla luodaan automaattisesti tavoiteluku kuluvan vuoden myyntiennusteesta")."</td></tr>";
  }

  echo "<tr>";
  echo "<th>".t("Lue tavoiteluvut tiedostosta")."</th>";
  echo "<td><input type='file' name='userfile'></td>";
  echo "</tr>";

  echo "</table><br>";

  echo t("Tavoiteluvun voi poistaa huutomerkillä (!)"), "<br />";
  echo "<br />";

  echo "<input type='submit' name='submit_button' id='submit_button' value='", t("Hae tavoitteet"), "' /><br>";
  echo "</form>";
}

// Oikellisuustarkistukset kun ollaan submitattu käyttöliittymä
if ($submit_button != "") {

  // Oletetaan, että ei tule virheitä, nollataan $tee jos tulee virheitä
  $tee = "AJA_RAPORTTI";

  if ($alkukk != "" or $alkuvv != "" or $loppukk != "" or $loppuvv != "") {

    $alkukk = (int) $alkukk;
    $alkuvv = (int) $alkuvv;

    $loppukk = (int) $loppukk;
    $loppuvv = (int) $loppuvv;

    if (!checkdate($alkukk, 1, $alkuvv) or !checkdate($loppukk, 1, $loppuvv)) {
      echo "<font class='error'>".t("Virheellinen kausi")."!</font><br>";
      $tee = "";
    }

    $tilikaudetrow["tilikausi_alku"]  = date("Y-m-d", mktime(0, 0, 0, $alkukk, 1, $alkuvv));
    $tilikaudetrow["tilikausi_loppu"] = date("Y-m-d", mktime(0, 0, 0, $loppukk+1, 0, $loppuvv));

    if ($tilikaudetrow["tilikausi_alku"] > $tilikaudetrow["tilikausi_loppu"]) {
      echo "<font class='error'>".t("Virheellinen kausi")."!</font><br>";
      $tee = "";
    }
  }
  else {
    $query = "SELECT *
              FROM tilikaudet
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$tkausi'";
    $vresult = pupe_query($query);

    if (mysql_num_rows($vresult) == 1) {
      $tilikaudetrow = mysql_fetch_array($vresult);
    }
    else {
      echo "<font class='error'>".t("Virheellinen kausi")."!</font><br>";
      $tee = "";
    }
  }

  if ($toim == "TUOTE" and $tuoteno == "" and $lisa_dynaaminen["tuote"] == "" and $lisa == "" and $lisa_parametri == "" and !is_uploaded_file($_FILES['userfile']['tmp_name'])) {
    echo "<font class='error'>".t("On valittava tuote tai tuotekategoria")."! $lisa tai $lisa_parametri</font><br>";
    $tee = "";
  }

  if ($toim == "ASIAKAS" and $asiakasid == "" and $lisa_dynaaminen["asiakas"] == "" and $lisa == "" and $lisa_parametri == "" and !is_uploaded_file($_FILES['userfile']['tmp_name'])) {
    echo "<font class='error'>".t("On valittava asiakas tai asiakaskategoria")."!";
    $tee = "";
  }

  if ($toim == "TOIMITTAJA" and $toimittajaid == "" and !is_uploaded_file($_FILES['userfile']['tmp_name'])) {
    echo "<font class='error'>".t("On valittava toimittaja")."!</font><br>";
    $tee = "";
  }

  if ($toim == "MYYJA" and $lisa_dynaaminen["laskumyyja"] == "" and $lisa == "" and $lisa_parametri == "" and !is_uploaded_file($_FILES['userfile']['tmp_name'])) {
    echo "<font class='error'>".t("On valittava myyjä")."!";
    $tee = "";
  }

  if ($toim == "ASIAKASMYYJA" and $lisa_dynaaminen["asiakasmyyja"] == "" and $lisa == "" and $lisa_parametri == "" and !is_uploaded_file($_FILES['userfile']['tmp_name'])) {
    echo "<font class='error'>".t("On valittava asiakasmyyjä")."!";
    $tee = "";
  }

  if ($toim == "MAA" and $lisa_dynaaminen["maa"] == "" and $lisa == "" and $lisa_parametri == "" and !is_uploaded_file($_FILES['userfile']['tmp_name'])) {
    echo "<font class='error'>".t("On valittava maa")."!";
    $tee = "";
  }

  // Kokonaisbudjetti-tarkastukset
  if (($toim == "TUOTE" or $toim == "ASIAKAS" or $toim == "MYYJA" or $toim == "ASIAKASMYYJA") and $summabudjetti == "on") {
    if ($budj_kohtelu == "indeksi" and $budjetointi_taso != "joka_kk_sama") {
      echo "<font class='error'>".t("VIRHE: Kokonaistavoitteet voi syöttää indeksiluvulla vain valitsemalla jokaiselle kuukaudelle saman arvon!")."</font><br>";
      $tee = "";
    }
    if ($budjetointi_taso == "kuukausittain") {
      echo "<font class='error'>".t("VIRHE: Kokonaistavoitteet ei voida syöttää kuukausittain aikavälillä!")."</font><br>";
      $tee = "";
    }
  }

  if ($budj_kohtelu == "indeksi" and $budjetointi_taso == "summa_jaetaan") {
    echo "<font class='error'>".t("VIRHE: Et voi jakaa indeksilukua kuukausittain!")."</font><br>";
    $tee = "";
  }

  if (isset($mul_osasto) and isset($mul_try) and count($mul_osasto) > 1 and count($mul_try) >= 1) {
    echo "<font class='error'>".t("VIRHE: Et voi valita useita osastoja ja tuoteryhmiä kerrallaan")."</font>";
    $tee = "";
  }
}

// Ajetaan raportti
if ($tee == "AJA_RAPORTTI") {

  include 'inc/pupeExcel.inc';

  $worksheet    = new pupeExcel();
  $format_bold = array("bold" => TRUE);

  $excelrivi    = 0;
  $excelsarake  = 0;
  $haen      = "";
  $passaan    = "";
  $selectlisa    = "";
  $grouppaus    = "";

  if ($toim == "TUOTE") {
    $worksheet->write($excelrivi, $excelsarake, t("Tuote"), $format_bold);
    $excelsarake++;
  }
  elseif ($toim == "TOIMITTAJA") {
    $worksheet->write($excelrivi, $excelsarake, t("Toimittajan tunnus"), $format_bold);
    $excelsarake++;
  }
  elseif ($toim == "ASIAKAS") {
    $worksheet->write($excelrivi, $excelsarake, t("Asiakkaan tunnus"), $format_bold);
    $excelsarake++;
  }
  elseif ($toim == "MYYJA") {
    $worksheet->write($excelrivi, $excelsarake, t("Myyjän tunnus"), $format_bold);
    $excelsarake++;
  }
  elseif ($toim == "ASIAKASMYYJA") {
    $worksheet->write($excelrivi, $excelsarake, t("Asiakasmyyjän tunnus"), $format_bold);
    $excelsarake++;
  }
  elseif ($toim == "MAA") {
    $worksheet->write($excelrivi, $excelsarake, t("Maan tunnus"), $format_bold);
    $excelsarake++;
  }

  if ($toim == "ASIAKAS" or $toim == "TOIMITTAJA") {
    $worksheet->write($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
    $excelsarake++;
  }

  if ($toim == "ASIAKAS") {
    $worksheet->write($excelrivi, $excelsarake, t("Asiakasnro"), $format_bold);
    $excelsarake++;
  }

  $worksheet->write($excelrivi, $excelsarake, t("Nimi"), $format_bold);
  $excelsarake++;

  if ($toim == "TOIMITTAJA" and $toimittajaid > 0) {
    $lisa .= " and toimi.tunnus = '$toimittajaid' ";
  }

  if ($toim == "ASIAKAS" and $asiakasid > 0) {
    $lisa .= " and asiakas.tunnus = '$asiakasid' ";
  }

  if ($toim == "TUOTE" and $tuoteno != "") {
    $lisa .= " and tuote.tuoteno = '$tuoteno' ";
  }

  if ($toim == "MAA") {
    $grouppaus = " group by 1";
  }

  if (isset($liitostunnukset) and $liitostunnukset != "") {
    // Excelistä tulleet asiakkaat ylikirjaavaat muut rajaukset
    if ($toim == "TUOTE") {
      $lisa = " and tuote.tuoteno in ($liitostunnukset) ";
    }
    elseif ($toim == "TOIMITTAJA") {
      $lisa = " and toimi.tunnus in ($liitostunnukset) ";
    }
    elseif ($toim == "ASIAKAS") {
      $lisa = " and asiakas.tunnus in ($liitostunnukset) ";
    }
    elseif ($toim == "MYYJA") {
      $lisa = " and kuka.tunnus in ({$liitostunnukset}) ";
    }
    elseif ($toim == "ASIAKASMYYJA") {
      $lisa = " and kuka.myyja in ({$liitostunnukset}) ";
    }
    elseif ($toim == "MAA") {
      $lisa = " maat.tunnus in ({$liitostunnukset}) ";
    }

    $lisa_parametri  = "";
    $lisa_dynaaminen = array("tuote" => "", "asiakas" => "");
  }

  if ($toim == "TUOTE" and $summabudjetti == "on") {
    // mikäli ollaan valittu "Anna Kokonaisbudjetti Osastolle Tai Tuoteryhmälle"
    // Mikäli luot lisää taikka muokkaat "haen" ja "passaan" muuttujia, niin korjaa myös riville 1170 alken piirtoihin.
    // sekä Funktiolle "piirra_budj_rivi"

    $selectlisa = " , avainsana.selitetark";

    if (strpos($lisa, 'tuote.osasto') != 0 and strpos($lisa, 'tuote.try') === FALSE) {
      $grouppaus = " group by tuote.osasto";
      $haen = "osasto";
      $passaan = "yksi";
    }
    elseif (strpos($lisa, 'tuote.osasto') != 0 and strpos($lisa, 'tuote.try') != 0) {
      $grouppaus = " group by tuote.try";
      $haen = "try";
      $passaan = "kaksi";
    }
    else {
      $grouppaus = " group by tuote.try";
      $haen = "try";
      $passaan = "yksi";
    }

    $joinlisa = " JOIN avainsana on (tuote.yhtio = avainsana.yhtio and tuote.{$haen} = avainsana.selite and avainsana.laji='{$haen}' and avainsana.kieli='{$yhtiorow["kieli"]}') ";
  }

  if ($toim == "TUOTE") {
    $query = "SELECT DISTINCT tuote.tuoteno, tuote.nimitys, tuote.try, tuote.osasto $selectlisa
              FROM tuote
              {$lisa_parametri}
              {$joinlisa}
              WHERE tuote.yhtio  = '{$kukarow['yhtio']}'
              AND tuote.status  != 'P'
              {$lisa}
              {$grouppaus}";
  }
  elseif ($toim == "TOIMITTAJA") {
    $query = "SELECT DISTINCT toimi.tunnus toimittajan_tunnus,
              toimi.ytunnus,
              toimi.ytunnus asiakasnro,
              toimi.nimi,
              toimi.nimitark,
              '' toim_nimi, # Nämä vaan sen takia, ettei tule noticeja tablen piirtämissessä (toimittaja query pitää olla sama kun asiakasquery alla)
              '' toim_nimitark
              FROM toimi
              WHERE toimi.yhtio = '{$kukarow["yhtio"]}'
              $lisa";
  }
  elseif ($toim == "MAA") {

    $where_maa_lisa = !empty($lisa) ? "WHERE {$lisa}" : "";

    $query = "SELECT DISTINCT koodi, nimi, MIN(tunnus) maa_id
              FROM maat
              {$where_maa_lisa}
              {$grouppaus}";
  }
  elseif ($toim == "ASIAKAS") {

    if ($summabudjetti != "") {
      $query = "SELECT group_concat(asiakas.tunnus) asiakkaan_tunnus,
                concat('***** ', count(asiakas.tunnus),' asiakasta *****') ytunnus
                FROM asiakas
                WHERE asiakas.yhtio  = '{$kukarow["yhtio"]}'
                and asiakas.laji    != 'P'
                $lisa";
    }
    else {
      $query = "SELECT DISTINCT asiakas.tunnus asiakkaan_tunnus,
                asiakas.ytunnus,
                asiakas.asiakasnro,
                asiakas.nimi,
                asiakas.nimitark,
                IF(STRCMP(TRIM(CONCAT(asiakas.toim_nimi, ' ', asiakas.toim_nimitark)), TRIM(CONCAT(asiakas.nimi, ' ', asiakas.nimitark))) != 0, asiakas.toim_nimi, '') toim_nimi,
                IF(STRCMP(TRIM(CONCAT(asiakas.toim_nimi, ' ', asiakas.toim_nimitark)), TRIM(CONCAT(asiakas.nimi, ' ', asiakas.nimitark))) != 0, asiakas.toim_nimitark, '') toim_nimitark
                FROM asiakas
                WHERE asiakas.yhtio  = '{$kukarow["yhtio"]}'
                and asiakas.laji    != 'P'
                $lisa";
    }
  }
  elseif ($toim == "MYYJA") {

    $query = "SELECT kuka.tunnus myyjan_tunnus,
              kuka.nimi,
              kuka.myyja
              FROM kuka
              WHERE kuka.yhtio  = '{$kukarow['yhtio']}'
              AND kuka.extranet = ''
              AND kuka.myyja    > 0
              {$lisa}";

  }
  elseif ($toim == "ASIAKASMYYJA") {
    $query = "SELECT DISTINCT(kuka.myyja) asiakasmyyjan_tunnus,
              kuka.nimi,
              kuka.myyja
              FROM kuka
              JOIN asiakas ON (asiakas.yhtio = kuka.yhtio AND asiakas.myyjanro = kuka.myyja)
              WHERE kuka.yhtio     = '{$kukarow['yhtio']}'
              AND kuka.extranet    = ''
              AND kuka.myyja       > 0
              AND asiakas.myyjanro > 0
              {$lisa}";
  }

  $result = pupe_query($query);

  echo "<br><font class='message'>".t("Tavoiteluvut")."</font><br>";
  echo "<hr />";

  if (isset($osastotryttain) and $osastotryttain == "tuoteryhmittain") {
    // Haetaan tuoteryhmät
    $res = t_avainsana("TRY");
    $rivimaara = mysql_num_rows($res)*mysql_num_rows($result);
  }
  elseif (isset($osastotryttain) and $osastotryttain == "osastoittain") {
    // Haetaan osastot
    $res = t_avainsana("OSASTO");
    $rivimaara = mysql_num_rows($res)*mysql_num_rows($result);
  }
  else {
    $rivimaara = mysql_num_rows($result);
  }

  if ($rivimaara >= $maxrivimaara) {
    echo "<br><font class='error'>".t("HUOM: Maksimirivimäärä ylittyi, rivejä ei näytetä ruudulla. Tallenna Excel-tiedosto")."!</font><br><br>";
  }
  else {
    echo "<form method='post' enctype='multipart/form-data' autocomplete='off'>";

    // Laitetaan monivalintalaatikoiden valinnat myös mukaan
    foreach ($_REQUEST as $a => $null) {
      if (substr($a, 0, 4) == "mul_") {
        foreach (${$a} as $val) {
          echo "<input type='hidden' name='{$a}[]' value = '$val'>";
        }
      }
    }

    echo "<input type='hidden' name='tkausi' value = '$tkausi'>";
    echo "<input type='hidden' name='alkukk' value = '$alkukk'>";
    echo "<input type='hidden' name='alkuvv' value = '$alkuvv'>";
    echo "<input type='hidden' name='loppukk' value = '$loppukk'>";
    echo "<input type='hidden' name='loppuvv' value = '$loppuvv'>";
    echo "<input type='hidden' name='tuoteno' value = '$tuoteno'>";
    echo "<input type='hidden' name='budj_kohtelu' value='$budj_kohtelu'>";
    echo "<input type='hidden' name='budjetointi_taso' value='$budjetointi_taso'>";
    echo "<input type='hidden' name='kausi_alku' value='{$tilikaudetrow["tilikausi_alku"]}'>";
    echo "<input type='hidden' name='kausi_loppu' value='{$tilikaudetrow["tilikausi_loppu"]}'>";
    echo "<input type='hidden' name='onko_ilman_budjettia' value='$onko_ilman_budjettia'>";
    echo "<input type='hidden' name='summabudjetti' value='$summabudjetti'>";
    echo "<input type='hidden' name='osastotryttain' value='$osastotryttain'>";
    echo "<input type='hidden' name='tee' value='TALLENNA_BUDJETTI_TARKISTA'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='myyntiennustekerroin' value='$myyntiennustekerroin'>";
    echo "<input type='hidden' name='naytamyyntiennuste' value='$naytamyyntiennuste'>";

    echo "<input type='submit' name='tallennus' id='tallennus' value='", t("Tallenna tavoiteluvut"), "' />";
    echo "<br><br>";

    echo "<table>";
  }

  if ($toim == "TUOTE") {
    if ($grouppaus != "") {
      // näytetään joko tuoteryhmä tai tuoteosasto
      if ($haen == "try") {
        if ($rivimaara < $maxrivimaara) echo "<tr><th>", t("Tuoteryhmä"), "</th>";
      }
      else {
        if ($rivimaara < $maxrivimaara) echo "<tr><th>", t("Tuoteosasto"), "</th>";
      }
    }
    else {
      if ($rivimaara < $maxrivimaara) echo "<tr><th>", t("Tuote"), "</th>";
    }
  }
  elseif ($toim == "TOIMITTAJA") {
    if ($rivimaara < $maxrivimaara) echo "<tr><th>", t("Toimittaja"), "</th>";
  }
  elseif ($toim == "MAA") {
    if ($rivimaara < $maxrivimaara) echo "<tr><th>", t("Maa"), "</th>";
  }
  elseif ($toim == "ASIAKAS") {
    if ($rivimaara < $maxrivimaara) echo "<tr><th>", t("Asiakas"), "</th>";
  }
  elseif ($toim == "MYYJA") {
    if ($rivimaara < $maxrivimaara) echo "<tr><th>", t("Myyjä"), "</th>";
  }
  elseif ($toim == "ASIAKASMYYJA") {
    if ($rivimaara < $maxrivimaara) echo "<tr><th>", t("Asiakasmyyjä"), "</th>";
  }

  if (isset($osastotryttain) and $osastotryttain == "tuoteryhmittain") {
    if ($rivimaara < $maxrivimaara) echo "<th>", t("Tuoteryhmä"), "</th>";

    $worksheet->write($excelrivi, $excelsarake, t("Tuoteryhmä"), $format_bold);
    $excelsarake++;

  }
  elseif (isset($osastotryttain) and $osastotryttain == "osastoittain") {
    if ($rivimaara < $maxrivimaara) echo "<th>", t("Osasto"), "</th>";

    $worksheet->write($excelrivi, $excelsarake, t("Osasto"), $format_bold);
    $excelsarake++;
  }

  if ($toim == "ASIAKAS" or $toim == "MYYJA" or $toim == "ASIAKASMYYJA") {
    if ($naytamyyntiennuste != "") {
      if ($rivimaara < $maxrivimaara) {
        echo "<th>", t("Myynti"), " ", (date('Y')-1), "</th>";
        echo "<th>", t("Myynti"), " ", date('Y'), "-01 - ", substr($edellinen_kuukausi_loppu, 0, 7), "</th>";
        echo "<th>", t("Myyntiennuste"), " ", date('Y'), "</th>";
      }

      $worksheet->write($excelrivi, $excelsarake, t("Myynti")." ".(date('Y')-1), $format_bold);
      $excelsarake++;

      $worksheet->write($excelrivi, $excelsarake, t("Myynti")." ".date('Y')."-01 - ".substr($edellinen_kuukausi_loppu, 0, 7), $format_bold);
      $excelsarake++;

      $worksheet->write($excelrivi, $excelsarake, t("Myynti")." ".date('Y'), $format_bold);
      $excelsarake++;
    }
  }

  $raja     = '0000-00';
  $alkuraja  = substr($tilikaudetrow['tilikausi_alku'], 0, 4)."-".substr($tilikaudetrow['tilikausi_alku'], 5, 2);
  $rajataulu   = array();
  $sarakkeet  = 0;

  while ($raja < substr($tilikaudetrow['tilikausi_loppu'], 0, 7)) {

    $vuosi   = substr($tilikaudetrow['tilikausi_alku'], 0, 4);
    $kk   = substr($tilikaudetrow['tilikausi_alku'], 5, 2);
    $kk   += $sarakkeet;

    if ($kk > 12) {
      $vuosi++;
      $kk -= 12;
    }

    if ($kk < 10) $kk = '0'.$kk;

    $rajataulu[$sarakkeet] = $vuosi.$kk;
    $sarakkeet++;

    $raja = $vuosi."-".$kk;
    $aikavalinkaudet[] = $vuosi."".$kk;

    if (($budjetointi_taso == "joka_kk_sama" or $budjetointi_taso == "summa_jaetaan") and $sarakkeet == 1) {
      // näytetään vain 1 sarake jossa on valitun/syötetyn aikakauden
      $vuosi2  = substr($tilikaudetrow['tilikausi_loppu'], 0, 4);
      $kk2  = substr($tilikaudetrow['tilikausi_loppu'], 5, 2);
      echo "<th>".$raja." / ".$vuosi2 ."-".$kk2."</th>";
    }
    elseif ($budjetointi_taso == "joka_kk_sama" or $budjetointi_taso == "summa_jaetaan") {
      // en piirrä turhaan otsikkoja
    }
    else {
      if ($rivimaara < $maxrivimaara) echo "<th>$raja</th>";
    }

    $worksheet->write($excelrivi, $excelsarake, $raja, $format_bold);
    $excelsarake++;

    if ($sarakkeet > 24) {
      echo "<font class='error'>".t("VIRHE: Tavoitelukuja ei voi syöttää yli 2 vuoden aikavälille")."!</font><br>";
      die();
    }
  }

  if ($rivimaara < $maxrivimaara) echo "</tr>";

  $excelrivi++;
  $xx = 0;

  // tätä tarvitaan ohituksessa että menee oikein.
  $ohituksen_alkuperaiset_sarakkeet = $sarakkeet;

  if ($budjetointi_taso == "joka_kk_sama" or $budjetointi_taso == "summa_jaetaan") {
    if (isset($osastotryttain) and ($osastotryttain == "tuoteryhmittain" or $osastotryttain == "osastoittain")) {
      while ($row = mysql_fetch_assoc($result)) {
        while ($ostryrow = mysql_fetch_assoc($res)) {
          piirra_budj_rivi($row, $ostryrow, 'OHITA', $ohituksen_alkuperaiset_sarakkeet);
        }

        mysql_data_seek($res, 0);
      }
    }
    else {
      while ($row = mysql_fetch_assoc($result)) {
        piirra_budj_rivi($row, '', 'OHITA', $ohituksen_alkuperaiset_sarakkeet);
      }
    }
  }
  elseif (isset($onko_ilman_budjettia) and $onko_ilman_budjettia != "") {
    // Mikäli ollaan valittu käyttöliittymästä "Näytä Vain Ne Tuotteet Joilla Ei Ole Budjettia"
    // niin ensin haetaan tuotteet ja kausi, luodaan array,
    // sen jälkeen verrataan $row:n tuoteno luotuun arrayseen. Loopataan kaudet läpi.
    // Mikäli löytyy yksikin kauden pätkä missä ei ole arvoa tuotteelle, niin piirretään
    // Muussa tapauksessa mikäli tuotteella on arvot, niin ei piirretä.

    $bquery = "SELECT distinct tuoteno, kausi
               FROM budjetti_tuote
               WHERE yhtio = '{$kukarow["yhtio"]}'";
    $bres = pupe_query($bquery);

    while ($brivi = mysql_fetch_assoc($bres)) {
      $bud[$brivi["tuoteno"]][$brivi["kausi"]] = $brivi["kausi"];
    }

    while ($row = mysql_fetch_assoc($result)) {

      if (!isset($bud[$row['tuoteno']])) {
        piirra_budj_rivi($row);
      }
      elseif (isset($bud[$row['tuoteno']])) {
        foreach ($aikavalinkaudet as $aikavali) {
          if (!isset($bud[$row['tuoteno']][$aikavali]) or $bud[$row['tuoteno']][$aikavali] == "") {
            piirra_budj_rivi($row);
            break;
          }
        }
      }
    }
  }
  else {
    if (isset($osastotryttain) and ($osastotryttain == "tuoteryhmittain" or $osastotryttain == "osastoittain")) {
      while ($row = mysql_fetch_assoc($result)) {
        while ($ostryrow = mysql_fetch_assoc($res)) {
          piirra_budj_rivi($row, $ostryrow);
        }

        mysql_data_seek($res, 0);
      }
    }
    else {
      while ($row = mysql_fetch_assoc($result)) {
        piirra_budj_rivi($row);
      }
    }
  }

  $excelnimi = $worksheet->close();

  if ($rivimaara < $maxrivimaara) {
    echo "</table>";
    echo "<br><input type='submit' name='tallenna_budjetti' id='tallenna_budjetti' value='", t("Tallenna tavoiteluvut"), "' />";
    echo "</form>";
  }

  echo "<br><br><font class='message'>".t("Tavoiteluvut Excel muodossa")."</font><br>";

  echo "<hr>";

  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='Tavoitematriisi_$toim.xlsx'>";
  echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
  echo "<input type='submit' value='", t("Hae tiedosto"), "'>";
  echo "</form>";
  echo "<br><br>";
}

require "inc/footer.inc";
