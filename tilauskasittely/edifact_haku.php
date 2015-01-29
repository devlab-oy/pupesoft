<?php

//require '../inc/edifact_functions.inc';

require "../inc/parametrit.inc";

if ($task == 'input') {

  $sanoma = str_replace("#@#", "'", $sanoma);

  if (strpos($sanoma, "DESADV") == true) {
    kasittele_rahtikirjasanoma($sanoma);
  }
  elseif (strpos($sanoma, "IFTSTA") == true) {
    kasittele_iftsta($sanoma);
  }
  elseif (strpos($sanoma, "IFTMBF") == true) {
    kasittele_bookkaussanoma($sanoma);
  }

}


if ($task == 'nollaa') {
/*

  $taulut = array(
      "tilausrivi",
      "tilausrivin_lisatiedot",
      "lasku",
      "laskun_lisatiedot",
      "sarjanumeroseuranta",
      "liitetiedostot");

  foreach ($taulut as $taulu) {
    $query = "TRUNCATE TABLE {$taulu}";
    pupe_query($query);
  }



  */
}



  echo "
  <font class='head'>".t("Testaus")."</font>


  <br><br>

  <form action='' method='post'>
    <input type='hidden' name='task' value='input' />

    <textarea name='sanoma'></textarea>

    <input type='submit' value='".t("Lue sanoma")."'>
  </form>

  <br><br>

  <form action='' method='post'>
    <input type='hidden' name='task' value='nollaa' />
    <input type='submit' value='".t("Nollaa tilanne")."'>
  </form>


  ";


require "inc/footer.inc";






