<?php

require "../inc/parametrit.inc";

echo "<font class='head'>", t("Toimittajan avoimet tilausrivit"), ":</font><hr>";

if (!isset($ytunnus)) $ytunnus = '';
if (!isset($nayta_rivit)) $nayta_rivit = '';
if (!isset($tee)) $tee = '';
if (!isset($otunnus)) $otunnus = '';
if (!isset($ojarj)) $ojarj = '';

if ($ytunnus != '') {
  require "inc/kevyt_toimittajahaku.inc";
}

$select = "";
if ($nayta_rivit == 'vahvistamattomat') {
  $select = "selected";
}
// Näytetään muuten vaan sopivia tilauksia
echo "<form method = 'post'>";
echo "<br><table>";
echo "<tr><th>", t("Valitse toimittaja"), ":</th><td style='text-align: top;'><input type='text' size='10' name='ytunnus' value='{$ytunnus}'> ";
echo "<select name='nayta_rivit'>";
echo "<option value=''>", t("Kaikki avoimet rivit"), "</option>";
echo "<option value='vahvistamattomat' {$select}>", t("Vain vahvistamattomia rivejä"), "</option> ";
echo "<input type='submit' class='hae_btn' value = '".t("Etsi")."'></td>";
echo "</tr>";

echo "<tr><th>", t("Näytä kaikki"), ":</th>";
echo "<td><input type='submit' name='nayta_rivit' value='", t("Näytä kaikkien toimittajien vahvistamattomat rivit"), "'></td></tr>";

if ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikat_res) > 0) {

  $sel = (isset($toimipaikka) and $toimipaikka == '0') ? "selected" : "";

  echo "<tr>";
  echo "<th>", t("Toimipaikka"), "</th>";
  echo "<td>";
  echo "<select name='toimipaikka'>";
  echo "<option value='kaikki'>", t("Kaikki toimipaikat"), "</option>";
  echo "<option value='0' {$sel}>", t("Ei toimipaikkaa"), "</option>";

  while ($toimipaikat_row = mysql_fetch_assoc($toimipaikat_res)) {

    $sel = '';

    if (isset($toimipaikka)) {
      if ($toimipaikka == $toimipaikat_row['tunnus']) {
        $sel = ' selected';
        $toimipaikka = $toimipaikat_row['tunnus'];
      }
    }
    else {
      if ($kukarow['toimipaikka'] == $toimipaikat_row['tunnus']) {
        $sel = ' selected';
        $toimipaikka = $toimipaikat_row['tunnus'];
      }
    }

    echo "<option value='{$toimipaikat_row['tunnus']}'{$sel}>{$toimipaikat_row['nimi']}</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";
}

echo "</table>";
echo "</form><br>";

if ($ytunnus == "" and isset($nayta_rivit) and $nayta_rivit != "") {

  $toimipaikkalisa = "";

  if (isset($toimipaikka)) {
    if ($toimipaikka != 'kaikki') {
      $toimipaikkalisa = " AND lasku.vanhatunnus = '{$toimipaikka}' ";
    }
  }

  $query = "SELECT tilausrivi.otunnus, lasku.nimi, lasku.ytunnus
            FROM tilausrivi
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus {$toimipaikkalisa})
            WHERE tilausrivi.yhtio    = '{$kukarow['yhtio']}'
            AND tilausrivi.toimitettu = ''
            AND tilausrivi.tyyppi     = 'O'
            AND tilausrivi.kpl        = 0
            AND tilausrivi.jaksotettu = 0
            GROUP BY tilausrivi.otunnus
            ORDER BY lasku.nimi";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<table>";
    echo "<tr><th>", t("Toimittaja"), "</th><th>", t("Tilausnumero"), "</th></tr>";

    while ($row = mysql_fetch_assoc($result)) {
      echo "<tr><td>{$row['nimi']}</td><td><a href='?ytunnus={$row['ytunnus']}&otunnus={$row['otunnus']}&toimittajaid={$toimittajaid}&ojarj={$apu}&toimipaikka=$toimipaikka'>{$row['otunnus']}</a></td></tr>";
    }

    echo "</table>";
  }
}

