#!/usr/bin/php
<?php
//Tää on rikki!
/*
if ($argc == 0) die (t("Tätä scriptiä voi ajaa vain komentoriviltä")."!");

// luetaan tilaustiedostoja sisään.. formaatti on perus arvoparikamaa.
// eli kuus ekaa merkkiä kertoo mitä kamaa tulossa, sitten tulee sisältö
// näyttää tältä:
//
// YHTIO:arwi								# yhtio
// KENEN:joni								# kenelle myyjälle tilaus tehdään, laatija on aina web-tilaus
// ASNUM:01066871							# asiakkaan ytunnus
// KOMM1:kommentti							# kommentti otsikolle
// TUOTE:tuotenumero\tkpl\trivikommentti\tH	# tuoteno tabi kpl tabi rivikommentti tabi VAR-kenttä
// TILAT:VALMIS								# jos tässä lukee valmis niin requirataan tilaus valmis..

// otetaan eka parametri komentoriviltä tiedostonimeksi
$file = trim($argv[1]);

if (!file_exists($file)) {
	die(t("Tiedostoa")." $file ".t("ei löydy").".");
}

require ("../inc/connect.inc");
require ("../inc/functions.inc");

if (!$handle = fopen($file, "r")) die(t("Tiedoston avaus epäonnistui")."!");

// nollataan parit muuttujat
$laskuri1 = 0;
$laskuri2 = 0;
$laskuri3 = 0;

// luetaan eka rivi tiedostosta
$rivi = fgets($handle, 4096);

while (!feof($handle)) {

	// poista yksöis- ja kaksoishipsut sekä backslashit...
	$poista	  = array("'", "\\", "\"");
	$rivi	  = str_replace($poista,"",$rivi);

	// otetaan riviltä tunniste ja loput
	$tunniste = substr($rivi, 0, 5);
	$loput    = trim(substr($rivi, 6));

	switch ($tunniste) {

		case "YHTIO":

			$tunnus   = "";

			$query = "select * from yhtio where yhtio='$loput'";
			$result = mysql_query($query) or die($query);

			if (mysql_num_rows($result) != 1) die(t("Yhtiötä")." $loput ".t("ei löydy")."");

			$yhtiorow = mysql_fetch_array($result);
			$kukarow['yhtio'] = $yhtiorow['yhtio'];

			echo t("Tilaus yritykselle")." $yhtiorow[nimi]\n";
			break;

		case "KENEN":

			$query = "select * from kuka where yhtio='$kukarow[yhtio]' and kuka='$loput'";
			$result = mysql_query($query) or die($query);

			if (mysql_num_rows($result) != 1) die(t("Käyttäjää")." $loput ".t("ei löydy")."");

			$kukarow = mysql_fetch_array($result);
			echo t("Myyjä")." $kukarow[nimi]\n";
			break;

		case "ASNUM":

			$loput = (int) $loput; // asiakasnumero numeroks
			$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$loput'";
			$result = mysql_query($query) or die($query);

			if (mysql_num_rows($result) != 1) die(t("Asiakasta")." $loput ".t("ei löydy")."");

			$asiakasrow = mysql_fetch_array($result);
			echo t("Asiakas")." $asiakasrow[nimi]\n";
			break;
			
		case "TILAT":

			if (strtoupper($loput) == "VALMIS") { // jos tässä lukee valmis, niin laitetaankin tilaus valmiiksi
				require ("tilaus-valmis.inc");
			}
			break;

		case "KOMM1":

			$laskuri3++; // otsikoiden määrä
			$kommentti = trim($loput);

			// viimeinen otsikko kenttä, tehdään nyt otsikko...
			$query  = "insert into lasku set
						alatila			= '',
						alv 			= '$asiakasrow[alv]',
						chn				= '$asiakasrow[chn]',
						comments 		= '$asiakasrow[comments]',
						kerayspvm 		=  now(),
						ketjutus		= '$asiakasrow[ketjutus]',
						laatija			= '".t("webtilaus")."',
						laskutusvkopv	= '$asiakasrow[laskutusvkopv]',
						luontiaika		=  now(),
						maa 			= '$asiakasrow[maa]',
						maksuehto 		= '$asiakasrow[maksuehto]',
						myyja 			= '$kukarow[kuka]',
						nimi			= '$asiakasrow[nimi]',
						nimitark 		= '$asiakasrow[nimitark]',
						osoite 			= '$asiakasrow[osoite]',
						ovttunnus 		= '$asiakasrow[ovttunnus]',
						postino 		= '$asiakasrow[postino]',
						postitp 		= '$asiakasrow[postitp]',
						tila			= 'N',
						erikoisale		= '$asiakasrow[erikoisale]',
						tilausvahvistus = '$asiakasrow[tilausvahvistus]',
						toim_maa 		= '$asiakasrow[maa]',
						toim_nimi 		= '$asiakasrow[nimi]',
						toim_nimitark 	= '$asiakasrow[nimitark]',
						toim_osoite 	= '$asiakasrow[osoite]',
						toim_ovttunnus	= '$asiakasrow[toim_ovttunnus]',
						toim_postino 	= '$asiakasrow[postino]',
						toim_postitp 	= '$asiakasrow[postitp]',
						toimaika 		=  now(),
						toimitusehto 	= '$asiakasrow[toimitusehto]',
						toimitustapa 	= '$asiakasrow[toimitustapa]',
						verkkotunnus	= '$asiakasrow[verkkotunnus]',
						liitostunnus    = '$asiakasrow[tunnus]',
						vienti 			= '$asiakasrow[vienti]',
						viesti 			= '$kommentti',
						yhtio			= '$kukarow[yhtio]',
						ytunnus			= '$asiakasrow[ytunnus]'";
			$result = mysql_query($query) or die($query);
			$tunnus = (string) mysql_insert_id();

			// haetaan laskurow vielä arrayseen
			$query    = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
			$result   = mysql_query($query) or die($query);
			$laskurow = mysql_fetch_array($result);

			// tätä tarvitaan jossain
			$kukarow['kesken'] = $laskurow['tunnus'];

			echo t("Otsikko perustettu")." $tunnus\n";
			break;

		case "TUOTE":

			$rivi	 = explode("\t", $loput);			// splitataan tabista
			$tuoteno = str_replace(" ", "",$rivi[0]);	// otetaan välilyönnit pois tuotenumerosta
			$atil    = (int) $rivi[1];					// muutetaan numeroksi
			$teksti  = trim($rivi[2]);					// kommentti
			$var     = trim($rivi[3]);					// var
			$laskuri1++;

			if($tuoteno != '' and $atil > 0) {

				$query = "	SELECT *
							FROM tuote
							WHERE tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or die($query);

				if(mysql_num_rows($result) == 1) {

					$tuoterow  = mysql_fetch_array($result);
					$toimaika  = date("Y-m-d");
					$kerayspvm = date("Y-m-d");
					
					//tarkistetaan rivi
					$eikayttajaa = "ON"; // käytetään kaikkia oletuksia
					require ("tarkistarivi.inc");

					// jos rivissä oli joku virhe, lisätään se puutteena tilaukselle
					if (strlen($varaosavirhe) > 0) {
						
						if ($var != 'H') {
							echo "$tuoteno: ".strip_tags($varaosavirhe)."\n".t("Merkattiin")." $tuoteno ".t("puutteeksi").".\n";
						}
						
						$varaosavirhe = '';

						if ($tee == 'Y') {	//tässä meillä ei ole käyttöliittymää ja rivi halutaan aina kuiteski lisätä tilaukselle joten alvihomma tulee ratkasta tässä
							//tuotteen tiedot alv muodossa
							$trow = $tuoterow;

							list($hinta, $alv) = alv($laskurow, $trow, $hinta, '', '');
						}

						$tee='UV';
						if ($var == 'H') { // jos väkisinhyväksytään
							$hyvaksy=$var;
							$avarattu=$atil;
						}
						else {
							$hyvaksy='P';
							$avarattu=0;
						}
					}

 					if ($var == 'J') { // jos nyt ihan välttämättä halutaan jt niin pistetään sitte ..
						$hyvaksy=$var;
						$avarattu=0;
						$ajt=$atil;
					}
					
					// lisätään rivi...
					require ("lisaarivi.inc");
					$laskuri2++;
				}
				else {
					echo t("Tuotenumeroa")." $tuoteno ".t("ei löydy")."!\n";
				}
			}
			break;

		} // end switch..

	// luetaan seuraava rivi
	$rivi = fgets($handle, 4096);

} // end while

fclose($handle);

echo "$laskuri3 ".t("tilausta")." $laskuri2/$laskuri1 ".t("riviä lisätty")."\n";
*/
?>
