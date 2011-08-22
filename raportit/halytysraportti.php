<?php

	if (isset($_POST["tee"])) {
		if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {

		echo "<font class='head'>".t("H�lytysraportti")."</font><hr>";

		// ABC luokkanimet
		$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
		$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

		// Jos jt-rivit varaavat saldoa niin se vaikuttaa asioihin
		if ($yhtiorow["varaako_jt_saldoa"] != "") {
			$lisavarattu = " + tilausrivi.varattu";
		}
		else {
			$lisavarattu = "";
		}

		// Tarvittavat p�iv�m��r�t
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

		if ($apienin == 99999999 and $lsuurin == 0) {
			$apienin = $lsuurin = date('Ymd'); // jos mit��n ei l�ydy niin NOW molempiin. :)
		}

		$apvm = substr($apienin,0,4)."-".substr($apienin,4,2)."-".substr($apienin,6,2);
		$lpvm = substr($lsuurin,0,4)."-".substr($lsuurin,4,2)."-".substr($lsuurin,6,2);

		// Tulostettavat sarakkeet
		$sarakkeet = array();

		//Voidaan tarvita jotain muuttujaa t��lt�
		if (isset($muutparametrit)) {
			list($temp_osasto,$temp_tuoryh,$temp_ytunnus,$temp_tuotemerkki,$temp_asiakasosasto,$temp_asiakasno,$temp_toimittaja) = explode('#', $muutparametrit);
		}

		$sarakkeet["SARAKE1"] 	= t("osasto")."\t";
		$sarakkeet["SARAKE2"] 	= t("tuoteryhma")."\t";
		$sarakkeet["SARAKE3"] 	= t("tuotemerkki")."\t";
		$sarakkeet["SARAKE3B"] 	= t("malli")."\t";
		$sarakkeet["SARAKE3C"] 	= t("mallitarkenne")."\t";
		$sarakkeet["SARAKE4"] 	= t("tahtituote")."\t";
		$sarakkeet["SARAKE4B"] 	= t("status")."\t";
		$sarakkeet["SARAKE4C"] 	= t("abc")."\t";
		$sarakkeet["SARAKE4CA"] = t("abc osasto")."\t";
		$sarakkeet["SARAKE4CB"] = t("abc try")."\t";
		$sarakkeet["SARAKE4D"] 	= t("luontiaika")."\t";
		$sarakkeet["SARAKE5"] 	= t("saldo")."\t";
		$sarakkeet["SARAKE6"] 	= t("halytysraja")."\t";
		$sarakkeet["SARAKE7"] 	= t("tilauksessa")."\t";
		$sarakkeet["SARAKE7B"] 	= t("valmistuksessa")."\t";
		$sarakkeet["SARAKE8"] 	= t("ennpois")."\t";
		$sarakkeet["SARAKE9"] 	= t("jt")."\t";

		$ehd_kausi_o1 = "1";
		$ehd_kausi_o2 = "3";
		$ehd_kausi_o3 = "4";

		if ($valitut["KAUSI1"] != '') {
			list($al, $lo) = explode("##", $valitut["KAUSI1"]);

			$ehd_kausi_o1	= $lo;
		}
		if ($valitut["KAUSI2"] != '') {
			list($al, $lo) = explode("##", $valitut["KAUSI2"]);

			$ehd_kausi_o2	= $lo;
		}
		if ($valitut["KAUSI3"] != '') {
			list($al, $lo) = explode("##", $valitut["KAUSI3"]);

			$ehd_kausi_o3	= $lo;
		}

		$sarakkeet["SARAKE10"] 	= t("Ostoehdotus")." $ehd_kausi_o1\t";
		$sarakkeet["SARAKE11"] 	= t("Ostoehdotus")." $ehd_kausi_o2\t";
		$sarakkeet["SARAKE12"] 	= t("Ostoehdotus")." $ehd_kausi_o3\t";

		$sarakkeet["SARAKE13"] 	= t("ostettava haly")."\t";
		$sarakkeet["SARAKE14"] 	= t("osto_era")."\t";
		$sarakkeet["SARAKE15"] 	= t("myynti_era")."\t";
		$sarakkeet["SARAKE16"] 	= t("toimittaja")."\t";
		$sarakkeet["SARAKE17"] 	= t("toim_tuoteno")."\t";
		$sarakkeet["SARAKE18"] 	= t("nimitys")."\t";
		$sarakkeet["SARAKE18B"] = t("toim_nimitys")."\t";
		$sarakkeet["SARAKE18C"] = t("kuvaus")."\t";

		$sarakkeet["SARAKE18D"] = t("lyhytkuvaus")."\t";
		$sarakkeet["SARAKE18E"] = t("tuotekorkeus")."\t";
		$sarakkeet["SARAKE18F"] = t("tuoteleveys")."\t";
		$sarakkeet["SARAKE18G"] = t("tuotesyvyys")."\t";
		$sarakkeet["SARAKE18H"] = t("tuotemassa")."\t";
		$sarakkeet["SARAKE18I"] = t("hinnastoon")."\t";

		$sarakkeet["SARAKE19"] 	= t("ostohinta")."\t";
		$sarakkeet["SARAKE20"] 	= t("myyntihinta")."\t";
		$sarakkeet["SARAKE20Z"] = t("epakurantti25pvm")."\t";
		$sarakkeet["SARAKE21"] 	= t("epakurantti50pvm")."\t";
		$sarakkeet["SARAKE21B"]	= t("epakurantti75pvm")."\t";
		$sarakkeet["SARAKE22"] 	= t("epakurantti100pvm")."\t";
		$sarakkeet["SARAKE23"] 	= t("oletussaldo")."\t";
		$sarakkeet["SARAKE24"] 	= t("hyllypaikka")."\t";

		if ($tee == "RAPORTOI" and isset($RAPORTOI)) {
			$kausi1 = "($ppa1.$kka1.$vva1-$ppl1.$kkl1.$vvl1)";
			$kausi2 = "($ppa2.$kka2.$vva2-$ppl2.$kkl2.$vvl2)";
			$kausi3 = "($ppa3.$kka3.$vva3-$ppl3.$kkl3.$vvl3)";
			$kausi4 = "($ppa4.$kka4.$vva4-$ppl4.$kkl4.$vvl4)";

			$kausied1 = "($ppa1ed.$kka1ed.$vva1ed-$ppl1ed.$kkl1ed.$vvl1ed)";
			$kausied2 = "($ppa2ed.$kka2ed.$vva2ed-$ppl2ed.$kkl2ed.$vvl2ed)";
			$kausied3 = "($ppa3ed.$kka3ed.$vva3ed-$ppl3ed.$kkl3ed.$vvl3ed)";
			$kausied4 = "($ppa4ed.$kka4ed.$vva4ed-$ppl4ed.$kkl4ed.$vvl4ed)";
		}
		else {
			$kausi1 = t("Kausi 1");
			$kausi2 = t("Kausi 2");
			$kausi3 = t("Kausi 3");
			$kausi4 = t("Kausi 4");

			$kausied1 = t("Ed. Kausi 1");
			$kausied2 = t("Ed. Kausi 2");
			$kausied3 = t("Ed. Kausi 3");
			$kausied4 = t("Ed. Kausi 4");
		}

		$sarakkeet["SARAKE25"] = t("puutteet")." $kausi1\t";
		$sarakkeet["SARAKE26"] = t("puutteet")." $kausi2\t";
		$sarakkeet["SARAKE27"] = t("puutteet")." $kausi3\t";
		$sarakkeet["SARAKE28"] = t("puutteet")." $kausi4\t";

		//Myydyt kappaleet
		$sarakkeet["SARAKE29"] = t("myynti")." $kausi1\t";
		$sarakkeet["SARAKE30"] = t("myynti")." $kausi2\t";
		$sarakkeet["SARAKE31"] = t("myynti")." $kausi3\t";
		$sarakkeet["SARAKE32"] = t("myynti")." $kausi4\t";

		$sarakkeet["SARAKE33"] = t("myynti")." $kausied1\t";
		$sarakkeet["SARAKE34"] = t("myynti")." $kausied2\t";
		$sarakkeet["SARAKE35"] = t("myynti")." $kausied3\t";
		$sarakkeet["SARAKE36"] = t("myynti")." $kausied4\t";

		//Kulutetut kappaleet
		$sarakkeet["SARAKE29K"] = t("kulutus")." $kausi1\t";
		$sarakkeet["SARAKE30K"] = t("kulutus")." $kausi2\t";
		$sarakkeet["SARAKE31K"] = t("kulutus")." $kausi3\t";
		$sarakkeet["SARAKE32K"] = t("kulutus")." $kausi4\t";
		$sarakkeet["SARAKE33K"] = t("kulutus")." $kausied1\t";
		$sarakkeet["SARAKE34K"] = t("kulutus")." $kausied2\t";
		$sarakkeet["SARAKE35K"] = t("kulutus")." $kausied3\t";
		$sarakkeet["SARAKE36K"] = t("kulutus")." $kausied4\t";

		$sarakkeet["SARAKE37"] = t("Kate")." $yhtiorow[valkoodi] $kausi1\t";
		$sarakkeet["SARAKE38"] = t("Kate")." $yhtiorow[valkoodi] $kausi2\t";
		$sarakkeet["SARAKE39"] = t("Kate")." $yhtiorow[valkoodi] $kausi3\t";
		$sarakkeet["SARAKE40"] = t("Kate")." $yhtiorow[valkoodi] $kausi4\t";
		$sarakkeet["SARAKE41"] = t("Kate %")." $kausi1\t";
		$sarakkeet["SARAKE42"] = t("Kate %")." $kausi2\t";
		$sarakkeet["SARAKE43"] = t("Kate %")." $kausi3\t";
		$sarakkeet["SARAKE44"] = t("Kate %")." $kausi4\t";

		$sarakkeet["SARAKE45"] 	= t("tuotekerroin")."\t";
		$sarakkeet["SARAKE46"] 	= t("ennakkotilauksessa")."\t";
		$sarakkeet["SARAKE47"] 	= t("aleryhm�")."\t";
		$sarakkeet["SARAKE47B"] = t("kehahin")."\t";

		if ($temp_asiakasosasto != '' or $asiakasosasto != '') {
			$sarakkeet["SARAKE48"] = t("myynti asiakasosasto")." $asiakasosasto $kausi1\t";
			$sarakkeet["SARAKE49"] = t("myynti asiakasosasto")." $asiakasosasto $kausi2\t";
			$sarakkeet["SARAKE50"] = t("myynti asiakasosasto")." $asiakasosasto $kausi3\t";
			$sarakkeet["SARAKE51"] = t("myynti asiakasosasto")." $asiakasosasto $kausi4\t";
		}
		if ($temp_asiakasno != '' or $asiakasno != '') {
			$sarakkeet["SARAKE52"] = t("myynti asiakas")." $asiakasno $kausi1\t";
			$sarakkeet["SARAKE53"] = t("myynti asiakas")." $asiakasno $kausi2\t";
			$sarakkeet["SARAKE54"] = t("myynti asiakas")." $asiakasno $kausi3\t";
			$sarakkeet["SARAKE55"] = t("myynti asiakas")." $asiakasno $kausi4\t";
		}

		// aika karseeta, mutta katotaan voidaanko t�ll�st� optiota n�ytt�� yks tosi firma specific juttu
		$query = "describe yhteensopivuus_rekisteri";
		$res = mysql_query($query);

		if (mysql_error() == "") {
			$query = "select count(*) kpl from yhteensopivuus_rekisteri where yhtio='$kukarow[yhtio]'";
			$res = mysql_query($query);
			$row = mysql_fetch_array($res);
			if ($row["kpl"] > 0) {
				$sarakkeet["SARAKE64"] = "Rekister�idyt kpl\t";
			}
		}



		//	Haetaan kaikki varastot ja luodaan kysely paljonko ko. varastoon on tilattu tavaraa..
		$varastolisa = "";

		if ($valitut["OSTOTVARASTOITTAIN"] != "") {

			$query = "	SELECT *
						FROM varastopaikat
						where yhtio = '$kukarow[yhtio]'";
			$osvres = mysql_query($query) or pupe_error($query);

			$abuArray=array();

			while ($vrow = mysql_fetch_array($osvres)) {
				$varastolisa .= ", sum(if(tyyppi='O' and
									concat(rpad(upper('$vrow[alkuhyllyalue]'),  5, '0'),lpad(upper('$vrow[alkuhyllynro]'),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0')) and
									concat(rpad(upper('$vrow[loppuhyllyalue]'), 5, '0'),lpad(upper('$vrow[loppuhyllynro]'), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								, varattu, 0)) tilattu_$vrow[tunnus] ";

				$sarakkeet["SARAKE65#".$vrow["tunnus"]] = t("tilattu kpl - $vrow[nimitys]")."\t";
				$abuArray["SARAKE65#".$vrow["tunnus"]] = "SARAKE65#".$vrow["tunnus"];
			}

			// Liitet��n oletus jotta summat voisi t�sm�t�..
			$varastolisa .= ", sum(if(tyyppi='O' and hyllyalue = '', varattu, 0)) tilattu_oletus ";

			$sarakkeet["SARAKE65#oletus"] = t("tilattu kpl - varastoa ei annettu")."\t";
			$abuArray["SARAKE65#oletus"] = "SARAKE65#oletus";

			//	karseeta haetaan offset valitut arrayksi jotta osataan siirt�� n�m� tiedot oikeaan paikkaan..
			$i = 0;

			foreach ($valitut as $key => $value) {
				if (in_array($key, array("SARAKE56","SARAKE57","SARAKE58","SARAKE59","SARAKE60","SARAKE61","SARAKE62","SARAKE63"))) {
					$offset = $i;
					echo "l�ydettiin offset ($offset)<br>";
					break;
				}
				$i++;
			}
			array_splice($valitut,$offset,0,$abuArray);
		}

		$sarakkeet["SARAKE56"] = t("Korvaavat Tuoteno")."\t";
		$sarakkeet["SARAKE57"] = t("Korvaavat Saldo")."\t";
		$sarakkeet["SARAKE58"] = t("Korvaavat Ennpois")."\t";
		$sarakkeet["SARAKE59"] = t("Korvaavat Tilauksessa")."\t";
		$sarakkeet["SARAKE60"] = t("Korvaavat Myyty")." $kausi1\t";
		$sarakkeet["SARAKE61"] = t("Korvaavat Myyty")." $kausi2\t";
		$sarakkeet["SARAKE62"] = t("Korvaavat Myyty")." $kausi3\t";
		$sarakkeet["SARAKE63"] = t("Korvaavat Myyty")." $kausi4\t";

		//Jos halutaan tallentaa p�iv�m��r�t profiilin taakse
		if ($valitut["TALLENNAPAIVAM"] == "TALLENNAPAIVAM") {
			//Tehd��n p�iv�m��rist� tallennettavia
			$paivamaarat = array('ppa1','kka1','vva1','ppl1','kkl1','vvl1','ppa2','kka2','vva2','ppl2','kkl2','vvl2','ppa3','kka3','vva3','ppl3','kkl3','vvl3','ppa4','kka4','vva4','ppl4','kkl4','vvl4');
			foreach($paivamaarat as $paiva) {
				$valitut[] = "PAIVAM##".$paiva."##".${$paiva};
			}
		}


		// T�ss� luodaan uusi raporttiprofiili
		if ($tee == "RAPORTOI" and $uusirappari != '') {

			$rappari = $kukarow["kuka"]."##".$uusirappari;

			foreach($valitut as $val) {
				$query = "INSERT INTO avainsana set yhtio='$kukarow[yhtio]', laji='HALYRAP', selite='$rappari', selitetark='$val'";
				$res = mysql_query($query) or pupe_error($query);
			}
		}


		//Ajetaan itse raportti
		if ($tee == "RAPORTOI" and isset($RAPORTOI)) {

			if ($rappari != '') {
				$query = "DELETE FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='HALYRAP' and selite='$rappari'";
				$res = mysql_query($query) or pupe_error($query);

				foreach($valitut as $val) {
					$query = "INSERT INTO avainsana set yhtio='$kukarow[yhtio]', laji='HALYRAP', selite='$rappari', selitetark='$val'";
					$res = mysql_query($query) or pupe_error($query);
				}
			}

			if ($tuoryh != '') {
				$sresult = t_avainsana("TRY", "", "and avainsana.selite  = '$tuoryh'");
				$srow = mysql_fetch_array($sresult);
			}
			if ($osasto != '') {
				$sresult = t_avainsana("OSASTO", "", "and avainsana.selite  = '$osasto'");
				$trow = mysql_fetch_array($sresult);
			}
			if ($toimittajaid != '') {
				$query = "	SELECT nimi
							FROM toimi
							WHERE yhtio = '$kukarow[yhtio]' and tunnus='$toimittajaid'";
				$sresult = mysql_query($query) or pupe_error($query);
				$trow1 = mysql_fetch_array($sresult);
			}
			if ($asiakasid != '') {
				$query = "	SELECT nimi
							FROM asiakas
							WHERE yhtio = '$kukarow[yhtio]' and tunnus='$asiakasid'";
				$sresult = mysql_query($query) or pupe_error($query);
				$trow2 = mysql_fetch_array($sresult);
			}

			$abcnimi = $ryhmanimet[$abcrajaus];

			echo "	<table>
					<tr><th>".t("Osasto")."</th><td colspan='3'>$osasto $trow[selitetark]</td></tr>
					<tr><th>".t("Tuoteryhm�")."</th><td colspan='3'>$tuoryh $srow[selitetark]</td></tr>
					<tr><th>".t("Toimittaja")."</th><td colspan='3'>$ytunnus $trow1[nimi]</td></tr>
					<tr><th>".t("Tuotemerkki")."</th><td colspan='3'>$tuotemerkki</td></tr>
					<tr><th>".t("ABC-rajaus")."</th><td colspan='3'>$abcnimi</td></tr>
					<tr><th>".t("Asiakasosasto")."</th><td colspan='3'>$asiakasosasto</td></tr>
					<tr><th>".t("Asiakas")."</th><td colspan='3'>$asiakasno $trow2[nimi]</td></tr>";

			echo "	</table><br>";
			flush();

			$lisaa  = ""; // tuote-rajauksia
			$lisaa2 = ""; // toimittaja-rajauksia

			if ($osasto != '') {
				$lisaa .= " and tuote.osasto = '$osasto' ";
			}
			if ($tuoryh != '') {
				$lisaa .= " and tuote.try = '$tuoryh' ";
			}
			if ($tuotemerkki != '') {
				$lisaa .= " and tuote.tuotemerkki = '$tuotemerkki' ";
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
			// Listaa vain �skett�in perustetut tuotteet:
			if ($valitut["VAINUUDETTUOTTEET"] != '') {
				$lisaa .= " and tuote.luontiaika >= date_sub(current_date, interval 12 month) ";
			}
			// �l� listaa �skett�in perustettuja tuotteita:
			if ($valitut["UUDETTUOTTEET"] != '') {
				$lisaa .= " and tuote.luontiaika < date_sub(current_date, interval 12 month) ";
			}

			if ($toimittajaid != '') {
				$lisaa2 .= " JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid' ";
			}

			///* T�m� skripti k�ytt�� slave-tietokantapalvelinta *///
			$useslave = 1;
			//Eli haetaan connect.inc uudestaan t�ss�
			require("../inc/connect.inc");

			//Yhti�valinnat
			$query	= "	SELECT distinct yhtio, nimi
						from yhtio
						where konserni = '$yhtiorow[konserni]' and konserni != ''";
			$presult = mysql_query($query) or pupe_error($query);

			$yhtiot 	= "";
			$konsyhtiot = "";

			if (mysql_num_rows($presult) > 0) {
				while ($prow = mysql_fetch_array($presult)) {
					if ($valitut["YHTIO##$prow[yhtio]"] == "YHTIO##".$prow["yhtio"]) {
						$yhtiot .= "'".$prow["yhtio"]."',";
					}
					$konsyhtiot .= "'".$prow["yhtio"]."',";
				}

				$yhtiot = substr($yhtiot,0,-1);
				$yhtiot = " yhtio in ($yhtiot) ";

				$konsyhtiot = substr($konsyhtiot,0,-1);
				$konsyhtiot = " yhtio in ($konsyhtiot) ";
			}
			else {
				$yhtiot = "'".$kukarow["yhtio"]."'";
				$yhtiot = " yhtio in ($yhtiot) ";

				$konsyhtiot = "'".$kukarow["yhtio"]."'";
				$konsyhtiot = " yhtio in ($konsyhtiot) ";
			}

			//Katsotaan valitut varastot
			$query = "	SELECT *
						FROM varastopaikat
						WHERE $konsyhtiot";
			$vtresult = mysql_query($query) or pupe_error($query);

			$varastot 			= "";
			$varastot_yhtiot 	= "";

			while ($vrow = mysql_fetch_array($vtresult)) {
				if ($valitut["VARASTO##$vrow[tunnus]"] == "VARASTO##".$vrow["tunnus"]) {
					$varastot .= "'".$vrow["tunnus"]."',";
					$varastot_yhtiot .= "'".$vrow["yhtio"]."',";
				}
			}

			$varastot 		 = substr($varastot,0,-1);
			$varastot_yhtiot = substr($varastot_yhtiot,0,-1);

			$paikoittain = $valitut["paikoittain"];

			if ($varastot == "" and $paikoittain != "") {
				echo "<font class='error'>".t("VIRHE: Ajat h�lytysraportin varastopaikoittain, mutta et valinnut yht��n varastoa.")."</font>";
				exit;
			}

			if ($varastot == "") {
				echo "<font class='error'>".t("VIRHE: Ajat h�lytysraportin, mutta et valinnut yht��n varastoa.")."</font>";
				exit;
			}

			if ($abcrajaus != "") {
				// katotaan JT:ss� olevat tuotteet
				$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
							FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
							JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
							WHERE tilausrivi.$yhtiot
							and tyyppi 	= 'L'
							and var 	= 'J'
							and jt $lisavarattu > 0";
				$vtresult = mysql_query($query) or pupe_error($query);
				$vrow = mysql_fetch_array($vtresult);

				$jt_tuotteet = "''";

				if ($vrow[0] != "") {
					$jt_tuotteet = $vrow[0];
				}

				// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
				$abcjoin = " 	JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (
								abc_aputaulu.yhtio = tuote.yhtio
								and abc_aputaulu.tuoteno = tuote.tuoteno
								and abc_aputaulu.tyyppi = '$abcrajaustapa'
								and (luokka <= '$abcrajaus' or luokka_osasto <= '$abcrajaus' or luokka_try <= '$abcrajaus' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ($jt_tuotteet))) ";
			}
			else {
				$abcjoin = "	LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (
								abc_aputaulu.yhtio = tuote.yhtio
								and abc_aputaulu.tuoteno = tuote.tuoteno
								and abc_aputaulu.tyyppi = '$abcrajaustapa') ";
			}

			$varastot 		 = " HAVING varastopaikat.tunnus in ($varastot) or varastopaikat.tunnus is null ";
			$varastot_yhtiot = " yhtio in ($varastot_yhtiot) ";


			//Tuotekannassa voi olla tuotteen mitat kahdella eri tavalla
			// leveys x korkeus x syvyys
			// leveys x korkeus x pituus
			$query = "	SHOW columns
						FROM tuote
						LIKE 'tuotepituus'";
			$spres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($spres) == 1) {
				$splisa = "tuote.tuotepituus tuotesyvyys";
			}
			else {
				$splisa = "tuote.tuotesyvyys";
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
							tuote.luontiaika,
							tuote.sarjanumeroseuranta,
							tuote.tuotekorkeus,
							tuote.tuoteleveys,
							tuote.tuotemassa,
							$splisa,
							tuote.lyhytkuvaus,
							tuote.hinnastoon
							FROM tuote
							LEFT JOIN korvaavat ON tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno
							$lisaa2
							$abcjoin
							WHERE tuote.$yhtiot
							$lisaa
							and tuote.ei_saldoa = ''
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
							tuote.luontiaika,
							tuote.sarjanumeroseuranta,
							tuote.tuotekorkeus,
							tuote.tuoteleveys,
							tuote.tuotemassa,
							$splisa,
							tuote.lyhytkuvaus,
							tuote.hinnastoon,
							concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali,tuotepaikat.hyllytaso) varastopaikka,
							varastopaikat.tunnus
							FROM tuote
							$lisaa2
							$abcjoin
							JOIN tuotepaikat ON tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno
							LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
							and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
							and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
							LEFT JOIN korvaavat ON tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno
							WHERE tuote.$yhtiot
							$lisaa
							and tuote.ei_saldoa = ''
							$varastot
							order by id, tuote.tuoteno, varastopaikka";
			}
			$res = mysql_query($query) or pupe_error($query);

			//	Oletetaan ett� k�ytt�j� ei halyua/saa ostaa poistuvia tai poistettuja tuotteita!
			if (!isset($valitut["poistetut"])) $valitut["poistetut"] = "checked";
			if (!isset($valitut["poistuvat"])) $valitut["poistuvat"] = "checked";

			if ($valitut["poistetut"] != '' and $valitut["poistuvat"] == '') {
				echo "<font class='message'>".t("Vain aktiiviset tuotteet, poistuvat n�ytet��n").".<br>";
			}
			if ($valitut["poistetut"] != '' and $valitut["poistuvat"] != '') {
				echo "<font class='message'>".t("Vain aktiiviset tuotteet").".<br>";
			}
			if ($valitut["poistetut"] == '' and $valitut["poistuvat"] != '') {
				echo "<font class='message'>".t("Vain aktiiviset tuotteet, poistetut n�ytet��n").".<br>";
			}

			if ($valitut["OSTOTVARASTOITTAIN"] != '') {
				echo "<font class='message'>".t("Tilatut eritell��n varastoittain").".<br>";
			}

			if ($valitut["VAINUUDETTUOTTEET"] != '') {
				echo "<font class='message'>".t("Listaa vain 12kk sis�ll� perustetut tuotteet").".<br>";
			}
			if ($valitut["UUDETTUOTTEET"] != '') {
				echo "<font class='message'>".t("Ei listata 12kk sis�ll� perustettuja tuotteita").".<br>";
			}

			if ($abcrajaus != "") {

				echo "<font class='message'>".t("ABC-luokka tai ABC-osastoluokka tai ABC-tuoteryhm�luokka")." >= $ryhmanimet[$abcrajaus] ".t("tai sit� on j�lkitoimituksessa");

				if ($valitut["VAINUUDETTUOTTEET"] == '' and $valitut["UUDETTUOTTEET"] == '') {
					echo " ".t("tai tuote on perustettu viimeisen 12kk sis�ll�").".<br>";
				}
				else {
					echo ".<br>";
				}
			}

			echo t("Tuotteita")." ".mysql_num_rows($res)." ".t("kpl").".<br>";

			if ($valitut["EHDOTETTAVAT"] != '') {
				echo "<font class='message'>".t("Joista j�tet��n pois ne tuotteet joita ei ehdoteta ostettavaksi").".<br>";
			}

			flush();

			if (include('Spreadsheet/Excel/Writer.php')) {

				//keksit��n failille joku varmasti uniikki nimi:
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
			}

			$rivi 		 = "";
			$excelrivi 	 = 0;
			$excelsarake = 0;

			$rivi .= t("tuoteno")."\t";

			if (isset($workbook)) {
				$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("tuoteno")), $format_bold);
				$excelsarake++;
			}


			if ($paikoittain != '') {
				$rivi .= t("Varastopaikka")."\t";

				if (isset($workbook)) {
					$worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("Varastopaikka")), $format_bold);
					$excelsarake++;
				}
			}

			foreach ($valitut as $val) {
				$rivi .= $sarakkeet[$val];

				if (isset($workbook) and $sarakkeet[$val] != '') {
					$worksheet->writeString($excelrivi, $excelsarake, ucfirst($sarakkeet[$val]), $format_bold);
					$excelsarake++;
				}
			}

			$rivi .= "\r\n";
			$excelrivi++;
			$excelsarake = 0;

			// arvioidaan kestoa
			$ajat      = array();
			$arvio     = array();
			$timeparts = explode(" ",microtime());
			$alkuaika  = $timeparts[1].substr($timeparts[0],1);
			$joukko    = 100; //kuinka monta rivi� otetaan keskiarvoon

			while ($row = mysql_fetch_array($res)) {

				$timeparts = explode(" ",microtime());
				$alku      = $timeparts[1].substr($timeparts[0],1);

				$lisa = "";

				if ($paikoittain != '') {
					$lisa = " and concat_ws(' ',hyllyalue, hyllynro, hyllyvali, hyllytaso)='$row[varastopaikka]' ";
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
				$result   = mysql_query($query) or pupe_error($query);
				$toimirow = mysql_fetch_array($result);


				$row['toimittaja'] 		= $toimirow['toimittaja'];
				$row['osto_era'] 		= $toimirow['osto_era'];
				$row['toim_tuoteno'] 	= $toimirow['toim_tuoteno'];
				$row['toim_nimitys'] 	= $toimirow['toim_nimitys'];
				$row['ostohinta'] 		= $toimirow['ostohinta'];
				$row['tuotekerroin'] 	= $toimirow['tuotekerroin'];

				///* Myydyt kappaleet *///
				$query = "	SELECT
							sum(if (laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) kpl1,
							sum(if (laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,kpl,0)) kpl2,
							sum(if (laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,kpl,0)) kpl3,
							sum(if (laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) kpl4,
							sum(if (laskutettuaika >= '$vva1ed-$kka1ed-$ppa1ed' and laskutettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed' ,kpl,0)) EDkpl1,
							sum(if (laskutettuaika >= '$vva2ed-$kka2ed-$ppa2ed' and laskutettuaika <= '$vvl2ed-$kkl2ed-$ppl2ed' ,kpl,0)) EDkpl2,
							sum(if (laskutettuaika >= '$vva3ed-$kka3ed-$ppa3ed' and laskutettuaika <= '$vvl3ed-$kkl3ed-$ppl3ed' ,kpl,0)) EDkpl3,
							sum(if (laskutettuaika >= '$vva4ed-$kka4ed-$ppa4ed' and laskutettuaika <= '$vvl4ed-$kkl4ed-$ppl4ed' ,kpl,0)) EDkpl4,
							sum(if (laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kate,0)) kate1,
							sum(if (laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,kate,0)) kate2,
							sum(if (laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,kate,0)) kate3,
							sum(if (laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kate,0)) kate4,
							sum(if (laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,rivihinta,0)) rivihinta1,
							sum(if (laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,rivihinta,0)) rivihinta2,
							sum(if (laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,rivihinta,0)) rivihinta3,
							sum(if (laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,rivihinta,0)) rivihinta4
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							WHERE yhtio = '$row[yhtio]'
							and tyyppi	= 'L'
							and tuoteno = '$row[tuoteno]'
							and laskutettuaika >= '$apvm'
							and laskutettuaika <= '$lpvm'
							$lisa";
				$result   = mysql_query($query) or pupe_error($query);
				$laskurow = mysql_fetch_array($result);

				$query = "	SELECT
							sum(if (laadittu >= '$vva1-$kka1-$ppa1 00:00:00' and laadittu <= '$vvl1-$kkl1-$ppl1 23:59:59' and var='P', tilkpl,0)) puutekpl1,
							sum(if (laadittu >= '$vva2-$kka2-$ppa2 00:00:00' and laadittu <= '$vvl2-$kkl2-$ppl2 23:59:59' and var='P', tilkpl,0)) puutekpl2,
							sum(if (laadittu >= '$vva3-$kka3-$ppa3 00:00:00' and laadittu <= '$vvl3-$kkl3-$ppl3 23:59:59' and var='P', tilkpl,0)) puutekpl3,
							sum(if (laadittu >= '$vva4-$kka4-$ppa4 00:00:00' and laadittu <= '$vvl4-$kkl4-$ppl4 23:59:59' and var='P', tilkpl,0)) puutekpl4
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laadittu)
							WHERE yhtio = '$row[yhtio]'
							and tyyppi='L'
							and tuoteno = '$row[tuoteno]'
							and laadittu >= '$apvm 00:00:00'
							and laadittu <= '$lpvm 23:59:59'
							$lisa";
				$result   = mysql_query($query) or pupe_error($query);
				$puuterow = mysql_fetch_array($result);

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

				///* Kulutetut kappaleet *///
				$query = "	SELECT
							sum(if (toimitettuaika >= '$vva1-$kka1-$ppa1 00:00:00' and toimitettuaika <= '$vvl1-$kkl1-$ppl1 23:59:59' ,kpl,0)) kpl1,
							sum(if (toimitettuaika >= '$vva2-$kka2-$ppa2 00:00:00' and toimitettuaika <= '$vvl2-$kkl2-$ppl2 23:59:59' ,kpl,0)) kpl2,
							sum(if (toimitettuaika >= '$vva3-$kka3-$ppa3 00:00:00' and toimitettuaika <= '$vvl3-$kkl3-$ppl3 23:59:59' ,kpl,0)) kpl3,
							sum(if (toimitettuaika >= '$vva4-$kka4-$ppa4 00:00:00' and toimitettuaika <= '$vvl4-$kkl4-$ppl4 23:59:59' ,kpl,0)) kpl4,
							sum(if (toimitettuaika >= '$vva1ed-$kka1ed-$ppa1ed 00:00:00' and toimitettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed 23:59:59' ,kpl,0)) EDkpl1,
							sum(if (toimitettuaika >= '$vva2ed-$kka2ed-$ppa2ed 00:00:00' and toimitettuaika <= '$vvl2ed-$kkl2ed-$ppl2ed 23:59:59' ,kpl,0)) EDkpl2,
							sum(if (toimitettuaika >= '$vva3ed-$kka3ed-$ppa3ed 00:00:00' and toimitettuaika <= '$vvl3ed-$kkl3ed-$ppl3ed 23:59:59' ,kpl,0)) EDkpl3,
							sum(if (toimitettuaika >= '$vva4ed-$kka4ed-$ppa4ed 00:00:00' and toimitettuaika <= '$vvl4ed-$kkl4ed-$ppl4ed 23:59:59' ,kpl,0)) EDkpl4
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laadittu)
							WHERE yhtio = '$row[yhtio]'
							and tyyppi = 'V'
							and tuoteno = '$row[tuoteno]'
							and ((toimitettuaika >= '$apvm 00:00:00' and toimitettuaika <= '$lpvm 23:59:59') or toimitettuaika = '0000-00-00 00:00:00')
							$lisa";
				$result   = mysql_query($query) or pupe_error($query);
				$kulutrow = mysql_fetch_array($result);

				//tilauksessa, ennakkopoistot ja jt	Huom! varastolisa m��ritelty jo aiemmin!
				$query = "	SELECT
							sum(if(tyyppi in ('W','M'), varattu, 0)) valmistuksessa,
							sum(if(tyyppi = 'O', varattu, 0)) tilattu,
							sum(if(tyyppi = 'E', varattu, 0)) ennakot, # toimittamattomat ennakot
							sum(if(tyyppi in ('L','V') and var not in ('P','J','S'), varattu, 0)) ennpois,
							sum(if(tyyppi in ('L','G') and var in ('J','S'), jt $lisavarattu, 0)) jt
							$varastolisa
							FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
							WHERE yhtio = '$row[yhtio]'
		 					and tyyppi in ('L','V','O','G','E','W','M')
							and tuoteno = '$row[tuoteno]'
							and laskutettuaika = '0000-00-00'
							and (varattu+jt > 0)";
				$result = mysql_query($query) or pupe_error($query);
				$ennp   = mysql_fetch_array($result);

				if ($paikoittain == '') {
					// Kaikkien valittujen varastojen paikkojen saldo yhteens�, mukaan tulee my�s aina ne saldot jotka ei kuulu mihink��n varastoalueeseen
					$query = "	SELECT sum(saldo) saldo, varastopaikat.tunnus
								FROM tuotepaikat
								LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
								and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
								WHERE tuotepaikat.$varastot_yhtiot
								and tuotepaikat.tuoteno='$row[tuoteno]'
								GROUP BY varastopaikat.tunnus
								$varastot";
					$result = mysql_query($query) or pupe_error($query);

					$sumsaldo = 0;

					while($saldo = mysql_fetch_array($result)) {
						$sumsaldo += $saldo["saldo"];
					}

					$saldo["saldo"] = $sumsaldo;
				}
				else {
					// Ajetaan varastopaikoittain eli t�ss� on just t�n paikan saldo
					$query = "	SELECT saldo
								from tuotepaikat
								where yhtio = '$row[yhtio]'
								and tuoteno = '$row[tuoteno]'
								$lisa";
					$result = mysql_query($query) or pupe_error($query);
					$saldo = mysql_fetch_array($result);
				}

				// oletuspaikan saldo ja hyllypaikka
				$query = "	SELECT sum(saldo) osaldo, hyllyalue, hyllynro, hyllyvali, hyllytaso
							from tuotepaikat
							where yhtio = '$row[yhtio]'
							and tuoteno = '$row[tuoteno]'
							and oletus  = 'X'
							group by hyllyalue, hyllynro, hyllyvali, hyllytaso";
				$result = mysql_query($query) or pupe_error($query);
				$osaldo = mysql_fetch_array($result);

				if ($row['osto_era']==0) $row['osto_era']=1;

				///* lasketaan ehdotelma, mit� kannattaisi tilata *///
				//mink� kauden mukaan ostoehdotukset lasketaan
				$indeksi    = "kpl".$ostoehdotus;

				//Kausien ero sekunneissa
				$ero = strtotime(${"vvl".$ostoehdotus}."-".${"kkl".$ostoehdotus}."-".${"ppl".$ostoehdotus}) - strtotime(${"vva".$ostoehdotus}."-".${"kka".$ostoehdotus}."-".${"ppa".$ostoehdotus});

				//Kausien ero kuukausissa, t�ss� approximoidaan, ett� kuukausissa on keskim��rin 365/12 p�iv��
				$ero = $ero/60/60/24/(365/12);

				$ehd_kausi1	= "1";
				$ehd_kausi2 = "3";
				$ehd_kausi3 = "4";

				if ($valitut["KAUSI1"] != '') {
					list($al, $lo) = explode("##", $valitut["KAUSI1"]);

					$ehd_kausi1	= $lo;
				}
				if ($valitut["KAUSI2"] != '') {
					list($al, $lo) = explode("##", $valitut["KAUSI2"]);

					$ehd_kausi2	= $lo;
				}
				if ($valitut["KAUSI3"] != '') {
					list($al, $lo) = explode("##", $valitut["KAUSI3"]);

					$ehd_kausi3	= $lo;
				}

				// Laskentakaava:
				// (((myynti + kulutus) / valitun_kauden_pituus_kuukauksissa * varastointitarve_kuukauksissa) - (saldo + tilattu + valmistuksessa - varatut - jt)) / osto_era
				// echo "$row[tuoteno]: ((({$laskurow[$indeksi]} + {$kulutrow[$indeksi]}) / $ero * $ehd_kausi1) - ({$saldo['saldo']} + {$ennp['tilattu']} + {$ennp['valmistuksessa']} - {$ennp['ennpois']} - {$ennp['jt']})) / {$row['osto_era']}<br>";

				$ostettava1kk  = ((($laskurow[$indeksi] + $kulutrow[$indeksi]) / $ero * $ehd_kausi1) - ($saldo['saldo'] + $ennp['tilattu'] + $ennp['valmistuksessa'] - $ennp['ennpois'] - $ennp['jt'])) / $row['osto_era'];
				$ostettava3kk  = ((($laskurow[$indeksi] + $kulutrow[$indeksi]) / $ero * $ehd_kausi2) - ($saldo['saldo'] + $ennp['tilattu'] + $ennp['valmistuksessa'] - $ennp['ennpois'] - $ennp['jt'])) / $row['osto_era'];
				$ostettava4kk  = ((($laskurow[$indeksi] + $kulutrow[$indeksi]) / $ero * $ehd_kausi3) - ($saldo['saldo'] + $ennp['tilattu'] + $ennp['valmistuksessa'] - $ennp['ennpois'] - $ennp['jt'])) / $row['osto_era'];

				$ostettavahaly = ($row['halytysraja'] - ($saldo['saldo'] + $ennp['tilattu'] + $ennp['valmistuksessa'] - $ennp['ennpois'] - $ennp['jt'])) / $row['osto_era'];

				// jos tuotteella on joku ostoer� py�ristell��n ylosp�in, ett� tilataan aina toimittajan haluama m��r�
				if ($ostettava1kk > 0)	$ostettava1kk = ceil($ostettava1kk) * $row['osto_era'];
				else 					$ostettava1kk = 0;

				if ($ostettava3kk > 0)	$ostettava3kk = ceil($ostettava3kk) * $row['osto_era'];
				else 					$ostettava3kk = 0;

				if ($ostettava4kk > 0)	$ostettava4kk = ceil($ostettava4kk) * $row['osto_era'];
				else 					$ostettava4kk = 0;

				if ($ostettavahaly > 0)	$ostettavahaly = ceil($ostettavahaly) * $row['osto_era'];
				else 					$ostettavahaly = 0;

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
					$asosresult = mysql_query($query) or pupe_error($query);
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
					$asresult = mysql_query($query) or pupe_error($query);
					$asrow = mysql_fetch_array($asresult);
				}

				if ($valitut['EHDOTETTAVAT'] == '' or $ostettavahaly > 0 or $ostettava4kk > 0) {

					// kirjotettaan rivi
					$rivi .= "\"$row[tuoteno]\"\t";

					if (isset($workbook)) {
						$worksheet->writeString($excelrivi, $excelsarake, $row["tuoteno"], $format_center);
						$excelsarake++;
					}

					if ($paikoittain != '') {
						$rivi .= "\"$row[varastopaikka]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["varastopaikka"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE1"] != '') {
						$rivi .= "\"$row[osasto]\"\t";

						if (isset($workbook)) {
							$worksheet->write($excelrivi, $excelsarake, $row["osasto"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE2"] != '') {
						$rivi .= "\"$row[try]\"\t";

						if (isset($workbook)) {
							$worksheet->write($excelrivi, $excelsarake, $row["try"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE3"] != '') {
						$rivi .= "\"$row[tuotemerkki]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["tuotemerkki"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE3B"] != '') {
						$rivi .= "\"$row[malli]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["malli"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE3C"] != '') {
						$rivi .= "\"$row[mallitarkenne]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["mallitarkenne"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE4"] != '') {
						$rivi .= "\"$row[tahtituote]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["tahtituote"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE4B"] != '') {
						$rivi .= "\"$row[status]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["status"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE4C"] != '') {
						$rivi .= "\"".$ryhmanimet[$row["abcluokka"]]."\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $ryhmanimet[$row["abcluokka"]]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE4CA"] != '') {
						$rivi .= "\"".$ryhmanimet[$row["abcluokka_osasto"]]."\"\t";

						if (isset($workbook)) {
							$worksheet->write($excelrivi, $excelsarake, $ryhmanimet[$row["abcluokka_osasto"]]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE4CB"] != '') {
						$rivi .= "\"".$ryhmanimet[$row["abcluokka_try"]]."\"\t";

						if (isset($workbook)) {
							$worksheet->write($excelrivi, $excelsarake, $ryhmanimet[$row["abcluokka_try"]]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE4D"] != '') {
						if ($row["luontiaika"] == "0000-00-00 00:00:00") $row["luontiaika"] = "";

						$rivi .= "\"$row[luontiaika]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["luontiaika"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE5"] != '') {
						$rivi .= str_replace(".",",",$saldo['saldo'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $saldo["saldo"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE6"] != '') {
						$rivi .= str_replace(".",",",$row['halytysraja'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row["halytysraja"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE7"] != '') {
						$rivi .= str_replace(".",",",$ennp['tilattu'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["tilattu"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE7B"] != '') {
						$rivi .= str_replace(".",",",$ennp['valmistuksessa'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["valmistuksessa"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE8"] != '') {
						$rivi .= str_replace(".",",",$ennp['ennpois'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["ennpois"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE9"] != '') {
						$rivi .= str_replace(".",",",$ennp['jt'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["jt"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE10"] != '') {
						$rivi .= "$ostettava1kk\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $ostettava1kk, $format_bold);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE11"] != '') {
						$rivi .= "$ostettava3kk\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $ostettava3kk, $format_bold);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE12"] != '') {
						$rivi .= "$ostettava4kk\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $ostettava4kk, $format_bold);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE13"] != '') {
						$rivi .= "$ostettavahaly\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $ostettavahaly);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE14"] != '') {
						$rivi .= str_replace(".",",",$row['osto_era'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row["osto_era"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE15"] != '') {
						$rivi .= str_replace(".",",",$row['myynti_era'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row["myynti_era"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE16"] != '') {
						$rivi .= "\"$row[toimittaja]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["toimittaja"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE17"] != '') {
						$rivi .= "\"$row[toim_tuoteno]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["toim_tuoteno"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE18"] != '') {
						$rivi .= "\"".t_tuotteen_avainsanat($row, 'nimitys')."\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, t_tuotteen_avainsanat($row, 'nimitys'));
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE18B"] != '') {
						$rivi .= "\"$row[toim_nimitys]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["toim_nimitys"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE18C"] != '') {
						$rivi .= "\"$row[kuvaus]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["kuvaus"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE18D"] != '') {
						$rivi .= "\"$row[lyhytkuvaus]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["lyhytkuvaus"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE18E"] != '') {
						$rivi .= "\"$row[tuotekorkeus]\"\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row["tuotekorkeus"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE18F"] != '') {
						$rivi .= "\"$row[tuoteleveys]\"\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row["tuoteleveys"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE18G"] != '') {
						$rivi .= "\"$row[tuotesyvyys]\"\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row["tuotesyvyys"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE18H"] != '') {
						$rivi .= "\"$row[tuotemassa]\"\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row["tuotemassa"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE18I"] != '') {
						$rivi .= "\"$row[hinnastoon]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["hinnastoon"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE19"] != '') {
						$rivi .= str_replace(".",",",$row['ostohinta'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row["ostohinta"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE20"] != '') {
						$rivi .= str_replace(".",",",$row['myyntihinta'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row["myyntihinta"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE20Z"] != '') {
						$epakur = "";
						if ($row['epakurantti25pvm'] != '0000-00-00') $epakur = $row['epakurantti25pvm'];

						$rivi .= "$epakur\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $epakur);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE21"] != '') {
						$epakur = "";
						if ($row['epakurantti50pvm'] != '0000-00-00') $epakur = $row['epakurantti50pvm'];

						$rivi .= "$epakur\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $epakur);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE21B"] != '') {
						$epakur = "";
						if ($row['epakurantti75pvm'] != '0000-00-00') $epakur = $row['epakurantti75pvm'];

						$rivi .= "$epakur\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $epakur);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE22"] != '') {
						$epakur = "";
						if ($row['epakurantti100pvm'] != '0000-00-00') $epakur = $row['epakurantti100pvm'];

						$rivi .= "$epakur\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $epakur);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE23"] != '') {
						$rivi .= str_replace(".",",",$osaldo['osaldo'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $osaldo["osaldo"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE24"] != '') 	{
						$rivi .= "\"$osaldo[hyllyalue]-$osaldo[hyllynro]-$osaldo[hyllyvali]-$osaldo[hyllytaso]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, "$osaldo[hyllyalue]-$osaldo[hyllynro]-$osaldo[hyllyvali]-$osaldo[hyllytaso]");
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE25"] != '') 	{
						$rivi .= str_replace(".",",",$puuterow['puutekpl1'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $puuterow["puutekpl1"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE26"] != '') {
						$rivi .= str_replace(".",",",$puuterow['puutekpl2'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $puuterow["puutekpl2"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE27"] != '') {
						$rivi .= str_replace(".",",",$puuterow['puutekpl3'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $puuterow["puutekpl3"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE28"] != '') {
						$rivi .= str_replace(".",",",$puuterow['puutekpl4'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $puuterow["puutekpl4"]);
							$excelsarake++;
						}
					}

					//Myydyt kappaleet
					if ($valitut["SARAKE29"] != '') {
						$rivi .= str_replace(".",",",$laskurow['kpl1'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kpl1"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE30"] != '') {
						$rivi .= str_replace(".",",",$laskurow['kpl2'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kpl2"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE31"] != '') {
						$rivi .= str_replace(".",",",$laskurow['kpl3'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kpl3"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE32"] != '') {
						$rivi .= str_replace(".",",",$laskurow['kpl4'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kpl4"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE33"] != '') {
						$rivi .= str_replace(".",",",$laskurow['EDkpl1'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["EDkpl1"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE34"] != '') {
						$rivi .= str_replace(".",",",$laskurow['EDkpl2'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["EDkpl2"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE35"] != '') {
						$rivi .= str_replace(".",",",$laskurow['EDkpl3'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["EDkpl3"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE36"] != '') {
						$rivi .= str_replace(".",",",$laskurow['EDkpl4'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["EDkpl4"]);
							$excelsarake++;
						}
					}

					//Kulutetut kappaleet
					if ($valitut["SARAKE29K"] != '') {
						$rivi .= str_replace(".",",",$kulutrow['kpl1'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["kpl1"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE30K"] != '') {
						$rivi .= str_replace(".",",",$kulutrow['kpl2'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["kpl2"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE31K"] != '') {
						$rivi .= str_replace(".",",",$kulutrow['kpl3'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["kpl3"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE32K"] != '') {
						$rivi .= str_replace(".",",",$kulutrow['kpl4'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["kpl4"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE33K"] != '') {
						$rivi .= str_replace(".",",",$kulutrow['EDkpl1'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["EDkpl1"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE34K"] != '') {
						$rivi .= str_replace(".",",",$kulutrow['EDkpl2'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["EDkpl2"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE35K"] != '') {
						$rivi .= str_replace(".",",",$kulutrow['EDkpl3'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["EDkpl3"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE36K"] != '') {
						$rivi .= str_replace(".",",",$kulutrow['EDkpl4'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $kulutrow["EDkpl4"]);
							$excelsarake++;
						}
					}


					if ($valitut["SARAKE37"] != '') {
						$rivi .= str_replace(".",",",$laskurow['kate1'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kate1"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE38"] != '') {
						$rivi .= str_replace(".",",",$laskurow['kate2'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kate2"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE39"] != '') {
						$rivi .= str_replace(".",",",$laskurow['kate3'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kate3"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE40"] != '') {
						$rivi .= str_replace(".",",",$laskurow['kate4'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $laskurow["kate4"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE41"] != '') {
						$rivi .= str_replace(".",",",$katepros1)."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $katepros1);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE42"] != '') {
						$rivi .= str_replace(".",",",$katepros2)."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $katepros2);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE43"] != '') {
						$rivi .= str_replace(".",",",$katepros3)."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $katepros3);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE44"] != '') {
						$rivi .= str_replace(".",",",$katepros4)."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $katepros4);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE45"] != '') {
						$rivi .= str_replace(".",",",$row['tuotekerroin'])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $row["tuotekerroin"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE46"] != '') {
						$rivi .= str_replace(".",",",$ennp["ennakot"])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["ennakot"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE47"] != '') {
						$rivi .= "\"$row[aleryhma]\"\t";

						if (isset($workbook)) {
							$worksheet->writeString($excelrivi, $excelsarake, $row["aleryhma"]);
							$excelsarake++;
						}
					}

					if ($valitut["SARAKE47B"] != '') {
						$kehahin = 0;

						//Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksil�iden ostohinnoista (ostetut yksil�t jotka eiv�t viel� ole myyty(=laskutettu))
						if ($row["sarjanumeroseuranta"] == "S") {
							$query	= "	SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
										FROM sarjanumeroseuranta
										LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
										LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
										WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
										and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
										and sarjanumeroseuranta.myyntirivitunnus != -1
										and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
										and tilausrivi_osto.laskutettuaika != '0000-00-00'";
							$sarjares = mysql_query($query) or pupe_error($query);
							$sarjarow = mysql_fetch_array($sarjares);

							$kehahin = sprintf('%.2f', $sarjarow["kehahin"]);
						}
						else {
							$kehahin = sprintf('%.2f', $row["kehahin"]);
						}

						if     ($row['epakurantti100pvm']!= '0000-00-00') $kehahin = 0;
						elseif ($row['epakurantti75pvm'] != '0000-00-00') $kehahin = round($kehahin * 0.25, 6);
						elseif ($row['epakurantti50pvm'] != '0000-00-00') $kehahin = round($kehahin * 0.5,  6);
						elseif ($row['epakurantti25pvm'] != '0000-00-00') $kehahin = round($kehahin * 0.75, 6);

						$rivi .= str_replace(".",",",$kehahin)."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $kehahin);
							$excelsarake++;
						}
					}

					if ($asiakasosasto != '') {
						if ($valitut["SARAKE48"] != '') {
							$rivi .= str_replace(".",",",$asosrow['kpl1'])."\t";

							if (isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $excelsarake, $asosrow['kpl1']);
								$excelsarake++;
							}
						}

						if ($valitut["SARAKE49"] != '') 	{
							$rivi .= str_replace(".",",",$asosrow['kpl2'])."\t";

							if (isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $excelsarake, $asosrow['kpl2']);
								$excelsarake++;
							}
						}

						if ($valitut["SARAKE50"] != '') {
							$rivi .= str_replace(".",",",$asosrow['kpl3'])."\t";

							if (isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $excelsarake, $asosrow['kpl3']);
								$excelsarake++;
							}
						}

						if ($valitut["SARAKE51"] != '') {
							$rivi .= str_replace(".",",",$asosrow['kpl4'])."\t";

							if (isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $excelsarake, $asosrow['kpl4']);
								$excelsarake++;
							}
						}

					}

					if ($asiakasno != '') {
						if ($valitut["SARAKE52"] != '') {
							$rivi .= str_replace(".",",",$asrow['kpl1'])."\t";

							if (isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $excelsarake, $asrow['kpl1']);
								$excelsarake++;
							}
						}

						if ($valitut["SARAKE53"] != '') {
							$rivi .= str_replace(".",",",$asrow['kpl2'])."\t";

							if (isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $excelsarake, $asrow['kpl2']);
								$excelsarake++;
							}
						}

						if ($valitut["SARAKE54"] != '') {
							$rivi .= str_replace(".",",",$asrow['kpl3'])."\t";

							if (isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $excelsarake, $asrow['kpl3']);
								$excelsarake++;
							}
						}

						if ($valitut["SARAKE55"] != '') {
							$rivi .= str_replace(".",",",$asrow['kpl4'])."\t";

							if (isset($workbook)) {
								$worksheet->writeNumber($excelrivi, $excelsarake, $asrow['kpl4']);
								$excelsarake++;
							}
						}
					}

					unset($korvaresult1);
					unset($korvaresult2);
					unset($korvaavat_tunrot);

					//korvaavat tuotteet
					$query  = "	SELECT id
								FROM korvaavat
								WHERE tuoteno	= '$row[tuoteno]'
								and yhtio		= '$row[yhtio]'";
					$korvaresult1 = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($korvaresult1) > 0) {
						$korvarow = mysql_fetch_array($korvaresult1);

						$query  = "	SELECT tuoteno
									FROM korvaavat
									WHERE tuoteno  != '$row[tuoteno]'
									and id			= '$korvarow[id]'
									and yhtio		= '$row[yhtio]'";
						$korvaresult2 = mysql_query($query) or pupe_error($query);

						$korvaavat_tunrot = "";

						//tulostetaan korvaavat
						while ($korvarow = mysql_fetch_array($korvaresult2)) {
							$korvaavat_tunrot .= ",'$korvarow[tuoteno]'";
						}
					}

					if ($valitut["SARAKE64"] != '') {
						$query = "	SELECT count(*) kpl
									FROM yhteensopivuus_tuote
									JOIN yhteensopivuus_rekisteri on (yhteensopivuus_rekisteri.yhtio = yhteensopivuus_tuote.yhtio and yhteensopivuus_rekisteri.autoid = yhteensopivuus_tuote.atunnus)
									WHERE yhteensopivuus_tuote.yhtio='$kukarow[yhtio]'
									and yhteensopivuus_tuote.tuoteno in ('$row[tuoteno]' $korvaavat_tunrot)
									and yhteensopivuus_tuote.tyyppi='HA'";
						$asresult = mysql_query($query) or pupe_error($query);
						$kasrow = mysql_fetch_array($asresult);

						$rivi .= $kasrow['kpl']."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $kasrow['kpl']);
							$excelsarake++;
						}
					}

					//Liitet��nk� my�s tilauttu by varasto
					if (is_resource($osvres)) {
						mysql_data_seek($osvres, 0);

						while ($vrow = mysql_fetch_array($osvres)) {
							$rivi .= str_replace(".",",",$ennp["tilattu_".$vrow["tunnus"]])."\t";

							if (isset($workbook)) {
								$worksheet->write($excelrivi, $excelsarake, $ennp["tilattu_".$vrow["tunnus"]]);
								$excelsarake++;
							}
						}

						$rivi .= str_replace(".",",",$ennp["tilattu_oletus"])."\t";

						if (isset($workbook)) {
							$worksheet->writeNumber($excelrivi, $excelsarake, $ennp["tilattu_oletus"]);
							$excelsarake++;
						}
					}

					if (is_resource($korvaresult2) and mysql_num_rows($korvaresult2) > 0) {

						mysql_data_seek($korvaresult2, 0);

						//tulostetaan korvaavat
						while ($korvarow = mysql_fetch_array($korvaresult2)) {
							// Korvaavien paikkojen valittujen varastojen paikkojen saldo yhteens�, mukaan tulee my�s aina ne saldot jotka ei kuulu mihink��n varastoalueeseen
							$query = "	SELECT sum(saldo) saldo, varastopaikat.tunnus
										FROM tuotepaikat
										LEFT JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
										and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
										and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
										WHERE tuotepaikat.$varastot_yhtiot
										and tuotepaikat.tuoteno='$korvarow[tuoteno]'
										GROUP BY varastopaikat.tunnus
										$varastot";
							$korvasaldoresult = mysql_query($query) or pupe_error($query);

							$korva_sumsaldo = 0;

							while($korvasaldorow = mysql_fetch_array($korvasaldoresult)) {
								$korva_sumsaldo += $korvasaldorow["saldo"];
							}

							$korvasaldorow["saldo"] = $korva_sumsaldo;

							// Saldolaskentaa tulevaisuuteen
							$query = "	SELECT
										sum(if(tyyppi in ('O','W','M'), varattu, 0)) tilattu,
										sum(if(tyyppi in ('L','V'), varattu, 0)) varattu
										FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
										WHERE yhtio = '$row[yhtio]'
										and tyyppi in ('L','V','O','W','M')
										and tuoteno = '$korvarow[tuoteno]'
										and varattu > 0";
							$presult = mysql_query($query) or pupe_error($query);
							$prow = mysql_fetch_array($presult);

							//Korvaavien myynnnit
							$query  = "	SELECT
										sum(if (laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) kpl1,
										sum(if (laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,kpl,0)) kpl2,
										sum(if (laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,kpl,0)) kpl3,
										sum(if (laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) kpl4
										FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
										WHERE yhtio = '$row[yhtio]'
										and tyyppi = 'L'
										and tuoteno = '$korvarow[tuoteno]'
										and laskutettuaika >= '$apvm'
										and laskutettuaika <= '$lpvm'";
							$asresult = mysql_query($query) or pupe_error($query);
							$kasrow = mysql_fetch_array($asresult);

							if ($valitut["SARAKE56"] != '') {
								$rivi .= "\"$korvarow[tuoteno]\"\t";

								if (isset($workbook)) {
									$worksheet->writeString($excelrivi, $excelsarake, $korvarow["tuoteno"], $format_center);
									$excelsarake++;
								}
							}

							if ($valitut["SARAKE57"] != '') {
								$rivi .= str_replace(".",",",$korvasaldorow['saldo'])."\t";

								if (isset($workbook)) {
									$worksheet->writeNumber($excelrivi, $excelsarake, $korvasaldorow["saldo"]);
									$excelsarake++;
								}
							}

							if ($valitut["SARAKE58"] != '') {
								$rivi .= str_replace(".",",",$prow['varattu'])."\t";

								if (isset($workbook)) {
									$worksheet->writeNumber($excelrivi, $excelsarake, $prow["varattu"]);
									$excelsarake++;
								}
							}

						    if ($valitut["SARAKE59"] != '') {
								$rivi .= str_replace(".",",",$prow['tilattu'])."\t";

								if (isset($workbook)) {
									$worksheet->writeNumber($excelrivi, $excelsarake, $prow["tilattu"]);
									$excelsarake++;
								}
							}


							if ($valitut["SARAKE60"] != '') {
								$rivi .= str_replace(".",",",$kasrow['kpl1'])."\t";

								if (isset($workbook)) {
									$worksheet->writeNumber($excelrivi, $excelsarake, $kasrow["kpl1"]);
									$excelsarake++;
								}
							}

							if ($valitut["SARAKE61"] != '') {
								$rivi .= str_replace(".",",",$kasrow['kpl2'])."\t";

								if (isset($workbook)) {
									$worksheet->writeNumber($excelrivi, $excelsarake, $kasrow["kpl2"]);
									$excelsarake++;
								}
							}

							if ($valitut["SARAKE62"] != '') {
								$rivi .= str_replace(".",",",$kasrow['kpl3'])."\t";

								if (isset($workbook)) {
									$worksheet->writeNumber($excelrivi, $excelsarake, $kasrow["kpl3"]);
									$excelsarake++;
								}
							}

							if ($valitut["SARAKE63"] != '') {
								$rivi .= str_replace(".",",",$kasrow['kpl4'])."\t";

								if (isset($workbook)) {
									$worksheet->writeNumber($excelrivi, $excelsarake, $kasrow["kpl4"]);
									$excelsarake++;
								}
							}
						}
					}


					$rivi .= "\r\n";
					$excelrivi++;
					$excelsarake = 0;
				}

				// tehd��n arvio kauan t�m� kest��.. wau! :)
				if (count($arvio)<=$joukko) {
					$timeparts = explode(" ",microtime());
					$endtime   = $timeparts[1].substr($timeparts[0],1);
					$arvio[]   = round($endtime-$alkuaika,4);

					if (count($arvio)==$joukko)
					{
						$ka   = array_sum($arvio) / count($arvio);
						$aika = round(mysql_num_rows($res) * $ka, 0);
						echo t("Arvioitu ajon kesto")." $aika sec.<br>";
						flush();
					}
					else
					{
						$timeparts = explode(" ",microtime());
						$alkuaika  = $timeparts[1].substr($timeparts[0],1);
					}
				}

				$timeparts = explode(" ",microtime());
				$loppu     = $timeparts[1].substr($timeparts[0],1);
				$ajat[]    = round($loppu-$alku,4);
			}

			flush();

			$timeparts = explode(" ",microtime());
			$endtime   = $timeparts[1].substr($timeparts[0],1);
			$total     = round($endtime-$starttime,0);

			echo "<br>";

			if (isset($workbook)) {
				$workbook->close();

				echo "<table>";
				echo "<tr><th>".t("Tallenna raportti (xls)").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='H�lytysraportti.xls'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table><br>";
			}


			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$txtnimi = md5(uniqid(mt_rand(), true)).".txt";

			file_put_contents("/tmp/$txtnimi", $rivi);

			echo "<table>";
			echo "<tr><th>".t("Tallenna raportti (txt)").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='H�lytysraportti.txt'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$txtnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";

			echo "<table>";
			echo "<tr><th>".t("Tallenna raportti (csv)").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='H�lytysraportti.csv'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$txtnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";


			//N�� muuttujat voi olla aika isoja joten unsetataan ne
			unset($rivi);

			foreach ($ajat as $aika) {
				$yht+=$aika;
			}

			$yht=@round($yht/count($ajat),5);

			echo t("Ajo kesti")." $total sec.</font> <font class='info'>($yht ".t("sec/tuoterivi").")</font><br><br>";

			$osasto		= '';
			$tuoryh		= '';
			$ytunnus	= '';
			$tuotemerkki= '';
			$tee		= 'X';
		}

		if ($tee == "" or $tee == "JATKA") {
			if (isset($muutparametrit)) {
				list($osasto,$tuoryh,$ytunnus,$tuotemerkki,$asiakasosasto,$asiakasno,$toimittaja) = explode('#', $muutparametrit);
			}

			$muutparametrit = $osasto."#".$tuoryh."#".$ytunnus."#".$tuotemerkki."#".$asiakasosasto."#".$asiakasno."#";

			if ($tuoryh !='' or $osasto != '' or $ytunnus != '' or $tuotemerkki != '') {
				if ($ytunnus != '' and !isset($ylatila)) {

					require("../inc/kevyt_toimittajahaku.inc");

					if ($ytunnus != '') {
						$tee = "JATKA";
					}
				}
				elseif ($ytunnus != '' and isset($ylatila)) {
					$tee = "JATKA";
				}
				elseif ($tuoryh !='' or $osasto != '' or $tuotemerkki != '') {
					$tee = "JATKA";
				}
				else {
					$tee = "";
				}
			}

			$muutparametrit = $osasto."#".$tuoryh."#".$ytunnus."#".$tuotemerkki."#".$asiakasosasto."#".$asiakasno."#";

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

			echo "	<form action='$PHP_SELF' method='post' autocomplete='off'>
					<br>".t("Valitse v�hint��n yksi seuraavista:")."
					<table>
					<tr><th>".t("Osasto")."</th><td>";

			// tehd��n avainsana query
			$sresult = t_avainsana("OSASTO");

			echo "<select name='osasto'>";
			echo "<option value=''>".t("N�yt� kaikki")."</option>";

			while ($srow = mysql_fetch_array($sresult)) {
				$sel = '';
				if ($osasto == $srow["selite"]) {
					$sel = "selected";
				}
				echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
			}
			echo "</select>";


			echo "</td></tr>
					<tr><th>".t("Tuoteryhm�")."</th><td>";

			//Tehd��n osasto & tuoteryhm� pop-upit
			// tehd��n avainsana query
			$sresult = t_avainsana("TRY");

			echo "<select name='tuoryh'>";
			echo "<option value=''>".t("N�yt� kaikki")."</option>";

			while ($srow = mysql_fetch_array($sresult)) {
				$sel = '';
				if ($tuoryh == $srow["selite"]) {
					$sel = "selected";
				}
				echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
			}
			echo "</select>";


			echo "</td></tr>
					<tr><th>".t("Tuotemerkki")."</th><td>";

			//Tehd��n osasto & tuoteryhm� pop-upit
			$sresult = t_avainsana("TUOTEMERKKI");

			echo "<select name='tuotemerkki'>";
			echo "<option value=''>".t("N�yt� kaikki")."</option>";

			while ($srow = mysql_fetch_array($sresult)) {
				$sel = '';
				if ($tuotemerkki == $srow["selite"]) {
					$sel = "selected";
				}
				echo "<option value='$srow[selite]' $sel>$srow[selite]</option>";
			}
			echo "</select>";


			echo "</td></tr>
					<tr><th>".t("Toimittaja")."</th><td><input type='text' size='20' name='ytunnus' value='$ytunnus'></td></tr>";

					// katotaan onko abc aputaulu rakennettu
					$query  = "select count(*) from abc_aputaulu where yhtio='$kukarow[yhtio]' and tyyppi in ('TK','TR','TP')";
					$abcres = mysql_query($query) or pupe_error($query);
					$abcrow = mysql_fetch_array($abcres);

					// jos on niin n�ytet��n t�ll�nen vaihtoehto
					if ($abcrow[0] > 0) {
						echo "<tr><th>".t("ABC-luokkarajaus/rajausperuste")."</th><td>";

						$sel = array();
						$sel[$abcrajaus] = "SELECTED";

						echo "<select name='abcrajaus'>
						<option value=''>Ei rajausta</option>
						<option $sel[0] value='0'>Luokka A-30</option>
						<option $sel[1] value='1'>Luokka B-20 ja paremmat</option>
						<option $sel[2] value='2'>Luokka C-15 ja paremmat</option>
						<option $sel[3] value='3'>Luokka D-15 ja paremmat</option>
						<option $sel[4] value='4'>Luokka E-10 ja paremmat</option>
						<option $sel[5] value='5'>Luokka F-05 ja paremmat</option>
						<option $sel[6] value='6'>Luokka G-03 ja paremmat</option>
						<option $sel[7] value='7'>Luokka H-02 ja paremmat</option>
						<option $sel[8] value='8'>Luokka I-00 ja paremmat</option>
						</select>";

						$sel = array();
						$sel[$abcrajaustapa] = "SELECTED";

						echo "<select name='abcrajaustapa'>
						<option $sel[TK] value='TK'>Myyntikate</option>
						<option $sel[TR] value='TR'>Myyntirivit</option>
						<option $sel[TP] value='TP'>Myyntikappaleet</option>
						</select>
						</td></tr>";

					}

			echo "<tr><td colspan='2' class='back'><br></td></tr>";
			echo "<tr><td colspan='2' class='back'>".t("Valitse jos haluat tulostaa asiakaan myynnit").":</td></tr>";

			echo "<tr><th>".t("Asiakasosasto")."</th><td>";

			$query = "	SELECT distinct osasto
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and osasto!=''
						order by osasto+0";
			$sresult = mysql_query($query) or pupe_error($query);

			echo "<select name='asiakasosasto'>";
			echo "<option value=''>".t("N�yt� kaikki")."</option>";

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
					<input type='Submit' value = '".t("Jatka")."'>
					</form>";

		}

		if ($tee == "JATKA" or $tee == "RAPORTOI") {
			if ($ostoehdotus == "1")
				$kl = "CHECKED";
			if ($ostoehdotus == "2")
				$k2 = "CHECKED";
			if ($ostoehdotus == "3")
				$k3 = "CHECKED";
			if ($ostoehdotus == "4")
				$k4 = "CHECKED";

			if (!isset($ostoehdotus))
				$k3 = "CHECKED";

			if ($tuoryh != '') {
				// tehd��n avainsana query
				$sresult = t_avainsana("TRY", "", "and avainsana.selite ='$tuoryh'");
				$srow = mysql_fetch_array($sresult);
			}
			if ($osasto != '') {
				// tehd��n avainsana query
				$sresult = t_avainsana("OSASTO", "", "and avainsana.selite ='$osasto'");
				$trow = mysql_fetch_array($sresult);
			}
			if ($toimittajaid != '') {
				$query = "	SELECT nimi
							FROM toimi
							WHERE yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
				$sresult = mysql_query($query) or pupe_error($query);
				$trow1 = mysql_fetch_array($sresult);
			}
			if ($asiakasid != '') {
				$query = "	SELECT nimi
							FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and tunnus='$asiakasid'";
				$sresult = mysql_query($query) or pupe_error($query);
				$trow2 = mysql_fetch_array($sresult);
			}

			if ($rappari != $edrappari) {
				unset($valitut);
				$tee = "JATKA";
			}

			if (!isset($edrappari) or ($rappari == "" and $edrappari != "")) {
				$defaultit = "P��LLE";
			}

			$abcnimi = $ryhmanimet[$abcrajaus];

			echo "	<form action='$PHP_SELF' method='post' autocomplete='off'>
					<input type='hidden' name='tee' value='RAPORTOI'>
					<input type='hidden' name='osasto' value='$osasto'>
					<input type='hidden' name='tuoryh' value='$tuoryh'>
					<input type='hidden' name='ytunnus' value='$ytunnus'>
					<input type='hidden' name='edrappari' value='$rappari'>
					<input type='hidden' name='toimittajaid' value='$toimittajaid'>
					<input type='hidden' name='asiakasid' value='$asiakasid'>
					<input type='hidden' name='tuotemerkki' value='$tuotemerkki'>
					<input type='hidden' name='asiakasno' value='$asiakasno'>
					<input type='hidden' name='asiakasosasto' value='$asiakasosasto'>
					<input type='hidden' name='abcrajaus' value='$abcrajaus'>
					<input type='hidden' name='abcrajaustapa' value='$abcrajaustapa'>

					<table>
					<tr><th>".t("Osasto")."</th><td colspan='3'>$osasto $trow[selitetark]</td></tr>
					<tr><th>".t("Tuoteryhm�")."</th><td colspan='3'>$tuoryh $srow[selitetark]</td></tr>
					<tr><th>".t("Toimittaja")."</th><td colspan='3'>$ytunnus $trow1[nimi]</td></tr>
					<tr><th>".t("Tuotemerkki")."</th><td colspan='3'>$tuotemerkki</td></tr>
					<tr><th>".t("ABC-rajaus")."</th><td colspan='3'>$abcnimi</td></tr>
					<tr><th>".t("Asiakasosasto")."</th><td colspan='3'>$asiakasosasto</td></tr>
					<tr><th>".t("Asiakas")."</th><td colspan='3'>$asiakasno $trow2[nimi]</td></tr>";

			echo "	<tr><td class='back'><br></td></tr>";

			echo "	<tr>
					<td class='back'></td><th colspan='3'>".t("Alkup�iv�m��r� (pp-kk-vvvv)")."</th>
					<td class='back'></td><th colspan='3'>".t("Loppup�iv�m��r� (pp-kk-vvvv)")."</th>
					<th>".t("Ostoehdotus")."</th></tr>";

			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji	= 'HALYRAP'
						and selite	= '$rappari'
						and selitetark like 'PAIVAM##%'";
			$sresult = mysql_query($query) or pupe_error($query);

			while($srow = mysql_fetch_array($sresult)) {
				list($etuliite, $nimi, $paivamaara) = explode('##',$srow["selitetark"]);

				${$nimi} = $paivamaara;
			}

			echo "	<tr><th>".t("Kausi 1")."</th>
					<td><input type='text' name='ppa1' value='$ppa1' size='5'></td>
					<td><input type='text' name='kka1' value='$kka1' size='5'></td>
					<td><input type='text' name='vva1' value='$vva1' size='5'></td>
					<td class='back'> - </td>
					<td><input type='text' name='ppl1' value='$ppl1' size='5'></td>
					<td><input type='text' name='kkl1' value='$kkl1' size='5'></td>
					<td><input type='text' name='vvl1' value='$vvl1' size='5'></td>
					<td><input type='radio' name='ostoehdotus' value='1' $k1></td></tr>";

			echo "	<tr><th>".t("Kausi 2")."</th>
					<td><input type='text' name='ppa2' value='$ppa2' size='5'></td>
					<td><input type='text' name='kka2' value='$kka2' size='5'></td>
					<td><input type='text' name='vva2' value='$vva2' size='5'></td>
					<td class='back'> - </td>
					<td><input type='text' name='ppl2' value='$ppl2' size='5'></td>
					<td><input type='text' name='kkl2' value='$kkl2' size='5'></td>
					<td><input type='text' name='vvl2' value='$vvl2' size='5'></td>
					<td><input type='radio' name='ostoehdotus' value='2' $k2></td></tr>";

			echo "	<tr><th>".t("Kausi 3")."</th>
					<td><input type='text' name='ppa3' value='$ppa3' size='5'></td>
					<td><input type='text' name='kka3' value='$kka3' size='5'></td>
					<td><input type='text' name='vva3' value='$vva3' size='5'></td>
					<td class='back'> - </td>
					<td><input type='text' name='ppl3' value='$ppl3' size='5'></td>
					<td><input type='text' name='kkl3' value='$kkl3' size='5'></td>
					<td><input type='text' name='vvl3' value='$vvl3' size='5'></td>
					<td><input type='radio' name='ostoehdotus' value='3' $k3></td></tr>";

			echo "	<tr><th>".t("Kausi 4")."</th>
					<td><input type='text' name='ppa4' value='$ppa4' size='5'></td>
					<td><input type='text' name='kka4' value='$kka4' size='5'></td>
					<td><input type='text' name='vva4' value='$vva4' size='5'></td>
					<td class='back'> - </td>
					<td><input type='text' name='ppl4' value='$ppl4' size='5'></td>
					<td><input type='text' name='kkl4' value='$kkl4' size='5'></td>
					<td><input type='text' name='vvl4' value='$vvl4' size='5'></td>
					<td><input type='radio' name='ostoehdotus' value='4' $k4></td></tr>";


			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji	= 'HALYRAP'
						and selite	= '$rappari'
						and selitetark = 'TALLENNAPAIVAM'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);


			$chk = "";
			if (($srow["selitetark"] == "TALLENNAPAIVAM" and $tee == "JATKA") or $valitut["TALLENNAPAIVAM"] != '') {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("Tallenna p�iv�m��r�t:")."</th><td colspan='9'><input type='checkbox' name='valitut[TALLENNAPAIVAM]' value='TALLENNAPAIVAM' $chk></td></tr>";
			echo "	<tr><td class='back'><br></td></tr>";

			//Ostokausivalinnat
			$kaudet_oletus = array("A" => 1, "B" => 3, "C" => 4);
			$kaudet_kaikki = array(1,2,3,4,5,6,7,8,9,10,11,12,24);

			$kaudet = array();
			$kaulas = 1;

			foreach ($kaudet_oletus as $kaunimi => $kausi1) {

				echo "<tr><th>Ostoehdotus $kaunimi:</th><td colspan='3'><select name='valitut[KAUSI$kaulas]'>";

				foreach ($kaudet_kaikki as $kausi2) {
					$query = "	SELECT selitetark
								FROM avainsana
								WHERE yhtio = '$kukarow[yhtio]'
								and laji	= 'HALYRAP'
								and selite	= '$rappari'
								and selitetark = 'KAUSI$kaulas##$kausi2'";
					$sresult = mysql_query($query) or pupe_error($query);
					$srow = mysql_fetch_array($sresult);

					$chk = "";

					if (("KAUSI".$kaulas."##".$kausi2 == $srow["selitetark"] and $tee == "JATKA") or $valitut["KAUSI$kaulas##$kausi2"] != '' or ($kausi1 == $kausi2 and $srow["selitetark"] == "")) {
						$chk = "SELECTED";
					}

					echo "<option value='KAUSI$kaulas##$kausi2' $chk onchange='submit();'>$kausi2</option>";
				}

				echo "</select> ".t("kuukauden tarve")."</td></tr>";

				$kaulas++;
			}

			echo "	<tr><td class='back'><br></td></tr>";



			//Yhti�valinnat
			$query	= "	SELECT distinct yhtio, nimi
						from yhtio
						where konserni = '$yhtiorow[konserni]' and konserni != ''";
			$presult = mysql_query($query) or pupe_error($query);

			$yhtiot 	= "";
			$konsyhtiot = "";
			$vlask 		= 0;

			if (mysql_num_rows($presult) > 0) {
				while ($prow = mysql_fetch_array($presult)) {

					$query = "	SELECT selitetark
								FROM avainsana
								WHERE yhtio = '$kukarow[yhtio]'
								and laji	= 'HALYRAP'
								and selite	= '$rappari'
								and selitetark = 'YHTIO##$prow[yhtio]'";
					$sresult = mysql_query($query) or pupe_error($query);
					$srow = mysql_fetch_array($sresult);

					$chk = "";
					if (("YHTIO##".$prow["yhtio"] == $srow["selitetark"] and $tee == "JATKA") or $valitut["YHTIO##$prow[yhtio]"] != '' or $prow["yhtio"] == $kukarow["yhtio"]) {
						$chk = "CHECKED";
						$yhtiot .= "'".$prow["yhtio"]."',";
					}

					if ($vlask == 0) {
						echo "<tr><th rowspan='".mysql_num_rows($presult)."'>Huomioi yhti�n myynnit:</th>";
					}
					else {
						echo "<tr>";
					}

					echo "<td colspan='3'><input type='checkbox' name='valitut[YHTIO##$prow[yhtio]]' value='YHTIO##$prow[yhtio]' $chk onClick='submit();'> $prow[nimi]</td></tr>";

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
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'HALYRAP'
						and selite	= '$rappari'
						and selitetark = 'PAIKOITTAIN'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);

			$chk = "";
			if (($srow["selitetark"] == "PAIKOITTAIN" and $tee == "JATKA") or $valitut["paikoittain"] != '') {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("Aja raportti varastopaikoittain")."</th><td colspan='3'><input type='checkbox' name='valitut[paikoittain]' value='PAIKOITTAIN' $chk></td></tr>";


			//N�ytet��nk� poistetut tuotteet
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'HALYRAP'
						and selite	= '$rappari'
						and selitetark = 'POISTETUT'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);

			$chk = "";
			if (($srow["selitetark"] == "POISTETUT" and $tee == "JATKA") or $valitut["poistetut"] != '' or $defaultit == "P��LLE") {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("�l� n�yt� poistettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[poistetut]' value='POISTETUT' $chk></td></tr>";

			//N�ytet��nk� poistetut tuotteet
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'HALYRAP'
						and selite	= '$rappari'
						and selitetark = 'POISTUVAT'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);

			$chk = "";
			if (($srow["selitetark"] == "POISTUVAT" and $tee == "JATKA") or $valitut["poistuvat"] != '' or $defaultit == "P��LLE") {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("�l� n�yt� poistuvia tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[poistuvat]' value='POISTUVAT' $chk></td></tr>";


			//N�ytet��nk� poistetut tuotteet
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'HALYRAP'
						and selite	= '$rappari'
						and selitetark = 'EIHINNASTOON'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);

			$chk = "";
			if (($srow["selitetark"] == "EIHINNASTOON" and $tee == "JATKA") or $valitut["EIHINNASTOON"] != '' or $defaultit == "P��LLE") {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("�l� n�yt� tuotteita joita ei n�ytet� hinnastossa")."</th><td colspan='3'><input type='checkbox' name='valitut[EIHINNASTOON]' value='EIHINNASTOON' $chk></td></tr>";

			//N�ytet��nk� ei varastoitavat tuotteet
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'HALYRAP'
						and selite	= '$rappari'
						and selitetark = 'EIVARASTOITAVA'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);

			$chk = "";
			if (($srow["selitetark"] == "EIVARASTOITAVA" and $tee == "JATKA") or $valitut["EIVARASTOITAVA"] != '') {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("�l� n�yt� tuotteita joita ei varastoida")."</th><td colspan='3'><input type='checkbox' name='valitut[EIVARASTOITAVA]' value='EIVARASTOITAVA' $chk></td></tr>";

			//N�ytet��nk� poistuvat tuotteet
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'HALYRAP'
						and selite	= '$rappari'
						and selitetark = 'EHDOTETTAVAT'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);

			$chk = "";
			if (($srow["selitetark"] == "EHDOTETTAVAT" and $tee == "JATKA") or $valitut["EHDOTETTAVAT"] != '') {
				$chk = "CHECKED";
			}

			echo "<tr><th>".t("N�yt� vain ostettavaksi ehdotettavat rivit")."</th><td colspan='3'><input type='checkbox' name='valitut[EHDOTETTAVAT]' value='EHDOTETTAVAT' $chk></td></tr>";


			//N�ytet��nk� ostot varastoittain
			$query = "	SELECT selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'HALYRAP'
						and selite	= '$rappari'
						and selitetark = 'OSTOTVARASTOITTAIN'";
			$sresult = mysql_query($query) or pupe_error($query);
			$srow = mysql_fetch_array($sresult);

			$chk = "";
			if (($srow["selitetark"] == "OSTOTVARASTOITTAIN" and $tee == "JATKA") or $valitut["OSTOTVARASTOITTAIN"] != '') {
				$chk = "CHECKED";
			}
			echo "<tr><th>".t("N�yt� tilatut varastoittain")."</th><td colspan='3'><input type='checkbox' name='valitut[OSTOTVARASTOITTAIN]' $chk></td></tr>";

			if ($abcrajaus != "") {

				echo "<tr><td class='back'><br></td></tr>";
				echo "<tr><th colspan='4'>".t("ABC-rajaus")." $ryhmanimet[$abcrajaus]</th></tr>";

				//n�ytet��nk� uudet tuotteet
				$query = "	SELECT selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji = 'HALYRAP'
							and selite	= '$rappari'
							and selitetark = 'UUDETTUOTTEET'";
				$sresult = mysql_query($query) or pupe_error($query);
				$srow = mysql_fetch_array($sresult);

				$chk = "";
				if (($srow["selitetark"] == "UUDETTUOTTEET" and $tee == "JATKA") or $valitut["UUDETTUOTTEET"] != '') {
					$chk = "CHECKED";
				}

				echo "<tr><th>".t("�l� listaa 12kk sis�ll� perustettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='valitut[UUDETTUOTTEET]' value='UUDETTUOTTEET' $chk></td></tr>";

				//n�ytet��nk� uudet tuotteet
				$query = "	SELECT selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji = 'HALYRAP'
							and selite	= '$rappari'
							and selitetark = 'VAINUUDETTUOTTEET'";
				$sresult = mysql_query($query) or pupe_error($query);
				$srow = mysql_fetch_array($sresult);

				$chk = "";
				if (($srow["selitetark"] == "VAINUUDETTUOTTEET" and $tee == "JATKA") or $valitut["VAINUUDETTUOTTEET"] != '') {
					$chk = "CHECKED";
				}

				echo "<tr><th>".t("Listaa vain 12kk sis�ll� perustetut tuotteet")."</th><td colspan='3'><input type='checkbox' name='valitut[VAINUUDETTUOTTEET]' value='VAINUUDETTUOTTEET' $chk></td></tr>";
			}

			echo "<tr><td class='back'><br></td></tr>";


			//Valitaan varastot joiden saldot huomioidaan
			//Tutkitaan onko k�ytt�j� klikannut useampaa yhti�t�
			if ($konsyhtiot  != '') {
				$konsyhtiot = " yhtio in (".$konsyhtiot.") ";
			}
			else {
				$konsyhtiot = " yhtio = '$kukarow[yhtio]' ";
			}

			$query = "	SELECT *
						FROM varastopaikat
						WHERE $konsyhtiot
						ORDER BY yhtio, nimitys";
			$vtresult = mysql_query($query) or pupe_error($query);

			$vlask = 0;

			while ($vrow = mysql_fetch_array($vtresult)) {
				$query = "	SELECT selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji = 'HALYRAP'
							and selite	= '$rappari'
							and selitetark = 'VARASTO##$vrow[tunnus]'";
				$sresult = mysql_query($query) or pupe_error($query);
				$srow = mysql_fetch_array($sresult);

				$chk = "";
				if (("VARASTO##".$vrow["tunnus"] == $srow["selitetark"]  and $tee == "JATKA") or $valitut["VARASTO##$vrow[tunnus]"] != '' or ($defaultit == "P��LLE" and $vrow["yhtio"] == $kukarow["yhtio"])) {
					$chk = "CHECKED";
				}

				if ($vlask == 0) {
					echo "<tr><th rowspan='".mysql_num_rows($vtresult)."'>".t("Huomioi saldot varastossa:")."</th>";
				}
				else {
					echo "<tr>";
				}

				echo "<td colspan='3'><input type='checkbox' name='valitut[VARASTO##$vrow[tunnus]]' value='VARASTO##$vrow[tunnus]' $chk> $vrow[nimitys] ($vrow[yhtio])</td></tr>";

				$vlask++;
			}


			echo "</table><br><br>";
			echo "<table>";
			echo "<tr><th colspan='4'>".t("Omat h�lytysraportit")."</th></tr>";
			echo "<tr><th>".t("Luo uusi oma raportti").":</th><td colspan='3'><input type='text' size='40' name='uusirappari' value=''></td></tr>";
			echo "<tr><th>".t("Valitse raportti").":</th><td colspan='3'>";

			//Haetaan tallennetut h�lyrapit
			$query = "	SELECT distinct selite, concat('(',replace(selite, '##',') ')) nimi
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji = 'HALYRAP'
						ORDER BY selite";
			$sresult = mysql_query($query) or pupe_error($query);

			echo "<select name='rappari' onchange='submit()'>";
			echo "<option value=''>".t("N�yt� kaikki")."</option>";

			while ($srow = mysql_fetch_array($sresult)) {

				$sel = '';
				if ($rappari == $srow["selite"]) {
					$sel = "selected";
				}

				echo "<option value='$srow[selite]' $sel>$srow[nimi]</option>";
			}
			echo "</select>";

			echo "</td></tr>";

			$lask = 0;
			echo "<tr>";

			foreach($sarakkeet as $key => $sarake) {

				$query = "	SELECT selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji	= 'HALYRAP'
							and selite	= '$rappari'
							and selitetark = '$key'";
				$sresult = mysql_query($query) or pupe_error($query);

				$sel = "";
				if (mysql_num_rows($sresult) == 1 or $rappari == "") {
					$sel = "CHECKED";
				}

				if ($lask % 4 == 0 and $lask != 0) {
					echo "</tr><tr>";
				}

				echo "<td><input type='checkbox' name='valitut[$key]' value='$key' $sel>".ucfirst($sarake)."</td>";
				$lask++;
			}

			echo "</tr>";
			echo "</table>";
			echo "<br>
				<input type='Submit' name='RAPORTOI' value = '".t("Aja h�lytysraportti")."'>
				</form>";
		}
		require ("../inc/footer.inc");
	}

?>