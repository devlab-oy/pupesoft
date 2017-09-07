<?php

require "inc/parametrit.inc";
require "inc/pankkiyhteys_functions.inc";

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

// Varmistetaan, että sepa pankkiyhteys on kunnossa. Funkio kuolee, jos ei ole.
sepa_pankkiyhteys_kunnossa();

$tee = empty($tee) ? '' : $tee;
$company_name = empty($company_name) ? '' : $company_name;
$customer_id = empty($customer_id) ? '' : $customer_id;
$pin = empty($pin) ? '' : $pin;
$bank = "";
$virheet_count = 0;
$pankki = isset($pankki) ? $pankki : null;

// Debug moodissa, voidaan upata suoraan key/cert käyttöliittymästä
$debug = empty($debug) ? 0 : 1;

$tuetut_pankit = tuetut_pankit();

if ($tee == 'paivita_hae_saldo') {
  $hae_saldo = isset($hae_saldo) ? 1 : 0;

  $query = "UPDATE pankkiyhteys SET
            hae_saldo   = {$hae_saldo}
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = {$pankkiyhteys_tunnus}";
  pupe_query($query);

  $tee = "";
}

if ($tee == 'paivita_hae_factoring') {
  $hae_factoring = isset($hae_factoring) ? 1 : 0;

  $query = "UPDATE pankkiyhteys SET
            hae_factoring = {$hae_factoring}
            WHERE yhtio   = '{$kukarow['yhtio']}'
            AND tunnus    = {$pankkiyhteys_tunnus}";
  pupe_query($query);

  $tee = "";
}

if ($tee == 'paivita_hae_laskut') {
  $hae_laskut = isset($hae_laskut) ? 1 : 0;

  $query = "UPDATE pankkiyhteys SET
            hae_laskut    = {$hae_laskut}
            WHERE yhtio   = '{$kukarow['yhtio']}'
            AND tunnus    = {$pankkiyhteys_tunnus}";
  pupe_query($query);

  $tee = "";
}

// Poistetaan pankkiyhteys
if ($tee == "poista") {
  $query = "DELETE
            FROM pankkiyhteys
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tunnus  = {$pankkiyhteys_tunnus}";
  $result = pupe_query($query);

  if ($result) {
    ok("Pankkiyhteys poistettu!");
  }
  else {
    virhe("Pankkiyhteyden poisto epäonnistui!");
  }

  echo "<br>";

  $tee = "";
}

// Vaihdetaan salasana, oikeellisuustarkastukset
if ($tee == "vaihda_salasana") {

  if (empty($vanha_salasana)) {
    virhe("Vanha salasana täytyy antaa!");
    $virheet_count++;
  }
  else {
    // Haetaan pankkiyhteys tässä, tätä käytetään salasanan vaihdossa
    $vanha_pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($pankkiyhteys_tunnus, $vanha_salasana);

    if ($vanha_pankkiyhteys === false) {
      virhe("Antamasi vanha salasana on väärä!");
      $virheet_count++;
    }
  }

  if (empty($uusi_salasana1) or empty($uusi_salasana2)) {
    virhe("Uudet salasanat täytyy antaa!");
    $virheet_count++;
  }
  elseif ($uusi_salasana1 != $uusi_salasana2) {
    virhe("Antamasi uudet salasanat ei täsmää!");
    $virheet_count++;
  }

  if ($virheet_count > 0) {
    echo "<br>";
    $tee = "vaihda_salasana_form";
  }
}

