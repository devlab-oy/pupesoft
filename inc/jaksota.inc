<?php

	$query = "SELECT * FROM lasku WHERE tunnus='$tunnus' and yhtio ='$kukarow[yhtio]'";

	$lresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($lresult) == 0) {
		echo "<b>".t("Lasku")." '$tunnus' ".t("katosi")."</b><br>";
		exit;
	}
	$laskurow=mysql_fetch_array ($lresult);

	$query = "SELECT * FROM tiliointi
				WHERE tunnus='$isa' and ltunnus = '$tunnus' and yhtio ='$kukarow[yhtio]'";

	$tresult = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($tresult) == 0) {
		echo "<b>".t("Tiliointi")." '$isa' ".t("katosi")."</b><br>";
		exit;
	}
	$tiliointirow=mysql_fetch_array ($tresult);

	if ($tiliointirow['vero'] != 0) {
		echo "<b>".t("ALV:n sisältävää riviä en osaa jaksottaa")."</b><br>";
		$tee = 'E';
	}

	if ($tee == 'U') { // Jaksotetaan tehtyä riviä
		echo "<font class='head'>".t("Tiliöinnin jaksotus")."</font><hr>";
		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($tresult); $i++) {
			echo "<th>" . t(mysql_field_name($tresult,$i))."</th>";
		}
		echo "</tr><tr>";
		for ($i=0; $i<mysql_num_fields($tresult); $i++) {
				echo "<td>$tiliointirow[$i]</td>";
		}
		echo "</tr></table><br><br>";
		echo "<form action = '$PHP_SELF' method = 'post'>
				<input type = 'hidden' name = 'tunnus' value = '$tunnus'>
				<input type = 'hidden' name = 'isa' value = '$isa'>
				<input type = 'hidden' name = 'tee' value = 'J'>
				<table><tr>";

		echo "<td>" .$tiliointirow['tapvm'] . "<input type = 'checkbox' name = 'kausi[]' value = '1'></td>";

		$kk = substr($tiliointirow['tapvm'], 5,2); // Tämä on kausi
		$vv = substr($tiliointirow['tapvm'], 0,4); // Tämä on vuosi

		for ($i = 2; $i < 20; $i++) {
			$kk++;
			if ($kk > 12) {
				$kk = 1;
				$vv++;
			}
			echo "<td>$vv-$kk-1 <input type = 'checkbox' name = 'kausi[]' value = '$i'></td>";
			$j = $i/4;
			if ($j == round($j,0)) { // Tehdään vähän sarakkeita
				echo "</tr><tr>";
			}
		}
		echo "</tr></table>";
		echo "<input type = 'submit' value = '".t("Jaksota")."'></form>";
	}
	else {
		if (count($kausi)!=0) { // Täällä tehdään varsinainen jaksotus
//			echo "Jaksotusinfo<br>";

			$pkk = substr($tiliointirow['tapvm'], 5,2); // Tämä on kausi
			$pvv = substr($tiliointirow['tapvm'], 0,4); // Tämä on vuosi

//			echo "Peruskausi on $pvv - $pkk<br>";

			$kpl = count($kausi);
//			echo "Jaksoja = $kpl<br>";
			$summa = round($tiliointirow['summa']/$kpl,2);
//			echo "Summa on $summa<br>";
			$eka = 0; // Tällä katsotaan ollaanko ensimmäisellä kierroksella

			foreach ($kausi as $mille) {

				// mikä on jaksotuksen vastatili
				// jos kyseessä on  myyntilasku niin 
				if ($laskurow["tila"] == "U") {
					$jaksotilino = $yhtiorow['siirtovelka'];
				}
				else {
					$jaksotilino = $yhtiorow['siirtosaamiset'];
				}
				
				if ($ok == 0) { // Mitä tapahtuu alkuperäiselle tiliöinnille

					$tapvm = $tiliointirow['tapvm'];

					if ($mille == 1) { // Varsinainen tiliöinti jää voimaan
//						echo "Varsinainen on valittu<br>";
						$asumma = $summa; // VIedään tiliöinnille normaali jaksotettu summa
					}
					else {
//						echo "Varsinaista ei valittu<br>";
						$asumma = 0;
//						Tehdään tiliöinti summalla 0, jotta meille jää jokin, jolla poistaa jaksotukset
					}
					$query = "INSERT into tiliointi set
						yhtio ='$kukarow[yhtio]',
						ltunnus =  '$tiliointirow[ltunnus]',
						tilino = '$tiliointirow[tilino]',
						kustp = '$tiliointirow[kustp]',
						kohde = '$tiliointirow[kohde]',
						projekti = '$projekti',
						tapvm = '$tapvm',
						summa = '$asumma',
						vero = '0',
						selite = '". "Jaksotus" . " $tiliointirow[selite]',
						lukko = '',
						laatija = '$kukarow[kuka]',
						laadittu = now()";
//					echo "$query<br>";
					$result = mysql_query($query) or pupe_error($query);
					$jisa = mysql_insert_id ($link); // Näin löydämme tiliöinnin, johon kiinnitämme muut

					// Tehdään vastaava siirtosaaminen
					$ssumma = $tiliointirow['summa'] - $asumma;
					$query = "INSERT into tiliointi set
						yhtio ='$kukarow[yhtio]',
						ltunnus =  '$tiliointirow[ltunnus]',
						tilino = '$jaksotilino',
						kustp = '$tiliointirow[kustp]',
						kohde = '$tiliointirow[kohde]',
						projekti = '$projekti',
						tapvm = '$tapvm',
						summa = $ssumma,
						vero = '0',
						selite = '". "Jaksotus" . " $tiliointirow[selite]',
						lukko = '1',
						aputunnus = '$jisa',
						laatija = '$kukarow[kuka]',
						laadittu = now()";
//					echo "$query<br>";
					$result = mysql_query($query) or pupe_error($query);

					$ok = 1; // Ensimmäinen on käsitelty
					$tsumma += $asumma; // Kuinka paljon olemme tiliöinneet
				}
				if ($mille != 1) { // Emme käsittele varsinaista tapahtumaa
					if ($jisa == 0) { // Jossain on jotain mätää, ei isä-tiliöintiä!
						echo "Jisa = 0, system error<br>";
						exit;
					}

					$pkk = substr($tiliointirow['tapvm'], 5,2); // Tämä on kausi
					$pvv = substr($tiliointirow['tapvm'], 0,4); // Tämä on vuosi

					$pkk += $mille - 1; // Oletus tapvm
					while ($pkk > 12) {
						$pkk -= 12;
						$pvv++;
					}
					$tapvm = $pvv . "-" . $pkk . "-1";

//					echo "Jaksotus pvmlle $tapvm<br>";
					$query = "INSERT into tiliointi set
						yhtio ='$kukarow[yhtio]',
						ltunnus =  '$tiliointirow[ltunnus]',
						tilino = '$tiliointirow[tilino]',
						kustp = '$tiliointirow[kustp]',
						kohde = '$tiliointirow[kohde]',
						projekti = '$projekti',
						tapvm = '$tapvm',
						summa = '$summa',
						vero = '0',
						selite = '". "Jaksotus" . " $tiliointirow[selite]',
						lukko = '1',
						aputunnus = '$jisa',
						laatija = '$kukarow[kuka]',
						laadittu = now()";
					$result = mysql_query($query) or pupe_error($query);
					$viimeinen1 = mysql_insert_id ($link); // Näin löydämme viimeisen muutoksen, jolle viemme pyöristyksen

					$query = "INSERT into tiliointi set
						yhtio ='$kukarow[yhtio]',
						ltunnus =  '$tiliointirow[ltunnus]',
						tilino = '$jaksotilino',
						kustp = '$tiliointirow[kustp]',
						kohde = '$tiliointirow[kohde]',
						projekti = '$projekti',
						tapvm = '$tapvm',
						summa = $summa * -1,
						vero = '0',
						selite = '". "Jaksotus" . " $tiliointirow[selite]',
						lukko = '1',
						aputunnus = '$jisa',
						laatija = '$kukarow[kuka]',
						laadittu = now()";

//					echo "$query<br>";
					$result = mysql_query($query) or pupe_error($query);
					$viimeinen2 = mysql_insert_id ($link); // Näin löydämme viimeisen muutoksen, jolle viemme pyöristyksen
					$tsumma += $summa;
				}
			}
			if (($viimeinen1 == 0) or ($viimeinen1 == 0)) { // Jossain on jotain mätää, ei viimeistä tiliöintiä!
				echo "".t("viimeinen1 tai 2 = 0").", system error<br>";
				exit;
			}
			if ($isa == 0) { // Jossain on jotain mätää, perustiliöinti katosi!
				echo "".t("isa = 0").", system error<br>";
				exit;
			}

			if ($tsumma != $tiliointirow['summa']) {
				$tsumma -= $tiliointirow['summa'];
				$tsumma = round($tsumma,2);
//				echo "Pyöristystä $tsumma<br>";
				$query = "UPDATE tiliointi SET summa = summa - $tsumma WHERE tunnus = '$viimeinen1' and yhtio = '$kukarow[yhtio]'";
//				echo "$query<br>";
				$result = mysql_query($query) or pupe_error($query);
				$query = "UPDATE tiliointi SET summa = summa + $tsumma WHERE tunnus = '$viimeinen2' and yhtio = '$kukarow[yhtio]'";
//				echo "$query<br>";
				$result = mysql_query($query) or pupe_error($query);
			}
// Yli-viivataan varsinainen tiliointi
			$query = "UPDATE tiliointi SET korjattu = '$kukarow[kuka]', korjausaika = now() WHERE tunnus = '$isa' and yhtio = '$kukarow[yhtio]'";
//			echo "$query<br>";
			$result = mysql_query($query) or pupe_error($query);
			$tee = 'E';
		}
		else {
			echo "".t("Jaksotuksia ei kuitenkaan valittu")."!<br>";
		}
	}
?>
