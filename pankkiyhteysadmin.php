<?php

const SEPA_OSOITE = "https://sepa.devlab.fi/api/";
const ACCESS_TOKEN = "Bexvxb10H1XBT36x42Lv8jEEKnA6";

require("inc/parametrit.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys Admin') . "</font>";
echo "<hr>";

if (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != 'on') {
  echo "<font class='error'>";
  echo t("Voit käyttää pankkiyhteyttä vain salatulla yhteydellä!");
  echo "</font>";
  exit;
}

$tee = isset($tee) ? $tee : '';

if ($tee == "luo") {
  if (!pankkiyhteystiedot_kunnossa()) {
    $tee = "";
  }
}

if ($tee == "luo") {
  $generoidut_tunnukset = generoi_private_key_ja_csr();
  $sertifikaatti = hae_sertifikaatti_sepasta($pin, $customer_id, $generoidut_tunnukset);

  if (!$sertifikaatti) {
    virhe("Sertifikaatin hakeminen epäonnistui, tarkista PIN-koodi ja asiakastunnus");
    $tee = "";
  }

  $salatut_tunnukset = array(
    "private_key"   => salaa($generoidut_tunnukset["private_key"], $salasana),
    "sertifikaatti" => salaa($sertifikaatti, $salasana)
  );
}

// Jos käyttäjä ei ole antanut target id:tä, haetaan se pankista
if ($tee == "luo" and $target_id == '') {
  $target_id = hae_target_id($sertifikaatti, $generoidut_tunnukset["private_key"], $customer_id);

  if (!$target_id) {
    virhe("Tiedon hakeminen pankista epäonnistui, yritä myöhemmin uudestaan");
    $tee = "";
  }
}

if ($tee == "luo") {
  if (tallenna_tunnukset($salatut_tunnukset, $customer_id, $target_id, $tili)) {
    ok("Tunnukset tallennettu");
    $tee = "";
  }

  else {
    virhe("Tunnusten tallennus epäonnistui");
    $tee = "";
  }
}

if ($tee == "") {
  uusi_pankkiyhteys_formi();
}

function uusi_pankkiyhteys_formi() {
  $tilit = hae_uudet_tilit();

  if (count($tilit) == 0) {
    return viesti("Kaikille tileille on jo luotu yhteydet");
  }

  echo "<form action='pankkiyhteysadmin.php' method='post'>";
  echo "<input type='hidden' name='tee' value='luo'/>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<td><label for='tili'>" . t("Tili, jolle pankkiyhteys luodaan") . "</label></td>";
  echo "<td>";
  echo "<select name='tili' id='tili'>";

  foreach ($tilit as $tili) {
    echo "<option value='{$tili["tunnus"]}'>{$tili["nimi"]}</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='customer_id'>Asiakastunnus</label></td>";
  echo "<td><input type='text' name='customer_id' id='customer_id'/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='target_id'>Aineistoryhmän tunnus</label></td>";
  echo "<td><input type='text' name='target_id' id='target_id'/></td>";
  echo "<td class='back'>Jos kenttä jätetään tyhjäksi, arvo yritetään hakea pankista</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='pin'>" . t("Pankilta saatu PIN-koodi") . "</label></td>";
  echo "<td><input type='text' name='pin' id='pin'/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='salasana'>" . t("Salasana, jolla pankkiyhteystunnukset suojataan");
  echo "</label></td>";
  echo "<td><input type='password' name='salasana' id='salasana'/></td>";
  echo "<td class='back'>Huom. salasanaa ei voi mitenkään palauttaa, jos se unohtuu</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='salasanan_vahvistus'>" . t("Salasanan vahvistus") . "</label></td>";
  echo "<td><input type='password' name='salasanan_vahvistus' id='salasanan_vahvistus'/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td class='back'><input type='submit' value='" . t("Luo pankkiyhteys") . "'/></td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table>";
  echo "</form>";
}

/**
 * @return array
 */
function hae_uudet_tilit() {
  global $kukarow;

  $query = "SELECT tunnus, nimi
            FROM yriti
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND bic != ''
            AND bic IS NOT NULL
            AND sepa_customer_id = ''";
  $result = pupe_query($query);

  $tilit = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($tilit, $rivi);
  }

  return $tilit;
}

/**
 * @return bool
 */
function pankkiyhteystiedot_kunnossa() {
  $virheet_count = 0;

  if (empty($_POST["salasana"])) {
    virhe("Salasana täytyy antaa");
    $virheet_count++;
  }

  if (empty($_POST["customer_id"])) {
    virhe("Asiakastunnus täytyy antaa");
    $virheet_count++;
  }

  if (empty($_POST["pin"])) {
    virhe("PIN-koodi täytyy antaa");
    $virheet_count++;
  }

  if ($_POST["salasana"] != $_POST["salasanan_vahvistus"]) {
    virhe("Salasanan vahvistus ei vastaa salasanaa");
    $virheet_count++;
  }

  if ($virheet_count == 0) {
    return true;
  }

  return false;
}

function virhe($viesti) {
  echo "<font class='error'>{$viesti}</font><br/>";
}

function ok($viesti) {
  echo "<font class='ok'>{$viesti}</font><br/>";
}

function viesti($viesti) {
  echo "<font class='message'>{$viesti}</font><br/>";
}

/**
 * @return array
 */
function generoi_private_key_ja_csr() {
  global $yhtiorow;

  $key_config = array(
    "digest_alg"       => "sha1",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA
  );

  $csr_info = array(
    "countryName"      => $yhtiorow["maa"],
    "localityName"     => $yhtiorow["kotipaikka"],
    "organizationName" => $yhtiorow["nimi"],
    "commonName"       => $yhtiorow["nimi"],
    "emailAddress"     => $yhtiorow["email"]
  );

  $key = openssl_pkey_new($key_config);
  $csr = openssl_csr_new($csr_info, $key);

  openssl_pkey_export($key, $private_key);
  openssl_csr_export($csr, $csrout);

  return array(
    "private_key" => $private_key,
    "csr"         => $csrout
  );
}

/**
 * @param $pin
 * @param $customer_id
 * @param $tunnukset
 *
 * @return string
 */
function hae_sertifikaatti_sepasta($pin, $customer_id, $tunnukset) {
  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "pin"         => $pin,
      "customer_id" => $customer_id,
      "environment" => "TEST",
      "csr"         => base64_encode($tunnukset["csr"])
    ),
    "url"     => SEPA_OSOITE . "nordea/get_certificate",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token=" . ACCESS_TOKEN
    )
  );

  $vastaus = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($vastaus)) {
    return false;
  }

  $sertifikaatti = base64_decode($vastaus[1]["content"]);

  return $sertifikaatti;
}