if ($ytunnus != '') {

  $toimipaikkalisa = "";

  if (isset($toimipaikka)) {
    if ($toimipaikka != 'kaikki') {
      $toimipaikkalisa = " AND lasku.vanhatunnus = '{$toimipaikka}' ";
    }
  }

  $query = "SELECT max(lasku.tunnus) maxtunnus, GROUP_CONCAT(distinct lasku.tunnus SEPARATOR ', ') tunnukset
            FROM lasku
            JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.uusiotunnus = 0 and tilausrivi.tyyppi = 'O')
            WHERE lasku.yhtio      = '{$kukarow['yhtio']}'
            and lasku.liitostunnus = '{$toimittajaid}'
            and lasku.tila         = 'O'
            and lasku.alatila      = 'A'
            {$toimipaikkalisa}
            HAVING tunnukset IS NOT NULL";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $tunnusrow = mysql_fetch_assoc($result);

    //Onko tietty tilaus valittu?
    if ($otunnus != "") {
      $tilaus_otunnukset = $otunnus;
    }
    else {
      $tilaus_otunnukset = $tunnusrow["tunnukset"];
    }

    $query = "SELECT *
              FROM lasku
              WHERE tunnus = '{$tunnusrow['maxtunnus']}'";
    $aresult = pupe_query($query);
    $laskurow = mysql_fetch_assoc($aresult);

    $keikka = (int) $keikka;

    if ($tee == "KAIKKIPVM") {
      //Päivitetään rivien toimituspäivät

      if ($keikka > 0) {
        $mitkakaikkipvm = " and uusiotunnus = {$keikka} and tyyppi = 'O' and kpl = 0 ";
      }
      else {
        $mitkakaikkipvm = " and otunnus in ({$tilaus_otunnukset}) and uusiotunnus=0 ";
      }

      $query = "UPDATE tilausrivi
                SET toimaika = '{$toimvv}-{$toimkk}-{$toimpp}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                {$mitkakaikkipvm}";
      $result = pupe_query($query);
    }

    if ($tee == "PAIVITARIVI") {
      foreach ($toimaikarivi as $tunnus => $toimaika) {
        if (strpos($toimaika, ".") !== false) {
          $_aika = explode(".", $toimaika);

          if (count($_aika) == 3) {
            $toimaika = "{$_aika["2"]}-{$_aika["1"]}-{$_aika["0"]}";
          }
        }

        $query = "UPDATE tilausrivi SET toimaika = '{$toimaika}' WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tunnus}' and tyyppi = 'O'";
        $result = pupe_query($query);
      }

      if ($keikka > 0) {
        $query = "UPDATE tilausrivi SET  jaksotettu = 0 WHERE yhtio = '{$kukarow['yhtio']}' and uusiotunnus = {$keikka} and tyyppi = 'O' and kpl = 0";
      }
      else {
        $query = "UPDATE tilausrivi SET  jaksotettu = 0 WHERE yhtio = '{$kukarow['yhtio']}' and otunnus in ({$t_otunnuksia}) and tyyppi = 'O' and uusiotunnus = 0";
      }

      $result = pupe_query($query);

      if (count($vahvistetturivi) > 0) {
        foreach ($vahvistetturivi as $tunnus => $vahvistettu) {
          $query = "UPDATE tilausrivi SET  jaksotettu = '{$vahvistettu}' where yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tunnus}' and tyyppi = 'O'";
          $result = pupe_query($query);
        }
      }

      if (isset($poista)) {
        foreach ($poista as $tunnus => $kala) {
          $query = "UPDATE tilausrivi SET tyyppi = 'D' where yhtio = '{$kukarow['yhtio']}' and tunnus = '{$tunnus}' and tyyppi = 'O' and uusiotunnus = 0";
          $result = pupe_query($query);
        }
      }
    }
  }
}

