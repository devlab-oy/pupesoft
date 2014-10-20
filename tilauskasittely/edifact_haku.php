<?php

require "../inc/parametrit.inc";

if ($task == 'hae') {

  $host = $ftp_info['host'];
  $user = $ftp_info['user'];
  $pass = $ftp_info['pass'];


  // Connect to host
  $yhteys = ftp_connect($host);

  // Open a session to an external ftp site
  $login = ftp_login($yhteys, $user, $pass);

  // Check open
  if ((!$yhteys) || (!$login)) {
    echo t("Ftp-yhteyden muodostus epaonnistui! Tarkista salasanat."); die;
  }
  else {
    echo t("Ftp-yhteys muodostettu.")."<br/>";
  }

  ftp_chdir($yhteys, 'out-test');

  ftp_pasv($yhteys, true);

  $files = ftp_nlist($yhteys, ".");

  foreach ($files as $file) {

    if (ftp_mdtm($yhteys, $file) > 1412772935) {

      if (substr($file, -3) == 'IFF') {
        $bookkaukset[] = $file;
      }

      if (substr($file, -3) == 'DAD') {
        $rahtikirjat[] = $file;
      }
    }
  }



  foreach ($bookkaukset as $bookkaus) {
    $temp_file = tempnam("/tmp", "IFF-");
    ftp_get($yhteys, $temp_file, $bookkaus, FTP_ASCII);
    $edi_data = file_get_contents($temp_file);
    kasittele_bookkaussanoma($edi_data);
    unlink($temp_file);
  }


  foreach ($rahtikirjat as $rahtikirja) {
    $temp_file = tempnam("/tmp", "DAD-");
    ftp_get($yhteys, $temp_file, $rahtikirja, FTP_ASCII);
    $edi_data = file_get_contents($temp_file);
    kasittele_rahtikirjasanoma($edi_data);
    unlink($temp_file);
  }



  // suljetaan yhteys
  ftp_close($yhteys);


}
else{

  echo "
  <font class='head'>".t("Sanomien haku")."</font>
  <form action='' method='post'>
    <input type='hidden' name='task' value='hae' />
    <input type='submit' value='".t("Hae sanomat")."'>
  </form>";

}

require "inc/footer.inc";

