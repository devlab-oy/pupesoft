<?php

require ("../inc/parametrit.inc");

if (!isset($toim)) {
	$toim = "";
}
if (!isset($maksuehto)) {
	$maksuehto = 0;
}
if (!isset($laskuno)) {
	$laskuno = 0;
}
if (!isset($tunnus)) {
	$tunnus = 0;
}


if ($toim == 'KATEINEN') {
	echo "<font class='head'>", t("Lasku halutaankin maksaa k�teisell�"), "</font><hr />";
}
else {
	echo "<font class='head'>", t("Lasku ei ollutkaan k�teist�"), "</font><hr />";
}

if ((int)$maksuehto != 0 and (int)$tunnus != 0) {
	$laskupvmerror = FALSE;
	$laskumaksettuerror = FALSE;

	if ($toim == 'KATEINEN') {
		$tapahtumapaiva = date('Y-m-d', mktime(0, 0, 0, $tapahtumapaiva_kk, $tapahtumapaiva_pp, $tapahtumapaiva_vv));
	}
	else {
		$tapahtumapaiva = date('Y-m-d');
	}

	// Haetaan laskun tiedot
	$laskurow = hae_lasku($tunnus);

	if (strtotime($tapahtumapaiva) < strtotime($laskurow['tapvm'])) {
		$laskupvmerror = TRUE;
	}

	if ($toim == 'KATEINEN' and $laskurow['mapvm'] != '0000-00-00') {
		$laskumaksettuerror = TRUE;
	}

	$saako_muuttaa = tarkista_saako_laskua_muuttaa($tapahtumapaiva);

	if ($saako_muuttaa and !$laskupvmerror and !$laskumaksettuerror) {
		$maksuehtorow = hae_maksuehto($maksuehto);
		$konsrow = hae_asiakas($laskurow);
		$kassalipasrow = hae_kassalipas($kassalipas);

		$params = array(
			'konsrow'		 => $konsrow,
			'mehtorow'		 => $maksuehtorow,
			'laskurow'		 => $laskurow,
			'maksuehto'		 => $maksuehto,
			'tunnus'		 => $tunnus,
			'toim'			 => $toim,
			'tapahtumapaiva' => $tapahtumapaiva,
			'kassalipas'	 => $kassalipas,
			'kateinen'		 => $kateinen,
		);

		$myyntisaamis_tili = hae_myyntisaamis_tili($params);
		korjaa_erapaivat_ja_alet_ja_paivita_lasku($params);

		//Haetaan requestista tulevan kassalippaan tiedot. L -> K kyseess� on siis se kassalipas, josta k�teinen halutaan ottaa
		if ($toim == 'KATEINEN') {
			list($valitun_maksuehdon_tili, $valitun_maksuehdon_kustannuspaikka) = hae_kassalippaan_tiedot2($kassalipas, $maksuehtorow);
		}

		//T�ss� ollu ennen t�mm�i iffi. En niink� tajuu miten tonne iffiin on voitu menn�.
//		if ($toim == 'KATEINEN' and $kateinen != '') {
//			// Lasku oli ennest��n k�teinen ja nyt p�ivitet��n sille joku toinen k�teismaksuehto
//			list($myysaatili, $_tmp) = hae_kassalippaan_tiedot($laskurow['kassalipas'], hae_maksuehto($laskurow['maksuehto']), $laskurow);
//			$_tmp = korjaa_erapaivat_ja_alet_ja_paivita_lasku($params);
//		}
//		else {
//			$myysaatili = korjaa_erapaivat_ja_alet_ja_paivita_lasku($params);
//		}

		$params['myyntisaamis_tili'] = $myyntisaamis_tili;
		$params['valitun_maksuehdon_tili'] = $valitun_maksuehdon_tili;
		$params['valitun_maksuehdon_kustannuspaikka'] = $valitun_maksuehdon_kustannuspaikka;
		$params['kassalippaan_kateistilit'] = hae_kassalippaan_kateistilit($laskurow);
		$params['kassalippaiden_kateistilit'] = hae_kateistilit();

		tee_kirjanpito_muutokset2($params);
		yliviivaa_alet_ja_pyoristykset($tunnus);
		tarkista_pyoristys_erotukset($laskurow, $tunnus);

		if ($toim == 'KATEINEN') {
			vapauta_kateistasmaytys($kassalipasrow, $tapahtumapaiva);
		}

		if (empty($maksuehtorow) and empty($laskurow)) {
			$laskuno = 0;
			$tunnus = 0;
			$maksuehto = 0;
		}

		$laskuno = 0;
		echo "<br>";
	}
	elseif ($laskumaksettuerror) {
		echo "<font class='error'>".t("VIRHE: Lasku on jo maksettu")."!</font>";
	}
	elseif ($laskupvmerror) {
		echo "<font class='error'>".t("VIRHE: Sy�tetty p�iv�m��r� on pienempi kuin laskun p�iv�m��r� %s", "", $laskurow['tapvm'])."!</font>";
	}
	else {
		echo "<font class='error'>".t("VIRHE: Tilikausi on p��ttynyt %s. Et voi merkit� laskua maksetuksi p�iv�lle %s", "", $yhtiorow['tilikausi_alku'], $tapahtumapaiva)."!</font>";
	}
}

