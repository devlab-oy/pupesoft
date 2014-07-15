<?php

require("inc/parametrit.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

if (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != 'on') {
  echo "<font class='error'>";
  echo t("Voit k‰ytt‰‰ pankkiyhteytt‰ vain salatulla yhteydell‰!");
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

if ($tee == "poista") {
  if (poista_pankkiyhteys($pankkiyhteys)) {
    viesti("Pankkiyhteys poistettu");
  }
  else {
    virhe("Pankkiyhteytt‰ ei poistettu");
  }

  $tee = "";
}

if ($tee == "luo" and !pankkiyhteystiedot_kunnossa()) {
  $tee = "";
}

if ($tee == "luo" and $pin != '') {
  $generoidut_tunnukset = generoi_private_key_ja_csr();

  $params = array(
    "pin"         => $pin,
    "customer_id" => $customer_id,
    "tunnukset"   => $generoidut_tunnukset
  );

  $sertifikaatti = hae_sertifikaatti_sepasta($params);
  $private_key = $generoidut_tunnukset["private_key"];

  if (!$sertifikaatti) {
    virhe("Sertifikaatin hakeminen ep‰onnistui, tarkista PIN-koodi ja asiakastunnus");
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

// Jos k‰ytt‰j‰ ei ole antanut target id:t‰, haetaan se pankista
if ($tee == "luo" and $target_id == '') {
  $params = array(
    "certificate" => $certificate,
    "private_key" => $private_key,
    "customer_id" => $customer_id
  );

  $target_id = hae_target_id($params);

  if (!$target_id) {
    virhe("Tiedon hakeminen pankista ep‰onnistui, yrit‰ myˆhemmin uudestaan");
    $tee = "";
  }
}

if ($tee == "luo") {
  $params = array(
    "pankki"            => $pankki,
    "salatut_tunnukset" => $salatut_tunnukset,
    "customer_id"       => $customer_id,
    "target_id"         => $target_id
  );

  if (tallenna_tunnukset($params)) {
    ok("Tunnukset tallennettu");
  }
  else {
    virhe("Tunnusten tallennus ep‰onnistui");
  }

  $tee = "";
}

if ($tee == "") {
  uusi_pankkiyhteys_formi();
  pankkiyhteydet_table();
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

  foreach ($mahdolliset_pankkiyhteydet as $bic => $nimi) {
    echo "<option value='{$bic}'>{$nimi}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='customer_id'>Asiakastunnus</label></td>";
  echo "<td><input type='text' name='customer_id' id='customer_id'/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='target_id'>Aineistoryhm‰n tunnus</label></td>";
  echo "<td><input type='text' name='target_id' id='target_id'/></td>";
  echo "<td class='back'>Jos kentt‰ j‰tet‰‰n tyhj‰ksi, arvo yritet‰‰n hakea pankista</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td class='back'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='pin'>" . t("Pankilta saatu PIN-koodi") . "</label></td>";
  echo "<td><input type='text' name='pin' id='pin'/></td>";
  echo "<td class='back'>T‰yt‰, jos olet saanut pankista PIN-koodin ja aiot nyt hakea tunnukset</td>";
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
  echo "T‰yt‰ n‰m‰ kent‰t vain, jos olet jo saanut tunnukset pankista";
  echo " ja haluat nyt ladata ne j‰rjestelm‰‰n";
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
  echo "<td class='back'>Huom. salasanaa ei voi mitenk‰‰n palauttaa, jos se unohtuu</td>";
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
    virhe("Salasana t‰ytyy antaa");
    $virheet_count++;
  }

  if (empty($_POST["customer_id"])) {
    virhe("Asiakastunnus t‰ytyy antaa");
    $virheet_count++;
  }

  $filet_tyhjat = empty($_FILES["private_key"]["name"]) or empty($_FILES["certificate"]["name"]);

  if (empty($_POST["pin"]) and $filet_tyhjat) {
    virhe("PIN-koodi tai yksityinen avain ja sertifikaatti t‰ytyy antaa");
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
 * @param $params
 *
 * @return bool|string
 */
function hae_sertifikaatti_sepasta($params) {
  $pin = isset($params["pin"]) ? (string) $params["pin"] : "";
  $customer_id = isset($params["customer_id"]) ? (string) $params["customer_id"] : "";
  $tunnukset = isset($params["tunnukset"]) ? (array) $params["tunnukset"] : "";

  if (empty($pin) or empty($customer_id) or empty($tunnukset)) {
    return false;
  }

  global $sepa_pankkiyhteys_token;

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "pin"         => $pin,
      "customer_id" => $customer_id,
      "environment" => "TEST", // Voi olla joko "TEST" tai "PRODUCTION"
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
 * @param array $params
 *
 * @return bool|resource
 */
function tallenna_tunnukset($params) {
  $pankki = isset($params["pankki"]) ? $params["pankki"] : "";
  $salatut_tunnukset = isset($params["salatut_tunnukset"]) ? $params["salatut_tunnukset"] : "";
  $customer_id = isset($params["customer_id"]) ? $params["customer_id"] : "";
  $target_id = isset($params["target_id"]) ? $params["target_id"] : "";

  if (empty($pankki) or empty($salatut_tunnukset) or empty($customer_id) or empty($target_id)) {
    return false;
  }

  global $kukarow;

  $query = "INSERT INTO pankkiyhteys SET
            yhtio = '{$kukarow['yhtio']}',
            pankki = '{$pankki}',
            private_key = '{$salatut_tunnukset['private_key']}',
            certificate = '{$salatut_tunnukset['sertifikaatti']}',
            customer_id = '{$customer_id}',
            target_id = '{$target_id}'";
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
 * @param $params
 *
 * @return bool|string
 */
function hae_target_id($params) {
  $certificate = isset($params["certificate"]) ? $params["certificate"] : "";
  $private_key = isset($params["private_key"]) ? $params["private_key"] : "";
  $customer_id = isset($params["customer_id"]) ? $params["customer_id"] : "";

  if (empty($certificate) or empty($private_key) or empty($customer_id)) {
    return false;
  }

  global $sepa_pankkiyhteys_token;

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "cert"        => base64_encode($certificate),
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
    return false;
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
      virhe("Pankki ei vastaa kyselyyn, yrit‰ myˆhemmin uudestaan");
      return false;
    case 503:
      virhe("Pankki ei vastaa kyselyyn toivotulla tavalla, yrit‰ myˆhemmin uudestaan");
      return false;
    case 0:
      virhe("Sepa-palvelimeen ei jostain syyst‰ saada yhteytt‰, yrit‰ myˆhemmin uudestaan");
      return false;
  }
}

/**
 * @return array
 */
function mahdolliset_pankkiyhteydet() {
  $pankit = array(
    "NDEAFIHH" => "Nordea",
    "DABAFIHX" => "Danske Bank"
  );

  $luodut_pankit = array();

  foreach (hae_pankkiyhteydet() as $pankkiyhteys) {
    array_push($luodut_pankit, $pankkiyhteys["pankki"]);
  }

  $mahdolliset_pankkiyhteydet = array();

  foreach ($pankit as $bic => $nimi) {
    if (!in_array($bic, $luodut_pankit)) {
      $mahdolliset_pankkiyhteydet[$bic] = $nimi;
    }
  }

  return $mahdolliset_pankkiyhteydet;
}

/**
 * @return array
 */
function hae_pankkiyhteydet() {
  global $kukarow;

  $luodut_pankkiyhteydet = array();

  $query = "SELECT *
            FROM pankkiyhteys
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($luodut_pankkiyhteydet, $rivi);
  }
  return $luodut_pankkiyhteydet;
}

function pankkiyhteydet_table() {
  $pankkiyhteydet = hae_pankkiyhteydet();

  echo "<br/>";
  echo "<font class='head'>" . t("Pankkiyhteydet") . "</font>";
  echo "<hr>";

  echo "<table>";
  echo "<thead>";

  echo "<tr>";
  echo "<th>" . t("Pankki") . "</th>";
  echo "<th>" . t("Asiakastunnus") . "</th>";
  echo "<th>" . t("Aineistoryhm‰n tunnus" . "</th>");
  echo "<th></th>";
  echo "</tr>";

  echo "</thead>";

  echo "<tbody>";

  foreach ($pankkiyhteydet as $pankkiyhteys) {
    echo "<tr>";
    echo "<td>" . pankin_nimi($pankkiyhteys["pankki"]) . "</td>";
    echo "<td>{$pankkiyhteys["customer_id"]}</td>";
    echo "<td>{$pankkiyhteys["target_id"]}</td>";
    echo "<td>";
    echo "<form class='multisubmit' method='post' action='pankkiyhteysadmin.php'
                onsubmit='return confirm(\"Haluatko varmasti poistaa pankkiyhteyden?\");'>";
    echo "<input type='hidden' name='tee' value='poista'/>";
    echo "<input type='hidden' name='pankkiyhteys' value='{$pankkiyhteys["pankki"]}'/>";
    echo "<input type='submit' value='" . t("Poista") . "'/>";
    echo "</form>";
    echo "</td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";
}

/**
 * @param $bic
 *
 * @return bool|string
 */
function pankin_nimi($bic) {
  switch ($bic) {
    case "NDEAFIHH":
      return "Nordea";
    case "DABAFIHX":
      return "Danske Bank";
    default:
      return false;
  }
}

/**
 * @param $pankki
 *
 * @return resource
 */
function poista_pankkiyhteys($pankki) {
  $query = "DELETE
            FROM pankkiyhteys
            WHERE pankki = '{$pankki}'";
  return pupe_query($query);
}
