<?PHP
// puun_alkio.inc

if ($laji == 'asiakas') {
  $rajauslisa = " and laji = 'asiakas' ";
  $laji = 'asiakas';
  $otsikko = 'Asiakas-segmentit';
}
else {
  $rajauslisa = " and laji = 'tuote' ";
  $laji = 'tuote';

  if ($mista == 'asiakas') {
    $otsikko = 'Tuote-segmenttien poissulkeminen';
  }
  else {
    $otsikko = 'Tuote-segmentit';
  }
}

$otsikko_nappi   = 'segmentti-liitos';

// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
$kentat = "tunnus, liitos, kieli, laji, kutsuja, puun_tunnus, (SELECT nimi FROM dynaaminen_puu WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = puun_alkio.puun_tunnus AND laji = '$laji') nimi";

$jarjestys = 'liitos, laji, kieli, puun_tunnus, nimi';
