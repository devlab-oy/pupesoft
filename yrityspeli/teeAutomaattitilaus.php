<?php
/// teeAutomaattitilaus.php
/// TAMK Yrityspeli-valikko, sutinan automaattitilaukset
///
/// Author: Jarmo Kortetjärvi
/// Created: 2010-06-23
/// Updated: 2011-03-22
///
/// AUTOMAATTIAJO MAANANTAISIN

//require('../inc/parametrit.inc');

date_default_timezone_set('Europe/Helsinki');

/*// debuggia
$file = "automaattitilaus.txt";
$handle = fopen($file, 'a+') or die("can't open file");
$data = "automaattitilausten luonti - ".date('Y-m-d H:i:s')."\n";
fwrite($handle, $data);
fclose($handle);
//*/

//* tietokantayhteys 
	require_once '/var/www/html/pupesoft/inc/salasanat.php';
	$link = mysql_connect($dbhost, $dbuser, $dbpass) or die ('Ongelma ' . mysql_error());
	// Select database
	$result = mysql_select_db($dbkanta, $link) or die('Ongelmia ' . mysql_error());

	if ($result) {
		//no actions
	} else {
		print 'Ongelma avatessa tietokantaa!';
	}
//*/

$viikko = sprintf("%04d-%02d", date('Y'), date('W'));

$query = "SELECT * FROM TAMK_automaattitilaus WHERE viikko = '$viikko'";
$numrows = mysql_num_rows(mysql_query($query));

if($numrows == 0){
	// haetaan muuttujat
		$query = " 	SELECT *
					FROM TAMK_automaattitilauskerroin
				";
		$result = mysql_query($query);

		$query =" INSERT INTO	TAMK_automaattitilaus
						(
							yhtio
							,viikko
							,tilausaika
							,summa
							,tilaustyyppi
							,tilausrivit
						)
						VALUES
				";
		
		while($row = mysql_fetch_assoc($result)){
			
			$tyyppi = $row['tilaustyyppi'];
			$kertaaviikossa = $row['kertaaViikossa'];
			$painoarvo= $row['painoarvo'];
			$laskuri = 0;
			if ($kertaaviikossa != 0) {
			$osuus = round(100/$kertaaviikossa);
			}

		if($kertaaviikossa==0){
			echo "Ei tehtäviä tilauksia.";
			//exit;
		}
			
		// tehdään 'kertaaViikossa' määrä tilauksia
			
			if($kertaaviikossa != 0){
				for($i=1; $i <= $kertaaviikossa; $i++){
					
					$tilausrivit= $row['tilausrivit'] +rand(0,1);  // Muokattu 16.1.2014, laitettu rand - funktion minimi arvoksi 0, ennen oli -1
					
					// arvotaan summan osuus tilauksesta
					$summa = $osuus + rand(-($osuus/$kertaaviikossa),($osuus/$kertaaviikossa));
					
					// viimeiselle kierrokselle loput prosentit
					if($i == $kertaaviikossa){
						$summa = 100 - $laskuri;
						if ($summa < 0) $summa = 0;
					}
					
					$laskuri += $summa;
					
					// satunnainen päivä seuraavalta viikolta
					$date = date('Y-m-d', rand(strtotime('now'), strtotime("+5 day")));
					// satunnainen kellonaika klo 7-16
					$time = sprintf("%02d:%02d", rand(7,15), rand(0,59));
					$tilausaika = $date." ".$time;
					
					$query .= "	(
									'myyra'
									,'$viikko'
									,'$tilausaika'
									,$summa
									,'$tyyppi'
									,$tilausrivit
								),";
					}
				}
				else echo "Ei tehtäviä tilauksia.";
		}
		$query = substr($query, 0, -1);
		mysql_query($query);
}
else echo "Viikon tilausajo on jo tehty.";

//require("../inc/footer.inc");
?>
