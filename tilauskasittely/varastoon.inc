<?php

// tarvitaan $laskurow array, jossa on meid‰n keikan otsikko
$debug = 0; // 0 = ei echoilla debug koodia, 1 = ehchoillaa infoa, 2 = echoillaan infoa plus queryt

# Muuttuja kun vied‰‰n tilausrivi kerrallaan.
$varastoon_tilausurivirajaus = "";

if ($toiminto == "kalkyyli" and $tee == "" and $kauttalaskutus == '') {
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

			function WriteText(sarake) {
				var count = document.riviformi.elements.length;
				var sarakenimi = sarake+'_kaikki';

				for (j=0; j<count; j++) {
					var element = document.riviformi.elements[j];

					if (element.name.substring(0,3) == sarake) {
						element.value = document.getElementById(sarakenimi).value;
					}
				}
			}

			//-->
			</script>";
}

$varastoon_msg  		  = "";
$jalkilaskenta_debug_text = "";

if ($tee == "" and $kauttalaskutus == '') {
	$varastoon_msg .= "<form name='riviformi' method='post'>";
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

if ($kauttalaskutus == '') {

	// Tarkastetaan onko taricit k‰ytˆss‰
	$tv_kaytossa = FALSE;

	$query = "SELECT count(*) kpl from taric_veroperusteet";
	$tv_res = pupe_query($query);
	$tv_row = mysql_fetch_assoc($tv_res);

	if ($tv_row["kpl"] > 0) {
		$tv_kaytossa = TRUE;
	}

	$varastoon_msg .= "	<table><tr>
						<th valign='top'>".t("Varastoon")."</th>
						<th valign='top'>".t("Varastoonvientip‰iv‰")."</th>";

	if ($toiminto == "kalkyyli" and ($tee == '' or $tee == 'varastoon') and $yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) {
		$varastoon_msg .= "<th valign='top'>".t("Saapuminen")."</th>";
		$varastoon_msg .= "<th valign='top'>".t("Suuntalava")."</th>";
	}

	$varastoon_msg .= "	<th valign='top'>".t("Tuoteno")."</th>
						<th valign='top'>".t("M‰‰r‰")." ".t("sis").".<br>".t("M‰‰r‰")." ".t("ulk").".</th>";

	for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
		$varastoon_msg .= " <th valign='top'>".t("Alennus")."{$alepostfix}</th>";
	}

	$varastoon_msg .= "	<th valign='top'>".t("Ostohinta")." ".t("ulk").".<br>".t("Ostohinta")." ".t("sis").".</th>
						<th valign='top'>".t("Osuus")."<br>".t("rahdista")."</th>
						<th valign='top'>".t("Rahti")."</th>";

	if ($tv_kaytossa) $varastoon_msg .= "<th valign='top'>".t("Tulli")."</th>";

	$varastoon_msg .= "<th valign='top'>".t("Ostohinta")."+<br>".t("Rahti")."+<br>".t("Tulli")."</th>
						<th valign='top'>".t("Rivihinta")."</th>
						<th valign='top'>".t("Vanha")." ".t("kehahin")."<br>".t("Uusi")." ".t("kehahin")."</th>";

	if ($toiminto == "kalkyyli" and $tee == "") {
		$varastoon_msg .= "<th valign='top'>".t("Vanha")." ".t("saldo")."<br>".t("Uusi")." ".t("saldo")."</th>";
	}

	if ($tv_kaytossa) $varastoon_msg .= "<th valign='top'>".t("Ei tullia")."</th>";

	$varastoon_msg .= "</tr>";
}

if (($toiminto == "kaikkiok" and $tee == "varma") or ($toiminto == "kaikkiok" and $tee == "")) {
	// virallinen laskenta, heetaan ihan kaikki rivit uudestaan (varattu=0) kaikki pit‰‰ olla viety varastoon jo ennen t‰t‰..
	$query = "	SELECT tilausrivi.*, tilausrivi.kpl varattu
				FROM tilausrivi
				JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus)
				LEFT JOIN tuotteen_toimittajat ON (tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = lasku.liitostunnus)
				WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]'
				and tilausrivi.varattu = 0
				and tilausrivi.kpl != 0
				and tilausrivi.yhtio = '$kukarow[yhtio]'
				and	tilausrivi.tyyppi = 'O'
				ORDER BY tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno, tilausrivi.kpl DESC, tilausrivi.laskutettuaika";
}
elseif ($toiminto == "kalkyyli" and $tee == "varastoon") {

	$uusiotunnuslisa = "tilausrivi.uusiotunnus = '{$laskurow['tunnus']}'";
	$suuntalavajoin = "";

	# Vied‰‰n vain yksi rivi varastoon
	if ($vie_vain_tama_tunnus_varastoon != 0) {
		$varastoon_tilausurivirajaus = "AND tilausrivi.tunnus = $vie_vain_tama_tunnus_varastoon";
	}

	if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) {

		$query = "	SELECT GROUP_CONCAT(saapuminen) keikkatunnus
					FROM suuntalavat_saapuminen
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND suuntalava = '{$suuntalavan_tunnus}'";
		$uusiotunnus_chk_res = pupe_query($query);
		$uusiotunnus_chk_row = mysql_fetch_assoc($uusiotunnus_chk_res);

		$uusiotunnuslisa = "tilausrivi.uusiotunnus IN ({$uusiotunnus_chk_row['keikkatunnus']})";

		$suuntalavajoin = " JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus = tilausrivi.suuntalava AND suuntalavat.tila = 'S' AND suuntalavat.tunnus = '{$suuntalavan_tunnus}')
							JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = tilausrivi.uusiotunnus) ";
	}

	// tavallinen laskenta.. VARATTU PITƒƒ OLLA > 0 ja netto-kentt‰ tyhj‰!
	// jos nettokent‰ss‰ on jotain niin t‰t‰ rivi‰ ei haluttu viel‰ vied‰ varastoon
	$query = "	SELECT tilausrivi.*
				FROM tilausrivi
				JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus)
				LEFT JOIN tuotteen_toimittajat ON (tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = lasku.liitostunnus)
				{$suuntalavajoin}
				WHERE {$uusiotunnuslisa}
				and tilausrivi.varattu <> 0
				and tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.tyyppi = 'O'
				and tilausrivi.netto = ''
				$varastoon_tilausurivirajaus
				ORDER BY tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno, tilausrivi.varattu DESC, tilausrivi.laskutettuaika";
}
elseif ($toiminto == "kalkyyli" and $tee == "") {

	$uusiotunnuslisa = "tilausrivi.uusiotunnus = '{$laskurow['tunnus']}'";
	$suuntalavajoin = "";

	if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) {

		$query = "	SELECT GROUP_CONCAT(saapuminen) keikkatunnus
					FROM suuntalavat_saapuminen
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND suuntalava = '{$suuntalavan_tunnus}'";
		$uusiotunnus_chk_res = pupe_query($query);
		$uusiotunnus_chk_row = mysql_fetch_assoc($uusiotunnus_chk_res);

		$uusiotunnuslisa = "tilausrivi.uusiotunnus IN ({$uusiotunnus_chk_row['keikkatunnus']})";

		$suuntalavajoin = " JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus = tilausrivi.suuntalava AND suuntalavat.tila = 'S' AND suuntalavat.tunnus = '{$suuntalavan_tunnus}')
							JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = tilausrivi.uusiotunnus) ";
	}

	// N‰ytet‰‰n ruudulla
	$query = "	SELECT tilausrivi.*
				FROM tilausrivi
				JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.uusiotunnus)
				LEFT JOIN tuotteen_toimittajat ON (tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = lasku.liitostunnus)
				{$suuntalavajoin}
				WHERE {$uusiotunnuslisa}
				and tilausrivi.varattu <> 0
				and tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.tyyppi = 'O'
				ORDER BY tuotteen_toimittajat.toim_tuoteno, tilausrivi.tuoteno, tilausrivi.varattu DESC, tilausrivi.laskutettuaika";
}

