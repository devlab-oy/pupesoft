<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (!isset($errors)) $errors = array();
if (!isset($viivakoodi)) $viivakoodi = "";
if (!isset($_viivakoodi)) $_viivakoodi = "";
if (!isset($orig_tilausten_lukumaara)) $orig_tilausten_lukumaara = 0;
if (!isset($saapumisnro_haku)) $saapumisnro_haku = '';

$sort_by_direction_tuoteno     = (!isset($sort_by_direction_tuoteno) or $sort_by_direction_tuoteno == 'asc') ? 'desc' : 'asc';
$sort_by_direction_otunnus     = (!isset($sort_by_direction_otunnus) or $sort_by_direction_otunnus == 'asc') ? 'desc' : 'asc';
$sort_by_direction_sorttaus_kpl  = (!isset($sort_by_direction_sorttaus_kpl) or $sort_by_direction_sorttaus_kpl == 'asc') ? 'desc' : 'asc';
$sort_by_direction_hylly    = (!isset($sort_by_direction_hylly) or $sort_by_direction_hylly == 'asc') ? 'desc' : 'asc';

$viivakoodi = (isset($_viivakoodi) and $_viivakoodi != "") ? $_viivakoodi : $viivakoodi;

$params = array();
$saapumisnro_haku_lisa = "";

// Joku parametri tarvii olla setattu.
if ($ostotilaus != '' or $tuotenumero != '' or $viivakoodi != '' or $saapumisnro_haku != '') {

  if (strpos($tuotenumero, "%") !== FALSE) $tuotenumero = urldecode($tuotenumero);

  if ($tuotenumero != '') $params['tuoteno'] = "tilausrivi.tuoteno = '{$tuotenumero}'";
  if ($ostotilaus != '')   $params['otunnus'] = "tilausrivi.otunnus = '{$ostotilaus}'";
  if ($saapumisnro_haku != '') $saapumisnro_haku_lisa = "AND lasku.laskunro = '{$saapumisnro_haku}'";

  // Viivakoodi case
  if ($viivakoodi != '') {
    $tuotenumerot = hae_viivakoodilla($viivakoodi);

    if (count($tuotenumerot) > 0) {

      $param_viivakoodi = array();

      foreach ($tuotenumerot as $_tuoteno => $_arr) {
        foreach ($_arr as $_liitostunnus) {
          if (trim($_liitostunnus) != "") {
            array_push($param_viivakoodi, "(tilausrivi.tuoteno = '{$_tuoteno}' AND lasku.liitostunnus = '{$_liitostunnus}')");
          }
        }
      }

      if (empty($param_viivakoodi)) {
        $params['viivakoodi'] = "tilausrivi.tuoteno IN ('".implode(array_keys($tuotenumerot), "','")."')";
      }
      else {
        $params['viivakoodi'] = "(".implode($param_viivakoodi, " OR ").")";
      }
    }
    else {
      $errors[] = t("Viivakoodilla %s ei löytynyt tuotetta", '', $viivakoodi)."<br />";
      $viivakoodi = "";
    }
  }

  $query_lisa = count($params) > 0 ? " AND ".implode($params, " AND ") : "";

}
else {
  // Tänne ei pitäis päätyä, tarkistetaan jo ostotilaus.php:ssä
  echo t("Parametrivirhe");
  echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=ostotilaus.php'>";
  exit();
}

if ($_viivakoodi == $viivakoodi) $viivakoodi = "";

// Tarkistetaan onko käyttäjällä kesken saapumista
$keskeneraiset_query = "SELECT kuka.kesken FROM lasku
                        JOIN kuka ON (lasku.tunnus=kuka.kesken and lasku.yhtio=kuka.yhtio)
                        WHERE kuka='{$kukarow['kuka']}'
                        and kuka.yhtio='{$kukarow['yhtio']}'
                        and lasku.tila='K'";
$keskeneraiset = mysql_fetch_assoc(pupe_query($keskeneraiset_query));

// Jos kuka.kesken on saapuminen, käytetään sitä
if ($keskeneraiset['kesken'] != 0) {
  $saapuminen = $keskeneraiset['kesken'];
}

