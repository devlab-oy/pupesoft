<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Kohdista viitesuoritus korkoihin")."</font><hr>";

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

if (isset($tilino)){

	// tutkaillaan tiliä
	$query = "select * from tili where yhtio='$kukarow[yhtio]' and tilino='$tilino'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Kirjanpidon tiliä ei löydy")."!</font> $tilino<br><br>";
		unset($viite);
		unset($tilino);
		unset($valitut);
	}
	else {
		$tilirow = mysql_fetch_array($result);
	}
}

if (isset($tapa) and $tapa == 'paalle') {
	if (isset($viite)) {
		// tutkaillaan suoritusta
		$query = "select suoritus.* from suoritus, yriti where suoritus.yhtio='$kukarow[yhtio]' and summa != 0 and kohdpvm = '0000-00-00' and viite like '$viite%' and suoritus.yhtio=yriti.yhtio and suoritus.tilino=yriti.tilino and yriti.factoring != ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sopivia suorituksia ei löydy")."!</font><br><br>";
			unset($viite);
			unset($tilino);
		}
		else {
			echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input name='tilino' type='hidden' value='$tilino'>";
			echo "<input name='tapa'   type='hidden' value='$tapa'>";
			echo "<table>
				<tr><th>".t("Viite")."</th><th>".t("Asiakas")."</th><th>".t("Summa")."</th><th>".t("Valitse")."</th></tr>";

			while ($suoritusrow=mysql_fetch_array($result)) {
				echo "<tr><td>$suoritusrow[viite]</td><td>$suoritusrow[nimi_maksaja]</td><td>$suoritusrow[summa]</td><td><input name='valitut[]' type='checkbox' value='$suoritusrow[tunnus]' CHECKED></td></tr>";
			}

			echo "</table><br>";
			echo "<input type='checkbox' name='val' onclick='toggleAll(this);' CHECKED> Ruksaa kaikki<br><br>";
			echo "<input type='submit' value='".t("Kohdista")."'></form>";
		}

	}

	if (isset($tilino) and is_array($valitut)) {
		echo "Kohdistan!<br>";
		foreach ($valitut as $valittu) {
			$query = "select * from suoritus where yhtio='$kukarow[yhtio]' and tunnus='$valittu' and kohdpvm='0000-00-00'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Suoritus on kadonnut tai se on käytetty")."!</font><br><br>";
			}
			else {
				$suoritusrow=mysql_fetch_array($result);
				// päivitetään suoritus
				$query = "update suoritus set kohdpvm = now(), summa = 0 where yhtio='$kukarow[yhtio]' and tunnus='$suoritusrow[tunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				//echo "$query<br>";
				if (mysql_affected_rows() == 0) {
					echo "<font class='error'>".t("Suorituksen päivitys epäonnistui")."! $tunnus</font><br>";
				}

				// tehdään kirjanpitomuutokset
				$query = "update tiliointi set tilino='$tilino', selite = '".t("Kohdistettiin korkoihin")."' where yhtio='$kukarow[yhtio]' and tunnus='$suoritusrow[ltunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				//echo "$query<br>";
				if (mysql_affected_rows() == 0) {
					echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehdä! Korjaa kirjanpito käsin")."!</font><br>";
				}
			}
		}
		unset($viite);
		unset($tapa);
	}
}
if (isset($tapa) and $tapa == 'pois') {
	if (isset($viite)) {
		$query = "select suoritus.*, tiliointi.summa from suoritus, yriti, tiliointi where suoritus.yhtio='$kukarow[yhtio]' and suoritus.summa = 0 and kohdpvm != '0000-00-00' and viite like '$viite%' and suoritus.yhtio=yriti.yhtio and suoritus.tilino=yriti.tilino and yriti.factoring != '' and tiliointi.yhtio=suoritus.yhtio and selite = '".t("Kohdistettiin korkoihin")."' and tiliointi.tunnus=suoritus.ltunnus";
		$result = mysql_query($query) or pupe_error($query);
		//echo $query."<br>";

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sopivia korkovientejä ei löydy")."!</font><br><br>";
			unset($viite);
			unset($tilino);
		}
		else {
			echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input name='tilino' type='hidden' value='$yhtiorow[factoringsaamiset]'>";
			echo "<input name='tapa'   type='hidden' value='$tapa'>";
			echo "<table>
				<tr><th>".t("Viite")."</th><th>".t("Asiakas")."</th><th>".t("Summa")."</th><th>".t("Valitse")."</th></tr>";

			while ($suoritusrow=mysql_fetch_array($result)) {
				echo "<tr><td>$suoritusrow[viite]</td><td>$suoritusrow[nimi_maksaja]</td><td>$suoritusrow[summa]</td><td><input name='valitut[]' type='checkbox' value='$suoritusrow[tunnus]' CHECKED></td></tr>";
			}
			echo "</table><br><input type='submit' value='".t("Peru korkoviennit")."'></form>";
		}
	}

	if (isset($tilino) and is_array($valitut)) {
		echo "Kohdistan!<br>";
		foreach ($valitut as $valittu) {
			$query = "select * from suoritus where yhtio='$kukarow[yhtio]' and tunnus='$valittu' and kohdpvm!='0000-00-00'";
			$result = mysql_query($query) or pupe_error($query);
			//echo "$query<br>";
			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Suoritus on kadonnut tai se ei ole enää käytetty")."!</font><br><br>";
			}
			else {
				$suoritusrow=mysql_fetch_array($result);
				// Etsitään kirjanpitotapahtuma
				$query = "select summa from tiliointi where yhtio='$kukarow[yhtio]' and tunnus='$suoritusrow[ltunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				//echo "$query<br>";
				if (mysql_num_rows($result) == 0) {
					echo "<font class='error'>".t("Tiliöinti on kadonnut")."!</font><br><br>";
				}
				else {
					$tiliointirow=mysql_fetch_array($result);
					$query = "select pankki_tili from factoring where yhtio='$kukarow[yhtio]' and pankki_tili='$suoritusrow[tilino]'";
					$result = mysql_query($query) or pupe_error($query);
					//echo "$query<br>";
					if (mysql_num_rows($result) == 0) {
						$tilino = $yhtiorow['myyntisaamiset'];
					}
					else {
						$tilino = $yhtiorow['factoringsaamiset'];
					}
					// päivitetään suoritus
					$query = "update suoritus set kohdpvm = '0000-00-00', summa = -1 * $tiliointirow[summa] where yhtio='$kukarow[yhtio]' and tunnus='$suoritusrow[tunnus]'";
					$result = mysql_query($query) or pupe_error($query);
					//echo "$query<br>";
					if (mysql_affected_rows() == 0) {
						echo "<font class='error'>".t("Suorituksen päivitys epäonnistui")."! $tunnus</font><br>";
					}
					// tehdään kirjanpitomuutokset
					$query = "update tiliointi set tilino='$tilino', selite = '".t("Korjatttu suoritus")."' where yhtio='$kukarow[yhtio]' and tunnus='$suoritusrow[ltunnus]'";
					$result = mysql_query($query) or pupe_error($query);
					//echo "$query<br>";
					if (mysql_affected_rows() == 0) {
						echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehdä! Korjaa kirjanpito käsin")."!</font><br>";
					}
				}
			}
		}
		unset($viite);
		unset($tapa);
	}
}

