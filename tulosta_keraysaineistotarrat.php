<?php
	require("inc/parametrit.inc");
	echo "<font class='head'>".t("Tulosta keräyserätarroja")."</font><hr>";
	
	$debug = 0;

	if ($debug == 1) {
		echo "tee=$tee<br>";
		echo "kirjoitin=$kirjoitin|kirjoitinvan=$kirjoitinvan<br>";
	}
	
	if ($tee == 'uudet') {
		$komento = $kirjoitin;
		require('inc/tulosta_keraysaineistotarrat_tec.inc');
	}
	elseif ($tee == 'vanhat') {
		$komento = $kirjoitinvan;
		require('inc/tulosta_keraysaineistotarrat_tec.inc');
	}	
	
	
	if ($tee == '') {
		$query =	"SELECT ytunnus, nimi, toim_nimi, count(tilausrivi.tunnus) riveja, sum(tilkpl) myyntieria
					FROM lasku, tilausrivi
					WHERE lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
					and lasku.yhtio = '$kukarow[yhtio]' and tila = 'Z' and alatila = 'A' and tyyppi = 'Z'
					GROUP BY 1,2,3
					ORDER BY 1,2,3";
		$tarrares = mysql_query($query) or pupe_error($query);
		if ($debug == 1) {
			echo "$query<br>";
			echo "".mysql_num_rows($tarrares)."<br>";
		}
		
		echo "<font class='message'>".t("Tulostamattomat")."</font><br><br>";
		
		if (mysql_num_rows($tarrares) > 0) {
			echo "<form action = '$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='tee' value='uudet'>";
			echo "<table>";
			echo "<tr><th>".t("Ytunnus")."</th><th>".t("Nimi")."</th><th>".t("Toim.Nimi")."</th><th>".t("Rivejä")."</th><th>".t("Myyntieriä")."</th></tr>";
			
			$yhtriveja	= 0;
			$yhteria	= 0;
			
			while($tarrarow = mysql_fetch_array($tarrares)) {
				echo "<tr><td>$tarrarow[ytunnus]</td><td>$tarrarow[nimi]</td><td>$tarrarow[toim_nimi]</td><td>$tarrarow[riveja]</td><td>$tarrarow[myyntieria]</td></tr>";
				
				$yhtriveja += $tarrarow['riveja'];
				$yhteria += $tarrarow['myyntieria'];
				
			}
			echo "<tr><td colspan='3'>".t("Yhteensä").":</td><td>$yhtriveja</td><td>$yhteria</td></tr>";
			
			$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]'";
			$kires = mysql_query($query) or pupe_error($query);
			echo "<tr><td class='back'><select name='kirjoitin'>";
			echo "<option value='$kirow[komento]'>".t("Valitse kirjoitin")."</option>";
			while ($kirow=mysql_fetch_array($kires))
			{
				if ($kirow['komento']==$kirjoitin) $select='SELECTED';
				else $select = '';

				echo "<option value='$kirow[komento]' $select>$kirow[kirjoitin]</option>";
			}

			echo "</select></td>";
			echo "<td class='back'><input type='Submit' value='".t("Tulosta nämä")."'></td></tr>";
			echo "</table>";
			echo "</form><br>";
		}
		else {
			echo "".t("Ei tulostamattomia keräyserätarroja")."";
		}
		
		echo "<hr>";
		echo "<font class='message'>".t("Tulosta vanhoja")."</font><br>";
		
		echo "<br>";
		echo "<table><form method='post' action='$PHP_SELF'>";

		// ehdotetaan 7 päivää taaksepäin
		if (!isset($kka))
			$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($vva))
			$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($ppa))
			$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

		if (!isset($kkl))
			$kkl = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($vvl))
			$vvl = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		if (!isset($ppl))
			$ppl = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

		echo "<input type='hidden' name='tee' value='vanhat'>";
		echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
		$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]'";
		$kires2 = mysql_query($query) or pupe_error($query);
		echo "<tr><td class='back'><select name='kirjoitinvan'>";
		echo "<option value='$kirow2[komento]'>".t("Valitse kirjoitin")."</option>";
		while ($kirow2=mysql_fetch_array($kires2))
		{
			if ($kirow2['komento']==$kirjoitinvan) $select='SELECTED';
			else $select = '';

			echo "<option value='$kirow2[komento]' $select>$kirow2[kirjoitin]</option>";
		}

		echo "</select>";
		echo "<input type='submit' value='".t("Lähetä")."'></td></tr></table>";
		
	}
	
	require("inc/footer.inc");
?>