<?php

	require ("inc/parametrit.inc");

	if ($toim == "" or $toim == "super") {
		$otsikko = t("myyntitilausta");
	}
	elseif ($toim == "ennakko") {
		$otsikko = t("ennakkotilausta");
	}
	elseif ($toim == "TYOMAARAYS") {
		$otsikko = t("työmääräystä");
	}
	elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
		$otsikko = t("valmistusta");
	}
	elseif ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER") {
		$otsikko = t("varastosiirtoa");
	}
	elseif ($toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER" or $toim == "MYYNTITILITOIMITA") {
		$otsikko = t("myyntitiliä");
	}
	elseif ($toim == "TARJOUS") {
		$otsikko = t("tarjousta");
	}
	elseif ($toim == "LASKUTUSKIELTO") {
		$otsikko = t("laskutuskieltoa");
	}
	elseif ($toim == "EXTRANET") {
		$otsikko = t("extranet-tilausta");
	}

	echo "<font class='head'>".t("Muokkaa")." $otsikko:<hr></font>";

	// Tehdään popup käyttäjän lepäämässä olevista tilauksista
	if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER" or $toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER") {
		$query = "	SELECT *
					FROM lasku use index (tila_index)
					WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'G'";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
		$query = "	SELECT *
					FROM lasku use index (tila_index)
					WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'V'";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='A' and alatila='' and tilaustyyppi='A'";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	elseif ($toim == "TARJOUS" or $toim == "TARJOUSSUPER") {
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='T' and alatila in ('','A') and tilaustyyppi='T'";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	elseif (strtoupper($toim) == "ENNAKKO") {
		$query = "	SELECT lasku.*
					FROM lasku, tilausrivi
					WHERE lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
					and lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]')  and lasku.tila='E' and lasku.alatila in ('','A','J') and lasku.tilaustyyppi = 'E' and tilausrivi.tyyppi = 'E'
					GROUP BY lasku.tunnus";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	elseif ($toim == "" or $toim == "super") {
		$query = "	SELECT *
					FROM lasku use index (tila_index)
					WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]') and alatila='' and tila in ('N','E')";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	elseif ($toim == "LASKUTUSKIELTO") {
		$query = "	SELECT lasku.*
					FROM lasku use index (tila_index)
					JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus and lasku.chn = '999'
					WHERE lasku.yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]') and tila in ('N','L') and alatila != 'X'";
		$eresult = mysql_query($query) or pupe_error($query);
	}

	if ($toim != "MYYNTITILITOIMITA" and $toim != "EXTRANET") {
		if (mysql_num_rows($eresult) > 0) {
			// tehdään aktivoi nappi.. kaikki mitä näytetään saa aktvoida, joten tarkkana queryn kanssa.
			if ($toim == "" or $toim == "super" or $toim == "ennakko" or $toim == "LASKUTUSKIELTO") {
				$aputoim1 = "RIVISYOTTO";
				$aputoim2 = "PIKATILAUS";

				$lisa1 = t("Rivisyöttöön");
				$lisa2 = t("Pikatilaukseen");
			}
			elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
				$aputoim1 = "VALMISTAASIAKKAALLE";
				$lisa1 = t("Muokkaa");

				$aputoim2 = "";
				$lisa2 = "";
			}
			elseif ($toim == "MYYNTITILISUPER") {
				$aputoim1 = "MYYNTITILI";
				$lisa1 = t("Muokkaa");

				$aputoim2 = "";
				$lisa2 = "";
			}
			elseif ($toim == "SIIRTOLISTASUPER") {
				$aputoim1 = "SIIRTOLISTA";
				$lisa1 = t("Muokkaa");

				$aputoim2 = "";
				$lisa2 = "";
			}
			else {
				$aputoim1 = $toim;
				$aputoim2 = "";

				$lisa1 = t("Muokkaa");
				$lisa2 = "";
			}

			echo "<table>
					<tr><form method='post' action='tilauskasittely/tilaus_myynti.php'>
					<input type='hidden' name='toim' value='$aputoim1'>
					<input type='hidden' name='tee' value='AKTIVOI'>
					<th>".t("Kesken olevat tilauksesi").":</th>
					<td class='back'><select name='tilausnumero'>";

			while ($row=mysql_fetch_array($eresult)) {
				$select="";
				//valitaan keskenoleva oletukseksi..
				if ($row['tunnus'] == $kukarow["kesken"]) {
					$select="SELECTED";
				}
				echo "<option value='$row[tunnus]' $select>$row[tunnus]: $row[nimi] ($row[luontiaika])</option>";
			}

			echo "</select></td>";

			if ($toim == "" or $toim == "super" or $toim == "ennakko" or $toim == "LASKUTUSKIELTO") {
				echo "<td class='back'><input type='submit' name='$aputoim2' value='$lisa2'></td>";
			}

			echo "<td class='back'><input type='submit' name='$aputoim1' value='$lisa1'></td>";
			echo "</form>
					</tr>
					</table>";
		}
		else {
			echo t("Sinulla ei ole aktiivisia eikä kesken olevia tilauksia").".<br>";
		}
	}


	// Näytetään muuten vaan sopivia tilauksia
	echo "<br><form action='$PHP_SELF' method='post'>
			<input type='hidden' name='toim' value='$toim'>
			<font class='head'>".t("Etsi")." $otsikko:<hr></font>
			".t("Syötä tilausnumero, nimen tai laatijan osa").":
			<input type='text' name='etsi'>
			<input type='Submit' value = '".t("Etsi")."'>
			</form>";

	// pvm 30 pv taaksepäin
	$dd = date("d",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
	$mm = date("m",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
	$yy = date("Y",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

	$haku='';
	if (is_string($etsi))  $haku="and (lasku.nimi like '%$etsi%' or lasku.laatija like '%$etsi%')";
	if (is_numeric($etsi)) $haku="and (lasku.tunnus like '$etsi%' or lasku.ytunnus like '$etsi%')";

	// Etsitään muutettavaa tilausta
	if ($toim=='super') {
		$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, luontiaika, laatija, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila in ('L', 'N') and alatila in ('a','b','c','d','e','j','e','t','') $haku
					order by luontiaika desc
					LIMIT 50";

		// haetaan kaikkien avoimien tilausten arvo
		$sumquery = "	SELECT round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.varattu+tilausrivi.kpl)/if('$yhtiorow[alv_kasittely]'='',1+(tilausrivi.alv/100),1)),2) arvo, count(distinct lasku.tunnus) kpl
						FROM lasku use index (yhtio_tila_tapvm)
						JOIN tilausrivi on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and
						tila in ('L', 'N') and
						tapvm = '0000-00-00' and
						alatila in ('a','b','c','d','e','j','e','t','')";
		$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
		$sumrow = mysql_fetch_array($sumresult);

		$miinus = 2;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim=='ennakko') {
		$query = "	SELECT lasku.tunnus tilaus, nimi asiakas, ytunnus, luontiaika, lasku.laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index), tilausrivi
					WHERE lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
					and lasku.yhtio = '$kukarow[yhtio]' and tila='E' and tilausrivi.tyyppi = 'E' $haku
					GROUP BY lasku.tunnus
					order by luontiaika desc
					LIMIT 50";

		$miinus = 2;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim == "SIIRTOLISTA") {
		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='G' and alatila in ('','A','B','J') $haku
					order by luontiaika desc
					LIMIT 50";

		$miinus = 2;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim == "MYYNTITILI") {
		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='G' and tilaustyyppi = 'M' and alatila in ('','A','B','J') $haku
					order by luontiaika desc
					LIMIT 50";

		$miinus = 2;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim == "SIIRTOLISTASUPER") {
		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='G' and alatila in ('','A','B','C','J','T') $haku
					order by luontiaika desc
					LIMIT 50";

		$miinus = 2;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim == "MYYNTITILISUPER") {
		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='G' and tilaustyyppi = 'M' and alatila in ('','A','B','C','J') $haku
					order by luontiaika desc
					LIMIT 50";

		$miinus = 2;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim == "MYYNTITILITOIMITA") {
		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='G' and tilaustyyppi = 'M' and alatila = 'V' $haku
					order by luontiaika desc
					LIMIT 50";

		$miinus = 2;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim=='VALMISTUS') {
		$query = "	SELECT tunnus tilaus, nimi vastaanottaja, ytunnus, luontiaika, laatija, viesti tilausviite, alatila, tila, tilaustyyppi
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='V' and alatila in ('','A','B','J') $haku
					order by luontiaika desc
					LIMIT 50";

		$miinus = 3;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim == "VALMISTUSSUPER") {
		$query = "	SELECT tunnus tilaus, nimi vastaanottaja, ytunnus, luontiaika, laatija, viesti tilausviite, alatila, tila, tilaustyyppi
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='V' and alatila in ('','A','B','C','J') $haku
					order by luontiaika desc
					LIMIT 50";

		$miinus = 3;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim == "TYOMAARAYS") {
		$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, luontiaika, laatija, alatila, tila
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tila in ('A','L','N') and tilaustyyppi='A' and alatila in ('','A','B','C','J') $haku
					order by luontiaika desc
					LIMIT 50";

		$miinus = 2;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim == "TYOMAARAYSSUPER") {
		$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, luontiaika, laatija, alatila, tila
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tila in ('A','L','N') and alatila in ('','A','B','C','J') and tilaustyyppi='A' $haku
					order by luontiaika desc
					LIMIT 50";

		$miinus = 2;
		//HUOMATKAA LIMITTI!
	}

	elseif ($toim == "TARJOUS") {
		$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, luontiaika,
					if(date_add(luontiaika, interval 14 day) >= now(), '<font color=\'#00FF00\'>Voimassa</font>', '<font color=\'#FF0000\'>Erääntynyt</font>') voimassa,
					DATEDIFF(luontiaika, date_sub(now(), INTERVAL 14 day)) pva,
					laatija, alatila, tila
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('','A') $haku
					ORDER BY tunnus desc
					LIMIT 50";
		$miinus = 2;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim == "TARJOUSSUPER") {
		$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, luontiaika, laatija, alatila, tila
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('A','','X') $haku
					order by luontiaika desc
					LIMIT 50";

		$miinus = 2;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim == "EXTRANET") {
		$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, luontiaika, laatija, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = 'N' and alatila = 'F' $haku
					order by luontiaika desc";

		$miinus = 2;
	}
	elseif ($toim == "LASKUTUSKIELTO") {
		$query = "	SELECT lasku.tunnus tilaus, nimi asiakas, ytunnus, luontiaika, laatija, alatila, tila
					FROM lasku
					JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus and lasku.chn = '999'
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila in ('N','L') and alatila != 'X' $haku
					order by luontiaika desc";

		$miinus = 2;
	}
	else {
		$query = "	SELECT lasku.tunnus tilaus, lasku.nimi asiakas, lasku.ytunnus, lasku.luontiaika, lasku.laatija, lasku.alatila, lasku.tila, kuka.extranet extra
					FROM lasku use index (tila_index)
					LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka
					WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in('L','N') and lasku.alatila in('A','') $haku
					HAVING extra = '' or extra is null
					order by luontiaika desc
					LIMIT 50";

		$miinus = 3;
		//HUOMATKAA LIMITTI!
	}
	$result = mysql_query($query) or pupe_error($query);


	if (mysql_num_rows($result)!=0) {

		echo "<table border='0' cellpadding='2' cellspacing='1'>";

		echo "<tr>";

		for ($i=0; $i < mysql_num_fields($result)-$miinus; $i++) {
			echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
		}
		echo "<th align='left'>".t("tyyppi")."</th></tr>";

		while ($row = mysql_fetch_array($result)) {

			$piilotarivi = "";

			// jos kyseessä on "odottaa JT tuotteita rivi"
			if ($row["tila"] == "N" and $row["alatila"] == "T") {
				$query = "select tunnus from tilausrivi where yhtio='$kukarow[yhtio]' and tyyppi='L' and otunnus='$row[tilaus]'";
				$countres = mysql_query($query) or pupe_error($query);

				// ja sillä ei ole yhtään riviä
				if (mysql_num_rows($countres) == 0) {
					$piilotarivi = "kylla";
				}
			}

			if ($piilotarivi == "") {
				echo "<tr>";

				for ($i=0; $i<mysql_num_fields($result)-$miinus; $i++) {
					echo "<td>$row[$i]</td>";
				}

				$laskutyyppi=$row["tila"];
				$alatila=$row["alatila"];

				//tehdään selväkielinen tila/alatila
				require "inc/laskutyyppi.inc";

				echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";

				// tehdään aktivoi nappi.. kaikki mitä näytetään saa aktvoida, joten tarkkana queryn kanssa.
				if ($toim == "" or $toim == "super" or $toim == "EXTRANET" or $toim == "ennakko" or $toim == "LASKUTUSKIELTO") {
					$aputoim1 = "RIVISYOTTO";
					$aputoim2 = "PIKATILAUS";

					$lisa1 = t("Rivisyöttöön");
					$lisa2 = t("Pikatilaukseen");
				}
				elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
					$aputoim1 = "VALMISTAASIAKKAALLE";
					$lisa1 = t("Muokkaa");

					$aputoim2 = "";
					$lisa2 = "";
				}
				elseif ($toim == "MYYNTITILISUPER" or $toim == "MYYNTITILITOIMITA") {
					$aputoim1 = "MYYNTITILI";
					$lisa1 = t("Muokkaa");

					$aputoim2 = "";
					$lisa2 = "";
				}
				elseif ($toim == "SIIRTOLISTASUPER") {
					$aputoim1 = "SIIRTOLISTA";
					$lisa1 = t("Muokkaa");

					$aputoim2 = "";
					$lisa2 = "";
				}
				else {
					$aputoim1 = $toim;
					$aputoim2 = "";

					$lisa1 = t("Muokkaa");
					$lisa2 = "";
				}

				echo "	<form method='post' action='tilauskasittely/tilaus_myynti.php'>
						<input type='hidden' name='toim' value='$aputoim1'>
						<input type='hidden' name='tee' value='AKTIVOI'>
						<input type='hidden' name='tilausnumero' value='$row[tilaus]'>";

				if ($toim == "" or $toim == "super" or $toim == "EXTRANET" or $toim == "ennakko" or $toim == "LASKUTUSKIELTO") {
					echo "<td class='back'><input type='submit' name='$aputoim2' value='$lisa2'></td>";
				}

				echo "<td class='back'><input type='submit' name='$aputoim1' value='$lisa1'></td>";
				echo "</form>";
				echo "</tr>";
			}
		}

		echo "</table>";

		if (is_array($sumrow)) {
			echo "<br><table cellpadding='5'><tr>";
			echo "<th>".t("Tilausten arvo yhteensä")." ($sumrow[kpl] ".t("kpl")."): </th>";
			echo "<td>$sumrow[arvo] $yhtiorow[valkoodi]</td>";
			echo "</tr></table>";
		}

	}
	else {
		echo t("Ei tilauksia")."...";
	}

	require ("inc/footer.inc");
?>
