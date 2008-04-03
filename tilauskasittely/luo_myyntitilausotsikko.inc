<?php

if(!function_exists("luo_myyntitilausotsikko")) {
	function luo_myyntitilausotsikko($asiakasid, $tilausnumero = '', $myyjanro = '', $viesti = '') {
		global $kukarow, $yhtiorow, $toim, $session;

		$query	= "	SELECT *
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$asiakasid'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$asiakasrow  = mysql_fetch_array($result);

			$ytunnus 							= $asiakasrow["ytunnus"];
			$nimi 			   	   	   			= $asiakasrow["nimi"];
			$nimitark 		   	   	   			= $asiakasrow["nimitark"];
			$osoite 		   	   	   			= $asiakasrow["osoite"];
			$postino 		   	   	   			= $asiakasrow["postino"];
			$postitp 		   	   	   			= $asiakasrow["postitp"];
			$maa 			   	   	   			= $asiakasrow["maa"];
			$tnimi 			   	   	   			= $asiakasrow["toim_nimi"];
			$tnimitark 		   	   	   			= $asiakasrow["toim_nimitark"];
			$tosoite 		   	   	   			= $asiakasrow["toim_osoite"];
			$tpostino 		   	   	   			= $asiakasrow["toim_postino"];
			$tpostitp 		   	   	   			= $asiakasrow["toim_postitp"];
			$toim_maa 		   	   	   			= $asiakasrow["toim_maa"];
			$verkkotunnus 	   	   	   			= $asiakasrow["verkkotunnus"];
			$piiri 								= $asiakasrow["piiri"];

			if ($asiakasrow["spec_ytunnus"] != '') {
				$ytunnus 				= $asiakasrow["spec_ytunnus"];
				$asiakasrow["ytunnus"] 	= $asiakasrow["spec_ytunnus"];
			}

			if ($asiakasrow["spec_tunnus"] != '') {
				$asiakasid 				= $asiakasrow["spec_tunnus"];
				$asiakasrow["tunnus"] 	= $asiakasrow["spec_tunnus"];
			}

			$toimvv = date("Y");
			$toimkk = date("m");
			$toimpp = date("d");

			$kervv = date("Y");
			$kerkk = date("m");
			$kerpp = date("d");

			$maksuehto 		= $asiakasrow["maksuehto"];
			$toimitustapa 	= $asiakasrow["toimitustapa"];

			// haetaan tomitustavan oletusmaksajan tiedot
			$apuqu = "	select *
						from toimitustapa use index (selite_index)
						where yhtio='$kukarow[yhtio]' and selite='$asiakasrow[toimitustapa]'";
			$meapu = mysql_query($apuqu) or pupe_error($apuqu);
			$apuro = mysql_fetch_array($meapu);
			$maksaja = $apuro['merahti'];

			if ($myyjanro != "") {
				$apuqu = "	SELECT *
							from kuka use index (yhtio_myyja)
							where yhtio='$kukarow[yhtio]' and myyja='$myyjanro'";
				$meapu = mysql_query($apuqu) or pupe_error($apuqu);

				if (mysql_num_rows($meapu)==1) {
					$apuro = mysql_fetch_array($meapu);
					$myyja = $apuro['tunnus'];
				}
				elseif (mysql_num_rows($meapu)>1) {
					echo "<font class='error'>".t("Syöttämäsi myyjänumero")." $myyjanro ".t("löytyi usealla käyttäjällä")."!</font><br><br>";
					$myyjanro = "";
				}
				else {
					echo "<font class='error'>".t("Syöttämäsi myyjänumero")." $myyjanro ".t("ei löytynyt")."!</font><br><br>";
					$myyjanro = "";
				}
			}

			if ((int) $myyja == 0) {
				$myyja = $kukarow["tunnus"];
			}
			
			$alv 				= $asiakasrow["alv"];
			$ovttunnus 			= $asiakasrow["ovttunnus"];
			$toim_ovttunnus 	= $asiakasrow["toim_ovttunnus"];
			$chn 				= $asiakasrow["chn"];
			$maksuteksti 		= $asiakasrow[""];
			$tilausvahvistus	= $asiakasrow["tilausvahvistus"];
			$laskutusvkopv 		= $asiakasrow["laskutusvkopv"];
			$vienti 			= $asiakasrow["vienti"];
			$ketjutus 			= $asiakasrow["ketjutus"];
			$valkoodi 			= $asiakasrow["valkoodi"];
			$sisviesti1			= $asiakasrow["sisviesti1"];
			
			if ($toim == "PIKATILAUS") {
				$myynti_kommentti	= $asiakasrow["myynti_kommentti1"];
			}			

			if (trim($asiakasrow["toimitusehto"]) != "") {
				$yhtiorow['oletus_toimitusehto'] = $asiakasrow["toimitusehto"];
			}

			$toimitusehto		= substr($yhtiorow['oletus_toimitusehto'], 0, 3);
			$kasitoimehto 		= substr($yhtiorow['oletus_toimitusehto'], 4);

			$toimitusehto2		= substr($yhtiorow['oletus_toimitusehto2'], 0, 3);
			$kasitoimehto2 		= substr($yhtiorow['oletus_toimitusehto2'], 4);

			//annetaan extranet-tilaukselle aina paras prioriteetti, tämä on hyvä porkkana.
			if ($kukarow["extranet"] != '') {
				$query  = "	SELECT distinct selite
							FROM avainsana
							WHERE yhtio='$kukarow[yhtio]' and laji = 'asiakasluokka' and selite != ''
							ORDER BY 1
							LIMIT 1";
				$prioresult = mysql_query($query) or pupe_error($query);
				$priorow = mysql_fetch_array($prioresult);

				$luokka = $priorow["selite"];
			}
			else {
				$luokka	= $asiakasrow["luokka"];
			}

			$erikoisale	= $asiakasrow["erikoisale"];

			$varasto = (int) $kukarow["varasto"];

			if ($yhtiorow["splittauskielto"] != '') {
				$splittauskielto = "E";
			}
			else {
				$splittauskielto = "";
			}
		}
		else {
			//yhtiön oletusalvi!
			$xwquery = "select selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
			$xwtres  = mysql_query($xwquery) or pupe_error($xwquery);
			$xwtrow  = mysql_fetch_array($xwtres);

			$alv = (float) $xwtrow["selite"];

			$ytunnus = "WEKAROTO";
			$varasto = (int) $kukarow["varasto"];
		}

		if ($valkoodi == '') {
			$valkoodi = $yhtiorow["valkoodi"]."##";
		}
		else {
			$query = "	SELECT nimi, kurssi
						FROM valuu
						WHERE yhtio = '$kukarow[yhtio]'
						and nimi= '$valkoodi'";
			$vresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($vresult) == 1) {
				$vrow = mysql_fetch_array($vresult);

				$valkoodi = $vrow["nimi"]."##".$vrow["kurssi"];
			}
			else {
				$valkoodi = $yhtiorow["valkoodi"]."##";
			}
		}

		$alv_velvollisuus = "";

		// jos meillä on lasku menossa ulkomaille
		if ($maa != "" and $maa != $yhtiorow["maa"]) {
			// tutkitaan ollaanko siellä alv-rekisteröity
			$alhqur = "select * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$maa' and vat_numero != ''";
			$alhire = mysql_query($alhqur) or pupe_error($alhqur);

			// ollaan alv-rekisteröity, aina kotimaa myynti ja alvillista
			if (mysql_num_rows($alhire) == 1) {

				$alhiro  = mysql_fetch_array($alhire);

				// haetaan maan oletusalvi
				$query = "select selite from avainsana where yhtio='$kukarow[yhtio]' and laji='ALVULK' and selitetark!='' and selitetark_2='$maa' limit 1";
				$alhire = mysql_query($query) or pupe_error($query);

				// jos ei löydy niin mennään erroriin
				if (mysql_num_rows($alhire) == 0) {
					echo "<font class='error'>Oletus ALV-kantaa ei löydy asiakkaan maahan $maa! Ei voida jatkaa!</font><br>";
					exit;
				}
				else {
					$apuro  = mysql_fetch_array($alhire);
					// nämä tässä keisissä aina näin
					$alv = $apuro["selite"];
					$vienti = "";
					$alv_velvollisuus = $alhiro["vat_numero"];
				}
			}
		}
		
		if ($toim == "TARJOUS") {
			$tilaustyyppi = 'T';
		}

		$jatka	= "JATKA";
		$tee	= "OTSIK";
		$override_ytunnus_check = "YES";

		require ("otsik.inc");

		return $tilausnumero;
	}
}

?>