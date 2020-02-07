<?php

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "../inc/parametrit.inc";

if (!isset($tee)) $tee = '';
if (!isset($toimipaikka)) $toimipaikka = $kukarow['toimipaikka'] != 0 ? $kukarow['toimipaikka'] : "";
if (!isset($tuotteen_toimittaja)) $tuotteen_toimittaja = 27371;
if (!isset($ytunnus)) $ytunnus = '';
if (!isset($toimittajaid)) $toimittajaid = 0;
if (!isset($myynti)) $myynti = 'o';

if ($tee == "lataa_tiedosto") {
  readfile("/tmp/".basename($tmpfilenimi));
  exit;
}

if ($ytunnus != "") {
  require "inc/kevyt_toimittajahaku.inc";
}

if ($ytunnus == '' or $toimittajaid == 0) {
  $tee = '';
}

echo "<font class='head'>", t("Hälytysrajojen suunnittelu -raportti"), "</font><hr>";

echo "<form method='post' action=''>";
echo "<input type='hidden' name='tee' value='rapsa' />";
echo "<table>";
echo "<tr>";
echo "<th>", t("Toimipaikka"), "</th>";
echo "<td>";
echo "<select name='toimipaikka'>";
echo "<option value=''>", t("Kaikki toimipaikat"), "</option>";

$sel = $toimipaikka === "0" ? "selected" : "";
echo "<option value='0' {$sel}>", t("Ei toimipaikkaa"), "</option>";

$toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']);

while ($toimipaikat_row = mysql_fetch_assoc($toimipaikat_res)) {
  $sel = $toimipaikat_row['tunnus'] == $toimipaikka ? "selected" : "";
  echo "<option value='{$toimipaikat_row['tunnus']}' {$sel}>{$toimipaikat_row['nimi']}</option>";
}

echo "</select>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<th>", t("Toimittaja"), "</th>";
echo "<td>";
echo "<input type='text' name='ytunnus' value='{$ytunnus}' />";
echo "</td>";
echo "</tr>";

$chk = $myynti != '' ? "checked" : "";

echo "<tr>";
echo "<th>", t("Näytä vain tuotteet joilla on myyntiä"), "</th>";
echo "<td><input type='checkbox' name='myynti' {$chk} /></td>";
echo "</tr>";

echo "<tr><td colspan='2' class='back'><input type='submit' value='", t("Tee"), "' /></td></tr>";

echo "</table>";
echo "</form>";