if ((int)$laskuno != 0) {
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
		$query = "	UPDATE lasku set
					erpcm = '{$params['tapahtumapaiva']}',
					mapvm = '{$params['tapahtumapaiva']}',
					maksuehto = '{$params['maksuehto']}',
					kassalipas = '{$params['kassalipas']}'
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = {$params['tunnus']}";
		pupe_query($query);

		echo "<font class='message'>".t("Muutettin laskun")." {$params['laskurow']['laskunro']} ".t("maksuehdoksi")." ".t_tunnus_avainsanat($params['mehtorow'], "teksti", "MAKSUEHTOKV")."!</font><br>";
	}
	else {
		// korjaillaan er�p�iv�t ja kassa-alet
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

		// p�ivitet��n lasku
		$query = "	UPDATE lasku set
					mapvm      = '',
					maksuehto  = '{$params['maksuehto']}',
					erpcm      = $erapvm,
					kapvm      = $kassa_erapvm,
					kasumma    = '$kassa_loppusumma',
					kassalipas = 0
					where yhtio = '$kukarow[yhtio]'
					and tunnus  = '{$params['tunnus']}'";
		pupe_query($query);

		if (mysql_affected_rows() > 0) {
			echo "<font class='message'>".t("Muutettin laskun")." {$params['laskurow']['laskunro']} ".t("maksuehdoksi")." ".t_tunnus_avainsanat($params['mehtorow'], "teksti", "MAKSUEHTOKV")." ".t("ja merkattiin maksu avoimeksi")."!</font><br>";
		}
		else {
			echo "<font class='error'>".t("Laskua")." {$params['laskurow']['laskunro']} ".t("ei pystytty muuttamaan")."!</font><br>";
		}
	}
}

function hae_myyntisaamis_tili($params) {
	global $yhtiorow;

	if ($params['mehtorow']["factoring"] != "") {
		return $yhtiorow['factoringsaamiset'];
	}
	elseif ($params['konsrow']["konserniyhtio"] != "") {
		return $yhtiorow['konsernimyyntisaamiset'];
	}
	else {
		return $yhtiorow['myyntisaamiset'];
	}
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

function tee_kirjanpito_muutokset2($params) {
	global $kukarow, $yhtiorow;

	$kateistilit_temp = array();
	foreach ($params['kassalippaiden_kateistilit'] as $kassalippaan_kateistilit) {
		foreach ($kassalippaan_kateistilit as $kassalippaan_kateistili) {
			if (!in_array($kassalippaan_kateistili, $kateistilit_temp)) {
				$kateistilit_temp[] = $kassalippaan_kateistili;
			}
		}
	}

	$kateistiliointi = hae_tiliointi($params['tunnus'], $kateistilit_temp);
	$myyntisaamistiliointi = hae_tiliointi($params['tunnus'], array($params['myyntisaamis_tili']));

	$loytyiko_kateistiliointi = true;
	if (empty($kateistiliointi)) {
		//Jos laskulle ei ole tehty k�teistili�inti� niin haetaan myyntisaamis tili�inti, koska se on k�teistili�innin vastatili�inti
		$kateistiliointi = hae_tiliointi($params['tunnus'], array($params['myyntisaamis_tili']));
		$kateistiliointi['summa'] = $kateistiliointi['summa'] * -1;
		$loytyiko_kateistiliointi = false;
	}

	//Tehd��n k�teistili�inti, joka on myyntisaamistili�innin vastatili�inti
	$tiliointi_tunnus = kopioitiliointi($kateistiliointi['tunnus'], "");

	$tiliointi = array(
		'tunnus' => $tiliointi_tunnus,
		'summa'	 => $kateistiliointi['summa'] * -1,
		'tilino' => $params['valitun_maksuehdon_tili'],
	);

	$muutettuja_tiliointeja_kpl = paivita_tilioinnin_tiedot($tiliointi, $params['kustp']);

	//Tehd��n myyntisaamistili�inti, joka on k�teistili�innin vastatili�inti
	$tiliointi_tunnus = kopioitiliointi($myyntisaamistiliointi['tunnus'], "");
	$tiliointi = array(
		'tunnus' => $tiliointi_tunnus,
		'summa'	 => $myyntisaamistiliointi['summa'] * -1,
		'tilino' => $params['myyntisaamis_tili'],
	);

	$muutettuja_tiliointeja_kpl = paivita_tilioinnin_tiedot($tiliointi, $params['kustp']);

	if ($params['toim'] == 'KATEINEN') {
		$lasku_saldo_maksettu = $params['laskurow']['summa'];
	}
	else {
		$lasku_saldo_maksettu = $params['laskurow']['summa'] - ($myyntisaamistiliointi['summa'] * -1);
	}

	$query = "	UPDATE lasku SET
				saldo_maksettu = {$lasku_saldo_maksettu}
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$params['laskurow']['tunnus']}'";
	pupe_query($query);
}

