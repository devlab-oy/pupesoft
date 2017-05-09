<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "../inc/parametrit.inc";

if (!isset($tee)) $tee = '';

if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

if (!isset($kkl)) $kkl = date("m");
if (!isset($vvl)) $vvl = date("Y");
if (!isset($ppl)) $ppl = date("d");

if (!isset($raporttityyppi)) $raporttityyppi = '';
if (!isset($aikamaare)) $aikamaare = 10;
if (!isset($laskutusajo_tuotteet)) $laskutusajo_tuotteet = '';
if (!isset($nayta_viennit)) $nayta_viennit = '';
if (!isset($tilaustyyppi)) $tilaustyyppi = array();
if (!isset($toimittumassa)) $toimittumassa = "";

echo "  <script type='text/javascript' language='JavaScript'>
      <!--

      $(function() {

        $('.asiakas').on('click', function() {
          $('.'+$(this).attr('id')).toggle();
        });

      });

      //-->
    </script>";

echo "<font class='head'>", t("Rivilaskuri"), "</font><hr>";

echo "<form method='post' autocomplete='off'>";
echo "<input type='hidden' name='tee' value='aja' />";

echo "<table>";

echo "<tr>";
echo "<th>", t("Ajotyyppi"), "</th>";
echo "<td colspan='3'><select name='raporttityyppi'>";
echo "<option value=''>", t("Myyntitilauksen luontiajan mukaan"), "</option>";

$sel = $raporttityyppi == 'kerays' ? ' selected' : '';

echo "<option value='kerays'{$sel}>", t("Myyntitilauksen keräysajan mukaan"), "</option>";
echo "</select></td>";
echo "</tr>";

$sel = array($aikamaare => ' selected') + array(10 => '', 30 => '', 60 => '');

echo "<tr>";
echo "<th>", t("Summaustaso"), "</th>";
echo "<td colspan='3'><select name='aikamaare'>";
echo "<option value='10'{$sel[10]}>", t("10 minuuttia"), "</option>";
echo "<option value='30'{$sel[30]}>", t("30 minuuttia"), "</option>";
echo "<option value='60'{$sel[60]}>", t("60 minuuttia"), "</option>";
echo "</select></td>";
echo "</tr>";

$sel = $laskutusajo_tuotteet != '' ? " selected" : "";

echo "<tr>";
echo "<th>", t("Pupesoftin automaattisesti lisäämät tuotteet"), ",<br>", t("kuten esimerkiksi rahtituotenumero ja kuljetusvakuutus"), "</th>";
echo "<td colspan='3'><select name='laskutusajo_tuotteet'>";
echo "<option value=''>", t("Näytä"), "</option>";
echo "<option value='ei'{$sel}>", t("Ei näytetä"), "</option>";
echo "</select></td>";
echo "</tr>";

$sel = $nayta_viennit != '' ? " selected" : "";

echo "<tr>";
echo "<th>", t("Vienti"), "</th>";
echo "<td colspan='3'><select name='nayta_viennit'>";
echo "<option value=''>", t("Näytä"), "</option>";
echo "<option value='ei'{$sel}>", t("Ei näytetä"), "</option>";
echo "</select></td>";
echo "</tr>";

$query = "SELECT tunnus
          FROM lahdot
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND aktiivi = ''
          LIMIT 1";
$lahdot_chk_res = pupe_query($query);

if (mysql_num_rows($lahdot_chk_res) == 1) {
  $sel = $toimittumassa != '' ? " checked" : "";

  echo "<tr>";
  echo "<th>", t("Huomioidaan vain niiden tilauksien rivit") , "<br>", t("jotka on annetun päivämäärävälin lähdöissä"), "</th>";
  echo "<td colspan='3'><input type='checkbox' name='toimittumassa' {$sel} /></td>";
  echo "</tr>";
}

$sel = array_fill_keys($tilaustyyppi, " checked") + array('N' => '', '2' => '', '7' => '', 'S' => '', '8' => '', 'R' => '');

