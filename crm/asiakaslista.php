<?php

// k�sitt�m�t�n juttu, mutta ei voi muuta
if ($_POST["voipcall"] != "") $_GET["voipcall"]  = "";

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {

  $tiedostonimi = $tmpfilenimi;

  $toot = fopen("/tmp/".$tiedostonimi, "w");

  fwrite($toot, "SALESPERSON_ID;addressNo;CustomerNo;COMPANY_NAME;CONTACT;CUST_CITY;CUST_STREET;");
  fwrite($toot, "CUST_POST_CODE;CUST_REGION;CUST_COUNTRY;CUST_TELEPHONE;CUST_EMAIL;CALL_DATE");

  $haaslisa = "";

  if (!empty($crm_haas['call_type'])) {
    fwrite($toot, ";CALL_TYPE");
    $haaslisa ." AND kalenteri.kentta02 != '' ";
  }
  if (!empty($crm_haas['opportunity'])) {
    fwrite($toot, ";OPPORTUNITY");
    $haaslisa .= " AND kalenteri.kentta03 != '' ";
  }
  if (!empty($crm_haas['qty'])) {
    fwrite($toot, ";QTY");
    $haaslisa .= " AND kalenteri.kentta04 != '' ";
  }
  if (!empty($crm_haas['opp_proj_date'])) {
    fwrite($toot, ";OPP_PROJ_DATE");
    $haaslisa .= " AND kalenteri.kentta05 != '' ";
  }
  if (!empty($crm_haas['end_reason'])) {
    fwrite($toot, ";END_REASON");
    $haaslisa .= " AND kalenteri.kentta06 != '' ";
  }

  fwrite($toot, "\r\n");

  $asiakaslisa = "";

  if (!empty($mul_asiakasosasto)) {
    $asiakaslisa .= " AND asiakas.osasto IN ('".implode("','", $mul_asiakasosasto)."')";
  }

  if (!empty($mul_asiakasryhma)) {
    $asiakaslisa .= " AND asiakas.ryhma IN ('".implode("','", $mul_asiakasryhma)."')";
  }

  if (!empty($mul_asiakaspiiri)) {
    $asiakaslisa .= " AND asiakas.piiri IN ('".implode("','", $mul_asiakaspiiri)."')";
  }

  if (!empty($mul_asiakasmyyja)) {
    $asiakaslisa .= " AND asiakas.myyjanro IN ('".implode("','", $mul_asiakasmyyja)."')";
  }

  if (!empty($mul_asiakastila)) {
    $asiakaslisa .= " AND asiakas.tila IN ('".implode("','", $mul_asiakastila)."')";
  }

  $dynaaminen_check = false;
  $param = 'asiakas';

  $query = "SELECT DISTINCT (COUNT(node.tunnus) - 1) AS syvyys
            FROM dynaaminen_puu AS node
            JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft)
            WHERE node.yhtio = '{$kukarow['yhtio']}'
            AND node.laji    = '{$param}'
            AND node.lft     > 1
            GROUP BY node.lft
            ORDER BY syvyys";
  $dynpuu_count_result = pupe_query($query, $link);

  while ($count_row = mysql_fetch_assoc($dynpuu_count_result)) {

    if (!isset(${"mul_".$param.$count_row['syvyys']})) {
      ${"mul_".$param.$count_row['syvyys']} = array();
    }
    else {
      $dynaaminen_check = true;
    }
  }

  if ($dynaaminen_check) {
    mysql_data_seek($dynpuu_count_result, 0);

    while ($count_row = mysql_fetch_assoc($dynpuu_count_result)) {

      if (count(${"mul_".$param.$count_row['syvyys']}) > 0) {
        $dynaamiset = '';

        foreach (${"mul_".$param.$count_row['syvyys']} as $dynaaminenx) {
          if (trim($dynaaminenx) != '') {
            $dynaaminenx = trim(mysql_real_escape_string($dynaaminenx));
            $dynaamiset .= "'$dynaaminenx',";
          }
        }

        $dynaamiset = substr($dynaamiset, 0, -1);

        if (trim($dynaamiset) != '') {

          $liitoksetlisa = ", GROUP_CONCAT(DISTINCT puun_alkio.liitos) liitokset ";
          $liitoksetlisawhere = " and puun_alkio.liitos != '' ";

          $query = "SELECT GROUP_CONCAT(DISTINCT node.tunnus) tunnukset
                    {$liitoksetlisa}
                    FROM dynaaminen_puu AS node
                    JOIN puun_alkio ON (puun_alkio.yhtio = node.yhtio AND puun_alkio.puun_tunnus = node.tunnus AND puun_alkio.laji = node.laji)
                    JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft AND parent.tunnus IN ($dynaamiset))
                    WHERE node.yhtio = '{$kukarow['yhtio']}'
                    AND node.laji    = '{$param}'
                    AND node.lft     > 1
                    {$liitoksetlisawhere}
                    ORDER BY node.lft";
          $kaikki_puun_tunnukset_res = pupe_query($query, $link);
          $kaikki_puun_tunnukset_row = mysql_fetch_assoc($kaikki_puun_tunnukset_res);

          if ($kaikki_puun_tunnukset_row["tunnukset"] != "") {
            $lisa_dynaaminen_liitokset[$param] = $kaikki_puun_tunnukset_row["liitokset"];
          }
        }
      }
    }

    // Dynaamisen puun rajaukset2
    if (isset($lisa_dynaaminen_liitokset) and count($lisa_dynaaminen_liitokset) > 0) {
      foreach ($lisa_dynaaminen_liitokset as $d_param => $d_liitokset) {
        if ($d_liitokset != "") {
          $asiaskaslisa .= " and asiakas.tunnus in ($d_liitokset) ";
        }
      }
    }
  }

  $pvmlisa = "";

  if (!empty($crm_haas_date_alku) and !empty($crm_haas_date_loppu)) {
    $_alku_d = (int) $crm_haas_date_alku[0];
    $_alku_m = (int) $crm_haas_date_alku[1];
    $_alku_y = (int) $crm_haas_date_alku[2];

    $_loppu_d = (int) $crm_haas_date_loppu[0];
    $_loppu_m = (int) $crm_haas_date_loppu[1];
    $_loppu_y = (int) $crm_haas_date_loppu[2];

    if (checkdate($_alku_m, $_alku_d, $_alku_y) and checkdate($_loppu_m, $_loppu_d, $_loppu_y)) {
      $pvmlisa  = " AND kalenteri.luontiaika >= '{$_alku_y}-{$_alku_m}-{$_alku_d} 00:00:00' ";
      $pvmlisa .= " AND kalenteri.luontiaika <= '{$_loppu_y}-{$_loppu_m}-{$_loppu_d} 23:59:59' ";
    }
  }

  $query = "SELECT kalenteri.*,
            kalenteri.tunnus AS kalenteritunnus,
            asiakas.*,
            asiakas.tunnus AS asiakastunnus,
            yhteyshenkilo.nimi AS yhteyshenkilo,
            IF(asiakas.gsm != '', asiakas.gsm,
            IF(asiakas.tyopuhelin != '', asiakas.tyopuhelin,
            IF(asiakas.puhelin != '', asiakas.puhelin, ''))) puhelin
            FROM kalenteri
            JOIN asiakas ON (
              asiakas.yhtio = kalenteri.yhtio AND
              asiakas.laji != 'P'
              {$asiakaslisa}
            )
            LEFT JOIN yhteyshenkilo ON (
              kalenteri.yhtio = yhteyshenkilo.yhtio AND
              kalenteri.henkilo = yhteyshenkilo.tunnus AND
              yhteyshenkilo.rooli = 'haas'
            )
            WHERE kalenteri.liitostunnus  = asiakas.tunnus
            AND kalenteri.tyyppi          IN ('Memo','Muistutus','Kuittaus','Lead','Myyntireskontraviesti')
            AND kalenteri.tapa           != 'asiakasanalyysi'
            AND kalenteri.yhtio           = '{$kukarow['yhtio']}'
            AND (kalenteri.perheid = 0 or kalenteri.tunnus = kalenteri.perheid)
            {$pvmlisa}
            {$haaslisa}
            ORDER BY asiakas.tunnus";
  $res = pupe_query($query);

  while ($row = mysql_fetch_assoc($res)) {
    fwrite($toot, "{$row['kuka']};");

    # Halutaan regexpill� numerot ja raput ensimm�iseksi
    # Esim. Pursimiehenkatu 26 C -> 26 C Pursimiehenkatu
    preg_match('/\d.*/', $row['osoite'], $matches);
    $address1 = $matches[0];

    preg_match('/^[^ \d]*/', $row['osoite'], $matches);
    $address2 = $matches[0];

    $address = $address1." ".$address2;

    fwrite($toot, "{$row['asiakastunnus']};");

    fwrite($toot, "{$row['ytunnus']};");

    $nimi = trim($row['nimi'].' '.$row['nimitark']);
    fwrite($toot, "{$nimi};");

    fwrite($toot, "{$row['yhteyshenkilo']};");
    fwrite($toot, "{$row['postitp']};");
    fwrite($toot, "{$address};");
    fwrite($toot, "{$row['postino']};");
    fwrite($toot, "{$row['postitp']};");
    fwrite($toot, "{$row['maa']};");
    fwrite($toot, "{$row['puhelin']};");
    fwrite($toot, "{$row['email']};");
    fwrite($toot, "{$row['luontiaika']}");

    if (!empty($crm_haas['call_type'])) {
      fwrite($toot, ";{$row['kentta02']}");
    }
    if (!empty($crm_haas['opportunity'])) {
      fwrite($toot, ";{$row['kentta03']}");
    }
    if (!empty($crm_haas['qty'])) {
      fwrite($toot, ";{$row['kentta04']}");
    }
    if (!empty($crm_haas['opp_proj_date'])) {
      fwrite($toot, ";{$row['kentta05']}");
    }
    if (!empty($crm_haas['end_reason'])) {
      fwrite($toot, ";{$row['kentta06']}");
    }

    fwrite($toot, "\r\n");
  }

  fclose($toot);

  readfile("/tmp/".basename($tmpfilenimi));
  exit;
}

