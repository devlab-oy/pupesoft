<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

$lis = "";

if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
  $lis = " and lasku.liitostunnus = '$kukarow[oletus_asiakas]' ";
}

if (!isset($ojarj)) $ojarj = "";
if (!isset($nippu)) $nippu = 0;
if (!isset($toim)) $toim = "";
if (!isset($asiakasid)) $asiakasid = "";
if (!isset($toimittajaid)) $toimittajaid = "";
if (!isset($nimi)) $nimi = "";
if (!isset($ppa)) $ppa = "";
if (!isset($kka)) $kka = "";
if (!isset($vva)) $vva = "";
if (!isset($ppl)) $ppl = "";
if (!isset($kkl)) $kkl = "";
if (!isset($vvl)) $vvl = "";
if (!isset($lopetus)) $lopetus = "";
if (!isset($linkurl)) $linkurl = "";
if (!isset($kieli)) $kieli = "";
if (!isset($tee)) $tee = "";
$toimittajan_ostotilaus = array();

$_asiakkaantilaus = (strpos($_SERVER['SCRIPT_NAME'], "asiakkaantilaukset.php") !== FALSE);
$_tulostakopio = (strpos($_SERVER['SCRIPT_NAME'], "tulostakopio.php") !== FALSE);
$lahetteen_tulostusjono = (strpos($_SERVER['SCRIPT_NAME'], "lahetteen_tulostusjono.php") !== FALSE);

$query = "SELECT lasku.*,
          laskun_lisatiedot.laskutus_nimi,
          laskun_lisatiedot.laskutus_nimitark,
          laskun_lisatiedot.laskutus_osoite,
          laskun_lisatiedot.laskutus_postino,
          laskun_lisatiedot.laskutus_postitp,
          laskun_lisatiedot.laskutus_maa,
          if (lasku.tila = 'L' and lasku.laskunro > 0,
                (   SELECT lasku.mapvm
                    FROM lasku l
                    WHERE l.yhtio               = lasku.yhtio
                    AND l.laskunro              = lasku.laskunro
                    AND l.tila                  = 'U'
                    LIMIT 1), lasku.mapvm) mapvm,
          if (lasku.tila = 'U',
                (   SELECT group_concat(distinct alv SEPARATOR ' ')
                    FROM tilausrivi use index (uusiotunnus_index)
                    WHERE tilausrivi.yhtio      = lasku.yhtio
                    AND tilausrivi.uusiotunnus  = lasku.tunnus
                    AND tilausrivi.var         != 'O'
                    AND tilausrivi.tyyppi      != 'D'),
                (   SELECT group_concat(distinct alv SEPARATOR ' ')
                    FROM tilausrivi use index (yhtio_otunnus)
                    WHERE tilausrivi.yhtio      = lasku.yhtio
                    AND tilausrivi.otunnus      = lasku.tunnus
                    AND tilausrivi.var         != 'O'
                    AND tilausrivi.tyyppi      != 'D')) alv,
          maksuehto.kateinen
          FROM lasku
          LEFT JOIN laskun_lisatiedot
          ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
          LEFT JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio
            AND maksuehto.tunnus                = lasku.maksuehto)
          WHERE lasku.tunnus                    = '$tunnus'
          $lis
          and lasku.yhtio='$kukarow[yhtio]'";
$aresult = pupe_query($query);

if (mysql_num_rows($aresult) == 0) {
  echo "<strong>".t("VIRHE: Tilausta ei löydy")."!</strong>$query<br>";
  exit;
}

$laskurow = mysql_fetch_assoc($aresult);

$vientikurssi = 1;

if (!in_array($laskurow["vienti_kurssi"], array(1, 0))) {
  $vientikurssi = $laskurow["vienti_kurssi"];
}

//  Onko koko projekti maksettu
if ($laskurow['tila'] == 'R') {
  $query = "SELECT count(distinct laskunro) laskuja, sum(if (laskunro=0, 1, 0)) laskuttamatta,
            sum(if ((select mapvm from lasku l where l.yhtio = lasku.yhtio and l.laskunro = lasku.laskunro and l.tila = 'U') = '0000-00-00', 1, 0)) maksamatta,
            max((select mapvm from lasku l where l.yhtio = lasku.yhtio and l.laskunro = lasku.laskunro and l.tila = 'U')) maksettu
            FROM lasku
            WHERE yhtio='$kukarow[yhtio]' and tunnusnippu='$laskurow[tunnus]' and tila = 'L'";
  $mares = pupe_query($query);
  $marow = mysql_fetch_assoc($mares);

  if ($marow["laskuttamatta"] == 0 and $marow["maksamatta"] == 0) {
    $laskurow["mapvm"] = $marow["maksettu"];
  }
}

echo "<table>";

echo "<tr>
    <th>".t("Ytunnus")."</th>
    <th colspan='2'>".t("Ostaja")."</th>
    <th colspan='2'>".t("Toimitusosoite")."</th>
    <th>".t("Laskutusosoite")."</th>";

if ($laskurow["tila"] == "O") {
  echo "<th>".t("Eturahti")."</th>";
  echo "<th>".t("Pyoristyserot")."</th>";
}

echo "</tr>";

echo "<tr>
    <td>", tarkistahetu($laskurow['ytunnus']), "</td>
    <td colspan='2'>$laskurow[nimi] $laskurow[nimitark]<br> $laskurow[osoite]<br> $laskurow[postino] $laskurow[postitp]<br>".maa($laskurow["maa"])."</td>";

if ($verkkokauppa != "" and $muokkaa != "") {
  echo "<td colspan='2'>
      <input type = 'text' name='toim_nimi' value = '$laskurow[toim_nimi]' size = '30'><br>
      <input type = 'text' name='toim_nimitark' value = '$laskurow[toim_nimitark]' size = '30'><br>
      <input type = 'text' name='toim_osoite' value = '$laskurow[toim_osoite]' size = '30'><br>
      <input type = 'text' name='toim_postino' value = '$laskurow[toim_postino]' size = '6'> <input type = 'text' name='toim_postitp' value = '$laskurow[toim_postitp]' size = '20'><br>
      ".maa($laskurow["toim_maa"])."</td>";
}
else {
  echo "<td colspan='2'>
      $laskurow[toim_nimi] $laskurow[toim_nimitark]<br> $laskurow[toim_osoite]<br> $laskurow[toim_postino] $laskurow[toim_postitp]<br> ".maa($laskurow["toim_maa"])."</td>";
}

if (trim($laskurow['laskutus_nimi']) == '') {
  $laskurow['laskutus_nimi']     = $laskurow['nimi'];
  $laskurow['laskutus_nimitark']   = $laskurow['nimitark'];
  $laskurow['laskutus_osoite']   = $laskurow['osoite'];
  $laskurow['laskutus_postino']   = $laskurow['postino'];
  $laskurow['laskutus_postitp']   = $laskurow['postitp'];
  $laskurow['laskutus_maa']     = $laskurow['maa'];
}

echo "<td>$laskurow[laskutus_nimi] $laskurow[laskutus_nimitark]<br>$laskurow[laskutus_osoite]<br>$laskurow[laskutus_postino] $laskurow[laskutus_postitp]<br>", maa($laskurow['laskutus_maa']), "</td>";

if ($laskurow["tila"] == "O") {
  echo "<td>$laskurow[rahti_etu]</td>";
  echo "<td>$laskurow[pyoristys_erot]</td>";
}

echo "</tr>";

echo "<tr>
    <th>".t("Tilausnumero")."</th>
    <th colspan='2'>".t("Tila")."</th>
    <th>".t("Vienti")."</th>
    <th>".t("Alv")."</th>
    <th>".t("Erikoisalennus")."</th>";

if ($laskurow["tila"] == "O") {
  echo "<th>".t("Rahti")."</th>";
}
echo "</tr>";

echo "<tr>";

if ($laskurow["tila"] != "K" and $laskurow["tila"] != "U") echo "<td>$laskurow[tunnus]</td>";
else echo "<td></td>";

$laskutyyppi = $laskurow["tila"];
$alatila   = $laskurow["alatila"];

//tehdään selväkielinen tila/alatila
if ($kukarow["extranet"] != '') {
  require "laskutyyppi.inc";
}
else {
  require "inc/laskutyyppi.inc";
}

if ($laskurow["vienti"] == "K") {
  $vteksti = t("Vienti ei-EU");
}
elseif ($laskurow["vienti"] == "E") {
  $vteksti = t("Vienti EU");
}
else {
  $vteksti = t("Kotimaa");
}

if ($laskutyyppi == "Mitätöity") {
  $fn1 = "<font class='error'>";
  $fn2 = "</font>";

  $deletedlisa = ",'D'";
}
else {
  $fn1 = "";
  $fn2 = "";

  $deletedlisa = "";
}

echo "  <td colspan='2'>$fn1".t("$laskutyyppi")." ".t("$alatila")."$fn2</td>
    <td>$vteksti</td>
    <td>".(float) $laskurow['alv']."%</td>
    <td>".(float) $laskurow['erikoisale']."%</td>";

if ($laskurow["tila"] == "O") {
  echo "<td>$laskurow[rahti]</td>";
}

echo "</tr>";
echo "<tr>";

if ($laskurow["tila"] == "O" or $laskurow["tila"] == "K") echo "<th>".t("Saapumisnumero")."</th>";
else echo "<th>".t("Laskunro")."</th>";

if ($laskurow["tila"] == "O" or $laskurow["tila"] == "K") {
  echo "<th colspan='2'>".t("Valuutta")."</th>";
}
else {
  echo "<th colspan='2'>".t("Laskun pvm")."</th>";
}

echo "  <th>".t("Eräpäivä")."</th>
    <th>".t("Maksupäivä")."</th>
    <th>".t("Toimitusaika")."</th>";

if ($laskurow["tila"] == "O") {
  echo "<th>".t("Huolinta")."</th>";
}
echo "</tr>";

echo "<tr><td>$laskurow[laskunro]</td>";

if ($laskurow["tila"] == "O" or $laskurow["tila"] == "K") {
  echo "<td colspan='2'>$laskurow[valkoodi]</td>";
}
else {
  echo "<td colspan='2'>".tv1dateconv($laskurow["tapvm"])."</td>";
}
echo "  <td>".tv1dateconv($laskurow["erpcm"])."</td>
    <td>".tv1dateconv($laskurow["mapvm"])."</td>
    <td>".tv1dateconv($laskurow["toimaika"])."</td>";

if ($laskurow["tila"] == "O") {
  echo "<td>$laskurow[rahti_huolinta]</td>";
}

echo "</tr>";

if ($laskurow["tila"] != "O" and $laskurow["tila"] != "K") {
  if (isset($dpdtun[$kukarow['yhtio']])) $dpdtun = $dpdtun[$kukarow['yhtio']];
  else $dpdtun = "";

  if (isset($dpdss[$kukarow['yhtio']]))  $dpdss  = $dpdss[$kukarow['yhtio']];
  else $dpdss = "";

  if ($laskurow["tila"] == "U") {
    $query = "SELECT lasku.tunnus
              FROM lasku
              WHERE yhtio  = '$kukarow[yhtio]'
              and tila     = 'L'
              and laskunro = '$laskurow[laskunro]'";
    $otres = pupe_query($query);
    $otsikkonrot = array();

    while ($otrow = mysql_fetch_assoc($otres)) {
      $otsikkonrot[] = $otrow["tunnus"];
    }
  }
  else {
    $otsikkonrot = array($laskurow["tunnus"]);
  }

  $linkit = tilauksen_seurantalinkit($otsikkonrot);

  foreach ($linkit as $seurantalinkki) {
    $rakirno = $seurantalinkki['id'];
    $link    = $seurantalinkki['link'];

    if (empty($link)) {
      $linkurl .= "{$rakirno} ";
    }
    else {
      $linkurl .= "<a class='linkki' href='{$link}' target='_blank'>{$rakirno}</a> ";
    }
  }

  if ($laskurow["kassalipas"] != "") {
    $kassalipas_row = hae_kassalipas($laskurow["kassalipas"]);
    $toimitus_colspan = 3;
    $kassalipas_header = "<th>Kassalipas</th>";
    $kassalipas_data = "<td>{$kassalipas_row["nimi"]}</td>";
  }
  else {
    $toimitus_colspan = 4;
    $kassalipas_header = "";
    $kassalipas_data = "";
  }

  echo "<tr><th colspan='{$toimitus_colspan}'>" . t("Toimitustapa") . " / " . t("Rahtikirjanro") .
    "</th><th colspan='2'>" . t("Toimitusehto") . "</th>{$kassalipas_header}</tr>";
  echo "<tr><td colspan='{$toimitus_colspan}'>" .
    t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV") .
    " / $linkurl</td><td colspan='2'>$laskurow[toimitusehto]</td>{$kassalipas_data}</tr>";
}

