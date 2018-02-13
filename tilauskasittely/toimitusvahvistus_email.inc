<?php

if ($kieli == '') {
  $querykiel = "SELECT kieli
                FROM asiakas
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$laskurow[liitostunnus]'";
  $kielresult = pupe_query($querykiel);
  $kielrow = mysql_fetch_assoc($kielresult);

  $kieli = strtolower($kielrow['kieli']);
}

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

pupesoft_log('toimitusvahvistus_email', "Valmistellaan tilauksien {$otunnukset} toimitusvahvistuksien lähettämistä sähköpostiin");

$toimitusvahvistus_email_lahetys = true;

if (!isset($rakir_row) or !isset($rakir_row['toimitusvahvistus']) or !isset($rakir_row['asiakas_email']) or !isset($tunnukset) or !isset($otunnukset)) {
  $toimitusvahvistus_email_lahetys = false;

  $msg = "Vaadittavia muuttujia puuttui!";

  if (!isset($rakir_row)) $msg .= " Rahtikirjan riviä ei löytynyt (rakir_row).";
  if (!isset($rakir_row['toimitusvahvistus'])) $msg .= " Toimitusvahvistusta ei ole asetettu (rakir_row[toimitusvahvistus]).";
  if (!isset($rakir_row['asiakas_email'])) $msg .= " Asiakkaan sähköpostia ei ole asetettu (rakir_row[asiakas_email]).";
  if (!isset($tunnukset)) $msg .= " Tunnukset-muuttujaa ei ole asetettu.";
  if (!isset($otunnukset)) $msg .= " Otunnukset-muuttujaa ei ole asetettu.";

  pupesoft_log('toimitusvahvistus_email', $msg);
}

if ($rakir_row['toimitusvahvistus'] != 'toimitusvahvistus_email.inc' or $rakir_row['asiakas_email'] == '' or $tunnukset == '' or $otunnukset == '') {
  $toimitusvahvistus_email_lahetys = false;

  $msg = "Vaadittavia muuttujia puuttui!";

  if ($rakir_row['toimitusvahvistus'] != 'toimitusvahvistus_email.inc') $msg .= " Toimitusvahvistus ei ole toimitusvahvistus_email.inc ({$rakir_row['toimitusvahvistus']}).";
  if ($rakir_row['asiakas_email'] == '') $msg .= " Asiakkaan sähköposti on tyhjä.";
  if ($tunnukset == '') $msg .= " Tunnukset-muuttuja on tyhjä.";
  if ($otunnukset == '') $msg .= " Otunnukset-muuttuja on tyhjä.";

  pupesoft_log('toimitusvahvistus_email', $msg);
}