if ($tee != '') {

  echo "<br />";
  echo "<font class='message'>", t("Etsitään tuotteita. Tämä voi kestää useita minuutteja. Odota hetki..."), "</font>";
  echo "<br /><br />";

  $vp_lisa = "";

  if ($toimipaikka != '' and $toimipaikka != "0") {

    $query  = "SELECT GROUP_CONCAT(tunnus) AS tunnukset
               FROM varastopaikat
               WHERE yhtio      = '{$kukarow['yhtio']}'
               AND tyyppi      != 'P'
               AND toimipaikka  = '{$toimipaikka}'";
    $vares = pupe_query($query);
    $varow = mysql_fetch_assoc($vares);

    if (!empty($varow['tunnukset'])) {
      $vp_lisa = " AND varastopaikat.tunnus IN ({$varow['tunnukset']}) ";
    }
  }

  $orum_setattu = strpos(preg_replace("/[a-zA-Z\-]/", "", $ytunnus), "20428100") !== false ? true : false;

  $tuotteet = array();

  $_12kk_date = new DateTime(date('Y-m-d'));
  $_12kk_date = $_12kk_date->sub(new DateInterval('P1Y'))->format('Y-m-d');

  $_24kk_date = new DateTime(date('Y-m-d'));
  $_24kk_date = $_24kk_date->sub(new DateInterval('P2Y'))->format('Y-m-d');

  if ($orum_setattu) {
    $havinglisa = " and SUM(IF((tilausrivi.laskutettuaika >= '{$_12kk_date}' AND tilausrivi.yhtio = 'artr'), tilausrivi.kpl, 0)) != 0 and SUM(IF(((tilausrivi.laskutettuaika < '{$_12kk_date}' AND tilausrivi.laskutettuaika >= '{$_24kk_date}') AND tilausrivi.yhtio = 'artr'), tilausrivi.kpl, 0)) != 0" ;
    $yhtiolisa = " tilausrivi.yhtio IN ('{$kukarow['yhtio']}','artr') ";
  }
  else {
    $havinglisa = "";
    $yhtiolisa = " tilausrivi.yhtio = '{$kukarow['yhtio']}' ";
  }

  if (trim($toimipaikka) != "" and $toimipaikka != 'kaikki') {
    $toimipaikkarajaus = "AND ((tilausrivi.yhtio = '{$kukarow['yhtio']}' AND lasku.yhtio_toimipaikka = '{$toimipaikka}') OR (tilausrivi.yhtio = 'artr' AND lasku.yhtio_toimipaikka = 0))";
  }
  else {
    $toimipaikkarajaus = "";
  }

  $query = "SELECT tuote.tuoteno,
            tt.ostohinta,
            tuote.nimitys,
            tuote.luontiaika,
            tuote.try,
            tuote.osasto,
            tuote.tuotemerkki,
            tuote.ostoehdotus,
            tuote.status,
            tuote.myyntihinta,
            tuote.kehahin,
            tuote.kuvaus,
            ROUND(SUM(IF(tilausrivi.laskutettuaika >= '{$_12kk_date}', tilausrivi.kpl, 0))) kplVA,
            ROUND(SUM(IF((tilausrivi.laskutettuaika < '{$_12kk_date}' AND tilausrivi.laskutettuaika >= '{$_24kk_date}'), tilausrivi.kpl, 0))) kplEDV
            FROM tilausrivi USE INDEX (yhtio_tyyppi_laskutettuaika)
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND lasku.kauppatapahtuman_luonne != 21)
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
            JOIN tuotteen_toimittajat AS tt ON (tt.yhtio = '{$kukarow['yhtio']}' AND tt.liitostunnus = '{$toimittajaid}' AND tt.tuoteno = tuote.tuoteno)
            WHERE tilausrivi.yhtio        = '{$kukarow['yhtio']}'
            AND tilausrivi.tyyppi         = 'L'
            AND tilausrivi.laskutettuaika >= '{$_24kk_date}'
            {$toimipaikkarajaus}
            GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12";
  $res = pupe_query($query);

  while ($row = mysql_fetch_assoc($res)) {

    if ($orum_setattu) {
      $query = "SELECT tilausrivi.tuoteno,
                ROUND(SUM(IF(tilausrivi.laskutettuaika >= '{$_12kk_date}', tilausrivi.kpl, 0))) kplVA_artr,
                ROUND(SUM(IF((tilausrivi.laskutettuaika < '{$_12kk_date}' AND tilausrivi.laskutettuaika >= '{$_24kk_date}'), tilausrivi.kpl, 0))) kplEDV_artr
                FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika)
                JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND lasku.kauppatapahtuman_luonne != 21)
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                WHERE tilausrivi.yhtio        = 'artr'
                AND tilausrivi.tyyppi         = 'L'
                AND tilausrivi.laskutettuaika >= '{$_24kk_date}'
                AND tilausrivi.tuoteno        = '{$row['tuoteno']}'
                GROUP BY 1";
      $res_orum = pupe_query($query);

      while ($row_orum = mysql_fetch_assoc($res_orum)) {
        if ($myynti != '') {
          if ($row['kplVA'] == 0 and $row['kplEDV'] == 0 and $row_orum['kplVA_artr'] == 0 and $row_orum['kplEDV_artr']  == 0) continue;
        }

        $tuotteet[$row_orum['tuoteno']]['row']['kplVA_artr'] = $row_orum['kplVA_artr'];
        $tuotteet[$row_orum['tuoteno']]['row']['kplEDV_artr'] = $row_orum['kplEDV_artr'];
      }
    }

    if ($myynti != '' and !$orum_setattu) {
      if ($row['kplVA'] == 0 and $row['kplEDV'] == 0) continue;
    }

    if ($orum_setattu) {
      $row['kplVA_artr'] = $tuotteet[$row['tuoteno']]['row']['kplVA_artr'];
      $row['kplEDV_artr'] = $tuotteet[$row['tuoteno']]['row']['kplEDV_artr'];

      $query = "SELECT IF(tuotteen_toimittajat.osto_era = 0, 1, round(tuotteen_toimittajat.osto_era)) osto_era,
                IF(tuote.myynti_era = 0, 1, round(tuote.myynti_era)) myynti_era
                FROM tuotteen_toimittajat
                JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno)
                WHERE tuotteen_toimittajat.yhtio = 'artr'
                AND tuotteen_toimittajat.tuoteno = '{$row['tuoteno']}'
                ORDER BY if(tuotteen_toimittajat.jarjestys=0,9999,tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus
                LIMIT 1";
      $ttres_orum = pupe_query($query);
      if (mysql_num_rows($ttres_orum) == 1) {
        $row_orum_tt = mysql_fetch_assoc($ttres_orum);
        $row['osto_era_artr'] = $row_orum_tt['osto_era'];
        $row['myynti_era_artr'] = $row_orum_tt['myynti_era'];
      }
    }

    $tuotteet[$row['tuoteno']]['row'] = $row;
  }

  echo "<br /><font class='message'>", t("Löytyi %d riviä", "", count($tuotteet)), "</font><br />";

  list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet('TK');

  $tiedostonimi = "tuotteiden_tiedot-{$kukarow['yhtio']}-".date("YmdHis").".csv";

  $toot = fopen("/tmp/".$tiedostonimi, "w");

  fwrite($toot, t("Tuoteno"));
  fwrite($toot, ";".t("Nimitys lyhyt"));
  fwrite($toot, ";".t("Nimitys pitkä"));

  if ($orum_setattu) fwrite($toot, ";".t("Reknro"));

  fwrite($toot, ";".t("Luontiaika"));
  fwrite($toot, ";".t("Try"));
  fwrite($toot, ";".t("Osasto"));
  fwrite($toot, ";".t("Merkki"));
  fwrite($toot, ";".t("Tuotepaikka"));

  if ($orum_setattu) fwrite($toot, ";".t("Örumin tuotepaikka"));

  fwrite($toot, ";".t("12 kk menekki Masi"));
  fwrite($toot, ";".t("24 kk menekki Masi"));

  if ($orum_setattu) {
    fwrite($toot, ";".t("12 kk menekki Örum"));
    fwrite($toot, ";".t("24 kk menekki Örum"));
    fwrite($toot, ";".t("Örum ABC osasto try"));
  }

  fwrite($toot, ";".t("Ostoehdotus Masi"));

  if ($orum_setattu) fwrite($toot, ";".t("Ostoehdotus Örum"));

  fwrite($toot, ";".t("Status Masi"));

  if ($orum_setattu) fwrite($toot, ";".t("Status Örum"));

  fwrite($toot, ";".t("Hälyraja Masi"));
  fwrite($toot, ";".t("Ostoerä Masi"));
  fwrite($toot, ";".t("Myyntihinta"));
  fwrite($toot, ";".t("Ostohinta"));
  fwrite($toot, ";".t("Kehahinta"));
  if ($orum_setattu) {
    fwrite($toot, ";".t("Ostoerä Örum"));
    fwrite($toot, ";".t("Myyntierä Örum"));
  }

  fwrite($toot, "\r\n");

  foreach ($tuotteet as $tuoteno => $_arr) {

    if ($_arr['row']['tuoteno'] == '') continue;

    $row = $_arr['row'];

    if ($orum_setattu) {
      $query = "SELECT count(yr.rekno) rekno
                FROM yhteensopivuus_tuote AS yt
                JOIN yhteensopivuus_rekisteri AS yr ON (yr.yhtio = yt.yhtio AND yr.autoid = yt.atunnus)
                WHERE yt.yhtio = 'artr'
                AND yt.tuoteno = '{$row['tuoteno']}'";
      $reknores = pupe_query($query);
      $reknorow = mysql_fetch_assoc($reknores);
    }

    $query = "SELECT *
              FROM tuotepaikat AS tp
              JOIN varastopaikat ON (varastopaikat.yhtio = tp.yhtio
              AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tp.hyllyalue), 5, '0'),lpad(upper(tp.hyllynro), 5, '0'))
              AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tp.hyllyalue), 5, '0'),lpad(upper(tp.hyllynro), 5, '0'))
              {$vp_lisa})
              WHERE tp.yhtio = '{$kukarow['yhtio']}'
              AND tp.tuoteno = '{$row['tuoteno']}'";
    $tp_res = pupe_query($query);
    $tp_row = mysql_fetch_assoc($tp_res);

    $row['nimitys'] = preg_replace("/[\"]/", "", $row['nimitys']);
    $row['kuvaus'] = preg_replace("/[\"]/", "", $row['kuvaus']);
    $row['kuvaus'] = preg_replace("/[\\r\\n]/", " ", $row['kuvaus']);
    $row['kuvaus'] = preg_replace("/[\\n]/", " ", $row['kuvaus']);
    $row['kuvaus'] = preg_replace("/[\\t]/", " ", $row['kuvaus']);

    fwrite($toot, "\"{$row['tuoteno']}\"");
    fwrite($toot, ";\"{$row['nimitys']}\"");
    fwrite($toot, ";\"{$row['kuvaus']}\"");

    if ($orum_setattu) fwrite($toot, ";\"{$reknorow['rekno']}\"");

    fwrite($toot, ";\"".tv1dateconv($row['luontiaika'])."\"");

    $try = t_avainsana("TRY", '', "and selite = '{$row['try']}'", '', '', "selitetark");
    fwrite($toot, ";\"{$try}\"");

    $osasto = t_avainsana("OSASTO", '', "and selite = '{$row['osasto']}'", '', '', "selitetark");
    fwrite($toot, ";\"{$osasto}\"");

    fwrite($toot, ";\"{$row['tuotemerkki']}\"");
    fwrite($toot, ";\"{$tp_row['hyllyalue']} {$tp_row['hyllynro']} {$tp_row['hyllyvali']} {$tp_row['hyllytaso']}\"");

    if ($orum_setattu) {
      $query = "SELECT tuotepaikat.hyllyalue,
                tuotepaikat.hyllynro,
                tuotepaikat.hyllyvali,
                tuotepaikat.hyllytaso,
                tuote.*
                FROM tuotepaikat
                JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno)
                WHERE tuotepaikat.yhtio  = 'artr'
                AND tuotepaikat.tuoteno  = '{$row['tuoteno']}'
                AND tuotepaikat.oletus  != ''";
      $orumin_tuotepaikka_res = pupe_query($query);
      $orumin_tuotepaikka_row = mysql_fetch_assoc($orumin_tuotepaikka_res);

      fwrite($toot, ";\"{$orumin_tuotepaikka_row['hyllyalue']} {$orumin_tuotepaikka_row['hyllynro']} {$orumin_tuotepaikka_row['hyllyvali']} {$orumin_tuotepaikka_row['hyllytaso']}\"");

    }

    if ($row['kplVA'] != '') {
      fwrite($toot, ";".$row['kplVA']);
      fwrite($toot, ";".$row['kplEDV']);
    }
    else {
      fwrite($toot, ';');
      fwrite($toot, ';');
    }

    if ($orum_setattu) {

      if ($row['kplVA_artr'] != '') {
        fwrite($toot, ";".$row['kplVA_artr']);
        fwrite($toot, ";".$row['kplEDV_artr']);
      }
      else {
        fwrite($toot, ';');
        fwrite($toot, ';');
      }

      $query = "SELECT ifnull(abc_aputaulu.luokka, 8) abcluokka,
                ifnull(abc_aputaulu.luokka_osasto, 8) abcluokka_osasto,
                ifnull(abc_aputaulu.luokka_try, 8) abcluokka_try
                FROM abc_aputaulu use index (yhtio_tyyppi_tuoteno)
                WHERE abc_aputaulu.yhtio = 'artr'
                AND abc_aputaulu.tuoteno = '{$row['tuoteno']}'
                AND abc_aputaulu.tyyppi  = 'TK'";
      $abcres = pupe_query($query);
      $abcrow = mysql_fetch_assoc($abcres);

      if (mysql_num_rows($abcres) != 0) {
        fwrite($toot, ";\"{$ryhmanimet[$abcrow['abcluokka']]} {$ryhmanimet[$abcrow['abcluokka_osasto']]} {$ryhmanimet[$abcrow['abcluokka_try']]}\"");
      }
      else {
        fwrite($toot, ";");
      }
    }

    fwrite($toot, ";".$row['ostoehdotus']);
    if ($orum_setattu) fwrite($toot, ";".$orumin_tuotepaikka_row['ostoehdotus']);

    fwrite($toot, ";\"{$row['status']}\"");
    if ($orum_setattu) fwrite($toot, ";\"{$orumin_tuotepaikka_row['status']}\"");

    fwrite($toot, ";".round($tp_row['halytysraja']));
    fwrite($toot, ";".round($tp_row['tilausmaara']));

    fwrite($toot, ";".str_replace(".", ",", round($row['myyntihinta'], $yhtiorow['hintapyoristys'])));
    fwrite($toot, ";".str_replace(".", ",", round($row['ostohinta'], $yhtiorow['hintapyoristys'])));
    fwrite($toot, ";".str_replace(".", ",", round($row['kehahin'], $yhtiorow['hintapyoristys'])));

    if ($orum_setattu) fwrite($toot, ";".$row['osto_era_artr']);
    if ($orum_setattu) fwrite($toot, ";".$row['myynti_era_artr']);

    fwrite($toot, "\r\n");
  }

  fclose($toot);

  echo "<br><br>", t("Tallenna aineisto"), ": ";
  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='{$tiedostonimi}'>";
  echo "<input type='hidden' name='tmpfilenimi' value='{$tiedostonimi}'>";
  echo "<td><input type='submit' value='", t("Tallenna"), "'></form>";
}

require "inc/footer.inc";
