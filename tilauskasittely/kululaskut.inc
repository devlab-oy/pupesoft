<?php

$query  = "	select *
			from lasku
			where tunnus	= '$otunnus'
			and yhtio		= '$kukarow[yhtio]'
			and tila		= 'K'
			and alatila		= ''";
$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result) == 1) {
	$laskurow = mysql_fetch_array($result);
}
else {
	if ($luouusikeikka == "OK") {
		// haetaan seuraava vapaa keikkaid
		$query  = "select max(laskunro)+1 from lasku where yhtio='$kukarow[yhtio]' and tila='K'";
		$result = mysql_query($query) or pupe_error($query);
		$row    = mysql_fetch_array($result);
		$id		= $row[0];
		
		$query  = "	INSERT into lasku
					SET	yhtio	= '$kukarow[yhtio]',
					laskunro	= '$id',
					tila		= 'K',
					alatila		= '',
					liitostunnus= '$liitostunnus',
					ytunnus 	= '$liitostunnus'";
		$result = mysql_query($query) or pupe_error($query);
			
		$otunnus = mysql_insert_id();
				
		$query  = "	select *
					from lasku
					where tunnus	= '$otunnus'
					and yhtio		= '$kukarow[yhtio]'
					and tila		= 'K'
					and alatila		= ''";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);	
	}
	else {
		echo "<font class='error'>".t("Tilaus katosi!")."</font>";
		exit;
	}
}

