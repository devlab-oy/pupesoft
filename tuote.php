<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

require "inc/parametrit.inc";

if (!empty($_COOKIE["myyntilaskun_myyja"])) {
  $myyntilaskun_myyja = "X";
}

if (!isset($tee))            $tee = "";
if (!isset($toim))           $toim = "";
if (!isset($lopetus))        $lopetus = "";
if (!isset($toim_kutsu))     $toim_kutsu = "";
if (!isset($ulos))           $ulos = "";
if (!isset($livesearch_tee)) $livesearch_tee = "";
if (!isset($tapahtumalaji))  $tapahtumalaji = "";
if (!isset($tilalehinta))    $tilalehinta = "";
if (!isset($myyntilaskun_myyja)) $myyntilaskun_myyja = "";
if (!isset($historia))       $historia = "";
if (!isset($raportti))       $raportti = "";
if (!isset($toimipaikka))    $toimipaikka = $kukarow['toimipaikka'] != 0 ? $kukarow['toimipaikka'] : 0;

$onkolaajattoimipaikat = ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikat_res) > 0) ? TRUE : FALSE;

if (!$onkolaajattoimipaikat) {
  $toimipaikka = 0;
}

require "korvaavat.class.php";
require "vastaavat.class.php";

if (isset($ajax)) {

  if (isset($tuoteno)) {
    $tuoteno = utf8_decode($tuoteno);
  }

  if ($ajax == "varastopaikat") {

    $_return = "";

    if ($_tp_kasittely) {
      $query = "SELECT GROUP_CONCAT(tunnus) tunnukset
                FROM varastopaikat
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND toimipaikka = '{$kukarow['toimipaikka']}'";

      $toimipaikka_varasto_res = pupe_query($query);
      $toimipaikka_varasto_row = mysql_fetch_assoc($toimipaikka_varasto_res);
      $toimipaikan_varastot_alkuperainen = $toimipaikka_varasto_row['tunnukset'];
      $_tp_varasto_alkp = ($toimipaikan_varastot_alkuperainen == '');

      if ($toimipaikan_varastot_alkuperainen == '' and !empty($kukarow['toimipaikka'])) {
        $query = "SELECT GROUP_CONCAT(tunnus) tunnukset
                  FROM varastopaikat
                  WHERE yhtio     = '{$kukarow['yhtio']}'
                  AND toimipaikka = 0";
        $toimipaikka_varasto_res = pupe_query($query);
        $toimipaikka_varasto_row = mysql_fetch_assoc($toimipaikka_varasto_res);
      }

      $toimipaikan_varastot = explode(",", $toimipaikka_varasto_row['tunnukset']);
    }
    else {
      $toimipaikan_varastot = array();
    }

    $_tpvar_url = urlencode(serialize($toimipaikan_varastot));

    $_return .= "<input type='hidden' id='toimipaikan_varastot' value='{$_tpvar_url}' />";

    // Saldot
    $_return .= "<table>";
    $_return .= "<tr>";
    $_return .= "<th>".t("Varasto")."</th>";
    $_return .= "<th>".t("Varastopaikka")."</th>";
    $_return .= "<th>".t("Saldo")."</th>";
    $_return .= "<th>".t("Hyllyssä")."</th>";
    $_return .= "<th>".t("Myytävissä")."</th>";
    $_return .= "</tr>";

    $kokonaissaldo = 0;
    $kokonaishyllyssa = 0;
    $kokonaismyytavissa = 0;
    $kokonaissaldo_tapahtumalle = 0;

    $yhtiot = array();
    $yhtiot[] = $kukarow["yhtio"];

    // Halutaanko saldot koko konsernista?
    if ($yhtiorow["haejaselaa_konsernisaldot"] == "S") {
      $query = "SELECT *
                FROM yhtio
                WHERE konserni  = '{$yhtiorow['konserni']}'
                AND konserni   != ''
                AND yhtio      != '{$kukarow['yhtio']}'";
      $result = pupe_query($query);

      while ($row = mysql_fetch_assoc($result)) {
        $yhtiot[] = $row["yhtio"];
      }
    }

    //saldot per varastopaikka
    if (in_array($sarjanumeroseuranta, array("E", "F", "G"))) {
      $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa, varastopaikat.toimipaikka AS varasto_toimipaikka,
                tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                sarjanumeroseuranta.sarjanumero era,
                concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
                varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
                 FROM tuote
                JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                  AND tuotepaikat.varasto                 = varastopaikat.tunnus)
                JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
                and sarjanumeroseuranta.tuoteno           = tuote.tuoteno
                and sarjanumeroseuranta.hyllyalue         = tuotepaikat.hyllyalue
                and sarjanumeroseuranta.hyllynro          = tuotepaikat.hyllynro
                and sarjanumeroseuranta.hyllyvali         = tuotepaikat.hyllyvali
                and sarjanumeroseuranta.hyllytaso         = tuotepaikat.hyllytaso
                and sarjanumeroseuranta.myyntirivitunnus  = 0
                and sarjanumeroseuranta.era_kpl          != 0
                WHERE tuote.yhtio                         in ('".implode("','", $yhtiot)."')
                and tuote.tuoteno                         = '$tuoteno'
                GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
                ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
    }
    else {

      if ($_tp_kasittely) {
        $_tp_sort_lisa = ", if(varastopaikat.toimipaikka = '{$kukarow['toimipaikka']}', 0, 1) toimipaikka_sorttaus";
      }
      else {
        $_tp_sort_lisa = ", 0 toimipaikka_sorttaus";
      }

      $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa, varastopaikat.toimipaikka AS varasto_toimipaikka,
                tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
                varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi,
                '' as era
                {$_tp_sort_lisa}
                FROM tuote
                JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                  AND tuotepaikat.varasto = varastopaikat.tunnus)
                WHERE tuote.yhtio         in ('".implode("','", $yhtiot)."')
                and tuote.tuoteno         = '$tuoteno'
                ORDER BY toimipaikka_sorttaus, tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
    }

    $sresult = pupe_query($query);

    if (mysql_num_rows($sresult) > 0) {

      $_tp_yhteensa = array();

      while ($saldorow = mysql_fetch_assoc($sresult)) {

        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], '', $saldoaikalisa, $saldorow["era"]);

        //summataan kokonaissaldoa ja vain oman firman saldoa
        $kokonaissaldo += $saldo;
        $kokonaishyllyssa += $hyllyssa;
        $kokonaismyytavissa += $myytavissa;

        $_class = '';

        if ($_tp_kasittely) {

          $_tp_chk_1 = ($kukarow['toimipaikka'] == $saldorow['varasto_toimipaikka']);

          $_onko_toimipaikkatunnukset = empty($toimipaikan_varastot_alkuperainen);
          $_kuka_tp = !empty($kukarow['toimipaikka']);
          $_var_tp = ($saldorow['varasto_toimipaikka'] == 0);
          $_tp_chk_2 = ($_onko_toimipaikkatunnukset and $_kuka_tp and $_var_tp);

          $_tp_chk_3 = ($_tp_chk_1 or $_tp_chk_2);

          if ($_tp_chk_3) {
            $_class = 'tumma';

            if (!isset($_tp_yhteensa[$saldorow['nimitys']])) {
              $_tp_yhteensa[$saldorow['nimitys']] = array(
                'saldo' => 0,
                'hyllyssa' => 0,
                'myytavissa' => 0,
              );
            }

            $_tp_yhteensa[$saldorow['nimitys']]['saldo'] += $saldo;
            $_tp_yhteensa[$saldorow['nimitys']]['hyllyssa'] += $hyllyssa;
            $_tp_yhteensa[$saldorow['nimitys']]['myytavissa'] += $myytavissa;
          }
        }

        if ($saldorow["yhtio"] == $kukarow["yhtio"] and (($toimipaikka == $saldorow['varasto_toimipaikka']) or $toimipaikka == 'kaikki' )) {
          $kokonaissaldo_tapahtumalle += $saldo;
        }

        $_return .= "<tr class='{$_class}'>";
        $_return .= "<td>$saldorow[nimitys] $saldorow[tyyppi] $saldorow[era]</td>";

        if ($saldorow["hyllyalue"] == "!!M") {
          $asiakkaan_tunnus = (int) $saldorow["hyllynro"].$saldorow["hyllyvali"].$saldorow["hyllytaso"];

          $query = "SELECT nimi, toim_nimi
                    FROM asiakas
                    WHERE yhtio = '{$kukarow["yhtio"]}'
                    AND tunnus  = '$asiakkaan_tunnus'";
          $asiakasresult = pupe_query($query);
          $asiakasrow = mysql_fetch_assoc($asiakasresult);
          $_return .= "<td>{$asiakasrow["nimi"]}</td>";
        }
        else {
          $_return .= "<td>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</td>";
        }

        $_return .= "<td align='right'>".sprintf("%.2f", $saldo)."</td>
              <td align='right'>".sprintf("%.2f", $hyllyssa)."</td>
              <td align='right' style='font-weight:bold;'>".sprintf("%.2f", $myytavissa)."</td>
              </tr>";
      }
    }

    list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, 'ORVOT', '', '', '', '', '', '', '', $saldoaikalisa);

    if ($myytavissa != 0) {
      $_return .= "<tr>";
      $_return .= "<td>".t("Tuntematon")."</td>";
      $_return .= "<td>?</td>";
      $_return .= "<td align='right'>".sprintf("%.2f", $saldo)."</td>";
      $_return .= "<td align='right'>".sprintf("%.2f", $hyllyssa)."</td>";
      $_return .= "<td align='right'>".sprintf("%.2f", $myytavissa)."</td>";
      $_return .= "</tr>";

      //summataan kokonaissaldoa ja vain oman firman saldoa.
      $kokonaissaldo += $saldo;
      $kokonaishyllyssa += $hyllyssa;
      $kokonaismyytavissa += $myytavissa;
    }

    if (!empty($_tp_yhteensa)) {

      foreach ($_tp_yhteensa as $_tp_varasto_nimi => $_tp_saldot) {
        $_return .= "<tr>
            <th colspan='2'>".t("Yhteensä")." {$_tp_varasto_nimi}</th>
            <th style='text-align:right;'>".sprintf("%.2f", $_tp_saldot['saldo'])."</th>
            <th style='text-align:right;'>".sprintf("%.2f", $_tp_saldot['hyllyssa'])."</th>
            <th style='text-align:right;'>".sprintf("%.2f", $_tp_saldot['myytavissa'])."</th>
            </tr>";
      }
    }

    $_return .= "<tr>
        <th colspan='2'>".t("Yhteensä")."</th>
        <th style='text-align:right;'>".sprintf("%.2f", $kokonaissaldo)."</th>
        <th style='text-align:right;'>".sprintf("%.2f", $kokonaishyllyssa)."</th>
        <th style='text-align:right;'>".sprintf("%.2f", $kokonaismyytavissa)."</th>
        </tr>";

    $_return .= "</table>";
    $_return .= "<input type='hidden' id='kokonaissaldo_tapahtumalle_ajax' value='{$kokonaissaldo_tapahtumalle}' />";
  }

  if ($ajax == "vastaavat") {

    if (isset($toimipaikan_varastot)) {
      $toimipaikan_varastot = unserialize(urldecode($toimipaikan_varastot));
    }
    else {
      $toimipaikan_varastot = array();
    }

    $_return = "";

    $vastaavat = new Vastaavat($tuoteno);

    // Ketjujen id:t
    foreach (explode(",", $vastaavat->getIDt()) as $ketju) {
      $_colspan = 3;

      if ($_tp_kasittely) {
        $_colspan++;
      }

      $_return .= "<table>";
      $_return .= "<tr><th colspan='{$_colspan}'>".t("Ketju").": $ketju.</th></tr>";
      $_return .= "<tr>";
      $_return .= "<th>".t("Tuotenumero")."</th>";
      $_return .= "<th>".t("Myytävissä")."</th>";
      $_return .= "<th>".t("Vaihtoehtoinen")."</th>";

      if ($_tp_kasittely) {
        $_return .= "<th>".t("Oma myytävissä")."</th>";
      }

      $_return .= "</tr>";

      // Haetaan tuotteet ketjukohtaisesti
      $_tuotteet = $vastaavat->tuotteet($ketju, $options);

      $kokonaismyytavissa = 0;
      $oma_myytavissa_yhteensa = 0;

      // Lisätään löydetyt vastaavat mahdollisten myytävien joukkoon
      foreach ($_tuotteet as $_tuote) {

        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($_tuote["tuoteno"], 'KAIKKI', '', '', '', '', '', '', '', $saldoaikalisa);
        $kokonaismyytavissa += $myytavissa;

        if ($_tp_kasittely and !empty($toimipaikan_varastot)) {
          list($_saldo, $_hyllyssa, $_myytavissa) = saldo_myytavissa($_tuote["tuoteno"], 'KAIKKI', $toimipaikan_varastot, '', '', '', '', '', '', $saldoaikalisa);
          $oma_myytavissa_yhteensa += $_myytavissa;
          $oma_myytavissa = $_myytavissa;
        }
        else {
          $oma_myytavissa = 0;
        }

        $_return .= "<tr>";
        $_return .= "<td><a href='$PHP_SELF?toim=$toim&tee=Z&tuoteno=".urlencode($_tuote["tuoteno"])."&lopetus=$lopetus'>$_tuote[tuoteno]</a></td>";
        $_return .= "<td align='right'>".sprintf("%.2f", $myytavissa)."</td>";
        $_return .= "<td>";

        // Vaihtoehtoinen
        if ($_tuote['vaihtoehtoinen'] == 'K') {
          $_return .= t("Kyllä");
        }

        $_return .= "</td>";

        if ($_tp_kasittely) {
          $_return .= "<td align='right'>".sprintf("%.2f", $oma_myytavissa)."</td>";
        }

        $_return .= "</tr>";
      }

      $_return .= "<tr>";
      $_return .= "<th>".t("Yhteensä")."</th>";
      $_return .= "<th style='text-align:right;'>".sprintf("%.2f", $kokonaismyytavissa)."</th>";
      $_return .= "<th></th>";

      if ($_tp_kasittely) {
        $_return .= "<th style='text-align:right;'>".sprintf("%.2f", $oma_myytavissa_yhteensa)."</th>";
      }

      $_return .= "</tr>";

      $_return .= "</table>";
    }
  }

  if ($ajax == "korvaavat") {

    if (isset($toimipaikan_varastot)) {
      $toimipaikan_varastot = unserialize(urldecode($toimipaikan_varastot));
    }
    else {
      $toimipaikan_varastot = array();
    }

    $_return = "<table>";
    $_return .= "<tr>";
    $_return .= "<th>".t("Tuotenumero")."</th>";
    $_return .= "<th>".t("Myytävissä")."</th>";

    if ($_tp_kasittely) {
      $_return .= "<th>".t("Oma myytävissä")."</th>";
    }

    $_return .= "</tr>";

    $kokonaismyytavissa = 0;
    $oma_myytavissa_yhteensa = 0;

    $korvaavat = new Korvaavat($tuoteno);

    // Listataan korvaavat ketju
    foreach (array_reverse($korvaavat->tuotteet()) as $tuote) {
      if ($tuoteno != $tuote["tuoteno"]) {

        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuote["tuoteno"], 'KAIKKI', '', '', '', '', '', '', '', $saldoaikalisa);
        $kokonaismyytavissa += $myytavissa;

        if ($_tp_kasittely and !empty($toimipaikan_varastot)) {
          list($_saldo, $_hyllyssa, $_myytavissa) = saldo_myytavissa($tuote["tuoteno"], 'KAIKKI', $toimipaikan_varastot, '', '', '', '', '', '', $saldoaikalisa);
          $oma_myytavissa_yhteensa += $_myytavissa;
          $oma_myytavissa = $_myytavissa;
        }
        else {
          $oma_myytavissa = 0;
        }

        $_return .= "<tr>";
        $_return .= "<td><a href='$PHP_SELF?toim=$toim&tee=Z&tuoteno=".urlencode($tuote["tuoteno"])."&lopetus=$lopetus'>$tuote[tuoteno]</a></td>";
        $_return .= "<td align='right'>".sprintf("%.2f", $myytavissa)."</td>";
        if ($_tp_kasittely) {
          $_return .= "<td align='right'>".sprintf("%.2f", $oma_myytavissa)."</td>";
        }
        $_return .= "</tr>";
      }
    }

    $_return .= "<tr>";
    $_return .= "<th>".t("Yhteensä")."</th>";
    $_return .= "<th style='text-align:right;'>".sprintf("%.2f", $kokonaismyytavissa)."</th>";

    if ($_tp_kasittely) {
      $_return .= "<th style='text-align:right;'>".sprintf("%.2f", $oma_myytavissa_yhteensa)."</th>";
    }

    $_return .= "</tr>";

    $_return .= "</table>";
  }

  if ($ajax == "tapahtumat") {

    $_return = "";

    if ($historia == '4') {
      $maara = "";
      $ehto  = "";
    }
    elseif (strpos($historia, 'TK') !== FALSE) {
      $query = "SELECT tilikausi_alku, tilikausi_loppu FROM tilikaudet WHERE yhtio = '$kukarow[yhtio]' and tunnus = '".substr($historia, 2)."'";
      $tkresult = pupe_query($query);
      $tkrow = mysql_fetch_assoc($tkresult);

      $maara = "";
      $ehto  = " and tapahtuma.laadittu >= '$tkrow[tilikausi_alku] 00:00:00' and tapahtuma.laadittu <= '$tkrow[tilikausi_loppu] 23:59:59' ";
    }
    else {
      $maara = "LIMIT 20";
      $ehto  = "";
    }

    $ale_query_concat_lisa = 'concat(';

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      $ale_query_concat_lisa .= "' ', tilausrivi.ale{$alepostfix}, ' %',";
    }

    $ale_query_concat_lisa = substr($ale_query_concat_lisa, 0, -1);
    $ale_query_concat_lisa .= "),";

    $toimipaikkarajaus = "";

    if ($onkolaajattoimipaikat and "{$toimipaikka}" != 'kaikki') {

      if ($toimipaikka != 0) {
        $_toimipaikat = array($toimipaikka, 0);
      }
      else {
        $_toimipaikat = array(0);
      }

      foreach ($_toimipaikat as $_toimipaikka) {

        $query  = "SELECT GROUP_CONCAT(tunnus) AS tunnukset
                   FROM varastopaikat
                   WHERE yhtio     = '{$kukarow['yhtio']}'
                   AND toimipaikka = '{$_toimipaikka}'";
        $vares = pupe_query($query);
        $varow = mysql_fetch_assoc($vares);

        // Jos meillä on toimipaikka setattuna ja ei löydetty tämän toimipaikan varastoja
        // Fallback: etsitään varastoja joita ei ole liitetty toimipaikkaan
        if (count($_toimipaikat) > 1 and $_toimipaikka != 0 and empty($varow['tunnukset'])) {
          continue;
        }

        if (!empty($varow['tunnukset'])) {
          $toimipaikkarajaus = "AND tapahtuma.varasto IN ({$varow['tunnukset']})";
          break;
        }
        else {
          // Jos toimipaikkarajaus palauttaa NULLia, ei näytetä tapahtumia
          $ehto = "AND tapahtuma.tunnus = 0";
        }
      }
    }

    $query = "SELECT tapahtuma.tuoteno,
              ifnull(kuka.nimi, tapahtuma.laatija) laatija,
              kuka_myyja.nimi AS tilauksen_myyja,
              tapahtuma.laadittu,
              tapahtuma.laji,
              tapahtuma.kpl,
              tapahtuma.kplhinta,
              tapahtuma.hinta,
              if (tapahtuma.laji in ('tulo','valmistus'), tapahtuma.kplhinta*tapahtuma.kpl, null) arvo,
              tapahtuma.selite,
              lasku.tunnus laskutunnus,
              concat_ws(' ', tapahtuma.hyllyalue, tapahtuma.hyllynro, tapahtuma.hyllyvali, tapahtuma.hyllytaso) tapapaikka,
              tapahtuma.hyllyalue tapahtuma_hyllyalue,
              concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
              tilausrivi.hyllyalue tilausrivi_hyllyalue,
              tilausrivi.kate,
              tilausrivi.rivihinta,
              tilausrivi.tunnus trivitunn,
              tilausrivi.perheid,
              tilausrivin_lisatiedot.osto_vai_hyvitys,
              tilausrivin_lisatiedot.korvamerkinta,
              lasku2.tunnus lasku2tunnus,
              lasku2.laskunro lasku2laskunro,
              concat_ws(' / ', round(tilausrivi.hinta, $yhtiorow[hintapyoristys]), $ale_query_concat_lisa round(tilausrivi.rivihinta, $yhtiorow[hintapyoristys])) tilalehinta,
              tapahtuma.tunnus tapatunnus
              FROM tapahtuma use index (yhtio_tuote_laadittu)
              LEFT JOIN tilausrivi use index (primary) ON (tilausrivi.yhtio = tapahtuma.yhtio and tilausrivi.tunnus = ABS(tapahtuma.rivitunnus))
              LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
              LEFT JOIN lasku use index (primary) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
              LEFT JOIN lasku AS lasku2 use index (primary) ON (lasku2.yhtio = tilausrivi.yhtio AND lasku2.tunnus = tilausrivi.uusiotunnus)
              LEFT JOIN kuka ON (kuka.yhtio = tapahtuma.yhtio AND kuka.kuka = tapahtuma.laatija)
              LEFT JOIN kuka AS kuka_myyja ON (kuka_myyja.yhtio = lasku.yhtio AND kuka_myyja.tunnus = lasku.myyja)
              WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
              and tapahtuma.tuoteno = '$tuoteno'
              {$toimipaikkarajaus}
              $ehto
              ORDER BY tapahtuma.laadittu desc, tapahtuma.tunnus desc
              $maara";
    $qresult = pupe_query($query);

    $yhteensa_maara = 0.0;
    $yhteensa_arvo  = 0.0;

    if (!empty($tapahtumalaji)) {
      while ($prow = mysql_fetch_assoc($qresult)) {
        if ($prow["laji"] != $tapahtumalaji) continue;

        $yhteensa_maara += $prow["kpl"];
        $yhteensa_arvo  += $prow["arvo"];
      }

      $_return .= "<tr class='aktiivi'>";
      $_return .= "<th>" . t("Yhteensä") . ":</th>";
      $_return .= "<td></td>";
      $_return .= "<td></td>";
      $_return .= "<td nowrap align='right' valign='top'>" . sprintf('%.2f', $yhteensa_maara) . "</td>";
      $_return .= "<td></td>";
      $_return .= "<td></td>";
      $_return .= "<td></td>";
      $_return .= "<td nowrap align='right' valign='top'>" . sprintf('%.2f', $yhteensa_arvo) . "</td>";
      $_return .= "<td></td>";
      $_return .= "<td></td>";
      $_return .= "<td></td>";
      $_return .= "</tr>";

      mysql_data_seek($qresult, 0);
    }

    // jos jsarjanumeroseuranta S ja inout varastonarvo
    if ($sarjanumeroseuranta == "S") {
      $kokonaissaldo_tapahtumalle = $sarjanumero_kpl;
    }

    $vararvo_nyt = sprintf('%.2f', $kokonaissaldo_tapahtumalle*$kehahin);
    $saldo_nyt = $kokonaissaldo_tapahtumalle;

    if ($ei_saldoa == "") {
      $colspan = 5;

      if ($myyntilaskun_myyja != '') {
        $colspan++;
      }

      $_return .= "<tr class='aktiivi'>";
      $_return .= "<td colspan='{$colspan}' id='varastonarvo_nyt_header'>".t("Varastonarvo nyt").":</td>";
      $_return .= "<td align='right' id='ajax_kehahin'>{$kehahin}</td>";
      $_return .= "<td align='right'></td>";
      $_return .= "<td align='right'>$vararvo_nyt</td>";
      $_return .= "<td align='right' id='ajax_kokonaissaldo'>".sprintf('%.2f', $kokonaissaldo_tapahtumalle*$kehahin)."</td>";
      $_return .= "<td align='right'>".sprintf('%.2f', $saldo_nyt)."</td>";
      $_return .= "<td></td>";

      if ($tilalehinta != '') {
        $_return .= "<td></td>";
      }

      $_return .= "</tr>";
    }

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

    while ($prow = mysql_fetch_assoc($qresult)) {

      $ankkuri = "ta_".$prow["tapatunnus"];

      $kehahinta = hinta_kuluineen($tuoteno, $prow['hinta']);

      if ($prow['arvo'] === null) {
        $prow['arvo'] = $kehahinta * $prow['kpl'];
      }

      $vararvo_nyt -= $prow["arvo"];

      // Epäkuranteissa saldo ei muutu
      if ($prow["laji"] != "Epäkurantti") {
        $saldo_nyt -= $prow["kpl"];
      }

      if ($tapahtumalaji == "" or strtoupper($tapahtumalaji) == strtoupper($prow["laji"])) {
        $_return .= "<tr class='aktiivi'>";
        $_return .= "<td nowrap valign='top'>{$prow['laatija']}</td>";

        if ($myyntilaskun_myyja != '') {
          $_return .= "<td nowrap valign='top'>{$prow['tilauksen_myyja']}</td>";
        }

        $_return .= "<td nowrap valign='top'>" . tv1dateconv($prow["laadittu"], "pitka") . "</td>";
        $_return .= "<td nowrap valign='top'>";

        if ($prow["laji"] == "laskutus" and $prow["laskutunnus"] != "") {
          $_return .= "<a href=\"javascript:lataaiframe('{$prow['laskutunnus']}', '{$palvelin2}raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus={$prow['laskutunnus']}&ohje=off');\">".t("$prow[laji]")."</a>";
        }
        elseif ($prow["laji"] == "tulo" and $prow["laskutunnus"] != "") {
          $_return .= "<a href=\"javascript:lataaiframe('{$prow['laskutunnus']}', '{$palvelin2}raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus={$prow['laskutunnus']}&ohje=off');\">".t("$prow[laji]")."</a>";
        }
        elseif ($prow["laji"] == "siirto" and $prow["laskutunnus"] != "") {
          $_return .= "<a href=\"javascript:lataaiframe('{$prow['laskutunnus']}', '{$palvelin2}tuote.php?tuoteno=".urlencode($tuoteno)."&tee=NAYTATILAUS&tunnus=$prow[laskutunnus]&ohje=off');\">".t("$prow[laji]")."</a>";
        }
        elseif ($prow["laji"] == "valmistus" and $prow["laskutunnus"] != "") {
          $_return .= "<a href=\"javascript:lataaiframe('{$prow['laskutunnus']}', '{$palvelin2}tuote.php?tuoteno=".urlencode($tuoteno)."&tee=NAYTATILAUS&tunnus=$prow[laskutunnus]&ohje=off');\">".t("$prow[laji]")."</a>";

          // Näytetään tämä vain jos käyttäjällä on oikeus tehdä valmistuksia tai reseptejä
          if ($oikeu_t1 or $oikeu_t2 or $oikeu_t3 or $oikeu_t4) {
            $id = md5(uniqid());
            $_return .= "&nbsp;<img src='{$palvelin2}pics/lullacons/info.png' class='tooltip' id='$id'>";

            // Näytetään mistä tuotteista tämä on valmistettu
            $_return .= "<div id='div_$id' class='popup' style='width: 400px;'>";
            $_return .= "<table>";

            $query = "SELECT tilausrivi.nimitys,
                      tilausrivi.tuoteno,
                      tapahtuma.kpl * -1 'kpl',
                      tapahtuma.hinta,
                      tapahtuma.kpl * tapahtuma.hinta * -1 yhteensa
                      FROM tilausrivi
                      JOIN tapahtuma ON tapahtuma.yhtio=tilausrivi.yhtio and tapahtuma.laji='kulutus' and tapahtuma.rivitunnus=tilausrivi.tunnus
                      WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                      and tilausrivi.otunnus = $prow[laskutunnus]
                      and tilausrivi.perheid = $prow[perheid]
                      and tilausrivi.tyyppi  = 'V'
                      ORDER BY tilausrivi.tunnus";
            $rresult = pupe_query($query);

            $_return .= "<tr>
                <th>".t("Nimitys")."</th>
                <th>".t("Tuoteno")."</th>
                <th>".t("Kpl")."</th>
                <th>".t("Arvo")."</th>
                <th>".t("Yhteensä")."</th>
                </tr>";

            $ressuyhteensa = 0;

            while ($rrow = mysql_fetch_assoc($rresult)) {
              $_return .= "<tr>
                  <td>$rrow[nimitys]</td>
                  <td>$rrow[tuoteno]</td>
                  <td align='right'>$rrow[kpl]</td>
                  <td align='right'>$rrow[hinta]</td>
                  <td align='right'>".sprintf("%.2f", $rrow["yhteensa"])."</td>
                  </tr>";
              $ressuyhteensa += $rrow["yhteensa"];
            }

            $_return .= "<tr>
                <td class='tumma' colspan='4'></td>
                <td class='tumma' align='right'>".sprintf("%.2f", $ressuyhteensa)."</td>
                </tr>";

            $query = "SELECT valmistuksen_lisatiedot
                      FROM lasku
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus = $prow[laskutunnus]";
            $rresult = pupe_query($query);
            $rrow = mysql_fetch_assoc($rresult);

            if (!empty($rrow["valmistuksen_lisatiedot"])) {
              $_return .= "<tr><td class='tumma' colspan='5'>".t("Kommentit").":</td></tr>";
              $_return .= "<tr><td colspan='5'>{$rrow["valmistuksen_lisatiedot"]}</td></tr>";
            }

            $_return .= "</table>";
            $_return .= "</div>";
          }
        }
        else {
          $_return .= t("$prow[laji]");
        }

        if (!empty($prow['korvamerkinta'])) {

          if ($prow['korvamerkinta'] == '.') {
            $luokka = '';
          }
          else {
            $luokka = 'tooltip';
          }
          $id = md5(uniqid());
          $_return .= "&nbsp;<img src='{$palvelin2}pics/lullacons/info.png' class='{$luokka}' id='{$id}_info'>";
          $_return .= "<div id='div_{$id}_info' class='popup'>";
          $_return .= $prow['korvamerkinta'];
          $_return .= "</div>";
        }

        $_return .= "</td>";

        $_return .= "<td nowrap align='right' valign='top'>" . $prow['kpl'] . "</td>";

        $_return .= "<td nowrap align='right' valign='top'>";

        if ($prow['laji'] == 'tulo') {
          $ohinta_kuluineen = hinta_kuluineen($tuoteno, $prow["kplhinta"]);

          $_return .= hintapyoristys($ohinta_kuluineen);

          // Jos katsotaan tulotapahtumia ja halutaan nähdä kulut hinnoissa
          // Ei näytetä selite-kenttää
          // Koska selite-kenttä on informatiivinen kenttä
          if ($ohinta_kuluineen != $prow["kplhinta"]) {
            $prow['selite'] = "";
          }
        }
        else {
          $_return .= hintapyoristys($prow["kplhinta"]);
        }

        $_return .= "</td>";
        $_return .= "<td nowrap align='right' valign='top'>" . hintapyoristys($kehahinta, 6, FALSE) . "</td>";

        if ($prow["laji"] == "laskutus") {
          $kate = $prow["kplhinta"] - $kehahinta;
          $katepros = 100 * ($kate/$prow['kplhinta']);
          $_return .= "<td nowrap align='right' valign='top'>".round($katepros, 2)."%</td>";
        }
        else {
          $_return .= "<td nowrap align='right' valign='top'></td>";
        }

        if ($ei_saldoa == "") {
          $_return .= "<td nowrap align='right' valign='top'>".sprintf('%.2f', $prow["arvo"])."</td>";
          $_return .= "<td nowrap align='right' valign='top'>".sprintf('%.2f', $vararvo_nyt)."</td>";
          $_return .= "<td nowrap align='right' valign='top'>".sprintf('%.2f', $saldo_nyt)."</td>";
        }
        else {
          $_return .= "<td></td>";
          $_return .= "<td></td>";
          $_return .= "<td></td>";
        }

        if ($tilalehinta != '') {
          $_return .= "<td nowrap align='right' valign='top'>$prow[tilalehinta]</td>";
        }

        $_return .= "<td valign='top'>$prow[selite]";

        if ($prow["laji"] == "tulo" and $prow["lasku2tunnus"] != "") {

          if (trim($prow['selite']) != '') {
            $_return .= "<br />";
          }

          $_return .= "<a href=\"javascript:lataaiframe('{$prow['lasku2tunnus']}', '{$palvelin2}raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus={$prow['lasku2tunnus']}&ohje=off');\">".t("Näytä saapuminen")." {$prow['lasku2laskunro']}</a>";
        }

        if (trim($prow["tapapaikka"]) != "" and $prow["tapahtuma_hyllyalue"] != "!!M") $_return .= "<br>".t("Varastopaikka").": $prow[tapapaikka]";
        elseif (trim($prow["paikka"]) != "" and $prow["tilausrivi_hyllyalue"] != "!!M") $_return .= "<br>".t("Varastopaikka").": $prow[paikka]";

        if ($sarjanumeroseuranta != "" and ($prow["laji"] == "tulo" or $prow["laji"] == "laskutus")) {

          if ($prow["laji"] == "tulo") {
            //Haetan sarjanumeron tiedot
            if ($prow["kpl"] < 0) {
              $sarjanutunnus = "myyntirivitunnus";
            }
            else {
              $sarjanutunnus = "ostorivitunnus";
            }
          }
          if ($prow["laji"] == "laskutus") {
            //Haetan sarjanumeron tiedot
            if ($prow["osto_vai_hyvitys"] == '' and $prow["kpl"] < 0) {
              $sarjanutunnus = "myyntirivitunnus";
            }
            elseif ($prow["kpl"] < 0) {
              $sarjanutunnus = "ostorivitunnus";
            }
            else {
              $sarjanutunnus = "myyntirivitunnus";
            }
          }

          $query = "SELECT distinct sarjanumero
                    FROM sarjanumeroseuranta
                    where yhtio      = '$kukarow[yhtio]'
                    and tuoteno      = '$prow[tuoteno]'
                    and $sarjanutunnus='$prow[trivitunn]'
                    and sarjanumero != ''
                    group by sarjanumero
                    order by sarjanumero";
          $sarjares = pupe_query($query);

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            if ($sarjanumeroseuranta == "E" or $sarjanumeroseuranta == "F" or $sarjanumeroseuranta == "G") {
              $_return .= "<br>".t("E:nro").": $sarjarow[sarjanumero]";
            }
            else {
              $_return .= "<br>".t("S:nro").": $sarjarow[sarjanumero]";
            }
          }
        }

        $_return .= "</td>";
        $_return .= "</tr>";

        $_colspanni = 11;

        if ($tilalehinta != '') $_colspanni++;
        if ($myyntilaskun_myyja != '') $_colspanni++;

        $_return .= "<tr><td colspan='{$_colspanni}' class='back' style='width:100%; padding:0; margin:0;'><div id = 'ifd_{$prow['laskutunnus']}' style='width:100%; border:1px solid; display:none'></div></td></tr>";
        $_return .= "<tr><td colspan='{$_colspanni}' class='back' style='width:100%; padding:0; margin:0;'><div id = 'ifd_{$prow['lasku2tunnus']}' style='width:100%; border:1px solid; display:none'></div></td></tr>";

      }
    }
  }

  if ($ajax == "raportointi") {

    $_return = "<table>";

    if ($raportti == "MYYNTI") {

      //myynnit
      $edvuosi  = date('Y')-1;
      $taavuosi = date('Y');

      if ($onkolaajattoimipaikat and "{$toimipaikka}" != 'kaikki') {
        $toimipaikkarajaus = " JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND lasku.yhtio_toimipaikka = '{$toimipaikka}')";
      }
      else {
        $toimipaikkarajaus = "";
      }

      $query = "SELECT tilausrivi.tuoteno,
                ROUND(SUM(IF(tilausrivi.laskutettuaika >= DATE_SUB(now(), INTERVAL 30 DAY), tilausrivi.rivihinta,0)), {$yhtiorow['hintapyoristys']}) summa30,
                ROUND(SUM(IF(tilausrivi.laskutettuaika >= DATE_SUB(now(), INTERVAL 30 DAY), tilausrivi.kate,0)), {$yhtiorow['hintapyoristys']}) kate30,
                SUM(IF(tilausrivi.laskutettuaika >= DATE_SUB(now(), INTERVAL 30 DAY), tilausrivi.kpl, 0)) kpl30,
                ROUND(SUM(IF(tilausrivi.laskutettuaika >= DATE_SUB(now(), INTERVAL 90 DAY), tilausrivi.rivihinta, 0)), {$yhtiorow['hintapyoristys']}) summa90,
                ROUND(SUM(IF(tilausrivi.laskutettuaika >= DATE_SUB(now(), INTERVAL 90 DAY), tilausrivi.kate, 0)), {$yhtiorow['hintapyoristys']}) kate90,
                SUM(IF(tilausrivi.laskutettuaika >= DATE_SUB(now(), INTERVAL 90 DAY), tilausrivi.kpl, 0)) kpl90,
                ROUND(SUM(IF(YEAR(tilausrivi.laskutettuaika) = '{$taavuosi}', tilausrivi.rivihinta, 0)), {$yhtiorow['hintapyoristys']})  summaVA,
                ROUND(SUM(IF(YEAR(tilausrivi.laskutettuaika) = '{$taavuosi}', tilausrivi.kate, 0)), {$yhtiorow['hintapyoristys']}) kateVA,
                SUM(IF(YEAR(tilausrivi.laskutettuaika) = '{$taavuosi}', tilausrivi.kpl, 0))  kplVA,
                ROUND(SUM(IF(YEAR(tilausrivi.laskutettuaika) = '{$edvuosi}', tilausrivi.rivihinta, 0)), {$yhtiorow['hintapyoristys']}) summaEDV,
                ROUND(SUM(IF(YEAR(tilausrivi.laskutettuaika) = '{$edvuosi}', tilausrivi.kate, 0)), {$yhtiorow['hintapyoristys']}) kateEDV,
                SUM(IF(YEAR(tilausrivi.laskutettuaika) = '{$edvuosi}', tilausrivi.kpl, 0)) kplEDV
                FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika)
                {$toimipaikkarajaus}
                WHERE tilausrivi.yhtio        = '{$kukarow['yhtio']}'
                AND tilausrivi.tyyppi         = 'L'
                AND tilausrivi.tuoteno        = '{$tuoteno}'
                AND tilausrivi.laskutettuaika >= '{$edvuosi}-01-01'
                GROUP BY tuoteno";
      $result3 = pupe_query($query);
      $lrow = mysql_fetch_assoc($result3);

      $_return .= "<tr>
          <th>".t("Myynti").":</th>
          <th>".t("Edelliset 30pv")."</th>
          <th>".t("Edelliset 90pv")."</th>
          <th>".t("Vuosi")." $taavuosi</th>
          <th>".t("Vuosi")." $edvuosi</th>
          </tr>";

      $_return .= "<tr><th align='left'>".t("Liikevaihto").":</th>
          <td align='right' nowrap>$lrow[summa30] $yhtiorow[valkoodi]</td>
          <td align='right' nowrap>$lrow[summa90] $yhtiorow[valkoodi]</td>
          <td align='right' nowrap>$lrow[summaVA] $yhtiorow[valkoodi]</td>
          <td align='right' nowrap>$lrow[summaEDV] $yhtiorow[valkoodi]</td></tr>";

      $_return .= "<tr><th align='left'>".t("Myykpl").":</th>
          <td align='right' nowrap>$lrow[kpl30]  ".t_avainsana("Y", "", " and avainsana.selite='{$yksikko}'", "", "", "selite")."</td>
          <td align='right' nowrap>$lrow[kpl90]  ".t_avainsana("Y", "", " and avainsana.selite='{$yksikko}'", "", "", "selite")."</td>
          <td align='right' nowrap>$lrow[kplVA]  ".t_avainsana("Y", "", " and avainsana.selite='{$yksikko}'", "", "", "selite")."</td>
          <td align='right' nowrap>$lrow[kplEDV] ".t_avainsana("Y", "", " and avainsana.selite='{$yksikko}'", "", "", "selite")."</td></tr>";


      if ($lrow['summa30'] <= 0) {
        $kate30 = '0.00';
      }
      else {
        $kate30 = round(kate_kuluineen($lrow['tuoteno'], $lrow['summa30'], $lrow['kate30']), 2);
      }

      if ($lrow['summa90'] <= 0) {
        $kate90 = '0.00';
      }
      else {
        $kate90 = round(kate_kuluineen($lrow['tuoteno'], $lrow['summa90'], $lrow['kate90']), 2);
      }

      if ($lrow['summaVA'] <= 0) {
        $kateVA = '0.00';
      }
      else {
        $kateVA = round(kate_kuluineen($lrow['tuoteno'], $lrow['summaVA'], $lrow['kateVA']), 2);
      }

      if ($lrow['summaEDV'] <= 0) {
        $kateEDV = '0.00';
      }
      else {
        $kateEDV = round(kate_kuluineen($lrow['tuoteno'], $lrow['summaEDV'], $lrow['kateEDV']), 2) ;
      }


      $_return .= "<tr><th align='left'>".t("Kate").":</th>
          <td align='right' nowrap>$kate30 $yhtiorow[valkoodi]</td>
          <td align='right' nowrap>$kate90 $yhtiorow[valkoodi]</td>
          <td align='right' nowrap>$kateVA $yhtiorow[valkoodi]</td>
          <td align='right' nowrap>$kateEDV $yhtiorow[valkoodi]</td></tr>";

      $_return .= "<tr><th align='left'>".t("Katepros").":</th>";

      if ($lrow["summa30"] > 0) {
        $kate30pros = round($kate30/$lrow["summa30"]*100, 2);
      }
      else {
        $kate30pros = '0.00';
      }

      if ($lrow["summa90"] > 0) {
        $kate90pros = round($kate90/$lrow["summa90"]*100, 2);
      }
      else {
        $kate90pros = '0.00';
      }

      if ($lrow["summaVA"] > 0) {
        $kateVApros = round($kateVA/$lrow["summaVA"]*100, 2);
      }
      else {
        $kateVApros = '0.00';
      }

      if ($lrow["summaEDV"] > 0) {
        $kateEDVpros = round($kateEDV/$lrow["summaEDV"]*100, 2);
      }
      else {
        $kateEDVpros = '0.00';
      }

      $_return .= "<td align='right' nowrap>$kate30pros %</td>";
      $_return .= "<td align='right' nowrap>$kate90pros %</td>";
      $_return .= "<td align='right' nowrap>$kateVApros %</td>";
      $_return .= "<td align='right' nowrap>$kateEDVpros %</td></tr>";

      $_return .= "</table><br>";
    }
    elseif ($raportti == "KULUTUS") {

      $kk=date("m");
      $vv=date("Y");
      $select_summa = $otsikkorivi = "";
      for ($y=1;$y<=12;$y++) {

        $kk--;

        if ($kk == 0) {
          $kk = 12;
          $vv--;
        }

        switch ($kk) {
        case "1":
          $month = "Tammi";
          break;
        case "2":
          $month = "Helmi";
          break;
        case "3":
          $month = "Maalis";
          break;
        case "4":
          $month = "Huhti";
          break;
        case "5":
          $month = "Touko";
          break;
        case "6":
          $month = "Kesä";
          break;
        case "7":
          $month = "Heinä";
          break;
        case "8":
          $month = "Elo";
          break;
        case "9":
          $month = "Syys";
          break;
        case "10":
          $month = "Loka";
          break;
        case "11":
          $month = "Marras";
          break;
        case "12":
          $month = "Joulu";
          break;
        }

        $otsikkorivi .= "<th>".t($month)."</th>";

        $ppk = date("t");
        $alku = "{$vv}-".sprintf("%02s", $kk)."-01 00:00:00";
        $ed = ($vv-1)."-".sprintf("%02s", $kk)."-01 00:00:00";

        if ($select_summa == "") {
          $select_summa .= "    SUM(IF(tapahtuma.laadittu >= '{$alku}' AND tapahtuma.laadittu <= DATE_ADD('{$alku}', INTERVAL 1 MONTH) AND tilausrivi.tyyppi = 'L', tapahtuma.kpl, 0)) * -1 kpl_myynti_{$kk}
                    , SUM(IF(tapahtuma.laadittu >= '{$alku}' AND tapahtuma.laadittu <= DATE_ADD('{$alku}', INTERVAL 1 MONTH) AND tilausrivi.tyyppi = 'V', tapahtuma.kpl, 0)) * -1 kpl_kulutus_{$kk}
                    , SUM(IF(tapahtuma.laadittu >= '{$ed}' AND tapahtuma.laadittu <= DATE_ADD('{$ed}', INTERVAL 1 MONTH) AND tilausrivi.tyyppi = 'L', tapahtuma.kpl, 0)) * -1 ed_kpl_myynti_{$kk}
                    , SUM(IF(tapahtuma.laadittu >= '{$ed}' AND tapahtuma.laadittu <= DATE_ADD('{$ed}', INTERVAL 1 MONTH) AND tilausrivi.tyyppi = 'V', tapahtuma.kpl, 0)) * -1 ed_kpl_kulutus_{$kk}

                    ";
        }
        else {
          $select_summa .= "  , SUM(IF(tapahtuma.laadittu >= '{$alku}' AND tapahtuma.laadittu <= DATE_ADD('{$alku}', INTERVAL 1 MONTH) AND tilausrivi.tyyppi = 'L', tapahtuma.kpl, 0)) * -1 kpl_myynti_{$kk}
                    , SUM(IF(tapahtuma.laadittu >= '{$alku}' AND tapahtuma.laadittu <= DATE_ADD('{$alku}', INTERVAL 1 MONTH) AND tilausrivi.tyyppi = 'V', tapahtuma.kpl, 0)) * -1 kpl_kulutus_{$kk}
                    , SUM(IF(tapahtuma.laadittu >= '{$ed}' AND tapahtuma.laadittu <= DATE_ADD('{$ed}', INTERVAL 1 MONTH) AND tilausrivi.tyyppi = 'L', tapahtuma.kpl, 0)) * -1 ed_kpl_myynti_{$kk}
                    , SUM(IF(tapahtuma.laadittu >= '{$ed}' AND tapahtuma.laadittu <= DATE_ADD('{$ed}', INTERVAL 1 MONTH) AND tilausrivi.tyyppi = 'V', tapahtuma.kpl, 0)) * -1 ed_kpl_kulutus_{$kk}

                    ";
        }

      }

      $ehto_where = $toimipaikkarajaus = "";

      if ($onkolaajattoimipaikat and "{$toimipaikka}" != 'kaikki') {

        if ($toimipaikka != 0) {
          $_toimipaikat = array($toimipaikka, 0);
        }
        else {
          $_toimipaikat = array(0);
        }

        foreach ($_toimipaikat as $_toimipaikka) {

          $query  = "SELECT GROUP_CONCAT(tunnus) AS tunnukset
                     FROM varastopaikat
                     WHERE yhtio     = '{$kukarow['yhtio']}'
                     AND toimipaikka = '{$_toimipaikka}'";
          $vares = pupe_query($query);
          $varow = mysql_fetch_assoc($vares);

          // Jos meillä on toimipaikka setattuna ja ei löydetty tämän toimipaikan varastoja
          // Fallback: etsitään varastoja joita ei ole liitetty toimipaikkaan
          if (count($_toimipaikat) > 1 and $_toimipaikka != 0 and empty($varow['tunnukset'])) {
            continue;
          }

          if (!empty($varow['tunnukset'])) {
            $toimipaikkarajaus = "AND tilausrivi.varasto IN ({$varow['tunnukset']})";
            break;
          }
          else {
            // Jos toimipaikkarajaus palauttaa NULLia, ei näytetä tapahtumia
            $ehto_where = "AND tapahtuma.tunnus = 0";
          }
        }
      }

      //  Tutkitaan onko tää liian hias
      $query = "SELECT
                {$select_summa}
                FROM tapahtuma USE INDEX (yhtio_tuote_laadittu)
                JOIN tilausrivi ON (tilausrivi.yhtio = tapahtuma.yhtio
                  AND tilausrivi.tunnus = tapahtuma.rivitunnus
                  {$toimipaikkarajaus})
                WHERE tapahtuma.yhtio   = '{$kukarow['yhtio']}'
                AND tapahtuma.tuoteno   = '{$tuoteno}'
                AND tapahtuma.laadittu  >= '{$ed}'
                {$ehto_where}
                AND tilausrivi.tyyppi   IN ('L','W','V')";
      $result3 = pupe_query($query);
      $lrow = mysql_fetch_assoc($result3);

      $_return .= "<table><tr><th>".t("Tyyppi")."</th>$otsikkorivi<th>".t("Yhteensä")."</th></tr>";
      $erittely = array(
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
        6 => 0,
        7 => 0,
        8 => 0,
        9 => 0,
        10 => 0,
        11 => 0,
        12 => 0,
      );

      $ed_erittely = $erittely;

      foreach (array("myynti", "kulutus") as $tyyppi) {
        $_return .= "<tr class='aktiivi'><td class='tumma'>".t(str_replace("_", " ", $tyyppi))."</td>";

        $kk=date("m");
        $summa=0;
        $ed_summa=0;

        for ($y=1;$y<=12;$y++) {

          $kk--;
          if ($kk == 0) {
            $kk = 12;
          }

          $key="kpl_".$tyyppi."_".$kk;

          $muutos="";
          $muutos_abs = $lrow[$key] - $lrow["ed_".$key];

          if ($lrow["ed_".$key]>0) {
            $muutos_suht = round((($lrow[$key] / $lrow["ed_".$key])-1)*100, 2);
          }
          else {
            $muutos_suht=0;
          }

          if ($muutos_abs<>0) {
            $muutos = "edellinen: ".(int)$lrow["ed_".$key]."{".t_avainsana("Y", "", " and avainsana.selite='{$yksikko}'", "", "", "selite")."} muutos: $muutos_abs{".t_avainsana("Y", "", " and avainsana.selite='{$yksikko}'", "", "", "selite")."}";
          }

          if ($muutos_suht<>0 and $lrow[$key]<>0 and $lrow["ed_".$key] <> 0) {
            $muutos .= " ($muutos_suht%)";
          }

          if ($lrow[$key]<>0) {
            $_return .= "<td title='$muutos'>".$lrow[$key]."</td>";
          }
          else {
            $_return .= "<td title='$muutos'></td>";
          }

          $summa+=$lrow[$key];
          $ed_summa+=$lrow["ed_".$key];

          $erittely[$kk]+=$lrow[$key];
          $ed_erittely[$kk]+=$lrow["ed_".$key];
        }

        $muutos="";
        $muutos_abs = $summa - $ed_summa;

        if ($ed_summa>0) {
          $muutos_suht = round((($summa / $ed_summa)-1)*100, 2);
        }
        else {
          $muutos_suht=0;
        }

        if ($muutos_abs<>0) {
          $muutos = "edellinen: ".(int)$ed_summa."{".t_avainsana("Y", "", " and avainsana.selite='{$yksikko}'", "", "", "selite")."} muutos: $muutos_abs{".t_avainsana("Y", "", " and avainsana.selite='{$yksikko}'", "", "", "selite")."}";
        }

        if ($muutos_suht<>0 and $summa<>0 and $ed_summa<>0) {
          $muutos .= " ($muutos_suht%)";
        }

        if ($summa>0) {
          $_return .= "<td class='tumma' title='$muutos'>".number_format($summa, 2, ',', ' ')."</td></tr>";
        }
        else {
          $_return .= "<td class='tumma' title='$muutos'></td></tr>";
        }
      }

      $_return .= "<tr><th>".t("Yhteensä")."</th>";

      $kk=date("m");
      $gt=$ed_gt=0;
      for ($y=1;$y<=12;$y++) {

        $kk--;
        if ($kk == 0) {
          $kk = 12;
        }

        $muutos="";
        $muutos_abs = $erittely[$kk] - $ed_erittely[$kk];

        if ($erittely[$kk]>0) {
          $muutos_suht = round((($erittely[$kk] / $erittely[$kk])-1)*100, 2);
        }
        else {
          $muutos_suht=0;
        }

        if ($muutos_abs<>0) {
          $muutos = "edellinen: ".(int)$ed_erittely[$kk]."{".t_avainsana("Y", "", " and avainsana.selite='{$yksikko}'", "", "", "selite")."} muutos: $muutos_abs{".t_avainsana("Y", "", " and avainsana.selite='{$yksikko}'", "", "", "selite");
        }

        if ($muutos_suht<>0 and $erittely[$kk]<>0 and $ed_erittely[$kk]<>0) {
          $muutos .= " ($muutos_suht%)";
        }

        if ($erittely[$kk]>0) {
          $_return .= "<td class='tumma' title='$muutos'>".number_format($erittely[$kk], 2, ',', ' ')."</td>";
          $gt+=$erittely[$kk];
        }
        else {
          $_return .= "<td class='tumma' title='$muutos'></td>";
        }
        $ed_gt+=$ed_erittely[$kk];
      }

      $muutos="";
      $muutos_abs = $gt - $ed_gt;

      if ($ed_gt>0) {
        $muutos_suht = round((($gt / $ed_gt)-1)*100, 2);
      }
      else {
        $muutos_suht=0;
      }

      if ($muutos_abs<>0) {
        $muutos = "edellinen: ".(int)$ed_gt."{".t_avainsana("Y", "", " and avainsana.selite='{$yksikko}'", "", "", "selite")."} muutos: $muutos_abs{".t_avainsana("Y", "", " and avainsana.selite='{$yksikko}'", "", "", "selite")."}";
      }

      if ($muutos_suht<>0 and $gt<>0 and $ed_gt <> 0) {
        $muutos .= " ($muutos_suht%)";
      }

      $_return .= "<td class='tumma' title='$muutos'>".number_format($gt, 2, ',', ' ')."</td><tr></table><br><br>";
    }
  }

  if ($ajax == 'tuotteen_tilaukset') {

    $toimipaikkarajaus = "";

    if ($onkolaajattoimipaikat and "{$toimipaikka}" != 'kaikki') {
      $toimipaikkarajaus = " and ((lasku.yhtio_toimipaikka = '{$toimipaikka}' and tilausrivi.tyyppi != 'O') OR (lasku.vanhatunnus = '{$toimipaikka}' and tilausrivi.tyyppi = 'O'))";
    }

    if ($yhtiorow["saldo_kasittely"] == "U") {
      $qpvm = "if(tilausrivi.tyyppi='O', tilausrivi.toimaika, tilausrivi.kerayspvm) pvm,";
    }
    else {
      $qpvm = "tilausrivi.toimaika pvm,";
    }

    // Tilausrivit tälle tuotteelle
    $query = "SELECT if (asiakas.ryhma != '', concat(lasku.nimi,' (',asiakas.ryhma,')'), lasku.nimi) nimi,
              lasku.tunnus,
              (tilausrivi.varattu+tilausrivi.jt) kpl,
              {$qpvm}
              tilausrivi.laadittu,
              varastopaikat.nimitys varasto,
              tilausrivi.tyyppi,
              lasku.laskunro,
              lasku.tila laskutila,
              lasku.alatila,
              lasku.tilaustyyppi,
              lasku.label,
              tilausrivi.var,
              lasku2.laskunro as keikkanro,
              lasku2.tunnus AS keikkatunnus,
              tilausrivi.jaksotettu,
              tilausrivin_lisatiedot.osto_vai_hyvitys,
              tilausrivin_lisatiedot.korvamerkinta,
              tilausrivi.tilkpl,
              lasku2.comments,
              lasku2.laatija,
              lasku2.luontiaika
              FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
              LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
              JOIN lasku use index (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus {$toimipaikkarajaus} AND lasku.tila != 'D')
              LEFT JOIN varastopaikat ON (varastopaikat.yhtio = lasku.yhtio
                AND varastopaikat.tunnus    = lasku.varasto)
              LEFT JOIN lasku as lasku2 ON lasku2.yhtio = tilausrivi.yhtio and lasku2.tunnus = tilausrivi.uusiotunnus
              LEFT JOIN asiakas ON asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus
              WHERE tilausrivi.yhtio        = '$kukarow[yhtio]'
              and tilausrivi.tyyppi         in ('L','E','G','V','W','M','O')
              and tilausrivi.tuoteno        = '$tuoteno'
              and tilausrivi.laskutettuaika = '0000-00-00'
              and ((tilausrivi.var != 'P' and tilausrivi.varattu + tilausrivi.jt != 0) or (tilausrivi.var = 'P' and lasku.alatila NOT IN ('X', 'V')))
              ORDER BY pvm, tunnus";
    $jtresult = pupe_query($query);

    $_return = "";

    if (mysql_num_rows($jtresult) != 0) {

      // Avoimet rivit
      $_return .= "<table>";

      $_return .= "<tr>
          <th>".t("Asiakas/Toimittaja")."</th>
          <th>".t("Tilaus/Saapuminen")."</th>
          <th>".t("Tyyppi")."</th>
          <th>".t("Luontiaika")."</th>
          <th>".t("Toim.aika")."</th>
          <th>".t("Määrä")."</th>
          <th>".t("Myytävissä")."</th>
          </tr>";

      $yhteensa = array();
      $myynyt   = FALSE;
      $myyta    = FALSE;
      $jtrows   = array();

      while ($jtrow = mysql_fetch_assoc($jtresult)) {

        if ((int) str_replace("-", "", $jtrow["pvm"]) > (int) date("Ymd") and (($yhtiorow["saldo_kasittely"] == "U" and $myyta !== FALSE and $myyta < $myynyt) or $myynyt === FALSE)) {
          $myynyt = $myyta;
        }

        list(, , $myyta) = saldo_myytavissa($tuoteno, "KAIKKI", '', '', '', '', '', '', '', $jtrow["pvm"], '', FALSE);

        $jtrow["myytavissa"] = $myyta;

        $jtrows[] = $jtrow;
      }

      if ($myynyt === FALSE) {
        $myynyt = $myyta;
      }

      foreach ($jtrows as $jtrow) {

        $tyyppi      = "";
        $vahvistettu = "";
        $merkki      = "";
        $keikka      = "";
        $laskutunnus = $jtrow['tunnus'];
        $tyyppi_url  = "MYYNTI";

        if ($jtrow["var"] == "P") {
          $tyyppi = t("Puute");
          $merkki = "";
        }
        elseif ($jtrow["tyyppi"] == "O") {
          if ($jtrow["laskutila"] == "K") {
            $tyyppi = t("Lisätty suoraan saapumiselle");

            // Jos rivi on lisätty suoraan saapumiselle katsotaan,
            // onko saapuminen jolle rivi on lisätty vielä auki.
            // Jos saapuminen on auki niin tulostetaan kyseisen saapumisen numero,
            // muuten tulostetaan sen saapumisen numero mihin rivi on mahdollisesti liitetty
            if ($jtrow["alatila"] == "X" and $jtrow["keikkanro"] > 0) {
              $keikka = " / ".$jtrow["keikkanro"];
              $laskutunnus = $jtrow["keikkatunnus"];
            }
            else {
              $keikka = " / ".$jtrow["laskunro"];
            }
          }
          else {
            $tyyppi = t("Ostotilaus");

            if ($jtrow["keikkanro"] > 0) {
              $keikka = " / ".$jtrow["keikkanro"];
            }
          }

          if ($jtrow["kpl"] >= 0) {
            $merkki = "+";
          }
          else {
            $merkki = "-";
          }

          $tyyppi_url = "OSTO";
        }
        elseif ($jtrow["tyyppi"] == "E") {
          $tyyppi = t("Ennakkotilaus");
          $merkki = "-";
        }
        elseif ($jtrow["tyyppi"] == "G" and $jtrow["tilaustyyppi"] == "S") {
          $tyyppi = t("Sisäinen työmääräys");
          $merkki = "-";
        }
        elseif ($jtrow["tyyppi"] == "G") {
          $tyyppi = t("Varastosiirto");
          $merkki = "-";
        }
        elseif ($jtrow["tyyppi"] == "V") {
          $tyyppi = t("Kulutus");
          $merkki = "-";
          $tyyppi_url = "VALMISTUSMYYNTI";
        }
        elseif ($jtrow["tyyppi"] == "L" and $jtrow["var"] == "J") {
          $tyyppi = t("Jälkitoimitus");
          $merkki = "-";
        }
        elseif ($jtrow["tyyppi"] == "L" and $jtrow["kpl"] > 0 and $jtrow["osto_vai_hyvitys"] == "H") {
          // Marginaalioston hyvitys
          $tyyppi = t("Käytetyn tavaran hyvitys");
          $merkki = "-";
        }
        elseif ($jtrow["tyyppi"] == "L" and $jtrow["kpl"] > 0) {
          // Normimyynti
          $tyyppi = t("Myynti");
          $merkki = "-";
        }
        elseif ($jtrow["tyyppi"] == "L" and $jtrow["kpl"] < 0 and $jtrow["osto_vai_hyvitys"] != "O") {
          // Normihyvitys
          $tyyppi = t("Hyvitys");
          $merkki = "+";
        }
        elseif ($jtrow["tyyppi"] == "L" and $jtrow["kpl"] < 0 and $jtrow["osto_vai_hyvitys"] == "O") {
          // Marginaaliosto
          $tyyppi = t("Käytetyn tavaran osto");
          $merkki = "+";
        }
        elseif (($jtrow["tyyppi"] == "W" or $jtrow["tyyppi"] == "M") and $jtrow["tilaustyyppi"] == "W") {
          $tyyppi = t("Valmistus");
          $merkki = "+";
          $tyyppi_url = "VALMISTUSMYYNTI";
        }
        elseif (($jtrow["tyyppi"] == "W" or $jtrow["tyyppi"] == "M") and $jtrow["tilaustyyppi"] == "V") {
          $tyyppi = t("Asiakkaallevalmistus");
          $merkki = "+";
          $tyyppi_url = "VALMISTUSMYYNTI";
        }

        if ($jtrow["jaksotettu"] == 1) {
          $vahvistettu = " (".t("Vahvistettu").")";
        }

        if ($jtrow["var"] == "P") {
          $yhteensa[$tyyppi] += $jtrow['tilkpl'];
          $kappalemaara = $jtrow["tilkpl"];
        }
        else {
          $yhteensa[$tyyppi] += $jtrow["kpl"];
          $kappalemaara = $jtrow["kpl"];
        }

        if ($jtrow["varasto"] != "") {
          $tyyppi = $tyyppi." - ".$jtrow["varasto"];
        }

        if ((int) str_replace("-", "", $jtrow["pvm"]) > (int) date("Ymd") and $myynyt !== FALSE) {
          $_return .= "<tr>
              <td colspan='6' align='right' class='spec'>".t("Myytävissä nyt").":</td>
              <td align='right' class='spec'>".sprintf('%.2f', $myynyt)."</td>
              </tr>";
          $myynyt = FALSE;
        }

        $classlisa = ($jtrow['tyyppi'] == 'O' and $jtrow["kpl"] == 0) ? " class='error'" : "";

        $_return .= "<tr{$classlisa}>
            <td>$jtrow[nimi]</td>";

        if ($jtrow["tyyppi"] == "O" and $jtrow["laskutila"] != "K" and $jtrow["keikkanro"] > 0 and $jtrow['comments'] != '') {
          $id = md5(uniqid());
          $_return .= "<td valign='top' class='tooltip' id='{$id}'>";
        }
        else {

          $label_color = "";
          if (isset($jtrow['label']) and $jtrow['label'] != '') {
            $label_query = "SELECT selite
                            FROM avainsana
                            WHERE yhtio = '{$kukarow['yhtio']}'
                            AND tunnus  = {$jtrow['label']}
                            AND laji    = 'label'";
            $label_result = pupe_query($label_query);

            if (mysql_num_rows($label_result) == 1) {
              $label_row = mysql_fetch_assoc($label_result);
              $label_color = " class='aktiivi' style = 'background-color: {$label_row['selite']};'";
            }
          }

          $_return .= "<td{$label_color}>";
        }

        $_return .= "<a href=\"javascript:lataaiframe('{$laskutunnus}', '{$palvelin2}raportit/asiakkaantilaukset.php?toim={$tyyppi_url}&tee=NAYTATILAUS&tunnus={$laskutunnus}&ohje=off');\">{$laskutunnus}</a>$keikka";

        if ($jtrow["tyyppi"] == "O" and $jtrow["laskutila"] != "K" and $jtrow["keikkanro"] > 0 and $jtrow['comments'] != '') {

          $query = "SELECT nimi
                    FROM kuka
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND kuka    = '{$jtrow['laatija']}'";
          $kuka_chk_res = pupe_query($query);
          $kuka_chk_row = mysql_fetch_assoc($kuka_chk_res);

          $_return .= "&nbsp;<img src='{$palvelin2}/pics/lullacons/info.png' class='tooltip' id='{$id}'>";
          $_return .= "<div id='div_{$id}' class='popup' style='width: 500px;'>";
          $_return .= t("Saapuminen"). ": {$jtrow['keikkanro']} / {$jtrow['nimi']}<br /><br />";
          $_return .= t("Laatija"). ": {$kuka_chk_row['nimi']}<br />";
          $_return .= t("Luontiaika"). ": ". tv1dateconv($jtrow['luontiaika'], "pitkä"). "<br /><br />";
          $_return .= $jtrow["comments"];
          $_return .= "</div>";
        }

        $_return .= "</td>";
        $_return .= "<td>";
        $_return .= $tyyppi;

        if (!empty($jtrow['korvamerkinta'])) {

          if ($jtrow['korvamerkinta'] == '.') {
            $luokka = '';
          }
          else {
            $luokka = 'tooltip';
          }
          $id = md5(uniqid());
          $_return .= "&nbsp;<img src='{$palvelin2}pics/lullacons/info.png' class='{$luokka}' id='{$id}_info'>";
          $_return .= "<div id='div_{$id}_info' class='popup'>";
          $_return .= $jtrow['korvamerkinta'];
          $_return .= "</div>";
        }

        $_return .= "</td>";

        $_return .= "
            <td>".tv1dateconv($jtrow["laadittu"])."</td>
            <td>".tv1dateconv($jtrow["pvm"])."$vahvistettu</td>
            <td align='right'>$merkki".abs($kappalemaara)."</td>
            <td align='right'>".sprintf('%.2f', $jtrow["myytavissa"])."</td>
            </tr>";

        $_return .= "<tr><td colspan='7' class='back' style='width:100%; padding:0; margin:0;'><div id = 'ifd_{$jtrow['tunnus']}' style='width:100%; border:1px solid; display:none'></div></td></tr>";
      }

      if ($myynyt !== FALSE) {
        $_return .= "<tr>
            <td colspan='6' align='right' class='spec'>".t("Myytävissä nyt").":</td>
            <td align='right' class='spec'>".sprintf('%.2f', $myynyt)."</td>
            </tr>";
      }

      foreach ($yhteensa as $type => $kappale) {
        $_return .= "<tr>";
        $_return .= "<th colspan='5'>$type ".t("yhteensä")."</th>";
        $_return .= "<th style='text-align:right;'>$kappale</th>";
        $_return .= "<th></th>";
        $_return .= "</tr>";
      }

      $_return .= "</table><br>";
    }
    else {
      $_return .= "<font class='info'>". t("Ei tilauksia"). "</font><br /><br />";
    }
  }

  echo json_encode(utf8_encode($_return));

  exit;
}