if (!isset($konserni))   $konserni = '';
if (!isset($tee))     $tee = '';
if (!isset($oper))     $oper = '';

if ($voipcall == "call" and $o != "" and $d != "") {
  ob_start();
  $retval = @readfile($VOIPURL."&o=$o&d=$d");
  $retval = ob_get_contents();
  ob_end_clean();
  if ($retval != "OK") echo "<font class='error'>Soitto $o -&gt; $d ep�onnistui!</font><br><br>";
}

echo "<font class='head'>".t("Asiakaslista")."</font><hr>";

echo "<form method='post'>
    <input type='hidden' name='voipcall' value='kala'>";

$monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA", "ASIAKASPIIRI", "ASIAKASMYYJA", "ASIAKASTILA", "<br>DYNAAMINEN_ASIAKAS");
$monivalintalaatikot_normaali = array();

require "tilauskasittely/monivalintalaatikot.inc";

if ($yhtiorow['konserni'] != "") {
  $chk = "";

  if (trim($konserni) != '') {
    $chk = "CHECKED";
  }

  echo "<br>".t("N�yt� konsernin kaikki asiakkaat").": <input type='checkbox' name='konserni' $chk onclick='submit();'><br>";
}

if ($yhtiorow['viikkosuunnitelma'] == '') {
  $kentat = "asiakas.tunnus::asiakas.nimi::asiakas.asiakasnro::asiakas.ytunnus::if (asiakas.toim_postitp!='',asiakas.toim_postitp,asiakas.postitp)::asiakas.postino::asiakas.yhtio::asiakas.myyjanro::asiakas.email";
}
else {
  $kentat = "asiakas.tunnus::asiakas.nimi::asiakas.myyjanro::asiakas.ytunnus::asiakas.asiakasnro::if (asiakas.toim_postitp!='',asiakas.toim_postitp,asiakas.postitp)::asiakas.yhtio";
}

