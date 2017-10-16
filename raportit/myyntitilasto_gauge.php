<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

require "../inc/parametrit.inc";

ini_set("memory_limit", "5G");

if (!isset($toim))   $toim = "";
if (!isset($naytetaan_tulos)) $naytetaan_tulos = '';

if ($toim != "") {

  if (!isset($naytetaan_luvut)) $naytetaan_luvut = 'eurolleen';

  $query = "SELECT selitetark, selitetark_2, selitetark_3
            FROM avainsana
            WHERE yhtio = '$kukarow[yhtio]'
            and laji = 'MYYNTITILASTO'
            and selite = '$toim'";
  $al_res = pupe_query($query);
  $al_row = mysql_fetch_assoc($al_res);

  $tilatut_eurot_params = $al_row['selitetark'];
  $toimiteut_rivit_params = $al_row['selitetark_2'];
  $tilatut_katepros = $al_row['selitetark_3'];
}
else {

  if (!isset($naytetaan_luvut)) $naytetaan_luvut = '';

  $tilatut_eurot_params = " min: 0,
                            max: 400000,
                            redFrom: 200000,
                            redTo: 300000,
                            yellowFrom: 300000,
                            yellowTo: 350000,
                            greenFrom: 350000,
                            greenTo: 400000,
                            minorTicks: 5,
                            majorTicks: [0, 50, 100, 150, 200, 250, 300, 350, 400]";

  $toimiteut_rivit_params = " min: 0,
                              max: 8000,
                              redFrom: 4000,
                              redTo: 6000,
                              yellowFrom: 6000,
                              yellowTo: 7000,
                              greenFrom: 7000,
                              greenTo: 8000,
                              minorTicks: 5,
                              majorTicks: [0, 1, 2, 3, 4, 5, 6, 7, 8]";

  $tilatut_katepros = " min: 0,
                        max: 50,
                        redFrom: 25,
                        redTo: 30,
                        yellowFrom: 30,
                        yellowTo: 40,
                        greenFrom: 40,
                        greenTo: 50,
                        minorTicks: 2,
                        majorTicks: [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50]";
}

gauge();

echo "  <script type='text/javascript' charset='utf-8'>

      $(document).ready(function() {

        $('td.toggleable, th.toggleable').toggle(
          function() {
            var id = $(this).attr('id');
            var child = $('.'+id);

            if (!$(child).is(':visible')) {
              $('#img_'+id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-down.png');
              child.show();
            }
            else {
              $('#img_'+id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-right.png');
              child.hide();
            }
          },
          function() {
            var id = $(this).attr('id');
            var child = $('.'+id);

            if (!$(child).is(':visible')) {
              $('#img_'+id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-down.png');
              child.show();
            }
            else {

              if ($(child).hasClass('osasto')) {
                $('tr.try:visible').hide();
              }

              if ($(child).hasClass('kustp')) {
                $('tr.try:visible').hide();
                $('tr.osasto:visible').hide();
              }

              $('#img_'+id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-right.png');
              child.hide();
            }
          }
        );

        setTimeout(function() {

          var gauge = new Gauge();
          var args = {
            tilatut: ['".$yhtiorow["valkoodi"]."', 0]
          }

          var options = {  forceIFrame: false,
                  width: 800,
                  height: 220,
                  $tilatut_eurot_params,
                  animation: {
                    easing: 'out',
                    duration: 4000
                  }};

          gauge.init(args, options);

          draw_options = {
            max: options.max,
            type: 'custom_parseint'
          }

          if (!isNaN($('#tilatut_eurot').val()) && $('#tilatut_eurot').val() != '') gauge.draw($('#tilatut_eurot').val(), draw_options);

          var gauge = new Gauge();
          var args = {
            kate: ['Rivit', 0]
          }

          var options = {  forceIFrame: false,
                  width: 800,
                  height: 220,
                  $toimiteut_rivit_params,
                  animation: {
                    easing: 'out',
                    duration: 4000
                  }};

          gauge.init(args, options);

          draw_options = {
            max: options.max,
            type: 'custom_parseint'
          }

          if (!isNaN($('#toimitetut_rivit').val()) && $('#toimitetut_rivit').val() != '') gauge.draw($('#toimitetut_rivit').val(), draw_options);

          var gauge = new Gauge();
          var args = {
            katepros: ['Kate%', 0]
          }

          var options = {  forceIFrame: false,
                  width: 800,
                  height: 220,
                  $tilatut_katepros,
                  animation: {
                    easing: 'out',
                    duration: 4000
                  }};

          gauge.init(args, options);

          draw_options = {
            max: options.max,
            type: 'custom_parsefloat'
          }

          if (!isNaN($('#tilatut_katepros').val()) && $('#tilatut_katepros').val() != '') gauge.draw($('#tilatut_katepros').val(), draw_options);
        }, 1);

        $('#naytetaan_tulos').change(function() {
          var date = new Date();

          if ($(this).val() == 'weekly' || $(this).val() == 'monthly') {
            $('#kka').val(1);
          }
          else {
            $('#kka').val(date.getMonth()+1);
          }

          $('#ppa').val(1);
          $('#vva').val(date.getFullYear());

          $('#ppl').val(date.getDate());
          $('#kkl').val(date.getMonth()+1);
          $('#vvl').val(date.getFullYear());
        });

      });
    </script>";

echo "<font class='head'>", t("Myyntitilasto"), "</font><hr>";

echo "<form method='post'>";
echo "<input type='hidden' name='toim' value='$toim'>";
echo "<table><tr>";
echo "<td class='back'><div id='chart_div'></div></td>";
echo "</tr><tr>";
echo "<td class='back'>";

$query_ale_lisa = generoi_alekentta('M');

$alku = date("Y-m-d")." 00:00:00";
$lopu = date("Y-m-d")." 23:59:59";

$query = "SELECT
          round(sum(if(tilausrivi.laskutettu!='',tilausrivi.rivihinta,(tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1))), 0) AS 'tilatut_eurot',
          round(sum(if(tilausrivi.laskutettu!='', tilausrivi.kate, (tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)-(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt)))), 0) AS 'tilatut_kate',
          sum(if(tilausrivi.toimitettu!='', 1, 0)) AS 'toimitetut_rivit'
          FROM tilausrivi
          JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.myynninseuranta = '')
          JOIN lasku on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
          JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and asiakas.myynninseuranta = '')
          WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
          AND tilausrivi.tyyppi   = 'L'
          AND tilausrivi.laadittu >= '$alku'
          AND tilausrivi.laadittu <= '$lopu'";
$result = pupe_query($query);
$row = mysql_fetch_assoc($result);

echo "<input type='hidden' id='tilatut_eurot' value='{$row['tilatut_eurot']}' />";
echo "<input type='hidden' id='toimitetut_rivit' value='{$row['toimitetut_rivit']}' />";
echo "<input type='hidden' id='tilatut_katepros' value='", ($row['tilatut_eurot'] != 0 ? round($row['tilatut_kate'] / $row['tilatut_eurot'] * 100, 1) : 0), "' />";
echo "<input type='hidden' name='tee' value='laske' />";

if (!isset($kka)) $kka = date("n", mktime(0, 0, 0, date("n"), 1, date("Y")));
if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("n"), 1, date("Y")));
if (!isset($ppa)) $ppa = date("j", mktime(0, 0, 0, date("n"), 1, date("Y")));

if (!isset($kkl)) $kkl = date("n");
if (!isset($vvl)) $vvl = date("Y");
if (!isset($ppl)) $ppl = date("j");

echo "<table>";
echo "<tr>";
echo "<th>", t("Syˆt‰ alkup‰iv‰m‰‰r‰"), " (", t("pp-kk-vvvv"), ")</th>";
echo "<td><input type='text' name='ppa' id='ppa' value='{$ppa}' size='3'>";
echo "<input type='text' name='kka' id='kka' value='{$kka}' size='3'>";
echo "<input type='text' name='vva' id='vva' value='{$vva}' size='5'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>", t("Syˆt‰ loppup‰iv‰m‰‰r‰"), " (", t("pp-kk-vvvv"), ")</th>";
echo "<td><input type='text' name='ppl' id='ppl' value='{$ppl}' size='3'>";
echo "<input type='text' name='kkl' id='kkl' value='{$kkl}' size='3'>";
echo "<input type='text' name='vvl' id='vvl' value='{$vvl}' size='5'></td>";
echo "</tr>";

if ($yhtiorow['konserni'] != "") {
  $query = "SELECT group_concat(yhtio) AS yhtiot
            FROM yhtio
            WHERE konserni  = '$yhtiorow[konserni]'
            and konserni   != ''";
  $yhtio_res = pupe_query($query);
  $yhtio_array = mysql_fetch_assoc($yhtio_res);

  $query = "SELECT nimi, yhtio
            FROM yhtio
            WHERE konserni  = '$yhtiorow[konserni]'
            and konserni   != ''";
  $yhtio_res = pupe_query($query);
  $numrows = mysql_num_rows($yhtio_res);

  echo "<tr>";
  echo "<th rowspan='{$numrows}'>", t("Valitse yhtiˆ"), "</th>";

  $i = 0;

  while ($yhtio_row = mysql_fetch_assoc($yhtio_res)) {

    if ($i > 0) {
      echo "</tr><tr>";
    }

    $chk = "";

    if (!isset($yhtiot) and $yhtio_row['yhtio'] == $kukarow['yhtio']) {
      $chk = "CHECKED";
    }

    if (isset($yhtiot[$yhtio_row['yhtio']]) and $yhtiot[$yhtio_row['yhtio']] != "") {
      $chk = "CHECKED";
    }

    echo "<td><input type='checkbox' name='yhtiot[{$yhtio_row['yhtio']}]' value='{$yhtio_row['yhtio']}' $chk> {$yhtio_row['nimi']}</td>";
    $i++;
  }

  echo "</tr>";
}
else {
  $yhtiot = array($kukarow['yhtio']);
}

$sel = array_fill_keys(array($naytetaan_tulos), " selected") + array('daily' => '', 'weekly' => '', 'monthly' => '');

echo "<tr><th>", t("N‰ytet‰‰n tulos"), "</th>";
echo "<td><select name='naytetaan_tulos' id='naytetaan_tulos'>";
echo "<option value='daily'{$sel['daily']}>", t("P‰ivitt‰in"), "</option>";
echo "<option value='weekly'{$sel['weekly']}>", t("Viikottain"), "</option>";
echo "<option value='monthly'{$sel['monthly']}>", t("Kuukausittain"), "</option>";
echo "</select></td></tr>";

$sel = array_fill_keys(array($naytetaan_luvut), " selected") + array('tuhansittain' => '', 'eurolleen' => '', 'sentilleen' => '');

echo "<tr><th>", t("N‰ytet‰‰n luvut"), "</th>";
echo "<td><select name='naytetaan_luvut' id='naytetaan_luvut'>";
echo "<option value='tuhansittain'{$sel['tuhansittain']}>", t("Tuhannen tarkkuudella"), "</option>";
echo "<option value='eurolleen'{$sel['eurolleen']}>", t("Kokonaislukuina"), "</option>";
echo "<option value='sentilleen'{$sel['sentilleen']}>", t("Sellaisinaan"), "</option>";
echo "</select></td></tr>";

if (isset($tavoitteet)) {
  $chk = "checked";
}
else {
  $tavoitteet = false;
  $chk = '';
}

echo "<tr><th>", t("N‰ytet‰‰n tavoitteet"), "</th>";
echo "<td><input type='checkbox' name='tavoitteet' value='1' $chk /></td></tr>";

echo "<tr><td colspan='2' class='back'><input type='submit' value='", t("Hae"), "' /></td></tr>";

echo "</table>";
echo "</td></tr></table>";
echo "</form>";

if (!isset($tee)) $tee = '';

if ($tee == 'laske' and (!isset($yhtiot) or count($yhtiot) == 0)) {
  echo "<font class='error'>", t("Et valinnut yhtiˆt‰"), "!</font>";
  $tee = '';
}

