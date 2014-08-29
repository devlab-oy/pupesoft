<?php

require "../inc/parametrit.inc";

// tehd‰‰n tiedostolle uniikki nimi
$filename = "$pupe_root_polku/datain/$tyyppi-order-".md5(uniqid(rand(), true)).".txt";


if (move_uploaded_file($_FILES['userfile']['tmp_name'], $filename)) {



  $edi_data = file_get_contents($filename);
  $edi_data = str_replace("\n", "", $edi_data);
  $edi_data = explode("'", $edi_data);


  $pakkaukset = array();
  $varasto_setattu = false;

  foreach ($edi_data as $key => $value) {

    if (substr($value, 0, 6) == 'NAD+OS') {
      $osat = explode("+", $value);
      $os = $osat[4];
    }

    if (substr($value, 0, 6) == 'RFF+CU') {
      $osat = explode("+", $value);
      $tilaus_info = $osat[1];
      $tilaus_info_osat = explode(":", $tilaus_info);
      $tilausno = $tilaus_info_osat[1];
    }


    if (substr($value, 0, 5) == 'LOC+8' and $varasto_setattu == false) {
      $osat = explode("+", $value);
      $paamaara_info = $osat[2];
      $paamaara_info_osat = explode(":", $paamaara_info);
      $paamaara = $paamaara_info_osat[3];

     // haetaan varaston tiedot
     $query = "SELECT *
               FROM varastopaikat
               WHERE yhtio = '$kukarow[yhtio]'
               AND locate(nimitys, '{$paamaara}') > 0";
     $varastores = pupe_query($query);
     $varastorow = mysql_fetch_assoc($varastores);
     $varasto_setattu = true;
    }

    if (substr($value, 0, 7) == 'CPS+PKG') {

      $osat = explode("+", $edi_data[$key+2]);
      $paino_info = $osat[3];
      $paino_info_osat = explode(":", $paino_info);

      $osat = explode("+", $edi_data[$key+4]);
      $ymp_info = $osat[3];
      $ymp_info_osat = explode(":", $ymp_info);

      $osat = explode("+", $edi_data[$key+5]);
      $leveys_info = $osat[3];
      $leveys_info_osat = explode(":", $leveys_info);

      $osat = explode("+", $edi_data[$key+7]);
      $sarjanumero = $osat[2];

      $tuoteno = '123';

      $pakkaukset[] = array(
        'paino' => $paino_info_osat[1],
        'ymparys' => $ymp_info_osat[1],
        'leveys' => $leveys_info_osat[1],
        'tuoteno' => $tuoteno,
        'sarjanumero' => $sarjanumero,
        );
    }

    if (substr($value, 0, 7) == 'DTM+132') {

      $osat = explode("+", $value);
      $toimitusaika_info = $osat[1];
      $toimitusaika_info_osat = explode(":", $toimitusaika_info);
      $toimitusaika = $toimitusaika_info_osat[1];

      $vuosi = substr($toimitusaika, 0,4);
      $kuu = substr($toimitusaika, 4,2);
      $paiva = substr($toimitusaika, 6,2);

    }

  }


echo 'Original supplier: ', $os, '<br>';
echo 'Tuoteno: ', $tuoteno, '<br>';
echo 'Tilausnumero: ', $tilausno, '<br>';
echo 'Toimitusaika: ', $paiva, '-', $kuu, '-', $vuosi, '<br>';

echo 'pakkaukset:<br>';

foreach ($pakkaukset as $key => $value) {
  echo 'paino: ', $value['paino'],' - leveys: ', $value['leveys'],' - ymp‰rys: ', $value['ymparys'], '<br>';
}

echo '<br>';

$data = array();

$data['os'] = $os;
$data['tilausno'] = $tilausno;
$data['toimaika'] = $vuosi.'-'.$kuu.'-'.$paiva;
$data['varasto'] = $varastorow['tunnus'];
$data['pakkaukset'] = $pakkaukset;

$edidata = base64_encode(serialize($data));

echo "<form method='post'>
<input type='hidden' name='data' value='$edidata' />
<input type='hidden' name='tee' value='luo' />
<input type='submit' />
</form>";


}
elseif($tee == 'luo') {

  $data = unserialize(base64_decode($data));

  require_once "../inc/luo_ostotilausotsikko.inc";

  // haetaan toimittajan tiedot
  $query = "SELECT *
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND nimi = '{$data['os']}'";
  $toimres = pupe_query($query);
  $toimrow = mysql_fetch_assoc($toimres);

  $params = array(
    'liitostunnus' => $toimrow['tunnus'],
    'nimi' => $toimrow['nimi'],
    'myytil_toimaika' => $data['toimaika'],
    'varasto' => $data['varasto'],
    'osoite' => $toimrow['osoite'],
    'postino' => $toimrow['postino'],
    'postitp' => $toimrow['postitp'],
    'maa' => $toimrow['maa'],
  );

  $kukarow['kesken'] = 0;

  $laskurow = luo_ostotilausotsikko($params);

  $kukarow['kesken'] = $laskurow['tunnus'];

  foreach ($data['pakkaukset'] as $key => $value) {

    // haetaan tuotteen tiedot
    $query = "SELECT *
              FROM tuote
              WHERE yhtio = '$kukarow[yhtio]'
              AND tuoteno = '$value[tuoteno]'";
    $tuoteres = pupe_query($query);

    $trow = mysql_fetch_assoc($tuoteres);
    $kpl = 1;

    require "lisaarivi.inc";

    $query = "SELECT *
              FROM tilausrivi
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus = '$lisatty_tun'";
    $rivires = pupe_query($query);
    $rivirow = mysql_fetch_assoc($rivires);

    $sarjanumero = trim($value['sarjanumero']);

    $query = "SELECT *
              FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
              WHERE yhtio     = '$kukarow[yhtio]'
              AND sarjanumero = '$sarjanumero'
              AND tuoteno     = '$rivirow[tuoteno]'
              AND (ostorivitunnus = 0 or myyntirivitunnus = 0)";
    $sarjares = pupe_query($query);

    if (mysql_num_rows($sarjares) == 0) {

      $query = "INSERT INTO sarjanumeroseuranta SET
                yhtio           = '{$kukarow['yhtio']}',
                tuoteno         = '{$rivirow['tuoteno']}',
                sarjanumero     = '{$sarjanumero}',
                massa           = '{$value['paino']}',
                leveys          = '{$value['leveys']}',
                ymparysmitta    = '{$value['ymparys']}',
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
}
else{

  echo "<font class='head'>".t("Tiedoston sis‰‰nluku")."</font><hr>";

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
