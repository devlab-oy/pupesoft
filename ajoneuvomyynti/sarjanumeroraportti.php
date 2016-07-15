<?php

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

if (strpos($_SERVER['SCRIPT_NAME'], "sarjanumeroraportti.php") !== FALSE) {
  require "../inc/parametrit.inc";
}

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}
else {

  echo "<font class='head'>".t("Sarjanumeroraportointi")."</font><hr>";

  if ($toiminto != 'TULOSTA') {
    echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
        <!--

        function toggleAll(toggleBox) {

          var currForm = toggleBox.form;
          var isChecked = toggleBox.checked;
          var nimi = toggleBox.name;

          for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
            if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,7) == nimi && currForm.elements[elementIdx].value != '".t("Ei valintaa")."') {
              currForm.elements[elementIdx].checked = isChecked;
            }
          }
        }

        //-->
        </script>";

    // Piirrell‰‰n formi
    // Kursorinohjaus
    $formi = "haku";
    $kentta = "sarjanumero_haku";

    echo "<form name='haku' method='post'>";


    $monivalintalaatikot = array("OSASTO", "TRY");
    $noautosubmit = TRUE;

    require "tilauskasittely/monivalintalaatikot.inc";

    echo "<br><table>";

    $chk1 = $chk2 = $chk3 = $chk4 = $chk5 = $chk6 = "";

    if ($varasto_haku == '') {
      $chk1 = "SELECTED";
    }
    elseif ($varasto_haku == 'VAR') {
      $chk2 = "SELECTED";
    }
    elseif ($varasto_haku == 'VVP') {
      $chk3 = "SELECTED";
    }
    elseif ($varasto_haku == 'VVS') {
      $chk4 = "SELECTED";
    }
    elseif ($varasto_haku == 'MYY') {
      $chk5 = "SELECTED";
    }

    if ($lisatiedot == '') {
      $chk6 = "SELECTED";
    }
    elseif ($lisatiedot == 'on') {
      $chk7 = "SELECTED";
    }
    elseif ($lisatiedot == 'EIVAR') {
      $chk8 = "SELECTED";
    }

    if ($exceliin == '') {
      $chk9 = "SELECTED";
    }
    else {
      $chk10 = "SELECTED";
    }

    echo "<tr><th>".t("Varastostatus")."</th><td>
          <select name='varasto_haku'>
           <option value=''    $chk1>".t("Kaikki")."</option>
          <option value='MYY' $chk5>".t("Vain myydyt tuotteet")."</option>
          <option value='VAR' $chk2>".t("Vain varastossa olevat")."</option>
          <option value='VVP' $chk3>".t("Vain vapaana varastossa olevat")."</option>
          <option value='VVS' $chk4>".t("Vain varattuina varastossa olevat")."</option>
          </select>
        </td></tr>";

    echo "<tr><th>".t("Lis‰tiedot/Varusteet")."</th><td>
          <select name='lisatiedot'>
           <option value=''    $chk6>".t("N‰ytet‰‰n kaikki tiedot")."</option>
          <option value='on' $chk7>".t("Ei n‰ytet‰ lis‰tietoja")."</option>
          <option value='EIVAR' $chk8>".t("Ei n‰ytet‰ lis‰tietoja eik‰ varusteita")."</option>
          </select>
        </td></tr>";


    echo "<tr><th>".t("Tee Excel listaus")."</th><td>
          <select name='exceliin'>
           <option value=''   $chk9>".t("Ei")."</option>
          <option value='kylla' $chk10>".t("Kyll‰")."</option>
          </select>
        </td></tr>";

    echo "<tr><td class='back'><br></td></tr>";

    echo "<tr>";
    echo "<th>".t("Sarjanumero")."</th>";
    echo "<th>".t("Tuoteno")."</th>";
    echo "<th>".t("Nimitys")."</th>";
    echo "<th>".t("Ostotilaus")."</th>";
    echo "<th>".t("Myyntitilaus")."</th>";
    echo "<th>".t("Hinnat")."</th>";
    echo "<th>".t("Varasto")."</th>";
    echo "<th>".t("K‰ytetty")."</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><input type='text' size='10' name='sarjanumero_haku'     value='$sarjanumero_haku'></td>";
    echo "<td><input type='text' size='10' name='tuoteno_haku'       value='$tuoteno_haku'></td>";
    echo "<td><input type='text' size='10' name='nimitys_haku'       value='$nimitys_haku'></td>";
    echo "<td><input type='text' size='10' name='ostotilaus_haku'     value='$ostotilaus_haku'></td>";
    echo "<td><input type='text' size='10' name='myyntitilaus_haku'    value='$myyntitilaus_haku'></td>";
    echo "<td></td>";

    echo "<td><input type='text' size='10' name='varastonimi_haku'    value='$varastonimi_haku'></td>";

    $chk1 = $chk2 = $chk3 = "";

    if ($kaytetty_haku == '') {
      $chk1 = "SELECTED";
    }
    elseif ($kaytetty_haku == 'U') {
      $chk2 = "SELECTED";
    }
    elseif ($kaytetty_haku == 'K') {
      $chk3 = "SELECTED";
    }

    echo "<td>
          <select name='kaytetty_haku'>
           <option value=''  $chk1>".t("Kaikki")."</option>
          <option value='U' $chk2>".t("Uudet")."</option>
          <option value='K' $chk3>".t("K‰ytetyt")."</option>
          </select>
        </td>";

    echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
    echo "</tr>";
    echo "</form>";
  }
  else {
    echo "<br><br><table>";
    echo "<tr>";
    echo "<th>".t("Sarjanumero")."</th>";
    echo "<th>".t("Tuoteno")."</th>";
    echo "<th>".t("Nimitys")."</th>";
    echo "<th>".t("Ostotilaus")."</th>";
    echo "<th>".t("Myyntitilaus")."</th>";
    echo "<th>".t("Hinnat")."</th>";
    echo "<th>".t("Varasto")."</th>";
    echo "<th>".t("K‰ytetty")."</th>";
    echo "</tr>";
  }

  if ($toiminto == 'TULOSTA' or $sarjanumero_haku != '' or $varastonimi_haku != '' or $tuoteno_haku != '' or $nimitys_haku != '' or $ostotilaus_haku != '' or $myyntitilaus_haku != '' or $lisa != '') {
    $lisa1  = "";

    if ($toiminto == 'TULOSTA') {
      $lisa = unserialize(urldecode($tul_lisa));
    }
    else {
      if ($ostotilaus_haku != "") {
        if (is_numeric($ostotilaus_haku)) {
          if ($ostotilaus_haku == 0) {
            $lisa1 .= " and lasku_osto.tunnus is null ";
          }
          else {
            $lisa1 .= " and lasku_osto.tunnus='$ostotilaus_haku' ";
          }
        }
        else {
          $lisa1 .= " and match (lasku_osto.nimi) against ('$ostotilaus_haku*' IN BOOLEAN MODE) ";
        }
      }

      if ($myyntitilaus_haku != "") {
        if (is_numeric($myyntitilaus_haku)) {
          if ($myyntitilaus_haku == 0) {
            $lisa1 .= " and (lasku_myynti.tunnus is null or lasku_myynti.tila = 'T') ";
          }
          else {
            $lisa1 .= " and lasku_myynti.tunnus='$myyntitilaus_haku' ";
          }
        }
        else {
          $lisa1 .= " and match (lasku_myynti.nimi) against ('$myyntitilaus_haku*' IN BOOLEAN MODE) ";
        }
      }

      if ($lisatieto_haku != '') {
        $lisa1 .= " and sarjanumeroseuranta.lisatieto like '%$lisatieto_haku%' ";
      }

      if ($tuoteno_haku != '') {
        $lisa1 .= " and sarjanumeroseuranta.tuoteno like '%$tuoteno_haku%' ";
      }

      if ($sarjanumero_haku != '') {
        $lisa1 .= " and sarjanumeroseuranta.sarjanumero like '%$sarjanumero_haku%' ";
      }

      if ($varastonimi_haku != '') {
        $lisa1 .= " and varastopaikat.nimitys = '$varastonimi_haku' ";
      }

      if ($kaytetty_haku == 'U') {
        $lisa1 .= " and sarjanumeroseuranta.kaytetty  = '' ";
      }
      elseif ($kaytetty_haku == 'K') {
        $lisa1 .= " and sarjanumeroseuranta.kaytetty != '' ";
      }

      if ($tuoteryhma != "kaikki" and $sel_tuoteryhma != "" and $sel_tuoteryhma != t("Ei valintaa")) {
        $lisa1 .= " and tuote.try in ('$sel_tuoteryhma') ";
      }

      if ($osasto != "kaikki" and $sel_osasto != "" and $sel_osasto != t("Ei valintaa")) {
        $lisa1 .= " and tuote.osasto in ('$sel_osasto') ";
      }

      if ($varasto_haku == 'MYY') {
        $lisa1 .= "  and tilausrivi_myynti.laskutettuaika != '0000-00-00'
                and tilausrivi_osto.laskutettuaika != '0000-00-00'";
      }
      elseif ($varasto_haku == 'VAR') {
        $lisa1 .= "  and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
                and tilausrivi_osto.laskutettuaika != '0000-00-00'";
      }
      elseif ($varasto_haku == 'VVP') {
        $lisa1 .= "  and (tilausrivi_myynti.tunnus is null or lasku_myynti.tila = 'T')
                and tilausrivi_osto.laskutettuaika != '0000-00-00'";
      }
      elseif ($varasto_haku == 'VVS') {
        $lisa1 .= "  and (tilausrivi_myynti.tunnus is not null and tilausrivi_myynti.laskutettuaika = '0000-00-00')
                and tilausrivi_osto.laskutettuaika != '0000-00-00'";
      }

      if ($nimitys_haku != '') {
        $lisa1 .= " HAVING nimitys like '%$nimitys_haku%' ";
      }
    }

    // N‰ytet‰‰n kaikki
    $query = "SELECT sarjanumeroseuranta.*,
              if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys) nimitys,
              tuote.myyntihinta                   tuotemyyntihinta,
              tuote.nimitys                     tuotenimitys,
              tuote.tuotemerkki                   tuotetuotemerkki,
              lasku_osto.tunnus                  osto_tunnus,
              lasku_osto.nimi                    osto_nimi,
              lasku_myynti.tunnus                  myynti_tunnus,
              lasku_myynti.nimi                  myynti_nimi,
              lasku_myynti.tila                  myynti_tila,
              (tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)    ostohinta,
              tilausrivi_osto.perheid2              osto_perheid2,
              (tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)  myyntihinta,
              varastopaikat.nimitys                varastonimi,
              tilausrivi_osto.tunnus                 osto_rivitunnus,
              sarjanumeroseuranta.lisatieto            lisatieto,
              concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka
              FROM sarjanumeroseuranta
              LEFT JOIN tuote use index (tuoteno_index) ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
              LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
              LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
              LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
              LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
              LEFT JOIN varastopaikat ON sarjanumeroseuranta.yhtio = varastopaikat.yhtio
              and concat(rpad(upper(varastopaikat.alkuhyllyalue)  ,5,'0'),lpad(upper(varastopaikat.alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
              and concat(rpad(upper(varastopaikat.loppuhyllyalue) ,5,'0'),lpad(upper(varastopaikat.loppuhyllynro) ,5,'0')) >= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
              WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
              and sarjanumeroseuranta.myyntirivitunnus != -1
              $lisa
              $lisa1
              ORDER BY sarjanumeroseuranta.kaytetty, sarjanumeroseuranta.tuoteno, sarjanumeroseuranta.myyntirivitunnus";
    $sarjares = mysql_query($query) or pupe_error($query);

    if (file_exists('../tilauskasittely/sarjanumeron_lisatiedot_popup.inc') and $lisatiedot != 'EIVAR') {
      require "../tilauskasittely/sarjanumeron_lisatiedot_popup.inc";
    }

    //Raportin pohja
    if ($toiminto == 'TULOSTA') {
      require_once 'pdflib/phppdflib.class.php';

      if (substr($hinnat, 0, 2) == "ME") {
        require_once 'sarjanumeromyyntiesite_pdf.inc';
      }
      else {
        require_once 'sarjanumeroraportti_pdf.inc';
      }
    }

    if ($exceliin == "kylla") {

      include 'inc/pupeExcel.inc';

      //HINNAT = ''
      $hinnat = "";

      $worksheet   = new pupeExcel();
      $format_bold = array("bold" => TRUE);
      $excelrivi   = 0;

      $worksheet->write($excelrivi, 0,  t("Merkki"), $format_bold);
      $worksheet->write($excelrivi, 1,  t("Malli"), $format_bold);
      $worksheet->write($excelrivi, 2,  t("Kone"), $format_bold);
      $worksheet->write($excelrivi, 3,  "", $format_bold);
      $worksheet->write($excelrivi, 4,  t("Sarjanumero"), $format_bold);
      $worksheet->write($excelrivi, 5,  t("Lis‰tiedot"), $format_bold);
      $worksheet->write($excelrivi, 6,  t("Ostohinta"), $format_bold);

      $worksheet->write($excelrivi, 7,  t("Kululaskut (Uusi laite)"), $format_bold);
      $worksheet->write($excelrivi, 8,  t("Kululaskut (K‰ytetty laite)"), $format_bold);

      $worksheet->write($excelrivi, 9,  t("Kate 8%"), $format_bold);
      $worksheet->write($excelrivi, 10, t("Tarjoushinta"), $format_bold);
      $worksheet->write($excelrivi, 11, t("Kate EUR"), $format_bold);
      $worksheet->write($excelrivi, 12, t("Kate %"), $format_bold);
      $worksheet->write($excelrivi, 13, t("Laite"), $format_bold);
      $worksheet->write($excelrivi, 14, t("Lis‰varusteet"), $format_bold);
      $worksheet->write($excelrivi, 15, t("Yhteens‰"), $format_bold);
      $worksheet->write($excelrivi, 16, t("Etusi"), $format_bold);
      $worksheet->write($excelrivi, 17, t("Varastopaikka"), $format_bold);
      $excelrivi++;
    }

    $lopetlinkki ="$PHP_SELF////varasto_haku=$varasto_haku//lisatiedot=$lisatiedot//exceliin=$exceliin//sarjanumero_haku=$sarjanumero_haku//tuoteno_haku=$tuoteno_haku//nimitys_haku=$nimitys_haku//ostotilaus_haku=$ostotilaus_haku//myyntitilaus_haku=$myyntitilaus_haku//varastonimi_haku=$varastonimi_haku//kaytetty_haku=$kaytetty_haku".str_replace("&", "//", $ulisa);

    while ($sarjarow = mysql_fetch_array($sarjares)) {
      if ($toiminto == 'TULOSTA') {
        //PDF parametrit
        if (!isset($pdf)) {
          $pdf = new pdffile;
          $pdf->set_default('margin-top',  0);
          $pdf->set_default('margin-bottom',  0);
          $pdf->set_default('margin-left',  0);
          $pdf->set_default('margin-right',  0);
        }

        if ($tiedosto == "") {
          list($page, $kalakorkeus) = alku($pdf, $hinnat);
        }
      }

      echo "<tr>";
      echo "<td valign='top'><a href='".$palvelin2."tilauskasittely/sarjanumeroseuranta.php?sarjanumero_haku=$sarjarow[sarjanumero]&lopetus=$lopetlinkki'>$sarjarow[sarjanumero]</a></td>";
      echo "<td colspan='2' valign='top'><a href='".$palvelin2."tuote.php?tee=Z&tuoteno=".urlencode($sarjarow["tuoteno"])."&lopetus=$lopetlinkki'>$sarjarow[tuoteno]</a><br>$sarjarow[nimitys]</td>";

      if ($sarjarow["ostorivitunnus"] == 0) {
        $sarjarow["ostorivitunnus"] = "";
      }
      if ($sarjarow["myyntirivitunnus"] == 0) {
        $sarjarow["myyntirivitunnus"] = "";
      }

      echo "<td colspan='2' valign='top'><a href='".$palvelin2."raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus=$sarjarow[osto_tunnus]&lopetus=$lopetlinkki'>$sarjarow[osto_tunnus] $sarjarow[osto_nimi]</a><br>";

      if ($sarjarow["myynti_tila"] == 'T') {
        $fnlina1 = "<font class='message'>(Tarjous: ";
        $fnlina2 = ")</font>";
      }
      else {
        $fnlina1 = "";
        $fnlina2 = "";
      }

      if ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"]) {
        $ztun = $sarjarow["osto_perheid2"];
      }
      else {
        $ztun = $sarjarow["siirtorivitunnus"];
      }

      if ($ztun > 0) {
        $query = "SELECT tilausrivi.tuoteno, sarjanumeroseuranta.sarjanumero
                  FROM tilausrivi
                  LEFT JOIN sarjanumeroseuranta ON (tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus)
                  WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.tunnus='$ztun'";
        $siires = mysql_query($query) or pupe_error($query);
        $siirow = mysql_fetch_array($siires);

        $fnlina11 = "<font class='error'>(Varattu lis‰varusteena tuotteelle: $siirow[tuoteno] $siirow[sarjanumero])</font>";
        $fnlina22 = "(Varattu lis‰varusteena tuotteelle: $siirow[tuoteno] $siirow[sarjanumero])";
      }
      else {
        $fnlina11 = "";
        $fnlina22 = "";
      }

      echo "<a href='".$palvelin2."raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$sarjarow[myynti_tunnus]&lopetus=$lopetlinkki'>$fnlina1 $sarjarow[myynti_tunnus] $sarjarow[myynti_nimi] $fnlina2 $fnlina11</a></td>";

      if (function_exists("sarjanumeronlisatiedot_popup")) {
        list($kommentit, $text_output, $kuvalisa_bin, $sarjarow["ostohinta"], $sarjarow["tuotemyyntihinta"]) = sarjanumeronlisatiedot_popup($sarjarow["tunnus"], '', '', $hinnat, '100%', $lisatiedot);
      }

      echo "<td valign='top' align='right' nowrap>";

      if ($sarjarow["ostohinta"] != 0) echo "-".sprintf("%.2f", $sarjarow["ostohinta"])."<br>";
      if ($sarjarow["myyntihinta"] != 0) echo "+".sprintf("%.2f", $sarjarow["myyntihinta"])."<br>";

      $yhteensa = $sarjarow["ostohinta"] * -1 + $sarjarow["myyntihinta"];

      if ($sarjarow["ostohinta"] != 0 or $sarjarow["myyntihinta"] != 0) echo "=".sprintf("%.2f", $yhteensa);

      echo "</td>";

      echo "<td valign='top' nowrap>$sarjarow[varastonimi]</td>";

      if ($sarjarow["kaytetty"] != '') {
        echo "<td valign='top'>".t("K‰ytetty")."</td>";
      }
      else {
        echo "<td valign='top'>".t("Uusi")."</td>";
      }

      if ($toiminto == 'TULOSTA') {
        list($page, $kalakorkeus) = rivi($pdf, $page, $kalakorkeus, $sarjarow, $kulurow, $hinnat, $tarkkuus, $kommentit, $text_output, $kuvalisa_bin, $sarjarow["ostohinta"], $fnlina22);


        if ($tiedosto == "") {
          print_pdf($pdf, $valittu_tulostin, $luetkpl);

          unset($pdf);
        }
      }

      // Lis‰t‰‰n rivi exceltiedostoon
      if (isset($workbook)) {
        //Merkki
        preg_match("/Merkki###(.*)/", $text_output, $xls_merkki);

        if (trim($xls_merkki[1]) == "") {
          $xls_merkki[1] = $sarjarow["tuotetuotemerkki"];
        }

        $worksheet->writeString($excelrivi, 0, $xls_merkki[1], $format_top);

        // Malli
        preg_match("/Malli###(.*)/", $text_output, $xls_malli);

        if (trim($xls_malli[1]) == "") {
          $xls_malli[1] = $sarjarow["tuotenimitys"];
        }

        $worksheet->writeString($excelrivi, 1, $xls_malli[1], $format_top);

        preg_match("/Moottorin Merkki###(.*)/", $text_output, $xls_moo1);
        preg_match("/Moottorin Malli###(.*)/", $text_output, $xls_moo2);
        //preg_match("/Moottoreita###(.*)/", $text_output, $xls_moo3);
        preg_match("/Moottorin Teho###(.*)/", $text_output, $xls_moo4);

        // Moottorin tiedot
        if ($xls_moo1[1] != "") {
          $worksheet->writeString($excelrivi, 2, $xls_moo1[1]." ".$xls_moo2[1]." ".$xls_moo4[1], $format_wrap);
        }
        else {
          $worksheet->writeString($excelrivi, 2, "", $format_wrap);
        }

        // Myyntitilaus/tarjous
        if ($sarjarow["myynti_tunnus"] > 0) {
          $worksheet->write($excelrivi, 3, $palvelin."raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$sarjarow[myynti_tunnus]", trim(strip_tags("$fnlina1 $sarjarow[myynti_tunnus] $sarjarow[myynti_nimi] $fnlina2 $fnlina11")), $format_wrap);
        }
        else {
          $worksheet->writeString($excelrivi, 3, trim(strip_tags("$fnlina1 $sarjarow[myynti_tunnus] $sarjarow[myynti_nimi] $fnlina2 $fnlina11")), $format_wrap);
        }

        // Sarjanumero
        $worksheet->write($excelrivi, 4, $palvelin."tilauskasittely/sarjanumeroseuranta.php?sarjanumero_haku=$sarjarow[sarjanumero]", "$sarjarow[sarjanumero]", $format_top);

        // Lis‰tiedot
        $kommpreg = preg_replace("/([0-9\.]* EUR|<\/td><td.*?>|&nbsp;|<img width='10px' heigth='10px' src='\.\.\/pics\/vihrea.png'>)/i", " ", $kommentit);
        $kommpreg = preg_replace("/ {2,}/", " ", $kommpreg);

        preg_match("/Tehdaslis‰varusteet:(.*?)<\/table>/", $kommpreg, $xls_komm1);

        $query = "SELECT lisatiedot, Hinta
                  FROM sarjanumeron_lisatiedot
                  WHERE yhtio      = '$kukarow[yhtio]'
                  and liitostunnus = '$sarjarow[tunnus]'";
        $lttres = mysql_query($query) or pupe_error($query);
        $lttrow = mysql_fetch_assoc($lttres);

        $worksheet->writeString($excelrivi, 5, trim(strip_tags(str_ireplace("</tr>", "\n", $xls_komm1[1]."\n".$lttrow["lisatiedot"]))), $format_wrap);

        // Ostohinnat ja kulut
        preg_match("/Ostohinta:### ### ###(.*) /", $text_output,   $xls_hp1);
        preg_match("/Kululasku:### ### ###(.*) /", $text_output,   $xls_hp2);
        preg_match("/Sis‰inen Kulu:### ### ###(.*) /", $text_output, $xls_hp3);
        preg_match("/Lis‰varusteet:### ### ###(.*) /", $text_output, $xls_hp4);
        preg_match("/Ostohinta alv:### ### ###(.*) /", $text_output, $xls_hp5);

        if ($sarjarow["kaytetty"] == '') $xls_hp_ostohinta = round(((float) $xls_hp1[1] + (float) $xls_hp4[1]) * 1.23, 2); // HUOM Aina plus 23% alvia!!!!!
        else $xls_hp_ostohinta = round((float) $xls_hp1[1] + (float) $xls_hp4[1], 2);

        $xls_hp_kulut = ((float) $xls_hp5[1]) - $xls_hp_ostohinta;

        // Ostohinta
        $worksheet->writeNumber($excelrivi, 6, $xls_hp_ostohinta, $format_top);

        // Kulutlaskut (Uusi laite)
        if ($sarjarow["kaytetty"] == '') {
          $worksheet->writeNumber($excelrivi, 7, $xls_hp_kulut, $format_top);
        }
        else {
          $worksheet->writeNumber($excelrivi, 7, "", $format_top);
        }

        // Kulutlaskut (K‰ytetty laite)
        if ($sarjarow["kaytetty"] != '') {
          $worksheet->writeNumber($excelrivi, 8, $xls_hp_kulut, $format_top);
        }
        else {
          $worksheet->writeNumber($excelrivi, 8, "", $format_top);
        }

        // Kate 8%
        $worksheet->writeNumber($excelrivi, 9, round($xls_hp5[1]/0.92, 2), $format_top);

        // Tarjoushinta
        $worksheet->writeNumber($excelrivi, 10, $lttrow["Hinta"], $format_top);

        // Kate EUR tarjoushinta - ostohinta
        $worksheet->writeNumber($excelrivi, 11, round($lttrow["Hinta"]-$xls_hp5[1], 2), $format_top);

        // Kate % tarjoushinta - ostohinta
        $worksheet->writeNumber($excelrivi, 12, @round((($lttrow["Hinta"]-$xls_hp5[1])/$lttrow["Hinta"])*100, 2), $format_top);

        if (function_exists("sarjanumeronlisatiedot_popup")) {
          list($null, $text_output_2, $null, $null, $null) = sarjanumeronlisatiedot_popup($sarjarow["tunnus"], '', '', 'MY', '100%', $lisatiedot);
        }

        preg_match("/Laitehinta:### ### ###(.*) /", $text_output_2, $xls_hp1);
        preg_match("/Lis‰varusteet:### ### ###(.*) /", $text_output_2, $xls_hp2);
        preg_match("/Myyntihinta yht:### ### ###(.*) /", $text_output_2, $xls_hp3);

        // Laitteen myyntihinta
        $worksheet->writeNumber($excelrivi, 13, $xls_hp1[1], $format_top);

        // Lis‰varusteiden myyntihinnat
        $worksheet->writeNumber($excelrivi, 14, $xls_hp2[1], $format_top);

        // Myyntihinta yhteens‰
        if ($sarjarow["kaytetty"] != '') {
          $worksheet->writeNumber($excelrivi, 15, $lttrow["Hinta"], $format_top);
        }
        else {
          $worksheet->writeNumber($excelrivi, 15, $xls_hp3[1], $format_top);
        }

        // Etusi Myyntihinta - Tarjoushinta
        if ($sarjarow["kaytetty"] != '') {
          $worksheet->write($excelrivi, 16, "", $format_top);
        }
        else {
          $worksheet->writeNumber($excelrivi, 16, $xls_hp3[1]-$lttrow["Hinta"], $format_top);
        }

        $worksheet->writeString($excelrivi, 17, $sarjarow["tuotepaikka"], $format_top);

        $excelrivi++;
      }

      if ($kommentit != '') {
        echo "</tr>";
        echo "<tr><td colspan='8'>$kommentit</td>";
        echo "</tr><tr><td class='back'><br></td>";
      }

      echo "</tr>";
    }
  }
  echo "</table><br>";

  if ($tiedosto == "YT") {
    print_pdf($pdf, $valittu_tulostin, $luetkpl);

    unset($pdf);
  }

  if (isset($sarjares) and mysql_num_rows($sarjares) > 0) {

    echo mysql_num_rows($sarjares)." laitetta.<br><br>";

    $luput = lopetus($lopetlinkki, "", TRUE);

    echo "<table>";
    echo "<form action='?$luput' method='post'>";
    echo "<input type='hidden' name='toiminto' value='TULOSTA'>";

    // Rajaukset, EI n‰in, mutta n‰in nyt kuitenkin
    echo "<input type='hidden' name='tul_lisa' value='".urlencode(serialize($lisa.$lisa1))."'>";

    echo "<tr><th>".t("Tarkkuus").":</th>";
    echo "<td><select name='tarkkuus'>";
    echo "<option value=''>".t("N‰yt‰ lis‰varusteet")."</option>";
    echo "<option value='AN'>".t("ƒl‰ n‰yt‰ lis‰varusteita")."</option>";

    echo "</select></td></tr>";

    $sel[$hinnat] = "SELECTED";

    echo "<tr><th>Tyyppi:</th>";
    echo "<td><select name='hinnat'>";
    echo "<option value=''>".t("N‰yt‰ hankintahinnat")."</option>";
    echo "<option value='MY' $sel[MY]>".t("N‰yt‰ myyntihinnat")."</option>";
    echo "<option value='ME' $sel[ME]>".t("Tulosta myyntiesite")."</option>";
    echo "<option value='MEL' $sel[MEL]>".t("Tulosta myyntiesite lis‰sivulla")."</option>";
    echo "<option value='MEY' $sel[MEY]>".t("Tulosta hintalappu")."</option>";
    echo "</select></td></tr>";

    $query = "SELECT *
              FROM kirjoittimet
              WHERE yhtio='$kukarow[yhtio]'
              AND komento != 'EDI'
              ORDER BY kirjoitin";
    $kirre = mysql_query($query) or pupe_error($query);

    echo "<tr><th>Tulostin:</th>";
    echo "<td><select name='valittu_tulostin'>";

    echo "<option value=''>".t("Valitse tulostin")."</option>";

    while ($kirrow = mysql_fetch_array($kirre)) {
      if ($kirrow["tunnus"] == $kukarow["kirjoitin"]) {
        $sel = "SELECTED";
      }
      else {
        $sel = "";
      }

      echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
    }

    echo "</select></td></tr>";

    $sel[$tiedosto] = "SELECTED";

    echo "<tr><th>Tiedostomuoto:</th>";
    echo "<td><select name='tiedosto'>";
    echo "<option value=''>".t("Yksi tiedosto per laite")."</option>";
    echo "<option value='YT' $sel[YT]>".t("Yksi tiedosto")."</option>";
    echo "</select></td></tr>";

    echo "<tr><th>Kpl:</th>";
    echo "<td><input type='text' size='4' name='luetkpl' value='1'></td>";
    echo "<td class='back'><input type='submit' name='$subnimi' value='Tulosta luettelo'></tr>";
    echo "</table></form><br><br>";


    if (isset($worksheet)) {
      $excelnimi = $worksheet->close();

      echo "<table>";
      echo "<tr><th>".t("Tallenna Excel").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='Sarjanumeroraportti.xls'>";
      echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br>";
    }
  }

  require "inc/footer.inc";
}
