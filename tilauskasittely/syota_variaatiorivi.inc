<?php

  if ((!isset($variaatio_tuoteno) or $variaatio_tuoteno == "") and isset($tuoteno) and trim($tuoteno) != "")  $variaatio_tuoteno = $tuoteno;
  if ((!isset($variaatio_tuoteno) or $variaatio_tuoteno == "") and isset($tuoteno_array[0]) and trim($tuoteno_array[0]) != "")  $variaatio_tuoteno = $tuoteno_array[0];

  $variaatio_hakulisa = "";
  $variaatio_i = 1;

  // poistetaan tyhjät arvot arraystä
  if (is_array($variaatio_valinta)) {
    foreach ($variaatio_valinta as $variaatio_key => $variaatio_val) {
      if (trim($variaatio_val) != "") {
        $variaatio_hakulisa .= " JOIN tuotteen_avainsanat as a$variaatio_i ON (a$variaatio_i.yhtio = tuote.yhtio and a$variaatio_i.tuoteno = tuote.tuoteno and a$variaatio_i.laji = 'parametri_$variaatio_key' and a$variaatio_i.selite = '$variaatio_val') ";
        $variaatio_i++;
      }
      else {
        unset($variaatio_valinta[$variaatio_key]);
      }
    }
  }

  if ($variaatio_tuoteno != "") {
    // haetaan kaikki tuotteet, jotka kuuluu tähän ryppääseen
    $query = "  SELECT tuote.tuoteno,
          if(t2.jarjestys = 0 or t2.jarjestys is null, t2.selite, t2.jarjestys) t2j2,
          if(t3.jarjestys = 0 or t3.jarjestys is null, t3.selite, t3.jarjestys) t3j3
          FROM tuotteen_avainsanat
          JOIN tuote on (tuote.yhtio = tuotteen_avainsanat.yhtio and tuote.tuoteno = tuotteen_avainsanat.tuoteno and tuote.status not in ('P','X'))
          LEFT JOIN tuotteen_avainsanat t2 on tuote.yhtio = t2.yhtio and tuote.tuoteno = t2.tuoteno and t2.laji = 'parametri_vari' and t2.kieli = '$yhtiorow[kieli]'
          LEFT JOIN tuotteen_avainsanat t3 on tuote.yhtio = t3.yhtio and tuote.tuoteno = t3.tuoteno and t3.laji = 'parametri_koko' and t3.kieli = '$yhtiorow[kieli]'
          WHERE tuotteen_avainsanat.yhtio = '$kukarow[yhtio]'
          AND tuotteen_avainsanat.laji = 'parametri_variaatio'
          AND tuotteen_avainsanat.kieli = '$yhtiorow[kieli]'
          AND tuotteen_avainsanat.selite = '$variaatio_tuoteno'
          ORDER BY t3j3, tuotteen_avainsanat.tuoteno";
    $variaatiores = mysql_query($query) or pupe_error($query);
  }

  if (is_resource($variaatiores) and mysql_num_rows($variaatiores) > 0) {

    $variaatiorow = mysql_fetch_assoc($variaatiores);

    // haetaan tuotteen parametrit
    $query = "  SELECT *
          from tuotteen_avainsanat
          where yhtio = '$kukarow[yhtio]'
          and laji like 'parametri_%'
          and kieli = '$yhtiorow[kieli]'
          and tuoteno = '$variaatiorow[tuoteno]'";
    $parametrit_res = mysql_query($query) or pupe_error($query);
    $variaatio_check = array();

    while ($parametritrivi = mysql_fetch_array($parametrit_res)) {
      $parametririvi_key = substr($parametritrivi["laji"], 10); // poistetaan "parametri_" -stringi alusta
      $parametririvi_val = trim($parametritrivi["selite"]);
      $variaatio_check[$parametririvi_key] = $parametririvi_val;
    }

    //Onko meillä moniulotteinen variaatio esim. väri ja koko...
    $variaatiot = explode(",", $variaatio_check["variaatio_jako"]);

    // Syöttörivi
    echo "<table>";
    echo "<tr><td class='back' colspan='6'><table><tr><th align='left'>".t("Tuote")."</th>";

    for ($vi = 0; $vi < count($variaatiot); $vi++) {
      echo "<th>".ucfirst($variaatiot[$vi])."</th>";
    }

    echo "</tr>";
    echo "<tr>";
    echo "<td valign='top'><input type='text' name='variaatio_tuoteno' size='25' value='$variaatio_tuoteno'></td>";

    // Tutkitaan kuinka moni variaatioistamme on jo setattu
    for ($vi = 0; $vi < count($variaatiot); $vi++) {

      $thisvariaatio = array();
      mysql_data_seek($variaatiores, 0);

      while ($variaatiorow = mysql_fetch_assoc($variaatiores)) {

        // haetaan tuotteen parametrit
        $query = "  SELECT *
              from tuotteen_avainsanat
              where yhtio = '$kukarow[yhtio]'
              and laji like 'parametri_%'
              and kieli = '$yhtiorow[kieli]'
              and tuoteno = '$variaatiorow[tuoteno]'";
        $parametrit_res = mysql_query($query) or pupe_error($query);
        $this_parametrit = array();

        while ($parametritrivi = mysql_fetch_array($parametrit_res)) {
          $parametririvi_key = substr($parametritrivi["laji"], 10); // poistetaan "parametri_" -stringi alusta
          $parametririvi_val = trim($parametritrivi["selite"]);
          $this_parametrit[$parametririvi_key] = $parametririvi_val;
        }

        if (!in_array($this_parametrit[$variaatiot[$vi]], $thisvariaatio)) $thisvariaatio[] = $this_parametrit[$variaatiot[$vi]];
      }

      echo "<td><select name='variaatio_valinta[$variaatiot[$vi]]' onchange='submit();'>";
      echo "<option value=''>".t("Näytä kaikki")."</option>";

      foreach ($thisvariaatio as $ekavar) {
        if ($ekavar == $variaatio_valinta[$variaatiot[$vi]]) $sel = "SELECTED";
        else $sel = "";
        echo "<option value='$ekavar' $sel>$ekavar</option>";
      }

      echo "</select></td>";
    }

    echo "</tr></table></td></tr>";

    echo "<tr><td class='back'><br></td></tr><tr>";

    echo "<th align='left'>".t("Tuotenumero")."</th>
        <th align='left'>".t("Määrä")."</th>";

    if ($variaaritorivi_osto !== TRUE) {
      echo "<th align='left'>".t("Var")."</th>";
    }

    echo "<th align='left'>".t("Hinta")."</th>";

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      echo "<th align='left'>".t("Ale")."{$alepostfix}</th>";
    }

    if ($variaaritorivi_osto !== TRUE) {
      echo "<th align='left'>".t("Netto")."</th>";
    }

    if ($toim != "PIKATILAUS") {
      echo "<th align='left'>".t("Alv")."</th>";

      if ($yhtiorow['rivinumero_syotto'] != '') {
        echo "<th align='left'></th>";
      }
    }

    echo "<td valign='top' class='back'></td></tr>";

    if ($toim == "PIKATILAUS") {

      echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
          <!--

          function fill_all(tcolumn, tfield) {

            var formi = document.getElementById(tfield).form;

            for (var elementIdx=0; elementIdx<formi.elements.length; elementIdx++) {
              if (formi.elements[elementIdx].type == 'text' && ((formi.elements[elementIdx].id.substring(0,3) == 'ale' && formi.elements[elementIdx].id.substring(0,10) == tcolumn) || (formi.elements[elementIdx].id.substring(0,9) == tcolumn))) {
                formi.elements[elementIdx].value = document.getElementById(tfield).value;
              }
            }
          }

          //-->
          </script>";

      echo "<tr>
          <td></td>
          <td><input type='text' id='kpl_js' onKeyUp='fill_all(\"kpl_array\", \"kpl_js\");' size='5'></td>
          <td><input type='text' id='var_js' onKeyUp='fill_all(\"var_array\", \"var_js\");' size='2'></td>
          <td><input type='text' id='hin_js' onKeyUp='fill_all(\"hin_array\", \"hin_js\");' size='5'></td>";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        echo "  <td><input type='text' id='ale_js_{$alepostfix}' onKeyUp='fill_all(\"ale_array{$alepostfix}\", \"ale_js_{$alepostfix}\");' size='5'></td>";
      }

      echo "  <td><input type='text' id='net_js' onKeyUp='fill_all(\"net_array\", \"net_js\");' size='2'></td></tr>";
    }

    $varilask = 0;

    // haetaan kaikki tuotteet, jotka halutaan nähä
    $query = "  SELECT distinct tuote.tuoteno, tuote.nimitys,
          if(t2.jarjestys = 0 or t2.jarjestys is null, t2.selite, t2.jarjestys) t2j2,
          if(t3.jarjestys = 0 or t3.jarjestys is null, t3.selite, t3.jarjestys) t3j3
          FROM tuotteen_avainsanat
          JOIN tuote on (tuote.yhtio = tuotteen_avainsanat.yhtio and tuote.tuoteno = tuotteen_avainsanat.tuoteno and tuote.status not in ('P','X'))
          LEFT JOIN tuotteen_avainsanat t2 on tuote.yhtio = t2.yhtio and tuote.tuoteno = t2.tuoteno and t2.laji = 'parametri_vari' and t2.kieli = '$yhtiorow[kieli]'
          LEFT JOIN tuotteen_avainsanat t3 on tuote.yhtio = t3.yhtio and tuote.tuoteno = t3.tuoteno and t3.laji = 'parametri_koko' and t3.kieli = '$yhtiorow[kieli]'
          $variaatio_hakulisa
          WHERE tuotteen_avainsanat.yhtio = '$kukarow[yhtio]'
          AND tuotteen_avainsanat.laji = 'parametri_variaatio'
          AND tuotteen_avainsanat.kieli = '$yhtiorow[kieli]'
          AND tuotteen_avainsanat.selite = '$variaatio_tuoteno'
          ORDER BY t3j3, tuotteen_avainsanat.tuoteno";
    $variaatiores = mysql_query($query) or pupe_error($query);

    while ($varirow = mysql_fetch_assoc($variaatiores)) {

      if ($varilask > 0) {
        echo "</tr>";
      }

      echo "<tr>";
      echo "<td valign='top'>$varirow[tuoteno] $varirow[nimitys] <input type='hidden' name='tuoteno_array[$varirow[tuoteno]]' value='$varirow[tuoteno]'></td>
          <td valign='top'><input type='text' id='kpl_array_$varilask' name='kpl_array[$varirow[tuoteno]]' size='5' maxlength='5' value='".$kpl_array[$varirow["tuoteno"]]."'></td>";

      if ($variaaritorivi_osto !== TRUE) {
        if ($toim != "PIKATILAUS") {
          $selh = $selj = $selp = "";

          if ($var_array[$varirow["tuoteno"]] == "H") {
            $selh = "SELECTED";
          }
          elseif ($var_array[$varirow["tuoteno"]] == "J") {
            $selj = "SELECTED";
          }
          elseif ($var_array[$varirow["tuoteno"]] == "P") {
            $selp = "SELECTED";
          }
          elseif ($var_array[$varirow["tuoteno"]] == "W") {
            $selw = "SELECTED";
          }

          echo "<td valign='top'><select name='var_array[$varirow[tuoteno]]'>";
          echo "<option value=''>".t("Oletus")."</option>";
          echo "<option value='J' $selj>".t("JT")."</option>";
          echo "<option value='H' $selh>".t("Hyväksy")."</option>";
          echo "<option value='P' $selp>".t("Puute")."</option>";

          if ($laskurow["tila"] == 'V' and $laskurow["tilaustyyppi"] == 'V') {
            echo "<option value='W' $selw>".t("Varastosta")."</option>";
          }

          echo "</select></td>";
        }
        else {
          echo "<td valign='top'><input type='text' id='var_array_$varilask' name='var_array[$varirow[tuoteno]]' size='2' maxlength='1' value='".$var_array[$varirow["tuoteno"]]."'></td>";
        }
      }

      echo "<td valign='top'><input type='text' id='hin_array_$varilask' name='hinta_array[$varirow[tuoteno]]' size='".(5+$yhtiorow['hintapyoristys']-2)."' maxlength='".(12+$yhtiorow['hintapyoristys']-2)."' value='".$hinta_array[$varirow["tuoteno"]]."'></td>";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        echo "<td valign='top'><input type='text' id='ale_array_{$alepostfix}_$varilask' name='ale_array{$alepostfix}[$varirow[tuoteno]]' size='5' maxlength='6' value='".${'ale_array'.$alepostfix}[$varirow["tuoteno"]]."'></td>";
      }


      if ($variaaritorivi_osto !== TRUE) {
        if ($toim != "PIKATILAUS") {
          $chke = $chkn = "";

          if ($netto_array[$varirow["tuoteno"]] == 'N') {
            $chkn = 'SELECTED';
          }
          elseif($netto_array[$varirow["tuoteno"]] == "E") {
            $chke = 'SELECTED';
          }

          echo "<td valign='top'><select name='netto_array[$varirow[tuoteno]]'>";
          echo "<option value=''>".t("Oletus")."</option>";
          echo "<option value='N' $chkn>".t("N-Netto")."</option>";
          echo "<option value='E' $chke>".t("E-Netto")."</option>";
          echo "</select></td>";
        }
        else {
          echo "<td valign='top'><input type='text' id='net_array_$varilask' name='netto_array[$varirow[tuoteno]]' size='2' maxlength='1' value='".$netto_array[$varirow["tuoteno"]]."'></td>";
        }
      }

      if ($toim != "PIKATILAUS") {
        if (isset($alv) and $alv >= 500) {

          echo "<td valign='top'>";

          if ($alv >= 600) {
            echo t("K.V.");
          }
          else {
            echo t("M.V.");
          }

          echo "</td>";
          echo "<input type='hidden' name='alv' value='".$alv_array[$varirow["tuoteno"]]."'>";
        }
        else {
          if ($variaaritorivi_osto === TRUE and $alv_array[$varirow["tuoteno"]] == '') $alv_array[$varirow["tuoteno"]]=$laskurow['alv'];

          echo "<td valign='top'>".alv_popup_oletus("alv_array[$varirow[tuoteno]]", $alv_array[$varirow["tuoteno"]], $laskurow["maa"])."</td>";
        }

        if ($yhtiorow['rivinumero_syotto'] != '') {
          echo "<th align='left'></th>";
        }
      }

      $varilask++;
    }

    if ($varilask > 0) {
      echo "  <td valign='top' class='back'><input type='submit' value='".t("Lisää")."'> <input type='submit' name='lisaa_jatka' value='".t("Lisää/Jatka")."'></td>
          <td valign='top' class='back'>";

      if ($toim == "PIKATILAUS") {
        echo "  <input type='hidden' name='perheid' value = '$perheid'>
            <input type='hidden' name='perheid2' value = '$perheid2'>
            <input type='hidden' name='takaisin' value = '$takaisin'>
            <input type='submit' name='tyhjenna' value='".t("Tyhjennä")."'> <font class='error'>$varaosavirhe</font></td>";
      }
    }

    echo "</tr>";

    if ($toim != "PIKATILAUS") {
      echo "<tr>";

      if ($variaaritorivi_osto !== TRUE) {
        echo "<th align='left'>".t("Keräysaika")."</th>";

        $toimcspl = 2;
      }
      else {
        $toimcspl = 1;
      }

      echo "<th align='left' colspan='$toimcspl'>".t("Toimitusaika")."</th>
          <th colspan='4' align='left'>".t("Kommentti")."</th>";

      if ($yhtiorow['rivinumero_syotto'] != '') {
        echo "<th align='left'>".t("Rivinumero")."</th>";
      }

      echo "<td valign='top' class='back'></td></tr>";
      echo "<tr>";

      if ($toimaika == "" or $toimaika == "0000-00-00") {
        $toimaika = $laskurow["toimaika"];
      }

      if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
        $kerayspvm = $laskurow["kerayspvm"];
      }

      if ($toimaika == "" or $toimaika == "0000-00-00") {
        $toimkka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $toimvva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $toimppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      }
      else {
        $toimkka = substr($toimaika, 5, 2);
        $toimvva = substr($toimaika, 0, 4);
        $toimppa = substr($toimaika, 8, 2);
      }

      if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
        $kerayskka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $keraysvva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $keraysppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      }
      else {
        $kerayskka = substr($kerayspvm, 5, 2);
        $keraysvva = substr($kerayspvm, 0, 4);
        $keraysppa = substr($kerayspvm, 8, 2);
      }

      if ($variaaritorivi_osto !== TRUE) {
        echo "<td valign='top'><input type='text' size='3' name='keraysppa' value='$keraysppa'><input type='text' size='3' name='kerayskka' value='$kerayskka'><input type='text' size='5' name='keraysvva' value='$keraysvva'></td>";
      }

      echo "<td valign='top' colspan='$toimcspl'><input type='text' size='3' name='toimppa' value='$toimppa'><input type='text' size='3' name='toimkka' value='$toimkka'><input type='text' size='5' name='toimvva' value='$toimvva'></td>";
      echo "<td valign='top' colspan='4'><textarea name='kommentti' cols='32' rows='2' wrap='soft'>$kommentti</textarea></td>";

      if ($yhtiorow['rivinumero_syotto'] != '') {
        echo "<td valign='top'><input type='text' size='2' name='tilrivinumero' value='$rivinumero'></td>";
      }

      if ($yhtiorow["nimityksen_muutos_tilauksella"] == "Y" and ($toim == "RIVISYOTTO" or $toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or ($toim == "TARJOUS" or $laskurow["tilaustyyppi"] == "T"))) {
        echo "</tr>
          <tr>$jarjlisa
            <th colspan='7' align='left'>".t("Tuotteen nimitys")."</th>
            </tr>
          <tr>$jarjlisa
            <td valign='top' colspan='7'><input type='text' maxlength='100' size='75' name='tuotenimitys' value = '$tuotenimitys'></td>";
      }

      echo "<td valign='top' class='back'><input type='submit' name='tyhjenna' value='".t("Tyhjennä")."'>";
    }

    echo "</table></form><br><br>";

    $variaaritorivi = TRUE;
  }
