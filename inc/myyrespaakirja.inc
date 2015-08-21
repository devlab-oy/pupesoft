<?php

echo "<font class='head'>".t("Suorituspäiväkirja")."</font><hr>";

if (!isset($alvk)) $alvk = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($alvp)) $alvp = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($alvv)) $alvv = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

$ksel = array();
$psel = array();
$vsel = array();

$ksel[$alvk] = "SELECTED";
$psel[$alvp] = "SELECTED";
$vsel[$alvv] = "SELECTED";
$tsel[$laji] = "SELECTED";

echo "  <form name = 'valinta' action = 'raportit.php' method='post'>
    <input type = 'hidden' name = 'toim' value = 'myyrespaakirja'>
    <input type = 'hidden' name = 'tee' value = '1'>
    <table>
    <tr>
    <th>".t("Laji")."</th>
    <td>
    <select name='laji'>
    <option value = 'MY'>".t("Myynti")."</option>
    <option value = 'OS' $tsel[OS]>".t("Osto")."</option>
    </select>
    </td>
    </tr>
    <tr>
    <th>".t("Ajalta")."</th>
    <td><select name='alvv'>
    ";

for ($i = date("Y")+2; $i >= date("Y")-4; $i--) {
  echo "<option value='$i' $vsel[$i]>$i</option>";
}

echo "
  </select>
  <select name='alvk'>
  <option value = '00'>".t("koko vuosi")."
  <option value = '01' $ksel[01]>01</option>
  <option value = '02' $ksel[02]>02</option>
  <option value = '03' $ksel[03]>03</option>
  <option value = '04' $ksel[04]>04</option>
  <option value = '05' $ksel[05]>05</option>
  <option value = '06' $ksel[06]>06</option>
  <option value = '07' $ksel[07]>07</option>
  <option value = '08' $ksel[08]>08</option>
  <option value = '09' $ksel[09]>09</option>
  <option value = '10' $ksel[10]>10</option>
  <option value = '11' $ksel[11]>11</option>
  <option value = '12' $ksel[12]>12</option>
  </select>
  <select name='alvp'>
  <option value = '00'>".t("koko kuukausi")."
  <option value = '01' $psel[01]>01</option>
  <option value = '02' $psel[02]>02</option>
  <option value = '03' $psel[03]>03</option>
  <option value = '04' $psel[04]>04</option>
  <option value = '05' $psel[05]>05</option>
  <option value = '06' $psel[06]>06</option>
  <option value = '07' $psel[07]>07</option>
  <option value = '08' $psel[08]>08</option>
  <option value = '09' $psel[09]>09</option>
  <option value = '10' $psel[10]>10</option>
  <option value = '11' $psel[11]>11</option>
  <option value = '12' $psel[12]>12</option>
  <option value = '13' $psel[13]>13</option>
  <option value = '14' $psel[14]>14</option>
  <option value = '15' $psel[15]>15</option>
  <option value = '16' $psel[16]>16</option>
  <option value = '17' $psel[17]>17</option>
  <option value = '18' $psel[18]>18</option>
  <option value = '19' $psel[19]>19</option>
  <option value = '20' $psel[20]>20</option>
  <option value = '21' $psel[21]>21</option>
  <option value = '22' $psel[22]>22</option>
  <option value = '23' $psel[23]>23</option>
  <option value = '24' $psel[24]>24</option>
  <option value = '25' $psel[25]>25</option>
  <option value = '26' $psel[26]>26</option>
  <option value = '27' $psel[27]>27</option>
  <option value = '28' $psel[28]>28</option>
  <option value = '29' $psel[29]>29</option>
  <option value = '30' $psel[30]>30</option>
  <option value = '31' $psel[31]>31</option>
  </select></td>
  </tr>
  <tr>";

// haetaan kaikki yrityksen rahatilit mysql muodossa
$query  = "SELECT oletus_rahatili, nimi, iban
           FROM yriti
           WHERE yhtio          = '$kukarow[yhtio]'
           AND kaytossa         = ''
           AND oletus_rahatili != ''";
$ratire = pupe_query($query);

echo "<th>".t("Pankkitili")."</th>";
echo "<td>";
echo "<select name='rahatili'>";
echo "<option value=''>".t("Kaikki")."</option>";

$psel = array();
$psel[$rahatili] = "SELECTED";

while ($ratiro = mysql_fetch_array($ratire)) {
  $rahatilisel = $ratiro["oletus_rahatili"];
  echo "<option value='$ratiro[oletus_rahatili]' $psel[$rahatilisel]>$ratiro[nimi] ($ratiro[iban])</option>";
}

echo "
  </td>
      <td class='back'><input type = 'submit' value = '".t("Näytä")."'></td></tr></table></form><br><br>";

