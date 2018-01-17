<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

echo "<font class='head'>".t("Verottomat korvaukset")."</font><hr><br>";

echo "<form method='post'>";
echo "<input type='hidden' name='tee' value ='NAYTA'>";

echo "<table>";
echo "<tr>";
echo "<th>".t("Valitse vuosi")."</th>";
echo "<td>";

$sel = array();
$sel[$vv] = "SELECTED";

if (!isset($vv)) $vv = date("Y");

echo "<select name='vv'>";
for ($i = date("Y"); $i >= date("Y")-4; $i--) {
  echo "<option value='$i' $sel[$i]>$i</option>";
}
echo "</select>";
echo "</td>";

echo "<td class='back'><input type='submit' value='".t("Näytä")."'></td>";
echo "</tr>";

echo "</table>";

echo "</form>";
echo "<br>";

if ($tee == "NAYTA") {

  $query = "SELECT
            toimi.tunnus,
            toimi.ytunnus,
            if(kuka.nimi IS NULL or toimi.tyyppi = 'P', concat('~POISTETTU ', toimi.nimi), kuka.nimi) nimi,
            tuote.kuvaus,
            avg(tilausrivi.hinta) hinta,
            sum(tilausrivi.kpl) kpl,
            sum(tilausrivi.rivihinta) yhteensa
            FROM lasku
            JOIN toimi on (toimi.yhtio = lasku.yhtio and toimi.tunnus = lasku.liitostunnus)
            LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio and kuka.kuka = toimi.nimi)
            JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
            JOIN tuote ON (tuote.yhtio = lasku.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuotetyyppi IN ('A', 'B') and tuote.kuvaus in ('50', '56'))
            WHERE lasku.yhtio      = '$kukarow[yhtio]'
            AND lasku.tila         = 'Y'
            AND lasku.tilaustyyppi = 'M'
            AND lasku.tapvm        >= '$vv-01-01'
            AND lasku.tapvm        <= '$vv-12-31'
            GROUP BY 1,2,3,4
            ORDER BY nimi, kuvaus";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<table>";

    echo "<tr>";
    echo "<th>".t("Kuka")."</th>";
    echo "<th>".t("Verokodi")." / ".t("Korvaus")."</th>";
    echo "<th>".t("Kappaletta")."</th>";
    echo "<th>".t("Hinta")."</th>";
    echo "<th>".t("Yhteensä")."</th>";
    echo "</tr>";

    $ednimi    = "";
    $summat    = array();
    $kappaleet = array();
    $vspserie  = array();

    while ($row = mysql_fetch_assoc($result)) {

      if ($row["kuvaus"] == '50') {
        $kuvaus = t("Päivärahat ja ateriakorvaukset");
      }
      else {
        $kuvaus = t("Verovapaa kilometrikorvaus");
      }

      if ($ednimi == "" or $ednimi != $row["nimi"]) {
        $nimi = $row["nimi"];

        $vspserie[$row["ytunnus"]]["nimi"] = trim($row["nimi"]);

        if ($ednimi != "") {
          echo "<tr><td class='back' colspan='5'></td></tr>";
        }
      }
      else {
        $nimi = "";
      }

      echo "<tr class='aktiivi'>";

      if ($nimi != "") {
        echo "<td>$nimi</td>";

        $vspserie[$row["ytunnus"]]["paivarahat"]       = 0.00;
        $vspserie[$row["ytunnus"]]["kotimaanpuolipaivat"] = 0;
        $vspserie[$row["ytunnus"]]["kotimaanpaivat"]     = 0;
        $vspserie[$row["ytunnus"]]["ulkomaanpaivat"]      = 0;
        $vspserie[$row["ytunnus"]]["kilsat"]         = 0;
        $vspserie[$row["ytunnus"]]["kilsat_raha"]       = 0.00;
      }
      else {
        echo "<td class='back'></td>";
      }

      echo "<td>$row[kuvaus] $kuvaus</td>";
      echo "<td align='right'>".number_format($row["kpl"], 0, ',', ' ')."</td>";
      echo "<td align='right'>".number_format($row["hinta"], 2, ',', ' ')."</td>";
      echo "<td align='right'>".number_format($row["yhteensa"], 2, ',', ' ')."</td>";
      echo "</tr>";

      if ($row['kuvaus'] == 50) {
        $vspserie[$row["ytunnus"]]["paivarahat"] = $row["yhteensa"];
        $vspserie[$row["ytunnus"]]["kotimaanpuolipaivat"] = 0;
        $vspserie[$row["ytunnus"]]["kotimaanpaivat"] = 0;
        $vspserie[$row["ytunnus"]]["ulkomaanpaivat"] = 0;
      }

      if ($row['kuvaus'] == 56) {
        $vspserie[$row["ytunnus"]]["kilsat"] = $row["kpl"];
        $vspserie[$row["ytunnus"]]["kilsat_raha"] = $row["yhteensa"];
      }

      // var:t otettiin käyttöön vasta 2013
      if ($vv >= 2013) {
        $varlisa = "tilausrivi.var";
      }
      else {
        $varlisa = "''";
      }

      // erittely
      $query = "SELECT tilausrivi.tuoteno,
                tilausrivi.nimitys,
                tuote.vienti,
                {$varlisa} var,
                avg(tilausrivi.hinta) hinta,
                sum(tilausrivi.kpl) kpl,
                sum(tilausrivi.rivihinta) yhteensa
                FROM lasku
                JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
                JOIN tuote ON (tuote.yhtio = lasku.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuotetyyppi IN ('A','B') and tuote.kuvaus = '$row[kuvaus]')
                LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio and kuka.kuka = lasku.toim_ovttunnus)
                WHERE lasku.yhtio      = '$kukarow[yhtio]'
                AND lasku.tila         = 'Y'
                AND lasku.tilaustyyppi = 'M'
                AND lasku.tapvm        >= '$vv-01-01'
                AND lasku.tapvm        <= '$vv-12-31'
                AND lasku.liitostunnus = '$row[tunnus]'
                GROUP BY 1,2,3,4
                ORDER BY 1,2";
      $eres = pupe_query($query);

      while ($erow = mysql_fetch_assoc($eres)) {

        if ($row['kuvaus'] == "50" and strtoupper($erow['vienti']) == "FI" and (substr($erow['tuoteno'], 0, 3) == "PPR" or $erow['var'] == "2" or $erow['var'] == "4")) {
          $vspserie[$row["ytunnus"]]["kotimaanpuolipaivat"] = 1;
        }
        elseif ($row['kuvaus'] == "50" and strtoupper($erow['vienti']) == "FI") {
          $vspserie[$row["ytunnus"]]["kotimaanpaivat"] = 1;
        }
        elseif ($row['kuvaus'] == "50") {
          $vspserie[$row["ytunnus"]]["ulkomaanpaivat"] = 1;
        }

        echo "<tr class='aktiivi'>";
        echo "<td class='back'></td>";
        echo "<td class='spec'><font class='info'>&raquo; $erow[tuoteno] - $erow[nimitys]</font></td>";
        echo "<td class='spec' align='right'>".number_format($erow["kpl"], 0, ',', ' ')."</td>";
        echo "<td class='spec' align='right'>".number_format($erow["hinta"], 2, ',', ' ')."</td>";
        echo "<td class='spec' align='right'>".number_format($erow["yhteensa"], 2, ',', ' ')."</td>";
        echo "</tr>";
      }

      $ednimi = $row["nimi"];
      $summat[$row["kuvaus"]] += $row["yhteensa"];
      $kappaleet[$row["kuvaus"]] += $row["kpl"];
    }

    echo "<tr><td class='back' colspan='5'></td></tr>";

    echo "<tr class='aktiivi'>";
    echo "<th colspan='2'>50 ".t("Päivärahat ja ateriakorvaukset")."</th>";
    echo "<td align='right'>".number_format($kappaleet[50], 2, ',', ' ')."</td>";
    echo "<td colspan='2' align='right'>".number_format($summat[50], 2, ',', ' ')."</td>";
    echo "</tr>";

    echo "<tr class='aktiivi'>";
    echo "<th colspan='2'>56 ".t("Verovapaa kilometrikorvaus")."</th>";
    echo "<td align='right'>".number_format($kappaleet[56], 2, ',', ' ')."</td>";
    echo "<td colspan='2' align='right'>".number_format($summat[56], 2, ',', ' ')."</td>";
    echo "</tr>";

    echo "</table>";

    $file = "";
    $lask = 1;
    $ytunnus = tulosta_ytunnus($yhtiorow['ytunnus']);
    
    // Aikaleima pakollinen 13.6.2017 alkaen
    $date = new DateTime('now');
    $aikaleima = date_format($date, 'dmYHis');

    foreach ($vspserie as $htunnus => $matkustaja) {

      $matkustaja['paivarahat']  = number_format(round($matkustaja['paivarahat'],  2), 2, ",", "");
      $matkustaja['kilsat_raha'] = number_format(round($matkustaja['kilsat_raha'], 2), 2, ",", "");
      $matkustaja['kilsat']      = round($matkustaja['kilsat']);

      $file .= "000:VSPSERIE\n";
      $file .= "084:P\n";
      $file .= "058:$vv\n";
      $file .= "010:{$ytunnus}\n";
      $file .= "083:{$htunnus}\n";
      $file .= "085:{$matkustaja['nimi']}\n";
      $file .= "114:0,00\n";
      $file .= "115:0,00\n";
      $file .= "150:{$matkustaja['paivarahat']}\n";
      $file .= "151:{$matkustaja['kotimaanpaivat']}\n";
      $file .= "152:{$matkustaja['kotimaanpuolipaivat']}\n";
      $file .= "153:{$matkustaja['ulkomaanpaivat']}\n";
      $file .= "154:0\n";
      $file .= "155:{$matkustaja['kilsat']}\n";
      $file .= "156:{$matkustaja['kilsat_raha']}\n";
      $file .= "157:0,00\n";
      $file .= "014:0838105-5_PS\n";
      $file .= "198:{$aikaleima}\n";
      $file .= "999:$lask\n";

      $lask++;
    }

    $filenimi = "VSPSERIE-$kukarow[yhtio]-".date("dmy-His").".txt";
    file_put_contents("/tmp/".$filenimi, $file);

    echo "<br><form method='post' class='multisubmit'>
          <input type='hidden' name='tee' value='lataa_tiedosto'>
          <input type='hidden' name='lataa_tiedosto' value='1'>
          <input type='hidden' name='kaunisnimi' value='".t("Verottomat_korvaukset")."-$kukarow[yhtio]-$vv.txt'>
          <input type='hidden' name='tmpfilenimi' value='$filenimi'>
          <input type='submit' name='tallenna' value='".t("Tallenna saajakohtainen erittely")."'>
        </form><br><br>";

  }
}

require "inc/footer.inc";
