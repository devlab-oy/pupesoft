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

echo "<font class='head'>", t("ALV-laskelma KMD INF"), "</font><hr />";

echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='laskelma' />";
echo "<table>";
echo "<tr>";
echo "<th>", t("Valitse"), "</th>";

$sel = $laskelma == "b" ? "selected" : "";

echo "<td>";
echo "<select name='laskelma'>";
echo "<option value='a'>", t("Laskelma A"), " (", t("myynti"), ")</option>";
echo "<option value='b' {$sel}>", t("Laskelma B"), " (", t("osto"), ")</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<th>", t("Valitse kausi"), "</th>";
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
  echo "<option value='{$i}' {$sel}>", str_pad($i, 2, floor($i / 10), STR_PAD_LEFT), "</option>";
}
echo "</select>";

echo "</td>";
echo "</tr>";

$sel = $_rajaa_chk ? "selected" : "";

echo "<tr>";
echo "<th>", t("Rajaa"), "</th>";
echo "<td>";
echo "<select name='rajaa'>";
echo "<option value=''>", t("N�yt� kaikki"), "</option>";
echo "<option value='1000' {$sel}>", t("Vain 1000 EUR"), "</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

$chk = !empty($per_paiva) ? "checked" : "";

echo "<tr>";
echo "<th>", t("Aja laskelma per p�iv�"), "</th>";
echo "<td><input type='checkbox' name='per_paiva' value='1' {$chk} /></td>";
echo "</tr>";

$chk = !empty($tee_excel) ? "checked" : "";

echo "<tr>";
echo "<th>", t("Tee Excel"), "<br /></th>";
echo "<td><input type='checkbox' name='tee_excel' {$chk} /></td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='2' class='back'><input type='submit' value='", t("N�yt�"), "' /></td>";
echo "</tr>";

echo "</table>";
echo "</form>";