function hae_tiliointi($lasku_tunnus, $tilinumerot) {
	global $kukarow, $yhtiorow;

	// Haetaan kassatili�inti
	$query = "	SELECT tunnus,
				summa,
				tilino
				FROM tiliointi
				WHERE yhtio = '$kukarow[yhtio]'
				AND ltunnus = '{$lasku_tunnus}'
				AND tilino IN (".implode(',', $tilinumerot).")
				AND korjattu = ''
				ORDER BY tapvm DESC, tunnus DESC
				LIMIT 1";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function paivita_tilioinnin_tiedot($tiliointi, $params) {
	global $kukarow, $yhtiorow;

	if ($params['toim'] == 'KATEINEN') {
		$tapvmlisa = "tapvm = '{$params['tapahtumapaiva']}',";
	}
	else {
		$tapvmlisa = '';
	}

	$kustplisa = $params['kustp'] != '' ? "kustp = '{$params['kustp']}'," : "";

	$tiliointi_lisa = "";
	if (!empty($tiliointi['tilino'])) {
		$tiliointi_lisa = "tilino = '{$tiliointi['tilino']}',";
	}

	$query = "	UPDATE tiliointi
				SET summa = {$tiliointi['summa']},
				summa_valuutassa = {$tiliointi['summa']},
				laatija = '{$kukarow['kuka']}',
				{$tiliointi_lisa}
				{$tapvmlisa}
				{$kustplisa}
				laadittu = NOW()
				WHERE yhtio	= '{$kukarow['yhtio']}'
				AND tunnus = '{$tiliointi['tunnus']}'";
	pupe_query($query);

	return mysql_affected_rows();
}

