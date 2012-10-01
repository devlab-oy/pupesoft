<?php

require ("../inc/parametrit.inc");

if ($toim == 'KATEINEN') {
	echo "<font class='head'>".t("Lasku halutaankin maksaa käteisellä")."</font><hr>";
	if ((int)$maksuehto != 0 and (int)$tunnus != 0) {
		$mehtorow = hae_maksuehto($kukarow, $maksuehto);

		$laskurow = hae_lasku($kukarow, $tunnus);

		$konsrow = hae_asiakas($kukarow, $laskurow);

		$paiva_string = $tapahtumapaiva_vv.'-'.$tapahtumapaiva_kk.'-'.$tapahtumapaiva_pp;
		$tapahtumapaiva = new DateTime($paiva_string);

		$myysaatili = korjaa_erapaivat_ja_alet_ja_paivita_lasku($kukarow, $yhtiorow, $konsrow, $mehtorow, $laskurow, $maksuehto, $tunnus, $toim, $tapahtumapaiva);

		tee_kirjanpito_muutokset($kukarow, $laskurow, $yhtiorow, $tunnus, $myysaatili, $toim);

		yliviivaa_alet_ja_pyoristykset($kukarow, $yhtiorow, $tunnus);

		tarkista_pyoristys_erotukset($kukarow, $yhtiorow, $laskurow, $tunnus);

		if (empty($mehtorow) && empty($laskurow)) {
			$laskuno = 0;
			$tunnus = 0;
			$maksuehto = 0;
		}

		$laskuno = 0;
	}

	if ((int)$laskuno != 0) {
		$laskurow = hae_lasku2($kukarow, $laskuno, $toim);

		if (empty($laskurow)) {
			$laskuno = 0;
		}
		else {
			echo_lasku_table($kukarow, $laskurow, $toim);
		}
	}

	if ($laskuno == 0) {
		echo_lasku_search();
	}
}
else {
	echo "<font class='head'>".t("Lasku ei ollutkaan käteistä")."</font><hr>";
	if ((int)$maksuehto != 0 and (int)$tunnus != 0) {
		$mehtorow = hae_maksuehto($kukarow, $maksuehto);

		$laskurow = hae_lasku($kukarow, $tunnus);

		$konsrow = hae_asiakas($kukarow, $laskurow);

		$myysaatili = korjaa_erapaivat_ja_alet_ja_paivita_lasku($kukarow, $yhtiorow, $konsrow, $mehtorow, $laskurow, $maksuehto, $tunnus, $toim, $tapahtumapaiva);

		tee_kirjanpito_muutokset($kukarow, $laskurow, $yhtiorow, $tunnus, $myysaatili, $toim);

		yliviivaa_alet_ja_pyoristykset($kukarow, $yhtiorow, $tunnus);

		tarkista_pyoristys_erotukset($kukarow, $yhtiorow, $laskurow, $tunnus);

		if (empty($mehtorow) && empty($laskurow)) {
			$laskuno = 0;
			$tunnus = 0;
			$maksuehto = 0;
		}

		$laskuno = 0;
	}

	if ((int)$laskuno != 0) {
		$laskurow = hae_lasku2($kukarow, $laskuno, $toim);

		if (empty($laskurow)) {
			$laskuno = 0;
		}
		else {
			echo_lasku_table($kukarow, $laskurow, $toim);
		}
	}

	if ($laskuno == 0) {
		echo_lasku_search();
	}
}

//kursorinohjausta
$formi = "eikat";
$kentta = "laskuno";

function hae_maksuehto($kukarow, $maksuehto) {
	$query = "	SELECT *
				FROM maksuehto
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus = '$maksuehto'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Maksuehto katosi")."!</font><br><br>";

		return null;
	}
	else {
		return mysql_fetch_assoc($result);
	}
}

function hae_lasku($kukarow, $tunnus) {
	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus = '$tunnus'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Lasku katosi")."!</font><br><br>";

		return null;
	}
	else {
		return mysql_fetch_assoc($result);
	}
}

function hae_asiakas($kukarow, $laskurow) {
	$query = "	SELECT konserniyhtio
				FROM asiakas
				WHERE yhtio = '$kukarow[yhtio]'
				and ytunnus = '$laskurow[ytunnus]'";
	$konsres = pupe_query($query);
	return mysql_fetch_assoc($konsres);
}

