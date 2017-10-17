<?php

if (php_sapi_name() == 'cli') {
  $pupe_root_polku = dirname(dirname(__FILE__));
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
  ini_set("display_errors", 0);

  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $yhtio = trim($argv[1]);

  //yhti�t� ei ole annettu
  if (empty($yhtio)) {
    echo "\nUsage: php ".basename($argv[0])." yhtio\n\n";
    die;
  }

  $yhtiorow = hae_yhtion_parametrit($yhtio);

  // Haetaan k�ytt�j�n tiedot
  $kukarow = hae_kukarow('admin', $yhtio);
}
elseif (php_sapi_name() != 'cli') {
  //for debug reasons
  require 'inc/parametrit.inc';

  echo "<font class='head'>".t('Asiakkaan jt-rivien toimitusajan vahvistus')."</font><hr>";
}

//haetaan ostotilaukset ja niiden tilausrivit joiden toimitusaika on p�ivittynyt viimeisein 24h aikana
$ostotilauksien_tilausrivit = hae_ostotilauksien_tilausrivit_joiden_toimitusaika_on_muuttunut_tai_vahvistettu();

//tarkistetaan onko yhtiolla s�hk�postien l�hetys parametri p��ll�
if ($yhtiorow['jt_toimitusaika_email_vahvistus'] != 'K') {
  //haetaan asiakkaat joilla kyseinen parametri on p��ll� ja joilla on jt rivej�
  $kaikki_myyntitilaukset = false;
}
else {
  //haetaan yhtion kaikki myyntitilaukset joilla on jt_rivej�
  $kaikki_myyntitilaukset = true;
}

$myyntitilaukset = hae_myyntitilaukset_joilla_jt_riveja($kaikki_myyntitilaukset);

$asiakkaille_lahtevat_sahkopostit = generoi_asiakas_emailit($ostotilauksien_tilausrivit, $myyntitilaukset);

laheta_asiakas_emailit($asiakkaille_lahtevat_sahkopostit);

/**
 * Hakee ostotilaukset ja niiden rivit joiden toimaika on muuttunut viimeisen p�iv�n aikana
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @return array
 */


function hae_ostotilauksien_tilausrivit_joiden_toimitusaika_on_muuttunut_tai_vahvistettu() {
  global $kukarow, $yhtiorow;

  $query = "SELECT lasku.tunnus AS lasku_tunnus,
            tilausrivi.toimaika,
            tilausrivi.tuoteno,
            tilausrivi.tilkpl as tilkpl,
            tilausrivi.tilkpl as tilkpl_jaljella
            FROM lasku
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus                             = lasku.tunnus )
            JOIN tilausrivin_lisatiedot
            ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus        = tilausrivi.tunnus
              AND tilausrivin_lisatiedot.toimitusaika_paivitetty >= DATE_SUB(NOW(), INTERVAL 1 DAY))
            WHERE lasku.yhtio                                    = '{$kukarow['yhtio']}'
            AND lasku.tila                                       = 'O'
            AND lasku.alatila                                    IN ('','A','B')
            ORDER BY lasku.toimaika ASC";
  $result = pupe_query($query);
  $ostolaskut = array();
  while ($ostolasku = mysql_fetch_assoc($result)) {
    $ostolaskut[$ostolasku['tuoteno']]['tilausrivit'][] = $ostolasku;
  }

  $ostolaskut = kasittele_ostotilaukset($ostolaskut);

  return $ostolaskut;
}


/**
 * K�sitell��n ostolaskujen tilausrivit niin, ett� tuotteen alle saadaan kokonaistilaus m��r�t
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param array   $ostolaskut
 * @return array
 */
