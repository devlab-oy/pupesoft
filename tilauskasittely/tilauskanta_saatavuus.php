<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// DataTables päälle
$pupe_DataTables = "tilauskanta";

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

echo "<font class='head'>".t("Tilauskanta")."/".t("Saatavuus")."</font><hr>";

if ($tee == 'NAYTATILAUS') {
  echo "<font class='head'>", t("Tilausnro"), ": {$tunnus}</font><hr>";
  $toim == "MYYNTI";
  require "raportit/naytatilaus.inc";
  require "inc/footer.inc";
  exit;
}

echo "<form name='uliuli' method='post'>";
echo "<input type='hidden' name='tee' value='aja'>";
echo "<input type='hidden' name='tunnus' value=''>";

if (!isset($kka))
  $kka = date("m");
if (!isset($vva))
  $vva = date("Y");
if (!isset($ppa))
  $ppa = date("d");

if (!isset($kkl))
  $kkl = date("m", mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));
if (!isset($vvl))
  $vvl = date("Y", mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));
if (!isset($ppl))
  $ppl = date("d", mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));

echo "<table>";
echo "<tr><th>".t("Syötä toimitusajan alku (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppa' value='$ppa' size='3'></td>
    <td><input type='text' name='kka' value='$kka' size='3'></td>
    <td><input type='text' name='vva' value='$vva' size='5'></td>
    <td class='back'></td>
    </tr><tr><th>".t("Syötä toimitusajan loppu (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppl' value='$ppl' size='3'></td>
    <td><input type='text' name='kkl' value='$kkl' size='3'></td>
    <td><input type='text' name='vvl' value='$vvl' size='5'></td>
    <td class='back'></td></tr>";

echo "<tr><th>".t("Syötä tuotenumeroväli (Ei pakollinen)").":</th>
    <td colspan='3'><input type='text' name='tuotealku' value='$tuotealku' size='15'> - <input type='text' name='tuoteloppu' value='$tuoteloppu' size='15'></td></tr>";

// tehdään avainsana query
$sresult = t_avainsana("OSASTO");

echo "<tr><th>".t("Osasto (Ei pakollinen)")."</th><td colspan='3'>";

echo "<select name='osasto'>";
echo "<option value=''>".t("Näytä kaikki")."</option>";

while ($srow = mysql_fetch_array($sresult)) {
  $sel = '';
  if ($osasto == $srow["selite"]) {
    $sel = "selected";
  }
  echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
}
echo "</select>";
echo "</td></tr>";

// tehdään avainsana query
$sresult = t_avainsana("TRY");

echo "<tr><th>".t("Tuoteryhmä (Ei pakollinen)")."</th><td colspan='3'>";

echo "<select name='try'>";
echo "<option value=''>".t("Näytä kaikki")."</option>";

while ($srow = mysql_fetch_array($sresult)) {
  $sel = '';
  if ($try == $srow["selite"]) {
    $sel = "selected";
  }
  echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
}
echo "</select>";
echo "</td></tr>";

//Valitaan varastot joiden saldot huomioidaan
$query = "SELECT *
          FROM varastopaikat
          WHERE yhtio = '{$kukarow['yhtio']}'
          ORDER BY yhtio, tyyppi, nimitys";
$vtresult = pupe_query($query);

echo "<tr><th class='ptop'>".t("Huomioi myytävissäoleva määrä varastoista:")."<br>";
echo "<font class='info'>(".t("Ilman valintaa määrä haetaan kaikista varastoista").")</font>";
echo "</th>";
echo "<td colspan='3'>";

while ($vrow = mysql_fetch_array($vtresult)) {

  $chk = "";
  if (is_array($valitutvarastot) and in_array($vrow["tunnus"], $valitutvarastot) != '') {
    $chk = "CHECKED";
  }

  echo "<input type='checkbox' name='valitutvarastot[]' value='$vrow[tunnus]' $chk> ";
  echo "$vrow[nimitys] ";
  if ($vrow["tyyppi"] != "") {
    echo " *$vrow[tyyppi]* ";
  }
  echo "<br>";
}
echo "</td></tr>";
echo "</table>";
echo "<br><input type='submit' value='".t("Aja")."'>";
echo "</form><br><br>";

if ($tee == 'aja') {
  $alkupvm = $vva."-".$kka."-".$ppa;
  $loppupvm = $vvl."-".$kkl."-".$ppl;

  $tuotelisa = '';

  if ((trim($tuotealku) != '' and trim($tuoteloppu) != '') or $osasto != '' or $try != '') {
    if ($osasto != '' or $try != '') {
      $tuotelisa .= " JOIN tuote on lasku.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno ";
    }

    if (trim($tuotealku) != '' and trim($tuoteloppu) != '') {
      $tuotelisa .= " and tilausrivi.tuoteno >= '$tuotealku' and tilausrivi.tuoteno <= '$tuoteloppu' ";
    }

    if ($osasto != '') {
      $tuotelisa .= " and tuote.osasto = '$osasto' ";
    }

    if ($try != '') {
      $tuotelisa .= " and tuote.try = '$try' ";
    }
  }

  $query_ale_lisa = generoi_alekentta('M');

  $query_select = " ";

  $query = "  SELECT
              lasku.toimaika,
              concat(concat(lasku.nimi,' '),if(lasku.nimitark!='',concat(lasku.nimitark,' '),''), if(lasku.toim_nimi!='',if(lasku.toim_nimi!=lasku.nimi,concat(lasku.toim_nimi,' '),''),''),if(lasku.toim_nimitark!='',if(lasku.toim_nimitark!=lasku.nimitark,concat(lasku.toim_nimitark,' '),''),'')) as 'nimi',
              lasku.tunnus as 'Tilausnro',
              lasku.tilaustyyppi Tyyppi,
              lasku.viesti as viesti,
              round(tilausrivi.hinta / if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 2) rivihinta,
              if (kuka.kuka IS NOT NULL, kuka.nimi, '') AS myyja,
              tilausrivi.tuoteno,
              tilausrivi.nimitys,
              tilausrivi.varattu,
              '' Myytävissä,
              '' Tilattu,
              lasku.tila,
              lasku.alatila
              FROM lasku use index (tila_index)
              JOIN tilausrivi on (lasku.yhtio = tilausrivi.yhtio
                and lasku.tunnus = tilausrivi.otunnus
                and tilausrivi.tyyppi in ('L','V','W')
                and tilausrivi.var != 'P'
                and tilausrivi.varattu <> 0
                and tilausrivi.laskutettuaika = '0000-00-00'
              )
              $tuotelisa
              LEFT JOIN kuka ON (lasku.yhtio = kuka.yhtio AND lasku.myyja = kuka.tunnus)
              where lasku.yhtio  = '$kukarow[yhtio]'
              and lasku.tila     in ('L','N','V')
              and lasku.alatila  not in ('X','V')
              and lasku.toimaika >= '$alkupvm' and lasku.toimaika <= '$loppupvm'
              ORDER BY 1, 3";
  $result = pupe_query($query);

  pupe_DataTables(array(array($pupe_DataTables, 13, 13, true, false)));

  include 'inc/pupeExcel.inc';

  $worksheet   = new pupeExcel();
  $excelrivi   = 0;
  $excelsarake = 0;

  $format_bold = array("bold" => TRUE);

  echo "<table class='display dataTable' id='$pupe_DataTables'><thead><tr>";
  echo "<th align='left'>".t("Toimitusaika")."</th>";
  echo "<th align='left'>".t("Nimi / Toim. Nimi")."</th>";
  echo "<th align='left'>".t("Tilausnumero")."</th>";
  echo "<th align='left'>".t("Tyyppi")."</th>";
  echo "<th align='left'>".t("Viesti")."</th>";
  echo "<th align='left'>".t("Rivihinta")."</th>";
  echo "<th align='left'>".t("Myyjä")."</th>";
  echo "<th align='left'>".t("Tuoteno")."</th>";
  echo "<th align='left'>".t("Nimitys")."</th>";
  echo "<th align='left'>".t("Tilattu")."</th>";
  echo "<th align='left'>".t("Myytävissä")."</th>";
  echo "<th align='left'>".t("Tulossa")."</th>";
  echo "<th align='left'>".t("Ennakkomyynti")."</th>";
  echo "</tr></thead>";

  $worksheet->write($excelrivi, $excelsarake, t("Toimitusaika"), $format_bold);
  $excelsarake++;
  $worksheet->write($excelrivi, $excelsarake, t("Nimi / Toim. Nimi"), $format_bold);
  $excelsarake++;
  $worksheet->write($excelrivi, $excelsarake, t("Tilausnumero"), $format_bold);
  $excelsarake++;
  $worksheet->write($excelrivi, $excelsarake, t("Tyyppi"), $format_bold);
  $excelsarake++;
  $worksheet->write($excelrivi, $excelsarake, t("Viesti"), $format_bold);
  $excelsarake++;
  $worksheet->write($excelrivi, $excelsarake, t("Rivihinta"), $format_bold);
  $excelsarake++;
  $worksheet->write($excelrivi, $excelsarake, t("Myyjä"), $format_bold);
  $excelsarake++;
  $worksheet->write($excelrivi, $excelsarake, t("Tuoteno"), $format_bold);
  $excelsarake++;
  $worksheet->write($excelrivi, $excelsarake, t("Nimitys"), $format_bold);
  $excelsarake++;
  $worksheet->write($excelrivi, $excelsarake, t("Tilattu"), $format_bold);
  $excelsarake++;
  $worksheet->write($excelrivi, $excelsarake, t("Myytävissä"), $format_bold);
  $excelsarake++;
  $worksheet->write($excelrivi, $excelsarake, t("Tulossa"), $format_bold);
  $excelsarake++;
  $worksheet->write($excelrivi, $excelsarake, t("Ennakkomyynti"), $format_bold);
  $excelsarake = 0;
  $excelrivi++;

  $myytavissa = array();
  $tilatut = array();
  $ennakot = array();

  echo "<tbody>";

  while ($prow = mysql_fetch_array($result)) {
    $out = js_openUrlNewWindow("{$palvelin2}tilauskasittely/tilauskanta.php?tee=NAYTATILAUS&tunnus={$prow['Tilausnro']}", $prow['Tilausnro'], NULL, 1000, 800);

    echo "<tr class='aktiivi'>";
    echo "<td class='top'>".pupe_DataTablesEchoSort($prow['toimaika']).tv1dateconv($prow['toimaika'])."</td>";
    echo "<td class='ptop text-right'>{$prow['nimi']}</td>";
    echo "<td class='ptop'>{$out}</td>";

    $laskutyyppi = $prow["tila"];
    $alatila     = $prow["alatila"];

    // tehdään selväkielinen tila/alatila
    require "inc/laskutyyppi.inc";

    $tarkenne = "";

    if ($prow["tila"] == "V" and $prow["tilaustyyppi"] == "V") {
      $tarkenne = " (".t("Asiakkaalle").") ";
    }
    elseif ($prow["tila"] == "V" and  $prow["tilaustyyppi"] == "W") {
      $tarkenne = " (".t("Varastoon").") ";
    }

    echo "<td valign='top'>".t("$laskutyyppi")." $tarkenne ".t("$alatila")."</td>";
    echo "<td class='ptop text-right'>{$prow['viesti']}</td>";
    echo "<td class='ptop text-right'>".str_replace(".", ",", $prow['rivihinta'])."</td>";
    echo "<td class='ptop text-right'>{$prow['myyja']}</td>";
    echo "<td class='ptop text-right'>{$prow['tuoteno']}</td>";
    echo "<td class='ptop text-right'>{$prow['nimitys']}</td>";
    echo "<td class='ptop text-right'>{$prow['varattu']}</td>";

    if (!isset($myytavissa[$prow['tuoteno']])) {
      list(, , $myyt) = saldo_myytavissa($prow['tuoteno'], '', $valitutvarastot);
      $myytavissa[$prow['tuoteno']] = $myyt;
    }

    echo "<td class='ptop text-right'>{$myytavissa[$prow['tuoteno']]}</td>";

    if (!isset($tilatut[$prow['tuoteno']])) {
      $query = "SELECT
                tilausrivi.tyyppi,
                tilausrivi.otunnus,
                tilausrivi.toimaika,
                max(tilausrivi.yksikko) yksikko,
                sum(if(tyyppi = 'O', varattu, 0)) tilattu,
                sum(if(tilausrivi.tyyppi = 'E' and tilausrivi.var != 'O', tilausrivi.varattu, 0)) ennakot
                FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
                WHERE yhtio        = '$kukarow[yhtio]'
                and tyyppi         in ('O','E')
                and tuoteno        = '$prow[tuoteno]'
                and laskutettuaika = '0000-00-00'
                and (varattu+jt > 0)
                GROUP BY 1, 2, 3";
      $ores = pupe_query($query);
      $tilviesti = array();
      $ennviesti = array();
      while ($ennp = mysql_fetch_assoc($ores)) {
        if ($ennp['tyyppi'] == "O") {
            $tilviesti[] = "{$ennp['otunnus']}: {$ennp['tilattu']} {$ennp['yksikko']} ".t("tulossa")." ".tv1dateconv($ennp['toimaika']);
        }
        if ($ennp['tyyppi'] == "E") {
            $ennviesti[] = "{$ennp['otunnus']}: {$ennp['ennakot']} {$ennp['yksikko']} ".t("toimaika")." ".tv1dateconv($ennp['toimaika']);
        }
      }
      $tilatut[$prow['tuoteno']] = implode(", ", $tilviesti);
      $ennakot[$prow['tuoteno']] = implode(", ", $ennviesti);
    }
    echo "<td class='ptop text-right'>{$tilatut[$prow['tuoteno']]}</td>";
    echo "<td class='ptop text-right'>{$ennakot[$prow['tuoteno']]}</td>";
    echo "</tr>";

    $worksheet->writeDate($excelrivi, $excelsarake, $prow['toimaika']);
    $excelsarake++;
    $worksheet->write($excelrivi, $excelsarake, $prow['nimi']);
    $excelsarake++;
    $worksheet->write($excelrivi, $excelsarake, $prow['Tilausnro']);
    $excelsarake++;
    $worksheet->write($excelrivi, $excelsarake, t("$laskutyyppi")." $tarkenne ".t("$alatila"));
    $excelsarake++;
    $worksheet->write($excelrivi, $excelsarake, $prow['viesti']);
    $excelsarake++;
    $worksheet->write($excelrivi, $excelsarake, $prow['rivihinta']);
    $excelsarake++;
    $worksheet->write($excelrivi, $excelsarake, $prow['myyja']);
    $excelsarake++;
    $worksheet->write($excelrivi, $excelsarake, $prow['tuoteno']);
    $excelsarake++;
    $worksheet->write($excelrivi, $excelsarake, $prow['nimitys']);
    $excelsarake++;
    $worksheet->write($excelrivi, $excelsarake, $prow['varattu']);
    $excelsarake++;
    $worksheet->write($excelrivi, $excelsarake, $myytavissa[$prow['tuoteno']]);
    $excelsarake++;
    $worksheet->write($excelrivi, $excelsarake, $tilatut[$prow['tuoteno']]);
    $excelsarake++;
    $worksheet->write($excelrivi, $excelsarake, $ennakot[$prow['tuoteno']]);
    $excelsarake = 0;
    $excelrivi++;

  }

  echo "</tbody>";
  echo "</table><br><br>";

  $excelnimi = $worksheet->close();

  echo "<br><br><table>";
  echo "<tr><th>".t("Tallenna tulos").":</th>";
  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='Tilauskanta_saatavuus.xlsx'>";
  echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna excel")."'></td></tr></form>";
  echo "</table><br>";
}

require "../inc/footer.inc";
