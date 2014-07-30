<?php

// Käyttöliittymän funktiot

/**
 * Tarkistaa sepa-palvelimelta tulleen vastauksen koodin ja palauttaa true tai false sen
 * perusteella. Echottaa myos mahdollisen virheilmoituksen
 *
 * @param $vastaus
 *
 * @return bool
 */
function vastaus_kunnossa($koodi) {
  switch ($koodi) {
    case 200:
      return true;
    case 400:
      virhe("Palvelimelta saatiin virheilmoitus, tarkista, että antamasi tiedot ovat kunnossa");
      return false;
    case 500:
      virhe("Pankki ei vastaa kyselyyn, yritä myöhemmin uudestaan");
      return false;
    case 503:
      virhe("Pankki ei vastaa kyselyyn toivotulla tavalla, yritä myöhemmin uudestaan");
      return false;
    case 0:
      virhe("Sepa-palvelimeen ei jostain syystä saada yhteyttä, yritä myöhemmin uudestaan");
      return false;
    default:
      virhe("Tuntematon virhekoodi");
      return false;
  }
}

/**
 * Piirtaa taulun palvelimelta ladatuille tiedostoille ja nayttaa siina tiedoston viitteen ja
 * statuksen
 *
 * @param $tiedostot
 */
function tiedostot_table($tiedostot) {
  echo "<br/>";

  echo "<table>";

  echo "<thead>";
  echo "<tr>";
  echo "<th>" . t("Viite") . "</th>";
  echo "<th>" . t("Status") . "</th>";
  echo "</tr>";
  echo "</thead>";

  echo "<tbody>";

  foreach ($tiedostot as $tiedosto) {
    echo "<tr>";
    echo "<td>{$tiedosto["viite"]}</td>";
    echo "<td>{$tiedosto["status"]}</td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";

  echo "<br/><br/>";
}

/**
 * @param string $viesti
 */
function virhe($viesti) {
  global $kukarow, $yhtiorow;

  echo "<font class='error'>";
  echo t($viesti);
  echo "</font>";
  echo "<br/>";
}

/**
 * @param string $viesti
 */
function ok($viesti) {
  global $kukarow, $yhtiorow;

  echo "<font class='ok'>";
  echo t($viesti);
  echo "</font>";
  echo "<br/>";
}

/**
 * @param string $viesti
 */
function viesti($viesti) {
  global $kukarow, $yhtiorow;

  echo "<font class='message'>";
  echo t($viesti);
  echo "</font>";
  echo "<br/>";
}


// Pankkiyhteys tietokantafunktiot

/**
 * Hakee kaikki yhtion pankkiyhteydet tietokannasta ja lisaa pankin nimen palautettavaan arrayhyn
 *
 * @return array
 */
function hae_pankkiyhteydet() {
  global $kukarow, $yhtiorow;

  $luodut_pankkiyhteydet = array();
  $tuetut_pankit = tuetut_pankit();

  $query = "SELECT *
            FROM pankkiyhteys
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  while ($rivi = mysql_fetch_assoc($result)) {
    $rivi["pankin_nimi"] = $tuetut_pankit[$rivi["pankki"]]["nimi"];
    array_push($luodut_pankkiyhteydet, $rivi);
  }

  return $luodut_pankkiyhteydet;
}

/**
 * Hakee pankkiyhteydet, joita ei ole viela luotu kyseiselle yhtiolle
 *
 * @return array
 */
function mahdolliset_pankkiyhteydet() {
  global $kukarow, $yhtiorow;

  $pankit = tuetut_pankit();

  $luodut_pankit = array();

  foreach (hae_pankkiyhteydet() as $pankkiyhteys) {
    array_push($luodut_pankit, $pankkiyhteys["pankki"]);
  }

  $mahdolliset_pankkiyhteydet = array();

  foreach ($pankit as $bic => $nimet) {
    if (!in_array($bic, $luodut_pankit)) {
      $mahdolliset_pankkiyhteydet[$bic] = $nimet["nimi"];
    }
  }

  return $mahdolliset_pankkiyhteydet;
}

/**
 * @param string $pankki
 *
 * @return resource
 */
function poista_pankkiyhteys($pankki) {
  global $kukarow, $yhtiorow;

  $query = "DELETE
            FROM  pankkiyhteys
            WHERE pankki = '{$pankki}'
            AND   yhtio  = '{$kukarow["yhtio"]}'";
  return pupe_query($query);
}


// SEPA API fuktiot

/**
 * Lataa sepasta tiedostolistan yhdelle tiedostotyypille. Palauttaa listan tiedostojen
 * viitenumeroita
 *
 * @param array $params
 *
 * @return array|bool
 */
function sepa_download_file_list($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $tunnus       = parameter($params["pankkiyhteys_tunnus"]);
  $salasana     = parameter($params["pankkiyhteys_salasana"]);
  $file_type    = parameter($params["file_type"]);
  $status       = parameter($params["status"]);
  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys or empty($file_type) or empty($status)) {
    return false;
  }

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "signing_certificate"    => $pankkiyhteys["signing_certificate"],
      "signing_private_key"    => $pankkiyhteys["signing_private_key"],
      "encryption_certificate" => $pankkiyhteys["encryption_certificate"],
      "encryption_private_key" => $pankkiyhteys["encryption_private_key"],
      "customer_id"            => $pankkiyhteys["customer_id"],
      "target_id"              => $pankkiyhteys["target_id"],
      "file_type"              => $file_type,
      "status"                 => $status,
    ),
    "url" => "https://sepa.devlab.fi/api/{$bank}/download_file_list",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  list($code, $response) = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($code)) {
    return false;
  }

  $tiedostot = $response["files"];
  $viitteet = array();

  foreach ($tiedostot as $tiedosto) {
    array_push($viitteet, $tiedosto["fileReference"]);
  }

  return $viitteet;
}

