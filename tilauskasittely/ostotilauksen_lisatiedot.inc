<?php

$query  = "SELECT *
           FROM lasku
           WHERE tunnus  = '$otunnus'
           AND yhtio     = '$kukarow[yhtio]'
           AND tila      = 'K'
           AND alatila  != 'X'";
$result = pupe_query($query);

if (mysql_num_rows($result) == 1) {
  $laskurow = mysql_fetch_assoc($result);
}
else {
  echo "<font style='error'>".t("VIRHE: Saapumista ei enää löydy")."!</font>";
  exit;
}

// lasku taulussa käytetään kenttiä seuraavasti:
//
// rahti      = kuluprosentti
// rahti_etu    = eturahti
// rahti_huolinta  = kulusumma (jos löytyy niin ohittaa kuluprosentin)

if ($tee == "update") {

  if ($yhtiorow['jalkilaskenta_kuluperuste'] == "VS" and isset($vienti_kurssi) and $vienti_kurssi >= 0) {
    $query  = "UPDATE lasku SET
               vienti_kurssi  = '{$vienti_kurssi}'
               WHERE tunnus   = '$otunnus'
               AND yhtio      = '$kukarow[yhtio]'
               AND tila       = 'K'
               AND alatila   != 'X'";
    $result = pupe_query($query);
    $laskurow['vienti_kurssi'] = $vienti_kurssi;
  }

  list (, , , , , , , , $llrow, , , , , , , ) = tsekit($laskurow, 0, 0);

  // Tsekki tehdään vain jos vaihto-omaisuus laskuja on liitetty saapumiseen!
  if ($llrow["volasku"] > 0 and abs($rahti_etu) > abs($llrow['vosumma_valuutassa'])) {
    echo "<font class='message'>".t("VIRHE: Eturahti ei saa olla suurempi kuin laskujen yhteissumma")."!</font><br><br>";

    $tee = "";
    $toiminto = "lisatiedot";

    if ($nappikeikalla == 'ollaan') {
      $nappikeikalle = 'menossa';
      $nappikeikalla = '';
    }
  }

  // Pyöristysero tarkistus
  if ($llrow['volasku'] > 0 and abs($pyoristys_erot) > abs($llrow['vosumma_valuutassa'])) {
    echo "<font class='message'>".t("VIRHE: Pyöristysero ei saa olla suurempi kuin laskujen yhteissumma")."!</font><br><br>";

    $tee = "";
    $toiminto = "lisatiedot";

    if ($nappikeikalla == 'ollaan') {
      $nappikeikalle = 'menossa';
      $nappikeikalla = '';
    }
  }

  if ((float) $erikoisale_saapuminen < 0 or (float) $erikoisale_saapuminen > 100) {
    echo "<font class='message'>".t("VIRHE: Erikoisale pitää olla alle 100% ja yli 0%")."!</font><br><br>";

    $tee = "";
    $toiminto = "lisatiedot";

    if ($nappikeikalla == 'ollaan') {
      $nappikeikalle = 'menossa';
      $nappikeikalla = '';
    }
  }

  foreach ($tilauksen_erikoisale as $etil => $eale) {
    if ((float) $eale < 0 or (float) $eale > 100) {
      echo "<font class='message'>".t("VIRHE: Erikoisale pitää olla alle 100% ja yli 0%")."! ".t("Tilaus").": $etil</font><br><br>";

      $tee = "";
      $toiminto = "lisatiedot";

      if ($nappikeikalla == 'ollaan') {
        $nappikeikalle = 'menossa';
        $nappikeikalla = '';
      }
    }
  }
}

