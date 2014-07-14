<?php

require("inc/parametrit.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

if (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != 'on') {
  echo "<font class='error'>";
  echo t("Voit käyttää pankkiyhteyttä vain salatulla yhteydellä!");
  echo "</font>";
  exit;
}

if (!isset($sepa_pankkiyhteys_token)) {
  echo "<font class='error'>";
  echo t("SEPA-palvelua ei ole aktivoitu.");
  echo "</font>";
  exit;
}

$tee = empty($tee) ? '' : $tee;
$pin = empty($pin) ? '' : $pin;
$target_id = empty($target_id) ? '' : $target_id;

if ($tee == "luo" and !pankkiyhteystiedot_kunnossa()) {
  $tee = "";
}

if ($tee == "luo" and $pin != '') {
  $generoidut_tunnukset = generoi_private_key_ja_csr();

  $certificate = hae_sertifikaatti_sepasta($pin, $customer_id, $generoidut_tunnukset);
  $private_key = $generoidut_tunnukset["private_key"];

  if (!$certificate) {
    virhe("Sertifikaatin hakeminen epäonnistui, tarkista PIN-koodi ja asiakastunnus");
    $tee = "";
  }

  $salatut_tunnukset = array(
    "private_key"   => salaa($private_key, $salasana),
    "sertifikaatti" => salaa($sertifikaatti, $salasana)
  );
}

if ($tee == "luo" and $pin == '') {
  $private_key = file_get_contents($_FILES["private_key"]["tmp_name"]);
  $certificate = file_get_contents($_FILES["certificate"]["tmp_name"]);

  $oikeat_keyt = openssl_x509_check_private_key($certificate, $private_key);

  if (!$oikeat_keyt) {
    virhe("Et antanut oikeaa avainparia");
    $tee = "";
  }
  else {
    $salatut_tunnukset = array(
      "private_key"   => salaa($private_key, $salasana),
      "sertifikaatti" => salaa($certificate, $salasana)
    );
  }
}

// Jos käyttäjä ei ole antanut target id:tä, haetaan se pankista
if ($tee == "luo" and $target_id == '') {
  $target_id = hae_target_id($certificate, $private_key, $customer_id);

  if (!$target_id) {
    virhe("Tiedon hakeminen pankista epäonnistui, yritä myöhemmin uudestaan");
    $tee = "";
  }
}

if ($tee == "luo") {
  if (tallenna_tunnukset($pankki, $salatut_tunnukset, $customer_id, $target_id)) {
    ok("Tunnukset tallennettu");
  }
  else {
    virhe("Tunnusten tallennus epäonnistui");
  }

  $tee = "";
}

if ($tee == "") {
  uusi_pankkiyhteys_formi();
}

function uusi_pankkiyhteys_formi() {
  $mahdolliset_pankkiyhteydet = mahdolliset_pankkiyhteydet();

  if (empty($mahdolliset_pankkiyhteydet)) {
    return viesti("Olet jo luonut kaikille pankeille yhteydet");
  }

  echo "<form action='pankkiyhteysadmin.php' method='post' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='tee' value='luo'/>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<td><label for='pankki'>" . t("Pankki, jolle pankkiyhteys luodaan") . "</label></td>";
  echo "<td>";
  echo "<select name='pankki' id='pankki'>";

  foreach ($mahdolliset_pankkiyhteydet as $pankkiyhteys) {
    echo "<option value='{$pankkiyhteys}'>{$pankkiyhteys}</option>";
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
  echo "<td class='back'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='pin'>" . t("Pankilta saatu PIN-koodi") . "</label></td>";
  echo "<td><input type='text' name='pin' id='pin'/></td>";
  echo "<td class='back'>Täytä, jos olet saanut pankista PIN-koodin ja aiot nyt hakea tunnukset</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td class='back'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='private_key'>";
  echo t("Yksityinen avain");
  echo "</label></td>";
  echo "<td><input type='file' name='private_key' id='private_key'/></td>";
  echo "<td class='back'>";
  echo "Täytä nämä kentät vain, jos olet jo saanut tunnukset pankista";
  echo " ja haluat nyt ladata ne järjestelmään";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='certificate'>";
  echo t("Sertifikaatti");
  echo "</label></td>";
  echo "<td><input type='file' name='certificate' id='certificate'/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td class='back'></td>";
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

  $filet_tyhjat = empty($_FILES["private_key"]["name"]) or empty($_FILES["certificate"]["name"]);

  if (empty($_POST["pin"]) and $filet_tyhjat) {
    virhe("PIN-koodi tai yksityinen avain ja sertifikaatti täytyy antaa");
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
  global $sepa_pankkiyhteys_token;

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "pin"         => $pin,
      "customer_id" => $customer_id,
      "environment" => "PRODUCTION", // Voi olla joko "TEST" tai "PRODUCTION"
      "csr"         => base64_encode($tunnukset["csr"])
    ),
    "url"     => "https://sepa.devlab.fi/api/nordea/get_certificate",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
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
 * @param $pankki
 * @param $salatut_tunnukset
 * @param $customer_id
 * @param $target_id
 *
 * @return resource
 */
function tallenna_tunnukset($pankki, $salatut_tunnukset, $customer_id, $target_id) {
  global $kukarow;

  $query = "INSERT INTO pankkiyhteys (yhtio, pankki, private_key, certificate, customer_id, target_id)
            VALUES
            (
              '{$kukarow['yhtio']}', '{$pankki}', '{$salatut_tunnukset['private_key']}',
              '{$salatut_tunnukset['sertifikaatti']}', '{$customer_id}', '{$target_id}'
            )";
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
  global $sepa_pankkiyhteys_token;

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "cert"        => base64_encode($sertifikaatti),
      "private_key" => base64_encode($private_key),
      "customer_id" => $customer_id
    ),
    "url"     => "https://sepa.devlab.fi/api/nordea/get_user_info",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
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

function mahdolliset_pankkiyhteydet() {
  global $kukarow;

  $pankit = array("Nordea", "Danske");

  $luodut_pankkiyhteydet = array();

  $query = "SELECT pankki
            FROM pankkiyhteys
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  while ($rivi = mysql_fetch_assoc($result)) {
      array_push($luodut_pankkiyhteydet, $rivi["pankki"]);
  }

  $mahdolliset_pankkiyhteydet = array();

  foreach ($pankit as $pankki) {
    if (!in_array($pankki, $luodut_pankkiyhteydet)) {
      array_push($mahdolliset_pankkiyhteydet, $pankki);
    }
  }

  return $mahdolliset_pankkiyhteydet;
}
