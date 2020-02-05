<?php

// Tämä skripti käyttää slave-tietokantapalvelinta
$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "inc/parametrit.inc";

if (isset($tee)) {
  if ($tee == "lataa_tiedosto") {
    echo $file;
    exit;
  }
}

echo "<font class='head'>".t("AKE rekisteridatan käyttö")."</font><hr>";

// kuus kuukautta taaksepäin, kuun eka päivä
$aika1 = date("Y-m-01", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
$aika2 = date("Y-m-01", mktime(0, 0, 0, date("m")-5, date("d"), date("Y")));
$aika3 = date("Y-m-01", mktime(0, 0, 0, date("m")-4, date("d"), date("Y")));
$aika4 = date("Y-m-01", mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
$aika5 = date("Y-m-01", mktime(0, 0, 0, date("m")-2, date("d"), date("Y")));
$aika6 = date("Y-m-01", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
$aika7 = date("Y-m-01", mktime(0, 0, 0, date("m"), date("d"), date("Y")));

$query = "SELECT ake_log.kuka, kuka.nimi,
          sum(if (aika >= '$aika1' and aika < '$aika2', 1, 0)) kpl1,
          sum(if (aika >= '$aika2' and aika < '$aika3', 1, 0)) kpl2,
          sum(if (aika >= '$aika3' and aika < '$aika4', 1, 0)) kpl3,
          sum(if (aika >= '$aika4' and aika < '$aika5', 1, 0)) kpl4,
          sum(if (aika >= '$aika5' and aika < '$aika6', 1, 0)) kpl5,
          sum(if (aika >= '$aika6' and aika < '$aika7', 1, 0)) kpl6,
          sum(if (aika >= '$aika7', 1, 0)) kpl7
          FROM ake_log USE INDEX (yhtio_aika)
          LEFT JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = ake_log.yhtio and kuka.kuka = ake_log.kuka)
          WHERE ake_log.yhtio='$kukarow[yhtio]' and
          aika >= '$aika1'
          GROUP BY kuka, nimi
          ORDER BY nimi";
$res = mysql_query($query) or pupe_error($query);

$html_ulos  = "<table>";
$html_ulos .= "<tr>";
$html_ulos .= "<th>Nimi (käyttäjätunnus)</th>";
$html_ulos .= "<th>".substr($aika1, 0, 7)."</th>";
$html_ulos .= "<th>".substr($aika2, 0, 7)."</th>";
$html_ulos .= "<th>".substr($aika3, 0, 7)."</th>";
$html_ulos .= "<th>".substr($aika4, 0, 7)."</th>";
$html_ulos .= "<th>".substr($aika5, 0, 7)."</th>";
$html_ulos .= "<th>".substr($aika6, 0, 7)."</th>";
$html_ulos .= "<th>".substr($aika7, 0, 7)."</th>";
$html_ulos .= "</tr>";

$file_ulos  = "Nimi (käyttäjätunnus)\t";
$file_ulos .= substr($aika1, 0, 7)."\t";
$file_ulos .= substr($aika2, 0, 7)."\t";
$file_ulos .= substr($aika3, 0, 7)."\t";
$file_ulos .= substr($aika4, 0, 7)."\t";
$file_ulos .= substr($aika5, 0, 7)."\t";
$file_ulos .= substr($aika6, 0, 7)."\t";
$file_ulos .= substr($aika7, 0, 7)."\n";

$yht_kpl1 = 0;
$yht_kpl2 = 0;
$yht_kpl3 = 0;
$yht_kpl4 = 0;
$yht_kpl5 = 0;
$yht_kpl6 = 0;
$yht_kpl7 = 0;

while ($row = mysql_fetch_array($res)) {

  $html_ulos .= "<tr>";
  $html_ulos .= "<td>$row[nimi] ($row[kuka])</td>";
  $html_ulos .= "<td>$row[kpl1]</td>";
  $html_ulos .= "<td>$row[kpl2]</td>";
  $html_ulos .= "<td>$row[kpl3]</td>";
  $html_ulos .= "<td>$row[kpl4]</td>";
  $html_ulos .= "<td>$row[kpl5]</td>";
  $html_ulos .= "<td>$row[kpl6]</td>";
  $html_ulos .= "<td>$row[kpl7]</td>";
  $html_ulos .= "</tr>";

  $file_ulos .= "$row[nimi] ($row[kuka])\t";
  $file_ulos .= "$row[kpl1]\t";
  $file_ulos .= "$row[kpl2]\t";
  $file_ulos .= "$row[kpl3]\t";
  $file_ulos .= "$row[kpl4]\t";
  $file_ulos .= "$row[kpl5]\t";
  $file_ulos .= "$row[kpl6]\t";
  $file_ulos .= "$row[kpl7]\n";

  $yht_kpl1 += $row["kpl1"];
  $yht_kpl2 += $row["kpl2"];
  $yht_kpl3 += $row["kpl3"];
  $yht_kpl4 += $row["kpl4"];
  $yht_kpl5 += $row["kpl5"];
  $yht_kpl6 += $row["kpl6"];
  $yht_kpl7 += $row["kpl7"];

}

$html_ulos .= "<tr>";
$html_ulos .= "<th>Yhteensä</th>";
$html_ulos .= "<th>$yht_kpl1</th>";
$html_ulos .= "<th>$yht_kpl2</th>";
$html_ulos .= "<th>$yht_kpl3</th>";
$html_ulos .= "<th>$yht_kpl4</th>";
$html_ulos .= "<th>$yht_kpl5</th>";
$html_ulos .= "<th>$yht_kpl6</th>";
$html_ulos .= "<th>$yht_kpl7</th>";
$html_ulos .= "</tr>";

$html_ulos .= "</table>";

$file_ulos .= "Yhteensä\t";
$file_ulos .= "$yht_kpl1\t";
$file_ulos .= "$yht_kpl2\t";
$file_ulos .= "$yht_kpl3\t";
$file_ulos .= "$yht_kpl4\t";
$file_ulos .= "$yht_kpl5\t";
$file_ulos .= "$yht_kpl6\t";
$file_ulos .= "$yht_kpl7\n";

echo "<form method='post' class='multisubmit'>";

echo "<table>";
echo "<tr><th>".t("Tallenna tulos")."</th>";
echo "<td>";
echo "<input type='radio' name='kaunisnimi' value='ake.xls' checked> Excel-muodossa<br>";
echo "<input type='radio' name='kaunisnimi' value='ake.csv'> OpenOffice-muodossa<br>";
echo "<input type='radio' name='kaunisnimi' value='ake.txt'> Tekstitiedostona";
echo "</td>";
echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
echo "<input type='hidden' name='file' value='$file_ulos'>";
echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr>";
echo "</table>";
echo "</form>";

echo "<br>$html_ulos";

require "inc/footer.inc";
