<?php

if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
  require "../inc/parametrit.inc";

  //Jos yll‰pidossa on luotu uusi asiakas
  if ($yllapidossa == "asiakas" and $yllapidontunnus != '') {
    $asiakasid = $yllapidontunnus;
  }
}

if (!isset($nayta_kaikki_merkinnat)) {
  $nayta_kaikki_merkinnat = array();

  if ($_COOKIE["pupesoft_asiakasmemo"] == "nayta_kaikki_merkinnat" or !isset($_COOKIE["pupesoft_asiakasmemo"])) {
    $nayta_kaikki_merkinnat = array('default', 1);
  }
}

if (!isset($haas_call_type))        $haas_call_type = '';
if (!isset($haas_call_type_ed))     $haas_call_type_ed = '';
if (!isset($haas_opportunity))      $haas_opportunity = '';
if (!isset($haas_qty))              $haas_qty = '';
if (!isset($haas_opp_proj_date_dd)) $haas_opp_proj_date_dd = '';
if (!isset($haas_opp_proj_date_mm)) $haas_opp_proj_date_mm = '';
if (!isset($haas_opp_proj_date_yy)) $haas_opp_proj_date_yy = '';
if (!isset($haas_end_reason))       $haas_end_reason = '';

$crm_haas_res = t_avainsana("CRM_HAAS");
$crm_haas_row = mysql_fetch_assoc($crm_haas_res);
$crm_haas_check = (mysql_num_rows($crm_haas_res) > 0 and $crm_haas_row['selite'] == 'K');

$crm_haas_lisa = "";

if ($crm_haas_check) {
  if (!empty($haas_call_type))   $crm_haas_lisa .= "kentta02 = '{$haas_call_type}',";
  if (!empty($haas_opportunity)) $crm_haas_lisa .= "kentta03 = '{$haas_opportunity}',";
  if (!empty($haas_qty))         $crm_haas_lisa .= "kentta04 = '{$haas_qty}',";

  $_dd = (int) $haas_opp_proj_date_dd;
  $_mm = (int) $haas_opp_proj_date_mm;
  $_yy = (int) $haas_opp_proj_date_yy;

  if (!empty($_dd) and !empty($_mm) and !empty($_yy) and checkdate($_mm, $_dd, $_yy)) {
    $crm_haas_lisa .= "kentta05 = '".date('Y-m-d', mktime(0, 0, 0, $_mm, $_dd, $_yy))."',";
  }

  if (!empty($haas_end_reason))  $crm_haas_lisa .= "kentta06 = '{$haas_end_reason}',";
}

echo "<font class='head'>".t("Asiakasmemo")."</font><hr>";

if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
  if ($ytunnus == '' and (int) $asiakasid == 0) {

    js_popup(-100);

    echo "<br><table>";
    echo "<tr>
        <th>".t("Asiakas").":</th>
        <form action='{$palvelin2}crm/asiakasmemo.php' name='asiakasformi' method = 'post'>
        <td><input type='text' size='30' name='ytunnus'> ", asiakashakuohje(), "</td>
        <td class='back'><input type='submit' value='".t("Jatka")."'></td>
        </tr>";
    echo "</form>";
    echo "</table>";

    $formi = "asiakasformi";
    $kentta = "ytunnus";
  }

  if ($ytunnus != '' or $asiakasid > 0) {
    $kutsuja = "asiakasemo.php";
    $ahlopetus = "{$palvelin2}crm/asiakasmemo.php////";

    require "inc/asiakashaku.inc";
  }
}

$asmemo_lopetus = "{$palvelin2}crm/asiakasmemo.php////ytunnus=$ytunnus//asiakasid=$asiakasid";

if ($lopetus != "") {
  // Lis‰t‰‰n t‰m‰ lopetuslinkkiin
  $asmemo_lopetus = $lopetus."/SPLIT/".$asmemo_lopetus;
}

