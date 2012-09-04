<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require("../inc/parametrit.inc");

echo "<font class='head'>".t("Viikkosuunnitelma")."</font><hr>";

$sel1 = '';
$sel2 = '';

if ($vstk == '') {
	$sel1 = "CHECKED";
	$vstk = "Viikkosuunnitelma";
}
if ($vstk == 'Viikkosuunnitelma') {
	$sel1 = "CHECKED";
}
if ($vstk == 'Asiakaskäynti') {
	$sel2 = "CHECKED";
}

echo "<li><a href='$PHP_SELF?kausi=$kausi&tee=VALITSE_TIEDOSTO&vstk=$vstk'>".t("Sisäänlue suunnitelma-/asiakaskäyntitiedosto")."</a>";
echo "<br><li><a href='$PHP_SELF?tee=laheta&kausi=$kausi&vstk=$vstk'>".t("Vie asiakastietopaketti sähköpostiisi")."</a><br><br>";

function viikonpaivat($kausi) {
	global $viikkoalku, $viikkoloppu;

	$viikko = substr($kausi,4,2);
	$vuosi  = substr($kausi,0,4);

	$paivat = array();

	for($d=0; $d<380; $d++) {
		$v = date("W", mktime(0, 0, 0, 1, 1+$d, $vuosi));
		$y = date("Y", mktime(0, 0, 0, 1, 1+$d, $vuosi));


		if ($v == $viikko) {
			$paivat[] = $d;
		}
	}

	$alku = min($paivat);
	$lopp = max($paivat);

	$viikkoalku  =  date('Y-m-d', mktime(0, 0, 0, 1, 1+$alku, $vuosi));
	$viikkoloppu =  date('Y-m-d', mktime(0, 0, 0, 1, 1+$lopp, $vuosi));
}


if ($tee == 'laheta') {

	echo "<br><br><font class='message'>".t("Asiakastietopakettit lähetetty sähköpostiisi")."!</font><br><br><br>";

	require("laheta_asiakastietopaketti.inc");

	$tee = "";
}


if ($tee == "VALITSE_TIEDOSTO" or $tee == "FILE") {
	require("sisaanlue_suunnitelma.inc");
}

if ($kausi == "") {
	$kausi = date('Y').sprintf('%02d',date('W'));
}

if ($tee == '') {

	echo "<table>";
	echo "<form method='POST' action='$PHP_SELF'>";
	echo "<tr><th colspan='3'>".t("Valitse viikko").":</th><th colspan='2'>".t("Näytä").":</th></tr>";

	$edviikko = substr($kausi,4,2)-1;
	$edvuosi  = substr($kausi,0,4);

	if ($edviikko < 1) {
		$edvuosi--;
		$edviikko = 52;
	}

	$edviikko = sprintf('%02d',$edviikko);

	echo "<tr><td><a href='$PHP_SELF?kausi=$edvuosi$edviikko&vstk=$vstk'>".t("Edellinen")." </a></td>";
	echo "<td><select name='kausi' onchange='submit();'>";

	for($y=date('Y')+1; $y>=date('Y')-1; $y--) {

		for($v=52; $v>=00; $v--) {
			$v = sprintf('%02d',$v);
			$sel = '';

			if ($kausi == $y.$v) {
				$sel = 'SELECTED';
			}

			echo "<option value='$y$v' $sel>$y $v</option>";
		}
	}
	echo "</select></td>";

	$seviikko = substr($kausi,4,2)+1;
	$sevuosi  = substr($kausi,0,4);

	if ($seviikko > 52) {
		$sevuosi++;
		$seviikko = 01;
	}

	$seviikko = sprintf('%02d',$seviikko);

	echo "<td><a href='$PHP_SELF?kausi=$sevuosi$seviikko&vstk=$vstk'>".t("Seuraava")."</a></td>";

	echo "	<td>".t("Viikkosuunnitelma")." <input type='radio' name='vstk' value='Viikkosuunnitelma' $sel1 onclick='submit()'></td>
			<td>".t("Asiakaskäynnit")." <input type='radio' name='vstk' value='Asiakaskäynti' $sel2 onclick='submit()'></td></tr>";

	echo "</form>";
	echo "</table><br>";

	//Haetaan viikon päivät
	viikonpaivat($kausi);

	$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
	$result = pupe_query($query);
	$konsernit = "";

	while ($row = mysql_fetch_assoc($result)) {
		$konsernit .= " '".$row["yhtio"]."' ,";
	}
	$konsernit = " and kalenteri.yhtio in (".substr($konsernit, 0, -1).") ";


	$query = "	SELECT asiakas.postitp, asiakas.postino, asiakas.ytunnus, asiakas.asiakasnro, kalenteri.yhtio, asiakas.nimi,
				asiakas.myyjanro, asiakas.email, asiakas.puhelin,
				left(kalenteri.pvmalku,10) pvmalku,
				kentta01, kentta02, kentta03, kentta04, if(right(pvmalku,8)='00:00:00','',right(pvmalku,8)) aikaalku, if(right(pvmloppu,8)='00:00:00','',right(pvmloppu,8)) aikaloppu
				FROM kalenteri, asiakas use index (ytunnus_index)
				WHERE asiakas.yhtio=kalenteri.yhtio
				and asiakas.tunnus=kalenteri.liitostunnus
				$konsernit
				and kalenteri.kuka     = '$kukarow[kuka]'
				and kalenteri.pvmalku >= '$viikkoalku 00:00:00'
				and kalenteri.pvmalku <= '$viikkoloppu 23:59:59'
				and kalenteri.tapa     = '$vstk'
				and kalenteri.tyyppi in ('kalenteri','memo')
				order by kalenteri.tunnus";
	$result = pupe_query($query);

	echo "<table>";
	echo "<tr><tr>
		<th>".t("Asiakas")."</th>
		<th>".t("Asiakasnumero")."</th>
		<th>".t("Ytunnus")."</th>
		<th>".t("Postitp")."</th>
		<th>".t("Postino")."</th>
		<th>".t("Yhtiöt")."</th>
		<th>".t("Myyjä")."</th>
		<th>".t("Email")."</th>
		<th>".t("Puhelin")."</th>
		<th>".t("Pvm")."</th>";

	if ($vstk == "Asiakaskäynti") {
		echo "<th>".t("Kampanjat")."</th><th>".t("PvmKäyty")."</th><th>".t("Km")."</th><th>".t("Lähtö")."</th><th>".t("Paluu")."</th><th>".t("PvRaha")."</th><th>".t("Kommentit")."</th></tr>";
	}

	while ($row = mysql_fetch_assoc($result)) {
		echo "<tr>
				<td><a href='asiakasmemo.php?ytunnus=$row[ytunnus]'>$row[nimi]</a></td>
				<td>$row[asiakasnro]</td>
				<td>$row[ytunnus]</td>
				<td>$row[postitp]</td>
				<td>$row[postino]</td>
				<td>$row[yhtio]</td>
				<td>$row[myyjanro]</td>
				<td>$row[email]</td>
				<td>$row[puhelin]</td>
				<td>$row[pvmalku]</td>";

		if ($vstk == "Asiakaskäynti") {
			echo "	<td>$row[kentta02]</td>
					<td>$row[pvmalku]</td>
					<td>$row[kentta03]</td>
					<td>$row[aikaalku]</td>
					<td>$row[aikaloppu]</td>
					<td>$row[kentta04]</td>
					<td>$row[kentta01]</td>";
		}

		echo "	</tr>";
	}

	echo "</table>";
}

require("../inc/footer.inc");

?>