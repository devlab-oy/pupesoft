<?php

// DataTables p‰‰lle
$pupe_DataTables = 'pullopantit_taulukko';

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

if (!isset($tee)) $tee = '';
if (!isset($ytunnus)) $ytunnus = '';
if (!isset($asiakasid)) $asiakasid = '';
if (!isset($status)) $status = '';
if (!isset($excel)) $excel = '';

if ($tee == "lataa_tiedosto") {
  readfile("/tmp/" . $tmpfilenimi);
  exit;
}

echo "<font class='head'>", t("Pullopantit"), "</font><hr>";

if ($ytunnus != '' or $asiakasid != "") {
  if ($asiakasid == "") {
    $tee = "";
  }

  $muutparametrit = $vva."/".$kka."/".$ppa."/".$vvl."/".$kkl."/".$ppl."/".$status;

  require "inc/asiakashaku.inc";

  if ($asiakasid != "") {
    $tee = "LISTAUS";
  }
}

if ($tee == 'NAYTATILAUS') {
  echo "<font class='head'>", t("Tilausnro"), ": {$tunnus}</font><hr>";
  require "naytatilaus.inc";
  require "inc/footer.inc";
  exit;
}

if ($tee == 'LISTAUS') {

  if ($excel != "") {
    echo "<table>\n";
  }
  else {
    pupe_DataTables(array(array($pupe_DataTables, 8, 8, false, false)));
    echo "\n<table class='display dataTable' id='$pupe_DataTables'>\n";
  }

  echo "<thead>\n";
  echo "<tr>\n";
  echo "<th>", t("asiakas"), "</th>\n";
  echo "<th>", t("tuote"), "</th>\n";
  echo "<th>", t("nimitys"), "</th>\n";
  echo "<th>", t("tilausnumero"), "</th>\n";
  echo "<th>", t("sarjanumero"), "</th>\n";
  echo "<th>", t("luovutus pvm"), "</th>\n";
  echo "<th>", t("status"), "</th>\n";
  echo "<th>", t("p‰ivi‰"), "</th>\n";
  echo "</tr>\n";

  if ($excel != "") {
    include 'inc/pupeExcel.inc';

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi    = 0;
    $excelsarake = 0;

    $worksheet->writeString($excelrivi, $excelsarake, t("Asiakas"), $format_bold); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Tuote"), $format_bold); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Nimitys"), $format_bold); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Tilausnumero"), $format_bold); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Sarjanumero"), $format_bold); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Luovutus pvm"), $format_bold); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Status"), $format_bold); $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("P‰ivi‰"), $format_bold); $excelsarake++;
  }
  else {
    echo "<tr>\n";
    echo "<td><input type='text' class='search_field' name='search_asikas'></td>\n";
    echo "<td><input type='text' class='search_field' name='search_tuote'></td>\n";
    echo "<td><input type='text' class='search_field' name='search_nimitys'></td>\n";
    echo "<td><input type='text' class='search_field' name='search_tilausnumero'></td>\n";
    echo "<td><input type='text' class='search_field' name='search_sarjanumero'></td>\n";
    echo "<td><input type='text' class='search_field' name='search_status'></td>\n";
    echo "<td><input type='text' class='search_field' name='search_luovutuspvm'></td>\n";
    echo "<td><input type='text' class='search_field' name='search_paivia'></td>\n";
    echo "</tr>\n";
  }

  echo "</thead>\n";
  echo "<tbody>\n";

  $query = "
    SELECT sns.sarjanumero, sns.ostorivitunnus, sns.panttirivitunnus,
      palautus.tunnus palautus_tunnus, palautus.toimaika palautus_pvm, 
      laskutettu.tunnus laskutettu_tunnus, laskutettu.toimaika laskutettu_pvm, 
      l.tunnus, l.alatila, l.nimi, r.tuoteno, r.hinta, r.nimitys, r.toimaika, DATEDIFF(NOW(), l.toimaika) paivia
    FROM sarjanumeroseuranta sns
    INNER JOIN tilausrivi r
      ON (r.yhtio = sns.yhtio AND r.tunnus = sns.myyntirivitunnus)
    INNER JOIN lasku l
      ON (l.yhtio = sns.yhtio AND l.tunnus = r.otunnus)
    INNER JOIN tuote t
      ON (t.yhtio = sns.yhtio AND t.tuoteno = sns.tuoteno)
    LEFT JOIN tilausrivi palautusrivi
      ON (palautusrivi.yhtio = sns.yhtio AND palautusrivi.tunnus = sns.ostorivitunnus)
    LEFT JOIN lasku palautus
      ON (palautus.yhtio = sns.yhtio AND palautus.tunnus = palautusrivi.otunnus)
    LEFT JOIN tilausrivi laskutetturivi
      ON (laskutetturivi.yhtio = sns.yhtio AND laskutetturivi.tunnus = sns.panttirivitunnus)
    LEFT JOIN lasku laskutettu
      ON (laskutettu.yhtio = sns.yhtio AND laskutettu.tunnus = laskutetturivi.otunnus)
    WHERE sns.yhtio = '{$kukarow['yhtio']}'
      AND t.pullopanttitarratulostus_kerayksessa = 'T'";

  if ($asiakasid != "") {
    $query .= "  AND l.liitostunnus = {$asiakasid}";
  }

  if (isset($vva) and isset($kka) and isset($ppa) and $vva != "" and $kka != "" and $ppa != "") {
    $query .= "  AND r.toimaika >= '{$vva}-{$kka}-{$ppa}'";
  }

  if (isset($vvl) and isset($kkl) and isset($ppl) and $vvl != "" and $kkl != "" and $ppl != "") {
    $query .= "  AND r.toimaika <= '{$vvl}-{$kkl}-{$ppl}'";
  }

  if ($status == "0") {
    $query .= "  AND sns.ostorivitunnus > 0 AND r.hinta = 0";
  }
  elseif ($status == "1") {
    $query .= "  AND sns.panttirivitunnus IS NOT NULL AND r.hinta = 0";
  }
  elseif ($status == "2") {
    $query .= "  AND r.hinta > 0";
  }
  elseif ($status != "") {
    $query .= "  AND r.hinta = 0 AND sns.ostorivitunnus = 0";
  }

  if ($status == "3") {
    $query .= " HAVING paivia >= 0 AND paivia <= 150";
  }
  elseif ($status == "4") {
    $query .= " HAVING paivia > 150 AND paivia <= 180";
  }
  elseif ($status == "5") {
    $query .= " HAVING paivia > 180 AND paivia <= 545";
  }
  elseif ($status == "6") {
    $query .= " HAVING paivia > 545";
  }

  $result = pupe_query($query);

  while ($tulrow = mysql_fetch_assoc($result)) {
    $paivia = $tulrow['paivia'];
    $palautuslinkki_aloitus = "<a href='../tilauskasittely/pullonpalautus.php?viivakoodi={$tulrow['sarjanumero']}'>";
    $palautuslinkki_lopetus = "</a>";

    if ($tulrow['hinta'] > 0) {
      $tila = t("Ostettu");
      $paivia = "";
      $palautuslinkki_aloitus = $palautuslinkki_lopetus = "";
    }
    elseif ($tulrow['alatila'] == "A") {
      $tila = t("Ei ker‰tty");
      $palautuslinkki_aloitus = $palautuslinkki_lopetus = "";
    }
    elseif ($tulrow['ostorivitunnus'] > 0) {
      if ($excel != "") {
        $tila = t("Palautettu") . ($tulrow['panttirivitunnus'] != null ? "* " : " ") . tv1dateconv($tulrow["palautus_pvm"]);
      }
      else {
        $tila = t("Palautettu") . ($tulrow['panttirivitunnus'] != null ? "* " : " ") . pupe_DataTablesEchoSort($tulrow['palautus_pvm']) . js_openUrlNewWindow("{$palvelin2}raportit/pullopantit.php?tee=NAYTATILAUS&tunnus={$tulrow['palautus_tunnus']}", tv1dateconv($tulrow["palautus_pvm"]), NULL, 1000, 800);
      }
      $paivia = "";
      $palautuslinkki_aloitus = $palautuslinkki_lopetus = "";
    } elseif ($tulrow['panttirivitunnus'] != null) {
      if ($excel != "") {
        $tila = t("Laskutettu") . " " . tv1dateconv($tulrow["laskutettu_pvm"]);
      }
      else {
        $tila = t("Laskutettu") . " " . pupe_DataTablesEchoSort($tulrow['laskutettu_pvm']) . js_openUrlNewWindow("{$palvelin2}raportit/pullopantit.php?tee=NAYTATILAUS&tunnus={$tulrow['laskutettu_tunnus']}", tv1dateconv($tulrow["laskutettu_pvm"]), NULL, 1000, 800);

      }
      $paivia = "";
    }
    elseif ($paivia >= 0 and $paivia <= 150) {
      $tila = "0 - 150 " . t("pv");
    }
    elseif ($paivia > 150 and $paivia <= 180) {
      $tila = "151 - 180 " . t("pv");
    }
    elseif ($paivia > 180 and $paivia <= 545) {
      $tila = "181 - 545 " . t("pv");
    }
    elseif ($paivia >= 546) {
      $tila = "546 -> " . t("pv");
    }
    else {
      $tila = "-";
    }

    echo "<tr>\n";
    echo "  <td>{$tulrow['nimi']}</td>\n";
    echo "  <td>" . js_openUrlNewWindow("{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($tulrow["tuoteno"]), $tulrow['tuoteno'], NULL, 1000, 800) . "</td>\n";
    echo "  <td>{$tulrow['nimitys']}</td>\n";
    echo "  <td>" . js_openUrlNewWindow("{$palvelin2}raportit/pullopantit.php?tee=NAYTATILAUS&tunnus={$tulrow['tunnus']}", $tulrow['tunnus'], NULL, 1000, 800) . "</td>\n";
    echo "  <td>{$palautuslinkki_aloitus}{$tulrow['sarjanumero']}{$palautuslinkki_lopetus}</td>\n";
    echo "  <td>" . pupe_DataTablesEchoSort($tulrow['toimaika']).tv1dateconv($tulrow['toimaika']) . "</td>\n";
    echo "  <td>{$tila}</td>\n";
    echo "  <td>{$paivia}</td>\n";
    echo "</tr>\n";

    if ($excel != "") {
      $excelrivi++;
      $excelsarake = 0;

      $worksheet->writeString($excelrivi, $excelsarake, $tulrow['nimi']); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $tulrow['tuoteno']); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $tulrow['nimitys']); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $tulrow['tunnus']); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $tulrow['sarjanumero']); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($tulrow['toimaika'])); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $tila); $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $paivia); $excelsarake++;
    }
  }

  echo "</tbody>\n";
  echo "</table>\n";

  if ($excel != "") {
    $excelnimi = $worksheet->close();
  }
}

