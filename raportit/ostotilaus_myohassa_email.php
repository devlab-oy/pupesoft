<?php

if (php_sapi_name() == 'cli') {

  // otetaan includepath aina rootista
  $pupe_root_polku = dirname(dirname(__FILE__));
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
  ini_set("display_errors", 0);

  // otetaan tietokantayhteys ja funkkarit
  require("inc/connect.inc");
  require("inc/functions.inc");

  $yhtio          = trim($argv[1]);
  $paivamaararaja = trim($argv[2]);

  $kieli = "";

  if (isset($argv[3])) {
    $kieli = trim($argv[3]);
  }

  $toimittajan_tuotetiedot = "";

  if (isset($argv[4])) {
    $toimittajan_tuotetiedot = trim($argv[4]);
  }

  //yhtiötä tai sähköpostia ei ole annettu
  if (empty($yhtio) or empty($paivamaararaja)) {
    echo "\nUsage: php ".basename($argv[0])." yhtio 3\n\n";
    die;
  }

  $yhtiorow = hae_yhtion_parametrit($yhtio);

  // Haetaan käyttäjän tiedot
  $query = "SELECT *
            FROM kuka
            WHERE yhtio = '$yhtio'
            AND kuka    = 'admin'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    die("User admin not found");
  }

  // Adminin oletus
  $kukarow = mysql_fetch_assoc($result);

  $tee     = "hae_ostotilaukset";
  $php_cli = true;
}
else {
  //Debuggaamista varten
  require("../inc/parametrit.inc");

  echo "<font class='head'>".t('Myöhässä olevien ostotilausten lähetys sähköpostiin')."</font><hr>";

  $tee                     = "hae_ostotilaukset";
  $paivamaararaja          = 3;
  $kieli                   = "";
  $toimittajan_tuotetiedot = "";
  $php_cli                 = false;
}

if ($tee == 'hae_ostotilaukset') {
  $ostotilaukset = hae_myohassa_olevat_ostotilaukset($paivamaararaja);

  if (!empty($ostotilaukset)) {
    $ostotilaukset_ostajittain = kasittele_ostotilaukset($ostotilaukset, 'ostaja');
    $email_bodys = generoi_email_body($ostotilaukset_ostajittain, $kieli, $toimittajan_tuotetiedot);
    laheta_sahkopostit($email_bodys, $kieli);

    $ostotilaukset_vastuuostajittain = kasittele_ostotilaukset($ostotilaukset, 'vastuuostaja');
    $email_bodys = generoi_email_body($ostotilaukset_vastuuostajittain, $kieli, $toimittajan_tuotetiedot);
    laheta_sahkopostit($email_bodys, $kieli);
  }
}

if (php_sapi_name() != 'cli') {
  require("inc/footer.inc");
}

/**
 * Haetaan tavarantoimittajalla lähetetyt ostotilaskut, jotka eivät ole vielä saapuneet
 *
 * @global array $kukarow
 * @param int $paivamaararaja
 * @return array
 */
function hae_myohassa_olevat_ostotilaukset($paivamaararaja) {
  global $kukarow;

  //AND tilausrivi.keratty = '', ei haeta ostotilausrivejä, jotka on jo saapuneet varastoon.
  $query = "SELECT lasku.tunnus as lasku_tunnus,
            lasku.nimi as toimittaja,
            tilausrivi.toimaika as toimitusaika,
            tilausrivi.tuoteno,
            tilausrivi.tilkpl,
            tilausrivi.jaksotettu as vahvistettu,
            lasku.laatija as ostaja,
            tuote.ostajanro as vastuuostaja,
            tuotteen_toimittajat.toim_tuoteno,
            tuotteen_toimittajat.toim_nimitys
            FROM lasku
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus  = lasku.tunnus
              AND tilausrivi.tyyppi   = 'O'
              AND tilausrivi.toimaika <= DATE_SUB(CURRENT_DATE, INTERVAL {$paivamaararaja} DAY)
              AND tilausrivi.varattu  > 0 )
            JOIN tuote
            ON ( tuote.yhtio = tilausrivi.yhtio
              AND tuote.tuoteno       = tilausrivi.tuoteno )
            JOIN tuotteen_toimittajat
            ON ( tuotteen_toimittajat.yhtio = tilausrivi.yhtio
              AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno
              AND tuotteen_toimittajat.liitostunnus = lasku.liitostunnus)
            WHERE lasku.yhtio         = '{$kukarow['yhtio']}'
            AND lasku.tila            = 'O'
            AND lasku.alatila         = 'A'
            ORDER BY lasku.nimi ASC";
  $result = pupe_query($query);

  $ostotilaukset = array();
  while ($ostotilaus = mysql_fetch_assoc($result)) {
    $ostotilaukset[] = $ostotilaus;
  }

  return $ostotilaukset;
}

/**
 *   Populoi ostotilaukset ostajan ostotilaus kohtaisesti
 *
 * @param array $ostotilaukset
 * @param string $ostaja_tyyppi
 * @return array
 */
function kasittele_ostotilaukset($ostotilaukset, $ostaja_tyyppi) {
  $ostotilaukset_temp = array();
  $ostajanro_kuka = array();

  foreach ($ostotilaukset as $ostotilaus) {

    if ($ostaja_tyyppi == "vastuuostaja") {
      if (empty($ostajanro_kuka[$ostotilaus['vastuuostaja']])) {
        $ostajanro_kuka[$ostotilaus['vastuuostaja']] = hae_kuka_ostajanro_perusteella($ostotilaus['vastuuostaja']);
      }

      $kuka = $ostajanro_kuka[$ostotilaus['vastuuostaja']];
    }
    else {
      $kuka = $ostotilaus['ostaja'];
    }

    $ostotilaukset_temp[$kuka][$ostotilaus['lasku_tunnus']]['rivit'][] = $ostotilaus;
  }

  return $ostotilaukset_temp;
}