function kasittele_ostotilaukset($ostolaskut) {
  global $kukarow, $yhtiorow;

  $ostolasku_temp = array();
  foreach ($ostolaskut as $ostolasku_index => $ostolasku) {
    foreach ($ostolasku['tilausrivit'] as $ostolasku_tilausrivi) {
      $ostolasku_temp[$ostolasku_index]['kpl_yhteensa'] += $ostolasku_tilausrivi['tilkpl'];
      $ostolasku_temp[$ostolasku_index]['tilausrivit'][] = $ostolasku_tilausrivi;
    }
    $ostolasku_temp[$ostolasku_index]['tilkpl_jaljella'] = $ostolasku_temp[$ostolasku_index]['kpl_yhteensa'];
    $ostolasku_temp[$ostolasku_index]['tuoteno'] = $ostolasku_index;
  }

  return $ostolasku_temp;
}


/**
 * Hakee kaikki myyntitilaukset ja niiden rivit, joissa on jt-rivej�
 * T�t� funktiota k�ytet��n kun yhti�parametri jt_toimitusaika_email_vahvistus on p��ll�
 *
 * TAI
 *
 * Hakee myyntitilaukset ja niiden rivit joiden asiakkailla on jt_toimitusaika_email_vahvistus p��ll� sek� myyntitilauksia, joissa on jt-rivej�
 * T�t� funktiota k�ytet��n jos yhti�parametri jt_toimitusaika_email_vahvistus ei ole p��ll�
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param bool    $kaikki_myyntitilaukset
 * @return array
 */
function hae_myyntitilaukset_joilla_jt_riveja($kaikki_myyntitilaukset = true) {
  global $kukarow, $yhtiorow;

  if ($kaikki_myyntitilaukset) {
    $asiakas_join = "AND (asiakas.jt_toimitusaika_email_vahvistus IN ('K', ''))";
  }
  else {
    $asiakas_join = "AND asiakas.jt_toimitusaika_email_vahvistus = 'K'";
  }
  //Haetaan kaikki myyntilaskut joiden asiakkaan jt_toimitusaika_email_vahvistus on K�ytet��n yhti�n oletus parametri� tai saa l�hett�� s�hk�posteja
  $query = "SELECT lasku.tunnus,
            lasku.nimi,
            lasku.liitostunnus,
            lasku.tilausyhteyshenkilo,
            lasku.ohjelma_moduli,
            lasku.myyja,
            lasku.asiakkaan_tilausnumero
            FROM lasku
            JOIN asiakas
            ON ( asiakas.yhtio = lasku.yhtio
              AND asiakas.tunnus      = lasku.liitostunnus
              {$asiakas_join} )
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus  = lasku.tunnus
              AND tilausrivi.var      = 'J'
              AND tilausrivi.tyyppi  != 'D')
            WHERE lasku.yhtio         = '{$kukarow['yhtio']}'
            GROUP BY lasku.tunnus
            ORDER BY lasku.luontiaika ASC";
  $result = pupe_query($query);

  $myyntitilaukset = array();
  while ($myyntitilaus = mysql_fetch_assoc($result)) {
    $myyntitilaus['tilausrivit'] = hae_myyntitilausrivit($myyntitilaus['tunnus']);
    $myyntitilaus['asiakas'] = hae_myyntitilauksen_asiakas($myyntitilaus['liitostunnus']);
    $myyntitilaus['tilausyhteyshenkilo'] = hae_tilauksen_yhteyshenkilo($myyntitilaus);
    $myyntitilaukset[] = $myyntitilaus;
  }

  return $myyntitilaukset;
}


/**
 * Hakee myyntitilauksen myyntitilausrivit
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param int     $tilaus_tunnus
 * @return array
 */
function hae_myyntitilausrivit($tilaus_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "SELECT tuoteno,
            varattu+jt AS tilkpl,
            varattu+jt AS tilkpl_jaljella,
            nimitys,
            toimaika
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$tilaus_tunnus}'
            AND var     = 'J'
            AND tyyppi != 'D'";
  $result = pupe_query($query);

  $tilausrivit = array();
  while ($tilausrivi = mysql_fetch_assoc($result)) {
    $tilausrivit[] = $tilausrivi;
  }

  return $tilausrivit;
}


