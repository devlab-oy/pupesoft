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
$customer_id = empty($customer_id) ? '' : $customer_id;
$pin = empty($pin) ? '' : $pin;
$bank = "";

// Debug moodissa, voidaan upata suoraan key/cert käyttöliittymästä
$debug = empty($debug) ? 0 : 1;

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
if ($tee == "luo") {
  $virheet_count = 0;

  // Otetaan pankin nimi muuttujaan
  $bank = $tuetut_pankit[$pankki]["lyhyt_nimi"];

  if (empty($salasana)) {
    virhe("Salasana täytyy antaa");
    $virheet_count++;
  }
  elseif ($salasana != $salasanan_vahvistus) {
    virhe("Salasanan vahvistus ei vastaa salasanaa");
    $virheet_count++;
  }

  if (empty($customer_id)) {
    virhe("Asiakastunnus täytyy antaa");
    $virheet_count++;
  }

  if (empty($pin) and $debug != 1) {
    virhe("PIN-koodi täytyy antaa");
    $virheet_count++;
  }

  if ($virheet_count > 0) {
    echo "<br>";
    $tee = "";
  }
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
if ($tee == "luo" and $pin == '' and $debug == 1) {
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

if ($tee == "luo" and $bank == "nordea") {
  $params = array(
    "bank" => $bank,
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
$mahdolliset_pankkiyhteydet = mahdolliset_pankkiyhteydet();

// Jos voidaan tehdä uusia pankkiyhteyksiä
if (!empty($mahdolliset_pankkiyhteydet)) {
  echo "<font class='message'>" . t("Uusi pankkiyhteys") . "</font>";
  echo "<hr>";

  echo "<form action='pankkiyhteysadmin.php' method='post' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='tee' value='luo'/>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<th><label for='pankki'>";
  echo t("Pankki, jolle pankkiyhteys luodaan");
  echo "</label></th>";
  echo "<td>";
  echo "<select name='pankki' id='pankki'>";

  foreach ($mahdolliset_pankkiyhteydet as $bic => $nimi) {
    $selected = $pankki == $bic ? " selected" : "";
    echo "<option value='{$bic}'{$selected}>{$nimi}</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='customer_id'>";
  echo t("Asiakastunnus");
  echo "</label></th>";
  echo "<td>";
  echo "<input type='text' name='customer_id' id='customer_id' value='{$customer_id}'/>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='pin'>";
  echo t("Pankilta saatu PIN-koodi");
  echo "</label></th>";
  echo "<td><input type='text' name='pin' id='pin' value='{$pin}'/></td>";
  echo "</tr>";

  if ($debug == 1) {
    echo "<tr>";
    echo "<td class='back'></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th><label for='private_key'>";
    echo t("Yksityinen avain");
    echo "</label></th>";
    echo "<td><input type='file' name='private_key' id='private_key'/></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th><label for='certificate'>";
    echo t("Sertifikaatti");
    echo "</label></th>";
    echo "<td><input type='file' name='certificate' id='certificate'/></td>";
    echo "</tr>";
  }

  echo "<tr>";
  echo "<td class='back'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='salasana'>";
  echo t("Salasana, jolla pankkiyhteystunnukset suojataan");
  echo "</label></th>";
  echo "<td><input type='password' name='salasana' id='salasana'/></td>";
  echo "<td class='back'>";
  echo t("Huom! Salasanaa ei voi mitenkään palauttaa, jos se unohtuu.");
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='salasanan_vahvistus'>";
  echo t("Salasanan vahvistus");
  echo "</label></th>";
  echo "<td><input type='password' name='salasanan_vahvistus' id='salasanan_vahvistus'/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td class='back'><input type='submit' value='" . t("Luo pankkiyhteys") . "'/></td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table>";
  echo "</form>";
}

$pankkiyhteydet = hae_pankkiyhteydet();

// Jos meillä on jo perustettuja pankkiyhteyksiä
if (!empty($pankkiyhteydet)) {
  echo "<br/>";
  echo "<font class='message'>" . t("Pankkiyhteydet") . "</font>";
  echo "<hr>";

  echo "<table>";
  echo "<thead>";

  echo "<tr>";
  echo "<th>" . t("Pankki") . "</th>";
  echo "<th>" . t("Asiakastunnus") . "</th>";
  echo "<th>" . t("Aineistoryhmän tunnus") . "</th>";
  echo "<th></th>";
  echo "</tr>";

  echo "</thead>";

  echo "<tbody>";

  $_confirm = t("Haluatko varmasti poistaa pankkiyhteyden?");

  foreach ($pankkiyhteydet as $pankkiyhteys) {
    echo "<tr class='aktiivi'>";
    echo "<td>{$pankkiyhteys["pankin_nimi"]}</td>";
    echo "<td>{$pankkiyhteys["customer_id"]}</td>";
    echo "<td>{$pankkiyhteys["target_id"]}</td>";
    echo "<td>";

    echo "<form class='multisubmit' method='post' action='pankkiyhteysadmin.php'
                onsubmit='return confirm(\"{$_confirm}\");'>";
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