if ($tee == 'laskelma') {

  $_csv = array(
    'header' => array(
      'taxPayerRegCode' => $yhtiorow['ytunnus'],
      'submitterPersonCode' => $kukarow['kuka'],
      'year' => $vv,
      'month' => str_pad($kk, 2, floor($kk / 10), STR_PAD_LEFT),
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

    echo "<a href='{$_url}&per_paiva=", ($per_paiva-1), "'>", t("Edellinen p�iv�"), "</a> ";
    echo t("ALV-laskelma KMD INF"), " ", t("p�iv�lt�"), " {$per_paiva}.{$kk}.{$vv} ";
    echo "<a href='{$_url}&per_paiva=", ($per_paiva+1), "'>", t("Seuraava p�iv�"), "</a>";

    $alkupvm     = date("Y-m-d", mktime(0, 0, 0, $kk, $per_paiva, $vv));
    $loppupvm    = date("Y-m-d", mktime(0, 0, 0, $kk, $per_paiva, $vv));
  }
  else {
    $alkupvm = date("Y-m-d", mktime(0, 0, 0, $kk,   1, $vv));
    $loppupvm = date("Y-m-d", mktime(0, 0, 0, $kk+1, 0, $vv));
  }

  // ee100 = myynti
  // ee500 = osto
  // myyntilasku tila U
  // ostolasku tilat HYMPQ
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
    $laskun_nimi_lisa_select = "trim(concat(ll.laskutus_nimi, ' ', ll.laskutus_nimitark)) nimi, lasku.nimi AS nimicsv,";
  }
  else {
    $taso = 'ee500';
    $eetasolisa = "or alv_taso like '%ee510%' or alv_taso like '%ee520%'";
    $tilat = "and lasku.tila IN ('H','Y','M','P','Q','X')";
    $tilaustyyppi = "";
    $laskun_lisatiedot_lisa = "";
    $verolisa = "";
    $laskun_nimi_lisa_select = "lasku.nimi, ";
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

    $query = "SELECT lasku.ytunnus
              FROM lasku
              JOIN tiliointi ON (
                tiliointi.yhtio    = lasku.yhtio AND
                tiliointi.ltunnus  = lasku.tunnus AND
                tiliointi.korjattu = '' AND
                tiliointi.tapvm    >= '{$alkupvm}' AND
                tiliointi.tapvm    <= '{$loppupvm}' AND
                tiliointi.tilino   in ({$tilirow['tilitMUU']})
                {$verolisa}
              )
              WHERE lasku.yhtio    = '{$kukarow['yhtio']}'
              {$tilat}
              {$tilaustyyppi}
              GROUP BY 1
              HAVING abs(sum(if(
                (tiliointi.summa + (tiliointi.summa * vero / 100)) > 0,
                (tiliointi.summa + (tiliointi.summa * vero / 100)),
                0
              ))) < {$rajaa}
              AND abs(sum(if(
                (tiliointi.summa + (tiliointi.summa * vero / 100)) < 0,
                (tiliointi.summa + (tiliointi.summa * vero / 100)),
                0
              ))) < {$rajaa}";
    $result = pupe_query($query);

    $_exclude_asiakkaat = array();

    while ($row = mysql_fetch_assoc($result)) {
      $_exclude_asiakkaat[$row['ytunnus']] = $row['ytunnus'];
    }

    if (!empty($_exclude_asiakkaat)) {
      $rajaalisa = "and lasku.ytunnus NOT IN ('".implode("','", $_exclude_asiakkaat)."')";
    }
  }

  $query = "SELECT tiliointi.ltunnus,
            max(tiliointi.vero) veropros,
            sum(round(tiliointi.summa * vero / 100, 2)) veronmaara,
            sum(tiliointi.summa) summa
            FROM tiliointi
            WHERE tiliointi.yhtio  = '{$kukarow['yhtio']}'
            AND tiliointi.korjattu = ''
            AND tiliointi.tapvm    >= '{$alkupvm}'
            AND tiliointi.tapvm    <= '{$loppupvm}'
            AND tiliointi.tilino   in ({$tilirow['tilitMUU']})
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
  echo "<th>", t("CSV"), "</th>";
  echo "<th>#</th>";
  echo "<th>", t("ytunnus"), "</th>";
  echo "<th>", t("nimi"), "</th>";
  echo "<th>", t("laskunro"), "</th>";
  echo "<th>", t("pvm"), "</th>";
  echo "<th>", t("laskun summa"), "</th>";
  echo "<th>", t("alv"), "</th>";
  echo "<th>", t("verot"), "</th>";
  echo "<th>", t("erikoiskoodi"), "</th>";
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
              AND lasku.tunnus  = '{$row['ltunnus']}'
              {$rajaalisa}";
    $laskures = pupe_query($query);
    $laskurow = mysql_fetch_assoc($laskures);

    if (!empty($laskurow['tunnus']) and $laskelma == 'a') {
      $query = "SELECT round(sum(rivihinta), {$yhtiorow['hintapyoristys']}) rivihinta_summa
                FROM tilausrivi
                WHERE yhtio      = '{$kukarow['yhtio']}'
                AND uusiotunnus  = '{$laskurow['tunnus']}'
                AND var          NOT IN ('P','J','O','S')
                AND tyyppi      != 'D'";
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

    if (!fmod($row['veropros'], 1)) $row['veropros'] = round($row['veropros']);

    $aineistoon = $_green;

    if ($laskelma == 'a') {

      $query = "SELECT tunnus
                FROM asiakas
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$laskurow['liitostunnus']}'
                AND laji    = 'H'";
      $asiakasres = pupe_query($query);

      if (mysql_num_rows($asiakasres) != 0) $aineistoon = $_red;
    }

    if (mysql_num_rows($laskures) == 0 or $laskurow['laskunro'] == 0) $aineistoon = $_red;

    $erikoiskoodi = ($laskelma == 'a' and $row['veropros'] == 0) ? '03' : '';

    $_sum_a = 10000 * abs($laskurow['laskun_summa']);
    $_sum_b = 10000 * abs($_vero);

    if ($laskelma == 'a' and !empty($laskurow['laskun_summa']) and $_sum_a != $_sum_b) {
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
    echo "<td>", pupe_DataTablesEchoSort($laskurow['tapvm']).tv1dateconv($laskurow['tapvm']), "</td>";
    echo "<td>$laskurow[laskun_summa]</td>";
    echo "<td>$row[veropros]</td>";
    echo "<td><a href='{$palvelin2}muutosite.php?tee=E&tunnus=$row[ltunnus]&lopetus={$lopetus}'>";

    if ($laskelma == 'a') {
      if ($laskurow['laskun_summa'] < 0 and $_vero > 0) {
        $_vero = $_vero * -1;
        echo $_vero;
      }
      else {
        $_vero = abs($_vero);
        echo $_vero;
      }
    }
    else {
      echo abs($_vero);
    }

    echo "</a></td>";
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

        $worksheet->write($excelrivi, $excelsarake, date('j.m.Y', strtotime($laskurow['tapvm'])));
        $excelsarake++;

        $worksheet->write($excelrivi, $excelsarake, $laskurow['laskun_summa']);
        $excelsarake++;

        $worksheet->write($excelrivi, $excelsarake, $row['veropros']);
        $excelsarake++;

        $worksheet->write($excelrivi, $excelsarake, $_vero);
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
          'buyerName' => $laskurow['nimicsv'],
          'invoiceNumber' => $laskurow['laskunro'],
          'invoiceDate' => date('j.m.Y', strtotime($laskurow['tapvm'])),
          'invoiceSum' => $laskurow['laskun_summa'],
          'taxRate' => $row['veropros'],
          'invoiceSumForRate' => '',
          'sumForRateInPeriod' => $_vero,
          'comments' => $erikoiskoodi,
        );
      }
      else {
        $_csv['B'][] = array(
          'sellerRegCode' => $laskurow['ytunnus'],
          'sellerName' => $laskurow['nimi'],
          'invoiceNumber' => $laskurow['laskunro'],
          'invoiceDate' => date('j.m.Y', strtotime($laskurow['tapvm'])),
          'invoiceSumVat' => $laskurow['laskun_summa'],
          'vatSum' => $_vero,
          'vatInPeriod' => $_vero,
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
  echo t("Yhteens�"), " (", t("ilman ALV"), ")";
  echo "<span style='float: right;'>", round(abs($verot_yht), 2), "</span>";
  echo "</th></tr>";

  if ($_rajaa_chk) {
    echo "<tr><th colspan='10'>";
    echo t("Yhteens�"), " CSV (", t("ilman ALV"), ")";
    echo "<span style='float: right;'>", round(abs($verot_csv_yht), 2), "</span>";
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
