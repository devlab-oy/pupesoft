<?php

$query = "SELECT *
          FROM laskun_lisatiedot
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND otunnus = '{$laskurow['tunnus']}'";
$lisatiedot_result = pupe_query($query);
$laskun_lisatiedot = mysql_fetch_assoc($lisatiedot_result);

$query = "SELECT *
          FROM asiakas
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tunnus = '{$laskurow['liitostunnus']}'";
$asiakasres = pupe_query($query);
$asiakasrow = mysql_fetch_assoc($asiakasres);

$query = "SELECT *, IF(puh != '', puh, gsm) AS puhelin
          FROM yhteyshenkilo
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tyyppi = 'A'
          AND liitostunnus = '{$laskurow['liitostunnus']}'
          AND nimi = '{$laskurow['tilausyhteyshenkilo']}'";
$yhteyshenkilores = pupe_query($query);
$yhteyshenkilorow = mysql_fetch_assoc($yhteyshenkilores);

$lask_yritys = trim($laskun_lisatiedot['laskutus_nimi'].' '.$laskun_lisatiedot['laskutus_nimitark']);
$lask_osoite = $laskun_lisatiedot['laskutus_osoite'];
$lask_postitp = $laskun_lisatiedot['laskutus_postitp'];
$lask_postino = $laskun_lisatiedot['laskutus_postino'];

$toim_nimi     = substr(trim($laskurow['toim_nimi'].' '.$laskurow['toim_nimitark']), 0, 30);
$toim_osoite   = substr(trim($laskurow['toim_osoite']), 0, 25);
$toim_postitp  = substr(trim($laskurow['toim_postitp']), 0, 25);
$toim_postino  = trim($laskurow['toim_postino']);
$toim_maa      = trim($laskurow['toim_maa']);

$edi_order  = "*IS from:721111720-1 to:IKH,ORDERS*id:{$laskurow['tunnus']} version:AFP-1.0 *MS\n";
$edi_order .= "*MS {$laskurow['tunnus']}\n";
$edi_order .= "*RS OSTOTIL\n";
$edi_order .= "OSTOTIL.OT_NRO:{$laskurow['tunnus']}\n";
$edi_order .= "OSTOTIL.OT_TOIMITTAJANRO:0\n";
$edi_order .= "OSTOTIL.OT_TILAUSTYYPPI:1\n";
$edi_order .= "OSTOTIL.OT_TILAUSAIKA:{$laskurow['h1time']}\n";
$edi_order .= "OSTOTIL.OT_KASITTELIJA:\n";
$edi_order .= "OSTOTIL.OT_TOIMITUSPVM:{$laskurow['toimaika']}\n";
$edi_order .= "OSTOTIL.OT_TOIMITUSAIKA:\n";
$edi_order .= "OSTOTIL.OT_TOIMITUSTAPA:{$laskurow['toimitustapa']}\n";
$edi_order .= "OSTOTIL.OT_TOIMITUSEHTO:{$laskurow['toimitusehto']}\n";
$edi_order .= "OSTOTIL.OT_MAKSUEHTO:{$laskurow['maksuehto']}\n";
$edi_order .= "OSTOTIL.OT_VIITTEEMME:{$laskurow['viesti']}\n";
$edi_order .= "OSTOTIL.OT_VIITTEENNE:{$laskurow['ohjausmerkki']}\n";
$edi_order .= "OSTOTIL.OT_SUMMA:{$laskurow['summa']}\n";
$edi_order .= "OSTOTIL.OT_VALUUTTAKOODI:{$laskurow['valkoodi']}\n";
$edi_order .= "OSTOTIL.OT_KLAUSUULI1:\n";
$edi_order .= "OSTOTIL.OT_KLAUSUULI2:\n";
$edi_order .= "OSTOTIL.OT_KULJETUSOHJE:\n";
$edi_order .= "OSTOTIL.OT_LAHETYSTAPA:\n";
$edi_order .= "OSTOTIL.OT_VAHVISTUS_FAKSILLA:\n";
$edi_order .= "OSTOTIL.OT_FAKSI:\n";
$edi_order .= "OSTOTIL.OT_ASIAKASNRO:{$yhtiorow['ovttunnus']}\n";
$edi_order .= "OSTOTIL.OT_YRITYS:{$lask_yritys}\n";
$edi_order .= "OSTOTIL.OT_KATUOSOITE:{$lask_osoite}\n";
$edi_order .= "OSTOTIL.OT_POSTITOIMIPAIKKA:{$lask_postitp}\n";
$edi_order .= "OSTOTIL.OT_POSTINRO:{$lask_postino}\n";
$edi_order .= "OSTOTIL.OT_YHTEYSHENKILO:{$yhteyshenkilorow['nimi']}\n";
$edi_order .= "OSTOTIL.OT_YHTEYSHENKILONPUH:{$yhteyshenkilorow['puhelin']}\n";
$edi_order .= "OSTOTIL.OT_YHTEYSHENKILONFAX:{$yhteyshenkilorow['fax']}\n";
$edi_order .= "OSTOTIL.OT_MYYNTI_YRITYS:{$yhtiorow['nimi']}\n";
$edi_order .= "OSTOTIL.OT_MYYNTI_KATUOSOITE:{$yhtiorow['osoite']}\n";
$edi_order .= "OSTOTIL.OT_MYYNTI_POSTITOIMIPAIKKA:{$yhtiorow['postitp']}\n";
$edi_order .= "OSTOTIL.OT_MYYNTI_POSTINRO:{$yhtiorow['postino']}\n";
$edi_order .= "OSTOTIL.OT_MYYNTI_MAAKOODI:{$yhtiorow['maa']}\n";
$edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILO:\n";
$edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONPUH:\n";
$edi_order .= "OSTOTIL.OT_MYYNTI_YHTEYSHENKILONFAX:\n";
$edi_order .= "OSTOTIL.OT_TOIMITUS_YRITYS:\n";
$edi_order .= "OSTOTIL.OT_TOIMITUS_NIMI:{$toim_nimi}\n";
$edi_order .= "OSTOTIL.OT_TOIMITUS_KATUOSOITE:{$toim_osoite}\n";
$edi_order .= "OSTOTIL.OT_TOIMITUS_POSTITOIMIPAIKKA:{$toim_postitp}\n";
$edi_order .= "OSTOTIL.OT_TOIMITUS_POSTINRO:{$toim_postino}\n";
$edi_order .= "OSTOTIL.OT_TOIMITUS_MAAKOODI:{$toim_maa}\n";
$edi_order .= "OSTOTIL.OT_TOIMITUS_EMAIL:{$laskurow['toim_email']}\n";
$edi_order .= "OSTOTIL.OT_TOIMITUS_PUH:{$laskurow['toim_puh']}\n";

