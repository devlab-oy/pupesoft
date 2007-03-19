<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Muuta factorointia")."</font><hr>";

if (isset($maksuehto) and isset($tunnus)) {

	// tutkaillaan maksuehtoa
	$query = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$maksuehto' and factoring!=''";
	if ($laji == 'pois') {
		$query = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$maksuehto' and factoring=''";
	}

	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Maksuehto katosi")."!</font><br><br>";
		unset($laskuno);
		unset($maksuehto);
	}
	else {
		$mehtorow = mysql_fetch_array($result);
	}
	
	// tutkaillaan laskua
	$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and mapvm = '0000-00-00'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Lasku katosi")."!</font> ($tunnus)<br><br>";
		unset($laskuno);
		unset($tunnus);
	}
	else {
		$laskurow = mysql_fetch_array($result);
	}
}

if (isset($maksuehto) and isset($tunnus)) {

	// korjaillaan eräpäivät ja kassa-alet
	if ($mehtorow['abs_pvm'] == '0000-00-00') {
		$erapvm = "adddate('$laskurow[tapvm]', interval $mehtorow[rel_pvm] day)";
	}
	else {
		$erapvm = "'$mehtorow[abs_pvm]'";
	}

	if ($mehtorow['kassa_teksti'] != '') {
		if ($mehtorow['kassa_abspvm'] == '0000-00-00') {
			$kassa_erapvm = "adddate('$laskurow[tapvm]', interval $mehtorow[kassa_relpvm] day)";
		}
		else {
			$kassa_erapvm = "'$mehtorow[kassa_abspvm]'";
		}
		$kassa_loppusumma = round($laskurow['summa']*$mehtorow['kassa_alepros']/100, 2);
	}
	else {
		$kassa_erapvm     = "''";
		$kassa_loppusumma = "";
	}

	// päivitetään lasku	
	$query = "	update lasku set
				maksuehto ='$maksuehto',
				erpcm     = $erapvm,
				kapvm     = $kassa_erapvm,
				kasumma   ='$kassa_loppusumma'
				where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_affected_rows() > 0) {
		echo "<font class='message'>".t("Muutettin laskun")." $laskurow[laskunro] ".t("maksuehdoksi")." $mehtorow[teksti] $mehtorow[kassa_teksti]</font><br>";	
	}
	else {
		echo "<font class='error'>".t("Laskua")." $laskurow[laskunro] ".t("ei pystytty muuttamaan")."!</font><br>";	
	}

	// tehdään kirjanpitomuutokset
	$query = "update tiliointi set tilino='$yhtiorow[factoringsaamiset]' where yhtio='$kukarow[yhtio]' and ltunnus='$tunnus' and tilino='$yhtiorow[myyntisaamiset]' and tapvm='$laskurow[tapvm]'";
	if ($laji == 'pois') {
		$query = "update tiliointi set tilino='$yhtiorow[myyntisaamiset]' where yhtio='$kukarow[yhtio]' and ltunnus='$tunnus' and tilino='$yhtiorow[factoringsaamiset]' and tapvm='$laskurow[tapvm]'";
	}
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_affected_rows() > 0) {
		echo "<font class='message'>".t("Korjattiin kirjanpitoviennit")." (".mysql_affected_rows()." ".t("kpl").").</font><br>";
	}
	else {
		echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehdä! Korjaa kirjanpito käsin")."!</font><br>";
	}
	unset($laskuno);
}

