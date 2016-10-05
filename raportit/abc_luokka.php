<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

echo "<font class='head'>".t("ABC-Analyysi‰: ABC-luokka")." $ryhmanimet[$luokka]<hr></font>";

if ($toim == "kulutus") {
  $myykusana = t("Kulutus");
}
else {
  $myykusana = t("Myynti");
}

if ($asiakasanalyysi) {
  $astusana = t("Asiakas");
}
else {
  $astusana = t("Tuote");
}

if (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '') {
  $saapumispp = $saapumispp;
  $saapumiskk = $saapumiskk;
  $saapumisvv  = $saapumisvv;
}
elseif (trim($saapumispvm) != '') {
  list($saapumisvv, $saapumiskk, $saapumispp) = explode('-', $saapumispvm);
}

// piirrell‰‰n formi
echo "<form method='post' autocomplete='OFF'>";
echo "<input type='hidden' name='tee' value='LUOKKA'>";
echo "<input type='hidden' name='toim' value='$toim'>";

// Monivalintalaatikot (osasto, try tuotemerkki...)
// M‰‰ritell‰‰n mitk‰ latikot halutaan mukaan
$abc_lisa  = "";
$ulisa = "";
$mulselprefix = "abc_aputaulu";

if ($asiakasanalyysi) {
  $monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA");
}
else {
  $monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "TUOTEMYYJA", "TUOTEOSTAJA");
}

require "tilauskasittely/monivalintalaatikot.inc";

echo "<br>";
echo "<table style='display:inline;'>";
echo "<tr>";
echo "<th>".t("Valitse luokka").":</th>";
echo "<td><select name='luokka'>";
echo "<option value=''>".t("Valitse luokka")."</option>";

$sel = array();
$sel[$luokka] = "selected";

$i=0;
foreach ($ryhmanimet as $nimi) {
  echo "<option value='$i' $sel[$i]>$nimi</option>";
  $i++;
}

echo "</select></td>";
echo "</tr>";

if (!$asiakasanalyysi) {
  echo "<tr>";
  echo "<th>", t("Tuotteen status"), "</th>";
  echo "<td><select name='status'><option value=''>".t("Kaikki")."</option>";
  echo product_status_options($status);
  echo "</select></td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>".t("Viimeinen saapumisp‰iv‰").":</th>";
  echo "<td nowrap><input type='text' name='saapumispp' value='$saapumispp' size='2'>
      <input type='text' name='saapumiskk' value='$saapumiskk' size='2'>
      <input type='text' name='saapumisvv' value='$saapumisvv'size='4'></td></tr>";
}

echo "<tr>";
echo "<th>".t("Taso").":</th>";

if ($lisatiedot != '') $sel = "selected";
else $sel = "";

echo "<td><select name='lisatiedot'>";
echo "<option value=''>".t("Normaalitiedot")."</option>";
echo "<option value='TARK' $sel>".t("N‰ytet‰‰n kaikki sarakkeet")."</option>";
echo "</select></td>";
echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
echo "</tr>";
echo "</form>";
echo "</table><br>";

