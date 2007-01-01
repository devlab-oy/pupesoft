<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Kohdista viitesuoritus korkoihin")."</font><hr>";

if (isset($tilino)){

	// tutkaillaan tiliä
	$query = "select * from tili where yhtio='$kukarow[yhtio]' and tilino='$tilino'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Kirjanpidon tiliä ei löydy")."!</font><br><br>";
		unset($viite);
		unset($tilino);
		unset($valitut);
	}
	else {
		$tilirow = mysql_fetch_array($result);
	}
}

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
		
		echo "<table>
			<tr><th>".t("Viite")."</th><th>".t("Asiakas")."</th><th>".t("Summa")."</th><th>".t("Valitse")."</th></tr>";

		while ($suoritusrow=mysql_fetch_array($result)) {
			echo "<tr><td>$suoritusrow[viite]</td><td>$suoritusrow[nimi_maksaja]</td><td>$suoritusrow[summa]</td><td><input name='valitut[]' type='checkbox' value='$suoritusrow[tunnus]' CHECKED></td></tr>";
		}				
		echo "</table><br><input type='submit' value='".t("Kohdista")."'></form>";
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
}

if (!isset($viite)) {
	echo "<form name='eikat' action='$PHP_SELF' method='post' autocomplete='off'>";
	echo "<table>";
	echo "<tr><th>".t("Anna viitteen alku suorituuksista, jotka haluat käsiteltäviksi")."</th>";
	echo "<td><input type='text' name='viite'></td></tr>";
	echo "<tr><th>".t("Mille tilille nämä varat tiliöidään")."</th>";
	echo "<td><input type='text' name='tilino'></td></tr>";
	echo "<tr><td class='back'><input name='subnappi' type='submit' value='".t("Hae suoritukset")."'></td></tr>";
	echo "</table>";
	echo "</form>";
}

// kursorinohjausta
$formi = "eikat";
$kentta = "viite";

require ("../inc/footer.inc");

?>
