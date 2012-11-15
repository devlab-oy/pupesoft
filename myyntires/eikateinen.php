<?php

require ("../inc/parametrit.inc");

if($toim == 'KATEINEN') {
	echo "<font class='head'>".t("Lasku halutaankin maksaa käteisellä")."</font><hr>";
}
else {
	echo "<font class='head'>".t("Lasku ei ollutkaan käteistä")."</font><hr>";
}

if ((int) $maksuehto != 0 and (int) $tunnus != 0) {
	$tapahtumapaiva  = date('Y-m-d', mktime(0,0,0,$tapahtumapaiva_kk,$tapahtumapaiva_pp,$tapahtumapaiva_vv));

	$tilikausi = tarkista_saako_laskua_muuttaa($tapahtumapaiva);
	if(empty($tilikausi)) {
		$laskurow = hae_lasku($tunnus);
		$mehtorow = hae_maksuehto($laskurow['maksuehto']);
		$konsrow  = hae_asiakas($laskurow);
		$kassalipasrow = hae_kassalipas($kassalipas);

		$params = array(
			'konsrow'		 => $konsrow,
			'mehtorow'		 => $mehtorow,
			'laskurow'		 => $laskurow,
			'maksuehto'		 => $maksuehto,
			'tunnus'		 => $tunnus,
			'toim'			 => $toim,
			'tapahtumapaiva' => $tapahtumapaiva,
			'kassalipas'	 => $kassalipas
		);

		$myysaatili  = korjaa_erapaivat_ja_alet_ja_paivita_lasku($params);
		$mehtorow = hae_maksuehto($maksuehto);
		$_kassalipas = hae_kassalippaan_tiedot($kassalipas, $mehtorow, $laskurow);

		$params = array(
			'laskurow'		 => $laskurow,
			'tunnus'		 => $tunnus,
			'myysaatili'	 => $myysaatili,
			'toim'			 => $toim,
			'_kassalipas'	 => $_kassalipas
		);

		tee_kirjanpito_muutokset($params);
		yliviivaa_alet_ja_pyoristykset($tunnus);
		tarkista_pyoristys_erotukset($laskurow, $tunnus);

		if($toim == 'KATEINEN') {
			vapauta_kateistasmaytys($kassalipasrow, $tapahtumapaiva);
		}

		if (empty($mehtorow) and empty($laskurow)) {
			$laskuno 	= 0;
			$tunnus 	= 0;
			$maksuehto 	= 0;
		}

		$laskuno = 0;
	}
	else {
		echo "<font class='error'>".t("Tilikausi on päättynyt {$tilikausi['tilikausi_alku']}. Et voi merkitä laskua maksetuksi {$tapahtumapaiva}")."</font>";
	}
}

if ((int) $laskuno != 0) {
	$laskurow = hae_lasku2($laskuno, $toim);

	if (empty($laskurow)) {
		$laskuno = 0;
	}
	else {
		echo_lasku_table($laskurow, $toim);
	}
}

if ($laskuno == 0) {
	echo_lasku_search();
}

//kursorinohjausta
$formi = "eikat";
$kentta = "laskuno";

