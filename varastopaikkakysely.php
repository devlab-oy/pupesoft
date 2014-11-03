<?php

require "inc/parametrit.inc";

if (!isset($tee))         $tee = "";
if (!isset($toim))         $toim = "";
if (!isset($lopetus))       $lopetus = "";
if (!isset($toim_kutsu))    $toim_kutsu = "";
if (!isset($ulos))         $ulos = "";
if (!isset($livesearch_tee)) $livesearch_tee = "";

$debug = 0;
$limit = "";
$mista = "";
$minne = "";

if ($debug == 1) {
  $limit = " limit 50"; // debug
}

if ($livesearch_tee == "VARASTOHAKU") {
  livesearch_varastohaku();
  exit;
}

// Enaboidaan ajax kikkare
enable_ajax();

echo "<font class='head'>".t("Varastopaikat")."</font><hr>";

echo "<table>";
echo "<tr>";
echo "<form method='post' name='formi' autocomplete='off'>";
echo "<input type='hidden' name='toim' value='$toim'>";
echo "<input type='hidden' name='lopetus' value='$lopetus'>";
echo "<input type='hidden' name='tee' value='V'>";
echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
echo "<th style='vertical-align:middle;'>".t("Varastohaku")."</th>";
echo "<td>".livesearch_kentta("formi", "VARASTOHAKU", "varastopaikka", 300)."</td>";

// Voidaan hakea sscc-koodilla jos suuntalavat on k�yt�ss�
if ($yhtiorow['suuntalavat'] == 'S') {
  echo "</tr>";
  echo "<tr>";
  echo "<th>".t("SSCC")."</th>";
  echo "<td><input type='text' name='sscc' class='varastopaikka' style='width:300px'></td>";
}

echo "<td class='back'>";
echo "<input type='Submit' value='".t("Hae")."'></form></td>";
echo "</tr>";
echo "</table>";
echo "<br>";

$thyllyalue = strtoupper(pupesoft_cleanstring($ahyllyalue));   // Tuleva tuotepaikka
$thyllynro  = strtoupper(pupesoft_cleanstring($ahyllynro));     // Tuleva tuotepaikka
$thyllyvali = strtoupper(pupesoft_cleanstring($ahyllyvali));   // Tuleva tuotepaikka
$thyllytaso = strtoupper(pupesoft_cleanstring($ahyllytaso));   // Tuleva tuotepaikka

// Virhetarkastukset
if ($tee == "W") {

  if ($thyllyalue == "" or $thyllynro == "" or $thyllyvali == "" or $thyllytaso == "") {
    echo "<font class='error'>".t("VIRHE: Sy�tetty varastopaikka ei ollut t�ydellinen")."</font><br>";
    $tee = "V";
  }

  if (kuuluukovarastoon($thyllyalue, $thyllynro) == 0) {
    echo "<font class='error'>".t("VIRHE: Sy�tetty varastopaikka ei kuulu mihink��n varastoon")." ($ahyllyalue-$ahyllynro)</font><br>";
    $tee = "V";
  }

  // Et rukasannut mit��n
  if (!is_array($tunnukset) or $tunnukset == "") {
    echo "<font class='error'>VIRHE: Ei yht��n valittua tuotetta</font><br>";
    $tee = "V";
  }

  // Kohdevarasto ja l�hdevarastopaikka on samat
  if ($varastopaikka != "") {
    $minnesiirretaan = $thyllyalue."-".$thyllynro."-".$thyllyvali."-".$thyllytaso;

    if (strtoupper($varastopaikka) == strtoupper($minnesiirretaan)) {
      echo "<font class='error'>VIRHE: L�hdevarastopaikka ja kohdevarastopaikka eiv�t voi olla samat</font><br>";
      $tee = "V";
    }
  }
}

