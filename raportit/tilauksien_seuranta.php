<?php

/*
	querymonster!

	ratkaistaan tilausten arvo tilaushetken mukaan.
	Eli etsit‰‰n kaikki tunnusnippujen is‰t ja lasketaan jokaisen tunnunsinpun arvo erikseen..
	Normitilauksilla haetaan vain tilauksen arvo

	Mukaan otetaan kaikki tilatut ja jo laskutetut tilaukset..

	Korjataan tilauksia joilta puuttuu seuranta..

*/

$useslave = 1;

require ("../inc/parametrit.inc");

if (!function_exists("luku")) {
	function luku($luku) {
		$ulos = number_format($luku, 2 ,',' ,' ');
		return $ulos;
	}
}

if ($toim == "TARJOUS") {
	$sallitut_tilat = "'T'";
	$tarjouslisa = " LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=t.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=t.tunnus and tilausrivin_lisatiedot.positio != 'Optio'";
	$tarjoussummalisa = " / count(distinct(otunnus))";

	echo "<font class='head'>".t("L‰hetetyt tarjoukset kaudella")."</font><hr><br>";

}
else {
	$sallitut_tilat = "'L','R','N'";
	$tarjouslisa = "";
	$tarjoussummalisa = "";

	echo "<font class='head'>".t("Toteutuneet tilaukset kaudella")."</font><hr><br>";

}

if ($tee == "LASKE") {
	echo "Suoritetaan kysely‰.. T‰ss‰ voi menn‰ hetkonen...<br>";
}

if ($tee == "KORJAA") {
	if ($tunnusnippu > 0) {
		echo "<font class='message'>".t("Korjataan projektin seurannat")."</font><br>";
		$query = "select GROUP_CONCAT(tunnus) tunnukset from lasku where yhtio='$kukarow[yhtio]' and tunnusnippu=$tunnusnippu group by tunnusnippu";
		$aresult = mysql_query($query) or pupe_error($query);
		$arow=mysql_fetch_array($aresult);
		$tunnukset=explode(",",$arow["tunnukset"]);

	}
	else {
		echo "<font class='message'>".t("Korjataan tilauksen seuranta")."</font><br>";
		$tunnukset=array($tunnus);
	}

	//	Korjataan kaikki tunnukset..
	foreach($tunnukset as $tunnus) {
		$query = "select tunnus from laskun_lisatiedot where otunnus=$tunnus";
		$atarkres = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($atarkres)>0) {
			$query = "UPDATE laskun_lisatiedot set seuranta = '$uusi_seuranta' where yhtio='$kukarow[yhtio]' and otunnus=$tunnus";
			$updres = mysql_query($query) or pupe_error($query);
		}
		else {
			$query = "INSERT INTO laskun_lisatiedot set yhtio='$kukarow[yhtio]', laatija='$kukarow[kuka]', luontiaika=now(), otunnus='$tunnus', seuranta='$uusi_seuranta'";
			$updres = mysql_query($query) or pupe_error($query);
		}

		if (mysql_affected_rows()>0) {
			echo t("Korjattii tilaus")." $tunnus<br>";
		}
	}

	$tee="";
}