if ($tee == "TULOSTAPDF") {
  if ($tulosta_exceliin == 'EXCEL') {
    $komento["Ostotilaus"] = "email";
    require_once 'tulosta_vahvistamattomista_ostoriveista_excel.inc';

    $excel = new vahvistamattomat_ostorivit_excel();
    $excel->set_kieli($kieli);
    $excel->set_yhtiorow($yhtiorow);
    $excel->set_toimittaja($laskurow);
    $excel->set_rivit(get_vahvistamattomat_rivit($tilaus_otunnukset, $toimittajaid, $laskurow, $kieli));

    $excel_tiedosto = $excel->generoi();

    $params = array(
      "to"     => $kukarow['eposti'],
      "subject"   => $yhtiorow['nimi'] . " - " . t('Ostotilaus' , $kieli),
      "ctype"     => "html",
      "body"     => t('Ostotilaus raportti liitteenä' , $kieli),
      "attachements" => array(
        0 => array(
          "filename"     => "/tmp/$excel_tiedosto",
          "newfilename"   => t('Ostotilaus' , $kieli) . ".xlsx",
          "ctype"       => "excel"),
      )
    );
    pupesoft_sahkoposti($params);
    echo t("Vahvistamattomat rivit lähetetty sähköpostiin")."...<br><br>";
  }
  else {
    $komento["Ostotilaus"] = "email";
    $vahvistamattomat_rivit = get_vahvistamattomat_rivit($tilaus_otunnukset, $toimittajaid, $laskurow, $kieli);
    require "tulosta_vahvistamattomista_ostoriveista.inc";
  }
}

