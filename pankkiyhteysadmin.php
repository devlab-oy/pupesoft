<?php

require "inc/parametrit.inc";
require "inc/pankkiyhteys_functions.inc";

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

// Varmistetaan, ett� sepa pankkiyhteys on kunnossa. Funkio kuolee, jos ei ole.
sepa_pankkiyhteys_kunnossa();

$tee = empty($tee) ? '' : $tee;
$customer_id = empty($customer_id) ? '' : $customer_id;
$pin = empty($pin) ? '' : $pin;
$bank = "";

// Debug moodissa, voidaan upata suoraan key/cert k�ytt�liittym�st�
$debug = empty($debug) ? 0 : 1;

$tuetut_pankit = tuetut_pankit();

// Poistetaan pankkiyhteys
if ($tee == "poista") {
  if (poista_pankkiyhteys($pankkiyhteys)) {
    ok("Pankkiyhteys poistettu");
  }
  else {
    virhe("Pankkiyhteytt� ei poistettu");
  }

  $tee = "";
}

// Uuden pankkiyhteyden oikeellisuustarkistus
if ($tee == "luo") {
  $virheet_count = 0;

  // Otetaan pankin nimi muuttujaan
  $bank = $tuetut_pankit[$pankki]["lyhyt_nimi"];

  if (empty($salasana)) {
    virhe("Salasana t�ytyy antaa");
    $virheet_count++;
  }
  elseif ($salasana != $salasanan_vahvistus) {
    virhe("Salasanan vahvistus ei vastaa salasanaa");
    $virheet_count++;
  }

  if (empty($customer_id)) {
    virhe("Asiakastunnus t�ytyy antaa");
    $virheet_count++;
  }

  if (empty($pin) and $debug != 1) {
    virhe("PIN-koodi t�ytyy antaa");
    $virheet_count++;
  }

  if ($virheet_count > 0) {
    echo "<br>";
    $tee = "";
  }
}

// Haetaan sertifikaatti jos PIN on annettu
if ($tee == "luo" and $pin != '') {
  // Generoidaan allekirjoitusta ja salausta varten private key ja certificate-signing-request
  $generoidut_tunnukset1 = generoi_private_key_ja_csr();
  $generoidut_tunnukset2 = generoi_private_key_ja_csr();

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
    virhe("Sertifikaatin hakeminen ep�onnistui, tarkista PIN-koodi ja asiakastunnus");
    $tee = "";
  }
}

// Avainpari annettu k�ytt�liittym�st� debugmode!
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

  $query = "INSERT INTO pankkiyhteys SET
            yhtio                       = '{$kukarow['yhtio']}',
            pankki                      = '{$pankki}',
            signing_certificate         = '{$osc}',
            signing_private_key         = '{$spk}',
            encryption_certificate      = '{$oec}',
            encryption_private_key      = '{$epk}',
            bank_encryption_certificate = '{$bec}',
            bank_root_certificate       = '{$brc}',
            ca_certificate              = '{$bca}',
            customer_id                 = '{$customer_id}'";
  $result = pupe_query($query);
}

// K�ytt�liittym�
$mahdolliset_pankkiyhteydet = mahdolliset_pankkiyhteydet();

// Jos voidaan tehd� uusia pankkiyhteyksi�
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

// Jos meill� on jo perustettuja pankkiyhteyksi�
if (!empty($pankkiyhteydet)) {
  echo "<br/>";
  echo "<font class='message'>" . t("Pankkiyhteydet") . "</font>";
  echo "<hr>";

  echo "<table>";
  echo "<thead>";

  echo "<tr>";
  echo "<th>" . t("Pankki") . "</th>";
  echo "<th>" . t("Asiakastunnus") . "</th>";
  echo "<th></th>";
  echo "</tr>";

  echo "</thead>";

  echo "<tbody>";

  $_confirm = t("Haluatko varmasti poistaa pankkiyhteyden?");

  foreach ($pankkiyhteydet as $pankkiyhteys) {
    echo "<tr class='aktiivi'>";
    echo "<td>{$pankkiyhteys["pankin_nimi"]}</td>";
    echo "<td>{$pankkiyhteys["customer_id"]}</td>";
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
