<?php

// K‰yttˆliittym‰n funktiot

function vastaus_kunnossa($vastaus) {
  switch ($vastaus[0]) {
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
  }

  return false;
}

function formi() {
  global $kukarow, $yhtiorow;

  $kaytossa_olevat_pankkiyhteydet = hae_kaytossa_olevat_pankkiyhteydet();

  if ($kaytossa_olevat_pankkiyhteydet) {

    echo "<form method='post' action='pankkiyhteys.php' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='tee' value='laheta'/>";
    echo "<table>";
    echo "<tbody>";

    echo "<tr>";
    echo "<td>Valitse tili</td>";
    echo "<td>";
    echo "<select name='tili'>";

    foreach ($kaytossa_olevat_pankkiyhteydet as $pankkiyhteys) {
      echo "<option value='{$pankkiyhteys["tunnus"]}'>";
      echo pankin_nimi($pankkiyhteys["pankki"]);
      echo "</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><label>";
    echo t("Mit‰ haluat tehd‰?");
    echo "</label></td>";
    echo "<td>";
    echo "<label for='hae_tiliotteet'>" . t("Hae tiliotteet") . "</label>";
    echo "<input type='checkbox' name='hae_tiliotteet' id='hae_tiliotteet'/>";
    echo "<label for='hae_viitteet'>" . t("Hae viitteet") . "</label>";
    echo "<input type='checkbox' name='hae_viitteet' id='hae_viitteet'/>";
    echo "<label for='laheta_maksuaineisto'>" . t("L‰het‰ maksuaineisto") . "</label>";
    echo "<input type='checkbox' name='laheta_maksuaineisto' id='laheta_maksuaineisto'/>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><label for='maksuaineisto'>" . t("Maksuaineisto") . "</label></td>";
    echo "<td><input type='file' name='maksuaineisto' id='maksuaineisto'/></td>";
    echo "<td class='back'>" . t("T‰yt‰ vain, jos aiot l‰hett‰‰ maksuaineiston") . "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><label for='salasana'>" . t("Salasana") . "</label></td>";
    echo "<td><input type='password' name='salasana' id='salasana'/></td>";
    echo "</tr>";

    echo "</tbody>";
    echo "</table>";
    echo "<input type='submit' value='" . t('L‰het‰') . "'>";

    echo "</form>";
  }
  else {
    viesti("Yht‰‰n pankkiyhteytt‰ ei ole viel‰ luotu.");
  }
}

function formi_kunnossa() {
  global $kukarow, $yhtiorow;

  $komennot_count = 0;
  $virheet_count = 0;

  if ($_POST["hae_tiliotteet"] == "on") {
    $komennot_count++;
  }
  elseif ($_POST["hae_viitteet"] == "on") {
    $komennot_count++;
  }
  elseif ($_POST["laheta_maksuaineisto"] == "on") {
    $komennot_count++;
  }

  if ($komennot_count == 0) {
    virhe("Et valinnut yht‰‰n komentoa");
    $virheet_count++;
  }

  if (empty($_POST["salasana"])) {
    virhe("Salasana t‰ytyy antaa");
    $virheet_count++;
  }

  if (!salasana_kunnossa()) {
    virhe("Antamasi salasana on v‰‰r‰");
    $virheet_count++;
  }

  if ($virheet_count == 0) {
    return true;
  }

  return false;
}

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

function pankkiyhteydet_table() {
  global $kukarow, $yhtiorow;

  $pankkiyhteydet = hae_pankkiyhteydet();

  if (empty($pankkiyhteydet)) {
    return;
  }

  echo "<br/>";
  echo "<font class='message'>" . t("Pankkiyhteydet") . "</font>";
  echo "<hr>";

  echo "<table>";
  echo "<thead>";

  echo "<tr>";
  echo "<th>" . t("Pankki") . "</th>";
  echo "<th>" . t("Asiakastunnus") . "</th>";
  echo "<th>" . t("Aineistoryhm‰n tunnus") . "</th>";
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

function uusi_pankkiyhteys_formi() {
  global $kukarow, $yhtiorow;

  $mahdolliset_pankkiyhteydet = mahdolliset_pankkiyhteydet();

  if (empty($mahdolliset_pankkiyhteydet)) {
    return;
  }

  echo "<font class='message'>" . t("Uusi pankkiyhteys") . "</font>";
  echo "<hr>";

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

function pankkiyhteystiedot_kunnossa() {
  global $kukarow, $yhtiorow;

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
  global $kukarow, $yhtiorow;

  echo "<font class='error'>";
  echo t($viesti);
  echo "</font>";
  echo "<br/>";
}

function ok($viesti) {
  global $kukarow, $yhtiorow;

  echo "<font class='ok'>";
  echo t($viesti);
  echo "</font>";
  echo "<br/>";
}

function viesti($viesti) {
  global $kukarow, $yhtiorow;

  echo "<font class='message'>";
  echo t($viesti);
  echo "</font>";
  echo "<br/>";
}

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


// Pankkiyhteys tietokantafunktiot

function hae_avain_sertifikaatti_ja_customer_id($tili) {
  global $kukarow, $yhtiorow;

  $query = "SELECT private_key, certificate, customer_id, target_id
            FROM pankkiyhteys
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tunnus = {$tili}";
  $result = pupe_query($query);

  $rivi = mysql_fetch_assoc($result);

  return $rivi;
}

function hae_kaytossa_olevat_pankkiyhteydet() {
  global $kukarow, $yhtiorow;

  $query = "SELECT tunnus, pankki
            FROM pankkiyhteys
            WHERE yhtio = '{$kukarow["yhtio"]}'";
  $result = pupe_query($query);

  $kaytossa_olevat_tilit = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($kaytossa_olevat_tilit, $rivi);
  }

  return $kaytossa_olevat_tilit;
}

function tallenna_tunnukset($params) {
  global $kukarow, $yhtiorow;

  $pankki = isset($params["pankki"]) ? $params["pankki"] : "";
  $salatut_tunnukset = isset($params["salatut_tunnukset"]) ? $params["salatut_tunnukset"] : "";
  $customer_id = isset($params["customer_id"]) ? $params["customer_id"] : "";
  $target_id = isset($params["target_id"]) ? $params["target_id"] : "";

  if (empty($pankki) or empty($salatut_tunnukset) or empty($customer_id) or empty($target_id)) {
    return false;
  }

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

function mahdolliset_pankkiyhteydet() {
  global $kukarow, $yhtiorow;

  // Tuetut pankit
  $pankit = array(
    "NDEAFIHH" => "Nordea",
    "DABAFIHX" => "Danske Bank",
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

function hae_pankkiyhteydet() {
  global $kukarow, $yhtiorow;

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

function poista_pankkiyhteys($pankki) {
  global $kukarow, $yhtiorow;

  $query = "DELETE
            FROM  pankkiyhteys
            WHERE pankki = '{$pankki}'
            AND   yhtio  = '{$kukarow["yhtio"]}'";
  return pupe_query($query);
}


// SEPA API fuktiot

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

function download_file_list($tiedostotyyppi, $tunnukset) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "cert"        => base64_encode($tunnukset["sertifikaatti"]),
      "private_key" => base64_encode($tunnukset["avain"]),
      "customer_id" => $tunnukset["customer_id"],
      "file_type"   => $tiedostotyyppi,
      "target_id"   => $tunnukset["target_id"]
    ),
    "url"     => "https://sepa.devlab.fi/api/nordea/download_file_list",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  $vastaus = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($vastaus)) {
    return false;
  }

  $tiedostot = $vastaus[1]["files"];
  $viitteet = array();

  foreach ($tiedostot as $tiedosto) {
    array_push($viitteet, $tiedosto["fileReference"]);
  }

  return $viitteet;
}

function download_files($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $viitteet = isset($params["viitteet"]) ? $params["viitteet"] : "";
  $tiedostotyyppi = isset($params["tiedostotyyppi"]) ? $params["tiedostotyyppi"] : "";
  $tunnukset = isset($params["tunnukset"]) ? $params["tunnukset"] : "";

  if (empty($viitteet) or empty($tiedostotyyppi) or empty($tunnukset)) {
    return false;
  }

  $tiedostot = array();

  foreach ($viitteet as $viite) {
    $parameters = array(
      "method"  => "POST",
      "data"    => array(
        "cert"           => base64_encode($tunnukset["sertifikaatti"]),
        "private_key"    => base64_encode($tunnukset["avain"]),
        "customer_id"    => $tunnukset["customer_id"],
        "file_type"      => $tiedostotyyppi,
        "target_id"      => $tunnukset["target_id"],
        "file_reference" => $viite
      ),
      "url"     => "https://sepa.devlab.fi/api/nordea/download_file",
      "headers" => array(
        "Content-Type: application/json",
        "Authorization: Token token={$sepa_pankkiyhteys_token}"
      )
    );

    $vastaus = pupesoft_rest($parameters);

    if ($vastaus[0] == 200) {
      $tiedosto = array(
        "viite"  => $viite,
        "status" => "OK"
      );
    }
    else {
      $tiedosto = array(
        "viite"  => $viite,
        "status" => "Virhe"
      );
    }

    array_push($tiedostot, $tiedosto);

    if (!is_dir("/tmp/{$tiedostotyyppi}")) {
      mkdir("/tmp/{$tiedostotyyppi}");
    }

    file_put_contents("/tmp/{$tiedostotyyppi}/{$viite}", base64_decode($vastaus[1]["data"]));
  }

  return $tiedostot;
}

function hae_tunnukset_ja_pura_salaus($tili, $salasana) {
  global $kukarow, $yhtiorow;

  $haetut_tunnukset = hae_avain_sertifikaatti_ja_customer_id($tili);
  $avain = pura_salaus($haetut_tunnukset["private_key"], $salasana);
  $sertifikaatti = pura_salaus($haetut_tunnukset["certificate"], $salasana);

  $customer_id = $haetut_tunnukset["customer_id"];
  $target_id = $haetut_tunnukset["target_id"];

  return array(
    "avain"         => $avain,
    "sertifikaatti" => $sertifikaatti,
    "customer_id"   => $customer_id,
    "target_id"     => $target_id
  );
}

function laheta_maksuaineisto($tunnukset, $maksuaineisto) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "cert"        => base64_encode($tunnukset["sertifikaatti"]),
      "private_key" => base64_encode($tunnukset["avain"]),
      "customer_id" => $tunnukset["customer_id"],
      "file_type"   => "NDCORPAYS",
      "target_id"   => $tunnukset["target_id"],
      "content"     => $maksuaineisto
    ),
    "url"     => "https://sepa.devlab.fi/api/nordea/upload_file",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  $vastaus = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($vastaus)) {
    return false;
  }

  return $vastaus;
}

