<?php

  // Kutsutaanko CLI:stä
  $php_cli = FALSE;

  if (php_sapi_name() == 'cli') {
    $php_cli = TRUE;
  }

  date_default_timezone_set('Europe/Helsinki');


  // haetaan yhtiön tiedot vain jos tätä tiedostoa kutsutaan komentoriviltä suoraan
  if ($php_cli and count(debug_backtrace()) <= 1) {

    if (trim($argv[1]) == '') {
      echo "Et antanut yhtiötä!\n";
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
    $operaattori     = (string) $argv[2];

    $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
  }

  // Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
  pupesoft_flock();

  if (trim($operaattori) == '') {
    echo "Operaattori puuttuu: unifaun_ps/unifaun_uo!\n";
    exit;
  }

  if (trim($ftpget_dest[$operaattori]) == '') {
    echo "Unifaun return-kansio puuttuu!\n";
    exit;
  }

  if (!is_dir($ftpget_dest[$operaattori])) {
    echo "Unifaun return-kansio virheellinen!\n";
    exit;
  }

  // Setataan tämä, niin ftp-get.php toimii niin kuin pitäisikin
  $argv[1] = $operaattori;

  require('ftp-get.php');

  if ($handle = opendir($ftpget_dest[$operaattori])) {

    while (($file = readdir($handle)) !== FALSE) {

      if (is_file($ftpget_dest[$operaattori]."/".$file)) {

        /*
          * pupessa tilausnumerona lähetettiin tilausnumero_ssccvanha esim.: 6215821_1025616
         */

        /* Normaalisanoma ilman viitettä
         * tilnro;sscc_ulkoinen;rahtikirjanro;datetime
         * 12345;373325380188609457;1000017762;2012-01-20 13:51:50
         */

        /* Normaalisanoma viitteen kanssa
         * tilnro;sscc_ulkoinen;rahtikirjanro;datetime;reference
         * 12345;373325380188609457;1000017762;2012-01-20 13:51:50;77777777
         */

        /* Sanomien erikoiskeissit (Itella, TNT, DPD, Matkahuolto)
         * tilnro;ensimmäinen kollitunniste on lähetysnumero;sama ensimmäinen kollitunniste on rahtikirjanumerona;timestamp
         * 199188177;MA1234567810000009586;MA1234567810000009586;2012-01-23 10:58:57 (Kimi: MAtkahuolto)
         *
         * tilnro;sscc_ulkoinen;LOGY rahtikirjanro;timestamp
         * 12345;373325380188816602;200049424052;2012-01-23 10:59:03 (Kimi: Kaukokiito, Kiitolinja ja Vr Transpoint; SSCC + LOGY-rahtikirjanumero)
         *
         *
         * 555555;JJFI65432110000070773;;2012-01-24 11:12:56; (Kimi: Itella)
         *
         *
         * 14656099734;1;GE249908410WW;2012-01-24 11:12:49;52146882 (Kimi: TNT)
         */

        list($eranumero_sscc, $sscc_ulkoinen, $rahtikirjanro, $timestamp, $viite) = explode(";", file_get_contents($ftpget_dest[$operaattori]."/".$file));

        $sscc_ulkoinen = (is_int($sscc_ulkoinen) and $sscc_ulkoinen == 1) ? '' : trim($sscc_ulkoinen);

        // Unifaun laittaa viivakoodiin kaksi etunollaa jos SSCC on numeerinen
        // Palautussanomasta etunollaat puuttuu, joten lisätään ne tässä
        if (is_numeric($sscc_ulkoinen)) {
          $sscc_ulkoinen = "00".$sscc_ulkoinen;
        }

        if ($yhtiorow['kerayserat'] == 'K') {

          list($eranumero, $sscc) = explode("_", $eranumero_sscc);

          // Jos paketilla on jo ulkoinen sscc, lähetetään discardParcel-sanoma
          $query = "  SELECT *
                FROM kerayserat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND sscc = '{$sscc}'
                AND nro = '{$eranumero}'
                AND sscc_ulkoinen != ''
                AND sscc_ulkoinen != 0
                LIMIT 1";
          $sscc_ulkoinen_chk_res = pupe_query($query);

          if (mysql_num_rows($sscc_ulkoinen_chk_res) == 1) {

            $sscc_ulkoinen_chk_row = mysql_fetch_assoc($sscc_ulkoinen_chk_res);

            require_once("inc/unifaun_send.inc");

            $query = "  SELECT lasku.toimitustavan_lahto, lasku.ytunnus, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp
                  FROM lasku
                  WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                  AND lasku.tunnus = '{$sscc_ulkoinen_chk_row['otunnus']}'";
            $toitares = pupe_query($query);
            $toitarow = mysql_fetch_assoc($toitares);

            if ($operaattori == 'unifaun_ps' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") {
              $unifaun = new Unifaun($unifaun_ps_host, $unifaun_ps_user, $unifaun_ps_pass, $unifaun_ps_path, $unifaun_ps_port, $unifaun_ps_fail, $unifaun_ps_succ);
            }
            elseif ($operaattori == 'unifaun_uo' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "") {
              $unifaun = new Unifaun($unifaun_uo_host, $unifaun_uo_user, $unifaun_uo_pass, $unifaun_uo_path, $unifaun_uo_port, $unifaun_uo_fail, $unifaun_uo_succ);
            }

            $mergeid = md5($toitarow["toimitustavan_lahto"].$toitarow["ytunnus"].$toitarow["toim_osoite"].$toitarow["toim_postino"].$toitarow["toim_postitp"]);

            $unifaun->_discardParcel($mergeid, $sscc_ulkoinen_chk_row['sscc_ulkoinen']);
            $unifaun->ftpSend();
          }

          $query = "  UPDATE kerayserat SET
                sscc_ulkoinen = '{$sscc_ulkoinen}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND sscc   = '{$sscc}'
                AND nro   = '{$eranumero}'";
          $upd_res = pupe_query($query);
        }
        else {

          $eranumero_sscc = preg_replace("/[^0-9\,]/", "", str_replace("_", ",", $eranumero_sscc));

          $query = "  UPDATE rahtikirjat SET
                sscc_ulkoinen = '{$sscc_ulkoinen}'
                WHERE yhtio   = '{$kukarow['yhtio']}'
                AND tunnus in ($eranumero_sscc)";
          $upd_res  = pupe_query($query);
        }

        rename($ftpget_dest[$operaattori]."/".$file, $ftpget_dest[$operaattori]."/ok/".$file);
      }
    }
  }
