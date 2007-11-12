<?php

// tarvitaan $laskurow array, jossa on meid‰n keikan otsikko
$debug = 0; // 0 = ei echoilla debug koodia, 1 = ehchoillaa infoa, 2 = echoillaan infoa plus queryt

echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
		<!--

		function toggleAll(toggleBox) {

			var currForm = toggleBox.form;
			var isChecked = toggleBox.checked;
			var nimi = toggleBox.name;

			for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
				if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,3) == nimi) {
					currForm.elements[elementIdx].checked = isChecked;
				}
			}
		}

		//-->
		</script>";

$varastoon_msg  = "";

if ($tee == "") {
	$varastoon_msg .= "<form action='$PHP_SELF' method='post'>";
	$varastoon_msg .= "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";

	if ($toiminto == "kaikkiok") {
		$varastoon_msg .= "<input type='hidden' name='toiminto' value='kaikkiok'>";
	}
	else {
		$varastoon_msg .= "<input type='hidden' name='toiminto' value='kalkyyli'>";
	}

	$varastoon_msg .= "<input type='hidden' name='tee' value='eitullia'>";
	$varastoon_msg .= "<input type='hidden' name='otunnus' value='$otunnus'>";
}

$varastoon_msg .= "	<table>
					<th valign='top'>".t("Varastoon")."</th>
					<th valign='top'>".t("Tuoteno")."</th>
					<th valign='top'>".t("M‰‰r‰")."<br>".t("sis").".</th>
					<th valign='top'>".t("M‰‰r‰")."<br>".t("ulk").".</th>
					<th valign='top'>".t("Alennus")."</th>
					<th valign='top'>".t("Ostohinta")."<br>".t("ulk").".</th>
					<th valign='top'>".t("Ostohinta")."<br>".t("sis").".</th>
					<th valign='top'>".t("Osuus")."<br>".t("rahdista")."</th>
					<th valign='top'>".t("Tulli")."</th>
					<th valign='top'>".t("Ostohinta")."+<br>".t("Rahti")."+".t("Tulli")."</th>
					<th valign='top'>".t("Rivihinta")."</th>
					<th valign='top'>".t("Vanha")."<br>".t("kehahin")."</th>
					<th valign='top'>".t("Uusi")."<br>".t("kehahin")."</th>
					<th valign='top'>".t("Ei tullia")."</th>
					</tr>";

if (($toiminto == "kaikkiok" and $tee == "varma") or ($toiminto == "kaikkiok" and $tee == "")) {
	// virallinen laskenta, heetaan ihan kaikki rivit uudestaan (varattu=0) kaikki pit‰‰ olla viety varastoon jo ennen t‰t‰..
	
	$query = "	SELECT *, kpl varattu
				FROM tilausrivi
				WHERE uusiotunnus='$laskurow[tunnus]' and varattu=0 and yhtio='$kukarow[yhtio]' and tyyppi='O'
				ORDER BY tunnus";
}
elseif ($toiminto == "kalkyyli" and $tee == "varastoon") {
	// tavallinen laskenta.. VARATTU PITƒƒ OLLA > 0 ja netto-kentt‰ tyhj‰!
	// jos nettokent‰ss‰ on jotain niin t‰t‰ rivi‰ ei haluttu viel‰ vied‰ varastoon
	
	$query = "	SELECT *
				FROM tilausrivi
				WHERE uusiotunnus='$laskurow[tunnus]' and varattu<>0 and yhtio='$kukarow[yhtio]' and tyyppi='O' and netto=''
				ORDER BY tunnus";
}
elseif ($toiminto == "kalkyyli" and $tee == "") {
	// N‰ytet‰‰n ruudulla
	
	$query = "	SELECT *
				FROM tilausrivi
				WHERE uusiotunnus='$laskurow[tunnus]' and varattu<>0 and yhtio='$kukarow[yhtio]' and tyyppi='O'
				ORDER BY tunnus";
	
}
$presult = mysql_query($query) or pupe_error($query);

// lasketaan tilauksen rahtikulut
// arvotaan illan 7 oikein, eli kurssi
if ($laskurow["maksu_kurssi"] == 0) {	//t‰m‰ olisi huonompi juttu, mut ei mik‰‰n stopperi
	$laskurow["maksu_kurssi"] = $laskurow["vienti_kurssi"];
}