$edi_order .= "OSTOTIL.OT_OVTTUNNUS:{$asiakasrow['ovttunnus']}\n";
$edi_order .= "OSTOTIL.OT_MAKSETTU:\n";
$edi_order .= "OSTOTIL.OT_VEROMAARA:\n";
$edi_order .= "OSTOTIL.VERKKOKAUPPA:\n";
$edi_order .= "OSTOTIL.OT_VERKKOKAUPPA_ASIAKASNRO:\n";
$edi_order .= "OSTOTIL.OT_VERKKOKAUPPA_KOHDE:\n";
$edi_order .= "OSTOTIL.OT_VERKKOKAUPPA_TILAUSNUMERO:\n";
$edi_order .= "OSTOTIL.OT_VERKKOKAUPPA_TILAUSVIITE:\n";

$edi_order .= "*RE OSTOTIL\n";

$query = "SELECT *
          FROM rahtikirjat
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND otsikkonro = '{$laskurow['tunnus']}'";
$rahtikirjares = pupe_query($query);

while ($rahtikirjarow = mysql_fetch_assoc($rahtikirjares)) {
  $edi_order .= "OSTOTILPAK.OTP_SSCC:\n";
  $edi_order .= "OSTOTILPAK.OTP_BRUTTOPAINO:{$rahtikirjarow['kilot']}\n";
  $edi_order .= "OSTOTILPAK.OTP_TILAVUUS:{$rahtikirjarow['kuutiot']}\n";
  $edi_order .= "OSTOTILPAK.OTP_TILAVUUSYKS:m3\n";
}

# Koontirahtikirjan pakkaus
$edi_order .= "OSTOTILPAK.OTP_KOONTISSCC:\n";
$edi_order .= "OSTOTILPAK.OTP_KOONTIBRUTTOPAINO:\n";
$edi_order .= "OSTOTILPAK.OTP_KOONTITILAVUUS:\n";
$edi_order .= "OSTOTILPAK.OTP_KOONTITILAVUUSYKS:\n";

