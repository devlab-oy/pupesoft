<?php

// DataTables päälle
$pupe_DataTables = "laatutable";

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

$tee = isset($tee) ? $tee : "";

if ($tee == "lataa_tiedosto") {
  readfile("/tmp/" . $tmpfilenimi);
  exit;
}

if ($tee == 'NAYTATILAUS') {
  echo "<font class='head'>", t("Tilausnro"), ": {$tunnus}</font><hr>";
  require "naytatilaus.inc";
  require "inc/footer.inc";
  exit;
}

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

if ($toim == 'MYYNTI') {
  echo "<font class='head'>".t("Toimitusvarmuus / asiakkaat").":</font><hr>";
}
elseif ($toim == 'AVOIMET') {
  echo "<font class='head'>".t("Toimitusvarmuus / asiakkaat / avoimet").":</font><hr>";
}
elseif ($toim == 'OSTO') {
  echo "<font class='head'>".t("Toimitusvarmuus / toimittajat").":</font><hr>";
}
elseif ($toim == 'KAIKKIAVOIMET') {
  echo "<font class='head'>".t("Kaikki avoimet toimitusrivit").":</font><hr>";
}

if (isset($vaihda)) {
  unset($asiakasid);
  unset($toimittajaid);
  unset($ytunnus);
}

if ($ytunnus != '' or (int) $asiakasid > 0 or (int) $toimittajaid > 0) {

  $muutparametrit = $vva."/".$kka."/".$ppa."/".$vvl."/".$kkl."/".$ppl."/".$tuoteno;

  if ($toim == 'MYYNTI' or $toim == 'AVOIMET' or $toim == 'KAIKKIAVOIMET') {
    require "inc/asiakashaku.inc";
    if (empty($asiakasid)) {
      unset($etsinappi);
    }
    else {
      $etsinappi = TRUE;
    }
  }
  elseif ($toim == 'OSTO') {
    require "../inc/kevyt_toimittajahaku.inc";
    if (empty($toimittajaid)) {
      unset($etsinappi);
    }
    else {
      $etsinappi = TRUE;
    }
  }
}

if ($tuoteno != '') {
  require 'inc/tuotehaku.inc';
}

// Pikku scripti formin tyhjentämiseen
echo "<script type='text/javascript' language='javascript'>
function vaihdaClick() {
  document.etsiform.etsinappi.name='vaihda';
  document.etsiform.etsinappi.click();
}
</script>";

//Etsi-kenttä
echo "<br>
    <form method='post' id='etsiform' name='etsiform'>
    <input type='hidden' name='toim' value='$toim'>
    <input type='hidden' name='tee' value='ETSIX'>
    <table>";

if ($kka == '')
  $kka = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if ($vva == '')
  $vva = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if ($ppa == '')
  $ppa = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

if ($kkl == '')
  $kkl = date("m");
if ($vvl == '')
  $vvl = date("Y");
if ($ppl == '')
  $ppl = date("d");

if ($toim == 'MYYNTI' or $toim == 'AVOIMET' or $toim == 'KAIKKIAVOIMET') {
  echo "<tr><th>".t("Asiakas").":</th>";
}
if ($toim == 'OSTO') {
  echo "<tr><th>".t("Toimittaja").":</th>";
}

if ((int) $asiakasid > 0 or (int) $toimittajaid > 0) {
  if ($toim == 'MYYNTI' or $toim == 'AVOIMET' or $toim == 'KAIKKIAVOIMET') {
    echo "<td colspan='3'>";
    echo "$asiakasrow[nimi] $asiakasrow[nimitark]";
    echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
    echo "</td>";
    echo "<td class='back'>";
    echo "<input type='button' onclick='vaihdaClick();' value='".t("Vaihda asiakasta")."'>";
    echo "</td>";
  }
  if ($toim == 'OSTO') {
    echo "<td colspan='3'>";
    echo "$toimittajarow[nimi] $toimittajarow[nimitark]";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "</td>";
    echo "<td class='back'>";
    echo "<input type='button' onclick='vaihdaClick();'  value='".t("Vaihda toimittajaa")."'>";
    echo "</td>";
  }
}
else {
  echo "<td colspan='3'><input type='text' name='ytunnus' value='$ytunnus' size='20'></td>";
}

