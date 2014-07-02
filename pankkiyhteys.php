<?php

require("inc/parametrit.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

if (isset($tee) and $tee == "lataa_sertifikaatti") {
  if (avaimet_ja_salasana_kunnossa($salasana, $salasanan_vahvistus)) {
    $sertifikaatti = file_get_contents($_FILES["certificate"]["tmp_name"]);
    $salattu_sertifikaatti = salaa($sertifikaatti, $salasana);

    $private_key = file_get_contents($_FILES["private_key"]["tmp_name"]);
    $salattu_private_key = salaa($private_key, $salasana);

    $query = "UPDATE yriti
              SET private_key='{$salattu_private_key}', certificate='{$salattu_sertifikaatti}'
              WHERE tunnus={$tili} AND yhtio='{$kukarow['yhtio']}'";

    $result = pupe_query($query);

    if ($result) {
      echo "Tunnukset lisätty";
    }
    else {
      echo "Tunnukset eivät tallentuneet tietokantaan";
    }
  }
  else {
    $tilit = hae_tilit($kukarow["yhtio"]);

    echo "<form action='pankkiyhteys.php' method='post' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='tee' value='lataa_sertifikaatti'/>";
    echo "<label for='tili'>" . t("Tili, jolle sertifikaatti on") . "</label>";
    echo "<select name='tili'>";
    foreach ($tilit as $tili) {
      echo "<option value='" . $tili["tunnus"] . "'>" . $tili["nimi"] . "</option>";
    }
    echo "</select>";
    echo "<br/>";
    echo "<label for='private_key'>" . t('Yksityinen avain') . "</label>";
    echo "<input type='file' name='private_key' id='private_key'>";
    echo "<br>";
    echo "<label for='certificate'>" . t('Sertifikaatti') . "</label>";
    echo "<input type='file' name='certificate' id='certificate'/>";
    echo "<br/>";
    echo "<label for='salasana'>" . t("Salasana, jolla tiedot suojataan") . "</label>";
    echo "<input type='password' name='salasana' id='salasana'/>";
    echo "<br/>";
    echo "<label for='salasanan_vahvistus'>" . t("Salasanan vahvistus") . "</label>";
    echo "<input type='password' name='salasanan_vahvistus' id='salasanan_vahvistus'/>";
    echo "<br/>";
    echo "<input type='submit' name='submit' value='" . t('Lähetä') . "'>";
    echo "</form>";
  }
}
elseif (isset($tee) and $tee == "hae_tiliote") {
  if ($salasana) {
    $salattu_sertifikaatti = hae_sertifikaatti($tili, $kukarow["yhtio"]);

    $sertifikaatti = pura_salaus($salattu_sertifikaatti, $salasana);

    $parameters = array(
      "method" => "POST",
      "data" => array(
        "cert" => base64_encode($sertifikaatti),
        "private_key" => "LS0tLS1CRUdJTiBSU0EgUFJJVkFURSBLRVktLS0tLQpNSUlDWFFJQkFBS0Jn\nUURDMFVSOEMxc200Yk5EREJHNlptUzlpSFlHTVpoV3dBeFI2SXEwNmQ3ZHRs\nSjZLeDhLCnI1TmVvdldBajB1aC9KNEJEMGord09icTB2elRLc1BtSnBKU3BX\nYm9EdmYweXlhbGIrTEpseFYvdWF6ekVBM24KVVJKU0EzcHFUQmtKVDJrZnJh\nZUFrT1BhQlN5UzFqUitteWhXd0JGMnU4NFdUUjlOSlJjcFozb3R0d0lEQVFB\nQgpBb0dCQUtyZmRkdis4ZUkya0U2OFpVaEN5eFZhZlhxTlFYckZVNGo4Rjd6\nNmJCbTI4cnhvMmY4N1pGemJQYzJXCjRkV2doczJUSklrZGxPeGVScGJJcWE1\nU0luK0hCZWw4KzZ3bzJnTE80ZzBiZlQ0NFkxYnFqUmtkaVBsU0NKVzAKUFYx\naFNkNVNSVnQ3KzB5R2ZDV3k1NTlGemhjL21RUVVraGt5dGMwelllRXdVTFl4\nQWtFQTN1VE43cnZadUVjRQpzUFVlaG1nOFB5QlVHWUs5S0ZrcjlGaUkwY0w4\nRnB4WjBsOXBXNURRSTdwVDlIV2hySnArNzhTS2FtY1Q4Y0hLCjFPTUJha3hl\nWFFKQkFOL0E1MndwdDJINklNOEN4emEzdG9RWmhxbzFtcTRiY2FyVVdxNjVJ\nSjVqbmZGdEdkUjIKOVhVaDY1WWxFbFVxeURXeXVXWFJGZGVVYWJ1MVF6bmo4\neU1DUUR6TEpVdnZHcFFEY3NrZElpVkF1dVh3MkY5WQo1R1RqNVhRd3phaUF5\nU2NWbi80Y0hlMW1rdzZibkpoNW1RNHQyVjltT09hS2xNc0VzMkRiUmFDTGtk\nVUNRR1dGCkdic3Fwa2l1KzBuUmdkK2l0UTMwb3ZRQlJFQXd0WDhEd0cwOEU3\nK3BoUlR3SW1NUzRrV1Y4VlQ3VnZrTFl6RngKK01wb2RsZU12L2hwd3FtMmNp\nOENRUUNVRWd3REJFcCtGTSsyWTVOMUt3U0d6R0JMOUx0cG5Bc3FjTEc5Snho\nTwpmNE13ejR4aFBYTVZsdnExd0VTTFByRFVGUXBaNGVPWjRYWDJNVG80R0gz\nOQotLS0tLUVORCBSU0EgUFJJVkFURSBLRVktLS0tLQ==\n",
        "customer_id" => "11111111",
        "file_type" => "TITO",
        "file_reference" => "11111111A12006030329501800000014",
        "target_id" => "11111111A1"
      ),
      "url" => "https://sepa.devlab.fi/api/nordea/download_file",
      "headers" => array(
        "Content-Type: application/json",
        "Authorization: Token token=Vl2E1xahRJz4vO4J28QSQn2mbkrM"
      )
    );
    $vastaus = pupesoft_rest($parameters);
    echo base64_decode($vastaus[1]["data"]);
  }
  else {
    $tilit = hae_tilit($kukarow["yhtio"]);

    echo "<form method='post' action='pankkiyhteys.php'>";
    echo "<input type='hidden' name='tee' value='hae_tiliote'/>";
    echo "<label for='tili'>" . t("Tili") . "</label>";
    echo "<select name='tili'>";
    foreach ($tilit as $tili) {
      echo "<option value='" . $tili["tunnus"] . "'>" . $tili["nimi"] . "</option>";
    }
    echo "</select>";
    echo "<br/>";
    echo "<label for='salasana'>" . t("Salasana, jolla salasit tunnukset") . "</label>";
    echo "<input type='password' name='salasana' id='salasana'/>";
    echo "<br/>";
    echo "<input type='submit'/>";
    echo "</form>";
  }
}
else {
  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<table>";
  echo "<tbody>";
  echo "<tr>";
  echo "<td>Mita haluat tehdä?</td>";
  echo "<td>";
  echo "<select name='tee'>";
  echo "<option value='lataa_sertifikaatti'>" . t('Lataa sertifikaatti') . "</option>";
  echo "<option value='hae_tiliote'>" . t("Hae tiliote") . "</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";
  echo "</tbody>";
  echo "</table>";
  echo "<input type='submit' value='" . t('OK') . "'>";
  echo "</form>";
}

/**
 * @param $data
 * @param $salasana
 * @return string
 */
function salaa($data, $salasana)
{
  $avain = hash("SHA256", $salasana, true);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
  $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

  $salattu_data = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $avain, $data, MCRYPT_MODE_CBC, $iv);
  $salattu_data = $iv . $salattu_data;

  return base64_encode($salattu_data);
}

