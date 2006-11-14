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

		//katsotaan että kukarow kesken ja $tilausnumero stemmaavat keskenään
		if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero!='' or $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
			echo "<br><br><br>".t("VIRHE: Sinulla on useita tilauksia auki")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
			exit;
		}
	}

	// Halutaan vaihtaa asiakas tilauksella
	if ($tila == "vaihdaasiakas") {
		$query = "	UPDATE lasku
					SET liitostunnus = 0,
					ytunnus		= '',
					nimi		= '',
					nimitark	= '',
					osoite		= '',
					postino		= '',
					postitp		= ''
					WHERE tunnus = '$kukarow[kesken]'";
		$result = mysql_query($query) or pupe_error($query);

		$tila 		= '';
		$nimi 		= '';
		$asiakasid	= '';
	}

	//Jos ylläpidossa on luotu uusi asiakas
	if ($yllapidossa == "asiakas" and $yllapidontunnus != '') {
		$asiakasid 	= $yllapidontunnus;
	}

	// Etsitään asiakasta
	if ($toim == "TYOMAARAYS" and ($nimi != '' or $rekno!='' or $valmnro!='') and !isset($jatka)) {
		$ytunnus	= $nimi;
		$lause 		= "<font class='head'>".t("Valitse asiakas").":</font><hr><br>";

		require ("../inc/tyomaarayshaku.inc");
	}
	elseif ($toim != "TYOMAARAYS" and $nimi != '' and !isset($jatka) and $asiakasid == 0) {

		//tehdään asiakashaku yhteensopivuus
		$ytunnus 	= $nimi;
		$asiakasid	= "";
		$lause 		= "<font class='head'>".t("Valitse asiakas").":</font><hr><br>";

		require ("../inc/asiakashaku.inc");

		if ($asiakasid == '' and $monta > 1) {
			//Löytyi monta sopivaa, näytetään formi, mutta ei otsikkoa
			$tila = 'EI_NAYTETA';
		}
	}

	if (isset($jatka) and $asiakasid == "" and $override_ytunnus_check != 'YES') {
		echo "<font class='error'>".t("VIRHE: Tilaukselta puuttuu Y-tunnus").":</font><br>";

		$tee	= "OTSIK";
		$tila 	= "";

		unset($jatka);
	}

	$erpcm_virhe = ""; // apumuuttuja

	if ($erpcmvv != "" and $erpcmkk != "" and $erpcmpp != "") {

		$erpquery = "select * from maksuehto where yhtio = '$kukarow[yhtio]' and erapvmkasin != '' order by tunnus limit 1";
		$erpres = mysql_query($erpquery) or pupe_error($erpquery);

		$erpcmvv = (int) $erpcmvv;
		$erpcmkk = (int) $erpcmkk;
		$erpcmpp = (int) $erpcmpp;

		if (!checkdate($erpcmkk, $erpcmpp, $erpcmvv) or mysql_num_rows($erpres) == 0) {
			if (mysql_num_rows($erpres) == 0) {
				echo "<font class='error'>".t("VIRHE: Yritykseltä ei löydy sopivaa maksuehtoa eräpäivän käsinsyöttöön")."!</font><br>";
			}
			else {
				echo "<font class='error'>".t("VIRHE: Syötetty eräpäivä on virheellinen")."!</font><br>";
			}
			$tee	= "OTSIK";
			unset($jatka);
			$erpcm_virhe = "X"; // apumuuttuja
		}
		else {
			$erprow = mysql_fetch_array($erpres);
			$maksuehto = $erprow["tunnus"]; // valitaan tää maksuehdoks
		}
	}

	if ($tila == "" and !isset($jatka)) {
		if ($toim == "TYOMAARAYS") {
			echo "<font class='head'>".t("Työmääräyksen otsikko")."</font><hr>";
		}
		elseif ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
			echo "<font class='head'>".t("Valmistuksen otsikko")."</font><hr>";
		}
		elseif ($toim == "SIIRTOLISTA") {
			echo "<font class='head'>".t("Siirtolistan otsikko")."</font><hr>";
		}
		elseif ($toim == "MYYNTITILI") {
			echo "<font class='head'>".t("Myyntitilin otsikko")."</font><hr>";
		}
		elseif ($toim == "TARJOUS") {
			echo "<font class='head'>".t("Tarjouksen otsikko")."</font><hr>";
		}
		else {
			echo "<font class='head'>".t("Myyntitilauksen otsikko")."</font><hr>";
		}

		echo "<form method='post' action='$PHP_SELF' autocomplete='off' name='paaformi'>";
		echo "<input type='hidden' name='tee' value='OTSIK'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";

		if ($asiakasid != "" or $kukarow["kesken"] != 0) {

			$qlisa = "";
			if ($toim == "TYOMAARAYS") {
				$qlisa = " LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus ";
			}
			elseif($toim == "TARJOUS") {
				$qlisa = " LEFT JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus ";
			}

			if (substr($asiakasid,0,1) == "£") {

				$asiakasid = substr($asiakasid,1);

				$squery = "	SELECT *, hyvaksynnanmuutos as luokka
							FROM lasku
							$qlisa
							WHERE lasku.tunnus = '$asiakasid'";
			}
			elseif ($asiakasid != "" and $tiedot_laskulta == "") {
				$squery = "	SELECT *, tunnus liitostunnus
							FROM asiakas
							WHERE tunnus = '$asiakasid'";
			}
			else {
				$squery = "	SELECT *, hyvaksynnanmuutos as luokka
							FROM lasku
							$qlisa
							WHERE lasku.tunnus = '$kukarow[kesken]'";
			}

			$sresult = mysql_query($squery) or pupe_error($squery);
			$srow = mysql_fetch_array($sresult);
		}

		//laskun valuuttakoodi
		$valkoodi = $srow["valkoodi"];
		if ($valkoodi == '') {
			$valkoodi = $srow["oletus_valkoodi"];
		}
		if ($valkoodi == '') {
			$valkoodi = $yhtiorow["valkoodi"];
		}

		//Tutkitaan onko tilauksella rivejä
		if ($kukarow["kesken"] != 0) {
			$query = "select otunnus from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
			$tempr = mysql_query($query) or pupe_error($query);
			$templ = mysql_num_rows($tempr);
		}
		else {
			$templ = 0;
		}

		// jos meillä on jo alatila ja tila ei muokkailla niitä!
		if ($alatila == '') {
			if ($srow['alatila'] != '') $alatila=$srow['alatila'];
		}
		if ($ylatila == '') {
			if ($srow['tila'] != '')    $ylatila=$srow['tila'];
		}

		echo "	<input type='hidden' name='ylatila' 						 value = '$ylatila'>
				<input type='hidden' name='alatila' 						 value = '$alatila'>
				<input type='hidden' name='poistumistoimipaikka_koodi'       value = '$srow[poistumistoimipaikka_koodi]'>
				<input type='hidden' name='kuljetusmuoto'                    value = '$srow[kuljetusmuoto]'>
				<input type='hidden' name='kauppatapahtuman_luonne'          value = '$srow[kauppatapahtuman_luonne]'>
				<input type='hidden' name='aktiivinen_kuljetus_kansallisuus' value = '$srow[aktiivinen_kuljetus_kansallisuus]'>
				<input type='hidden' name='aktiivinen_kuljetus'              value = '$srow[aktiivinen_kuljetus]'>
				<input type='hidden' name='kontti'                           value = '$srow[kontti]'>
				<input type='hidden' name='sisamaan_kuljetusmuoto'           value = '$srow[sisamaan_kuljetusmuoto]'>
				<input type='hidden' name='sisamaan_kuljetus_kansallisuus'   value = '$srow[sisamaan_kuljetus_kansallisuus]'>
				<input type='hidden' name='sisamaan_kuljetus'                value = '$srow[sisamaan_kuljetus]'>
				<input type='hidden' name='maa_maara'                        value = '$srow[maa_maara]'>
				<input type='hidden' name='laskutusvkopv' 					 value = '$srow[laskutusvkopv]'>
				<input type='hidden' name='ovttunnus' 						 value = '$srow[ovttunnus]'>
				<input type='hidden' name='toim_ovttunnus' 					 value = '$srow[toim_ovttunnus]'>
				<input type='hidden' name='kolm_ovttunnus' 					 value = '$srow[kolm_ovttunnus]'>
				<input type='hidden' name='chn' 							 value = '$srow[chn]'>
				<input type='hidden' name='verkkotunnus' 					 value = '$srow[verkkotunnus]'>
				<input type='hidden' name='ytunnus' 						 value = '$srow[ytunnus]'>
				<input type='hidden' name='valkoodi' 						 value = '$valkoodi'>
				<input type='hidden' name='maa' 							 value = '$srow[maa]'>
				<input type='hidden' name='toim_maa' 						 value = '$srow[toim_maa]'>
				<input type='hidden' name='kolm_maa' 						 value = '$srow[kolm_maa]'>
				<input type='hidden' name='liitostunnus' 					 value = '$srow[liitostunnus]'>";


		//jos käyttäjällä on oletusaiskas
		if ($kukarow["oletus_asiakas"] != 0 and $srow["nimi"] == '') {
			$srow["nimi"] = $kukarow["oletus_asiakas"];
		}

		echo "<table>";
		echo "<tr><td>";

		echo "<table cellpadding='1' cellspacing='1'>";
		echo "<tr>
				<th colspan='2' align='left' valign='top'>&nbsp;".t("Laskutusosoite").":</td></tr>";
		echo "<tr>
				<td valign='top'>".t("Nimi").": </td>
				<td><input type='text' name='nimi' size='35' maxlength='31' value='$srow[nimi]'></td></tr>";
		echo "<tr>
				<td></td>
				<td><input type='text' name='nimitark' size='35' maxlength='31'  value='$srow[nimitark]'></td></tr>";
		echo "<tr>
				<td valign='top'>".t("Osoite").": </td>
				<td><input type='text' name='osoite' size='35' maxlength='31' value='$srow[osoite]'></td></tr>";
		echo "<tr>
				<td valign='top'>".t("Postitp").": </td>
				<td><input type='text' name='postino' size='6' maxlength='6' value='$srow[postino]'> <input type='text' name='postitp' size='21' maxlength='20' value='$srow[postitp]'></td></tr>";

		if ($templ > 0 and $asiakasid == '') {
			echo "<tr><td colspan='2'><br><font class='message'>".t("HUOM! Jos vaihdat asiakasta, tarkista hinnat ja alennukset")."!</font></td></tr>";
		}

		echo "</table>";
		echo "</td>";

		if($srow["liitostunnus"] == 0) {
			echo "</table><br>";
			echo "<input type='submit' value='".t("Hae")."'>";
			echo "</form>";

			$formi  = "paaformi";
			$kentta = "nimi";
		}
		else {
			echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";

			echo "<td valign='top'>";
			echo "<table cellpadding='1' cellspacing='1'>";
			echo "<tr>
					<th colspan='2' align='left' valign='top'>&nbsp; ".t("Toimitusosoite").":</td></tr>";
			echo "<tr>
					<td valign='top'> ".t("Nimi").": </td>
					<td><input type='text' name='tnimi' size='35' maxlength='31' value='$srow[toim_nimi]'></td></tr>";
			echo "<tr>
					<td></td>
					<td><input type='text' name='tnimitark' size='35' maxlength='31'  value='$srow[toim_nimitark]'></td></tr>";
			echo "<tr>
					<td valign='top'>".t("Osoite").": </td>
					<td><input type='text' name='tosoite' size='35' maxlength='31' value='$srow[toim_osoite]'></td></tr>";
			echo "<tr>
					<td valign='top'>".t("Postitp").": </td>
					<td><input type='text' name='tpostino' size='6' maxlength='6' value='$srow[toim_postino]'> <input type='text' name='tpostitp' size='21' maxlength='20' value='$srow[toim_postitp]'></td></tr>";
			echo "</table>";
			echo "</td>";
			echo "</tr>";

			echo "</table><br>";


			if ($toim == "TARJOUS") {
				echo "<table>";
				echo "<tr><td>";
					echo "<table cellpadding='1' cellspacing='1'>";
					echo "<tr>
							<th colspan='2' align='left' valign='top'>&nbsp; ".t("Rinnakkaisostaja").":</th></tr>";
					echo "<tr>
							<td valign='top'> ".t("Nimi").": </td>
							<td><input type='text' name='kolm_nimi' size='35' maxlength='31' value='$srow[kolm_nimi]'></td></tr>";
					echo "<tr>
							<td></td>
							<td><input type='text' name='kolm_nimitark' size='35' maxlength='31'  value='$srow[kolm_nimitark]'></td></tr>";
					echo "<tr>
							<td valign='top'>".t("Osoite").": </td>
							<td><input type='text' name='kolm_osoite' size='35' maxlength='31' value='$srow[kolm_osoite]'></td></tr>";
					echo "<tr>
							<td valign='top'>".t("Postitp").": </td>
							<td><input type='text' name='kolm_postino' size='6' maxlength='6' value='$srow[kolm_postino]'> <input type='text' name='kolm_postitp' size='21' maxlength='20' value='$srow[kolm_postitp]'></td></tr>";
					echo "</table>";
				echo "</td><td>";
					echo "<table cellpadding='1' cellspacing='1'>";
					echo "<tr>
							<td valign='top'> ".t("Sähköpostiosoite").": </td>
							<td>$srow[email]</td></tr>";
					echo "<tr>
							<td valign='top'>Kotipuhelin</td>
							<td>$srow[puhelin]</td></tr>";
					echo "<tr>
							<td valign='top'>".t("Työpuhelin").": </td>
							<td>$srow[fax]</td></tr>";
					echo "<tr>
							<td valign='top'>&nbsp;</td>
							<td></td></tr>";
					echo "<tr>
							<td valign='top'></td>
							<td></td></tr>";	
					echo "</table>";		
				echo "</td></tr></table><br>";
			}



			echo "<table>";
			echo "<tr><th colspan='4'>".t("Tilauksen tiedot").":</th></tr>";


			//Tutkitaan onko laskulla maksupositioita
			$query = "	SELECT *
						FROM maksupositio
						WHERE maksupositio.yhtio ='$kukarow[yhtio]'
						and maksupositio.otunnus ='$kukarow[kesken]'";
			$result = mysql_query($query) or pupe_error($query);

			echo "<tr><td>".t("Maksuehto").":</td>";

			if(mysql_num_rows($result) > 0) {
				echo "<td>$srow[maksuehto]</td>";
				echo "<input type='hidden' name='maksuehto' value = '$srow[maksuehto]'>";
			}
			else {

				echo "<td><select name='maksuehto'>";

				$query = "	SELECT tunnus, concat_ws(' ', kassa_teksti, teksti)
							FROM maksuehto
							WHERE yhtio = '$kukarow[yhtio]' order by jarjestys,concat_ws(' ', kassa_teksti, teksti), tunnus";
				$mresult = mysql_query($query) or pupe_error($query);

				while($row=mysql_fetch_array($mresult)) {
					$sel = "";
					if ($row[0] == $srow["maksuehto"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[0]' $sel>$row[1]";
				}
				echo "</select></td>";
			}
			echo "<td>".t("Erikoisalennus").":</td>
				<td><input type='text' name='erikoisale' value='$srow[erikoisale]'></td>
				</tr>";

			// otetaan erpcm kannasta jos mitään ei ole syötetty ruudulle
			if ($srow["erpcm"] != "" and (int) $erpcmpp == 0 and (int) $ercpmkk == 0 and (int) $erpcmvv == 0 and $erpcm_virhe == "") {
				list($erpcmvv, $erpcmkk, $erpcmpp) = split('-', $srow["erpcm"]);
			}

			// jos päivämäärä on nollaa laitetaan tyhjää
			if ((int) $erpcmpp == 0 and (int) $erpcmkk == 0 and (int) $erpcmvv == 0) {
				$erpcmpp = "";
				$erpcmkk = "";
				$erpcmvv = "";
			}

			echo "<tr><td>".t("Eräpäivä").":</td>
				<td>
				<input type='text' name='erpcmpp' value='$erpcmpp' size='3'>
				<input type='text' name='erpcmkk' value='$erpcmkk' size='3'>
				<input type='text' name='erpcmvv' value='$erpcmvv' size='6'>
				</td>";

			echo "<td>".t("Rahdinmaksaja").":</td>
				<td><select name='maksaja'>
					<option value='O'>".t("Oletus")."</option>
					<option value='K'>".t("Lähettäjä")."</option>
					<option value=''>".t("Vastaanottaja")."</option>
					</select>
				</td>
				</tr>";


			echo "<tr><td>".t("Toimitustapa").":</td>";
			echo "<td><select name='toimitustapa'>";

			$query = "	SELECT tunnus, selite
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY jarjestys,selite";
			$tresult = mysql_query($query) or pupe_error($query);

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";
				if ($row[1] == $srow["toimitustapa"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[1]' $sel>$row[1]";
			}
			echo "</select></td>";

			//yhtiön oletusalvi!
			$wquery = "select selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
			$wtres  = mysql_query($wquery) or pupe_error($wquery);
			$wtrow  = mysql_fetch_array($wtres);

			echo "<td>".t("Alv").":</td>";

			if ($srow["vienti"] == '') {
				if (isset($srow)) {
					if ($srow['alv'] == 0) {
						$sel00 = 'selected';
						$sel22 ='';
					}
					if ($srow['alv'] == $wtrow["selite"]) {
						$sel22 = 'selected';
						$sel00 = '';
					}
				}

				echo " <td>
						<select name='alv'>
						<option value='$wtrow[selite]' $sel22>".t("Verollinen myynti")."</option>
						<option value='0' $sel00>".t("Veroton myynti")."</option>
						</select>
						</td></tr>";
			}
			else {
				echo " <td>".t("Veroton myynti")."
						<input type='hidden' name='alv' value='0'>
						</td></tr>";
			}

			if ($kukarow['kesken'] == 0) {
				$toimpp = $kerpp = date(j);
				$toimkk = $kerkk = date(n);
				$toimvv = $kervv = date(Y);
			}
			else {
				list($toimvv, $toimkk, $toimpp) = split('-', $srow["toimaika"]);
				list($kervv, $kerkk, $kerpp)    = split('-', $srow["kerayspvm"]);
				$kerpp = substr($kerpp,0,2);
				$toimpp = substr($toimpp,0,2);

			}

			//voidaan tarvita jos asiakas on vaihdettu
			if ($toimvv == '') {
				$toimpp = date(j);
				$toimkk = date(n);
				$toimvv = date(Y);
			}
			if ($kervv == '') {
				$kerpp = date(j);
				$kerkk = date(n);
				$kervv = date(Y);
			}


			echo "<tr><td>".t("Toimitusajankohta").": </td><td valign='middle'>
					<input type='text' name='toimpp' value='$toimpp' size='3'>
					<input type='text' name='toimkk' value='$toimkk' size='3'>
					<input type='text' name='toimvv' value='$toimvv' size='6'>
					<input type='hidden' name='vtoimaika' value='".$srow["toimaika"]."'></td>";


			if ($srow['vienti'] == '') {
				$sel1 = 'SELECTED';
				$sel2 = '';
				$sel3 = '';
				$vteksti=t("Kotimaa");
			}
			elseif ($srow['vienti'] == 'E') {
				$sel1 = '';
				$sel2 = 'SELECTED';
				$sel3 = '';
				$vteksti=t("Vienti EU");
			}
			elseif ($srow['vienti'] == 'K') {
				$sel1 = '';
				$sel2 = '';
				$sel3 = 'SELECTED';
				$vteksti=t("Vienti ei-EU");
			}

			echo "<td>".t("Vienti").":</td>";

			if ($srow["alv"] == 99.99) {
				echo "<td><input type='hidden' name='vienti' value=''>".t("Kotimaan marginaalimyyntiä")."</td>";
			}
			else {
				echo "<td>
					<select name='vienti'>
					<option value=''  $sel1>".t("Kotimaa")."</option>
					<option value='E' $sel2>".t("vienti EU")."</option>
					<option value='K' $sel3>".t("Vienti ei-EU")."</option>
					</select></td>";
			}

			echo "</tr>";
			echo "<tr><td>".t("Keräysajankohta").": </td><td valign='middle'>
					<input type='text' name='kerpp' value='$kerpp' size='3'>
					<input type='text' name='kerkk' value='$kerkk' size='3'>
					<input type='text' name='kervv' value='$kervv' size='6'>
					<input type='hidden' name='vkerayspvm' value='".substr($srow["kerayspvm"],0,10)."'></td>";


			//jos käyttäjällä on myyjänro
			if ($kukarow["myyja"] != 0) {
				$myyjanro = $kukarow["myyja"];
			}

			echo "<td>".t("Myyjänro").": <input type='text' size='8' name='myyjanro' value='$myyjanro'> ".t("tai")."</td>";
			echo "<td><select name='myyja'>";

			$query = "	SELECT tunnus, kuka, nimi, myyja
						FROM kuka
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY nimi";
			$yresult = mysql_query($query) or pupe_error($query);

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

			if ($toim == "TYOMAARAYS") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on työmääräys
				echo "<input type='hidden' name='tilaustyyppi' value='A'>";

				echo "<td>".t("Työmääräys")."</td>";
			}
			elseif ($toim == "TARJOUS") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on tarjous
				echo "<input type='hidden' name='tilaustyyppi' value='T'>";

				echo "<td>".t("Tarjous")."</td>";
			}
			elseif ($toim == "MYYNTITILI") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on myyntitili niin laitetaan tilaustyypiksi M
				echo "<input type='hidden' name='tilaustyyppi' value='M'>";


				$query = "	SELECT tunnus
							FROM varastopaikat
							WHERE yhtio = '$kukarow[yhtio]'
							and alkuhyllyalue = '!!M'
							and loppuhyllyalue = '!!M'";
				$tresult = mysql_query($query) or pupe_error($query);
				$mrow = mysql_fetch_array($tresult);

				//pupesoftissa on myytitili aina varastoalueella ##M
				echo "<input type='hidden' name='clearing' value='$mrow[tunnus]'>";


				echo "<td>".t("Myyntitili")."</td>";
			}
			elseif ($toim == "VALMISTAASIAKKAALLE") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on valmistus asiakkaalle niin laitetaan tilaustyypiksi V
				echo "<input type='hidden' name='tilaustyyppi' value='V'>";

				echo "<td>".t("Valmistus-Tilaus")."</td>";
			}
			elseif ($srow["tilaustyyppi"] != 'E' or $templ == 0) {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";
				echo "<td><select name='tilaustyyppi'>";

				$selenna = "";
				$selnorm = "";

				if ($srow["tilaustyyppi"] == 'E') {
					$selenna = 'selected';
				}
				else {
					$selnorm = 'selected';
				}

				echo "<option value='N' $selnorm>".t("Normaalitilaus")."</option>";
				echo "<option value='E' $selenna>".t("Ennakkotilaus")."</option>";

				echo "</select></td>";
			}
			else {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";
				echo "<input type='hidden' name='tilaustyyppi' value='E'>";
				echo "<td>".t("Ennakkotilaus")."</td>";
			}

			echo "<td>".t("Myy varastosta").":</td>";
			echo "<td><select name='varasto'><option value='0'>".t("Kaikista")."</option>";

			$query  = "	SELECT *
						FROM varastopaikat
						WHERE yhtio='$kukarow[yhtio]'";
			$vares = mysql_query($query) or pupe_error($query);

			while ($varow = mysql_fetch_array($vares)) {

				if ($varow["tyyppi"] == '' or $kukarow["varasto"] == $varow["tunnus"]) {

					$sel='';
					if ($varow['tunnus']==$srow["varasto"] or ($varow['tunnus'] == $kukarow['varasto'] and $srow['varasto'] == '')) $sel = 'selected';

					echo "<option value='$varow[tunnus]' $sel>$varow[nimitys]</option>";
				}
			}

			echo "</select></td></tr>";

			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'TV'
						ORDER BY jarjestys, selite";
			$tresult = mysql_query($query) or pupe_error($query);

			echo "<tr><td>".t("Tilausvahvistus").":</td>";
			echo "<td><select name='tilausvahvistus'><option value=' '>".t("Ei vahvistusta")."</option>";

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";
				if ($row[0]== $srow["tilausvahvistus"]) $sel = 'selected';
				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select></td>";

			$eilah = '';
			$eiket = '';
			$sisah = '';
			$osath = '';
			$laskkielto = '';
			if ($srow['eilahetetta'] != '') $eilah = 'CHECKED';
			if ($srow['ketjutus']    != '') $eiket = 'CHECKED';
			if ($srow['sisainen']    != '') $sisah = 'CHECKED';
			if ($srow['osatoimitus'] != '') $osath = 'CHECKED';
			if ($srow['chn'] == '999') $laskkielto = 'CHECKED';

			echo "<td>".t("Sisäinen lasku")."</td><td><input type='checkbox' name='sisainen' $sisah></td>";
			echo "</tr>";

			echo "<td>".t("Keräysprioriteetti")."</td><td>";

			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'ASIAKASLUOKKA'
						ORDER BY jarjestys, selite";
			$tresult = mysql_query($query) or pupe_error($query);

			echo "<select name='luokka'><option value=''>".t("Tyhjä")."</option>";

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";

				if ($row[0] == $srow["luokka"]) $sel = 'selected';


				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select>";


			echo "</td>";
			echo "<td>".t("Suoraan laskutukseen")."</td><td><input type='checkbox' name='eilahe' $eilah></td>";
			echo "</tr>";
			echo "<tr>";


			echo "<td>".t("Toimitusehto")."</td><td>";

			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'TOIMEHTO'
						ORDER BY jarjestys, selite";
			$tresult = mysql_query($query) or pupe_error($query);

			echo "<select name='toimitusehto'><option value=''>".t("Tyhjä")."</option>";

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";
				if ($row["selite"] == $srow["toimitusehto"] or ($srow["toimitusehto"] == '' and $yhtiorow['oletus_toimitusehto'] == $row["selite"])) $sel = 'selected';

				echo "<option value='$row[selite]' $sel>$row[0]</option>";
			}
			echo "</select>";
			echo "</td>";

			echo "<td>".t("Laskua ei ketjuteta")."</td><td><input type='checkbox' name='ketjutus' $eiket></td>";
			echo "</tr>";

			echo "<tr>";

			if ($toim == "TARJOUS") {
				echo "<td>".t("Vaihtokohteen toimitusehto")."</td><td>";

				$query = "	SELECT selite, selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]' and laji = 'TOIMEHTO'
							ORDER BY jarjestys, selite";
				$tresult = mysql_query($query) or pupe_error($query);

				echo "<select name='toimitusehto2'><option value=''>".t("Tyhjä")."</option>";

				while($row = mysql_fetch_array($tresult)) {
					$sel = "";
					if ($row["selite"] == $srow["toimitusehto2"] or ($srow["toimitusehto2"] == '' and $yhtiorow['oletus_toimitusehto2'] == $row["selite"])) $sel = 'selected';

					echo "<option value='$row[selite]' $sel>$row[0]</option>";
				}
				echo "</select>";
				echo "</td>";
			}
			else {
				echo "<td></td><td></td>";
			}

			echo "<td>".t("Tilausta ei osatoimiteta")."</td><td><input type='checkbox' name='osatoimitus' $osath></td>";
			
			if ($yhtiorow['laskutuskielto'] != 0) {
				echo "</tr><tr>";
				echo "<td></td><td></td>";
				echo "<td>".t("Laskutuskieltoon")."</td><td><input type='checkbox' name='laskutuskielto' $laskkielto></td>";
			}

			echo "</tr>";
			echo "</table><br>";

			if ($toim == "TYOMAARAYS") {
				echo "<table>";
				echo "<tr>
						<td>Rek. no:</td><td><input type='text' name='rekno' size='15' value='$srow[rekno]'> <input type='submit' value='Hae'></td>
						<td>Kotipuh:</td><td><input type='text' name='kotipuh' size='35' value='$srow[kotipuh]'></td></tr>";
				echo "<tr>
						<td>Valm. nro:</td><td><input type='text' name='valmnro' size='35' value='$srow[valmnro]'></td>
						<td>Työpuh:</td><td><input type='text' name='tyopuh' size='35' value='$srow[tyopuh]'></td></tr>";
				echo "<tr>
						<td>Mittarilukema:</td><td><input type='text' name='mittarilukema' size='35' value='$srow[mittarilukema]'></td>
						<td>Myyjäliike:</td><td><input type='text' name='myyjaliike' size='35' value='$srow[myyjaliike]'></td></tr>";

				if ($srow["ostopvm"] == "" or $srow["ostopvm"] == "0000-00-00") {
					$luvattupp = date("d");
					$luvattukk = date("m");
					$luvattuvv = date("Y");
				}
				else {
					list($ostopvmvv, $ostopvmkk, $ostopvmpp) = split('-', $srow["ostopvm"]);
				}
				if ($srow["tuotu"] == "" or $srow["tuotu"] == "0000-00-00") {
					$tuotupp = date("d");
					$tuotukk = date("m");
					$tuotuvv = date("Y");
				}
				else {
					list($tuotuvv, $tuotukk, $tuotupp) = split('-', $srow["tuotu"]);
				}

				if ($srow["luvattu"] == "" or $srow["luvattu"] == "0000-00-00") {
					$luvattupp = date("d");
					$luvattukk = date("m");
					$luvattuvv = date("Y");
				}
				else {
					list($luvattuvv, $luvattukk, $luvattupp) = split('-', $srow["luvattu"]);
				}

				echo "<tr><td>Ostopvm:</td>
						<td>
						<input type='text' name='ostopvmpp' size='3' value='$ostopvmpp'>-
						<input type='text' name='ostopvmkk' size='3' value='$ostopvmkk'>-
						<input type='text' name='ostopvmvv' size='6' value='$ostopvmvv'>
						</td>
						<td>Merkki ja malli:</td><td><input type='text' name='merkki' size='11' value='$srow[merkki]'>&nbsp;";
				echo "<select name='merkki2'><option value='eimenu'>Valitse malli";


				$query = "	SELECT distinct malli
							FROM huollot order by malli";
				$result = mysql_query($query) or pupe_error($query);

				while($row = mysql_fetch_array($result)){
					echo "<option value = '$row[0]'>$row[0]";
				}

				echo "</select></td></tr>";
				echo "<tr><td>Tuotu:</td>
						<td>
						<input type='text' name='tuotupp' size='3' value='$tuotupp'>-
						<input type='text' name='tuotukk' size='3' value='$tuotukk'>-
						<input type='text' name='tuotuvv' size='6' value='$tuotuvv'>
						</td>
						<td>Malli/Värikoodi:</td><td><input type='text' name='mallivari' size='35' value='$srow[mallivari]'></td></tr>";
				echo "<tr><td>Luvattu:</td><td>
						<input type='text' name='luvattupp' size='3' value='$luvattupp'>-
						<input type='text' name='luvattukk' size='3' value='$luvattukk'>-
						<input type='text' name='luvattuvv' size='6' value='$luvattuvv'>
						</td>
						<td>Työn suorittaja:</td><td><input type='text' name='suorittaja' size='35' maxlengtd='15' value='$srow[suorittaja]'></td></tr>";
				echo "<tr><td>".t("Työn kuvaus").":</td><td colspan='3'><textarea name='komm1' rows='4' cols='40'>$srow[komm1]</textarea></td></tr>";
				echo "<tr><td>".t("Sisäiset kommentit").":</td><td colspan='3'><textarea name='komm2' rows='4' cols='40'>$srow[komm2]</textarea></td></tr>";
				echo "</table><br>";
			}

			echo "<table>";
			echo "<tr><th colspan='3'>".t("Tilauksen kommentit").":</th></tr>";
			echo "<tr><td>".t("Tilausviite").":</td><td><input type='text' size='40' name='viesti' value='$srow[viesti]'></td><td>(".t("keräyslista + lähete + lasku").")</td></tr>";
			echo "<tr><td>".t("Kommentti")." 1:</td><td><textarea name='comments'   rows='2' cols='40'>$srow[comments]</textarea></td><td>(".t("keräyslista + lähete").")</td></tr>";
			echo "<tr><td>".t("Kommentti")." 2:</td><td><textarea name='sisviesti2' rows='2' cols='40'>$srow[sisviesti2]</textarea></td><td>(".t("keräyslista").")</td></tr>";
			echo "<tr><td>".t("Kommentti")." 3:</td><td><textarea name='sisviesti1' rows='2' cols='40'>$srow[sisviesti1]</textarea></td><td>(".t("lasku").")</td></tr>";
			echo "</table><br>";

			echo "<table>";
			echo "<tr><td class='back'><input type='submit' ACCESSKEY='J' name='jatka' value='".t("Jatka")."'></td></form>";

			echo "<form method='post' action='$PHP_SELF' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='OTSIK'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
			echo "<input type='hidden' name='tila' value='vaihdaasiakas'>";
			echo "<td class='back'><input type='submit' ACCESSKEY='A' value='".t("Vaihda asiakas")."'></td></form>";

			echo "</tr></table>";

		}

		$formi  = "paaformi";
		$kentta = "nimi";
	}

	if (isset($jatka)) {

		//otetaan laskun Ytunnus talteen
		$apuqu = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus = '$kukarow[kesken]'";
		$meapu = mysql_query($apuqu) or pupe_error($apuqu);
		$apuro = mysql_fetch_array($meapu);

		//otetaan käyttöliittymästä tullut alv talteen
		$laskunalv = $alv;

		//tutkitaan onko ytunnus-, alvi- tai vienti-kenttä vaihtunut
		if ((int) $kukarow["kesken"] != 0 and ($apuro['liitostunnus'] != $asiakasid or $apuro['alv'] != $alv or $apuro['vienti'] != $vienti)) {
			// Nyt alennukset ja hinnat ja alvi lasketaan automaattisesti uudestaan
			// Netotettuja rivejä ei päivitetä
			$query = "select * from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
			$result = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($result)) {

				$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
				$tres  = mysql_query($query) or pupe_error($query);
				$trow  = mysql_fetch_array($tres);

				$laskurow['ytunnus']      = $ytunnus;
				$laskurow['vienti']       = $vienti;
				$laskurow['alv']          = $laskunalv;
				$laskurow['liitostunnus'] = $asiakasid;

				$kpl	= $row['tilkpl'];
				$debug	= 0;
				$netto	= $row['netto'];

				// Hinnat lasketaan uudestaan vain jos asiakas vaihdetaan
				// Ei esim jos vain alvi tai vientikenttää muutetaan
				if ($apuro["liitostunnus"] != $asiakasid and $asiakasid > 0) {

					if ($netto != "N") {
						$hinta 	= '';
						$ale	= '';
					}
					else {
						//Alvihinta korjataan vain jos yhtiölla on verolliset myyntihinnat
						if ($row["alv"] != '' and $yhtiorow["alv_kasittely"] == "") {
							$hinta = sprintf('%.2f',round($row['hinta'] / (1+$row['alv']/100) * (1+$trow["alv"]/100),2));
						}
						else {
							$hinta = $row['hinta'];
						}
						$ale = $row['ale'];
					}

					require ("../inc/alehinta.inc"); // haetaan tuotteelle asiakaskohtaisia hintoja..

					$uusihinta	= '';
					$uusiale 	= '';

					if ($hinta != $row['hinta']) {
						$uusihinta = $hinta;
					}
					else {
						$uusihinta = $row["hinta"];
					}

					if ($ale != $row['ale']) {
						$uusiale = $ale;
					}
					else {
						$uusiale = $row['ale'];
					}
				}
				else {
					//Asiakas ei vaihtunut hinnat pysyvät samoina, avia joudumme ehkä korjaamaan
					if ($row["alv"] != '' and $yhtiorow["alv_kasittely"] == "") {
						$uusihinta = sprintf('%.2f',round($row['hinta'] / (1+$row['alv']/100) * (1+$trow["alv"]/100),2));
					}
					else {
						$uusihinta = $row['hinta'];
					}
					$uusiale = $row['ale'];
				}

				//lasketaan alvit
				$hinta 	= $uusihinta;
				$alv 	= "";

				require ("alv.inc");
				$uusihinta = $hinta;

				$query = "update tilausrivi set hinta='$uusihinta', ale='$uusiale', netto='$netto', alv='$alv' where yhtio='$kukarow[yhtio]' and tunnus='$row[tunnus]'";
				$tres  = mysql_query($query) or pupe_error($query);

				$netto		= '';
				$uusihinta	= '';
				$uusiale	= '';
				$hinta		= '';
				$ale		= '';
				$alv		= '';
				$kpl		= '';
			}
		}

		//otetaan käyttöliittymästä tullut alv talteen
		$alv = $laskunalv;

		//Erikoisalennuksen desimaalipilkku
		$erikoisale = str_replace ( ",", ".", $erikoisale);

		if ($kukarow["kesken"] == 0) {
			$query = "INSERT into ";
			$postquery = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";

			$query2 = "INSERT into ";
			$postquery2 = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";

			$query3 = "INSERT into ";
			$postquery3 = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";

			//Kun tehdään uusi tilaus niin nollataan aina tilat ja alatilat
			$alatila = "";
			$ylatila = "";
		}
		else {
			// Pidetään huolta tilausrivien toimituspäivistä ja kerayspaivasta
			$query = "	UPDATE tilausrivi
						SET kerayspvm = '".$kervv."-".$kerkk."-".$kerpp."'
						WHERE otunnus = '$kukarow[kesken]' and kerayspvm='$vkerayspvm' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE tilausrivi
						SET toimaika = '".$toimvv."-".$toimkk."-".$toimpp."'
						WHERE otunnus = '$kukarow[kesken]' and toimaika='$vtoimaika' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "UPDATE ";
			$postquery = " WHERE tunnus = '$kukarow[kesken]'";

			$query2 = "UPDATE ";
			$postquery2 = " WHERE otunnus = '$kukarow[kesken]'";

			$query3 = "UPDATE ";
			$postquery3 = " WHERE otunnus = '$kukarow[kesken]'";
		}

		if(strlen(trim($tnimi)) == 0) {
			$tnimi     = $nimi;
			$tnimitark = $nimitark;
			$tosoite   = $osoite;
			$tpostino  = $postino;
			$tpostitp  = $postitp;
			$toim_maa  = $maa;
		}

		// katotaan, ettei olla valittu käteismyyntiä joka menee rahtikirjalla.. se on no-no.
		$apuqu = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$maksuehto' and kateinen!=''";
		$meapu = mysql_query($apuqu) or pupe_error($apuqu);

		// katotaan, onko valittu toimitustapa nouto
		$apuqu2 = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$toimitustapa' and nouto != ''";
		$meapu2 = mysql_query($apuqu2) or pupe_error($apuqu2);

		if (mysql_num_rows($meapu2)<1) {
			// jos kyseessä oli käteinen laitetaan toimitustavaksi nouto... hakee järjestyksessä ekan
			if (mysql_num_rows($meapu)>0) {
				$apuqu = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and nouto!='' order by jarjestys limit 1";
				$meapu = mysql_query($apuqu) or pupe_error($apuqu);
				$apuro = mysql_fetch_array($meapu);
				$toimitustapa = $apuro['selite'];
			}
		}

		// jos ollaan valittu rahdinmaksajaksi oletus, haetaan oletusmaksaja toimitustavalta
		if ($maksaja=='O') {
			$apuqu = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$toimitustapa'";
			$meapu = mysql_query($apuqu) or pupe_error($apuqu);
			$apuro = mysql_fetch_array($meapu);
			$maksaja = $apuro['merahti'];
		}

		// Marginaalimyyntispecial
		if ($alv == 99.99) {
			// Tässä ceississä myydään vain kotimaanmyyntiä
			$vienti = "";
		}
		


		$crlf = array("\r","\n"); // poistetaan rivinvaihdot kommentista
		$comments = str_replace($crlf, " ", $comments);
		$sisviesti1 = str_replace($crlf, " ", $sisviesti1);
		$sisviesti2 = str_replace($crlf, " ", $sisviesti2);

		// liitostunnusfixaus
		if ((int) $liitostunnus == 0) $liitostunnus = $asiakasid;
		
		//jos oollaan haluttu laskutuskielto laitetaan chn kenttään 999
		if ($laskutuskielto != '') {
			//$srow['chn'] = '999';
			$chn = '999';
		}
		else {
			$apuqu = "select chn from asiakas where yhtio='$kukarow[yhtio]' and tunnus = $liitostunnus";
			$meapu = mysql_query($apuqu) or pupe_error($apuqu);
			$apuro = mysql_fetch_array($meapu);
			$chn = $apuro['chn'];
			if ($chn == '') {
				$chn = '100';
			}
		}
		
		
		$query .= "	lasku SET
					ytunnus			= '$ytunnus',
					nimi 			= '$nimi',
					nimitark 		= '$nimitark',
					osoite 			= '$osoite',
					postino 		= '$postino',
					postitp 		= '$postitp',
					maa 			= '$maa',
					toim_nimi 		= '$tnimi',
					toim_nimitark 	= '$tnimitark',
					toim_osoite 	= '$tosoite',
					toim_postino 	= '$tpostino',
					toim_postitp 	= '$tpostitp',
					toim_maa 		= '$toim_maa',
					verkkotunnus	= '$verkkotunnus',
					toimaika 		= '$toimvv-$toimkk-$toimpp',
					kerayspvm 		= '$kervv-$kerkk-$kerpp',
					erpcm			= '$erpcmvv-$erpcmkk-$erpcmpp',
					maksuehto 		= '$maksuehto',
					toimitustapa 	= '$toimitustapa',
					toimitusehto 	= '$toimitusehto',
					kohdistettu 	= '$maksaja',
					myyja 			= '$myyja',
					alv 			= '$alv',
					comments 		= '$comments',
					ovttunnus 		= '$ovttunnus',
					toim_ovttunnus 	= '$toim_ovttunnus',
					viesti 			= '$viesti',
					chn				= '$chn',
					kuljetus 		= '$kuljetus',
					huolitsija 		= '$huolitsija',
					maksuteksti 	= '$maksuteksti',
					clearing		= '$clearing',
					jakelu 			= '$jakelu',
					tilausvahvistus = '$tilausvahvistus',
					laskutusvkopv 	= '$laskutusvkopv',
					erikoisale 		= '$erikoisale',
					yhtio 			= '$kukarow[yhtio]',
					alatila 		= '$alatila',
					vienti 			= '$vienti',
					varasto 		= '$varasto',
					tilaustyyppi	= '$tilaustyyppi',
					ketjutus 		= '$ketjutus',
					sisainen 		= '$sisainen',
					eilahetetta 	= '$eilahe',
					valkoodi 		= '$valkoodi',
					hyvaksynnanmuutos = '$luokka',
					liitostunnus    = '$liitostunnus',
					viikorkopros 	= '$yhtiorow[viivastyskorko]',
					osatoimitus		= '$osatoimitus',
					sisviesti1 		= '$sisviesti1',
					sisviesti2		= '$sisviesti2',
					poistumistoimipaikka_koodi       = '$poistumistoimipaikka_koodi',
					kuljetusmuoto                    = '$kuljetusmuoto',
					kauppatapahtuman_luonne          = '$kauppatapahtuman_luonne',
					aktiivinen_kuljetus_kansallisuus = '$aktiivinen_kuljetus_kansallisuus',
					aktiivinen_kuljetus              = '$aktiivinen_kuljetus',
					kontti                           = '$kontti',
					sisamaan_kuljetusmuoto           = '$sisamaan_kuljetusmuoto',
					sisamaan_kuljetus_kansallisuus   = '$sisamaan_kuljetus_kansallisuus',
					sisamaan_kuljetus                = '$sisamaan_kuljetus',
					maa_maara                        = '$maa_maara'";

		if ($toim == "MYYNTITILI") {
			if ($ylatila=='') {
				$ylatila='G';
			}
			$tee = "";
		}
		elseif ($toim == "VALMISTAASIAKKAALLE") {
			if ($ylatila=='') {
				$ylatila='V';
			}
			$tee = "";
		}
		elseif ($toim == "TYOMAARAYS") {
			if ($ylatila=='') {
				$ylatila='A';
			}
			$tee = "";
		}
		elseif ($toim == "TARJOUS") {
			if ($ylatila=='') {
				$ylatila='T';
			}
			$tee = "";
		}
		else {
			if ($ylatila=='') {
				$ylatila='N';
			}
			$tee = "";
		}


		$query .= ", tila = '$ylatila'";


		$query .= $postquery;
		$result = mysql_query($query) or pupe_error($query);

		if ($kukarow["kesken"] == 0 or $kukarow["kesken"] == "") {
			$id = mysql_insert_id();

			$query = "	UPDATE kuka
						SET kesken='$id'
						WHERE session = '$session'";
			$result = mysql_query($query) or pupe_error($query);

			$kukarow["kesken"] = $id;
			$tilausnumero = $id;
		}

		if ($toim == "TYOMAARAYS") {
			//otetaan kantaa pyörän malliin
			if($merkki2 != 'eimenu'){
				$merkki = $merkki2;
			}

			$query2 .= "tyomaarays SET
						yhtio			= '$kukarow[yhtio]',
						kotipuh 		= '$kotipuh',
						tyopuh 			= '$tyopuh',
						myyjaliike 		= '$myyjaliike',
						ostopvm 		= '$ostopvmvv-$ostopvmkk-$ostopvmpp',
						rekno 			= '$rekno',
						mittarilukema 	= '$mittarilukema',
						merkki 			= '$merkki',
						mallivari 		= '$mallivari',
						valmnro 		= '$valmnro',
						tuotu 			= '$tuotuvv-$tuotukk-$tuotupp',
						luvattu 		= '$luvattuvv-$luvattukk-$luvattupp',
						komm1 			= '$komm1',
						komm2 			= '$komm2',
						suorittaja 		= '$suorittaja',
						viite	 		= '$viite',
						otunnus 		= '$kukarow[kesken]'";
			$query2 .= $postquery2;
			$result = mysql_query($query2) or pupe_error($query2);
		}

		if ($toim == "TARJOUS") {
			$query3 .= "laskun_lisatiedot SET
						yhtio			= '$kukarow[yhtio]',
						kolm_ovttunnus	= '$kolm_ovttunnus',
						kolm_nimi		= '$kolm_nimi',
						kolm_nimitark	= '$kolm_nimitark',
						kolm_osoite		= '$kolm_osoite',
						kolm_postino	= '$kolm_postino',
						kolm_postitp	= '$kolm_postitp',
						kolm_maa		= '$kolm_maa',
						toimitusehto2	= '$toimitusehto2',
						otunnus 		= '$kukarow[kesken]'";
			$query3 .= $postquery3;
			$result = mysql_query($query3) or pupe_error($query3);
		}
	}

	unset($alv);

?>