$presult = pupe_query($query);

// jos meill‰ on valuuttakeikka, lasketaan keskikurssi kaikille vaihto-omaisuuslaskuille
$query  = "	SELECT sum(summa) summa, sum(arvo) arvo, ifnull(round(sum(summa * vienti_kurssi) / sum(summa), 6), 0) vientikurssi
			FROM lasku
			WHERE yhtio = '$kukarow[yhtio]'
			and tila = 'K'
			and alatila in ('','S')
			and vanhatunnus <> 0
			and laskunro = '$laskurow[laskunro]'
			and vienti in ('C','F','I','J','K','L')";
$result = pupe_query($query);
$kurssirow = mysql_fetch_assoc($result);

if ($laskurow["vienti_kurssi"] != 1 and $kurssirow["vientikurssi"] != 0) {
	$laskurow["vienti_kurssi"] = $kurssirow["vientikurssi"];
}

// Onko miell‰ oletuksesta poikkeava alvi (esim alvimuutoksen yhteydess‰)
$simualv = round(100 * (($kurssirow["summa"]/$kurssirow["arvo"])-1),2);

if (in_array($simualv, array(8,12,17,22))) {
	$simualv = (float) $simualv;
}
else {
	$simualv = 0;
}

// tsekataan viel‰
if ($laskurow["vienti_kurssi"] == 0) {
	echo t("Kurssi uupuu! Jossain on iso ongelma ,8,1");

	if ($kauttalaskutus == '') {
		exit;
	}
}

if ((($toiminto == "kaikkiok" and $tee == "varma") or ($toiminto == "kaikkiok" and $tee == "")) and ($yhtiorow["jalkilaskenta_kuluperuste"] == "" or ($yhtiorow["jalkilaskenta_kuluperuste"] == "PX" and $laskurow["saldo_maksettu"] !=0))) {
	// jos lasketaan virallista varastonarvoa, otetaan keikan summa huomioon (kululaskujen yhteenlaskettu summa OLETUSVALUUTASSA on laskurow.saldo_maksettu)
	$rahtikulut = $laskurow['saldo_maksettu'] + round($laskurow['rahti_etu'] * $laskurow['vienti_kurssi'], 6);
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

	if ($toiminto == "kaikkiok") {
		$maara_peruste = "and tilausrivi.varattu = 0 and tilausrivi.kpl != 0";
	}
	else {
		$maara_peruste = "and tilausrivi.varattu <> 0";
	}

	// lasketaan nyt varastoon viet‰vien rivien summa yhteens‰
	$query    = "	SELECT sum((tilausrivi.varattu + tilausrivi.kpl) * if(tuotteen_toimittajat.tuotekerroin <= 0 or tuotteen_toimittajat.tuotekerroin is null, 1, tuotteen_toimittajat.tuotekerroin) * tilausrivi.hinta * $erikoisale) summa
					FROM tilausrivi use index (uusiotunnus_index)
					LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.liitostunnus = '{$laskurow["liitostunnus"]}' and tuotteen_toimittajat.yhtio = tilausrivi.yhtio and tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno)
					WHERE tilausrivi.uusiotunnus = '$otunnus'
					and tilausrivi.yhtio = '{$kukarow["yhtio"]}'
					and	tilausrivi.tyyppi = 'O'
					and tilausrivi.netto = ''
					$maara_peruste";
	$result   = pupe_query($query);
	$hintarow = mysql_fetch_assoc($result);

	$laskurow['summa'] = $hintarow['summa'];

	// lasketaan itse rahtikulut
	$rahtikulut = round($laskurow['summa'] * $laskurow['vienti_kurssi'] * ($laskurow['rahti'] / 100), 2);
	$rahtitapa  = t("Kuluprosentti") ." ". $laskurow['rahti']. "%";

	// jos ollaan annettu t‰lle batchille kulusumma, k‰ytet‰‰nkin vaan sit‰!
	if ($laskurow["rahti_huolinta"] != 0) {
		$rahtikulut = $laskurow["rahti_huolinta"];
		$rahtitapa  = t("Kulusumma");
	}
}

if ($mobiili_keikka != "yes" and $kauttalaskutus == '') {
	echo "<br>";
	echo "<font class='message'>".t("Saapuminen")." $laskurow[laskunro] $laskurow[nimi]</font><br>";
	echo "<font class='message'>".t("Rahdin m‰‰r‰ yhteens‰").": $rahtikulut $yhtiorow[valkoodi] (".t("Peruste").": $rahtitapa)<br><hr></font>";
}

$summa		 	 = 0;
$jtrivit	 	 = array();
$jtrivit_paikat	 = array();
$jtvarastot	 	 = array();
$rivia 		 	 = 0;
$korjattavat_valmistukset 	  = array();
$korjattavat_valmistukset_ind = 0;
$suoralisa 	= "";

if ($yhtiorow['automaattinen_jt_toimitus'] == 'S') {
	$suoralisa = "AND tilausrivin_lisatiedot.suoraan_laskutukseen = 'o'";
}

// Oletusvarastoonvientip‰iv‰
$ovpppa = date("d");
$ovpkka = date("m");
$ovpvva = date("Y");

//Avoimen kauden tiedot
list($tkavv,$tkakk,$tkapp) = explode("-", $yhtiorow["tilikausi_alku"]);
list($tklvv,$tklkk,$tklpp) = explode("-", $yhtiorow["tilikausi_loppu"]);

$tilikausi_alku  = (int) date('Ymd',mktime(0,0,0, $tkakk, $tkapp, $tkavv));
$tilikausi_loppu = (int) date('Ymd',mktime(0,0,0, $tklkk, $tklpp, $tklvv));

// jos kyseess‰ on ihan virallinen varastonarvonlaskenta, tehd‰‰n n‰in
if ($toiminto == "kaikkiok" and $tee == "varma" and $kauttalaskutus == '') {
	echo "<font class='message'>".t("Suoritetaan j‰lkilaskentaa %s tuotteelle", "", mysql_num_rows($presult)).":<br></font>";
}
elseif ($mobiili_keikka != "yes" and $kauttalaskutus == '') {
	echo "<font class='message'>".t("Vied‰‰n varastoon %s tuotetta", "", mysql_num_rows($presult)).":<br></font>";
}

if ($tee != "" and $mobiili_keikka != "yes" and $kauttalaskutus == '') {
	require_once ('inc/ProgressBar.class.php');

	$bar = new ProgressBar();

	$bar->initialize(mysql_num_rows($presult)); // print the empty bar
}

