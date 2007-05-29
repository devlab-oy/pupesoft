<?php

// tarvitaan $ytunnus (jolla haetaan) tai $asiakasid (joka on asiakas.tunnus) ja $kukarow
// palautetaan $asiakasrow

if (trim($konserni) != '') {
	$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
	$result = mysql_query($query) or pupe_error($query);
	$konsyhtiot = "";

	while ($row = mysql_fetch_array($result)) {
		$konsyhtiot .= " '".$row["yhtio"]."' ,";
	}
	$konsyhtiot = " yhtio in (".substr($konsyhtiot, 0, -1).") ";
}
else {
	$konsyhtiot = " yhtio = '$kukarow[yhtio]' ";
}

$ytunnus = addslashes(trim($ytunnus));
$limit   = 300; // montako riviä on maksimi
$monta   = 0;

if (is_numeric($asiakasid) and $asiakasid > 0) {
	$query	= "	SELECT *
				FROM asiakas
				WHERE $konsyhtiot and tunnus='$asiakasid'";
	$result = mysql_query($query) or pupe_error($query);
	$monta  = mysql_num_rows($result);
}
elseif ($ytunnus != '') {
	if (substr($ytunnus,0,1) == "£") {
		// jos eka merkki £ etsitään laskun tunnuksella
		// eka merkki pois alusta
		$ytunnus = substr($ytunnus, 1);

		$query  = "	select *, concat('£', tunnus) tunnus, concat('£', tunnus) ytunnus, ytunnus spec_ytunnus, liitostunnus spec_tunnus
					FROM lasku
					WHERE $konsyhtiot and tunnus='$ytunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$monta  = mysql_num_rows($result);
	}

	if (substr($ytunnus,0,1) == "*") {
		// jos eka merkki on * etsitään laskuilta asiakastietoja

		// eka merkki pois alusta
		$ytunnus = substr($ytunnus, 1);

		// kokeillaan ihan asiakkaan nimellä laskulta
		$query	= "	SELECT nimi, nimitark, osoite, postino, postitp, toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp, concat('£',max(tunnus)) ytunnus, concat('£',max(tunnus)) tunnus, yhtio
					FROM lasku use index (asiakasnimi)
					WHERE $konsyhtiot and tila != 'D' and match (nimi) against ('$ytunnus*' IN BOOLEAN MODE)
					group by 1,2,3,4,5 order by 1";
		$result = mysql_query($query) or pupe_error($query);
		$monta  = mysql_num_rows($result);

		if ($monta != 0) $monta  = 2; // aina selailuun niin menee liitostunnukset ja ytunnukset oikein
	}

	if ($monta == 0  and substr($ytunnus,0,1) == "#") {
		// jos eka merkki on # etsitään toimitusnimen perusteella

		// eka merkki pois alusta
		$ytunnus = substr($ytunnus, 1);

		// kokeillaan ihan asiakkaan nimellä laskulta
		$query	= "	SELECT toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp, nimi, nimitark, osoite, postino, postitp, concat('£',max(tunnus)) ytunnus, concat('£',max(tunnus)) tunnus, yhtio
					FROM lasku use index (asiakastoim_nimi)
					WHERE $konsyhtiot and tila != 'D' and match (toim_nimi) against ('$ytunnus*' IN BOOLEAN MODE)
					group by 1,2,3,4,5 order by 1";
		$result = mysql_query($query) or pupe_error($query);
		$monta  = mysql_num_rows($result);

		if ($monta != 0) $monta  = 2; // aina selailuun niin menee liitostunnukset ja ytunnukset oikein
	}
	
	
	if ($toim == "TYOMAARAYS") {
		$query = "	SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, concat('£',max(lasku.tunnus)) ytunnus, concat('£',max(lasku.tunnus)) tunnus, lasku.yhtio
					FROM lasku
					JOIN tyomaarays ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
					WHERE lasku.yhtio='$kukarow[yhtio]'						
					and lasku.tila in ('A','L','N')
					and tyomaarays.rekno = '$ytunnus'
					group by 1,2,3,4,5 order by 1
					LIMIT 50";
		$result = mysql_query($query) or pupe_error($query);
		$monta  = mysql_num_rows($result);
				
		if ($monta == 0) {
			$query = "	SELECT lasku.nimi, lasku.nimitark, lasku.osoite, lasku.postino, lasku.postitp, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, concat('£',max(lasku.tunnus)) ytunnus, concat('£',max(lasku.tunnus)) tunnus, lasku.yhtio
						FROM lasku
						JOIN tyomaarays ON lasku.yhtio=tyomaarays.yhtio and lasku.tunnus=tyomaarays.otunnus
						WHERE lasku.yhtio='$kukarow[yhtio]'						
						and lasku.tila in ('A','L','N')
						and tyomaarays.valmnro = '$ytunnus'
						group by 1,2,3,4,5 order by 1
						LIMIT 50";
			$result = mysql_query($query) or pupe_error($query);
			$monta  = mysql_num_rows($result);
		}
	}

	if ($monta == 0) {
		// exactihaku
		$query	= "	(SELECT * FROM asiakas use index (ytunnus_index) WHERE $konsyhtiot and ytunnus = '$ytunnus' and ytunnus!='' and laji != 'P')
					UNION
					(SELECT * FROM asiakas use index (asno_index) WHERE $konsyhtiot and asiakasnro = '$ytunnus' and asiakasnro!=0 and laji != 'P')
					UNION
					(SELECT * FROM asiakas use index (ovttunnus_index) WHERE $konsyhtiot and ovttunnus = '$ytunnus' and ovttunnus!='' and laji != 'P')
					UNION
					(SELECT * FROM asiakas use index (toim_ovttunnus_index) WHERE $konsyhtiot and toim_ovttunnus = '$ytunnus' and toim_ovttunnus!='' and laji != 'P')
					ORDER BY nimi";
		$result = mysql_query($query) or pupe_error($query);
		$monta  = mysql_num_rows($result);
	}

	if ($monta == 0) {
		// kokeillaan ytunnuksen alulla
		$query	= "	SELECT *
					FROM asiakas use index (ytunnus_index)
					WHERE $konsyhtiot 
					and ytunnus like '$ytunnus%' 
					and ytunnus!=''
					and laji != 'P'
					ORDER BY asiakas.nimi";
		$result = mysql_query($query) or pupe_error($query);
		$monta  = mysql_num_rows($result);
	}

	if ($monta == 0 and is_string($ytunnus)) {
		// arvailuhaku
		$query	= "	(SELECT * FROM asiakas use index (asiakasnimi) WHERE $konsyhtiot and nimi like '%$ytunnus%' and nimi!='' and laji != 'P')
					UNION
					(SELECT * FROM asiakas use index (asiakasnimitark) WHERE $konsyhtiot and nimitark like '%$ytunnus%' and nimitark!='' and laji != 'P')
					UNION
					(SELECT * FROM asiakas use index (asiakastoim_nimi) WHERE $konsyhtiot and toim_nimi like '%$ytunnus%' and toim_nimi!='' and laji != 'P')
					UNION
					(SELECT * FROM asiakas use index (asiakastoim_nimitark) WHERE $konsyhtiot and toim_nimitark like '%$ytunnus%' and toim_nimitark!='' and laji != 'P')
					UNION
					(SELECT * FROM asiakas use index (ovttunnus_index) WHERE $konsyhtiot and ovttunnus like '%$ytunnus%' and ovttunnus!='' and laji != 'P')
					UNION
					(SELECT * FROM asiakas WHERE $konsyhtiot and postitp like '%$ytunnus%' and postitp!='' and laji != 'P')
					ORDER BY nimi";
		$result = mysql_query($query) or pupe_error($query);
		$monta  = mysql_num_rows($result);
	}
}

