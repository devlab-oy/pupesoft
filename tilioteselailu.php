<?php
	require "inc/parametrit.inc";
	
	echo "<font class='head'>".t("Pankkiaineistojen selailu")."</font><hr>";

	if ($tee == 'Z') { //Olemme tulossa takain suorituksista
		$query= "SELECT tilino FROM yriti
				WHERE tunnus = $mtili and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Tili katosi")."</font><br>";
			
			require ("inc/footer.inc");
			exit;
		}
		else {
			$yritirow=mysql_fetch_array ($result);
			$tee='T';
			$tilino=$yritirow['tilino'];
			$tyyppi=1;
		}
	}

	if ($tee == 'X') {
	//Pyyntö seuraavasta tiliotteesta
		$query= "SELECT * FROM tiliotedata
				WHERE alku > '$pvm' and tilino = '$tilino' and tyyppi ='1'
				ORDER BY tunnus LIMIT 1";
		$tiliotedataresult = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($tiliotedataresult) == 0) {
			echo "<font class='message'>".t("Ei uudempaa aineistoa")."</font><br>";
			$tee = '';
		}
		else {
			$tee='T';
			$tiliotedatarow=mysql_fetch_array ($tiliotedataresult);
			$tyyppi=1;
			$pvm=$tiliotedatarow['alku'];
		}

	}

	if ($tee == 'S') {
		// Tarkistetaan oliko pvm ok
		$val = checkdate($kk, $pp, $vv);
		if (!$val) {
			echo "<b>".t("Virheellinen pvm")."</b><br>";
			$tee = '';
		}
		else {
			$pvm = $vv . "-" . $kk . "-" . $pp;
		}
	}


	if ($tee == 'T') {
		$tee='S'; //Pvm on jo kunnossa
	}

	if ($tee == 'S') {
		if ($tyyppi=='3') {
			$query = "	SELECT * FROM tiliotedata
						WHERE alku = '$pvm' and tilino = '$tilino' and tyyppi ='$tyyppi'
						ORDER BY tieto";
		}
		else {
			$query = "	SELECT * FROM tiliotedata
						WHERE alku = '$pvm' and tilino = '$tilino' and tyyppi ='$tyyppi'
						ORDER BY tunnus";
		}
		$tiliotedataresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($tiliotedataresult) == 0) {
			echo "<font class='message'>".t("Tuollaista aineistoa ei löytynyt")."! $query</font><br>";
			$tee = '';
		}
		else {
			while ($tiliotedatarow=mysql_fetch_array ($tiliotedataresult)) {
				$tietue = $tiliotedatarow['tieto'];

				if ($tiliotedatarow['tyyppi'] == 1) {
					require "inc/tiliote.inc";
				}
				if ($tiliotedatarow['tyyppi'] == 2) {
					require "inc/LMP.inc";
				}
				if ($tiliotedatarow['tyyppi'] == 3) {
					require "inc/naytaviitteet.inc";
				}
			}
			echo "</table>";
		}
	}
	if ($tee == '') {
		$query = "SELECT *
	                 FROM yriti
	                 WHERE yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sinulla ei ole yhtään pankkitiliä")."</font><hr>";
			
			require ("inc/footer.inc");
			exit;
		}

		echo "<form name = 'valikko' action = '$PHP_SELF' method='post'><table>
			  <tr>
			  <td>".t("Tapahtumapvm")."</td>
			  <td>
			  	<input type='hidden' name='tee' value='S'>
				<input type='text' name='pp' maxlength='2' size=2>
				<input type='text' name='kk' maxlength='2' size=2>
				<input type='text' name='vv' maxlength='4' size=4></td>
			  </tr>
			  <tr>
			  <td>".t("Pankkitili")."</td>
			  <td><select name='tilino'>";

		while ($yritirow=mysql_fetch_array ($result)) {
			echo "<option value='$yritirow[tilino]'>$yritirow[nimi] ($yritirow[tilino])";
		}
		echo "</select></td></tr>
				<tr>
				<td>Laji</td>
				<td><select name='tyyppi'>
					<option value='1'>".t("Tiliote")."
					<option value='2'>".t("Lmp")."
					<option value='3'>".t("Viitesiirrot")."
				</select>
				</td></tr>
				</table><br><input type='Submit' value='".t("Valitse")."'><br></form>";

		$query = "SELECT alku, concat_ws(' ', yriti.nimi, yriti.tilino) tili, if(tyyppi='1', 'tiliote', if(tyyppi='2','lmp','viitesiirrot')) laji, tyyppi, yriti.tilino
					FROM tiliotedata, yriti
			                WHERE tiliotedata.yhtio='$kukarow[yhtio]' and tiliotedata.yhtio=yriti.yhtio and tiliotedata.tilino=yriti.tilino
					GROUP BY alku, tili, laji
					ORDER BY alku desc, tiliotedata.tilino, laji
					LIMIT 30";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sinulla ei ole pankkiainestoja")."</font><hr>";
			
			require ("inc/footer.inc");
			exit;
		}
		echo "<table>";
		echo "<tr>";
		for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) ."</th>";
		}
		echo "</tr>";

		while ($row=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($result)-2 ; $i++) {
				echo "<td>$row[$i]</td>";
			}
			echo "	<form name = 'valikko' action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tee' value='T'>
					<input type='hidden' name='pvm' value='$row[alku]'>
					<input type='hidden' name='tyyppi' value='$row[tyyppi]'>
					<input type='hidden' name='tilino' value='$row[tilino]'>
					<td><input type = 'submit' value = '".t("Valitse")."'></td>
			  		</form>
			  		</tr>";
		}
		echo "</table></form>";

		$tee = "";
		$formi = 'valikko';
		$kentta = 'pp';
	}
	require "inc/footer.inc";
?>
