<?php

	require ("inc/parametrit.inc");

	// Muutetaanko jonkun muun oikeuksia??
	if ($selkuka != '') {
		$query = "	SELECT nimi, kuka, tunnus
					FROM kuka
					WHERE tunnus='$selkuka'";
		$result = mysql_query($query) or pupe_error($query);
		$selkukarow = mysql_fetch_array($result);
	}
	elseif ($toim != "extranet") {
		$query = "	SELECT nimi, kuka, tunnus
					FROM kuka
					WHERE tunnus='$kukarow[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);
		$selkukarow = mysql_fetch_array($result);
	}


	// tehd��n oikeuksien p�ivitys
	if ($update == 'totta' and $selkukarow["kuka"] != "") {
		// poistetaan ihan aluksi kaikki.. iiik.
		$query = "	DELETE
					FROM oikeu
					WHERE yhtio='$kukarow[yhtio]' and kuka = '$selkukarow[kuka]'";
		if ($sovellus != '') {
			$query .= " and sovellus='$sovellus'";
		}
		$result = mysql_query($query) or pupe_error($query);

		// sitten tutkaillaan onko jotain ruksattu...
		if (count($valittu) != 0) {
			foreach ($valittu as $rastit) { // Tehd��n oikeudet
				list ($nimi, $alanimi, $sov) = explode("#", $rastit);

				//haetaan menu itemi
				$query = "	SELECT nimi, nimitys, jarjestys, alanimi, sovellus, jarjestys2, hidden
							FROM oikeu
							WHERE kuka='' and nimi='$nimi' and alanimi='$alanimi' and sovellus='$sov' and yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				$trow = mysql_fetch_array($result);

				$query = "	INSERT into oikeu
							SET
							kuka		= '$selkukarow[kuka]',
							sovellus	= '$trow[sovellus]',
							nimi		= '$trow[nimi]',
							alanimi 	= '$trow[alanimi]',
							paivitys	= '',
							lukittu		= '',
							nimitys		= '$trow[nimitys]',
							jarjestys 	= '$trow[jarjestys]',
							jarjestys2	= '$trow[jarjestys2]',
							hidden		= '$trow[hidden]',
							yhtio		= '$kukarow[yhtio]'";

				$result = mysql_query($query) or pupe_error($query);
			}
			echo "<font class='message'>".t("K�ytt�oikeudet p�ivitetty")."!</font><br>";
		}

		if (count($paivitys) != 0) {
			foreach ($paivitys as $rastit) { // P�ivitet��n p�ivitys-kentt�
				list ($nimi, $alanimi, $sov) = explode("#", $rastit);

				$query = "	SELECT nimi
							FROM oikeu
							WHERE yhtio='$kukarow[yhtio]' and kuka='$selkukarow[kuka]' and nimi='$nimi' and alanimi='$alanimi' and sovellus='$sov'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 1) {
					$query = "UPDATE oikeu SET paivitys = '1' where yhtio='$kukarow[yhtio]' and kuka='$selkukarow[kuka]' and nimi='$nimi' and alanimi='$alanimi' and sovellus='$sov'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}
		}

		if (count($lukot) != 0) {
			foreach ($lukot as $rastit) { // P�ivitet��n lukittu-kentt�
				list ($nimi, $alanimi, $sov) = explode("#", $rastit);

				$query = "	SELECT nimi
							FROM oikeu
							WHERE yhtio='$kukarow[yhtio]' and kuka='$selkukarow[kuka]' and nimi='$nimi' and alanimi='$alanimi' and sovellus='$sov'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 1) {
					$query = "UPDATE oikeu SET lukittu = '1' where yhtio='$kukarow[yhtio]' and kuka='$selkukarow[kuka]' and nimi='$nimi' and alanimi='$alanimi' and sovellus='$sov'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}
		}
	}

	echo "<font class='head'>".t("Tietosuoja").":</font><hr>";

	echo "<font class='message'>".t("K�ytt�j�n")." $selkukarow[nimi] ".t("k�ytt�oikeudet")." ($yhtiorow[nimi])</font><hr>";

	echo "<table>
			<form method='post'>
			<input type='hidden' name='toim' value='$toim'>
			<input type='hidden' name='sovellus' value='$sovellus'>

			<tr>
				<th>".t("Valitse k�ytt�j�").":</th>
				<td><select name='selkuka' onchange='submit()'>";

	if ($toim == "" or $selkukarow["kuka"] != "") {
		echo "<option selected value='$selkukarow[tunnus]'>$selkukarow[nimi] ($selkukarow[kuka])</option>";
	}
	elseif ($toim == "extranet" and $selkukarow["kuka"] == "") {
		echo "<option selected value=''>".t("Valitse k�ytt�j�")."</option>";
	}

	if ($toim == "extranet" and $selkukarow["tunnus"] != "") {
		$query = "SELECT *
				  FROM kuka
				  WHERE tunnus!='$selkukarow[tunnus]' and yhtio='$kukarow[yhtio]' and extranet != ''
				  ORDER BY nimi";
	}
	elseif ($toim == "extranet" and $selkukarow["tunnus"] == "") {
		$query = "SELECT *
				  FROM kuka
				  WHERE yhtio='$kukarow[yhtio]' and extranet != ''
				  ORDER BY nimi";
	}
	else {
		$query = "SELECT *
				  FROM kuka
				  WHERE tunnus!='$selkukarow[tunnus]' and extranet = '' and yhtio='$kukarow[yhtio]'
				  ORDER BY nimi";
	}
	$kukares = mysql_query($query) or pupe_error($query);

	while ($kurow=mysql_fetch_array($kukares)) {
		echo "<option value='$kurow[tunnus]'>$kurow[nimi] ($kurow[kuka])</option>";
	}

	echo "</select></td></form>";

	if ($toim == "extranet") {
		$sovellus_rajaus = " and sovellus like 'Extranet%' ";
	}
	else {
		$sovellus_rajaus = " and sovellus not like 'Extranet%' ";	
	}
	
	$query = "	SELECT distinct sovellus
				FROM oikeu
				where yhtio = '$kukarow[yhtio]'
				$sovellus_rajaus
				order by sovellus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 1) {
		echo "	<form name='vaihdaSovellus' method='POST'>
				<input type='hidden' name='selkuka' value='$selkukarow[tunnus]'>
				<input type='hidden' name='toim' value='$toim'>
				<tr><th>".t("Valitse sovellus").":</th><td>
				<select name='sovellus' onchange='submit()'>
				<option value=''>".t("Nayta kaikki")."</option>";

		while ($orow = mysql_fetch_array($result)) {
			$sel = '';
			if ($sovellus == $orow[0] and $orow[0] != '') {
				$sel = "SELECTED";
			}
			if ($orow[0] != '') {
				echo "<option value='$orow[0]' $sel>".t("$orow[0]")."</option>";
			}
		}
	}
	echo "</select></td><td class='back'></td></tr></table></form>";

	// n�ytet��n oikeuslista
	echo "<table>";

	$query = "	SELECT *
				FROM oikeu
				WHERE kuka = ''	
				and yhtio = '$kukarow[yhtio]' 
				$sovellus_rajaus";
	
	if ($sovellus != '') {
		$query .= " and sovellus='$sovellus'";
	}
	
	$query .= "	ORDER BY sovellus, jarjestys, jarjestys2";
	$result = mysql_query($query) or pupe_error($query);

	print " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
		<!--

		function toggleAll(toggleBox) {

			var currForm = toggleBox.form;
			var isChecked = toggleBox.checked;
			var nimi = toggleBox.name;

			for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
				if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,3) == nimi) {
					currForm.elements[elementIdx].checked = isChecked;
				}
			}
		}

		//-->
		</script>";

	echo "<form name='suojax' method='post'>
			<input type='hidden' name='update' value='totta'>
			<input type='hidden' name='sovellus' value='$sovellus'>
			<input type='hidden' name='toim' value='$toim'>
			<input type='hidden' name='selkuka' value='$selkukarow[tunnus]'>";

	while ($orow=mysql_fetch_array($result)) {


		if ($vsove != $orow['sovellus']) {
			echo "<tr><td class='back colspan='5'><br></td></tr>";
			echo "<tr><th>".t("Sovellus")."</th>
				<th colspan='2'>".t("Toiminto")."</th>
				<th>".t("K�ytt�")."</th>
				<th>".t("P�ivitys")."</th>
				<th>".t("Lukittu")."</th>
				</tr>";
		}

		$checked	= '';
		$paivit		= '';
		$luk		= '';

		if ($selkukarow["kuka"] != "") {
			$oq = "	SELECT *
					FROM oikeu
					WHERE yhtio='$kukarow[yhtio]' and kuka='$selkukarow[kuka]' and nimi='$orow[nimi]' and alanimi='$orow[alanimi]' and sovellus='$orow[sovellus]'";
			$or = mysql_query($oq) or pupe_error($oq);

			if (mysql_num_rows($or) != 0) {
				$checked = "CHECKED";

				$oikeurow=mysql_fetch_array($or);

				if ($oikeurow["paivitys"] == 1) {
					$paivit = "CHECKED";
				}

				if ($oikeurow["lukittu"] == 1) {
					$luk = "CHECKED";
				}
			}
		}

		echo "<tr><td>".t("$orow[sovellus]")."</td>";

		if ($orow['jarjestys2']!='0') {
			echo "<td class='back'>--></td><td>";
		}
		else {
			echo "<td colspan='2'>";
		}

		echo "	".t("$orow[nimitys]")."</td>
				<td align='center'><input type='checkbox' $checked 	value='$orow[nimi]#$orow[alanimi]#$orow[sovellus]' name='valittu[]'></td>
				<td align='center'><input type='checkbox' $paivit  	value='$orow[nimi]#$orow[alanimi]#$orow[sovellus]' name='paivitys[]'></td>
				<td align='center'><input type='checkbox' $luk  	value='$orow[nimi]#$orow[alanimi]#$orow[sovellus]' name='lukot[]'></td>
				</tr>";

		$vsove = $orow['sovellus'];
	}
	echo "<tr>
			<th colspan='3'>".t("Ruksaa kaikki")."</th>
			<td align='center'><input type='checkbox' name='val' onclick='toggleAll(this);'></td>
			<td align='center'><input type='checkbox' name='pai' onclick='toggleAll(this)'></td>
			<td align='center'><input type='checkbox' name='luk' onclick='toggleAll(this)'></td>
			</tr>";
	echo "</table>";

	if ($toim == "" or ($toim == "extranet" and $selkukarow["kuka"] != "")) {
		echo "<input type='submit' value='".t("P�ivit� tiedot")."'></form>";
	}

	require("inc/footer.inc");


?>