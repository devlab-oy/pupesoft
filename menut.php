<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Menujen ylläpito")."</font><hr>";

	$syncyhtiot = $yhtiot;
	unset($syncyhtiot["REFERENSSI"]);

	// Synkronoidaan kahden firman menut
	if (isset($synkronoi) and count($syncyhtiot) > 1) {

		$yht = "";
		foreach ($syncyhtiot as $yhtio) {
			$yht .= "'$yhtio',";
		}

		$yht = substr($yht,0,-1);

		if ($sovellus != '') {
			$lisa = " and sovellus = '$sovellus' ";
		}
		else {
			$lisa = "";
		}

		$query = "	SELECT sovellus, nimi, alanimi, min(nimitys) nimitys, min(jarjestys) jarjestys, min(jarjestys2) jarjestys2, max(hidden) hidden
					FROM oikeu
					WHERE yhtio in ($yht)
					and kuka = ''
					and sovellus != ''
					$lisa
					GROUP BY sovellus, nimi, alanimi
					ORDER BY sovellus, jarjestys, jarjestys2";
		$result = mysql_query($query) or pupe_error($query);

		//poistetaan molemilta yhtiöiltä tämä menu
		$query = "	DELETE
					FROM oikeu
					WHERE yhtio in ($yht)
					and kuka = ''
					$lisa";
		$deleteresult = mysql_query($query) or pupe_error($query);

		$jarj  = 0;
		$jarj2 = 0;

		while ($row = mysql_fetch_array($result)) {

			if ($edsovellus != $row["sovellus"]) {
				$jarj  = 0;
				$jarj2 = 0;
			}

			if ($row["jarjestys"] != $edjarjoikea or (($row["nimi"] != $ednimi or $row["alanimi"] != $edalan) and $row["jarjestys2"] == 0 )) {
				$jarj += 10;
				$jarj2 = 0;
			}

			if ($row["jarjestys2"] != 0 and $edjarjoikea != $row["jarjestys"]) {
				$jarj2 = 10;
			}

			if ($row["jarjestys2"] != 0 and $row["jarjestys"] == $edjarjoikea) {
				$jarj2 += 10;
			}

			foreach ($syncyhtiot as $uusiyhtio) {
				$query = "	INSERT into oikeu
							SET
							kuka		= '',
							profiili	= '',
							sovellus	= '$row[sovellus]',
							nimi		= '$row[nimi]',
							alanimi 	= '$row[alanimi]',
							nimitys		= '$row[nimitys]',
							jarjestys 	= '$jarj',
							jarjestys2	= '$jarj2',
							hidden		= '$row[hidden]',
							yhtio		= '$uusiyhtio'";
				$insresult = mysql_query($query) or pupe_error($query);

				//päivitettän käyttäjien oikeudet
				$query = "	UPDATE oikeu
							SET nimitys		= '$row[nimitys]',
							jarjestys 		= '$jarj',
							jarjestys2		= '$jarj2'
							WHERE yhtio 	= '$uusiyhtio'
							and sovellus	= '$row[sovellus]'
							and nimi 		= '$row[nimi]'
							and alanimi		= '$row[alanimi]'";
				$updresult = mysql_query($query) or pupe_error($query);
			}

			$edsovellus 	= $row["sovellus"];
			$edjarjoikea 	= $row["jarjestys"];
			$ednimi 		= $row["nimi"];
			$adalan 		= $row["alanimi"];
		}
	}

	if ((isset($synkronoireferenssi) OR isset($synkronoireferenssialapaivita)) and count($syncyhtiot) > 0) {

		$ch  = curl_init();
		curl_setopt ($ch, CURLOPT_URL, "http://api.devlab.fi/referenssivalikot.sql");
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_HEADER, FALSE);
		$referenssit = curl_exec ($ch);
		$referenssit = explode("\n", trim($referenssit));

		// Eka rivi roskikseen
		array_shift($referenssit);

		$rows = array();

		foreach ($referenssit as $rivi) {

			// luetaan rivi tiedostosta..
			$rivi = explode("\t", trim($rivi));

			if ($sovellus == '' or strtoupper($sovellus) == strtoupper($rivi[0])) {

				$row = array();
				$row["sovellus"] 	= $rivi[0];
				$row["nimi"] 		= $rivi[1];
				$row["alanimi"] 	= $rivi[2];
				$row["nimitys"] 	= $rivi[3];
				$row["jarjestys"] 	= (int) $rivi[4];
				$row["jarjestys2"] 	= (int) $rivi[5];
				$row["hidden"] 		= $rivi[6];
				$row["tunnus"] 		= $rivi[7];

				$rows[$row["sovellus"].$row["nimi"].$row["alanimi"]] = $row;
			}
		}

		$yht = "";
		foreach ($syncyhtiot as $yhtio) {
			$yht .= "'$yhtio',";
		}

		$yht = substr($yht,0,-1);

		if ($sovellus != '') {
			$lisa = " and sovellus	= '$sovellus' ";
		}
		else {
			$lisa = "";
		}

		$query = "	SELECT sovellus, nimi, alanimi, min(nimitys) nimitys, min(jarjestys)-1 jarjestys, min(jarjestys2) jarjestys2, max(hidden) hidden
					FROM oikeu
					WHERE yhtio in ($yht)
					and kuka = ''
					$lisa
					GROUP BY sovellus, nimi, alanimi
					ORDER BY sovellus, jarjestys, jarjestys2";
		$result = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($result)) {
			if (!array_key_exists($row["sovellus"].$row["nimi"].$row["alanimi"], $rows)) {
				$rows[$row["sovellus"].$row["nimi"].$row["alanimi"]] = $row;
			}
		}

		// Sortataan array niin että omat privaatit lisäykset tulee sopivaan rakoon referenssiin nähden
		$jarj0 = $jarj1 = $jarj2 = array();
		foreach ($rows as $key => $row) {
			$jarj0[$key] = $row['sovellus'];
		    $jarj1[$key] = $row['jarjestys'];
		    $jarj2[$key] = $row['jarjestys2'];
		}

		array_multisort($jarj0, SORT_ASC, $jarj1, SORT_ASC, $jarj2, SORT_ASC, $rows);

		$jarj  = 0;
		$jarj2 = 0;

		foreach ($rows as $row) {

			if ($edsovellus != $row["sovellus"]) {
				$jarj  = 0;
				$jarj2 = 0;
			}

			if ($row["jarjestys"] != $edjarjoikea or (($row["nimi"] != $ednimi or $row["alanimi"] != $edalan) and $row["jarjestys2"] == 0 )) {
				$jarj += 10;
				$jarj2 = 0;
			}

			if ($row["jarjestys2"] != 0 and $edjarjoikea != $row["jarjestys"]) {
				$jarj2 = 10;
			}

			if ($row["jarjestys2"] != 0 and $row["jarjestys"] == $edjarjoikea) {
				$jarj2 += 10;
			}

			foreach ($syncyhtiot as $yhtio) {
				$query = "	SELECT *
							FROM oikeu
							WHERE yhtio 	= '$yhtio'
							and kuka 		= ''
							and sovellus	= '$row[sovellus]'
							and nimi		= '$row[nimi]'
							and alanimi		= '$row[alanimi]'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 0 and $row["sovellus"] != "") {

					$query = "	INSERT into oikeu
								SET
								kuka		= '',
								profiili	= '',
								sovellus	= '$row[sovellus]',
								nimi		= '$row[nimi]',
								alanimi 	= '$row[alanimi]',
								nimitys		= '$row[nimitys]',
								jarjestys 	= '$jarj',
								jarjestys2	= '$jarj2',
								hidden		= '$row[hidden]',
								yhtio		= '$yhtio'";
					$insresult = mysql_query($query) or pupe_error($query);
				}

				//päivitettän käyttäjien oikeudet
				if (isset($synkronoireferenssialapaivita)) {
					$jarjestysupdate = "";
				}
				else {
					$jarjestysupdate = ", jarjestys = '$jarj', jarjestys2 = '$jarj2'";
				}

				$query = "	UPDATE oikeu
							SET nimitys		= '$row[nimitys]'
							$jarjestysupdate
							WHERE yhtio 	= '$yhtio'
							and sovellus	= '$row[sovellus]'
							and nimi 		= '$row[nimi]'
							and alanimi		= '$row[alanimi]'";
				$updresult = mysql_query($query) or pupe_error($query);
			}

			$edsovellus 	= $row["sovellus"];
			$edjarjoikea 	= $row["jarjestys"];
			$ednimi 		= $row["nimi"];
			$adalan 		= $row["alanimi"];
		}
	}

	if ($tee == "PAIVITAJARJETYS") {
		foreach ($jarjestys as $tun => $jarj) {

			$query  = "	SELECT *
						FROM oikeu
						WHERE tunnus='$tun'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {

				$row = mysql_fetch_array($result);

				//päivitetään uudet menun tiedot kaikille käyttäjille
				$query = "	UPDATE oikeu
							SET jarjestys = '$jarj', jarjestys2 = '$jarjestys2[$tun]'
							WHERE yhtio		= '$row[yhtio]'
							and sovellus	= '$row[sovellus]'
							and nimi		= '$row[nimi]'
							and alanimi		= '$row[alanimi]'
							and nimitys		= '$row[nimitys]'
							and jarjestys	= '$row[jarjestys]'
							and jarjestys2	= '$row[jarjestys2]'
							and hidden		= '$row[hidden]'";
				$result = mysql_query($query) or pupe_error($query);
				$num1 = mysql_affected_rows();
			}
		}

		echo "<font class='message'>".t("Järjestykset päivitetty")."!<br><br></font>";


		$yhtiot = array();
		$yht = str_replace("'","", $yht);
		$yht = explode(",", $yht);

		foreach($yht as $yhtio) {
			$yhtiot[$yhtio] = $yhtio;
		}

		$tee = "";
	}

	if ($tee == "PAIVITA") {
		if ($kopioi == 'on') {
			$tunnus = '';
		}

		if ($tunnus != '')	{
			$query  = "	SELECT *
						FROM oikeu
						WHERE tunnus='$tunnus'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {

				$row = mysql_fetch_array($result);

				$yht = str_replace(",","','",$yht);
				$yht = "'".$yht."'";

				//päivitetään uudet menun tiedot kaikille käyttäjille
				$query = "	UPDATE oikeu
							SET sovellus='$sove', nimi='$nimi', alanimi='$alanimi', nimitys='$nimitys', jarjestys='$jarjestys', jarjestys2='$jarjestys2', hidden='$hidden'
							WHERE
							sovellus		= '$row[sovellus]'
							and nimi		= '$row[nimi]'
							and alanimi		= '$row[alanimi]'
							and nimitys		= '$row[nimitys]'
							and jarjestys	= '$row[jarjestys]'
							and jarjestys2	= '$row[jarjestys2]'
							and hidden		= '$row[hidden]'
							and yhtio in ($yht)";
				$result = mysql_query($query) or pupe_error($query);
				$num1 = mysql_affected_rows();

				echo "<font class='message'>$num1 ".t("riviä päivitetty")."!<br></font>";
			}

			$yhtiot = array();
			$yht = str_replace("'", "", $yht);
			$yht = explode(",", $yht);

			foreach($yht as $yhtio) {
				$yhtiot[$yhtio] = $yhtio;
			}
		}
		else {
			$yhtiot = array();
			$yht = str_replace("'","", $yht);
			$yht = explode(",", $yht);

			foreach($yht as $yhtio) {
				$yhtiot[$yhtio] = $yhtio;

				if ($yhtio != "REFERENSSI") {
					$query = "INSERT into oikeu (kuka, sovellus, nimi, alanimi, nimitys, jarjestys, jarjestys2, yhtio, hidden)
								values ('', '$sove', '$nimi', '$alanimi', '$nimitys', '$jarjestys', '$jarjestys2', '$yhtio', '$hidden')";
					$result = mysql_query($query) or pupe_error($query);
					$num = mysql_affected_rows();

					echo "<font class='message'>$num ".t("riviä lisätty")."!<br></font>";
				}
			}
		}

		$tee = "";
	}

	if ($tee == "MUUTA") {
		echo "<form method='post' action='menut.php'>";
		echo "<input type='hidden' name='tee' value='PAIVITA'>";
		echo "<input type='hidden' name='sovellus' value='$sovellus'>";
		echo "<input type='hidden' name='yht' value='$yht'>";
		echo "<input type='hidden' name='tunnus' value='$tunnus'>";

		if ($tunnus > 0) {
			$query  = "	SELECT *
						from oikeu
						where tunnus='$tunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($result);

			$sove		= $row['sovellus'];
			$nimi		= $row['nimi'];
			$alanimi	= $row['alanimi'];
			$nimitys	= $row['nimitys'];
			$jarjestys	= $row['jarjestys'];
			$jarjestys2	= $row['jarjestys2'];
			$hidden		= $row['hidden'];
		}
		else {
			$sove		= "";
			$nimi		= "";
			$alanimi	= "";
		    $nimitys	= "";
		    $jarjestys	= "";
		    $jarjestys2	= "";
		    $hidden		= "";
		}

		echo "<table>
				<tr><th>".t("Muokataan yhtöille")."</th><td>".str_replace(",REFERENSSI" , "", $yht)."</td></tr>
				<tr><th>".t("Sovellus")."</th><td><input type='text' name='sove' value='$sove'></td></tr>
				<tr><th>".t("Nimi")."</th><td><input type='text' name='nimi' value='$nimi'></td></tr>
				<tr><th>".t("Alanimi")."</th><td><input type='text' name='alanimi' value='$alanimi'></td></tr>
				<tr><th>".t("Nimitys")."</th><td><input type='text' name='nimitys' value='$nimitys'></td></tr>
				<tr><th>".t("Järjestys")."</th><td><input type='text' name='jarjestys' value='$jarjestys'></td></tr>
				<tr><th>".t("Järjestys2")."</th><td><input type='text' name='jarjestys2' value='$jarjestys2'></td></tr>";

		if ($hidden != '') {
			$chk = "CHECKED";
		}
		else {
			$chk = "";
		}

		echo "	<tr><th>".t("Piilossa")."</th><td><input type='checkbox' name='hidden' value='H' $chk></td></tr>
				<tr><th>".t("Kopioi")."</th><td><input type='checkbox' name='kopioi'></td></tr>
				</table>
				<br>
				<input type='submit' value='".t("Päivitä")."'>
				</form>";

		if ($tunnus > 0) {
			echo "<form method='post' action='menut.php'>";
			echo "<input type='hidden' name='tee' value='POISTA'>";
			echo "<input type='hidden' name='sovellus' value='$sovellus'>";
			echo "<input type='hidden' name='yht' value='$yht'>";
			echo "<input type='hidden' name='tunnus' value='$tunnus'>";
			echo "<input type='submit' value='*".t("Poista")." $nimitys*'>";
			echo "</form>";
		}
	}

	if ($tee == 'POISTA') {
		// haetaan poistettavan rivin alkuperäiset tiedot
		$query  = "	SELECT *
					from oikeu
					where tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 1) {

			$row    = mysql_fetch_array($result);

			$yht = str_replace(",","','",$yht);
			$yht = "'".$yht."'";

			//päivitetään uudet menun tiedot kaikille käyttäjille
			$query = "	DELETE from oikeu
						WHERE sovellus	= '$row[sovellus]'
						and nimi		= '$row[nimi]'
						and alanimi		= '$row[alanimi]'
						and nimitys		= '$row[nimitys]'
						and jarjestys	= '$row[jarjestys]'
						and jarjestys2	= '$row[jarjestys2]'
						and yhtio in ($yht)";
			$result = mysql_query($query) or pupe_error($query);
			$num1 = mysql_affected_rows();

			echo "<font class='message'>$num1 ".t("riviä poistettu")."!<br></font>";
		}

		$yhtiot = array();
		$yht = str_replace("'","", $yht);
		$yht = explode(",", $yht);

		foreach($yht as $yhtio) {
			$yhtiot[$yhtio] = $yhtio;
		}

		$tee = "";
	}

	if ($tee == "") {
		echo "<form method='post' action='menut.php'><table>";

		$query	= "	SELECT distinct yhtio, nimi
					from yhtio
					where yhtio='$kukarow[yhtio]' or (konserni = '$yhtiorow[konserni]' and konserni != '')";
		$result = mysql_query($query) or pupe_error($query);

		$sovyhtiot = "";

		while ($prow = mysql_fetch_array($result)) {

			if($yhtiot[$prow["yhtio"]] != "") {
				$chk = "CHECKED";
			}
			else {
				$chk = "";
			}

			echo "<tr><th>".t("Näytä yhtiö").":</th><td><input type='checkbox' name='yhtiot[$prow[yhtio]]' value='$prow[yhtio]' $chk onclick='submit();'> $prow[nimi]</td></tr>";
			$sovyhtiot .= "'$prow[yhtio]',";
		}

		if($yhtiot["REFERENSSI"] != "") {
			$chk = "CHECKED";
		}
		else {
			$chk = "";
		}

		echo "<tr><th>".t("Näytä referenssivalikot").":</th><td><input type='checkbox' name='yhtiot[REFERENSSI]' value='REFERENSSI' $chk onclick='submit();'></td></tr>";

		$sovyhtiot = substr($sovyhtiot,0,-1);

		$query = "	SELECT distinct sovellus
					FROM oikeu
					where yhtio in ($sovyhtiot)
					and kuka=''
					order by sovellus";
		$result = mysql_query($query) or pupe_error($query);

		echo "<tr><th>".t("Valitse sovellus").":</th><td><select name='sovellus' onchange='submit();'>";

		echo "<option value=''>".t("Näytä kaikki").":</option>";

		while ($orow = mysql_fetch_array($result)) {
			$sel = '';
			if ($sovellus == $orow["sovellus"]) {
				$sel = "SELECTED";
			}

			echo "<option value='$orow[sovellus]' $sel>".t($orow["sovellus"])."</option>";
		}
		echo "</select></td></tr>";

		if (count($yhtiot) > 1) {
			echo "<tr><th>".t("Synkronoi").":</th><td><input type='submit' name='synkronoi' value='".t("Synkronoi")."'></td></tr>";
		}

		echo "<tr><th>".t("Synkronoi referenssiin").":</th><td><input type='submit' name='synkronoireferenssi' value='".t("Synkronoi")."'></td></tr>";
		echo "<tr><th>".t("Synkronoi referenssiin")." ".t("älä päivitä järjestyksiä").":</th><td><input type='submit' name='synkronoireferenssialapaivita' value='".t("Synkronoi")."'></td></tr>";

		echo "</form>";


		if (count($yhtiot) > 0) {
			$yht = "";
			foreach($yhtiot as $yhtio) {
				$yht .= "$yhtio,";
			}
			$yht = substr($yht,0,-1);

			echo "<form method='post' action='menut.php'>";
			echo "<input type='hidden' name='tee' value='MUUTA'>";
			echo "<input type='hidden' name='sovellus' value='$sovellus'>";
			echo "<input type='hidden' name='sove' value='$sovellus'>";
			echo "<input type='hidden' name='yht' value='$yht'>";
			echo "<tr><th>".t("Uusi valikko").":</th><td><input type='submit' value='".t("Lisää")."'></td></tr>";
			echo "</form>";
		}

		echo "</table><br>";
		echo "<table><tr>";

		if (count($yhtiot) > 0) {

			$dirikka = getcwd();

			foreach ($yhtiot as $yhtio) {
				echo "<td class='back' valign='top'>";

				$rows = array();

				if ($yhtio == "REFERENSSI") {
					$ch  = curl_init();
					curl_setopt ($ch, CURLOPT_URL, "http://api.devlab.fi/referenssivalikot.sql");
					curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt ($ch, CURLOPT_HEADER, FALSE);
					$referenssit = curl_exec ($ch);
					$referenssit = explode("\n", trim($referenssit));

					// Eka rivi roskikseen
					array_shift($referenssit);

					$rows = array();

					foreach ($referenssit as $rivi) {

						// luetaan rivi tiedostosta..
						$rivi = explode("\t", trim($rivi));

						if ($sovellus == '' or strtoupper($sovellus) == strtoupper($rivi[0])) {
							$rows[$lask]["sovellus"] 	= $rivi[0];
							$rows[$lask]["nimi"] 		= $rivi[1];
							$rows[$lask]["alanimi"] 	= $rivi[2];
							$rows[$lask]["nimitys"] 	= $rivi[3];
							$rows[$lask]["jarjestys"] 	= (int) $rivi[4];
							$rows[$lask]["jarjestys2"] 	= (int) $rivi[5];
							$rows[$lask]["hidden"] 		= $rivi[6];
							$rows[$lask]["tunnus"] 		= $rivi[7];
						}

						$lask++;
					}
				}
				else {

					echo "<form method='post' action='menut.php'>";
					echo "<input type='hidden' name='tee' value='PAIVITAJARJETYS'>";
					echo "<input type='hidden' name='sovellus' value='$sovellus'>";
					echo "<input type='hidden' name='yht' value='$yht'>";

					$query	= "	SELECT sovellus, nimi, alanimi, nimitys, jarjestys, jarjestys2, hidden, tunnus
								from oikeu
								where kuka = ''
								and yhtio = '$yhtio'";

					if ($sovellus != '') {
						$query .= " and sovellus='$sovellus'";
					}

					$query .= " order by sovellus, jarjestys, jarjestys2";
					$result = mysql_query($query) or pupe_error($query);

					$lask = 0;

					while ($prow = mysql_fetch_array($result)) {
						$rows[$lask]["sovellus"] 	= $prow["sovellus"];
						$rows[$lask]["nimi"] 		= $prow["nimi"];
						$rows[$lask]["alanimi"] 	= $prow["alanimi"];
						$rows[$lask]["nimitys"] 	= $prow["nimitys"];
						$rows[$lask]["jarjestys"] 	= $prow["jarjestys"];
						$rows[$lask]["jarjestys2"] 	= $prow["jarjestys2"];
						$rows[$lask]["hidden"] 		= $prow["hidden"];
						$rows[$lask]["tunnus"] 		= $prow["tunnus"];

						$lask++;
					}
				}

				echo "<table>";

				$vsove = "";

				foreach ($rows as $row) {
					$tunnus 	= $row['tunnus'];
					$sove		= $row['sovellus'];
					$nimi		= $row['nimi'];
					$alanimi	= $row['alanimi'];
					$nimitys	= $row['nimitys'];
					$jarjestys	= $row['jarjestys'];
					$jarjestys2	= $row['jarjestys2'];
					$hidden		= $row['hidden'];

					if ($vsove != $sove) {
						echo "<tr><td class='back' colspan='4'><br></td></tr>\n";
						echo "<tr>
								<th colspan='2' nowrap>".t("Sovellus").": ".t($sove)." $yhtio</th>
								<th nowrap>".t("Alanimi")."</th>
								<th nowrap>".t("Nimitys")."</th>
								<th nowrap>".t("J1")."</th>
								<th nowrap>".t("J2")."</th>
								<th nowrap>".t("Piilossa")."</th>
							</tr>\n";
					}

					echo "<tr>";

					if ($jarjestys2!='0') {
						echo "<td class='back' nowrap>--></td><td>";
					}
					else {
						echo "<td colspan='2' nowrap>";
					}

					if (!file_exists($dirikka."/".$nimi)) {
						$mordor1 = "<font class='error'>";
						$mordor2 = "</font>";
					}
					else {
						$mordor1 = $mordor2 = "";
					}

					if ($yhtio == "REFERENSSI") {
							echo "$mordor1$nimi$mordor2</td>";
							echo "<td nowrap>$alanimi</td>";
							echo "<td nowrap>".t($nimitys)."</td>";
							echo "<td nowrap><input type='text' size='4' value='$jarjestys' DISABLED></td>";
							echo "<td nowrap><input type='text' size='4' value='$jarjestys2' DISABLED></td>";
							echo "<td nowrap>$hidden</td></tr>\n";
							echo "</form>";
					}
					else {
						echo "<a href='$PHP_SELF?tee=MUUTA&tunnus=$tunnus&yht=$yht&sovellus=$sovellus'>$mordor1$nimi$mordor2</a></td>";
						echo "<td nowrap>$alanimi</td>";
						echo "<td nowrap>".t($nimitys)."</td>";
						echo "<td nowrap><input type='text' size='4' name='jarjestys[$tunnus]' value='$jarjestys'></td>";
						echo "<td nowrap><input type='text' size='4' name='jarjestys2[$tunnus]' value='$jarjestys2'></td>";
						echo "<td nowrap>$hidden</td>";
					}

					$vsove = $sove;
				}

				echo "</table>";

				if ($yhtio != "REFERENSSI") {
					echo "<input type='submit' value='".t("Päivitä järjestykset")."'>\n";
					echo "</form>";
				}

				echo "</td>";
			}
		}
		echo "</tr></table>";
	}

	require ("inc/footer.inc");

?>