$jarjestys = poista_osakeyhtio_lyhenne_mysql("nimi").", nimitark, ytunnus, tunnus";

$array = explode("::", $kentat);
$count = count($array);

for ($i = 0; $i <= $count; $i++) {
  if (isset($haku[$i]) and strlen($haku[$i]) > 0) {
    if ($array[$i] == "asiakas.nimi") {
      $lisa .= " AND (asiakas.nimi LIKE '%{$haku[$i]}%'
                      OR asiakas.toim_nimi LIKE '%{$haku[$i]}%'
                      OR asiakas.nimitark LIKE '%{$haku[$i]}%'
                      OR asiakas.toim_nimitark LIKE '%{$haku[$i]}%')";
      $ulisa .= "&haku[" . $i . "]=" . $haku[$i];
    }
    else {
      $lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
      $ulisa .= "&haku[" . $i . "]=" . $haku[$i];
    }
  }
}

if (strlen($ojarj) > 0) {
  $jarjestys = $ojarj;
}

$lisa .= " and asiakas.laji != 'P' ";

if (trim($konserni) != '') {
  $query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
  $result = pupe_query($query);
  $konsernit = "";

  while ($row = mysql_fetch_array($result)) {
    $konsernit .= " '".$row["yhtio"]."' ,";
  }
  $konsernit = " asiakas.yhtio in (".substr($konsernit, 0, -1).") ";
}
else {
  $konsernit = " asiakas.yhtio = '$kukarow[yhtio]' ";
}

