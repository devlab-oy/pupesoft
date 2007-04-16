<?php

	require ("inc/parametrit.inc");

	$toim = strtoupper($toim);

	if ($toim == "" or $toim == "SUPER") {
		$otsikko = t("myyntitilausta");
	}
	elseif ($toim == "ENNAKKO") {
		$otsikko = t("ennakkotilausta");
	}
	elseif ($toim == "TYOMAARAYS") {
		$otsikko = t("työmääräystä");
	}
	elseif ($toim == "SIIRTOTYOMAARAYS") {
		$otsikko = t("sisäistä työmääräystä");
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
	elseif ($toim == "OSTO" or $toim == "OSTOSUPER") {
		$otsikko = t("osto-tilausta");
	}
	elseif ($toim == "YLLAPITO") {
		$otsikko = t("ylläpitosopimusta");
	}
	else {
		$otsikko = t("myyntitilausta");
		$toim = "";
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
	elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
		$query = "	SELECT *
					FROM lasku use index (tila_index)
					WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'S'";
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
	elseif ($toim == "OSTO") {
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='O' and alatila = ''";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	elseif ($toim == "OSTOSUPER") {
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='O' and alatila = 'A'";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	elseif ($toim == "ENNAKKO") {
		$query = "	SELECT lasku.*
					FROM lasku use index (tila_index), tilausrivi use index (yhtio_otunnus)
					WHERE lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
					and lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]')  and lasku.tila='E' and lasku.alatila in ('','A','J') and lasku.tilaustyyppi = 'E' and tilausrivi.tyyppi = 'E'
					GROUP BY lasku.tunnus";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	elseif ($toim == "" or $toim == "SUPER") {
		$query = "	SELECT *
					FROM lasku use index (tila_index)
					WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]') and alatila='' and tila in ('N','E')";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	elseif ($toim == "LASKUTUSKIELTO") {
		$query = "	SELECT lasku.*
					FROM lasku use index (tila_index)
					JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus and lasku.chn = '999'
					WHERE lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]') and tila in ('N','L') and alatila != 'X'";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	elseif ($toim == "YLLAPITO") {
		$query = "	SELECT lasku.*
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]') and tila = '0' and alatila not in ('V','D')";
		$eresult = mysql_query($query) or pupe_error($query);
	}


	if ($toim != "MYYNTITILITOIMITA" and $toim != "EXTRANET") {
		if (isset($eresult) and  mysql_num_rows($eresult) > 0) {
			// tehdään aktivoi nappi.. kaikki mitä näytetään saa aktvoida, joten tarkkana queryn kanssa.
			if ($toim == "" or $toim == "SUPER" or $toim == "ENNAKKO" or $toim == "LASKUTUSKIELTO") {
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
			elseif ($toim == "TARJOUSSUPER") {
				$aputoim1 = "TARJOUS";
				$lisa1 = t("Muokkaa");

				$aputoim2 = "";
				$lisa2 = "";
			}
			elseif ($toim == "OSTO" or $toim == "OSTOSUPER") {
				$aputoim1 = "";
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

			if ($toim == "OSTO" or $toim == "OSTOSUPER") {
				echo "<form method='post' action='tilauskasittely/tilaus_osto.php'>";
			}
			else {
				echo "<form method='post' action='tilauskasittely/tilaus_myynti.php'>";
			}

			echo "	<input type='hidden' name='toim' value='$aputoim1'>
					<input type='hidden' name='tee' value='AKTIVOI'>";

			echo "<table>
					<tr>
					<th>".t("Kesken olevat tilauksesi").":</th>
					<td class='back'><select name='tilausnumero'>";

			while ($row = mysql_fetch_array($eresult)) {
				$select="";
				//valitaan keskenoleva oletukseksi..
				if ($row['tunnus'] == $kukarow["kesken"]) {
					$select="SELECTED";
				}
				echo "<option value='$row[tunnus]' $select>$row[tunnus]: $row[nimi] ($row[luontiaika])</option>";
			}

			echo "</select></td>";

			if ($toim == "" or $toim == "SUPER" or $toim == "ENNAKKO" or $toim == "LASKUTUSKIELTO") {
				echo "<td class='back'><input type='submit' name='$aputoim2' value='$lisa2'></td>";
			}

			echo "<td class='back'><input type='submit' name='$aputoim1' value='$lisa1'></td>";
			echo "</tr></table></form>";
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

	$seuranta = "";
	$seurantalisa = "";
	
	if ($yhtiorow["tilauksen_seuranta"] !="") {
		$seuranta = " seuranta, ";
		$seurantalisa = "LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus";
	}
	
	// Etsitään muutettavaa tilausta
	if ($toim == 'SUPER') {
		$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, laatija, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila in ('L', 'N') and alatila != 'X'
					$haku
					order by lasku.luontiaika desc
					LIMIT 50";

		// haetaan kaikkien avoimien tilausten arvo
		$sumquery = "	SELECT round(sum(tilausrivi.hinta*(1-(tilausrivi.ale/100))*(1-(lasku.erikoisale/100))*(tilausrivi.jt+tilausrivi.varattu+tilausrivi.kpl)/if('$yhtiorow[alv_kasittely]'='',1+(tilausrivi.alv/100),1)),2) arvo, count(distinct lasku.tunnus) kpl
						FROM lasku use index (tila_index)
						JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and tila in ('L', 'N')
						and tapvm = '0000-00-00'
						and alatila != 'X'";
		$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
		$sumrow = mysql_fetch_array($sumresult);

		$miinus = 2;
	}
	elseif ($toim == 'ENNAKKO') {
		$query = "	SELECT lasku.tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, lasku.laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index), tilausrivi use index (yhtio_otunnus)
					WHERE lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
					and lasku.yhtio = '$kukarow[yhtio]' and tila='E' and tilausrivi.tyyppi = 'E'
					$haku
					GROUP BY lasku.tunnus
					order by lasku.luontiaika desc
					LIMIT 50";
		$miinus = 2;
	}
	elseif ($toim == "SIIRTOLISTA") {
		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, lasku.luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='G' and alatila in ('','A','J')
					$haku
					order by lasku.luontiaika desc
					LIMIT 50";
		$miinus = 2;
	}
	elseif ($toim == "MYYNTITILI") {
		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, lasku.luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='G' and tilaustyyppi = 'M' and alatila in ('','A','B','J')
					$haku
					order by lasku.luontiaika desc
					LIMIT 50";
		$miinus = 2;
	}
	elseif ($toim == "SIIRTOLISTASUPER") {
		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, lasku.luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='G' and alatila in ('','A','B','C','D','J','T')
					$haku
					order by lasku.luontiaika desc
					LIMIT 50";
		$miinus = 2;
	}
	elseif ($toim == "MYYNTITILISUPER") {
		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, lasku.luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='G' and tilaustyyppi = 'M' and alatila in ('','A','B','C','J')
					$haku
					order by lasku.luontiaika desc
					LIMIT 50";
		$miinus = 2;
	}
	elseif ($toim == "MYYNTITILITOIMITA") {
		$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, lasku.luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='G' and tilaustyyppi = 'M' and alatila = 'V'
					$haku
					order by lasku.luontiaika desc
					LIMIT 50";
		$miinus = 2;
	}
	elseif ($toim == "JTTOIMITA") {
		$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, laatija, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='N' and alatila='U'
					$haku
					order by lasku.luontiaika desc";
		$miinus = 2;
	}
	elseif ($toim == 'VALMISTUS') {
		$query = "	SELECT tunnus tilaus, nimi vastaanottaja, ytunnus, lasku.luontiaika, laatija, viesti tilausviite, alatila, tila, tilaustyyppi
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='V' and alatila in ('','A','B','J')
					$haku
					order by lasku.luontiaika desc
					LIMIT 50";
		$miinus = 3;
	}
	elseif ($toim == "VALMISTUSSUPER") {
		$query = "	SELECT tunnus tilaus, nimi vastaanottaja, ytunnus, lasku.luontiaika, laatija, viesti tilausviite, alatila, tila, tilaustyyppi
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='V' and alatila in ('','A','B','C','J')
					$haku
					order by lasku.luontiaika desc
					LIMIT 50";
		$miinus = 3;
	}
	elseif ($toim == "TYOMAARAYS") {
		$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, laatija, alatila, tila
					FROM lasku use index (tila_index)
					WHERE yhtio = '$kukarow[yhtio]' and tila in ('A','L','N') and tilaustyyppi='A' and alatila in ('','A','B','C','J')
					$haku
					order by lasku.luontiaika desc
					LIMIT 50";

		$miinus = 2;
	}
	elseif ($toim == "TYOMAARAYSSUPER") {
		$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, laatija, alatila, tila
					FROM lasku use index (tila_index)
					WHERE yhtio = '$kukarow[yhtio]' and tila in ('A','L','N') and alatila in ('','A','B','C','J') and tilaustyyppi='A'
					$haku
					order by lasku.luontiaika desc
					LIMIT 50";
		$miinus = 2;
	}
	elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
			$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, lasku.luontiaika, laatija, viesti tilausviite, alatila, tila
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='S' and alatila in ('','A','B','J','C')
						$haku
						order by lasku.luontiaika desc
						LIMIT 50";
			$miinus = 2;
	}
	elseif ($toim == "TARJOUS") {
		$query = "	SELECT if(tunnusnippu>0,tunnusnippu,lasku.tunnus) tarjous, $seuranta nimi asiakas, ytunnus, lasku.luontiaika,
					if(date_add(lasku.luontiaika, interval $yhtiorow[tarjouksen_voimaika] day) >= now(), '<font color=\'#00FF00\'>Voimassa</font>', '<font color=\'#FF0000\'>Erääntynyt</font>') voimassa,
					DATEDIFF(lasku.luontiaika, date_sub(now(), INTERVAL $yhtiorow[tarjouksen_voimaika] day)) pva,
					lasku.laatija, alatila, tila, lasku.tunnus tilaus
					FROM lasku use index (tila_index)
					$seurantalisa
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('','A')
					$haku
					ORDER BY lasku.tunnus desc
					LIMIT 50";
		$miinus = 3;
	}
	elseif ($toim == "TARJOUSSUPER") {
		$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, laatija, alatila, tila
					FROM lasku use index (tila_index)
					WHERE yhtio = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('A','','X')
					$haku
					order by lasku.luontiaika desc
					LIMIT 50";

		$miinus = 2;
	}
	elseif ($toim == "EXTRANET") {
		$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, laatija, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = 'N' and alatila = 'F'
					$haku
					order by lasku.luontiaika desc";

		$miinus = 2;
	}
	elseif ($toim == "LASKUTUSKIELTO") {
		$query = "	SELECT lasku.tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, lasku.laatija, alatila, tila
					FROM lasku use index (tila_index)
					JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus and lasku.chn = '999'
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila in ('N','L') and alatila != 'X'
					$haku
					order by lasku.luontiaika desc";

		$miinus = 2;
	}
	elseif ($toim == 'OSTOSUPER') {
		$query = "	SELECT lasku.tunnus tilaus, lasku.nimi asiakas, lasku.ytunnus, lasku.luontiaika, lasku.laatija, lasku.alatila, lasku.tila
					FROM tilausrivi use index (yhtio_tyyppi_kerattyaika),
					lasku use index (primary)
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.tyyppi = 'O'
					and tilausrivi.uusiotunnus = 0
					and tilausrivi.kerattyaika = '0000-00-00 00:00:00'
					and lasku.yhtio = tilausrivi.yhtio
					and lasku.tunnus = tilausrivi.otunnus
					and lasku.tila = 'O'
					and lasku.alatila != ''
					$haku
					GROUP by 1
					ORDER by lasku.luontiaika desc
					LIMIT 50";
		$miinus = 2;
	}
	elseif ($toim == 'OSTO') {
		$query = "	SELECT lasku.tunnus tilaus, lasku.nimi asiakas, lasku.ytunnus, lasku.luontiaika, lasku.laatija, lasku.alatila, lasku.tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='O' and alatila=''
					$haku
					ORDER by lasku.luontiaika desc
					LIMIT 50";
		$miinus = 2;
	}
	elseif ($toim == 'PROJEKTI') {
		$query = "	SELECT lasku.tunnus tilaus, $seuranta lasku.nimi asiakas, lasku.ytunnus, lasku.luontiaika, lasku.laatija, lasku.alatila, lasku.tila, tunnusnippu
					FROM lasku use index (tila_index)
					$seurantalisa
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila IN ('R','L','N') and alatila NOT IN ('X') 
					$haku
					ORDER by lasku.luontiaika desc
					LIMIT 100";
		$miinus = 3;
	}
	elseif ($toim == 'YLLAPITO') {
		$query = "	SELECT lasku.tunnus tilaus, lasku.nimi asiakas, lasku.ytunnus, lasku.luontiaika, lasku.laatija, lasku.alatila, lasku.tila, tunnusnippu
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = '0' and alatila NOT IN ('D') 
					$haku
					ORDER by lasku.luontiaika desc
					LIMIT 100";
		$miinus = 3;
	}
	else {
		$query = "	SELECT lasku.tunnus tilaus, $seuranta lasku.nimi asiakas, lasku.ytunnus, lasku.luontiaika, lasku.laatija, lasku.alatila, lasku.tila, kuka.extranet extra
					FROM lasku use index (tila_index)
					LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka
					$seurantalisa
					WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in('L','N') and lasku.alatila in('A','')
					$haku
					HAVING extra = '' or extra is null
					order by lasku.luontiaika desc
					LIMIT 50";
		$miinus = 3;
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
			$pitaako_varmistaa = "";

			// jos kyseessä on "odottaa JT tuotteita rivi"
			if ($row["tila"] == "N" and $row["alatila"] == "T") {
				$query = "select tunnus from tilausrivi where yhtio='$kukarow[yhtio]' and tyyppi='L' and otunnus='$row[tilaus]'";
				$countres = mysql_query($query) or pupe_error($query);

				// ja sillä ei ole yhtään riviä
				if (mysql_num_rows($countres) == 0) {
					$piilotarivi = "kylla";
				}
			}
			
			//	tarjousnipuista halutaan vain se viimeisin..
			if($row["tila"] == "T") {
				$query = "select tunnus from lasku where yhtio='$kukarow[yhtio]' and tila='T' and tunnusnippu='$row[tarjous]' and tunnus > $row[tilaus]";
				$countres = mysql_query($query) or pupe_error($query);

				// ja sillä ei ole yhtään riviä
				if (mysql_num_rows($countres) > 0) {
					$piilotarivi = "kylla";
				}
			}
			elseif($row["tunnusnippu"] > 0 and $row["tila"] != "R" and $toim == "PROJEKTI") {
				
				//	Jos meillä on tunnusnippu joka kuuluu projektiin se piilotetaan
				$query = "select tunnus from lasku where yhtio='$kukarow[yhtio]' and tila='R' and tunnusnippu='$row[tunnusnippu]'";
				$countres = mysql_query($query) or pupe_error($query);

				// ja sillä ei ole yhtään riviä
				if (mysql_num_rows($countres) > 0) {
					$piilotarivi = "kylla";
				}
			}
			
			if ($piilotarivi == "") {

				// jos kyseessä on "odottaa JT tuotteita rivi ja kyseessa on toim=JTTOIMITA"
				if ($row["tila"] == "N" and $row["alatila"] == "U") {
					$query = "select tuoteno, jt from tilausrivi where yhtio='$kukarow[yhtio]' and tyyppi='L' and otunnus='$row[tilaus]'";
					$countres = mysql_query($query) or pupe_error($query);

					$jtok = 0;
					while($countrow = mysql_fetch_array($countres)) {
						list( , , $jtapu_myytavissa) = saldo_myytavissa($countrow["tuoteno"], "", 0, "");
						if($jtapu_myytavissa < $countrow["jt"]) {
							$jtok--;
						}
					}
				}


				echo "<tr>";

				for ($i=0; $i<mysql_num_fields($result)-$miinus; $i++) {
					echo "<td>$row[$i]</td>";
				}

				if ($row["tila"] == "N" and $row["alatila"] == "U") {
					if ($jtok == 0) {
						echo "<td><font color='#00FF00'>Voidaan toimittaa</font></td>";
					}
					else {
						echo "<td><font color='#FF0000'>Ei voida toimittaa</font></td>";
					}
				}
				else {

					$laskutyyppi=$row["tila"];
					$alatila=$row["alatila"];

					//tehdään selväkielinen tila/alatila
					require "inc/laskutyyppi.inc";

					echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";
				}

				// tehdään aktivoi nappi.. kaikki mitä näytetään saa aktvoida, joten tarkkana queryn kanssa.
				if ($toim == "" or $toim == "SUPER" or $toim == "EXTRANET" or $toim == "ENNAKKO" or $toim == "JTTOIMITA" or $toim == "LASKUTUSKIELTO") {
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
				elseif ($toim == "TARJOUSSUPER") {
					$aputoim1 = "TARJOUS";
					$lisa1 = t("Muokkaa");

					$aputoim2 = "";
					$lisa2 = "";
				}
				elseif ($toim == "OSTO" or $toim == "OSTOSUPER") {
					$aputoim1 = "";
					$lisa1 = t("Muokkaa");

					$aputoim2 = "";
					$lisa2 = "";
				}
				elseif($toim=="PROJEKTI" and $row["tila"] != "R") {
					$aputoim1 = "RIVISYOTTO";

					$lisa1 = t("Rivisyöttöön");
				}
				else {
					$aputoim1 = $toim;
					$aputoim2 = "";

					$lisa1 = t("Muokkaa");
					$lisa2 = "";
				}

				// tehdään alertteja
				if ($row["tila"] == "L" and $row["alatila"] == "A" and $toim == "") {
					$pitaako_varmistaa = t("Keräyslista on jo tulostettu! Oletko varma, että haluat vielä muokata tilausta?");
				}

				if ($row["tila"] == "G" and $row["alatila"] == "A" and $toim == "SIIRTOLISTA") {
					$pitaako_varmistaa = t("Siirtolista on jo tulostettu! Oletko varma, että haluat vielä muokata siirtolistaa?");
				}

				// tehdään alertti jos sellanen ollaan määritelty
				$javalisa = "";
				if ($pitaako_varmistaa != "") {
					echo "	<script language=javascript>
							function lahetys_verify() {
								msg = '$pitaako_varmistaa';
								return confirm(msg);
							}
							</script>";
					$javalisa = "onSubmit = 'return lahetys_verify()'";
				}

				if ($toim == "OSTO" or $toim == "OSTOSUPER") {
					echo "<form method='post' action='tilauskasittely/tilaus_osto.php' $javalisa>";
				}
				else {
					echo "<form method='post' action='tilauskasittely/tilaus_myynti.php' $javalisa>";
				}

				echo "	<input type='hidden' name='toim' value='$aputoim1'>
						<input type='hidden' name='tee' value='AKTIVOI'>
						<input type='hidden' name='tilausnumero' value='$row[tilaus]'>";

				if ($toim == "" or $toim == "SUPER" or $toim == "EXTRANET" or $toim == "ENNAKKO" or $toim == "JTTOIMITA" or $toim == "LASKUTUSKIELTO") {
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
