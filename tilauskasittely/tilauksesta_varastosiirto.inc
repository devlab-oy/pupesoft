<?php

if (!function_exists("tilauksesta_varastosiirto")) {

	function tilauksesta_varastosiirto($myyntitilaus_tunnus) {
		global $yhtiorow, $kukarow, $toim, $pupe_root_polku;

		$myyntitilaus = hae_lasku($myyntitilaus_tunnus);
		$myyntitilaus['tilausrivit'] = hae_tilausrivit($myyntitilaus_tunnus);
		$myyntitilaus['tilausrivit'] = kasittele_tilausrivit($myyntitilaus['tilausrivit']);

		$tilausrivi_tunnukset = array();
		foreach ($myyntitilaus['tilausrivit'] as $lahdevarasto_tunnus => $kohdevaraston_tilausrivit) {
			foreach ($kohdevaraston_tilausrivit as $kohdevaraston_tunnus => $tilausrivit) {

				aseta_kukarow_kesken(0);
				$siirtolista = luo_siirtolista($lahdevarasto_tunnus, $kohdevaraston_tunnus, $tilausrivit['toimitustapa_tunnus']);

				if (empty($siirtolista)) {
					return false;
				}

				aseta_kukarow_kesken($siirtolista['tunnus']);
				foreach ($tilausrivit['tilausrivit'] as $tilausrivi) {
					$lisatyt_rivit1 = luo_siirtolistarivi($siirtolista, $tilausrivi, $lahdevarasto_tunnus);
					$tilausrivi_tunnukset[] = $tilausrivi['tunnus'];

					linkkaa_myyntitilausrivi_siirtolistariviin($lisatyt_rivit1[0], $tilausrivi['tunnus']);
				}
			}
		}

		aseta_tilausrivien_var($tilausrivi_tunnukset, 'J');
		aseta_kukarow_kesken($myyntitilaus_tunnus);

		return true;
	}

}

if (!function_exists('luo_siirtolistarivi')) {

	function luo_siirtolistarivi($laskurow, $tilausrivi, $lahdevarasto_tunnus) {
		global $kukarow, $yhtiorow;

		$trow = hae_tuote($tilausrivi['tuoteno']);

		$tuoteno_echo = $trow['tuoteno'];
		$yksikko_echo = $trow['yksikko'];
		$toimaika = $laskurow["toimaika"];
		$kerayspvm = $laskurow["kerayspvm"];
		$tuoteno = $trow["tuoteno"];
		$kpl = $tilausrivi['tilkpl'];
		$jtkielto = $laskurow['jtkielto'];
		$varasto = $lahdevarasto_tunnus;
		$hinta = "";
		$netto = "";
		$var = "";
		$korvaavakielto = 1;
		$perhekielto = $lapsituotteet == "" ? 1 : 0;
		$orvoteikiinnosta = "EITOD";

		//Laitetaan tuote tuotteen oletuspaikalle jos sellainen on. Muussa tapauksessa "varastoon" eli hyllyalue ja hyllynro vain merkittynä
		$tuotteen_oletuspaikka = tuotteen_oletuspaikka($tilausrivi['tuoteno'], $tilausrivi['kohdevarasto_tunnus']);

		if (!empty($tuotteen_oletuspaikka)) {
			$kohde_alue = $tuotteen_oletuspaikka['hyllyalue'];
			$kohde_nro = $tuotteen_oletuspaikka['hyllynro'];
			$kohde_vali = $tuotteen_oletuspaikka['hyllyvali'];
			$kohde_taso = $tuotteen_oletuspaikka['hyllytaso'];
		}
		else {
			$kohde_alue = $tilausrivi["kohde_hyllyalue"];
			$kohde_nro = $tilausrivi["kohde_hyllynro"];
			$kohde_vali = 0;
			$kohde_taso = 0;
		}

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			${'ale'.$alepostfix} = "";
		}

		require ('lisaarivi.inc');

		$tuoteno = '';
		$kpl = '';
		$hinta = '';
		$alv = 'X';
		$var = '';
		$toimaika = '';
		$kerayspvm = '';
		$kommentti = '';

		for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
			${'ale'.$alepostfix} = '';
		}

		return $lisatyt_rivit1;
	}

}