if ($yhtiorow['viikkosuunnitelma'] == '') {
  if ($tee == "lahetalista") {
    $query = "SELECT asiakas.tunnus, asiakas.nimi, asiakas.postitp, asiakas.ytunnus, asiakas.yhtio, asiakas.asiakasnro, asiakas.nimitark,
              asiakas.osoite, asiakas.postino, asiakas.postitp, asiakas.maa, asiakas.toim_nimi, asiakas.toim_nimitark, asiakas.toim_osoite,
              asiakas.toim_postino, asiakas.toim_postitp, asiakas.toim_maa, asiakas.puhelin, asiakas.fax, asiakas.myyjanro, asiakas.email,
              asiakas.osasto, asiakas.piiri, asiakas.ryhma, asiakas.fakta, asiakas.toimitustapa, asiakas.yhtio
              FROM asiakas
              WHERE $konsernit
              $lisa";
    $tiednimi = "asiakaslista.xls";
  }
  else {
    $query = "SELECT asiakas.tunnus, if (asiakas.nimi != asiakas.toim_nimi, CONCAT(asiakas.nimi, '<br />', asiakas.toim_nimi), asiakas.nimi) nimi,
              asiakas.asiakasnro, asiakas.ytunnus,  if (asiakas.toim_postitp != '', asiakas.toim_postitp, asiakas.postitp) postitp,
              if (asiakas.toim_postino != 00000, asiakas.toim_postino, asiakas.postino) postino,
              asiakas.yhtio, asiakas.myyjanro, asiakas.email, asiakas.puhelin $selectlisa
              FROM asiakas
              WHERE $konsernit
              $lisa";
    $tiednimi = "viikkosuunnitelma.xls";
  }
}
else {
  $query = "SELECT asiakas.tunnus, asiakas.nimi, (SELECT concat_ws(' ', kuka.myyja, kuka.nimi) FROM kuka WHERE kuka.yhtio = '$kukarow[yhtio]' AND kuka.myyja = asiakas.myyjanro AND kuka.myyja > 0 LIMIT 1) myyja,
            asiakas.ytunnus, asiakas.asiakasnro, if (asiakas.toim_postitp != '', asiakas.toim_postitp, asiakas.postitp) postitp,
            asiakas.puhelin, asiakas.yhtio
            FROM asiakas
            WHERE $konsernit
            $lisa";
}
if ($lisa == "" and ($tee != 'laheta' or $tee != 'lahetalista')) {
  $limit = " LIMIT 200 ";
}
else {
  $limit = " ";
}