function korjaa_erapaivat_ja_alet_ja_paivita_lasku($kukarow, $yhtiorow, $konsrow, $mehtorow, $laskurow, $maksuehto, $tunnus, $toim, $tapahtumapaiva = null) {
	if ($toim == 'KATEINEN') {
		$query = "	UPDATE lasku set
				mapvm     = '".$tapahtumapaiva->format('Y-m-d')."',
				maksuehto = '$maksuehto',
				erpcm     = '".$tapahtumapaiva->format('Y-m-d')."',
				kapvm     = '".$tapahtumapaiva->format('Y-m-d')."',
				kasumma   = '$kassa_loppusumma'
				where yhtio = '$kukarow[yhtio]'
				and tunnus  = '$tunnus'";
		$result = pupe_query($query);
	}
	else {
		// korjaillaan eräpäivät ja kassa-alet
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
			$kassa_loppusumma = round($laskurow['summa'] * $mehtorow['kassa_alepros'] / 100, 2);
		}
		else {
			$kassa_erapvm = "''";
			$kassa_loppusumma = "";
		}

		// päivitetään lasku
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
	}

	if ($mehtorow["factoring"] != "") {
		$myysaatili = $yhtiorow['factoringsaamiset'];
	}
	elseif ($konsrow["konserniyhtio"] != "") {
		$myysaatili = $yhtiorow['konsernimyyntisaamiset'];
	}
	else {
		$myysaatili = $yhtiorow['myyntisaamiset'];
	}
	return $myysaatili;
}

function tee_kirjanpito_muutokset($kukarow, $laskurow, $yhtiorow, $tunnus, $myysaatili, $toim) {
	if ($toim == 'KATEINEN') {
		$query = "	UPDATE tiliointi
				SET tilino = '$yhtiorow[kassa]',
				summa = '$laskurow[summa]'
				WHERE yhtio	= '$kukarow[yhtio]'
				and ltunnus	= '$tunnus'
				and tilino	= '$myysaatili'";
	}
	else {
		$query = "	UPDATE tiliointi
				SET tilino = '$myysaatili',
				summa = '$laskurow[summa]'
				WHERE yhtio	= '$kukarow[yhtio]'
				and ltunnus	= '$tunnus'
				and tilino	= '$yhtiorow[kassa]'";
	}

	$result = pupe_query($query);

	if (mysql_affected_rows() > 0) {
		echo "<font class='message'>".t("Korjattiin kirjanpitoviennit")." (".mysql_affected_rows()." ".t("kpl").").</font><br>";
	}
	else {
		echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehdä! Korjaa kirjanpito käsin")."!</font><br>";
	}
}

function yliviivaa_alet_ja_pyoristykset($kukarow, $yhtiorow, $tunnus) {
	$query = "	UPDATE tiliointi
				SET korjattu = 'X',
				korjausaika  = now()
				where yhtio = '$kukarow[yhtio]'
				and ltunnus = '$tunnus'
				and tilino  IN ('$yhtiorow[myynninkassaale]', '$yhtiorow[pyoristys]')";
	$result = pupe_query($query);

	if (mysql_affected_rows() > 0) {
		echo "<font class='message'>".t("Poistettiin pyöristys- ja kassa-alekirjaukset")." (".mysql_affected_rows()." ".t("kpl").").</font><br><br>";
	}
}

function tarkista_pyoristys_erotukset($kukarow, $yhtiorow, $laskurow, $tunnus) {
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
					selite 				= '".t("Pyöristysero")."',
					lukko 				= '',
					laatija 			= '$kukarow[kuka]',
					laadittu 			= now()";
		$laskutusres = pupe_query($query);
	}
}

function hae_lasku2($kukarow, $laskuno, $toim) {
	if ($toim == 'KATEINEN') {
		$query = "	SELECT lasku.*, lasku.tunnus ltunnus, maksuehto.tunnus, maksuehto.teksti
				from lasku
				JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.kateinen=''
				where lasku.yhtio	= '$kukarow[yhtio]'
				and lasku.laskunro	= '$laskuno'
				and lasku.tila		= 'U'
				and lasku.alatila	= 'X'";
	}
	else {
		$query = "	SELECT lasku.*, lasku.tunnus ltunnus, maksuehto.tunnus, maksuehto.teksti
				from lasku
				JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.kateinen!=''
				where lasku.yhtio	= '$kukarow[yhtio]'
				and lasku.laskunro	= '$laskuno'
				and lasku.tila		= 'U'
				and lasku.alatila	= 'X'";
	}

	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Laskunumerolla")." '$laskuno' ".t("ei löydy käteislaskua")."!</font><br><br>";
	}

	return mysql_fetch_assoc($result);
}

