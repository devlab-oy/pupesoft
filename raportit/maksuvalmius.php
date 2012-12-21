<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require("../inc/parametrit.inc");

	echo "<font class='head'>".t("Maksuvalmius")."</font><hr>";

	if ($aika == 'pv') {
		$sel1 = 'SELECTED';
	}
	if ($aika == 'vi') {
		$sel2 = "SELECTED";
	}
	if ($aika == 'kk') {
		$sel3 = "SELECTED";
	}

	if ($konserni != '') {
		$sel4 = "CHECKED";
	}

	echo "<form method='post'>
			<input type = 'hidden' name = 'tee' value = '1'>
			<table>
			<tr>
			<th>".t("Maksuvalmius")."</th>
			<td><select name='aika'>
				<option value = 'pv' $sel1>".t("Päivä")."
				<option value = 'vi' $sel2>".t("Viikko")."
				<option value = 'kk' $sel3>".t("Kuukausi")."
			</select></td>
			</tr>
			<tr>";

	if ($yhtiorow["konserni"] != "") {
		echo "<th>".t("Konserni")."</th>
			<td><input type = 'checkbox' name = 'konserni' $sel4></td>";
	}

	echo "<td class='back'><input type = 'submit' value = '".t("Näytä")."'></td>
			</tr>
			</table>
			</form><br>";

	if ($tee == "1") {

		if ($konserni == 'on') {
			// haetaan konsernin kaikki yhtiot ja tehdään mysql lauseke
			$query = "SELECT yhtio from yhtio where konserni='$yhtiorow[konserni]' and konserni != ''";
			$result = mysql_query($query) or pupe_error($query);

			$yhtio = "";
			while ($rivi = mysql_fetch_array($result)) {
				$yhtio .= "'$rivi[yhtio]',";
			}
			$yhtio = substr($yhtio, 0, -1); // vika pilkku pois

			// jos ei löytynyt yhtään konserniyhtiötä ni laitetaan kukarow[yhtio]
			if ($yhtio == "") {
				$yhtio = "'$kukarow[yhtio]'";
			}

			// Tehdään alkusiivous!
			$query = "	SELECT konserni
						FROM yhtio
						WHERE yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {
				$trow = mysql_fetch_array($result);
				//echo "Konserni on '$trow[konserni]'<br>";
			}
			else {
				echo t("Yhtiöitä löytyi monta tai ei lainkaan! Virhe!")."";
				exit;
			}

			$query = "	SELECT yhtio, konserni, nimi
						FROM yhtio
						WHERE konserni = '$yhtiorow[konserni]' and konserni != ''";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) < 2) {
				echo t("Pyysit konserninäkökulmaa, mutta yritys ei ole konsernin osa").".<br>";
				exit;
			}
			else {
				echo "<table><tr><th>".t("Konserniyritykset").":</th></tr>";

				while ($yrow = mysql_fetch_array ($result)) {
					echo "<tr><td>$yrow[nimi]</td></tr>";
				}

				echo "</table><br>";
			}
		}
		else {
			$yhtio = "'$kukarow[yhtio]'";
		}

		// pvm grouppaus hässässäkkä
		$tapa = "if(lasku.tila = 'K', DATE_ADD(tilausrivi.toimaika, INTERVAL ifnull(toimi.oletus_erapvm,0) DAY), if(lasku.tila in ('H','M','P','Q','U'), if(kapvm > now(),kapvm,erpcm),olmapvm)) olmapvm";

		if ($aika == 'vi') {
			$tapa = "YEARWEEK(if(lasku.tila = 'K', DATE_ADD(tilausrivi.toimaika, INTERVAL ifnull(toimi.oletus_erapvm,0) DAY), if(lasku.tila in ('H','M','P','Q','U'), if(kapvm > now(),kapvm,erpcm),olmapvm)),1) olmapvm";
		}
		if ($aika == 'kk') {
			$tapa = "left(if(lasku.tila = 'K', DATE_ADD(tilausrivi.toimaika, INTERVAL ifnull(toimi.oletus_erapvm,0) DAY), if(lasku.tila in ('H','M','P','Q','U'), if(kapvm > now(),kapvm,erpcm),olmapvm)),7) olmapvm";
		}

		$query = "	SELECT $tapa,
					sum(if(lasku.tila = 'U' and mapvm = '0000-00-00', lasku.summa, 0)) myynti,
					round(sum(if(lasku.tila != 'U' and lasku.tila != 'K', -1*lasku.summa*valuu.kurssi, 0)),2) osto,
					sum(if(lasku.tila = 'K', tilausrivi.hinta, 0)) summa
					FROM lasku
					LEFT JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'O' and tilausrivi.toimaika != '0000-00-00')
					LEFT JOIN toimi ON (toimi.yhtio = lasku.yhtio and toimi.tunnus = lasku.liitostunnus)
					LEFT JOIN valuu ON (valuu.yhtio = lasku.yhtio and lasku.valkoodi = valuu.nimi)
					WHERE lasku.yhtio in ($yhtio) and ((lasku.tila in ('H','M','P','Q','U') and (alatila != 'X' or tila='U')) or (lasku.vanhatunnus = 0 and lasku.tila='K')) and lasku.mapvm = '0000-00-00'
					GROUP BY 1
					HAVING myynti<>0 or osto<>0 or summa<>0";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr><th>".t("Pvm")."</th><th>".t("Myyntireskontra")."</th><th>".t("Ostoreskontra")."</th><th>".t("Yhteensä")."</th><th>".t("Kumulatiivinen")."</th><th>".t("Ostotilauksen arvo")."</th></tr>";

		$kumu = 0;

		while ($rivi = mysql_fetch_array($result)) {

			// summaillaan yhteensä ja kumulatiivinen
			$yht   = $rivi["myynti"] + $rivi["osto"];
			$kumu += $yht;

			// ja ruudulle
			echo "<tr>";
			echo "<td>$rivi[olmapvm]</td>";
			echo "<td align='right'>$rivi[myynti]</td>";
			if ($aika == "pv") {
				echo "<td align='right'><a href='../raportit.php?toim=laskuhaku&tee=M&pvm=$rivi[olmapvm]&lopetus=$PHP_SELF////tee=$tee//aika=$aika//konserni=$konserni'>$rivi[osto]</a></td>";
			}
			else {
				echo "<td align='right'>$rivi[osto]</td>";
			}
			echo "<td align='right'>".sprintf("%.2f", $yht)."</td>";
			echo "<td align='right'>".sprintf("%.2f", $kumu)."</td>";
			echo "<td>".sprintf("%.2f", $rivi['summa'])."</td>";
			echo "</tr>";
		}

		echo "</table>";
	}

	require("inc/footer.inc");