function tee_kirjanpito_muutokset($params) {
	global $kukarow, $yhtiorow;

	if ($params['toim'] == 'KATEINEN') {
		$tapvmlisa = ", tapvm = '{$params['tapahtumapaiva']}' ";
	}
	else {
		$tapvmlisa = '';
	}

	// Haetaan kassatili�inti
	$query = "	SELECT tunnus,
				summa,
				tilino
				FROM tiliointi
				WHERE yhtio = '$kukarow[yhtio]'
				AND ltunnus = '{$params['tunnus']}'
				AND tilino IN (".implode(',', $params['_kassalipas']).")
				AND korjattu = ''
				ORDER BY tapvm DESC, tunnus DESC
				LIMIT 1";
	$kassatili_result = pupe_query($query);

	// Haetaan myyntisaamistili�nti
	$query = "	SELECT tunnus,
				summa,
				tilino
				FROM tiliointi
				WHERE yhtio = '$kukarow[yhtio]'
				AND ltunnus = '{$params['tunnus']}'
				AND tilino IN ({$params['myysaatili']})
				AND korjattu = ''
				ORDER BY tapvm DESC, tunnus DESC
				LIMIT 1";
	$myyntisaamis_result = pupe_query($query);

	if (mysql_num_rows($myyntisaamis_result) == 1) {

		if (mysql_num_rows($kassatili_result) == 0) {
			$query = "	SELECT tunnus,
						summa,
						tilino
						FROM tiliointi
						WHERE yhtio = '$kukarow[yhtio]'
						AND ltunnus = '{$params['tunnus']}'
						AND tilino IN ({$params['myysaatili']})
						AND korjattu = ''
						ORDER BY tapvm DESC, tunnus DESC
						LIMIT 1";
			$kassatili_result = pupe_query($query);
			$kassatilirow = mysql_fetch_assoc($kassatili_result);
			$kassatilirow['summa'] = $kassatilirow['summa'] * -1;
			$loytyiko_kassa_tiliointi = false;
		}
		else {
			$kassatilirow = mysql_fetch_assoc($kassatili_result);
			$loytyiko_kassa_tiliointi = true;
		}
		$myyntisaamisrow = mysql_fetch_assoc($myyntisaamis_result);
		$kustplisa = $params['kustp'] != '' ? ", kustp = '{$params['kustp']}'" : "";

		// Tehd��n vastakirjaus alkuper�iselle tili�innille
		$tilid = kopioitiliointi($kassatilirow['tunnus'], "");

		if ($params['toim'] == 'KATEINEN' and $params['laskurow']['saldo_maksettu'] != 0) {
			$summalisa = ($params['laskurow']['summa'] - $params['laskurow']['saldo_maksettu'])." * -1";
		}
		else {
			if ($loytyiko_kassa_tiliointi) {
				$uusitili = $kassatilirow['tilino'];
				$summalisa = "summa * -1";
			}
			else {
				$uusitili = $params['_kassalipas'][0];
				$summalisa = "summa";
			}
		}

		$query = "	UPDATE tiliointi
					SET tilino = '{$uusitili}',
					summa = {$summalisa},
					laatija = '{$kukarow['kuka']}',
					laadittu = now()
					{$tapvmlisa}
					{$kustplisa}
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus = '{$tilid}'";
		pupe_query($query);

		// Kopsataan alkuper�inen ja p�ivitet��n siille uudet tiedot
		$tilid = kopioitiliointi($myyntisaamisrow['tunnus'], "");

		if ($params['toim'] == 'KATEINEN' and $params['laskurow']['saldo_maksettu'] != 0) {
			$summalisa = $params['laskurow']['summa'] - $params['laskurow']['saldo_maksettu'];
		}
		else {
			$summalisa = $myyntisaamisrow['summa'];
		}

		$query = "	UPDATE tiliointi
					SET	summa = {$summalisa} * -1,
					laatija = '{$kukarow['kuka']}',
					laadittu = now()
					{$tapvmlisa}
					{$kustplisa}
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus = '{$tilid}'";
		pupe_query($query);

		if (mysql_affected_rows() > 0) {
			echo "<font class='message'>".t("Korjattiin kirjanpitoviennit")." (".mysql_affected_rows()." ".t("kpl").").</font><br>";
		}
		else {
			echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehd�! Korjaa kirjanpito k�sin")."!</font><br>";
		}
		if ($params['laskurow']['summa'] > 0) {
			$summalisa = ($params['toim'] == 'KATEINEN' and $params['laskurow']['saldo_maksettu'] != 0) ? 0 : ($params['laskurow']['summa'] - $kassatilirow['summa']);
		}
		else {
			$summalisa = ($params['toim'] == 'KATEINEN' and $params['laskurow']['saldo_maksettu'] != 0) ? 0 : ($params['laskurow']['summa'] + $kassatilirow['summa']);
		}

		$query = "	UPDATE lasku SET
					saldo_maksettu = {$summalisa}
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$params['laskurow']['tunnus']}'";
		pupe_query($query);
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
		echo "<font class='message'>".t("Poistettiin py�ristys- ja kassa-alekirjaukset")." (".mysql_affected_rows()." ".t("kpl").").</font><br>";
	}
}