if ($luokka != "") {
  if (count($haku) > 0) {
    foreach ($haku as $kentta => $arvo) {
      if (strlen($arvo) > 0 and $kentta != 'kateosuus') {
        $abc_lisa  .= " and abc_aputaulu.$kentta like '%$arvo%'";
        $ulisa2 .= "&haku[$kentta]=$arvo";
      }
      if (strlen($arvo) > 0 and $kentta == 'kateosuus') {
        $hav = "HAVING abc_aputaulu.kateosuus like '%$arvo%' ";
        $ulisa2 .= "&haku[$kentta]=$arvo";
      }
    }
  }

  if (strlen($order) > 0) {
    if ($order == "try" or $order == "osasto" or $order == "nimitys" or $order == "luokka") {
      $jarjestys = "abc_aputaulu.".$order." ".$sort;
    }
    else {
      $jarjestys = $order." ".$sort;
    }
  }
  else {
    $jarjestys = "abc_aputaulu.luokka, $abcwhat desc";
  }

  $saapumispvmlisa = "";

  if (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '') {
    $saapumispvm = "$saapumisvv-$saapumiskk-$saapumispp";
    $saapumispvmlisa = " and abc_aputaulu.saapumispvm <= '$saapumispvm' ";
  }

  if (!$asiakasanalyysi) {
    if ($status != '') {
      $abc_lisa .= " and abc_aputaulu.status = '".(string) $status."' ";
    }
  }
  // n‰m‰ m‰‰ritt‰‰ kumpaan tauluun Joinataan, asiakas vai tuote
  $asiakas_join_array = array('AK', 'AM', 'AP', 'AR');
  $tuote_join_array = array('TK', 'TM', 'TP', 'TR', 'TV');

  if (in_array($abcchar, $asiakas_join_array)) {
    $analyysin_join = " JOIN asiakas on (abc_aputaulu.yhtio = asiakas.yhtio and abc_aputaulu.tuoteno = asiakas.tunnus) ";
  }
  elseif (in_array($abcchar, $tuote_join_array)) {
    $analyysin_join = " JOIN tuote USING (yhtio, tuoteno) ";
  }
  else {
    $analyysin_join = "";
  }

  list($ryhmanimet, $ryhmaprossat, $kiertonopeus_tavoite, $palvelutaso_tavoite, $varmuusvarasto_pv, $toimittajan_toimitusaika_pv) = hae_ryhmanimet($abcchar);

  //kauden yhteismyynnit ja katteet
  $query = "SELECT
            sum(abc_aputaulu.summa) yhtmyynti,
            sum(abc_aputaulu.kate) yhtkate
            FROM abc_aputaulu
            {$analyysin_join}
            WHERE abc_aputaulu.yhtio = '{$kukarow["yhtio"]}'
            and abc_aputaulu.tyyppi  = '$abcchar'
            and abc_aputaulu.luokka  = '$luokka'
            $abc_lisa
            $lisa
            $saapumispvmlisa";
  $sumres = pupe_query($query);
  $sumrow = mysql_fetch_assoc($sumres);

  if ($sumrow["yhtkate"] == 0) {
    $sumrow["yhtkate"] = 0.01;
  }

  //haetaan rivien arvot
  $query = "SELECT *,
            if ({$sumrow["yhtkate"]} = 0, 0, abc_aputaulu.kate / {$sumrow["yhtkate"]} * 100) kateosuus,
            abc_aputaulu.katepros * abc_aputaulu.varaston_kiertonop kate_kertaa_kierto,
            abc_aputaulu.kate - abc_aputaulu.kustannus_yht total
            FROM abc_aputaulu
            {$analyysin_join}
            WHERE abc_aputaulu.yhtio = '{$kukarow["yhtio"]}'
            and abc_aputaulu.tyyppi  = '$abcchar'
            and abc_aputaulu.luokka  = '$luokka'
            $saapumispvmlisa
            $abc_lisa
            $lisa
            $hav
            ORDER BY $jarjestys";
  $res = pupe_query($query);

  echo "<br><table>";

  echo "<tr>";
  echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=luokka&status=$status&sort=asc$ulisa2'>".t("ABC")."<br>".t("Luokka")."</th>";
  echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=tuoteno&status=$status&sort=asc$ulisa2'>$astusana</a><br>&nbsp;</th>";
  echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=nimitys&status=$status&sort=asc$ulisa2'>".t("Nimitys")."</a><br>&nbsp;</th>";
  echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=osasto&status=$status&sort=asc$ulisa2'>".t("Osasto")."</a><br>&nbsp;</th>";
  echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=try&status=$status&sort=asc$ulisa2'>".t("Ryhm‰")."</a><br>&nbsp;</th>";

  if (!$asiakasanalyysi and $lisatiedot == "TARK") {
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=tuotemerkki&status=$status&sort=asc$ulisa2'>".t("Merkki")."</a><br>&nbsp;</th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=malli&status=$status&sort=asc$ulisa2'>".t("Malli")."</a><br>&nbsp;</th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=mallitarkenne&status=$status&sort=asc$ulisa2'>".t("Mallitarkenne")."</a><br>&nbsp;</th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=myyjanro&status=$status&sort=asc$ulisa2'>".t("Myyj‰")."</a><br>&nbsp;</th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=ostajanro&status=$status&sort=asc$ulisa2'>".t("Ostaja")."</a><br>&nbsp;</th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=saapumispvm&status=$status&sort=asc$ulisa2'>".t("Viimeinen")."<br>".t("Saapumispvm")."</a><br>&nbsp;</th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=saldo&status=$status&sort=asc$ulisa2'>".t("Saldo")."</a><br>&nbsp;</th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=tulopvm&status=$status&sort=desc$ulisa2'>".t("Tulopvm")."</a><br>&nbsp;</th>";
  }

  echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=summa&status=$status&sort=desc$ulisa2'>$myykusana<br>".t("tot")."</a></th>";
  echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kate&status=$status&sort=desc$ulisa2'>".t("Kate")."<br>".t("tot")."</a></th>";
  echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=katepros&status=$status&sort=desc$ulisa2'>".t("Kate")."<br>%</a></th>";

  if ($lisatiedot == "TARK") echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kateosuus&status=$status&sort=desc$ulisa2'>".t("Osuus")." %<br>".t("kat").".</a></th>";

  if (!$asiakasanalyysi) {
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=vararvo&status=$status&sort=desc$ulisa2'>".t("Varast").".<br>".t("arvo")."</a></th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=varaston_kiertonop&status=$status&sort=desc$ulisa2'>".t("Varast").".<br>".t("kiert").".</a></th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kate_kertaa_kierto&status=$status&sort=desc$ulisa2'>".t("Kate")."% x<br>".t("kiert").".</a></th>";
  }

  echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kpl&status=$status&sort=desc$ulisa2'>$myykusana<br>".t("m‰‰r‰")."</a></th>";

  if ($lisatiedot == "TARK") {
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=myyntierankpl&status=$status&sort=desc$ulisa2'>$myykusana".t("er‰")."<br>".t("m‰‰r‰")."</a></th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=myyntieranarvo&status=$status&sort=desc$ulisa2'>$myykusana".t("er‰")."<br>$yhtiorow[valkoodi]</a></th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=rivia&status=$status&sort=desc$ulisa2'>$myykusana<br>".t("rivej‰")."</a></th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=puuterivia&status=$status&sort=desc$ulisa2'>".t("Puute")."<br>".t("rivej‰")."</a></th>";
  }

  echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=palvelutaso&status=$status&sort=desc$ulisa2'>".t("Palvelu")."-<br>".t("taso")." %</a></th>";

  if (!$asiakasanalyysi and $lisatiedot == "TARK") {
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=ostoerankpl&status=$status&sort=desc$ulisa2'>".t("Ostoer‰")."<br>".t("m‰‰r‰")."</a></th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=ostoeranarvo&status=$status&sort=desc$ulisa2'>".t("Ostoer‰")."<br>$yhtiorow[valkoodi]</a></th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=osto_rivia&status=$status&sort=desc$ulisa2'>".t("Ostettu")."<br>".t("rivej‰")."</a></th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kustannus&status=$status&sort=desc$ulisa2'>".t("Myynn").".<br>".t("kustan").".</a></th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kustannus_osto&status=$status&sort=desc$ulisa2'>".t("Oston")."<br>".t("kustan").".</a></th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=kustannus_yht&status=$status&sort=desc$ulisa2'>".t("Kustan").".<br>".t("yht")."</a></th>";
    echo "<th nowrap><a href='$PHP_SELF?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&order=total&status=$status&sort=desc$ulisa2'>".t("Kate -")."<br>".t("Kustannus")."</a></th>";
  }

  echo "<form action = '?toim=$toim&tee=LUOKKA&luokka=$luokka$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&valinta=$valinta&status=$status' method='post'>";
  echo "<tr>";
  echo "<th><input type='text' name='haku[luokka]' value='$haku[luokka]' size='5'></th>";
  echo "<th><input type='text' name='haku[tuoteno]' value='$haku[tuoteno]' size='5'></th>";
  echo "<th><input type='text' name='haku[nimitys]' value='$haku[nimitys]' size='5'></th>";
  echo "<th><input type='text' name='haku[osasto]' value='$haku[osasto]' size='5'></th>";
  echo "<th><input type='text' name='haku[try]' value='$haku[try]' size='5'></th>";

  if (!$asiakasanalyysi and $lisatiedot == "TARK") {
    echo "<th><input type='text' name='haku[tuotemerkki]' value='$haku[tuotemerkki]' size='5'></th>";
    echo "<th><input type='text' name='haku[malli]' value='$haku[malli]' size='5'></th>";
    echo "<th><input type='text' name='haku[mallitarkenne]' value='$haku[mallitarkenne]' size='5'></th>";
    echo "<th><input type='text' name='haku[myyjanro]' value='$haku[myyjanro]' size='5'></th>";
    echo "<th><input type='text' name='haku[ostajanro]' value='$haku[ostajanro]' size='5'></th>";
    echo "<th><input type='text' name='haku[saapumispvm]' value='$haku[saapumispvm]' size='5'></th>";
    echo "<th><input type='text' name='haku[saldo]' value='$haku[saldo]' size='5'></th>";
    echo "<th><input type='text' name='haku[tulopvm]' value='$haku[tulopvm]' size='5'></th>";
  }

  echo "<th><input type='text' name='haku[summa]' value='$haku[summa]' size='5'></th>";
  echo "<th><input type='text' name='haku[kate]' value='$haku[kate]' size='5'></th>";
  echo "<th><input type='text' name='haku[katepros]' value='$haku[katepros]' size='5'></th>";

  if ($lisatiedot == "TARK") echo "<th><input type='text' name='haku[kateosuus]' value='$haku[kateosuus]' size='5'></th>";

  if (!$asiakasanalyysi) {
    echo "<th><input type='text' name='haku[vararvo]' value='$haku[vararvo]' size='5'></th>";
    echo "<th><input type='text' name='haku[varaston_kiertonop]' value='$haku[varaston_kiertonop]' size='5'></th>";
    echo "<th><input type='text' name='haku[kate_kertaa_kierto]' value='$haku[kate_kertaa_kierto]' size='5'></th>";
  }

  echo "<th><input type='text' name='haku[kpl]' value='$haku[kpl]' size='5'></th>";

  if ($lisatiedot == "TARK") {
    echo "<th><input type='text' name='haku[myyntierankpl]' value='$haku[myyntierankpl]' size='5'></th>";
    echo "<th><input type='text' name='haku[myyntieranarvo]' value='$haku[myyntieranarvo]' size='5'></th>";
    echo "<th><input type='text' name='haku[rivia]' value='$haku[rivia]' size='5'></th>";
    echo "<th><input type='text' name='haku[puuterivia]' value='$haku[puuterivia]' size='5'></th>";
  }

  echo "<th><input type='text' name='haku[palvelutaso]' value='$haku[palvelutaso]' size='5'></th>";

  if (!$asiakasanalyysi and $lisatiedot == "TARK") {
    echo "<th><input type='text' name='haku[ostoerankpl]' value='$haku[ostoerankpl]' size='5'></th>";
    echo "<th><input type='text' name='haku[ostoeranarvo]' value='$haku[ostoeranarvo]' size='5'></th>";
    echo "<th><input type='text' name='haku[osto_rivia]'  value='$haku[osto_rivia]' size='5'></th>";
    echo "<th><input type='text' name='haku[kustannus]' value='$haku[kustannus]' size='5'></th>";
    echo "<th><input type='text' name='haku[kustannus_osto]'value='$haku[kustannus_osto]' size='5'></th>";
    echo "<th><input type='text' name='haku[kustannus_yht]' value='$haku[kustannus_yht]' size='5'></th>";
    echo "<th><input type='text' name='haku[total]' value='$haku[total]' size='5'></th>";
  }

  echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Etsi")."'></td></form></tr>";

  //jos rivej‰ ei lˆydy
  if (mysql_num_rows($res) == 0) {
    echo "</table>";
  }
  else {
    while ($row = mysql_fetch_assoc($res)) {

      //haetaan asiakkaan tiedot
      if ($asiakasanalyysi) {
        $query = "SELECT *
                  FROM asiakas
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$row[tuoteno]'";
        $asres = pupe_query($query);
        $asrow = mysql_fetch_assoc($asres);

        $row["asiakastunnus"] = $row["tuoteno"];
        $row["tuoteno"] = $asrow["ytunnus"];
        $row["nimitys"] = $asrow["nimi"];
      }

      echo "<tr>";
      echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=YHTEENVETO$ulisa&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&status=$status'>".$ryhmanimet[$luokka]."</a></td>";

      if (!$asiakasanalyysi) echo "<td valign='top'><a href='../tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
      else echo "<td valign='top'><a href='../crm/asiakasmemo.php?asiakasid=$row[asiakastunnus]'>$row[tuoteno]</a></td>";

      echo "<td valign='top'>$row[nimitys]</td>";

      if (!$asiakasanalyysi) {
        echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY".preg_replace("/&mul_osasto\[\]\=[^&]*/i", "", $ulisa)."&mul_osasto[]=$row[osasto]&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&status=$status'>$row[osasto]</a></td>";
        echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY".preg_replace("/&mul_(osasto|try)\[\]\=[^&]*/i", "", $ulisa)."&mul_osasto[]=$row[osasto]&mul_try[]=$row[try]&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&status=$status'>$row[try]</a></td>";
      }
      else {
        echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY".preg_replace("/&mul_asiakasosasto\[\]\=[^&]*/i", "", $ulisa)."&mul_asiakasosasto[]=$row[osasto]&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&status=$status'>$row[osasto]</a></td>";
        echo "<td valign='top'><a href='$PHP_SELF?toim=$toim&tee=OSASTOTRY".preg_replace("/&mul_(asiakasosasto|asiakasryhma)\[\]\=[^&]*/i", "", $ulisa)."&mul_asiakasosasto[]=$row[osasto]&mul_asiakasryhma[]=$row[try]&saapumispvm=$saapumispvm&lisatiedot=$lisatiedot&status=$status'>$row[try]</a></td>";
      }

      if (!$asiakasanalyysi and $lisatiedot == "TARK") {
        echo "<td valign='top'>$row[tuotemerkki]</td>";
        echo "<td valign='top'>$row[malli]</td>";
        echo "<td valign='top'>$row[mallitarkenne]</td>";

        $query = "SELECT distinct myyja, nimi
                  FROM kuka
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND myyja   = '$row[myyjanro]'
                  AND myyja   > 0
                  ORDER BY myyja";
        $sresult = pupe_query($query);
        $srow = mysql_fetch_assoc($sresult);

        echo "<td valign='top'>$srow[nimi]</td>";

        $query = "SELECT distinct myyja, nimi
                  FROM kuka
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND myyja   = '$row[ostajanro]'
                  AND myyja   > 0
                  ORDER BY myyja";
        $sresult = pupe_query($query);
        $srow = mysql_fetch_assoc($sresult);

        echo "<td valign='top'>$srow[nimi]</td>";

        echo "<td valign='top'>".tv1dateconv($row["saapumispvm"])."</td>";
        echo "<td align='right' valign='top'>$row[saldo]</td>";
        echo "<td valign='top'>".tv1dateconv($row["tulopvm"])."</td>";
      }

      echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["summa"]))."</td>";
      echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["kate"]))."</td>";
      echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["katepros"]))."</td>";

      if ($lisatiedot == "TARK") echo "<td align='right' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["kateosuus"]))."</td>";

      if (!$asiakasanalyysi) {
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["vararvo"]))."</td>";
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["varaston_kiertonop"]))."</td>";
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["kate_kertaa_kierto"]))."</td>";
      }

      echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.0f', $row["kpl"]))."</td>";

      if ($lisatiedot == "TARK") {
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["myyntierankpl"]))."</td>";
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["myyntieranarvo"]))."</td>";
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.0f', $row["rivia"]))."</td>";
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.0f', $row["puuterivia"]))."</td>";
      }

      echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["palvelutaso"]))."</td>";

      if (!$asiakasanalyysi and $lisatiedot == "TARK") {
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["ostoerankpl"]))."</td>";
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["ostoeranarvo"]))."</td>";
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.0f', $row["osto_rivia"]))."</td>";
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["kustannus"]))."</td>";
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["kustannus_osto"]))."</td>";
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["kustannus_yht"]))."</td>";
        echo "<td align='right' valign='top' nowrap>".str_replace(".", ",", sprintf('%.1f', $row["total"]))."</td>";
      }
      echo "</tr>\n";

      $saldoyht        += $row["saldo"];
      $ryhmamyyntiyht     += $row["summa"];
      $ryhmakateyht       += $row["kate"];
      $ryhmanvarastonarvoyht   += $row["vararvo"];
      $rivilkmyht        += $row["rivia"];
      $ryhmakplyht      += $row["kpl"];
      $ryhmapuuteyht      += $row["puutekpl"];
      $ryhmapuuterivityht    += $row["puuterivia"];
      $ryhmaostotyht        += $row["osto_summa"];
      $ryhmaostotkplyht    += $row["osto_kpl"];
      $ryhmaostotrivityht   += $row["osto_rivia"];
      $ryhmakustamyyyht    += $row["kustannus"];
      $ryhmakustaostyht    += $row["kustannus_osto"];
      $ryhmakustayhtyht    += $row["kustannus_yht"];
      $totalyht        += $row["total"];

    }

    //yhteens‰rivi
    if ($ryhmamyyntiyht != 0) $kateprosenttiyht = round($ryhmakateyht / $ryhmamyyntiyht * 100, 2);
    else $kateprosenttiyht = 0;

    if ($sumrow["yhtkate"] != 0) $kateosuusyht = round($ryhmakateyht / $sumrow["yhtkate"] * 100, 2);
    else $kateosuusyht = 0;

    if ($ryhmanvarastonarvoyht != 0) $kiertonopeusyht = round(($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht, 2);
    else $kiertonopeusyht = 0;

    if ($rivilkmyht != 0) $myyntieranarvoyht = round($ryhmamyyntiyht / $rivilkmyht, 2);
    else $myyntieranarvoyht = 0;

    if ($rivilkmyht != 0) $myyntieranakplyht = round($ryhmakplyht / $rivilkmyht, 2);
    else $myyntieranakplyht = 0;

    if ($ryhmapuuterivityht + $rivilkmyht != 0)  $palvelutasoyht = round(100 - ($ryhmapuuterivityht / ($ryhmapuuterivityht + $rivilkmyht) * 100), 2);
    else $palvelutasoyht = 0;

    if ($ryhmaostotrivityht != 0) $ostoeranarvoyht = round($ryhmaostotyht / $ryhmaostotrivityht, 2);
    else $ostoeranarvoyht = 0;

    if ($ryhmaostotrivityht != 0) $ostoeranakplyht = round($ryhmaostotkplyht / $ryhmaostotrivityht, 2);
    else $ostoeranakplyht = 0;

    if ($ryhmamyyntiyht != 0 and $ryhmanvarastonarvoyht != 0) {
      $kate_kertaa_kierto = round(($ryhmakateyht / $ryhmamyyntiyht * 100) * (($ryhmamyyntiyht - $ryhmakateyht) / $ryhmanvarastonarvoyht), 2);
    }
    else {
      $kate_kertaa_kierto = 0;
    }

    echo "<tr>";

    if (!$asiakasanalyysi and $lisatiedot == "TARK") {
      echo "<td colspan='11' class='spec'>".t("Yhteens‰").":</td>";
    }
    else {
      echo "<td colspan='5' class='spec'>".t("Yhteens‰").":</td>";
    }

    if (!$asiakasanalyysi and $lisatiedot == "TARK") {
      echo "<td align='right' class='spec' nowrap>$saldoyht</td>";
      echo "<td></td>";
    }
    echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $ryhmamyyntiyht))."</td>";
    echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $ryhmakateyht))."</td>";
    echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $kateprosenttiyht))."</td>";

    if ($lisatiedot == "TARK") echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $kateosuusyht))."</td>";

    if (!$asiakasanalyysi) {
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $ryhmanvarastonarvoyht))."</td>";
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $kiertonopeusyht))."</td>";
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $kate_kertaa_kierto))."</td>";
    }

    echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.0f', $ryhmakplyht))."</td>";

    if ($lisatiedot == "TARK") {
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $myyntieranakplyht))."</td>";
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $myyntieranarvoyht))."</td>";
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.0f', $rivilkmyht))."</td>";
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.0f', $ryhmapuuterivityht))."</td>";
    }

    echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $palvelutasoyht))."</td>";

    if (!$asiakasanalyysi and $lisatiedot == "TARK") {
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $ostoeranakplyht))."</td>";
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $ostoeranarvoyht))."</td>";
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.0f', $ryhmaostotrivityht))."</td>";
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $ryhmakustamyyyht))."</td>";
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $ryhmakustaostyht))."</td>";
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $ryhmakustayhtyht))."</td>";
      echo "<td align='right' class='spec' nowrap>".str_replace(".", ",", sprintf('%.1f', $totalyht))."</td>";
    }

    echo "</tr>\n";
    echo "</table>";
  }
}
