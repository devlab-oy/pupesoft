<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Lasku onkin faktoroitu")."</font><hr>";

if (isset($maksuehto) and isset($tunnus)) {

	// tutkaillaan maksuehtoa
	$query = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$maksuehto' and factoring!=''";
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
	$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
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
				and factoring=''";
	$result = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Laskunumerolla")." '$laskuno' ".t("ei löydy normaalia laskua")."!</font><br><br>";
		unset($laskuno);
	}
	else {
		$laskurow = mysql_fetch_array($result);

		echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input name='tunnus' type='hidden' value='$laskurow[ltunnus]'>";
		
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
		$query = "	SELECT tunnus, concat_ws(' ', kassa_teksti, teksti) selite
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]' and factoring!=''
					ORDER BY jarjestys, teksti";
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
	echo "<th>".t("Syötä laskunumero")."</th>";
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
