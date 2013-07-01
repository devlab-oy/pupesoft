<?php

	// Syötetään rivi
	echo "	<tr>
			<form name='rivi' action = '$PHP_SELF#$rivitunnus' method='post' autocomplete='off'>
			<input type='hidden' name='toim' 				value = '$toim'>
			<input type='hidden' name='lopetus' 			value = '$lopetus'>
			<input type='hidden' name='tilausnumero' 		value = '$tilausnumero'>
			<input type='hidden' name='toim_nimitykset' 	value = '$toim_nimitykset'>
			<input type='hidden' name='toim_tuoteno'		value = '$toim_tuoteno'>
			<input type='hidden' name='naytetaankolukitut' 	value = '$naytetaankolukitut'>
			<input type='hidden' name='toiminto' 			value = '$toiminto'>
			<input type='hidden' name='tee' 				value = 'TI'>
			<input type='hidden' name='rivitunnus' 			value = '$rivitunnus'>
			<input type='hidden' name='perheid2' 			value = '$perheid2'>
			<input type='hidden' name='muuta' 				value = '$muuta'>
			<input type='hidden' name='otunnus' 			value = '$otunnus'>
			<input type='hidden' name='muut_siirrettavat' 	value = '$muut_siirrettavat'>
			<input type='hidden' name='rivinotunnus' 		value = '$rivinotunnus'>
			<input type='hidden' name='osto_sarjatunnus' 	value = '$osto_sarjatunnus'>
			<input type='hidden' name='rivinvar' 			value = '$var'>
			<input type='hidden' name='lasku'				value = '$lasku'>
			<input type='hidden' name='toimittajaid'		value = '$toimittajaid'>";

	$variaaritorivi = FALSE;

	$suoratoimitusekko = "";

	if ($rivitunnus > 0) {
		//Tutkitaan löytyykö JT-rivi joka mäppäytyy tähän ostoriviin
	   	$query = "	SELECT tilausrivi.tunnus, tilausrivi.otunnus, lasku.nimi, tilausrivin_lisatiedot.suoraan_laskutukseen
	   				FROM tilausrivin_lisatiedot
	   				JOIN tilausrivi ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and (tilausrivi.varattu+tilausrivi.jt) > 0 and tilausrivi.laskutettuaika = '0000-00-00')
					JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
	   				WHERE tilausrivin_lisatiedot.yhtio = '$kukarow[yhtio]'
	   				and tilausrivin_lisatiedot.tilausrivilinkki  = '$rivitunnus'";
		$varastoon_result = pupe_query($query);

		if ($varastoon_row = mysql_fetch_assoc($varastoon_result)) {
			echo "<input type='hidden' name='suoratoimitus_rivitun' value='$varastoon_row[tunnus]'>";
			echo "<input type='hidden' name='suoratoimitus_rivikpl' value='$kpl'>";

			if ($varastoon_row["suoraan_laskutukseen"] != "") {
				$suoratoimitusekko = "<tr><th colspan='7'>".t("HUOM").":</td></tr><tr><td colspan='7'>".t("Suoratoimitus asiakkaalle").": $varastoon_row[nimi], ".t("Tilaus").": $varastoon_row[otunnus]. Ostorivin muutokset tehdään myös myyntiriville!</td></tr>";
			}
			else {
				$suoratoimitusekko = "<tr><th colspan='7'>".t("HUOM").":</td></tr><tr><td colspan='7'>".t("Tilattu asiakkaalle").": $varastoon_row[nimi], ".t("Tilaus").": $varastoon_row[otunnus]. Ostorivin muutokset tehdään myös myyntiriville!</td></tr>";
			}
		}
	}

	if ($yhtiorow['variaatiomyynti'] == "K" and $kukarow["extranet"] == '' and (is_array($tuoteno_array) or $tuoteno != "" or $variaatio_tuoteno) and $ulos == "" and $rivitunnus == "") {
		$variaaritorivi_osto = TRUE;

		require ("syota_variaatiorivi.inc");
	}

	if ($variaaritorivi === FALSE) {
		// Annetaan mahdollisuus tehdä uusi tilausrivi tai näytetään virhe
		echo "<table>
				$suoratoimitusekko
				<tr>
				<th>".t("Tuoteno")."</th>
				<th>".t("Määrä")."</th>
				<th>".t("Määrä")."</th>
				<th>".t("Hinta")."</th>";

				for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
					echo "<th>",t("Ale"),"{$alepostfix}</th>";
				}

		echo 	"<th>".t("Alv")."</th>
				<th>".t("Viestit")."</th>
				<td class='back'></td>
				</tr>";

		if ($ulos != '') { // Nyt me selataan
			// Meilla on kaikki valmiina $ulos:ssa
			echo "<td>$ulos</td>";
		}
		else {
			if ($yhtiorow["livetuotehaku_tilauksella"] == "K" and $tuoteno == "") {

				$nosubmitti = "";

				if (strpos($_SERVER['SCRIPT_NAME'], "keikka.php")  !== FALSE) {
					$nosubmitti = "EISUBMIT";
				}

				echo "<td valign='top'>".livesearch_kentta("tilaus", "TUOTEHAKU", "tuoteno", 200, '', $nosubmitti)."</td>";
			}
			else {
				echo "<td><input type='text' size='18' name='tuoteno' value = '$tuoteno'></td>";
			}
		}

		echo "<td>$kpl</td>
			<td><input type='text' size='7' name='kpl' value = '$kpl'></td>
			<td><input type='text' size='7' name='hinta' value = '$hinta'></td>";

		$backspan = 5;
		for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
			echo "<td><input type='text' size='5' name='ale{$alepostfix}' value = '",${'ale'.$alepostfix},"'></td>";
			$backspan++;
		}

		if ($alv=='') $alv=$laskurow['alv'];

		echo "<td>".alv_popup_oletus('alv',$alv)."</td><td>";

		if ($automatiikka == "ON" and $onjodropdowni != 'ON' and $laskurow['tila'] != 'O') {
			echo "	".t("Valtse toiminto").": <select name='var'>";
			echo "<option value='LOJ#!¡!#$kpl'>".t("Lisää syötetyt ja merkkaa erotus JT:ksi")."</option>";
#			echo "<option value='LOP#!¡!#$kpl'>".t("Lisää syötetyt ja merkkaa erotus puutteeksi")."</option>";
			echo "<option value=''>".t("Päivitä rivin määrä")."</option>";
			echo "</select>";
		}

		// Ostohinnan päivityskysely
		if ($paiv == 'X') {
			echo "<input type='radio' name='paiv' value='K'>".t("Päivitä")."<br>";
			echo "<input type='radio' name='paiv' value='E' CHECKED>".t("Älä Päivitä")."<br>";
		}

		echo "$varaosavirhe</td>";
		echo "<td class='back'><input type='Submit' name= 'kumpi' value = '".t("Lisää")."'></td>";

		//näytetään poista nappi vain kun ollaan navigoitu ostotilaus riville saapumisen kohdista rivin kautta ja kun tilausrivi on esitäytetty
		if($saapuminen_ostorivin_muokkaus == 1 and !empty($rivitunnus)) {
			echo "<td class='back'><input type='submit' name='poista' value='".t("Poista")."'/></td>";
		}

		echo "</tr>";

		if ($toim == "HAAMU" or $tuoteno == "99" or $yhtiorow["nimityksen_muutos_tilauksella"] == "Y") {
			echo "	<tr><th align='left' colspan='7'>".t("Nimitys")."</th></tr>
					<tr><td align='left' colspan='7'><input type = 'text' name='tuotenimitys' value='$tuotenimitys' size = '70'></td></tr>";

		}

		echo "<tr>
				<th align='left'>".t("Toimitusaika")."</th>";

		echo "<th colspan='$backspan' align='left'>".t("Kommentti")."</th>";

		echo "<td class='back'></td>
				</tr>";

		if ($toimaika == "" or $toimaika == "0000-00-00") {
			$toimaika = $laskurow["toimaika"];
		}

		if ($toimaika == "" or $toimaika == "0000-00-00") {
			$toimkka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$toimvva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$toimppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		}
		else {
			$toimkka = substr($toimaika, 5, 2);
			$toimvva = substr($toimaika, 0, 4);
			$toimppa = substr($toimaika, 8, 2);
		}

		echo "<tr>
				<td><input type='text' size='3' name='toimppa' value='$toimppa'><input type='text' size='3' name='toimkka' value='$toimkka'><input type='text' size='5' name='toimvva' value='$toimvva'></td>
				<td colspan='$backspan'><textarea name='kommentti' cols='50'>$kommentti</textarea></td>";

		//Tyhjennetään kentät
		echo "<td class='back'><input type='Submit' name= 'tyhjenna' value = '".t("Tyhjennä")."'></td></tr>";
		echo "</form></table><br>";
	}

	// Kursorinohjausta
	if ($formi == '') {
		$formi = 'rivi';
	}

   if ($kentta == '') {
       $kentta = 'tuoteno';
   }

?>
