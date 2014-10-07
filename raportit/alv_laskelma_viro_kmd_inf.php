<?php

$pupe_DataTables = 'alv_laskelma_viro_kmd_inf';

if (isset($_REQUEST["tee"])) {
  if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/", "", $_REQUEST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

if (!isset($laskelma)) $laskelma = 'a';
if (!isset($vv)) $vv = date("Y");
if (!isset($kk)) $kk = date("m");
if (!isset($rajaa)) $rajaa = "";
if (!isset($per_paiva)) $per_paiva = "";
if (!isset($tee_excel)) $tee_excel = "";
if (!isset($tee)) $tee = "";

$_rajaa_chk = ("{$rajaa}" == "1000");

echo "<font class='head'>",t("ALV-laskelma KMD INF"),"</font><hr />";

echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='laskelma' />";
echo "<table>";
echo "<tr>";
echo "<th>",t("Valitse"),"</th>";

$sel = $laskelma == "b" ? "selected" : "";

echo "<td>";
echo "<select name='laskelma'>";
echo "<option value='a'>",t("Laskelma A")," (",t("myynti"),")</option>";
echo "<option value='b' {$sel}>",t("Laskelma B")," (",t("osto"),")</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<th>",t("Valitse kausi"),"</th>";
echo "<td>";

$vv_select = date("Y");

echo "<select name='vv'>";
for ($i = $vv_select; $i >= $vv_select-4; $i--) {
  $sel = $vv == $i ? "selected" : "";
  echo "<option value='{$i}' {$sel}>{$i}</option>";
}
echo "</select>";

echo "<select name='kk'>";
for ($i = 1; $i <= 12; $i++) {
  $sel = $i == $kk ? "selected" : "";
  echo "<option value='{$i}' {$sel}>",str_pad($i, 2, floor($i / 10), STR_PAD_LEFT),"</option>";
}
echo "</select>";

echo "</td>";
echo "</tr>";

$sel = $_rajaa_chk ? "selected" : "";

echo "<tr>";
echo "<th>",t("Rajaa"),"</th>";
echo "<td>";
echo "<select name='rajaa'>";
echo "<option value=''>",t("Näytä kaikki"),"</option>";
echo "<option value='1000' {$sel}>",t("Vain 1000 EUR"),"</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

$chk = !empty($per_paiva) ? "checked" : "";

echo "<tr>";
echo "<th>",t("Aja laskelma per päivä"),"</th>";
echo "<td><input type='checkbox' name='per_paiva' value='1' {$chk} /></td>";
echo "</tr>";

$chk = !empty($tee_excel) ? "checked" : "";

echo "<tr>";
echo "<th>",t("Tee Excel"),"<br /></th>";
echo "<td><input type='checkbox' name='tee_excel' {$chk} /></td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='2' class='back'><input type='submit' value='",t("Näytä"),"' /></td>";
echo "</tr>";

echo "</table>";
echo "</form>";

if ($tee == 'laskelma') {

  $_csv = array(
    'header' => array(
      'taxPayerRegCode' => $yhtiorow['ytunnus'],
      'submitterPersonCode' => $kukarow['kuka'],
      'year' => $vv,
      'month' => $kk,
      'declarationType' => 1
    ),
  );

  if (!empty($tee_excel)) {
    include 'inc/pupeExcel.inc';

    $worksheet = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi = $excelsarake = 0;
  }

  $oletus_verokanta = 20;

  if (!empty($per_paiva)) {

    echo "<br />";

    $_url = "{$palvelin2}raportit/alv_laskelma_viro_kmd_inf.php";
    $_url .= "?tee=laskelma&laskelma={$laskelma}&rajaa={$rajaa}&kk={$kk}&vv={$vv}";

    echo "<a href='{$_url}&per_paiva=",($per_paiva-1),"'>",t("Edellinen päivä"),"</a> ";
    echo t("ALV-laskelma KMD INF")," ",t("päivältä")," {$per_paiva}.{$kk}.{$vv} ";
    echo "<a href='{$_url}&per_paiva=",($per_paiva+1),"'>",t("Seuraava päivä"),"</a>";

    $alkupvm     = date("Y-m-d", mktime(0, 0, 0, $kk, $per_paiva, $vv));
    $loppupvm    = date("Y-m-d", mktime(0, 0, 0, $kk, $per_paiva, $vv));
  }
  else {
    $alkupvm = date("Y-m-d", mktime(0, 0, 0, $kk,   1, $vv));
    $loppupvm = date("Y-m-d", mktime(0, 0, 0, $kk+1, 0, $vv));
  }

  # ee100 = myynti
  # ee500 = osto
  # myyntilasku tila U
  # ostolasku tilat HYMPQ
  if ($laskelma == 'a') {
    $taso = 'ee100';
    $eetasolisa = "or alv_taso like '%ee110%'";
    $tilat = "and lasku.tila = 'U'";
    $tilaustyyppi = "and lasku.tilaustyyppi != '9'";
    $laskun_lisatiedot_lisa = "
      LEFT JOIN laskun_lisatiedot AS ll ON (
        ll.yhtio = lasku.yhtio AND
        ll.otunnus = lasku.tunnus
      )";
    $verolisa = "AND tiliointi.vero > 0";
    $laskun_nimi_lisa_select = "trim(concat(ll.laskutus_nimi, ' ', ll.laskutus_nimitark)) nimi,";
  }
  else {
    $taso = 'ee500';
    $eetasolisa = "or alv_taso like '%ee510%' or alv_taso like '%ee520%'";
    $tilat = "and lasku.tila IN ('H','Y','M','P','Q','X')";
    $tilaustyyppi = "";
    $laskun_lisatiedot_lisa = "";
    $verolisa = "";
    $laskun_nimi_lisa_select = "trim(concat(lasku.nimi, ' ', lasku.nimitark)) nimi,";
  }

  $query = "SELECT
            group_concat(concat(\"'\",tilino,\"'\")) tilitMUU
            FROM tili
            WHERE yhtio = '$kukarow[yhtio]'
            and (alv_taso like '%$taso%' $eetasolisa)";
  $tilires = pupe_query($query);
  $tilirow = mysql_fetch_assoc($tilires);

  $rajaalisa = "";

  if ($_rajaa_chk) {

    $query = "SELECT lasku.ytunnus,
          lasku.liitostunnus exclude_asiakkaat
          FROM lasku
          JOIN tiliointi ON (
            tiliointi.yhtio = lasku.yhtio AND
            tiliointi.ltunnus = lasku.tunnus AND
            tiliointi.korjattu = '' AND
            tiliointi.tapvm    >= '{$alkupvm}' AND
            tiliointi.tapvm    <= '{$loppupvm}' AND
            tiliointi.tilino in ({$tilirow['tilitMUU']})
            {$verolisa}
          )
          WHERE lasku.yhtio = '{$kukarow['yhtio']}'
          {$tilat}
          {$tilaustyyppi}
          GROUP BY 1,2
          HAVING abs(sum(if(tiliointi.summa > 0, tiliointi.summa, 0))) < {$rajaa}
          AND abs(sum(if(tiliointi.summa < 0, tiliointi.summa, 0))) < {$rajaa}";
    $result = pupe_query($query);

    $_exclude_asiakkaat = array();

    while ($row = mysql_fetch_assoc($result)) {
      $_exclude_asiakkaat[$row['exclude_asiakkaat']] = $row['exclude_asiakkaat'];
    }

    if (!empty($_exclude_asiakkaat)) {
      $rajaalisa = "and lasku.liitostunnus NOT IN (".implode(",", $_exclude_asiakkaat).")";
    }
  }

  $query = "SELECT tiliointi.ltunnus,
            max(tiliointi.vero) veropros,
            sum(round(tiliointi.summa * vero / 100, 2)) veronmaara,
            sum(tiliointi.summa) summa
            FROM tiliointi
            WHERE tiliointi.yhtio = '{$kukarow['yhtio']}'
            AND tiliointi.korjattu = ''
            AND tiliointi.tapvm    >= '{$alkupvm}'
            AND tiliointi.tapvm    <= '{$loppupvm}'
            AND tiliointi.tilino in ({$tilirow['tilitMUU']})
            {$verolisa}
            GROUP BY 1";
  $result = pupe_query($query);

  $verot_yht = 0;
  $verot_csv_yht = 0;

  pupe_DataTables(array(array($pupe_DataTables, 10, 10, true)));

  $style = "width: 15px; height: 15px; display: inline-table; border-radius: 50%; -webkit-border-radius: 50%; -moz-border-radius: 50%;";

  $_green = "<span style='{$style} background-color: #5D2; margin-right: 5px;'></span>";
  $_red = "<span style='{$style} background-color: #E66; margin-right: 5px;'></span>";

  echo "<table class='display dataTable' id='{$pupe_DataTables}'>";

  echo "<thead>";

  echo "<tr>";
  echo "<th>CSV</th>";
  echo "<th>#</th>";
  echo "<th>ytunnus</th>";
  echo "<th>nimi</th>";
  echo "<th>laskunro</th>";
  echo "<th>pvm</th>";
  echo "<th>laskun summa</th>";
  echo "<th>alv</th>";
  echo "<th>verot</th>";
  echo "<th>erikoiskoodi</th>";
  echo "</tr>";

  if (isset($worksheet)) {
    $worksheet->writeString($excelrivi, $excelsarake, "#", $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Nimi"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Laskunro"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Pvm"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Laskun summa"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("ALV"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Verot"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Erikoiskoodi"), $format_bold);
    $excelsarake++;

    $excelrivi++;
  }

  echo "<tr>";
  echo "<td><input type='text'   class='search_field' name='search_aineistoon'></td>";
  echo "<td><input type='text'   class='search_field' name='search_nr'></td>";
  echo "<td><input type='text'   class='search_field' name='search_ytunnus'></td>";
  echo "<td><input type='text'   class='search_field' name='search_nimi'></td>";
  echo "<td><input type='text'   class='search_field' name='search_laskunro'></td>";
  echo "<td><input type='text'   class='search_field' name='search_pvm'></td>";
  echo "<td><input type='text'   class='search_field' name='search_laskun_summa'></td>";
  echo "<td><input type='text'   class='search_field' name='search_alv'></td>";
  echo "<td><input type='text'   class='search_field' name='search_verot'></td>";
  echo "<td><input type='text'   class='search_field' name='search_erikoiskoodi'></td>";
  echo "</tr>";

  echo "</thead>";

  echo "<tbody>";

  $_lopetus_arr = array(
    'tee' => $tee,
    'laskelma' => $laskelma,
    'vv' => $vv,
    'kk' => $kk,
    'rajaa' => $rajaa,
    'per_paiva' => $per_paiva,
    'tee_excel' => $tee_excel,
  );

  $lopetus = "{$palvelin2}raportit/alv_laskelma_viro_kmd_inf.php////";

  foreach ($_lopetus_arr as $_key => $_val) {
    $lopetus .= "{$_key}={$_val}//";
  }

  $_i = 1;

  while ($row = mysql_fetch_assoc($result)) {

    $query = "SELECT lasku.laskunro laskunro,
              {$laskun_nimi_lisa_select}
              lasku.ytunnus,
              lasku.tapvm,
              lasku.alv,
              lasku.liitostunnus,
              lasku.tunnus,
              round(lasku.summa / (1+lasku.alv/100), {$yhtiorow['hintapyoristys']}) laskun_summa
              FROM lasku
              {$laskun_lisatiedot_lisa}
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              {$tilat}
              {$tilaustyyppi}
              AND lasku.tunnus = '{$row['ltunnus']}'
              {$rajaalisa}";
    $laskures = pupe_query($query);
    $laskurow = mysql_fetch_assoc($laskures);

    if (!empty($laskurow['tunnus']) and $laskelma == 'a') {
      $query = "SELECT round(sum(rivihinta), {$yhtiorow['hintapyoristys']}) rivihinta_summa
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND uusiotunnus = '{$laskurow['tunnus']}'
                AND var NOT IN ('P','J','O','S')
                AND tyyppi != 'D'";
      $_tilsum_res = pupe_query($query);
      $_tilsum_row = mysql_fetch_assoc($_tilsum_res);

      $laskurow['laskun_summa'] = $_tilsum_row['rivihinta_summa'];
    }

    if ($laskelma == 'a') {
      $_vero = $row['summa'];
    }
    else {
      $_vero = $row['veronmaara'];
    }

    if (trim($_vero) == '' or $_vero == 0) continue;
    if (($laskurow['laskun_summa'] == '' or $laskurow['laskun_summa'] == 0) and ($row['veropros'] == '' or $row['veropros'] == 0)) continue;

    $aineistoon = $_green;

    if ($laskelma == 'a') {

      $query = "  SELECT tunnus
                  FROM asiakas
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus = '{$laskurow['liitostunnus']}'
                  AND laji = 'H'";
      $asiakasres = pupe_query($query);

      if (mysql_num_rows($asiakasres) != 0) $aineistoon = $_red;
    }

    if (mysql_num_rows($laskures) == 0) $aineistoon = $_red;

    $erikoiskoodi = $row['veropros'] == 0 ? '03' : '';

    $_sum_vertailu_a = 10000 * abs($laskurow['laskun_summa']);
    $_sum_vertailu_b = 10000 * abs($_vero);

    if (!empty($laskurow['laskun_summa']) and $_sum_vertailu_a != $_sum_vertailu_b) {
      $erikoiskoodi = '03';
    }

    $_class = $aineistoon == $_red ? 'spec' : '';

    echo "<tr class='$_class aktiivi'>";

    echo "<td>";
    echo $aineistoon;

    if ($aineistoon == $_green) {
      echo "<span style='display: none;'>X</span>";
    }

    echo "</td>";

    echo "<td>$_i</td>";
    echo "<td>$laskurow[ytunnus]</td>";
    echo "<td>$laskurow[nimi]</td>";
    echo "<td>$laskurow[laskunro]</td>";
    echo "<td>",pupe_DataTablesEchoSort($laskurow['tapvm']).tv1dateconv($laskurow['tapvm']),"</td>";
    echo "<td>$laskurow[laskun_summa]</td>";
    echo "<td>$row[veropros]</td>";
    echo "<td><a href='{$palvelin2}muutosite.php?tee=E&tunnus=$row[ltunnus]&lopetus={$lopetus}'>",abs($_vero),"</a></td>";
    echo "<td>{$erikoiskoodi}</td>";
    echo "</tr>";

    if (isset($worksheet)) {

      $excelsarake = 0;

      $_exceliin = false;

      if ($_rajaa_chk) {
        if ($aineistoon == $_green) {
          $_exceliin = true;
        }
      }
      else {
        $_exceliin = true;
      }

      if ($_exceliin) {
        $worksheet->write($excelrivi, $excelsarake, $_i);
        $excelsarake++;

        $worksheet->write($excelrivi, $excelsarake, $laskurow['ytunnus']);
        $excelsarake++;

        $worksheet->writeString($excelrivi, $excelsarake, $laskurow['nimi']);
        $excelsarake++;

        $worksheet->write($excelrivi, $excelsarake, $laskurow['laskunro']);
        $excelsarake++;

        $worksheet->write($excelrivi, $excelsarake, tv1dateconv($laskurow['tapvm']));
        $excelsarake++;

        $worksheet->write($excelrivi, $excelsarake, $laskurow['laskun_summa']);
        $excelsarake++;

        $worksheet->write($excelrivi, $excelsarake, $row['veropros']);
        $excelsarake++;

        $worksheet->write($excelrivi, $excelsarake, abs($_vero));
        $excelsarake++;

        $worksheet->write($excelrivi, $excelsarake, $erikoiskoodi);
        $excelsarake++;

        $excelrivi++;
      }
    }

    if ($_rajaa_chk and $aineistoon == $_green) {

      if ($laskelma == 'a') {
        $_csv['A'][] = array(
          'buyerRegCode' => $laskurow['ytunnus'],
          'buyerName' => $laskurow['nimi'],
          'invoiceNumber' => $laskurow['laskunro'],
          'invoiceDate' => tv1dateconv($laskurow['tapvm']),
          'invoiceSum' => $laskurow['laskun_summa'],
          'taxRate' => $row['veropros'],
          'invoiceSumForRate' => $laskurow['laskun_summa'],
          'sumForRateInPeriod' => abs($_vero),
          'comments' => $erikoiskoodi,
        );
      }
      else {
        $_csv['B'][] = array(
          'sellerRegCode' => $laskurow['ytunnus'],
          'sellerName' => $laskurow['nimi'],
          'invoiceNumber' => $laskurow['laskunro'],
          'invoiceDate' => tv1dateconv($laskurow['tapvm']),
          'invoiceSumVat' => $laskurow['laskun_summa'],
          'vatSum' => abs($_vero),
          'vatInPeriod' => abs($_vero),
          'comments' => $erikoiskoodi,
        );
      }
    }

    $verot_yht += $_vero;

    if ($_rajaa_chk) {
      $verot_csv_yht += $aineistoon == $_green ? $_vero : 0;
    }
    else {
      $verot_csv_yht += $_vero;
    }

    $_i++;
  }

  echo "<tfoot>";

  echo "<tr><th colspan='10'>";
  echo t("Yhteensä")," (",t("ilman ALV"),")";
  echo "<span style='float: right;'>",round(abs($verot_yht), 2),"</span>";
  echo "</th></tr>";

  if ($_rajaa_chk) {
    echo "<tr><th colspan='10'>";
    echo t("Yhteensä")," CSV (",t("ilman ALV"),")";
    echo "<span style='float: right;'>",round(abs($verot_csv_yht), 2),"</span>";
    echo "</th></tr>";
  }

  echo "</tfoot>";

  echo "</tbody>";

  echo "</table>";

  echo "<br /><br />";

  if (isset($worksheet)) {

    $excelnimi = $worksheet->close();

    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='ee_vat_kmd_inf.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='{$excelnimi}'>";
    echo "<table>";
    echo "<tr><th>", t("Tallenna (xlsx)"), ":</th>";
    echo "<td class='back'><input type='submit' value='", t("Tallenna"), "'></td></tr>";
    echo "</table>";
    echo "</form>";
  }

  if ($_rajaa_chk and !empty($_csv['A'])) {
    $_csv_file = "header;".implode(";", $_csv['header'])."\n";

    foreach ($_csv['A'] as $_a) {
      $_csv_file .= "A;".implode(";", $_a)."\n";
    }
  }
  elseif ($_rajaa_chk and !empty($_csv['B'])) {
    $_csv_file = "header;".implode(";", $_csv['header'])."\n";

    foreach ($_csv['B'] as $_b) {
      $_csv_file .= "B;".implode(";", $_b)."\n";
    }
  }
  else {
    $_csv_file = "";
  }

  if (!empty($_csv_file)) {

    $_csv_file = utf8_encode($_csv_file);
    $csvnimi = md5(uniqid(mt_rand(), true))."csv";

    file_put_contents("/tmp/".$csvnimi, $_csv_file);

    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='ee_vat_kmd_inf.csv'>";
    echo "<input type='hidden' name='tmpfilenimi' value='{$csvnimi}'>";
    echo "<table>";
    echo "<tr><th>", t("Tallenna (csv)"), ":</th>";
    echo "<td class='back'><input type='submit' value='", t("Tallenna"), "'></td></tr>";
    echo "</table>";
    echo "</form>";
  }
}

require "inc/footer.inc";
