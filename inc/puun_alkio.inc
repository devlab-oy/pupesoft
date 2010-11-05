<?PHP
// puun_alkio.inc

$otsikko 		= 'Puun alkiot';
$otsikko_nappi 	= 'Puun Alkio';

// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
$kentat = 'tunnus, liitos, kieli, laji, puun_tunnus';

$jarjestys = 'liitos, laji, kieli, puun_tunnus';

$ulisa .= "&toim2=$toim2";

if ($laji == 'asiakas') {
	$rajauslisa = " and laji = 'asiakas' ";
}
else {
	$rajauslisa = " and laji = 'tuote' ";
}

?>