if (isset($laskurow)) {
  echo "<table width='720'>";

  echo "<tr><th>", t("Ytunnus"), "</th><th colspan='2'>", t("Toimittaja"), "</th></tr>";
  echo "<tr><td>{$laskurow['ytunnus']}</td>
    <td colspan='2'>{$laskurow['nimi']} {$laskurow['nimitark']}<br> {$laskurow['osoite']}<br> {$laskurow['postino']} {$laskurow['postitp']}</td></tr>";

  echo "<tr><th>", t("Tila"), "</th><th>", t("Toimaika"), "</th><th>", t("Tilausnumerot"), "</th><td class='back'></td></tr>";
  echo "<tr><td>{$laskurow['tila']}</td><td>{$laskurow['toimaika']}</td><td>{$tunnusrow['tunnukset']}</td></tr>";
  echo "</table><br>";

  echo "  <form method='post'>
      <input type='hidden' name='ytunnus' value = '{$ytunnus}'>
      <input type='hidden' name='toimittajaid' value = '{$toimittajaid}'>
      <table>";

  echo "<tr><th>", t("Näytä tilaukset"), "</th><td>";
  echo "<select name='otunnus' onchange='submit();'>";
  echo "<option value=''>", t("Näytä kaikki"), "</option>";

  $tunnukset = explode(',', $tunnusrow["tunnukset"]);

  foreach ($tunnukset as $tunnus) {
    $sel = '';
    if ($otunnus == $tunnus) {
      $sel = "selected";
    }
    echo "<option value='{$tunnus}' {$sel}>{$tunnus}</option>";
  }
  echo "</select></td></tr>";

  $query = "SELECT lasku.laskunro, lasku.tunnus
            FROM lasku
            JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'O' and kpl = 0 and uusiotunnus > 0)
            WHERE lasku.yhtio      = '{$kukarow['yhtio']}'
            and lasku.liitostunnus = '{$toimittajaid}'
            and lasku.tila         = 'K'
            and lasku.alatila      = ''
            GROUP BY 1
            ORDER BY 1";
  $result = pupe_query($query);

  echo "<tr><th>", t("Näytä Saapuminen"), "</th>";
  echo "<td><select name='keikka' onchange='submit();'>";
  echo "<option value=''>", t("Näytä kaikki"), "</option>";

  if (mysql_num_rows($result) > 0) {
    while ($keikkaselrow = mysql_fetch_assoc($result)) {
      $selkeikka = '';
      if ($keikka == $keikkaselrow['tunnus'] and $otunnus == "") {
        $selkeikka = "selected";
      }
      echo "<option value='{$keikkaselrow['tunnus']}' {$selkeikka}>{$keikkaselrow['laskunro']}</option>";
    }
  }

  echo "</select></td></tr>";

  echo "<tr><th>", t("Näytä"), ":</th>";

  $select = "";
  if ($nayta_rivit == 'vahvistamattomat') {
    $select = "selected";
  }

  echo "<td><select name='nayta_rivit' onchange='submit();'>";
  echo "<option value=''>", t("Kaikki avoimet rivit"), "</option>";
  echo "<option value='vahvistamattomat' {$select}>", t("Vain vahvistamattomia rivejä"), "</option>";
  echo "</select></td></tr>";

  if ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikat_res) > 0) {

    $sel = (isset($toimipaikka) and $toimipaikka == '0') ? "selected" : "";

    echo "<tr>";
    echo "<th>", t("Toimipaikka"), "</th>";
    echo "<td>";
    echo "<select name='toimipaikka' onchange='submit();'>";
    echo "<option value='kaikki'>", t("Kaikki toimipaikat"), "</option>";
    echo "<option value='0' {$sel}>", t("Ei toimipaikkaa"), "</option>";

    while ($toimipaikat_row = mysql_fetch_assoc($toimipaikat_res)) {

      $sel = '';

      if (isset($toimipaikka) and $toimipaikka == $toimipaikat_row['tunnus']) {
        $sel = ' selected';
        $toimipaikka = $toimipaikat_row['tunnus'];
      }

      echo "<option value='{$toimipaikat_row['tunnus']}'{$sel}>{$toimipaikat_row['nimi']}</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }

  echo "</table></form><br>";

  echo "  <table>
      <form method='post'>
      <input type='hidden' name='ytunnus' value = '{$ytunnus}'>
      <input type='hidden' name='toimittajaid' value = '{$toimittajaid}'>
      <input type='hidden' name='otunnus' value = '{$otunnus}'>
      <input type='hidden' name='keikka' value = '{$keikka}'>
      <input type='hidden' name='tee' value = 'KAIKKIPVM'>";

  $toimpp = date("j");
  $toimkk = date("n");
  $toimvv = date("Y");

  echo "<tr><th>", t("Päivitä rivien toimitusajat"), ": </th><td valign='middle'>
      <input type='text' name='toimpp' value='{$toimpp}' size='3'>
      <input type='text' name='toimkk' value='{$toimkk}' size='3'>
      <input type='text' name='toimvv' value='{$toimvv}' size='6'></td>
      <td><input type='submit' value='", t("Päivitä"), "'></form></td></tr></table><br>";

  echo "  <table>
      <form method='post'>
      <input type='hidden' name='ytunnus' value = '{$ytunnus}'>
      <input type='hidden' name='toimittajaid' value = '{$toimittajaid}'>
      <input type='hidden' name='otunnus' value = '{$otunnus}'>
      <input type='hidden' name='keikka' value = '{$keikka}'>
      <input type='hidden' name='tee' value = 'TULOSTAPDF'>";

  echo "<tr><th>".t('Tiedostomuoto').":</th>";

  $sel = "";
  if (isset($tulosta_exceliin) and $tulosta_exceliin != "") $sel = "SELECTED";

  echo "<td><select name='tulosta_exceliin'>";
  echo "<option value=''>", t("PDF"), "</option>";
  echo "<option value='EXCEL' $sel>", t("Excel"), "</option>";
  echo "</select></td>";

  echo "<tr><th>", t("Lähetä vahvistamattomat rivit sähköpostiin"), ": </th>
      <td><input type='submit' value='", t("Lähetä"), "'></form></td></tr></table><br>";


  //Haetaan kaikki tilausrivit
  echo "<b>", t("Tilausrivit"), ":</b><hr>";

  //Listataan tilauksessa olevat tuotteet
  $jarjestys = "tilausrivi.otunnus";

  if (strlen($ojarj) > 0) {
    $jarjestys = $ojarj;
  }

  $query_ale_lisa = generoi_alekentta('O');

  $ale_query_select_lisa = generoi_alekentta_select('erikseen', 'O');

  if ((int) $keikka > 0 and $otunnus == "") {
    $query = "SELECT tilausrivi.otunnus, tilausrivi.tuoteno, tuotteen_toimittajat.toim_tuoteno, tilausrivi.nimitys,
              concat_ws('/',tilkpl,round(tilkpl*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tilattu sis/ulk',
              hinta, {$ale_query_select_lisa} round((varattu+jt)*hinta*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa},'{$yhtiorow['hintapyoristys']}') rivihinta,
              toimaika, tilausrivi.jaksotettu as vahvistettu, tilausrivi.uusiotunnus, tilausrivi.tunnus
              FROM tilausrivi
              LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
              LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='{$toimittajaid}'
              WHERE tilausrivi.uusiotunnus = {$keikka}
              and tilausrivi.yhtio         = '{$kukarow['yhtio']}'
              and tilausrivi.kpl           = 0
              and tilausrivi.tyyppi        = 'O'
              ORDER BY {$jarjestys}";
  }
  else {
    $query = "SELECT tilausrivi.otunnus, tilausrivi.tuoteno, tuotteen_toimittajat.toim_tuoteno, tilausrivi.nimitys,
              concat_ws('/',tilkpl,round(tilkpl*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tilattu sis/ulk',
              hinta, {$ale_query_select_lisa} round((varattu+jt)*hinta*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa},'{$yhtiorow['hintapyoristys']}') rivihinta,
              toimaika, tilausrivi.jaksotettu as vahvistettu, tilausrivi.uusiotunnus, tilausrivi.tunnus
              FROM tilausrivi
              LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
              LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='{$toimittajaid}'
              WHERE otunnus in ({$tilaus_otunnukset})
              and tilausrivi.yhtio='{$kukarow['yhtio']}'
              and tilausrivi.uusiotunnus=0
              and tilausrivi.tyyppi='O'
              ORDER BY {$jarjestys}";
  }
  $presult = pupe_query($query);

  $rivienmaara = mysql_num_rows($presult);

  echo "<table><tr>";

  $miinus = 2;

  for ($i = 0; $i < mysql_num_fields($presult)-$miinus; $i++) {
    $apu = $i + 1;
    echo "<th align='left'><a href = '?ytunnus={$ytunnus}&otunnus={$otunnus}&toimittajaid={$toimittajaid}&ojarj={$apu}&toimipaikka=$toimipaikka'>", t(mysql_field_name($presult, $i)), "</a></th>";
  }

  echo "<th align='left'>", t("poista"), "</th>";

  echo "</tr>";

  $yhteensa = 0;

  echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
      <!--

      function toggleAll(toggleBox) {

        var currForm = toggleBox.form;
        var isChecked = toggleBox.checked;
        var nimi = toggleBox.name.substring(0,3);

        for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
          if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,3) == nimi) {
            currForm.elements[elementIdx].checked = isChecked;
          }
        }
      }

      //-->
      </script>";

  echo "  <form method='post'>
      <input type='hidden' name='ytunnus' value = '{$ytunnus}'>
      <input type='hidden' name='toimittajaid' value = '{$toimittajaid}'>
      <input type='hidden' name='otunnus' value = '{$otunnus}'>
      <input type='hidden' name='t_otunnuksia' value = '", trim($tilaus_otunnukset), "'>
      <input type='hidden' name='keikka' value = '{$keikka}'>
      <input type='hidden' name='tee' value = 'PAIVITARIVI'>";

  while ($prow = mysql_fetch_array($presult)) {

    if ($nayta_rivit == 'vahvistamattomat' and $prow["vahvistettu"] == 1) {
      continue;
    }

    $yhteensa += $prow["rivihinta"];

    echo "<tr class='aktiivi'>";

    for ($i=0; $i < mysql_num_fields($presult)-$miinus; $i++) {
      if (mysql_field_name($presult, $i) == 'tuoteno') {
        echo "<td><a href='../tuote.php?tee=Z&tuoteno=", urlencode($prow[$i]), "'>{$prow[$i]}</a></td>";
      }
      elseif (mysql_field_name($presult, $i) == 'toimaika') {
        echo "<td align='right'><input type='text' name='toimaikarivi[{$prow['tunnus']}]' value='{$prow[$i]}' size='10'></td>";
      }
      elseif (mysql_field_name($presult, $i) == 'vahvistettu') {
        $chekkis = "";
        if ($prow['vahvistettu'] == 1) {
          $chekkis = 'CHECKED';
        }
        echo "<td><input type='checkbox' name='vahvistetturivi[{$prow['tunnus']}]' value='1' {$chekkis}></td>";
      }
      else {
        echo "<td align='right'>{$prow[$i]}</td>";
      }
    }
    echo "<td>";
    if ($prow["uusiotunnus"] == 0) {
      echo "<input type='checkbox' name='poista[{$prow['tunnus']}]' value='Poista'>";
    }
    echo "</td>";
    echo "</tr>";
  }
  echo "<tr>
      <td class='back' colspan='5' align='right'></td>
      <td colspan='3' class='spec'>", t("Tilauksen arvo"), ":</td>
      <td align='right' class='spec'>", sprintf("%.2f", $yhteensa), "</td>
      <td align='right'>", t("Ruksaa kaikki"), ":</td>
      <td><input type='checkbox' name='vahvist' onclick='toggleAll(this);'></td>
      <td><input type='checkbox' name='poist' onclick='toggleAll(this);'></td>
    </tr>";
  echo "<tr>
      <td class='back' colspan='8'></td>
      <td class='back' colspan='2' align='right'><input type='submit' value='", t("Päivitä tiedot"), "'></td>
    </tr>";

  echo "</form></table>";
}