$orderby = "tilausrivi_tyyppi DESC, tuoteno, ostotilaus, sorttaus_kpl";
$ascdesc = "desc";

if (isset($sort_by)) {

  switch ($sort_by) {
  case 'tuoteno':
  case 'otunnus':
  case 'sorttaus_kpl':
  case 'hylly':
    $orderby = $sort_by;
    $ascdesc = ${"sort_by_direction_{$sort_by}"};
    break;
  default:
    break;
  }
}

if (!empty($saapumisnro_haku_lisa)) {
  $where_lisa = "AND lasku.tila = 'K'
                 AND lasku.alatila = ''
                 {$saapumisnro_haku_lisa}";

  $join_lisa = "AND tilausrivi.uusiotunnus = lasku.tunnus
                AND tilausrivi.suuntalava = 0";

  $lasku_join_lisa = "JOIN lasku AS lasku_osto ON (lasku_osto.yhtio = tilausrivi.yhtio
                        AND lasku_osto.tunnus = tilausrivi.otunnus
                        AND lasku_osto.tila = 'O' AND lasku_osto.alatila = 'A')";

  $select_lisa = "lasku_osto.tunnus AS ostotilaus,";
}
else {
  $where_lisa = "AND ((lasku.tila = 'K' AND lasku.alatila = '') or (lasku.tila='O' AND lasku.alatila ='A'))";

  $join_lisa = "AND tilausrivi.otunnus = lasku.tunnus
                AND (tilausrivi.uusiotunnus = 0 OR tilausrivi.suuntalava = 0)";

  $lasku_join_lisa = "";

  $select_lisa = "lasku.tunnus as ostotilaus,";
}

// Haetaan ostotilaukset
$query = "SELECT
          {$select_lisa}
          lasku.liitostunnus,
          tilausrivi.tunnus,
          tilausrivi.otunnus,
          tilausrivi.tuoteno,
          tilausrivi.varattu,
          tilausrivi.kpl,
          (tilausrivi.varattu + tilausrivi.kpl) as sorttaus_kpl,
          tilausrivi.tilkpl,
          tilausrivi.uusiotunnus,
          concat_ws('-',tilausrivi.hyllyalue,tilausrivi.hyllynro,tilausrivi.hyllyvali,tilausrivi.hyllytaso) as hylly,
          IF(IFNULL(tilausrivin_lisatiedot.suoraan_laskutukseen, 'NORM') = '', 'JT', IFNULL(tilausrivin_lisatiedot.suoraan_laskutukseen, '')) as tilausrivi_tyyppi
          FROM lasku
          JOIN tilausrivi ON (tilausrivi.yhtio=lasku.yhtio
            AND tilausrivi.tyyppi                        = 'O'
            AND tilausrivi.varattu                      != 0
            {$join_lisa})
          {$lasku_join_lisa}
          LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
            AND tilausrivin_lisatiedot.tilausrivilinkki  = tilausrivi.tunnus
            AND tilausrivin_lisatiedot.tilausrivilinkki  <> 0)
          WHERE lasku.yhtio                              = '{$kukarow['yhtio']}'
          AND lasku.vanhatunnus                          = '{$kukarow['toimipaikka']}'
          {$where_lisa}
          {$query_lisa}
          ORDER BY {$orderby} {$ascdesc}";
$result = pupe_query($query);
$tilausten_lukumaara = mysql_num_rows($result);

if ($orig_tilausten_lukumaara == 0) $orig_tilausten_lukumaara = $tilausten_lukumaara;

