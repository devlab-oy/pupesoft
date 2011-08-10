<?php

	require("inc/parametrit.inc");

	enable_jquery();

	echo "	<script type='text/javascript'>
				$(document).ready(function() {
					$('.kollibutton').click(function(){
						var kollitunniste = $(this).attr('id');
						$('#kolli').val(kollitunniste);
						$('#formi').submit();
					});

					$('.etsibutton').click(function(){
						var rivitunniste = $(this).attr('id');
						$('#asn_rivi').val(rivitunniste);
						$('#kolliformi').submit();
					});
				});
			</script>";

	echo "<font class='head'>",t("Ostolasku / ASN-sanomien tarkastelu"),"</font><hr>";

	if (!isset($tee)) $tee = '';

	if ($tee == 'etsi_tilausrivi') {
		if (isset($asn_rivi)) {
			list($asn_rivi, $tuoteno, $tilaajanrivinro) = explode('##', $asn_rivi);

			$asn_rivi = (int) $asn_rivi;

			$query = "SELECT * FROM asn_sanomat WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$asn_rivi}'";
			$result = pupe_query($query);
			$asn_row = mysql_fetch_assoc($result);
		}

		if (!isset($toimittaja) and isset($asn_row)) $toimittaja = $asn_row['toimittajanumero'];
		if (!isset($tilausnro) and isset($asn_row)) $tilausnro = $asn_row['tilausnumero'];
		if (!isset($kpl) and isset($asn_row)) $kpl = $asn_row['kappalemaara'];

		echo "<form method='post' action='?tee=etsi_tilausrivi'>";
		echo "<input type='hidden' name='lopetus' value='{$lopetus}' />";

		echo "<table>";
		echo "<tr><th colspan='6'>",t("Etsi tilausrivi"),"</th></tr>";

		echo "<tr>";
		echo "<th>",t("Toimittaja"),"</th>";
		echo "<th>",t("Tilausnumero"),"</th>";
		echo "<th>",t("Tuotenumero"),"</th>";
		echo "<th>",t("Tilaajan rivinumero"),"</th>";
		echo "<th>",t("Kpl"),"</th>";
		echo "<th>&nbsp;</th>";
		echo "</tr>";

		echo "<tr>";
		echo "<td><input type='text' name='toimittaja' value='{$toimittaja}' /></td>";
		echo "<td><input type='text' name='tilausnro' value='{$tilausnro}' /></td>";
		echo "<td><input type='text' name='tuoteno' value='{$tuoteno}' /></td>";
		echo "<td><input type='text' name='tilaajanrivinro' value='{$tilaajanrivinro}' /></td>";
		echo "<td><input type='text' name='kpl' value='{$kpl}' /></td>";
		echo "<td><input type='submit' value='",t("Etsi"),"' /></td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";

		if (trim($toimittaja) != '' or trim($tilausnro) != '' or trim($tuoteno) != '' or trim($tilaajanrivinro) != '' or trim($kpl) != '') {
			echo "<br /><hr /><br />";

			echo "<form method='post' action='?tee=kohdista_tilausrivi'>";
			echo "<input type='hidden' name='lopetus' value='{$lopetus}' />";

			echo "<table>";
			echo "<tr><th colspan='5'>",t("Haun tulokset"),"</th><th><input type='button' value='",t("Kohdista"),"' /></th></tr>";
			echo "<tr>";
			echo "<th>",t("Tilausnumero"),"</th>";
			echo "<th>",t("Tuoteno"),"</th>";
			echo "<th>",t("Varattu")," / ",t("Kpl"),"</th>";
			echo "<th>",t("Keikka"),"</th>";
			echo "<th>",t("Keikan tila"),"</th>";
			echo "<th>",t("Kohdistus"),"</th>";
			echo "</tr>";

			$tilaajanrivinrolisa = trim($tilaajanrivinro) != '' ? "and tilaajanrivinro = ".(int) $tilaajanrivinro : '';
			$tilausnrolisa = trim($tilausnro) != '' ? "and otunnus = ".(int) $tilausnro : '';
			$tuotenolisa = trim($tuoteno) != '' ? "and tuoteno = '".mysql_real_escape_string($tuoteno)."'" : '';
			$kpllisa = trim($kpl) != '' ? "and varattu = ".(float) $kpl : '';

			$query = "	SELECT *, if(uusiotunnus = 0, '', uusiotunnus) AS uusiotunnus
						FROM tilausrivi
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tyyppi IN ('O', 'K')
						#AND uusiotunnus = 0
						{$tilaajanrivinrolisa}
						{$tilausnrolisa}
						{$tuotenolisa}
						{$kpllisa}";
			$result = pupe_query($query);

			while ($row = mysql_fetch_assoc($result)) {
				echo "<tr>";
				echo "<td>{$row['otunnus']}</td>";
				echo "<td>{$row['tuoteno']}</td>";
				echo "<td>{$row['varattu']} / {$row['kpl']}</td>";

				if ($row['uusiotunnus'] != '') {
					$query = "SELECT laskunro FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = '{$row['uusiotunnus']}'";
					$keikkares = pupe_query($query);
					$keikkarow = mysql_fetch_assoc($keikkares);
					$row['uusiotunnus'] = $keikkarow['laskunro'];
				}

				echo "<td>$row[uusiotunnus]</td>";

				if ($row['uusiotunnus'] != 0 and $row['kpl'] != 0) {
					echo "<td>",t("Viety varastoon"),"</td>";
					echo "<td>&nbsp;</td>";
				}
				else {
					echo "<td>&nbsp;</td>";
					echo "<td><input type='checkbox' name='tunnukset[]' value='{$row['tunnus']}' /></td>";
				}

				echo "</tr>";
			}

			echo "</table>";
			echo "</form>";
		}
	}

	if ($tee == 'nayta_kolli') {

		$kolli = mysql_real_escape_string($kolli);

		$query = "	SELECT asn_sanomat.toimittajanumero, asn_sanomat.toim_tuoteno, asn_sanomat.tilausrivinpositio, asn_sanomat.kappalemaara, asn_sanomat.status,
					tuotteen_toimittajat.tuoteno, tuote.nimitys, tilausrivi.tilaajanrivinro
					FROM asn_sanomat
					JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero)
					JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = asn_sanomat.yhtio AND tuotteen_toimittajat.toim_tuoteno = asn_sanomat.toim_tuoteno AND tuotteen_toimittajat.liitostunnus = toimi.tunnus)
					JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno)
					JOIN tilausrivi ON (tilausrivi.yhtio = asn_sanomat.yhtio AND tilausrivi.otunnus = asn_sanomat.tilausnumero AND tilausrivi.tuoteno = tuote.tuoteno)
					WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
					AND asn_sanomat.paketintunniste = '{$kolli}'
					AND asn_sanomat.status != ''
					ORDER BY asn_sanomat.tilausrivinpositio + 0 ASC";
		$result = pupe_query($query);

		echo "<form method='post' action='?tee=etsi_tilausrivi&lopetus={$lopetus}/SPLIT/{$PHP_SELF}////tee=nayta_kolli//kolli={$kolli}' id='kolliformi'>";
		echo "<input type='hidden' id='asn_rivi' name='asn_rivi' value='' />";

		echo "<table>";
		echo "<tr>";
		echo "<th>",t("Toimittajanro"),"</th>";
		echo "<th>",t("Ostotilausnumero"),"</th>";
		echo "<th>",t("Tuotenumero"),"</th>";
		echo "<th>",t("Toimittajan"),"<br />",t("Tuotenumero"),"</th>";
		echo "<th>",t("Nimitys"),"</th>";
		echo "<th>",t("Rivinumero"),"</th>";
		echo "<th>",t("Kpl"),"</th>";
		echo "<th>",t("Hinta"),"</th>";
		echo "<th>",t("Alennukset"),"</th>";
		echo "<th>",t("Status"),"</th>";
		echo "<th>&nbsp;</th>";
		echo "</tr>";

		while ($row = mysql_fetch_assoc($result)) {
			echo "<tr>";

			echo "<td>{$row['toimittajanumero']}</td>";
			echo "<td>{$row['tilausnumero']}</td>";
			echo "<td>{$row['tuoteno']}</td>";
			echo "<td>{$row['toim_tuoteno']}</td>";
			echo "<td>{$row['nimitys']}</td>";
			echo "<td>{$row['tilausrivinpositio']}</td>";
			echo "<td>{$row['kappalemaara']}</td>";
			echo "<td></td>";
			echo "<td></td>";

			echo "<td><font class='ok'>",t("Ok"),"</font></td>";

			echo "<td></td>";

			echo "</tr>";
		}

		$query = "	SELECT asn_sanomat.toimittajanumero, asn_sanomat.toim_tuoteno, asn_sanomat.tilausrivinpositio, asn_sanomat.kappalemaara, asn_sanomat.status, asn_sanomat.tilausnumero,
					toimi.tunnus AS toimi_tunnus, asn_sanomat.tunnus AS asn_tunnus
					FROM asn_sanomat
					JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero)
					WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
					AND asn_sanomat.paketintunniste = '{$kolli}'
					AND asn_sanomat.status = ''
					ORDER BY asn_sanomat.tilausrivinpositio + 0 ASC";
		$result = pupe_query($query);

		while ($row = mysql_fetch_assoc($result)) {
			echo "<tr>";

			$query = "SELECT tuoteno FROM tuotteen_toimittajat WHERE yhtio = '{$kukarow['yhtio']}' AND toim_tuoteno = '{$row['toim_tuoteno']}' AND liitostunnus = '{$row['toimi_tunnus']}'";
			$res = pupe_query($query);

			if (mysql_num_rows($res) > 0) {
				$ttrow = mysql_fetch_assoc($res);

				$row['tuoteno'] = $ttrow['tuoteno'];

				$query = "	SELECT nimitys 
							FROM tuote 
							WHERE yhtio = '{$kukarow['yhtio']}' 
							AND tuoteno = '{$ttrow['tuoteno']}'";
				$tres = pupe_query($query);
				$trow = mysql_fetch_assoc($tres);

				$row['nimitys'] = $trow['nimitys'];

				$query = "SELECT nimitys, uusiotunnus, tilaajanrivinro FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus = '{$row['tilausnumero']}' AND tuoteno = '{$row['tuoteno']}' AND tyyppi = 'O'";
				$tilres = pupe_query($query);

				if (mysql_num_rows($tilres) > 0) {
					$tilrow = mysql_fetch_assoc($tilres);
					$row['nimitys'] = $tilrow['nimitys'];
					$row['uusiotunnus'] = $tilrow['uusiotunnus'];
					$row['tilaajanrivinro'] = $tilrow['tilaajanrivinro'];
				}
				else {
					$row['uusiotunnus'] = 0;
					$row['tilaajanrivinro'] = '';
				}
			}
			else {
				$row['tuoteno'] = '';
				$row['nimitys'] = t("Tuntematon tuote");
			}

			echo "<td>{$row['toimittajanumero']}</td>";
			echo "<td>{$row['tilausnumero']}</td>";
			echo "<td>{$row['tuoteno']}</td>";
			echo "<td>{$row['toim_tuoteno']}</td>";
			echo "<td>{$row['nimitys']}</td>";
			echo "<td>{$row['tilausrivinpositio']}</td>";
			echo "<td>{$row['kappalemaara']}</td>";
			echo "<td></td>";
			echo "<td></td>";

			echo "<td><font class='error'>",t("Virhe"),"</font></td>";

			echo "<td>";
			if ($row['uusiotunnus'] == 0) echo "<input type='button' class='etsibutton' id='{$row['asn_tunnus']}##{$row['tuoteno']}##{$row['tilaajanrivinro']}' value='",t("Etsi"),"' />";
			echo "</td>";

			echo "</tr>";
		}

		echo "</table>";
		echo "</form>";
	}

	if ($tee == '') {
		$query = "	SELECT toimi.ytunnus, toimi.nimi, toimi.nimitark, toimi.osoite, toimi.osoitetark, toimi.postino, toimi.postitp, toimi.maa, toimi.swift,
					asn_sanomat.asn_numero, asn_sanomat.paketintunniste,
					count(asn_sanomat.tunnus) AS rivit,
					sum(if(status != '', 1, 0)) AS ok
					FROM asn_sanomat
					JOIN toimi ON (toimi.yhtio = asn_sanomat.yhtio AND toimi.toimittajanro = asn_sanomat.toimittajanumero)
					WHERE asn_sanomat.yhtio = '{$kukarow['yhtio']}'
					GROUP BY asn_sanomat.paketinnumero, asn_sanomat.asn_numero, toimi.ytunnus, toimi.nimi, toimi.nimitark, toimi.osoite, toimi.osoitetark, toimi.postino, toimi.postitp, toimi.maa, toimi.swift
					ORDER BY asn_sanomat.asn_numero, asn_sanomat.paketintunniste";
		$result = pupe_query($query);

		echo "<form method='post' action='?tee=nayta_kolli&lopetus={$PHP_SELF}////tee=' id='formi'>";
		echo "<input type='hidden' id='kolli' name='kolli' value='' />";
		echo "<table>";
		echo "<tr>";
		echo "<th>",t("Ytunnus"),"</th>";
		echo "<th>",t("Nimi"),"</th>";
		echo "<th>",t("Osoite"),"</th>";
		echo "<th>",t("Swift"),"</th>";
		echo "<th>",t("ASN sanomanumero"),"</th>";
		echo "<th>",t("ASN kollinumero"),"</th>";
		echo "<th>",t("Rivim‰‰r‰"),"<br />",t("ok")," / ",t("kaikki"),"</th>";
		echo "<th>&nbsp;</th>";
		echo "</tr>";

		while ($row = mysql_fetch_assoc($result)) {
			echo "<tr>";
			echo "<td>{$row['ytunnus']}</td>";

			echo "<td>{$row['nimi']}";
			if (trim($row['nimitark']) != '') echo " {$row['nimitark']}";
			echo "</td>";

			echo "<td>{$row['osoite']} ";
			if (trim($row['osoitetark']) != '') echo "{$row['osoitetark']} ";
			echo "{$row['postino']} {$row['postitp']} {$row['maa']}</td>";

			echo "<td>{$row['swift']}</td>";
			echo "<td>{$row['asn_numero']}</td>";
			echo "<td>{$row['paketintunniste']}</td>";
			echo "<td>{$row['ok']} / {$row['rivit']}</td>";
			echo "<td><input type='button' class='kollibutton' id='{$row['paketintunniste']}' value='",t("Valitse"),"' /></td>";
			echo "</tr>";
		}

		echo "</table>";
		echo "</form>";
	}

	require ("inc/footer.inc");