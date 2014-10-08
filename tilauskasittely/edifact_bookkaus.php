<?php

require "../inc/parametrit.inc";

$filename = "$pupe_root_polku/datain/bookki-".md5(uniqid(rand(), true)).".txt";

if (!isset($tee)) {
  $tee = '';
}

if (move_uploaded_file($_FILES['file']['tmp_name'], $filename)) {

  $edi_data = file_get_contents($filename);
  $edi_data = str_replace("\n", "", $edi_data);
  $liitedata = $edi_data;
  $edi_data = str_replace("?'", "#%#", $edi_data);
  $edi_data = explode("'", $edi_data);

  $kuorma = array();
  $pakkaukset = array();
  $tilaukset = array();

  foreach ($edi_data as $rivi => $value) {

    $value = str_replace("#%#", "'", $value);

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

  $data = $bookkaus;

  // katsotaan onko tilauksesta luotu jo osto- tai myyntitilaus
  $query = "SELECT group_concat(tila)
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND asiakkaan_tilausnumero = '{$data['tilausnro']}'
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
    $query = "SELECT tunnus
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND ovttunnus = '{$data['vastaanottaja_ovt']}'";
    $res = pupe_query($query);
    $asiakas_id = mysql_result($res, 0);

    require_once "../tilauskasittely/luo_myyntitilausotsikko.inc";
    $tunnus = luo_myyntitilausotsikko('RIVISYOTTO', $asiakas_id);

    $update_query = "UPDATE lasku SET
                     asiakkaan_tilausnumero = '{$data['tilausnro']}'
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus = '{$tunnus}'";
    $update_result = pupe_query($update_query);
  }
  else {
    $query = "SELECT tunnus
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND asiakkaan_tilausnumero = '{$data['tilausnro']}'
              AND tila = 'N'";
    $result = pupe_query($query);
    $tunnus = mysql_result($result, 0);
  }

  $filesize = strlen($liitedata);
  $liitedata = mysql_real_escape_string($liitedata);

  $query = "INSERT INTO liitetiedostot set
            yhtio        = '{$kukarow['yhtio']}',
            liitos       = 'lasku',
            liitostunnus = '$tunnus',
            selite       = 'bookkaussanoma',
            laatija      = '{$kukarow['kuka']}',
            luontiaika   = now(),
            data         = '$liitedata',
            filename     = 'bookkaussanoma',
            filesize     = '$filesize',
            filetype     = 'text/plain'";
  pupe_query($query);

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
                  AND asiakkaan_tilausnumero = '{$data['tilausnro']}'
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
      $update_result = pupe_query($update_query);
    }
  }
}
else{

  echo "<font class='head'>".t("Bookkaus-sanoman sis‰‰nluku")."</font><hr>";
  echo "<form enctype='multipart/form-data' name='sendfile' method='post'>
    <table>
    <tr>
      <th>".t("Valitse tiedosto")."</th>
      <td><input type='file' name='file'></td>
    </tr>
    </table>";
  echo "<br><input type='submit' value='".t("K‰sittele tiedosto")."'>";
  echo "</form>";

}

require "inc/footer.inc";