if ($livesearch_tee == "TUOTEHAKU") {
  livesearch_tuotehaku();
  exit;
}

// Liitetiedostot popup
if (isset($liite_popup_toiminto) and $liite_popup_toiminto == "AK") {
  liite_popup("AK", $tuotetunnus, $width, $height);
}
else {
  liite_popup("JS");
}

if (function_exists("js_popup")) {
  echo js_popup();
}

// Enaboidaan ajax kikkare
enable_ajax();

echo "<script type='text/javascript'>

        function suljedivi(tunnus) {
          $('#ifd_'+tunnus).hide();
        }

        function lataaiframe(tunnus, url) {

          var ifd = $('#ifd_'+tunnus);
          var ifr = $('#ifr_'+tunnus);

          if (ifr.length) {

            if (ifr.attr('src') == url) {
              ifd.toggle();
            }
            else {
              ifd.show();
              ifr.attr('src', url);
            }
          }
          else {
            ifd.show();
            ifd.html(\"<div style='float:right;'><a href=\\\"javascript:suljedivi('\"+tunnus+\"');\\\">".t("Piilota")." <img src='{$palvelin2}pics/lullacons/stop.png'></a></div><iframe id='ifr_\"+tunnus+\"' src='\"+url+\"' style='width:100%; height: 800px; border: 1px; display: block;'></iFrame>\");
          }
        }

        $(function() {
          $('#vastaavat').on('click', function() {

            var _src = '{$palvelin2}pics/loading_blue_small.gif',
                toimipaikka = $('#toimipaikka option:selected').val(),
                _tp_kasittely = $('#_tp_kasittely').val(),
                saldoaikalisa = $('#saldoaikalisa').val(),
                toimipaikan_varastot = $('#toimipaikan_varastot').val();

            $('#vastaavat_container').html('<img src=\"'+_src+'\" /><br />');

            $.ajax({
              async: false,
              type: 'POST',
              dataType: 'JSON',
              data: {
                ajax: 'vastaavat',
                no_head: 'yes',
                ohje: 'off',
                tuoteno: $('#tuoteno').val(),
                toimipaikka: toimipaikka,
                _tp_kasittely: _tp_kasittely,
                saldoaikalisa: saldoaikalisa,
                toimipaikan_varastot: toimipaikan_varastot
              },
              success: function(data) {
                $('#vastaavat_container').html(data);
              }
            });
          });

          $('#korvaavat').on('click', function() {

            var _src = '{$palvelin2}pics/loading_blue_small.gif',
                toimipaikka = $('#toimipaikka option:selected').val(),
                _tp_kasittely = $('#_tp_kasittely').val(),
                saldoaikalisa = $('#saldoaikalisa').val(),
                toimipaikan_varastot = $('#toimipaikan_varastot').val();

            $('#korvaavat_container').html('<img src=\"'+_src+'\" /><br />');

            $.ajax({
              async: false,
              type: 'POST',
              dataType: 'JSON',
              data: {
                ajax: 'korvaavat',
                no_head: 'yes',
                ohje: 'off',
                tuoteno: $('#tuoteno').val(),
                toimipaikka: toimipaikka,
                _tp_kasittely: _tp_kasittely,
                saldoaikalisa: saldoaikalisa,
                toimipaikan_varastot: toimipaikan_varastot
              },
              success: function(data) {
                $('#korvaavat_container').html(data);
              }
            });
          });

          $('#tuotteen_tilaukset').on('click', function() {

            var _src = '{$palvelin2}pics/loading_blue_small.gif',
                toimipaikka = $('#toimipaikka option:selected').val();";

if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
  echo "   $('#tuotteen_tilaukset_img').attr('src', '{$palvelin2}pics/facelift/refresh.png');";
}
else {
  echo "   $(this).val('".t("Päivitä")."');";
}

echo "     $('#tuotteen_tilaukset_container').html('<img src=\"'+_src+'\" /><br />');

            $.ajax({
              async: false,
              type: 'POST',
              dataType: 'JSON',
              data: {
                ajax: 'tuotteen_tilaukset',
                no_head: 'yes',
                ohje: 'off',
                tuoteno: $('#tuoteno').val(),
                toimipaikka: toimipaikka
              },
              success: function(data) {
                $('#tuotteen_tilaukset_container').html(data);
                bind_tooltip();
              }
            });
          });

          $('#raportointi').on('click', function() {

            var _src = '{$palvelin2}pics/loading_blue_small.gif',
                raportointi_tyyppi = $('.raportti_tyyppi:checked').val(),
                yksikko = $('#yksikko').val(),
                toimipaikka = $('#toimipaikka option:selected').val();";

if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
  echo "    $('#raportointi_img').attr('src', '{$palvelin2}pics/facelift/refresh.png');";
}
else {
  echo "    $(this).val('".t("Päivitä")."');";
}

echo"       $('#raportointi_container').html('<img src=\"'+_src+'\" /><br />');

            $.ajax({
              async: false,
              type: 'POST',
              dataType: 'JSON',
              data: {
                ajax: 'raportointi',
                no_head: 'yes',
                ohje: 'off',
                tuoteno: $('#tuoteno').val(),
                raportti: raportointi_tyyppi,
                yksikko: yksikko,
                toimipaikka: toimipaikka
              },
              success: function(data) {
                $('#raportointi_container').html(data);
              }
            });
          });

          $('#tapahtumat').on('click', function() {

            var _src = '{$palvelin2}pics/loading_blue_small.gif',
                historia = $('#historia option:selected').val(),
                sarjanumeroseuranta = $('#sarjanumeroseuranta').val(),
                ei_saldoa = $('#ei_saldoa').val(),
                kehahin = $('#kehahin').val(),
                tapahtumalaji = $('#tapahtumalaji option:selected').val(),
                tilalehinta = $('#tilalehinta:checked').val(),
                myyntilaskun_myyja = $('#myyntilaskun_myyja:checked').val(),
                kokonaissaldo_tapahtumalle = $('#kokonaissaldo_tapahtumalle').val(),
                toimipaikka = $('#toimipaikka option:selected').val(),
                sarjanumero_kpl = $('#sarjanumero_kpl').val();";

if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
  echo "    $('#tapahtumat_img').attr('src', '{$palvelin2}pics/facelift/refresh.png');";
}
else {
  echo "    $(this).val('".t("Päivitä")."');";
}

echo"
            $('#tapahtumat_container').html('<img src=\"'+_src+'\" /><br />');

            $.ajax({
              async: false,
              type: 'POST',
              dataType: 'JSON',
              data: {
                ajax: 'tapahtumat',
                no_head: 'yes',
                ohje: 'off',
                tuoteno: $('#tuoteno').val(),
                historia: historia,
                sarjanumeroseuranta: sarjanumeroseuranta,
                ei_saldoa: ei_saldoa,
                kehahin: kehahin,
                tapahtumalaji: tapahtumalaji,
                tilalehinta: tilalehinta,
                myyntilaskun_myyja: myyntilaskun_myyja,
                kokonaissaldo_tapahtumalle: kokonaissaldo_tapahtumalle,
                toimipaikka: toimipaikka,
                sarjanumero_kpl: sarjanumero_kpl
              },
              success: function(data) {
                $('#tapahtumat_container').html(data);
                bind_tooltip();
              }
            });
          });

          $('#varastopaikat').on('click', function() {

            var _src = '{$palvelin2}pics/loading_blue_small.gif',
                sarjanumeroseuranta = $('#sarjanumeroseuranta').val(),
                _tp_kasittely = $('#_tp_kasittely').val(),
                toimipaikka = $('#toimipaikka option:selected').val(),
                saldoaikalisa = $('#saldoaikalisa').val();";

if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
  echo "    $('#varastopaikat_img').attr('src', '{$palvelin2}pics/facelift/refresh.png');";
}
else {
  echo "    $(this).val('".t("Päivitä")."');";
}

echo"       $('#varastopaikat_container').html('<img src=\"'+_src+'\" /><br />');

            $.ajax({
              async: false,
              type: 'POST',
              dataType: 'JSON',
              data: {
                ajax: 'varastopaikat',
                no_head: 'yes',
                ohje: 'off',
                tuoteno: $('#tuoteno').val(),
                sarjanumeroseuranta: sarjanumeroseuranta,
                _tp_kasittely: _tp_kasittely,
                toimipaikka: toimipaikka,
                saldoaikalisa: saldoaikalisa
              },
              success: function(data) {
                $('#varastopaikat_container').html(data);
                var koksaldo = $('#kokonaissaldo_tapahtumalle_ajax').val(),
                    kehahin = $('#kehahin').val();

                $('#kokonaissaldo_tapahtumalle').val(koksaldo);
                $('#ajax_kokonaissaldo').val(Math.round(koksaldo * kehahin));
              }
            });
          });

          $('.raportti_tyyppi').on('change', function() {
            $('#raportointi').trigger('click');
          });

          $('#toimipaikka').on('change', function() {
            $('#varastopaikat').trigger('click');

            if ($('#tuotteen_tilaukset_container:not(:empty)').length) {
              $('#tuotteen_tilaukset').trigger('click');
            }

            if ($('#raportointi_container:not(:empty)').length) {
              $('#raportointi').trigger('click');
            }

            if ($('#tapahtumat_container:not(:empty)').length) {
              $('#tapahtumat').trigger('click');
            }
          });

          $('#historia, #tapahtumalaji').on('change', function() {
            $('#tapahtumat').trigger('click');
          });

          $('#tilalehinta').on('change', function() {
            var tilalehinta = $('#tilalehinta:checked').val();
            var colspan = $('#tapahtumalaji_header').attr('colspan');

            colspan = parseInt(colspan);

            if (tilalehinta) {
              colspan = colspan + 1;
              $('#tapahtumalaji_header').attr('colspan', colspan);
              $('#tilalehinta_hearder').show();
            }
            else {
              colspan = colspan - 1;
              $('#tapahtumalaji_header').attr('colspan', colspan);
              $('#tilalehinta_hearder').hide();
            }

            $('#tapahtumat').trigger('click');
          });

          $('#myyntilaskun_myyja').on('change', function() {
            var myyntilaskun_myyja = $('#myyntilaskun_myyja:checked').val();
            var colspan = $('#tapahtumalaji_header').attr('colspan');
            var colspan_var = $('#varastonarvo_nyt_header').attr('colspan');

            colspan = parseInt(colspan);
            colspan_var = parseInt(colspan_var);

            if (myyntilaskun_myyja) {
              colspan = colspan + 1;
              colspan_var = colspan_var + 1;

              $('#tapahtumalaji_header').attr('colspan', colspan);
              $('#varastonarvo_nyt_header').attr('colspan', colspan_var);

              $('#myyntilaskun_myyja_header').show();
              document.cookie = \"myyntilaskun_myyja=show;30\";
            }
            else {
              colspan = colspan - 1;
              colspan_var = colspan_var + 1;

              $('#tapahtumalaji_header').attr('colspan', colspan);
              $('#varastonarvo_nyt_header').attr('colspan', colspan_var);

              $('#myyntilaskun_myyja_header').hide();
              document.cookie = \"myyntilaskun_myyja=;30\";
            }

            $('#tapahtumat').trigger('click');
          });

          $('#varastopaikat').trigger('click');

          if ('{$yhtiorow['tuotekysely']}' == '') {
            $('#vastaavat').trigger('click');
            $('#korvaavat').trigger('click');
            $('#tuotteen_tilaukset').trigger('click');
            $('#raportointi').trigger('click');
            $('#tapahtumat').trigger('click');
          }
        });
      </script>";

