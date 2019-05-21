<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (!isset($errors)) $errors = array();
if (!isset($viivakoodi)) $viivakoodi = "";
if (!isset($tuoteviivakoodi)) $tuoteviivakoodi = "";

$sort_by_direction_tuoteno = (!isset($sort_by_direction_tuoteno) or $sort_by_direction_tuoteno == 'asc') ? 'desc' : 'asc';
$sort_by_direction_kpl     = (!isset($sort_by_direction_kpl) or $sort_by_direction_kpl == 'asc') ? 'desc' : 'asc';
$sort_by_direction_hylly   = (!isset($sort_by_direction_hylly) or $sort_by_direction_hylly == 'asc') ? 'desc' : 'asc';

$params = array();

// Joku parametri tarvii olla setattu.
if ($siirtolista == '' and $viivakoodi == '') {
  // T�nne ei pit�is p��ty�, tarkistetaan jo siirtolista.php:ss�
  echo t("Parametrivirhe");
  echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=siirtolista.php'>";
  exit();
}

// Tarkistetaan onko k�ytt�j�ll� kesken saapumista
$keskeneraiset_query = "SELECT kuka.kesken FROM lasku
                        JOIN kuka ON (lasku.tunnus = kuka.kesken AND lasku.yhtio = kuka.yhtio)
                        WHERE kuka = '{$kukarow['kuka']}'
                          AND kuka.yhtio = '{$kukarow['yhtio']}'
                          AND lasku.tila = 'Q'";
$keskeneraiset = mysql_fetch_assoc(pupe_query($keskeneraiset_query));

// Jos kuka.kesken on saapuminen, k�ytet��n sit�
if ($keskeneraiset['kesken'] != 0) {
  $saapuminen = $keskeneraiset['kesken'];
}

$orderby = "tuoteno, varattu";
$ascdesc = "desc";

if (isset($sort_by)) {
  switch ($sort_by) {
    case 'tuoteno':
    case 'kpl':
    case 'hylly':
      $orderby = $sort_by;
      $ascdesc = ${"sort_by_direction_{$sort_by}"};
      break;

    default:
      break;
  }
}

if (isset($viivakoodi) and $viivakoodi != "") {
  $query = "
    SELECT tunnus FROM lasku
    WHERE lasku.viitetxt = {$viivakoodi}
      AND lasku.yhtio = '{$kukarow['yhtio']}'
      AND (lasku.tila = 'G' AND lasku.alatila IN ('C', 'B', 'D'))";
  $result = pupe_query($query);
  $lasku_row = mysql_fetch_assoc($result);
  $siirtolista = $lasku_row['tunnus'];
}

if (isset($siirtolista) && is_numeric($siirtolista)) {
  if ($tuoteviivakoodi != "") {
    $tuotenumerot = hae_viivakoodilla($tuoteviivakoodi);
    $viivakoodirajaus = "AND tilausrivi.tuoteno IN ('".implode(array_keys($tuotenumerot), "','")."')";
  } else {
    $viivakoodirajaus = "";
  }

  // Haetaan siirtolistan tuotteet
  $query = "SELECT
            lasku.tunnus AS siirtolista,
            tilausrivi.tunnus,
            tilausrivi.tuoteno,
            tilausrivi.varattu,
            concat_ws('-',tilausrivin_lisatiedot.kohde_hyllyalue,tilausrivin_lisatiedot.kohde_hyllynro,tilausrivin_lisatiedot.kohde_hyllyvali,tilausrivin_lisatiedot.kohde_hyllytaso) as hylly,
            lasku.liitostunnus
          FROM lasku
          INNER JOIN tilausrivi ON ((tilausrivi.otunnus = lasku.tunnus) AND (tilausrivi.yhtio = lasku.yhtio))
          INNER JOIN tilausrivin_lisatiedot ON ((tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus) AND (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio))
          WHERE lasku.tunnus = {$siirtolista}
            AND lasku.yhtio = '{$kukarow['yhtio']}'
            AND (lasku.tila = 'G' AND lasku.alatila IN ('C', 'B', 'D'))
            AND tilausrivi.toimitettu = ''
            {$viivakoodirajaus}
          ORDER BY {$orderby} {$ascdesc}";

  $result = pupe_query($query);
  $riveja = mysql_num_rows($result);
  $tuotteet = mysql_fetch_assoc($result);
} else {
  $riveja = 0;
}

// Submit
if (isset($submit)) {
  switch ($submit) {
    case 'ok':
      if (empty($tilausrivi)) {
        $errors[] = t("Valitse rivi");
        break;
      }

      $url_array['siirtolista'] = $siirtolista;
      $url_array['riveja'] = $riveja;
      echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=siirtolistan_hyllytys.php?".http_build_query($url_array)."'>"; exit();

      #$url_array['siirtolista'] = $siirtolista;
      #$url_array['viivakoodi'] = $tuoteviivakoodi;
      #$url_array['tilausten_lukumaara'] = $riveja;
      #$url_array['saapumisnro_haku'] = '';
      #$url_array['tilausrivi'] = $tilausrivi;
      #$url_array['manuaalisesti_syotetty_ostotilausnro'] = '';
      #$url_array['ennaltakohdistettu'] = '';
      #$url_array['tuotenumero'] = '';
      #$url_array['saapuminen'] = '';
      #$url_array['alusta_tunnus'] = '';
      #$url_array['liitostunnus'] = '';
      #echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?".http_build_query($url_array)."'>";

      exit;

    case 'cancel':
      echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=siirtolista.php?movingback&siirtolista={$siirtolista}&viivakoodi={$viivakoodi}'>";
      exit;

    default:
      echo "Virhe";
      break;
  }
}

