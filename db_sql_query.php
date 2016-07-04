<?php

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "inc/parametrit.inc";

if (isset($tee)) {
  if ($tee == "lataa_tiedosto") {
    readfile("/tmp/".$tmpfilenimi);
    exit;
  }
}
else {

  echo " <script language='javascript' type='text/javascript'>
      function toggleAll(toggleBox) {
        var currForm = toggleBox.form;
        var isChecked = toggleBox.checked;

        for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
          if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].className == 'toggle shift') {
              currForm.elements[elementIdx].checked = isChecked;
          }
        }
      }
      </script>

      <!-- Enabloidaan shiftill‰ checkboxien chekkaus //-->
      <script src='inc/checkboxrange.js'></script>

      <script language='javascript' type='text/javascript'>
        $(document).ready(function(){
          $(\".shift\").shiftcheckbox();
        });
      </script>";

  echo "<font class='head'>".t("SQL-raportti").":</font><hr>";

  echo "<form method='post' action='db_sql_query.php'>";
  echo "<table><tr>";
  echo "<th>".t("Valitse taulu")."</th></tr>";
  echo "<tr><td><select name='table' onchange='submit();'>";
  echo "<option value=''></option>";

  $query  = "SHOW tables FROM `$dbkanta`";
  $result =  pupe_query($query);

  while ($row = mysql_fetch_array($result)) {

    $query  = "describe $row[0]";
    $fieldresult = pupe_query($query);

    $naytetaanko = FALSE;

    while ($fields = mysql_fetch_assoc($fieldresult)) {
      if ($fields["Field"] == "yhtio") {
        $naytetaanko = TRUE;
        break;
      }
    }

    if ($naytetaanko) {
      $sel = (!empty($table) and $table == $row[0]) ? "SELECTED" : "";
      echo "<option value='$row[0]' $sel>".ucfirst($row[0])."</option>";
    }
  }

  echo "</select></td></tr></table></form><br>";

  $oper_array = array('on' => '=', 'not' => '!=', 'in' => 'in', 'like' => 'like', 'gt' => '>', 'lt' => '<', 'gte' => '>=', 'lte' => '<=');

  function db_piirra_otsikot($dbtaulu) {
    global $table;

    echo "<tr><th>".t("Kentt‰")."</th><th>".t("Valitse")."</th><th>".t("Operaattori")."</th><th>".t("Rajaus")."</th>";

    if ($dbtaulu == $table) {
      echo "<th>".t("J‰rjestys")."</th>";
    }

    echo "</tr>";
  }

  function db_piirra_rivi($fields) {
    global $kukarow, $kentat, $ruksaa, $operaattori, $rajaus, $jarjestys, $table, $sanakirja_kielet;
    $kala = array();

    foreach ($fields as $row) {

      list($dbtaulu, $sarake) = explode(".", $row[0]);

      $chk = "";

      if (!empty($kentat[$row[0]]) and $kentat[$row[0]] == $row[0]) {
        $chk = "CHECKED";
      }
      elseif (is_array($ruksaa) and count($ruksaa) > 0 and in_array(strtoupper($sarake), $ruksaa)) {
        $chk = "CHECKED";
      }

      $sel = array('on' => '', 'not' => '', 'in' => '', 'like' => '', 'gt' => '', 'lt' => '', 'gte' => '', 'lte' => '');
      $sel[$operaattori[$row[0]]] = "SELECTED";

      $class = $dbtaulu == $table ? "toggle shift" : "shift";

      $rivi = "<tr>
                <td>$sarake</td>
                <td><input type='hidden' name='sarakkeet[$row[0]]' value='$row[0]'>
                <input type='checkbox' class='$class' name='kentat[$row[0]]' value='$row[0]' $chk></td>";

      if ((($table == "tuote" and $dbtaulu == "tuotteen_avainsanat") or ($table == "asiakas" and $dbtaulu == "asiakkaan_avainsanat")) and $sarake == "kieli") {

        $rivi .= "<td colspan='2'>";
        foreach ($sanakirja_kielet as $kielikoodi => $kieli) {
          $chk = !empty($rajaus[$row[0]][$kielikoodi]) ? "CHECKED" : "";
          $rivi .= "<input type='checkbox' class='$class' name='rajaus[$row[0]][$kielikoodi]' value='$kielikoodi' $chk> $kieli<br>";
        }
        $rivi .= "</td>";
      }
      elseif ($table == "tuote" and $dbtaulu == "tuotteen_toimittajat" and $sarake == "liitostunnus") {
        $rivi .= "<td colspan='2'>";

        $query = "SELECT toimi.tunnus,
                  toimi.nimi
                  FROM toimi
                  WHERE toimi.yhtio       = '{$kukarow["yhtio"]}'
                  AND toimi.oletus_vienti IN ('C', 'J', 'F', 'K', 'I', 'L')
                  AND toimi.tyyppi        NOT IN ('P', 'PP')
                  ORDER BY toimi.nimi";
        $toimittaja_result = pupe_query($query);

        $rivi .= "<select name='rajaus[$row[0]]'>";

        $chk1 = (!empty($toimittaja) and $toimittaja == "OLETUS") ? "CHECKED" : "";
        $rivi .= "<option value='OLETUS' $chk1>".t("P‰‰toimittaja")."</option>";

        while ($toimittaja = mysql_fetch_assoc($toimittaja_result)) {
          $sel = ($toimittaja["tunnus"] == $rajaus[$row[0]]) ? ' selected' : '';
          $rivi .= "<option value='{$toimittaja["tunnus"]}' $sel>{$toimittaja["nimi"]}</option>";
        }

        $rivi .= "</select></td>";
      }
      elseif ($table == "tuote" and $dbtaulu == "tuotteen_avainsanat" and $sarake == "laji") {
        $rivi .= "<td colspan='2'>";

        $avainsanatyypit = array();
        $avainsanatyypit["nimitys"]                  = t("Tuotteen nimitys");
        $avainsanatyypit["lyhytkuvaus"]              = t("Tuotteen lyhytkuvaus");
        $avainsanatyypit["kuvaus"]                   = t("Tuotteen kuvaus");
        $avainsanatyypit["mainosteksti"]             = t("Tuotteen mainosteksti");
        $avainsanatyypit["tarratyyppi"]              = t("Tuotteen tarratyyppi");
        $avainsanatyypit["sistoimittaja"]            = t("Tuotteen sis‰inen toimittaja");
        $avainsanatyypit["oletusvalinta"]            = t("Tuotteen tilauksen oletusvalinta");
        $avainsanatyypit["osasto"]                   = t("Tuotteen osasto");
        $avainsanatyypit["try"]                      = t("Tuotteen tuoteryhm‰");
        $avainsanatyypit["ps_ala_try"]               = t("PupeShop alaryhm‰");
        $avainsanatyypit["ei_edi_ostotilaukseen"]    = t("Tuotetta ei lis‰t‰ EDI-ostotilaukselle");
        $avainsanatyypit["hammastus"]                = t("Tuotteen hammastus");
        $avainsanatyypit["laatuluokka"]              = t("Tuotteen laatuluokka");
        $avainsanatyypit["synkronointi"]             = t("Tuotteen synkronointi");
        $avainsanatyypit["toimpalautus"]             = t("Palautus toimittajalle");
        $avainsanatyypit["varastopalautus"]          = t("Palautus sallittuihin varastoihin");
        $avainsanatyypit["hinnastoryhmittely"]       = t("hinnastoryhmittely");
        $avainsanatyypit["magento_attribute_set_id"] = t("Magento attribute set ID");

        foreach ($avainsanatyypit as $laji => $nimitys) {
          $chk = !empty($rajaus[$row[0]][$laji]) ? "CHECKED" : "";

          $rivi .= "<input type='checkbox' class='$class' name='rajaus[$row[0]][$laji]' value='$laji' $chk>$nimitys<br>";
        }

        // Tuotteiden avainsanojen laji
        // N‰m‰ on dynaamisia ja k‰ytet‰‰n ainoastaan raporteissa/erikoistapauksissa, johon erikseen hardcoodattu sovittu arvo.
        $sresult = t_avainsana("TUOTEULK");

        while ($srow = mysql_fetch_assoc($sresult)) {
          $chk = !empty($rajaus[$row[0]][$srow["selite"]]) ? "CHECKED" : "";

          $rivi .= "<input type='checkbox' class='$class' name='rajaus[$row[0]][$srow[selite]]' value='$srow[selite]' $chk>$srow[selite] $srow[selitetark]<br>";
        }

        // Tuotteen parametri.
        // K‰ytet‰‰n "tuote-export" -raportissa, "monivalintalaatikot" -listauksessa
        // sek‰ "myyntier‰t ja tuotetetiedot" -n‰kym‰ss‰ (jos se on enabloitu myyntitilaukselle)
        $sresult = t_avainsana("PARAMETRI");

        while ($srow = mysql_fetch_assoc($sresult)) {
          $selite = "parametri_$srow[selite]";
          $chk = !empty($rajaus[$row[0]][$selite]) ? "CHECKED" : "";

          $rivi .= "<input type='checkbox' class='$class' name='rajaus[$row[0]][$selite]' value='$selite' $chk>".t("Tuotteen parametri").": $srow[selitetark]<br>";
        }

        // Tuotteen lis‰tieto.
        // K‰ytet‰‰n ainoastaan "hae ja selaa tuotteita" -n‰kym‰ss‰.
        $lresult = t_avainsana("LISATIETO");

        while ($lrow = mysql_fetch_assoc($lresult)) {
          $selite = "lisatieto_$lrow[selite]";
          $chk = !empty($rajaus[$row[0]][$selite]) ? "CHECKED" : "";

          $rivi .= "<input type='checkbox' class='$class' name='rajaus[$row[0]][$selite]' value='$selite' $chk>".t("Tuotteen lis‰tieto").": $lrow[selitetark]<br>";
        }

        $rivi .= "</td>";
      }
      elseif ($table == "asiakas" and $dbtaulu == "asiakkaan_avainsanat" and $sarake == "laji") {
        $rivi .= "<td colspan='2'>";
        $sresult = t_avainsana("ASAVAINSANA");

        while ($srow = mysql_fetch_assoc($sresult)) {
          $chk = !empty($rajaus[$row[0]][$srow["selite"]]) ? "CHECKED" : "";

          $rivi .= "<input type='checkbox' class='$class' name='rajaus[$row[0]][$srow[selite]]' value='$srow[selite]' $chk>$srow[selitetark]<br>";
        }

        $rivi .= "</td>";
      }
      else {
        $rivi .= "
                  <td><select name='operaattori[$row[0]]'>
                    <option value=''></option>
                    <option value='on'   $sel[on]>=</option>
                    <option value='not'  $sel[not]>!=</option>
                    <option value='in'   $sel[in]>in</option>
                    <option value='like' $sel[like]>like</option>
                    <option value='gt'   $sel[gt]>&gt;</option>
                    <option value='lt'   $sel[lt]>&lt;</option>
                    <option value='gte'  $sel[gte]>&gt;=</option>
                    <option value='lte'  $sel[lte]>&lt;=</option>
                    </select></td>
                  <td><input type='text' size='15' name='rajaus[$row[0]]' value='".$rajaus[$row[0]]."'></td>";
      }

      if ($dbtaulu == $table) {
        $rivi .= "<td><input type='text' size='5'  name='jarjestys[$row[0]]' value='".$jarjestys[$row[0]]."'></td>";
      }

      $rivi .= "</tr>";

      array_push($kala, $rivi);
    }

    return $kala;
  }

  if ($rtee == "AJA" and isset($ruks_pakolliset)) {
    require "inc/pakolliset_sarakkeet.inc";

    list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, $joinattavat, $saakopoistaa, $oletukset, $eisaaollatyhja) = pakolliset_sarakkeet($table);

    if (!is_array($wherelliset)) {
      $ruksaa = $pakolliset;
    }
    else {
      $ruksaa = array_merge($pakolliset, $wherelliset);
    }

    // Oletusaliakset ja onko niiss‰ pakollisia
    $query = "SELECT distinct selite
              FROM avainsana
              WHERE yhtio      = '$kukarow[yhtio]'
              and laji         = 'MYSQLALIAS'
              and selite       like '{$table}.%'
              and selitetark_2 = 'Default'
              and selitetark_3 = 'PAKOLLINEN'";
    $al_res = pupe_query($query);

    if (mysql_num_rows($al_res) > 0) {
      while ($pakollisuuden_tarkistus_rivi = mysql_fetch_assoc($al_res)) {
        $ruksaa[] = strtoupper(str_replace("$table.", "", $pakollisuuden_tarkistus_rivi["selite"]));
      }
    }
  }

  if ($kysely != $edkysely) {
    $rtee = "";
  }

  list($kysely_kuka, $kysely_mika) = explode("#", $kysely);

  // jos ollaan annettu uusirappari nimi, niin unohdetaan dropdowni!
  if ($uusirappari != "") $kysely = "";

  // n‰it‰ muuttujia ei tallenneta
  $ala_tallenna = array("kysely", "uusirappari", "edkysely", "rtee");

  // tallennetaan uusi kysely
  if ($rtee == "AJA" and $uusirappari != '' and $kysely == "") {
    tallenna_muisti($uusirappari, $ala_tallenna);
  }

  // tallennetaan aina myˆs kysely uudestaan jos sit‰ ajetaan (jos on oma rappari)
  if ($rtee == "AJA" and $kysely != '' and $kysely_kuka == $kukarow["kuka"]) {
    tallenna_muisti($kysely_mika, $ala_tallenna);
  }

  // jos kysely on valittuna mutta ei olla viel‰ ajamassa niin haetaan muuttujat
  if ($kysely != "" and $rtee != "AJA") {
    hae_muisti($kysely_mika, $kysely_kuka);
  }

  if ($rtee == "AJA" and !empty($AJASQL) and is_array($kentat)) {

    //* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
    $useslave = 1;

    require "inc/connect.inc";

    $where   = "";
    $selecti = "";
    $joinit  = array();

    $order = "ORDER BY $table.yhtio";
    $joinejaon = (!empty($tuotteentoim_join) or !empty($tuotteenavainsana_join)) ? TRUE : FALSE;

    foreach ($sarakkeet as $kentta) {
      $clean_kentta = $kentta;
      list($taulu, $kentta) = explode(".", $clean_kentta);

      if ($taulu != $table) continue;

      if (!empty($kentat[$clean_kentta])) {
        $selecti .= "$clean_kentta,\n";
      }

      if (!empty($operaattori[$clean_kentta])) {
        if ($operaattori[$clean_kentta] == "in") {
          $raj = "('".str_replace(",", "','", $rajaus[$clean_kentta])."')";
        }
        elseif ($operaattori[$clean_kentta] == "like") {
          $raj = "'".$rajaus[$clean_kentta]."%'";
        }
        else {
          $raj = "'".$rajaus[$clean_kentta]."'";
        }

        $where .= "and $taulu.$kentta ".$oper_array[$operaattori[$clean_kentta]]." ".$raj."\n";
      }
    }

    if (!empty($tuotteentoim_join) and !empty($kentat["tuotteen_toimittajat.liitostunnus"])) {

      if ($rajaus["tuotteen_toimittajat.liitostunnus"] == "OLETUS") {
        $liitos = " and tuotteen_toimittajat.liitostunnus = (select liitostunnus from tuotteen_toimittajat where yhtio = tuote.yhtio and tuoteno = tuote.tuoteno ORDER BY if (jarjestys = 0, 9999, jarjestys) LIMIT 1)";
      }
      else {
        $liitos = " and tuotteen_toimittajat.liitostunnus = '{$rajaus["tuotteen_toimittajat.liitostunnus"]}'";
      }

      $joinit["tuotteen_toimittajat"] = "\nLEFT JOIN tuotteen_toimittajat ON tuote.yhtio=tuotteen_toimittajat.yhtio and tuote.tuoteno=tuotteen_toimittajat.tuoteno {$liitos}";

      foreach ($sarakkeet as $kentta) {

        $clean_kentta = $kentta;
        list($taulu, $kentta) = explode(".", $clean_kentta);

        if ($taulu != "tuotteen_toimittajat") continue;

        if (!empty($kentat[$clean_kentta])) {
          $selecti .= "$clean_kentta as 'tuotteen_toimittajat.$kentta',\n";
        }

        if (!empty($operaattori[$clean_kentta])) {
          if ($operaattori[$clean_kentta] == "in") {
            $raj = "('".str_replace(",", "','", $rajaus[$clean_kentta])."')";
          }
          elseif ($operaattori[$clean_kentta] == "like") {
            $raj = "'".$rajaus[$clean_kentta]."%'";
          }
          else {
            $raj = "'".$rajaus[$clean_kentta]."'";
          }

          $where .= "and $taulu.$kentta ".$oper_array[$operaattori[$clean_kentta]]." ".$raj."\n";
        }
      }
    }

    if (!empty($asiakkaanavainsana_join) and !empty($kentat["asiakkaan_avainsanat.kieli"]) and !empty($kentat["asiakkaan_avainsanat.laji"])) {
      $selecti .= "asiakas.tunnus as 'liitostunnus',\n";

      foreach ($rajaus["asiakkaan_avainsanat.kieli"] as $kieli) {
        foreach ($rajaus["asiakkaan_avainsanat.laji"] as $laji) {

          $taulunimi = "asiakkaan_avainsanat_{$kieli}_{$laji}";
          $joinit[$taulunimi] = "\nLEFT JOIN asiakkaan_avainsanat AS $taulunimi ON asiakas.yhtio=$taulunimi.yhtio and asiakas.tunnus=$taulunimi.liitostunnus and $taulunimi.laji='$laji' and $taulunimi.kieli='$kieli'";

          if (!empty($esitayta_kieli_laji)) {
            $selecti .= "'$kieli' as 'asiakkaan_avainsanat.kieli',\n";
            $selecti .= "'$laji' as 'asiakkaan_avainsanat.laji',\n";
          }

          foreach ($sarakkeet as $kentta) {

            $clean_kentta = $kentta;
            list($taulu, $kentta) = explode(".", $clean_kentta);

            if ($taulu != "asiakkaan_avainsanat") continue;

            if (!empty($esitayta_kieli_laji) and ($kentta == "kieli" or $kentta == "laji")) {
              continue;
            }

            if (!empty($kentat[$clean_kentta])) {
              $selecti .= "$taulunimi.$kentta as 'asiakkaan_avainsanat.$kentta',\n";
            }

            if (!empty($operaattori[$clean_kentta])) {
              if ($operaattori[$clean_kentta] == "in") {
                $raj = "('".str_replace(",", "','", $rajaus[$clean_kentta])."')";
              }
              elseif ($operaattori[$clean_kentta] == "like") {
                $raj = "'".$rajaus[$clean_kentta]."%'";
              }
              else {
                $raj = "'".$rajaus[$clean_kentta]."'";
              }

              $where .= "and $taulunimi.$kentta ".$oper_array[$operaattori[$clean_kentta]]." ".$raj."\n";
            }
          }
        }
      }
    }


    if (!empty($tuotteenavainsana_join) and !empty($kentat["tuotteen_avainsanat.kieli"]) and !empty($kentat["tuotteen_avainsanat.laji"])) {
      foreach ($rajaus["tuotteen_avainsanat.kieli"] as $kieli) {
        foreach ($rajaus["tuotteen_avainsanat.laji"] as $laji) {

          $taulunimi = "tuotteen_avainsanat_{$kieli}_{$laji}";
          $joinit[$taulunimi] = "\nLEFT JOIN tuotteen_avainsanat AS $taulunimi ON tuote.yhtio=$taulunimi.yhtio and tuote.tuoteno=$taulunimi.tuoteno and $taulunimi.laji='$laji' and $taulunimi.kieli='$kieli'";

          if (!empty($esitayta_kieli_laji)) {
            $selecti .= "'$kieli' as 'tuotteen_avainsanat.kieli',\n";
            $selecti .= "'$laji' as 'tuotteen_avainsanat.laji',\n";
          }

          foreach ($sarakkeet as $kentta) {

            $clean_kentta = $kentta;
            list($taulu, $kentta) = explode(".", $clean_kentta);

            if ($taulu != "tuotteen_avainsanat") continue;

            if (!empty($esitayta_kieli_laji) and ($kentta == "kieli" or $kentta == "laji")) {
              continue;
            }

            if (!empty($kentat[$clean_kentta])) {
              $selecti .= "$taulunimi.$kentta as 'tuotteen_avainsanat.$kentta',\n";
            }

            if (!empty($operaattori[$clean_kentta])) {
              if ($operaattori[$clean_kentta] == "in") {
                $raj = "('".str_replace(",", "','", $rajaus[$clean_kentta])."')";
              }
              elseif ($operaattori[$clean_kentta] == "like") {
                $raj = "'".$rajaus[$clean_kentta]."%'";
              }
              else {
                $raj = "'".$rajaus[$clean_kentta]."'";
              }

              $where .= "and $taulunimi.$kentta ".$oper_array[$operaattori[$clean_kentta]]." ".$raj."\n";
            }
          }
        }
      }
    }

    asort($jarjestys);

    foreach ($jarjestys as $kentta => $jarj) {
      if (!empty($jarj)) {
        $order .= ", $kentta";
      }
    }

    $selecti_tt = substr(trim($selecti_tt), 0, -1);
    $selecti = substr(trim($selecti), 0, -1);

    $sqlhaku = "SELECT
                $selecti
                FROM $table ".implode(" ", $joinit)."
                WHERE $table.yhtio = '$kukarow[yhtio]'
                $where
                $order";
    $result = pupe_query($sqlhaku);

    echo "<font class='message'>", query_dump($sqlhaku)."<br>".t("Haun tulos")." ".mysql_num_rows($result)." ".t("rivi‰").".</font><br><br>";

    if (mysql_num_rows($result) > 0) {
      if (include 'inc/pupeExcel.inc') {

        function tee_excel($result) {
          global $excelrivi, $excelnimi;

          $worksheet    = new pupeExcel();
          $format_bold = array("bold" => TRUE);

          $excelrivi = 0;

          for ($i=0; $i < mysql_num_fields($result); $i++) {
            $worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result, $i))), $format_bold);
          }
          $worksheet->write($excelrivi, $i, "TOIMINTO", $format_bold);
          $excelrivi++;

          return array($worksheet, $excelrivi);
        }

        function sulje_excel($worksheet, $filelask) {
          global $excelnimi, $table;

          // We need to explicitly close the worksheet
          $excelnimi = $worksheet->close();
          $loprivi = $filelask*65000;
          $alkrivi = ($loprivi-65000)+1;

          echo "<table>";
          echo "<tr><th>".t("Tallenna tulos")." (".t("Rivit")." $alkrivi-$loprivi):</th>";
          echo "<form method='post' class='multisubmit'>";
          echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
          echo "<input type='hidden' name='kaunisnimi' value='SQLhaku_".$table."_".$filelask.".xlsx'>";
          echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
          echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
          echo "</table><br>";
        }

        if ($table == "tuote" and in_array("tuote.vienti", $kentat)) {
          $maaryhmaquery = "SELECT *
                            FROM avainsana
                            WHERE yhtio  = '{$kukarow['yhtio']}'
                            and laji     = 'maaryhma'
                            and selite  != ''
                            ORDER BY jarjestys";
          $maaryhmares = pupe_query($maaryhmaquery);
          $maaryhma_kaytossa = mysql_num_rows($maaryhmares) > 0 ? true : false;

          if ($maaryhma_kaytossa) {
            for ($i=0; $i < mysql_num_fields($result); $i++) {
              if (mysql_field_name($result, $i) == 'vienti') {
                $vienti_indx = $i;
                break;
              }
            }
          }
        }
        else {
          $vienti_indx = false;
          $maaryhma_kaytossa = false;
        }

        $lask = 0;
        $filelask = 1;

        list($worksheet, $excelrivi) = tee_excel($result);

        while ($row = mysql_fetch_row($result)) {
          $lask++;

          if ($lask % 65000 == 0) {
            sulje_excel($worksheet, $filelask);
            $filelask++;

            list($worksheet, $excelrivi) = tee_excel($result);
          }

          for ($i=0; $i<mysql_num_fields($result); $i++) {

            if (mysql_field_type($result, $i) == 'real') {
              $worksheet->writeNumber($excelrivi, $i, $row[$i]);
            }
            else {
              if ($maaryhma_kaytossa and $vienti_indx == $i) {
                $query = "SELECT *
                          FROM avainsana
                          WHERE yhtio = '{$kukarow['yhtio']}'
                          and laji    = 'maaryhma'
                          and selite  = '{$row[$i]}'";
                $maaryhmares = pupe_query($query);

                if (mysql_num_rows($maaryhmares) != 0) {
                  $maaryhmarow = mysql_fetch_assoc($maaryhmares);
                  $row[$i]     = $maaryhmarow['selitetark'];
                }
              }

              $worksheet->writeString($excelrivi, $i, $row[$i]);
            }
          }

          $j=0;

          if (!empty($selecti_tt)) {

            $query = "SELECT
                      $selecti_tt
                      FROM tuotteen_toimitajat
                      WHERE yhtio = '$kukarow[yhtio]'
                      $where
                      $order";
            $res = pupe_query($sqlhaku);
            $row = mysql_fetch_assoc($res);

            for ($j=0; $j<mysql_num_fields($res); $j++) {
              $fieldname = mysql_field_name($res, $j);

              if (mysql_field_type($res, $j) == 'real') {
                $worksheet->writeNumber($excelrivi, $i+$j, $row[$fieldname]);
              }
              else {
                $worksheet->writeString($excelrivi, $i+$j, $row[$fieldname]);
              }
            }
          }

          $worksheet->writeString($excelrivi, $i+$j, "MUUTA");
          $excelrivi++;
        }

        sulje_excel($worksheet, $filelask);
      }
    }
  }

  if ($table!='') {

    $fields = array();

    $query  = "SHOW columns from $table";
    $fieldres =  pupe_query($query);

    while ($row = mysql_fetch_array($fieldres)) {
      $row[0] = $table.".".$row[0];
      $fields[] = $row;
    }

    if ($table == "tuote" and !empty($tuotteentoim_join)) {
      $tuotteen_toimittajat = array();
      $query  = "SHOW columns from tuotteen_toimittajat";
      $fieldres =  pupe_query($query);

      while ($row = mysql_fetch_array($fieldres)) {
        if ($row[0] == "tuoteno" or $row[0] == "yhtio") continue;
        $row[0] = "tuotteen_toimittajat.".$row[0];

        $tuotteen_toimittajat[] = $row;
      }
    }

    if ($table == "tuote" and !empty($tuotteenavainsana_join)) {
      $tuotteen_avainsanat = array();
      $query  = "SHOW columns from tuotteen_avainsanat";
      $fieldres =  pupe_query($query);

      while ($row = mysql_fetch_array($fieldres)) {
        if ($row[0] == "tuoteno" or $row[0] == "yhtio") continue;
        $row[0] = "tuotteen_avainsanat.".$row[0];

        $tuotteen_avainsanat[] = $row;
      }
    }

    if ($table == "asiakas" and !empty($asiakkaanavainsana_join)) {
      $asiakkaan_avainsanat = array();
      $query  = "SHOW columns from asiakkaan_avainsanat";
      $fieldres =  pupe_query($query);

      while ($row = mysql_fetch_array($fieldres)) {
        if ($row[0] == "liitostunnus" or $row[0] == "yhtio") continue;
        $row[0] = "asiakkaan_avainsanat.".$row[0];

        $asiakkaan_avainsanat[] = $row;
      }
    }

    echo "<form name='sql' class='sql' method='post' autocomplete='off'>";
    echo "<input type='hidden' name='table' value='$table'>";
    echo "<input type='hidden' name='rtee' value='AJA'>";
    echo "<input type='hidden' name='edkysely' value='$kysely'>";

    echo "<table>";
    echo "<tr><td>".t("Tallenna kysely").":</td><td><input type='text' size='20' name='uusirappari' value=''></td></tr>";
    echo "<tr><td>".t("Valitse kysely").":</td><td>";

    // tehd‰‰n "serializoitua" dataa ni etsit‰‰n t‰ll‰ vain t‰m‰n tablen tallennettuja kyselyit‰...
    $data = "\"table\";s:".strlen($table).":\"$table\"";

    // Haetaan tallennetut kyselyt
    $query = "SELECT distinct kuka.nimi, kuka.kuka, tallennetut_parametrit.nimitys
              FROM tallennetut_parametrit
              JOIN kuka on (kuka.yhtio = tallennetut_parametrit.yhtio and kuka.kuka = tallennetut_parametrit.kuka)
              WHERE tallennetut_parametrit.yhtio  = '$kukarow[yhtio]'
              and tallennetut_parametrit.sovellus = '$_SERVER[SCRIPT_NAME]'
              and tallennetut_parametrit.data     like '%$data%'
              ORDER BY tallennetut_parametrit.nimitys";
    $sresult = pupe_query($query);

    echo "<select name='kysely' onchange='submit()'>";
    echo "<option value=''>".t("Valitse")."</option>";

    while ($srow = mysql_fetch_array($sresult)) {

      $sel = '';
      if ($kysely == $srow["kuka"]."#".$srow["nimitys"]) {
        $sel = "selected";
      }

      echo "<option value='$srow[kuka]#$srow[nimitys]' $sel>$srow[nimitys] ($srow[nimi])</option>";
    }
    echo "</select>";

    echo "</td></tr>";
    echo "</table><br>";

    echo "<table>";
    echo "<tr><td>".t("Ruksaa sis‰‰nluvussa pakolliset kent‰t").":</td><td><input type='submit' name='ruks_pakolliset' value='".t("Ruksaa")."'></td></tr>";

    if ($table == "tuote") {
      $chk1 = !empty($tuotteentoim_join) ? "CHECKED" : "";
      $chk2 = !empty($tuotteenavainsana_join) ? "CHECKED" : "";

      echo "<tr><td>".t("Tuotteen toimittajatiedot").":</td><td><input type='checkbox' name='tuotteentoim_join' onclick='submit();' $chk1></td></tr>";
      echo "<tr><td>".t("Tuotteen parametrit").":</td><td><input type='checkbox' name='tuotteenavainsana_join' onclick='submit();' $chk2></td></tr>";
    }

    if ($table == "asiakas") {
      $chk1 = !empty($asiakkaanavainsana_join) ? "CHECKED" : "";

      echo "<tr><td>".t("Asiakkaan avainsanat").":</td><td><input type='checkbox' name='asiakkaanavainsana_join' onclick='submit();' $chk1></td></tr>";
    }

    echo "</table><br>";

    echo "<table>";
    echo "<tr>";

    echo "<td class='back ptop'>";
    echo "<table>";
    db_piirra_otsikot($table);

    $kala = db_piirra_rivi($fields);

    foreach ($kala as $rivi) {
      echo "$rivi";
    }

    echo "<tr><td class='back'>".t("Ruksaa kaikki")."</td><td class='back'><input type='checkbox' onclick='toggleAll(this);'></td></tr>";
    echo "</table>";
    echo "</td>";

    if (!empty($tuotteentoim_join)) {
      echo "<td class='back ptop'>";
      echo "<table>";
      db_piirra_otsikot("tuotteen_toimittajat");

      foreach (db_piirra_rivi($tuotteen_toimittajat) as $rivi) {
        echo "$rivi";
      }
      echo "</table>";
      echo "</td>";
    }

    if (!empty($tuotteenavainsana_join)) {
      echo "<td class='back ptop'>";
      echo "<table>";
      db_piirra_otsikot("tuotteen_avainsanat");

      foreach (db_piirra_rivi($tuotteen_avainsanat) as $rivi) {
        echo "$rivi";
      }
      echo "</table>";

      echo "<br>".t("Esit‰yt‰ kieli ja laji").": ";
      $chk1 = !empty($esitayta_kieli_laji) ? "CHECKED" : "";
      echo "<input type='checkbox' name='esitayta_kieli_laji' $chk1>";
      echo "</td>";
    }

    if (!empty($asiakkaanavainsana_join)) {
      echo "<td class='back ptop'>";
      echo "<table>";
      db_piirra_otsikot("asiakkaan_avainsanat");

      foreach (db_piirra_rivi($asiakkaan_avainsanat) as $rivi) {
        echo "$rivi";
      }
      echo "</table>";

      echo "<br>".t("Esit‰yt‰ kieli ja laji").": ";
      $chk1 = !empty($esitayta_kieli_laji) ? "CHECKED" : "";
      echo "<input type='checkbox' name='esitayta_kieli_laji' $chk1>";
      echo "</td>";
    }

    echo "</tr>";
    echo "</table>";


    echo "<br><input type='submit' name='AJASQL' value='".t("Suorita")."'>";
    echo "</form>";
  }

  require "inc/footer.inc";
}
