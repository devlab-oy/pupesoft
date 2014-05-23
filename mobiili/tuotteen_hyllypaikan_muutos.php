<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (!isset($tuotepaikan_tunnus)) $tuotepaikan_tunnus = 0;
if (!isset($mista_koodi)) $mista_koodi = '';
if (!isset($minne_koodi)) $minne_koodi = '';
if (!isset($minne_hyllypaikka)) $minne_hyllypaikka = '';
if (!isset($nakyma)) $nakyma = '';
if (!isset($siirrettava_yht)) $siirrettava_yht = 0;
if (!isset($siirretty)) $siirretty = 0;

$errors = array();

if ($tuotepaikan_tunnus == 0 or $siirretty) {
  echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=hyllysiirrot.php'>";
  exit();
}

$query = "SELECT tuotepaikat.*, tuote.yksikko
          FROM tuotepaikat
          JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno)
          WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
          AND tuotepaikat.tunnus  = '{$tuotepaikan_tunnus}'";
$res = pupe_query($query);
$row = mysql_fetch_assoc($res);

if (isset($submit)) {
  switch($submit) {
    case 'kerayspaikka':

      $url = "tuotepaikan_tunnus={$tuotepaikan_tunnus}&tullaan=tuotteen_hyllypaikan_muutos";

      if (trim($minne_hyllypaikka) != '') {
        $url .= "&tuotepaikka={$minne_hyllypaikka}&mista_koodi={$mista_koodi}&minne_koodi={$minne_koodi}";
      }

      echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=uusi_kerayspaikka.php?{$url}'>"; exit();
      break;
    case 'ok':

      # Tarkistetaan koodi
      $options = array('varmistuskoodi' => $mista_koodi);
      if (!is_numeric($mista_koodi) or !tarkista_varaston_hyllypaikka($row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso'], $options)) {
        $errors[] = t("Virheellinen varmistuskoodi")." ({$mista_koodi})";
      }

      if (empty($minne_hyllypaikka)) {
        $errors[] = t("Virheellinen hyllypaikka");
      }

      if (count($errors) == 0) {

        // Parsitaan uusi tuotepaikka
        // Jos tuotepaikka on luettu viivakoodina, muotoa (C21 045) tai (21C 03V)
        if (preg_match('/^([a-zåäö#0-9]{2,4} [a-zåäö#0-9]{2,4})/i', $minne_hyllypaikka)) {

          // Pilkotaan viivakoodilla luettu tuotepaikka välilyönnistä
          list($alku, $loppu) = explode(' ', $minne_hyllypaikka);

          // Mätsätään numerot ja kirjaimet erilleen
          preg_match_all('/([0-9]+)|([a-z]+)/i', $alku, $alku);
          preg_match_all('/([0-9]+)|([a-z]+)/i', $loppu, $loppu);

          // Hyllyn tiedot oikeisiin muuttujiin
          $hyllyalue = $alku[0][0];
          $hyllynro  = $alku[0][1];
          $hyllyvali = $loppu[0][0];
          $hyllytaso = $loppu[0][1];

          // Kaikkia tuotepaikkoja ei pystytä parsimaan
          if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
            $errors[] = t("Tuotepaikan haussa virhe, yritä syöttää tuotepaikka käsin") . " ({$minne_hyllypaikka})";
          }
        }
        // Tuotepaikka syötetty manuaalisesti (C-21-04-5) tai (C 21 04 5) tai (E 14 21 5) tai (2 P 58 D)
        elseif (strstr($minne_hyllypaikka, '-') or strstr($minne_hyllypaikka, ' ')) {
          // Parsitaan tuotepaikka omiin muuttujiin (erotelto välilyönnillä)
          if (preg_match('/\w+\s\w+\s\w+\s\w+/i', $minne_hyllypaikka)) {
            list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode(' ', $minne_hyllypaikka);
          }
          // (erotelto väliviivalla)
          elseif (preg_match('/\w+-\w+-\w+-\w+/i', $minne_hyllypaikka)) {
            list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode('-', $minne_hyllypaikka);
          }

          // Ei saa olla tyhjiä kenttiä
          if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
            $errors[] = t("Virheellinen tuotepaikka") . ". ({$minne_hyllypaikka})";
          }
        }
        else {
          $errors[] = t("Virheellinen tuotepaikka, yritä syöttää tuotepaikka käsin") . " ({$minne_hyllypaikka})";
        }

        if (count($errors) == 0) {

          $query = "SELECT tunnus
                    FROM tuotepaikat
                    WHERE yhtio   = '{$kukarow['yhtio']}'
                    AND tuoteno   = '{$row['tuoteno']}'
                    AND hyllyalue = '{$hyllyalue}'
                    AND hyllynro  = '{$hyllynro}'
                    AND hyllyvali = '{$hyllyvali}'
                    AND hyllytaso = '{$hyllytaso}'";
          $chk_res = pupe_query($query);

          if (mysql_num_rows($chk_res) == 0) $errors[] = t("Tuotepaikkaa (%s-%s-%s-%s) ei ole perustettu tuotteelle", "", $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso).'.';
        }

        // Tarkistetaan että tuotepaikka on olemassa
        if (count($errors) == 0 and !tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso)) {
          $errors[] = t("Tuotepaikkaa (%s-%s-%s-%s) ei ole perustettu varaston hyllypaikkoihin", "", $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso).'.';
        }

        if (count($errors) == 0) {
          $options = array('varmistuskoodi' => $minne_koodi);
          if (!is_numeric($minne_koodi) or !tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $options)) {
            $errors[] = t("Virheellinen varmistuskoodi")." ({$minne_koodi})";
          }
        }
      }

      if (count($errors) == 0) {

        list($siirrettava_yht, $siirrettavat_rivit) = laske_siirrettava_maara($row);

        $query = "SELECT tuotepaikat.*, tuote.yksikko
                  FROM tuotepaikat
                  JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno)
                  WHERE tuotepaikat.yhtio   = '{$kukarow['yhtio']}'
                  AND tuotepaikat.tuoteno   = '{$row['tuoteno']}'
                  AND tuotepaikat.hyllyalue = '{$hyllyalue}'
                  AND tuotepaikat.hyllynro  = '{$hyllynro}'
                  AND tuotepaikat.hyllyvali = '{$hyllyvali}'
                  AND tuotepaikat.hyllytaso = '{$hyllytaso}'";
        $res = pupe_query($query);
        $minnerow = mysql_fetch_assoc($res);

        $params = array(
          'kappaleet' => $siirrettava_yht,
          'lisavaruste' => '',
          'tuoteno' => $row['tuoteno'],
          'tuotepaikat_tunnus_otetaan' => $row['tunnus'],
          'tuotepaikat_tunnus_siirretaan' => $minnerow['tunnus'],
          'mistarow' => $row,
          'minnerow' => $minnerow,
          'sarjano_array' => array(),
          'selite' => '',
          'tun' => 0,
        );

        hyllysiirto($params);

        if (count($siirrettavat_rivit) > 0) {

          foreach ($siirrettavat_rivit as $siirrettavat_rivi) {

            $query = "UPDATE tilausrivi SET
                      hyllyalue   = '{$hyllyalue}',
                      hyllynro    = '{$hyllynro}',
                      hyllyvali   = '{$hyllyvali}',
                      hyllytaso   = '{$hyllytaso}'
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = {$siirrettavat_rivi}";
            pupe_query($query);
          }
        }

        $nakyma = 'siirrä';
      }

      break;
    default:
      $errors[] = t("Yllättävä virhe");
      break;
  }
}