while ($tilrivirow = mysql_fetch_assoc($presult)) {

	$hankintakulut = "";

	if ($tee != "" and $mobiili_keikka != "yes" and $kauttalaskutus == '') {
		$bar->increase(); //calls the bar with every processed element
	}

	// lasketaan keskihankintahinta, tarvitaan tuotteen saldo ja tuotteen tiedot
	$query  = "SELECT sum(saldo) saldo from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tilrivirow[tuoteno]'";
	$salres = pupe_query($query);
	$salrow = mysql_fetch_assoc($salres);

	$query  = "	SELECT tuote.*,
				if(tuotteen_toimittajat.osto_alv >= 0, tuotteen_toimittajat.osto_alv, tuote.alv) alv,
				if(tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) tuotekerroin
				from tuote
				LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]' and tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno)
				where tuote.yhtio	= '$kukarow[yhtio]'
				and tuote.tuoteno	= '$tilrivirow[tuoteno]'";
	$tuores = pupe_query($query);
	$tuorow = mysql_fetch_assoc($tuores);

	if ((float) $tuorow['tuotekerroin'] <= 0) $tuorow['tuotekerroin'] = 1;

	// jos kysess‰ on kotimainen vaihto-omaisuuslasku, pit‰‰ lis‰t‰ tuotteen hintaan alvi
	if ($laskurow['vienti'] == 'C' or $laskurow['vienti'] == 'J') {
		if ($simualv > 0) {
			$alvit = 1 + $simualv / 100;
		}
		else {
			$alvit = 1 + $tuorow["alv"] / 100;
		}
	}
	else {
		$alvit = 1;
	}

	$ale_lisa_apu_kala = generoi_alekentta_php($tilrivirow, 'O', 'kerto');

	// lasketaan rivin hinta
	$osuus_apu_hinta = round($tilrivirow['hinta']*$alvit*abs($tilrivirow['varattu'])*$ale_lisa_apu_kala*$tuorow['tuotekerroin'], $yhtiorow["hintapyoristys"]);

	// jos keikan arvo on muuta kuin nolla ja rivin hinta on pienempi kuin keikan hinta, lasketaan rahtiosuus. muuten nollaa
	if ($laskurow['summa'] != 0 and $osuus_apu_hinta <= $laskurow["summa"] + 0.01) {
		// rivin hinta
		$osuus   = $osuus_apu_hinta / $laskurow['summa'];	// kuinka paljon t‰m‰ rivi on koko keikasta
		$rahtios = $osuus*$rahtikulut;						// lasketaan sama osuus rahtikuluista t‰lle riville
	}
	else {
		$rahtios = 0;
		$osuus   = 0;
	}

	if ($tilrivirow['varattu'] < 0) {
		$rahtios = $rahtios * -1;
	}

	// t‰m‰ on miinusta hyvitysrivill‰
	$ohinta = round($tilrivirow['hinta']*$ale_lisa_apu_kala*$tuorow['tuotekerroin']*$laskurow['vienti_kurssi']*$tilrivirow['varattu']+$rahtios, 6); // tuotteen rivihinta rahteineen OLETUSVALUUTASSA

	// Osuus kululaskuista # Osuus eturahdista
	$hankintakulut .= round($laskurow["saldo_maksettu"] * $osuus, 2)."#".round($laskurow['rahti_etu'] * $laskurow['vienti_kurssi'] * $osuus, 6);

	$tulliprossa = 0;
	$tv_nimrow	 = "";

	if ($tv_kaytossa and $tilrivirow["var"] == "" and $laskurow["maa"] != $yhtiorow["maa"]) {
		// lis‰t‰‰n tulli
		require ("tilauskasittely/taric_veroperusteet.inc");

		$aputullimaara = $ohinta * ($tulliprossa/100);
		$ohinta = $ohinta * (1 + ($tulliprossa/100));
		$tv_nimrow["maara"] = $tv_nimrow["maara"] * 1; // teh‰‰n numero, ett‰ lista n‰ytt‰‰ kivemmalta

	}

	// Tulli% # Aputullim‰‰r‰
	$hankintakulut .= "#".sprintf('%.02f',$tulliprossa)."#".sprintf('%.02f',$aputullimaara);

	// lis‰t‰‰n riville extra kulu, jos sellanen oli annettu
	if ($tilrivirow["kate"] != 0) {
		$kuluteksti = "($ohinta + $tilrivirow[kate])";
		$ohinta = $ohinta + $tilrivirow['kate'];
	}
	else {
		//$kuluteksti = "";
	}

	// Varastoonvientip‰iv‰
	if ($tilrivirow["toimitettuaika"] == "0000-00-00 00:00:00") {
		$varastoonvientipva = date("Y-m-d H:i:s");
	}
	else {
		$varastoonvientipva = $tilrivirow["toimitettuaika"];
	}

	// Tsekataan, ett‰ varastoonvientip‰iv‰ on avoimella kaudella
	//Avoimen kauden tiedot
	list($tkvpvv,$tkvpkk,$tkvppp) = explode("-", substr($varastoonvientipva,0,10));

	$varastoonvientipva_tsek  = (int) date('Ymd',mktime(0,0,0, $tkvpkk, $tkvppp, $tkvpvv));

	if (($tee == "varma" or $tee == "varastoon") and ($varastoonvientipva_tsek < $tilikausi_alku or $varastoonvientipva_tsek > $tilikausi_loppu)) {

		if ($tv_kaytossa) $vcspan = 10;
		else $vcspan = 8;

		$varastoon_msg .= "	<tr>";
		$varastoon_msg .= "<td></td>";
		$varastoon_msg .= "<td>".tv1dateconv($varastoonvientipva)."</td>";
		$varastoon_msg .= "<td>$tilrivirow[tuoteno]</td>";
		$varastoon_msg .= "<td colspan='$vcspan'>".t("VIRHE: Varastoonvientip‰iv‰ ei kuulu avoimeen kauteen. Rivi‰ ei viety varastoon.")."</td>";
		$varastoon_msg .= "</tr>";
		continue;
	}

	// Rivinlis‰kulu
	$hankintakulut .= "#$tilrivirow[kate]";

	$rivihin = round($ohinta, $yhtiorow['hintapyoristys']);	// tilausrivin rivihinta talteen

	// t‰m‰ muuttaa hinnan plussaksi hyvityskeikalla (jolloin kehahin matikka menee oikein)
	$ohinta  = round($ohinta / $tilrivirow['varattu'], 6); // yhden tuotteen hinta kaikkine kuluineen

	//Keskihankintahintaa yll‰pidet‰‰n vain jos tuotteella ei ole sarjanumeroseurantaa tai marginaaliverostusta
	if ($tuorow["sarjanumeroseuranta"] == "S" or $tuorow["sarjanumeroseuranta"] == "U") {
		//Laitetaan kehahintamuuttujaan t‰m‰n keikan ostohinta jotta tapahtumat n‰ytt‰isiv‰t fiksuja lukuja
		$kehahin = $ohinta;
	}
	else {
		if ($salrow['saldo'] + $tilrivirow['varattu'] != 0 and $salrow['saldo'] != 0 and $salrow['saldo'] * $tuorow['kehahin'] + $ohinta * $tilrivirow['varattu'] != 0) {
			// kehahin matikka, tuotteella pit‰‰ olla saldoa ennen ja j‰lkeen ett‰ edes tehd‰‰n matikkaa, sek‰ jakolaskun osoittaja pit‰‰ olla positiivinen
			$kehahin = round(($salrow['saldo'] * $tuorow['kehahin'] + $ohinta * $tilrivirow['varattu']) / ($salrow['saldo'] + $tilrivirow['varattu']), 6);

			if ($debug >= 1) {
				$varastoon_msg .= "<font class='message'>Tuote: $tuorow[tuoteno] Matikka: ($salrow[saldo] * $tuorow[kehahin] + $ohinta * $tilrivirow[varattu]) / ($salrow[saldo] + $tilrivirow[varattu]) = $kehahin</font><br>";
			}
		}
		else {
			// Jos j‰‰d‰‰n saldossa t‰m‰nkin tulon j‰lkeen miinukselle tai nollille, laitetaan kehahin suoraan t‰m‰n keikan hinnasta
			$kehahin = round($ohinta, 6);

			if ($debug >= 1) {
				$varastoon_msg .= "<font class='message'>Tuote: $tuorow[tuoteno] Saldo j‰i nollaan tai miinukselle. Kehahin = ostohinta = $kehahin</font><br>";
			}
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
		$rivitunnus = $tilrivirow["tunnus"]; 		// rivin tunnus, t‰ll‰ lˆydet‰‰n varmasti oikea tapahtuma

		$uusikehahin = jalkilaskentafunktio($tuoteno, $pvm, $uusihinta, $rivitunnus);
		$uusikehahin = sprintf('%.6f', $uusikehahin);

		if ($hankintakulut != '') {
			$query = "SELECT hankintakulut FROM tilausrivin_lisatiedot WHERE yhtio = '$kukarow[yhtio]' AND tilausrivitunnus = '$tilrivirow[tunnus]'";
			$resxxxx = pupe_query($query);

			if (mysql_num_rows($resxxxx) == 0) {
				$query = "INSERT INTO tilausrivin_lisatiedot SET hankintakulut = '$hankintakulut', yhtio = '$kukarow[yhtio]', tilausrivitunnus = '$tilrivirow[tunnus]'";
				$hankresxxx = pupe_query($query);
			}
			else {
				$query = "UPDATE tilausrivin_lisatiedot SET hankintakulut = '$hankintakulut' WHERE yhtio = '$kukarow[yhtio]' AND tilausrivitunnus = '$tilrivirow[tunnus]'";
				$hankresxxx = pupe_query($query);
			}
		}
	}
	elseif($toiminto == "kalkyyli" and $tee == "varastoon") {

		// p‰ivitet‰‰n tilausrivin rivihintaa
		$query  = "	UPDATE tilausrivi set
					rivihinta = '$rivihin'
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$tilrivirow[tunnus]'";
		$resultxxx = pupe_query($query);

		if ($tuorow["sarjanumeroseuranta"] == "S" or $tuorow["sarjanumeroseuranta"] == "U") {
			$kehalis = "";
		}
		else {
			$kehalis = " kehahin = '$kehahin', ";
		}

		// p‰ivitet‰‰n tuote
		$query = "	UPDATE tuote set
					$kehalis
					vihahin     = round('$ohinta','$yhtiorow[hintapyoristys]'),
					vihapvm     = '$varastoonvientipva'
					WHERE yhtio = '$kukarow[yhtio]' and tuoteno='$tilrivirow[tuoteno]'";
		$result = pupe_query($query);

		if ($tuorow["sarjanumeroseuranta"] == "E" or $tuorow["sarjanumeroseuranta"] == "F" or $tuorow["sarjanumeroseuranta"] == "G") {

			$query = "	SELECT era_kpl, tunnus
						FROM sarjanumeroseuranta
						WHERE yhtio 		 = '$kukarow[yhtio]'
						and tuoteno 		 = '$tilrivirow[tuoteno]'
						and ostorivitunnus 	 = '$tilrivirow[tunnus]'
						and myyntirivitunnus = 0";
			$sarjares = pupe_query($query);

			while($sarjarow = mysql_fetch_assoc($sarjares)) {
				if ($sarjarow["era_kpl"] < 0) {
					$query = "	UPDATE sarjanumeroseuranta
								SET era_kpl = 0
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus 	= $sarjarow[tunnus]";
					$lisa_res = pupe_query($query);
				}
			}
		}

		// muuten t‰ss‰ ollaan sitten viem‰ss‰ kamaa ihan oikeesti saldoille.. tehd‰‰n siihen liittyv‰t hommat.
		$uusikehahin = "";

		if ($tilrivirow["tunnus"] != $tilrivirow["perheid2"] and $tilrivirow["perheid2"] > 0) {
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
					hyllyalue 	= '$tilrivirow[hyllyalue]',
					hyllynro 	= '$tilrivirow[hyllynro]',
					hyllytaso 	= '$tilrivirow[hyllytaso]',
					hyllyvali 	= '$tilrivirow[hyllyvali]',
					selite     = '$laskurow[nimi] $laskurow[nimitark], ".t("Tilaus").": $tilrivirow[otunnus], ".t("Ostohinta").": $ohinta $lisavartlisa',
					laatija    = '$kukarow[kuka]',
					rivitunnus = '$tilrivirow[tunnus]',
					laadittu   = '$varastoonvientipva'";
		$result = pupe_query($query);
		$tapahtumaid = mysql_insert_id();

		// Vied‰‰n koko suuntalava varastoon. Eli kaikille suuntalavalla oleville tuotteille p‰ivitet‰‰n sama tuotepaikka
		if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus) and trim($koko_suuntalava) == 'X') {

			$query = "	SELECT *
						FROM tuotepaikat
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tuoteno = '{$tilrivirow['tuoteno']}'
						AND hyllyalue = '{$tilrivirow['hyllyalue']}'
						AND hyllynro = '{$tilrivirow['hyllynro']}'
						AND hyllytaso = '{$tilrivirow['hyllytaso']}'
						AND hyllyvali = '{$tilrivirow['hyllyvali']}'
						LIMIT 1";
			$paikka_chk_res = pupe_query($query);

			if (mysql_num_rows($paikka_chk_res) == 0) {
				// lis‰t‰‰n paikka
				$query = "	INSERT INTO tuotepaikat SET
	 						yhtio			= '{$kukarow['yhtio']}',
				 			tuoteno     	= '{$tilrivirow['tuoteno']}',
				 			oletus      	= '',
		   		 			saldo       	= '{$tilrivirow['varattu']}',
							saldo_varattu 	= if({$tilrivirow['tunnus']} != {$tilrivirow['perheid2']} and {$tilrivirow['perheid2']} > 0, {$tilrivirow['varattu']}, 0),
		   		 			saldoaika   	= now(),
							hyllyalue   	= '{$tilrivirow['hyllyalue']}',
							hyllynro    	= '{$tilrivirow['hyllynro']}',
							hyllytaso   	= '{$tilrivirow['hyllytaso']}',
							hyllyvali   	= '{$tilrivirow['hyllyvali']}',
							tyyppi 			= 'S',
							laatija			= '{$kukarow['kuka']}',
							luontiaika		= now()";
				$result = pupe_query($query);

				// tehd‰‰n tapahtuma
				$query = "	INSERT into tapahtuma set
							yhtio 		= '$kukarow[yhtio]',
							tuoteno 	= '$tilrivirow[tuoteno]',
							kpl 		= '0',
							kplhinta	= '0',
							hinta 		= '0',
							laji 		= 'uusipaikka',
							hyllyalue  	= '{$tilrivirow['hyllyalue']}',
							hyllynro   	= '{$tilrivirow['hyllynro']}',
							hyllytaso  	= '{$tilrivirow['hyllytaso']}',
							hyllyvali  	= '{$tilrivirow['hyllyvali']}',
							selite 		= '".t("Lis‰ttiin tuotepaikka")." {$tilrivirow['hyllyalue']} {$tilrivirow['hyllynro']} {$tilrivirow['hyllyvali']} {$tilrivirow['hyllytaso']}',
							laatija 	= '$kukarow[kuka]',
							laadittu 	= date_sub('$varastoonvientipva', interval 1 SECOND)";
				$result = pupe_query($query);
			}
			else {
				// p‰ivitet‰‰n saldo tuoterivill‰ olevalle paikalle
				$query = "	UPDATE tuotepaikat SET
							saldo       	= saldo+{$tilrivirow['varattu']},
							saldo_varattu 	= saldo_varattu + if({$tilrivirow['tunnus']} != {$tilrivirow['perheid2']} and {$tilrivirow['perheid2']} > 0, {$tilrivirow['varattu']}, 0),
							saldoaika   	= now(),
							tyyppi 			= 'S'
							WHERE yhtio 	= '{$kukarow['yhtio']}'
							AND tuoteno     = '{$tilrivirow['tuoteno']}'
							AND hyllyalue   = '{$tilrivirow['hyllyalue']}'
							AND hyllynro    = '{$tilrivirow['hyllynro']}'
							AND hyllytaso   = '{$tilrivirow['hyllytaso']}'
							AND hyllyvali   = '{$tilrivirow['hyllyvali']}'
							LIMIT 1";
				$result = pupe_query($query);
			}
		}
		else {
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
			$result = pupe_query($query);
		}

		$jtvarastot[$tilrivirow["tunnus"]] = array($tilrivirow["hyllyalue"], $tilrivirow["hyllynro"]);
		$millepakallemeni = "";

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
			$result = pupe_query($query);

			$query = "	SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso
						FROM tuotepaikat
						WHERE yhtio 	= '$kukarow[yhtio]' and
	   		 			tuoteno     	= '$tilrivirow[tuoteno]' and
	   		 			oletus     	   != ''
	   		 			LIMIT 1";
			$olpares = pupe_query($query);
			$olparow = mysql_fetch_assoc($olpares);

			$jtvarastot[$tilrivirow["tunnus"]] = array($olparow["hyllyalue"], $olparow["hyllynro"]);
			$millepakallemeni = " hyllyalue = '$olparow[hyllyalue]', hyllynro = '$olparow[hyllynro]', hyllyvali = '$olparow[hyllyvali]', hyllytaso = '$olparow[hyllytaso]' ";

	   		if (mysql_affected_rows() == 0) {

	   			$varastoon_msg .= "<br><font class='error'>".t("Tuotteella")." $tilrivirow[tuoteno] ".t("ei ole oletuspaikkaa")."!!!</font><br><br>";

				// haetaan firman eka varasto, tˆk‰t‰‰n kama sinne ja tehd‰‰n siit‰ oletuspaikka
				$query = "	SELECT alkuhyllyalue, alkuhyllynro
							FROM varastopaikat
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = ''
							ORDER BY alkuhyllyalue, alkuhyllynro
							LIMIT 1";
				$result = pupe_query($query);

				if (mysql_num_rows($result) != 1) {
					// haetaan firman eka varasto, tˆk‰t‰‰n kama sinne ja tehd‰‰n siit‰ oletuspaikka
					$query = "	SELECT alkuhyllyalue, alkuhyllynro
								FROM varastopaikat
								WHERE yhtio = '$kukarow[yhtio]'
								ORDER BY alkuhyllyalue, alkuhyllynro
								LIMIT 1";
					$result = pupe_query($query);
				}

				if (mysql_num_rows($result) == 1) {
					$hyllyrow =  mysql_fetch_assoc($result);
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
				$result = pupe_query($query);

				$jtvarastot[$tilrivirow["tunnus"]] = array($hyllyrow["alkuhyllyalue"], $hyllyrow["alkuhyllynro"]);
				$millepakallemeni = "hyllyalue = '$hyllyrow[alkuhyllyalue]', hyllynro = '$hyllyrow[alkuhyllynro]', hyllyvali = '0', hyllytaso = '0' ";

				// tehd‰‰n tapahtuma
				$query = "	INSERT into tapahtuma set
							yhtio 		= '$kukarow[yhtio]',
							tuoteno 	= '$tilrivirow[tuoteno]',
							kpl 		= '0',
							kplhinta	= '0',
							hinta 		= '0',
							laji 		= 'uusipaikka',
							hyllyalue   = '$hyllyrow[alkuhyllyalue]',
							hyllynro    = '$hyllyrow[alkuhyllynro]',
							hyllytaso   = '0',
							hyllyvali   = '0',
							selite 		= '".t("Lis‰ttiin tuotepaikka")." $hyllyrow[alkuhyllyalue] $hyllyrow[alkuhyllynro] 0 0',
							laatija 	= '$kukarow[kuka]',
							laadittu 	= date_sub('$varastoonvientipva', interval 1 SECOND)";
				$result = pupe_query($query);
	   		}
			else {
				$varastoon_msg .= " <font class='error'>".t("Huh, se onnistui!")."</font><br>";
			}
		}

		// Saldo ei mennytk‰‰n ostorivin osoittamalle paikalle, p‰ivitet‰‰n riville ja tapahtumalle kuitenkin paikat jolle saldo loppupeleiss‰ meni
		if ($millepakallemeni != "") {
			$query  = "	UPDATE tapahtuma set
						$millepakallemeni
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  = '$tapahtumaid'";
			$varastoon_result = pupe_query($query);

			$millepakallemeni = " ,".$millepakallemeni;
		}

		// p‰ivitet‰‰n tilaus saapuneeksi varastoon
		// p‰ivitet‰‰n myˆs tehdaslis‰varusteet saapuneiksi
		// p‰ivitet‰‰n riville paikat jolle saldo loppupeleiss‰ meni (jos meni ok niin $millepakallemeni on tyhj‰)
		$query  = "	UPDATE tilausrivi set
					kpl = varattu,
					varattu = 0,
					laskutettuaika = '$varastoonvientipva',
					laskutettu = '$kukarow[kuka]'
					{$millepakallemeni}
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilrivirow[otunnus]'
					and varattu != 0
					and (tunnus = '$tilrivirow[tunnus]' or perheid = '$tilrivirow[tunnus]')";
		$varastoon_result = pupe_query($query);

		$rivia++;

		//	Kohdistetaan vain oikeille asiakkaille n‰iss‰ caseissa
		if ($yhtiorow["automaattinen_jt_toimitus"] != "") {
			//Tutkitaan lˆytyykˆ JT-rivi joka m‰pp‰ytyy t‰h‰n ostoriviin
		   	$query = "	SELECT ifnull(if((tilausrivi.perheid != 0 and tilausrivi.tunnus != tilausrivi.perheid), tilausrivi.perheid, tilausrivi.tunnus), 0) jtrivi
		   				FROM tilausrivin_lisatiedot
		   				JOIN tilausrivi ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
		   				WHERE tilausrivin_lisatiedot.yhtio 			 = '$kukarow[yhtio]'
		   				AND tilausrivin_lisatiedot.tilausrivilinkki  = '$tilrivirow[tunnus]'
						$suoralisa";
			$varastoon_result = pupe_query($query);

			while ($varastoon_row = mysql_fetch_assoc($varastoon_result)) {
				// Mitk‰ suoratoimitukset tuloutettiin t‰ll‰ keikalla
				$jtrivit[$varastoon_row["jtrivi"]] = $varastoon_row["jtrivi"];
				// Katotaan mille paikalle n‰‰ meni, jotta suoratoimitus voidaan laukasta t‰lt‰ paikalta
				$jtrivit_paikat[$varastoon_row["jtrivi"]] = $tapahtumaid;
			}
		}
	}

	// n‰ytet‰‰n ruudulla
	$varastoon_msg .= "	<tr>";

	if ($tilrivirow["toimitettuaika"] == "0000-00-00 00:00:00") {
		$vpppa = $ovpppa;
		$vpkka = $ovpkka;
		$vpvva = $ovpvva;
	}
	else {
		list($vpvva, $vpkka, $vpppa) = explode("-", substr($tilrivirow["toimitettuaika"], 0, 10));
	}

	if ($toiminto == "kalkyyli" and $tee == "") {
		$chk = "";
		if ($tilrivirow["netto"] == '') {
			$chk = "CHECKED";
		}

		$varastoon_msg .= "<td align='center' valign='top'><input type='checkbox' name='varastoonko[]' value='$tilrivirow[tunnus]' $chk>";

		if ($tilrivirow['netto'] == 'X') {
			$varastoon_msg .= "<br /><img src='../pics/lullacons/alert.png' alt='".t("Huomio")."' title='".t("HUOM: Rivi‰ ei vied‰ automaattisesti varastoon.")."' />";
		}

		$varastoon_msg .= "</td>";

		if ($yhtiorow["varastoonvientipaiva"] == "K") {
			$varastoon_msg .= "<td valign='top' nowrap>
								<input type='text' name='ppa[$tilrivirow[tunnus]]' value='$vpppa' size='3'>
								<input type='text' name='kka[$tilrivirow[tunnus]]' value='$vpkka' size='3'>
								<input type='text' name='vva[$tilrivirow[tunnus]]' value='$vpvva' size='5'></td>";
		}
		else {
			$varastoon_msg .= "<td valign='top' nowrap>".tv1dateconv("$vpvva-$vpkka-$vpppa")."</td>";
		}

		if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) {

			$query = "SELECT laskunro FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilrivirow['uusiotunnus']}'";
			$saapuminen_chk_res = pupe_query($query);
			$saapuminen_chk_row = mysql_fetch_assoc($saapuminen_chk_res);

			$varastoon_msg .= "<td>{$saapuminen_chk_row['laskunro']}</td>";

			$query = "	SELECT suuntalavat.sscc
						FROM suuntalavat
						JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$tilrivirow['uusiotunnus']}')
						WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
						AND suuntalavat.tunnus  = '{$suuntalavan_tunnus}'";
			$sscc_res = pupe_query($query);
			$sscc_row = mysql_fetch_assoc($sscc_res);

			// n‰ytet‰‰n listauksessa SSCC-koodi ja otetaan mukaan suuntalavan tunnus
			$varastoon_msg .= "<td>{$sscc_row['sscc']}</td>";
		}
	}
	else {
		if ($tilrivirow["netto"] == '') {
			$varastoon_msg .= "<td align='center' valign='top'>x</td>";
		}
		else {
			$varastoon_msg .= "<td></td>";
		}

		$varastoon_msg .= "<td valign='top' nowrap>".tv1dateconv("$vpvva-$vpkka-$vpppa")."</td>";

		if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) {

			$query = "SELECT laskunro FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$tilrivirow['uusiotunnus']}'";
			$saapuminen_chk_res = pupe_query($query);
			$saapuminen_chk_row = mysql_fetch_assoc($saapuminen_chk_res);

			$varastoon_msg .= "<td>{$saapuminen_chk_row['laskunro']}</td>";

			$query = "	SELECT suuntalavat.sscc
						FROM suuntalavat
						JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = '{$tilrivirow['uusiotunnus']}')
						WHERE suuntalavat.yhtio = '{$kukarow['yhtio']}'
						AND suuntalavat.tunnus  = '{$suuntalavan_tunnus}'";
			$sscc_res = pupe_query($query);
			$sscc_row = mysql_fetch_assoc($sscc_res);

			// n‰ytet‰‰n listauksessa SSCC-koodi ja otetaan mukaan suuntalavan tunnus
			$varastoon_msg .= "<td>{$sscc_row['sscc']}</td>";
		}
	}

	$varastoon_msg .= "	<td valign='top'>$tilrivirow[tuoteno]</td>
						<td align='right' valign='top'>$tilrivirow[varattu]<br>".sprintf('%.2f', $tilrivirow['varattu']*$tuorow['tuotekerroin'])."</td>";

	for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
		$varastoon_msg .= "<td align='right' valign='top'>".$tilrivirow["ale{$alepostfix}"]."%</td>";
	}

	$varastoon_msg .= "<td align='right' valign='top'>".sprintf('%.'.$yhtion_parametritrow['hintapyoristys'].'f', $tilrivirow['hinta'])."<br>".sprintf('%.'.$yhtion_parametritrow['hintapyoristys'].'f', $tilrivirow['hinta']*$tuorow['tuotekerroin'])."</td>
						<td align='right' valign='top'>".sprintf('%.6f', $osuus)."</td>
						<td align='right' valign='top'>".sprintf('%.6f', $osuus*$rahtikulut)."</td>";

	if ($tv_kaytossa) $varastoon_msg .= "<td align='right' valign='top'>$tv_nimrow[maara] $tv_nimrow[rahayksikko]</td>";

	$varastoon_msg .= "<td align='right' valign='top'>".sprintf('%.'.$yhtion_parametritrow['hintapyoristys'].'f', $ohinta)."</td>
						<td align='right' valign='top'>".sprintf('%.'.$yhtion_parametritrow['hintapyoristys'].'f', $ohinta*$tilrivirow['varattu']) . " $kuluteksti</td>";

	if ($tuorow["sarjanumeroseuranta"] == "S" or $tuorow["sarjanumeroseuranta"] == "U") {
		$varastoon_msg .= "	<td align='right'></td>";
	}
	else {
		if ($uusikehahin == "") {

			$varastoon_fontti = "";
			if ($kehahin <= 0) {
				$varastoon_fontti = "error";
			}

			$varastoon_msg .= "	<td align='right'>$tuorow[kehahin]<br><font class='$varastoon_fontti'>~$kehahin</font></td>";
		}
		else {
			$varastoon_msg .= "	<td align='right'>$tuorow[kehahin]<br>$uusikehahin</td>";
		}
	}

	if ($toiminto == "kalkyyli" and $tee == "") {

		list($varastoon_saldo, $dummy1, $dummy2, $dummy3) = saldo_myytavissa($tuorow["tuoteno"]);
		$varastoon_saldo_uusi = $varastoon_saldo + $tilrivirow["varattu"];

		$varastoon_fontti = "";
		$varastoon_saldo_viesti = "";

		if ($varastoon_saldo_uusi <= 0) {
			$varastoon_fontti = "error";
			$varastoon_saldo_viesti = "<br>".t("HUOM: Saldo j‰‰ negatiiviseksi!");
		}

		$varastoon_msg .= "<td align='right'>$varastoon_saldo<br><font class='$varastoon_fontti'>$varastoon_saldo_uusi$varastoon_saldo_viesti</font></td>";
	}

	if ($tv_kaytossa) {
		if ($tee == "") {
			$chk = "";
			if ($tilrivirow["var"] != '') {
				$chk = "CHECKED";
			}

			$varastoon_msg .= "<td align='center' valign='top'><input type='checkbox' name='eitullia[]' value='$tilrivirow[tunnus]' $chk></td>";
		}
		else {
			if ($tilrivirow["var"] != '') {
				$varastoon_msg .= "<td valign='top'>".t("ei tullia")."</td>";
			}
			else {
				$varastoon_msg .= "<td></td>";
			}
		}
	}

	$varastoon_msg .= "</tr>";

	if ($mobiili_keikka == "yes") {
		echo "<font class='message'>".t("Vietiin tuotetta")." $tilrivirow[tuoteno] ".t("varastoon").": $tilrivirow[varattu] ".t("kappaletta").".</font><br>";
	}
}

