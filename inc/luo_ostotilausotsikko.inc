<?php

if (!function_exists('luo_ostotilausotsikko')) {

	function luo_ostotilausotsikko(array $params) {

		global $kukarow, $yhtiorow;

		/* 	Pakolliset parametrit
		 * 	$params['liitostunnus'];
		 *
		 * 	Muut parametrit
		 * 	$params['ostotilauksen_kasittely'];
		 * 	$params["nimi"];
		 * 	$params["nimitark"];
		 *  $params["osoite"];
		 * 	$params["postino"];
		 * 	$params["postitp"];
		 * 	$params["maa"];
		 * 	$params['ohjausmerkki']
		 * 	$params['jakelu']
		 * 	$params['myyntitil_kerayspvm']
		 * 	$params['myytil_myyja']
		 * 	$params['myytil_sisviesti1']
		 * 	$params['myytil_toimaika']
		 * 	$params['myytil_toimitustapa']
		 * 	$params['myytil_liitostunnus']
		 */

		$params['ostotilauksen_kasittely'] = (!empty($params['ostotilauksen_kasittely'])) ? $params['ostotilauksen_kasittely'] : '';
		$params["nimi"] = (!empty($params["nimi"])) ? $params["nimi"] : '';
		$params["nimi"] = (!empty($params["nimi"])) ? $params["nimi"] : '';
		$params["nimitark"] = (!empty($params["nimitark"])) ? $params["nimitark"] : '';
		$params["osoite"] = (!empty($params["osoite"])) ? $params["osoite"] : '';
		$params["postino"] = (!empty($params["postino"])) ? $params["postino"] : '';
		$params["postitp"] = (!empty($params["postitp"])) ? $params["postitp"] : '';
		$params["maa"] = (!empty($params["maa"])) ? $params["maa"] : '';
		$params['ohjausmerkki'] = (!empty($params['ohjausmerkki'])) ? $params['ohjausmerkki'] : '';
		$params['jakelu'] = (!empty($params['jakelu'])) ? $params['jakelu'] : '';
		$params['myyntitil_kerayspvm'] = (!empty($params['myyntitil_kerayspvm'])) ? $params['myyntitil_kerayspvm'] : '';
		$params['myytil_myyja'] = (!empty($params['myytil_myyja'])) ? $params['myytil_myyja'] : '';
		$params['myytil_sisviesti1'] = (!empty($params['myytil_sisviesti1'])) ? $params['myytil_sisviesti1'] : '';
		$params['myytil_toimaika'] = (!empty($params['myytil_toimaika'])) ? $params['myytil_toimaika'] : '';
		$params['myytil_toimitustapa'] = (!empty($params['myytil_toimitustapa'])) ? $params['myytil_toimitustapa'] : '';
		$params['myytil_liitostunnus'] = (!empty($params['myytil_liitostunnus'])) ? $params['myytil_liitostunnus'] : '';

		if (empty($params['toim_nimi']) or empty($params['toim_nimitark']) or empty($params['toim_osoite']) or empty($params['toim_postino']) or empty($params['toim_postitp']) or empty($params['toim_maa'])) {

			$toim_nimi = $yhtiorow["nimi"];
			$toim_nimitark = $yhtiorow["nimitark"];
			$toim_osoite = $yhtiorow["osoite"];
			$toim_postino = $yhtiorow["postino"];
			$toim_postitp = $yhtiorow["postitp"];
			$toim_maa = $yhtiorow["maa"];
		}
		else {
			$toim_nimi = $params["nimi"];
			$toim_nimitark = $params["nimitark"];
			$toim_osoite = $params["osoite"];
			$toim_postino = $params["postino"];
			$toim_postitp = $params["postitp"];
			$toim_maa = $params["maa"];
		}

		$tiltoi = hae_toimittaja($params['liitostunnus']);
		$valkoodi = $tiltoi["oletus_valkoodi"];

		if ($valkoodi == '') {
			$valkoodi = $yhtiorow["valkoodi"];
		}

		$vquery = "	SELECT nimi, kurssi, tunnus
					FROM valuu
					WHERE yhtio = '$kukarow[yhtio]'
					and nimi = '$valkoodi'";
		$vresult = pupe_query($vquery);
		$vrow = mysql_fetch_array($vresult);

		// Käsitellään myyntitilauksen otsikolla setattu "ostotilauksen käsittely"
		switch ($params['ostotilauksen_kasittely']) {
			case '4':
			case '5':
				$ostotilauksen_kasittely_wherelisa = "
					and toim_nimi		= '{$toim_nimi}'
					and toim_nimitark	= '{$toim_nimitark}'
					and toim_osoite		= '{$toim_osoite}'
					and toim_postino	= '{$toim_postino}'
					and toim_postitp	= '{$toim_postitp}'
					and toim_maa		= '{$toim_maa}'
					and varasto 		= '{$kukarow['oletus_ostovarasto']}'
					and ohjausmerkki 	= '{$params['ohjausmerkki']}'";
				break;
			case '2':
			case '3':
				$ostotilauksen_kasittely_wherelisa = "
					and toim_nimi		= '{$toim_nimi}'
					and toim_nimitark	= '{$toim_nimitark}'
					and toim_osoite		= '{$toim_osoite}'
					and toim_postino	= '{$toim_postino}'
					and toim_postitp	= '{$toim_postitp}'
					and toim_maa		= '{$toim_maa}'";
				break;
			case '1':
			case '0':
			default:
				$ostotilauksen_kasittely_wherelisa = "
					and toim_nimi		= '{$toim_nimi}'
					and toim_nimitark	= '{$toim_nimitark}'
					and toim_osoite		= '{$toim_osoite}'
					and toim_postino	= '{$toim_postino}'
					and toim_postitp	= '{$toim_postitp}'
					and toim_maa		= '{$toim_maa}'
					and left(luontiaika,10) = left(now(),10)
					and laatija 		= '{$kukarow['kuka']}'";
				break;
		}

		$query = "	SELECT *
					from lasku
					where yhtio 		= '$kukarow[yhtio]'
					and liitostunnus 	= '$tiltoi[tunnus]'
					and tila 			= 'O'
					and alatila 		= ''
					{$ostotilauksen_kasittely_wherelisa}";
		$ostotilaus_result = pupe_query($query);

		// ei löydy, tehdään uus otsikko
		if (mysql_num_rows($ostotilaus_result) == 0) {

			$query = "	INSERT INTO lasku SET
						alatila 			= '',
						huolitsija 			= '{$tiltoi['huolitsija']}',
						jakelu 				= '{$params['jakelu']}',
						kerayspvm 			= '{$params['myyntitil_kerayspvm']}',
						kuljetus 			= '{$tiltoi['kuljetus']}',
						laatija 			= '{$kukarow['kuka']}',
						liitostunnus		= '{$tiltoi['tunnus']}',
						luontiaika			= now(),
						maa 				= '{$tiltoi['maa']}',
						maksuteksti 		= '{$tiltoi['maksuteksti']}',
						myyja 				= '{$params['myytil_myyja']}',
						nimi 				= '{$tiltoi['nimi']}',
						nimitark 			= '{$tiltoi['nimitark']}',
						ohjausmerkki 		= '{$params['ohjausmerkki']}',
						osoite 				= '{$tiltoi['osoite']}',
						ovttunnus 			= '{$tiltoi['ovttunnus']}',
						postino 			= '{$tiltoi['postino']}',
						postitp 			= '{$tiltoi['postitp']}',
						sisviesti1			= '{$params['myytil_sisviesti1']}',
						tila 				= 'O',
						tilaustyyppi		= '',
						tilausyhteyshenkilo	= '{$kukarow['nimi']}',
						toimaika 			= '{$params['myytil_toimaika']}',
						toimitusehto 		= '{$tiltoi['toimitusehto']}',
						toimitustapa 		= '{$params['myytil_toimitustapa']}',
						toim_maa 			= '{$toim_maa}',
						toim_nimi 			= '{$toim_nimi}',
						toim_nimitark 		= '{$toim_nimitark}',
						toim_osoite 		= '{$toim_osoite}',
						toim_ovttunnus		= '{$tiltoi['ovttunnus']}',
						toim_postino 		= '{$toim_postino}',
						toim_postitp 		= '{$toim_postitp}',
						valkoodi 			= '{$valkoodi}',
						vanhatunnus			= '',
						varasto 			= '{$kukarow['oletus_ostovarasto']}',
						verkkotunnus		= '{$params['myytil_liitostunnus']}',
						vienti_kurssi 		= '{$vrow['kurssi']}',
						viesti 				= '{$params['myytil_viesti']}',
						viikorkopros 		= '{$yhtiorow['viivastyskorko']}',
						yhtio 				= '{$kukarow['yhtio']}',
						ytunnus				= '{$tiltoi['ytunnus']}'";
			pupe_query($query);
			$ostotilaus_tunnus = mysql_insert_id();

			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$ostotilaus_tunnus}'";
			$ostotilaus_result = pupe_query($query);

			$ostotilaus_row = mysql_fetch_assoc($ostotilaus_result);
		}
		else {
			$ostotilaus_row = mysql_fetch_assoc($ostotilaus_result);
		}

		return $ostotilaus_row;
	}
}