if (($laskurow["tila"] == "L" or $laskurow["tila"] == "N") and $kukarow["extranet"] == "") {

  $query = "SELECT MIN(tilausrivi.kerattyaika) AS kerattyaika
            FROM tilausrivi
            WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus  = '{$laskurow['tunnus']}'
            and tilausrivi.var      not in ('P','J','O','S')
            AND tilausrivi.tyyppi  != 'D'
            HAVING kerattyaika != '0000-00-00 00:00:00'";
  $kerattyaikares = pupe_query($query);
  $kerattyaikarow = mysql_fetch_assoc($kerattyaikares);

  // Rivin toimitusaika
  if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K' or $yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
    $query = "SELECT MIN(tilausrivi.toimaika) AS toimaika
              FROM tilausrivi
              WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
              AND tilausrivi.otunnus  = '{$laskurow['tunnus']}'
              and tilausrivi.var     != 'O'
              AND tilausrivi.tyyppi  != 'D'
              HAVING toimaika != '0000-00-00 00:00:00'";
    $toimaikares = pupe_query($query);
    $toimaikarow = mysql_fetch_assoc($toimaikares);

    $toimitettuaikarow["toimitettuaika"] = $toimaikarow["toimaika"];
  }
  else {
    $query = "SELECT MIN(tilausrivi.toimitettuaika) AS toimitettuaika
              FROM tilausrivi
              WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
              AND tilausrivi.otunnus  = '{$laskurow['tunnus']}'
              and tilausrivi.var      not in ('P','J','O','S')
              AND tilausrivi.tyyppi  != 'D'
              HAVING toimitettuaika != '0000-00-00 00:00:00'";
    $toimitettuaikares = pupe_query($query);
    $toimitettuaikarow = mysql_fetch_assoc($toimitettuaikares);
  }

  echo "<tr>
      <th>".t("Luontiaika")."</th>
      <th>".t("Keräykseen")."</th>
      <th>".t("Tulostettu")."</th>
      <th>".t("Kerätty")."</th>
      <th>".t("Toimitettu")."</th>
      <th>".t("Laskutettu")."</th>
    </tr>";
  echo "<tr>
      <td>".tv1dateconv($laskurow["luontiaika"], "P", "")."</td>
      <td>".tv1dateconv($laskurow["h1time"], "P", "")."</td>
      <td>".tv1dateconv($laskurow["lahetepvm"], "P", "")."</td>
      <td>".tv1dateconv($kerattyaikarow["kerattyaika"], "P", "")."</td>
      <td>".tv1dateconv($toimitettuaikarow["toimitettuaika"], "P", "")."</td>
      <td>".tv1dateconv($laskurow["laskutettu"], "P", "")."</td>
    </tr>";
}

$komm = "";

if ($laskurow['myyja'] > 0) {
  //etsitään myyjän nimi
  $query  = "SELECT nimi, kuka
             from kuka
             where tunnus='$laskurow[myyja]' and yhtio='$kukarow[yhtio]'";
  $myyresult = pupe_query($query);
  $myyrow = mysql_fetch_assoc($myyresult);

  if ($laskurow["tila"] == "O" or $laskurow["tila"] == "K") {
    $komm .= "\n".t("Ostaja").":###".$myyrow['nimi'];
  }
  else {
    $komm .= "\n".t("Myyjä").":###".$myyrow['nimi'];
  }
}

if (!isset($myyrow) or $myyrow['kuka'] != $laskurow['laatija']) {
  //etsitään laatijan nimi
  $query  = "SELECT nimi
             from kuka
             where kuka='$laskurow[laatija]' and yhtio='$kukarow[yhtio]'";
  $laaresult = pupe_query($query);
  $laarow = mysql_fetch_assoc($laaresult);

  if ($laarow["nimi"] == "") {
    $laarow["nimi"] = $laskurow["laatija"];
  }

  $komm .= "\n".t("Laatija").":###".$laarow['nimi'];
}


if ($verkkokauppa != "" and $muokkaa != "") {
  list($toimvv, $toimkk, $toimpp) = explode("-", $laskurow['toimaika']);

  $komm .= "\n".t("Toimitusaika").":###<input type = 'text' name = 'toimpp' value='$toimpp' size = '3'> - <input type = 'text' name = 'toimkk' value='$toimkk' size = '3'> - <input type = 'text' name = 'toimvv' value='$toimvv' size = '5'>";
}
elseif ($verkkokauppa != "") {
  $komm .= "\n".t("Toimitusaika").":###".tv1dateconv($laskurow["toimaika"]);
}

if ($verkkokauppa != "" and $muokkaa != "") {
  $komm .= "\n".t("Tilaaja").":###<input type = 'text' name='tilausyhteyshenkilo' value='$laskurow[tilausyhteyshenkilo]' size = '62'>";
}
elseif (trim($laskurow['tilausyhteyshenkilo']) != '') {
  $komm .= "\n".t("Tilaaja").":###".$laskurow['tilausyhteyshenkilo'];
}

if ($verkkokauppa != "" and $muokkaa != "") {
  $komm .= "\n".t("Tilauksenne").":###<input type = 'text' name='asiakkaan_tilausnumero' value='$laskurow[asiakkaan_tilausnumero]' size = '62'>";
}
elseif (trim($laskurow['asiakkaan_tilausnumero']) != '') {
  $komm .= "\n".t("Tilauksenne").":###".$laskurow['asiakkaan_tilausnumero'];
}

if ($verkkokauppa != "" and $muokkaa != "") {
  $komm .= "\n".t("Kohde").":###<input type = 'text' name='kohde' value='$laskurow[kohde]' size = '62'>";
}
elseif (trim($laskurow['kohde']) != '') {
  $komm .= "\n".t("Kohde").":###".$laskurow['kohde'];
}

if ($verkkokauppa != "" and $muokkaa != "") {
  $komm .= "\n".t("Lisätietoja").":###<input type = 'text' name='viesti' value='$laskurow[viesti]' size = '62'>";
}
elseif (trim($laskurow['viesti']) != '') {
  $komm .= "\n".t("Lisätietoja").":###".$laskurow['viesti'];
}

// Reklamaation / tarjouksen työmääräyskommentit
if ($laskurow['tilaustyyppi'] == 'R' or ($laskurow['tilaustyyppi'] == 'T' and $yhtiorow["tyomaaraystiedot_tarjouksella"] == "")) {
  $query = "SELECT komm1, komm2
            from tyomaarays
            where yhtio = '$kukarow[yhtio]'
            and otunnus = '$laskurow[tunnus]'";
  $reklaresult = pupe_query($query);
  $reklarow = mysql_fetch_assoc($reklaresult);

  $komm1_nimi = t("Työn kuvaus");
  $komm2_nimi = t("Sisäiset kommentit");

  $tyom_avainsanat = t_avainsana("TYOM_TYOKENTAT", "", "and avainsana.selite != ''");

  while ($kommnimi = mysql_fetch_assoc($tyom_avainsanat)) {
    if ($kommnimi["selite"] == "komm1" and $kommnimi["selitetark"] != "") {
      $komm1_nimi = $kommnimi["selitetark"];
    }
    elseif ($kommnimi["selite"] == "komm2" and $kommnimi["selitetark"] != "") {
      $komm2_nimi = $kommnimi["selitetark"];
    }
  }

  if (trim($reklarow['komm1']) != '') {
    $komm .= "\n$komm1_nimi:###".wordwrap(str_replace("\n", "\n###", $reklarow['komm1']), 90, "\n###");
  }

  if (trim($reklarow['komm2']) != '') {
    $komm .= "\n$komm2_nimi:###".wordwrap(str_replace("\n", "\n###", $reklarow['komm2']), 90, "\n###");
  }
}

// Työmääräystiedot
if ($laskurow['tilaustyyppi'] == 'A') {
  $query = "SELECT *
            from tyomaarays
            where yhtio = '$kukarow[yhtio]'
            and otunnus = '$laskurow[tunnus]'";
  $reklaresult = pupe_query($query);
  $reklarow = mysql_fetch_assoc($reklaresult);

  $tyom_avainsanat = t_avainsana("TYOM_TYOKENTAT", "", "and avainsana.selite != ''");

  while ($kommnimi = mysql_fetch_assoc($tyom_avainsanat)) {
    $komm .= "\n{$kommnimi["selitetark"]}:###".wordwrap(str_replace("\n", "\n###", $reklarow[$kommnimi["selite"]]), 90, "\n###");
  }
}

if ($verkkokauppa != "" and $muokkaa != "") {
  $komm .= "\n".t("Kommentti")." 1:###<textarea name='comments' cols = '60' rows = '3'>".str_replace(array("\r\n", "\r", "\n"), " ", $laskurow["comments"])."</textarea>";
}
elseif (trim($laskurow['comments']) != '') {
  $komm .= "\n".t("Kommentti")." 1:###".wordwrap(str_replace("\n", "\n###", $laskurow['comments']), 90, "\n###");
}

if ($laskurow["sisviesti2"] != "" and $kukarow["extranet"] == "") {

  $pregit = FALSE;

  if ($_asiakkaantilaus and (preg_match_all("/Tilaus jakaantunut [0-9]* tilaukseksi: ([0-9, ]*) Tilaus:/", $laskurow['sisviesti2'], $tilausnumerotlista))) {
    foreach ($tilausnumerotlista[1] as $tilausnumerolis) {
      foreach (explode(",", $tilausnumerolis) as $tilnro) {

        $tilnro = (int) trim($tilnro);

        $laskurow["sisviesti2"] = preg_replace("/".$tilnro."([ ,])/", "<a href='asiakkaantilaukset.php?tee=NAYTATILAUS&toim=$toim&asiakasid=$asiakasid&toimittajaid=$toimittajaid&laskunro=$laskunro&lasku_yhtio=$lasku_yhtio&tunnus=$tilnro&ytunnus=$ytunnus&nimi=$nimi&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl'>$tilnro</a>\\1", $laskurow["sisviesti2"]);
        $pregit = TRUE;
      }
    }
  }

  if (!$pregit) $komm .= "\n".t("Kommentti")." 2:###".wordwrap(str_replace("\n", "\n###", $laskurow['sisviesti2']), 90, "\n###");
  else $komm .= "\n".t("Kommentti")." 2:###".$laskurow["sisviesti2"];
}

if ($laskurow["sisviesti1"] != "") {
  $komm .= "\n".t("Kommentti")." 3:###".wordwrap(str_replace("\n", "\n###", $laskurow['sisviesti1']), 90, "\n###");
}

if ($laskurow["sisviesti3"] != "" and $kukarow["extranet"] == "") {
  $komm .= "\n".t("Sisäinen viesti").":###".wordwrap(str_replace("\n", "\n###", $laskurow['sisviesti3']), 90, "\n###");
}

if ($laskurow["ohjausmerkki"] != "" and $kukarow["extranet"] == "") {
  $komm .= "\n".t("Ohjausmerkki").":###".wordwrap(str_replace("\n", "\n###", $laskurow['ohjausmerkki']), 90, "\n###");
}

if ($laskurow["noutaja"] != '') {
  $komm .= "\n".t("Noutaja").":###$laskurow[noutaja]";
}

if ($komm != "") {
  $kommentit = explode("\n", trim($komm));

  foreach ($kommentit as $kommentti) {

    list($o, $v) = explode("###", $kommentti);

    echo "<tr><th>$o</th><td colspan='5'>$v</td></tr>";
  }
}

// Katsotaan onko liitetiedostoja
$liitequery = "SELECT tunnus, selite
               FROM liitetiedostot USE INDEX (yhtio_liitos_liitostunnus)
               WHERE yhtio      = '$kukarow[yhtio]'
               AND liitos       = 'lasku'
               AND liitostunnus = '$laskurow[tunnus]'";
$liiteres = pupe_query($liitequery);

if (mysql_num_rows($liiteres) > 0) {
  $liitemaara = 1;
  echo "<tr><th>".t("Liitetiedosto")."</th><td colspan='5'>";

  while ($liiterow = mysql_fetch_assoc($liiteres)) {
    echo "<a href='{$palvelin2}view.php?id=$liiterow[tunnus]' target='Attachment'>".t("Liite")." $liitemaara $liiterow[selite]</a> ";
    $liitemaara++;
  }
  echo "</td></tr>";
}

if ($laskurow["tila"] == 'K') {
  $query = "SELECT yhtio
            FROM oikeu
            WHERE yhtio = '$kukarow[yhtio]'
            and kuka    = '$kukarow[kuka]'
            and nimi    = 'raportit.php'
            and alanimi = 'laskuhaku'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    $query = "SELECT l2.ebid, l2.nimi, l2.summa, l2.tunnus
              FROM lasku l1
              JOIN lasku l2 ON l1.yhtio=l2.yhtio and l1.vanhatunnus=l2.tunnus
              WHERE l1.yhtio     = '$kukarow[yhtio]'
              and l1.tila        = 'K'
              and l1.laskunro    = '$laskurow[laskunro]'
              and l1.vanhatunnus > 0";
    $muutkeikres = pupe_query($query);

    if (mysql_num_rows($muutkeikres) > 0) {
      echo "<tr><th colspan='6'>".t("Saapumiseen liitetyt ostolaskut:")."</th></tr>";
      echo "<tr><td colspan='6'>";

      $laskakak = 1;

      while ($muutkeikrow = mysql_fetch_assoc($muutkeikres)) {
        echo $laskakak.": <a href='{$palvelin2}raportit.php?toim=laskuhaku&tee=T&summa1=$muutkeikrow[tunnus]&lopetus=$PHP_SELF////toim=$toim//tee=$tee//asiakasid=$asiakasid//toimittajaid=$toimittajaid//laskunro=$laskunro//lasku_yhtio=$lasku_yhtio//tunnus=$tunnus//ytunnus=$ytunnus//nimi=$nimi//ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl/SPLIT/$lopetus'>$muutkeikrow[summa]</a> ".ebid($muutkeikrow['tunnus'])."<br>";
        $laskakak++;
      }

      echo "</td></tr>";
    }
  }
}

echo "</table>";

if ($verkkokauppa != "" and $muokkaa != "") {
  echo "<br><input type = 'submit' name='tallenna' value='".t("Tallenna muutokset")."'></form><br>";
}

$maksupaate_kassamyynti = (($yhtiorow['maksupaate_kassamyynti'] == 'K' and
    $kukarow["maksupaate_kassamyynti"] == "") or
  $kukarow["maksupaate_kassamyynti"] == "K");