if ($mobiili_keikka != "yes" and $kauttalaskutus == '') {
	echo "<br><br>";
	echo $varastoon_msg;
	echo $jalkilaskenta_debug_text;
}

// jos kyseess‰ on ihan virallinen varastonarvonlaskenta, tehd‰‰n n‰in
if ($toiminto == "kaikkiok" and $tee == "varma") {

	$rivia = mysql_num_rows($presult); // kaunistelua vaan loppua varten... t‰‰ tehd‰‰n elsess‰ affected rowssilla..

	// laitetaan kohdistettu X kun lasku on viety varastoon...
	// laitetaan MAPVM kentt‰‰n t‰m‰ p‰iv‰ niin tiedet‰‰n koska viety varastoon
	// MAPVM p‰vittyy jos keikka loppulasketaan uudestaan ja se nolllataan jos/kun keikka avataan (avaa_keikka.php)
	$query = "	UPDATE lasku set
				kohdistettu = 'X',
				alatila = 'X',
				mapvm = now(),
				vienti_kurssi = '$laskurow[vienti_kurssi]',
				maksu_kurssi = '$laskurow[maksu_kurssi]'
				WHERE tunnus = '$laskurow[tunnus]'
				and yhtio = '$kukarow[yhtio]'";
	$result = pupe_query($query);

	// haetaan keikan vaihto-omaisuuslaskut (kotimaa, eu tai ei-eu)
	$query = "	SELECT vanhatunnus
				FROM lasku
				WHERE yhtio		= '$kukarow[yhtio]'
				and tila		= 'K'
				and laskunro	= '$laskurow[laskunro]'
				and vanhatunnus <> 0
				and vienti in ('C','F','I','J','K','L')";
	$result = pupe_query($query);

	// jos on, haetaan liitetyn laskun tiedot
	if (mysql_num_rows($result) >= 1) {

		while ($llrow = mysql_fetch_assoc($result)) {

			//T‰m‰ on ostoreskontran lasku
			$query   = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$llrow[vanhatunnus]'";
			$llres   = pupe_query($query);
			$kulurow = mysql_fetch_assoc($llres);

			// tehd‰‰n viel‰ kirjapitoa...
			// p‰ivitet‰‰n tilaus saapuneeksi varastoon kirjanpitoon
			$query = "	SELECT tunnus, summa, kustp, kohde, projekti
						FROM tiliointi
						WHERE yhtio  = '$kukarow[yhtio]'
						and ltunnus  = '$kulurow[tunnus]'
						and tilino   = '$yhtiorow[matkalla_olevat]'
						and korjattu = ''";
			$result1 = pupe_query($query);

			if (mysql_num_rows($result1) > 0) { // Tiliointi lˆytyi
				while ($tiliointirow = mysql_fetch_assoc($result1)) {

					// Tarkenteet kopsataan alkuper‰iselt‰ tiliˆinnilt‰, mutta jos alkuper‰inen tiliˆinti on ilman tarkenteita, niin menn‰‰n tilin defaulteilla
					list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["matkalla_olevat"], $tiliointirow["kustp"], $tiliointirow["kohde"], $tiliointirow["projekti"]);

					// Kumotaan, matkallaolevat
					$query = "	INSERT into tiliointi set
								yhtio		= '$kukarow[yhtio]',
								ltunnus		= '$kulurow[tunnus]',
								tilino		= '$yhtiorow[matkalla_olevat]',
								kustp    	= '{$kustp_ins}',
								kohde	 	= '{$kohde_ins}',
								projekti 	= '{$projekti_ins}',
								tapvm		= now(),
								summa		= $tiliointirow[summa] * -1,
								selite		= '".t("Lasku kirjattiin varastoon")." $kulurow[nimi]',
								lukko		= '',
								laatija		= '$kukarow[kuka]',
								laadittu	= now()";
					$result2 = pupe_query($query);

					$varastotili = $yhtiorow["varasto"];

					if ($kulurow["vienti"] == 'J' or $kulurow["vienti"] == 'K' or $kulurow["vienti"] == 'L') {
						$varastotili = $yhtiorow["raaka_ainevarasto"];
					}

					// Tarkenteet kopsataan alkuper‰iselt‰ tiliˆinnilt‰, mutta jos alkuper‰inen tiliˆinti on ilman tarkenteita, niin menn‰‰n tilin defaulteilla
					list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastotili, $tiliointirow["kustp"], $tiliointirow["kohde"], $tiliointirow["projekti"]);

					// Kirjataan varastonarvo
					$query = "	INSERT into tiliointi set
								yhtio		= '$kukarow[yhtio]',
								ltunnus		= '$kulurow[tunnus]',
								tilino		= '$varastotili',
								kustp    	= '{$kustp_ins}',
								kohde	 	= '{$kohde_ins}',
								projekti 	= '{$projekti_ins}',
								tapvm		= now(),
								summa		= '$tiliointirow[summa]',
								selite		= '".t("Lasku kirjattiin varastoon")." $kulurow[nimi]',
								lukko		= '',
								laatija 	= '$kukarow[kuka]',
								laadittu	= now()";
					$result3 = pupe_query($query);
				}
			}
			else {
				echo "<font class='error'>".t("En lˆyt‰nyt matkallaolevat kirjausta!")." $kulurow[tunnus] $yhtiorow[matkalla_olevat]</font><br>";
			}
		}
	}
	else {
		echo "<font class='error'>".t("HUOM: Saapumiseen ei ole liitetty yht‰‰n vaihto-omaisuuslaskua!")."<br><br></font>";
	}
}

