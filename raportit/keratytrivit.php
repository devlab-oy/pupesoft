<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "	<script type='text/javascript'>

				$(function() {

					$('.kayttajittain, .nayta_keraajittain, .nayta_keraaja').on('click', function() {
						$('.'+$(this).attr('id')).toggle();

						if ($(this).hasClass('nayta_keraaja') || $(this).hasClass('nayta_keraajittain')) {

							$(this).toggleClass('tumma');

							if ($('.'+$(this).attr('id')).is(':visible')) {
								$('#arrowi_'+$(this).attr('id')).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-down.png');
							}
							else {

								if ($(this).hasClass('nayta_keraajittain')) {
									$('.child').hide().removeClass('tumma');
									$('img[id^=\"arrowi_\"]').attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-right.png');
								}

								$('#arrowi_'+$(this).attr('id')).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-right.png');
							}
						}

					});

				});

			</script>";

	echo "<font class='head'>",t("Kerätyt rivit"),"</font><hr>";

	if (!isset($MONTH_ARRAY)) $MONTH_ARRAY = array(1=> t('Tammikuu'),t('Helmikuu'),t('Maaliskuu'),t('Huhtikuu'),t('Toukokuu'),t('Kesäkuu'),t('Heinäkuu'),t('Elokuu'),t('Syyskuu'),t('Lokakuu'),t('Marraskuu'),t('Joulukuu'));

	if (!isset($tee)) $tee = '';

	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	if (!isset($tapa)) $tapa = "";
	if (!isset($eipoistettuja)) $eipoistettuja = "";
	if (!isset($varastot)) $varastot = array();
	if (!isset($keraysvyohykkeet)) $keraysvyohykkeet = array();

	if (!isset($edellinen_vuosi)) $edellinen_vuosi = "";

	//Käyttöliittymä
	echo "<form method='post'>";
	echo "<table>";

	echo "<input type='hidden' name='tee' value='kaikki'>";

	$sel = array($tapa => " selected") + array("keraaja" => "", "kerpvm" => "", "kerkk" => "");

	echo "<tr>";
	echo "<th>",t("Valitse tapa"),"</th>";
	echo "<td colspan='3'>";
	echo "<select name='tapa'>";
	echo "<option value='keraaja'{$sel['keraaja']}>",t("Kerääjittäin"),"</option>";
	echo "<option value='kerpvm'{$sel['kerpvm']}>",t("Päivittäin"),"</option>";
	echo "<option value='kerkk'{$sel['kerkk']}>",t("Kuukausittain"),"</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	$query  = "	SELECT tunnus, nimitys
				FROM keraysvyohyke
				WHERE yhtio = '{$kukarow['yhtio']}'
				ORDER BY nimitys";
	$vares = pupe_query($query);

	if (mysql_num_rows($vares) > 0) {
		echo "<tr>";
		echo "<th valign=top>",t('Keräysvyöhykkeet'),"<br /><br /><span style='font-size: 0.8em;'>",t('Saat kaikki keräysvyöhykkeet jos et valitse yhtään'),"</span></th>";
		echo "<td colspan='3'>";

	    while ($varow = mysql_fetch_assoc($vares)) {
			$sel = in_array($varow['tunnus'], $keraysvyohykkeet) ? 'checked' : '';

			echo "<input type='checkbox' name='keraysvyohykkeet[]' value='{$varow['tunnus']}' {$sel}/>{$varow['nimitys']}<br />\n";
		}

		echo "</td></tr>";
	}

	$query  = "	SELECT tunnus, nimitys
				FROM varastopaikat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tyyppi != 'P'
				ORDER BY tyyppi, nimitys";
	$vares = pupe_query($query);

	echo "<tr>";
	echo "<th valign=top>",t('Varastot'),"<br /><br /><span style='font-size: 0.8em;'>",t('Saat kaikki varastot jos et valitse yhtään'),"</span></th>";
	echo "<td colspan='3'>";

    while ($varow = mysql_fetch_assoc($vares)) {
		$sel = in_array($varow['tunnus'], $varastot) ? 'checked' : '';

		echo "<input type='checkbox' name='varastot[]' value='{$varow['tunnus']}' {$sel}/>{$varow['nimitys']}<br />\n";
	}

	echo "</td></tr>";

	$chk = $edellinen_vuosi != '' ? "checked" : "";

	echo "<tr><th valign=top>",t('Edellinen vuosi'),"<br /><br /><span style='font-size: 0.8em;'>",t('Otetaan huomioon vain päivä- tai kuukausinäkymässä'),"</span></th>";
	echo "<td colspan='3'><input type='checkbox' name='edellinen_vuosi' {$chk} /></td></tr>";

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

	$chk = $eipoistettuja != '' ? " checked" : "";

	echo "<tr>";
	echo "<th>",t("Älä näytä poistettujen käyttäjien rivejä"),"</th>";
	echo "<td colspan='3'><input type='checkbox' name='eipoistettuja'{$chk}></td>";
	echo "</tr>";

	echo "<tr><td colspan='4' class='back'></td></tr>";
	echo "<tr><td colspan='4' class='back'><input type='submit' value='",t("Aja raportti"),"'></td></tr>";
	echo "</table>";
	echo "</form>";

	echo "<br /><br />";

	if ($tee != '') {

		if ($eipoistettuja != "") {
			$lefti = "";
		}
		else {
			$lefti = "LEFT";
		}

		$lisa = "";

		if (count($varastot) > 0) {
			$lisa = " and varastopaikat.tunnus IN (".implode(', ', $varastot).")";
        }

        $keraysvyohykejoin = "";

        if (count($keraysvyohykkeet) > 0) {
        	$keraysvyohykejoin = "	JOIN varaston_hyllypaikat AS vh1 ON (vh1.yhtio = tilausrivi.yhtio AND vh1.hyllyalue = tilausrivi.hyllyalue AND vh1.hyllynro = tilausrivi.hyllynro AND vh1.hyllyvali = tilausrivi.hyllyvali AND vh1.hyllytaso = tilausrivi.hyllytaso AND vh1.keraysvyohyke IN (".implode(",", $keraysvyohykkeet)."))";
        }

		if ($tapa == 'keraaja') {

			$query = "	SELECT tilausrivi.keratty,
						tilausrivi.otunnus,
						tilausrivi.kerattyaika,
						lasku.lahetepvm,
						kuka.nimi,
						kuka.keraajanro,
						SEC_TO_TIME(UNIX_TIMESTAMP(kerattyaika) - UNIX_TIMESTAMP(lahetepvm)) aika,
						SUM(IF(tilausrivi.var  = 'P', 1, 0)) puutteet,
						SUM(IF(tilausrivi.var != 'P' AND tilausrivi.tyyppi in ('L','V'), 1, 0)) kappaleet,
						SUM(IF(tilausrivi.var != 'P' AND tilausrivi.tyyppi = 'G', 1, 0)) siirrot,
						ROUND(SUM(IF(tilausrivi.var != 'P', tilausrivi.jt + tilausrivi.varattu + tilausrivi.kpl, 0)), 2) kerkappaleet,
						ROUND(SUM(IF(tilausrivi.var != 'P', (tilausrivi.jt + tilausrivi.varattu + tilausrivi.kpl) * tuote.tuotemassa, 0)), 2) kerkilot,
						COUNT(*) yht
						FROM tilausrivi USE INDEX (yhtio_tyyppi_kerattyaika)
						JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays = '')
						JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND lasku.eilahetetta = '' AND lasku.sisainen = '')
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
						{$keraysvyohykejoin}
						{$lefti} JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = tilausrivi.yhtio AND kuka.kuka = tilausrivi.keratty)
						LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio AND
		                CONCAT(RPAD(UPPER(alkuhyllyalue),  5, '0'),LPAD(UPPER(alkuhyllynro),  5, '0')) <= CONCAT(RPAD(UPPER(tilausrivi.hyllyalue), 5, '0'),LPAD(UPPER(tilausrivi.hyllynro), 5, '0')) AND
		                CONCAT(RPAD(UPPER(loppuhyllyalue), 5, '0'),LPAD(UPPER(loppuhyllynro), 5, '0')) >= CONCAT(RPAD(UPPER(tilausrivi.hyllyalue), 5, '0'),LPAD(UPPER(tilausrivi.hyllynro), 5, '0')))
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.kerattyaika >= '{$vva}-{$kka}-{$ppa} 00:00:00'
						AND tilausrivi.kerattyaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
						AND tilausrivi.var IN ('','H','P')
						AND tilausrivi.tyyppi IN ('L','G','V')
						AND tilausrivi.keratty != ''
						{$lisa}
						GROUP BY 1,2,3,4,5,6,7
						ORDER BY kuka.nimi, tilausrivi.keratty, tilausrivi.kerattyaika";
			$result = pupe_query($query);

			$data = array(
				'keraaja' => array(),
				'summaus' => array(
					'yhteensa' => array(
						'puutteet' => 0,
						'siirrot' => 0,
						'keratyt' => 0,
						'yhteensa' => 0,
						'yhteensa_kpl' => 0,
						'yhteensa_kilot' => 0
					),
				),
			);

			$nimet = array();

			while ($row = mysql_fetch_assoc($result)) {
				$data['summaus']['yhteensa']['puutteet'] += $row['puutteet'];
				$data['summaus']['yhteensa']['siirrot'] += $row['siirrot'];
				$data['summaus']['yhteensa']['keratyt'] += $row['kappaleet'];
				$data['summaus']['yhteensa']['yhteensa'] += $row['yht'];
				$data['summaus']['yhteensa']['yhteensa_kpl'] += $row['kerkappaleet'];
				$data['summaus']['yhteensa']['yhteensa_kilot'] += $row['kerkilot'];

				if (!isset($data['summaus'][$row['keratty']])) {
					$data['summaus'][$row['keratty']] = array(
						'puutteet' => 0,
						'siirrot' => 0,
						'keratyt' => 0,
						'yhteensa' => 0,
						'yhteensa_kpl' => 0,
						'yhteensa_kilot' => 0
					);
				}

				$data['summaus'][$row['keratty']]['puutteet'] += $row['puutteet'];
				$data['summaus'][$row['keratty']]['siirrot'] += $row['siirrot'];
				$data['summaus'][$row['keratty']]['keratyt'] += $row['kappaleet'];
				$data['summaus'][$row['keratty']]['yhteensa'] += $row['yht'];
				$data['summaus'][$row['keratty']]['yhteensa_kpl'] += $row['kerkappaleet'];
				$data['summaus'][$row['keratty']]['yhteensa_kilot'] += $row['kerkilot'];

				if (!isset($data['keraaja'][$row['keratty']][$row['kerattyaika']])) {
					$data['keraaja'][$row['keratty']][$row['kerattyaika']] = array(
						'keraajanro' => '',
						'otunnus' => '',
						'lahetepvm' => '',
						'kerattyaika' => '',
						'aika' => '',
						'puutteet' => 0,
						'siirrot' => 0,
						'keratyt' => 0,
						'yhteensa' => 0,
						'yhteensa_kpl' => 0,
						'yhteensa_kilot' => 0
					);
				}

				$data['keraaja'][$row['keratty']][$row['kerattyaika']]['keraajanro'] = $row['keraajanro'];
				$data['keraaja'][$row['keratty']][$row['kerattyaika']]['otunnus'] = $row['otunnus'];
				$data['keraaja'][$row['keratty']][$row['kerattyaika']]['lahetepvm'] = tv1dateconv($row['lahetepvm'], "P");
				$data['keraaja'][$row['keratty']][$row['kerattyaika']]['kerattyaika'] = tv1dateconv($row['kerattyaika'], "P");
				$data['keraaja'][$row['keratty']][$row['kerattyaika']]['aika'] = $row['aika'];
				$data['keraaja'][$row['keratty']][$row['kerattyaika']]['puutteet'] += $row['puutteet'];
				$data['keraaja'][$row['keratty']][$row['kerattyaika']]['siirrot'] += $row['siirrot'];
				$data['keraaja'][$row['keratty']][$row['kerattyaika']]['keratyt'] += $row['kappaleet'];
				$data['keraaja'][$row['keratty']][$row['kerattyaika']]['yhteensa'] += $row['yht'];
				$data['keraaja'][$row['keratty']][$row['kerattyaika']]['yhteensa_kpl'] += $row['kerkappaleet'];
				$data['keraaja'][$row['keratty']][$row['kerattyaika']]['yhteensa_kilot'] += $row['kerkilot'];

				$nimet[$row['keratty']] = $row['nimi'];
			}

			echo "<img src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' /> <font class='info'>= ",t("Voit avata alatasoja nuolella varustetuista riveistä"),"</font>";

			echo "<br /><br />";

			echo "<table>";
			echo "<tr>";
			echo "<th colspan='5'></th>";
			echo "<th norwap>",t("Puuterivit"),"</th>";
			echo "<th norwap>",t("Siirrot"),"</th>";
			echo "<th nowrap>",t("Kerätyt"),"</th>";
			echo "<th nowrap>",t("Yhteensä"),"</th>";
			echo "<th nowrap>",t("Kappaleet"),"<br />",t("Yhteensä"),"</th>";
			echo "<th nowrap>",t("Kilot"),"<br />",t("Yhteensä"),"</th>";
			echo "</tr>";

			echo "<tr class='nayta_keraajittain' id='keraajittain'>";

			echo "<td colspan='5'><img id='arrowi_keraajittain' style='float:left;' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' /></td>";

			foreach ($data['summaus']['yhteensa'] as $_arr_key => $_arr_values) {
				echo "<td>{$_arr_values}</td>";
			}

			echo "</tr>";

			unset($data['summaus']['yhteensa']);

			echo "<tr class='keraajittain child' style='display: none;'>";
			echo "<th nowrap colspan='5'>",t("Kerääjä"),"</th>";
			echo "<th norwap>",t("Puuterivit"),"</th>";
			echo "<th norwap>",t("Siirrot"),"</th>";
			echo "<th nowrap>",t("Kerätyt"),"</th>";
			echo "<th nowrap>",t("Yhteensä"),"</th>";
			echo "<th nowrap>",t("Kappaleet"),"<br />",t("Yhteensä"),"</th>";
			echo "<th nowrap>",t("Kilot"),"<br />",t("Yhteensä"),"</th>";
			echo "</tr>";

			foreach ($data['summaus'] as $_arr_key => $_arr_values) {

				$id = str_replace(array(".", " ", ":"), "", $_arr_key);

				echo "<tr class='keraajittain nayta_keraaja child' id='{$id}' style='display: none;'>";

				$nimi = $nimet[$_arr_key] != "" ? "{$nimet[$_arr_key]} ($_arr_key)" : $_arr_key;

				echo "<td colspan='5'><img id='arrowi_{$id}' style='float:left;' src='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />&nbsp;&nbsp;{$nimi}</td>";

				foreach($_arr_values as $_val) {
					echo "<td>{$_val}</td>";
				}

				echo "</tr>";

				echo "<tr class='{$id} child' style='display: none;'>";
				echo "<th nowrap>",t("Kerääjänro"),"</th>";
				echo "<th nowrap>",t("Tilaus"),"</th>";
				echo "<th nowrap>",t("Lähete tulostettu"),"</th>";
				echo "<th nowrap>",t("Tilaus kerätty"),"</th>";
				echo "<th nowrap>",t("Käytetty aika"),"</th>";
				echo "<th norwap>",t("Puuterivit"),"</th>";
				echo "<th norwap>",t("Siirrot"),"</th>";
				echo "<th nowrap>",t("Kerätyt"),"</th>";
				echo "<th nowrap>",t("Yhteensä"),"</th>";
				echo "<th nowrap>",t("Kappaleet"),"<br />",t("Yhteensä"),"</th>";
				echo "<th nowrap>",t("Kilot"),"<br />",t("Yhteensä"),"</th>";
				echo "</tr>";

				foreach ($data['keraaja'][$_arr_key] as $aika => $arr) {

					echo "<tr class='{$id} spec aktiivi child' style='display: none;'>";

					foreach ($arr as $val) {
						echo "<td>{$val}</td>";
					}

					echo "</tr>";
				}
			}

			echo "</table>";
		}

		if (($tapa == 'kerpvm') or ($tapa == 'kerkk')) {

			$ajotapa = array('normaali');

			if ($edellinen_vuosi) {
				$ajotapa[] = 'edellinen';
			}

			$grp = $tapa == 'kerkk' ? 'left(kerattyaika, 7)' : 'pvm';

			if ($tapa == 'kerkk') {
				$selecti = "LEFT(tilausrivi.kerattyaika,7) pvm,";
			}
			else {
				$selecti = "LEFT(tilausrivi.kerattyaika,10) pvm,";
			}

			$data = $data_ed = array();

			foreach ($ajotapa as $ajo) {

				$vva_x = $ajo == 'edellinen' ? $vva - 1 : $vva;
				$vvl_x = $ajo == 'edellinen' ? $vvl - 1 : $vvl;

				$query = "	SELECT {$selecti}
							CONCAT(kuka.nimi, ' (', tilausrivi.keratty, ')') keratty,
							SUM(IF(tilausrivi.var  = 'P', 1, 0)) puutteet,
							SUM(IF(tilausrivi.var != 'P' and tilausrivi.tyyppi in ('L','V') and (tilausrivi.jt + tilausrivi.varattu + tilausrivi.kpl) > 0, 1, 0)) kappaleet,
							SUM(IF(tilausrivi.var != 'P' and tilausrivi.tyyppi in ('L','V') and (tilausrivi.jt + tilausrivi.varattu + tilausrivi.kpl) < 0, 1, 0)) kappaleet_palautus,
							SUM(IF(tilausrivi.var != 'P' and tilausrivi.tyyppi = 'G', 1, 0)) siirrot,
							COUNT(*) yht
							FROM tilausrivi USE INDEX (yhtio_tyyppi_kerattyaika)
							JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND lasku.eilahetetta = '' AND lasku.sisainen = '')
							JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays = '')
							JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
							{$keraysvyohykejoin}
							{$lefti} JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = tilausrivi.yhtio AND kuka.kuka = tilausrivi.keratty)
							JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio AND
			                CONCAT(RPAD(UPPER(alkuhyllyalue),  5, '0'),LPAD(UPPER(alkuhyllynro),  5, '0')) <= CONCAT(RPAD(UPPER(tilausrivi.hyllyalue), 5, '0'),LPAD(UPPER(tilausrivi.hyllynro), 5, '0')) AND
			                CONCAT(RPAD(UPPER(loppuhyllyalue), 5, '0'),LPAD(UPPER(loppuhyllynro), 5, '0')) >= CONCAT(RPAD(UPPER(tilausrivi.hyllyalue), 5, '0'),LPAD(UPPER(tilausrivi.hyllynro), 5, '0')))
							WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
							AND tilausrivi.kerattyaika >= '{$vva_x}-{$kka}-{$ppa} 00:00:00'
							AND tilausrivi.kerattyaika <= '{$vvl_x}-{$kkl}-{$ppl} 23:59:59'
							AND tilausrivi.var IN ('','H','P')
							AND tilausrivi.tyyppi IN ('L','G','V')
							{$lisa}
							GROUP BY 1,2
							ORDER BY 1";
				$result = pupe_query($query);

				$data_array = array();

				while ($ressu = mysql_fetch_assoc($result)) {

					if (!isset($data_array[$ressu['pvm']])) {
						$data_array[$ressu['pvm']] = array(
							'summaus' => array(
								'puutteet' => 0,
								'siirrot' => 0,
								'kappaleet' => 0,
								'kappaleet_palautus' => 0,
								'yht' => 0
							)
						);
					}

					$data_array[$ressu['pvm']]['keraajat'][$ressu['keratty']] = array(
						'puutteet' => $ressu['puutteet'],
						'siirrot' => $ressu['siirrot'],
						'kappaleet' => $ressu['kappaleet'],
						'kappaleet_palautus' => $ressu['kappaleet_palautus'],
						'yht' => $ressu['yht']
					);

					$data_array[$ressu['pvm']]['summaus']['puutteet'] += $ressu['puutteet'];
					$data_array[$ressu['pvm']]['summaus']['siirrot'] += $ressu['siirrot'];
					$data_array[$ressu['pvm']]['summaus']['kappaleet'] += $ressu['kappaleet'];
					$data_array[$ressu['pvm']]['summaus']['kappaleet_palautus'] += $ressu['kappaleet_palautus'];
					$data_array[$ressu['pvm']]['summaus']['yht'] += $ressu['yht'];
				}

				// jos ajetaan edellinen vuosi, assignoidaan $data_array $data_ed arraysee, muuten $data arrayseen
				$ajo == 'edellinen' ? $data_ed = $data_array : $data = $data_array;
			}

			// outer table
			echo "<table><tr><td class='back'>";

			$ed_ajotapa = '';

			foreach ($ajotapa as $ajo) {

				if ($ed_ajotapa != '' and $ed_ajotapa != $ajo) echo "</td><td class='back'>";

				$ed_ajotapa = $ajo;

				$postfix = $ajo == 'edellinen' ? "_ed" : "";
				$postfix_title = $ajo == 'edellinen' ? "Ed. " : "";

				echo "<table>";

				echo "<tr>";
				echo "<th>",t("{$postfix_title}Pvm"),"</th>";
				echo "<th>",t("{$postfix_title}Puutteet"),"</th>";
				echo "<th>",t("{$postfix_title}Siirrot"),"</th>";
				echo "<th>",t("{$postfix_title}Kerätyt"),"</th>";
				echo "<th>",t("{$postfix_title}Palautukset"),"</th>";
				echo "<th>",t("{$postfix_title}Yhteensä"),"</th>";
				echo "</tr>";

				foreach (${"data{$postfix}"} as $pvm => $arr) {
					foreach ($arr as $key => $_arr) {
						if ($key == 'summaus') {

							echo "<tr class='kayttajittain' id='",str_replace("-", "", $pvm),"'>";

							echo "<td>";
							echo $tapa == 'kerpvm' ? tv1dateconv($pvm, "P") : $pvm;
							echo " <img src='{$palvelin2}pics/lullacons/go-down.png' /></td>";

							foreach ($_arr as $title => $value) {
								echo "<td align='right'>{$value}</td>";
							}

							echo "</tr>";
						}
						else {

							echo "<tr class='",str_replace("-", "", $pvm),"' style='display:none;'>";
							echo "<th>",t("{$postfix_title}Kerääjä"),"</th>";
							echo "<th>",t("{$postfix_title}Puutteet"),"</th>";
							echo "<th>",t("{$postfix_title}Siirrot"),"</th>";
							echo "<th>",t("{$postfix_title}Kerätyt"),"</th>";
							echo "<th>",t("{$postfix_title}Palautukset"),"</th>";
							echo "<th>",t("{$postfix_title}Yhteensä"),"</th>";
							echo "</tr>";

							foreach ($_arr as $title => $__arr) {
								echo "<tr class='spec aktiivi ",str_replace("-", "", $pvm),"' style='display:none;'>";
								echo "<td>{$title}</td>";

								foreach ($__arr as $foo => $val) {
									echo "<td align='right'>{$val}</td>";
								}

								echo "</tr>";
							}
						}
					}
				}

				$puutteet_yht = 0;
				$kappaleet_palautus_yht = 0;
				$kappaleet_yht = 0;
				$siirrot_yht = 0;
				$yht_yht = 0;

				foreach (${"data{$postfix}"} as $pvm => $arr) {
					foreach ($arr as $key => $_arr) {
						if ($key == 'summaus') {

							foreach ($_arr as $title => $value) {
								${"{$title}_yht"} += $value;
							}
						}
					}
				}

				echo "<tr>";
				echo "<th>",t("Yhteensä"),"</th>";
				echo "<td class='tumma' align='right'>{$puutteet_yht}</td>";
				echo "<td class='tumma' align='right'>{$siirrot_yht}</td>";
				echo "<td class='tumma' align='right'>{$kappaleet_yht}</td>";
				echo "<td class='tumma' align='right'>{$kappaleet_palautus_yht}</td>";
				echo "<td class='tumma' align='right'>{$yht_yht}</td>";
				echo "</tr>";

				echo "</table>";
			}

			echo "</td></tr></table>";
		}
	}

	require ("inc/footer.inc");
