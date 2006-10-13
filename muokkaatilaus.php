<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Muokkaa tilausta").":<hr></font>";

	// aktivoidaan saatu id
	if ($tee=='aktivoi') {
		// katsotaan onko muilla aktiivisena
		$query = "select * from kuka where yhtio='$kukarow[yhtio]' and kesken='$id' and kesken!=0";
		$result = mysql_query($query) or pupe_error($query);

		$row="";
		if (mysql_num_rows($result) != 0) {
			$row=mysql_fetch_array($result);
		}

		if (($row!="") and ($row['kuka']!=$kukarow['kuka'])) {
			echo "<font class='error'>".t("Tilaus on aktiivisena k‰ytt‰j‰ll‰")." $row[nimi]. ".t("Tilausta ei voi t‰ll‰ hetkell‰ muokata").".</font><br>";

			// poistetaan aktiiviset tilaukset jota t‰ll‰ k‰ytt‰j‰ll‰ oli
			$query = "update kuka set kesken='' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);

			$id 				 = "";
			$kukarow['kesken'] 	 = $id;
		}
		else {
			$query = "update kuka set kesken='$id' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);

			$kukarow['kesken'] 	 = $id;
		}
	}

	// Tehd‰‰n popup k‰ytt‰j‰n lep‰‰m‰ss‰ olevista tilauksista
	$boob=0;

	//siirtolistat
	if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER" or $toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER") {
		$query = "	SELECT *
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and laatija='$kukarow[kuka]' and alatila='' and tila = 'G'";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	//valmistukset
	elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
		$query = "	SELECT *
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and laatija='$kukarow[kuka]' and alatila='' and tila = 'V'";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	//tyˆm‰‰r‰ykset
	elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and laatija='$kukarow[kuka]' and tila='A' and alatila='' and tilaustyyppi='A'";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	//Tarjoukset
	elseif ($toim == "TARJOUS" or $toim == "TARJOUSSUPER") {
		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and laatija='$kukarow[kuka]' and tila='T' and alatila in ('','A') and tilaustyyppi='T'";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	//Ennakot
	elseif (strtoupper($toim) == "ENNAKKO") {
		$query = "	SELECT lasku.*
					FROM lasku, tilausrivi
					WHERE lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
					and lasku.yhtio = '$kukarow[yhtio]' and lasku.laatija='$kukarow[kuka]' and tila='E' and alatila in ('','A','J') and tilaustyyppi = 'E' and tilausrivi.tyyppi = 'E'
					GROUP BY lasku.tunnus";
		$eresult = mysql_query($query) or pupe_error($query);
	}
	//myyntitilaukset
	elseif ($toim == "" or $toim == "super") {
		$query = "	SELECT *
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and laatija='$kukarow[kuka]' and alatila='' and tila in ('N','E')";
		$eresult = mysql_query($query) or pupe_error($query);
	}

	if ($toim != "MYYNTITILITOIMITA" and $toim != "EXTRANET") {
		if (mysql_num_rows($eresult) != 0) {
			$boob=1;
			echo "
			<table><tr>

			<form method='post' action='$PHP_SELF'>
			<input type='hidden' name='tee' value='aktivoi'>
			<input type='hidden' name='toim' value='$toim'>

			<th>".t("Kesken olevat tilauksesi").":</th>
			<td><select name='id'>";

			while ($row=mysql_fetch_array($eresult)) {
				$select="";
				//valitaan keskenoleva oletukseksi..
				if ($row['tunnus'] == $id) $select="SELECTED";
				echo "<option value='$row[tunnus]' $select>$row[tunnus]: $row[nimi] ($row[luontiaika])</option>";
			}
			echo "</select></td>
			<td class='back'><input type='submit' name='tila' value='".t("Aktivoi")."'></td>
			</form>
			</tr></table>
			";
		}
	}

	if ($kukarow['kesken'] != 0) {

		if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER" or $toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER" or $toim == "MYYNTITILITOIMITA") {
			$query = "	SELECT * FROM lasku
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tunnus='$kukarow[kesken]' and tila = 'G'";
			$result = mysql_query($query) or pupe_error($query);
		}
		//valmistukset
		elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
			$query = "	SELECT *
						FROM lasku
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tunnus='$kukarow[kesken]' and tila = 'V'";
			$result = mysql_query($query) or pupe_error($query);
		}
		//tyˆm‰‰r‰ykset
		elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and tunnus='$kukarow[kesken]' and tila in ('A','L','N') and tilaustyyppi='A'";
			$result = mysql_query($query) or pupe_error($query);
		}
		//tarjoukset
		elseif ($toim == "TARJOUS" or $toim == "TARJOUSSUPER") {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and tunnus='$kukarow[kesken]' and tila = 'T' and tilaustyyppi='T'";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			$query = "	SELECT * FROM lasku
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tunnus='$kukarow[kesken]' and tila in ('L','N','E')";
			$result = mysql_query($query) or pupe_error($query);
		}


		if (mysql_num_rows($result)>0) {
			$row = mysql_fetch_array($result);
			$boob=1;

			if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER") {
				echo "<br><table>
						<tr><th colspan='2' nowrap>".t("Sinulla on aktiivisena siirtolista")." $row[tunnus]: $row[nimi] ($row[luontiaika])</th>
						<form action = 'tilauskasittely/tilaus_myynti.php' method='post'>
						<input type='hidden' name='toim' value='SIIRTOLISTA'>
						<input type='hidden' name='tilausnumero' value='$row[tunnus]'>
						<input type='hidden' name='aktivoinnista' value='true'>
						<td><input type='submit' value = '".t("Muokkaa siirtolistaa")."'></td></tr></form>
						</table>";

			}
			elseif ($toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER" or $toim == "MYYNTITILITOIMITA") {
				echo "<br><table>
						<tr><th colspan='2' nowrap>".t("Sinulla on aktiivisena myyntitili")." $row[tunnus]: $row[nimi] ($row[luontiaika])</th>
						<form action = 'tilauskasittely/tilaus_myynti.php' method='post'>
						<input type='hidden' name='toim' value='MYYNTITILI'>
						<input type='hidden' name='tilausnumero' value='$row[tunnus]'>
						<input type='hidden' name='aktivoinnista' value='true'>
						<td><input type='submit' value = '".t("Muokkaa myyntitili‰")."'></td></tr></form>
						</table>";

			}
			elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
				echo "<br><table>
						<tr><th colspan='2' nowrap>".t("Sinulla on aktiivisena valmistus")." $row[tunnus]: $row[nimi] ($row[luontiaika])</th>";

				echo "<form action = 'tilauskasittely/tilaus_myynti.php' method='post'>";

				if ($row["tilaustyyppi"] == "W") {
					echo "<input type='hidden' name='toim' value = 'VALMISTAVARASTOON'>";
				}
				else {
					echo "<input type='hidden' name='toim' value = 'VALMISTAASIAKKAALLE'>";
				}

				echo "	<input type='hidden' name='tilausnumero' value='$row[tunnus]'>
						<input type='hidden' name='aktivoinnista' value='true'>
						<td><input type='submit' value = '".t("Muokkaa valmistusta")."'></td></tr></form>
						</table>";

			}
			elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
				echo "<br><table>
						<tr><th colspan='2'  nowrap>".t("Sinulla on aktiivisena tyˆm‰‰r‰ys")." $row[tunnus]: $row[nimi] ($row[luontiaika])</th>
						<form method='post' action='tilauskasittely/tilaus_myynti.php'>
						<input type='hidden' name='aktivoinnista' value='true'>
						<input type='hidden' name='toim' value='TYOMAARAYS'>
						<input type='hidden' name='tilausnumero' value='$row[tunnus]'>
						<td align='center'><input type='submit' value='".t("Muokkaa tyˆm‰‰r‰yst‰")."'></td>
						</form>
						</tr>
						</table>";
			}
			elseif ($toim == "TARJOUS" or $toim == "TARJOUSSUPER") {
				echo "<br><table>
						<tr><th colspan='2'  nowrap>".t("Sinulla on aktiivisena tarjous")." $row[tunnus]: $row[nimi] ($row[luontiaika])</th>
						<form method='post' action='tilauskasittely/tilaus_myynti.php'>
						<input type='hidden' name='aktivoinnista' value='true'>
						<input type='hidden' name='toim' value='TARJOUS'>
						<input type='hidden' name='tilausnumero' value='$row[tunnus]'>
						<td align='center'><input type='submit' value='".t("Muokkaa tarjousta")."'></td>
						</form>
						</tr>
						</table>";
			}
			else {
				echo "<br><table>
						<tr><th colspan='2' nowrap>".t("Sinulla on aktiivisena tilaus")." $row[tunnus]: $row[nimi] ($row[luontiaika])</th></tr>
						<tr>

						<form method='post' action='tilauskasittely/tilaus_myynti.php'>
						<input type='hidden' name='aktivoinnista' value='true'>
						<input type='hidden' name='toim' value='PIKATILAUS'>
						<input type='hidden' name='tilausnumero' value='$row[tunnus]'>
						<td align='center' width='50%'><input type='submit' value='".t("Muokkaa Pikatilauksessa")."'></td>
						</form>

						<form method='post' action='tilauskasittely/tilaus_myynti.php'>
						<input type='hidden' name='aktivoinnista' value='true'>
						<input type='hidden' name='toim' value='RIVISYOTTO'>
						<input type='hidden' name='tilausnumero' value='$row[tunnus]'>
						<td align='center' width='50%'><input type='submit' value='".t("Muokkaa Rivisyˆtˆss‰")."'></td>
						</form>

						</tr>
						</table>";
			}
		}
	}

	if ($boob==0)
		echo t("Sinulla ei ole aktiivisia eik‰ kesken olevia tilauksia").".<br>";

	// N‰ytet‰‰n muuten vaan sopivia tilauksia
	echo "<br><form action='$PHP_SELF' method='post'>
			<input type='hidden' name='toim' value='$toim'>
			<font class='head'>".t("Etsi tilauksia").":<hr></font>
			".t("Syˆt‰ tilausnumero, nimen tai laatijan osa").":
			<input type='text' name='etsi'>
			<input type='Submit' value = '".t("Etsi")."'>
			</form>";

	// pvm 30 pv taaksep‰in
	$dd = date("d",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
	$mm = date("m",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
	$yy = date("Y",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

	$haku='';
	if (is_string($etsi))  $haku="and (lasku.nimi like '%$etsi%' or lasku.laatija like '%$etsi%')";
	if (is_numeric($etsi)) $haku="and (lasku.tunnus like '$etsi%' or lasku.ytunnus like '$etsi%')";

	// Etsit‰‰n muutettavaa tilausta
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
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='G' and alatila in ('','A','B','C','J') $haku
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
		$query = "	SELECT tunnus tilaus, nimi vastaanottaja, ytunnus, luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='V' and alatila in ('','A','B','J') $haku
					order by luontiaika desc
					LIMIT 50";

		$miinus = 2;
		//HUOMATKAA LIMITTI!
	}
	elseif ($toim == "VALMISTUSSUPER") {
		$query = "	SELECT tunnus tilaus, nimi vastaanottaja, ytunnus, luontiaika, laatija, viesti tilausviite, alatila, tila
					FROM lasku use index (tila_index)
					WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='V' and alatila in ('','A','B','C','J') $haku
					order by luontiaika desc
					LIMIT 50";

		$miinus = 2;
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
					if(date_add(luontiaika, interval 14 day) >= now(), '<font color=\'#00FF00\'>Voimassa</font>', '<font color=\'#00FF00\'>Er‰‰ntynyt</font>') voimassa,
					DATEDIFF(luontiaika, date_sub(now(), INTERVAL 14 day)) pva,
					laatija, alatila, tila
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('','A') $haku
					ORDER BY tunnus desc
					LIMIT 50";

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
		echo "<th align='left'>".t("tyyppi")."</th><td class='back'></td></tr>";

		while ($row = mysql_fetch_array($result)) {
			echo "<tr>";

			for ($i=0; $i<mysql_num_fields($result)-$miinus; $i++) {
				echo "<td>$row[$i]</td>";
			}

			$laskutyyppi=$row["tila"];
			$alatila=$row["alatila"];

			//tehd‰‰n selv‰kielinen tila/alatila
			require "inc/laskutyyppi.inc";

			echo "<td>".t("$laskutyyppi")." ".t("$alatila")."</td>";

			// tehd‰‰n aktivoi nappi.. kaikki mit‰ n‰ytet‰‰n saa aktvoida, joten tarkkana queryn kanssa.
			echo "<form method='post' action='$PHP_SELF'><td class='back'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='tee' value='aktivoi'>
					<input type='hidden' name='id' value='$row[tilaus]'>
					<input type='submit' name='tila' value='".t("Aktivoi")."'></td></tr></form>";

			echo "</tr>";
		}

		echo "</table>";

		if (is_array($sumrow)) {
			echo "<br><table cellpadding='5'><tr>";
			echo "<th>".t("Tilausten arvo yhteens‰")." ($sumrow[kpl] ".t("kpl")."): </th>";
			echo "<td>$sumrow[arvo] $yhtiorow[valkoodi]</td>";
			echo "</tr></table>";
		}

	}
	else {
		echo t("Ei tilauksia")."...";
	}

	require ("inc/footer.inc");
?>
