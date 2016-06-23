<?php

if ($kieli == '') {
  $querykiel = "SELECT kieli
                FROM asiakas
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus = '$laskurow[liitostunnus]'";
  $kielresult = pupe_query($querykiel);
  $kielrow = mysql_fetch_assoc($kielresult);

  $kieli = strtolower($kielrow['kieli']);
}

$toimitusvahvistus_email_lahetys = true;

if (!isset($rakir_row) or !isset($rakir_row['toimitusvahvistus']) or !isset($rakir_row['asiakas_email']) or !isset($tunnukset) or !isset($otunnukset)) {
  $toimitusvahvistus_email_lahetys = false;
}

if ($rakir_row['toimitusvahvistus'] != 'toimitusvahvistus_email.inc' or $rakir_row['asiakas_email'] == '' or $tunnukset == '' or $otunnukset == '') {
  $toimitusvahvistus_email_lahetys = false;
}

if ($toimitusvahvistus_email_lahetys) {
  $_toimvahvistus = mysql_fetch_assoc(t_avainsana("TOIMVAHVISTUS", $kieli, " and selite = 'toimitusvahvistus_email.inc' "));

  if ($_toimvahvistus['selitetark_2'] == "eihintaa") {
    $_hinta = "";
  }
  else {
    $_hinta = " ".t("Hinta", $kieli);
  }

  $kenelle_emailataan = array();

  if (!empty($_toimvahvistus['selitetark_3'])) {
    // Haetaan myyjän sähköpostiosoite
    $myyjaquery = "SELECT DISTINCT(kuka.eposti) myyjan_email
                   FROM lasku
                   JOIN kuka ON (lasku.yhtio = kuka.yhtio AND lasku.myyja = kuka.tunnus)
                   WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                   AND lasku.tunnus IN ({$otunnukset})
                   AND lasku.myyja != ''
                   AND kuka.eposti != ''
                   LIMIT 1";
    $myyjaresult = pupe_query($myyjaquery);
    $myyjarow = mysql_fetch_assoc($myyjaresult);

    if ($_toimvahvistus['selitetark_3'] == 'M') {
      // Email lähtee vain myyjälle
      $kenelle_emailataan[] = $myyjarow['myyjan_email'];
    }
    elseif ($_toimvahvistus['selitetark_3'] == 'K') {
      // Email lähtee myyjälle ja asiakkaalle
      $kenelle_emailataan[] = $myyjarow['myyjan_email'];
      $kenelle_emailataan[] = $rakir_row['asiakas_email'];
    }
  }
  else {
    // Email lähtee vain asiakkaalle
    $kenelle_emailataan[] = $rakir_row['asiakas_email'];
  }

  $body = t("Hei", $kieli).",\n\n".t("Seuraavat tilaamanne tuotteet ovat toimituksessa", $kieli).":\n\n\n".t("Tuotenumero", $kieli)." ".t("Määrä", $kieli)." ".t("Nimitys", $kieli).$_hinta."\n\n";

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

    $body .= "{$rivi_loop_row['tuoteno']} {$rivi_loop_row['maara']} {$rivi_loop_row['nimitys']} $_hinta\n\n";
  }

  $body .= "\n";

  $query = "SELECT if(sscc_ulkoinen != '', sscc_ulkoinen, rahtikirjanro) rahtikirjanro
            FROM rahtikirjat
            WHERE tunnus IN ({$tunnukset})
            AND yhtio    = '{$kukarow['yhtio']}'";
  $seurantakoodit_res = pupe_query($query);

  $body .= t("Seurantakoodit", $kieli).":\n\n";

  while ($seurantakoodit_row = mysql_fetch_assoc($seurantakoodit_res)) {
    $body .= "{$seurantakoodit_row['rahtikirjanro']}\n\n";
  }

  $body .= "\n";

  $body .= t("Tilauksen toimitusosoite", $kieli).":\n\n";

  $maan_tiedot = hae_maa(array('maakoodi' => $rakir_row['toim_maa']));

  $body .= "{$rakir_row['toim_nimi']} {$rakir_row['toim_nimitark']}\n\n";
  $body .= "{$rakir_row['toim_osoite']}\n\n";
  $body .= "{$rakir_row['toim_postino']} {$rakir_row['toim_postitp']}\n\n";
  $body .= "{$maan_tiedot['nimi']}\n\n";

  foreach ($kenelle_emailataan as $key => $email_kuka) {
    // Sähköpostin lähetykseen parametrit
    $parametri = array(
      "to"      => $email_kuka,
      "cc"      => "",
      "subject" => t("Toimitusvahvistus", $kieli),
      "ctype"   => "text",
      "body"    => $body,
    );

    if (pupesoft_sahkoposti($parametri)) {
      echo t("Sähköpostin lähetys onnistui").": {$email_kuka}<br />";
    }
  }
}
