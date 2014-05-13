<?php
  // Kutsutaanko CLI:st�
  $php_cli = FALSE;

  if (php_sapi_name() == 'cli') {
    $php_cli = TRUE;
  }

  if (!$php_cli) {
    die ("T�t� scripti� voi ajaa vain komentorivilt�!");
  }
  else {
    if (trim($argv[1]) == '') {
      echo "Et antanut yhti�t�!\n";
      exit;
    }

    // otetaan includepath aina rootista
    ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
    error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
    ini_set("display_errors", 0);

    // otetaan tietokanta connect
    require("inc/connect.inc");
    require("inc/functions.inc");

    $kukarow['yhtio'] = (string) $argv[1];
    $kukarow['kuka']  = 'admin';
    $kukarow['kieli'] = 'fi';

    if (trim($argv[2]) != '') {
      $kukarow['kuka'] = trim($argv[2]);
    }

    $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

    $query = "  SELECT *
          FROM keraysvyohyke
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND ulkoinen_jarjestelma = 'K'";
    $gen_ker_res_result = pupe_query($query);

    while ($gen_ker_row = mysql_fetch_assoc($gen_ker_res_result)) {

      // HUOM!!! FUNKTIOSSA TEHD��N LOCK TABLESIT, LUKKOJA EI AVATA T�SS� FUNKTIOSSA! MUISTA AVATA LUKOT FUNKTION K�YT�N J�LKEEN!!!!!!!!!!
      $erat = tee_keraysera($gen_ker_row["tunnus"], $gen_ker_row["varasto"]);

      if (isset($erat['tilaukset']) and count($erat['tilaukset']) > 0) {
        // Tallennetaan miss� t�� er� on tehty
        $ohjelma_moduli = "KARDEX";

        // Tallennetaan ker�yser�
        require('inc/tallenna_keraysera.inc');

        // N�m� tilaukset tallennettin ker�yser��n
        if (isset($lisatyt_tilaukset) and count($lisatyt_tilaukset) > 0) {

          $otunnukset = implode(",", $lisatyt_tilaukset);
          $kerayslistatunnus = array_shift(array_keys($lisatyt_tilaukset));

          // tilaus on jo tilassa N A, p�ivitet��n nyt tilaus "ker�yslista tulostettu" eli L A
          $query = "  UPDATE lasku SET
                tila = 'L',
                lahetepvm = now(),
                kerayslista = '{$kerayslistatunnus}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus in ({$otunnukset})";
          $upd_res = pupe_query($query);
        }
      }

      // lukitaan tableja
      $query = "UNLOCK TABLES";
      $result = pupe_query($query);

      if (isset($lisatyt_tilaukset) and count($lisatyt_tilaukset) > 0) {

        $reittietikettitulostin = $gen_ker_row['printteri8'];

        // Tulostetaan kollilappu
        require('inc/tulosta_reittietiketti.inc');

        // L�hetet��n tiedot kardexiin
        require("inc/kardex_send.inc");
      }
    }
  }