/**
 * Lataa pankista tiedostot annetuiden viitenumeroiden ja tiedostotyypin perusteella. Tallentaa
 * tiedostot /tmp -kansioon tiedostotyypin nimisen kansion alle. Palauttaa myos arrayin tiedostojen
 * viitteista ja siita onnistuiko niiden lataus vai ei.
 *
 * @param array $params
 *
 * @return array|bool
 */
function sepa_download_files($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $tunnus       = parameter($params["pankkiyhteys_tunnus"]);
  $salasana     = parameter($params["pankkiyhteys_salasana"]);
  $file_type    = parameter($params["file_type"]);
  $viitteet     = parameter($params["viitteet"]);
  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys or empty($file_type) or empty($viitteet)) {
    return false;
  }

  $tiedostot = array();

  foreach ($viitteet as $viite) {
    $parameters = array(
      "method"  => "POST",
      "data"    => array(
        "signing_certificate"    => $pankkiyhteys["signing_certificate"],
        "signing_private_key"    => $pankkiyhteys["signing_private_key"],
        "encryption_certificate" => $pankkiyhteys["encryption_certificate"],
        "encryption_private_key" => $pankkiyhteys["encryption_private_key"],
        "customer_id"            => $pankkiyhteys["customer_id"],
        "target_id"              => $pankkiyhteys["target_id"],
        "file_type"              => $file_type,
        "file_reference"         => $viite
      ),
      "url" => "https://sepa.devlab.fi/api/{$bank}/download_file",
      "headers" => array(
        "Content-Type: application/json",
        "Authorization: Token token={$sepa_pankkiyhteys_token}"
      )
    );

    list($code, $response) = pupesoft_rest($parameters);

    if (vastaus_kunnossa($code)) {
      $tiedosto = array(
        "viite"  => $viite,
        "status" => "OK",
        "data" => $response["data"]
      );
    }
    else {
      $tiedosto = array(
        "viite"  => $viite,
        "status" => "Virhe",
        "data" => null
      );
    }

    array_push($tiedostot, $tiedosto);
  }

  return $tiedostot;
}

/**
 * Lahettaa maksuaineiston pankkiin. Talla hetkella ainut hyvaksyttava tyyppi on NDCORPAYS.
 *
 * @param array $params
 *
 * @return array|bool
 */