/**
 * Hakee myyntitilauksen asiakkaan
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param int     $liitostunnus
 * @return array
 */
function hae_myyntitilauksen_asiakas($liitostunnus) {
  global $kukarow, $yhtiorow;

  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$liitostunnus}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}


/**
 * Generoi ostotilausrivien sek� myyntitilauksien pohjalta asiakkaille l�hetett�v�t emailit
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param array   $tuotteet_ja_niiden_ostotilausrivit
 * @param array   $myyntitilaukset
 * @return array
 */
function generoi_asiakas_emailit($tuotteet_ja_niiden_ostotilausrivit, $myyntitilaukset) {
  global $kukarow, $yhtiorow;

  $asiakkaille_lahtevat_sahkopostit = array();

  foreach ($tuotteet_ja_niiden_ostotilausrivit as &$tuote_ja_sen_ostotilausrivit) {
    foreach ($myyntitilaukset as $myyntitilaus) {
      foreach ($myyntitilaus['tilausrivit'] as $myyntitilausrivi) {
        if ($tuote_ja_sen_ostotilausrivit['tuoteno'] == $myyntitilausrivi['tuoteno']) {
          //riitt��k� tietyn tuotteen kaikkien ostotilausrivien kappaleet myyntitilausriville
          if ($tuote_ja_sen_ostotilausrivit['tilkpl_jaljella'] >= $myyntitilausrivi['tilkpl']) {
            foreach ($tuote_ja_sen_ostotilausrivit['tilausrivit'] as &$ostotilausrivi) {
              //riitt��k� t�m�n kyseisen ostotilausrivin kappaleet myyntitilausriville
              if ($ostotilausrivi['tilkpl_jaljella'] >= $myyntitilausrivi['tilkpl_jaljella']) {
                populoi_asiakkaan_email_array($asiakkaille_lahtevat_sahkopostit, $myyntitilaus, $myyntitilausrivi, $ostotilausrivi);

                $ostotilausrivi['tilkpl_jaljella'] = $ostotilausrivi['tilkpl_jaljella'] - $myyntitilausrivi['tilkpl_jaljella'];
                $tuote_ja_sen_ostotilausrivit['tilkpl_jaljella'] = $tuote_ja_sen_ostotilausrivit['tilkpl_jaljella'] - $myyntitilausrivi['tilkpl_jaljella'];
                $myyntitilausrivi['tilkpl_jaljella'] = 0;

                break 1;
              }
              else {
                if ($ostotilausrivi['tilkpl_jaljella'] != 0) {
                  populoi_asiakkaan_email_array($asiakkaille_lahtevat_sahkopostit, $myyntitilaus, $myyntitilausrivi, $ostotilausrivi);

                  $myyntitilausrivi['tilkpl_jaljella'] = $myyntitilausrivi['tilkpl_jaljella'] - $ostotilausrivi['tilkpl_jaljella'];
                  $ostotilausrivi['tilkpl_jaljella'] = 0;
                }
              }
            }
          }
          else {
            //jos ei riit�
            //vied��n kyseisen tuotteen saldo nollille, jotta j�lkimm�iset myyntitilaukset eiv�t p��se niihin k�siksi
            $tuote_ja_sen_ostotilausrivit['tilkpl_jaljella'] = 0;
            continue;
          }
        }
      }
    }
  }

  return $asiakkaille_lahtevat_sahkopostit;
}


/**
 * Populoi asiakas_email_arrayt�, johon ker�t��n asiakkaalle l�hetett�vi� emaileja
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param array   $asiakkaille_lahtevat_sahkopostit
 * @param array   $myyntitilaus
 * @param array   $myyntitilausrivi
 * @param array   $ostotilausrivi
 */
