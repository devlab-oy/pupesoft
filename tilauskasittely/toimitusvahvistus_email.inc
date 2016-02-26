<?php

if (!empty($laskurow['asiakas_email'])) {

  if ($kieli == '') {
    $querykiel = "SELECT kieli
                  FROM asiakas
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus = '$laskurow[liitostunnus]'";
    $kielresult = pupe_query($querykiel);
    $kielrow = mysql_fetch_assoc($kielresult);

    $kieli = strtolower($kielrow['kieli']);
  }

  $_toimvahvistus = t_avainsana("TOIMVAHVISTUS", "", " and avainsana.selite = '{$laskurow['toimitusvahvistus']}'", "", "", "selitetark_2");

  if ($_toimvahvistus == "eihintaa") {
    $_hinta = "";
  }
  else {
    $_hinta = " ".t("Hinta", $kieli);
  }

  $body = t("Hei", $kieli).",\n\n".t("Seuraavat tilaamanne tuotteet ovat toimituksessa", $kieli).":\n\n".t("Tuotenumero", $kieli)." ".t("Määrä", $kieli)." ".t("Nimitys", $kieli).$_hinta."\n";

  $query_ale_lisa = generoi_alekentta('M');

  $query = "SELECT tilausrivi.tuoteno,
            tilausrivi.varattu+tilausrivi.kpl as maara,
            tilausrivi.nimitys,
            round(tilausrivi.hinta * tilausrivi.varattu * {$query_ale_lisa}, 2) hinta,
            lasku.valkoodi
            FROM lasku
            JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi = 'L' AND tilausrivi.toimitettu != '' AND tilausrivi.toimitettuaika != '0000-00-00 00:00:00')
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tunnus  IN ({$otunnukset})";
  $rivi_loop_res = pupe_query($query);

  while ($rivi_loop_row = mysql_fetch_assoc($rivi_loop_res)) {

    if ($_toimvahvistus == "eihintaa") {
      $_hinta = "";
    }
    else {
      $_hinta = "{$rivi_loop_row['hinta']} {$rivi_loop_row['valkoodi']}";
    }

    $body .= "{$rivi_loop_row['tuoteno']} {$rivi_loop_row['maara']} {$rivi_loop_row['nimitys']} $_hinta\n";
  }

  $body .= "\n";

  $query = "SELECT if(sscc_ulkoinen != '', sscc_ulkoinen, rahtikirjanro) rahtikirjanro
            FROM rahtikirjat
            WHERE tunnus IN ({$tunnukset})
            AND yhtio = '{$kukarow['yhtio']}'";
  $seurantakoodit_res = pupe_query($query);

  $body .= t("Seurantakoodit", $kieli).":\n";

  while ($seurantakoodit_row = mysql_fetch_assoc($seurantakoodit_res)) {
    $body .= "{$seurantakoodit_row['rahtikirjanro']}\n";
  }

  $body .= "\n";

  $body .= t("Tilauksen toimitusosoite", $kieli).":\n\n";

  $maan_tiedot = hae_maa(array('maakoodi' => $laskurow['toim_maa']));

  $body .= "{$laskurow['toim_nimi']} {$laskurow['toim_nimitark']}\n";
  $body .= "{$laskurow['toim_osoite']}\n";
  $body .= "{$laskurow['toim_postino']} {$laskurow['toim_postitp']}\n";
  $body .= "{$maan_tiedot['nimi']}\n";

  // Sähköpostin lähetykseen parametrit
  $parametri = array(
    "to" => $laskurow['asiakas_email'],
    "cc" => "",
    "subject" => t("Toimitusvahvistus", $kieli),
    "ctype" => "text",
    "body" => $body
  );

  if (pupesoft_sahkoposti($parametri)) {
    echo t("Sähköpostin lähetys onnistui").": {$laskurow['asiakas_email']}<br />";
  }
}