// tsekataan viel‰
if ($laskurow["maksu_kurssi"] == 0) {	//t‰m‰ olisi huonompi juttu, mut ei mik‰‰n stopperi
	echo t("Kurssi uupuu! Jossain on iso ongelma ,8,1");
	exit;
}

// jos erikoislale niin otetaan sit‰ kanssa huomioon
if ($laskurow['erikoisale'] <> 0) {
	$erikoisale = 1 - ($laskurow['erikoisale']/100);
}
else {
	$erikoisale = 1;
}

if (($toiminto == "kaikkiok" and $tee == "varma") or ($toiminto == "kaikkiok" and $tee == "")) {
	// jos lasketaan virallista varastonarvoa, otetaan keikan summa huomioon (kululaskujen yhteenlaskettu summa OLETUSVALUUTASSA on laskurow.saldo_maksettu)
	$rahtikulut = $laskurow['saldo_maksettu'] + round($laskurow['rahti_etu'] * $laskurow['maksu_kurssi'], 2);
	$rahtitapa  = t("Virallinen");
}
else {
	// muuten rahtikulut valuutassa laskettuna kuluprosentin mukaan (laskurow.rahti on kuluprosentti, laskurow.summa on kohdistettujen rivien summa)

	// jos erikoislale niin otetaan sit‰ kanssa huomioon
	if ($laskurow['erikoisale']<>0) {
		$erikoisale = 1-($laskurow['erikoisale']/100);
	}
	else {
		$erikoisale = 1;
	}

	// lasketaan nyt varastoon viet‰vien rivien summa yhteens‰
	$query    = "	SELECT sum((tilausrivi.varattu+tilausrivi.kpl)*if(tuotekerroin<=0,1,tuotekerroin) * tilausrivi.hinta * $erikoisale) summa
					FROM tilausrivi use index (uusiotunnus_index)
					JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]' and tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno)
					WHERE tilausrivi.uusiotunnus = '$otunnus'
					and tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.netto = ''
					and tilausrivi.varattu<>0";
	$result   = mysql_query($query) or pupe_error($query);
	$hintarow = mysql_fetch_array($result);
		
	$laskurow['summa'] = $hintarow['summa'];

	// lasketaan itse rahtikulut
	$rahtikulut = round($laskurow['summa'] * $laskurow['maksu_kurssi'] * ($laskurow['rahti'] / 100), 2);
	$rahtitapa  = t("Kuluprosentti") ." ". $laskurow['rahti']. "%";

	// jos ollaan annettu t‰lle batchille kulusumma, k‰ytet‰‰nkin vaan sit‰!
	if ($laskurow["rahti_huolinta"] != 0) {
		$rahtikulut = $laskurow["rahti_huolinta"];
		$rahtitapa  = t("Kulusumma");
	}
}

echo "<br>";
echo "<font class='message'>".t("Keikka")." $laskurow[laskunro] $laskurow[nimi]</font><br>";
echo "<font class='message'>".t("Rahdin m‰‰r‰ yhteens‰").": $rahtikulut $yhtiorow[valkoodi] (".t("Peruste").": $rahtitapa)<br><hr></font>";

$summa		 = 0;
$superirivit = '';
$jtrivit	 = '';
$jtvarastot	 = array();
$rivia 		 = 0;

require_once ('inc/ProgressBar.class.php');

if ($tee != "") {
	$bar = new ProgressBar();
	$elements = mysql_num_rows($presult); // total number of elements to process
}

// jos kyseess‰ on ihan virallinen varastonarvonlaskenta, tehd‰‰n n‰in
if ($toiminto == "kaikkiok" and $tee == "varma") {
	echo "<font class='message'>".t("Suoritetaan j‰lkilaskentaa %s tuotteelle", "", $elements).":<br></font>";
}
else {
	echo "<font class='message'>".t("Vied‰‰n varastoon %s tuotetta", "", $elements).":<br></font>";
}

if ($tee != "") {
	$bar->initialize($elements); // print the empty bar
}