function tarkista_pyoristys_erotukset($laskurow, $tunnus) {
	global $kukarow, $yhtiorow;

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
					selite 				= '".t("Py�ristysero")."',
					lukko 				= '',
					laatija 			= '$kukarow[kuka]',
					laadittu 			= now()";
		pupe_query($query);
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
					maksuehto.kateinen,
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
					JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio AND lasku.maksuehto = maksuehto.tunnus)
					JOIN asiakas ON asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus
					WHERE lasku.yhtio  = '{$kukarow['yhtio']}'
					AND	lasku.laskunro = '{$laskuno}'
					AND lasku.tila     = 'U'
					AND lasku.alatila  = 'X'";
	}
	else {
		$query = "	SELECT lasku.ytunnus,
					lasku.liitostunnus,
					lasku.*,
					lasku.tunnus ltunnus,
					maksuehto.tunnus,
					maksuehto.teksti,
					maksuehto.kateinen,
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
					WHERE lasku.yhtio  = '{$kukarow['yhtio']}'
					AND lasku.laskunro = '{$laskuno}'
					AND lasku.tila     = 'U'
					AND lasku.alatila  = 'X'";
	}

	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Laskunumerolla")." '$laskuno' ".t("ei l�ydy sopivaa laskua")."!</font><br><br>";
		return FALSE;
	}

	$row = mysql_fetch_assoc($result);

	if ($toim == 'KATEINEN' and $row['kateinen'] != '') {
		echo "<font class='error'>".t("VIRHE: Lasku on jo k�teislasku")."!</font><br><br>";
		return FALSE;
	}
	elseif ($toim == 'KATEINEN' and $row['mapvm'] != '0000-00-00') {
		echo "<font class='error'>".t("VIRHE: Lasku on jo maksettu")."!</font><br><br>";
		return FALSE;
	}

	return $row;
}

function echo_lasku_table($laskurow, $toim) {
	global $kukarow;

	echo "<form method='post' autocomplete='off'>";
	echo "<input name='tunnus' type='hidden' value='$laskurow[ltunnus]'>";
	echo "<input name='kateinen' type='hidden' value='{$laskurow['kateinen']}'>";

	if (!empty($laskurow['asiakas_toim_osoite'])) {
		$asiakas_string = "<tr><td>$laskurow[asiakas_ytunnus]<br> $laskurow[asiakas_nimi] $laskurow[asiakas_nimitark]<br> $laskurow[asiakas_osoite]<br> $laskurow[asiakas_postino] $laskurow[asiakas_postitp]</td><td>$laskurow[asiakas_ytunnus]<br> $laskurow[asiakas_toim_nimi] $laskurow[asiakas_toim_nimitark]<br> $laskurow[asiakas_toim_osoite]<br> $laskurow[asiakas_toim_postino] $laskurow[asiakas_toim_postitp]</td></tr>";
	}
	else {
		$asiakas_string = "<tr><td>$laskurow[asiakas_ytunnus]<br> $laskurow[asiakas_nimi] $laskurow[asiakas_nimitark]<br> $laskurow[asiakas_osoite]<br> $laskurow[asiakas_postino] $laskurow[asiakas_postitp]</td><td>$laskurow[asiakas_ytunnus]<br> $laskurow[asiakas_nimi] $laskurow[asiakas_nimitark]<br> $laskurow[asiakas_osoite]<br> $laskurow[asiakas_postino] $laskurow[asiakas_postitp]</td></tr>";
	}

	$osasuoritus_string = "";

	if ($laskurow['saldo_maksettu'] != 0) {
		$osasuoritus_string = "<tr><th>".t("Osasuoritukset")."</th><td>{$laskurow['saldo_maksettu']}</td></tr>";
		$osasuoritus_string .= "<tr><th>".t("Laskua maksamatta")."</th><td>".($laskurow['summa'] - $laskurow['saldo_maksettu'])."</td></tr>";
	}

	echo "<table>";
	echo "<tr><th>", t("Laskutusosoite"), "</th><th>", t("Toimitusosoite"), "</th></tr>";
	echo $asiakas_string;
	echo "<tr><th>", t("Laskunumero"), "</th><td>{$laskurow['laskunro']}</td></tr>";
	echo "<tr><th>", t("Laskun summa"), "</th><td>{$laskurow['summa']}</td></tr>";
	echo "<tr><th>", t("Laskun summa (veroton)"), "</th><td>{$laskurow['arvo']}</td></tr>";
	echo $osasuoritus_string;
	echo "<tr><th>", t("Maksuehto"), "</th><td>", t_tunnus_avainsanat($laskurow, "teksti", "MAKSUEHTOKV"), "</td></tr>";

	if ($toim == 'KATEINEN') {
		$now = date('Y-m-d');
		$now = explode('-', $now);
		// haetaan kaikki k�teisen maksuehdot
		$query = "	SELECT *
					FROM kassalipas
					WHERE yhtio = '{$kukarow['yhtio']}'";
		$result = pupe_query($query);

		echo '<tr>';
		echo "<th>".t('Kassalipas')."</th>";
		echo '<td>';
		echo '<select name="kassalipas">';

		while ($row = mysql_fetch_assoc($result)) {

			$sel = $laskurow['kassalipas'] == $row['tunnus'] ? " selected" : "";

			echo "<option value='{$row['tunnus']}'{$sel}>".t($row['nimi'])."</option>";
		}

		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo "<tr><th>".t("Tapahtumap�iv� (pp-kk-vvvv)")."</th><td><input name='tapahtumapaiva_pp' type='text' size='3' value='".$now[2]."'/>-<input name='tapahtumapaiva_kk' type='text' size='3' value='".$now[1]."'/>-<input name='tapahtumapaiva_vv' type='text' size='5' value='".$now[0]."'/></td></tr>";

		$query = "	SELECT *
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]'
					and kateinen != ''
					and kaytossa = ''
					ORDER BY jarjestys, teksti";
	}
	else {
		echo "<tr><th>".t("Tapahtumap�iv�")."</th><td>$laskurow[tapvm]</td></tr>";

		// haetaan kaikki maksuehdot (paitsi k�teinen)
		$query = "	SELECT *
					FROM maksuehto
					WHERE yhtio = '$kukarow[yhtio]'
					and kateinen = ''
					and kaytossa = ''
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
	echo "<th>".t("Sy�t� laskunumero")."</th>";
	echo "<td><input type='text' name='laskuno'></td>";
	echo "<td class='back'><input name='subnappi' type='submit' value='".t("Etsi")."'></td>";
	echo "</tr></table>";
	echo "</form>";
}

