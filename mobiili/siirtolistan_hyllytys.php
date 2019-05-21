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
if (!isset($kerayspaikka)) $kerayspaikka = '';

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
          JOIN tuotepaikat
            ON (tuotepaikat.yhtio = lasku.yhtio
              AND tuotepaikat.tuoteno                     = tilausrivi.tuoteno
              AND tuotepaikat.oletus                      = 'X')
          WHERE tilausrivi.tunnus                         = '{$tilausrivi}'
          AND tilausrivi.yhtio                            = '{$kukarow['yhtio']}'
          AND lasku.tunnus                                = '{$siirtolista}'";
$result = pupe_query($query);
$row = mysql_fetch_assoc($result);

if (!$row) {
  exit("Virhe, rivi� ei l�ydy");
}

$clearing = $row['clearing'];

// P�ivitet��n kuka.kesken
$update_kuka = "UPDATE kuka SET kesken = {$siirtolista} WHERE yhtio = '{$kukarow['yhtio']}' AND kuka = '{$kukarow['kuka']}'";
$updated = pupe_query($update_kuka);

// Kontrolleri
if (isset($submit)) {
  $url = "&kerayspaikka={$kerayspaikka}&varasto={$clearing}&viivakoodi={$viivakoodi}&tilausten_lukumaara={$riveja}&saapumisnro_haku={$saapumisnro_haku}&manuaalisesti_syotetty_ostotilausnro={$manuaalisesti_syotetty_ostotilausnro}&ennaltakohdistettu={$ennaltakohdistettu}&tuotenumero=".urlencode($tuotenumero);

  switch ($submit) {
    case 'ok':
      // Vahvista ker�yspaikka
      echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=vahvista_kerayspaikka.php?siirtolista&".http_build_query($url_array)."{$url}&saapuminen={$saapuminen}&alusta_tunnus={$row['suuntalava']}&liitostunnus={$row['liitostunnus']}'>";
      exit();
      break;

    case 'kerayspaikka':
      // Parametrit $alusta_tunnus, $liitostunnus, $tilausrivi
      echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=uusi_kerayspaikka.php?siirtolista&ostotilaus={$siirtolista}{$url}&tilausrivi={$tilausrivi}'>"; exit();
      break;

    default:
      $errors[] = "Error";
      break;
  }
}

$url_prelisa = $riveja < 2 ? "siirtolista.php" : "siirtolistalla_useita_tuotteita.php";
$url_lisa = $siirtolista ? "siirtolista={$siirtolista}" : "";
$url_lisa .= ($viivakoodi != "" and $riveja > 1) ? "&viivakoodi={$viivakoodi}" : "";

// vastaanottavan varaston tiedot
$query  = "SELECT *
           FROM varastopaikat
           WHERE yhtio = '$kukarow[yhtio]'
             AND tunnus  = '{$clearing}'";
$vares = pupe_query($query);
$varow2 = mysql_fetch_assoc($vares);

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
        <th>", t("Varattu m��r�"), "</th>
        <td>{$row['varattu']}</td>
        <td>({$row['ulkkpl']})</td>
    </tr>
    <tr>
        <th>", t("Hyllytetty m��r�"), "</th>
        <td><input id='numero' class='numero' type='text' id='maara' name='maara' value='{$row['varattu']}' onchange='update_label()'></td>
        <td> </td>
    </tr>
    <tr>
        <th>", t("Tuote"), "</th>
        <td>{$row['tuoteno']}</td>
        <td>&nbsp;</td>
    </tr>";

$vares = varaston_lapsivarastot($varow2['tunnus'], $row['tuoteno']);

$s1_options = array();
$s2_options = array();
$s3_options = array();

while ($varow = mysql_fetch_assoc($vares)) {
  $status = $varow['status'];
  ${$status."_options"}[] = $varow;
}

$counts = array(
  's1' => count($s1_options),
  's2' => count($s2_options),
  's3' => count($s3_options)
);

if (array_sum($counts) > 1) {
  if ($counts['s1'] > 0) {
    $tulosta_otsikko = true;
    foreach ($s1_options as $tp) {
      echo "<tr>";
      if ($tulosta_otsikko) {
        $tulosta_otsikko = false;
        echo "<th>" . t("Kohdevaraston-paikat") . "</th>";
      } else {
        echo "<th>&nbsp;</th>";
      }

      echo "<td>" . $tp['hyllyalue'], '-', $tp['hyllynro'], '-', $tp['hyllyvali'], '-', $tp['hyllytaso'] . "</td>";
      echo "<td>&nbsp;</td>";
      echo "</tr>";
    }
  }

  if ($counts['s2'] > 0) {
    $tulosta_otsikko = true;
    foreach ($s2_options as $tp) {
      echo "<tr>";
      if ($tulosta_otsikko) {
        $tulosta_otsikko = false;
        echo "<th>" . t("Lapsivarastojen-paikat") . "</th>";
      } else {
        echo "<td>&nbsp;</td>";
      }

      echo "<td>" . $tp['hyllyalue'], '-', $tp['hyllynro'], '-', $tp['hyllyvali'], '-', $tp['hyllytaso'] . "</td>";
      echo "<td>&nbsp;</td>";
      echo "</tr>";
    }
  }

  if ($counts['s3'] > 0) {
    $tulosta_otsikko = true;
    foreach ($s3_options as $va) {
      echo "<tr>";
      if ($tulosta_otsikko) {
        $tulosta_otsikko = false;
        echo "<th>" . t("Paikattomat-lapsivarastot") . "</th>";
      } else {
        echo "<td>&nbsp;</td>";
      }

      echo "<td>" . $va['nimitys'] . "</td>";
      echo "<td>&nbsp;</td>";
      echo "</tr>";
    }
  }
} else {
  echo "
    <tr>
    <th>", t("Ker�yspaikka"), "</th>
    <td>{$row['kerayspaikka']}</td>
    <td>&nbsp;</td>
    </tr>";
}

echo "
    <tr>
      <th>".t("Vaihda Ker�yspaikka")."</th>
      <td colspan='2'><input type='text' id='hylly' name='hylly' value='' size='11' /></td>
    </tr> 

</table>
</div>";

$url = "siirtolista&varasto={$clearing}&viivakoodi={$viivakoodi}&alusta_tunnus=&liitostunnus=&saapumisnro_haku=&tilausrivi={$tilausrivi}&ostotilaus={$siirtolista}&tilausten_lukumaara={$riveja}&tuotenumero=".urlencode($tuotenumero);

// Napit
echo "<div class='controls'>";
echo "<button type='submit' class='button left' onclick=\"f1.action='vahvista_kerayspaikka.php?{$url}'\">", t("OK"), "</button>";
echo "<button name='submit' class='button right' id='submit' value='kerayspaikka' onclick='submit();' disabled>", t("UUSI KER�YSPAIKKA"), "</button>";

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
