<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Varastoonviedyt rivit").":</font><hr>";

	if ($tee != '') {
		if ($tapa== 'vieja') {
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
			
			echo "	<table><tr><th>".t("Nimi")."</th><th>".t("Keikka")."</th><th>".t("Yksiköt")."</th><th>".t("Rivit")."</th><th nowrap>".t("Viety varastoon")."</th></tr>";
			$lask = 0;
			$edkeraaja = '';
	        $ysummayht	= 0;
			$rsummayht	= 0;

			while ($row = mysql_fetch_array($result)) {
				if ($edkeraaja != $row["kuka"] && $lask > 0 && $ysumma > 0) {
					echo "	<tr><td colspan='2' class='back'>".t("Yhteensä").":</td><td>$ysumma</td><td>$rsumma</td><td></td></tr>";
					echo "<tr><td class='back'><br></td></tr>";

					$ysumma	= 0;
					$rsumma	= 0;
				}
				
				echo "	<tr><td>$row[kuka]</td><td>$row[keikka]</td><td>$row[yksikot]</td><td>$row[rivit]</td><td>$row[laadittu]</td></tr>";


				$ysumma	+= $row["yksikot"];
				$rsumma	+= $row["rivit"];

				// yhteensä
				$ysummayht	+= $row["yksikot"];
				$rsummayht	+= $row["rivit"];

				$lask++;
				$edkeraaja = $row["kuka"];
			}
			if ($ysumma > 0) {
				echo "	<tr><td colspan='2' class='back'>".t("Yhteensä").":</td><td>$ysumma</td><td>$rsumma</td><td></td></tr>";
			}
			// Kaikki yhteensä
			echo "<tr><td class='back'><br></td></tr>";
			echo "<tr><td colspan='2' class='back'>".t("Kaikki yhteensä").":</td><td>$ysummayht</td><td>$rsummayht</td><td></td></tr>";

			echo "</table>";
		}

		else {
			if (!isset($vva))
				$vvaa = date("Y");
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
					and laadittu>='$vvaa-$kkaa-$ppaa 00:00:00'
					and laadittu<='$vvaa-$kkll-$ppll 23:59:59'
					and laji = 'tulo'
					group by 1
					order by 1";
			$result = mysql_query($query) or pupe_error($query);

			//echo "$query";
			echo "<table>";

			echo "<tr>";
			echo "<th>".t("pvm")."</th>";

			echo "<th>".t("Kappaleet")."</th><th>".t("Rivit")."</th>";

			echo "</tr>";
			while ($ressu = mysql_fetch_array($result)) {
					$apu 		= (int) $ressu['pvm'];
					$vietyaika[$apu]= $ressu['vietyaika'];
					$yksikot[$apu]	= $ressu['yksikot'];
					$rivit[$apu]= $ressu['rivit'];
			}




			for ($i=1; $i<367; $i++) {

					echo "<tr>";
					echo "<td>";
					if (strlen($vietyaika[$i])==0) echo "$i"; else  echo "$vietyaika[$i]";
					echo "</td>";
					echo "<td>".$yksikot[$i]."</td>";
					echo "<td>".$rivit[$i]."</td>";
					echo "</tr>";
			}

			echo "</table>";
		}
	}

	//Käyttöliittymä
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<tr><th>".t("Valitse tapa")."</th>
		<td colspan='3'><select name='tapa'>
		<option value='vieja'>".t("viejittäin")."</option>
		<option value='viepvm'>".t("Päivittäin")."</option></select></td>";
	echo "<tr><th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppa' value='$ppa' size='3'></td>
		<td><input type='text' name='kka' value='$kka' size='3'></td>
		<td><input type='text' name='vva' value='$vva' size='5'></td><th><-- ".t("Jos valitset *Päivittäin* niin ajetaan tämän vuoden tiedot")."</th>
		</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppl' value='$ppl' size='3'></td>
		<td><input type='text' name='kkl' value='$kkl' size='3'></td>
		<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	echo "<tr><td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";



	require ("../inc/footer.inc");
?>