if ($tee == 'N' or $tee == 'E') {

  if ($tee == 'N') {
    $oper='>';
    $suun='';
  }
  else {
    $oper='<';
    $suun='desc';
  }

  $query = "SELECT tuote.tuoteno
            FROM tuote use index (tuoteno_index)
            WHERE tuote.yhtio     = '$kukarow[yhtio]'
            and tuote.tuoteno $oper '$tuoteno'
            and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
            and tuote.tuotetyyppi not in ('A','B')
            ORDER BY tuote.tuoteno $suun
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $trow = mysql_fetch_assoc($result);
    $tuoteno = $trow['tuoteno'];
    $tee = 'Z';
  }
  else {
    $varaosavirhe = t("Yhtään tuotetta ei löytynyt")."!";
    $tuoteno = '';
    $tee = 'Y';
  }
}

// Tehdään lopetusmuuttuja, kun ollaan saatu oikea tuotenumero tietoon
if (isset($tuoteno)) {
  $tkysy_lopetus = "{$palvelin2}tuote.php////toim=$toim//tee=Z//tuoteno=".urlencode($tuoteno)."//toimipaikka=$toimipaikka//raportti=$raportti//historia=$historia//tapahtumalaji=$tapahtumalaji";
}
else {
  $tkysy_lopetus = "";
}