function lataa_kaikki($tiedostotyyppi) {
  global $kukarow, $yhtiorow;

  $tunnukset = hae_tunnukset_ja_pura_salaus($_POST["tili"], $_POST["salasana"]);

  if (!$tunnukset) {
    return false;
  }

  $viitteet = download_file_list($tiedostotyyppi, $tunnukset);

  $params = array(
    "viitteet"       => $viitteet,
    "tiedostotyyppi" => $tiedostotyyppi,
    "tunnukset"      => $tunnukset
  );

  $tiedostot = download_files($params);

  if ($tiedostot) {
    return $tiedostot;
  }

  return false;
}

function salasana_kunnossa() {
  $haetut_tunnukset = hae_avain_sertifikaatti_ja_customer_id($_POST["tili"]);

  $avain = pura_salaus($haetut_tunnukset["private_key"], $_POST["salasana"]);

  if (!openssl_pkey_get_private($avain)) {
    return false;
  }

  return true;
}

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

function hae_sertifikaatti_sepasta($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $pin = isset($params["pin"]) ? (string) $params["pin"] : "";
  $customer_id = isset($params["customer_id"]) ? (string) $params["customer_id"] : "";
  $tunnukset = isset($params["tunnukset"]) ? (array) $params["tunnukset"] : "";

  if (empty($pin) or empty($customer_id) or empty($tunnukset)) {
    return false;
  }

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

function salaa($data, $salasana) {
  $avain = hash("SHA256", $salasana, true);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
  $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

  $salattu_data = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $avain, $data, MCRYPT_MODE_CBC, $iv);
  $salattu_data = $iv . $salattu_data;

  return base64_encode($salattu_data);
}

function hae_target_id($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $certificate = isset($params["certificate"]) ? $params["certificate"] : "";
  $private_key = isset($params["private_key"]) ? $params["private_key"] : "";
  $customer_id = isset($params["customer_id"]) ? $params["customer_id"] : "";

  if (empty($certificate) or empty($private_key) or empty($customer_id)) {
    return false;
  }

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
