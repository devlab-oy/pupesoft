<?PHP
// puun_alkio.inc

$otsikko 		= 'Puun alkiot';
$otsikko_nappi 	= 'Puun Alkio';

if ($laji == 'asiakas') {
	$rajauslisa = " and laji = 'asiakas' ";
}
else {
	$rajauslisa = " and laji = 'tuote' ";
	$laji = 'tuote';
}

// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
$kentat = "tunnus, liitos, kieli, laji, puun_tunnus, (SELECT nimi FROM dynaaminen_puu WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = puun_alkio.puun_tunnus AND laji = '$laji') nimi";

$jarjestys = 'liitos, laji, kieli, puun_tunnus, nimi';

$ulisa .= "&toim2=$toim2";

?>