function hae_maksuehto($maksuehto) {
	global $kukarow;

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

function hae_lasku($tunnus) {
	global $kukarow;

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

function hae_asiakas($laskurow) {
	global $kukarow;

	$query = "	SELECT konserniyhtio
				FROM asiakas
				WHERE yhtio = '{$kukarow['yhtio']}'
				and tunnus = '{$laskurow['liitostunnus']}'";
	$konsres = pupe_query($query);

	return mysql_fetch_assoc($konsres);
}

function korjaa_erapaivat_ja_alet_ja_paivita_lasku($params) {
	global $kukarow, $yhtiorow;

	if ($params['toim'] == 'KATEINEN') {
		$query	 = "	UPDATE lasku set
						mapvm      = '{$params['tapahtumapaiva']}',
						maksuehto  = '{$params['maksuehto']}',
						erpcm      = '{$params['tapahtumapaiva']}',
						kapvm      = '{$params['tapahtumapaiva']}',
						tapvm	   = '{$params['tapahtumapaiva']}',
						kassalipas = '{$params['kassalipas']}'
						where yhtio = '{$kukarow['yhtio']}'
						and tunnus  = '{$params['tunnus']}'";
		$result = pupe_query($query);
	}
	else {
		// korjaillaan eräpäivät ja kassa-alet
		if ($params['mehtorow']['abs_pvm'] == '0000-00-00') {
			$erapvm = "adddate('{$params['laskurow']['tapvm']}', interval {$params['mehtorow']['rel_pvm']} day)";
		}
		else {
			$erapvm = "'{$params['mehtorow']['abs_pvm']}'";
		}

		if ($params['mehtorow']['kassa_abspvm'] != '0000-00-00' or $params['mehtorow']["kassa_relpvm"] > 0) {
			if ($params['mehtorow']['kassa_abspvm'] == '0000-00-00') {
				$kassa_erapvm = "adddate('{$params['laskurow']['tapvm']}', interval {$params['mehtorow']['kassa_relpvm']} day)";
			}
			else {
				$kassa_erapvm = "'{$params['mehtorow']['kassa_abspvm']}'";
			}
			$kassa_loppusumma = round($params['laskurow']['tapvm'] * $params['mehtorow']['kassa_alepros'] / 100, 2);
		}
		else {
			$kassa_erapvm = "''";
			$kassa_loppusumma = "";
		}

		// päivitetään lasku
		$query = "	UPDATE lasku set
					mapvm      = '',
					maksuehto  = '{$params['maksuehto']}',
					erpcm      = $erapvm,
					kapvm      = $kassa_erapvm,
					kasumma    = '$kassa_loppusumma',
					kassalipas = 0
					where yhtio = '$kukarow[yhtio]'
					and tunnus  = '{$params['tunnus']}'";
		$result = pupe_query($query);

		if (mysql_affected_rows() > 0) {
			echo "<font class='message'>".t("Muutettin laskun")." {$params['laskurow']['laskunro']} ".t("maksuehdoksi")." ".t_tunnus_avainsanat($params['mehtorow'], "teksti", "MAKSUEHTOKV")." ".t("ja merkattiin maksu avoimeksi").".</font><br>";
		}
		else {
			echo "<font class='error'>".t("Laskua")." {$params['laskurow']['laskunro']} ".t("ei pystytty muuttamaan")."!</font><br>";
		}
	}

	if ($params['mehtorow']["factoring"] != "") {
		$myysaatili = $yhtiorow['factoringsaamiset'];
	}
	elseif ($params['konsrow']["konserniyhtio"] != "") {
		$myysaatili = $yhtiorow['konsernimyyntisaamiset'];
	}
	else {
		$myysaatili = $yhtiorow['myyntisaamiset'];
	}

	return $myysaatili;
}

function hae_kassalipas($kassalipas_tunnus) {
	global $kukarow;
	$query = "	SELECT *
				FROM kassalipas
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$kassalipas_tunnus}'";

	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function tee_kirjanpito_muutokset($params) {
	global $kukarow, $yhtiorow;

	if ($params['toim'] == 'KATEINEN') {
		$uusitili  = $params['_kassalipas'];
		$vanhatili = '(' . $params['myysaatili'] . ')';
	}
	else {
		$uusitili  = $params['myysaatili'];
		$vanhatili = '('.implode(',', $params['_kassalipas']).')';
	}

	$query = "	SELECT tunnus
				FROM tiliointi
				WHERE yhtio	 = '$kukarow[yhtio]'
				AND ltunnus	 = '{$params['tunnus']}'
				AND tilino	 IN {$vanhatili}
				AND korjattu = ''";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 1) {
		$vanharow = mysql_fetch_assoc($result);

		// Tehdään kopio alkuperöisestöä, niin jää treissi miten oli alunperin kirjattu.
		kopioitiliointi($vanharow['tunnus'], $kukarow['kuka']);

		$query = "	UPDATE tiliointi
					SET tilino = '{$uusitili}',
					summa = '{$params['laskurow']['summa']}'
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus	= '{$vanharow['tunnus']}'";
		$result = pupe_query($query);

		if (mysql_affected_rows() > 0) {
			echo "<font class='message'>".t("Korjattiin kirjanpitoviennit")." (".mysql_affected_rows()." ".t("kpl").").</font><br>";
		}
		else {
			echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehdä! Korjaa kirjanpito käsin")."!</font><br>";
		}
	}
}

function yliviivaa_alet_ja_pyoristykset($tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE tiliointi
				SET korjattu = '$kukarow[kuka]',
				korjausaika  = now()
				where yhtio = '$kukarow[yhtio]'
				and ltunnus = '$tunnus'
				and tilino  IN ('$yhtiorow[myynninkassaale]', '$yhtiorow[pyoristys]')
				and korjattu = ''";
	$result = pupe_query($query);

	if (mysql_affected_rows() > 0) {
		echo "<font class='message'>".t("Poistettiin pyöristys- ja kassa-alekirjaukset")." (".mysql_affected_rows()." ".t("kpl").").</font><br><br>";
	}
}

function tarkista_pyoristys_erotukset($laskurow, $tunnus) {
	global $kukarow , $yhtiorow;

	$query = "	SELECT sum(summa) summa, sum(summa_valuutassa) summa_valuutassa
				FROM tiliointi
				WHERE yhtio  = '$kukarow[yhtio]'
				AND ltunnus  = '$tunnus'
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

function vapauta_kateistasmaytys($kassalipasrow, $paiva) {
	global $kukarow, $yhtiorow;

	// Katsotaan onko kassalippaan tämän päivän kassa jo täsmäytetty
	$tasmays_query = "	SELECT group_concat(distinct lasku.tunnus) ltunnukset,
						group_concat(distinct tiliointi.selite) selite
						FROM lasku
						JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio
						AND tiliointi.ltunnus = lasku.tunnus
						AND tiliointi.selite LIKE '%$kassalipasrow[nimi]%'
						AND tiliointi.korjattu = '')
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						AND lasku.tila = 'X'
						AND lasku.tapvm = '$paiva'";
	$tasmays_result = pupe_query($tasmays_query);
	$tasmaysrow = mysql_fetch_assoc($tasmays_result);

	// Jos on, niin poistetaan täsmäytys
	if ($tasmaysrow["ltunnukset"] != "") {
		$query = "	UPDATE tiliointi
					SET korjattu = '{$kukarow['kuka']}',
					korjausaika  = NOW()
					WHERE yhtio  = '{$kukarow['yhtio']}'
					AND ltunnus IN ({$tasmaysrow['ltunnukset']})
					AND korjattu = ''";
		$result = pupe_query($query);

		$query = "	UPDATE lasku
					SET tila = 'U',
					alatila = 'X',
					comments=CONCAT(comments, ' {$kukarow['kuka']} mitätöi tositteen')
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus IN ({$tasmaysrow['ltunnukset']})";
		$result = pupe_query($query);

		echo "<font class='error'>".t("Vapautettiin kassojen %s päivän %s tosite.", '', $tasmaysrow['selite'], $paiva)."<br/><br/>";
		echo t("Sinun on täsmäytettävä päivän käteismyynnit uudelleen käteismyynnit ohjelmasta") . "</font>";
	}
}

function hae_lasku2($laskuno, $toim) {
	global $kukarow;

	if ($toim == 'KATEINEN') {
		$query = "	SELECT lasku.ytunnus,
					lasku.liitostunnus,
					lasku.*,
					lasku.tunnus ltunnus,
					maksuehto.tunnus,
					maksuehto.teksti,
					asiakas.ytunnus asiakas_ytunnus,
					asiakas.nimi asiakas_nimi,
					asiakas.nimitark asiakas_nimitark,
					asiakas.osoite asiakas_osoite,
					asiakas.postino asiakas_postino,
					asiakas.postitp asiakas_postitp,
					asiakas.toim_nimi asiakas_toim_nimi,
					asiakas.toim_nimitark asiakas_toim_nimitark,
					asiakas.toim_osoite asiakas_toim_osoite,
					asiakas.toim_postino asiakas_toim_postino,
					asiakas.toim_postitp asiakas_toim_postitp
					FROM lasku
					JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio AND lasku.maksuehto = maksuehto.tunnus AND maksuehto.kateinen = ''
					JOIN asiakas ON asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND	lasku.laskunro = '{$laskuno}'
					AND lasku.tila = 'U'
					AND lasku.alatila = 'X'";
	}
	else {
		$query = "	SELECT lasku.ytunnus,
					lasku.liitostunnus,
					lasku.*,
					lasku.tunnus ltunnus,
					maksuehto.tunnus,
					maksuehto.teksti,
					asiakas.ytunnus asiakas_ytunnus,
					asiakas.nimi asiakas_nimi,
					asiakas.nimitark asiakas_nimitark,
					asiakas.osoite asiakas_osoite,
					asiakas.postino asiakas_postino,
					asiakas.postitp asiakas_postitp,
					asiakas.toim_nimi asiakas_toim_nimi,
					asiakas.toim_nimitark asiakas_toim_nimitark,
					asiakas.toim_osoite asiakas_toim_osoite,
					asiakas.toim_postino asiakas_toim_postino,
					asiakas.toim_postitp asiakas_toim_postitp
					FROM lasku
					JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio AND lasku.maksuehto = maksuehto.tunnus AND maksuehto.kateinen != ''
					JOIN asiakas ON asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.laskunro = '{$laskuno}'
					AND lasku.tila = 'U'
					AND lasku.alatila = 'X'";
	}

	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Laskunumerolla")." '$laskuno' ".t("ei löydy käteislaskua")."!</font><br><br>";
	}

	return mysql_fetch_assoc($result);
}

