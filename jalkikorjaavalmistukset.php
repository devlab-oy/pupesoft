<?php

  require('inc/parametrit.inc');

  echo "<font class='head'>".t("Korjaa valmistuksia").":</font><hr><br>";

  if ($tee == "JALKILASKE" and checkdate($kka, $ppa, $vva)) {

    if ($raakaaine != "") {
      $selilisa = " sum(if(tilausrivi.tuoteno = '$raakaaine', 1, 0)) valriveja, ";
      $havlisa  = " and valriveja > 0 ";
    }
    else {
      $selilisa = "";
      $havlisa  = "";
    }

    $query = "  SELECT lasku.tunnus,
          sum(if(tilausrivi.tyyppi in ('V','W'), 1, 0)) valmistusriveja,
          $selilisa
          avg(if(tilausrivi.toimitettuaika='0000-00-00 00:00:00' or tilausrivi.tyyppi not in ('V','W'), NULL, date_format(tilausrivi.toimitettuaika, '%Y%m%d%H%i%s'))) toimitettuaikax
          FROM tilausrivi
          JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
          WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
          AND lasku.tila   in ('V', 'L')
          AND lasku.alatila  in ('V', 'K', 'X')
          AND (tilausrivi.toimitettu != '' or tilausrivi.tyyppi='D') and lasku.tilaustyyppi in ('V','W')
          AND lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00'
          GROUP BY lasku.tunnus
          HAVING valmistusriveja > 0
          $havlisa
          ORDER BY toimitettuaikax";
    $tilre = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($tilre) > 0) {

      echo "<table>";

      $korjattavat_valmistukset = array();

      while ($tilrow = mysql_fetch_array($tilre)) {
        $korjattavat_valmistukset[] = $tilrow["tunnus"];
      }

      $rekurkoko = count($korjattavat_valmistukset);

      // Tässä on rekursiivista toimintaa $korjattavat_valmistukset-array voi kasvaa matkan varrella
      for ($korjattavat_valmistukset_ind=0; $korjattavat_valmistukset_ind < $rekurkoko; $korjattavat_valmistukset_ind++) {
        jalkilaske_valmistus($korjattavat_valmistukset[$korjattavat_valmistukset_ind]);

        $rekurkoko = count($korjattavat_valmistukset);
      }

      echo "</table>";
    }
    else {
      echo "<br><br><font class='message'>".t("Yhtään valmistettavaa tilausta/tuotetta ei löytynyt")."...</font>";
    }

    $tee = "";
  }
  elseif ($tee == "JALKILASKE") {
    echo "<font class='error'>".t("VIRHE: Syötetty päivämäärä oli virheellinen")."!</font><br><br>";
    $tee = "";
  }

  if ($tee == "") {
    echo "<form method='post'>";
    echo "<input type='hidden' name='tee' value='JALKILASKE'>";
    echo "<table>";
    echo "<tr>
      <th>".t("Syötä päivämäärä josta korjataan")." ".t("(pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppa' value='$ppa' size='3'></td>
      <td><input type='text' name='kka' value='$kka' size='3'></td>
      <td><input type='text' name='vva' value='$vva' size='5'></td>
      </tr>\n";

    echo "<tr>
      <th>".t("Korjaa vain valmistuksia joissa on kulutettu raaka-ainetta")."</th>
      <td colspan='3'><input type='text' name='raakaaine' value='$raakaaine' size='30'></td>
      </tr>\n";

    echo "</table><br>";
    echo "<br><input type='submit' value='Korjaa'>";
    echo "</form>";
  }

  require ("inc/footer.inc");
