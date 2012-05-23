<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Lasku ei ollutkaan k�teist�")."</font><hr>";

if ((int) $maksuehto != 0 and (int) $tunnus != 0) {
	// tutkaillaan maksuehtoa
	$query = "	SELECT *
				FROM maksuehto
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus = '$maksuehto'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Maksuehto katosi")."!</font><br><br>";
		$laskuno   = 0;
		$tunnus    = 0;
		$maksuehto = 0;
	}
	else {
		$mehtorow = mysql_fetch_assoc($result);
	}

	// tutkaillaan laskua
	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus = '$tunnus'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Lasku katosi")."!</font><br><br>";
		$laskuno   = 0;
		$tunnus    = 0;
		$maksuehto = 0;
	}
	else {
		$laskurow = mysql_fetch_assoc($result);
	}

	// haetaan asiakkaan tiedot (esim konserniyhti�)
	$query = "	SELECT konserniyhtio
				FROM asiakas
				WHERE yhtio = '$kukarow[yhtio]'
				and ytunnus = '$laskurow[ytunnus]'";
	$konsres = pupe_query($query);
	$konsrow = mysql_fetch_assoc($konsres);
}

if ((int) $maksuehto != 0 and (int) $tunnus != 0) {

	// korjaillaan er�p�iv�t ja kassa-alet
	if ($mehtorow['abs_pvm'] == '0000-00-00') {
		$erapvm = "adddate('$laskurow[tapvm]', interval $mehtorow[rel_pvm] day)";
	}
	else {
		$erapvm = "'$mehtorow[abs_pvm]'";
	}

	if ($mehtorow['kassa_abspvm'] != '0000-00-00' or $mehtorow["kassa_relpvm"] > 0) {
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

	// p�ivitet��n lasku
	$query = "	UPDATE lasku set
				mapvm     = '',
				maksuehto = '$maksuehto',
				erpcm     = $erapvm,
				kapvm     = $kassa_erapvm,
				kasumma   = '$kassa_loppusumma'
				where yhtio = '$kukarow[yhtio]'
				and tunnus  = '$tunnus'";
	$result = pupe_query($query);

	if (mysql_affected_rows() > 0) {
		echo "<font class='message'>".t("Muutettin laskun")." $laskurow[laskunro] ".t("maksuehdoksi")." ".t_tunnus_avainsanat($mehtorow, "teksti", "MAKSUEHTOKV")." ".t("ja merkattiin maksu avoimeksi").".</font><br>";
	}
	else {
		echo "<font class='error'>".t("Laskua")." $laskurow[laskunro] ".t("ei pystytty muuttamaan")."!</font><br>";
	}

	if ($mehtorow["factoring"] != "") {
		$myysaatili	= $yhtiorow['factoringsaamiset'];
	}
	elseif ($konsrow["konserniyhtio"] != "") {
		$myysaatili	= $yhtiorow['konsernimyyntisaamiset'];
	}
	else {
		$myysaatili	= $yhtiorow['myyntisaamiset'];
	}

	// tehd��n kirjanpitomuutokset
	$query = "	UPDATE tiliointi
				SET tilino = '$myysaatili',
				summa = '$laskurow[summa]'
				WHERE yhtio	= '$kukarow[yhtio]'
				and ltunnus	= '$tunnus'
				and tilino	= '$yhtiorow[kassa]'";
	$result = pupe_query($query);

	if (mysql_affected_rows() > 0) {
		echo "<font class='message'>".t("Korjattiin kirjanpitoviennit")." (".mysql_affected_rows()." ".t("kpl").").</font><br>";
	}
	else {
		echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehd�! Korjaa kirjanpito k�sin")."!</font><br>";
	}

	// yliviivataan kassa-aletili�innit ja py�ristykset
	$query = "	UPDATE tiliointi
				SET korjattu = 'X',
				korjausaika  = now()
				where yhtio = '$kukarow[yhtio]'
				and ltunnus = '$tunnus'
				and tilino  IN ('$yhtiorow[myynninkassaale]', '$yhtiorow[pyoristys]')";
	$result = pupe_query($query);

	if (mysql_affected_rows() > 0) {
		echo "<font class='message'>".t("Poistettiin py�ristys- ja kassa-alekirjaukset")." (".mysql_affected_rows()." ".t("kpl").").</font><br><br>";
	}

	// tarkistetaan onko py�ristyseroja
	$query = "	SELECT sum(summa) summa, sum(summa_valuutassa) summa_valuutassa
				FROM tiliointi
				WHERE yhtio = '$kukarow[yhtio]'
				AND ltunnus = '$tunnus'
				AND korjattu = ''";
	$result = pupe_query($query);
	$check1 = mysql_fetch_assoc($result);

	if ($check1['summa'] != 0) {
		$query = "	INSERT into tiliointi set
					yhtio 				= '$kukarow[yhtio]',
					ltunnus 			= '$tunnus',
					tilino 				= '$yhtiorow[pyoristys]',
					kustp 				= 0,
					kohde 				= 0,
					projekti 			= 0,
					tapvm 				= '$laskurow[tapvm]',
					summa 				= -1 * $check1[summa],
					summa_valuutassa 	= -1 * $check1[summa_valuutassa],
					valkoodi			= '$laskurow[valkoodi]',
					vero 				= 0,
					selite 				= '".t("Py�ristysero")."',
					lukko 				= '',
					laatija 			= '$kukarow[kuka]',
					laadittu 			= now()";
		$laskutusres = pupe_query($query);
	}

	$laskuno = 0;
}

if ((int) $laskuno != 0) {
	// haetaan lasku. pit�� olla maksettu ja maksuehto k�teinen
	$query = "	SELECT lasku.*, lasku.tunnus ltunnus, maksuehto.tunnus, maksuehto.teksti
				from lasku
				JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.kateinen!=''
				where lasku.yhtio	= '$kukarow[yhtio]'
				and lasku.laskunro	= '$laskuno'
				and lasku.tila		= 'U'
				and lasku.alatila	= 'X'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Laskunumerolla")." '$laskuno' ".t("ei l�ydy k�teislaskua")."!</font><br><br>";
		$laskuno = 0;
	}
	else {
		$laskurow = mysql_fetch_assoc($result);

		echo "<form method='post' autocomplete='off'>";
		echo "<input name='tunnus' type='hidden' value='$laskurow[ltunnus]'>";

		echo "<table>
			<tr><th>".t("Laskutusosoite")."</th><th>".t("Toimitusosoite")."</th></tr>
			<tr><td>$laskurow[ytunnus]<br> $laskurow[nimi] $laskurow[nimitark]<br> $laskurow[osoite]<br> $laskurow[postino] $laskurow[postitp]</td><td>$laskurow[ytunnus]<br> $laskurow[toim_nimi] $laskurow[toim_nimitark]<br> $laskurow[toim_osoite]<br> $laskurow[toim_postino] $laskurow[toim_postitp]</td></tr>
			<tr><th>".t("Laskunumero")."</th><td>$laskurow[laskunro]</td></tr>
			<tr><th>".t("Laskun summa")."</th><td>$laskurow[summa]</td></tr>
			<tr><th>".t("Laskun summa (veroton)")."</th><td>$laskurow[arvo]</td></tr>
			<tr><th>".t("Maksuehto")."</th><td>".t_tunnus_avainsanat($laskurow, "teksti", "MAKSUEHTOKV")."</td></tr>
			<tr><th>".t("Tapahtumap�iv�")."</th><td>$laskurow[tapvm]</td></tr>
			<tr><th>".t("Uusi maksuehto")."</th>
			<td>";

		// haetaan kaikki maksuehdot (paitsi k�teinen)
		$query = "	SELECT *
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]' and kateinen=''
					ORDER BY jarjestys, teksti";
		$vresult = pupe_query($query);

		echo "<select name='maksuehto'>";

		while ($vrow=mysql_fetch_assoc($vresult)) {
			echo "<option value='$vrow[tunnus]'>".t_tunnus_avainsanat($vrow, "teksti", "MAKSUEHTOKV")."</option>";
		}

		echo "</select>";
		echo "</td></tr></table><br>";
		echo "<input name='subnappi' type='submit' value='".t("Muuta maksuehto")."'></td>";
		echo "</form>";
	}
}


if ($laskuno == 0) {
	echo "<form name='eikat' method='post' autocomplete='off'>";
	echo "<table><tr>";
	echo "<th>".t("Sy�t� laskunumero")."</th>";
	echo "<td><input type='text' name='laskuno'></td>";
	echo "<td class='back'><input name='subnappi' type='submit' value='".t("Etsi")."'></td>";
	echo "</tr></table>";
	echo "</form>";
}

// kursorinohjausta
$formi = "eikat";
$kentta = "laskuno";

require ("inc/footer.inc");

?>