if ((isset($ppa) and (int) $ppa == 0) or (isset($kka) and (int) $kka == 0) or (isset($vva) and (int) $vva == 0) or (isset($ppl) and (int) $ppl == 0) or (isset($kkl) and (int) $kkl == 0) or (isset($vvl) and (int) $vvl == 0)) {
  echo "<font class='error'>", t("P‰iv‰m‰‰r‰ss‰ on virhe"), "!</font>";
  $tee = '';
}

if ($tee == 'laske') {

  $arr = $arr_kustp = $arr_kustp_osasto = $arr_kustp_osasto_try = $arr_osasto = array();
  $arr_try = $yhteensa_kustp = $yhteensa_osasto = $yhteensa_try = array();
  $yhteensa_kustp_osasto = $yhteensa_kustp_osasto_try = array();

  // Mitk‰ tuoteryhm‰t kuuluu mihinki osastoon
  $osaston_ryhmat = array();

  $query_yhtiot = implode("','", $yhtiot);

  $ppa = (int) $ppa;
  $kka = (int) $kka;
  $vva = (int) $vva;
  $ppl = (int) $ppl;
  $kkl = (int) $kkl;
  $vvl = (int) $vvl;

  $query = "SELECT
            left(tilausrivi.laadittu, 10) AS 'pvm',
            kustannuspaikka.nimi AS kustannuspaikka,
            tuote.osasto,
            tuote.try,
            sum(if(tilausrivi.laskutettu != '', tilausrivi.kate, (tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)-(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt)))) AS 'tilatut_kate',
            sum(if(tilausrivi.laskutettu != '', tilausrivi.rivihinta, (tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1))) AS tilatut_eurot,
            count(tilausrivi.tunnus) AS 'tilatut_rivit'
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.myynninseuranta = '')
            JOIN lasku on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
            JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and asiakas.myynninseuranta = '')
            LEFT JOIN kustannuspaikka ON (kustannuspaikka.yhtio = tilausrivi.yhtio AND kustannuspaikka.tunnus = asiakas.kustannuspaikka)
            WHERE tilausrivi.yhtio  IN ('{$query_yhtiot}')
            AND tilausrivi.tyyppi   = 'L'
            AND tilausrivi.laadittu >= '{$vva}-{$kka}-{$ppa} 00:00:00'
            AND tilausrivi.laadittu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
            GROUP BY 1,2,3,4
            ORDER BY tilausrivi.laadittu";
  $result = pupe_query($query);

  $_k = "";
  if ($naytetaan_luvut == 'tuhansittain') $_k = "k";

  echo "<br />";
  echo "<table>";
  echo "<tr>";
  echo "<th>", t("Kustp"), "</th>";
  echo "<th>", t("Osasto"), "<br />", t("Try"), "</th>";
  echo "<th>";
  echo $naytetaan_tulos == 'monthly' ? t("Kuukausi") : ($naytetaan_tulos == 'weekly' ? t("Viikko") : t("P‰iv‰"));
  echo "</th>";
  echo "<th>", t("Tilatut"), " $_k{$yhtiorow["valkoodi"]}</th>";
  echo "<th>", t("Tilatut Kate%"), "</th>";
  echo "<th>", t("Tilatut Rivit"), "</th>";
  echo "<th>", t("Laskutetut"), " $_k{$yhtiorow["valkoodi"]}</th>";
  echo "<th>", t("Laskutetut Kate%"), "</th>";
  echo "<th>", t("Laskutetut Rivit"), "</th>";
  if ($tavoitteet) {
    echo "<th>", t("Tavoite"), " $_k{$yhtiorow["valkoodi"]}</th>";
  }
  echo "<th>", t("Uudet"), " $_k{$yhtiorow["valkoodi"]}</th>";
  echo "<th>", t("Uudet Kate%"), "</th>";
  echo "<th>", t("Uudet Rivit"), "</th>";
  echo "</tr>";

  $yhteensa = array(
    'tilatut_eurot'    => 0,
    'tilatut_kate'     => 0,
    'tilatut_rivit'    => 0,
    'laskutetut_eurot' => 0,
    'laskutetut_kate'  => 0,
    'laskutetut_rivit' => 0,
    'avoimet_eurot'    => 0,
    'avoimet_kate'     => 0,
    'avoimet_rivit'    => 0,
  );

  if ($tavoitteet) {
    $yhteensa['tavoite'] = 0;
  }

  while ($row = mysql_fetch_assoc($result)) {

    $aikaleima = strtotime($row['pvm']);
    $pai = date('d', $aikaleima);
    $vuo = date('Y', $aikaleima);
    $vko = (int) date('W', $aikaleima);
    $kuu = (int) date('m', $aikaleima);
    $vko = $vko == 1 ? ($kuu == 12 ? 52 : 1) : ($vko >= 51 ? ($kuu == 1 ? 0 : $vko) : $vko);

    $kuu = str_pad($kuu, 2, "0", STR_PAD_LEFT);
    $vko = str_pad($vko, 2, "0", STR_PAD_LEFT);

    if ($naytetaan_tulos == 'weekly') {
      $pvm = "{$vko}-{$vuo}";
    }
    elseif ($naytetaan_tulos == 'monthly') {
      $pvm = "{$kuu}-{$vuo}";
    }
    else {
      $pvm = "{$pai}-{$kuu}-{$vuo}";
    }

    $kustp  = $row['kustannuspaikka'];
    $osasto = $row['osasto'];
    $try    = $row['try'];

    $osaston_ryhmat[$try] = $osasto;

    if (!isset($arr[$pvm]['tilatut_eurot'])) $arr[$pvm]['tilatut_eurot'] = 0;
    if (!isset($arr[$pvm]['tilatut_kate']))  $arr[$pvm]['tilatut_kate'] = 0;
    if (!isset($arr[$pvm]['tilatut_rivit'])) $arr[$pvm]['tilatut_rivit'] = 0;

    if (!isset($arr_kustp[$pvm][$kustp]['tilatut_eurot'])) $arr_kustp[$pvm][$kustp]['tilatut_eurot'] = 0;
    if (!isset($arr_kustp[$pvm][$kustp]['tilatut_kate']))  $arr_kustp[$pvm][$kustp]['tilatut_kate'] = 0;
    if (!isset($arr_kustp[$pvm][$kustp]['tilatut_rivit'])) $arr_kustp[$pvm][$kustp]['tilatut_rivit'] = 0;

    if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_eurot'])) $arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_eurot'] = 0;
    if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_kate']))  $arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_kate'] = 0;
    if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_rivit'])) $arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_rivit'] = 0;

    if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_eurot']))   $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_eurot'] = 0;
    if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_kate']))   $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_kate'] = 0;
    if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_rivit']))   $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_rivit'] = 0;

    if (!isset($arr_osasto[$pvm][$osasto]['tilatut_eurot'])) $arr_osasto[$pvm][$osasto]['tilatut_eurot'] = 0;
    if (!isset($arr_osasto[$pvm][$osasto]['tilatut_kate'])) $arr_osasto[$pvm][$osasto]['tilatut_kate'] = 0;
    if (!isset($arr_osasto[$pvm][$osasto]['tilatut_rivit'])) $arr_osasto[$pvm][$osasto]['tilatut_rivit'] = 0;

    if (!isset($arr_try[$pvm][$osasto][$try]['tilatut_eurot'])) $arr_try[$pvm][$osasto][$try]['tilatut_eurot'] = 0;
    if (!isset($arr_try[$pvm][$osasto][$try]['tilatut_kate'])) $arr_try[$pvm][$osasto][$try]['tilatut_kate'] = 0;
    if (!isset($arr_try[$pvm][$osasto][$try]['tilatut_rivit'])) $arr_try[$pvm][$osasto][$try]['tilatut_rivit'] = 0;

    $arr[$pvm]['tilatut_eurot'] += $row['tilatut_eurot'];
    $arr[$pvm]['tilatut_kate']  += $row['tilatut_kate'];
    $arr[$pvm]['tilatut_rivit'] += $row['tilatut_rivit'];

    $arr_kustp[$pvm][$kustp]['tilatut_eurot'] += $row['tilatut_eurot'];
    $arr_kustp[$pvm][$kustp]['tilatut_kate']  += $row['tilatut_kate'];
    $arr_kustp[$pvm][$kustp]['tilatut_rivit'] += $row['tilatut_rivit'];

    $arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_eurot'] += $row['tilatut_eurot'];
    $arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_kate']  += $row['tilatut_kate'];
    $arr_kustp_osasto[$pvm][$kustp][$osasto]['tilatut_rivit'] += $row['tilatut_rivit'];

    $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_eurot'] += $row['tilatut_eurot'];
    $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_kate']  += $row['tilatut_kate'];
    $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tilatut_rivit'] += $row['tilatut_rivit'];

    $arr_osasto[$pvm][$osasto]['tilatut_eurot'] += $row['tilatut_eurot'];
    $arr_osasto[$pvm][$osasto]['tilatut_kate']  += $row['tilatut_kate'];
    $arr_osasto[$pvm][$osasto]['tilatut_rivit'] += $row['tilatut_rivit'];

    $arr_try[$pvm][$osasto][$try]['tilatut_eurot'] += $row['tilatut_eurot'];
    $arr_try[$pvm][$osasto][$try]['tilatut_kate']  += $row['tilatut_kate'];
    $arr_try[$pvm][$osasto][$try]['tilatut_rivit'] += $row['tilatut_rivit'];
  }

  $query = "SELECT
            tilausrivi.laskutettuaika AS 'pvm',
            kustannuspaikka.nimi AS kustannuspaikka,
            tuote.osasto,
            tuote.try,
            sum(tilausrivi.kate) AS 'laskutetut_kate',
            sum(tilausrivi.rivihinta) AS 'laskutetut_eurot',
            count(tilausrivi.tunnus) AS 'laskutetut_rivit'
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.myynninseuranta = '')
            JOIN lasku on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
            JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and asiakas.myynninseuranta = '')
            LEFT JOIN kustannuspaikka ON (kustannuspaikka.yhtio = tilausrivi.yhtio AND kustannuspaikka.tunnus = asiakas.kustannuspaikka)
            WHERE tilausrivi.yhtio         IN ('{$query_yhtiot}')
            AND tilausrivi.tyyppi          = 'L'
            AND tilausrivi.laskutettuaika  >= '{$vva}-{$kka}-{$ppa}'
            AND tilausrivi.laskutettuaika  <= '{$vvl}-{$kkl}-{$ppl}'
            AND tilausrivi.laskutettu     != ''
            GROUP BY 1,2,3,4
            ORDER BY tilausrivi.laadittu";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {

    $aikaleima = strtotime($row['pvm']);
    $pai = date('d', $aikaleima);
    $vuo = date('Y', $aikaleima);
    $vko = (int) date('W', $aikaleima);
    $kuu = (int) date('m', $aikaleima);
    $vko = $vko == 1 ? ($kuu == 12 ? 52 : 1) : ($vko >= 51 ? ($kuu == 1 ? 0 : $vko) : $vko);

    $kuu = str_pad($kuu, 2, "0", STR_PAD_LEFT);
    $vko = str_pad($vko, 2, "0", STR_PAD_LEFT);

    if ($naytetaan_tulos == 'weekly') {
      $pvm = "{$vko}-{$vuo}";
    }
    elseif ($naytetaan_tulos == 'monthly') {
      $pvm = "{$kuu}-{$vuo}";
    }
    else {
      $pvm = "{$pai}-{$kuu}-{$vuo}";
    }

    $kustp  = $row['kustannuspaikka'];
    $osasto = $row['osasto'];
    $try    = $row['try'];

    $osaston_ryhmat[$try] = $osasto;

    if (!isset($arr[$pvm]['laskutetut_eurot'])) $arr[$pvm]['laskutetut_eurot'] = 0;
    if (!isset($arr[$pvm]['laskutetut_kate'])) $arr[$pvm]['laskutetut_kate'] = 0;
    if (!isset($arr[$pvm]['laskutetut_rivit'])) $arr[$pvm]['laskutetut_rivit'] = 0;

    if (!isset($arr_kustp[$pvm][$kustp]['laskutetut_eurot'])) $arr_kustp[$pvm][$kustp]['laskutetut_eurot'] = 0;
    if (!isset($arr_kustp[$pvm][$kustp]['laskutetut_kate'])) $arr_kustp[$pvm][$kustp]['laskutetut_kate'] = 0;
    if (!isset($arr_kustp[$pvm][$kustp]['laskutetut_rivit'])) $arr_kustp[$pvm][$kustp]['laskutetut_rivit'] = 0;

    if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_eurot'])) $arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_eurot'] = 0;
    if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_kate']))  $arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_kate'] = 0;
    if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_rivit'])) $arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_rivit'] = 0;

    if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_eurot'])) $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_eurot'] = 0;
    if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_kate']))   $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_kate'] = 0;
    if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_rivit'])) $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_rivit'] = 0;

    if (!isset($arr_osasto[$pvm][$osasto]['laskutetut_eurot'])) $arr_osasto[$pvm][$osasto]['laskutetut_eurot'] = 0;
    if (!isset($arr_osasto[$pvm][$osasto]['laskutetut_kate'])) $arr_osasto[$pvm][$osasto]['laskutetut_kate'] = 0;
    if (!isset($arr_osasto[$pvm][$osasto]['laskutetut_rivit'])) $arr_osasto[$pvm][$osasto]['laskutetut_rivit'] = 0;

    if (!isset($arr_try[$pvm][$osasto][$try]['laskutetut_eurot'])) $arr_try[$pvm][$osasto][$try]['laskutetut_eurot'] = 0;
    if (!isset($arr_try[$pvm][$osasto][$try]['laskutetut_kate'])) $arr_try[$pvm][$osasto][$try]['laskutetut_kate'] = 0;
    if (!isset($arr_try[$pvm][$osasto][$try]['laskutetut_rivit'])) $arr_try[$pvm][$osasto][$try]['laskutetut_rivit'] = 0;

    $arr[$pvm]['laskutetut_eurot'] += $row['laskutetut_eurot'];
    $arr[$pvm]['laskutetut_kate']  += $row['laskutetut_kate'];
    $arr[$pvm]['laskutetut_rivit'] += $row['laskutetut_rivit'];

    $arr_kustp[$pvm][$kustp]['laskutetut_eurot'] += $row['laskutetut_eurot'];
    $arr_kustp[$pvm][$kustp]['laskutetut_kate']  += $row['laskutetut_kate'];
    $arr_kustp[$pvm][$kustp]['laskutetut_rivit'] += $row['laskutetut_rivit'];

    $arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_eurot'] += $row['laskutetut_eurot'];
    $arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_kate']  += $row['laskutetut_kate'];
    $arr_kustp_osasto[$pvm][$kustp][$osasto]['laskutetut_rivit'] += $row['laskutetut_rivit'];

    $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_eurot'] += $row['laskutetut_eurot'];
    $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_kate']  += $row['laskutetut_kate'];
    $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['laskutetut_rivit'] += $row['laskutetut_rivit'];

    $arr_osasto[$pvm][$osasto]['laskutetut_eurot'] += $row['laskutetut_eurot'];
    $arr_osasto[$pvm][$osasto]['laskutetut_kate']  += $row['laskutetut_kate'];
    $arr_osasto[$pvm][$osasto]['laskutetut_rivit'] += $row['laskutetut_rivit'];

    $arr_try[$pvm][$osasto][$try]['laskutetut_eurot'] += $row['laskutetut_eurot'];
    $arr_try[$pvm][$osasto][$try]['laskutetut_kate']  += $row['laskutetut_kate'];
    $arr_try[$pvm][$osasto][$try]['laskutetut_rivit'] += $row['laskutetut_rivit'];
  }

  $query = "SELECT
            left(lasku.luontiaika, 10) AS 'pvm',
            kustannuspaikka.nimi AS kustannuspaikka,
            tuote.osasto,
            tuote.try,
            sum(if(tilausrivi.laskutettu != '', tilausrivi.kate, (tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1)-(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt)))) AS 'avoimet_kate',
            sum(if(tilausrivi.laskutettu != '', tilausrivi.rivihinta, (tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('{$yhtiorow['alv_kasittely']}'='',(1+tilausrivi.alv/100),1))) AS avoimet_eurot,
            count(tilausrivi.tunnus) AS 'avoimet_rivit'
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.myynninseuranta = '')
            JOIN lasku on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
            JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and asiakas.myynninseuranta = '')
            LEFT JOIN kustannuspaikka ON (kustannuspaikka.yhtio = tilausrivi.yhtio AND kustannuspaikka.tunnus = asiakas.kustannuspaikka)
            WHERE tilausrivi.yhtio  IN ('{$query_yhtiot}')
            AND tilausrivi.tyyppi   = 'L'
            #AND tilausrivi.laadittu >= '{$vva}-{$kka}-{$ppa} 00:00:00'
            #AND tilausrivi.laadittu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
            #AND (tilausrivi.laskutettuaika >= '{$vvl}-{$kkl}-{$ppl} 23:59:59' OR tilausrivi.laskutettuaika = 0)
            AND lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00'
            AND lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
            GROUP BY 1,2,3,4
            ORDER BY tilausrivi.laadittu";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {

    $aikaleima = strtotime($row['pvm']);
    $pai = date('d', $aikaleima);
    $vuo = date('Y', $aikaleima);
    $vko = (int) date('W', $aikaleima);
    $kuu = (int) date('m', $aikaleima);
    $vko = $vko == 1 ? ($kuu == 12 ? 52 : 1) : ($vko >= 51 ? ($kuu == 1 ? 0 : $vko) : $vko);

    $kuu = str_pad($kuu, 2, "0", STR_PAD_LEFT);
    $vko = str_pad($vko, 2, "0", STR_PAD_LEFT);

    if ($naytetaan_tulos == 'weekly') {
      $pvm = "{$vko}-{$vuo}";
    }
    elseif ($naytetaan_tulos == 'monthly') {
      $pvm = "{$kuu}-{$vuo}";
    }
    else {
      $pvm = "{$pai}-{$kuu}-{$vuo}";
    }

    $kustp  = $row['kustannuspaikka'];
    $osasto = $row['osasto'];
    $try    = $row['try'];

    $osaston_ryhmat[$try] = $osasto;

    if (!isset($arr[$pvm]['avoimet_eurot'])) $arr[$pvm]['avoimet_eurot'] = 0;
    if (!isset($arr[$pvm]['avoimet_kate'])) $arr[$pvm]['avoimet_kate'] = 0;
    if (!isset($arr[$pvm]['avoimet_rivit'])) $arr[$pvm]['avoimet_rivit'] = 0;

    if (!isset($arr_kustp[$pvm][$kustp]['avoimet_eurot'])) $arr_kustp[$pvm][$kustp]['avoimet_eurot'] = 0;
    if (!isset($arr_kustp[$pvm][$kustp]['avoimet_kate'])) $arr_kustp[$pvm][$kustp]['avoimet_kate'] = 0;
    if (!isset($arr_kustp[$pvm][$kustp]['avoimet_rivit'])) $arr_kustp[$pvm][$kustp]['avoimet_rivit'] = 0;

    if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['avoimet_eurot'])) $arr_kustp_osasto[$pvm][$kustp][$osasto]['avoimet_eurot'] = 0;
    if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['avoimet_kate']))  $arr_kustp_osasto[$pvm][$kustp][$osasto]['avoimet_kate'] = 0;
    if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['avoimet_rivit'])) $arr_kustp_osasto[$pvm][$kustp][$osasto]['avoimet_rivit'] = 0;

    if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['avoimet_eurot'])) $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['avoimet_eurot'] = 0;
    if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['avoimet_kate']))   $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['avoimet_kate'] = 0;
    if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['avoimet_rivit'])) $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['avoimet_rivit'] = 0;

    if (!isset($arr_osasto[$pvm][$osasto]['avoimet_eurot'])) $arr_osasto[$pvm][$osasto]['avoimet_eurot'] = 0;
    if (!isset($arr_osasto[$pvm][$osasto]['avoimet_kate'])) $arr_osasto[$pvm][$osasto]['avoimet_kate'] = 0;
    if (!isset($arr_osasto[$pvm][$osasto]['avoimet_rivit'])) $arr_osasto[$pvm][$osasto]['avoimet_rivit'] = 0;

    if (!isset($arr_try[$pvm][$osasto][$try]['avoimet_eurot'])) $arr_try[$pvm][$osasto][$try]['avoimet_eurot'] = 0;
    if (!isset($arr_try[$pvm][$osasto][$try]['avoimet_kate'])) $arr_try[$pvm][$osasto][$try]['avoimet_kate'] = 0;
    if (!isset($arr_try[$pvm][$osasto][$try]['avoimet_rivit'])) $arr_try[$pvm][$osasto][$try]['avoimet_rivit'] = 0;

    $arr[$pvm]['avoimet_eurot'] += $row['avoimet_eurot'];
    $arr[$pvm]['avoimet_kate']  += $row['avoimet_kate'];
    $arr[$pvm]['avoimet_rivit'] += $row['avoimet_rivit'];

    $arr_kustp[$pvm][$kustp]['avoimet_eurot'] += $row['avoimet_eurot'];
    $arr_kustp[$pvm][$kustp]['avoimet_kate']  += $row['avoimet_kate'];
    $arr_kustp[$pvm][$kustp]['avoimet_rivit'] += $row['avoimet_rivit'];

    $arr_kustp_osasto[$pvm][$kustp][$osasto]['avoimet_eurot'] += $row['avoimet_eurot'];
    $arr_kustp_osasto[$pvm][$kustp][$osasto]['avoimet_kate']  += $row['avoimet_kate'];
    $arr_kustp_osasto[$pvm][$kustp][$osasto]['avoimet_rivit'] += $row['avoimet_rivit'];

    $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['avoimet_eurot'] += $row['avoimet_eurot'];
    $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['avoimet_kate']  += $row['avoimet_kate'];
    $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['avoimet_rivit'] += $row['avoimet_rivit'];

    $arr_osasto[$pvm][$osasto]['avoimet_eurot'] += $row['avoimet_eurot'];
    $arr_osasto[$pvm][$osasto]['avoimet_kate']  += $row['avoimet_kate'];
    $arr_osasto[$pvm][$osasto]['avoimet_rivit'] += $row['avoimet_rivit'];

    $arr_try[$pvm][$osasto][$try]['avoimet_eurot'] += $row['avoimet_eurot'];
    $arr_try[$pvm][$osasto][$try]['avoimet_kate']  += $row['avoimet_kate'];
    $arr_try[$pvm][$osasto][$try]['avoimet_rivit'] += $row['avoimet_rivit'];
  }

  // Haetaan tavoitteet
  if ($tavoitteet) {

    $alku  = "{$vva}-{$kka}-{$ppa}";
    $loppu = "{$vvl}-{$kkl}-{$ppl}";

    $alkupvm_totime  = strtotime($alku);
    $loppupvm_totime = strtotime($loppu);

    $ero   = floor(($loppupvm_totime - $alkupvm_totime)/86400);

    $query = "SELECT
              budjetti_asiakas.kausi,
              budjetti_asiakas.try,
              kustannuspaikka.nimi AS kp_nimi,
              sum(budjetti_asiakas.summa) summa
              FROM budjetti_asiakas
              JOIN asiakas ON (asiakas.yhtio = budjetti_asiakas.yhtio AND asiakas.tunnus = budjetti_asiakas.asiakkaan_tunnus)
              JOIN kustannuspaikka ON (kustannuspaikka.yhtio = asiakas.yhtio AND kustannuspaikka.tunnus = asiakas.kustannuspaikka)
              WHERE budjetti_asiakas.yhtio  IN ('{$query_yhtiot}')
              AND budjetti_asiakas.kausi    >= DATE_FORMAT('{$alku}', '%Y%m')
              AND budjetti_asiakas.kausi    <= DATE_FORMAT('{$loppu}','%Y%m')
              AND budjetti_asiakas.summa    > 0
              AND budjetti_asiakas.try     != ''
              GROUP BY 1,2,3";
    $result = pupe_query($query);

    while ($row = mysql_fetch_assoc($result)) {

      $vuosi = substr($row['kausi'], 0, 4);
      $kuu   = substr($row['kausi'], 4, 2);

      $paivia = cal_days_in_month(CAL_GREGORIAN, $kuu, $vuosi);

      // Tehd‰‰n p‰iv‰kohtaiset arrayt
      for ($i = 0; $i < $paivia; $i++) {

        $paiva = $i+1;
        $paiva = str_pad($paiva, 2, "0", STR_PAD_LEFT);

        if ($naytetaan_tulos == 'daily') {
          $pvm = "{$paiva}-{$kuu}-{$vuosi}";
        }
        elseif ($naytetaan_tulos == 'weekly') {
          $pvm = date("W-Y", strtotime("{$vuosi}-{$kuu}-{$paiva}"));
        }
        else {
          $pvm = "{$kuu}-{$vuosi}";
        }

        if ($alkupvm_totime <= strtotime("{$vuosi}-{$kuu}-{$paiva}") and $loppupvm_totime >= strtotime("{$vuosi}-{$kuu}-{$paiva}")) {

          $kustp = $row['kp_nimi'];
          $try   = $row['try'];

          // t‰m‰ tuoteryhm‰ kuuluu t‰h‰n osastoon
          if (!isset($osaston_ryhmat[$try])) {
            $osaston_ryhmat[$try] = 0;
          }

          $osasto = $osaston_ryhmat[$try];

          if (!isset($arr[$pvm]['tavoite'])) {
            $arr[$pvm]['tavoite'] = 0;
          }
          if (!isset($arr_kustp[$pvm][$kustp]['tavoite'])) {
            $arr_kustp[$pvm][$kustp]['tavoite'] = 0;
          }
          if (!isset($arr_kustp_osasto[$pvm][$kustp][$osasto]['tavoite'])) {
            $arr_kustp_osasto[$pvm][$kustp][$osasto]['tavoite'] = 0;
          }
          if (!isset($arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tavoite'])) {
            $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tavoite'] = 0;
          }
          if (!isset($arr_osasto[$pvm][$osasto]['tavoite'])) {
            $arr_osasto[$pvm][$osasto]['tavoite'] = 0;
          }
          if (!isset($arr_try[$pvm][$osasto][$try]['tavoite'])) {
            $arr_try[$pvm][$osasto][$try]['tavoite'] = 0;
          }

          $arr[$pvm]['tavoite'] += $row['summa'] / $paivia;
          $arr_kustp[$pvm][$kustp]['tavoite'] += $row['summa'] / $paivia;
          $arr_kustp_osasto[$pvm][$kustp][$osasto]['tavoite'] += $row['summa'] / $paivia;
          $arr_kustp_osasto_try[$pvm][$kustp][$osasto][$try]['tavoite'] += $row['summa'] / $paivia;
          $arr_osasto[$pvm][$osasto]['tavoite'] += $row['summa'] / $paivia;
          $arr_try[$pvm][$osasto][$try]['tavoite'] += $row['summa'] / $paivia;
        }
      }
    }
  }

  function aikasorttaus($a, $b) {
    global $naytetaan_tulos;

    if ($naytetaan_tulos == 'weekly') {
      $a_vuo = substr($a, 3, 4);
      $a_vko = substr($a, 0, 2);
      $a = strtotime("{$a_vuo}W{$a_vko}");

      $b_vuo = substr($b, 3, 4);
      $b_vko = substr($b, 0, 2);
      $b = strtotime("{$b_vuo}W{$b_vko}");
    }
    elseif ($naytetaan_tulos == 'monthly') {
      $a_vuo = substr($a, 3, 4);
      $a_kuu = substr($a, 0, 2);
      $a = strtotime("{$a_vuo}-{$a_kuu}");

      $b_vuo = substr($b, 3, 4);
      $b_kuu = substr($b, 0, 2);
      $b = strtotime("{$b_vuo}-{$b_kuu}");
    }
    else {
      $a_vuo = substr($a, 6, 4);
      $a_kuu = substr($a, 3, 2);
      $a_pai = substr($a, 0, 2);
      $a = strtotime("{$a_vuo}-{$a_kuu}-{$a_pai}");

      $b_vuo = substr($b, 6, 4);
      $b_kuu = substr($b, 3, 2);
      $b_pai = substr($b, 0, 2);
      $b = strtotime("{$b_vuo}-{$b_kuu}-{$b_pai}");
    }

    if ($a==$b) return 0;
    return ($a<$b)?-1:1;
  }

  uksort($arr, "aikasorttaus");

  foreach ($arr as $pvm => $arvot) {

    $_pvm =  str_replace('-', '.', $pvm);

    if ($tavoitteet) {
      if (!isset($arvot['tavoite'])) {
        $arvot['tavoite'] = 0;
      }
      $yhteensa['tavoite'] += $arvot['tavoite'];
    }

    $yhteensa['tilatut_eurot']    += (isset($arvot['tilatut_eurot']) and $arvot['tilatut_eurot'] != '') ? $arvot['tilatut_eurot'] : 0;
    $yhteensa['tilatut_kate']     += (isset($arvot['tilatut_kate']) and $arvot['tilatut_kate'] != '') ? $arvot['tilatut_kate'] : 0;
    $yhteensa['tilatut_rivit']    += (isset($arvot['tilatut_rivit']) and $arvot['tilatut_rivit'] != '') ? $arvot['tilatut_rivit'] : 0;
    $yhteensa['laskutetut_eurot'] += (isset($arvot['laskutetut_eurot']) and $arvot['laskutetut_eurot'] != '') ? $arvot['laskutetut_eurot'] : 0;
    $yhteensa['laskutetut_kate']  += (isset($arvot['laskutetut_kate']) and $arvot['laskutetut_kate'] != '') ? $arvot['laskutetut_kate'] : 0;
    $yhteensa['laskutetut_rivit'] += (isset($arvot['laskutetut_rivit']) and $arvot['laskutetut_rivit'] != '') ? $arvot['laskutetut_rivit'] : 0;
    $yhteensa['avoimet_eurot']    += (isset($arvot['avoimet_eurot']) and $arvot['avoimet_eurot'] != '') ? $arvot['avoimet_eurot'] : 0;
    $yhteensa['avoimet_kate']     += (isset($arvot['avoimet_kate']) and $arvot['avoimet_kate'] != '') ? $arvot['avoimet_kate'] : 0;
    $yhteensa['avoimet_rivit']    += (isset($arvot['avoimet_rivit']) and $arvot['avoimet_rivit'] != '') ? $arvot['avoimet_rivit'] : 0;

    $tilatut_katepros = (isset($arvot['tilatut_eurot']) and $arvot['tilatut_eurot'] != 0) ? round($arvot['tilatut_kate'] / $arvot['tilatut_eurot'] * 100, 1) : 0;
    $laskutetut_katepros = (isset($arvot['laskutetut_kate']) and $arvot['laskutetut_eurot'] !=0) ? round($arvot['laskutetut_kate'] / $arvot['laskutetut_eurot'] * 100, 1) : 0;
    $avoimet_katepros = (isset($arvot['avoimet_eurot']) and $arvot['avoimet_eurot'] != 0) ? round($arvot['avoimet_kate'] / $arvot['avoimet_eurot'] * 100, 1) : 0;

    if ($naytetaan_luvut == 'eurolleen') {
      $arvot['tilatut_eurot'] = isset($arvot['tilatut_eurot']) ? round($arvot['tilatut_eurot']) : 0;
      $arvot['laskutetut_eurot'] = isset($arvot['laskutetut_eurot']) ? round($arvot['laskutetut_eurot']) : 0;
      $arvot['avoimet_eurot'] = isset($arvot['avoimet_eurot']) ? round($arvot['avoimet_eurot']) : 0;
      $arvot['tavoite'] = isset($arvot['tavoite']) ? round($arvot['tavoite']) : 0;
    }
    elseif ($naytetaan_luvut == 'sentilleen') {
      $arvot['tilatut_eurot'] = isset($arvot['tilatut_eurot']) ? round($arvot['tilatut_eurot'], 2) : 0;
      $arvot['laskutetut_eurot'] = isset($arvot['laskutetut_eurot']) ? round($arvot['laskutetut_eurot'], 2) : 0;
      $arvot['avoimet_eurot'] = isset($arvot['avoimet_eurot']) ? round($arvot['avoimet_eurot'], 2) : 0;
      $arvot['tavoite'] = isset($arvot['tavoite']) ? round($arvot['tavoite'], 2) : 0;
    }
    else {
      $arvot['tilatut_eurot'] = isset($arvot['tilatut_eurot']) ? round($arvot['tilatut_eurot'] / 1000) : 0;
      $arvot['laskutetut_eurot'] = isset($arvot['laskutetut_eurot']) ? round($arvot['laskutetut_eurot'] / 1000) : 0;
      $arvot['avoimet_eurot'] = isset($arvot['avoimet_eurot']) ? round($arvot['avoimet_eurot'] / 1000) : 0;
      $arvot['tavoite'] = isset($arvot['tavoite']) ? round($arvot['tavoite'] / 1000) : 0;
    }

    $arvot['laskutetut_rivit'] = isset($arvot['laskutetut_rivit']) ? $arvot['laskutetut_rivit'] : 0;
    $arvot['tilatut_rivit'] = isset($arvot['tilatut_rivit']) ? $arvot['tilatut_rivit'] : 0;
    $arvot['avoimet_rivit'] = isset($arvot['avoimet_rivit']) ? $arvot['avoimet_rivit'] : 0;

    echo "<tr class='aktiivi'>";
    echo "<td align='left' class='toggleable' id='{$pvm}'><img style='float:left;' id='img_{$pvm}' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' /></td>";
    echo "<td align='left' class='toggleable' id='{$pvm}_osasto'><img style='float:left;' id='img_{$pvm}_osasto' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' /></td>";
    echo "<td align='left'>{$_pvm}</td>";
    echo "<td align='right'>{$arvot['tilatut_eurot']}</td>";
    echo "<td align='right'>{$tilatut_katepros}</td>";
    echo "<td align='right'>{$arvot['tilatut_rivit']}</td>";
    echo "<td align='right'>{$arvot['laskutetut_eurot']}</td>";
    echo "<td align='right'>{$laskutetut_katepros}</td>";
    echo "<td align='right'>{$arvot['laskutetut_rivit']}</td>";
    if ($tavoitteet) {
      echo "<td align='right'>".round($arvot['tavoite'], 2)."</td>";
    }
    echo "<td align='right'>{$arvot['avoimet_eurot']}</td>";
    echo "<td align='right'>{$avoimet_katepros}</td>";
    echo "<td align='right'>{$arvot['avoimet_rivit']}</td>";
    echo "</tr>";

    ksort($arr_kustp[$pvm]);

    foreach ($arr_kustp[$pvm] as $kustp => $vals) {

      if (!isset($vals['tilatut_eurot'])) $vals['tilatut_eurot'] = 0;
      if (!isset($vals['tilatut_kate'])) $vals['tilatut_kate'] = 0;
      if (!isset($vals['tilatut_rivit'])) $vals['tilatut_rivit'] = 0;
      if (!isset($vals['avoimet_rivit'])) $vals['avoimet_rivit'] = 0;
      if (!isset($vals['tavoite']) and $tavoitteet) $vals['tavoite'] = 0;

      $tilatut_katepros = $vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : 0;
      $laskutetut_katepros = (isset($vals['laskutetut_kate']) and isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != 0) ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : 0;
      $avoimet_katepros = $vals['avoimet_eurot'] != 0 ? round($vals['avoimet_kate'] / $vals['avoimet_eurot'] * 100, 1) : 0;

      if (!isset($yhteensa_kustp[$kustp]['tilatut_eurot'])) $yhteensa_kustp[$kustp]['tilatut_eurot'] = 0;
      if (!isset($yhteensa_kustp[$kustp]['tilatut_kate'])) $yhteensa_kustp[$kustp]['tilatut_kate'] = 0;
      if (!isset($yhteensa_kustp[$kustp]['tilatut_rivit'])) $yhteensa_kustp[$kustp]['tilatut_rivit'] = 0;
      if (!isset($yhteensa_kustp[$kustp]['laskutetut_eurot'])) $yhteensa_kustp[$kustp]['laskutetut_eurot'] = 0;
      if (!isset($yhteensa_kustp[$kustp]['laskutetut_kate'])) $yhteensa_kustp[$kustp]['laskutetut_kate'] = 0;
      if (!isset($yhteensa_kustp[$kustp]['laskutetut_rivit'])) $yhteensa_kustp[$kustp]['laskutetut_rivit'] = 0;
      if (!isset($yhteensa_kustp[$kustp]['tavoite']) and $tavoitteet) $yhteensa_kustp[$kustp]['tavoite'] = 0;
      if (!isset($yhteensa_kustp[$kustp]['avoimet_eurot'])) $yhteensa_kustp[$kustp]['avoimet_eurot'] = 0;
      if (!isset($yhteensa_kustp[$kustp]['avoimet_kate'])) $yhteensa_kustp[$kustp]['avoimet_kate'] = 0;
      if (!isset($yhteensa_kustp[$kustp]['avoimet_rivit'])) $yhteensa_kustp[$kustp]['avoimet_rivit'] = 0;

      if ($tavoitteet) {
        $yhteensa_kustp[$kustp]['tavoite'] += $vals['tavoite'];
      }
      $yhteensa_kustp[$kustp]['tilatut_eurot']     += $vals['tilatut_eurot'];
      $yhteensa_kustp[$kustp]['tilatut_kate']     += $vals['tilatut_kate'];
      $yhteensa_kustp[$kustp]['tilatut_rivit']     += $vals['tilatut_rivit'];
      $yhteensa_kustp[$kustp]['laskutetut_eurot']   += (isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != '') ? $vals['laskutetut_eurot'] : 0;
      $yhteensa_kustp[$kustp]['laskutetut_kate']     += (isset($vals['laskutetut_kate']) and $vals['laskutetut_kate'] != '') ? $vals['laskutetut_kate'] : 0;
      $yhteensa_kustp[$kustp]['laskutetut_rivit']   += (isset($vals['laskutetut_rivit']) and $vals['laskutetut_rivit'] != '') ? $vals['laskutetut_rivit'] : 0;
      $yhteensa_kustp[$kustp]['avoimet_eurot']     += $vals['avoimet_eurot'];
      $yhteensa_kustp[$kustp]['avoimet_kate']     += $vals['avoimet_kate'];
      $yhteensa_kustp[$kustp]['avoimet_rivit']     += $vals['avoimet_rivit'];
      $yhteensa_kustp[$kustp]['pvm'][$pvm]      = $pvm;

      if ($naytetaan_luvut == 'eurolleen') {
        $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot']) : 0;
        $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot']) : 0;
        $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot']) : 0;
        $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite']) : 0;
      }
      elseif ($naytetaan_luvut == 'sentilleen') {
        $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'], 2) : 0;
        $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'], 2) : 0;
        $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot'], 2) : 0;
        $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite'], 2) : 0;
      }
      else {
        $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'] / 1000) : 0;
        $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'] / 1000) : 0;
        $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot'] / 1000) : 0;
        $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite'] / 1000) : 0;
      }

      $vals['laskutetut_rivit'] = isset($vals['laskutetut_rivit']) ? $vals['laskutetut_rivit'] : 0;
      $vals['tilatut_rivit'] = isset($vals['tilatut_rivit']) ? $vals['tilatut_rivit'] : 0;
      $vals['avoimet_rivit'] = isset($vals['avoimet_rivit']) ? $vals['avoimet_rivit'] : 0;

      $id = sanitoi_javascript_id($pvm.'_'.$kustp);

      echo "<tr class='{$pvm} spec kustp' style='display:none;'>";
      echo "<td align='right' class='toggleable' id='{$id}_osasto'><img style='float:left;' id='img_{$id}_osasto' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;{$kustp}</td>";
      echo "<td align='right'></td>";
      echo "<td align='right'></td>";
      echo "<td align='right'>{$vals['tilatut_eurot']}</td>";
      echo "<td align='right'>{$tilatut_katepros}</td>";
      echo "<td align='right'>{$vals['tilatut_rivit']}</td>";
      echo "<td align='right'>{$vals['laskutetut_eurot']}</td>";
      echo "<td align='right'>{$laskutetut_katepros}</td>";
      echo "<td align='right'>{$vals['laskutetut_rivit']}</td>";
      if ($tavoitteet) {
        echo "<td align='right'>".round($vals['tavoite'], 2)."</td>";
      }
      echo "<td align='right'>{$vals['avoimet_eurot']}</td>";
      echo "<td align='right'>{$avoimet_katepros}</td>";
      echo "<td align='right'>{$vals['avoimet_rivit']}</td>";
      echo "</tr>";

      ksort($arr_kustp_osasto[$pvm][$kustp]);

      foreach ($arr_kustp_osasto[$pvm][$kustp] as $osasto => $vals) {

        if (!isset($vals['tilatut_eurot'])) $vals['tilatut_eurot'] = 0;
        if (!isset($vals['tilatut_kate']))  $vals['tilatut_kate'] = 0;
        if (!isset($vals['tilatut_rivit'])) $vals['tilatut_rivit'] = 0;
        if (!isset($vals['avoimet_eurot'])) $vals['avoimet_eurot'] = 0;
        if (!isset($vals['avoimet_kate']))  $vals['avoimet_kate'] = 0;
        if (!isset($vals['avoimet_rivit'])) $vals['avoimet_rivit'] = 0;

        $tilatut_katepros = $vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : 0;
        $laskutetut_katepros = (isset($vals['laskutetut_kate']) and isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != 0) ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : 0;
        $avoimet_katepros = $vals['avoimet_eurot'] != 0 ? round($vals['avoimet_kate'] / $vals['avoimet_eurot'] * 100, 1) : 0;

        if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_eurot']))     $yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_eurot'] = 0;
        if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_kate']))     $yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_kate'] = 0;
        if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_rivit']))     $yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_rivit'] = 0;
        if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_eurot']))   $yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_eurot'] = 0;
        if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_kate']))   $yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_kate'] = 0;
        if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_rivit']))   $yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_rivit'] = 0;
        if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['tavoite']) and $tavoitteet) $yhteensa_kustp_osasto[$kustp][$osasto]['tavoite'] = 0;
        if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['avoimet_eurot']))     $yhteensa_kustp_osasto[$kustp][$osasto]['avoimet_eurot'] = 0;
        if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['avoimet_kate']))     $yhteensa_kustp_osasto[$kustp][$osasto]['avoimet_kate'] = 0;
        if (!isset($yhteensa_kustp_osasto[$kustp][$osasto]['avoimet_rivit']))     $yhteensa_kustp_osasto[$kustp][$osasto]['avoimet_rivit'] = 0;

        if ($tavoitteet) {
          if (!isset($vals['tavoite'])) {
            $vals['tavoite'] = 0;
          }
          $yhteensa_kustp_osasto[$kustp][$osasto]['tavoite'] += $vals['tavoite'];
        }
        $yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_eurot']     += $vals['tilatut_eurot'];
        $yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_kate']     += $vals['tilatut_kate'];
        $yhteensa_kustp_osasto[$kustp][$osasto]['tilatut_rivit']     += $vals['tilatut_rivit'];
        $yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_eurot']   += (isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != '') ? $vals['laskutetut_eurot'] : 0;
        $yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_kate']   += (isset($vals['laskutetut_kate']) and $vals['laskutetut_kate'] != '') ? $vals['laskutetut_kate'] : 0;
        $yhteensa_kustp_osasto[$kustp][$osasto]['laskutetut_rivit']   += (isset($vals['laskutetut_rivit']) and $vals['laskutetut_rivit'] != '') ? $vals['laskutetut_rivit'] : 0;
        $yhteensa_kustp_osasto[$kustp][$osasto]['avoimet_eurot']     += $vals['avoimet_eurot'];
        $yhteensa_kustp_osasto[$kustp][$osasto]['avoimet_kate']     += $vals['avoimet_kate'];
        $yhteensa_kustp_osasto[$kustp][$osasto]['avoimet_rivit']     += $vals['avoimet_rivit'];
        $yhteensa_kustp_osasto[$kustp][$osasto]['pvm'][$pvm]      = $pvm;

        if ($naytetaan_luvut == 'eurolleen') {
          $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot']) : 0;
          $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot']) : 0;
          $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite']) : 0;
          $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot']) : 0;
        }
        elseif ($naytetaan_luvut == 'sentilleen') {
          $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'], 2) : 0;
          $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'], 2) : 0;
          $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite'], 2) : 0;
          $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot'], 2) : 0;
        }
        else {
          $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'] / 1000) : 0;
          $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'] / 1000) : 0;
          $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite'] / 1000) : 0;
          $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot'] / 1000) : 0;
        }

        $vals['laskutetut_rivit'] = isset($vals['laskutetut_rivit']) ? $vals['laskutetut_rivit'] : 0;
        $vals['tilatut_rivit'] = isset($vals['tilatut_rivit']) ? $vals['tilatut_rivit'] : 0;
        $vals['avoimet_rivit'] = isset($vals['avoimet_rivit']) ? $vals['avoimet_rivit'] : 0;

        echo "<tr class='{$id}_osasto tumma osasto' style='display:none;'>";
        echo "<td align='right'></td>";
        echo "<td align='right' class='toggleable' id='{$id}_{$osasto}_try'><img style='float:left;' id='img_{$id}_{$osasto}_try' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;{$osasto} ", t_avainsana("OSASTO", "", "and avainsana.selite ='{$osasto}'", "", "", "selitetark"), "</td>";
        echo "<td align='right'></td>";
        echo "<td align='right'>{$vals['tilatut_eurot']}</td>";
        echo "<td align='right'>{$tilatut_katepros}</td>";
        echo "<td align='right'>{$vals['tilatut_rivit']}</td>";
        echo "<td align='right'>{$vals['laskutetut_eurot']}</td>";
        echo "<td align='right'>{$laskutetut_katepros}</td>";
        echo "<td align='right'>{$vals['laskutetut_rivit']}</td>";
        if ($tavoitteet) {
          echo "<td align='right'>".round($vals['tavoite'], 2)."</td>";
        }
        echo "<td align='right'>{$vals['avoimet_eurot']}</td>";
        echo "<td align='right'>{$avoimet_katepros}</td>";
        echo "<td align='right'>{$vals['avoimet_rivit']}</td>";
        echo "</tr>";

        ksort($arr_kustp_osasto_try[$pvm][$kustp][$osasto]);

        foreach ($arr_kustp_osasto_try[$pvm][$kustp][$osasto] as $try => $vals) {

          if (!isset($vals['tilatut_eurot'])) $vals['tilatut_eurot'] = 0;
          if (!isset($vals['tilatut_kate'])) $vals['tilatut_kate'] = 0;
          if (!isset($vals['tilatut_rivit'])) $vals['tilatut_rivit'] = 0;
          if (!isset($vals['laskutetut_eurot'])) $vals['laskutetut_eurot'] = 0;
          if (!isset($vals['laskutetut_kate'])) $vals['laskutetut_kate'] = 0;
          if (!isset($vals['laskutetut_rivit'])) $vals['laskutetut_rivit'] = 0;
          if (!isset($vals['avoimet_eurot'])) $vals['avoimet_eurot'] = 0;
          if (!isset($vals['avoimet_kate'])) $vals['avoimet_kate'] = 0;
          if (!isset($vals['avoimet_rivit'])) $vals['avoimet_rivit'] = 0;

          $tilatut_katepros = $vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : 0;
          $laskutetut_katepros = (isset($vals['laskutetut_kate']) and isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != 0) ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : 0;
          $avoimet_katepros = $vals['avoimet_eurot'] != 0 ? round($vals['avoimet_kate'] / $vals['avoimet_eurot'] * 100, 1) : 0;

          if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_eurot']))   $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_eurot'] = 0;
          if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_kate']))     $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_kate'] = 0;
          if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_rivit']))   $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_rivit'] = 0;
          if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_eurot']))   $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_eurot'] = 0;
          if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_kate']))  $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_kate'] = 0;
          if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_rivit']))   $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_rivit'] = 0;
          if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tavoite']) and $tavoitteet)   $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tavoite'] = 0;
          if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['avoimet_eurot']))   $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['avoimet_eurot'] = 0;
          if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['avoimet_kate']))     $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['avoimet_kate'] = 0;
          if (!isset($yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['avoimet_rivit']))   $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['avoimet_rivit'] = 0;

          if ($tavoitteet) {
            if (!isset($vals['tavoite'])) {
              $vals['tavoite'] = 0;
            }
            $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tavoite'] += $vals['tavoite'];
          }
          $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_eurot']     += $vals['tilatut_eurot'];
          $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_kate']     += $vals['tilatut_kate'];
          $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['tilatut_rivit']     += $vals['tilatut_rivit'];
          $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_eurot']   += (isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != '') ? $vals['laskutetut_eurot'] : 0;
          $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_kate']   += (isset($vals['laskutetut_kate']) and $vals['laskutetut_kate'] != '') ? $vals['laskutetut_kate'] : 0;
          $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['laskutetut_rivit']   += (isset($vals['laskutetut_rivit']) and $vals['laskutetut_rivit'] != '') ? $vals['laskutetut_rivit'] : 0;
          $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['pvm'][$pvm]      = $pvm;
          $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['avoimet_eurot']     += $vals['avoimet_eurot'];
          $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['avoimet_kate']     += $vals['avoimet_kate'];
          $yhteensa_kustp_osasto_try[$kustp][$osasto][$try]['avoimet_rivit']     += $vals['avoimet_rivit'];

          if ($naytetaan_luvut == 'eurolleen') {
            $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot']) : 0;
            $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot']) : 0;
            $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot']) : 0;
            $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite']) : 0;
          }
          elseif ($naytetaan_luvut == 'sentilleen') {
            $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'], 2) : 0;
            $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'], 2) : 0;
            $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot'], 2) : 0;
            $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite'], 2) : 0;
          }
          else {
            $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'] / 1000) : 0;
            $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'] / 1000) : 0;
            $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot'] / 1000) : 0;
            $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite'] / 1000) : 0;
          }

          $vals['laskutetut_rivit'] = isset($vals['laskutetut_rivit']) ? $vals['laskutetut_rivit'] : 0;
          $vals['tilatut_rivit'] = isset($vals['tilatut_rivit']) ? $vals['tilatut_rivit'] : 0;
          $vals['avoimet_rivit'] = isset($vals['avoimet_rivit']) ? $vals['avoimet_rivit'] : 0;

          echo "<tr class='{$id}_{$osasto}_try spec try' style='display:none;'>";
          echo "<td align='right'></td>";
          echo "<td align='right'>{$try} ", t_avainsana("TRY", "", "and avainsana.selite ='{$try}'", "", "", "selitetark"), "</td>";
          echo "<td align='right'></td>";
          echo "<td align='right'>{$vals['tilatut_eurot']}</td>";
          echo "<td align='right'>{$tilatut_katepros}</td>";
          echo "<td align='right'>{$vals['tilatut_rivit']}</td>";
          echo "<td align='right'>{$vals['laskutetut_eurot']}</td>";
          echo "<td align='right'>{$laskutetut_katepros}</td>";
          echo "<td align='right'>{$vals['laskutetut_rivit']}</td>";
          if ($tavoitteet) {
            echo "<td align='right'>".round($vals['tavoite'], 2)."</td>";
          }
          echo "<td align='right'>{$vals['avoimet_eurot']}</td>";
          echo "<td align='right'>{$tavoimet_katepros}</td>";
          echo "<td align='right'>{$vals['avoimet_rivit']}</td>";
          echo "</tr>";
        }
      }
    }

    ksort($arr_osasto[$pvm]);

    foreach ($arr_osasto[$pvm] as $osasto => $vals) {

      if (!isset($vals['tilatut_eurot'])) $vals['tilatut_eurot'] = 0;
      if (!isset($vals['tilatut_kate'])) $vals['tilatut_kate'] = 0;
      if (!isset($vals['tilatut_rivit'])) $vals['tilatut_rivit'] = 0;
      if (!isset($vals['avoimet_eurot'])) $vals['avoimet_eurot'] = 0;
      if (!isset($vals['avoimet_kate'])) $vals['avoimet_kate'] = 0;
      if (!isset($vals['avoimet_rivit'])) $vals['avoimet_rivit'] = 0;

      $tilatut_katepros = $vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : 0;
      $laskutetut_katepros = (isset($vals['laskutetut_kate']) and isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != 0) ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : 0;
      $avoimet_katepros = $vals['avoimet_eurot'] != 0 ? round($vals['avoimet_kate'] / $vals['avoimet_eurot'] * 100, 1) : 0;

      if (!isset($yhteensa_osasto[$osasto]['tilatut_eurot'])) $yhteensa_osasto[$osasto]['tilatut_eurot'] = 0;
      if (!isset($yhteensa_osasto[$osasto]['tilatut_kate'])) $yhteensa_osasto[$osasto]['tilatut_kate'] = 0;
      if (!isset($yhteensa_osasto[$osasto]['tilatut_rivit'])) $yhteensa_osasto[$osasto]['tilatut_rivit'] = 0;
      if (!isset($yhteensa_osasto[$osasto]['laskutetut_eurot'])) $yhteensa_osasto[$osasto]['laskutetut_eurot'] = 0;
      if (!isset($yhteensa_osasto[$osasto]['laskutetut_kate'])) $yhteensa_osasto[$osasto]['laskutetut_kate'] = 0;
      if (!isset($yhteensa_osasto[$osasto]['laskutetut_rivit'])) $yhteensa_osasto[$osasto]['laskutetut_rivit'] = 0;
      if (!isset($yhteensa_osasto[$osasto]['tavoite']) and $tavoitteet) $yhteensa_osasto[$osasto]['tavoite'] = 0;
      if (!isset($yhteensa_osasto[$osasto]['avoimet_eurot'])) $yhteensa_osasto[$osasto]['avoimet_eurot'] = 0;
      if (!isset($yhteensa_osasto[$osasto]['avoimet_kate'])) $yhteensa_osasto[$osasto]['avoimet_kate'] = 0;
      if (!isset($yhteensa_osasto[$osasto]['avoimet_rivit'])) $yhteensa_osasto[$osasto]['avoimet_rivit'] = 0;

      if ($tavoitteet) {
        if (!isset($vals['tavoite'])) {
          $vals['tavoite'] = 0;
        }
        $yhteensa_osasto[$osasto]['tavoite']     += $vals['tavoite'];
      }
      $yhteensa_osasto[$osasto]['tilatut_eurot']     += $vals['tilatut_eurot'];
      $yhteensa_osasto[$osasto]['tilatut_kate']     += $vals['tilatut_kate'];
      $yhteensa_osasto[$osasto]['tilatut_rivit']     += $vals['tilatut_rivit'];
      $yhteensa_osasto[$osasto]['laskutetut_eurot']   += (isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != '') ? $vals['laskutetut_eurot'] : 0;
      $yhteensa_osasto[$osasto]['laskutetut_kate']   += (isset($vals['laskutetut_kate']) and $vals['laskutetut_kate'] != '') ? $vals['laskutetut_kate'] : 0;
      $yhteensa_osasto[$osasto]['laskutetut_rivit']   += (isset($vals['laskutetut_rivit']) and $vals['laskutetut_rivit'] != '') ? $vals['laskutetut_rivit'] : 0;
      $yhteensa_osasto[$osasto]['avoimet_eurot']     += $vals['avoimet_eurot'];
      $yhteensa_osasto[$osasto]['avoimet_kate']     += $vals['avoimet_kate'];
      $yhteensa_osasto[$osasto]['avoimet_rivit']     += $vals['avoimet_rivit'];
      $yhteensa_osasto[$osasto]['pvm'][$pvm]      = $pvm;

      if ($naytetaan_luvut == 'eurolleen') {
        $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot']) : 0;
        $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot']) : 0;
        $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite']) : 0;
        $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot']) : 0;
      }
      elseif ($naytetaan_luvut == 'sentilleen') {
        $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'], 2) : 0;
        $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'], 2) : 0;
        $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite'], 2) : 0;
        $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot'], 2) : 0;
      }
      else {
        $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'] / 1000) : 0;
        $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'] / 1000) : 0;
        $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite'] / 1000) : 0;
        $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot'] / 1000) : 0;
      }

      $vals['laskutetut_rivit'] = isset($vals['laskutetut_rivit']) ? $vals['laskutetut_rivit'] : 0;
      $vals['tilatut_rivit'] = isset($vals['tilatut_rivit']) ? $vals['tilatut_rivit'] : 0;
      $vals['avoimet_rivit'] = isset($vals['avoimet_rivit']) ? $vals['avoimet_rivit'] : 0;

      echo "<tr class='{$pvm}_osasto tumma osasto' style='display:none;'>";
      echo "<td align='right'></td>";
      echo "<td align='right' class='toggleable' id='{$pvm}_{$osasto}_try'><img style='float:left;' id='img_{$pvm}_{$osasto}_try' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;{$osasto} ", t_avainsana("OSASTO", "", "and avainsana.selite ='{$osasto}'", "", "", "selitetark"), "</td>";
      echo "<td align='right'></td>";
      echo "<td align='right'>{$vals['tilatut_eurot']}</td>";
      echo "<td align='right'>{$tilatut_katepros}</td>";
      echo "<td align='right'>{$vals['tilatut_rivit']}</td>";
      echo "<td align='right'>{$vals['laskutetut_eurot']}</td>";
      echo "<td align='right'>{$laskutetut_katepros}</td>";
      echo "<td align='right'>{$vals['laskutetut_rivit']}</td>";
      if ($tavoitteet) {
        echo "<td align='right'>".round($vals['tavoite'], 2)."</td>";
      }
      echo "<td align='right'>{$vals['avoimet_eurot']}</td>";
      echo "<td align='right'>{$avoimet_katepros}</td>";
      echo "<td align='right'>{$vals['avoimet_rivit']}</td>";
      echo "</tr>";

      ksort($arr_try[$pvm][$osasto]);

      foreach ($arr_try[$pvm][$osasto] as $try => $vals) {

        if (!isset($vals['tilatut_eurot'])) $vals['tilatut_eurot'] = 0;
        if (!isset($vals['tilatut_kate'])) $vals['tilatut_kate'] = 0;
        if (!isset($vals['tilatut_rivit'])) $vals['tilatut_rivit'] = 0;
        if (!isset($vals['laskutetut_eurot'])) $vals['laskutetut_eurot'] = 0;
        if (!isset($vals['laskutetut_kate'])) $vals['laskutetut_kate'] = 0;
        if (!isset($vals['laskutetut_rivit'])) $vals['laskutetut_rivit'] = 0;
        if (!isset($vals['avoimet_eurot'])) $vals['avoimet_eurot'] = 0;
        if (!isset($vals['avoimet_kate'])) $vals['avoimet_kate'] = 0;
        if (!isset($vals['avoimet_rivit'])) $vals['avoimet_rivit'] = 0;

        $tilatut_katepros = $vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : 0;
        $laskutetut_katepros = (isset($vals['laskutetut_kate']) and isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != 0) ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : 0;
        $avoimet_katepros = $vals['avoimet_eurot'] != 0 ? round($vals['avoimet_kate'] / $vals['avoimet_eurot'] * 100, 1) : 0;

        if (!isset($yhteensa_try[$osasto][$try]['tilatut_eurot']))     $yhteensa_try[$osasto][$try]['tilatut_eurot'] = 0;
        if (!isset($yhteensa_try[$osasto][$try]['tilatut_kate']))     $yhteensa_try[$osasto][$try]['tilatut_kate'] = 0;
        if (!isset($yhteensa_try[$osasto][$try]['tilatut_rivit']))     $yhteensa_try[$osasto][$try]['tilatut_rivit'] = 0;
        if (!isset($yhteensa_try[$osasto][$try]['laskutetut_eurot']))   $yhteensa_try[$osasto][$try]['laskutetut_eurot'] = 0;
        if (!isset($yhteensa_try[$osasto][$try]['laskutetut_kate']))  $yhteensa_try[$osasto][$try]['laskutetut_kate'] = 0;
        if (!isset($yhteensa_try[$osasto][$try]['laskutetut_rivit']))   $yhteensa_try[$osasto][$try]['laskutetut_rivit'] = 0;
        if (!isset($yhteensa_try[$osasto][$try]['avoimet_eurot']))     $yhteensa_try[$osasto][$try]['avoimet_eurot'] = 0;
        if (!isset($yhteensa_try[$osasto][$try]['avoimet_kate']))     $yhteensa_try[$osasto][$try]['avoimet_kate'] = 0;
        if (!isset($yhteensa_try[$osasto][$try]['avoimet_rivit']))     $yhteensa_try[$osasto][$try]['avoimet_rivit'] = 0;
        if (!isset($yhteensa_try[$osasto][$try]['tavoite']) and $tavoitteet)     $yhteensa_try[$osasto][$try]['tavoite'] = 0;

        if ($tavoitteet) {
          if (!isset($vals['tavoite'])) {
            $vals['tavoite'] = 0;
          }
          $yhteensa_try[$osasto][$try]['tavoite']     += $vals['tavoite'];
        }
        $yhteensa_try[$osasto][$try]['tilatut_eurot']     += $vals['tilatut_eurot'];
        $yhteensa_try[$osasto][$try]['tilatut_kate']     += $vals['tilatut_kate'];
        $yhteensa_try[$osasto][$try]['tilatut_rivit']     += $vals['tilatut_rivit'];
        $yhteensa_try[$osasto][$try]['laskutetut_eurot']   += (isset($vals['laskutetut_eurot']) and $vals['laskutetut_eurot'] != '') ? $vals['laskutetut_eurot'] : 0;
        $yhteensa_try[$osasto][$try]['laskutetut_kate']   += (isset($vals['laskutetut_kate']) and $vals['laskutetut_kate'] != '') ? $vals['laskutetut_kate'] : 0;
        $yhteensa_try[$osasto][$try]['laskutetut_rivit']   += (isset($vals['laskutetut_rivit']) and $vals['laskutetut_rivit'] != '') ? $vals['laskutetut_rivit'] : 0;
        $yhteensa_try[$osasto][$try]['avoimet_eurot']     += $vals['avoimet_eurot'];
        $yhteensa_try[$osasto][$try]['avoimet_kate']     += $vals['avoimet_kate'];
        $yhteensa_try[$osasto][$try]['avoimet_rivit']     += $vals['avoimet_rivit'];
        $yhteensa_try[$osasto][$try]['pvm'][$pvm]      = $pvm;

        if ($naytetaan_luvut == 'eurolleen') {
          $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot']) : 0;
          $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot']) : 0;
          $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite']) : 0;
          $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot']) : 0;
        }
        elseif ($naytetaan_luvut == 'sentilleen') {
          $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'], 2) : 0;
          $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'], 2) : 0;
          $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite'], 2) : 0;
          $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot'], 2) : 0;
        }
        else {
          $vals['tilatut_eurot'] = $vals['tilatut_eurot'] != '' ? round($vals['tilatut_eurot'] / 1000) : 0;
          $vals['laskutetut_eurot'] = isset($vals['laskutetut_eurot']) ? round($vals['laskutetut_eurot'] / 1000) : 0;
          $vals['tavoite'] = isset($vals['tavoite']) ? round($vals['tavoite'] / 1000) : 0;
          $vals['avoimet_eurot'] = $vals['avoimet_eurot'] != '' ? round($vals['avoimet_eurot'] / 1000) : 0;
        }

        $vals['laskutetut_rivit'] = isset($vals['laskutetut_rivit']) ? $vals['laskutetut_rivit'] : 0;
        $vals['tilatut_rivit'] = isset($vals['tilatut_rivit']) ? $vals['tilatut_rivit'] : 0;
        $vals['avoimet_rivit'] = isset($vals['avoimet_rivit']) ? $vals['avoimet_rivit'] : 0;

        echo "<tr class='{$pvm}_{$osasto}_try spec try' style='display:none;'>";
        echo "<td align='right'></td>";
        echo "<td align='right'>{$try} ", t_avainsana("TRY", "", "and avainsana.selite ='{$try}'", "", "", "selitetark"), "</td>";
        echo "<td align='right'></td>";
        echo "<td align='right'>{$vals['tilatut_eurot']}</td>";
        echo "<td align='right'>{$tilatut_katepros}</td>";
        echo "<td align='right'>{$vals['tilatut_rivit']}</td>";
        echo "<td align='right'>{$vals['laskutetut_eurot']}</td>";
        echo "<td align='right'>{$laskutetut_katepros}</td>";
        echo "<td align='right'>{$vals['laskutetut_rivit']}</td>";
        if ($tavoitteet) {
          echo "<td align='right'>".round($vals['tavoite'], 2)."</td>";
        }
        echo "<td align='right'>{$vals['avoimet_eurot']}</td>";
        echo "<td align='right'>{$avoimet_katepros}</td>";
        echo "<td align='right'>{$vals['avoimet_rivit']}</td>";
        echo "</tr>";
      }
    }
  }

  echo "<tr class='aktiivi'>";
  echo "<th class='toggleable' id='yhteensa_kustp'><img style='float:left;' id='img_yhteensa_kustp' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;", t("Yhteens‰"), "<br />", t("Kustp"), "</th>";
  echo "<th class='toggleable' id='yhteensa_osasto'><img style='float:left;' id='img_yhteensa_osasto' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;", t("Yhteens‰"), "<br />", t("os / try"), "</th>";
  echo "<td align='right'></td>";

  echo "<td align='right'>";

  if ($naytetaan_luvut == 'eurolleen') {
    echo round($yhteensa['tilatut_eurot']);
  }
  elseif ($naytetaan_luvut == 'sentilleen') {
    echo round($yhteensa['tilatut_eurot'], 2);
  }
  else {
    echo round($yhteensa['tilatut_eurot'] / 1000);
  }

  echo "</td>";

  echo "<td align='right'>", round($yhteensa['tilatut_kate'] / $yhteensa['tilatut_eurot'] * 100, 1), "</td>";
  echo "<td align='right'>", round($yhteensa['tilatut_rivit']), "</td>";

  echo "<td align='right'>";

  if ($naytetaan_luvut == 'eurolleen') {
    echo round($yhteensa['laskutetut_eurot']);
  }
  elseif ($naytetaan_luvut == 'sentilleen') {
    echo round($yhteensa['laskutetut_eurot'], 2);
  }
  else {
    echo round($yhteensa['laskutetut_eurot'] / 1000);
  }

  echo "</td>";

  echo "<td align='right'>", (round($yhteensa['laskutetut_kate'] / $yhteensa['laskutetut_eurot'] * 100, 1)), "</td>";
  echo "<td align='right'>", round($yhteensa['laskutetut_rivit']), "</td>";

  if ($tavoitteet) {
    echo "<td align='right'>";
    if ($naytetaan_luvut == 'eurolleen') {
      echo round($yhteensa['tavoite']);
    }
    elseif ($naytetaan_luvut == 'sentilleen') {
      echo round($yhteensa['tavoite'], 2);
    }
    else {
      echo round($yhteensa['tavoite'] / 1000);
    }
    echo "</td>";
  }

  echo "<td align='right'>";

  if ($naytetaan_luvut == 'eurolleen') {
    echo round($yhteensa['avoimet_eurot']);
  }
  elseif ($naytetaan_luvut == 'sentilleen') {
    echo round($yhteensa['avoimet_eurot'], 2);
  }
  else {
    echo round($yhteensa['avoimet_eurot'] / 1000);
  }

  echo "</td>";

  echo "<td align='right'>", round($yhteensa['avoimet_kate'] / $yhteensa['avoimet_eurot'] * 100, 1), "</td>";
  echo "<td align='right'>", round($yhteensa['avoimet_rivit']), "</td>";

  echo "</tr>";

  ksort($yhteensa_kustp);

  foreach ($yhteensa_kustp as $kustp => $vals) {

    $_kustp = $kustp;

    if ($kustp == '') $_kustp = t("Ei kustannuspaikkaa");
    $kustp_id = sanitoi_javascript_id($kustp);

    echo "<tr class='yhteensa_kustp aktiivi' style='display:none;'>";
    echo "<th class='toggleable' id='yhteensa_{$kustp_id}_osasto'><img style='float:left;' id='img_yhteensa_{$kustp_id}_osasto' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;", t("Yhteens‰"), " {$_kustp}</th>";
    echo "<td align='right'></td>";
    echo "<td align='right'></td>";

    echo "<td align='right'>";

    if ($naytetaan_luvut == 'eurolleen') {
      echo round($vals['tilatut_eurot']);
    }
    elseif ($naytetaan_luvut == 'sentilleen') {
      echo round($vals['tilatut_eurot'], 2);
    }
    else {
      echo round($vals['tilatut_eurot'] / 1000);
    }

    echo "</td>";

    echo "<td align='right'>", ($vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : 0), "</td>";
    echo "<td align='right'>", round($vals['tilatut_rivit']), "</td>";

    echo "<td align='right'>";

    if ($naytetaan_luvut == 'eurolleen') {
      echo round($vals['laskutetut_eurot']);
    }
    elseif ($naytetaan_luvut == 'sentilleen') {
      echo round($vals['laskutetut_eurot'], 2);
    }
    else {
      echo round($vals['laskutetut_eurot'] / 1000);
    }

    echo "</td>";

    echo "<td align='right'>", ($vals['laskutetut_eurot'] != 0 ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : 0), "</td>";
    echo "<td align='right'>", round($vals['laskutetut_rivit']), "</td>";

    if ($tavoitteet) {
      echo "<td align='right'>";
      if ($naytetaan_luvut == 'eurolleen') {
        echo round($yhteensa['tavoite']);
      }
      elseif ($naytetaan_luvut == 'sentilleen') {
        echo round($yhteensa['tavoite'], 2);
      }
      else {
        echo round($yhteensa['tavoite'] / 1000);
      }
      echo "</td>";
    }

    echo "<td align='right'>";

    if ($naytetaan_luvut == 'eurolleen') {
      echo round($vals['avoimet_eurot']);
    }
    elseif ($naytetaan_luvut == 'sentilleen') {
      echo round($vals['avoimet_eurot'], 2);
    }
    else {
      echo round($vals['avoimet_eurot'] / 1000);
    }

    echo "</td>";

    echo "<td align='right'>", ($vals['avoimet_eurot'] != 0 ? round($vals['avoimet_kate'] / $vals['avoimet_eurot'] * 100, 1) : 0), "</td>";
    echo "<td align='right'>", round($vals['avoimet_rivit']), "</td>";

    echo "</tr>";

    ksort($yhteensa_kustp_osasto);

    unset($osasto);

    foreach ($yhteensa_kustp_osasto[$kustp] as $osasto => $vals) {

      $_osasto = $osasto == '' ? t("Ei osastoa") : $osasto;

      $id = sanitoi_javascript_id("{$kustp}_{$osasto}");

      echo "<tr class='yhteensa_{$kustp_id}_osasto aktiivi osasto' style='display:none;'>";
      echo "<td align='right'></td>";
      echo "<th class='toggleable' id='yhteensa_{$id}_try'><img style='float:left;' id='img_{$id}_try' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;", t("Yhteens‰"), " {$_osasto} ", t_avainsana("OSASTO", "", "and avainsana.selite ='{$osasto}'", "", "", "selitetark"), "</th>";
      echo "<td align='right'></td>";

      echo "<td align='right'>";

      if ($naytetaan_luvut == 'eurolleen') {
        echo round($vals['tilatut_eurot']);
      }
      elseif ($naytetaan_luvut == 'sentilleen') {
        echo round($vals['tilatut_eurot'], 2);
      }
      else {
        echo round($vals['tilatut_eurot'] / 1000);
      }

      echo "</td>";

      echo "<td align='right'>", ($vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : 0), "</td>";
      echo "<td align='right'>", round($vals['tilatut_rivit']), "</td>";

      echo "<td align='right'>";

      if ($naytetaan_luvut == 'eurolleen') {
        echo round($vals['laskutetut_eurot']);
      }
      elseif ($naytetaan_luvut == 'sentilleen') {
        echo round($vals['laskutetut_eurot'], 2);
      }
      else {
        echo round($vals['laskutetut_eurot'] / 1000);
      }

      echo "</td>";

      echo "<td align='right'>", ($vals['laskutetut_eurot'] != 0 ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : 0), "</td>";
      echo "<td align='right'>", round($vals['laskutetut_rivit']), "</td>";

      if ($tavoitteet) {
        echo "<td align='right'>";
        if ($naytetaan_luvut == 'eurolleen') {
          echo round($yhteensa['tavoite']);
        }
        elseif ($naytetaan_luvut == 'sentilleen') {
          echo round($yhteensa['tavoite'], 2);
        }
        else {
          echo round($yhteensa['tavoite'] / 1000);
        }
        echo "</td>";
      }

      echo "<td align='right'>";

      if ($naytetaan_luvut == 'eurolleen') {
        echo round($vals['avoimet_eurot']);
      }
      elseif ($naytetaan_luvut == 'sentilleen') {
        echo round($vals['avoimet_eurot'], 2);
      }
      else {
        echo round($vals['avoimet_eurot'] / 1000);
      }

      echo "</td>";

      echo "<td align='right'>", ($vals['avoimet_eurot'] != 0 ? round($vals['avoimet_kate'] / $vals['avoimet_eurot'] * 100, 1) : 0), "</td>";
      echo "<td align='right'>", round($vals['avoimet_rivit']), "</td>";

      echo "</tr>";

      unset($try);

      foreach ($yhteensa_kustp_osasto_try[$kustp][$osasto] as $try => $vals) {

        if ($try == '') $try = t("Ei tuoteryhm‰‰");

        echo "<tr class='yhteensa_{$id}_try spec aktiivi try' style='display:none;'>";
        echo "<td align='right'></td>";
        echo "<td align='left' class='tumma'>", t("Yhteens‰"), " {$try} ", t_avainsana("TRY", "", "and avainsana.selite ='{$try}'", "", "", "selitetark"), "</td>";
        echo "<td align='right'></td>";

        echo "<td align='right'>";

        if ($naytetaan_luvut == 'eurolleen') {
          echo round($vals['tilatut_eurot']);
        }
        elseif ($naytetaan_luvut == 'sentilleen') {
          echo round($vals['tilatut_eurot'], 2);
        }
        else {
          echo round($vals['tilatut_eurot'] / 1000);
        }

        echo "</td>";

        echo "<td align='right'>", ($vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : 0), "</td>";
        echo "<td align='right'>", round($vals['tilatut_rivit']), "</td>";

        echo "<td align='right'>";

        if ($naytetaan_luvut == 'eurolleen') {
          echo round($vals['laskutetut_eurot']);
        }
        elseif ($naytetaan_luvut == 'sentilleen') {
          echo round($vals['laskutetut_eurot'], 2);
        }
        else {
          echo round($vals['laskutetut_eurot'] / 1000);
        }

        echo "</td>";

        echo "<td align='right'>", ($vals['laskutetut_eurot'] != 0 ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : 0), "</td>";
        echo "<td align='right'>", round($vals['laskutetut_rivit']), "</td>";

        if ($tavoitteet) {
          echo "<td align='right'>";
          if ($naytetaan_luvut == 'eurolleen') {
            echo round($yhteensa['tavoite']);
          }
          elseif ($naytetaan_luvut == 'sentilleen') {
            echo round($yhteensa['tavoite'], 2);
          }
          else {
            echo round($yhteensa['tavoite'] / 1000);
          }
          echo "</td>";
        }

        echo "<td align='right'>";

        if ($naytetaan_luvut == 'eurolleen') {
          echo round($vals['avoimet_eurot']);
        }
        elseif ($naytetaan_luvut == 'sentilleen') {
          echo round($vals['avoimet_eurot'], 2);
        }
        else {
          echo round($vals['avoimet_eurot'] / 1000);
        }

        echo "</td>";

        echo "<td align='right'>", ($vals['avoimet_eurot'] != 0 ? round($vals['avoimet_kate'] / $vals['avoimet_eurot'] * 100, 1) : 0), "</td>";
        echo "<td align='right'>", round($vals['avoimet_rivit']), "</td>";

        echo "</tr>";
      }
    }
  }

  ksort($yhteensa_osasto);

  unset($osasto);

  foreach ($yhteensa_osasto as $osasto => $vals) {

    $_osasto = $osasto == '' ? t("Ei osastoa") : $osasto;

    echo "<tr class='yhteensa_osasto aktiivi osasto' style='display:none;'>";
    echo "<td align='right'></td>";
    echo "<th class='toggleable' id='{$osasto}_try'><img style='float:left;' id='img_{$osasto}_try' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;", t("Yhteens‰"), " {$_osasto} ", t_avainsana("OSASTO", "", "and avainsana.selite ='{$osasto}'", "", "", "selitetark"), "</th>";
    echo "<td align='right'></td>";

    echo "<td align='right'>";

    if ($naytetaan_luvut == 'eurolleen') {
      echo round($vals['tilatut_eurot']);
    }
    elseif ($naytetaan_luvut == 'sentilleen') {
      echo round($vals['tilatut_eurot'], 2);
    }
    else {
      echo round($vals['tilatut_eurot'] / 1000);
    }

    echo "</td>";

    echo "<td align='right'>", ($vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : 0), "</td>";
    echo "<td align='right'>", round($vals['tilatut_rivit']), "</td>";

    echo "<td align='right'>";

    if ($naytetaan_luvut == 'eurolleen') {
      echo round($vals['laskutetut_eurot']);
    }
    elseif ($naytetaan_luvut == 'sentilleen') {
      echo round($vals['laskutetut_eurot'], 2);
    }
    else {
      echo round($vals['laskutetut_eurot'] / 1000);
    }

    echo "</td>";

    echo "<td align='right'>", ($vals['laskutetut_eurot'] != 0 ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : 0), "</td>";
    echo "<td align='right'>", round($vals['laskutetut_rivit']), "</td>";

    if ($tavoitteet) {
      echo "<td align='right'>";
      if ($naytetaan_luvut == 'eurolleen') {
        echo round($yhteensa['tavoite']);
      }
      elseif ($naytetaan_luvut == 'sentilleen') {
        echo round($yhteensa['tavoite'], 2);
      }
      else {
        echo round($yhteensa['tavoite'] / 1000);
      }
      echo "</td>";
    }

    echo "<td align='right'>";
    if ($naytetaan_luvut == 'eurolleen') {
      echo round($vals['avoimet_eurot']);
    }
    elseif ($naytetaan_luvut == 'sentilleen') {
      echo round($vals['avoimet_eurot'], 2);
    }
    else {
      echo round($vals['avoimet_eurot'] / 1000);
    }

    echo "</td>";

    echo "<td align='right'>", ($vals['avoimet_eurot'] != 0 ? round($vals['avoimet_kate'] / $vals['avoimet_eurot'] * 100, 1) : 0), "</td>";
    echo "<td align='right'>", round($vals['avoimet_rivit']), "</td>";

    echo "</tr>";

    unset($try);

    foreach ($yhteensa_try[$osasto] as $try => $vals) {

      if ($try == '') $try = t("Ei tuoteryhm‰‰");

      echo "<tr class='{$osasto}_try spec aktiivi try' style='display:none;'>";
      echo "<td align='right'></td>";
      echo "<td align='left' class='tumma'>", t("Yhteens‰"), " {$try} ", t_avainsana("TRY", "", "and avainsana.selite ='{$try}'", "", "", "selitetark"), "</td>";
      echo "<td align='right'></td>";

      echo "<td align='right'>";

      if ($naytetaan_luvut == 'eurolleen') {
        echo round($vals['tilatut_eurot']);
      }
      elseif ($naytetaan_luvut == 'sentilleen') {
        echo round($vals['tilatut_eurot'], 2);
      }
      else {
        echo round($vals['tilatut_eurot'] / 1000);
      }

      echo "</td>";

      echo "<td align='right'>", ($vals['tilatut_eurot'] != 0 ? round($vals['tilatut_kate'] / $vals['tilatut_eurot'] * 100, 1) : 0), "</td>";
      echo "<td align='right'>", round($vals['tilatut_rivit']), "</td>";

      echo "<td align='right'>";

      if ($naytetaan_luvut == 'eurolleen') {
        echo round($vals['laskutetut_eurot']);
      }
      elseif ($naytetaan_luvut == 'sentilleen') {
        echo round($vals['laskutetut_eurot'], 2);
      }
      else {
        echo round($vals['laskutetut_eurot'] / 1000);
      }

      echo "</td>";

      echo "<td align='right'>", ($vals['laskutetut_eurot'] != 0 ? round($vals['laskutetut_kate'] / $vals['laskutetut_eurot'] * 100, 1) : 0), "</td>";
      echo "<td align='right'>", round($vals['laskutetut_rivit']), "</td>";

      if ($tavoitteet) {
        echo "<td align='right'>";
        if ($naytetaan_luvut == 'eurolleen') {
          echo round($yhteensa['tavoite']);
        }
        elseif ($naytetaan_luvut == 'sentilleen') {
          echo round($yhteensa['tavoite'], 2);
        }
        else {
          echo round($yhteensa['tavoite'] / 1000);
        }
        echo "</td>";
      }

      echo "<td align='right'>";

      if ($naytetaan_luvut == 'eurolleen') {
        echo round($vals['avoimet_eurot']);
      }
      elseif ($naytetaan_luvut == 'sentilleen') {
        echo round($vals['avoimet_eurot'], 2);
      }
      else {
        echo round($vals['avoimet_eurot'] / 1000);
      }

      echo "</td>";

      echo "<td align='right'>", ($vals['avovimet_eurot'] != 0 ? round($vals['avoimet_kate'] / $vals['avoimet_eurot'] * 100, 1) : 0), "</td>";
      echo "<td align='right'>", round($vals['avoimet_rivit']), "</td>";

      echo "</tr>";
    }
  }

  echo "</table>";
}

require "inc/footer.inc";
