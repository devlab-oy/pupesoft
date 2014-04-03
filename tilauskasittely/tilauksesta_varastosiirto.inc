<?php

if (!function_exists("tilauksesta_varastosiirto")) {

	/**
	 * Funktio jakaa myyntitilauksen tilausrivit varastosiirroiksi lähde-kohdevarasto kombinaatioiden avulla
	 *
	 * @global array $yhtiorow
	 * @global array $kukarow
	 * @global string $toim
	 * @global string $pupe_root_polku
	 * @param int $myyntitilaus_tunnus
	 * @param boolean $kirjanpidollinen_varastosiirto
	 * @return boolean
	 */
	function tilauksesta_varastosiirto($myyntitilaus_tunnus, $kirjanpidollinen_varastosiirto = false) {
		global $yhtiorow, $kukarow, $toim, $pupe_root_polku;

		if (empty($myyntitilaus_tunnus)) {
			return false;
		}

		$myyntitilaus = hae_lasku($myyntitilaus_tunnus);
		$myyntitilaus['tilausrivit'] = hae_tilausrivit($myyntitilaus_tunnus, $kirjanpidollinen_varastosiirto);

		if ($kirjanpidollinen_varastosiirto) {
			//Kirjanpidollisessa varastosiirrossa yhdellä myyntitilauksella voi olla vain 1 lähde-kohdevarasto kombinaatio.
			//Kombinaatio muodostuu Lähde: lasku.varasto kohde: lasku.yhtio_toimipaikka.varasto.first
			$toimipaikan_varastot = hae_yhtion_toimipaikan_varastot($myyntitilaus['yhtio_toimipaikka']);
			$lahde_ja_kohde_varasto_yhdistelmat = array();
			$lahde_ja_kohde_varasto_yhdistelma = array(
				'lahdevarasto_tunnus'	 => $myyntitilaus['varasto'],
				'kohdevarasto_tunnus'	 => $toimipaikan_varastot[0]['tunnus'],
			);

			array_push($lahde_ja_kohde_varasto_yhdistelmat, $lahde_ja_kohde_varasto_yhdistelma);
		}
		else {
			//Jokaiselle tilausriville voi määrittää erikseen lähde sekä kohde varaston
			//Mahdolliset kombinaatiot muodostuvat avainsana.laji = SIIRTOVARASTOT, lasku.varasto tai lasku.yhtio_toimipaikka.varastot perusteella
			$keys = array(
				'lahdevarasto_tunnus',
				'kohdevarasto_tunnus'
			);
			$lahde_ja_kohde_varasto_yhdistelmat = array_find_unique_values($myyntitilaus['tilausrivit'], $keys);
		}

		$tilausrivi_tunnukset = array();
		$kommentit = array();
		foreach ($lahde_ja_kohde_varasto_yhdistelmat as $lahde_ja_kohde_varasto_yhdistelma) {
			$myyntitilausrivit = array_find($myyntitilaus['tilausrivit'], $lahde_ja_kohde_varasto_yhdistelma);

			aseta_kukarow_kesken(0);
			//Kaikilla myyntitilausriveillä on sama toimitustapa, joka määritellään avainsanoissa SIIRTOVARASTOT. Tällöin voidaan viitata indeksillä.
			$siirtolista = luo_siirtolista($lahde_ja_kohde_varasto_yhdistelma['lahdevarasto_tunnus'], $lahde_ja_kohde_varasto_yhdistelma['kohdevarasto_tunnus'], $myyntitilausrivit[0]['toimitustapa_tunnus']);

			if (empty($siirtolista)) {
				return false;
			}

			aseta_kukarow_kesken($siirtolista['tunnus']);
			foreach ($myyntitilausrivit as $tilausrivi) {
				$lisatyt_rivit1 = luo_siirtolistarivi($siirtolista, $tilausrivi, $lahde_ja_kohde_varasto_yhdistelma['lahdevarasto_tunnus']);
				$tilausrivi_tunnukset[] = $tilausrivi['tunnus'];

				linkkaa_myyntitilausrivi_siirtolistariviin($lisatyt_rivit1[0], $tilausrivi['tunnus']);

				$kommentit[] = t('Tilauksen')." {$myyntitilaus['tunnus']} ".t('tilausrivi')." {$tilausrivi['tuoteno']} - {$tilausrivi['tilkp']} {$tilausrivi['yksikko']} ".t('lisättiin siirtolistalle')." {$siirtolista['tunnus']}";
			}
		}

		if (!$kirjanpidollinen_varastosiirto) {
			aseta_tilausrivien_var($tilausrivi_tunnukset, 'J');
		}
		aseta_kukarow_kesken($myyntitilaus_tunnus);

		if ($kirjanpidollinen_varastosiirto) {
			//Kirjallisessa varastosiirrossa $lahde_ja_kohde_varasto_yhdistelmat loopataan vain kerran läpi.
			//Tästä syystä voimme käyttää tässä kohtaa $siirtolista['tunnus']
			$siirtolistarivit = hae_tilausrivit($siirtolista['tunnus'], $kirjanpidollinen_varastosiirto);

			foreach ($siirtolistarivit as &$siirtolistarivi) {
				$lahde_varasto_tuotepaikka = hae_tuotepaikka($siirtolistarivi['tuoteno'], $siirtolistarivi['hyllyalue'], $siirtolistarivi['hyllynro'], $siirtolistarivi['hyllytaso'], $siirtolistarivi['hyllyvali']);
				$kohde_varasto_tuotepaikat = hae_tuotteen_tuotepaikat($siirtolistarivi['tuoteno']);
				if (empty($kohde_varasto_tuotepaikat)) {
					//Jos kohde varastossa ei ole tälle tuotteelle hyllypaikkaa niin luodaan ns. default-paikka
					//$siirtolista['clearing'] = kohdevarasto tunnus
					$kohde_varasto_tuotepaikka = luo_tuotepaikka($siirtolistarivi['tuoteno'], false, false, false, false, $siirtolista['clearing']);
				}
				else {
					$kohde_varasto_tuotepaikka = $kohde_varasto_tuotepaikat[0];
				}

				$siirtolistarivi['kohde_hyllyalue'] = $kohde_varasto_tuotepaikka['hyllyalue'];
				$siirtolistarivi['kohde_hyllynro'] = $kohde_varasto_tuotepaikka['hyllynro'];
				$siirtolistarivi['kohde_hyllyvali'] = $kohde_varasto_tuotepaikka['hyllyvali'];
				$siirtolistarivi['kohde_hyllytaso'] = $kohde_varasto_tuotepaikka['hyllytaso'];

				$params = array(
					'kappaleet'						 => $siirtolistarivi['tilkpl'],
					'lisavaruste'					 => '',
					'tuoteno'						 => $siirtolistarivi['tuoteno'],
					'tuotepaikat_tunnus_otetaan'	 => $lahde_varasto_tuotepaikka['tunnus'],
					'tuotepaikat_tunnus_siirretaan'	 => $kohde_varasto_tuotepaikka['tunnus'],
					'mistarow'						 => $lahde_varasto_tuotepaikka,
					'minnerow'						 => $kohde_varasto_tuotepaikka,
					'sarjano_array'					 => array(),
					'selite'						 => '',
					'tun'							 => 0,
				);
				hyllysiirto($params);
			}

			aseta_varastosiirto_vastaanotetuksi($siirtolistarivit);
		}

		if (!$kirjanpidollinen_varastosiirto) {
			foreach ($kommentit as $kommentti) {
				echo "<font class='message'>$kommentti</font>";
				echo "<br/>";
			}
			echo "<br/>";
		}

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

if (!function_exists('hae_tilausrivit')) {

	function hae_tilausrivit($otunnus, $kirjanpidollinen_varastosiirto = false) {
		global $kukarow, $yhtiorow;

		$tilausrivi_where = '';
		$varasto_avainsana_join = '';
		$select = '';
		if (!$kirjanpidollinen_varastosiirto) {
			$tilausrivi_where = "AND t.var = 'S'";
			$varasto_avainsana_join = '
					JOIN avainsana AS a
					ON ( a.yhtio = tl.yhtio
						AND a.tunnus = tl.toimittajan_tunnus )
					JOIN varastopaikat AS v_lahdevarasto
					ON ( v_lahdevarasto.tunnus = a.selite )
					JOIN varastopaikat AS v_kohdevarasto
					ON ( v_kohdevarasto.tunnus = a.selitetark )';

			$select = '	v_lahdevarasto.tunnus AS lahdevarasto_tunnus,
						v_kohdevarasto.tunnus AS kohdevarasto_tunnus,
						a.selitetark_2 AS toimitustapa_tunnus,';
		}

		$query = "	SELECT t.*,
					{$select}
					t.hyllyalue AS lahde_hyllyalue,
					t.hyllynro AS lahde_hyllynro,
					tl.toimittajan_tunnus,
					tl.kohde_hyllyalue AS kohde_hyllyalue,
					tl.kohde_hyllynro AS kohde_hyllynro,
					tl.kohde_hyllytaso AS kohde_hyllytaso,
					tl.kohde_hyllyvali AS kohde_hyllyvali
					FROM tilausrivi AS t
					JOIN tuote
					ON ( tuote.yhtio = t.yhtio
						AND tuote.tuoteno = t.tuoteno
						AND tuote.ei_saldoa = '')
					JOIN tilausrivin_lisatiedot AS tl
					ON (tl.yhtio = t.yhtio
						AND tl.tilausrivitunnus = t.tunnus )
					{$varasto_avainsana_join}
					WHERE t.yhtio = '{$kukarow['yhtio']}'
					AND t.otunnus = {$otunnus}
					{$tilausrivi_where}
					AND t.tyyppi != 'D'";
		$result = pupe_query($query);

		$tilausrivit = array();
		while ($tilausrivi = mysql_fetch_assoc($result)) {
			$tilausrivit[] = $tilausrivi;
		}

		return $tilausrivit;
	}

}

if (!function_exists('aseta_varastosiirto_vastaanotetuksi')) {

	function aseta_varastosiirto_vastaanotetuksi($varastosiirtorivit) {
		global $kukarow, $yhtiorow;

		if (empty($varastosiirtorivit)) {
			return false;
		}

		$t1 = $t2 = $t3 = $t4 = array();
		$tee = 'paikat';
		$vainlistaus = '';
		$echotaanko = false;
		$id = $varastosiirtorivit[0]['otunnus'];
		foreach ($varastosiirtorivit as $varastosiirtorivi) {
			$tunnus[] = $varastosiirtorivi['tunnus'];
			$t1[$varastosiirtorivi['tunnus']] = $varastosiirtorivi['kohde_hyllyalue'];
			$t2[$varastosiirtorivi['tunnus']] = $varastosiirtorivi['kohde_hyllynro'];
			$t3[$varastosiirtorivi['tunnus']] = $varastosiirtorivi['kohde_hyllyvali'];
			$t4[$varastosiirtorivi['tunnus']] = $varastosiirtorivi['kohde_hyllytaso'];
		}

		require('vastaanota.php');

		return true;
	}

}