$url_lisa = "?tuotenumero=".urlencode($row['tuoteno']);

### UI ###
echo "<div class='header'>";
if ($nakyma == '') echo "<button onclick='window.location.href=\"tuotteella_useita_tuotepaikkoja.php{$url_lisa}\"' class='button left'><img src='back2.png'></button>";
echo "<h1>",t("HYLLYPAIKAN MUUTOS"), "</h1></div>";

echo "<form name='form1' method='post' action=''>";
echo "<input type='hidden' name='tuotepaikan_tunnus' value='{$row['tunnus']}' />";

if ($nakyma == 'siirrä') {

  echo "<input type='hidden' name='siirretty' value='1' />";

  if (isset($aiempi_siirrettava_yht) and $aiempi_siirrettava_yht != $siirrettava_yht) {
    echo "<span class='error'>",t("Huomioithan, että siirrettävä määrä on muuttunut (%d)", "", $aiempi_siirrettava_yht),"</span><br />";
  }

  echo "<span class='message'>",t("Siirrä %d tuotetta", "", $siirrettava_yht),"</span>";

  echo "<div class='controls'>";
  echo "<button name='submit' class='button left' id='haku_nappi' value='ok' onclick='submit();' class='button'>",t("OK"),"</button>";
  echo "</div>";

}
else {

  # Virheet
  if (count($errors) > 0) {
    echo "<span class='error'>";
    foreach($errors as $virhe) {
      echo "{$virhe}<br>";
    }
    echo "</span>";
  }

  echo "<table>";

  echo "<tr>";
  echo "<th>",t("Tuoteno"),"</th>";
  echo "<td>{$row['tuoteno']}</td>";
  echo "</tr>";

  list($siirrettava_yht, $siirrettavat_rivit) = laske_siirrettava_maara($row);

  echo "<input type='hidden' name='aiempi_siirrettava_yht' value='{$siirrettava_yht}' />";

  echo "<tr>";
  echo "<th>",t("Määrä"),"</th>";
  echo "<td>{$siirrettava_yht} {$row['yksikko']}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>",t("Mistä hyllypaikka"),"</th>";
  echo "<td>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>",t("Koodi"),"</th>";
  echo "<td><input type='text' name='mista_koodi' id='mista_koodi' value='{$mista_koodi}' size='7' /></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>",t("Minne hyllypaikka"),"</th>";
  echo "<td><input type='text' name='minne_hyllypaikka' value='{$minne_hyllypaikka}' /></td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>",t("Koodi"),"</th>";
  echo "<td><input type='text' id='minne_koodi' name='minne_koodi' value='{$minne_koodi}' size='7' /></td>";
  echo "</tr>";

  echo "</table>";
  echo "<div class='controls'>";
  echo "<button name='submit' class='button left' id='haku_nappi' value='ok' onclick='submit();' class='button'>",t("OK"),"</button>";
  echo "<button name='submit' class='button right' id='submit' value='kerayspaikka' onclick='submit();'>",t("UUSI KERÄYSPAIKKA"),"</button>";
  echo "</div>";
  echo "</form>";

  echo "<input type='button' id='myHiddenButton' visible='false' onclick='javascript:doFocus();' width='1px' style='display:none'>";
  echo "<script type='text/javascript'>

      function doFocus() {
          var focusElementId = 'mista_koodi';
          var textBox = document.getElementById(focusElementId);
          textBox.focus();
        }

      function clickButton() {
         document.getElementById('myHiddenButton').click();
      }

      setTimeout('clickButton()', 1000);

    </script>";
}

require('inc/footer.inc');
