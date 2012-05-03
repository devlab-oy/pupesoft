<?php

	$data = $_GET;
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");

	require ("inc/connect.inc");
	require ("inc/functions.inc");

	function rest_virhe_header($viesti) {
		// Mikäli kutsutaan esimerkiksi "asiakastarkista-funktiota" ja se palauttaa tekstimuodossa virheen, niin $virhe pitää myös utf8-encodata, tai tulee 500-virhettä.
		// rest_virhe_header(utf8_encode("Tarkistusvirhe:")." ".utf8_encode($virhe[$i]));
		header("HTTP/1.0 400 Bad Request");
		echo json_encode($viesti);
		die();
	}
	
	function rest_ok_header($viesti) {
		// Malli ratkaisu:
		// rest_ok_header(utf8_encode("Päivitit asiakkaan: $muuttuja"));  
		header("HTTP/1.0 200 OK");
		echo json_encode($viesti);
		die();
	}

	// Palauttaa asiakkaan tiedot
	function rest_palauta_asiakastiedot($tunnus, $muoto) {

		global $kukarow, $yhtiorow;

		// Haetaan asiakkaan tiedot
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND tunnus = '{$tunnus}'";
		$tulos = pupe_query($query);

		if (mysql_num_rows($tulos) == 0) {
			rest_virhe_header(utf8_encode("Asiakastietoja ei löytynyt"));
		}
		elseif (mysql_num_rows($tulos) > 1) {
			rest_virhe_header(utf8_encode("Asiakastietoja haulle löytyi enemmän kuin yksi. Ei voida jatkaa"));
		}
		else {
			return $tulos;
		}
		
	}

	function rest_tilaa($params) {

		global $kukarow, $yhtiorow;

		// Hyväksytään seuraavat parametrit
		$kpl			= isset($params["kpl"])				? (float) trim($params["kpl"]) : "";
		$tilausnumero	= isset($params["tilausnumero"])	? mysql_real_escape_string(trim($params["tilausnumero"])) : "";
		$tuoteno		= isset($params["tuoteno"])			? mysql_real_escape_string(trim($params["tuoteno"])) : "";
		$tunnus			= isset($params["tunnus"])			? (int) trim($params["tunnus"]) : "";
		$kommentti		= isset($params["kommentti"])		? mysql_real_escape_string(trim($params["kommentti"])) : "";
		$toim 	 		= "RIVISYOTTO";
		
		// Määritellään luo_myyntitilausotsikko -funkkari
		require("tilauskasittely/luo_myyntitilausotsikko.inc");

		if ($tuoteno == "") {
			rest_virhe_header(utf8_encode("Tuotenumero puuttuu"));
		}

		if ($kpl <= 0) {
			rest_virhe_header(utf8_encode("Kappalemäärä ei saa olla 0 tai negatiivinen"));
		}

		// tähän haaraan ei voida edes teoriassakaan tulla.
		if ($tunnus == "" or $tunnus == 0) {
			rest_virhe_header(utf8_encode("Asiakastunnus puuttuu tai käyttäjän oletusasiakasta ei ole määritelty"));
		}
	
		// asiakas tarkistus
		// Haetaan asiakkaan tiedot
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND tunnus = '{$tunnus}'";
		$tulos = pupe_query($query);
		
		if (mysql_num_rows($tulos) == 0) {
			rest_virhe_header(utf8_encode("Asiakasta ei löytynyt järjestelmästä"));
		}
		
		// ei löytynyt tilausta tällä tunnisteella, pitää tehä uus!
		if ($tilausnumero == 0) {
			// varmistetaan, että käyttäjällä ei ole mitään kesken
			$kukarow["kesken"] = 0;

			$query  = "	UPDATE kuka
						SET kesken = 0
						WHERE yhtio = '{$kukarow["yhtio"]}'
						AND kuka 	= '{$kukarow["kuka"]}'";
			$update = pupe_query($query);

			$tilausnumero = luo_myyntitilausotsikko($toim, $tunnus, "", "", $kommentti, "", "");
		}

		$kukarow["kesken"] = $tilausnumero;

		$query = "	SELECT *
					FROM lasku
					WHERE yhtio 		= '{$kukarow["yhtio"]}'
					AND laatija 		= '{$kukarow["kuka"]}'
					AND liitostunnus 	= '{$tunnus}'
					AND tila 			= 'N'
					AND tunnus 			= '{$tilausnumero}'";
		$kesken = pupe_query($query);
		
		if (mysql_num_rows($kesken) == 0) {
			rest_virhe_header(utf8_encode("Tilausta ei löytynyt järjestelmästä"));
		}
		
		$laskurow = mysql_fetch_assoc($kesken);

		// haetaan tuotteen tiedot
		$query = "	SELECT *
					FROM tuote
					WHERE yhtio = '{$kukarow["yhtio"]}'
					AND tuoteno = '$tuoteno'";
		$tuoteres = pupe_query($query);

		if (mysql_num_rows($tuoteres) == 0) {
			rest_virhe_header(utf8_encode("Tuotetta \"{$tuoteno}\" ei löytynyt järjestelmästä"));
		}

		// tuote löytyi ok
		$trow = mysql_fetch_assoc($tuoteres);

		// Tarkistetaan saldo
		list($saldo, $hyllyssa, $myytavissa, $bool) = saldo_myytavissa($tuoteno);
		
		if ($myytavissa < $kpl) {
			rest_virhe_header(utf8_encode("Virhe. Saldo ei riitä"));
		}
		
		$ytunnus			= $laskurow["ytunnus"];
		$kpl				= $kpl;
		$tuoteno			= $trow["tuoteno"];
		$toimaika			= $laskurow["toimaika"];
		$kerayspvm			= $laskurow["kerayspvm"];
		$hinta 				= "";
		$netto				= "";
		$var				= "";
		$alv				= "";
		$paikka				= "";
		$varasto			= "";
		$rivitunnus			= "";
		$korvaavakielto		= "";
		$jtkielto 			= $laskurow["jtkielto"];
		$varataan_saldoa	= "EI";
		$kommentti			= $kommentti;

		for ($alepostfix = 1; $alepostfix <= $yhtiorow["myynnin_alekentat"]; $alepostfix++) {
			${"ale".$alepostfix} = "";
		}

		require("tilauskasittely/lisaarivi.inc");
		
		rest_ok_header($tilausnumero);

	}

	function rest_login($params) {
		
		global $kukarow, $yhtiorow; 

		// Hyväksytään seuraavat parametrit
		$user = isset($params["user"]) ? mysql_real_escape_string(trim($params["user"])) : "";
		$pass = isset($params["pass"]) ? md5($params["pass"]) : "";
		$yhtio = isset($params["yhtio"]) ? mysql_real_escape_string(trim($params["yhtio"])) : "";
		$versio	= (float) pupesoft_cleannumber($params["versio"]);

		// Tehdään tarkistukset tähän väliin.
		if (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != 'on')  rest_virhe_header(utf8_encode("Vain https on sallittu."));
		if ($versio != 0.1) rest_virhe_header(utf8_encode("Versionumero ei ole sallittu."));

		// Vasta virhetarkistuksien jälkeen.
		// haetaan ensin käyttäjätiedot, sen jälkeen yhtiön kaikki tiedot ja yhtion_parametrit

		$query = "	SELECT kuka.*
					FROM kuka
					WHERE kuka.yhtio = '{$yhtio}'
					AND kuka.kuka = '{$user}'
					AND kuka.salasana = '{$pass}'
					AND kuka.kuka !=''
					AND kuka.salasana !=''";
		$result = pupe_query($query);
		
		if (mysql_num_rows($result) == 0) {
			rest_virhe_header(utf8_encode("Syötetty käyttäjätunnus tai salasana on virheellinen"));
		}

		$kukarow = mysql_fetch_assoc($result);

		// Haetaan yhtiörow
		$yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);

	}
	
	$parametrit = array(
		'user'	=> $data["user"],
		'pass'  => $data["pass"],
		'yhtio' => $data["yhtio"],
		'versio' => $data["versio"],
	);
		
	rest_login($parametrit);
	
	if ($tyyppi != "order" and $tyyppi != "customer") rest_virhe_header(utf8_encode("Valittu tyyppi ei ole sallittu"));
		
	// Tarkistetaan "tilauspuolen" muuttujat
	if ($tyyppi == "order") {
		
		$parametrit = array(
			'tuoteno' 		=> $data["tuoteno"], 
			'kpl'			=> $data["kpl"], 
			'tunnus'		=> $data["tunnus"],
			'kommentti'		=> $data["tilauskommentti"],
			'tilausnumero'	=> $data["tilausnumero"],
		);

		rest_tilaa($parametrit);

	}

	if ($tyyppi == "customer") {
	   	if ($ytunnus == "") rest_virhe_header(utf8_encode("Y-tunnus pitää olla syötettynä"));  	// pakollinen kenttä
		
	   	// if ($ovttunnus == "")		rest_virhe_header(utf8_encode("OVT-tunnus ei voi olla tyhjää"));	// pakollinen kenttä
		// if ($nimi == "")				rest_virhe_header(utf8_encode("Nimi ei saa olla tyhjä"));
		// if ($osoite == "")			rest_virhe_header(utf8_encode("Osoite ei saa olla tyhjä"));
		// if ($postino == "")			rest_virhe_header(utf8_encode("Postinumero ei saa olla tyhjä"));
		// if ($postitp == "")			rest_virhe_header(utf8_encode("Postitoimipaikka ei saa olla tyhjä"));
		// if ($maa == "")			    rest_virhe_header(utf8_encode("Maa ei saa olla tyhjä"));
		// if ($email == "")			rest_virhe_header(utf8_encode("Sähköposti ei saa olla tyhjä"));
		// if ($puhelin == "")			rest_virhe_header(utf8_encode("Puhelinnumero ei saa olla tyhjä"));
		// if ($fakta == "")			rest_virhe_header(utf8_encode("Fakta ei saa olla tyhjä"));
		// if ($ryhma == "")			rest_virhe_header(utf8_encode("Asiakasryhmä ei saa olla tyhjä"));
		if ($valkoodi == "")			rest_virhe_header(utf8_encode("Valuutta ei ole koskaan tyhjää"));

		// if ($toim_ovttunnus == "")	rest_virhe_header(utf8_encode("Toimitusosoitteen OVT-tunnus ei saa olla tyhjä"));		// pakollinen kenttä
	   	// if ($toim_nimi == "")		rest_virhe_header(utf8_encode("Toimitusosoitteen nimi ei saa olla tyhjä"));
	   	// if ($toim_osoite == "")		rest_virhe_header(utf8_encode("Toimitusosoite ei saa olla tyhjä"));
	   	// if ($toim_postino == "")		rest_virhe_header(utf8_encode("Toimitusosoitteen postinumero ei saa olla tyhjä"));
	   	// if ($toim_postitp == "")		rest_virhe_header(utf8_encode("Toimitusosoitteen postitoimipaikka ei saa olla tyhjä"));
	   	// if ($toim_maa == "")			rest_virhe_header(utf8_encode("Toimitusosoitteen maa ei saa olla tyhjä"));

		if ($laskutus_nimi != "") {
			$virheviesti = "";
			
			 if ($laskutus_osoite == "")	$virheviesti .=" Laskutusosoitteen osoite ei saa olla tyhjä\n"; 			//rest_virhe_header(utf8_encode("Laskutusosoitteen osoite ei saa olla tyhjä"));
			 if ($laskutus_postino == "")	$virheviesti .=" Laskutusosoitteen postinumero ei saa olla tyhjä\n"; 		//rest_virhe_header(utf8_encode("Laskutusosoitteen postinumero ei saa olla tyhjä"));
			 if ($laskutus_postitp == "")	$virheviesti .=" Laskutusosoitteen postitoimipaikka ei saa olla tyhjä\n"; 	//rest_virhe_header(utf8_encode("Laskutusosoitteen postitoimipaikka ei saa olla tyhjä"));
			 if ($laskutus_maa == "")		$virheviesti .=" Laskutusosoitteen maa ei saa olla tyhjä"; 					//rest_virhe_header(utf8_encode("Laskutusosoitteen maa ei saa olla tyhjä"));
			// rest_virhe_header(utf8_encode("Laskutusosoitteen nimi ei saa olla tyhjä"));
			
			if ($virheviesti != "") rest_virhe_header(utf8_encode($virheviesti));
		}		

		// asiakkaaseen liittyvät muuttujat
		$ytunnus 			= (isset($data["ytunnus"])) ? 	utf8_encode(pupesoft_cleanstring($data["ytunnus"])) : rest_virhe_header(utf8_encode("Y-tunnus pitää olla syötettynä"));
		$ovttunnus 			= (isset($data["ovttunnus"])) ? utf8_encode(pupesoft_cleanstring($data["ovttunnus"])) : "";
		$nimi 				= (isset($data["nimi"])) ? 		utf8_encode(pupesoft_cleanstring($data["nimi"])) : "";
		$osoite 			= (isset($data["osoite"])) ? 	utf8_encode(pupesoft_cleanstring($data["osoite"])) : "";
		$postino 			= (isset($data["postino"])) ? 	utf8_encode(pupesoft_cleanstring($data["postino"])) : "";
		$postitp 			= (isset($data["postitp"])) ? 	utf8_encode(pupesoft_cleanstring($data["postitp"])) : "";
		$maa 				= (isset($data["maa"])) ? 		utf8_encode(pupesoft_cleanstring($data["maa"])) : "FI";
		$email 				= (isset($data["email"])) ? 	utf8_encode(pupesoft_cleanstring($data["email"])) : "";
		$puhelin 			= (isset($data["puhelin"])) ? 	utf8_encode(pupesoft_cleanstring($data["puhelin"])) : "";
		$fakta 				= (isset($data["fakta"])) ? 	utf8_encode(pupesoft_cleanstring($data["fakta"])) : "";
		$ryhma				= (isset($data["ryhma"])) ? 	utf8_encode(pupesoft_cleanstring($data["ryhma"])) : "";

		// Oletuksia Pupen puolelta, mikäli niitä ei syötetä, niin HardKoodataan ne tähän.
		$valkoodi			= (isset($data["valkoodi"])) ? 	utf8_encode(pupesoft_cleanstring($data["valkoodi"])) : "EUR";
		$tila				= (isset($data["tila"])) ? 		utf8_encode(pupesoft_cleanstring($data["tila"])) : "H";
		$kansalaisuus		= (isset($data["kansalaisuus"])) ? 	utf8_encode(pupesoft_cleanstring($data["kansalaisuus"])) : "FI";
		$kieli				= (isset($data["kieli"])) ? 		utf8_encode(pupesoft_cleanstring($data["kieli"])) : "FI";

		// toimitusosoite-tiedot
		$toim_ovttunnus 	= (isset($data["toim_ovttunnus"])) ? 	utf8_encode(pupesoft_cleanstring($data["toim_ovttunnus"])) : "";
		$toim_nimi 			= (isset($data["toim_nimi"])) ? 		utf8_encode(pupesoft_cleanstring($data["toim_nimi"])) : "";
		$toim_osoite 		= (isset($data["toim_osoite"])) ? 		utf8_encode(pupesoft_cleanstring($data["toim_osoite"])) : "";
		$toim_postino 		= (isset($data["toim_postino"])) ? 		utf8_encode(pupesoft_cleanstring($data["toim_postino"])) : "";
		$toim_postitp 		= (isset($data["toim_postitp"])) ? 		utf8_encode(pupesoft_cleanstring($data["toim_postitp"])) : "";
		$toim_maa 			= (isset($data["toim_maa"])) ? 			utf8_encode(pupesoft_cleanstring($data["toim_maa"])) : "FI";

		// laskutusosoite-tiedot
		$laskutus_nimi 		= (isset($data["laskutus_nimi"])) ?  	utf8_encode(pupesoft_cleanstring($data["laskutus_nimi"])): "";
		$laskutus_osoite 	= (isset($data["laskutus_osoite"])) ?  	utf8_encode(pupesoft_cleanstring($data["laskutus_osoite"])): "";
		$laskutus_postino 	= (isset($data["laskutus_postino"])) ?  utf8_encode(pupesoft_cleanstring($data["laskutus_postino"])): "";
		$laskutus_postitp 	= (isset($data["laskutus_postitp"])) ?  utf8_encode(pupesoft_cleanstring($data["laskutus_postitp"])): "";
		$laskutus_maa 		= (isset($data["laskutus_maa"])) ?  	utf8_encode(pupesoft_cleanstring($data["laskutus_maa"])): "FI";

		// Tämä on 100% asiakas-taulun simulointia arraynä. 
		$syotto_array["ytunnus"]		   	= $ytunnus; // tää ei ole ikinä tyhjää
		$syotto_array["ovttunnus"]         	= $ovttunnus;
		$syotto_array["nimi"]              	= $nimi;
		$syotto_array["osoite"]            	= $osoite;
		$syotto_array["postino"]           	= $postino;
		$syotto_array["postitp"]           	= $postitp;
		$syotto_array["maa"]               	= $maa;

		$syotto_array["toim_ovttunnus"]    	= $toim_ovttunnus;
		$syotto_array["toim_nimi"]         	= $toim_nimi;
		$syotto_array["toim_osoite"]       	= $toim_osoite;
		$syotto_array["toim_postino"]      	= $toim_postino;
		$syotto_array["toim_postitp"]      	= $toim_postitp;
		$syotto_array["toim_maa"]          	= $toim_maa;

		$syotto_array["laskutus_nimi"]     	= $laskutus_nimi;
		$syotto_array["laskutus_osoite"]   	= $laskutus_osoite;
		$syotto_array["laskutus_postino"]  	= $laskutus_postino;
		$syotto_array["laskutus_postitp"]  	= $laskutus_postitp;
		$syotto_array["laskutus_maa"]      	= $laskutus_maa;

		$syotto_array["email"]             	= $email;
		$syotto_array["puhelin"]           	= $puhelin;
		$syotto_array["fakta"]             	= $fakta;
		$syotto_array["ryhma"]             	= $ryhma;

		// Oletuksia jos ei muuta tietoa ole.
		$syotto_array["valkoodi"]          	= $valkoodi;
		$syotto_array["tila"]          		= $tila;
		$syotto_array["kansalaisuus"]		= $kansalaisuus;
	    $syotto_array["kieli"]				= $kieli;
		
		if ($toiminto == "muokkaa") {
			$paivityslause = "UPDATE asiakas SET yhtio='$kukarow[yhtio]', muuttaja='$kukarow[kuka]', muutospvm=now() ";

			$asiakas_result = rest_palauta_asiakastiedot($ytunnus, "ytunnus");	// muuta !!

			$tarkrow = mysql_fetch_assoc($asiakas_result);
			$atunnus = $tarkrow["tunnus"];

			// Tehdään oikeellisuustsekit, 0=yhtio
			for ($i=1; $i < mysql_num_fields($asiakas_result); $i++)  {

				if ($syotto_array[mysql_field_name($asiakas_result, $i)] != "") {
				 	$t[$i] = mysql_field_name($asiakas_result, $i);

					if (strtoupper($syotto_array[$t[$i]]) != strtoupper($tarkrow[$t[$i]])) {
						$paivityslause .= ", ".$t[$i] ." = '" . $syotto_array[$t[$i]]. "' ";
					}
				}
				else {
					$t[$i] = isset($tarkrow[mysql_field_name($asiakas_result, $i)]) ? $tarkrow[mysql_field_name($asiakas_result, $i)] : "";
				}

				unset($virhe);

				require ("inc/asiakastarkista.inc");
				asiakastarkista($t, $i, $asiakas_result, $atunnus, $virhe, $tarkrow);
				
				if ($virhe) rest_virhe_header(utf8_encode("Tarkistusvirhe:")." ".utf8_encode($virhe[$i]));
			}

			if ($errori == 0) {
				$update_result = pupe_query($paivityslause);
				rest_ok_header(utf8_encode("Päivitit asiakkaan: $nimi"));
			}

		}

		if ($toiminto == "lisaa") {
			$lisayslause = "INSERT into asiakas SET yhtio='$kukarow[yhtio]', muuttaja='$kukarow[kuka]', muutospvm=now() ";

			// Luodaan puskuri, jotta saadaan taulukot kuntoon
			$query = "	SELECT *
						FROM asiakas
						WHERE tunnus = ''";
			$result = pupe_query($query);
			$trow = mysql_fetch_array($result);
			$atunnus = "";

			//	Tehdään muuttujista linkit jolla luomme otsikolliset avaimet!
			for ($i=1; $i < mysql_num_fields($result)-1; $i++) {
				$t[mysql_field_name($result, $i)] = &$t[$i];
			}

			// Tehdään oikeellisuustsekit, 0=yhtio
			for ($i=1; $i < mysql_num_fields($result)-1; $i++)  {
				if ($syotto_array[mysql_field_name($result,$i)] != "") {
					$t[$i] = $syotto_array[mysql_field_name($result,$i)];

					$lisayslause .= ", ". mysql_field_name($result,$i)."='".$syotto_array[mysql_field_name($result,$i)]."' ";
					$tarkistuslause .= " AND ". mysql_field_name($result,$i)."='".$syotto_array[mysql_field_name($result,$i)]."'";
				}
				else {
					if (mysql_field_name($result,$i) == "yhtio" or mysql_field_name($result,$i) == "muuttaja" or mysql_field_name($result,$i) == "muutospvm") {
						// Skipataan nämä 3 kenttää
					}
					else {
						$t[$i] = isset($trow[mysql_field_name($result, $i)]) ? $trow[mysql_field_name($result, $i)] : "";
						$lisayslause .= ", ". mysql_field_name($result,$i)."='".trim($t[$i])."' ";
					}
				}


				unset($virhe);

				require ("../inc/asiakastarkista.inc");
				if (function_exists(asiakastarkista)) {
					asiakastarkista($t, $i, $result, $atunnus, $virhe, $trow);
				}

				if ($virhe) rest_virhe_header(utf8_encode("Tarkistusvirhe:")." ".utf8_encode($virhe[$i]));

			}

			if ($errori == 0) {
				$aquery = "SELECT * FROM asiakas where yhtio = '{$kukarow["yhtio"]}' {$tarkistuslause}";
				$asiakas_result = pupe_query($aquery);

				if (mysql_num_rows($asiakas_result) == 0) {
					$insert_result = pupe_query($lisayslause);
					rest_ok_header(utf8_encode("Lisäsit uuden asiakkaan: $nimi"));
				}
				else {
					rest_virhe_header(utf8_encode("Asiakas löytyi jo järjestelmästä"));
				}
			}
		}
	}

?>