echo "</tr>";
echo "<tr><th>".t("Syötä tuotenumero").":</th>
      <td colspan='3'>";

if (isset($tuoteno) and trim($ulos) != '') {
  echo $ulos;
}
else {
  echo "<input type='text' name='tuoteno' value='$tuoteno' size='20'>";
}

echo "</td>";

if ($toim != 'AVOIMET' and $toim != 'KAIKKIAVOIMET') {
  echo "</tr><tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
        <td><input type='text' name='ppa' value='$ppa' size='3'></td>
        <td><input type='text' name='kka' value='$kka' size='3'></td>
        <td><input type='text' name='vva' value='$vva' size='5'></td>
        </tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
        <td><input type='text' name='ppl' value='$ppl' size='3'></td>
        <td><input type='text' name='kkl' value='$kkl' size='3'></td>
        <td><input type='text' name='vvl' value='$vvl' size='5'></td>";
}

if ($toim != 'KAIKKIAVOIMET') {
  $tasoselected = array_fill_keys(array($raptaso), " selected") + array_fill_keys(array('yritys', 'partneri', 'tilaus', 'rivi'), '');

  echo "</tr><tr><th>".t("Raportointitaso")."</th>
        <td colspan='3'><select name='raptaso'>
        <option value='yritys' {$tasoselected['yritys']}>".t("Yhteenveto")."</option>
        <option value='partneri' {$tasoselected['partneri']}>".t("Asiakas/toimittaja")."</option>
        <option value='tilaus' {$tasoselected['tilaus']}>".t("Tilaus")."</option>";

  if ($toim == 'KAIKKIAVOIMET') {
    echo "<option value='rivi' {$tasoselected['rivi']}>".t("Rivi")."</option>";
  }

  echo "</select></td>";
}
else {
  $raptaso = "rivi";
}

echo "<td class='back'><input type='submit' id='etsinappi' name='etsinappi' value='".t("Näytä")."'></td></tr>";
echo "</table>";
echo "</form>";