//	Tarkastetaan ett‰ kaikilla on seurannat kun saavutaan t‰nne ensimm‰ist‰ kertaa..
if ($tee=="") {
	$query = "SELECT GROUP_CONCAT(distinct(selite) SEPARATOR '\',\'') seurannat from avainsana where yhtio='$kukarow[yhtio]' and laji='SEURANTA' group by laji";
	$aresult = mysql_query($query) or pupe_error($query);
	$arow = mysql_fetch_array($aresult);

	$query="SELECT lasku.tunnus, tunnusnippu, nimi, tila, alatila
			from lasku use index(tila_index)
			left join laskun_lisatiedot use index (yhtio_otunnus) on lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
			where lasku.yhtio='$kukarow[yhtio]' and tila IN ($sallitut_tilat) and (laskun_lisatiedot.seuranta NOT IN ('$arow[seurannat]') or laskun_lisatiedot.seuranta IS NULL)
			order by tunnusnippu, tunnus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result)>0) {

		$aresult = t_avainsana("SEURANTA");

		$opt="<select name='uusi_seuranta' onchange='submit();'><option value='' selected>".t("Valitse oikea seuranta")."</option>";

		while($arow=mysql_fetch_array($aresult)) {
			$opt .= "<option value='$arow[selite]'>$arow[selite] - $arow[selitetark]</option>";
		}
		$opt .= "</select>";

		echo "<font class='message'>".t("Korjataan puuttuvat seurannat")."</font><br>";
		echo "<table><tr><th>".t("Tilaus")."</th><th>".t("Tyyppi")."</th><th>".t("Asiakas")."</th><th>".t("Korjattu seuranta")."</th></tr>";

		while($row=mysql_fetch_array($result)) {
			echo "	<tr class='aktiivi'>";

			echo "<td><form method = 'post'>
								<input type='hidden' name='tee' value='KORJAA'>
								<input type='hidden' name='toim' value='$toim'>
								<input type='hidden' name='tunnus' value='$row[tunnus]'>
								<input type='hidden' name='tunnusnippu' value='$row[tunnusnippu]'>";
			$lisa=$korjattu="";
			if ($row["tunnusnippu"] > 0) {

				//	Voidaanko automaattikorjata
				$query = "SELECT seuranta from laskun_lisatiedot where otunnus=$row[tunnusnippu]";
				$tarkres = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($tarkres)==1) {
					$tarkrow=mysql_fetch_array($tarkres);
					if ($tarkrow["seuranta"] != "") {
						$query = "SELECT tunnus from laskun_lisatiedot where otunnus=$row[tunnus]";
						$atarkres = mysql_query($query) or pupe_error($query);
						if (mysql_num_rows($atarkres)>0) {
							$query = "UPDATE laskun_lisatiedot set seuranta = '$tarkrow[seuranta]' where yhtio='$kukarow[yhtio]' and otunnus=$row[tunnus]";
							$updres = mysql_query($query) or pupe_error($query);
						}
						else {
							$query = "INSERT INTO laskun_lisatiedot set yhtio='$kukarow[yhtio]', laatija='$kukarow[kuka]', luontiaika=now(), otunnus='$row[tunnus]', seuranta='$tarkrow[seuranta]'";
							$updres = mysql_query($query) or pupe_error($query);
						}

						$korjattu = t("Projektiotsikolta")." ($tarkrow[seuranta])";
					}
				}

				if ($toim != "TARJOUS") {
					$lisa ="<td class='back'>HUOM: tilaus on osa projektia '$row[tunnusnippu]'!</td>";
				}
				echo "<a href='../raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$row[tunnus]&nippu=$row[tunnusnippu]&otunnus=$row[tunnus]'>$row[tunnus]</a>";
			}
			else {
				echo "<a href='../raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$row[tunnus]'>$row[tunnus]</a>";
			}
			echo  "</td>";

			echo "<td>$row[nimi]</td>";

			$laskutyyppi=$row["tila"];
			$alatila=$row["alatila"];

			//tehd‰‰n selv‰kielinen tila/alatila
			require "inc/laskutyyppi.inc";
			echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";

			if ($korjattu != "") {
				echo "<td>$korjattu</td>";
			}
			else {
				echo "<td>$opt</td>$lisa";
			}

			echo "</form></tr>";
		}
		echo "</table>";
	}
}

