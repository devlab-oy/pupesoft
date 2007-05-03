<?php
	// Tämä rutiini määrittelee riville hinnan ja alennuksen

	// siihen tarvitaan:
	// $laskurow[] (laskun tiedot)
	// $trow[] (select * from tuote)
	// $kukarow[] (kuka (tai vastaava))
	// $kpl (tilatava määrä)
	// $netto = N jos halutaan nettohinta
	// $debug (jos 1 niin näytetään tulos)

	// tulokset on:
	// $hinta (hinta)
	// $ale (aleprosentti)
	// if ($yhtiorow["asiakashinta_netto"] == "") jos kenttä on tyhjä niin asiakashinnat ovat nettohintoja, muuten ovat ei-nettohintoja
	// $aperuste (selkokielinen teksti mihin päädyttiin)
	// alehinta_alv jos on joku erikoialv tälle hinnaston tuotteelle

	$aperuste = "";

	// oletetaan yhtiön valuutta jos sitä ei tiedetä
	if ($laskurow["valkoodi"] == "") $laskurow["valkoodi"] = $yhtiorow["valkoodi"];

	// oletetaan tuotteen alvi
	$alehinta_alv = "";

	// jos meillä on lasku menossa ulkomaille
	if ($laskurow["maa"] != "" and $laskurow["maa"] != $yhtiorow["maakoodi"]) {

		// tutkitaan ollaanko siellä alv-rekisteröity
		$query = "select * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maakoodi='$laskurow[maa]' and vat_numero != ''";
		$alhire = mysql_query($query) or pupe_error($query);

		// ollaan alv-rekisteröity, haetaan tuotteelle oikea ALV
		if (mysql_num_rows($alhire) == 1) {
			$query = "select * from tuotteen_alv where yhtio='$kukarow[yhtio]' and maakoodi='$laskurow[maa]' and tuoteno='$trow[tuoteno]' limit 1";
			$alhire = mysql_query($query) or pupe_error($query);

			// ei löytynyt alvia, se on pakko löytyä
			if (mysql_num_rows($alhire) == 0) {
				$alehinta_alv = -999.99; // tää on näin että tiedetään että kävi huonosti ja ei anneta lisätä tuotetta
				$alv          = -999.99;
				$netto        = "";
				$hinta        = "0";
			}
			else {
				$alehi_alrow = mysql_fetch_array($alhire);
				$alehinta_alv = $alehi_alrow["alv"];
			}
		}
	}

	// haetaan asiakkaan tiedot
	$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
	$alhire = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($alhire) == 1) {
		$alehi_asrow = mysql_fetch_array($alhire);
	}
	else {
		$alehi_asrow = array();
		$aperuste .= t("Asiakasta ei löytynyt").". ";
	}

	// 1. käyttäjän syöttämä hinta/nettohinta
	if ($hinta != '') {
		// nettohinta jos netto-kentässä tulee N
		if ($netto == 'N') {
			$aperuste .= "Käyttäjän antama nettohinta ";
			$ale = 0;
		}
		else {
			$aperuste .= "Käyttäjän antama hinta ";
		}

		if (trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$hinta = round(yhtioval($hinta, $laskurow["vienti_kurssi"]),2);
		}
	}
	elseif ($hinta == '') {

		$hinta = 0;

		// 2. asiakas.ytunnus tuote.aleryhmä negatiivinen-aleprosentti (asiakkaan katemyyntihinta netto)
		if ($hinta == 0) {

			$query = "	SELECT alennus
						FROM asiakasalennus
						WHERE yhtio = '$kukarow[yhtio]'
						and ytunnus = '$laskurow[ytunnus]'
						and ytunnus != ''
						and asiakas_ryhma = ''
						and ryhma = '$trow[aleryhma]'
						and ryhma != ''
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and (alennus < 0 or left(alennus,1) = '-')
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {

				$hrow = mysql_fetch_array($hresult);

				//Tässä katotaan onko epäkuranttiutta
				$kokoepakurantti = "";

				if ($trow["epakurantti2pvm"] != "0000-00-00") {
					$trow["kehahin"] = 0;
					$kokoepakurantti = "ON";
				}
				elseif ($trow["epakurantti1pvm"] != "0000-00-00") {
					$trow["kehahin"] = $trow["kehahin"]/2;
				}

				// Kehahinta saa olla nolla vain jos tuote on kokoepäkurantti, muuten katemyynti ei onnaa
				if ($trow['kehahin'] == 0 and $kokoepakurantti == "ON") {
					$hinta 		= 0.01;
					$ale		= 0;
					$netto		= "";
					$aperuste 	.= "Katemyyntihinta (kokoepäkurantti). ";
				}
				elseif ($trow['kehahin'] > 0) {
					if ($yhtiorow['alv_kasittely']=='')
						$hinta	= round(($trow['kehahin'] * (1+($trow['alv']/100))) * (1+(abs($hrow["alennus"])/100)) ,2);
					else
						$hinta	= round(($trow['kehahin']  * (1+(abs($hrow["alennus"])/100))) ,2);

					$ale		= 0;
					$netto		= "";
					$aperuste 	.= "Katemyyntihinta. ";
				}
			}

		}

		// 3. asiakas.ryhmä tuote.aleryhmä negatiivinen-aleprosentti (asiakkaan katemyyntihinta netto)
		if ($hinta == 0) {

			$query = "	SELECT alennus
						FROM asiakasalennus
						WHERE yhtio = '$kukarow[yhtio]'
						and asiakas_ryhma = '$alehi_asrow[ryhma]'
						and asiakas_ryhma != ''
						and ryhma = '$trow[aleryhma]'
						and ryhma != ''
						and ytunnus = ''
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and (alennus < 0 or left(alennus,1) = '-')
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {

				$hrow = mysql_fetch_array($hresult);

				//Tässä katotaan onko epäkuranttiutta
				$kokoepakurantti = "";

				if ($trow["epakurantti2pvm"] != "0000-00-00") {
					$trow["kehahin"] = 0;
					$kokoepakurantti = "ON";
				}
				elseif ($trow["epakurantti1pvm"] != "0000-00-00") {
					$trow["kehahin"] = $trow["kehahin"]/2;
				}

				// Kehahinta saa olla nolla vain jos tuote on kokoepäkurantti, muuten katemyynti ei onnaa
				if ($trow['kehahin'] == 0 and $kokoepakurantti == "ON") {
					$hinta 		= 0.01;
					$ale		= 0;
					$netto		= "";
					$aperuste 	.= "Asiakasryhmä Katemyyntihinta (kokoepäkurantti). ";
				}
				elseif ($trow['kehahin'] > 0) {
					if ($yhtiorow['alv_kasittely']=='')
						$hinta	= round(($trow['kehahin'] * (1+($trow['alv']/100))) * (1+(abs($hrow["alennus"])/100)) ,2);
					else
						$hinta	= round(($trow['kehahin']  * (1+(abs($hrow["alennus"])/100))) ,2);
					$ale		= 0;
					$netto		= "";
					$aperuste 	.= "Asiakasryhmä Katemyyntihinta. ";
				}
			}

		}

		// 4. asiakas.ytunnus tuote.aleryhmä aleprosentti == 999.99 (asiakkaan myymälähinta)
		if ($hinta == 0) {

			$query = "	SELECT alennus
						FROM asiakasalennus
						WHERE yhtio = '$kukarow[yhtio]'
						and ytunnus = '$laskurow[ytunnus]'
						and ytunnus != ''
						and ryhma = '$trow[aleryhma]'
						and ryhma != ''
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and alennus = 999.99
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {

				$hrow = mysql_fetch_array($hresult);

				//Jos aleprossa  = 999.99 haetaan tuotteen myymälähinta myyntihinnan tilalle
				$query = "SELECT myymalahinta, alv FROM tuote WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$trow[tuoteno]'";
				$myymresult = mysql_query($query) or pupe_error($query);
				$myymrow = mysql_fetch_array ($myymresult);

				if ($alehinta_alv != "") $myymrow["alv"] = $alehinta_alv;

				if ($myymrow["myymalahinta"] > 0) {
					if ($yhtiorow["alv_kasittely"] != '') {
						$hinta = $myymrow["myymalahinta"]/($myymrow["alv"]/100+1);
					}
					else {
						$hinta = $myymrow["myymalahinta"];
					}
				}
				$ale		= 0;
				$netto		= "";
				$aperuste 	.= "Myymälähinta. ";
			}
		}

		// 5. hinnasto.hinta tuotteen nettohinta hinnastosta laskun valuutassa
		if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

			$query = "	SELECT hinta, laji
						FROM hinnasto
						WHERE yhtio  = '$kukarow[yhtio]'
						and tuoteno  = '$trow[tuoteno]'
						and tuoteno != ''
						and laji in ('N', 'E')
						and valkoodi = '$laskurow[valkoodi]'
						and maakoodi in ('$laskurow[maa]','')
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), maakoodi desc
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow       	= mysql_fetch_array ($hresult);
				$hinta 			= round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]),2);
				$netto			= $hrow["laji"];
				$aperuste		.= "Tuotteen hinnastohinta laskun valuutassa";
			}
		}

		// 6. hinnasto.hinta tuotteen nettohinta hinnastosta yhtiön valuutassa
		if ($hinta == 0) {

			$query = "	SELECT hinta, laji
						FROM hinnasto
						WHERE yhtio  = '$kukarow[yhtio]'
						and tuoteno  = '$trow[tuoteno]'
						and tuoteno != ''
						and laji in ('N', 'E')
						and valkoodi in ('$yhtiorow[valkoodi]','')
						and maakoodi in ('$laskurow[maa]','')
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow       	= mysql_fetch_array ($hresult);
				$hinta 			= $hrow["hinta"];
				$netto			= $hrow["laji"];
				$aperuste		.= "Tuotteen hinnastohinta yhtiön valuutassa";
			}
		}

		// 7.1 asiakas.ytunnus tuote.tuotenumero nettohinta (asiakkaan tuotteen hinta) laskun valuutassa
		if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

			$query = "	SELECT hinta
						FROM asiakashinta
						WHERE yhtio = '$kukarow[yhtio]'
						and ytunnus = '$laskurow[ytunnus]'
						and ytunnus != ''
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and valkoodi = '$laskurow[valkoodi]'
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow = mysql_fetch_array ($hresult);

				//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
				if ($yhtiorow["asiakashinta_netto"] == "E") {
					$netto	= 'E';
					$ale	= 0;
				}
				//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
				elseif ($yhtiorow["asiakashinta_netto"] == "B") {
					$netto	= '';
					$ale	= '';
				}
				//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
				else {
					$netto	= 'N';
					$ale	= 0;
				}

				$hinta 		= yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]);
				$aperuste	.= "Asiakkaan tuotteen nettohinta laskun valuutassa. ";
			}
		}

		// 7.2 asiakas.ytunnus tuote.tuotenumero nettohinta (asiakkaan tuotteen hinta) yhtiön valuutassa
		if ($hinta == 0) {

			$query = "	SELECT hinta
						FROM asiakashinta
						WHERE yhtio = '$kukarow[yhtio]'
						and ytunnus = '$laskurow[ytunnus]'
						and ytunnus != ''
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and valkoodi in ('$yhtiorow[valkoodi]','')
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow = mysql_fetch_array ($hresult);

				//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
				if ($yhtiorow["asiakashinta_netto"] == "E") {
					$netto	= 'E';
					$ale	= 0;
				}
				//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
				elseif ($yhtiorow["asiakashinta_netto"] == "B") {
					$netto	= '';
					$ale	= '';
				}
				//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
				else {
					$netto	= 'N';
					$ale	= 0;
				}

				$hinta		= $hrow[0];
				$aperuste	.= "Asiakkaan tuotteen nettohinta. ";
			}
		}

		// 8.1 asiakas.ytunnus tuote.aleryhmä nettohinta (asiakkaan tuotealeryhmän hinta) laskun valuutassa
		if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

			$query = "	SELECT hinta
						FROM asiakashinta
						WHERE yhtio = '$kukarow[yhtio]'
						and ytunnus = '$laskurow[ytunnus]'
						and ytunnus != ''
						and ryhma = '$trow[aleryhma]'
						and ryhma != ''
						and valkoodi = '$laskurow[valkoodi]'
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow = mysql_fetch_array ($hresult);

				//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
				if ($yhtiorow["asiakashinta_netto"] == "E") {
					$netto	= 'E';
					$ale	= 0;
				}
				//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
				elseif ($yhtiorow["asiakashinta_netto"] == "B") {
					$netto	= '';
					$ale	= '';
				}
				//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
				else {
					$netto	= 'N';
					$ale	= 0;
				}

				$hinta 		= yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]);
				$aperuste	.= "Asiakkaan tuotealeryhmän nettohinta laskun valuutassa. ";
			}
		}

		// 8.2 asiakas.ytunnus tuote.aleryhmä nettohinta (asiakkaan tuotealeryhmän hinta) yhtiön valuutassa
		if ($hinta == 0) {

			$query = "	SELECT hinta
						FROM asiakashinta
						WHERE yhtio = '$kukarow[yhtio]'
						and ytunnus = '$laskurow[ytunnus]'
						and ytunnus != ''
						and ryhma = '$trow[aleryhma]'
						and ryhma != ''
						and valkoodi in ('$yhtiorow[valkoodi]','')
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow = mysql_fetch_array ($hresult);

				//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
				if ($yhtiorow["asiakashinta_netto"] == "E") {
					$netto	= 'E';
					$ale	= 0;
				}
				//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
				elseif ($yhtiorow["asiakashinta_netto"] == "B") {
					$netto	= '';
					$ale	= '';
				}
				//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
				else {
					$netto	= 'N';
					$ale	= 0;
				}

				$hinta		= $hrow[0];
				$aperuste	.= "Asiakkaan tuotealeryhmän nettohinta. ";
			}
		}

		// 9.1 asiakas.ryhmä tuote.tuoteno nettohinta (asiakasaleryhmän tuotteen hinta) laskun valuutassa
		if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

			$query = "	SELECT hinta
						FROM asiakashinta
						WHERE yhtio = '$kukarow[yhtio]'
						and asiakas_ryhma = '$alehi_asrow[ryhma]'
						and asiakas_ryhma != ''
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and ytunnus = ''
						and valkoodi = '$laskurow[valkoodi]'
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow = mysql_fetch_array ($hresult);

				//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
				if ($yhtiorow["asiakashinta_netto"] == "E") {
					$netto	= 'E';
					$ale	= 0;
				}
				//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
				elseif ($yhtiorow["asiakashinta_netto"] == "B") {
					$netto	= '';
					$ale	= '';
				}
				//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
				else {
					$netto	= 'N';
					$ale	= 0;
				}

				$hinta 		= yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]);
				$aperuste	.= "Asiakasaleryhmän tuotteen nettohinta laskun valuutassa. ";
			}
		}

		// 9.2 asiakas.ryhmä tuote.tuoteno nettohinta (asiakasaleryhmän tuotteen hinta)
		if ($hinta == 0) {

			$query = "	SELECT hinta
						FROM asiakashinta
						WHERE yhtio = '$kukarow[yhtio]'
						and asiakas_ryhma = '$alehi_asrow[ryhma]'
						and asiakas_ryhma != ''
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and ytunnus = ''
						and valkoodi in ('$yhtiorow[valkoodi]','')
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow = mysql_fetch_array ($hresult);

				//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
				if ($yhtiorow["asiakashinta_netto"] == "E") {
					$netto	= 'E';
					$ale	= 0;
				}
				//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
				elseif ($yhtiorow["asiakashinta_netto"] == "B") {
					$netto	= '';
					$ale	= '';
				}
				//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
				else {
					$netto	= 'N';
					$ale	= 0;
				}

				$hinta		= $hrow[0];
				$aperuste	.= "Asiakasaleryhmän tuotteen nettohinta. ";
			}
		}

		// 10.1 asiakas.ryhmä tuote.aleryhmä nettohinta (asiakasaleryhmän tuotealeryhmän hinta)
		if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

			$query = "	SELECT hinta
						FROM asiakashinta
						WHERE yhtio = '$kukarow[yhtio]'
						and asiakas_ryhma = '$alehi_asrow[ryhma]'
						and asiakas_ryhma != ''
						and ryhma = '$trow[aleryhma]'
						and ryhma != ''
						and ytunnus = ''
						and valkoodi = '$laskurow[valkoodi]'
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow = mysql_fetch_array ($hresult);

				//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
				if ($yhtiorow["asiakashinta_netto"] == "E") {
					$netto	= 'E';
					$ale	= 0;
				}
				//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
				elseif ($yhtiorow["asiakashinta_netto"] == "B") {
					$netto	= '';
					$ale	= '';
				}
				//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
				else {
					$netto	= 'N';
					$ale	= 0;
				}

				$hinta 		= yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]);
				$aperuste	.= "Asiakasaleryhmän tuotealeryhmän nettohinta laskun valuutassa. ";
			}
		}

		// 10.2 asiakas.ryhmä tuote.aleryhmä nettohinta (asiakasaleryhmän tuotealeryhmän hinta)
		if ($hinta == 0) {

			$query = "	SELECT hinta
						FROM asiakashinta
						WHERE yhtio = '$kukarow[yhtio]'
						and asiakas_ryhma = '$alehi_asrow[ryhma]'
						and asiakas_ryhma != ''
						and ryhma = '$trow[aleryhma]'
						and ryhma != ''
						and ytunnus = ''
						and valkoodi in ('$yhtiorow[valkoodi]','')
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow = mysql_fetch_array ($hresult);

				//Asiakashinnat ovat nettohintoja, mutta niille annetaan erikoisalennus, ei normaalia alennusta
				if ($yhtiorow["asiakashinta_netto"] == "E") {
					$netto	= 'E';
					$ale	= 0;
				}
				//Asiakashinnat ovat bruttohintoja, alennuslaskentaa käytetään
				elseif ($yhtiorow["asiakashinta_netto"] == "B") {
					$netto	= '';
					$ale	= '';
				}
				//Asiakashinnat ovat nettohintoja, ei alennuksia ollenkaan
				else {
					$netto	= 'N';
					$ale	= 0;
				}

				$hinta		= $hrow[0];
				$aperuste	.= "Asiakasaleryhmän tuotealeryhmän nettohinta. ";
			}
		}

		// 11. tuote.nettohinta (tuotteen nettohinta)
		if ($hinta == 0 and $netto != 'E' and $trow['nettohinta'] > 0) {
			$hinta 			= $trow['nettohinta'];
			$aperuste 		.= "Tuotteen nettohinta. ";
			$netto 			= 'N';
			$ale			= 0;
		}

		// 12. hinnasto.hinta tuotteen bruttohinta hinnastosta laskun valuutassa
		if ($hinta == 0 and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {

			$query = "	SELECT hinta, laji
						FROM hinnasto
						WHERE yhtio  = '$kukarow[yhtio]'
						and tuoteno  = '$trow[tuoteno]'
						and tuoteno != ''
						and laji     = ''
						and valkoodi = '$laskurow[valkoodi]'
						and maakoodi in ('$laskurow[maa]','')
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), maakoodi desc
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow			= mysql_fetch_array ($hresult);
				$hinta			= round(yhtioval($hrow["hinta"], $laskurow["vienti_kurssi"]),2);
				$netto			= $hrow["laji"];
				$aperuste		.= "Tuotteen hinnastohinta laskun valuutassa";
			}
		}

		// 13. hinnasto.hinta tuotteen bruttohinta hinnastosta yhtion valuutassa
		if ($hinta == 0) {

			$query = "	SELECT hinta, laji
						FROM hinnasto
						WHERE yhtio  = '$kukarow[yhtio]'
						and tuoteno  = '$trow[tuoteno]'
						and tuoteno != ''
						and laji     = ''
						and valkoodi in ('$yhtiorow[valkoodi]','')
						and maakoodi in ('$laskurow[maa]','')
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and ((minkpl <= '$kpl' and maxkpl >= '$kpl') or (minkpl = 0 and maxkpl = 0))
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), valkoodi DESC
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow			= mysql_fetch_array ($hresult);
				$hinta 			= $hrow["hinta"];
				$netto			= $hrow["laji"];
				$aperuste		.= "Tuotteen hinnastohinta yhtiön valuutassa";
			}
		}

		// 14. tuote.myyntihinta (tuotteen bruttohinta)
		if ($hinta == 0) {
			$hinta		= $trow['myyntihinta'];
			$aperuste	.= "Tuotteen myyntihinta. ";
		}

	}

	if (substr($ale,-1) == "+") {
		// 0. käyttäjän syöttämä EURO-määräinen alennus
		$hinta_xxx = $hinta + substr($ale,0,-1);
		$ale = (1 - ($hinta/$hinta_xxx))*100;
		$hinta = $hinta_xxx;
	}
	elseif(substr($ale,-1) == "-") {
		// 0. käyttäjän syöttämä EURO-määräinen alennus
		$hinta_xxx = $hinta - substr($ale,0,-1);
		$ale = (1 - ($hinta_xxx/$hinta))*100;
	}
	elseif ($ale > 0) {
		// 0. käyttäjän syöttämä alennus
		$aperuste .= " Käyttäjän syöttämä ale";
	}
	elseif (is_numeric($ale) and $ale == 0) {
		// 0. käyttäjän syöttämä alennus
		$aperuste .= " Ei alennusta";
	}
	elseif ($ale == "" and $netto != 'N' and $netto != 'E') {

		$ale = 0;

		// 1. asiakas.ytunnus tuote.tuotenumero aleprosentti (asiakkaan tuotteen alennus)
		$query = "	SELECT alennus
					FROM asiakasalennus
					WHERE yhtio = '$kukarow[yhtio]'
					and ytunnus = '$laskurow[ytunnus]'
					and ytunnus != ''
					and tuoteno = '$trow[tuoteno]'
					and tuoteno != ''
					and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
					and alennus > 0
					and alennus <= 100
					ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
					LIMIT 1";
		$hresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($hresult) > 0) {
			$hrow = mysql_fetch_array ($hresult);
			$ale  = $hrow[0];
			$aperuste .= " Asiakkaan tuotteen alennus";
		}

		// 2. asiakas.ytunnus tuote.aleryhmä aleprosentti (asiakkaan tuotealeryhmän alennus)
		if ($ale == 0) {

			$query = "	SELECT alennus
						FROM asiakasalennus
						WHERE yhtio = '$kukarow[yhtio]'
						and ytunnus = '$laskurow[ytunnus]'
						and ytunnus != ''
						and ryhma = '$trow[aleryhma]'
						and ryhma != ''
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and alennus > 0
						and alennus <= 100
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow = mysql_fetch_array ($hresult);
				$ale  = $hrow[0];
				$aperuste .= " Asiakkaan tuotealeryhmän alennus";
			}
		}

		// 3. asiakas.ryhmä tuote.tuoteno aleprosentti (asiakasaleryhmän tuotteen alennus)
		if ($ale == 0) {

			$query = "	SELECT alennus
						FROM asiakasalennus
						WHERE yhtio = '$kukarow[yhtio]'
						and asiakas_ryhma = '$alehi_asrow[ryhma]'
						and asiakas_ryhma != ''
						and tuoteno = '$trow[tuoteno]'
						and tuoteno != ''
						and ytunnus = ''
						and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
						and alennus > 0
						and alennus <= 100
						ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
						LIMIT 1";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow = mysql_fetch_array ($hresult);
				$ale  = $hrow[0];
				$aperuste .= " Asiakasaleryhmän tuotteen alennus";
			}
		}

		// 4. asiakas.ryhmä tuote.aleryhmä aleprosentti (asiakasaleryhmän tuotealeryhmän alennus)
		if ($ale == 0) {

			for($aasi = strlen($trow["aleryhma"]); $aasi>0; $aasi--) {

				if ($aasi == strlen($trow["aleryhma"])) {
					$hakuavain_hae = "and ryhma  = '$trow[aleryhma]'";
				}
				else {
					$hakuavain_hae = "	and right(ryhma,1) = '*'
										and substring(ryhma, 1, char_length(ryhma)-1) = '".substr($trow["aleryhma"], 0, $aasi)."' ";
				}

				$query = "	SELECT alennus
							FROM asiakasalennus
							WHERE yhtio = '$kukarow[yhtio]'
							and asiakas_ryhma = '$alehi_asrow[ryhma]'
							and asiakas_ryhma != ''
							$hakuavain_hae
							and ryhma != ''
							and ytunnus = ''
							and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-99-99',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							and alennus > 0
							and alennus <= 100
							ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999)
							LIMIT 1";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) > 0) {
					$hrow = mysql_fetch_array ($hresult);
					$ale  = $hrow[0];
					$aperuste .= " Asiakasaleryhmän tuotealeryhmän alennus";
					break;
				}
			}
		}

		// 5. tuote.aleryhmä aleprosentti (tuotealeryhmän perusalennus)
		if ($ale == 0) {

			$query = "	SELECT alennus
						FROM perusalennus
						WHERE yhtio = '$kukarow[yhtio]'
						and ryhma = '$trow[aleryhma]'
						and ryhma != ''
						and alennus > 0
						and alennus <= 100";
			$hresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($hresult) > 0) {
				$hrow = mysql_fetch_array ($hresult);
				$ale  = $hrow[0];
				$aperuste .= " Tuotealeryhmän perusalennus";
			}
		}
	}

	if ($ale > 100) {
		$ale = 100;
	}
	if ($ale < 0) {
		$ale = 0;
	}

	//$debug = 1;
	if ($debug == 1) echo t("Tulin tulokseen").": $aperuste.  ALE: $ale ".t("% HINTA")." $hinta $yhtiorow[valkoodi]<br><br>";

?>