function kasittele_bookkaussanoma($edi_data) {
  global $kukarow;

  $edi_data = str_replace("\n", "", $edi_data);
  $liitedata = $edi_data;
  $edi_data = str_replace("?'", "#%#", $edi_data);
  $edi_data = explode("'", $edi_data);

  $kuorma = array();
  $pakkaukset = array();
  $tilaukset = array();

  foreach ($edi_data as $rivi) {

    trim($rivi);

    $rivi = str_replace("#%#", "'", $rivi);

    if (substr($rivi, 0, 3) == 'UNB') {
      $osat = explode("+", $rivi);

      /* näillä ei nyt olekaan vielä käyttöä
      $vastaanottaja_ovt_info = $osat[3];
      $vastaanottaja_ovt_info_osat = explode(":", $vastaanottaja_ovt_info);
      $vastaanottaja_ovt = $vastaanottaja_ovt_info_osat[0];

      $lahettaja_ovt_info = $osat[2];
      $lahettaja_ovt_info_osat = explode(":", $lahettaja_ovt_info);
      $lahettaja_ovt = $lahettaja_ovt_info_osat[0];
      */

      $sanoma_id = $osat[5];
    }

    // katsotaan onko viesti alkuperäinen vai korvaava (9 vai 5)
    // tulee ehkä olemaan oleellinen tieto
    if (substr($rivi, 0, 3) == 'BGM') {
      $osat = explode("+", $rivi);
      $matkakoodi = $osat[2];
      $tyyppi = $osat[3];
    }

    if (substr($rivi, 0, 7) == 'RFF+VON' and !isset($konttiviite)) {
      $osat = explode("+", $rivi);
      $konttiviite_info = $osat[1];
      $konttiviite_info_osat = explode(":", $konttiviite_info);
      $konttiviite = $konttiviite_info_osat[1];
    }

    if (substr($rivi, 0, 6) == "RFF+CU" and !isset($tilausnro)) {
      $osat = explode("+", $rivi);
      $tilaus_info = $osat[1];
      $tilaus_info_osat = explode(":", $tilaus_info);
      $tilausnro = $tilaus_info_osat[1];
      $rivinro = $tilaus_info_osat[2];
    }

    if (substr($rivi, 0, 7) == "FTX+TRA" and !isset($ohje)) {
      $osat = explode("+", $rivi);
      $ohje = $osat[4];
    }

    if (substr($rivi, 0, 6) == 'EQD+CN') {
      $osat = explode("+", $rivi);
      $konttityyppi = $osat[3];
    }

    if (substr($rivi, 0, 3) == 'EQN') {
      $osat = explode("+", $rivi);
      $konttimaara = $osat[1];
    }

  }

  // tässä vaiheessa vastaanottaja on aina steveco
  $asiakas_id = 106;

  // tarkistetaan onko tämä sanoma jostakin syystä jo käsitelty
  $query = "SELECT tunnus
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND filename = '{$sanoma_id}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 0) {
    return false;
  }

  // tarkistetaan onko vastaava sanoma jo liitetiedostona
  $query = "SELECT tunnus
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND selite = '{$tilausnro}:{$rivinro}'
            AND kayttotarkoitus = 'bookkaussanoma'";
  $vastaavuusresult = pupe_query($query);
  $osumia = mysql_num_rows($vastaavuusresult);

  // katsotaan onko tilauksesta luotu jo osto- tai myyntitilaus
  $query = "SELECT group_concat(tila)
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND asiakkaan_tilausnumero = '{$tilausnro}'
            GROUP BY yhtio";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $tilat = mysql_result($result, 0);
  }
  else {
    $tilat = '';
  }

  $ostotilaus = strpos($tilat, "O") === false ? false : true;
  $myyntitilaus = strpos($tilat, "N") === false ? false : true;

  $kukarow['kesken'] = 0;

  if (!$myyntitilaus) {

    require_once "../tilauskasittely/luo_myyntitilausotsikko.inc";
    $tunnus = luo_myyntitilausotsikko('RIVISYOTTO', $asiakas_id);

    $update_query = "UPDATE lasku SET
                     asiakkaan_tilausnumero = '{$tilausnro}',
                     sisviesti1 = '{$ohje}'
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus = '{$tunnus}'";
    pupe_query($update_query);

    $update_query = "UPDATE laskun_lisatiedot SET
                     konttiviite  = '{$konttiviite}',
                     konttimaara  = '{$konttimaara}',
                     konttityyppi = '{$konttityyppi}',
                     matkakoodi   = '{$matkakoodi}'
                     WHERE yhtio  = '{$kukarow['yhtio']}'
                     AND otunnus  = '{$tunnus}'";
    pupe_query($update_query);

    if ($ostotilaus) {

      $query = "SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$tunnus}'";
      $result = pupe_query($query);
      $laskurow = mysql_fetch_assoc($result, 0);

      // otetaan ostotilauksen tunnus
      $ostoquery = "SELECT tunnus
                    FROM lasku
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND asiakkaan_tilausnumero = '{$tilausnro}'
                    AND tila = 'O'";
      $ostoresult = pupe_query($ostoquery);
      $ostotunnus = mysql_result($ostoresult, 0);

      // haetaan ostotilauksen rivit
      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$ostotunnus}'";
      $result = pupe_query($query);

      $kukarow['kesken'] = $laskurow['tunnus'];

      // haetaan tuotteen tiedot
      $query = "SELECT *
                FROM tuote
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '123'";
      $tuoteres = pupe_query($query);
      $trow = mysql_fetch_assoc($tuoteres);
      $kpl = 1;
      $var = 'P';

      while ($tilausrivi = mysql_fetch_assoc($result)) {
        $ostorivit[] = $tilausrivi;
      }

      foreach ($ostorivit as  $ostorivi) {

        require "lisaarivi.inc";

        $update_query = "UPDATE tilausrivi
                         SET var2 = 'OK'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tunnus = '{$lisatty_tun}'";
        pupe_query($update_query);

        $update_query = "UPDATE sarjanumeroseuranta
                         SET myyntirivitunnus = '{$lisatty_tun}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND ostorivitunnus = '{$ostorivi['tunnus']}'";
        pupe_query($update_query);

        // haetaan rivin lisätiedot
        $query = "SELECT *
                  FROM tilausrivin_lisatiedot
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tilausrivitunnus = '{$ostorivi['tunnus']}'";
        $result = pupe_query($query);
        $lisatiedot = mysql_fetch_assoc($result);

        $update_query = "UPDATE tilausrivin_lisatiedot SET
                         juoksu = '{$lisatiedot['juoksu']}',
                         tilauksen_paino = '{$lisatiedot['tilauksen_paino']}',
                         asiakkaan_rivinumero = '{$lisatiedot['asiakkaan_rivinumero']}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tilausrivitunnus = '{$lisatty_tun}'";
        pupe_query($update_query);

      }
    }
  }
  else {
    $query = "SELECT tunnus
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND asiakkaan_tilausnumero = '{$tilausnro}'
              AND tila = 'N'";
    $result = pupe_query($query);
    $tunnus = mysql_result($result, 0);
  }

  $filesize = strlen($liitedata);
  $liitedata = mysql_real_escape_string($liitedata);

  if ($osumia == 0) {

    $query = "INSERT INTO liitetiedostot SET
              yhtio           = '{$kukarow['yhtio']}',
              liitos          = 'lasku',
              liitostunnus    = '$tunnus',
              selite          = '{$tilausnro}:{$rivinro}',
              laatija         = '{$kukarow['kuka']}',
              luontiaika      = NOW(),
              data            = '{$liitedata}',
              filename        = '{$sanoma_id}',
              filesize        = '$filesize',
              filetype        = 'text/plain',
              kayttotarkoitus = 'bookkaussanoma'";
    pupe_query($query);

  }
  elseif ($tyyppi == 5){

    $korvattava = mysql_result($vastaavuusresult, 0);

    $query = "UPDATE liitetiedostot SET
              data        = '$liitedata',
              muutospvm   = NOW(),
              muuttaja    = '{$kukarow['kuka']}',
              filename    = '{$sanoma_id}',
              filesize    = '$filesize'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND  tunnus = '{$korvattava}'";
    pupe_query($query);

  }
}


