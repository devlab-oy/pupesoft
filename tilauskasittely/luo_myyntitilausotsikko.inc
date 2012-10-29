<?php

if(!function_exists("luo_myyntitilausotsikko")) {
	function luo_myyntitilausotsikko($toim, $asiakasid, $tilausnumero = '', $myyjanro = '', $viesti = '', $kantaasiakastunnus = '', $ohjelmamoduli = '') {
		global $kukarow, $yhtiorow, $session;

		$query	= "	SELECT *
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$asiakasid'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$asiakasrow  = mysql_fetch_array($result);

			$ytunnus 			= $asiakasrow["ytunnus"];
			$nimi 			   	= $asiakasrow["nimi"];
			$nimitark 		   	= $asiakasrow["nimitark"];
			$osoite 		   	= $asiakasrow["osoite"];
			$postino 		   	= $asiakasrow["postino"];
			$postitp 		   	= $asiakasrow["postitp"];
			$maa 			   	= $asiakasrow["maa"];
			$tnimi 			   	= $asiakasrow["toim_nimi"];
			$tnimitark 		   	= $asiakasrow["toim_nimitark"];
			$tosoite 		   	= $asiakasrow["toim_osoite"];
			$tpostino 		   	= $asiakasrow["toim_postino"];
			$tpostitp 		   	= $asiakasrow["toim_postitp"];
			$toim_maa 		   	= $asiakasrow["toim_maa"];
			$laskutus_nimi		= $asiakasrow["laskutus_nimi"];
			$laskutus_nimitark	= $asiakasrow["laskutus_nimitark"];
			$laskutus_osoite	= $asiakasrow["laskutus_osoite"];
			$laskutus_postino	= $asiakasrow["laskutus_postino"];
			$laskutus_postitp	= $asiakasrow["laskutus_postitp"];
			$laskutus_maa		= $asiakasrow["laskutus_maa"];
			$verkkotunnus 	   	= $asiakasrow["verkkotunnus"];
			$piiri 				= $asiakasrow["piiri"];

			if ($kukarow['extranet'] != '' and $kukarow['hyvaksyja'] != "") {
				$eilahe = "o";
			}

			if (isset($asiakasrow["spec_ytunnus"]) and $asiakasrow["spec_ytunnus"] != '') {
				$ytunnus 				= $asiakasrow["spec_ytunnus"];
				$asiakasrow["ytunnus"] 	= $asiakasrow["spec_ytunnus"];
			}

			if (isset($asiakasrow["spec_tunnus"]) and $asiakasrow["spec_tunnus"] != '') {
				$asiakasid 				= $asiakasrow["spec_tunnus"];
				$asiakasrow["tunnus"] 	= $asiakasrow["spec_tunnus"];
			}

			$toimvv = date("Y");
			$toimkk = date("m");
			$toimpp = date("d");

			$kervv = date("Y");
			$kerkk = date("m");
			$kerpp = date("d");


			if ($toim == "TARJOUS" or $asiakasrow["vienti"] =='K') {
				$kolm_nimi 		= $asiakasrow["kolm_nimi"];
				$kolm_nimitark 	= $asiakasrow["kolm_nimitark"];
				$kolm_osoite 	= $asiakasrow["kolm_osoite"];
				$kolm_postino 	= $asiakasrow["kolm_postino"];
				$kolm_postitp 	= $asiakasrow["kolm_postitp"];
			}

			$maksuehto 		= $asiakasrow["maksuehto"];

			if ($myyjanro != "") {
				$apuqu = "	SELECT *
							from kuka use index (yhtio_myyja)
							where yhtio = '$kukarow[yhtio]'
							and myyja = '$myyjanro'
							AND myyja > 0";
				$meapu = mysql_query($apuqu) or pupe_error($apuqu);

				if (mysql_num_rows($meapu)==1) {
					$apuro = mysql_fetch_array($meapu);
					$myyja = $apuro['tunnus'];
				}
				elseif (mysql_num_rows($meapu)>1) {
					echo "<font class='error'>".t("Syöttämäsi myyjänumero")." $myyjanro ".t("löytyi usealla käyttäjällä")."!</font><br><br>";
					$myyja = 0;
				}
				else {
					echo "<font class='error'>".t("Syöttämäsi myyjänumero")." $myyjanro ".t("ei löytynyt")."!</font><br><br>";
					$myyja = 0;
				}
			}

			if (!isset($myyja) or $myyja == 0) {
				$myyja = $kukarow["tunnus"];
			}

			$alv 				= $asiakasrow["alv"];
			$ovttunnus 			= $asiakasrow["ovttunnus"];
			$toim_ovttunnus 	= $asiakasrow["toim_ovttunnus"];
			$chn 				= $asiakasrow["chn"];
			$maksuteksti 		= "";
			$tilausvahvistus	= $asiakasrow["tilausvahvistus"];
			$laskutusvkopv 		= $asiakasrow["laskutusvkopv"];
			$vienti 			= $asiakasrow["vienti"];
			$ketjutus 			= $asiakasrow["ketjutus"];
			$valkoodi 			= $asiakasrow["valkoodi"];
			$sisviesti1			= $asiakasrow["sisviesti1"];

			if ($asiakasrow["myynti_kommentti1"] != "") {
				$comments = $asiakasrow["myynti_kommentti1"];
			}

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

			// Jos on varasto setattuna, pässätään eteenpäin
			$varasto = 0;

			if (strpos($kukarow["varasto"], ',') === FALSE and (int) $kukarow["varasto"] > 0) {
				$varasto = $kukarow['varasto'];
			}

			if ($yhtiorow["splittauskielto"] != '') {
				$splittauskielto = "E";
			}
			else {
				$splittauskielto = "";
			}

			$jtkielto 		= $asiakasrow['jtkielto'];
			$toimitustapa 	= $asiakasrow["toimitustapa"];

			// Haetaan tomitustavan oletusmaksajan tiedot
			$apuqu = "	SELECT *
						from toimitustapa use index (selite_index)
						where yhtio	= '$kukarow[yhtio]'
						and selite	= '$toimitustapa'";
			$meapu = mysql_query($apuqu) or pupe_error($apuqu);
			$apuro = mysql_fetch_array($meapu);

			// Toimitusehto
			if (trim($asiakasrow["toimitusehto"]) != "") {
				$yhtiorow['oletus_toimitusehto'] = $asiakasrow["toimitusehto"];
			}

			$toimitusehto		= substr($yhtiorow['oletus_toimitusehto'], 0, 3);
			$kasitoimehto 		= substr($yhtiorow['oletus_toimitusehto'], 4);

			$toimitusehto2		= substr($yhtiorow['oletus_toimitusehto2'], 0, 3);
			$kasitoimehto2 		= substr($yhtiorow['oletus_toimitusehto2'], 4);

			$toimehto_tresult = t_avainsana("TOIMEHTO", "", " and trim(concat_ws(' ', selite, selitetark)) = trim('$asiakasrow[toimitusehto]') ");

			if (mysql_num_rows($toimehto_tresult) > 0) {
				$toimehto_row = mysql_fetch_assoc($toimehto_tresult);

				if ($toimehto_row["selitetark_3"] == "LAHETTAJAN_SOPPARI") {
					$maksaja = "K";
				}
				elseif ($toimehto_row["selitetark_3"] == "VASTAANOTTAJAN_SOPPARI") {
					$maksaja = "";
				}
				elseif ($toimehto_row["selitetark_3"] == "RAHTIVAPAA") {
					$maksaja    = "K";
					$rahtivapaa = "ON";
				}
				else {
					$maksaja = $apuro['merahti'];
				}
			}
			else {
				$maksaja = $apuro['merahti'];
			}

			if ($asiakasrow['rahtivapaa'] != '') $rahtivapaa = 'ON';
		}
		else {
			//yhtiön oletusalvi!
			$xwquery = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
			$xwtres  = mysql_query($xwquery) or pupe_error($xwquery);
			$xwtrow  = mysql_fetch_array($xwtres);

			$alv = (float) $xwtrow["selite"];

			$ytunnus = "*";

			// Jos on varasto setattuna, pässätään eteenpäin
			$varasto = 0;

			if (strpos($kukarow["varasto"], ',') === FALSE and (int) $kukarow["varasto"] > 0) {
				$varasto = $kukarow['varasto'];
			}
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
			$alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$maa' and vat_numero != ''";
			$alhire = mysql_query($alhqur) or pupe_error($alhqur);

			// ollaan alv-rekisteröity, aina kotimaa myynti ja alvillista
			if (mysql_num_rows($alhire) == 1) {

				$alhiro  = mysql_fetch_array($alhire);

				// haetaan maan oletusalvi
				$query = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='ALVULK' and selitetark!='' and selitetark_2='$maa' limit 1";
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
		if ($toim == "EXTRANET_REKLAMAATIO") {
			$tilaustyyppi = 'R';
		}
		if ($toim == "MYYNTITILI") {
			$tilaustyyppi = "M";
		}

		$jatka	= "JATKA";
		$tee	= "OTSIK";
		$override_ytunnus_check = "YES";
		$ei_kayttoliittymaa = "kylla";

		require ("otsik.inc");

		if ($session == "") {
			return $id;
		}
		else {
			return $tilausnumero;
		}
	}
}

?>