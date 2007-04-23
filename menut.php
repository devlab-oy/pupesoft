<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Menujen ylläpito")."</font><hr>";

	// Synkronoidaan kahden firman menut
	if (isset($synkronoi)) {

		if (count($yhtiot) > 0) {
			foreach($yhtiot as $yhtio) {
				$yht .= "'$yhtio',";
			}
			$yht = substr($yht,0,-1);
		}

		$lisa = "";
		if ($sovellus != '') {
			$lisa = " and sovellus	= '$sovellus' ";
		}

		$query = "	SELECT sovellus, nimi, alanimi, min(nimitys) nimitys, min(jarjestys) jarjestys, min(jarjestys2) jarjestys2, max(hidden) hidden
					FROM oikeu
					WHERE oikeu.yhtio in ($yht)
					and kuka = ''
					$lisa
					GROUP BY sovellus, nimi, alanimi
					ORDER BY sovellus, jarjestys, jarjestys2";
		$result = mysql_query($query) or pupe_error($query);

		//poistetaan molemilta yhtiöiltä tämä menu
		$query = "	DELETE
					FROM oikeu
					WHERE oikeu.yhtio in ($yht)
					and kuka		= ''
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

			foreach($yhtiot as $uusiyhtio) {
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

	if ($tee == "PAIVITA") {
		if ($kopioi == 'on') {
			$tunnus ='';
		}
		if ($tunnus != '')	{		// haetaan muutettavan rivin alkuperäiset tiedot
			$query  = "	select *
						from oikeu
						where tunnus='$tunnus'";
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
			$yht = str_replace("'","", $yht);
			$yht = explode(",", $yht);

			foreach($yht as $yhtio) {
				$yhtiot[] = $yhtio;
			}
		}
		else {
			$yhtiot = array();
			$yht = str_replace("'","", $yht);
			$yht = explode(",", $yht);

			foreach($yht as $yhtio) {
				$yhtiot[] = $yhtio;

				$query = "INSERT into oikeu (kuka, sovellus, nimi, alanimi, nimitys, jarjestys, jarjestys2, yhtio, hidden)
							values ('', '$sove', '$nimi', '$alanimi', '$nimitys', '$jarjestys', '$jarjestys2', '$yhtio', '$hidden')";
				$result = mysql_query($query) or pupe_error($query);

				$num=mysql_affected_rows();

				echo "<font class='message'>$num ".t("riviä lisätty")."!<br></font>";

			}
		}

		$tee = "";
	}

	if ($tee == "MUUTA") {
		echo "<form method='post' action='$PHP_SELF'>	";
		echo "<input type='hidden' name='tee' value='PAIVITA'>";
		echo "<input type='hidden' name='sovellus' value='$sovellus'>";
		echo "<input type='hidden' name='yht' value='$yht'>";
		echo "<input type='hidden' name='tunnus' value='$tunnus'>";

		if ($tunnus > 0) {
			$query  = "select * from oikeu where tunnus='$tunnus'";
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
				<tr><th>".t("Muokataan yhtöille")."</th><td>$yht</td></tr>
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
			echo "<form method='post' action='$PHP_SELF'>	";
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
		$query  = "select * from oikeu where tunnus='$tunnus'";
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
			$yhtiot[] = $yhtio;
		}

		$tee = "";
	}

	if ($tee == "") {
		echo "<form action='$PHP_SELF' method='post'><table>";

		$query	= "	SELECT distinct yhtio, nimi
					from yhtio
					where yhtio='$kukarow[yhtio]' or (konserni = '$yhtiorow[konserni]' and konserni != '')";
		$result = mysql_query($query) or pupe_error($query);

		$sovyhtiot = "";

		while ($prow = mysql_fetch_array($result)) {

			$chk = "";

			if (count($yhtiot) > 0) {
				foreach($yhtiot as $prof) {
					if ($prow["yhtio"] == $prof) {
						$chk = "CHECKED";
					}
				}
			}

			echo "<tr><th>Näytä yhtiö:</th><td><input type='checkbox' name='yhtiot[]' value='$prow[yhtio]' $chk onclick='submit();'> $prow[nimi]</td></tr>";
			$sovyhtiot .= "'$prow[yhtio]',";
		}

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
		echo "</form>";


		if (count($yhtiot) > 0 and $yhtiot[0] != '') {
			$yht = "";
			foreach($yhtiot as $yhtio) {
				$yht .= "$yhtio,";
			}
			$yht = substr($yht,0,-1);

			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='MUUTA'>";
			echo "<input type='hidden' name='sovellus' value='$sovellus'>";
			echo "<input type='hidden' name='sove' value='$sovellus'>";
			echo "<input type='hidden' name='yht' value='$yht'>";
			echo "<tr><th>".t("Uusi valikko").":</th><td><input type='submit' value='".t("Lisää")."'></td></tr>";
			echo "</form>";
		}

		echo "</table><br>";
		echo "<table><tr>";

		if (count($yhtiot) > 0 and $yhtiot[0] != '') {
			foreach($yhtiot as $yhtio) {
				echo "<td class='back' valign='top'>";

				$query	= "	SELECT sovellus, nimi, alanimi, nimitys, jarjestys, jarjestys2, hidden, tunnus
							from oikeu
							where kuka='' and yhtio='$yhtio'";

				if ($sovellus != '') {
					$query .= " and sovellus='$sovellus'";
				}

				$query .= " order by sovellus, jarjestys, jarjestys2";
				$result = mysql_query($query) or pupe_error($query);

				echo "<table>";

				$vsove = "";

				while ($row = mysql_fetch_array($result)) {
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

					echo "<a href='$PHP_SELF?tee=MUUTA&tunnus=$tunnus&yht=$yht&sovellus=$sovellus'>$nimi</a></td>";
					echo "<td nowrap>$alanimi</td>";
					echo "<td nowrap>".t($nimitys)."</td>";
					echo "<td nowrap>$jarjestys</td>";
					echo "<td nowrap>$jarjestys2</td>";
					echo "<td nowrap>$hidden</td></tr>\n";

					$vsove = $sove;
				}
				echo "</table>";

				echo "</td>";

			}
		}
		echo "</tr></table>";
	}

	require ("inc/footer.inc");

?>