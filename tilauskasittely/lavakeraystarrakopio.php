<?php

// DataTables päälle
$pupe_DataTables = "kopsutable";

require "../inc/parametrit.inc";

echo "<font class='head'>".t("Lavakeräystarrakopio")."</font><hr>";

if (!isset($tee)) $tee = "";
if (!isset($kerayslista)) $kerayslista = "";

if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

if (!isset($kkl)) $kkl = date("m");
if (!isset($vvl)) $vvl = date("Y");
if (!isset($ppl)) $ppl = date("d");

if ($tee == "") {
  // Etsitään muutettavaa tilausta
  $query = "SELECT lasku.*
            FROM lasku
            JOIN asiakas ON (lasku.yhtio = asiakas.yhtio AND lasku.liitostunnus = asiakas.tunnus and asiakas.kerayserat = 'H')
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            and lasku.tila = 'L'
            and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
            and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'
            and lasku.tunnus = lasku.kerayslista
            ORDER BY tunnus DESC";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    pupe_DataTables(array(array($pupe_DataTables, 7, 7, true, true)));

    echo "<table class='display dataTable' id='$pupe_DataTables'>";

    echo "<thead>";
    echo "<tr>";
    echo "<th valign='top'>".t("Keräyslista")."</td>";
    echo "<th valign='top'>".t("Asiakas")."</td>";
    echo "<th valign='top'>".t("Pvm")."</td>";
    echo "<th valign='top'>".t("Toimitustapa")."</td>";
    echo "<th valign='top'>".t("Tulostettu")."</td>";
    echo "<th valign='top'>".t("Tilauksia")."</td>";
    echo "<th valign='top'>".t("Rivit")."</td>";
    echo "<th style='display:none;'></th>";
    echo "</tr>";
    echo "</thead>";

    echo "<tbody>";

    while ($row = mysql_fetch_assoc($result)) {

      $query = "SELECT group_concat(tunnus) tunnukset, count(*) tilauksia
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                and tila = 'L'
                and kerayslista = '{$row['kerayslista']}'";
      $tilres = pupe_query($query);
      $tilrow = mysql_fetch_assoc($tilres);

      $query = "SELECT count(*) riveja
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                and tyyppi = 'L'
                and otunnus in ({$tilrow['tunnukset']})";
      $tilrivires = pupe_query($query);
      $tilrivirow = mysql_fetch_assoc($tilrivires);

      echo "<tr>";

      echo "<td class='ptop'>$row[tunnus]</td>";
      echo "<td class='ptop'>$row[toim_nimi] $row[toim_nimitark]</td>";
      echo "<td class='ptop text-right'>",pupe_DataTablesEchoSort($row["luontiaika"]),tv1dateconv($row["luontiaika"], "PITKA"),"</td>";
      echo "<td class='ptop'>$row[toimitustapa]</td>";
      echo "<td class='ptop text-right'>",pupe_DataTablesEchoSort($row["lahetepvm"]),tv1dateconv($row["lahetepvm"], "PITKA"),"</td>";
      echo "<td class='ptop text-right'>$tilrow[tilauksia]</td>";
      echo "<td class='ptop text-right'>$tilrivirow[riveja]</td>";

      echo "<td class='back'>
            <form method='post' autocomplete='off'>
            <input type='hidden' name='kerayslista' value='$row[tunnus]'>
            <input type='hidden' name='tee' value='TULOSTA'>
            <input type='submit' value='".t("Tulosta")."'></form>";
      echo "</td>";
      echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
  }
  else {
    echo "<font class='error'>".t("Ei tilauksia")."...</font><br><br>";
  }
}

