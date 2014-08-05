<?php

// K‰yttˆliittym‰n funktiot

/**
 * Tarkistaa sepa-palvelimelta tulleen vastauksen koodin ja palauttaa true tai false sen
 * perusteella. Echottaa myos mahdollisen virheilmoituksen
 *
 * @param unknown $vastaus
 *
 * @return bool
 */


function vastaus_kunnossa($koodi) {
  switch ($koodi) {
  case 200:
    return true;
  case 400:
    virhe("Palvelimelta saatiin virheilmoitus, tarkista, ett‰ antamasi tiedot ovat kunnossa");
    return false;
  case 500:
    virhe("Pankki ei vastaa kyselyyn, yrit‰ myˆhemmin uudestaan");
    return false;
  case 503:
    virhe("Pankki ei vastaa kyselyyn toivotulla tavalla, yrit‰ myˆhemmin uudestaan");
    return false;
  case 0:
    virhe("Sepa-palvelimeen ei jostain syyst‰ saada yhteytt‰, yrit‰ myˆhemmin uudestaan");
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
 * @param unknown $tiedostot
 */
function tiedostot_table($tiedostot, $filelist) {
  echo "<br/>";

  echo "<table>";

  echo "<thead>";
  echo "<tr>";
  echo "<th>" . t("Pankkitili") . "</th>";
  echo "<th>" . t("Aineiston p‰iv‰ys") . "</th>";
  echo "<th>" . t("Haun status") . "</th>";
  echo "</tr>";
  echo "</thead>";

  echo "<tbody>";

  foreach ($tiedostot as $tiedosto) {

    // Haetaan tiedoston tiedot filelistasta, ett‰ saadaan p‰iv‰m‰‰r‰ ja tilinumero
    $tiedot = search_array_key_for_value_recursive($filelist, "fileReference", $tiedosto["viite"]);
    $_date = date("d.m.Y h:i:s", strtotime($tiedot[0]["fileTimestamp"]));
    $_tili = $tiedot[0]["serviceId"];

    echo "<tr class='aktiivi'>";
    echo "<td>{$_tili}</td>";
    echo "<td>{$_date}</td>";
    echo "<td>{$tiedosto["status"]}</td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";

  echo "<br/><br/>";
}

function toggle_all($toggler_id, $toggled_class) {
  echo "
  <script type='text/javascript' language='javascript'>
    $(function () {
      $('#{$toggler_id}').toggle(
        function() {
          $('.{$toggled_class}').prop('checked', true);
        },
        function() {
          $('.{$toggled_class}').prop('checked', false);
        }
    );
    });
  </script>
  ";
}

function filelist_table($array, $type) {
  global $kukarow, $yhtiorow;

  if (!is_array($array) or count($array) == 0) {
    echo "<font class='message>";
    echo t("Yht‰‰n %s-aineistoa ei ole saavavilla!", $kukarow['kieli'], $type);
    echo "</font>";
    return;
  }
  echo "<table>";

  echo "<thead>";
  echo "<tr>";
  echo "<th>" . t("Pankkitili") . "</th>";
  echo "<th>" . t("Aineiston p‰iv‰ys") . "</th>";
  echo "<th>" . t("Status") . "</th>";
  echo "<th>" . t("Valitse") . "</th>";
  echo "</tr>";
  echo "</thead>";

  echo "<tbody>";

  foreach ($array['files'] as $tiedosto) {
    $_viite = $tiedosto["fileReference"];
    $_date = date("d.m.Y h:i:s", strtotime($tiedosto["fileTimestamp"]));
    $_status = $tiedosto["status"];

    // Ruksataan, jos tiedosto on uusi ja tehd‰‰n selkokielisempi
    if (strtolower($_status) == 'new') {
      $_chk = "checked";
      $_status = t("Uusi");
    }
    else {
     $_chk = '';
     $_status = t("Haettu");
    }

    echo "<tr class='aktiivi'>";
    echo "<td>{$tiedosto["serviceId"]}</td>";
    echo "<td>{$_date}</td>";
    echo "<td>{$_status}</td>";
    echo "<td>";
    echo "<input type='checkbox' {$_chk} value='{$_viite}' name='{$type}_references[]' class='{$type}_boxes'>";
    echo "</td>";
    echo "</tr>";
  }

  echo "<tr>";
  echo "<th colspan='3'>";
  echo t("Ruksaa kaikki");
  echo "</th>";
  echo "<td>";
  echo "<input type='checkbox' id='{$type}_toggler'>";
  echo "</td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table>";
}

/**
 *
 * @param string  $viesti
 */
function virhe($viesti) {
  global $kukarow, $yhtiorow;

  echo "<font class='error'>";
  echo t($viesti);
  echo "</font>";
  echo "<br/>";
}


/**
 *
 * @param string  $viesti
 */
function ok($viesti) {
  global $kukarow, $yhtiorow;

  echo "<font class='ok'>";
  echo t($viesti);
  echo "</font>";
  echo "<br/>";
}


/**
 *
 * @param string  $viesti
 */
function viesti($viesti) {
  global $kukarow, $yhtiorow;

  echo "<font class='message'>";
  echo t($viesti);
  echo "</font>";
  echo "<br/>";
}

function sepa_pankkiyhteys_kunnossa() {
  global $yhtiorow, $kukarow, $sepa_pankkiyhteys_token;

  if (SEPA_PANKKIYHTEYS === false) {
    echo "<font class='error'>";
    echo t("SEPA-palvelua ei ole aktivoitu.");
    echo "<br>";

    if (empty($sepa_pankkiyhteys_token)) {
      echo "<br>";
      echo t("Sepa palvelun tunniste on asentamatta!");
    }

    if (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != 'on') {
      echo "<br>";
      echo t("Voit k‰ytt‰‰ pankkiyhteytt‰ vain salatulla yhteydell‰!");
    }

    if ($yhtiorow['pankkitiedostot'] != "F") {
      echo "<br>";
      echo t("Voit k‰ytt‰‰ pankkiyhteytt‰ vain pankkitiedostot -parametrill‰");
      echo ": \"";
      echo t("P‰iv‰t yhdistet‰‰n, yksi tiedosto per pankki");
      echo "\"!";
    }

    echo "</font>";

    exit;
  }
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
 *
 * @param string  $pankki
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

function sepa_download_file_list($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $tunnus       = parameter($params["pankkiyhteys_tunnus"]);
  $salasana     = parameter($params["pankkiyhteys_salasana"]);
  $file_type    = parameter($params["file_type"]);
  $status       = parameter($params["status"]);
  $target_id    = parameter($params["target_id"]);
  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys or empty($file_type) or empty($status)) {
    return false;
  }

  $bank = $pankkiyhteys['bank'];

  // Nordealla, pit‰‰ hakea filetypen "target_id"
  // Haetaan vaan jos ei ole passattu parametrin‰
  if ($bank == "nordea" and $target_id == "") {
    $target_id = sepa_target_id_for_file_type($params);

    if ($target_id === false) {
      return false;
    }
  }

  // Varmistetaan, ett‰ ei kutsuta v‰‰r‰ll‰ filetypell‰
  $file_type = sepa_file_type_check($bank, $file_type);

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "signing_certificate"    => $pankkiyhteys["signing_certificate"],
      "signing_private_key"    => $pankkiyhteys["signing_private_key"],
      "encryption_certificate" => $pankkiyhteys["bank_encryption_certificate"],
      "encryption_private_key" => $pankkiyhteys["encryption_private_key"],
      "customer_id"            => $pankkiyhteys["customer_id"],
      "target_id"              => $target_id,
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

  // Lis‰t‰‰n target id_responseen, niin ei tartte hakea uudestaan jos kutsutaan per‰kk‰in
  $response["target_id"] = $target_id;

  return $response;
}

function sepa_download_files($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $tunnus       = parameter($params["pankkiyhteys_tunnus"]);
  $salasana     = parameter($params["pankkiyhteys_salasana"]);
  $file_type    = parameter($params["file_type"]);
  $target_id    = parameter($params["target_id"]);
  $viitteet     = parameter($params["viitteet"]);
  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys or empty($file_type) or empty($viitteet)) {
    return false;
  }

  $bank = $pankkiyhteys['bank'];

  // Nordealla, pit‰‰ hakea filetypen "target_id"
  // Haetaan vaan jos ei ole passattu parametrin‰
  if ($bank == "nordea" and $target_id == "") {
    $target_id = sepa_target_id_for_file_type($params);

    if ($target_id === false) {
      return false;
    }
  }

  // Varmistetaan, ett‰ ei kutsuta v‰‰r‰ll‰ filetypell‰
  $file_type = sepa_file_type_check($bank, $file_type);

  $tiedostot = array();

  foreach ($viitteet as $viite) {
    $parameters = array(
      "method"  => "POST",
      "data"    => array(
        "signing_certificate"    => $pankkiyhteys["signing_certificate"],
        "signing_private_key"    => $pankkiyhteys["signing_private_key"],
        "encryption_certificate" => $pankkiyhteys["bank_encryption_certificate"],
        "encryption_private_key" => $pankkiyhteys["encryption_private_key"],
        "customer_id"            => $pankkiyhteys["customer_id"],
        "target_id"              => $target_id,
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

function sepa_upload_file($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $tunnus        = parameter($params["pankkiyhteys_tunnus"]);
  $salasana      = parameter($params["pankkiyhteys_salasana"]);
  $file_type     = parameter($params["file_type"]);
  $maksuaineisto = parameter($params["maksuaineisto"]);
  $target_id     = parameter($params["target_id"]);
  $pankkiyhteys  = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys or empty($file_type) or empty($maksuaineisto)) {
    return false;
  }

  $bank = $pankkiyhteys['bank'];

  // Nordealla, pit‰‰ hakea filetypen "target_id"
  // Haetaan vaan jos ei ole passattu parametrin‰
  if ($bank == "nordea" and $target_id == "") {
    $target_id = sepa_target_id_for_file_type($params);

    if ($target_id === false) {
      return false;
    }
  }

  // Varmistetaan, ett‰ ei kutsuta v‰‰r‰ll‰ filetypell‰
  $file_type = sepa_file_type_check($bank, $file_type);

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "signing_certificate"    => $pankkiyhteys["signing_certificate"],
      "signing_private_key"    => $pankkiyhteys["signing_private_key"],
      "encryption_certificate" => $pankkiyhteys["bank_encryption_certificate"],
      "encryption_private_key" => $pankkiyhteys["encryption_private_key"],
      "customer_id"            => $pankkiyhteys["customer_id"],
      "target_id"              => $target_id,
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
      "encryption_certificate" => $pankkiyhteys["bank_encryption_certificate"],
      "encryption_private_key" => $pankkiyhteys["encryption_private_key"],
      "customer_id"            => $pankkiyhteys["customer_id"],
    ),
    "url" => "https://sepa.devlab.fi/api/{$pankkiyhteys['bank']}/get_user_info",
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

function sepa_target_id_for_file_type($params) {

  $tunnus       = parameter($params["pankkiyhteys_tunnus"]);
  $salasana     = parameter($params["pankkiyhteys_salasana"]);
  $file_type    = parameter($params["file_type"]);
  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($tunnus, $salasana);

  if (!$pankkiyhteys or empty($file_type)) {
    return false;
  }

  // Tehd‰‰n pankille userinfo kysely
  $user_info = sepa_get_user_info($params);

  if (!$user_info) {
    return false;
  }

  // Haetaan filetypen target_id
  $value = search_array_key_for_value_recursive($user_info, "fileType", $file_type);
  $target_id = parameter($value[0]["targetId"]);

  // Jos filetype‰ ei lˆydy listasta, ei voida hakea
  if ($target_id == "") {
    echo "Pankki '{$pankkiyhteys['bank']}' ei tule tiedostotyyppi‰ '{$file_type}'";
    return false;
  }

  return $target_id;
}

function sepa_file_type_check($bank, $file_type) {
  // Varmistetaan, ett‰ ei kutsuta v‰‰r‰ll‰ filetypell‰

  // Nordeamuunnokset
  if ($bank == "nordea") {
    // Konekielinen tiliote
    if ($file_type == "KTON") {
      $file_type = "TITO";
    }
    // Konekielinen viiteaineisto
    if ($file_type == "VIPN") {
      $file_type = "KTL";
    }
    // XML maksuaineisto
    if ($file_type == "C2BL") {
      $file_type = "NDCORPAYS";
    }
  }

  // Danskemuunnokset
  if ($bank == "danske") {
    // Konekielinen tiliote
    if ($file_type == "TITO") {
      $file_type = "KTON";
    }
    // Konekielinen viiteaineisto
    if ($file_type == "KTL") {
      $file_type = "VIPN";
    }
    // XML maksuaineisto
    if ($file_type == "NDCORPAYS") {
      $file_type = "C2BL";
    }
  }

  return $file_type;
}
// Sertifikaatti / salausfunktiot


/**
 * Hakee pankkiyhteysrivin tietokannasta ja purkaa kenttien salauksen.
 * Palauttaa pankkiyhteys arrayn
 *
 * @param string  $pankkiyhteys_tunnus
 * @param string  $salasana
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
            AND tunnus  = {$pankkiyhteys_tunnus}";
  $result = pupe_query($query);

  // Jos pankkiyhteytt‰ ei lˆydy
  if (mysql_num_rows($result) != 1) {
    return false;
  }

  $pankki = mysql_fetch_assoc($result);

  // Tarkistetaan salasana
  $_pk = pura_salaus($pankki["signing_private_key"], $salasana);

  // Jos salauksen purkaminen ei onnistu, lopetetaan t‰h‰
  if (!openssl_pkey_get_private($_pk)) {
    return false;
  }

  // List‰‰n resulttiin pankin lyhyt nimi
  $pankki["bank"] = $tuetut_pankit[$pankki["pankki"]]["lyhyt_nimi"];

  // N‰m‰ kent‰t on salattuna kannassa
  $salatut = array(
    "signing_private_key",
    "signing_certificate",
    "encryption_certificate",
    "encryption_private_key",
    "bank_encryption_certificate",
    "bank_root_certificate",
    "ca_certificate",
  );

  // Puretaan salaus kaikista
  foreach ($salatut as $key) {
    // Puretaan salaus
    $_salaamaton = pura_salaus($pankki[$key], $salasana);

    // Base64 enkoodataan ja setataan takasin arrayseen
    $pankki[$key] = base64_encode(trim($_salaamaton));
  }

  return $pankki;
}


/**
 * Laitaa kaikki tietyntyyppiset tiedostot pankista ja
 * palauttaa arrayn tiedostojen viitteista ja
 *
 * @param array   $params
 *
 * @return array|bool
 */
function sepa_lataa_kaikki_uudet_tiedostot($params) {
  global $kukarow, $yhtiorow;

  $tunnus    = parameter($params["pankkiyhteys_tunnus"]);
  $salasana  = parameter($params["pankkiyhteys_salasana"]);
  $file_type = parameter($params["file_type"]);

  if (empty($file_type)) {
    return false;
  }

  // Lis‰t‰‰n paramsiin status NEW, ett‰ haetaan vain uudet filet
  $params = array(
    "pankkiyhteys_tunnus" => $tunnus,
    "pankkiyhteys_salasana" => $salasana,
    "file_type" => $file_type,
    "status" => 'NEW'
  );

  // Haetaan filelista pankista
  $filelist = sepa_download_file_list($params);

  if (!$filelist) {
    return false;
  }

  $viitteet = array();

  // Lis‰t‰‰n paramssiin haettavat referencet
  foreach ($filelist['files'] as $tiedosto) {
    $viitteet[] = $tiedosto["fileReference"];
  }

  $params = array(
    "pankkiyhteys_tunnus" => $tunnus,
    "pankkiyhteys_salasana" => $salasana,
    "file_type" => $file_type,
    "target_id" => $filelist['target_id'],
    "viitteet" => $viitteet
  );

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
 * @param string  $salattava_data
 * @param string  $salasana
 *
 * @return string
 */
function salaa($salattava_data, $salasana) {

  if (empty($salattava_data)) {
    return null;
  }

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
 * @param string  $salattu_data
 * @param string  $salasana
 *
 * @return string
 */
function pura_salaus($salattu_data, $salasana) {

  if (empty($salattu_data)) {
    return null;
  }

  $avain = hash("SHA256", $salasana, true);

  $salattu_data_binaari = base64_decode($salattu_data);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);

  // T‰ss‰ on "//NO_MB_OVERLOAD"-kommentti
  // jotta UTF8-konversio ei osu t‰h‰n riviin
  $iv = substr($salattu_data_binaari, 0, $iv_size);  //NO_MB_OVERLOAD

  // T‰ss‰ on "//NO_MB_OVERLOAD"-kommentti
  // jotta UTF8-konversio ei osu t‰h‰n riviin
  $salattu_data_binaari = substr($salattu_data_binaari, $iv_size); //NO_MB_OVERLOAD

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
    "DABAFIHH" => array(
      "nimi"       => "Danske Bank",
      "lyhyt_nimi" => "danske"
    ),
  );
}

function parameter($param) {
  return empty($param) ? '' : $param;
}
