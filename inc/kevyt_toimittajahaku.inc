<?php
///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;
// tarvitaan $ytunnus ja $kukarow
// palautetaan $toimittajarow

$ytunnus = trim($ytunnus);
$limit   = 100; // montako riviä on maksimi

if ($toimittajaid != '') {
	$query	= "SELECT * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
	$result = mysql_query($query) or pupe_error($query);
	$monta  = mysql_num_rows($result);
}
elseif (is_numeric($ytunnus)) {
	//etsitään eka koko ytunnuksella
	$query	= "SELECT * from toimi where yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$monta  = mysql_num_rows($result);

	if ($monta==0) {
		// kokeillaan ytunnuksen alulla
		$query	= "SELECT * from toimi where yhtio='$kukarow[yhtio]' and ytunnus like '$ytunnus%'";
		$result = mysql_query($query) or pupe_error($query);
		$monta  = mysql_num_rows($result);
	}
}
elseif (is_string($ytunnus)) {
	// kokeillaan nimen osalla
	$query	= "SELECT * from toimi where yhtio='$kukarow[yhtio]' and nimi like '%$ytunnus%'";
	$result = mysql_query($query) or pupe_error($query);
	$monta  = mysql_num_rows($result);
}

if ($monta==0) {
	echo "<font class='error'>".t("Toimittajaa")." '$ytunnus' ".t("ei löydy")."!</font><br>";
	$ytunnus = "";
	$toimittajaid = "";
}

if ($monta==1) {
	$toimittajarow = mysql_fetch_array($result);
	$ytunnus = $toimittajarow['ytunnus'];
	$toimittajaid = $toimittajarow['tunnus'];
}

if (($monta>1) and ($monta<=$limit)) {
	//tämä vain otsik.php:tä varten
	echo $lause;

	echo "<table border='0'>";
	echo "<tr>
			<th>".t("ytunnus")."</th>
			<th>".t("nimi")."</th>
			<th colspan='3'>".t("osoite")."</th>
			<td class='back'></td>
		</tr>";

	while ($toimittaja = mysql_fetch_array($result)) {

		if     ($toimittaja['ytunnus']!='')    $numero=trim($toimittaja['ytunnus']);
		else    die("VIRHE: Toimittajalla ei ole ytunnusta! Korjaa asia!");

		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittaja[tunnus]'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='muutparametrit'  value='$muutparametrit'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<input type='hidden' name='ytunnus' value='$numero'>";
		echo "<tr>
				<td>$toimittaja[ytunnus] ($toimittaja[tunnus])</td>
				<td>$toimittaja[nimi]</td>
				<td>$toimittaja[osoite]</td>
				<td>$toimittaja[postino]</td>
				<td>$toimittaja[postitp]</td>
				<td class='back'><input type='submit' value='".t("Valitse")."'></td>
			</tr>";
		echo "</form>";
	}
	echo "</table><br>";
	$ytunnus = "";
}

if ($monta > $limit) {
	echo "<font class='error'>".t("VIRHE: Toimittajahaulla löytyi")." $monta ".t("sopivaa toimittajaa. Tarkenna hakuasi")."!</font><br><br>";
	$ytunnus="";
}

?>