while ($tilrivirow = mysql_fetch_array($presult)) {

	// lasketaan keskihankintahinta, tarvitaan tuotteen saldo ja tuotteen tiedot
	$query  = "SELECT sum(saldo) saldo from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tilrivirow[tuoteno]'";
	$salres = mysql_query($query) or pupe_error($query);
	$salrow = mysql_fetch_array($salres);

	$query  = "	SELECT *
				from tuote
				JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]' and tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno)
				where tuote.yhtio	= '$kukarow[yhtio]' 
				and tuote.tuoteno	= '$tilrivirow[tuoteno]'";
	$tuores = mysql_query($query) or pupe_error($query);
	$tuorow = mysql_fetch_array($tuores);

	if ($tuorow['tuotekerroin'] <= 0) $tuorow['tuotekerroin'] = 1;

	// jos kysess‰ on kotimainen vaihto-omaisuuslasku, pit‰‰ lis‰t‰ tuotteen hintaan alvi
	if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {
		$alvit = 1 + $tuorow["alv"] / 100;
	}
	else {
		$alvit = 1;
	}

	$osuus   = round($erikoisale*$tilrivirow['hinta']*$alvit*abs($tilrivirow['varattu'])*(1-($tilrivirow['ale']/100))*$tuorow['tuotekerroin'],2)/$laskurow['summa'];	// kuinka paljon t‰m‰ rivi on koko tilauksesta
	$rahtios = $osuus*$rahtikulut;	// lasketaan sama osuus rahtikuluista t‰lle riville

	if ($tilrivirow['varattu'] < 0) {
		$rahtios = $rahtios * -1;
	}

	$ohinta  = round($erikoisale*$tilrivirow['hinta']*(1-($tilrivirow['ale']/100))*$tuorow['tuotekerroin']*$laskurow['maksu_kurssi']*$tilrivirow['varattu']+$rahtios,6); // tuotteen rivihinta rahteineen OLETUSVALUUTASSA

	$tulliprossa = "";
	$tv_nimrow	 = "";

	if ($tilrivirow["var"] == "" and $laskurow["maa"] != $yhtiorow["maa"]) {
		// lis‰t‰‰n tulli
		require ("taric_veroperusteet.inc");
		
		$ohinta = $ohinta * (1+($tulliprossa/100));
		$tv_nimrow["maara"] = $tv_nimrow["maara"] * 1; // teh‰‰n numero, ett‰ lista n‰ytt‰‰ kivemmalta
	}

	// lis‰t‰‰n riville extra kulu, jos sellanen oli annettu
	if ($tilrivirow["kate"] != 0) {
		$kuluteksti = "(".round($ohinta,6)." + $tilrivirow[kate])";
		$ohinta = $ohinta + $tilrivirow['kate'];
	}
	else $kuluteksti = "";

	$rivihin = round($ohinta,$yhtiorow['hintapyoristys']);	// tilausrivin rivihinta talteen
	$ohinta  = round($ohinta / $tilrivirow['varattu'],6); // yhden tuotteen hinta kaikkine kuluineen

	//Keskihankintahintaa yll‰pidet‰‰n vain jos tuotteella ei ole sarjanumeroseurantaa tai marginaaliverostusta
	if ($tuorow["sarjanumeroseuranta"] == "S") {
		//Laitetaan kehahintamuuttujaan t‰m‰n keikan ostohinta jotta tapahtumat n‰ytt‰isiv‰t fiksuja lukuja
		$kehahin = $ohinta;
	}
	else {
		if ($salrow['saldo'] + $tilrivirow['varattu'] > 0 and $salrow['saldo'] > 0) {
			// kehahin matikka, tuotteella pit‰‰ olla saldoa ennen ja j‰lkeen ett‰ edes tehd‰‰n matikkaa
			if ($debug >= 1) $varastoon_msg .= "<font class='message'>Tuote: $tuorow[tuoteno] Matikka: kehahin = ($salrow[saldo] * $tuorow[kehahin] + $ohinta * $tilrivirow[varattu]) / ($salrow[saldo] + $tilrivirow[varattu])</font><br>";
			$kehahin = round(($salrow['saldo'] * $tuorow['kehahin'] + $ohinta * $tilrivirow['varattu']) / ($salrow['saldo'] + $tilrivirow['varattu']),6);
		}
		else {
			// Jos j‰‰d‰‰n saldossa t‰m‰nkin tulon j‰lkeen miinukselle tai nollille, laitetaan kehahin suoraan t‰m‰n keikan hinnasta
			$kehahin = round($ohinta,6);
		}	
	}

	$summa += round($ohinta*$tilrivirow['varattu'],2);

	if ($toiminto == "kaikkiok" and $tee == "varma") {
		// tarvitaan
		// $tuoteno =	korjattava tuote
		// $pvm = mihin p‰iv‰‰n asti korjataan
		// $uusihinta = mik‰ on tuon pvm:n oikea ostohinta
		// $rivitunnus = mik‰ on tapahtuman tehneen rivin tunnus

		$tuoteno	= $tilrivirow["tuoteno"];
		$uusihinta	= $ohinta;
		$pvm		= $tilrivirow["laskutettuaika"]; // koska t‰m‰ tuote oli viety varastoon
		$rivitunnus = $tilrivirow["tunnus"]; // rivin tunnus, t‰ll‰ lˆydet‰‰n varmasti oikea tapahtuma

		$uusikehahin = jalkilaskentafunktio($tuoteno, $pvm, $uusihinta, $rivitunnus);

		$uusikehahin = "-> $uusikehahin";
	}
	elseif($toiminto == "kalkyyli" and $tee == "varastoon") {
		
		// p‰ivitet‰‰n tilausrivin rivihintaa
		$query  = "	UPDATE tilausrivi set
					rivihinta = '$rivihin'
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$tilrivirow[tunnus]'";
		$resultxxx = mysql_query($query) or pupe_error($query);

		if ($tuorow["sarjanumeroseuranta"] == "S") {
			$kehalis = "";
		}
		else {
			$kehalis = " kehahin = '$kehahin', ";
		}

		// p‰ivitet‰‰n tuote
		$query = "	UPDATE tuote set
					$kehalis
					vihahin     = round('$ohinta','$yhtiorow[hintapyoristys]'),
					vihapvm     = now()
					WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tilrivirow[tuoteno]'";
		$result = mysql_query($query) or pupe_error($query);


		// muuten t‰ss‰ ollaan sitten viem‰ss‰ kamaa ihan oikeesti saldoille.. tehd‰‰n siihen liittyv‰t hommat.
		$uusikehahin = "";

		if($tilrivirow["tunnus"] != $tilrivirow["perheid2"] and $tilrivirow["perheid2"]>0) {
			$lisavartlisa = " ".t("Saldo on varattu.");
		}
		else {
			$lisavartlisa = "";
		}
		
		// lis‰t‰‰n tapahtuma
		$query = "	INSERT into tapahtuma set
					yhtio      = '$kukarow[yhtio]',
					tuoteno    = '$tilrivirow[tuoteno]',
					kpl        = '$tilrivirow[varattu]',
					hinta      = '$kehahin',
					kplhinta   = '$ohinta',
					laji       = 'tulo',
					selite     = '$laskurow[nimi] $laskurow[nimitark], ".t("Tilaus").": $tilrivirow[otunnus], ".t("Ostohinta").": $ohinta $lisavartlisa',
					laatija    = '$kukarow[kuka]',
					rivitunnus = '$tilrivirow[tunnus]',
					laadittu   = now()";
		$result = mysql_query($query) or pupe_error($query);

		// p‰ivitet‰‰n saldo tuoterivill‰ olevalle paikalle
		$query = "	UPDATE tuotepaikat set
					saldo       	= saldo+$tilrivirow[varattu],
					saldo_varattu 	= saldo_varattu + if($tilrivirow[tunnus] != $tilrivirow[perheid2] and $tilrivirow[perheid2]>0, $tilrivirow[varattu], 0),
					saldoaika   	= now()
					where yhtio 	= '$kukarow[yhtio]' and
					tuoteno     	= '$tilrivirow[tuoteno]' and
					hyllyalue   	= '$tilrivirow[hyllyalue]' and
					hyllynro    	= '$tilrivirow[hyllynro]' and
					hyllytaso   	= '$tilrivirow[hyllytaso]' and
					hyllyvali   	= '$tilrivirow[hyllyvali]'
					limit 1";
		$result = mysql_query($query) or pupe_error($query);
		$jtvarastot[$tilrivirow["tunnus"]] = array($tilrivirow["hyllyalue"], $tilrivirow["hyllynro"]);
		
		if (mysql_affected_rows() == 0) {
			$varastoon_msg .= "<font class='error'>".t("Varastopaikka")." $tilrivirow[hyllyalue]-$tilrivirow[hyllynro]-$tilrivirow[hyllytaso]-$tilrivirow[hyllyvali] ".t("ei lˆydy vaikka se on syˆtetty tilausriville! Koitetaan p‰ivitt‰‰ oletustapaikkaa!")."</font>";

			$query = "	UPDATE tuotepaikat
	   		 			SET saldo      	= saldo+$tilrivirow[varattu],
						saldo_varattu 	= saldo_varattu + if($tilrivirow[tunnus] != $tilrivirow[perheid2] and $tilrivirow[perheid2]>0, $tilrivirow[varattu], 0),
	   		 			saldoaika   	= now()
	   		 			WHERE yhtio 	= '$kukarow[yhtio]' and
	   		 			tuoteno     	= '$tilrivirow[tuoteno]' and
	   		 			oletus     	   != ''
	   		 			LIMIT 1";
			$result = mysql_query($query) or pupe_error($query);
			
			$query = "	SELECT hyllyalue, hyllynro
						FROM tuotepaikat
						WHERE yhtio 	= '$kukarow[yhtio]' and
	   		 			tuoteno     	= '$tilrivirow[tuoteno]' and
	   		 			oletus     	   != ''
	   		 			LIMIT 1";
			$olpares = mysql_query($query) or pupe_error($query);
			$olparow = mysql_fetch_array($tuores);
			
			$jtvarastot[$tilrivirow["tunnus"]] = array($olparow["hyllyalue"], $olparow["hyllynro"]);
			
	   		if (mysql_affected_rows() == 0) {

	   			$varastoon_msg .= "<br><font class='error'>".t("Tuotteella")." $tilrivirow[tuoteno] ".t("ei ole oletuspaikkaakaaaaan")."!!!</font><br><br>";

				// haetaan firman eka varasto, tˆk‰t‰‰n kama sinne ja tehd‰‰n siit‰ oletuspaikka
				$query = "select alkuhyllyalue, alkuhyllynro from varastopaikat where yhtio='$kukarow[yhtio]' and tyyppi='' order by alkuhyllyalue, alkuhyllynro limit 1";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) != 1) {
					// haetaan firman eka varasto, tˆk‰t‰‰n kama sinne ja tehd‰‰n siit‰ oletuspaikka
					$query = "select alkuhyllyalue, alkuhyllynro from varastopaikat where yhtio='$kukarow[yhtio]' order by alkuhyllyalue, alkuhyllynro limit 1";
					$result = mysql_query($query) or pupe_error($query);
				}

				if (mysql_num_rows($result) == 1) {
					$hyllyrow =  mysql_fetch_array($result);
				}
				else {
					$hyllyrow = array();
					$hyllyrow["alkuhyllyalue"] = "A";
					$hyllyrow["alkuhyllynro"]  = "0";
				}

	   			$varastoon_msg .= "<br><font class='error'>".t("Tehtiin oletuspaikka")." $tilrivirow[tuoteno]: $hyllyrow[alkuhyllyalue] $hyllyrow[alkuhyllynro] 0 0</font><br><br>";

				// lis‰t‰‰n paikka
				$query = "	INSERT INTO tuotepaikat set
	 						yhtio			= '$kukarow[yhtio]',
				 			tuoteno     	= '$tilrivirow[tuoteno]',
				 			oletus      	= 'X',
		   		 			saldo       	= '$tilrivirow[varattu]',
							saldo_varattu 	= if($tilrivirow[tunnus] != $tilrivirow[perheid2] and $tilrivirow[perheid2]>0, $tilrivirow[varattu], 0),
		   		 			saldoaika   	= now(),
							hyllyalue   	= '$hyllyrow[alkuhyllyalue]',
							hyllynro    	= '$hyllyrow[alkuhyllynro]',
							hyllytaso   	= '0',
							hyllyvali   	= '0'";
				$result = mysql_query($query) or pupe_error($query);
				$jtvarastot[$tilrivirow["tunnus"]] = array($hyllyrow["alkuhyllyalue"], $hyllyrow["alkuhyllynro"]);

				// tehd‰‰n tapahtuma
				$query = "	INSERT into tapahtuma set
							yhtio 		= '$kukarow[yhtio]',
							tuoteno 	= '$tilrivirow[tuoteno]',
							kpl 		= '0',
							kplhinta	= '0',
							hinta 		= '0',
							laji 		= 'uusipaikka',
							selite 		= '".t("Lis‰ttiin tuotepaikka")." $hyllyrow[alkuhyllyalue] $hyllyrow[alkuhyllynro] 0 0',
							laatija 	= '$kukarow[kuka]',
							laadittu 	= now()";
				$result = mysql_query($query) or pupe_error($query);
	   		}
			else {
				$varastoon_msg .= "<font class='error'>".t("Huh, se onnistui!")."</font><br>";
			}
		}

		// p‰ivitet‰‰n tilaus saapuneeksi varastoon
		// p‰ivitet‰‰n myˆs tehdaslis‰varusteet saapuneiksi
		$query  = "	UPDATE tilausrivi set
					kpl     = varattu,
					varattu = 0,
					laskutettuaika = now(),
					laskutettu = '$kukarow[kuka]'
					WHERE yhtio='$kukarow[yhtio]' and otunnus='$tilrivirow[otunnus]' and (tunnus='$tilrivirow[tunnus]' or perheid='$tilrivirow[tunnus]')";
		$varastoon_result = mysql_query($query) or pupe_error($query);

		$rivia ++;

		// n‰m‰ halutaan koittaa suoratoimittaa
		if ($tilrivirow['tilaajanrivinro'] <> 0) {
			$superirivit .= "'$tilrivirow[tunnus]',";
		}
		
		//Tutkitaan lˆytyykˆ JT-rivi joka m‰pp‰ytyy t‰h‰n ostoriviin
	   	$query = "	SELECT tilausrivi.tunnus
	   				FROM tilausrivin_lisatiedot
	   				JOIN tilausrivi ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
	   				WHERE tilausrivin_lisatiedot.yhtio 			 = '$kukarow[yhtio]' 
	   				and tilausrivin_lisatiedot.tilausrivilinkki  = '$tilrivirow[tunnus]'";
		$varastoon_result = mysql_query($query) or pupe_error($query);
		
		if ($varastoon_row = mysql_fetch_array($varastoon_result)) {
			$jtrivit .= "$varastoon_row[tunnus],";
		}
	}

	// n‰ytet‰‰n ruudulla
	$varastoon_msg .= "	<tr>";
	
	if ($toiminto == "kalkyyli" and $tee == "") {
		$chk = "";
		if ($tilrivirow["netto"] == '') {
			$chk = "CHECKED";
		}

		$varastoon_msg .= "<td align='center'><input type='checkbox' name='varastoonko[]' value='$tilrivirow[tunnus]' $chk></td>";
	}
	else {	
		if ($tilrivirow["netto"] == '') {
			$varastoon_msg .= "<td align='center'>x</td>";
		}
		else {
			$varastoon_msg .= "<td></td>";
		}
	}

	$varastoon_msg .= "	<td>$tilrivirow[tuoteno]</td>
						<td align='right'>$tilrivirow[varattu]</td>
						<td align='right'>" . round($tilrivirow['varattu']*$tuorow['tuotekerroin'],2) . "</td>
						<td align='right'>$tilrivirow[ale]%</td>
						<td align='right'>$tilrivirow[hinta]</td>
						<td align='right'>" . round($tilrivirow['hinta']*$tuorow['tuotekerroin'],2) . "</td>
						<td align='right'>" . round($osuus,6) . "</td>
						<td align='right'>$tv_nimrow[maara] $tv_nimrow[rahayksikko]</td>
						<td align='right'>$ohinta</td>
						<td align='right'>" . round($ohinta*$tilrivirow['varattu'],2) . " $kuluteksti</td>";


	if ($tuorow["sarjanumeroseuranta"] == "S") {
		$varastoon_msg .= "	<td align='right'></td>
							<td align='right'></td>";
	}
	else {
		$varastoon_msg .= "	<td align='right'>$tuorow[kehahin]</td>
							<td align='right'>$kehahin $uusikehahin</td>";
	}

	if ($tee == "") {
		$chk = "";
		if ($tilrivirow["var"] != '') {
			$chk = "CHECKED";
		}
		
		$varastoon_msg .= "<td align='center'><input type='checkbox' name='eitullia[]' value='$tilrivirow[tunnus]' $chk></td>";
	}
	else {
		if ($tilrivirow["var"] != '') {
			$varastoon_msg .= "<td>".t("ei tullia")."</td>";
		}
		else {
			$varastoon_msg .= "<td></td>";
		}
	}

	$varastoon_msg .= "</tr>";
	
	
	if ($tee != "") {
		$bar->increase(); //calls the bar with every processed element
	}
}