///* Asiakas on valittu *///
if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE and $ytunnus != '') {
  ///* Jos ollaan k‰yty yll‰pidossa *///
  if ($yllapidossa == 'asiakas') {
    $yhtunnus = '';
  }

  if ($tee == "SAHKOPOSTI") {

    list($email, $ekuka, $enimi) = explode("###", $email);

    if ($email != "" and $ekuka != "") {

      // Haetaan muistiinpano
      $query = "SELECT kalenteri.*, asiakas.nimi, asiakas.nimitark, asiakas.toim_nimi, asiakas.toim_nimitark, asiakas.asiakasnro
                FROM kalenteri
                LEFT JOIN asiakas ON (kalenteri.yhtio = asiakas.yhtio and kalenteri.liitostunnus = asiakas.tunnus)
                WHERE kalenteri.tunnus = '$tunnus'";
      $res = pupe_query($query);
      $row = mysql_fetch_array($res);

      $meili = "\n$kukarow[nimi] ".t("l‰hetti sinulle asiakasmemon").".\n\n\n";
      $meili .= t("Tapa").": $row[tapa]\n\n";
      $meili .= t("Ytunnus").": $row[asiakas]\n";
      $meili .= t("Asiakasnumero").": $row[asiakasnro]\n";
      $meili .= t("Asiakas").": $row[nimi] $row[nimitark] $row[toim_nimi] $row[toim_nimitark]\n";
      $meili .= t("P‰v‰m‰‰r‰").": ".tv1dateconv($row["pvmalku"])."\n\n";
      $meili .= t("Viesti").":\n".str_replace("\r\n", "\n", $row["kentta01"])."\n\n";
      $meili .= "-----------------------\n\n";

      $tulos = mail($email, mb_encode_mimeheader(t("Asiakasmemo")." $yhtiorow[nimi]", "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");

      if ($row["tyyppi"] == "Lead") {
        $eviesti = "$kukarow[nimi] l‰hetti leadin k‰ytt‰j‰lle: $enimi / $email";
      }
      else {
        $eviesti = "$kukarow[nimi] l‰hetti memon k‰ytt‰j‰lle: $enimi / $email";
      }

      $kysely = "INSERT INTO kalenteri
                 SET tapa = '$row[tapa]',
                 asiakas = '$row[asiakas]',
                 liitostunnus = '$row[liitostunnus]',
                 henkilo = '$row[henkilo]',
                 kuka = '$kukarow[kuka]',
                 yhtio = '$kukarow[yhtio]',
                 tyyppi = 'Memo',
                 pvmalku = now(),
                 kentta01 = '$eviesti',
                 {$crm_haas_lisa}
                 perheid = '$row[tunnus]',
                 laatija = '$kukarow[kuka]',
                 luontiaika = now()";
      $result = pupe_query($kysely);

      if ($row["tyyppi"] == "Lead") {
        $kysely = "INSERT INTO kalenteri
                   SET tapa = '$row[tapa]',
                   asiakas = '$row[asiakas]',
                   liitostunnus = '$row[liitostunnus]',
                   henkilo = '$row[henkilo]',
                   kuka = '$ekuka',
                   myyntipaallikko = '$row[myyntipaallikko]',
                   yhtio = '$kukarow[yhtio]',
                   tyyppi = '$row[tyyppi]',
                   pvmalku = '$row[pvmalku]',
                   kentta01 = '$row[kentta01]',
                   {$crm_haas_lisa}
                   kuittaus = '$row[kuittaus]',
                   laatija = '$kukarow[kuka]',
                   luontiaika = now()";
        $result = pupe_query($kysely);
      }
    }

    $tunnus = "";
    $tee = "";
    $meili = "";
  }

  ///* Lis‰t‰‰n uusi memeotietue*///
  if ($tee == "UUSIMEMO" and $tyyppi == "Muistutus" and $muistutusko == "") {
    $muistutusko = "Muistutus";
    $tee = "";
  }
  else {
    $muistutusko = "";
  }

  if ($tee == "UUSIMEMO" and $crm_haas_check) {

    $_dd = $haas_opp_proj_date_dd;
    $_mm = $haas_opp_proj_date_mm;
    $_yy = $haas_opp_proj_date_yy;

    if ($haas_call_type != $haas_call_type_ed) {
      $tee = '';
    }

    if (!empty($haas_call_type) and $haas_call_type != 'Prospecting Call') {
      if (empty($haas_opportunity)) {
        echo "<font class='error'>", t("%s on pakollinen.", '', 'OPPORTUNITY'), "</font><br>";
        $tee = '';
      }

      if (empty($haas_qty)) {
        echo "<font class='error'>", t("%s on pakollinen.", '', 'QTY'), "</font><br>";
        $tee = '';
      }

      if (empty($_dd) or empty($_mm) or empty($_yy)) {
        echo "<font class='error'>", t("%s on pakollinen.", '', 'OPP_PROJ_DATE'), "</font><br>";
        $tee = '';
      }

      if (in_array($haas_call_type, array('Won Call', 'Lost Call', 'Dead Call')) and empty($haas_end_reason)) {
        echo "<font class='error'>", t("%s on pakollinen.", '', 'END_REASON'), "</font><br>";
        $tee = '';
      }

      if (!empty($haas_qty)) {

        if (!is_numeric($haas_qty)) {
          echo "<font class='error'>", t("Kappalem‰‰r‰n t‰ytyy olla numero."), "</font><br>";
          $tee = '';
        }

        if (strlen($haas_qty) > 2) {
          echo "<font class='error'>", t("Liian suuri kappalem‰‰r‰. M‰‰r‰n maksimipituus on 2."), "</font><br>";
          $tee = '';
        }
      }

      if ((!empty($_dd) or !empty($_mm) or !empty($_yy))) {
        if (!checkdate($_mm, $_dd, $_yy)) {
          echo "<font class='error'>", t("Virheellinen p‰iv‰m‰‰r‰."), "</font><br>";
          $tee = '';
        }
        if (checkdate($_mm, $_dd, $_yy) and mktime(0, 0, 0, $_mm, $_dd, $_yy) < mktime(0, 0, 0, date("m"), date("d"), date("Y"))) {
          echo "<font class='error'>", t("P‰iv‰m‰‰r‰ ei saa olla menneisyydess‰."), "</font><br>";
          $tee = '';
        }
      }
    }
  }

  if ($tee == "UUSIMEMO") {

    if (checkdate($mkka, $mppa, $mvva)) {
      $pvmalku = "'$mvva-$mkka-$mppa $mhh:$mmm:00'";
    }
    else {
      $pvmalku = "'".date("Y-m-d H:i:s")."'";
    }

    if ($kuittaus == '' and ($tyyppi == "Muistutus" or $tyyppi == "Lead")) {
      $kuittaus = 'K';
    }

    if ($kuka == "") {
      $kuka = $kukarow["kuka"];
    }

    if ($korjaus == '') {
      if ($viesti != '') {

        if ($kukarow["kieli"] != 'fi') {
          $query = "SELECT selite from avainsana
                    where yhtio = '$kukarow[yhtio]'
                    and laji = 'KALETAPA'
                    and selitetark = '$tapa'
                    and kieli = '$kukarow[kieli]'";
          $tapa_res = pupe_query($query);
          $tapa_row = mysql_fetch_assoc($tapa_res);

          $tapa_res = t_avainsana("KALETAPA", "fi", "and avainsana.selite = '{$tapa_row['selite']}'");
          $tapa_row = mysql_fetch_assoc($tapa_res);

          if (!empty($tapa_row['selitetark'])) $tapa = $tapa_row['selitetark'];
        }
        $kysely = "INSERT INTO kalenteri
                   SET tapa = '$tapa',
                   asiakas = '$ytunnus',
                   liitostunnus = '$asiakasid',
                   henkilo = '$yhtunnus',
                   kuka = '$kuka',
                   myyntipaallikko = '$myyntipaallikko',
                   yhtio = '$kukarow[yhtio]',
                   tyyppi = '$tyyppi',
                   pvmalku = $pvmalku,
                   pvmloppu = date_add($pvmalku, INTERVAL 30 MINUTE),
                   kentta01 = '$viesti',
                   {$crm_haas_lisa}
                   kuittaus = '$kuittaus',
                   laatija = '$kukarow[kuka]',
                   luontiaika = now()";
        $result = pupe_query($kysely);
        $muist = mysql_insert_id($GLOBALS["masterlink"]);

        if ($tyyppi == "Muistutus") {

          $query = "SELECT *
                    FROM kuka
                    WHERE yhtio = '$kukarow[yhtio]'
                    and kuka = '$kuka'";
          $result = pupe_query($query);
          $row = mysql_fetch_array($result);

          // K‰ytt‰j‰lle l‰hetet‰‰n tekstiviestimuistutus
          if ($row["puhno"] != '' and strlen($viesti) > 0 and $sms_palvelin != "" and $sms_user != "" and $sms_pass != "") {
            $ok = 1;

            $teksti = substr("Muistutus $yhtiorow[nimi]. $tapa. ".$viesti, 0, 160);
            $teksti = urlencode($teksti);

            $retval = file_get_contents("$sms_palvelin?user=$sms_user&pass=$sms_pass&numero=$row[puhno]&viesti=$teksti&not_before_date=$mvva-$mkka-$mppa&not_before=$mhh:$mmm:00&yhtio=$kukarow[yhtio]&kalenteritunnus=$muist");

            if (trim($retval) == "0") $ok = 0;

            if ($ok == 1) {
              echo "<font class='error'>VIRHE: Tekstiviestin l‰hetys ep‰onnistui! $retval</font><br><br>";
            }

            if ($ok == 0) {
              echo "<font class='message'>Tekstiviestimuistutus lehetet‰‰n!</font><br><br>";
            }
          }
        }
        $aputyyppi = $tyyppi;
        $tapa = "";
        $viesti = "";
        $tunnus = "";
        $tyyppi = "";
        $mvva = "";
        $mkka = "";
        $mppa = "";
        $mhh = "";
        $mmm = "";
        $kuka = "";
        $kuittaus = "";
        $myyntipaallikko = "";
      }
    }
    else {
      $kysely = "UPDATE kalenteri
                 SET tapa = '$tapa',
                 asiakas = '$ytunnus',
                 liitostunnus = '$asiakasid',
                 henkilo = '$yhtunnus',
                 kuka = '$kuka',
                 myyntipaallikko = '$myyntipaallikko',
                 yhtio = '$kukarow[yhtio]',
                 tyyppi = '$tyyppi',
                 pvmalku = $pvmalku,
                 pvmloppu = date_add($pvmalku, INTERVAL 30 MINUTE),
                 kentta01 = '$viesti',
                 {$crm_haas_lisa}
                 kuittaus = '$kuittaus',
                 muuttaja = '$kukarow[kuka]',
                 muutospvm = now()
                 WHERE tunnus = '$korjaus'";
      $result = pupe_query($kysely);

      $aputyyppi = $tyyppi;
      $tapa = "";
      $viesti = "";
      $tunnus = "";
      $tyyppi = "";
      $mvva = "";
      $mkka = "";
      $mppa = "";
      $mhh = "";
      $mmm = "";
      $kuka = "";
      $kuittaus = "";
      $myyntipaallikko = "";
    }

    $tee = "";

    if ($crm_haas_check and ($korjaus != '' or $viesti != '')) {
      $haas_call_type = '';
      $haas_opportunity = '';
      $haas_qty = '';
      $haas_opp_proj_date_dd = '';
      $haas_opp_proj_date_mm = '';
      $haas_opp_proj_date_yy = '';
      $haas_end_reason = '';
    }
  }

  // tallenetaan uutena ominaisuutena liitetiedostoja memolle.
  if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

    if ($korjaus == '') {
      $liitostunnus = $muist;
    }
    else {
      $liitostunnus = $korjaus;
    }

    $tallennustring = "Liitetiedosto ".$aputyyppi;

    $id = tallenna_liite("userfile", $aputyyppi, $liitostunnus, $tallennustring);
  }

  if ($tee == "POISTAMEMO") {
    $kysely = "UPDATE kalenteri
               SET
               tyyppi = concat('DELETED ',tyyppi)
               WHERE tunnus = '$tunnus'
               and yhtio = '$kukarow[yhtio]'
               and asiakas = '$ytunnus'
               and liitostunnus = '$asiakasid'";
    $result = pupe_query($kysely);

    $kysely = "UPDATE liitetiedostot
               SET liitos = concat('DELETED ',liitos)
               WHERE liitostunnus = '$tunnus'
               AND liitos = '$liitostyyppi'
               and yhtio = '$kukarow[yhtio]'";

    $result = pupe_query($kysely);

    $tee = '';
  }

  if ($tee == "KORJAAMEMO") {

    // Haetaan viimeisin muistiinpano
    $query = "SELECT *
              FROM kalenteri
              WHERE liitostunnus = '$asiakasid'
              and tyyppi in ('Memo','Muistutus','Kuittaus','Lead','Myyntireskontraviesti')
              and yhtio = '$kukarow[yhtio]'
              and (perheid=0 or tunnus=perheid)
              ORDER BY tunnus desc
              LIMIT 1";
    $res = pupe_query($query);
    $korjrow = mysql_fetch_array($res);

    $tapa = $korjrow["tapa"];
    $viesti = $korjrow["kentta01"];
    $yhtunnus = $korjrow["henkilo"];
    $tunnus = $korjrow["tunnus"];
    $tyyppi = $korjrow["tyyppi"];
    $mvva = substr($korjrow["pvmalku"], 0, 4);
    $mkka = substr($korjrow["pvmalku"], 5, 2);
    $mppa = substr($korjrow["pvmalku"], 8, 2);

    $mhh = substr($korjrow["pvmalku"], 11, 2);
    $mmm = substr($korjrow["pvmalku"], 14, 2);

    $kuka = $korjrow["kuka"];
    $kuittaus = $korjrow["kuittaus"];
    $myyntipaallikko = $korjrow["myyntipaallikko"];

    if ($crm_haas_check) {
      $haas_call_type = $korjrow['kentta02'];
      $haas_opportunity = $korjrow['kentta03'];
      $haas_qty = $korjrow['kentta04'];
      $haas_opp_proj_date_dd = substr($korjrow['kentta05'], 8);
      $haas_opp_proj_date_mm = substr($korjrow['kentta05'], 5, 2);
      $haas_opp_proj_date_yy = substr($korjrow['kentta05'], 0, 4);
      $haas_end_reason = $korjrow['kentta06'];
    }

    if ($tyyppi == "Muistutus") {
      $muistutusko = 'Muistutus';
    }

    $tee = "";
  }

  if ($tee == 'paivita_tila') {
    $tee2 = $tee;
    $tee = '';

    $query_update = "UPDATE asiakas
                     SET tila = '$astila'
                     WHERE yhtio = '$kukarow[yhtio]'
                     and ytunnus = '$ytunnus'
                     and tunnus = '$asiakasid'";
    $result_update = pupe_query($query_update);

    echo t("Vaihdettiin asiakkaan tila")."<br/>";
  }
}

