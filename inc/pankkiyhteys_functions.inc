<?php

// K‰yttˆliittym‰n funktiot

/**
 * Tarkistaa sepa-palvelimelta tulleen vastauksen koodin ja palauttaa true tai false sen
 * perusteella. Echottaa myos mahdollisen virheilmoituksen
 *
 * @param $vastaus
 *
 * @return bool
 */
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

/**
 * Piirtaa formin, jolla voi hakea tiliotteet, viiteaineistot tai lahettaa maksuaineiston
 */
function formi() {
  global $kukarow, $yhtiorow;

  $kaytossa_olevat_pankkiyhteydet = hae_pankkiyhteydet();

  if ($kaytossa_olevat_pankkiyhteydet) {

    echo "<form method='post' action='pankkiyhteys.php' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='tee' value='laheta'/>";
    echo "<table>";
    echo "<tbody>";

    echo "<tr>";
    echo "<td>Valitse tili</td>";
    echo "<td>";
    echo "<select name='pankkiyhteys_tunnus'>";

    foreach ($kaytossa_olevat_pankkiyhteydet as $pankkiyhteys) {
      $selected = $_POST["pankkiyhteys_tunnus"] == $pankkiyhteys["tunnus"] ? "selected" : "";

      echo "<option value='{$pankkiyhteys["tunnus"]}' {$selected}>";
      echo "{$pankkiyhteys["pankin_nimi"]}</option>";
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
    $checked = $_POST["hae_tiliotteet"] == "on" ? "checked" : "";
    echo "<input type='checkbox' name='hae_tiliotteet' id='hae_tiliotteet' {$checked}/>";

    echo "<label for='hae_viitteet'>" . t("Hae viitteet") . "</label>";
    $checked = $_POST["hae_viitteet"] == "on" ? "checked" : "";
    echo "<input type='checkbox' name='hae_viitteet' id='hae_viitteet' {$checked}/>";

    echo "<label for='laheta_maksuaineisto'>" . t("L‰het‰ maksuaineisto") . "</label>";
    $checked = $_POST["laheta_maksuaineisto"] == "on" ? "checked" : "";
    echo "<input type='checkbox' name='laheta_maksuaineisto' id='laheta_maksuaineisto' {$checked}/>";

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

/**
 * Tarkistaa pankkiyhteysformin ja palauttaa true tai false sen perusteella onko formi kunnossa vai
 * ei. Echottaa myos mahdolliset virheet
 *
 * @return bool
 */
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

  if (!pankkiyhteys_salasana_kunnossa($_POST["pankkiyhteys_tunnus"], $_POST["salasana"])) {
    virhe("Antamasi salasana on v‰‰r‰");
    $virheet_count++;
  }

  if ($virheet_count == 0) {
    return true;
  }

  return false;
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
 * Piirtaa luoduille pankkiyhteyksille taulun ja nayttaa siina pankin, asiakastunnuksen,
 * aineistoryhman tunnuksen ja napin, josta pankkiyhteyden voi poistaa
 */
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
    echo "<td>{$pankkiyhteys["pankin_nimi"]}</td>";
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
 * Piirtaa formin, jossa voi luoda uuden pankkiyhteyden, joko valmiilla avainparilla tai
 * PIN-koodilla
 */
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

/**
 * Tarkistaa uuden pankkiyhteysformin ja palauttaa true tai false sen perusteella onko formissa
 * kaikki kunnossa. Echottaa myos mahdolliset virheet
 *
 * @return bool
 */
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
 * @param array $params
 *
 * @return bool|resource
 */
function tallenna_pankkiyhteys($params) {
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
            certificate = '{$salatut_tunnukset['certificate']}',
            customer_id = '{$customer_id}',
            target_id = '{$target_id}'";
  $result = pupe_query($query);

  return $result;
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

  $tiedostotyyppi = isset($params["tiedostotyyppi"]) ? $params["tiedostotyyppi"] : "";
  $pankkiyhteys = isset($params["pankkiyhteys"]) ? $params["pankkiyhteys"] : "";

  if (empty($tiedostotyyppi) or empty($pankkiyhteys)) {
    return false;
  }

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "cert"        => base64_encode($pankkiyhteys["certificate"]),
      "private_key" => base64_encode($pankkiyhteys["private_key"]),
      "customer_id" => $pankkiyhteys["customer_id"],
      "file_type"   => $tiedostotyyppi,
      "target_id"   => $pankkiyhteys["target_id"]
    ),
    "url"     => "https://sepa.devlab.fi/api/{$pankkiyhteys["pankki_lyhyt_nimi"]}/download_file_list",
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

  $viitteet = isset($params["viitteet"]) ? $params["viitteet"] : "";
  $tiedostotyyppi = isset($params["tiedostotyyppi"]) ? $params["tiedostotyyppi"] : "";
  $pankkiyhteys = isset($params["pankkiyhteys"]) ? $params["pankkiyhteys"] : "";

  if (empty($viitteet) or empty($tiedostotyyppi) or empty($pankkiyhteys)) {
    return false;
  }

  $tiedostot = array();

  foreach ($viitteet as $viite) {
    $parameters = array(
      "method"  => "POST",
      "data"    => array(
        "cert"           => base64_encode($pankkiyhteys["certificate"]),
        "private_key"    => base64_encode($pankkiyhteys["private_key"]),
        "customer_id"    => $pankkiyhteys["customer_id"],
        "file_type"      => $tiedostotyyppi,
        "target_id"      => $pankkiyhteys["target_id"],
        "file_reference" => $viite
      ),
      "url"     => "https://sepa.devlab.fi/api/{$pankkiyhteys["pankki_lyhyt_nimi"]}/download_file",
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

/**
 * Lahettaa maksuaineiston pankkiin. Talla hetkella ainut hyvaksyttava tyyppi on NDCORPAYS.
 *
 * @param array $params
 *
 * @return array|bool
 */
function sepa_upload_file($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $pankkiyhteys = isset($params["pankkiyhteys"]) ? $params["pankkiyhteys"] : "";
  $maksuaineisto = isset($params["maksuaineisto"]) ? $params["maksuaineisto"] : "";

  if (empty($pankkiyhteys) or empty($maksuaineisto)) {
    return false;
  }

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "cert"        => base64_encode($pankkiyhteys["certificate"]),
      "private_key" => base64_encode($pankkiyhteys["private_key"]),
      "customer_id" => $pankkiyhteys["customer_id"],
      "file_type"   => "NDCORPAYS",
      "target_id"   => $pankkiyhteys["target_id"],
      "content"     => $maksuaineisto
    ),
    "url"     => "https://sepa.devlab.fi/api/{$pankkiyhteys["pankki_lyhyt_nimi"]}/upload_file",
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

  $pin = isset($params["pin"]) ? (string) $params["pin"] : "";
  $customer_id = isset($params["customer_id"]) ? (string) $params["customer_id"] : "";
  $tunnukset = isset($params["tunnukset"]) ? $params["tunnukset"] : "";
  $pankki_lyhyt_nimi = isset($params["pankki_lyhyt_nimi"]) ? $params["pankki_lyhyt_nimi"] : "";

  if (empty($pin) or empty($customer_id) or empty($tunnukset) or empty($pankki_lyhyt_nimi)) {
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
    "url"     => "https://sepa.devlab.fi/api/{$pankki_lyhyt_nimi}/get_certificate",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token={$sepa_pankkiyhteys_token}"
    )
  );

  $vastaus = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($vastaus)) {
    return false;
  }

  $certificate = base64_decode($vastaus[1]["content"]);

  return $certificate;
}

/**
 * Hakee aineistoryhman tunnuksen (target_id) pankista
 *
 * @param array $params
 *
 * @return string|bool
 */
function sepa_get_target_id($params) {
  global $kukarow, $yhtiorow, $sepa_pankkiyhteys_token;

  $certificate = isset($params["certificate"]) ? $params["certificate"] : "";
  $private_key = isset($params["private_key"]) ? $params["private_key"] : "";
  $customer_id = isset($params["customer_id"]) ? $params["customer_id"] : "";
  $pankki_lyhyt_nimi = isset($params["pankki_lyhyt_nimi"]) ? $params["pankki_lyhyt_nimi"] : "";

  if (empty($certificate) or empty($private_key) or empty($customer_id) or empty($pankki_lyhyt_nimi)) {
    return false;
  }

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "cert"        => base64_encode($certificate),
      "private_key" => base64_encode($private_key),
      "customer_id" => $customer_id
    ),
    "url"     => "https://sepa.devlab.fi/api/{$pankki_lyhyt_nimi}/get_user_info",
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

  $query = "SELECT *
            FROM pankkiyhteys
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tunnus = {$pankkiyhteys_tunnus}";
  $result = pupe_query($query);

  $pankkiyhteys = mysql_fetch_assoc($result);
  $pankkiyhteys["pankki_lyhyt_nimi"] = $tuetut_pankit[$pankkiyhteys["pankki"]]["lyhyt_nimi"];
  $pankkiyhteys["private_key"] = pura_salaus($pankkiyhteys["private_key"], $salasana);
  $pankkiyhteys["certificate"] = pura_salaus($pankkiyhteys["certificate"], $salasana);

  return $pankkiyhteys;
}

/**
 * Laitaa kaikki tietyn tyyppiset tiedostot pankista ja palauttaa arrayn tiedostojen viitteista ja
 * statuksista
 *
 * @param array $params
 *
 * @return array|bool
 */
function lataa_kaikki($params) {
  global $kukarow, $yhtiorow;

  $tiedostotyyppi = isset($params["tiedostotyyppi"]) ? $params["tiedostotyyppi"] : "";
  $pankkiyhteys_tunnus = isset($params["pankkiyhteys_tunnus"]) ? $params["pankkiyhteys_tunnus"] : "";
  $salasana = isset($params["salasana"]) ? $params["salasana"] : "";

  if (empty($tiedostotyyppi) or empty($pankkiyhteys_tunnus) or empty($salasana)) {
    return false;
  }

  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($pankkiyhteys_tunnus, $salasana);

  if (!$pankkiyhteys) {
    return false;
  }

  $params = array(
    "pankkiyhteys"   => $pankkiyhteys,
    "tiedostotyyppi" => $tiedostotyyppi
  );

  $viitteet = sepa_download_file_list($params);

  $params["viitteet"] = $viitteet;

  $tiedostot = sepa_download_files($params);

  if ($tiedostot) {
    return $tiedostot;
  }

  return false;
}

/**
 * Tarkistaa pystyyko annetulla salasana decryptaamaan private_keyn pankkiyhteysrivista.
 * Tarkistus perustuu siihen, saako salatusta datasta selvaa decryptauksen jalkeen
 *
 * @param string $pankkiyhteys_tunnus
 * @param string $salasana
 *
 * @return bool
 */
function pankkiyhteys_salasana_kunnossa($pankkiyhteys_tunnus, $salasana) {
  $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($pankkiyhteys_tunnus, $salasana);

  if (!openssl_pkey_get_private($pankkiyhteys["private_key"])) {
    return false;
  }

  return true;
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