if ($tee == "update") {
  if ($laskurow["erikoisale_saapuminen"] == $erikoisale_saapuminen and $laskurow["rahti_etu"] == $rahti_etu and $laskurow["rahti_etu_alv"] == $rahti_etu_alv and $laskurow['pyoristys_erot'] == $pyoristys_erot and $laskurow['pyoristys_erot_alv'] == $pyoristys_erot_alv) {
    $kohdistettu = $laskurow["kohdistettu"];
  }
  else {
    $kohdistettu = "";
  }

  if ($nappikeikalla != 'ollaan') {
    echo "<font class='message'>".t("Muutit eturahtia ja/tai erikoisalennusta, kohdistus pitää tarkastaa uudelleen.")."</font><br><br>";
  }
  else {
    echo "<font class='message'>".t("Muutit eturahtia ja/tai erikoisalennusta, muistathan painaa kohdistus valmis, jotta muutokset huomioidaan.")."<br></font>";
  }

  // Hoidetaan pilkut pisteiksi....
  $rahti_etu         = str_replace(",", ".", $rahti_etu);
  $rahti           = str_replace(",", ".", $rahti);
  $rahti_huolinta      = str_replace(",", ".", $rahti_huolinta);
  $pyoristys_erot      = str_replace(",", ".", $pyoristys_erot);
  $pyoristys_erot_alv    = str_replace(",", ".", $pyoristys_erot_alv);
  $erikoisale_saapuminen  = str_replace(",", ".", $erikoisale_saapuminen);
  $bruttopaino       = str_replace(",", ".", $bruttopaino);

  $toimaika     = $vv."-".$kk."-".$pp;
  $ultilno     = tarvitaanko_intrastat($maa_lahetys, $maa_maara);

  $toimipaikka = isset($toimipaikka) ? $toimipaikka : 0;

  $query = "UPDATE lasku
            SET maa_lahetys      = '$maa_lahetys',
            maa_maara               = '$maa_maara',
            kauppatapahtuman_luonne = '$ktapahtuman_luonne',
            kuljetusmuoto           = '$kuljetusmuoto',
            bruttopaino             = '$bruttopaino',
            rahti_huolinta          = '$rahti_huolinta',
            rahti_etu               = '$rahti_etu',
            rahti_etu_alv           = '$rahti_etu_alv',
            rahti                   = '$rahti',
            pyoristys_erot          = '$pyoristys_erot',
            pyoristys_erot_alv      = '$pyoristys_erot_alv',
            erikoisale_saapuminen   = '$erikoisale_saapuminen',
            toimaika                = '$toimaika',
            kohdistettu             = '$kohdistettu',
            comments                = '$kommentti',
            ultilno                 = '$ultilno',
            yhtio_toimipaikka       = '{$toimipaikka}'
            WHERE tunnus            = '$otunnus'
            and yhtio               = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  $query_erikoisale = "UPDATE tilausrivi
                       SET erikoisale_saapuminen = '{$erikoisale_saapuminen}'
                       WHERE yhtio      = '{$kukarow['yhtio']}'
                       AND uusiotunnus  = '{$otunnus}'
                       AND netto       != 'N'
                       AND tyyppi      != 'D'";
  $upd_erikoisale_res = pupe_query($query_erikoisale);

  foreach ($tilauksen_erikoisale as $etil => $eale) {
    $eale = str_replace(",", ".", $eale);
    $query_erikoisale = "UPDATE tilausrivi
                         SET erikoisale = '{$eale}'
                         WHERE yhtio      = '{$kukarow['yhtio']}'
                         AND uusiotunnus  = '{$otunnus}'
                         AND otunnus      = '{$etil}'
                         AND netto       != 'N'
                         AND tyyppi      != 'D'";
    $upd_erikoisale_res = pupe_query($query_erikoisale);
  }

  $toiminto = "";
}