// Jos etsitään viivakoodilla ja kyseistä tuotetta ei löydy esim. ostotilaukselta, tehdään uusi haku ilman viivakoodia
if ($tilausten_lukumaara == 0 and (isset($_viivakoodi) and $_viivakoodi != "") and count($params) > 1) {

  $errors[] = t("Viivakoodilla %s ei löytynyt tuotetta", '', $_viivakoodi)."<br />";

  unset($params['viivakoodi']);

  $query_lisa = " AND ".implode($params, " AND ");

  $query = "SELECT
            {$select_lisa}
            lasku.liitostunnus,
            tilausrivi.tunnus,
            tilausrivi.otunnus,
            tilausrivi.tuoteno,
            tilausrivi.varattu,
            tilausrivi.kpl,
            (tilausrivi.varattu + tilausrivi.kpl) as sorttaus_kpl,
            tilausrivi.tilkpl,
            tilausrivi.uusiotunnus,
            concat_ws('-',tilausrivi.hyllyalue,tilausrivi.hyllynro,tilausrivi.hyllyvali,tilausrivi.hyllytaso) as hylly,
            IF(IFNULL(tilausrivin_lisatiedot.suoraan_laskutukseen, 'NORM') = '', 'JT', IFNULL(tilausrivin_lisatiedot.suoraan_laskutukseen, '')) as tilausrivi_tyyppi
            FROM lasku
            JOIN tilausrivi ON (tilausrivi.yhtio=lasku.yhtio
              AND tilausrivi.tyyppi                        = 'O'
              AND tilausrivi.varattu                      != 0
              {$join_lisa})
            {$lasku_join_lisa}
            LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivilinkki  = tilausrivi.tunnus
              AND tilausrivin_lisatiedot.tilausrivilinkki  <> 0)
            WHERE lasku.yhtio                              = '{$kukarow['yhtio']}'
            AND lasku.vanhatunnus                          = '{$kukarow['toimipaikka']}'
            {$where_lisa}
            {$query_lisa}
            ORDER BY {$orderby} {$ascdesc}";
  $result = pupe_query($query);
  $tilausten_lukumaara = mysql_num_rows($result);
}

$tilaukset = mysql_fetch_assoc($result);

// Submit
if (isset($submit)) {
  switch ($submit) {
  case 'ok':

    if (empty($tilausrivi)) {
      $errors[] = t("Valitse rivi");
      break;
    }

    $url_array['ostotilaus'] = $ostotilaus;
    $url_array['tilausrivi'] = $tilausrivi;
    $url_array['saapuminen'] = $saapuminen;
    $url_array['saapumisnro_haku'] = $saapumisnro_haku;

    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys.php?".http_build_query($url_array)."'>"; exit();

    break;
  case 'cancel':
    echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php?ostotilaus={$ostotilaus}&backsaapuminen={$backsaapuminen}&saapumisnro_haku={$saapumisnro_haku}'>";
    exit;
  default:
    echo "Virhe";
    break;
  }
}

// Ei osumia, palataan ostotilaus sivulle
if ($tilausten_lukumaara == 0) {
  echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=ostotilaus.php?tuotenumero={$tuotenumero}&ostotilaus={$ostotilaus}&saapumisnro_haku={$saapumisnro_haku}&virhe'>";
  exit();
}

// Jos vain yksi osuma, mennään suoraan hyllytykseen;
if ($tilausten_lukumaara == 1 and $orig_tilausten_lukumaara == 1 and $_viivakoodi == "") {

  $url_array['tilausrivi'] = $tilaukset['tunnus'];
  $url_array['ostotilaus'] = empty($ostotilaus) ? $tilaukset['otunnus'] : $ostotilaus;
  $url_array['saapuminen'] = $saapuminen;
  $url_array['manuaalisesti_syotetty_ostotilausnro'] = empty($manuaalisesti_syotetty_ostotilausnro) ? 0 : 1;
  $url_array['tilausten_lukumaara'] = $tilausten_lukumaara;
  $url_array['saapumisnro_haku'] = $saapumisnro_haku;

  echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllytys.php?".http_build_query($url_array)."'>";
  exit();
}

if (isset($virhe)) {
  $errors[] = t("Tuotetta ei löytynyt").".<br>";
}

// Result alkuun
mysql_data_seek($result, 0);

//otetaan haltuun se tapaus, jos heti halutaan mennä takasin hakuruutuun ja meillä ei ole vielä tehtynä uutta saapumista -> pitää setata erillinen muuttuja, jotta saapumistunnuksen settaaminen kukarow.keskeniin ohitettaisiin kun palataan hakuun ostotilaus.php
if (!isset($saapuminen)) {
  $backsaapuminen = "";
}
else {
  $backsaapuminen = $saapuminen;
}

