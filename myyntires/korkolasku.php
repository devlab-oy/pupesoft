<?php

require "../inc/parametrit.inc";

echo "<font class='head'>".t("Korkolaskut")."</font><hr>";

//laskun pitää olla vähintään näin monta päivää myöhässä maksettu
//laskut huomioidaan näkymässsä
$min_myoh = 3;

$query = "SELECT tuoteno
          FROM tuote
          WHERE yhtio = '$kukarow[yhtio]'
          AND tuoteno = 'Korko'";
$result = pupe_query($query);

if (mysql_num_rows($result) != 1) {
  echo "<font class='error'>".t("Tuoterekisteristä ei löydy KORKO-tuotetta")."!</font><br>";
  $tee = "";
}

$kasittelykulu = str_replace(',', '.', $kasittelykulu);

if (($yhtiorow["kasittelykulu_tuotenumero"] != '') and (!is_numeric($kasittelykulu) or $kasittelykulu < 0)) {
  echo "<font class='error'>".t("Käsittelykulu summa on syötettävä")."!</font><br>";
  $tee = "";
}

if ($tee == 'LAHETA') {

  //tulostetaan korkoerittely
  require 'tulosta_korkoerittely.inc';

  //tehdään itse korkolasku (tämä skripti kutsuu tilaus-valmis ohjelmaa joka tekee melekin kaiken meille)
  require 'tee_korkolasku.inc';

  //päivitetään laskut lähetetyiksi
  $query = "UPDATE lasku
            SET olmapvm = now()
            WHERE tunnus in ($xquery)
            and yhtio    = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  $tee = "KOROTA";
}

if ($tee == "ALOITAKOROTUS") {

  $korkolisa = "";
  if ($korkosumma > 0) {
    $korkosumma = str_replace(',', '.', $korkosumma);
    $korkolisa = " and korkosumma > $korkosumma ";
  }

  $minimisumma = (float) $minimisumma;

  $query = "SELECT GROUP_CONCAT(distinct concat('\'',ovttunnus,'\'')) konsrernyhtiot
            FROM yhtio
            WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
  $result = pupe_query($query);

  $konslisa = "";
  if (mysql_num_rows($result) > 0) {
    $konsrow = mysql_fetch_assoc($result);
    $konslisa = " and lasku.ovttunnus not in ($konsrow[konsrernyhtiot])";
  }

  $asiakaslisa = "";
  if ($syot_ytunnus != '') {
    $asiakaslisa = " and asiakas.ytunnus >= '$syot_ytunnus' ";
  }

  if (isset($vienti) and is_array($vienti)) {
    array_walk($vienti, 'mysql_real_escape_string');
    $asiakaslisa .= " and asiakas.vienti in ('".implode("','", $vienti)."') ";
  }

  $query = "SELECT
            GROUP_CONCAT(distinct lasku.tunnus) korotettavat,
            round(sum(lasku.viikorkopros * tiliointi.summa * -1 * (to_days(tiliointi.tapvm)-to_days(lasku.erpcm)) / 36500),2) korkosumma
            FROM lasku
            JOIN (  SELECT lasku.tunnus,
                to_days(tiliointi.tapvm) - to_days(lasku.erpcm) ika,
                round(lasku.viikorkopros * tiliointi.summa * -1 * (to_days(tiliointi.tapvm)-to_days(lasku.erpcm)) / 36500,2) korkosumma2,
                maksuehto.jv
                FROM lasku use index (yhtio_tila_mapvm)
                JOIN asiakas
                  ON (lasku.yhtio = asiakas.yhtio
                    AND lasku.liitostunnus  = asiakas.tunnus $asiakaslisa)
                JOIN tiliointi use index (tositerivit_index)
                  ON (tiliointi.yhtio = lasku.yhtio
                    AND tiliointi.ltunnus   = lasku.tunnus
                    AND tiliointi.tilino    in ('$yhtiorow[myyntisaamiset]', '$yhtiorow[factoringsaamiset]')
                    AND tiliointi.tapvm     > lasku.erpcm and tiliointi.korjattu = '')
                LEFT JOIN maksuehto
                  ON (maksuehto.yhtio = lasku.yhtio
                    AND maksuehto.tunnus    = lasku.maksuehto)
                WHERE lasku.yhtio           = '$kukarow[yhtio]'
                AND lasku.tila              = 'U'
                AND lasku.mapvm   >='$vva-$kka-$ppa'
                AND lasku.mapvm   <='$vvl-$kkl-$ppl'
                AND lasku.summa            != 0
                AND lasku.olmapvm           = '0000-00-00'
                $konslisa
                HAVING ika > $min_myoh AND abs(korkosumma2) > abs($minimisumma) AND (maksuehto.jv is null or maksuehto.jv = '')
                ORDER BY asiakas.ytunnus) AS laskut
            JOIN asiakas ON (lasku.yhtio = asiakas.yhtio
              AND lasku.liitostunnus        = asiakas.tunnus $asiakaslisa)
            JOIN tiliointi use index (tositerivit_index)
              ON (tiliointi.yhtio = lasku.yhtio
                AND tiliointi.ltunnus       = lasku.tunnus
                AND tiliointi.tilino        in ('$yhtiorow[myyntisaamiset]', '$yhtiorow[factoringsaamiset]')
                AND tiliointi.tapvm         > lasku.erpcm AND tiliointi.korjattu = '')
            WHERE lasku.tunnus              = laskut.tunnus
            $konslisa
            GROUP BY asiakas.ytunnus, asiakas.nimi, asiakas.nimitark, asiakas.osoite, asiakas.postino, asiakas.postitp
            HAVING korkosumma > 0 $korkolisa
            ORDER BY asiakas.ytunnus";
  $result = pupe_query($query);

  $korotettavat = array();

  if (mysql_num_rows($result) > 0) {
    while ($karhuttavarow = mysql_fetch_assoc($result)) {
      $korotettavat[] = $karhuttavarow["korotettavat"];
    }
    $tee = "KOROTA";
  }
  else {
    echo "<font class='message'>".t("Ei korkolaskutettavia asiakkaita")."!</font><br><br>";
    $tee = "";
  }
}