echo "<br>";

if (isset($excelnimi) and $excelnimi != "") {
  echo "<br><br><table>";
  echo "<tr><th>".t("Tallenna Excel").":</th><td class='back'>";
  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='Pullopantit.xlsx'>";
  echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
  echo "<input type='submit' value='".t("Tallenna")."'></form></td></tr>";
  echo "</table><br>";
}

echo "<br>";

echo "<form name=asiakas method='post' autocomplete='off'>";
echo "<input type='hidden' id='tee' name='tee' value='LISTAUS'>";
echo "<table>";

echo "<tr>";
echo "<th>", t("Anna ytunnus tai osa nimest‰"), "</th>";
if ((int) $asiakasid > 0) {
  echo "<td colspan='3'>";
  echo "$asiakasrow[nimi] $asiakasrow[nimitark]";
  echo "<input type='hidden' id='asiakasid' name='asiakasid' value='$asiakasid'>";
  echo "</td>";
  echo "<td class='back'>";
  echo '<input type="button" onclick="document.getElementById(\'asiakasid\').value = \'\'; document.getElementById(\'tee\').value = \'\'; document.asiakas.submit();" value="'.t("Vaihda asiakasta").'">';
  echo "</td>";
}
else {
  echo "<td colspan='3'>";
  echo "<input type='text' id='ytunnus' name='ytunnus' value='{$ytunnus}'>";
  echo "</td>";
}
echo "</tr>";