$url_lisa = $manuaalisesti_syotetty_ostotilausnro ? "?ostotilaus={$ostotilaus}" : "";
$url_lisa .= $url_lisa ? "&" : "?";
$url_lisa .= "backsaapuminen={$backsaapuminen}";
$url_lisa .= "&saapumisnro_haku={$saapumisnro_haku}";

//## UI ###
echo "<div class='header'>
  <button onclick='window.location.href=\"ostotilaus.php{$url_lisa}\"' class='button left'><img src='back2.png'></button>
  <h1>", t("USEITA TILAUKSIA"), "</h1></div>";

$viivakoodi_formi_urli  = "?tuotenumero=".urlencode($tuotenumero);
$viivakoodi_formi_urli .= "&ostotilaus={$ostotilaus}";
$viivakoodi_formi_urli .= "&manuaalisesti_syotetty_ostotilausnro={$manuaalisesti_syotetty_ostotilausnro}";
$viivakoodi_formi_urli .= "&orig_tilausten_lukumaara={$orig_tilausten_lukumaara}";
$viivakoodi_formi_urli .= "&saapumisnro_haku={$saapumisnro_haku}";

echo "<div class='main'>

<form name='viivakoodiformi' method='post' action='{$viivakoodi_formi_urli}' id='viivakoodiformi'>
  <table class='search'>
    <tr>
      <th>", t("Viivakoodi"), ":&nbsp;<input type='text' id='viivakoodi' name='_viivakoodi' value='' /></th>
      <td><button id='valitse_nappi' value='viivakoodi' class='button' onclick='submit();'>", t("Etsi"), "</button></td>
    </tr>
  </table>
  </form>


<form name='form1' method='post' action=''>
<table>
<tr>";

$url_sorttaus = "ostotilaus={$ostotilaus}&viivakoodi={$viivakoodi}&_viivakoodi={$_viivakoodi}&orig_tilausten_lukumaara={$orig_tilausten_lukumaara}&manuaalisesti_syotetty_ostotilausnro={$manuaalisesti_syotetty_ostotilausnro}&saapuminen={$saapuminen}&tuotenumero=&ennaltakohdistettu={$ennaltakohdistettu}&saapumisnro_haku={$saapumisnro_haku}&backsaapuminen={$backsaapuminen}".urlencode($tuotenumero);

if (($tuotenumero != '' or $viivakoodi != '') and $ostotilaus == '') {
  echo "<th><a href='tuotteella_useita_tilauksia.php?{$url_sorttaus}&sort_by=otunnus&sort_by_direction_otunnus={$sort_by_direction_otunnus}'>", t("Ostotilaus"), "</a>&nbsp;";
  echo $sort_by_direction_otunnus == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";
  echo "</th>";
}
if ($tuotenumero == '' and $viivakoodi == '' and ($ostotilaus != '' or $saapumisnro_haku != '')) {
  echo "<th><a href='tuotteella_useita_tilauksia.php?{$url_sorttaus}&sort_by=tuoteno&sort_by_direction_tuoteno={$sort_by_direction_tuoteno}'>", t("Tuoteno"), "</a>&nbsp;";
  echo $sort_by_direction_tuoteno == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";
  echo "</th>";
}

echo "<th><a href='tuotteella_useita_tilauksia.php?{$url_sorttaus}&sort_by=sorttaus_kpl&sort_by_direction_sorttaus_kpl={$sort_by_direction_sorttaus_kpl}'>", t("Kpl (ulk.)"), "</a>";
echo $sort_by_direction_sorttaus_kpl == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";
echo "</th>";

echo "<th><a href='tuotteella_useita_tilauksia.php?{$url_sorttaus}&sort_by=hylly&sort_by_direction_hylly={$sort_by_direction_hylly}'>", t("Tuotepaikka"), "</a>";
echo $sort_by_direction_hylly == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";
echo "</th>";
echo "</tr>";

// otetaan saapuminen rivilooppia varten talteen ja luodaan ennaltakohdistettu muuttuja, jolla hallitaan ennalta kohdistetut rivit (aka poikkeustapaukset)
$_saapuminen = $saapuminen;
$ennaltakohdistettu = FALSE;