if ($ytunnus != '' and $tee == '') {

  function listaaliitetiedostot($kalenteritunnus, $tyyppi) {
    global $palvelin2, $kukarow;
    $out = "";

    $query = "SELECT tunnus, filename
              FROM liitetiedostot
              WHERE yhtio = '$kukarow[yhtio]'
              AND liitostunnus = '$kalenteritunnus'
              AND liitos = '$tyyppi'";
    $res = pupe_query($query);

    while ($row = mysql_fetch_array($res)) {
      $out .= js_openUrlNewWindow("{$palvelin2}view.php?id=$row[tunnus]", t('N‰yt‰ liite'), NULL, 800, 600)." $row[filename]<br>\n ";
    }

    return $out;
  }

  ///* Yhteyshenkilˆn tiedot, otetaan valitun yhteyshenkilˆn tiedot talteen  *///
  if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
    $query = "SELECT *
              FROM yhteyshenkilo
              WHERE yhtio = '$kukarow[yhtio]'
              and liitostunnus = '$asiakasid'
              and tyyppi = 'A'
              ORDER BY nimi";
    $result = pupe_query($query);

    $yhenkilo = "<form action='{$palvelin2}crm/asiakasmemo.php' method='POST'>
          <input type='hidden' name='ytunnus' value='$ytunnus'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='asiakasid' value='$asiakasid'>
          <select name='yhtunnus' Onchange='submit();'>
          <option value=''>".t("Yleistiedot")."</option>";

    while ($row = mysql_fetch_array($result)) {

      if ($yhtunnus == $row["tunnus"]) {
        $sel = 'SELECTED';
        $yemail = $row["email"];
        $ynimi = $row["nimi"];
        $yfax = $row["fax"];
        $ygsm = $row["gsm"];
        $ypuh = $row["puh"];
        $ywww = $row["www"];
        $ytitteli = $row["titteli"];
        $yfakta = $row["fakta"];
      }
      else {
        $sel = '';
      }

      $yhenkilo .= "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
    }
    $yhenkilo .= "</select></form>";

    //N‰ytet‰‰n asiakkaan tietoja jos yhteyshenkilˆ‰ ei olla valittu
    if ($yhtunnus == "kaikki" or $yhtunnus == '') {
      $yemail = $asiakasrow["email"];
      $ynimi = "";
      $ygsm = $asiakasrow["gsm"];
      $ypuh = $asiakasrow["puhelin"];
      $yfax = $asiakasrow["fax"];
      $ywww = "";
      $ytitteli = "";
      $yfakta = $asiakasrow["fakta"];
    }

    ///* Asiakaan tiedot ja yhteyshenkilˆn tiedot *///
    echo "<table>";

    echo "<tr>";
    echo "<th align='left'>".t("Laskutusasiakas").":</th>";
    echo "<th align='left'>".t("Toimitusasiakas").":</th>";
    echo "<th align='left'>".t("Muut tiedot").":</th>";
    echo "<th align='left'>".t("Toiminnot").":</th>";
    echo "</tr>";


    //asiakkaan toimitusosoite
    if ($asiakasrow['toim_osoite']=='') {
      $asiakasrow['toim_nimi']     = $asiakasrow['nimi'];
      $asiakasrow['toim_nimitark'] = $asiakasrow['nimitark'];
      $asiakasrow['toim_osoite']   = $asiakasrow['osoite'];
      $asiakasrow['toim_postino']  = $asiakasrow['postino'];
      $asiakasrow['toim_postitp']  = $asiakasrow['postitp'];
    }

    $asylloik = tarkista_oikeus("yllapito.php", "asiakas%", "X", TRUE);
    $yhylloik = tarkista_oikeus("yllapito.php", "yhteyshenkilo%", "X");

    echo "<tr>";
    echo "<td>$asiakasrow[nimi]";

    if ($asylloik) {
      echo "&nbsp;&nbsp;<a href='{$palvelin2}yllapito.php?toim={$asylloik["alanimi"]}&tunnus=$asiakasid&lopetus=$asmemo_lopetus'><img src='{$palvelin2}pics/lullacons/document-properties.png' alt='", t("Muokkaa"), "' title='", t("Muuta asiakkaan tietoja"), "' /></a>";
    }

    echo "</td>";
    echo "<td>$asiakasrow[toim_nimi]</td><td>$yhenkilo</td>";

    echo "<td rowspan='6' class='ptop'><ul>";

    if ($asylloik and $yhylloik) {
      echo "<li><a href='{$palvelin2}yllapito.php?toim={$asylloik["alanimi"]}&tunnus=$asiakasid&lopetus=$asmemo_lopetus'>".t("Luo uusi yhteyshenkilˆ")."</a></li>";
    }

    if (tarkista_oikeus("crm/kalenteri.php", "", "X")) {
      echo "<li><a href='{$palvelin2}crm/kalenteri.php?viikkonakyma=".date("W")."&lopetus=$asmemo_lopetus'>".t("Kalenteri")."</a></li>";
    }

    if (tarkista_oikeus("raportit/myyntiseuranta.php")) {
      echo "<li><a href='{$palvelin2}raportit/asiakasinfo.php?ytunnus=$ytunnus&asiakasid={$asiakasrow["tunnus"]}&rajaus=MYYNTI&tee=go&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&tuoteosasto2=kaikki&yhtiot[]=$kukarow[yhtio]&jarjestys[]=&lopetus=$asmemo_lopetus'>".t("Myynninseuranta")."</a></li>";
    }

    if (tarkista_oikeus("raportit/asiakasinfo.php")) {
      echo "<li><a href='{$palvelin2}raportit/asiakasinfo.php?ytunnus=$ytunnus&asiakasid={$asiakasrow["tunnus"]}&rajaus=ALENNUKSET&lopetus=$asmemo_lopetus'>".t("Alennustaulukko")."</a></li>";
    }

    if (tarkista_oikeus("budjetinyllapito_tat.php", "ASIAKAS")) {
      echo "<li><a href='{$palvelin2}budjetinyllapito_tat.php?toim=ASIAKAS&ytunnus=$ytunnus&asiakasid={$asiakasrow["tunnus"]}&submit_button=joo&alkuvv=".date("Y")."&alkukk=01&loppuvv=".date("Y")."&loppukk=12&lopetus=$asmemo_lopetus'>".t("Asiakkaan myyntitavoitteet")."</a></li>";
    }

    if (tarkista_oikeus("raportit/asiakkaantilaukset.php", "TARJOUS")) {
      $tkka = date("m", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
      $tvva = date("Y", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
      $tppa = date("d", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));

      $out = js_openUrlNewWindow("{$palvelin2}raportit/asiakkaantilaukset.php?toim=TARJOUS&kka=$tkka&vva=$tvva&ppa=$tppa&ytunnus=$ytunnus&asiakasid={$asiakasrow["tunnus"]}", t('Asiakkaan tarjoukset'), NULL, 1000, 800);
      echo "<li>$out</li>";
    }

    if (tarkista_oikeus("raportit/sarjanumerohistoria.php")) {
      $out = js_openUrlNewWindow("{$palvelin2}raportit/sarjanumerohistoria.php?tee=hae_tilaukset&indexvas=1&asiakastunnus={$asiakasrow["tunnus"]}", t('Asiakkaan laitteet'), NULL, 1000, 800);
      echo "<li>$out</li>";
    }

    if ($asylloik and $yhylloik) {
      echo "<li><a href='{$palvelin2}yllapito.php?toim={$asylloik["alanimi"]}&tunnus=$asiakasid&lopetus=$asmemo_lopetus'>".t("Muuta yhteyshenkilˆn tietoja")."</a></li>";
    }

    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>$asiakasrow[nimitark]</td><td>$asiakasrow[toim_nimitark]</td><td>".t("Puh").": $ypuh</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";

    $gurl = utf8_encode($asiakasrow["osoite"].", ".$asiakasrow["postitp"]);
    echo js_openUrlNewWindow("http://maps.google.com/?q=$gurl", $asiakasrow["osoite"], "", 1200);
    echo "</td><td>";

    $gurl = utf8_encode($asiakasrow["toim_osoite"].", ".$asiakasrow["toim_postitp"]);
    echo js_openUrlNewWindow("http://maps.google.com/?q=$gurl", $asiakasrow["toim_osoite"], "", 1200);

    echo "</td><td>".t("Fax").": $yfax</td>";

    // P‰iv‰m‰‰r‰t rappareita varten
    $kka = date("m", mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
    $vva = date("Y", mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
    $ppa = date("d", mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
    $kkl = date("m");
    $vvl = date("Y");
    $ppl = date("d");

    echo "</tr>";

    echo "<tr>";
    echo "<td>$asiakasrow[postino] $asiakasrow[postitp]</td><td>$asiakasrow[toim_postino] $asiakasrow[toim_postitp]</td><td>".t("Gsm").": $ygsm</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>$asiakasrow[fakta]</td><td></td><td>".t("Email").": $yemail";

    if ($yemail != "") {
      echo " &nbsp; <a href=\"mailto:$yemail\">".t("Email")."</a>";
    }

    echo "</td>";
    echo "</tr>";

    echo "<tr><td colspan='2'></td><td>".t("Tila").": ";
    echo "<form action='{$palvelin2}crm/asiakasmemo.php' method='POST'>";
    echo "<input type='hidden' name='ytunnus' value='$ytunnus'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='asiakasid' value='$asiakasid'>
        <input type='hidden' name='tee' value='paivita_tila'>";
    echo "<select name='astila' Onchange='submit();'>";
    echo "<option value=''>".t("Ei tilaa")."</option>";

    $asosresult = t_avainsana("ASIAKASTILA");

    if ($tee2 == "") {
      $astila = $asiakasrow['tila'];
    }
    while ($asosrow = mysql_fetch_array($asosresult)) {
      $sel2 = '';
      if ($astila == $asosrow["selite"]) {
        $sel2 = "selected";
      }
      echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
    }

    echo "</select></form>";
    echo "</td>";
    echo "</tr>";

    if ($yfakta != '' or $ytitteli != '' or $ynimi != '') {
      echo "<tr><td colspan='2'><b>".t("Valittu yhteyshenkilˆ").": $ytitteli $ynimi</b></td><td colspan='2'>$yfakta</td></tr>";
    }

    echo "</table><br>";
  }

  ///* Syˆt‰ memo-tietoa *///
  if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
    echo "<table width='620'>";

    echo "  <form action='{$palvelin2}crm/asiakasmemo.php' method='POST' enctype='multipart/form-data'>
        <input type='hidden' name='tee'     value='UUSIMEMO'>
        <input type='hidden' name='korjaus'   value='$tunnus'>
        <input type='hidden' name='yhtunnus'   value='$yhtunnus'>
        <input type='hidden' name='ytunnus'   value='$ytunnus'>
        <input type='hidden' name='lopetus'   value='$lopetus'>
        <input type='hidden' name='asiakasid'   value='$asiakasid'>
        <input type='hidden' name='muistutusko' value='$muistutusko'>";

    if ($tyyppi == "Kuittaus") {
      echo "<input type='hidden' name='kuka' value='$kuka'>";
    }

    echo "<tr><th>".t("Lis‰‰")."</th>";

    $sel = array();
    $sel[$tyyppi] = "SELECTED";

    echo "<td><select name='tyyppi' Onchange='submit();'>
        <option value='Memo' $sel[Memo]>".t("Memo")."</option>
        <option value='Muistutus' $sel[Muistutus]>".t("Muistutus")."</option>
        <option value='Lead' $sel[Lead]>".t("Lead")."</option>
        <option value='Myyntireskontraviesti' $sel[Myyntireskontraviesti]>".t("Myyntireskontraviesti")."</option>";

    if ($tyyppi == "Kuittaus") {
      echo "<option value='Kuittaus' $sel[Kuittaus]>".t("Kuittaus")."</option>";
    }

    echo "</select></td></tr>";

    if ($crm_haas_check and ($tyyppi == 'Memo' or $tyyppi == '')) {

      $call_types = array(
        'Prospecting Call' => '',
        'Qualifying Call'  => '',
        'Quoting Call'     => '',
        'Closing Call'     => '',
        'Won Call'         => '',
        'Lost Call'        => '',
        'Dead Call'        => '',
      );

      $call_type_sel = array($haas_call_type => 'selected') + $call_types;

      echo "<tr>";
      echo "<th>CALL_TYPE</th>";
      echo "<td>";
      echo "<select name='haas_call_type' onchange='submit();'>";
      echo "<option value=''>", t("Valitse"), "</option>";

      foreach ($call_types as $key => $value) {
        echo "<option value='{$key}' {$call_type_sel[$key]}>{$key}</th>";
      }

      echo "</select>";
      echo "<input type='hidden' name='haas_call_type_ed' value='{$haas_call_type}' />";
      echo "</td>";
      echo "</tr>";

      if ($haas_call_type != 'Prospecting Call') {
        $opportunities = array(
          'OMs'           => '',
          'TMs'           => '',
          'MMs'           => '',
          'VF-1/2s'       => '',
          'VF-3/4/5'      => '',
          'VF-6+'         => '',
          'GRs'           => '',
          'DT/DMs'        => '',
          'UMCs'          => '',
          'EC400/500'     => '',
          'EC1600'        => '',
          'OL'            => '',
          'TLs'           => '',
          'ST10/15'       => '',
          'ST20/25'       => '',
          'ST30/35'       => '',
          'ST40/45/50/55' => '',
          'DSs'           => '',
          'ROTARY'        => '',
          'BARFEEDER'     => '',
        );

        $opportunity_sel = array($haas_opportunity => 'selected') + $opportunities;

        echo "<tr>";
        echo "<th>OPPORTUNITY</th>";
        echo "<td>";
        echo "<select name='haas_opportunity'>";
        echo "<option value=''>", t("Valitse"), "</option>";

        foreach ($opportunities as $key => $value) {
          echo "<option value='{$key}' {$opportunity_sel[$key]}>{$key}</th>";
        }

        echo "</select>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>QTY</th>";
        echo "<td>";
        echo "<input type='text' name='haas_qty' value='{$haas_qty}' size='8' maxlength='7' />";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>OPP_PROJ_DATE</th>";
        echo "<td>";
        echo "<input type='text' name='haas_opp_proj_date_dd' value='{$haas_opp_proj_date_dd}' size='3' maxlength='2' />";
        echo "<input type='text' name='haas_opp_proj_date_mm' value='{$haas_opp_proj_date_mm}' size='3' maxlength='2' />";
        echo "<input type='text' name='haas_opp_proj_date_yy' value='{$haas_opp_proj_date_yy}' size='5' maxlength='4' />";
        echo "</td>";
        echo "</tr>";

        if (in_array($haas_call_type, array('Won Call', 'Lost Call', 'Dead Call'))) {
          $end_reasons = array(
            'Price Win'               => '',
            'Specification Win'       => '',
            'Delivery Win'            => '',
            'Builder/Distributor Win' => '',
            'Price Loss'              => '',
            'Specification Loss'      => '',
            'Delivery Loss'           => '',
            'Distributor Loss'        => '',
            'Competitor Loss'         => '',
            'Dead'                    => '',
          );

          $end_reason_sel = array($haas_end_reason => 'selected') + $end_reasons;

          echo "<tr>";
          echo "<th>END_REASON</th>";
          echo "<td>";
          echo "<select name='haas_end_reason'>";
          echo "<option value=''>", t("Valitse"), "</option>";

          foreach ($end_reasons as $key => $value) {
            echo "<option value='{$key}' {$end_reason_sel[$key]}>{$key}</th>";
          }

          echo "</select>";
          echo "</td>";
          echo "</tr>";
        }
      }
    }

    echo "  <tr><th>".t("Tallenna tiedosto liitteeksi")."</th>";
    echo "  <td><input type = 'file' name = 'userfile' />";
    echo "  <input type='hidden' name='teeliite'  value='tallenna_pdf'>";
    echo "  <input type='hidden' name='yhtunnus'   value='$yhtunnus'>
        <input type='hidden' name='ytunnus'   value='$ytunnus'>
        <input type='hidden' name='asiakasid'   value='$asiakasid'>";
    echo "  </td></tr>";

    echo "<tr><td colspan='2'><textarea cols='83' rows='3' name='viesti' wrap='hard'>$viesti</textarea></td></tr>";

    if ($tyyppi == "Muistutus") {
      echo "  <tr>
          <th>".t("Yhteydenottaja").":</th>
          <td><select name='kuka'>
          <option value='$kukarow[kuka]'>".t("Itse")."</option>";

      $query = "SELECT DISTINCT kuka.tunnus, kuka.nimi, kuka.kuka
                FROM kuka
                JOIN oikeu ON (oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi = 'crm/kalenteri.php')
                WHERE kuka.yhtio = '$kukarow[yhtio]'
                AND kuka.aktiivinen = 1
                and kuka.kuka       != '$kukarow[kuka]'
                ORDER BY kuka.nimi";
      $result = pupe_query($query);

      while ($row = mysql_fetch_array($result)) {
        if ($row["kuka"] == $kuka) {
          $sel = "SELECTED";
        }
        else {
          $sel = "";
        }

        echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
      }
      echo "</select></td></tr>";

      if (!isset($mkka))
        $mkka = date("m");
      if (!isset($mvva))
        $mvva = date("Y");
      if (!isset($mppa))
        $mppa = date("d");
      if (!isset($mhh))
        $mhh = "08";
      if (!isset($mmm))
        $mmm = "00";

      echo "<tr><th>".t("Muistutusp‰iv‰m‰‰r‰ (pp-kk-vvvv tt:mm)")."</th>
          <td><input type='text' name='mppa' value='$mppa' size='3'>-
          <input type='text' name='mkka' value='$mkka' size='3'>-
          <input type='text' name='mvva' value='$mvva' size='5'>
          &nbsp;&nbsp;
          <input type='text' name='mhh' value='$mhh' size='3'>:
          <input type='text' name='mmm' value='$mmm' size='3'></td></tr>";

      if ($kuittaus == "E") {
        $sel = "CHECKED";
      }
      else {
        $sel = "";
      }

      echo"  <tr>
          <th>".t("Ei kuittausta").":</th><td colspan='2'><input type='checkbox' name='kuittaus' value='E' $sel>
          </td>
          </tr>";
    }
    if ($tyyppi == "Lead") {

      echo "  <tr>
          <th>".t("Leadia valvoo").":</th>
          <td><select name='myyntipaallikko'>";

      $query = "SELECT DISTINCT kuka.tunnus, kuka.nimi, kuka.kuka
                FROM kuka
                JOIN oikeu ON (oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi = 'crm/kalenteri.php')
                WHERE kuka.yhtio = '$kukarow[yhtio]'
                AND kuka.aktiivinen = 1
                and kuka.asema      like '%MP%'
                ORDER BY kuka.nimi";
      $result = pupe_query($query);

      while ($row = mysql_fetch_array($result)) {
        if ($row["myyntipaallikko"] == $myyntipaallikko) {
          $sel = "SELECTED";
        }
        else {
          $sel = "";
        }

        echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
      }
      echo "</select></td></tr>";


      echo "  <tr>
          <th>".t("Leadia hoitaa").":</th>
          <td><select name='kuka'>
          <option value='$kukarow[kuka]'>$kukarow[nimi]</option>";

      $query = "SELECT DISTINCT kuka.tunnus, kuka.nimi, kuka.kuka
                FROM kuka
                JOIN oikeu ON (oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi = 'crm/kalenteri.php')
                WHERE kuka.yhtio = '$kukarow[yhtio]'
                AND kuka.aktiivinen = 1
                and kuka.kuka       != '$kukarow[kuka]'
                ORDER BY kuka.nimi";
      $result = pupe_query($query);

      while ($row = mysql_fetch_array($result)) {
        if ($row["kuka"] == $kuka) {
          $sel = "SELECTED";
        }
        else {
          $sel = "";
        }

        echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
      }
      echo "</select></td></tr>";

      if (!isset($lkka)) $lkka = date("m", mktime(0, 0, 0, date("m"), date("d")+7, date("Y")));
      if (!isset($lvva)) $lvva = date("Y", mktime(0, 0, 0, date("m"), date("d")+7, date("Y")));
      if (!isset($lppa)) $lppa = date("d", mktime(0, 0, 0, date("m"), date("d")+7, date("Y")));
      if (!isset($lhh))  $lhh = "10";
      if (!isset($lmm))  $lmm = "00";

      echo "<tr><th>".t("Muistutusp‰iv‰m‰‰r‰ (pp-kk-vvvv tt:mm)")."</th>
          <td><input type='text' name='mppa' value='$lppa' size='3'>-
          <input type='text' name='mkka' value='$lkka' size='3'>-
          <input type='text' name='mvva' value='$lvva' size='5'>
          &nbsp;&nbsp;
          <input type='text' name='mhh' value='$lhh' size='3'>:
          <input type='text' name='mmm' value='$lmm' size='3'></td></tr>";
    }

    echo "<tr><th>".t("Tapa").":</th>";

    $vresult = t_avainsana("KALETAPA");

    echo "<td><select name='tapa'>";

    while ($vrow = mysql_fetch_array($vresult)) {
      $sel="";

      if ($tapa == $vrow["selitetark"]) {
        $sel = "selected";
      }
      echo "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark]</option>";
    }

    echo "</select></td></tr>";

    echo "  <tr>
        <td colspan='2' align='right' class='back'>
        <input type='submit' value='".t("Tallenna")."'>
        </form>
        </td></tr>";
    echo "</table>";
  }

  $_chk_if = (!empty($nayta_kaikki_merkinnat) and is_array($nayta_kaikki_merkinnat));
  $_merkinnat_chk = ($_chk_if and count($nayta_kaikki_merkinnat) > 1) ? 'checked' : '';

  if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
    echo "<form action='{$palvelin2}crm/asiakasmemo.php' method='POST'>";
    echo "<input type='hidden' name='tee' value=''>";
    echo "<input type='hidden' name='yhtunnus'   value='$yhtunnus'>";
    echo "<input type='hidden' name='ytunnus'   value='$ytunnus'>";
    echo "<input type='hidden' name='lopetus'   value='$lopetus'>";
    echo "<input type='hidden' name='asiakasid'   value='$asiakasid'>";

  }

  echo "<input type='hidden' name='tallenna_keksiin' value='joo'>";
  echo "<input type='hidden' name='kaytiin_otsikolla' value='' id='ka_ot_h'>";
  echo "<input type='hidden' name='nayta_kaikki_merkinnat[]' value='default'>";
  echo "<input type='checkbox' name='nayta_kaikki_merkinnat[]' onchange='document.getElementById(\"ka_ot_h\").value=\"NOJOO!\"; this.form.submit();' {$_merkinnat_chk}>";
  echo " ", t("N‰yt‰ kaikkien k‰ytt‰jien merkinn‰t");

  if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
    echo "</form>";

    echo "<span class='nopad ptop' style='padding-left:40px;'>";
    echo "<a href='{$palvelin2}crm/asiakasmemo.php?tee=KORJAAMEMO&yhtunnus=$yhtunnus&ytunnus=$ytunnus&lopetus=$lopetus&asiakasid=$asiakasid'>";
    echo t("Korjaa viimeisint‰ merkint‰‰")."</a></span>";
  }

  echo "<br><br>";

  ///* Haetaan memosta sisalto asiakkaan kohdalta *///
  echo "<table width='620'>";

  if ($naytapoistetut == '') {
    $lisadel = " and left(kalenteri.tyyppi,7) != 'DELETED'";
  }

  $kayttajalisa = empty($_merkinnat_chk) ? "and kalenteri.kuka = '{$kukarow['kuka']}'" : "";

  $query = "SELECT kalenteri.tyyppi, tapa, kalenteri.asiakas ytunnus, yhteyshenkilo.nimi yhteyshenkilo,
            if(kuka.nimi!='',kuka.nimi, kalenteri.kuka) laatija, kentta01 viesti, left(pvmalku,10) paivamaara,
            kentta02, kentta03, kentta04, kentta05, kentta06, kentta07, kentta08,
            lasku.tunnus laskutunnus, lasku.tila laskutila, lasku.alatila laskualatila, kuka2.nimi laskumyyja, lasku.muutospvm laskumpvm,
            kalenteri.tunnus, kalenteri.perheid, if(kalenteri.perheid!=0, kalenteri.perheid, kalenteri.tunnus) sorttauskentta
            FROM kalenteri
            LEFT JOIN yhteyshenkilo ON (kalenteri.yhtio=yhteyshenkilo.yhtio and kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.tyyppi = 'A')
            LEFT JOIN kuka ON (kalenteri.yhtio=kuka.yhtio and kalenteri.kuka=kuka.kuka)
            LEFT JOIN lasku ON (kalenteri.yhtio=lasku.yhtio and kalenteri.otunnus=lasku.tunnus)
            LEFT JOIN kuka kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE kalenteri.liitostunnus = '$asiakasid'
            $lisadel
            {$kayttajalisa}
            and kalenteri.yhtio = '$kukarow[yhtio]' ";

  if ($yhtunnus > 0) {
    $query .= " and henkilo='$yhtunnus'";
  }

  $query .= "ORDER by sorttauskentta desc, tunnus";

  if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") === FALSE) {
    $query .= "  LIMIT 5 ";
  }

  $res = pupe_query($query);

  while ($memorow = mysql_fetch_assoc($res)) {

    if ($memorow["laskutunnus"] > 0) {
      $laskutyyppi = $memorow["laskutila"];
      $alatila = $memorow["laskualatila"];

      //tehd‰‰n selv‰kielinen tila/alatila
      require "inc/laskutyyppi.inc";
    }

    // Onko t‰m‰ lasku?
    if ($memorow["tyyppi"] == "TILAUS") {
      $memorow["LASKURIVI"] = TRUE;
      $memorow["tyyppi"] = t("$laskutyyppi");
      $memorow["tapa"] = t("$laskutyyppi")." ".t("$alatila");
    }

    if ($memorow["perheid"] == 0) {
      echo "<tr><td class='back pnopad' style='height:10px;'></td></tr>";
      echo "<tr>";
      echo "<td>".ucfirst($memorow["tyyppi"])."</td>
            <td>$memorow[laatija]</td>
            <td>".tv1dateconv($memorow["paivamaara"])."</td>
            <td>$memorow[tapa]</td>
            <td>$memorow[yhteyshenkilo]</td>";

      if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE and substr($memorow['tyyppi'], 0, 7) != 'DELETED' and empty($memorow["LASKURIVI"])) {
        echo "<td><a href='{$palvelin2}crm/asiakasmemo.php?tunnus=$memorow[tunnus]&ytunnus=$ytunnus&asiakasid=$asiakasid&yhtunnus=$yhtunnus&tee=POISTAMEMO&liitostyyppi=$memorow[tyyppi]&lopetus=$lopetus'>".t("Poista")."</a></td>";
      }
      else {
        echo "<td></td>";
      }
      echo "</tr>";
    }

    echo "<tr><td colspan='6'><strong style='font-weight:bold'>".str_replace("\n", "<br>", trim($memorow["viesti"]))."</strong>";

    if ($memorow["laskutunnus"] > 0 and in_array($memorow["laskutila"], array('N', 'L', 'T'))) {

      if ($memorow["laskutila"] == "T") {
        $koptoim = "TARJOUS";
      }
      elseif ($memorow["laskualatila"] == "X") {
        $koptoim = "LASKU";
      }
      else {
        $koptoim = "TILAUSVAHVISTUS";
      }

      $url = js_openUrlNewWindow("{$palvelin2}tilauskasittely/tulostakopio.php?toim=$koptoim&tee=NAYTATILAUS&otunnus=$memorow[laskutunnus]", "$memorow[laskutunnus]");

      echo "<br><br>".t("$laskutyyppi")." ".t("$alatila").": $url / ".tv1dateconv($memorow["laskumpvm"])."  ($memorow[laskumyyja])";
    }

    if ($memorow["laskutunnus"] == 0 and $memorow["tyyppi"] == "Lead") {
      echo "<br><br><a href='{$palvelin2}tilauskasittely/tilaus_myynti.php?toim=TARJOUS&from=CRM&asiakasid=$asiakasid&lead=$memorow[tunnus]'>".t("Tee tarjous")."</a>";
    }

    echo "</td></tr>";

    $crm_haas_column_check = !empty($memorow['kentta02']);
    $crm_haas_column_check = (!empty($memorow['kentta03']) or $crm_haas_column_check);
    $crm_haas_column_check = (!empty($memorow['kentta04']) or $crm_haas_column_check);
    $crm_haas_column_check = (!empty($memorow['kentta05']) or $crm_haas_column_check);
    $crm_haas_column_check = (!empty($memorow['kentta06']) or $crm_haas_column_check);

    if ($crm_haas_check and $crm_haas_column_check) {
      echo "<tr>";
      echo "<th colspan='2'>CALL_TYPE</th>";
      echo "<td colspan='4'>{$memorow['kentta02']}</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th colspan='2'>OPPORTUNITY</th>";
      echo "<td colspan='4'>{$memorow['kentta03']}</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th colspan='2'>QTY</th>";
      echo "<td colspan='4'>{$memorow['kentta04']}</td>";
      echo "</tr>";


      echo "<tr>";
      echo "<th colspan='2'>OPP_PROJ_DATE</th>";
      echo "<td colspan='4'>".tv1dateconv($memorow['kentta05'])."</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th colspan='2'>END_REASON</th>";
      echo "<td colspan='4'>{$memorow['kentta06']}</td>";
      echo "</tr>";
    }

    $liitetiedostot = listaaliitetiedostot($memorow['tunnus'], $memorow['tyyppi']);

    if ($liitetiedostot != '') {
      echo "<tr><td colspan='2'>".t("Liitetiedosto")."</th><td colspan='4'>".$liitetiedostot."</td></tr>";
    }

    if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE and $memorow["perheid"] == 0 and ($memorow["tyyppi"] == "Memo" or $memorow["tyyppi"] == "Lead")) {
      echo "<tr><td colspan='2'>".t("L‰het‰ k‰ytt‰j‰lle").":</td><td colspan='4'>";
      echo "<form action='{$palvelin2}crm/asiakasmemo.php' method='POST'>";
      echo "<input type='hidden' name='tee' value='SAHKOPOSTI'>";
      echo "<input type='hidden' name='tunnus' value='$memorow[tunnus]'>";
      echo "<input type='hidden' name='yhtunnus' value='$yhtunnus'>";
      echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
      echo "<input type='hidden' name='lopetus' value='$lopetus'>";
      echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
      echo "<select name='email'><option value=''>".t("Valitse k‰ytt‰j‰")."</option>";

      $query = "SELECT kuka.nimi, kuka.eposti, min(kuka.kuka) kuka
                 FROM kuka
                 WHERE kuka.yhtio = '$kukarow[yhtio]'
                 AND kuka.aktiivinen = 1
                 and kuka.extranet = ''
                 and kuka.eposti     != ''
                 GROUP BY 1,2
                 ORDER BY kuka.nimi";
      $vares = pupe_query($query);

      while ($varow = mysql_fetch_array($vares)) {
        echo "<option value='$varow[eposti]###$varow[kuka]###$varow[nimi]'>$varow[nimi]</option>";
      }

      echo "</select>";
      echo "<input type='submit' value='".t("L‰het‰ viesti")."'>";
      echo "</form>";
      echo "</td></tr>";
    }
  }

  echo "</table>";

  if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE ) {
    if ($naytapoistetut == "") {
      echo "<br>";
      echo "<a href='{$palvelin2}crm/asiakasmemo.php?naytapoistetut=OK&ytunnus=$ytunnus&asiakasid=$asiakasid&yhtunnus=$yhtunnus&lopetus=$lopetus'>".t("N‰yt‰ poistetut muistiinpanot")."</a>";
    }
    else {
      echo "<br>";
      echo "<a href='{$palvelin2}crm/asiakasmemo.php?naytapoistetut=&ytunnus=$ytunnus&asiakasid=$asiakasid&yhtunnus=$yhtunnus&lopetus=$lopetus'>".t("N‰yt‰ aktiiviset muistiinpanot"). "</a>";
    }
  }

  echo "<br>";
}

if (strpos($_SERVER['SCRIPT_NAME'], "asiakasmemo.php") !== FALSE) {
  require "inc/footer.inc";
}