if (!isset($tapa)) {
		echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<table>";
		echo "<tr><th>".t("Suorituksia siirretään korkoihin")."</th>";
		echo "<td><input type='radio' name='tapa' value='paalle' checked></td></tr>";
		echo "<tr><th>".t("Suorituksia siirretään koroista normaaleiksi suorituksiksi")."</th>";
		echo "<td><input type='radio' name='tapa' value='pois'></td></tr>";
		echo "<tr><td class='back'><input name='subnappi' type='submit' value='".t("Valitse")."'></td></tr>";
		echo "</table>";
		echo "</form>";
}
else {
	if (!isset($viite)) {
		if ($tapa == 'paalle') {
			echo "<form name='eikat' action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tapa' value='$tapa'><table>";
			echo "<tr><th>".t("Anna viitteen alku suorituuksista, jotka haluat käsiteltäviksi")."</th>";
			echo "<td><input type='text' name='viite' value = '50009'></td></tr>";
			echo "<tr><th>".t("Mille tilille nämä varat tiliöidään")."</th>";
			echo "<td><input type='text' name='tilino'></td></tr>";
			echo "<tr><td class='back'><input name='subnappi' type='submit' value='".t("Hae suoritukset")."'></td></tr>";
			echo "</table>";
			echo "</form>";
		}
		else {
			echo "<form name='eikat' action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tapa' value='$tapa'><table>";
			echo "<tr><th>".t("Anna viitteen alku korkovienneistä, jotka haluat käsiteltäviksi")."</th>";
			echo "<td><input type='text' name='viite'></td></tr>";
			echo "<tr><td class='back'><input name='subnappi' type='submit' value='".t("Hae korkoviennit")."'></td></tr>";
			echo "</table>";
			echo "</form>";
		}
	}
}

// kursorinohjausta
$formi = "eikat";
$kentta = "viite";

require ("../inc/footer.inc");

?>
