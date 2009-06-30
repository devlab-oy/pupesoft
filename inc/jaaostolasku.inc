<?php

function jaalasku($jaettavalasku, $vahennettavasumma_valuutassa) {

	global $yhtiorow, $kukarow, $palvelin2;

	$fields	= "";
	$values	= "";
	$tunnus	= (int) $jaettavalasku;
	$vahennettavasumma_valuutassa = (float) str_replace(",", ".", $vahennettavasumma_valuutassa);

	// Katsotaan, ett‰ lasku on hyv‰ksytt‰v‰n‰ tai valmiina maksatukseen
	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$jaettavalasku'
				AND tila in ('H', 'M')";
	$jaettavalaskures = mysql_query($query) or pupe_error($query);
	$jaettavalaskurow = mysql_fetch_array($jaettavalaskures);

	if (($jaettavalaskurow['summa'] >= 0 and $vahennettavasumma_valuutassa <= 0) or ($jaettavalaskurow['summa'] <= 0 and $vahennettavasumma_valuutassa >= 0) or (abs($vahennettavasumma_valuutassa) >= abs($jaettavalaskurow['summa']))) {
		echo "<font class='error'>",t("Syˆtetty summa on virheellinen"),"! $vahennettavasumma_valuutassa<br/>";
		return false;
	}

	// Haetaan keikat, joihin lasku on liitetty
	$query = "	SELECT ifnull(group_concat(laskunro), 0) keikat
				FROM lasku USE INDEX (tila_index)
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				and lasku.liitostunnus = '{$jaettavalaskurow['liitostunnus']}'
				and lasku.tila = 'K'
				and lasku.alatila in ('', 'S')
				and lasku.vanhatunnus = '{$jaettavalaskurow['tunnus']}'";
	$keikkares = mysql_query($query) or pupe_error($query);
	$keikkarow = mysql_fetch_assoc($keikkares);

	// katsotaan onko j‰lkilaskettu
	$query = "	SELECT alatila, kohdistettu
				FROM lasku
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND laskunro in ($keikkarow[keikat])
				AND laskunro != 0
				AND tila = 'K'
				AND alatila = 'X'
				AND kohdistettu = 'X'";
	$check_res = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($jaettavalaskures) == 1 and mysql_num_rows($check_res) == 0) {

		$vahennettavasumma = round($vahennettavasumma_valuutassa * $jaettavalaskurow['vienti_kurssi'], 2);

		// Tehd‰‰n uusi lasku
		for ($i=0; $i < mysql_num_fields($jaettavalaskures)-1; $i++) {

			$fields .= mysql_field_name($jaettavalaskures, $i).",";

			switch (mysql_field_name($jaettavalaskures, $i)) {
				case 'summa_valuutassa':
					$values .= "'$vahennettavasumma_valuutassa',";
					break;
				case 'summa':
					$values .= "'$vahennettavasumma',";
					break;
				case 'arvo':
					$values .= "'$jaettavalaskurow[summa]',";
					break;
				case 'arvo_valuutassa':
					$values .= "'$jaettavalaskurow[summa_valuutassa]',";
					break;
				case 'kasumma':
				case 'kasumma_valuutassa':
					$values .= "'0.00',";
					break;
				case 'vanhatunnus' :
					$values .= "'$jaettavalaskurow[tunnus]',";
					break;
				default:
					$values .= "'$jaettavalaskurow[$i]',";
			}
		}

		// Vikat pilkut pois
		$fields = substr($fields, 0, -1);
		$values = substr($values, 0, -1);

		// Lis‰t‰‰n uusi lasku
		$query = "INSERT into lasku ($fields) VALUES ($values)";
		$result = mysql_query($query) or pupe_error($query);
		$utunnus = mysql_insert_id();

		// P‰ivitet‰‰n vanha lasku
		$query  = "		UPDATE lasku SET
						summa = $jaettavalaskurow[summa] - $vahennettavasumma,
						summa_valuutassa = $jaettavalaskurow[summa_valuutassa] - $vahennettavasumma_valuutassa,
						arvo = $jaettavalaskurow[summa],
						arvo_valuutassa = $jaettavalaskurow[summa_valuutassa],
						kasumma = 0.00,
						kasumma_valuutassa = 0.00
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tunnus = '{$jaettavalaskurow['tunnus']}'";
		$result = mysql_query($query) or pupe_error($query);

		// Lis‰t‰‰n tiliˆinnit
		// V‰hennet‰‰n alkuper‰isen laskun ostovelkaa ja siirret‰‰n se selvittelytilille
		$selite = t('Ostolaskun osasuoritus');

//		pit‰‰ laskea kerroin annettavasta summasta ja alkuper‰isest‰ summasta
		$kerroin = round($vahennettavasumma / $jaettavalaskurow['summa'], 6);

		$aputunnukset = array();

		$query = "	SELECT *
					FROM tiliointi
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND ltunnus = '{$jaettavalaskurow['tunnus']}'
					AND korjattu = ''
					ORDER BY aputunnus DESC";
		$tiliointires = mysql_query($query) or pupe_error($query);

		while ($tiliointirow = mysql_fetch_assoc($tiliointires)) {

			$uusi_summa = (float)round($tiliointirow["summa"] * $kerroin, 2);
			$uusi_summa_valuutassa = (float)round($tiliointirow["summa_valuutassa"] * $kerroin, 2);

			$vanha_summa = (float)$tiliointirow["summa"] - $uusi_summa;
			$vanha_summa_valuutassa = (float)$tiliointirow["summa_valuutassa"] - $uusi_summa_valuutassa;

			// ei kopioida pyˆristystili‰
			if ($tiliointirow['tilino'] != $yhtiorow['pyoristys']) {

				// tehd‰‰n uudet tiliˆnnit
				$query = "	INSERT INTO tiliointi SET
							yhtio 				= '{$tiliointirow['yhtio']}',
							ltunnus 			= '{$jaettavalaskurow['tunnus']}',
							tilino 				= '{$tiliointirow['tilino']}',
							kustp				= '{$tiliointirow['kustp']}',
							projekti			= '{$tiliointirow['projekti']}',
							kohde				= '{$tiliointirow['kohde']}',
							vero				= '{$tiliointirow['vero']}',
							tapvm 				= '{$tiliointirow['tapvm']}',
							summa 				= '$vanha_summa',
							summa_valuutassa	= '$vanha_summa_valuutassa',
							valkoodi			= '{$tiliointirow['valkoodi']}',
							selite				= '{$tiliointirow['selite']}',
							lukko				= '{$tiliointirow['lukko']}',
							korjattu			= '{$tiliointirow['korjattu']}',
							korjausaika			= '{$tiliointirow['korjausaika']}',
							tosite				= '{$tiliointirow['tosite']}',
							aputunnus			= '',
							laatija				= '{$kukarow['kuka']}',
							laadittu			= now()";
				$result = mysql_query($query) or pupe_error($query);
				$uusi_id = mysql_insert_id();

				// jos aputunnus ei ole tyhj‰, otetaan se avaimeksi taulukkoon ja laitetaan uuden aputunnusrivin id sen alle
				if ($tiliointirow['aputunnus'] != '') {
					$aputunnukset[$tiliointirow['aputunnus']] = $uusi_id;
				}

				// jos tiliˆintirivi lˆytyy avaimena taulukosta
				if (array_key_exists($tiliointirow['tunnus'], $aputunnukset)) {
					$where_id = $aputunnukset[$tiliointirow['tunnus']];
					$query = "	UPDATE tiliointi SET
								aputunnus = $uusi_id
								WHERE tunnus = $where_id";
					$result = mysql_query($query) or pupe_error($query);
				}

				$query = "	INSERT INTO tiliointi SET
							yhtio 				= '{$tiliointirow['yhtio']}',
							ltunnus 			= '$utunnus',
							tilino 				= '{$tiliointirow['tilino']}',
							kustp				= '{$tiliointirow['kustp']}',
							kohde				= '{$tiliointirow['kohde']}',
							projekti			= '{$tiliointirow['projekti']}',
							vero				= '{$tiliointirow['vero']}',
							tapvm 				= '{$tiliointirow['tapvm']}',
							summa 				= '$uusi_summa',
							summa_valuutassa	= '$uusi_summa_valuutassa',
							valkoodi			= '{$tiliointirow['valkoodi']}',
							selite				= '{$tiliointirow['selite']}',
							lukko				= '{$tiliointirow['lukko']}',
							korjattu			= '{$tiliointirow['korjattu']}',
							korjausaika			= '{$tiliointirow['korjausaika']}',
							tosite				= '{$tiliointirow['tosite']}',
							aputunnus			= '',
							laatija				= '{$kukarow['kuka']}',
							laadittu			= now()";
				$result = mysql_query($query) or pupe_error($query);
				$uusi_id = mysql_insert_id();

				if ($tiliointirow['aputunnus'] != '') {
					$aputunnukset[$tiliointirow['aputunnus']] = $uusi_id;
				}

				if (array_key_exists($tiliointirow['tunnus'], $aputunnukset)) {
					$where_id = $aputunnukset[$tiliointirow['tunnus']];

					$query = "	UPDATE tiliointi SET
								aputunnus = $uusi_id,
								summa = $uusi_summa * ($tiliointirow[vero] / 100),
								summa_valuutassa = $uusi_summa_valuutassa * ($tiliointirow[vero] / 100)
								WHERE tunnus = $where_id";
					$result = mysql_query($query) or pupe_error($query);

				}
			}

			// yliviivataan vanhat tiliˆinnit
			$query = "	UPDATE tiliointi SET
						korjattu = '{$kukarow['kuka']}',
						korjausaika = now()
						WHERE yhtio = '{$tiliointirow['yhtio']}'
						AND tunnus = '{$tiliointirow['tunnus']}'";
			$result = mysql_query($query) or pupe_error($query);
		}


		// tarkistetaan onko pyˆristyseroja
		$query = "	SELECT sum(summa) summa, sum(summa_valuutassa) summa_valuutassa
					FROM tiliointi
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND ltunnus = '{$jaettavalaskurow['tunnus']}'
					AND korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);
		$check1 = mysql_fetch_assoc($result);
		
		$query = "	SELECT sum(summa) summa, sum(summa_valuutassa) summa_valuutassa
					FROM tiliointi
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND ltunnus = '$utunnus'
					AND korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);
		$check2 = mysql_fetch_assoc($result);
		
		if ($check1['summa'] != 0) {
			$query = "	INSERT into tiliointi set
						yhtio 				= '$kukarow[yhtio]',
						ltunnus 			= '{$jaettavalaskurow['tunnus']}',
						tilino 				= '$yhtiorow[pyoristys]',
						kustp 				= '',
						kohde 				= '',
						projekti 			= '',
						tapvm 				= '{$jaettavalaskurow['tapvm']}',
						summa 				= -1 * $check1[summa],
						summa_valuutassa 	= -1 * $check1[summa_valuutassa],
						valkoodi			= '{$jaettavalaskurow['valkoodi']}',
						vero 				= '0',
						selite 				= '".t("Pyˆristysero")."',
						lukko 				= '',
						laatija 			= '$kukarow[kuka]',
						laadittu 			= now()";
			$laskutusres = mysql_query($query) or pupe_error($query);
		}

		if ($check2['summa'] != 0) {
			$query = "	INSERT into tiliointi set
						yhtio 				= '$kukarow[yhtio]',
						ltunnus 			= '$utunnus',
						tilino 				= '$yhtiorow[pyoristys]',
						kustp 				= '',
						kohde 				= '',
						projekti 			= '',
						tapvm 				= '{$jaettavalaskurow['tapvm']}',
						summa 				= -1 * $check2[summa],
						summa_valuutassa 	= -1 * $check2[summa_valuutassa],
						valkoodi			= '{$jaettavalaskurow['valkoodi']}',
						vero 				= '0',
						selite 				= '".t("Pyˆristysero")."',
						lukko 				= '',
						laatija 			= '$kukarow[kuka]',
						laadittu 			= now()";
			$laskutusres = mysql_query($query) or pupe_error($query);
		}

		// Haetaan keikat, joihin lasku on liitetty
		$query = "	SELECT *
					FROM lasku USE INDEX (tila_index)
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					and lasku.liitostunnus = '{$jaettavalaskurow['liitostunnus']}'
					and lasku.tila = 'K'
					and lasku.alatila in ('', 'S')
					and lasku.vanhatunnus = '{$jaettavalaskurow['tunnus']}'";
		$keikkares = mysql_query($query) or pupe_error($query);

		$keikat = array();
		while ($keikkarow = mysql_fetch_assoc($keikkares)) {
			// katsotaan onko alkuper‰inen lasku liitetty kokonaan keikkaan, jos on voidaan liitt‰‰ molemmat laskut uudestaan
			if ($jaettavalaskurow['summa'] == $keikkarow['summa']) {
				$keikat[] = $keikkarow['laskunro'];
				echo "<font class='error'>Liitet‰‰n molemmat laskut uudestaan keikkaan $keikkarow[laskunro]</font><br>";
			}
			else {
				echo "<font class='error'>Lasku oli liitetty vain osittain keikkaan {$keikkarow['laskunro']}, joten uusia laskuja ei voitu liitt‰‰. K‰y korjaamassa keikan laskut manuaalisesti.</font><br>";
				echo "summa ei m‰ts‰nny (jaettavan laskun summa $jaettavalaskurow[summa] <> keikan summa $keikkarow[summa])<br>";
			}
		}

		// tuhotaan vanhat liitosotsikot
		$query = "	UPDATE lasku SET
		 			tila = 'D'
					WHERE lasku.yhtio = '{$kukarow['yhtio']}'
					and lasku.liitostunnus = '{$jaettavalaskurow['liitostunnus']}'
					and lasku.tila = 'K'
					and lasku.alatila in ('', 'S')
					and lasku.vanhatunnus = '{$jaettavalaskurow['tunnus']}'";
		$keikkares = mysql_query($query) or pupe_error($query);
		
		foreach ($keikat as $keikka) {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tila = 'K'
						AND vanhatunnus = 0
						AND laskunro = '$keikka'";
			$laskures = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_assoc($laskures);
			
			$tee = 'liita';
			$otunnus = $laskurow['tunnus'];
			$silent = 'jee';
			
			$laskutunnus = $jaettavalaskurow['tunnus'];
			require('tilauskasittely/kululaskut.inc');

			$laskutunnus = $utunnus;
			require('tilauskasittely/kululaskut.inc');
		}

		// T‰ss‰ kopioidaan laskun liitetiedostot uudelle laskulle
		$query = "	INSERT INTO liitetiedostot 
					(yhtio, data, filename, filesize, filetype, image_bits, image_channels, image_height, image_width, jarjestys, kayttotarkoitus, kieli, laatija, liitos, liitostunnus, luontiaika, muutospvm, muuttaja, selite)
					SELECT yhtio, data, filename, filesize, filetype, image_bits, image_channels, image_height, image_width, jarjestys, kayttotarkoitus, kieli, laatija, liitos, '$utunnus', luontiaika, muutospvm, muuttaja, selite
					FROM liitetiedostot
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND liitostunnus = '{$jaettavalaskurow['tunnus']}'
					AND liitos = 'lasku'";
		$insert_res = mysql_query($query) or pupe_error($query);

		echo "<a href='muutosite.php?tee=E&tunnus=$utunnus&lopetus=",$palvelin2,"muutosite.php////tee=E//tunnus={$jaettavalaskurow['tunnus']}'>",t("P‰‰set uudelle ostolaskulle t‰st‰"),"</a><br/><br/>";
		return true;
	}
	else {
		echo "<font class='error'>",t('Valittua laskua ei voida jakaa'),"</font><br/>";
		return false;
	}

}