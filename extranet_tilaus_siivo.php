<?php

	// Kutsutaanko CLI:stä
	if (php_sapi_name() != 'cli') {
		die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
	}

	// komentoriviltä pitää tulla parametrinä yhtio
	if (trim($argv[1]) != '') {

		date_default_timezone_set('Europe/Helsinki');

		// otetaan tietokanta connect
		require ("inc/connect.inc");
		require ("inc/functions.inc");

		// hmm.. jännää
		$kukarow['yhtio'] = mysql_real_escape_string($argv[1]);
		$kukarow['kuka'] = "crond";

		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		if (!isset($yhtiorow['extranet_tilaus_varaa_saldoa'])) {
			die ("Yhtiöllä ei ole parametria extranet_tilaus_varaa_saldoa!\n");
		}

		$laskuri_jt = 0;
		$laskuri_norm = 0;

		$query = "	SELECT lasku.tunnus laskutunnus, asiakas.extranet_tilaus_varaa_saldoa
					FROM lasku
					JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = lasku.laatija AND kuka.extranet != '')
					JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					AND lasku.tila = 'N'
					AND lasku.alatila = ''";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_assoc($result)) {

			$aikaraja = 0;

			// tyhjä = Yhtiön oletus
			// X = Extranet-tilaus varaa saldoa
			// numero = Extranet-tilaus varaa saldoa x tuntia
			// E = Extranet-tilaus ei varaa saldoa
			if ($row['extranet_tilaus_varaa_saldoa'] != 'X') {

				// tyhjä on yhtiön oletus
				if ($row['extranet_tilaus_varaa_saldoa'] == '') {

					// jos yhtiön oletus ei ole tyhjää, otetaan aikaraja tunteina
					if ($yhtiorow['extranet_tilaus_varaa_saldoa'] != '') {
						$aikaraja = (int) $yhtiorow['extranet_tilaus_varaa_saldoa'];
					}
					else {
						continue;
					}
				}
				else {
					$aikaraja = (int) $row['extranet_tilaus_varaa_saldoa'];
				}
			}
			else {
				continue;
			}

			if ($aikaraja > 0) {

				// laitetaan kaikki poimitut extranet jt-rivit takaisin omille vanhoille tilauksille
				// laitetaan myös ei poimitut extranet jt-rivit varattu = 0
				$query = "	SELECT tilausrivi.tunnus, tilausrivin_lisatiedot.vanha_otunnus, tilausrivin_lisatiedot.positio, tilausrivi.var, sum(if(tilausrivi.laadittu < DATE_SUB(now(), INTERVAL $aikaraja HOUR), 0, 1)) laskuri
							FROM tilausrivi
							LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.otunnus = '{$row['laskutunnus']}'
							AND tilausrivi.varattu > 0
							GROUP BY 1, 2, 3, 4
							HAVING laskuri = 0";
				$rivi_check_res = mysql_query($query) or die($query);

				if (mysql_num_rows($rivi_check_res) > 0) {

					$jt_saldo_lisa = $yhtiorow["varaako_jt_saldoa"] == "" ? ", jt = varattu, varattu = 0 " : '';

					while ($rivi_check_row = mysql_fetch_assoc($rivi_check_res)) {

						if ($rivi_check_row['positio'] == 'JT') {
							$query = "	UPDATE tilausrivi SET
										otunnus = '{$rivi_check_row['vanha_otunnus']}',
										var = 'J'
										$jt_saldo_lisa
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$rivi_check_row['tunnus']}'";
							$rivi_res = mysql_query($query) or die($query);
							$laskuri_jt++;
						}
						else {
							$query = "	UPDATE tilausrivi SET
										varattu = 0
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$rivi_check_row['tunnus']}'";
							$rivi_res = mysql_query($query) or die($query);

							$query = "	UPDATE tilausrivin_lisatiedot SET
										positio = 'Ei varaa saldoa'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tilausrivitunnus = '{$rivi_check_row['tunnus']}'";
							$rivi_res = mysql_query($query) or die($query);
							$laskuri_norm++;
						}
					}
				}
			}
		}

		if ($laskuri_jt > 0 or $laskuri_norm > 0) {
			echo date("Y-m-d H:i:s").": Palautettiin $laskuri_jt jt-riviä alkuperäisille extranet-tilauksille ja $laskuri_norm tilausriviä ei enää varaa saldoa.\n";
		}
	}

?>