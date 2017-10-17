<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

echo "<font class='head'>", t("Edustajaraportti"), "</font><hr>";

if (!isset($konserni)) $konserni = "";
if (!isset($vstk)) $vstk = "";
if (!isset($tee)) $tee = "";
if (!isset($piilota_matkasarakkeet)) $piilota_matkasarakkeet = "";
if (!isset($nayta_yhteenveto)) $nayta_yhteenveto = "";
if (!isset($asos)) $asos = "";
if (!isset($aspiiri)) $aspiiri = "";
if (!isset($asryhma)) $asryhma = "";

$lisa = "";

print " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
      $(function() {
        $('.check_all').on('click', function() {
          var id = $(this).val();

          if ($(this).is(':checked')) {
            $('.'+id).attr('checked', true);
          }
          else {
            $('.'+id).attr('checked', false);
          }
        });

        $('.show_all').on('click', function() {
          var id = $(this).attr('id');
          $('.'+id).toggle();
          $(this).toggleClass('tumma');
          if ($('.'+id).is(':visible')) {
            $('#img_'+id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-down.png');
          }
          else {
            $('#img_'+id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-right.png');
          }

        });
      });
    </script>";

if ($tee == '') {

  echo "<br><table>
      <form method='post'>";

  $asosresult = t_avainsana("ASIAKASOSASTO");

  echo "<tr><th>", t("Valitse asiakkaan osasto"), ":</th><td colspan='3'><select name='asos'>";
  echo "<option value=''>", t("Kaikki osastot"), "</option>";

  while ($asosrow = mysql_fetch_assoc($asosresult)) {
    $sel = $asos == $asosrow["selite"] ? "selected" : "";

    echo "<option value='{$asosrow['selite']}' {$sel}>{$asosrow['selite']} - {$asosrow['selitetark']}</option>";
  }

  echo "</select></td></tr>";

  $asosresult = t_avainsana("PIIRI");

  echo "<tr><th>", t("Valitse asiakkaan piiri"), ":</th><td colspan='3'><select name='aspiiri'>";
  echo "<option value=''>", t("Kaikki piirit"), "</option>";

  while ($asosrow = mysql_fetch_assoc($asosresult)) {
    $sel = $aspiiri == $asosrow["selite"] ? "selected" : "";

    echo "<option value='{$asosrow['selite']}' {$sel}>{$asosrow['selite']} - {$asosrow['selitetark']}</option>";
  }
  echo "</select></td></tr>";

  $asosresult = t_avainsana("ASIAKASRYHMA");

  echo "<tr><th>", t("Valitse asiakkaan ryhmä"), ":</th><td colspan='3'><select name='asryhma'>";
  echo "<option value=''>", t("Kaikki ryhmät"), "</option>";

  while ($asosrow = mysql_fetch_assoc($asosresult)) {
    $sel = $asryhma == $asosrow["selite"] ? "selected" : "";

    echo "<option value='{$asosrow['selite']}' {$sel}>{$asosrow['selite']} - {$asosrow['selitetark']}</option>";
  }

  echo "  </select></td></tr>";

  if ($yhtiorow['konserni'] != "") {
    $chk = trim($konserni) != '' ? "CHECKED" : "";
    echo "<tr><th>", t("Näytä konsernin kaikki asiakkaat"), ":</th><td colspan='3'><input type='checkbox' name='konserni' {$chk}></td></tr>";
  }
  $chk = trim($piilota_matkasarakkeet) != '' ? "CHECKED" : "";
  echo "<tr><th>", t("Piilota matkasarakkeet"), ":</th><td colspan='3'><input type='checkbox' id='piilota_matkasarakkeet' name='piilota_matkasarakkeet' {$chk}></td></tr>";

  $chk = trim($nayta_yhteenveto) != '' ? "CHECKED" : "";
  echo "<tr><th>".t("Näytä yhteenveto")."</th><td colspan='3'><input type='checkbox' name='nayta_yhteenveto' {$chk}></td></tr>";

  if (!isset($kka))
    $kka = date("m", mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
  if (!isset($vva))
    $vva = date("Y", mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
  if (!isset($ppa))
    $ppa = date("d", mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));

  if (!isset($kkl))
    $kkl = date("m");
  if (!isset($vvl))
    $vvl = date("Y");
  if (!isset($ppl))
    $ppl = date("d");

  echo "<tr><th>Syötä alkupäivämäärä (pp-kk-vvvv)</th>
      <td><input type='text' name='ppa' value='{$ppa}' size='3'></td>
      <td><input type='text' name='kka' value='{$kka}' size='3'></td>
      <td><input type='text' name='vva' value='{$vva}' size='5'></td>
      </tr><tr><th>Syötä loppupäivämäärä (pp-kk-vvvv)</th>
      <td><input type='text' name='ppl' value='{$ppl}' size='3'></td>
      <td><input type='text' name='kkl' value='{$kkl}' size='3'></td>
      <td><input type='text' name='vvl' value='{$vvl}' size='5'></td></tr>";
  echo "<tr>";

  if (isset($kaletapa)) {
    if (!isset($vali_kale)) $vali_kale = "";

    foreach ($kaletapa as $tama) {
      $vali_kale .= "$tama,";
    }
    $vali_kale     = substr($vali_kale, 0, -1); // Viimeinen pilkku poistetaan

  }
  else {
    if (!isset($vali_kale)) {
      $valitut = "$kukarow[kuka]"; // Jos ketaan ei ole valittu valitaan kayttaja itse
      $vertaa  = "'$kukarow[kuka]'";
    }
  }
  $ruksatut_kalet = explode(",", $vali_kale);
  $kale_querylisa = "'".implode("','", $ruksatut_kalet)."'";
  $checked = '';
  echo "<th>".t("Valitse listattavat kalenteritapahtuman lajit")."<br><br><input type='checkbox' class='check_all' value='kaletapa'>".t("Valitse kaikki")."</th>";
  echo "<td colspan='3'><div style='width:280px;height:265px;overflow:auto;'>
      <table width='100%'>";

  if ($kukarow['kieli'] == '') {
      $kukarow['kieli'] = $yhtiorow['kieli'];
  }

  $query = "SELECT tunnus,selitetark, perhe
            FROM avainsana
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND laji    = 'KALETAPA'
            AND kieli   = '{$kukarow['kieli']}'";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    $checked = in_array("$row[tunnus]", $ruksatut_kalet) ? 'checked' : "";

    echo "<tr><td nowrap><input type='checkbox' class='kaletapa' name='kaletapa[]' value='{$row['perhe']}' {$checked}></td><td>{$row['selitetark']}</td></tr>";
  }

  echo "</table>";

  if (isset($kalen)) {
    if (!isset($valitut)) $valitut = "";

    foreach ($kalen as $tama) {
      $valitut .= "$tama,";
    }
    $valitut = substr($valitut, 0, -1); // Viimeinen pilkku poistetaan
  }
  else {
    if (!isset($valitut)) {
      $valitut = "$kukarow[kuka]"; // Jos ketaan ei ole valittu valitaan kayttaja itse
      $vertaa  = "'$kukarow[kuka]'";
    }
    else {
      $vertaa = "'$valitut'";
    }
  }

  $ruksatut   = explode(",", $valitut);          //tata kaytetaan ihan lopussa
  $ruksattuja = count($ruksatut);             //taman avulla pohditaan tarvitaanko tarkenteita
  $vertaa     = "'".implode("','", $ruksatut)."'";  // tehdään mysql:n ymmärtämä muoto

  if (in_array("$kukarow[kuka]", $ruksatut)) { // Oletko valinnut itsesi
    $checked = 'checked';
  }

  echo "<tr>
      <th>", t("Listaa edustajat"), "<br><br><input type='checkbox' class='check_all' value='kalen'> ".t("Valitse kaikki")."</th>";

  echo "  <td colspan='3'><div style='width:280px;height:265px;overflow:auto;'>

      <table width='100%'><tr>
      <td><input type='checkbox' class='kalen' name='kalen[]' value = '{$kukarow['kuka']}' {$checked}></td>
      <td>", t("Oma"), "</td></tr>";

  $query = "SELECT kuka.tunnus, kuka.nimi, kuka.kuka
            FROM kuka, oikeu
            WHERE kuka.yhtio = '{$kukarow['yhtio']}'
            and oikeu.yhtio  = kuka.yhtio
            and oikeu.kuka   = kuka.kuka
            and oikeu.nimi   = 'crm/viikkosuunnitelma.php'
            and kuka.tunnus  <> '{$kukarow['tunnus']}'
            ORDER BY kuka.nimi";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    $checked = in_array("$row[kuka]", $ruksatut) ? 'checked' : "";

    echo "<tr><td nowrap><input type='checkbox' class='kalen' name='kalen[]' value='{$row['kuka']}' {$checked}></td><td>{$row['nimi']}</td></tr>";
  }

  echo "</table>";
  echo "</div></td></tr></table>";
  echo "<br><input type='submit' value='Jatka'>";
  echo "</form>";

  if ($asos != '') {
    $lisa .= " and asiakas.osasto='{$asos}' ";
  }

  if ($aspiiri != '') {
    $lisa .= " and asiakas.piiri='{$aspiiri}' ";
  }

  if ($asryhma != '') {
    $lisa .= " and asiakas.ryhma='{$asryhma}' ";
  }

  if (trim($konserni) != '') {
    $query = "SELECT DISTINCT yhtio
              FROM yhtio
              WHERE (konserni = '{$yhtiorow['konserni']}' AND konserni != '')
              OR (yhtio = '{$yhtiorow['yhtio']}')";
    $result = pupe_query($query);
    $yhtiot = array();

    while ($row = mysql_fetch_assoc($result)) {
      $yhtiot[] = $row["yhtio"];
    }
  }
  else {
    $yhtiot = array();
    $yhtiot[] = $kukarow["yhtio"];
  }
  $nayta_sarake = ($piilota_matkasarakkeet != "") ? FALSE : TRUE;
  $piirra_yhteenveto = ($nayta_yhteenveto != "") ? TRUE : FALSE;

  echo "<br><br><table>";

  $asiakasjoini = "";
  if ($lisa != '') {
    $asiakasjoini = " LEFT JOIN asiakas USE INDEX (ytunnus_index) ON (asiakas.tunnus = kalenteri.liitostunnus AND asiakas.yhtio = kalenteri.yhtio) ";
  }
  foreach ($yhtiot as $yhtio) {
    $query = "SELECT kuka.nimi kukanimi,
              kuka.yhtio yhtijo,
              avainsana.selitetark aselitetark,
              count(*) montakotapahtumaa,
              kalenteri.kuka,
              avainsana.tunnus,
              avainsana.perhe
              FROM kalenteri
              JOIN kuka ON (kuka.kuka = kalenteri.kuka AND kuka.yhtio = kalenteri.yhtio)
              JOIN avainsana ON (avainsana.yhtio = kalenteri.yhtio AND avainsana.perhe IN ({$kale_querylisa})
              AND avainsana.kieli  = 'fi')
              {$asiakasjoini}
              WHERE kalenteri.yhtio = '{$yhtio}'
              AND kalenteri.kuka    IN ({$vertaa})
              AND kalenteri.pvmalku >= '{$vva}-{$kka}-{$ppa} 00:00:00'
              AND kalenteri.pvmalku <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
              AND kalenteri.tyyppi  IN ('kalenteri','memo')
              AND kalenteri.tapa    = avainsana.selitetark
              {$lisa}
              GROUP BY 1,2,3
              HAVING count(*) > 0
              ORDER BY kukanimi, aselitetark";
    $result_group = pupe_query($query);

    if (mysql_num_rows($result_group) > 0) {

      include 'inc/pupeExcel.inc';

      $worksheet    = new pupeExcel();
      $format_bold = array("bold" => TRUE);
      $excelrivi    = 0;
      $excelsarake = 0;

      if ($piirra_yhteenveto) {
        // yhteenveto alkuun
        echo "<tr>";
        echo "<th>".t("Edustaja")."</th>";
        echo "<th>".t("Yhtiö")."</th>";
        echo "<th>".t("Tapa")."</th>";
        echo "<th>".t("Tapahtumia")."</th>";
        echo "</tr>";

        $worksheet->write($excelrivi, $excelsarake++, t("Edustaja"),  $format_bold);
        $worksheet->write($excelrivi, $excelsarake++, t("Yhtiö"),  $format_bold);
        $worksheet->write($excelrivi, $excelsarake++, t("Tapa"),  $format_bold);
        $worksheet->write($excelrivi, $excelsarake++, t("Tapahtumia"),  $format_bold);
        $excelrivi++;


        while ($rivi = mysql_fetch_assoc($result_group)) {
          $js_safe_muuttuja = str_replace("#", "hash_", $rivi['kuka']);
          echo "<tr class='show_all' id='{$js_safe_muuttuja}_{$rivi['tunnus']}'>";
          echo "<td><img style='float:left;' id='img_{$js_safe_muuttuja}_{$rivi['tunnus']}' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;{$rivi['kukanimi']}</td>";
          echo "<td>{$rivi['yhtijo']}</td>";
          echo "<td>{$rivi['aselitetark']}</td>";
          echo "<td>{$rivi['montakotapahtumaa']}</td>";
          echo "</tr>";

          $excelsarake = 0;
          $worksheet->write($excelrivi, $excelsarake++, $rivi["kukanimi"]);
          $worksheet->write($excelrivi, $excelsarake++, $rivi["yhtijo"]);
          $worksheet->write($excelrivi, $excelsarake++, $rivi["aselitetark"]);
          $worksheet->write($excelrivi, $excelsarake++, $rivi["montakotapahtumaa"]);

          $excelrivi++;

          echo "<tr class='{$js_safe_muuttuja}_{$rivi['tunnus']}' style='display:none;'>";
          echo "<td colspan='4' >";
          // haetaan tarkemmat tiedot kalelajilla ja kalekukalla
          $query = "SELECT kuka.nimi kukanimi,
                    avainsana.selitetark aselitetark,
                    IF(asiakas.toim_postitp!='', asiakas.toim_postitp, asiakas.postitp) postitp,
                    IF(asiakas.toim_postino!='' AND asiakas.toim_postino!='00000', asiakas.toim_postino, asiakas.postino) postino,
                    kalenteri.asiakas ytunnus, asiakas.asiakasnro asiakasno, kalenteri.yhtio,
                    IF(kalenteri.asiakas!='', asiakas.nimi, 'N/A') nimi,
                    LEFT(kalenteri.pvmalku,10) pvmalku,
                    kentta01, kentta02, kentta03, kentta04,
                    IF(RIGHT(pvmalku,8) = '00:00:00','',RIGHT(pvmalku,8)) aikaalku, IF(RIGHT(pvmloppu,8) = '00:00:00','',RIGHT(pvmloppu,8)) aikaloppu
                    FROM kalenteri
                    JOIN kuka ON (kuka.kuka = kalenteri.kuka AND kuka.yhtio = kalenteri.yhtio)
                    JOIN avainsana ON (avainsana.yhtio = kalenteri.yhtio AND avainsana.perhe IN ('{$rivi['perhe']}') AND avainsana.kieli  = 'fi')
                    LEFT JOIN asiakas USE INDEX (ytunnus_index) ON (asiakas.tunnus = kalenteri.liitostunnus AND asiakas.yhtio = '{$yhtio}' )
                    WHERE kalenteri.yhtio = '{$yhtio}'
                    AND kalenteri.kuka    IN ('{$rivi['kuka']}')
                    AND kalenteri.pvmalku >= '{$vva}-{$kka}-{$ppa} 00:00:00'
                    AND kalenteri.pvmalku <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
                    AND kalenteri.tyyppi  IN ('kalenteri','memo')
                    AND kalenteri.tapa    = avainsana.selitetark
                    {$lisa}
                    ORDER BY pvmalku, kalenteri.tunnus, kukanimi, aselitetark";
          $ressu = pupe_query($query);

          echo "<table style='width:100%;'>";

          echo "<tr>";

          echo "<th>", t("Edustaja"), "</th>";
          if ($nayta_sarake) echo "<th>", t("Yhtio"), "</th>";
          if ($nayta_sarake) echo "<th>", t("Tapa"), "</th>";
          echo "<th>", t("Paikka"), "</th>";
          echo "<th>", t("Postino"), "</th>";
          echo "<th>", t("Asiakas"), "</th>";
          echo "<th>", t("Asiakasno"), "</th>";
          echo "<th>", t("Nimi"), "</th>";
          if ($nayta_sarake) echo "<th>", t("Pvm"), "</th>";
          if ($nayta_sarake) echo "<th>", t("Kampanjat"), "</th>";
          echo "<th>", t("PvmKäyty"), "</th>";
          if ($nayta_sarake) echo "<th>", t("Km"), "</th>";
          echo "<th>", t("Lähtö"), "</th>";
          echo "<th>", t("Paluu"), "</th>";
          if ($nayta_sarake) echo "<th>", t("PvRaha"), "</th>";
          echo "<th>", t("Kommentit"), "</th>";

          echo "</tr>";

          while ($divirivi = mysql_fetch_assoc($ressu)) {
            echo "<tr>";
            echo "<td>{$divirivi['kukanimi']}</td>";
            if ($nayta_sarake) echo "<td>{$divirivi['yhtio']}</td>";
            if ($nayta_sarake) echo "<td>{$divirivi['aselitetark']}</td>";
            echo "<td>{$divirivi['postitp']}</td>";
            echo "<td>{$divirivi['postino']}</td>";
            echo "<td>{$divirivi['ytunnus']}</td>";
            echo "<td>{$divirivi['asiakasno']}</td>";
            echo "<td><a href='asiakasmemo.php?ytunnus={$divirivi['ytunnus']}' target='_blank'>{$divirivi['nimi']}</a></td>";
            if ($nayta_sarake) echo "<td nowrap>{$divirivi['pvmalku']}</td>";
            if ($nayta_sarake) echo "<td>{$divirivi['kentta02']}</td>";
            echo "<td nowrap>{$divirivi['pvmalku']}</td>";
            if ($nayta_sarake) echo "<td>{$divirivi['kentta03']}</td>";
            echo "<td>{$divirivi['aikaalku']}</td>";
            echo "<td>{$divirivi['aikaloppu']}</td>";
            if ($nayta_sarake) echo "<td>{$divirivi['kentta04']}</td>";
            echo "<td>{$divirivi['kentta01']}</td>";
            echo "</tr>";
          }

          echo "</table>";
          echo "</td>";
          echo "</tr>";
          echo "<tr class='{$js_safe_muuttuja}_{$rivi['tunnus']}' style='display:none;'><td class='back' colspan='4'>&nbsp;</td></tr>";

        }

        $excelsarake = 0;
        $excelrivi+=2;
      }

      $worksheet->write($excelrivi, $excelsarake++, t("Edustaja"),  $format_bold);
      if ($nayta_sarake) $worksheet->write($excelrivi, $excelsarake++, t("Yhtio"),    $format_bold);
      if ($nayta_sarake) $worksheet->write($excelrivi, $excelsarake++, t("Tapa"),    $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Paikka"),    $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Postino"),    $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Asiakas"),    $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Asiakasno"),  $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Nimi"),    $format_bold);
      if ($nayta_sarake) $worksheet->write($excelrivi, $excelsarake++, t("Pvm"),     $format_bold);
      if ($nayta_sarake) $worksheet->write($excelrivi, $excelsarake++, t("Kampanjat"),  $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("PvmKäyty"),  $format_bold);
      if ($nayta_sarake) $worksheet->write($excelrivi, $excelsarake++, t("Km"),      $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Lähtö"),    $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Paluu"),    $format_bold);
      if ($nayta_sarake) $worksheet->write($excelrivi, $excelsarake++, t("PvRaha"),    $format_bold);
      $worksheet->write($excelrivi, $excelsarake++, t("Kommentit"),  $format_bold);

      $excelrivi++;

      if (!$piirra_yhteenveto) {
        echo "<table>";
        echo "<tr>";
        echo "<th>".t("Edustaja")."</th>";
        if ($nayta_sarake) echo "<th>".t("Yhtio")."</th>";
        if ($nayta_sarake) echo "<th>".t("Tapa")."</th>";
        echo "<th>".t("Paikka")."</th>";
        echo "<th>".t("Postino")."</th>";
        echo "<th>".t("Asiakas")."</th>";
        echo "<th>".t("Asiakasnro")."</th>";
        echo "<th>".t("Nimi")."</th>";
        if ($nayta_sarake) echo "<th>".t("Pvm")."</th>";
        if ($nayta_sarake) echo "<th>".t("Kampanjat")."</th>";
        echo "<th>".t("PvmKäyty")."</th>";
        if ($nayta_sarake) echo "<th>".t("Km")."</th>";
        echo "<th>".t("Lähtö")."</th>";
        echo "<th>".t("Paluu")."</th>";
        if ($nayta_sarake) echo "<th>".t("PvRaha")."</th>";
        echo "<th>".t("Kommentit")."</th>";
        echo "</tr>";
      }

      $query = "SELECT kuka.nimi kukanimi,
                avainsana.selitetark aselitetark, avainsana.perhe,
                IF(asiakas.toim_postitp!='', asiakas.toim_postitp, asiakas.postitp) postitp,
                IF(asiakas.toim_postino!='' AND asiakas.toim_postino!='00000', asiakas.toim_postino, asiakas.postino) postino,
                kalenteri.asiakas ytunnus, asiakas.asiakasnro asiakasno, kalenteri.yhtio,
                IF(kalenteri.asiakas!='', asiakas.nimi, 'N/A') nimi,
                LEFT(kalenteri.pvmalku,10) pvmalku,
                kentta01, kentta02, kentta03, kentta04,
                IF(RIGHT(pvmalku,8) = '00:00:00','',RIGHT(pvmalku,8)) aikaalku, IF(RIGHT(pvmloppu,8) = '00:00:00','',RIGHT(pvmloppu,8)) aikaloppu
                FROM kalenteri
                JOIN kuka ON (kuka.kuka = kalenteri.kuka AND kuka.yhtio = kalenteri.yhtio)
                JOIN avainsana ON (avainsana.yhtio = kalenteri.yhtio AND avainsana.perhe IN ({$kale_querylisa}) AND avainsana.kieli  = 'fi')
                LEFT JOIN asiakas USE INDEX (ytunnus_index) ON (asiakas.tunnus = kalenteri.liitostunnus AND asiakas.yhtio = '{$yhtio}' )
                WHERE kalenteri.yhtio = '{$yhtio}'
                AND kalenteri.kuka    IN ($vertaa)
                AND kalenteri.pvmalku >= '{$vva}-{$kka}-{$ppa} 00:00:00'
                AND kalenteri.pvmalku <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
                AND kalenteri.tyyppi  IN ('kalenteri','memo')
                AND kalenteri.tapa    = avainsana.selitetark
                AND (kalenteri.perheid=0 or kalenteri.tunnus=kalenteri.perheid)
                {$lisa}
                ORDER BY pvmalku, kalenteri.tunnus, kukanimi, aselitetark";
      $ressu = pupe_query($query);

      while ($row = mysql_fetch_assoc($ressu)) {

        $asquery = "SELECT tunnus,selitetark, perhe
                  FROM avainsana
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND perhe   = '{$row['perhe']}'
                  AND kieli   = '{$kukarow['kieli']}'
                  LIMIT 1";
        $asresult = pupe_query($asquery);

        if (mysql_num_rows($asresult) > 0) {
          $asrow = mysql_fetch_assoc($asresult);
          $row["aselitetark"] = $asrow["selitetark"];
        }

        $excelsarake = 0;

        $worksheet->write($excelrivi, $excelsarake++, $row["kukanimi"]);
        if ($nayta_sarake) $worksheet->write($excelrivi, $excelsarake++, $row["yhtio"]);
        if ($nayta_sarake) $worksheet->write($excelrivi, $excelsarake++, $row["aselitetark"]);
        $worksheet->write($excelrivi, $excelsarake++, $row["postitp"]);
        $worksheet->write($excelrivi, $excelsarake++, $row["postino"]);
        $worksheet->write($excelrivi, $excelsarake++, $row["ytunnus"]);
        $worksheet->write($excelrivi, $excelsarake++, $row["asiakasno"]);
        $worksheet->write($excelrivi, $excelsarake++, $row["nimi"]);
        if ($nayta_sarake) $worksheet->write($excelrivi, $excelsarake++, $row["pvmalku"]);
        if ($nayta_sarake) $worksheet->write($excelrivi, $excelsarake++, $row["kentta02"]);
        $worksheet->write($excelrivi, $excelsarake++, $row["pvmalku"]);
        if ($nayta_sarake) $worksheet->write($excelrivi, $excelsarake++, $row["kentta03"]);
        $worksheet->write($excelrivi, $excelsarake++, $row["aikaalku"]);
        $worksheet->write($excelrivi, $excelsarake++, $row["aikaloppu"]);
        if ($nayta_sarake) $worksheet->write($excelrivi, $excelsarake++, $row["kentta04"]);
        $worksheet->write($excelrivi, $excelsarake++, $row["kentta01"]);

        $excelrivi++;

        if (!$piirra_yhteenveto) {
          echo "<tr>";
          echo "<td>{$row["kukanimi"]}</td>";
          if ($nayta_sarake) echo "<td>{$row["yhtio"]}</td>";
          if ($nayta_sarake) echo "<td>{$row["aselitetark"]}</td>";
          echo "<td>{$row["postitp"]}</td>";
          echo "<td>{$row["postino"]}</td>";
          echo "<td>{$row["ytunnus"]}</td>";
          echo "<td>{$row["asiakasno"]}</td>";
          echo "<td>{$row["nimi"]}</td>";
          if ($nayta_sarake) echo "<td nowrap>{$row["pvmalku"]}</td>";
          if ($nayta_sarake) echo "<td>{$row["kentta02"]}</td>";
          echo "<td nowrap>{$row["pvmalku"]}</td>";
          if ($nayta_sarake) echo "<td>{$row["kentta03"]}</td>";
          echo "<td>{$row["aikaalku"]}</td>";
          echo "<td>{$row["aikaloppu"]}</td>";
          if ($nayta_sarake) echo "<td>{$row["kentta04"]}</td>";
          echo "<td>{$row["kentta01"]}</td>";
          echo "</tr>";
        }
      }
      if (!$piirra_yhteenveto) echo "</table>";
      $excelnimi = $worksheet->close();
    }
  }
  echo "</table>";

  if (isset($excelnimi)) {
    echo "<br><br><table>";
    echo "<tr><th>", t("Tallenna tulos"), ":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Edustajaraportti.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='{$excelnimi}'>";
    echo "<td class='back'><input type='submit' value='", t("Tallenna"), "'></td></tr></form>";
    echo "</table><br>";
  }
}

require "inc/footer.inc";
