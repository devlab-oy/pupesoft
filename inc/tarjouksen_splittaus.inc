<?php

require_once 'tilauskasittely/luo_myyntitilausotsikko.inc';

$query = "SELECT laskun_lisatiedot.sopimus_lisatietoja,
          laskun_lisatiedot.saate,
          lasku.olmapvm,
          lasku.clearing,
          lasku.viesti,
          lasku.toimvko,
          lasku.toimaika as toimitusaika,
          tilausrivi.*,
          lasku.varasto,
          lasku.tilaustyyppi
          FROM laskun_lisatiedot
          JOIN tilausrivi ON (tilausrivi.yhtio = laskun_lisatiedot.yhtio AND tilausrivi.otunnus = laskun_lisatiedot.otunnus)
          JOIN lasku ON (lasku.yhtio = laskun_lisatiedot.yhtio AND lasku.tunnus = laskun_lisatiedot.otunnus)
          WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
          AND laskun_lisatiedot.otunnus = '{$tilausnumero}'";
$result = pupe_query($query);

$tilausrivit = array();

while ($tilausrivi = mysql_fetch_assoc($result)) {
  $tilausrivit[] = $tilausrivi;
  //setataan muuttujat monta kertaa
  $tarjouksen_asiakkaat = $tilausrivi['sopimus_lisatietoja'];
  $clearing = $tilausrivi['clearing'];
  $alkuperainen_varasto = $tilausrivi['varasto'];
  $viimeinen_voimassaolo_pvm = $tilausrivi['olmapvm'];
  $saate_teksti = $tilausrivi['saate'];
  $tilaustyyppi = $tilausrivi['tilaustyyppi'];
  $viesti = $tilausrivi['viesti'];
  $toimitusviikko = $tilausrivi['toimvko'];
  $toimaika = $tilausrivi['toimitusaika'];
}

$tarjouksen_asiakkaat = explode(',', $tarjouksen_asiakkaat);

$dummy_tarjouksen_tilausnumero = $tilausnumero;

foreach ($tarjouksen_asiakkaat as $tarjous_asiakas) {

  $kukarow['kesken'] = 0;

  if ($toim == "EXTTARJOUS") {
    $tilausnumero = luo_myyntitilausotsikko('TARJOUS', $tarjous_asiakas, '', '', '', '', '', '');
  }
  else {
    $tilausnumero = luo_myyntitilausotsikko('ENNAKKO', $tarjous_asiakas, '', '', '', '', '', '');
  }

  //Halutaan pitää asiakkaille kopioitavissa tarjouksissa myyjän valitsema varasto, viesti, toimitusviikko
  $query = "UPDATE lasku
            JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
            SET lasku.varasto = '{$alkuperainen_varasto}',
            lasku.olmapvm           = '{$viimeinen_voimassaolo_pvm}',
            laskun_lisatiedot.saate = '{$saate_teksti}',
            lasku.clearing          = '{$clearing}',
            lasku.viesti            = '{$viesti}',
            lasku.toimvko           = '{$toimitusviikko}',
            lasku.toimaika          = '{$toimaika}',
            lasku.tilaustyyppi      = '{$tilaustyyppi}'
            WHERE lasku.yhtio       = '{$kukarow['yhtio']}'
            AND lasku.tunnus        = '{$tilausnumero}'";
  pupe_query($query);

  $query = "SELECT *
            FROM lasku
            JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tunnus  = '{$tilausnumero}'";
  $result = pupe_query($query);
  $laskurow = mysql_fetch_assoc($result);

  $kukarow['kesken'] = $tilausnumero;

  foreach ($tilausrivit as $tilausrivi) {

    if ($tilausrivi['tunnus'] == $tilausrivi['perheid'] or $tilausrivi['perheid'] == 0) {

      $trow = hae_tuote($tilausrivi['tuoteno']);

      // Mikä maa mikä valuutta - Tarkistetaan asiakkaan valuutta ja maa
      $query = "SELECT *
                FROM asiakas
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$tarjous_asiakas}'";
      $result = pupe_query($query);
      $maatiedot = mysql_fetch_assoc($result);

      // Haetaanko ruotsihinnat
      if (strtoupper($maatiedot["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
        $query = "SELECT alv
                  FROM tuotteen_alv
                  WHERE tuoteno = '{$tilausrivi['tuoteno']}'
                  AND maa       = '{$maatiedot['maa']}'";
        $result = pupe_query($query);
        $tuotteen_alv_rivi = mysql_fetch_assoc($result);
        $tuotteen_alv = $tuotteen_alv_rivi['alv'];

        $tilausrivi['hinta'] = tuotteen_myyntihinta($laskurow, $trow, $tilausrivi['varattu']);
      }

      // Ext-ennakolle tulee hinnat aina nettona, lasketaan alennukset pois
      if ($toim == "EXTENNAKKO") {
        $tilausrivi['hinta'] = $tilausrivi['hinta'] * (1 - $tilausrivi['ale1'] / 100) * (1 - $tilausrivi['ale2'] / 100) * (1 - $tilausrivi['ale3'] / 100);
        $tilausrivi['ale1'] = 0;
        $tilausrivi['ale2'] = 0;
        $tilausrivi['ale3'] = 0;
        $tilausrivi['netto'] = 'N';
      }

      $parametrit = array(
        'trow'         => $trow,
        'tuoteno'       => $trow['tuoteno'],
        'laskurow'       => $laskurow,
        'kpl'         => $tilausrivi['varattu'],
        'ale1'         => $tilausrivi['ale1'],
        'ale2'         => $tilausrivi['ale2'],
        'ale3'         => $tilausrivi['ale3'],
        'netto'         => $tilausrivi['netto'],
        'hinta'         => $tilausrivi['hinta'],
        'varataan_saldoa'   => 'EI',
        'var'         => $tilausrivi['var'],
        'toimaika'       => $laskurow['toimaika']
      );

      lisaa_rivi($parametrit);
    }
  }

  // Tällä parametrilla ohitetaan tilausvahvistuksen lähettäminen Tarjouspohjaa kopioidessa
  $laskurow['tilausvahvistus'] = "";

  require_once 'tilaus-valmis.inc';
}

$query = "UPDATE lasku
          SET tila = 'D'
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tunnus  = '{$dummy_tarjouksen_tilausnumero}'";
pupe_query($query);

$query = "UPDATE kuka
          SET kesken = 0
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND kuka    = '{$kukarow['kuka']}'";
pupe_query($query);
