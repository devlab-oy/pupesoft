<?php
echo "<font class='head'>".t("Laskut per tila")."</font><hr>";

if (!isset($erittely)) {
  $query = "SELECT tila,
            alatila,
            round(sum(lasku.summa * if(lasku.maksu_kurssi = 0, lasku.vienti_kurssi, lasku.maksu_kurssi)), 2) summa,
            count(*) kpl,
            min(erpcm) erpcm,
            max(erpcm) maxerpcm
            FROM lasku use index (tila_index)
            LEFT JOIN valuu ON valuu.yhtio = lasku.yhtio and lasku.valkoodi  = valuu.nimi
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and lasku.tila    in ('H','M','P','Q')
            GROUP BY tila, alatila";
  $result = mysql_query($query) or pupe_error($query);

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Tila")."</th>";
  echo "<th>".t("Summa")." $yhtiorow[valkoodi]</th>";
  echo "<th>".t("Kpl")."</th>";
  echo "<th>".t("Min")." ".t("er‰pvm")."</th>";
  echo "<th>".t("Max")." ".t("er‰pvm")."</th>";
  echo "</tr>";

  while ($trow=mysql_fetch_array($result)) {
    $laskutyyppi = $trow['tila'];
    $alatila     = $trow['alatila'];
    require "inc/laskutyyppi.inc";

    echo "<tr class='aktiivi'>";
    echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";
    echo "<td style='text-align: right;'><a href='raportit.php?toim=laskutila&erittely=$trow[tila]&alatila=$trow[alatila]'>$trow[summa]</a></td>";
    echo "<td style='text-align: right;'>$trow[kpl]</td>";
    echo "<td>".tv1dateconv($trow["erpcm"])."</td>";
    echo "<td>".tv1dateconv($trow["maxerpcm"])."</td>";
    echo "</tr>";
  }

  echo "</table><br>";
}

if (isset($erittely)) {
  $query = "SELECT lasku.tapvm, lasku.kapvm, lasku.erpcm, if (lasku.comments!='',concat_ws('<br>', lasku.nimi, lasku.comments), lasku.nimi) nimi, lasku.postitp,
            round(lasku.summa * lasku.vienti_kurssi, 2) 'kotisumma', lasku.summa, lasku.valkoodi, lasku.ebid, lasku.tunnus, yriti.nimi tilinimi, yriti.tunnus maksutili, if(lasku.laskunro = 0, '', lasku.laskunro) laskunro
            FROM lasku
            left join yriti on lasku.yhtio=yriti.yhtio and lasku.maksu_tili=yriti.tunnus
            WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = '$erittely' and lasku.alatila = '$alatila'
            ORDER BY lasku.tapvm";
  $result = mysql_query($query) or pupe_error($query);

  echo "<table><tr>";
  echo "<th valign='top'>".t("Pvm")."</th>";
  echo "<th valign='top'>".t("Erpvm")."<br>".t("Kapvm")."</th>";
  echo "<th valign='top'>".t("Nimi")."</th>";
  echo "<th valign='top'>".t("Summa")." $yhtiorow[valkoodi]</th>";
  echo "<th valign='top'>".t("Summa")."</th>";
  echo "<th valign='top'>".t("Laskunro")."</th>";
  echo "<th valign='top'>".t("EBID")."</th>";

  if ($erittely == "Q" or $erittely == "P") {
    echo "<th valign='top'>".t("Maksutili")."</th>";
  }

  echo "</tr>";

  while ($trow = mysql_fetch_array ($result)) {

    echo "<tr class='aktiivi'>";

    if ($kukarow['taso'] != 1 and $kukarow['taso'] != 2 and $kukarow['taso'] != 3) {
      echo "<td valign='top'>".tv1dateconv($trow["tapvm"])."</td>";
    }
    else {
      echo "<td valign='top'><a href = 'muutosite.php?tee=E&tunnus=$trow[tunnus]&lopetus=$PHP_SELF////toim=$toim//erittely=$erittely'>".tv1dateconv($trow["tapvm"])."</a></td>";
    }

    echo "<td valign='top'>".tv1dateconv($trow["erpcm"])."<br>".tv1dateconv($trow["kapvm"])."</td>";
    echo "<td valign='top'>$trow[nimi]<br>$trow[postitp]</td>";
    echo "<td valign='top' style='text-align: right;'>$trow[kotisumma] $yhtiorow[valkoodi]</td>";
    echo "<td valign='top' style='text-align: right;'>$trow[summa] $trow[valkoodi]</td>";
    echo "<td valign='top' style='text-align: right;'>$trow[laskunro]</td>";

    // tehd‰‰n lasku linkki
    echo "<td valign='top'>". ebid($trow['tunnus']) ."</td>";

    if ($erittely == "Q" or $erittely == "P") {
      echo "<td valign='top'>$trow[tilinimi]</td>";
    }
  }
  echo "</table><br>";
}
