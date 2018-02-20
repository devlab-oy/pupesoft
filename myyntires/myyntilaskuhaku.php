<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// DataTables p‰‰lle
$pupe_DataTables = "myyntilaskuhaku";

require "../inc/parametrit.inc";

if (!isset($tee))    $tee = '';
if (!isset($summa1)) $summa1 = '';
if (!isset($summa2)) $summa2 = '';
if (!isset($pvm))    $pvm = '';

if (!function_exists("kuka_kayttaja")) {
  function kuka_kayttaja($keta_haetaan) {
    global $kukarow, $yhtiorow;

    $query = "SELECT kuka.nimi
              FROM kuka
              WHERE kuka.yhtio = '{$kukarow['yhtio']}'
              AND kuka.kuka ='$keta_haetaan'";
    $kukares = pupe_query($query);
    $row = mysql_fetch_assoc($kukares);

    if ($row["nimi"] !="") {
      return $row["nimi"];
    }
    else {
      return $keta_haetaan;
    }
  }
}

echo "<font class='head'>", t("Myyntilaskuhaku"), "</font><hr>";

$index = "";

$lopelisa = "${palvelin2}myyntires/myyntilaskuhaku.php////tee=$tee//laskuntyyppi=$laskuntyyppi//summa1=$summa1//summa2=$summa2//alkuvv=$alkuvv//alkukk=$alkukk//alkupp=$alkupp//loppuvv=$loppuvv//loppukk=$loppukk//loppupp=$loppupp//pvm=$pvm";
if (isset($lopetus) and $lopetus != "") $lopelisa = "$lopetus/SPLIT/$lopelisa";

echo "<form name = 'valinta' method='post'>";

echo "<table>";

$sel = array_fill_keys(array($pvm_rajaustyyppi), " selected") + array_fill_keys(array('tappvm', 'luopvm', 'laspvm'), '');

echo "<tr><th valign='top'>".t("Alkupvm")."</th>";
echo "<td><select name='alkuvv'>";

$sel = array();
if (!isset($alkuvv) or $alkuvv == "") $alkuvv = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
$sel[$alkuvv] = "SELECTED";

for ($i = date("Y"); $i >= date("Y")-10; $i--) {
  if (!isset($sel[$i])) $sel[$i] = "";
  echo "<option value='{$i}' {$sel[$i]}>{$i}</option>";
}

echo "</select>";

$sel = array();
if (!isset($alkukk) or $alkukk == "") $alkukk = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
$sel[$alkukk] = "SELECTED";

echo "<select name='alkukk'>";

for ($i = 1; $i < 13; $i++) {
  $val = $i < 10 ? '0'.$i : $i;

  if (!isset($sel[$val])) $sel[$val] = "";

  echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
}

echo "</select>";

$sel = array();
if (!isset($alkupp) or $alkupp == "") $alkupp = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
$sel[$alkupp] = "SELECTED";

echo "<select name='alkupp'>";

for ($i = 1; $i < 32; $i++) {
  $val = $i < 10 ? '0'.$i : $i;

  if (!isset($sel[$val])) $sel[$val] = "";

  echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
}

echo "</select></td><td class='back'>&nbsp;</td></tr>";

echo "<tr>
  <th valign='top'>", t("Loppupvm"), "</th>
  <td><select name='loppuvv'>";

$sel = array();
if (!isset($loppuvv) or $loppuvv == "") $loppuvv = date("y", mktime(0, 0, 0, (date("m")+6), 0, date("Y")));
$sel[$loppuvv] = "SELECTED";

for ($i = date("Y")+1; $i >= date("Y")-10; $i--) {

  if (!isset($sel[$i])) $sel[$i] = "";

  echo "<option value='{$i}' {$sel[$i]}>{$i}</option>";
}

echo "</select>";

$sel = array();
if (!isset($loppukk) or $loppukk == "") $loppukk = date("m", mktime(0, 0, 0, (date("m")+6), 0, date("Y")));
$sel[$loppukk] = "SELECTED";

echo "<select name='loppukk'>";

for ($i = 1; $i < 13; $i++) {
  $val = $i < 10 ? '0'.$i : $i;

  if (!isset($sel[$val])) $sel[$val] = "";

  echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
}

