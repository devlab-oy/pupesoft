<?php

  // Kutsutaanko CLI:st‰
  $php_cli = FALSE;

  if (php_sapi_name() == 'cli') {
    $php_cli = TRUE;
  }

  date_default_timezone_set('Europe/Helsinki');

  /* DATA IMPORT CRON LOOP */
  /* Ajetaan cronista ja t‰m‰ sis‰‰nlukee luedata-tiedostot datain hakemistosta */

  // Kutsutaanko CLI:st‰
  if (!$php_cli) {
    die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
  }

  // Laitetaan unlimited max time
  ini_set("max_execution_time", 0);

  $pupe_root_polku = dirname(__FILE__);
  require ("{$pupe_root_polku}/inc/connect.inc");
  require ("{$pupe_root_polku}/inc/functions.inc");
  
  $lock_params = array(
      "locktime" => 900,
  );
  
  // Sallitaan vain yksi instanssi t‰st‰ skriptist‰ kerrallaan
  pupesoft_flock($lock_params);

  function data_import () {
    GLOBAL $pupe_root_polku;

    $faileja_kasitelty = FALSE;

    // Loopataan DATAIN -hakemisto l‰pi
    if ($files = scandir($pupe_root_polku."/datain")) {
        foreach ($files as $file) {

        // Etsit‰‰n "lue-data#" -alkuisia filej‰, jotka loppuu ".DATAIMPORT"
        if (substr($file, 0, 9) == "lue-data#" and substr($file, -11) == ".DATAIMPORT") {

          // Filename on muotoa: lue-data#username#yhtio#taulu#randombit#jarjestys.DATAIMPORT
          // Filename on muotoa: lue-data#username#yhtio#taulu#randombit#alkuperainen_filename#jarjestys.DATAIMPORT
          $filen_tiedot = explode("#", $file);

          // Ei k‰sitell‰ jos filename ei ole oikeaa muotoa
          if (count($filen_tiedot) == 7) {

            $kuka     = $filen_tiedot[1];
            $yhtio     = $filen_tiedot[2];
            $taulu     = $filen_tiedot[3];
            $random   = $filen_tiedot[4];
            $orig_file   = $filen_tiedot[5];
            $jarjestys   = $filen_tiedot[6];

            // Logfile on muotoa: lue-data#username#yhtio#taulu#randombit#jarjestys.LOG
            $logfile = "lue-data#{$kuka}#{$yhtio}#{$taulu}#{$random}#{$orig_file}#{$jarjestys}.LOG";

            // Errorfile on muotoa: lue-data#username#yhtio#taulu#randombit#jarjestys.ERR
            $errfile = "lue-data#{$kuka}#{$yhtio}#{$taulu}#{$random}#{$orig_file}#{$jarjestys}.ERR";

            // Ajetaan lue_data t‰lle tiedostolle
            passthru("/usr/bin/php $pupe_root_polku/lue_data.php ".escapeshellarg($yhtio)." ".escapeshellarg($taulu)." ".escapeshellarg($pupe_root_polku."/datain/".$file)." ".escapeshellarg($pupe_root_polku."/datain/".$logfile)." ".escapeshellarg($pupe_root_polku."/datain/".$errfile));

            // Siirret‰‰n file k‰sitellyksi
            rename($pupe_root_polku."/datain/".$file, $pupe_root_polku."/datain/".$file.".DONE");

            $faileja_kasitelty = TRUE;
          }
        }
        }
    }

    // Katsotaan oisko tullut lis‰‰ k‰sitelt‰vi‰
    if ($faileja_kasitelty) {
      data_import();
    }
  }

  // Aloitetaan sis‰‰nluku
  data_import();
