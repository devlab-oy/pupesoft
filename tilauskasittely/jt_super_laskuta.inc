<?php

	// jos ollaan saavuttamassa tuotteita varastoon, jota on ollu JT-SUPER jälkkärissä tehdään niistä automaattisesti tilaus joka laskutetaan
	// eli täppä lähetettä ei tulosteta päälle vaan tilaukselle...
	// tarvitaan keikan tunnus muuttujassa $otunnus
	// jos $mista ei ole tyhjä tullaan käyttöliittymästä, jolloin aina kutsutaan tätä failia eikä haluta pilata käyttäjän päivää echoamalla että epäonnistui
	
	if ($mista == 'varastoon.inc' and $superirivit != '') {
		$alkulisa = " tilausrivi.tunnus in ($superirivit) and ";
	}
	else {
		$alkulisa = " tilausrivi.uusiotunnus='$otunnus' and ";
	}
	
	// haetaan eka keikan kaikki rivit (ostotilauksen verkkotunnus kentässä on alkuperäisen myyntitilauksen lasku.liitostunnus)
	$query = "	select tilausrivi.*, verkkotunnus from
				tilausrivi use index (uusiotunnus_index)
				join lasku on lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
				where tilausrivi.yhtio='$kukarow[yhtio]' and
				$alkulisa
				tilausrivi.tyyppi='O' and
				tilausrivi.tilaajanrivinro <> 0";
	$superres = mysql_query($query) or pupe_error($query);

	$jt_otsikot = array(); // otetaan talteen kaikki otsikot
	$varastosta = array(); // mistä varastoista saa myydä

	// haetaan kaikki varastot jotka ei ole myyntikiellossa
	$query = "select tunnus from varastopaikat where yhtio='$kukarow[yhtio]' and tyyppi=''";
	$vares = mysql_query($query) or pupe_error($query);

	while ($varow = mysql_fetch_array($vares)) {
		$varastosta[] = $varow["tunnus"];
	}

	while ($rivi = mysql_fetch_array($superres)) {

		// katsotaan onko tuotetta super-jt:ssä tälle samalle asiakkaalle samaa määrää
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

		// jos löyty oikea rivi
		if (mysql_num_rows($jtres) == 1) {

			$jtrivit = mysql_fetch_array($jtres);
			$laskuvirhe .= "$jtrivit[nimi] ($jtrivit[ytunnus]): ".t("Täysin oikea rivi löytyi").". $jtrivit[tuoteno] ".t("toimitetaan")." $jtrivit[jt] ".t("kpl")." ($jtrivit[tunnus])\n";

			$kpl = array();
			$loput = array();
			$tunnus = $jtrivit["tunnus"];
			$kpl[$tunnus] = $jtrivit["jt"];
			$loput[$tunnus] = "JATA";
			$naytakaikkipaikat = "ON"; // pakotetaan myynti kaikista varastoista (myyntikiellot yms)
			$ohitasaldotarkistus = "ON"; // pakotetaan myynti saldosta välittämättä

			require ("tee_jt_tilaus.inc");

			//jt-tilausten laskutunnukset talteen, nämä pitää sitten merkata valmiiksi lopus
			if (!in_array($id, $jt_otsikot)) $jt_otsikot[] = $id;
		}
		// ei löytyny oikeella määrällä.. osatoimitetaan
		else {

			// katsotaan onko tuotetta super-jt:ssä tälle samalle asiakkaalle
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
				$laskuvirhe .= t("Tuotteen suoratoimitus epäonnistui!")." ".sprintf (t("Asiakkaalle (%s) ei ole yhtään tuotetta (%s) jälkitoimituksessa!"), $rivi["verkkotunnus"], $rivi["tuoteno"])."\n";
			}

			while ($jtrivit = mysql_fetch_array($jtres)) {

				$kpl = array();
				$loput = array();

				$tunnus = $jtrivit["tunnus"];
				$loput[$tunnus] = "JATA";
				$naytakaikkipaikat = "ON"; // pakotetaan myynti kaikista varastoista (myyntikiellot yms)
				$ohitasaldotarkistus = "ON"; // pakotetaan myynti saldosta välittämättä

				// yritetään myydä niin paljon kun ollaan viemässä varastoon tai sitten koko JT rivi kerralla
				if ($rivi["kpl"] > $jtrivit["jt"]) $kpl[$tunnus] = $jtrivit["jt"];
				else $kpl[$tunnus] = $rivi["kpl"];

				$laskuvirhe .= "$jtrivit[nimi] ($jtrivit[ytunnus]): ".t("Toimitetaan osatoimitus").". $jtrivit[tuoteno] ".t("yritetään toimittaa")." $kpl[$tunnus] ".t("kpl")." ".t("jtssä rivillä")." $jtrivit[jt] ".t("kpl")." ".t("viemässä varastoon")." $rivi[kpl] ".t("kpl")." ($jtrivit[tunnus])\n";

				require ("tee_jt_tilaus.inc");

				//jt-tilausten laskutunnukset talteen, nämä pitää sitten merkata valmiiksi lopus
				if (!in_array($id, $jt_otsikot)) $jt_otsikot[] = $id;
			}

		}

	}

	if (count($jt_otsikot) > 0) {
		// saatiin jotain toimitettua tehdään tilaukset laskutusvalmiiksi
		foreach ($jt_otsikot as $apukala) {

			// laitetaan laskulle ei-lähetettä flägi ja oikea alatila, niin menee suoraan laskutukseen
			$query = "update lasku set eilahetetta='o', alatila='A' where yhtio='$kukarow[yhtio]' and tunnus='$apukala'";
			$jtres = mysql_query($query) or pupe_error($query);

			// haetaan laskurow
			$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$apukala'";
			$jtres = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($jtres);

			// tilaus-valmis vaatii tän
			$kukarow["kesken"] = $apukala;

			$laskuvirhe .= t("Laskutetaan suoratoimitus")." $apukala\n";

			require ("tilaus-valmis.inc");
		}
	}
	elseif ($mista == 'varastoon.inc') {
		$laskuvirhe .= t("Ei suoratoimitettavaa keikalta!")." ($otunnus)\n";
	}
	else {
		$laskuvirhe .= t("Suoratoimituksen laskutus epäonnistui! Yhtään riviä ei voitu toimittaa keikalta!")." ($otunnus)\n";
	}
	
	if ($mista == 'varastoon.inc') {
		$laskuvirhe = str_replace("\n","<br>",$laskuvirhe);
	}

?>