if ($tee == "liita") {

	// haetaan ostolaskun tiedot
	$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$laskutunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$oslasrow = mysql_fetch_array($result);

	//Haetaan kululaskun kaikki verotiliˆinnit jotta voidaan tallentaa myˆs veroton summa
	$query = "	select sum(summa) summa
				from tiliointi
				where yhtio	= '$kukarow[yhtio]'
				and ltunnus	= '$oslasrow[tunnus]'
				and tilino  = '$yhtiorow[alv]'
				and korjattu = ''";
	$alvires = mysql_query($query) or pupe_error($query);
	$alvirow = mysql_fetch_array($alvires);

	//Ostoreskontralaskun veroton arvo
	$oslasrow["arvo"] = $oslasrow["summa"] - $alvirow["summa"];

	// pilkut pisteiksi
	$liitasumma = (float) str_replace(",",".",$liitasumma);

	//jos k‰ytt‰j‰ on syˆtt‰nyt osasumman kululakulta
	if ($liitasumma != 0) {

		$query = "	select sum(arvo) arvo
					from lasku
					where yhtio		= '$kukarow[yhtio]'
					and tila		= 'K'
					and vanhatunnus	= '$laskutunnus'";
		$apure = mysql_query($query) or pupe_error($query);
		$apurow = mysql_fetch_array($apure);

		//t‰m‰n kululaskun k‰ytett‰viss‰ oleva summa
		$summa_kaytettavissa = round($oslasrow["arvo"] - $apurow["arvo"],2);

		if (abs($summa_kaytettavissa) >= abs($liitasumma)) {
			//jos t‰m‰ on hyvityslasku k‰‰nnet‰‰n k‰ytt‰j‰n syˆtt‰m‰ summa aina negatiiviseksi
			if ($oslasrow["arvo"] < 0) {
				$liitasumma = -1 * abs($liitasumma);
			}
			else {
				$liitasumma = abs($liitasumma);
			}

			$verosumma = $liitasumma;
			$verotonsumma = $liitasumma;
		}

		else {
			echo "<font class='error'>".t("VIRHE: Yritit liitt‰‰ liian suuren summan. Laskun saldo ei riitt‰nyt")."!</font><br>";
			$tee = "haku";
			$verosumma = 0;
			$verotonsumma = 0;
		}

	}
	else {
		//T‰ss‰ on ostolasku verollinen summa
		$verosumma = $oslasrow["summa"];

		//Verojen m‰‰r‰
		$vero = $alvirow["summa"];

		//Jalopuksi viel‰ veroton summa
		$verotonsumma = $verosumma - $vero;
	}

	//lis‰t‰‰n lasku
	if ($verotonsumma != 0 and $verosumma != 0) {
		// meill‰ on laskurow haettuna ylh‰‰ll‰, tehd‰‰n liitosotsikko
		$query = "	insert into lasku set
					yhtio        = '$kukarow[yhtio]',
					laskunro     = '$laskurow[laskunro]',
					ytunnus	     = '$oslasrow[ytunnus]',
					nimi         = '$oslasrow[nimi]',
					tapvm        = '$oslasrow[tapvm]',
					valkoodi     = '$oslasrow[valkoodi]',
					maksu_kurssi = '$oslasrow[maksu_kurssi]',
					vienti_kurssi= '$oslasrow[vienti_kurssi]',
					toimitusehto = '$oslasrow[toimitusehto]',
					vanhatunnus  = '$oslasrow[tunnus]',
					arvo 		 = '$verotonsumma',
					viite		 = '$oslasrow[viite]',
					summa        = '$verosumma',
					vienti       = '$oslasrow[vienti]',
					liitostunnus = '$oslasrow[liitostunnus]',
					luontiaika	 = now(),
					tila         = 'K'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='message'>".t("Lasku")." ($oslasrow[nimi] $oslasrow[summa] $oslasrow[valkoodi]) ".t("liitettiin keikkaan")." $laskurow[laskunro].</font><br><br>";


		// jos kyseess‰ on vaihto-omaisuuslasku, niin p‰ivitet‰‰n laskun tapvm keikalla ja vientikent‰n info keikan otsikolle (jos jostain syyst‰ toimittajan oletus onkin v‰‰rin)
		if ($oslasrow["vienti"] == "C" or $oslasrow["vienti"] == "F" or $oslasrow["vienti"] == "I" or $oslasrow["vienti"] == "J" or $oslasrow["vienti"] == "K" or $oslasrow["vienti"] == "L") {

			// haetaan kurssi uusiksi, jos valuutta on vaihtunut
			$query  = "	select kurssi
						from valuu
						where nimi	= '$oslasrow[valkoodi]'
						and yhtio	= '$kukarow[yhtio]'";
			$kurres = mysql_query($query) or pupe_error($query);
			$kurrow = mysql_fetch_array($kurres);
			$kurssi = $kurrow["kurssi"];

			$query = "	update lasku set
						vienti			= '$oslasrow[vienti]',
						tapvm			= '$oslasrow[tapvm]',
						valkoodi		= '$oslasrow[valkoodi]',
						vienti_kurssi	= '$kurssi',
						kohdistettu		= ''
						where yhtio		= '$kukarow[yhtio]'
						and tunnus		= '$laskurow[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);
		}

		// takasin selaukseen
		$toiminto = "kululaskut";
		$ytunnus  = $laskurow["ytunnus"];
	}
}

