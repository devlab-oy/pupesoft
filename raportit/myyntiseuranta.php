<?php
	// katsotaan tuleeko kaikki muuttujat REQUEST:ssa serialisoituna
	if (isset($_REQUEST['kaikki_parametrit_serialisoituna'])) {

		$kaikki_parametrit_serialisoituna = unserialize(urldecode($_REQUEST['kaikki_parametrit_serialisoituna']));
		$kaikki_muuttujat_array = array();

		foreach ($kaikki_parametrit_serialisoituna as $parametri_key => $parametri_value) {
			${$parametri_key} = $parametri_value;
			$_REQUEST[$parametri_key] = $parametri_value;
		}

		unset($_REQUEST['kaikki_parametrit_serialisoituna']);
	}

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if(isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta.php") !== FALSE) {
		require ("../inc/parametrit.inc");
	}

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {
		echo "<font class='head'>",t("Myynninseuranta"),"</font><hr>";

		// tehd‰‰n kaikista raportin parametreist‰ yksi muuttuja serialisoimista varten
		$kaikki_muuttujat_array = array();

		foreach ($_REQUEST as $kaikki_muuttujat_array_key => $kaikki_muuttujat_array_value) {
			if ($kaikki_muuttujat_array_key != "pupesoft_session" and
				$kaikki_muuttujat_array_key != "uusi_kysely" and
				$kaikki_muuttujat_array_key != "tallenna_muutokset" and
				$kaikki_muuttujat_array_key != "poista_kysely" and
				$kaikki_muuttujat_array_key != "aja_kysely") {
				$kaikki_muuttujat_array[$kaikki_muuttujat_array_key] = $kaikki_muuttujat_array_value;
			}
		}

		if (!aja_kysely()) {
			unset($_REQUEST);
		}

		//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
		$useslave = 1;
		$usemastertoo = 1;

		require ("inc/connect.inc");

		if ($lopetus == "") {

			if (isset($muutparametrit)) {
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

			//K‰yttˆliittym‰
			if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			if (!isset($kkl)) $kkl = date("m");
			if (!isset($vvl)) $vvl = date("Y");
			if (!isset($ppl)) $ppl = date("d");
			if (!isset($yhtio)) $yhtio = "'$kukarow[yhtio]'";

			echo "<br>\n\n\n";
			echo "<form method='post'>";
			echo "<input type='hidden' name='tee' value='go'>";

			// t‰ss‰ on t‰m‰ "perusn‰kym‰" mik‰ tulisi olla kaikissa myynnin raportoinneissa..

			// Jos ajetaan tilauksittain vaihdetaan aina ajotavaksi 'tilaus'
			if (isset($ruksit[140]) and $ruksit[140] != "") {
				$ajotapa = 'tilaus';
			}

			if ($ajotapa == "lasku") {
				$chk1 = "SELECTED";
			}
			elseif ($ajotapa == "tilaus") {
				$chk2 = "SELECTED";
			}
			elseif ($ajotapa == "tilausjaauki") {
				$chk3 = "SELECTED";
			}
			elseif ($ajotapa == "tilausjaaukiluonti") {
				$chk4 = "SELECTED";
			}
			elseif ($ajotapa == "ennakot") {
				$chk5 = "SELECTED";
			}
			elseif ($ajotapa == "tilausauki") {
				$chk6 = "SELECTED";
			}
			else {
				$chk1 = "SELECTED";
			}

			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Valitse ajotapa:"),"</th>";

			echo "<td><select name='ajotapa'>";
			echo "<option value='lasku'  				{$chk1}>",t("Laskuista")," (",t("Laskutus"),")</option>";
			echo "<option value='tilaus' 				{$chk2}>",t("Laskutetuista tilauksista"),"</option>";
			echo "<option value='tilausjaauki'			{$chk3}>",t("Laskutetuista sek‰ avoimista tilauksista"),"</option>";
			echo "<option value='tilausjaaukiluonti'	{$chk4}>",t("Laskutetuista sek‰ avoimista tilauksista luontiajalla")," (",t("Myynti"),")</option>";
			echo "<option value='ennakot'				{$chk5}>",t("Lep‰‰m‰ss‰ olevista ennakoista"),"</option>";
			echo "<option value='tilausauki'			{$chk6}>",t("Avoimista tilauksista"),"</option>";
			echo "</select></td>";

			echo "</tr>";

			$chk1 = $chk2 = $chk3 = $chk4 = $chk5 = $chk6 = '';

			if ($ajotapanlisa == "summattuna") {
				$chk1 = "SELECTED";
			}
			elseif ($ajotapanlisa == "erikseen") {
				$chk2 = "SELECTED";
			}
			else {
				$chk1 = "SELECTED";
			}

			echo "<tr>";
			echo "<th>",t("Ajotavan lis‰parametrit:"),"</th>";

			echo "<td><select name='ajotapanlisa'>";
			echo "<option value='summattuna'  {$chk1}>",t("Veloitukset ja hyvitykset summattuina"),"</option>";
			echo "<option value='erikseen' 	  {$chk2}>",t("Veloitukset ja hyvitykset allekkain"),"</option>";
			echo "</select></td>";
			echo "</tr>";
			echo "</table><br>";

			$query = "	SELECT *
						FROM yhtio
						WHERE konserni = '{$yhtiorow['konserni']}'
						AND konserni != ''";
			$result = pupe_query($query);

			// voidaan valita listaukseen useita konserniyhtiˆit‰, jos k‰ytt‰j‰ll‰ on "PƒIVITYS" oikeus t‰h‰n raporttiin
			if (mysql_num_rows($result) > 0 and $oikeurow['paivitys'] != "") {
				echo "<table>";
				echo "<tr>";
				echo "<th>",t("Valitse yhtiˆ"),"</th>";

				if (!isset($yhtiot)) $yhtiot = array();

				while ($row = mysql_fetch_assoc($result)) {
					$sel = "";

					if ($kukarow["yhtio"] == $row["yhtio"] and count($yhtiot) == 0) $sel = "CHECKED";
					if (in_array($row["yhtio"], $yhtiot)) $sel = "CHECKED";

					echo "<td><input type='checkbox' name='yhtiot[]' onchange='submit()' value='{$row['yhtio']}' {$sel}>{$row['nimi']}</td>";
				}

				echo "</tr>";
				echo "</table><br>";
			}
			else {
				echo "<input type='hidden' name='yhtiot[]' value='{$kukarow['yhtio']}'>";
			}

			$noautosubmit = TRUE;
			$monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA", "ASIAKASPIIRI", "<br>DYNAAMINEN_ASIAKAS", "<br>OSASTO", "TRY", "TUOTEMERKKI", "MALLI/MALLITARK", "<br>DYNAAMINEN_TUOTE", "<br>LASKUMYYJA", "TUOTEMYYJA", "ASIAKASMYYJA", "TUOTEOSTAJA", "<br>TOIMIPAIKKA","KUSTP", "KOHDE", "PROJEKTI");
			$monivalintalaatikot_normaali = array();

			require ("tilauskasittely/monivalintalaatikot.inc");

			echo "<br><br>";

			// lis‰rajaukset n‰kym‰..
			$ruk10chk  				= "";
			$ruk20chk  				= "";
			$ruk30chk  				= "";
			$ruk40chk  				= "";
			$ruk50chk  				= "";
			$ruk60chk  				= "";
			$ruk80chk  				= "";
			$ruk90chk  				= "";
			$ruk100chk 				= "";
			$ruk110chk 				= "";
			$nimchk   				= "";
			$katchk   				= "";
			$nettokatchk			= "";
			$tarchk   				= "";
			$piychk   				= "";
			$sarjachk 				= "";
			$sarjachk2 				= "";
			$kuuchk	  				= "";
			$varvochk 				= "";
			$piiloedchk 			= "";
			$tilrivikommchk 		= "";
			$vain_excelchk 			= "";
			$piilota_myynti_sel 	= "";
			$piilota_nettokate_sel	= "";
			$piilota_kate_sel 		= "";
			$piilota_kappaleet_sel 	= "";
			$einollachk 			= "";
			$naytaennakkochk 		= "";
			$laskutuspaivachk		= "";

			if ($ruksit[10]  != '') 		$ruk10chk  				= "CHECKED";
			if ($ruksit[20]  != '') 		$ruk20chk  				= "CHECKED";
			if ($ruksit[30]  != '') 		$ruk30chk  				= "CHECKED";
			if ($ruksit[40]  != '') 		$ruk40chk  				= "CHECKED";
			if ($ruksit[50]  != '') 		$ruk50chk  				= "CHECKED";
			if ($ruksit[60]  != '') 		$ruk60chk  				= "CHECKED";
			if ($ruksit[80]  != '') 		$ruk80chk  				= "CHECKED";
			if ($ruksit[90]  != '') 		$ruk90chk  				= "CHECKED";
			if ($ruksit[100]  != '') 		$ruk100chk 				= "CHECKED";
			if ($ruksit[110]  != '') 		$ruk110chk 				= "CHECKED";
			if ($ruksit[120] != '')			$ruk120chk 				= "CHECKED";
			if ($ruksit[130] != '')			$ruk130chk 				= "CHECKED";
			if ($ruksit[140] != '')			$ruk140chk 				= "CHECKED";

			if ($nimitykset != '')   		$nimchk   				= "CHECKED";
			if ($kateprossat != '')  		$katchk   				= "CHECKED";
			if ($nettokateprossat != '')	$nettokatchk			= "CHECKED";
			if ($osoitetarrat != '') 		$tarchk   				= "CHECKED";
			if ($piiyhteensa != '')  		$piychk   				= "CHECKED";
			if ($sarjanumerot != '')  		$sarjachk 				= "CHECKED";
			if ($eiOstSarjanumeroita != '') $sarjachk2 				= "CHECKED";
			if ($kuukausittain != '')		$kuuchk	  				= "CHECKED";
			if ($varastonarvo != '')		$varvochk 				= "CHECKED";
			if ($piiloed != '')				$piiloedchk 			= "CHECKED";
			if ($tilrivikomm != '')			$tilrivikommchk 		= "CHECKED";
			if ($vain_excel != '')			$vain_excelchk 			= "CHECKED";
			if ($piilota_myynti != '')		$piilota_myynti_sel 	= "CHECKED";
			if ($piilota_nettokate != '')	$piilota_nettokate_sel	= "CHECKED";
			if ($piilota_kate != '')		$piilota_kate_sel 		= "CHECKED";
			if ($piilota_kappaleet != '')	$piilota_kappaleet_sel 	= "CHECKED";
			if ($piilotanollarivit != '')	$einollachk 			= "CHECKED";
			if ($naytaennakko != '')		$naytaennakkochk 		= "CHECKED";
			if ($vertailubu != '')			${"sel_".$vertailubu}	= "SELECTED";
			if ($naytakaikkiasiakkaat != '') $naytakaikkiasiakkaatchk = "CHECKED";
			if ($naytakaikkituotteet != '') $naytakaikkituotteetchk	= "CHECKED";
			if ($laskutuspaiva != '')		$laskutuspaivachk		= "CHECKED";
			if ($ytunnus_mistatiedot != '')	$ytun_mistatiedot_sel	= "SELECTED";
			if ($naytamaksupvm != '')		$naytamaksupvmchk 		= "CHECKED";
			if ($asiakaskaynnit != '')		$asiakaskaynnitchk 		= "CHECKED";
			if ($liitetiedostot != '')		$liitetiedostotchk		= "CHECKED";
			if ($ytun_laajattied != '')		$ytun_laajattiedchk		= "CHECKED";
			if ($naytatoimtuoteno != '')	$naytatoimtuotenochk 	= "CHECKED";

			echo "<table>
				<tr>
				<th>",t("Lis‰rajaus"),"</th>
				<th>",t("Prio"),"</th>
				<th> x</th>
				<th>",t("Rajaus"),"</th>
				</tr>
				<tr>
				<tr>
				<th>",t("Listaa y-tunnuksella"),"</th>
				<td><input type='text' name='jarjestys[10]' size='2' value='{$jarjestys[10]}'></td>
				<td><input type='checkbox' name='ruksit[10]' value='ytunnus' {$ruk10chk}></td>
				<td><input type='text' name='ytunnus' value='{$ytunnus}'>
				<br>
				<table><tr><td class='spec'>".t("Hae asiakastiedot").":</td><td><select name='ytunnus_mistatiedot'>
				<option value=''>",t("Asiakasrekisterist‰"),"</option>
				<option value='laskulta' {$ytun_mistatiedot_sel}>",t("Laskuilta"),"</option>
				</select></td></tr>
				<tr><td class='spec'>".t("N‰yt‰ laajat asiakastiedot").":</td><td><input type='checkbox' name='ytun_laajattied' value='laajat' {$ytun_laajattiedchk}></td></tr>
				</table>
				</tr>
				<tr>
				<th>",t("Listaa asiakasnumerolla"),"</th>
				<td><input type='text' name='jarjestys[20]' size='2' value='{$jarjestys[20]}'></td>
				<td><input type='checkbox' name='ruksit[20]' value='asiakasnro' {$ruk20chk}></td>
				<td><input type='text' name='asiakasnro' value='{$asiakasnro}'></td>
				</tr>
				<tr>
				<th>",t("Listaa tuotteittain"),"</th>
				<td><input type='text' name='jarjestys[30]' size='2' value='{$jarjestys[30]}'></td>
				<td><input type='checkbox' name='ruksit[30]' value='tuote' {$ruk30chk}></td>
				<td><input type='text' name='rajaus[30]' value='{$rajaus[30]}'></td>
				</tr>
				<tr>
				<th>",t("Listaa maittain"),"</th>
				<td><input type='text' name='jarjestys[40]' size='2' value='{$jarjestys[40]}'></td>
				<td><input type='checkbox' name='ruksit[40]' value='maa' {$ruk40chk}></td>
				<td><input type='text' name='rajaus[40]' value='{$rajaus[40]}'></td>
				</tr>
				<tr>
				<th>",t("Listaa toimittajittain"),"</th>
				<td><input type='text' name='jarjestys[50]' size='2' value='{$jarjestys[50]}'></td>
				<td><input type='checkbox' name='ruksit[50]' value='toimittaja' {$ruk50chk}></td>
				<td><input type='text' name='toimittaja' value='{$toimittaja}'></td>
				</tr>
				<tr>
				<th>",t("Listaa tilaustyypeitt‰in"),"</th>
				<td><input type='text' name='jarjestys[60]' size='2' value='{$jarjestys[60]}'></td>
				<td><input type='checkbox' name='ruksit[60]' value='tilaustyyppi' {$ruk60chk}></td>
				<td><input type='text' name='rajaus[60]' value='{$rajaus[60]}'></td>
				<td class='back'>",t("(Toimii vain jos ajat raporttia tilauksista)"),"</td>
				</tr>
				<tr>
				<th>",t("Listaa konsernittain"),"</th>
				<td><input type='text' name='jarjestys[80]' size='2' value='{$jarjestys[80]}'></td>
				<td><input type='checkbox' name='ruksit[80]' value='konserni' {$ruk80chk}></td>
				<td><input type='text' name='rajaus[80]' value='{$rajaus[80]}'></td>
				</tr>
				<tr>
				<th>",t("Listaa laskuittain"),"</th>
				<td><input type='text' name='jarjestys[90]' size='2' value='{$jarjestys[90]}'></td>
				<td><input type='checkbox' name='ruksit[90]' value='laskuittain' {$ruk90chk}></td>
				<td><input type='text' name='rajaus[90]' value='{$rajaus[90]}'></td>
				</tr>
				<tr>
				<th>",t("Listaa varastoittain"),"</th>
				<td><input type='text' name='jarjestys[100]' size='2' value='{$jarjestys[100]}'></td>
				<td><input type='checkbox' name='ruksit[100]' value='varastoittain' {$ruk100chk}></td>
				<td><input type='text' name='rajaus[100]' value='{$rajaus[100]}'></td>
				</tr>
				<tr>
				<th>",t("Listaa kanta-asiakkaittain"),"</th>
				<td><input type='text' name='jarjestys[110]' size='2' value='{$jarjestys[110]}'></td>
				<td><input type='checkbox' name='ruksit[110]' value='kantaasiakkaittain' {$ruk110chk}></td>
				<td><input type='text' name='rajaus[110]' value='{$rajaus[110]}'></td>
				<td class='back'>",t("(Toimii vain jos ajat raporttia tilauksista)"),"</td>
				</tr>
				<tr>
				<th>",t("Listaa maksuehdoittain"),"</th>
				<td><input type='text' name='jarjestys[120]' size='2' value='{$jarjestys[120]}'></td>
				<td><input type='checkbox' name='ruksit[120]' value='maksuehdoittain' {$ruk120chk}></td>
				<td><input type='text' name='rajaus[120]' value='{$rajaus[120]}'></td>
				</tr>
				<tr>
				<th>",t("Listaa asiakkaan tilausnumeroittain"),"</th>
				<td><input type='text' name='jarjestys[130]' size='2' value='{$jarjestys[130]}'></td>
				<td><input type='checkbox' name='ruksit[130]' value='asiakkaan_tilausnumeroittain' {$ruk130chk}></td>
				<td><input type='text' name='rajaus[130]' value='{$rajaus[130]}'></td>
				</tr>
				<tr>
				<th>",t("Listaa tilauksittain"),"</th>
				<td><input type='text' name='jarjestys[140]' size='2' value='{$jarjestys[140]}'></td>
				<td><input type='checkbox' name='ruksit[140]' value='tilauksittain' {$ruk140chk}></td>
				<td><input type='text' name='rajaus[140]' value='{$rajaus[140]}'></td>
				<td class='back'>",t("(Toimii vain jos ajotapa on Laskutetuista tilaukista)"),"</td>
				</tr>
				<tr>
				<td class='back'><br></td>
				</tr>
				<tr><th valign='top'>",t("Tuotelista"),"<br>(",t("Rajaa n‰ill‰ tuotteilla"),")</th><td colspan='3'><textarea name='tuotteet_lista' rows='5' cols='35'>{$tuotteet_lista}</textarea></td></tr>
				<tr>
				<td class='back'><br></td>
				</tr>
				<tr>
				<th>",t("Piilota myynti"),"</th>
				<td colspan='3'><input type='checkbox' name='piilota_myynti' {$piilota_myynti_sel}></td>
				</tr>
				<tr>
				<th>",t("Piilota nettokate"),"</th>
				<td colspan='3'><input type='checkbox' name='piilota_nettokate' {$piilota_nettokate_sel}></td>
				</tr>
				<tr>
				<th>",t("Piilota kate"),"</th>
				<td colspan='3'><input type='checkbox' name='piilota_kate' {$piilota_kate_sel}></td>
				</tr>
				<tr>
				<th>",t("Piilota kappaleet"),"</th>
				<td colspan='3'><input type='checkbox' name='piilota_kappaleet' {$piilota_kappaleet_sel}></td>
				</tr>
				<tr>
				<th>",t("Piilota edellisen kauden sarakkeet"),"</th>
				<td colspan='3'><input type='checkbox' name='piiloed' {$piiloedchk}></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>",t("Piilota v‰lisummat"),"</th>
				<td colspan='3'><input type='checkbox' name='piiyhteensa' {$piychk}></td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ nettokateprosentit"),"</th>
				<td colspan='3'><input type='checkbox' name='nettokateprossat' {$nettokatchk}></td>
				<td class='back'>",t("(Toimii vain jos myynti ja nettokate n‰ytet‰‰n)"),"</td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ kateprosentit"),"</th>
				<td colspan='3'><input type='checkbox' name='kateprossat' {$katchk}></td>
				<td class='back'>",t("(Toimii vain jos myynti ja kate n‰ytet‰‰n)"),"</td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ tuotteiden nimitykset"),"</th>
				<td colspan='3'><input type='checkbox' name='nimitykset' {$nimchk}></td>
				<td class='back'>",t("(Toimii vain jos listaat tuotteittain)"),"</td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ sarjanumerot"),"</th>
				<td colspan='3'><input type='checkbox' name='sarjanumerot' {$sarjachk}></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ vain myydyt sarjanumerot"),"</th>
				<td colspan='3'><input type='checkbox' name='eiOstSarjanumeroita' {$sarjachk2}></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ varastonarvo"),"</th>
				<td colspan='3'><input type='checkbox' name='varastonarvo' {$varvochk}></td>
				<td class='back'>",t("(Toimii vain jos listaat tuotteittain)"),"</td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ tilausrivin kommentti"),"</th>
				<td colspan='3'><input type='checkbox' name='tilrivikomm' {$tilrivikommchk}></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>",t("Tulosta myynti kuukausittain"),"</th>
				<td colspan='3'><input type='checkbox' name='kuukausittain' {$kuuchk}></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>",t("Tulosta osoitetarrat"),"</th>
				<td colspan='3'><input type='checkbox' name='osoitetarrat' {$tarchk}></td>
				<td class='back'>",t("(Toimii vain jos listaat asiakkaittain)"),"</td>
				</tr>
				<tr>
				<th>",t("Raportti vain Exceliin"),"</th>
				<td colspan='3'><input type='checkbox' name='vain_excel' {$vain_excelchk}></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>",t("Piilota nollarivit"),"</th>
				<td colspan='3'><input type='checkbox' name='piilotanollarivit' {$einollachk}></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ myˆs ennakkolaskutus"),"</th>
				<td colspan='3'><input type='checkbox' name='naytaennakko' {$naytaennakkochk}></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ myˆs toimittajan tuoteno"),"</th>
				<td colspan='3'><input type='checkbox' name='naytatoimtuoteno' {$naytatoimtuotenochk}></td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ kaikki asiakkaat"),"</th>
				<td colspan='3'><input type='checkbox' name='naytakaikkiasiakkaat' {$naytakaikkiasiakkaatchk}></td>
				<td class='back'>",t("(N‰ytt‰‰ myˆs asiakkaat joita ei huomioida myynninseurannassa)"),"</td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ kaikki tuotteet"),"</th>
				<td colspan='3'><input type='checkbox' name='naytakaikkituotteet' {$naytakaikkituotteetchk}></td>
				<td class='back'>",t("(N‰ytt‰‰ tuotteet joita ei huomioida myynninseurannassa)"),"</td>
				</tr>
				<th>",t("N‰yt‰ laskutusp‰iv‰"),"</th>
				<td colspan='3'><input type='checkbox' name='laskutuspaiva' {$laskutuspaivachk}></td>
				<td class='back'>",t("(Toimii vain jos listataan tilauksittain tai laskuittain)"),"</td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ tuotteet statuksella"),"</th>";

			$status_result = t_avainsana("S");

			echo "<td colspan='3'><select name='status'><option value=''>",t("Kaikki"),"</option>";

			while ($statusrow = mysql_fetch_assoc($status_result)) {

				$sel = '';

				if (isset($status) and $status == $statusrow['selite']) $sel = ' SELECTED';

				echo "<option value='{$statusrow['selite']}'{$sel}>{$statusrow['selite']} - {$statusrow['selitetark']}</option>";
			}

			echo "</select></td></tr>";

			$vsel[$verkkokaupat] = "SELECTED";

			echo "<tr>
				<th>",t("Ohjelmamoduli"),"</th>
				<td colspan='3'>
				<select name='verkkokaupat'>
				<option value=''>",t("Kaikki ohjelmamodulit"),"</option>
				<option value='PUPESOFT'		{$vsel["PUPESOFT"]}>",t("Vain Pupesoft-tilauksia"),"</option>
				<option value='EXTRANET'		{$vsel["EXTRANET"]}>",t("Vain Extranet-tilauksia"),"</option>
				<option value='MAGENTO'			{$vsel["MAGENTO"]}>",t("Vain Magento verkkokauppa-tilauksia"),"</option>
				<option value='VARAOSASELAIN'	{$vsel["VARAOSASELAIN"]}>",t("Vain Varaosaselain-tilauksia"),"</option>
				<option value='VERKKOKAUPPA'	{$vsel["VERKKOKAUPPA"]}>",t("Vain Pupesoft verkkokauppa-tilauksia"),"</option>
				<option value='EDIFACT911'		{$vsel["EDIFACT911"]}>",t("Vain Orders 91.1 EDI-tilauksia"),"</option>
				<option value='FUTURSOFT'		{$vsel["FUTURSOFT"]}>",t("Vain Futursoft EDI-tilauksia"),"</option>
				</select>
				</td>
				<td class='back'></td>
				</tr>
				<tr>
				<th>",t("N‰yt‰ laskun maksup‰iv‰m‰‰r‰"),"</th>
				<td colspan='3'><input type='checkbox' name='naytamaksupvm' {$naytamaksupvmchk}></td>
				<td class='back'>",t("(Toimii vain jos listaat laskuittain)"),"</td>
				</tr>";

			echo "<tr>
			<th>",t("Tavoitevertailu"),"</th>";
			echo "<td colspan='3'><select name='vertailubu'><option value=''>",t("Ei tavoitevertailua"),"</option>";
			echo "<option value='asbu'   {$sel_asbu}>",t("Asiakastavoitteet"),"</option>";
			echo "<option value='asbuos' {$sel_asbuos}>",t("Asiakas-Osastotavoitteet"),"</option>";
			echo "<option value='asbury' {$sel_asbury}>",t("Asiakas-Tuoteryhm‰tavoitteet"),"</option>";
			echo "<option value='tubu' 	 {$sel_tubu}>",t("Tuotetavoitteet"),"</option>";
			echo "<option value='mybu' 	 {$sel_mybu}>",t("Myyj‰tavoitteet"),"</option>";
			echo "<option value='mybuos' {$sel_mybuos}>",t("Myyj‰-Osastotavoitteet"),"</option>";
			echo "<option value='mybury' {$sel_mybury}>",t("Myyj‰-Tuoteryhm‰tavoitteet"),"</option>";
			echo "</select></td>
			</tr>";

			echo "<tr>
			<th>",t("N‰yt‰ asiakask‰ynnit"),"</th>";
			echo "<td colspan='3'><input type='checkbox' name='asiakaskaynnit' {$asiakaskaynnitchk}></td>
			<td class='back'>".t("(Toimii vain jos listaat asiakkaittain)")."</td>
			</tr>";

			echo "<tr>";
			echo "<th>".t('N‰yt‰ tilauksen liitetiedostot')."</th>";
			echo "<td colspan='3'><input type='checkbox' name='liitetiedostot' {$liitetiedostotchk} /></td>";
			echo "<td class='back'>".t('(Toimii vain jos listaat tilauksittain)')."</td>";
			echo "</tr>";

			echo "</table><br>";

			// p‰iv‰m‰‰r‰rajaus
			echo "<table>";
			echo "<tr>
				<th>",t("Syˆt‰ alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)"),"</th>
				<td><input type='text' name='ppa' value='{$ppa}' size='3'></td>
				<td><input type='text' name='kka' value='{$kka}' size='3'></td>
				<td><input type='text' name='vva' value='{$vva}' size='5'></td>
				</tr>\n
				<tr><th>",t("Syˆt‰ loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)"),"</th>
				<td><input type='text' name='ppl' value='{$ppl}' size='3'></td>
				<td><input type='text' name='kkl' value='{$kkl}' size='3'></td>
				<td><input type='text' name='vvl' value='{$vvl}' size='5'></td>
				</tr>\n";
			echo "</table><br>";

			echo nayta_kyselyt("myyntiseuranta");

			echo "<br>";
			echo "<input type='submit' name='aja_raportti' value='",t("Aja raportti"),"'>";
			echo "</form><br><br>";
		}

		if ((isset($aja_raportti) or isset($valitse_asiakas)) and count($_REQUEST) > 0) {
			if (!function_exists("vararvo")) {
				function vararvo($tuoteno, $vv, $kk, $pp) {
					global $kukarow, $yhtiorow;

					$kehahin = 0;

					$query  = "	SELECT tuote.tuoteno, tuote.tuotemerkki, tuote.nimitys, tuote.kehahin, tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm, tuote.sarjanumeroseuranta
								FROM tuote
								WHERE tuote.yhtio 	= '{$kukarow['yhtio']}'
								and tuote.ei_saldoa = ''
								and tuote.tuoteno 	= '{$tuoteno}'";
					$result = pupe_query($query);
					$row = mysql_fetch_assoc($result);

					// Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksilˆiden ostohinnoista (ostetut yksilˆt jotka eiv‰t viel‰ ole myyty(=laskutettu))
					if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "U" or $row["sarjanumeroseuranta"] == "G") {
						$query	= "	SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
									FROM sarjanumeroseuranta
									LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
									LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
									WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
									and sarjanumeroseuranta.tuoteno = '{$row['tuoteno']}'
									and sarjanumeroseuranta.myyntirivitunnus != -1
									and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
									and tilausrivi_osto.laskutettuaika != '0000-00-00'";
						$sarjares = pupe_query($query);
						$sarjarow = mysql_fetch_assoc($sarjares);

						$kehahin = sprintf('%.2f', $sarjarow["kehahin"]);
					}
					else {
						$kehahin = sprintf('%.2f', $row["kehahin"]);
					}

					// tuotteen muutos varastossa annetun p‰iv‰n j‰lkeen
					$query = "	SELECT sum(kpl * if(laji in ('tulo','valmistus'), kplhinta, hinta)) muutoshinta, sum(kpl) muutoskpl
					 			FROM tapahtuma use index (yhtio_tuote_laadittu)
					 			WHERE yhtio = '{$kukarow['yhtio']}'
					 			and tuoteno = '{$row['tuoteno']}'
					 			and laadittu > '$vv-$kk-$pp 23:59:59'";
					$mres = pupe_query($query);
					$mrow = mysql_fetch_assoc($mres);

					// katotaan onko tuote ep‰kurantti nyt
					$kerroin = 1;

					if ($row['epakurantti25pvm'] != '0000-00-00') {
						$kerroin = 0.75;
					}
					if ($row['epakurantti50pvm'] != '0000-00-00') {
						$kerroin = 0.5;
					}
					if ($row['epakurantti75pvm'] != '0000-00-00') {
						$kerroin = 0.25;
					}
					if ($row['epakurantti100pvm'] != '0000-00-00') {
						$kerroin = 0;
					}

					// tuotteen m‰‰r‰ varastossa nyt
					$query = "	SELECT sum(saldo) varasto
								FROM tuotepaikat use index (tuote_index)
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tuoteno = '{$row['tuoteno']}'";
					$vres = pupe_query($query);
					$vrow = mysql_fetch_assoc($vres);

					// arvo historiassa: lasketaan (nykyinen varastonarvo) - muutoshinta
					$muutoshinta = ($vrow["varasto"] * $kehahin * $kerroin) - $mrow["muutoshinta"];

					// saldo historiassa: lasketaan nykyiset kpl - muutoskpl
					$muutoskpl = $vrow["varasto"] - $mrow["muutoskpl"];

					// haetaan tuotteen myydyt kappaleet
					$query  = "	SELECT ifnull(sum(kpl),0) kpl
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tyyppi = 'L'
								AND tuoteno = '{$row['tuoteno']}'
								AND laskutettuaika <= '{$vv}-{$kk}-{$pp}'
								AND laskutettuaika >= date_sub('{$vv}-{$kk}-{$pp}', INTERVAL 12 month)";
					$xmyyres = pupe_query($query);
					$xmyyrow = mysql_fetch_assoc($xmyyres);

					// haetaan tuotteen kulutetut kappaleet
					$query  = "	SELECT ifnull(sum(kpl),0) kpl
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tyyppi = 'V'
								AND tuoteno = '{$row['tuoteno']}'
								AND toimitettuaika <= '$vv-$kk-$pp'
								AND toimitettuaika >= date_sub('$vv-$kk-$pp', INTERVAL 12 month)";
					$xkulres = pupe_query($query);
					$xkulrow = mysql_fetch_assoc($xkulres);

					// lasketaan varaston kiertonopeus
					if ($muutoskpl > 0) {
						$kierto = round(($xmyyrow["kpl"] + $xkulrow["kpl"]) / $muutoskpl, 2);
					}
					else {
						$kierto = 0;
					}

					return array($muutoshinta, $kierto, $muutoskpl);
				}
			}

			//	Jos k‰ytt‰j‰ll‰ on valittu piirej‰ niin sallitaan vain ko. piirin/piirien hakeminen
			if ($kukarow["piirit"] != "")	 {
				$asiakasrajaus = "and lasku.piiri IN ({$kukarow['piirit']})";
			}
			else {
				$asiakasrajaus = "";
			}

			// tutkaillaan saadut muuttujat
			$ytunnus	= trim($ytunnus);
			$toimittaja	= trim($toimittaja);

			// hehe, n‰in on helpompi verrata p‰iv‰m‰‰ri‰
			$query  = "SELECT TO_DAYS('{$vvl}-{$kkl}-{$ppl}') - TO_DAYS('{$vva}-{$kka}-{$ppa}') ero";
			$result = pupe_query($query);
			$row    = mysql_fetch_assoc($result);

			if ($row["ero"] > 365 and $ajotapa != 'tilausauki') {
				echo "<font class='error'>",t("Jotta homma ei menisi liian hitaaksi, niin vuosi on pisin mahdollinen laskentav‰li!"),"</font><br>";
				$tee = "";
			}

			// haetaan tilauksista
			if ($ajotapa == 'tilaus') {
				$tila		= "'L'";
				$ouusio		= 'otunnus';
				$index		= 'yhtio_otunnus';
				$tyyppi		= "'L'";
			}
			elseif ($ajotapa == 'tilausjaauki' or $ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'tilausauki') {
				$tila		= "'L','N'";
				$ouusio		= 'otunnus';
				$index		= 'yhtio_otunnus';
				$tyyppi		= "'L'";
			}
			elseif ($ajotapa == 'ennakot') {
				$tila		= "'E'";
				$ouusio		= 'otunnus';
				$index		= 'yhtio_otunnus';
				$tyyppi		= "'E'";
			}
			// haetaan laskuista
			else {
				$tila		= "'U'";
				$ouusio		= 'uusiotunnus';
				$index		= 'uusiotunnus_index';
				$tyyppi		= "'L'";
			}

			// jos on joku toimittajajuttu valittuna, niin ei saa valita ku yhen yrityksen
			if ($toimittaja != "" or $mukaan == "toimittaja") {
				if (count($yhtiot) != 1) {
					echo "<font class='error'>",t("Toimittajahauissa voi valita vain yhden yrityksen"),"!</font><br>";
					$tee = "";
				}
			}

			// jos ei ole mit‰‰n yrityst‰ valittuna ei tehd‰ mit‰‰n
			if (count($yhtiot) == 0) {
				$tee = "";
			}
			else {
				$yhtio  = "";
				foreach ($yhtiot as $apukala) {
					$yhtio .= "'{$apukala}',";
				}
				$yhtio = substr($yhtio,0,-1);
			}

			// jos joku p‰iv‰kentt‰ on tyhj‰‰ ei tehd‰ mit‰‰n
			if ($ppa == "" or $kka == "" or $vva == "" or $ppl == "" or $kkl == "" or $vvl == "") {
				$tee = "";
			}

			if ($tee == 'go' and ($asiakasnro != '' or $ytunnus != '' or $toimittaja != '')) {
				$muutparametrit = "";

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
			}

			if ($tee == 'go' and ($asiakasnro != '' or $ytunnus != '')) {
				//$ytunnus = $asiakas;

				require ("inc/asiakashaku.inc");

				if ($asiakasnro != "") {
					$ytunnus = "";
				}
				else if ($ytunnus != "") {
					$asiakasnro = "";
				}

				if ($ytunnus != '') {
					$asiakas = $ytunnus;
				}
				else if ($asiakasnro != "") {
					// menn‰‰n ohi
				}
				else {
					$tee 		= "";
					$asiakasid 	= "";
				}
			}

			if ($tee == 'go' and $toimittaja != '') {
				$ytunnus = $toimittaja;

				require("inc/kevyt_toimittajahaku.inc");

				if ($ytunnus != '') {
					$toimittaja = $ytunnus;
					$ytunnus = '';
				}
				else {
					$tee 			= "";
					$toimittajaid 	= "";
				}
			}

			if ($tee == 'go') {

				$query_ale_lisa = generoi_alekentta('M');

				// HUOM: ", " (pilkku-space) stringi‰ k‰ytet‰‰n vain sarakkeiden v‰lill‰, eli ole tarkkana concatissa ja muissa funkkareissa $select-muuttujassa
				$select 			= "";
				$query  			= "";
				$group  			= "";
				$order  			= "";
				$gluku  			= 0;
				$varasto_join 		= "";
				$kantaasiakas_join 	= "";

				// n‰it‰ k‰ytet‰‰n queryss‰
				$sel_osasto = "";
				$sel_tuoteryhma = "";

				$apu = array();

				if (count($yhtiot) > 1) {
					$group  .= ",lasku.yhtio";
					$select .= "lasku.yhtio yhtio, ";
					$order  .= "lasku.yhtio,";
					$gluku++;
				}

				// Sortataan grouppaukset k‰ytt‰j‰n antamaan prioj‰rjestykseen
				foreach ($jarjestys as $ind => $arvo) {
					if (trim($arvo) != "") $apu[] = $arvo;
				}

				if (count($apu) > 0) {
					asort($jarjestys);
				}

				$apu = array();

				foreach ($jarjestys as $i => $arvo) {
					if ($ruksit[$i] != "") {
						$apu[$i] = $ruksit[$i];
					}
				}

				// Pidet‰‰n lukua mink‰ mukaan groupataan, jotta osataan liitt‰‰ tavoitteet mukaan
				$asiakasgroups = 0;
				$tuotegroups   = 0;
				$turyhgroups   = 0;
				$tuosagroups   = 0;
				$laskugroups   = 0;
				$muutgroups    = 0;
				$myyjagroups   = 0;

				// K‰yd‰‰n l‰pi k‰ytt‰j‰n syˆtt‰m‰t grouppaukset
				foreach ($apu as $i => $mukaan) {

					//** Asiakasgrouppaukset start **//
					if ($mukaan == "asiakasosasto") {
						$group .= ",asiakas.osasto";
						$select .= "asiakas.osasto 'asiakasosasto', ";
						if (strpos($select, "'asiakaslista',") === FALSE) $select .= "group_concat(DISTINCT asiakas.tunnus) 'asiakaslista', ";
						$order  .= "asiakas.osasto,";
						$gluku++;
						$asiakasgroups++;
					}

					if ($mukaan == "asiakasryhma") {
						$group .= ",asiakas.ryhma";
						$select .= "asiakas.ryhma 'asiakasryhm‰', ";
						if (strpos($select, "'asiakaslista',") === FALSE) $select .= "group_concat(DISTINCT asiakas.tunnus) 'asiakaslista', ";
						$order  .= "asiakas.ryhma,";
						$gluku++;
						$asiakasgroups++;
					}

					if ($mukaan == "asiakaspiiri") {
						if ($piirivalinta == "asiakas") {
							$group .= ",asiakas.piiri";
							$select .= "asiakas.piiri 'asiakaspiiri', ";
							if (strpos($select, "'asiakaslista',") === FALSE) $select .= "group_concat(DISTINCT asiakas.tunnus) 'asiakaslista', ";
							$order  .= "asiakas.piiri,";
							$gluku++;
						}

						if ($piirivalinta == "lasku") {
							$group .= ",lasku.piiri";
							$select .= "lasku.piiri 'asiakaspiiri', ";
							if (strpos($select, "'asiakaslista',") === FALSE) $select .= "group_concat(DISTINCT asiakas.tunnus) 'asiakaslista', ";
							$order  .= "lasku.piiri,";
							$gluku++;
						}
						$asiakasgroups++;
					}

					if ($mukaan == "asiakasmyyja") {
						$group .= ",asiakas.myyjanro";
						$select .= "asiakas.myyjanro 'asiakasmyyj‰', ";
						if (strpos($select, "'asiakaslista',") === FALSE) $select .= "group_concat(DISTINCT asiakas.tunnus) 'asiakaslista', ";
						$order  .= "asiakas.myyjanro,";
						$gluku++;
						$asiakasgroups++;
						$myyjagroups++;
					}

					if ($mukaan == "ytunnus") {
						$group  .= ",asiakas.tunnus";

						if ($osoitetarrat != "" or $asiakaskaynnit != "") $select .= "asiakas.tunnus astunnus, ";

						if ($ytunnus_mistatiedot != "") {
							$etuliite = "lasku";
						}
						else {
							$etuliite = "asiakas";
						}

						if (isset($ytun_laajattied) and $ytun_laajattied != "") {
							$select .= "{$etuliite}.ytunnus, {$etuliite}.toim_ovttunnus, concat_ws('<br>',concat_ws(' ',{$etuliite}.nimi,{$etuliite}.nimitark)) nimi, {$etuliite}.osoite, {$etuliite}.postino, {$etuliite}.postitp, {$etuliite}.maa, ";
							$select .= "if({$etuliite}.toim_nimi!='' and {$etuliite}.nimi!={$etuliite}.toim_nimi, concat_ws('<br>',concat_ws(' ',{$etuliite}.toim_nimi,{$etuliite}.toim_nimitark)), concat_ws('<br>',concat_ws(' ',{$etuliite}.nimi,{$etuliite}.nimitark))) toim_nimi, ";
							$select .= "if({$etuliite}.toim_nimi!='' and {$etuliite}.nimi!={$etuliite}.toim_nimi, {$etuliite}.toim_osoite, {$etuliite}.osoite) toim_osoite, ";
							$select .= "if({$etuliite}.toim_nimi!='' and {$etuliite}.nimi!={$etuliite}.toim_nimi, {$etuliite}.toim_postino, {$etuliite}.postino) toim_postino, ";
							$select .= "if({$etuliite}.toim_nimi!='' and {$etuliite}.nimi!={$etuliite}.toim_nimi, {$etuliite}.toim_postitp, {$etuliite}.postitp) toim_postitp, ";
							$select .= "if({$etuliite}.toim_nimi!='' and {$etuliite}.nimi!={$etuliite}.toim_nimi, {$etuliite}.toim_maa, {$etuliite}.maa) toim_maa, ";
							$select .= "if(asiakas.puhelin!='', asiakas.puhelin, asiakas.gsm) puhelin, ";
							$select .= "asiakas.email, ";
						}
						else {
							$select .= "{$etuliite}.ytunnus, {$etuliite}.toim_ovttunnus, concat_ws('<br>',concat_ws(' ',{$etuliite}.nimi,{$etuliite}.nimitark),if({$etuliite}.toim_nimi!='' and {$etuliite}.nimi!={$etuliite}.toim_nimi,concat_ws(' ',{$etuliite}.toim_nimi,{$etuliite}.toim_nimitark),NULL)) nimi, concat_ws('<br>',{$etuliite}.postitp,if({$etuliite}.toim_postitp!='' and {$etuliite}.postitp!={$etuliite}.toim_postitp,{$etuliite}.toim_postitp,NULL)) postitp, ";
						}
						if (strpos($select, "'asiakaslista',") === FALSE) $select .= "asiakas.tunnus 'asiakaslista', ";
						$order  .= "{$etuliite}.ytunnus,";
						$gluku++;
					}

					if ($mukaan == "asiakasnro") {
						$group .= ",asiakas.tunnus";
						$select .= "asiakas.asiakasnro, concat_ws('<br>',concat_ws(' ',asiakas.nimi,asiakas.nimitark),if(asiakas.toim_nimi!='' and asiakas.nimi!=asiakas.toim_nimi,concat_ws(' ',asiakas.toim_nimi,asiakas.toim_nimitark),NULL)) 'asiakasnro.nimi', concat_ws('<br>',asiakas.postitp,if(asiakas.toim_postitp!='' and asiakas.postitp!=asiakas.toim_postitp,asiakas.toim_postitp,NULL)) 'asiakasnro.postitp', ";
						if (strpos($select, "'asiakaslista',") === FALSE) $select .= "asiakas.tunnus 'asiakaslista', ";
						$order  .= "asiakas.asiakasnro,";
						$gluku++;
						$asiakasgroups++;
					}

					if ($mukaan == "konserni") {
						$group .= ",asiakas.konserni";
						$select .= "asiakas.konserni, ";
						if (strpos($select, "'asiakaslista',") === FALSE) $select .= "group_concat(DISTINCT asiakas.tunnus) 'asiakaslista', ";
						$order  .= "asiakas.konserni,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and asiakas.konserni = '{$rajaus[$i]}' ";
						}
						$asiakasgroups++;
					}

					if (strtolower(substr($mukaan, 0, 18)) == "dynaaminen_asiakas") {
						// HUOMHUOM: Myynnit summautuu kun asiakas kuuluu useampaan segmenttiin
						$dyna_ms_luku = substr($mukaan, -1);
						$mukaan_join  = substr($mukaan, 0, -1).$dynaaminen_syvintaso["asiakas"];

						$group .= ",{$mukaan}";
						$select .= "{$mukaan_join}.tunnus {$mukaan}, ";
						if (strpos($select, "'asiakaslista',") === FALSE) $select .= "group_concat(DISTINCT asiakas.tunnus) 'asiakaslista', ";
						$order  .= "{$mukaan},";
						$gluku++;
						$asiakasgroups++;
					}
					//** Asiakasgrouppaukset loppu **//

					//** Tuotegrouppaukset start **//
					if ($mukaan == "osasto") {
						$group .= ",tuote.osasto";
						$select .= "tuote.osasto 'tuoteosasto', ";
						if (strpos($select, "'tuotelista',") === FALSE) $select .= "group_concat(DISTINCT concat('\'',tuote.tuoteno,'\'')) 'tuotelista', ";
						$order  .= "tuote.osasto,";
						$gluku++;
						$tuosagroups++;
					}

					if ($mukaan == "try") {
						$group .= ",tuote.try";
						$select .= "tuote.try 'tuoteryhm‰', ";
						if (strpos($select, "'tuotelista',") === FALSE) $select .= "group_concat(DISTINCT concat('\'',tuote.tuoteno,'\'')) 'tuotelista', ";
						$order  .= "tuote.try,";
						$gluku++;
						$turyhgroups++;
					}

					if ($mukaan == "tuotemerkki") {
						$group .= ",tuote.tuotemerkki";
						$select .= "tuote.tuotemerkki 'tuotemerkki', ";
						if (strpos($select, "'tuotelista',") === FALSE) $select .= "group_concat(DISTINCT concat('\'',tuote.tuoteno,'\'')) 'tuotelista', ";
						$order  .= "tuote.tuotemerkki,";
						$gluku++;
						$tuotegroups++;
					}

					if ($mukaan == "malli") {
						$group .= ",tuote.malli";
						$select .= "tuote.malli 'malli', ";
						if (strpos($select, "'tuotelista',") === FALSE) $select .= "group_concat(DISTINCT concat('\'',tuote.tuoteno,'\'')) 'tuotelista', ";
						$order  .= "tuote.malli,";
						$gluku++;
						$tuotegroups++;
					}

					if ($mukaan == "mallitarkenne") {
						$group .= ",tuote.mallitarkenne";
						$select .= "tuote.mallitarkenne 'mallitarkenne', ";
						if (strpos($select, "'tuotelista',") === FALSE) $select .= "group_concat(DISTINCT concat('\'',tuote.tuoteno,'\'')) 'tuotelista', ";
						$order  .= "tuote.mallitarkenne,";
						$gluku++;
						$tuotegroups++;
					}

					if ($mukaan == "tuotemyyja") {
						$group .= ",tuote.myyjanro";
						$select .= "tuote.myyjanro 'tuotemyyj‰', ";
						if (strpos($select, "'tuotelista',") === FALSE) $select .= "group_concat(DISTINCT concat('\'',tuote.tuoteno,'\'')) 'tuotelista', ";
						$order  .= "tuote.myyjanro,";
						$gluku++;
						$myyjagroups++;
					}

					if ($mukaan == "tuoteostaja") {
						$group .= ",tuote.ostajanro";
						$select .= "tuote.ostajanro 'tuoteostaja', ";
						if (strpos($select, "'tuotelista',") === FALSE) $select .= "group_concat(DISTINCT concat('\'',tuote.tuoteno,'\'')) 'tuotelista', ";
						$order  .= "tuote.ostajanro,";
						$gluku++;
						$tuotegroups++;
					}

					if ($mukaan == "tuote") {
						if ($nimitykset == "") {
							$group .= ",tuote.tuoteno";
							$select .= "tuote.tuoteno tuoteno, ";
							if (strpos($select, "'tuotelista',") === FALSE) $select .= "concat('\'',tuote.tuoteno,'\'') 'tuotelista', ";
							$order  .= "tuote.tuoteno,";
							$gluku++;
						}
						else {
							$group .= ",tuote.tuoteno, tuote.nimitys";
							$select .= "tuote.tuoteno tuoteno, tuote.nimitys nimitys, ";
							if (strpos($select, "'tuotelista',") === FALSE) $select .= "concat('\'',tuote.tuoteno,'\'') 'tuotelista', ";
							$order  .= "tuote.tuoteno,";
							$gluku++;
						}

						if ($varastonarvo != '') {
							$select .= "0 varastonarvo, 0 kierto, 0 varastonkpl, ";
						}

						if ($rajaus[$i] != "") {
							$lisa .= " and tuote.tuoteno='{$rajaus[$i]}' ";
						}
						$tuotegroups++;
					}

					if ($mukaan == "toimittaja") {
						$group .= ",toimittaja";
						$select .= "(select group_concat(distinct tuotteen_toimittajat.liitostunnus) from tuotteen_toimittajat use index (yhtio_tuoteno) where tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno) toimittaja, ";
						if (strpos($select, "'tuotelista',") === FALSE) $select .= "group_concat(DISTINCT concat('\'',tuote.tuoteno,'\'')) 'tuotelista', ";
						$order  .= "toimittaja,";
						$gluku++;
						$tuotegroups++;
					}

					if (strtolower(substr($mukaan, 0, 16)) == "dynaaminen_tuote") {
						// HUOMHUOM: Myynnit summautuu kun tuote kuuluu useampaan segmenttiin
						$dyna_ms_luku = substr($mukaan, -1);
						$mukaan_join  = substr($mukaan, 0, -1).$dynaaminen_syvintaso["tuote"];

						$group .= ",{$mukaan}";
						$select .= "{$mukaan_join}.tunnus {$mukaan}, ";
						if (strpos($select, "'tuotelista',") === FALSE) $select .= "group_concat(DISTINCT concat('\'',tuote.tuoteno,'\'')) 'tuotelista', ";
						$order  .= "{$mukaan},";
						$gluku++;
						$tuotegroups++;
					}
					//** Tuotegrouppaukset loppu **//
					//** Laskugrouppaukset start **//
					if ($mukaan == "laskumyyja") {
						$group .= ",lasku.myyja";
						$select .= "lasku.myyja 'myyj‰', ";
						$order  .= "lasku.myyja,";
						$gluku++;
						$myyjagroups++;
					}

					if ($mukaan == "maa") {
						$group .= ",lasku.maa";
						$select .= "lasku.maa maa, ";
						$order  .= "lasku.maa,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and lasku.maa='{$rajaus[$i]}' ";
						}
						$laskugroups++;
					}

					if ($mukaan == "tilaustyyppi") {
						$group .= ",lasku.clearing";
						$select .= "lasku.clearing tilaustyyppi, ";
						$order  .= "lasku.clearing,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and lasku.clearing='{$rajaus[$i]}' ";
						}
						$laskugroups++;
					}

					if ($mukaan == "laskuittain") {
						$group .= ",lasku.tunnus";
						$select .= "if(lasku.laskunro>0,concat('".t("LASKU").":',lasku.laskunro),concat('".t("TILAUS").":',lasku.tunnus)) laskunumero, ";
						$order  .= "laskunumero,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and lasku.laskunro = '{$rajaus[$i]}' ";
						}

						if ($laskutuspaiva != "") $select .= "lasku.tapvm laskutuspvm, ";

						$laskugroups++;
					}
					//** Laskugrouppaukset loppu **//

					if ($mukaan == "toimipaikka") {
						$group .= ",lasku.yhtio_toimipaikka";
						$select .= "lasku.yhtio_toimipaikka, ";
						$order  .= "lasku.yhtio_toimipaikka,";
						$gluku++;
						$muutgroups++;
					}
					//** Asiakas_ja_tai_tuote grouppaukset start **//
					if ($mukaan == "kustp") {
						$group .= ",kustannuspaikka";

						if (!isset($kustapvalinta) or $kustapvalinta == 'tuote') {
							$select .= "tuote.kustp as kustannuspaikka, ";
						}
						else {
							$select .= "asiakas.kustannuspaikka as kustannuspaikka, ";
						}

						$order  .= "kustannuspaikka,";
						$gluku++;
						$muutgroups++;
					}

					if ($mukaan == "kohde") {
						$group .= ",kohde";

						if (!isset($kohdevalinta) or $kohdevalinta == 'tuote') {
							$select .= "tuote.kohde as kohde, ";
						}
						else {
							$select .= "asiakas.kohde as kohde, ";
						}

						$order  .= "kohde,";
						$gluku++;
						$muutgroups++;
					}

					if ($mukaan == "projekti") {
						$group .= ",projekti";

						if (!isset($projektivalinta) or $projektivalinta == 'tuote') {
							$select .= "tuote.projekti as projekti, ";
						}
						else {
							$select .= "asiakas.projekti as projekti, ";
						}

						$order  .= "projekti,";
						$gluku++;
						$muutgroups++;
					}
					//** Asiakas_ja_tai_tuote grouppaukset loppu **//

					//**  Varastogrouppaukset start **//
					if ($mukaan == "varastoittain") {
						$group .= ",varastopaikat.nimitys";
						$select .= "varastopaikat.nimitys Varasto, ";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and varastopaikat.nimitys = '{$rajaus[$i]}' ";
						}

						$varasto_join = "LEFT JOIN varastopaikat ON varastopaikat.yhtio = tilausrivi.yhtio
										and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))
										and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'))";
						$muutgroups++;
					}
					//**  Varastogrouppaukset loppu **//

					//**  Avainsanagrouppaukset start **//
					if ($ajotapa != "lasku" and $mukaan == "kantaasiakkaittain") {
						$group .= ",kantaasiakas.avainsana";
						$select .= "kantaasiakas.avainsana Kantaasiakastunnus, ";
						$order  .= "kantaasiakas.avainsana,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and kantaasiakas.avainsana = '{$rajaus[$i]}' ";
						}

						$kantaasiakas_join	= "JOIN laskun_lisatiedot lasklisa ON (lasklisa.yhtio = lasku.yhtio AND lasklisa.otunnus = lasku.tunnus)\n";
						$kantaasiakas_join  .= "JOIN asiakkaan_avainsanat kantaasiakas ON (kantaasiakas.yhtio = lasku.yhtio AND kantaasiakas.laji = 'kantaasiakastunnus' AND kantaasiakas.liitostunnus = lasku.liitostunnus AND kantaasiakas.avainsana = lasklisa.kantaasiakastunnus)\n";
						$muutgroups++;
					}
					//**  Avainsanagrouppaukset loppu **//

					//**  Maksuehtogrouppaukset start **//
					if ($mukaan == "maksuehdoittain") {
						$group  .= ",lasku.maksuteksti";
						$select .= "lasku.maksuteksti maksuehto, ";
						$order  .= "lasku.maksuteksti,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and lasku.maksuteksti='{$rajaus[$i]}' ";
						}
					}
					//**  Maksuehtogrouppaukset loppu **//

					//**  Asiakkaan_tilausnumeroittain start **//
					if ($mukaan == "asiakkaan_tilausnumeroittain") {
						$group .= ",lasku.asiakkaan_tilausnumero";
						$select .= "lasku.asiakkaan_tilausnumero asiakkaan_tilausnumero, ";
						$order  .= "asiakkaan_tilausnumero,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and lasku.asiakkaan_tilausnumero = '{$rajaus[$i]}' ";
						}
					}
					//**  Asiakkaan_tilausnumeroittain loppu **//

					//**  Tilauksittain start **//
					if ($mukaan == "tilauksittain") {
						$group .= ",lasku.tunnus";
						$select .= "lasku.tunnus tilausnumero, ";
						$order  .= "tilausnumero,";
						$gluku++;

						if ($rajaus[$i] != "") {
							$lisa .= " and lasku.tunnus IN ({$rajaus[$i]}) ";
						}

						if ($laskutuspaiva != "") $select .= "lasku.tapvm laskutuspvm, ";
					}
					//**  Tilauksittain loppu **//
				}

				// N‰ytet‰‰n tilausrivin kommentit ja groupataan tilausriveitt‰in
				if ($tilrivikomm != "") {
					$group .= ",tilausrivi.tunnus";
					$select .= "tilausrivi.kommentti, ";
					$gluku++;
					$muutgroups++;
				}

				if ($naytamaksupvm != "") {
					$group .= ",lasku.mapvm";
					$select .= "lasku.mapvm maksupvm, ";
					$gluku++;
					$muutgroups++;
				}

				if ($naytatoimtuoteno != "") {
					$group .= ",toim_tuoteno";
					$select .= "(SELECT tuotteen_toimittajat.toim_tuoteno
								FROM tuotteen_toimittajat use index (yhtio_tuoteno)
								WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio
								AND tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno
								ORDER BY IF(jarjestys = 0, 999, jarjestys)
								LIMIT 1) toim_tuoteno, ";
					$order  .= "toim_tuoteno,";
					$gluku++;
				}

				// N‰ytet‰‰n sarjanumerot
				if ($sarjanumerot != '') {
					$select .= "group_concat(concat(tilausrivi.tunnus,'#',tilausrivi.kpl)) sarjanumero, ";
				}

				if ($order != "") {
					$order = substr($order,0,-1);
				}
				else {
					$order = "1";
				}

				if ($toimittaja != "") {
					$query = "	SELECT group_concat(concat('\'',tuoteno,'\'')) tuotteet
								FROM tuotteen_toimittajat
								WHERE yhtio IN ({$yhtio})
								AND liitostunnus = '{$toimittajaid}'";
					$result = pupe_query($query);
					$toimirow = mysql_fetch_assoc($result);

					if ($toimirow["tuotteet"] != '') {
						$lisa .= " and tilausrivi.tuoteno in ({$toimirow['tuotteet']})";
					}
					else {
						echo "<font class='error'>",t("Toimittajan")," {$toimittaja} ",t("tuotteita ei lˆytynyt"),"!</font><br><br>";
						$tee = "";
					}
				}

				if ($asiakas != "") {
					$query = "	SELECT group_concat(tunnus) asiakkaat
								FROM asiakas
								WHERE yhtio IN ({$yhtio})
								AND ytunnus = '{$asiakas}'
								{$asiakasrajaus}";
					$result = pupe_query($query);
					$asiakasrow = mysql_fetch_assoc($result);

					if (trim($asiakasrow["asiakkaat"]) != "") {
						$lisa .= " and lasku.liitostunnus in ({$asiakasrow['asiakkaat']}) ";
					}
					else {
						echo "<font class='error'>",t("Asiakasta")," {$asiakas} ",t("ei lˆytynyt"),"!</font><br><br>";
						$tee = '';
					}
				}
				elseif ($asiakasnro != "") {
					$query = "	SELECT group_concat(tunnus) asiakkaat
								FROM asiakas
								WHERE yhtio IN ({$yhtio})
								AND asiakasnro = '{$asiakasnro}'
								{$asiakasrajaus}";
					$result = pupe_query($query);
					$asiakasrow = mysql_fetch_assoc($result);

					if (trim($asiakasrow["asiakkaat"]) != "") {
						$lisa .= " and lasku.liitostunnus in ({$asiakasrow['asiakkaat']}) ";
					}
					else {
						echo "<font class='error'>",t("Asiakasta")," {$asiakasnro} ",t("ei lˆytynyt"),"!</font><br><br>";
						$tee = '';
					}
				}

				if (isset($tuotteet_lista) and $tuotteet_lista != '') {
					$tuotteet = explode("\n", $tuotteet_lista);
					$tuoterajaus = "";
					foreach ($tuotteet as $tuote) {
						if (trim($tuote) != '') {
							$tuoterajaus .= "'".trim($tuote)."',";
						}
					}

					if ($tuoterajaus != "") {
						$lisa .= "and tuote.tuoteno in (".substr($tuoterajaus, 0, -1).") ";
					}
				}

				if (isset($status) and $status != '') {
					$lisa .= " and tuote.status = '".(string) $status."' ";
				}

				if (isset($verkkokaupat) and $verkkokaupat != '') {
					$lisa .= " and lasku.ohjelma_moduli = '$verkkokaupat' ";
				}

				$vvaa = $vva - '1';
				$vvll = $vvl - '1';

				if ($kateprossat != "") {
					$katelisanyt = " 0 kateprosnyt, ";
					$katelisaed  = " 0 kateprosed, ";
				}
				else {
					$katelisanyt = "";
					$katelisaed  = "";
				}

				if ($nettokateprossat != "") {
					$nettokatelisanyt = " 0 nettokateprosnyt, ";
					$nettokatelisaed  = " 0 nettokateprosed, ";
				}
				else {
					$nettokatelisanyt = "";
					$nettokatelisaed  = "";
				}

				if ($asiakaskaynnit != "") {
					$select .= "(SELECT count(*) kaynnit
								FROM kalenteri
								WHERE kalenteri.yhtio 		= asiakas.yhtio
								AND kalenteri.liitostunnus 	= asiakas.tunnus
								and kalenteri.tapa 	 	 	= 'Asiakask‰ynti'
								and kalenteri.tyyppi	   in ('kalenteri','memo')
								and ((kalenteri.pvmalku  >= '{$vva}-{$kka}-{$ppa} 00:00:00' and kalenteri.pvmalku  <= '{$vvl}-{$kkl}-{$ppl} 23:59:59') or
									 (kalenteri.pvmloppu >= '{$vva}-{$kka}-{$ppa} 00:00:00' and kalenteri.pvmloppu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59') or
									 (kalenteri.pvmalku  <= '{$vva}-{$kka}-{$ppa} 00:00:00' and kalenteri.pvmloppu >= '{$vvl}-{$kkl}-{$ppl} 23:59:59'))) asiakaskaynnit,";
				}

				if ($eiOstSarjanumeroita != "") {
					$lisatiedot_join = " JOIN tilausrivin_lisatiedot use index (tilausrivitunnus) ON tilausrivin_lisatiedot.yhtio=lasku.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus and tilausrivin_lisatiedot.osto_vai_hyvitys!='O'\n";
				}
				else {
					$lisatiedot_join = "";
				}

				// Jos ei olla valittu mit‰‰n
				if ($group == "") {
					$select = "tuote.yhtio, ";
					$group  = "lasku.yhtio";
				}
				else {
					$group  = substr($group,1);
				}

				if ($ajotapanlisa == "erikseen") {
					$tilauslisa3 = ", if(tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt>0,'Veloitus','Hyvitys') rivityyppi";
					$group .= ",rivityyppi";
					$muutgroups++;
				}
				else {
					$tilauslisa3 = "";
				}

				// Onnistuuko tavoitevertailu
				if ($vertailubu == "asbu" or $vertailubu == "asbury" or $vertailubu == "asbuos") {
					// N‰ytet‰‰n asiakastavoitteet:

					// ei voi groupata muiden kuin asiakkaiden tietojen mukaan
					if ($tuotegroups > 0 or $laskugroups > 0 or $muutgroups > 0 or $myyjagroups > 0) {
						echo "<font class='error'>".t("VIRHE: Muita kuin asiakaaseen liittyvi‰ ryhmittelyj‰ ei voida valita kun n‰ytet‰‰n asiakastavoitteet")."!</font><br>";
						$tee = '';
					}

					// ei voi groupata muiden kuin asiakkaiden tietojen mukaan (paitsi tuoteryhm‰n mukaan kun valitaan asbury)
					if ($vertailubu == "asbu" and ($turyhgroups > 0 or $tuosagroups > 0)) {
						echo "<font class='error'>".t("VIRHE: Muita kuin asiakaaseen liittyvi‰ ryhmittelyj‰ ei voida valita kun n‰ytet‰‰n asiakastavoitteet")."!</font><br>";
						$tee = '';
					}

					// eik‰ rajata muiden kuin aiakkaan tietojen mukaan (t‰ss‰ on kaikki joinit ja wheren ehdot)
					if (preg_match("/JOIN (tilausrivin_lisatiedot|asiakkaan_avainsanat|laskun_lisatiedot|varastopaikat)/i", $lisatiedot_join.$varasto_join.$kantaasiakas_join.$lisa_parametri) or $lisa_dynaaminen["tuote"] != '') {
						echo "<font class='error'>".t("VIRHE: Muita kuin asiakaaseen liittyvi‰ JOINeja ei voida valita kun n‰ytet‰‰n asiakastavoitteet")."!</font><br>";
						$tee = '';
					}

					if (preg_match("/AND ?(tilausrivin_lisatiedot\.|kantaasiakas\.|lasklisa\.|varastopaikat\.|tilausrivi\.|tuote\.|toimitustapa\.)/i", $asiakasrajaus.$lisa)) {
						echo "<font class='error'>".t("VIRHE: Muita kuin asiakaaseen liittyvi‰ rajauksia ei voida valita kun n‰ytet‰‰n asiakastavoitteet")."!</font><br>";
						$tee = '';
					}
				}

				if ($vertailubu == "tubu") {
					// N‰ytet‰‰n tuotetavoitteet:

					//siin‰ tapauksessa ei voi groupata muiden kuin asiakkaiden ja/tai tuoteryhm‰n tietojen mukaan
					if ($asiakasgroups > 0 or $laskugroups > 0 or $muutgroups > 0 or $myyjagroups > 0) {
						echo "<font class='error'>".t("VIRHE: Muita kuin tuotteisiin liittyvi‰ ryhmittelyj‰ ei voida valita kun n‰ytet‰‰n tuotetavoitteet")."!</font><br>";
						$tee = '';
					}

					// eik‰ rajata muiden kuin tuotteen tietojen mukaan (t‰ss‰ on kaikki joinit ja wheren ehdot)
					if (preg_match("/JOIN (tilausrivin_lisatiedot|asiakkaan_avainsanat|laskun_lisatiedot|varastopaikat)/i", $lisatiedot_join.$varasto_join.$kantaasiakas_join.$lisa_parametri) or $lisa_dynaaminen["asiakas"] != '') {
						echo "<font class='error'>".t("VIRHE: Muita kuin tuotteisiin liittyvi‰ JOINeja ei voida valita kun n‰ytet‰‰n tuotetavoitteet")."!</font><br>";
						$tee = '';
					}

					if (preg_match("/AND ?(tilausrivin_lisatiedot\.|kantaasiakas\.|lasklisa\.|varastopaikat\.|tilausrivi\.|asiakas\.|toimitustapa\.)/i", $asiakasrajaus.$lisa)) {
						echo "<font class='error'>".t("VIRHE: Muita kuin tuotteisiin liittyvi‰ rajauksia ei voida valita kun n‰ytet‰‰n tuotetavoitteet")."!</font><br>";
						$tee = '';
					}
				}

				if ($vertailubu == "mybu" or $vertailubu == "mybury" or $vertailubu == "mybuos") {
					// N‰ytet‰‰n myyj‰tavoitteet:

					//siin‰ tapauksessa ei voi groupata muiden kuin myyjien mukaan
					if ($asiakasgroups > 0 or $tuotegroups > 0 or $laskugroups > 0 or $muutgroups > 0) {
						echo "<font class='error'>".t("VIRHE: Muita kuin myyjiin liittyvi‰ ryhmittelyj‰ ei voida valita kun n‰ytet‰‰n myyj‰tavoitteet")."!</font><br>";
						$tee = '';
					}

					// ei voi groupata muiden kuin myyjien tietojen mukaan (paitsi tuoteryhm‰n mukaan kun valitaan mybury)
					if ($vertailubu == "mybu" and ($turyhgroups > 0 or $tuosagroups > 0)) {
						echo "<font class='error'>".t("VIRHE: Muita kuin myyjiin liittyvi‰ ryhmittelyj‰ ei voida valita kun n‰ytet‰‰n myyj‰tavoitteet")."!</font><br>";
						$tee = '';
					}

					// siin‰ tapauksessa ei voi groupata muiden kuin myyjien mukaan
					if ($myyjagroups > 1) {
						echo "<font class='error'>".t("VIRHE: Valitse korjeintaan yksi myyjiin liittyv‰ ryhmittely")."!</font><br>";
						$tee = '';
					}
				}

				if ($naytakaikkituotteet == "") {
					$lisa .= " and tuote.myynninseuranta = '' ";
				}

				if ($naytakaikkiasiakkaat == "") {
					$asiakaslisa = " and asiakas.myynninseuranta = '' ";
				}

				if ($naytaennakko == "") {
					$lisa .= " and tilausrivi.tuoteno != '{$yhtiorow['ennakkomaksu_tuotenumero']}' ";
				}

				if ($tee == 'go') {
					$query = "	SELECT {$select}";

					// Katotaan mist‰ kohtaa query‰ alkaa varsinaiset numerosarakkeet
					//(HUOM: ", " (pilkku-space) stringi‰ k‰ytet‰‰n vain sarakkeiden v‰lill‰, eli ole tarkkana concatissa ja muissa funkkareissa $select-muuttujassa)
					$data_start_index = substr_count($select, ", ");

					// generoidaan selectit
					if ($kuukausittain != "") {
						$MONTH_ARRAY  	= array(1=> t('Tammikuu'),t('Helmikuu'),t('Maaliskuu'),t('Huhtikuu'),t('Toukokuu'),t('Kes‰kuu'),t('Hein‰kuu'),t('Elokuu'),t('Syyskuu'),t('Lokakuu'),t('Marraskuu'),t('Joulukuu'));

						$startmonth	= date("Ymd",mktime(0, 0, 0, $kka, 1,  $vva));
						$endmonth 	= date("Ymd",mktime(0, 0, 0, $kkl, 1,  $vvl));

						for ($i = $startmonth;  $i <= $endmonth;) {

							$alku  = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)));
							$loppu = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), date("t", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4))),  substr($i,0,4)));

							$alku_ed  = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)-1));
							$loppu_ed = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), date("t", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4))),  substr($i,0,4)-1));

							// MYYNTI
							if ($ajotapa == 'tilausjaauki' or $ajotapa == 'tilausauki') {
								$query .= " sum(if(lasku.luontiaika >= '{$alku} 00:00:00' and lasku.luontiaika <= '{$loppu} 23:59:59' and tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) '".substr($i,0,4).substr($i,4,2)."_laskuttamatta', ";
							}

							if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
								$query .= " sum(if(lasku.luontiaika >= '{$alku} 00:00:00' and lasku.luontiaika <= '{$loppu} 23:59:59', if(tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, tilausrivi.rivihinta), 0)) '".substr($i,0,4).substr($i,4,2)."_myynti', ";
							}
							elseif($ajotapa != 'tilausauki') {
								$query .= " sum(if(tilausrivi.laskutettuaika >= '{$alku}' and tilausrivi.laskutettuaika <= '{$loppu}', tilausrivi.rivihinta, 0)) '".substr($i,0,4).substr($i,4,2)."_myynti', ";
							}

							if ($vertailubu != "") {
								$query .= " sum(0) '".substr($i,0,4).substr($i,4,2)."_tavoitenyt', ";
								$query .= " sum(0) '".substr($i,0,4).substr($i,4,2)."_tavoiteindnyt', ";
							}

							if ($piilota_kappaleet == "") {
								$query .= " sum(if(tilausrivi.laskutettuaika >= '{$alku}' and tilausrivi.laskutettuaika <= '{$loppu}', tilausrivi.kpl,0)) '".substr($i,0,4).substr($i,4,2)."_kpl', ";
							}

							if ($ajotapa == 'tilausjaauki') {
								$query .= " sum(if(lasku.luontiaika >= '{$alku} 00:00:00' and lasku.luontiaika <= '{$loppu} 23:59:59' and tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) +
											sum(if(tilausrivi.laskutettuaika >= '{$alku}' and tilausrivi.laskutettuaika <= '{$loppu}', tilausrivi.rivihinta, 0)) '".substr($i,0,4).substr($i,4,2)."_myyntiyht', ";
							}

							if ($piiloed == "") {
								// MYYNTIED
								if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
									$query .= " sum(if(lasku.luontiaika >= '{$alku_ed} 00:00:00' and lasku.luontiaika <= '{$loppu_ed} 23:59:59', if(tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, tilausrivi.rivihinta), 0)) '".(substr($i,0,4)-1).substr($i,4,2)."_myynti', ";
								}
								elseif ($ajotapa != 'tilausauki')  {
									$query .= " sum(if(tilausrivi.laskutettuaika >= '{$alku_ed}' and tilausrivi.laskutettuaika <= '{$loppu_ed}', tilausrivi.rivihinta, 0)) '".(substr($i,0,4)-1).substr($i,4,2)."_myynti', ";
								}

								if ($vertailubu != "") {
									$query .= " sum(0) '".(substr($i,0,4)-1).substr($i,4,2)."_tavoiteed', ";
									$query .= " sum(0) '".(substr($i,0,4)-1).substr($i,4,2)."_tavoiteinded', ";
								}

								if ($piilota_kappaleet == "") {
									$query .= " sum(if(tilausrivi.laskutettuaika >= '{$alku_ed}' and tilausrivi.laskutettuaika <= '{$loppu_ed}', tilausrivi.kpl,0)) '".(substr($i,0,4)-1).substr($i,4,2)."_kpl', ";
								}
							}

							$i = date("Ymd",mktime(0, 0, 0, substr($i,4,2)+1, 1,  substr($i,0,4)));
						}

						// Vika pilkku pois
						$query = substr($query, 0 , -2);
					}
					else {

						//MYYNTI
						if ($piilota_myynti == "") {
							if ($ajotapa == 'tilausjaauki' or $ajotapa == 'tilausauki') {
								$query .= "sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59' and tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) myyntilaskuttamattanyt, ";
							}

							if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
								$query .= "	sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59', if(tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, tilausrivi.rivihinta), 0)) myyntinyt, ";
							}
							elseif ($ajotapa != 'tilausauki') {
								$query .= "	sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.rivihinta, 0)) myyntinyt, ";
							}

							if ($vertailubu != "") {
								$query .= " sum(0) 'tavoitenyt', ";
								$query .= " sum(0) 'tavoiteindnyt', ";
							}

							if ($ajotapa == 'tilausjaauki') {
								$query .= " sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59' and tilausrivi.uusiotunnus=0, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) +
											sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl', tilausrivi.rivihinta, 0)) myyntinytyht, ";
							}

							//MYYNTIED
							if ($piiloed == "") {
								if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
									$query .= " sum(if(lasku.luontiaika >= '{$vvaa}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvll}-{$kkl}-{$ppl} 23:59:59', if(tilausrivi.uusiotunnus = 0, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, tilausrivi.rivihinta), 0)) myyntied, ";

									$query .= "	round(sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59', if(tilausrivi.uusiotunnus = 0, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, tilausrivi.rivihinta), 0)) /
													sum(if(lasku.luontiaika >= '{$vvaa}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvll}-{$kkl}-{$ppl} 23:59:59', if(tilausrivi.uusiotunnus = 0, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, tilausrivi.rivihinta), 0)), 2) myyntiind, ";
								}
								elseif ($ajotapa != 'tilausauki')  {
									$query .= "	sum(if(tilausrivi.laskutettuaika >= '{$vvaa}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvll}-{$kkl}-{$ppl}', tilausrivi.rivihinta, 0)) myyntied, ";
									$query .= "	round(sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.rivihinta, 0)) / sum(if(tilausrivi.laskutettuaika >= '{$vvaa}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvll}-{$kkl}-{$ppl}', tilausrivi.rivihinta, 0)), 2) myyntiind, ";
								}

								if ($vertailubu != "") {
									$query .= " sum(0) 'tavoiteed', ";
									$query .= " sum(0) 'tavoiteinded', ";
								}
							}
						}

						if ($oikeurow['paivitys'] == 1) {

							if ($piilota_nettokate == "") {
								//NETTOKATE
								if ($ajotapa == 'tilausjaauki' or $ajotapa == 'tilausauki') {
									$query .= " sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59' and tilausrivi.uusiotunnus = 0,
										 		(tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)) -
										 		(tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(asiakas.kuluprosentti, 0) / 100) -
										 		(tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(toimitustapa.kuluprosentti, 0) / 100) -
										 		(tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(yhtio.kuluprosentti, 0) / 100) -
										 		(tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(tuote.kuluprosentti, 0) / 100), 0)) nettokatelaskuttamattanyt, ";
								}

								if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
									$query .= "	sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59',
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt))) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(asiakas.kuluprosentti, 0) / 100) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(toimitustapa.kuluprosentti, 0) / 100) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(yhtio.kuluprosentti, 0) / 100) -
												(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(tuote.kuluprosentti, 0) / 100), 0)) nettokatenyt, ";
								}
								elseif ($ajotapa != 'tilausauki') {
									$query .= "	sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.kate - (tilausrivi.rivihinta * IFNULL(asiakas.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(toimitustapa.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(tuote.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(yhtio.kuluprosentti, 0)/100), 0)) nettokatenyt, ";
								}

								if ($ajotapa == 'tilausjaauki') {
									$query .= "sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59' and tilausrivi.uusiotunnus = 0,
										 		(tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)) -
										 		(tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(asiakas.kuluprosentti, 0) / 100) -
										 		(tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(toimitustapa.kuluprosentti, 0) / 100) -
										 		(tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(yhtio.kuluprosentti, 0) / 100) -
										 		(tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * IFNULL(tuote.kuluprosentti, 0) / 100), 0)) +
												sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.kate - (tilausrivi.rivihinta * IFNULL(asiakas.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(toimitustapa.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(tuote.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(yhtio.kuluprosentti, 0)/100), 0)) nettokateyhtnyt, ";
								}

								//NETTOKATE ED
								if ($piiloed == "") {

									if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
										$query .= "	sum(if(lasku.luontiaika >= '{$vvaa}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvll}-{$kkl}-{$ppl} 23:59:59',
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt))) -
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(asiakas.kuluprosentti, 0) / 100) -
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(toimitustapa.kuluprosentti, 0) / 100) -
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(yhtio.kuluprosentti, 0) / 100) -
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(tuote.kuluprosentti, 0) / 100), 0)) nettokateed, ";

										$query .= "	sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59',
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt))) -
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(asiakas.kuluprosentti, 0) / 100) -
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(toimitustapa.kuluprosentti, 0) / 100) -
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(yhtio.kuluprosentti, 0) / 100) -
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(tuote.kuluprosentti, 0) / 100), 0)) /
													sum(if(lasku.luontiaika >= '{$vvaa}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvll}-{$kkl}-{$ppl} 23:59:59',
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt))) -
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(asiakas.kuluprosentti, 0) / 100) -
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(toimitustapa.kuluprosentti, 0) / 100) -
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(yhtio.kuluprosentti, 0) / 100) -
													(if(tilausrivi.uusiotunnus != 0, tilausrivi.rivihinta, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) * IFNULL(tuote.kuluprosentti, 0) / 100), 0)) nettokateind, ";
									}
									elseif ($ajotapa != 'tilausauki')  {
										$query .= "	sum(if(tilausrivi.laskutettuaika >= '{$vvaa}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvll}-{$kkl}-{$ppl}', tilausrivi.kate - (tilausrivi.rivihinta * IFNULL(asiakas.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(toimitustapa.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(tuote.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(yhtio.kuluprosentti, 0)/100), 0)) nettokateed, ";
										$query .= "	sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}',  tilausrivi.kate - (tilausrivi.rivihinta * IFNULL(asiakas.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(toimitustapa.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(tuote.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(yhtio.kuluprosentti, 0)/100), 0)) / sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl', tilausrivi.kate - (tilausrivi.rivihinta * IFNULL(asiakas.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(toimitustapa.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(tuote.kuluprosentti, 0)/100) - (tilausrivi.rivihinta * IFNULL(yhtio.kuluprosentti, 0)/100), 0)) nettokateind, ";
									}
								}

								//nettokateprossa n‰ytet‰‰n vain jos myynti ja nettokate on valittu myˆs n‰ytett‰v‰ksi
								if ($piilota_myynti == "" and $piilota_nettokate == "") {
									//NETTOKATEPROS
									$query .= $nettokatelisanyt;

									//NETTOKATEPROSED
									if ($piiloed == "") {
										$query .= $nettokatelisaed;
									}
								}
							}

							if ($piilota_kate == "") {
								//KATE
								if ($ajotapa == 'tilausjaauki' or $ajotapa == 'tilausauki') {
									$query .= "sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59' and tilausrivi.uusiotunnus = 0, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt), 0)) katelaskuttamattanyt, ";
								}

								if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
									$query .= "	sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59', if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)), 0)) katenyt, ";
								}
								elseif ($ajotapa != 'tilausauki') {
									$query .= "	sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.kate, 0)) katenyt, ";
								}

								if ($ajotapa == 'tilausjaauki') {
									$query .= " sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59' and tilausrivi.uusiotunnus = 0, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt), 0)) +
												sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.kate, 0)) kateyhtnyt, ";
								}

								if ($piiloed == "") {
									if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
										$query .= "	sum(if(lasku.luontiaika >= '{$vvaa}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvll}-{$kkl}-{$ppl} 23:59:59', if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)), 0)) kateed, ";
										$query .= "	round(sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59', if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)), 0)) /sum(if(lasku.luontiaika >= '$vvaa-$kka-$ppa 00:00:00' and lasku.luontiaika <= '$vvll-$kkl-$ppl 23:59:59', if(tilausrivi.uusiotunnus != 0, tilausrivi.kate, tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} - if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) * (tilausrivi.varattu+tilausrivi.jt)), 0)), 2) kateind, ";
									}
									elseif ($ajotapa != 'tilausauki') {
										$query .= "	sum(if(tilausrivi.laskutettuaika >= '{$vvaa}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvll}-{$kkl}-{$ppl}', tilausrivi.kate, 0)) kateed, ";
										$query .= "	round(sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.kate, 0)) /sum(if(tilausrivi.laskutettuaika >= '{$vvaa}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvll}-{$kkl}-{$ppl}', tilausrivi.kate, 0)), 2) kateind, ";
									}
								}
							}

							//kateprossa n‰ytet‰‰n vain jos myynti ja kate on valittu myˆs n‰ytett‰v‰ksi
							if ($piilota_myynti == "" and $piilota_kate == "") {
								//KATEPROS
								$query .= $katelisanyt;

								//KATEPROSED
								if ($piiloed == "") {
									$query .= $katelisaed;
								}
							}
						}

						if ($piilota_kappaleet == "") {
							//KPL
							if ($ajotapa == 'tilausjaauki' or $ajotapa == 'tilausauki') {
								$query .= "sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59' and tilausrivi.uusiotunnus = 0, tilausrivi.varattu+tilausrivi.jt, 0)) myykpllaskuttamattanyt, ";
							}

							if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
								$query .= "	sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59', if(tilausrivi.uusiotunnus = 0, tilausrivi.varattu+tilausrivi.jt, tilausrivi.kpl), 0)) myykplnyt, ";
							}
							elseif ($ajotapa != 'tilausauki') {
								$query .= "	sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.kpl, 0)) myykplnyt, ";
							}

							if ($ajotapa == 'tilausjaauki') {
								$query .= " sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59' and tilausrivi.uusiotunnus = 0, tilausrivi.varattu+tilausrivi.jt, 0)) +
											sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.kpl, 0)) myykplyhtnyt, ";
							}

							//KPLED
							if ($piiloed == "") {
								if ($ajotapa == 'tilausjaaukiluonti' or $ajotapa == 'ennakot') {
									$query .= "	sum(if(lasku.luontiaika >= '{$vvaa}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvll}-{$kkl}-{$ppl} 23:59:59', if(tilausrivi.uusiotunnus = 0, tilausrivi.varattu+tilausrivi.jt, tilausrivi.kpl), 0)) myykpled,
												round(sum(if(lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59', if(tilausrivi.uusiotunnus = 0, tilausrivi.varattu+tilausrivi.jt, tilausrivi.kpl), 0)) / sum(if(lasku.luontiaika >= '{$vvaa}-{$kka}-{$ppa} 00:00:00' and lasku.luontiaika <= '{$vvll}-{$kkl}-{$ppl} 23:59:59', if(tilausrivi.kpl = 0, tilausrivi.varattu+tilausrivi.jt, tilausrivi.kpl), 0)), 2) myykplind, ";
								}
								elseif ($ajotapa != 'tilausauki')  {
									$query .= "	sum(if(tilausrivi.laskutettuaika >= '{$vvaa}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvll}-{$kkl}-{$ppl}', tilausrivi.kpl, 0)) myykpled,
												round(sum(if(tilausrivi.laskutettuaika >= '{$vva}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvl}-{$kkl}-{$ppl}', tilausrivi.kpl, 0)) / sum(if(tilausrivi.laskutettuaika >= '{$vvaa}-{$kka}-{$ppa}' and tilausrivi.laskutettuaika <= '{$vvll}-{$kkl}-{$ppl}', tilausrivi.kpl, 0)), 2) myykplind, ";
								}
							}
						}
						// Vika pilkku ja space pois
						$query = substr($query, 0, -2);
					}

					$query .= $tilauslisa3;
					$query .= "\nFROM lasku use index (yhtio_tila_tapvm)
								JOIN yhtio ON (yhtio.yhtio = lasku.yhtio)
								JOIN tilausrivi use index ({$index}) ON (tilausrivi.yhtio=lasku.yhtio and tilausrivi.{$ouusio}=lasku.tunnus and tilausrivi.tyyppi={$tyyppi})
								JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno)
								JOIN asiakas use index (PRIMARY) ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus {$asiakaslisa})
								LEFT JOIN toimitustapa ON (lasku.yhtio=toimitustapa.yhtio and lasku.toimitustapa=toimitustapa.selite)
								{$lisatiedot_join}
								{$varasto_join}
								{$kantaasiakas_join}
								{$lisa_parametri}
								WHERE lasku.yhtio in ({$yhtio})
								and lasku.tila in ({$tila})";

					//yritet‰‰n saada kaikki tarvittavat laskut mukaan
					$lalku  	= date("Y-m-d", mktime(0, 0, 0, $kka-1, $ppa,  $vva));
					$lloppu 	= date("Y-m-d", mktime(0, 0, 0, $kkl+1, $ppl,  $vvl));
					$lalku_ed	= date("Y-m-d", mktime(0, 0, 0, $kka-1, $ppa,  $vva-1));
					$lloppu_ed	= date("Y-m-d", mktime(0, 0, 0, $kkl+1, $ppl,  $vvl-1));

					if ($ajotapa == 'tilausjaauki') {
						$query .= "	and lasku.alatila in ('','A','B','C','D','J','E','F','T','U','X')
									and ((lasku.luontiaika >= '{$vva}-{$kka}-{$ppa} 00:00:00'  and lasku.luontiaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59') or (lasku.tapvm >= '{$vva}-{$kka}-{$ppa}' and lasku.tapvm <= '{$vvl}-{$kkl}-{$ppl}') ";

						if ($piiloed == "") {
							$query .= " or (lasku.tapvm >= '{$vvaa}-{$kka}-{$ppa}' and lasku.tapvm <= '{$vvll}-{$kkl}-{$ppl}') ";
						}

						$query .= " ) ";
					}
					elseif ($ajotapa == 'tilausjaaukiluonti') {
						$query .= "	and lasku.alatila in ('','A','B','C','D','J','E','F','T','U','X')
									and ((lasku.luontiaika >= '{$lalku} 00:00:00'  and lasku.luontiaika <= '{$lloppu} 23:59:59') ";

						if ($piiloed == "") {
							$query .= " or (lasku.luontiaika >= '{$lalku_ed} 00:00:00' and lasku.luontiaika <= '{$lloppu_ed} 23:59:59') ";
						}

						$query .= " ) ";
					}
					elseif ($ajotapa == 'tilausauki') {
						$query .= "	and lasku.alatila in ('','A','B','C','D','J','E','F','T','U','X')
									and ((lasku.luontiaika >= '{$lalku} 00:00:00'  and lasku.luontiaika <= '{$lloppu} 23:59:59') ";

						if ($piiloed == "") {
							$query .= " or (lasku.luontiaika >= '{$lalku_ed} 00:00:00' and lasku.luontiaika <= '{$lloppu_ed} 23:59:59') ";
						}

						$query .= " ) ";
					}
					elseif ($ajotapa == 'ennakot') {
						$query .= "	and lasku.alatila = 'A'
									and ((lasku.luontiaika >= '{$lalku} 00:00:00'  and lasku.luontiaika <= '{$lloppu} 23:59:59') ";

						if ($piiloed == "") {
							$query .= " or (lasku.luontiaika >= '{$lalku_ed} 00:00:00' and lasku.luontiaika <= '{$lloppu_ed} 23:59:59') ";
						}

						$query .= " ) ";
					}
					else {
						$query .= "	and lasku.alatila='X'
									and ((lasku.tapvm >= '{$vva}-{$kka}-{$ppa}'  and lasku.tapvm <= '{$vvl}-{$kkl}-{$ppl}') ";

						if ($piiloed == "") {
							$query .= " or (lasku.tapvm >= '{$vvaa}-{$kka}-{$ppa}' and lasku.tapvm <= '{$vvll}-{$kkl}-{$ppl}') ";
						}

						$query .= " ) ";
					}

					$query .= "	{$asiakasrajaus}
								{$lisa}
								GROUP BY {$group}
								ORDER BY {$order}";

					// ja sitten ajetaan itte query
					if ($query != "") {

						#echo "<pre>".str_replace("\t", "", str_replace("and", "\nand", $query))."</pre><br>";

						$result = pupe_query($query);

						$rivimaara   = mysql_num_rows($result);
						$rivilimitti = 1000;

						if ($vain_excel != "") {
							echo "<font class='error'>",t("Tallenna/avaa tulos exceliss‰"),"!</font><br><br>";
							$rivilimitti = 0;
						}
						else {
							if ($rivimaara > $rivilimitti) {
								echo "<br><font class='error'>",t("Hakutulos oli liian suuri"),"!</font><br>";
								echo "<font class='error'>",t("Tallenna/avaa tulos exceliss‰"),"!</font><br><br>";
							}
						}
					}

					if ($query != "") {
						if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta.php") !== FALSE) {
							include('inc/pupeExcel.inc');

							$worksheet 	 = new pupeExcel();
							$format_bold = array("bold" => TRUE);
							$excelrivi 	 = 0;
						}

						echo "<a name='focus_tahan' /><table>";
						echo "<tr>
							<th>",t("Kausi nyt"),"</th>
							<td>{$ppa}</td>
							<td>{$kka}</td>
							<td>{$vva}</td>
							<th>-</th>
							<td>{$ppl}</td>
							<td>{$kkl}</td>
							<td>{$vvl}</td>
							</tr>\n";
						echo "<tr>
							<th>",t("Kausi ed"),"</th>
							<td>{$ppa}</td>
							<td>{$kka}</td>
							<td>{$vvaa}</td>
							<th>-</th>
							<td>{$ppl}</td>
							<td>{$kkl}</td>
							<td>{$vvll}</td>
							</tr>\n";
						echo "</table><br>";
						echo "<script LANGUAGE='JavaScript'>window.location.hash=\"focus_tahan\";</script>";

						// Muutama p‰iv‰m‰‰r‰muuttuja
						$alku_kausi = date('Ym', mktime(0,0,0,$kka,1,$vva));
						$lopu_kausi = date('Ym', mktime(0,0,0,$kkl,1,$vvl));

						$alku_kausi_ed = date('Ym', mktime(0,0,0,$kka,1,$vvaa));
						$lopu_kausi_ed = date('Ym', mktime(0,0,0,$kkl,1,$vvll));

						// Oletetaan, ett‰ samat t‰n‰ ku viime vuonna
						$alkukuun_paivat = date('t', mktime(0,0,0,$kka,1,$vva));
						$lopukuun_paivat = date('t', mktime(0,0,0,$kkl,1,$vvl));

						$kka = sprintf("%02d", $kka);
						$kkl = sprintf("%02d", $kkl);

						// Kausi p‰iviss‰
						$kausi_paivissa = round((mktime(0,0,0,$kkl,$ppl,$vvl)-mktime(0,0,0,$kka,$ppa,$vva))/86400);

						// Luodann resultista array ja korjataan/lis‰t‰‰n tietoja joita ei olla voitu laittaa mukaan isoon kyselyyn
						$rows = array();
						$groupby = array();

						while ($row = mysql_fetch_assoc($result)) {
							// Haetaan kategorioiden nimet ja tasot
							$dyn_asiakas = FALSE;
							$dyn_tuote   = FALSE;

							for ($i = $data_start_index; $i >= 0; $i--) {
								if (substr(mysql_field_name($result, $i), 0, 10) == "dynaaminen") {

									list($null, $dynlaji, $null) = explode("_", mysql_field_name($result, $i));

									if (!${"dyn_".$dynlaji}) {
										$dynpuu_q = " 	SELECT subparent.nimi
														FROM dynaaminen_puu AS subnode
														JOIN dynaaminen_puu AS subparent ON (subparent.yhtio = subnode.yhtio AND subparent.laji = subnode.laji AND subparent.lft < subnode.lft AND subparent.rgt > subnode.lft)
														WHERE subnode.tunnus = ".$row[mysql_field_name($result, $i)]."
														ORDER BY subparent.lft";
										$dynpuu_r = pupe_query($dynpuu_q);

										${"dyn_".$dynlaji} = TRUE;

										$dylask = 0;
										while ($dyprow = mysql_fetch_assoc($dynpuu_r)) {
											if (isset($row["dynaaminen_".$dynlaji."_".$dylask])) $row["dynaaminen_".$dynlaji."_".$dylask] = $row[mysql_field_name($result, $i)]." / ".$dyprow["nimi"];
											$dylask++;
										}
									}
								}
							}

							if (isset($vertailubu) and
								(($vertailubu == "asbu" or $vertailubu == "asbury" or $vertailubu == "asbuos") and isset($row["asiakaslista"]) and $row["asiakaslista"] != "") or
								($vertailubu  == "tubu" and isset($row["tuotelista"]) and $row["tuotelista"] != "") or
								(($vertailubu == "mybu" or $vertailubu == "mybury" or $vertailubu == "mybuos") and $myyjagroups > 0 and ((isset($row["asiakasmyyj‰"]) and $row["asiakasmyyj‰"] != "") or (isset($row["tuotemyyj‰"]) and $row["tuotemyyj‰"] != "") or (isset($row["myyj‰"]) and $row["myyj‰"] != "")))) {

								if ($vertailubu == "tubu") {
									$budj_taulu = "budjetti_tuote";
									$bulisa = " and tuoteno	in ({$row['tuotelista']}) ";
								}
								elseif ($vertailubu == "mybu" or $vertailubu == "mybury" or $vertailubu == "mybuos") {

									$tunnus_lisa = "";

									if (isset($row["asiakasmyyj‰"]) and $row["asiakasmyyj‰"] != "") {
										$tunnus_lisa = $row["asiakasmyyj‰"];
									}
									elseif (isset($row["tuotemyyj‰"]) and $row["tuotemyyj‰"] != "") {
										$tunnus_lisa = $row["tuotemyyj‰"];
									}
									else {
										$tunnus_lisa = $row["myyj‰"];
									}

									$budj_taulu = "budjetti_myyja";
									$bulisa = " and myyjan_tunnus in ({$tunnus_lisa}) ";

									if ($vertailubu == "mybuos" and $tuosagroups > 0) {
										$bulisa .= " and osasto = '{$row['osasto']}' ";
									}
									elseif ($vertailubu == "mybuos") {
										$bulisa .= " and osasto != '' ";
									}
									elseif ($vertailubu == "mybury" and $turyhgroups > 0) {
										$bulisa .= " and try = '{$row['tuoteryhm‰']}' ";
									}
									elseif ($vertailubu == "mybury") {
										$bulisa .= " and try != '' ";
									}
									else {
										$bulisa .= " and try = '' and osasto = '' ";
									}
								}
								else {
									$budj_taulu = "budjetti_asiakas";
									$bulisa = " and asiakkaan_tunnus in ({$row['asiakaslista']}) ";

									if ($vertailubu == "asbuos" and $tuosagroups > 0) {
										$bulisa .= " and osasto = '{$row['osasto']}' ";
									}
									elseif ($vertailubu == "asbuos") {
										$bulisa .= " and osasto != '' ";
									}
									elseif ($vertailubu == "asbury" and $turyhgroups > 0) {
										$bulisa .= " and try = '{$row['tuoteryhm‰']}' ";
									}
									elseif ($vertailubu == "asbury") {
										$bulisa .= " and try != '' ";
									}
									else {
										$bulisa .= " and try = '' and osasto = '' ";
									}
								}

								// Valitun kauden tavoite:
								$budj_q = "	SELECT kausi, sum(summa) summa
											FROM {$budj_taulu}
											WHERE yhtio				= '{$kukarow['yhtio']}'
											and kausi 			   >= '{$alku_kausi}'
											and kausi 			   <= '{$lopu_kausi}'
											{$bulisa}
											GROUP BY kausi";
								$budj_r = pupe_query($budj_q);

								$tavoite_yhtl = 0;

								while ($dyprow = mysql_fetch_assoc($budj_r)) {

									if ($dyprow["kausi"] == $alku_kausi and (int) $ppa != 1) {
										$dyprow["summa"] = $dyprow["summa"] * (($alkukuun_paivat+1-$ppa)/$alkukuun_paivat);
									}

									if ($dyprow["kausi"] == $lopu_kausi and (int) $ppl != $lopukuun_paivat) {
										$dyprow["summa"] = $dyprow["summa"] * ($ppl/$lopukuun_paivat);
									}

									if ($kuukausittain != "") {
										if (isset($row[$dyprow["kausi"]."_tavoitenyt"])) {
											$row[$dyprow["kausi"]."_tavoitenyt"] = $dyprow["summa"];
											$row[$dyprow["kausi"]."_tavoiteindnyt"] = $row[$dyprow["kausi"]."_myynti"] / $dyprow["summa"];
										}
									}
									else {
										$tavoite_yhtl += $dyprow["summa"];
									}
								}

								if ($tavoite_yhtl != 0) {
									$row["tavoitenyt"] = $tavoite_yhtl;
									$row["tavoiteindnyt"] = round($row["myyntinyt"] / $tavoite_yhtl, 2);
								}

								if ($piiloed == "") {
									// Edellisen kauden tavoite
									$budj_q = "	SELECT kausi, sum(summa) summa
												FROM {$budj_taulu}
												WHERE yhtio				= '{$kukarow['yhtio']}'
												and kausi 			   >= '{$alku_kausi_ed}'
												and kausi 			   <= '{$lopu_kausi_ed}'
												{$bulisa}
												and osasto				= ''
												GROUP BY kausi";
									$budj_r = pupe_query($budj_q);

									$tavoite_yhtl = 0;

									while ($dyprow = mysql_fetch_assoc($budj_r)) {

										if ($dyprow["kausi"] == $alku_kausi_ed and (int) $ppa != 1) {
											$dyprow["summa"] = $dyprow["summa"] * (($alkukuun_paivat+1-$ppa)/$alkukuun_paivat);
										}

										if ($dyprow["kausi"] == $lopu_kausi_ed and (int) $ppl != $lopukuun_paivat) {
											$dyprow["summa"] = $dyprow["summa"] * ($ppl/$lopukuun_paivat);
										}

										if ($kuukausittain != "") {
											if (isset($row[$dyprow["kausi"]."_tavoiteed"])) {
												$row[$dyprow["kausi"]."_tavoiteed"] = $dyprow["summa"];
												$row[$dyprow["kausi"]."_tavoiteinded"] = $row[$dyprow["kausi"]."_myynti"] / $dyprow["summa"];
											}
										}
										else {
											$tavoite_yhtl += $dyprow["summa"];
										}
									}

									if ($tavoite_yhtl != 0) {
										$row["tavoiteed"] = $tavoite_yhtl;
										$row["tavoiteinded"] = round($row["myyntied"] / $tavoite_yhtl, 2);
									}
								}
							}

							if ($ruk140chk != '' and $liitetiedostot != '') {
								$liitetiedosto_query = "SELECT *
														FROM liitetiedostot
														WHERE yhtio = '{$kukarow['yhtio']}'
														AND liitos = 'lasku'
														AND liitostunnus = '{$row['tilausnumero']}'";
								$liitetiedosto_result = pupe_query($liitetiedosto_query);

								$liitetiedosto_indeksi = 1;
								$row['liitetiedostot'] = '';

								while ($liitetiedosto_row = mysql_fetch_assoc($liitetiedosto_result)) {
									$row['liitetiedostot'] .= "<a href='{$palvelin2}/view.php?id={$liitetiedosto_row['tunnus']}' target='Attachment'>".t("Liite")." {$liitetiedosto_indeksi}</a> ";
									$liitetiedosto_indeksi++;
								}
							}

							$rows[] = $row;
						}

						// Echotaan kenttien nimet
						if ($rivimaara <= $rivilimitti) {
							echo "<table><tr>";

							foreach ($rows[0] as $ken_nimi => $null) {
								if ($ken_nimi != "asiakaslista" and $ken_nimi != "tuotelista") echo "<th>",t($ken_nimi),"</th>";
							}

							echo "</tr>\n";
						}

						if (isset($worksheet)) {
							$excelsarake=0;
							foreach ($rows[0] as $ken_nimi => $null) {
								if ($ken_nimi != "asiakaslista" and $ken_nimi != "tuotelista") $worksheet->write($excelrivi, $excelsarake++, ucfirst(t($ken_nimi)), $format_bold);
							}

							$excelsarake = 0;
							$excelrivi++;
						}

						$edluku 		= "x";
						$valisummat 	= array();
						$totsummat  	= array();
						$tarra_aineisto = "";

						if ($rivimaara > $rivilimitti) {
							require_once ('inc/ProgressBar.class.php');
							$bar = new ProgressBar();
							$elements = $rivimaara; // total number of elements to process
							$bar->initialize($elements); // print the empty bar
						}

						// Indeksien nimet
						$row_keys = array_keys($rows[0]);

						foreach ($rows as $row) {

							if ($rivimaara > $rivilimitti) $bar->increase();

							$piilosumma  = 0;
							$ken_lask    = 0;
							$excelsarake = 0;

							foreach ($row as $ken_nimi => $kentta) {
								if ($ken_lask >= $data_start_index and is_numeric($kentta)) {
									$piilosumma += $kentta;
								}
								$ken_lask++;
							}

							// N‰ytet‰‰n vain jos halutaan n‰hd‰ kaikki rivit tai summa on > 0
							if ($piilotanollarivit == "" or (float) $piilosumma != 0) {

								if ($osoitetarrat != "" and $row["asiakaslista"] > 0) {
									$tarra_aineisto .= $row["asiakaslista"].",";
								}

								if ($rivimaara <= $rivilimitti) echo "<tr>";

								// echotaan kenttien sis‰ltˆ
								$ken_lask = 0;

								// Jos gruupataan enemm‰n kuin yksi taso niin tehd‰‰n v‰lisumma
								if ($piiyhteensa == '' and $gluku > 1 and $edluku != 'x' and $edluku != $row[$row_keys[0]] and strpos($group, ',') !== FALSE and substr($group, 0, 13) != "tuote.tuoteno") {
									$excelsarake = $myyntiind = $kateind = $nettokateind = $myykplind = 0;

									if ($rivimaara <= $rivilimitti) echo "<tr>";

									foreach ($valisummat as $vnim => $vsum) {

										if (!is_numeric($vsum)) {
											$vsum = "";
										}
										elseif ($vnim == "kateprosnyt") {
											if ($valisummat["myyntinyt"] <> 0) 		$vsum = round($valisummat["katenyt"] / abs($valisummat["myyntinyt"]) * 100, 2);
										}
										elseif ($vnim == "kateprosed") {
											if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["kateed"] / abs($valisummat["myyntied"]) * 100, 2);
										}
										elseif ($vnim == "nettokateprosnyt") {
											if ($valisummat["myyntinyt"] <> 0) 		$vsum = round($valisummat["nettokatenyt"] / abs($valisummat["myyntinyt"]) * 100, 2);
										}
										elseif ($vnim == "nettokateprosed") {
											if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["nettokateed"] / abs($valisummat["myyntied"]) * 100, 2);
										}
										elseif ($vnim == "myyntiind") {
											if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["myyntinyt"] / $valisummat["myyntied"],2);
										}
										elseif ($vnim == "kateind") {
											if ($valisummat["kateed"] <> 0) 		$vsum = round($valisummat["katenyt"] / $valisummat["kateed"],2);
										}
										elseif ($vnim == "nettokateind") {
											if ($valisummat["nettokateed"] <> 0) 	$vsum = round($valisummat["nettokatenyt"] / $valisummat["nettokateed"],2);
										}
										elseif ($vnim == "myykplind") {
											if ($valisummat["myykpled"] <> 0)		$vsum = round($valisummat["myykplnyt"] / $valisummat["myykpled"],2);
										}
										elseif ($vnim == "tavoiteindnyt") {
											if ($valisummat["tavoitenyt"] <> 0)		$vsum = round($valisummat["myyntinyt"] / $valisummat["tavoitenyt"],2);
										}
										elseif ((string) $vsum != '') {
											$vsum = sprintf("%.2f", $vsum);
										}

										if ($rivimaara <= $rivilimitti) echo "<td class='tumma' align='right'>{$vsum}</td>";

										if (isset($worksheet)) {
											$worksheet->write($excelrivi, $excelsarake++, $vsum);
										}
									}

									$excelsarake = 0;
									$excelrivi++;

									if ($rivimaara <= $rivilimitti) echo "</tr>";

									$valisummat = array();
								}

								$edluku = $row[$row_keys[0]];

								foreach ($row as $ken_nimi => $kentta) {

									// jos kyseessa on tuote
									if ($ken_nimi == "tuoteno") {
										$koskematon_tuoteno = $row["tuoteno"];
										$row[$ken_nimi] = "<a href='../tuote.php?tee=Z&tuoteno=".urlencode($row[$ken_nimi])."'>{$row[$ken_nimi]}</a>";
									}

									if ($ken_nimi == "laskutuspvm") {
										$row[$ken_nimi] = tv1dateconv($kentta);
									}

									// jos kyseessa on asiakasosasto, haetaan sen nimi
									if ($ken_nimi == "asiakasosasto") {
										$osre = t_avainsana("ASIAKASOSASTO", "", "and avainsana.selite  = '{$row[$ken_nimi]}'", $yhtio);
										$osrow = mysql_fetch_assoc($osre);

										if ($osrow['selite'] == "") {
											$osrow['selite'] = t("Ei asiakasosastoa");
										}

										$serialisoitavat_muuttujat = $kaikki_muuttujat_array;

										// jos asiakasosostoittain ja asiakasryhmitt‰in ruksin on chekattu, osastoa klikkaamalla palataan taaksep‰in
										if ($ruksit["asiakasosasto"] != '' and $ruksit["asiakasryhma"] != '') {
											// Nollataan asiakasosasto sek‰ asiakaryhm‰valinnat
											unset($serialisoitavat_muuttujat["mul_oasiakasosasto"]);
											unset($serialisoitavat_muuttujat["mul_asiakasryhma"]);

											// Nollataan asiakasryhm‰ruksi sek‰ tuotettainruksi
											$serialisoitavat_muuttujat["ruksit"]["asiakasryhma"] = "";
											$serialisoitavat_muuttujat["ruksit"][30] = "";
										}
										else {
											// jos asiakasosostoittain ja asiakasryhmitt‰in ei ole chekattu, osastoa klikkaamalla menn‰‰n eteenp‰in
											$serialisoitavat_muuttujat["mul_oasiakasosasto"][$ken_nimi] = $row[$ken_nimi];
											$serialisoitavat_muuttujat["ruksit"]["asiakasryhma"] = "asiakasryhma";
										}

										$row[$ken_nimi] = "<a href='myyntiseuranta.php?kaikki_parametrit_serialisoituna=".urlencode(serialize($serialisoitavat_muuttujat))."'>{$osrow['selite']} {$osrow['selitetark']}</a>";
									}

									// jos kyseessa on piiri, haetaan sen nimi
									if ($ken_nimi == "asiakaspiiri") {
										$osre = t_avainsana("PIIRI", "", "and avainsana.selite  = '{$row[$ken_nimi]}'", $yhtio);
										$osrow = mysql_fetch_assoc($osre);

										if ($osrow['selitetark'] != "" and $osrow['selite'] != $osrow['selitetark']) {
											$row[$ken_nimi] = $row[$ken_nimi] ." ". $osrow['selitetark'];
										}
									}

									// jos kyseessa on asiakasryhma, haetaan sen nimi
									if ($ken_nimi == "asiakasryhm‰") {
										$osre = t_avainsana("ASIAKASRYHMA", "", "and avainsana.selite  = '{$row[$ken_nimi]}'", $yhtio);
										$osrow = mysql_fetch_assoc($osre);

										if ($osrow['selite'] == "") {
											$osrow['selite'] = t("Ei asiakasryhm‰‰");
										}

										$serialisoitavat_muuttujat = $kaikki_muuttujat_array;

										// jos asiakasosastot, asiakasryhm‰t ja tuottetain on valittu, menn‰‰n taaksep‰in
										if ($ruksit[10] != '' and $ruksit[20] != '' and $ruksit[80] != '') {
											unset($serialisoitavat_muuttujat["mul_asiakasryhma"]);
											$serialisoitavat_muuttujat["ruksit"][80] = "";
										}
										else {
											// jos vain asiakasosastot, asiakasryhm‰t ja tuottetain on valittu, menn‰‰n eteenp‰in
											$serialisoitavat_muuttujat["mul_asiakasryhma"][$ken_nimi] = $row[$ken_nimi];
											$serialisoitavat_muuttujat["ruksit"][20] = "asiakasryhma";
											$serialisoitavat_muuttujat["ruksit"][80] = "tuote";
										}

										$row[$ken_nimi] = "<a href='myyntiseuranta.php?kaikki_parametrit_serialisoituna=".urlencode(serialize($serialisoitavat_muuttujat))."'>{$osrow['selite']} {$osrow['selitetark']}</a>";
									}

									// jos kyseessa on tuoteosasto, haetaan sen nimi
									if ($ken_nimi == "tuoteosasto") {
										$osre = t_avainsana("OSASTO", "", "and avainsana.selite  = '{$row[$ken_nimi]}'", $yhtio);
										$osrow = mysql_fetch_assoc($osre);

										if ($osrow['selite'] == "") {
											$osrow['selite'] = t("Ei tuoteosastoa");
										}

										$serialisoitavat_muuttujat = $kaikki_muuttujat_array;

										// jos tuoteosostoittain ja tuoteryhmitt‰in ruksin on chekattu, osastoa klikkaamalla palataan taaksep‰in
										if ($ruksit["osasto"] != '' and $ruksit["try"] != '') {
											// Nollataan asiakasosasto sek‰ asiakaryhm‰valinnat
											unset($serialisoitavat_muuttujat["mul_osasto"]);
											unset($serialisoitavat_muuttujat["mul_try"]);

											// Nollataan tuoteryhm‰ruksi sek‰ tuotettainruksi
											$serialisoitavat_muuttujat["ruksit"]["try"] = "";
											$serialisoitavat_muuttujat["ruksit"][30] = "";
										}
										else {
											// jos tuoteosostoittain ja tuoteryhmitt‰in ei ole chekattu, osastoa klikkaamalla menn‰‰n eteenp‰in
											$serialisoitavat_muuttujat["mul_osasto"][$ken_nimi] = $row[$ken_nimi];
											$serialisoitavat_muuttujat["ruksit"]["try"] = "tuoteryhma";
										}

										$row[$ken_nimi] = "<a href='myyntiseuranta.php?kaikki_parametrit_serialisoituna=".urlencode(serialize($serialisoitavat_muuttujat))."'>{$osrow['selite']} {$osrow['selitetark']}</a>";
									}

									// jos kyseessa on tuoteosasto, haetaan sen nimi
									if ($ken_nimi == "tuoteryhm‰") {
										$osre = t_avainsana("TRY", "", "and avainsana.selite  = '{$row[$ken_nimi]}'", $yhtio);
										$osrow = mysql_fetch_assoc($osre);

										if ($osrow['selite'] == "") {
											$osrow['selite'] = t("Ei tuoteryhm‰‰");
										}

										$serialisoitavat_muuttujat = $kaikki_muuttujat_array;

										// jos tuoteosastot, tuoteryhm‰t ja tuottetain on valittu, menn‰‰n taaksep‰in
										if ($ruksit["osasto"] != '' and $ruksit["try"] != '' and $ruksit[30] != '') {
											unset($serialisoitavat_muuttujat["mul_try"]);
											$serialisoitavat_muuttujat["ruksit"][30] = "";
										}
										else {
											// jos vain tuoteosastot, tuoteryhm‰t ja tuottetain on valittu, menn‰‰n eteenp‰in
											$serialisoitavat_muuttujat["mul_try"][$ken_nimi] = $row[$ken_nimi];
											$serialisoitavat_muuttujat["ruksit"]["osasto"] = "osasto";
											$serialisoitavat_muuttujat["ruksit"]["try"] = "tuoteryhma";
											$serialisoitavat_muuttujat["ruksit"][30] = "tuote";
										}

										$row[$ken_nimi] = "<a href='myyntiseuranta.php?kaikki_parametrit_serialisoituna=".urlencode(serialize($serialisoitavat_muuttujat))."'>{$osrow['selite']} {$osrow['selitetark']}</a>";
									}

									// jos kyseessa on myyj‰, haetaan sen nimi
									if ($ken_nimi == "tuotemyyj‰" or $ken_nimi == "asiakasmyyj‰") {
										$query = "	SELECT nimi
													FROM kuka
													WHERE yhtio IN ({$yhtio})
													AND myyja = '{$row[$ken_nimi]}'
													AND myyja > 0
													LIMIT 1";
										$osre = pupe_query($query);

										if (mysql_num_rows($osre) == 1) {
											$osrow = mysql_fetch_assoc($osre);
											$row[$ken_nimi] = $row[$ken_nimi] ." ". $osrow['nimi'];
										}
									}

									// jos kyseessa on myyj‰, haetaan sen nimi
									if ($ken_nimi == "myyj‰") {
										$query = "	SELECT nimi
													FROM kuka
													WHERE yhtio IN ({$yhtio})
													AND tunnus = '{$row[$ken_nimi]}'";
										$osre = pupe_query($query);

										if (mysql_num_rows($osre) == 1) {
											$osrow = mysql_fetch_assoc($osre);
											$row[$ken_nimi] = $osrow['nimi'];
										}
										else {
											$row[$ken_nimi] = t("Tyhj‰");
										}
									}

									// jos kyseessa on ostaja, haetaan sen nimi
									if ($ken_nimi == "tuoteostaja") {
										$query = "	SELECT nimi
													FROM kuka
													WHERE yhtio IN ({$yhtio})
													AND myyja = '{$row[$ken_nimi]}'
													AND myyja > 0
													LIMIT 1";
										$osre = pupe_query($query);
										if (mysql_num_rows($osre) == 1) {
											$osrow = mysql_fetch_assoc($osre);
											$row[$ken_nimi] = $row[$ken_nimi] ." ". $osrow['nimi'];
										}
									}

									// jos kyseessa on toimittaja, haetaan nimi/nimet
									if ($ken_nimi == "toimittaja") {
										// fixataan mysql 'in' muotoon
										$toimittajat = "'".str_replace(",","','",$row[$ken_nimi])."'";

										$query = "	SELECT group_concat(concat_ws(' / ',ytunnus,nimi)) nimi
													FROM toimi
													WHERE yhtio IN ({$yhtio})
													AND tunnus IN ({$toimittajat})";
										$osre = pupe_query($query);
										if (mysql_num_rows($osre) == 1) {
											$osrow = mysql_fetch_assoc($osre);
											$row[$ken_nimi] = $osrow['nimi'];
										}
									}

									// kateprossa
									if ($ken_nimi == "kateprosnyt") {
										if ($row["myyntinyt"] != 0) {
											$row[$ken_nimi] = round($row["katenyt"] / abs($row["myyntinyt"]) * 100, 2);
										}
										else {
											if ($ajotapa == "tilausauki" and $row["myyntilaskuttamattanyt"] != 0) {
												$row[$ken_nimi] = round($row["katenyt"] / abs($row["myyntilaskuttamattanyt"]) * 100, 2);
											}
											else {
												$row[$ken_nimi] = 0;
											}
										}
									}

									// kateprossa
									if ($ken_nimi == "kateprosed") {
										if ($row["myyntied"] != 0) {
											$row[$ken_nimi] = round($row["kateed"] / abs($row["myyntied"]) * 100, 2);
										}
										else {
											$row[$ken_nimi] = 0;
										}
									}

									// nettokateprossa
									if ($ken_nimi == "nettokateprosnyt") {
										if ($row["myyntinyt"] != 0) {
											$row[$ken_nimi] = round($row["nettokatenyt"] / abs($row["myyntinyt"]) * 100, 2);
										}
										else {
											$row[$ken_nimi] = 0;
										}
									}

									// nettokateprossa
									if ($ken_nimi == "nettokateprosed") {
										if ($row["myyntied"] != 0) {
											$row[$ken_nimi] = round($row["nettokateed"] / abs($row["myyntied"]) * 100, 2);
										}
										else {
											$row[$ken_nimi] = 0;
										}
									}

									if ($ken_nimi == "yhtio_toimipaikka") {
										$query = "	SELECT nimi
													FROM yhtion_toimipaikat
													WHERE yhtio = '{$kukarow['yhtio']}'
													AND tunnus = '{$row[$ken_nimi]}'";
										$toimipaikka_result = pupe_query($query);

										if (mysql_num_rows($toimipaikka_result) == 1) {
											$toimipaikka_row = mysql_fetch_assoc($toimipaikka_result);
											$row[$ken_nimi] = $toimipaikka_row['nimi'];
										}
									}

									// kustannuspaikka
									if ($ken_nimi == "kustannuspaikka") {
										// n‰ytet‰‰n soveltuvat kustannuspaikka
										$query = "	SELECT nimi
													FROM kustannuspaikka
													WHERE yhtio = '{$kukarow['yhtio']}'
													AND tunnus = '{$row[$ken_nimi]}'";
										$osre = pupe_query($query);

										if (mysql_num_rows($osre) == 1) {
											$osrow = mysql_fetch_assoc($osre);
											$row[$ken_nimi] = $osrow['nimi'];
										}
									}

									// jos kyseessa on sarjanumero
									if ($ken_nimi == "sarjanumero") {
										$sarjat = explode(",", $row[$ken_nimi]);

										$row[$ken_nimi] = "";

										foreach ($sarjat as $sarja) {
											list($s,$k) = explode("#", $sarja);

											$query = "	SELECT osto_vai_hyvitys
														FROM tilausrivin_lisatiedot
														WHERE yhtio IN ({$yhtio})
														AND tilausrivitunnus = '{$s}'";
											$rilires = pupe_query($query);
											$rilirow = mysql_fetch_assoc($rilires);

											if ($k > 0 or ($k < 0 and $rilirow["osto_vai_hyvitys"] == "")) {
												$tunken = "myyntirivitunnus";
											}
											else {
												$tunken = "ostorivitunnus";
											}

											$query = "	SELECT sarjanumero
														FROM sarjanumeroseuranta
														WHERE yhtio IN ({$yhtio})
														AND {$tunken} = {$s}";
											$osre = pupe_query($query);

											if (mysql_num_rows($osre) > 0) {
												$osrow = mysql_fetch_assoc($osre);
												$row[$ken_nimi] .= "<a href='../tilauskasittely/sarjanumeroseuranta.php?sarjanumero_haku=".urlencode($osrow["sarjanumero"])."' target='_top'>{$osrow['sarjanumero']}</a><br>";
											}
										}
										$row[$ken_nimi] = substr($row[$ken_nimi], 0, -4);
									}

									// jos kyseessa on laskunumero
									if ($ken_nimi == "laskunumero") {
										list($laskalk, $lasklop) = explode(":", $row[$ken_nimi]);

										$row[$ken_nimi] = $laskalk.":<a href='{$palvelin2}raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=&laskunro={$lasklop}' target='_top'>{$lasklop}</a>";
									}

									// jos kyseessa on varastonarvo
									if ($ken_nimi == "varastonarvo") {
										if (!isset($koskematon_tuoteno)) $koskematon_tuoteno = $row["tuoteno"];
										list($varvo, $kierto, $varaston_saldo) = vararvo($koskematon_tuoteno, $vvl, $kkl, $ppl);
										$row[$ken_nimi] = $varvo;
									}

									// jos kyseessa on varastonkierto
									if ($ken_nimi == "kierto") {
										$row[$ken_nimi] = $kierto;
									}

									// jos kyseessa on varaston saldo
									if ($ken_nimi == "varastonkpl") {
										$row[$ken_nimi] = $varaston_saldo;
									}

									if ($ken_nimi != 'asiakaslista' and $ken_nimi != "tuotelista") {
										if ($ken_lask >= $data_start_index and is_numeric($row[$ken_nimi])) {
											if ($rivimaara <= $rivilimitti) {
												echo "<td valign='top' align='right'>".sprintf("%.02f",$row[$ken_nimi])."</td>";
											}

											if (isset($worksheet)) {
												$worksheet->writeNumber($excelrivi, $excelsarake++, sprintf("%.02f",$row[$ken_nimi]));
											}
										}
										elseif ($ken_nimi == 'sarjanumero') {
											if ($rivimaara <= $rivilimitti) {
												echo "<td valign='top'>{$row[$ken_nimi]}</td>";
											}

											if (isset($worksheet)) {
												$worksheet->writeString($excelrivi, $excelsarake++, strip_tags(str_replace("<br>", "\n", $row[$ken_nimi])));
											}
										}
										else {
											if ($rivimaara <= $rivilimitti) {
												echo "<td valign='top'>{$row[$ken_nimi]}</td>";
											}

											if (isset($worksheet)) {
												$worksheet->writeString($excelrivi, $excelsarake++, strip_tags(str_replace("<br>", " / ", $row[$ken_nimi])));
											}
										}
									}

									$ken_lask++;
								}

								if ($rivimaara <= $rivilimitti) echo "</tr>\n";

								$excelsarake = 0;
								$excelrivi++;

								$ken_lask = 0;

								foreach ($row as $ken_nimi => $kentta) {
									if ($ken_nimi != "asiakaslista" and $ken_nimi != "tuotelista") {
										if ($ken_lask < $data_start_index) {
											$valisummat[$ken_nimi] = "";
											$totsummat[$ken_nimi]  = "";
										}
										else {
											$valisummat[$ken_nimi] += $row[$ken_nimi];
											$totsummat[$ken_nimi]  += $row[$ken_nimi];
										}
									}
									$ken_lask++;
								}
							}
						}

						$apu = mysql_num_fields($result)-11;

						if ($ajotapanlisa == "erikseen") {
							$apu -= 1;
						}

						if ($ajotapa == 'tilausjaauki') {
							$apu -= 2;
						}

						if ($kateprossat != "") {
							$apu -= 2;
						}

						if ($nettokateprossat != "") {
							$apu -= 2;
						}

						// jos gruupataan enemm‰n kuin yksi taso niin tehd‰‰n v‰lisumma
						if ($gluku > 1 and $mukaan != 'tuote' and $piiyhteensa == '') {

							if ($rivimaara <= $rivilimitti) echo "<tr>";

							$excelsarake = $myyntiind = $kateind = $nettokateind = $myykplind = 0;

							foreach ($valisummat as $vnim => $vsum) {

								if (!is_numeric($vsum)) {
									$vsum = "";
								}
								elseif ($vnim == "kateprosnyt") {
									if ($valisummat["myyntinyt"] <> 0) 		$vsum = round($valisummat["katenyt"] / abs($valisummat["myyntinyt"]) * 100, 2);
								}
								elseif ($vnim == "kateprosed") {
									if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["kateed"] / abs($valisummat["myyntied"]) * 100, 2);
								}
								elseif ($vnim == "nettokateprosnyt") {
									if ($valisummat["myyntinyt"] <> 0) 		$vsum = round($valisummat["nettokatenyt"] / abs($valisummat["myyntinyt"]) * 100, 2);
								}
								elseif ($vnim == "nettokateprosed") {
									if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["nettokateed"] / abs($valisummat["myyntied"]) * 100, 2);
								}
								elseif ($vnim == "myyntiind") {
									if ($valisummat["myyntied"] <> 0) 		$vsum = round($valisummat["myyntinyt"] / $valisummat["myyntied"],2);
								}
								elseif ($vnim == "kateind") {
									if ($valisummat["kateed"] <> 0) 		$vsum = round($valisummat["katenyt"] / $valisummat["kateed"],2);
								}
								elseif ($vnim == "nettokateind") {
									if ($valisummat["nettokateed"] <> 0) 	$vsum = round($valisummat["nettokatenyt"] / $valisummat["nettokateed"],2);
								}
								elseif ($vnim == "myykplind") {
									if ($valisummat["myykpled"] <> 0)		$vsum = round($valisummat["myykplnyt"] / $valisummat["myykpled"],2);
								}
								elseif ($vnim == "tavoiteindnyt") {
									if ($valisummat["tavoitenyt"] <> 0)		$vsum = round($valisummat["myyntinyt"] / $valisummat["tavoitenyt"],2);
								}
								elseif ((string) $vsum != '') {
									$vsum = sprintf("%.2f", $vsum);
								}

								if ($rivimaara <= $rivilimitti) echo "<td class='tumma' align='right'>{$vsum}</td>";

								if(isset($worksheet)) {
									$worksheet->write($excelrivi, $excelsarake++, $vsum);
								}
							}

							$excelsarake = 0;
							$excelrivi++;

							if ($rivimaara <= $rivilimitti) echo "</tr>";
						}

						if ($rivimaara <= $rivilimitti) echo "<tr>";

						$excelsarake = $myyntiind = $kateind = $nettokateind = $myykplind = 0;

						foreach ($totsummat as $vnim => $vsum) {
							if ((string) $vsum != '') {
								$vsum = sprintf("%.2f", $vsum);
							}
							if ($vnim == "kateprosnyt") {
								if ($totsummat["myyntinyt"] <> 0) 		$vsum = round($totsummat["katenyt"] / abs($totsummat["myyntinyt"]) * 100, 2);
							}
							if ($vnim == "kateprosed") {
								if ($totsummat["myyntied"] <> 0) 		$vsum = round($totsummat["kateed"] / abs($totsummat["myyntied"]) * 100, 2);
							}
							if ($vnim == "nettokateprosnyt") {
								if ($totsummat["myyntinyt"] <> 0) 		$vsum = round($totsummat["nettokatenyt"] / abs($totsummat["myyntinyt"]) * 100, 2);
							}
							if ($vnim == "nettokateprosed") {
								if ($totsummat["myyntied"] <> 0) 		$vsum = round($totsummat["nettokateed"] / abs($totsummat["myyntied"]) * 100, 2);
							}
							if ($vnim == "myyntiind") {
								if ($totsummat["myyntied"] <> 0) 		$vsum = round($totsummat["myyntinyt"] / $totsummat["myyntied"],2);
							}
							if ($vnim == "kateind") {
								if ($totsummat["kateed"] <> 0) 			$vsum = round($totsummat["katenyt"] / $totsummat["kateed"],2);
							}
							if ($vnim == "nettokateind") {
								if ($totsummat["nettokateed"] <> 0) 	$vsum = round($totsummat["nettokatenyt"] / $totsummat["nettokateed"],2);
							}
							if ($vnim == "myykplind") {
								if ($totsummat["myykpled"] <> 0)		$vsum = round($totsummat["myykplnyt"] / $totsummat["myykpled"],2);
							}
							if ($vnim == "tavoiteindnyt") {
								if ($totsummat["tavoitenyt"] <> 0)			$vsum = round($totsummat["myyntinyt"] / $totsummat["tavoitenyt"],2);
							}

							if ($rivimaara <= $rivilimitti) echo "<td class='tumma' align='right'>{$vsum}</td>";

							if (isset($worksheet)) {
								$worksheet->writeNumber($excelrivi, $excelsarake++, $vsum);
							}
						}

						$excelsarake = 0;
						$excelrivi++;

						if ($rivimaara <= $rivilimitti) echo "</tr></table>";

						echo "<br>";

						if (isset($worksheet)) {
							// We need to explicitly close the worksheet
							$excelnimi = $worksheet->close();

							echo "<table>";
							echo "<tr><th>",t("Tallenna tulos"),":</th>";
							echo "<form method='post' class='multisubmit'>";
							echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
							echo "<input type='hidden' name='kaunisnimi' value='".t('Myynninseuranta').".xlsx'>";
							echo "<input type='hidden' name='tmpfilenimi' value='{$excelnimi}'>";
							echo "<td class='back'><input type='submit' value='",t("Tallenna"),"'></td></tr></form>";
							echo "</table><br>";
						}

						if ($osoitetarrat != "" and $tarra_aineisto != '')  {
							$tarra_aineisto = substr($tarra_aineisto, 0, -1);


							echo "<br><table>";
							echo "<tr><th>",t("Tulosta osoitetarrat"),":</th>";
							echo "<form method='post' action='../crm/tarrat.php'>";
							echo "<input type='hidden' name='tee' value=''>";
							echo "<input type='hidden' name='tarra_aineisto' value='{$tarra_aineisto}'>";
							echo "<td class='back'><input type='submit' value='",t("Siirry"),"'></td></tr></form>";
							echo "</table><br>";
						}
					}
					echo "<br><br><hr>";
				}
			}
		}

		if (strpos($_SERVER['SCRIPT_NAME'], "myyntiseuranta.php") !== FALSE) {
			require ("../inc/footer.inc");
		}
	}