// Loopataan ostotilaukset
while ($row = mysql_fetch_assoc($result)) {

  $query = "SELECT
            IF(tuotteen_toimittajat.tuotekerroin = 0, 1, tuotteen_toimittajat.tuotekerroin) tuotekerroin
            FROM tuotteen_toimittajat
            WHERE tuotteen_toimittajat.yhtio      = '{$kukarow['yhtio']}'
            AND tuotteen_toimittajat.tuoteno      = '{$row['tuoteno']}'
            AND tuotteen_toimittajat.liitostunnus = '{$row['liitostunnus']}'";
  $ttres = pupe_query($query);
  $ttrow = mysql_fetch_assoc($ttres);

  if ($row['tilausrivi_tyyppi'] == 'o') {
    //suoratoimitus asiakkaalle
    $row['tilausrivi_tyyppi'] = 'JTS';
  }

  // Jos rivi on jo kohdistettu eri saapumiselle
  if ($row['uusiotunnus'] != 0) {
    $saapuminen = $row['uusiotunnus'];
    $ennaltakohdistettu = TRUE;

  } else {
    $saapuminen = $_saapuminen;
    $ennaltakohdistettu = FALSE;
  }

  if ($orig_tilausten_lukumaara != $tilausten_lukumaara) $tilausten_lukumaara = $orig_tilausten_lukumaara;

  $url = http_build_query(
    array(
      'saapuminen' => $saapuminen,
      'ostotilaus' => $row['ostotilaus'],
      'tilausrivi' => $row['tunnus'],
      'manuaalisesti_syotetty_ostotilausnro'  => $manuaalisesti_syotetty_ostotilausnro,
      'tilausten_lukumaara' => $tilausten_lukumaara,
      'viivakoodi' => $viivakoodi,
      'tuotenumero' => $tuotenumero,
      'ennaltakohdistettu' => $ennaltakohdistettu,
      'saapumisnro_haku' => $saapumisnro_haku
    )
  );

  echo "<tr>";

  if (($tuotenumero != '' or $viivakoodi != '') and $ostotilaus == '') {
    echo "<td><a href='hyllytys.php?{$url}'>{$row['otunnus']}</a></td>";
  }
  if ($tuotenumero == '' and $viivakoodi == '' and ($ostotilaus != '' or $saapumisnro_haku != '')) {
    echo "<td><a href='hyllytys.php?{$url}'>{$row['tuoteno']}</a></td>";
  }
  echo "
    <td><a href='hyllytys.php?{$url}'>".($row['varattu']+$row['kpl']).
    "(".($row['varattu']+$row['kpl'])*$ttrow['tuotekerroin'].") {$row['tilausrivi_tyyppi']}
    </a></td>
    <td>{$row['hylly']}</td>";
  echo "<tr>";
}

echo "</table></div>";
echo "Rivejä: ".mysql_num_rows($result);

echo "<div class='controls'>
<button type='submit' name='submit' value='ok' onsubmit='false'>", t("OK"), "</button>
<button class='right' name='submit' id='takaisin' value='cancel' onclick='submit();'>", t("Takaisin"), "</button>
</form>
</div>";

echo "<div class='error'>";
foreach ($errors as $virhe) {
  echo $virhe;
}
echo "</div>";

echo "<input type='button' id='myHiddenButton' visible='false' onclick='javascript:doFocus();' width='1px' style='display:none'>";
echo "<script type='text/javascript'>

  // katotaan onko mobile
  var is_mobile = navigator.userAgent.match(/Opera Mob/i) != null;

  $(document).ready(function() {
    $('#viivakoodi').on('keyup', function() {
      // Autosubmit vain jos on syötetty tarpeeksi pitkä viivakoodi
      if (is_mobile && $('#viivakoodi').val().length > 8) {
        document.getElementById('valitse_nappi').click();
      }
    });
  });

  function doFocus() {
        var focusElementId = 'viivakoodi'
        var textBox = document.getElementById(focusElementId);
        textBox.focus();
    }

  function clickButton() {
     document.getElementById('myHiddenButton').click();
  }

   setTimeout('clickButton()', 1000);

</script>
";