if ($tee == "V") {

  echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
    <!--

    function toggleAll(toggleBox) {

      var currForm = toggleBox.form;
      var isChecked = toggleBox.checked;
      var nimi = toggleBox.name;

      for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
        if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,3) == nimi) {
          currForm.elements[elementIdx].checked = isChecked;
        }
      }
    }

    //-->
    </script>";

  // Jos haettu sscc koodilla
  if (!empty($sscc)) {
    $query = "SELECT tuotepaikat.*, suuntalavat.sscc
              FROM tuotepaikat
              JOIN tilausrivi on (
                tilausrivi.yhtio=tuotepaikat.yhtio
                AND tilausrivi.suuntalava > 0
                AND tilausrivi.tyyppi='O'
                AND tilausrivi.tuoteno=tuotepaikat.tuoteno
                AND tilausrivi.hyllyalue=tuotepaikat.hyllyalue
                AND tilausrivi.hyllynro=tuotepaikat.hyllynro
                AND tilausrivi.hyllyvali=tuotepaikat.hyllyvali
                AND tilausrivi.hyllytaso=tuotepaikat.hyllytaso)
              JOIN suuntalavat on (suuntalavat.yhtio=tilausrivi.yhtio and suuntalavat.tunnus=tilausrivi.suuntalava)
              WHERE tuotepaikat.yhtio='{$kukarow['yhtio']}'
              AND tuotepaikat.tyyppi='S'
              AND suuntalavat.sscc='{$sscc}'
              GROUP BY tuotepaikat.tuoteno";
    $result = pupe_query($query);
  }
  else {
    // Haetaan tuotteet varastopaikka haulla
    $query = "SELECT tuotepaikat.*
              FROM tuotepaikat
              WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
              AND concat(tuotepaikat.hyllyalue,'-',tuotepaikat.hyllynro,'-',tuotepaikat.hyllyvali,'-',tuotepaikat.hyllytaso) = '$varastopaikka'
              order by tuotepaikat.tuoteno
              $limit";
    $result = pupe_query($query);
  }

  echo "<font class='head'>".t("Varastopaikka")." $varastopaikka</font><hr>";

  echo "<form method='post' name='formi3' autocomplete='off'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<input type='hidden' name='tee' value='W'>";
  echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
  echo "<input type='hidden' name='varastopaikka' value='$varastopaikka'>";

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Tuoteno")."</th>";
  echo "<th>".t("Paikka")."</th>";
  echo "<th>".t("Nimitys")."</th>";
  echo "<th>".t("Saldo")."</th>";
  echo "<th>".t("Oletus")."</th>";
  echo "<th>".t("Suuntalava")."</th>";
  echo "<th>".t("Siirr�")."</th>";
  echo "</tr>";

  $i=0;

  while ($rivi = mysql_fetch_assoc($result)) {

    // Haetaan nimitys
    $nimitys_result = pupe_query("  SELECT nimitys
                    FROM tuote
                    WHERE yhtio='{$kukarow['yhtio']}'
                    AND tuoteno='{$rivi['tuoteno']}'");
    $nimitys_result = mysql_fetch_assoc($nimitys_result);
    $rivi['nimitys'] = $nimitys_result['nimitys'];

    echo "<tr>";
    echo "<td>$rivi[tuoteno]</td>";
    echo "<td>$rivi[hyllyalue] $rivi[hyllynro] $rivi[hyllyvali] $rivi[hyllytaso]</td>";
    echo "<td>$rivi[nimitys]</td>";
    echo "<td>$rivi[saldo]</td>";
    echo "<td>$rivi[oletus]</td>";

    // Haetaan suuntalavan sscc, jos tuotepaikan tyyppi on 'S'
    if ($rivi['tyyppi'] == 'S') {
      $s_query = "SELECT group_concat(distinct(sscc)) as sscc
                  FROM tilausrivi
                  join suuntalavat on (tilausrivi.yhtio=suuntalavat.yhtio AND tilausrivi.suuntalava=suuntalavat.tunnus)
                  WHERE tilausrivi.yhtio='{$kukarow['yhtio']}'
                  AND tilausrivi.tyyppi='O'
                  AND suuntalava > 0
                  AND tuoteno='$rivi[tuoteno]'
                  AND hyllyalue='$rivi[hyllyalue]'
                  AND hyllynro='$rivi[hyllynro]'
                  AND hyllyvali='$rivi[hyllyvali]'
                  AND hyllytaso='$rivi[hyllytaso]'";

      $lava_result = pupe_query($s_query);
      $lava_result = mysql_fetch_assoc($lava_result);
      $rivi['sscc'] = $lava_result['sscc'];
      echo "<td>{$rivi['sscc']}</td>";
    }
    else {
      echo "<td></td>";
    }

    echo "<td align='center'>";
    if ($rivi['saldo'] != 0) {
      echo "<input type='checkbox' name='tunnukset[$i]' value='$rivi[tunnus]'>";
      echo "<input type='hidden' name='tuotenumerot[$i]' value='$rivi[tuoteno]'>";
      echo "<input type='hidden' name='saldot[$i]' value='$rivi[saldo]'>";
    }
    echo "</td>";
    echo "</tr>";
    $i++;
  }

  echo "<input type='hidden' name='org_varasto' value='$org_varasto'>";

  echo "<tr>";
  echo "<th colspan='6'>".t("Ruksaa kaikki")."</th>";
  echo "<td align='center'><input type='checkbox' name='tun' onclick='toggleAll(this)'></td>";
  echo "</tr>";
  echo "</table><br>";

  // T�h�n kohde varasto
  echo "<table>
      <tr><th>".t("Minne varastopaikalle siirret��n")."</th></tr>
      <tr><td>
      ".t("Alue")." ", hyllyalue("ahyllyalue", $thyllyalue), "
      ".t("Nro")."  <input type = 'text' name = 'ahyllynro'  size = '5' maxlength='5' value = '$thyllynro'>
      ".t("V�li")." <input type = 'text' name = 'ahyllyvali' size = '5' maxlength='5' value = '$thyllyvali'>
      ".t("Taso")." <input type = 'text' name = 'ahyllytaso' size = '5' maxlength='5' value = '$thyllytaso'>
      </td>";

  echo "<td colspan='3' class='back'></td>";
  echo "<td class='back' >";
  echo "<input type='Submit' value='".t("Siirr�")."'>";
  echo "</td></tr>";
  echo "</table>";
  echo "</form>";
}