// Vaihdetaan salasana
if ($tee == "vaihda_salasana") {
  $spk = salaa(base64_decode($vanha_pankkiyhteys['signing_private_key']), $uusi_salasana1);
  $epk = salaa(base64_decode($vanha_pankkiyhteys['encryption_private_key']), $uusi_salasana1);
  $oec = salaa(base64_decode($vanha_pankkiyhteys["encryption_certificate"]), $uusi_salasana1);
  $osc = salaa(base64_decode($vanha_pankkiyhteys["signing_certificate"]), $uusi_salasana1);
  $bec = salaa(base64_decode($vanha_pankkiyhteys["bank_encryption_certificate"]), $uusi_salasana1);
  $brc = salaa(base64_decode($vanha_pankkiyhteys["bank_root_certificate"]), $uusi_salasana1);
  $bca = salaa(base64_decode($vanha_pankkiyhteys["ca_certificate"]), $uusi_salasana1);

  $query = "UPDATE pankkiyhteys SET
            signing_certificate                  = '{$osc}',
            signing_private_key                  = '{$spk}',
            encryption_certificate               = '{$oec}',
            encryption_private_key               = '{$epk}',
            bank_encryption_certificate          = '{$bec}',
            bank_root_certificate                = '{$brc}',
            ca_certificate                       = '{$bca}',
            signing_certificate_valid_to         = '{$vanha_pankkiyhteys['signing_certificate_valid_to']}',
            encryption_certificate_valid_to      = '{$vanha_pankkiyhteys['encryption_certificate_valid_to']}',
            bank_encryption_certificate_valid_to = '{$vanha_pankkiyhteys['bank_encryption_certificate_valid_to']}',
            bank_root_certificate_valid_to       = '{$vanha_pankkiyhteys['bank_root_certificate_valid_to']}',
            ca_certificate_valid_to              = '{$vanha_pankkiyhteys['ca_certificate_valid_to']}'
            WHERE yhtio                          = '{$kukarow['yhtio']}'
            AND tunnus                           = {$pankkiyhteys_tunnus}";
  $result = pupe_query($query);

  if (mysql_affected_rows() == 1) {
    ok("Salasana vaihdettu!");
  }
  else {
    virhe("Salasanan vaihto epäonnistui!");
  }

  echo "<br>";

  $tee = "";
}

// Vaihda salasana formi
if ($tee == "vaihda_salasana_form") {

  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='vaihda_salasana'/>";
  echo "<input type='hidden' name='pankkiyhteys_tunnus' value='{$pankkiyhteys_tunnus}'/>";

  echo "<table>";

  echo "<tr>";
  echo "<th><label for='vanha_salasana'>";
  echo t("Vanha salasana");
  echo "</label></th>";
  echo "<td><input type='password' name='vanha_salasana'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='uusi_salasana1'>";
  echo t("Uusi salasana");
  echo "</label></th>";
  echo "<td><input type='password' name='uusi_salasana1'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='uusi_salasana2'>";
  echo t("Uusi salasana vahvistus");
  echo "</label></th>";
  echo "<td><input type='password' name='uusi_salasana2'></td>";
  echo "</tr>";

  echo "</table>";
  echo "<br>";


  echo "<input type='submit' value='" . t("Vaihda salasana") . "'/>";
  echo "</form>";
}

// Haetaan uusi pankin sertifikaatti, oikeellisuustarkastus
if ($tee == "pankin_sertifikaatti_hae") {
  if (empty($salasana)) {
    virhe("Salasana täytyy antaa!");
    $virheet_count++;
  }
  else {
    // Haetaan pankkiyhteys tässä, tätä käytetään salasanan vaihdossa
    $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($pankkiyhteys_tunnus, $salasana);

    if ($pankkiyhteys === false) {
      virhe("Antamasi salasana on väärä!");
      $virheet_count++;
    }
  }

  if ($virheet_count > 0) {
    echo "<br>";
    $tee = "pankin_sertifikaatti";
  }
}

// Haetaan ja tallennetaan uusi pankin sertifikaatti
if ($tee == "pankin_sertifikaatti_hae") {

  $params = array(
    "bank" => $pankkiyhteys['bank'],
    "customer_id" => $pankkiyhteys['customer_id'],
  );

  $tunnukset_pankista = sepa_get_bank_certificate($params);

  if (!$tunnukset_pankista) {
    virhe("Sertifikaatin hakeminen epäonnistui!");
  }
  else {
    // Salataan sertifikaatit
    $bec = salaa($tunnukset_pankista["bank_encryption_certificate"], $salasana);
    $brc = salaa($tunnukset_pankista["bank_root_certificate"], $salasana);

    // Haetaan sertifikaattien expire datet
    $_temp = parse_sertificate($tunnukset_pankista["bank_encryption_certificate"]);
    $bec_time = $_temp['valid_to'];

    $_temp = parse_sertificate($tunnukset_pankista["bank_root_certificate"]);
    $brc_time = $_temp['valid_to'];

    $query = "UPDATE pankkiyhteys SET
              bank_encryption_certificate          = '{$bec}',
              bank_root_certificate                = '{$brc}',
              bank_encryption_certificate_valid_to = '{$bec_time}',
              bank_root_certificate_valid_to       = '{$brc_time}'
              WHERE yhtio                          = '{$kukarow['yhtio']}'
              AND tunnus                           = {$pankkiyhteys_tunnus}";
    $result = pupe_query($query);

    ok("Pankin sertifikaatit päivitetty!");
    echo "<br>";
  }

  $tee = "";
}