function kasittele_rahtikirjasanoma($edi_data) {
  global $kukarow;

  $edi_data = str_replace("\n", "", $edi_data);

  // otetaan talteen liitetiedoston lisäämistä varten
  $filesize = strlen($edi_data);
  $liitedata = mysql_real_escape_string($edi_data);

  $edi_data = explode("'", $edi_data);

  $kuorma = array();
  $pakkaukset = array();
  $tilaukset = array();
  $rivimaara = count($edi_data);

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
          $kuormakirja_id = $osat[2];
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

        if (substr($edi_data[$luetaan], 0, 5) == "LOC+8") {
          $osat = explode("+", $edi_data[$luetaan]);
          $paamaara_info = $osat[2];
          $paamaara_info_osat = explode(":", $paamaara_info);
          $paamaara = $paamaara_info_osat[3];

          // haetaan varaston tiedot
          $query = "SELECT tunnus
                    FROM varastopaikat
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND locate(nimitys, '{$paamaara}') > 0
                    LIMIT 1";
          $varastores = pupe_query($query);
          $varasto_id = mysql_result($varastores,0);
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

      $kuorma[$kuormakirja_id]= array(
        'sanoma_id' => $sanoma_id,
        'kuorma_id' => $kuormakirja_id,
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

        if (substr($edi_data[$luetaan], 0, 3) == "PAC") {
          $osat = explode("+", $edi_data[$luetaan]);
          $tilaukset[$rivi]['kpl'] = $osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 15) == "MEA+AAE+AAL+KGM") {
          $osat = explode("+", $edi_data[$luetaan]);
          $paino_info = $osat[3];
          $paino_info_osat = explode(":", $paino_info);
          $tilaukset[$rivi]['paino'] = $paino_info_osat[1];
        }

        if (substr($edi_data[$luetaan], 0, 6) == "NAD+OS") {
          $osat = explode("+", $edi_data[$luetaan]);
          $tilaukset[$rivi]['lahettaja'] = $osat[4];
        }

        if (substr($edi_data[$luetaan], 0, 6) == "RFF+CU") {
          $osat = explode("+", $edi_data[$luetaan]);
          $tilaus_info = $osat[1];
          $tilaus_info_osat = explode(":", $tilaus_info);
          $tilaukset[$rivi]['tilausnro'] = $tilaus_info_osat[1];
          $_rivi = $tilaukset[$rivi]['rivinro'] = $tilaus_info_osat[2];
          $valmis = true;
        }

        if (substr($edi_data[$luetaan], 0, 7) == "CPS+PKG" or $luetaan >= $rivimaara) {
          $valmis = true;
        }

      }
      $pakkaukset = array();
    }

    if (substr($value, 0, 7) == 'CPS+PKG') {

      end($tilaukset);
      $tilaus_id = key($tilaukset);

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

      $pakkaukset[$rivi] = array(
        'paino' => $paino,
        'halkaisija' => $halkaisija,
        'leveys' => $leveys,
        'tuoteno' => $tuoteno,
        'juoksu' => $juoksu,
        'sarjanumero' => $sarjanumero,
        'rivinro' => $_rivi
        );

      $tilaukset[$tilaus_id]['pakkaukset'] = $pakkaukset;
    }
  }

  $kuorma[$kuormakirja_id]['tilaukset'] = $tilaukset;

  $data = $kuorma;
  $data = $data[key($data)];

  foreach ($data['tilaukset'] as $key => $tilaus) {

    // katsotaan onko tilauksesta luotu jo osto- tai myyntitilaus
    $query = "SELECT group_concat(tila)
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND asiakkaan_tilausnumero = '{$tilaus['tilausnro']}'
              GROUP BY yhtio";
    $result = pupe_query($query);
    $tilat = mysql_result($result, 0);

    $ostotilaus = strpos($tilat, "O") === false ? false : true;
    $myyntitilaus = strpos($tilat, "N") === false ? false : true;

    if ($ostotilaus) {
      // osa tilauksesta on tullut aiemmassa rahtikirjassa tai duplikaattisanoma

      $query = "SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND asiakkaan_tilausnumero = '{$tilaus['tilausnro']}'";
      $result = pupe_query($query);
      $laskurow = mysql_fetch_assoc($result);

    }
    else {
      // ei ostotilausta

      require_once "../inc/luo_ostotilausotsikko.inc";

      // haetaan toimittajan tiedot
      $query = "SELECT *
                FROM toimi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND REPLACE(nimi, ' ', '') = REPLACE('{$data['lahettaja']}', ' ', '')";
      $toimres = pupe_query($query);
      $toimrow = mysql_fetch_assoc($toimres);

      $params = array(
        'liitostunnus' => $toimrow['tunnus'],
        'nimi' => $toimrow['nimi'],
        'myytil_toimaika' => $data['toimitusaika'],
        'varasto' => $data['varasto_id'],
        'osoite' => $toimrow['osoite'],
        'postino' => $toimrow['postino'],
        'postitp' => $toimrow['postitp'],
        'maa' => $toimrow['maa'],
        'edi' => 'X'
      );

      $laskurow = luo_ostotilausotsikko($params);

      $update_query = "UPDATE lasku SET
                       asiakkaan_tilausnumero = '{$tilaus['tilausnro']}'
                       WHERE yhtio = '{$kukarow['yhtio']}'
                       AND tunnus = '{$laskurow['tunnus']}'";
      pupe_query($update_query);

    }

    // tarkistetaan onko vastaava sanoma jo liitetiedostona
    $query = "SELECT tunnus
              FROM liitetiedostot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND CONCAT(liitostunnus,selite) = '{$laskurow['tunnus']}{$tilaus['tilausnro']}'
              AND kayttotarkoitus = 'rahtikirjasanoma'";
    $vastaavuusresult = pupe_query($query);

    if (mysql_num_rows($vastaavuusresult) == 0) {

      $query = "INSERT INTO liitetiedostot set
                yhtio           = '{$kukarow['yhtio']}',
                liitos          = 'lasku',
                selite          = '{$tilaus['tilausnro']}',
                liitostunnus    = '{$laskurow['tunnus']}',
                laatija         = '{$kukarow['kuka']}',
                luontiaika      = now(),
                data            = '$liitedata',
                filename        = '{$data['sanoma_id']}',
                filesize        = '{$filesize}',
                filetype        = 'text/plain',
                kayttotarkoitus = 'rahtikirjasanoma'";
      pupe_query($query);

    }
    elseif ($data['tyyppi'] == 5) {

      $korvattava = mysql_result($vastaavuusresult, 0);

      $query = "UPDATE liitetiedostot SET
                data        = '$liitedata',
                muutospvm   = NOW(),
                muuttaja    = '{$kukarow['kuka']}',
                filename    = '{$data['sanoma_id']}',
                filesize    = '{$filesize}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND  tunnus = '{$korvattava}'";
      pupe_query($query);

    }

    $kukarow['kesken'] = $laskurow['tunnus'];



    foreach ($tilaus['pakkaukset'] as $key => $pakkaus) {

      $query = "SELECT tunnus
                FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND sarjanumero = '{$pakkaus['sarjanumero']}'";
      $sarjares = pupe_query($query);

      if (mysql_num_rows($sarjares) == 0) {

        // haetaan tuotteen tiedot
        $query = "SELECT *
                  FROM tuote
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$pakkaus['tuoteno']}'";
        $tuoteres = pupe_query($query);

        $trow = mysql_fetch_assoc($tuoteres);
        $kpl = 1;
        $kerayspvm = $toimaika = $data['toimitusaika'];

        require "lisaarivi.inc";

        $update_query = "UPDATE tilausrivin_lisatiedot SET
                         rahtikirja_id = '{$data['kuorma_id']}',
                         juoksu = '{$pakkaus['juoksu']}',
                         tilauksen_paino = '{$tilaus['paino']}',
                         kuljetuksen_rekno = '{$data['rekisterinumero']}',
                         asiakkaan_rivinumero = '{$pakkaus['rivinro']}'
                         WHERE yhtio = '{$kukarow['yhtio']}'
                         AND tilausrivitunnus = '{$lisatty_tun}'";
        pupe_query($update_query);

        $query = "SELECT *
                  FROM tilausrivi
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus = '{$lisatty_tun}'";
        $rivires = pupe_query($query);
        $rivirow = mysql_fetch_assoc($rivires);


        $query = "INSERT INTO sarjanumeroseuranta SET
                  yhtio           = '{$kukarow['yhtio']}',
                  tuoteno         = '{$rivirow['tuoteno']}',
                  sarjanumero     = '{$pakkaus['sarjanumero']}',
                  massa           = '{$pakkaus['paino']}',
                  leveys          = '{$pakkaus['leveys']}',
                  halkaisija      = '{$pakkaus['halkaisija']}',
                  ostorivitunnus  = '{$lisatty_tun}',
                  era_kpl         = '1',
                  laatija         = '{$kukarow['kuka']}',
                  luontiaika      = NOW()";
        pupe_query($query);

      }
    }

    $kukarow['kesken'] = 0;

    if ($myyntitilaus) {
      // bookkaussanoma tullut ennen rahtikirjaa

      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$laskurow['tunnus']}'";
      $oresult = pupe_query($query);

      $query = "SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND asiakkaan_tilausnumero = '{$tilaus['tilausnro']}'
                AND tilaustyyppi = 'N'";
      $result = pupe_query($query);
      $laskurow = mysql_fetch_assoc($result);

      $kukarow['kesken'] = $laskurow['tunnus'];

      while ($tilausrivi = mysql_fetch_assoc($oresult)) {

        $query = "SELECT tunnus
                  FROM sarjanumeroseuranta
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND ostorivitunnus = '{$tilausrivi['tunnus']}'
                  AND myyntirivitunnus = 0";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 1) {

          // haetaan tuotteen tiedot
          $query = "SELECT *
                    FROM tuote
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tuoteno = '{$tilausrivi['tuoteno']}'";
          $tuoteres = pupe_query($query);

          $trow = mysql_fetch_assoc($tuoteres);
          $kpl = 1;
          $var = 'P';

          require "lisaarivi.inc";

          // haetaan rivin lisätiedot
          $query = "SELECT *
                    FROM tilausrivin_lisatiedot
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tilausrivitunnus = '{$tilausrivi['tunnus']}'";
          $result = pupe_query($query);
          $lisatiedot = mysql_fetch_assoc($result);

          $update_query = "UPDATE tilausrivin_lisatiedot SET
                           juoksu = '{$lisatiedot['juoksu']}',
                           tilauksen_paino = '{$lisatiedot['tilauksen_paino']}',
                           asiakkaan_rivinumero = '{$lisatiedot['asiakkaan_rivinumero']}'
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
                           AND ostorivitunnus = '{$tilausrivi['tunnus']}'";
          pupe_query($update_query);

        }
      }
    }
  }
}