if ($maksupaate_kassamyynti and $laskurow["kateinen"] != "") {
  require "inc/maksupaate_funktiot.inc";

  if (isset($kuitin_haku)) {
    if ($kuitin_haku["toiminto"] == "kauppias") {
      hae_kauppiaan_kuitti($kuitin_haku["tunnus"]);
    }
    else {
      hae_asiakkaan_kuitti($kuitin_haku["tunnus"]);
    }
  }

  $maksutapahtumat = hae_maksutapahtumat($tunnus);

  if ($maksutapahtumat) {
    piirra_maksutapahtumat($maksutapahtumat, $asiakasid, $laskunro, $lasku_yhtio, $tunnus, $ytunnus,
      $ppa, $kka, $vva, $ppl, $kkl, $vvl, $lopetus);
  }
}

echo "<br>";
echo "<strong>".t("Tilausrivit").":</strong><hr>";

if ($laskurow["tila"] == 'U' or $laskurow["tila"] == 'K') {
  $where = " tilausrivi.uusiotunnus = '$tunnus' ";
  $pkusei = " use index (uusiotunnus_index) ";
}
elseif ($laskurow["tila"] == 'R') {
  $query = "SELECT group_concat(tunnus) tunnukset
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = '$tunnus' and tila IN ('L','G','E','V','W','N','R')
            GROUP BY tunnusnippu";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  $where = " tilausrivi.otunnus IN ($row[tunnukset]) ";
}
else {
  $where = " tilausrivi.otunnus = '$tunnus' ";
}

$jarjestys = " toimaika, tilausrivi.tunnus ";

if (strlen($ojarj) > 0) {
  $jarjestys = $ojarj;
}
else {
  $jarjestys = "otunnus, sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus";
}

// katotaan miten halutaan sortattavan
$sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

if ($yhtiorow["varaako_jt_saldoa"] != "") {
  $lisavarattu = " + tilausrivi.varattu";
}
else {
  $lisavarattu = "";
}