echo "<tr>";
echo "<th>", t("Tilaustyyppi"), "</th>";
echo "<td colspan='3'>";
echo "<input type='hidden' name='tilaustyyppi[]' value='default' />";
echo "<input type='checkbox' name='tilaustyyppi[]' value='N' {$sel['N']}>", t("Normaalitilaus"), "<br />";
echo "<input type='checkbox' name='tilaustyyppi[]' value='2' {$sel[2]}>", t("Varastotäydennys"), "<br />";
echo "<input type='checkbox' name='tilaustyyppi[]' value='7' {$sel[7]}>", t("Tehdastilaus"), "<br />";
echo "<input type='checkbox' name='tilaustyyppi[]' value='S' {$sel['S']}>", t("Sarjatilaus"), "<br />";
echo "<input type='checkbox' name='tilaustyyppi[]' value='8' {$sel[8]}>", t("Muiden mukana"), "<br />";
echo "<input type='checkbox' name='tilaustyyppi[]' value='R' {$sel['R']}>", t("Reklamaatio"), "<br />";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<th>", t("Syötä alkupäivämäärä"), " (", t("pp-kk-vvvv"), ")</th>";
echo "<td><input type='text' name='ppa' value='{$ppa}' size='3'></td>";
echo "<td><input type='text' name='kka' value='{$kka}' size='3'></td>";
echo "<td><input type='text' name='vva' value='{$vva}' size='5'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>", t("Syötä loppupäivämäärä"), " (", t("pp-kk-vvvv"), ")</th>";
echo "<td><input type='text' name='ppl' value='{$ppl}' size='3'></td>";
echo "<td><input type='text' name='kkl' value='{$kkl}' size='3'></td>";
echo "<td><input type='text' name='vvl' value='{$vvl}' size='5'></td>";
echo "</tr>";

echo "</table>";

echo "<br>";
echo "<input type='submit' value='", t("Aja raportti"), "'>";

echo "</form><br /><br />";