function get_vahvistamattomat_rivit($tilaus_otunnukset, $toimittajaid, $laskurow, $kieli) {
  global $yhtiorow, $kukarow;

  $query_ale_lisa = generoi_alekentta('O');

  $ale_query_select_lisa = generoi_alekentta_select('erikseen', 'O');

  $query = "SELECT tilausrivi.otunnus, tilausrivi.tuoteno, tilausrivi.yksikko, tuotteen_toimittajat.toim_tuoteno, tilausrivi.nimitys,
            tilkpl,
            round(tilkpl*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4) ulkkpl,
            hinta, {$ale_query_select_lisa} round((varattu+jt)*hinta*if(tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
            toimaika, tilausrivi.jaksotettu as vahvistettu, tilausrivi.tunnus,
            toim_tuoteno
            FROM tilausrivi
            LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
            LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='$toimittajaid'
            WHERE otunnus             in ($tilaus_otunnukset)
            and tilausrivi.yhtio='$kukarow[yhtio]'
            and tilausrivi.uusiotunnus=0
            and tilausrivi.tyyppi='O'
            and tilausrivi.jaksotettu = 0
            ORDER BY tilausrivi.otunnus";
  $result = pupe_query($query);

  $rivit = array();
  $total = 0;
  while ($row = mysql_fetch_assoc($result)) {
    //  Tarkastetaan olisiko toimittajalla yksikkö!
    $query = "SELECT toim_yksikko
              FROM tuotteen_toimittajat
              WHERE yhtio      = '$kukarow[yhtio]'
              and tuoteno      = '$row[tuoteno]'
              and liitostunnus = '$laskurow[liitostunnus]'
              LIMIT 1";
    $rarres = pupe_query($query);
    $rarrow   = mysql_fetch_assoc($rarres);

    if ($row["yksikko"] == "") {
      $row["yksikko"] = $rarrow["toim_yksikko"];
    }
    if ($rarrow["toim_yksikko"] == "") {
      $rarrow["toim_yksikko"] = $row["yksikko"];
    }

    $omyks = t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite");
    $toyks = t_avainsana("Y", $kieli, "and avainsana.selite='$rarrow[toim_yksikko]'", "", "", "selite");

    $row['omyks'] = $omyks;
    $row['toyks'] = $toyks;

    $rivit[] = $row;
    $total += $row['rivihinta'];
  }

  $rivit['total_rivihinta'] = $total;

  return $rivit;
}

require "inc/footer.inc";
