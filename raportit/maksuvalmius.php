<?php

	require("../inc/parametrit.inc");

	print "<font class='head'>Maksuvalmius</font><hr>";

	if ($tee == "1") {
		
		if ($konserni == 'on') {
			// haetaan konsernin kaikki yhtiot ja tehdään mysql lauseke
			$query = "select yhtio from yhtio where konserni='$yhtiorow[konserni]' and konserni != ''";
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
		}
		else {
			$yhtio = "'$kukarow[yhtio]'";
		}

		// pvm grouppaus hässässäkkä
		$tapa = "if(lasku.tila='U',if(kapvm > now(),kapvm,erpcm),olmapvm) olmapvm";

		if ($aika == 'vi') {
			$tapa = "YEARWEEK(if(lasku.tila='U',if(kapvm > now(),kapvm,erpcm),olmapvm),1) olmapvm";
		}
		if ($aika == 'kk') {
			$tapa = "left(if(lasku.tila='U',if(kapvm > now(),kapvm,erpcm),olmapvm),7) olmapvm";
		}

		$query = "	SELECT $tapa,
					sum(if(lasku.tila = 'U' and mapvm = '0000-00-00', summa, 0)) myynti,
					round(sum(if(lasku.tila != 'U', -1*summa*valuu.kurssi, 0)),2) osto
					FROM lasku
					LEFT JOIN valuu ON valuu.yhtio = lasku.yhtio and lasku.valkoodi = valuu.nimi
					WHERE lasku.yhtio in ($yhtio) and lasku.tila in ('H','M','P','Q','U') and mapvm = '0000-00-00'
					GROUP BY 1
					HAVING myynti<>0 or osto<>0";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr><th>Pvm</th><th>Myyntireskontra</th><th>Ostoreskontra</th><th>Yhteensä</th><th>Kumulatiivinen</th></tr>";

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
				echo "<td align='right'><a href='../raportit.php?toim=laskuhaku&tee=M&pvm=$rivi[olmapvm]'>$rivi[osto]</a></td>";
			}
			else {
				echo "<td align='right'>$rivi[osto]</td>";
			}
			echo "<td align='right'>".sprintf("%.2f", $yht)."</td>";
			echo "<td align='right'>".sprintf("%.2f", $kumu)."</td>";
			echo "</tr>";
		}

		echo "</table>";		
	}
	else {
		echo "<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'tee' value = '1'>
				<table>
				<tr>
				<th>".t("Maksuvalmius")."</th>
				<td><select name='aika'>
					<option value = 'pv'>".t("Päivä")."
					<option value = 'vi'>".t("Viikko")."
					<option value = 'kk'>".t("Kuukausi")."
				</select></td>
				</tr>
				<tr>
				<th>".t("Konserni")."</th>
				<td><input type = 'checkbox' name = 'konserni'></td>
				<td class='back'><input type = 'submit' value = '".t("Näytä")."'></td>
				</tr>
				</table>
				</form>";
	}
	
	require("../inc/footer.inc");
?>