function sepa_upload_file($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $tunnus        = parameter($params["pankkiyhteys_tunnus"]);
  $salasana      = parameter($params["pankkiyhteys_salasana"]);
  $file_type     = parameter($params["file_type"]);
  $maksuaineisto = parameter($params["maksuaineisto"]);
  $pankkiyhteys  = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys or empty($file_type) or empty($maksuaineisto)) {
    return false;
  }

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "signing_certificate"    => $pankkiyhteys["signing_certificate"],
      "signing_private_key"    => $pankkiyhteys["signing_private_key"],
      "encryption_certificate" => $pankkiyhteys["encryption_certificate"],
      "encryption_private_key" => $pankkiyhteys["encryption_private_key"],
      "customer_id"            => $pankkiyhteys["customer_id"],
      "target_id"              => $pankkiyhteys["target_id"],
      "file_type"              => $file_type,
      "content"                => $maksuaineisto
    ),
    "url"     => "https://sepa.devlab.fi/api/{$bank}/upload_file",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  list($code, $response) = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($code)) {
    return false;
  }

  return $response;
}

/**
 * Lataa pankin allekirjoittaman sertifikaatin pankista. Ottaa parametreissa certificate signing
 * requestin ja pin koodin.
 *
 * @param array $params
 *
 * @return bool|string
 */
function sepa_get_certificate($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $bank           = parameter($params["bank"]);
  $customer_id    = parameter($params["customer_id"]);
  $pin            = parameter($params["pin"]);
  $signing_csr    = parameter($params["signing_csr"]);
  $encryption_csr = parameter($params["encryption_csr"]);

  if (empty($bank)
      or empty($customer_id)
      or empty($pin)
      or empty($signing_csr)) {
    return false;
  }

  $parameters = array(
    "method" => "POST",
    "data" => array(
      "pin" => $pin,
      "customer_id" => $customer_id,
      "environment" => "production", // Voi olla joko "TEST" tai "PRODUCTION"
      "signing_csr" => base64_encode($signing_csr),
      "encryption_csr" => base64_encode($encryption_csr),
    ),
    "url" => "https://sepa.devlab.fi/api/{$bank}/get_certificate",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  list($code, $response) = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($code)) {
    return false;
  }

  $return = array(
    "bank_encryption_certificate" => base64_decode($response["bank_encryption_certificate"]),
    "bank_root_certificate"       => base64_decode($response["bank_root_certificate"]),
    "own_encryption_certificate"  => base64_decode($response["own_encryption_certificate"]),
    "own_signing_certificate"     => base64_decode($response["own_signing_certificate"]),
    "ca_certificate"              => base64_decode($response["ca_certificate"]),
  );

  return $return;
}

/**
 * Hakee aineistoryhman tunnuksen (target_id) pankista
 *
 * @param array $params
 *
 * @return string|bool
 */
function sepa_get_user_info($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $tunnus       = parameter($params["pankkiyhteys_tunnus"]);
  $salasana     = parameter($params["pankkiyhteys_salasana"]);
  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys) {
    return false;
  }

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "signing_certificate"    => $pankkiyhteys["signing_certificate"],
      "signing_private_key"    => $pankkiyhteys["signing_private_key"],
      "encryption_certificate" => $pankkiyhteys["encryption_certificate"],
      "encryption_private_key" => $pankkiyhteys["encryption_private_key"],
      "customer_id"            => $pankkiyhteys["customer_id"],
    ),
    "url" => "https://sepa.devlab.fi/api/{$bank}/get_user_info",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  list($code, $response) = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($code)) {
    return false;
  }

  $target_id = $response["userFileTypes"][0]["targetId"];

  if (empty($target_id)) {
    return false;
  }

  return $target_id;
}


// Sertifikaatti / salausfunktiot

/**
 * Hakee pankkiyhteysrivin tietokannasta ja purkaa private_keyn ja sertifikaatin salauksen.
 * Palauttaa pankkiyhteys arrayn
 *
 * @param string $pankkiyhteys_tunnus
 * @param string $salasana
 *
 * @return array
 */