$query .= "$ryhma ORDER BY $jarjestys $limit";
$result = pupe_query($query);

if ($oper == t("Vaihda listan kaikkien asiakkaiden tila")) {
  // K�yd��n lista l�pi kertaalleen
  while ($trow = mysql_fetch_array($result)) {
    $query_update = "UPDATE asiakas
                     SET tila = '$astila_vaihto'
                     WHERE tunnus = '$trow[tunnus]'
                     AND yhtio    = '$yhtiorow[yhtio]'";
    $result_update = pupe_query($query_update);
  }
  $result = pupe_query($query);
}

if ($tee == 'laheta' or $tee == 'lahetalista') {

  if ($tee == "lahetalista") {
    if (@include 'Spreadsheet/Excel/Writer.php') {
      //keksit��n failille joku varmasti uniikki nimi:
      list($usec, $sec) = explode(' ', microtime());
      mt_srand((float) $sec + ((float) $usec * 100000));
      $excelnimi = md5(uniqid(mt_rand(), true)).".xls";

      $workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
      $workbook->setVersion(8);
      $worksheet =& $workbook->addWorksheet('Sheet 1');

      $format_bold =& $workbook->addFormat();
      $format_bold->setBold();

      $excelrivi = 0;

      if (isset($workbook)) {
        $excelsarake = 0;

        for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
          if (isset($workbook)) {
            $worksheet->write($excelrivi, $excelsarake, t(mysql_field_name($result, $i)) , $format_bold);
            $excelsarake++;
          }
        }

        $excelsarake = 0;
        $excelrivi++;
      }
    }
  }
  else {
    if (@include 'Spreadsheet/Excel/Writer.php') {
      //keksit��n failille joku varmasti uniikki nimi:
      list($usec, $sec) = explode(' ', microtime());
      mt_srand((float) $sec + ((float) $usec * 100000));
      $excelnimi = md5(uniqid(mt_rand(), true)).".xls";

      $workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
      $workbook->setVersion(8);
      $worksheet =& $workbook->addWorksheet('Sheet 1');

      $format_bold =& $workbook->addFormat();
      $format_bold->setBold();

      $excelrivi = 0;

      if (isset($workbook)) {
        $excelsarake = 0;

        for ($i=1; $i<mysql_num_fields($result); $i++) {
          //$liite .= $trow[$i]."\t";
          if (isset($workbook)) {
            $worksheet->write($excelrivi, $excelsarake, t(mysql_field_name($result, $i)) , $format_bold);
            $excelsarake++;
          }
        }

        $worksheet->write($excelrivi, $excelsarake, t("pvm") , $format_bold);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, t("kampanjat") , $format_bold);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, t("pvm k�yty") , $format_bold);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, t("km") , $format_bold);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, t("l�ht�") , $format_bold);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, t("paluu") , $format_bold);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, t("pvraha") , $format_bold);
        $excelsarake++;
        $worksheet->write($excelrivi, $excelsarake, t("kommentti") , $format_bold);

        $excelsarake = 0;
        $excelrivi++;
      }
    }
  }

  while ($trow = mysql_fetch_array($result)) {
    $excelsarake = 0;
    for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
      if (isset($workbook)) {
        $worksheet->writeString($excelrivi, $excelsarake, $trow[$i], $format_bold);
        $excelsarake++;
      }
    }
    $excelrivi++;
  }

  if (isset($workbook)) {
    // We need to explicitly close the workbook
    $workbook->close();
  }

  $liite = "/tmp/".$excelnimi;

  $bound = uniqid(time()."_") ;

  $header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
  $header .= "MIME-Version: 1.0\n" ;
  $header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

  $content = "--$bound\n" ;

  $content .= "Content-Type: application/excel; name=\"".basename($liite)."\"\n" ;
  $content .= "Content-Transfer-Encoding: base64\n" ;
  $content .= "Content-Disposition: inline; filename=\"".basename($tiednimi)."\"\n\n";

  $handle  = fopen($liite, "r");
  $sisalto = fread($handle, filesize($liite));
  fclose($handle);

  $content .= chunk_split(base64_encode($sisalto));
  $content .= "\n" ;

  if ($tee == "lahetalista") {
    mail($kukarow['eposti'], mb_encode_mimeheader("Asiakkaiden tiedot", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
    echo "<br><br><font class='message'>".t("Asiakkaiden tiedot s�hk�postiisi")."!</font><br><br><br>";
  }
  else {
    mail($kukarow['eposti'], mb_encode_mimeheader("Viikkosunnitelmapohja", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
    echo "<br><br><font class='message'>".t("Suunnitelmapohja l�hetetty s�hk�postiisi")."!</font><br><br><br>";
  }

  mysql_data_seek($result, 0);
}

echo "<br><table>";
echo "<tr>";

for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
  echo "<th><a href='$PHP_SELF?konserni=$konserni&ojarj=".mysql_field_name($result, $i).$ulisa."'>" . t(mysql_field_name($result, $i)) . "</a>";

  if   (mysql_field_len($result, $i)>10) $size='20';
  elseif  (mysql_field_len($result, $i)<5)  $size='5';
  else  $size='10';

  if (!isset($haku[$i])) $haku[$i] = '';

  echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result, $i) ."'>";
  echo "</th>";
}

echo "<td class='back'>&nbsp;&nbsp;<input type='submit' class='hae_btn' value='".t("Etsi")."'></td></tr>\n\n";

$kalalask = 1;

while ($trow=mysql_fetch_array($result)) {
  echo "<tr class='aktiivi'>";

  for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
    if ($i == 1) {
      if (trim($trow[1]) == '') $trow[1] = t("*tyhj�*");
      echo "<td><a name='1_$kalalask' href='".$palvelin2."crm/asiakasmemo.php?ytunnus=$trow[ytunnus]&asiakasid=$trow[tunnus]&lopetus=".$palvelin2."crm/asiakaslista.php////konserni=$konserni//ojarj=$ojarj".str_replace("&", "//", $ulisa)."///1_$kalalask'>$trow[1]</a></td>";
    }
    elseif (mysql_field_name($result, $i) == 'ytunnus') {
      echo "<td><a name='2_$kalalask' href='".$palvelin2."yllapito.php?toim=asiakas&tunnus=$trow[tunnus]&lopetus=".$palvelin2."crm/asiakaslista.php////konserni=$konserni//ojarj=$ojarj".str_replace("&", "//", $ulisa)."///2_$kalalask'>$trow[$i]</a></td>";
    }
    else {
      echo "<td>$trow[$i]</td>";
    }
  }

  echo "<td class='back'>";

  if ($trow["puhelin"] != "" and $kukarow["puhno"] != "" and isset($VOIPURL)) {
    $d = ereg_replace("[^0-9]", "", $trow["puhelin"]);  // dest
    $o = ereg_replace("[^0-9]", "", $kukarow["puhno"]); // orig
    echo "<a href='$PHP_SELF?konserni=$konserni&ojarj=$ojarj&o=$o&d=$d&voipcall=call".$ulisa."'>Soita $o -&gt; $d</a>";
  }

  echo "</td>";
  echo "</tr>\n\n";

  $kalalask++;
}
echo "</table>";

