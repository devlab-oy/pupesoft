<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "../inc/parametrit.inc";

echo "<font class='head'>".t("Tiliöinnit lajeittain")."</font><hr>";

// käyttöliittymä
if (!isset($pp)) $pp = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($kk)) $kk = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vv)) $vv = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
$pp = sprintf("%02d", trim($pp));
$kk = sprintf("%02d", trim($kk));
$vv = sprintf("%04d", trim($vv));

if (!isset($lpp)) $lpp = date("d");
if (!isset($lkk)) $lkk = date("m");
if (!isset($lvv)) $lvv = date("Y");
$lpp = sprintf("%02d", trim($lpp));
$lkk = sprintf("%02d", trim($lkk));
$lvv = sprintf("%04d", trim($lvv));

if (!checkdate($kk, $pp, $vv) or !checkdate($lkk, $lpp, $lvv)) {
  echo "<font class='error'>".t("Virheellinen päivämäärä")."!</font><br><br>";
  $tee = "";
}

echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='raportti'>";

echo "<table>";

$check = array();
$check[$laji] = "SELECTED";

echo "<tr>";
echo "<th>".t("Valitse tapahtumatyyppi")."</th>";
echo "<td>
    <select name='laji'>
    <option value='myynti' $check[myynti]>".t("Myyntilaskut")."</option>
    <option value='osto' $check[osto]>".t("Ostolaskut")."</option>
    <option value='tosite' $check[tosite]>".t("Tositteet")."</option>
    <option value='kaikki' $check[kaikki]>".t("Kaikki")."</option>
    </td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Syötä alkupäivä")."</th>";
echo "<td><input type='text' name='pp' size='5' value='$pp'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='vv' size='7' value='$vv'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>".t("Syötä loppupäivä")."</th>";
echo "<td><input type='text' name='lpp' size='5' value='$lpp'><input type='text' name='lkk' size='5' value='$lkk'><input type='text' name='lvv' size='7' value='$lvv'></td>";
echo "</tr>";

if ($tehdaspalautukset != '') {
  $chk = "CHECKED";
}
else {
  $chk = "";
}

echo "<th>".t("Näytä vain tehdaspalautukset")."</th>";
echo "<td><input type = 'checkbox' name='tehdaspalautukset' $chk></td></tr>";

echo "</table>";

echo "<br><input type='submit' value='".t("Hae tiliöinnit")."'>";

echo "</form>";
echo "<br><br>";