if ($lopetus != "") {
  // Lisätään tämä lopetuslinkkiin
  $tkysy_lopetus = $lopetus."/SPLIT/".$tkysy_lopetus;
}

if ($tee == 'NAYTATILAUS') {
  echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
  require "raportit/naytatilaus.inc";
  exit;
}

echo "<font class='head'>".t("Tuotekysely")."</font><hr>";

if ($tee == 'Z' and isset($tyyppi) and $tyyppi != '') {
  if ($tyyppi == 'TOIMTUOTENO') {
    $tuoteno = "?".$tuoteno;
  }
}

if ($tee == 'Z' and $tuoteno != "") {
  require "inc/tuotehaku.inc";
}

if ($tee == 'Y') echo "<font class='error'>$varaosavirhe</font>";

//syotetaan tuotenumero
$formi  = 'formi';
$kentta = 'tuoteno';

// Paluu nappi osto/myyntitilaukselle
if ($kukarow["kesken"] > 0) {
  $query    = "SELECT *
               FROM lasku
               WHERE tunnus = '$kukarow[kesken]'
               AND yhtio    = '$kukarow[yhtio]'";
  $result   = pupe_query($query);
  $laskurow = mysql_fetch_assoc($result);
}
else {
  $laskurow = array(
    "tila" => "",
    "maa_lahetys" => "",
  );
}