/**
 * @param $salatut_tunnukset
 * @param $customer_id
 * @param $target_id
 * @param $tili
 *
 * @return resource
 */
function tallenna_tunnukset($salatut_tunnukset, $customer_id, $target_id, $tili) {
  global $kukarow;

  $query = "UPDATE yriti SET
            private_key = '{$salatut_tunnukset["private_key"]}',
            certificate = '{$salatut_tunnukset["sertifikaatti"]}',
            sepa_customer_id = '{$customer_id}',
            sepa_target_id = '{$target_id}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = {$tili}";
  $result = pupe_query($query);

  return $result;
}

/**
 * @param $data
 * @param $salasana
 *
 * @return string
 */
function salaa($data, $salasana) {
  $avain = hash("SHA256", $salasana, true);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
  $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

  $salattu_data = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $avain, $data, MCRYPT_MODE_CBC, $iv);
  $salattu_data = $iv . $salattu_data;

  return base64_encode($salattu_data);
}

/**
 * @param $sertifikaatti
 * @param $private_key
 * @param $customer_id
 *
 * @return string
 */
function hae_target_id($sertifikaatti, $private_key, $customer_id) {
  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "cert"        => base64_encode($sertifikaatti),
      "private_key" => base64_encode($private_key),
      "customer_id" => $customer_id
    ),
    "url"     => SEPA_OSOITE . "nordea/get_user_info",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token=" . ACCESS_TOKEN
    )
  );

  $vastaus = pupesoft_rest($parameters);

  $target_id = $vastaus[1]["userFileTypes"][0]["targetId"];

  if (empty($target_id)) {
    return null;
  }

  return $target_id;
}

/**
 * @param $vastaus
 *
 * @return bool
 */
function vastaus_kunnossa($vastaus) {
  switch ($vastaus[0]) {
    case 200:
      return true;
    case 500:
      virhe("Pankki ei vastaa kyselyyn, yritä myöhemmin uudestaan");

      return false;
    case 503:
      virhe("Pankki ei vastaa kyselyyn toivotulla tavalla, yritä myöhemmin uudestaan");

      return false;
    case 0:
      virhe("Sepa-palvelimeen ei jostain syystä saada yhteyttä, yritä myöhemmin uudestaan");

      return false;
  }
}
