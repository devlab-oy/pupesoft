<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>",t("Muokkaa kolleja"),"</font><hr>";

	if (!isset($tee)) $tee = "";

	if ($tee == 'muuta') {

		$query = "	SELECT kerayserat.nro,
					kerayserat.otunnus,
					kerayserat.pakkausnro,
					IFNULL(pakkaus.pakkaus, 'MUU KOLLI') pakkaus,
					IF(kerayserat.sscc_ulkoinen != 0, kerayserat.sscc_ulkoinen, kerayserat.sscc) sscc,
					lasku.ohjausmerkki
					FROM kerayserat
					JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus)
					LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
					WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
					AND kerayserat.otunnus IN ({$otunnukset})
					GROUP BY 1,2,3,4,5,6";
		$keraysera_res = pupe_query($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>Kolli</th>";
		echo "<th>SSCC</th>";
		echo "<th>Ohjausmerkki</th>";
		echo "</tr>";

		while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {

			echo "<tr>";
			echo "<td>$keraysera_row[pakkaus]</td>";
			echo "<td>$keraysera_row[sscc]</td>";
			echo "<td>$keraysera_row[ohjausmerkki]</td>";
			echo "</tr>";
		}

		echo "</table>";

	}

	if ($tee == '' and isset($checkbox_parent) and is_array($checkbox_parent) and count($checkbox_parent) == 1) {

		$lahto = (int) $checkbox_parent[0];

		$query = "	SELECT GROUP_CONCAT(tunnus) AS tunnukset
					FROM lasku
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tila IN ('L','G')
					AND alatila = 'B'
					AND toimitustavan_lahto = '{$lahto}'";
		$tunnus_res = pupe_query($query);
		$tunnus_row = mysql_fetch_assoc($tunnus_res);

		if ($tunnus_row['tunnukset'] == '') {
			echo "<font class='message'>",t("Yhtään kerättyä tilausta ei löytynyt"),".</font><br><br>";
			require ("inc/footer.inc");
			exit;
		}

		$ltunnukset_lisa = "and lasku.tunnus in ({$tunnus_row['tunnukset']})";

		$query = "	SELECT toimitustapa.*
					FROM lahdot
					JOIN toimitustapa ON (toimitustapa.yhtio = lahdot.yhtio AND toimitustapa.tunnus = lahdot.liitostunnus)
					WHERE lahdot.yhtio = '{$kukarow['yhtio']}'
					AND lahdot.tunnus = '{$lahto}'";
		$toitares = pupe_query($query);
		$toitarow = mysql_fetch_assoc($toitares);

		$toimitustapa = $toitarow['selite'];

		// haetaan kaikki distinct rahtikirjat..
		$query = "	SELECT distinct lasku.ytunnus, lasku.toim_maa, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_ovttunnus, lasku.toim_postino, lasku.toim_postitp,
					lasku.maa, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.ovttunnus, lasku.postino, lasku.postitp,
					rahtikirjat.merahti, rahtikirjat.rahtisopimus, if(maksuehto.jv is null,'',maksuehto.jv) jv, lasku.alv, lasku.vienti, rahtisopimukset.muumaksaja,
					asiakas.toimitusvahvistus, if(asiakas.gsm != '', asiakas.gsm, if(asiakas.tyopuhelin != '', asiakas.tyopuhelin, if(asiakas.puhelin != '', asiakas.puhelin, ''))) puhelin
					FROM rahtikirjat
					JOIN lasku on (rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio and lasku.tila in ('L','G') and lasku.alatila = 'B' {$ltunnukset_lisa})
					LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
					LEFT JOIN maksuehto on lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
					LEFT JOIN rahtisopimukset on lasku.ytunnus = rahtisopimukset.ytunnus and rahtikirjat.toimitustapa = rahtisopimukset.toimitustapa and rahtikirjat.rahtisopimus = rahtisopimukset.rahtisopimus
					WHERE rahtikirjat.yhtio	= '$kukarow[yhtio]'
					and rahtikirjat.tulostettu	= '0000-00-00 00:00:00'
					and rahtikirjat.toimitustapa	= '{$toimitustapa}'
					and rahtikirjat.tulostuspaikka	= '{$select_varasto}'
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

				if ($rakir_row['jv'] != '') {
					$jvehto = "and maksuehto.jv != ''";
				}
				else {
					$jvehto = "and maksuehto.jv = ''";
				}

				// haetaan tälle rahtikirjalle kuuluvat tunnukset
				$query = "	SELECT GROUP_CONCAT(lasku.tunnus) otunnus, GROUP_CONCAT(rahtikirjat.tunnus) rtunnus
							FROM rahtikirjat
							JOIN lasku on (rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio and lasku.tila in ('L','G') and lasku.alatila = 'B' {$ltunnukset_lisa})
							LEFT JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus {$jvehto})
							WHERE rahtikirjat.yhtio	= '{$kukarow[yhtio]}'
							and rahtikirjat.tulostettu	= '0000-00-00 00:00:00'
							and rahtikirjat.toimitustapa	= '$toimitustapa'
							and rahtikirjat.tulostuspaikka	= '$select_varasto'
							$asiakaslisa
							and rahtikirjat.merahti			= '$rakir_row[merahti]'
							and rahtikirjat.rahtisopimus	= '$rakir_row[rahtisopimus]'
							ORDER BY lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, rahtikirjat.merahti, rahtikirjat.rahtisopimus, lasku.tunnus";
				$res = pupe_query($query);

				echo "<form method='post' action=''>";

				echo "<input type='hidden' name='tee' value='muuta' />";
				echo "<input type='hidden' name='select_varasto' value='{$select_varasto}' />";
				echo "<input type='hidden' name='checkbox_parent[]' value='{$lahto}' />";

				$lopetus_url = $lopetus."/SPLIT/{$palvelin2}tilauskasittely/muokkaa_kolleja.php////select_varasto={$select_varasto}//tee=//checkbox_parent[]={$lahto}";

				echo "<input type='hidden' name='lopetus' value='{$lopetus_url}' />";

				echo "<table style='width:100%'>";
				echo "<tr>";
				echo "<th>Asiakas</th>";
				echo "<th>Osoite</th>";
				echo "<th>Kollilkm</th>";
				echo "<th>Paino</th>";
				echo "<th>Tilavuus</th>";
				echo "</tr>";

				$otunnukset = $tunnukset = "";

				while ($rivi = mysql_fetch_assoc($res)) {

					// otetaan kaikki otsikkonumerot ja rahtikirjanumerot talteen... tarvitaan myöhemmin hauissa
					$otunnukset = $rivi['otunnus'];
					$tunnukset = $rivi['rtunnus'];

					echo "<tr>";
					echo "<td>$rakir_row[toim_nimi] $rakir_row[toim_nimitark]</td>";
					echo "<td>$rakir_row[toim_osoite] $rakir_row[toim_postino] $rakir_row[toim_postitp]</td>";

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
				echo "<th colspan='6'>";
				echo "<input type='submit' value='Muuta' />";
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