if (!function_exists('luo_siirtolista')) {

	function luo_siirtolista($lahdevarasto_tunnus, $kohdevaraston_tunnus, $toimitustapa_tunnus) {
		global $kukarow, $yhtiorow;

		$jatka = 'joo';
		$tilausnumero = 0;
		$clearing = $kohdevaraston_tunnus;
		$chn = 'GEN'; // tällä erotellaan "tulosta siirtolista"-kohdassa generoidut ja käsin tehdyt siirtolistat
		$toimpp = $kerpp = date("j");
		$toimkk = $kerkk = date("n");
		$toimvv = $kervv = date("Y");
		$comments = $kukarow["nimi"]." ".t("Automaattinen varastosiirron luonti myyntitilauksen perusteella");
		$viesti = $kukarow["nimi"]." ".t("Automaattinen varastosiirron luonti myyntitilauksen perusteella");
		$varasto = $lahdevarasto_tunnus;
		$toimitustapa = $toimitustapa_tunnus;
		$toim = "SIIRTOLISTA";

		require ("otsik_siirtolista.inc");

		return hae_lasku($id);
	}

}

if (!function_exists('aseta_kukarow_kesken')) {

	function aseta_kukarow_kesken($kesken) {
		global $kukarow, $yhtiorow;

		$query = "	UPDATE kuka
					SET kesken = {$kesken}
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND kuka = '{$kukarow['kuka']}'";
		pupe_query($query);

		$kukarow["kesken"] = $kesken;

		if (mysql_affected_rows() == 1) {
			return true;
		}

		return false;
	}

}

if (!function_exists('linkkaa_myyntitilausrivi_siirtolistariviin')) {

	function linkkaa_myyntitilausrivi_siirtolistariviin($siirtolistarivi_tunnus, $myyntitilausirivi_tunnus) {
		global $kukarow, $yhtiorow;

		if (empty($siirtolistarivi_tunnus) or empty($myyntitilausirivi_tunnus)) {
			return false;
		}

		$query = "	UPDATE tilausrivin_lisatiedot
					SET tilausrivilinkki = {$myyntitilausirivi_tunnus}
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tilausrivitunnus = {$siirtolistarivi_tunnus}";
		pupe_query($query);

		return true;
	}

}

if (!function_exists('kasittele_tilausrivit')) {

	function kasittele_tilausrivit($tilausrivit) {
		$tilausrivit_temp = array();

		foreach ($tilausrivit as $tilausrivi) {
			$tilausrivit_temp[$tilausrivi['lahdevarasto_tunnus']][$tilausrivi['kohdevarasto_tunnus']]['toimitustapa_tunnus'] = $tilausrivi['toimitustapa_tunnus'];
			$tilausrivit_temp[$tilausrivi['lahdevarasto_tunnus']][$tilausrivi['kohdevarasto_tunnus']]['tilausrivit'][] = $tilausrivi;
		}

		return $tilausrivit_temp;
	}

}

if (!function_exists('hae_tilausrivit')) {

	function hae_tilausrivit($otunnus) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT t.*,
					t.hyllyalue AS lahde_hyllyalue,
					t.hyllynro AS lahde_hyllynro,
					tl.toimittajan_tunnus,
					tl.kohde_hyllyalue AS kohde_hyllyalue,
					tl.kohde_hyllynro AS kohde_hyllynro,
					v_lahdevarasto.yhtio AS lahdevarasto_yhtio,
					v_lahdevarasto.tunnus AS lahdevarasto_tunnus,
					v_kohdevarasto.yhtio AS kohdevarasto_yhtio,
					v_kohdevarasto.tunnus AS kohdevarasto_tunnus,
					a.selitetark_2 AS toimitustapa_tunnus
					FROM tilausrivi AS t
					JOIN tilausrivin_lisatiedot AS tl
					ON (tl.yhtio = t.yhtio
						AND tl.tilausrivitunnus = t.tunnus )
					JOIN avainsana AS a
					ON ( a.yhtio = tl.yhtio
						AND a.tunnus = tl.toimittajan_tunnus )
					JOIN varastopaikat AS v_lahdevarasto
					ON ( v_lahdevarasto.tunnus = a.selite )
					JOIN varastopaikat AS v_kohdevarasto
					ON ( v_kohdevarasto.tunnus = a.selitetark )
					WHERE t.yhtio = '{$kukarow['yhtio']}'
					AND t.otunnus = {$otunnus}
					AND t.var = 'S'
					AND t.tyyppi != 'D'";
		$result = pupe_query($query);

		$tilausrivit = array();
		while ($tilausrivi = mysql_fetch_assoc($result)) {
			$tilausrivit[] = $tilausrivi;
		}

		return $tilausrivit;
	}

}