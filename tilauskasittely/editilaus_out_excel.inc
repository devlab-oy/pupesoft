<?php

// Formaatti on:
// toimittajanumero
// tilausnumero
// tuotekoodi
// määrä

$query_ale_lisa = generoi_alekentta("O");

$query = "SELECT tilausrivi.tunnus,
          tilausrivi.otunnus,
          ROUND(tilausrivi.varattu * IF(tuotteen_toimittajat.tuotekerroin = 0, 1, tuotteen_toimittajat.tuotekerroin), 2) AS varattu,
          tilausrivi.yksikko,
          tilausrivi.tuoteno,
          IFNULL(tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno) toim_tuoteno,
          ROUND(tilausrivi.hinta * IF(tuotteen_toimittajat.tuotekerroin = 0 OR tuotteen_toimittajat.tuotekerroin IS NULL, 1, tuotteen_toimittajat.tuotekerroin) * {$query_ale_lisa}, '$yhtiorow[hintapyoristys]') nettohinta
          FROM tilausrivi
          JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno AND tuotteen_toimittajat.liitostunnus = '{$laskurow['liitostunnus']}')
          LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tilausrivi.yhtio AND ta.tuoteno = tilausrivi.tuoteno AND ta.laji = 'ei_edi_ostotilaukseen')
          WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
          AND tilausrivi.tyyppi  = 'O'
          AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
          AND tilausrivi.varattu > 0
          AND ta.tunnus IS NULL
          ORDER BY tilausrivi.tunnus";
$result = pupe_query($query);

if (mysql_num_rows($result) == 0) {
  echo "<font class='error'>".t("Lähetettäviä tilausrivejä ei löydy")."</font>";
}
else {
  
  include 'inc/pupeExcel.inc';
  
  $worksheet   = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi   = 0;
  $excelsarake = 0;

  $worksheet->writeString($excelrivi++,   1, trim($laskurow["toim_nimi"]." ".$laskurow["toim_nimitark"]), $boldi);
  $worksheet->writeString($excelrivi++, 1, $laskurow["toim_osoite"], $boldi);
  $worksheet->writeString($excelrivi++, 1, $laskurow["toim_maa"]."-".$laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $boldi);

  $worksheet->writeString($excelrivi, 1, "Order Number:", $boldi);
  $worksheet->writeString($excelrivi++, 2, $laskurow["tunnus"]);
  $worksheet->writeString($excelrivi, 1, "Date of order:", $boldi);
  $worksheet->writeString($excelrivi++, 2, date("Y-m-d"));

  $excelrivi++;
  $worksheet->writeString($excelrivi, 0, "Line", $boldi);
  $worksheet->writeString($excelrivi, 1, "Your product code", $boldi);
  $worksheet->writeString($excelrivi, 2, "Our product code", $boldi);
  $worksheet->writeString($excelrivi, 4, "Quantity", $boldi);
  $worksheet->writeString($excelrivi, 5, "Price", $boldi);
  $excelrivi++;

  $lask = 1;

  while ($tilausrivirow = mysql_fetch_assoc($result)) {

    $worksheet->writeString($excelrivi, 0, $lask);
    $worksheet->writeString($excelrivi, 1, $tilausrivirow['toim_tuoteno']);
    $worksheet->writeString($excelrivi, 2, $tilausrivirow['tuoteno']);
    $worksheet->writeNumber($excelrivi, 4, $tilausrivirow['varattu']);
    $worksheet->writeNumber($excelrivi, 5, $tilausrivirow['nettohinta']);
    $excelrivi++;

    $lask++;
  }

  // We need to explicitly close the workbook
  $tmpfilenimi = $worksheet->close();

  echo "<table>";
  echo "<tr><th>".t("Tallenna ostotilaus").":</th>";
  echo "<form method='post' action='{$palvelin}tilauskasittely/tilaus_osto.php'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='Order_{$laskurow["tunnus"]}.xlsx'>";
  echo "<input type='hidden' name='filenimi' value='{$tmpfilenimi}'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna Excel")."'></td></form></tr>";
  echo "</table><br>";
}

// Nollataan tarkoituksella lopetusmuuttuja
$lopetus = "";