if ($monta == 0) {
	echo "<font class='error'>".t("Asiakas")." '$ytunnus' ".t("ei löydy")."!</font><br>";
	$ytunnus = "";
}

if ($monta == 1) {
	$asiakasrow  = mysql_fetch_array($result);
	$ytunnus     = $asiakasrow['ytunnus'];
	$asiakasid   = $asiakasrow['tunnus'];
}

if ($monta > 1 and $monta <= $limit) {
	
	if ($kutsuja != "yllapito.php") {
		//tämä vain otsik.php:tä varten
		echo $lause;

		echo "<table border='0'>";
		echo "<tr>
				<th>".t("ytunnus")."</th>
				<th>".t("asnro")."</th>
				<th>".t("lasknimi/toimnimi")."</th>
				<th colspan='3'>".t("laskosoite/toimosoite")."</th>";

		if ($konserni != '') {
			echo "<th>".t("yhtiö")."</th>";
		}

		echo "<td class='back'></td></tr>";
	}
	
	while ($asiakas = mysql_fetch_array($result)) {

		if     ($asiakas['ytunnus']    != '') $numero = trim($asiakas['ytunnus']);
		elseif ($asiakas['asiakasnro'] != '') $numero = trim($asiakas['asiakasnro']);
		else    die("Tällä asiakkaalla ei ole mitään tietoja! Tee JOTAIN!");

		$brnim 		= '';
		$bros 		= '';
		$brpostino  = '';
		$brpostitp  = '';

		$asiakas['toim_nimi']		= trim($asiakas['toim_nimi']);
		$asiakas['toim_osoite']		= trim($asiakas['toim_osoite']);
		$asiakas['toim_postino']	= trim($asiakas['toim_postino']);
		$asiakas['toim_postitp']	= trim($asiakas['toim_postitp']);

		$asiakas['nimi']			= trim($asiakas['nimi']);
		$asiakas['osoite']			= trim($asiakas['osoite']);
		$asiakas['postino']			= trim($asiakas['postino']);
		$asiakas['postitp']			= trim($asiakas['postitp']);

		if ($kutsuja != "yllapito.php") {
			// jos mikätahansa on eri ja mikätahansa on erisuurta ku tyhjää.. tää on varmaan huomenna daily WTF:ssä.. :)
			if (($asiakas['toim_nimi'] != $asiakas["nimi"] or $asiakas['toim_osoite'] != $asiakas["osoite"] or $asiakas['toim_postino'] != $asiakas["postino"] or $asiakas['toim_postitp'] != $asiakas["postitp"]) and
				($asiakas['toim_nimi'] != "" or $asiakas['toim_osoite'] != "" or $asiakas['toim_postino'] != "" or $asiakas['toim_postitp'] != "")) {
				$brnim     = '<br>'.$asiakas['toim_nimi'];
				$bros      = '<br>'.$asiakas['toim_osoite'];
				$brpostino = '<br>'.$asiakas['toim_postino'];
				$brpostitp = '<br>'.$asiakas['toim_postitp'];
			}
			
			echo "<tr>
					<td>$asiakas[ytunnus]</td>
					<td>$asiakas[asiakasnro]</td>
					<td>$asiakas[nimi] $brnim</td>
					<td>$asiakas[osoite] $bros</td>
					<td>$asiakas[postino] $brpostino</td>
					<td>$asiakas[postitp] $brpostitp</td>";

		
			if ($konserni != '') {
				echo "<td>$asiakas[yhtio]</td>";
			}

			echo "<form action='$PHP_SELF' method='post'>";
			echo "<td class='back'>";
			echo "<input type='hidden' name='asiakasid' 	  value='$asiakas[tunnus]'>";
			echo "<input type='hidden' name='tee' 			  value='$tee'>";
			echo "<input type='hidden' name='konserni'		  value='$konserni'>";
			echo "<input type='hidden' name='toim' 			  value='$toim'>";
			echo "<input type='hidden' name='tilausnumero' 	  value='$tilausnumero'>";
			echo "<input type='hidden' name='from' 	  		  value='$from'>";
			echo "<input type='hidden' name='tila' 			  value='$tila'>";
			echo "<input type='hidden' name='muutparametrit'  value='$muutparametrit'>";
			echo "<input type='hidden' name='ytunnus'  		  value='$numero'>";
			echo "<input type='hidden' name='toimittajaid'	  value='$toimittajaid'>";
			echo "<input type='hidden' name='asiakasyhtio'    value='$asiakas[yhtio]'>";
			echo "<input type='hidden' name='alatila'		  value='$alatila'>";
			echo "<input type='hidden' name='ylatila'  		  value='$ylatila'>";
			echo "<input type='hidden' name='lopetus' 		  value='$lopetus'>";
			echo "<input type='hidden' name='myyja' 		  value='$myyja'>";
			echo "<input type='hidden' name='myyjanro' 		  value='$myyjanro'>";
			echo "<input type='submit' value='".t("Valitse")."'>";
			echo "</td>";
			echo "</form>";
			echo "</tr>";
		}
		else {
			// jos mikätahansa on eri ja mikätahansa on erisuurta ku tyhjää.. tää on varmaan huomenna daily WTF:ssä.. :)
			if (($asiakas['toim_nimi'] != $asiakas["nimi"] or $asiakas['toim_osoite'] != $asiakas["osoite"] or $asiakas['toim_postino'] != $asiakas["postino"] or $asiakas['toim_postitp'] != $asiakas["postitp"]) and
				($asiakas['toim_nimi'] != "" or $asiakas['toim_osoite'] != "" or $asiakas['toim_postino'] != "" or $asiakas['toim_postitp'] != "")) {
				$brnim     = ', '.$asiakas['toim_nimi'];
				$bros      = ' '.$asiakas['toim_osoite'];
				$brpostino = ' '.$asiakas['toim_postino'];
				$brpostitp = ' '.$asiakas['toim_postitp'];
			}
			
			$ulos .= "<option value='$asiakas[ytunnus]'>$asiakas[ytunnus] $asiakas[nimi] $asiakas[osoite] $asiakas[postino] $asiakas[postitp] $brnim $bros $brpostitp $brpostitp</option>";
		}
	}
	
	if ($kutsuja != "yllapito.php") {
		echo "</table><br>";
	}
	
	$ytunnus = "";
}