if ($laskurow["tila"] == 'O' or $laskurow["tila"] == 'K') {

  $query_ale_lisa = generoi_alekentta('O');

  $ale_query_select_lisa = generoi_alekentta_select('yhteen', 'O');

  // Keikat ja ostotilausket
  $query = "SELECT tilausrivi.otunnus tilaus,
            tilausrivi.nimitys,
            concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
            tilausrivi.tuoteno,
            tuotteen_toimittajat.toim_tuoteno,
            round(tilausrivi.tilkpl, 4) tilattu_sis,
            round(tilausrivi.tilkpl*if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4) tilattu_ulk,
            round(tilausrivi.varattu,4) tulossa_sis,
            round(tilausrivi.varattu*if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4) tulossa_ulk,
            tilausrivi.kpl maara,
            concat_ws('<br>', if ('$laskurow[valkoodi]' != '$yhtiorow[valkoodi]', concat(tilausrivi.hinta, ' $laskurow[valkoodi]'), NULL), concat(round(tilausrivi.hinta*if ($laskurow[vienti_kurssi]=0, 1, $laskurow[vienti_kurssi]), '$yhtiorow[hintapyoristys]'), ' $yhtiorow[valkoodi]')) ostohinta,
            {$ale_query_select_lisa} ale,
            concat_ws('<br>', if ('$laskurow[valkoodi]' != '$yhtiorow[valkoodi]', concat(round((tilausrivi.varattu+tilausrivi.kpl)*tilausrivi.hinta*if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa},'$yhtiorow[hintapyoristys]'), ' $laskurow[valkoodi]'), NULL), concat(round((tilausrivi.varattu+tilausrivi.kpl)*tilausrivi.hinta*if ($laskurow[vienti_kurssi]=0, 1, $laskurow[vienti_kurssi])*if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin)*{$query_ale_lisa},'$yhtiorow[hintapyoristys]'), ' $yhtiorow[valkoodi]')) rivihinta,
            concat(round(tilausrivi.rivihinta, '$yhtiorow[hintapyoristys]'), ' $yhtiorow[valkoodi]') Varastonarvo,
            tilausrivi.laskutettuaika,
            tilausrivi.varattu,
            tilausrivi.kommentti,
            tilausrivi.tunnus,
            tilausrivi.uusiotunnus,
            tilausrivin_lisatiedot.hankintakulut,
            tilausrivin_lisatiedot.korvamerkinta,
            $sorttauskentta
            FROM tilausrivi
            JOIN tuote USING (yhtio, tuoteno)
            LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='$laskurow[liitostunnus]'
            LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
            WHERE $where
            and tilausrivi.yhtio='$kukarow[yhtio]'
            and tilausrivi.tyyppi in ('O' $deletedlisa)
            ORDER BY $jarjestys";
  $presult = pupe_query($query);

  echo "<table><tr>";

  if ($nippu > 0) {
    $linkkilisa = "$PHP_SELF?tee=NAYTATILAUS&toim=$toim&asiakasid=$asiakasid&toimittajaid=$toimittajaid&tunnus=$tunnus&nippu=$nippu&otunnus=$nippu&lopetus=$lopetus";
  }
  else {
    $linkkilisa = "$PHP_SELF?tee=NAYTATILAUS&toim=$toim&asiakasid=$asiakasid&toimittajaid=$toimittajaid&tunnus=$tunnus&ytunnus=$ytunnus&nimi=$nimi&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&lopetus=$lopetus";
  }

  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=1'>" .t("Tilaus")."</a></th>";
  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=2'>" .t("Nimitys")."</a></th>";
  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=3'>" .t("Paikka")."</a></th>";
  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=4'>" .t("Tuoteno")."</a></th>";
  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=5'>" .t("Toim_tuoteno")."</a></th>";
  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=6'>" .t("Tilattu")."<br>".t("sis/ulk")."</a></th>";
  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=8'>" .t("Tulossa")."<br>".t("sis/ulk")."</a></th>";
  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=10'>" .t("Varastossa")."</a></th>";
  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=11'>" .t("Hinta")."</a></th>";
  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=12'>".t("Ale")."</a></th>";
  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=13'>".t("Rivihinta")."</a></th>";
  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=14'>".t("Varastonarvo")."</a></th>";
  echo "<th align='left'><a href = '{$linkkilisa}&ojarj=15'>".t("Varastooonvientipvm")."</a></th>";
  echo "</tr>";

  $summa = array();
  $osuus_kululaskuista_yhteensa   = "";
  $osuus_eturahdista_yhteensa   = "";
  $osuus_pyoristyseroista_yhteensa = "";
  $aputullimaara_yhteensa     = "";
  $rivinlisakulu_yhteensa     = "";
  $saapumisenkulut_yhteensa = "";

  if ($lopetus != '') {
    $_lopetus_talteen       = $lopetus;
    $_lopetus_talteen_array = explode("/SPLIT/", $_lopetus_talteen);
    $lopetus_vika           = array_pop($_lopetus_talteen_array);
    $lopetus               .= "/SPLIT/";
  }

  if ($_tulostakopio) {
    $lopetus .= $palvelin2;
    $lopetus .= "tilauskasittely/tulostakopio.php";
    $lopetus .= "////toim=$toim";
    $lopetus .= "//tee=NAYTAHTML";
    $lopetus .= "//asiakasid=$asiakasid";
    $lopetus .= "//toimittajaid=$toimittajaid";
    $lopetus .= "//laskunro=$laskunro";
    $lopetus .= "//lasku_yhtio=$row[yhtio]";
    $lopetus .= "//ytunnus=$ytunnus";
    $lopetus .= "//tunnus=$tunnus";
    $lopetus .= "//ppa=$ppa";
    $lopetus .= "//kka=$kka";
    $lopetus .= "//vva=$vva";
    $lopetus .= "//ppl=$ppl";
    $lopetus .= "//kkl=$kkl";
    $lopetus .= "//vvl=$vvl";

    if (strpos($lopetus_vika, "tilauskasittely/tulostakopio.php") !== FALSE) {
      $lopetus = $_lopetus_talteen;
    }
  }
  elseif ($lahetteen_tulostusjono) {
    $lopetus .= $palvelin2;
    $lopetus .= "tilauskasittely/lahetteen_tulostusjono.php";
    $lopetus .= "////tee2=NAYTATILAUS";
    $lopetus .= "//toim=";
    $lopetus .= "//toim=";
    $lopetus .= "//lasku_yhtio=$row[yhtio]";
    $lopetus .= "//ytunnus=$ytunnus";
    $lopetus .= "//tunnus=$tunnus";
    $lopetus .= "//ppa=$ppa";
    $lopetus .= "//kka=$kka";
    $lopetus .= "//vva=$vva";
    $lopetus .= "//ppl=$ppl";
    $lopetus .= "//kkl=$kkl";
    $lopetus .= "//vvl=$vvl";
    $lopetus .= "//jarj=$jarj";
    $lopetus .= "//tuvarasto=$tuvarasto";
    $lopetus .= "//tumaa=$tumaa";
    $lopetus .= "//tutyyppi=$tutyyppi";
    $lopetus .= "//tutoimtapa=$tutoimtapa";
    $lopetus .= "//karajaus=$karajaus";
  }
  else {
    $lopetus .= $palvelin2;
    $lopetus .= "raportit/asiakkaantilaukset.php";
    $lopetus .= "////tee=NAYTATILAUS";
    $lopetus .= "//toim=OSTO";
    $lopetus .= "//asiakasid=$asiakasid";
    $lopetus .= "//toimittajaid=$toimittajaid";
    $lopetus .= "//laskunro=$laskunro";
    $lopetus .= "//lasku_yhtio=$row[yhtio]";
    $lopetus .= "//ytunnus=$ytunnus";
    $lopetus .= "//tunnus=$tunnus";
    $lopetus .= "//ppa=$ppa";
    $lopetus .= "//kka=$kka";
    $lopetus .= "//vva=$vva";
    $lopetus .= "//ppl=$ppl";
    $lopetus .= "//kkl=$kkl";
    $lopetus .= "//vvl=$vvl";

    if (strpos($lopetus_vika, "raportit/asiakkaantilaukset.php") !== FALSE) {
      $lopetus = $_lopetus_talteen;
    }
  }

  while ($prow = mysql_fetch_assoc($presult)) {

    echo "<tr class='aktiivi'>";
    echo "<td valign='top'>$prow[tilaus]</td>";
    echo "<td valign='top'>$prow[nimitys]</td>";
    echo "<td valign='top'>$prow[paikka]</td>";
    echo "<td valign='top'><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($prow["tuoteno"])."&lopetus=$lopetus'>$prow[tuoteno]</a></td>";
    echo "<td valign='top'>$prow[toim_tuoteno]</td>";
    echo "<td align='right' valign='top'>".($prow["tilattu_sis"]*1)."<br>".($prow["tilattu_ulk"]*1)."</td>";
    echo "<td align='right' valign='top'>".($prow["tulossa_sis"]*1)."<br>".($prow["tulossa_ulk"]*1)."</td>";
    echo "<td align='right' valign='top'>".($prow["maara"]*1)."</td>";
    echo "<td align='right' nowrap valign='top'>$prow[ostohinta]</td>";
    echo "<td align='right' valign='top'>".($prow["ale"]*1)."</td>";
    echo "<td align='right' nowrap valign='top'>$prow[rivihinta]</td>";
    echo "<td align='right' nowrap valign='top'>$prow[Varastonarvo]</td>";
    echo "<td align='right' nowrap valign='top'>".tv1dateconv($prow["laskutettuaika"])."</td>";

    $osuus_kululaskuista = $osuus_eturahdista = $osuus_pyoristyseroista = $tulliprossa = $aputullimaara = $rivinlisakulu = $saapumisenkulut = "";

    if (strpos($prow["hankintakulut"], "#") !== false) {
      list($osuus_kululaskuista, $osuus_eturahdista, $osuus_pyoristyseroista, $tulliprossa, $aputullimaara, $rivinlisakulu, $saapumisenkulut) = explode("#", $prow["hankintakulut"]);

      $osuus_kululaskuista_yhteensa += $osuus_kululaskuista;
      $osuus_eturahdista_yhteensa += $osuus_eturahdista;
      $osuus_pyoristyseroista_yhteensa += $osuus_pyoristyseroista;
      $aputullimaara_yhteensa += $aputullimaara;
      $rivinlisakulu_yhteensa += $rivinlisakulu;
      $saapumisenkulut_yhteensa += $saapumisenkulut;

      if ((float) $osuus_kululaskuista != 0) $prow["kommentti"] .= "\n".t("Kululaskut").": ".$osuus_kululaskuista;
      if ((float) $osuus_eturahdista != 0) $prow["kommentti"] .= "\n".t("Eturahti").": ".$osuus_eturahdista;
      if ((float) $osuus_pyoristyseroista != 0) $prow["kommentti"] .= "\n".t("Pyöristyserot").": ".$osuus_pyoristyseroista;
      if ((float) $tulliprossa != 0) $prow["kommentti"] .= "\n".t("Tulli%").": ".$tulliprossa;
      if ((float) $aputullimaara != 0) $prow["kommentti"] .= "\n".t("Tulli").": ".$aputullimaara;
      if ((float) $rivinlisakulu != 0) $prow["kommentti"] .= "\n".t("Lisäkulu").": ".$rivinlisakulu;
      if ((float) $saapumisenkulut != 0) $prow["kommentti"] .= "\n".t("Saapumisen kulut").": ".$saapumisenkulut;
    }

    echo "</tr>";

    //Haetaan sarjanumeron tiedot
    if ($prow["maara"] + $prow["varattu"] > 0) {
      $sarjanutunnus = "ostorivitunnus";
    }
    else {
      $sarjanutunnus = "myyntirivitunnus";
    }

    $query = "SELECT distinct sarjanumero
              from sarjanumeroseuranta
              where yhtio      = '$kukarow[yhtio]'
              and tuoteno      = '$prow[tuoteno]'
              and $sarjanutunnus='$prow[tunnus]'
              and sarjanumero != ''";
    $sarjares = pupe_query($query);

    $prow["kommentti"] = str_replace("\n", "<br>", trim($prow["kommentti"]));

    if ($prow["kommentti"] != '' and mysql_num_rows($sarjares) > 0) {
      $prow["kommentti"] .= "<br>";
    }

    while ($sarjarow = mysql_fetch_assoc($sarjares)) {
      $prow["kommentti"] .= "# <a href='{$palvelin2}tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($prow["tuoteno"])."&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."&lopetus=$lopetus'>$sarjarow[sarjanumero]</a><br>";
    }

    if ($prow["kommentti"] != '') {
      echo "<tr>";
      echo "<td valign='top'>".t("Kommentti").":</td>";
      echo "<td colspan='".(mysql_num_fields($presult)-6)."'>".trim($prow["kommentti"])."</td>";
      echo "</tr>";
    }

    //  Jos ollaan laskulla joka ei ole ennakkolasku
    if ($prow["tuoteno"] == $yhtiorow["ennakkomaksu_tuotenumero"]) {
      $ennakkosumma += $prow["ostohinta"];
    }
    else {
      list($s1, $s2) = explode("<br>", $prow["rivihinta"]);

      list($ss1, $sv1) = explode(" ", trim($s1));
      list($ss2, $sv2) = explode(" ", trim($s2));

      $summa[$sv1] += $ss1;
      if ($sv2 != "") $summa[$sv2] += $ss2;
    }
  }

  foreach ($summa as $val => $sum) {
    echo "<tr><td class='back' colspan='8'></td><td class='spec' colspan='2' align='right'>".t("Yhteensä").":</td><td class='spec' align='right'>".hintapyoristys($sum)." $val</td></tr>";
  }

  if ($osuus_kululaskuista_yhteensa != "" or $osuus_eturahdista_yhteensa != "" or $osuus_pyoristyseroista_yhteensa != "" or $aputullimaara_yhteensa != "" or $rivinlisakulu_yhteensa != "" or $saapumisenkulut_yhteensa != "") {
    if ((float) $osuus_kululaskuista_yhteensa != 0) echo "<tr><td class='back' colspan='8'></td><td class='spec' colspan='2' align='right'>".t("Kululaskut").":</td><td class='spec' align='right'>".$osuus_kululaskuista_yhteensa."</td></tr>";
    if ((float) $osuus_eturahdista_yhteensa != 0) echo "<tr><td class='back' colspan='8'></td><td class='spec' colspan='2' align='right'>".t("Eturahti").":</td><td class='spec' align='right'>".$osuus_eturahdista_yhteensa."</td></tr>";
    if ((float) $osuus_pyoristyseroista_yhteensa != 0) echo "<tr><td class='back' colspan='8'></td><td class='spec' colspan='2' align='right'>".t("Pyöristyserot").":</td><td class='spec' align='right'>".$osuus_pyoristyseroista_yhteensa."</td></tr>";
    if ((float) $aputullimaara_yhteensa != 0) echo "<tr><td class='back' colspan='8'></td><td class='spec' colspan='2' align='right'>".t("Tulli").":</td><td class='spec' align='right'>".$aputullimaara_yhteensa."</td></tr>";
    if ((float) $rivinlisakulu_yhteensa != 0) echo "<tr><td class='back' colspan='8'></td><td class='spec' colspan='2' align='right'>".t("Lisäkulu").":</td><td class='spec' align='right'>".$rivinlisakulu_yhteensa."</td></tr>";
    if ((float) $saapumisenkulut_yhteensa != 0) echo "<tr><td class='back' colspan='8'></td><td class='spec' colspan='2' align='right'>".t("Saapumisen kulut").":</td><td class='spec' align='right'>".$saapumisenkulut_yhteensa."</td></tr>";
  }
  echo "</table>";
}
else {
  // Myynnit ja muut myyntipuolen jutut

  // katotaan miten halutaan sortattavan
  $tilauksen_jarjestys = $yhtiorow['tilauksen_jarjestys'];

  if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
    $sorttauslisa = "tuotetyyppi, ";
  }
  elseif ($toim == 'EXTRANET') {
    $sorttauslisa = "tilausrivin_lisatiedot.positio, ";
  }
  else {
    if ($tilauksen_jarjestys == '0' or $tilauksen_jarjestys == '1' or $tilauksen_jarjestys == '4' or $tilauksen_jarjestys == '5') {
      $sorttauslisa = "tilausrivi.perheid $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.perheid2 $yhtiorow[tilauksen_jarjestys_suunta],";
    }
    else {
      $sorttauslisa = "";
    }
  }

  $sorttauskentta = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);

  $query_ale_lisa = generoi_alekentta('M');

  if ($laskurow["tila"] == "L" or $laskurow["tila"] == "N" or $laskurow["tila"] == "U") {
    $tyyppilisa = " and tilausrivi.tyyppi in ('L' $deletedlisa) ";
  }
  else {

    if ($toim == 'PROFORMA') {
      $tyyppilisa = " and tilausrivi.tyyppi in ('L','W','E') and ((tilausrivi.varattu+tilausrivi.jt != 0 and tilausrivi.laskutettuaika = '0000-00-00') or (tilausrivi.kpl != 0 and tilausrivi.laskutettuaika != '0000-00-00')) ";
    }
    else {
      // Onko käyttäjällä oikeus nähdä valmistuksia tai reseptejä
      $oikeu_t1 = tarkista_oikeus("tilauskasittely/tilaus_myynti.php", "VALMISTAVARASTOON");

      if ($yhtiorow["raaka_aineet_valmistusmyynti"] == "N") {
        $oikeu_t2 = FALSE;
      }
      else {
        $oikeu_t2 = tarkista_oikeus("tilauskasittely/tilaus_myynti.php", "VALMISTAASIAKKAALLE");
      }

      $oikeu_t3 = tarkista_oikeus("tilauskasittely/valmista_tilaus.php", "");
      $oikeu_t4 = tarkista_oikeus("tuoteperhe.php", "RESEPTI");

      if ($oikeu_t1 or $oikeu_t2 or $oikeu_t3 or $oikeu_t4) {
        $tyyppilisa = " and tilausrivi.tyyppi in ('E','L','G','V','W','T' $deletedlisa) ";
      }
      else {
        $tyyppilisa = " and tilausrivi.tyyppi in ('E','L','G','W','T' $deletedlisa) ";
      }
    }
  }

  // Tilausrivit
  $query = "SELECT tilausrivin_lisatiedot.*, tilausrivi.*,
            tilausrivi.kpl,
            if (tuotetyyppi='K','Työ','Varaosa') tuotetyyppi,
            tuote.myyntihinta,
            tuote.kehahin,
            tuote.sarjanumeroseuranta,
            tuote.tuoteno,
            tuote.myyntihinta_maara,
            tuote.status,
            tuote.ei_saldoa,
            tuote.valmistuslinja,
            if (tilausrivi.tyyppi='V', 0, tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) rivihinta,
            if (tilausrivi.tyyppi='V', 0, (tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) rivihinta_valuutassa,
            if (tilausrivi.tyyppi='V', 0, if (tilausrivi.kpl!=0, tilausrivi.rivihinta * if (tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1), tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa})) summa,
            if (tilausrivi.tyyppi='V', 0, (tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1)) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) summa_valuutassa,
            $sorttauskentta
            FROM tilausrivi
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
            LEFT JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno)
            LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
            WHERE $where
            and tilausrivi.yhtio='$kukarow[yhtio]'
            and tilausrivi.var != 'O'
            $tyyppilisa
            ORDER BY tilausrivi.otunnus, $sorttauslisa sorttauskentta $yhtiorow[tilauksen_jarjestys_suunta], tilausrivi.tunnus";
  $result = pupe_query($query);

  $rivilaskuri = mysql_num_rows($result);

  if ($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
    $rivino = 0;
  }
  else {
    $rivino = $rivilaskuri+1;
  }

  echo "<table>";
  echo "<tr><th>#</th>";

  if ($laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "T") {

    $trivityyppi_result = t_avainsana("TRIVITYYPPI");

    if (mysql_num_rows($trivityyppi_result) > 0) {
      echo "<th>".t("Tyyppi")."</th>";

      $tyyppisarake = "OK";
    }
    else {
      $tyyppisarake = "";
    }
  }

  echo "<th>".t("Nimitys")."</th>";

  if (($laskurow["tila"] != "T" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and ($kukarow['extranet'] == '' or $yhtiorow['varastopaikan_lippu'] != '')) {
    echo "  <th>".t("Paikka")."</th>";
  }

  echo "  <th>".t("Tuotenumero")."</th>
      <th>".t("Til. Määrä")."</th>
      <th>".t("Määrä")."</th>
      <th>".t("Tila")."</th>
      <th>".t("Netto")."</th>";

  $kurssilisa1 = "";
  $kurssilisa2 = "";

  if ($vientikurssi != 1) {
    $kurssilisa1 = " (Hinta val.)";
    $kurssilisa2 = " (Rivihinta val.)";
  }

  if ($kukarow['hinnat'] >= 0) echo "<th style='text-align:right;'>".t("Svh $kurssilisa1")."</th>";

  if ($kukarow['hinnat'] == 0) {
    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      echo "<th style='text-align:right;'>".t("Ale")."{$alepostfix}%</th>";
    }
  }

  if ($kukarow['hinnat'] == 0) echo "<th style='text-align:right;'>".t("Hinta")."</th>";
  if ($kukarow['hinnat'] >= 0) echo "<th style='text-align:right;'>".t("Rivihinta $kurssilisa2")."</th>";

  if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
    echo "<th style='text-align:right;'>".t("Kate")."</th>";
  }

  echo "<th style='text-align:right;'>".t("Alv%")."</th>";

  $naytetaan_valmistuslinja = false;

  if ($toim == "VALMISTUS") {
    $avainsanat = t_avainsana("VALMISTUSLINJA");

    echo "<th>".t("Reaalisaldo")."</th>";

    if (mysql_num_rows($avainsanat) > 0) {
      echo "<th style='text-align:right;'>".t("Valmistuslinja")."</th>";
      $naytetaan_valmistuslinja = true;
    }
  }

  echo "<td class='back'>&nbsp;</td>
  <td class='back'>&nbsp;</td>";


  echo "</tr>";

  $tuotetyyppi = "";
  $positio_varattu = "";
  $varaosatyyppi = "";
  $vanhaid = "KALA";
  $borderlask = 0;
  $pknum = 0;
  $erikoistuote_tuoteperhe = array();
  $tuoteperhe_kayty = '';
  $edotunnus = 0;
  $tuotekyslinkki = "";
  $lopetus_apu = "";
  $lopetus_vika = "";
  $_lopetus_talteen = "";
  $_lopetus_talteen_array = array();

  $summa_yht    = 0;
  $arvo_yht     = 0;
  $kate_yht     = 0;
  $ennas_yht    = 0;
  $ennaa_yht    = 0;
  $rihin_yht    = 0;

  $_myyntispessut = (in_array($toim, array('LASKU', 'TILAUSVAHVISTUS', 'LAHETE', 'TYOMAARAYS')));

  if ($lopetus != '') {
    $_lopetus_talteen       = $lopetus;
    $_lopetus_talteen_array = explode("/SPLIT/", $_lopetus_talteen);
    $lopetus_vika           = array_pop($_lopetus_talteen_array);
    $lopetus               .= "/SPLIT/";
  }

  if ($_tulostakopio) {
    $lopetus_apu .= $palvelin2;
    $lopetus_apu .= "tilauskasittely/tulostakopio.php";
    $lopetus_apu .= "////tee=NAYTAHTML";
    $lopetus_apu .= "//asiakasid=$asiakasid";
    $lopetus_apu .= "//toimittajaid=$toimittajaid";
    $lopetus_apu .= "//laskunro=$laskunro";
    $lopetus_apu .= "//lasku_yhtio=$row[yhtio]";
    $lopetus_apu .= "//ytunnus=$ytunnus";
    $lopetus_apu .= "//tunnus=$tunnus";
    $lopetus_apu .= "//ppa=$ppa";
    $lopetus_apu .= "//kka=$kka";
    $lopetus_apu .= "//vva=$vva";
    $lopetus_apu .= "//ppl=$ppl";
    $lopetus_apu .= "//kkl=$kkl";
    $lopetus_apu .= "//vvl=$vvl";
    $lopetus_apu .= "//toim=$toim";

    if (strpos($lopetus_vika, "tilauskasittely/tulostakopio.php") !== FALSE) {
      $lopetus = str_replace($lopetus_vika, "", $_lopetus_talteen) . "/SPLIT/" . $lopetus_apu;
    }
    else {
      $lopetus .= $lopetus_apu;
    }
  }
  elseif ($lahetteen_tulostusjono) {
    $lopetus .= $palvelin2;
    $lopetus .= "tilauskasittely/lahetteen_tulostusjono.php";
    $lopetus .= "////tee2=NAYTATILAUS";
    $lopetus .= "//toim=";
    $lopetus .= "//toim=";
    $lopetus .= "//lasku_yhtio=$row[yhtio]";
    $lopetus .= "//ytunnus=$ytunnus";
    $lopetus .= "//tunnus=$tunnus";
    $lopetus .= "//ppa=$ppa";
    $lopetus .= "//kka=$kka";
    $lopetus .= "//vva=$vva";
    $lopetus .= "//ppl=$ppl";
    $lopetus .= "//kkl=$kkl";
    $lopetus .= "//vvl=$vvl";
    $lopetus .= "//jarj=$jarj";
    $lopetus .= "//tuvarasto=$tuvarasto";
    $lopetus .= "//tumaa=$tumaa";
    $lopetus .= "//tutyyppi=$tutyyppi";
    $lopetus .= "//tutoimtapa=$tutoimtapa";
    $lopetus .= "//karajaus=$karajaus";
  }
  else {
    $lopetus_apu .= $palvelin2;
    $lopetus_apu .= "raportit/asiakkaantilaukset.php";
    $lopetus_apu .= "////tee=NAYTATILAUS";
    $lopetus_apu .= "//asiakasid=$asiakasid";
    $lopetus_apu .= "//toimittajaid=$toimittajaid";
    $lopetus_apu .= "//laskunro=$laskunro";
    $lopetus_apu .= "//lasku_yhtio=$row[yhtio]";
    $lopetus_apu .= "//ytunnus=$ytunnus";
    $lopetus_apu .= "//tunnus=$tunnus";
    $lopetus_apu .= "//ppa=$ppa";
    $lopetus_apu .= "//kka=$kka";
    $lopetus_apu .= "//vva=$vva";
    $lopetus_apu .= "//ppl=$ppl";
    $lopetus_apu .= "//kkl=$kkl";
    $lopetus_apu .= "//vvl=$vvl";

    if ($_myyntispessut) {
      $lopetus_apu .= "//toim=MYYNTI";
    }
    elseif ($_asiakkaantilaus) {
      $lopetus_apu .= "//toim=$toim";
    }
    else {
      $lopetus = '';
    }

    if (strpos($lopetus_vika, "raportit/asiakkaantilaukset.php") !== FALSE) {
      $lopetus = str_replace($lopetus_vika, "", $_lopetus_talteen) . "/SPLIT/" . $lopetus_apu;
    }
    else {
      $lopetus .= $lopetus_apu;
    }
  }

  while ($row = mysql_fetch_assoc($result)) {
    $linjannimi = "";

    if (strpos($row['sorttauskentta'], 'ÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖÖ') !== FALSE) {
      $erikoistuote_tuoteperhe[$row['perheid']] = $row['sorttauskentta'];
    }

    if ($row["tuoteno"] == $yhtiorow["ennakkomaksu_tuotenumero"]) {
      $ennas_yht += $row["summa_valuutassa"];
      $ennaa_yht += $row["rivihinta_valuutassa"];
    }
    else {

      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        if ($row["osto_vai_hyvitys"] != "O" and ($laskurow["tapvm"] == '0000-00-00' or ($row["var"] != "P" and $row["var"] != "J" and $row["var"] != "S"))) {
          $rihin_yht += $row["rivihinta_valuutassa"];
        }

        $arvo_yht  += round($row["rivihinta_valuutassa"], 2);
        $summa_yht += round($row["summa_valuutassa"], 2);
      }
      else {
        if ($row["osto_vai_hyvitys"] != "O" and ($laskurow["tapvm"] == '0000-00-00' or ($row["var"] != "P" and $row["var"] != "J" and $row["var"] != "S"))) {
          $rihin_yht += $row["rivihinta"];
        }

        $arvo_yht  += round($row["rivihinta"], 2);
        $summa_yht += round($row["summa"], 2);
      }
    }

    $tquery  = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
    $tres  = pupe_query($tquery);
    $trow   = mysql_fetch_assoc($tres); // haetaan tuotteen tiedot..

    //Haetaan sarjanumeron tiedot
    if ($laskurow["tila"] == 'O' or $laskurow["tila"] == 'K') {
      if ($row["kpl"] + $row["varattu"] > 0) {
        $sarjanutunnus = "ostorivitunnus";
      }
      else {
        $sarjanutunnus = "myyntirivitunnus";
      }
    }
    else {
      if ($laskurow["tila"] == "G") {
        $sarjanutunnus = "siirtorivitunnus";
      }
      elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["varattu"] + $row["kpl"] < 0 and $row["var2"] == "EIOST") {
        $sarjanutunnus = "ostorivitunnus";
      }
      elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["varattu"] + $row["kpl"] < 0) {
        $sarjanutunnus = "myyntirivitunnus";
      }
      elseif ($row["varattu"] + $row["kpl"] < 0) {
        $sarjanutunnus = "ostorivitunnus";
      }
      else {
        $sarjanutunnus = "myyntirivitunnus";
      }
    }

    $query = "SELECT distinct sarjanumero
              from sarjanumeroseuranta
              where yhtio      = '$kukarow[yhtio]'
              and tuoteno      = '$row[tuoteno]'
              and $sarjanutunnus='$row[tunnus]'
              and sarjanumero != ''";
    $sarjares = pupe_query($query);

    $row["kommentti"] = str_replace("\n", "<br>", trim($row["kommentti"]));

    if ($row["sarjanumeroseuranta"] != '') {
      if ($row["kommentti"] != '') {
        $row["kommentti"] .= "<br>";
      }
      else {
        $row["kommentti"] .= "&nbsp;";
      }
    }

    while ($sarjarow = mysql_fetch_assoc($sarjares)) {
      if ($kukarow['extranet'] == '') {
        $row["kommentti"] .= "# <a href='{$palvelin2}tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($row["tuoteno"])."&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."&lopetus=$lopetus'>$sarjarow[sarjanumero]</a><br>";
      }
      else {
        $row["kommentti"] .= "# $sarjarow[sarjanumero]<br>";
      }
    }

    if ($kukarow['extranet'] == '' and $row["toimitettuaika"] != '0000-00-00 00:00:00') {
      $row['kommentti'] .= "<br><font class='info'>".t("Toimitettu").": $row[toimitettu] ".tv1dateconv($row["toimitettuaika"], "P")."</font>";
    }

    if ($kukarow['extranet'] == '' and $row["kerattyaika"] != '0000-00-00 00:00:00') {
      $row['kommentti'] .= "<br><font class='info'>".t("Kerätty").": $row[keratty] ".tv1dateconv($row["kerattyaika"], "P")."</font>";
    }

    if ($laskurow["tilaustyyppi"] == "A") {
      if ($tuotetyyppi == "" and $row["tuotetyyppi"] == 'Työ') {
        $tuotetyyppi = 1;

        echo "<tr><td class='back' colspan='10'><br></td></tr>";
        echo "<tr><td class='back' colspan='10'><strong>".t("Työt")."</strong>:</td></tr>";
      }
    }

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      if ($row["ale{$alepostfix}"] == 0.00) $row["ale{$alepostfix}"] = '';
    }

    if ($row["hyllyalue"] == "") {
      $row["hyllyalue"] = "";
      $row["hyllynro"]  = "";
      $row["hyllyvali"] = "";
      $row["hyllytaso"] = "";
    }

    if ($row["var"] == "P") {
      $class = " class='spec' ";
    }
    elseif ($row["var"] == "J") {
      $class = " class='green' ";
    }
    else {
      $class = '';
    }

    if ($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
      $rivino++;
    }
    else {
      $rivino--;
    }

    // Tuoteperheiden lapsille ei näytetä rivinumeroa
    if ($tilauksen_jarjestys != 'M' and $tuoteperhe_kayty != $row['perheid'] and (($row['perheid'] != 0 and ($tilauksen_jarjestys == '1' or $tilauksen_jarjestys == '5' or ($tilauksen_jarjestys == '4' or $tilauksen_jarjestys == '0' and $erikoistuote_tuoteperhe[$row['perheid']] == $row['sorttauskentta']))) or $row["perheid"] == $row["tunnus"] or ($row["perheid2"] == $row["tunnus"] and $row["perheid"] == 0) or ($row["perheid2"] == -1 or ($row["perheid"] == 0 and $row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U"))))) {

      if ($row["perheid2"] == 0 and ($row["var"] == "T" or $row["var"] == "U")) {
        $pklisa = " and (tilausrivi.perheid = '$row[tunnus]' or tilausrivi.perheid2 = '$row[tunnus]' or tilausrivi.tunnus = '$row[tunnus]')";
      }
      elseif ($row["perheid"] == 0) {
        $pklisa = " and tilausrivi.perheid2 = '$row[perheid2]'";
      }
      else {
        $pklisa = " and (tilausrivi.perheid = '$row[perheid]' or tilausrivi.perheid2 = '$row[perheid]')";
      }

      $pkquery = "SELECT sum(if(tilausrivi.kommentti != ''
                  or tuote.sarjanumeroseuranta!=''
                  or (tilausrivi.kerattyaika != '0000-00-00 00:00:00' and '$kukarow[extranet]' = '')
                  or (tilausrivi.toimitettuaika != '0000-00-00 00:00:00' and '$kukarow[extranet]' = ''), 1, 0)), count(*)
                  FROM tilausrivi
                  JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
                  WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                  and tilausrivi.var     != 'O'
                  and $where
                  $pklisa
                  $tyyppilisa";
      $pkres = pupe_query($pkquery);
      $pkrow = mysql_fetch_row($pkres);

      $pknum = $pkrow[0] + $pkrow[1];
      $borderlask = $pkrow[1];

      echo "<tr>";

      $echorivino = $rivino;

      if ($yhtiorow['rivinumero_syotto'] != '') {
        if ($row['tilaajanrivinro'] != '' and $row['tilaajanrivinro'] != 0 and $echorivino != $row['tilaajanrivinro']) {
          $echorivino .= " &raquo; ($row[tilaajanrivinro])";
        }
      }

      // jos tuoteperheitä ei pidetä yhdessä, ei tehdä rowspannia eikä bordereita
      if ($tilauksen_jarjestys != '0' and $tilauksen_jarjestys != '1' and $tilauksen_jarjestys != '4' and $tilauksen_jarjestys != '5' and $tilauksen_jarjestys != '8') {
        $pknum     = 0;
        $borderlask = 0;

        if ($row["kommentti"] != "") {
          echo "<td valign='top' rowspan = '2' $class>$echorivino</td>";
        }
        else {
          echo "<td valign='top' $class>$echorivino</td>";
        }
      }
      else {
        if ($row['perheid'] != 0 and ($tilauksen_jarjestys == '1' or $tilauksen_jarjestys == '0' or $tilauksen_jarjestys == '4' or $tilauksen_jarjestys == '5')) {
          $tuoteperhe_kayty = $row['perheid'];
        }

        echo "<td valign='top' rowspan='$pknum' $class style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;'>$echorivino</td>";
      }
    }
    // normirivit tai jos tuoteperheitä ei pidetä yhdessä, näytetään lapsille rivinumerot
    elseif ($row["perheid"] == 0 and $row["perheid2"] == 0 or ($tilauksen_jarjestys != '0' and $tilauksen_jarjestys != '1' and $tilauksen_jarjestys != '4' and $tilauksen_jarjestys != '5' and $tilauksen_jarjestys != '8') or (($tilauksen_jarjestys == '0' or $tilauksen_jarjestys == '4') and $erikoistuote_tuoteperhe[$row['perheid']] == $row['sorttauskentta'] and $tuoteperhe_kayty != $row['perheid'])) {

      $echorivino = $rivino;

      if ($yhtiorow['rivinumero_syotto'] != '') {
        if ($row['tilaajanrivinro'] != '' and $row['tilaajanrivinro'] != 0 and $echorivino != $row['tilaajanrivinro']) {
          $echorivino .= " &raquo; ($row[tilaajanrivinro])";
        }
      }

      echo "<tr>";

      if ($row["kommentti"] != "") {
        echo "<td $class rowspan = '2' valign='top'>$echorivino";
      }
      elseif ($tilauksen_jarjestys == '1' and $row['perheid'] != 0) {
        echo "<td $class>&nbsp;</td>";
      }
      else {
        echo "<td  $class valign='top'>$echorivino";
      }

      echo "</td>";
      $borderlask    = 0;
      $pknum      = 0;
    }

    $vanhaid    = $row["perheid"];
    $trivityyulos  = "";
    $classlisa    = "";

    if ($borderlask == 1 and $pkrow[1] == 1 and $pknum == 1) {
      $classlisa = $class." style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
      $class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

      $borderlask--;
    }
    elseif (isset($pkrow) and $borderlask == $pkrow[1] and $pkrow[1] > 0) {
      $classlisa = $class." style='border-top: 1px solid; border-right: 1px solid;' ";
      $class    .= " style='border-top: 1px solid;' ";

      $borderlask--;
    }
    elseif ($borderlask == 1) {
      if ($row["kommentti"] != '') {
        $classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
        $class    .= " style='font-style:italic; ' ";
      }
      else {
        $classlisa = $class." style='font-style:italic; border-bottom: 1px solid; border-right: 1px solid;' ";
        $class    .= " style='font-style:italic; border-bottom: 1px solid;' ";
      }

      $borderlask--;
    }
    elseif ($borderlask > 0 and $borderlask <= $pknum) {
      $classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
      $class    .= " style='font-style:italic;' ";
      $borderlask--;
    }

    if (isset($tyyppisarake) and $tyyppisarake == "OK" and ($laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "T")) {

      echo "<td $class valign='top'>";

      if (mysql_num_rows($trivityyppi_result) > 0) {
        //annetaan valita tilausrivin tyyppi
        mysql_data_seek($trivityyppi_result, 0);

        while ($trrow = mysql_fetch_assoc($trivityyppi_result)) {
          $sel = "";
          if ($trrow["selite"]==$row["positio"]) echo "$trrow[selitetark]";
        }
      }

      echo "</td>";
    }

    echo "<td $class align='left' valign='top'>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";

    if (($laskurow["tila"] != "T" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $kukarow['extranet'] == '') {

      $query = "SELECT *
                FROM varastopaikat
                WHERE
                concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0')) and
                concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0'))
                and yhtio = '$kukarow[yhtio]'";
      $varesult = pupe_query($query);
      $varow = mysql_fetch_assoc($varesult);

      if ($varow['maa'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {
        echo "<td $class align='left' valign='top'><font class='error'><img src='{$palvelin2}pics/flag_icons/gif/".strtolower($varow['maa']).".gif'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font>";
      }
      elseif ($varow['maa'] != '' and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
        echo "<td $class align='left' valign='top'><font class='error'>".strtoupper($varow['maa'])." $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]</font>";
      }
      else {
        echo "<td $class align='left' valign='top'> $row[hyllyalue] $row[hyllynro] $row[hyllyvali] $row[hyllytaso]";
      }

      if (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and !in_array($row["var"], array('P', 'J', 'S', 'T', 'U'))) {
        $query = "SELECT sarjanumeroseuranta.sarjanumero era, sarjanumeroseuranta.parasta_ennen
                  FROM sarjanumeroseuranta
                  WHERE yhtio          = '$kukarow[yhtio]'
                  and tuoteno          = '$row[tuoteno]'
                  and myyntirivitunnus = '$row[tunnus]'
                  LIMIT 1";
        $sarjares = pupe_query($query);
        $sarjarow = mysql_fetch_assoc($sarjares);

        echo ", $sarjarow[era]";

        if ($trow["sarjanumeroseuranta"] == "F") {
          echo " ".tv1dateconv($sarjarow["parasta_ennen"]);
        }
      }

      echo "</td>";
    }
    elseif (($laskurow["tila"] != "T" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and $kukarow['extranet'] != '' and $yhtiorow['varastopaikan_lippu'] != '') {
      $query = "SELECT *
                FROM varastopaikat
                WHERE
                concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0')) and
                concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$row[hyllyalue]') ,5,'0'),lpad(upper('$row[hyllynro]') ,5,'0'))
                and yhtio = '$kukarow[yhtio]'";
      $varesult = pupe_query($query);
      $varow = mysql_fetch_assoc($varesult);

      if ($varow['maa'] != '' ) {
        echo "<td $class align='left' valign='top'><img src='{$palvelin2}pics/flag_icons/gif/".strtolower($varow['maa']).".gif'></td>";
      }
      else {
        echo "<td $class align='left' valign='top'></td>";
      }
    }

    if ($kukarow['extranet'] == '') {
      echo "<td $class valign='top'><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."&lopetus=$lopetus'>$row[tuoteno]</a></td>";
    }
    else {
      echo "<td $class valign='top'>$row[tuoteno]</td>";
    }

    echo "<td $class align='right' valign='top' nowrap>".($row["tilkpl"]*1)."</td>";

    if ($row["kpl"] != 0) {
      $kpl_ruudulle = $row['kpl'] * 1;
    }
    elseif ($row["var"] == 'J') {
      $kpl_ruudulle = $row['jt'] * 1;
    }
    elseif ($row["var"] == 'S' or $row["var"] == 'T' or $row["var"] == 'U') {
      $kpl_ruudulle = $row['jt'] * 1;
    }
    elseif ($row["var"] == 'P') {
      $kpl_ruudulle = $row['tilkpl'] * 1;
    }
    else {
      $kpl_ruudulle = $row['varattu'] * 1;
    }

    echo "<td $class align='right' valign='top' nowrap>$kpl_ruudulle</td>";

    if ($toim != "VALMISTAVARASTOON" and $toim != "SIIRTOLISTA") {
      $classvar = $class;
    }
    else {
      if ($classlisa != "") {
        $classvar = $classlisa;
      }
      else {
        $classvar = $class;
      }
    }

    echo "<td $classvar align='center' valign='top'>";

    $var_temp = var_kaannos($row['var']);

    echo $var_temp . "&nbsp;";
    if (!empty($row['korvamerkinta'])) {
      if ($row['korvamerkinta'] == '.') {
        $luokka = '';
      }
      else {
        $luokka = 'tooltip';
      }
      echo "<img src='{$palvelin2}pics/lullacons/info.png' class='{$luokka}' id='{$row['tunnus']}_info'>";
      echo "<div id='div_{$row['tunnus']}_info' class='popup'>";
      echo $row['korvamerkinta'];
      echo "</div>";
    }
    echo "&nbsp;</td>";

    echo "<td $class align='center' valign='top'>$row[netto]&nbsp;</td>";

    $kpl = $row["varattu"]+$row["jt"]+$row['kpl'];
    $myyntihinta = hintapyoristys(tuotteen_myyntihinta($laskurow, $trow, 1));
    $bruttorivi = $row["hinta"] * $kpl;

    if ($kukarow['hinnat'] == 1) {
      echo "<td $class align='right' valign='top' nowrap>$myyntihinta</td>";
    }
    elseif ($kukarow['hinnat'] == 0) {

      $kurssilisa = "";

      if ($vientikurssi != 1) {
        $kurssilisa = " (".hintapyoristys($row["hinta"]/$vientikurssi).")";
      }

      if ($myyntihinta != $row["hinta"]) $myyntihinta = hintapyoristys($myyntihinta)." $kurssilisa";
      else $myyntihinta = hintapyoristys($myyntihinta);

      echo "<td $class align='right' valign='top' nowrap>$myyntihinta</td>";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        echo "<td $class align='right' valign='top' nowrap>".($row["ale{$alepostfix}"] * 1)."</td>";
      }

      echo "<td $class align='right' valign='top' nowrap>".hintapyoristys($row["hinta"]);

      if ($row["myyntihinta_maara"] > 1) {
        echo "<br>".hintapyoristys($row["hinta"]*$row["myyntihinta_maara"])." / $row[myyntihinta_maara]";
      }

      echo "</td>";
    }

    if ($kukarow['hinnat'] == 1) {
      echo "<td $class align='right' valign='top' nowrap>".hintapyoristys($bruttorivi)."</td>";
    }
    elseif ($kukarow['hinnat'] == 0) {

      if ($yhtiorow["alv_kasittely"] == "") {
        //verolliset hinnat
        $kurssilisa = "";
        if ($vientikurssi != 1) {
          $kurssilisa = " (".hintapyoristys($row["summa_valuutassa"]).")";
        }
        echo "<td $class align='right' valign='top' nowrap>".hintapyoristys($row["summa"])."$kurssilisa</td>";
      }
      else {
        $kurssilisa = "";
        if ($vientikurssi != 1) {
          $kurssilisa = " (".hintapyoristys($row["rivihinta_valuutassa"]).")";
        }
        echo "<td $class align='right' valign='top' nowrap>".hintapyoristys($row["rivihinta"])."$kurssilisa</td>";
      }
    }

    if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
      // Tän rivin kate
      $kate = 0;

      if ($laskurow["tapvm"] != '0000-00-00') {

        if ($row["kpl"] == 0) {
          $kate = "";
        }
        elseif ($row["rivihinta"] != 0) {
          $row["kate"] = kate_kuluineen($row["tuoteno"], $row["rivihinta"], $row["kate"]);
          if ($row["kate"] < 0) {
            $kate = sprintf('%.2f', -1 * abs(100 * $row["kate"] / $row["rivihinta"]))."%";
          }
          else {
            $kate = sprintf('%.2f', abs(100 * $row["kate"] / $row["rivihinta"]))."%";
          }
        }
        elseif ($row["kate"] != 0) {
          $kate = "-100.00%";
        }

        if ($row["tyyppi"] != "V" and $row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += $row["kate"];
      }
      elseif ($kukarow['extranet'] == '' and ($row["sarjanumeroseuranta"] == "S")) {
        if ($kpl > 0) {
          //Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on myyntiä
          $ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $row["tunnus"]);
          $ostohinta = hinta_kuluineen($row["tuoteno"], $ostohinta);
          // Kate = Hinta - Ostohinta
          if ($row["rivihinta"] != 0) {
            $kate = sprintf('%.2f', 100*($row["rivihinta"] - ($ostohinta * $kpl))/$row["rivihinta"])."%";
          }

          if ($row["tyyppi"] != "V" and $row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += ($row["rivihinta"] - ($ostohinta * $kpl));
        }
        elseif ($kpl < 0 and $row["osto_vai_hyvitys"] == "O") {
          //Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on OSTOA

          // Kate = 0
          $kate = "0%";
        }
        elseif ($kpl < 0 and $row["osto_vai_hyvitys"] == "") {
          //Jos tuotteella ylläpidetään in-out varastonarvo ja kyseessä on HYVITYSTÄ

          //Tähän hyvitysriviin liitetyt sarjanumerot
          $query = "SELECT sarjanumero, kaytetty
                    FROM sarjanumeroseuranta
                    WHERE yhtio        = '$kukarow[yhtio]'
                    and ostorivitunnus = '$row[tunnus]'";
          $sarjares = pupe_query($query);

          $ostohinta = 0;

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {

            // Haetaan hyvitettävien myyntirivien kautta alkuperäiset ostorivit
            $query = "SELECT tilausrivi.rivihinta/tilausrivi.kpl ostohinta
                      FROM sarjanumeroseuranta
                      JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
                      WHERE sarjanumeroseuranta.yhtio          = '$kukarow[yhtio]'
                      and sarjanumeroseuranta.tuoteno          = '$row[tuoteno]'
                      and sarjanumeroseuranta.sarjanumero      = '$sarjarow[sarjanumero]'
                      and sarjanumeroseuranta.kaytetty         = '$sarjarow[kaytetty]'
                      and sarjanumeroseuranta.myyntirivitunnus > 0
                      and sarjanumeroseuranta.ostorivitunnus   > 0
                      ORDER BY sarjanumeroseuranta.tunnus
                      LIMIT 1";
            $sarjares1 = pupe_query($query);
            $sarjarow1 = mysql_fetch_assoc($sarjares1);
            $oh = hinta_kuluineen($arow['tuoteno'], $sarjarow1['ostohinta']);
            $ostohinta += $oh;
          }

          // Kate = Hinta - Alkuperäinen ostohinta
          if ($row["rivihinta"] != 0) {
            $kate = sprintf('%.2f', 100 * ($row["rivihinta"]*-1 - $ostohinta)/$row["rivihinta"])."%";
          }
          else {
            $kate = "100.00%";
          }

          if ($row["tyyppi"] != "V" and $row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += ($row["rivihinta"]*-1 - $ostohinta);
        }
        else {
          $kate = "N/A";
        }
      }
      elseif ($kukarow['extranet'] == '') {

        if ($row["tyyppi"] == "V") {
          $kate = "";
        }
        elseif ($row["rivihinta"] != 0) {
          $khh = kehahin($row["tuoteno"]);
          $khh = hinta_kuluineen($row["tuoteno"], $khh);
          $kate = round(100 * ($row["rivihinta"] - ($khh*($row["varattu"]+$row["jt"]+$row["kpl"]))) / $row["rivihinta"], 2) . "%";
        }
        elseif (kehahin($row["tuoteno"]) != 0) {
          $kate = "-100.00%";
        }

        if ($row["tyyppi"] != "V" and $row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) {
          $khh = kehahin($row["tuoteno"]);
          $khh = hinta_kuluineen($row["tuoteno"], $khh);

          $kate_yht += ($row["rivihinta"] - ($khh*($row["varattu"]+$row["jt"]+$row['kpl'])));
        }
      }

      echo "<td $class align='right' valign='top' nowrap>$kate</td>";
    }

    if ($classlisa != "") {
      $classx = $classlisa;
    }
    else {
      $classx = $class;
    }

    if ($row["alv"] >= 600) {
      echo "<td $classx align='right' valign='top' nowrap>".t("K.V.")."</td>";
    }
    elseif ($row["alv"] >= 500) {
      echo "<td $classx align='right' valign='top' nowrap>".t("M.V.")."</td>";
    }
    else {
      echo "<td $classx align='right' valign='top' nowrap>".($row["alv"] * 1)."</td>";
    }

    if ($toim == "VALMISTUS") {

      if ($row["ei_saldoa"] != "") {

        echo "<td style='text-align:right;'>".t("Saldoton")."</td>";

        if ($naytetaan_valmistuslinja) {
          $linjannimi = t_avainsana("VALMISTUSLINJA", '', " and avainsana.selite='{$row["valmistuslinja"]}'", '', '', "selitetark");
          if ($linjannimi == "") $linjannimi = t("Ei Linjaa");
          echo "<td>{$linjannimi}</td>";
        }
      }
      else {

        list ($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"]);

        $tilauksessa = "SELECT sum(tilausrivi.varattu) tulossa
                        FROM tilausrivi
                        WHERE tilausrivi.yhtio  = '{$kukarow["yhtio"]}'
                        AND tilausrivi.tuoteno  = '{$row["tuoteno"]}'
                        AND tilausrivi.tyyppi   = 'O'
                        and tilausrivi.var     != 'O'
                        AND tilausrivi.varattu  > 0";
        $tilres = pupe_query($tilauksessa);
        $tilrivi = mysql_fetch_assoc($tilres);

        $reaalisaldo = $myytavissa + $tilrivi["tulossa"];

        if ($row["status"] == "T") {
          echo "<td style='text-align:right;'>".t("Tehdastilaustuote")."</td>";
        }
        else {
          echo "<td style='text-align:right;'>$reaalisaldo</td>";
        }

        if ($naytetaan_valmistuslinja) {
          $linjannimi = t_avainsana("VALMISTUSLINJA", '', " and avainsana.selite='{$row["valmistuslinja"]}'", '', '', "selitetark");
          if ($linjannimi == "") $linjannimi = t("Ei Linjaa");
          echo "<td>{$linjannimi}</td>";
        }

        if ($yhtiorow["teeostotilaus_valmistuksen_tulosjonosta"] == "K" and $row["status"] == "T") {

          // haetaan tuotteen toimittajan tiedot ja datat
          $query2 = "SELECT tt.liitostunnus, if (tt.jarjestys = 0, 9999, tt.jarjestys) sorttaus, tt.osto_era
                     FROM tuotteen_toimittajat as tt
                     WHERE tt.yhtio = '{$kukarow["yhtio"]}'
                     AND tt.tuoteno = '{$row["tuoteno"]}'
                     ORDER BY sorttaus
                     LIMIT 1";
          $result2   = pupe_query($query2);
          $toimirow2 = mysql_fetch_assoc($result2);

          if (!isset($toimittajan_ostotilaus[$toimirow2["liitostunnus"]][$row["tuoteno"]]["tarve"])) $toimittajan_ostotilaus[$toimirow2["liitostunnus"]][$row["tuoteno"]]["tarve"] = 0;

          $toimittajan_ostotilaus[$toimirow2["liitostunnus"]][$row["tuoteno"]]["tarve"] += $row["tilkpl"];
          $toimittajan_ostotilaus[$toimirow2["liitostunnus"]][$row["tuoteno"]]["osto_era"] = $toimirow2['osto_era'];
          $toimittajan_ostotilaus[$toimirow2["liitostunnus"]][$row["tuoteno"]]["reaalisaldo"] = $reaalisaldo;
          $toimittajan_ostotilaus[$toimirow2["liitostunnus"]][$row["tuoteno"]]["nimitys"] = $row["nimitys"];
        }
      }
    }

    if ($verkkokauppa != "" and $muokkaa != "") {
      echo "<td class='back'><input type = 'button' onclick = \"if (confirm('".t("Oletko varma, että haluat mitätöidä tilausrivin?")."')) { sndReq('selain', 'verkkokauppa.php?tee=poistarivi&rivitunnus=$row[tunnus]&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki'); }\" value='".t("Mitätöi rivi")."'></td>";
    }

    echo "</tr>";

    if ($row['kommentti'] != '') {
      $cspan = 11;

      if ($kukarow['hinnat'] == 1) {
        $cspan-=2;
      }
      if ($kukarow['hinnat'] == -1) {
        $cspan-=4;
      }

      if (($laskurow["tila"] != "T" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and ($kukarow['extranet'] == '' or $yhtiorow['varastopaikan_lippu'] != '')) {
        $cspan++;
      }
      if (isset($tyyppisarake) and $tyyppisarake == "OK" and ($laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "T")) {
        $cspan++;
      }
      if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
        $cspan++;
      }

      $cspan += $yhtiorow['myynnin_alekentat'] - 1;

      echo "<tr>";

      if ($borderlask == 0 and $pknum > 1) {
        $kommclass1 = " style='border-bottom: 1px solid; border-right: 1px solid;'";
      }
      elseif ($pknum > 0) {
        $kommclass1 = " style='border-right: 1px solid;'";
      }
      else {
        $kommclass1 = "";
      }

      $rivikommentti = str_replace("\n", "<br>", $row["kommentti"]);

      if (preg_match("/\bAlkuperäinen tilaus: ([0-9]+)\b/", $rivikommentti, $osumat)) {
        $tilaustunnus = $osumat[1];
        $linkki = "<a href='asiakkaantilaukset.php?tee=NAYTATILAUS&toim=MYYNTI&asiakasid=$asiakasid&lasku_yhtio=$lasku_yhtio&tunnus=$tilaustunnus&ytunnus=$ytunnus&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl'>" . $tilaustunnus . "</a>";
        $rivikommentti = str_replace($tilaustunnus, $linkki, $rivikommentti);
      }

      if (preg_match("/\bToimitettu tilauksella: ([0-9]+)\b/", $rivikommentti, $osumat)) {
        $tilaustunnus = $osumat[1];
        $linkki = "<a href='asiakkaantilaukset.php?tee=NAYTATILAUS&toim=MYYNTI&asiakasid=$asiakasid&lasku_yhtio=$lasku_yhtio&tunnus=$tilaustunnus&ytunnus=$ytunnus&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl'>" . $tilaustunnus . "</a>";
        $rivikommentti = str_replace($tilaustunnus, $linkki, $rivikommentti);
      }

      echo "<td $kommclass1 colspan='$cspan' valign='top'>".t("Kommentti").":<br><font class='black'>".$rivikommentti."</font></td>";
      echo "</tr>";
    }
  }

  if ($kukarow['hinnat'] >= 0) {
    $cspan = 7;

    if ($kukarow['hinnat'] == 1) {
      $cspan-=2;
    }
    if ($kukarow['hinnat'] == -1) {
      $cspan-=4;
    }

    if (($laskurow["tila"] != "T" or $yhtiorow['tarjouksen_tuotepaikat'] == "") and ($kukarow['extranet'] == '' or $yhtiorow['varastopaikan_lippu'] != '')) {
      $cspan++;
    }
    if (isset($tyyppisarake) and $tyyppisarake == "OK" and ($laskurow["tila"] == "T" or $laskurow["tilaustyyppi"] == "T")) {
      $cspan++;
    }
    if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
      $cspan++;
    }

    $cspan += $yhtiorow['myynnin_alekentat'] - 1;

    echo "<tr>
        <td class='back' colspan='$cspan'></td>
        <td class='spec' colspan='3' align='right' nowrap>".t("Veroton yhteensä").":</td>
        <td class='spec' align='right'>".sprintf('%.2f', $arvo_yht)."</td>
        <td class='spec'>$laskurow[valkoodi]</td>
      </tr>";


    // Haetaan kaikki alvikannat riveiltä
    $alvquery = "SELECT distinct alv
                 FROM tilausrivi
                 WHERE $where
                 and yhtio    = '$kukarow[yhtio]'
                 and var     != 'O'
                 $tyyppilisa
                 and tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]'
                 ORDER BY alv";
    $alvresult = pupe_query($alvquery);

    $query_ale_lisa = generoi_alekentta('M');

    while ($alvrow = mysql_fetch_assoc($alvresult)) {

      // Valuuttajutut kuntoon
      if ($alvrow["alv"] >= 500) {
        $aquery = "SELECT
                   round(sum(0),2) alvrivihinta,
                   round(sum((tilausrivi.hinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*(tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl)*{$query_ale_lisa}),2) rivihinta
                   FROM tilausrivi
                   JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                   WHERE $where
                   and tilausrivi.yhtio  = '$kukarow[yhtio]'
                   and tilausrivi.alv    = '$alvrow[alv]'
                   and tilausrivi.var   != 'O'
                   $tyyppilisa
                   and tuoteno          != '$yhtiorow[ennakkomaksu_tuotenumero]'";
      }
      else {
        $aquery = "SELECT
                   round(sum((tilausrivi.hinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa} * (tilausrivi.alv/100)),2) alvrivihinta,
                   round(sum((tilausrivi.hinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}),2) rivihinta
                   FROM tilausrivi
                   JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                   WHERE $where
                   and tilausrivi.yhtio  = '$kukarow[yhtio]'
                   and tilausrivi.alv    = '$alvrow[alv]'
                   and tilausrivi.var   != 'O'
                   $tyyppilisa
                   and tuoteno          != '$yhtiorow[ennakkomaksu_tuotenumero]'";
      }

      $aresult = pupe_query($aquery);
      $arow = mysql_fetch_assoc($aresult);

      if ($alvrow["alv"] >= 600) {
        $alvi = t("K.V.");
      }
      elseif ($alvrow["alv"] >= 500) {
        $alvi = t("M.V.");
      }
      else {
        $alvi = ($alvrow["alv"]*1)."%";
      }

      echo "<tr><td class='back' colspan='$cspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Arvonlisävero", $kieli)." (".sprintf('%.2f', $arow["rivihinta"])."):</td><td align='right' class='spec' nowrap>".sprintf('%.2f', $arow["alvrivihinta"])."</td><td class='spec'>$alvi</td></tr>";
    }

    echo "<tr><td class='back' colspan='$cspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Verollinen yhteensä").":</td><td class='spec' align='right' nowrap>".sprintf('%.2f', $summa_yht)."</td><td class='spec'>$laskurow[valkoodi]</td></tr>";

    if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {

      if ($kate_yht < 0) {
        $ykate = @round(-1 * abs($kate_yht / $rihin_yht * 100), 2);
      }
      else {
        $ykate = @round(abs($kate_yht / $rihin_yht * 100), 2);
      }

      echo "<tr><td class='back' colspan='$cspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Kate yhteensä").":</td><td class='spec' align='right' nowrap>".sprintf('%.2f', $ykate)."</td><td class='spec'>%</td></tr>";
    }

    if ($ennaa_yht != 0) {
      echo "<tr><td class='back'><br></td></tr>";
      echo "<tr><td class='back' colspan='$cspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Ennakkolaskutus").":</td><td class='spec' align='right' nowrap>".sprintf('%.2f', $ennaa_yht)."</td><td class='spec'>$laskurow[valkoodi]</td></tr>";

      //Haetaan kaikki alvikannat riveiltä
      $alvquery = "SELECT distinct alv
                   FROM tilausrivi
                   WHERE $where
                   and yhtio    = '$kukarow[yhtio]'
                   and var     != 'O'
                   $tyyppilisa
                   and tuoteno  = '$yhtiorow[ennakkomaksu_tuotenumero]'
                   ORDER BY alv";
      $alvresult = pupe_query($alvquery);

      while ($alvrow = mysql_fetch_assoc($alvresult)) {

        // Valuuttajutut kuntoon
        if ($alvrow["alv"] >= 500) {
          $aquery = "SELECT
                     round(sum(0),2) alvrivihinta,
                     round(sum((tilausrivi.hinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1))*(tilausrivi.varattu+tilausrivi.kpl)*{$query_ale_lisa}),2) rivihinta
                     FROM tilausrivi
                     JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                     WHERE $where
                     and tilausrivi.yhtio  = '$kukarow[yhtio]'
                     and tilausrivi.alv    = '$alvrow[alv]'
                     and tilausrivi.var   != 'O'
                     $tyyppilisa
                     and tuoteno           = '$yhtiorow[ennakkomaksu_tuotenumero]'";
        }
        else {
          $aquery = "SELECT
                     round(sum((tilausrivi.hinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa} * (tilausrivi.alv/100)),2) alvrivihinta,
                     round(sum((tilausrivi.hinta/if (lasku.vienti_kurssi>0, lasku.vienti_kurssi, 1)) / if ('$yhtiorow[alv_kasittely]' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.kpl) * {$query_ale_lisa}),2) rivihinta
                     FROM tilausrivi
                     JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                     WHERE $where
                     and tilausrivi.yhtio  = '$kukarow[yhtio]'
                     and tilausrivi.alv    = '$alvrow[alv]'
                     and tilausrivi.var   != 'O'
                     $tyyppilisa
                     and tuoteno           = '$yhtiorow[ennakkomaksu_tuotenumero]'";
        }

        $aresult = pupe_query($aquery);
        $arow = mysql_fetch_assoc($aresult);

        if ($alvrow["alv"] >= 600) {
          $alvi = t("K.V.");
        }
        elseif ($alvrow["alv"] >= 500) {
          $alvi = t("M.V.");
        }
        else {
          $alvi = ($alvrow["alv"]*1)."%";
        }

        echo "<tr><td class='back' colspan='$cspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Arvonlisävero", $kieli)." (".sprintf('%.2f', $arow["rivihinta"])."):</td><td align='right' class='spec' nowrap>".sprintf('%.2f', $arow["alvrivihinta"])."</td><td class='spec'>$alvi</td></tr>";
      }

      echo "<tr><td class='back' colspan='$cspan'></td><td class='spec' colspan='3' align='right' nowrap>".t("Yhteensä").":</td><td align='right' class='spec' nowrap>".sprintf('%.2f', $ennas_yht)."</td><td class='spec'>$laskurow[valkoodi]</td></tr>";
    }
  }

  echo "</table>";
}

