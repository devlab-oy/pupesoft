<?php

	if (isset($_POST["tee"])) {
		if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
		if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}

	if (isset($tee) and $tee == "TILAA_AJAX") {
		require_once("inc/tilaa_ajax.inc");
	}

	if ($tee == "PAIVITA_AJAX") {

		$query = "	UPDATE tuotteen_toimittajat SET
					pakkauskoko  = '$pakkauskoot',
					toimitusaika = '$toimitusajat',
					muuttaja	 = '$kukarow[kuka]',
					muutospvm	 = now()
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tuoteno'
					and liitostunnus  = '$toimittaja'";
		$result   = pupe_query($query);

		$query = "	UPDATE tuote SET
					varmuus_varasto = '$varmuusvarastot',
					status 			= '$varastoitavat',
					muuttaja		= '$kukarow[kuka]',
					muutospvm		= now()
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tuoteno'";
		$result   = pupe_query($query);

		echo json_encode('ok');
		exit;
	}

	echo "<font class='head'>".t("Ostoehdotus")."</font><hr>";

	$useampi_yhtio = 0;

	if (is_array($valitutyhtiot)) {
		foreach ($valitutyhtiot as $yhtio) {
			$yhtiot .= "'$yhtio',";
			$useampi_yhtio++;
		}
		$yhtiot = substr($yhtiot, 0, -1);
	}

	if ($yhtiot == "") {
		$yhtiot = "'$kukarow[yhtio]'";
		$useampi_yhtio = 1;
	}

	// Jos jt-rivit varaavat saldoa niin se vaikuttaa asioihin
	if ($yhtiorow["varaako_jt_saldoa"] != "") {
		$lisavarattu = " + tilausrivi.varattu";
	}
	else {
		$lisavarattu = "";
	}

	function myynnit($myynti_varasto = '', $myynti_maa = '') {

		// otetaan kaikki muuttujat mukaan funktioon mitä on failissakin
		extract($GLOBALS);

		$laskuntoimmaa = "";
		$returnstring1 = 0;
		$returnstring2 = 0;

		$varastotapa = " JOIN varastopaikat USE INDEX (PRIMARY) ON varastopaikat.yhtio = tilausrivi.yhtio
						 and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))
						 and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))";

		if ($myynti_varasto != "") {
			$varastotapa .= " and varastopaikat.tunnus = '$myynti_varasto' ";
		}
		elseif ($erikoisvarastot != "") {
			$varastotapa .= " and varastopaikat.tyyppi = '' ";
		}
		else {
			$varastotapa = "";
		}

		if ($myynti_maa != "") {
			$laskuntoimmaa = " and lasku.toim_maa = '$myynti_maa' ";
		}

		// tutkaillaan myynti
		$query = "	SELECT
					sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) kpl4,
					sum(if((tilausrivi.tyyppi = 'L' or tilausrivi.tyyppi = 'V') and tilausrivi.var not in ('P','J','S'), tilausrivi.varattu, 0)) ennpois,
					sum(if(tilausrivi.tyyppi = 'L' and tilausrivi.var in ('J','S'), tilausrivi.jt $lisavarattu, 0)) jt,
					sum(if(tilausrivi.tyyppi = 'E', tilausrivi.varattu, 0)) ennakko
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
					JOIN lasku USE INDEX (PRIMARY) on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus $laskuntoimmaa)
					JOIN asiakas USE INDEX (PRIMARY) on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus $lisaa3)
					$varastotapa
					WHERE tilausrivi.yhtio in ($yhtiot)
					and tilausrivi.tyyppi in ('L','V','E')
					and tilausrivi.tuoteno = '$row[tuoteno]'
					and ((tilausrivi.laskutettuaika >= '$vva4-$kka4-$ppa4' and tilausrivi.laskutettuaika <= '$vvl4-$kkl4-$ppl4') or tilausrivi.laskutettuaika = '0000-00-00')";
		$result   = pupe_query($query);
		$laskurow = mysql_fetch_assoc($result);

		// Myydyt kappaleet
		$returnstring2 += (float) $laskurow['kpl4'];
		$returnstring1 += (float) ($laskurow['ennpois'] + $laskurow['jt']);

		return array($returnstring1, $returnstring2);
	}

	function saldot($myynti_varasto = '', $myynti_maa = '') {
		// otetaan kaikki muuttujat mukaan funktioon mitä on failissakin
		extract($GLOBALS);

		$varastotapa  = "";
		$returnstring = 0;

		if ($myynti_varasto != "") {
			$varastotapa = " and varastopaikat.tunnus = '$myynti_varasto' ";
		}
		elseif ($erikoisvarastot != "") {
			$varastotapa .= " and varastopaikat.tyyppi = '' ";
		}

		if ($myynti_maa != "") {
			$varastotapa .= " and varastopaikat.maa = '$myynti_maa' ";
		}

		// Kaikkien valittujen varastojen saldo per maa
		$query = "	SELECT ifnull(sum(saldo),0) saldo, ifnull(sum(halytysraja),0) halytysraja
					FROM tuotepaikat
					JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					$varastotapa
					WHERE tuotepaikat.yhtio in ($yhtiot)
					and tuotepaikat.tuoteno = '$row[tuoteno]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			while ($varrow = mysql_fetch_assoc($result)) {
				$returnstring += (float) $varrow['saldo'];
			}
		}
		else {
			$returnstring = 0;
		}

		return $returnstring;
	}

	function ostot($myynti_varasto = '', $myynti_maa = '') {

		// otetaan kaikki muuttujat mukaan funktioon mitä on failissakin
		extract($GLOBALS);

		$returnstring = 0;

		$varastotapa  = "	JOIN varastopaikat USE INDEX (PRIMARY) ON varastopaikat.yhtio = tilausrivi.yhtio
						 	and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))
						 	and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))";

		if ($myynti_varasto != "") {
			$varastotapa .= " and varastopaikat.tunnus = '$myynti_varasto' ";
		}
		elseif ($erikoisvarastot != "" and $myynti_maa == "") {
			$query    = "SELECT group_concat(tunnus) varastot from varastopaikat where yhtio in ($yhtiot) and tyyppi = ''";
			$result   = pupe_query($query);
			$laskurow = mysql_fetch_assoc($result);

			if ($laskurow['varastot'] != "") {
				$varastotapa .= " and varastopaikat.tunnus in ($laskurow[varastot]) ";
			}
		}
		elseif ($myynti_maa != "") {
			$query    = "SELECT group_concat(tunnus) varastot from varastopaikat where yhtio in ($yhtiot) and maa = '$myynti_maa'";

			if ($erikoisvarastot != "") {
				$query .= " and tyyppi = '' ";
			}

			$result   = pupe_query($query);
			$laskurow = mysql_fetch_assoc($result);

			if ($laskurow['varastot'] != "") {
				$varastotapa .= " and varastopaikat.tunnus in ($laskurow[varastot]) ";
			}
		}
		else {
			$varastotapa = "";
		}

		//tilauksessa
		$query = "	SELECT sum(tilausrivi.varattu) tilattu
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
					$varastotapa
					WHERE tilausrivi.yhtio in ($yhtiot)
					and tilausrivi.tyyppi  = 'O'
					and tilausrivi.tuoteno = '$row[tuoteno]'
					and tilausrivi.varattu > 0";
		$result = pupe_query($query);
		$ostorow = mysql_fetch_assoc($result);

		// tilattu kpl
		$returnstring += (float) $ostorow['tilattu'];

		return $returnstring;
	}

	// org_rajausta tarvitaan yhdessä selectissä joka triggeröi taas toisen asian.
	$org_rajaus = $abcrajaus;
	list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

	if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";

	list($ryhmanimet, $ryhmaprossat, $kiertonopeus_tavoite, $palvelutaso_tavoite, $varmuusvarasto_pv, $toimittajan_toimitusaika_pv) = hae_ryhmanimet($abcrajaustapa);

	// Tarvittavat päivämäärät
	if (!isset($kka4)) $kka4 = date("m",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
	if (!isset($vva4)) $vva4 = date("Y",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
	if (!isset($ppa4)) $ppa4 = date("d",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
	if (!isset($kkl4)) $kkl4 = date("m");
	if (!isset($vvl4)) $vvl4 = date("Y");
	if (!isset($ppl4)) $ppl4 = date("d");

	// katsotaan tarvitaanko mennä toimittajahakuun
	if (($ytunnus != "" and $toimittajaid == "") or (isset($edytunnus) and $edytunnus != "" and $edytunnus != $ytunnus)) {

		if (isset($edytunnus) and $edytunnus != "" and $edytunnus != $ytunnus) {
			$toimittajaid = 0;
		}

		$muutparametrit = "";

		unset($_POST["toimittajaid"]);
		unset($_POST["edytunnus"]);
		unset($_POST["ytunnus"]);

		foreach ($_POST as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $a => $b) {
					$muutparametrit .= $key."[".$a."]=".$b."##";
				}
			}
			else {
				$muutparametrit .= $key."=".$value."##";
			}
		}

		require ("inc/kevyt_toimittajahaku.inc");

		if ($toimittajaid == 0) {
			$tee = "";
		}
	}

	if (isset($muutparametrit) and $toimittajaid > 0) {
		foreach (explode("##", $muutparametrit) as $muutparametri) {
			list($a, $b) = explode("=", $muutparametri);

			if (strpos($a, "[") !== FALSE) {
				$i = substr($a, strpos($a, "[")+1, strpos($a, "]")-(strpos($a, "[")+1));
				$a = substr($a, 0, strpos($a, "["));

				${$a}[$i] = $b;
			}
			else {
				${$a} = $b;
			}
		}
	}

	// tehdään itse raportti
	if ($tee == "RAPORTOI" and isset($ehdotusnappi)) {

		enable_ajax();

		echo "<script type=\"text/javascript\" charset=\"utf-8\">

			var tilaatuote = function() {
				if ($(this).attr(\"disabled\") == undefined) {
					var submitid 		= $(this).attr(\"id\");
					var osat 	 		= submitid.split(\"_\");
					var tuoteno 		= $(\"#tuoteno_\"+osat[1]).html();
					var toimittaja 		= $(\"#toimittaja_\"+osat[1]).html();
					var maara 			= $(\"#ostettavat_\"+osat[1]).val();
					var valittuvarasto	= 0;

					if (\"$toim\" == \"KK\") {
						valittuvarasto = $(\"#valitutvarastot_valittu\").val();
					}

					$.post('{$_SERVER['SCRIPT_NAME']}',
						{ 	tee: 'TILAA_AJAX',
							tuoteno: tuoteno,
							toimittaja: toimittaja,
							maara: maara,
							valittuvarasto: valittuvarasto,
							no_head: 'yes',
							ohje: 'off' },
						function(return_value) {
							var message = jQuery.parseJSON(return_value);

							if (message == \"ok\") {
								$(\"#\"+submitid).val('".t("Tilattu")."').attr('disabled', true);
								$(\"#ostettavat_\"+osat[1]).attr('disabled', true);
							}
						}
					);
				}
			}

			var paivitatuote = function() {
				var submitid = $(this).attr(\"id\");
				var osat 	 = submitid.split(\"_\");

				var tuoteno 		= $(\"#tuoteno_\"+osat[1]).html();
				var toimittaja 		= $(\"#toimittaja_\"+osat[1]).html();
				var varmuusvarastot	= $(\"#varmuusvarastot_\"+osat[1]).val();
				var pakkauskoot 	= $(\"#pakkauskoot_\"+osat[1]).val();
				var toimitusajat 	= $(\"#toimitusajat_\"+osat[1]).val();
				var varastoitavat 	= $(\"#varastoitavat_\"+osat[1]).val();

				$.post('{$_SERVER['SCRIPT_NAME']}',
					{ 	tee: 'PAIVITA_AJAX',
						tuoteno: tuoteno,
						toimittaja: toimittaja,
						varmuusvarastot: varmuusvarastot,
						pakkauskoot: pakkauskoot,
						toimitusajat: toimitusajat,
						varastoitavat: varastoitavat,
						no_head: 'yes',
						ohje: 'off' },
					function(return_value) {
						var message = jQuery.parseJSON(return_value);

						if (message == \"ok\") {
							$(\"#paivitetty_\"+osat[1]).html(' ".t("Tiedot päivitetty")."!');
						}
					}
				);
			}

			$('.tilaa').live('click', tilaatuote);

			$('#tilaakaikki').live('click', function(){
				$('.tilaa').each(tilaatuote);
				$('#tilaakaikki').val('".t("Tilattu")."').attr('disabled', true);
			});

			$('.paivita').live('click', paivitatuote);

			</script>";

		$lisaa  = ""; // tuote-rajauksia
		$lisaa2 = ""; // toimittaja-rajauksia
		$lisaa3 = ""; // asiakas-rajauksia

		$paivitys_mul_osasto = $mul_osasto;
		$paivitys_mul_try    = $mul_try;

		if (is_array($mul_osasto) and count($mul_osasto) > 0) {
			$sel_osasto = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_osasto))."')";
			$lisaa .= " and tuote.osasto in $sel_osasto ";
		}
		if (is_array($mul_try) and count($mul_try) > 0) {
			$sel_tuoteryhma = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_try))."')";
			$lisaa .= " and tuote.try in $sel_tuoteryhma ";
		}
		if ($tuotemerkki != '') {
			$lisaa .= " and tuote.tuotemerkki = '$tuotemerkki' ";
		}
		if ($poistetut != '') {
			$lisaa .= " and tuote.status != 'P' ";
		}
		if ($poistuva != '') {
			$lisaa .= " and tuote.status != 'X' ";
		}
		if ($eihinnastoon != '') {
			$lisaa .= " and tuote.hinnastoon != 'E' ";
		}
		if ($varastointi == 'vainvarastoitavat') {
			$lisaa .= " and tuote.status != 'T' ";
		}
		if ($varastointi == 'vaineivarastoitavat') {
			$lisaa .= " and tuote.status = 'T' ";
		}
		if ($vainuudet != '') {
			$lisaa .= " and tuote.luontiaika >= date_sub(current_date, interval 12 month) ";
		}
		if ($eiuusia != '') {
			$lisaa .= " and tuote.luontiaika < date_sub(current_date, interval 12 month) ";
		}
		if ($toimittajaid != '') {
			$lisaa2 .= " JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid') ";
		}
		if ($eliminoikonserni != '') {
			$lisaa3 .= " and asiakas.konserniyhtio = '' ";
		}

		$valvarasto = "";

		if (isset($valitutvarastot) and (int) $valitutvarastot > 0) {
			$valvarasto = (int) $valitutvarastot;
		}

		// katotaan JT:ssä olevat tuotteet ABC-analyysiä varten, koska ne pitää includata aina!
		$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',') jteet
					FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
					JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
					WHERE tilausrivi.yhtio in ($yhtiot)
					and tyyppi IN  ('L','G')
					and var = 'J'
					and jt $lisavarattu > 0";
		$vtresult = pupe_query($query);
		$vrow = mysql_fetch_assoc($vtresult);

		$jt_tuotteet = "''";

		if ($vrow['jteet'] != "") {
			$jt_tuotteet = $vrow['jteet'];
		}

		if ($abcrajaus != "") {
			// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
			$abcjoin = " 	JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio
							and abc_aputaulu.tuoteno = tuote.tuoteno
							and abc_aputaulu.tyyppi = '$abcrajaustapa'
							and (luokka <= '$abcrajaus' or luokka_osasto <= '$abcrajaus' or luokka_try <= '$abcrajaus' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ($jt_tuotteet))) ";
		}
		else {
			$abcjoin = " LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio and abc_aputaulu.tuoteno = tuote.tuoteno and abc_aputaulu.tyyppi = '$abcrajaustapa') ";
		}

		// tässä haetaan sitten listalle soveltuvat tuotteet
		$query = "	SELECT
					group_concat(DISTINCT tuote.yhtio) yhtio,
					tuote.tuoteno,
					tuote.halytysraja,
					tuote.tahtituote,
					tuote.status,
					tuote.nimitys,
					tuote.myynti_era,
					tuote.myyntihinta,
					tuote.epakurantti25pvm,
					tuote.epakurantti50pvm,
					tuote.epakurantti75pvm,
					tuote.epakurantti100pvm,
					tuote.tuotemerkki,
					tuote.osasto,
					tuote.try,
					tuote.aleryhma,
					tuote.kehahin,
					tuote.varmuus_varasto,
					if(tuote.status != 'T', '".t("Varastoitava")."','".t("Ei varastoida")."') ei_varastoida,
					abc_aputaulu.luokka abcluokka,
					tuote.luontiaika ";

		if ($toim == "KK") {
			$query .= " , sum(tuotepaikat.halytysraja) tpaikka_halyraja
						, sum(tuotepaikat.tilausmaara)  tpaikka_tilausmaara ";
		}

		$query .= " FROM tuote
					$lisaa2
					$abcjoin
					LEFT JOIN korvaavat ON (tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno) ";

		if ($toim == "KK") {
			$query .= " JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
						JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
						and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and varastopaikat.tunnus = '$valvarasto' ";
		}

		$query .= "	WHERE
					tuote.yhtio in ($yhtiot)
					$lisaa
					and tuote.ei_saldoa = ''
					and tuote.tuotetyyppi NOT IN ('A','B')
					and tuote.ostoehdotus = ''
					GROUP BY tuote.tuoteno
					ORDER BY id, tuote.tuoteno, yhtio";
		$res = pupe_query($query);

		flush();

		echo "<form name='ostoehdotuscs' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='paivita'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<table>";
		echo "<tr>";

		if ($useampi_yhtio > 1) {
			echo "<th valign='top'>".t("Yhtiö")."</th>";
		}

		echo "<th valign='top'>".t("Tuoteno")."<br>".t("Nimitys")."</th>";

		if ($toim == "KK") echo "<th valign='top'>".t("Hälytysraja")."</th>";
		else echo "<th valign='top'>".t("Varmuusvarasto")."<br>".t("Tilauspiste")."</th>";

		echo "<th valign='top'>".t("Saldo")."</th>";
		echo "<th valign='top'>".t("Tilattu")."<br>".t("Varattu")."</th>";
		echo "<th valign='top'>".t("Ostoehdotus")."<br>".t("Vuosikulutus")."</th>";

		if ($toim == "KK") echo "<th valign='top'>".t("Tilausmäärä")."<br>".t("Varastoitava")."</th>";
		else echo "<th valign='top'>".t("Pakkauskoko")."<br>".t("Varastoitava")."</th>";

		echo "<th valign='top'>".t("Toimaika")."</th>";

		if ($useampi_yhtio == 1) {
			echo "<th valign='top'>".t("Ostettavat")."</th>";
		}

		echo "</tr>";

		$btl = " style='border-top: 1px solid; border-left: 1px solid;' ";
		$btr = " style='border-top: 1px solid; border-right: 1px solid;' ";
		$bt  = " style='border-top: 1px solid;' ";
		$bb  = " style='border-bottom: 1px solid; margin-bottom: 20px;' ";
		$bbr = " style='border-bottom: 1px solid; border-right: 1px solid; margin-bottom: 20px;' ";
		$bbl = " style='border-bottom: 1px solid; border-left: 1px solid; margin-bottom: 20px;' ";

		$indeksi = 0;
		// loopataan tuotteet läpi
		while ($row = mysql_fetch_assoc($res)) {

			$toimilisa = "";
			if ($toimittajaid != '') $toimilisa = " and liitostunnus = '$toimittajaid' ";

			//hae liitostunnukset
			// haetaan tuotteen toimittajatietoa
			$query = "	SELECT group_concat(tuotteen_toimittajat.toimittaja     order by tuotteen_toimittajat.tunnus separator '/') toimittaja,
						group_concat(distinct tuotteen_toimittajat.osto_era     order by tuotteen_toimittajat.tunnus separator '/') osto_era,
						group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno,
						group_concat(distinct tuotteen_toimittajat.toim_nimitys order by tuotteen_toimittajat.tunnus separator '/') toim_nimitys,
						group_concat(distinct tuotteen_toimittajat.ostohinta    order by tuotteen_toimittajat.tunnus separator '/') ostohinta,
						group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '/') tuotekerroin,
						group_concat(distinct tuotteen_toimittajat.pakkauskoko  order by tuotteen_toimittajat.tunnus separator '/') pakkauskoko,
						group_concat(distinct tuotteen_toimittajat.toimitusaika order by tuotteen_toimittajat.tunnus separator '/') toimitusaika,
						group_concat(distinct tuotteen_toimittajat.tunnus 		order by tuotteen_toimittajat.tunnus separator '/') tunnukset,
						group_concat(distinct tuotteen_toimittajat.liitostunnus order by tuotteen_toimittajat.tunnus separator '/') liitostunnukset
						FROM tuotteen_toimittajat
						WHERE yhtio in ($yhtiot)
						and tuoteno = '$row[tuoteno]'
						$toimilisa";
			$result   = pupe_query($query);
			$toimirow = mysql_fetch_assoc($result);

			// kaunistellaan kenttiä
			if ($row["luontiaika"] == "0000-00-00 00:00:00") $row["luontiaika"] = "";
			if ($row['epakurantti25pvm'] == '0000-00-00')    $row['epakurantti25pvm'] = "";
			if ($row['epakurantti50pvm'] == '0000-00-00')    $row['epakurantti50pvm'] = "";
			if ($row['epakurantti75pvm'] == '0000-00-00')    $row['epakurantti75pvm'] = "";
			if ($row['epakurantti100pvm'] == '0000-00-00')   $row['epakurantti50pvm'] = "";

			// haetaan abc luokille nimet
			$abcnimi  = $ryhmanimet[$row["abcluokka"]];
			$abcnimi2 = $ryhmanimet[$row["abcluokka_osasto"]];
			$abcnimi3 = $ryhmanimet[$row["abcluokka_try"]];

			// sitte vielä totalit
			$saldot = saldot($valvarasto);

			// sitte vielä totalit
			$ostot = ostot($valvarasto);

			// sitte vielä totalit
			list($enp, $vku) = myynnit($valvarasto);

			if ($toim == "KK") {

				// Lisätään varatut tilaukseen ja verrataan tilauspistettä vapaasaldoon
				$vapaasaldo = ($saldot - $enp + $ostot);

				if ($vapaasaldo <= $row["tpaikka_halyraja"]) {

					$lisa = (float) $row["tpaikka_halyraja"] - $vapaasaldo;

					if ($row["status"] != "T" or $lisa != 0) {

						$osto_era = (float) $toimirow['osto_era'];
						$osto_era = $osto_era == 0 ? 1 : $osto_era;

						$ostoehdotus = $row["tpaikka_tilausmaara"] - $vapaasaldo;
						$ostoehdotus = floor($ostoehdotus / $osto_era) * $osto_era;
					}
				}
				else {
					$ostoehdotus = 0;
				}
			}
			else {
				if (($saldot - $enp + $ostot) <= $row["halytysraja"]) {

					if ((float) $kiertonopeus_tavoite[$row["abcluokka"]] == 0) $kiertonopeus_tavoite[$row["abcluokka"]] = 1;

					// Lisätään varatut tilaukseen ja verrataan tilauspistettä vapaasaldoon
					$vapaasaldo = ($saldot - $enp + $ostot);

					$lisa = (float) $row["halytysraja"] - $vapaasaldo;

					if ($row["status"] != "T" or $lisa != 0) {

						$ostoehdotus 		= $row["halytysraja"] - $vapaasaldo;
						$ostoehdotus_lisa 	= (2 * (($vku / $kiertonopeus_tavoite[$row["abcluokka"]]) - $row["varmuus_varasto"]));

						if ($ostoehdotus_lisa > 0) {
							$ostoehdotus += $ostoehdotus_lisa;
						}

						$ostoehdotus = round($ostoehdotus, 2);
					}
				}
				else {
					$ostoehdotus = 0;
				}
			}

			if ($eivarastoivattilaus == '' and $ostot+$enp == 0 and $row["status"] == 'T') {
				$naytetaan = "nope";
			}
			else {
				$naytetaan = "juu";
			}

			if (($ostoehdotus > 0 or $naytakaikkituotteet != '') and ($naytavainmyydyt == '' or $vku+$enp != 0) and $naytetaan == "juu") {

				$toim_tunnukset = explode('/', $toimirow['tunnukset']);
				$toim_liitostunnukset = explode('/', $toimirow['liitostunnukset']);

				echo "<tr>";

				if ($useampi_yhtio > 1) {
					echo "<td valign='top' $btl>$row[yhtio]</td>";
					echo "<td valign='top' $bt><a name='A_$indeksi'></a>$row[tuoteno] <img onclick=\"window.open('{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100,width=1000,height=600'); return false;\" src='{$palvelin2}pics/lullacons/info.png'></td>";
				}
				else {
					echo "<td valign='top' $btl><a name='A_$indeksi'></a>$row[tuoteno] <img onclick=\"window.open('{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100,width=1000,height=600'); return false;\" src='{$palvelin2}pics/lullacons/info.png'></td>";
				}

				if ($toim == "KK") echo "<td valign='top' $bt  align='right'>".(float) $row["tpaikka_halyraja"]."</td>";
				else echo "<td valign='top' $bt  align='right'>".(float) $row["varmuus_varasto"]."</td>";

				echo "<td valign='top' $bt  align='right'>".(float) $saldot."</td>";
				echo "<td valign='top' $bt  align='right'>".(float) $ostot."</td>";

				if ($toimirow["pakkauskoko"] != 0) {
					echo "<td valign='top' $bt  align='right'><font style='color: 00FF00;'>".ceil($ostoehdotus)."</font></td>";
				}
				else {
					echo "<td valign='top' $bt  align='right'><font style='color: 00FF00;'>$ostoehdotus</font></td>";
				}

				if ($toim == "KK") echo "<td valign='top' $bt  align='right'>".(float) $row["tpaikka_tilausmaara"]."</td>";
				else echo "<td valign='top' $bt  align='right'>".(float) $toimirow["pakkauskoko"]."</td>";

				echo "<td valign='top' $btr align='right'>".(float) $toimirow["toimitusaika"]." ".t("pva")."</td>";

				if ($useampi_yhtio == 1 and $toimirow['toimittaja'] != '' and $yhtiorow['yhtio'] == $row['yhtio']) {
					if ($toimirow["pakkauskoko"] != 0) {
						echo "<td valign='top' $bt align='right'><input type='text' size='10' id='ostettavat_$indeksi' name='ostettavat[$row[tuoteno]]' value='".ceil($ostoehdotus)."'></td>";
					}
					else {
						echo "<td valign='top' $bt align='right'><input type='text' size='10' id='ostettavat_$indeksi' name='ostettavat[$row[tuoteno]]' value='$ostoehdotus'></td>";
					}
				}
				else {
					echo "<td></td>";
				}

				echo "</tr>\n";

				echo "\n";

				echo "<tr>";

				if ($useampi_yhtio > 1) {
					echo "<td valign='top' $bbl>$row[yhtio]</td>";
					echo "<td valign='top' $bb><a href=\"javascript:toggleGroup('$indeksi')\">$row[nimitys]</a></td>";
				}
				else {
					echo "<td valign='top' $bbl><a href=\"javascript:toggleGroup('$indeksi')\">$row[nimitys]</a></td>";
				}

				if ($toim == "KK") echo "<td valign='top' $bb align='right'></td>";
				else echo "<td valign='top' $bb align='right'>".(float) $row["halytysraja"]."</td>";

				echo "<td valign='top' $bb></td>";
				echo "<td valign='top' $bb align='right'>".(float) $enp."</td>";
				echo "<td valign='top' $bb align='right'>".(float) $vku."</td>";
				echo "<td valign='top' $bb>$row[ei_varastoida]</td>";
				echo "<td valign='top' $bbr>".tv1dateconv(date("Y-m-d",mktime(0, 0, 0, date("m"), date("d")+$toimirow["toimitusaika"], date("Y"))))."</td>";

				if ($useampi_yhtio == 1 and $toimirow['toimittaja'] != '' and $yhtiorow['yhtio'] == $row['yhtio']) {
					echo "<td valign='top' align='right'>";
					echo "<span id = 'tuoteno_$indeksi' style='visibility:hidden;'>{$row["tuoteno"]}</span>";
					echo "<span id = 'toimittaja_$indeksi' style='visibility:hidden;'>{$toim_liitostunnukset[0]}</span>";
					echo "<input type='button' value='".t("Tilaa tuotetta")."' class='tilaa' id='submit_$indeksi'></td>";
				}
				else {
					echo "<td></td>";
				}

				echo "</tr>";

				if ($kukarow['yhtio'] == $row['yhtio']) {
					echo "<tr style='height: 5px;'><td colspan='8' class='back'>";

					echo "<div id='$indeksi' style='display:none'><table>";
					echo "<tr><th>".t("Varmuusvarasto").":</th><td><input type='text' size='10' id = 'varmuusvarastot_$indeksi' name='varmuus_varastot[$row[tuoteno]]' value='".$row["varmuus_varasto"]."'></td></tr>";
					echo "<tr><th>".t("Pakkauskoko").":</th><td><input type='text' size='10' id = 'pakkauskoot_$indeksi' name='pakkauskoot[$row[tuoteno]]' value='".(float) $toimirow["pakkauskoko"]."'></td></tr>";
					echo "<tr><th>".t("Toimitusaika").":</th><td><input type='text' size='10' id = 'toimitusajat_$indeksi' name='toimitusajat[$row[tuoteno]]' value='".(float) $toimirow["toimitusaika"]."'> ".t("pva").".</td></tr>";
					echo "<tr><th>".t("Varastoitava/status").":</th><td><select id = 'varastoitavat_$indeksi' name='varastoitavat[$row[tuoteno]]'>";

					$query = "	SELECT selite, selitetark
								FROM avainsana
								WHERE yhtio = '$kukarow[yhtio]'
								AND laji = 'S'";
					$status_select_res = pupe_query($query);

					while ($status_select_row = mysql_fetch_assoc($status_select_res)) {
						$sel = '';

						if ($row['status'] == $status_select_row['selite']) $sel = 'SELECTED';

						echo "<option value='$status_select_row[selite]' $sel>";

						if ($status_select_row['selite'] != 'T') {
							echo t("Varastoitava");
						}
						else {
							echo t("Ei varastoitava");
						}

						echo " ",t("Status")," $status_select_row[selitetark]</option>";
					}

					echo "</select></td>";

					echo "<td class='back'>";
					echo "<input type='button' value='".t("Tallenna")."' class='paivita' id='paivitatuote_$indeksi'>";
					echo "<span id = 'paivitetty_$indeksi'></span>";
					echo "</td>";
					echo "</tr>";

					echo "<input type='hidden' name='toimittajien_tunnukset[$row[tuoteno]]' value='$toim_tunnukset[0]'>";
					echo "<input type='hidden' name='toimittajien_liitostunnukset[$row[tuoteno]]' value='$toim_liitostunnukset[0]'>";
					echo "<input type='hidden' name='tuotteen_yhtiot[$row[tuoteno]]' value='$row[yhtio]'>";
					echo "</table>";

					echo "</div>";
					echo "</td></tr>";
				}

				$indeksi++;

			}
		}

		echo "</td></tr></table>";

		echo "<input type='hidden' name='mul_osasto' value='".urlencode(serialize($paivitys_mul_osasto))."'>";
		echo "<input type='hidden' name='mul_try' value='".urlencode(serialize($paivitys_mul_try))."'>";
		echo "<input type='hidden' name='valitutyhtiot' value='".urlencode(serialize($valitutyhtiot))."'>";
		echo "<input type='hidden' name='valitutvarastot' value='$valitutvarastot' id='valitutvarastot_valittu'>";
		echo "<input type='hidden' name='tuotemerkki' value='$tuotemerkki'>";
		echo "<input type='hidden' name='poistetut' value='$poistetut'>";
		echo "<input type='hidden' name='poistuva' value='$poistuva'>";
		echo "<input type='hidden' name='eihinnastoon' value='$eihinnastoon'>";
		echo "<input type='hidden' name='varastointi' value='$varastointi'>";
		echo "<input type='hidden' name='vainuudet' value='$vainuudet'>";
		echo "<input type='hidden' name='eiuusia' value='$eiuusia'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
		echo "<input type='hidden' name='eliminoikonserni' value='$eliminoikonserni'>";
		echo "<input type='hidden' name='abcrajaus' value='$abcrajaus'>";
		echo "<input type='hidden' name='abcrajaustapa' value='$abcrajaustapa'>";
		echo "<input type='hidden' name='eliminoi' value='$eliminoi'>";
		echo "<input type='hidden' name='erikoisvarastot' value='$erikoisvarastot'>";
		echo "<input type='hidden' name='naytakaikkituotteet' value='$naytakaikkituotteet'>";
		echo "<input type='hidden' name='eivarastoivattilaus' value='$eivarastoivattilaus'>";

		echo "<input type='hidden' name='kka4' value='$kka4'>";
		echo "<input type='hidden' name='vva4' value='$vva4'>";
		echo "<input type='hidden' name='ppa4' value='$ppa4'>";
		echo "<input type='hidden' name='kkl4' value='$kkl4'>";
		echo "<input type='hidden' name='vvl4' value='$vvl4'>";
		echo "<input type='hidden' name='ppl4' value='$ppl4'>";

		echo "</form><br>";


		echo "<input type='button' value='".t("Tilaa kaikki")."' id='tilaakaikki'><br><br>";

	}

	// näytetään käyttöliittymä..
	$abcnimi = $ryhmanimet[$abcrajaus];

	echo "	<form method='post' autocomplete='off'>
			<input type='hidden' name='tee' value='RAPORTOI'>
			<input type='hidden' name='toim' value='$toim'>
			<table>";

	// tehdään avainsana query
	$res2 = t_avainsana("OSASTO", "", "", $yhtiot);

	if (mysql_num_rows($res2) > 0) {
		echo "<tr><th>".t("Osasto")."</th><td colspan='3'>";
		echo "<select name='mul_osasto[]' multiple='TRUE' size='10' style='width:100%;'>";

		$mul_check = '';
		if ($mul_osasto!="") {
			if (in_array("PUPEKAIKKIMUUT", $mul_osasto)) {
				$mul_check = 'SELECTED';
			}
		}
		echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoteosastoa")."</option>";

		while ($rivi = mysql_fetch_assoc($res2)) {
			$mul_check = '';

			if (isset($mul_osasto) and $mul_osasto != "") {
				if (in_array($rivi['selite'],$mul_osasto)) {
					$mul_check = 'SELECTED';
				}
			}

			echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
		}

		echo "</select>";
		echo "</td></tr>";
	}

	//Tehdään osasto & tuoteryhmä pop-upit
	$res2 = t_avainsana("TRY", "", "", $yhtiot);

	if (mysql_num_rows($res2) > 0) {
		echo "<tr><th>".t("Tuoteryhmä")."</th><td colspan='3'>";
		echo "<select name='mul_try[]' multiple='TRUE' size='10' style='width:100%;'>";

		$mul_check = '';
		if ($mul_try!="") {
			if (in_array("PUPEKAIKKIMUUT", $mul_try)) {
				$mul_check = 'SELECTED';
			}
		}
		echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoteryhmää")."</option>";

		while ($rivi = mysql_fetch_assoc($res2)) {
			$mul_check = '';
			if ($mul_try!="") {
				if (in_array($rivi['selite'],$mul_try)) {
					$mul_check = 'SELECTED';
				}
			}

			echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
		}

		echo "</select>";
		echo "</td></tr>";
	}

	//Tehdään osasto & tuoteryhmä pop-upit
	$query = "	SELECT distinct tuotemerkki
				FROM tuote
				WHERE yhtio in ($yhtiot) and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = pupe_query($query);

	if (mysql_num_rows($sresult) > 0) {
		echo "<tr><th>".t("Tuotemerkki")."</th><td colspan='3'>";
		echo "<select name='tuotemerkki'>";
		echo "<option value=''>".t("Näytä kaikki")."</option>";

		while ($srow = mysql_fetch_assoc($sresult)) {
			$sel = '';
			if ($tuotemerkki == $srow["tuotemerkki"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[tuotemerkki]' $sel>$srow[tuotemerkki]</option>";
		}
		echo "</select>";
		echo "</td></tr>";
	}

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

	list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

	echo "<tr><th>".t("Toimittaja")."</th><td colspan='3'><input type='text' size='20' name='ytunnus' value='$ytunnus'></td></tr>";
	echo "<input type='hidden' name='edytunnus' value='$ytunnus'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";

	$sel[$varastointi]	= " selected";

	echo "<tr><th>".t("Varastointi")."</th><td colspan='3'>";

	echo "<select name='varastointi'>
		<option value='kaikki' $sel[kaikki]>".t("Kaikki")."</option>
		<option value='vainvarastoitavat' $sel[vainvarastoitavat]>".t("Vain varastoitavat")."</option>
		<option value='vaineivarastoitavat' $sel[vaineivarastoitavat]>".t("Vain  ei varastoitavat")."</option>
		</select>";

	echo "</td></tr>";

	echo "</table><table><br>";

	echo "	<tr>
			<th></th><th colspan='3'>".t("Alkupäivämäärä (pp-kk-vvvv)")."</th>
			<th></th><th colspan='3'>".t("Loppupäivämäärä (pp-kk-vvvv)")."</th>
			</tr>";

	echo "	<tr><th>".t("Kausi")."</th>
			<td><input type='text' name='ppa4' value='$ppa4' size='5'></td>
			<td><input type='text' name='kka4' value='$kka4' size='5'></td>
			<td><input type='text' name='vva4' value='$vva4' size='5'></td>
			<td class='back'>&nbsp;-&nbsp;</td>
			<td><input type='text' name='ppl4' value='$ppl4' size='5'></td>
			<td><input type='text' name='kkl4' value='$kkl4' size='5'></td>
			<td><input type='text' name='vvl4' value='$vvl4' size='5'></td>
			</tr>";

	echo "</table><table><br>";

	$chk = "";
	if ($eliminoi != "") $chk = "checked";
	echo "<tr><th>".t("Älä huomioi konsernimyyntiä")."</th><td colspan='3'><input type='checkbox' name='eliminoi' $chk></td></tr>";

	$chk = "";
	if ($erikoisvarastot != "") $chk = "checked";
	echo "<tr><th>".t("Älä huomioi erikoisvarastoja")."</th><td colspan='3'><input type='checkbox' name='erikoisvarastot' $chk></td></tr>";

	$chk = "";
	if ($poistetut != "") $chk = "checked";
	echo "<tr><th>".t("Älä näytä poistettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistetut' $chk></td></tr>";

	$chk = "";
	if ($poistuva != "") $chk = "checked";
	echo "<tr><th>".t("Älä näytä poistuvia tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistuva' $chk></td></tr>";

	$chk = "";
	if ($eihinnastoon != "") $chk = "checked";
	echo "<tr><th>".t("Älä näytä tuotteita joita ei näytetä hinnastossa")."</th><td colspan='3'><input type='checkbox' name='eihinnastoon' $chk></td></tr>";

	$chk = "";
	if ($naytakaikkituotteet != "") $chk = "checked";
	echo "<tr><th>".t("Näytä myös tuotteet joiden ostoehdotus on nolla")."</th><td colspan='3'><input type='checkbox' name='naytakaikkituotteet' $chk></td></tr>";

	$chk = "";
	if ($naytavainmyydyt != "") $chk = "checked";
	echo "<tr><th>".t("Näytä vain tuotteet joilla on myyntiä")."</th><td colspan='3'><input type='checkbox' name='naytavainmyydyt' $chk></td></tr>";

	$chk = "";
	if ($eivarastoivattilaus != "") $chk = "checked";
	echo "<tr><th>".t("Näytä myös ei varastoitavat tuotteet joilla ei ole tilauksia")."</th><td colspan='3'><input type='checkbox' name='eivarastoivattilaus' $chk></td></tr>";


	if ($abcrajaus != "") {
		echo "<tr><td class='back'><br></td></tr>";
		echo "<tr><th colspan='4'>".t("ABC-rajaus")." $ryhmanimet[$abcrajaus]</th></tr>";

		$chk = "";
		if ($eiuusia != "") $chk = "checked";
		echo "<tr><th>".t("Älä listaa 12kk sisällä perustettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='eiuusia' $chk></td></tr>";

		$chk = "";
		if ($vainuudet != "") $chk = "checked";
		echo "<tr><th>".t("Listaa vain 12kk sisällä perustetut tuotteet")."</th><td colspan='3'><input type='checkbox' name='vainuudet' $chk></td></tr>";
	}

	echo "</table><table><br>";

	// yhtiövalinnat
	$query	= "	SELECT distinct yhtio, nimi
				from yhtio
				where konserni = '$yhtiorow[konserni]'
				and konserni != ''";
	$presult = pupe_query($query);

	$useampi_yhtio = 0;

	if (mysql_num_rows($presult) > 0) {

		echo "<tr><th>".t("Huomioi yhtiön saldot, myynnit ja ostot").":</th></tr>";
		$yhtiot = "";

		while ($prow = mysql_fetch_assoc($presult)) {

			$chk = "";
			if (is_array($valitutyhtiot) and in_array($prow["yhtio"], $valitutyhtiot) != '') {
				$chk = "CHECKED";
				$yhtiot .= "'$prow[yhtio]',";
				$useampi_yhtio++;
			}
			elseif ($prow["yhtio"] == $kukarow["yhtio"]) {
				$chk = "CHECKED";
			}

			echo "<tr><td><input type='checkbox' name='valitutyhtiot[]' value='$prow[yhtio]' $chk onClick='submit();'> $prow[nimi]</td></tr>";
		}

		$yhtiot = substr($yhtiot,0,-1);

		if ($yhtiot == "") $yhtiot = "'$kukarow[yhtio]'";

		echo "</table><table><br>";
	}

	//Valitaan varastot joiden saldot huomioidaan
	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio in ($yhtiot)
				AND tyyppi != 'P'
				ORDER BY yhtio, tyyppi, nimitys";
	$vtresult = pupe_query($query);

	$vlask = 0;

	if (mysql_num_rows($vtresult) > 0) {

		echo "<tr><th>".t("Huomioi saldot, myynnit ja ostot varastoittain:")."</th></tr>";

		while ($vrow = mysql_fetch_assoc($vtresult)) {

			$chk = "";
			if ((isset($valitutvarastot) and $valitutvarastot == $vrow["tunnus"]) or (!isset($valitutvarastot) and $toim == "KK")) {
				$chk = "CHECKED";
				if (!isset($valitutvarastot)) $valitutvarastot = $vrow["tunnus"];
			}

			echo "<tr><td><input type='radio' name='valitutvarastot' value='$vrow[tunnus]' $chk>";

			if ($useampi_yhtio > 1) {
				$query = "SELECT nimi from yhtio where yhtio='$vrow[yhtio]'";
				$yhtres = pupe_query($query);
				$yhtrow = mysql_fetch_assoc($yhtres);
				echo "$yhtrow[nimi]: ";
			}

			echo "$vrow[nimitys] ";

			if ($vrow["tyyppi"] != "") {
				echo " *$vrow[tyyppi]* ";
			}
			if (isset($useampi_maa) and $useampi_maa == 1) {
				echo "(".maa($vrow["maa"]).")";
			}

			echo "</td></tr>";
		}
	}
	else {
		echo "<font class='error'>".t("Yhtään varastoa ei löydy, raporttia ei voida ajaa")."!</font>";
		exit;
	}

	echo "</table>";
	echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Aja ostoehdotus")."'></form>";

	require ("inc/footer.inc");

?>