echo "</select>";

$sel = array();
if (!isset($loppupp) or $loppupp == "") $loppupp = date("d", mktime(0, 0, 0, (date("m")+6), 0, date("Y")));
$sel[$loppupp] = "SELECTED";

echo "<select name='loppupp'>";

for ($i = 1; $i < 32; $i++) {
  $val = $i < 10 ? '0'.$i : $i;

  if (!isset($sel[$val])) $sel[$val] = "";

  echo "<option value='{$val}' {$sel[$val]}>{$val}</option>";
}

echo "</select></td><td class='back'>&nbsp;</td></tr>";

if (!empty($serikustpt) or !empty($serikohdet) or !empty($seriprojektit)) {
  $mul_kustp = unserialize(urldecode($serikustpt));
  $mul_kohde = unserialize(urldecode($serikohdet));
  $mul_projekti = unserialize(urldecode($seriprojektit));
}

$monivalintalaatikot = array();

$query = "SELECT tunnus
          FROM kustannuspaikka
          WHERE yhtio   = '{$kukarow["yhtio"]}'
          AND tyyppi    = 'K'
          AND kaytossa != 'E'
          LIMIT 1";
$result = pupe_query($query);

if (mysql_num_rows($result) > 0) {
  $monivalintalaatikot[] = "KUSTP";
}

$query = "SELECT tunnus
          FROM kustannuspaikka
          WHERE yhtio   = '{$kukarow["yhtio"]}'
          AND tyyppi    = 'O'
          AND kaytossa != 'E'
          LIMIT 1";
$result = pupe_query($query);

if (mysql_num_rows($result) > 0) {
  $monivalintalaatikot[] = "KOHDE";
}

$query = "SELECT tunnus
          FROM kustannuspaikka
          WHERE yhtio   = '{$kukarow["yhtio"]}'
          AND tyyppi    = 'P'
          AND kaytossa != 'E'
          LIMIT 1";
$result = pupe_query($query);

if (mysql_num_rows($result) > 0) {
  $monivalintalaatikot[] = "PROJEKTI";
}

if (count($monivalintalaatikot) > 0) {
  echo "<tr>";
  echo "<th>".t("Tarkenne")."</th>";
  echo "<td>";

  $noautosubmit = TRUE;
  require "tilauskasittely/monivalintalaatikot.inc";

  echo "</td>";
  echo "</tr>";
}

$sel = array_fill_keys(array($laskuntyyppi), " selected") + array_fill_keys(array('H', 'Y', 'M', 'P', 'Q', 'K'), '');

echo "<tr><th>".t("Laskun tila")."</th>";
echo "<td><select name = 'laskuntyyppi'>
    <option value = ''>".t("Kaikki")."</option>
    <option {$sel['A']} value = 'A'>".t("Avoin")."</option>
    <option {$sel['M']} value = 'M'>".t("Maksettu")."</option>
    </select></td>
    </tr>";

$sel = array_fill_keys(array($tee), " selected") + array_fill_keys(array('S', 'VS', 'N', 'V', 'L', 'A', 'LN', 'M'), '');

echo "<tr>";
echo "<th>".t("Hakulaji")."</th>";
echo "<td><select name = 'tee'>";

echo "<option value = 'S'  {$sel["S"]}>",   t("Summalla"), "</option>";
echo "<option value = 'VS' {$sel["VS"]}>",  t("Valuuttasummalla"), "</option>";
echo "<option value = 'N'  {$sel["N"]}>",   t("Nimell‰"), "</option>";
echo "<option value = 'V'  {$sel["V"]}>",   t("Viitteell‰"), "</option>";
echo "<option value = 'L'  {$sel["L"]}>",   t("Laskunnumerolla"), "</option>";
echo "<option value = 'K'  {$sel["K"]}>",   t("Kohteella"), "</option>";
echo "<option value = 'A'  {$sel["A"]}>",   t("Asiakasnumerolla"), "</option>";
echo "<option value = 'AT'  {$sel["AT"]}>", t("Asiakkaan tilausnumerolla"), "</option>";
echo "<option value = 'LN' {$sel["LN"]}>",  t("Laatijan/myyj‰n nimell‰"), "</option>";