if ($toiminto == "kalkyyli" and $tee == "") {
	if ($tv_kaytossa) $cspani = 11;
	else $cspani = 9;

	for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
		$cspani++;
	}

	if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) $cspani += 2;

	echo "<tr><td align='center' class='tumma'><input type='checkbox' name='var' onclick='toggleAll(this);'></td>";

	if ($yhtiorow["varastoonvientipaiva"] == "K") {
		echo "<td valign='top' class='tumma' nowrap>
							<input type='text' name='ppa_kaikki' id='ppa_kaikki' value='' onKeyUp='WriteText(\"ppa\");' size='3'>
							<input type='text' name='kka_kaikki' id='kka_kaikki' value='' onKeyUp='WriteText(\"kka\");' size='3'>
							<input type='text' name='vva_kaikki' id='vva_kaikki' value='' onKeyUp='WriteText(\"vva\");' size='5'></td>";
	}

	echo "<td colspan='$cspani' class='tumma'>&larr; ".t("Ruksaa kaikki");

	if ($yhtiorow["varastoonvientipaiva"] == "K") {
		echo " / ".t("Muuta kaikki p‰iv‰m‰‰r‰t");

		echo ". ".t("HUOM").":<img src='../pics/lullacons/alert.png' alt='",t("Huomio"),"' title='",t("HUOM: Rivi‰ ei vied‰ automaattisesti varastoon."),"' /> = ",t("Rivi‰ ei vied‰ automaattisesti varastoon");
		echo "<br />",t("HUOM: Jos muutit varastoonviet‰vi‰ m‰‰ri‰, muista p‰ivitt‰‰ rivit ennen varastoonvienti‰");
	}
	else {
		echo ". ".t("HUOM").":<img src='../pics/lullacons/alert.png' alt='",t("Huomio"),"' title='",t("HUOM: Rivi‰ ei vied‰ automaattisesti varastoon."),"' /> = ",t("Rivi‰ ei vied‰ automaattisesti varastoon");
		echo "<br />",t("HUOM: Jos muutit varastoonviet‰vi‰ m‰‰ri‰, muista p‰ivitt‰‰ rivit ennen varastoonvienti‰");
		echo "</td><td valign='top' class='tumma' nowrap>";
	}

	echo "</td></tr>";
}

