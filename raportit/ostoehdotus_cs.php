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

	if ($tee == "TILAA_AJAX") {

		//p�ivitet��n kukarow[kesken] kun k�ytt�j� tekee uutta tilausta
		$query = "	UPDATE kuka
					SET kesken = 0
					WHERE session = '$session'";
		$result = mysql_query($query) or pupe_error($query);

		$kukarow['kesken'] 	= 0;
		$tilausnumero 		= 0;

		$query	= "	SELECT *, tunnus liitostunnus
					from toimi
					where yhtio = '$kukarow[yhtio]'
					and tunnus  = '$toimittaja'";
		$result = mysql_query($query) or pupe_error($query);
		$srow 	= mysql_fetch_array($result);

		// oletuksia
		$varasto 		= 0;
		$toimipiste 	= 0;

		// tarvittavat muuttujat otsikolle
		$ytunnus 		= $srow['ytunnus'];
		$ovttunnus 		= $srow["ovttunnus"];
		$nimi			= $srow["nimi"];
		$nimitark		= $srow["nimitark"];
		$osoite			= $srow["osoite"];
		$postino		= $srow["postino"];
		$postitp		= $srow["postitp"];
		$maa			= $srow["maa"];
		$liitostunnus  	= $toimittaja;
		$maksuteksti	= $srow["maksuteksti"];
		$kuljetus		= $srow["kuljetus"];
		$tnimi			= "";

		$query	= "SELECT nimi from yhteyshenkilo where yhtio='$kukarow[yhtio]' and tyyppi = 'T' and tilausyhteyshenkilo != '' and liitostunnus='$toimittaja'";
		$result = mysql_query($query) or pupe_error($query);
		$yhrow 	= mysql_fetch_array($result);

		$tilausyhteyshenkilo = $yhrow['nimi'];

		$verkkotunnus	= $srow["verkkotunnus"];

		$valkoodi 		= $srow["oletus_valkoodi"];

		//ker�yspvm pit�isi olla -
		if ($kukarow['kesken'] == 0 and $yhtiorow['ostotilaukseen_toimittajan_toimaika'] != '2') {
			$toimpp = date("j");
			$toimkk = date("n");
			$toimvv = date("Y");
		}
		elseif ($kukarow['kesken'] == 0 and $yhtiorow['ostotilaukseen_toimittajan_toimaika'] == '2') {
			$toimittajan_toimaika = date('Y-m-d',time() + $srow["oletus_toimaika"] * 24 * 60 * 60);
			list($toimvv, $toimkk, $toimpp) = explode('-', $toimittajan_toimaika);
		}
		else {
			list($toimvv, $toimkk, $toimpp) = explode('-', $srow["toimaika"]);
			$toimpp = substr($toimpp,0,2);
		}

		//voidaan tarvita
		if ($toimvv == '') {
			$toimpp = date("j");
			$toimkk = date("n");
			$toimvv = date("Y");
		}

		$maksaja 	= $srow["toimitusehto"];
		$myyja		= $kukarow["tunnus"];
		$comments	= "";

		$jatka = "jatka";

		$query = "	SELECT max(tunnus) tunnus
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					AND tila = 'O'
					AND alatila = ''
					AND liitostunnus = '$toimittaja'
					AND myyja = '$kukarow[tunnus]'";
		$lasres = mysql_query($query) or pupe_error($query);
		$lasrow = mysql_fetch_array($lasres);

		if ($lasrow['tunnus'] == 0) {
			require("../tilauskasittely/otsik_ostotilaus.inc");
			$rivi = 1;
		}
		else {
			$tilausnumero = $lasrow['tunnus'];

			$query = "	SELECT max(tilaajanrivinro) tilaajanrivinro
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]'
						AND tyyppi = 'O'
						AND otunnus = '$tilausnumero'";
			$trivires = mysql_query($query) or pupe_error($query);
			$trivirow = mysql_fetch_array($trivires);

			$rivi = $trivirow['tilaajanrivinro'] + 1;
		}

		// haetaan oletuspaikan tiedot niin laitetaan se riville
		$query = "SELECT * from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and oletus!=''";
		$jtsre = mysql_query($query) or pupe_error($query);
		$jtstu = mysql_fetch_array($jtsre);

		//haetaan tuotteen ostohinta
		$query = "SELECT * from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and liitostunnus='$toimittaja'";
		$ossre = mysql_query($query) or pupe_error($query);
		$osstu = mysql_fetch_array($ossre);

		//haetaan tuotteen ostohinta
		$query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
		$tuotere = mysql_query($query) or pupe_error($query);
		$tuoterow = mysql_fetch_array($tuotere);

		// lis�t��n ostotilausrivi
		$query = "	INSERT into tilausrivi
					(hinta, ale1, nimitys, tuoteno, try, osasto, tilkpl, varattu, yksikko, otunnus, yhtio, tyyppi, kommentti, toimaika, kerayspvm,hyllyalue, hyllynro, hyllyvali, hyllytaso, tilaajanrivinro, laatija, laadittu) values
					('$osstu[ostohinta]', '$osstu[alennus]','$tuoterow[nimitys]', '$tuoteno', '$tuoterow[try]', '$tuoterow[osasto]', '$maara', '$maara', '$tuoterow[yksikko]', '$tilausnumero', '$kukarow[yhtio]', 'O','', now(), now(), '$jtstu[hyllyalue]','$jtstu[hyllynro]','$jtstu[hyllyvali]','$jtstu[hyllytaso]', '$rivi','$kukarow[kuka]', now())";
		$updre = mysql_query($query) or pupe_error($query);

		echo json_encode('ok');

		require ("../inc/footer.inc");
		exit;
	}

	require_once ('inc/ProgressBar.class.php');

	echo "<font class='head'>".t("Ostoehdotus")."</font><hr>";

	if ($tee == "paivita" and $valmis == "PAIVITA") {

		foreach ($varmuus_varastot as $tuoteno => $val) {

			if ($toimittajien_tunnukset[$tuoteno] != '') {
				//echo "Toim tunnus:".$toimittajien_tunnukset[$tuoteno]."<br>";
				//echo "Tuoteno:".$tuoteno."<br>";
				$query = "	UPDATE tuotteen_toimittajat
							SET pakkauskoko = '$pakkauskoot[$tuoteno]',
								toimitusaika = '$toimitusajat[$tuoteno]',
								muuttaja	= '$kukarow[kuka]',
								muutospvm	= now()
							WHERE yhtio = '$tuotteen_yhtiot[$tuoteno]'
							and tuoteno = '$tuoteno'
							and tunnus = '$toimittajien_tunnukset[$tuoteno]'";
				$result   = mysql_query($query) or pupe_error($query);
			}


			$query = "	UPDATE tuote
						SET varmuus_varasto = '$varmuus_varastot[$tuoteno]',
							status = '$varastoitavat[$tuoteno]',
							muuttaja	= '$kukarow[kuka]',
							muutospvm	= now()
						WHERE yhtio = '$tuotteen_yhtiot[$tuoteno]'
						and tuoteno = '$tuoteno'";
			$result   = mysql_query($query) or pupe_error($query);

		}

		$valitutvarastot = unserialize(urldecode($valitutvarastot));
		$valitutyhtiot = unserialize(urldecode($valitutyhtiot));
		$mul_osasto = unserialize(urldecode($mul_osasto));
		$mul_try = unserialize(urldecode($mul_try));
		$tee = "RAPORTOI";
		$ehdotusnappi = '';
	}

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

		// otetaan kaikki muuttujat mukaan funktioon mit� on failissakin
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
		$result   = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		// Myydyt kappaleet
		$returnstring2 += (float) $laskurow['kpl4'];
		$returnstring1 += (float) ($laskurow['ennpois'] + $laskurow['jt']);

		return array($returnstring1, $returnstring2);
	}

	function saldot($myynti_varasto = '', $myynti_maa = '') {
		// otetaan kaikki muuttujat mukaan funktioon mit� on failissakin
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
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			while ($varrow = mysql_fetch_array($result)) {
				$returnstring += (float) $varrow['saldo'];
			}
		}
		else {
			$returnstring = 0;
		}

		return $returnstring;
	}

	function ostot($myynti_varasto = '', $myynti_maa = '') {

		// otetaan kaikki muuttujat mukaan funktioon mit� on failissakin
		extract($GLOBALS);

		$returnstring = 0;

		$varastotapa  = "	JOIN varastopaikat USE INDEX (PRIMARY) ON varastopaikat.yhtio = tilausrivi.yhtio
						 	and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))
						 	and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tilausrivi.hyllyalue) ,5,'0'),lpad(upper(tilausrivi.hyllynro) ,5,'0'))";

		if ($myynti_varasto != "") {
			$varastotapa .= " and varastopaikat.tunnus = '$myynti_varasto' ";
		}
		elseif ($erikoisvarastot != "" and $myynti_maa == "") {
			$query    = "SELECT group_concat(tunnus) from varastopaikat where yhtio in ($yhtiot) and tyyppi = ''";
			$result   = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($result);

			if ($laskurow[0] != "") {
				$varastotapa .= " and varastopaikat.tunnus in ($laskurow[0]) ";
			}
		}
		elseif ($myynti_maa != "") {
			$query    = "SELECT group_concat(tunnus) from varastopaikat where yhtio in ($yhtiot) and maa = '$myynti_maa'";

			if ($erikoisvarastot != "") {
				$query .= " and tyyppi = '' ";
			}

			$result   = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($result);

			if ($laskurow[0] != "") {
				$varastotapa .= " and varastopaikat.tunnus in ($laskurow[0]) ";
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
					and tilausrivi.tyyppi = 'O'
					and tilausrivi.tuoteno = '$row[tuoteno]'
					and tilausrivi.varattu > 0";
		$result = mysql_query($query) or pupe_error($query);
		$ostorow = mysql_fetch_array($result);

		// tilattu kpl
		$returnstring += (float) $ostorow['tilattu'];

		return $returnstring;
	}

	// Haetaan abc-parametrit
	$query = "	SELECT *
				FROM abc_parametrit
				WHERE yhtio = '$kukarow[yhtio]'
				and tyyppi 	= '$abcrajaustapa'
				ORDER by luokka";
	$res = mysql_query($query) or pupe_error($query);

	$ryhmanimet   					= array();
	$ryhmaprossat					= array();
	$kiertonopeus_tavoite 			= array();
	$palvelutaso_tavoite 			= array();
	$varmuusvarasto_pv   			= array();
	$toimittajan_toimitusaika_pv 	= array();

	while ($row = mysql_fetch_array($res)) {
		$ryhmanimet[] 					= $row["luokka"];
		$ryhmaprossat[] 				= $row["osuusprosentti"];
		$kiertonopeus_tavoite[] 		= $row["kiertonopeus_tavoite"];
		$palvelutaso_tavoite[] 			= $row["palvelutaso_tavoite"];
		$varmuusvarasto_pv[]   			= $row["varmuusvarasto_pv"];
		$toimittajan_toimitusaika_pv[] 	= $row["toimittajan_toimitusaika_pv"];
	}

	// Tarvittavat p�iv�m��r�t
	if (!isset($kka4)) $kka4 = date("m",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
	if (!isset($vva4)) $vva4 = date("Y",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
	if (!isset($ppa4)) $ppa4 = date("d",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
	if (!isset($kkl4)) $kkl4 = date("m");
	if (!isset($vvl4)) $vvl4 = date("Y");
	if (!isset($ppl4)) $ppl4 = date("d");

	// katsotaan tarvitaanko menn� toimittajahakuun
	if (($ytunnus != "" and $toimittajaid == "") or ($edytunnus != $ytunnus)) {

		$muutparametrit = "";

		foreach ($_POST as $key => $value) {
			if ($key != "toimittajaid") {
				if (is_array($value)) {
					foreach ($value as $a => $b) {
						$muutparametrit .= $key."[".$a."]=".$b."##";
					}
				}
				else {
					$muutparametrit .= $key."=".$value."##";
				}
			}
		}

		if ($edytunnus != $ytunnus) $toimittajaid = "";
		require ("inc/kevyt_toimittajahaku.inc");

		$ytunnus = $toimittajarow["ytunnus"];

		if($toimittajaid == 0) {
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

	// tehd��n itse raportti
	if ($tee == "RAPORTOI" and isset($ehdotusnappi)) {

		enable_ajax();

		echo "<script type='text/javascript' language='javascript'>";
		require_once("inc/jquery.min.js");
		echo "</script>";

		echo "<script type=\"text/javascript\" charset=\"utf-8\">

			$('.tilaa').live('click', function(){
				var submitid = $(this).attr(\"id\");
				var osat 	 = submitid.split(\"_\");

				var tuoteno 	= $(\"#tuoteno_\"+osat[1]).html();
				var toimittaja 	= $(\"#toimittaja_\"+osat[1]).html();
				var maara 		= $(\"#ostettavat_\"+osat[1]).val();

				$.post('{$_SERVER['SCRIPT_NAME']}',
					{ 	tee: 'TILAA_AJAX',
						tuoteno: tuoteno,
						toimittaja: toimittaja,
						maara: maara,
						no_head: 'yes',
						ohje: 'off' },
					function(return_value) {
						var message = jQuery.parseJSON(return_value);

						if (message == \"ok\") {
							$(\"#\"+submitid).val('".t("Tilattu")."').attr('disabled',true);
							$(\"#ostettavat_\"+osat[1]).attr('disabled',true);
						}
					}
				);
			});
			</script>";

		$lisaa  = ""; // tuote-rajauksia
		$lisaa2 = ""; // toimittaja-rajauksia
		$lisaa3 = ""; // asiakas-rajauksia

		$paivitys_mul_osasto = $mul_osasto;
		$paivitys_mul_try = $mul_try;

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

		// katotaan JT:ss� olevat tuotteet ABC-analyysi� varten, koska ne pit�� includata aina!
		$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
					FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
					JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
					WHERE tilausrivi.yhtio in ($yhtiot)
					and tyyppi IN  ('L','G')
					and var = 'J'
					and jt $lisavarattu > 0";
		$vtresult = mysql_query($query) or pupe_error($query);
		$vrow = mysql_fetch_array($vtresult);

		$jt_tuotteet = "''";

		if ($vrow[0] != "") {
			$jt_tuotteet = $vrow[0];
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

		// t�ss� haetaan sitten listalle soveltuvat tuotteet
		$query = "	SELECT
					group_concat(tuote.yhtio) yhtio,
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
					tuote.luontiaika
					FROM tuote
					$lisaa2
					$abcjoin
					LEFT JOIN korvaavat ON (tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno)
					WHERE
					tuote.yhtio in ($yhtiot)
					$lisaa
					and tuote.ei_saldoa = ''
					GROUP BY tuote.tuoteno
					ORDER BY id, tuote.tuoteno, yhtio";
		$res = mysql_query($query) or pupe_error($query);

		flush();

		//itse rivit
		echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
					function submit_juppe(valmis, ltuoteno, ankkuri) {
						document.ostoehdotuscs.action='$PHP_SELF?lisaa_tuote='+ltuoteno+'&valmis='+valmis+'#A_'+ankkuri;
						document.ostoehdotuscs.submit();
					}
				</SCRIPT>";


		echo "<form name='ostoehdotuscs' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='paivita'>";
		echo "<table>";
		echo "<tr>";

		if ($useampi_yhtio > 1) {
			echo "<th valign='top'>".t("Yhti�")."</th>";
		}

		echo "<th valign='top'>".t("Tuoteno")."<br>".t("Nimitys")."</th>";
		echo "<th valign='top'>".t("Varmuusvarasto")."<br>".t("Tilauspiste")."</th>";
		echo "<th valign='top'>".t("Saldo")."</th>";
		echo "<th valign='top'>".t("Tilattu")."<br>".t("Varattu")."</th>";
		echo "<th valign='top'>".t("Ostoehdotus")."<br>".t("Vuosikulutus")."</th>";
		echo "<th valign='top'>".t("Pakkauskoko")."<br>".t("Varastoitava")."</th>";
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
		// loopataan tuotteet l�pi
		while ($row = mysql_fetch_array($res)) {

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
			$result   = mysql_query($query) or pupe_error($query);
			$toimirow = mysql_fetch_array($result);

			// kaunistellaan kentti�
			if ($row["luontiaika"] == "0000-00-00 00:00:00")	$row["luontiaika"] = "";
			if ($row['epakurantti25pvm'] == '0000-00-00')    	$row['epakurantti25pvm'] = "";
			if ($row['epakurantti50pvm'] == '0000-00-00')     	$row['epakurantti50pvm'] = "";
			if ($row['epakurantti75pvm'] == '0000-00-00')     	$row['epakurantti75pvm'] = "";
			if ($row['epakurantti100pvm'] == '0000-00-00')     	$row['epakurantti50pvm'] = "";

			// haetaan abc luokille nimet
			$abcnimi  = $ryhmanimet[$row["abcluokka"]];
			$abcnimi2 = $ryhmanimet[$row["abcluokka_osasto"]];
			$abcnimi3 = $ryhmanimet[$row["abcluokka_try"]];

			// sitte viel� totalit
			$saldot = saldot($valvarasto);

			// sitte viel� totalit
			$ostot = ostot($valvarasto);

			// sitte viel� totalit
			list($enp, $vku) = myynnit($valvarasto);

			if (($saldot - $enp + $ostot) <= $row["halytysraja"]) {

				if ((float) $kiertonopeus_tavoite[$row["abcluokka"]] == 0) $kiertonopeus_tavoite[$row["abcluokka"]] = 1;

				// Lis�t��n varatut tilaukseen ja verrataan tilauspistett� vapaasaldoon
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
					echo "<td valign='top' $bt><a name='A_$indeksi'><a href='../tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
				}
				else {
					echo "<td valign='top' $btl><a name='A_$indeksi'><a href='../tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
				}

				echo "<td valign='top' $bt  align='right'>".(float) $row["varmuus_varasto"]."</td>";
				echo "<td valign='top' $bt  align='right'>".(float) $saldot."</td>";
				echo "<td valign='top' $bt  align='right'>".(float) $ostot."</td>";

				if ($toimirow["pakkauskoko"] != 0) {
					echo "<td valign='top' $bt  align='right'><font style='color: 00FF00;'>".ceil($ostoehdotus)."</font></td>";
				}
				else {
					echo "<td valign='top' $bt  align='right'><font style='color: 00FF00;'>$ostoehdotus</font></td>";
				}

				echo "<td valign='top' $bt  align='right'>".(float) $toimirow["pakkauskoko"]."</td>";
				echo "<td valign='top' $btr align='right'>".(float) $toimirow["toimitusaika"]." ".t("pva")."</td>";

				if ($useampi_yhtio == 1 and $yhtiorow['yhtio'] == $row['yhtio']) {
					if ($toimirow["pakkauskoko"] != 0) {
						echo "<td valign='top' $bt align='right'><input type='text' size='10' id='ostettavat_$indeksi' name='ostettavat[$row[tuoteno]]' value='".ceil($ostoehdotus)."'></td>";
					}
					else {
						echo "<td valign='top' $bt align='right'><input type='text' size='10' id='ostettavat_$indeksi' name='ostettavat[$row[tuoteno]]' value='$ostoehdotus'></td>";
					}
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

				echo "<td valign='top' $bb align='right'>".(float) $row["halytysraja"]."</td>";
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

				echo "<tr style='height: 5px;'><td align='center' colspan ='8' class='back'>";

				echo "<div id='$indeksi' style='display:none'>";
				echo t("Varmuusvarasto").": <input type='text' size='10' name='varmuus_varastot[$row[tuoteno]]' value='".$row["varmuus_varasto"]."'> ";
				echo t("Pakkauskoko").": <input type='text' size='10' name='pakkauskoot[$row[tuoteno]]' value='".(float) $toimirow["pakkauskoko"]."'> ";
				echo t("Toimitusaika").": <input type='text' size='10' name='toimitusajat[$row[tuoteno]]' value='".(float) $toimirow["toimitusaika"]."'> ".t("pva").". ";

				echo " <select name='varastoitavat[$row[tuoteno]]'>";

				$query = "	SELECT selite, selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							AND laji = 'S'";
				$status_select_res = mysql_query($query) or pupe_error($query);

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

				echo "</select>";

				echo "<input type='hidden' name='toimittajien_tunnukset[$row[tuoteno]]' value='$toim_tunnukset[0]'>";
				echo "<input type='hidden' name='toimittajien_liitostunnukset[$row[tuoteno]]' value='$toim_liitostunnukset[0]'>";
				echo "<input type='hidden' name='tuotteen_yhtiot[$row[tuoteno]]' value='$row[yhtio]'>";

				echo "</div>";
				echo "</td></tr>";

				$indeksi++;

			}
		}
		echo "<tr><td colspan ='8'>";

		if ($useampi_yhtio == 1) {
			echo "<input type='submit' value='".t("P�ivit�")."' onclick=\"submit_juppe('PAIVITA','', 0);\">";
		}

		echo "<input type='hidden' name='mul_osasto' value='".urlencode(serialize($paivitys_mul_osasto))."'>";
		echo "<input type='hidden' name='mul_try' value='".urlencode(serialize($paivitys_mul_try))."'>";
		echo "<input type='hidden' name='valitutyhtiot' value='".urlencode(serialize($valitutyhtiot))."'>";
		echo "<input type='hidden' name='valitutvarastot' value='".urlencode(serialize($valitutvarastot))."'>";
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

		echo "</td></tr></table>";
		echo "</form><br><br>";
	}

	// n�ytet��n k�ytt�liittym�..
	$abcnimi = $ryhmanimet[$abcrajaus];

	echo "	<form action='$PHP_SELF' method='post' autocomplete='off'>
			<input type='hidden' name='tee' value='RAPORTOI'>
			<table>";

	echo "<tr><th>".t("Osasto")."</th><td colspan='3'>";

	// tehd��n avainsana query
	$res2 = t_avainsana("OSASTO", "", "", $yhtiot);

	echo "<select name='mul_osasto[]' multiple='TRUE' size='10' style='width:100%;'>";

	$mul_check = '';
	if ($mul_osasto!="") {
		if (in_array("PUPEKAIKKIMUUT", $mul_osasto)) {
			$mul_check = 'SELECTED';
		}
	}
	echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoteosastoa")."</option>";

	while ($rivi = mysql_fetch_array($res2)) {
		$mul_check = '';
		if ($mul_osasto!="") {
			if (in_array($rivi['selite'],$mul_osasto)) {
				$mul_check = 'SELECTED';
			}
		}

		echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
	}

	echo "</select>";




	echo "</td></tr>
			<tr><th>".t("Tuoteryhm�")."</th><td colspan='3'>";

	//Tehd��n osasto & tuoteryhm� pop-upit
	// tehd��n avainsana query
	$res2 = t_avainsana("TRY", "", "", $yhtiot);

	echo "<select name='mul_try[]' multiple='TRUE' size='10' style='width:100%;'>";

	$mul_check = '';
	if ($mul_try!="") {
		if (in_array("PUPEKAIKKIMUUT", $mul_try)) {
			$mul_check = 'SELECTED';
		}
	}
	echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoterym��")."</option>";

	while ($rivi = mysql_fetch_array($res2)) {
		$mul_check = '';
		if ($mul_try!="") {
			if (in_array($rivi['selite'],$mul_try)) {
				$mul_check = 'SELECTED';
			}
		}

		echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
	}

	echo "</select>";

	echo "</td></tr>
			<tr><th>".t("Tuotemerkki")."</th><td colspan='3'>";

	//Tehd��n osasto & tuoteryhm� pop-upit
	$query = "	SELECT distinct tuotemerkki
				FROM tuote
				WHERE yhtio in ($yhtiot) and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='tuotemerkki'>";
	echo "<option value=''>".t("N�yt� kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($tuotemerkki == $srow["tuotemerkki"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[tuotemerkki]' $sel>$srow[tuotemerkki]</option>";
	}
	echo "</select>";

	echo "</td></tr>";

	// katotaan onko abc aputaulu rakennettu
	$query  = "select count(*) from abc_aputaulu where yhtio in ($yhtiot) and tyyppi in ('TK','TR','TP')";
	$abcres = mysql_query($query) or pupe_error($query);
	$abcrow = mysql_fetch_array($abcres);

	// jos on niin n�ytet��n t�ll�nen vaihtoehto
	if ($abcrow[0] > 0) {
		echo "<tr><th>".t("ABC-luokkarajaus/rajausperuste")."</th><td colspan='3'>";

		$sel = array();
		$sel[$abcrajaus] = "SELECTED";

		echo "<select name='abcrajaus' onchange='submit()'>
		<option value=''>".t("Ei rajausta")."</option>
		<option $sel[0] value='0'>".t("Luokka A-30")."</option>
		<option $sel[1] value='1'>".t("Luokka B-20 ja paremmat")."</option>
		<option $sel[2] value='2'>".t("Luokka C-15 ja paremmat")."</option>
		<option $sel[3] value='3'>".t("Luokka D-15 ja paremmat")."</option>
		<option $sel[4] value='4'>".t("Luokka E-10 ja paremmat")."</option>
		<option $sel[5] value='5'>".t("Luokka F-05 ja paremmat")."</option>
		<option $sel[6] value='6'>".t("Luokka G-03 ja paremmat")."</option>
		<option $sel[7] value='7'>".t("Luokka H-02 ja paremmat")."</option>
		<option $sel[8] value='8'>".t("Luokka I-00 ja paremmat")."</option>
		</select>";

		$sel = array();
		$sel[$abcrajaustapa] = "SELECTED";

		echo "<select name='abcrajaustapa'>
		<option $sel[TK] value='TK'>".t("Myyntikate")."</option>
		<option $sel[TM] value='TM'>".t("Myynti")."</option>
		<option $sel[TR] value='TR'>".t("Myyntirivit")."</option>
		<option $sel[TP] value='TP'>".t("Myyntikappaleet")."</option>
		</select>
		</td></tr>";
	}

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
			<th></th><th colspan='3'>".t("Alkup�iv�m��r� (pp-kk-vvvv)")."</th>
			<th></th><th colspan='3'>".t("Loppup�iv�m��r� (pp-kk-vvvv)")."</th>
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
	echo "<tr><th>".t("�l� huomioi konsernimyynti�")."</th><td colspan='3'><input type='checkbox' name='eliminoi' $chk></td></tr>";

	$chk = "";
	if ($erikoisvarastot != "") $chk = "checked";
	echo "<tr><th>".t("�l� huomioi erikoisvarastoja")."</th><td colspan='3'><input type='checkbox' name='erikoisvarastot' $chk></td></tr>";

	$chk = "";
	if ($poistetut != "") $chk = "checked";
	echo "<tr><th>".t("�l� n�yt� poistettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistetut' $chk></td></tr>";

	$chk = "";
	if ($poistuva != "") $chk = "checked";
	echo "<tr><th>".t("�l� n�yt� poistuvia tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistuva' $chk></td></tr>";

	$chk = "";
	if ($eihinnastoon != "") $chk = "checked";
	echo "<tr><th>".t("�l� n�yt� tuotteita joita ei n�ytet� hinnastossa")."</th><td colspan='3'><input type='checkbox' name='eihinnastoon' $chk></td></tr>";

	$chk = "";
	if ($naytakaikkituotteet != "") $chk = "checked";
	echo "<tr><th>".t("N�yt� my�s tuotteet joiden ostoehdotus on nolla")."</th><td colspan='3'><input type='checkbox' name='naytakaikkituotteet' $chk></td></tr>";

	$chk = "";
	if ($naytavainmyydyt != "") $chk = "checked";
	echo "<tr><th>".t("N�yt� vain tuotteet joilla on myynti�")."</th><td colspan='3'><input type='checkbox' name='naytavainmyydyt' $chk></td></tr>";

	$chk = "";
	if ($eivarastoivattilaus != "") $chk = "checked";
	echo "<tr><th>".t("N�yt� my�s ei varastoitavat tuotteet joilla ei ole tilauksia")."</th><td colspan='3'><input type='checkbox' name='eivarastoivattilaus' $chk></td></tr>";


	if ($abcrajaus != "") {
		echo "<tr><td class='back'><br></td></tr>";
		echo "<tr><th colspan='4'>".t("ABC-rajaus")." $ryhmanimet[$abcrajaus]</th></tr>";

		$chk = "";
		if ($eiuusia != "") $chk = "checked";
		echo "<tr><th>".t("�l� listaa 12kk sis�ll� perustettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='eiuusia' $chk></td></tr>";

		$chk = "";
		if ($vainuudet != "") $chk = "checked";
		echo "<tr><th>".t("Listaa vain 12kk sis�ll� perustetut tuotteet")."</th><td colspan='3'><input type='checkbox' name='vainuudet' $chk></td></tr>";
	}

	echo "</table><table><br>";

	// yhti�valinnat
	$query	= "	SELECT distinct yhtio, nimi
				from yhtio
				where konserni = '$yhtiorow[konserni]'
				and konserni != ''";
	$presult = mysql_query($query) or pupe_error($query);

	$useampi_yhtio = 0;

	if (mysql_num_rows($presult) > 0) {

		echo "<tr><th>".t("Huomioi yhti�n saldot, myynnit ja ostot").":</th></tr>";
		$yhtiot = "";

		while ($prow = mysql_fetch_array($presult)) {

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
				ORDER BY yhtio, nimitys";
	$vtresult = mysql_query($query) or pupe_error($query);

	$vlask = 0;

	if (mysql_num_rows($vtresult) > 0) {

		echo "<tr><th>".t("Huomioi saldot, myynnit ja ostot varastoittain:")."</th></tr>";

		while ($vrow = mysql_fetch_array($vtresult)) {

			$chk = "";
			if (isset($valitutvarastot) and $valitutvarastot == $vrow["tunnus"]) {
				$chk = "CHECKED";
			}

			echo "<tr><td><input type='radio' name='valitutvarastot' value='$vrow[tunnus]' $chk>";

			if ($useampi_yhtio > 1) {
				$query = "SELECT nimi from yhtio where yhtio='$vrow[yhtio]'";
				$yhtres = mysql_query($query) or pupe_error($query);
				$yhtrow = mysql_fetch_array($yhtres);
				echo "$yhtrow[nimi]: ";
			}

			echo "$vrow[nimitys] ";

			if ($vrow["tyyppi"] != "") {
				echo " *$vrow[tyyppi]* ";
			}
			if ($useampi_maa == 1) {
				echo "(".maa($vrow["maa"]).")";
			}

			echo "</td></tr>";
		}
	}
	else {
		echo "<font class='error'>".t("Yht��n varastoa ei l�ydy, raporttia ei voida ajaa")."!</font>";
		exit;
	}

	echo "</table>";
	echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Aja ostoehdotus")."'></form>";

	require ("inc/footer.inc");

?>