function kasittele_rahtikirjasanoma($edi_data) {
  global $kukarow, $yhtiorow;

  $edi_data = str_replace("\n", "", $edi_data);

  // otetaan talteen liitetiedoston lisäämistä varten
  $filesize = strlen($edi_data);
  $liitedata = mysql_real_escape_string($edi_data);

  $edi_data = explode("'", $edi_data);

  $rivimaara = count($edi_data);

  // luetaan kaikki rivit
  foreach ($edi_data as $rivi => $value) {

    if (substr($value, 0, 3) == 'UNB') {

      $osat = explode("+", $value);

      $lahettaja_id_info = $osat[2];
      $lahettaja_id_info_osat = explode(":", $lahettaja_id_info);
      $lahettaja_id = $lahettaja_id_info_osat[0];

      $vastaanottaja_id_info = $osat[3];
      $vastaanottaja_id_info_osat = explode(":", $vastaanottaja_id_info);
      $vastaanottaja_id = $vastaanottaja_id_info_osat[0];

      $sanoma_id = $osat[5];

      $valmis = false;
      $luetaan = $rivi;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 3) == "BGM") {
          $osat = explode("+", $edi_data[$luetaan]);
          $rahtikirja_id = $osat[2];
          $tyyppi = $osat[3];
        }

        if (substr($edi_data[$luetaan], 0, 6) == "NAD+FX") {
          $osat = explode("+", $edi_data[$luetaan]);
          $vastaanottaja_info = $osat[2];
          $vastaanottaja_info_osat = explode(":", $vastaanottaja_info);
          $vastaanottaja = $vastaanottaja_info_osat[0];
        }

        if (substr($edi_data[$luetaan], 0, 6) == "NAD+CZ") {
          $osat = explode("+", $edi_data[$luetaan]);
          $lahettaja = $osat[4];
        }

        if (substr($edi_data[$luetaan], 0, 3) == "TDT") {
          $osat = explode("+", $edi_data[$luetaan]);
          $kuljettaja_info = $osat[5];
          $kuljettaja_info_osat = explode(":", $kuljettaja_info);
          $kuljettaja = $kuljettaja_info_osat[3];
          $rekno = $osat[8];
        }

        /*
        if (substr($edi_data[$luetaan], 0, 3) == "EQD+RR") {
          $osat = explode("+", $edi_data[$luetaan]);
          $kuljettaja_info = $osat[5];
          $kuljettaja_info_osat = explode(":", $kuljettaja_info);
          $kuljettaja = $kuljettaja_info_osat[3];
          $rekno = $osat[8];
        }

        */

        if (substr($edi_data[$luetaan], 0, 5) == "LOC+8") {
          $osat = explode("+", $edi_data[$luetaan]);
          $paamaara_info = $osat[2];
          $paamaara_info_osat = explode(":", $paamaara_info);
          $paamaara = $paamaara_info_osat[3];

          /*
          // haetaan varaston tiedot
          $query = "SELECT tunnus
                    FROM varastopaikat
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND locate(nimitys, '{$paamaara}') > 0
                    LIMIT 1";
          $varastores = pupe_query($query);
          $varasto_id = mysql_result($varastores,0);
          */
          $varasto_id = 101;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "DTM+132") {
          $osat = explode("+", $edi_data[$luetaan]);
          $toimitusaika_info = $osat[1];
          $toimitusaika_info_osat = explode(":", $toimitusaika_info);
          $toimitusaika = $toimitusaika_info_osat[1];
          $vuosi = substr($toimitusaika, 0,4);
          $kuu = substr($toimitusaika, 4,2);
          $paiva = substr($toimitusaika, 6,2);
          $toimitusaika = $vuosi.'-'.$kuu.'-'.$paiva;
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "CPS+MOL" or $luetaan >= $rivimaara) {
          $valmis = true;
        }
      }

      $rahti = array(
        'sanoma_id' => $sanoma_id,
        'rahtikirja_id' => $rahtikirja_id,
        'tyyppi' => $tyyppi,
        'sender_id' => $lahettaja_id,
        'recipient_id' => $vastaanottaja_id,
        'vastaanottaja' => $vastaanottaja,
        'lahettaja' => $lahettaja,
        'kuljettaja' => $kuljettaja,
        'rekisterinumero' => $rekno,
        'paamaara' => $paamaara,
        'varasto_id' => $varasto_id,
        'toimitusaika' => $toimitusaika
        );
    }

    if (substr($value, 0, 7) == 'CPS+MOL') {

      $valmis = false;
      $luetaan = $rivi;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 15) == "MEA+AAE+AAL+KGM") {
          $osat = explode("+", $edi_data[$luetaan]);
          $paino_info = $osat[3];
          $paino_info_osat = explode(":", $paino_info);
          $_paino = $paino_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 6) == "RFF+CU") {
          $osat = explode("+", $edi_data[$luetaan]);
          $tilaus_info = $osat[1];
          $tilaus_info_osat = explode(":", $tilaus_info);
          $_tilausnro = $tilaus_info_osat[1];
          $_rivi = $tilaus_info_osat[2];
        }

        if (substr($edi_data[$luetaan], 0, 7) == "RFF+VON") {
          $osat = explode("+", $edi_data[$luetaan]);
          $matka_info = $osat[1];
          $matka_info_osat = explode(":", $matka_info);
          $_matkakoodi = $matka_info_osat[1];

          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "CPS+PKG" or $luetaan >= $rivimaara) {

    if ($_matkakoodi === NULL) {
            $_matkakoodi = 'bookkaukseton';
          }

          $valmis = true;
        }
      }
    }

    if (substr($value, 0, 7) == 'CPS+PKG') {

      $valmis = false;
      $luetaan = $rivi;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 15) == "MEA+AAE+AAL+KGM") {
          $osat = explode("+", $edi_data[$luetaan]);
          $paino_info = $osat[3];
          $paino_info_osat = explode(":", $paino_info);
          $paino = $paino_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 14) == "MEA+AAE+DI+MMT") {
          $osat = explode("+", $edi_data[$luetaan]);
          $halkaisija_info = $osat[3];
          $halkaisija_info_osat = explode(":", $halkaisija_info);
          $halkaisija = $halkaisija_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 14) == "MEA+AAE+WD+MMT") {
          $osat = explode("+", $edi_data[$luetaan]);
          $leveys_info = $osat[3];
          $leveys_info_osat = explode(":", $leveys_info);
          $leveys = $leveys_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 7) == "GIN+ZUN") {
          $osat = explode("+", $edi_data[$luetaan]);
          $sarjanumero = $osat[2];
        }

        if (substr($edi_data[$luetaan], 0, 7) == "GIN+ZPI") {
          $osat = explode("+", $edi_data[$luetaan]);
          $juoksu = $osat[2];
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "CPS+PKG" or $luetaan >= $rivimaara) {
          $valmis = true;
        }
      }

      $tuoteno = '123';

      $rullat[] = array(
        'paino' => $paino,
        'halkaisija' => $halkaisija,
        'leveys' => $leveys,
        'tuoteno' => $tuoteno,
        'juoksu' => $juoksu,
        'sarjanumero' => $sarjanumero,
        'matkakoodi' => $_matkakoodi,
        'tilausnro' => $_tilausnro,
        'rivinro' => $_rivi,
        'tilauksen_paino' => $_paino
        );

    }
  }// rivit luettu

  $rahti['rullat'] = $rullat;

  $data = $rahti;



  foreach ($data['rullat'] as $rulla) {


    $query = "SELECT tunnus
            FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
            WHERE yhtio     = '{$kukarow['yhtio']}'
            AND sarjanumero = '{$rulla['sarjanumero']}'
            AND myyntirivitunnus != 0";
    $sarjares = pupe_query($query);

    if(mysql_num_rows($sarjares) > 0) {
    continue;
    }

    // haetaan tuotteen tiedot
    $query = "SELECT *
          FROM tuote
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tuoteno = '{$rulla['tuoteno']}'";
    $tuoteres = pupe_query($query);

    $trow = mysql_fetch_assoc($tuoteres);
    $kpl = 1;
    $kerayspvm = $toimaika = $data['toimitusaika'];
    $toim = '';
    $hinta = 0;
    $var = '';
    $kutsuja = '';
    $kpl2 = 0;
    $toimittajan_tunnus = '';

        $query = "SELECT * from lasku
                  where asiakkaan_tilausnumero = '{$rulla['tilausnro']}'
                  AND lasku.tilaustyyppi = 'N'";
        $result = pupe_query($query);
        $laskurow = mysqL_fetch_assoc($result);

    if (!$laskurow and $rulla['matkakoodi'] == "bookkaukseton") {

      $kukarow['kesken'] = 0;

      require_once "tilauskasittely/luo_myyntitilausotsikko.inc";

      $tunnus = luo_myyntitilausotsikko('RIVISYOTTO', 106);

      $update_query = "UPDATE lasku SET
                         asiakkaan_tilausnumero = '{$rulla['tilausnro']}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tunnus = '{$tunnus}'";
      pupe_query($update_query);

      $update_query = "UPDATE laskun_lisatiedot SET
                         konttiviite = 'bookkaukseton',
                         matkakoodi = 'bookkaukseton'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND otunnus = '{$tunnus}'";
      pupe_query($update_query);

      $laskuquery = "SELECT *
                       FROM lasku
                       WHERE yhtio = '{$kukarow['yhtio']}'
                       AND tunnus = '{$tunnus}'";
      $laskuresult = pupe_query($laskuquery);
      $laskurow = mysql_fetch_assoc($laskuresult, 0);

      }

        if ($laskurow) {

      $kukarow['kesken'] = $laskurow['tunnus'];

      require "tilauskasittely/lisaarivi.inc";

      $update_query = "UPDATE tilausrivin_lisatiedot SET
                           juoksu = '{$rulla['juoksu']}',
                           tilauksen_paino = '{$rulla['tilauksen_paino']}',
                           asiakkaan_tilausnumero = '{$rulla['tilausnro']}',
                           asiakkaan_rivinumero = '{$rulla['rivinro']}'
                           WHERE yhtio = '{$kukarow['yhtio']}'
                           AND tilausrivitunnus = '{$lisatty_tun}'";
      pupe_query($update_query);

      $update_query = "UPDATE tilausrivi
                           SET var2 = 'OK'
                           WHERE yhtio = '{$kukarow['yhtio']}'
                           AND tunnus = '{$lisatty_tun}'";
      pupe_query($update_query);

      $update_query = "UPDATE sarjanumeroseuranta
                           SET myyntirivitunnus = '{$lisatty_tun}'
                           WHERE yhtio = '{$kukarow['yhtio']}'
                           AND sarjanumero = '{$rulla['sarjanumero']}'";
      pupe_query($update_query);
        }
    }


  $query = "UPDATE kuka
            SET kesken = 0
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND kuka = '{$kukarow['kuka']}'";
  pupe_query($query);

}