// Tehdään yhteistilaus.
if ($yhtiorow["teeostotilaus_valmistuksen_tulosjonosta"] == "K") {

  if (!function_exists("tuot_toim")) {
    function tuot_toim($liitostunnus) {
      global $kukarow, $yhtiorow;
      // Haetaan tuotteen toimittajan liitostunnuksella toimitaulusta kaikki tiedot.
      // halutaan näyttää rivillä täydellinen toimittajanimi pelkän tunnuksen sijasta.
      $toimsql = "SELECT * from toimi where yhtio = '{$kukarow["yhtio"]}' and tunnus = '{$liitostunnus}'";
      $toimres = pupe_query($toimsql);

      if (mysql_num_rows($toimres) >1) {
        return FALSE;
      }
      else {
        $toimrivit = mysql_fetch_assoc($toimres);
        return $toimrivit;
      }
    }
  }

  echo "<font class='head'>".t("Tilaa valmistuksen tehdastilaustuotteet")."</font><hr>";

  $toimittajan_ostotilaus_indeksi = 0;

  if (count($toimittajan_ostotilaus) == 0) {
    echo "<font class='message'>".t("Ei tehdastilaustuotteita")."</font>";
  }

  echo "<table>";

  foreach ($toimittajan_ostotilaus as $toimittajan_tunnus => $toimittajan_ostotilaus_array) {

    $toimittaja = tuot_toim($toimittajan_tunnus);

    echo "<tr>";
    echo "<td class='back' colspan='5'><font class='message'><br>{$toimittaja["nimi"]}</font></th>";
    echo "<tr>";

    echo "<tr>";
    echo "<th>".t("Tuoteno")."</th>";
    echo "<th>".t("Nimitys")."</th>";
    echo "<th>".t("Tarve")."</th>";
    echo "<th>".t("Reaalisaldo")."</th>";
    echo "<th></th>";
    echo "<tr>";

    foreach ($toimittajan_ostotilaus_array as $tuotenumero => $tuote_array) {

      // Muuttujat
      $tarve = $tuote_array["tarve"];
      $ostoera = $tuote_array["osto_era"];
      $reaalisaldo = $tuote_array["reaalisaldo"];
      $nimitys = $tuote_array["nimitys"];

      echo "<tr>";
      echo "<td>$tuotenumero</td>";
      echo "<td>$nimitys</td>";
      echo "<td align='right'>$tarve</td>";
      echo "<td align='right'>$reaalisaldo</td>";

      // Jos ei ole ostoerää, niin yksi
      if ((int) $ostoera == 0) {
        $ostoera = 1;
      }

      // Pyöristetään tilattava määrä ylöspäin seuraavaan ostoerään
      $tilattava_maara = ceil($tarve / $ostoera) * $ostoera;

      // mikäli muutat näiden nimityksiä, niin nimitykset pitää korjata myös lahetteen_tulostusjono.php kohdan "NAYTATILAUS" javascriptiin.
      echo "<td>";
      echo "<input type='text' size='6' id='maara_$toimittajan_ostotilaus_indeksi' name='maara' value='$tilattava_maara'>";
      echo "<input type='hidden' id='tuoteno_$toimittajan_ostotilaus_indeksi' name='tuoteno' value='$tuotenumero'>";
      echo "<input type='hidden' id='toimittaja_$toimittajan_ostotilaus_indeksi' name='toimittaja' value='$toimittajan_tunnus'>";
      echo "</td>";
      echo "<td class='back'><input type='button' value='".t("Tee Ostotilaus")."' class='tilaa' id='submit_$toimittajan_ostotilaus_indeksi'></td>";
      echo "</tr>";

      $toimittajan_ostotilaus_indeksi++;
    }
  }

  echo "</table>";
}