function echo_lasku_table($laskurow, $toim) {
	global $kukarow;

	echo "<form method='post' autocomplete='off'>";
	echo "<input name='tunnus' type='hidden' value='$laskurow[ltunnus]'>";

	if (!empty($laskurow['asiakas_toim_osoite'])) {
		$asiakas_string = "<tr><td>$laskurow[asiakas_ytunnus]<br> $laskurow[asiakas_nimi] $laskurow[asiakas_nimitark]<br> $laskurow[asiakas_osoite]<br> $laskurow[asiakas_postino] $laskurow[asiakas_postitp]</td><td>$laskurow[asiakas_ytunnus]<br> $laskurow[asiakas_toim_nimi] $laskurow[asiakas_toim_nimitark]<br> $laskurow[asiakas_toim_osoite]<br> $laskurow[asiakas_toim_postino] $laskurow[asiakas_toim_postitp]</td></tr>";
	}
	else {
		$asiakas_string = "<tr><td>$laskurow[asiakas_ytunnus]<br> $laskurow[asiakas_nimi] $laskurow[asiakas_nimitark]<br> $laskurow[asiakas_osoite]<br> $laskurow[asiakas_postino] $laskurow[asiakas_postitp]</td><td>$laskurow[asiakas_ytunnus]<br> $laskurow[asiakas_nimi] $laskurow[asiakas_nimitark]<br> $laskurow[asiakas_osoite]<br> $laskurow[asiakas_postino] $laskurow[asiakas_postitp]</td></tr>";
	}

	echo "<table>
			<tr><th>".t("Laskutusosoite")."</th><th>".t("Toimitusosoite")."</th></tr>
			{$asiakas_string}
			<tr><th>".t("Laskunumero")."</th><td>$laskurow[laskunro]</td></tr>
			<tr><th>".t("Laskun summa")."</th><td>$laskurow[summa]</td></tr>
			<tr><th>".t("Laskun summa (veroton)")."</th><td>$laskurow[arvo]</td></tr>
			<tr><th>".t("Maksuehto")."</th><td>".t_tunnus_avainsanat($laskurow, "teksti", "MAKSUEHTOKV")."</td></tr>";

	if ($toim == 'KATEINEN') {
		$now = date('Y-m-d');
		$now = explode('-' , $now);
		// haetaan kaikki käteisen maksuehdot
		$query = "	SELECT *
					FROM kassalipas
					WHERE yhtio = '{$kukarow['yhtio']}'";
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

		echo "<tr><th>".t("Tapahtumapäivä (pp-kk-vvvv)")."</th><td><input name='tapahtumapaiva_pp' type='text' size='3' value='".$now[2]."'/>-<input name='tapahtumapaiva_kk' type='text' size='3' value='".$now[1]."'/>-<input name='tapahtumapaiva_vv' type='text' size='5' value='".$now[0]."'/></td></tr>";

		$query = "	SELECT *
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]'
					and kateinen != ''
					ORDER BY jarjestys, teksti";
	}
	else {
		echo "<tr><th>".t("Tapahtumapäivä")."</th><td>$laskurow[tapvm]</td></tr>";

		// haetaan kaikki maksuehdot (paitsi käteinen)
		$query = "	SELECT *
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]'
					and kateinen = ''
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