function echo_lasku_table($kukarow, $laskurow, $toim) {
	echo "<form method='post' autocomplete='off'>";
	echo "<input name='tunnus' type='hidden' value='$laskurow[ltunnus]'>";

	echo "<table>
			<tr><th>".t("Laskutusosoite")."</th><th>".t("Toimitusosoite")."</th></tr>
			<tr><td>$laskurow[ytunnus]<br> $laskurow[nimi] $laskurow[nimitark]<br> $laskurow[osoite]<br> $laskurow[postino] $laskurow[postitp]</td><td>$laskurow[ytunnus]<br> $laskurow[toim_nimi] $laskurow[toim_nimitark]<br> $laskurow[toim_osoite]<br> $laskurow[toim_postino] $laskurow[toim_postitp]</td></tr>
			<tr><th>".t("Laskunumero")."</th><td>$laskurow[laskunro]</td></tr>
			<tr><th>".t("Laskun summa")."</th><td>$laskurow[summa]</td></tr>
			<tr><th>".t("Laskun summa (veroton)")."</th><td>$laskurow[arvo]</td></tr>
			<tr><th>".t("Maksuehto")."</th><td>".t_tunnus_avainsanat($laskurow, "teksti", "MAKSUEHTOKV")."</td></tr>";

	if ($toim == 'KATEINEN') {
		$now = new DateTime('now');
		// haetaan kaikki käteisen maksuehdot
		$query = 'SELECT * FROM kassalipas WHERE kassalipas.yhtio="'.$kukarow['yhtio'].'"';
		$result = pupe_query($query);

		echo '<tr>';
		echo "<th>".t('Kassalipas')."</th>";
		echo '<td>';
		echo '<select name="kassalipas">';
		while ($row = mysql_fetch_assoc($result)) {
			echo '<option value="'.$row['tunnus'].'">'.t($row['nimi']).'</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo "<tr><th>".t("Tapahtumapäivä")."</th><td>".t('Päivä')." <input id='tapahtumapaiva_pp' type='text' size='2' value='".$now->format('d')."'/> ".t('Kuukausi')." <input id='tapahtumapaiva_kk' type='text' size='2' value='".$now->format('m')."'/> ".t('Vuosi')." <input id='tapahtumapaiva_vv' type='text' size='4' value='".$now->format('Y')."'/></td></tr>";

		$query = "	SELECT *
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]' and kateinen!=''
					ORDER BY jarjestys, teksti";
	}
	else {
		echo "<tr><th>".t("Tapahtumapäivä")."</th><td>$laskurow[tapvm]</td></tr>";

		// haetaan kaikki maksuehdot (paitsi käteinen)
		$query = "	SELECT *
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]' and kateinen=''
					ORDER BY jarjestys, teksti";
	}
	$vresult = pupe_query($query);

	echo "<tr><th>".t("Uusi maksuehto")."</th>";
	echo "<td>";
	echo "<select name='maksuehto'>";

	while ($vrow = mysql_fetch_assoc($vresult)) {
		echo "<option value='$vrow[tunnus]'>".t_tunnus_avainsanat($vrow, "teksti", "MAKSUEHTOKV")."</option>";
	}

	echo "</select>";
	echo "</td></tr></table><br>";
	echo "<input name='subnappi' type='submit' value='".t("Muuta maksuehto")."'></td>";
	echo "</form>";
}

function echo_lasku_search() {
	echo "<form name='eikat' method='post' autocomplete='off'>";
	echo "<table><tr>";
	echo "<th>".t("Syötä laskunumero")."</th>";
	echo "<td><input type='text' name='laskuno'></td>";
	echo "<td class='back'><input name='subnappi' type='submit' value='".t("Etsi")."'></td>";
	echo "</tr></table>";
	echo "</form>";
}

require ("inc/footer.inc");
?>