//  Näytetään vaan tää tilaus jostain linkistä..
if ($tee != "NAYTA") {
  if ($laskurow["jaksotettu"] <> 0) {
    echo "<br><strong>".t("Maksusopimus").":</strong><hr>";

    if ($laskurow["jaksotettu"]<0) {
      $sopimus = (-1) * $laskurow["jaksotettu"];
    }
    else {
      $sopimus = $laskurow["jaksotettu"];
    }

    echo "<table><tr><th>#</th><th>".t("Osuus-%")."</th><th>".t("Maksuehto/Kuvaus")."</th><th>".t("Lisätiedot/Ohje")."</th><th>".t("Summa")."</th><th>".t("Laskunro")."</th><th>".t("Laskun pvm")."</th><th>".t("Eräpäivä")."</th><th>".t("Maksupäivä")."</th></tr>";

    $query = "SELECT maksupositio.*, round(osuus,0) osuus,
              concat_ws('<br>', maksuehto.teksti, kuvaus) maksuehto,
              concat(maksupositio.lisatiedot, '<br>', maksupositio.ohje) lisatiedot,
              if (lasku.laskunro>0,lasku.laskunro, '') laskunro,
              if (myre.tapvm!='0000-00-00', date_format(myre.tapvm, '%d.%m.%Y'), '') tapvm,
              if (myre.erpcm!='0000-00-00', date_format(myre.erpcm, '%d.%m.%Y'), '') erpcm,
              if (myre.mapvm!='0000-00-00', date_format(myre.mapvm, '%d.%m.%Y'), '') mapvm,
              lasku.summa, lasku.summa_valuutassa, lasku.valkoodi
              FROM maksupositio
              LEFT JOIN lasku ON lasku.yhtio=maksupositio.yhtio and lasku.tunnus=maksupositio.uusiotunnus
              LEFT JOIN lasku myre ON myre.yhtio=lasku.yhtio and myre.laskunro=lasku.laskunro and myre.tila='U'
              LEFT JOIN maksuehto on maksuehto.yhtio=maksupositio.yhtio and maksuehto.tunnus=maksupositio.maksuehto
              WHERE maksupositio.yhtio = '$kukarow[yhtio]' and otunnus = '$sopimus'
              ORDER BY tunnus";
    $maksusopres = pupe_query($query);
    $i=0;

    while ($maksusoprow = mysql_fetch_assoc($maksusopres)) {
      $i++;
      echo "<tr class='aktiivi'>
          <td>$i</td>
          <td align='center'>$maksusoprow[osuus]</td>
          <td>$maksusoprow[maksuehto]</td>
          <td>$maksusoprow[lisatiedot]</td>
          <td align='right'>$maksusoprow[summa]</td>
          <td align='center'>$maksusoprow[laskunro]</td>
          <td align='center'>$maksusoprow[tapvm] ";
      if ($maksusoprow["tapvm"] != '') {
        echo "($maksusoprow[summa] $yhtiorow[valkoodi] / $maksusoprow[summa_valuutassa] $maksusoprow[valkoodi])";
      }
      echo "</td>
          <td align='center'>$maksusoprow[erpcm]</td>
          <td align='center'>$maksusoprow[mapvm]</td>
        </tr>";
    }
    echo "</table><br>";
  }

  if ($laskurow["tunnusnippu"] > 0) {
    echo "<br><strong>".t("Liitetyt tilaukset").":</strong><hr>";

    if ($kukarow['hinnat'] == 0) $summaselli = " summa, ";
    else $summaselli = "";

    $query = "SELECT tunnus tilaus, laskunro, concat_ws(' ', nimi, nimitark) asiakas, ytunnus, toimaika, laatija, $summaselli tila, alatila
              FROM lasku use index (yhtio_tunnusnippu)
              WHERE yhtio     = '$kukarow[yhtio]'
              and tunnusnippu = '$laskurow[tunnusnippu]'
              and tila        IN ('L','G','E','V','W','N','R','A')";
    $result = pupe_query($query);

    if (mysql_num_rows($result)!=0) {

      echo "<table>";
      echo "<tr>";
      for ($i=0; $i < mysql_num_fields($result)-2; $i++) {
        echo "<th align='left'>".t(mysql_field_name($result, $i))."</th>";
      }
      echo "<th align='left'>".t("Tyyppi")."</th>";
      echo "</tr>";

      while ($row = mysql_fetch_assoc($result)) {

        $ero="td";
        if ($tunnus==$row['tilaus']) $ero="th";

        echo "<tr class='aktiivi'>";

        for ($i=0; $i<mysql_num_fields($result)-2; $i++) {

          $fieldname = mysql_field_name($result, $i);

          if ($fildname == 'toimaika') {
            echo "<$ero>".tv1dateconv($row[$fieldname])."</$ero>";
          }
          else {
            echo "<$ero>".$row[$fieldname]."</$ero>";
          }
        }

        $laskutyyppi  = $row["tila"];
        $alatila    = $row["alatila"];

        //tehdään selväkielinen tila/alatila
        require "inc/laskutyyppi.inc";

        echo "<$ero>".t($laskutyyppi)." ".t($alatila)."</$ero>";

        echo "<form method='post'><td class='back'>
            <input type='hidden' name='tee' value='NAYTATILAUS'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='asiakasid' value='$asiakasid'>
            <input type='hidden' name='toimittajaid' value='$toimittajaid'>
            <input type='hidden' name='tunnus' value='$row[tilaus]'>
            <input type='hidden' name='tunnukset' value='$laskurow[tunnusnippu]'>
            <input type='hidden' name='otunnus' value='$laskurow[tunnusnippu]'>
            <input type='hidden' name='nippu' value='$laskurow[tunnusnippu]'>
            <input type='hidden' name='nimi' value='$nimi'>
            <input type='hidden' name='ppa' value='$ppa'>
            <input type='hidden' name='kka' value='$kka'>
            <input type='hidden' name='vva' value='$vva'>
            <input type='hidden' name='ppl' value='$ppl'>
            <input type='hidden' name='kkl' value='$kkl'>
            <input type='hidden' name='vvl' value='$vvl'>
            <input type='submit' value='".t("Näytä tilaus")."'></td></form>";

        echo "</tr>";
      }
      echo "</table><br>";
    }
    else {
      echo t("Ei tilauksia")."...<br><br>";
    }
  }
}