function hae_pankkiyhteys_ja_pura_salaus($pankkiyhteys_tunnus, $salasana) {
  global $kukarow, $yhtiorow;

  $tuetut_pankit = tuetut_pankit();
  $pankkiyhteys_tunnus = (int) $pankkiyhteys_tunnus;

  $query = "SELECT *
            FROM pankkiyhteys
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tunnus = {$pankkiyhteys_tunnus}";
  $result = pupe_query($query);

  // Jos pankkiyhteyttä ei löydy
  if (mysql_num_rows($result) != 1) {
    return false;
  }

  $pankki = mysql_fetch_assoc($result);

  // Listään resulttiin pankin lyhyt nimi
  $pankki["pankki_lyhyt_nimi"] = $tuetut_pankit[$pankkiyhteys["pankki"]]["lyhyt_nimi"];

  // Puretaan salaus private keystä
  $pankki["signing_private_key"] = pura_salaus($pankki["signing_private_key"], $salasana);

  // Jos salauksen purkaminen ei onnistu, lopetetaan tähä
  if (!openssl_pkey_get_private($pankki["signing_private_key"])) {
    return false;
  }

  // Puretaan loputkin salaukset
  $pankki["signing_certificate"]         = pura_salaus($pankki["signing_certificate"], $salasana);
  $pankki["encryption_certificate"]      = pura_salaus($pankki["encryption_certificate"], $salasana);
  $pankki["encryption_private_key"]      = pura_salaus($pankki["encryption_private_key"], $salasana);
  $pankki["bank_encryption_certificate"] = pura_salaus($pankki["bank_encryption_certificate"], $salasana);
  $pankki["bank_root_certificate"]       = pura_salaus($pankki["bank_root_certificate"], $salasana);
  $pankki["ca_certificate"]              = pura_salaus($pankki["ca_certificate"], $salasana);

  return $pankki;
}

/**
 * Laitaa kaikki tietyn tyyppiset tiedostot pankista ja palauttaa arrayn tiedostojen viitteista ja
 * statuksista
 *
 * @param array $params
 *
 * @return array|bool
 */
function sepa_lataa_kaikki_uudet_tiedostot($params) {
  global $kukarow, $yhtiorow;

  // Lisätään paramsiin status NEW, että haetaan vain uudet filet
  $params['status'] = 'NEW';

  // Haetaan filelista pankista
  $viitteet = sepa_download_file_list($params);

  if (!$viitteet) {
    return false;
  }

  // Lisätään paramssiin hettavat referencet
  $params["viitteet"] = $viitteet;

  $tiedostot = sepa_download_files($params);

  if (!$tiedostot) {
    return false;
  }

  return $tiedostot;
}

/**
 * Generoi private keyn ja certificate signing requestin. Tiedot signing requestiin haetaan
 * yhtiorowista. Palauttaa arrayn, jossa private_key ja csr.
 *
 * @return array
 */
function generoi_private_key_ja_csr() {
  global $kukarow, $yhtiorow;

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
 * Salaa annetun datan annetulla salasanalla. Salaus on 256-bittinen AES-salaus.
 *
 * @param string $salattava_data
 * @param string $salasana
 *
 * @return string
 */
function salaa($salattava_data, $salasana) {
  $avain = hash("SHA256", $salasana, true);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
  $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

  $salattu_data = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $avain, $salattava_data, MCRYPT_MODE_CBC, $iv);
  $salattu_data = $iv . $salattu_data;

  return base64_encode($salattu_data);
}

/**
 * Purkaa 256-bittisen AES-salauksen annetusta datasta annetulla salasanalla
 *
 * @param string $salattu_data
 * @param string $salasana
 *
 * @return string
 */
function pura_salaus($salattu_data, $salasana) {
  $avain = hash("SHA256", $salasana, true);

  $salattu_data_binaari = base64_decode($salattu_data);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);

  //  Pitaa olla monta spacee, jotta UTF-8 konversio ei riko toiminnallisuutta
  $iv = substr    ($salattu_data_binaari, 0, $iv_size);

  //  Pitaa olla monta spacee, jotta UTF-8 konversio ei riko toiminnallisuutta
  $salattu_data_binaari = substr    ($salattu_data_binaari, $iv_size);

  return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $avain, $salattu_data_binaari, MCRYPT_MODE_CBC, $iv);
}

/**
 * Palauttaa arrayn tuetuista pankeista muodossa BIC => [lyhyt_nimi, pitka_nimi]
 *
 * @return array
 */
function tuetut_pankit() {
  return array(
    "NDEAFIHH" => array(
      "nimi"       => "Nordea",
      "lyhyt_nimi" => "nordea"
    ),
    "DABAFIHX" => array(
      "nimi"       => "Danske Bank",
      "lyhyt_nimi" => "danske"
    ),
  );
}

function parameter($param) {
  empty($param) ? '' : trim($param);
}
