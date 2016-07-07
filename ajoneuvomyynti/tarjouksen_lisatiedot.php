<?php

require "../inc/parametrit.inc";

if ($tee != 'osamaksusoppari' and $tee != 'vakuutushakemus') {
  echo "<font class='head'>".t("Lisätietojen korjaus").":</font><hr><br>";
}

if ($tee == 'osamaksusoppari') {
  // Tehdään rahoituslaskuelma
  require 'osamaksusoppari.inc';
}
elseif ($tee == 'vakuutushakemus') {
  // Tehdään vakuutushakemus
  require 'vakuutushakemus.inc';
}


if ($tee == "TULOSTA") {
  $tulostimet[0] = 'Tarjous';
  if ($kappaleet > 0 and $komento["Tarjous"] != '' and $komento["Tarjous"] != 'email') {
    $komento["Tarjous"] .= " -# $kappaleet ";
  }

  $tulostimet[1] = 'Myyntisopimus';
  if ($kappaleet > 0 and $komento["Myyntisopimus"] != '' and $komento["Myyntisopimus"] != 'email') {
    $komento["Myyntisopimus"] .= " -# $kappaleet ";
  }

  $tulostimet[2] = 'Osamaksusopimus';
  if ($kappaleet > 0 and $komento["Osamaksusopimus"] != '' and $komento["Osamaksusopimus"] != 'email') {
    $komento["Osamaksusopimus"] .= " -# $kappaleet ";
  }

  $tulostimet[3] = 'Luovutustodistus';
  if ($kappaleet > 0 and $komento["Luovutustodistus"] != '' and $komento["Luovutustodistus"] != 'email') {
    $komento["Luovutustodistus"] .= " -# $kappaleet ";
  }

  $tulostimet[4] = 'Vakuutushakemus';
  if ($kappaleet > 0 and $komento["Vakuutushakemus"] != '' and $komento["Vakuutushakemus"] != 'email') {
    $komento["Vakuutushakemus"] .= " -# $kappaleet ";
  }

  $tulostimet[5] = 'Rekisteröinti_ilmoitus';
  if ($kappaleet > 0 and $komento["Rekisteröinti_ilmoitus"] != '' and $komento["Rekisteröinti_ilmoitus"] != 'email') {
    $komento["Rekisteröinti_ilmoitus"] .= " -# $kappaleet ";
  }

  if (count($komento) == 0 and $tee == 'TULOSTA') {
    require "../inc/valitse_tulostin.inc";
  }
}

if ($tee == "TULOSTA") {
  if ($komento["Tarjous"] != "") {
    require_once "tulosta_tarjous.inc";
    tulosta_tarjous($otunnus, $komento["Tarjous"], $kieli, $tee);
  }

  if ($komento["Myyntisopimus"] != "") {
    require_once "tulosta_myyntisopimus.inc";
    tulosta_myyntisopimus($otunnus, $komento["Myyntisopimus"], $kieli, $tee);
  }

  if ($komento["Osamaksusopimus"] != "") {
    require_once "tulosta_osamaksusoppari.inc";
    tulosta_osamaksusoppari($otunnus, $komento["Osamaksusopimus"], $kieli, $tee);
  }

  if ($komento["Luovutustodistus"] != "") {
    require_once "tulosta_luovutustodistus.inc";
    tulosta_luovutustodistus($otunnus, $komento["Luovutustodistus"], $kieli, $tee);
  }

  if ($komento["Vakuutushakemus"] != "") {
    require_once "tulosta_vakuutushakemus.inc";
    tulosta_vakuutushakemus($otunnus, $komento["Vakuutushakemus"], $kieli, $tee);
  }

  if ($komento["Rekisteröinti_ilmoitus"] != "") {
    require_once "tulosta_rekisteriilmoitus.inc";
    tulosta_rekisteriilmoitus($otunnus, $komento["Rekisteröinti_ilmoitus"], $kieli, $tee);
  }
  $otunnus = "";
  $tee   = "";
}


if ($tee == 'NAYTAHTML' or $tee == 'NAYTATILAUS') {
  echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
  require "raportit/naytatilaus.inc";
  echo "<br><br>";
  $tee = "ETSILASKU";
}

if ($tee == "" or $tee == 'ETSILASKU') {
  if ($ytunnus != '') {
    require "inc/asiakashaku.inc";
  }
  if ($ytunnus != '') {
    $tee = "ETSILASKU";
  }
  else {
    $tee = "";
  }

  if ($laskunro > 0) {
    $tee = "ETSILASKU";
  }

  if ($otunnus > 0) {
    $tee = 'ETSILASKU';
  }
}

if ($tee == "ETSILASKU") {
  echo "<form method='post' autocomplete='off'>
			<input type='hidden' name='ytunnus' value='$ytunnus'>
			<input type='hidden' name='asiakasid' value='$asiakasid'>
			<input type='hidden' name='tee' value='ETSILASKU'>";

  echo "<table>";

  if (!isset($kka))
    $kka = date("m", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
  if (!isset($vva))
    $vva = date("Y", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
  if (!isset($ppa))
    $ppa = date("d", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));

  if (!isset($kkl))
    $kkl = date("m");
  if (!isset($vvl))
    $vvl = date("Y");
  if (!isset($ppl))
    $ppl = date("d");

  echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
  echo "<td class='back'><input type='submit' value='".t("Hae")."'></td></tr></form></table><br>";

  $where1 = "";
  $where2 = "";

  //myyntilasku. Tälle oliolle voidaan tulostaa laskun kopio
  $where1 = " lasku.tila in ('L','N') ";

  if ($ytunnus{0} == '£') {
    $where2 = " and lasku.nimi      = '$asiakasrow[nimi]'
						and lasku.nimitark  = '$asiakasrow[nimitark]'
						and lasku.osoite    = '$asiakasrow[osoite]'
						and lasku.postino   = '$asiakasrow[postino]'
						and lasku.postitp   = '$asiakasrow[postitp]' ";
  }
  else {
    $where2 = " and lasku.liitostunnus  = '$asiakasid'";
  }

  $where2 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
					 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

  if (!isset($jarj)) $jarj = " lasku.tunnus desc";

  $use = " use index (yhtio_tila_luontiaika) ";

  if ($laskunro > 0) {
    $where2 = " and lasku.laskunro = '$laskunro' ";
    if (!isset($jarj)) $jarj = " lasku.tunnus desc";
    $use = " use index (lasno_index) ";
  }

  if ($otunnus > 0) {
    //katotaan löytyykö lasku ja sen kaikki tilaukset
    $query = "SELECT laskunro





















		$query = "SELECT lasku.tunnus Tilaus, if (lasku.laskunro=0, '', laskunro) Laskunro,















































































































      
