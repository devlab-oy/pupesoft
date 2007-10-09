<?php

// tarvitaan $ytunnus ja $kukarow
// palautetaan $toimittajarow

$ytunnus = addslashes(trim($ytunnus));
$limit   = 300; // montako riviä on maksimi
$monta   = 0;

if (is_numeric($toimittajaid) and $toimittajaid > 0) {
	$query	= "SELECT * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
	$result = mysql_query($query) or pupe_error($query);
	$monta  = mysql_num_rows($result);
}
if ($monta == 0 and $ytunnus != '') {
	//etsitään eka koko ytunnuksella
	$query	= "SELECT * from toimi where yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus' and tyyppi != 'P'";
	$result = mysql_query($query) or pupe_error($query);
	$monta  = mysql_num_rows($result);

	if ($monta == 0) {
		// kokeillaan ytunnuksen alulla
		$query	= "SELECT * from toimi where yhtio='$kukarow[yhtio]' and ytunnus like '$ytunnus%' and tyyppi != 'P'";
		$result = mysql_query($query) or pupe_error($query);
		$monta  = mysql_num_rows($result);
	}
}

if ($monta == 0 and $ytunnus != '') {
	// kokeillaan nimen osalla
	$query	= "SELECT * from toimi where yhtio='$kukarow[yhtio]' and nimi like '%$ytunnus%' and tyyppi != 'P'";
	$result = mysql_query($query) or pupe_error($query);
	$monta  = mysql_num_rows($result);
}

if ($monta==0) {
	echo "<font class='error'>".t("Toimittajaa")." '$ytunnus' ".t("ei löydy")."!</font><br>";
	$ytunnus 		= "";
	$toimittajaid 	= "";
}

if ($monta==1) {
	$toimittajarow 	= mysql_fetch_array($result);
	$ytunnus 		= $toimittajarow['ytunnus'];
	$toimittajaid 	= $toimittajarow['tunnus'];
}

if ($monta > 1 and $monta <= $limit) {
	//tämä vain otsik.php:tä varten
	echo $lause;

	echo "<table border='0'>";
	echo "<tr>
			<th>".t("Ytunnus")."</th>
			<th>".t("Nimi")."</th>
			<th colspan='3'>".t("Osoite")."</th>
			<th>".t("Tilinumero")."</th>
			<td class='back'></td>
		</tr>";

	while ($toimittaja = mysql_fetch_array($result)) {

		if     ($toimittaja['ytunnus']!='')    $numero=trim($toimittaja['ytunnus']);
		else    die("VIRHE: Toimittajalla ei ole ytunnusta! Korjaa asia!");

		echo "<tr>
				<td>$toimittaja[ytunnus]</td>
				<td>$toimittaja[nimi] $toimittaja[nimitark]</td>
				<td>$toimittaja[osoite]</td>
				<td>$toimittaja[postino]</td>
				<td>$toimittaja[postitp]</td>";
				
		if (strtoupper($toimittaja['maa']) != strtoupper($yhtiorow['maa'])) {
			echo "<td>$toimittaja[ultilno]</td>";
		}
		else {
			echo "<td>$toimittaja[tilinumero]</td>";	
		}
			
		echo "<td class='back'>";
		echo "<form id='toimittaja_$toimittaja[tunnus]' name='toimittaja_$toimittaja[tunnus]' action=\"$PHP_SELF\" method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='$toimittaja[tunnus]'>";
		echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
		echo "<input type='hidden' name='tee' value='$tee'>";
		echo "<input type='hidden' name='tila' value='$tila'>";
		echo "<input type='hidden' name='maara' value='$maara'>";	
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='tunnus' value='$tunnus'>";
		echo "<input type='hidden' name='keikalla' value='$keikalla'>";		
		echo "<input type='hidden' name='muutparametrit'  value='$muutparametrit'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<input type='hidden' name='ytunnus' value='$numero'>";
		echo "<input type='submit' value='".t("Valitse")."' onclick=\"this.form.id='kevyt_toimittajahaku';\">";
		echo "</form>";
		echo "</td></tr>";
		
	}
	echo "</table><br>";
	$ytunnus = "";
}

if ($monta > $limit) {
	echo "<font class='error'>".t("VIRHE: Toimittajahaulla löytyi")." $monta ".t("sopivaa toimittajaa. Tarkenna hakuasi")."!</font><br><br>";
	$ytunnus="";
}

?>