// M = laskuja er‰p‰iv‰n:n mukaan
if ($tee == 'M') {
  echo "<option value = 'M' {$sel['M']}>".t("Er‰p‰iv‰n mukaan")."</option>";
}

echo "</select></td></tr>";
echo "<tr><th>".t("Haku")."</th><td><input type = 'text' name = 'summa1' value = '$summa1' size='13'> - <input type = 'text' name = 'summa2' value = '$summa2' size='13'></td>";

$sel = array_fill_keys(array($rajaus), " selected") + array_fill_keys(array('50', '100', '250', '500', '750', '1000'), '');

echo "<tr><th>".t("N‰ytet‰‰n")."</th>";
echo "<td><select name = 'rajaus'>";
echo "<option {$sel['50']} value = '50'>".t("50")."</option>";
echo "<option {$sel['100']} value = '100'>".t("100")."</option>";
echo "<option {$sel['250']} value = '250'>".t("250")."</option>";
echo "<option {$sel['500']} value = '500'>".t("500")."</option>";
echo "<option {$sel['750']} value = '750'>".t("750")."</option>";
echo "<option {$sel['1000']} value = '1000'>".t("1000")."</option>";
echo "</select> ".t("laskua")."</td>";

if ($tee == 'M') {
  echo "<input type = 'hidden' name = 'pvm' value = '$pvm'>";
}

echo "<td class='back'><input type = 'submit' class='hae_btn' value = '".t("Etsi")."'></td>";
echo "</tr>";
echo "</table>";
echo "</form>";
echo "<hr>";

$formi = 'valinta';
$kentta = 'summa1';

if (trim($summa1) == "" and empty($pvm)) {
  $tee = "";
}

// LN = Etsit‰‰n myyj‰n tai laatijan nimell‰
if ($tee == 'LN') {
  // haetaan vain aktiivisia k‰ytt‰ji‰
  $query = "SELECT
            group_concat(concat('\'',kuka.kuka,'\'')) kuka,
            group_concat(concat(if(kuka.myyja=0, null, kuka.myyja))) myyja
            FROM kuka
            WHERE kuka.yhtio    = '{$kukarow['yhtio']}'
            AND kuka.aktiivinen = 1
            AND (kuka.kuka like '%$summa1%' or kuka.nimi like '%$summa1%')";
  $kukares = pupe_query($query);

  $row = mysql_fetch_assoc($kukares);

  if ($row["myyja"] != "") {
    $myyja = " or myyja in ({$row["myyja"]})";
  }

  // Jos ei lˆytynyt k‰ytt‰jist‰ niin kokeillaan hakusanalla
  if ($row["kuka"] == "") {
    $row["kuka"] = "'".$summa1."'";
  }

  $ehto = "tila = 'U' and (laatija in ({$row["kuka"]}) $myyja)";
  $jarj = "nimi, tapvm desc";
}

// VS = Etsit‰‰n valuuttasummaa laskulta
if ($tee == 'VS') {

  if (strlen($summa2) == 0) {
    $summa2 = $summa1;
  }

  $summa1 = (float) str_replace( ",", ".", $summa1);
  $summa2 = (float) str_replace( ",", ".", $summa2);

  $ehto = "tila = 'U' and ";
  $index = " use index (yhtio_tila_summavaluutassa) ";

  if ($summa1 == $summa2) {
    $ehto .= "summa_valuutassa in ({$summa1}, ".($summa1*-1).") ";
    $jarj = "tapvm desc";
  }
  else {
    $ehto .= "summa_valuutassa >= " . $summa1 . " and summa_valuutassa <= " . $summa2;
    $jarj = "summa_valuutassa, tapvm";
  }
}

// AT = Etsit‰‰n asiakkaan tilausnumerolla
if ($tee == 'AT') {
  $summa1 = mysql_real_escape_string($summa1);

  $ehto .= "tila = 'U' and asiakkaan_tilausnumero LIKE '%{$summa1}%'";
  $index = " use index (yhtio_asiakkaan_tilausnumero) ";
  $jarj = "asiakkaan_tilausnumero, tapvm";
}