if ($tee == "TULOSTA" and $kerayslista > 0) {

  if (empty($tarratvalittu)) {
    echo "<form method='post' autocomplete='off'>
          <input type='hidden' name='kerayslista' value='$kerayslista'>
          <input type='hidden' name='tee' value='TULOSTA'>
          <input type='hidden' name='tarratvalittu' value='JES'>";

    echo t("Valitse tulostettavat tarrat").":<br><br>";

    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Tarravälin alku")."</th>";
    echo "<td><input type='text' name='alku' size='10'></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>".t("Tarravälin loppu")."</th>";
    echo "<td><input type='text' name='loppu' size='10'></td>";
    echo "</tr>";

    $query = "SELECT *
              FROM kirjoittimet
              WHERE yhtio  = '$kukarow[yhtio]'
              AND komento not in ('EDI','email')
              ORDER by kirjoitin";
    $kirre = pupe_query($query);

    echo "<tr>";
    echo "<th>".t("Valitse tulostin")."</th>";
    echo "<td><select name='valittu_tulostin'>";
    echo "<option value=''>".t("Valitse tulostin")."</option>";

    if (empty($hb_keraystarra_tulostin)) {
      $hb_keraystarra_tulostin = isset($_COOKIE["hb_keraystarra_tulostin"]) ? $_COOKIE["hb_keraystarra_tulostin"] : "";
    }

    while ($kirrow = mysql_fetch_array($kirre)) {
      $sel = "";
      if ($hb_keraystarra_tulostin == $kirrow['tunnus']) {
        $sel = "SELECTED";
      }
      echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
    }

    echo "</select></td></tr>";
    echo "</table><br>";
    echo t("Jätä kentät tyhjäksi jos haluat tulostaa kaikki tarrat.");
    echo "<br><br><input type='submit' value='".t("Tulosta")."'>";
    echo "</form>";
  }
  else {
    require "inc/lavakeraysparametrit.inc";
    require_once "inc/tulosta_lavakeraystarrat_tec.inc";

    $alku = (int) $alku;
    $loppu = (int) $loppu;

    if ($alku + $loppu == 0) {
      $from = "KOPIO_KAIKKI";
    }
    else {
      $from = "KOPIO";
    }

    if ($alku < 1) {
      $alku = 1;
    }

    if ($loppu < $alku) {
      $loppu = 9999999;
    }

    // haetaan kaikki tälle klöntille kuuluvat otsikot
    $query = "SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
              FROM lasku
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND tila        = 'L'
              AND kerayslista = '{$kerayslista}'
              HAVING tunnukset IS NOT NULL";
    $toimresult = pupe_query($query);

    //jos rivejä löytyy niin tiedetään, että tämä on keräysklöntti
    if (mysql_num_rows($toimresult) > 0) {
      $toimrow = mysql_fetch_assoc($toimresult);
      $tilausnumeroita = $toimrow["tunnukset"];
    }

    $lisa1 = "";
    $select_lisa = $lavakeraysparam;
    $pjat_sortlisa = "tilausrivin_lisatiedot.alunperin_puute,lavasort,";
    $where_lisa = "";

    // keräyslistalle ei oletuksena tulosteta saldottomia tuotteita
    if ($yhtiorow["kerataanko_saldottomat"] == '') {
      $lisa1 = " and tuote.ei_saldoa = '' ";
    }

    $sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
    $order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

    if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

    // Summataan rivit yhteen (HUOM: unohdetaan kaikki perheet!)
    if ($yhtiorow["kerayslistan_jarjestys"] == "S") {
      $select_lisa = "sum(tilausrivi.kpl) kpl, sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, '' perheid, '' perheid2, ";
      $where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
    }

    // ignoorataan rivien haussa pakkauksien kulutuotteet
    $query = "SELECT group_concat(pakkausveloitus_tuotenumero) pakkausveloitus_tuotenumero
              FROM pakkaus
              WHERE yhtio = '$kukarow[yhtio]'
              AND trim(pakkausveloitus_tuotenumero) != ''";
    $pakvel_result = pupe_query($query);
    $pakvel_row = mysql_fetch_assoc($pakvel_result);

    if (!empty($pakvel_row['pakkausveloitus_tuotenumero'])) {
      $lisa1 .= " and tilausrivi.tuoteno not in ('".str_replace(",", "','", $pakvel_row['pakkausveloitus_tuotenumero'])."')";
    }

    // rivit
    $query = "SELECT tilausrivi.*,
              $select_lisa
              $sorttauskentta,
              if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
              tuote.myynti_era,
              tuote.mallitarkenne
              FROM tilausrivi
              JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
              LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
              JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
              WHERE tilausrivi.otunnus  in ($tilausnumeroita)
              and tilausrivi.yhtio      = '$kukarow[yhtio]'
              and tilausrivi.tyyppi    != 'D'
              and tilausrivi.var       != 'O'
              $lisa1
              $where_lisa
              ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
    $riresult = pupe_query($query);

    $lavanumero = 1;
    $lava_referenssiluku = 0;
    $lavat = array();
    $rivinumerot = array();
    $kal = 1;

    while ($row = mysql_fetch_assoc($riresult)) {
      if (empty($lavat[$lavanumero][$row['otunnus']])) {
        $lavat[$lavanumero][$row['otunnus']] = 0;
      }

      if ($lava_referenssiluku >= lavakerayskapasiteetti) {
        $lavanumero++;
        $lava_referenssiluku=0;
      }

      // myynti_era = 1 / mallitarkenne = 400 poikkeus
      if ((int) $row['myynti_era'] == 1 and (int) $row['mallitarkenne'] == 400) {
        $row['myynti_era'] = 6;
      }

      $lavat[$lavanumero][$row['otunnus']] += round(($row['varattu']+$row['kpl'])/$row['myynti_era'], 2);
      $lava_referenssiluku += ($row['tilkpl'] * $row['lavakoko']);

      if ($kal >= $alku and $kal <= $loppu) {
        $rivinumerot[$row["tunnus"]] = $kal;
      }

      $kal++;
    }

    if (!empty($valittu_tulostin)) {
      $query  = "SELECT *
                 FROM kirjoittimet
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND tunnus = '$valittu_tulostin'";
      $kirres = pupe_query($query);
      $kirrow = mysql_fetch_assoc($kirres);

      // HB-keraystarra tulostin
      setcookie("hb_keraystarra_tulostin", $kirrow['tunnus'], time()+60*60*24*90, "/");

      if (!empty($kirrow['komento'])) {
        // Lavakeraystarrat
        tulosta_lavakeraystarrat_tec($riresult, $rivinumerot, $kirrow["komento"], $from);
      }
    }
  }
}

require "inc/footer.inc";
