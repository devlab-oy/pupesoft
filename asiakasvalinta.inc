<?php

if ($kukarow['extranet'] == '') die(t("K‰ytt‰j‰n parametrit - T‰m‰ ominaisuus toimii vain extranetiss‰"));

if (isset($toiminto) and $toiminto == 'vaihda_oletusasiakkuus' and $asiakasvalinta != '') {
  paivita_kayttajan_oletusasiakkuus($asiakasvalinta);

  // p‰ivitet‰‰n kukarow kesken jos on vaihdettu asiakas
  $til_qry = "SELECT tunnus
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laatija  = '{$kukarow['laatija']}'
              AND ohjelma_moduli = 'EXTRANET'
              AND tila = 'N'
              AND alatila = ''
              AND liitostunnus = '{$asiakasvalinta}'
              ORDER BY tunnus desc
              LIMIT 1";
  $til_res = pupe_query($til_qry);
  if (mysql_num_rows($til_res) == 1) {
    $tilrow = mysql_fetch_assoc($til_res);
    $kukarow['kesken'] == $tilrow['tunnus'];

    $query  = "UPDATE kuka set kesken='{$tilrow['tunnus']}' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
    $result = pupe_query($query);
  }
  else {
    $kukarow['kesken'] == 0;

    $query  = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
    $result = pupe_query($query);
  }
}

piirra_multiasiakkuusdropdown();

function piirra_multiasiakkuusdropdown() {
  global $kukarow, $toim;

  // Haetaan k‰ytt‰j‰n asiakkuudet
  $query = "SELECT asiakas.tunnus, asiakas.nimi, asiakas.nimitark, asiakas.selaus
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
  echo "<input type='hidden' name='toim'     value='$toim'>";

  // Piirret‰‰n dropdown
  echo "<table><tr>";
  echo "<td><select name='asiakasvalinta' value='{$kukarow['oletus_asiakas']}' onchange='submit();'>";
  echo "<option value='' selected>".t("Oletusasiakkuus")."</option>";

  while ($multirow = mysql_fetch_assoc($multiresult)) {
    if (empty($multirow['selaus']))  {
      echo "<option value='{$multirow['tunnus']}'>".$multirow['nimi'].', '.$row['nimitark']."</option>";
    }
    else {
      echo "<option value='{$multirow['tunnus']}'>".$multirow['selaus']."</option>";
    }
  }

  echo "</select>";
  echo "</td>";

  if (isset($row['nimi'])) {
    if (empty($row['selaus'])) {
      echo "<td class='back'>".$row['nimi'].', '.$row['nimitark']."</td>";
    }
    else {
      echo "<td class='back'>".$row['selaus']."</td>";
    }
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