/**
 * @param $yhtio
 * @return array
 */
function hae_tilit($yhtio)
{
  $query = "SELECT tunnus, nimi
            FROM yriti
            WHERE yhtio='{$yhtio}'";

  $result = pupe_query($query);

  $tilit = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($tilit, $rivi);
  }

  return $tilit;
}

/**
 * @param $tili
 * @param $yhtio
 * @return mixed
 */
function hae_sertifikaatti($tili, $yhtio)
{
  $query = "SELECT certificate
              FROM yriti
              WHERE tunnus={$tili} AND yhtio='{$yhtio}'";

  $result = pupe_query($query);
  $rivi = mysql_fetch_assoc($result);

  return $rivi["certificate"];
}

/**
 * @param $salattu_data
 * @param $salasana
 * @return string
 */
function pura_salaus($salattu_data, $salasana)
{
  $avain = hash("SHA256", $salasana, true);

  $salattu_data_binaari = base64_decode($salattu_data);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
  $iv = substr($salattu_data_binaari, 0, $iv_size);

  $salattu_data_binaari = substr($salattu_data_binaari, $iv_size);

  return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $avain, $salattu_data_binaari, MCRYPT_MODE_CBC, $iv);
}

/**
 * @param $salasana
 * @param $salasanan_vahvistus
 * @return bool
 */
function avaimet_ja_salasana_kunnossa($salasana, $salasanan_vahvistus)
{
  return $_FILES["certificate"]["tmp_name"] and $_FILES["private_key"]["tmp_name"] and !empty($salasana) and
  $salasana == $salasanan_vahvistus;
}
