<?php
///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

if (file_exists("../inc/parametrit.inc")) {
	require ("../inc/parametrit.inc");
}
else {
	require ("parametrit.inc");
}

echo "<font class='head'>".t("Osastoseuranta tuotemerkeittäin")."</font><hr>";
echo "<p>".t("Tässä ajetaan annettu kausi ja verrataan sitä edellisen vuoden vastaavaan kauteen").".<br>";

$asiakasytunnus = "";

if ($asiakas != '' and $kukarow["extranet"] == "") {

	 $muutparametrit = $maa."#".$asos."#".$kka."#".$ppa."#".$vva."#".$kkl."#".$ppl."#".$vvl;

	 $ytunnus = $asiakas;
	 require ("../inc/asiakashaku.inc");

	if ($ytunnus != '') {
	 	if (isset($muutparametrit)) {
	 		list($maa,$asos,$kka,$ppa,$vva,$kkl,$ppl,$vvl) = explode('#', $muutparametrit);
	 	}

	 	//asiakasmuuttujassa menee asiakkaan tunnus
	 	$asiakas = $asiakasid;
		$asiakasytunnus = $ytunnus;
	}
	else {
		$tee = "";
	}
}

if ($tee == 'go') {
	$err = 0;

	$query  = "SELECT TO_DAYS('$vvl-$kkl-$ppl')-TO_DAYS('$vva-$kka-$ppa') ero";
	$result = mysql_query($query) or pupe_error($query);
	$row    = mysql_fetch_array($result);

	if ($row["ero"] > 365) {
		echo "<br><br><font class='error'>".t("Jotta homma ei menisi liian hitaaksi, niin vuosi on pisin mahdollinen laskentaväli!")."</font><br>";
		$err = 1;
	}

	if ($err == 0) {
		echo "<p>".t("ind = vertailu ed. vuoden vastaavaan kauteen")."<br>";

		if ($kukarow["extranet"] == '') {
			echo t("Osuus % = osuus osaston kokonaismyynnistä/katteesta")."<br><br>";
		}

		echo "<table>";

		echo "<tr>";
		echo "<th>$vva-$kka-$ppa / $vvl-$kkl-$ppl ".t("Osasto").": $osasto<br>$maa $asiakas $asos</th>";
		echo "<th>".t("Myynti")." $yhtiorow[valkoodi]</th>";
		echo "<th>".t("ind Myynti")."</th>";

		if ($kukarow["extranet"] == '') {
			echo "<th>".t("Osuus")." %</th>";
		}

		echo "<th>".t("Kpl")."</th>";
		echo "<th>".t("ind Kpl")."</th>";

		if ($kukarow["extranet"] == '') {
			echo "<th>".t("Kate")."</th>";
			echo "<th>".t("Kate")." %</th>";
			echo "<th>".t("ind Kate")."</th>";
			echo "<th>".t("Osuus")." %</th>";
			echo "<th>".t("Varastonarvo<")."/th>";
		}

		echo "</tr>";

		$kateyht   = 0;
		$myyntiyht = 0;

		//edellinen vuosi
		$vvaa = $vva - '1';
		$vvll = $vvl - '1';

		$lisa1 = '';
		$lisa2 = '';

		//extranet käyttäjällä on aina asiakas valittuna
		if ($kukarow["extranet"] != '') {
			$asiakas = $kukarow['oletus_asiakas'];
		}


		if ($maa != '') {
			$lisa1 = " , lasku use index (primary), asiakas use index (primary)";
			$lisa2 .= " and lasku.yhtio     = tilausrivi.yhtio
						and lasku.tunnus    = tilausrivi.uusiotunnus
						and asiakas.yhtio   = tilausrivi.yhtio
						and asiakas.tunnus  = lasku.liitostunnus
						and asiakas.maa		= '$maa' ";
		}

		if ($asiakas != '') {
			$lisa1 = " , lasku use index (primary), asiakas use index (primary)";
			$lisa2 .= " and lasku.yhtio     = tilausrivi.yhtio
						and lasku.tunnus    = tilausrivi.uusiotunnus
						and asiakas.yhtio   = tilausrivi.yhtio
						and asiakas.tunnus  = lasku.liitostunnus
						and asiakas.tunnus	= '$asiakas' ";
		}

		if ($asos != '') {
			$lisa1 = " , lasku use index (primary), asiakas use index (primary)";
			$lisa2 .= " and lasku.yhtio     = tilausrivi.yhtio
						and lasku.tunnus    = tilausrivi.uusiotunnus
						and asiakas.yhtio   = tilausrivi.yhtio
						and asiakas.tunnus  = lasku.liitostunnus
						and asiakas.osasto = '$asos' ";
		}

		$query =  "	select
					tilausrivi.osasto,
					if(tuote.tuotemerkki='','Ilman tuotemerkkiä',tuote.tuotemerkki) tuotemerkki,
					sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kate,0)) kateedyht,
					sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.rivihinta,0)) myyntiedyht,
					sum(if(tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl',tilausrivi.kpl,0)) kpledyht,
					sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',tilausrivi.kate,0)) katecuryht,
					sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',tilausrivi.rivihinta,0)) myynticuryht,
					sum(if(tilausrivi.laskutettuaika >= '$vva-$kka-$ppa'  and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl',tilausrivi.kpl,0)) kplcuryht
					from tilausrivi use index (yhtio_tyyppi_laskutettuaika), tuote use index (tuoteno_index) $lisa1
					where tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi = 'L'
					and ((tilausrivi.laskutettuaika >= '$vvaa-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvll-$kkl-$ppl') or (tilausrivi.laskutettuaika >= '$vva-$kka-$ppa' and tilausrivi.laskutettuaika <= '$vvl-$kkl-$ppl'))
					and tuote.yhtio = tilausrivi.yhtio
					and tuote.tuoteno = tilausrivi.tuoteno
					$lisa2
					GROUP by tilausrivi.osasto, tuote.tuotemerkki
					ORDER BY tilausrivi.osasto, tuote.tuotemerkki";
		$yhtresulta = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($yhtresulta) != 0) {

			//tehdään ensin summamuuttujat
			while ($yhtrow = mysql_fetch_array ($yhtresulta)) {

				if ($yhtrow["osasto"] != $edosasto) {
					$i = $yhtrow["osasto"];

					$osastomyynticuryht[$i] = 0;
					$osastokatecuryht[$i]   = 0;

					$osastomyyntiedyht[$i] = 0;
					$osastokateedyht[$i]   = 0;

					$osastokplcuryht[$i]  = 0;
					$osastokpledyht[$i]   = 0;
				}

				$osastomyynticuryht[$i] += $yhtrow['myynticuryht'];
				$osastokatecuryht[$i]   += $yhtrow['katecuryht'];

				$osastomyyntiedyht[$i] += $yhtrow['myyntiedyht'];
				$osastokateedyht[$i]   += $yhtrow['kateedyht'];

				$osastokplcuryht[$i]  += $yhtrow['kplcuryht'];
				$osastokpledyht[$i]   += $yhtrow['kpledyht'];


				$kaikkimyynticuryht += $yhtrow['myynticuryht'];
				$kaikkikatecuryht   += $yhtrow['katecuryht'];

				$kaikkimyyntiedyht += $yhtrow['myyntiedyht'];
				$kaikkikateedyht   += $yhtrow['kateedyht'];

				$kaikkikplcuryht  += $yhtrow['kplcuryht'];
				$kaikkikpledyht   += $yhtrow['kpledyht'];

				$edosasto = $yhtrow["osasto"];

			}

			//kelataan alkuun
			mysql_data_seek($yhtresulta,0);

			//ja tulostetaan kaikki rivit ruudulle
			while ($yhtrow = mysql_fetch_array ($yhtresulta)) {

				//tässä tulee yhteensärivi
				if ($yhtrow["osasto"] != $edosasto) {
					$i = $yhtrow["osasto"];

					if (($osastomyynticuryht[$i] != 0) and ($osastomyyntiedyht[$i] != 0))
						$indlvtot = sprintf('%.2f',$osastomyynticuryht[$i] / $osastomyyntiedyht[$i]);
					else $indlvtot = "n/a";

					if (($osastokplcuryht[$i] != 0) and ($osastokpledyht[$i] != 0))
						$indkpltot = sprintf('%.2f',$osastokplcuryht[$i] / $osastokpledyht[$i]);
					else $indkpltot = "n/a";

					if (($osastomyynticuryht[$i] != 0) and ($osastokatecuryht[$i] != 0))
						$kateprosyht = sprintf('%.2f',($osastokatecuryht[$i] / $osastomyynticuryht[$i]) * 100);
					else $kateprosyht = 0;

					if (($osastokatecuryht[$i] != 0) and ($osastokateedyht[$i] != 0))
						$indkatetot = sprintf('%.2f',$osastokatecuryht[$i] / $osastokateedyht[$i]);
					else $indkatetot = "n/a";


					if (($kaikkimyynticuryht != 0) and ($osastomyynticuryht[$i] != 0))
						$lvosuus = sprintf('%.2f',($osastomyynticuryht[$i] / $kaikkimyynticuryht) * 100);
					else $lvosuus = 0;

					if (($kaikkikatecuryht != 0) and ($osastokatecuryht[$i] != 0))
						$kateosuus = sprintf('%.2f',($osastokatecuryht[$i] / $kaikkikatecuryht) * 100);
					else $kateosuus = 0;

					$query = "	SELECT distinct avainsana.selite, ".avain('select')."
								FROM avainsana
								".avain('join','OSASTO_')."
								WHERE avainsana.yhtio='$kukarow[yhtio]'
								and avainsana.laji='OSASTO'
								and avainsana.selite='$yhtrow[osasto]'";
					$sresult = mysql_query($query) or pupe_error($query);
					$srow = mysql_fetch_array($sresult);

					echo "<tr>";
					echo "<td><b>$yhtrow[osasto] $srow[selitetark] yhteensä</b></td>";
					echo "<td align='right'><b>".str_replace(".",",",$osastomyynticuryht[$i])."</b></td>";
					echo "<td align='right'><b>".str_replace(".",",",$indlvtot)."</b></td>";

					if ($kukarow["extranet"] == '') {
						echo "<td align='right'><b>".str_replace(".",",",$lvosuus)."%</b></td>";
					}

					echo "<td align='right'><b>".str_replace(".",",",$osastokplcuryht[$i])."</b></td>";
					echo "<td align='right'><b>".str_replace(".",",",$indkpltot)."</b></td>";

					if ($kukarow["extranet"] == '') {
						echo "<td align='right'><b>".str_replace(".",",",$osastokatecuryht[$i])."</b></td>";
						echo "<td align='right'><b>".str_replace(".",",",$kateprosyht)."%</b></td>";
						echo "<td align='right'><b>".str_replace(".",",",$indkatetot)."</b></td>";
						echo "<td align='right'><b>".str_replace(".",",",$kateosuus)."%</b></td>";


						$query = "	SELECT sum(tuotepaikat.saldo*if(epakurantti1pvm='0000-00-00', kehahin, kehahin/2)) varasto
									FROM tuotepaikat, tuote
									WHERE tuote.tuoteno 	  = tuotepaikat.tuoteno
									and tuote.yhtio 		  = '$kukarow[yhtio]'
									and tuotepaikat.yhtio 	  = '$kukarow[yhtio]'
									and tuote.osasto 		  = '$yhtrow[osasto]'
									and tuote.ei_saldoa 	  = ''
									and tuote.epakurantti2pvm = '0000-00-00'";
						$vresult = mysql_query($query) or pupe_error($query);
						$vrow = mysql_fetch_array($vresult);

						echo "<td align='right'><b>".str_replace(".",",",sprintf('%.2f',$vrow["varasto"]))."</b></td>";

					}

					echo "</tr>";
				}

				if (($yhtrow['myynticuryht'] != 0) and ($yhtrow['myyntiedyht'] != 0))
					$indlv = sprintf('%.2f',$yhtrow['myynticuryht'] / $yhtrow['myyntiedyht']);
				else $indlv = "n/a";

				if (($osastomyynticuryht[$i] != 0) and ($yhtrow['myynticuryht'] != 0))
					$lvosuus = sprintf('%.2f',($yhtrow['myynticuryht'] / $osastomyynticuryht[$i]) * 100);
				else $lvosuus = 0;

				if (($yhtrow['kplcuryht'] != 0) and ($yhtrow['kpledyht'] != 0))
					$indkpl = sprintf('%.2f',$yhtrow['kplcuryht'] / $yhtrow['kpledyht']);
				else $indkpl = "n/a";

				if (($yhtrow['myynticuryht'] != 0) and ($yhtrow['katecuryht'] != 0))
					$katepros = sprintf('%.2f',($yhtrow['katecuryht'] / $yhtrow['myynticuryht']) * 100);
				else $katepros = 0;

				if (($yhtrow['katecuryht'] != 0) and ($yhtrow['kateedyht'] != 0))
					$indkate = sprintf('%.2f',$yhtrow['katecuryht'] / $yhtrow['kateedyht']);
				else $indkate = "n/a";

				if (($osastokatecuryht[$i] != 0) and ($yhtrow['katecuryht'] != 0))
					$kateosuus = sprintf('%.2f',($yhtrow['katecuryht'] / $osastokatecuryht[$i]) * 100);
				else $kateosuus = 0;
				echo "<tr>";
				echo "<td>--> $yhtrow[tuotemerkki]</td>";
				echo "<td align='right'>".str_replace(".",",",$yhtrow["myynticuryht"])."</td>";
				echo "<td align='right'>".str_replace(".",",",$indlv)."</td>";

				if ($kukarow["extranet"] == '') {
					echo "<td align='right'>".str_replace(".",",",$lvosuus)."%</td>";
				}

				echo "<td align='right'>".str_replace(".",",",$yhtrow["kplcuryht"])."</td>";
				echo "<td align='right'>".str_replace(".",",",$indkpl)."</td>";

				if ($kukarow["extranet"] == '') {
					echo "<td align='right'>".str_replace(".",",",$yhtrow["katecuryht"])."</td>";
					echo "<td align='right'>".str_replace(".",",",$katepros)."%</td>";
					echo "<td align='right'>".str_replace(".",",",$indkate)."</td>";
					echo "<td align='right'>".str_replace(".",",",$kateosuus)."%</td>";

					$query = "	SELECT sum(tuotepaikat.saldo*if(epakurantti1pvm='0000-00-00', kehahin, kehahin/2)) varasto
								FROM tuotepaikat, tuote
								WHERE tuote.tuoteno 	  = tuotepaikat.tuoteno
								and tuote.yhtio 		  = '$kukarow[yhtio]'
								and tuotepaikat.yhtio 	  = '$kukarow[yhtio]'
								and tuote.osasto 		  = '$yhtrow[osasto]'
								and tuote.try 			  = '$yhtrow[try]'
								and tuote.ei_saldoa 	  = ''
								and tuote.epakurantti2pvm = '0000-00-00'";
					$vresult = mysql_query($query) or pupe_error($query);
					$vrow = mysql_fetch_array($vresult);

					echo "<td align='right'>".str_replace(".",",",sprintf('%.2f',$vrow["varasto"]))."</td>";

				}

				echo "</tr>";

				$edosasto = $yhtrow["osasto"];
			}
			
			
			if ($kaikkimyynticuryht != 0 and $kaikkimyyntiedyht != 0)
				$indlv = sprintf('%.2f',$kaikkimyynticuryht / $kaikkimyyntiedyht);
			else $indlv = "n/a";
			
			echo "<tr>";
			echo "<th>Yhteensä</th>";
			echo "<th align='right'>".str_replace(".",",",$kaikkimyynticuryht)."</th>";
			echo "<th align='right'>".str_replace(".",",",$indlv)."</th>";

			if ($kukarow["extranet"] == '') {
				echo "<th></th>";
			}

			if ($kaikkikplcuryht != 0 and $kaikkikpledyht != 0)
				$indlv = sprintf('%.2f',$kaikkikplcuryht / $kaikkikpledyht);
			else $indlv = "n/a";

			echo "<th align='right'>".str_replace(".",",",$kaikkikplcuryht)."</th>";
			echo "<th align='right'>".str_replace(".",",",$indlv)."</th>";

			if ($kukarow["extranet"] == '') {

				if ($kaikkikatecuryht != 0 and $kaikkikateedyht != 0)
					$indlv = sprintf('%.2f',$kaikkikatecuryht / $kaikkikateedyht);
				else $indlv = "n/a";

				if ($kaikkimyynticuryht != 0 and $kaikkikatecuryht != 0)
					$katepros = sprintf('%.2f',($kaikkikatecuryht / $kaikkimyynticuryht) * 100);
				else $katepros = 0;

				echo "<th align='right'>".str_replace(".",",",$kaikkikatecuryht)."</th>";
				echo "<th align='right'>".str_replace(".",",",$katepros)."%</th>";
				echo "<th align='right'>".str_replace(".",",",$indlv)."</th>";
				echo "<th align='right'>".str_replace(".",",","")."</th>";
				echo "<th align='right'>".str_replace(".",",","")."</th>";
			}

			echo "</tr>";
			
			echo "</table><br>";
		}
		else {
			echo "<font class='error'>".t("Yhtään riviä ei löytynyt")."!</font><br>";
		}
	}
}

//Käyttöliittymä

if (!isset($kka))
	$kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva))
	$vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa))
	$ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