if (!empty($etsinappi)) {

  $lisa = "";

  if ($tuoteno != '') {
    $lisa .= " and tilausrivi.tuoteno='$tuoteno' ";
  }

  if ((int) $asiakasid > 0) {
    $lisa .= " and lasku.liitostunnus = '$asiakasid' ";
  }

  if ((int) $toimittajaid > 0) {
    $lisa .= " and lasku.liitostunnus = '$toimittajaid' ";
  }

  if ($toim == 'OSTO') {

    $onkomyohassa_sql = "if(tilausrivi.laskutettuaika > 0, tilausrivi.laskutettuaika, CURDATE()) ";

    if ($raptaso == "yritys") {
      $query = "SELECT
                count(distinct lasku.tunnus) tilauksia,
                count(tilausrivi.tunnus) riveja,
                count(distinct if($onkomyohassa_sql > tilausrivi.toimaika, lasku.tunnus, null)) myohassa_tilauksia,
                sum(if($onkomyohassa_sql > tilausrivi.toimaika, 1, 0)) myohassa_riveja,
                round(avg(
                  if($onkomyohassa_sql > tilausrivi.toimaika, DATEDIFF($onkomyohassa_sql, tilausrivi.toimaika), null)
                ), 1) myohassa_pva
                ";
    }
    elseif ($raptaso == "partneri") {
      $query = "SELECT
                lasku.liitostunnus,
                max(lasku.nimi) nimi,
                count(distinct lasku.tunnus) tilauksia,
                count(tilausrivi.tunnus) riveja,
                count(distinct if($onkomyohassa_sql > tilausrivi.toimaika, lasku.tunnus, null)) myohassa_tilauksia,
                sum(if($onkomyohassa_sql > tilausrivi.toimaika, 1, 0)) myohassa_riveja,
                round(avg(
                  if($onkomyohassa_sql > tilausrivi.toimaika, DATEDIFF($onkomyohassa_sql, tilausrivi.toimaika), null)
                ), 1) myohassa_pva
                ";
    }
    else {
      $query = "SELECT
                lasku.nimi,
                lasku.ytunnus,
                lasku.liitostunnus,
                lasku.tunnus tilaus,
                from_unixtime(
                  avg(
                    unix_timestamp(tilausrivi.toimaika)
                  ), '%Y-%m-%d'
                ) toimaika,
                count(tilausrivi.tunnus) riveja,
                sum(if($onkomyohassa_sql > tilausrivi.toimaika, 1, 0)) myohassa_riveja,
                from_unixtime(
                  avg(
                    unix_timestamp(tilausrivi.laskutettuaika)
                  ), '%Y-%m-%d'
                ) toimitettu,
                round(avg(
                  DATEDIFF($onkomyohassa_sql, tilausrivi.toimaika)
                ), 1) myohassa_pva
                ";
    }

    $query .= " FROM tilausrivi
                JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and lasku.tila = 'O')
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tuote.ei_saldoa = '')
                WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                and tilausrivi.tyyppi = 'O'
                and tilausrivi.laskutettuaika >='$vva-$kka-$ppa'
                and tilausrivi.laskutettuaika <='$vvl-$kkl-$ppl'
                {$lisa}";

    if ($raptaso == "partneri") {
      $query .= " GROUP BY 1 ";
    }
    elseif ($raptaso == "tilaus") {
      $query .= " GROUP BY 1,2,3,4 ";
    }

    $query .= " ORDER BY 1 ";
  }
  else {
    $onkomyohassa_sql = " if (
                            left(tilausrivi.toimitettuaika, 10) > 0 and (toimitustapa.nouto is null or toimitustapa.nouto = ''),
                            left(tilausrivi.toimitettuaika, 10),
                            if (
                              left(tilausrivi.kerattyaika, 10) > 0 and toimitustapa.nouto != '',
                              left(tilausrivi.kerattyaika, 10),
                              CURDATE()
                            )
                          ) ";

    if ($raptaso == "yritys") {
      $query = "SELECT
                count(distinct lasku.tunnus) tilauksia,
                count(tilausrivi.tunnus) riveja,
                count(distinct if($onkomyohassa_sql > lasku.toimaika, lasku.tunnus, null)) myohassa_tilauksia,
                sum(if($onkomyohassa_sql > lasku.toimaika, 1, 0)) myohassa_riveja,
                round(avg(
                  if($onkomyohassa_sql > lasku.toimaika, DATEDIFF($onkomyohassa_sql, tilausrivi.toimaika), null)
                ), 1) myohassa_pva
                ";
    }
    elseif ($raptaso == "partneri") {
      $query = "SELECT
                lasku.liitostunnus,
                max(lasku.nimi) nimi,
                count(distinct lasku.tunnus) tilauksia,
                count(tilausrivi.tunnus) riveja,
                count(distinct if($onkomyohassa_sql > lasku.toimaika, lasku.tunnus, null)) myohassa_tilauksia,
                sum(if($onkomyohassa_sql > lasku.toimaika, 1, 0)) myohassa_riveja,
                round(avg(
                  if($onkomyohassa_sql > lasku.toimaika, DATEDIFF($onkomyohassa_sql, tilausrivi.toimaika), null)
                ), 1) myohassa_pva
                ";
    }
    elseif ($raptaso == "rivi") {
      $query_ale_lisa = generoi_alekentta('M');

      $query = "SELECT
                lasku.liitostunnus,
                lasku.tunnus tilaus,
                lasku.viesti,
                lasku.nimi,
                tilausrivi.tuoteno,
                tilausrivi.nimitys,
                tilausrivi.varattu,
                tilausrivi.yksikko,
                round(tilausrivi.hinta, 2) hinta,
                round(tilausrivi.hinta * tilausrivi.varattu * {$query_ale_lisa}, 2) AS rivihinta,
                tilausrivi.toimaika
                ";
    }
    else {
      $query = "SELECT
                lasku.nimi,
                lasku.tila,
                lasku.alatila,
                lasku.tunnus tilaus,
                lasku.toimaika,
                count(tilausrivi.tunnus) riveja,
                sum(if($onkomyohassa_sql > lasku.toimaika, 1, 0)) myohassa_riveja,
                from_unixtime(
                  avg(
                    unix_timestamp(
                      if (
                        (toimitustapa.nouto is null or toimitustapa.nouto = ''),
                        tilausrivi.toimitettuaika,
                        tilausrivi.kerattyaika
                      )
                    )
                  ), '%Y-%m-%d'
                ) toimitettu,
                round(avg(
                  DATEDIFF($onkomyohassa_sql, tilausrivi.toimaika)
                ), 1) myohassa_pva
                ";
    }

    $query .= " FROM tilausrivi";

    if ($toim == 'AVOIMET' or $toim == 'KAIKKIAVOIMET') {
      $tilalisa = "or (lasku.tila='V' and tilaustyyppi = 'V')";
    }

    $query .= " JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
                    and (lasku.tila = 'L' $tilalisa))
                LEFT JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa)
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tilausrivi.tuoteno = tuote.tuoteno
                  and tuote.ei_saldoa = '')
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                and tilausrivi.var not in ('P','J','O','S')";

    if ($toim == 'AVOIMET') {
      $query .= " and tilausrivi.tyyppi in ('L','M','W')
                  and (tilausrivi.varattu + tilausrivi.jt) != 0
                  and tilausrivi.toimitettuaika = '0000-00-00 00:00:00'
                  and tilausrivi.laskutettuaika = '0000-00-00'
                  and tilausrivi.toimaika < CURDATE()";
    }
    elseif ($toim == 'KAIKKIAVOIMET') {
      $query .= " and tilausrivi.tyyppi in ('L','M','W')
                  and (tilausrivi.varattu + tilausrivi.jt) != 0
                  and tilausrivi.laskutettuaika = '0000-00-00'";
    }
    else {
      $query .= " and tilausrivi.tyyppi = 'L'
                  and tilausrivi.toimitettuaika >='$vva-$kka-$ppa 00:00:00'
                  and tilausrivi.toimitettuaika <='$vvl-$kkl-$ppl 23:59:59'";
    }

    $query .= " {$lisa} ";

    if ($raptaso == "partneri") {
      $query .= " GROUP BY 1 ";
    }
    elseif ($raptaso == "tilaus") {
      $query .= " GROUP BY 1,2,3,4 ";
    }

    $query .= " ORDER BY 1 ";
  }

  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    echo "<br>";

    include 'inc/pupeExcel.inc';

    $worksheet   = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi   = 0;

    if ($raptaso == "yritys") {
      echo "<table>";
      echo "<thead>";
      echo "<tr>";
      echo "<th>".t("Tilauksia")."</th>";
      echo "<th>".t("Tilauksia myöhässä")."</th>";
      echo "<th>".t("Tilauksia myöhässä")." %</th>";
      echo "<th>".t("Rivejä")."</th>";
      echo "<th>".t("Rivejä myöhässä")."</th>";
      echo "<th>".t("Rivejä myöhässä")." %</th>";
      echo "<th>".t("Myöhässä / pvä")."</th>";
      echo "</tr>";
      echo "</thead>";

      $i=0;
      $worksheet->write($excelrivi, $i++, t("Tilauksia"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Tilauksia myöhässä"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Tilauksia myöhässä")." %", $format_bold);
      $worksheet->write($excelrivi, $i++, t("Rivejä"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Rivejä myöhässä"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Rivejä myöhässä")." %", $format_bold);
      $worksheet->write($excelrivi, $i++, t("Myöhässä / pvä"), $format_bold);
      $excelrivi++;
    }
    elseif ($raptaso == "partneri") {
      pupe_DataTables(array(array($pupe_DataTables, 8, 8, false, true)));

      echo "<table class='display dataTable' id='$pupe_DataTables'>";
      echo "<thead>";
      echo "<tr>";

      if ($toim == "ASIAKAS") {
        echo "<th>".t("Asiakas")."</th>";
      }
      else {
        echo "<th>".t("Toimittaja")."</th>";
      }

      echo "<th>".t("Tilauksia")."</th>";
      echo "<th>".t("Tilauksia myöhässä")."</th>";
      echo "<th>".t("Tilauksia myöhässä")." %</th>";
      echo "<th>".t("Rivejä")."</th>";
      echo "<th>".t("Rivejä myöhässä")."</th>";
      echo "<th>".t("Rivejä myöhässä")." %</th>";
      echo "<th>".t("Myöhässä / pvä")."</th>";
      echo "</tr>";
      echo "<tr>";
      echo "<td><input type='text' class='search_field' name='search_asiakas'></td>";
      echo "<td><input type='text' class='search_field' name='search_tilaus'></td>";
      echo "<td><input type='text' class='search_field' name='search_myohtil'></td>";
      echo "<td><input type='text' class='search_field' name='search_myohtilpros'></td>";
      echo "<td><input type='text' class='search_field' name='search_riveja'></td>";
      echo "<td><input type='text' class='search_field' name='search_myohriv'></td>";
      echo "<td><input type='text' class='search_field' name='search_myohrivpros'></td>";
      echo "<td><input type='text' class='search_field' name='search_myohrivpva'></td>";
      echo "</tr>";
      echo "</thead>";

      $i=0;

      if ($toim == "ASIAKAS") {
        $worksheet->write($excelrivi, $i++, t("Asiakas"), $format_bold);
      }
      else {
        $worksheet->write($excelrivi, $i++, t("Toimittaja"), $format_bold);
      }

      $worksheet->write($excelrivi, $i++, t("Tilauksia"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Tilauksia myöhässä"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Tilauksia myöhässä")." %", $format_bold);
      $worksheet->write($excelrivi, $i++, t("Rivejä"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Rivejä myöhässä"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Rivejä myöhässä")." %", $format_bold);
      $worksheet->write($excelrivi, $i++, t("Myöhässä / pvä"), $format_bold);
      $excelrivi++;
    }
    elseif ($raptaso == "rivi") {
      pupe_DataTables(array(array($pupe_DataTables, 10, 10, false, true)));

      echo "<table class='display dataTable' id='$pupe_DataTables'>";
      echo "<thead>";
      echo "<tr>";

      echo "<th>".t("Asiakas")."</th>";
      echo "<th>".t("Tilaus")."</th>";
      echo "<th>".t("Tilausviite")."</th>";
      echo "<th>".t("Tuoteno")."</th>";
      echo "<th>".t("Nimitys")."</th>";
      echo "<th>".t("Määrä")."</th>";
      echo "<th>".t("Yksikkö")."</th>";
      echo "<th>".t("Hinta")."</th>";
      echo "<th>".t("Rivihinta")."</th>";
      echo "<th>".t("Toimaika")."</th>";

      echo "</tr>";
      echo "<tr>";
      echo "<td><input type='text' class='search_field' name='search_asiakas'></td>";
      echo "<td><input type='text' class='search_field' name='search_tilaus'></td>";
      echo "<td><input type='text' class='search_field' name='search_tilausviite'></td>";
      echo "<td><input type='text' class='search_field' name='search_tuoteno'></td>";
      echo "<td><input type='text' class='search_field' name='search_nimitys'></td>";
      echo "<td><input type='text' class='search_field' name='search_maara'></td>";
      echo "<td><input type='text' class='search_field' name='search_yksikko'></td>";
      echo "<td><input type='text' class='search_field' name='search_hinta'></td>";
      echo "<td><input type='text' class='search_field' name='search_rivihinta'></td>";
      echo "<td><input type='text' class='search_field' name='search_toimaika'></td>";
      echo "</tr>";
      echo "</thead>";

      $i=0;

      $worksheet->write($excelrivi, $i++, t("Asiakas"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Tilaus"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Tilausviite"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Tuoteno"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Nimitys"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Määrä"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Yksikkö"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Hinta"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Rivihinta"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Toimaika"), $format_bold);
      $excelrivi++;
    }
    else {

      $sarakkeet = 7;

      if ($toim == "MYYNTI" or $toim == 'AVOIMET' or $toim == 'KAIKKIAVOIMET') {
        $editoikkarit = tarkista_oikeus('tilaus_myynti.php', 'RIVISYOTTO');
        $sarakkeet++;

      }
      else {
        $editoikkarit = tarkista_oikeus('tilaus_osto.php');
        $sarakkeet++;
      }


      pupe_DataTables(array(array($pupe_DataTables, $sarakkeet, $sarakkeet, false, true)));

      echo "<table class='display dataTable' id='$pupe_DataTables'>";
      echo "<thead>";
      echo "<tr>";

      if ($toim == "ASIAKAS") {
        echo "<th>".t("Asiakas")."</th>";
      }
      else {
        echo "<th>".t("Toimittaja")."</th>";
      }

      echo "<th>".t("Tilaus")."</th>";
      echo "<th>".t("Toimitusaika")."</th>";
      echo "<th>".t("Toimitettu")."</th>";
      echo "<th>".t("Rivejä")."</th>";
      echo "<th>".t("Rivejä myöhässä")."</th>";
      echo "<th>".t("Myöhässä / pvä")."</th>";
      echo "<th></th>";
      echo "</tr>";
      echo "<tr>";
      echo "<td><input type='text' class='search_field' name='search_asiakas'></td>";
      echo "<td><input type='text' class='search_field' name='search_tilaus'></td>";
      echo "<td><input type='text' class='search_field' name='search_toimitusaika'></td>";
      echo "<td><input type='text' class='search_field' name='search_toimitettu'></td>";
      echo "<td><input type='text' class='search_field' name='search_riveja'></td>";
      echo "<td><input type='text' class='search_field' name='search_myohriv'></td>";
      echo "<td><input type='text' class='search_field' name='search_myohpva'></td>";
      echo "<td></td>";
      echo "</tr>";
      echo "</thead>";

      $i=0;
      $worksheet->write($excelrivi, $i++, t("Tilaus"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Toimitusaika"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Toimitettu"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Rivejä"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Rivejä myöhässä"), $format_bold);
      $worksheet->write($excelrivi, $i++, t("Myöhässä / pvä"), $format_bold);
      $excelrivi++;
    }

    echo "<tbody>";

    while ($row = mysql_fetch_assoc($result)) {

      if (substr($row['toimitettu'], 0, 4) == "1970") {
        $row['toimitettu'] = "";
      }

      if ($row['myohassa_pva'] <= 0) {
        $row['myohassa_pva'] = "";
        $row['myohassa_riveja'] = "";
        $row['myohassa_tilauksia'] = "";
      }

      echo "<tr class='aktiivi'>";

      if ($raptaso == "yritys") {
        $pros_myohassa_til = round($row['myohassa_tilauksia'] / $row['tilauksia'] * 100, 1);
        $pros_myohassa_riv = round($row['myohassa_riveja'] / $row['riveja'] * 100, 1);

        echo "<td align='right'><a href='$PHP_SELF?toim=$toim&raptaso=partneri&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsinappi=yes'>{$row['tilauksia']}</a></td>";
        echo "<td align='right'>{$row['myohassa_tilauksia']}</td>";
        echo "<td align='right'>{$pros_myohassa_til} %</td>";
        echo "<td align='right'>{$row['riveja']}</td>";
        echo "<td align='right'>{$row['myohassa_riveja']}</td>";
        echo "<td align='right'>{$pros_myohassa_riv} %</td>";
        echo "<td align='right'>{$row['myohassa_pva']}</td>";

        $i=0;
        $worksheet->write($excelrivi, $i++, $row['tilauksia']);
        $worksheet->write($excelrivi, $i++, $row['myohassa_tilauksia']);
        $worksheet->write($excelrivi, $i++, $pros_myohassa_til);
        $worksheet->write($excelrivi, $i++, $row['riveja']);
        $worksheet->write($excelrivi, $i++, $row['myohassa_riveja']);
        $worksheet->write($excelrivi, $i++, $pros_myohassa_riv);
        $worksheet->write($excelrivi, $i++, $row['myohassa_pva']);
        $excelrivi++;
      }
      elseif ($raptaso == "partneri") {
        $pros_myohassa_til = round($row['myohassa_tilauksia'] / $row['tilauksia'] * 100, 1);
        $pros_myohassa_riv = round($row['myohassa_riveja'] / $row['riveja'] * 100, 1);

        if ($toim == "MYYNTI" or $toim == 'AVOIMET' or $toim == 'KAIKKIAVOIMET') {
          $plisa = "&asiakasid=$row[liitostunnus]";
        }
        else {
          $plisa = "&toimittajaid=$row[liitostunnus]";
        }

        echo "<td><a href='$PHP_SELF?toim=$toim&raptaso=tilaus&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&etsinappi=yes$plisa'>{$row['nimi']}</a></td>";
        echo "<td align='right'>{$row['tilauksia']}</td>";
        echo "<td align='right'>{$row['myohassa_tilauksia']}</td>";
        echo "<td align='right'>{$pros_myohassa_til} %</td>";
        echo "<td align='right'>{$row['riveja']}</td>";
        echo "<td align='right'>{$row['myohassa_riveja']}</td>";
        echo "<td align='right'>{$pros_myohassa_riv} %</td>";
        echo "<td align='right'>{$row['myohassa_pva']}</td>";

        $i=0;
        $worksheet->write($excelrivi, $i++, $row['nimi']);
        $worksheet->write($excelrivi, $i++, $row['tilauksia']);
        $worksheet->write($excelrivi, $i++, $row['myohassa_tilauksia']);
        $worksheet->write($excelrivi, $i++, $pros_myohassa_til);
        $worksheet->write($excelrivi, $i++, $row['riveja']);
        $worksheet->write($excelrivi, $i++, $row['myohassa_riveja']);
        $worksheet->write($excelrivi, $i++, $pros_myohassa_riv);
        $worksheet->write($excelrivi, $i++, $row['myohassa_pva']);
        $excelrivi++;
      }
      elseif ($raptaso == "rivi") {
        echo "<td>{$row['nimi']}</td>";
        echo "<td>{$row['tilaus']}</td>";
        echo "<td>{$row['viesti']}</td>";
        echo "<td>{$row['tuoteno']}</td>";
        echo "<td>{$row['nimitys']}</td>";
        echo "<td align='right'>{$row['varattu']}</td>";
        echo "<td>{$row['yksikko']}</td>";
        echo "<td align='right'>{$row['hinta']}</td>";
        echo "<td align='right'>{$row['rivihinta']}</td>";
        echo "<td align='right'>".pupe_DataTablesEchoSort($row['toimaika']).tv1dateconv($row["toimaika"])."</td>";

        $i=0;
        $worksheet->write($excelrivi, $i++, $row['nimi']);
        $worksheet->write($excelrivi, $i++, $row['tilaus']);
        $worksheet->write($excelrivi, $i++, $row['viesti']);
        $worksheet->write($excelrivi, $i++, $row['tuoteno']);
        $worksheet->write($excelrivi, $i++, $row['nimitys']);
        $worksheet->write($excelrivi, $i++, $row['varattu']);
        $worksheet->write($excelrivi, $i++, $row['yksikko']);
        $worksheet->write($excelrivi, $i++, $row['hinta']);
        $worksheet->write($excelrivi, $i++, $row['rivihinta']);
        $worksheet->write($excelrivi, $i++, $row['toimaika']);
        $excelrivi++;
      }
      else {
        echo "<td>{$row['nimi']}</td>";

        $out = js_openUrlNewWindow("{$palvelin2}raportit/toimitusvarmuus.php?toim=$toim&tee=NAYTATILAUS&tunnus={$row['tilaus']}", $row['tilaus'], NULL, 1000, 800);

        echo "<td>{$out}</td>";
        echo "<td>".pupe_DataTablesEchoSort($row['toimaika']).tv1dateconv($row['toimaika'])."</td>";
        echo "<td>".pupe_DataTablesEchoSort($row['toimitettu']).tv1dateconv($row['toimitettu'])."</td>";
        echo "<td align='right'>{$row['riveja']}</td>";
        echo "<td align='right'>{$row['myohassa_riveja']}</td>";
        echo "<td align='right'>{$row['myohassa_pva']}</td>";

        if ($editoikkarit) {
          echo "<td>";

          $lop = "&lopetus=$PHP_SELF////toim=$toim//raptaso=tilaus//ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl//etsinappi=yes";

          if (($toim == "MYYNTI" or $toim == 'AVOIMET') and $row['alatila'] != 'X') {
            $lop .= "//asiakasid=$asiakasid";

            if ($row['tila'] == 'V') {
              $toimi = "VALMISTAASIAKKAALLE";
            }
            else {
              $toimi = "RIVISYOTTO";
            }

            echo "<a href='{$palvelin2}tilauskasittely/tilaus_myynti.php?toim={$toimi}&tilausnumero=$row[tilaus]$lop'><img src='{$palvelin2}pics/lullacons/doc-option-edit.png'></a>";
          }

          if ($toim == "OSTO") {
            $lop .= "//toimittajaid=$toimittajaid";

            echo "<a href='{$palvelin2}tilauskasittely/ostotilausrivien_hallinta.php?nayta_rivit=ihankaikki&ytunnus={$row['ytunnus']}&otunnus={$row['tilaus']}&toimittajaid={$row['liitostunnus']}$lop'><img src='{$palvelin2}pics/lullacons/doc-option-edit.png'></a>";
          }

          echo "</td>";
        }

        $i=0;
        $worksheet->write($excelrivi, $i++, $row['tilaus']);
        $worksheet->write($excelrivi, $i++, $row['toimaika']);
        $worksheet->write($excelrivi, $i++, $row['toimitettu']);
        $worksheet->write($excelrivi, $i++, $row['riveja']);
        $worksheet->write($excelrivi, $i++, $row['myohassa_riveja']);
        $worksheet->write($excelrivi, $i++, $row['myohassa_pva']);
        $excelrivi++;
      }

      echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";

    $excelnimi = $worksheet->close();
    $kaunisnimi = "Toimitusvarmuus\_$ppa$kka$vva-$ppl$kkl$vvl.xlsx";

    if ($toim == 'KAIKKIAVOIMET') {
      $kaunisnimi = "Avoimet_rivit\_$ppl$kkl$vvl.xlsx";
    }

    echo "<br><br><table>";
    echo "<tr><th>".t("Tallenna Excel").":</th><td class='back'>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='$kaunisnimi'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
    echo "<input type='submit' value='".t("Tallenna")."'></form></td></tr>";
    echo "</table><br>";
  }
}

if ((int) $asiakasid > 0 or (int) $toimittajaid > 0) {
  echo "<br>";
  echo "<form action = 'toimitusvarmuus.php' method = 'post'>
    <input type='hidden' name='toim' value='$toim'>
    <input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<br><input type='submit' value='".t("Tee uusi haku")."'>";
  echo "</form>";
}

require "inc/footer.inc";
