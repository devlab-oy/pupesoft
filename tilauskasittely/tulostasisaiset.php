<?php

require('../inc/parametrit.inc');

 echo "<font class='head'>".t("Tulosta sisäisiä laskuja").":</font><hr><br>";

if (isset($tee) and $tee == 'TULOSTA') {

  if ($tila == 'yksi') {
    if ($laskunro != '') {
      $where = " and laskunro='$laskunro' ";
    }
  }
  elseif ($tila == 'monta') {
    if ($vva != '' and $vvl != '' and $kka != '' and $kkl != '') {
      $where = "  and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
            and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59' ";
    }
  }
  else {
    echo t("Ilman hakukriteerejä ei voida jatkaa")."!";
    exit;
  }

  if ($where == '') {
    echo t("Et syöttänyt mitään järkevää")."!<br>";
    exit;
  }

  if ($raportti == "k") {
    $where .= " and vienti != '' ";
  }
  else {
    $where .= " and vienti = '' ";
  }

  require_once("tilauskasittely/tulosta_lasku.inc");

  //hateaan laskun kaikki tiedot
  $query = "  SELECT *
        FROM lasku
        WHERE tila    = 'U'
        and alatila   = 'X'
        and sisainen != ''
        $where
        and yhtio ='$kukarow[yhtio]'
        ORDER BY laskunro";
  $laskurrrresult = mysql_query($query) or pupe_error($query);

  while ($sislaskrow = mysql_fetch_array($laskurrrresult)) {
    
    echo t("Tulostetaan sisäinen lasku").": $sislaskrow[laskunro]<br>";
    
    tulosta_lasku($sislaskrow["tunnus"], "", "", "", $valittu_tulostin, "", "");
  }

  $tee = '';
  echo "<br>";
}

if (!isset($tee) or $tee == '') {
  //syötetään tilausnumero
  echo "<form method = 'post'>";
  echo "<input type='hidden' name='tee' value='TULOSTA'>";
  echo "<input type='hidden' name='tila' value='yksi'>";

  echo "<table>";
  echo "<tr><th colspan='2'>".t("Tulosta yksittäinen lasku")."</th></tr>";
  echo "<tr><th>".t("Laskunumero")."</th><td><input type='text' size='10' name='laskunro'></td></tr>";
  echo "<tr><th>".t("Tulosta lasku").":</th><td colspan='3'><select name='valittu_tulostin'>";
  echo "<option value=''>".t("Ei kirjoitinta")."</option>";

  $query = "  SELECT *
        FROM kirjoittimet
        WHERE
        yhtio = '$kukarow[yhtio]'
        ORDER by kirjoitin";
  $kirre = mysql_query($query) or pupe_error($query);

  while ($kirrow = mysql_fetch_array($kirre)) {
    $sel = "";
    if ($kirrow["tunnus"] == $kukarow["kirjoitin"]) {
      $sel = "SELECTED";
    }

    echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
  }

  echo "</select></td></tr>";
  echo "<tr><th></th><td colspan='3'><input type='submit' value='".t("Tulosta")."'></td></tr>";
  echo "</table>";
  echo "</form><br><br>";



  if (!isset($kka))
    $kka = date("m");
  if (!isset($vva))
    $vva = date("Y");
  if (!isset($ppa))
    $ppa = date("d");

  if (!isset($kkl))
    $kkl = date("m");
  if (!isset($vvl))
    $vvl = date("Y");
  if (!isset($ppl))
    $ppl = date("d");

  echo "<table>";
  echo "<form method = 'post'>";
  echo "<input type='hidden' name='tee' value='TULOSTA'>";
  echo "<input type='hidden' name='tila' value='monta'>";
  echo "<tr><th colspan='4'>".t("Tulosta sisäiset laskut päivämäärärajauksella")."</th></tr>";
  echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppa' value='$ppa' size='3'></td>
      <td><input type='text' name='kka' value='$kka' size='3'></td>
      <td><input type='text' name='vva' value='$vva' size='5'></td></tr>
      <tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppl' value='$ppl' size='3'></td>
      <td><input type='text' name='kkl' value='$kkl' size='3'></td>
      <td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";
  echo "<tr><th>".t("Vain kotimaiset laskut")."</th><td colspan='3'><input type='radio' name='raportti' value='e' checked></td></tr>";
  echo "<tr><th>".t("Vain vientilaskut")."</th><td colspan='3'><input type='radio' name='raportti' value='k'></td></tr>";

  echo "<tr><th>".t("Tulosta lasku").":</th><td colspan='3'><select name='valittu_tulostin'>";
  echo "<option value=''>".t("Ei kirjoitinta")."</option>";

  $query = "  SELECT *
        FROM kirjoittimet
        WHERE
        yhtio = '$kukarow[yhtio]'
        ORDER by kirjoitin";
  $kirre = mysql_query($query) or pupe_error($query);

  while ($kirrow = mysql_fetch_array($kirre)) {
    $sel = "";
    if ($kirrow["tunnus"] == $kukarow["kirjoitin"]) {
      $sel = "SELECTED";
    }

    echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
  }

  echo "</select></td></tr>";
  echo "<tr><th></th><td colspan='3'><input type='submit' value='".t("Tulosta")."'></td></tr>";
  echo "</table>";
  echo "</form>";

}

require ("inc/footer.inc");