function populoi_asiakkaan_email_array(&$asiakkaille_lahtevat_sahkopostit, $myyntitilaus, $myyntitilausrivi, $ostotilausrivi) {
  global $kukarow, $yhtiorow;

  $kieli = $myyntitilaus['asiakas']['kieli'];
  //jos kaikki ostotilausrivin tuotteet riitt��
  if ($ostotilausrivi['tilkpl_jaljella'] >= $myyntitilausrivi['tilkpl_jaljella']) {
    $kappaleet_jotka_riittaa = $myyntitilausrivi['tilkpl_jaljella'];
  }
  else {
    $kappaleet_jotka_riittaa = $ostotilausrivi['tilkpl_jaljella'];
  }

  //jos riitt�� niin voidaan lis�t� asiakkaan emailiin tilauksen alle tilausrivi
  if (empty($asiakkaille_lahtevat_sahkopostit[$myyntitilaus['liitostunnus']]['tilaukset'][$myyntitilaus['tunnus']]['tilausrivit'][$myyntitilausrivi['tuoteno']])) {
    $asiakkaille_lahtevat_sahkopostit[$myyntitilaus['liitostunnus']]['tilaukset'][$myyntitilaus['tunnus']]['tilausrivit'][$myyntitilausrivi['tuoteno']] .= '<td>'.$myyntitilausrivi['tuoteno'].'</td>'
      .'<td>'.$myyntitilausrivi['nimitys'].'</td>'
      .'<td>'.$myyntitilausrivi['tilkpl'].'</td>'
      .'<td>'.t("Toimitusaika", $kieli).': '.date('d.m.Y', strtotime($ostotilausrivi['toimaika'])).' '.(number_format($kappaleet_jotka_riittaa, 0)).' '.t("kpl", $kieli).' '; //HUOM J�TET��N VIIMEINEN TD PRINTTAAMATTA, jotta viimeiseen soluun voidaan appendaa lis�� toimituksia. viimeinen td printataan emailin luomis vaiheessa
  }
  else {
    $asiakkaille_lahtevat_sahkopostit[$myyntitilaus['liitostunnus']]['tilaukset'][$myyntitilaus['tunnus']]['tilausrivit'][$myyntitilausrivi['tuoteno']] .= '<br/>'
      .t("Toimitusaika", $kieli).': '.date('d.m.Y', strtotime($ostotilausrivi['toimaika'])).' '.(number_format($kappaleet_jotka_riittaa, 0)).' '.t("kpl", $kieli).' '; //HUOM J�TET��N VIIMEINEN TD PRINTTAAMATTA, jotta viimeiseen soluun voidaan appendaa lis�� toimituksia. viimeinen td printataan emailin luomis vaiheessa
  }

  $asiakkaille_lahtevat_sahkopostit[$myyntitilaus['liitostunnus']]['kieli'] = $kieli;

  $mailiosoite = $myyntitilaus['asiakas']['email'];

  if (!empty($myyntitilaus["tilausyhteyshenkilo"])) {
    $mailiosoite .= ", {$myyntitilaus["tilausyhteyshenkilo"]["email"]}";
  }

  $asiakkaille_lahtevat_sahkopostit[$myyntitilaus['liitostunnus']]['email'] = $mailiosoite;
  $asiakkaille_lahtevat_sahkopostit[$myyntitilaus['liitostunnus']]['tilaukset'][$myyntitilaus['tunnus']]['asiakkaan_tilausnumero'] = $myyntitilaus["asiakkaan_tilausnumero"];
}


/**
 * L�hett�� generoidut emailit
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param array   $asiakkaille_lahtevat_sahkopostit
 */
