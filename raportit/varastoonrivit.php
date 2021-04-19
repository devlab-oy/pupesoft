<?php

	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Varastoonviedyt rivit").":</font><hr>";

	if ($tee != "") {

		if (!checkdate($kka, $ppa, $vva)) {
			echo "<font class='error'>".t("Virheellinen alkupäivämäärä")."!</font><br>";
			$tee = "";
		}

		if (!checkdate($kkl, $ppl, $vvl)) {
			echo "<font class='error'>".t("Virheellinen loppupäivämäärä")."!</font><br>";
			$tee = "";
		}
	}

	if ($tee != '') {
		if ($tapa == 'vieja') {

			$query = "	SELECT if(kuka.nimi is null,tapahtuma.laatija, kuka.nimi) kuka,
						tilausrivi.uusiotunnus keikka,
						left(tapahtuma.laadittu,10) laadittu,
						sum(tapahtuma.kpl) yksikot,
						count(tapahtuma.tunnus) rivit
						FROM tapahtuma
						JOIN tilausrivi ON tilausrivi.yhtio = tapahtuma.yhtio and tilausrivi.tunnus = tapahtuma.rivitunnus
						LEFT JOIN kuka ON kuka.yhtio = tapahtuma.yhtio and kuka.kuka = tapahtuma.laatija
						WHERE tapahtuma.yhtio='$kukarow[yhtio]' and tapahtuma.laji = 'tulo'
						and tapahtuma.laadittu >='$vva-$kka-$ppa 00:00:00'
						and tapahtuma.laadittu <='$vvl-$kkl-$ppl 00:00:00'
						group by 1,2,3
						ORDER BY kuka.nimi, left(tapahtuma.laadittu,10)";

			$result = mysql_query($query) or pupe_error($query);

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Nimi")."</th>";
			echo "<th>".t("Keikka")."</th>";
			echo "<th>".t("Yksiköt")."</th>";
			echo "<th>".t("Rivit")."</th>";
			echo "<th nowrap>".t("Viety varastoon")."</th>";
			echo "</tr>";

			$lask = 0;
			$edkeraaja = '';
	        $ysummayht	= 0;
			$rsummayht	= 0;

			while ($row = mysql_fetch_array($result)) {

				if ($edkeraaja != $row["kuka"] and $lask > 0 and $ysumma > 0) {

					echo "<tr>";
					echo "<th colspan='2'>".t("Yhteensä").":</th>";
					echo "<th style='text-align:right'>$ysumma</th>";
					echo "<th style='text-align:right'>$rsumma</th>";
					echo "<th></th>";
					echo "</tr>";

					echo "<tr><td class='back'><br></td></tr>";

					echo "<table>";
					echo "<tr>";
					echo "<th>".t("Nimi")."</th>";
					echo "<th>".t("Keikka")."</th>";
					echo "<th>".t("Yksiköt")."</th>";
					echo "<th>".t("Rivit")."</th>";
					echo "<th nowrap>".t("Viety varastoon")."</th>";
					echo "</tr>";

					$ysumma	= 0;
					$rsumma	= 0;
				}

				echo "<tr>";
				echo "<td>$row[kuka]</td>";
				echo "<td>$row[keikka]</td>";
				echo "<td align='right'>$row[yksikot]</td>";
				echo "<td align='right'>$row[rivit]</td>";
				echo "<td align='right'>".tv1dateconv($row["laadittu"])."</td>";
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
				echo "<th colspan='2'>".t("Yhteensä").":</th>";
				echo "<th style='text-align:right'>$ysumma</th>";
				echo "<th style='text-align:right'>$rsumma</th>";
				echo "<th></th>";
				echo "</tr>";
			}

			// Kaikki yhteensä
			echo "<tr>";
			echo "<td class='back'><br></td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th colspan='2'>".t("Kaikki yhteensä").":</th>";
			echo "<th style='text-align:right'>$ysummayht</th>";
			echo "<th style='text-align:right'>$rsummayht</th>";
			echo "<th></th>";
			echo "</tr>";

			echo "</table>";
		}
		else {

			if (!isset($vva)) {
				$vvaa = date("Y");
			}
			else {
				$vvaa = $vva;
			}

			$ppaa = '01';
			$kkaa = '01';
			$kkll = '12';
			$vvll = date("Y");
			$ppll = '31';

			$query = "SELECT date_format(left(laadittu,10), '%j') pvm, left(laadittu,10) vietyaika,
					sum(tapahtuma.kpl) yksikot,
					count(tapahtuma.tunnus) rivit
					from tapahtuma
					where yhtio = '$kukarow[yhtio]'
					and laadittu >= '$vvaa-$kkaa-$ppaa 00:00:00'
					and laadittu <= '$vvaa-$kkll-$ppll 23:59:59'
					and laji = 'tulo'
					group by 1
					order by 1";
			$result = mysql_query($query) or pupe_error($query);

			//echo "$query";
			echo "<table>";

			echo "<tr>";
			echo "<th>".t("Pvm")."</th>";
			echo "<th>".t("Kappaleet")."</th>";
			echo "<th>".t("Rivit")."</th>";
			echo "</tr>";

			while ($ressu = mysql_fetch_array($result)) {
				$apu = (int) $ressu['pvm'];
				$vietyaika[$apu] = $ressu['vietyaika'];
				$yksikot[$apu] = $ressu['yksikot'];
				$rivit[$apu] = $ressu['rivit'];
			}

			for ($i=1; $i<366; $i++) {
				echo "<tr>";
				echo "<td>".tv1dateconv(date("Y-m-d", mktime(0, 0, 0, 1, 0+$i, $vvaa)))."</td>";
				echo "<td align='right'>".$yksikot[$i]."</td>";
				echo "<td align='right'>".$rivit[$i]."</td>";
				echo "</tr>";
			}

			echo "</table>";
		}
	}

	//Käyttöliittymä
	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	echo "<form method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<br>";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Valitse tapa")."</th>";
	echo "<td colspan='3'><select name='tapa'>
			<option value='vieja'>".t("Viejittäin")."</option>
			<option value='viepvm'>".t("Päivittäin")."</option>
			</select></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>";
	echo "<td><input type='text' name='ppa' value='$ppa' size='3'></td>";
	echo "<td><input type='text' name='kka' value='$kka' size='3'></td>";
	echo "<td><input type='text' name='vva' value='$vva' size='5'></td>";
	echo "<td class='back'><-- ".t("Jos valitset *Päivittäin* niin ajetaan tämän vuoden tiedot")."</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>";
	echo "<td><input type='text' name='ppl' value='$ppl' size='3'></td>";
	echo "<td><input type='text' name='kkl' value='$kkl' size='3'></td>";
	echo "<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
	echo "</tr>";
	echo "</table>";

	echo "<br>";
	echo "<input type='submit' value='".t("Aja raportti")."'>";
	echo "</form>";

	require ("inc/footer.inc");

?>
