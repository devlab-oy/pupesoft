<?php

echo "<font class='head'>Tilauksen maksusopimus</font><hr><br>";

if($tila == "uusi") {
	$query = "	INSERT INTO maksupositio
				SET yhtio 	= '$kukarow[yhtio]',
				otunnus 	= '$laskurow[tunnus]',
				luotu		= now(),
				luonut		= '$kukarow[kuka]'";
	$result = mysql_query($query) or pupe_error($query);

	if ($laskurow["jaksotettu"] == 0) {
		$query = "	UPDATE lasku
					SET jaksotettu=tunnus
					WHERE tunnus = '$laskurow[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	UPDATE tilausrivi
					SET jaksotettu='J'
					WHERE otunnus = '$laskurow[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);
	}
}

if($tila == "tallenna") {

	$query = "	SELECT
				round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * tilausrivi.varattu * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100))),2) summa
	 			FROM  lasku
				JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D' and tilausrivi.jaksotettu='J'
				WHERE lasku.yhtio ='$kukarow[yhtio]' and lasku.tunnus ='$laskurow[tunnus]'";
	$result = mysql_query($query) or pupe_error($query);
	$rivrow = mysql_fetch_array($result);

	$query = "	SELECT *
				FROM maksupositio
				WHERE yhtio ='$kukarow[yhtio]' and tunnus ='$maksupositio'";
	$result = mysql_query($query) or pupe_error($query);
	$posrow = mysql_fetch_array($result);

	if ($posrow["summa"] != $summa and $summa > 0) {
		//Lasketaan prossa annetun summan perusteella
		$osuus = round($summa / $rivrow["summa"] * 100,4);
	}
	elseif($posrow["osuus"] != $osuus and $osuus > 0) {
		//Lasketaan summa annetun prossan perusteella
		$summa = round($osuus/100 * $rivrow["summa"],2);
	}

	$query = "	UPDATE maksupositio SET
				osuus 		= '$osuus',
				summa  		= '$summa',
				kuvaus 		= '$kuvaus',
				maksuehto 	= '$mehto',
				lisatiedot 	= '".addslashes($lisatiedot)."',
				ohje 		= '".addslashes($ohje)."'
				WHERE otunnus = '$laskurow[tunnus]' and tunnus='$maksupositio'";
	$result = mysql_query($query) or pupe_error($query);
}

if($tila == "poista") {
	$query = "	DELETE FROM maksupositio WHERE yhtio = '$kukarow[yhtio]' and tunnus='$maksupositio'";
	$result = mysql_query($query) or pupe_error($query);

	$query = "	SELECT *
				FROM maksupositio
				WHERE yhtio ='$kukarow[yhtio]' and otunnus ='$laskurow[tunnus]'";
	$result = mysql_query($query) or pupe_error($query);

	if(mysql_num_rows($result) == 0) {
		$query = "	UPDATE lasku
					SET jaksotettu=0
					WHERE tunnus = '$laskurow[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	UPDATE tilausrivi
					SET jaksotettu=''
					WHERE otunnus = '$laskurow[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);
	}
}

// Maksopositiot
$query = "	SELECT *
			FROM maksuehto
			WHERE yhtio ='$kukarow[yhtio]'
			and tunnus = '$laskurow[maksuehto]'
			and jaksotettu != ''";
$result = mysql_query($query) or pupe_error($query);

