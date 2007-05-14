<?php
	
	require("inc/parametrit.inc");

	echo "<font class='head'>".t("Rahtikirjakopio")."</font><hr>";

	if ($tee=='tulosta') {

		if ($yksittainen == "ON") {
			//Tässä on haettava tulostettavan tilauksen tiedot
			$query = "select otsikkonro from rahtikirjat where yhtio='$kukarow[yhtio]' and rahtikirjanro='$rtunnukset[0]' limit 1";
			$ores  = mysql_query($query) or pupe_error($query);
			$rrow  = mysql_fetch_array($ores);

			$query = "select toimitustapa, varasto from lasku where yhtio='$kukarow[yhtio]' and tunnus='$rrow[otsikkonro]'";
			$ores  = mysql_query($query) or pupe_error($query);
			$orow  = mysql_fetch_array($ores);

			$toimitustapa	= $orow["toimitustapa"];
			$varasto		= $orow["varasto"];
		}

		// haetaan toimitustavan tiedot
		$query    = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$toimitustapa'";
		$toitares = mysql_query($query) or pupe_error($query);
		$toitarow = mysql_fetch_array($toitares);

		if ($valittu_tulostin == "") {
			// haetaan printterin tiedot
			$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$varasto'";
			$pres  = mysql_query($query) or pupe_error($query);
			$print = mysql_fetch_array($pres);
			if (strpos($toitarow['rahtikirja'],'pdf') === false) {
				$printteri = $print["printteri2"]; //matriisi
			}
			else {
				$printteri = $print["printteri6"]; //laser
			}
		}
		else {
			$printteri = $valittu_tulostin;
		}
		
		

		// haetaan printteri 2:lle tulostuskomento
		$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$printteri'";
		$pres  = mysql_query($query) or pupe_error($query);
		$print = mysql_fetch_array($pres);

		$kirjoitin = $print['komento'];
		$merkisto  = $print['merkisto'];
		$pvm       = "$pp.$kk.$vv";

		if ($kirjoitin=='') die ("Tulostin hukassa! (printteri2)!");

		// jos meillä printterille joku spessu osoitetieto niin käytetään sen tietoja lähettäjän tietoina
		if ($print["nimi"] != "") {
			$yhtiorow["nimi"]    = $print["nimi"];
			$yhtiorow["osoite"]  = $print["osoite"];
			$yhtiorow["postino"] = $print["postino"];
			$yhtiorow["postitp"] = $print["postitp"];
			$yhtiorow["puhelin"] = $print["puhelin"];
			$yhtiorow["yhteyshenkilo"] = $print["yhteyshenkilo"];
		}

		// tulostetaan jokainen ruksattu rahtikirja erikseen...
		foreach ($rtunnukset as $rakir) {

			$pakkaus       = array();
			$kilot         = array();
			$kollit        = array();
			$kuutiot       = array();
			$lavametri     = array();
			$otsikot       = array();
			$vakit         = array();
			$kilotyht      = 0;
			$lavatyht      = 0;
			$kollityht     = 0;
			$kuutiotyht    = 0;
			$otunnukset    = "";
			$tunnukset     = "";
			$rahtimaksu    = "";
			$viite 		   = "";
			$jvhinta	   = "";
			$lasno		   = "";
			$yhteensa	   = "";
			$summa		   = "";
			$aputeksti     = "";

			// haetaan tälle rahtikirjalle kuuluvat tunnukset
			$query = "select tunnus, otsikkonro, merahti from rahtikirjat where yhtio='$kukarow[yhtio]' and rahtikirjanro = '$rakir'";
			$res   = mysql_query($query) or pupe_error($query);

			while ($rivi = mysql_fetch_array($res)) {

				//otetaan kaikki otsikkonumerot ja rahtikirjanumerot talteen... tarvitaan myöhemmin hauissa
				$otunnukset   .="'$rivi[otsikkonro]',";
				$tunnukset    .="'$rivi[tunnus]',";

				//otsikkonumerot talteen, nämä printataan paperille
				if (!in_array($rivi['otsikkonro'], $otsikot))
					$otsikot[] = $rivi['otsikkonro'];

				$merahtix = $rivi["merahti"];
			}

			//otetaan tästä loopista vielä toi rahdinmaksaja ulos
			$rahdinmaksaja = "Vastaanottaja"; //tämä on defaultti
			if ($merahtix == 'K') $rahdinmaksaja = "Lähettäjä";

			//vikat pilkut pois
			$otunnukset = substr($otunnukset,0,-1);
			$tunnukset  = substr($tunnukset,0,-1);

			//summataan kaikki painot yhteen
			$query = "SELECT pakkaus, sum(kilot), sum(kollit), sum(kuutiot), sum(lavametri) FROM rahtikirjat WHERE tunnus in ($tunnukset) and yhtio='$kukarow[yhtio]' group by pakkaus order by pakkaus";
			$pakka = mysql_query($query) or pupe_error($query);
			while ($pak = mysql_fetch_array($pakka)) {

				$pakkaus[]   = $pak[0];
				$kilot[]     = $pak[1];
				$kollit[]    = $pak[2];
				$kuutiot[]   = $pak[3];
				$lavametri[] = $pak[4];
				$kilotyht   += $pak[1];
				$kollityht  += $pak[2];
				$kuutiotyht += $pak[3];
				$lavatyht   += $pak[4];
			}

			//haetaan rahtikirjan kaikki vakkoodit arrayseen
			$query      = "select distinct(vakkoodi) from tilausrivi,tuote where otunnus in ($otunnukset) and tilausrivi.yhtio='$kukarow[yhtio]' and tuote.tuoteno=tilausrivi.tuoteno and tuote.yhtio=tilausrivi.yhtio and vakkoodi<>'0' and vakkoodi<>' ' and var in ('','H') and tilausrivi.tyyppi='L'";
			$vres       = mysql_query($query) or pupe_error($query);
			while ($vak = mysql_fetch_array($vres)) $vakit[] = $vak[0];

			// haetaan laskun tiedot
			$query = "select lasku.*, '$merahtix' merahti from lasku where yhtio='$kukarow[yhtio]' and tunnus in ($otunnukset) limit 1";
			$res   = mysql_query($query) or pupe_error($query);
			$rakir_row = mysql_fetch_array($res);

			$query = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$rakir_row[maksuehto]'";
			$res   = mysql_query($query) or pupe_error($query);
			$mehto = mysql_fetch_array($res);

			// jos kyseessä oli JV haetaan vähä lisää tietoja
			if ($mehto['jv'] != '') {

				// haetaan U-laskun tiedot (oletetaan että tulee vaan yks.. niin pitäs ainakin olla)
				$query  = "select distinct uusiotunnus from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus in ($otunnukset) and tyyppi='L'";
				$jvares = mysql_query($query) or pupe_error($query);
				$jvarow = mysql_fetch_array($jvares);

				$query  = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus = '$jvarow[uusiotunnus]'";
				$jvares = mysql_query($query) or pupe_error($query);
				$jvarow = mysql_fetch_array($jvares);

				// haetaan rahdin hinta
				$query      = "select hinta from tilausrivi where otunnus in ($otunnukset) and yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
				$rares		= mysql_query($query) or pupe_error($query);
				$rahti 		= mysql_fetch_array($rares);

				$rahtimaksu = $rahti['hinta'];
				$viite 		= $jvarow['viite'];
				$jvhinta	= $toitarow['jvkulu'];
				$lasno		= $jvarow['laskunro'];
				$yhteensa	= $jvarow['summa'];
				$summa		= $jvarow['summa'] - $jvhinta - $rahtimaksu;
				$aputeksti	= "JÄLKIVAATIMUS";
			}

			// nyt on kaikki tiedot rahtikirjaa varten haettu..
			//
			// arrayt:
			// otsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
			// $rakir_row:sta löytyy asiakkaan tiedot ja $rivi:stä ytunnus
			//
			// muuttujat:
			// rahdinmaksaja, rahtihinta, pvm, toimitustapa, kolliyht, kilotyht, kuutiotyht, kirjoitin
			// jv tapauksissa on myös yhteensa, summa, jvhinta, lasno ja viite muuttujat
			//
			// tulostetaan rahtikirja

			echo "<font class='message'>".t("Asiakas")." $rakir_row[toim_nimi]</font><li>".t("Tilaukset").": ";
			foreach($otsikot as $doit) echo "$doit ";

			$tulostakopio = "kylla"; // tarvitaan tietää, että onko kyseessä kopio

			// tulostetaan toimitustavan määrittelemä rahtikirja
			if (file_exists("tilauskasittely/$toitarow[rahtikirja]")) {
				require("tilauskasittely/$toitarow[rahtikirja]");
			}
			else {
				echo "<li><font class='error'>".t("Rahtikirjatiedostoa 'tilauskasittely")."/$toitarow[rahtikirja]' ".t("Ei löydy")."!</font>";
			}

			echo "<br>";

		} // end foreach rtunnukset

		echo "<br>";
		$tee = "";

	} // end tee==tulosta


	if ($tee=='valitse') {

		if ($otunnus == "") {
			$query = "	select distinct rahtikirjanro
						from rahtikirjat
						where yhtio		= '$kukarow[yhtio]' and
						tulostuspaikka	= '$varasto' and
						toimitustapa	= '$toimitustapa' and
						tulostettu		> '$vv-$kk-$pp 00:00:00' and
						tulostettu		< '$vv-$kk-$pp 23:59:59'";
		}
		else {
			$query = "	select distinct rahtikirjanro
						from rahtikirjat
						where yhtio		= '$kukarow[yhtio]'
						and otsikkonro	= '$otunnus'
						and tulostettu != '0000-00-00 00:00:00'";

			$toimitustapa = "";
			$varasto = "";
		}

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='message'>$toimitustapa: $vv-$kk-$pp<br><br>".t("Yhtään rahtikirjaa ei löytynyt")."!</font><br><br>";
			$tee = "";
		}
		else {

			echo "<form action='rahtikirja-kopio.php' method='post'>";
			echo "<input type='hidden' name='tee' value='tulosta'>";
			echo "<input type='hidden' name='pp' value='$pp'>";
			echo "<input type='hidden' name='kk' value='$kk'>";
			echo "<input type='hidden' name='vv' value='$vv'>";

			if ($otunnus == "") {
				echo "<font class='message'>$toimitustapa: $vv-$kk-$pp</font><br><br>";
				echo "<input type='hidden' name='varasto' value='$varasto'>";
				echo "<input type='hidden' name='toimitustapa' value='$toimitustapa'>";
			}
			else {
				echo "<input type='hidden' name='yksittainen' value='ON'>";
			}


			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Rahtikirjanro")."</th>";
			echo "<th>".t("Tilausnumero")."</th>";
			echo "<th>".t("Tulostettu")."</th>";
			echo "<th>".t("Asiakas")."</th>";
			echo "<th>".t("Osoite")."</th>";
			echo "<th>".t("Postino")."</th>";
			echo "<th>".t("Valitse")."</th>";
			echo "</tr>";

			while ($row = mysql_fetch_array($result)) {
				if ($row['rahtikirjanro'] != '') {
					$query = "select otsikkonro, tulostettu from rahtikirjat where yhtio='$kukarow[yhtio]' and rahtikirjanro='$row[rahtikirjanro]' limit 1";
					$ores  = mysql_query($query) or pupe_error($query);
					$rrow  = mysql_fetch_array($ores);

					$query = "select ytunnus, nimi, nimitark, toim_osoite, toim_postino, toim_postitp, tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnus='$rrow[otsikkonro]'";
					$ores  = mysql_query($query) or pupe_error($query);
					$orow  = mysql_fetch_array($ores);

					echo "<tr>";
					echo "<td>$row[rahtikirjanro]</td>";
					echo "<td>$orow[tunnus]</td>";
					echo "<td>$rrow[tulostettu]</td>";
					echo "<td>$orow[nimi] $orow[nimitark]</td>";
					echo "<td>$orow[toim_osoite]</td>";
					echo "<td>$orow[toim_postino] $orow[toim_postitp]</td>";
					echo "<td><input type='checkbox' name='rtunnukset[]' value='$row[rahtikirjanro]' checked></td>";
					echo "</tr>";
				}
			}

			echo "</table><br>";

			$query = "	SELECT *
						FROM kirjoittimet
						WHERE
						yhtio='$kukarow[yhtio]'
						ORDER by kirjoitin";
			$kirre = mysql_query($query) or pupe_error($query);

			echo "<select name='valittu_tulostin'>";
			echo "<option value=''>".t("Oletustulostimelle")."</option>";

			while ($kirrow = mysql_fetch_array($kirre)) {
				echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
			}

			echo "</select><br>";

			echo "<br><input type='submit' value='".t("Tulosta valitut")."'>";
			echo "</form>";
		}
	}

	if ($tee=='') {
		// mitä etsitään
		if (!isset($vv)) $vv = date("Y");
		if (!isset($kk)) $kk = date("m");
		if (!isset($pp)) $pp = date("d");


		echo "<br><form action='rahtikirja-kopio.php' method='post'>";
		echo "<input type='hidden' name='tee' value='valitse'>";

		echo t("Tulosta yksittäinen rahtikirjakopio").":";
		echo "<table><tr>
			<th>".t("Syötä tilausnumero").":</th>
			<td><input type='text' name='otunnus' size='15'></td>
			</tr>";
		echo "</table><br>";

		echo t("Tulosta kopiot eräajosta").":";
		echo "<table><tr>
			<th>".t("Syötä päivämäärä (pp-kk-vvvv)").":</th>
			<td><input type='text' name='pp' value='$pp' size='3'>
			<input type='text' name='kk' value='$kk' size='3'>
			<input type='text' name='vv' value='$vv' size='5'></td>
			</tr>";

		$query  = "SELECT * FROM toimitustapa WHERE nouto='' and yhtio='$kukarow[yhtio]' order by jarjestys, selite";
		$result = mysql_query($query) or pupe_error($query);

		echo "<tr><th>".t("Valitse toimitustapa").":</th>";
		echo "<td><select name='toimitustapa'>";

		while ($row = mysql_fetch_array($result)) {
			if ($toimitustapa==$row['selite']) $sel=" selected ";
			else $sel = "";

			echo "<option value='$row[selite]' $sel>".asana('TOIMITUSTAPA_',$row['selite'])."";
		}

		echo "</select></td></tr>";

		// haetaan kaikki varastot
		$query  = "SELECT tunnus, nimitys FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		// jos löytyy enemmän kuin yksi, tehdään varasto popup..
		if (mysql_num_rows($result)>1) {
			echo "<tr><th>".t("Valitse varasto").":</th>";
			echo "<td><select name='varasto'>";

			while ($row = mysql_fetch_array($result)) {
				if ($varasto==$row['tunnus']) $sel=" selected ";
				else $sel = "";
				echo "<option value='$row[tunnus]' $sel>$row[nimitys]";
			}

			echo "</select></td></tr>";
		}
		else {
			$row = mysql_fetch_array($result);
			echo "<input type='hidden' name='varasto' value='$row[tunnus]'>";
		}

		echo "</table><br>";
		echo "<input type='submit' value='".t("Tulosta")."'>";
		echo "</form>";
	}

	require("inc/footer.inc");
?>