// S = Etsit‰‰n summaa laskulta
if ($tee == 'S') {

  if (strlen($summa2) == 0) {
    $summa2 = $summa1;
  }

  $summa1 = (float) str_replace(",", ".", $summa1);
  $summa2 = (float) str_replace(",", ".", $summa2);

  $ehto = "tila = 'U' and ";
  $index = " use index (yhtio_tila_summa) ";

  if ($summa1 == $summa2) {
    $ehto .= "summa in ({$summa1}, ".($summa1*-1).") ";
    $jarj = "tapvm desc";
  }
  else {
    $ehto .= "summa >= {$summa1} and summa <= {$summa2}";
    $jarj = "summa, tapvm";
  }
}

// N = Etsit‰‰n nime‰ laskulta
if ($tee == 'N') {
  $index = " use index (asiakasnimi) ";
  $ehto = "tila = 'U' and nimi like '%".$summa1."%'";
  $jarj = "nimi, tapvm desc";
}

// A = Etsit‰‰n asiakannumeroa laskulta
if ($tee == 'A') {
  $query  = "SELECT group_concat(tunnus) asiakkaat
             FROM asiakas
             WHERE yhtio    = '{$kukarow['yhtio']}'
             and asiakasnro = '$summa1'
             and asiakasnro not in ('0','')";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  $liitostunnus = -1;

  if ($row["asiakkaat"] != "") {
    $liitostunnus = $row["asiakkaat"];
  }

  $index = "";
  $ehto = "tila = 'U' and liitostunnus in ($liitostunnus)";
  $jarj = "nimi, tapvm desc";
}

// V = viitteell‰
if ($tee == 'V') {
  $index = " use index (tila_viite) ";
  $ehto = "tila = 'U' and viite = '{$summa1}'";
  $jarj = "nimi, summa";
}

// L = laskunumerolla
if ($tee == 'L') {
  $index = " use index (yhtio_tila_laskunro) ";
  $ehto = "tila = 'U' and (laskunro = '".abs($summa1)."' or laskunro = '-".abs($summa1)."')";
  $jarj = "nimi, summa";
}

// K = kohteella
if ($tee == 'K') {
  $ehto = "tila = 'U' and kohde = '{$summa1}'";
  $jarj = "nimi, summa";
}

// M = er‰p‰iv‰m‰‰r‰ll‰
if ($tee == 'M') {
  $ehto = "tila = 'U' and lasku.erpcm = '$pvm'";
  $jarj = "nimi, summa";

  unset($alkuvv);
  unset($loppuvv);
}

// P‰iv‰m‰‰r‰rajaus
if (is_numeric($alkuvv) and is_numeric($alkukk) and is_numeric($alkupp) and is_numeric($loppuvv) and is_numeric($loppukk) and is_numeric($loppupp)) {
  $ehto .= "\nand lasku.tapvm >= '{$alkuvv}-{$alkukk}-{$alkupp}'\nand lasku.tapvm <= '{$loppuvv}-{$loppukk}-{$loppupp}'";

  if ($index == "") $index = " use index (yhtio_tila_tapvm) ";
}

// Maksetut tai avoimet laskut
if (!empty($laskuntyyppi) and $laskuntyyppi == "A") {
  $ehto .= " AND lasku.mapvm = 0 ";
}
elseif (!empty($laskuntyyppi) and $laskuntyyppi == "M") {
  $ehto .= " AND lasku.mapvm > 0 ";
}

if (empty($rajaus) or !is_numeric($rajaus)) {
  $rajaus = "LIMIT 50";
}
else {
  $rajaus = "LIMIT ".round($rajaus);
}

