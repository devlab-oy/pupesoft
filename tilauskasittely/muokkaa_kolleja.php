<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>",t("Muokkaa kolleja"),"</font><hr>";

	if (!isset($tee)) $tee = "";
	if (!isset($checkbox_parent)) $checkbox_parent = array();
	if (!isset($lopetus)) $lopetus = "";
	if (!isset($select_varasto)) $select_varasto = 0;

	$lahto = isset($checkbox_parent[0]) ? (int) $checkbox_parent[0] : "";

	echo "<br />";

	echo "<form method='post' action='?tee=&lopetus={$lopetus}'>";
	echo "<table>";

	echo "<tr><th>",t("Valitse varasto"),"</th><td>&nbsp;";
	echo "<select name='select_varasto' id='select_varasto'>";
	echo "<option value=''>",t("Valitse"),"</option>";

	$query = "	SELECT tunnus, nimitys
				FROM varastopaikat
				WHERE yhtio = '{$kukarow['yhtio']}' AND tyyppi != 'P'
				ORDER BY tyyppi, nimitys";
	$varastores = pupe_query($query);

	while ($varastorow = mysql_fetch_assoc($varastores)) {

		$sel = $select_varasto == $varastorow['tunnus'] ? " selected" : ($kukarow['oletus_varasto'] == $varastorow['tunnus'] ? " selected" : "");

		if ($select_varasto == 0 and $sel != "" and $kukarow['oletus_varasto'] == $varastorow['tunnus']) {
			$select_varasto = $kukarow['oletus_varasto'];
		}

		echo "<option value='{$varastorow['tunnus']}'{$sel}>{$varastorow['nimitys']}</option>";
	}

	echo "</select>";
	echo "</td></tr>";

	echo "<tr>";
	echo "<th>",t("Etsi rahtikirjoja lähdön tunnuksella"),"</th>";
	echo "<td><input type='text' name='checkbox_parent[]' value='{$lahto}' /> <input type='submit' value='",t("Etsi"),"' /></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";

	echo "<br />";

	if ($tee == 'paivita') {

		$muutetaan_pakkausta = $siirretaan_pakkausta = false;

		foreach ($uusi_pakkaus as $sscc => $pak) {
			if ($pak != "") {
				if (strpos($pak, '####') !== FALSE) $siirretaan_pakkausta = true;
				else $muutetaan_pakkausta = true;
			}
		}

		if ($muutetaan_pakkausta and $siirretaan_pakkausta) {
			echo "<font class='error'>",t("Et voi muuttaa pakkauksia ja siirtää pakkauksia samaan aikaan"),".</font><br /><br />";
			$tee = 'muuta';

			reset($uusi_pakkaus);
			unset($sscc, $pak);
		}
	}

	if ($tee == 'paivita') {

		foreach ($uusi_pakkaus as $sscc => $pak) {

			if ($pak != "") {

				$toisen_sscc = "";

				if (strpos($pak, '####') !== FALSE) list($pak, $toisen_sscc) = explode('####', $pak);

				if ($pak == 'muu_kolli') $pak = 999;

				if ($toisen_sscc == "") {
					$uusi_sscc = $sscc;
				}
				else {
					$uusi_sscc = $toisen_sscc;
				}

				if ($pak != 999) {
					$query = "	SELECT *
								FROM pakkaus
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus = '{$pak}'";
					$pak_res = pupe_query($query);
					$pak_row = mysql_fetch_assoc($pak_res);
				}
				else {
					$pak_row['pakkaus'] = t("MUU KOLLI");
					$pak_row['pakkauskuvaus'] = "";
				}

				if ($toisen_sscc != "") {
					$query = "	SELECT otunnus
								FROM kerayserat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND (sscc = '{$toisen_sscc}' or sscc_ulkoinen = '{$toisen_sscc}')";
					$toisen_res = pupe_query($query);
					$toisen_row = mysql_fetch_assoc($toisen_res);
				}

				$query = "	SELECT DISTINCT otunnus
							FROM kerayserat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND (sscc = '{$sscc}' or sscc_ulkoinen = '{$sscc}')";
				$check_res = pupe_query($query);

				$rahtikirjarivit = $otunnukset = array();
				$kilot = $kuutiot = $lavametrit = 0;

				while ($check_row = mysql_fetch_assoc($check_res)) {

					$otunnukset[] = $check_row['otunnus'];

					$query = "	SELECT *
								FROM rahtikirjat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND otsikkonro = '{$check_row['otunnus']}'";
					$rahtikirjat_res = pupe_query($query);

					while ($rahtikirjat_row = mysql_fetch_assoc($rahtikirjat_res)) {
						$rahtikirjarivit[$rahtikirjat_row['otsikkonro']] = $rahtikirjat_row;

						$kilot += $rahtikirjat_row['kilot'];
						$kuutiot += $rahtikirjat_row['kuutiot'];
						$lavametrit += $rahtikirjat_row['lavametri'];

						if ($toisen_sscc == "") {
							$query = "	DELETE FROM rahtikirjat
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$rahtikirjat_row['tunnus']}'";
							$delres = pupe_query($query);
						}
						else {
							$query = "	UPDATE rahtikirjat SET
										kilot = 0,
										kollit = 0,
										kuutiot = 0,
										lavametri = 0,
										pakkaus = '{$pak_row['pakkaus']}',
										pakkauskuvaus = '{$pak_row['pakkauskuvaus']}'
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$rahtikirjat_row['tunnus']}'";
							$updres = pupe_query($query);
						}
					}
				}

				$query = "	UPDATE kerayserat SET
							sscc = '{$uusi_sscc}',
							pakkaus = '{$pak}',
							muuttaja = '{$kukarow['kuka']}',
							muutospvm = now()
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND (sscc = '{$sscc}' or sscc_ulkoinen = '{$sscc}')";
				$updres = pupe_query($query);

				$rahtikirjanro = "";

				foreach ($otunnukset as $otun) {
					if ($rahtikirjanro == "") $rahtikirjanro = $rahtikirjarivit[$otun]['otsikkonro'];

					if ($toisen_sscc != "") {

						if ($kilot == 0 and $kuutiot == 0 and $lavametrit == 0) continue;

						// Tarkistetaan aluksi montako riviä kyseisellä rahtikirjalla on
						// Jos rivejä on > 1, päivitetään vaan ensimmäistä löytyvää riviä
						$query = "	SELECT tunnus
									FROM rahtikirjat
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND otsikkonro = '{$toisen_row['otunnus']}'
									AND pakkaus = '{$pak_row['pakkaus']}'
									AND pakkauskuvaus = '{$pak_row['pakkauskuvaus']}'";
						$row_count_chk_res = pupe_query($query);

						if (mysql_num_rows($row_count_chk_res) > 1) {

							$row_count_chk_row = mysql_fetch_assoc($row_count_chk_res);

							$query = "	UPDATE rahtikirjat SET
										kilot = kilot + {$kilot},
										kuutiot = kuutiot + {$kuutiot},
										lavametri = lavametri + {$lavametrit}
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND tunnus = '{$row_count_chk_row['tunnus']}'";
							$updres = pupe_query($query);
						}
						else {
							$query = "	UPDATE rahtikirjat SET
										kilot = kilot + {$kilot},
										kuutiot = kuutiot + {$kuutiot},
										lavametri = lavametri + {$lavametrit}
										WHERE yhtio = '{$kukarow['yhtio']}'
										AND otsikkonro = '{$toisen_row['otunnus']}'
										AND pakkaus = '{$pak_row['pakkaus']}'
										AND pakkauskuvaus = '{$pak_row['pakkauskuvaus']}'";
							$updres = pupe_query($query);
						}
					}
					else {

						$_kolli_lkm = ($kilot == 0 and $kuutiot == 0) ? 0 : 1;

						$query = "	INSERT INTO rahtikirjat SET
									kilot = '{$kilot}',
									kollit = '{$_kolli_lkm}',
									kuutiot = '{$kuutiot}',
									lavametri = '{$lavametrit}',
									merahti = '{$rahtikirjarivit[$otun]['merahti']}',
									pakkaus = '{$pak_row['pakkaus']}',
									pakkauskuvaus = '{$pak_row['pakkauskuvaus']}',
									pakkauskuvaustark = '{$rahtikirjarivit[$otun]['pakkauskuvaustark']}',
									poikkeava = '{$rahtikirjarivit[$otun]['poikkeava']}',
									rahtisopimus = '{$rahtikirjarivit[$otun]['rahtisopimus']}',
									otsikkonro = '{$otun}',
									pakkaustieto_tunnukset = '{$rahtikirjarivit[$otun]['pakkaustieto_tunnukset']}',
									toimitustapa = '{$rahtikirjarivit[$otun]['toimitustapa']}',
									viitelah = '{$rahtikirjarivit[$otun]['viitelah']}',
									viitevas = '{$rahtikirjarivit[$otun]['viitevas']}',
									viesti = '{$rahtikirjarivit[$otun]['viesti']}',
									tulostuspaikka = '{$rahtikirjarivit[$otun]['tulostuspaikka']}',
									tulostustapa = '{$rahtikirjarivit[$otun]['tulostustapa']}',
									tulostettu = '{$rahtikirjarivit[$otun]['tulostettu']}',
									rahtikirjanro = '{$rahtikirjanro}',
									sscc_ulkoinen = '{$rahtikirjarivit[$otun]['sscc_ulkoinen']}',
									tyhjanrahtikirjan_otsikkotiedot = '{$rahtikirjarivit[$otun]['tyhjanrahtikirjan_otsikkotiedot']}',
									yhtio = '{$kukarow['yhtio']}'";
						$insres = pupe_query($query);
					}

					$kilot = 0;
					$kuutiot = 0;
					$lavametrit = 0;
				}
			}
		}

		$tee = '';
	}

	if ($tee == 'muuta') {

		$query = "	SELECT DISTINCT IF(kerayserat.sscc_ulkoinen != 0, kerayserat.sscc_ulkoinen, kerayserat.sscc) sscc
					FROM kerayserat
					WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
					AND kerayserat.otunnus IN ({$otunnukset})
					GROUP BY 1
					ORDER BY 1";
		$keraysera_res = pupe_query($query);

		$lopetus = "{$palvelin2}tilauskasittely/lahtojen_hallinta.php////select_varasto={$select_varasto}//tee=";

		echo "<form method='post' action='?tee=paivita&select_varasto={$select_varasto}&checkbox_parent[]={$checkbox_parent[0]}&otunnukset={$otunnukset}&lopetus={$lopetus}'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>",t("Kolli"),"</th>";
		echo "<th>",t("SSCC"),"</th>";
		echo "<th>",t("Uusi pakkaus"),"</th>";
		echo "</tr>";

		while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {

			$query = "	SELECT IFNULL(pakkaus.pakkaus, 'MUU KOLLI') pakkaus,
						GROUP_CONCAT(DISTINCT kerayserat.otunnus) otunnus
						FROM kerayserat
						LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
						WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
						AND kerayserat.otunnus IN ({$otunnukset})
						AND kerayserat.sscc = '{$keraysera_row['sscc']}'
						GROUP BY 1";
			$info_res = pupe_query($query);
			$info_row = mysql_fetch_assoc($info_res);

			echo "<tr>";
			echo "<td>$info_row[pakkaus]</td>";
			echo "<td>$keraysera_row[sscc]</td>";

			echo "<td><select name='uusi_pakkaus[{$keraysera_row['sscc']}]'>";
			echo "<option value=''>",t("Valitse"),"</option>";

			echo "<optgroup label='",t("Keräyserässä"),"'>";

			$query = "	SELECT CONCAT(IFNULL(pakkaus.pakkaus, 'Yksin keräilyalustalle'), ' ', IF(kerayserat.sscc_ulkoinen != 0, kerayserat.sscc_ulkoinen, kerayserat.sscc)) pak,
						IFNULL(kerayserat.pakkaus, 'muu_kolli') pakkaus,
						IF(kerayserat.sscc_ulkoinen != 0, kerayserat.sscc_ulkoinen, kerayserat.sscc) sscc
						FROM kerayserat
						LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
						WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
						AND kerayserat.otunnus IN ({$otunnukset})
						AND kerayserat.sscc != '{$keraysera_row['sscc']}'
						AND kerayserat.sscc_ulkoinen != '{$keraysera_row['sscc']}'
						GROUP BY 1,2,3
						ORDER BY sscc";
			$pak_res = pupe_query($query);

			while ($pak_row = mysql_fetch_assoc($pak_res)) {
				echo "<option value='{$pak_row['pakkaus']}####{$pak_row['sscc']}'>{$pak_row['pak']}</option>";
			}

			echo "</optgroup>";

			echo "<optgroup label='",t("Pakkaukset"),"'>";
			echo "<option value='muu_kolli'>",t("Yksin keräilyalustalle"),"</option>";

			$query = "	SELECT *
						FROM pakkaus
						WHERE yhtio = '{$kukarow['yhtio']}'
						ORDER BY pakkaus, pakkauskuvaus";
			$pak_res = pupe_query($query);

			while ($pak_row = mysql_fetch_assoc($pak_res)) {
				echo "<option value='{$pak_row['tunnus']}'>{$pak_row['pakkaus']} {$pak_row['pakkauskuvaus']}</option>";
			}

			echo "</optgroup>";
			echo "</select></td>";

			echo "</tr>";
		}

		echo "<tr><th colspan='5'><input type='submit' value='",t("Tee"),"' /></th></tr>";

		echo "</table>";
		echo "</form>";
	}

	if ($tee == '' and isset($checkbox_parent) and count($checkbox_parent) == 1) {

		$query = "	SELECT toimitustapa.*
					FROM lahdot
					JOIN toimitustapa ON (toimitustapa.yhtio = lahdot.yhtio AND toimitustapa.tunnus = lahdot.liitostunnus)
					WHERE lahdot.yhtio = '{$kukarow['yhtio']}'
					AND lahdot.tunnus = '{$lahto}'";
		$toitares = pupe_query($query);
		$toitarow = mysql_fetch_assoc($toitares);

		$toimitustapa = $toitarow['selite'];

		// haetaan kaikki distinct rahtikirjat..
		$query = "	SELECT DISTINCT lasku.ytunnus, lasku.toim_maa, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_ovttunnus, lasku.toim_postino, lasku.toim_postitp,
					rahtikirjat.merahti, rahtikirjat.rahtisopimus, GROUP_CONCAT(DISTINCT lasku.tunnus) ltunnukset
					FROM rahtikirjat
					JOIN lasku on (rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio and lasku.tila in ('L','G') and lasku.alatila = 'B' AND lasku.toimitustavan_lahto = '{$lahto}')
					LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
					LEFT JOIN maksuehto on lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
					LEFT JOIN rahtisopimukset on lasku.ytunnus = rahtisopimukset.ytunnus and rahtikirjat.toimitustapa = rahtisopimukset.toimitustapa and rahtikirjat.rahtisopimus = rahtisopimukset.rahtisopimus
					WHERE rahtikirjat.yhtio	= '$kukarow[yhtio]'
					and rahtikirjat.tulostettu	= '0000-00-00 00:00:00'
					and rahtikirjat.toimitustapa	= '{$toimitustapa}'
					and rahtikirjat.tulostuspaikka	= '{$select_varasto}'
					GROUP BY lasku.ytunnus, lasku.toim_maa, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_ovttunnus, lasku.toim_postino, lasku.toim_postitp,
					rahtikirjat.merahti, rahtikirjat.rahtisopimus
					ORDER BY lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, rahtikirjat.merahti, rahtikirjat.rahtisopimus, lasku.tunnus";
		$rakir_res = pupe_query($query);

		if (mysql_num_rows($rakir_res) == 0) {
			echo "<font class='message'>",t("Yhtään tulostettavaa rahtikirjaa ei löytynyt"),".</font><br /><br />";
		}
		else {

			echo "<table><tr><td class='back'>";

			while ($rakir_row = mysql_fetch_assoc($rakir_res)) {

				// Katsotaan onko tämä koontikuljetus
				if ($toitarow["tulostustapa"] == "L" or $toitarow["tulostustapa"] == "K") {
					// Monen asiakkaan rahtikirjat tulostuu aina samalle paperille
					$asiakaslisa = " ";

					//Toimitusosoitteeksi halutaan tässä tapauksessa toimitustavan takaa löytyvät
					$rakir_row["toim_maa"]		= $toitarow["toim_maa"];
					$rakir_row["toim_nimi"]		= $toitarow["toim_nimi"];
					$rakir_row["toim_nimitark"]	= $toitarow["toim_nimitark"];
					$rakir_row["toim_osoite"]	= $toitarow["toim_osoite"];
					$rakir_row["toim_postino"]	= $toitarow["toim_postino"];
					$rakir_row["toim_postitp"]	= $toitarow["toim_postitp"];

				}
				else {
					// Normaalissa keississä ainoastaan saman toimitusasiakkaan kirjat menee samalle paperille
					$asiakaslisa = "and lasku.ytunnus			= '$rakir_row[ytunnus]'
									and lasku.toim_maa			= '$rakir_row[toim_maa]'
									and lasku.toim_nimi			= '$rakir_row[toim_nimi]'
									and lasku.toim_nimitark		= '$rakir_row[toim_nimitark]'
									and lasku.toim_osoite		= '$rakir_row[toim_osoite]'
									and lasku.toim_ovttunnus	= '$rakir_row[toim_ovttunnus]'
									and lasku.toim_postino		= '$rakir_row[toim_postino]'
									and lasku.toim_postitp		= '$rakir_row[toim_postitp]' ";
				}

				// haetaan tälle rahtikirjalle kuuluvat tunnukset
				$query = "	SELECT GROUP_CONCAT(DISTINCT lasku.tunnus) otunnus,
							GROUP_CONCAT(DISTINCT rahtikirjat.tunnus) rtunnus,
							GROUP_CONCAT(DISTINCT rahtikirjat.rahtikirjanro SEPARATOR ', ') rahtikirjanrot,
							SUM(kollit) kollit
							FROM rahtikirjat
							JOIN lasku on (rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio and lasku.tila in ('L','G') and lasku.alatila = 'B' AND lasku.tunnus IN ({$rakir_row['ltunnukset']}))
							LEFT JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus)
							WHERE rahtikirjat.yhtio	= '{$kukarow[yhtio]}'
							and rahtikirjat.tulostettu	= '0000-00-00 00:00:00'
							and rahtikirjat.toimitustapa	= '$toimitustapa'
							and rahtikirjat.tulostuspaikka	= '$select_varasto'
							$asiakaslisa
							and rahtikirjat.merahti			= '$rakir_row[merahti]'
							and rahtikirjat.rahtisopimus	= '$rakir_row[rahtisopimus]'
							HAVING kollit != 0
							ORDER BY lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, rahtikirjat.merahti, rahtikirjat.rahtisopimus, lasku.tunnus";
				$res = pupe_query($query);

				if (mysql_num_rows($res) == 0) continue;

				echo "<form method='post' action=''>";

				echo "<input type='hidden' name='tee' value='muuta' />";
				echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
				echo "<input type='hidden' name='checkbox_parent[]' value='{$lahto}' />";

				if ($lopetus != "") {
					$lopetus_url = $lopetus."/SPLIT/{$palvelin2}tilauskasittely/muokkaa_kolleja.php////select_varasto={$select_varasto}//tee=//checkbox_parent[]={$lahto}";
				}
				else {
					$lopetus_url = "{$palvelin2}tilauskasittely/muokkaa_kolleja.php////select_varasto={$select_varasto}//tee=//checkbox_parent[]={$lahto}";
				}

				echo "<input type='hidden' name='lopetus' value='{$lopetus_url}' />";

				echo "<table style='width:100%'>";
				echo "<tr>";
				echo "<th>",t("Asiakas"),"</th>";
				echo "<th>",t("Osoite"),"</th>";
				echo "<th>",t("Rahtikirjanumerot"),"</th>";
				echo "<th>",t("Rahdinmaksaja"),"</th>";
				echo "<th>",t("Pakkauksien lukumäärä"),"</th>";
				echo "<th>",t("Paino"),"</th>";
				echo "<th>",t("Tilavuus"),"</th>";
				echo "</tr>";

				$otunnukset = $tunnukset = "";

				while ($rivi = mysql_fetch_assoc($res)) {

					// otetaan kaikki otsikkonumerot ja rahtikirjanumerot talteen... tarvitaan myöhemmin hauissa
					$otunnukset = $rivi['otunnus'];
					$tunnukset = $rivi['rtunnus'];

					echo "<tr>";
					echo "<td>$rakir_row[toim_nimi] $rakir_row[toim_nimitark]</td>";
					echo "<td>$rakir_row[toim_osoite] $rakir_row[toim_postino] $rakir_row[toim_postitp]</td>";

					if ($rakir_row['merahti'] == 'K') {
						$rahdinmaksaja = t("Lähettäjä");
					}
					else {
						$rahdinmaksaja = t("Vastaanottaja"); //tämä on defaultti
					}

					echo "<td>{$rivi['rahtikirjanrot']}</td>";
					echo "<td>{$rahdinmaksaja}</td>";

					$query = "	SELECT SUM(kollit) kollit,
								ROUND(SUM(kilot), 2) kilot,
								SUM(kuutiot) kuutiot
								FROM rahtikirjat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND tunnus IN ({$rivi['rtunnus']})";
					$summaukset_res = pupe_query($query);
					$summaukset_row = mysql_fetch_assoc($summaukset_res);

					echo "<td align='right'>{$summaukset_row['kollit']}</td>";
					echo "<td align='right'>{$summaukset_row['kilot']}</td>";
					echo "<td align='right'>{$summaukset_row['kuutiot']}</td>";

					echo "</tr>";
				}

				echo "<tr>";
				echo "<th colspan='7'>";
				echo "<input type='submit' value='",t("Muuta"),"' />";
				echo "<input type='hidden' name='tunnukset' value='{$tunnukset}' />";
				echo "<input type='hidden' name='otunnukset' value='{$otunnukset}' />";
				echo "</th>";
				echo "</tr>";

				echo "</table>";
				echo "</form>";
				echo "<br />";
			}

			echo "</td></tr></table>";
		}
	}

	require ("inc/footer.inc");