// Haetaan uusi pankin sertifikaatti, kysytään salasana
if ($tee == "pankin_sertifikaatti") {
  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='pankin_sertifikaatti_hae'/>";
  echo "<input type='hidden' name='pankkiyhteys_tunnus' value='{$pankkiyhteys_tunnus}'/>";

  echo "<table>";

  echo "<tr>";
  echo "<th><label for='salasana'>";
  echo t("Salasana");
  echo "</label></th>";
  echo "<td><input type='password' name='salasana'></td>";
  echo "</tr>";

  echo "</table>";
  echo "<br>";

  echo "<input type='submit' value='" . t("Jatka") . "'/>";
  echo "</form>";
}

// Haetaan uusi sertifikaatti, oikeellisuustarkastus
if ($tee == "uusi_sertifikaatti_hae") {
  if (empty($salasana)) {
    virhe("Salasana täytyy antaa!");
    $virheet_count++;
  }
  else {
    $pankkiyhteys = hae_pankkiyhteys_ja_pura_salaus($pankkiyhteys_tunnus, $salasana);

    if ($pankkiyhteys === false) {
      virhe("Antamasi salasana on väärä!");
      $virheet_count++;
    }
  }

  if ($virheet_count > 0) {
    echo "<br>";
    $tee = "uusi_sertifikaatti";
  }
}

// Haetaan uusi sertifikaatti, kysytään salasana
if ($tee == "uusi_sertifikaatti") {
  echo "<h2>" . t('Sertifikaatin uusiminen') . "</h2>";

  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='uusi_sertifikaatti_hae'/>";
  echo "<input type='hidden' name='pankkiyhteys_tunnus' value='{$pankkiyhteys_tunnus}'/>";

  echo "<table>";

  echo "<tr>";
  echo "<th><label for='salasana'>";
  echo t("Salasana");
  echo "</label></th>";
  echo "<td><input type='password' name='salasana'></td>";
  echo "</tr>";

  echo "</table>";
  echo "<br>";

  echo "<input type='submit' value='" . t("Jatka") . "'/>";
  echo "</form>";
}

