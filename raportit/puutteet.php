<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Puutelistaus")."</font><hr>";

	if ($tee != '') {

		if ($try != '') {
			$query = "	SELECT
						round(sum(if(tilausrivi.var='P', tilausrivi.tilkpl*tilausrivi.hinta*(1-(tilausrivi.ale/100))/(1+(tilausrivi.alv/100)), 0)),2) puuteeur,
						sum(if(tilausrivi.var='P', tilausrivi.tilkpl, 0)) puutekpl,
						round(sum(if((tilausrivi.var='' or tilausrivi.var='H'), tilausrivi.tilkpl*tilausrivi.hinta*(1-(tilausrivi.ale/100))/(1+(tilausrivi.alv/100)), 0)),2) myyeur,
						tilausrivi.osasto, tilausrivi.try, tilausrivi.tuoteno, tilausrivi.nimitys, lasku.ytunnus
						FROM tilausrivi
						LEFT JOIN lasku ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
						LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno
						WHERE tilausrivi.yhtio 	= '$kukarow[yhtio]'
						and tilausrivi.laadittu >= '$vva-$kka-$ppa 00:00:00'
						and tilausrivi.laadittu <= '$vvl-$kkl-$ppl 23:59:59'
						and tilausrivi.var in ('P','H','')
						and tilausrivi.osasto 	= '$osasto'
						and tilausrivi.try		= '$try'
						and tilausrivi.tyyppi	='L'
						and tuote.status NOT IN ('P','X')
						GROUP BY tilausrivi.osasto, tilausrivi.try, tilausrivi.tuoteno, tilausrivi.nimitys, lasku.ytunnus
						HAVING puuteeur <> 0
						ORDER BY tilausrivi.osasto, tilausrivi.try, tilausrivi.tuoteno, tilausrivi.nimitys, lasku.ytunnus";
		}
		else {
			$query = "	SELECT tilausrivi.osasto, tilausrivi.try,
						round(sum(if(tilausrivi.var='P', tilausrivi.tilkpl*tilausrivi.hinta*(1-(tilausrivi.ale/100))/(1+(tilausrivi.alv/100)), 0)),2) puuteeur,
						sum(if(tilausrivi.var='P', tilausrivi.tilkpl, 0)) puutekpl,
						round(sum(if(tilausrivi.var='' or tilausrivi.var='H', tilausrivi.tilkpl*tilausrivi.hinta*(1-(tilausrivi.ale/100))/(1+(tilausrivi.alv/100)), 0)),2) myyeur
						FROM tilausrivi
						LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno
						WHERE tilausrivi.yhtio='$kukarow[yhtio]'
						and tilausrivi.tyyppi='L'
						and tilausrivi.laadittu >='$vva-$kka-$ppa 00:00:00'
						and tilausrivi.laadittu <='$vvl-$kkl-$ppl 23:59:59'
						and tilausrivi.var in ('P','H','')
						and tuote.status NOT IN ('P','X')
						GROUP BY tilausrivi.osasto, tilausrivi.try
						ORDER BY tilausrivi.osasto, tilausrivi.try";
		}
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>
				<th>".t("Osasto")."</th>
				<th>	".t("Tuoteryhmä")."</th>";
		if ($try != '') {
			echo "<th>".t("Asiakas")."</td>";
			echo "<th nowrap>".t("Tuotenumero")."</td>";
			echo "<th>".t("Nimitys")."</td>";
		}
		echo "	<th nowrap>".t("Puute kpl")."</th>
				<th nowrap>".t("Puute")." $yhtiorow[valkoodi]</th>
				<th nowrap>".t("Myynti")." $yhtiorow[valkoodi]</th>
				<th nowrap>".t("Puute")." %</th>";

		if ($try != '') {
			echo "<th>".t("Tilkpl")."</td>";
			echo "<th>".t("Korvaava (saldo)")."</td>";
			echo "<th>".t("Tähtituote")."</td>";
			echo "<th>".t("Hinnastoon")."</td>";
			echo "<th>".t("Status")."</td>";
			echo "<th>".t("Toimittajan tuoteno")."</td>";
			echo "<th>".t("Toimittaja")."</td>";
		}
		echo "</tr>";

		$puuteyht		= 0;
		$puutekplyht	= 0;
		$myyntiyht		= 0;
		$puuteprosyht	= 0;
		$edosasto		= '';
		$lask			= 1;
		$ospuute		= 0;
		$ospuutekpl		= 0;
		$osmyynti		= 0;

		while ($row = mysql_fetch_array($result)) {

			if ($row["osasto"] != $edosasto and $lask > 1) {
				if ($osmyynti > 0)
					$ospuutepros = round($ospuute/($ospuute+$osmyynti)*100,2);
				else
					$ospuutepros = 100;

				echo "<tr>
						<th class='back' colspan='2'>".t("Osasto")." $edosasto ".t("yhteensä").":</th>
						<th style='text-align:right'>".str_replace(".",",",sprintf("%.2f",$ospuutekpl))."</th>
						<th style='text-align:right'>".str_replace(".",",",sprintf("%.2f",$ospuute))."</th>
						<th style='text-align:right'>".str_replace(".",",",sprintf("%.2f",$osmyynti))."</th>
						<th style='text-align:right'>".str_replace(".",",",sprintf("%.2f",$ospuutepros))."</th>
						</tr>";

				$ospuute  		= 0;
				$ospuutekpl		= 0;
				$osmyynti 		= 0;
				$ospuutepros	= 0;
			}

			$ospuute+=$row["puuteeur"];
			$osmyynti+=$row["myyeur"];
			$ospuutekpl+=$row["puutekpl"];

			if ($row["myyeur"] > 0)
				$puutepros = round($row["puuteeur"]/($row["puuteeur"]+$row["myyeur"])*100,2);
			else
				$puutepros = 100;

			if ($puutepros == 0) {
				$vari = "spec";
			}
			else {
				$vari = "";
			}

			echo "<tr>
				<td class='$vari'>$row[osasto]</td>";

			if ($try=='') {
				echo "<td class='$vari'><a href='$PHP_SELF?tee=go&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&osasto=$row[osasto]&try=$row[try]'>$row[try]</a></td>";
			}
			else {
				echo "<td class='$vari'>$row[try]</td>";
				echo "<td class='$vari'><a href='asiakasinfo.php?ytunnus=$row[ytunnus]'>$row[ytunnus]</a></td>";
				echo "<td class='$vari'><a href='../tuote.php?tuoteno=$row[tuoteno]&tee=Z'>$row[tuoteno]</td>";
				echo "<td class='$vari'>".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."</td>";
			}

			echo "<td style='text-align:right' class='$vari'>".str_replace(".",",",$row['puutekpl'])."</td>
				<td style='text-align:right' class='$vari'>".str_replace(".",",",$row['puuteeur'])."</td>
				<td style='text-align:right' class='$vari'>".str_replace(".",",",$row['myyeur'])."</td>
				<td style='text-align:right' class='$vari'>".str_replace(".",",",sprintf("%.2f",$puutepros))."</td>";

			if ($try!='') {
				//tilauksessa olevat
				$query = "	SELECT sum(varattu) tilattu
							FROM tilausrivi
							WHERE yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and varattu>0 and tyyppi='O'";
				$tulresult = mysql_query($query) or pupe_error($query);
				$tulrow = mysql_fetch_array($tulresult);
				echo "<td class='$vari'>$tulrow[tilattu]</td>";

				$query = "	SELECT tahtituote, hinnastoon, status,
							group_concat(tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno,
							group_concat(tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '<br>') toimittaja
							FROM tuote
							LEFT JOIN tuotteen_toimittajat USING (yhtio, tuoteno)
							WHERE tuote.yhtio='$kukarow[yhtio]' and tuote.tuoteno='$row[tuoteno]'
							GROUP BY 1,2,3";
				$tuoteresult = mysql_query($query) or pupe_error($query);
				$tuoterow = mysql_fetch_array($tuoteresult);

				///* Korvaavat tuotteet *///
				$query  = "select * from korvaavat where tuoteno='$row[tuoteno]' and yhtio='$kukarow[yhtio]'";
				$korvaresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($korvaresult) > 0) {
					// tuote löytyi, joten haetaan sen id...
					$korvarow = mysql_fetch_array($korvaresult);

					$query = "select * from korvaavat where id='$korvarow[id]' and tuoteno<>'$row[tuoteno]' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
					$korva2result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($korva2result) > 0) {
						while ($krow2row = mysql_fetch_array($korva2result)) {
							//hateaan vielä korvaaville niiden saldot.
							//saldot per varastopaikka
							$query = "select sum(saldo) alkusaldo from tuotepaikat where tuoteno='$krow2row[tuoteno]' and yhtio='$kukarow[yhtio]'";
							$alkuresult = mysql_query($query) or pupe_error($query);
							$alkurow = mysql_fetch_array($alkuresult);

							//ennakkopoistot
							$query = "	SELECT sum(varattu) varattu
										FROM tilausrivi
										WHERE tyyppi = 'L' and yhtio = '$kukarow[yhtio]' and tuoteno = '$krow2row[tuoteno]' and varattu>0";
							$varatutresult = mysql_query($query) or pupe_error($query);
							$varatutrow = mysql_fetch_array($varatutresult);

							$vapaana = $alkurow["alkusaldo"] - $varatutrow["varattu"];

							echo "<td class='$vari'>$krow2row[tuoteno] ($vapaana)</td>";
						}
					}
				}
				else {
					echo "<td class='$vari'>".t("Ei korvaavia")."!</td>";
				}
				echo "<td class='$vari'>$tuoterow[tahtituote]</td>";
				echo "<td class='$vari'>$tuoterow[hinnastoon]</td>";
				echo "<td class='$vari'>$tuoterow[status]</td>";
				echo "<td class='$vari'>$tuoterow[toim_tuoteno]</td>";
				echo "<td class='$vari'>$tuoterow[toimittaja]</td>";
			}

			echo "</tr>";

			$lask++;
			$puuteyht		+= $row["puuteeur"];
			$puutekplyht	+= $row["puutekpl"];
			$myyntiyht		+= $row["myyeur"];
			$puuteprosyht	+= $puutepros;
			$edosasto 		= $row["osasto"];
		}

		if ($try == '') {
			// vika osasto yhteensä
			if ($osmyynti > 0)
				$ospuutepros = round($ospuute/($ospuute+$osmyynti)*100,2);
			else
				$ospuutepros = 100;

			echo "<tr>
					<th colspan='2'>".t("Osasto")." $edosasto ".t("yhteensä").":</th>
					<th style='text-align:right'>".str_replace(".",",",sprintf("%.2f",$ospuutekpl))."</th>
					<th style='text-align:right'>".str_replace(".",",",sprintf("%.2f",$ospuute))."</th>
					<th style='text-align:right'>".str_replace(".",",",sprintf("%.2f",$osmyynti))."</th>
					<th style='text-align:right'>".str_replace(".",",",sprintf("%.2f",$ospuutepros))."</th>
					</tr>";

			//tähän tullee nyt keskiarvo
			$puuteprosyht = round($puuteyht/($puuteyht+$myyntiyht)*100,2);

			echo "<tr>
					<th class='back' colspan='2'>".t("Kaikki yhteensä").":</th>
					<th style='text-align:right'>".str_replace(".",",",sprintf("%.2f",$puutekplyht))."</th>
					<th style='text-align:right'>".str_replace(".",",",sprintf("%.2f",$puuteyht))."</th>
					<th style='text-align:right'>".str_replace(".",",",sprintf("%.2f",$myyntiyht))."</th>
					<th style='text-align:right'>".str_replace(".",",",sprintf("%.2f",$puuteprosyht))."</th>
					</tr>";
		}

		echo "</table>";
	}


	//Käyttöliittymä
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

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
	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";

	require ("../inc/footer.inc");

?>