if ($tee == "poista") {

	if ($tunnus != "") {
		// poistetaan liitetty ostolasku
		$query  = "delete from lasku where yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$laskurow[laskunro]' and summa='$poistasumma' and vanhatunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		// jos poistettiin vaihto-omaisuuslasku niin laitetaan oletukset takasin
		if ($poistavienti == "C" or $poistavienti == "F" or $poistavienti == "I" or $poistavienti == "J" or $poistavienti == "K" or $poistavienti == "L") {

			//jos keikalta poistettiin vaihto-omaisuuslasku niin merkataan se ei kohdistetuksi
			$query = "	update lasku set
						kohdistettu		= ''
						where yhtio		= '$kukarow[yhtio]'
						and tunnus		= '$laskurow[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);

			//katotaan j‰ikˆ keikalle en‰‰ yht‰‰n vaihto-omaisuuslaskua
			$query  = "	select tunnus
						from lasku
						where yhtio='$kukarow[yhtio]' and tila='K' and alatila='' and vanhatunnus<>0 and laskunro='$laskurow[laskunro]' and vienti in ('C','F','I','J','K','L')";
			$result = mysql_query($query) or pupe_error($query);

			//vaihto-omaisuuslasku/laskuja ei lˆytynyt
			if (mysql_num_rows($result) == 0) {
				// haetaan toimittajan tiedot uusiks
				$query = "select * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
				$kurres = mysql_query($query) or pupe_error($query);
				$asiakasrow = mysql_fetch_array($kurres);

				// haetaan oletuskurssi uusiksi
				$query  = "select kurssi from valuu where nimi='$asiakasrow[oletus_valkoodi]' and yhtio='$kukarow[yhtio]'";
				$kurres = mysql_query($query) or pupe_error($query);
				$kurrow = mysql_fetch_array($kurres);
				$kurssi = $kurrow["kurssi"];

				// palautetaan keikalle oletusarvot
				$query = "update lasku set vienti='$asiakasrow[oletus_vienti]', tapvm='', valkoodi='$asiakasrow[oletus_valkoodi]', vienti_kurssi='$kurssi' where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[tunnus]'";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
	}
	else {
		echo "<font class='error'>".t("Laskua ei voida poistaa!")."<br><font>";
	}

	// takasin laskuhakuun
	$tee = "";
}

if ($toiminto == "kululaskut") {

	// katotaan onko liitettyj‰ laskuja
	$query  = "	select *
				from lasku
				where yhtio='$kukarow[yhtio]' and tila='K' and alatila='' and vanhatunnus<>0 and laskunro='$laskurow[laskunro]'
				order by tapvm desc";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 0) {

		echo "<br><font class='message'>".t("Liitetyt laskut")." (".t("keikka").": $laskurow[laskunro] $laskurow[nimi])</font><br><br>";
		echo "<table>";
		echo "<tr>
				<th>".t("Nimi")."</th>
				<th>".t("Tapvm")."</th>
				<th>".t("Summa")."</th>
				<th>".t("Veroton")."</th>
				<th>".t("Valuutta")."</th>
				<th>".t("Viite")."</th>
				<th>".t("Vienti")."</th>
				</tr>";

		while($llrow = mysql_fetch_array($result)) {

			echo "<tr><td>$llrow[nimi]</td><td>$llrow[tapvm]</td><td align='right'>$llrow[summa]</td><td align='right'>$llrow[arvo]</td><td>$llrow[valkoodi]</td><td>$llrow[viite]</td><td>$llrow[vienti]</td>";
			echo "<td class='back'>
					<form name='haku' action='$PHP_SELF' method='post' autocomplete='off'>
					<input type='hidden' name='toimittajaid' value='$toimittajaid'>
					<input type='hidden' name='toiminto' value='kululaskut'>
					<input type='hidden' name='poistavienti' value='$llrow[vienti]'>
					<input type='hidden' name='tee' value='poista'>
					<input type='hidden' name='otunnus' value='$otunnus'>
					<input type='hidden' name='tunnus' value='$llrow[vanhatunnus]'>
					<input type='hidden' name='poistasumma' value='$llrow[summa]'>
					<input type='submit' value='".t("Poista")."'></form>
					</td></tr>";
		}
		echo "</table>";

		echo "<hr>";
	}

	echo "<form name='haku' action='$PHP_SELF' method='post' autocomplete='off'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='toiminto' value='kululaskut'>";
	echo "<input type='hidden' name='tee' value='haku'>";
	echo "<input type='hidden' name='otunnus' value='$otunnus'>";

	echo "<br><font class='message'>".t("Liit‰ lasku")." (".t("keikka").": $laskurow[laskunro] $laskurow[nimi])</font><br><br>";
	echo "<table><tr>";
	echo "<th>".t("Etsi lasku")."</th>";
	echo "<th><select name='hakutapa'>";
	echo "<option value='S'>".t("verollisella summalla")."</option>";
	echo "<option value='N'>".t("nimell‰")."</option>";
	echo "<option value='V'>".t("viitteell‰")."</option>";
	echo "</select></th>";
	echo "<td><input type='text' name='haku'></td>";
	echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
	echo "</tr></table>";

	echo "</form>";

	// kursorinohjausta
	$formi    = "haku";
	$kentta   = "haku";
}

if ($tee == "haku") {

	$lisa = ""; // no hacking
	if ($hakutapa == "S") {
		$haku = (float) str_replace(",",".",$haku); // pilkut pisteiksi
		$lisa = "and summa='$haku'";
	}
	if ($hakutapa == "N") $lisa = "and nimi like '%$haku%'";
	if ($hakutapa == "V") $lisa = "and viite='$haku'";

	// katsotaan aluksi, ollaanko t‰h‰n keikkaan liitetty jo vaihto-omaisuuslasku
	$query  = "	select *
				from lasku
				where yhtio='$kukarow[yhtio]' and tila='K' and alatila='' and vanhatunnus<>0 and laskunro='$laskurow[laskunro]' and vienti in ('C','F','I','J','K','L')";
	$result = mysql_query($query) or pupe_error($query);

	$vienti = "";
	$valuutta = "";

	//vaihto-omaisuuslasku/laskuja lˆytyi
	if (mysql_num_rows($result) != 0) {
		$vaihtrow = mysql_fetch_array($result);

		//n‰it‰ tarvitaan jos haluamme liitt‰‰ lis‰‰ vaihto-omaisuuslaskuja keikalle
		$valuutta 	= $vaihtrow["valkoodi"];
		$vienti		= $vaihtrow["vienti"];
	}



	// haetaan vaihto-omaisuus, rahti ja raaka-aine laskuja (kotimaa, ei-eu, eu) HUOMAA LIMIT!
	$query = "	SELECT *
				FROM lasku use index (kohdistus_index)
				WHERE yhtio='$kukarow[yhtio]' and tila in ('H','Y','M','P','Q') and vienti in ('B','C','J','E','F','K','H','I','L')
				$lisa
				order by tapvm desc
				LIMIT 50";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Yht‰‰n sopivaa laskua ei lˆytynyt!")."</font><br><br>";
		$tee = "";
	}
	else {

		echo "<table>";
		echo "<tr>
				<th>".t("Nimi")."</th>
				<th>".t("Tapvm")."</th>
				<th>".t("Summa")."</th>
				<th>".t("Veroton")."</th>
				<th>".t("Valuutta")."</th>
				<th>".t("Viite")."</th>
				<th>".t("Vienti")."</th>
				<th>".t("Liitett‰viss‰")."</th>
				<th>".t("Summa")."</th>
				</tr>";

		while($llrow = mysql_fetch_array($result)) {

			//Haetann kululaskun kaikki verotiliˆinnit jotta voidaan tallentaa myˆs veroton summa
			$query = "	select sum(summa) summa
						from tiliointi
						where yhtio	= '$kukarow[yhtio]'
						and ltunnus	= '$llrow[tunnus]'
						and tilino  = '$yhtiorow[alv]'
						and korjattu = ''";
			$alvires = mysql_query($query) or pupe_error($query);
			$alvirow = mysql_fetch_array($alvires);

			//T‰ss‰ on ostolasku verollinen summa
			$verosumma = $llrow["summa"];

			//Verojen m‰‰r‰
			$vero = $alvirow["summa"];

			//Jalopuksi viel‰ verorton summa
			$verotonsumma = $verosumma - $vero;


			echo "<tr><td>$llrow[nimi]";
			
			if ($llrow["comments"] != '') {
				echo "<br>$llrow[comments]";
			}
			
			echo "</td>";
			
			
			echo "	<td valign='top'>$llrow[tapvm]</td>
					<td align='right' valign='top'>".sprintf('%.2f',$verosumma)."</td>
					<td align='right' valign='top'>".sprintf('%.2f',$verotonsumma)."</td>
					<td valign='top'>$llrow[valkoodi]</td>
					<td valign='top'>$llrow[viite]</td>
					<td valign='top'>$llrow[vienti]</td>";

			$query = "	select sum(arvo) arvo, GROUP_CONCAT(DISTINCT laskunro ORDER BY laskunro SEPARATOR ', ') laskunrot
						from lasku
						where yhtio		= '$kukarow[yhtio]'
						and tila		= 'K'
						and vanhatunnus	= '$llrow[tunnus]'";
			$apure = mysql_query($query) or pupe_error($query);
			$apurow = mysql_fetch_array($apure);

			$summa_kaytettavissa = round(abs($verotonsumma) - abs($apurow["arvo"]),2);

			if ($summa_kaytettavissa > 0) {

				if ($verotonsumma < 0) {
					$lw = -1;
				}
				else {
					$lw = 1;
				}

				$summa_kaytettavissa = $summa_kaytettavissa * $lw;

				echo "<td class='green' align='right' valign='top'>".sprintf('%.2f',$summa_kaytettavissa)."</td>";

				$voikolisata = "";

				//kululaskut voidaan aina lis‰t‰
				if ($llrow["vienti"] != "C" and $llrow["vienti"] != "F" and $llrow["vienti"] != "I" and $llrow["vienti"] != "J" and $llrow["vienti"] != "K" and $llrow["vienti"] != "L") {
					$voikolisata = "OK";
				}
				//jos t‰m‰ on vahto-omaisuuslasku pit‰‰ viel‰ tarkistaa vienti ja valuutta
				elseif ($llrow["valkoodi"] == $valuutta and $llrow["vienti"] == $vienti and ($llrow["vienti"] == "C" or $llrow["vienti"] == "F" or $llrow["vienti"] == "I" or $llrow["vienti"] == "J" or $llrow["vienti"] == "K" or $llrow["vienti"] == "L")) {
					$voikolisata = "OK";
				}
				elseif ($vienti == '' and $valuutta == '' and ($llrow["vienti"] == "C" or $llrow["vienti"] == "F" or $llrow["vienti"] == "I" or $llrow["vienti"] == "J" or $llrow["vienti"] == "K" or $llrow["vienti"] == "L")) {
					$voikolisata = "OK";
				}

				if ($voikolisata == "OK") {

					if ($llrow["vienti"] != "C" and $llrow["vienti"] != "F" and $llrow["vienti"] != "I" and $llrow["vienti"] != "J" and $llrow["vienti"] != "K" and $llrow["vienti"] != "L") {
						echo "<td valign='top'><form name='haku' action='$PHP_SELF' method='post'>
								<input type='text' name='liitasumma' size='8'>
								</td>";
					}
					else {
						echo "<td valign='top'><form name='haku' action='$PHP_SELF' method='post'> </td>";
					}


					echo "<td class='back' valign='top'>";
					echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
					echo "<input type='hidden' name='toiminto' value='kululaskut'>";
					echo "<input type='hidden' name='hakutapa' value='$hakutapa'>";
					echo "<input type='hidden' name='haku' value='$haku'>";
					echo "<input type='hidden' name='tee' value='liita'>";
					echo "<input type='hidden' name='otunnus' value='$otunnus'>";
					echo "<input type='hidden' name='laskutunnus' value='$llrow[tunnus]'>";
					echo "<input type='submit' value='".t("Valitse")."'>";
					echo "</td></tr>";
					echo "</form>";
				}
				else {
					echo "<td class='spec' colspan='2' valign='top'>";
					echo t("T‰t‰ vaihto-omaisuuslaskua ei voida lis‰t‰ t‰lle keikalle")."!";
					echo "</td></tr>";
				}
			}
			else {
				echo "<td colspan='2' valign='top'>";
				echo t("Laskun summa on jo k‰ytetty keikalla/keikoilla")." $apurow[laskunrot]!";
				echo "</td></tr>";
			}
		}

		echo "</table>";
	}
}

if ($toiminto != "") {
	echo "<hr>";
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toiminto' value=''>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
	echo "<input type='submit' value='".t("Takaisin keikalle")."'>";
	echo "</form>";
}

?>