function hae_kassalippaan_tiedot2($kassalipas, $maksuehtorow) {
	global $kukarow, $yhtiorow;

	$kassalipas = hae_kassalipas($kassalipas);

	if ($maksuehtorow['kateinen'] == "n") {
		if ($kassalipas["pankkikortti"] != "") {
			$valitun_maksuehdon_kustannuspaikka = $kassalipas['kustp'];
			$valitun_maksuehdon_tili = $kassalipas['pankkikortti'];
		}
		else {
			$valitun_maksuehdon_tili = $yhtiorow['pankkikortti'];
		}
	}

	if ($maksuehtorow['kateinen'] == "o") {
		if ($kassalipas["luottokortti"] != "") {
			$valitun_maksuehdon_kustannuspaikka = $kassalipas['kustp'];
			$valitun_maksuehdon_tili = $kassalipas['luottokortti'];
		}
		else {
			$valitun_maksuehdon_tili = $yhtiorow['luottokortti'];
		}
	}

	if ($maksuehtorow['kateinen'] == 'p') {
		if ($kassalipas['kassa'] != '') {
			$valitun_maksuehdon_kustannuspaikka = $kassalipas['kustp'];
			$valitun_maksuehdon_tili = $kassalipas['kassa'];
		}
		else {
			$valitun_maksuehdon_tili = $yhtiorow['kassa'];
		}
	}

	if ($valitun_maksuehdon_tili == "") {
		if ($kassalipas["kassa"] != "") {
			$valitun_maksuehdon_kustannuspaikka = $kassalipas['kustp'];
			$valitun_maksuehdon_tili = $kassalipas['kassa'];
		}
		else {
			$valitun_maksuehdon_tili = $yhtiorow['kassa'];
		}
	}

	return array($valitun_maksuehdon_tili, $valitun_maksuehdon_kustannuspaikka);
}

