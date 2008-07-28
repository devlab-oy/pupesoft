<?php
	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Käyttäjäprofiilit").":</font><hr>";


	//tehdään tsekki, että ei tehdä profiilia samannimiseksi kuin joku käyttäjä
	if ($profiili != '') {
		$query = "	SELECT nimi
					FROM kuka use index (kuka_index)
					WHERE kuka='$profiili' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$tee = "";
			$profiili = "";
			echo "<br><font class='error'>".t("VIRHE: Profiilin nimi on jo käytössä. Valitse toinen nimi")."</font><br><br>";
		}
	}


	if ($tee=='POISTA') {
		if ($profiili != '') {
			$query = "	DELETE
						FROM oikeu
						WHERE yhtio='$kukarow[yhtio]' and profiili='$profiili' and lukittu = ''";
			$result = mysql_query($query) or pupe_error($query);

			$maara = mysql_affected_rows();

			echo "<font class='message'>".t("Poistettiin")." $maara ".t("riviä")."</font><br>";

			$profiili = "";
		}

		$tee = '';
	}


	// tehdään oikeuksien päivitys
	if ($tee == 'PAIVITA') {

		// poistetaan ihan aluksi kaikki.. iiik.
		$query = "	DELETE
					FROM oikeu
					WHERE yhtio='$kukarow[yhtio]' and kuka = '$profiili' and profiili='$profiili'";
		if ($sovellus != '') {
			$query .= " and sovellus='$sovellus'";
		}
		$result = mysql_query($query) or pupe_error($query);

		// sitten tutkaillaan onko jotain ruksattu...
		if (count($valittu) != 0) {
			foreach ($valittu as $rastit) { // Tehdään oikeudet
				list ($nimi, $alanimi, $sov) = split("#", $rastit);

				//haetaan menu itemi
				$query = "	SELECT nimi, nimitys, jarjestys, alanimi, sovellus, jarjestys2, hidden
							FROM oikeu use index (sovellus_index)
							WHERE kuka='' and nimi='$nimi' and alanimi='$alanimi' and sovellus='$sov' and yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				$trow = mysql_fetch_array($result);

				$query = "	INSERT into oikeu
							SET
							kuka		= '$profiili',
							profiili	= '$profiili',
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
			echo "<font class='message'>".t("Käyttöoikeudet päivitetty")."!</font><br>";
		}

		if (count($paivitys) != 0) {
			foreach ($paivitys as $rastit) { // Päivitetään päivitys-kenttä
				list ($nimi, $alanimi, $sov) = split("#", $rastit);

				$query = "	SELECT nimi
							FROM oikeu use index (sovellus_index)
							WHERE yhtio		= '$kukarow[yhtio]'
							and kuka		= '$profiili'
							and profiili	= '$profiili'
							and nimi		= '$nimi'
							and alanimi		= '$alanimi'
							and sovellus	= '$sov'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 1) {
					$query = "	UPDATE oikeu
								SET paivitys 	= '1'
								where yhtio		= '$kukarow[yhtio]'
								and kuka		= '$profiili'
								and profiili	= '$profiili'
								and nimi		= '$nimi'
								and alanimi		= '$alanimi'
								and sovellus	= '$sov'";
					$result = mysql_query($query) or pupe_error($query);
				}
			}
		}


		//päivitetään käyttäjien profiilit (joilla on käytössä tämä profiili)
		$query = "	SELECT *
					FROM kuka
			 		WHERE yhtio='$kukarow[yhtio]' and profiilit like '%$profiili%'";
		$kres = mysql_query($query) or pupe_error($query);

		while ($krow = mysql_fetch_array($kres)) {
			$profiilit = explode(',', $krow["profiilit"]);

			if (count($profiilit) > 0) {
				//käydään läpi käyttäjän kaikki profiilit
				$triggeri = "";
				foreach($profiilit as $prof) {
					//jos tämä kyseinen profiili on ollut käyttäjällä aikaisemmin, niin joudumme päivittämään oikeudet
					if (strtoupper($prof) == strtoupper($profiili)) {
						$triggeri = "HAPPY";
					}
				}

				if ($triggeri == "HAPPY") {
					//poistetaan käyttäjän vanhat
					$query = "	DELETE FROM oikeu
								WHERE yhtio='$kukarow[yhtio]' and kuka='$krow[kuka]' and lukittu=''";
					$pres = mysql_query($query) or pupe_error($query);

					//käydään uudestaan profiili läpi
					foreach($profiilit as $prof) {
						$query = "	SELECT *
									FROM oikeu use index (oikeudet_index)
									WHERE yhtio='$kukarow[yhtio]' and kuka='$prof' and profiili='$prof'";
						$pres = mysql_query($query) or pupe_error($query);

						while ($trow = mysql_fetch_array($pres)) {
							//joudumme tarkistamaan ettei tätä oikeutta ole jo tällä käyttäjällä.
							//voi olla esim jos se on lukittuna annettu
							$query = "	SELECT yhtio
										FROM oikeu use index (sovellus_index)
										WHERE kuka		= '$krow[kuka]'
										and sovellus	= '$trow[sovellus]'
										and nimi		= '$trow[nimi]'
										and alanimi 	= '$trow[alanimi]'
										and yhtio		= '$kukarow[yhtio]'";
							$tarkesult = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($tarkesult) == 0) {
								$query = "	INSERT into oikeu
											SET
											kuka		= '$krow[kuka]',
											sovellus	= '$trow[sovellus]',
											nimi		= '$trow[nimi]',
											alanimi 	= '$trow[alanimi]',
											paivitys	= '$trow[paivitys]',
											nimitys		= '$trow[nimitys]',
											jarjestys 	= '$trow[jarjestys]',
											jarjestys2	= '$trow[jarjestys2]',
											hidden		= '$trow[hidden]',
											yhtio		= '$kukarow[yhtio]'";
								$rresult = mysql_query($query) or pupe_error($query);
							}
						}
					}
				}
			}
		}
	}

	echo "<SCRIPT LANGUAGE=JAVASCRIPT>
				function verify(){
						msg = '".t("Haluatko todella poistaa tämän profiilin ja käyttäjiltä oikeudet tähän profiiliin?")."';
						return confirm(msg);
				}
		</SCRIPT>";

	echo "<table>

			<form action='$PHP_SELF' method='post'>
			<tr>
				<th>".t("Luo uusi profiili").":</th>
				<td><input type='text' name='uusiprofiili' size='25'></td>
				<td class='back'><input type='submit' value='".t("Luo uusi profiili")."'></td>
			</tr>
			</form>
			<form action='$PHP_SELF' method='post'>
			<input type='hidden' name='sovellus' value='$sovellus'>
			<input type='hidden' name='vainval' value='$vainval'>

			<tr>
				<th>".t("Valitse Profiili").":</th>
				<td><select name='profiili' onchange='submit()'>";



	if ($uusiprofiili == "") {
		$query = "	SELECT distinct profiili
					FROM oikeu
					WHERE yhtio='$kukarow[yhtio]' and profiili!=''
					ORDER BY profiili";
		$kukares = mysql_query($query) or pupe_error($query);

		while ($kurow=mysql_fetch_array($kukares)) {
			$sel = "";

			if ($profiili == $kurow["profiili"]) {
				$sel = "SELECTED";
			}

			echo "<option value='$kurow[profiili]' $sel>$kurow[profiili]</option>";
		}
		echo "</select>";
	}
	else {
		echo "<option value='$uusiprofiili'>$uusiprofiili</option></select>";
		echo "<input type='hidden' name='uusiprofiili' value='$uusiprofiili'>";
	}


	echo "</td><td class='back'><input type='submit' value='".t("Valitse profiili")."'></form></td>";

	if ($profiili != '') {
		echo "<form method='post' action='$PHP_SELF' onSubmit = 'return verify()'>
				<input type='hidden' name='tee' value='POISTA'>
				<input type='hidden' name='profiili' value='$profiili'>
				<td class='back'><input type='submit' value='".t("Poista tämä profiili")."'></td></form>";
	}


	echo "</tr></table><br><br>";

	if ($profiili != '') {

		echo "<table>";

		$query = "	SELECT distinct sovellus
					FROM oikeu
					where yhtio='$kukarow[yhtio]'
					order by sovellus";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 1) {

			echo "	<form action='$PHP_SELF' name='vaihdaSovellus' method='POST'>
					<input type='hidden' name='profiili' value='$profiili'>
					<input type='hidden' name='uusiprofiili' value='$uusiprofiili'>
					<tr><th>".t("Valitse sovellus").":</th><td>
					<select name='sovellus' onchange='submit()'>
					<option value=''>".t("Nayta kaikki")."</option>";

			while ($orow = mysql_fetch_array($result)) {
				$sel = '';
				if ($sovellus == $orow["sovellus"]) {
					$sel = "SELECTED";
				}
				echo "<option value='$orow[sovellus]' $sel>$orow[sovellus]</option>";
			}
		}

		echo "</select></td></tr>";

		$chk = "";
		if ($vainval != "") {
			$chk = "CHECKED";
			$lisa = " kuka=profiili and profiili = '$profiili' ";
		}
		else {
			$lisa = " kuka = '' and profiili = '' ";
		}

		echo "<tr><th>".t("Näytä vain ruksatut")."</th><td><input type='checkbox' name='vainval' $chk onClick='submit();'></td></tr>";

		echo "</table></form>";

		// näytetään oikeuslista
		echo "<table>";

		$query = "	SELECT *
					FROM oikeu
					WHERE
					$lisa
					and yhtio = '$kukarow[yhtio]'";

		if ($sovellus != '') {
			$query .= " and sovellus = '$sovellus'";
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



		echo "<form action='$PHP_SELF' name='suojax' method='post'>
				<input type='hidden' name='tee' value='PAIVITA'>
				<input type='hidden' name='sovellus' value='$sovellus'>
				<input type='hidden' name='profiili' value='$profiili'>";

		while ($orow=mysql_fetch_array($result)) {


			if ($vsove != $orow['sovellus']) {
				echo "<tr><td class='back colspan='5'><br></td></tr>";
				echo "<tr><th>".t("Sovellus")."</th>
					<th colspan='2'>".t("Toiminto")."</th>
					<th>".t("Käyttö")."</th>
					<th>".t("Päivitys")."</th>
					</tr>";
			}

			$checked	= '';
			$paivit		= '';

			$oq = "	SELECT *
					FROM oikeu
					WHERE yhtio		= '$kukarow[yhtio]'
					and kuka		= '$profiili'
					and profiili	= '$profiili'
					and nimi		= '$orow[nimi]'
					and alanimi		= '$orow[alanimi]'
					and sovellus	= '$orow[sovellus]'";
			$or = mysql_query($oq) or pupe_error($oq);

			if (mysql_num_rows($or) != 0) {
				$checked = "CHECKED";

				$oikeurow=mysql_fetch_array($or);

				if ($oikeurow["paivitys"] == 1) {
					$paivit = "CHECKED";
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
					</tr>";

			$vsove = $orow['sovellus'];
		}
		echo "<tr>
				<th colspan='3'>".t("Ruksaa kaikki")."</th>
				<td align='center'><input type='checkbox' name='val' onclick='toggleAll(this);'></td>
				<td align='center'><input type='checkbox' name='pai' onclick='toggleAll(this)'></td>
				</tr>";
		echo "</table>";

		echo "<input type='submit' value='".t("Päivitä tiedot")."'></form>";
	}

	require("inc/footer.inc");

?>