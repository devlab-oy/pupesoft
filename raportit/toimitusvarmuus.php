<?php

// DataTables päälle
$pupe_DataTables = "laatutable";

require "../inc/parametrit.inc";

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

if ($toim == 'OSTO') {
  echo "<font class='head'>".t("Toimitusvarmuus / toimittajat").":</font><hr>";
}

if (isset($vaihda)) {
  unset($asiakasid);
  unset($toimittajaid);
  unset($ytunnus);
}

if ($ytunnus != '' or (int) $asiakasid > 0 or (int) $toimittajaid > 0) {

  $muutparametrit = $vva."/".$kka."/".$ppa."/".$vvl."/".$kkl."/".$ppl."/".$tuoteno;

  if ($toim == 'MYYNTI') {
    require "inc/asiakashaku.inc";
  }
  if ($toim == 'OSTO') {
    require "../inc/kevyt_toimittajahaku.inc";
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
echo "<br><table><form method='post' id='etsiform' name='etsiform'>
    <input type='hidden' name='toim' value='$toim'>
    <input type='hidden' name='tee' value='ETSI'>";

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

if ($toim == 'MYYNTI') {
  echo "<tr><th>".t("Asiakas").":</th>";
}
if ($toim == 'OSTO') {
  echo "<tr><th>".t("Toimittaja").":</th>";
}

if ($toim == 'OSTO' and $pvmtapa == 'toimaika') {
  $pvmtapa = "toimaika";
  $pvm_select1 = "";
  $pvm_select2 = "SELECTED";
}
elseif ($toim == 'MYYNTI' and $pvmtapa == 'laskutettu') {
  $pvmtapa = "laskutettuaika";
  $pvm_select1 = "";
  $pvm_select2 = "SELECTED";
}

if ((int) $asiakasid > 0 or (int) $toimittajaid > 0) {
  if ($toim == 'MYYNTI') {
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
echo "  <tr><th>".t("Syötä tuotenumero").":</th>
    <td colspan='3'>";

if (isset($tuoteno) and trim($ulos) != '') {
  echo $ulos;
}
else {
  echo "<input type='text' name='tuoteno' value='$tuoteno' size='20'>";
}

echo "</td></tr>";

echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppa' value='$ppa' size='3'></td>
    <td><input type='text' name='kka' value='$kka' size='3'></td>
    <td><input type='text' name='vva' value='$vva' size='5'></td>
    </tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppl' value='$ppl' size='3'></td>
    <td><input type='text' name='kkl' value='$kkl' size='3'></td>
    <td><input type='text' name='vvl' value='$vvl' size='5'></td>";

if ($toim == 'OSTO') {
  echo "</tr><tr><th>".t("Valitse päivämäärän tyyppi")."</th>
    <td colspan='3'><select name='pvmtapa'>
      <option value='laadittu' $pvm_select1>".t("Tilauksen laatimispäivämäärä")."</option>
      <option value='toimaika' $pvm_select2>".t("Tilauksen toivottu toimituspäivämäärä")."</option>
    </select></td>";
}
else {
  echo "</tr><tr><th>".t("Valitse päivämäärän tyyppi")."</th>
    <td colspan='3'><select name='pvmtapa'>
      <option value='laadittu' $pvm_select1>".t("Tilauksen laatimispäivämäärä")."</option>
      <option value='laskutettu' $pvm_select2>".t("Tilauksen laskutuspäivämäärä")."</option>
    </select></td>";
}

$tasoselected = array_fill_keys(array($raptaso), " selected") + array_fill_keys(array('yritys', 'partneri', 'tilaus'), '');

echo "</tr><tr><th>".t("Raportointitaso")."</th>
  <td colspan='3'><select name='raptaso'>
    <option value='yritys' {$tasoselected['yritys']}>".t("Yhteenveto")."</option>
    <option value='partneri' {$tasoselected['partneri']}>".t("Asiakas/toimittaja")."</option>
    <option value='tilaus' {$tasoselected['tilaus']}>".t("Tilaus")."</option>
  </select></td>";

echo "<td class='back'><input id='etsinappi' name='etsinappi' type='submit' value='".t("Näytä")."'></td></tr></form></table>";

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
                sum(if($onkomyohassa_sql, 1, 0)) myohassa_riveja,
                round(avg(
                  if($onkomyohassa_sql, DATEDIFF($onkomyohassa_sql, tilausrivi.toimaika), null)
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
                lasku.tunnus tilaus,
                lasku.toimaika,
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
                and tilausrivi.$pvmtapa >='$vva-$kka-$ppa 00:00:00'
                and tilausrivi.$pvmtapa <='$vvl-$kkl-$ppl 23:59:59'
                {$lisa}";

    if ($raptaso == "partneri") {
      $query .= " GROUP BY 1 ";
    }
    elseif ($raptaso == "tilaus") {
      $query .= " GROUP BY 1,2,3 ";
    }

    $query .= " ORDER BY 1 ";
  }
  else {
    if ($raptaso == "yritys") {
      $query = "SELECT
                count(distinct lasku.tunnus) tilauksia,
                count(tilausrivi.tunnus) riveja,
                count(distinct if(left(tilausrivi.toimitettuaika, 10) > lasku.toimaika, lasku.tunnus, null)) myohassa_tilauksia,
                sum(if(left(tilausrivi.toimitettuaika, 10) > lasku.toimaika, 1, 0)) myohassa_riveja,
                from_unixtime(
                  avg(
                    unix_timestamp(tilausrivi.toimitettuaika)
                  ), '%Y-%m-%d'
                ) toimitettu,
                round(avg(
                  if(left(tilausrivi.toimitettuaika, 10) > lasku.toimaika, DATEDIFF(tilausrivi.toimitettuaika, tilausrivi.toimaika), null)
                ), 1) myohassa_pva
                ";
    }
    elseif ($raptaso == "partneri") {
      $query = "SELECT
                lasku.liitostunnus,
                max(lasku.nimi) nimi,
                count(distinct lasku.tunnus) tilauksia,
                count(tilausrivi.tunnus) riveja,
                count(distinct if(left(tilausrivi.toimitettuaika, 10) > lasku.toimaika, lasku.tunnus, null)) myohassa_tilauksia,
                sum(if(left(tilausrivi.toimitettuaika, 10) > lasku.toimaika, 1, 0)) myohassa_riveja,
                from_unixtime(
                  avg(
                    unix_timestamp(tilausrivi.toimitettuaika)
                  ), '%Y-%m-%d'
                ) toimitettu,
                round(avg(
                  if(left(tilausrivi.toimitettuaika, 10) > lasku.toimaika, DATEDIFF(tilausrivi.toimitettuaika, tilausrivi.toimaika), null)
                ), 1) myohassa_pva
                ";
    }
    else {
      $query = "SELECT
                lasku.nimi,
                lasku.tunnus tilaus,
                lasku.toimaika,
                count(tilausrivi.tunnus) riveja,
                sum(if(left(tilausrivi.toimitettuaika, 10) > lasku.toimaika, 1, 0)) myohassa_riveja,
                from_unixtime(
                  avg(
                    unix_timestamp(tilausrivi.toimitettuaika)
                  ), '%Y-%m-%d'
                ) toimitettu,
                round(avg(
                  DATEDIFF(tilausrivi.toimitettuaika, tilausrivi.toimaika)
                ), 1) myohassa_pva
                ";
    }

    $query .= " FROM tilausrivi
                JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and lasku.tila = 'L')
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tuote.ei_saldoa = '')
                WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                and tilausrivi.tyyppi = 'L'
                and tilausrivi.$pvmtapa >='$vva-$kka-$ppa 00:00:00'
                and tilausrivi.$pvmtapa <='$vvl-$kkl-$ppl 23:59:59'
                {$lisa}";

    if ($raptaso == "partneri") {
      $query .= " GROUP BY 1 ";
    }
    elseif ($raptaso == "tilaus") {
      $query .= " GROUP BY 1,2,3 ";
    }

    $query .= " ORDER BY 1 ";
  }

  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    echo "<br>";

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
    }
    else {
      pupe_DataTables(array(array($pupe_DataTables, 7, 7, false, true)));

      echo "<table class='display dataTable' id='$pupe_DataTables'>";
      echo "<thead>";
      echo "<tr>";
      echo "<th>".t("Asiakas")."</th>";
      echo "<th>".t("Tilaus")."</th>";
      echo "<th>".t("Toimitusaika")."</th>";
      echo "<th>".t("Toimitettu")."</th>";
      echo "<th>".t("Rivejä")."</th>";
      echo "<th>".t("Myöhässä / rivejä")."</th>";
      echo "<th>".t("Myöhässä / pvä")."</th>";
      echo "</tr>";
      echo "<tr>";
      echo "<td><input type='text' class='search_field' name='search_asiakas'></td>";
      echo "<td><input type='text' class='search_field' name='search_tilaus'></td>";
      echo "<td><input type='text' class='search_field' name='search_toimitusaika'></td>";
      echo "<td><input type='text' class='search_field' name='search_toimitettu'></td>";
      echo "<td><input type='text' class='search_field' name='search_riveja'></td>";
      echo "<td><input type='text' class='search_field' name='search_myohriv'></td>";
      echo "<td><input type='text' class='search_field' name='search_myohpva'></td>";
      echo "</tr>";
      echo "</thead>";
    }

    echo "<tbody>";

    while ($row = mysql_fetch_assoc($result)) {

      if (substr($row['toimitettu'],0,4) == "1970") {
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

        echo "<td align='right'><a href='$PHP_SELF?toim=$toim&raptaso=partneri&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&pvmtapa=$pvmtapa&etsinappi=yes'>{$row['tilauksia']}</a></td>";
        echo "<td align='right'>{$row['myohassa_tilauksia']}</td>";
        echo "<td align='right'>{$pros_myohassa_til} %</td>";
        echo "<td align='right'>{$row['riveja']}</td>";
        echo "<td align='right'>{$row['myohassa_riveja']}</td>";
        echo "<td align='right'>{$pros_myohassa_riv} %</td>";
        echo "<td align='right'>{$row['myohassa_pva']}</td>";
      }
      elseif ($raptaso == "partneri") {
        $pros_myohassa_til = round($row['myohassa_tilauksia'] / $row['tilauksia'] * 100, 1);
        $pros_myohassa_riv = round($row['myohassa_riveja'] / $row['riveja'] * 100, 1);

        if ($toim == "MYYNTI") {
          $plisa = "&asiakasid=$row[liitostunnus]";
        }
        else {
          $plisa = "&toimittajaid=$row[liitostunnus]";
        }

        echo "<td><a href='$PHP_SELF?toim=$toim&raptaso=tilaus&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&pvmtapa=$pvmtapa&etsinappi=yes$plisa'>{$row['nimi']}</a></td>";
        echo "<td align='right'>{$row['tilauksia']}</td>";
        echo "<td align='right'>{$row['myohassa_tilauksia']}</td>";
        echo "<td align='right'>{$pros_myohassa_til} %</td>";
        echo "<td align='right'>{$row['riveja']}</td>";
        echo "<td align='right'>{$row['myohassa_riveja']}</td>";
        echo "<td align='right'>{$pros_myohassa_riv} %</td>";
        echo "<td align='right'>{$row['myohassa_pva']}</td>";
      }
      else {
        echo "<td>{$row['nimi']}</td>";

        $out = js_openUrlNewWindow("{$palvelin2}raportit/toimitusvarmuus.php?toim=$toim&tee=NAYTATILAUS&tunnus={$row['tilaus']}", $row['tilaus'], NULL, 1000, 800);

        echo "<td>{$out}</td>";
        echo "<td>".tv1dateconv($row['toimaika'])."</td>";
        echo "<td>".tv1dateconv($row['toimitettu'])."</td>";
        echo "<td align='right'>{$row['riveja']}</td>";
        echo "<td align='right'>{$row['myohassa_riveja']}</td>";
        echo "<td align='right'>{$row['myohassa_pva']}</td>";
      }

      echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
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