if (isset($laskuno)) {

	// haetaan lasku. pitää factoroimaton
	$query = "select *, lasku.tunnus ltunnus 
				from lasku, maksuehto 
				where lasku.yhtio='$kukarow[yhtio]' 
				and lasku.yhtio=maksuehto.yhtio
				and lasku.maksuehto=maksuehto.tunnus
				and lasku.laskunro='$laskuno' 
				and tila='U' 
				and alatila='X' 
				and factoring=''
				and mapvm = '0000-00-00'";
	if ($laji == 'pois') {
		$query = "select *, lasku.tunnus ltunnus 
			from lasku, maksuehto 
			where lasku.yhtio='$kukarow[yhtio]' 
			and lasku.yhtio=maksuehto.yhtio
			and lasku.maksuehto=maksuehto.tunnus
			and lasku.laskunro='$laskuno' 
			and tila='U' 
			and alatila='X' 
			and factoring!=''
			and mapvm = '0000-00-00'";
	}
	$result = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($result) == 0) {
		if ($laji == 'pois')
			echo "<font class='error'>".t("Laskunumerolla")." '$laskuno' ".t("ei löydy factoroitua laskua")."!</font><br><br>";
		else 
			echo "<font class='error'>".t("Laskunumerolla")." '$laskuno' ".t("ei löydy normaalia laskua")."!</font><br><br>";
		unset($laskuno);
	}
	else {
		$laskurow = mysql_fetch_array($result);

		echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input name='tunnus' type='hidden' value='$laskurow[ltunnus]'>";
		echo "<input name='laji' type='hidden' value='$laji'>";
		echo "<table>
			<tr><th>".t("Laskutusosoite")."</th><th>".t("Toimitusosoite")."</th></tr>
			<tr><td>$laskurow[ytunnus]<br> $laskurow[nimi] $laskurow[nimitark]<br> $laskurow[osoite]<br> $laskurow[postino] $laskurow[postitp]</td><td>$laskurow[ytunnus]<br> $laskurow[toim_nimi] $laskurow[toim_nimitark]<br> $laskurow[toim_osoite]<br> $laskurow[toim_postino] $laskurow[toim_postitp]</td></tr>
			<tr><th>".t("Laskunumero")."</th><td>$laskurow[laskunro]</td></tr>
			<tr><th>".t("Laskun summa")."</th><td>$laskurow[summa]</td></tr>
			<tr><th>".t("Laskun summa (veroton)")."</th><td>$laskurow[arvo]</td></tr>
			<tr><th>".t("Maksuehto")."</th><td>$laskurow[teksti]</td></tr>
			<tr><th>".t("Tapahtumapäivä")."</th><td>$laskurow[tapvm]</td></tr>
			<tr><th>".t("Uusi maksuehto")."</th>
			<td>";

		// haetaan kaikki factoringmaksuehdot
		$query = "SELECT maksuehto.tunnus, concat_ws(' ', ".avain('selectcon','MEHTOKATXT_').",  ".avain('selectcon2','MEHTOTXT_').") selite
				FROM maksuehto
				".avain('join','MEHTOKATXT_')."
				".avain('join2','MEHTOTXT_')."
				WHERE maksuehto.yhtio = '$kukarow[yhtio]' and maksuehto.factoring!=''
				ORDER BY maksuehto.jarjestys, maksuehto.teksti";

		if ($laji == 'pois') {
			$query = "SELECT maksuehto.tunnus, concat_ws(' ', ".avain('selectcon','MEHTOKATXT_').",  ".avain('selectcon2','MEHTOTXT_').") selite
					FROM maksuehto
					".avain('join','MEHTOKATXT_')."
					".avain('join2','MEHTOTXT_')."
					WHERE maksuehto.yhtio = '$kukarow[yhtio]' and maksuehto.factoring=''
					ORDER BY maksuehto.jarjestys, maksuehto.teksti";
		}

		$vresult = mysql_query($query) or pupe_error($query);
		
		echo "<select name='maksuehto'>";

		while ($vrow=mysql_fetch_array($vresult)) {
			echo "<option value='$vrow[tunnus]'>$vrow[selite]</option>";
		}
		echo "</select>";
				
		echo "</td></tr></table><br>";

		echo "<input name='subnappi' type='submit' value='".t("Muuta maksuehto")."'>";
		echo "</form>";
	}
}


if (!isset($laskuno)) {
	echo "<form name='eikat' action='$PHP_SELF' method='post' autocomplete='off'>";
	echo "<table><tr>";
	echo "<td><input type='radio' name='laji' value='paalle' checked> ".t("Lisää factoring")."</td>";
	echo "<td><input type='radio' name='laji' value='pois'> ".t("Poista factoring")."</td></tr>";
	echo "<tr><th>".t("Syötä laskunumero")."</th>";
	echo "<td><input type='text' name='laskuno'></td>";
	echo "<td class='back'><input name='subnappi' type='submit' value='".t("Hae lasku")."'></td>";
	echo "</tr></table>";
	echo "</form>";
}

// kursorinohjausta
$formi = "eikat";
$kentta = "laskuno";

require ("../inc/footer.inc");

?>
