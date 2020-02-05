<?php

require "../inc/parametrit.inc";

echo "<font class='head'>", t("Automanual-lisenssit"), "</font><hr>";

if (!isset($nimi))    $nimi = '';
if (!isset($ytunnus))  $ytunnus = '';
if (!isset($osoite))  $osoite = '';
if (!isset($submit))  $submit = '';
if (!isset($akk))     $akk = '';
if (!isset($avv))     $avv = '';
if (!isset($app))     $app = '';
if (!isset($lkk))     $lkk = '';
if (!isset($lvv))     $lvv = '';
if (!isset($lpp))     $lpp = '';

echo "<table>";
echo "<form method='post'>";
echo "<tr><th>", t("Etsi yrityksen nimell‰"), "</th><td><input type='text' name='nimi' id='nimi' value='$nimi'></td>";
echo "<tr><th>", t("Etsi yrityksen ytunnuksella"), "</th><td><input type='text' name='ytunnus' id='ytunnus' value='$ytunnus'></td>";
echo "<tr><th>Etsi yrityksen osoitteella</th><td><input type='text' name='osoite' id='osoite' value='$osoite'></td>";
echo "<tr><th>Alkup‰iv‰m‰‰r‰ (PP-KK-VVVV)</th><td><input type='text' name='app' id='app' value='$app' size='3' maxlength='2'>-<input type='text' name='akk' id='akk' value='$akk' size='3' maxlength='2'>-<input type='text' name='avv' id='avv' value='$avv' size='5' maxlength='4'></td></tr>";
echo "<tr><th>Loppup‰iv‰m‰‰r‰ (PP-KK-VVVV)</th><td><input type='text' name='lpp' id='lpp' value='$lpp' size='3' maxlength='2'>-<input type='text' name='lkk' id='lkk' value='$lkk' size='3' maxlength='2'>-<input type='text' name='lvv' id='lvv' value='$lvv' size='5' maxlength='4'></td>";
echo "<td class='back'><input type='submit' name='submit' id='submit' value='", t("Etsi"), "'></td></tr>";
echo "</form>";
echo "</table>";

if ($submit != '') {

  $wherelisa = '';

  if ($nimi != '') {
    $nimi     = mysql_real_escape_string($nimi);
    $wherelisa .= " AND asiakas.nimi like '%$nimi%' ";
  }

  if ($ytunnus != '') {
    $ytunnus   = mysql_real_escape_string($ytunnus);
    $wherelisa .= " AND asiakas.ytunnus = '$ytunnus' ";
  }

  if ($osoite != '') {
    $osoite   = mysql_real_escape_string($osoite);
    $wherelisa .= " AND asiakas.osoite like '%$osoite%' ";
  }

  // alkup‰iv‰m‰‰r‰
  if ($app != '' and $akk != '' and $avv != '') {
    $app = str_pad((int) $app, 2, "0", STR_PAD_LEFT);
    $akk = str_pad((int) $akk, 2, "0", STR_PAD_LEFT);
    $avv = (int) $avv;

    // errorcheckit...
    $val = checkdate((int) $akk, $app, $avv);

    if (!$val) {
      echo "<font class='error'>Alkup‰iv‰m‰‰r‰ oli virheellinen!</font>";
      exit;
    }

    $wherelisa .= " AND e2.selite >= '$avv-$akk-$app' ";
  }

  // loppup‰iv‰m‰‰r‰
  if ($lpp != '' and $lkk != '' and $lvv != '') {
    $lpp = str_pad((int) $lpp, 2, "0", STR_PAD_LEFT);
    $lkk = str_pad((int) $lkk, 2, "0", STR_PAD_LEFT);
    $lvv = (int) $lvv;

    // errorcheckit...
    $val = checkdate($lkk, $lpp, $lvv);

    if (!$val) {
      echo "<font class='error'>Loppup‰iv‰m‰‰r‰ oli virheellinen!</font>";
      exit;
    }

    $wherelisa .= " AND e2.selite <= '$lvv-$lkk-$lpp' ";
  }

  $query = "SELECT asiakas.*,
            group_concat(distinct concat(kuka.nimi, ' (', kuka.kuka, ')') order by kuka.nimi, kuka.kuka SEPARATOR '<br>') kuka,
            group_concat(distinct e1.selite order by e1.selite SEPARATOR '<br>') autodatatunnus,
            group_concat(distinct e2.selite order by e2.selite SEPARATOR '<br>') autodatalisenssi,
            group_concat(distinct e3.selite order by e3.selite SEPARATOR '<br>') autodatathinclient,
            group_concat(distinct e4.selite order by e4.selite SEPARATOR '<br>') autodatatuoteno
            FROM asiakas
            JOIN kuka on (kuka.yhtio = asiakas.yhtio
                and kuka.oletus_asiakas = asiakas.tunnus)
            LEFT JOIN extranet_kayttajan_lisatiedot e1 on (e1.yhtio = asiakas.yhtio
                and e1.liitostunnus     = kuka.tunnus
                and e1.laji             = 'AUTODATATUNNUS')
            LEFT JOIN extranet_kayttajan_lisatiedot e2 on (e2.yhtio = asiakas.yhtio
                and e2.liitostunnus     = kuka.tunnus
                and e2.laji             = 'AUTODATALISENSSI')
            LEFT JOIN extranet_kayttajan_lisatiedot e3 on (e3.yhtio = asiakas.yhtio
                and e3.liitostunnus     = kuka.tunnus
                and e3.laji             = 'AUTODATATHINCLIENT')
            LEFT JOIN extranet_kayttajan_lisatiedot e4 on (e4.yhtio = asiakas.yhtio
                and e4.liitostunnus     = kuka.tunnus
                and e4.laji             = 'AUTODATATUOTENO')
            WHERE asiakas.yhtio         = '$kukarow[yhtio]'
            $wherelisa
            GROUP BY asiakas.tunnus
            HAVING autodatatunnus is not null or autodatalisenssi is not null or autodatathinclient is not null or autodatatuoteno is not null";
  $asiakasres = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($asiakasres) == 0) {
    echo "<font class='message'>Yht‰‰n asiakasta ei lˆytynyt!</font>";
    exit;
  }

  echo "<br><br>";
  echo "<table>";
  echo "<tr class='aktiivi'>";
  echo "<th>Nimi</th>";
  echo "<th>Ytunnus</th>";
  echo "<th>Osoite</th>";
  echo "<th>Pupesoft_user</th>";
  echo "<th>Autodata_user</th>";
  echo "<th>Lisenssi_pvm</th>";
  echo "<th>Thinclient</th>";
  echo "<th>Versio</th>";
  echo "</tr>";

  while ($asiakasrow = mysql_fetch_assoc($asiakasres)) {
    echo "<tr>";
    echo "<td valign='top'>{$asiakasrow['nimi']}</td>";
    echo "<td valign='top'>{$asiakasrow['ytunnus']}</td>";
    echo "<td valign='top'>{$asiakasrow['osoite']}</td>";
    echo "<td valign='top' nowrap>{$asiakasrow['kuka']}</td>";
    echo "<td valign='top' nowrap>{$asiakasrow['autodatatunnus']}</td>";
    echo "<td valign='top' nowrap>{$asiakasrow['autodatalisenssi']}</td>";
    echo "<td valign='top' nowrap>{$asiakasrow['autodatathinclient']}</td>";
    echo "<td valign='top' nowrap>{$asiakasrow['autodatatuoteno']}</td>";
    echo "</tr>";
  }

  echo "</table>";
}
