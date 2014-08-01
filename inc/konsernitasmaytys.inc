<?php

echo "<font class='head'>".t("Konsernitäsmäytys")."</font><hr>";

if ($yhtiorow["konserni"] == "") {
  echo "<font class = 'error'>".t("Yritys ei ole osa konsernia")."</font>";
}
else {
  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='aja'>";

  echo "<table>";
  echo "<th valign='top'>".t("Alkukausi")."</th>";
  echo "<td><select name='plvv'>";

  $sel = array();
  $sel[$plvv] = "SELECTED";

  for ($i = date("Y"); $i >= date("Y")-4; $i--) {
    echo "<option value='$i' $sel[$i]>$i</option>";
  }

  echo "</select>";

  $sel = array();
  $sel[$plvk] = "SELECTED";

  echo "<select name='plvk'>
      <option $sel[01] value = '01'>01</option>
      <option $sel[02] value = '02'>02</option>
      <option $sel[03] value = '03'>03</option>
      <option $sel[04] value = '04'>04</option>
      <option $sel[05] value = '05'>05</option>
      <option $sel[06] value = '06'>06</option>
      <option $sel[07] value = '07'>07</option>
      <option $sel[08] value = '08'>08</option>
      <option $sel[09] value = '09'>09</option>
      <option $sel[10] value = '10'>10</option>
      <option $sel[11] value = '11'>11</option>
      <option $sel[12] value = '12'>12</option>
      </select>";

  $sel = array();
  $sel[$plvp] = "SELECTED";

  echo "<select name='plvp'>
      <option $sel[01] value = '01'>01</option>
      <option $sel[02] value = '02'>02</option>
      <option $sel[03] value = '03'>03</option>
      <option $sel[04] value = '04'>04</option>
      <option $sel[05] value = '05'>05</option>
      <option $sel[06] value = '06'>06</option>
      <option $sel[07] value = '07'>07</option>
      <option $sel[08] value = '08'>08</option>
      <option $sel[09] value = '09'>09</option>
      <option $sel[10] value = '10'>10</option>
      <option $sel[11] value = '11'>11</option>
      <option $sel[12] value = '12'>12</option>
      <option $sel[13] value = '13'>13</option>
      <option $sel[14] value = '14'>14</option>
      <option $sel[15] value = '15'>15</option>
      <option $sel[16] value = '16'>16</option>
      <option $sel[17] value = '17'>17</option>
      <option $sel[18] value = '18'>18</option>
      <option $sel[19] value = '19'>19</option>
      <option $sel[20] value = '20'>20</option>
      <option $sel[21] value = '21'>21</option>
      <option $sel[22] value = '22'>22</option>
      <option $sel[23] value = '23'>23</option>
      <option $sel[24] value = '24'>24</option>
      <option $sel[25] value = '25'>25</option>
      <option $sel[26] value = '26'>26</option>
      <option $sel[27] value = '27'>27</option>
      <option $sel[28] value = '28'>28</option>
      <option $sel[29] value = '29'>29</option>
      <option $sel[30] value = '30'>30</option>
      <option $sel[31] value = '31'>31</option>
      </select>
      </td></tr>";

  echo "<tr>
    <th valign='top'>".t("Loppukausi")."</th>
    <td><select name='alvv'>";

  $sel = array();
  if ($alvv == "") $alvv = date("Y");
  $sel[$alvv] = "SELECTED";

  for ($i = date("Y")+1; $i >= date("Y")-4; $i--) {
    echo "<option value='$i' $sel[$i]>$i</option>";
  }

  $sel = array();
  if ($alvk == "") $alvk = date("m");
  $sel[$alvk] = "SELECTED";

  echo "</select>";

  echo "<select name='alvk'>
      <option $sel[01] value = '01'>01</option>
      <option $sel[02] value = '02'>02</option>
      <option $sel[03] value = '03'>03</option>
      <option $sel[04] value = '04'>04</option>
      <option $sel[05] value = '05'>05</option>
      <option $sel[06] value = '06'>06</option>
      <option $sel[07] value = '07'>07</option>
      <option $sel[08] value = '08'>08</option>
      <option $sel[09] value = '09'>09</option>
      <option $sel[10] value = '10'>10</option>
      <option $sel[11] value = '11'>11</option>
      <option $sel[12] value = '12'>12</option>
      </select>";

  $sel = array();
  if ($alvp == "") $alvp = date("d", mktime(0, 0, 0, (date("m")+1), 0, date("Y")));
  $sel[$alvp] = "SELECTED";

  echo "<select name='alvp'>
      <option $sel[01] value = '01'>01</option>
      <option $sel[02] value = '02'>02</option>
      <option $sel[03] value = '03'>03</option>
      <option $sel[04] value = '04'>04</option>
      <option $sel[05] value = '05'>05</option>
      <option $sel[06] value = '06'>06</option>
      <option $sel[07] value = '07'>07</option>
      <option $sel[08] value = '08'>08</option>
      <option $sel[09] value = '09'>09</option>
      <option $sel[10] value = '10'>10</option>
      <option $sel[11] value = '11'>11</option>
      <option $sel[12] value = '12'>12</option>
      <option $sel[13] value = '13'>13</option>
      <option $sel[14] value = '14'>14</option>
      <option $sel[15] value = '15'>15</option>
      <option $sel[16] value = '16'>16</option>
      <option $sel[17] value = '17'>17</option>
      <option $sel[18] value = '18'>18</option>
      <option $sel[19] value = '19'>19</option>
      <option $sel[20] value = '20'>20</option>
      <option $sel[21] value = '21'>21</option>
      <option $sel[22] value = '22'>22</option>
      <option $sel[23] value = '23'>23</option>
      <option $sel[24] value = '24'>24</option>
      <option $sel[25] value = '25'>25</option>
      <option $sel[26] value = '26'>26</option>
      <option $sel[27] value = '27'>27</option>
      <option $sel[28] value = '28'>28</option>
      <option $sel[29] value = '29'>29</option>
      <option $sel[30] value = '30'>30</option>
      <option $sel[31] value = '31'>31</option>
      </select>
      </td></tr>";
  echo "</table>";

  echo "<br>";
  echo "<input type = 'submit' value = '".t("Aja raportti")."'>";
  echo "</form>";
}

