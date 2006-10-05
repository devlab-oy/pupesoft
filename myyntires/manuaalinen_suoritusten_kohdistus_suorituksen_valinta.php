<?php

// estetään sivun lataus suoraan
if (!empty($HTTP_GET_VARS["oikeus"]) ||
    !empty($HTTP_POST_VARS["oikeus"]) ||
    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
    !isset($oikeus)) {

  echo "<p>".t("Kielletty toiminto")."!</p>";
  exit;
}

$query = "SELECT summa, viite, viesti,tilino,maksupvm,kirjpvm,nimi_maksaja,asiakas_tunnus, tunnus FROM suoritus WHERE yhtio ='$kukarow[yhtio]' AND kohdpvm='0000-00-00' and asiakas_tunnus='$asiakas_tunnus' ";

//echo "<p>$query";
//echo "<font class='head'> suoritus tunnus: $suoritus_tunnus</font>";

$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result) > 1) {
	echo "<font class='head'>".t("Manuaalinen suoritusten kohdistaminen (suorituksen valinta)")."</font><hr>";
	echo "<table><tr><th>Valitse</th>";
	for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
		echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
	}
	echo "</tr>";

	echo "<form action = '$PHP_SELF?tila=kohdistaminen' method = 'post'>";

	if (isset($asiakas_nimi)) {
		echo "<input type='hidden' name='asiakas_nimi' value='$asiakas_nimi'>";
	}
	
	$r=1;
	while ($suoritus=mysql_fetch_array ($result)) {
		
		echo "<tr><td>
		<input type='radio' name='suoritus_tunnus' value='$suoritus[tunnus]' ";
		
		if (mysql_num_rows($result)==$r) echo "checked";
		$r++;

		echo "></td>";

		for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
			echo "<td>$suoritus[$i]</td>";
		}

		echo "</tr>";

	}

	echo "</table>";
	echo "<br><input type='submit' value='".t("Kohdista")."'></form>";
}
else {
	if (mysql_num_rows($result) == 1) {
		$tila='kohdistaminen';
		$suoritus=mysql_fetch_array ($result);
		$suoritus_tunnus=$suoritus['tunnus'];	
	}
	else {
		echo "<font class='message'>".t("Asiakkaalle ei ole muita suorituksia")."</font><br>";
		$tila='';
	}
}
?>