if ($monta > $limit) {
	echo "<font class='error'>".t("Asiakashaulla löytyi")." $monta ".t("sopivaa asiakasta. Tarkenna hakuasi")."!</font><br><br>";
	$ytunnus = "";
}

if ($kutsuja != "yllapito.php") {
	$query = "	SELECT yhtio
				FROM oikeu
				WHERE yhtio	= '$kukarow[yhtio]'
				and kuka	= '$kukarow[kuka]'
				and nimi	= 'yllapito.php'
				and alanimi = 'asiakas'
				and paivitys != ''";
	$oikeuresult = mysql_query($query) or pupe_error($query);

	if ($monta != 1 and mysql_num_rows($oikeuresult) > 0) {
		//Annetaan mahdollisuus perustaa uusi asiakas

		echo "<table border='0'>";

		echo "<tr><td>".t("Oikeaa asiakasta ei löytynyt. Perusta uusi asiakas")."</td>";
		echo "<td class='back'>";
		echo "<form action='../yllapito.php' method='post'>";
		echo "<input type='hidden' name='lopetus' 	value='tilauskasittely/tilaus_myynti.php////toim=$toim'>";
		echo "<input type='hidden' name='toim'		value='asiakas!!!VAHITTAISMYYNTI!!!true'>";
		echo "<input type='hidden' name='uusi'		value='1'>";
		echo "<input type='submit' value='".t("Uusi henkilöasiakas")."'>";
		echo "</form>";
		echo "</td>";
	
		echo "<td class='back'>";
		echo "<form action='../yllapito.php' method='post'>";
		echo "<input type='hidden' name='lopetus' 	value='tilauskasittely/tilaus_myynti.php////toim=$toim'>";
		echo "<input type='hidden' name='toim'		value='asiakas'>";
		echo "<input type='hidden' name='uusi'		value='1'>";
		echo "<input type='submit' value='".t("Uusi yritysasiakas")."'>";
		echo "</form>";
		echo "</td>";
	
		echo "</tr>";

		echo "</table><br>";
	}
}

?>