if ($toimitusvahvistus_email_lahetys) {
  pupesoft_log('toimitusvahvistus_email', "Tarvittavat muuttujat löytyy ja tarkistukset tehty! Aloitetaan sähköpostin generointi ja lähettäminen.");

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
                   WHERE lasku.yhtio  = '{$kukarow['yhtio']}'
                   AND lasku.tunnus   IN ({$otunnukset})
                   AND lasku.myyja   != ''
                   AND kuka.eposti   != ''
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
            lasku.valkoodi,
            tilausrivi.otunnus,
            tilausrivi.tunnus,
            tilausrivi.perheid,
            tilausrivin_lisatiedot.ei_nayteta
            FROM lasku
            JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi = 'L' AND tilausrivi.toimitettu != '' AND tilausrivi.toimitettuaika != '0000-00-00 00:00:00')
            LEFT JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tunnus  IN ({$otunnukset})
            AND (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)";
  $rivi_loop_res = pupe_query($query);

  while ($rivi_loop_row = mysql_fetch_assoc($rivi_loop_res)) {

    // isätuote, jonka lapsia ei näytetä
    if ($rivi_loop_row["perheid"] == $rivi_loop_row["tunnus"]) {

      $query = "SELECT vanhatunnus
                FROM lasku
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$rivi_loop_row[otunnus]'";
      $vtunres = pupe_query($query);
      $vtunrow = mysql_fetch_assoc($vtunres);

      if ($vtunrow["vanhatunnus"] != 0) {
        $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                  FROM lasku use index (yhtio_vanhatunnus)
                  WHERE yhtio     = '$kukarow[yhtio]'
                  and vanhatunnus = '$vtunrow[vanhatunnus]'";
        $perheresult = pupe_query($query);
        $numrows = mysql_num_rows($perheresult);
      }

      if ($numrows == 0 or $vtunrow["vanhatunnus"] == 0) {
        $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                  FROM lasku use index (PRIMARY)
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$rivi_loop_row[otunnus]'";
        $perheresult = pupe_query($query);
      }

      if (mysql_num_rows($perheresult) > 0) {

        $perherow = mysql_fetch_assoc($perheresult);

        // lasketaan isätuotteen riville lapsien (joita ei näytetä) hinnat yhteen
        $query = "SELECT
                  round(sum((tilausrivi.hinta/if(lasku.vienti_kurssi=0, 1, lasku.vienti_kurssi)) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}),'$yhtiorow[hintapyoristys]') rivihinta
                  FROM tilausrivi
                  JOIN lasku ON tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus
                  LEFT JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
                  WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                  AND tilausrivi.otunnus  in ($perherow[tunnukset])
                  AND tilausrivi.tyyppi  != 'D'
                  AND tilausrivi.var     != 'O'
                  AND tilausrivi.perheid  = '$rivi_loop_row[perheid]'
                  AND tilausrivin_lisatiedot.ei_nayteta = 'E'";
        $riresult = pupe_query($query);
        $perherow = mysql_fetch_assoc($riresult);
        $rivi_loop_row['hinta'] += $perherow["rivihinta"];
      }
    }

    if ($_toimvahvistus['selitetark_2'] == "eihintaa") {
      $_hinta = "";
    }
    else {
      $_hinta = "{$rivi_loop_row['hinta']} {$rivi_loop_row['valkoodi']}";
    }

    $body .= "{$rivi_loop_row['tuoteno']} {$rivi_loop_row['maara']} {$rivi_loop_row['nimitys']} $_hinta\n\n";
  }

  $body .= "\n";
  $body .= t("Seurantakoodit", $kieli).":\n\n";

  $_tilausnumerot = explode(',', $otunnukset);
  $linkit = tilauksen_seurantalinkit($_tilausnumerot);

  foreach ($linkit as $seurantalinkki) {
    $link = $seurantalinkki['link'];
    $nro  = $seurantalinkki['id'];

    if (empty($link)) {
      $body .= "{$nro}\n";
    }
    else {
      $body .= "{$link}\n";
    }
  }

  $body .= "\n";

  $body .= t("Tilauksen toimitusosoite", $kieli).":\n\n";

  $maan_tiedot = hae_maa(array('maakoodi' => $laskurow['toim_maa']));

  $body .= "{$laskurow['toim_nimi']} {$laskurow['toim_nimitark']}\n";
  $body .= "{$laskurow['toim_osoite']}\n";
  $body .= "{$laskurow['toim_postino']} {$laskurow['toim_postitp']}\n";
  $body .= "{$maan_tiedot['nimi']}\n";

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
      if (!$php_cli) {
        echo t("Sähköpostin lähetys onnistui").": {$email_kuka}<br />";
      }

      pupesoft_log('toimitusvahvistus_email', "Sähköpostin lähetys onnistui osoitteeseen {$email_kuka}.");
    }
    else {
      pupesoft_log('toimitusvahvistus_email', "Sähköpostin lähetys epäonnistui osoitteeseen {$email_kuka}.");
    }
  }
}
else {
  pupesoft_log('toimitusvahvistus_email', "Toimitusvahvistus-sähköpostia ei lähetetä virheiden vuoksi.");
}
