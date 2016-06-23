<?php

if ($kukarow['extranet'] == '') die(t("K‰ytt‰j‰n parametrit - T‰m‰ ominaisuus toimii vain extranetiss‰"));

if (isset($toiminto) and $toiminto == 'vaihda_oletusasiakkuus' and $asiakasvalinta != '') {
  paivita_kayttajan_oletusasiakkuus($asiakasvalinta);
}

piirra_multiasiakkuusdropdown();

function piirra_multiasiakkuusdropdown() {
  global $kukarow;

  // Haetaan k‰ytt‰j‰n asiakkuudet
  $query = "SELECT asiakas.tunnus, asiakas.nimi
            FROM customers_users
            JOIN asiakas ON (customers_users.customer_id = asiakas.tunnus)
            WHERE user_id = '{$kukarow['tunnus']}'";
  $multiresult = pupe_query($query);

  // Jos ei ole linkattu mit‰‰n asiakkaita, ei piirret‰ mit‰‰n
  if (mysql_num_rows($multiresult) == 0 and empty($kukarow['oletus_asiakas'])) {
    return;
  }

  // Haetaan oletusasiakkuus
  $query = "SELECT asiakas.*
            FROM asiakas
            WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
            AND asiakas.tunnus  = '{$kukarow['oletus_asiakas']}'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  echo "<form>";
  echo "<input type='hidden' name='toiminto' value='vaihda_oletusasiakkuus'>";

  // Piirret‰‰n dropdown
  echo "<table><tr>";
  echo "<td><select name='asiakasvalinta' value='{$kukarow['oletus_asiakas']}' onchange='submit();'>";
  echo "<option value='' selected>".t("Oletusasiakkuus")."</option>";

  while ($multirow = mysql_fetch_assoc($multiresult)) {
    echo "<option value='{$multirow['tunnus']}'>".$multirow['nimi']."</option>";
  }

  echo "</select>";
  echo "</td>";

  if (isset($row['nimi'])) {
    echo "<td class='back'>".$row['nimi']."</td>";
  }
  echo "</tr></table></form>";
}

function paivita_kayttajan_oletusasiakkuus($asiakasvalinta) {
  global $kukarow;

  $query = "UPDATE kuka
            SET oletus_asiakas = '$asiakasvalinta'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$kukarow['tunnus']}'";
  pupe_query($query);
  $kukarow['oletus_asiakas'] = $asiakasvalinta;

  return true;
}
