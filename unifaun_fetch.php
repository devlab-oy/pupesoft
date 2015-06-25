<?php

// Kutsutaanko CLI:st�
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');


// haetaan yhti�n tiedot vain jos t�t� tiedostoa kutsutaan komentorivilt� suoraan
if ($php_cli and count(debug_backtrace()) <= 1) {

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

  $kukarow['yhtio'] = (string) $argv[1];
  $kukarow['kuka']  = 'admin';
  $kukarow['kieli'] = 'fi';
  $operaattori     = (string) $argv[2];

  $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
}

// Sallitaan vain yksi instanssi t�st� skriptist� kerrallaan
pupesoft_flock();

/*
  * pupessa tilausnumerona l�hetettiin tilausnumero_ssccvanha esim.: 6215821_1025616
 */

/* Normaalisanoma ilman viitett�
 * tilnro;sscc_ulkoinen;rahtikirjanro;datetime
 * 12345;373325380188609457;1000017762;2012-01-20 13:51:50
 */

/* Normaalisanoma viitteen kanssa
 * tilnro;sscc_ulkoinen;rahtikirjanro;datetime;reference
 * 12345;373325380188609457;1000017762;2012-01-20 13:51:50;77777777
 */

/* Sanomien erikoiskeissit (Itella, TNT, DPD, Matkahuolto)
 * tilnro;ensimm�inen kollitunniste on l�hetysnumero;sama ensimm�inen kollitunniste on rahtikirjanumerona;timestamp
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

// Setataan t�m�, niin ftp-get.php toimii niin kuin pit�isikin
$argv[1] = $operaattori;

require 'ftp-get.php';

if ($handle = opendir($ftpget_dest[$operaattori])) {
  while (($file = readdir($handle)) !== FALSE) {
    if (is_file($ftpget_dest[$operaattori]."/".$file)) {

      $fh = fopen($ftpget_dest[$operaattori]."/".$file, "r") or die ("Tiedoston avaus ep�onnistui!");

      while ($rivi = fgets($fh)) {

        list($eranumero_sscc, $sscc_ulkoinen, $rahtikirjanro, $timestamp, $viite) = explode(";", $rivi);

        $toimitrow = array();
        $toimitrow["toimitustapa"] = FALSE;

        // Jos on mittoihin perustuvat ker�yser�t k�yt�ss�
        // tuohon kentt��n tallennetaan eranumero ja SSCC tiedot
        // eik� laskun tunnusta
        // ei siis ole j�rke� yritt�� etsi� lasku n�ill� tiedoilla
        if ($yhtiorow['kerayserat'] != 'K') {
          $query = "SELECT toimitustapa
                    FROM lasku
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$eranumero_sscc}'";
          $toimitrow = mysql_fetch_assoc(pupe_query($query));
        }

        $sscc_ulkoinen = (is_int($sscc_ulkoinen) and $sscc_ulkoinen == 1) ? '' : trim($sscc_ulkoinen);

        // Unifaun laittaa viivakoodiin kaksi etunollaa jos SSCC on numeerinen
        // Palautussanomasta etunollaat puuttuu, joten lis�t��n ne t�ss�
        // DPD:hen ei tule ylim��r�isi� nollia lis�t�.
        if (is_numeric($sscc_ulkoinen) and stripos($toimitrow["toimitustapa"], "DPD") === FALSE) {
          $sscc_ulkoinen = "00".$sscc_ulkoinen;
        }

        if ($yhtiorow['kerayserat'] == 'K') {

          list($eranumero, $sscc) = explode("_", $eranumero_sscc);

          // Jos paketilla on jo ulkoinen sscc, l�hetet��n discardParcel-sanoma
          $query = "SELECT *
                    FROM kerayserat
                    WHERE yhtio        = '{$kukarow['yhtio']}'
                    AND sscc           = '{$sscc}'
                    AND nro            = '{$eranumero}'
                    AND sscc_ulkoinen != ''
                    AND sscc_ulkoinen != 0
                    LIMIT 1";
          $sscc_ulkoinen_chk_res = pupe_query($query);

          if (mysql_num_rows($sscc_ulkoinen_chk_res) == 1) {

            $sscc_ulkoinen_chk_row = mysql_fetch_assoc($sscc_ulkoinen_chk_res);

            require_once "inc/unifaun_send.inc";

            $query = "SELECT lasku.toimitustavan_lahto, lasku.ytunnus, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp
                      FROM lasku
                      WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                      AND lasku.tunnus  = '{$sscc_ulkoinen_chk_row['otunnus']}'";
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

          $query = "UPDATE kerayserat SET
                    sscc_ulkoinen = '{$sscc_ulkoinen}'
                    WHERE yhtio   = '{$kukarow['yhtio']}'
                    AND sscc      = '{$sscc}'
                    AND nro       = '{$eranumero}'";
          $upd_res = pupe_query($query);
        }
        else {
          $eranumero_sscc = preg_replace("/[^0-9\,]/", "", str_replace("_", ",", $eranumero_sscc));

          if (!empty($eranumero_sscc)) {

            $query = "SELECT *
                      FROM toimitustapa
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND selite  = '{$toimitrow['toimitustapa']}'";
            $toimitustapa_res = pupe_query($query);
            $toimitustapa_row = mysql_fetch_assoc($toimitustapa_res);

            // koontier�tulostuksessa pikkuisen eri tavalla kuin muissa
            if ($toimitustapa_row["tulostustapa"] == 'L') {
              $_rahtiwherelisa = "AND otsikkonro = 0 and rahtikirjanro = '$eranumero_sscc'";
              $_otsikkonro = 0;
              $_pakkaustieto_tunnukset = "pakkaustieto_tunnukset = '$eranumero_sscc', ";
            }
            else {
              $_rahtiwherelisa = "AND otsikkonro = '{$eranumero_sscc}'";
              $_otsikkonro = $eranumero_sscc;
              $_pakkaustieto_tunnukset = "";
            }
            
            $query = "SELECT tunnus, rahtikirjanro, sscc_ulkoinen
                      FROM rahtikirjat
                      WHERE yhtio    = '{$kukarow['yhtio']}'
                      $_rahtiwherelisa
                      ORDER BY tunnus
                      LIMIT 1";
            $rakir_res = pupe_query($query);
            $rakir_row = mysql_fetch_assoc($rakir_res);

            if (!empty($rakir_row['tunnus'])) {

              $rakirno = trim($rakir_row['rahtikirjanro']);
              $sscculk = trim($rakir_row['sscc_ulkoinen']);

              if (!empty($rahtikirjanro) and !preg_match("/\b{$rahtikirjanro}\b/i", $rakirno)) {
                $rakirno = trim($rakirno."\n".$rahtikirjanro);
              }

              if (!empty($sscc_ulkoinen) and !preg_match("/\b{$sscc_ulkoinen}\b/i", $sscculk)) {
                $sscculk = trim($sscculk."\n".$sscc_ulkoinen);
              }

              $query = "UPDATE rahtikirjat SET
                        rahtikirjanro = '{$rakirno}',
                        sscc_ulkoinen = '{$sscculk}'
                        WHERE yhtio   = '{$kukarow['yhtio']}'
                        AND tunnus    = '{$rakir_row['tunnus']}'";
              pupe_query($query);
            }
            else {
              $query = "SELECT *
                        FROM lasku
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  = '{$eranumero_sscc}'";
              $lasku_res = pupe_query($query);
              $lasku_row = mysql_fetch_assoc($lasku_res);

              if (!empty($lasku_row['tunnus'])) {

                if (empty($rahtikirjanro)) {
                  $rahtikirjanro = $sscc_ulkoinen;
                }

                $query  = "INSERT INTO rahtikirjat SET
                           kollit         = '1',
                           kilot          = '1',
                           pakkaus        = '',
                           pakkauskuvaus  = '',
                           rahtikirjanro  = '$rahtikirjanro',
                           sscc_ulkoinen  = '$sscc_ulkoinen',
                           otsikkonro     = $_otsikkonro,
                           $_pakkaustieto_tunnukset
                           tulostuspaikka = '{$lasku_row['varasto']}',
                           toimitustapa   = '{$lasku_row['toimitustapa']}',
                           tulostettu     = now(),
                           yhtio          = '{$kukarow['yhtio']}',
                           viesti         = ''";
                pupe_query($query);
              }
            }
          }
        }
      }

      rename($ftpget_dest[$operaattori]."/".$file, $ftpget_dest[$operaattori]."/ok/".$file);

      // Logitetaan ajo
      cron_log($ftpget_dest[$operaattori]."/ok/".$file);
    }
  }
}