if ($mobiili_keikka != "yes" and $kauttalaskutus == '') {
	echo "<tr>";
}

if ($tee == "") {
	echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavan_tunnus}' />";

	if ($yhtiorow['suuntalavat'] == 'S' and $tee == '' and trim($suuntalavan_tunnus) != '' and trim($koko_suuntalava) == 'X') {

		if (trim($suuntalavan_hyllypaikka) != '') {
			echo "<input type='hidden' name='suuntalavan_hyllypaikka' value='{$suuntalavan_hyllypaikka}' />";
		}
		else {
			echo "<input type='hidden' name='suuntalavan_hyllyalue' value='{$suuntalavan_hyllyalue}' />";
			echo "<input type='hidden' name='suuntalavan_hyllynro'  value='{$suuntalavan_hyllynro}' />";
			echo "<input type='hidden' name='suuntalavan_hyllyvali' value='{$suuntalavan_hyllyvali}' />";
			echo "<input type='hidden' name='suuntalavan_hyllytaso' value='{$suuntalavan_hyllytaso}' />";
		}
		echo "<input type='hidden' name='koko_suuntalava' value='X' />";
		echo "<input type='hidden' name='vietiinko_koko_suuntalava' value='{$vietiinko_koko_suuntalava}' />";
	}

	echo "<td class='back' colspan='2'><input type='submit' value='".t("P‰ivit‰")."'></td>";
}
elseif ($mobiili_keikka != "yes" and $kauttalaskutus == '') {
	echo "<td class='back' colspan='2'></td>";
}

