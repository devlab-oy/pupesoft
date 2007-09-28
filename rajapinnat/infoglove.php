<?php

if (empty($argv)) {
    die('<p>Tämän scriptin voi ajaa ainoastaan komentoriviltä.</p>');
}

require 'inc/connect.inc';
require 'inc/functions.inc';

if ($argv[1] == '') {
	die("Yhtiö on annettava!!");
}

$kukarow['yhtio'] = $argv[1];

$keissit = array("Asiakas","Kustannupaikka","Laskunumero","Myynti","Nimike");

mkdir("/tmp/infoglove");

foreach ($keissit as $keissi) {
	
	switch ($keissi) {
		case "Asiakas" :
			$query =	"SELECT asiakas.ytunnus, asiakas.nimi, asiakas.ryhma, avainsana.selitetark, asiakas.maa, asiakas.konserni, asiakas.kustannuspaikka, kustannuspaikka.nimi
						FROM asiakas
						LEFT JOIN avainsana ON avainsana.yhtio=asiakas.yhtio and avainsana.laji = 'ASIAKASRYHMA' and avainsana.selite = asiakas.ryhma
						LEFT JOIN kustannuspaikka ON kustannuspaikka.yhtio = asiakas.yhtio and kustannuspaikka.tunnus = asiakas.kustannuspaikka and kustannuspaikka.tyyppi = 'K'
						WHERE asiakas.yhtio = '$kukarow[yhtio]'";
			break;
		case "Kustannupaikka" :
			// K = kustannuspaikka, O = kohde ja P = projekti
			$query =	"SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE kustannuspaikka.yhtio = '$kukarow[yhtio]' and tyyppi = 'K'";
			break;
		case "Laskunumero" :
			$query =	"SELECT laskunro
						FROM lasku 
						WHERE lasku.yhtio = '$kukarow[yhtio]' 
						and lasku.tila = 'U' and lasku.alatila = 'X'";
			break;
		case "Myynti" :
			$query =	"SELECT lasku.tapvm, tilausrivi.kpl, tilausrivi.rivihinta, tilausrivi.kate, tuote.kehahin, tilausrivi.tuoteno, lasku.ytunnus, if(tuote.kustp != '', tuote.kustp, asiakas.kustannuspaikka) as kustp, lasku.laskunro
						FROM lasku
						JOIN tilausrivi ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus
						LEFT JOIN tuote ON lasku.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
						LEFT JOIN asiakas ON lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'U' and lasku.alatila = 'X'";
			break;
		case "Nimike" :
			$query =	"SELECT tuote.tuoteno, 
						tuote.nimitys, 
						tuote.try, 
						avainsana.selitetark, 
						(SELECT toim_tuoteno FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio = tuote.yhtio and tuotteen_toimittajat.tuoteno = tuote.tuoteno LIMIT 1) as toim_tuoteno, 
						tuote.tullinimike1, 
						if(epakurantti75pvm='0000-00-00', if(epakurantti50pvm='0000-00-00', if(epakurantti25pvm='0000-00-00', 0, 25), 50), 75) as arvonalennus, 
						tuote.kehahin, 
						sum(tuotepaikat.saldo) as saldo, 
						sum(tuotepaikat.saldo*tuote.kehahin) as varastonarvo
						FROM tuote 
						LEFT JOIN avainsana ON avainsana.yhtio=tuote.yhtio and avainsana.laji = 'TRY' and avainsana.selite = tuote.try
						LEFT JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						GROUP BY tuote.tuoteno";
			break;
		default :
			die("$keissi luonti epäonnistui!");
	}
	
	$filenimi = "/tmp/infoglove/$keissi.txt";
	if (!$handle = fopen($filenimi, "w")) die("Filen $filenimi luonti epäonnistui!");

	$result = mysql_query($query) or pupe_error($query);

	while ($row = mysql_fetch_array($result)) {
		$ulos = "";
	
		for ($i=0; $i < mysql_num_fields($result); $i++) {
			$ulos .= "$row[$i]\t"; 
		}
	
		$ulos .="\n";
	
		if (fwrite($handle, $ulos) === FALSE) die("failin kirjoitus epäonnistui");
	}
	
	fclose($handle);
}

// Zipataan filet yhex zipix
$cmd = "cd /tmp/infoglove/;/usr/bin/zip ".$kukarow['yhtio']."-infoglove.zip *";
$palautus = exec($cmd);

// sit pitäis siirtää ssh:lla jonnekki
// tarvitaan $infoglove_host $infoglove_user
$cmd = "cd /tmp/infoglove/;scp -i /home/$infoglove_user/.ssh/id_dsa ".$kukarow['yhtio']."-infoglove.zip ".$infoglove_host.":.";
$palautus = exec($cmd);
//echo "$cmd\n";

//sit pitää dellata koko dirikka
$cmd = "cd /tmp/;rm -rf infoglove/";
$palautus = exec($cmd);

?>