if ($tee == "1") {

  if ($rahatili != "") {
    $rahatili_lisa = " and oletus_rahatili = '$rahatili' ";
  }
  else {
    $rahatili_lisa = "";
  }
  // haetaan kaikki yrityksen rahatilit mysql muodossa
  $query  = "SELECT concat(group_concat(distinct concat('\'',oletus_rahatili) SEPARATOR '\', '),'\'') rahatilit
             FROM yriti
             WHERE yhtio          = '$kukarow[yhtio]'
             and kaytossa         = ''
             $rahatili_lisa
             and oletus_rahatili != ''";
  $ratire = pupe_query($query);
  $ratiro = mysql_fetch_array($ratire);

  if ($ratiro["rahatilit"] != "") {

    $lisa = "";
    $jarj = "tiliointi.tapvm, tiliointi.summa";

    if ($alvk == 0) {
      echo "<font class='head'>".t("Suorituspäiväkirja vuodelta")." $alvv</font><hr>";
      $lisa = "LEFT(tiliointi.tapvm,4) = '$alvv'";
    }
    else {
      if ($alvp == 0) {
        echo "<font class='head'>".t("Suorituspäiväkirja kaudelta")." $alvv-$alvk</font><hr>";
        $lisa = "LEFT(tiliointi.tapvm,7) = '$alvv-$alvk'";
      }
      else {
        echo "<font class='head'>".t("Suorituspäiväkirja päivältä")." $alvv-$alvk-$alvp</font><hr>";
        $lisa = "tiliointi.tapvm = '$alvv-$alvk-$alvp'";
      }
    }

    $summa = 0;

    if ($laji == "MY") {
      $laskulisa = " and lasku.tila = 'U' and lasku.alatila = 'X' ";
      $etumerkki = "";
    }
    else {
      $laskulisa = " and lasku.tila = 'Y' ";
      $etumerkki = " * -1";
    }

    $query = "SELECT
              min(lasku.nimi) nimi,
              min(lasku.laskunro) laskunro,
              group_concat(tiliointi.tapvm ORDER BY tiliointi.tunnus) tapvm,
              group_concat(tiliointi.summa $etumerkki ORDER BY tiliointi.tunnus) summa,
              sum(tiliointi.summa $etumerkki) sum_summa,
              min(tiliointi.ltunnus) ltunnus,
              min(tiliointi.tapvm) o_pvm,
              max(tiliointi.summa) o_sum,
              max(lasku.summa) laskusumma,
              max(lasku.valkoodi) laskuvalkoodi
              FROM tiliointi
              JOIN lasku ON tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus $laskulisa
              WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
              and tiliointi.tilino   IN ($ratiro[rahatilit])
              and tiliointi.korjattu = ''
              and  $lisa
              GROUP BY lasku.tunnus
              ORDER BY o_pvm, o_sum";
    $result = pupe_query($query);

    pupe_DataTables(array(array($pupe_DataTables, 7, 7)));

    echo "<table class='display dataTable' id='$pupe_DataTables'>";

    echo "<thead>";
    echo "<tr>
        <th>".t("Nimi")."</th>
        <th>".t("Laskunro")."</th>
        <th>".t("Laskusumma")."</th>
        <th>".t("Valuutta")."</th>
        <th>".t("Pvm")."</th>
        <th>".t("Suoritus")." $yhtiorow[valkoodi]</th>
        <th>".t("Yhteensä")." $yhtiorow[valkoodi]</th>
        </tr>
        <tr>
        <td><input type='text' class='search_field' name='search_nimi'></td>
        <td><input type='text' class='search_field' name='search_laskunro'></td>
        <td><input type='text' class='search_field' name='search_summa'></td>
        <td><input type='text' class='search_field' name='search_valuutta'></td>
        <td><input type='text' class='search_field' name='search_pvm'></td>
        <td><input type='text' class='search_field' name='search_summa'></td>
        <td><input type='text' class='search_field' name='search_yhteensa'></td>
      </tr>";
    echo "</thead>";

    echo "<tbody>";

    while ($trow = mysql_fetch_assoc($result)) {

      echo "<tr class='aktiivi'>";
      echo "<td><a name='A_$trow[ltunnus]' href = 'muutosite.php?tee=E&tunnus=$trow[ltunnus]&lopetus=$PHP_SELF////toim=$toim//tee=$tee//alvv=$alvv//alvk=$alvk//alvp=$alvp//order=$order///A_$trow[ltunnus]'>$trow[nimi]</a></td>";
      echo "<td>$trow[laskunro]</td>";
      echo "<td align='right'>$trow[laskusumma]</td>";
      echo "<td>$trow[laskuvalkoodi]</td>";
      echo "<td>";

      foreach (explode(",", $trow["tapvm"]) as $a) {
        echo $a."<br>";
      }
      echo "</td>";

      echo "<td align='right'>";

      foreach (explode(",", $trow["summa"]) as $a) {
        echo $a."<br>";
      }

      echo "</td>";

      echo "<td align='right'>$trow[sum_summa]</td>";

      echo "</tr>";

      $summa+=$trow['sum_summa'];
    }

    echo "</tbody>";

    echo "<tfoot>";
    echo "<tr>
      <td class='tumma' colspan='6'>".t("Yhteensä")."</td>
      <td class='tumma' name='yhteensa' id='yhteensa' align='right'>".sprintf("%.2f", $summa), "</td>
      </tr>";
    echo "</tfoot>";
    echo "</table><br>";
  }
  else {
    echo "Sopivia tilejä ei löytynyt!";
  }
}
