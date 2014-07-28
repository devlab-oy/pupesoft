<?php

require("inc/parametrit.inc");
require("inc/pankkiyhteys_functions.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

if (SEPA_PANKKIYHTEYS === false) {
  echo "<font class='error'>";
  echo t("SEPA-palvelua ei ole aktivoitu.");

  if (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != 'on') {
    echo "<br>";
    echo t("Voit käyttää pankkiyhteyttä vain salatulla yhteydellä!");
  }

  echo "</font>";

  exit;
}

$tee = empty($tee) ? '' : $tee;
$pin = empty($pin) ? '' : $pin;
$target_id = empty($target_id) ? '' : $target_id;

$tuetut_pankit = tuetut_pankit();

// Poistetaan pankkiyhteys
if ($tee == "poista") {
  if (poista_pankkiyhteys($pankkiyhteys)) {
    ok("Pankkiyhteys poistettu");
  }
  else {
    virhe("Pankkiyhteyttä ei poistettu");
  }

  $tee = "";
}

// Uuden pankkiyhteyden oikeellisuustarkistus
if ($tee == "luo" and !pankkiyhteystiedot_kunnossa()) {
  $tee = "";
}

// Haetaan sertifikaatti jos PIN on annettu
if ($tee == "luo" and $pin != '') {
  $generoidut_tunnukset = generoi_private_key_ja_csr();

  $params = array(
    "bank" => $tuetut_pankit[$pankki]["lyhyt_nimi"],
    "customer_id" => $customer_id,
    "pin" => $pin,
    "csr" => $generoidut_tunnukset["csr"]
  );

  $certificate = sepa_get_certificate($params);
  $private_key = $generoidut_tunnukset["private_key"];

  if (!$certificate) {
    virhe("Sertifikaatin hakeminen epäonnistui, tarkista PIN-koodi ja asiakastunnus");
    $tee = "";
  }

  $salatut_tunnukset = array(
    "private_key"   => salaa($private_key, $salasana),
    "certificate" => salaa($certificate, $salasana)
  );
}

// Avainpari annettu käyttöliittymästä
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
      "certificate" => salaa($certificate, $salasana)
    );
  }
}

// Jos käyttäjä ei ole antanut target id:tä, haetaan se pankista
if ($tee == "luo" and $target_id == '') {

  $params = array(
    "bank" => $tuetut_pankit[$pankki]["lyhyt_nimi"],
    "certificate" => $certificate,
    "private_key" => $private_key,
    "customer_id" => $customer_id,
  );

  $target_id = sepa_get_target_id($params);

  if (!$target_id) {
    virhe("Tiedon hakeminen pankista epäonnistui, yritä myöhemmin uudestaan");
    $tee = "";
  }
}

// Tallennetaan pankkiyhteys
if ($tee == "luo") {
  $params = array(
    "pankki"            => $pankki,
    "salatut_tunnukset" => $salatut_tunnukset,
    "customer_id"       => $customer_id,
    "target_id"         => $target_id
  );

  if (tallenna_pankkiyhteys($params)) {
    ok("Tunnukset tallennettu");
    $tee = "tyhjenna_formi";
  }
  else {
    virhe("Tunnusten tallennus epäonnistui");
  }
}

if ($tee == "tyhjenna_formi") {
  $_POST["pankki"] = "";
  $_POST["customer_id"] = "";
  $_POST["target_id"] = "";
  $_POST["pin"] = "";
}

// Käyttöliittymä
uusi_pankkiyhteys_formi();
pankkiyhteydet_table();