if ($tee == 'KOROTA' and $korotettavat[0] == "") {
  echo "<font class='message'>".t("Kaikki asiakkaat korkolaskutettu")."!</font><br><br>";
  $tee = "";
}

if ($tee == "KOROTA") {

  $query = "SELECT lasku.liitostunnus, tiliointi.summa*-1 summa, lasku.tunnus,
            lasku.erpcm, lasku.laskunro, tiliointi.tapvm, lasku.tapvm latapvm, if(count(*) > 1, 'useita', lasku.mapvm) mapvm, lasku.viikorkopros,
            if (count(*) > 1, 'useita', to_days(tiliointi.tapvm) - to_days(lasku.erpcm)) AS ika,
            sum(round(lasku.viikorkopros * tiliointi.summa * -1 * (to_days(tiliointi.tapvm)-to_days(lasku.erpcm)) / 36500,2)) AS korkosumma
            FROM lasku
            JOIN tiliointi use index (tositerivit_index)
              ON (tiliointi.yhtio = lasku.yhtio
                AND tiliointi.ltunnus = lasku.tunnus
                AND tiliointi.tilino  IN ('$yhtiorow[myyntisaamiset]', '$yhtiorow[factoringsaamiset]')
                AND tiliointi.tapvm   > lasku.erpcm AND tiliointi.korjattu = '')
            LEFT JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio
              AND maksuehto.tunnus    = lasku.maksuehto)
            WHERE lasku.yhtio         = '$kukarow[yhtio]'
            AND lasku.tunnus          IN ($korotettavat[0])
            GROUP BY lasku.tunnus
            ORDER BY lasku.erpcm";
  $result = pupe_query($query);

  //Poistetaan arraysta käytetyt tunnukset
  unset($korotettavat[0]);

  //otetaan asiakastiedot ekalta laskulta
  $asiakastiedot = mysql_fetch_assoc($result);

  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$asiakastiedot[liitostunnus]'";
  $asiakasresult = pupe_query($query);
  $asiakastiedot = mysql_fetch_assoc($asiakasresult);

  //ja kelataan akuun
  mysql_data_seek($result, 0);

  echo "<table>
  <tr><th>".t("Ytunnus")."</th><td>$asiakastiedot[ytunnus]</td></tr>
  <tr><th>".t("Nimi")."</th><td>$asiakastiedot[nimi]</td></tr>
  <tr><th>".t("Nimitark")."</th><td>$asiakastiedot[nimitark]</td></tr>
  <tr><th>".t("Osoite")."</th><td>$asiakastiedot[osoite]</td></tr>
  <tr><th>".t("Postinumero")."</th><td>$asiakastiedot[postino] $asiakastiedot[postitp]</td></tr>";
  echo "</table><br>";

  echo "<table>";
  echo "<tr>";
  echo "<td class='back'><input type='button' onclick='javascript:document.lahetaformi.submit();' value='".t("Tee korkolasku")."'></td>";
  echo "<td class='back'><input type='button' onclick='javascript:document.ohitaformi.submit();' value='".t("Ohita")."'></td>";
  echo "</tr>";
  echo "</table><br>";

  echo "<form name='lahetaformi' method='post'>";
  echo "<table><tr>";
  echo "<th>".t("Laskunpvm")."</th>";
  echo "<th>".t("Laskunro")."</th>";
  echo "<th>".t("Summa")."</th>";
  echo "<th>".t("Eräpäivä")."</th>";
  echo "<th>".t("Maksupäivä")."</th>";
  echo "<th>".t("Maksettu myöh").".</th>";
  echo "<th>".t("Viikorko")."%</th>";
  echo "<th>".t("Korko")."</th>";
  echo "<th>".t("Lisätään korkolaskuun")."</th></tr>";

  $summmmma  = 0;
  $summmmma2 = 0;
  $edlasku = 0;

  while ($lasku=mysql_fetch_assoc($result)) {

    echo "<tr><td>";
    if ($kukarow['taso'] < 2) {
      echo $lasku["latapvm"];
    }
    else {
      echo "<a href = '../muutosite.php?tee=E&tunnus=$lasku[tunnus]'>$lasku[latapvm]</a>";
    }
    echo "</td><td>";
    echo "<a href = '../tilauskasittely/tulostakopio.php?toim=LASKU&laskunro=$lasku[laskunro]'>$lasku[laskunro]";
    echo "</td><td align='right'>";
    echo $lasku['summa'];
    echo "</td><td>";
    echo tv1dateconv($lasku['erpcm']);
    echo "</td><td>";
    echo tv1dateconv($lasku['mapvm']);
    echo "</td><td>";
    echo $lasku['ika'];
    echo "</td><td>";
    echo $lasku['viikorkopros'];
    echo "</td><td align='right'>";
    echo $lasku['korkosumma'];
    echo "</td><td>";

    if ($lasku["laskunro"] != $edlasku) {
      $chk = $lasku['korkosumma'] < 0 ? '' : ' checked';
      echo "<input type='checkbox' name = 'lasku_tunnus[]' value = '$lasku[tunnus]'{$chk}>";
    }

    $edlasku = $lasku["laskunro"];

    echo "</td></tr>\n";

    $summmmma  += $lasku['summa'];
    $summmmma2 += $lasku['korkosumma'];
  }

  echo "<th colspan='2'>".t("Yhteensä")."</th>";
  echo "<th style='text-align:right;'>$summmmma</th>";
  echo "<th colspan='4'></th>";
  echo "<th style='text-align:right;'>$summmmma2</th>";
  echo "<th></th></tr>";



  echo "</table><br>";

  echo "<table>";
  echo "<tr>";

  echo "<input name='tee' type='hidden' value='LAHETA'>";
  echo "<input name='kasittelykulu' type='hidden' value='$kasittelykulu'>";
  echo "<input name='kirjoitin' type='hidden' value='$kirjoitin'>";

  foreach ($korotettavat as $tunnukset) {
    echo "\n<input type='hidden' name='korotettavat[]' value='$tunnukset'>";
  }

  echo "<input name='korkosumma' type='hidden' value='$korkosumma'>";
  echo "<input name='vmehto' type='hidden' value='$vmehto'>";
  echo "<input name='yhteyshenkilo' type='hidden' value='$yhteyshenkilo'>";
  echo "  <input type='hidden' name='ppa' value='$ppa'>
      <input type='hidden' name='kka' value='$kka'>
      <input type='hidden' name='vva' value='$vva'>
      <input type='hidden' name='ppl' value='$ppl'>
      <input type='hidden' name='kkl' value='$kkl'>
      <input type='hidden' name='vvl' value='$vvl'>";

  echo "<td class='back'><input type='submit' value='".t("Tee korkolasku")."'></td>";
  echo "</form>";

  echo "<form  name='ohitaformi' method='post'>";
  echo "<input name='tee' type='hidden' value='KOROTA'>";

  foreach ($korotettavat as $tunnukset) {
    echo "\n<input type='hidden' name='korotettavat[]' value='$tunnukset'>";
  }

  echo "<input name='kasittelykulu' type='hidden' value='$kasittelykulu'>";
  echo "<input name='kirjoitin' type='hidden' value='$kirjoitin'>";
  echo "<input name='korkosumma' type='hidden' value='$korkosumma'>";
  echo "<input name='vmehto' type='hidden' value='$vmehto'>";
  echo "<input name='yhteyshenkilo' type='hidden' value='$yhteyshenkilo'>";
  echo "  <input type='hidden' name='ppa' value='$ppa'>
      <input type='hidden' name='kka' value='$kka'>
      <input type='hidden' name='vva' value='$vva'>
      <input type='hidden' name='ppl' value='$ppl'>
      <input type='hidden' name='kkl' value='$kkl'>
      <input type='hidden' name='vvl' value='$vvl'>";
  echo "<td class='back'><input type='submit' value='".t("Ohita")."'></td>";

  echo "</tr></form>";
  echo "</table>";
}