if ($yhtiorow['viikkosuunnitelma'] == '') {
  echo "<br><br>";
  echo "<li><a href='$PHP_SELF?tee=laheta&konserni=$konserni".$ulisa."'>".t("L�het� viikkosuunnitelmapohja s�hk�postiisi")."</a><br>";
  echo "<li><a href='$PHP_SELF?tee=lahetalista&konserni=$konserni".$ulisa."'>".t("L�het� asiakaslista s�hk�postiisi")."</a><br>";
}

$asosresult = t_avainsana("ASIAKASTILA");

if (mysql_num_rows($asosresult) > 0) {
  echo "<br/>";
  echo t("Vaihda asiakkaiden tila").": <select name='astila_vaihto'>";

  while ($asosrow = mysql_fetch_array($asosresult)) {
    $sel2 = '';
    if ($astila == $asosrow["selite"]) {
      $sel2 = "selected";
    }
    echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
  }

  echo "</select></td></tr>\n\n";
  echo "<input type=\"submit\" name=\"oper\" value=\"".t("Vaihda listan kaikkien asiakkaiden tila")."\">";
}

echo "</form>";

$crm_haas_res = t_avainsana("CRM_HAAS");
$crm_haas_row = mysql_fetch_assoc($crm_haas_res);
$crm_haas_check = (mysql_num_rows($crm_haas_res) > 0 and $crm_haas_row['selite'] == 'K');