if ($tee == "") {

  list (, , , , , , , , $llrow, , , , , , , ) = tsekit($laskurow, 0, 0);

  // haetaan toimittajan tiedot
  $query  = "SELECT * FROM toimi WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$laskurow[liitostunnus]'";
  $result = pupe_query($query);
  $toimittaja = mysql_fetch_array($result);

  // katotaan paljonko on kohdistamatta
  $jaljella = sprintf("%.2f", $llrow["voverosumma_valuutassa"] - $laskurow["summa"]);

  // kaunistellaan
  if ($llrow["voverosumma_valuutassa"] == "") $llrow["voverosumma_valuutassa"] = "0.00";

  // näytetään vielä laskun tiedot, ettei kohdisteta päin berberiä
  echo "<br><table>";
  echo "<tr>";
  echo "<th>".t("saapuminen")."</th>";
  echo "<th>".t("ytunnus")."</th>";
  echo "<th>".t("nimi")."</th>";
  echo "<th>".t("tapvm")."</th>";
  echo "<th>".t("toimitusehto")."</th>";
  echo "<th>".t("ostolaskut")."</th>";
  echo "<th>".t("kohdistettu")."</th>";
  echo "<th>".t("jäljellä")."</th>";
  echo "</tr>";
  echo "<tr>
      <td>$laskurow[laskunro]</td>
      <td>$laskurow[ytunnus]</td>
      <td>$laskurow[nimi]</td>
      <td>$laskurow[tapvm]</td>
      <td>$laskurow[toimitusehto]</td>
      <td>$llrow[voverosumma_valuutassa] $laskurow[valkoodi]</td>
      <td>$laskurow[summa] $laskurow[valkoodi]</td>
      <td>$jaljella $laskurow[valkoodi]</td></tr>";
  echo "</table><br>";

  echo "<table>";
  echo "<form method='post'>";
  echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
  echo "  <input type='hidden' name='toiminto' value='lisatiedot'>
      <input type='hidden' name='otunnus' value='$otunnus'>
      <input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>
      <input type='hidden' name='tee' value='update'>";

  if ($nappikeikalle == 'menossa') {
    echo "<input type='hidden' name='nappikeikalla' value='ollaan'>";
  }

  echo "<tr><th>".t("Lähetysmaa").":</th><td colspan='3'>
      <select name='maa_lahetys'>";

  $query = "SELECT distinct koodi, nimi
            FROM maat
            where nimi != ''
            ORDER BY koodi";
  $result = pupe_query($query);
  echo "<option value=''>".t("Valitse")."</option>";

  while ($row = mysql_fetch_array($result)) {
    $sel = '';

    if ($row['koodi'] == $laskurow["maa_lahetys"]) {
      $sel = 'selected';
    }
    elseif ($laskurow["maa_lahetys"] == "" and $row['koodi'] == $toimittaja["maa_lahetys"]) {
      $sel = "selected";
    }
    elseif ($laskurow["maa_lahetys"] == "" and $toimittaja['maa_lahetys'] == '' and $row['koodi'] == $toimittaja['maa']) {
      $sel = "selected";
    }

    echo "<option value='{$row['koodi']}' $sel>{$row['nimi']}</option>";
  }
  echo "</select></td>";
  echo "</tr>";

  echo "  <tr><th>".t("Määrämaan koodi").":</th>
      <td colspan='3'><select name='maa_maara'>";

  $query = "SELECT distinct koodi, nimi
            FROM maat
            where nimi != ''
            ORDER BY koodi";
  $result = pupe_query($query);

  if ($laskurow["maa_maara"] == "") {
    $query = "SELECT varastopaikat.maa, count(*) kpl
              FROM lasku
              JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.uusiotunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
              JOIN varastopaikat ON (varastopaikat.yhtio = lasku.yhtio
                AND varastopaikat.tunnus = tilausrivi.varasto)
              WHERE lasku.yhtio          = '$kukarow[yhtio]'
              AND lasku.tunnus           = '$laskurow[tunnus]'
              GROUP BY varastopaikat.maa
              ORDER BY kpl DESC
              LIMIT 1";
    $maaresult = pupe_query($query);

    if ($maarow = mysql_fetch_array($maaresult)) {
      $laskurow["maa_maara"] = $maarow["maa"];
    }
    else {
      $laskurow["maa_maara"] = $yhtiorow["maa"];
    }
  }

  while ($row = mysql_fetch_array($result)) {
    $sel = '';
    if ($row['koodi'] == $laskurow["maa_maara"]) {
      $sel = 'selected';
    }
    echo "<option value='{$row['koodi']}' $sel>{$row['nimi']}</option>";
  }
  echo "</select></td>";
  echo "</tr>";

  if ($laskurow["tuontipvm"] == '0000-00-00') {
    $pp = date('d');
    $kk = date('m');
    $vv = date('Y');
    $laskurow["tuontipvm"] = $vv."-".$kk."-".$pp;
  }

  echo "<tr><th>".t("Kauppatapahtuman luonne").":</th><td colspan='3'><select name='ktapahtuman_luonne'>";
  echo "<option value=''>".t("Valitse")."</option>";

  $result = t_avainsana("KT");

  while ($row = mysql_fetch_array($result)) {
    $sel = '';
    if ($row["selite"] == $laskurow["kauppatapahtuman_luonne"]) {
      $sel = 'selected';
    }
    elseif ($laskurow["kauppatapahtuman_luonne"] == "0" and $row["selite"] == $toimittaja["kauppatapahtuman_luonne"]) {
      $sel = "selected";
    }
    echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
  }
  echo "</select></td>";
  echo "</tr>";

  echo "<tr><th>".t("Kuljetusmuoto").":</th><td colspan='3'>
        <select NAME='kuljetusmuoto'>";
  echo "<option value=''>".t("Valitse")."</option>";

  $result = t_avainsana("KM");

  while ($row = mysql_fetch_array($result)) {
    $sel = '';
    if ($row["selite"] == $laskurow["kuljetusmuoto"]) {
      $sel = 'selected';
    }
    elseif ($laskurow["kuljetusmuoto"] == "0" and $row["selite"] == $toimittaja["kuljetusmuoto"]) {
      $sel = "selected";
    }
    echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
  }
  echo "</select></td>";
  echo "</tr>";

  if ($laskurow["rahti"] == 0) {
    // katotaan onko toimittajalla joku defaultti kuluprosentti
    $laskurow["rahti"] = $toimittaja["oletus_kulupros"];
  }

  if ($laskurow["bruttopaino"] == 0) {
    $query  = "SELECT round(sum(tuotemassa*(varattu+kpl)),2) massa, sum(varattu+kpl) kpl, sum(if(tuotemassa!=0, varattu+kpl, 0)) kplok
               FROM tilausrivi
               JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
               WHERE tilausrivi.yhtio      = '$kukarow[yhtio]'
               AND tilausrivi.uusiotunnus  = '$laskurow[tunnus]'
               AND tilausrivi.tyyppi      != 'D'";
    $painoresult = pupe_query($query);
    $painorow = mysql_fetch_array($painoresult);

    if ($painorow["kpl"] > 0) {
      $osumapros = round($painorow["kplok"] / $painorow["kpl"] * 100, 2);
    }
    else {
      $osumapros = "N/A";
    }

    $laskurow["bruttopaino"] = $painorow["massa"];

    $lisamassa = "<font class='message'>".sprintf(t("Tilauksen paino tuoterekisterin tietojen mukaan on: %s kg, %s %%:lle kappaleista on annettu paino."), $painorow["massa"], $osumapros)."</font><br>";
  }

  if (abs($laskurow['rahti_etu']) > abs($llrow['vosumma_valuutassa'])) {
    $lisatiedot = "<font class='message'>".t("VIRHE: Eturahti ei saa olla suurempi kuin laskujen yhteissumma")."!</font><br>";
  }

  if (abs($laskurow['pyoristys_erot']) > abs($llrow['vosumma_valuutassa'])) {
    $pyoristusero_lisatiedot .= "<font class='message'>".t("VIRHE: Pyöristysero ei saa olla suurempi kuin laskujen yhteissumma")."!</font><br>";
  }

  if ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikat_res) > 0) {

    echo "<tr>";
    echo "<th valign='top'>", t("Toimipaikka"), "</th>";
    echo "<td valign='top' colspan='3'>";
    echo "<select name='toimipaikka'>";
    echo "<option value=''>", t("Valitse"), "</option>";

    $sel = (isset($toimipaikka) and $toimipaikka == 0) ? "selected" : "";

    echo "<option value='0' {$sel}>".t('Ei toimipaikkaa')."</option>";

    $sel = "";

    while ($toimipaikat_row = mysql_fetch_assoc($toimipaikat_res)) {
      $sel = $laskurow['yhtio_toimipaikka'] == $toimipaikat_row['tunnus'] ? ' selected' : '';
      echo "<option value='{$toimipaikat_row['tunnus']}'{$sel}>{$toimipaikat_row['nimi']}</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }

  echo "<tr><th valign='top'>".t("Nettopaino").":</th><td valign='top' colspan='3'><input type='text' name='bruttopaino' value='$laskurow[bruttopaino]'></td><td valign='top' class='back' width='450'>$lisamassa</td></tr>";

  // Näytetään eturahti vain jos yhtiönparametri jalkilaskenta_kuluperuste == ''
  if (($yhtiorow['yhtio'] != 'artr' and $yhtiorow['yhtio'] != 'mergr') or $laskurow['rahti_etu'] != 0) {
    echo "<tr><th valign='top'>".t("Veroton")." ".t("Eturahti").":</th><td valign='top'><input type='text' name='rahti_etu' value='$laskurow[rahti_etu]'></td><td colspan='2' valign='top' nowrap>".t("Eturahdin ALV").": ".alv_popup('rahti_etu_alv', $laskurow["rahti_etu_alv"])."</td><td class='back'> $lisatiedot </td></tr>";
  }

  echo "<tr><th valign='top'>".t("Veroton")." ".t("Pyöristysero").":</th><td valign='top'><input type='text' id='pyoristys_erot' name='pyoristys_erot' value='$laskurow[pyoristys_erot]'></td><td colspan='2' valign='top' nowrap>".t("Pyöristyseron ALV").": ".alv_popup('pyoristys_erot_alv', $laskurow["pyoristys_erot_alv"])."</td><td class='back'>$pyoristusero_lisatiedot</td></tr>";
  echo "<tr><th valign='top'>".t("Arvioitu kuluprosentti").":</th><td valign='top' colspan='3'><input type='text' name='rahti' value='$laskurow[rahti]'> n.nn %</td></tr>";
  echo "<tr><th valign='top'>".t("Kulusumma")." $yhtiorow[valkoodi]:</th><td valign='top' colspan='3'><input type='text' name='rahti_huolinta' value='$laskurow[rahti_huolinta]'> ".t("ohittaa kuluprosentin")."</td></tr>";
  if ($yhtiorow['jalkilaskenta_kuluperuste'] == "VS" and $laskurow['valkoodi'] != $yhtiorow['valkoodi']) {
    echo "<tr><th valign='top'>".t("Kurssi").":</th><td valign='top' colspan='3'><input type='text' name='vienti_kurssi' value='$laskurow[vienti_kurssi]'> n.nn %</td></tr>";
  }
  echo "<tr><th valign='top'>".t("Erikoisalennus").":</th><td valign='top' colspan='3'><input type='text' name='erikoisale_saapuminen' value='$laskurow[erikoisale_saapuminen]'></td></tr>";

  if ($laskurow["toimaika"] != '0000-00-00') {
    $vv = substr($laskurow["toimaika"], 0, 4);
    $kk = substr($laskurow["toimaika"], 5, 2);
    $pp = substr($laskurow["toimaika"], 8, 2);
  }
  else {
    $vv = date("Y");
    $kk = date("m");
    $pp = date("d");
  }

  echo "<tr><th>".t("Toimitusaika").":</th>";
  echo "<td colspan='3'>";
  echo t("pp")." <input type='text' name='pp' value='{$pp}' size='3'> ";
  echo t("kk")." <input type='text' name='kk' value='{$kk}' size='3'> ";
  echo t("vvvv")." <input type='text' name='vv' value='{$vv}' size='5'></td></tr>";

  echo "<tr><th>".t("Kommentti").":</th><td colspan='3'>";
  echo "<textarea name='kommentti' cols='50' rows='5'>{$laskurow['comments']}</textarea>";
  echo "</td></tr>";

  echo "<tr><td class='back'><br>".t("Ostotilausten erikoisalennukset").":</td></tr>";

  $query  = "SELECT otunnus, MAX(erikoisale) erikoisale
             FROM tilausrivi
             WHERE tilausrivi.yhtio      = '$kukarow[yhtio]'
             AND tilausrivi.uusiotunnus  = '$laskurow[tunnus]'
             AND tilausrivi.tyyppi      != 'D'
             GROUP BY 1";
  $painoresult = pupe_query($query);

  while ($painorow = mysql_fetch_array($painoresult)) {
    echo "<tr><th>".t("Tilaus").": {$painorow['otunnus']}</th><td colspan='3'><input type='text' name='tilauksen_erikoisale[{$painorow['otunnus']}]' size='5' value='{$painorow['erikoisale']}'></td></tr>";
  }

  echo "<tr><td class='back'><br>".t("Käyttöliittymä").":</td></tr>";

  $chk = "";

  if (!empty($saap_erikoisale_ui)) {
    $chk = "CHECKED";
  }

  echo "<tr><th>".t("Piilota rivikohtainen erikoisale").":</th><td colspan='3'><input type='checkbox' name='saap_erikoisale_ui' $chk></td></tr>";

  $chk = "";

  if (!empty($saap_rivihinta_ui)) {
    $chk = "SELECTED";
  }

  echo "<tr><th>".t("Rivihinta").":</th><td colspan='3'>
  <select name='saap_rivihinta_ui'>
  <option value=''>".t("Näytetään veroton bruttohinta ja verollinen nettohinta")."</option>
  <option value='verobveron' $chk>".t("Näytetään veroton bruttohinta ja veroton nettohinta")."</option>
  </select>
  </td></tr>";

  echo "</table>";

  echo "<br><input type='submit' id='paivita' name='lisatiedot_submit' value='".t("Päivitä tiedot")."'>";
  echo "</form>";

  echo "
    <script type='text/javascript'>

      // Tarkistetaan onko pyöristyseroja muutettu ja varmistetaan
      // käyttäjältä että tämä on ok.
      var muutettu_pyoristyseroja = false;
      $('#pyoristys_erot').on('change', function() {
        muutettu_pyoristyseroja = true;
      });

      $('#paivita').on('click', function() {
        if(muutettu_pyoristyseroja) {
          return confirm('".t("Olet lisännyt tai muuttanut pöyristyseroja, oletko varma")."?');
        }
      });

    </script>";
}
