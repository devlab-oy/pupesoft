<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "jtselaus.php") !== FALSE) {
		if (@include("../inc/parametrit.inc"));
		elseif (@include("parametrit.inc"));
		else exit;

		if (isset($toim) and $toim == "ENNAKKO") {
			echo "<font class='head'>".t("Ennakkotilausrivit")."</font><hr>";
		}
		else {
			echo "<font class='head'>".t("JT rivit")."</font><hr>";
		}
	}

	if(!isset($asiakasid))          $asiakasid = "";
	if(!isset($asiakasmaa)) 		$asiakasmaa = "";
	if(!isset($asiakasno))	 		$asiakasno = "";
	if(!isset($automaaginen))       $automaaginen = "";
	if(!isset($borderlask) )        $borderlask = "";
	if(!isset($ei_limiittia))       $ei_limiittia = "";
	if(!isset($from_varastoon_inc)) $from_varastoon_inc = "";
	if(!isset($ins))                $ins = "";
	if(!isset($jarj)) 		 		$jarj = "";
	if(!isset($kpl))                $kpl = "";
	if(!isset($lapsires))           $lapsires = "";
	if(!isset($loput))              $loput = "";
	if(!isset($maa)) 		 		$maa = "";
	if(!isset($pkrow))              $pkrow = array();
	if(!isset($suorana))            $suorana = "";
	if(!isset($suoravarasto))       $suoravarasto = array();
	if(!isset($superit))            $superit = "";
	if(!isset($tee))                $tee = "";
	if(!isset($tilaus)) 	 		$tilaus = "";
	if(!isset($tilausnumero))       $tilausnumero = "";
	if(!isset($tilaus_on_jo))       $tilaus_on_jo = "";
	if(!isset($toim)) 		 		$toim = "";
	if(!isset($toimi))              $toimi = "";
	if(!isset($toimittaja))         $toimittaja = "";
	if(!isset($toimittajaid))       $toimittajaid = "";
	if(!isset($tuotemerkki)) 		$tuotemerkki = "";
	if(!isset($tuotenumero)) 		$tuotenumero = "";
	if(!isset($tuoteosasto)) 		$tuoteosasto = "";
	if(!isset($tuoteryhma))  		$tuoteryhma = "";
	if(!isset($vainvarastosta))     $vainvarastosta = "";
	if(!isset($vain_rivit))         $vain_rivit = "";
	if(!isset($varastosta))  		$varastosta = "";
	if(!isset($ytunnus)) 	 		$ytunnus = "";

	$DAY_ARRAY = array(1 => t("Ma"), t("Ti"), t("Ke"), t("To"), t("Pe"), t("La"), t("Su"));

	if (isset($_POST['korvataanko']) and $_POST['korvataanko'] == 'KORVAA') {
		$query = "	UPDATE tilausrivi
					SET tuoteno = '$_POST[korvaava]'
					WHERE yhtio = '$kukarow[yhtio]'
					AND tuoteno = '$_POST[korvattava]'
					AND tunnus = '$_POST[korvattava_tilriv]'";
		$res = pupe_query($query);
		$tee = 'JATKA';
		$_POST['korvataanko'] = '';
	}

	if ($tee != "JT_TILAUKSELLE" and $vainvarastosta != "") {
		$varastosta = array();
		$varastosta[$vainvarastosta] = $vainvarastosta;
	}

	//Extranet k�ytt�jille pakotetaan aina tiettyj� arvoja
	if ($kukarow["extranet"] != "") {
		$query  = "	SELECT *
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$kukarow[oletus_asiakas]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$asiakas = mysql_fetch_array($result);

			$toimittaja		= "";
			$toimittajaid	= "";

			$asiakasno 		= $asiakas["ytunnus"];
			$asiakasid		= $asiakas["tunnus"];

			if ($asiakas["toim_nimi"] == "") {
				$asiakasmaa = $asiakas["maa"];
			}
			else {
				$asiakasmaa = $asiakas["toim_maa"];
			}

			$jarj	 		= "tuoteno";
			$tuotenumero	= "";
			$tilaus			= "";
			$toimi			= "";
			$ei_limiittia	= "";
			$superit		= "";
			$tilaus_on_jo	= "KYLLA";

			if ($tee == "") {
				$tee = "JATKA";
			}
		}
		else {
			die("Asiakastietojasi ei l�ydy!");
		}
	}
	elseif ($tilaus_on_jo != '') {
		$query  = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[kesken]' and tila IN ('N', 'L', 'E', 'G')";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 1) {
			$asiakas = mysql_fetch_array($result);
			$asiakasmaa = $asiakas["toim_maa"];
		}
		else {
			die("Tilausta ei l�ydy!");
		}
	}

	// Haetaan tee_jt_tilaus-funktio
	require ("tee_jt_tilaus.inc");

	//JT-rivit on poimittu
	if ($oikeurow['paivitys'] == '1' and ($tee == 'POIMI' or $tee == "JT_TILAUKSELLE")) {
		foreach($jt_rivitunnus as $tunnukset) {

			$tunnusarray = explode(',', $tunnukset);

			//	Jos suoratoimitukselle annettiin kappalem��r� tehd��n laitetaan montako per��n
			if ($suoratoimpaikka[$tunnukset] != "") {

				if ($loput["tunnukset"]=="JATA") {

				}
				else{
					$montako = $kpl[$tunnukset];
				}

				$suoratoimpaikka[$tunnukset] = $suoravarastopaikka[$tunnukset]."&&&".$montako;
			}
			elseif (isset($suoratoimpaikka[$tunnukset]) and ($kpl[$tunnukset] > 0 or $loput[$tunnukset] != '')) {
				echo "<font class='message'>".t("Jos suoratoimitat tuotteita, muista valita my�s toimittaja")."!!!</font><br>";

				unset($suoratoimpaikka[$tunnukset]);
				unset($kpl[$tunnukset]);
				unset($loput[$tunnukset]);
			}

			if ($kpl[$tunnukset] > 0 or $loput[$tunnukset] != '' or $suoratoimpaikka[$tunnukset] != "") {

				// Tutkitaan hintoja ja alennuksia
				if ($tee == "JT_TILAUKSELLE" and $tila == "jttilaukseen" and $toim != "ENNAKKO" and $toim != 'SIIRTOLISTA') {
					$mista = 'jtrivit_tilaukselle.inc';
					require ("laskealetuudestaan.inc");
				}

				// Toimitetaan jtrivit
				tee_jt_tilaus($tunnukset, $tunnusarray, $kpl, $loput, $suoratoimpaikka, $tilaus_on_jo, $varastosta);

				if ($kukarow['extranet'] != '' and $tee == "JT_TILAUKSELLE") {
					unset($jarj);
				}
			}
		}

		$tee = "JATKA";
	}

	if ($oikeurow['paivitys'] == '1' and $kukarow["extranet"] == "" and $tilaus_on_jo == "" and $tee == 'TOIMITA') {
		if ($toim == "ENNAKKO") {
			$query  = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						AND laatija = '$kukarow[kuka]'
						AND alatila = 'E'
						AND tila = 'N'";
		}
		else {

			// "Osatoimitus kielletty"-tilauksella, mutta nyt kaikki JT-rivit on poimittu, joten laitetaan tilaus eteenp�in
			$query = "	SELECT lasku.tunnus tilaus,
						count(tilausrivi.tunnus) tot_riveja,
						sum(if(tilausrivi.var != 'J',1,0)) toimitettavia_riveja
						FROM lasku use index (tila_index)
						JOIN tilausrivi ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and tilausrivi.tyyppi='L'
						WHERE lasku.yhtio 	= '$kukarow[yhtio]'
						AND lasku.tila 		= 'N'
						AND lasku.alatila 	= 'U'
						GROUP BY lasku.tunnus
						HAVING tot_riveja = toimitettavia_riveja";
			$stresult = pupe_query($query);

			$ostok = "";

			while ($osatoimrow = mysql_fetch_assoc($stresult)) {
				$ostok .= $osatoimrow["tilaus"].",";
			}

			$ostok = substr($ostok, 0, -1);

			if ($ostok != "") {
				$ostok = "	UNION
							(SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							and lasku.tunnus in ($ostok))";
			}

			$query = "	(SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and tila 	= 'N'
						and alatila = 'J'
						and laatija = '$kukarow[kuka]')
						UNION
						(SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and tila 	= 'G'
						and alatila = 'P'
						and laatija = '$kukarow[kuka]')
						$ostok";
		}

		$jtrest = pupe_query($query);

		while ($laskurow = mysql_fetch_array($jtrest)) {

			$query  = "	UPDATE lasku
						SET alatila = 'A'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$laskurow[tunnus]'";
			$apure  = pupe_query($query);

			if ($toim != "ENNAKKO") {
				//menn��n aina t�nne ja sit tuolla inkiss� katotaan aiheuttaako toimenpiteit�.
				$mista = 'jtselaus';
				require ("laskealetuudestaan.inc");
			}

			if ($laskurow["tila"] == "N" and $automaaginen == "") {
				// katsotaan ollaanko tehty JT-supereita..
				require ("jt_super.inc");
				$jt_super = jt_super($laskurow["tunnus"]);

				echo "$jt_super<br><br>";

				//Pyydet��n tilaus-valmista olla echomatta mit��n
				$silent = "SILENT";
			}

			// tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow ja $yhtiorow
			$kukarow["kesken"] = $laskurow["tunnus"];

			// Oletuksena ei laiteta k�teistilauksia suoraan laskutuskeen vaikka ne muuten menisiv�t sinne.
			$kateisohitus = "X";

			// Onko yht��n ker�tt�v�� rivi�, vai oliko kaikki esim. suoratoimituksia?
			$query = "	SELECT tunnus
			 			FROM tilausrivi
						WHERE yhtio 	= '$kukarow[yhtio]'
						AND otunnus 	= '$laskurow[tunnus]'
						AND toimitettu  = ''";
			$apure = pupe_query($query);

			// Kaikki rivit toimitettu:
			if (mysql_num_rows($apure) == 0) {
				$kateisohitus = "";
				$laskurow['eilahetetta'] = 'o';
			}

			if ($laskurow['tila']== 'G') {
				$vanhatoim = $toim;
				$toim = "SIIRTOLISTA";

				require ("tilaus-valmis-siirtolista.inc");

				$toim = $vanhatoim;
				$vanhatoim = "";
			}
			else {
				$mista = "jtselaus";
				require ("tilaus-valmis.inc");
			}

			//	Katsotaan toimitettiinko jotain mist� meid�n tulee laittaa viesti�
			if (sms_jt == "kaikki_toimitettu") {
				if ($yhtiorow["varaako_jt_saldoa"] != "") {
					$sms_lisavarattu = " + alkup_tilaus.varattu";
				}
				else {
					$sms_lisavarattu = "";
				}
				//	Haetaan kaikki alkuper�iset tilaukset joilla ei ole en�� mit��n j�lkk�riss�
				$query = "	SELECT tilausrivin_lisatiedot.vanha_otunnus, sum(alkup_tilaus.jt $sms_lisavarattu) jt
							FROM tilausrivi
							JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
								AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
							LEFT JOIN tilausrivi alkup_tilaus ON (alkup_tilaus.yhtio = tilausrivin_lisatiedot.yhtio
								AND alkup_tilaus.otunnus = tilausrivin_lisatiedot.vanha_otunnus)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							AND tilausrivi.otunnus = '$laskurow[tunnus]'
							and tilausrivi.var != 'J'
							GROUP BY tilausrivin_lisatiedot.vanha_otunnus
							HAVING jt = 0 or jt IS NULL";
				$result = pupe_query($query);

				while ($row = mysql_fetch_array($result)) {

					$smsviesti = "Tilauksenne $row[vanha_otunnus] on valmis noudettavaksi. Viestiin ei tarvitse vastata. - $yhtiorow[nimi]";
					$smsnumero = "";

					//	Jos mist��n ei ole tullut numeroa koitetaan arvata se (t�ll�hetkell� se ei tule mist��n...)
					if ($smsnumero == "") {
						//	Haetaan sen orginaalilaskun tiedot, koska siell� ne on ainakin oikein
						$query = "	SELECT lasku.liitostunnus, lasku.nimitark, maksuehto.kateinen, lasku.nimi
									FROM lasku
									LEFT JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and maksuehto.tunnus = lasku.maksuehto
									WHERE lasku.yhtio = '$kukarow[yhtio]'
									AND lasku.tunnus = '$row[vanha_otunnus]'";
						$result = pupe_query($query);
						$vanhalaskurow = mysql_fetch_array($result);
						$animi = $vanhalaskurow["nimi"];

						//	Oletyksena asiakkaan on gsm-numero
						if ($smsnumero == "") {
							$query = "	SELECT gsm
										FROM asiakas
										WHERE yhtio = '$kukarow[yhtio]'
										AND tunnus = '$vanhalaskurow[liitostunnus]'";
							$result = pupe_query($query);
							$asrow = mysql_fetch_array($result);
							$n = on_puhelinnumero($asrow["gsm"]);
							if ($n != "") {
								$smsnumero = $n;
							}
						}

						//	Jos meill� on k�teismyynti voidaan otsikon nimitarkenteessa sis�llytt�� puhelinnumero
						if ($smsnumero == "" and $vanhalaskurow["kateinen"] != "" and $vanhalaskurow["nimitark"] != "") {
							$n = on_puhelinnumero($vanhalaskurow["nimitark"]);
							if ($n != "" ) {
								$smsnumero = $n;
							}
						}

						//	Ja l�hetet��n itse SMS
						if ($smsnumero!="") {
							sendSMS($smsnumero, $smsviesti, $animi);
						}
					}
				}
			}
		}

		if ($from_varastoon_inc == "") $tee = '';
	}

	// Tutkitaan onko k�ytt�j�ll� keskenolevia jt-rivej�
	if ($oikeurow['paivitys'] == '1' and $kukarow["extranet"] == "" and $tilaus_on_jo == "" and $from_varastoon_inc == "") {

		if ($toim == "ENNAKKO") {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and laatija	= '$kukarow[kuka]'
						and alatila	= 'E'
						and tila 	= 'N'";
		}
		else {
			// "Osatoimitus kielletty"-tilauksella, mutta nyt kaikki JT-rivit on poimittu, joten laitetaan tilaus eteenp�in
			$query = "	SELECT lasku.tunnus tilaus,
						count(tilausrivi.tunnus) tot_riveja,
						sum(if(tilausrivi.var != 'J',1,0)) toimitettavia_riveja
						FROM lasku use index (tila_index)
						JOIN tilausrivi ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and tilausrivi.tyyppi='L'
						WHERE lasku.yhtio 	= '$kukarow[yhtio]'
						AND lasku.tila 		= 'N'
						AND lasku.alatila 	= 'U'
						GROUP BY lasku.tunnus
						HAVING tot_riveja = toimitettavia_riveja";
			$stresult = pupe_query($query);

			$ostok = "";

			while ($osatoimrow = mysql_fetch_assoc($stresult)) {
				$ostok .= $osatoimrow["tilaus"].",";
			}

			$ostok = substr($ostok, 0, -1);

			if ($ostok != "") {
				$ostok = "	UNION
							(SELECT *
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							and lasku.tunnus in ($ostok))";
			}

			$query = "	(SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and tila 	= 'N'
						and alatila = 'J'
						and laatija = '$kukarow[kuka]')
						UNION
						(SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and tila 	= 'G'
						and alatila = 'P'
						and laatija = '$kukarow[kuka]')
						$ostok";
		}

		$stresult = pupe_query($query);

		if (mysql_num_rows($stresult) > 0) {
			echo "	<form name='valinta' action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='maa' value='$maa'>
					<input type='hidden' name='tee' value='TOIMITA'>";

			if ($toim == "ENNAKKO") {
				echo "	<font class='error'>".t("HUOM! Sinulla on toimittamattomia ennakkorivej�")."</font><br>
						<table>
						<tr>
						<td>".t("Toimita poimitut ennakko-rivit").": </td>";
			}
			else {
				echo "	<font class='error'>".t("HUOM! Sinulla on toimittamattomia jt-rivej�")."</font><br>
						<table>
						<tr>
						<td>".t("Laske alennukset uudelleen")."</td>
						<td><input type='checkbox' name='laskeuusix'></td></tr><tr>
						<td>".t("Toimita poimitut JT-rivit")."</td>";
			}

			echo "	<td><input type='submit' value='".t("Toimita")."'></td>
					</tr>
					</table>
					</form><hr>";
		}
	}

	//muokataan tilausrivi�
	if ($oikeurow['paivitys'] == '1' and $kukarow["extranet"] == "" and $tilaus_on_jo == "" and $tee == 'MUOKKAARIVI') {
		$query = "	SELECT *
					FROM tilausrivi
					WHERE tunnus = '$jt_rivitunnus' and yhtio='$kukarow[yhtio]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo t("Tilausrivi� ei l�ydy")."! $query";
			exit;
		}
		$trow = mysql_fetch_array($result);

		$query = "	DELETE from tilausrivi
					WHERE tunnus = '$jt_rivitunnus' and yhtio='$kukarow[yhtio]'";
		$result = pupe_query($query);

		$query = "	SELECT *
					FROM tuote
					WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$trow[tuoteno]'";
		$result = pupe_query($query);
		$tuoterow = mysql_fetch_array($result);

		$query  = "	SELECT *
					from lasku
					WHERE yhtio='$kukarow[yhtio]' and tunnus = '$trow[otunnus]'";
		$result = pupe_query($query);
		$laskurow = mysql_fetch_array($result);

		$tuoteno 		= $trow["tuoteno"];

		if ($toim == "ENNAKKO") {
			$kpl 		= $trow["varattu"];
		}
		else {
			if ($yhtiorow["varaako_jt_saldoa"] == "") {
				$kpl 	= $trow["jt"];
			}
			else {
				$kpl 	= $trow["jt"]+$trow["varattu"];
			}
		}

		// Tutkitaan onko t�m� myyty ulkomaan alvilla
		list(,,,$tsek_alehinta_alv,) = alehinta($laskurow, $tuoterow, $kpl, '', '', '');

		if ($tsek_alehinta_alv > 0) {
			$tuoterow["alv"] = $tsek_alehinta_alv;
		}

		if ($tuoterow["alv"] != $trow["alv"] and $yhtiorow["alv_kasittely"] == "" and $trow["alv"] < 500) {
			$hinta 		= hintapyoristys($trow["hinta"] / (1+$trow['alv']/100) * (1+$tuoterow['alv']/100));
		}
		else {
			$hinta		= $trow["hinta"];
		}

		if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
			$hinta  = hintapyoristys(laskuval($hinta, $laskurow["vienti_kurssi"]));
		}

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			${'ale'.$alepostfix} = $trow["ale{$alepostfix}"];
		}

		$toimaika 		= $trow["toimaika"];
		$kerayspvm		= $trow["kerayspvm"];
		$alv 			= $trow["alv"];
		$var	 		= $trow["var"];
		$netto			= $trow["netto"];
		$perheid		= $trow["perheid"];
		$kommentti 		= $trow["kommentti"];
		$rivinotunnus	= $trow["otunnus"];

		echo t("Muuta rivi�").":<br>";

		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tee' value='LISAARIVI'>";
		echo "<input type='hidden' name='jarj' value='$jarj'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
		echo "<input type='hidden' name='asiakasno' value='$asiakasno'>";
		echo "<input type='hidden' name='toimittaja' value='$toimittaja'>";
		echo "<input type='hidden' name='toimi' value='$toimi'>";
		echo "<input type='hidden' name='ei_limiittia' value='$ei_limiittia'>";
		echo "<input type='hidden' name='superit' value='$superit'>";
		echo "<input type='hidden' name='suorana' value='$suorana'>";
		echo "<input type='hidden' name='tuotenumero' value='$tuotenumero'>";
		echo "<input type='hidden' name='tilaus' value='$tilaus'>";
		echo "<input type='hidden' name='rivinotunnus' value='$rivinotunnus'>";
		echo "<input type='hidden' name='maa' value='$maa'>";

		if (is_array($varastosta)) {
			foreach ($varastosta as $vara) {
				$tilausnumero .= $vara."##";
			}
			$tilausnumero = substr($tilausnumero,0,-2);
		}
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";

		$aputoim = "RIVISYOTTO";

		require ('syotarivi.inc');
		exit;
	}

	//Lis�t��n muokaattu tilausrivi
	if ($oikeurow['paivitys'] == '1' and $kukarow["extranet"] == "" and $tilaus_on_jo == "" and $tee == 'LISAARIVI') {

		// lis�� p�iv�m��r�n tarkistus.
		if (checkdate($kerayskka, $keraysppa, $keraysvva)) {
			$kerayspvm = $keraysvva."-".$kerayskka."-".$keraysppa;
		}
		else {
			$kerayspvm  = date("Y-m-d");
		}

		// lis�� p�iv�m��r�n tarkistus.
		if (checkdate($toimkka, $toimppa, $toimvva)) {
			$toimaika  =  $toimvva."-".$toimkka."-".$toimppa;
		}
		else {
			$toimaika  = date("Y-m-d");
		}

		if ($kpl > 0) {
			$query = "	SELECT *
						FROM tuote
						WHERE tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$tuoteresult = pupe_query($query);

			if (mysql_num_rows($tuoteresult) == 1) {

				$trow = mysql_fetch_array($tuoteresult);

				$query = "	SELECT *
							FROM lasku
							WHERE yhtio='$kukarow[yhtio]' and tunnus='$rivinotunnus'";
				$laskures = pupe_query($query);
				$laskurow = mysql_fetch_array($laskures);

				$varataan_saldoa 			= "EI";
				$kukarow["kesken"] 			= $rivinotunnus;

				require ('lisaarivi.inc');
			}
			else {
				$varaosavirhe = t("VIRHE: Tuotetta ei l�ydy")."!<br>";
			}
		}

		$varastot = explode('##', $tilausnumero);

		foreach ($varastot as $vara) {
			$varastosta[$vara] = $vara;
		}
		$tee = "JATKA";
	}

	if ($kukarow["extranet"] == "" and $tilaus_on_jo == "" and ($tee == "" or $tee == "JATKA")) {

		if (isset($muutparametrit)) {
			list($tuotenumero, $tilaus,$jarj,$toimi,$ei_limiittia,$superit,$automaaginen,$ytunnus,$asiakasno,$toimittaja,$suorana,$tuoteosasto,$tuoteryhma,$tuotemerkki,$maa) = explode('#', $muutparametrit);

			$varastot = explode('##', $tilausnumero);

			foreach ($varastot as $vara) {
				$varastosta[$vara] = $vara;
			}
		}

		$muutparametrit = "$tuotenumero#$tilaus#$jarj#$toimi#$ei_limiittia#$superit#$automaaginen#$ytunnus#$asiakasno#$toimittaja#$suorana#$tuoteosasto#$tuoteryhma#$tuotemerkki#$maa#";

		if (is_array($varastosta)) {
			foreach ($varastosta as $vara) {
				$tilausnumero .= $vara."##";
			}
			$tilausnumero = substr($tilausnumero,0,-2);
		}

		if ($ytunnus != '' or is_array($varastosta)) {
			if ($ytunnus != '' and !isset($ylatila) and is_array($varastosta)) {
				require ("inc/kevyt_toimittajahaku.inc");

				if ($ytunnus != '') {
					$toimittaja = $ytunnus;
					$tee = "JATKA";
				}
			}
			elseif ($ytunnus != '' and isset($ylatila)) {
				$tee = "JATKA";
			}
			elseif (is_array($varastosta)) {
				$tee = "JATKA";
			}
			else {
				$tee = "";
			}
		}
		$muutparametrit = "$tuotenumero#$tilaus#$jarj#$toimi#$ei_limiittia#$superit#$automaaginen#$ytunnus#$asiakasno#$toimittaja#$suorana#$tuoteosasto#$tuoteryhma#$tuotemerkki#$maa#";

		if (is_array($varastosta)) {
			foreach ($varastosta as $vara) {
				$tilausnumero .= $vara."##";
			}
			$tilausnumero = substr($tilausnumero,0,-2);
		}

		if ($asiakasno != '' and $tee == "JATKA") {
			$muutparametrit .= $ytunnus;

			if ($asiakasid == "") {
				$ytunnus = $asiakasno;
			}

			require ("inc/asiakashaku.inc");

			if ($ytunnus != '') {
				$tee = "JATKA";
				$asiakasno = $ytunnus;
				$ytunnus = $toimittaja;
			}
			else {
				$asiakasno = $ytunnus;
				$ytunnus = $toimittaja;

				$tee = "";
			}
		}
	}

	if ($tee == "JATKA") {

		$tuotelisa      = "";
		$laskulisa      = "";
		$toimittajalisa = "";
		$tilausrivilisa = "";

		if ($toimittaja != '') {
			$toimittajalisa .= " JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio and tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus = '$toimittajaid') ";
		}

		if ($tilaus_on_jo == "KYLLA" and $asiakasid != '') {
			$laskulisa .= " and lasku.liitostunnus = '$asiakasid' ";
		}
		elseif ($tilaus_on_jo == "" and $asiakasid != '') {
			$laskulisa .= " and lasku.liitostunnus = '$asiakasid' ";
	  	}
		elseif ($tilaus_on_jo == "" and $asiakasno != '') {
			$laskulisa .= " and lasku.ytunnus = '$asiakasno' ";
		}

		if ($tuotenumero != '') {
			$tilausrivilisa .= " and tilausrivi.tuoteno = '$tuotenumero' ";
		}

		if ($tilaus != '') {
			$tilausrivilisa .= " and tilausrivi.otunnus = '$tilaus' ";
		}

		if ($vain_rivit != '') {
			$tilausrivilisa .= " and tilausrivi.tunnus in ($vain_rivit) ";
		}

		if ($tilaus_on_jo == "KYLLA" and $toim == 'SIIRTOLISTA' and $laskurow['clearing'] != '') {
		 	$laskulisa .= " and lasku.clearing = '$laskurow[clearing]' ";
		}

		if ($tuoteryhma != '') {
			$tuotelisa .= " and tuote.try = '$tuoteryhma' ";
		}

		if ($tuoteosasto != '') {
			$tuotelisa .= " and tuote.osasto = '$tuoteosasto' ";
		}

        if ($ei_tehdastoimitus_tuotteita != "") {
            $tuotelisa .= " and tuote.status != 'T' ";
        }

		if ($tuotemerkki != '') {
			$tuotelisa .= " and tuote.tuotemerkki = '$tuotemerkki' ";
		}

		if ($maa != '') {
			$laskulisa .= " and lasku.maa = '$maa' ";
		}

		if ($automaaginen != '' or $ei_limiittia != '') {
			$limit = "";
		}
		else {
			$limit = " LIMIT 1000 ";
		}

		$saatanat_chk = array();

		switch($jarj) {
			case "ytunnus":
				$order = " ORDER BY lasku.ytunnus, tuote.tuoteno";
				break;
			case "tuoteno":
				$order = " ORDER BY tuote.tuoteno, lasku.ytunnus";
				break;
			case "luontiaika":
				$order = " ORDER BY lasku.luontiaika, tuote.tuoteno, lasku.ytunnus";
				break;
			case "toimaika":
				$order = " ORDER BY lasku.toimaika, tuote.tuoteno, lasku.ytunnus";
				break;
			default:
				$order = " ORDER BY lasku.tunnus";
				break;
		}

		if ($yhtiorow["varaako_jt_saldoa"] != "") {
			$lisavarattu = " + tilausrivi.varattu";
		}
		else {
			$lisavarattu = "";
		}

		if ($yhtiorow["saldo_kasittely"] == "T") {
			$saldoaikalisa = date("Y-m-d");
		}
		else {
			$saldoaikalisa = "";
		}

		$summarajauslisa = '';
		$summarajausfail = '';

		if (in_array($jarj, array("ytunnus","tuoteno","luontiaika","toimaika"))) {

			$query_ale_lisa = generoi_alekentta('M');

			if (isset($summarajaus) and $summarajaus != '') {
				$summarajaus = (float) $summarajaus;

				// jos on annettuna summarajaus, niin katsotaan ylitt��k� asiakkaan kaikkien tilausrivien yhteishinta t�m�n rajan
				// n�ytet��n vaan kaikkien summarajan ylitt�neiden asiakkaiden rivit (summarajauslisa)
				if ($toim == "ENNAKKO") {
					$query = "	SELECT lasku.liitostunnus, sum(tilausrivi.hinta * (tilausrivi.varattu + tilausrivi.jt) * {$query_ale_lisa}) hintarajaus
								FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
								JOIN lasku use index (PRIMARY) ON (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and ((lasku.tila = 'E' and lasku.alatila = 'A') or (lasku.tila = 'L' and lasku.alatila = 'X')) $laskulisa)
								JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno $tuotelisa)
								$toimittajalisa
								WHERE tilausrivi.yhtio 			= '$kukarow[yhtio]'
								and tilausrivi.tyyppi 			= 'E'
								and tilausrivi.laskutettuaika 	= '0000-00-00'
								and tilausrivi.varattu 			> 0
								and ((tilausrivi.tunnus = tilausrivi.perheid and tilausrivi.perheid2 = 0) or (tilausrivi.tunnus = tilausrivi.perheid2) or (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0))
								$tilausrivilisa
								GROUP BY lasku.liitostunnus
								HAVING hintarajaus > '$summarajaus'
								$limit";
				}
				else {
					$query = "	SELECT lasku.liitostunnus, sum(tilausrivi.hinta * (tilausrivi.varattu + tilausrivi.jt) * {$query_ale_lisa}) hintarajaus
								FROM tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
								JOIN lasku use index (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and ((lasku.tila = 'N' and lasku.alatila != '') or lasku.tila != 'N') $laskulisa)
								JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $tuotelisa)
								$toimittajalisa
								WHERE tilausrivi.yhtio 	= '$kukarow[yhtio]'
								and tilausrivi.tyyppi in ('L','G')
								and tilausrivi.var 				= 'J'
								and tilausrivi.keratty 			= ''
								and tilausrivi.uusiotunnus 		= 0
								and tilausrivi.kpl 				= 0
								and tilausrivi.jt $lisavarattu	> 0
								and ((tilausrivi.tunnus=tilausrivi.perheid and tilausrivi.perheid2=0) or (tilausrivi.tunnus=tilausrivi.perheid2) or (tilausrivi.perheid=0 and tilausrivi.perheid2=0))
								$tilausrivilisa
								GROUP BY lasku.liitostunnus
								HAVING hintarajaus > '$summarajaus'
								$limit";
				}
				$summarajausres = pupe_query($query);

				// loopataan asiakkaan tunnukset talteen
				if (mysql_num_rows($summarajausres) > 0) {
					$summarajauslisa = " and lasku.liitostunnus in (";

					while ($summarajausrow = mysql_fetch_assoc($summarajausres)) {
						$summarajauslisa .= "$summarajausrow[liitostunnus],";
					}

					$summarajauslisa = substr($summarajauslisa, 0, -1);
					$summarajauslisa .= ")";
				}
				else {
					echo "<font class='error'>",t("Hintarajauksella ei l�ytynyt yht��n tilausta"),"!</font><br/><br/>";
					$tee = '';
					$tilaus_on_jo = '';
					$from_varastoon_inc = '';
					$summarajausfail = 'fail';
				}
			}
		}

		if (in_array($jarj, array("ytunnus","tuoteno","luontiaika","toimaika")) and $summarajausfail == '') {

			$ale_query_select_lisa = generoi_alekentta_select('erikseen', 'M');

			//haetaan vain tuoteperheiden is�t tai sellaset tuotteet jotka eiv�t kuulu tuoteperheisiin
			if ($toim == "ENNAKKO") {
				$query = "	SELECT tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.tilaajanrivinro, lasku.ytunnus, tilausrivi.varattu jt,
							lasku.nimi, lasku.toim_nimi, lasku.viesti, tilausrivi.tilkpl, tilausrivi.hinta, {$ale_query_select_lisa}
							lasku.tunnus ltunnus, tilausrivi.tunnus tunnus, tuote.ei_saldoa, tilausrivi.perheid, tilausrivi.perheid2,
							tilausrivi.otunnus, lasku.clearing, lasku.varasto, tuote.yksikko, tilausrivi.toimaika ttoimaika, lasku.toimaika ltoimaika,
							lasku.toimvko, lasku.osatoimitus, lasku.valkoodi, lasku.vienti_kurssi
							FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
							JOIN lasku use index (PRIMARY) ON (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and ((lasku.tila = 'E' and lasku.alatila = 'A') or (lasku.tila = 'L' and lasku.alatila = 'X')) $laskulisa $summarajauslisa)
							JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno $tuotelisa)
							$toimittajalisa
							WHERE tilausrivi.yhtio 			= '$kukarow[yhtio]'
							and tilausrivi.tyyppi 			= 'E'
							and tilausrivi.laskutettuaika 	= '0000-00-00'
							and tilausrivi.varattu 			> 0
							and ((tilausrivi.tunnus = tilausrivi.perheid and tilausrivi.perheid2 = 0) or (tilausrivi.tunnus = tilausrivi.perheid2) or (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0))
							$tilausrivilisa
							$order
							$limit";
			}
			else {
				$query = "	SELECT tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.tilaajanrivinro, lasku.ytunnus, tilausrivi.jt $lisavarattu jt,
							lasku.nimi, lasku.toim_nimi, lasku.viesti, tilausrivi.tilkpl, tilausrivi.hinta, {$ale_query_select_lisa}
							lasku.tunnus ltunnus, tilausrivi.tunnus tunnus, tuote.ei_saldoa, tilausrivi.perheid, tilausrivi.perheid2,
							tilausrivi.otunnus, lasku.clearing, lasku.varasto, tuote.yksikko, tilausrivi.toimaika ttoimaika, lasku.toimaika ltoimaika,
							lasku.toimvko, lasku.osatoimitus, lasku.valkoodi, lasku.vienti_kurssi,
							tilausrivin_lisatiedot.tilausrivilinkki
							FROM tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
							JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
							JOIN lasku use index (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and (lasku.tila != 'N' or lasku.alatila != '') $laskulisa $summarajauslisa)
							JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $tuotelisa)
							$toimittajalisa
							WHERE tilausrivi.yhtio 	= '$kukarow[yhtio]'
							and tilausrivi.tyyppi in ('L','G')
							and tilausrivi.var 				= 'J'
							and tilausrivi.keratty 			= ''
							and tilausrivi.uusiotunnus 		= 0
							and tilausrivi.kpl 				= 0
							and tilausrivi.jt $lisavarattu	> 0
							and ((tilausrivi.tunnus=tilausrivi.perheid and tilausrivi.perheid2=0) or (tilausrivi.tunnus=tilausrivi.perheid2) or (tilausrivi.perheid=0 and tilausrivi.perheid2=0))
							$tilausrivilisa
							$order
							$limit";
			}
			$isaresult = pupe_query($query);

			$jtseluas_rivienmaara = mysql_num_rows($isaresult);

			if (mysql_num_rows($isaresult) > 0) {

				$jt_rivilaskuri = 1;

				while ($jtrow = mysql_fetch_array($isaresult)) {

					//tutkitaan onko t�m� suoratoimitusrivi
					$onkosuper = "";

					if ($jtrow["tilausrivilinkki"] > 0) {
						$query = "	SELECT tunnus
					   				FROM tilausrivi
					   				WHERE yhtio = '$kukarow[yhtio]'
									and tyyppi  = 'O'
					   				and tunnus  = '$jtrow[tilausrivilinkki]'";
						$sjtres = pupe_query($query);

						if (mysql_num_rows($sjtres) > 0) {
							$onkosuper = "ON";
						}
					}

					// ei n�ytet� suoratoimitusrivej�, ellei $superit ole ruksattu, sillon n�ytet��n pelk�st��n suoratoimitukset
					// Jos $vain_rivit muuttuja on setattu niin huomioidaan sek� normit et� suoratoimit
					if (($onkosuper == "" and $superit == "") or ($onkosuper == "ON" and $superit != "") or $vain_rivit != "") {

						$kokonaismyytavissa = 0;

						if ($jtrow["perheid"] > 0 or $jtrow["perheid2"] > 0) {
							if ($jtrow["perheid"] == 0) {
								$pklisa = " and perheid2 = '$jtrow[perheid2]'";
							}
							else {
								$pklisa = " and (perheid = '$jtrow[perheid]' or perheid2 = '$jtrow[perheid]')";
							}

							unset($perherow);

							$query = "	SELECT vanhatunnus
										FROM lasku use index (PRIMARY)
										WHERE yhtio	= '$kukarow[yhtio]'
										and tunnus	= '$jtrow[ltunnus]'";
							$vtunres = pupe_query($query);
							$vtunrow = mysql_fetch_array($vtunres);

							if ($vtunrow["vanhatunnus"] > 0) {
								$query = " 	SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
											FROM lasku use index (yhtio_vanhatunnus)
											WHERE yhtio		= '$kukarow[yhtio]'
											and vanhatunnus	= '$vtunrow[vanhatunnus]'";
								$perheresult = pupe_query($query);
								$perherow = mysql_fetch_array($perheresult);
							}

							if ($perherow["tunnukset"] != "") {
								$otunlisa = " and tilausrivi.otunnus in ($perherow[tunnukset]) ";
							}
							else {
								$otunlisa = " and tilausrivi.otunnus = '$jtrow[ltunnus]' ";
							}
						}

						// Jos tuote on tuoteperheen is�
						unset($lapsires);

						if ($toim == "ENNAKKO" and ($jtrow["perheid"] > 0 or $jtrow["perheid2"] > 0)) {
							$query = "	SELECT tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.varattu jt, tilausrivi.tilkpl, tilausrivi.hinta, {$ale_query_select_lisa}
										tilausrivi.tunnus tunnus, tuote.ei_saldoa, tilausrivi.perheid, tilausrivi.perheid2, tilausrivi.otunnus, tuote.yksikko, lasku.valkoodi, lasku.vienti_kurssi
										FROM tilausrivi use index (yhtio_otunnus)
										JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
										JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
										WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
										$otunlisa
										$pklisa
										and tilausrivi.varattu > 0
										and tilausrivi.tunnus != '$jtrow[tunnus]'
										ORDER BY tilausrivi.tunnus";
							$lapsires = pupe_query($query);
						}
						elseif ($jtrow["perheid"] > 0 or $jtrow["perheid2"] > 0) {
							$query = "	SELECT tilausrivi.tuoteno, tilausrivi.nimitys, tilausrivi.jt $lisavarattu jt, tilausrivi.tilkpl, tilausrivi.hinta, {$ale_query_select_lisa}
										tilausrivi.tunnus tunnus, tuote.ei_saldoa, tilausrivi.perheid, tilausrivi.perheid2, tilausrivi.otunnus, tuote.yksikko, lasku.valkoodi, lasku.vienti_kurssi
										FROM tilausrivi use index (yhtio_otunnus)
										JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
										JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
										WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
										$otunlisa
										$pklisa
										and tilausrivi.jt $lisavarattu > 0
										and tilausrivi.tunnus != '$jtrow[tunnus]'
										ORDER BY tilausrivi.tunnus";
							$lapsires = pupe_query($query);
						}
						unset($perherow);
						$perheok = 0;

						//K�sitelt�v�t rivitunnukset (is� ja mahdolliset lapset)
						$tunnukset = $jtrow["tunnus"].",";

						if (isset($lapsires) and mysql_num_rows($lapsires) > 0) {
							while ($perherow = mysql_fetch_array($lapsires)) {
								$lapsitoimittamatta = $perherow["jt"];

								if ($perherow["ei_saldoa"] == "") {
									foreach ($varastosta as $vara) {
										list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($perherow["tuoteno"], "JTSPEC", $vara, "", "", "", "", "", $asiakasmaa);

										if ($saldolaskenta == "hyllysaldo") {
											$lapsitoimittamatta -= $hyllyssa;
										}
										else {
											$lapsitoimittamatta -= $myytavissa;
										}

									}
								}
								else {
									$lapsitoimittamatta	= 0;
								}

								$tunnukset .= $perherow["tunnus"].",";


								if ($lapsitoimittamatta > 0) {
									//t�m�n lapsen saldo ei riit�
									$perheok++;
								}
							}
						}

						$tunnukset = substr($tunnukset, 0, -1);

						if ($jtrow["ei_saldoa"] == "") {
							foreach ($varastosta as $vara) {

								$jt_saldopvm = "";
								if ($yhtiorow["saldo_kasittely"] != "") $jt_saldopvm = date("Y-m-d");

								list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($jtrow["tuoteno"], "JTSPEC", $vara, "", "", "", "", "", $asiakasmaa, $jt_saldopvm);

								if ($saldolaskenta == "hyllysaldo") {
									$kokonaismyytavissa += $hyllyssa;
								}
								else {
									$kokonaismyytavissa += $myytavissa;
								}
							}

							//jos ei ole automaaginen ja halutaan suoratoimittaa ja omasta varastosta ei l�ydy yht��n niin katotaan suoratoimitusmahdollisuus
							if ($automaaginen == '' and $kukarow["extranet"] == '' and $onkosuper == "" and $toim != 'SIIRTOLISTA' and ($suorana != '' or $tilaus_on_jo == 'KYLLA' or count($suoravarasto)>0)) {
								$suora_tuoteno 	= $jtrow["tuoteno"];
								$suora_kpl 		= $jtrow["jt"];
								$paikatlask 	= 0;
								$paikat 		= '';
								$mista 			= 'selaus';

								if (count($suoravarasto) > 0) {
									$varastoista = implode(",",$suoravarasto);
								}
								else {
									$varastoista = "";
								}
								require ("suoratoimitusvalinta.inc");
							}
							else {
								$paikatlask = 0;
								$paikat 	= '';
							}
						}

						// Saldoa on tai halutaan n�hd� kaikki rivit tai suoratoimituspaikkoja l�ytyi
						if ($kokonaismyytavissa > 0 or $toimi == '' or $paikatlask > 0) {

							//Tulostetaan otsikot
							if ($automaaginen == '' and $jt_rivilaskuri == 1) {

								echo "<table>";
								echo "<tr>";
								echo "<th>#</th>";

								echo "<th valign='top'>".t("Tuoteno");

								if ($kukarow["resoluutio"] == 'I' or $kukarow["extranet"] != "") {
									echo "<br>".t("Nimitys");
								}

								echo "</th>";

								if ($tilaus_on_jo == "") {
									echo "<th valign='top'>".t("Ytunnus")."<br>".t("Nimi")."<br>".t("Toim_Nimi")."</th>";
								}

								if ($kukarow["resoluutio"] == 'I' or $kukarow["extranet"] != "") {
									echo "<th valign='top'>".t("Tilausnro")."<br>".t("Viesti.")."</th>";
								}

								echo "<th valign='top'>".t("JT")."<br>".t("Hinta")."<br>";

								for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
									echo t("Ale"),"{$alepostfix}<br />";
								}

								echo "</th>";

								if (count($suoravarasto) > 0 or $suorana != "") {
									echo "<th valign='top'>".t("Status")."<br>".t("Suoratoimittaja")."<br>".t("Toimaika")."</th>";
								}
								else {
									echo "<th valign='top'>".t("Status")."<br>".t("Toimaika")."</th>";
								}


								if ($oikeurow['paivitys'] == '1') {
									if ($kukarow["extranet"] == "") {
										echo "<th valign='top'>".t("Toimita")."<br>".t("kaikki")."</th>";
										echo "<th valign='top'>".t("Toimita")."<br>".t("m��r�")."</th>";
										echo "<th valign='top'>".t("Poista")."<br>".t("loput")."</th>";
										echo "<th valign='top'>".t("J�t�")."<br>".t("loput")."</th>";
										echo "<th valign='top'>".t("Mit�t�i")."<br>".t("rivi")."</th>";
										echo "<th valign='top'>".t("Hyv�ksy")."<br>".t("v�kisin")."</th>";
									}
									else {
										echo "<th valign='top'>".t("Toimita")."</th>";
										if ($kukarow["extranet"] != "" and $yhtiorow["jt_rivien_kasittely"] != 'E') {
											echo "<th valign='top'>".t("Mit�t�i")."</th>";
										}
										echo "<th valign='top'>".t("�l� tee mit��n")."</th>";
									}
								}

								echo "</tr>";

								if ($oikeurow['paivitys'] == '1') {

									echo "	<script type='text/javascript' language='JavaScript'>
											<!--
												function update_params(KORVATTAVA, KORVAAVA, TILRIVTUNNUS) {
													//alert(KORVATTAVA + ' ' + KORVAAVA + ' ' + TILRIVTUNNUS);

													document.getElementById('korvattava_tilriv').value = TILRIVTUNNUS;
													document.getElementById('korvattava').value = KORVATTAVA;
													document.getElementById('korvaava').value = KORVAAVA;
													document.getElementById('korvataanko').value = 'KORVAA';
												}
											-->
											</script>";


									echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
										<!--

										function toggleAll(toggleBox) {

											var currForm = toggleBox.form;
											var isChecked = toggleBox.checked;
											var nimi = toggleBox.name;

											for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
												if (currForm.elements[elementIdx].type == 'radio' && currForm.elements[elementIdx].name.substring(0,5) == 'loput' && currForm.elements[elementIdx].value == nimi) {
													currForm.elements[elementIdx].checked = isChecked;
												}
											}
										}

										//-->
										</script>";


									echo "<form action='$PHP_SELF' method='post'>";
									echo "<input type='hidden' name='maa' value='$maa'>";

									// N�m� ovat niit� hiddeneit� mit� yll�oleva js muokkaa (korvaa-nappi).
									echo "<input type='hidden' name='korvattava_tilriv' id='korvattava_tilriv' value=''>";
									echo "<input type='hidden' name='korvattava' id='korvattava' value=''>";
									echo "<input type='hidden' name='korvaava' id='korvaava' value=''>";
									echo "<input type='hidden' name='korvataanko' id='korvataanko' value=''>";

									if ($tilaus_on_jo == "") {
										echo "<input type='hidden' name='tee' value='POIMI'>";
										echo "<input type='hidden' name='toim' value='$toim'>";
										echo "<input type='hidden' name='jarj' value='$jarj'>";
										echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
										echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
										echo "<input type='hidden' name='asiakasno' value='$asiakasno'>";
										echo "<input type='hidden' name='toimittaja' value='$toimittaja'>";
										echo "<input type='hidden' name='toimi' value='$toimi'>";
										echo "<input type='hidden' name='ei_limiittia' value='$ei_limiittia'>";
										echo "<input type='hidden' name='superit' value='$superit'>";
										echo "<input type='hidden' name='suorana' value='$suorana'>";
										echo "<input type='hidden' name='tuotenumero' value='$tuotenumero'>";
										echo "<input type='hidden' name='tilaus' value='$tilaus'>";

										if (count($suoravarasto) > 0) {
											foreach($suoravarasto as $key => $value) {
												echo "<input type='hidden' name='suoravarasto[$key]' value='$value'>";
											}
										}

										if (is_array($varastosta)) {
											foreach ($varastosta as $vara) {
												echo "<input type='hidden' name='varastosta[$vara]' value='$vara'>";
											}
										}

										//Tehd��n apumuuttuja jotta muokkaa_rivi linkki toimisi kunnolla
										$tilausnumero = "";

										if (is_array($varastosta)) {
											foreach ($varastosta as $vara) {
												$tilausnumero .= $vara."##";
											}
											$tilausnumero = substr($tilausnumero,0,-2);
										}
									}
									else {
										if ($kukarow["extranet"] == "") {
											if ($asiakasmaa != '') {
												$asiakasmaalisa = "and (varastopaikat.sallitut_maat like '%$asiakasmaa%' or varastopaikat.sallitut_maat = '')";
											}

											$query = "	SELECT *
														FROM varastopaikat
														WHERE yhtio = '$kukarow[yhtio]' $asiakasmaalisa";
											$vtresult = pupe_query($query);

											if (mysql_num_rows($vtresult) > 1) {
												echo "<b>".t("N�yt� saatavuus vain varastosta").": </b> <select name='vainvarastosta' onchange='submit();'>";
												echo "<option value=''>".t("Kaikki varastot")."</option>";

												while ($vrow = mysql_fetch_array($vtresult)) {
													if ($vrow["tyyppi"] != 'E' or (isset($kukarow["varasto"]) and (int) $kukarow["varasto"] > 0 and in_array($vrow["tunnus"], explode(",", $kukarow['varasto'])))) {

														$sel = "";
														if ($vainvarastosta == $vrow["tunnus"]) {
															$sel = 'SELECTED';
														}

														echo "<option value='$vrow[tunnus]' $sel>$vrow[nimitys]</option>";
													}
												}

												echo "</select>";

											}
											echo "<input type='hidden' name='jt_kayttoliittyma' value='kylla'>";
										}

										echo "	<input type='hidden' name='toim' value='$toim'>
												<input type='hidden' name='tilausnumero' value='$tilausnumero'>
												<input type='hidden' name='tee'  value='JT_TILAUKSELLE'>
												<input type='hidden' name='tila' value='jttilaukseen'>";
									}
								}
							}

							if ($automaaginen == '') {
								// Tuoteperheiden lapsille ei n�ytet� rivinumeroa
								if ($jtrow["perheid"] == $jtrow["tunnus"] or ($jtrow["perheid2"] == $jtrow["tunnus"] and $jtrow["perheid"] == 0)) {
									$query = "	SELECT count(*)
												from tilausrivi
												where yhtio = '$kukarow[yhtio]'
												$otunlisa
												$pklisa";
									$pkres = pupe_query($query);
									$pkrow = mysql_fetch_array($pkres);

									$pknum 		= $pkrow[0];
									$borderlask = $pkrow[0];

									echo "<tr class='aktiivi'><td valign='top' rowspan='$pknum' style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;' >$jt_rivilaskuri</td>";
								}
								elseif ($jtrow["perheid"] == 0 and $jtrow["perheid2"] == 0) {
									$pkrow = array(0);
									$pknum 		= $pkrow[0];
									$borderlask = $pkrow[0];

									echo "<tr class='aktiivi'><td valign='top'>$jt_rivilaskuri</td>";
								}

								$classlisa 	= "";
								$class 		= "";

								if ($borderlask == 1 and $pkrow[0] == 1 and $pknum == 1) {
									$classlisa = " style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
									$class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

									$borderlask--;
								}
								elseif ($borderlask == $pkrow[0] and $pkrow[0] > 0) {
									$classlisa = " style='border-top: 1px solid; border-right: 1px solid;' ";
									$class    .= " style='border-top: 1px solid;' ";
									$borderlask--;
								}
								elseif ($borderlask == 1) {
									if ($pknum > 1) {
										$classlisa =" style='font-style:italic; border-bottom: 1px solid; border-right: 1px solid;' ";
										$class    .= " style='font-style:italic; border-bottom: 1px solid;' ";
									}
									else {
										$classlisa = $class." style='border-bottom: 1px solid; border-right: 1px solid;' ";
										$class    .= " style='border-bottom: 1px solid;' ";
									}

									$borderlask--;
								}
								elseif ($borderlask > 0 and $borderlask < $pknum) {
									$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
									$class    .= " style='font-style:italic;' ";
									$borderlask--;
								}

								if ($kukarow["extranet"] == "") {
									echo "<td valign='top' $class>$ins <a href='../tuote.php?tee=Z&tuoteno=".urlencode($jtrow["tuoteno"])."'>$jtrow[tuoteno]</a>";
								}
								else {
									echo "<td valign='top' $class>$ins $jtrow[tuoteno]";
								}

								if ($kukarow["resoluutio"] == 'I' or $kukarow["extranet"] != "") {
									echo "<br>$jtrow[nimitys]";
								}
								echo "</td>";

								if ($tilaus_on_jo == "") {
									echo "<td valign='top' $class>$jtrow[ytunnus]<br>";

									if ($kukarow["extranet"] == "") {
										echo "<a href='../tuote.php?tee=NAYTATILAUS&tunnus=$jtrow[ltunnus]'>$jtrow[nimi]</a><br>";
									}
									else {
										echo "$jtrow[nimi]<br>";
									}

									echo "$jtrow[toim_nimi]";

									if (!isset($saatanat_chk[$jtrow['ytunnus']])) {

										$sytunnus 	 	 = $jtrow['ytunnus'];
										$eiliittymaa 	 = 'ON';
										$luottorajavirhe = '';
										$jvvirhe 		 = '';
										$ylivito 		 = '';
										$trattavirhe 	 = '';

										ob_start();
										require ("raportit/saatanat.php");
										ob_end_clean();

										$saatanat_chk[$sytunnus] = array($luottorajavirhe, $jvvirhe, $ylivito, $trattavirhe);
									}
									else {
										list($luottorajavirhe, $jvvirhe, $ylivito, $trattavirhe) = $saatanat_chk[$jtrow['ytunnus']];
									}

									if ($luottorajavirhe != '' or $jvvirhe != '' or $ylivito > 0 or $trattavirhe != '') {
										echo "<br/>";
									}

									if ($luottorajavirhe != '') {
										echo "<br/>";
										echo "<font class='message'>",t("Luottoraja ylittynyt"),"</font>";
									}

									if ($jvvirhe != '') {
										echo "<br/>";
										echo "<font class='message'>",t("T�m� on j�lkivaatimusasiakas"),"</font>";
									}

									if ($ylivito > 0) {
										echo "<br/>";
										echo "<font class='message'>".t("Yli 15 pv sitten er��ntyneit� laskuja")."</font>";
									}

									if ($trattavirhe != '') {
										echo "<br/>";
										echo "<font class='message'>".t("Asiakkaalla on maksamattomia trattoja")."<br></font>";
									}

									echo "</td>";
								}

								if ($kukarow["resoluutio"] == 'I' or $kukarow["extranet"] != "") {
									echo "<td valign='top' $class>$jtrow[otunnus]<br>$jtrow[viesti]</td>";
								}

								if ($oikeurow['paivitys'] == '1' and $kukarow["extranet"] == "") {
									echo "<td valign='top' $class><a href='$PHP_SELF?toim=$toim&tee=MUOKKAARIVI&jt_rivitunnus=$jtrow[tunnus]&toimittajaid=$toimittajaid&asiakasid=$asiakasid&asiakasno=$asiakasno&toimittaja=$toimittaja&toimi=$toimi&ei_limiittia=$ei_limiittia&superit=$superit&suorana=$suorana&tuotenumero=$tuotenumero&tilaus=$tilaus&jarj=$jarj&tilausnumero=$tilausnumero'>".($jtrow["jt"]*1)."</a><br>";
								}
								else {
									echo "<td valign='top' align='right' $class>".($jtrow["jt"]*1)."<br>";
								}

								if ($jtrow["valkoodi"] != '' and trim(strtoupper($jtrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
									$hinta	= hintapyoristys(laskuval($jtrow["hinta"], $jtrow["vienti_kurssi"]))." ".$jtrow["valkoodi"];
								}
								else {
									$hinta	= hintapyoristys($jtrow["hinta"])." ".$jtrow["valkoodi"];
								}

								echo "$hinta<br>";
								for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
									if ($jtrow["ale{$alepostfix}"] > 0) {
										if ($alepostfix > 1) echo "+";
										echo ($jtrow["ale{$alepostfix}"]*1),"%<br>";
									}
								}
								echo "</td>";
							}

							if ($oikeurow['paivitys'] == '1') {
								if ($toim == "ENNAKKO") {
									$query = "	SELECT sum(varattu) jt, count(*) kpl
												FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
												WHERE yhtio 	= '$kukarow[yhtio]'
												and tyyppi 		= 'E'
												and tuoteno 	= '$jtrow[tuoteno]'
												and varattu 	> 0";
								}
								else {
									$query = "	SELECT sum(jt $lisavarattu) jt, count(*) kpl
												FROM tilausrivi use index(yhtio_tyyppi_var)
												WHERE yhtio			= '$kukarow[yhtio]'
												and tyyppi 			in ('L','G')
												and tuoteno			= '$jtrow[tuoteno]'
												and jt $lisavarattu > 0
												and kpl				= 0
												and var				= 'J'";
								}
								$juresult = pupe_query($query);
								$jurow    = mysql_fetch_array ($juresult);

								if (strtotime($jtrow['ttoimaika']) == strtotime($jtrow['ltoimaika'])) {
									unset($toimvko);
									unset($toimpva);

									if ($jtrow['toimvko'] > 0 and $jtrow['toimvko'] != 7) {
										$toimvko = date("W", strtotime($jtrow['ltoimaika']));
										$toimpva = date("N", strtotime($jtrow['ltoimaika']));
									}
									else if ($jtrow['toimvko'] > 0 and $jtrow['toimvko'] == 7) {
										$toimvko = date("W", strtotime($jtrow['ltoimaika']));
									}

									$toimaika = $jtrow['ltoimaika'];
								}
								else {
									unset($toimvko);
									unset($toimpva);
									$toimaika = $jtrow['ttoimaika'];
								}

								$kaikki_check = '';
								$poista_check = '';
								$jata_check = '';
								$mita_check = '';

								if (isset($loput[$tunnukset]) and $loput[$tunnukset] == 'KAIKKI') {
									$kaikki_check = 'checked';
								}

								if (isset($loput[$tunnukset]) and $loput[$tunnukset] == 'POISTA') {
									$poista_check = 'checked';
								}

								if (isset($loput[$tunnukset]) and $loput[$tunnukset] == 'JATA') {
									$jata_check = 'checked';
								}

								if (isset($loput[$tunnukset]) and $loput[$tunnukset] == 'MITA') {
									$mita_check = 'checked';
								}

								if (isset($loput[$tunnukset]) and $loput[$tunnukset] == 'VAKISIN') {
									$mita_check = 'checked';
								}

								if (!isset($kpl[$tunnukset])) $kpl[$tunnukset] = "";

								// Riitt�� kaikille
								if (($kokonaismyytavissa >= $jurow["jt"] or $jtrow["ei_saldoa"] != "") and $perheok == 0) {

									// Jos haluttiin toimittaa t�m� rivi automaagisesti
									if (($kukarow["extranet"] == "" or ($kukarow['extranet'] != '' and $automaattinen_poiminta != '')) and ($automaaginen == 'automaaginen' or $automaaginen == 'tosi_automaaginen')) {

										if ($from_varastoon_inc == "editilaus_in.inc") {
											$edi_ulos .= "\n".t("JT-rivi")." --> ".t("Tuoteno").": $jtrow[tuoteno] ".t("lis�ttiin tilaukseen")."!";
										}
										else {
											echo "<font class='message'>".t("JT-rivi")." --> ".t("Tuoteno").": $jtrow[tuoteno] ".t("lis�ttiin tilaukseen").". (".t("Tuotetta riitti kaikille JT-riveille").")</font><br>";
										}

										// Pomitaan t�m� rivi/perhe
										$loput[$tunnukset] 	= "KAIKKI";
										$kpl[$tunnukset] 	= 0;
										$tunnusarray 		= explode(',', $tunnukset);

										// Toimitetaan jtrivit
										tee_jt_tilaus($tunnukset, $tunnusarray, $kpl, $loput, $suoratoimpaikka, $tilaus_on_jo, $varastosta);

										$jt_rivilaskuri++;
									}
									else {
										echo "<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>";

										if ($kukarow["extranet"] == "") {
											echo "<td valign='top' $class>$kokonaismyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$jtrow[yksikko]'", "", "", "selite")."<br><font style='color:green;'>".t("Riitt�� kaikille")."!</font><br>";

											if (isset($toimvko) and $toimvko > 0 and !isset($toimpva)) {
												echo t("Viikko")." $toimvko";
											}
											elseif (isset($toimvko) and $toimvko > 0 and isset($toimpva)) {
												echo t("Viikko")." $toimvko (".$DAY_ARRAY[$toimpva].")";
											}
											else {
												echo tv1dateconv($toimaika);
											}

											echo "</td>";

											echo "<td valign='top' align='center' $class>".t("K")."<input type='radio' name='loput[$tunnukset]' value='KAIKKI' $kaikki_check></td>";

											if ($jtrow["osatoimitus"] == "") {
												echo "<td valign='top' align='center' $class><input type='text' name='kpl[$tunnukset]' size='4' value='{$kpl[$tunnukset]}'></td>";
												echo "<td valign='top' align='center' $class>".t("P")."<input type='radio' name='loput[$tunnukset]' value='POISTA' $poista_check></td>";
												echo "<td valign='top' align='center' $class>".t("J")."<input type='radio' name='loput[$tunnukset]' value='JATA' $jata_check></td>";
											}
											else {
												echo "<td valign='top' align='center' colspan='3' $class>".t("Tilausta ei osatoimiteta")."</td>";
											}

											echo "<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA' $mita_check></td>";
											echo "<td valign='top' align='center' $classlisa>".t("H")."<input type='radio' name='loput[$tunnukset]' value='VAKISIN' $mita_check></td>";
										}
										elseif ($kukarow["extranet"] != "") {
											echo "<td valign='top' $class><font style='color:green;'>".t("Voidaan toimittaa")."!</font></td>";

											if ((int) $kukarow["kesken"] > 0) {
												echo "	<td valign='top' align='center' $class>".t("Toimita")."<input type='radio' name='loput[$tunnukset]' value='KAIKKI'></td>";
											}
											else {
												echo "<td valign='top' $class>".t("Avaa uusi tilaus jotta voit toimittaa rivin").".</td>";
											}

											if ($yhtiorow["jt_rivien_kasittely"] != 'E') {
												echo "<td valign='top' align='center' $class>".t("Mit�t�i")."<input type='radio' name='loput[$tunnukset]' value='MITA'></td>";
											}
											echo "<td valign='top' align='center' $classlisa>".t("�l� tee mit��n")."<input type='radio' name='loput[$tunnukset]' value=''></td>";

										}

										$jt_rivilaskuri++;
									}
								}
								// Riitt�� t�lle riville mutta ei kaikille
								elseif (($kukarow["extranet"] == "" or ($kukarow['extranet'] != '' and $automaattinen_poiminta != '')) and $kokonaismyytavissa >= $jtrow["jt"] and $perheok == 0) {

									// Jos haluttiin toimittaa t�m� rivi automaagisesti
									if (($kukarow["extranet"] == "" or ($kukarow['extranet'] != '' and $automaattinen_poiminta != '')) and $automaaginen == 'tosi_automaaginen') {

										if ($from_varastoon_inc == "editilaus_in.inc") {
											$edi_ulos .= "\n".t("JT-rivi")." --> ".t("Tuoteno").": $jtrow[tuoteno] ".t("lis�ttiin tilaukseen")."!";
										}
										else {
											echo "<font class='message'>".t("JT-rivi")." --> ".t("Tuoteno").": $jtrow[tuoteno] ".t("lis�ttiin tilaukseen").". (".t("Tuotetta ei riitt�nyt kaikille JT-riveille").")</font><br>";
										}

										// Pomitaan t�m� rivi/perhe
										$loput[$tunnukset] 	= "KAIKKI";
										$kpl[$tunnukset] 	= 0;
										$tunnusarray 		= explode(',', $tunnukset);

										// Toimitetaan jtrivit
										tee_jt_tilaus($tunnukset, $tunnusarray, $kpl, $loput, $suoratoimpaikka, $tilaus_on_jo, $varastosta);

										$jt_rivilaskuri++;
									}
									elseif ($automaaginen == "") {
										echo "<td valign='top' $class>$kokonaismyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$jtrow[yksikko]'", "", "", "selite")."<br><font style='color:yellowgreen;'>".t("Ei riit� kaikille")."!</font><br>";

										if (isset($toimvko) and $toimvko > 0 and !isset($toimpva)) {
											echo t("Viikko")." $toimvko";
										}
										elseif (isset($toimvko) and $toimvko > 0 and isset($toimpva)) {
											echo t("Viikko")." $toimvko (".$DAY_ARRAY[$toimpva].")";
										}
										else {
											echo tv1dateconv($toimaika);
										}
										echo "</td>";

										echo "<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>";
										echo "<td valign='top' align='center' $class>".t("K")."<input type='radio' name='loput[$tunnukset]' value='KAIKKI' $kaikki_check></td>";

										if ($jtrow["osatoimitus"] == "") {
											echo "<td valign='top' align='center' $class><input type='text' name='kpl[$tunnukset]' size='4' value='$kpl[$tunnukset]'></td>";
											echo "<td valign='top' align='center' $class>".t("P")."<input type='radio' name='loput[$tunnukset]' value='POISTA' $poista_check></td>";
											echo "<td valign='top' align='center' $class>".t("J")."<input type='radio' name='loput[$tunnukset]' value='JATA' $jata_check></td>";
										}
										else {
											echo "<td valign='top' align='center' colspan='3' $class>".t("Tilausta ei osatoimiteta")."</td>";
										}

										echo "<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA' $mita_check></td>";
										echo "<td valign='top' align='center' $classlisa>".t("H")."<input type='radio' name='loput[$tunnukset]' value='VAKISIN' $mita_check></td>";

										$jt_rivilaskuri++;
									}
								}
								// Suoratoimitus
								elseif ($paikatlask > 0 and $automaaginen == '' and $kukarow['extranet'] == '') {

									$varalisa = "<br><select name='suoratoimpaikka[$tunnukset]'><option value=''>".t("Ei toimiteta")."</option>".$paikat."</select>";

									if ($suoratoim_totaali >= $jurow["jt"]) {
										echo "<td valign='top' $class><font style='color:green;'>".t("Riitt�� kaikille")."!$varalisa</font></td>";

									}
									elseif ($suoratoim_totaali >= $jtrow["jt"]) {
										echo "<td valign='top' $class><font style='color:yellowgreen;'>".t("Ei riit� kaikille")."!$varalisa</font></td>";
									}
									else {
										echo "<td valign='top' $class><font style='color:orange;'>".t("Ei riit� koko riville")."!$varalisa</font></td>";
									}

									echo "<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>";
									echo "<td valign='top' align='center' $class>".t("K")."<input type='radio' name='loput[$tunnukset]' value='KAIKKI' $kaikki_check></td>";

									if ($jtrow["osatoimitus"] == "") {
										echo "<td valign='top' align='center' $class><input type='text' name='kpl[$tunnukset]' size='4' value='$kpl[$tunnukset]'></td>";
										echo "<td valign='top' align='center' $class>".t("P")."<input type='radio' name='loput[$tunnukset]' value='POISTA' $poista_check></td>";
										echo "<td valign='top' align='center' $class>".t("J")."<input type='radio' name='loput[$tunnukset]' value='JATA' $jata_check></td>";
									}
									else {
										echo "<td valign='top' align='center' colspan='3' $class>".t("Tilausta ei osatoimiteta")."</td>";
									}

									echo "<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA' $mita_check></td>";
									echo "<td valign='top' align='center' $classlisa>".t("H")."<input type='radio' name='loput[$tunnukset]' value='VAKISIN' $mita_check></td>";

									$jt_rivilaskuri++;
								}
								// Ei riit� koko riville
								elseif ($kukarow["extranet"] == "" and $kokonaismyytavissa > 0 and $perheok==0) {
									if ($automaaginen == '') {
										echo "<td valign='top' $class>$kokonaismyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$jtrow[yksikko]'", "", "", "selite")."<br><font style='color:orange;'>".t("Ei riit� koko riville")."!</font><br>";

										if (isset($toimvko) and $toimvko > 0 and !isset($toimpva)) {
											echo t("Viikko")." $toimvko";
										}
										elseif (isset($toimvko) and $toimvko > 0 and isset($toimpva)) {
											echo t("Viikko")." $toimvko (".$DAY_ARRAY[$toimpva].")";
										}
										else {
											echo tv1dateconv($toimaika);
										}
										echo "</td>";

										echo "<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>";
										echo "<td valign='top' align='center' $class>&nbsp;</td>";

										if ($jtrow["osatoimitus"] == "") {
											echo "<td valign='top' align='center' $class><input type='text' name='kpl[$tunnukset]' size='4' value='$kpl[$tunnukset]'></td>";
											echo "<td valign='top' align='center' $class>".t("P")."<input type='radio' name='loput[$tunnukset]' value='POISTA' $poista_check></td>";
											echo "<td valign='top' align='center' $class>".t("J")."<input type='radio' name='loput[$tunnukset]' value='JATA' $jata_check></td>";
										}
										else {
											echo "<td valign='top' align='center' colspan='3' $class>".t("Tilausta ei osatoimiteta")."</td>";
										}

										echo "<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA' $mita_check></td>";
										echo "<td valign='top' align='center' $classlisa>".t("H")."<input type='radio' name='loput[$tunnukset]' value='VAKISIN' $mita_check></td>";

										$jt_rivilaskuri++;
									}
								}
								// Rivi� ei voida toimittaa
								else {
									if ($automaaginen == '') {
										echo "<td valign='top' $class>$kokonaismyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$jtrow[yksikko]'", "", "", "selite")."<br><font style='color:red;'>".t("Rivi� ei voida toimittaa")."!</font><br>";

										if (isset($toimvko) and $toimvko > 0 and !isset($toimpva)) {
											echo t("Viikko")." $toimvko";
										}
										elseif (isset($toimvko) and $toimvko > 0 and isset($toimpva)) {
											echo t("Viikko")." $toimvko (".$DAY_ARRAY[$toimpva].")";
										}
										else {
											echo tv1dateconv($toimaika);
										}
										echo "</td>";
										echo "<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>";

										if ($kukarow["extranet"] == "") {
											echo "	<td valign='top' align='center' $class>&nbsp;</td>
													<td valign='top' align='center' $class>&nbsp;</td>
													<td valign='top' align='center' $class>&nbsp;</td>
													<td valign='top' align='center' $class>&nbsp;</td>
													<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA' $mita_check></td>";
											echo "<td valign='top' align='center' $classlisa>".t("H")."<input type='radio' name='loput[$tunnukset]' value='VAKISIN' $mita_check></td>";
										}
										else {
											echo "<td valign='top' align='center' $class>&nbsp;</td>";
											if ($kukarow["extranet"] != "" and $yhtiorow["jt_rivien_kasittely"] != 'E') {
												echo "<td valign='top' align='center' $class>".t("Mit�t�i")." $yhtiorow[jt_rivien_kasittely]<input type='radio' name='loput[$tunnukset]' value='MITA'></td>";
											}
											echo "<td valign='top' align='center' $classlisa>".t("�l� tee mit��n")."<input type='radio' name='loput[$tunnukset]' value=''></td>";
										}

										$jt_rivilaskuri++;
									}
								}

							}
							else {
								echo "<td valign='top' align='center' $classlisa>&nbsp;</td>";
								$jt_rivilaskuri++;
							}

							if ($automaaginen == '') {
								echo "</tr>";
							}

							if (isset($lapsires) and mysql_num_rows($lapsires) > 0 and $automaaginen == '') {

								mysql_data_seek($lapsires, 0);

								while ($perherow = mysql_fetch_array($lapsires)) {

									$classlisa 	= "";
									$class 		= "";

									if ($borderlask == 1 and $pkrow[1] == 1 and $pknum == 1) {
										$classlisa = $class." style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
										$class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

										$borderlask--;
									}
									elseif ($borderlask == $pkrow[1] and $pkrow[1] > 0) {
										$classlisa = $class." style='border-top: 1px solid; border-right: 1px solid;' ";
										$class    .= " style='border-top: 1px solid;' ";
										$borderlask--;
									}
									elseif ($borderlask == 1) {
										if ($pknum > 1) {
											$classlisa = $class." style='font-style:italic; border-bottom: 1px solid; border-right: 1px solid;' ";
											$class    .= " style='font-style:italic; border-bottom: 1px solid;' ";
										}
										else {
											$classlisa = $class." style='border-bottom: 1px solid; border-right: 1px solid;' ";
											$class    .= " style='border-bottom: 1px solid;' ";
										}

										$borderlask--;
									}
									elseif ($borderlask > 0 and $borderlask < $pknum) {
										$classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
										$class    .= " style='font-style:italic;' ";
										$borderlask--;
									}

									echo "<tr>";

									$kokonaismyytavissa = 0;

									foreach ($varastosta as $vara) {
										list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($perherow["tuoteno"], "JTSPEC", $vara, "", "", "", "", "", $asiakasmaa);

										if ($saldolaskenta == "hyllysaldo") {
											$kokonaismyytavissa += $hyllyssa;
										}
										else {
											$kokonaismyytavissa += $myytavissa;
										}
									}

									if ($kukarow["extranet"] == "") {
										echo "<td valign='top' $class><a href='../tuote.php?tee=Z&tuoteno=".urlencode($perherow["tuoteno"])."'>$perherow[tuoteno]</a>";
									}
									else {
										echo "<td valign='top' $class>$perherow[tuoteno]";
									}

									if ($kukarow["resoluutio"] == 'I' or $kukarow["extranet"] != "") {
										echo "<br>$perherow[nimitys]</td>";
									}
									echo "</td>";

									if ($tilaus_on_jo == "") {
										echo "<td valign='top' $class>$perherow[ytunnus]<br>";

										if ($kukarow["extranet"] == "") {
											echo "<a href='../tuote.php?tee=NAYTATILAUS&tunnus=$perherow[ltunnus]'>$perherow[nimi]</a><br>";
										}
										else {
											echo "$perherow[nimi]<br>";
										}

										echo "$perherow[toim_nimi]</td>";
									}

									if ($kukarow["resoluutio"] == 'I' or $kukarow["extranet"] != "") {
										echo "<td valign='top' $class>$perherow[otunnus]<br>$perherow[viesti]</td>";
									}

									if ($kukarow["extranet"] == "") {
										echo "<td valign='top' $class><a href='$PHP_SELF?toim=$toim&tee=MUOKKAARIVI&jt_rivitunnus=$perherow[tunnus]&toimittajaid=$toimittajaid&asiakasid=$asiakasid&asiakasno=$asiakasno&toimittaja=$toimittaja&toimi=$toimi&ei_limiittia=$ei_limiittia&superit=$superit&suorana=$suorana&tuotenumero=$tuotenumero&tilaus=$tilaus&jarj=$jarj&tilausnumero=$tilausnumero'>$perherow[jt]</a><br>";
									}
									else {
										echo "<td valign='top' align='right' $class>$perherow[jt]<br>";
									}


									if ($perherow["valkoodi"] != '' and trim(strtoupper($perherow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
										$hinta	= hintapyoristys(laskuval($perherow["hinta"], $perherow["vienti_kurssi"]))." ".$perherow["valkoodi"];
									}
									else {
										$hinta	= hintapyoristys($perherow["hinta"])." ".$perherow["valkoodi"];
									}

									echo $hinta."<br>";

									for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
										if ($perherow["ale{$alepostfix}"] > 0) {
											if ($alepostfix > 1) echo "+";
											echo $perherow["ale{$alepostfix}"],"%<br />";
										}
									}

									echo "</td>";

									if ($oikeurow['paivitys'] == '1') {
										echo "<td valign='top' $class>$kokonaismyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$perherow[yksikko]'", "", "", "selite")."<br></font>";

										if (!isset($toimpva) and $toimvko > 0) {
											echo t("Viikko")." $toimvko";
										}
										else if ($toimvko > 0 and isset($toimpva)) {
											echo t("Viikko")." $toimvko";

											if (isset($toimpva)) {
												echo " ($DAY_ARRAY[$toimpva])";
											}
										}
										else {
											echo tv1dateconv($toimaika);
										}
										echo "</td>";

										if ($kukarow["extranet"] == "") {
											echo "	<td valign='top' align='center' $class>&nbsp;</td>
													<td valign='top' align='center' $class>&nbsp;</td>
													<td valign='top' align='center' $class>&nbsp;</td>
													<td valign='top' align='center' $class>&nbsp;</td>
													<td valign='top' align='center' $classlisa>&nbsp;</td>
													<td valign='top' align='center' $classlisa>&nbsp;</td>";
										}
										else {
											echo "	<td valign='top' align='center' $class>&nbsp;</td>
													<td valign='top' align='center' $class>&nbsp;</td>
													<td valign='top' align='center' $classlisa>&nbsp;</td>";
										}
									}
									else {
										echo "<td valign='top' align='center' $classlisa>&nbsp;</td>";
									}
									echo "</tr>";
								}

								unset($lapsires);
							}

							if ($kukarow['extranet'] == '' and (!isset($php_cli) or !$php_cli)) {
								// Korvaavat tuotteet //
								$query  = "SELECT * from korvaavat where tuoteno='$jtrow[tuoteno]' and yhtio='$kukarow[yhtio]'";
								$korvaresult = pupe_query($query);

								if (mysql_num_rows($korvaresult) > 0) {
									// tuote l�ytyi, joten haetaan sen id...
									$korvarow = mysql_fetch_array($korvaresult);

									$query = "SELECT * from korvaavat where id='$korvarow[id]' and tuoteno<>'$jtrow[tuoteno]' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
									$korva2result = pupe_query($query);

									if (mysql_num_rows($korva2result) > 0) {
										while ($krow2row = mysql_fetch_array($korva2result)) {

											$vapaana = 0;

											$jt_saldopvm = "";

											if ($yhtiorow["saldo_kasittely"] != "") {
												$jt_saldopvm = date("Y-m-d");
											}

											foreach ($varastosta as $vara) {
												list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($krow2row["tuoteno"], "JTSPEC", $vara, "", "", "", "", "", $asiakasmaa, $jt_saldopvm);

												if ($saldolaskenta == "hyllysaldo") {
													$vapaana += $hyllyssa;
												}
												else {
													$vapaana += $myytavissa;
												}

											}

											if ($vapaana >= $jurow["jt"]) {
												echo "<tr class='aktiivi'>";
												echo "<td><font style='color:red;'>".t("Korvaava")."</font></td>";
												echo "<td align='left' style='vertical-align:top'>";
												echo "$krow2row[tuoteno] ($vapaana) <font style='color:green;'>".t("Riitt�� kaikille")."!$varalisa</font><br>";
												echo "</td><td colspan='9' align='left'><input type='button' value='".t("Korvaa tuote")." $jtrow[tuoteno]' onClick='javascript:update_params(\"$jtrow[tuoteno]\", \"$krow2row[tuoteno]\", \"$jtrow[tunnus]\");javascript:submit();'></td></tr>";
											}
											elseif ($vapaana >= $jtrow["jt"]) {
												echo "<tr class='aktiivi'>";
												echo "<td><font style='color:red;'>".t("Korvaava")."</font></td>";
												echo "<td align='left' style='vertical-align:top'>";
												echo "$krow2row[tuoteno] ($vapaana) <font style='color:yellowgreen;'>".t("Ei riit� kaikille")."!$varalisa</font><br>";
												echo "</td><td colspan='9' align='left'><input type='submit' value='".t("Korvaa tuote")." $jtrow[tuoteno]' onClick='javascript:update_params(\"$jtrow[tuoteno]\", \"$krow2row[tuoteno]\", \"$jtrow[tunnus]\");javascript:submit();'></td></tr>";
											}
											elseif ($vapaana > 0) {
												echo "<tr class='aktiivi'>";
												echo "<td><font style='color:red;'>".t("Korvaava")."</font></td>";
												echo "<td align='left' style='vertical-align:top'>";
												echo "$krow2row[tuoteno] ($vapaana) <font style='color:orange;'>".t("Ei riit� koko riville")."!$varalisa</font><br>";
												echo "</td><td colspan='9' align='left'><input type='submit' value='".t("Korvaa tuote")." $jtrow[tuoteno]' onClick='javascript:update_params(\"$jtrow[tuoteno]\", \"$krow2row[tuoteno]\", \"$jtrow[tunnus]\");javascript:submit();'></td></tr>";
											}
										}
									}
								}
							}
						}
					}
				}

				if ($automaaginen == '' and $jt_rivilaskuri > 1) {

					if ($oikeurow['paivitys'] == '1') {

						if ($kukarow["extranet"] == "" and $automaaginen == '') {

							echo "<tr class='aktiivi'>";

							$colspan = 3;

							if ($tilaus_on_jo == "") {
								$colspan++;
							}

							if ($kukarow["resoluutio"] == 'I' or $kukarow["extranet"] != "") {
								$colspan++;
							}

							if ($oikeurow['paivitys'] == '1' and $kukarow["extranet"] == "") {
								$colspan++;
							}

							echo "<td colspan='$colspan'>".t("Ruksaa kaikki")."</td>";
							echo "<td align='center'><input type='checkbox' name='KAIKKI' onclick='toggleAll(this);'></td>";
							echo "<td></td>";
							echo "<td align='center'><input type='checkbox' name='POISTA' onclick='toggleAll(this);'></td>";
							echo "<td align='center'><input type='checkbox' name='JATA' onclick='toggleAll(this);'></td>";
							echo "<td align='center'><input type='checkbox' name='MITA' onclick='toggleAll(this);'></td>";
							echo "<td align='center'><input type='checkbox' name='VAKISIN' onclick='toggleAll(this);'></td></tr>";
						}

						echo "<tr><td colspan='8' class='back'></td><td colspan='3' class='back' align='right'><input type='submit' value='".t("Poimi")."'></td></tr>";
						echo "</form>";
					}

					echo "</table>";

					if ($jtseluas_rivienmaara >= 1000 and $ei_limiittia == "") {
						echo "<font class='error'>".t("Haun tulos liian suuri! N�ytet��n ensimm�iset 1000 rivi�!")."</font><br>";
					}
				}
				elseif ($jt_rivilaskuri == 1) {
					if ($from_varastoon_inc == "editilaus_in.inc") {
						$edi_ulos .= "\n".t("Yht��n JT-rivi� ei l�ytynyt")."!";
					}
					elseif ($from_varastoon_inc == "varastoon.inc") {
						// ei mit��n
					}
					else {
						echo t("Yht��n JT-rivi� ei l�ytynyt")."!<br>";
					}
				}
			}
			else {
				if ($from_varastoon_inc == "editilaus_in.inc") {
					$edi_ulos .= "\n".t("Yht��n JT-rivi� ei l�ytynyt")."!";
				}
				else {
					echo t("Yht��n JT-rivi� ei l�ytynyt")."!<br>";
				}
			}
			$tee = '';
		}
	}

	if ($tilaus_on_jo == "" and $from_varastoon_inc == "" and $tee == '') {

		echo "<br><font class='message'>".t("Valinnat")."</font><br><br>";

		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'";
		$vtresult = pupe_query($query);

		echo "	<form name='valinta' action='$PHP_SELF' method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<table>";

		echo "<tr><td class='back'><font class='message'>".t("Toimita varastosta:")."</font></td></tr>";

		while ($vrow = mysql_fetch_array($vtresult)) {

				$sel = "";
				if (isset($varastosta[$vrow["tunnus"]]) and $varastosta[$vrow["tunnus"]] == $vrow["tunnus"]) {
					$sel = 'CHECKED';
				}

				$huomio = "";

				if ($vrow['tyyppi'] == 'E') {
					$huomio = "<td class='back'><font class='error'>".t("HUOM!!! Erikoisvarasto!")."</font></td>";
				}

				echo "<tr><th>$vrow[nimitys]</th><td><input type='checkbox' name='varastosta[$vrow[tunnus]]' value='$vrow[tunnus]' $sel></td>$huomio</tr>";
		}

		$query = "	SELECT varastopaikat.tunnus, varastopaikat.nimitys, yhtio.nimi
					from toimi
					JOIN varastopaikat ON varastopaikat.yhtio=toimi.tyyppi_tieto and varastopaikat.tyyppi=''
					JOIN yhtio ON yhtio.yhtio=varastopaikat.yhtio
					where toimi.yhtio = '$kukarow[yhtio]'
					and toimi.tyyppi        = 'S'
					and toimi.tyyppi_tieto != ''
					and toimi.edi_palvelin != ''
					and toimi.edi_kayttaja != ''
					and toimi.edi_salasana != ''
					and toimi.edi_polku    != ''
					and toimi.oletus_vienti in ('C','F','I')
					ORDER BY tyyppi_tieto";
		$superjtres  = pupe_query($query);

		if (mysql_num_rows($superjtres) > 0) {

			//	Piilotetaan t�m� jos meill� on jo jotain suoravarastoja valittuna (t�m� toiminto depracoituu enivei)
			$sel = "";
			if ($suorana != '' and count($suoravarasto)==0) $sel = 'CHECKED';
			echo "<tr><td class='back'><br></td></tr><tr><td class='back'><font class='message'>".t("Toimita suoratoimituksena varastosta:")."</font></td></tr>";
			echo "<tr><th>".t("Toimita kaikista varastoista (manuaalivalinta)")."</th><td><input type='checkbox' name='suorana' value='suora' $sel></td></tr>";

			while ($superjtrow=mysql_fetch_array($superjtres)) {
				if (array_search($superjtrow["tunnus"], (array) $suoravarasto)!== false) {
					$sel = "checked";
				}
				else {
					$sel = "";
				}

				echo "<tr><th>$superjtrow[nimi] - $superjtrow[nimitys]</th><td><input type='checkbox' name='suoravarasto[]' value='$superjtrow[tunnus]' $sel></td></tr>";
			}

		}

		echo "</table>";


		echo "<table>";

		echo "<tr><td class='back'><br></td></tr><tr><td class='back'><font class='message'>".t("Valinnat:")." </font></td></tr>";

		$sel=array();
		$sel[$jarj] = "selected";

		echo "<tr>
				<th>".t("J�rjestys")."</th>
				<td>
					<select name='jarj'>
					<option value='tuoteno' {$sel["tuoteno"]}>".t("Tuotteittain")."</option>
					<option value='ytunnus' {$sel["ytunnus"]}>".t("Asiakkaittain")."</option>
					<option value='luontiaika' {$sel["luontiaika"]}>".t("Tilausajankohdan mukaan")."</option>
					<option value='toimaika' {$sel["toimaika"]}>".t("Toimitusajankohdan mukaan")."</option>
					</select>
				</td>
			</tr>";

		$sel = '';
		if ($automaaginen != '') $sel = 'CHECKED';

		echo "	<tr>
				<th>".t("Toimita selke�t rivit automaagisesti")."</th>
				<td><input type='checkbox' name='automaaginen' value='tosi_automaaginen' $sel onClick = 'return verify()'></td>
			</tr>";

		$selvar=array();
		$selvar[$saldolaskenta] = "SELECTED";

		echo "	<tr>
				<th>".t("Saldovalinnassa k�ytet��n")."</th>
				<td><select name='saldolaskenta'>
					<option value='myytavissasaldo' {$selvar["myytavissasaldo"]}>".t("Myyt�viss� olevaa m��r��")."</option>
					<option value='hyllysaldo' {$selvar["hyllysaldo"]}>".t("Hyllyss� olevaa m��r��")."</option>
					</select>
				</td></tr>";

		echo "</table>";


		echo "<table>";

		echo "<tr><td class='back'><br></td></tr><tr><td class='back'><font class='message'>".t("Rajaukset:")."</font></td></tr>";

		echo "<tr>
				<th>".t("Toimittaja")."</th>
				<td>
				<input type='text' size='20' name='ytunnus' value='$toimittaja'>
				</td>
			</tr>";

		echo "<tr>
				<th>".t("Asiakas")."</th>
				<td>
				<input type='text' size='20' name='asiakasno' value='$asiakasno'>
				</td>
				</td>
			</tr>";

		echo "<tr>";
		echo "<th>".t("Tuoteosasto")."</th>";

		// tehd��n avainsana query
		$result = t_avainsana("OSASTO");

		echo "<td><select name='tuoteosasto'>";
		echo "<option value=''>".t("Tuoteosasto")."</option>";

		while ($row = mysql_fetch_array($result)) {
			if ($tuoteosasto == $row["selite"]) $sel = "selected";
			else $sel = "";
			echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
		}

		echo "</select></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Tuoteryhm�")."</th>";

		// tehd��n avainsana query
		$result = t_avainsana("TRY");

		echo "<td><select name='tuoteryhma'>";
		echo "<option value=''>".t("Tuoteryhm�")."</option>";

		while ($row = mysql_fetch_array($result)) {
			if ($tuoteryhma == $row["selite"]) $sel = "selected";
			else $sel = "";
			echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
		}

		echo "</select></td>";
		echo "</tr>\n";

		echo "<tr>";
		echo "<th>".t("Tuotemerkki")."</th>";

		$query = "	SELECT distinct tuotemerkki
					FROM tuote use index (yhtio_tuotemerkki)
					WHERE yhtio='$kukarow[yhtio]'
					and tuotemerkki != ''
					ORDER BY tuotemerkki";
		$result = pupe_query($query);

		echo "<td><select name='tuotemerkki'>";
		echo "<option value=''>".t("Tuotemerkki")."</option>";

		while ($row = mysql_fetch_array($result)) {
			if ($tuotemerkki == $row["tuotemerkki"]) $sel = "selected";
			else $sel = "";
			echo "<option value='$row[tuotemerkki]' $sel>$row[tuotemerkki]</option>";
		}

		echo "</select></td>";
		echo "</tr>\n";

		echo "<tr>";
		echo "<th>".t("Maa")."</th>";

		$query = "	SELECT distinct koodi, nimi
					FROM maat
					WHERE nimi != ''
					ORDER BY koodi";
		$result = pupe_query($query);

		echo "<td><select name='maa'>";
		echo "<option value=''>".t("Maa")."</option>";

		while ($row = mysql_fetch_array($result)) {
			if ($maa == $row["koodi"]) $sel = "selected";
			else $sel = "";
			echo "<option value = '".strtoupper($row[0])."' $sel>".t($row[1])."</option>";
		}

		echo "</select></td>";
		echo "</tr>\n";

		echo "<tr>
				<th>".t("Tuotenumero")."</th>
				<td>
				<input type='text' name='tuotenumero' value='$tuotenumero' size='10'>
				</td>
				</td>
			</tr>";

		echo "<tr>
				<th>".t("Tilaus")."</th>
				<td>
				<input type='text' name='tilaus' value='$tilaus' size='10'>
				</td>
				</td>
			</tr>";

		echo "<tr>
				<th>",t("N�yt� vain jt-rivit jos asiakkaan j�lkitoimitusten yhteissumma on yli")."</th>
				<td>
				<input type='text' name='summarajaus' value='$summarajaus' size='10'>
				</td>
				</td>
			</tr>";

		$sel = $sel2 = '';
		if ($toimi != '') $sel = 'CHECKED';
		if ($ei_limiittia != '') $sel2 = 'CHECKED';

		echo "<tr>
				<th>".t("N�yt� vain toimitettavat rivit")."</th>
				<td><input type='checkbox' name='toimi' $sel></td>
			</tr>";

		echo "<tr>
				<th>".t("N�yt� kaikki rivit")." (".t("Oletus").": max 1000 ".t("rivi�").")</th>
				<td><input type='checkbox' name='ei_limiittia' $sel2></td>
			</tr>";

		if ($toim == "ENNAKKO") {
			echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
					function verify(){
						msg = '".t("Haluatko todella toimittaa kaikki selke�t ennakkorivit? Eli tied�tk� nyt aivan varmasti mit� olet tekem�ss�")."?';
						return confirm(msg);
					}
					</SCRIPT>";
		}
		else {
			echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
					function verify(){
						msg = '".t("Haluatko todella toimittaa kaikki selke�t JT-Rivit? Eli tied�tk� nyt aivan varmasti mit� olet tekem�ss�")."?';
						return confirm(msg);
					}
					</SCRIPT>";
		}

		$sel = '';
		if ($superit != '') $sel = 'CHECKED';

		echo "	<tr>
				<th>".t("N�yt� vain suoratoimitusrivit")."</th>
				<td><input type='checkbox' name='superit' $sel></td><td class='back'>".t("�l� toimita suoratoimituksia, ellet ole 100% varma ett� voit niin tehd�")."!</td>
				</tr>";

		echo "</table>

			<br><input type='submit' value='".t("N�yt�")."'>
			</form>";
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "jtselaus.php") !== FALSE) {
		if (@include("inc/footer.inc"));
		elseif (@include("footer.inc"));
		else exit;
	}

?>