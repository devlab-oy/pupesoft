<?php
	require "inc/parametrit.inc";
	
	echo "<font class='head'>".t("Pankkiaineistojen virheet")."</font><hr>";

	if ($tee == 'S') {
		
		$silent = "SILENT";

		$query = "	SELECT * FROM tiliotedata
					WHERE alku >= '$vv-$kk-$pp' 
					and tilino = '$tilino' 
					and tyyppi ='$tyyppi'
					ORDER BY alku, tunnus";
		$tiliotedataresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tiliotedataresult) == 0) {
			echo "<font class='message'>".t("Tuollaista aineistoa ei löytynyt")."!</font><br>";
			$tee = '';
		}
		else {
			while ($tiliotedatarow = mysql_fetch_array ($tiliotedataresult)) {
				$tietue = $tiliotedatarow['tieto'];

				if ($tiliotedatarow['tyyppi'] == 1) {
					require "inc/tiliote.inc";
				}
				if ($tiliotedatarow['tyyppi'] == 2) {
					require "inc/LMP.inc";
				}
				
			}
			echo "</table>";

		}
	}

	if ($tee == '') {

		$query = "	SELECT *
					FROM yriti
					WHERE yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sinulla ei ole yhtään pankkitiliä")."</font><hr>";
			require ("inc/footer.inc");
			exit;
		}

		if (!isset($kk)) $kk = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($vv)) $vv = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		if (!isset($pp)) $pp = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

		echo "<form name = 'valikko' action = '$PHP_SELF' method='post'><table>
			  <tr>
			  <th>".t("Alkupvm")."</th>
			  <td>
			  	<input type='hidden' name='tee' value='S'>
				<input type='text' name='pp' maxlength='2' size=2 value='$pp'>
				<input type='text' name='kk' maxlength='2' size=2 value='$kk'>
				<input type='text' name='vv' maxlength='4' size=4 value='$vv'></td>
			  </tr>
			  <tr>
			  <th>".t("Pankkitili")."</th>
			  <td><select name='tilino'>";

		echo "<option value=''>".t("Näytä kaikki")."</option>";

		while ($yritirow = mysql_fetch_array ($result)) {
			$chk = "";
			if ($yritirow["tilino"] == $tilino) $chk = "selected";
			echo "<option value='$yritirow[tilino]' $chk>$yritirow[nimi] ($yritirow[tilino])";
		}

		$chk = array();
		$chk[$tyyppi] = "selected";

		echo "</select></td></tr>
				<tr>
				<th>".t("Laji")."</th>
				<td><select name='tyyppi'>
					<option value='1' $chk[1]>".t("Tiliote")."
					<option value='2' $chk[2]>".t("Lmp")."
				</select>
				</td>
				<td class='back'><input type='submit' value='".t("Hae")."'></td>
				</tr>
				</table><br>
				</form>";

		$tee = "";
		$formi = 'valikko';
		$kentta = 'pp';
	}

	require ("inc/footer.inc");

?>