if ($tee=="" or $tee=="LASKE") {
	echo "<br><table>";
	echo "<form method = 'post'>
			<input type='hidden' name='tee' value='LASKE'>
			<input type='hidden' name='toim' value='$toim'>
			<tr><th colspan='3'>".t("N‰yt‰ Myynti")."</th></tr>
			<td>".t("Ajalta")."</td>
			<td><select name='alvv'>";

	for ($i = date("Y")+1; $i >= date("Y")-6; $i--) {
		if ($alvv==$i) $sel = "selected";
		elseif ($i == date("Y") and $alvv=="") $sel = "selected";
		else $sel = "";
		echo "<option value='$i' $sel>$i</option>";
	}
	echo "</select>";

	echo "<select name='alvk'>";
	for ($i = 1; $i <= 12; $i++) {
		$i = sprintf("%02s",$i);
		if ($alvk == $i) $sel = "selected";
		else $sel = "";
		echo "<option value='$i' $sel>$i</option>";
	}
	echo "</select> - <select name='blvv'>";

	for ($i = date("Y")+1; $i >= date("Y")-6; $i--) {
		if ($blvv==$i) $sel = "selected";
		elseif ($i == date("Y") and $blvv=="") $sel = "selected";
		else $sel = "";
		echo "<option value='$i' $sel>$i</option>";
	}

	echo "</select><select name='blvk'>";
	for ($i = 1; $i <= 12; $i++) {
		$i = sprintf("%02s",$i);
		if ($blvk == $i) $sel = "selected";
		elseif ($i == date("m") and $blvk == "") $sel = "selected";
		else $sel = "";
		echo "<option value='$i' $sel>$i</option>";
	}
	echo "</select></td></tr>";

	echo "<tr><td>".t("Tai koko tilikausi")."</td>";

	$query = "	SELECT *
				FROM tilikaudet
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tilikausi_alku";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='tkausi'><option value='0'>".t("Ei valintaa")."";

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($tkausi == $vrow["tunnus"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[tunnus]' $sel>$vrow[tilikausi_alku] - $vrow[tilikausi_loppu]";
	}
	echo "</select></td></tr>";

	echo "<tr><td>".t("Vain seurannasta")."</td>";
	echo "<td><select name='seuranta'><option value=''>".t("Kaikista")."";

	$vresult = t_avainsana("SEURANTA");

	while ($vrow=mysql_fetch_array($vresult)) {
		$sel="";
		if ($seuranta == $vrow["selite"]) {
			$sel = "selected";
		}
		echo "<option value = '$vrow[selite]' $sel>$vrow[selite] - $vrow[selitetark]</option>";
	}
	echo "</select></td>";
	echo "</tr>";

	$sel = "";
	if ($nayta_tilaukset == "JOO") $sel = "checked";
	echo "<tr><td>".t("N‰yt‰ tilaukset")."</td>";
	echo "<td><input type = 'checkbox' name = 'nayta_tilaukset' value = 'JOO' $sel></td></tr>";

	echo "<tr><td><input type='submit' value='".t("Suorita raportti")."'></form></td></tr>";
	echo "</table><br>";

	$lisa = "";

	if ($tee=="LASKE") {

		$alvp = 1;
		$blvp = cal_days_in_month(CAL_GREGORIAN, $blvk, $blvv);

		if ($tkausi != '0') {
			$query = "	SELECT *
						FROM tilikaudet
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tkausi'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				echo "<font class='error'>".t("Sopivaa yrityksen tilikautta ei lˆytynyt")."</font>";
				exit;
			}
			$tilikaudetrow=mysql_fetch_array($result);

		    echo "<font class='message'>$nimi ".t("Toteutuneet tilaukset tilikaudella")." $tilikaudetrow[tilikausi_alku] - $tilikaudetrow[tilikausi_loppu]</font><hr>";
			$lisa  = "lasku.olmapvm <= '$tilikaudetrow[tilikausi_loppu]' and lasku.olmapvm >= '$tilikaudetrow[tilikausi_alku]'";
		}
		else {
			echo "<font class='message'>$nimi ".t("Toteutuneet tilaukset kaudelta")." $alvv-$alvk - $blvv-$blvk</font><hr>";
			$lisa  = "lasku.olmapvm <= '$blvv-$blvk-$blvp' and lasku.olmapvm >= '$alvv-$alvk-$alvp'";
		}

		if ($tkausi == 0) {
			$alku_vv = $alvv;
			$alku_kk = $alvk;
			$alku_pp = $alvp;
			$loppu_vv = $blvv;
			$loppu_kk = $blvk;
			$loppu_pp = $blvp;
		}
		else {
			list($alku_vv,$alku_kk, $alku_pp) = explode("-", $tilikaudetrow["tilikausi_alku"]);
			list($loppu_vv,$loppu_kk, $loppu_pp) = explode("-", $tilikaudetrow["tilikausi_loppu"]);
		}

		$summat=$tilaukset="";

		//	Montaku kuukautta t‰lle v‰lille mahtuu?
		$query = "	SELECT PERIOD_DIFF('$loppu_vv$loppu_kk', '$alku_vv$alku_kk') lkm";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);
		$kuukausia = $row[0]+1;
		$vv=$alku_vv;
		$kk=$alku_kk-1;
		$summasarake="";
		$tunnukset="";
		if ($kuukausia > 0) {

			$query_ale_lisa = generoi_alekentta('M','t');

			$sarakkeet[]="seuranta";

			for($y=1;$y<=$kuukausia;$y++) {

				$kk++;

				if ($kk > 12) {
					$kk = 1;
					$vv++;
				}

				switch ($kk) {
					case "1":
						$month = "Tammikuu";
						break;
					case "2":
						$month = "Helmikuu";
						break;
					case "3":
						$month = "Maaliskuu";
						break;
					case "4":
						$month = "Huhtikuu";
						break;
					case "5":
						$month = "Toukokuu";
						break;
					case "6":
						$month = "Kes‰kuu";
						break;
					case "7":
						$month = "Hein‰kuu";
						break;
					case "8":
						$month = "Elokuu";
						break;
					case "9":
						$month = "Syyskuu";
						break;
					case "10":
						$month = "Lokakuu";
						break;
					case "11":
						$month = "Marraskuu";
						break;
					case "12":
						$month = "Joulukuu";
						break;
				}

				if ($kuukausia>12) {
					$sarake = str_replace("kuu","",$month)."_".substr($vv,2,4);
				}
				else {
					$sarake = $month;
				}

				if ($nayta_tilaukset!="") {
					$tilaukset .= ", GROUP_CONCAT(distinct(if (left(lasku.luontiaika,7)>='$vv-".sprintf("%02s",$kk)."' and left(lasku.luontiaika,7)<='$vv-".sprintf("%02s",$kk)."', if (tunnusnippu>0,concat('*',lasku.tunnus),lasku.tunnus), NULL)))  tilaukset_$sarake\n";
				}

				$summasarake .= ", sum(if (left(lasku.luontiaika,7)>='$vv-".sprintf("%02s",$kk)."' and left(lasku.luontiaika,7)<='$vv-".sprintf("%02s",$kk)."',
										if (lasku.tunnusnippu=0,
											(
												SELECT sum(if (t.uusiotunnus=0,t.hinta * (t.varattu+t.jt) * {$query_ale_lisa},rivihinta))
												FROM lasku l use index (yhtio_tunnusnippu, tila_index)
												LEFT JOIN tilausrivi t ON t.yhtio=l.yhtio and t.otunnus=l.tunnus and t.tyyppi!='D' and tuoteno!='$yhtiorow[ennakkomaksu_tuotenumero]'
												$tarjouslisa
												WHERE l.yhtio=lasku.yhtio and l.tunnus=lasku.tunnus
											),
											(
												SELECT sum(if (t.uusiotunnus=0,t.hinta * (t.varattu+t.jt) * {$query_ale_lisa},rivihinta)) $tarjoussummalisa
												FROM lasku l use index (yhtio_tunnusnippu, tila_index)
												LEFT JOIN tilausrivi t ON t.yhtio=l.yhtio and t.otunnus=l.tunnus and t.tyyppi!='D' and tuoteno!='$yhtiorow[ennakkomaksu_tuotenumero]'
												$tarjouslisa
												WHERE l.yhtio=lasku.yhtio and l.tunnusnippu=lasku.tunnus and l.tila IN ($sallitut_tilat)
											)
											), NULL))  $sarake\n";

				$sarakkeet[]=$sarake;

			}

			$tunnukset=substr($tunnukset,0,-2);

			$lisa="";
			if ($seuranta != "") {
				$lisa .= " and seuranta = '$seuranta'";
			}

			$query = "	SELECT concat_ws(' - ', avainsana.selite, avainsana.selitetark) seuranta
						$summasarake
						$tilaukset
						FROM lasku USE INDEX(yhtio_tila_luontiaika)
						LEFT JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus
						LEFT JOIN avainsana ON laskun_lisatiedot.yhtio=avainsana.yhtio and laskun_lisatiedot.seuranta=avainsana.selite and avainsana.laji='SEURANTA'
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and left(lasku.luontiaika,7)>='$alku_vv-$alku_kk' and left(lasku.luontiaika,7)<='$loppu_vv-$loppu_kk'
						and lasku.tila IN ($sallitut_tilat)
						and (lasku.tunnusnippu=0 or lasku.tunnusnippu=lasku.tunnus)
						$lisa
						and sisainen=''
						GROUP BY seuranta";
			flush();
			$result = mysql_query($query) or pupe_error($query);
			//echo "<pre>".str_replace("\t","",$query)."</pre><br><br>";

			if (mysql_num_rows($result)>0) {
				// tehd‰‰n otsikot
				echo "<table cellpadding='2'><tr>";

				foreach($sarakkeet as $sarake) {
					echo "<th align='left'>".t(ucfirst(str_replace("_"," -",$sarake)))."</th>";
				}

				echo "<th>".t("Yhteens‰")."</th></tr>";

				$grantTotal=0;
				$sarakeTotal=array();
				while($laskurow=mysql_fetch_array($result)) {
					//echo "<pre>".print_r($laskurow,true)."</pre><br><br>";
					echo "<tr class='aktiivi'>";

					$riviTotal=0;
					foreach($sarakkeet as $sarake) {
						if ($sarake == "seuranta") {
							echo "<td>$laskurow[$sarake]</td>";
						}
						else {

							$lisa="";

							if ($nayta_tilaukset != "") {
								$abu = explode(",", $laskurow["tilaukset_$sarake"]);
								if (is_array($abu) and count($abu)>0) {
									foreach($abu as $tabu) {
										if ($tabu{0} == "*") {
											$tabu=substr($tabu,1);
											$lisa .= "<a href='../raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$tabu&nippu=$tabu&otunnus=$tabu'>$tabu</a> ";
										}
										else {
											$lisa .= "<a href='../raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$tabu'>$tabu</a> ";
										}

									}
								}
							}

							if ($lisa != "") {
								$lisa="<br><font class='info'>".trim($lisa)."</font>";
							}

							if (round($laskurow[$sarake], 2)>0) {
								echo "<td><font class='message'>".luku($laskurow[$sarake])."$lisa</font></td>";
							}
							elseif (round($laskurow[$sarake], 2)<0) {
								echo "<td><font class='error'>".luku($laskurow[$sarake])."$lisa !!!</font></td>";
							}
							else {
								echo "<td></td>";
							}

							$sarakeTotal[$sarake]+=$laskurow[$sarake];
							$riviTotal+=$laskurow[$sarake];

						}
					}

					echo "<td><font class='message'>".luku($riviTotal)."</font></td></tr>";
					$grantTotal+=$riviTotal;

				}

				echo "<tr><th>".t("Yhteens‰")."</th>";
				foreach($sarakeTotal as $summa) {
					echo "<th>".luku($summa)."</th>";
				}

				echo "<th>".luku($grantTotal)."</th></tr>";
				echo "</table>";

			}
			else {
				echo "OHO ei mit‰‰!<br>";
			}
		}
		else {
			echo "Aika v‰h‰n kuukausia!";
		}
	}
}

require ("inc/footer.inc");
