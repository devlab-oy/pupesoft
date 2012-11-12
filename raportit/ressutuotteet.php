<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>Varastotilannetarkistus (loppu oletuspaikalla, saldoa jossain muualla):</font><hr>";

	if ($tee != '') {

		$varastot = "";

		if (is_array($varastosta)) {
			foreach($varastosta as $var) {
				$varastot .= $var.",";
			}
			$varastot = substr($varastot,0,-1);
			$varastot = " and varastopaikat.tunnus in ($varastot) ";
		}

		if ($nollat != '') {
			$nollatlisa = " 1 ";
		}
		else {
			$nollatlisa = " tuotepaikat.saldo ";
		}

		#TODO ei sortaa varastopaikkoja oikein
		$query = "	SELECT tuotepaikat.tuoteno,
					sum(if(tuotepaikat.oletus='X',tuotepaikat.saldo,0)) oletuspaikalla,
					sum(if(tuotepaikat.oletus='',$nollatlisa,0)) muillapaikoilla,
					sum(if(tuotepaikat.oletus='X',1,0)) oletuspaikkoja,
					GROUP_CONCAT(if(tuotepaikat.oletus='X', concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso), '') SEPARATOR '') paikka
					FROM tuotepaikat
					LEFT JOIN varastopaikat
					ON varastopaikat.yhtio = tuotepaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					WHERE tuotepaikat.yhtio='$kukarow[yhtio]'
					$varastot
					GROUP BY tuotepaikat.tuoteno
					HAVING oletuspaikalla <= 0 and muillapaikoilla > 0 and oletuspaikkoja > 0
					ORDER BY paikka, tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		echo "	<table><tr><th>Tuoteno</th><th>Nimitys</th><th>Toim_tuoteno</th><th>Varastopaikka</th><th>Oletus</th><th>Saldo</th></tr>";

		while ($row = mysql_fetch_array($result)) {
			#TODO ei sorttaa varastopaikkoja oikein
			$query = "	SELECT tuotepaikat.tuoteno,
						concat_ws(' ',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) tuotepaikka,
						tuotepaikat.saldo, tuotepaikat.oletus,
						tuote.nimitys,
						(SELECT tuoteno FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno LIMIT 1) toim_tuoteno
						FROM tuotepaikat
						JOIN tuote ON tuote.tuoteno=tuotepaikat.tuoteno and tuote.yhtio=tuotepaikat.yhtio
						LEFT JOIN varastopaikat
						ON varastopaikat.yhtio = tuotepaikat.yhtio
						and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
						and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
						WHERE tuotepaikat.yhtio='$kukarow[yhtio]'
						and tuotepaikat.tuoteno='$row[tuoteno]'
						$varastot
						ORDER BY oletus desc, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuoteno";
			$aresult = mysql_query($query) or pupe_error($query);

			while ($arow = mysql_fetch_array($aresult)) {
				echo "<tr><td>$arow[tuoteno]</td><td>".t_tuotteen_avainsanat($arow, 'nimitys')."</td><td>$arow[toim_tuoteno]</td><td>$arow[tuotepaikka]</td><td>$arow[oletus]</td><td>$arow[saldo]</td></tr>";
			}
			echo "<tr><td colspan='4' class='back'></td></tr>";
		}
		echo "</table>";
	}

	//Käyttöliittymä
	echo "<br>";
	echo "<form method='post'>";
	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<table>";
	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
				ORDER BY tyyppi, nimitys";
	$vtresult = mysql_query($query) or pupe_error($query);

	while ($vrow = mysql_fetch_array($vtresult)) {
		$sel = "";

		if ($varastosta[$vrow["tunnus"]] != '') {
			$sel = "CHECKED";
		}

		echo "<tr><th>".t("Huomioi saldot varastossa:")." $vrow[nimitys]</th><td><input type='checkbox' name='varastosta[$vrow[tunnus]]' value='$vrow[tunnus]' $sel></td></tr>";
	}

	echo "<tr><td class='back'><br></td></tr>";


	$sel = "";

	if ($nollat != '') {
		$sel = "CHECKED";
	}
	echo "<tr><th>".t("Näytä myös tuotteet joiden saldo on nolla kaikilla paikoilla:")."</th><td><input type='checkbox' name='nollat' $sel></td></tr>";

	echo "</table><br><input type='submit' value='Aja raportti'>";

	require ("../inc/footer.inc");
?>