function hae_kassalippaan_kateistilit($laskurow) {
	global $kukarow, $yhtiorow;

	if ($laskurow['kassalipas'] != '') {
		//haetaan kassalippaan tilit kassalippaan takaa
		$kassalipas_query = "	SELECT kassa,
								pankkikortti,
								luottokortti
								FROM kassalipas
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$laskurow['kassalipas']}'";
		$kassalipas_result = pupe_query($kassalipas_query);

		$kassalippaat = mysql_fetch_assoc($kassalipas_result);

		if (!empty($kassalippaat)) {
			$kateis_tilit = $kassalippaat;
		}
		else {
			$kateis_tilit = array(
				'kassa'			 => $yhtiorow['kassa'],
				'pankkikortti'	 => $yhtiorow['pankkikortti'],
				'luottokortti'	 => $yhtiorow['luottokortti']
			);
		}
	}
	else {
		$kateis_tilit = array(
			'kassa'			 => $yhtiorow['kassa'],
			'pankkikortti'	 => $yhtiorow['pankkikortti'],
			'luottokortti'	 => $yhtiorow['luottokortti']
		);
	}

	return $kateis_tilit;
}

function hae_kassalippaan_tiedot($kassalipas, $maksuehtorow, $laskurow) {
	global $yhtiorow, $kukarow;

	$kustp = "";

	if ($maksuehtorow['kateinen'] != '') {

		$query = "	SELECT *
					FROM kassalipas
					WHERE yhtio = '{$kukarow['yhtio']}'
					and tunnus  = '{$kassalipas}'";
		$kateisresult = pupe_query($query);
		$kateisrow = mysql_fetch_assoc($kateisresult);

		if ($maksuehtorow['kateinen'] == "n") {
			if ($kateisrow["pankkikortti"] != "") {
				$kustp = $kateisrow['kustp'];
				$kateis_tilit = $kateisrow['pankkikortti'];
			}
			$kateis_tilit = array($yhtiorow['pankkikortti']);
		}

		if ($maksuehtorow['kateinen'] == "o") {
			if ($kateisrow["luottokortti"] != "") {
				$kustp = $kateisrow['kustp'];
			}
			$kateis_tilit = array($kateisrow['luottokortti']);
		}

		if ($maksuehtorow['kateinen'] == 'p') {
			if ($kateisrow['kassa'] != '') {
				$kustp = $kateisrow['kustp'];
			}
			$kateis_tilit = array($kateisrow['kassa']);
		}

		if ($kateis_tilit == "") {
			if ($kateisrow["kassa"] != "") {
				$kustp = $kateisrow['kustp'];
			}
			$kateis_tilit = array($kateisrow['kassa']);
		}
	}
	else {
		if ($laskurow['kassalipas'] != '') {
			//haetaan kassalippaan tilit kassalippaan takaa
			$kassalipas_query = "	SELECT kassa,
									pankkikortti,
									luottokortti
									FROM kassalipas
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$laskurow['kassalipas']}'";
			$kassalipas_result = pupe_query($kassalipas_query);

			$kassalippaat = mysql_fetch_assoc($kassalipas_result);

			if (!empty($kassalippaat)) {
				$kateis_tilit = $kassalippaat;
			}
			else {
				$kateis_tilit = array(
					'kassa'			 => $yhtiorow['kassa'],
					'pankkikortti'	 => $yhtiorow['pankkikortti'],
					'luottokortti'	 => $yhtiorow['luottokortti']
				);
			}
		}
		else {
			$kateis_tilit = array(
				'kassa'			 => $yhtiorow['kassa'],
				'pankkikortti'	 => $yhtiorow['pankkikortti'],
				'luottokortti'	 => $yhtiorow['luottokortti']
			);
		}
	}

	return array($kateis_tilit, $kustp);
}

function hae_kateistilit() {
	global $kukarow, $yhtiorow;

	$query = "	SELECT kassa,
				pankkikortti,
				luottokortti
				FROM kassalipas
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	$kateistilit = array();
	while ($kateistili = mysql_fetch_assoc($result)) {
		$kateistilit[] = $kateistili;
	}

	return $kateistilit;
}

function tarkista_saako_laskua_muuttaa($tapahtumapaiva) {
	global $kukarow, $yhtiorow;

	if (strtotime($yhtiorow['tilikausi_alku']) < strtotime($tapahtumapaiva) and strtotime($yhtiorow['tilikausi_loppu']) > strtotime($tapahtumapaiva)) {
		return true;
	}
	else {
		return false;
	}
}

require ("inc/footer.inc");