// itse raportti
if ($tee == "raportti") {

  // yhteeveto
  $laskurajaus = "";

  if ($laji == "myynti") {
    $laskurajaus = "AND lasku.tila = 'U'";
    echo "<font class='head'>".t("Myyntilaskut")."</font><hr>";
  }

  if ($laji == "osto") {
    $laskurajaus = "AND lasku.tila in ('H','Y','M','P','Q')";
    echo "<font class='head'>".t("Ostolaskut")."</font><hr>";
  }

  if ($laji == "tosite") {
    $laskurajaus = "AND lasku.tila = 'X'";
    echo "<font class='head'>".t("Tositteet")."</font><hr>";
  }

  if ($laji == "kaikki") {
    $laskurajaus = "AND lasku.tila in ('H','Y','M','P','Q','U','X')";
    echo "<font class='head'>".t("Myyntilaskut")." + ".t("Ostolaskut")." + ".t("Tositteet")."</font><hr>";

  }

  $tehdaspalautuksetrajaus = "";

  if ($tehdaspalautukset != '') {
    $tehdaspalautuksetrajaus = "and lasku.tilaustyyppi = '9'";
  }

  $query = "SELECT tiliointi.tilino, tili.nimi, sum(tiliointi.summa) summa, sum(tiliointi.summa*(tiliointi.vero/100)) veronmaara
            FROM tiliointi USE INDEX (yhtio_tapvm_tilino)
            JOIN lasku ON (lasku.yhtio = tiliointi.yhtio AND lasku.tunnus = tiliointi.ltunnus $laskurajaus $tehdaspalautuksetrajaus)
            LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio AND tili.tilino = tiliointi.tilino)
            WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
            AND tiliointi.tapvm    >= '$vv-$kk-$pp'
            AND tiliointi.tapvm    <= '$lvv-$lkk-$lpp'
            AND tiliointi.korjattu = ''
            GROUP BY tilino, nimi
            ORDER BY tilino, nimi";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Tilinumero")."</th>";
    echo "<th>".t("Nimitys")."</th>";
    echo "<th>".t("Summa")."</th>";
    echo "<th>".t("Vero")."</th>";
    echo "</tr>";

    $summa     = 0;
    $verosumma = 0;
    $alvsumma  = array();

    while ($row = mysql_fetch_assoc($result)) {
      echo "<tr class='aktiivi'>";
      echo "<td>$row[tilino]</td>";
      echo "<td>$row[nimi]</td>";
      echo "<td align='right'>". number_format($row["summa"], 2, ',', ' ')."</td>";
      echo "<td align='right'>". number_format($row["veronmaara"], 2, ',', ' ')."</td>";
      echo "</tr>";

      $summa += $row["summa"];
      $verosumma += $row["veronmaara"];
    }

    echo "<tr>";
    echo "<th colspan='2'>".t("Yhteensä")."</th>";
    echo "<th style='text-align:right;'>". sprintf("%.02f", $summa)."</td>";
    echo "<th style='text-align:right;'>". sprintf("%.02f", $verosumma)."</td>";
    echo "</tr>";
    echo "</table>";
  }

  if ($laji == "myynti") {
    $query = "SELECT lasku.tunnus, lasku.laskunro, lasku.nimi, lasku.tapvm, lasku.arvo summa, '$yhtiorow[valkoodi]' valkoodi, lasku.vienti,
              if (lasku.toim_maa!='', lasku.toim_maa, if(lasku.maa != '', lasku.maa, '$yhtiorow[maa]')) maa
              FROM lasku
              WHERE lasku.yhtio = '$kukarow[yhtio]'
              AND lasku.tapvm   >= '$vv-$kk-$pp'
              AND lasku.tapvm   <= '$lvv-$lkk-$lpp'
              AND lasku.tila    = 'U'
              $tehdaspalautuksetrajaus
              ORDER BY lasku.tapvm, lasku.tunnus";
  }

  if ($laji == "osto") {
    $query = "SELECT lasku.tunnus, concat_ws('<br>', lasku.viesti, lasku.viite) laskunro, lasku.nimi, lasku.tapvm, lasku.summa, lasku.valkoodi, lasku.vienti
              FROM lasku
              WHERE lasku.yhtio = '$kukarow[yhtio]'
              AND lasku.tapvm   >= '$vv-$kk-$pp'
              AND lasku.tapvm   <= '$lvv-$lkk-$lpp'
              AND lasku.tila    in ('H','Y','M','P','Q')
              ORDER BY lasku.tapvm, lasku.tunnus";
  }

  if ($laji == "tosite") {
    $query = "SELECT lasku.tunnus, group_concat(tiliointi.tilino SEPARATOR '<br>') laskunro, group_concat(tiliointi.selite SEPARATOR '<br>') nimi, group_concat(tiliointi.tapvm SEPARATOR '<br>') tapvm, sum(tiliointi.summa) summa, group_concat(tiliointi.summa SEPARATOR '<br>') valkoodi, lasku.vienti
              FROM lasku
              JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio AND tiliointi.ltunnus = lasku.tunnus and tiliointi.korjattu = '')
              WHERE lasku.yhtio = '$kukarow[yhtio]'
              AND lasku.tapvm   >= '$vv-$kk-$pp'
              AND lasku.tapvm   <= '$lvv-$lkk-$lpp'
              AND lasku.tila    = 'X'
              GROUP BY lasku.tunnus
              ORDER BY lasku.tapvm, lasku.tunnus";
    $result = pupe_query($query);
  }

  if ($laji == "myynti" or $laji == "osto" or $laji == "tosite") {

    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      echo "<br><table>";
      echo "<tr>";
      echo "<th>#</th>";
      echo "<th>".t("Nimi")."</th>";
      echo "<th>".t("Tapvm")."</th>";
      echo "<th>".t("Summa")."</th>";
      echo "<th>".t("Valuutta")."</th>";
      echo "<th>".t("Vienti")."</th>";

      $query = "SELECT cast(selite AS DECIMAL(4,2)) selite
                FROM avainsana
                WHERE yhtio = '$kukarow[yhtio]'
                AND laji    = 'ALV'
                ORDER by jarjestys, selite";
      $alv_result = pupe_query($query);

      while ($alv_row = mysql_fetch_assoc($alv_result)) {
        echo "<th>".t("Alv")." ".(float) $alv_row["selite"]."%</th>";
      }

      echo "</tr>";

      $summa = 0;

      while ($row = mysql_fetch_assoc($result)) {

        if (in_array($row["vienti"], array("", "A", "B", "C", "J"))) {
          $row["vienti"] = t("Kotimaa");
        }
        elseif (in_array($row["vienti"], array("D", "E", "F", "K"))) {
          $row["vienti"] = t("EU");
        }
        elseif (in_array($row["vienti"], array("G", "H", "I", "L"))) {
          $row["vienti"] = t("EI EU");
        }

        echo "<tr class='aktiivi'>";
        echo "<td nowrap valign='top'>$row[laskunro]</td>";
        echo "<td valign='top'>$row[nimi]</td>";
        echo "<td nowrap valign='top'>$row[tapvm]</td>";
        echo "<td nowrap valign='top' align='right'>".number_format($row["summa"], 2, ',', ' ')."</td>";
        echo "<td nowrap valign='top' align='right'>$row[valkoodi]</td>";
        echo "<td nowrap valign='top'>$row[vienti]</td>";

        // alvikannat alkuun
        mysql_data_seek($alv_result, 0);

        if ($laji == "myynti") {

          $query = "SELECT ";

          while ($alv_row = mysql_fetch_assoc($alv_result)) {
            $query .= " sum(if(tilausrivi.alv=$alv_row[selite], rivihinta*(tilausrivi.alv/100), 0)) 'alv$alv_row[selite]', ";
          }

          //  Tehdään lista vielä alv erottelusta
          $query .= "  'dummy'
                FROM lasku
                JOIN tilausrivi ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.tyyppi!='D'
                WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = '$row[tunnus]'";
          $alvres = pupe_query($query);
          $alvrow = mysql_fetch_assoc($alvres);
        }
        else {

          $query = "SELECT ";

          while ($alv_row = mysql_fetch_assoc($alv_result)) {
            $query .= " sum(if(t1.vero=$alv_row[selite], t2.summa, 0)) 'alv$alv_row[selite]', ";
          }

          // Haetaan  kaikki verotiliöinnit
          $query .= "  'dummy'
                FROM tiliointi t1
                JOIN tiliointi t2 ON t1.yhtio=t2.yhtio and t1.ltunnus=t2.ltunnus and t1.tunnus=t2.aputunnus and t2.korjattu = ''
                where t1.yhtio  = '$kukarow[yhtio]'
                and t1.ltunnus  = '$row[tunnus]'
                and t1.korjattu = ''
                and t1.vero    != 0";
          $alvres = pupe_query($query);
          $alvrow = mysql_fetch_assoc($alvres);
        }

        // alvikannat alkuun
        mysql_data_seek($alv_result, 0);

        $alvit_logistiikka = 0;

        while ($alv_row = mysql_fetch_assoc($alv_result)) {
          $verokanta = $alv_row["selite"];
          echo "<td nowrap valign='top' align='right'>".number_format($alvrow["alv$verokanta"], 2, ',', ' ')."</td>";
          $alvsumma[$verokanta] += $alvrow["alv$verokanta"];

          $alvit_logistiikka += $alvrow["alv$verokanta"];
        }

        if ($laji == "myynti") {
          if ($alvit_logistiikka != 0 and strtoupper($row['maa']) != strtoupper($yhtiorow['maa'])) {
            echo "<td nowrap valign='top' class='back'><font class='error'>VIRHE: Verollinen lasku toimitettu ulkomaille!</font></td>";
          }

          echo "<td nowrap valign='top' class='back'><a href='../tilauskasittely/tulostakopio.php?otunnus=$row[tunnus]&toim=LASKU&tee=NAYTATILAUS'>".t("Näytä lasku")."</a></td>";
        }
        else {
          echo "<td nowrap valign='top' class='back'>".ebid($row['tunnus'])."</td>";
        }

        echo "</tr>";
        $summa += $row["summa"];
      }

      echo "<tr>";
      echo "<th colspan='3'>".t("Yhteensä")."</th>";
      echo "<th style='text-align:right;' nowrap>". number_format($summa, 2, ',', ' ')."</th>";
      echo "<th></th>";
      echo "<th></th>";

      // alvikannat alkuun
      mysql_data_seek($alv_result, 0);

      while ($alv_row = mysql_fetch_assoc($alv_result)) {
        echo "<th style='text-align:right;' nowrap>".number_format($alvsumma[$alv_row["selite"]], 2, ',', ' ')."</th>";
      }

      echo "</tr>";
      echo "</table>";
    }
  }
}

require "inc/footer.inc";
