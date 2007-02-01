<?php

	echo "<font class='head'>".t("Tilauksen maksusopimus")."</font><hr><br>";

	if($tila == "uusi") {
		if ($laskurow["jaksotettu"] == 0) {
			$query = "	UPDATE lasku
						SET jaksotettu = tunnus
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  = $laskurow[tunnus]
						and tila in ('N','L','T')
						and alatila != 'X'";
			$result = mysql_query($query) or pupe_error($query);

			// Tässä meidän on hieman fiilattava tätä muuttujaa
			$laskurow["jaksotettu"] = $laskurow["tunnus"];

			// Jos tilaus on slittaantunut useaksi osaksi laitetaan maksusoppari koskemaan kaikkia splittejä jotka periytyvät samalta tilaukselta
			if ($laskurow["vanhatunnus"] > 0) {
				$query = "	UPDATE lasku
							SET jaksotettu = '$laskurow[jaksotettu]'
							WHERE yhtio 	= '$kukarow[yhtio]'
							and vanhatunnus = '$laskurow[vanhatunnus]'
							and tila in ('N','L','T')
							and alatila != 'X'";
				$result = mysql_query($query) or pupe_error($query);

				$query = "	SELECT group_concat(distinct tunnus) tunnukset
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							and jaksotettu  = '$laskurow[jaksotettu]'
							and tila in ('N','L','T')
							and alatila != 'X'";
				$result = mysql_query($query) or pupe_error($query);
				$posrow = mysql_fetch_array($result);

				$ltunnukset	= $posrow["tunnukset"];
			}
			else {
				$ltunnukset = $laskurow["tunnus"];
			}

			$query = "	UPDATE tilausrivi
						SET jaksotettu = '$laskurow[jaksotettu]'
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus in ($ltunnukset)";
			$result = mysql_query($query) or pupe_error($query);
		}

		$query = "	INSERT INTO maksupositio
					SET yhtio 	= '$kukarow[yhtio]',
					otunnus 	= '$laskurow[jaksotettu]',
					luotu		= now(),
					luonut		= '$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	if($tila == "tallenna") {

		$query = "	SELECT
					sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100))) jaksotettavaa_veroton,
					sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100))) jaksotettavaa_verolli					
					FROM lasku
					JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D' and tilausrivi.jaksotettu='$laskurow[jaksotettu]'
					WHERE lasku.yhtio 		= '$kukarow[yhtio]'
					and lasku.jaksotettu 	= '$laskurow[jaksotettu]'";
		$result = mysql_query($query) or pupe_error($query);
		$rivrow = mysql_fetch_array($result);

		$query = "	SELECT *
					FROM maksupositio
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$maksupositio'";
		$result = mysql_query($query) or pupe_error($query);
		$posrow = mysql_fetch_array($result);

		$summa = str_replace(",", ".", $summa);
		$osuus = str_replace(",", ".", $osuus);
		
		if($yhtiorow["alv_kasittely"] == '') {
			$laskunsumma = $rivrow["jaksotettavaa_verolli"];
		}
		else {
			$laskunsumma = $rivrow["jaksotettavaa_veroton"];
		}
		
		if ($posrow["summa"] != $summa and $summa > 0) {
			//Lasketaan prossa annetun summan perusteella
			$osuus = round($summa / $laskunsumma * 100,4);
		}
		elseif($posrow["osuus"] != $osuus and $osuus > 0) {
			//Lasketaan summa annetun prossan perusteella
			$summa = round($osuus/100 * $laskunsumma,2);
		}
		
		
		if ($mehto != "") {
			// haetaan maksuehdoen tiedot tarkastuksia varten
			$apuqu = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$mehto'";
			$meapu = mysql_query($apuqu) or pupe_error($apuqu);
			$meapurow = mysql_fetch_array($meapu);
		}

		if (($erpcmvv != "" and $erpcmkk != "" and $erpcmpp != "") or $meapurow["erapvmkasin"] != '') {

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
				$erpcmvv = 0;
				$erpcmkk = 0;
				$erpcmpp = 0;
			}
		}
		
		$query = "	UPDATE maksupositio SET
					osuus 		= '$osuus',
					summa  		= '$summa',
					kuvaus 		= '$kuvaus',
					erpcm		= '$erpcmvv-$erpcmkk-$erpcmpp',
					maksuehto 	= '$mehto',
					lisatiedot 	= '".addslashes($lisatiedot)."',
					ohje 		= '".addslashes($ohje)."'
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[jaksotettu]'
					and tunnus  = '$maksupositio'";
		$result = mysql_query($query) or pupe_error($query);
		
		$erpcmpp = "";
		$erpcmkk = "";
		$erpcmvv = "";
	}

	if($tila == "poista") {
		$query = "DELETE FROM maksupositio WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$maksupositio'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	SELECT *
					FROM maksupositio
					WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$laskurow[jaksotettu]'";
		$result = mysql_query($query) or pupe_error($query);

		if(mysql_num_rows($result) == 0) {
			$query = "	UPDATE lasku
						SET jaksotettu = 0
						WHERE yhtio 	= '$kukarow[yhtio]'
						and jaksotettu  = '$laskurow[jaksotettu]'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE tilausrivi
						SET jaksotettu = 0
						WHERE yhtio 	= '$kukarow[yhtio]'
						and jaksotettu 	= '$laskurow[jaksotettu]'";
			$result = mysql_query($query) or pupe_error($query);
		}
	}

	// Maksopositiot
	$query = "	SELECT *
				FROM maksuehto
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus  = '$laskurow[maksuehto]'
				and jaksotettu != ''";
	$result = mysql_query($query) or pupe_error($query);

	if(mysql_num_rows($result) == 1) {

		$tot = 0;

		$query	= "	SELECT *
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus  = '$laskurow[liitostunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {
			$asiakasrow = mysql_fetch_array($result);
		}

		// Haetaan laskun summat
		$query = "	SELECT
					group_concat(distinct lasku.tunnus) tilaukset,					
					round(sum(if(tilausrivi.jaksotettu='$laskurow[jaksotettu]', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)), 0)),2) jaksotettavaa_veroton,
					round(sum(if(tilausrivi.jaksotettu='$laskurow[jaksotettu]', tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)), 0)),2) jaksotettavaa_verolli,
					round(sum(if(tilausrivi.jaksotettu=0 , tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)), 0)),2) loput_veroton,
					round(sum(if(tilausrivi.jaksotettu=0 , tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)), 0)),2) loput_verolli
					FROM lasku
					JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D'
					WHERE lasku.yhtio		= '$kukarow[yhtio]'
					and lasku.jaksotettu	= '$laskurow[jaksotettu]'
					and lasku.jaksotettu 	> 0
					GROUP by lasku.jaksotettu";
		$result = mysql_query($query) or pupe_error($query);
		$rivrow = mysql_fetch_array($result);

		
		if($yhtiorow["alv_kasittely"] == '') {
			$jaksotettavaa 	= $rivrow["jaksotettavaa_verolli"];
			$loput 			= $rivrow["loput_verolli"];
		}
		else {
			$jaksotettavaa 	= $rivrow["jaksotettavaa_veroton"];
			$loput 			= $rivrow["loput_veroton"];
		}
		
		
		// Haetaan kaikki tämän tilauksen laskutuspositiot (HUOM! Positioiden summ on veroton tai verollinen riippuen yhtiön myyntihinnoista)
		$query = "	SELECT *
					FROM maksupositio
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$laskurow[jaksotettu]'
					ORDER BY tunnus";
		$result = mysql_query($query) or pupe_error($query);

		if(mysql_num_rows($result) > 0) {

			echo "<table>";
			echo "<tr><th>".t("Maksusopimukseen kuuluvat tilaukset").":</th><td> $rivrow[tilaukset] </td></tr>";
			echo "</table><br>";

			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'LASKUKUVAUS'
						ORDER BY jarjestys, selite";
			$kuvares = mysql_query($query) or pupe_error($query);

			$query = "	SELECT tunnus, concat_ws(' ', kassa_teksti, teksti)
						FROM maksuehto
						WHERE yhtio = '$kukarow[yhtio]'
						and jaksotettu=''
						ORDER BY jarjestys";
			$mresult = mysql_query($query) or pupe_error($query);

			echo "<table cellspacing='1' cellpadding='2'>
					<tr>
					<th>#</th>
					<th>".t("Osuus-%")."</th>
					<th>".t("Maksuehto/Kuvaus")."</th>
					<th>".t("Lisätiedot/Ohje")."</th>
					<th>".t("Summa")."</th>
					</tr>";

			$osuus	= 0;
			$i		= 0;

			while($row=mysql_fetch_array($result)) {

				$osuus += $row["osuus"];
				$tot   += $row["summa"];

				$i++;

				if($row["uusiotunnus"] == 0) {
					echo "<form method='post' name='maksupositiot' action='$PHP_SELF'>";
					echo "<tr>";

					echo "<td>$i</td>";
					echo "<td><input type='text' size ='5' name='osuus' value='$row[osuus]'></td>";
					echo "<td><select name='mehto'>";

					while($prow = mysql_fetch_array($mresult)) {
						if($row["maksuehto"] == $prow["tunnus"]) $sel = "SELECTED";
						elseif($asiakasrow["maksuehto"] == $prow["tunnus"] and $row["maksuehto"] == "") $sel = "SELECTED";
						else $sel = "";

						echo "<option value='$prow[0]' $sel>$prow[1]</option>";
					}
					echo "</select><br>";
					
					
					// otetaan erpcm kannasta jos mitään ei ole syötetty ruudulle
					if ($row["erpcm"] != "" and (int) $erpcmpp == 0 and (int) $ercpmkk == 0 and (int) $erpcmvv == 0 and $erpcm_virhe == "") {
						list($erpcmvv, $erpcmkk, $erpcmpp) = split('-', $row["erpcm"]);
					}

					// jos päivämäärä on nollaa laitetaan tyhjää
					if ((int) $erpcmpp == 0 and (int) $erpcmkk == 0 and (int) $erpcmvv == 0) {
						$erpcmpp = "";
						$erpcmkk = "";
						$erpcmvv = "";
					}

					echo "	<input type='text' name='erpcmpp' value='$erpcmpp' size='3'>
							<input type='text' name='erpcmkk' value='$erpcmkk' size='3'>
							<input type='text' name='erpcmvv' value='$erpcmvv' size='6'>";
					echo "</td>";

					echo "<td><input type='text' size='30' name='lisatiedot' value='$row[lisatiedot]'></td>";
					
					
					if($yhtiorow["alv_kasittely"] == '') {
						$row["summa"] = round($row["summa"] * (1+($row["alv"]/100)),2);	
					}
					else {
						$row["summa"] = round($row["summa"],2);
					}
					
					
					echo "<td align ='right'><input type='text' size='6'  name='summa' value='".sprintf('%.2f', $row["summa"])."'></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td></td><td></td><td valign='top'><select name='kuvaus'>";

					while($prow= mysql_fetch_array($kuvares)) {
						if($row["kuvaus"] == $prow["selitetark"]) $sel = "SELECTED";
						else $sel = "";

						echo "<option value='$prow[selitetark]' $sel>$prow[selitetark]</option>";
					}
					echo "</select></td>";
					
					echo "<td><textarea rows='2' cols='30' name='ohje'>$row[ohje]</textarea></td>";
					echo "<td></td>";

					echo "	<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tilausnumero' value='$tilausnumero'>
							<input type='hidden' name='tee' value='$tee'>
							<input type='hidden' name='tila' value='tallenna'>
							<input type='hidden' name='maksupositio' value='$row[tunnus]'>
							<td class='back' valign='top'><input type='submit' value='".t("Tallenna")."'></td></form>";

					echo "<form method='post' name='maksupositiot' action='$PHP_SELF'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='tilausnumero' value='$tilausnumero'>
							<input type='hidden' name='tee' value='$tee'>
							<input type='hidden' name='tila' value='poista'>
							<input type='hidden' name='maksupositio' value='$row[tunnus]'>
							<td class='back' valign='top'><input type='submit' value='".t("Poista")."'></td></form>";

					echo "</tr>";
					echo "<tr><td class='back'><br></td></tr>";

				}
				else {
					echo "<tr><td>$i</td>";
					echo "<td>$row[osuus]</td>";
					
					echo "<td>";
					
					while($prow = mysql_fetch_array($mresult)) {
						if($row["maksuehto"] == $prow["tunnus"]) echo $prow[1];
					}
					
					echo "<br>$row[kuvaus]</td>";
					echo "<td>$row[lisatiedot]<br>$row[ohje]</td>";
					echo "<td align ='right'>$row[summa]</td></tr>";
				}
				
				$erpcmpp = "";
				$erpcmkk = "";
				$erpcmvv = "";
				
				//alkuun tämä resultti
				mysql_data_seek($kuvares,0);
				mysql_data_seek($mresult,0);
			}
			
			$ihakaikki = $loput + $jaksotettavaa;
			
			//Käsin syötetty summa johon lasku pyöristetään
			if (abs($laskurow["hinta"]-$ihakaikki) <= 0.5) {
				$ihakaikki = sprintf("%.2f",$laskurow["hinta"]);
			}
			//Käsin syötetty summa johon lasku pyöristetään
			if (abs($laskurow["hinta"]-$loput-$jaksotettavaa) <= 0.5) {
				$jaksotettavaa = $laskurow["hinta"]-$loput;
			}
						
			if(round($tot,2) != $jaksotettavaa) {
				$osuusvippaus 	= round(($osuus - 100) * -1,2);
				$summavippaus 	= round($jaksotettavaa-$tot,2);
				$osuus 			= round($osuus,2);
				$tot			= round($tot,2);

				echo "<tr>";
				echo "<th><font class='error'>#</font></th>
					<th><font class='error'>$osuus %</font></th>
					<th colspan='3'><font class='error'>".t("Laskutettavana")." ".$osuus."% : $tot $yhtiorow[valkoodi]<br>".t("Muuta jotain positiota")." $osuusvippaus% : $summavippaus $yhtiorow[valkoodi]</th>";
				echo "</tr>";
			}
			echo "<tr><td class='back' colspan = '3'></td><th>".t("Jaksotettavaa yhteensä").":</th><td class='spec' align='right'>".sprintf('%.2f', $tot)."</td></tr>";
			echo "<tr><td class='back' colspan = '3'></td><th>".t("Ei jaksotettavaa yhteensä").":</th><td class='spec' align='right'>".sprintf('%.2f', $loput)."</td></tr>";
			echo "<tr><td class='back' colspan = '3'></td><th>".t("Tilaus yhteensä").":</th><td class='spec' align='right'>".sprintf('%.2f', $ihakaikki)."</td></tr>";
			echo "</table>";
		}
		else {
			echo "<font class='error'>".t("Tilauksella ei ole maksupositioita!")."</font><br><br>";
		}

		echo "<form method='post' name='maksupositiot' action='$PHP_SELF'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='tee' value='$tee'>
				<input type='hidden' name='tila' value='uusi'>
				<input type='submit' name='uusi_maksupositio' value='".t("Uusi positio")."'></form>

				<form method='post' action='$PHP_SELF'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='submit' value='".t("Takaisin tilaukselle")."'></form>";

	}
?>