<?php

require "../inc/parametrit.inc";

// tehd‰‰n tiedostolle uniikki nimi
$filename = "$pupe_root_polku/datain/$tyyppi-order-".md5(uniqid(rand(), true)).".txt";


if (move_uploaded_file($_FILES['userfile']['tmp_name'], $filename)) {

  $edi_data = file_get_contents($filename);
  $edi_data = str_replace("\n", "", $edi_data);
  $edi_data = explode("'", $edi_data);

  $kuorma = array();
  $pakkaukset = array();
  $tilaukset = array();
  $varasto_setattu = false;
  $rivimaara = count($edi_data);

  foreach ($edi_data as $rivi => $value) {

    if (substr($value, 0, 3) == 'UNB') {
      $osat = explode("+", $value);
      $vastaanottaja_ovt_info = $osat[3];
      $vastaanottaja_ovt_info_osat = explode(":", $vastaanottaja_ovt_info);
      $vastaanottaja_ovt = $vastaanottaja_ovt_info_osat[0];
    }

    if (substr($value, 0, 6) == "RFF+CU") {
      $osat = explode("+", $value);
      $tilaus_info = $osat[1];
      $tilaus_info_osat = explode(":", $tilaus_info);
      $tilausnro = $tilaus_info_osat[1];
      break;
    }

  }

  $bookkaus = array(
    'vastaanottaja_ovt' => $vastaanottaja_ovt,
    'tilausnro' => $tilausnro
  );


  $edidata = base64_encode(serialize($bookkaus));

  echo "<form method='post'>
  <input type='hidden' name='data' value='$edidata' />
  <input type='hidden' name='tee' value='luo' />
  <input type='submit' />
  </form>";

}
elseif($tee == 'luo') {

  $data = unserialize(base64_decode($data));

  require_once "../tilauskasittely/luo_myyntitilausotsikko.inc";

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

  if (!$myyntitilaus) {

    $query = "SELECT tunnus
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND ovttunnus = '{$data['vastaanottaja_ovt']}'";
    $res = pupe_query($query);
    $asiakas_id = mysql_result($res, 0);

    $tunnus = luo_myyntitilausotsikko('RIVISYOTTO', $asiakas_id);

    $update_query = "UPDATE lasku SET
                     asiakkaan_tilausnumero = '{$data['tilausnro']}'
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus = '{$tunnus}'";
    $update_result = pupe_query($update_query);

    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$tunnus}'";
    $result = pupe_query($query);
    $laskurow = mysql_fetch_assoc($result);

    if ($ostotilaus) {

      $query = "SELECT tunnus
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND asiakkaan_tilausnumero = '{$tilaus['tilausnro']}'";
      $result = pupe_query($query);
      $otunnus = mysql_result($result, 0);

      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$otunnus}'";
      $result = pupe_query($query);

      while ($tilausrivi = mysql_fetch_assoc($result)) {

        // haetaan tuotteen tiedot
        $query = "SELECT *
                  FROM tuote
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$tilausrivi['tuoteno']}'";
        $tuoteres = pupe_query($query);

        $trow = mysql_fetch_assoc($tuoteres);
        $kpl = 1;
        require "lisaarivi.inc";
      }
    }
  }


die;

/*

  require_once "../tilauskasittely/luo_myyntitilausotsikko.inc";

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
    'maa' => $toimrow['maa']
  );

  $kukarow['kesken'] = 0;

  foreach ($data['tilaukset'] as $key => $tilaus) {

    $laskurow = luo_ostotilausotsikko($params);

    $kukarow['kesken'] = $laskurow['tunnus'];

    $update_query = "UPDATE lasku SET
                     asiakkaan_tilausnumero = '{$tilaus['tilausnro']}',
                     alatila = 'A'
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus = '{$laskurow['tunnus']}'";
    $update_result = pupe_query($update_query);

    foreach ($tilaus['pakkaukset'] as $key => $pakkaus) {

      // haetaan tuotteen tiedot
      $query = "SELECT *
                FROM tuote
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$pakkaus['tuoteno']}'";
      $tuoteres = pupe_query($query);

      $trow = mysql_fetch_assoc($tuoteres);
      $kpl = 1;

      require "lisaarivi.inc";

      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$lisatty_tun}'";
      $rivires = pupe_query($query);
      $rivirow = mysql_fetch_assoc($rivires);

      $sarjanumero = trim($pakkaus['sarjanumero']);

      $query = "SELECT *
                FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND sarjanumero = '{$sarjanumero}'
                AND tuoteno     = '{$rivirow['tuoteno']}'
                AND (ostorivitunnus = 0 or myyntirivitunnus = 0)";
      $sarjares = pupe_query($query);

      if (mysql_num_rows($sarjares) == 0) {

        $query = "INSERT INTO sarjanumeroseuranta SET
                  yhtio           = '{$kukarow['yhtio']}',
                  tuoteno         = '{$rivirow['tuoteno']}',
                  sarjanumero     = '{$sarjanumero}',
                  lisatieto       = 'rahtikirjanumero:#!#{$data['kuorma_id']}#!#',
                  massa           = '{$pakkaus['paino']}',
                  leveys          = '{$pakkaus['leveys']}',
                  ymparysmitta    = '{$pakkaus['ymparys']}',
                  ostorivitunnus  = '{$lisatty_tun}',
                  era_kpl         = '1',
                  laatija         = '{$kukarow['kuka']}',
                  luontiaika      = NOW(),
                  hyllyalue       = '{$rivirow['hyllyalue']}',
                  hyllynro        = '{$rivirow['hyllynro']}',
                  hyllyvali       = '{$rivirow['hyllyvali']}',
                  hyllytaso       = '{$rivirow['hyllytaso']}'";
        $sarjares = pupe_query($query);
      }
    }
    $kukarow['kesken'] = 0;
  }


*/


}
else{

  echo "<font class='head'>".t("Bookkaus-sanoman sis‰‰nluku")."</font><hr>";

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
