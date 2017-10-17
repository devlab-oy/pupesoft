<?php

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

echo "<font class='head'>".t("Tulosta osoitetarrat")."</font><hr>";

if (isset($TULOSTA) and $TULOSTA != "") {
  $tee = "TULOSTA";
}


if ($tee == "TULOSTA" and count($otunnus) == 0) {
  $tee = "";
}

if ($tee == "TULOSTA") {
  $tulostimet[0] = 'Tarrat';

  if (is_array($otunnus)) {
    $otunnus = implode(",", $otunnus);
  }

  if (isset($rajaus)) {
    $rajaus = unserialize(urldecode($rajaus));

    foreach ($rajaus as $a => $b) {
      if (substr($a, 0, 5) == "haku_") {
        $a = str_replace("haku_", "", $a);

        ${"haku"}[$a] = $b;
      }
      else {
        ${$a} = $b;
      }
    }
  }

  $rajaus = array();
  $rajaus["asmemo_viesti"]     = $asmemo_viesti;
  $rajaus["tarra_aineisto"]     = $tarra_aineisto;
  $rajaus["raportti"]       = $raportti;
  $rajaus["toimas"]         = $toimas;
  $rajaus["as_yht_tiedot"]     = $as_yht_tiedot;
  $rajaus["asiakas_segmentin_yhteystiedot"]  = $asiakas_segmentin_yhteystiedot;
  $rajaus["limitti"]         = $limitti;
  $rajaus["negaatio_haku"]     = $negaatio_haku;
  $rajaus["lisaraj_haku"]     = $lisaraj_haku;
  $rajaus["ojarj"]         = $ojarj;

  foreach ($haku as $a => $b) {
    $ind = "haku_$a";
    $rajaus[$ind] = $b;
  }

  $rajaus = urlencode(serialize($rajaus));

  if ($raportti != "EX" and count($komento) == 0) {
    require "inc/valitse_tulostin.inc";
  }

  $joinilisa = "";
  $selectilisa = "";

  if ($as_yht_tiedot == 'on') {
    if ($aytunnus == 'on') {
      $selectilisa = ", yht.nimi AS yht_nimi, yht.titteli AS yht_titteli, yht.email yht_email, yht.puh yht_puhelin, yht.tunnus yht_id";
    }
    else {
      $selectilisa = ", yht.nimi AS yht_nimi, yht.titteli AS yht_titteli, yht.email yht_email, yht.puh yht_puhelin";
    }
    $joinilisa = " LEFT JOIN yhteyshenkilo yht ON yht.yhtio = asiakas.yhtio and yht.liitostunnus = asiakas.tunnus and yht.tyyppi = 'A' ";
  }

  if ($asiakas_segmentin_yhteystiedot == 'on') {
    $mul_asiakas = array();
    for ($i = 1; $i <= $dynaaminenasiakasmaxsyvyys; $i++) {
      $muuttuja = "mul_asiakas{$i}";
      $mul_asiakas = array_merge($mul_asiakas, ${$muuttuja});
    }

    if ($aytunnus == 'on') {
      $selectilisa = ", yht.nimi AS yht_nimi, yht.titteli AS yht_titteli, yht.email yht_email, yht.puh yht_puhelin, yht.tunnus yht_id";
    }
    else {
      $selectilisa = ", yht.nimi AS yht_nimi, yht.titteli AS yht_titteli, yht.email yht_email, yht.puh yht_puhelin";
    }

    $joinilisa = "  JOIN yhteyshenkilo yht
            ON ( yht.yhtio = asiakas.yhtio
              AND yht.liitostunnus = asiakas.tunnus
              AND yht.tyyppi = 'A'
              AND yht.rooli IN ('".implode("','", $mul_asiakas)."') )";
  }

  $query = "SELECT asiakas.* $selectilisa
            FROM asiakas
            $joinilisa
            WHERE asiakas.yhtio = '$kukarow[yhtio]'
            and asiakas.tunnus  in ($otunnus)";
  $res = pupe_query($query);

  $laskuri = 1;
  $sarake  = 1;
  $sisalto = "";

  if ($raportti == "33") {
    $rivinpituus_ps  = 28;
    $rivinpituus  = 27;
    $sarakkeet     = 3;
    $rivit       = 11;
    $sisalto .= "\n";
    $sisalto .= "\n";
    $sisalto .= "\n";
  }
  elseif ($raportti == "24") {
    $rivinpituus_ps  = 28;
    $rivinpituus  = 27;
    $sarakkeet     = 3;
    $rivit       = 8;

    if ($as_yht_tiedot == 'on' or $asiakas_segmentin_yhteystiedot == 'on') {
      $sisalto .= "\n";
      $sisalto .= "\n";
    }
  }
  else {
    include 'inc/pupeExcel.inc';

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi    = 0;
    $excelsarake = 0;

    $worksheet->writeString($excelrivi, $excelsarake, t("Nimi"), $format_bold);
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Nimitarkenne"), $format_bold);
    $excelsarake++;

    if ($as_yht_tiedot == 'on' or $asiakas_segmentin_yhteystiedot == 'on') {
      $worksheet->writeString($excelrivi, $excelsarake, t("Yhteyshenkilö"), $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, t("Titteli"), $format_bold);
      $excelsarake++;
    }

    $worksheet->writeString($excelrivi, $excelsarake, t("Osoite"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Postino"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Postitp"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Maa"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Sähköpostiosoite"), $format_bold);
    $excelsarake++;

    if ($as_yht_tiedot == 'on') {
      $worksheet->writeString($excelrivi, $excelsarake, t("Sähköpostiosoite"), $format_bold);
      $excelsarake++;
    }
    if ($aytunnus == 'on') {
      $worksheet->writeString($excelrivi, $excelsarake, t("Asiakas-id"), $format_bold);
      $excelsarake++;
      if ($as_yht_tiedot == 'on') {
        $worksheet->writeString($excelrivi, $excelsarake, t("Yht henkilö-id"), $format_bold);
        $excelsarake++;
      }
    }

    $excelrivi++;
  }

  while ($row = mysql_fetch_array($res)) {

    if ($yhtiorow["kalenterimerkinnat"] == "") {
      $kysely = "INSERT INTO kalenteri
                 SET tapa     = '".t("Osoitetarrat")."',
                 asiakas      = '$row[ytunnus]',
                 liitostunnus = '$row[tunnus]',
                 kuka         = '$kukarow[kuka]',
                 yhtio        = '$kukarow[yhtio]',
                 tyyppi       = 'Memo',
                 pvmalku      = now(),
                 kentta01     = '$kukarow[nimi] tulosti osoitetarrat.\n$asmemo_viesti',
                 laatija      = '$kukarow[kuka]',
                 luontiaika   = now()";
      $result = pupe_query($kysely);
    }

    // käytetään toim_ tietoja jos niin halutaan
    if ($_POST['toimas'] == 'on') {

      // tarkistetaan tiedot
      $nimi    = (trim($row['toim_nimi']) != '')       ? true : false;
      $osoite  = (trim($row['toim_osoite']) != '')     ? true : false;
      $postino = (trim($row['toim_postino']) != ''
        and $row['toim_postino'] != '00000') ? true : false;
      $postitp = (trim($row['toim_postitp']) != '')    ? true : false;
      $maa     = (trim($row['toim_maa']) != '')        ? true : false;

      // ovatko tiedot validit
      if ($nimi and $osoite and $postino and $postitp and $maa) {
        $row['nimi']     = $row['toim_nimi'];
        $row['nimitark'] = $row['toim_nimitark'];
        $row['osoite']   = $row['toim_osoite'];
        $row['postino']  = $row['toim_postino'];
        $row['postitp']  = $row['toim_postitp'];
        $row['maa']      = $row['toim_maa'];
      }
    }

    if ($raportti == "EX") {
      $excelsarake = 0;

      $worksheet->writeString($excelrivi, $excelsarake, $row["nimi"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $row["nimitark"]);
      $excelsarake++;

      if ($as_yht_tiedot == 'on' or $asiakas_segmentin_yhteystiedot == 'on') {
        $worksheet->writeString($excelrivi, $excelsarake, $row["yht_nimi"]);
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, $row["yht_titteli"]);
        $excelsarake++;
      }

      $worksheet->writeString($excelrivi, $excelsarake, $row["osoite"]);
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, $row["postino"]);
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, $row["postitp"]);
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, $row["maa"]);
      $excelsarake++;

      if ($as_yht_tiedot == 'on' or $asiakas_segmentin_yhteystiedot == 'on') {
        $worksheet->writeString($excelrivi, $excelsarake, $row["yht_email"]);
        $excelsarake++;

        $worksheet->writeString($excelrivi, $excelsarake, $row["yht_puhelin"]);
        $excelsarake++;
      }
      else {
        $worksheet->writeString($excelrivi, $excelsarake, $row["email"]);
        $excelsarake++;
      }

      if ($aytunnus == 'on') {
        $worksheet->writeString($excelrivi, $excelsarake,$row["tunnus"]);
        $excelsarake++;
        if ($as_yht_tiedot == 'on') {
          $worksheet->writeString($excelrivi, $excelsarake, $row["yht_id"]);
          $excelsarake++;
        }
      }

      $excelrivi++;
    }
    else {
      if ($sarake == 3) {
        $lisa = " ";
      }
      else {
        $lisa = "";
      }

      $sisalto .= sprintf('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["nimi"]))."\n";
      $sisalto .= sprintf('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["nimitark"]))."\n";
      if (($as_yht_tiedot == 'on' or $asiakas_segmentin_yhteystiedot == 'on') and $row["yht_nimi"] != '') {
        $sisalto .= sprintf('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["yht_nimi"]))."\n";
      }
      $sisalto .= sprintf('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["osoite"]))."\n";
      $sisalto .= sprintf('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["postino"]." ".$row["postitp"]))."\n";
      $sisalto .= sprintf('%-'.$rivinpituus.'.'.$rivinpituus.'s', " $lisa".trim($row["maa"]))."\n";

      if (($as_yht_tiedot == 'on' or $asiakas_segmentin_yhteystiedot == 'on') and $row["yht_nimi"] != '') {
        $sisalto .= "\n";
      }
      else {
        $sisalto .= "\n\n";
      }

      if ($raportti == "24" and $laskuri != $rivit) {
        $sisalto .= "\n\n\n";
      }

      if ($raportti == "33" and $laskuri == ($rivit-1)) {
        $sisalto .= "\n";
        $sisalto .= "\n";
        $sisalto .= "\n";
        $sisalto .= "\n";
        $laskuri++;
      }

      if ($laskuri == $rivit) {
        if ($raportti == "33") {
          $sisalto .= "\n";
          $sisalto .= "\n";
          $sisalto .= "\n";
        }

        $laskuri = 0;
        $sarake++;
      }

      $laskuri++;
    }
  }

  if ($raportti == "EX") {

    $excelnimi = $worksheet->close();

    echo "<form method='post' class='multisubmit'>";
    echo "<table>";
    echo "<tr><th>".t("Tallenna tulos").":</th>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Osoitetiedot.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
    echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr>";
    echo "</table></form><br>";
  }
  else {
    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $filenimi = "/tmp/CRM-Osoitetarrat-".md5(uniqid(mt_rand(), true)).".txt";
    $fh = fopen($filenimi, "w+");
    fputs($fh, $sisalto);
    fclose($fh);

    $params = array(
      'chars'    => $rivinpituus_ps,
      'columns'  => $sarakkeet,
      'filename' => $filenimi,
      'major'    => 'columns',
      'mode'     => 'portrait',
    );

    // konveroidaan postscriptiksi
    $filenimi_ps = pupesoft_a2ps($params);

    // itse print komento...
    if ($komento["Tarrat"] == 'email') {
      $liite = "/tmp/CRM-Osoitetarrat-".md5(uniqid(mt_rand(), true)).".pdf";
      $kutsu = "Tarrat";
      $ctype = "pdf";

      system("ps2pdf -sPAPERSIZE=a4 {$filenimi_ps} $liite");

      require "inc/sahkoposti.inc";
    }
    else {
      $cmd = $komento["Tarrat"]." {$filenimi_ps}";
      $line = exec($cmd);
    }

    //poistetaan tmp file samantien kuleksimasta...
    unlink($filenimi);
    unlink($filenimi_ps);

    echo "<br>".t("Tarrat tulostuu")."!<br><br>";
  }

  $tee = '';
}

// Nyt selataan
if ($tee == '') {

  echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
    <!--

    function toggleAll(toggleBox) {

      var currForm = toggleBox.form;
      var isChecked = toggleBox.checked;
      var nimi = toggleBox.name;

      for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
        if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,6) == nimi) {
          currForm.elements[elementIdx].checked = isChecked;
        }
      }
    }

    //-->
    </script>";

  echo "<form method = 'post'>
  <input type='hidden' name='tarra_aineisto' value='$tarra_aineisto'>";

  $monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA", "ASIAKASPIIRI", "ASIAKASMYYJA", "ASIAKASTILA", "<br>DYNAAMINEN_ASIAKAS");
  $monivalintalaatikot_normaali = array();

  require "tilauskasittely/monivalintalaatikot.inc";

  $kentat = "asiakas.nimi, asiakas.osoite, asiakas.postino, asiakas.postitp, asiakas.maa, asiakas.osasto, asiakas.ryhma, asiakas.piiri, asiakas.flag_1, asiakas.flag_2, asiakas.flag_3, asiakas.flag_4";

  $array = explode(",", $kentat);
  $count = count($array);

  for ($i=0; $i<=$count; $i++) {
    if (strlen($haku[$i]) > 0) {

      if (isset($negaatio_haku) and $negaatio_haku == 'on') {
        $lisa .= " and " . $array[$i] . " not like '%" . $haku[$i] . "%'";
      }
      else {
        $lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
      }

      $ulisa .= "&haku[" . $i . "]=" . $haku[$i];
    }
  }

  if (strlen($ojarj) > 0) {
    $jarjestys = $ojarj;
  }
  else {
    $jarjestys = 'asiakas.nimi';
  }

  $limit = "";

  if ($tarra_aineisto != "") {
    $lisa .= " and asiakas.tunnus in ($tarra_aineisto) ";
  }
  elseif (isset($limitti) and $limitti != "KAIKKI" and (int) $limitti > 0) {
    $limit = " LIMIT $limitti ";
  }
  elseif (!isset($limitti)) {
    $limit = " LIMIT 1000 ";
  }

  $asiakas_yhteeyshenkilo_join = "";
  if (!empty($asiakas_segmentin_yhteystiedot)) {
    $mul_asiakas = array();
    for ($i = 1; $i <= $dynaaminenasiakasmaxsyvyys; $i++) {
      $muuttuja = "mul_asiakas{$i}";
      $mul_asiakas = array_merge($mul_asiakas, ${$muuttuja});
    }
    $asiakas_yhteeyshenkilo_join = "JOIN yhteyshenkilo
                    ON ( yhteyshenkilo.yhtio = asiakas.yhtio
                      AND yhteyshenkilo.liitostunnus = asiakas.tunnus
                      AND yhteyshenkilo.rooli IN ('".implode("','", $mul_asiakas)."'))";
  }

  //haetaan omat asiakkaat
  $query = "SELECT asiakas.nimi, asiakas.osoite, asiakas.postino, asiakas.postitp, asiakas.maa, asiakas.osasto, asiakas.ryhma, asiakas.piiri, asiakas.flag_1, asiakas.flag_2, asiakas.flag_3, asiakas.flag_4, asiakas.tunnus
            FROM asiakas
            {$asiakas_yhteeyshenkilo_join}
            WHERE asiakas.yhtio  = '$kukarow[yhtio]'
            and asiakas.laji    != 'P'
            and asiakas.nimi    != ''
            $lisa
            ORDER BY $jarjestys
            $limit";
  $result = pupe_query($query);
  $lim = "";
  $lim[$limitti] = "SELECTED";

  echo "<table>";
  echo "<tr><th>".t("Rivimäärärajaus").":</th>
      <td><select name='limitti' onchange='submit();'>
      <option value='1000'   $lim[1000]>1000</option>
      <option value='5000'   $lim[5000]>5000</option>
      <option value='10000'  $lim[10000]>10000</option>
      <option value='KAIKKI' $lim[KAIKKI]>".t("Ei rajaa")."</option>
      </select></td></tr>";

  $neg_chk = '';

  if (isset($negaatio_haku) and $negaatio_haku == 'on') {
    $neg_chk = ' checked';
  }

  echo "<tr><th valign='bottom'>".t("Hakuehtojen negaatio")."</th><td><input type='checkbox' name='negaatio_haku' $neg_chk></th></tr>";

  $lis_chk = '';

  if (isset($lisaraj_haku) and $lisaraj_haku == 'on') {
    $lis_chk = ' checked';
  }

  if (isset($asiakas_segmentin_yhteystiedot) and $asiakas_segmentin_yhteystiedot == 'on') {
    $asiakas_segmentin_yhteystiedot_chk = 'CHECKED';
  }

  echo "<tr><th valign='bottom'>".t("Näytä lisärajaukset")."</th><td><input type='checkbox' name='lisaraj_haku' $lis_chk></td></tr>";
  echo "<tr><th>".t("Luo aineisto vain valitun asiakaskategorian yhteyshenkilön osoitetiedoista").":</th><td><input type='checkbox' name='asiakas_segmentin_yhteystiedot' value='on' onclick='submit();' $asiakas_segmentin_yhteystiedot_chk /></td></tr>";

  echo "</table><br>";

  echo "<table><tr>";
  echo "<th></th>";

  $urllisa = "&asmemo_viesti=$asmemo_viesti&tarra_aineisto=$tarra_aineisto&raportti=$raportti&toimas=$toimas&as_yht_tiedot=$as_yht_tiedot&asiakas_segmentin_yhteystiedot=$asiakas_segmentin_yhteystiedot&limitti=$limitti&negaatio_haku=$negaatio_haku&lisaraj_haku=$lisaraj_haku".$ulisa;

  echo "<th nowrap><a href='$PHP_SELF?ojarj=asiakas.nimi$urllisa'>".t("Nimi")."</a>";
  echo "<br><input type='text' size='10' name = 'haku[0]'  value = '$haku[0]'></th>";
  echo "<th nowrap><a href='$PHP_SELF?ojarj=asiakas.osoite$urllisa'>".t("Osoite")."</a>";
  echo "<br><input type='text' size='10' name = 'haku[1]'  value = '$haku[1]'></th>";
  echo "<th nowrap><a href='$PHP_SELF?ojarj=asiakas.postino$urllisa'>".t("Postino")."</a>";
  echo "<br><input type='text' size='7' name = 'haku[2]'  value = '$haku[2]'></th>";
  echo "<th nowrap><a href='$PHP_SELF?ojarj=asiakas.postitp$urllisa'>".t("Postitp")."</a>";
  echo "<br><input type='text' size='10' name = 'haku[3]'  value = '$haku[3]'></th>";
  echo "<th nowrap><a href='$PHP_SELF?ojarj=asiakas.maa$urllisa'>".t("Maa")."</a>";
  echo "<br><input type='text' size='3' name = 'haku[4]'  value = '$haku[4]'></th>";
  echo "<th nowrap><a href='$PHP_SELF?ojarj=asiakas.osasto$urllisa'>".t("Osasto")."</a>";
  echo "<br><input type='text' size='3' name = 'haku[5]'  value = '$haku[5]'></th>";
  echo "<th nowrap><a href='$PHP_SELF?ojarj=asiakas.ryhma$urllisa'>".t("Ryhma")."</a>";
  echo "<br><input type='text' size='3' name = 'haku[6]'  value = '$haku[6]'></th>";
  echo "<th nowrap><a href='$PHP_SELF?ojarj=asiakas.piiri$urllisa'>".t("Piiri")."</a>";
  echo "<br><input type='text' size='3' name = 'haku[7]'  value = '$haku[7]'></th>";

  if (isset($lisaraj_haku) and $lisaraj_haku == 'on') {
    echo "<th nowrap><a href='$PHP_SELF?ojarj=asiakas.flag_1$urllisa'>".t("Muuta 1")."</a>";
    echo "<br><input type='text' size='3' name = 'haku[8]'  value = '$haku[8]'></th>";
    echo "<th nowrap><a href='$PHP_SELF?ojarj=asiakas.flag_2$urllisa'>".t("Muuta 2")."</a>";
    echo "<br><input type='text' size='3' name = 'haku[9]'  value = '$haku[9]'></th>";
    echo "<th nowrap><a href='$PHP_SELF?ojarj=asiakas.flag_3$urllisa'>".t("Muuta 3")."</a>";
    echo "<br><input type='text' size='3' name = 'haku[10]' value = '$haku[10]'></th>";
    echo "<th nowrap><a href='$PHP_SELF?ojarj=asiakas.flag_4$urllisa'>".t("Muuta 4")."</a>";
    echo "<br><input type='text' size='3' name = 'haku[11]' value = '$haku[11]'></th>";
  }

  echo "<td class='back' valign='bottom'><input type='submit' class='hae_btn' value='".t("Etsi")."'></td></tr>";

  while ($trow = mysql_fetch_assoc($result)) {

    echo "<tr>";
    echo "<td><input type='checkbox' name = 'otunnus[]' value = '$trow[tunnus]' CHECKED></td>";
    echo "<td>$trow[nimi]</td>";
    echo "<td>$trow[osoite]</td>";
    echo "<td>$trow[postino]</td>";
    echo "<td>$trow[postitp]</td>";
    echo "<td>$trow[maa]</td>";
    echo "<td>$trow[osasto]</td>";
    echo "<td>$trow[ryhma]</td>";
    echo "<td>$trow[piiri]</td>";


    if (isset($lisaraj_haku) and $lisaraj_haku == 'on') {
      echo "<td>$trow[flag_1]</td>";
      echo "<td>$trow[flag_2]</td>";
      echo "<td>$trow[flag_3]</td>";
      echo "<td>$trow[flag_4]</td>";
    }

    echo "</tr>";
  }

  echo "<tr><td class='back'><input type='checkbox' name='otunnu' onclick='toggleAll(this);'></td><td class='back'>".t("Ruksaa kaikki")."</td></tr>";

  echo "</table>";
  echo "<br><br>";

  $otunnus = substr($otunnus, 0, -1);

  $tck = "";
  $chk = "";
  $ack = "";
  $sel = "";

  if ($toimas != "") {
    $tck = "CHECKED";
  }

  if ($as_yht_tiedot != "") {
    $chk = "CHECKED";
  }

  if ($aytunnus != "") {
    $ack = "CHECKED";
  }

  $sel[$raportti] = "SELECTED";

  echo "<table>";

  if ($yhtiorow['kalenterimerkinnat'] == '') {
    echo "<tr><th>".t("Asiakasmemon viesti").":</th><td><input type='text' size='20' name='asmemo_viesti' value='$asmemo_viesti'></td></tr>";
  }

  echo "<tr><th>".t("Tulosta toimitusosoitteen tiedot").":</th><td><input type='checkbox' name='toimas' value='on' $tck></td></tr>";
  echo "<tr><th>".t("Luo aineisto yhteyshenkilön osoitetiedoista").":</th><td><input type='checkbox' name='as_yht_tiedot' value='on' $chk></td></tr>";
  echo "<tr><th>".t("Tulosta asiakkaan ja yht.henkilön tunnus (vain excel)").":</th><td><input type='checkbox' name='aytunnus' value='on' $ack></td></tr>";
  echo "<tr><th>".t("Valitse tarra-arkin tyyppi").":</th>
      <td><select name='raportti'>
      <option value='33' $sel[33]>33 ".t("Tarraa")."</option>
      <option value='24' $sel[24]>24 ".t("Tarraa")."</option>
      <option value='EX' $sel[EX]>".t("Excel-tiedosto")."</option>
      </select></td></tr>";
  echo "<tr><td class='back'><input type='submit' name='TULOSTA' value = '".t("Tulosta")."'></td><td class='back'></td></tr></table></form>";
}

require "inc/footer.inc";