function hae_kassalippaan_tiedot($kassalipas, $mehtorow, $laskurow) {
	global $yhtiorow, $kukarow;

	if ($mehtorow['kateinen'] != '') {
		$query = "	SELECT *
					FROM kassalipas
					WHERE yhtio = '{$kukarow['yhtio']}'
					and tunnus  = '{$kassalipas}'";
		$kateisresult = pupe_query($query);
		$kateisrow = mysql_fetch_assoc($kateisresult);

		if ($mehtorow['kateinen'] == "n") {
			if ($kateisrow["pankkikortti"] != "") {
				$myysaatili = $kateisrow["pankkikortti"];
			}
			else {
				$myysaatili = $yhtiorow['pankkikortti'];
			}
		}

		if ($mehtorow['kateinen'] == "o") {
			if ($kateisrow["luottokortti"] != "") {
				$myysaatili = $kateisrow["luottokortti"];
			}
			else {
				$myysaatili = $yhtiorow['luottokortti'];
			}
		}

		if($mehtorow['kateinen'] == 'p') {
			if($kateisrow['kassa'] != '') {
				$myysaatili = $kateisrow['kassa'];
			}
			else {
				$myysaatili = $yhtiorow['kassa'];
			}
		}

		if ($myysaatili == "") {
			if ($kateisrow["kassa"] != "") {
				$myysaatili = $kateisrow["kassa"];
			}
			else {
				$myysaatili = $yhtiorow['kassa'];
			}
		}
	}
	else {
		if($laskurow['kassalipas'] != '') {
			//haetaan kassalippaan tilit kassalippaan takaa
			$kassalipas_query = "	SELECT kassa,
									pankkikortti,
									luottokortti
									FROM kassalipas
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$laskurow['kassalipas']}'";
			$kassalipas_result = pupe_query($kassalipas_query);
			
			$kassalippaat = mysql_fetch_assoc($kassalipas_result);

			if(!empty($kassalippaat)) {
				$myysaatili = $kassalippaat;
			}
			else {
				$myysaatili = array(
					'kassa' => $yhtiorow['kassa'],
					'pankkikortti' => $yhtiorow['pankkikortti'],
					'luottokortti' => $yhtiorow['luottokortti']
				);
			}
		}
		else {
			$myysaatili = array(
				'kassa' => $yhtiorow['kassa'],
				'pankkikortti' => $yhtiorow['pankkikortti'],
				'luottokortti' => $yhtiorow['luottokortti']
			);
		}
	}

	return $myysaatili;
}

function tarkista_saako_laskua_muuttaa($tapahtumapaiva) {
	global $kukarow;

	$query = "	SELECT tilikausi_alku
				FROM yhtio
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	$tilikausi_alku = mysql_fetch_assoc($result);

	if(strtotime($tilikausi_alku['tilikausi_alku']) < strtotime($tapahtumapaiva)) {
		return false;
	}
	else {
		return $tilikausi_alku;
	}
	
}

require ("inc/footer.inc");