// oikeellisuustarkistuksia
if ($tee != "") {
  if (!checkdate($plvk, $plvp, $plvv)) {
    echo "<font class='error'>".t("VIRHE: Alkupäivämäärä on virheellinen")."!</font><br>";
    $tee = "";
  }

  if (!checkdate($alvk, $alvp, $alvv)) {
    echo "<font class='error'>".t("VIRHE: Loppupäivämäärä on virheellinen")."!</font><br>";
    $tee = "";
  }
}

if ($tee != "") {

  $query = "SELECT *
            FROM yhtio
            WHERE konserni = '$yhtiorow[konserni]'
            AND trim(konserni) != ''";
  $konserniresult = pupe_query($query);

  if (mysql_num_rows($konserniresult) > 0) {

    echo "<table>";

    while ($konsernirow = mysql_fetch_assoc($konserniresult)) {

      echo "<tr><td class='back' colspan='5'><br><font class='message'>$konsernirow[nimi] ($konsernirow[ytunnus])</font></td></tr>";

      $myyntisaamiset_yhteensa = 0;
      $ostovelat_yhteensa = 0;
      $erotus_yhteensa = 0;

      $query = "SELECT lasku.ytunnus, group_concat(distinct lasku.nimi) nimi, sum(tiliointi.summa) myyntisaamiset
                FROM tiliointi
                JOIN lasku ON (lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus)
                WHERE tiliointi.yhtio  = '$konsernirow[yhtio]'
                AND tiliointi.tilino   = '$konsernirow[konsernimyyntisaamiset]'
                AND tiliointi.tapvm    >= '$plvv-$plvk-$plvp'
                AND tiliointi.tapvm    <= '$alvv-$alvk-$alvp'
                AND tiliointi.korjattu = ''
                GROUP BY lasku.ytunnus";
      $result = pupe_query($query);

      echo "<tr>";
      echo "<th>".t("Ytunnus")."</th>";
      echo "<th>".t("Nimi")."</th>";
      echo "<th>".t("Myyntisaamiset")."</th>";
      echo "<th>".t("Ostovelat")."</th>";
      echo "<th>".t("Erotus")."</th>";
      echo "</tr>";

      while ($trow = mysql_fetch_assoc($result)) {

        $query = "SELECT *
                  FROM yhtio
                  WHERE ytunnus = '$trow[ytunnus]'";
        $riviyhtioresult = pupe_query($query);

        if (mysql_num_rows($riviyhtioresult) == 1) {

          $riviyhtiorow = mysql_fetch_assoc($riviyhtioresult);

          $query = "SELECT sum(tiliointi.summa) ostovelat
                    FROM tiliointi
                    JOIN lasku ON (lasku.yhtio = tiliointi.yhtio AND lasku.tunnus = tiliointi.ltunnus AND lasku.ytunnus = '$konsernirow[ytunnus]')
                    WHERE tiliointi.yhtio  = '$riviyhtiorow[yhtio]'
                    AND tiliointi.tilino   = '$riviyhtiorow[konserniostovelat]'
                    AND tiliointi.tapvm    >= '$plvv-$plvk-$plvp'
                    AND tiliointi.tapvm    <= '$alvv-$alvk-$alvp'
                    AND tiliointi.korjattu = ''";
          $ostovelatresult = pupe_query($query);
          $ostovelatrow = mysql_fetch_assoc($ostovelatresult);
        }
        else {
          $ostovelatrow['ostovelat'] = "N/A";
        }

        if ($trow["myyntisaamiset"] != 0 or $ostovelatrow["ostovelat"] != 0) {

          $erotus = $trow['myyntisaamiset'] + (float) $ostovelatrow['ostovelat'];
          $myyntisaamiset_yhteensa += $trow["myyntisaamiset"];
          $ostovelat_yhteensa += $trow["ostovelat"];
          $erotus_yhteensa += $erotus;

          echo "<tr>";
          echo "<td>$trow[ytunnus]</td>";
          echo "<td>$trow[nimi]</td>";
          echo "<td align='right'>$trow[myyntisaamiset]</td>";
          echo "<td align='right'>$ostovelatrow[ostovelat]</td>";
          echo "<td align='right'>".sprintf("%.02f", $erotus)."</td>";
          echo "</tr>";
        }
      }

      echo "<tr>";
      echo "<th colspan='2'>Yhteensä</th>";
      echo "<th>".sprintf("%.02f", $myyntisaamiset_yhteensa)."</th>";
      echo "<th>".sprintf("%.02f", $ostovelat_yhteensa)."</th>";
      echo "<th>".sprintf("%.02f", $erotus_yhteensa)."</th>";
      echo "</tr>";

    }

    echo "</table><br>";
  }
  else {
    echo "<font class = 'error'>".t("Konserniyrityksiä ei löydy!")."</font>";
  }
}
