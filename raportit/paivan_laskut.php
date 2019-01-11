<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "../inc/parametrit.inc";

echo "<font class='head'>".t("Päivän laskut")."</font><hr>";

if (!isset($naytalaskut)) {
  $naytalaskut = 0;
}
if (!isset($kk)) {
  $kk = date("m");
}
if (!isset($vv)) {
  $vv = date("Y");
}
if (!isset($pp)) {
  $pp = date("d");
}

// Käyttöliittymä
echo "<br>";
echo "<form method='post'>
      <input type='hidden' name='teerappari' value='1'>";

echo "<table>";
echo "<tr><th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
    <td><input type='text' name='pp' value='$pp' size='3'></td>
    <td><input type='text' name='kk' value='$kk' size='3'></td>
    <td><input type='text' name='vv' value='$vv' size='5'></td>";

$chk2 = "";
if ($naytalaskut == 1) $chk2 = "CHECKED";

echo "<tr><th>".t("Näytä laskut")."</th>
    <td colspan='3'><input type='checkbox' name='naytalaskut' value='1' $chk2></td>";
echo "</tr></table>";

echo "<br><input type='submit' value='".t("Aja raportti")."'>";
echo "</form><br><br>";

if (isset($teerappari)) {

  if ($naytalaskut == 1) {
    echo "<table>";
    echo "<tr>";
    echo "<th>", t("Laskunro"), "</th>";
    echo "<th>", t("Asiakas"), "</th>";
    echo "<th>", t("Summa"), "</th>";
    echo "<th>", t("Kanava"), "</th>";
    echo "<th>", t("Operaattori"), "</th>";
    echo "</tr>";
  }

  $query  = "SELECT lasku.*
             FROM lasku
             WHERE lasku.yhtio    = '{$kukarow['yhtio']}'
             AND lasku.tila       = 'U'
             AND lasku.alatila    = 'X'
             AND lasku.laskutettu >= '$vv-$kk-$pp 00:00:00'
             AND lasku.laskutettu <= '$vv-$kk-$pp 23:59:59'
             ORDER BY lasku.laskunro";
  $lasres = pupe_query($query);

  $operaattorille = 0;
  $oikmuutosite   = tarkista_oikeus("muutosite.php");

  while ($lasrow = mysql_fetch_assoc($lasres)) {

    $query  = "SELECT *
               FROM maksuehto
               WHERE maksuehto.yhtio = '{$kukarow['yhtio']}'
               AND maksuehto.tunnus  = '{$lasrow['maksuehto']}'";
    $masres = pupe_query($query);
    $masrow = mysql_fetch_assoc($masres);


    if ($naytalaskut == 1) {
      echo "<tr>";
      echo "<td>";

      if ($oikmuutosite) {
        echo "<a  name='$lasrow[tunnus]' href = '{$palvelin2}muutosite.php?tee=E&tunnus=$lasrow[tunnus]&lopetus=$PHP_SELF////teerappari=$teerappari//pp=$pp//kk=$kk//vv=$vv//naytalaskut=$naytalaskut///$lasrow[tunnus]'>$lasrow[laskunro]</a>";
      }
      else {
        echo "$lasrow[laskunro]";
      }

      echo "</td>";
      echo "<td>$lasrow[nimi]</td>";
      echo "<td align='right'>$lasrow[summa]</td>";

      echo "<td>";
      $meneeko = verkkolaskuputkeen($lasrow, $masrow, TRUE);
      echo "</td>";

      echo "<td>";

      if ($meneeko) {
        if ($lasrow["chn"] == "112") {
          echo t("FTP-siirto");
        }
        elseif ($yhtiorow['verkkolasku_lah'] == 'iPost') {
          echo "<font class='ok'>", t("Finvoice iPost"), "</font>";
          $operaattorille++;
        }
        elseif ($yhtiorow['verkkolasku_lah'] == 'apix') {
          echo "<font class='ok'>", t("Apix-verkkolaskut"), "</font>";
          $operaattorille++;
        }
        elseif ($yhtiorow['verkkolasku_lah'] == 'finvoice') {
          echo t("Finvoice");
        }
        elseif ($yhtiorow['verkkolasku_lah'] == 'maventa') {
          echo "<font class='ok'>", t("Maventa-verkkolaskut"), "</font>";
          $operaattorille++;
        }
        elseif ($yhtiorow['verkkolasku_lah'] == 'servinet') {
          echo "<font class='ok'>", t("Pupevoice Servinet"), "</font>";
          $operaattorille++;
        }
        elseif ($yhtiorow['verkkolasku_lah'] == 'trustpoint') {
          echo "<font class='ok'>", t("Trustpoint-verkkolaskut"), "</font>";
          $operaattorille++;
        }
        elseif ($yhtiorow['verkkolasku_lah'] == 'ppg') {
          echo "<font class='ok'>", t("PPG Laskutuspalvelu"), "</font>";
          $operaattorille++;
        }
        elseif ($yhtiorow['verkkolasku_lah'] == 'sepa') {
          echo "<font class='ok'>", t("SEPA-pankkiyhteys"), "</font>";
          $operaattorille++;
        }
        elseif ($yhtiorow['verkkolasku_lah'] == 'talenom') {
          echo "<font class='ok'>", t("Talenom Myyntilaskutuspalvelu"), "</font>";
          $operaattorille++;
        }
        elseif ($yhtiorow['verkkolasku_lah'] == 'arvato') {
          echo "<font class='ok'>", t("Arvato Laskutuspalvelu"), "</font>";
          $operaattorille++;
        }
        elseif ($yhtiorow['verkkolasku_lah'] == 'fitek') {
          echo "<font class='ok'>", t("Fitek-verkkolaskut"), "</font>";
          $operaattorille++;
        } 
        else {
          echo "<font class='ok'>", t("Pupevoice Itella"), "</font>";
          $operaattorille++;
        }
      }
      else {
        echo "-";
      }

      echo "</td></tr>";
    }
    else {
      $meneeko = verkkolaskuputkeen($lasrow, $masrow);

      if ($meneeko and $lasrow["chn"] != "112") {
        $operaattorille++;
      }
    }
  }

  echo "</table><br>";
  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Laskuja yhteensä").":</th><td>".mysql_num_rows($lasres)."</td>";
  echo "</tr><tr>";
  echo "<th>".t("Operaattorille siirrretty").":</th><td>$operaattorille</td>";
  echo "</tr>";
  echo "</table>";
}

require "inc/footer.inc";
