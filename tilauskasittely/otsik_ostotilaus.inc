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
		echo "<font class='head'>".t("Ostotilauksen otsikko").":</font><hr><br>";

		echo "<form method='post' action='$PHP_SELF' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='MUUOTAOSTIKKOA'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<table border='0' cellpadding='2' cellspacing='1'>";



		if (($tunnus != "") or ($kukarow["kesken"] != 0)) {
			if ($tunnus != "") {
				$squery = "	SELECT *, tunnus liitostunnus
							FROM toimi
							WHERE tunnus = '$tunnus'";
			}
			else {
				$squery = "	SELECT *, hyvaksynnanmuutos as luokka
							FROM lasku
							WHERE tunnus = '$kukarow[kesken]'";
			}
			$sresult = mysql_query($squery) or pupe_error($squery);
			$srow = mysql_fetch_array($sresult);

			if($srow["liitostunnus"]>0 and $toimittajaid == 0) $toimittajaid = $srow["liitostunnus"]; 
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

		echo "<tr><td colspan='2'>
				<input type='hidden' name='ovttunnus' value='$srow[ovttunnus]'>
				<input type='hidden' name='verkkotunnus' value='$srow[verkkotunnus]'>
				<input type='hidden' name='ytunnus' value='$srow[ytunnus]'>
				<input type='hidden' name='valkoodi' value='$valkoodi'>
				<input type='hidden' name='maa' value='$srow[maa]'>
				<input type='hidden' name='liitostunnus' value='$srow[liitostunnus]'>
				<input type='hidden' name='ylatila' value='$ylatila'>
				<input type='hidden' name='alatila' value='$alatila'>";
				
		echo "<table cellpadding='1' cellspacing='1'>
				<tr><th colspan='2' align='left' valign='top'>&nbsp;".t("Toimittajan tiedot").":</td></tr>
				<tr><td valign='top'>".t("Nimi").": </td><td><input type='text' name='nimi' size='35' maxlength='31' value='$srow[nimi]'></td></tr>
				<tr><td></td><td><input type='text' name='nimitark' size='35' maxlength='31'  value='$srow[nimitark]'></td></tr>
				<tr><td valign='top'>".t("Osoite").": </td><td><input type='text' name='osoite' size='35' maxlength='31' value='$srow[osoite]'></td></tr>
				<tr>
					<td valign='top'>".t("Postitp").": </td><td><input type='text' name='postino' size='6' maxlength='6' value='$srow[postino]'>
					<input type='text' name='postitp' size='21' maxlength='20' value='$srow[postitp]'>
					<input type='hidden' name='maa' value='$srow[maa]'>";
		
		if($toimittajaid == 0 or $tila == "vaihdaasiakas") {
			echo "		</td>
						</tr></table>
					</td>
				</tr>
				<tr>
					<td class='back' colspan = '2' align = 'right'><input type = 'hidden' name = 'tila' value = 'vaihdaasiakas'><input type='submit' value='".t("Hae")."'></td>
				</tr>
				</table>
				</form>";

			$formi  = "paaformi";
			$kentta = "nimi";			
		}
		else {
			
			echo "		</td>
					</tr>
					</table>
					</td>";
			
			// katotaan ollaanko valittu tilaajaksi joku muu toimipiste
			if (isset($srow)) {
				if ($toimipiste != 0) {
					$edique  = "select * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and tunnus='$toimipiste'";
					$ediotr = mysql_query($edique) or pupe_error($edique);
					$edioro = mysql_fetch_array($ediotr);

					// laitetaan toimipisteen tiedot toimitusosoitteeseen jos siellä ei vielä muuta ole
					$srow["toim_nimi"] 		= $edioro["nimi"];
					$srow["toim_nimitark"] 	= $edioro["nimitark"];
					$srow["toim_osoite"] 	= $edioro["osoite"];
					$srow["toim_postino"] 	= $edioro["postino"];
					$srow["toim_postitp"] 	= $edioro["postitp"];
					$srow["toim_maa"] 		= $edioro["maa"];
				}

				// varmistellaan, että meillä menee jotain toimitusosoite kenttään
				if(strlen(trim($srow["toim_nimi"])) == 0) {
					$srow["toim_nimi"] 		= $yhtiorow["nimi"];
					$srow["toim_nimitark"] 	= $yhtiorow["nimitark"];
					$srow["toim_osoite"] 	= $yhtiorow["osoite"];
					$srow["toim_postino"] 	= $yhtiorow["postino"];
					$srow["toim_postitp"] 	= $yhtiorow["postitp"];
					$srow["toim_maa"] 		= $yhtiorow["maa"];
				}
			}
			
			echo "<td colspan = '2'><table cellpadding='1' cellspacing='1'>";
			echo "<tr><th colspan='2' align='left' valign='top'>&nbsp; ".t("Toimitusosoite").":</td></tr>";
			echo "<tr><td valign='top'> ".t("Nimi").": </td><td><input type='text' name='tnimi' size='35' maxlength='31' value='$srow[toim_nimi]'></td></tr>";
			echo "<tr><td> </td><td><input type='text' name='tnimitark' size='35' maxlength='31'  value='$srow[toim_nimitark]'></td></tr>";
			echo "<tr><td valign='top'>".t("Osoite").": </td><td><input type='text' name='tosoite' size='35' maxlength='31' value='$srow[toim_osoite]'></td></tr>";
			echo "<tr><td valign='top'>".t("Postitp").": </td><td><input type='text' name='tpostino' size='8' maxlength='8' value='$srow[toim_postino]'>";
			echo "<input type='text' name='tpostitp' size='21' maxlength='20' value='$srow[toim_postitp]'></td></tr>";
			echo "<input type='hidden' name='tmaa' value='$srow[toim_maa]'>";
			echo "</table>";
			echo "</td></tr>";

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

			$query = "	SELECT tunnus, kuka, nimi, myyja
						FROM kuka
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY nimi";
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

			echo "<tr><td>".t("Kuljetus").": </td><td valign='middle'>";

			$query = "select selite from avainsana where yhtio='$kukarow[yhtio]' and laji='TOIMITUSTAPA_OS'";
			$toimtapares  = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($toimtapares) > 0) {
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
						WHERE yhtio='$kukarow[yhtio]'";
			$vares = mysql_query($query) or pupe_error($query);

			while ($varow = mysql_fetch_array($vares)) {
				if (($varow["tyyppi"] == '') or ($kukarow["varasto"] == $varow["tunnus"])) {

					$sel='';
					if ($varow['tunnus']==$srow["varasto"] or ($varow['tunnus'] == $kukarow['varasto'] and $srow['varasto'] == '')) $sel = 'selected';

					echo "<option value='$varow[tunnus]' $sel>$varow[nimitys]</option>";
				}
			}

			echo "</select></td></tr>";

			echo "<tr><td>".t("Tilausyhteyshenkilö").":</td>";

			$yhteysquery = "SELECT * FROM yhteyshenkilo where yhtio = '$kukarow[yhtio]' and liitostunnus = '$srow[liitostunnus]' and tyyppi = 'T' and tilausyhteyshenkilo != ''";
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
					
			echo "&nbsp;&nbsp;<form method='post' action='$PHP_SELF' autocomplete='off'>";
			echo "<input type='hidden' name='tila' value='vaihdaasiakas'>";
			echo "<input type='hidden' name='tee' value='MUUOTAOSTIKKOA'>";
			echo "<input type='hidden' name='lopetus' value='$lopetus'>";
			echo "<input type='hidden' name='toim' value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
			echo "<td class='back'><input type='submit' ACCESSKEY='A' value='".t("Vaihda toimittaja")."'></td></form>";
					
		}
	}

	if(isset($jatka)) {
		//Erikoisalennuksen desimaalipilkku
		$erikoisale = str_replace ( ",", ".", $erikoisale);

		if ($kukarow["kesken"] == 0) {
			$query = "INSERT into ";
			$postquery = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";
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
		}

		// katotaan ollaanko valittu tilaajaksi joku muu toimipiste
		if ($toimipiste != 0) {
			$edique  = "select * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and tunnus='$toimipiste'";
			$ediotr = mysql_query($edique) or pupe_error($edique);
			$edioro = mysql_fetch_array($ediotr);

			// laitetaan toimipisteen tiedot toimitusosoitteeseen jos siellä ei vielä muuta ole
			$tnimi     	= $edioro["nimi"];
			$tnimitark 	= $edioro["nimitark"];
			$tosoite   	= $edioro["osoite"];
			$tpostino  	= $edioro["postino"];
			$tpostitp  	= $edioro["postitp"];
			$tmaa  		= $edioro["maa"];
		}

		// varmistellaan, että meillä menee jotain toimitusosoite kenttään
		if(strlen(trim($tnimi)) == 0) {
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

		$query .= "	lasku SET
					ytunnus				= '$ytunnus',
					nimi 				= '$nimi',
					nimitark 			= '$nimitark',
					osoite 				= '$osoite',
					postino 			= '$postino',
					postitp 			= '$postitp',
					maa 				= '$maa',
					toim_nimi 			= '$tnimi',
					toim_nimitark 		= '$tnimitark',
					toim_osoite 		= '$tosoite',
					toim_postino 		= '$tpostino',
					toim_postitp 		= '$tpostitp',
					toim_maa 			= '$tmaa',
					verkkotunnus		= '$verkkotunnus',
					toimaika 			= '".$toimvv."-".$toimkk."-".$toimpp."',
					kerayspvm 			= '".$kervv."-".$kerkk."-".$kerpp."',
					maksuehto 			= '$maksuehto',
					toimitustapa 		= '$toimitustapa',
					tilausyhteyshenkilo	= '$tilausyhteyshenkilo',
					toimitusehto 		= '$maksaja',
					myyja 				= '$myyja',
					alv 				= '$alv',
					comments 			= '$comments',
					ovttunnus 			= '$ovttunnus',
					toim_ovttunnus 		= '$toim_ovttunnus',
					viesti 				= '$viesti',
					sisviesti1			= '$sisviesti1',
					chn					= '$chn',
					kuljetus 			= '$kuljetus',
					huolitsija 			= '$huolitsija',
					maksuteksti 		= '$maksuteksti',
					jakelu 				= '$jakelu',
					tilausvahvistus 	= '$tilausvahvistus',
					laskutusvkopv 		= '$laskutusvkopv',
					erikoisale 			= '$erikoisale',
					yhtio 				= '$kukarow[yhtio]',
					alatila 			= '$alatila',
					vienti 				= '$vienti',
					varasto 			= '$varasto',
					tilaustyyppi		= '$tilaustyyppi',
					ketjutus 			= '$ketjutus',
					sisainen 			= '$sisainen',
					eilahetetta 		= '$eilahe',
					valkoodi 			= '$valkoodi',
					hyvaksynnanmuutos 	= '$luokka',
					liitostunnus		= '$liitostunnus',
					vanhatunnus			= '$toimipiste',
					viikorkopros 		= '$yhtiorow[viivastyskorko]',
					tila = 'O'";
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
