<?php

	require ("inc/parametrit.inc");

	if (!isset($tyyppi)) 	$tyyppi = 't';
	if (!isset($tee)) 		$tee = '';
	if (!isset($virhe)) 	$virhe = '';
	if (!isset($lisat)) 	$lisat = '';
	if (!isset($mintuote)) 	$mintuote = '';
	if (!isset($maxtuote))	$maxtuote = '';
	if (!isset($kuvaus2))	$kuvaus2 = '';
	if (!isset($tilino))	$tilino = '';

	echo "<font class='head'>".t("Toimittajan tili�intis��nn�t")."</font><hr>";

	if ($tee == 'S' or $tee == 'N' or $tee == 'Y') {

		if ($tee == 'S') { // S = selaussanahaku
			$lisat = "and selaus like '%" . $nimi . "%'";
		}

		if ($tee == 'N') { // N = nimihaku
			$lisat = "and nimi like '%" . $nimi . "%'";
		}

		if ($tee == 'Y') { // Y = yritystunnushaku
			$lisat = "and ytunnus = '$nimi'";
		}

		$query = "	SELECT tunnus, ytunnus, nimi, postitp
					FROM toimi
					WHERE yhtio = '$kukarow[yhtio]' $lisat
					ORDER BY nimi";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Haulla ei l�ytynyt yht��n toimittajaa")."</font>";
		}

		if (mysql_num_rows($result) > 40) {
			echo "<font class='error'>".t("Haulla l�ytyi liikaa toimittajia. Tarkenna hakua")."</font>";
		}
		else {
			echo "<table><tr>";

			for ($i = 1; $i < mysql_num_fields($result); $i++) {
				echo "<th>".t(mysql_field_name($result,$i))."</th>";
			}

			echo "<th></th></tr>";

			while ($trow = mysql_fetch_assoc($result)) {
				echo "<form action = '$PHP_SELF' method='post'>
						<tr>
						<input type='hidden' name='tunnus' value='$trow[tunnus]'>";

				for ($i = 1; $i < mysql_num_fields($result); $i++) {
					echo "<td>".$trow[mysql_field_name($result,$i)]."</td>";
				}

				echo "<td><input type='submit' value='".t("Valitse")."'></td></tr></form>";
			}

			echo "</table>";

			require ("inc/footer.inc");

			exit;
		}
	}

	if ($tee == 'P') {
		// Olemassaolevaa s��nt�� muutetaan, joten poistetaan rivi ja annetaan perustettavaksi
		$query = "	SELECT *
					FROM tiliointisaanto
					WHERE tunnus = '$rtunnus' and yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo t("Tili�intis��nt�� ei l�ydy")."! $query";
			exit;
		}

		$tiliointirow = mysql_fetch_assoc($result);
		$mintuote	= $tiliointirow['mintuote'];
		$maxtuote	= $tiliointirow['maxtuote'];
		$kuvaus		= $tiliointirow['kuvaus'];
		$kuvaus2	= $tiliointirow['kuvaus2'];
		$tilino		= $tiliointirow['tilino'];
		$kustp		= $tiliointirow['kustp'];
		$hyvak1		= $tiliointirow['hyvak1'];
		$hyvak2		= $tiliointirow['hyvak2'];
		$hyvak3		= $tiliointirow['hyvak3'];
		$hyvak4		= $tiliointirow['hyvak4'];
		$hyvak5		= $tiliointirow['hyvak5'];
		$vienti		= $tiliointirow['vienti'];
		$ok 		= 1;

		$query = "DELETE from tiliointisaanto WHERE tunnus = '$rtunnus' and yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);
	}

	if ($tee == 'U') {

		$query = "	SELECT tunnus
					FROM toimi
					WHERE yhtio = '$kukarow[yhtio]'
					$lisat
					ORDER BY selaus";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			$virhe	= "<font class='error'>".t("Haulla ei l�ytynyt yht��n toimittajaa")."</font>";
			$ok		= 1;
			$tee	= '';
		}

		if ($kustp != 0) {
			$query = "	SELECT tunnus
						FROM kustannuspaikka
						WHERE tunnus = '$kustp'
						and yhtio = '$kukarow[yhtio]'
						and tyyppi = 'K'
						and kaytossa != 'E'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				$virhe = "<font class='error'>".t("Kustannuspaikkaa ei l�ydy")."!</font>";
				$ok = 1;
				$tee = '';
			}
		}

		// Tarkistetaan s��nt�
		if ($tyyppi == 't') {

			if ($mintuote != '' and $maxtuote == '') $maxtuote = $mintuote;
			if ($maxtuote != '' and $mintuote == '') $mintuote = $maxtuote;

			if ($mintuote != '') {
				if ($mintuote > $maxtuote) {
					$virhe	= "<font class='error'>".t("Minimituote on pienempi kuin maksimituote")."!</font>";
					$ok		= 1;
					$tee	= '';
				}
			}

			$query = "	SELECT tilino
						FROM tili
						WHERE tilino = '$tilino' and yhtio = '$kukarow[yhtio]'";
			$result = pupe_query($query);

			if (mysql_num_rows($result) == 0) {
				$virhe = "<font class='error'>".t("Tili� ei l�ydy")."!</font>";
				$ok = 1;
				$tee = '';
			}

			//Onko t�lle v�lille jo s��nt�?
			if ($mintuote != '') {
				$query = "	SELECT mintuote, maxtuote
							FROM tiliointisaanto
							WHERE ttunnus = '$tunnus'
							and yhtio = '$kukarow[yhtio]'
							and mintuote <= '$mintuote'
							and maxtuote >= '$mintuote'
							and tilino != 0";
				$result = pupe_query($query);

				if (mysql_num_rows($result) == 0) {
					$query = "	SELECT mintuote, maxtuote
								FROM tiliointisaanto
								WHERE ttunnus = '$tunnus'
								and yhtio = '$kukarow[yhtio]'
								and mintuote <= '$maxtuote'
								and maxtuote >= '$maxtuote'
								and tilino != 0";
					$result = pupe_query($query);

					if (mysql_num_rows($result) != 0) {
						$virhe = "<font class='error'>".t("T�lle v�lille on jo s��nt�")." 1</font>";
						$ok = 1;
						$tee = '';
					}
				}
				else {
					$virhe = "<font class='error'>".t("T�lle v�lille on jo s��nt�")." 2</font>";
					$ok = 1;
					$tee = '';
				}
			}
		}
		elseif ($tyyppi == 'a') {
			if ($mintuote != '' or $maxtuote != '' or $tilino != '') {
				$virhe = t("Sis�inen virhe")."!";
				$ok = 1;
				$tee = '';
			}
			elseif ($kuvaus == '') {
				$virhe = t("Asiakastunnnus on pakollinen tieto")."!";
				$ok = 1;
				$tee = '';
			}
		}
		elseif ($tyyppi == 'b' or $tyyppi == 'o') {
			if (trim($kuvaus) == '' and trim($kuvaus2) == '' and trim($mintuote) == '' and trim($maxtuote) == '' and trim($kustp) == '') {
				$virhe = "<font class='error'>".t("Kaikki tiedot eiv�t saa olla tyhji�")."!</font>";
				$ok = 1;
				$tee = '';
			}
		}

		if ($tyyppi != 't') {

			if (trim($hyvak1) != '' or trim($hyvak2) != '' or trim($hyvak3) != '' or trim($hyvak4) != '' or trim($hyvak5) != '') {

				for ($i = 1; $i < 6; $i++) {
					if ($i == 1) {
						if ($hyvak1 == '') {
							$virhe = "<font class='error'>".t("Valitse ensimm�inen hyv�ksyj�")."!</font>";
							$ok = 1;
							$tee = '';
							break;
						}
					}
					else {
						if (${'hyvak'.$i} != '' and ${'hyvak'.($i-1)} == '') {
							$virhe = "<font class='error'>".t("Valitse hyv�ksyj�t j�rjestyksess�")."!</font>";
							$ok = 1;
							$tee = '';
							break;
						}
					}
				}
			}

		}

	}

	if ($tee == 'U') {
		// Lis�t��n s��nt�
		$query = "INSERT into tiliointisaanto VALUES (
				'$kukarow[yhtio]',
				'$tyyppi',
				'$tunnus',
				'$mintuote',
				'$maxtuote',
				'$kuvaus',
				'$kuvaus2',
				'$tilino',
				'$kustp',
				'$hyvak1',
				'$hyvak2',
				'$hyvak3',
				'$hyvak4',
				'$hyvak5',
				'$vienti',
				'$kukarow[kuka]',
				now(),
				'',
				'',
				'')";
		$result = pupe_query($query);
	}

	if (isset($tunnus) and $tunnus > 0) {
		// Toimittaja on valittu ja sille annetaan s��nt�j�
		if ($tila == "XML") {

			$query = " 	SELECT data, filename, kayttotarkoitus, tunnus
						FROM liitetiedostot
						WHERE yhtio = '$kukarow[yhtio]'
						AND liitos = 'lasku'
						AND tunnus = '$liitetiedosto'
						AND kayttotarkoitus = '$kayttotyyppi'";

			$tulokset = pupe_query($query);
			$tulosrivi = mysql_fetch_assoc($tulokset);
			$xmlstr = $tulosrivi['data'];

			$xml = simplexml_load_string($xmlstr);

			if ($xml === FALSE) {
			    echo ("Tiedosto $file ei ole validi XML!\n");
			    return ("Tiedosto $file ei ole validi XML!");
			   }

			require_once("inc/functions.inc");

			// Katsotaan mit� aineistoa k�pistell��n
			if ($kayttotyyppi == 'FINVOICE') {
			    require("inc/verkkolasku-in-finvoice.inc");
			}
			else {
			    require("inc/verkkolasku-in-pupevoice.inc");

			}
		}

		$query = "	SELECT ytunnus, concat_ws(' ', nimi, nimitark) nimi, concat_ws(' ', postino, postitp) osoite
					FROM toimi
					WHERE tunnus = '$tunnus'
					and yhtio = '$kukarow[yhtio]'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Toimittaja katosi")."</font><br>";
			exit;
		}

		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";

		while ($toimittajarow = mysql_fetch_assoc($result)) {
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<td>".$toimittajarow[mysql_field_name($result,$i)]."</td>";
			}
		}

		echo "</tr></table><br>";

		$sel = array('t' => '', 'b' => '', 'o' => '', 'a' => '', 'k' => '');
		$sel[$tyyppi] = "SELECTED";

		echo "<font class='head'>".t("S��nn�t")."</font><hr>
				<form action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tunnus' value='$tunnus'>";
		echo "<input type='hidden' name='lopetus' value='$lopetus'>";
		echo "<input type='hidden' name='tila' value='$tila'>";
		echo "<input type='hidden' name='liitetiedosto' value='$liitetiedosto'>";
		echo "<input type='hidden' name='kayttotyyppi' value='$kayttotyyppi'>";

		echo "	<table>
				<tr>
				<th>".t("Valitse tili�intis��nn�n tyyppi").":</th>
				<td><select name='tyyppi' onchange='submit();'>
				<option value='t' $sel[t]>1 ".t("Tuotes��nn�t")."</option>
				<option value='b' $sel[b]>2 ".t("Ostajan osoitetiedot")."</option>
				<option value='o' $sel[o]>3 ".t("Toimitusosoitteen tiedot")."</option>
				<option value='a' $sel[a]>4 ".t("Asiakastunnukset")."</option>
				<option value='k' $sel[k]>5 ".t("Kauttalaskutukset")."</option>
				</select></td>
				<td class='back'><input type='submit' value='".t("N�yt�")."'></td>
				</tr>
				</table>
				</form><br><br>
				<table>";

		// Pakko resetoida t��ll�kin, tai laskun-tyyppi-valikko voi heitt�� h�r�npylly�
		$vientidefault = '';
		$vientia = '';
		$vientib = '';
		$vientic = '';
		$vientid = '';
		$vientie = '';
		$vientif = '';
		$vientig = '';
		$vientih = '';
		$vientii = '';
		$vientij = '';
		$vientik = '';
		$vientil = '';


		// N�ytet��n vanhat s��nn�t muutosta varten
		if ($tyyppi == 't') {
			$query = "	SELECT tiliointisaanto.tunnus, tiliointisaanto.mintuote, tiliointisaanto.maxtuote, tiliointisaanto.kuvaus, concat(tili.tilino,'/',tili.nimi) tilinumero,
						kustannuspaikka.nimi Kustannuspaikka, tiliointisaanto.kustp, tiliointisaanto.vienti
						FROM tiliointisaanto
						LEFT JOIN tili ON tili.yhtio = tiliointisaanto.yhtio and tili.tilino = tiliointisaanto.tilino
						LEFT JOIN kustannuspaikka ON tiliointisaanto.yhtio = kustannuspaikka.yhtio and tiliointisaanto.kustp = kustannuspaikka.tunnus
						WHERE tiliointisaanto.ttunnus	= '$tunnus'
						and tiliointisaanto.tyyppi 		= '$tyyppi'
						and tiliointisaanto.yhtio 		= '$kukarow[yhtio]'
						order by tiliointisaanto.mintuote, tiliointisaanto.maxtuote, tiliointisaanto.kuvaus";
		}
		elseif ($tyyppi == 'o' or $tyyppi == 'b') {
			$query = "	SELECT tiliointisaanto.tunnus, tiliointisaanto.kuvaus Nimi, tiliointisaanto.kuvaus2 Osoite,
						tiliointisaanto.mintuote Postino, tiliointisaanto.maxtuote Postitp,
						concat(tiliointisaanto.hyvak1, '#', tiliointisaanto.hyvak2, '#', tiliointisaanto.hyvak3, '#', tiliointisaanto.hyvak4, '#', tiliointisaanto.hyvak5) Hyvak,
						kustannuspaikka.nimi Kustannuspaikka, tiliointisaanto.kustp, tiliointisaanto.vienti
						FROM tiliointisaanto
						LEFT JOIN kustannuspaikka ON tiliointisaanto.yhtio = kustannuspaikka.yhtio and tiliointisaanto.kustp = kustannuspaikka.tunnus
						WHERE tiliointisaanto.ttunnus 	= '$tunnus'
						and tiliointisaanto.tyyppi 		= '$tyyppi'
						and tiliointisaanto.yhtio 		= '$kukarow[yhtio]'
						order by tiliointisaanto.kuvaus, tiliointisaanto.kuvaus2, tiliointisaanto.mintuote, tiliointisaanto.maxtuote";
		}
		elseif ($tyyppi == 'a') {
			$query = "	SELECT tiliointisaanto.tunnus, tiliointisaanto.kuvaus Asiakastunnus,
						concat(tiliointisaanto.hyvak1, '#', tiliointisaanto.hyvak2, '#', tiliointisaanto.hyvak3, '#', tiliointisaanto.hyvak4, '#', tiliointisaanto.hyvak5) Hyvak,
						kustannuspaikka.nimi Kustannuspaikka, tiliointisaanto.kustp, tiliointisaanto.vienti
						FROM tiliointisaanto
						LEFT JOIN kustannuspaikka ON tiliointisaanto.yhtio = kustannuspaikka.yhtio and tiliointisaanto.kustp = kustannuspaikka.tunnus
						WHERE tiliointisaanto.ttunnus 	= '$tunnus'
						and tiliointisaanto.tyyppi 		= '$tyyppi'
						and tiliointisaanto.yhtio 		= '$kukarow[yhtio]'
						and tiliointisaanto.tilino 		= 0
						ORDER BY tiliointisaanto.kuvaus";
		}
		elseif ($tyyppi == 'k') {
			$query = "	SELECT tiliointisaanto.tunnus, tiliointisaanto.kuvaus Kauttalaskutus,
						concat(tiliointisaanto.hyvak1, '#', tiliointisaanto.hyvak2, '#', tiliointisaanto.hyvak3, '#', tiliointisaanto.hyvak4, '#', tiliointisaanto.hyvak5) Hyvak,
						kustannuspaikka.nimi Kustannuspaikka, tiliointisaanto.kustp, tiliointisaanto.vienti
						FROM tiliointisaanto
						LEFT JOIN kustannuspaikka ON tiliointisaanto.yhtio = kustannuspaikka.yhtio and tiliointisaanto.kustp = kustannuspaikka.tunnus
						WHERE tiliointisaanto.ttunnus 	= '$tunnus'
						and tiliointisaanto.tyyppi 		= '$tyyppi'
						and tiliointisaanto.yhtio 		= '$kukarow[yhtio]'
						ORDER BY tiliointisaanto.kuvaus";
		}

		$result = pupe_query($query);

		echo "<tr>";
		for ($i = 1; $i < mysql_num_fields($result)-2; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		if ($tyyppi !='t') echo "<th>".t("Laskun tyyppi")."</th>";
		echo "</tr>";

		while ($tiliointirow = mysql_fetch_assoc($result)) {
			echo "<tr>";

			for ($i = 1; $i<mysql_num_fields($result)-2; $i++) {

				$kennimi = mysql_field_name($result, $i);

				if ($tyyppi != 't' and $kennimi == 'Hyvak') {
					echo "<td>";

					$xxx = 1;

					foreach (explode('#', $tiliointirow[$kennimi]) as $hyvaksyja) {

						if (trim($hyvaksyja) != '') {
							$query = "	SELECT nimi
										FROM kuka
										WHERE yhtio = '$kukarow[yhtio]'
										AND kuka = '".mysql_real_escape_string($hyvaksyja)."'
										and hyvaksyja = 'o'";
							$kuka_chk_res = pupe_query($query);

							if (mysql_num_rows($kuka_chk_res) == 1) {
								$kuka_chk_row = mysql_fetch_assoc($kuka_chk_res);
								$hnimi = $kuka_chk_row['nimi'];
							}
							else {
								$hnimi = $hyvaksyja;
							}

							echo t("Hyv�ksyj�")." {$xxx}: $hnimi<br/>";
						}

						$xxx++;
					}

					echo "</td>";
				}
				else {
					echo "<td>$tiliointirow[$kennimi]</td>";
				}
			}
			if ($tyyppi != 't') {
				echo "<td>";

				if ($tiliointirow["vienti"] == '')  {
					echo t("Toimittajan oletus");
				}
				if ($tiliointirow["vienti"] == 'A') {
					echo t("Kotimaa");
				}
				if ($tiliointirow["vienti"] == 'B') {
					echo t("Kotimaa huolinta/rahti");
				}
				if ($tiliointirow["vienti"] == 'C') {
					echo t("Kotimaa vaihto-omaisuus");
				}
				if ($tiliointirow["vienti"] == 'D') {
					echo t("EU");
				}
				if ($tiliointirow["vienti"] == 'E') {
					echo t("EU huolinta/rahti");
				}
				if ($tiliointirow["vienti"] == 'F') {
					echo t("EU vaihto-omaisuus");
				}
				if ($tiliointirow["vienti"] == 'G') {
					echo t("ei-EU");
				}
				if ($tiliointirow["vienti"] == 'H') {
					echo t("ei-EU huolinta/rahti");
				}
				if ($tiliointirow["vienti"] == 'I') {
					echo t("ei-EU vaihto-omaisuus");
				}
				if ($tiliointirow["vienti"] == 'J') {
					echo t("Kotimaa raaka-aine");
				}
				if ($tiliointirow["vienti"] == 'K') {
					echo t("EU raaka-aine");
				}
				if ($tiliointirow["vienti"] == 'L') {
					echo t("ei-EU raaka-aine");
				}
				echo "</td>";
			}

			echo "<td class='back'>
					<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tunnus' value = '$tunnus'>
					<input type='hidden' name='rtunnus' value = '$tiliointirow[tunnus]'>
					<input type='hidden' name='tee' value = 'P'>
					<input type='hidden' name='tyyppi' value = '$tyyppi'>
					<input type='hidden' name='lopetus' value='$lopetus'>
					<input type='hidden' name='tila' value='$tila'>
					<input type='hidden' name='liitetiedosto' value='$liitetiedosto'>
					<input type='hidden' name='kayttotyyppi' value='$kayttotyyppi'>
					<input type='Submit' value = '".t("Muuta tili�intis��nt��")."'>
				</td></tr></form>";
		}

		// Annetaan mahdollisuus tehd� uusi tili�inti
		if (!isset($ok) or $ok != 1) {
			// Annetaan tyhj�t tiedot, jos rivi oli virheet�n
			$maxtuote	= '';
			$mintuote	= '';
			$kuvaus		= '';
			$kuvaus2	= '';
			$kustp		= '';
			$tilino		= '';
			$hyvak1		= '';
			$hyvak2		= '';
			$hyvak3		= '';
			$hyvak4		= '';
			$hyvak5		= '';
			$vienti		= '';
		}

		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = 'K'
					and kaytossa != 'E'
					ORDER BY koodi+0, koodi, nimi";
		$result = pupe_query($query);

		$ulos = "<select name = 'kustp'><option value = ' '>".t("Ei kustannuspaikkaa")."</option>";

		while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
			$valittu = "";

			if ($kustannuspaikkarow["tunnus"] == $kustp) {
				$valittu = "selected";
			}

			$ulos .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[nimi]</option>";
		}
		$ulos .= "</select><br>";

		echo "<tr>
				<form action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value = 'U'>
				<input type='hidden' name='tunnus' value = '$tunnus'>
				<input type='hidden' name='tyyppi' value = '$tyyppi'>
				<input type='hidden' name='lopetus' value='$lopetus'>";
		echo "<input type='hidden' name='tila' value='$tila'>";
		echo "<input type='hidden' name='liitetiedosto' value='$liitetiedosto'>";
		echo "<input type='hidden' name='kayttotyyppi' value='$kayttotyyppi'>";

		if ($tyyppi == 't') {
			echo "	<td><input type='text' name='mintuote' size='15' value = '$mintuote'></td>
					<td><input type='text' name='maxtuote' size='15' value = '$maxtuote'></td>
					<td><input type='text' name='kuvaus' size='15' value = '$kuvaus'></td>
					<td><input type='text' name='tilino' size='5' value = '$tilino'></td>";
		}
		elseif ($tyyppi == 'o' or $tyyppi == 'b') {
			if ($tila == "XML" and $tyyppi == 'b') {
				$kuvaus =   $ostaja_asiakkaantiedot["nimi"]; 	// Nimi
				$kuvaus2 =  $ostaja_asiakkaantiedot["osoite"];	// Osoite
				$mintuote = $ostaja_asiakkaantiedot["postino"]; // Postinumero
				$maxtuote = $ostaja_asiakkaantiedot["postitp"]; // Postitp
			}
			if ($tila == "XML" and $tyyppi == 'o') {
				$kuvaus =   $toim_asiakkaantiedot["nimi"]; 	// Nimi
				$kuvaus2 =  $toim_asiakkaantiedot["osoite"];	// Osoite
				$mintuote = $toim_asiakkaantiedot["postino"]; // Postinumero
				$maxtuote = $toim_asiakkaantiedot["postitp"]; // Postitp
			}
			echo "	<td><input type='text' name='kuvaus' size='35' value = '$kuvaus'></td>
					<td><input type='text' name='kuvaus2' size='35' value = '$kuvaus2'></td>
					<td><input type='text' name='mintuote' size='15' value = '$mintuote'></td>
					<td><input type='text' name='maxtuote' size='15' value = '$maxtuote'></td>";
		}
		elseif ($tyyppi == 'a') {
			if ($tila == "XML" and $tyyppi == 'a') {
				$kuvaus =   $laskun_asiakastunnus;
			}
			echo "	<td><input type='text' name='kuvaus' size='15' value = '$kuvaus'></td>";
		}
		elseif ($tyyppi == 'k') {
			echo "	<td><input type='text' name='kuvaus' size='15' value = '$kuvaus'></td>";
		}

		if ($tyyppi != 't') {
			$query = "	SELECT kuka, nimi
						FROM kuka
						WHERE yhtio = '$kukarow[yhtio]' and hyvaksyja = 'o'
						ORDER BY nimi";
			$hyvak_result = pupe_query($query);

			echo "<td nowrap>",t("Hyv�ksyj�")," 1: <select name='hyvak1'>";
			echo "<option value=''>",t("Oletus"),"</option>";

			$sel = (isset($hyvak1) and $hyvak1 == "MAKSUUN") ? $sel = ' SELECTED' : '';
			echo "<option value='MAKSUUN' $sel>",t("Suoraan maksuvalmiiksi"),"</option>";

			while ($hyvak_row = mysql_fetch_assoc($hyvak_result)) {
				$sel = (isset($hyvak1) and $hyvak1 == $hyvak_row['kuka']) ? $sel = ' SELECTED' : '';
				echo "<option value='$hyvak_row[kuka]'$sel>$hyvak_row[nimi]</option>";
			}

			echo "</select><br/>";

			mysql_data_seek($hyvak_result, 0);
			echo t("Hyv�ksyj�")," 2: <select name='hyvak2'><option value=''>",t("Oletus"),"</option>";

			while ($hyvak_row = mysql_fetch_assoc($hyvak_result)) {
				$sel = (isset($hyvak2) and $hyvak2 == $hyvak_row['kuka']) ? $sel = ' SELECTED' : '';
				echo "<option value='$hyvak_row[kuka]'$sel>$hyvak_row[nimi]</option>";
			}

			echo "</select><br/>";

			mysql_data_seek($hyvak_result, 0);
			echo t("Hyv�ksyj�")," 3: <select name='hyvak3'><option value=''>",t("Oletus"),"</option>";

			while ($hyvak_row = mysql_fetch_assoc($hyvak_result)) {
				$sel = (isset($hyvak3) and $hyvak3 == $hyvak_row['kuka']) ? $sel = ' SELECTED' : '';
				echo "<option value='$hyvak_row[kuka]'$sel>$hyvak_row[nimi]</option>";
			}

			echo "</select><br/>";

			mysql_data_seek($hyvak_result, 0);
			echo t("Hyv�ksyj�")," 4: <select name='hyvak4'><option value=''>",t("Oletus"),"</option>";

			while ($hyvak_row = mysql_fetch_assoc($hyvak_result)) {
				$sel = (isset($hyvak4) and $hyvak4 == $hyvak_row['kuka']) ? $sel = ' SELECTED' : '';
				echo "<option value='$hyvak_row[kuka]'$sel>$hyvak_row[nimi]</option>";
			}

			echo "</select><br/>";

			mysql_data_seek($hyvak_result, 0);
			echo t("Hyv�ksyj�")," 5: <select name='hyvak5'><option value=''>",t("Oletus"),"</option>";

			while ($hyvak_row = mysql_fetch_assoc($hyvak_result)) {
				$sel = (isset($hyvak5) and $hyvak5 == $hyvak_row['kuka']) ? $sel = ' SELECTED' : '';
				echo "<option value='$hyvak_row[kuka]'$sel>$hyvak_row[nimi]</option>";
			}

			echo "</select></td>";
		}

		echo "<td>$ulos</td>";

		if ($tyyppi != 't') {

			$vientidefault = '';
			$vientia = '';
			$vientib = '';
			$vientic = '';
			$vientid = '';
			$vientie = '';
			$vientif = '';
			$vientig = '';
			$vientih = '';
			$vientii = '';
			$vientij = '';
			$vientik = '';
			$vientil = '';

			if ($vienti == '') $vientidefault = 'SELECTED';
			if ($vienti == 'A') $vientia = 'selected';
			if ($vienti == 'B') $vientib = 'selected';
			if ($vienti == 'C') $vientic = 'selected';
			if ($vienti == 'D') $vientid = 'selected';
			if ($vienti == 'E') $vientie = 'selected';
			if ($vienti == 'F') $vientif = 'selected';
			if ($vienti == 'G') $vientig = 'selected';
			if ($vienti == 'H') $vientih = 'selected';
			if ($vienti == 'I') $vientii = 'selected';
			if ($vienti == 'J') $vientij = 'selected';
			if ($vienti == 'K') $vientik = 'selected';
			if ($vienti == 'L') $vientil = 'selected';

			echo "<td>
						<select name='vienti'>
						<option value='' $vientidefault>".t("Ei valintaa")."</option>
						<option value='A' $vientia>".t("Kotimaa")."</option>
						<option value='B' $vientib>".t("Kotimaa huolinta/rahti")."</option>
						<option value='C' $vientic>".t("Kotimaa vaihto-omaisuus")."</option>
						<option value='J' $vientij>".t("Kotimaa raaka-aine")."</option>

						<option value='D' $vientid>".t("EU")."</option>
						<option value='E' $vientie>".t("EU huolinta/rahti")."</option>
						<option value='F' $vientif>".t("EU vaihto-omaisuus")."</option>
						<option value='K' $vientik>".t("EU raaka-aine")."</option>

						<option value='G' $vientig>".t("ei-EU")."</option>
						<option value='H' $vientih>".t("ei-EU huolinta/rahti")."</option>
						<option value='I' $vientii>".t("ei-EU vaihto-omaisuus")."</option>
						<option value='L' $vientil>".t("ei-EU raaka-aine")."</option>
						</select>
					</td>";
			}

		echo "<td class='back'><input type='Submit' value = '".t("Lis�� tili�intis��nt�")."'>$virhe</td>";
		echo "</tr></form></table>";
	}
	else {
		// T�ll� ollaan, jos olemme vasta valitsemassa toimittajaa
		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
				<table>
				<td>".t("Valitse toimittaja")."</td>
				<td><input type = 'text' name = 'nimi'></td>
				<td><select name='tee'><option value = 'N'>".t("Toimittajan nimi")."
				<option value = 'S'>".t("Toimittajan selaussana")."
				<option value = 'Y'>".t("Y-tunnus")."
				</select>
				</td>
				<td><input type = 'submit' value = '".t("Valitse")."'></td>
				</tr>
				</table>
				</form>";

		$formi = 'valinta';
		$kentta = 'nimi';
	}

	require ("inc/footer.inc");

?>