echo "<tr><th>", t("Status"), "</th>";
echo "<td colspan='3'><select name='status'>";
echo "<option value=''" . ($status == "" ? " SELECTED" : "") . ">", t("Kaikki"), "</option>";
echo "<option value='0'" . ($status == "0" ? " SELECTED" : "") . ">", t("Palautetut"), "</option>";
echo "<option value='1'" . ($status == "1" ? " SELECTED" : "") . ">", t("Laskutetut"), "</option>";
echo "<option value='2'" . ($status == "2" ? " SELECTED" : "") . ">", t("Ostetut"), "</option>";
echo "<option value='3'" . ($status == "3" ? " SELECTED" : "") . ">", t("0 - 150 pv"), "</option>";
echo "<option value='4'" . ($status == "4" ? " SELECTED" : "") . ">", t("151 - 180 pv"), "</option>";
echo "<option value='5'" . ($status == "5" ? " SELECTED" : "") . ">", t("181 - 545 pv"), "</option>";
echo "<option value='6'" . ($status == "6" ? " SELECTED" : "") . ">", t("546 -> pv"), "</option>";
echo "</select></td>";
echo "</tr>";

echo "<tr><th>".t("Alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppa' value='$ppa' size='3'></td>
      <td><input type='text' name='kka' value='$kka' size='3'></td>
      <td><input type='text' name='vva' value='$vva' size='5'></td>
      </tr><tr><th>".t("Loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppl' value='$ppl' size='3'></td>
      <td><input type='text' name='kkl' value='$kkl' size='3'></td>
      <td><input type='text' name='vvl' value='$vvl' size='5'></td>
      </tr>";

echo "<tr>";
echo "<th>Tee Excel</th>";
echo "<td colspan='3'><input type='checkbox' name='excel' value='excel'" . ($excel != "" ? " CHECKED" : "") . "/></td>";
echo "<td class='back'><input type='submit' value='", t("Listaa"), "'></td>";
echo "</tr>";

echo "</table>";
echo "</form>";

// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

require "inc/footer.inc";