if (!empty($tee)) {
  $query = "SELECT tapvm, erpcm, laskunro, concat_ws(' ', nimi, nimitark) nimi,
            summa, valkoodi, ebid, tila, alatila, tunnus,
            mapvm, saldo_maksettu, ytunnus, liitostunnus, laatija
            FROM lasku {$index}
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND {$ehto}
            ORDER BY {$jarj}
            {$rajaus}";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<b>", t("Haulla ei lˆytynyt yht‰‰n laskua"), "</b>";
    $tee = '';
  }
  else {

    pupe_DataTables(array(array($pupe_DataTables, 9, 9)));

    echo "<table class='display dataTable' id='{$pupe_DataTables}'>";

    echo "<thead>
        <tr>
        <th>", t("Pvm"), "</th>
        <th>", t("Er‰p‰iv‰"), "</th>
        <th>", t("Laskunro"), "</th>
        <th>", t("Nimi"), "</th>
        <th>", t("Summa"), "</th>
        <th>", t("Valuutta"), "</th>
        <th>", t("Ebid"), "</th>
        <th>", t("Tila"), "</th>
        <th>", t("Laatija"), "</th>
        </tr>
        <tr>
        <td><input type='text' class='search_field' name='search_pvm'></td>
        <td><input type='text' class='search_field' name='search_erpvm'></td>
        <td><input type='text' class='search_field' name='search_laskunro'></td>
        <td><input type='text' class='search_field' name='search_nimi'></td>
        <td><input type='text' class='search_field' name='search_summa'></td>
        <td><input type='text' class='search_field' name='search_valuutta'></td>
        <td><input type='text' class='search_field' name='search_ebid'></td>
        <td><input type='text' class='search_field' name='search_tila'></td>
        <td><input type='text' class='search_field' name='search_laatija'></td>
        </tr>
      </thead>";

    echo "<tbody>";

    while ($trow = mysql_fetch_assoc($result)) {
      echo "<tr class='aktiivi'>";

      if ($kukarow['taso'] < 2) {
        echo "<td valign='top'>".pupe_DataTablesEchoSort($trow['tapvm']).tv1dateconv($trow["tapvm"])."</td>";
      }
      else {
        echo "<td valign='top'>".pupe_DataTablesEchoSort($trow['tapvm'])."<a href = '../muutosite.php?tee=E&tunnus={$trow[tunnus]}&lopetus={$lopelisa}'>".tv1dateconv($trow["tapvm"])."</a></td>";
      }
      echo "<td valign='top'>".pupe_DataTablesEchoSort($trow['erpcm']).tv1dateconv($trow["erpcm"])."</td>";

      echo "<td valign='top'>";
      echo pupe_DataTablesEchoSort($trow['laskunro']);
      echo "<a href='../tilauskasittely/tulostakopio.php?toim=LASKU&tee=ETSILASKU&laskunro={$trow['laskunro']}&lopetus={$lopelisa}'>{$trow['laskunro']}</a>";
      echo "</td>";

      echo "<td valign='top'>";
      echo "<a name='$trow[tunnus]' href='".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php?ytunnus=$trow[ytunnus]&asiakasid=$trow[liitostunnus]&alatila=Y&tila=tee_raportti&lopetus={$lopelisa}'>{$trow['nimi']}</a>";
      echo "</td>";

      echo "<td valign='top' align='right'>{$trow['summa']}</td>";
      echo "<td valign='top'>{$trow['valkoodi']}</td>";

      // tehd‰‰n lasku linkki
      echo "<td>", ebid($trow['tunnus']), "</td>";

      $maksuviesti = "";

      if ($trow['mapvm'] != "0000-00-00") {
        $maksuviesti = t("Maksettu");
      }
      elseif ($trow['mapvm'] == "0000-00-00" and $trow['saldo_maksettu'] != 0) {
        $maksuviesti = t("Osasuoritettu");

        if ($trow['mapvm'] == "0000-00-00" and str_replace("-", "", $trow['erpcm']) < date("Ymd")) {
          $maksuviesti .= " / ".t("Er‰‰ntynyt");
        }
      }
      elseif ($trow['mapvm'] == "0000-00-00" and str_replace("-", "", $trow['erpcm']) < date("Ymd")) {
        $maksuviesti = " ".t("Er‰‰ntynyt");
      }
      else {
        $maksuviesti = t("Avoin");
      }

      echo "<td>$maksuviesti</td>";
      echo "<td>".kuka_kayttaja($trow["laatija"])."</td>";
      echo "</tr>";
    }

    echo "</tbody>";
    echo "</table><br /><br />";

    $toim = "";
  }
}

require "inc/footer.inc";
