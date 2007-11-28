<?php

	// jos ollaan saavuttamassa tuotteita varastoon, jota on ollu JT-SUPER j‰lkk‰riss‰ tehd‰‰n niist‰ automaattisesti tilaus joka laskutetaan
	// eli t‰pp‰ "suoraan laskutukseen" p‰‰lle tilaukselle...
	// tarvitaan keikan tunnus muuttujassa $otunnus
	// jos $mista ei ole tyhj‰ tullaan k‰yttˆliittym‰st‰, jolloin aina kutsutaan t‰t‰ failia eik‰ haluta pilata k‰ytt‰j‰n p‰iv‰‰ echoamalla ett‰ ep‰onnistui

	if ($mista == 'varastoon.inc' and $superirivit != '') {
		$alkulisa = " tilausrivi.tunnus in ($superirivit) and ";
	}
	else {
		$alkulisa = " tilausrivi.uusiotunnus='$otunnus' and ";
	}

	// haetaan eka keikan kaikki rivit (ostotilauksen verkkotunnus kent‰ss‰ on alkuper‰isen myyntitilauksen lasku.liitostunnus)
	$query = "	select tilausrivi.*, verkkotunnus from
				tilausrivi use index (uusiotunnus_index)
				join lasku on lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
				where tilausrivi.yhtio='$kukarow[yhtio]' and
				$alkulisa
				tilausrivi.tyyppi='O' and
				tilausrivi.tilaajanrivinro <> 0";
	$superres = mysql_query($query) or pupe_error($query);

	$jt_otsikot = array(); // otetaan talteen kaikki otsikot
	$varastosta = array(); // mist‰ varastoista saa myyd‰

	// haetaan kaikki varastot jotka ei ole myyntikiellossa
	$query = "select tunnus from varastopaikat where yhtio='$kukarow[yhtio]' and tyyppi=''";
	$vares = mysql_query($query) or pupe_error($query);

	while ($varow = mysql_fetch_array($vares)) {
		$varastosta[] = $varow["tunnus"];
	}

	while ($rivi = mysql_fetch_array($superres)) {

		// katsotaan onko tuotetta super-jt:ss‰ t‰lle samalle asiakkaalle samaa m‰‰r‰‰
		$query = "	select tilausrivi.*, lasku.nimi, lasku.ytunnus
					from tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
					join lasku on lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and lasku.liitostunnus='$rivi[verkkotunnus]'
					where tilausrivi.yhtio = '$kukarow[yhtio]' and
					tilausrivi.tyyppi = 'L' and
					tilausrivi.var = 'J' and
					tilausrivi.tilaajanrivinro <> 0 and
					tilausrivi.tuoteno = '$rivi[tuoteno]' and
					tilausrivi.jt = '$rivi[kpl]'
					order by tunnus
					limit 1";
		$jtres = mysql_query($query) or pupe_error($query);

		// jos lˆyty oikea rivi
		if (mysql_num_rows($jtres) == 1) {
			
			$jtrivit = mysql_fetch_array($jtres);
			
			$laskuvirhe .= "$jtrivit[nimi] ($jtrivit[ytunnus]): ".t("T‰ysin oikea rivi lˆytyi").". $jtrivit[tuoteno] ".t("toimitetaan")." $jtrivit[jt] ".t("kpl")." ($jtrivit[tunnus])\n";

			$tunnukset 			= $jtrivit["tunnus"];
			$tunnusarray		= array(0 => $jtrivit["tunnus"]);
			$kpl	 			= array($tunnukset => $jtrivit["jt"]);
			$loput 				= array($tunnukset => "JATA");
			$varataan_saldoa 	= "EI"; // pakotetaan myynti saldosta v‰litt‰m‰tt‰ kaikista varastoista
			$alv				= $jtrivit["alv"];
			$olpaikalta 		= "TIETENKII";

			require ("tee_jt_tilaus.inc");
			tee_jt_tilaus($tunnukset, $tunnusarray, $kpl, $loput, $suoratoimpaikka, $tilaus_on_jo, $varastosta, $olpaikalta, $varataan_saldoa, $alv);
			
			if ($paikka != '') {
				$laskuvirhe .= "Toimitetaan paikalta $paikka\n";
			}
			
			//jt-tilausten laskutunnukset talteen, n‰m‰ pit‰‰ sitten merkata valmiiksi lopussa
			if (!in_array($id, $jt_otsikot)) $jt_otsikot[] = $id;
		}
		// ei lˆytyny oikeella m‰‰r‰ll‰.. osatoimitetaan
		else {

			// katsotaan onko tuotetta super-jt:ss‰ t‰lle samalle asiakkaalle
			$query = "	select tilausrivi.*, lasku.nimi, lasku.ytunnus
						from tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
						join lasku on lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and lasku.liitostunnus='$rivi[verkkotunnus]'
						where tilausrivi.yhtio = '$kukarow[yhtio]' and
						tilausrivi.tyyppi = 'L' and
						tilausrivi.var = 'J' and
						tilausrivi.tilaajanrivinro <> 0 and
						tilausrivi.tuoteno = '$rivi[tuoteno]'
						order by tunnus";
			$jtres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($jtres) == 0) {
				// #TODO t‰ss‰ voi k‰yd‰ niin, ett‰ jos joku on dellannut JT rivit ja otsikot j‰‰ lasku l‰hett‰m‰tt‰ kokonaan asiakkaalle.. me ei vaan tiedet‰ otsikkoa kenelle t‰‰ pit‰s lis‰t‰ niin pattitilanne
				$laskuvirhe .= t("Tuotteen suoratoimitus ep‰onnistui!")." ".sprintf (t("Asiakkaalle (%s) ei ole yht‰‰n tuotetta (%s) j‰lkitoimituksessa!"), $rivi["verkkotunnus"], $rivi["tuoteno"])."\n";
			}

			while ($jtrivit = mysql_fetch_array($jtres)) {

				$tunnukset 			= $jtrivit["tunnus"];
				$tunnusarray		= array(0 => $jtrivit["tunnus"]);
				$loput 				= array($tunnukset => "JATA");
				$varataan_saldoa 	= "EI"; // pakotetaan myynti saldosta v‰litt‰m‰tt‰ kaikista varastoista
				$alv				= $jtrivit["alv"];

				// yritet‰‰n myyd‰ niin paljon kun ollaan viem‰ss‰ varastoon tai sitten koko JT rivi kerralla
				if ($rivi["kpl"] > $jtrivit["jt"]) {
					$kpl = array($tunnukset => $jtrivit["jt"]);
				}
				else {
					$kpl = array($tunnukset => $rivi["kpl"]);
				}

				$laskuvirhe .= "$jtrivit[nimi] ($jtrivit[ytunnus]): ".t("Toimitetaan osatoimitus").". $jtrivit[tuoteno] ".t("yritet‰‰n toimittaa")." $kpl[$tunnukset] ".t("kpl")." ".t("jtss‰ rivill‰")." $jtrivit[jt] ".t("kpl")." ".t("viem‰ss‰ varastoon")." $rivi[kpl] ".t("kpl")." ($jtrivit[tunnus])\n";

				require ("tee_jt_tilaus.inc");
				tee_jt_tilaus($tunnukset, $tunnusarray, $kpl, $loput, $suoratoimpaikka, $tilaus_on_jo, $varastosta, $olpaikalta, $varataan_saldoa, $alv);

				//jt-tilausten laskutunnukset talteen, n‰m‰ pit‰‰ sitten merkata valmiiksi lopus
				if (!in_array($id, $jt_otsikot)) $jt_otsikot[] = $id;
			}
		}
	}

	if (count($jt_otsikot) > 0) {
		// saatiin jotain toimitettua tehd‰‰n tilaukset laskutusvalmiiksi
		foreach ($jt_otsikot as $apukala) {

			// laitetaan laskulle ei-l‰hetett‰ fl‰gi ja oikea alatila, niin menee suoraan laskutukseen
			$query = "update lasku set eilahetetta='o', alatila='A' where yhtio='$kukarow[yhtio]' and tunnus='$apukala'";
			$jtres = mysql_query($query) or pupe_error($query);

			// haetaan laskurow
			$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$apukala'";
			$jtres = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($jtres);

			// tilaus-valmis vaatii t‰n
			$kukarow["kesken"] = $apukala;

			$laskuvirhe .= t("Laskutetaan suoratoimitus")." $apukala\n";

			require ("tilaus-valmis.inc");
		}
	}
	elseif ($mista == 'varastoon.inc') {
		$laskuvirhe .= t("Ei suoratoimitettavaa keikalta!")." ($otunnus)\n";
	}
	else {
		$laskuvirhe .= t("Suoratoimituksen laskutus ep‰onnistui! Yht‰‰n rivi‰ ei voitu toimittaa keikalta!")." ($otunnus)\n";
	}

	if ($mista == 'varastoon.inc') {
		$laskuvirhe = str_replace("\n","<br>",$laskuvirhe);
	}

?>