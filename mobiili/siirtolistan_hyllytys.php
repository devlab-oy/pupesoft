<?php
$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

if (!isset($errors)) $errors = array();
if (!isset($siirtolista)) $siirtolista = '';
if (!isset($tilausrivi)) $tilausrivi = '';
if (!isset($tilausrivi)) $tilausrivi = '';
if (!isset($hylly)) $hylly = '';

if (empty($siirtolista)) {
  exit("Virheelliset parametrit");
}

// Haetaan tilausrivin tiedot
$query = "SELECT
            tilausrivi.varattu+tilausrivi.kpl AS siskpl,
            tilausrivi.tuoteno,
            round(tilausrivi.varattu + tilausrivi.kpl, 2) AS ulkkpl,
            concat_ws('-',tilausrivin_lisatiedot.kohde_hyllyalue,tilausrivin_lisatiedot.kohde_hyllynro,tilausrivin_lisatiedot.kohde_hyllyvali,tilausrivin_lisatiedot.kohde_hyllytaso) AS kerayspaikka,
            tilausrivi.varattu,
            tilausrivi.yksikko,
            tilausrivi.suuntalava,
            tilausrivi.uusiotunnus,
            lasku.liitostunnus,
            lasku.clearing
          FROM lasku
          JOIN tilausrivi
            ON (tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus                      = lasku.tunnus
              AND tilausrivi.tyyppi='G')
          LEFT JOIN tilausrivin_lisatiedot
            ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
          WHERE tilausrivi.tunnus                         = '{$tilausrivi}'
          AND tilausrivi.yhtio                            = '{$kukarow['yhtio']}'
          AND lasku.tunnus                                = '{$siirtolista}'";
$result = pupe_query($query);
$row = mysql_fetch_assoc($result);

if (!$row) {
  exit("Virhe, riviä ei löydy");
}

$clearing = $row['clearing'];
if (!isset($maara)) $maara = $row['varattu'];
if (!isset($hylly) or $hylly == "") $hylly = $row['kerayspaikka'];

// Päivitetään kuka.kesken
$update_kuka = "UPDATE kuka SET kesken = {$siirtolista} WHERE yhtio = '{$kukarow['yhtio']}' AND kuka = '{$kukarow['kuka']}'";
$updated = pupe_query($update_kuka);

// Kontrolleri
if (isset($submit)) {
  $url = "&kerayspaikka={$hylly}&varasto={$clearing}&viivakoodi={$viivakoodi}&tilausten_lukumaara={$riveja}&saapumisnro_haku={$saapumisnro_haku}&manuaalisesti_syotetty_ostotilausnro={$manuaalisesti_syotetty_ostotilausnro}&ennaltakohdistettu={$ennaltakohdistettu}&tuotenumero=".urlencode($tuotenumero);

  switch ($submit) {
    case 'ok':
      // Vahvista keräyspaikka
      echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=vahvista_kerayspaikka.php?siirtolista{$url}&maara={$maara}&saapuminen={$saapuminen}&alusta_tunnus={$row['suuntalava']}&liitostunnus={$row['liitostunnus']}'>";
      exit();
      break;

    case 'kerayspaikka':
      // Parametrit $alusta_tunnus, $liitostunnus, $tilausrivi
      echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=uusi_kerayspaikka.php?siirtolista&ostotilaus={$siirtolista}{$url}&tilausrivi={$tilausrivi}&clearing={$clearing}&maara={$maara}'>"; exit();
      break;

    default:
      $errors[] = "Error";
      break;
  }
}

$url_prelisa = $riveja < 2 ? "siirtolista.php" : "siirtolistalla_useita_tuotteita.php";
$url_lisa = $siirtolista ? "siirtolista={$siirtolista}&movingback" : "";
$url_lisa .= ($viivakoodi != "" and $riveja > 1) ? "&viivakoodi={$viivakoodi}" : "";

//####### UI ##########
// Otsikko
echo "<div class='header'>";
echo "<button onclick='window.location.href=\"{$url_prelisa}?{$url_lisa}\"' class='button left'><img src='back2.png'></button>";
echo "<h1>", t("HYLLYTYS")."</h1>";
echo "</div>";

// Main
echo "<div class='main'>
<form name='f1' method='post' action=''>
<input type='hidden' name='siirtolista' value='{$siirtolista}' />
<table>
    <tr>
        <th width=\"40%\">", t("Varattu määrä"), "</th>
        <td>{$row['varattu']}</td>
        <td>({$row['ulkkpl']})</td>
    </tr>
    <tr>
        <th>", t("Hyllytetty määrä"), "</th>
        <td><input class='numero' type='text' id='maara' name='maara' value='{$maara}' onchange='update_label()'></td>
        <td> </td>
    </tr>
    <tr>
        <th>", t("Tuote"), "</th>
        <td>{$row['tuoteno']}</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <th>", t("Keräyspaikka"), "</th>
        <td colspan='2'>{$hylly}{$muutpaikat}</td>
    </tr>
    <tr>
        <th>", t("Siirtolista"), "</th>
        <td>{$siirtolista}</td>
        <td>&nbsp;</td>
    </tr>
</table>
</div>";

$url = "siirtolista&varasto={$clearing}&hylly={$hylly}&viivakoodi={$viivakoodi}&alusta_tunnus=&liitostunnus=&saapumisnro_haku=&tilausrivi={$tilausrivi}&ostotilaus={$siirtolista}&tilausten_lukumaara={$riveja}&tuotenumero=".urlencode($tuotenumero);

// Napit
echo "<div class='controls'>";
echo "<button type='submit' class='button left' onclick=\"f1.action='vahvista_kerayspaikka.php?{$url}'\">", t("OK"), "</button>";
echo "<button name='submit' class='button right' id='submit' value='kerayspaikka' onclick='submit();'>", t("UUSI KERÄYSPAIKKA"), "</button>";

echo "</div>";
echo "</form>";

echo "<div class='error'>";
foreach ($errors as $virhe) {
  echo $virhe."<br>";
}
echo "</div>";

echo "<script type='text/javascript'>
    function update_label(numero) {
        var hyllytetty = document.getElementById('numero').value;
        var tuotekerroin = {$row['tuotekerroin']} * hyllytetty;
        var label = document.getElementById('hylytetty_label');
        label.innerHTML = tuotekerroin;
    }
</script>";