function hae_kassalipas($tunnus) {
  global $kukarow;

  $query = "SELECT nimi
            FROM kassalipas
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tunnus  = '{$tunnus}'";

  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function piirra_maksutapahtumat($maksutapahtumat, $asiakasid, $laskunro, $lasku_yhtio, $tunnus,
  $ytunnus, $ppa, $kka, $vva, $ppl, $kkl, $vvl, $lopetus) {
  global $yhtiorow, $toim;

  echo "<script src='../js/naytatilaus.js'></script>";

  echo "<br>";

  echo "<table>";
  echo "<thead>";

  echo "<tr>";
  echo "<th class='text-center' colspan='5'>" . t("Maksutapahtumat") . "</th>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t("Maksutapa") . "</th>";
  echo "<th>" . t("Summa") . "</th>";
  echo "<th>" . t("Osto") . "/" . t("Hyvitys") . "</th>";
  echo "<th>" . t("Asiakkaan kuitti") . "</th>";
  echo "<th>" . t("Kauppiaan kuitti") . "</th>";
  echo "</tr>";

  echo "</thead>";
  echo "<tbody>";

  while ($maksutapahtuma = mysql_fetch_assoc($maksutapahtumat)) {
    $summa = number_format($maksutapahtuma["summa_valuutassa"],
      $yhtiorow["hintapyoristys"], ",", " ");
    $tila = $maksutapahtuma["tila"] == "K" ? t("Osto") : t("Hyvitys");
    $asiakkaan_kuitti = str_replace("\n", "<br>", $maksutapahtuma["asiakkaan_kuitti"]);
    $kauppiaan_kuitti = str_replace("\n", "<br>", $maksutapahtuma["kauppiaan_kuitti"]);

    echo "<tr>";
    echo "<td>{$maksutapahtuma["maksutapa"]}</td>";
    echo "<td>{$summa} {$maksutapahtuma["valkoodi"]}</td>";
    echo "<td>{$tila}</td>";
    echo "<td>";

    if (empty($asiakkaan_kuitti)) {
      echo "<span>Kuittia ei ole haettu</span><br>";
      echo "<a class='hae_asiakkaan_kuitti' href>" . t("Yritä uudelleen") . "</a>";
      echo "<form>";
      echo "<input type='hidden' name='tee' value='NAYTATILAUS'>";
      echo "<input type='hidden' name='toim' value='{$toim}'>";
      echo "<input type='hidden' name='asiakasid' value='{$asiakasid}'>";
      echo "<input type='hidden' name='laskunro' value='{$laskunro}'>";
      echo "<input type='hidden' name='lasku_yhtio' value='{$lasku_yhtio}'>";
      echo "<input type='hidden' name='tunnus' value='{$tunnus}'>";
      echo "<input type='hidden' name='ytunnus' value='{$ytunnus}'>";
      echo "<input type='hidden' name='ppa' value='{$ppa}'>";
      echo "<input type='hidden' name='kka' value='{$kka}'>";
      echo "<input type='hidden' name='vva' value='{$vva}'>";
      echo "<input type='hidden' name='ppl' value='{$ppl}'>";
      echo "<input type='hidden' name='kkl' value='{$kkl}'>";
      echo "<input type='hidden' name='vvl' value='{$vvl}'>";
      echo "<input type='hidden' name='lopetus' value='{$lopetus}'>";
      echo "<input type='hidden' name='kuitin_haku[tunnus]' value='{$maksutapahtuma["tunnus"]}'>";
      echo "<input type='hidden' name='kuitin_haku[toiminto]' value='asiakas'>";
      echo "</form>";
    }
    else {
      echo "<a class='nayta_asiakkaan_kuitti' href>" . t("Näytä") . "</a>";
      echo "<div class='hidden'>{$asiakkaan_kuitti}</div>";
    }

    echo "</td>";
    echo "<td>";

    if (empty($kauppiaan_kuitti)) {
      echo "<span>Kuittia ei ole haettu</span><br>";
      echo "<a class='hae_kauppiaan_kuitti' href>" . t("Yritä uudelleen") . "</a>";
      echo "<form>";
      echo "<input type='hidden' name='tee' value='NAYTATILAUS'>";
      echo "<input type='hidden' name='toim' value='{$toim}'>";
      echo "<input type='hidden' name='asiakasid' value='{$asiakasid}'>";
      echo "<input type='hidden' name='laskunro' value='{$laskunro}'>";
      echo "<input type='hidden' name='lasku_yhtio' value='{$lasku_yhtio}'>";
      echo "<input type='hidden' name='tunnus' value='{$tunnus}'>";
      echo "<input type='hidden' name='ytunnus' value='{$ytunnus}'>";
      echo "<input type='hidden' name='ppa' value='{$ppa}'>";
      echo "<input type='hidden' name='kka' value='{$kka}'>";
      echo "<input type='hidden' name='vva' value='{$vva}'>";
      echo "<input type='hidden' name='ppl' value='{$ppl}'>";
      echo "<input type='hidden' name='kkl' value='{$kkl}'>";
      echo "<input type='hidden' name='vvl' value='{$vvl}'>";
      echo "<input type='hidden' name='lopetus' value='{$lopetus}'>";
      echo "<input type='hidden' name='kuitin_haku[tunnus]' value='{$maksutapahtuma["tunnus"]}'>";
      echo "<input type='hidden' name='kuitin_haku[toiminto]' value='kauppias'>";
      echo "</form>";
    }
    else {
      echo "<a class='nayta_kauppiaan_kuitti' href>" . t("Näytä") . "</a>";
      echo "<div class='hidden'>{$kauppiaan_kuitti}</div>";
    }

    echo "</td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";
}