// Ei osumia, palataan siirtolista sivulle
if ($riveja == 0 and $tuoteviivakoodi == "") {
  echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=siirtolista.php?siirtolista={$siirtolista}&viivakoodi={$viivakoodi}&virhe'>";
  exit();
}

// Jos vain yksi osuma, menn��n suoraan hyllytykseen;
if ($riveja == 1) {
  mysql_data_seek($result, 0);
  $row = mysql_fetch_assoc($result);

  #$url_array['siirtolista'] = $siirtolista;
  #$url_array['tilausrivi'] = $row['tunnus'];
  #$url_array['viivakoodi'] = $tuoteviivakoodi;
  #$url_array['tilausten_lukumaara'] = $riveja;
  #$url_array['saapumisnro_haku'] = '';
  #$url_array['manuaalisesti_syotetty_ostotilausnro'] = '';
  #$url_array['ennaltakohdistettu'] = '';
  #$url_array['tuotenumero'] = '';
  #$url_array['saapuminen'] = '';
  #$url_array['alusta_tunnus'] = '';
  #$url_array['liitostunnus'] = '';
  #echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?".http_build_query($url_array)."'>";

  $url = http_build_query(
    array(
      'riveja' => $riveja,
      'siirtolista' => $row['siirtolista'],
      'tilausrivi' => $row['tunnus']
    )
  );
  echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=siirtolistan_hyllytys.php?".$url."'>";

  exit();
}

if (isset($virhe)) {
  $errors[] = t("Siirtolistaa ei löytynyt").".<br>";
}

// Result alkuun
mysql_data_seek($result, 0);

$viivakoodi_formi_urli = "?siirtolista={$siirtolista}";

//## UI ###
echo "<div class='header'>
  <button onclick='window.location.href=\"siirtolista.php?movingback&siirtolista={$siirtolista}\"' class='button left'><img src='back2.png'></button>
  <h1>", t("USEITA TUOTTEITA"), "</h1></div>

  <div class='main'>
  
  <form name='viivakoodiformi' method='post' action='{$viivakoodi_formi_urli}' id='viivakoodiformi'>
    <table class='search'>
      <tr>
        <th>", t("Viivakoodi"), ":&nbsp;<input type='text' id='tuoteviivakoodi' name='tuoteviivakoodi' value='' /></th>
        <td><button id='valitse_nappi' value='tuoteviivakoodi' class='button' onclick='submit();'>", t("Etsi"), "</button></td>
      </tr>
    </table>
  </form>
  
  
  <form name='form1' method='post' action=''>
  <table>
  <tr>";

$url_sorttaus = "siirtolista={$siirtolista}&viivakoodi={$viivakoodi}";

echo "<th><a href='siirtolistalla_useita_tuotteita.php?{$url_sorttaus}&sort_by=tuoteno&sort_by_direction_tuoteno={$sort_by_direction_tuoteno}'>", t("Tuoteno"), "</a>&nbsp;";
echo $sort_by_direction_tuoteno == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";
echo "</th>";

echo "<th><a href='siirtolistalla_useita_tuotteita.php?{$url_sorttaus}&sort_by=kpl&sort_by_direction_kpl={$sort_by_direction_kpl}'>", t("Kpl"), "</a>";
echo $sort_by_direction_kpl == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";
echo "</th>";

echo "<th><a href='siirtolistalla_useita_tuotteita.php?{$url_sorttaus}&sort_by=hylly&sort_by_direction_hylly={$sort_by_direction_hylly}'>", t("Tuotepaikka"), "</a>";
echo $sort_by_direction_hylly == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";
echo "</th>";
echo "</tr>";

// Loopataan ostotilaukset
while ($row = mysql_fetch_assoc($result)) {
  $url = http_build_query(
    array(
      'riveja' => $riveja,
      'siirtolista' => $row['siirtolista'],
      'tilausrivi' => $row['tunnus']
    )
  );

  #$url_array['viivakoodi'] = '';
  #$url_array['liitostunnus'] = row['liitostunnus'];
  #$url_array['alusta_tunnus'] = '';
  #$url_array['saapumisnro_haku'] = '';
  #$url_array['tilausrivi'] = $row['tunnus'];
  #$url_array['ostotilaus'] = $siirtolista;
  #$url_array['tilausten_lukumaara'] = $riveja;
  #$url_array['tuotenumero'] = '';
  #$url_array['manuaalisesti_syotetty_ostotilausnro'] = '';
  #$url = http_build_query($url_array) . "&siirtolista";

  echo "<tr>";
  echo "<td><a href='siirtolistan_hyllytys.php?{$url}'>{$row['tuoteno']}</a></td>";
  echo "<td><a href='siirtolistan_hyllytys.php?{$url}'>" . $row['varattu'] . "</a></td> <td>{$row['hylly']}</td>";
  #echo "<td><a href='vahvista_kerayspaikka.php?{$url}'>{$row['tuoteno']}</a></td>";
  #echo "<td><a href='vahvista_kerayspaikka.php?{$url}'>" . $row['varattu'] . "</a></td> <td>{$row['hylly']}</td>";
  echo "<tr>";
}

echo "</table></div>";
echo "Rivejä: " . mysql_num_rows($result);

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
      // Autosubmit vain jos on sy�tetty tarpeeksi pitk� viivakoodi
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
