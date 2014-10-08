<?php

require "../inc/parametrit.inc";

$filename = "$pupe_root_polku/datain/rahti-".md5(uniqid(rand(), true)).".txt";

if (move_uploaded_file($_FILES['userfile']['tmp_name'], $filename)) {

  $edi_data = file_get_contents($filename);
  $edi_data = str_replace("\n", "", $edi_data);

  // otetaan talteen liitetiedoston lis‰‰mist‰ varten
  $filesize = strlen($liitedata);
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

      $valmis = false;
      $luetaan = $rivi;

      while ($valmis == false) {

        $luetaan++;

        if (substr($edi_data[$luetaan], 0, 3) == "BGM") {
          $osat = explode("+", $edi_data[$luetaan]);
          $kuormakirja_id = $osat[2];
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
        'kuorma_id' => $kuormakirja_id,
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
        'sarjanumero' => $sarjanumero
        );

      $tilaukset[$tilaus_id]['pakkaukset'] = $pakkaukset;
    }
  }

  $kuorma[$kuormakirja_id]['tilaukset'] = $tilaukset;


  $data = $kuorma;
  $data = $data[key($data)];

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

  $kukarow['kesken'] = 0;

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

    if ($ostotilaus) { // osa tilauksesta on tullut aiemmassa rahtikirjassa

      $query = "SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND asiakkaan_tilausnumero = '{$tilaus['tilausnro']}'";
      $result = pupe_query($query);
      $laskurow = mysql_fetch_assoc($result);

    }
    else { // ei ostotilausta

      $laskurow = luo_ostotilausotsikko($params);

      $update_query = "UPDATE lasku SET
                       asiakkaan_tilausnumero = '{$tilaus['tilausnro']}'
                       WHERE yhtio = '{$kukarow['yhtio']}'
                       AND tunnus = '{$laskurow['tunnus']}'";
      $update_result = pupe_query($update_query);
    }

    $query = "INSERT INTO liitetiedostot set
              yhtio        = '{$kukarow['yhtio']}',
              liitos       = 'lasku',
              selite       = 'rahtikirjasanoma',
              liitostunnus = '{$laskurow['tunnus']}',
              laatija      = '{$kukarow['kuka']}',
              luontiaika   = now(),
              data         = '$liitedata',
              filename     = 'rahtikirjasanoma',
              filesize     = '$filesize',
              filetype     = 'text/plain'";
    pupe_query($query);

    $kukarow['kesken'] = $laskurow['tunnus'];

    foreach ($tilaus['pakkaukset'] as $key => $pakkaus) {

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
                       kuljetuksen_rekno = '{$data['rekisterinumero']}'
                       WHERE yhtio = '{$kukarow['yhtio']}'
                       AND tilausrivitunnus = '{$lisatty_tun}'";
      pupe_query($update_query);

      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$lisatty_tun}'";
      $rivires = pupe_query($query);
      $rivirow = mysql_fetch_assoc($rivires);

      $query = "SELECT tunnus
                FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND sarjanumero = '{$sarjanumero}'";
      $sarjares = pupe_query($query);

      if (mysql_num_rows($sarjares) == 0) {
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
        $sarjares = pupe_query($query);
      }
    }

    $kukarow['kesken'] = 0;

    if ($myyntitilaus) { // bookkaussanoma tullut ennen rahtikirjaa

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

      $query = "INSERT INTO liitetiedostot set
                yhtio        = '{$kukarow['yhtio']}',
                liitos       = 'lasku',
                selite       = 'rahtikirjasanoma',
                liitostunnus = '{$laskurow['tunnus']}',
                laatija      = '{$kukarow['kuka']}',
                luontiaika   = now(),
                data         = '$liitedata',
                filename     = 'rahtikirjasanoma',
                filesize     = '$filesize',
                filetype     = 'text/plain'";
      pupe_query($query);
    }
  }
}
else{

  echo "<font class='head'>".t("Rahtikirjasanoman sis‰‰nluku")."</font><hr>";
  echo "<form enctype='multipart/form-data' name='sendfile' method='post'>
    <table>
    <tr>
      <th>".t("Valitse tiedosto")."</th>
      <td><input type='file' name='userfile'></td>
    </tr>
    </table>";
  echo "<br><input type='submit' value='".t("K‰sittele tiedosto")."'>";
  echo "</form>";

}

require "inc/footer.inc";