echo $varastoon_msg."<br>";

// jos kyseess‰ on ihan virallinen varastonarvonlaskenta, tehd‰‰n n‰in
if ($toiminto == "kaikkiok" and $tee == "varma") {
	
	$rivia = mysql_num_rows($presult); // kaunistelua vaan loppua varten... t‰‰ tehd‰‰n elsess‰ affected rowssilla..

	// laitetaan kohdistettu X kun lasku on viety varastoon...	laitetaan MAPVM kentt‰‰n t‰m‰ p‰iv‰ niin tiedet‰‰n koska viety varastoon
	$query = "	UPDATE lasku set kohdistettu='X', alatila='X', mapvm=now()
				WHERE tunnus='$laskurow[tunnus]' and yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	// haetaan keikan vaihto-omaisuuslaskut (kotimaa, eu tai ei-eu)
	$query = "	SELECT vanhatunnus
				FROM lasku
				WHERE yhtio='$kukarow[yhtio]'
				and tila='K'
				and laskunro='$laskurow[laskunro]'
				and vanhatunnus<>0
				and vienti in ('C','F','I','J','K','L')";
	$result = mysql_query($query) or pupe_error($query);

	// jos on, haetaan liitetyn laskun tiedot
	if (mysql_num_rows($result) >= 1) {

		while($llrow = mysql_fetch_array($result)) {

			//T‰m‰ on ostoreskontran lasku
			$query   = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$llrow[vanhatunnus]'";
			$llres   = mysql_query($query) or pupe_error($query);
			$kulurow = mysql_fetch_array($llres);
			// tehd‰‰n viel‰ kirjapitokakkaa...
			// p‰ivitet‰‰n tilaus saapuneeksi varastoon kirjanpitoon
			$query = "	SELECT tunnus, summa
						FROM tiliointi
						WHERE yhtio='$kukarow[yhtio]' and ltunnus='$kulurow[tunnus]' and tilino = '$yhtiorow[matkalla_olevat]' and korjattu=''";
			$result1 = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result1) == 1) { // Tiliointi lˆytyi
				$tiliointirow=mysql_fetch_array($result1);

				$varastotili = $yhtiorow["varasto"];

				if (($kulurow["vienti"]=='J') or ($kulurow["vienti"]=='K') or ($kulurow["vienti"]=='L')) {
					$varastotili = $yhtiorow["raaka_ainevarasto"];
				}

				$query = "INSERT into tiliointi set
								yhtio	= '$kukarow[yhtio]',
								ltunnus	= '$kulurow[tunnus]',
								tilino	= '$yhtiorow[matkalla_olevat]',
								tapvm	= now(),
								summa	= $tiliointirow[summa] * -1,
								selite	= '".t("Lasku kirjattiin varastoon")." $kulurow[nimi]',
								lukko	= '',
								laatija	= '$kukarow[kuka]',
								laadittu= now()";
				$result2 = mysql_query($query) or pupe_error($query);

				$query = "INSERT into tiliointi set
								yhtio	= '$kukarow[yhtio]',
								ltunnus	= '$kulurow[tunnus]',
								tilino	= '$varastotili',
								tapvm	= now(),
								summa	= '$tiliointirow[summa]',
								selite	= '".t("Lasku kirjattiin varastoon")." $kulurow[nimi]',
								lukko	= '',
								laatija = '$kukarow[kuka]',
								laadittu = now()";
				$result3 = mysql_query($query) or pupe_error($query);
			}
			else {
				echo "<font class='error'>".t("En lˆyt‰nyt matkallaolevat kirjausta!")." $kulurow[tunnus] $yhtiorow[matkalla_olevat]</font><br>";
			}
		}
	}
	else {
		echo "<font class='error'>".t("En lˆyt‰nyt keikan vaihto-omaisuuslaskua! PAHA VIRHE!")."<br><br></font>";
	}
}

