<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>",t("Varastoonviedyt rivit"),"</font><hr>";

	//Käyttöliittymä
	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	if (!isset($tee)) $tee = "";
	if (!isset($tapa)) $tapa = '';

	if (!isset($keraajanro_pakollinen)) $keraajanro_pakollinen = "";

	if (!isset($MONTH_ARRAY)) $MONTH_ARRAY = array(1=> t('Tammikuu'),t('Helmikuu'),t('Maaliskuu'),t('Huhtikuu'),t('Toukokuu'),t('Kesäkuu'),t('Heinäkuu'),t('Elokuu'),t('Syyskuu'),t('Lokakuu'),t('Marraskuu'),t('Joulukuu'));

	echo "	<script type='text/javascript'>

				$(function() {

					$('.kayttajat, .useri').on('click', function() {

						if ($(this).hasClass('kayttajat')) {
							$('.lapsi').hide();
						}

						$('.'+$(this).attr('id')).toggle();
					});
				});

			</script>";

	echo "<form method='post'>";
	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<br>";
	echo "<table>";

	$sel = array($tapa => " selected") + array("pp" => "", "vk" => "", "kk" => "", "vieja" => "");

	echo "<tr>";
	echo "<th>",t("Valitse tapa"),"</th>";
	echo "<td colspan='3'>";
	echo "<select name='tapa'>";
	echo "<option value='pp'{$sel['pp']}>",t("Päivittäin"),"</option>";
	echo "<option value='vk'{$sel['vk']}>",t("Viikottain"),"</option>";
	echo "<option value='kk'{$sel['kk']}>",t("Kuukausittain"),"</option>";
	echo "<option value='vieja'{$sel['vieja']}>",t("Viejittäin"),"</option>";
	echo "</select></td>";
	echo "</tr>";

	$chk = $keraajanro_pakollinen != '' ? "checked" : "";

	echo "<tr>";
	echo "<th>",t("Kerääjänumero pakollinen"),"</th>";
	echo "<td colspan='3'><input type='checkbox' name='keraajanro_pakollinen' {$chk} /></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>",t("Syötä päivämäärä (pp-kk-vvvv)"),"</th>";
	echo "<td><input type='text' name='ppa' value='{$ppa}' size='3'></td>";
	echo "<td><input type='text' name='kka' value='{$kka}' size='3'></td>";
	echo "<td><input type='text' name='vva' value='{$vva}' size='5'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>",t("Syötä loppupäivämäärä (pp-kk-vvvv)"),"</th>";
	echo "<td><input type='text' name='ppl' value='{$ppl}' size='3'></td>";
	echo "<td><input type='text' name='kkl' value='{$kkl}' size='3'></td>";
	echo "<td><input type='text' name='vvl' value='{$vvl}' size='5'></td>";
	echo "</tr>";
	echo "</table>";

	echo "<br />";
	echo "<input type='submit' value='",t("Aja raportti"),"'>";
	echo "</form>";

	echo "<br /><br />";

	if ($tee != "") {

		if (!checkdate($kka, $ppa, $vva)) {
			echo "<font class='error'>",t("Virheellinen alkupäivämäärä"),"!</font><br>";
			$tee = "";
		}

		if (!checkdate($kkl, $ppl, $vvl)) {
			echo "<font class='error'>",t("Virheellinen loppupäivämäärä"),"!</font><br>";
			$tee = "";
		}
	}

	if ($tee != '') {

		$keraajanrolisa = $keraajanro_pakollinen != '' ? "HAVING (kuka.keraajanro IS NOT NULL and kuka.keraajanro != 0)" : "";
		$keraajanrolisaselect = $keraajanro_pakollinen != '' ? "kuka.keraajanro," : "";

		if ($tapa == 'vieja') {

			$groupby = $keraajanro_pakollinen != '' ? "GROUP BY 1,2,3,4" : "GROUP BY 1,2,3";

			$query = "	SELECT IF(kuka.nimi IS NULL,tapahtuma.laatija, kuka.nimi) kuka,
						tilausrivi.uusiotunnus keikka,
						LEFT(tapahtuma.laadittu,10) laadittu,
						{$keraajanrolisaselect}
						SUM(IF(tl.tunnus IS NOT NULL, 0, tapahtuma.kpl)) yksikot,
						COUNT(IF(tl.tunnus IS NOT NULL, NULL, tapahtuma.tunnus)) rivit
						FROM tapahtuma
						JOIN tilausrivi ON (tilausrivi.yhtio = tapahtuma.yhtio AND tilausrivi.tunnus = tapahtuma.rivitunnus)
						LEFT JOIN tilausrivin_lisatiedot AS tl ON (tl.yhtio = tilausrivi.yhtio AND tl.tilausrivitunnus = tilausrivi.tunnus AND (tl.suoraan_laskutukseen != '' OR tl.ohita_kerays != ''))
						LEFT JOIN kuka ON (kuka.yhtio = tapahtuma.yhtio AND kuka.kuka = tapahtuma.laatija)
						WHERE tapahtuma.yhtio = '{$kukarow['yhtio']}'
						AND tapahtuma.laji = 'tulo'
						AND tapahtuma.laadittu >= '{$vva}-{$kka}-{$ppa} 00:00:00'
						AND tapahtuma.laadittu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
						{$groupby}
						{$keraajanrolisa}
						ORDER BY kuka.nimi, LEFT(tapahtuma.laadittu,10)";
			$result = pupe_query($query);

			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Nimi"),"</th>";
			echo "<th>",t("Saapuminen"),"</th>";
			echo "<th>",t("Toimittaja"),"</th>";
			echo "<th>",t("Yksiköt"),"</th>";
			echo "<th>",t("Rivit"),"</th>";
			echo "<th nowrap>",t("Viety varastoon"),"</th>";
			echo "</tr>";

			$lask = 0;
			$edkeraaja = '';
	        $ysummayht	= 0;
			$rsummayht	= 0;
			$ysumma	= 0;
			$rsumma	= 0;

			while ($row = mysql_fetch_assoc($result)) {

				if ($edkeraaja != $row["kuka"] and $lask > 0 and $ysumma != 0) {

					echo "<tr>";
					echo "<th colspan='3'>",t("Yhteensä"),":</th>";
					echo "<th style='text-align:right'>{$ysumma}</th>";
					echo "<th style='text-align:right'>{$rsumma}</th>";
					echo "<th></th>";
					echo "</tr>";

					echo "<tr><td class='back'><br></td></tr>";

					echo "<tr>";
					echo "<th>",t("Nimi"),"</th>";
					echo "<th>",t("Saapuminen"),"</th>";
					echo "<th>",t("Toimittaja"),"</th>";
					echo "<th>",t("Yksiköt"),"</th>";
					echo "<th>",t("Rivit"),"</th>";
					echo "<th nowrap>",t("Viety varastoon"),"</th>";
					echo "</tr>";

					$ysumma	= 0;
					$rsumma	= 0;
				}

				$query = "	SELECT laskunro, TRIM(CONCAT(nimi, ' ', nimitark)) nimi
							FROM lasku
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$row['keikka']}'";
				$laskunro_res = pupe_query($query);
				$laskunro_row = mysql_fetch_assoc($laskunro_res);

				echo "<tr class='aktiivi'>";
				echo "<td>{$row['kuka']}</td>";
				echo "<td>{$laskunro_row['laskunro']}</td>";
				echo "<td>{$laskunro_row['nimi']}</td>";
				echo "<td align='right'>{$row['yksikot']}</td>";
				echo "<td align='right'>{$row['rivit']}</td>";
				echo "<td align='right'>",tv1dateconv($row["laadittu"]),"</td>";
				echo "</tr>";

				$ysumma	+= $row["yksikot"];
				$rsumma	+= $row["rivit"];

				// yhteensä
				$ysummayht += $row["yksikot"];
				$rsummayht += $row["rivit"];

				$lask++;
				$edkeraaja = $row["kuka"];
			}

			if ($ysumma > 0) {
				echo "<tr>";
				echo "<th colspan='3'>",t("Yhteensä"),":</th>";
				echo "<th style='text-align:right'>{$ysumma}</th>";
				echo "<th style='text-align:right'>{$rsumma}</th>";
				echo "<th></th>";
				echo "</tr>";
			}

			// Kaikki yhteensä
			echo "<tr>";
			echo "<td class='back'><br></td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th colspan='3'>",t("Kaikki yhteensä"),":</th>";
			echo "<th style='text-align:right'>{$ysummayht}</th>";
			echo "<th style='text-align:right'>{$rsummayht}</th>";
			echo "<th></th>";
			echo "</tr>";

			echo "</table>";

		}
		else {

			if ($tapa == 'vk') {
				$select = "WEEK(LEFT(tapahtuma.laadittu, 10)) pvm,";
			}
			elseif ($tapa == 'kk') {
				$select = "CONCAT(MONTH(LEFT(tapahtuma.laadittu, 10)),'#',SUBSTRING(tapahtuma.laadittu,1,4)) pvm,";
			}
			else {
				$select = "LEFT(tapahtuma.laadittu, 10) pvm,";
			}

			$query = "	SELECT {$select}
						SUM(IF(tl.tunnus IS NOT NULL, 0, tapahtuma.kpl)) yksikot,
						COUNT(IF(tl.tunnus IS NOT NULL, NULL, tapahtuma.tunnus)) rivit
						FROM tapahtuma
						JOIN tilausrivi ON (tilausrivi.yhtio = tapahtuma.yhtio AND tilausrivi.tunnus = tapahtuma.rivitunnus)
						LEFT JOIN tilausrivin_lisatiedot AS tl ON (tl.yhtio = tilausrivi.yhtio AND tl.tilausrivitunnus = tilausrivi.tunnus AND (tl.suoraan_laskutukseen != '' OR tl.ohita_kerays != ''))
						WHERE tapahtuma.yhtio = '{$kukarow['yhtio']}'
						AND tapahtuma.laadittu >= '{$vva}-{$kka}-{$ppa} 00:00:00'
						AND tapahtuma.laadittu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
						AND tapahtuma.laji = 'tulo'
						GROUP BY 1
						ORDER BY 1";
			$result = pupe_query($query);

			echo "<table>";

			echo "<tr>";
			echo "<th colspan='2'>",t("Pvm"),"</th>";
			echo "<th>",t("Kappaleet"),"</th>";
			echo "<th>",t("Rivit"),"</th>";
			echo "<th></th>";
			echo "</tr>";

			$groupby = $keraajanro_pakollinen != '' ? "GROUP BY 1,2,3" : "GROUP BY 1,2";

			while ($ressu = mysql_fetch_assoc($result)) {

				if ($tapa == 'pp') {
					echo "<tr class='kayttajat' id='{$ressu['pvm']}'>";
					echo "<td colspan='2'>",tv1dateconv($ressu['pvm']),"</td>";

					$wherelisa = "	AND tapahtuma.laadittu >= '{$ressu['pvm']} 00:00:00'
									AND tapahtuma.laadittu <= '{$ressu['pvm']} 23:59:59'";
				}
				elseif ($tapa == 'vk') {
					echo "<tr class='kayttajat' id='{$ressu['pvm']}'>";
					echo "<td colspan='2'>",t("Viikko")," {$ressu['pvm']}</td>";

					$wherelisa = "	AND WEEK(tapahtuma.laadittu) = '{$ressu['pvm']}'
									AND tapahtuma.laadittu >= '{$vva}-{$kka}-{$ppa} 00:00:00'
									AND tapahtuma.laadittu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'";
				}
				else {

					list($ressu['pvm'], $vuosi) = explode("#", $ressu['pvm']);

					echo "<tr class='kayttajat' id='{$ressu['pvm']}'>";
					echo "<td colspan='2'>",$MONTH_ARRAY[$ressu['pvm']]," {$vuosi}</td>";

					$wherelisa = "	AND MONTH(tapahtuma.laadittu) = '{$ressu['pvm']}'
									AND tapahtuma.laadittu >= '{$vva}-{$kka}-{$ppa} 00:00:00'
									AND tapahtuma.laadittu <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'";
				}

				echo "<td align='right'>{$ressu['yksikot']}</td>";
				echo "<td align='right'>{$ressu['rivit']}</td>";
				echo "<td><img title='",t("Käyttäjittäin"),"' alt='",t("Käyttäjittäin"),"' src='{$palvelin2}pics/lullacons/go-down.png' /></td>";
				echo "</tr>";



				$query = "	SELECT IF(kuka.nimi IS NULL,tapahtuma.laatija, kuka.nimi) kuka,
							IF(kuka.kuka IS NULL,tapahtuma.laatija, kuka.kuka) kuka_tunnus,
							{$keraajanrolisaselect}
							SUM(IF(tl.tunnus IS NOT NULL, 0, tapahtuma.kpl)) yksikot,
							COUNT(IF(tl.tunnus IS NOT NULL, NULL, tapahtuma.tunnus)) rivit
							FROM tapahtuma
							JOIN tilausrivi ON (tilausrivi.yhtio = tapahtuma.yhtio AND tilausrivi.tunnus = tapahtuma.rivitunnus)
							LEFT JOIN tilausrivin_lisatiedot AS tl ON (tl.yhtio = tilausrivi.yhtio AND tl.tilausrivitunnus = tilausrivi.tunnus AND (tl.suoraan_laskutukseen != '' OR tl.ohita_kerays != ''))
							LEFT JOIN kuka ON (kuka.yhtio = tapahtuma.yhtio AND kuka.kuka = tapahtuma.laatija)
							WHERE tapahtuma.yhtio = '{$kukarow['yhtio']}'
							AND tapahtuma.laji = 'tulo'
							{$wherelisa}
							{$groupby}
							{$keraajanrolisa}
							ORDER BY kuka.nimi, LEFT(tapahtuma.laadittu,10)";
				$kayttajittain_result = pupe_query($query);

				echo "<tr class='{$ressu['pvm']}' style='display:none;'>";
				echo "<th colspan='2'>",t("Nimi"),"</th>";
				echo "<th>",t("Yksiköt"),"</th>";
				echo "<th>",t("Rivit"),"</th>";
				echo "<th></th>";
				echo "</tr>";

				while ($kayttajittain_row = mysql_fetch_assoc($kayttajittain_result)) {

					echo "<tr class='spec useri {$ressu['pvm']}' id='{$ressu['pvm']}{$kayttajittain_row['kuka_tunnus']}' style='display:none;'>";
					echo "<td colspan='2'>{$kayttajittain_row['kuka']}</td>";
					echo "<td align='right'>{$kayttajittain_row['yksikot']}</td>";
					echo "<td align='right'>{$kayttajittain_row['rivit']}</td>";
					echo "<td><img title='",t("Käyttäjä"),"' alt='",t("Käyttäjä"),"' src='{$palvelin2}pics/lullacons/go-down.png' /></td>";
					echo "</tr>";

					$query = "	SELECT tilausrivi.uusiotunnus keikka,
								LEFT(tapahtuma.laadittu,10) laadittu,
								{$keraajanrolisaselect}
								SUM(IF(tl.tunnus IS NOT NULL, 0, tapahtuma.kpl)) yksikot,
								COUNT(IF(tl.tunnus IS NOT NULL, NULL, tapahtuma.tunnus)) rivit
								FROM tapahtuma
								JOIN tilausrivi ON (tilausrivi.yhtio = tapahtuma.yhtio AND tilausrivi.tunnus = tapahtuma.rivitunnus)
								LEFT JOIN tilausrivin_lisatiedot AS tl ON (tl.yhtio = tilausrivi.yhtio AND tl.tilausrivitunnus = tilausrivi.tunnus AND (tl.suoraan_laskutukseen != '' OR tl.ohita_kerays != ''))
								LEFT JOIN kuka ON (kuka.yhtio = tapahtuma.yhtio AND kuka.kuka = tapahtuma.laatija)
								WHERE tapahtuma.yhtio = '{$kukarow['yhtio']}'
								AND tapahtuma.laji = 'tulo'
								{$wherelisa}
								AND tapahtuma.laatija = '{$kayttajittain_row['kuka_tunnus']}'
								{$groupby}
								{$keraajanrolisa}
								ORDER BY kuka.nimi, LEFT(tapahtuma.laadittu,10)";
					$kayttaja_result = pupe_query($query);

					echo "<tr class='lapsi {$ressu['pvm']}{$kayttajittain_row['kuka_tunnus']}' style='display:none;'>";
					echo "<th>",t("Toimittaja"),"</th>";
					echo "<th>",t("Saapuminen"),"</th>";
					echo "<th>",t("Yksiköt"),"</th>";
					echo "<th>",t("Rivit"),"</th>";
					echo "<th nowrap>",t("Viety varastoon"),"</th>";
					echo "</tr>";

					while ($kayttaja_row = mysql_fetch_assoc($kayttaja_result)) {

						$query = "	SELECT laskunro, TRIM(CONCAT(nimi, ' ', nimitark)) nimi
									FROM lasku
									WHERE yhtio = '{$kukarow['yhtio']}'
									AND tunnus = '{$kayttaja_row['keikka']}'";
						$laskunro_res = pupe_query($query);
						$laskunro_row = mysql_fetch_assoc($laskunro_res);

						echo "<tr class='tumma lapsi {$ressu['pvm']}{$kayttajittain_row['kuka_tunnus']}' style='display:none;'>";
						echo "<td>{$laskunro_row['nimi']}</td>";
						echo "<td>{$laskunro_row['laskunro']}</td>";
						echo "<td align='right'>{$kayttaja_row['yksikot']}</td>";
						echo "<td align='right'>{$kayttaja_row['rivit']}</td>";
						echo "<td align='right'>",tv1dateconv($kayttaja_row["laadittu"]),"</td>";
						echo "</tr>";

					}
				}
			}

			echo "</table>";
		}
	}

	require ("inc/footer.inc");
