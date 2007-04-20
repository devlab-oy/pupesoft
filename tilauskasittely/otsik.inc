<?php

	//tässä joko tehdään uutta otsikkoa tai muutetaan olemassa olevaa
	if ($tee == '' or $kopioitava_otsikko != 0) {
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
		echo "<br><br><br>".t("VIRHE: Sinulla on useita tilauksia auki")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
		exit;
	}

	// Halutaan vaihtaa asiakas tilauksella
	if ($tila == "vaihdaasiakas") {
		$query = "	UPDATE lasku
					SET liitostunnus 	= 0,
					ytunnus				= '',
					ovttunnus			= '',
					nimi				= '',
					nimitark			= '',
					osoite 				= '',
					postino 			= '',
					postitp				= '',
					maa   				= '',
					toim_ovttunnus 		= '',
					toim_nimi      		= '',
					toim_nimitark  		= '',
					toim_osoite    		= '',
					toim_postino  		= '',
					toim_postitp 		= '',
					toim_maa    		= ''
					WHERE yhtio 		= '$kukarow[yhtio]'
					and tunnus	 		= '$kukarow[kesken]'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	UPDATE laskun_lisatiedot
					SET kolm_ovttunnus	= '',
					kolm_nimi   		= '',
					kolm_nimitark		= '',
					kolm_osoite  		= '',
					kolm_postino  		= '',
					kolm_postitp 		= '',
					kolm_maa    		= ''
					WHERE yhtio 		= '$kukarow[yhtio]'
					and otunnus 		= '$kukarow[kesken]'";
		$result = mysql_query($query) or pupe_error($query);

		$tila 		= '';
		$nimi 		= '';
		$asiakasid	= '';
		$from 		= 'vaihdaasiakas';
	}

	//Jos ylläpidossa on luotu uusi asiakas
	if ($yllapidossa == "asiakas" and $yllapidontunnus != '') {
		$asiakasid 	= $yllapidontunnus;
	}

	// Etsitään asiakasta
	if ($toim == "TYOMAARAYS" and ($nimi != '' or $rekno!='' or $valmnro!='') and !isset($jatka)) {
		//tehdään asiakashaku yhteensopivuus
		$ytunnus 	= $nimi;
		$asiakasid	= "";
		$lause 		= "<font class='head'>".t("Valitse asiakas").":</font><hr><br>";

		require ("../inc/asiakashaku.inc");

		if ($asiakasid == '' and $monta > 1) {
			//Löytyi monta sopivaa, näytetään formi, mutta ei otsikkoa
			$tila = 'EI_NAYTETA';
		}

		/*
		$ytunnus	= $nimi;
		$lause 		= "<font class='head'>".t("Valitse asiakas").":</font><hr><br>";

		require ("../inc/tyomaarayshaku.inc");
		*/
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
	$erpcm_kasin = ""; // apumuuttuja
	$meapurow    = array();

	if ($maksuehto != "") {
		// haetaan maksuehdon tiedot tarkastuksia varten
		$apuqu = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$maksuehto'";
		$meapu = mysql_query($apuqu) or pupe_error($apuqu);
		$meapurow = mysql_fetch_array($meapu);
	}

	if ($erpcmvv != "" or $erpcmkk != "" or $erpcmpp != "" or $meapurow["erapvmkasin"] != '') {

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
			$erpcm_kasin = "KYLLA";
		}
	}

	// tehdään päivämäärächeckit
	if ($toim == "YLLAPITO") {

		if ($yllapito_alkupp != "" or $yllapito_alkukk != "" or $yllapito_alkuvv != "") {

			$yllapito_alkupp = (int) $yllapito_alkupp;
			$yllapito_alkukk = (int) $yllapito_alkukk;
			$yllapito_alkuvv = (int) $yllapito_alkuvv;

			if (!checkdate($yllapito_alkukk, $yllapito_alkupp, $yllapito_alkuvv)) {
				echo "<font class='error'>".t("VIRHE: Syötetty alkupvm on virheellinen")."!</font><br>";
				$tee = "OTSIK";
				unset($jatka);
				$erpcm_virhe = "X";
			}
		}

		if ($yllapito_loppupp != "" or $yllapito_loppukk != "" or $yllapito_loppuvv != "") {

			$yllapito_loppupp = (int) $yllapito_loppupp;
			$yllapito_loppukk = (int) $yllapito_loppukk;
			$yllapito_loppuvv = (int) $yllapito_loppuvv;

			if (!checkdate($yllapito_loppukk, $yllapito_loppupp, $yllapito_loppuvv)) {
				echo "<font class='error'>".t("VIRHE: Syötetty loppupvm on virheellinen")."!</font><br>";
				$tee = "OTSIK";
				unset($jatka);
				$erpcm_virhe = "X";
			}
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
		elseif ($toim == "PROJEKTI") {
			echo "<font class='head'>".t("Projektin otsikko")."</font><hr>";
		}
		elseif ($toim == "YLLAPITO") {
			echo "<font class='head'>".t("Ylläpitosopimuksen otsikko")."</font><hr>";
		}
		else {
			echo "<font class='head'>".t("Myyntitilauksen otsikko")."</font><hr>";
		}

		echo "<form method='post' action='$PHP_SELF' autocomplete='off' name='paaformi'>";
		echo "<input type='hidden' name='tee' value='OTSIK'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='from' value='$from'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";

		if ($asiakasid != "" or $kukarow["kesken"] != 0 or $kopioitava_otsikko != 0) {

			//Asiakas vaihtuu
			if ($from == "vaihdaasiakas" and $asiakasid != "") {
				// asiakashaussa pitää olla vakio columni kohdistettu, jonka arvo on O että saadaan OLETUS toimitustapa haettua asiakashaunkin yhteydessä
				$squery = "	SELECT *, 'O' kohdistettu
							FROM asiakas
							WHERE tunnus = '$asiakasid'";
				$assresult = mysql_query($squery) or pupe_error($squery);
				$assrow = mysql_fetch_array($assresult);

				$query = "	UPDATE lasku
							SET liitostunnus 	= '$assrow[tunnus]',
							ytunnus				= '$assrow[ytunnus]',
							ovttunnus			= '$assrow[ovttunnus]',
							nimi				= '$assrow[nimi]',
							nimitark			= '$assrow[nimitark]',
							osoite 				= '$assrow[osoite]',
							postino 			= '$assrow[postino]',
							postitp				= '$assrow[postitp]',
							maa   				= '$assrow[maa]',
							toim_ovttunnus 		= '$assrow[toim_ovttunnus]',
							toim_nimi      		= '$assrow[toim_nimi]',
							toim_nimitark  		= '$assrow[toim_nimitark]',
							toim_osoite    		= '$assrow[toim_osoite]',
							toim_postino  		= '$assrow[toim_postino]',
							toim_postitp 		= '$assrow[toim_postitp]',
							toim_maa    		= '$assrow[toim_maa]'
							WHERE yhtio 		= '$kukarow[yhtio]'
							and tunnus	 		= '$kukarow[kesken]'
							and liitostunnus	= 0";
				$result = mysql_query($query) or pupe_error($query);

				$query = "	UPDATE laskun_lisatiedot
							SET kolm_ovttunnus	= '$assrow[kolm_ovttunnus]',
							kolm_nimi   		= '$assrow[kolm_nimi]',
							kolm_nimitark		= '$assrow[kolm_nimitark]',
							kolm_osoite  		= '$assrow[kolm_osoite]',
							kolm_postino  		= '$assrow[kolm_postino]',
							kolm_postitp 		= '$assrow[kolm_postitp]',
							kolm_maa    		= '$assrow[kolm_maa]'
							WHERE yhtio 		= '$kukarow[yhtio]'
							and otunnus 		= '$kukarow[kesken]'";
				$result = mysql_query($query) or pupe_error($query);

				$tiedot_laskulta = "YES";
			}

			if (substr($asiakasid,0,1) == "£") {

				$asiakasid = substr($asiakasid,1);

				$squery = "	SELECT tyomaarays.*, laskun_lisatiedot.*, lasku.*,  lasku.hyvaksynnanmuutos as luokka
							FROM lasku
							LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
							LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
							WHERE lasku.tunnus = '$asiakasid'";
			}
			elseif ($asiakasid != "" and $tiedot_laskulta == "") {
				// asiakashaussa pitää olla vakio columni kohdistettu, jonka arvo on O että saadaan OLETUS toimitustapa haettua asiakashaunkin yhteydessä
				$squery = "	SELECT *, tunnus liitostunnus, 'O' kohdistettu
							FROM asiakas
							WHERE tunnus = '$asiakasid'";
			}
			elseif ($kopioitava_otsikko != 0) {
				$squery = "	SELECT tyomaarays.*, laskun_lisatiedot.*, lasku.*, lasku.hyvaksynnanmuutos as luokka
							FROM lasku
							LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
							LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
							WHERE lasku.tunnus = '$kopioitava_otsikko'";
			}
			else {
				$squery = "	SELECT tyomaarays.*, laskun_lisatiedot.*, lasku.*, lasku.hyvaksynnanmuutos as luokka
							FROM lasku
							LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
							LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
							WHERE lasku.tunnus = '$kukarow[kesken]'";
			}
			$sresult = mysql_query($squery) or pupe_error($squery);
			$srow = mysql_fetch_array($sresult);
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
				<input type='hidden' name='maa' 							 value = '$srow[maa]'>
				<input type='hidden' name='toim_maa' 						 value = '$srow[toim_maa]'>
				<input type='hidden' name='kolm_maa' 						 value = '$srow[kolm_maa]'>
				<input type='hidden' name='tunnusnippu' 					 value = '$srow[tunnusnippu]'>
				<input type='hidden' name='jaksotettu'	 					 value = '$srow[jaksotettu]'>
				<input type='hidden' name='liitostunnus' 					 value = '$srow[liitostunnus]'>";


		//jos käyttäjällä on oletusaiskas
		if ($kukarow["oletus_asiakas"] != 0 and $srow["nimi"] == '') {
			$query  = "	SELECT *
						FROM asiakas
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$extra_asiakas = mysql_fetch_array($result);
				$srow["nimi"]  = $extra_asiakas["ytunnus"];
			}
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


			if ($toim == "TARJOUS" or $srow["tilaustyyppi"] == "T") {
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

			if ($toim == "TYOMAARAYS") {
				echo "<tr><th colspan='4'>".t("Työmääräyksen tiedot").":</th></tr>";
			}
			elseif ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
				echo "<tr><th colspan='4'>".t("Valmistuksen tiedot").":</th></tr>";

			}
			elseif ($toim == "SIIRTOLISTA") {
				echo "<tr><th colspan='4'>".t("Siirtolistan tiedot").":</th></tr>";

			}
			elseif ($toim == "MYYNTITILI") {
				echo "<tr><th colspan='4'>".t("Myyntitilin tiedot").":</th></tr>";

			}
			elseif ($toim == "TARJOUS") {
				echo "<tr><th colspan='4'>".t("Tarjouksen tiedot").":</th></tr>";

			}
			elseif ($toim == "PROJEKTI") {
				echo "<tr><th colspan='4'>".t("Projektin tiedot").":</th></tr>";

			}
			else {
				echo "<tr><th colspan='4'>".t("Tilauksen tiedot").":</th></tr>";
			}


			//Tutkitaan onko laskulla maksupositioita
			$query = "	SELECT *
						FROM maksupositio
						WHERE maksupositio.yhtio ='$kukarow[yhtio]'
						and maksupositio.otunnus ='$srow[jaksotettu]'";
			$result = mysql_query($query) or pupe_error($query);

			echo "<tr><td>".t("Maksuehto").":</td>";

			if(mysql_num_rows($result) > 0) {
				echo "<td>".t("Maksusopimus")."</td>";
				echo "<input type='hidden' name='maksuehto' value = '$srow[maksuehto]'>";
			}
			else {

				echo "<td><select name='maksuehto'>";

				$query = "	SELECT maksuehto.tunnus, concat_ws(' ', ".avain('selectcon','MEHTOKATXT_').",  ".avain('selectcon2','MEHTOTXT_').") mehto
							FROM maksuehto
							".avain('join','MEHTOKATXT_')."
							".avain('join2','MEHTOTXT_')."
							WHERE maksuehto.yhtio = '$kukarow[yhtio]'
							and (maksuehto.sallitut_maat = '' or maksuehto.sallitut_maat like '%$srow[maa]%')
							order by maksuehto.jarjestys, mehto, maksuehto.tunnus";
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
			else {
				$erpcm_kasin = "KYLLA";
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

			// haetaan oletus rahdinmaksaja
			$apu_rahdinmaksaja = $srow["kohdistettu"];

			if ($apu_rahdinmaksaja == "O") {
				$apuqu = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$srow[toimitustapa]'";
				$meapu = mysql_query($apuqu) or pupe_error($apuqu);
				$apuro = mysql_fetch_array($meapu);
				$apu_rahdinmaksaja = $apuro['merahti'];
			}

			if ($apu_rahdinmaksaja == "") $apu_rahdinmaksaja = "V";
			$sel[$apu_rahdinmaksaja] = "selected";

			echo "<td>".t("Rahdinmaksaja").":</td>
				<td><select name='maksaja'>
					<option value='O'>".t("Oletus")."</option>
					<option value='K' $sel[K]>".t("Lähettäjä")."</option>
					<option value=''  $sel[V]>".t("Vastaanottaja")."</option>
					</select>
				</td>
				</tr>";

			echo "<tr><td>".t("Toimitustapa").":</td>";
			echo "<td><select name='toimitustapa'>";

			$query = "	SELECT tunnus, selite
						FROM toimitustapa
						WHERE yhtio = '$kukarow[yhtio]'
						and (sallitut_maat = '' or sallitut_maat like '%$srow[toim_maa]%')
						ORDER BY jarjestys,selite";
			$tresult = mysql_query($query) or pupe_error($query);

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";
				if ($row[1] == $srow["toimitustapa"]) {
					$sel = 'selected';
				}
				echo "<option value='$row[1]' $sel>".asana('TOIMITUSTAPA_',$row[1])."";
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
			if ($kukarow["myyja"] != 0 and  (int) $srow["myyja"] == 0) {
				$myyjanro = $kukarow["myyja"];
			}
			else {
				$myyjanro = "";
			}

			echo "<td>".t("Myyjänro").": <input type='text' size='8' name='myyjanro' value='$myyjanro'> ".t("tai")."</td>";
			echo "<td><select name='myyja'>";

			$query = "	SELECT tunnus, kuka, nimi, myyja, asema
						FROM kuka
						WHERE yhtio = '$kukarow[yhtio]' and (extranet = '' or tunnus='$srow[myyja]')
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

			//	Jos halutaan niin voidaan liittää hieman yhteyshenkilöitä
			if($yhtiorow["tilauksen_yhteyshenkilot"] == "K") {
				echo "<tr><td>".t("Yhteyshenkilö").":</td>
				<td><select id='yhteyshenkilo_tekninen' name='yhteyshenkilo_tekninen' onchange=\"yllapito('yhteyshenkilo&liitostunnus=$srow[liitostunnus]&yhtyyppi=asiakas',this.id)\">";

				$query = "	SELECT tunnus, if(titteli != '', concat_ws(', ' ,nimi, titteli), nimi) nimi
							FROM yhteyshenkilo
							WHERE liitostunnus = '$srow[liitostunnus]'
							ORDER BY nimi";
				$yhresult = mysql_query($query) or pupe_error($query);

				//duusaan näihin javascript handlerin myöhemmin
				echo "	<option value=''>Ei teknistä yhteyshenkilöä</option>";

				while($row = mysql_fetch_array($yhresult)) {
					$sel = "";
					if ($row[0] == $srow["yhteyshenkilo_tekninen"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[0]' $sel>$row[1]</option>";
				}

				echo "	<optgroup label='Toiminto'>
							<option value='uusi'>Luo uusi yhteyshenkilö</option>
						</optgroup>";

				echo "</select>";


				echo "</td>";

				echo "<td>".t("Projektipaallikko").":</td>
					<td><select name='projektipaallikko'>";

				echo "<option value=''>".t("Ei projektipäällikköä")."</option>";

				$query = "	SELECT tunnus, kuka, nimi, myyja, asema
							FROM kuka
							WHERE yhtio = '$kukarow[yhtio]'
							and asema	= 'PP'
							ORDER BY nimi";
				$yresult = mysql_query($query) or pupe_error($query);

				while($row = mysql_fetch_array($yresult)) {
					$sel = "";
					if ($row["kuka"] == $srow["projektipaallikko"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
				}
				echo "</select>";

				echo "</td></tr>";

				echo "<tr><td>".t("Asiakkaan kaupallinen käsittelijä").":</td>
				<td><select id='yhteyshenkilo_kaupallinen' name='yhteyshenkilo_kaupallinen' onchange=\"yllapito('yhteyshenkilo&liitostunnus=$srow[liitostunnus]&yhtyyppi=asiakas',this.id)\">";

				echo "	<option value=''>Ei kaupallista käsittelijää</option>";
				mysql_data_seek($yhresult,0);
				while($row = mysql_fetch_array($yhresult)) {
					$sel = "";
					if ($row[0] == $srow["yhteyshenkilo_kaupallinen"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[0]' $sel>$row[1]</option>";
				}

				//duusaan näihin javascript handlerin myöhemmin
				echo "	<optgroup label='Toiminto'>
							<option value='uusi'>Luo uusi yhteyshenkilö</option>
						</optgroup>";

				echo "</select>";
				echo "</td>";

			}

			if($yhtiorow["tilauksen_kohteet"] == "K") {
				$query = "	SELECT tunnus, kohde
							FROM asiakkaan_kohde
							WHERE yhtio = '$kukarow[yhtio]' and liitostunnus = '$srow[liitostunnus]'
							ORDER BY tunnus desc";
				$kohderesult = mysql_query($query) or pupe_error($query);

				echo "<td>".t("Kohde").":</td>
					<td><select id='asiakkaan_kohde' name='asiakkaan_kohde' onchange=\"yllapito('asiakkaan_kohde&liitostunnus=$srow[liitostunnus]',this.id)\">";

				echo "<option value=''>Asiakkaalla ei ole kohdetta</option>";

				while($row = mysql_fetch_array($kohderesult)) {
					$sel = "";
					if ($row[0] == $srow["asiakkaan_kohde"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[0]' $sel>$row[0] - $row[1]</option>";
				}

				//duusaan näihin javascript handlerin myöhemmin
				echo "	<optgroup label='Toiminto'>
							<option value='uusi'>Luo uusi asiakkaan kohde</option>
						</optgroup>";

				echo "</select>";

				echo "</td></tr>";

			}
			elseif($yhtiorow["tilauksen_yhteyshenkilot"] == "K") {
				//	Fiksataan HTML
				echo "<td></td><td></td></tr>";
			}
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
			elseif ($toim == "PROJEKTI") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on tarjous
				echo "<input type='hidden' name='tilaustyyppi' value='P'>";

				echo "<td>".t("Projekti")."</td>";
			}
			elseif ($toim == "YLLAPITO") {
				echo "<tr><td>".t("Tilaustyyppi").":</td>";

				//Jos kyseessä on ylläpitosopimus
				echo "<input type='hidden' name='tilaustyyppi' value='0'>";

				echo "<td>".t("Ylläpitosopimus")."</td>";
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

				//Tarvitaan ainakin tarjoustilauksilla
				if ($srow["clearing"] != '') {
					echo "<input type='hidden' name='clearing' value='$srow[clearing]'>";
				}

				echo "<tr><td>".t("Tilaustyyppi").":</td>";
				echo "<td><select name='tilaustyyppi'>";

				$sel = array();
				$sel[$srow["tilaustyyppi"]] = "selected";

				echo "<option value='N' $sel[N]>".t("Normaalitilaus")."</option>";
				echo "<option value='E' $sel[E]>".t("Ennakkotilaus")."</option>";
				echo "<option value='T' $sel[T]>".t("Tarjoustilaus")."</option>";

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

			$query = "	SELECT avainsana.selite, ".avain('select')."
						FROM avainsana
						".avain('join','TV_')."
						WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji = 'TV'
						ORDER BY avainsana.jarjestys, avainsana.selite";
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

			// Tarjouksia ei oletuksena osatoimiteta
			if ($srow['osatoimitus'] != '' or ($toim == "TARJOUS" and $kukarow["kesken"] == 0)) $osath = 'CHECKED';

			if ($srow['chn'] == '999') $laskkielto = 'CHECKED';

			echo "<td>".t("Sisäinen lasku").":</td><td><input type='checkbox' name='sisainen' $sisah></td>";
			echo "</tr>";

			echo "<td>".t("Keräysprioriteetti").":</td><td>";

			$query = "	SELECT avainsana.selite, ".avain('select')."
						FROM avainsana
						".avain('join','ASLUOKKA_')."
						WHERE avainsana.yhtio = '$kukarow[yhtio]' and avainsana.laji = 'ASIAKASLUOKKA'
						ORDER BY avainsana.jarjestys, avainsana.selite";
			$tresult = mysql_query($query) or pupe_error($query);

			echo "<select name='luokka'><option value=''>".t("Tyhjä")."</option>";

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";

				if ($row[0] == $srow["luokka"]) $sel = 'selected';


				echo "<option value='$row[0]' $sel>$row[1]</option>";
			}
			echo "</select>";

			echo "</td>";
			echo "<td>".t("Suoraan laskutukseen").":</td><td><input type='checkbox' name='eilahe' $eilah></td>";
			echo "</tr>";
			echo "<tr>";


			echo "<td>".t("Toimitusehto").":</td><td>";

			$query = "	SELECT selite, if(selitetark_2 != '', concat(concat(' (',selitetark_2,') -'), selitetark), if(selitetark!='',concat('- ',selitetark), ''))
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'TOIMEHTO'
						ORDER BY jarjestys, selite";
			$tresult = mysql_query($query) or pupe_error($query);

			echo "<select id='toimitusehto' name='toimitusehto' Style='width: 55px; font-size: 8pt; padding: 0' onchange=toimehtoTarkenne('toimitusehto')>";

			while($row = mysql_fetch_array($tresult)) {
				$sel = "";

				if(stripos(substr($srow["toimitusehto"],0,4), $row["selite"]." ") !== false) {
					$sel = 'selected';
					$kasitoimehto = substr($srow["toimitusehto"],4);
				}
				elseif ($row["selite"] == $srow["toimitusehto"] or ($srow["toimitusehto"] == '' and $yhtiorow['oletus_toimitusehto'] == $row["selite"])) {
					$sel = 'selected';
				}

				echo "<option value='$row[selite]' $sel>$row[0] $row[1]</option>";
			}
			echo "<option value=''></option></select>";

			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'TOIMEHTO' and selite = '$srow[toimitusehto]'
						ORDER BY jarjestys, selite";
			$tresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($tresult) == 0 and $kasitoimehto == "") {
				$kasitoimehto = $srow["toimitusehto"];
			}
			if (mysql_num_rows($tresult) == 1 and $kasitoimehto == "") {
				$row = mysql_fetch_array($tresult);
				$kasitoimehto = $row["selitetark"];
			}

			echo "<input type='text' id='toimitusehtoLisa' name='kasitoimehto' value='$kasitoimehto' size='20'>";

			echo "</td>";

			echo "<td>".t("Laskua ei ketjuteta").":</td><td><input type='checkbox' name='ketjutus' $eiket></td>";
			echo "</tr>";

			echo "<tr>";

			if ($toim == "TARJOUS" or $srow["tilaustyyppi"] == "T") {
				echo "<td>".t("Vaihtokohteen toimitusehto")."</td><td>";

				$query = "	SELECT selite, if(selitetark_2 != '', concat(concat(' (',selitetark_2,') -'), selitetark), if(selitetark!='',concat('- ',selitetark), ''))
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]' and laji = 'TOIMEHTO'
							ORDER BY jarjestys, selite";
				$tresult = mysql_query($query) or pupe_error($query);

				echo "<select id='toimitusehto2' name='toimitusehto2' Style='width: 55px; font-size: 8pt; padding: 0' onchange=toimehtoTarkenne('toimitusehto2')>";

				while($row = mysql_fetch_array($tresult)) {
					$sel = "";

					if(stripos(substr($srow["toimitusehto2"],0,4), $row["selite"]." ") !== false) {
						$sel = 'selected';
						$kasitoimehto2 = substr($srow["toimitusehto2"],4);

					}
					elseif ($row["selite"] == $srow["toimitusehto2"] or ($srow["toimitusehto2"] == '' and $yhtiorow['oletus_toimitusehto2'] == $row["selite"])) {
						$sel = 'selected';
					}

					echo "<option value='$row[selite]' $sel>$row[0] $row[1]</option>";
				}
				echo "<option value=''></option></select>";

				$query = "	SELECT selite, selitetark
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]' and laji = 'TOIMEHTO' and selite = '$srow[toimitusehto2]'
							ORDER BY jarjestys, selite";
				$tresult = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($tresult) == 0 and $kasitoimehto2 == "") {
					$kasitoimehto2 = $srow["toimitusehto2"];
				}
				if (mysql_num_rows($tresult) == 1 and $kasitoimehto2 == "") {
					$row = mysql_fetch_array($tresult);
					$kasitoimehto2 = $row["selitetark"];
				}

				echo "<input type='text' id='toimitusehto2Lisa' name='kasitoimehto2' value='$kasitoimehto2' size='20'>";

				echo "</td>";
			}
			else {
				echo "<td></td><td></td>";
			}

			echo "<td>".t("Tilausta ei osatoimiteta").":</td><td><input type='checkbox' name='osatoimitus' $osath></td></tr>";

			if($yhtiorow["tilauksen_seuranta"] == "K") {
				//seuranta
				$query = "	SELECT selite, concat_ws(' - ',selite, selitetark)
							FROM avainsana
							WHERE yhtio='$kukarow[yhtio]' and laji='SEURANTA'
							ORDER BY jarjestys, selitetark";
				$mryresult = mysql_query($query) or pupe_error($query);

				echo "<tr><td>".t("Seuranta")."</td><td><select name='seuranta'>
					<option value=''>".t("Valitse")."</option>";
				while($row = mysql_fetch_array($mryresult)) {
					$sel = "";
					if ($row[0] == $srow["seuranta"]) {
						$sel = 'selected';
					}
					echo "<option value='$row[0]' $sel>$row[1]</option>";
				}
				echo "</select></td>";

				$rchk = "";
				if($srow["rivihintoja_ei_nayteta"] != '') $rchk = 'CHECKED';
				echo "<td>".t("Rivihintoja ei näytetä")."</td><td><input type='checkbox' value='X' name='rivihintoja_ei_nayteta' $rchk></td>";

			}

			echo "<tr>";

			//laskun valuuttakoodi
			if ($srow["valkoodi"] == '') {
				$srow["valkoodi"] = $yhtiorow["valkoodi"];
			}

			$query = "	SELECT nimi, kurssi, tunnus
						FROM valuu
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY jarjestys";
			$vresult = mysql_query($query) or pupe_error($query);

			echo "<td>".t("Valuutta").":</td><td><select name='valkoodi'>";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($srow["valkoodi"] == $vrow['nimi']) {
					$sel = "selected";
				}

				echo "<option value = '$vrow[nimi]##$vrow[kurssi]' $sel>$vrow[nimi]</option>";
			}

			echo "</select></td>";

			if ($yhtiorow['laskutuskielto'] != 0) {
				echo "<td>".t("Laskutuskieltoon").":</td><td><input type='checkbox' name='laskutuskielto' $laskkielto></td>";
			}
			else{
				echo "<td></td><td></td>";
			}

			echo "</tr>";

			if ($yhtiorow["splittauskielto"] != "") {
				$laskkielto = "";
				if (($yhtiorow["splittauskielto"] != '' and !isset($srow["splittauskielto"])) or $srow["splittauskielto"] != '') $laskkielto = 'CHECKED';

				echo "<tr>";
				echo "<td></td><td></td>";
				echo "<td>".t("Splittauskielto").":</td><td><input type='checkbox' value='E' name='splittauskielto' $laskkielto></td>";
				echo "</tr>";
			}

			echo "</table><br>";

			if ($toim == "TYOMAARAYS") {
				echo "<table>";
				echo "<tr>
						<td>Rek. no:</td><td><input type='text' name='rekno' size='15' value='$srow[rekno]'> <input type='submit' value='Hae'></td>
						<td>Kotipuh:</td><td><input type='text' name='kotipuh' size='20' value='$srow[kotipuh]'></td></tr>";
				echo "<tr>
						<td>Valm. nro:</td><td><input type='text' name='valmnro' size='20' value='$srow[valmnro]'></td>
						<td>Työpuh:</td><td><input type='text' name='tyopuh' size='20' value='$srow[tyopuh]'></td></tr>";
				echo "<tr>
						<td>Mittarilukema:</td><td><input type='text' name='mittarilukema' size='20' value='$srow[mittarilukema]'></td>
						<td>Myyjäliike:</td><td><input type='text' name='myyjaliike' size='20' value='$srow[myyjaliike]'></td></tr>";

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

				echo "<tr><td>".t("Ostopvm").":</td>
						<td>
						<input type='text' name='ostopvmpp' size='3' value='$ostopvmpp'>-
						<input type='text' name='ostopvmkk' size='3' value='$ostopvmkk'>-
						<input type='text' name='ostopvmvv' size='6' value='$ostopvmvv'>
						</td>
						<td>".t("Merkki ja malli").":</td><td>";
				echo "<select name='merkki2'><option value='eimenu'>Valitse merkki";


				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio='$kukarow[yhtio]' and laji = 'sarjanumeron_li' and selite = 'MERKKI'
							ORDER BY jarjestys, selitetark_2";
				$vresult = mysql_query($query) or pupe_error($query);

				echo "<option value = ''>".t("Valitse merkki")."</option>";

				$zxk = "";

				while ($vrow=mysql_fetch_array($vresult)) {
					$sel="";
					if ($srow["merkki"] == $vrow['selitetark']) {
						$sel = "selected";
						$zxk = "OK";
					}
					echo "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark_2]</option>";
				}

				if ($zxk == "OK") {
					$srow["merkki"] = "";
				}

				echo "</select> ja <input type='text' name='merkki' size='11' value='$srow[merkki]'></td></tr>";
				echo "<tr><td>Tuotu:</td>
						<td>
						<input type='text' name='tuotupp' size='3' value='$tuotupp'>-
						<input type='text' name='tuotukk' size='3' value='$tuotukk'>-
						<input type='text' name='tuotuvv' size='6' value='$tuotuvv'>
						</td>
						<td>Malli/Värikoodi:</td><td><input type='text' name='mallivari' size='20' value='$srow[mallivari]'></td></tr>";
				echo "<tr><td>Luvattu:</td><td>
						<input type='text' name='luvattupp' size='3' value='$luvattupp'>-
						<input type='text' name='luvattukk' size='3' value='$luvattukk'>-
						<input type='text' name='luvattuvv' size='6' value='$luvattuvv'>
						</td>
						<td>Työn suorittaja:</td><td><input type='text' name='suorittaja' size='20' maxlengtd='15' value='$srow[suorittaja]'></td></tr>";
				echo "<tr><td>".t("Työn kuvaus").":</td><td colspan='3'><textarea name='komm1' rows='6' cols='50'>$srow[komm1]</textarea></td></tr>";
				echo "<tr><td>".t("Sisäiset kommentit").":</td><td colspan='3'><textarea name='komm2' rows='6' cols='50'>$srow[komm2]</textarea></td></tr>";
				echo "</table><br>";
			}

			// tehdään ylläpitosopimista varten laskutusaikataulu taulu
			if ($toim == "YLLAPITO") {

				$query = "select * from laskun_lisatiedot where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
				$sopapures = mysql_query($query) or pupe_error($query);
				$sopapurow = mysql_fetch_array($sopapures);

				$disable = "";

				// jos alatila on X niin pitää tehdä hidden muuttujat ettei nollaannu
				if ($srow["alatila"] == "X") {
					$disable = "disabled";
					
					foreach (explode(',', $sopapurow["sopimus_kk"]) as $numi) {
						echo "<input type='hidden' name='yllapito_kk[]' value='$numi'>";
					}
					foreach (explode(',', $sopapurow["sopimus_pp"]) as $numi) {
						echo "<input type='hidden' name='yllapito_pp[]' value='$numi'>";
					}
				
				}

				$chk = array();
				foreach (explode(',', $sopapurow["sopimus_kk"]) as $numi) {
					$chk[$numi] = "checked";
				}

				echo "<table>";
				echo "<tr><th colspan='2'>".t("Valitse ylläpitosopimuksen laskutusaikataulu").":</th></tr>";
				echo "<tr>";
				echo "<td>";
				echo "<table><tr>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[1] value='1'> Tammi </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[2] value='2'> Helmi </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[3] value='3'> Maalis </td>";
				echo "</tr><tr>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[4] value='4'> Huhti </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[5] value='5'> Touko </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[6] value='6'> Kesä </td>";
				echo "</tr><tr>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[7] value='7'> Heinä </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[8] value='8'> Elo </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[9] value='9'> Syys </td>";
				echo "</tr><tr>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[10] value='10'> Loka </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[11] value='11'> Marras </td>";
				echo "<td><input name='yllapito_kk[]' $disable type='checkbox' $chk[12] value='12'> Joulu </td>";
				echo "</tr></table>";
				echo "</td>";
				echo "<td>";

				$chk = array();
				foreach (explode(',', $sopapurow["sopimus_pp"]) as $numi) {
					$chk[$numi] = "checked";
				}

				echo "<table><tr>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[1] value='1'> 1 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[2] value='2'> 2 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[3] value='3'> 3 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[4] value='4'> 4 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[5] value='5'> 5 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[6] value='6'> 6 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[7] value='7'> 7 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[8] value='8'> 8 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[9] value='9'> 9 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[10] value='10'> 10 </td>";
				echo "</tr><tr>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[11] value='11'> 11 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[12] value='12'> 12 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[13] value='13'> 13 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[14] value='14'> 14 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[15] value='15'> 15 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[16] value='16'> 16 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[17] value='17'> 17 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[18] value='18'> 18 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[19] value='19'> 19 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[20] value='20'> 20 </td>";
				echo "</tr><tr>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[21] value='21'> 21 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[22] value='22'> 22 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[23] value='23'> 23 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[24] value='24'> 24 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[25] value='25'> 25 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[26] value='26'> 26 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[27] value='27'> 27 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[28] value='28'> 28 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[29] value='29'> 29 </td>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[30] value='30'> 30 </td>";
				echo "</tr><tr>";
				echo "<td><input name='yllapito_pp[]' $disable type='checkbox' $chk[31] value='31'> 31 </td>";
				echo "<td colspan='9'><input name='yllapito_pp[]' $disable type='checkbox' $chk[99] value='99'> Kuun viimeinen </td>";
				echo "</tr></table>";
				echo "</td>";
				echo "</tr>";
				echo "<tr><th colspan='4'>".t("Sopimuksen voimassaoloaika").":</th></tr>";
				echo "<tr>";

				if ($sopapurow["sopimus_alkupvm"] == "0000-00-00") $sopapurow["sopimus_alkupvm"] = "";
				if ($sopapurow["sopimus_loppupvm"] == "0000-00-00") $sopapurow["sopimus_loppupvm"] = "";

				if (!isset($yllapito_alkuvv) or !isset($yllapito_alkukk) or !isset($yllapito_alkupp))
					list($yllapito_alkuvv, $yllapito_alkukk, $yllapito_alkupp) = explode('-', $sopapurow["sopimus_alkupvm"]);

				if (!isset($yllapito_loppuvv) or !isset($yllapito_loppukk) or !isset($yllapito_loppupp))
					list($yllapito_loppuvv, $yllapito_loppukk, $yllapito_loppupp) = explode('-', $sopapurow["sopimus_loppupvm"]);

				echo "<td>Alkupäivä:</td>";
				echo "<td>";
				echo "<input type='text' name='yllapito_alkupp' value='$yllapito_alkupp' size='3'>";
				echo "<input type='text' name='yllapito_alkukk' value='$yllapito_alkukk' size='3'>";
				echo "<input type='text' name='yllapito_alkuvv' value='$yllapito_alkuvv' size='6'>";
				echo "</td>";
				echo "</tr><tr>";
				echo "<td>Loppupäivä:</td>";
				echo "<td>";
				echo "<input type='text' name='yllapito_loppupp' value='$yllapito_loppupp' size='3'>";
				echo "<input type='text' name='yllapito_loppukk' value='$yllapito_loppukk' size='3'>";
				echo "<input type='text' name='yllapito_loppuvv' value='$yllapito_loppuvv' size='6'>";
				echo "</td>";
				echo "</tr>";
				echo "</table><br>";

			}

			echo "<table>";

			if ($toim == "TYOMAARAYS") {
				echo "<tr><th colspan='3'>".t("Työmääräyksen kommentit").":</th></tr>";
			}
			elseif ($toim == "VALMISTAVARASTOON" or $toim == "VALMISTAASIAKKAALLE") {
				echo "<tr><th colspan='3'>".t("Valmistuksen kommentit").":</th></tr>";
			}
			elseif ($toim == "SIIRTOLISTA") {
				echo "<tr><th colspan='3'>".t("Siirtolistan kommentit").":</th></tr>";
			}
			elseif ($toim == "MYYNTITILI") {
				echo "<tr><th colspan='3'>".t("Myyntitilin kommentit").":</th></tr>";
			}
			elseif ($toim == "TARJOUS") {
				echo "<tr><th colspan='3'>".t("Tarjouksen kommentit").":</th></tr>";
			}
			elseif ($toim == "PROJEKTI") {
				echo "<tr><th colspan='3'>".t("Projektin kommentit").":</th></tr>";
			}
			else {
				echo "<tr><th colspan='3'>".t("Tilauksen kommentit").":</th></tr>";
			}


			echo "<tr><td>".t("Tilausviite").":</td><td><input type='text' size='43' name='viesti' value='$srow[viesti]'></td><td>(".t("keräyslista + lähete + lasku").")</td></tr>";
			echo "<tr><td>".t("Kommentti")." 1:</td><td><textarea name='comments'   rows='2' cols='40'>$srow[comments]</textarea></td><td>(".t("keräyslista + lähete").")</td></tr>";
			echo "<tr><td>".t("Kommentti")." 2:</td><td><textarea name='sisviesti2' rows='2' cols='40'>$srow[sisviesti2]</textarea></td><td>(".t("keräyslista").")</td></tr>";
			echo "<tr><td>".t("Kommentti")." 3:</td><td><textarea name='sisviesti1' rows='2' cols='40'>$srow[sisviesti1]</textarea></td><td>(".t("lasku").")</td></tr>";

			if ($toim != "TYOMAARAYS" and ($toim == "TARJOUS" or $srow["tilaustyyppi"] == "T")) {
				echo "<tr><td>".t("Työn kuvaus").":</td><td colspan='2'><textarea name='komm1' rows='6' cols='60'>$srow[komm1]</textarea></td></tr>";
			}


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

		// HUOM!!! Asiakkaanvaihto ei nyt tällä hetkellä mene tähän haaraan. Vaatii redesignin!!!!!
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
						if ($row["alv"] != '' and $row["alv"] < 500 and $yhtiorow["alv_kasittely"] == "" ) {
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
					//Asiakas ei vaihtunut hinnat pysyvät samoina, alvia joudumme ehkä korjaamaan
					if ($row["alv"] != '' and $row["alv"] < 500 and $yhtiorow["alv_kasittely"] == "") {
						$uusihinta = sprintf('%.2f',round($row['hinta'] / (1+$row['alv']/100) * (1+$trow["alv"]/100),2));
					}
					else {
						$uusihinta = $row['hinta'];
					}
					$uusiale = $row['ale'];
				}

				//lasketaan alvit
				$hinta 	= $uusihinta;

				if($row["alv"] < 500) {
					$alv = "";
				}
				else {
					$alv = 500;
				}

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


			//Tsekataan vielä, että olio varmasi löytyy, muuten lisätään se
			$apuqu = "select yhtio from tyomaarays where yhtio='$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
			$mexapu = mysql_query($apuqu) or pupe_error($apuqu);

			if (mysql_num_rows($mexapu) == 0) {
				$query2 = "INSERT into ";
				$postquery2 = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";
			}
			else {
				$query2 = "UPDATE ";
				$postquery2 = " WHERE otunnus = '$kukarow[kesken]'";
			}

			//Tsekataan vielä, että olio varmasi löytyy, muuten lisätään se
			$apuqu = "select yhtio from laskun_lisatiedot where yhtio='$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
			$mexapu = mysql_query($apuqu) or pupe_error($apuqu);

			if (mysql_num_rows($mexapu) == 0) {
				$query3 = "INSERT into ";
				$postquery3 = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";
			}
			else {
				$query3 = "UPDATE ";
				$postquery3 = " WHERE otunnus = '$kukarow[kesken]'";
			}
		}

		if (strlen(trim($tnimi)) == 0) {
			$tnimi     = $nimi;
			$tnimitark = $nimitark;
			$tosoite   = $osoite;
			$tpostino  = $postino;
			$tpostitp  = $postitp;
			$toim_maa  = $maa;
		}

		// haetaan maksuehdoen tiedot tarkastuksia varten
		$apuqu = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$maksuehto'";
		$meapu = mysql_query($apuqu) or pupe_error($apuqu);
		$meapurow = mysql_fetch_array($meapu);

		// jos kyseessä oli käteinen
		if ($meapurow["kateinen"] != "") {
			// haetaan toimitustavan tiedot tarkastuksia varten
			$apuqu2 = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$toimitustapa'";
			$meapu2 = mysql_query($apuqu2) or pupe_error($apuqu2);
			$meapu2row = mysql_fetch_array($meapu2);

			// ja toimitustapa ei ole nouto laitetaan toimitustavaksi nouto... hakee järjestyksessä ekan
			if ($meapu2row["nouto"] == "") {
				$apuqu = "select * from toimitustapa where yhtio = '$kukarow[yhtio]' and nouto != '' order by jarjestys limit 1";
				$meapu = mysql_query($apuqu) or pupe_error($apuqu);
				$apuro = mysql_fetch_array($meapu);
				$toimitustapa = $apuro['selite'];
			}
		}

		// jos kyseessä oli käsin syötetty eräpäivä ja maksuehto on väärin laitetaan oikea maksuehto... hakee järjestyksessä ekan
		if ($erpcm_kasin == "KYLLA" and $meapurow["erapvmkasin"] == "") {
			$erpquery = "select * from maksuehto where yhtio = '$kukarow[yhtio]' and erapvmkasin != '' order by tunnus limit 1";
			$erpres = mysql_query($erpquery) or pupe_error($erpquery);
			$erprow = mysql_fetch_array($erpres);
			$maksuehto = $erprow["tunnus"]; // valitaan tää maksuehdoks
		}

		// jos ollaan valittu rahdinmaksajaksi oletus, haetaan oletusmaksaja toimitustavalta
		if ($maksaja == "O") {
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

		// liitostunnusfixaus
		if ((int) $liitostunnus == 0) $liitostunnus = $asiakasid;

		//jos oollaan haluttu laskutuskielto laitetaan chn kenttään 999
		if ($laskutuskielto != '') {
			//$srow['chn'] = '999';
			$chn = '999';
		}
		elseif ($liitostunnus != "") {
			$apuqu = "select chn from asiakas where yhtio='$kukarow[yhtio]' and tunnus = '$liitostunnus'";
			$meapu = mysql_query($apuqu) or pupe_error($apuqu);
			$apuro = mysql_fetch_array($meapu);
			$chn = $apuro['chn'];
			if ($chn == '') {
				$chn = '100';
			}
		}

		if ($kasitoimehto != '') {
			$toimitusehto = trim($toimitusehto." ".$kasitoimehto);
		}

		if ($kasitoimehto2 != '') {
			$toimitusehto2 = trim($toimitusehto2." ".$kasitoimehto2);
		}

		//Otetaan valuutan kurssi
		list($valkoodi, $kurssi) = explode('##', $valkoodi);

		$query .= "	lasku SET
					ytunnus								= '$ytunnus',
					nimi 								= '$nimi',
					nimitark 							= '$nimitark',
					osoite 								= '$osoite',
					postino 							= '$postino',
					postitp 							= '$postitp',
					maa 								= '$maa',
					toim_nimi 							= '$tnimi',
					toim_nimitark 						= '$tnimitark',
					toim_osoite 						= '$tosoite',
					toim_postino 						= '$tpostino',
					toim_postitp 						= '$tpostitp',
					toim_maa 							= '$toim_maa',
					verkkotunnus						= '$verkkotunnus',
					toimaika 							= '$toimvv-$toimkk-$toimpp',
					kerayspvm 							= '$kervv-$kerkk-$kerpp',
					erpcm								= '$erpcmvv-$erpcmkk-$erpcmpp',
					maksuehto 							= '$maksuehto',
					toimitustapa 						= '$toimitustapa',
					toimitusehto 						= '$toimitusehto',
					kohdistettu 						= '$maksaja',
					myyja 								= '$myyja',
					alv 								= '$alv',
					comments 							= '$comments',
					ovttunnus 							= '$ovttunnus',
					toim_ovttunnus 						= '$toim_ovttunnus',
					viesti 								= '$viesti',
					chn									= '$chn',
					kuljetus 							= '$kuljetus',
					huolitsija 							= '$huolitsija',
					maksuteksti 						= '$maksuteksti',
					clearing							= '$clearing',
					jakelu 								= '$jakelu',
					tilausvahvistus 					= '$tilausvahvistus',
					laskutusvkopv 						= '$laskutusvkopv',
					erikoisale 							= '$erikoisale',
					yhtio 								= '$kukarow[yhtio]',
					alatila 							= '$alatila',
					vienti 								= '$vienti',
					varasto 							= '$varasto',
					tilaustyyppi						= '$tilaustyyppi',
					ketjutus 							= '$ketjutus',
					sisainen 							= '$sisainen',
					eilahetetta 						= '$eilahe',
					valkoodi 							= '$valkoodi',
					vienti_kurssi						= '$kurssi',
					hyvaksynnanmuutos 					= '$luokka',
					liitostunnus						= '$liitostunnus',
					viikorkopros 						= '$yhtiorow[viivastyskorko]',
					osatoimitus							= '$osatoimitus',
					sisviesti1 							= '$sisviesti1',
					sisviesti2							= '$sisviesti2',
					poistumistoimipaikka_koodi			= '$poistumistoimipaikka_koodi',
					kuljetusmuoto						= '$kuljetusmuoto',
					kauppatapahtuman_luonne				= '$kauppatapahtuman_luonne',
					aktiivinen_kuljetus_kansallisuus	= '$aktiivinen_kuljetus_kansallisuus',
					aktiivinen_kuljetus					= '$aktiivinen_kuljetus',
					kontti								= '$kontti',
					sisamaan_kuljetusmuoto				= '$sisamaan_kuljetusmuoto',
					sisamaan_kuljetus_kansallisuus		= '$sisamaan_kuljetus_kansallisuus',
					sisamaan_kuljetus					= '$sisamaan_kuljetus',
					tunnusnippu							= '$tunnusnippu',
					jaksotettu							= '$jaksotettu',
					splittauskielto						= '$splittauskielto',
					maa_maara							= '$maa_maara'";

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
		elseif ($toim == "PROJEKTI") {
			if ($ylatila=='') {
				$ylatila='R';
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

		if ($toim == "PROJEKTI" or ($toim=="TARJOUS" and $yhtiorow["tarjouksen_voi_versioida"]!="")) {
			$query = "	UPDATE lasku
						SET tunnusnippu = '$tilausnumero'
						WHERE tunnus = '$tilausnumero'";
			$result = mysql_query($query) or pupe_error($query);
		}

		if ($toim == "TYOMAARAYS" or trim($komm1) != "") {
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

		if ($toim == "RIVISYOTTO" or $toim == "TARJOUS" or $tilaustyyppi == "T") {
			$query3 .= "laskun_lisatiedot SET
						yhtio						= '$kukarow[yhtio]',
						kolm_ovttunnus				= '$kolm_ovttunnus',
						kolm_nimi					= '$kolm_nimi',
						kolm_nimitark				= '$kolm_nimitark',
						kolm_osoite					= '$kolm_osoite',
						kolm_postino				= '$kolm_postino',
						kolm_postitp				= '$kolm_postitp',
						kolm_maa					= '$kolm_maa',
						toimitusehto2				= '$toimitusehto2',
						projektipaallikko			= '$projektipaallikko',
						yhteyshenkilo_kaupallinen	= '$yhteyshenkilo_kaupallinen',
						yhteyshenkilo_tekninen		= '$yhteyshenkilo_tekninen',
						seuranta					= '$seuranta',
						rivihintoja_ei_nayteta		= '$rivihintoja_ei_nayteta',
						asiakkaan_kohde				= '$asiakkaan_kohde',
						otunnus 					= '$kukarow[kesken]'";
			$query3 .= $postquery3;
			$result = mysql_query($query3) or pupe_error($query3);
		}

		if ($toim == "YLLAPITO") {

			$sopimus_kk = implode(",", $yllapito_kk);
			$sopimus_pp = implode(",", $yllapito_pp);

			$query3 .= "laskun_lisatiedot SET
						yhtio						= '$kukarow[yhtio]',
						sopimus_kk					= '$sopimus_kk',
						sopimus_pp					= '$sopimus_pp',
						sopimus_alkupvm				= '$yllapito_alkuvv-$yllapito_alkukk-$yllapito_alkupp',
						sopimus_loppupvm			= '$yllapito_loppuvv-$yllapito_loppukk-$yllapito_loppupp',
						otunnus 					= '$kukarow[kesken]'";
			$query3 .= $postquery3;
			$result = mysql_query($query3) or pupe_error($query3);
		}

	}
	unset($alv);
?>