if ($toiminto == "kalkyyli" and $tee == "") {
	echo "<tr><td align='center'><input type='checkbox' name='var' onclick='toggleAll(this);'></td><td colspan='13'><--".t("Ruksaa kaikki")."</td></tr>";
}

echo "<tr>";

if ($tee == "") {
	echo "<td class='tumma' align='center'><input type='submit' value='".t("P‰ivit‰")."'></td>";
}
else {
	echo "<th></th>";
}

echo "<th colspan='9' align='right'>".t("Yhteens‰").":</th><td class='tumma' align='right'>".sprintf("%.2f", $summa)."</th><th colspan='2'></th>";

if ($tee == "") {
	echo "<th><input type='submit' value='".t("P‰ivit‰")."'></form></th>";
}
else {
	echo "<th></th>";
}
			
echo "</tr>";


echo "</table><br>";

if ($toiminto == "kaikkiok" and $tee == "varma") {
	echo "<font class='message'>".t("Vietiin keikka")." $laskurow[laskunro] ".t("varastoon")."! ".t("Virallinen varastonarvo laskettu").": $rivia ".t("rivi‰").".</font><br><br>";
}
elseif ($toiminto == "kalkyyli" and $tee == "varastoon") {
	echo "<font class='message'>".t("Vietiin keikka")." $laskurow[laskunro] ".t("varastoon").": $rivia ".t("rivi‰").".</font><br><br>";

	$query 	= "SELECT automaatti_toimitus FROM toimi where yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
	$result	= mysql_query($query) or pupe_error($query);
	$atrow 	= mysql_fetch_array($result);

	// jos ei olla virallisessa toimitellaan suoratoimituksia
	if ($superirivit != "" and $suoratoimitus == '' and $atrow['automaatti_toimitus'] == 'S') {
		$superirivit 	= substr($superirivit,0,-1);
		$mista 			= 'varastoon.inc';
	
		require('jt_super_laskuta.inc');
	
		echo "<br><font class='message'>$laskuvirhe</font><br><br>";
	}

	//	Halutaanko toimittaa j‰lkk‰reit‰ automaattisesti ja saako k‰ytt‰j‰ ylip‰‰t‰‰n k‰sitell‰ j‰lkk‰reit‰..
	$query = "	SELECT paivitys
				FROM oikeu
				WHERE yhtio	= '$kukarow[yhtio]'
				and kuka	= '$kukarow[kuka]'
				and nimi	= 'tilauskasittely/jtselaus.php'
				and alanimi = ''";
	$jtoikeudetres = mysql_query($query) or pupe_error($query);

	if ($jtrivit != "" and mysql_num_rows($jtoikeudetres)<>0 and $yhtiorow["automaattinen_jt_toimitus"] != "") {
	
		$jtoikeudetrow = mysql_fetch_array($jtoikeudetres);
	
		$jtrivit 	= substr($jtrivit,0,-1);
		$mista 		= 'varastoon.inc';
	
		$varastosta = array();
	
		foreach ($jtvarastot as $varasto) {
			$v = kuuluukovarastoon($varasto[0], $varasto[1]);
		
			if ($v > 0 and !in_array($v, $varastosta)) {
				$varastosta[$v] = $v;			
			}
		}
	
	
		function jt_toimita ($toimittaja, $toimittajaid, $varastosta, $jtrivit, $automaaginen, $tee) {
			global $yhtiorow, $kukarow, $oikeurow;
		
			$toimittaja		= "";
			$toimittajaid	= "";
			$asiakasno 		= "";
			$asiakasid		= "";
			$asiakasmaa 	= "";
			$jarj	 		= "tuoteno";
			$tuotenumero	= "";
			$tilaus			= "";			
			$toimi			= "";
			$superit		= "";
			$tilaus_on_jo	= "";
		
			$vain_rivit 		= $jtrivit;
			$from_varastoon_inc = "varastoon.inc";
		
			require('jtselaus.php');
			
		}

		jt_toimita($laskurow["ytunnus"], $laskurow["liitostunnus"], $varastosta, $jtrivit, "automaaginen", "JATKA");
		jt_toimita($laskurow["ytunnus"], $laskurow["liitostunnus"], $varastosta, $jtrivit, "tosi_automaaginen", "JATKA");
	
		//	Laitetaan tavarat liikkeelle jos sallitaan!
		if($jtoikeudetrow["paivitys"] == 1 and $yhtiorow["automaattinen_jt_toimitus"] == "T") {
			jt_toimita("ytunnus", "liitostunnus", "", "", "tosi_automaaginen", "TOIMITA");	
		}
	}

	echo "<hr>";
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toiminto' value=''>";
	echo "<input type='hidden' name='toimittajaid' value='$laskurow[liitostunnus]'>";
	echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
	echo "<input type='submit' value='".t("Takaisin keikan valintaan")."'>";
	echo "</form>&nbsp;";
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toiminto' value=''>";
	echo "<input type='hidden' name='toimittajaid' value=''>";
	echo "<input type='hidden' name='ytunnus' value=''>";
	echo "<input type='submit' value='".t("Takaisin toimittajan valintaan")."'>";
	echo "</form>";
}

?>