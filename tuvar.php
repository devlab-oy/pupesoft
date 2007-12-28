<?php
	require("inc/parametrit.inc");

	if (($tee == 'N') or ($tee == 'E')) {
		if ($tee == 'N') {
			$oper='>';
			$suun='';
		}
		else {
			$oper='<';
			$suun='desc';
		}

		$query = "	SELECT tuote.tuoteno, sum(saldo) saldo, status
					FROM tuote
					LEFT JOIN tuotepaikat ON tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.yhtio=tuote.yhtio
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					and tuote.tuoteno " . $oper . " '$tuoteno'
					GROUP BY tuote.tuoteno
					HAVING status NOT IN ('P','X') or saldo > 0
					ORDER BY tuote.tuoteno " . $suun . "
					LIMIT 1";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$trow = mysql_fetch_array ($result);
			$tuoteno = $trow['tuoteno'];
			$tee='Z';
		}
		else {
			$varaosavirhe = t("Yhtään tuotetta ei löytynyt")."!";
			$tuoteno = '';
			$tee='Y';
		}
	}

	echo "<font class='head'>".t("Tuotekysely")."</font><hr>";

	if (($tee == 'Z') and ($tyyppi == '')) {
		require "inc/tuotehaku.inc";
	}
	if (($tee == 'Z') and ($tyyppi != '')) {

		if ($tyyppi == 'TOIMTUOTENO') {

			$query = "	SELECT tuotteen_toimittajat.tuoteno, sum(saldo) saldo, status
						FROM tuotteen_toimittajat
						JOIN tuote ON tuote.yhtio=tuotteen_toimittajat.yhtio and tuote.tuoteno=tuotteen_toimittajat.tuoteno and tuote.status NOT IN ('P','X')
						LEFT JOIN tuotepaikat ON tuotepaikat.yhtio=tuotteen_toimittajat.yhtio and tuotepaikat.tuoteno=tuotteen_toimittajat.tuoteno
						WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
						and tuotteen_toimittajat.toim_tuoteno = '$tuoteno'
						GROUP BY tuotteen_toimittajat.tuoteno
						HAVING status NOT IN ('P','X') or saldo > 0
						ORDER BY tuote.tuoteno";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				$varaosavirhe = t("VIRHE: Tiedolla ei löytynyt tuotetta")."!";
				$tee = 'Y';
			}
			elseif (mysql_num_rows($result) > 1) {
				$varaosavirhe = t("VIRHE: Tiedolla löytyi useita tuotteita")."!";
				$tee = 'Y';
			}
			else {
				$tr = mysql_fetch_array($result);
				$tuoteno = $tr["tuoteno"];
			}
		}
		elseif ($tyyppi != '') {
			$query = "	SELECT tuoteno
						FROM tuotteen_avainsanat
						WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and laji='$tyyppi'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				$varaosavirhe = t("VIRHE: Tiedolla ei löytynyt tuotetta")."!";
				$tee = 'Y';
			}
			else {
				$tr = mysql_fetch_array($result);
				$tuoteno = $tr["tuoteno"];
			}
		}
	}

	if ($tee=='Y') echo "<font class='error'>$varaosavirhe</font>";

	 //syotetaan tuotenumero
	$formi  = 'formi';
	$kentta = 'tuoteno';

	echo "<table><tr>";;
	echo "<form action='$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='Z'>";

	echo "<td class='back'><select name='tyyppi'>";
	echo "<option value=''>".t("Tuotenumero").":</option>";
	echo "<option value='TOIMTUOTENO'>".t("Toimittajan tuotenumero").":</option>";

	$query = "	SELECT selite, selitetark
				FROM avainsana
				WHERE laji = 'TUOTEULK' and yhtio = '$kukarow[yhtio]'
				ORDER BY jarjestys";
	$vresult = mysql_query($query) or pupe_error($query);

	while ($vrow = mysql_fetch_array($vresult)) {
		echo "<option value='$vrow[selite]'>$vrow[selitetark]:</option>";
	}

	echo "</select></th>";
	echo "<td class='back'><input type='text' name='tuoteno' value=''></td>";
	echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
	echo "</form>";

	//Jos ei haettu, annetaan 'edellinen' & 'seuraava'-nappi
	if (($ulos=='') and ($tee=='Z')) {
		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tee' value='E'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<td class='back'>";
		echo "<input type='Submit' value='".t("Edellinen")."'>";
		echo "</td>";
		echo "</form>";

		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
		echo "<input type='hidden' name='tee' value='N'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<td class='back'>";
		echo "<input type='Submit' value='".t("Seuraava")."'>";
		echo "</td>";
		echo "</form>";
	}
	echo "</tr></table><br>";
	
	
	//tuotteen varastostatus
	if ($tee == 'Z') {
		$query = "	SELECT tuote.*, date_format(tuote.muutospvm, '%Y-%m-%d') muutos, date_format(tuote.luontiaika, '%Y-%m-%d') luonti,
					group_concat(distinct tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '<br>') toimittaja,
					group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '<br>') osto_era,
					group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno,
					group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '<br>') tuotekerroin 
					FROM tuote 
					LEFT JOIN tuotteen_toimittajat USING (yhtio, tuoteno)
					WHERE tuote.yhtio = '$kukarow[yhtio]' 
					and tuote.tuoteno = '$tuoteno'
					GROUP BY tuote.tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$tuoterow = mysql_fetch_array($result);

			//saldot per varastopaikka
			$query = "select * from tuotepaikat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$sresult = mysql_query($query) or pupe_error($query);

			//saldolaskentaa tulevaisuuteen
			$query = "	SELECT  if(tyyppi = 'O', toimaika, kerayspvm) paivamaara,
						sum(if(tyyppi='O', varattu, 0)) tilattu,
						sum(if((tyyppi='L' or tyyppi='G' or tyyppi='V'), varattu, 0)) varattu
						FROM tilausrivi
						WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and varattu>0 and tyyppi in ('O','L','G','V')
						GROUP BY toimaika";
			$presult = mysql_query($query) or pupe_error($query);

			//tilauksessa olevat
			$query = "	SELECT toimaika paivamaara,
						sum(varattu) tilattu, otunnus
						FROM tilausrivi
						WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and varattu>0 and tyyppi='O'
						GROUP BY toimaika, otunnus
						ORDER BY toimaika";
			$tulresult = mysql_query($query) or pupe_error($query);

			//korvaavat tuotteet
			$query  = "select * from korvaavat where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
			$korvaresult = mysql_query($query) or pupe_error($query);

			//eka laitetaan tuotteen yleiset (aika staattiset) tiedot
			echo "<table>";
			echo "<tr><th>".t("Tuoteno")."</th><th colspan='5'>".t("Nimitys")."</th>";
			echo "<tr><td>$tuoterow[tuoteno]</td><td colspan='5'>".substr(asana('nimitys_',$tuoterow['tuoteno'],$tuoterow['nimitys']),0,100)."</td></tr>";

			echo "<tr><th>".t("Os/Try")."</th><th>".t("Toimittaja")."</th><th>".t("Aleryhmä")."</th><th>".t("Tähti")."</th><th colspan='2'>".t("VAK")."</th></tr>";
			echo "<td>$tuoterow[osasto]/$tuoterow[try]</td><td>$tuoterow[toimittaja]</td>
					<td>$tuoterow[aleryhma]</td><td>$tuoterow[tahtituote]</td><td colspan='2'>$tuoterow[vakkoodi]</td></tr>";
			echo "<tr><th>".t("Toimtuoteno")."</th><th>".t("Myyntihinta")."</th><th>".t("Nettohinta")."</th><th colspan='3'>".t("Viimeksi tullut")."</th>";
			echo "<tr><td>$tuoterow[toim_tuoteno]</td>
					<td>$tuoterow[myyntihinta]</td><td>$tuoterow[nettohinta]</td><td colspan='3'>$tuoterow[vihapvm]</td></tr>";
			echo "<tr><th>".t("Hälyraja")."</th><th>".t("Tilerä")."</th><th>".t("Toierä")."</th><th>".t("Kerroin")."</th><th>".t("Tarrakerroin")."</th><th>".t("Tarrakpl")."</th>";
			echo "<tr><td>$tuoterow[halytysraja]</td>
					<td>$tuoterow[osto_era]</td><td>$tuoterow[myynti_era]</td><td>$tuoterow[tuotekerroin]</td>
					<td>$tuoterow[tarrakerroin]</td><td>$tuoterow[tarrakpl]</td></tr>";
			echo "</table><br>";


			// Varastosaldot ja paikat
			echo "<table><tr><td class='back' valign='top'>";

			if ($tuoterow["ei_saldoa"] == '') {
				//saldot
				echo "<table>";
				echo "<tr><th>".t("Varastopaikka")."</th><th>".t("Saldo")."</th></tr>";

				$kokonaissaldo = 0;
				if (mysql_num_rows($sresult) > 0) {
					while ($saldorow = mysql_fetch_array ($sresult)) {
						echo "<tr><td>$saldorow[hyllyalue] $saldorow[hyllynro] $saldorow[hyllyvali] $saldorow[hyllytaso]</td>";
						echo "<td>$saldorow[saldo]</td></tr>";
						//summataan kokonaissaldoa
						$kokonaissaldo += $saldorow["saldo"];
					}
				}
				echo "<tr><th>".t("Yhteensä")."</th><td>$kokonaissaldo</td></tr>";
				$asaldo = $kokonaissaldo;
				$ennpois = 0;
				$tilauksessa = 0;
				while ($prow = mysql_fetch_array ($presult)) {
					if($prow["varattu"] > 0) {
						$ennpois += $prow["varattu"];
					}
				}
				$myytavissa = $kokonaissaldo-$ennpois;
				echo "<tr><th>".t("Myytävissä")."</th><td>$myytavissa</td></tr>";

				echo "</table>";
				echo "</td><td class='back' valign='top'>";

				// tilatut
				echo "<table>";
				echo "<tr><th>".t("Päivämäärä")."</th><th>".t("Tilattu")."</th><th>".t("Tilaus")."</th></tr>";

				$tilauksessa = 0;
				if (mysql_num_rows($tulresult)>0) {
					while ($prow = mysql_fetch_array ($tulresult)) {
							$asaldo = $asaldo + $prow["tilattu"];
							echo "<tr><td>$prow[paivamaara]</td><td>$prow[tilattu]</td><td>$prow[otunnus]</td></tr>";
							$tilauksessa += $prow["tilattu"];
					}
				}
				echo "<tr><th>".t("Yhteensä")."</th><td>$tilauksessa</td></tr>";
				echo "</table>";
				echo "</td><td class='back' valign='top'>";
			}

			echo "<table>";
			echo "<th>".t("Korvaavat")."</th><th>".t("Kpl").".</th>";

			if (mysql_num_rows($korvaresult)==0)
			{
				echo "<tr><td>".t("Ei korvaavia")."!</td><td></td></tr>";
			}
			else
			{
				// tuote löytyi, joten haetaan sen id...
				$row    = mysql_fetch_array($korvaresult);
				$id		= $row['id'];

				$query = "select * from korvaavat where id='$id' and tuoteno<>'$tuoteno' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
				$korva2result = mysql_query($query) or pupe_error($query);

				while ($row = mysql_fetch_array($korva2result))
				{
					//hateaan vielä korvaaville niiden saldot.
					//saldot per varastopaikka
					$query = "select sum(saldo) alkusaldo from tuotepaikat where tuoteno='$row[tuoteno]' and yhtio='$kukarow[yhtio]'";
					$alkuresult = mysql_query($query) or pupe_error($query);
					$alkurow = mysql_fetch_array($alkuresult);

					//ennakkopoistot
					$query = "	SELECT sum(varattu) varattu
								FROM tilausrivi
								WHERE tyyppi in ('L','G','V') and yhtio = '$kukarow[yhtio]' and tuoteno = '$row[tuoteno]' and varattu>0";
					$varatutresult = mysql_query($query) or pupe_error($query);
					$varatutrow = mysql_fetch_array($varatutresult);

					$vapaana = $alkurow["alkusaldo"] - $varatutrow["varattu"];

					echo "<tr><td><a href='$PHP_SELF?tee=Z&tuoteno=$row[tuoteno]'>$row[tuoteno]</a></td><td>$vapaana</td></tr>";
				}

			}
			
			echo "</table></td></tr></table><br>";


			$query = "	SELECT tilausrivi.*, lasku.ytunnus, tilausrivi.varattu+tilausrivi.jt kpl, lasku.nimi, tilausrivi.toimaika, round((tilausrivi.varattu+tilausrivi.jt)*tilausrivi.hinta*(1-(tilausrivi.ale/100)),2) rivihinta
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						JOIN lasku use index (PRIMARY) ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi = 'G'
						and tilausrivi.tuoteno = '$tuoteno'
						and tilausrivi.laskutettuaika = '0000-00-00'
						and tilausrivi.varattu + tilausrivi.jt != 0
						and tilausrivi.var not in ('P')
						ORDER BY tyyppi, var";
			$jtresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($jtresult) != 0) {

				// Avoimet rivit
				echo "</td></tr><tr><td class='back' valign='top'><br><table>";

				echo "<tr>
						<th>".t("Asiakas/Toimittaja")."</th>
						<th>".t("Tilaus/Keikka")."</th>
						<th>".t("Tyyppi")."</th>
						<th>".t("Toimaika")."</th>
						<th>".t("Kpl")."</th>
						</tr>";

				// jtrivejä löytyi
				while ($jtrow = mysql_fetch_array($jtresult)) {

					$tyyppi = "";
					$merkki = "";
					$keikka = "";

					if($jtrow["tyyppi"] == "G") {
						$tyyppi = t("Varastosiirto");
						$merkki = "-";
					}
					
					echo "<tr>
							<td>$jtrow[nimi]</td>
							<td>$jtrow[otunnus] $keikka</td>";
					echo "	<td>$tyyppi</td>
							<td>".substr($jtrow["toimaika"],0,10)."</td>
							<td>$merkki".abs($jtrow["kpl"])."</td>
							</tr>";
				}

				echo "</table>";
			}
			
			echo "</td></tr><tr><td class='back' valign='top'><br>";
			echo "<table>";
			echo "<form action='$PHP_SELF#Tapahtumat' method='post'>";

			if ($historia == "") $historia=1;
			$chk[$historia] = "SELECTED";

			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";

			echo "<a href='#' name='Tapahtumat'>";

			echo "<tr>";
			echo "<th colspan='2'>".t("Näytä tapahtumat").": ";
			echo "<select name='historia' onchange='submit();'>'";
			echo "<option value='1' $chk[1]> ".t("20 viimeisintä")."</option>";
			echo "<option value='2' $chk[2]> ".t("Tilivuoden alusta")."</option>";
			echo "<option value='3' $chk[3]> ".t("Lähes kaikki")."</option>";
			echo "</select>";
			echo "</th>";


			if ($tapahtumalaji == "laskutus") 			$sel1="SELECTED";
			if ($tapahtumalaji == "tulo") 				$sel2="SELECTED";
			if ($tapahtumalaji == "valmistus") 			$sel3="SELECTED";
			if ($tapahtumalaji == "siirto") 			$sel4="SELECTED";
			if ($tapahtumalaji == "kulutus") 			$sel5="SELECTED";
			if ($tapahtumalaji == "Inventointi") 		$sel6="SELECTED";
			if ($tapahtumalaji == "Epäkurantti") 		$sel7="SELECTED";
			if ($tapahtumalaji == "poistettupaikka") 	$sel8="SELECTED";
			if ($tapahtumalaji == "uusipaikka") 		$sel9="SELECTED";

			echo "<th colspan='2'>".t("Tapahtumalaji").": ";
			echo "<select name='tapahtumalaji' onchange='submit();'>'";
			echo "<option value=''>".t("Näytä kaikki")."</option>";
			echo "<option value='laskutus' $sel1>".t("Laskutukset")."</option>";
			echo "<option value='tulo' $sel2>".t("Tulot")."</option>";
			echo "<option value='valmistus' $sel3>".t("Valmistukset")."</option>";
			echo "<option value='siirto' $sel4>".t("Siirrot")."</option>";
			echo "<option value='kulutus' $sel5>".t("Kulutukset")."</option>";
			echo "<option value='Inventointi' $sel6>".t("Inventoinnit")."</option>";
			echo "<option value='Epäkurantti' $sel7>".t("Epäkuranttiusmerkinnät")."</option>";
			echo "<option value='poistettupaikka' $sel8>".t("Poistetut tuotepaikat")."</option>";
			echo "<option value='uusipaikka' $sel9>".t("Perustetut tuotepaikat")."</option>";
			echo "</select>";
			echo "</th>";

			echo "<tr>";
			echo "<th>".t("Käyttäjä@Pvm")."</th>";
			echo "<th>".t("Tyyppi")."</th>";
			echo "<th>".t("Kpl")."</th>";
			echo "<th>".t("Selite")."";

			echo "</th></form>";
			echo "</tr>";


			//tapahtumat
			if ($historia == '1' or $historia == '') {
				$maara = "LIMIT 20";
				$ehto = ' and tapahtuma.laadittu >= date_sub(now(), interval 6 month)';
			}
			if ($historia == '2') {
				$maara = "";
				$ehto = " and tapahtuma.laadittu > '$yhtiorow[tilikausi_alku]'";
			}
			if ($historia == '3') {
				$maara = "LIMIT 2500";
				$ehto = "";
			}

			$query = "	SELECT concat_ws('@', tapahtuma.laatija, tapahtuma.laadittu) kuka, tapahtuma.laji, tapahtuma.kpl, tapahtuma.kplhinta, tapahtuma.hinta,
						if(tapahtuma.laji in ('tulo','valmistus'), tapahtuma.kplhinta, tapahtuma.hinta)*tapahtuma.kpl arvo, tapahtuma.selite, lasku.tunnus laskutunnus
						FROM tapahtuma use index (yhtio_tuote_laadittu)
						LEFT JOIN tilausrivi use index (primary) ON tilausrivi.yhtio=tapahtuma.yhtio and tilausrivi.tunnus=tapahtuma.rivitunnus
						LEFT JOIN lasku use index (primary) ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
						and tapahtuma.tuoteno = '$tuoteno'
						and tapahtuma.laadittu > '0000-00-00 00:00:00'
						and tapahtuma.laji = 'siirto'
						$ehto
						ORDER BY tapahtuma.laadittu desc $maara";
			$qresult = mysql_query($query) or pupe_error($query);

			$vararvo_nyt = sprintf('%.2f',$kokonaissaldo_tapahtumalle*$tuoterow["kehahin"]);

			while ($prow = mysql_fetch_array ($qresult)) {

				$vararvo_nyt -= $prow["arvo"];

				if ($tapahtumalaji == "" or strtoupper($tapahtumalaji)==strtoupper($prow["laji"])) {
					echo "<tr>";
					echo "<td nowrap>$prow[kuka]</td>";
					echo "<td nowrap>";

					
					
					echo t("$prow[laji]");
					

					echo "</td>";

					echo "<td nowrap align='right'>$prow[kpl]</td>";
					echo "<td>$prow[selite]</td>";
					echo "</tr>";
				}
			}
			echo "</table>";
			
			
		}
		else {
			echo "<font class='message'>".t("Yhtään tuotetta ei löytynyt")."!<br></font>";
		}
		$tee = '';
	}
	if ($tee == "Y") {
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='Z'>";
			echo "<table><tr>";
			echo "<th>".t("Valitse tuotenumero").":</th>";
			echo "<td>$ulos</td>";
			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
			echo "</tr></table>";
			echo "</form>";
	}

	require ("inc/footer.inc");
?>