if(mysql_num_rows($result) == 1) {

	$tot = 0;

	$query	= "	SELECT *
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]'
				and tunnus='$laskurow[liitostunnus]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$asiakasrow = mysql_fetch_array($result);
	}

	// Haetaan laskun summat
	$query = "	SELECT
				round(sum(if(tilausrivi.jaksotettu='J', tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * tilausrivi.varattu * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)), 0)),2) jaksotettavaa,
				round(sum(if(tilausrivi.jaksotettu='' , tilausrivi.hinta / if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * tilausrivi.varattu * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)), 0)),2) loput
	 			FROM lasku
				JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D'
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus  = '$laskurow[tunnus]'
				GROUP by lasku.tunnus";
	$result = mysql_query($query) or pupe_error($query);
	$rivrow = mysql_fetch_array($result);


	// Haetaan kaikki tämän tilauksen laskutuspositiot (käytetään alvittomia rivihintoja)
	$query = "	SELECT *
				FROM maksupositio
				WHERE yhtio ='$kukarow[yhtio]'
				and otunnus ='$laskurow[tunnus]'
				ORDER BY tunnus";
	$result = mysql_query($query) or pupe_error($query);

	if(mysql_num_rows($result) > 0) {
		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'LASKUKUVAUS'
					ORDER BY jarjestys, selite";
		$kuvares = mysql_query($query) or pupe_error($query);

		$query = "	SELECT tunnus, concat_ws(' ', kassa_teksti, teksti)
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]' and jaksotettu=''
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
					elseif($asiakasrow["maksuehto"] == $prow["tunnus"]) $sel = "SELECTED";
					else $sel = "";

					echo "<option value='$prow[0]' $sel>$prow[1]</option>";
				}
				echo "</select></td>";

				echo "<td><input type='text' size='30' name='lisatiedot' value='$row[lisatiedot]'></td>";
				echo "<td align ='right'><input type='text' size='6'  name='summa' value='".sprintf('%.2f', $row["summa"])."'></td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td></td><td></td><td><select name='kuvaus'>";

				while($prow= mysql_fetch_array($kuvares)) {
					if($row["kuvaus"] == $prow["selitetark"]) $sel = "SELECTED";
					else $sel = "";

					echo "<option value='$prow[selitetark]' $sel>$prow[selitetark]</option>";
				}
				echo "</select></td>";

				//alkuun tämä resultti
				mysql_data_seek($kuvares,0);
				mysql_data_seek($mresult,0);

				echo "<td><textarea rows='2' cols='30' name='ohje'>$row[ohje]</textarea></td>";
				echo "<td></td>";

				echo "	<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='tee' value='$tee'>
						<input type='hidden' name='tila' value='tallenna'>
						<input type='hidden' name='maksupositio' value='$row[tunnus]'>
						<td class='back'><input type='submit' value='".t("Tallenna")."'></td></form>";

				echo "<form method='post' name='maksupositiot' action='$PHP_SELF'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tilausnumero' value='$tilausnumero'>
						<input type='hidden' name='tee' value='$tee'>
						<input type='hidden' name='tila' value='poista'>
						<input type='hidden' name='maksupositio' value='$row[tunnus]'>
						<td class='back'><input type='submit' value='".t("Poista")."'></td></form>";

				echo "</tr>";
				echo "<tr><td class='back'><br></td></tr>";

			}
			else {
				echo "<tr><td>$i</td>";
				echo "<td>$row[osuus]</td>";
				echo "<td>$row[mehto_teksti]<br>-$row[kuvaus]</td>";
				echo "<td>$row[lisatiedot]</td>";
				echo "<td>$row[ohje]</td>";
				echo "<td align ='right'>$row[summa]</td></tr>";
			}
		}

		if($tot != $rivrow["jaksotettavaa"]) {
			$osuusvippaus = ($osuus - 100) * -1;
			$summavippaus = $rivrow["jaksotettavaa"]-$tot;

			echo "<tr>";
			echo "<th><font class='error'>#</font></th>
				<th><font class='error'>$osuus %</font></th>
				<th colspan='3'><font class='error'>".t("Laskutettavana")." ".$osuus."% : $tot $yhtiorow[valkoodi]<br>".t("Muuta jotain positiota")." $osuusvippaus% : $summavippaus $yhtiorow[valkoodi]</th>";
			echo "</tr>";
		}

		$ihakaikki = $rivrow["loput"] + $rivrow["jaksotettavaa"];

		echo "<tr><td class='back' colspan = '3'></td><th>".t("Jaksotettavaa yhteensä").":</th><td class='spec' align='right'>".sprintf('%.2f', $tot)."</td></tr>";
		echo "<tr><td class='back' colspan = '3'></td><th>".t("Ei jaksotettavaa yhteensä").":</th><td class='spec' align='right'>".sprintf('%.2f', $rivrow["loput"])."</td></tr>";
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