<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Datan sisäänluku")."</font><hr>";

if ($oikeurow['paivitys'] != '1') { // Saako päivittää
	if ($uusi == 1) {
		echo "<b>".t("Sinulla ei ole oikeutta lisätä")."</b><br>";
		$uusi = '';
	}
	if ($del == 1) {
		echo "<b>".t("Sinulla ei ole oikeuttaa poistaa")."</b><br>";
		$del = '';
		$tunnus = 0;
	}
	if ($upd == 1) {
		echo "<b>".t("Sinulla ei ole oikeuttaa muuttaa")."</b><br>";
		$upd = '';
		$uusi = 0;
		$tunnus = 0;
	}
}

flush();

$vikaa=0;
$tarkea=0;
$kielletty=0;
$lask=0;
$postoiminto = 'X';
$tyhjatok  = "";
$ashinaleas = 0;
$ashinaletuo = 0;

if (is_uploaded_file($_FILES['userfile']['tmp_name'])==TRUE) {

	list($name,$ext) = split("\.", $_FILES['userfile']['name']);

	if (strtoupper($ext) !="TXT" and strtoupper($ext)!="CSV")
	{
		die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
	}

	if ($_FILES['userfile']['size']==0)
	{
		die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
	}

	$file=fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");

	echo "<font class='message'>".t("Tutkaillaan mitä olet lähettänyt").".<br></font>";

	// luetaan eka rivi tiedostosta..
	$rivi    = fgets($file);
	$otsikot = explode("\t", strtoupper(trim($rivi)));

	// haetaan valitun taulun sarakkeet
	$query = "SHOW COLUMNS FROM $table";
	$fres  = mysql_query($query) or pupe_error($query);

	while ($row=mysql_fetch_array($fres))
	{
		//pushataan arrayseen kaikki sarakenimet ja tietuetyypit
		$trows[] = strtoupper($row[0]);
		$ttype[] = $row[1];
	}

	//määrittelee onko tämä taulu sellanen jossa ei ole yhtiö-saraketta
	$eiyhtiota = "";

	// määritellään pakolliset sarakkeet
	switch ($table) {

		case "tuote" :
			$pakolliset = array("TUOTENO");
//			$kielletyt = array("KEHAHIN","VIHAHIN","VIHAPVM","EPAKURANTTI1PVM","EPAKURANTTI2PVM");
			$kielletyt = array("KEHAHIN","VIHAHIN","VIHAPVM");
			break;
		case "tuotepaikat" :
			$pakolliset = array("TUOTENO","HYLLYALUE","HYLLYNRO","HYLLYVALI","HYLLYTASO","OLETUS");
			$kielletyt = array("");
			break;
		case "asiakas" :
			if (strtoupper($yhtiorow['maakoodi']) == 'FI') {
				$pakolliset = array("YTUNNUS", "OVTTUNNUS");
			}
			else {
				$pakolliset = array("YTUNNUS");
			}
			$kielletyt = array("");
			break;
		case "toimi" :
			$pakolliset = array("YTUNNUS");
			$kielletyt = array("");
			break;
		case "tullinimike" :
			$pakolliset = array("CN","KIELI");
			$kielletyt = array("");
			break;
		case "tili" :
			$pakolliset = array("TILINO");
			$kielletyt = array("");
			break;
		case "maksuehto" :
			$pakolliset = array("TEKSTI");
			$kielletyt = array("");
			break;
		case "asiakashinta" :
			$pakolliset = array("HINTA");
			$kielletyt = array("MUUTOSPVM");
			break;
		case "asiakaskommentti" :
			$pakolliset = array("YTUNNUS","TUOTENO");
			$kielletyt = array("");
			break;
		case "asiakasalennus" :
			$pakolliset = array("ALENNUS");
			$kielletyt = array("MUUTOSPVM");
			break;
		case "perusalennus" :
			$pakolliset = array("ALENNUS","RYHMA");
			$kielletyt = array("");
			break;
		case "yhteyshenkilo" :
			$pakolliset = array("ASIAKAS","NIMI");
			$kielletyt = array("");
			break;
		case "avainsana" :
			$pakolliset = array("LAJI","SELITE");
			$kielletyt = array("");
			break;
		case "tuoteperhe" :
			$pakolliset = array("ISATUOTENO","TUOTENO","TYYPPI");
			$kielletyt = array("");
			break;
		case "rahtimaksut" :
			$pakolliset = array("TOIMITUSTAPA","KILOTALKU","KILOTLOPPU");
			$kielletyt = array("");
			break;
		case "tuotteen_avainsanat" :
			$pakolliset = array("TUOTENO","LAJI","SELITE");
			$kielletyt = array("");
			break;
		case "kalenteri" :
			$pakolliset = array("KUKA","ASIAKAS","PVMALKU");
			$kielletyt = array("");
			break;
		case "yhteensopivuus_auto" :
			$pakolliset = array("MERKKI","MALLI","MALLITARKENNE","KORITYYPPI","CC","MOOTTORITYYPPI","POLTTOAINE","SYLINTERIMAARA","SYLINTERINHALKAISIJA","TEHO_KW","TEHO_HV","ALKUKK","ALKUVUOSI","LOPPUKK","LOPPUVUOSI","LISATIEDOT","AUTODATA");
			$kielletyt = array("");
			$tyhjatok  = "yes";
			break;
		case "yhteensopivuus_tuote" :
			$pakolliset = array("ATUNNUS","TUOTENO");
			$kielletyt = array("");
			break;
		case "yhteensopivuus_autodata" :
			$pakolliset = array("AUTODATAID");
			$kielletyt = array("");
			break;
		case "rekisteritiedot" :
			$pakolliset = array("REKNO");
			$kielletyt = array("");
			break;
		case "sanakirja" :
			$pakolliset = array("FI");
			$kielletyt = array("");
			$eiyhtiota = "TRIP";
				break;
		case "tuotteen_toimittajat" :
			$pakolliset = array("TUOTENO","TOIMITTAJA");
			$kielletyt = array("LIITOSTUNNUS");
			break;
		case "kuka" :
			$pakolliset = array("KUKA");
			$kielletyt = array("");
			break;
		case "todo" :
			$pakolliset = array("KUVAUS","PRIORITEETTI","PYYTAJA");
			$kielletyt = array("");
			break;
		case "hinnasto" :
			$pakolliset = array("TUOTENO","HINTA");
			$kielletyt = array("");
			break;
		default :
			echo t("Miten tänne pääsit!");
			exit;
	}

	// $trows 		sisältää kaikki taulun sarakkeet tietokannasta
	// $otsikot 	sisältää kaikki sarakkeet saadusta tiedostosta

	foreach ($otsikot as $column) {

		$column = strtoupper(trim($column));
		if ($column != '') {
			//laitetaan kaikki paitsi valintasarake talteen.
			if ($column != "TOIMINTO") {
				if (!in_array($column, $trows))
				{
					echo "<br><font class='message'>".t("Saraketta")." \"<b>".strtoupper($column)."</b>\" ".t("ei löydy")." $table-".t("taulusta")."!</font>";
					$vikaa++;
				}
				else
				{
					//sarake löytyy, laitetaan sen tyyppi talteen..
					//sarakkeen positio tietokannassa
					$pos1 = array_search($column, $trows);
					//sarakkeen positio tiedostossa
					$pos2 = array_search($column, $otsikot);

					$ityyppi[$pos2]=$ttype[$pos1];
				}

				// yhtio ja tunnus kenttiä ei saa koskaan muokata...
				if ($column=='YHTIO' or $column=='TUNNUS') {
					echo "<br><font class='message'>".t("Yhtio- ja tunnussaraketta ei saa muuttaa")."!</font>";
					$vikaa++;
				}

				if (in_array($column, $pakolliset)) {
					// pushataan positio indeksiin, että tiedetään missä kohtaa avaimet tulevat
					$pos = array_search($column, $otsikot);
					$indeksi[] = $pos;
					$tarkea++; //
				}
				if (in_array($column, $kielletyt)) {
					// katotaan ettei kiellettyjä sarakkkeita muuteta
					$viesti .= t("Sarake").": $column ".t("on kielletty sarake")."!<br>";
					$kielletty++;
				}

				//asiakashinta ja asiakasalennus keisseissä ei voida laittaa pakollisiin kenttiin näitä koska riittää että jompikumpi on annettu
				if ($table == 'asiakashinta' or $table == 'asiakasalennus') {
					if ($column == 'ASIAKAS_RYHMA' or $column == 'YTUNNUS') {
						$ashinaleas ++;
					}
					if ($column == 'RYHMA' or $column == 'TUOTENO') {
						$ashinaletuo ++;
					}
				}
			}

			if ($column == "TOIMINTO") {
				//TOIMINTO sarakkeen positio tiedostossa
				$postoiminto = (string) array_search($column, $otsikot);
			}
		}
	}

	// oli virheellisiä sarakkeita tai pakollisia ei löytynyt..
	if ($vikaa != 0 or $tarkea != count($pakolliset) or $postoiminto == 'X' or $kielletty > 0) {
		// suljetaan avattu faili.. kilttiä!
		fclose ($file);
		// ja kuollaan pois

		if ($vikaa != 0) {
			echo "<font class='error'>".t("Vääriä sarakkeita tai yritit muuttaa yhtio/tunnus saraketta")."!</font><br>";
		}
		if ($tarkea != count($pakolliset)) {
			echo "<font class='error'>".t("Pakollisia/tärkeitä kenttiä puuttuu")."! ( ";
			foreach ($pakolliset as $apupako) {
				echo "$apupako ";
			}
			echo " )</font><br>";
		}
		if ($postoiminto == 'X') {
			echo "<font class='error'>".t("Toiminto sarake puuttuu")."!</font><br>";
		}
		if ($kielletty > 0) {
			echo "<font class='error'>".t("Yrität päivittää kiellettyjä sarakkeita")."!</font><br>";
		}
		die("<font class='error'>".t("Virheitä löytyi. Ei voida jatkaa")."!<br></font>");
	}

	//pitää kuolla pois jos ollaan annettu molemmat
	if ($table == 'asiakashinta' or $table == 'asiakasalennus') {
		if ($ashinaleas == 0 or $ashinaletuo == 0) {
			// suljetaan avattu faili.. kilttiä!
			fclose ($file);
			// ja kuollaan pois
			die("<br><br><font class='error'>".t("Virheitä löytyi. Sinun on annettava joko ytunnuksen tai asiakasryhmän, ja tuotenumeron tai tuotteen alennusryhmän")."!<br></font>");
		}
	}

	echo "<br><font class='message'>".t("Tiedosto ok, aloitetaan päivitys")."...<br></font>";
	flush();

	// luetaan tiedosto loppuun...
	$rivi = fgets($file);

	while (!feof($file)) {
		$hylkaa    = 0;
		$tila      = "";
		$tee       = "";
		$eilisataeikamuuteta = "";

		//asiakashinta/asiakasalennus spessuja
		$chasiakas_ryhma = '';
		$chytunnus = '';
		$chryhma = '';
		$chtuoteno = '';
		$chalkupvm = '0000-00-00';
		$chloppupvm = '0000-00-00';
		$ashinaleas = 0;
		$ashinaletuo = 0;
		$and = '';

		if ($eiyhtiota == "") {
			$valinta   = " YHTIO='$kukarow[yhtio]'";
		}
		elseif($eiyhtiota == "TRIP") {
			$valinta   = " TUNNUS>0 ";
		}

		// luetaan rivi tiedostosta..
		$poista	 = array("'", "\\");
		$rivi	 = str_replace($poista,"",$rivi);
		$rivi	 = explode("\t", trim($rivi));

		for($j=0; $j<count($indeksi); $j++) {
			if ($otsikot[$indeksi[$j]] == "TUOTENO") {

				$tuoteno = trim($rivi[$indeksi[$j]]);

				$valinta .= " and TUOTENO='$tuoteno'";
			}
			elseif ($table == 'sanakirja' and $otsikot[$indeksi[$j]] == "FI") {
				// jos ollaan mulkkaamassa RU tai EE ni tehdään utf-8 -> latin-1 konversio FI kentällä
				if (in_array("RU", $otsikot) or in_array("EE", $otsikot)) {
					//$rivi[$r] = recode_string("utf-8..latin1", $rivi[$r]);
					$rivi[$indeksi[$j]] = iconv("UTF-8", "ISO-8859-1", $rivi[$indeksi[$j]]);

					$valinta .= " and ".$otsikot[$indeksi[$j]]."='".trim($rivi[$indeksi[$j]])."'";
				}
			}
			elseif ($table == 'tuotepaikat' and $otsikot[$indeksi[$j]] == "OLETUS") {
				//ei haluta tätä tänne
			}
			else {
				$valinta .= " and ".$otsikot[$indeksi[$j]]."='".trim($rivi[$indeksi[$j]])."'";
			}
			// jos tieto puuttuu kokonaan
			if (strlen(trim($rivi[$indeksi[$j]])) == 0 and $tyhjatok != "yes") {
				$tila = 'ohita';
			}
		}

		// jos ei ole puuttuva tieto etsitään riviä
		if($tila != 'ohita') {

			$query = "	SELECT tunnus
						FROM $table
						WHERE $valinta";
			$fresult = mysql_query($query) or pupe_error($query);
			
			if (strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
				if (mysql_num_rows($fresult) != 0 ) {
					if ($table != 'asiakasalennus' and $table != 'asiakashinta') {
						echo t("Rivi on jo olemassa, ei voida perustaa uutta!")." $valinta<br>";
						$tila = 'ohita';
					}
				}
			}
			elseif (strtoupper(trim($rivi[$postoiminto])) == 'MUUTA') {
				if (mysql_num_rows($fresult) == 0) {
					if ($table != 'asiakasalennus' and $table != 'asiakashinta') {
						echo t("Riviä ei voida muuttaa, koska sitä ei löytynyt!")." $valinta<br>";
						$tila = 'ohita';
					}
				}
			}
			else {
					echo " ".t("Riviä ei voida käsitellä koska siltä puuttuu toiminto!")." $valinta<br>";
					$tila = 'ohita';
			}
		}
		else {
			echo t("Pakollista tietoa puuttuu/tiedot ovat virheelliset!")." $valinta<br>";
		}

		// lisätään rivi
		if($tila != 'ohita') {
			if (strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
				if ($eiyhtiota == "") {
					$query = "INSERT into $table SET YHTIO='$kukarow[yhtio]' ";
				}
				elseif ($eiyhtiota == "TRIP") {
					$query = "INSERT into $table SET tunnus=0 ";
				}
			}
			if (strtoupper(trim($rivi[$postoiminto])) == 'MUUTA') {
				if ($eiyhtiota == "") {
					$query = "UPDATE $table SET YHTIO='$kukarow[yhtio]' ";
      			}
				elseif ($eiyhtiota == "TRIP") {
					$query = "UPDATE $table SET tunnus=tunnus ";
      			}
			}

			for ($r=0; $r<count($otsikot); $r++) {
				if ($r != $postoiminto) {
					$rivi[$r] = trim($rivi[$r]);

					if(substr($ityyppi[$r],0,7) == "decimal") {
						//korvataan decimal kenttien pilkut pisteillä...
						$rivi[$r] = str_replace(",",".",$rivi[$r]);
					}

					// tehdään riville oikeellisuus tsekkejä
					if ($otsikot[$r] == 'TULLINIMIKE1') {
						// jos ollaan syötetty tullinimike niin sen pitää olla oikein
						if ((int) $rivi[$r] != 0) {
							$cnque = "select cn from tullinimike where cn = '$rivi[$r]'";
							$cnres = mysql_query($cnque) or pupe_error($cnque);

							if (mysql_num_rows($cnres) == 0) {
								//Kokeillaan vielä yhtä tasoa ylempää ja ollaan käyttäjäystävällisiä
								$varafat = $rivi[$r];
								$rivi[$r] = substr($rivi[$r],0,-2)."00";
								
								$cnque = "select cn from tullinimike where cn = '$rivi[$r]'";
								$cnres = mysql_query($cnque) or pupe_error($cnque);
								
								if (mysql_num_rows($cnres) == 0) {
									$hylkaa++; // ei päivitetä tätä riviä
									echo t("Tullinimike")." '$rivi[$r]' ".t("on väärin! Riviä ei päivitetty/lisätty")."!<br>";
								}
								else {
									echo t("Tullinimike")." '$varafat' ".t("on väärin! Se muokattiin muotoon:")." ".$rivi[$r]."!<br>";	
								}
							}
						}
					}

					// tehdään riville oikeellisuus tsekkejä aina jos otsikk on tuoteno, paitsi asiakasalennus ja asiakashinta tapauksissa, koska tää joudutaan silloin tekemään toisella tavalla muualla
					if ($otsikot[$r] == 'TUOTENO' and  ($table != 'tuote' or strtoupper(trim($rivi[$postoiminto])) == 'MUUTA') and $table != 'asiakasalennus' and $table != 'asiakashinta') {

						$tpque = "select tunnus from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$rivi[$r]'";
						$tpres = mysql_query($tpque) or pupe_error($tpque);

						if (mysql_num_rows($tpres) == 0) {
							echo t("Tuotetta")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."!<br>";
							$hylkaa++; // ei päivitetä tätä riviä
						}
					}

					// tehdään riville oikeellisuus tsekkejä aina jos table on tuote ja kyseseessä on projekti kohde tai kustp
					if (($otsikot[$r] == 'PROJEKTI' or $otsikot[$r] == 'KOHDE' or $otsikot[$r] == 'KUSTP') and  $table == 'tuote' and $rivi[$r] != "") {

						if ($otsikot[$r] == 'PROJEKTI') $tyyppi = "P";
						if ($otsikot[$r] == 'KOHDE') 	$tyyppi = "O";
						if ($otsikot[$r] == 'KUSTP') 	$tyyppi = "K";

						$tpque = "select tunnus from kustannuspaikka where yhtio='$kukarow[yhtio]' and tyyppi='$tyyppi' and tunnus='$rivi[$r]'";
						$tpres = mysql_query($tpque) or pupe_error($tpque);

						if (mysql_num_rows($tpres) == 0) {
							echo t("$otsikot[$r]")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."!<br>";
							$hylkaa++; // ei päivitetä tätä riviä
						}
					}

					if (($otsikot[$r] == 'OSASTO' or $otsikot[$r] == 'TRY') and  $table == 'tuote' and $rivi[$r] != "") {

						$tpque = "select tunnus from avainsana where yhtio='$kukarow[yhtio]' and laji='$otsikot[$r]' and selite='$rivi[$r]'";
						$tpres = mysql_query($tpque) or pupe_error($tpque);

						if (mysql_num_rows($tpres) == 0) {
							echo t("$otsikot[$r]")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."!<br>";
							$hylkaa++; // ei päivitetä tätä riviä
						}
					}

					if (($otsikot[$r] == 'OSASTO' or $otsikot[$r] == 'RYHMA' or $otsikot[$r] == 'PIIRI') and  $table == 'asiakas' and $rivi[$r] != "") {

						if ($otsikot[$r] == 'OSASTO') 	$tyyppi = "ASIAKASOSASTO";
						if ($otsikot[$r] == 'RYHMA') 	$tyyppi = "ASIAKASRYHMA";
						if ($otsikot[$r] == 'PIIRI') 	$tyyppi = "PIIRI";

						$tpque = "select tunnus from avainsana where yhtio='$kukarow[yhtio]' and laji='$tyyppi' and selite='$rivi[$r]'";
						$tpres = mysql_query($tpque) or pupe_error($tpque);

						if (mysql_num_rows($tpres) == 0) {
							echo t("$otsikot[$r]")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."!<br>";
							$hylkaa++; // ei päivitetä tätä riviä
						}
					}

					// tehdään riville oikeellisuus tsekkejä
					if ($table == 'yhteensopivuus_tuote' and $otsikot[$r] == 'ATUNNUS') {

						$tpque = "select tunnus from yhteensopivuus_auto where yhtio='$kukarow[yhtio]' and tunnus='$rivi[$r]'";
						$tpres = mysql_query($tpque) or pupe_error($tpque);

						if (mysql_num_rows($tpres) == 0) {
							echo t("Automallia")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."!<br>";
							$hylkaa++; // ei päivitetä tätä riviä
						}
					}

					// tehdään riville oikeellisuus tsekkejä
					if ($table == 'tuotepaikat' and $otsikot[$r] == 'OLETUS') {
						// $tuoteno pitäs olla jo aktivoitu ylhäällä
						// haetaan tuotteen varastopaikkainfo
						$tpque = "	select sum(if(oletus='X',1,0)) oletus, sum(if(oletus='X',0,1)) regular
									from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
						$tpres = mysql_query($tpque) or pupe_error($tpque);

						if (mysql_num_rows($tpres) == 0) {
							$rivi[$r] = "X"; // jos yhtään varastopaikkaa ei löydy, pakotetaan oletus
							echo t("Tuotteella")." '$tuoteno' ".t("ei ole yhtään varastopaikkaa, pakotetaan tästä oletus").".<br>";
						}
						else {
							$tprow = mysql_fetch_array($tpres);
							if ($rivi[$r] != '' and $tprow['oletus'] > 0) {
								$rivi[$r] = ""; // tällä tuotteella on jo oletuspaikka, nollataan tämä
								echo t("Tuotteella")." '$tuoteno' ".t("on jo oletuspaikka, ei päivitetty oletuspaikkaa")."!<br>";
							}
							elseif ($rivi[$r] == '' and $tprow['oletus'] == 0) {
								$rivi[$r] = "X"; // jos yhtään varastopaikkaa ei löydy, pakotetaan oletus
								echo t("Tuotteella")." '$tuoteno' ".t("ei ole yhtään oletuspaikkaa! Tätä EI PITÄISI tapahtua! Tehdään nyt tästä oletus").".<br>";
							}
						}
					}

					if ($table == 'tuote' and ($otsikot[$r] == 'EPAKURANTTI1PVM' or $otsikot[$r] == 'EPAKURANTTI2PVM')) {

						// $tuoteno pitäs olla jo aktivoitu ylhäällä
						if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikot[$r] == 'EPAKURANTTI1PVM') {
							$tee = "puolipaalle";
						}
						elseif ($tee == "") {
							$tee = "pois";
						}

						if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikot[$r] == 'EPAKURANTTI2PVM') {
							$tee = "paalle";
						}
						elseif ($tee == "") {
							$tee = "pois";
						}

						$eilisataeikamuuteta = "joo";
					}

					// tehdään riville oikeellisuus tsekkejä
					if ($table == 'sanakirja' and $otsikot[$r] == 'FI') {
						// jos ollaan mulkkaamassa RU tai EE ni tehdään utf-8 -> latin-1 konversio FI kentällä
						 if (in_array("RU", $otsikot) or in_array("EE", $otsikot)) {
							//$rivi[$r] = recode_string("utf-8..latin1", $rivi[$r]);
							$rivi[$r] = iconv("UTF-8", "ISO-8859-1", $rivi[$r]);
						}
					}

					// tehdään riville oikeellisuus tsekkejä
					if ($table == 'tuotteen_toimittajat' and $otsikot[$r] == 'TOIMITTAJA') {

						$tpque = "select tunnus from toimi where yhtio='$kukarow[yhtio]' and ytunnus='$rivi[$r]' order by tunnus limit 1";
						$tpres = mysql_query($tpque) or pupe_error($tpque);

						if (mysql_num_rows($tpres) == 0) {
							echo t("Toimittajaa")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."! ".t("TUOTENO")." = $tuoteno<br>";
							$hylkaa++; // ei päivitetä tätä riviä
						}
						else {
							$tpttrow = mysql_fetch_array($tpres);
							$query .= ", liitostunnus='$tpttrow[tunnus]' ";
						}
					}

					//tarkistetaan asiakasalennus ja asiakashinta juttuja
					if ($table == 'asiakasalennus' or $table == 'asiakashinta') {
						if ($otsikot[$r] == 'RYHMA' and $rivi[$r] != '') {
							$chryhma = $rivi[$r];
							$xquery = "	SELECT tunnus
										FROM perusalennus
										WHERE yhtio='$kukarow[yhtio]' and ryhma = '$rivi[$r]'";
							$xresult = mysql_query($xquery) or pupe_error($xquery);
							if (mysql_num_rows($xresult) != 1) {
								echo t("Aleryhmää")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."! $otsikot[$r] = $rivi[$r]<br>";
								$hylkaa++; // ei päivitetä tätä riviä
							}
						}

						if ($otsikot[$r] == 'TUOTENO' and $rivi[$r] != '') {
							$chtuoteno = $rivi[$r];
							$xquery = "	SELECT tuoteno
										FROM tuote
										WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$rivi[$r]'";
							$xresult = mysql_query($xquery) or pupe_error($xquery);
							if (mysql_num_rows($xresult) != 1) {
								echo t("Tuotetta")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."! $otsikot[$r] = $rivi[$r]<br>";
								$hylkaa++; // ei päivitetä tätä riviä
							}
						}

						if ($otsikot[$r] == 'ASIAKAS_RYHMA' and $rivi[$r] != '') {
							$chasiakas_ryhma = $rivi[$r];
							$xquery = "	SELECT tunnus
										FROM avainsana
										WHERE yhtio='$kukarow[yhtio]' and laji = 'ASIAKASRYHMA' and selite = '$rivi[$r]'";
							$xresult = mysql_query($xquery) or pupe_error($xquery);
							if (mysql_num_rows($xresult) == 0) {
								echo t("Asiakasryhmää")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."! $otsikot[$r] = $rivi[$r]<br>";
								$hylkaa++; // ei päivitetä tätä riviä
							}
						}

						if ($otsikot[$r] == 'YTUNNUS' and $rivi[$r] != '') {
							$chytunnus = $rivi[$r];
							$xquery = "	SELECT ytunnus
										FROM asiakas
										WHERE yhtio='$kukarow[yhtio]' and ytunnus = '$rivi[$r]'";
							$xresult = mysql_query($xquery) or pupe_error($xquery);
							if (mysql_num_rows($xresult) == 0) {
								echo t("Asiakasta")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."! $otsikot[$r] = $rivi[$r]<br>";
								$hylkaa++; // ei päivitetä tätä riviä
							}
						}

						if (($otsikot[$r] == 'ASIAKAS_RYHMA' and trim($rivi[$r]) != '') or ($otsikot[$r] == 'YTUNNUS' and trim($rivi[$r]) != '')) {
							$ashinaleas ++;
						}
						if (($otsikot[$r] == 'RYHMA' and trim($rivi[$r]) != '') or ($otsikot[$r] == 'TUOTENO' and trim($rivi[$r]) != '')) {
							$ashinaletuo ++;
						}

						if ($otsikot[$r] == 'ALKUPVM' and trim($rivi[$r]) != '') {
							$chalkupvm = $rivi[$r];
						}

						if ($otsikot[$r] == 'LOPPUPVM' and trim($rivi[$r]) != '') {
							$chloppupvm = $rivi[$r];
						}
					}

					//tarkistetaan kuka juttuja
					if ($table == 'kuka') {
						if ($otsikot[$r] == 'SALASANA' and $rivi[$r] != '') {
							$rivi[$r]=md5(trim($rivi[$r]));
						}

						if ($otsikot[$r] == 'OLETUS_ASIAKAS' and $rivi[$r] != '') {
							$xquery = "	SELECT tunnus
										FROM asiakas
										WHERE yhtio='$kukarow[yhtio]' and tunnus = '$rivi[$r]'";
							$xresult = mysql_query($xquery) or pupe_error($xquery);
							if (mysql_num_rows($xresult) == 0) {
								$x2query = "SELECT tunnus
											FROM asiakas
											WHERE yhtio='$kukarow[yhtio]' and ytunnus = '$rivi[$r]'";
								$x2result = mysql_query($x2query) or pupe_error($x2query);
								if (mysql_num_rows($x2result) == 0) {
									echo t("Asiakasta")." '$rivi[$r]' ".t("ei löydy! Riviä ei päivitetty/lisätty")."! $otsikot[$r] = $rivi[$r]<br>";
									$hylkaa++; // ei päivitetä tätä riviä
								}
								elseif (mysql_num_rows($x2result) > 1) {
									echo t("Asiakasta")." '$rivi[$r]' ".t("löytyi monia! Riviä ei päivitetty/lisätty")."! $otsikot[$r] = $rivi[$r]<br>";
									$hylkaa++; // ei päivitetä tätä riviä
								}
								else {
									$x2row = mysql_fetch_array($x2result);
									$rivi[$r] = $x2row['tunnus'];
								}
							}
							else {
								$xrow = mysql_fetch_array($xresult);
								$rivi[$r] = $xrow['tunnus'];
							}
						}
					}

					//muutetaan riviä, silloin ei saa päivittää pakollisia kenttiä
					if (strtoupper(trim($rivi[$postoiminto])) == 'MUUTA' and (!in_array($otsikot[$r], $pakolliset) or ($table == 'asiakashinta' or $table == 'asiakasalennus'))) {
						///* Tässä on kaikki oikeellisuuscheckit *///
						if ($table=='tuotepaikat' and $otsikot[$r] == 'SALDO') {
							if ($rivi[$r] != 0 and $rivi[$r] != '') {
								$query .= ", $otsikot[$r]='$rivi[$r]' ";
							}
							elseif($rivi[$r] == 0) {
								echo t("Saldoa ei saa nollata!")."<br>";
							}
						}
						elseif ($table=='asiakashinta' and $otsikot[$r] == 'HINTA') {
							if ($rivi[$r] != 0 and $rivi[$r] != '') {
								$query .= ", $otsikot[$r]='$rivi[$r]' ";
							}
							elseif($rivi[$r] == 0) {
								echo t("Hintaa ei saa nollata!")."<br>";
							}
						}
						elseif ($table=='asiakasalennus' and $otsikot[$r] == 'ALENNUS') {
							if ($rivi[$r] != 0 and $rivi[$r] != '') {
								$query .= ", $otsikot[$r]='$rivi[$r]' ";
							}
							elseif($rivi[$r] == 0) {
								echo t("Alennusta ei saa nollata!")."<br>";
							}
						}
						elseif ($table=='avainsana' and $otsikot[$r] == 'SELITE') {
							if ($rivi[$r] != 0 and $rivi[$r] != '') {
								$query .= ", $otsikot[$r]='$rivi[$r]' ";
							}
							elseif($rivi[$r] == 0) {
								echo t("Selite ei saa olla tyhjä!")."<br>";
							}
						}
						elseif ($table=='tuotepaikat' and $otsikot[$r] == 'OLETUS') {
							//echo "Oletusta ei voi muuttaa!<br>";
						}
						else {
							if ($eilisataeikamuuteta == "") {
								$query .= ", $otsikot[$r]='$rivi[$r]' ";
							}
				  		}
					}

					//lisätään riviä
					if (strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
						if ($eilisataeikamuuteta == "") {
							$query .= ", $otsikot[$r]='$rivi[$r]' ";
						}
					}
				}
			}

			//
			if (($table == 'asiakasalennus' or $table == 'asiakashinta') and ($ashinaleas > 1 or $ashinaletuo > 1)) {
				echo t("Annoit liikaa tietoja. Riviä ei päivitetty/lisätty")."! ytunnus=$chytunnus | asiakas_ryhma=$chasiakas_ryhma | tuoteno=$chtuoteno | ryhma=$chryhma<br>";
				$hylkaa++; // ei päivitetä tätä riviä
			}
			elseif (($table == 'asiakasalennus' or $table == 'asiakashinta') and ($ashinaleas < 1 or $ashinaletuo < 1)) {
				echo t("Et antanut tarpeeksi tietoja. Riviä ei päivitetty/lisätty")."! ytunnus=$chytunnus | asiakas_ryhma=$chasiakas_ryhma | tuoteno=$chtuoteno | ryhma=$chryhma<br>";
				$hylkaa++; // ei päivitetä tätä riviä
			}

			//tarkistetaan asiakasalennus ja asiakashinta keisseissä onko tällanen rivi jo olemassa
			if ($hylkaa == 0 and ($chasiakas_ryhma != '' or $chytunnus != '') and ($chryhma != '' or $chtuoteno != '') and ($table == 'asiakasalennus' or $table == 'asiakashinta')) {
				if ($chasiakas_ryhma != '') {
					$and .= " and asiakas_ryhma = '$chasiakas_ryhma'";
				}
				if ($chytunnus != '') {
					$and .= " and ytunnus = '$chytunnus'";
				}
				if ($chryhma != '') {
					$and .= " and ryhma = '$chryhma'";
				}
				if ($chtuoteno != '') {
					$and .= " and tuoteno = '$chtuoteno'";
				}

				$and .= " and alkupvm = '$chalkupvm' and loppupvm = '$chloppupvm'";

				$aquery = "	SELECT tunnus
							FROM $table
							WHERE yhtio='$kukarow[yhtio]'$and";
				$dsresult = mysql_query($aquery) or pupe_error($aquery);

				if (mysql_num_rows($dsresult) > 0 and strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
					echo t("Rivi on jo olemassa, ei voida perustaa uutta!")." ytunnus=$chytunnus | asiakas_ryhma=$chasiakas_ryhma | tuoteno=$chtuoteno | ryhma=$chryhma | alkupvm=$chalkupvm | loppupvm=$chloppupvm<br>";
					$hylkaa ++;
				}
				elseif (mysql_num_rows($dsresult) == 0 and strtoupper(trim($rivi[$postoiminto])) == 'MUUTA') {
					echo t("Riviä ei voida muuttaa, koska sitä ei löytynyt!")." ytunnus=$chytunnus | asiakas_ryhma=$chasiakas_ryhma | tuoteno=$chtuoteno | ryhma=$chryhma | alkupvm=$chalkupvm | loppupvm=$chloppupvm<br>";
					$hylkaa ++;
				}
			}

			if (strtoupper(trim($rivi[$postoiminto])) == 'MUUTA') {
				if ($table == 'asiakasalennus' or $table == 'asiakashinta') {
					$query .= " WHERE yhtio = '$kukarow[yhtio]'";
					$query .= $and;
				}
				else {
					$query .= " WHERE ".$valinta;
				}

				$query .= " ORDER BY tunnus";
			}

			if ($hylkaa == 0) {
				$iresult = mysql_query($query) or pupe_error($query);

				// tehdään epäkunrattijutut
				if ($tee != "") {
					require("epakurantti.inc");
				}
				$lask++;
			}
		}

		// luetaan seuraava rivi failista
		$rivi = fgets($file);

	} // end while eof

	fclose($file);

	echo t("Päivitettiin")." $lask ".t("riviä")."!";

}
else
{
	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<table>
			<tr>
				<td>".t("Valitse tietokannan taulu").":</td>
				<td><select name='table'>
					<option value='tuote'>".t("Tuote")."</option>
					<option value='tuotepaikat'>".t("Tuotepaikat")."</option>
					<option value='asiakas'>".t("Asiakas")."</option>
					<option value='toimi'>".t("Toimittaja")."</option>
					<option value='tullinimike'>".t("Tullinimikeet")."</option>
					<option value='asiakashinta'>".t("Asiakashinnat")."</option>
					<option value='asiakasalennus'>".t("Asiakasalennukset")."</option>
					<option value='asiakaskommentti'>".t("Asiakaskommentit")."</option>
					<option value='perusalennus'>".t("Perusalennukset")."</option>
					<option value='avainsana'>".t("Avainsanat")."</option>
					<option value='tili'>".t("Tilikartta")."</option>
					<option value='tuoteperhe'>".t("Tuoteperheet")."</option>
					<option value='rahtimaksut'>".t("Rahtimaksut")."</option>
					<option value='yhteyshenkilo'>".t("Yhteyshenkilöt")."</option>
					<option value='tuotteen_avainsanat'>".t("Tuotteen avainsanat")."</option>
					<option value='kalenteri'>".t("Kalenteritietoja")."</option>
					<option value='yhteensopivuus_auto'>".t("Yhteensopivuus automallit")."</option>
					<option value='yhteensopivuus_tuote'>".t("Yhteensopivuus tuotteet")."</option>
					<option value='yhteensopivuus_autodata'>".t("Autodatatiedot")."</option>
					<option value='rekisteritiedot'>".t("Rekisteritiedot")."</option>
					<option value='sanakirja'>".t("Sanakirja")."</option>
					<option value='tuotteen_toimittajat'>".t("Tuotteen toimittajat")."</option>
					<option value='todo'>".t("Todo-lista")."</option>
					<option value='maksuehto'>".t("Maksuehto")."</option>
					<option value='hinnasto'>".t("Hinnasto")."</option>
					</select></td>
			</tr>

			<input type='hidden' name='tee' value='file'>

			<tr><td>".t("Valitse tiedosto").":</td>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("Lähetä")."'></td>
			</tr>

			</table>
			</form>";
}

require ("inc/footer.inc");

?>