if ($kukarow["kuka"] != "" and $laskurow["tila"] == "O") {
  echo "  <form method='post' action='".$palvelin2."tilauskasittely/tilaus_osto.php'>
      <input type='hidden' name='aktivoinnista' value='true'>
      <input type='hidden' name='tee' value='AKTIVOI'>
      <input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
      <input type='submit' value='".t("Takaisin tilaukselle")."'>
      </form><br><br>";
}
elseif (strpos($lopetus, "tilaus_myynti.php") === FALSE and $kukarow["kuka"] != "" and $laskurow["tila"] != "" and $laskurow["tila"] != "K" and $toim_kutsu != "") {
  echo "  <form method='post' action='".$palvelin2."tilauskasittely/tilaus_myynti.php'>
      <input type='hidden' name='toim' value='$toim_kutsu'>
      <input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
      <input type='submit' value='".t("Takaisin tilaukselle")."'>
      </form><br><br>";
}

echo "<br>";
echo "<table>";

echo "<tr>";
echo "<form method='post' name='formi' autocomplete='off'>";
echo "<input type='hidden' name='toim' value='$toim'>";
echo "<input type='hidden' name='lopetus' value='$lopetus'>";
echo "<input type='hidden' name='tee' value='Z'>";
echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
echo "<th style='vertical-align:middle;'>".t("Tuotehaku")."</th>";
echo "<td>".livesearch_kentta("formi", "TUOTEHAKU", "tuoteno", 300)."</td>";
echo "<td class='back'>";
echo "<input type='submit' class='hae_btn' value='".t("Hae")."'></td>";
echo "</form>";

echo "</tr>";

echo "<tr>";
echo "<form method='post' name='formi2' autocomplete='off'>";
echo "<input type='hidden' name='toim' value='$toim'>";
echo "<input type='hidden' name='lopetus' value='$lopetus'>";
echo "<input type='hidden' name='tee' value='Z'>";
echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";

echo "<th style='vertical-align:middle;'>";
echo "<input type='hidden' name='tyyppi' value='TOIMTUOTENO'>";
echo t("Toimittajan tuotenumero");
echo "</th>";

echo "<td>";
echo "<input type='text' name='tuoteno' value='' style='width:300px;'>";
echo "</td>";

echo "<td class='back'>";
echo "<input type='submit' class='hae_btn' value='".t("Hae")."'>";
echo "</td>";
echo "</form>";

//Jos ei haettu, annetaan 'edellinen' & 'seuraava'-nappi
if ($ulos == '' and $tee == 'Z') {
  echo "<form method='post'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<input type='hidden' name='tee' value='E'>";
  echo "<input type='hidden' name='tyyppi' value=''>";
  echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
  echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
  echo "<td class='back'>";
  echo "<input type='submit' value='".t("Edellinen")."'>";
  echo "</td>";
  echo "</form>";

  echo "<form method='post'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<input type='hidden' name='tyyppi' value=''>";
  echo "<input type='hidden' name='tee' value='N'>";
  echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
  echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
  echo "<td class='back'>";
  echo "<input type='submit' value='".t("Seuraava")."'>";
  echo "</td>";
  echo "</form>";
}

echo "</tr></table><br>";

