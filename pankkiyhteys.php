<?php

require("inc/parametrit.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

if (isset($tee) and $tee == "lataa_sertifikaatti") {
  if ($_FILES["certificate"] and $_FILES["private_key"] and $salasana) {
    $sertifikaatti = file_get_contents($_FILES["certificate"]["tmp_name"]);
    $salattu_sertifikaatti = salaa($sertifikaatti, $salasana);

    $private_key = file_get_contents($_FILES["private_key"]["tmp_name"]);
    $salattu_private_key = salaa($private_key, $salasana);

    $query = "UPDATE yriti
              SET private_key='{$salattu_private_key}', certificate='{$salattu_sertifikaatti}'
              WHERE tunnus={$tili}";

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
    echo "<input type='submit' name='submit' value='" . t('Lähetä') . "'>";
    echo "</form>";
  }
}
elseif (isset($tee) and $tee == "hae_tiliote") {
  if ($salasana) {
    $salattu_sertifikaatti = hae_sertifikaatti($tili, $kukarow["yhtio"]);

    $sertifikaatti = pura_salaus($salattu_sertifikaatti, $salasana);

    echo $sertifikaatti;


    $parameters = array(
      "method" => "POST",
      "data" => array(
        "cert" => "LS0tLS1CRUdJTiBDRVJUSUZJQ0FURS0tLS0tCk1JSUR3VENDQXFtZ0F3SUJB\nZ0lFQVgxSnVUQU5CZ2txaGtpRzl3MEJBUVVGQURCa01Rc3dDUVlEVlFRR0V3\nSlQKUlRFZU1Cd0dBMVVFQ2hNVlRtOXlaR1ZoSUVKaGJtc2dRVUlnS0hCMVlt\nd3BNUjh3SFFZRFZRUURFeFpPYjNKawpaV0VnUTI5eWNHOXlZWFJsSUVOQklE\nQXhNUlF3RWdZRFZRUUZFd3MxTVRZME1EWXRNREV5TURBZUZ3MHhNekExCk1E\nSXhNakkyTXpSYUZ3MHhOVEExTURJeE1qSTJNelJhTUVReEN6QUpCZ05WQkFZ\nVEFrWkpNU0F3SGdZRFZRUUQKREJkT2IzSmtaV0VnUkdWdGJ5QkRaWEowYVda\ncFkyRjBaVEVUTUJFR0ExVUVCUk1LTlRjNE1EZzJNREl6T0RDQgpuekFOQmdr\ncWhraUc5dzBCQVFFRkFBT0JqUUF3Z1lrQ2dZRUF3dEZFZkF0Ykp1R3pRd3dS\ndW1aa3ZZaDJCakdZClZzQU1VZWlLdE9uZTNiWlNlaXNmQ3ErVFhxTDFnSTlM\nb2Z5ZUFROUkvc0RtNnRMODB5ckQ1aWFTVXFWbTZBNzMKOU1zbXBXL2l5WmNW\nZjdtczh4QU41MUVTVWdONmFrd1pDVTlwSDYybmdKRGoyZ1Vza3RZMGZwc29W\nc0FSZHJ2TwpGazBmVFNVWEtXZDZMYmNDQXdFQUFhT0NBUjB3Z2dFWk1Ba0dB\nMVVkRXdRQ01BQXdFUVlEVlIwT0JBb0VDRUJ3CjJjajcrWE1BTUJNR0ExVWRJ\nQVFNTUFvd0NBWUdLb1Z3UndFRE1CTUdBMVVkSXdRTU1BcUFDRUFMZGRiYnp3\ndW4KTURjR0NDc0dBUVVGQndFQkJDc3dLVEFuQmdnckJnRUZCUWN3QVlZYmFI\nUjBjRG92TDI5amMzQXVibTl5WkdWaApMbk5sTDBORFFUQXhNQTRHQTFVZER3\nRUIvd1FFQXdJRm9EQ0JoUVlEVlIwZkJINHdmREI2b0hpZ2RvWjBiR1JoCmND\nVXpRUzh2YkdSaGNDNXVZaTV6WlM5amJpVXpSRTV2Y21SbFlTdERiM0p3YjNK\naGRHVXJRMEVyTURFbE1rTnYKSlRORVRtOXlaR1ZoSzBKaGJtc3JRVUlySlRJ\nNGNIVmliQ1V5T1NVeVEyTWxNMFJUUlNVelJtTmxjblJwWm1sagpZWFJsY21W\nMmIyTmhkR2x2Ym14cGMzUXdEUVlKS29aSWh2Y05BUUVGQlFBRGdnRUJBQ0xV\nUEIxR21xNjI4Ni9zClJPQURvN04rdzNlVmlHSjJmdU9UTE15NFIwVUhPem5L\nWk5zdWs0ekFiUzJLeWNiWnNFNXB5NEw4bytJWW9hUzgKOFlIdEVlY2tyMm9x\nSG5QcHovMEVnN3dJdGo4QWQrQUZXSnF6Ym42SHUvTFFobG5sNUpFelh6bDNl\nWmo5b2lpSgoxcS8yQ0dYdkZvbVk3UzR0Z3BXUm1ZVUx0Q0s2am9kZTBOaGdO\nbkFnT0k5dXk3NnBTUzE2YURvaVFXVUpxUWdWCnlkb3dBbnFTOWg5YVE2Z2Vk\nd2JPZHRrV213S01EVlhVNmFSejlHdmsrSmVZSmh0cHVQM09QTkdiYkM1TDdO\nVmQKbm8rQjZBdHd4bUczb3pkK21QY01lVnV6NmtLTEFtUXlJaUJTclJOYTVP\nclRrcS9DVXp4TzlXVWdUbm0vU3JpNwp6UmVSNm1VPQotLS0tLUVORCBDRVJU\nSUZJQ0FURS0tLS0t\n",
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

function pura_salaus($salattu_data, $salasana)
{
  $avain = hash("SHA256", $salasana, true);

  $salattu_data_binaari = base64_decode($salattu_data);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
  $iv = substr($salattu_data_binaari, 0, $iv_size);

  $salattu_data_binaari = substr($salattu_data_binaari, $iv_size);

  return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $avain, $salattu_data_binaari, MCRYPT_MODE_CBC, $iv);
}
