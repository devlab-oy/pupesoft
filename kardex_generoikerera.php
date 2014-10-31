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
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $kukarow['yhtio'] = (string) $argv[1];
  $kukarow['kuka']  = 'admin';
  $kukarow['kieli'] = 'fi';

  if (trim($argv[2]) != '') {
    $kukarow['kuka'] = trim($argv[2]);
  }

  $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

  $query = "SELECT *
            FROM keraysvyohyke
            WHERE yhtio              = '{$kukarow['yhtio']}'
            AND ulkoinen_jarjestelma = 'K'";
  $gen_ker_res_result = pupe_query($query);

  while ($gen_ker_row = mysql_fetch_assoc($gen_ker_res_result)) {
    
    $erat = tee_keraysera($gen_ker_row["tunnus"], $gen_ker_row["varasto"]);

    // Ei saatu lukkoa j�rkev�ss� ajassa
    if ($erat === FALSE) {
      // echo t("VIRHE: Ker�yserien luonnissa ruuhkaa. Yrit� pian uudelleen!<br>";
      continue;
    }
    
    if (isset($erat['tilaukset']) and count($erat['tilaukset']) > 0) {
      // Tallennetaan miss� t�� er� on tehty
      $ohjelma_moduli = "KARDEX";

      // Tallennetaan ker�yser�
      require 'inc/tallenna_keraysera.inc';

      // N�m� tilaukset tallennettin ker�yser��n
      if (isset($lisatyt_tilaukset) and count($lisatyt_tilaukset) > 0) {

        $otunnukset = implode(",", $lisatyt_tilaukset);
        $lisatyt_tilaukset_keys = array_keys($lisatyt_tilaukset);
        $kerayslistatunnus = array_shift($lisatyt_tilaukset_keys);

        // tilaus on jo tilassa N A, p�ivitet��n nyt tilaus "ker�yslista tulostettu" eli L A
        $query = "UPDATE lasku SET
                  tila 	      = 'L',
                  alatila     = 'A',
                  lahetepvm   = now(),
                  kerayslista = '{$kerayslistatunnus}'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus in ({$otunnukset})
                  AND tila    = 'N'
                  AND alatila = 'KA'";
        pupe_query($query);

        if ($yhtiorow['kerayserat'] != '' and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
          // siirtolista on jo tilassa G J, p�ivitet��n nyt tilaus "siirtolista tulostettu" eli G A
          $query = "UPDATE lasku SET
                    alatila     = 'A',
                    lahetepvm   = now(),
                    kerayslista = '{$kerayslistatunnus}'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus in ({$otunnukset})
                    AND tila    = 'G'
                    AND alatila = 'KJ'";
          pupe_query($query);
        }
      }
    }

    // Vapautetaan ker�syer�n nappaamat tilaukset
    release_tee_keraysera();

    if (isset($lisatyt_tilaukset) and count($lisatyt_tilaukset) > 0) {

      $reittietikettitulostin = $gen_ker_row['printteri8'];

      // Tulostetaan kollilappu
      require 'inc/tulosta_reittietiketti.inc';

      // L�hetet��n tiedot kardexiin
      require "inc/kardex_send.inc";
    }
  }
}