//tuotteen varastostatus
if ($tee == 'Z') {

  echo "<font class='message'>".t("Tuotetiedot")."</font><hr>";

  $query = "SELECT tuote.*,
            date_format(tuote.muutospvm, '%Y-%m-%d') muutos, date_format(tuote.luontiaika, '%Y-%m-%d') luonti
            FROM tuote
            WHERE tuote.yhtio = '$kukarow[yhtio]'
            and tuote.tuoteno = '$tuoteno'";
  $result = pupe_query($query);

  $query = "SELECT sum(saldo) saldo
            from tuotepaikat
            where tuoteno = '$tuoteno'
            and saldo     > 0
            and yhtio     = '$kukarow[yhtio]'";
  $salre = pupe_query($query);
  $salro = mysql_fetch_assoc($salre);

  if (mysql_num_rows($result) == 1) {
    $tuoterow = mysql_fetch_assoc($result);
  }
  else {
    $tuoterow = array();
  }

  // tuotteen toimittajatiedot
  if ($tuoterow["ei_saldoa"] == '') {
    $query = "SELECT tuotteen_toimittajat.*,
              toimi.ytunnus, toimi.nimi, toimi.nimitark, toimi.oletus_valkoodi,
              IF(tuotteen_toimittajat.toimitusaika != 0, tuotteen_toimittajat.toimitusaika, toimi.oletus_toimaika) AS toimitusaika,
              if (jarjestys = 0, 9999, jarjestys) sorttaus
              FROM tuotteen_toimittajat
              LEFT JOIN toimi on (toimi.yhtio = tuotteen_toimittajat.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus)
              WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
              and tuotteen_toimittajat.tuoteno = '$tuoteno'
              ORDER BY sorttaus";
    $ttres = pupe_query($query);

    $ttrow = array();

    while ($ttrowx = mysql_fetch_assoc($ttres)) {
      $ttrow[] = $ttrowx;
    }
  }
  else {
    $ttrow = array();
  }

  // Tarkastetaan onko taricit käytössä
  $tv_kaytossa = tarkista_onko_taric_veroperusteet_kaytossa();

  if ($tuoterow["tuoteno"] != "") {

    if (!empty($yhtiorow["saldo_kasittely"])) {
      $saldoaikalisa = date("Y-m-d");
    }
    else {
      $saldoaikalisa = "";
    }

    echo "<input type='hidden' id='saldoaikalisa' value='{$saldoaikalisa}' />";

    $sarjanumero_kpl = 0;

    // Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksilöiden ostohinnoista (ostetut yksilöt jotka eivät vielä ole myyty(=laskutettu))
    if ($tuoterow["sarjanumeroseuranta"] == "S" or $tuoterow['sarjanumeroseuranta'] == 'G') {
      $query  = "SELECT sarjanumeroseuranta.tunnus
                 FROM sarjanumeroseuranta
                 LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
                 LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                 WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
                 and sarjanumeroseuranta.tuoteno           = '$tuoterow[tuoteno]'
                 and sarjanumeroseuranta.myyntirivitunnus != -1
                 and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
                 and tilausrivi_osto.laskutettuaika       != '0000-00-00'";
      $sarjares = pupe_query($query);

      $kehahin = 0;

      if (mysql_num_rows($sarjares) > 0) {
        while ($sarjarow = mysql_fetch_assoc($sarjares)) {
          $kehahin += sarjanumeron_ostohinta("tunnus", $sarjarow["tunnus"]);
          $sarjanumero_kpl++;
        }

        $tuoterow['kehahin'] = sprintf('%.6f', ($kehahin / mysql_num_rows($sarjares)));
      }
      else {
        $tuoterow['kehahin'] = "";
      }
    }

    echo "<input type='hidden' id='sarjanumero_kpl' value='{$sarjanumero_kpl}' />";

    // Lisätäänkö kuluja varastonarvoon / katteeseen
    $tuoterow['kehahin'] = hintapyoristys(hinta_kuluineen($tuoterow['tuoteno'], $tuoterow['kehahin']), 6, TRUE);
    $tuoterow['vihahin'] = hintapyoristys(hinta_kuluineen($tuoterow['tuoteno'], $tuoterow['vihahin']), 6, TRUE);

    $alkuperainen_keskihankintahinta = $tuoterow["kehahin"];

    if ($kukarow["naytetaan_katteet_tilauksella"] == "B" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "B")) {
      $tuoterow['kehahin'] = $tuoterow['kehahin'];
    }
    else {
      if      ($tuoterow['epakurantti100pvm'] != '0000-00-00') $tuoterow['kehahin'] = 0;
      elseif ($tuoterow['epakurantti75pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.25, 6);
      elseif ($tuoterow['epakurantti50pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.5,  6);
      elseif ($tuoterow['epakurantti25pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.75, 6);
    }

    // Hinnastoon
    if (strtoupper($tuoterow['hinnastoon']) == 'E') {
      $tuoterow['hinnastoon'] = "<font class='red'>".t("Ei")."</font>";
    }
    else {
      $tuoterow['hinnastoon'] = "<font class='green'>".t("Kyllä")."</font>";
    }

    // Varastoon
    if ($tuoterow['status'] == 'T' or $tuoterow['status'] == 'P' or $tuoterow["ei_saldoa"] == 'o') {
      $tuoterow['ei_varastoida'] = "<font class='red'>".t("Ei")."</font>";
    }
    else {
      $tuoterow['ei_varastoida'] = "<font class='green'>".t("Kyllä")."</font>";
    }

    // Ostoehdotukselle
    if ($tuoterow['ostoehdotus'] == 'E') {
      $tuoterow['ostoehdotus'] = "<font class='red'>".t("Ei")."</font>";
    }
    else {
      $tuoterow['ostoehdotus'] = "<font class='green'>".t("Kyllä")."</font>";
    }

    //tullinimike
    $cn1 = $tuoterow["tullinimike1"];
    $cn2 = substr($tuoterow["tullinimike1"], 0, 6);
    $cn3 = substr($tuoterow["tullinimike1"], 0, 4);

    $query = "SELECT cn, dm, su from tullinimike where cn='$cn1' and kieli = '$yhtiorow[kieli]'";
    $tulliresult1 = pupe_query($query);

    $query = "SELECT cn, dm, su from tullinimike where cn='$cn2' and kieli = '$yhtiorow[kieli]'";
    $tulliresult2 = pupe_query($query);

    $query = "SELECT cn, dm, su from tullinimike where cn='$cn3' and kieli = '$yhtiorow[kieli]'";
    $tulliresult3 = pupe_query($query);

    $tullirow1 = mysql_fetch_assoc($tulliresult1);
    $tullirow2 = mysql_fetch_assoc($tulliresult2);
    $tullirow3 = mysql_fetch_assoc($tulliresult3);

    //perusalennus
    $query  = "SELECT alennus from perusalennus where ryhma='$tuoterow[aleryhma]' and yhtio='$kukarow[yhtio]'";
    $peralresult = pupe_query($query);
    $peralrow = mysql_fetch_assoc($peralresult);

    $query = "SELECT distinct valkoodi, maa
              from hinnasto
              where yhtio = '$kukarow[yhtio]'
              and tuoteno = '$tuoterow[tuoteno]'
              and laji    = ''
              order by maa, valkoodi";
    $hintavalresult = pupe_query($query);

    $valuuttalisa = "";

    while ($hintavalrow = mysql_fetch_assoc($hintavalresult)) {

      // katotaan onko tuotteelle valuuttahintoja
      $query = "SELECT *
                from hinnasto
                where yhtio  = '$kukarow[yhtio]'
                and tuoteno  = '$tuoterow[tuoteno]'
                and valkoodi = '$hintavalrow[valkoodi]'
                and maa      = '$hintavalrow[maa]'
                and laji     = ''
                and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
                order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
                limit 1";
      $hintaresult = pupe_query($query);

      while ($hintarow = mysql_fetch_assoc($hintaresult)) {
        $valuuttalisa .= "<br>$hintarow[maa]: ".hintapyoristys($hintarow["hinta"])." $hintarow[valkoodi]";
      }
    }

    $prossat = '';

    if ($tv_kaytossa and $tullirow1['cn'] != '') {
      $alkuperamaat = array();
      $alkuperamaat[] = explode(',', $tuoterow['alkuperamaa']);
      $tuorow = $tuoterow;
      $prossa_str = '';

      foreach ($alkuperamaat as $alkuperamaa) {
        foreach ($alkuperamaa as $alkupmaa) {

          $laskurow['maa_lahetys'] = $alkupmaa;

          $mista = 'tuote.php';

          include 'tilauskasittely/taric_veroperusteet.inc';

          $prossa_str = trim($tulliprossa, "0");

          if (strlen($prossa_str) > 1) {
            $prossat .= "<br>$prossa_str $alkupmaa";
          }
        }
      }
    }

    //eka laitetaan tuotteen yleiset (aika staattiset) tiedot
    echo "<table class='tuotekysely'>";

    echo "<tr>";
    echo "<th>".t("Tuotenumero")."<br>".t("Tuotemerkki")."</th>";
    echo "<th>".t("Yksikkö")."</th>";
    echo "<th>".t("Eankoodi")."</th>";
    echo "<th colspan='2'>".t("Nimitys")."</th>";
    echo "<th>".t("Hinnastoon")."<br>".t("Status")."</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td style='font-weight:bold;'>$tuoterow[tuoteno]";

    $tuotehallintaoikeus = tarkista_oikeus('yllapito.php', 'tuote%', 1, true);

    if ($tuotehallintaoikeus) {
      echo "&nbsp;&nbsp;
            <a href='{$palvelin2}yllapito.php?toim={$tuotehallintaoikeus["alanimi"]}&tunnus={$tuoterow["tunnus"]}&lopetus={$tkysy_lopetus}'>";

      if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
        echo "<img style='height:15px;' src='{$palvelin2}pics/facelift/jakoavain.png' alt='", t("Muokkaa"), "' title='", t("Muuta tuotteen tietoja"), "' />";
      }
      else {
        echo "<img style='height:10px;' src='{$palvelin2}pics/lullacons/document-properties.png' alt='", t("Muokkaa"), "' title='", t("Muuta tuotteen tietoja"), "' />";
      }

      echo "</a>";
    }

    //haetaan orginaalit
    if (table_exists("tuotteen_orginaalit")) {
      $query = "SELECT *
                from tuotteen_orginaalit
                where yhtio = '$kukarow[yhtio]'
                and tuoteno = '$tuoterow[tuoteno]'";
      $origresult = pupe_query($query);

      if (mysql_num_rows($origresult) > 0) {

        $i = 0;

        $trimtuoteno = str_replace(array(" ", "+"), "_", $tuoterow["tuoteno"]);

        $divit = "<div id='div_".sanitoi_javascript_id($trimtuoteno)."' class='popup'>";
        $divit .= "<table><tr><td valign='top'><table>";
        $divit .= "<tr><td class='back' valign='top' align='center'>".t("Alkuperäisnumero")."</td><td class='back' valign='top' align='center'>".t("Hinta")."</td><td class='back' valign='top' align='center'>".t("Merkki")."</td></tr>";

        while ($origrow = mysql_fetch_assoc($origresult)) {
          ++$i;
          if ($i == 20) {
            $divit .= "</table></td><td valign='top'><table>";
            $divit .= "<tr><td class='back' valign='top' align='center'>".t("Alkuperäisnumero")."</td><td class='back' valign='top' align='center'>".t("Hinta")."</td><td class='back' valign='top' align='center'>".t("Merkki")."</td></tr>";
            $i = 1;
          }
          $divit .= "<tr><td class='back' valign='top'>$origrow[orig_tuoteno]</td><td class='back' valign='top' align='right'>$origrow[orig_hinta]</td><td class='back' valign='top'>$origrow[merkki]</td></tr>";
        }

        $divit .= "</table></td></tr>";

        $divit .= "</table>";
        $divit .= "</div>";

        echo "&nbsp;&nbsp;<a src='#' class='tooltip' id='".sanitoi_javascript_id($trimtuoteno)."'><img src='pics/lullacons/info.png' height='13'></a>";
      }
    }

    //1
    echo "<br>".t_avainsana("TUOTEMERKKI", "", " and avainsana.selite='$tuoterow[tuotemerkki]'", "", "", "selite")."</td>";
    echo "<td>".t_avainsana("Y", "", "and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite");

    foreach ($ttrow as $tt_rivi) {

      $_pakkaukset = tuotteen_toimittajat_pakkauskoot($tt_rivi['tunnus']);
      foreach ($_pakkaukset as $_pak) {
        echo "<br>$_pak[0] $_pak[1]";
      }
    }

    echo "</td>";

    echo "<td>$tuoterow[eankoodi]</td><td colspan='2' style='font-weight:bold;'>".t_tuotteen_avainsanat($tuoterow, 'nimitys')."</td>";
    echo "<td>$tuoterow[hinnastoon]<br>";

    if ($tuoterow["status"] == "P") echo "<font class='error'>";
    $product_statuses = product_statuses();
    echo $product_statuses[$tuoterow["status"]];
    if ($tuoterow["status"] == "P") echo "</font>";

    echo "</td>";
    echo "</tr>";

    //2
    echo "<tr>";
    echo "<th>".t("Osasto/try")."</th>";
    echo "<th>".t("Toimittaja")."</th>";
    echo "<th>".t("Aleryhmä")."</th>";
    echo "<th>".t("Tähti")."</th>";
    echo "<th>".t("Perusalennus")."</th>";
    echo "<th>".t("VAK")."</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>$tuoterow[osasto] - ".t_avainsana("OSASTO", "", "and avainsana.selite='$tuoterow[osasto]'", "", "", "selitetark")."<br>$tuoterow[try] - ".t_avainsana("TRY", "", "and avainsana.selite='$tuoterow[try]'", "", "", "selitetark")."</td>";
    echo "<td>";
    foreach ($ttrow as $tt_rivi) {
      echo "{$tt_rivi["ytunnus"]} {$tt_rivi["nimi"]}<br>";
    }
    echo "</td>";
    echo "<td>$tuoterow[aleryhma]</td>";
    echo "<td>$tuoterow[tahtituote]</td>";
    echo "<td>$peralrow[alennus]%</td>";

    if ($yhtiorow["vak_kasittely"] != "" and $tuoterow["vakkoodi"] != "" and $tuoterow["vakkoodi"] != "0") {
      $query = "SELECT tunnus, concat_ws(' / ', concat('UN',yk_nro), nimi_ja_kuvaus, luokka, luokituskoodi, pakkausryhma, lipukkeet, rajoitetut_maarat_ja_poikkeusmaarat_1) vakkoodi
                FROM vak
                WHERE yhtio = '{$kukarow['yhtio']}'
                and tunnus  = '{$tuoterow['vakkoodi']}'";
      $vak_res = pupe_query($query);
      $vak_row = mysql_fetch_assoc($vak_res);

      $tuoterow["vakkoodi"] = $vak_row["vakkoodi"];
    }

    echo "<td>$tuoterow[vakkoodi]</td>";
    echo "</tr>";

    //3
    echo "<tr>";
    echo "<th>".t("Toimtuoteno")."</th>";
    echo "<th>".t("Myyntihinta");

    if ($tuoterow["myyntihinta_maara"] != 0) {
      echo " $tuoterow[myyntihinta_maara] $tuoterow[yksikko]";
    }

    echo "</th>";
    echo "<th>".t("Netto/Ovh")."</th>";
    echo "<th>".t("Ostohinta")." / ";

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
      if ($alepostfix > 1) echo " ";

      echo t("Ale{$alepostfix}");
    }

    echo "<th>".t("Kehahinta")."</th>";
    echo "<th>".t("Vihahinta")." ".tv1dateconv($tuoterow["vihapvm"])."</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>";
    foreach ($ttrow as $tt_rivi) {
      echo "{$tt_rivi["toim_tuoteno"]}<br>";
    }
    echo "</td>";
    echo "<td valign='top' align='right' style='font-weight:bold;'>".hintapyoristys($tuoterow["myyntihinta"])." $yhtiorow[valkoodi]$valuuttalisa</td>";
    echo "<td valign='top' align='right'>".hintapyoristys($tuoterow["nettohinta"])."/".hintapyoristys($tuoterow["myymalahinta"])."</td>";
    echo "<td valign='top' align='right'>";

    foreach ($ttrow as $tt_rivi) {

      $query = "SELECT *
                FROM valuu
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND nimi    = '{$tt_rivi['valuutta']}'
                ORDER BY tunnus DESC
                LIMIT 1";
      $kurssi_chk_res = pupe_query($query);
      $kurssi_chk_row = mysql_fetch_assoc($kurssi_chk_res);

      $_laskurow = array(
        'liitostunnus'   => $tt_rivi['liitostunnus'],
        'valkoodi'     => $tt_rivi['valuutta'],
        'ytunnus'     => $tt_rivi['ytunnus'],
        'vienti_kurssi' => $kurssi_chk_row['kurssi']
      );

      list($_hinta, $_netto, $_ale, $_valuutta) = alehinta_osto($_laskurow, $tuoterow, 1, '', '', array());
      echo "<span style='font-weight:bold;'>", hintapyoristys(hinta_kuluineen($tuoterow['tuoteno'], $_hinta)), " {$_valuutta}</span> / ";

      foreach ($_ale as $key => $val) {

        if (substr($key, 3, 1) > $yhtiorow['oston_alekentat']) continue;

        echo "{$val}% ";
      }

      echo "<br />";
    }
    echo "</td>";
    echo "<td valign='top' align='right' style='font-weight:bold;'>{$tuoterow['kehahin']}";

    if ($tuoterow["myyntihinta_maara"] != 0) {
      echo " $tuoterow[yksikko]<br>";
      echo hintapyoristys($tuoterow["kehahin"] * $tuoterow["myyntihinta_maara"], 6, TRUE);
      echo " $tuoterow[myyntihinta_maara] $tuoterow[yksikko]";
    }

    if ($alkuperainen_keskihankintahinta != $tuoterow["kehahin"]) {
      echo "<br>($alkuperainen_keskihankintahinta)";
    }

    echo "</td>";
    echo "<td valign='top' align='right' style='font-weight:bold;'>{$tuoterow['vihahin']}";

    if ($tuoterow["myyntihinta_maara"] != 0) {
      echo " $tuoterow[yksikko]<br>";
      echo hintapyoristys($tuoterow["vihahin"] * $tuoterow["myyntihinta_maara"], 6, TRUE);
      echo " $tuoterow[myyntihinta_maara] $tuoterow[yksikko]";
    }

    echo "</td>";
    echo "</tr>";

    //4
    echo "<tr>";
    echo "<th>".t("Hälyraja")." / ".t("Varastoitava")."</th>";
    echo "<th>".t("Ostoerä")."</th>";
    echo "<th>".t("Myyntierä")."</th>";
    echo "<th>".t("Kerroin")."</th>";
    echo "<th>".t("Tarrakerroin")."</th>";
    echo "<th>".t("Tarrakpl")."</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td valign='top' align='right'>$tuoterow[halytysraja] / $tuoterow[ei_varastoida]</td>";
    echo "<td valign='top' align='right'>";

    foreach ($ttrow as $tt_rivi) {
      if ($tt_rivi["osto_era"] == 0) {
        $tt_rivi["osto_era"] = 1.00;
      }
      echo "{$tt_rivi["osto_era"]}<br>";
    }
    echo "</td>";

    echo "<td valign='top' align='right'>$tuoterow[myynti_era]</td>";
    echo "<td valign='top' align='right'>";

    foreach ($ttrow as $tt_rivi) {
      echo "{$tt_rivi["tuotekerroin"]}<br>";
    }
    echo "</td>";
    echo "<td valign='top' align='right'>$tuoterow[tarrakerroin]</td>";
    echo "<td valign='top' align='right'>$tuoterow[tarrakpl]</td>";
    echo "</tr>";

    //5
    echo "<tr>";
    echo "<th>".t("Toimittajan toimitusaika")."</th>";
    echo "<th>".t("Tullinimike")." / %</th>";
    echo "<th colspan='3'>".t("Tullinimikkeen kuvaus")."</th>";
    echo "<th>".t("Toinen paljous")."</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>";
    foreach ($ttrow as $tt_rivi) {
      if (!empty($tt_rivi['toimitusaika'])) {
        echo $tt_rivi['toimitusaika']." ".t("pv")."<br />";
      }
    }
    echo "</td>";
    echo "<td>$tullirow1[cn] $prossat</td>";
    echo "<td colspan='3'>".wordwrap(substr($tullirow3['dm'], 0, 20)." - ".substr($tullirow2['dm'], 0, 20)." - ".substr($tullirow1['dm'], 0, 20), 70, "<br>")."</td>";
    echo "<td>$tullirow1[su]</td>";
    echo "</tr>";

    //6
    echo "<tr>";
    echo "<th>".t("Luontipvm")."</th>";
    echo "<th>".t("Muutospvm")."</th>";
    echo "<th>".t("Epäkurantti25pvm")."</th>";
    echo "<th>".t("Epäkurantti50pvm")."</th>";
    echo "<th>".t("Epäkurantti75pvm")."</th>";
    echo "<th>".t("Epäkurantti100pvm")."</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>".tv1dateconv($tuoterow["luonti"])."</td>";
    echo "<td>".tv1dateconv($tuoterow["muutos"])."</td>";
    echo "<td>".tv1dateconv($tuoterow["epakurantti25pvm"])."</td>";
    echo "<td>".tv1dateconv($tuoterow["epakurantti50pvm"])."</td>";
    echo "<td>".tv1dateconv($tuoterow["epakurantti75pvm"])."</td>";
    echo "<td>".tv1dateconv($tuoterow["epakurantti100pvm"])."</td>";
    echo "</tr>";

    //7
    echo "<tr>";
    echo "<th colspan='6'>".t("Tuotteen kuvaus")."</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='6'>".wordwrap($tuoterow["kuvaus"], 130, "<br>")."&nbsp;</td>";
    echo "</tr>";

    //8
    echo "<tr>";
    echo "<th>".t("Muuta")."</th>";
    echo "<th colspan='5'>".t("Lyhytkuvaus")."</th>";
    echo "</tr>";

    echo "<tr>";

    echo "<td>$tuoterow[muuta]&nbsp;</td>";
    echo "<td colspan='5'>";
    echo wordwrap($tuoterow["lyhytkuvaus"], 70, "<br>");

    $palautus = t_tuotteen_avainsanat($tuoterow, "laatuluokka");

    if (trim($palautus) != "") {

      echo $tuoterow["lyhytkuvaus"] != "" ? "<br>" : "";

      switch ($palautus) {
      case '0':
        echo "Premium";
        break;
      case '1':
        echo "Standard";
        break;
      case '2':
        echo "Economy";
        break;
      }
    }

    echo "</td>";
    echo "</tr>";

    //9
    echo "<tr>";
    echo "<th>".t("Korkeus")."</th>";
    echo "<th>".t("Leveys")."</th>";
    echo "<th>".t("Syvyys")."</th>";
    echo "<th>".t("Paino")."</th>";
    echo "<th>".t("Ostoehdotus")."</th>";
    echo "<th>".t("Tuotteen lisätiedot")."</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>$tuoterow[tuotekorkeus] m</td>";
    echo "<td>$tuoterow[tuoteleveys] m</td>";
    echo "<td>$tuoterow[tuotesyvyys] m</td>";
    echo "<td>$tuoterow[tuotemassa] kg</td>";
    echo "<td>$tuoterow[ostoehdotus]</td>";
    echo "<td>";

    $lisatiedot = tuotteen_lisatiedot($tuoterow["tuoteno"]);

    if (count($lisatiedot) > 0) {
      echo "<ul>";

      foreach ($lisatiedot as $lisatieto) {
        echo "<li>{$lisatieto["kentta"]} &raquo; ".url_or_text($lisatieto["selite"])."</li>";
      }

      echo "</ul>";
    }

    echo "</td>";
    echo "</tr>";

    echo "</table><br>";

    if (count($ttrow) > 0) {

      $otsikot = FALSE;

      foreach ($ttrow as $tt_rivi) {
        $query = "SELECT ttt.*, TRIM(CONCAT(toimi.nimi, ' ', toimi.nimitark)) AS nimi
                  FROM tuotteen_toimittajat_tuotenumerot AS ttt
                  JOIN tuotteen_toimittajat AS tt ON (tt.yhtio = ttt.yhtio AND tt.tunnus = ttt.toim_tuoteno_tunnus AND tt.toim_tuoteno = '{$tt_rivi['toim_tuoteno']}' AND tt.toim_tuoteno != '')
                  JOIN toimi ON (toimi.yhtio = tt.yhtio AND toimi.tunnus = tt.liitostunnus)
                  WHERE ttt.yhtio = '{$kukarow['yhtio']}'";
        $chk_res = pupe_query($query);

        if (mysql_num_rows($chk_res) > 0 and !$otsikot) {
          echo "<font class='message'>", t("Tuotteen toimittajan vaihtoehtoiset tuotenumerot"), "</font><hr />";
          echo "<table>";
          echo "<tr>";
          echo "<th>", t("Toimittaja"), "</th>";
          echo "<th>", t("Tuoteno"), "</th>";
          echo "<th>", t("Viivakoodi"), "</th>";
          echo "</tr>";

          $otsikot = TRUE;
        }

        while ($chk_row = mysql_fetch_assoc($chk_res)) {
          echo "<tr>";
          echo "<td>{$chk_row['nimi']}</td>";
          echo "<td>{$chk_row['tuoteno']}</td>";
          echo "<td>{$chk_row['viivakoodi']}</td>";
          echo "</tr>";
        }
      }

      if ($otsikot) echo "</table><br />";
    }

    // Onko liitetiedostoja
    $liitteet = liite_popup("TN", $tuoterow["tunnus"]);

    if ($liitteet != "") {
      echo "<font class='message'>".t("Liitetiedostot")."</font><hr>";
      echo "$liitteet<br><br>";
    }

    // aika karseeta, mutta katotaan voidaanko tällästä optiota näyttää yks tosi firma specific juttu
    if (table_exists("yhteensopivuus_tuote") and file_exists("yhteensopivuus_tuote.php") and tarkista_oikeus('yhteensopivuus_tuote.php')) {

      $lisa = " and tuoteno = '$tuoteno' ";

      $query = "SELECT isatuoteno
                FROM tuoteperhe
                WHERE yhtio = '$kukarow[yhtio]'
                AND tuoteno = '$tuoteno'";
      $tuoteperhe_result = pupe_query($query);

      if (mysql_num_rows($tuoteperhe_result) > 0) {
        $lisa = " and tuoteno in ('$tuoteno',";
      }

      while ($tuoteperhe_row = mysql_fetch_assoc($tuoteperhe_result)) {
        $lisa .= "'$tuoteperhe_row[isatuoteno]',";
      }

      if (mysql_num_rows($tuoteperhe_result) > 0) {
        $lisa = substr($lisa, 0, -1);
        $lisa .= ") ";
      }

      $query = "SELECT tyyppi, count(*) countti
                from yhteensopivuus_tuote
                where yhtio = '$kukarow[yhtio]'
                $lisa
                GROUP BY 1
                HAVING countti > 0";
      $yhtresult = pupe_query($query);

      if (mysql_num_rows($yhtresult) > 0) {
        while ($yhtrow = mysql_fetch_assoc($yhtresult)) {
          if ($yhtrow["tyyppi"] == "HA") $yhttoim = "";
          else $yhttoim = $yhtrow["tyyppi"];

          echo "<form action='yhteensopivuus_tuote.php' method='post'>";
          echo "<input type='hidden' name='tee' value='etsi'>";
          echo "<input type='hidden' name='lopetus' value='$tkysy_lopetus'>";
          echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
          echo "<input type='hidden' name='toim' value='$yhttoim'>";
          echo "<input type='submit' value='".t("Siirry tuotteen $yhttoim yhteensopivuuksiin")."'>";
          echo "</form>";
        }
      }
      echo "<br>";
    }

    $_tp_kasittely = ($yhtiorow['toimipaikkakasittely'] == "L");

    echo "<input type='hidden' id='_tp_kasittely' value='{$_tp_kasittely}' />";

    // Saldot, korvaavat ja vastaavat
    echo "<table><tr><td class='back pnopad ptop'>";

    if ($tuoterow["ei_saldoa"] == '') {

      // Varastosaldot ja paikat
      echo "<font class='message'>".t("Varastopaikat")."</font>";

      if (tarkista_oikeus('muuvarastopaikka.php', '', 1)) {
        echo "&nbsp;&nbsp;<a href='{$palvelin2}muuvarastopaikka.php?tee=M&tuoteno=".urlencode($tuoterow["tuoteno"])."&lopetus=$tkysy_lopetus'>";

        if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
          echo "<img style='height:15px;' src='{$palvelin2}pics/facelift/jakoavain.png' alt='", t("Muokkaa"), "' title='", t("Muuta tuotepaikkoja"), "' />";
        }
        else {
          echo "<img style='height:10px;' src='{$palvelin2}pics/lullacons/document-properties.png' alt='", t("Muokkaa"), "' title='", t("Muuta tuotepaikkoja"), "' />";
        }

        echo "</a>";
      }
      elseif (tarkista_oikeus('muuvarastopaikka.php', 'OLETUSVARASTO', 1)) {
        echo "&nbsp;&nbsp;<a href='{$palvelin2}muuvarastopaikka.php?toim=OLETUSVARASTO&tee=M&tuoteno=".urlencode($tuoterow["tuoteno"])."&lopetus=$tkysy_lopetus'>";

        if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
          echo "<img style='height:15px;' src='{$palvelin2}pics/facelift/jakoavain.png' alt='", t("Muokkaa"), "' title='", t("Muuta tuotepaikkoja"), "' />";
        }
        else {
          echo "<img style='height:10px;' src='{$palvelin2}pics/lullacons/document-properties.png' alt='", t("Muokkaa"), "' title='", t("Muuta tuotepaikkoja"), "' />";
        }

        echo "</a>";
      }

      if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
        echo "&nbsp;&nbsp;<a id='varastopaikat'><img id='varastopaikat_img' style='height: 20px;' src='{$palvelin2}pics/facelift/nuolet_alas.png' /></a>";
      }
      else {
        echo "&nbsp;&nbsp;<input type='button' id='varastopaikat' value='", t("Näytä"), "' />";
      }

      echo "<hr>";

      echo "<div id='varastopaikat_container'>";
      echo "</div>";
    }

    echo "</td><td class='back pnopad ptop'>";

    // Korvaavat tuotteet
    $korvaavat = new Korvaavat($tuoteno);

    if (count($korvaavat->tuotteet()) > 0) {

      // Varastosaldot ja paikat
      echo "<font class='message'>".t("Korvaavat tuotteet")."</font>";

      if (tarkista_oikeus('korvaavat.php', '', 1)) {
        echo "&nbsp;&nbsp;<a href='{$palvelin2}korvaavat.php?tuoteno=".urlencode($tuoterow["tuoteno"])."&lopetus=$tkysy_lopetus'>";

        if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
          echo "<img style='height:15px;' src='{$palvelin2}pics/facelift/jakoavain.png' alt='", t("Muokkaa"), "' title='", t("Muuta korvaavuusketjuja"), "' />";
        }
        else {
          echo "<img style='height:10px;' src='{$palvelin2}pics/lullacons/document-properties.png' alt='", t("Muokkaa"), "' title='", t("Muuta korvaavuusketjuja"), "' />";
        }

        echo "</a>";
        echo "&nbsp;&nbsp;";
      }

      echo "<hr>";

      echo "<div id='korvaavat_container'>";

      if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
        echo "&nbsp;&nbsp;<a id='korvaavat'><img id='korvaavat_img' style='height: 20px;' src='{$palvelin2}pics/facelift/nuolet_alas.png' /></a>";
      }
      else {
        echo "&nbsp;&nbsp;<input type='button' id='korvaavat' value='", t("Näytä"), "' />";
      }

      echo "</div>";
    }

    echo "</td><td class='back pnopad ptop'>";

    // Vastaavat tuotteet
    $vastaavat = new Vastaavat($tuoteno);

    // Jos tuote kuulu useampaan kuin yhteen vastaavuusketjuun
    if ($vastaavat->onkovastaavia()) {
      echo "<font class='message'>".t("Vastaavat tuotteet")."</font>";

      if (tarkista_oikeus('vastaavat.php', '', 1)) {
        echo "&nbsp;&nbsp;<a href='{$palvelin2}vastaavat.php?tuoteno=".urlencode($tuoterow["tuoteno"])."&lopetus=$tkysy_lopetus'>";

        if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
          echo "<img style='height:15px;' src='{$palvelin2}pics/facelift/jakoavain.png' alt='", t("Muokkaa"), "' title='", t("Muuta vastaavuusvuusketjuja"), "' />";
        }
        else {
          echo "<img style='height:10px;' src='{$palvelin2}pics/lullacons/document-properties.png' alt='", t("Muokkaa"), "' title='", t("Muuta vastaavuusvuusketjuja"), "' />";
        }

        echo "</a>";
      }

      echo "<hr>";

      echo "<div id='vastaavat_container'>";

      if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
        echo "&nbsp;&nbsp;<a id='vastaavat'><img id='vastaavat_img' style='height: 20px;' src='{$palvelin2}pics/facelift/nuolet_alas.png' /></a>";
      }
      else {
        echo "&nbsp;&nbsp;<input type='button' id='vastaavat' value='", t("Näytä"), "' />";
      }

      echo "</div>";
    }

    echo "</td><td class='back pnopad ptop'>";

    //Tuotemuutoksia halutaan näyttää, mikäli niitä on.
    $lista = hae_tuotemuutokset($tuoteno);

    if (count($lista) > 0) {
      // Tuotemuutoksia.
      echo "<font class='message'>".t("Tuotenumeromuutoksia")."</font><hr>";

      echo "<table>";
      echo "<tr>";
      echo "<th>".t("Vanha tuotenumero")."</th>";
      echo "<th>".t("Muutospvm")."</th>";
      echo "<th>".t("Muuttaja")."</th>";
      echo "</tr>";

      foreach ($lista as $muuttunut_tuote) {
        echo "<tr>";
        echo "<td>{$muuttunut_tuote["tuoteno"]}</td>";
        echo "<td>".tv1dateconv($muuttunut_tuote['muutospvm'], 'X')."</td>";
        echo "<td>{$muuttunut_tuote["kuka"]}</td>";
        echo "</tr>";
      }
      echo "</table>";
    }

    echo "</td></tr></table><br>";

    if ($onkolaajattoimipaikat) {

      $sel = '';
      if ("{$toimipaikka}" != 'kaikki' and ($toimipaikka == 0 or $kukarow['toimipaikka'] == 0)) {
        $sel = 'selected';
      }

      echo "<br /><hr />";
      echo "<a href='#' name='RajaaToimipaikalla'></a>";
      echo "<font class='message'>", t("Rajaa toimipaikalla"), "</font>&nbsp;";
      echo "<form action='{$PHP_SELF}#RajaaToimipaikalla' method='post'>
      <input type='hidden' name='toim' value='{$toim}'>
      <input type='hidden' name='lopetus' value='{$lopetus}'>
      <input type='hidden' name='tuoteno' value='{$tuoteno}'>
      <input type='hidden' name='tee' value='Z'>
      <input type='hidden' name='historia' value='{$historia}'>
      <input type='hidden' name='tapahtumalaji' value='{$tapahtumalaji}'>
      <input type='hidden' name='raportti' value='{$raportti}' />
      <input type='hidden' name='toim_kutsu' value='{$toim_kutsu}'>";

      echo "<select id='toimipaikka' name='toimipaikka'>";
      echo "<option value='kaikki'>", t("Kaikki toimipaikat"), "</option>";
      echo "<option value='0' {$sel}>", t("Ei toimipaikkaa"), "</option>";

      while ($toimipaikat_row = mysql_fetch_assoc($toimipaikat_res)) {
        $sel = $toimipaikat_row['tunnus'] == $toimipaikka ? "selected" : "";
        echo "<option value='{$toimipaikat_row['tunnus']}' {$sel}>{$toimipaikat_row['nimi']}</option>";
      }

      echo "</select>";
      echo "</form>";
      echo " <font class='message'>(", t("tilaukset, raportointi ja tapahtumat"), ")</font>";
      echo "<hr /><br />";
    }

    // Varastosaldot ja paikat
    echo "<font class='message'>".t("Tuotteen tilaukset")."</font>";

    if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
      echo "&nbsp;&nbsp;<a id='tuotteen_tilaukset'><img id='tuotteen_tilaukset_img' style='height: 20px;' src='{$palvelin2}pics/facelift/nuolet_alas.png' /></a>";
    }
    else {
      echo "&nbsp;&nbsp;<input type='button' id='tuotteen_tilaukset' value='", t("Näytä"), "' />";
    }

    echo "<input type='hidden' id='tuoteno' value='{$tuoteno}' />";
    echo "<input type='hidden' id='yksikko' value='{$tuoterow['yksikko']}' />";
    echo "<input type='hidden' id='sarjanumeroseuranta' value='{$tuoterow['sarjanumeroseuranta']}' />";
    echo "<input type='hidden' id='ei_saldoa' value='{$tuoterow['ei_saldoa']}' />";
    echo "<input type='hidden' id='kehahin' value='{$tuoterow['kehahin']}' />";

    echo "<hr />";
    echo "<div id='tuotteen_tilaukset_container'>";
    echo "</div>";
    echo "<br />";

    if ($toim != "TYOMAARAYS_ASENTAJA") {

      if ($raportti == "") {
        if ($tuoterow["tuotetyyppi"] == "R") $raportti = "KULUTUS";
        else $raportti = "MYYNTI";
      }

      $sele = array(
        "K" => ($raportti == "KULUTUS") ? "checked" : "",
        "M" => ($raportti != "KULUTUS") ? "checked" : "",
      );

      echo "<form action='$PHP_SELF#Raportit' method='post'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='tuoteno' value='$tuoteno'>
        <input type='hidden' name='tee' value='Z'>
        <input type='hidden' name='historia' value='$historia'>
        <input type='hidden' name='tapahtumalaji' value='$tapahtumalaji'>
        <input type='hidden' name='toim_kutsu' value='$toim_kutsu'>
        <input type='hidden' name='toimipaikka' value='{$toimipaikka}' />
        <font class='message'>".t("Raportointi")."</font><a href='#' name='Raportit'></a>
        (<input type='radio' class='raportti_tyyppi' name='raportti' value='MYYNTI' $sele[M]> ".t("Myynnistä")." /
        <input type='radio' class='raportti_tyyppi' name='raportti' value='KULUTUS' $sele[K]> ".t("Kulutuksesta").")";

      if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
        echo "&nbsp;&nbsp;<a id='raportointi'><img id='raportointi_img' style='height: 20px;' src='{$palvelin2}pics/facelift/nuolet_alas.png' /></a>";
      }
      else {
        echo "&nbsp;&nbsp;<input type='button' id='raportointi' value='", t("Näytä"), "' />";
      }

      echo "</form><hr>";

      echo "<div id='raportointi_container'>";
      echo "</div>";
      echo "<br />";
    }

    if ($tuoterow["sarjanumeroseuranta"] == "S" or $tuoterow["sarjanumeroseuranta"] == "V" or $tuoterow['sarjanumeroseuranta'] == 'T') {

      $query  = "SELECT sarjanumeroseuranta.*, sarjanumeroseuranta.tunnus sarjatunnus,
                 tilausrivi_osto.tunnus osto_rivitunnus,
                 tilausrivi_osto.perheid2 osto_perheid2,
                 tilausrivi_osto.nimitys nimitys,
                 lasku_myynti.nimi myynimi
                 FROM sarjanumeroseuranta
                 LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
                 LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                 LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.uusiotunnus
                 LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
                 WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
                 and sarjanumeroseuranta.tuoteno           = '$tuoterow[tuoteno]'
                 and sarjanumeroseuranta.myyntirivitunnus != -1
                 and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
                 and tilausrivi_osto.laskutettuaika       != '0000-00-00'";
      $sarjares = pupe_query($query);

      if (mysql_num_rows($sarjares) > 0) {
        echo "<font class='message'>".t("Sarjanumerot")."</font><hr>";

        echo "<table>";
        echo "<tr><th>".t("Nimitys")."</th>";
        echo "<th>".t("Sarjanumero")."</th>";
        echo "<th>".t("Varastopaikka")."</th>";
        echo "<th>".t("Ostohinta")."</th>";
        echo "<th>".t("Varattu asiakaalle")."</th></tr>";

        while ($sarjarow = mysql_fetch_assoc($sarjares)) {

          $fnlina1 = "";

          if (($sarjarow["siirtorivitunnus"] > 0) or ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"])) {

            if ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"]) {
              $ztun = $sarjarow["osto_perheid2"];
            }
            else {
              $ztun = $sarjarow["siirtorivitunnus"];
            }

            $query = "SELECT tilausrivi.tunnus, tilausrivi.tuoteno, sarjanumeroseuranta.sarjanumero, tyyppi, otunnus
                      FROM tilausrivi
                      LEFT JOIN sarjanumeroseuranta ON (tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus)
                      WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.tunnus='$ztun'";
            $siires = pupe_query($query);
            $siirow = mysql_fetch_assoc($siires);

            if ($siirow["tyyppi"] == "O") {
              // pultattu kiinni johonkin
              $fnlina1 = " <font class='message'>(".t("Varattu lisävarusteena").": $siirow[tuoteno] <a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($siirow["tuoteno"])."&sarjanumero_haku=".urlencode($siirow["sarjanumero"])."'>$siirow[sarjanumero]</a>)</font>";
            }
            elseif ($siirow["tyyppi"] == "G") {
              // jos tämä on jollain siirtolistalla
              $fnlina1 = " <font class='message'>(".t("Kesken siirtolistalla").": $siirow[otunnus])</font>";
            }
          }

          echo "<tr>
              <td>$sarjarow[nimitys]</td>
              <td><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($tuoterow["tuoteno"])."&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."'>$sarjarow[sarjanumero]</a></td>
              <td>$sarjarow[hyllyalue] $sarjarow[hyllynro] $sarjarow[hyllyvali] $sarjarow[hyllytaso]</td>
              <td align='right'>";
          if ($tuoterow['sarjanumeroseuranta'] == 'V' or $tuoterow['sarjanumeroseuranta'] == 'T') {
            echo sprintf('%.2f', $tuoterow['kehahin']);
          }
          else {
            echo sprintf('%.2f', hinta_kuluineen($tuoterow['tuoteno'], sarjanumeron_ostohinta("tunnus", $sarjarow["sarjatunnus"])));
          }
          echo "</td>
              <td>$sarjarow[myynimi] $fnlina1</td></tr>";
        }

        echo "</table><br>";
      }
    }
    elseif ($tuoterow["sarjanumeroseuranta"] == "E" or $tuoterow["sarjanumeroseuranta"] == "F" or $tuoterow["sarjanumeroseuranta"] == "G") {

      $query  = "SELECT sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.parasta_ennen, sarjanumeroseuranta.lisatieto,
                 sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso,
                 sarjanumeroseuranta.era_kpl kpl,
                 sarjanumeroseuranta.tunnus sarjatunnus
                 FROM sarjanumeroseuranta
                 LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
                 WHERE sarjanumeroseuranta.yhtio           = '$kukarow[yhtio]'
                 and sarjanumeroseuranta.tuoteno           = '$tuoterow[tuoteno]'
                 and sarjanumeroseuranta.myyntirivitunnus  = 0
                 and sarjanumeroseuranta.era_kpl          != 0
                 and tilausrivi_osto.laskutettuaika       != '0000-00-00'";
      $sarjares = pupe_query($query);

      if (mysql_num_rows($sarjares) > 0) {
        echo "<font class='message'>".t("Eränumerot")."</font><hr>";

        echo "<table>";
        if ($tuoterow["sarjanumeroseuranta"] == "F") {
          echo "<tr><th colspan='4'>".t("Varasto").":</th></tr>";
        }
        elseif ($tuoterow["sarjanumeroseuranta"] == "G") {
          echo "<tr><th colspan='5'>".t("Varasto").":</th></tr>";
        }
        else {
          echo "<tr><th colspan='3'>".t("Varasto").":</th></tr>";
        }
        echo "<th>".t("Eränumero")."</th>";

        if ($tuoterow["sarjanumeroseuranta"] == "F") {
          echo "<th>".t("Parasta ennen")."</th>";
        }

        echo "<th>".t("Määrä")."</th>";
        if ($tuoterow['sarjanumeroseuranta'] == 'G') {
          echo "<th>", t("Ostohinta"), "</th>";
        }
        echo "<th>".t("Lisätieto")."</th></tr>";

        while ($sarjarow = mysql_fetch_assoc($sarjares)) {
          echo "<tr>
              <td><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($tuoterow["tuoteno"])."&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."'>$sarjarow[sarjanumero]</a></td>";

          if ($tuoterow["sarjanumeroseuranta"] == "F") {
            echo "<td>".tv1dateconv($sarjarow["parasta_ennen"])."</td>";
          }

          echo "<td align='right'>$sarjarow[kpl]</td>";
          if ($tuoterow['sarjanumeroseuranta'] == 'G') {
            echo "<td align='right'>".sprintf('%.2f', sarjanumeron_ostohinta("tunnus", $sarjarow["sarjatunnus"]))."</td>";
          }
          echo "<td>$sarjarow[lisatieto]</td>";

          //  Katsotaan jos meidän pitäisi liittää jotain infoa lisätiedoista
          if (file_exists("inc/generoi_sarjanumeron_info.inc")) {
            require "inc/generoi_sarjanumeron_info.inc";
            $sarjainfo = generoi_sarjanumeron_info($sarjarow["sarjanumero"]);
            if ($sarjainfo!="") {
              echo "<td class='back'>$sarjainfo</td>";
            }
          }

          echo "</tr>";
        }

        echo "</table><br>";
      }
    }

    if ($toim != "TYOMAARAYS_ASENTAJA") {
      // Varastotapahtumat
      echo "<font class='message'>".t("Tuotteen tapahtumat")."</font>";

      echo "<input type='hidden' id='kokonaissaldo_tapahtumalle' value='' />";

      echo "<form action='$PHP_SELF#Tapahtumat' method='post'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='lopetus' value='$lopetus'>";
      echo "<input type='hidden' name='tee' value='Z'>";
      echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
      echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
      echo "<input type='hidden' name='raportti' value='$raportti'>";
      echo "&nbsp;&nbsp;<a href='#' name='Tapahtumat'>";

      if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
        echo "<img style='height:20px;' src='{$palvelin2}pics/facelift/nuolet_ylos.png' />";
      }
      else {
        echo "<img src='pics/lullacons/arrow-double-up-green.png' />";
      }

      echo "</a>";

      if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
        echo "&nbsp;&nbsp;<a id='tapahtumat'><img id='tapahtumat_img' style='height: 20px;' src='{$palvelin2}pics/facelift/nuolet_alas.png' /></a>";
      }
      else {
        echo "&nbsp;&nbsp;<input type='button' id='tapahtumat' value='", t("Näytä"), "' />";
      }

      echo "<hr />";
      echo "<table>";

      if ($historia == "") $historia=1;
      $chk[$historia] = "SELECTED";

      echo "<tr>";
      echo "<th colspan='5'>".t("Näytä tapahtumat").": ";
      echo "<select id='historia' name='historia'>'";
      echo "<option value='1' $chk[1]> ".t("20 viimeisintä")."</option>";

      $query = "SELECT * FROM tilikaudet WHERE yhtio = '$kukarow[yhtio]' ORDER BY tilikausi_loppu DESC";
      $tkresult = pupe_query($query);

      while ($tkrow = mysql_fetch_assoc($tkresult)) {
        $tkchk = "";
        if ($historia == "TK".$tkrow["tunnus"]) {
          $tkchk = "SELECTED";
        }
        echo "<option value='TK".$tkrow["tunnus"]."' $tkchk> ".t("Tilikausi")." ".$tkrow["tilikausi_alku"]." --> ".$tkrow["tilikausi_loppu"]."</option>";
      }

      echo "<option value='4' $chk[4]> ".t("Kaikki tapahtumat")."</option>";
      echo "</select>";

      if ($tapahtumalaji == "laskutus")        $sel1="SELECTED";
      if ($tapahtumalaji == "tulo")            $sel2="SELECTED";
      if ($tapahtumalaji == "valmistus")       $sel3="SELECTED";
      if ($tapahtumalaji == "siirto")          $sel4="SELECTED";
      if ($tapahtumalaji == "kulutus")         $sel5="SELECTED";
      if ($tapahtumalaji == "Inventointi")     $sel6="SELECTED";
      if ($tapahtumalaji == "Epäkurantti")     $sel7="SELECTED";
      if ($tapahtumalaji == "poistettupaikka") $sel8="SELECTED";
      if ($tapahtumalaji == "uusipaikka")      $sel9="SELECTED";

      $colspan = 5;

      if ($tilalehinta != '') {
        $colspan++;
      }
      if ($myyntilaskun_myyja != '') {
        $colspan++;
      }

      echo "</th><th id='tapahtumalaji_header' colspan='{$colspan}'>".t("Tapahtumalaji").": ";
      echo "<select id='tapahtumalaji' name='tapahtumalaji'>'";
      echo "<option value=''>".t("Näytä kaikki")."</option>";
      echo "<option value='laskutus' $sel1>".t("Laskutukset")."</option>";
      echo "<option value='tulo' $sel2>".t("Tulot")."</option>";
      echo "<option value='valmistus' $sel3>".t("Valmistukset")."</option>";
      echo "<option value='siirto' $sel4>".t("Siirrot")."</option>";
      echo "<option value='kulutus' $sel5>".t("Kulutukset")."</option>";
      echo "<option value='Inventointi' $sel6>".t("Inventoinnit")."</option>";
      echo "<option value='Epäkurantti' $sel7>".t("Epäkuranttiusmerkinnät")."</option>";
      echo "<option value='poistettupaikka' $sel8>".t("Poistetut tuotepaikat")."</option>";
      echo "<option value='uusipaikka' $sel9>".t("Perustetut tuotepaikat")."</option>";
      echo "</select>";
      echo "</th>";

      $check_hinta = $tilalehinta != '' ? "checked" : '';
      $check_myyja = $myyntilaskun_myyja != '' ? 'checked' : '';

      echo "<th>";
      echo t("Näytä tilausrivin hinta ja ale").": <input type='checkbox' name='tilalehinta' id='tilalehinta' {$check_hinta} />";
      echo "<br />";
      echo t("Näytä myyntilaskun myyjä").": <input type='checkbox' name='myyntilaskun_myyja' id='myyntilaskun_myyja' {$check_myyja} />";
      echo "</th>";

      echo "</tr>";

      echo "<tr id='tapahtumat_header'>";
      echo "<th>".t("Laatija")."</th>";

      $dsp = "display: none;";

      if ($myyntilaskun_myyja != '') {
        $dsp = "";
      }

      echo "<th id='myyntilaskun_myyja_header' style='{$dsp}'>".t("Myyjä")."</th>";
      echo "<th>".t("Pvm")."</th>";
      echo "<th>".t("Tyyppi")."</th>";
      echo "<th>".t("Määrä")."</th>";
      echo "<th>".t("Kplhinta")."</th>";
      echo "<th>".t("Kehahinta")."</th>";
      echo "<th>".t("Kate")."</th>";
      echo "<th>".t("Arvo")."</th>";
      echo "<th>".t("Var.Arvo")."</th>";
      echo "<th>".t("Var.Saldo")."</th>";
      echo "<th id='tilalehinta_hearder' style='display: none;'>";
      echo t("Hinta / Ale / Rivihinta");
      echo "</th>";
      echo "<th>".t("Selite");

      echo "</th></form>";
      echo "</tr>";

      //tapahtumat
      echo "<tbody id='tapahtumat_container'></tbody>";
      echo "</table>";
    }

    echo "<br /><br />";
    echo $divit;
  }
  else {
    echo "<font class='message'>".t("Yhtään tuotetta ei löytynyt")."!<br></font>";
  }
  $tee = '';
}

if ($ulos != "") {

  echo "<form method='post'>";
  echo "<input type='hidden' name='toim' value='{$toim}'>";
  echo "<input type='hidden' name='lopetus' value='{$lopetus}'>";
  echo "<input type='hidden' name='tuoteno' value='{$tuoteno}'>";
  echo "<input type='hidden' name='tee' value='Z'>";
  echo "<table><tr>";
  echo "<td class='back'>";
  $chk = !empty($poistuvat_tuotteet) ? 'checked' : '';
  echo "<input type='checkbox' name='poistuvat_tuotteet' {$chk} onchange='submit();' /> ";
  echo t("Älä näytä listauksessa poistuvia, poistettuja, saldottomia ja varastoimattomia tuotteita");
  echo "</td>";
  echo "</tr></table>";
  echo "</form>";

  echo "<form method='post' autocomplete='off'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<input type='hidden' name='tee' value='Z'>";
  echo "<table><tr>";
  echo "<th>".t("Valitse listasta").":</th>";
  echo "<td>$ulos</td>";
  echo "<td class='back'><input type='submit' value='".t("Valitse")."'></td>";
  echo "</tr></table>";
  echo "</form>";
}

require "inc/footer.inc";
