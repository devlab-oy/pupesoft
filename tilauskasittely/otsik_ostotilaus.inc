<?php

	//tässä joko tehdään uutta otsikkoa tai muutetaan olemassa olevaa
	if ($tee == '') {
		//päivitetään kukarow[kesken] kun käyttäjä tekee uutta tilausta
		if ($tila == '' and !isset($jatka)) {
			$query = "	UPDATE kuka
						SET kesken=0
						WHERE session = '$session'";
			$result = mysql_query($query) or pupe_error($query);

			$kukarow['kesken'] 	= 0;
			$tilausnumero 		= 0;
		}
	}

	//katsotaan että kukarow kesken ja $tilausnumero stemmaavat keskenään
	if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero!='' or $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
		echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
		exit;
	}

	///* Etsitään toimittajaa *///
	if ($nimi == '' and $ytunnus == '' and $tila == '' and !isset($jatka)) {
		$tila = 'Muuta';
	}
	elseif ($nimi != '' and (($tila == '' or $tila == 'vaihdaasiakas') and !isset($jatka))) {
		//tehdään asiakas- ja toimittajahaku yhteensopivuus
		$ytunnus = $nimi;

		$lause = "<font class='head'>".t("Valitse toimittaja").":</font><hr><br>";
		require ("../inc/kevyt_toimittajahaku.inc");

		if ($ytunnus == '' and $monta > 1) {
			//Löytyi monta sopivaa, näytetään formi, mutta ei otsikkoa
			$tila = '';
		}
		elseif ($ytunnus == '' and $monta < 1) {
			//yhtään asiakasta ei löytynyt, näytetään otsikko
			$tila = 'Muuta';
		}
		else {
			//oikea asiakas on löytynyt
			$tunnus = $toimittajaid;
			$tila = 'Muuta';
		}
	}
	elseif ($ytunnus != '' and (($kukarow['kesken'] == 0 and $tila == '') or $tila == 'vaihdaasiakas') and !isset($jatka)) {
		$tunnus = $toimittajaid;
		$tila = 'Muuta';
	}


	if ($tila == "Muuta" or $tila == "vaihdaasiakas") {
		if($toim == "HAAMU") {
			echo "<font class='head'>".t("Työ/tarvikeoston otsikko").":</font><hr><br>";
		}
		else {
			echo "<font class='head'>".t("Ostotilauksen otsikko").":</font><hr><br>";
		}

		if (($tunnus != "") or ($kukarow["kesken"] != 0)) {
			if ($tunnus != "") {
				$squery = "	SELECT *, tunnus liitostunnus
							FROM toimi
							WHERE tunnus = '$tunnus'";
			}
			else {
				$squery = "	SELECT *
							FROM lasku
							WHERE tunnus = '$kukarow[kesken]'";
			}
			$sresult = mysql_query($squery) or pupe_error($squery);
			$srow = mysql_fetch_array($sresult);

			if ($srow["liitostunnus"]>0 and $toimittajaid == 0) $toimittajaid = $srow["liitostunnus"];
		}

		// jos meillä on jo alatila ja tila ei muokkailla niitä!
		if ($alatila == '') {
			if ($srow['alatila'] != '') $alatila=$srow['alatila'];
		}
		if ($ylatila == '') {
			if ($srow['tila'] != '')    $ylatila=$srow['tila'];
		}

		//laskun valuuttakoodi
		$valkoodi = $srow["valkoodi"];
		if ($valkoodi == '') {
			$valkoodi = $srow["oletus_valkoodi"];
		}
		if ($valkoodi == '') {
			$valkoodi = $yhtiorow["valkoodi"];
		}

		echo "<form method='post' autocomplete='off'>
				<input type='hidden' name='toim' 				value = '$toim'>
				<input type='hidden' name='lopetus' 			value = '$lopetus'>
				<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
				<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
				<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
				<input type='hidden' name='tee' 				value = 'MUUOTAOSTIKKOA'>
				<input type='hidden' name='ovttunnus' 			value = '$srow[ovttunnus]'>
				<input type='hidden' name='verkkotunnus' 		value = '$srow[verkkotunnus]'>
				<input type='hidden' name='ytunnus' 			value = '$srow[ytunnus]'>
				<input type='hidden' name='valkoodi' 			value = '$valkoodi'>
				<input type='hidden' name='maa' 				value = '$srow[maa]'>
				<input type='hidden' name='liitostunnus' 		value = '$srow[liitostunnus]'>
				<input type='hidden' name='ylatila' 			value = '$ylatila'>
				<input type='hidden' name='alatila' 			value = '$alatila'>";

		echo "<table>";
		echo "<tr><td colspan='2'>";
		echo "<table>
				<tr><th colspan='2' align='left' valign='top'>&nbsp;".t("Toimittajan tiedot").":</td></tr>
				<tr><td valign='top'>".t("Nimi").": </td><td><input type='text' name='nimi' size='35' maxlength='31' value='$srow[nimi]'></td></tr>
				<tr><td></td><td><input type='text' name='nimitark' size='35' maxlength='31'  value='$srow[nimitark]'></td></tr>
				<tr><td valign='top'>".t("Osoite").": </td><td><input type='text' name='osoite' size='35' maxlength='31' value='$srow[osoite]'></td></tr>
				<tr>
			  	<td valign='top'>".t("Postitp").": </td><td><input type='text' name='postino' size='7' maxlength='15' value='$srow[postino]'>
			  	<input type='text' name='postitp' size='21' maxlength='20' value='$srow[postitp]'>
			  	<input type='hidden' name='maa' value='$srow[maa]'>";

		if($toimittajaid == 0 or $tila == "vaihdaasiakas") {
			echo "	</td></tr></table></td></tr><tr>
					<td class='back' colspan = '2' align = 'right'><input type = 'hidden' name = 'tila' value = 'vaihdaasiakas'><input type='submit' value='".t("Hae")."'></td>
					</tr></table></form>";

			$formi  = "paaformi";
			$kentta = "nimi";
		}
		else {

			echo "</td></tr></table></td>";
			echo "<td colspan = '2'><table>";
			echo "<tr><th colspan='2' align='left' valign='top'>&nbsp; ".t("Toimitusosoite").":</td></tr>";
			echo "<tr><td valign='top'> ".t("Nimi").": </td><td><input type='text' name='tnimi' size='35' maxlength='31' value='$srow[toim_nimi]'></td></tr>";
			echo "<tr><td> </td><td><input type='text' name='tnimitark' size='35' maxlength='31'  value='$srow[toim_nimitark]'></td></tr>";
			echo "<tr><td valign='top'>".t("Osoite").": </td><td><input type='text' name='tosoite' size='35' maxlength='31' value='$srow[toim_osoite]'></td></tr>";
			echo "<tr><td valign='top'>".t("Postitp").": </td><td><input type='text' name='tpostino' size='8' maxlength='15' value='$srow[toim_postino]'>";
			echo "<input type='text' name='tpostitp' size='21' maxlength='20' value='$srow[toim_postitp]'></td></tr>";
			echo "<input type='hidden' name='tmaa' value='$srow[toim_maa]'>";
			echo "</table>";
			echo "</td></tr>";

			if ($kukarow['kesken'] == 0 and $yhtiorow['ostotilaukseen_toimittajan_toimaika'] != '2') {
				$toimpp = $kerpp = date("j");
				$toimkk = $kerkk = date("n");
				$toimvv = $kervv = date("Y");
			}
			elseif ($kukarow['kesken'] == 0 and $yhtiorow['ostotilaukseen_toimittajan_toimaika'] == '2') {
				$toimittajan_toimaika = date('Y-m-d',time() + $srow["oletus_toimaika"] * 24 * 60 * 60);

				list($toimvv, $toimkk, $toimpp) = explode('-', $toimittajan_toimaika);
				list($kervv, $kerkk, $kerpp)    = explode('-', $toimittajan_toimaika);

			}
			else {
				list($toimvv, $toimkk, $toimpp) = explode('-', $srow["toimaika"]);
				list($kervv, $kerkk, $kerpp)    = explode('-', $srow["kerayspvm"]);
				$kerpp = substr($kerpp,0,2);
				$toimpp = substr($toimpp,0,2);

			}

			//voidaan tarvita
			if ($toimvv == '') {
				$toimpp = date("j");
				$toimkk = date("n");
				$toimvv = date("Y");
			}
			if ($kervv == '') {
				$kerpp = date("j");
				$kerkk = date("n");
				$kervv = date("Y");
			}

			echo "<tr><td>".t("Toimitusajankohta").": </td><td valign='middle'>
					<input type='text' name='toimpp' value='$toimpp' size='3'>
					<input type='text' name='toimkk' value='$toimkk' size='3'>
					<input type='text' name='toimvv' value='$toimvv' size='6'>
					<input type='hidden' name='vtoimaika' value='".$srow["toimaika"]."'></td>";

			$query = "	SELECT DISTINCT kuka.tunnus, kuka.kuka, kuka.nimi, kuka.myyja, kuka.asema
						FROM kuka
						JOIN oikeu ON oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi like '%tilaus_osto.php'
						WHERE kuka.yhtio = '$kukarow[yhtio]'
						and kuka.extranet = ''
						ORDER BY kuka.nimi";
			$yresult = mysql_query($query) or pupe_error($query);

			echo "<td>".t("Ostaja").":</td>";
			echo "<td><select name='myyja'>";

			while($row = mysql_fetch_array($yresult)) {
				$sel = "";
				if ($srow['myyja'] == '' or $srow['myyja'] == 0) {
					if ($row['nimi'] == $kukarow['nimi']) {
						$sel = 'selected';
					}
				}
				else {
					if ($row['tunnus'] == $srow['myyja']) {
						$sel = 'selected';
					}
				}
				echo "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
			}
			echo "</select></td></tr>";

			$query = "select * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]'";
			$yresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($yresult) != 0) {

				echo "<tr><td></td><td></td>";
				echo "<td>".t("Tilaava toimipiste").":</td>";
				echo "<td valign='middle'>";
				echo "<select name='toimipiste'>";
				echo "<option value=''>".t("Ei poikkeavaa toimipistettä")."</option>";

				while($row = mysql_fetch_array($yresult)) {
					$sel = "";
					if ($srow["vanhatunnus"] == $row["tunnus"]) $sel = "selected";
					echo "<option value='$row[tunnus]' $sel>$row[nimi] ($row[ovtlisa])</option>";
				}

				echo "</select></td></tr>";
			}

			if ($toim != "HAAMU") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";
				echo "<td><select name='tilaustyyppi'>";

				$sel = array();
				$sel[$srow["tilaustyyppi"]] = "selected";

				echo "<option value='2' $sel[2]>".t("Normaalitilaus")."</option>";
				echo "<option value='1' $sel[1]>".t("Pikalähetys")."</option>";
				echo "</select></td>";

				//visuaalista säätöä, "täytetään" turha väli
				echo "<td>";
				echo "</td>";
				echo "<td>";
				echo "</td>";
			}

			echo "<tr><td>".t("Kuljetus").": </td><td valign='middle'>";

			$toimtapares = t_avainsana("TOIMITUSTAPA_OS");

			if (mysql_num_rows($toimtapares) > 0) {
				echo "<select name = 'kuljetus'>";
				while($toimtaparow  = mysql_fetch_array($toimtapares)) {
					echo "<option value='$toimtaparow[selite]'>$toimtaparow[selite]</option>";
				}
				echo "</select>";
			}
			else {
				echo "<input type='text' name='kuljetus' value='$srow[kuljetus]' size='25'>";
			}
			echo "</td>";


			echo "<td>".t("Toimitusehto").": </td><td valign='middle'>
					<input type='text' name='maksaja' value='$srow[toimitusehto]' size='25'></td></tr>";

			echo "<tr><td>".t("Huolitsija").": </td><td valign='middle'>
					<input type='text' name='huolitsija' value='$srow[huolitsija]' size='25'></td>";
			echo "<td>".t("Maksuehto").": </td><td valign='middle'>
					<input type='text' name='maksuteksti' value='$srow[maksuteksti]' size='25'></td></tr>";

			echo "<tr><td>".t("Jakelu").": </td><td valign='middle'>
					<input type='text' name='jakelu' value='$srow[jakelu]' size='25'></td>";

			echo "<td>".t("Tilaa Varastoon").":</td>";
			echo "<td><select name='varasto'><option value='0'>".t("Oletus")."</option>";

			$query  = "	SELECT *
						FROM varastopaikat
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY tyyppi, nimitys";
			$vares = mysql_query($query) or pupe_error($query);

			while ($varow = mysql_fetch_array($vares)) {
				if (($varow["tyyppi"] == '') or ($kukarow["varasto"] == $varow["tunnus"])) {

					$sel = '';
					// Jos kukarow varasto on vain YKSI varasto, niin silloin valitaan se
					if ($varow['tunnus']==$srow["varasto"] or ($varow['tunnus'] == $kukarow['varasto'] and $srow['varasto'] == '')) $sel = 'selected';

					echo "<option value='$varow[tunnus]' $sel>$varow[nimitys]</option>";
				}
			}

			echo "</select></td></tr>";

			echo "<tr><td>".t("Tilausyhteyshenkilö").":</td>";

			$yhteysquery = "SELECT *
							FROM yhteyshenkilo
							where yhtio 			 = '$kukarow[yhtio]'
							and liitostunnus 		 = '$srow[liitostunnus]'
							and tyyppi 				 = 'T'
							and tilausyhteyshenkilo != ''";
			$yhteysresult = mysql_query($yhteysquery) or pupe_error($yhteysquery);

			if (mysql_num_rows($yhteysresult) > 0) {
				echo "<td colspan = '3'><select name='seltilausyhteyshenkilo'>";
				echo "<option value = ''>".t("Ei valintaa")."</option>";
				while ($yhteysrow = mysql_fetch_array($yhteysresult)) {
					$sela = "";
					if ($yhteysrow["nimi"] == $srow["tilausyhteyshenkilo"] or ($kukarow["kesken"] == 0 and $yhteysrow["oletusyhteyshenkilo"] != "")) {
						$sela = "SELECTED";
						$srow["tilausyhteyshenkilo"] = "";
					}
					echo "<option value = '$yhteysrow[nimi]' $sela>$yhteysrow[nimi]</option>";

				}
				echo "</select>";
				echo "<input type='text' size='30' name='tilausyhteyshenkilo' value='$srow[tilausyhteyshenkilo]'> (".t("Ostotilaus").")</td></tr>";
			}
			else {
				echo "<td colspan = '3'><input type='text' size='43' name='tilausyhteyshenkilo' value='$srow[tilausyhteyshenkilo]'> (".t("Ostotilaus").")</td></tr>";
			}

			echo "<tr><td>".t("Tilausviite").":</td><td colspan='3'>
				<input type='text' size='61' name='viesti' value='$srow[viesti]'> (".t("Ostotilaus").")</td>";
			echo "</tr>";
			echo "<tr><td>".t("Viesti").":</td><td colspan='3'><textarea name='sisviesti1' rows='2' cols='60'>$srow[sisviesti1]</textarea> (".t("Ostotilaus").")</td></tr>";
			echo "<tr><td>".t("Kommentit").":</td><td colspan='3'><textarea name='comments' rows='2' cols='60'>$srow[comments]</textarea> (".t("Sisäinen").")</td></tr>";
			echo "</table>";
			echo "<input type='hidden' name='from' value='$from'><br>";
			echo "<br><br>";
			echo "<input type='submit' name='jatka' value='".t("Jatka")."'></form>";

			echo "<script LANGUAGE='JavaScript'>
					window.document.otsikko.tila.focus();
					</script>";

			echo "&nbsp;&nbsp;";

			$query = "	SELECT tunnus
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND otunnus = '{$tilausnumero}'
						AND uusiotunnus > 0
						LIMIT 1";
			$vresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($vresult) == 0) {
				echo "<td class='back'>
						<form method='post' autocomplete='off'>
						<input type='hidden' name='toim' 				value = '$toim'>
						<input type='hidden' name='lopetus' 			value = '$lopetus'>
						<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
						<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
						<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
						<input type='hidden' name='tila' 				value = 'vaihdaasiakas'>
						<input type='hidden' name='tee' 				value = 'MUUOTAOSTIKKOA'>
						<input type='submit' ACCESSKEY='A' value='".t("Vaihda toimittaja")."'></form>
						</td>";
			}
		}
	}

	if (isset($jatka)) {

		if ($kukarow["kesken"] == 0) {
			$query = "INSERT into ";

			$vquery = "	SELECT nimi, kurssi, tunnus
						FROM valuu
						WHERE yhtio = '$kukarow[yhtio]'
						and nimi = '$valkoodi'";
			$vresult = mysql_query($vquery) or pupe_error($vquery);
			$vrow = mysql_fetch_array($vresult);

			$postquery = ", vienti_kurssi = '$vrow[kurssi]', laatija = '$kukarow[kuka]', luontiaika=now()";
		}
		else {
			// Pidetään huolta tilausrivien toimituspäivistä ja kerayspaivasta
			$query = "	UPDATE tilausrivi
						SET kerayspvm = '".$kervv."-".$kerkk."-".$kerpp."'
						WHERE otunnus = '$kukarow[kesken]'
						and kerayspvm = '$vkerayspvm'
						and yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE tilausrivi
						SET toimaika = '".$toimvv."-".$toimkk."-".$toimpp."'
						WHERE otunnus = '$kukarow[kesken]'
						and toimaika = '$vtoimaika'
						and yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "UPDATE ";
			$postquery = " WHERE tunnus = '$kukarow[kesken]'";
		}

		// katotaan ollaanko valittu tietty varasto johon kamat tilataan
		if ($varasto > 0 and strlen(trim($tnimi)) == 0) {
			$edique  = "SELECT *
						FROM varastopaikat
						WHERE yhtio	= '$kukarow[yhtio]'
						and tunnus 	= '$varasto'";
			$vares = mysql_query($edique) or pupe_error($edique);
			$varow = mysql_fetch_array($vares);

			if ($varow["nimi"] != "" and $varow["osoite"] != "") {
				$tnimi     = $varow["nimi"];
				$tnimitark = $varow["nimitark"];
				$tosoite   = $varow["osoite"];
				$tpostino  = $varow["postino"];
				$tpostitp  = $varow["postitp"];
				$tmaa  	   = $varow["maa"];
			}
		}

		// katotaan ollaanko valittu tilaajaksi joku muu toimipiste
		if ($toimipiste > 0 and strlen(trim($tnimi)) == 0) {
			$edique  = "SELECT *
						from yhtion_toimipaikat
						where yhtio	= '$kukarow[yhtio]'
						and tunnus	= '$toimipiste'";
			$ediotr = mysql_query($edique) or pupe_error($edique);
			$edioro = mysql_fetch_array($ediotr);

			// laitetaan toimipisteen tiedot toimitusosoitteeseen jos siellä ei vielä muuta ole
			if ($edioro["nimi"] != "" and $edioro["osoite"] != "") {
				$tnimi     	= $edioro["nimi"];
				$tnimitark 	= $edioro["nimitark"];
				$tosoite   	= $edioro["osoite"];
				$tpostino  	= $edioro["postino"];
				$tpostitp  	= $edioro["postitp"];
				$tmaa  		= $edioro["maa"];
			}
		}

		// varmistellaan, että meillä menee jotain toimitusosoite kenttään
		if (strlen(trim($tnimi)) == 0) {
			$tnimi     = $yhtiorow["nimi"];
			$tnimitark = $yhtiorow["nimitark"];
			$tosoite   = $yhtiorow["osoite"];
			$tpostino  = $yhtiorow["postino"];
			$tpostitp  = $yhtiorow["postitp"];
			$tmaa  	   = $yhtiorow["maa"];
		}

		if ($tilausyhteyshenkilo == '' and $seltilausyhteyshenkilo != '') {
			$tilausyhteyshenkilo = $seltilausyhteyshenkilo;
		}

		$crlf = array("\r","\n"); // poistetaan rivinvaihdot kommentista
		$comments = str_replace($crlf, " ", $comments);

		if ($toim == "HAAMU") {
			$tilaustyyppi = "O";
		}

		$query .= "	lasku SET
					alatila 			= '$alatila',
					comments 			= '$comments',
					huolitsija 			= '$huolitsija',
					jakelu 				= '$jakelu',
					kerayspvm 			= '".$kervv."-".$kerkk."-".$kerpp."',
					kuljetus 			= '$kuljetus',
					liitostunnus		= '$liitostunnus',
					maa 				= '$maa',
					maksuteksti 		= '$maksuteksti',
					myyja 				= '$myyja',
					nimi 				= '$nimi',
					nimitark 			= '$nimitark',
					osoite 				= '$osoite',
					ovttunnus 			= '$ovttunnus',
					postino 			= '$postino',
					postitp 			= '$postitp',
					sisviesti1			= '$sisviesti1',
					tila 				= 'O',
					tilaustyyppi		= '$tilaustyyppi',
					tilausyhteyshenkilo	= '$tilausyhteyshenkilo',
					toimaika 			= '".$toimvv."-".$toimkk."-".$toimpp."',
					toimitusehto 		= '$maksaja',
					toimitustapa 		= '$toimitustapa',
					toim_maa 			= '$tmaa',
					toim_nimi 			= '$tnimi',
					toim_nimitark 		= '$tnimitark',
					toim_osoite 		= '$tosoite',
					toim_ovttunnus 		= '$toim_ovttunnus',
					toim_postino 		= '$tpostino',
					toim_postitp 		= '$tpostitp',
					valkoodi 			= '$valkoodi',
					vanhatunnus			= '$toimipiste',
					varasto 			= '$varasto',
					verkkotunnus		= '$verkkotunnus',
					viesti 				= '$viesti',
					viikorkopros 		= '$yhtiorow[viivastyskorko]',
					yhtio 				= '$kukarow[yhtio]',
					ytunnus				= '$ytunnus'";
		$query .= $postquery;
		$result = mysql_query($query) or pupe_error($query);
		$id = mysql_insert_id();

		if ((int) $kukarow["kesken"] == 0 and $id != 0 and $id !== FALSE and $session != "") {
			$query = "	UPDATE kuka
						SET kesken = '$id'
						WHERE yhtio = '$kukarow[yhtio]' AND
						kuka = '$kukarow[kuka]' AND
						session = '$session'";
			$result = mysql_query($query) or pupe_error($query);

			$kukarow["kesken"] = $id;
			$tilausnumero = $id;
		}

		//mennään tilaukselle
		$tee = "Y";
	}
?>
