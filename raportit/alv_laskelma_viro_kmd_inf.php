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

$sel = "{$rajaa}" == "1000" ? "selected" : "";

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
echo "<th>",t("Tee Excel"),"</th>";
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
    $tilausrivijoin = "JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)";
    $tilaustyyppi = "and lasku.tilaustyyppi != '9'";
  }
  else {
    $taso = 'ee500';
    $eetasolisa = "or alv_taso like '%ee510%' or alv_taso like '%ee520%'";
    $tilat = "and lasku.tila IN ('H','Y','M','P','Q')";
    $tilausrivijoin = "";
    $tilaustyyppi = "";
  }

  $query = "SELECT
            group_concat(concat(\"'\",tilino,\"'\")) tilitMUU
            FROM tili
            WHERE yhtio = '$kukarow[yhtio]'
            and (alv_taso like '%$taso%' $eetasolisa)";
  $tilires = pupe_query($query);
  $tilirow = mysql_fetch_assoc($tilires);

  if ("{$rajaa}" == "1000") {
    $rajaalisa = "HAVING veloitukset > 1000 or hyvitykset > 1000";
  }
  else {
    $rajaalisa = "";
  }

  $query = "SELECT lasku.tunnus ltunnus,
            lasku.laskunro laskunro,
            trim(concat(lasku.nimi, ' ', lasku.nimitark)) nimi,
            lasku.ytunnus,
            lasku.tapvm,
            lasku.alv,
            lasku.liitostunnus,
            sum(tiliointi.vero) veropros,
            sum(lasku.summa) laskun_summa,
            sum(round(tiliointi.summa * if('veronmaara'='$oletus_verokanta', $oletus_verokanta, vero) / 100, 2)) veronmaara,
            sum(tiliointi.summa) summa,
            abs(sum(if(tiliointi.summa > 0, tiliointi.summa, 0))) veloitukset,
            abs(sum(if(tiliointi.summa < 0, tiliointi.summa, 0))) hyvitykset
            FROM lasku
            JOIN tiliointi ON (
              tiliointi.yhtio = lasku.yhtio AND
              tiliointi.ltunnus = lasku.tunnus AND
              tiliointi.korjattu = '' AND
              tiliointi.tapvm    >= '{$alkupvm}' AND
              tiliointi.tapvm    <= '{$loppupvm}' AND
              tiliointi.tilino in ({$tilirow['tilitMUU']})
            )
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            {$tilat}
            {$tilaustyyppi}
            AND lasku.tapvm    >= '{$alkupvm}'
            AND lasku.tapvm    <= '{$loppupvm}'
            GROUP BY 1,2,3,4,5,6,7
            {$rajaalisa}";
  $result = pupe_query($query);

  $verot_yht = 0;

  pupe_DataTables(array(array($pupe_DataTables, 9, 9, true)));

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
  echo "</tr>";

  if (isset($worksheet)) {
    $worksheet->writeString($excelrivi, $excelsarake, t("CSV"), $format_bold);
    $excelsarake++;

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
  echo "</tr>";

  echo "</thead>";

  echo "<tbody>";

  $_i = 1;

  while ($row = mysql_fetch_assoc($result)) {

    if ($laskelma == 'a' and $row['veropros'] == 0) continue;

    $_vero = $laskelma == 'a' ? $row['summa'] : $row['veronmaara'];

    $aineistoon = $_green;

    if ($laskelma == 'a') {

      $query = "  SELECT tunnus
                  FROM asiakas
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus = '{$row['liitostunnus']}'
                  AND laji = 'H'";
      $asiakasres = pupe_query($query);

      if (mysql_num_rows($asiakasres) != 0) $aineistoon = $_red;
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
    echo "<td>$row[ytunnus]</td>";
    echo "<td>$row[nimi]</td>";
    echo "<td>$row[laskunro] ($row[ltunnus])</td>";
    echo "<td>",pupe_DataTablesEchoSort($row['tapvm']).tv1dateconv($row['tapvm']),"</td>";
    echo "<td>$row[laskun_summa]</td>";
    echo "<td>$row[alv]</td>";
    echo "<td>$_vero</td>";
    echo "</tr>";

    if (isset($worksheet)) {

      $excelsarake = 0;

      $worksheet->writeString($excelrivi, $excelsarake, ($aineistoon == $_green ? "X" : ""));
      $excelsarake++;

      $worksheet->write($excelrivi, $excelsarake, $_i);
      $excelsarake++;

      $worksheet->write($excelrivi, $excelsarake, $row['ytunnus']);
      $excelsarake++;

      $worksheet->writeString($excelrivi, $excelsarake, $row['nimi']);
      $excelsarake++;

      $worksheet->write($excelrivi, $excelsarake, $row['laskunro']);
      $excelsarake++;

      $worksheet->write($excelrivi, $excelsarake, tv1dateconv($row['tapvm']));
      $excelsarake++;

      $worksheet->write($excelrivi, $excelsarake, $row['laskun_summa']);
      $excelsarake++;

      $worksheet->write($excelrivi, $excelsarake, $row['alv']);
      $excelsarake++;

      $worksheet->write($excelrivi, $excelsarake, $_vero);
      $excelsarake++;

      $excelrivi++;
    }

    if ($laskelma == 'a') {
      $_csv['A'][] = array(
        'buyerRegCode' => $row['ytunnus'],
        'buyerName' => $row['nimi'],
        'invoiceNumber' => $row['laskunro'],
        'invoiceDate' => tv1dateconv($row['tapvm']),
        'invoiceSum' => $row['laskun_summa'],
        'taxRate' => $row['alv'],
        'invoiceSumForRate' => $row['laskun_summa'],
        'sumForRateInPeriod' => $_vero,
        'comments' => '',
      );
    }
    else {
      $_csv['B'][] = array(
        'sellerRegCode' => $row['ytunnus'],
        'sellerName' => $row['nimi'],
        'invoiceNumber' => $row['laskunro'],
        'invoiceDate' => tv1dateconv($row['tapvm']),
        'invoiceSumVat' => $row['laskun_summa'],
        'vatSum' => $_vero,
        'vatInPeriod' => $_vero,
        'comments' => '',
      );
    }

    $verot_yht += $_vero;
    $_i++;
  }

  echo "<tfoot>";
  echo "<tr>";
  echo "<th colspan='9'>";
  echo "verot yht";
  echo "<span style='float: right;'>",round($verot_yht, 2),"</span>";
  echo "</th>";
  echo "</tr>";
  echo "</tfoot>";

  echo "</tbody>";

  echo "</table>";

  if (isset($worksheet)) {

    $excelnimi = $worksheet->close();

    echo "<br />";
    echo "<br />";
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

  if (count($_csv['A']) > 0) {
    $_csv_file = "header;".implode(";", $_csv['header'])."\n";

    foreach ($_csv['A'] as $_a) {
      $_csv_file .= "A;".implode(";", $_a)."\n";
    }
  }
  elseif (count($_csv['B']) > 0) {
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

    echo "<br />";
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