if ($tee == "W") {

  echo "<font class='message'>".("Siirrettiin")." ".count($tunnukset)." ".t("tuotetta paikalle")." $thyllyalue-$thyllynro-$thyllyvali-$thyllytaso</font><br>";

  foreach ($tunnukset as $key => $arvo) {

    // Mist� varastosta vied��n
    $mista = $arvo;
    $tuoteno = $tuotenumerot[$key];
    $asaldo = $saldot[$key];

    // Minne varastoon tarkistus
    $query = "SELECT tunnus
              FROM tuotepaikat
              WHERE yhtio   = '$kukarow[yhtio]'
              and tuoteno   = '$tuoteno'
              and hyllyalue = '$thyllyalue'
              and hyllynro  = '$thyllynro'
              and hyllyvali = '$thyllyvali'
              and hyllytaso = '$thyllytaso'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      echo "<font class='message'>".("Luodaan uusi varastopaikka tuotteelle").": $tuoteno ($thyllyalue, $thyllynro, $thyllyvali, $thyllytaso)</font><br>";

      $lisatty_paikka = lisaa_tuotepaikka($tuoteno, $thyllyalue, $thyllynro, $thyllyvali, $thyllytaso, "Varastopaikat ohjelmassa", "", 0, 0, 0);
      $minne = $lisatty_paikka["tuotepaikan_tunnus"];
    }
    else {
      $row = mysql_fetch_assoc($result);
      $minne = $row["tunnus"];
    }

    $tee = "N";
    $kutsuja = "vastaanota.php";

    // Tarvitsemme
    // $asaldo  = siirrett�v� m��r�
    // $mista   = tuotepaikan tunnus josta otetaan      // tunnus
    // $minne   = tuotepaikan tunnus jonne siirret��n    // uusivarastopaikan tunnus
    // $tuoteno = tuotenumero jota siirret��n

    // muuvarastopaikka.php nollaa ahylly-muuttujat
    // joten �l� vaihda thylly-muuttujia ahyllyiksi
    // Rivin 171 lomakkeella k�ytet��n a- ja t-hyllyj� tarkoituksella.

    require "muuvarastopaikka.php";
  }
}
$formi = "formi";
$kentta = "varastopaikka";

require "inc/footer.inc";