if ($tee != '') {

  if ($raporttityyppi == "kerays") {
    echo "<font class='head'>", t("Kerätyt rivit"), " {$ppa}.{$kka}.{$vva} - {$ppl}.{$kkl}.{$vvl}</font>";

    $ajotapa = 'tilausrivi.kerattyaika';
    $ajoindex = 'yhtio_tyyppi_kerattyaika';
    $saldotonjoin = "JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '')";
  }
  else {
    echo "<font class='head'>", t("Myyntitilaukset"), " {$ppa}.{$kka}.{$vva} - {$ppl}.{$kkl}.{$vvl}</font>";

    $ajotapa = 'tilausrivi.laadittu';
    $ajoindex = 'yhtio_laadittu';
    $saldotonjoin = '';
  }

  if ($aikamaare == 30) {
    $aikamaarelisa = "  CONCAT(LEFT(DATE_FORMAT({$ajotapa}, '%H:%i'), 3), IF(MINUTE(DATE_FORMAT({$ajotapa}, '%H:%i')) > 29, '30', '00'))  kello,
              CONCAT(LEFT(DATE_FORMAT({$ajotapa}, '%H:%i'), 3), IF(MINUTE(DATE_FORMAT({$ajotapa}, '%H:%i')) > 29, '59', '29'))  kello2,";
  }
  elseif ($aikamaare == 60) {
    $aikamaarelisa = "  CONCAT(LEFT(DATE_FORMAT({$ajotapa}, '%H:%i'), 3), '00') kello,
              CONCAT(LEFT(DATE_FORMAT({$ajotapa}, '%H:%i'), 3), '59') kello2,";
  }
  else {
    $aikamaarelisa = "  CONCAT(LEFT(DATE_FORMAT({$ajotapa}, '%H:%i'), 4), '0') kello,
              CONCAT(LEFT(DATE_FORMAT({$ajotapa}, '%H:%i'), 4), '9') kello2,";
  }

  if ($laskutusajo_tuotteet != '') {

    $query = "SELECT GROUP_CONCAT(DISTINCT kuljetusvakuutus_tuotenumero SEPARATOR '\',\'') kuljetusvakuutus_tuotenumerot
              FROM toimitustapa
              WHERE yhtio                       = '{$kukarow['yhtio']}'
              AND kuljetusvakuutus_tuotenumero != ''";
    $toimitustapa_res = pupe_query($query);
    $toimitustapa_row = mysql_fetch_assoc($toimitustapa_res);

    $ei_laskutusajo_tuotteita = "AND tilausrivi.tuoteno NOT IN (";

    $ei_laskutusajo_tuotteita .= "  '{$yhtiorow['rahti_tuotenumero']}',
                    '{$yhtiorow['jalkivaatimus_tuotenumero']}',
                    '{$yhtiorow['erilliskasiteltava_tuotenumero']}',
                    '{$yhtiorow['kasittelykulu_tuotenumero']}',
                    '{$yhtiorow['maksuehto_tuotenumero']}',
                    '{$yhtiorow['ennakkomaksu_tuotenumero']}',
                    '{$yhtiorow['alennus_tuotenumero']}',
                    '{$yhtiorow['laskutuslisa_tuotenumero']}',
                    '{$yhtiorow['kuljetusvakuutus_tuotenumero']}'";
    $ei_laskutusajo_tuotteita = lisaa_vaihtoehtoinen_rahti_merkkijonoon($ei_laskutusajo_tuotteita);

    if ($toimitustapa_row['kuljetusvakuutus_tuotenumerot'] != '') $ei_laskutusajo_tuotteita .= ",'{$toimitustapa_row['kuljetusvakuutus_tuotenumerot']}'";

    $ei_laskutusajo_tuotteita .= ")";
  }
  else {
    $ei_laskutusajo_tuotteita = "";
  }

  $lahdotlisa = "";

  if ($toimittumassa != '') {
    $lahdotlisa = "JOIN lahdot ON (lahdot.yhtio = lasku.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto AND lahdot.pvm >= '{$vva}-{$kka}-{$ppa}' AND lahdot.pvm <= '{$vvl}-{$kkl}-{$ppl}')";
  }

  $vientilisa = "";

  if ($nayta_viennit == '') {
    $vientilisa = "  SUM(IF(lasku.vienti = 'E', 1, 0)) vienti_riveja,
            ROUND(SUM(IF(lasku.vienti = 'E', kpl + varattu + jt, 0))) vienti_nimikkeita,
            SUM(IF(lasku.vienti = 'K', 1, 0)) ei_eu_riveja,
            ROUND(SUM(IF(lasku.vienti = 'K', kpl + varattu + jt, 0))) ei_eu_nimikkeita,";
  }

  $tilaustyyppilisa = "";

  if (count($tilaustyyppi) > 1) {
    unset($tilaustyyppi[0]);

    if (in_array('N', $tilaustyyppi)) $tilaustyyppi[] = '';

    $tilaustyyppilisa = " AND lasku.tilaustyyppi IN ('".implode("','", $tilaustyyppi)."')";
  }

  $query = "SELECT {$aikamaarelisa}
            COUNT(*) yhteensa_riveja,
            ROUND(SUM(kpl + varattu + jt)) yhteensa_nimikkeita,
            {$vientilisa}
            SUM(IF(lasku.ohjelma_moduli IN ('EDIFACT911', 'FUTURSOFT', 'MAGENTO'), 1, 0)) sahkoisia_riveja,
            ROUND(SUM(IF(lasku.ohjelma_moduli IN ('EDIFACT911', 'FUTURSOFT', 'MAGENTO'), kpl + varattu + jt, 0))) sahkoisia_nimikkeita,
            GROUP_CONCAT(lasku.tunnus) tunnukset
            FROM tilausrivi USE INDEX ({$ajoindex})
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'L' {$tilaustyyppilisa})
            {$lahdotlisa}
            {$saldotonjoin}
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND {$ajotapa} >= '{$vva}-{$kka}-{$ppa} 00:00:00'
            AND {$ajotapa} <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
            AND tilausrivi.tyyppi  = 'L'
            {$ei_laskutusajo_tuotteita}
            GROUP BY 1
            ORDER BY 1";
  $res = pupe_query($query);

  echo "<br /><br />";
  echo "<table>";

  echo "<tr>";
  echo "<th></th>";
  echo "<th colspan='2' align='center'>", t("Yhteensä"), "</th>";

  if ($nayta_viennit == '') {
    echo "<th colspan='2' align='center'>", t("Vienti EU"), "</th>";
    echo "<th colspan='2' align='center'>", t("ei-EU"), "</th>";
  }

  echo "<th colspan='2' align='center'>", t("Sähköinen"), "</th>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>", t("Kello"), "</th>";
  echo "<th>", t("Rivejä"), "</th>";
  echo "<th>", t("Nimikkeitä"), "</th>";

  if ($nayta_viennit == '') {
    echo "<th>", t("Rivejä"), "</th>";
    echo "<th>", t("Nimikkeitä"), "</th>";
    echo "<th>", t("Rivejä"), "</th>";
    echo "<th>", t("Nimikkeitä"), "</th>";
  }

  echo "<th>", t("Rivejä"), "</th>";
  echo "<th>", t("Nimikkeitä"), "</th>";
  echo "</tr>";

  while ($row = mysql_fetch_assoc($res)) {

    echo "<tr class='aktiivi asiakas' id='", str_replace(":", "", $row['kello']), "'>";

    echo "<td>{$row['kello']} - {$row['kello2']} <img title='", t("Asiakkaittain"), "' alt='", t("Asiakkaittain"), "' src='{$palvelin2}pics/lullacons/go-down.png' /></td>";
    echo "<td align='right'>{$row['yhteensa_riveja']}</td>";
    echo "<td align='right'>{$row['yhteensa_nimikkeita']}</td>";

    if ($nayta_viennit == '') {
      echo "<td align='right'>{$row['vienti_riveja']}</td>";
      echo "<td align='right'>{$row['vienti_nimikkeita']}</td>";
      echo "<td align='right'>{$row['ei_eu_riveja']}</td>";
      echo "<td align='right'>{$row['ei_eu_nimikkeita']}</td>";
    }

    echo "<td align='right'>{$row['sahkoisia_riveja']}</td>";
    echo "<td align='right'>{$row['sahkoisia_nimikkeita']}</td>";
    echo "</tr>";

    $query = "SELECT {$aikamaarelisa}
              TRIM(CONCAT(lasku.nimi, ' ', lasku.nimitark)) asiakas,
              COUNT(*) yhteensa_riveja,
              ROUND(SUM(kpl + varattu + jt)) yhteensa_nimikkeita,
              {$vientilisa}
              SUM(IF(lasku.ohjelma_moduli IN ('EDIFACT911', 'FUTURSOFT', 'MAGENTO'), 1, 0)) sahkoisia_riveja,
              ROUND(SUM(IF(lasku.ohjelma_moduli IN ('EDIFACT911', 'FUTURSOFT', 'MAGENTO'), kpl + varattu + jt, 0))) sahkoisia_nimikkeita
              FROM tilausrivi USE INDEX ({$ajoindex})
              JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'L' AND lasku.tunnus IN ({$row['tunnukset']}) {$tilaustyyppilisa})
              JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
              {$lahdotlisa}
              {$saldotonjoin}
              WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
              AND {$ajotapa} >= '{$vva}-{$kka}-{$ppa} 00:00:00'
              AND {$ajotapa} <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
              AND tilausrivi.tyyppi  = 'L'
              {$ei_laskutusajo_tuotteita}
              GROUP BY 1,2,3
              HAVING kello = '{$row['kello']}'
              ORDER BY 1,2,3";
    $res_per_asiakas = pupe_query($query);

    while ($row_per_asiakas = mysql_fetch_assoc($res_per_asiakas)) {
      echo "<tr class='aktiivi spec ".str_replace(":", "", $row_per_asiakas['kello']), "' style='display:none;'>";

      echo "<td>{$row_per_asiakas['asiakas']}</td>";
      echo "<td align='right'>{$row_per_asiakas['yhteensa_riveja']}</td>";
      echo "<td align='right'>{$row_per_asiakas['yhteensa_nimikkeita']}</td>";

      if ($nayta_viennit == '') {
        echo "<td align='right'>{$row_per_asiakas['vienti_riveja']}</td>";
        echo "<td align='right'>{$row_per_asiakas['vienti_nimikkeita']}</td>";
        echo "<td align='right'>{$row_per_asiakas['ei_eu_riveja']}</td>";
        echo "<td align='right'>{$row_per_asiakas['ei_eu_nimikkeita']}</td>";
      }

      echo "<td align='right'>{$row_per_asiakas['sahkoisia_riveja']}</td>";
      echo "<td align='right'>{$row_per_asiakas['sahkoisia_nimikkeita']}</td>";
      echo "</tr>";
    }
  }

  $vientilisa = "";

  if ($nayta_viennit == '') {

    $vientilisa = "  sum(IF(lasku.vienti = 'E', 1, 0)) vienti_riveja,
            round(sum(IF(lasku.vienti = 'E', kpl + varattu + jt, 0))) vienti_nimikkeita,
            sum(IF(lasku.vienti = 'K', 1, 0)) ei_eu_riveja,
            round(sum(IF(lasku.vienti = 'K', kpl + varattu + jt, 0))) ei_eu_nimikkeita,";
  }

  ///* Yhteensärivi, annetaan tietokannan tehä työ, en jakssa summata while loopissa t. juppe*///
  $query = "SELECT
            count(*) yhteensa_riveja,
            round(sum(kpl + varattu + jt)) yhteensa_nimikkeita,
            {$vientilisa}
            SUM(IF(lasku.ohjelma_moduli IN ('EDIFACT911', 'FUTURSOFT', 'MAGENTO'), 1, 0)) sahkoisia_riveja,
            ROUND(SUM(IF(lasku.ohjelma_moduli IN ('EDIFACT911', 'FUTURSOFT', 'MAGENTO'), kpl + varattu + jt, 0))) sahkoisia_nimikkeita
            FROM tilausrivi USE INDEX ($ajoindex)
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'L' {$tilaustyyppilisa})
            {$lahdotlisa}
            {$saldotonjoin}
            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
            AND $ajotapa >= '$vva-$kka-$ppa 00:00:00'
            AND $ajotapa <= '$vvl-$kkl-$ppl 23:59:59'
            AND tilausrivi.tyyppi  = 'L'
            {$ei_laskutusajo_tuotteita}";
  $res = pupe_query($query);
  $row = mysql_fetch_array($res);

  echo "<tr>";
  echo "<th>".t("Yhteensä").":</th>";
  echo "<th style='text-align:right;'>{$row['yhteensa_riveja']}</th>";
  echo "<th style='text-align:right;'>{$row['yhteensa_nimikkeita']}</th>";

  if ($nayta_viennit == '') {
    echo "<th style='text-align:right;'>{$row['vienti_riveja']}</th>";
    echo "<th style='text-align:right;'>{$row['vienti_nimikkeita']}</th>";
    echo "<th style='text-align:right;'>{$row['ei_eu_riveja']}</th>";
    echo "<th style='text-align:right;'>{$row['ei_eu_nimikkeita']}</th>";
  }

  echo "<th style='text-align:right;'>{$row['sahkoisia_riveja']}</th>";
  echo "<th style='text-align:right;'>{$row['sahkoisia_nimikkeita']}</th>";
  echo "</tr>";

  echo "</table>";
}

require "inc/footer.inc";