if (!isset($kkl))
	$kkl = date("m");
if (!isset($vvl))
	$vvl = date("Y");
if (!isset($ppl))
	$ppl = date("d");

echo "<br>";
echo "<form method='post' action='$PHP_SELF'>";
echo "<input type='hidden' name='tee' value='go'>";

echo "<table>";

echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppa' value='$ppa' size='3'></td>
		<td><input type='text' name='kka' value='$kka' size='3'></td>
		<td><input type='text' name='vva' value='$vva' size='5'></td>
		</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
		<td><input type='text' name='ppl' value='$ppl' size='3'></td>
		<td><input type='text' name='kkl' value='$kkl' size='3'></td>
		<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

if ($kukarow["extranet"] == '') {
	echo "<tr><th>".t("Valitse asiakkaan maa (jos et, ajetaan kaikki yhteensä)")."</th><td colspan = '3'>";

	$query = "	SELECT distinct b.nimi, a.maa
				FROM asiakas a, maat b
				WHERE a.yhtio='$kukarow[yhtio]' and a.maa = b.koodi order by 1";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='maa'>";
	echo "<option value=''>".t("Kaikki maat")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($maa == $srow[1]) {
			$sel = "selected";
		}
		echo "<option value='$srow[1]' $sel>$srow[0]</option>";
	}
	echo "</select>";


	echo "</td></tr>
			<tr><th>".t("tai asiakkaan osasto (jos et, ajetaan kaikki yhteensä)")."</th><td colspan = '3'>";

	$query = "	SELECT distinct osasto
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' and osasto != '' order by 1";
	$asosresult = mysql_query($query) or pupe_error($query);

	echo "<select name='asos'>";
	echo "<option value=''>".t("Kaikki osastot")."</option>";

	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($asos == $asosrow["osasto"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[osasto]' $sel2>$asosrow[osasto]</option>";
	}
	echo "</select>";

	if ($asiakasytunnus != "") $asiakas = $asiakasytunnus;
	
	echo "</td></tr>
			<tr><th>".t("tai anna asiakkaan ytunnus (jos et, ajetaan kaikki yhteensä)")."</th><td colspan = '3'><input type='text' name='asiakas' value='$asiakas' size='15'></td></tr>";
}

echo "</table>";
echo "<br>";

echo "<input type='submit' value='".t("Aja raportti")."'>";
echo "</form>";

if (file_exists("../inc/footer.inc")) {
	require ("../inc/footer.inc");
}
else {
	require ("footer.inc");
}

?>