/**
 *   Generoi sähköpostit ostajittain ostajien ostotilauksista.
 *
 * @param array $ostotilaukset
 * @return array
 */
function generoi_email_body($ostotilaukset, $kieli, $toimittajan_tuotetiedot) {
  global $yhtiorow;

  $email_bodys = array();

  //index:ssä on ostotilaus ostaja kuka
  foreach ($ostotilaukset as $ostaja => $ostajan_ostotilaukset) {
    $email_bodys[$ostaja] = t("Seuraavat ostotilausrivit ovat myöhässä", $kieli).".\n\n";
    foreach ($ostajan_ostotilaukset as $ostotilaus_tunnus => $ostotilaus) {
      $email_bodys[$ostaja] .= "-----------------------\n";
      $email_bodys[$ostaja] .= t("Ostotilaus", $kieli)." $ostotilaus_tunnus\n";
      //toimittaja voidaan hakea rivin ekalta solulta koska se on ostotilauksen kaikille riveille aina sama
      $email_bodys[$ostaja] .= t("Toimittaja", $kieli)." {$ostotilaus['rivit'][0]['toimittaja']}\n\n";

      if ($toimittajan_tuotetiedot != "") {
        $email_bodys[$ostaja] .= t("Tuoteno", $kieli).', ';
        $email_bodys[$ostaja] .= t("Toimittajan tuoteno", $kieli).', ';
        $email_bodys[$ostaja] .= t("Toimittajan nimitys", $kieli).', ';
        $email_bodys[$ostaja] .= t("Kpl", $kieli).', ';
        $email_bodys[$ostaja] .= t("Toimitusaika", $kieli).', ';
        $email_bodys[$ostaja] .= t("Vahvistettu", $kieli)."\n";

        foreach ($ostotilaus['rivit'] as $ostotilaus_rivi) {
          $email_bodys[$ostaja] .= $ostotilaus_rivi['tuoteno'].', ';
          $email_bodys[$ostaja] .= $ostotilaus_rivi['toim_tuoteno'].', ';
          $email_bodys[$ostaja] .= $ostotilaus_rivi['toim_nimitys'].', ';
          $email_bodys[$ostaja] .= $ostotilaus_rivi['tilkpl'].', ';
          $email_bodys[$ostaja] .= date('d.m.Y', strtotime($ostotilaus_rivi['toimitusaika'])).', ';
          $email_bodys[$ostaja] .= ($ostotilaus_rivi['vahvistettu'] == 1 ? t("Vahvistettu", $kieli) : t("Vahvistamatta", $kieli))."\n";
        }
      }
      else {
        $email_bodys[$ostaja] .= t("Tuoteno", $kieli).', ';
        $email_bodys[$ostaja] .= t("Kpl", $kieli).', ';
        $email_bodys[$ostaja] .= t("Toimitusaika", $kieli).', ';
        $email_bodys[$ostaja] .= t("Vahvistettu", $kieli)."\n";

        foreach ($ostotilaus['rivit'] as $ostotilaus_rivi) {
          $email_bodys[$ostaja] .= $ostotilaus_rivi['tuoteno'].', ';
          $email_bodys[$ostaja] .= $ostotilaus_rivi['tilkpl'].', ';
          $email_bodys[$ostaja] .= date('d.m.Y', strtotime($ostotilaus_rivi['toimitusaika'])).', ';
          $email_bodys[$ostaja] .= ($ostotilaus_rivi['vahvistettu'] == 1 ? t("Vahvistettu", $kieli) : t("Vahvistamatta", $kieli))."\n";
        }
      }

      $email_bodys[$ostaja] .= "\n";
    }
  }

  return $email_bodys;
}

/**
 * Lähettää sähköpostit
 *
 * @param array $email_bodys
 */
function laheta_sahkopostit($email_bodys, $kieli) {
  foreach ($email_bodys as $kuka => $email_body) {
    $to = hae_sahkopostiosoite($kuka);
    $parametrit = array(
      "to"     => $to,
      "subject"   => t("Myöhässä olevat ostotilaukset", $kieli),
      "ctype"     => "plain",
      "body"     => $email_body);
    pupesoft_sahkoposti($parametrit);
  }
}

/**
 *   Hakee kuka-taulusta sähköpostiosoitteen
 *
 * @global array $kukarow
 * @param string $kuka
 * @return string
 */
function hae_sahkopostiosoite($kuka) {
  global $kukarow;

  $query = "SELECT eposti
            FROM kuka
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND kuka    = '{$kuka}'";
  $result = pupe_query($query);
  $kuka_eposti = mysql_fetch_assoc($result);

  return $kuka_eposti['eposti'];
}

/**
 *   Hakee käyttäjänimen ostajanro perusteella
 *
 * @global array $kukarow
 * @param int $ostajanro
 * @return string
 */
function hae_kuka_ostajanro_perusteella($ostajanro) {
  global $kukarow;

  $query = "SELECT kuka
            FROM kuka
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND myyja   = '{$ostajanro}'";
  $result = pupe_query($query);

  $kuka = mysql_fetch_assoc($result);

  if (empty($kuka)) {
    return 'admin';
  }

  return $kuka['kuka'];
}