if ($crm_haas_check) {
  echo "<br><br>";

  $tiedostonimi = "crm-haas-{$kukarow['yhtio']}-".date("YmdHis").".csv";

  echo "<form method='post' class='multisubmit' action='?{$ulisa}'>";
  echo "<table>";

  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='$tiedostonimi'>";
  echo "<input type='hidden' name='tmpfilenimi' value='$tiedostonimi'>";

  echo "<tr><td class='back'><font class='info'>";
  echo t("Huom. taulukon omat rajaukset eiv�t vaikuta aineiston luontiin");
  echo "</font></td></tr>";

  echo "<tr>";
  echo "<th>",t("Valitse CRM Haas -kent�t"),"</th>";
  echo "<td>";
  echo "<input type='checkbox' name='crm_haas[call_type]' /> CALL_TYPE<br>";
  echo "<input type='checkbox' name='crm_haas[opportunity]' /> OPPORTUNITY<br>";
  echo "<input type='checkbox' name='crm_haas[qty]' /> QTY<br>";
  echo "<input type='checkbox' name='crm_haas[opp_proj_date]' /> OPP_PROJ_DATE<br>";
  echo "<input type='checkbox' name='crm_haas[end_reason]' /> END_REASON";
  echo "</tr>";

  echo "<tr>";
  echo "<th>",t("Aikarajaus"),"</th>";
  echo "<td>";
  echo "<input type='text' name='crm_haas_date_alku[]' value='",date('d', mktime(0, 0, 0, date('m'), date('d'), date('Y')-1)),"' size='3' maxlength='2' /> ";
  echo "<input type='text' name='crm_haas_date_alku[]' value='",date("m", mktime(0, 0, 0, date('m'), date('d'), date('Y')-1)),"' size='3' maxlength='2' /> ";
  echo "<input type='text' name='crm_haas_date_alku[]' value='",date("Y", mktime(0, 0, 0, date('m'), date('d'), date('Y')-1)),"' size='5' maxlength='4' /> ";
  echo " - ";
  echo "<input type='text' name='crm_haas_date_loppu[]' value='",date("d"),"' size='3' maxlength='2' /> ";
  echo "<input type='text' name='crm_haas_date_loppu[]' value='",date("m"),"' size='3' maxlength='2' /> ";
  echo "<input type='text' name='crm_haas_date_loppu[]' value='",date("Y"),"' size='5' maxlength='4' /> ";
  echo "</td>";
  echo "</tr>";
  echo "<tr><td colspan='2' class='back'><input type='submit' value='",t("Tallenna CSV"),"' /></td></tr>";
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";
}

require "inc/footer.inc";