if ($tee == "") {

  echo "<form method='post'>";
  echo "<input name='tee' type='hidden' value='ALOITAKOROTUS'>";
  echo t("Syötä ytunnus jos haluat lähettää korkolaskun tietylle asiakkaalle").".<br>";
  echo t("Jätä kenttä tyhjäksi jos haluat aloittaa ensimmäisestä asiakkaasta").".<br>";
  echo t("Minimi korkosumma on summa euroissa, jonka yli korkolaskun loppusumman on oltava, että sitä edes ehdotetaan. (tyhjä=kaikki laskut)")."<br>";
  echo t("Korkoa lasketaan laskuille jotka on maksettu alku- ja loppupäivämäärän välillä").".<br><br>";
  echo "<table>";

  if (!isset($kka))
    $kka = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  if (!isset($vva))
    $vva = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  if (!isset($ppa))
    $ppa = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

  if (!isset($kkl))
    $kkl = date("m");
  if (!isset($vvl))
    $vvl = date("Y");
  if (!isset($ppl))
    $ppl = date("d");

  $query = "SELECT *
            FROM maksuehto
            WHERE yhtio    = '$kukarow[yhtio]'
            and kaytossa   = ''
            and jaksotettu = ''
            ORDER BY jarjestys, teksti";
  $vresult = pupe_query($query);

  $ulos = "<select name='vmehto'>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel = "";
    if ($vmehto == $vrow["tunnus"]) $sel = "SELECTED";

    $ulos .= "<option value = '$vrow[tunnus]' $sel>".t_tunnus_avainsanat($vrow, "teksti", "MAKSUEHTOKV", $kieli)."</option>";
  }
  $ulos .= "</select>";

  echo "<tr><th>".t("Alkupäivämäärä").":</th>
      <td><input type='text' name='ppa' value='$ppa' size='3'></td>
      <td><input type='text' name='kka' value='$kka' size='3'></td>
      <td><input type='text' name='vva' value='$vva' size='5'></td>
      </tr><tr><th>".t("Loppupäivämäärä").":</th>
      <td><input type='text' name='ppl' value='$ppl' size='3'></td>
      <td><input type='text' name='kkl' value='$kkl' size='3'></td>
      <td><input type='text' name='vvl' value='$vvl' size='5'></td>";

  echo "<tr><th>".t("Korkolaskun maksuehto").":</th>";
  echo "<td colspan='3'>$ulos</td></tr>";

  $vienti_sel = array();

  if (isset($vienti) and is_array($vienti)) {
    foreach ($vienti as $v) {
      $vienti_sel[$v] = ' selected';
    }
  }

  echo "<tr><th>", t("Vienti"), ":</th>";
  echo "<td colspan='3'><select name='vienti[]' multiple size='3'>";
  echo "<option value=''{$vienti_sel['']}>", t("Kotimaa"), "</option>";
  echo "<option value='E'{$vienti_sel['E']}>", t("Vienti EU"), "</option>";
  echo "<option value='K'{$vienti_sel['K']}>", t("Vienti ei-EU"), "</option>";
  echo "</select></td></tr>";

  echo "<tr><th>".t("Minimi korkosumma").":</th>";
  echo "<td colspan='3'><input type='text' name='korkosumma' value='$korkosumma'></td></tr>";

  echo "<tr><th>".t("Minimi yksittäisen laskun korkosumma").":</th>";
  echo "<td colspan='3'><input type='text' name='minimisumma' value='$minimisumma'></td></tr>";

  if ($yhtiorow["kasittelykulu_tuotenumero"] != '') {

    if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
      $valuutta = $laskurow["valkoodi"];
    }
    else {
      $valuutta = $yhtiorow["valkoodi"];
    }

    echo "<tr><th>".t("Käsittelykulu").":</th>";
    echo "<td colspan='3'><input type='text' name='kasittelykulu' value='$kasittelykulu'> $valuutta</td></tr>";
  }

  echo "<tr><th>", t("Valitse tulostin"), "</th><td colspan='3'><select name='kirjoitin'>";

  $query = "SELECT *
            FROM kirjoittimet
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND komento != 'edi'
            ORDER BY kirjoitin";
  $kires = pupe_query($query);

  while ($kirow = mysql_fetch_assoc($kires)) {
    if ($kirow['tunnus'] == $kukarow["kirjoitin"]) $select = ' selected';
    else $select = '';
    echo "<option value='{$kirow['tunnus']}'{$select}>{$kirow['kirjoitin']}</option>";
  }

  echo "</select></td></tr>";

  $apuqu = "SELECT kuka, nimi, puhno, eposti, tunnus
            FROM kuka
            WHERE yhtio     = '$kukarow[yhtio]'
            AND aktiivinen  = 1
            AND nimi       != ''
            AND puhno      != ''
            AND eposti     != ''
            AND extranet    = ''";
  $meapu = pupe_query($apuqu);

  echo "<tr><th>".t("Yhteyshenkilö").":</th>";
  echo "<td colspan='3'><select name='yhteyshenkilo'>";

  while ($row = mysql_fetch_assoc($meapu)) {
    $sel = "";

    if ($row['kuka'] == $kukarow['kuka']) {
      $sel = 'selected';
    }

    echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
  }

  echo "</select></td></tr>";


  echo "<tr><th>".t("Ytunnus").":</th>";
  echo "<td colspan='3'>";
  echo "<input name='syot_ytunnus' type='text' value='$syot_ytunnus'></td>";
  echo "<td class='back'><input type='submit' value='".t("Aloita")."'></td>";
  echo "</form></tr>";
  echo "</table>";
}

require "inc/footer.inc";