if ($mobiili_keikka != "yes" and $kauttalaskutus == '') {
	if ($tv_kaytossa) $cspani = 7;
	else $cspani = 6;

	for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
		$cspani++;
	}

	if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) $cspani += 2;

	echo "<td colspan='$cspani' class='back' align='right'>".t("Yhteens‰").":</th><td align='right'>".sprintf("%.2f", $summa)."</td>";
}

if ($tv_kaytossa and $tee == "") {
	echo "<th><input type='submit' value='".t("P‰ivit‰")."'></th>";
}
elseif ($tv_kaytossa and $kauttalaskutus == '') {
	echo "<th></th>";
}

if ($mobiili_keikka != "yes" and $kauttalaskutus == '') {
	echo "</tr>";
	echo "</table></form><br>";
}

// Jos varastoonvienti vaikuttaa tehtyihin valmistuksiin, niin j‰lkilasketaan myˆs ne
if ($toiminto == "kaikkiok" and $tee == "varma" and count($korjattavat_valmistukset) > 0) {
	$rekurkoko = count($korjattavat_valmistukset);

	echo "<table>";

	echo "<tr>";
	echo "<th colspan='6'>".t("Korjataan valmistukset joihin t‰m‰ varastoonvienti vaikuttaa")."</td>";
	echo "</tr>";


	// T‰ss‰ on rekursiivista toimintaa $korjattavat_valmistukset-array voi kasvaa matkan varrella
	for ($korjattavat_valmistukset_ind=0; $korjattavat_valmistukset_ind < $rekurkoko; $korjattavat_valmistukset_ind++) {
		jalkilaske_valmistus($korjattavat_valmistukset[$korjattavat_valmistukset_ind]);

		$rekurkoko = count($korjattavat_valmistukset);
	}

	echo "</table>";
}

