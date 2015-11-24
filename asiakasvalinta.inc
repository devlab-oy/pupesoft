<?php

if (isset($toiminto) and $toiminto == 'vaihda_oletusasiakkuus' and $asiakasvalinta != '') {
  paivita_kayttajan_oletusasiakkuus($asiakasvalinta);
}

piirra_multiasiakkuusdropdown();

function piirra_multiasiakkuusdropdown() {
  global $kukarow;

#  if ($kukarow['multi_asiakkuus'] == '' or $kukarow['oletus_asiakas'] = '') {
#    return;
#  }
  // Haetaan oletusasiakkuus
  $query = "SELECT asiakas.*
            FROM asiakas 
            WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
            AND asiakas.tunnus = '{$kukarow['oletus_asiakas']}'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  echo "<form>";
  echo "<input type='hidden' name='toiminto' value='vaihda_oletusasiakkuus'>";

  // Piirret‰‰n dropdown
  echo "<table><tr>";
  echo "<td><select name='asiakasvalinta' value='{$kukarow['oletus_asiakas']}' onchange='submit();'>";
  echo "<option value='' selected>".t("Oletusasiakkuus")."</option>";

  // Haetaan k‰ytt‰j‰n asiakkuudet
  $query = "SELECT asiakas.tunnus,
            asiakas.nimi
            FROM customers_users
            JOIN asiakas ON (asiakas.tunnus = customers_users.customer_id) 
            WHERE customers_users.user_id = '{$kukarow['tunnus']}'";
  $result = pupe_query($query);

  while ($multirow = mysql_fetch_assoc($result)) {
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
            AND tunnus = '{$kukarow['tunnus']}'";
  pupe_query($query);
  $kukarow['oletus_asiakas'] = $asiakasvalinta;

  return true;
}
