<?php

require("inc/parametrit.inc");
require("inc/pankkiyhteys_functions.inc");

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

  $tuetut_pankit = tuetut_pankit();

  $params = array(
    "pin"               => $pin,
    "customer_id"       => $customer_id,
    "tunnukset"         => $generoidut_tunnukset,
    "pankki_lyhyt_nimi" => $tuetut_pankit[$pankki]["lyhyt_nimi"]
  );

  $sertifikaatti = sepa_get_certificate($params);
  $private_key = $generoidut_tunnukset["private_key"];

  if (!$sertifikaatti) {
    virhe("Sertifikaatin hakeminen epäonnistui, tarkista PIN-koodi ja asiakastunnus");
    $tee = "";
  }

  $salatut_tunnukset = array(
    "private_key"   => salaa($private_key, $salasana),
    "sertifikaatti" => salaa($sertifikaatti, $salasana)
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
      "sertifikaatti" => salaa($certificate, $salasana)
    );
  }
}

// Jos käyttäjä ei ole antanut target id:tä, haetaan se pankista
if ($tee == "luo" and $target_id == '') {
  $params = array(
    "certificate" => $certificate,
    "private_key" => $private_key,
    "customer_id" => $customer_id
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
  }
  else {
    virhe("Tunnusten tallennus epäonnistui");
  }

  $tee = "";
}

// Käyttöliittymä
uusi_pankkiyhteys_formi();
pankkiyhteydet_table();