if ($toiminto == "kaikkiok" and $tee == "varma" and $kauttalaskutus == '') {
	echo "<br><font class='message'>".t("Vietiin saapuminen")." $laskurow[laskunro] ".t("varastoon")."! ".t("Virallinen varastonarvo laskettu").": $rivia ".t("rivi‰").".</font><br><br>";
}
elseif ($toiminto == "kalkyyli" and $tee == "varastoon" and $kauttalaskutus == '') {
	if ($mobiili_keikka != "yes") {
		echo "<font class='message'>".t("Vietiin saapuminen")." $laskurow[laskunro] ".t("varastoon").": $rivia ".t("rivi‰").".</font><br><br>";
	}

	if ($yhtiorow['suuntalavat'] == 'S' and is_numeric($suuntalavan_tunnus)) {

		$query = "	SELECT GROUP_CONCAT(saapuminen) keikkatunnus
					FROM suuntalavat_saapuminen
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND suuntalava = '{$suuntalavan_tunnus}'";
		$uusiotunnus_chk_res = pupe_query($query);
		$uusiotunnus_chk_row = mysql_fetch_assoc($uusiotunnus_chk_res);

		# Jos vied‰‰n yksi rivi kerrallaan, ei suuntalavaa merkata puretuksi
		# Jos kyseess‰ on viimeinen tuote, merkataan puretuksi.
		if ($vie_vain_tama_tunnus_varastoon == 0) {

			// p‰ivitet‰‰n suuntalava puretuksi
			$query = "	UPDATE tilausrivi
						JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus = tilausrivi.suuntalava)
						JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = tilausrivi.uusiotunnus)
			 			SET suuntalavat.tila = 'P'
						WHERE tilausrivi.yhtio 		= '{$kukarow['yhtio']}'
						AND tilausrivi.uusiotunnus IN ({$uusiotunnus_chk_row['keikkatunnus']})
						AND tilausrivi.netto 		= ''
						AND tilausrivi.kpl 		   != 0
						AND tilausrivi.tyyppi 		= 'O'
						AND tilausrivi.suuntalava 	= '{$suuntalavan_tunnus}'";
			$netto_suuntalava_result = pupe_query($query);
		}

		// AINA EI SIIS OSUTA TƒHƒN, JOS JƒMƒRIVEJƒ EI OLE
		// JOS JƒMƒRIVEJƒ (= SPLITTAANTUNEET) Jƒƒ SUUNTALAVALLE
		// JƒMƒRIVEILTƒ tyhjennet‰‰n netto-kentt‰, jotta tuotteet menev‰t seuraavalla kerralla varastoon.
		$query = "	UPDATE tilausrivi
					JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus = tilausrivi.suuntalava)
					JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = tilausrivi.uusiotunnus)
		 			SET tilausrivi.netto = '',
					suuntalavat.tila 	 = 'S'
					WHERE tilausrivi.yhtio 		= '{$kukarow['yhtio']}'
					AND tilausrivi.uusiotunnus IN ({$uusiotunnus_chk_row['keikkatunnus']})
					AND tilausrivi.netto 		= 'X'
					AND tilausrivi.kpl 			= 0
					AND tilausrivi.varattu 	   != 0
					AND tilausrivi.tyyppi	 	= 'O'
					AND tilausrivi.suuntalava 	= '{$suuntalavan_tunnus}'";
		$netto_suuntalava_result = pupe_query($query);

		// Jos suuntalava on purettu ja rivi virty normaalisti hyllyyn niin nollataan linkki suuntalavalle
		// jos ollaan viety koko lava purkamatta varastoon niin silloin s‰ilytet‰‰n tilausrivin linkki suuntalavalle
		if ($yhtiorow['suuntalavat'] == 'S' and (!isset($koko_suuntalava) or trim($koko_suuntalava) != 'X')) {

			// p‰ivitet‰‰n suuntalava puretuksi
			$query = "	UPDATE tilausrivi
						JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus = tilausrivi.suuntalava)
						JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = tilausrivi.uusiotunnus)
			 			SET tilausrivi.suuntalava = 0
						WHERE tilausrivi.yhtio 		= '{$kukarow['yhtio']}'
						AND tilausrivi.uusiotunnus IN ({$uusiotunnus_chk_row['keikkatunnus']})
						AND tilausrivi.netto 		= ''
						AND tilausrivi.kpl 		   != 0
						AND tilausrivi.tyyppi 		= 'O'
						AND tilausrivi.suuntalava 	= '{$suuntalavan_tunnus}'";
			$netto_suuntalava_result = pupe_query($query);
		}
	}

	//	Halutaanko toimittaa j‰lkk‰reit‰ automaattisesti ja saako k‰ytt‰j‰ ylip‰‰t‰‰n k‰sitell‰ j‰lkk‰reit‰..
	$query = "	SELECT paivitys
				FROM oikeu
				WHERE yhtio	= '$kukarow[yhtio]'
				and kuka	= '$kukarow[kuka]'
				and nimi	= 'tilauskasittely/jtselaus.php'
				and alanimi = ''";
	$jtoikeudetres = pupe_query($query);

	if ((mysql_num_rows($jtoikeudetres) > 0 and $yhtiorow["automaattinen_jt_toimitus"] != "") or $yhtiorow["automaattinen_jt_toimitus"] == 'J')  {

		$jtoikeudetrow = mysql_fetch_assoc($jtoikeudetres);
		$mista = 'varastoon.inc';
		$varastosta = array();

		foreach ($jtvarastot as $varasto) {
			$v = kuuluukovarastoon($varasto[0], $varasto[1]);

			if ($v > 0 and !in_array($v, $varastosta)) {
				$varastosta[$v] = $v;
			}
		}

		if (in_array($yhtiorow["automaattinen_jt_toimitus"], array('K','T','J','S','V')) and count($jtrivit) > 0) {
			jt_toimita($laskurow["ytunnus"], $laskurow["liitostunnus"], $varastosta, $jtrivit, $jtrivit_paikat, "tosi_automaaginen", "JATKA");
		}

		if (in_array($yhtiorow["automaattinen_jt_toimitus"], array('K','T','J'))) {
			jt_toimita($laskurow["ytunnus"], $laskurow["liitostunnus"], $varastosta, "", "", "tosi_automaaginen", "JATKA");
		}

		//	Laitetaan tavarat liikkeelle jos sallitaan!
		if (($jtoikeudetrow["paivitys"] == 1 and in_array($yhtiorow["automaattinen_jt_toimitus"], array('T','J','S','V'))) or $yhtiorow["automaattinen_jt_toimitus"] == 'J') {
			jt_toimita("", "", "", "", "", "dummy", "TOIMITA");
		}
	}
}

if ($tee != "" and $mobiili_keikka != "yes" and $kauttalaskutus == '') {
	echo "<br><hr>";
	echo "<form method='post'>";
	echo "<input type='hidden' name='toiminto' value=''>";
	echo "<input type='hidden' name='toimittajaid' value='$laskurow[liitostunnus]'>";
	echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
	echo "<input type='submit' value='".t("Takaisin saapumisen valintaan")."'>";
	echo "</form>&nbsp;";
	echo "<form method='post'>";
	echo "<input type='hidden' name='toiminto' value=''>";
	echo "<input type='hidden' name='toimittajaid' value=''>";
	echo "<input type='hidden' name='ytunnus' value=''>";
	echo "<input type='submit' value='".t("Takaisin toimittajan valintaan")."'>";
	echo "</form>";
}