// Haetaan ja tallennetaan uusi sertifikaatti
if ($tee == "uusi_sertifikaatti_hae") {
  $params = array(
    "pankkiyhteys_tunnus" => $pankkiyhteys_tunnus,
    "salasana"            => $salasana,
    "bank"                => $pankkiyhteys['bank'],
  );

  $uudet_tunnukset = sepa_renew_certificate($params);

  if (!$uudet_tunnukset) {
    virhe("Sertifikaatin uusiminen epäonnistui!");
  }
  else {
    // Salataan sertifikaatit
    $osc = salaa($uudet_tunnukset["own_signing_certificate"],     $salasana);
    $oec = salaa($uudet_tunnukset["own_encryption_certificate"],  $salasana);
    $cac = salaa($uudet_tunnukset["ca_certificate"],              $salasana);
    $bec = salaa($uudet_tunnukset["bank_encryption_certificate"], $salasana);
    $brc = salaa($uudet_tunnukset["bank_root_certificate"],       $salasana);
    $spk = salaa($uudet_tunnukset["signing_private_key"],         $salasana);
    $epk = salaa($uudet_tunnukset["encryption_private_key"],      $salasana);

    // Haetaan sertifikaattien expire datet
    $_temp    = parse_sertificate($uudet_tunnukset["own_signing_certificate"]);
    $osc_time = $_temp['valid_to'];

    $_temp    = parse_sertificate($uudet_tunnukset["own_encryption_certificate"]);
    $oec_time = $_temp['valid_to'];

    $_temp    = parse_sertificate($uudet_tunnukset["ca_certificate"]);
    $cac_time = $_temp['valid_to'];

    $_temp    = parse_sertificate($uudet_tunnukset["bank_encryption_certificate"]);
    $bec_time = $_temp['valid_to'];

    $_temp    = parse_sertificate($uudet_tunnukset["bank_root_certificate"]);
    $brc_time = $_temp['valid_to'];

    $query = "UPDATE pankkiyhteys
              SET signing_certificate                  = '{$osc}',
                  encryption_certificate               = '{$oec}',
                  ca_certificate                       = '{$cac}',
                  bank_encryption_certificate          = '{$bec}',
                  bank_root_certificate                = '{$brc}',
                  signing_private_key                  = '{$spk}',
                  encryption_private_key               = '{$epk}',
                  signing_certificate_valid_to         = '{$osc_time}',
                  encryption_certificate_valid_to      = '{$oec_time}',
                  ca_certificate_valid_to              = '{$cac_time}',
                  bank_encryption_certificate_valid_to = '{$bec_time}',
                  bank_root_certificate_valid_to       = '{$brc_time}'
              WHERE yhtio  = '{$kukarow['yhtio']}'
                AND tunnus = {$pankkiyhteys_tunnus}";
    $result = pupe_query($query);

    ok("Sertifikaatti päivitetty!");
    echo "<br>";
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
  $csr_params = array(
    "company_name" => $company_name,
    "customer_id"  => $customer_id,
    "pankki"       => $bank,
  );

  // Generoidaan allekirjoitusta ja salausta varten private key ja certificate-signing-request
  $generoidut_tunnukset1 = generoi_private_key_ja_csr($csr_params);
  $generoidut_tunnukset2 = generoi_private_key_ja_csr($csr_params);

  $signing_private_key = $generoidut_tunnukset1["private_key"];
  $encryption_private_key = $generoidut_tunnukset2["private_key"];

  $params = array(
    "bank" => $tuetut_pankit[$pankki]["lyhyt_nimi"],
    "customer_id" => $customer_id,
    "pin" => $pin,
    "signing_csr" => $generoidut_tunnukset1["csr"],
    "encryption_csr" => $generoidut_tunnukset2["csr"],
  );

  $tunnukset_pankista = sepa_get_certificate($params);

  if (!$tunnukset_pankista) {
    virhe("Sertifikaatin hakeminen epäonnistui, tarkista PIN-koodi ja asiakastunnus");
    $tee = "";
  }
}

// Avainpari annettu käyttöliittymästä debugmode!
if ($tee == "luo" and $pin == '' and $debug == 1) {
  $signing_private_key = file_get_contents($_FILES["signing_private_key"]["tmp_name"]);
  $encryption_private_key = file_get_contents($_FILES["encryption_private_key"]["tmp_name"]);

  $tunnukset_pankista = array();
  $tunnukset_pankista["own_encryption_certificate"] = file_get_contents($_FILES["own_encryption_certificate"]["tmp_name"]);
  $tunnukset_pankista["own_signing_certificate"] = file_get_contents($_FILES["own_signing_certificate"]["tmp_name"]);
  $tunnukset_pankista["bank_encryption_certificate"] = file_get_contents($_FILES["bank_encryption_certificate"]["tmp_name"]);
  $tunnukset_pankista["bank_root_certificate"] = file_get_contents($_FILES["bank_root_certificate"]["tmp_name"]);
  $tunnukset_pankista["ca_certificate"] = file_get_contents($_FILES["ca_certificate"]["tmp_name"]);
}

// Tallennetaan pankkiyhteys
if ($tee == "luo") {
  $spk = salaa($signing_private_key, $salasana);
  $epk = salaa($encryption_private_key, $salasana);
  $oec = salaa($tunnukset_pankista["own_encryption_certificate"], $salasana);
  $osc = salaa($tunnukset_pankista["own_signing_certificate"], $salasana);
  $bec = salaa($tunnukset_pankista["bank_encryption_certificate"], $salasana);
  $brc = salaa($tunnukset_pankista["bank_root_certificate"], $salasana);
  $bca = salaa($tunnukset_pankista["ca_certificate"], $salasana);

  // Tallennetaan certificaattien valid_to päivä kantaan
  $_temp = parse_sertificate($tunnukset_pankista["own_encryption_certificate"]);
  $oec_time = $_temp['valid_to'];

  $_temp = parse_sertificate($tunnukset_pankista["own_signing_certificate"]);
  $osc_time = $_temp['valid_to'];

  $_temp = parse_sertificate($tunnukset_pankista["bank_encryption_certificate"]);
  $bec_time = $_temp['valid_to'];

  $_temp = parse_sertificate($tunnukset_pankista["bank_root_certificate"]);
  $brc_time = $_temp['valid_to'];

  $_temp = parse_sertificate($tunnukset_pankista["ca_certificate"]);
  $bca_time = $_temp['valid_to'];

  $query = "INSERT INTO pankkiyhteys SET
            yhtio                                = '{$kukarow['yhtio']}',
            pankki                               = '{$pankki}',
            signing_certificate                  = '{$osc}',
            signing_private_key                  = '{$spk}',
            encryption_certificate               = '{$oec}',
            encryption_private_key               = '{$epk}',
            bank_encryption_certificate          = '{$bec}',
            bank_root_certificate                = '{$brc}',
            ca_certificate                       = '{$bca}',
            signing_certificate_valid_to         = '{$osc_time}',
            encryption_certificate_valid_to      = '{$oec_time}',
            bank_encryption_certificate_valid_to = '{$bec_time}',
            bank_root_certificate_valid_to       = '{$brc_time}',
            ca_certificate_valid_to              = '{$bca_time}',
            customer_id                          = '{$customer_id}'";
  $result = pupe_query($query);

  $tee = "";
}

if ($tee == "") {
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
    echo "<th>";
    echo "<label for='company_name'>";
    echo t("Yrityksen nimi (Sama kuin sopimuksessa)");
    echo "</label>";
    echo "</th>";
    echo "<td>";
    echo "<input type='text' name='company_name' id='company_name' value='{$company_name}'>";
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
      echo "<th>own_signing_certificate</th>";
      echo "<td><input type='file' name='own_signing_certificate'/></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>own_signing_private_key</th>";
      echo "<td><input type='file' name='signing_private_key'/></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>own_encryption_certificate</th>";
      echo "<td><input type='file' name='own_encryption_certificate'/></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>own_encryption_private_key</th>";
      echo "<td><input type='file' name='encryption_private_key'/></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>bank_encryption_certificate</th>";
      echo "<td><input type='file' name='bank_encryption_certificate'/></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>bank_root_certificate</th>";
      echo "<td><input type='file' name='bank_root_certificate'/></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>ca_certificate</th>";
      echo "<td><input type='file' name='ca_certificate'/></td>";
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
    echo "</tr>";

    echo "<tr>";
    echo "<th><label for='salasanan_vahvistus'>";
    echo t("Salasanan vahvistus");
    echo "</label></th>";
    echo "<td><input type='password' name='salasanan_vahvistus' id='salasanan_vahvistus'/></td>";
    echo "</tr>";
    echo "</tbody>";
    echo "</table>";

    echo "<br>";
    echo "<input type='submit' value='" . t("Tallenna uusi pankkiyhteys") . "'/>";
    echo "</form>";
    echo "<br>";

  }

  $pankkiyhteydet = hae_pankkiyhteydet();

  // Jos meillä on jo perustettuja pankkiyhteyksiä
  if (!empty($pankkiyhteydet)) {
    echo "<br/>";
    echo "<font class='message'>" . t("Tallennetut pankkiyhteydet") . "</font>";
    echo "<hr>";

    echo "<table>";
    echo "<thead>";

    echo "<tr>";
    echo "<th>" . t("Pankki") . "</th>";
    echo "<th>" . t("Asiakastunnus") . "</th>";
    echo "<th>" . t("Toiminnot") . "</th>";
    echo "<th>" . t("Sertifikaattien voimassaolo") . "</th>";
    echo "<td class='back'></td>";
    echo "</tr>";

    echo "</thead>";

    echo "<tbody>";

    $_confirm = t("Haluatko varmasti poistaa pankkiyhteyden?");

    foreach ($pankkiyhteydet as $pankkiyhteys) {
      echo "<tr class='aktiivi'>";

      echo "<td>{$pankkiyhteys["pankin_nimi"]}</td>";
      echo "<td>{$pankkiyhteys["customer_id"]}</td>";

      echo "<td>";
      echo "<ul class='list-unstyled'>";

      $toiminnot = array(
        'hae_saldo' => array(
          'nimi'        => 'Hae saldo',
          'disable_for' => array('NDEAFIHH', 'DABAFIHH', 'HELSFIHH', 'ITELFIHH', 'POPFFI22', 'HANDFIHH'),
        ),
        'hae_factoring' => array(
          'nimi'        => 'Hae factoring',
          'disable_for' => array('OKOYFIHH', 'HELSFIHH', 'ITELFIHH', 'POPFFI22', 'HANDFIHH'),
        ),
        'hae_laskut' => array(
          'nimi' =>'Hae laskut',
        ),
      );

      foreach ($toiminnot as $toiminto => $options) {
        echo "<li class='nowrap'>";
        echo "<form>";
        echo "<input type='hidden' name='tee' value='paivita_{$toiminto}'>";
        echo "<input type='hidden' name='pankkiyhteys_tunnus' value='{$pankkiyhteys["tunnus"]}'/>";

        $checked  = $pankkiyhteys[$toiminto] == 1 ? ' checked' : '';
        $disabled = isset($options['disable_for']) && in_array($pankkiyhteys['pankki'], $options['disable_for']) ? ' disabled' : '';

        echo "<input type='checkbox' id='{$toiminto}' name='{$toiminto}' value='1'{$checked} onchange='this.form.submit()'{$disabled}>";
        echo "<label for='{$toiminto}'>{$options['nimi']}</label>";
        echo "</form>";
        echo "</li>";
      }

      echo "</ul>";
      echo "</td>";

      echo "<td>";

      // Lisätään tauluun certifikaattien expire datet
      $certit = array(
        "signing_certificate_valid_to" => "Allekirjoitus-sertifikaatti",
        "encryption_certificate_valid_to" => "Salaus-sertifikaatti",
        "bank_encryption_certificate_valid_to" => "Pankin salaus-sertifikaatti",
        "bank_root_certificate_valid_to" => "Pankin juuri-sertifikaatti",
        "ca_certificate_valid_to" => "Pankin CA-sertifikaatti",
      );

      foreach ($certit as $valid => $nimi) {
        $_time = $pankkiyhteys[$valid];

        if ($_time == '0000-00-00 00:00:00') {
          continue;
        }

        $_nimi = t($nimi);
        $_time = tv1dateconv($_time);

        echo "{$_nimi}: {$_time}<br>";
      }
      echo "</td>";

      echo "<td class='back'>";

      echo "<div>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='tee' value='vaihda_salasana_form'/>";
      echo "<input type='hidden' name='pankkiyhteys_tunnus' value='{$pankkiyhteys["tunnus"]}'/>";
      echo "<input type='submit' value='" . t("Vaihda salasana") . "' class='full-width'/>";
      echo "</form>";
      echo "</div>";

      echo "<div>";
      echo "<form method='post' class='multisubmit' onsubmit='return confirm(\"{$_confirm}\");'>";
      echo "<input type='hidden' name='tee' value='poista'/>";
      echo "<input type='hidden' name='pankkiyhteys_tunnus' value='{$pankkiyhteys["tunnus"]}'/>";
      echo "<input type='submit' value='" . t("Poista pankkiyhteys") . "' class='full-width'/>";
      echo "</form>";
      echo "</div>";

      if ($pankkiyhteys['pankki'] == "DABAFIHH") {
        echo "<div>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='tee' value='pankin_sertifikaatti'/>";
        echo "<input type='hidden' name='pankkiyhteys_tunnus' value='{$pankkiyhteys["tunnus"]}'/>";
        echo "<input type='submit' value='" . t("Päivitä pankin sertifikaatit") . "' class='full-width'/>";
        echo "</form>";
        echo "</div>";
      }

      if (in_array($pankkiyhteys['pankki'], array('NDEAFIHH', 'OKOYFIHH', 'DABAFIHH', 'HELSFIHH', 'ITELFIHH', 'POPFFI22', 'HANDFIHH'))) {
        echo "<div>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='tee' value='uusi_sertifikaatti'/>";
        echo "<input type='hidden' name='pankkiyhteys_tunnus' value='{$pankkiyhteys["tunnus"]}'/>";
        echo "<input type='submit' value='" . t("Uusi sertifikaatti") . "' class='full-width'/>";
        echo "</form>";
        echo "</div>";
      }

      echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
  }
}

require "inc/footer.inc";