$i = 1;

$query = "SELECT tilausrivi.*, tuote.eankoodi
          FROM tilausrivi
          JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.panttitili = '' AND tuote.ei_saldoa = '')
          LEFT JOIN tuotteen_avainsanat AS ta ON (ta.yhtio = tilausrivi.yhtio AND ta.tuoteno = tilausrivi.tuoteno AND ta.laji = 'ei_edi_ostotilaukseen')
          WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
          AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
          AND tilausrivi.var     not in ('U','J')
          AND ta.tunnus IS NULL";
$tilausrivi_res = pupe_query($query);

while ($tilausrivi_row = mysql_fetch_assoc($tilausrivi_res)) {

  $kpl = $tilausrivi_row['jt'] + $tilausrivi_row['varattu'];

  if ($tilausrivi_row['var'] == 'P') {
    $kpl = $tilausrivi_row['tilkpl'] * 1;
  }

  // HUOM: Reklamaatioissa laitetaan nettohyvitysyksikköhinta
  $hinta = $tilausrivi_row['hinta'] * generoi_alekentta_php($tilausrivi_row, 'M', 'kerto');

  // nettorivihinta
  $rivihinta = $kpl * $hinta;

  $tuotenumero = preg_replace("/[^a-zA-ZåäöÅÄÖ0-9]/", " ", $tilausrivi_row['tuoteno']);
  $tuotteen_nimitys = preg_replace("/[^a-zA-ZåäöÅÄÖ0-9]/", " ", $tilausrivi_row['nimitys']);

  $edi_order .= "*RS OSTOTILRIV {$i}\n";
  $edi_order .= "OSTOTILRIV.OTR_NRO:{$laskurow['tunnus']}\n";
  $edi_order .= "OSTOTILRIV.OTR_RIVINRO:{$tilausrivi_row['tunnus']}\n";
  $edi_order .= "OSTOTILRIV.OTR_TOIMITTAJANRO:\n";
  $edi_order .= "OSTOTILRIV.OTR_TUOTEKOODI:{$tuotenumero}\n";
  $edi_order .= "OSTOTILRIV.OTR_EANKOODI:{$tilausrivi_row['eankoodi']}\n";
  $edi_order .= "OSTOTILRIV.OTR_SSCC:\n";
  $edi_order .= "OSTOTILRIV.OTR_NIMI:{$tuotteen_nimitys}\n";

  $kpl = round($kpl);

  $edi_order .= "OSTOTILRIV.OTR_TILATTUMAARA:{$kpl}\n";
  $edi_order .= "OSTOTILRIV.OTR_RIVISUMMA:{$rivihinta}\n";
  $edi_order .= "OSTOTILRIV.OTR_OSTOHINTA:{$tilausrivi_row['hinta']}\n";

  $ale = generoi_alekentta_php($tilausrivi_row, $myynti_vai_osto, 'erikseen');
  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    $edi_order .= "OSTOTILRIV.OTR_ALENNUS_{$alepostfix}:".$ale["ale".$alepostfix]."\n";
  }

  $edi_order .= "OSTOTILRIV.OTR_VEROKANTA:{$tilausrivi_row['alv']}\n";

  //$edi_order .= "OSTOTILRIV.OTR_VIITE:{$tilausrivi_row['kommentti']}\n";
  $edi_order .= "OSTOTILRIV.OTR_OSATOIMITUSKIELTO:\n";
  $edi_order .= "OSTOTILRIV.OTR_JALKITOIMITUSKIELTO:\n";
  $edi_order .= "OSTOTILRIV.OTR_YKSIKKO:{$tilausrivi_row['yksikko']}\n";
  $edi_order .= "*RE  OSTOTILRIV $i\n";
  $i++;

  $sqlquery = "UPDATE tilausrivi SET
               tilaajanrivinro = tunnus
               WHERE yhtio     = '{$kukarow['yhtio']}'
               AND tunnus      = '{$tilausrivi_row['tunnus']}'";
  $tresult = pupe_query($sqlquery, $GLOBALS["masterlink"]);
}

if ($i > 1) {

  $edi_order .= "*ME\n";
  $edi_order .= "*IE\n";

  $edipolku = "/tmp";

  $filename = $edipolku."/{$laskurow['tunnus']}.dat";
  file_put_contents($filename, $edi_order);
}
