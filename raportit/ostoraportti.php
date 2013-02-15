<?php

	if (isset($_REQUEST["tee"])) {
		if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/","",$_REQUEST["kaunisnimi"]);
	}

	// Enabloidaan, että Apache flushaa kaiken mahdollisen ruudulle kokoajan.
	//apache_setenv('no-gzip', 1);
	ini_set('zlib.output_compression', 0);
	ini_set('implicit_flush', 1);
	ob_implicit_flush(1);

	// Aikaa ja muistia enemmän kun normisivuille
	ini_set("memory_limit", "5G");
	ini_set("mysql.connect_timeout", 600);
	ini_set("max_execution_time", 18000);

	require ("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {

		if (!@include('Spreadsheet/Excel/Writer.php')) {
			echo "<font class='error'>".t("VIRHE: Pupe-asennuksesi ei tue Excel-kirjoitusta.")."</font><br>";
			exit;
		}

		$ala_tallenna = array(	"kysely",
								"uusirappari",
								"edkysely",
								"rtee",
								"mul_osasto",
								"mul_try",
								"mul_tme",
								"toimittajaid",
								"ytunnus",
								"asiakasosasto",
								"asiakasid",
								"asiakasno",
								"abcrajaus",
								"abcrajaustapa",
								"abcrajausluokka");

		if ($valitut["TALLENNAPAIVAM"] == '') {
			array_push($ala_tallenna, "ppa1", "kka1", "vva1", "ppl1", "kkl1", "vvl1", "ppa2", "kka2", "vva2", "ppl2", "kkl2", "vvl2", "ppa3", "kka3", "vva3", "ppl3", "kkl3", "vvl3", "ppa4", "kka4", "vva4", "ppl4", "kkl4", "vvl4");
		}

		list($kysely_kuka, $kysely_mika) = explode("#", $kysely);

		$kysely_warning = '';
		$rappari = '';

		if ($tee == "tallenna" and $kysely_kuka == $kukarow["kuka"]) {
			tallenna_muisti($kysely_mika, $ala_tallenna);
			$tee = 'JATKA';
			$rappari = $kysely_kuka;
		}
		elseif ($tee == 'tallenna' and $kysely_kuka != $kukarow['kuka']) {
			$tee = 'JATKA';
			$kysely_warning = 'yes';
			$kysely = '';
		}

		if ($tee == "uusiraportti") {
			tallenna_muisti($uusirappari, $ala_tallenna);
			$kysely = "$kukarow[kuka]#$uusirappari";
			$tee = 'JATKA';
			$rappari = $kysely_kuka;
		}

		if ($tee == "lataavanha") {
			hae_muisti($kysely_mika, $kysely_kuka);
			$tee = 'JATKA';
			$rappari = $kysely_kuka;
		}

		//* Tämä skripti käyttää slave-tietokantapalvelinta *//
		$useslave = 1;

		require ("inc/connect.inc");

		echo "<font class='head'>".t("Ostoraportti")."</font><hr>";

		// org_rajausta tarvitaan yhdessä selectissä joka triggeröi taas toisen asian.
		$org_rajaus = $abcrajaus;
		list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

		if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";

		list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

		// Jos jt-rivit varaavat saldoa niin se vaikuttaa asioihin
		if ($yhtiorow["varaako_jt_saldoa"] != "") {
			$lisavarattu = " + tilausrivi.varattu";
		}
		else {
			$lisavarattu = "";
		}

		// Tarvittavat päivämäärät
		if (!isset($kka1)) $kka1 = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($vva1)) $vva1 = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($ppa1)) $ppa1 = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($kkl1)) $kkl1 = date("m");
		if (!isset($vvl1)) $vvl1 = date("Y");
		if (!isset($ppl1)) $ppl1 = date("d");

		if (!isset($kka2)) $kka2 = date("m",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
		if (!isset($vva2)) $vva2 = date("Y",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
		if (!isset($ppa2)) $ppa2 = date("d",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
		if (!isset($kkl2)) $kkl2 = date("m");
		if (!isset($vvl2)) $vvl2 = date("Y");
		if (!isset($ppl2)) $ppl2 = date("d");

		if (!isset($kka3)) $kka3 = date("m",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
		if (!isset($vva3)) $vva3 = date("Y",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
		if (!isset($ppa3)) $ppa3 = date("d",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
		if (!isset($kkl3)) $kkl3 = date("m");
		if (!isset($vvl3)) $vvl3 = date("Y");
		if (!isset($ppl3)) $ppl3 = date("d");

		if (!isset($kka4)) $kka4 = date("m",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
		if (!isset($vva4)) $vva4 = date("Y",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
		if (!isset($ppa4)) $ppa4 = date("d",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
		if (!isset($kkl4)) $kkl4 = date("m");
		if (!isset($vvl4)) $vvl4 = date("Y");
		if (!isset($ppl4)) $ppl4 = date("d");

		if (!isset($naytauudet_kk)) $naytauudet_kk = date("m",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
		if (!isset($naytauudet_vv)) $naytauudet_vv = date("Y",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
		if (!isset($naytauudet_pp)) $naytauudet_pp = date("d",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));

		//Edellisen vuoden vastaavat kaudet
		$kka1ed = date("m",mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
		$vva1ed = date("Y",mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
		$ppa1ed = date("d",mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
		$kkl1ed = date("m",mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));
		$vvl1ed = date("Y",mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));
		$ppl1ed = date("d",mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));

		$kka2ed = date("m",mktime(0, 0, 0, $kka2, $ppa2, $vva2-1));
		$vva2ed = date("Y",mktime(0, 0, 0, $kka2, $ppa2, $vva2-1));
		$ppa2ed = date("d",mktime(0, 0, 0, $kka2, $ppa2, $vva2-1));
		$kkl2ed = date("m",mktime(0, 0, 0, $kkl2, $ppl2, $vvl2-1));
		$vvl2ed = date("Y",mktime(0, 0, 0, $kkl2, $ppl2, $vvl2-1));
		$ppl2ed = date("d",mktime(0, 0, 0, $kkl2, $ppl2, $vvl2-1));

		$kka3ed = date("m",mktime(0, 0, 0, $kka3, $ppa3, $vva3-1));
		$vva3ed = date("Y",mktime(0, 0, 0, $kka3, $ppa3, $vva3-1));
		$ppa3ed = date("d",mktime(0, 0, 0, $kka3, $ppa3, $vva3-1));
		$kkl3ed = date("m",mktime(0, 0, 0, $kkl3, $ppl3, $vvl3-1));
		$vvl3ed = date("Y",mktime(0, 0, 0, $kkl3, $ppl3, $vvl3-1));
		$ppl3ed = date("d",mktime(0, 0, 0, $kkl3, $ppl3, $vvl3-1));

		$kka4ed = date("m",mktime(0, 0, 0, $kka4, $ppa4, $vva4-1));
		$vva4ed = date("Y",mktime(0, 0, 0, $kka4, $ppa4, $vva4-1));
		$ppa4ed = date("d",mktime(0, 0, 0, $kka4, $ppa4, $vva4-1));
		$kkl4ed = date("m",mktime(0, 0, 0, $kkl4, $ppl4, $vvl4-1));
		$vvl4ed = date("Y",mktime(0, 0, 0, $kkl4, $ppl4, $vvl4-1));
		$ppl4ed = date("d",mktime(0, 0, 0, $kkl4, $ppl4, $vvl4-1));


		//katotaan pienin alkupvm ja isoin loppupvm
		$apaiva1 = (int) date('Ymd',mktime(0,0,0,$kka1,$ppa1,$vva1));
		$apaiva2 = (int) date('Ymd',mktime(0,0,0,$kka2,$ppa2,$vva2));
		$apaiva3 = (int) date('Ymd',mktime(0,0,0,$kka3,$ppa3,$vva3));
		$apaiva4 = (int) date('Ymd',mktime(0,0,0,$kka4,$ppa4,$vva4));
		$apaiva5 = (int) date('Ymd',mktime(0,0,0,$kka1ed,$ppa1ed,$vva1ed));
		$apaiva6 = (int) date('Ymd',mktime(0,0,0,$kka2ed,$ppa2ed,$vva2ed));
		$apaiva7 = (int) date('Ymd',mktime(0,0,0,$kka3ed,$ppa3ed,$vva3ed));
		$apaiva8 = (int) date('Ymd',mktime(0,0,0,$kka4ed,$ppa4ed,$vva4ed));

		$lpaiva1 = (int) date('Ymd',mktime(0,0,0,$kkl1,$ppl1,$vvl1));
		$lpaiva2 = (int) date('Ymd',mktime(0,0,0,$kkl2,$ppl2,$vvl2));
		$lpaiva3 = (int) date('Ymd',mktime(0,0,0,$kkl3,$ppl3,$vvl3));
		$lpaiva4 = (int) date('Ymd',mktime(0,0,0,$kkl4,$ppl4,$vvl4));
		$lpaiva5 = (int) date('Ymd',mktime(0,0,0,$kkl1ed,$ppl1ed,$vvl1ed));
		$lpaiva6 = (int) date('Ymd',mktime(0,0,0,$kkl2ed,$ppl2ed,$vvl2ed));
		$lpaiva7 = (int) date('Ymd',mktime(0,0,0,$kkl3ed,$ppl3ed,$vvl3ed));
		$lpaiva8 = (int) date('Ymd',mktime(0,0,0,$kkl4ed,$ppl4ed,$vvl4ed));
		$lpaiva9 = (int) date('Ymd',mktime(0,0,0,$naytauudet_kk,$naytauudet_pp,$naytauudet_vv));


		$apienin = 99999999;
		$lsuurin = 0;

		if ($apaiva1 <= $apienin and $apaiva1 != 19700101) $apienin = $apaiva1;
		if ($apaiva2 <= $apienin and $apaiva2 != 19700101) $apienin = $apaiva2;
		if ($apaiva3 <= $apienin and $apaiva3 != 19700101) $apienin = $apaiva3;
		if ($apaiva4 <= $apienin and $apaiva4 != 19700101) $apienin = $apaiva4;
		if ($apaiva5 <= $apienin and $apaiva5 != 19700101) $apienin = $apaiva5;
		if ($apaiva6 <= $apienin and $apaiva6 != 19700101) $apienin = $apaiva6;
		if ($apaiva7 <= $apienin and $apaiva7 != 19700101) $apienin = $apaiva7;
		if ($apaiva8 <= $apienin and $apaiva8 != 19700101) $apienin = $apaiva8;

		if ($lpaiva1 >= $lsuurin and $lpaiva1 != 19700101) $lsuurin = $lpaiva1;
		if ($lpaiva2 >= $lsuurin and $lpaiva2 != 19700101) $lsuurin = $lpaiva2;
		if ($lpaiva3 >= $lsuurin and $lpaiva3 != 19700101) $lsuurin = $lpaiva3;
		if ($lpaiva4 >= $lsuurin and $lpaiva4 != 19700101) $lsuurin = $lpaiva4;
		if ($lpaiva5 >= $lsuurin and $lpaiva5 != 19700101) $lsuurin = $lpaiva5;
		if ($lpaiva6 >= $lsuurin and $lpaiva6 != 19700101) $lsuurin = $lpaiva6;
		if ($lpaiva7 >= $lsuurin and $lpaiva7 != 19700101) $lsuurin = $lpaiva7;
		if ($lpaiva8 >= $lsuurin and $lpaiva8 != 19700101) $lsuurin = $lpaiva8;
		if ($lpaiva9 >= $lsuurin and $lpaiva9 != 19700101) $lsuurin = $lpaiva9;

		if ($apienin == 99999999 and $lsuurin == 0) {
			$apienin = $lsuurin = date('Ymd'); // jos mitään ei löydy niin NOW molempiin. :)
		}

		$apvm = substr($apienin,0,4)."-".substr($apienin,4,2)."-".substr($apienin,6,2);
		$lpvm = substr($lsuurin,0,4)."-".substr($lsuurin,4,2)."-".substr($lsuurin,6,2);

		//Voidaan tarvita jotain muuttujaa täältä
		if (isset($muutparametrit)) {
			list($temp_osasto,$temp_tuoryh,$temp_ytunnus,$temp_tuotemerkki,$temp_asiakasosasto,$temp_asiakasno,$temp_toimittaja) = explode('#', $muutparametrit);
		}

		// Tulostettavat sarakkeet
		$sarakkeet = array(	"os", "try", "tme", "malli", "mallitark",
							"sta", "tah",
							"abc", "abc os", "abc try", "abc tme",
							"luontiaika",
							"saldo", "reaalisaldo", "saldo2",
							"haly", "til", "valmistuksessa", "ennpois", "jt", "siirtojt", "ennakot",
							"1kk", "3kk", "6kk", "12kk", "ke", "1x2",
							"ostoera1", "ostoera3", "ostoera6", "ostoera12", "osthaly1", "osthaly3", "osthaly6", "osthaly12",
							"o_era", "m_era", "kosal", "komy", "Määrä",
							"kuvaus", "lyhytkuvaus", "tkorkeus", "tleveys", "tmassa", "tsyvyys", "eankoodi",
							"hinnastoon", "toimittaja", "toim_tuoteno",
							"nimitys", "ostohinta", "myyntihinta",
							"epa25pvm", "epa50pvm", "epa75pvm", "epa100pvm",
							"osaldo", "hyllypaikka",
							"pu1", "pu3", "pu6", "pu12",
							"my1", "my3", "my6","my12",
							"kul1", "kul3", "kul6", "kul12",
							"edkul1", "edkul3", "edkul6", "edkul12",
							"enn1", "enn3", "enn6","enn12",
							"ykpl1", "ykpl3", "ykpl6","ykpl12",
							"e_kate1", "e_kate3", "e_kate6", "e_kate12",
							"e_kate % 1", "e_kate % 3", "e_kate % 6", "e_kate % 12",
							"ed1", "ed3", "ed6", "ed12",
							"kate1", "kate3", "kate6", "kate12",
							"Kate % 1", "Kate % 3", "Kate % 6", "Kate % 12",
							"aleryh", "kehahin",
							"Kortuoteno", "Korsaldo", "Korennpois", "Kortil",
							"Kormy1", "Kormy2","Kormy3","Kormy4");

		// sarakkeiden queryissä olevat nimet
		$sarake_keyt = array(	"os"			=> "osasto",
								"try"			=> "try",
								"tme"			=> "tuotemerkki",
								"malli"			=> "malli",
								"mallitark"		=> "mallitarkenne",
								"sta"			=> "status",
								"tah" 			=> "tahtituote",
								"abc" 			=> "abcluokka",
								"abc os"		=> "abcluokka_osasto",
								"abc try"		=> "abcluokka_try",
								"abc tme" 		=> "abcluokka_tuotemerkki",
								"luontiaika"	=> "luontiaika",
								"saldo" 		=> "saldo",
								"saldo2" 		=> "saldo2",
								"reaalisaldo"	=> "reaalisaldo",
								"haly"			=> "halytysraja",
								"til"			=> "tilattu",
								"valmistuksessa"=> "valmistuksessa",
								"ennpois"		=> "ennpois",
								"jt"			=> "jt",
								"siirtojt"		=> "siirtojt",
								"ennakot"		=> "ennakot",
								"1kk"			=> "ostettava_kausi1",
								"3kk"			=> "ostettava_kausi2",
								"6kk"			=> "ostettava_kausi3",
								"12kk"			=> "ostettava_kausi4",
								"ke" 			=> "ke",
								"1x2"			=> "tuotekerroin",
								"ostoera1"		=> "ostettava_era1",
								"ostoera3"		=> "ostettava_era2",
								"ostoera6"		=> "ostettava_era3",
								"ostoera12"		=> "ostettava_era4",
								"osthaly1"		=> "ostettavahaly_kausi1",
								"osthaly3"		=> "ostettavahaly_kausi2",
								"osthaly6"		=> "ostettavahaly_kausi3",
								"osthaly12"		=> "ostettavahaly_kausi4",
								"o_era"			=> "osto_era",
								"m_era"			=> "myynti_era",
								"kosal"			=> "kosal",
								"komy"			=> "komy",
								"Määrä"			=> "kpl",
								"kuvaus"		=> "kuvaus",
								"lyhytkuvaus"	=> "lyhytkuvaus",
								"tkorkeus"		=> "tuotekorkeus",
								"tleveys"		=> "tuoteleveys",
								"tmassa"		=> "tuotemassa",
								"tsyvyys"		=> "tuotesyvyys",
								"eankoodi"		=> "eankoodi",
								"hinnastoon"	=> "hinnastoon",
								"toimittaja"	=> "toimittaja",
								"toim_tuoteno"	=> "toim_tuoteno",
								"nimitys"		=> "nimitys",
								"ostohinta"		=> "ostohinta",
								"myyntihinta"	=> "myyntihinta",
								"epa25pvm"		=> "epakurantti25pvm",
								"epa50pvm"		=> "epakurantti50pvm",
								"epa75pvm"		=> "epakurantti75pvm",
								"epa100pvm"		=> "epakurantti100pvm",
								"osaldo"		=> "osaldo",
								"hyllypaikka"	=> "varastopaikka",
								"pu1"			=> "puutekpl1",
								"pu3"			=> "puutekpl2",
								"pu6"			=> "puutekpl3",
								"pu12"			=> "puutekpl4",
								"my1"			=> "kpl1",
								"my3"			=> "kpl2",
								"my6"			=> "kpl3",
								"my12"			=> "kpl4",
								"kul1"			=> "kpl1",
								"kul3"			=> "kpl2",
								"kul6"			=> "kpl3",
								"kul12"			=> "kpl4",
								"edkul1"		=> "EDkpl1",
								"edkul3"		=> "EDkpl2",
								"edkul6"		=> "EDkpl3",
								"edkul12"		=> "EDkpl4",
								"enn1"			=> "e_kpl1",
								"enn3"			=> "e_kpl2",
								"enn6"			=> "e_kpl3",
								"enn12"			=> "e_kpl4",
								"ykpl1"			=> "ykpl1",
								"ykpl3"			=> "ykpl2",
								"ykpl6"			=> "ykpl3",
								"ykpl12"		=> "ykpl4",
								"e_kate1"		=> "e_kate1",
								"e_kate3"		=> "e_kate2",
								"e_kate6"		=> "e_kate3",
								"e_kate12"		=> "e_kate4",
								"e_kate % 1"	=> "katepros1_ennakko",
								"e_kate % 3"	=> "katepros2_ennakko",
								"e_kate % 6"	=> "katepros3_ennakko",
								"e_kate % 12"	=> "katepros4_ennakko",
								"ed1"			=> "EDkpl1",
								"ed3"			=> "EDkpl2",
								"ed6"			=> "EDkpl3",
								"ed12"			=> "EDkpl4",
								"kate1"			=> "kate1",
								"kate3"			=> "kate2",
								"kate6"			=> "kate3",
								"kate12"		=> "kate4",
								"Kate % 1"		=> "katepros1",
								"Kate % 3"		=> "katepros2",
								"Kate % 6"		=> "katepros3",
								"Kate % 12"		=> "katepros4",
								"aleryh"		=> "aleryhma",
								"kehahin"		=> "kehahin",
								"Kortuoteno"	=> "tuoteno",
								"Korsaldo"		=> "saldo",
								"Korennpois"	=> "varattu",
								"Kortil"		=> "tilattu",
								"Kormy1"		=> "kpl1",
								"Kormy2"		=> "kpl2",
								"Kormy3"		=> "kpl3",
								"Kormy4"		=> "kpl4");

		//	Haetaan kaikki varastot ja luodaan kysely paljonko ko. varastoon on tilattu tavaraa..
		$varastolisa = "";

		//Ajetaan itse raportti
		if (isset($RAPORTOI) and $tee == "RAPORTOI") {
			$osasto = '';
			$osasto2 = '';
			$try = '';
			$try2 = '';
			$tme = '';
			$tme2 = '';

			$mul_osasto = unserialize(urldecode($mul_osasto));
			$mul_try = unserialize(urldecode($mul_try));
			$mul_tme = unserialize(urldecode($mul_tme));

			$lisa = unserialize(urldecode($lisa));
			$lisa_parametri = unserialize(urldecode($lisa_parametri));

			// tallennamuisti-funkkarin takia joudutaan kaksi kertaa unserializee.
			if ($mul_osasto != '' and !is_array($mul_osasto)) {
				$mul_osasto = unserialize($mul_osasto);
			}

			if ($mul_try != '' and !is_array($mul_try)) {
				$mul_try = unserialize($mul_try);
			}

			if ($mul_tme != '' and !is_array($mul_tme)) {
				$mul_tme = unserialize($mul_tme);
			}
			if ($mul_try != '' and count($mul_try) > 0) {

				foreach ($mul_try as $tr) {
					$try .= "'$tr',";
				}

				$try = substr($try, 0, -1);

				$sresult = t_avainsana("TRY", "", "and avainsana.selite  in ($try)");

				while ($srow = mysql_fetch_array($sresult)) {
					$try2 .= "{$srow['selite']} {$srow['selitetark']}<br>";
				}

				$try2 = substr($try2, 0, -4);

			}
			if ($mul_osasto != '' and count($mul_osasto) > 0) {

				foreach ($mul_osasto as $os) {
					$osasto .= "'$os',";
				}

				$osasto = substr($osasto, 0, -1);

				$sresult = t_avainsana("OSASTO", "", "and avainsana.selite  in ($osasto)");

				while($trow = mysql_fetch_array($sresult)) {
					$osasto2 .= "{$trow['selite']} {$trow['selitetark']}<br>";
				}

				$osasto2 = substr($osasto2, 0, -4);
			}
			if ($mul_tme != '' and count($mul_tme) > 0) {

				foreach ($mul_tme as $tm) {
					$tme .= "'$tm',";
				}

				$tme = substr($tme, 0, -1);

				$sresult = t_avainsana("TUOTEMERKKI", "", "and avainsana.selite  in ($tme)");

				while ($tmerow = mysql_fetch_array($sresult)) {
					$tme2 .= "{$tmerow['selite']}<br>";
				}

				$tme2 = substr($tme2, 0, -4);
			}
			if ($toimittajaid != '') {
				$query = "	SELECT nimi
							FROM toimi
							WHERE yhtio = '$kukarow[yhtio]' and tunnus='$toimittajaid'";
				$sresult = pupe_query($query);
				$trow1 = mysql_fetch_array($sresult);
			}
			if ($asiakasid != '') {
				$query = "	SELECT nimi
							FROM asiakas
							WHERE yhtio = '$kukarow[yhtio]' and tunnus='$asiakasid'";
				$sresult = pupe_query($query);
				$trow2 = mysql_fetch_array($sresult);
			}

			$abcnimi = $ryhmanimet[$abcrajaus];


			echo "	<table>
					<tr><th>".t("Osasto")."</th><td colspan='3'>$osasto2</td></tr>
					<tr><th>".t("Tuoteryhmä")."</th><td colspan='3'>$try2</td></tr>
					<tr><th>".t("Toimittaja")."</th><td colspan='3'>$ytunnus $trow1[nimi]</td></tr>
					<tr><th>".t("Tuotemerkki")."</th><td colspan='3'>$tme2</td></tr>
					<tr><th>".t("ABC-rajaus")."</th><td colspan='3'>$abcnimi</td></tr>
					<tr><th>".t("Asiakasosasto")."</th><td colspan='3'>$asiakasosasto</td></tr>
					<tr><th>".t("Asiakas")."</th><td colspan='3'>$asiakasno $trow2[nimi]</td></tr>";

			echo "	</table><br>";
			flush();

			$lisaa  = ""; // tuote-rajauksia
			$lisaa2 = ""; // toimittaja-rajauksia

			if ($osasto != '') {
				$lisaa .= " and tuote.osasto in ($osasto) ";
			}
			if ($try != '') {
				$lisaa .= " and tuote.try in ($try) ";
			}
			if ($tme != '') {
				$lisaa .= " and tuote.tuotemerkki in ($tme) ";
			}
			if ($valitut["poistetut"] != '') {
				$lisaa .= " and tuote.status != 'P' ";
			}
			if ($valitut["poistuvat"] != '') {
				$lisaa .= " and tuote.status != 'X' ";
			}
			if ($valitut["EIHINNASTOON"] != '') {
				$lisaa .= " and tuote.hinnastoon != 'E' ";
			}
			if ($valitut["EIVARASTOITAVA"] != '') {
				$lisaa .= " and tuote.status != 'T' ";
			}

			if ($toimittajaid != '') {
				$lisaa2 .= " JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid' ";
			}

			///* Tämä skripti käyttää slave-tietokantapalvelinta *///
			$useslave = 1;
			//Eli haetaan connect.inc uudestaan tässä
			require("../inc/connect.inc");

			//Yhtiövalinnat
			$query	= "	SELECT distinct yhtio, nimi
						from yhtio
						where konserni = '$yhtiorow[konserni]' and konserni != ''";
			$presult = pupe_query($query);

			$yhtiot 	= "";
			$konsyhtiot = "";

			if (mysql_num_rows($presult) > 0) {
				while ($prow = mysql_fetch_array($presult)) {
					if ($valitut["YHTIO##$prow[yhtio]"] == "YHTIO##".$prow["yhtio"]) {
						$yhtiot .= "'".$prow["yhtio"]."',";
					}
					$konsyhtiot .= "'".$prow["yhtio"]."',";
				}

				if ($yhtiot != '') {
					$yhtiot = substr($yhtiot,0,-1);
					$yhtiot = "yhtio in ($yhtiot) ";
				}

				$konsyhtiot = substr($konsyhtiot,0,-1);
				$konsyhtiot = "yhtio in ($konsyhtiot) ";
			}
			else {
				$yhtiot = "'".$kukarow["yhtio"]."'";
				$yhtiot = "yhtio in ($yhtiot) ";

				$konsyhtiot = "'".$kukarow["yhtio"]."'";
				$konsyhtiot = "yhtio in ($konsyhtiot) ";
			}

			//Katsotaan valitut varastot
			$query = "	SELECT *
						FROM varastopaikat
						WHERE $konsyhtiot
						ORDER BY yhtio, tyyppi, nimitys";
			$vtresult = pupe_query($query);

			$varastot 			= "";
			$varastot2			= "";
			$varastot_yhtiot 	= "";

			while ($vrow = mysql_fetch_array($vtresult)) {
				if ($valitut["VARASTO##$vrow[tunnus]"] == "VARASTO##".$vrow["tunnus"]) {
					$varastot .= "'".$vrow["tunnus"]."',";
					$varastot_yhtiot .= "'".$vrow["yhtio"]."',";
				}
				if ($valitut["VARASTO2##$vrow[tunnus]"] == "VARASTO2##".$vrow["tunnus"]) {
					$varastot2 .= "'".$vrow["tunnus"]."',";
				}
			}

			$varastot 		 = substr($varastot,0,-1);
			$varastot2		 = substr($varastot2,0,-1);
			$varastot_yhtiot = substr($varastot_yhtiot,0,-1);

			$paikoittain = $valitut["paikoittain"];

			if ($varastot == "" and $paikoittain != "") {
				echo "<font class='error'>".t("VIRHE: Et valinnut yhtään varastoa.")."</font>";
				exit;
			}

			if ($varastot == "") {
				echo "<font class='error'>".t("VIRHE: Et valinnut yhtään varastoa.")."</font>";
				exit;
			}

			if ($yhtiot == "") {
				echo "<font class='error'>".t("VIRHE: Et valinnut mitään yhtiötä.")."</font>";
				exit;
			}

			$abcwhere = "";

			if ($abcrajaus != "") {
				// katotaan JT:ssä olevat tuotteet
				$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
							FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
							JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
							WHERE tilausrivi.$yhtiot
							and tyyppi 	= 'L'
							and var 	= 'J'
							and jt $lisavarattu > 0";
				$vtresult = pupe_query($query);
				$vrow = mysql_fetch_array($vtresult);

				$jt_tuotteet = "''";

				if ($vrow[0] != "") {
					$jt_tuotteet = $vrow[0];
				}

				$abc_luontiaikarajaus = "";

				if ($abc_laadittuaika == "alle_12kk") {
					# Nämä menee lisää -muuttujaan, koska on AND ja pitää rajata koko queryä
					$lisaa .= " and tuote.luontiaika >= date_sub(current_date, interval 12 month) ";
				}
				elseif ($abc_laadittuaika == "yli_12kk") {
					# Nämä menee lisää -muuttujaan, koska on AND ja pitää rajata koko queryä
					$lisaa .= " and tuote.luontiaika < date_sub(current_date, interval 12 month) ";
				}
				elseif ($abc_laadittuaika == "yli_annettu" and $naytauudet_pp != '' and $naytauudet_kk != '' and $naytauudet_vv != '') {
					# Nämä menee abc_luontiaikarajaus -muuttujaan, koska on OR ja pitää rajata ehdollisesti abc rajausta
					$abc_luontiaikarajaus = " or tuote.luontiaika >= '$naytauudet_vv-$naytauudet_kk-$naytauudet_pp' ";
				}
				else {
					# Nämä menee abc_luontiaikarajaus -muuttujaan, koska on OR ja pitää rajata ehdollisesti abc rajausta
					$abc_luontiaikarajaus = " or tuote.luontiaika >= date_sub(current_date, interval 12 month) ";
				}

				// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
				if ($abcrajausluokka == 'y') {
					$abcwhere .= " and (abc_aputaulu.luokka <= '$abcrajaus'
									$abc_luontiaikarajaus
									or abc_aputaulu.tuoteno in ($jt_tuotteet))";
				}
				elseif ($abcrajausluokka == 'os') {
					$abcwhere .= " and (abc_aputaulu.luokka_osasto <= '$abcrajaus'
									$abc_luontiaikarajaus
									or abc_aputaulu.tuoteno in ($jt_tuotteet))";
				}
				elseif ($abcrajausluokka == 'try') {
					$abcwhere .= " and (abc_aputaulu.luokka_try <= '$abcrajaus'
									$abc_luontiaikarajaus
									or abc_aputaulu.tuoteno in ($jt_tuotteet))";
				}
				elseif ($abcrajausluokka == 'tme') {
					$abcwhere .= " and (abc_aputaulu.luokka_tuotemerkki <= '$abcrajaus'
									$abc_luontiaikarajaus
									or abc_aputaulu.tuoteno in ($jt_tuotteet))";
				}
				else {
					$abcwhere .= " and (abc_aputaulu.luokka <= '$abcrajaus'
									or abc_aputaulu.luokka_osasto <= '$abcrajaus'
									or abc_aputaulu.luokka_try <= '$abcrajaus'
									or abc_aputaulu.luokka_tuotemerkki <= '$abcrajaus'
									$abc_luontiaikarajaus
									or abc_aputaulu.tuoteno in ($jt_tuotteet)) ";
				}
			}

			if ($varastot != '') {
				$varastolisa_korv = " AND varastopaikat.tunnus in ($varastot) ";
				$varastot = " HAVING varastopaikat.tunnus in ($varastot) or varastopaikat.tunnus is null ";
			}

			if ($varastot2 != '') {
				$varastot2 = " HAVING varastopaikat.tunnus in ($varastot2) or varastopaikat.tunnus is null ";
			}

			if ($varastot_yhtiot != '') {
				$varastot_yhtiot = " yhtio in ($varastot_yhtiot) ";
			}

			$joinlisa 	= '';
			$tyyppilisa = '';
			$tyyppi 	= '';

			if (table_exists("yhteensopivuus_rekisteri")) {

				if (table_exists("yhteensopivuus_mp")) {
					$query = "	SELECT DISTINCT tyyppi
								FROM yhteensopivuus_mp
								WHERE yhtio = '{$kukarow['yhtio']}'";
					$res_rekyht = pupe_query($query);

					if (mysql_num_rows($res_rekyht) > 0) {
						$tyyppi = 'mp';

						while ($tyyppi_row = mysql_fetch_assoc($res_rekyht)) {
							$tyyppilisa .= "'$tyyppi_row[tyyppi]',";
						}
					}
				}


				if ($tyyppi == "" and table_exists("yhteensopivuus_tuote")) {
					$query = "	SELECT DISTINCT tyyppi
								FROM yhteensopivuus_tuote
								WHERE yhtio = '{$kukarow['yhtio']}'";
					$res_rekyht = pupe_query($query);

					if (mysql_num_rows($res_rekyht) > 0) {
						$tyyppi = 'auto';

						while ($tyyppi_row = mysql_fetch_assoc($res_rekyht)) {
							$tyyppilisa .= "'$tyyppi_row[tyyppi]',";
						}
					}
				}

				if ($tyyppi != '') {

					$tyyppilisa = substr($tyyppilisa, 0, -1);

					if ($vm1 != '' or $vm2 != '') {
						$joinlisa = " 	JOIN yhteensopivuus_$tyyppi ON (yhteensopivuus_$tyyppi.yhtio = yhteensopivuus_rekisteri.yhtio
										AND yhteensopivuus_$tyyppi.tunnus = yhteensopivuus_rekisteri.autoid ";
						if ($vm1 != '') {
							$vm1 = (int) $vm1;
							$joinlisa .= $tyyppi == 'auto' ? " AND yhteensopivuus_$tyyppi.alkuvuosi >= '$vm1' " : " AND yhteensopivuus_$tyyppi.vm >= '$vm1' ";
						}

						if ($vm2 != '') {
							$vm2 = (int) $vm2;
							$joinlisa .= $tyyppi == 'auto' ? " AND yhteensopivuus_$tyyppi.loppuvuosi <= '$vm2' " : " AND yhteensopivuus_$tyyppi.vm <= '$vm2' ";
						}

						$joinlisa .= ") ";
					}
				}
			}

			//Ajetaan raportti tuotteittain
			if ($paikoittain == '') {
				$query = "	SELECT
							tuote.yhtio,
							tuote.tuoteno,
							tuote.halytysraja,
							tuote.tahtituote,
							tuote.status,
							tuote.nimitys,
							tuote.kuvaus,
							tuote.myynti_era,
							tuote.myyntihinta,
							tuote.epakurantti25pvm,
							tuote.epakurantti50pvm,
							tuote.epakurantti75pvm,
							tuote.epakurantti100pvm,
							tuote.tuotemerkki,
							tuote.malli,
							tuote.mallitarkenne,
							tuote.osasto,
							tuote.try,
							tuote.aleryhma,
							tuote.kehahin,
							abc_aputaulu.luokka abcluokka,
							abc_aputaulu.luokka_osasto abcluokka_osasto,
							abc_aputaulu.luokka_try abcluokka_try,
							abc_aputaulu.luokka_tuotemerkki abcluokka_tuotemerkki,
							tuote.luontiaika,
							tuote.sarjanumeroseuranta,
							tuote.tuotekorkeus,
							tuote.tuoteleveys,
							tuote.tuotemassa,
							tuote.tuotesyvyys,
							tuote.eankoodi,
							tuote.lyhytkuvaus,
							tuote.hinnastoon,
							concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali,tuotepaikat.hyllytaso) varastopaikka
							FROM tuote
							LEFT JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno and tuotepaikat.oletus = 'X')
							LEFT JOIN korvaavat ON (tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno)
							LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio and abc_aputaulu.tuoteno = tuote.tuoteno and abc_aputaulu.tyyppi = '$abcrajaustapa')
							$lisa_parametri
							$lisaa2
							WHERE tuote.$yhtiot
							$lisa
							$lisaa
							$abcwhere
							and tuote.ei_saldoa = ''
							and tuote.tuotetyyppi NOT IN ('A', 'B')
							and tuote.ostoehdotus = ''
							ORDER BY id, tuote.tuoteno";
			}
			//Ajetaan raportti tuotteittain, varastopaikoittain
			else {
				$query = "	SELECT
							tuote.yhtio,
							tuote.tuoteno,
							tuotepaikat.halytysraja,
							tuote.tahtituote,
							tuote.status,
							tuote.nimitys,
							tuote.kuvaus,
							tuote.myynti_era,
							tuote.myyntihinta,
							tuote.epakurantti25pvm,
							tuote.epakurantti50pvm,
							tuote.epakurantti75pvm,
							tuote.epakurantti100pvm,
							tuote.tuotemerkki,
							tuote.malli,
							tuote.mallitarkenne,
							tuote.osasto,
							tuote.try,
							tuote.aleryhma,
							tuote.kehahin,
							abc_aputaulu.luokka abcluokka,
							abc_aputaulu.luokka_osasto abcluokka_osasto,
							abc_aputaulu.luokka_try abcluokka_try,
							abc_aputaulu.luokka_tuotemerkki abcluokka_tuotemerkki,
							tuote.luontiaika,
							tuote.sarjanumeroseuranta,
							tuote.tuotekorkeus,
							tuote.tuoteleveys,
							tuote.tuotemassa,
							tuote.tuotesyvyys,
							tuote.eankoodi,
							tuote.lyhytkuvaus,
							tuote.hinnastoon,
							concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali,tuotepaikat.hyllytaso) varastopaikka,
							varastopaikat.tunnus
							FROM tuote
							$lisaa2
							LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio and abc_aputaulu.tuoteno = tuote.tuoteno and abc_aputaulu.tyyppi = '$abcrajaustapa')
							JOIN tuotepaikat ON (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
							LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
							and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
							and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0')))
							LEFT JOIN korvaavat ON (tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno)
							$lisa_parametri
							WHERE tuote.$yhtiot
							$lisa
							$lisaa
							and tuote.ei_saldoa = ''
							and tuote.tuotetyyppi NOT IN ('A', 'B')
							and tuote.ostoehdotus = ''
							$abcwhere
							$varastot
							order by id, tuote.tuoteno, varastopaikka";
			}

			$res = pupe_query($query);

			//	Oletetaan että käyttäjä ei halyua/saa ostaa poistuvia tai poistettuja tuotteita!
			if (!isset($valitut["poistetut"])) $valitut["poistetut"] = "checked";
			if (!isset($valitut["poistuvat"])) $valitut["poistuvat"] = "checked";

			if ($valitut["poistetut"] != '' and $valitut["poistuvat"] == '') {
				echo "<font class='message'>".t("Vain aktiiviset tuotteet, poistuvat näytetään").".<br>";
			}
			if ($valitut["poistetut"] != '' and $valitut["poistuvat"] != '') {
				echo "<font class='message'>".t("Vain aktiiviset tuotteet").".<br>";
			}
			if ($valitut["poistetut"] == '' and $valitut["poistuvat"] != '') {
				echo "<font class='message'>".t("Vain aktiiviset tuotteet, poistetut näytetään").".<br>";
			}
			if ($valitut["OSTOTVARASTOITTAIN"] != '') {
				echo "<font class='message'>".t("Tilatut eritellään varastoittain").".<br>";
			}

			if ($abcrajaus != "") {

				echo "<font class='message'>".t("ABC-luokka tai ABC-osastoluokka tai ABC-tuoteryhmäluokka tai ABC-tuotemerkkiluokka")."  >= $ryhmanimet[$abcrajaus] ".t("tai sitä on jälkitoimituksessa")." ";

				if ($abc_laadittuaika == "alle_12kk") {
					echo t("ja tuote on perustettu 12kk sisällä");
				}
				elseif ($abc_laadittuaika == "yli_12kk") {
					echo t("ja tuotetta ei olla perustettu 12kk sisällä");
				}
				elseif ($abc_laadittuaika == "yli_annettu" and $naytauudet_pp != '' and $naytauudet_kk != '' and $naytauudet_vv != '') {
					echo t("tai tuote on perustettu jälkeen"). " $naytauudet_pp.$naytauudet_kk.$naytauudet_vv";
				}
				else {
					echo t("tai tuote on perustettu viimeisen 12kk sisällä");
				}

				echo ".<br>";
			}

			echo t("Tuotteita")." ".mysql_num_rows($res)." ".t("kpl").".<br>";

			if ($valitut["EHDOTETTAVAT"] != '') {
				echo "<font class='message'>".t("Joista jätetään pois ne tuotteet joita ei ehdoteta ostettavaksi").".<br>";
			}

			flush();

			//keksitään failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Sheet 1');

			$format_bold =& $workbook->addFormat();
			$format_bold->setBold();

			$format_center =& $workbook->addFormat();
			$format_center->setBold();
			$format_center->setHAlign('left');

			$workbook->setCustomColor(12, 255, 255, 0);
			$format_bg_yellow =& $workbook->addFormat();
			$format_bg_yellow->setFgColor(12);
			$format_bg_yellow->setPattern(1);

			$format_bg_yellow_text_red =& $workbook->addFormat();
			$format_bg_yellow_text_red->setFgColor(12);
			$workbook->setCustomColor(17, 255, 0, 0);
			$format_bg_yellow_text_red->setColor(17);
			$format_bg_yellow_text_red->setPattern(1);

			$workbook->setCustomColor(13, 200, 100, 180);
			$format_bg_magenta =& $workbook->addFormat();
			$format_bg_magenta->setFgColor(13);
			$format_bg_magenta->setPattern(1);

			$format_bg_magenta_text_red =& $workbook->addFormat();
			$format_bg_magenta_text_red->setFgColor(13);
			$format_bg_magenta_text_red->setColor(17);
			$format_bg_magenta_text_red->setPattern(1);

			$workbook->setCustomColor(14, 150, 255, 170);
			$format_bg_green =& $workbook->addFormat();
			$format_bg_green->setFgColor(14);
			$format_bg_green->setPattern(1);

			$workbook->setCustomColor(15, 255, 170, 70);
			$format_bg_brown =& $workbook->addFormat();
			$format_bg_brown->setFgColor(15);
			$format_bg_brown->setPattern(1);

			$workbook->setCustomColor(16, 200, 200, 200);
			$format_bg_grey =& $workbook->addFormat();
			$format_bg_grey->setFgColor(16);
			$format_bg_grey->setPattern(1);

			$workbook->setCustomColor(18, 255, 255, 255);
			$format_bg_text_red =& $workbook->addFormat();
			$format_bg_text_red->setFgColor(18);
			$format_bg_text_red->setColor(17);
			$format_bg_text_red->setPattern(1);

			$excelrivi 	 = 0;
			$excelsarake = 0;
			$siirtojt	 = '';

			$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("tuoteno")), $format_bold);
			$excelsarake++;

			if ($paikoittain != '') {
				$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("Varastopaikka")), $format_bold);
				$excelsarake++;
			}

			foreach ($valitut as $key => $val) {
				if ($sarakkeet[$key] != '') {
					if ($sarakkeet[$key] == 'Siirtojt') {
						$siirtojt = 0;
					}
					$worksheet->writeString($excelrivi, $excelsarake, $sarakkeet[$key], $format_bold);
					$excelsarake++;
				}
			}

			$varasto_ot = array();

			$excelrivi++;
			$korvaavien_otsikot_aloitus = $excelsarake;
			$korvaavien_otsikot = 0;
			$excelsarake = 0;

			$elements = mysql_num_rows($res); // total number of elements to process

			if ($elements > 0) {
				require_once ('inc/ProgressBar.class.php');
				$bar = new ProgressBar();
				$bar->initialize($elements); // print the empty bar
			}

			while ($row = mysql_fetch_array($res)) {

				$bar->increase();

				$ykpl1 = $ykpl2 = $ykpl3 = $ykpl4 = 0;

				if ($paikoittain != '') {
					$paikkalisa = " and concat_ws(' ',hyllyalue, hyllynro, hyllyvali, hyllytaso)='$row[varastopaikka]' ";
				}
				else {
					$paikkalisa = "";
				}

				//toimittajatiedot
				if ($toimittajaid == '') {
					$query = "	SELECT group_concat(tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '/') toimittaja,
								group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '/') osto_era,
								group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno,
								group_concat(distinct tuotteen_toimittajat.toim_nimitys order by tuotteen_toimittajat.tunnus separator '/') toim_nimitys,
								group_concat(distinct tuotteen_toimittajat.ostohinta order by tuotteen_toimittajat.tunnus separator '/') ostohinta,
								group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '/') tuotekerroin
								FROM tuotteen_toimittajat
								WHERE yhtio = '$row[yhtio]'
								and tuoteno = '$row[tuoteno]'";
				}
				else {
					$query = "	SELECT tuotteen_toimittajat.toimittaja,
								tuotteen_toimittajat.osto_era,
								tuotteen_toimittajat.toim_tuoteno,
								tuotteen_toimittajat.toim_nimitys,
								tuotteen_toimittajat.ostohinta,
								tuotteen_toimittajat.tuotekerroin
								FROM tuotteen_toimittajat
								WHERE yhtio = '$row[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and liitostunnus = '$toimittajaid'";
				}
				$result   = pupe_query($query);
				$toimirow = mysql_fetch_array($result);

				$row['toimittaja'] 		= $toimirow['toimittaja'];
				$row['osto_era'] 		= $toimirow['osto_era'];
				$row['toim_tuoteno'] 	= $toimirow['toim_tuoteno'];
				$row['toim_nimitys'] 	= $toimirow['toim_nimitys'];
				$row['ostohinta'] 		= $toimirow['ostohinta'];
				$row['tuotekerroin'] 	= $toimirow['tuotekerroin'];

				///* Myydyt kappaleet *///
				$query = "	SELECT
							# normimyynti
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) kpl1,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,kpl,0)) kpl2,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,kpl,0)) kpl3,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) kpl4,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva1ed-$kka1ed-$ppa1ed' and laskutettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed' ,kpl,0)) EDkpl1,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva2ed-$kka2ed-$ppa2ed' and laskutettuaika <= '$vvl2ed-$kkl2ed-$ppl2ed' ,kpl,0)) EDkpl2,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva3ed-$kka3ed-$ppa3ed' and laskutettuaika <= '$vvl3ed-$kkl3ed-$ppl3ed' ,kpl,0)) EDkpl3,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva4ed-$kka4ed-$ppa4ed' and laskutettuaika <= '$vvl4ed-$kkl4ed-$ppl4ed' ,kpl,0)) EDkpl4,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.kate,0)) kate1,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,tilausrivi.kate,0)) kate2,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,tilausrivi.kate,0)) kate3,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,tilausrivi.kate,0)) kate4,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,rivihinta,0)) rivihinta1,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,rivihinta,0)) rivihinta2,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,rivihinta,0)) rivihinta3,
							sum(if (lasku.clearing != 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,rivihinta,0)) rivihinta4,
							# ennakkomyynti
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) e_kpl1,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,kpl,0)) e_kpl2,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,kpl,0)) e_kpl3,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) e_kpl4,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva1ed-$kka1ed-$ppa1ed' and laskutettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed' ,kpl,0)) e_EDkpl1,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva2ed-$kka2ed-$ppa2ed' and laskutettuaika <= '$vvl2ed-$kkl2ed-$ppl2ed' ,kpl,0)) e_EDkpl2,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva3ed-$kka3ed-$ppa3ed' and laskutettuaika <= '$vvl3ed-$kkl3ed-$ppl3ed' ,kpl,0)) e_EDkpl3,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva4ed-$kka4ed-$ppa4ed' and laskutettuaika <= '$vvl4ed-$kkl4ed-$ppl4ed' ,kpl,0)) e_EDkpl4,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.kate,0)) e_kate1,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,tilausrivi.kate,0)) e_kate2,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,tilausrivi.kate,0)) e_kate3,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,tilausrivi.kate,0)) e_kate4,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,rivihinta,0)) e_rivihinta1,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,rivihinta,0)) e_rivihinta2,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,rivihinta,0)) e_rivihinta3,
							sum(if (lasku.clearing = 'ENNAKKOTILAUS' AND laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,rivihinta,0)) e_rivihinta4
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
							WHERE tilausrivi.yhtio = '$row[yhtio]'
							and tilausrivi.tyyppi	= 'L'
							and tilausrivi.tuoteno = '$row[tuoteno]'
							and tilausrivi.laskutettuaika >= '$apvm'
							and tilausrivi.laskutettuaika <= '$lpvm'
							$paikkalisa";
				$result   = pupe_query($query);
				$laskurow = mysql_fetch_array($result);

				$query = "	SELECT
							sum(if (laadittu >= '$vva1-$kka1-$ppa1' and laadittu <= '$vvl1-$kkl1-$ppl1' and var='P', tilkpl,0)) puutekpl1,
							sum(if (laadittu >= '$vva2-$kka2-$ppa2' and laadittu <= '$vvl2-$kkl2-$ppl2' and var='P', tilkpl,0)) puutekpl2,
							sum(if (laadittu >= '$vva3-$kka3-$ppa3' and laadittu <= '$vvl3-$kkl3-$ppl3' and var='P', tilkpl,0)) puutekpl3,
							sum(if (laadittu >= '$vva4-$kka4-$ppa4' and laadittu <= '$vvl4-$kkl4-$ppl4' and var='P', tilkpl,0)) puutekpl4
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laadittu)
							WHERE yhtio = '$row[yhtio]'
							and tyyppi='L'
							and tuoteno = '$row[tuoteno]'
							and laadittu >= '$apvm'
							and laadittu <= '$lpvm'
							$paikkalisa";
				$result   = pupe_query($query);
				$puuterow = mysql_fetch_array($result);

				// normimyynti
				if ($laskurow['rivihinta1'] <> 0){
					$katepros1 = round($laskurow['kate1'] / $laskurow['rivihinta1'] * 100,0);
				}
				else ($katepros1 = 0);

				if ($laskurow['rivihinta2'] <> 0){
					$katepros2 = round($laskurow['kate2'] / $laskurow['rivihinta2'] * 100,0);
				}
				else ($katepros2 = 0);

				if ($laskurow['rivihinta3'] <> 0){
					$katepros3 = round($laskurow['kate3'] / $laskurow['rivihinta3'] * 100,0);
				}
				else ($katepros3 = 0);

				if ($laskurow['rivihinta4'] <> 0){
						$katepros4 = round($laskurow['kate4'] / $laskurow['rivihinta4'] * 100,0);
				}
				else ($katepros4 = 0);

				// ennakkomyynti
				$katepros1_ennakko = $laskurow['e_rivihinta1'] <> 0 ? (round($laskurow['e_kate1'] / $laskurow['e_rivihinta1'] * 100,0)) : 0;
				$katepros2_ennakko = $laskurow['e_rivihinta2'] <> 0 ? (round($laskurow['e_kate2'] / $laskurow['e_rivihinta2'] * 100,0)) : 0;
				$katepros3_ennakko = $laskurow['e_rivihinta3'] <> 0 ? (round($laskurow['e_kate3'] / $laskurow['e_rivihinta3'] * 100,0)) : 0;
				$katepros4_ennakko = $laskurow['e_rivihinta4'] <> 0 ? (round($laskurow['e_kate4'] / $laskurow['e_rivihinta4'] * 100,0)) : 0;

				///* Kulutetut kappaleet *///
				$query = "	SELECT
							sum(if(toimitettuaika >= '$vva1-$kka1-$ppa1' and toimitettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) kpl1,
							sum(if(toimitettuaika >= '$vva2-$kka2-$ppa2' and toimitettuaika <= '$vvl2-$kkl2-$ppl2' ,kpl,0)) kpl2,
							sum(if(toimitettuaika >= '$vva3-$kka3-$ppa3' and toimitettuaika <= '$vvl3-$kkl3-$ppl3' ,kpl,0)) kpl3,
							sum(if(toimitettuaika >= '$vva4-$kka4-$ppa4' and toimitettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) kpl4,
							sum(if(toimitettuaika >= '$vva1ed-$kka1ed-$ppa1ed' and toimitettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed' ,kpl,0)) EDkpl1,
							sum(if(toimitettuaika >= '$vva2ed-$kka2ed-$ppa2ed' and toimitettuaika <= '$vvl2ed-$kkl2ed-$ppl2ed' ,kpl,0)) EDkpl2,
							sum(if(toimitettuaika >= '$vva3ed-$kka3ed-$ppa3ed' and toimitettuaika <= '$vvl3ed-$kkl3ed-$ppl3ed' ,kpl,0)) EDkpl3,
							sum(if(toimitettuaika >= '$vva4ed-$kka4ed-$ppa4ed' and toimitettuaika <= '$vvl4ed-$kkl4ed-$ppl4ed' ,kpl,0)) EDkpl4
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laadittu)
							WHERE yhtio = '$row[yhtio]'
							and tyyppi = 'V'
							and tuoteno = '$row[tuoteno]'
							and ((toimitettuaika >= '$apvm' and toimitettuaika <= '$lpvm') or toimitettuaika = '0000-00-00')
							$paikkalisa";
				$result   = pupe_query($query);
				$kulutrow = mysql_fetch_array($result);

				// Yhteensä kpl: myydyt normi ja ennakkorivit sekä kulutukset (lisätään laskurowiin helppouden vuoksi)
				$laskurow["ykpl1"] = $laskurow["kpl1"] + $laskurow["e_kpl1"] + $kulutrow["kpl1"];
				$laskurow["ykpl2"] = $laskurow["kpl2"] + $laskurow["e_kpl2"] + $kulutrow["kpl2"];
				$laskurow["ykpl3"] = $laskurow["kpl3"] + $laskurow["e_kpl3"] + $kulutrow["kpl3"];
				$laskurow["ykpl4"] = $laskurow["kpl4"] + $laskurow["e_kpl4"] + $kulutrow["kpl4"];

				//tilauksessa, ennakkopoistot ja jt	HUOM: varastolisa määritelty jo aiemmin!
				$query = "	SELECT
							sum(if(tyyppi in ('W','M'), varattu, 0)) valmistuksessa,
							sum(if(tyyppi = 'O', varattu, 0)) tilattu,
							sum(if(tyyppi = 'E', varattu, 0)) ennakot, # toimittamattomat ennakot
							sum(if(tyyppi in ('L','V') and var not in ('P','J','S'), varattu, 0)) ennpois, # saldon ennakkopoistoja
							sum(if(tyyppi = 'L' and var in ('J','S'), jt $lisavarattu, 0)) jt
							$varastolisa
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							WHERE yhtio = '$row[yhtio]'
		 					and tyyppi in ('L','V','O','E','W','M')
							and tuoteno = '$row[tuoteno]'
							and laskutettuaika = '0000-00-00'
							and (varattu+jt > 0)";
				$result = pupe_query($query);
				$ennp   = mysql_fetch_assoc($result);

				$query = "	SELECT sum(if(tyyppi = 'G', jt $lisavarattu, 0)) siirtojt
							FROM tilausrivi
							WHERE yhtio = '$row[yhtio]'
							and tyyppi in ('O','G')
							and tuoteno = '$row[tuoteno]'
							and varattu + jt > 0";
				$result = pupe_query($query);
				$siirtojtrow = mysql_fetch_array($result);

				if ($paikoittain == '' and $varastot_yhtiot != '') {
					// Kaikkien valittujen varastojen paikkojen saldo yhteensä, mukaan tulee myös aina ne saldot jotka ei kuulu mihinkään varastoalueeseen
					$query = "	SELECT sum(saldo) saldo, varastopaikat.tunnus
								FROM tuotepaikat
								LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
								and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
								WHERE tuotepaikat.$varastot_yhtiot
								and tuotepaikat.tuoteno='$row[tuoteno]'
								GROUP BY varastopaikat.tunnus
								$varastot";
					$result = pupe_query($query);

					$sumsaldo = 0;

					while($saldo = mysql_fetch_array($result)) {
						$sumsaldo += $saldo["saldo"];
					}

					$saldo["saldo"] = $sumsaldo;

					if ($varastot2 != '') {
						$query = "	SELECT sum(saldo) saldo, varastopaikat.tunnus
									FROM tuotepaikat
									LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
									and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
									and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
									WHERE tuotepaikat.$varastot_yhtiot
									and tuotepaikat.tuoteno='$row[tuoteno]'
									GROUP BY varastopaikat.tunnus
									$varastot2";
						$result2 = pupe_query($query);

						$sumsaldo2 = 0;

						while($saldo2 = mysql_fetch_array($result2)) {
							$sumsaldo2 += $saldo2["saldo"];
						}

						$saldo["saldo2"] = $sumsaldo2;
					}

				}
				else {
					// Ajetaan varastopaikoittain eli tässä on just tän paikan saldo
					$query = "	SELECT saldo
								from tuotepaikat
								where yhtio='$row[yhtio]'
								and tuoteno='$row[tuoteno]'
								$paikkalisa";
					$result = pupe_query($query);
					$saldo = mysql_fetch_array($result);
				}

				// oletuspaikan saldo ja hyllypaikka
				$query = "	SELECT sum(saldo) osaldo, hyllyalue, hyllynro, hyllyvali, hyllytaso
							from tuotepaikat
							where yhtio='$row[yhtio]'
							and tuoteno='$row[tuoteno]'
							and oletus='X'
							group by hyllyalue, hyllynro, hyllyvali, hyllytaso";
				$result = pupe_query($query);
				$osaldo = mysql_fetch_array($result);

				if ($row['osto_era'] == 0) {
					$row['osto_era'] = 1;
				}

				///* lasketaan ehdotelma, mitä kannattaisi tilata *///

				$ostettava_kausi1 = 0;
				$ostettava_kausi2 = 0;
				$ostettava_kausi3 = 0;
				$ostettava_kausi4 = 0;
				$ostettava_era1 = 0;
				$ostettava_era2 = 0;
				$ostettava_era3 = 0;
				$ostettava_era4 = 0;
				$ostettavahaly_kausi1 = 0;
				$ostettavahaly_kausi2 = 0;
				$ostettavahaly_kausi3 = 0;
				$ostettavahaly_kausi4 = 0;

				for ($i = 1; $i < 5; $i++) {
					$indeksi = "kpl".$i;

					//Kausien ero sekunneissa
					$ero = strtotime(${"vvl".$i}."-".${"kkl".$i}."-".${"ppl".$i}) - strtotime(${"vva".$i}."-".${"kka".$i}."-".${"ppa".$i});

					//Kausien ero viikoissa
					$ero = $ero/60/60/24/7;
					$ehd_kausi1	= "1";

					if (isset($row["abcluokka"]) and
						isset($ryhmanimet[$row["abcluokka"]]) and
						isset($valitut["KAUSI".$ryhmanimet[$row["abcluokka"]]]) and
						$valitut["KAUSI".$ryhmanimet[$row["abcluokka"]]] != '') {
						list($al, $lo) = explode("##", $valitut["KAUSI".$ryhmanimet[$row["abcluokka"]]]);
						$ehd_kausi1	= $lo;
					}

					// ((kulutus + myynti + varatut + jt + siirtojt) / valitun_kauden_pituus_viikoissa) * varastointitarve_viikoissa - (saldo + tilattu + valmistuksessa + ennpois + jt + siirtojt)
					// echo "$row[tuoteno]: "(({$kulutrow[$indeksi]} + {$laskurow[$indeksi]} + {$ennp['ennpois']} + {$ennp['jt']} + {$siirtojtrow['siirtojt']}) / $ero) * $ehd_kausi1 - ({$saldo['saldo']} + {$ennp['tilattu']} + {$ennp['valmistuksessa']} + {$ennp['ennpois']} + {$ennp['jt']} + {$siirtojtrow['siirtojt']});<br>";

					${"ostettava_kausi".$i} = (($kulutrow[$indeksi] + $laskurow[$indeksi] + $ennp['ennpois'] + $ennp['jt'] + $siirtojtrow['siirtojt']) / $ero) * $ehd_kausi1 - ($saldo['saldo'] + $ennp['tilattu'] + $ennp['valmistuksessa'] + $ennp['ennpois'] + $ennp['jt'] + $siirtojtrow['siirtojt']);
					${"ostettavahaly_kausi".$i} = ($row['halytysraja'] - ($saldo['saldo'] + $ennp['tilattu'] + $ennp['valmistuksessa'] + $ennp['ennpois'] + $ennp['jt']));

					// jos tuotteella on joku ostoerä pyöristellään ylospäin, että tilataan aina toimittajan haluama määrä
					if (${"ostettava_kausi".$i} != '') {
						${"ostettava_era".$i} = ceil(${"ostettava_kausi".$i} / $row['osto_era']) * $row['osto_era'];

						${"ostettava_kausi".$i} = ceil(${"ostettava_kausi".$i});

						if (${"ostettava_era".$i} < 0) {
							${"ostettava_era".$i} = 0;
						}
					}
					else {
						${"ostettava_kausi".$i} = ${"ostettava_era".$i} = 0;
					}

					if (${"ostettavahaly_kausi".$i} != '' and ${"ostettavahaly_kausi".$i} > 0 and $row['halytysraja'] > 0) {
						${"ostettavahaly_kausi".$i} = ceil(${"ostettavahaly_kausi".$i});
					}
					else {
						${"ostettavahaly_kausi".$i} = 0;
					}
				}

				//ennakkotilauksessa
				$query  = "	SELECT sum(tilkpl) tilkpl
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							WHERE yhtio='$row[yhtio]'
							and tyyppi='E'
							and tuoteno='$row[tuoteno]'
							and laskutettuaika = '0000-00-00'";
				$ennaresult = pupe_query($query);
				$ennarow = mysql_fetch_array($ennaresult);

				//asiakkaan ostot
				if ($asiakasosasto != '') {
					$query  = "	SELECT
								sum(if (t.laskutettuaika >= '$vva1-$kka1-$ppa1' and t.laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,t.kpl,0)) kpl1,
								sum(if (t.laskutettuaika >= '$vva2-$kka2-$ppa2' and t.laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,t.kpl,0)) kpl2,
								sum(if (t.laskutettuaika >= '$vva3-$kka3-$ppa3' and t.laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,t.kpl,0)) kpl3,
								sum(if (t.laskutettuaika >= '$vva4-$kka4-$ppa4' and t.laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,t.kpl,0)) kpl4
								FROM tilausrivi t use index (yhtio_tyyppi_tuoteno_laskutettuaika),
								lasku l use index(PRIMARY),
								asiakas a use index (ytunnus_index)
								WHERE t.yhtio = '$row[yhtio]'
								and t.tyyppi = 'L'
								and t.tuoteno = '$row[tuoteno]'
								and t.laskutettuaika >= '$apvm'
								and t.laskutettuaika <= '$lpvm'
								and l.yhtio = t.yhtio
								and l.tunnus = t.uusiotunnus
								and a.ytunnus = l.ytunnus
								and a.yhtio = l.yhtio
								and a.osasto = '$asiakasosasto'";
					$asosresult = pupe_query($query);
					$asosrow = mysql_fetch_array($asosresult);
				}

				if ($asiakasid != '') {
					$query  = "	SELECT sum(if (t.laskutettuaika >= '$vva1-$kka1-$ppa1' and t.laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,t.kpl,0)) kpl1,
								sum(if (t.laskutettuaika >= '$vva2-$kka2-$ppa2' and t.laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,t.kpl,0)) kpl2,
								sum(if (t.laskutettuaika >= '$vva3-$kka3-$ppa3' and t.laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,t.kpl,0)) kpl3,
								sum(if (t.laskutettuaika >= '$vva4-$kka4-$ppa4' and t.laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,t.kpl,0)) kpl4
								FROM tilausrivi t use index (yhtio_tyyppi_tuoteno_laskutettuaika),
								lasku l use index(PRIMARY)
								WHERE t.yhtio = '$row[yhtio]'
								and t.tyyppi = 'L'
								and t.tuoteno = '$row[tuoteno]'
								and t.laskutettuaika >= '$apvm'
								and t.laskutettuaika <= '$lpvm'
								and l.yhtio = t.yhtio
								and l.tunnus = t.otunnus
								and l.liitostunnus 	= '$asiakasid'";
					$asresult = pupe_query($query);
					$asrow = mysql_fetch_array($asresult);
				}

				if (!isset($valitut['EHDOTETTAVAT']) or $valitut['EHDOTETTAVAT'] == '' or ($ostettavahaly_kausi1 > 0 or $ostettavahaly_kausi2 > 0 or $ostettavahaly_kausi3 > 0 or $ostettavahaly_kausi4 > 0) or ($ostettava_kausi1 > 0 or $ostettava_kausi2 > 0 or $ostettava_kausi3 > 0 or $ostettava_kausi4 > 0)) {

					$worksheet->writeString($excelrivi, $excelsarake, $row["tuoteno"], $format_center);
					$excelsarake++;

					if ($paikoittain != '') {
						$worksheet->writeString($excelrivi, $excelsarake, $row["varastopaikka"]);
						$excelsarake++;
					}

					$value_1kk = '';
					$value_3kk = '';
					$value_6kk = '';
					$value_12kk = '';

					// tähän kerätään sarakkeiden koordinaatteja, jos pitää päivittää jälkikäteen jotain tietuetta
					$column_location = array();
					$kosal_yht = 0;
					$komy_yht = 0;
					$korvaavat_kayty = '';

					foreach($valitut as $key => $sarake) {
						$bg_color = '';
						$sarake = trim($sarake);

						if (isset($sarake_keyt[$sarake]) and $sarake_keyt[$sarake] != '') {

							$value = '';

							// jos sarake on abc (abcluokkanumero), haetaan se kauniimpi nimi (esim. A-30)
							if ($sarake == 'abc' or $sarake == 'abc os' or $sarake == 'abc try' or $sarake == 'abc tme') {
								$value = isset($ryhmanimet[$row[$sarake_keyt[$sarake]]]) ? $ryhmanimet[$row[$sarake_keyt[$sarake]]] : "";
							}
							// jos sarake on saldo, haetaan saldo toisesta muuttujasta
							elseif ($sarake == 'saldo' or $sarake == 'saldo2') {
								$value = isset($saldo[$sarake_keyt[$sarake]]) ? $saldo[$sarake_keyt[$sarake]] : "";
							}
							elseif ($sarake == 'reaalisaldo') {
								$value = $saldo['saldo'] + $ennp['tilattu'] + $ennp['valmistuksessa'] - $ennp['ennpois'] - $ennp['jt'];
							}
							elseif ($sarake == 'osaldo') {
								$value = round($osaldo[$sarake_keyt[$sarake]], 2);
							}
							elseif ($sarake == 'ostohinta' or $sarake == 'myyntihinta') {
								$value = round($row[$sarake_keyt[$sarake]], 2);
							}
							// jos sarake on tilattu, ennakko tai jt, haetaan ne toisesta muuttujasta
							elseif ($sarake == 'til' or $sarake == 'valmistuksessa' or $sarake == 'ennpois' or $sarake == 'jt' or $sarake == 'ennakot') {
								$value = $ennp[$sarake_keyt[$sarake]];
							}
							elseif ($sarake == 'siirtojt') {
								$value = $siirtojtrow[$sarake_keyt[$sarake]];
							}
							// tässä keississä otetaan arvot omista muuttujistaan
							elseif ($sarake == 'ostoera1' or $sarake == 'ostoera3' or $sarake == 'ostoera6' or $sarake == 'ostoera12' or
									$sarake == '1kk' or $sarake == '3kk' or $sarake == '6kk' or $sarake == '12kk' or
									$sarake == 'osthaly1' or $sarake == 'osthaly3' or $sarake == 'osthaly6' or $sarake == 'osthaly12' or
									$sarake == 'Kate % 1' or $sarake == 'Kate % 3' or $sarake == 'Kate % 6' or $sarake == 'Kate % 12' or
									$sarake == 'e_kate % 1' or $sarake == 'e_kate % 3' or $sarake == 'e_kate % 6' or $sarake == 'e_kate % 12') {

								$value = ${$sarake_keyt[$sarake]};

								if ($sarake == 'Kate % 1' or $sarake == 'Kate % 3' or $sarake == 'Kate % 6' or $sarake == 'Kate % 12') {
									$bg_color = $value < 0 ? 'text_red' : '';
								}

								if ($sarake == '1kk' or $sarake == '3kk' or $sarake == '6kk' or $sarake == '12kk') {
									${"value_".$sarake} = $value;
									$bg_color = $value >= 0 ? 'yellow' : 'yellow_text_red';
								}
							}
							// tarvitaan oma taustaväri (sa + ti sarakkeet yhteensä)
							elseif ($sarake == 'kosal') {
								$column_location['kosal']['rivi'] = $excelrivi;
								$column_location['kosal']['sarake'] = $excelsarake;
							}
							// keskiarvo
							elseif ($sarake == 'ke') {
								$value = round(($value_1kk + $value_3kk + $value_6kk + $value_12kk) / 4);
								$bg_color = $value < 0 ? 'text_red' : '';
							}
							// myydyt kappaleet yhteensä
							elseif ($sarake == 'komy') {
								$column_location['komy']['rivi'] = $excelrivi;
								$column_location['komy']['sarake'] = $excelsarake;
							}
							// nimitys pitää käyttää avainsanafunkkarin kautta
							elseif ($sarake == 'nimitys') {
								$value = t_tuotteen_avainsanat($row, 'nimitys');
							}
							elseif ($sarake == 'epa25pvm' or $sarake == 'epa50pvm' or $sarake == 'epa75pvm' or $sarake == 'epa100pvm' or $sarake == 'luontiaika') {
								$value = tv1dateconv($row[$sarake_keyt[$sarake]]);
							}
							// puute 1 tarvitaan oma taustaväri, muuten haetaan tiedot puuterow-muuttujasta
							elseif ($sarake == 'pu1' or $sarake == 'pu3' or $sarake == 'pu6' or $sarake == 'pu12') {
								if ($sarake == 'pu1') {
									$bg_color = 'brown';
								}

								$value = $puuterow[$sarake_keyt[$sarake]];
							}
							// myynneissä haetaan kaikki laskurowsta
							elseif ($sarake == 'my1' or $sarake == 'my3' or $sarake == 'my6' or $sarake == 'my12' or
									$sarake == 'ed1' or $sarake == 'ed3' or $sarake == 'ed6' or $sarake == 'ed12' or
									$sarake == 'enn1' or $sarake == 'enn3' or $sarake == 'enn6' or $sarake == 'enn12' or
									$sarake == 'kate1' or $sarake == 'kate3' or $sarake == 'kate6' or $sarake == 'kate12' or
									$sarake == 'e_kate1' or $sarake == 'e_kate3' or $sarake == 'e_kate6' or $sarake == 'e_kate12' or
									$sarake == 'ykpl1' or $sarake == 'ykpl3' or $sarake == 'ykpl6' or $sarake == 'ykpl12') {
								$value = $laskurow[$sarake_keyt[$sarake]];
								if ($sarake == 'my3' or $sarake == 'my12') {
									$bg_color = $value >= 0 ? $bg_color = 'yellow' : $bg_color = 'yellow_text_red';
								}
							}
							elseif ($sarake == 'kul1' or $sarake == 'kul3' or $sarake == 'kul6' or $sarake == 'kul12' or
									$sarake == 'edkul1' or $sarake == 'edkul3' or $sarake == 'edkul6' or $sarake == 'edkul12') {
								$value = $kulutrow[$sarake_keyt[$sarake]];
							}
							// defaulttina haetaan normaalista rowsta tiedot
							elseif ($sarake == 'Määrä') {
								//korvaavat tuotteet
								$query  = "	SELECT id
											FROM korvaavat
											WHERE tuoteno	= '$row[tuoteno]'
											and yhtio		= '$row[yhtio]'";
								$korvaresult1 = pupe_query($query);

								$korvaavat_tunrot = '';

								if (mysql_num_rows($korvaresult1) > 0) {
									$korvarow = mysql_fetch_array($korvaresult1);

									$query  = "	SELECT tuoteno
												FROM korvaavat
												WHERE tuoteno  != '$row[tuoteno]'
												and id			= '$korvarow[id]'
												and yhtio		= '$row[yhtio]'";
									$korvaresult2 = pupe_query($query);

									if (mysql_num_rows($korvaresult2) > 0) {

										$i = 0;

										//tulostetaan korvaavat
										while ($korvarow = mysql_fetch_array($korvaresult2)) {
											$korvaavat_tunrot .= "'$korvarow[tuoteno]', ";
										}
									}
								}

								$korvaavat_tunrot = substr($korvaavat_tunrot, 0, -2);

								$tuotteet = $korvaavat_tunrot != '' ? ("'".$row['tuoteno']."', ".$korvaavat_tunrot) : "'".$row['tuoteno']."'";

								if ($tyyppilisa != "") {
									$query = "	SELECT count(*) kpl
												FROM yhteensopivuus_tuote
												JOIN yhteensopivuus_rekisteri ON (yhteensopivuus_rekisteri.yhtio = yhteensopivuus_tuote.yhtio AND yhteensopivuus_rekisteri.autoid = yhteensopivuus_tuote.atunnus)
												$joinlisa
												WHERE yhteensopivuus_tuote.yhtio = '$kukarow[yhtio]'
												AND yhteensopivuus_tuote.tuoteno IN ($tuotteet)
												AND yhteensopivuus_tuote.tyyppi IN ($tyyppilisa)";
									$asresult = pupe_query($query);
									$kasrow = mysql_fetch_array($asresult);
									$rek_kpl_maara = round($kasrow['kpl'], 2);
								}
								else {
									$rek_kpl_maara = "";
								}

								$worksheet->write($excelrivi, $excelsarake, $rek_kpl_maara, $format_bg_green);
								$value = '';
							}
							elseif ($sarake == 'Kortuoteno' or $sarake == 'Korsaldo' or $sarake == 'Korennpois' or $sarake == 'Kortil' or $sarake == 'Kormy1' or $sarake == 'Kormy2' or $sarake == 'Kormy3' or $sarake == 'Kormy4') {

								if ($korvaavat_kayty != '') {
									continue;
								}
								else {
									$korvaavat_kayty = 'joo';
								}

								// Korvaavat
								unset($korvaresult1);
								unset($korvaresult2);

								//korvaavat tuotteet
								$query  = "	SELECT id
											FROM korvaavat
											WHERE tuoteno	= '$row[tuoteno]'
											and yhtio		= '$row[yhtio]'";
								$korvaresult1 = pupe_query($query);

								if (mysql_num_rows($korvaresult1) > 0) {
									$korvarow = mysql_fetch_array($korvaresult1);

									$query  = "	SELECT tuoteno
												FROM korvaavat
												WHERE tuoteno  != '$row[tuoteno]'
												and id			= '$korvarow[id]'
												and yhtio		= '$row[yhtio]'";
									$korvaresult2 = pupe_query($query);

									if (mysql_num_rows($korvaresult2) > 0) {

										$i = 0;

										//tulostetaan korvaavat
										while ($korvarow = mysql_fetch_array($korvaresult2)) {

											//Korvaavien myynnnit
											$query  = "	SELECT
														sum(if (laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) kpl1,
														sum(if (laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,kpl,0)) kpl2,
														sum(if (laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,kpl,0)) kpl3,
														sum(if (laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) kpl4
														FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
														WHERE yhtio = '$row[yhtio]'
														and tyyppi = 'L'
														and tuoteno in ('$korvarow[tuoteno]')
														and laskutettuaika >= '$apvm'
														and laskutettuaika <= '$lpvm'";
											$asresult = pupe_query($query);
											$kasrow = mysql_fetch_array($asresult);

											$query = "	SELECT sum(saldo) saldo
														FROM tuotepaikat
														JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
														and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
														and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0')))
														WHERE tuotepaikat.$varastot_yhtiot
														and tuotepaikat.tuoteno in ('$korvarow[tuoteno]')
														$varastolisa_korv";
											$korvasaldoresult = pupe_query($query);
											$korvasaldorow = mysql_fetch_array($korvasaldoresult);

											// Korvaavan tuotteen varatut ja tilatut
											$query = "	SELECT
														sum(if(tilausrivi.tyyppi in ('O','W','M'), varattu, 0)) tilattu,
														sum(if(tilausrivi.tyyppi in ('L','V'), varattu, 0)) varattu
														FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
														JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
														and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))
														and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0')))
														WHERE tilausrivi.yhtio = '$row[yhtio]'
														and tilausrivi.tyyppi in ('L','V','O','W','M')
														and tilausrivi.tuoteno in ('$korvarow[tuoteno]')
														and tilausrivi.varattu > 0";
											$presult = pupe_query($query);
											$prow = mysql_fetch_array($presult);

											foreach ($valitut as $key => $val) {
												if (isset($sarakkeet[$key]) and $sarakkeet[$key] != '') {
													if ($sarakkeet[$key] == 'Kortuoteno') {
														$worksheet->write($excelrivi, $excelsarake, str_replace("'", "", $korvarow['tuoteno']));
														$excelsarake++;
													}
													elseif ($sarakkeet[$key] == 'Korsaldo') {
														$worksheet->writeNumber($excelrivi, $excelsarake, round($korvasaldorow['saldo'], 2));
														$excelsarake++;
													}
													elseif ($sarakkeet[$key] == 'Korennpois') {
														$worksheet->writeNumber($excelrivi, $excelsarake, round($prow['varattu'], 2));
														$excelsarake++;
													}
													elseif ($sarakkeet[$key] == 'Kortil') {
														$worksheet->writeNumber($excelrivi, $excelsarake, round($prow['tilattu'], 2));
														$excelsarake++;
													}
													elseif ($sarakkeet[$key] == 'Kormy1') {
														$worksheet->writeNumber($excelrivi, $excelsarake, round($kasrow['kpl1'], 2));
														$excelsarake++;
													}
													elseif ($sarakkeet[$key] == 'Kormy2') {
														$worksheet->writeNumber($excelrivi, $excelsarake, round($kasrow['kpl2'], 2));
														$excelsarake++;
													}
													elseif ($sarakkeet[$key] == 'Kormy3') {
														$worksheet->writeNumber($excelrivi, $excelsarake, round($kasrow['kpl3'], 2));
														$excelsarake++;
													}
													elseif ($sarakkeet[$key] == 'Kormy4') {
														$worksheet->writeNumber($excelrivi, $excelsarake, round($kasrow['kpl4'], 2));
														$excelsarake++;
													}
												}
											}

											$kosal_yht += $korvasaldorow['saldo'];
											$kosal_yht += $prow['tilattu'];
											$komy_yht += $kasrow['kpl4'];
											$i++;
										}

										$korvaavien_otsikot = $i > $korvaavien_otsikot ? $i : $korvaavien_otsikot;

										$bg_color = $kosal_yht >= 0 ? 'yellow' : 'yellow_text_red';

										$korvaavien_rivi = (int) $column_location['kosal']['rivi'];
										$korvaavien_sarake = (int) $column_location['kosal']['sarake'];

										$worksheet->write($korvaavien_rivi, $korvaavien_sarake, round($kosal_yht, 2), ${"format_bg_".$bg_color});
										$kosal_yht = 0;

										$bg_color = $kosal_yht >= 0 ? 'magenta' : 'magenta_text_red';

										$korvaavien_rivi = (int) $column_location['komy']['rivi'];
										$korvaavien_sarake = (int) $column_location['komy']['sarake'];

										$worksheet->write($korvaavien_rivi, $korvaavien_sarake, round($komy_yht, 2), ${"format_bg_".$bg_color});
										$komy_yht = 0;

										$bg_color = '';
									}
									else {
										// jos tänne tullaan niin kosal-sarake pitää värjätä tyhjänä keltaiseksi
										if (count($column_location) > 0) {
											$korvaavien_rivi = (int) $column_location['kosal']['rivi'];
											$korvaavien_sarake = (int) $column_location['kosal']['sarake'];

											$worksheet->write($korvaavien_rivi, $korvaavien_sarake, '', $format_bg_yellow);

											$korvaavien_rivi = (int) $column_location['komy']['rivi'];
											$korvaavien_sarake = (int) $column_location['komy']['sarake'];

											$worksheet->write($korvaavien_rivi, $korvaavien_sarake, '', $format_bg_magenta);
										}
									}
								}
								else {
									// jos tänne tullaan niin kosal-sarake pitää värjätä tyhjänä keltaiseksi
									if (count($column_location) > 0) {
										$korvaavien_rivi = (int) $column_location['kosal']['rivi'];
										$korvaavien_sarake = (int) $column_location['kosal']['sarake'];

										$worksheet->write($korvaavien_rivi, $korvaavien_sarake, '', $format_bg_yellow);

										$korvaavien_rivi = (int) $column_location['komy']['rivi'];
										$korvaavien_sarake = (int) $column_location['komy']['sarake'];

										$worksheet->write($korvaavien_rivi, $korvaavien_sarake, '', $format_bg_magenta);
									}
								}
							}
							else {
								$value = $row[$sarake_keyt[$sarake]];
							}

							$value = trim($value);

							if ($value != '') {
								// katsotaan onko arvo numerollinen excel writerin takia (eri funkkarit)
								// Ean on numeerinen, mutta excel rikkoo sen koska on niin pitkä.
								if (is_numeric($value) and $sarakkeet[$key] != 'eankoodi') {
									if ($bg_color != '') {
										$worksheet->writeNumber($excelrivi, $excelsarake, round($value, 2), ${"format_bg_".$bg_color});
									}
									else {
										$worksheet->writeNumber($excelrivi, $excelsarake, round($value, 2));
									}
								}
								else {
									if ($bg_color != '') {
										$worksheet->writeString($excelrivi, $excelsarake, $value, ${"format_bg_".$bg_color});
									}
									else {
										$worksheet->writeString($excelrivi, $excelsarake, $value);
									}
								}
							}

							// siirretään saraketta eteenpäin
							$excelsarake++;
						}
					}

					$excelrivi++;
					$excelsarake = 0;
				}
			}

			if ($korvaavien_otsikot > 0) {
				for ($i = 1; $korvaavien_otsikot > $i; $i++) {
					foreach ($valitut as $key => $val) {
						if ($sarakkeet[$key] != '') {
							if ($sarakkeet[$key] == 'Kortuoteno') {
								$worksheet->write(0, $korvaavien_otsikot_aloitus, "Kortuoteno", $format_bold);
								$korvaavien_otsikot_aloitus++;
							}
							elseif ($sarakkeet[$key] == 'Korsaldo') {
								$worksheet->writeString(0, $korvaavien_otsikot_aloitus, "Korsaldo", $format_bold);
								$korvaavien_otsikot_aloitus++;
							}
							elseif ($sarakkeet[$key] == 'Korennpois') {
								$worksheet->writeString(0, $korvaavien_otsikot_aloitus, "Korennpois", $format_bold);
								$korvaavien_otsikot_aloitus++;
							}
							elseif ($sarakkeet[$key] == 'Kortil') {
								$worksheet->writeString(0, $korvaavien_otsikot_aloitus, "Kortil", $format_bold);
								$korvaavien_otsikot_aloitus++;
							}
							elseif ($sarakkeet[$key] == 'Kormy1') {
								$worksheet->writeString(0, $korvaavien_otsikot_aloitus, "Kormy1", $format_bold);
								$korvaavien_otsikot_aloitus++;
							}
							elseif ($sarakkeet[$key] == 'Kormy2') {
								$worksheet->writeString(0, $korvaavien_otsikot_aloitus, "Kormy2", $format_bold);
								$korvaavien_otsikot_aloitus++;
							}
							elseif ($sarakkeet[$key] == 'Kormy3') {
								$worksheet->writeString(0, $korvaavien_otsikot_aloitus, "Kormy3", $format_bold);
								$korvaavien_otsikot_aloitus++;
							}
							elseif ($sarakkeet[$key] == 'Kormy4') {
								$worksheet->writeString(0, $korvaavien_otsikot_aloitus, "Kormy4", $format_bold);
								$korvaavien_otsikot_aloitus++;
							}
						}
					}
				}
			}

			flush();
			echo "<br><br>";

			$workbook->close();

			echo "<table>";
			echo "<tr><th>".t("Tallenna raportti (xls)").":</th>";
			echo "<form method='post' class='multisubmit'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Ostoraportti.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";

			$osasto		= '';
			$tuoryh		= '';
			$ytunnus	= '';
			$tuotemerkki= '';
			$tee		= 'X';
		}

		if (($tee == "" and isset($jatkanappi)) or $tee == "JATKA") {

			if (isset($muutparametrit)) {
				list($mul_osasto2,$mul_try2,$ytunnus,$mul_tme2,$asiakasosasto,$asiakasno,$toimittaja,$abcrajaus) = explode('#', $muutparametrit);

				$mul_osasto = explode(",", $mul_osasto2);
				$mul_try = explode(",", $mul_try2);
				$mul_tme = explode(",", $mul_tme2);
			}

			if (is_array($mul_osasto) and count($mul_osasto) > 0) {
				$mul_osasto2 = '';
				foreach ($mul_osasto as $os) {
					$mul_osasto2 .= "$os,";
				}
				$mul_osasto2 = substr($mul_osasto2, 0, -1);
			}

			if (is_array($mul_try) and count($mul_try) > 0) {
				$mul_try2 = '';
				foreach ($mul_try as $tr) {
					$mul_try2 .= "$tr,";
				}
				$mul_try2 = substr($mul_try2, 0, -1);
			}

			if (is_array($mul_tme) and count($mul_tme) > 0) {
				$mul_tme2 = '';
				foreach ($mul_tme as $tm) {
					$mul_tme2 .= "$tm,";
				}
				$mul_tme2 = substr($mul_tme2, 0, -1);
			}

			$muutparametrit = $mul_osasto2."#".$mul_try2."#".$ytunnus."#".$mul_tme2."#".$asiakasosasto."#".$asiakasno."#".$abcrajaus."#";

			if (count($mul_try) > 0 or count($mul_osasto) > 0 or $ytunnus != '' or count($mul_tme) > 0) {
				if ($ytunnus != '' and !isset($ylatila)) {

					require("../inc/kevyt_toimittajahaku.inc");

					if ($ytunnus != '') {
						$tee = "JATKA";
					}
				}
				elseif ($ytunnus != '' and isset($ylatila)) {
					$tee = "JATKA";
				}
				elseif (count($mul_try) > 0 or count($mul_osasto) > 0 or count($mul_tme) > 0) {
					$tee = "JATKA";
				}
				else {
					$tee = "";
				}
			}

			$muutparametrit = $mul_osasto2."#".$mul_try2."#".$ytunnus."#".$mul_tme2."#".$asiakasosasto."#".$asiakasno."#".$abcrajaus."#";

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

		if ($tee == "") {

			echo "	<form method='post' autocomplete='off'>
					<input type='hidden' name='tee' id='tee' value=''>
					<input type='hidden' name='toimittajaid' value='$toimittajaid'>
					<br/>",t("Valitse vähintään yksi seuraavista:"),"<br/>";

			// Monivalintalaatikot (osasto, try tuotemerkki...)
			// Määritellään mitkä latikot halutaan mukaan
			$lisa  = "";
			$ulisa = "";

			// selite 		= käytetäänkö uutta vai vanhaa ulkoasua
			// selitetark 	= näytettävät monivalintalaatikot, jos tyhjää, otetaan oletus alhaalla
			// selitetark_2 = mitkä näytettävistä monivalintalaatikoista on normaaleja alasvetovalikoita
			$query = "	SELECT selite, selitetark, REPLACE(selitetark_2, ', ', ',') selitetark_2
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						AND laji = 'HAE_JA_SELAA'
						AND selite != ''";
			$hs_result = pupe_query($query);
			$hs_row = mysql_fetch_assoc($hs_result);

			if (trim($hs_row['selitetark']) != '') {
				$monivalintalaatikot = explode(",", $hs_row['selitetark']);

				if (trim($hs_row['selitetark_2'] != '')) {
					$monivalintalaatikot_normaali = explode(",", $hs_row['selitetark_2']);
				}
				else {
					$monivalintalaatikot_normaali = array();
				}
			}
			else {
				// Oletus
				$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "<br>MALLI/MALLITARK");
				$monivalintalaatikot_normaali = array();
			}

			require ("tilauskasittely/monivalintalaatikot.inc");

			// Otetaan monivalintalaatikoista palautuvat parametrit talteen ja laitetaan isoihin kyselyihin
			echo "	<input type='hidden' name='lisa' value='".urlencode(serialize($lisa))."'>
					<input type='hidden' name='lisa_parametri' value='".urlencode(serialize($lisa_parametri))."'>";

			echo "<br><br>";
			echo "<table>";
			echo "<tr><th>".t("Toimittaja")."</th><td><input type='text' size='20' name='ytunnus' value='$ytunnus'></td></tr>";

			echo "<tr><th>".t("ABC-luokkarajaus ja rajausperuste")."</th><td>";

			echo "<select name='abcrajaus' onchange='submit()'>";
			echo "<option  value=''>".t("Valitse")."</option>";

			$teksti = "";
			for ($i=0; $i < count($ryhmaprossat); $i++) {
				$selabc = "";

				if ($i > 0) $teksti = t("ja paremmat");
				if ($org_rajaus == "{$i}##TM") $selabc = "SELECTED";

				echo "<option  value='$i##TM' $selabc>".t("Myynti").": {$ryhmanimet[$i]} $teksti</option>";
			}

			$teksti = "";
			for ($i=0; $i < count($ryhmaprossat); $i++) {
				$selabc = "";

				if ($i > 0) $teksti = t("ja paremmat");
				if ($org_rajaus == "{$i}##TK") $selabc = "SELECTED";

				echo "<option  value='$i##TK' $selabc>".t("Myyntikate").": {$ryhmanimet[$i]} $teksti</option>";
			}

			$teksti = "";
			for ($i=0; $i < count($ryhmaprossat); $i++) {
				$selabc = "";

				if ($i > 0) $teksti = t("ja paremmat");
				if ($org_rajaus == "{$i}##TR") $selabc = "SELECTED";

				echo "<option  value='$i##TR' $selabc>".t("Myyntirivit").": {$ryhmanimet[$i]} $teksti</option>";
			}

			$teksti = "";
			for ($i=0; $i < count($ryhmaprossat); $i++) {
				$selabc = "";

				if ($i > 0) $teksti = t("ja paremmat");
				if ($org_rajaus == "{$i}##TP") $selabc = "SELECTED";

				echo "<option  value='$i##TP' $selabc>".t("Myyntikappaleet").": {$ryhmanimet[$i]} $teksti</option>";
			}

			echo "</select>";

			$sel = array();
			$sel[$abcrajausluokka] = "SELECTED";

			echo "<select name='abcrajausluokka'>";
			echo "<option {$sel['y']} value='y'>",t("Yrityksen luokka"),"</option>";
			echo "<option {$sel['os']} value='os'>",t("Osaston luokka"),"</option>";
			echo "<option {$sel['try']} value='try'>",t("Tuoteryhmän luokka"),"</option>";
			echo "<option {$sel['tme']} value='tme'>",t("Tuotemerkin luokka"),"</option>";
			echo "</select>";
			echo "</td></tr>";


			echo "<tr><td colspan='2' class='back'><br></td></tr>";
			echo "<tr><td colspan='2' class='back'>".t("Valitse jos haluat tulostaa asiakaan myynnit").":</td></tr>";

			echo "<tr><th>".t("Asiakasosasto")."</th><td>";

			$query = "	SELECT distinct osasto
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and osasto!=''
						order by osasto+0";
			$sresult = pupe_query($query);

			echo "<select name='asiakasosasto'>";
			echo "<option value=''>".t("Näytä kaikki")."</option>";

			while ($srow = mysql_fetch_array($sresult)) {
				$sel = '';
				if ($asiakasosasto == $srow[0]) {
					$sel = "selected";
				}
				echo "<option value='$srow[osasto]' $sel>$srow[osasto]</option>";
			}
			echo "</select>";


			echo "	</td></tr>
					<tr><th>".t("Asiakas")."</th><td><input type='text' size='20' name='asiakasno' value='$asiakasno'></td></tr>

					</table><br>
					<input type='Submit' name='jatkanappi' value = '".t("Jatka")."'>
					</form>";

		}

		if ($tee == "JATKA" or $tee == "RAPORTOI") {

			if (!isset($uusiraportti)) {
				$uusiraportti = '';
			}

			//Haetaan tallennetut kyselyt
			$query = "	SELECT distinct kuka.nimi, kuka.kuka, tallennetut_parametrit.nimitys
						FROM tallennetut_parametrit
						JOIN kuka on (kuka.yhtio = tallennetut_parametrit.yhtio and kuka.kuka = tallennetut_parametrit.kuka)
						WHERE tallennetut_parametrit.yhtio = '$kukarow[yhtio]'
						and tallennetut_parametrit.sovellus = '$_SERVER[SCRIPT_NAME]'
						ORDER BY tallennetut_parametrit.nimitys";
			$sresult = pupe_query($query);

			if ($kysely_warning != '') {
				echo "<font class='error'>",t("Et saa tallentaa toisen käyttäjän raporttia"),"!!!";
			}

			echo "<table>";
			echo "<form method='post' autocomplete='off'>";
			echo "	<input type='hidden' name='mul_osasto' value='".urlencode(serialize($mul_osasto))."'>
					<input type='hidden' name='mul_try' value='".urlencode(serialize($mul_try))."'>
					<input type='hidden' name='mul_tme' value='".urlencode(serialize($mul_tme))."'>
					<input type='hidden' name='lisa' value='$lisa'>
					<input type='hidden' name='lisa_parametri' value='$lisa_parametri'>
					<input type='hidden' name='ytunnus' value='$ytunnus'>
					<input type='hidden' name='edrappari' value='$rappari'>
					<input type='hidden' name='toimittajaid' value='$toimittajaid'>
					<input type='hidden' name='asiakasid' value='$asiakasid'>
					<input type='hidden' name='asiakasno' value='$asiakasno'>
					<input type='hidden' name='asiakasosasto' value='$asiakasosasto'>
					<input type='hidden' name='abcrajaus' value='$abcrajaus'>
					<input type='hidden' name='abcrajaustapa' value='$abcrajaustapa'>
					<input type='hidden' name='abcrajausluokka' value='$abcrajausluokka'>";
			echo "<tr>";
			echo "<th>",t("Valitse raportti"),":</th>";
			echo "<td>";
			echo "<select name='kysely' onchange='document.getElementById(\"tee\").value = \"lataavanha\";submit();'>";
			echo "<option value=''>".t("Valitse")."</option>";
			while ($srow = mysql_fetch_array($sresult)) {

				$sel = '';
				if ($kysely == $srow["kuka"]."#".$srow["nimitys"]) {
					$sel = "selected";
				}

				echo "<option value='$srow[kuka]#$srow[nimitys]' $sel>$srow[nimitys] ($srow[nimi])</option>";
			}
			echo "</select>";
			echo "</td>";
			echo "<td>";
			echo "<input type='button' value='",t("Tallenna"),"' onclick='document.getElementById(\"tee\").value = \"tallenna\";submit();'>";
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<th>",t("Tallenna uusi raportti"),":</th>";
			echo "<td><input type='text' name='uusirappari' value=''></td>";
			echo "<td><input type='submit' id='tallenna_button' value='",t("Tallenna"),"' onclick=\"document.getElementById('tee').value = 'uusiraportti'\"></td>";
			echo "</tr>";
			echo "</table>";
			echo "<br/>";

			if (!isset($mul_try)) {
				$mul_try = array();
			}

			if (!isset($mul_osasto)) {
				$mul_osasto = array();
			}

			if (!isset($mul_tme)) {
				$mul_tme = array();
			}

			if (!isset($asiakasid)) {
				$asiakasid = '';
			}

			if (!isset($edrappari)) {
				$edrappari = '';
			}

			if (!isset($rappari)) {
				$rappari = '';
			}

			if (!isset($osasto)) {
				$osasto = '';
			}

			if (!isset($try)) {
				$try = '';
			}

			if (!isset($tuotemerkki)) {
				$tuotemerkki = '';
			}

			if ($mul_osasto != '' and !is_array($mul_osasto)) {
				$mul_osasto = unserialize(urldecode($mul_osasto));
			}

			if ($mul_try != '' and !is_array($mul_try)) {
				$mul_try = unserialize(urldecode($mul_try));
			}

			if ($mul_tme != '' and !is_array($mul_tme)) {
				$mul_tme = unserialize(urldecode($mul_tme));
			}

			if (is_array($mul_try) and count($mul_try) > 0) {
				$try = '';

				foreach ($mul_try as $tr) {
					$res = t_avainsana("TRY", "", "and avainsana.selite = '$tr'");

					$row_tr = mysql_fetch_assoc($res);
					$try .= "$tr {$row_tr['selitetark']}<br>";
				}

				$try = substr($try, 0, -4);
			}

			if (is_array($mul_osasto) and count($mul_osasto) > 0) {

				$osasto = '';

				foreach ($mul_osasto as $os) {
					$res = t_avainsana("OSASTO", "", "and avainsana.selite = '$os'");

					$row_os = mysql_fetch_assoc($res);
					$osasto .= "$os {$row_os['selitetark']}<br>";
				}

				$osasto = substr($osasto, 0, -4);
			}
			if (is_array($mul_tme) and count($mul_tme) > 0) {

				$tuotemerkki = '';

				foreach ($mul_tme as $tm) {
					$tuotemerkki .= "$tm<br>";
				}

				$tuotemerkki = substr($tuotemerkki, 0, -4);
			}
			if ($toimittajaid != '') {
				$query = "	SELECT nimi
							FROM toimi
							WHERE yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
				$sresult = pupe_query($query);
				$trow1 = mysql_fetch_array($sresult);
			}

			if ($asiakasid != '') {
				$query = "	SELECT nimi
							FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and tunnus='$asiakasid'";
				$sresult = pupe_query($query);
				$trow2 = mysql_fetch_array($sresult);
			}

			if (($rappari != '' and $edrappari != '') and $rappari != $edrappari) {
				unset($valitut);
				$tee = "JATKA";
			}

			if (!isset($edrappari) or ($rappari == "" and $edrappari != "")) {
				$defaultit = "PÄÄLLE";
			}

			$abcnimi = isset($ryhmanimet[$abcrajaus]) ? $ryhmanimet[$abcrajaus] : '';

			echo "	<input type='hidden' name='tee' id='tee' value='RAPORTOI'>
					<input type='hidden' name='mul_osasto' value='".urlencode(serialize($mul_osasto))."'>
					<input type='hidden' name='mul_try' value='".urlencode(serialize($mul_try))."'>
					<input type='hidden' name='mul_tme' value='".urlencode(serialize($mul_tme))."'>
					<input type='hidden' name='lisa' value='$lisa'>
					<input type='hidden' name='lisa_parametri' value='$lisa_parametri'>
					<input type='hidden' name='ytunnus' value='$ytunnus'>
					<input type='hidden' name='edrappari' value='$rappari'>
					<input type='hidden' name='toimittajaid' value='$toimittajaid'>
					<input type='hidden' name='asiakasid' value='$asiakasid'>
					<input type='hidden' name='asiakasno' value='$asiakasno'>
					<input type='hidden' name='asiakasosasto' value='$asiakasosasto'>
					<input type='hidden' name='abcrajaus' value='$abcrajaus'>
					<input type='hidden' name='abcrajaustapa' value='$abcrajaustapa'>
					<input type='hidden' name='abcrajausluokka' value='$abcrajausluokka'>

					<table>";

			if ($osasto != "") echo "<tr><th>".t("Osasto")."</th><td colspan='2'>$osasto</td></tr>";
			if ($try != "") echo "<tr><th>".t("Tuoteryhmä")."</th><td colspan='2'>$try</td></tr>";
			if ($ytunnus != "") echo "<tr><th>".t("Toimittaja")."</th><td colspan='2'>$ytunnus {$trow1['nimi']}</td></tr>";
			if ($tuotemerkki != "") echo "<tr><th>".t("Tuotemerkki")."</th><td colspan='2'>$tuotemerkki</td></tr>";
			if ($abcnimi != "") echo "<tr><th>".t("ABC-rajaus")."</th><td colspan='2'>$abcnimi</td></tr>";
			if ($asiakasosasto != "") echo "<tr><th>".t("Asiakasosasto")."</th><td colspan='2'>$asiakasosasto</td></tr>";
			if ($asiakasno != "") echo "<tr><th>".t("Asiakas")."</th><td colspan='2'>$asiakasno {$trow2['nimi']}</td></tr>";

			echo "	<tr><td class='back'><br></td></tr>";

			echo "	<tr>
					<td class='back'></td>
					<th>".t("Alkupäivämäärä (pp-kk-vvvv)")."</th>
					<th>".t("Loppupäivämäärä (pp-kk-vvvv)")."</th></tr>";

			echo "	<tr><th>".t("Kausi 1")."</th>
					<td><input type='text' name='ppa1' value='$ppa1' size='5'>
					<input type='text' name='kka1' value='$kka1' size='5'>
					<input type='text' name='vva1' value='$vva1' size='5'></td>
					<td><input type='text' name='ppl1' value='$ppl1' size='5'>
					<input type='text' name='kkl1' value='$kkl1' size='5'>
					<input type='text' name='vvl1' value='$vvl1' size='5'></td>";
			echo "</tr>";

			echo "	<tr><th>".t("Kausi 2")."</th>
					<td><input type='text' name='ppa2' value='$ppa2' size='5'>
					<input type='text' name='kka2' value='$kka2' size='5'>
					<input type='text' name='vva2' value='$vva2' size='5'></td>
					<td><input type='text' name='ppl2' value='$ppl2' size='5'>
					<input type='text' name='kkl2' value='$kkl2' size='5'>
					<input type='text' name='vvl2' value='$vvl2' size='5'></td>";
			echo "</tr>";

			echo "	<tr><th>".t("Kausi 3")."</th>
					<td><input type='text' name='ppa3' value='$ppa3' size='5'>
					<input type='text' name='kka3' value='$kka3' size='5'>
					<input type='text' name='vva3' value='$vva3' size='5'></td>
					<td><input type='text' name='ppl3' value='$ppl3' size='5'>
					<input type='text' name='kkl3' value='$kkl3' size='5'>
					<input type='text' name='vvl3' value='$vvl3' size='5'></td>";
			echo "</tr>";

			echo "	<tr><th>".t("Kausi 4")."</th>
					<td><input type='text' name='ppa4' value='$ppa4' size='5'>
					<input type='text' name='kka4' value='$kka4' size='5'>
					<input type='text' name='vva4' value='$vva4' size='5'></td>
					<td><input type='text' name='ppl4' value='$ppl4' size='5'>
					<input type='text' name='kkl4' value='$kkl4' size='5'>
					<input type='text' name='vvl4' value='$vvl4' size='5'></td>";
			echo "</tr>";

			$chk = "";

			if ($valitut["TALLENNAPAIVAM"] != '') {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("Tallenna päivämäärät:")."</th><td colspan='2'><input type='checkbox' name='valitut[TALLENNAPAIVAM]' value='TALLENNAPAIVAM' $chk></td></tr>";
			echo "	<tr><td class='back'><br></td></tr>";

			//Ostokausivalinnat
			echo "<tr><th>",t("Ostoehdotus")," (",t("anna varastointitarve viikoissa"),"):</th><td colspan='2'>";

			foreach ($ryhmanimet as $ryhma) {
				echo "<select name='valitut[KAUSI$ryhma]'>";

				for ($i = 1; $i < 53; $i++) {
					$chk = '';

					if ($valitut["KAUSI$ryhma"] == "$ryhma##$i") {
						$chk = 'selected';
					}
					echo "<option value='$ryhma##$i' $chk>$i</option>";
				}

				if ($valitut["KAUSI$ryhma"] == "$ryhma##104") {
					$chk = 'selected';
				}

				echo "<option value='$ryhma##104' $chk>104</option>";
				echo "</select> $ryhma<br/>";
			}

			echo "</td></tr>";

			echo "<tr><td class='back'><br></td></tr>";

			if (table_exists("yhteensopivuus_rekisteri")) {
				echo "<tr><th>",t("Vuosimalliväli"),"<td colspan='2'><input type='text' name='vm1' id='vm1' size='10' value='$vm1'> - <input type='text' name='vm2' id='vm2' size='10' value='$vm2'></td></tr>";
				echo "<tr><td class='back'><br></td></tr>";
			}

			//Yhtiövalinnat
			$query	= "	SELECT distinct yhtio, nimi
						from yhtio
						where konserni = '$yhtiorow[konserni]' and konserni != ''";
			$presult = pupe_query($query);

			$yhtiot 	= "";
			$konsyhtiot = "";
			$vlask 		= 0;

			if (mysql_num_rows($presult) > 0) {
				while ($prow = mysql_fetch_array($presult)) {

					$chk = "";
					if ($prow["yhtio"] == $kukarow["yhtio"]) {
						$chk = "CHECKED";
						$yhtiot .= "'".$prow["yhtio"]."',";
					}

					if ($vlask == 0) {
						echo "<tr><th rowspan='".mysql_num_rows($presult)."'>Huomioi yhtiön myynnit:</th>";
					}
					else {
						echo "<tr>";
					}

					echo "<td colspan='2'><input type='checkbox' name='valitut[YHTIO##$prow[yhtio]]' value='YHTIO##$prow[yhtio]' $chk> $prow[nimi]</td></tr>";

					$konsyhtiot .= "'".$prow["yhtio"]."',";
					$vlask++;
				}

				$yhtiot = substr($yhtiot,0,-1);
				$konsyhtiot = substr($konsyhtiot,0,-1);

				echo "	<tr><td class='back'><br></td></tr>";
			}
			else {
				$yhtiot = "'".$kukarow['yhtio']."'";
				$konsyhtiot = "'".$kukarow['yhtio']."'";
			}

			//Ajetaanko varastopaikoittain
			$chk = "";
			if ($valitut["paikoittain"] != '') {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("Aja raportti varastopaikoittain")."</th><td colspan='2'><input type='checkbox' name='valitut[paikoittain]' value='PAIKOITTAIN' $chk></td></tr>";


			//Näytetäänkö poistetut tuotteet
			$chk = "";
			if ($valitut["poistetut"] != '' or $defaultit == "PÄÄLLE") {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("Älä näytä poistettuja tuotteita")."</th><td colspan='2'><input type='checkbox' name='valitut[poistetut]' value='POISTETUT' $chk></td></tr>";

			//Näytetäänkö poistetut tuotteet
			$chk = "";
			if ($valitut["poistuvat"] != '' or $defaultit == "PÄÄLLE") {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("Älä näytä poistuvia tuotteita")."</th><td colspan='2'><input type='checkbox' name='valitut[poistuvat]' value='POISTUVAT' $chk></td></tr>";


			//Näytetäänkö poistetut tuotteet
			$chk = "";
			if ($valitut["EIHINNASTOON"] != '' or $defaultit == "PÄÄLLE") {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("Älä näytä tuotteita joita ei näytetä hinnastossa")."</th><td colspan='2'><input type='checkbox' name='valitut[EIHINNASTOON]' value='EIHINNASTOON' $chk></td></tr>";

			//Näytetäänkö ei varastoitavat tuotteet
			$chk = "";
			if ($valitut["EIVARASTOITAVA"] != '') {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("Älä näytä tuotteita joita ei varastoida")."</th><td colspan='2'><input type='checkbox' name='valitut[EIVARASTOITAVA]' value='EIVARASTOITAVA' $chk></td></tr>";

			//Näytetäänkö ehdotettavat tuotteet
			$chk = "";
			if ($valitut["EHDOTETTAVAT"] != '') {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("Näytä vain ostettavaksi ehdotettavat rivit")."</th><td colspan='2'><input type='checkbox' name='valitut[EHDOTETTAVAT]' value='EHDOTETTAVAT' $chk></td></tr>";

			if ($abcrajaus != "") {

				echo "<tr><td class='back'><br></td></tr>";
				echo "<tr><th colspan='3'>".t("ABC-rajaus")." $ryhmanimet[$abcrajaus]</th></tr>";

				if (!isset($abc_laadittuaika) or $abc_laadittuaika == "")            $chk1 = "CHECKED";
				if (isset($abc_laadittuaika) and $abc_laadittuaika == "alle_12kk")   $chk2 = "CHECKED";
				if (isset($abc_laadittuaika) and $abc_laadittuaika == "yli_12kk")    $chk3 = "CHECKED";
				if (isset($abc_laadittuaika) and $abc_laadittuaika == "yli_annettu") $chk4 = "CHECKED";

				echo "
				<tr>
					<th>
						".t("Listaa myös tuotteet, jotka on perustettu 12kk sisällä")."
					</th>
					<td colspan='2'>
						<input type='radio' name='abc_laadittuaika' value='' $chk1>
					</td>
				</tr>

				<tr>
					<th>
						".t("Älä listaa 12kk sisällä perustettuja tuotteita")."
					</th>
					<td colspan='2'>
						<input type='radio' name='abc_laadittuaika' value='alle_12kk' $chk2>
					</td>
				</tr>

				<tr>
					<th>
						".t("Listaa vain 12kk sisällä perustetut tuotteet")."
					</th>
					<td colspan='2'>
						<input type='radio' name='abc_laadittuaika' value='yli_12kk' $chk3>
					</td>
				</tr>

				<tr>
					<th>
						".t("Listaa myös tuotteet, jotka on perustettu jälkeen")."
					</th>
					<td colspan='2'>
						<input type='radio' name='abc_laadittuaika' value='yli_annettu' $chk4>&nbsp;
						<input type='text' name='naytauudet_pp' value='$naytauudet_pp' size='5'>
						<input type='text' name='naytauudet_kk' value='$naytauudet_kk' size='5'>
						<input type='text' name='naytauudet_vv' value='$naytauudet_vv' size='5'>
						".t("pp.kk.vvvv")."
					</td>
				</tr>";
			}

			echo "<tr><td class='back'><br></td></tr>";

			//Valitaan varastot joiden saldot huomioidaan
			//Tutkitaan onko käyttäjä klikannut useampaa yhtiötä
			if ($konsyhtiot  != '') {
				$konsyhtiot = " yhtio in (".$konsyhtiot.") ";
			}
			else {
				$konsyhtiot = " yhtio = '$kukarow[yhtio]' ";
			}

			// normivarastot
			$query = "	SELECT *
						FROM varastopaikat
						WHERE $konsyhtiot
						ORDER BY yhtio, tyyppi, nimitys";
			$vtresult = pupe_query($query);

			$vlask = 0;

			echo "<tr>";
			echo "<th rowspan='".(mysql_num_rows($vtresult)+1)."'>".t("Varastot").":</th>";
			echo "<th>".t("Huomioi varaston saldo ostoehdotuksen laskennassa")."</th>";
			echo "<th>".t("Näytä myös varaston saldo")."</th>";
			echo "</tr>";

			while ($vrow = mysql_fetch_array($vtresult)) {
				$chk = "";
				$chk2 = "";

				if ($valitut["VARASTO##$vrow[tunnus]"] != '') {
					$chk = " checked";
				}

				if ($valitut["VARASTO2##$vrow[tunnus]"] != '') {
					$chk2 = " checked";
				}

				echo "<tr>";
				echo "<td><input type='checkbox' name='valitut[VARASTO##$vrow[tunnus]]' value='VARASTO##$vrow[tunnus]'$chk> $vrow[nimitys] ($vrow[yhtio])</td>";
				echo "<td><input type='checkbox' name='valitut[VARASTO2##$vrow[tunnus]]' value='VARASTO2##$vrow[tunnus]'$chk2> $vrow[nimitys] ($vrow[yhtio])</td>";
				echo "</tr>";
			}

			echo "<tr><td class='back'><br></td></tr>";

			echo "</table>";

			echo "<table>";
			echo "<tr><th colspan='8'>".t("Sarakkeet")."</th></tr>";

			$lask = 0;
			echo "<tr>";

			foreach ($sarakkeet as $key => $sarake) {

				$sel = "";
				if ($valitut[$key] != "") {
					$sel = "CHECKED";
				}

				if ($rappari == '') {
					$sel = 'CHECKED';
				}

				if ($lask % 8 == 0 and $lask != 0) {
					echo "</tr><tr>";
				}

				echo "<td><input type='checkbox' name='valitut[$key]' value='".trim($sarake)."' $sel> ".ucfirst($sarake)."</td>";
				$lask++;
			}

			echo "</tr>";
			echo "</table>";
			echo "<br>
				<input type='Submit' name='RAPORTOI' value = '".t("Aja ostoraportti")."'>
				</form>";
		}

		require ("../inc/footer.inc");
	}
