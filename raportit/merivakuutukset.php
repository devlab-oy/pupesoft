<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Merivakuutuslistaus")."</font><hr>";

	if ($tee != '') {

		$query = "	SELECT tunnus, laskunro, nimi, ytunnus, toimitusehto, kuljetusmuoto,
					summa,
					rahti,
					rahti_etu,
					rahti_huolinta,
					pyoristys_erot,
					saldo_maksettu,
					vienti_kurssi,
					mapvm
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]'
					and tapvm >= '$vva-$kka-$ppa 00:00:00'
					and tapvm <= '$vvl-$kkl-$ppl 23:59:59'
					and tila = 'K'
					and vienti in ('C','F','I')
					and kuljetusmuoto = '$kuljetusmuoto'
					order by laskunro";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>
				<th>".t("Saapuminen")."</th>
				<th>".t("Toimittaja")."</th>
				<th>".t("Ytunnus")."</th>
				<th>".t("Toimitusehto")."</th>
				<th>".t("Kuljetusmuoto")."</th>
				<th>".t("Arvo")."<br>$yhtiorow[valkoodi]</th>
				<th>".t("Vak").".".t("Arvo")."<br>(".t("Arvo")." * 1,10)</th>
				<th>".t("Fennia-arvo")."<br>(".t("Vak").".".t("Arvo")." * 0,001114)</th>";
		echo "</tr>";

		$yarvo	  = 0;
		$yvakarvo = 0;
		$yfenarvo = 0;

		while ($row = mysql_fetch_assoc($result)) {

			echo "<tr class='aktiivi'>";
			echo "<td><a href='asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus=$row[tunnus]&lopetus=$PHP_SELF////tee=$tee//kuljetusmuoto=$kuljetusmuoto//ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl'>$row[laskunro]</a></td>";
			echo "<td>$row[nimi]</td>";
			echo "<td>$row[ytunnus]</td>";
			echo "<td>$row[toimitusehto]</td>";

			$kmresult = t_avainsana("KM", "", "and avainsana.selite = '$row[kuljetusmuoto]'");
			$kmrow = mysql_fetch_array($kmresult);

			echo "<td>$kmrow[selite] - $kmrow[selitetark]</td>";

			if ($row["mapvm"] != "0000-00-00") {
				// Jos virallinen varastonarvolaskenta on tehty
				$rahtikulut = $row['saldo_maksettu'] + round($row['rahti_etu'] * $row['vienti_kurssi'], 2);
				$rahtikulut += round($row['pyoristys_erot'] * $row['vienti_kurssi'], 2);
			}
			else {
				// Jos ollaan annettu tälle batchille kulusumma, käytetäänkin vaan sitä!
				if ($row["rahti_huolinta"] != 0) {
					$rahtikulut = $row["rahti_huolinta"];
				}
				else {
					$rahtikulut = round($row['summa'] * $row['vienti_kurssi'] * ($row['rahti'] / 100), 2);
				}
			}

			$arvo    = $rahtikulut + ($row['summa'] * $row['vienti_kurssi']);
			$vakarvo = $arvo * 1.10;
			$fenarvo = $vakarvo * 0.001114;

			echo "<td align='right'>".sprintf('%.2f', $arvo)."</td>";
			echo "<td align='right'>".sprintf('%.2f', $vakarvo)."</td>";
			echo "<td align='right'>".sprintf('%.2f', $fenarvo)."</td>";
			echo "</tr>";


			$yarvo	 += $arvo;
			$yvakarvo += $vakarvo;
			$yfenarvo += $fenarvo;
		}

		echo "<tr>";
		echo "<td class='back' colspan='4'></td>";
		echo "<td class='tumma'>".t("Yhteensä")."</th>";
		echo "<td class='tumma' align='right'>".sprintf('%.2f', $yarvo)."</th>";
		echo "<td class='tumma' align='right'>".sprintf('%.2f', $yvakarvo)."</th>";
		echo "<td class='tumma' align='right'>".sprintf('%.2f', $yfenarvo)."</th>";
		echo "</tr>";

		echo "</table>";
	}


	//Käyttöliittymä
	echo "<br><br><br>";
	echo "<table><form method='post'>";

	// ehdotetaan 7 päivää taaksepäin
	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<tr><th>".t("Kuljetusmuoto").":</th><td colspan='3'>
					<select NAME='kuljetusmuoto'>";

	$result = t_avainsana("KM");

	while($row = mysql_fetch_array($result)){
		$sel = '';
		if($row["selite"] == $kuljetusmuoto) {
			$sel = 'selected';
		}
		echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
	}
	echo "</select></td>";


	echo "<tr><th>".t("Syötä alkupäivämäärä")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Syötä loppupäivämäärä")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";

	require ("../inc/footer.inc");

?>