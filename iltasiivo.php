<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli') {
		$php_cli = TRUE;
	}

	if ($php_cli) {

		if (!isset($argv[1]) or $argv[1] == '') {
			echo "Anna yhtiö!!!\n";
			die;
		}

		//tarvitaan yhtiö
		$kukarow['yhtio'] = $argv[1];
		$kukarow['kuka'] = "crond";

		// otetaan tietokanta connect
		require ("inc/connect.inc");
		require ("inc/functions.inc");

		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
		$aja = 'run';

		echo date("d.m.Y @ G:i:s").": Iltasiivo $yhtiorow[nimi]\n";
	}
	else {
		require ("inc/parametrit.inc");

		echo "<font class='head'>",t("Iltasiivo"),"</font><hr>";

		if ($aja != "run") {
			echo "<br><form method='post'>";
			echo "<input type='hidden' name='aja' value='run'>";
			echo "<input type='submit' value='",t("Aja iltasiivo"),"'>";
			echo "</form>";
		}

		echo "<pre>";
	}

	if ($aja == "run") {

		$iltasiivo = "";
		$laskuri   = 0;

		// poistetaan kaikki tuotteen_toimittajat liitokset joiden toimittaja on poistettu
		$query = "	SELECT toimi.tunnus, tuotteen_toimittajat.tunnus toimtunnus
					from tuotteen_toimittajat
					left join toimi on toimi.yhtio = tuotteen_toimittajat.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus
					where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
					having toimi.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_assoc($result)) {
			$query = "DELETE from tuotteen_toimittajat where tunnus = '$row[toimtunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri++;
		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Poistettiin $laskuri poistetun toimittajan tuoteliitosta.\n";
		$laskuri = 0;

		// poistetaan kaikki tuotteen_toimittajat liitokset joiden tuote on poistettu
		$query = "	SELECT tuote.tunnus, tuotteen_toimittajat.tunnus toimtunnus
					from tuotteen_toimittajat
					left join tuote on tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
					where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
					having tuote.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_assoc($result)) {
			$query = "DELETE from tuotteen_toimittajat where tunnus = '$row[toimtunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri++;
		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Poistettiin $laskuri poistetun tuotteen tuoteliitosta.\n";

		$laskuri = 0;
		$laskuri2 = 0;

		// poistetaan kaikki JT-otsikot jolla ei ole enää rivejä ja extranet tilaukset joilla ei ole rivejä ja tietenkin myös ennakkootsikot joilla ei ole rivejä.
		$query = "	SELECT tilausrivi.tunnus, lasku.tunnus laskutunnus, lasku.tila, lasku.tunnusnippu
					FROM lasku
					LEFT JOIN tilausrivi on( tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tila in ('N','E','L')
					AND lasku.alatila != 'X'
					AND tilausrivi.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_assoc($result)) {
			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa iltasiivo.php")." (1)<br>";

			//	Jos kyseessä on tunnusnippupaketti, halutaan säilyttää linkki tästä tehtyihin tilauksiin, tilaus merkataan vain toimitetuksi
			if ($row["tunnusnippu"] > 0) {
				$query = "UPDATE lasku set tila = 'L', alatila='X' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
				$deler = mysql_query($query) or die($query);
				$laskuri2++;
			}
			else {
				$query = "UPDATE lasku set alatila='$row[tila]', tila='D',  comments = '$komm' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
				$deler = mysql_query($query) or die($query);
				$laskuri++;
			}

			//poistetaan TIETENKIN kukarow[kesken] ettei voi syöttää extranetissä rivejä tälle
			$query = "UPDATE kuka set kesken = '' where yhtio = '$kukarow[yhtio]' and kesken = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);
		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Poistettiin $laskuri rivitöntä tilausta.\n";
		if ($laskuri2 > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Merkattiin toimitetuksi $laskuri2 rivitöntä tilausta.\n";

		$laskuri = 0;

		// Merkitään laskut mitätöidyksi joilla on pelkästään mitätöityjä rivejä / pelkästään puuterivejä.
		$query = "	SELECT lasku.tunnus laskutunnus, lasku.tila, count(*) kaikki, sum(if (tilausrivi.tyyppi='D' or tilausrivi.var='P', 1, 0)) dellatut
					from lasku
					join tilausrivi on tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					where lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila in ('N','E','L')
					and lasku.alatila != 'X'
					group by 1,2
					having dellatut > 0 and kaikki = dellatut";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_assoc($result)) {
			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa iltasiivo.php")." (2)<br>";

			$query = "UPDATE lasku set alatila='$row[tila]', tila='D',  comments = '$komm' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri++;

			//poistetaan TIETENKIN kukarow[kesken] ettei voi syöttää extranetissä rivejä tälle
			$query = "UPDATE kuka set kesken = '' where yhtio = '$kukarow[yhtio]' and kesken = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);
		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Mitätöitiin $laskuri tilausta joilla oli pelkkiä mitätöityjä rivejä.\n";

		$laskuri = 0;

		// Merkitään rivit mitätöidyksi joiden otsikot on mitätöity (ei mitätöidä puuterivejä, eikä suoraan saapumiseen lisättyjä ostorivejä lasku.alatila!='K')
		$query = "	SELECT lasku.tunnus laskutunnus
					from lasku
					join tilausrivi on tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D' and tilausrivi.var != 'P'
					where lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'D'
					and lasku.alatila != 'K'
					GROUP BY 1";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_assoc($result)) {
			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa iltasiivo.php")." (3)<br>";

			$query = "UPDATE tilausrivi set tyyppi='D' where yhtio = '$kukarow[yhtio]' and otunnus = '$row[laskutunnus]' and var != 'P'";
			$deler = mysql_query($query) or die($query);
			$laskuri++;
		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Mitätöitiin $laskuri mitätöidyn tilauksen rivit. (Rivit jostain syystä ei dellattuja)\n";

		$laskuri = 0;

		// Arkistoidaan tulostetut ostotilaukset joilla ei ole yhtään tulossa olevaa kamaa
		$query = "	SELECT distinct lasku.tunnus laskutunnus
					FROM lasku
					LEFT JOIN tilausrivi on tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'O' and tilausrivi.varattu != 0
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND lasku.tila = 'O'
					AND lasku.alatila = 'A'
					AND tilausrivi.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_assoc($result)) {
			$query = "UPDATE lasku set alatila='X' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri++;
		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Arkistoitiin $laskuri ostotilausta.\n";

		$laskuri = 0;

		// Vapautetaan holdissa olevat tilaukset, jos niillä on maksupositioita ja ennakkolaskut ovat maksettu
		// Holdissa olevat tilaukset ovat tilassa N B
		$query = "	SELECT DISTINCT jaksotettu
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tila = 'N'
					AND alatila = 'B'";
		$pos_chk_result = mysql_query($query) or die($query);

		while ($pos_chk_row = mysql_fetch_assoc($pos_chk_result)) {

			$query = "	SELECT maksupositio.otunnus,
						sum(if(ifnull(uusiolasku_ux.mapvm, '0000-00-00') != '0000-00-00', 1, 0)) laskutettu_ux_kpl,
						count(*) yhteensa_kpl
						FROM maksupositio
						LEFT JOIN lasku uusiolasku ON (maksupositio.yhtio = uusiolasku.yhtio and maksupositio.uusiotunnus = uusiolasku.tunnus)
						LEFT JOIN lasku uusiolasku_ux ON (uusiolasku_ux.yhtio = uusiolasku.yhtio and uusiolasku_ux.tila = 'U' and uusiolasku_ux.alatila = 'X' and uusiolasku_ux.laskunro = uusiolasku.laskunro)
						WHERE maksupositio.yhtio = '{$kukarow['yhtio']}'
						and maksupositio.otunnus = '{$pos_chk_row['jaksotettu']}'
						GROUP BY 1
						HAVING (yhteensa_kpl - laskutettu_ux_kpl) = 1
						ORDER BY 1, maksupositio.tunnus";
			$posres = mysql_query($query) or die($query);

			if (mysql_num_rows($posres) != 0) {

				$silent = 'Nyt hiljaa, hiljaa hiivitään näin Kardemumman yössä';
				$vapauta_tilaus_keraykseen = true;
				$kukarow['kesken'] = $pos_chk_row['jaksotettu'];

				$query = "	UPDATE lasku SET
							alatila = ''
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$pos_chk_row['jaksotettu']}'";
				$upd_res = pupe_query($query);

				$query = "	SELECT *
							FROM lasku
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$pos_chk_row['jaksotettu']}'";
				$laskures = mysql_query($query) or die($query);
				$laskurow = mysql_fetch_assoc($laskures);

				require('tilauskasittely/tilaus-valmis.inc');

				$laskuri++;
			}
		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Vapautettiin {$laskuri} myyntitilausta tulostusjonoon.\n";

		$laskuri = 0;

		// Arkistoidaan saapumiset joilla ei ole yhtään liitettyä riviä eikä yhtään laskuja liitetty
		$query = "	SELECT distinct lasku.tunnus laskutunnus
					FROM lasku
					LEFT JOIN lasku liitosotsikko ON liitosotsikko.yhtio = lasku.yhtio and liitosotsikko.tila=lasku.tila and liitosotsikko.laskunro = lasku.laskunro and liitosotsikko.vanhatunnus > 0
					LEFT JOIN tilausrivi on tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'O'
					LEFT JOIN tilausrivi suoraan_keikalle on suoraan_keikalle.yhtio = lasku.yhtio and suoraan_keikalle.otunnus = lasku.tunnus and suoraan_keikalle.tyyppi = 'O'
					WHERE lasku.yhtio 	  = '$kukarow[yhtio]'
					AND lasku.tila 		  = 'K'
					AND lasku.mapvm		  = '0000-00-00'
					AND lasku.vanhatunnus = 0
					AND tilausrivi.tunnus is null
					AND suoraan_keikalle.tunnus is null
					AND liitosotsikko.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_assoc($result)) {
			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa iltasiivo.php")."<br>";

			$query = "UPDATE lasku set alatila = tila, tila = 'D', comments = '$komm' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri++;
		}

		if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Mitätöitiin $laskuri tyhjää saapumista.\n";

		// tässä tehdään isittömistä perheistä ei-perheitä ja myös perheistä joissa ei ole lapsia eli nollataan perheid
		$lask = 0;
		$lask2 = 0;

		$query = "	SELECT perheid, count(*) koko
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and perheid != '0'
					GROUP BY perheid";
		$result = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_assoc($result)) {
			$query = "	SELECT perheid
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and tunnus = '$row[perheid]'";
			$result2 = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result2) == 0) {
				$lask++;
				$query = "UPDATE tilausrivi SET perheid = 0 WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and perheid = '$row[perheid]' order by tunnus";
				$upresult = mysql_query($query) or pupe_error($query);
			}
			else {
				if ($row['koko'] == 1) {
					$lask2++;
					$query = "UPDATE tilausrivi SET perheid = 0 WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and perheid = '$row[perheid]' order by tunnus";
					$upresult = mysql_query($query) or pupe_error($query);
				}
			}
		}

		if ($lask + $lask2 > 0) {
			$iltasiivo .= date("d.m.Y @ G:i:s").": Tuhottiin $lask orpoa perhettä, ja $lask2 lapsetonta isää (eli perheid nollattiin)\n";
		}

		$lasktuote = 0;
		$laskpois = 0;
		$poistetaankpl = 0;

		$query = "	SELECT tuoteno, liitostunnus, count(tunnus) countti
					FROM tuotteen_toimittajat
					WHERE yhtio = '$kukarow[yhtio]'
					GROUP BY 1,2
					HAVING countti > 1";
		$result = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_assoc($result)) {
			$lasktuote++;
			$poistetaankpl = $row['countti']-1;

			$poisquery = "	DELETE FROM tuotteen_toimittajat
							WHERE yhtio = '$kukarow[yhtio]'
							AND tuoteno = '$row[tuoteno]'
							AND liitostunnus = '$row[liitostunnus]'
							ORDER BY tunnus DESC
						 	LIMIT $poistetaankpl";
			$poisresult = mysql_query($poisquery) or pupe_error($poisquery);
			$laskpois += mysql_affected_rows();
		}

		if ($lasktuote > 0) {
			$iltasiivo .= date("d.m.Y @ G:i:s").": Poistettiin $lasktuote tuotteelta yhteensä $laskpois duplikaatti tuotteen_toimittajaa\n";
		}

		$kukaquery = "	UPDATE kuka
						SET taso = '2'
						WHERE taso = '3'
						and extranet = ''";
		$kukaresult = mysql_query($kukaquery) or pupe_error($kukaquery);

		if (mysql_affected_rows() > 0) {
			$iltasiivo .= date("d.m.Y @ G:i:s").": Päivitettiin ".mysql_affected_rows()." käyttäjän taso 3 --> 2\n";
		}

		// mitätöidään keskenolevia extranet-tilauksia, jos ne on liian vanhoja ja yhtiön parametri on päällä
		if ($yhtiorow['iltasiivo_mitatoi_ext_tilauksia'] != '') {

			$laskuri = 0;
			$aikaraja = (int) $yhtiorow['iltasiivo_mitatoi_ext_tilauksia'];

			$query = "	SELECT lasku.tunnus laskutunnus
						FROM lasku
						JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = lasku.laatija AND kuka.extranet != '')
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.tila = 'N'
						AND lasku.alatila = ''
						AND lasku.luontiaika < DATE_SUB(now(), INTERVAL $aikaraja HOUR)";
			$result = mysql_query($query) or die($query);

			while ($row = mysql_fetch_assoc($result)) {
				// laitetaan kaikki poimitut extranet jt-rivit takaisin omille vanhoille tilauksille
				$query = "	SELECT tilausrivi.tunnus, tilausrivin_lisatiedot.vanha_otunnus
							FROM tilausrivi
							JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.positio = 'JT')
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.otunnus = '{$row['laskutunnus']}'";
				$jt_rivien_muisti_res = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($jt_rivien_muisti_res) > 0) {
					$jt_saldo_lisa = $yhtiorow["varaako_jt_saldoa"] == "" ? ", jt = varattu, varattu = 0 " : '';

					while ($jt_rivien_muisti_row = mysql_fetch_assoc($jt_rivien_muisti_res)) {
						$query = "	UPDATE tilausrivi SET
									otunnus = '{$jt_rivien_muisti_row['vanha_otunnus']}',
									var = 'J'
									$jt_saldo_lisa
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$jt_rivien_muisti_row['tunnus']}'";
						$jt_rivi_res = mysql_query($query) or pupe_error($query);
					}
				}

				$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa iltasiivo.php")." (4)<br>";

				$query = "	UPDATE lasku SET
							alatila = 'N',
							tila = 'D',
							comments = '$komm'
							WHERE yhtio = '{$kukarow['yhtio']}'
							and tunnus = '{$row['laskutunnus']}'";
				$deler = mysql_query($query) or die($query);

				$query = "	UPDATE tilausrivi SET
							tyyppi = 'D'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND otunnus = '{$row['laskutunnus']}'
							and var != 'P'";
				$deler = mysql_query($query) or die($query);

				//poistetaan TIETENKIN kukarow[kesken] ettei voi syöttää extranetissä rivejä tälle
				$query = "	UPDATE kuka SET
							kesken = ''
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND kesken = '{$row['laskutunnus']}'";
				$deler = mysql_query($query) or die($query);

				$laskuri++;
			}

			if ($laskuri > 0) $iltasiivo .= date("d.m.Y @ G:i:s").": Mitätöitiin $laskuri extranet-tilausta, jotka olivat $aikaraja tuntia vanhoja.\n";
		}

		if (table_exists('suorituskykyloki')) {
			$query = "	DELETE FROM suorituskykyloki
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND luontiaika < date_sub(now(), INTERVAL 1 YEAR)";
			$deler = mysql_query($query) or die($query);

			if (mysql_affected_rows() > 0) {
				$iltasiivo .= date("d.m.Y @ G:i:s").": Poistettiin ".mysql_affected_rows()." riviä suorituskykylokista.\n";
			}
		}

		// Dellataan rogue oikeudet
		$query = "	DELETE o1.*
					FROM oikeu o1
					LEFT JOIN oikeu o2 ON o1.yhtio=o2.yhtio and o1.sovellus=o2.sovellus and o1.nimi=o2.nimi and o1.alanimi=o2.alanimi and o2.kuka=''
					WHERE o1.yhtio = '{$kukarow['yhtio']}'
					and o1.kuka != ''
					and o2.tunnus is null";
		$result = mysql_query($query) or pupe_error($query);


		// Merkataan myyntitilit valmiiksi, jos niillä ei ole yhtään käsittelemättömiä rivejä
		$query = "	SELECT lasku.tunnus,
					sum(if(tilausrivi.kpl != 0, 1, 0)) ei_valmis
					FROM lasku
					JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'G'
					and lasku.tilaustyyppi = 'M'
					and lasku.alatila = 'V'
					GROUP BY lasku.tunnus
					HAVING ei_valmis = 0";
		$result = pupe_query($query);

		$myyntitili = 0;

		while ($laskurow = mysql_fetch_assoc($result)) {
			$query = "	UPDATE lasku
						SET alatila = 'X'
						WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
						AND lasku.tunnus  = '{$laskurow["tunnus"]}'";
			$update_result = pupe_query($query);
			$myyntitili++;
		}

		if ($myyntitili > 0) {
			$iltasiivo .= date("d.m.Y @ G:i:s").": Merkattiin $myyntitili myyntitiliä valmiiksi.\n";
		}

		// Poistetaan kaikki myyntitili-varastopaikat, jos niiden saldo on nolla
		$query = "	SELECT tunnus, tuoteno
					FROM tuotepaikat
					WHERE tuotepaikat.yhtio = '{$kukarow["yhtio"]}'
					AND tuotepaikat.hyllyalue = '!!M'
					AND tuotepaikat.oletus = ''
					AND tuotepaikat.saldo = 0";
		$iltatuotepaikatresult = pupe_query($query);

		$myyntitili = 0;

		while ($iltatuotepaikatrow = mysql_fetch_assoc($iltatuotepaikatresult)) {
			$tee = "MUUTA";
			$tuoteno = $iltatuotepaikatrow["tuoteno"];
			$poista = array($iltatuotepaikatrow["tunnus"]);
			$halyraja2 = array();
			$tilausmaara2 = array();
			$kutsuja = "vastaanota.php";
			require("muuvarastopaikka.php");
			$myyntitili++;
		}

		if ($myyntitili > 0) {
			$iltasiivo .= date("d.m.Y @ G:i:s").": Poistettiin $myyntitili tyhjää myyntitilin varastopaikkaa.\n";
		}

		if ($iltasiivo != "" or $php_cli) {
			echo $iltasiivo;
			echo date("d.m.Y @ G:i:s").": Iltasiivo $yhtiorow[nimi]. Done!\n\n";
		}

		if ($iltasiivo != "") {
			if (isset($iltasiivo_email) and $iltasiivo_email == 1) {
				$header	 = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
				$header .= "MIME-Version: 1.0\n" ;
				$subject = "Iltasiivo ".date("d.m.Y")." - $yhtiorow[nimi]";

				mail($yhtiorow["admin_email"], mb_encode_mimeheader("Iltasiivo yhtiölle '{$yhtiorow["yhtio"]}'", "ISO-8859-1", "Q"), $iltasiivo, $header, " -f $yhtiorow[postittaja_email]");
			}
		}

		# Poistetaan tuotepaikat joiden saldo on 0 ja ne on määritelty reservipaikoiksi (ei kuitenkaan oletuspaikkaa)
		if ($yhtiorow['kerayserat'] == 'K') {
			$query = "	SELECT tuotepaikat.*
						FROM tuotepaikat
						JOIN varaston_hyllypaikat ON (varaston_hyllypaikat.yhtio = tuotepaikat.yhtio AND varaston_hyllypaikat.hyllyalue = tuotepaikat.hyllyalue AND varaston_hyllypaikat.hyllynro = tuotepaikat.hyllynro AND varaston_hyllypaikat.hyllyvali = tuotepaikat.hyllyvali AND varaston_hyllypaikat.hyllytaso = tuotepaikat.hyllytaso AND varaston_hyllypaikat.reservipaikka = 'K')
						WHERE tuotepaikat.yhtio='{$kukarow['yhtio']}'
						AND tuotepaikat.saldo=0
						AND tuotepaikat.oletus = ''
						AND tuotepaikat.inventointilista_aika='0000-00-00 00:00:00'";
			$tuotepaikat = pupe_query($query);

			# Poistetaan löydetyt rivit ja tehdään tapahtuma
			while($tuotepaikkarow = mysql_fetch_assoc($tuotepaikat)) {
				# Poistetaan paikka
				$query = "	DELETE FROM tuotepaikat
							WHERE yhtio='{$kukarow['yhtio']}'
							AND tunnus='{$tuotepaikkarow['tunnus']}'
							AND saldo=0";
				$tuotepaikat_siivo_result = pupe_query($query);

				# Tehdään tapahtuma
				$query = "	INSERT INTO tapahtuma SET
							yhtio 		= '$kukarow[yhtio]',
							tuoteno 	= '$tuotepaikkarow[tuoteno]',
							kpl			= '0',
							kplhinta	= '0',
							hinta		= '0',
							hyllyalue	= '$tuotepaikkarow[hyllyalue]',
							hyllynro	= '$tuotepaikkarow[hyllynro]',
							hyllyvali	= '$tuotepaikkarow[hyllyvali]',
							hyllytaso	= '$tuotepaikkarow[hyllytaso]',
							laji		= 'poistettupaikka',
							selite		= '".t("Poistettiin tuotepaikka")." $tuotepaikkarow[hyllyalue] $tuotepaikkarow[hyllynro] $tuotepaikkarow[hyllyvali] $tuotepaikkarow[hyllytaso]',
							laatija		= '$kukarow[kuka]',
							laadittu	= now()";
				$tapahtuma_result = pupe_query($query);
			}
		}
	}

	/**
	 * Poistetaan poistettavaksi merkatut tuotepaikat joilla ei ole saldoa
	 * tuotepaikat.poistettava = 'D' ja tuotepaikat.saldo=0
	 * Ei poisteta oletuspaikkaa
	 */
	$query = "SELECT *
				FROM tuotepaikat
				WHERE yhtio     = '{$kukarow['yhtio']}'
				AND poistettava = 'D'
				AND saldo       = 0
				AND oletus 		= ''";
	$poistettavat_tuotepaikat = pupe_query($query);
	$poistettu = 0;

	// Loopataan poistettavat tuotepaikat läpi
	while ($tuotepaikka = mysql_fetch_assoc($poistettavat_tuotepaikat)) {

		// Poistetaan tuotepaikka
		$query = "DELETE FROM tuotepaikat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND poistettava = 'D'
					AND saldo   = 0
					AND tunnus  = {$tuotepaikka['tunnus']}";
		$poistettu_result = pupe_query($query);
		$poistettu++;

		// Luodaan tapahtuma
		$tapahtuma_query = "INSERT INTO tapahtuma SET
							yhtio 		= '$kukarow[yhtio]',
							tuoteno 	= '$tuotepaikka[tuoteno]',
							kpl			= '0',
							kplhinta	= '0',
							hinta		= '0',
							hyllyalue	= '$tuotepaikka[hyllyalue]',
							hyllynro	= '$tuotepaikka[hyllynro]',
							hyllyvali	= '$tuotepaikka[hyllyvali]',
							hyllytaso	= '$tuotepaikka[hyllytaso]',
							laji		= 'poistettupaikka',
							selite		= '".t("Poistettiin tuotepaikka")." $tuotepaikka[hyllyalue] $tuotepaikka[hyllynro] $tuotepaikka[hyllyvali] $tuotepaikka[hyllytaso]',
							laatija		= '$kukarow[kuka]',
							laadittu	= now()";
		$tapahtuma_result = pupe_query($tapahtuma_query);
	}

	echo date("d.m.Y @ G:i:s").": Poistettiin $poistettu poistettavaksi merkattua tuotepaikkaa.\n";

	if (!$php_cli) {
		echo "</pre>";
		require('inc/footer.inc');
	}