function laheta_asiakas_emailit($asiakkaille_lahtevat_sahkopostit = array()) {
  global $kukarow, $yhtiorow;

  foreach ($asiakkaille_lahtevat_sahkopostit as $asiakas_sahkoposti) {
    $body = t('Hei', $asiakas_sahkoposti['kieli']).',<br/><br/>';
    $body .= t("Seuraavien tuotteiden toimitusaika on muuttunut", $asiakas_sahkoposti['kieli']).'.<br/><br/>';
    foreach ($asiakas_sahkoposti['tilaukset'] as $tilaustunnus => $tilaus) {
      $body .= t('Tilaus', $asiakas_sahkoposti['kieli']).": {$tilaustunnus}"."<br/>";

      if (!empty($tilaus["asiakkaan_tilausnumero"])) {
        $body .= t('Tilausnumeronne', $asiakas_sahkoposti['kieli']) . ": {$tilaus["asiakkaan_tilausnumero"]}" . "<br/>";
      }

      $body .= "<table border=1>";
      $body .= "<tr>";
      $body .= "<td>".t("Tuoteno", $asiakas_sahkoposti['kieli'])."</td>";
      $body .= "<td>".t("Nimitys", $asiakas_sahkoposti['kieli'])."</td>";
      $body .= "<td>".t("Tilattu kpl", $asiakas_sahkoposti['kieli'])."</td>";
      $body .= "<td>".t("Saapumiset", $asiakas_sahkoposti['kieli'])."</td>";
      $body .= "</tr>";
      foreach ($tilaus['tilausrivit'] as $tilausrivi) {
        $body .= "<tr>";
        $body .= $tilausrivi.'</td>'; //emailin generoimis vaiheessa j�tet��n viimeinen td printtaamatta, jotta viimeiseen soluun pystyt��n appendaamaan lis�� toimituksia
        $body .= "</tr>";
      }
      $body .= "</table>";
      $body .= "<br/>";
      $body .= "<br/>";
      $body .= t("Yst�v�llisin terveisin", $asiakas_sahkoposti['kieli']);
      $body .= "<br/>";
      $body .= $yhtiorow['nimi'];
      $body .= "<br/>";
      $body .= "<br/>";
      $body .= t("Puh", $asiakas_sahkoposti['kieli']).': '.$yhtiorow['puhelin'];
      $body .= "<br/>";
      $body .= t("Mail", $asiakas_sahkoposti['kieli']).': '.$yhtiorow['email'];
      $body .= "<br/>";
      $body .= t("Www", $asiakas_sahkoposti['kieli']).': '.$yhtiorow['www'];
    }
    laheta_sahkoposti($asiakas_sahkoposti['email'], $body);
  }
}


/**
 * L�hett�� yhden emailin
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @param string  $email
 * @param string  $body
 */
function laheta_sahkoposti($email, $body) {
  global $kukarow, $yhtiorow;

  $parametrit = array(
    "to"     => $email,
    "subject"   => t('Tilauksenne toimitusajankohta on p�ivittynyt'),
    "ctype"     => "html",
    "body"     => $body,
  );
  pupesoft_sahkoposti($parametrit);
}


/**
 *
 * @param array   $params
 *
 * @return array Yhteyshenkilon tiedot
 */
function hae_tilauksen_yhteyshenkilo($params = array()) {
  global $kukarow;

  $ohjelma_moduli      = $params['ohjelma_moduli'];
  $myyja               = $params['myyja'];
  $liitostunnus        = $params['liitostunnus'];
  $tilausyhteyshenkilo = $params['tilausyhteyshenkilo'];

  if ($ohjelma_moduli == 'EXTRANET') {
    $query = "SELECT eposti AS email
              FROM kuka
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND tunnus  = '{$myyja}'";
    $result = pupe_query($query);
  }
  else {
    $query = "SELECT email
              FROM yhteyshenkilo
              WHERE yhtio      = '{$kukarow["yhtio"]}'
              AND liitostunnus = '{$liitostunnus}'
              AND nimi         = '{$tilausyhteyshenkilo}'";
    $result = pupe_query($query);
  }

  return mysql_fetch_assoc($result);
}
