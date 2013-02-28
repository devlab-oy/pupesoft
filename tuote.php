<?php

	require("inc/parametrit.inc");

	if (!isset($tee)) 		 	 $tee = "";
	if (!isset($toim)) 		 	 $toim = "";
	if (!isset($lopetus)) 	 	 $lopetus = "";
	if (!isset($toim_kutsu)) 	 $toim_kutsu = "";
	if (!isset($ulos)) 		 	 $ulos = "";
	if (!isset($livesearch_tee)) $livesearch_tee = "";
	if (!isset($tapahtumalaji))  $tapahtumalaji = "";
	if (!isset($tilalehinta))  	 $tilalehinta = "";
	if (!isset($historia))  	 $historia = "";

	if ($livesearch_tee == "TUOTEHAKU") {
		livesearch_tuotehaku();
		exit;
	}

	// Liitetiedostot popup
	if (isset($liite_popup_toiminto) and $liite_popup_toiminto == "AK") {
		liite_popup("AK", $tuotetunnus, $width, $height);
	}
	else {
		liite_popup("JS");
	}

	if (function_exists("js_popup")) {
		echo js_popup(-100);
	}

	// Enaboidaan ajax kikkare
	enable_ajax();

	if ($tee == 'N' or $tee == 'E') {

		if ($tee == 'N') {
			$oper='>';
			$suun='';
		}
		else {
			$oper='<';
			$suun='desc';
		}

		$query = "	SELECT tuote.tuoteno
					FROM tuote use index (tuoteno_index)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno $oper '$tuoteno'
					and (tuote.status not in ('P','X') or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0)
					ORDER BY tuote.tuoteno $suun
					LIMIT 1";
		$result = pupe_query($query);

		if (mysql_num_rows($result) > 0) {
			$trow = mysql_fetch_assoc ($result);
			$tuoteno = $trow['tuoteno'];
			$tee = 'Z';
		}
		else {
			$varaosavirhe = t("Yhtään tuotetta ei löytynyt")."!";
			$tuoteno = '';
			$tee = 'Y';
		}
	}

	if (isset($tuoteno)) $tkysy_lopetus = "{$palvelin2}tuote.php////tuoteno=$tuoteno//tee=Z";
	else $tkysy_lopetus = "";

	if ($lopetus != "") {
		// Lisätään tämä lopetuslinkkiin
		$tkysy_lopetus = $lopetus."/SPLIT/".$tkysy_lopetus;
	}

	if ($tee == 'NAYTATILAUS') {
		echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
		require ("raportit/naytatilaus.inc");
		echo "<br><br><br>";
		$tee = "Z";
	}

	echo "<font class='head'>".t("Tuotekysely")."</font><hr>";

	if ($tee == 'Z' and (!isset($tyyppi) or $tyyppi == '')) {
		require "inc/tuotehaku.inc";
	}

	if ($tee == 'Z' and isset($tyyppi) and $tyyppi != '') {

		if ($tyyppi == 'TOIMTUOTENO') {

			$query = "	SELECT tuotteen_toimittajat.tuoteno
						FROM tuotteen_toimittajat
						JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno)
						WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
						AND tuotteen_toimittajat.toim_tuoteno = '{$tuoteno}'
						AND (tuote.status NOT IN ('P','X') OR (SELECT SUM(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio = tuote.yhtio AND tuotepaikat.tuoteno = tuote.tuoteno AND tuotepaikat.saldo > 0) > 0)
						ORDER BY tuote.tuoteno";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				$query = "	SELECT tuote.tuoteno
							FROM tuotteen_toimittajat_tuotenumerot AS ttt
							JOIN tuotteen_toimittajat AS tt ON (tt.yhtio = ttt.yhtio AND tt.tunnus = ttt.toim_tuoteno_tunnus)
							JOIN tuote ON (tuote.yhtio = tt.yhtio AND tuote.tuoteno = tt.tuoteno)
							WHERE ttt.yhtio = '{$kukarow['yhtio']}'
							AND (ttt.toim_tuoteno = '{$tuoteno}' or ttt.viivakoodi = '{$tuoteno}')
							AND (tuote.status NOT IN ('P','X') OR (SELECT SUM(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio = tuote.yhtio AND tuotepaikat.tuoteno = tuote.tuoteno AND tuotepaikat.saldo > 0) > 0)";
				$chk_res = pupe_query($query);

				if (mysql_num_rows($chk_res) != 0) {
					$result = $chk_res;
				}
			}

			if (mysql_num_rows($result) == 0) {
				$varaosavirhe = t("VIRHE: Tiedolla ei löytynyt tuotetta")."!";
				$tee = 'Y';
			}
			elseif (mysql_num_rows($result) > 1) {
				$varaosavirhe = t("VIRHE: Tiedolla löytyi useita tuotteita")."!";
				$tee = 'Y';
			}
			else {
				$tr = mysql_fetch_assoc($result);
				$tuoteno = $tr["tuoteno"];
			}
		}
	}

	if ($tee=='Y') echo "<font class='error'>$varaosavirhe</font>";

	 //syotetaan tuotenumero
	$formi  = 'formi';
	$kentta = 'tuoteno';

	//Paluu nappi osto/myyntitilaukselle
	$query    = "SELECT * from lasku where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$result   = pupe_query($query);
	$laskurow = mysql_fetch_assoc($result);

	if ($kukarow["kuka"] != "" and $laskurow["tila"] == "O") {
		echo "	<form method='post' action='".$palvelin2."tilauskasittely/tilaus_osto.php'>
				<input type='hidden' name='aktivoinnista' value='true'>
				<input type='hidden' name='tee' value='AKTIVOI'>
				<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
				<input type='submit' value='".t("Takaisin tilaukselle")."'>
				</form><br><br>";
	}
	elseif (strpos($lopetus, "tilaus_myynti.php") === FALSE and $kukarow["kuka"] != "" and $laskurow["tila"] != "" and $laskurow["tila"] != "K" and $toim_kutsu != "") {
		echo "	<form method='post' action='".$palvelin2."tilauskasittely/tilaus_myynti.php'>
				<input type='hidden' name='toim' value='$toim_kutsu'>
				<input type='hidden' name='aktivoinnista' value='true'>
				<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
				<input type='submit' value='".t("Takaisin tilaukselle")."'>
				</form><br><br>";
	}

	echo "<br>";
	echo "<table>";

	echo "<tr>";
	echo "<form method='post' name='formi' autocomplete='off'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<input type='hidden' name='lopetus' value='$lopetus'>";
	echo "<input type='hidden' name='tee' value='Z'>";
	echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
	echo "<th style='vertical-align:middle;'>".t("Tuotehaku")."</th>";
	echo "<td>".livesearch_kentta("formi", "TUOTEHAKU", "tuoteno", 300)."</td>";
	echo "<td class='back'>";
	echo "<input type='Submit' value='".t("Hae")."'></form></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<form method='post' name='formi2' autocomplete='off'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<input type='hidden' name='lopetus' value='$lopetus'>";
	echo "<input type='hidden' name='tee' value='Z'>";
	echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";

	echo "<th style='vertical-align:middle;'>";
	echo "<input type='hidden' name='tyyppi' value='TOIMTUOTENO'>";
	echo t("Toimittajan tuotenumero");
	echo "</th>";

	echo "<td>";
	echo "<input type='text' name='tuoteno' value='' style='width:300px;'>";
	echo "</td>";

	echo "<td class='back'>";
	echo "<input type='Submit' value='".t("Hae")."'>";
	echo "</form>";
	echo "</td>";

	//Jos ei haettu, annetaan 'edellinen' & 'seuraava'-nappi
	if ($ulos == '' and $tee == 'Z') {
		echo "<form method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='lopetus' value='$lopetus'>";
		echo "<input type='hidden' name='tee' value='E'>";
		echo "<input type='hidden' name='tyyppi' value=''>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
		echo "<td class='back'>";
		echo "<input type='Submit' value='".t("Edellinen")."'>";
		echo "</td>";
		echo "</form>";

		echo "<form method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='lopetus' value='$lopetus'>";
		echo "<input type='hidden' name='tyyppi' value=''>";
		echo "<input type='hidden' name='tee' value='N'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
		echo "<td class='back'>";
		echo "<input type='Submit' value='".t("Seuraava")."'>";
		echo "</td>";
		echo "</form>";
	}

	echo "</tr></table><br>";

	//tuotteen varastostatus
	if ($tee == 'Z') {

		echo "<font class='message'>".t("Tuotetiedot")."</font><hr>";

		$query = "	SELECT tuote.*,
					if (tuote.status = '', 'A', tuote.status) status,
					date_format(tuote.muutospvm, '%Y-%m-%d') muutos, date_format(tuote.luontiaika, '%Y-%m-%d') luonti
					FROM tuote
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno = '$tuoteno'";
		$result = pupe_query($query);

		$query = "	SELECT sum(saldo) saldo
					from tuotepaikat
					where tuoteno	= '$tuoteno'
					and saldo		> 0
					and yhtio		= '$kukarow[yhtio]'";
		$salre = pupe_query($query);
		$salro = mysql_fetch_assoc($salre);

		if (mysql_num_rows($result) == 1) {
			$tuoterow = mysql_fetch_assoc($result);
		}
		else {
			$tuoterow = array();
		}

		// tuotteen toimittajatiedot
		if ($tuoterow["ei_saldoa"] == '') {
			$query = "	SELECT tuotteen_toimittajat.*,
						toimi.ytunnus, toimi.nimi, toimi.nimitark, toimi.oletus_valkoodi,
						if (jarjestys = 0, 9999, jarjestys) sorttaus
						FROM tuotteen_toimittajat
						LEFT JOIN toimi on (toimi.yhtio = tuotteen_toimittajat.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus)
						WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
						and tuotteen_toimittajat.tuoteno = '$tuoteno'
						ORDER BY sorttaus";
			$ttres = pupe_query($query);

			$ttrow = array();

			while ($ttrowx = mysql_fetch_assoc($ttres)) {
				$ttrow[] = $ttrowx;
			}
		}
		else {
			$ttrow = array();
		}

		// Tarkastetaan onko taricit käytössä
		$tv_kaytossa = FALSE;

		$query = "SELECT count(*) kpl from taric_veroperusteet";
		$tv_res = pupe_query($query);
		$tv_row = mysql_fetch_assoc($tv_res);

		if ($tv_row["kpl"] > 0) {
			$tv_kaytossa = TRUE;
		}

		if ($tuoterow["tuoteno"] != "" and (!in_array($tuoterow["status"], array('P', 'X')) or $salro["saldo"] != 0)) {

			if ($yhtiorow["saldo_kasittely"] == "T") {
				$saldoaikalisa = date("Y-m-d");
			}
			else {
				$saldoaikalisa = "";
			}

			$sarjanumero_kpl = 0;

			// Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksilöiden ostohinnoista (ostetut yksilöt jotka eivät vielä ole myyty(=laskutettu))
			if ($tuoterow["sarjanumeroseuranta"] == "S" or $tuoterow["sarjanumeroseuranta"] == "U" or $tuoterow['sarjanumeroseuranta'] == 'G') {
				$query	= "	SELECT sarjanumeroseuranta.tunnus
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno = '$tuoterow[tuoteno]'
							and sarjanumeroseuranta.myyntirivitunnus != -1
							and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
							and tilausrivi_osto.laskutettuaika != '0000-00-00'";
				$sarjares = pupe_query($query);

				$kehahin = 0;

				if (mysql_num_rows($sarjares) > 0) {
					while($sarjarow = mysql_fetch_assoc($sarjares)) {
						$kehahin += sarjanumeron_ostohinta("tunnus", $sarjarow["tunnus"]);
						$sarjanumero_kpl++;
					}

					$tuoterow['kehahin'] = sprintf('%.6f', ($kehahin / mysql_num_rows($sarjares)));
				}
				else {
					$tuoterow['kehahin'] = "";
				}
			}

			$alkuperainen_keskihankintahinta = $tuoterow["kehahin"];

			if ($kukarow["naytetaan_katteet_tilauksella"] == "B" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "B")) {
				$tuoterow['kehahin'] = $tuoterow['kehahin'];
			}
			else {
				if 	   ($tuoterow['epakurantti100pvm'] != '0000-00-00') $tuoterow['kehahin'] = 0;
				elseif ($tuoterow['epakurantti75pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.25, 6);
				elseif ($tuoterow['epakurantti50pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.5,  6);
				elseif ($tuoterow['epakurantti25pvm'] != '0000-00-00') $tuoterow['kehahin'] = round($tuoterow['kehahin'] * 0.75, 6);
			}

			// Hinnastoon
			if (strtoupper($tuoterow['hinnastoon']) == 'E') {
			 	$tuoterow['hinnastoon'] = "<font style='color:#FF0000;'>".t("Ei")."</font>";
			}
			else {
				$tuoterow['hinnastoon'] = "<font style='color:#00FF00;'>".t("Kyllä")."</font>";
			}

			// Varastoon
			if ($tuoterow['status'] == 'T' or $tuoterow['status'] == 'P') {
			 	$tuoterow['ei_varastoida'] = "<font style='color:#FF0000;'>".t("Ei")."</font>";
			}
			else {
				$tuoterow['ei_varastoida'] = "<font style='color:#00FF00;'>".t("Kyllä")."</font>";
			}

			//tullinimike
			$cn1 = $tuoterow["tullinimike1"];
			$cn2 = substr($tuoterow["tullinimike1"],0,6);
			$cn3 = substr($tuoterow["tullinimike1"],0,4);

			$query = "SELECT cn, dm, su from tullinimike where cn='$cn1' and kieli = '$yhtiorow[kieli]'";
			$tulliresult1 = pupe_query($query);

			$query = "SELECT cn, dm, su from tullinimike where cn='$cn2' and kieli = '$yhtiorow[kieli]'";
			$tulliresult2 = pupe_query($query);

			$query = "SELECT cn, dm, su from tullinimike where cn='$cn3' and kieli = '$yhtiorow[kieli]'";
			$tulliresult3 = pupe_query($query);

			$tullirow1 = mysql_fetch_assoc($tulliresult1);
			$tullirow2 = mysql_fetch_assoc($tulliresult2);
			$tullirow3 = mysql_fetch_assoc($tulliresult3);

			//perusalennus
			$query  = "SELECT alennus from perusalennus where ryhma='$tuoterow[aleryhma]' and yhtio='$kukarow[yhtio]'";
			$peralresult = pupe_query($query);
			$peralrow = mysql_fetch_assoc($peralresult);

			$query = "	SELECT distinct valkoodi, maa
						from hinnasto
						where yhtio = '$kukarow[yhtio]'
						and tuoteno = '$tuoterow[tuoteno]'
						and laji = ''
						order by maa, valkoodi";
			$hintavalresult = pupe_query($query);

			$valuuttalisa = "";

			while ($hintavalrow = mysql_fetch_assoc($hintavalresult)) {

				// katotaan onko tuotteelle valuuttahintoja
				$query = "	SELECT *
							from hinnasto
							where yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tuoterow[tuoteno]'
							and valkoodi = '$hintavalrow[valkoodi]'
							and maa = '$hintavalrow[maa]'
							and laji = ''
							and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
							order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
							limit 1";
				$hintaresult = pupe_query($query);

				while ($hintarow = mysql_fetch_assoc($hintaresult)) {
					$valuuttalisa .= "<br>$hintarow[maa]: ".hintapyoristys($hintarow["hinta"])." $hintarow[valkoodi]";
				}

			}

			if ($tv_kaytossa and $tullirow1['cn'] != '') {
				$alkuperamaat 	= array();
				$alkuperamaat[] = explode(',',$tuoterow['alkuperamaa']);
				$tuorow 		= $tuoterow;
				$prossat 		= '';
				$prossa_str 	= '';

				foreach ($alkuperamaat as $alkuperamaa) {
					foreach ($alkuperamaa as $alkupmaa) {

						$laskurow['maa_lahetys'] = $alkupmaa;

						$mista = 'tuote.php';

						include('tilauskasittely/taric_veroperusteet.inc');

						$prossa_str = trim($tulliprossa,"0");
						if (strlen($prossa_str) > 1) {
							$prossat .= "<br>".trim($tulliprossa,"0")." ".$alkupmaa;
						}
					}
				}
			}

			//eka laitetaan tuotteen yleiset (aika staattiset) tiedot
			echo "<table>";

			echo "<tr>";
			echo "<th>".t("Tuotenumero")."<br>".t("Tuotemerkki")."</th>";
			echo "<th>".t("Yksikkö")."</th>";
			echo "<th>".t("Eankoodi")."</th>";
			echo "<th colspan='2'>".t("Nimitys")."</th>";
			echo "<th>".t("Hinnastoon")."<br>".t("Status")."</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td style='font-weight:bold;'>$tuoterow[tuoteno]";

			if (tarkista_oikeus('yllapito.php', 'tuote', 1)) {
				echo "&nbsp;&nbsp;<a href='{$palvelin2}yllapito.php?toim=tuote&tunnus={$tuoterow["tunnus"]}&lopetus=$tkysy_lopetus'><img src='{$palvelin2}pics/lullacons/document-properties.png' alt='",t("Muokkaa"),"' title='",t("Muuta tuotteen tietoja"),"' /></a>";
			}

			//haetaan orginaalit
			if (table_exists("tuotteen_orginaalit")) {
				$query = "	SELECT *
							from tuotteen_orginaalit
							where yhtio = '$kukarow[yhtio]'
							and tuoteno = '$tuoterow[tuoteno]'";
					$origresult = pupe_query($query);

				if (mysql_num_rows($origresult) > 0) {

					$i = 0;

					$divit = "<div id='div_$tuoterow[tuoteno]' class='popup'>";
					$divit .= "<table><tr><td valign='top'><table>";
					$divit .= "<tr><td class='back' valign='top' align='center'>".t("Alkuperäisnumero")."</td><td class='back' valign='top' align='center'>".t("Hinta")."</td><td class='back' valign='top' align='center'>".t("Merkki")."</td></tr>";

					while ($origrow = mysql_fetch_assoc($origresult)) {
						++$i;
						if ($i == 20) {
							$divit .= "</table></td><td valign='top'><table>";
							$divit .= "<tr><td class='back' valign='top' align='center'>".t("Alkuperäisnumero")."</td><td class='back' valign='top' align='center'>".t("Hinta")."</td><td class='back' valign='top' align='center'>".t("Merkki")."</td></tr>";
							$i = 1;
						}
						$divit .= "<tr><td class='back' valign='top'>$origrow[orig_tuoteno]</td><td class='back' valign='top' align='right'>$origrow[orig_hinta]</td><td class='back' valign='top'>$origrow[merkki]</td></tr>";
					}

					$divit .= "</table></td></tr>";

					$divit .= "</table>";
					$divit .= "</div>";

					echo "&nbsp;&nbsp;<a src='#' class='tooltip' id='$tuoterow[tuoteno]'><img src='pics/lullacons/info.png' height='13'></a>";

				}
			}

			//1
			echo "<br>".t_avainsana("TUOTEMERKKI", "", " and avainsana.selite='$tuoterow[tuotemerkki]'", "", "", "selite")."</td>";

			echo "<td>".t_avainsana("Y", "", "and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite");

			$palautus = t_tuotteen_avainsanat($tuoterow, "pakkauskoko2");
			if (is_array($palautus) and count($palautus) > 0) {
				echo "<br>";
				echo "{$palautus["selite"]} ".t_avainsana("Y", "", "and avainsana.selite='{$palautus["selitetark"]}'", "", "", "selite");
			}

			$palautus = t_tuotteen_avainsanat($tuoterow, "pakkauskoko3");
			if (is_array($palautus) and count($palautus) > 0) {
				echo "<br>";
				echo "{$palautus["selite"]} ".t_avainsana("Y", "", "and avainsana.selite='{$palautus["selitetark"]}'", "", "", "selite");
			}

			echo "</td>";

			echo "<td>$tuoterow[eankoodi]</td><td colspan='2' style='font-weight:bold;'>".t_tuotteen_avainsanat($tuoterow, 'nimitys')."</td>";
			echo "<td>$tuoterow[hinnastoon]<br>".t_avainsana("S", $kieli, "and avainsana.selite='$tuoterow[status]'", "", "", "selitetark")."</td>";
			echo "</tr>";

			//2
			echo "<tr>";
			echo "<th>".t("Osasto/try")."</th>";
			echo "<th>".t("Toimittaja")."</th>";
			echo "<th>".t("Aleryhmä")."</th>";
			echo "<th>".t("Tähti")."</th>";
			echo "<th>".t("Perusalennus")."</th>";
			echo "<th>".t("VAK")."</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td>$tuoterow[osasto] - ".t_avainsana("OSASTO", "", "and avainsana.selite='$tuoterow[osasto]'", "", "", "selitetark")."<br>$tuoterow[try] - ".t_avainsana("TRY", "", "and avainsana.selite='$tuoterow[try]'", "", "", "selitetark")."</td>";
			echo "<td>";
			foreach ($ttrow as $tt_rivi) {
				echo "{$tt_rivi["ytunnus"]} {$tt_rivi["nimi"]}<br>";
			}
			echo "</td>";
			echo "<td>$tuoterow[aleryhma]</td>";
			echo "<td>$tuoterow[tahtituote]</td>";
			echo "<td>$peralrow[alennus]%</td>";

			if ($yhtiorow["vak_kasittely"] != "" and $tuoterow["vakkoodi"] != "" and $tuoterow["vakkoodi"] != "0") {
				$query = "	SELECT tunnus, concat_ws(' / ', concat('UN',yk_nro), nimi_ja_kuvaus, luokka, luokituskoodi, pakkausryhma, lipukkeet, rajoitetut_maarat_ja_poikkeusmaarat_1) vakkoodi
							FROM vak
							WHERE yhtio = '{$kukarow['yhtio']}'
							and tunnus  = '{$tuoterow['vakkoodi']}'";
				$vak_res = pupe_query($query);
				$vak_row = mysql_fetch_assoc($vak_res);

				$tuoterow["vakkoodi"] = $vak_row["vakkoodi"];
			}

			echo "<td>$tuoterow[vakkoodi]</td>";
			echo "</tr>";

			//3
			echo "<tr>";
			echo "<th>".t("Toimtuoteno")."</th>";
			echo "<th>".t("Myyntihinta");

			if ($tuoterow["myyntihinta_maara"] != 0) {
				echo " $tuoterow[myyntihinta_maara] $tuoterow[yksikko]";
			}

			echo "</th>";
			echo "<th>".t("Netto/Ovh")."</th>";
			echo "<th>".t("Ostohinta")." / ";

			for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
				if ($alepostfix > 1) echo " ";

				echo t("Ale{$alepostfix}");
			}

			echo "<th>".t("Kehahinta")."</th>";
			echo "<th>".t("Vihahinta")." ".tv1dateconv($tuoterow["vihapvm"])."</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td>";
			foreach ($ttrow as $tt_rivi) {
				echo "{$tt_rivi["toim_tuoteno"]}<br>";
			}
			echo "</td>";
			echo "<td valign='top' align='right' style='font-weight:bold;'>".hintapyoristys($tuoterow["myyntihinta"])." $yhtiorow[valkoodi]$valuuttalisa</td>";
			echo "<td valign='top' align='right'>".hintapyoristys($tuoterow["nettohinta"])."/".hintapyoristys($tuoterow["myymalahinta"])."</td>";
			echo "<td valign='top' align='right'>";

			foreach ($ttrow as $tt_rivi) {

				$query = "	SELECT *
							FROM valuu
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND nimi = '{$tt_rivi['oletus_valkoodi']}'
							ORDER BY tunnus DESC
							LIMIT 1";
				$kurssi_chk_res = pupe_query($query);
				$kurssi_chk_row = mysql_fetch_assoc($kurssi_chk_res);

				$_laskurow = array(
					'liitostunnus' 	=> $tt_rivi['liitostunnus'],
					'valkoodi' 		=> $tt_rivi['oletus_valkoodi'],
					'ytunnus' 		=> $tt_rivi['ytunnus'],
					'vienti_kurssi' => $kurssi_chk_row['kurssi']
				);

				list($_hinta, $_netto, $_ale, $_valuutta) = alehinta_osto ($_laskurow, $tuoterow, 1, '', '', array());

				echo "<span style='font-weight:bold;'>",hintapyoristys($_hinta, 6, TRUE)," {$_valuutta}</span> / ";

				foreach ($_ale as $key => $val) {

					if ($key{3} > $yhtiorow['oston_alekentat']) continue;

					echo "{$val}% ";
				}

				echo "<br />";
			}
			echo "</td>";
			echo "<td valign='top' align='right' style='font-weight:bold;'>".hintapyoristys($tuoterow["kehahin"], 6, TRUE);

			if ($tuoterow["myyntihinta_maara"] != 0) {
				echo " $tuoterow[yksikko]<br>";
				echo hintapyoristys($tuoterow["kehahin"] * $tuoterow["myyntihinta_maara"], 6, TRUE);
				echo " $tuoterow[myyntihinta_maara] $tuoterow[yksikko]";
			}

			if ($alkuperainen_keskihankintahinta != $tuoterow["kehahin"]) {
				echo "<br>($alkuperainen_keskihankintahinta)";
			}

			echo "</td>";
			echo "<td valign='top' align='right' style='font-weight:bold;'>".hintapyoristys($tuoterow["vihahin"], 6, TRUE);

			if ($tuoterow["myyntihinta_maara"] != 0) {
				echo " $tuoterow[yksikko]<br>";
				echo hintapyoristys($tuoterow["vihahin"] * $tuoterow["myyntihinta_maara"], 6, TRUE);
				echo " $tuoterow[myyntihinta_maara] $tuoterow[yksikko]";
			}

			echo "</td>";
			echo "</tr>";

			//4
			echo "<tr>";
			echo "<th>".t("Hälyraja")." / ".t("Varastoitava")."</th>";
			echo "<th>".t("Ostoerä")."</th>";
			echo "<th>".t("Myyntierä")."</th>";
			echo "<th>".t("Kerroin")."</th>";
			echo "<th>".t("Tarrakerroin")."</th>";
			echo "<th>".t("Tarrakpl")."</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td valign='top' align='right'>$tuoterow[halytysraja] / $tuoterow[ei_varastoida]</td>";
			echo "<td valign='top' align='right'>";

			foreach ($ttrow as $tt_rivi) {
				echo "{$tt_rivi["osto_era"]}<br>";
			}
			echo "</td>";

			echo "<td valign='top' align='right'>$tuoterow[myynti_era]</td>";
			echo "<td valign='top' align='right'>";

			foreach ($ttrow as $tt_rivi) {
				echo "{$tt_rivi["tuotekerroin"]}<br>";
			}
			echo "</td>";
			echo "<td valign='top' align='right'>$tuoterow[tarrakerroin]</td>";
			echo "<td valign='top' align='right'>$tuoterow[tarrakpl]</td>";
			echo "</tr>";

			//5
			echo "<tr>";
			echo "<th>".t("Tullinimike")." / %</th>";
			echo "<th colspan='4'>".t("Tullinimikkeen kuvaus")."</th>";
			echo "<th>".t("Toinen paljous")."</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td>$tullirow1[cn] $prossat</td>";
			echo "<td colspan='4'>".wordwrap(substr($tullirow3['dm'],0,20)." - ".substr($tullirow2['dm'],0,20)." - ".substr($tullirow1['dm'],0,20), 70, "<br>")."</td>";
			echo "<td>$tullirow1[su]</td>";
			echo "</tr>";

			//6
			echo "<tr>";
			echo "<th>".t("Luontipvm")."</th>";
			echo "<th>".t("Muutospvm")."</th>";
			echo "<th>".t("Epäkurantti25pvm")."</th>";
			echo "<th>".t("Epäkurantti50pvm")."</th>";
			echo "<th>".t("Epäkurantti75pvm")."</th>";
			echo "<th>".t("Epäkurantti100pvm")."</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td>".tv1dateconv($tuoterow["luonti"])."</td>";
			echo "<td>".tv1dateconv($tuoterow["muutos"])."</td>";
			echo "<td>".tv1dateconv($tuoterow["epakurantti25pvm"])."</td>";
			echo "<td>".tv1dateconv($tuoterow["epakurantti50pvm"])."</td>";
			echo "<td>".tv1dateconv($tuoterow["epakurantti75pvm"])."</td>";
			echo "<td>".tv1dateconv($tuoterow["epakurantti100pvm"])."</td>";
			echo "</tr>";

			//7
			echo "<tr>";
			echo "<th colspan='6'>".t("Tuotteen kuvaus")."</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td colspan='6'>".wordwrap($tuoterow["kuvaus"], 130, "<br>")."&nbsp;</td>";
			echo "</tr>";

			//8
			echo "<tr>";
			echo "<th>".t("Muuta")."</th>";
			echo "<th colspan='5'>".t("Lyhytkuvaus")."</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td>$tuoterow[muuta]&nbsp;</td>";
			echo "<td colspan='5'>".wordwrap($tuoterow["lyhytkuvaus"], 70, "<br>")."</td>";
			echo "</tr>";

			//9
			echo "<tr>";
			echo "<th>".t("Korkeus")."</th>";
			echo "<th>".t("Leveys")."</th>";
			echo "<th>".t("Syvyys")."</th>";
			echo "<th>".t("Paino")."</th>";
			echo "<th></th>";
			echo "<th></th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td>$tuoterow[tuotekorkeus] m</td>";
			echo "<td>$tuoterow[tuoteleveys] m</td>";
			echo "<td>$tuoterow[tuotesyvyys] m</td>";
			echo "<td>$tuoterow[tuotemassa] kg</td>";
			echo "<td></td>";
			echo "<td></td>";
			echo "</tr>";

			echo "</table><br>";

			if (count($ttrow) > 0) {
				echo "<font class='message'>",t("Tuotteen toimittajan vaihtoehtoiset tuotenumerot"),"</font><hr />";

				echo "<table>";
				echo "<tr>";
				echo "<th>",t("Toimittaja"),"</th>";
				echo "<th>",t("Tuoteno"),"</th>";
				echo "<th>",t("Viivakoodi"),"</th>";
				echo "</tr>";

				foreach ($ttrow as $tt_rivi) {
					$query = "	SELECT ttt.*, TRIM(CONCAT(toimi.nimi, ' ', toimi.nimitark)) AS nimi
								FROM tuotteen_toimittajat_tuotenumerot AS ttt
								JOIN tuotteen_toimittajat AS tt ON (tt.yhtio = ttt.yhtio AND tt.tunnus = ttt.toim_tuoteno_tunnus AND tt.toim_tuoteno = '{$tt_rivi['toim_tuoteno']}')
								JOIN toimi ON (toimi.yhtio = tt.yhtio AND toimi.tunnus = tt.liitostunnus)
								WHERE ttt.yhtio = '{$kukarow['yhtio']}'";
					$chk_res = pupe_query($query);

					while ($chk_row = mysql_fetch_assoc($chk_res)) {
						echo "<tr>";
						echo "<td>{$chk_row['nimi']}</td>";
						echo "<td>{$chk_row['toim_tuoteno']}</td>";
						echo "<td>{$chk_row['viivakoodi']}</td>";
						echo "</tr>";
					}
				}

				echo "</table><br />";
			}

			// Onko liitetiedostoja
			$liitteet = liite_popup("TN", $tuoterow["tunnus"]);

			if ($liitteet != "") {
				echo "<font class='message'>".t("Liitetiedostot")."</font><hr>";
				echo "$liitteet<br><br>";
			}

			// aika karseeta, mutta katotaan voidaanko tällästä optiota näyttää yks tosi firma specific juttu
			if (table_exists("yhteensopivuus_tuote") and file_exists("yhteensopivuus_tuote.php") and tarkista_oikeus('yhteensopivuus_tuote.php')) {

                $lisa = " and tuoteno = '$tuoteno' ";

                $query = "  SELECT isatuoteno
                            FROM tuoteperhe
                            WHERE yhtio = '$kukarow[yhtio]'
                            AND tuoteno = '$tuoteno'";
                $tuoteperhe_result = pupe_query($query);

                if (mysql_num_rows($tuoteperhe_result) > 0) {
                    $lisa = " and tuoteno in ('$tuoteno',";
                }

                while ($tuoteperhe_row = mysql_fetch_assoc($tuoteperhe_result)) {
                    $lisa .= "'$tuoteperhe_row[isatuoteno]',";
                }

                if (mysql_num_rows($tuoteperhe_result) > 0) {
                    $lisa = substr($lisa, 0, -1);
                    $lisa .= ") ";
                }

				$query = "	SELECT tyyppi, count(*) countti
							from yhteensopivuus_tuote
							where yhtio = '$kukarow[yhtio]'
							$lisa
							GROUP BY 1
							HAVING countti > 0";
				$yhtresult = pupe_query($query);

				if (mysql_num_rows($yhtresult) > 0) {
					while ($yhtrow = mysql_fetch_assoc($yhtresult)) {
						if ($yhtrow["tyyppi"] == "HA") $yhttoim = "";
						else $yhttoim = $yhtrow["tyyppi"];

						echo "<form action='yhteensopivuus_tuote.php' method='post'>";
						echo "<input type='hidden' name='tee' value='etsi'>";
                        echo "<input type='hidden' name='lopetus' value='$tkysy_lopetus'>";
						echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
						echo "<input type='hidden' name='toim' value='$yhttoim'>";
						echo "<input type='submit' value='".t("Siirry tuotteen $yhttoim yhteensopivuuksiin")."'>";
						echo "</form>";
					}
				}
				echo "<br>";
			}

			// Saldot, korvaavat ja vastaavat
			echo "<table><tr><td class='back' valign='top' style='padding:0px; margin:0px;height:0px;'>";

			if ($tuoterow["ei_saldoa"] == '') {

				$yhtiot = array();
				$yhtiot[] = $kukarow["yhtio"];

				// Halutaanko saldot koko konsernista?
				if ($yhtiorow["haejaselaa_konsernisaldot"] == "S") {
					$query = "	SELECT *
								FROM yhtio
								WHERE konserni = '$yhtiorow[konserni]'
								AND konserni != ''
								AND yhtio != '$kukarow[yhtio]'";
					$result = pupe_query($query);

					while ($row = mysql_fetch_assoc($result)) {
						$yhtiot[] = $row["yhtio"];
					}
				}

				// Varastosaldot ja paikat
				echo "<font class='message'>".t("Varastopaikat")."</font><hr>";

				// Saldot
				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Varasto")."</th>";
				echo "<th>".t("Varastopaikka")."</th>";
				echo "<th>".t("Saldo")."</th>";
				echo "<th>".t("Hyllyssä")."</th>";
				echo "<th>".t("Myytävissä")."</th>";
				echo "</tr>";

				$kokonaissaldo = 0;
				$kokonaishyllyssa = 0;
				$kokonaismyytavissa = 0;
				$kokonaissaldo_tapahtumalle = 0;

				//saldot per varastopaikka
				if ($tuoterow["sarjanumeroseuranta"] == "E" or $tuoterow["sarjanumeroseuranta"] == "F" or $tuoterow["sarjanumeroseuranta"] == "G") {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								sarjanumeroseuranta.sarjanumero era,
								concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
								JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
								and sarjanumeroseuranta.tuoteno = tuote.tuoteno
								and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
								and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
								and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
								and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
								and sarjanumeroseuranta.myyntirivitunnus = 0
								and sarjanumeroseuranta.era_kpl != 0
								WHERE tuote.yhtio in ('".implode("','", $yhtiot)."')
								and tuote.tuoteno = '$tuoteno'
								GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
				}
				else {
					$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
								tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
								concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
								varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
					 			FROM tuote
								JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
								JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
								and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
								WHERE tuote.yhtio in ('".implode("','", $yhtiot)."')
								and tuote.tuoteno = '$tuoteno'
								ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
				}

				$sresult = pupe_query($query);

				if (mysql_num_rows($sresult) > 0) {
					while ($saldorow = mysql_fetch_assoc ($sresult)) {

						list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], '', $saldoaikalisa, $saldorow["era"]);

						//summataan kokonaissaldoa ja vain oman firman saldoa
						$kokonaissaldo += $saldo;
						$kokonaishyllyssa += $hyllyssa;
						$kokonaismyytavissa += $myytavissa;

						if ($saldorow["yhtio"] == $kukarow["yhtio"]) {
							$kokonaissaldo_tapahtumalle += $saldo;
						}

						echo "<tr>";
						echo "<td>$saldorow[nimitys] $saldorow[tyyppi] $saldorow[era]</td>";

						if ($saldorow["hyllyalue"] == "!!M") {
							$asiakkaan_tunnus = (int) $saldorow["hyllynro"].$saldorow["hyllyvali"].$saldorow["hyllytaso"];

							$query = "	SELECT nimi, toim_nimi
										FROM asiakas
										WHERE yhtio = '{$kukarow["yhtio"]}'
										AND tunnus = '$asiakkaan_tunnus'";
							$asiakasresult = pupe_query($query);
							$asiakasrow = mysql_fetch_assoc($asiakasresult);
							echo "<td>{$asiakasrow["nimi"]}</td>";
						}
						else {
							echo "<td>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</td>";
						}

						echo "<td align='right'>".sprintf("%.2f", $saldo)."</td>
									<td align='right'>".sprintf("%.2f", $hyllyssa)."</td>
									<td align='right' style='font-weight:bold;'>".sprintf("%.2f", $myytavissa)."</td>
									</tr>";
					}
				}

				list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, 'ORVOT', '', '', '', '', '', '', '', $saldoaikalisa);

				if ($myytavissa != 0) {
					echo "<tr>";
					echo "<td>".t("Tuntematon")."</td>";
					echo "<td>?</td>";
					echo "<td align='right'>".sprintf("%.2f", $saldo)."</td>";
					echo "<td align='right'>".sprintf("%.2f", $hyllyssa)."</td>";
					echo "<td align='right'>".sprintf("%.2f", $myytavissa)."</td>";
					echo "</tr>";

					//summataan kokonaissaldoa ja vain oman firman saldoa.
					$kokonaissaldo += $saldo;
					$kokonaishyllyssa += $hyllyssa;
					$kokonaismyytavissa += $myytavissa;
				}

				echo "<tr>
						<th colspan='2'>".t("Yhteensä")."</th>
						<th style='text-align:right;'>".sprintf("%.2f", $kokonaissaldo)."</th>
						<th style='text-align:right;'>".sprintf("%.2f", $kokonaishyllyssa)."</th>
						<th style='text-align:right;'>".sprintf("%.2f", $kokonaismyytavissa)."</th>
						</tr>";

				echo "</table>";
			}

			echo "</td><td class='back' valign='top' style='padding:0px; margin:0px;height:0px;'>";

			//korvaavat tuotteet
			$query  = "SELECT * from korvaavat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$korvaresult = pupe_query($query);

			if (mysql_num_rows($korvaresult) > 0) {
				// Varastosaldot ja paikat
				echo "<font class='message'>".t("Korvaavat tuotteet")."</font><hr>";

				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Tuotenumero")."</th>";
				echo "<th>".t("Myytävissä")."</th>";
				echo "</tr>";

				// tuote löytyi, joten haetaan sen id...
				$row    = mysql_fetch_assoc($korvaresult);
				$id		= $row['id'];

				$query = "SELECT * FROM korvaavat WHERE id='$id' AND tuoteno<>'$tuoteno' AND yhtio='$kukarow[yhtio]' ORDER BY jarjestys, tuoteno";
				$korva2result = pupe_query($query);

				while ($row = mysql_fetch_assoc($korva2result)) {
					list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', '', '', '', '', '', $saldoaikalisa);

					echo "<tr>";
					echo "<td><a href='$PHP_SELF?toim=$toim&tee=Z&tuoteno=".urlencode($row["tuoteno"])."&lopetus=$lopetus'>$row[tuoteno]</a></td>";
					echo "<td align='right'>".sprintf("%.2f", $myytavissa)."</td>";
					echo "</tr>";
				}

				echo "</table>";
			}

			echo "</td><td class='back' valign='top' style='padding:0px; margin:0px;height:0px;'>";

			//vastaavat tuotteet
			$query  = "SELECT * FROM vastaavat WHERE tuoteno='$tuoteno' AND yhtio='$kukarow[yhtio]'";
			$vastaresult = pupe_query($query);

			if (mysql_num_rows($vastaresult) > 0) {
				// Varastosaldot ja paikat
				echo "<font class='message'>".t("Vastaavat tuotteet")."</font><hr>";

				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Tuotenumero")."</th>";
				echo "<th>".t("Myytävissä")."</th>";
				echo "</tr>";

				// tuote löytyi, joten haetaan sen id...
				$row    = mysql_fetch_assoc($vastaresult);
				$id		= $row['id'];

				$query = "SELECT * FROM vastaavat WHERE id='$id' AND tuoteno<>'$tuoteno' AND yhtio='$kukarow[yhtio]' ORDER BY jarjestys, tuoteno";
				$vasta2result = pupe_query($query);

				while ($row = mysql_fetch_assoc($vasta2result)) {
					list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', '', '', '', '', '', '', '', $saldoaikalisa);

					echo "<tr>";
					echo "<td><a href='$PHP_SELF?toim=$toim&tee=Z&tuoteno=".urlencode($row["tuoteno"])."&lopetus=$lopetus'>$row[tuoteno]</a></td>";
					echo "<td align='right'>".sprintf("%.2f", $myytavissa)."</td>";
					echo "</tr>";
				}

				echo "</table>";
			}

			echo "</td><td class='back' valign='top' style='padding:0px; margin:0px;height:0px;'>";

			//Tuotemuutoksia halutaan näyttää, mikäli niitä on.
			$lista = hae_tuotemuutokset($tuoteno);

			if (count($lista) > 0) {
				// tuotemuutoksia.
				echo "<font class='message'>".t("Tuotenumeromuutoksia")."</font><hr>";

				echo "<table>";
				echo "<tr>";
				echo "<th>".t("Vanha tuotenumero")."</th>";
				echo "<th>".t("Muutospvm")."</th>";
				echo "<th>".t("Muuttaja")."</th>";
				echo "</tr>";

				foreach ($lista as $muuttunut_tuote) {
					echo "<tr>";
					echo "<td>{$muuttunut_tuote["tuoteno"]}</td>";
					echo "<td>".tv1dateconv($muuttunut_tuote['muutospvm'], 'X')."</td>";
					echo "<td>{$muuttunut_tuote["kuka"]}</td>";
					echo "</tr>";
				}
				echo "</table>";
			}

			echo "</td></tr></table><br>";

			// Tilausrivit tälle tuotteelle
			$query = "	SELECT if (asiakas.ryhma != '', concat(lasku.nimi,' (',asiakas.ryhma,')'), lasku.nimi) nimi, lasku.tunnus, (tilausrivi.varattu+tilausrivi.jt) kpl,
						tilausrivi.toimaika pvm, tilausrivi.laadittu,
						varastopaikat.nimitys varasto, tilausrivi.tyyppi, lasku.laskunro, lasku.tila laskutila, lasku.tilaustyyppi, tilausrivi.var, lasku2.laskunro as keikkanro, tilausrivi.jaksotettu, tilausrivin_lisatiedot.osto_vai_hyvitys
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
						JOIN lasku use index (PRIMARY) ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
						LEFT JOIN varastopaikat ON varastopaikat.yhtio = lasku.yhtio and varastopaikat.tunnus = lasku.varasto
						LEFT JOIN lasku as lasku2 ON lasku2.yhtio = tilausrivi.yhtio and lasku2.tunnus = tilausrivi.uusiotunnus
						LEFT JOIN asiakas ON asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi in ('L','E','O','G','V','W','M')
						and tilausrivi.tuoteno = '$tuoteno'
						and tilausrivi.laskutettuaika = '0000-00-00'
						and tilausrivi.varattu + tilausrivi.jt != 0
						and tilausrivi.var != 'P'
						ORDER BY pvm, tunnus";
			$jtresult = pupe_query($query);

			if (mysql_num_rows($jtresult) != 0) {

				// Varastosaldot ja paikat
				echo "<font class='message'>".t("Tuotteen tilaukset")."</font><hr>";

				$myyta = $kokonaismyytavissa;

				// Avoimet rivit
				echo "<table>";

				echo "<tr>
						<th>".t("Asiakas/Toimittaja")."</th>
						<th>".t("Tilaus/Saapuminen")."</th>
						<th>".t("Tyyppi")."</th>
						<th>".t("Luontiaika")."</th>
						<th>".t("Toim.aika")."</th>
						<th>".t("Määrä")."</th>
						<th>".t("Myytävissä")."</th>
						</tr>";

				$yhteensa 	= array();
				$ekotettiin = 0;

				while ($jtrow = mysql_fetch_assoc($jtresult)) {

					$tyyppi 	 = "";
					$vahvistettu = "";
					$merkki 	 = "";
					$keikka 	 = "";

					if ($jtrow["tyyppi"] == "O") {

						if ($jtrow["laskutila"] == "K") {
							$tyyppi = t("Lisätty suoraan saapumiselle");
							$keikka = " / ".$jtrow["laskunro"];
						}
						else {
							$tyyppi = t("Ostotilaus");

							if ($jtrow["keikkanro"] > 0) {
								$keikka = " / ".$jtrow["keikkanro"];
							}
						}
						if ($jtrow["kpl"] > 0) {
							$merkki = "+";
						}
						else {
							$merkki = "-";
						}
					}
					elseif ($jtrow["tyyppi"] == "E") {
						$tyyppi = t("Ennakkotilaus");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "G" and $jtrow["tilaustyyppi"] == "S") {
						$tyyppi = t("Sisäinen työmääräys");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "G") {
						$tyyppi = t("Varastosiirto");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "V") {
						$tyyppi = t("Kulutus");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "L" and $jtrow["var"] == "J") {
						$tyyppi = t("Jälkitoimitus");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "L" and $jtrow["kpl"] > 0 and $jtrow["osto_vai_hyvitys"] == "H") {
						// Marginaalioston hyvitys
						$tyyppi = t("Käytetyn tavaran hyvitys");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "L" and $jtrow["kpl"] > 0) {
						// Normimyynti
						$tyyppi = t("Myynti");
						$merkki = "-";
					}
					elseif ($jtrow["tyyppi"] == "L" and $jtrow["kpl"] < 0 and $jtrow["osto_vai_hyvitys"] != "O") {
						// Normihyvitys
						$tyyppi = t("Hyvitys");
						$merkki = "+";
					}
					elseif ($jtrow["tyyppi"] == "L" and $jtrow["kpl"] < 0 and $jtrow["osto_vai_hyvitys"] == "O") {
						// Marginaaliosto
						$tyyppi = t("Käytetyn tavaran osto");
						$merkki = "+";
					}
					elseif (($jtrow["tyyppi"] == "W" or $jtrow["tyyppi"] == "M") and $jtrow["tilaustyyppi"] == "W") {
						$tyyppi = t("Valmistus");
						$merkki = "+";
					}
					elseif (($jtrow["tyyppi"] == "W" or $jtrow["tyyppi"] == "M") and $jtrow["tilaustyyppi"] == "V") {
						$tyyppi = t("Asiakkaallevalmistus");
						$merkki = "+";
					}

					if ($jtrow["jaksotettu"] == 1) {
						$vahvistettu = " (".t("Vahvistettu").")";
					}

					$yhteensa[$tyyppi] += $jtrow["kpl"];

					if ($jtrow["varasto"] != "") {
						$tyyppi = $tyyppi." - ".$jtrow["varasto"];
					}

					if ((int) str_replace("-", "", $jtrow["pvm"]) > (int) date("Ymd") and $ekotettiin == 0) {
						echo "<tr>
								<td colspan='6' align='right' class='spec'>".t("Myytävissä nyt").":</td>
								<td align='right' class='spec'>".sprintf('%.2f', $myyta)."</td>
								</tr>";
						$ekotettiin = 1;
					}

					list(, , $myyta) = saldo_myytavissa($tuoteno, "KAIKKI", '', '', '', '', '', '', '', $jtrow["pvm"]);

					echo "<tr>
							<td>$jtrow[nimi]</td>
							<td><a href='$PHP_SELF?toim=$toim&tuoteno=".urlencode($tuoteno)."&tee=NAYTATILAUS&tunnus=$jtrow[tunnus]&lopetus=$lopetus'>$jtrow[tunnus]</a>$keikka</td>
							<td>$tyyppi</td>
							<td>".tv1dateconv($jtrow["laadittu"])."</td>
							<td>".tv1dateconv($jtrow["pvm"])."$vahvistettu</td>
							<td align='right'>$merkki".abs($jtrow["kpl"])."</td>
							<td align='right'>".sprintf('%.2f', $myyta)."</td>
							</tr>";
				}

				foreach ($yhteensa as $type => $kappale) {
					echo "<tr>";
					echo "<th colspan='5'>$type ".t("yhteensä")."</th>";
					echo "<th style='text-align:right;'>$kappale</th>";
					echo "<th></th>";
					echo "</tr>";
				}

				echo "</table><br>";
			}

			if ($toim != "TYOMAARAYS_ASENTAJA") {
				if (!isset($raportti)) {
					if ($tuoterow["tuotetyyppi"] == "R") $raportti="KULUTUS";
					else $raportti="MYYNTI";
				}

				if ($raportti == "KULUTUS") $sele["K"] = "checked";
				else $sele["M"] = "checked";

				echo "<form action='$PHP_SELF#Raportit' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='tuoteno' value='$tuoteno'>
					<input type='hidden' name='tee' value='Z'>
					<input type='hidden' name='historia' value='$historia'>
					<input type='hidden' name='tapahtumalaji' value='$tapahtumalaji'>
					<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>
					<font class='message'>".t("Raportointi")."</font><a href='#' name='Raportit'></a>
					(<input type='radio' onclick='submit()' name='raportti' value='MYYNTI' $sele[M]> ".t("Myynnistä")." /
					<input type='radio' onclick='submit()' name='raportti' value='KULUTUS' $sele[K]> ".t("Kulutuksesta").")
					</form><hr>";

				echo "<table>";

				if ($raportti=="MYYNTI") {
					//myynnit
					$edvuosi  = date('Y')-1;
					$taavuosi = date('Y');

					$query = "	SELECT
								round(sum(if (laskutettuaika >= date_sub(now(),interval 30 day), rivihinta,0)), $yhtiorow[hintapyoristys])	summa30,
								round(sum(if (laskutettuaika >= date_sub(now(),interval 30 day), kate,0)), $yhtiorow[hintapyoristys])  		kate30,
								sum(if (laskutettuaika >= date_sub(now(),interval 30 day), kpl,0))  											kpl30,
								round(sum(if (laskutettuaika >= date_sub(now(),interval 90 day), rivihinta,0)), $yhtiorow[hintapyoristys])	summa90,
								round(sum(if (laskutettuaika >= date_sub(now(),interval 90 day), kate,0)), $yhtiorow[hintapyoristys])		kate90,
								sum(if (laskutettuaika >= date_sub(now(),interval 90 day), kpl,0))											kpl90,
								round(sum(if (YEAR(laskutettuaika) = '$taavuosi', rivihinta,0)), $yhtiorow[hintapyoristys])	summaVA,
								round(sum(if (YEAR(laskutettuaika) = '$taavuosi', kate,0)), $yhtiorow[hintapyoristys])		kateVA,
								sum(if (YEAR(laskutettuaika) = '$taavuosi', kpl,0))											kplVA,
								round(sum(if (YEAR(laskutettuaika) = '$edvuosi', rivihinta,0)), $yhtiorow[hintapyoristys])	summaEDV,
								round(sum(if (YEAR(laskutettuaika) = '$edvuosi', kate,0)), $yhtiorow[hintapyoristys])		kateEDV,
								sum(if (YEAR(laskutettuaika) = '$edvuosi', kpl,0))											kplEDV
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
								WHERE yhtio='$kukarow[yhtio]'
								and tyyppi='L'
								and tuoteno='$tuoteno'
								and laskutettuaika >= '$edvuosi-01-01'";
					$result3 = pupe_query($query);
					$lrow = mysql_fetch_assoc($result3);

					echo "<tr>
							<th>".t("Myynti").":</th>
							<th>".t("Edelliset 30pv")."</th>
							<th>".t("Edelliset 90pv")."</th>
							<th>".t("Vuosi")." $taavuosi</th>
							<th>".t("Vuosi")." $edvuosi</th>
							</tr>";

					echo "<tr><th align='left'>".t("Liikevaihto").":</th>
							<td align='right' nowrap>$lrow[summa30] $yhtiorow[valkoodi]</td>
							<td align='right' nowrap>$lrow[summa90] $yhtiorow[valkoodi]</td>
							<td align='right' nowrap>$lrow[summaVA] $yhtiorow[valkoodi]</td>
							<td align='right' nowrap>$lrow[summaEDV] $yhtiorow[valkoodi]</td></tr>";

					echo "<tr><th align='left'>".t("Myykpl").":</th>
							<td align='right' nowrap>$lrow[kpl30]  ".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."</td>
							<td align='right' nowrap>$lrow[kpl90]  ".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."</td>
							<td align='right' nowrap>$lrow[kplVA]  ".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."</td>
							<td align='right' nowrap>$lrow[kplEDV] ".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."</td></tr>";

					echo "<tr><th align='left'>".t("Kate").":</th>
							<td align='right' nowrap>$lrow[kate30] $yhtiorow[valkoodi]</td>
							<td align='right' nowrap>$lrow[kate90] $yhtiorow[valkoodi]</td>
							<td align='right' nowrap>$lrow[kateVA] $yhtiorow[valkoodi]</td>
							<td align='right' nowrap>$lrow[kateEDV] $yhtiorow[valkoodi]</td></tr>";

					echo "<tr><th align='left'>".t("Katepros").":</th>";

					if ($lrow["summa30"] > 0)
						$kate30pros = round($lrow["kate30"]/$lrow["summa30"]*100,2);

					if ($lrow["summa90"] > 0)
						$kate90pros = round($lrow["kate90"]/$lrow["summa90"]*100,2);

					if ($lrow["summaVA"] > 0)
						$kateVApros = round($lrow["kateVA"]/$lrow["summaVA"]*100,2);

					if ($lrow["summaEDV"] > 0)
						$kateEDVpros = round($lrow["kateEDV"]/$lrow["summaEDV"]*100,2);

					echo "<td align='right' nowrap>$kate30pros %</td>";
					echo "<td align='right' nowrap>$kate90pros %</td>";
					echo "<td align='right' nowrap>$kateVApros %</td>";
					echo "<td align='right' nowrap>$kateEDVpros %</td></tr>";

					echo "</table><br>";
				}
				elseif ($raportti == "KULUTUS") {

					$kk=date("m");
					$vv=date("Y");
					$select_summa = $otsikkorivi = "";
					for($y=1;$y<=12;$y++) {

						$kk--;

						if ($kk == 0) {
							$kk = 12;
							$vv--;
						}

						switch ($kk) {
							case "1":
								$month = "Tammi";
								break;
							case "2":
								$month = "Helmi";
								break;
							case "3":
								$month = "Maalis";
								break;
							case "4":
								$month = "Huhti";
								break;
							case "5":
								$month = "Touko";
								break;
							case "6":
								$month = "Kesä";
								break;
							case "7":
								$month = "Heinä";
								break;
							case "8":
								$month = "Elo";
								break;
							case "9":
								$month = "Syys";
								break;
							case "10":
								$month = "Loka";
								break;
							case "11":
								$month = "Marras";
								break;
							case "12":
								$month = "Joulu";
								break;
						}

						$otsikkorivi .= "<th>".t("$month")."</th>";

						$ppk = date("t");
						$alku="$vv-".sprintf("%02s",$kk)."-01 00:00:00";
						$ed=($vv-1)."-".sprintf("%02s",$kk)."-01 00:00:00";

						if ($select_summa=="") {
							$select_summa .= "	  SUM(if (tapahtuma.laadittu>='$alku' and tapahtuma.laadittu<=DATE_ADD('$alku', interval 1 month) and tyyppi='L', tapahtuma.kpl, 0))*-1 kpl_myynti_$kk
												, SUM(if (tapahtuma.laadittu>='$alku' and tapahtuma.laadittu<=DATE_ADD('$alku', interval 1 month) and tyyppi='V', tapahtuma.kpl, 0))*-1 kpl_kulutus_$kk
												, SUM(if (tapahtuma.laadittu>='$ed' and tapahtuma.laadittu<=DATE_ADD('$ed', interval 1 month) and tyyppi='L', tapahtuma.kpl, 0))*-1 ed_kpl_myynti_$kk
												, SUM(if (tapahtuma.laadittu>='$ed' and tapahtuma.laadittu<=DATE_ADD('$ed', interval 1 month) and tyyppi='V', tapahtuma.kpl, 0))*-1 ed_kpl_kulutus_$kk

												";
						}
						else {
							$select_summa .= "	, SUM(if (tapahtuma.laadittu>='$alku' and tapahtuma.laadittu<=DATE_ADD('$alku', interval 1 month) and tyyppi='L', tapahtuma.kpl, 0))*-1 kpl_myynti_$kk
												, SUM(if (tapahtuma.laadittu>='$alku' and tapahtuma.laadittu<=DATE_ADD('$alku', interval 1 month) and tyyppi='V', tapahtuma.kpl, 0))*-1 kpl_kulutus_$kk
												, SUM(if (tapahtuma.laadittu>='$ed' and tapahtuma.laadittu<=DATE_ADD('$ed', interval 1 month) and tyyppi='L', tapahtuma.kpl, 0))*-1 ed_kpl_myynti_$kk
												, SUM(if (tapahtuma.laadittu>='$ed' and tapahtuma.laadittu<=DATE_ADD('$ed', interval 1 month) and tyyppi='V', tapahtuma.kpl, 0))*-1 ed_kpl_kulutus_$kk

												";
						}

					}

					//	Tutkitaan onko tää liian hias
					$query = "	SELECT
								$select_summa
								FROM tapahtuma use index (yhtio_tuote_laadittu)
								JOIN tilausrivi on tilausrivi.yhtio = tapahtuma.yhtio and tilausrivi.tunnus = tapahtuma.rivitunnus
								WHERE tapahtuma.yhtio='$kukarow[yhtio]'
								and tapahtuma.tuoteno='$tuoteno'
								and tapahtuma.laadittu >= '$ed'
								and tilausrivi.tyyppi IN ('L','W','V')";
					$result3 = pupe_query($query);
					$lrow = mysql_fetch_assoc($result3);

					echo "<table><tr><th>".t("Tyyppi")."</th>$otsikkorivi<th>".t("Yhteensä")."</th></tr>";
					$erittely=array();
					$ed_erittely=array();

					foreach(array("myynti", "kulutus") as $tyyppi) {
						echo "<tr class='aktiivi'><td class='tumma'>".t(str_replace("_"," ",$tyyppi))."</td>";

						$kk=date("m");
						$summa=0;
						$ed_summa=0;

						for($y=1;$y<=12;$y++) {

							$kk--;
							if ($kk == 0) {
								$kk = 12;
							}

							$key="kpl_".$tyyppi."_".$kk;

							$muutos="";
							$muutos_abs = $lrow[$key] - $lrow["ed_".$key];

							if ($lrow["ed_".$key]>0) {
								$muutos_suht = round((($lrow[$key] / $lrow["ed_".$key])-1)*100,2);
							}
							else {
								$muutos_suht=0;
							}

							if ($muutos_abs<>0) {
								$muutos = "edellinen: ".(int)$lrow["ed_".$key]."{".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."} muutos: $muutos_abs{".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."}";
							}

							if ($muutos_suht<>0 and $lrow[$key]<>0 and $lrow["ed_".$key] <> 0) {
								$muutos .= " ($muutos_suht%)";
							}

							if ($lrow[$key]<>0) {
								echo "<td title='$muutos'>".$lrow[$key]."</td>";
							}
							else {
								echo "<td title='$muutos'></td>";
							}

							$summa+=$lrow[$key];
							$ed_summa+=$lrow["ed_".$key];

							$erittely[$kk]+=$lrow[$key];
							$ed_erittely[$kk]+=$lrow["ed_".$key];
						}

						$muutos="";
						$muutos_abs = $summa - $ed_summa;

						if ($ed_summa>0) {
							$muutos_suht = round((($summa / $ed_summa)-1)*100,2);
						}
						else {
							$muutos_suht=0;
						}

						if ($muutos_abs<>0) {
							$muutos = "edellinen: ".(int)$ed_summa."{".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."} muutos: $muutos_abs{".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."}";
						}

						if ($muutos_suht<>0 and $summa<>0 and $ed_summa<>0) {
							$muutos .= " ($muutos_suht%)";
						}

						if ($summa>0) {
							echo "<td class='tumma' title='$muutos'>".number_format($summa, 2, ',', ' ')."</td></tr>";
						}
						else {
							echo "<td class='tumma' title='$muutos'></td></tr>";
						}
					}

					echo "<tr><th>".t("Yhteensä")."</th>";

					$kk=date("m");
					$gt=$ed_gt=0;
					for($y=1;$y<=12;$y++) {

						$kk--;
						if ($kk == 0) {
							$kk = 12;
						}

						$muutos="";
						$muutos_abs = $erittely[$kk] - $ed_erittely[$kk];

						if ($erittely[$kk]>0) {
							$muutos_suht = round((($erittely[$kk] / $erittely[$kk])-1)*100,2);
						}
						else {
							$muutos_suht=0;
						}

						if ($muutos_abs<>0) {
							$muutos = "edellinen: ".(int)$ed_erittely[$kk]."{".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."} muutos: $muutos_abs{".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."";
						}

						if ($muutos_suht<>0 and $erittely[$kk]<>0 and $ed_erittely[$kk]<>0) {
							$muutos .= " ($muutos_suht%)";
						}

						if ($erittely[$kk]>0) {
							echo "<td class='tumma' title='$muutos'>".number_format($erittely[$kk], 2, ',', ' ')."</td>";
							$gt+=$erittely[$kk];
						}
						else {
							echo "<td class='tumma' title='$muutos'></td>";
						}
						$ed_gt+=$ed_erittely[$kk];
					}

					$muutos="";
					$muutos_abs = $gt - $ed_gt;

					if ($ed_gt>0) {
						$muutos_suht = round((($gt / $ed_gt)-1)*100,2);
					}
					else {
						$muutos_suht=0;
					}

					if ($muutos_abs<>0) {
						$muutos = "edellinen: ".(int)$ed_gt."{".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."} muutos: $muutos_abs{".t_avainsana("Y", "", " and avainsana.selite='$tuoterow[yksikko]'", "", "", "selite")."}";
					}

					if ($muutos_suht<>0 and $gt<>0 and $ed_gt <> 0) {
						$muutos .= " ($muutos_suht%)";
					}

					echo "<td class='tumma' title='$muutos'>".number_format($gt, 2, ',', ' ')."</td><tr></table><br><br>";
				}
			}

			if ($tuoterow["sarjanumeroseuranta"] == "S" or $tuoterow["sarjanumeroseuranta"] == "U" or $tuoterow["sarjanumeroseuranta"] == "V" or $tuoterow['sarjanumeroseuranta'] == 'T') {

				$query	= "	SELECT sarjanumeroseuranta.*, sarjanumeroseuranta.tunnus sarjatunnus,
							tilausrivi_osto.tunnus osto_rivitunnus,
							tilausrivi_osto.perheid2 osto_perheid2,
							tilausrivi_osto.nimitys nimitys,
							lasku_myynti.nimi myynimi
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.uusiotunnus
							LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno = '$tuoterow[tuoteno]'
							and sarjanumeroseuranta.myyntirivitunnus != -1
							and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
							and tilausrivi_osto.laskutettuaika != '0000-00-00'";
				$sarjares = pupe_query($query);

				if (mysql_num_rows($sarjares) > 0) {
					echo "<font class='message'>".t("Sarjanumerot")."</font><hr>";

					echo "<table>";
					echo "<tr><th>".t("Nimitys")."</th>";
					echo "<th>".t("Sarjanumero")."</th>";
					echo "<th>".t("Varastopaikka")."</th>";
					echo "<th>".t("Ostohinta")."</th>";
					echo "<th>".t("Varattu asiakaalle")."</th></tr>";

					while($sarjarow = mysql_fetch_assoc($sarjares)) {

						$fnlina1 = "";

						if (($sarjarow["siirtorivitunnus"] > 0) or ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"])) {

							if ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"]) {
								$ztun = $sarjarow["osto_perheid2"];
							}
							else {
								$ztun = $sarjarow["siirtorivitunnus"];
							}

							$query = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno, sarjanumeroseuranta.sarjanumero, tyyppi, otunnus
										FROM tilausrivi
										LEFT JOIN sarjanumeroseuranta ON (tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus)
										WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.tunnus='$ztun'";
							$siires = pupe_query($query);
							$siirow = mysql_fetch_assoc($siires);

							if ($siirow["tyyppi"] == "O") {
								// pultattu kiinni johonkin
								$fnlina1 = " <font class='message'>(".t("Varattu lisävarusteena").": $siirow[tuoteno] <a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($siirow["tuoteno"])."&sarjanumero_haku=".urlencode($siirow["sarjanumero"])."'>$siirow[sarjanumero]</a>)</font>";
							}
							elseif ($siirow["tyyppi"] == "G") {
								// jos tämä on jollain siirtolistalla
								$fnlina1 = " <font class='message'>(".t("Kesken siirtolistalla").": $siirow[otunnus])</font>";
							}
						}

						echo "<tr>
								<td>$sarjarow[nimitys]</td>
								<td><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($tuoterow["tuoteno"])."&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."'>$sarjarow[sarjanumero]</a></td>
								<td>$sarjarow[hyllyalue] $sarjarow[hyllynro] $sarjarow[hyllyvali] $sarjarow[hyllytaso]</td>
								<td align='right'>";
								if ($tuoterow['sarjanumeroseuranta'] == 'V' or $tuoterow['sarjanumeroseuranta'] == 'T') {
									echo sprintf('%.2f', $tuoterow['kehahin']);
								}
								else {
									echo sprintf('%.2f', sarjanumeron_ostohinta("tunnus", $sarjarow["sarjatunnus"]));
								}
								echo "</td>
								<td>$sarjarow[myynimi] $fnlina1</td></tr>";
					}

					echo "</table><br>";
				}
			}
			elseif ($tuoterow["sarjanumeroseuranta"] == "E" or $tuoterow["sarjanumeroseuranta"] == "F" or $tuoterow["sarjanumeroseuranta"] == "G") {

				$query	= "	SELECT sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.parasta_ennen, sarjanumeroseuranta.lisatieto,
							sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso,
							sarjanumeroseuranta.era_kpl kpl,
							sarjanumeroseuranta.tunnus sarjatunnus
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
							and sarjanumeroseuranta.tuoteno = '$tuoterow[tuoteno]'
							and sarjanumeroseuranta.myyntirivitunnus = 0
							and sarjanumeroseuranta.era_kpl != 0
							and tilausrivi_osto.laskutettuaika != '0000-00-00'";
				$sarjares = pupe_query($query);

				if (mysql_num_rows($sarjares) > 0) {
					echo "<font class='message'>".t("Eränumerot")."</font><hr>";

					echo "<table>";
					if ($tuoterow["sarjanumeroseuranta"] == "F") {
						echo "<tr><th colspan='4'>".t("Varasto").":</th></tr>";
					}
					elseif ($tuoterow["sarjanumeroseuranta"] == "G") {
						echo "<tr><th colspan='5'>".t("Varasto").":</th></tr>";
					}
					else {
						echo "<tr><th colspan='3'>".t("Varasto").":</th></tr>";
					}
					echo "<th>".t("Eränumero")."</th>";

					if ($tuoterow["sarjanumeroseuranta"] == "F") {
						echo "<th>".t("Parasta ennen")."</th>";
					}

					echo "<th>".t("Määrä")."</th>";
					if ($tuoterow['sarjanumeroseuranta'] == 'G') {
						echo "<th>",t("Ostohinta"),"</th>";
					}
					echo "<th>".t("Lisätieto")."</th></tr>";

					while($sarjarow = mysql_fetch_assoc($sarjares)) {
						echo "<tr>
								<td><a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($tuoterow["tuoteno"])."&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."'>$sarjarow[sarjanumero]</a></td>";

						if ($tuoterow["sarjanumeroseuranta"] == "F") {
							echo "<td>".tv1dateconv($sarjarow["parasta_ennen"])."</td>";
						}

						echo "<td align='right'>$sarjarow[kpl]</td>";
						if ($tuoterow['sarjanumeroseuranta'] == 'G') {
							echo "<td align='right'>".sprintf('%.2f', sarjanumeron_ostohinta("tunnus", $sarjarow["sarjatunnus"]))."</td>";
						}
						echo "<td>$sarjarow[lisatieto]</td>";

						//	Katsotaan jos meidän pitäisi liittää jotain infoa lisätiedoista
						if (file_exists("inc/generoi_sarjanumeron_info.inc")) {
							require("inc/generoi_sarjanumeron_info.inc");
							$sarjainfo = generoi_sarjanumeron_info($sarjarow["sarjanumero"]);
							if ($sarjainfo!="") {
								echo "<td class='back'>$sarjainfo</td>";
							}
						}

						echo "</tr>";
					}

					echo "</table><br>";
				}
			}

			if ($toim != "TYOMAARAYS_ASENTAJA") {
				// Varastotapahtumat
				echo "<font class='message'>".t("Tuotteen tapahtumat")."</font><hr>";
				echo "<table>";
				echo "<form action='$PHP_SELF#Tapahtumat' method='post'>";
				echo "<input type='hidden' name='toim' value='$toim'>";
				echo "<input type='hidden' name='lopetus' value='$lopetus'>";
				echo "<input type='hidden' name='tee' value='Z'>";
				echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
				echo "<input type='hidden' name='raportti' value='$raportti'>";
				echo "<a href='#' name='Tapahtumat'>";

				if ($historia == "") $historia=1;
				$chk[$historia] = "SELECTED";

				echo "<tr>";
				echo "<th colspan='5'>".t("Näytä tapahtumat").": ";
				echo "<select name='historia' onchange='submit();'>'";
				echo "<option value='1' $chk[1]> ".t("20 viimeisintä")."</option>";

				$query = "SELECT * FROM tilikaudet WHERE yhtio = '$kukarow[yhtio]' ORDER BY tilikausi_loppu DESC";
				$tkresult = pupe_query($query);

				while ($tkrow = mysql_fetch_assoc($tkresult)) {
					$tkchk = "";
					if ($historia == "TK".$tkrow["tunnus"]) {
						$tkchk = "SELECTED";
					}
					echo "<option value='TK".$tkrow["tunnus"]."' $tkchk> ".t("Tilikausi")." ".$tkrow["tilikausi_alku"]." --> ".$tkrow["tilikausi_loppu"]."</option>";
				}

				echo "<option value='4' $chk[4]> ".t("Kaikki tapahtumat")."</option>";
				echo "</select>";

				if ($tapahtumalaji == "laskutus") 			$sel1="SELECTED";
				if ($tapahtumalaji == "tulo") 				$sel2="SELECTED";
				if ($tapahtumalaji == "valmistus") 			$sel3="SELECTED";
				if ($tapahtumalaji == "siirto") 			$sel4="SELECTED";
				if ($tapahtumalaji == "kulutus") 			$sel5="SELECTED";
				if ($tapahtumalaji == "Inventointi") 		$sel6="SELECTED";
				if ($tapahtumalaji == "Epäkurantti") 		$sel7="SELECTED";
				if ($tapahtumalaji == "poistettupaikka") 	$sel8="SELECTED";
				if ($tapahtumalaji == "uusipaikka") 		$sel9="SELECTED";

				if ($tilalehinta != '') {
					$check = "CHECKED";
				}
				else {
					$check = "";
				}

				echo "</th><th colspan='";

				if ($tilalehinta != '') {
					echo 6;
				}
				else {
					echo 5;
				}

				echo "'>".t("Tapahtumalaji").": ";
				echo "<select name='tapahtumalaji' onchange='submit();'>'";
				echo "<option value=''>".t("Näytä kaikki")."</option>";
				echo "<option value='laskutus' $sel1>".t("Laskutukset")."</option>";
				echo "<option value='tulo' $sel2>".t("Tulot")."</option>";
				echo "<option value='valmistus' $sel3>".t("Valmistukset")."</option>";
				echo "<option value='siirto' $sel4>".t("Siirrot")."</option>";
				echo "<option value='kulutus' $sel5>".t("Kulutukset")."</option>";
				echo "<option value='Inventointi' $sel6>".t("Inventoinnit")."</option>";
				echo "<option value='Epäkurantti' $sel7>".t("Epäkuranttiusmerkinnät")."</option>";
				echo "<option value='poistettupaikka' $sel8>".t("Poistetut tuotepaikat")."</option>";
				echo "<option value='uusipaikka' $sel9>".t("Perustetut tuotepaikat")."</option>";
				echo "</select>";
				echo "</th>";

				echo "<th>";
				echo t("Näytä tilausrivin hinta ja ale").": <input type='checkbox' name='tilalehinta' id='tilalehinta' $check onClick='javascript:submit();'>";
				echo "</th>";

				echo "</tr>";

				echo "<tr>";
				echo "<th>".t("Laatija")."</th>";
				echo "<th>".t("Pvm")."</th>";
				echo "<th>".t("Tyyppi")."</th>";
				echo "<th>".t("Määrä")."</th>";
				echo "<th>".t("Kplhinta")."</th>";
				echo "<th>".t("Kehahinta")."</th>";
				echo "<th>".t("Kate")."</th>";
				echo "<th>".t("Arvo")."</th>";
				echo "<th>".t("Var.Arvo")."</th>";
				echo "<th>".t("Var.Saldo")."</th>";
				if ($tilalehinta != '') {
					echo "<th>".t("Hinta / Ale / Rivihinta")."</th>";
				}
				echo "<th>".t("Selite")."";

				echo "</th></form>";
				echo "</tr>";

				//tapahtumat
				if ($historia == '4') {
					$maara = "";
					$ehto  = "";
				}
				elseif (strpos($historia,'TK') !== FALSE) {
					$query = "SELECT tilikausi_alku, tilikausi_loppu FROM tilikaudet WHERE yhtio = '$kukarow[yhtio]' and tunnus = '".substr($historia,2)."'";
					$tkresult = pupe_query($query);
					$tkrow = mysql_fetch_assoc($tkresult);

					$maara = "";
					$ehto  = " and tapahtuma.laadittu >= '$tkrow[tilikausi_alku]' and tapahtuma.laadittu <= '$tkrow[tilikausi_loppu]' ";
				}
				else {
					$maara = "LIMIT 20";
					$ehto  = "";
				}

				$ale_query_concat_lisa = 'concat(';

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
					$ale_query_concat_lisa .= "' ', tilausrivi.ale{$alepostfix}, ' %',";
				}

				$ale_query_concat_lisa = substr($ale_query_concat_lisa, 0, -1);
				$ale_query_concat_lisa .= "),";

				$query = "	SELECT tapahtuma.tuoteno, ifnull(kuka.nimi, tapahtuma.laatija) laatija, tapahtuma.laadittu, tapahtuma.laji, tapahtuma.kpl, tapahtuma.kplhinta, tapahtuma.hinta,
							if (tapahtuma.laji in ('tulo','valmistus'), tapahtuma.kplhinta, tapahtuma.hinta)*tapahtuma.kpl arvo, tapahtuma.selite, lasku.tunnus laskutunnus,
							concat_ws(' ', tapahtuma.hyllyalue, tapahtuma.hyllynro, tapahtuma.hyllyvali, tapahtuma.hyllytaso) tapapaikka,
							tapahtuma.hyllyalue tapahtuma_hyllyalue,
							concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
							tilausrivi.hyllyalue tilausrivi_hyllyalue,
							round(100*tilausrivi.kate/tilausrivi.rivihinta, 2) katepros,
							tilausrivi.tunnus trivitunn,
							tilausrivi.perheid,
							tilausrivin_lisatiedot.osto_vai_hyvitys,
							lasku2.tunnus lasku2tunnus,
							lasku2.laskunro lasku2laskunro,
							concat_ws(' / ', round(tilausrivi.hinta, $yhtiorow[hintapyoristys]), $ale_query_concat_lisa round(tilausrivi.rivihinta, $yhtiorow[hintapyoristys])) tilalehinta
							FROM tapahtuma use index (yhtio_tuote_laadittu)
							LEFT JOIN tilausrivi use index (primary) ON (tilausrivi.yhtio = tapahtuma.yhtio and tilausrivi.tunnus = tapahtuma.rivitunnus)
							LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
							LEFT JOIN lasku use index (primary) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus)
							LEFT JOIN lasku AS lasku2 use index (primary) ON (lasku2.yhtio = tilausrivi.yhtio AND lasku2.tunnus = tilausrivi.uusiotunnus)
							LEFT JOIN kuka ON (kuka.yhtio = tapahtuma.yhtio AND kuka.kuka = tapahtuma.laatija)
							WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
							and tapahtuma.tuoteno = '$tuoteno'
							$ehto
							ORDER BY tapahtuma.laadittu desc, tapahtuma.tunnus desc
							$maara";
				$qresult = pupe_query($query);

				// jos jsarjanumeroseuranta S tai U ja inout varastonarvo
				if ($tuoterow["sarjanumeroseuranta"] == "S" or $tuoterow["sarjanumeroseuranta"] == "U") {
					$kokonaissaldo_tapahtumalle = $sarjanumero_kpl;
				}

				$vararvo_nyt = sprintf('%.2f',$kokonaissaldo_tapahtumalle*$tuoterow["kehahin"]);
				$saldo_nyt = $kokonaissaldo_tapahtumalle;

				if ($tuoterow["ei_saldoa"] == "") {
					echo "<tr class='aktiivi'>";
					echo "<td colspan='5'>".t("Varastonarvo nyt").":</td>";
					echo "<td align='right'>$tuoterow[kehahin]</td>";
					echo "<td align='right'></td>";
					echo "<td align='right'>$vararvo_nyt</td>";
					echo "<td align='right'>".sprintf('%.2f',$kokonaissaldo_tapahtumalle*$tuoterow["kehahin"])."</td>";
					echo "<td align='right'>".sprintf('%.2f', $saldo_nyt)."</td>";
					echo "<td></td>";

					if ($tilalehinta != '') {
						echo "<td></td>";
					}

					echo "</tr>";
				}

				// Onko käyttäjällä oikeus nähdä valmistuksia tai reseptejä
				$oikeu_t1 = tarkista_oikeus("tilauskasittely/tilaus_myynti.php", "VALMISTAVARASTOON");

				if ($yhtiorow["raaka_aineet_valmistusmyynti"] == "N") {
					$oikeu_t2 = FALSE;
				}
				else {
					$oikeu_t2 = tarkista_oikeus("tilauskasittely/tilaus_myynti.php", "VALMISTAASIAKKAALLE");
				}

				$oikeu_t3 = tarkista_oikeus("tilauskasittely/valmista_tilaus.php", "");
				$oikeu_t4 = tarkista_oikeus("tuoteperhe.php", "RESEPTI");

				while ($prow = mysql_fetch_assoc ($qresult)) {

					$vararvo_nyt -= $prow["arvo"];

					// Epäkuranteissa saldo ei muutu
					if ($prow["laji"] != "Epäkurantti") {
						$saldo_nyt -= $prow["kpl"];
					}

					if ($tapahtumalaji == "" or strtoupper($tapahtumalaji)==strtoupper($prow["laji"])) {
						echo "<tr class='aktiivi'>";
						echo "<td nowrap valign='top'>$prow[laatija]</td>";
						echo "<td nowrap valign='top'>".tv1dateconv($prow["laadittu"], "pitka")."</td>";
						echo "<td nowrap valign='top'>";

						if ($prow["laji"] == "laskutus" and $prow["laskutunnus"] != "") {
							echo "<a href='raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$prow[laskutunnus]&lopetus=$tkysy_lopetus'>".t("$prow[laji]")."</a>";
						}
						elseif ($prow["laji"] == "tulo" and $prow["laskutunnus"] != "") {
							echo "<a href='raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus=$prow[laskutunnus]&lopetus=$tkysy_lopetus'>".t("$prow[laji]")."</a>";
						}
						elseif ($prow["laji"] == "siirto" and $prow["laskutunnus"] != "") {
							echo "<a href='$PHP_SELF?tuoteno=".urlencode($tuoteno)."&tee=NAYTATILAUS&tunnus=$prow[laskutunnus]&lopetus=$lopetus'>".t("$prow[laji]")."</a>";
						}
						elseif ($prow["laji"] == "valmistus" and $prow["laskutunnus"] != "") {
							echo "<a href='$PHP_SELF?tuoteno=".urlencode($tuoteno)."&tee=NAYTATILAUS&tunnus=$prow[laskutunnus]&lopetus=$lopetus'>".t("$prow[laji]")."</a>";

							// Näytetään tämä vain jos käyttäjällä on oikeus tehdä valmistuksia tai reseptejä
							if ($oikeu_t1 or $oikeu_t2 or $oikeu_t3 or $oikeu_t4) {
								echo "&nbsp;<img src='{$palvelin2}pics/lullacons/info.png' class='tooltip' id='$prow[trivitunn]'>";

								// Näytetään mistä tuotteista tämä on valmistettu
								echo "<div id='div_$prow[trivitunn]' class='popup' style='width:200px;'>";
								echo "<table>";

								$query = "	SELECT tilausrivi.nimitys,
											tilausrivi.tuoteno,
											tapahtuma.kpl * -1 'kpl',
											tapahtuma.hinta,
											tapahtuma.kpl * tapahtuma.hinta * -1 yhteensa
											FROM tilausrivi
											JOIN tapahtuma ON tapahtuma.yhtio=tilausrivi.yhtio and tapahtuma.laji='kulutus' and tapahtuma.rivitunnus=tilausrivi.tunnus
											WHERE tilausrivi.yhtio	= '$kukarow[yhtio]'
											and tilausrivi.otunnus = $prow[laskutunnus]
											and tilausrivi.perheid = $prow[perheid]
											and tilausrivi.tyyppi = 'V'
											ORDER BY tilausrivi.tunnus";
								$rresult = pupe_query($query);

								echo "<tr>
										<th>".t("Nimitys")."</th>
										<th>".t("Tuoteno")."</th>
										<th>".t("Kpl")."</th>
										<th>".t("Arvo")."</th>
										<th>".t("Yhteensä")."</th>
										</tr>";

								$ressuyhteensa = 0;

								while ($rrow = mysql_fetch_assoc ($rresult)) {
									echo "<tr>
											<td>$rrow[nimitys]</td>
											<td>$rrow[tuoteno]</td>
											<td align='right'>$rrow[kpl]</td>
											<td align='right'>$rrow[hinta]</td>
											<td align='right'>".sprintf("%.2f", $rrow["yhteensa"])."</td>
											</tr>";
									$ressuyhteensa += $rrow["yhteensa"];
								}

								echo "<tr>
										<td class='tumma' colspan='4'></td>
										<td class='tumma' align='right'>".sprintf("%.2f", $ressuyhteensa)."</td>
										</tr>";

								echo "</table>";
								echo "</div>";
							}
						}
						else {
							echo t("$prow[laji]");
						}

						echo "</td>";

						echo "<td nowrap align='right' valign='top'>$prow[kpl]</td>";
						echo "<td nowrap align='right' valign='top'>".hintapyoristys($prow["kplhinta"])."</td>";
						echo "<td nowrap align='right' valign='top'>$prow[hinta]</td>";

						if ($prow["laji"] == "laskutus") {
							echo "<td nowrap align='right' valign='top'>$prow[katepros]%</td>";
						}
						else {
							echo "<td nowrap align='right' valign='top'></td>";
						}

						if ($tuoterow["ei_saldoa"] == "") {
							echo "<td nowrap align='right' valign='top'>".sprintf('%.2f', $prow["arvo"])."</td>";
							echo "<td nowrap align='right' valign='top'>".sprintf('%.2f', $vararvo_nyt)."</td>";
							echo "<td nowrap align='right' valign='top'>".sprintf('%.2f', $saldo_nyt)."</td>";
						}
						else {
							echo "<td></td>";
							echo "<td></td>";
							echo "<td></td>";
						}

						if ($tilalehinta != '') {
							echo "<td nowrap align='right' valign='top'>$prow[tilalehinta]</td>";
						}

						echo "<td valign='top'>$prow[selite]";

						if ($prow["laji"] == "tulo" and $prow["lasku2tunnus"] != "") {
							echo "<br><a href='raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus=$prow[lasku2tunnus]&lopetus=$tkysy_lopetus'>".t("Näytä saapuminen")." $prow[lasku2laskunro]</a>";
						}

						if (trim($prow["tapapaikka"]) != "" and $prow["tapahtuma_hyllyalue"] != "!!M") echo "<br>".t("Varastopaikka").": $prow[tapapaikka]";
						elseif (trim($prow["paikka"]) != "" and $prow["tilausrivi_hyllyalue"] != "!!M") echo "<br>".t("Varastopaikka").": $prow[paikka]";

						if ($tuoterow["sarjanumeroseuranta"] != "" and ($prow["laji"] == "tulo" or $prow["laji"] == "laskutus")) {

							if ($prow["laji"] == "tulo") {
								//Haetan sarjanumeron tiedot
								if ($prow["kpl"] < 0) {
									$sarjanutunnus = "myyntirivitunnus";
								}
								else {
									$sarjanutunnus = "ostorivitunnus";
								}
							}
							if ($prow["laji"] == "laskutus") {
								//Haetan sarjanumeron tiedot
								if ($prow["osto_vai_hyvitys"] == '' and $prow["kpl"] < 0) {
									$sarjanutunnus = "myyntirivitunnus";
								}
								elseif ($prow["kpl"] < 0){
									$sarjanutunnus = "ostorivitunnus";
								}
								else {
									$sarjanutunnus = "myyntirivitunnus";
								}
							}

							$query = "	SELECT distinct sarjanumero
										from sarjanumeroseuranta
										where yhtio = '$kukarow[yhtio]'
										and tuoteno = '$prow[tuoteno]'
										and $sarjanutunnus='$prow[trivitunn]'
										and sarjanumero != ''
										group by sarjanumero
										order by sarjanumero";
							$sarjares = pupe_query($query);

							while($sarjarow = mysql_fetch_assoc($sarjares)) {
								if ($tuoterow["sarjanumeroseuranta"] == "E" or $tuoterow["sarjanumeroseuranta"] == "F" or $tuoterow["sarjanumeroseuranta"] == "G") {
									echo "<br>".t("E:nro").": $sarjarow[sarjanumero]";
								}
								else {
									echo "<br>".t("S:nro").": $sarjarow[sarjanumero]";
								}
							}
						}

						echo "</td>";
						echo "</tr>";
					}
				}
				echo "</table>";
			}
			echo $divit;
		}
		else {
			echo "<font class='message'>".t("Yhtään tuotetta ei löytynyt")."!<br></font>";
		}
		$tee = '';
	}

	if ($ulos != "") {
			echo "<form method='post' autocomplete='off'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='lopetus' value='$lopetus'>";
			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<table><tr>";
			echo "<th>".t("Valitse listasta").":</th>";
			echo "<td>$ulos</td>";
			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
			echo "</tr></table>";
			echo "</form>";
	}

	require ("inc/footer.inc");
?>
