<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
	die ("T�t� scripti� voi ajaa vain komentorivilt�!");
}

if (!isset($argv[1]) or $argv[1] == '') {
	die("Yhti� on annettava!!");
}

$kukarow['yhtio'] = $argv[1];

require 'inc/connect.inc';
require 'inc/functions.inc';

$query = "SELECT * FROM yhtio, yhtion_parametrit WHERE yhtio.yhtio = yhtion_parametrit.yhtio and yhtio.yhtio = '$kukarow[yhtio]'";
$result = mysql_query($query) or pupe_error($query);
$yhtiorow = mysql_fetch_array($result);

$keissit = array("Asiakas","Toimittaja","Kustannupaikka","Laskunumero","Myynti","Nimike","Monivarasto","Ostot","Ostotilausnumero","Varastonumero","Tapahtumalaji","Myyntitil","Avointil");
//$keissit = array("Avointil");

mkdir("/tmp/infoglove");

$query_ale_lisa = generoi_alekentta('M');

foreach ($keissit as $keissi) {

	switch ($keissi) {
		case "Asiakas" :
			$query =	"SELECT asiakas.tunnus, if(asiakas.selaus!='' and asiakas.selaus is not null, asiakas.selaus, asiakas.ytunnus) AS asiakasnro, asiakas.nimi, asiakas.ryhma, avainsana.selitetark, asiakas.maa, asiakas.konserni, asiakas.kustannuspaikka, kustannuspaikka.nimi
						FROM asiakas
						LEFT JOIN avainsana ON avainsana.yhtio=asiakas.yhtio and avainsana.laji = 'ASIAKASRYHMA' and avainsana.selite = asiakas.ryhma
						LEFT JOIN kustannuspaikka ON kustannuspaikka.yhtio = asiakas.yhtio and kustannuspaikka.tunnus = asiakas.kustannuspaikka and kustannuspaikka.tyyppi = 'K'
						WHERE asiakas.yhtio = '$kukarow[yhtio]'";
			break;
		case "Toimittaja" :
			$query =	"SELECT toimi.tunnus, toimi.ytunnus AS toimittajanro, toimi.nimi, '' AS ryhma, '', toimi.maa, '' AS konserni, toimi.kustannuspaikka, kustannuspaikka.nimi
						FROM toimi
						LEFT JOIN kustannuspaikka ON kustannuspaikka.yhtio = toimi.yhtio and kustannuspaikka.tunnus = toimi.kustannuspaikka and kustannuspaikka.tyyppi = 'K'
						WHERE toimi.yhtio = '$kukarow[yhtio]'";
			break;
		case "Kustannupaikka" :
			// K = kustannuspaikka, O = kohde ja P = projekti
			$query = "	SELECT tunnus, IF(INSTR(nimi,tunnus) != 0,LTRIM(RIGHT(nimi,CHAR_LENGTH(nimi)-CHAR_LENGTH(tunnus))),nimi) as nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]'
						and kaytossa != 'E'
						and tyyppi = 'K'";
			break;
		case "Laskunumero" :
			$query =	"SELECT laskunro
						FROM lasku
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'U' and lasku.alatila = 'X'";
			break;
		case "Myynti" :
			$query =	"SELECT lasku.tapvm, tilausrivi.kpl, tilausrivi.rivihinta, tilausrivi.kate, tuote.kehahin, tilausrivi.tuoteno, asiakas.tunnus AS asiakasnro, if(tuote.kustp != 0, tuote.kustp, asiakas.kustannuspaikka) as kustp, lasku.laskunro
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
		case "Monivarasto" :
			$query =	"SELECT saldoaika, saldo, saldo*kehahin as Varastoarvo, tuotepaikat.tuoteno, LEFT(hyllyalue,1) as hyllyalue
						FROM tuotepaikat
						JOIN tuote ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno
						WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
						ORDER BY tuotepaikat.tuoteno";
			break;
		case "Ostot" :
			$query =	"SELECT tilausrivi.laskutettuaika, tilausrivi.kpl, tilausrivi.rivihinta, tilausrivi.tuoteno, lasku.ytunnus, toimi.kustannuspaikka, lasku.laskunro
						FROM lasku
						JOIN tilausrivi ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus
						LEFT JOIN toimi ON lasku.yhtio = toimi.yhtio and lasku.liitostunnus = toimi.tunnus
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and tila = 'K'
						and alatila = 'X'
						and vanhatunnus = 0
						ORDER BY lasku.tunnus";
			break;
		case "Ostotilausnumero" :
			$query =	"SELECT distinct laskunro
						FROM lasku
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and tila = 'K'
						and alatila = 'X'
						and vanhatunnus = 0
						ORDER BY lasku.tunnus";
			break;
		case "Varastonumero" :
			$query =	"SELECT distinct LEFT(hyllyalue,1) as hyllyalue
						FROM tuotepaikat
						WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
						ORDER BY 1";
			break;
		case "Tapahtumalaji" :
			$query =	"SELECT 'Myynti' as Tapahtumalaji";
			break;
		case "Myyntitil" :
			$query =	"SELECT left(lasku.luontiaika,10) AS 'paivays',
						tilausrivi.tilkpl AS 'maara',
						round(if(tilausrivi.laskutettu!='',tilausrivi.rivihinta/if('$yhtiorow[alv_kasittely]'='',(1+tilausrivi.alv/100),1),(tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('$yhtiorow[alv_kasittely]'='',(1+tilausrivi.alv/100),1)),'$yhtiorow[hintapyoristys]') AS 'arvo',
						if(tilausrivi.laskutettu!='',tilausrivi.kate,round((tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('$yhtiorow[alv_kasittely]'='',(1+tilausrivi.alv/100),1)-(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt)),'$yhtiorow[hintapyoristys]')) AS 'kate',
						if(tilausrivi.laskutettu!='',tilausrivi.rivihinta-tilausrivi.kate,round(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt),6)) AS 'keskihinta',
						tilausrivi.tuoteno,
						asiakas.tunnus AS asiakasnro,
						if(tuote.kustp > 0, tuote.kustp, asiakas.kustannuspaikka) as kustp,
						lasku.tunnus
						FROM lasku
						JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
						LEFT JOIN tuote ON (tuote.yhtio = lasku.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
						LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila IN ('N','L')
						and lasku.luontiaika != '0000-00-00 00:00:00'
						ORDER BY lasku.tunnus, tilausrivi.tunnus";
			break;
		case "Avointil" :
			$query =	"SELECT lasku.toimaika AS 'paivays',
						tilausrivi.tilkpl AS 'maara',
						round(if(tilausrivi.laskutettu!='',tilausrivi.rivihinta/if('$yhtiorow[alv_kasittely]'='',(1+tilausrivi.alv/100),1),(tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('$yhtiorow[alv_kasittely]'='',(1+tilausrivi.alv/100),1)),'$yhtiorow[hintapyoristys]') AS 'arvo',
						if(tilausrivi.laskutettu!='',tilausrivi.kate,round((tilausrivi.hinta*(tilausrivi.varattu+tilausrivi.jt))*{$query_ale_lisa}/if('$yhtiorow[alv_kasittely]'='',(1+tilausrivi.alv/100),1)-(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt)),'$yhtiorow[hintapyoristys]')) AS 'kate',
						if(tilausrivi.laskutettu!='',tilausrivi.rivihinta-tilausrivi.kate,round(tuote.kehahin*(tilausrivi.varattu+tilausrivi.jt),6)) AS 'keskihinta',
						tilausrivi.tuoteno,
						asiakas.tunnus AS asiakasnro,
						if(tuote.kustp > 0, tuote.kustp, asiakas.kustannuspaikka) as kustp,
						lasku.tunnus
						FROM lasku
						JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
						LEFT JOIN tuote ON (tuote.yhtio = lasku.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
						LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila IN ('N','L')
						and lasku.luontiaika != '0000-00-00 00:00:00'
						and lasku.laskunro = 0
						and tilausrivi.kpl = 0
						and lasku.alatila != 'X'
						ORDER BY lasku.tunnus, tilausrivi.tunnus";
			break;
		default :
			die("$keissi luonti ep�onnistui!");
	}

	$filenimi = "/tmp/infoglove/$keissi.txt";
	if (!$handle = fopen($filenimi, "w")) die("Filen $filenimi luonti ep�onnistui!");

	$result = mysql_query($query) or pupe_error($query);

	while ($row = mysql_fetch_array($result)) {
		$ulos = "";

		for ($i=0; $i < mysql_num_fields($result); $i++) {
			$ulos .= "$row[$i]\t";
		}

		$ulos .="\n";

		if (fwrite($handle, $ulos) === FALSE) die ("failin kirjoitus ep�onnistui");
	}

	fclose($handle);
}


// Zipataan filet yhex zipix
$cmd = "cd /tmp/infoglove/;/usr/bin/zip ".$kukarow['yhtio']."-infoglove.zip *";
$palautus = exec($cmd);

// sit pit�is siirt�� ssh:lla jonnekki
// tarvitaan $infoglove_host $infoglove_user
$cmd = "cd /tmp/infoglove/;scp -i /home/$infoglove_user/.ssh/id_dsa ".$kukarow['yhtio']."-infoglove.zip ".$infoglove_user."@".$infoglove_host.":.";
$palautus = exec($cmd);
//echo "$cmd\n";

//sit pit�� dellata koko dirikka
$cmd = "cd /tmp/;rm -rf infoglove/";
$palautus = exec($cmd);

?>
