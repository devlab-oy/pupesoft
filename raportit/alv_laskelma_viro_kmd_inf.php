<?php

$pupe_DataTables = 'alv_laskelma_viro_kmd_inf';

require "../inc/parametrit.inc";

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
echo "<td><input type='checkbox' name='per_paiva' {$chk} /></td>";
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

  $oletus_verokanta = 20;

  $alkupvm = date("Y-m-d", mktime(0, 0, 0, $kk,   1, $vv));
  $loppupvm = date("Y-m-d", mktime(0, 0, 0, $kk+1, 0, $vv));

  # ee100 = myynti
  # ee500 = osto
  # myyntilasku tila U
  # ostolasku tilat HYMPQ
  if ($laskelma == 'a') {
    $taso = 'ee100';
    $eetasolisa = "or alv_taso like '%ee110%'";
    $tilat = "and lasku.tila = 'U'";
    $tilausrivijoin = "JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)";
  }
  else {
    $taso = 'ee500';
    $eetasolisa = "or alv_taso like '%ee510%' or alv_taso like '%ee520%'";
    $tilat = "and lasku.tila IN ('H','Y','M','P','Q')";
    $tilausrivijoin = "";
  }

  $query = "SELECT
            group_concat(concat(\"'\",tilino,\"'\")) tilitMUU
            FROM tili
            WHERE yhtio = '$kukarow[yhtio]'
            and (alv_taso like '%$taso%' $eetasolisa)";
  $tilires = pupe_query($query);
  $tilirow = mysql_fetch_assoc($tilires);

  # myyntilasku tila U
  # ostolasku tilat HYMPQ

  $query = "SELECT lasku.tunnus ltunnus, lasku.laskunro laskunro,
            trim(concat(lasku.nimi, ' ', lasku.nimitark)) nimi, lasku.ytunnus,
            lasku.tapvm, lasku.alv,
            sum(lasku.summa) laskun_summa
            #sum(tilausrivi.alv) alvia
            FROM lasku USE INDEX (yhtio_tila_tapvm)
            {$tilausrivijoin}
            #JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuoteno != '{$yhtiorow['ennakkomaksu_tuotenumero']}')
            #JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus AND asiakas.laji != 'H')
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            {$tilat}
            and lasku.tapvm >= '{$alkupvm}'
            and lasku.tapvm <= '{$loppupvm}'
            #and lasku.vienti = 'E'
            and lasku.tilaustyyppi != '9'
            GROUP BY 1,2,3,4,5,6
            #HAVING alvia > 0
            ORDER BY 1,2";
  $result = pupe_query($query);

  $verot_yht = 0;

  pupe_DataTables(array(array($pupe_DataTables, 7, 7, true)));

  echo "<table class='display dataTable' id='{$pupe_DataTables}'>";

  echo "<thead>";

  echo "<tr>";
  echo "<th>ytunnus</th>";
  echo "<th>nimi</th>";
  echo "<th>laskunro</th>";
  echo "<th>pvm</th>";
  echo "<th>laskun summa</th>";
  echo "<th>alv</th>";
  echo "<th>verot</th>";
  echo "</tr>";

  echo "<tr>";
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

  while ($row = mysql_fetch_assoc($result)) {

    $query = "SELECT sum(round(tiliointi.summa * if('veronmaara'='$oletus_verokanta', $oletus_verokanta, vero) / 100, 2)) veronmaara,
              sum(tiliointi.summa) summa
              FROM lasku
              JOIN tiliointi ON (
                tiliointi.yhtio = lasku.yhtio AND
                tiliointi.ltunnus = lasku.tunnus AND
                tiliointi.korjattu = '' AND
                tiliointi.tilino in ({$tilirow['tilitMUU']})
              )
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              {$tilat}
              AND lasku.tunnus = '{$row['ltunnus']}'";
    $verores = pupe_query($query);
    $verorow = mysql_fetch_assoc($verores);

    $_vero = $laskelma == 'a' ? $verorow['summa'] : $verorow['veronmaara'];

    echo "<tr>";
    echo "<td>$row[ytunnus]</td>";
    echo "<td>$row[nimi]</td>";
    echo "<td>$row[laskunro] ($row[ltunnus])</td>";
    echo "<td>",pupe_DataTablesEchoSort($row['tapvm']).tv1dateconv($row['tapvm']),"</td>";
    echo "<td>$row[laskun_summa]</td>";
    echo "<td>$row[alv]</td>";
    echo "<td>$_vero</td>";
    echo "</tr>";

    $verot_yht += $_vero;
  }

  echo "<tfoot>";
  echo "<tr>";
  echo "<th colspan='7'>";
  echo "verot yht";
  echo "<span style='float: right;'>",round($verot_yht, 2),"</span>";
  echo "</th>";
  echo "</tr>";
  echo "</tfoot>";

  echo "</tbody>";

  echo "</table>";

}

require "inc/footer.inc";
