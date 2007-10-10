<?php
	
	require("inc/parametrit.inc");

	echo "<font class='head'>".t("Rahtikirjakopio")."</font><hr>";

	if ($tee=='tulosta') {

		if ($yksittainen == "ON") {
			//Tässä on haettava tulostettavan tilauksen tiedot
			$query = "select toimitustapa, tulostuspaikka from rahtikirjat where yhtio='$kukarow[yhtio]' and rahtikirjanro='$rtunnukset[0]' limit 1";
			$ores  = mysql_query($query) or pupe_error($query);
			$rrow  = mysql_fetch_array($ores);

			$toimitustapa	= $rrow["toimitustapa"];
			$varasto		= $rrow["tulostuspaikka"];
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
		
		// haetaan kaikki distinct rahtikirjat..
		$query = "	SELECT distinct lasku.ytunnus, lasku.toim_maa, lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_ovttunnus, lasku.toim_postino, lasku.toim_postitp,
					lasku.maa, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.ovttunnus, lasku.postino, lasku.postitp,
					rahtikirjat.merahti, rahtikirjat.rahtisopimus, if(maksuehto.jv is null,'',maksuehto.jv) jv, lasku.alv, lasku.vienti, rahtisopimukset.muumaksaja
					FROM rahtikirjat
					JOIN lasku on rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio
					LEFT JOIN maksuehto on lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
					LEFT JOIN rahtisopimukset on lasku.ytunnus = rahtisopimukset.ytunnus and rahtikirjat.toimitustapa = rahtisopimukset.toimitustapa and rahtikirjat.rahtisopimus = rahtisopimukset.rahtisopimus
					where rahtikirjat.yhtio			= '$kukarow[yhtio]'
					and rahtikirjat.toimitustapa	= '$toimitustapa'
					and rahtikirjat.tulostuspaikka	= '$varasto'
					and rahtikirjat.rahtikirjanro  in ('".str_replace(',','\',\'',implode(",", $rtunnukset))."')
					order by lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, rahtikirjat.merahti, rahtikirjat.rahtisopimus";
		$rakir_res = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($rakir_res) == 0) {
			echo "<font class='message'>".t("Yhtään tulostettavaa rahtikirjaa ei löytynyt").".$query</font><br><br>";
		}

		while ($rakir_row = mysql_fetch_array($rakir_res)) {
			// muutama muuttuja tarvitaan
			$pakkaus       	= array();
			$kilot         	= array();
			$kollit        	= array();
			$kuutiot       	= array();
			$lavametri     	= array();
			$lotsikot      	= array();
			$astilnrot		= array();
			$vakit         	= array();
			$kilotyht      	= 0;
			$lavatyht      	= 0;
			$kollityht     	= 0;
			$kuutiotyht    	= 0;
			$tulostuskpl   	= 0;
			$otunnukset    	= "";
			$tunnukset     	= "";
			$rahtikirjanro 	= "";


			if ($rakir_row['merahti'] == 'K') {
				$rahdinmaksaja = "Lähettäjä";
			}
			else {
				$rahdinmaksaja = "Vastaanottaja"; //tämä on defaultti
			}
			
			// Katsotaan onko tämä koontikuljetus
			if ($toitarow["tulostustapa"] == "K" or $toitarow["tulostustapa"] == "L") {
				// Monen asiakkaan rahtikirjat tulostuu aina samalle paperille
				$asiakaslisa = " ";

				//Toimitusosoitteeksi halutaan tässä tapauksessa toimitustavan takaa löytyvät
				$rakir_row["toim_maa"]		= $toitarow["toim_maa"];
				$rakir_row["toim_nimi"]		= $toitarow["toim_nimi"];
				$rakir_row["toim_nimitark"]	= $toitarow["toim_nimitark"];
				$rakir_row["toim_osoite"]	= $toitarow["toim_osoite"];
				$rakir_row["toim_postino"]	= $toitarow["toim_postino"];
				$rakir_row["toim_postitp"]	= $toitarow["toim_postitp"];

			}
			else {
				// Normaalissa keississä ainoastaan saman toimitusasiakkaan kirjat menee samalle paperille
				$asiakaslisa = "and lasku.ytunnus			= '$rakir_row[ytunnus]'
								and lasku.toim_maa			= '$rakir_row[toim_maa]'
								and lasku.toim_nimi			= '$rakir_row[toim_nimi]'
								and lasku.toim_nimitark		= '$rakir_row[toim_nimitark]'
								and lasku.toim_osoite		= '$rakir_row[toim_osoite]'
								and lasku.toim_ovttunnus	= '$rakir_row[toim_ovttunnus]'
								and lasku.toim_postino		= '$rakir_row[toim_postino]'
								and lasku.toim_postitp		= '$rakir_row[toim_postitp]' ";
			}

			// haetaan tälle rahtikirjalle kuuluvat tunnukset
			$query = "	SELECT rahtikirjat.tunnus rtunnus, lasku.tunnus otunnus, merahti, lasku.ytunnus, if(maksuehto.jv is null,'',maksuehto.jv) jv, lasku.asiakkaan_tilausnumero
						FROM rahtikirjat
						join lasku on rahtikirjat.otsikkonro = lasku.tunnus and rahtikirjat.yhtio = lasku.yhtio
						left join maksuehto on lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
						WHERE rahtikirjat.yhtio			= '$kukarow[yhtio]'
						and rahtikirjat.toimitustapa	= '$toimitustapa'
						and rahtikirjat.tulostuspaikka	= '$varasto'
						$asiakaslisa
						and rahtikirjat.merahti			= '$rakir_row[merahti]'
						and rahtikirjat.rahtisopimus	= '$rakir_row[rahtisopimus]'
						and rahtikirjat.rahtikirjanro  in ('".str_replace(',','\',\'',implode(",", $rtunnukset))."')";
			$res   = mysql_query($query) or pupe_error($query);

			while ($rivi = mysql_fetch_array($res)) {
				//otetaan kaikki otsikkonumerot ja rahtikirjanumerot talteen... tarvitaan myöhemmin hauissa
				$otunnukset   .="'$rivi[otunnus]',";
				$tunnukset    .="'$rivi[rtunnus]',";

				//otsikkonumerot talteen, nämä printataan paperille
				if (!in_array($rivi['otunnus'], $lotsikot)) {
					$lotsikot[] 	= $rivi['otunnus'];
					$astilnrot[]	= $rivi['asiakkaan_tilausnumero'];
				}
				// otetaan jokuvaan rtunnus talteen uniikisi numeroksi
				// tarvitaan postin rahtikirjoissa
				$rtunnus = $rivi["rtunnus"];
			}

			if (mysql_num_rows($res) > 0) {
				mysql_data_seek($res,0);
				$rivi = mysql_fetch_array($res);

				//vikat pilkut pois
				$otunnukset = substr($otunnukset,0,-1);
				$tunnukset  = substr($tunnukset,0,-1);

				//summataan kaikki painot yhteen
				$query = "	SELECT pakkaus, sum(kilot), sum(kollit), sum(kuutiot), sum(lavametri)
							FROM rahtikirjat
							WHERE tunnus in ($tunnukset) and yhtio='$kukarow[yhtio]'
							group by pakkaus order by pakkaus";
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

				$tulostuskpl = $kollityht;

				//haetaan rahtikirjan kaikki vakkoodit arrayseen
				$query = "	select distinct(vakkoodi)
							from tilausrivi,tuote
							where otunnus in ($otunnukset)
							and tilausrivi.yhtio = '$kukarow[yhtio]'
							and tuote.tuoteno = tilausrivi.tuoteno
							and tuote.yhtio = tilausrivi.yhtio
							and vakkoodi <> '0'
							and vakkoodi <> ' '
							and var in ('','H')
							and tilausrivi.tyyppi in ('L','G')";
				$vres = mysql_query($query) or pupe_error($query);
				while ($vak = mysql_fetch_array($vres)) $vakit[] = $vak[0];


				// nyt on kaikki tiedot rahtikirjaa varten haettu..
				//
				// arrayt:
				// toitarow, otsikot, pakkaus, kilot, kollit, kuutiot, lavametri, vakit
				// $rakir_row:sta löytyy asiakkaan tiedot
				//
				////ja $rivi:stä ytunnus
				//
				// muuttujat:
				// otunnukset, pvm, rahdinmaksaja, toimitustapa, kolliyht, kilotyht, kuutiotyht, kirjoitin
				// jv tapauksissa on myös aputeksti, rahtihinta, rahdinhinta, yhteensa, summa, jvhinta, jvtext, lasno ja viite muuttujat
				// rtunnus jossa on uniikki numero
				//
				// tulostetaan rahtikirja

				foreach($lotsikot as $doit) echo t("Tulostetaan rahtikirja").": $doit <br>";

				// tarvitaan tietää, että onko kyseessä kopio
				$tulostakopio = "kylla";

				// tulostetaan toimitustavan määrittelemä rahtikirja
				if (file_exists("tilauskasittely/$toitarow[rahtikirja]")) {
					require("tilauskasittely/$toitarow[rahtikirja]");
				}
				else {
					echo "<li><font class='error'>".t("VIRHE: Rahtikirja-tiedostoa")." 'tilauskasittely/$toitarow[rahtikirja]' ".t("ei löydy")."!</font>";
				}
			}
			echo "<br>";

		} // end while haetaan kaikki distinct rahtikirjat..


		$tee = '';
		echo "<br>";

	} // end tee==tulosta
		
	if ($tee == 'valitse') {

		if ($otunnus == "") {
			$query = "	select rahtikirjanro, sum(kilot) paino
						from rahtikirjat
						where yhtio		= '$kukarow[yhtio]' and
						tulostuspaikka	= '$varasto' and
						toimitustapa	= '$toimitustapa' and
						tulostettu		> '$vv-$kk-$pp 00:00:00' and
						tulostettu		< '$vv-$kk-$pp 23:59:59'
						GROUP BY rahtikirjanro";
		}
		else {
		    $query = "	SELECT rahtikirjanro 
						from rahtikirjat 
						where otsikkonro = '$otunnus'
		            	and yhtio = '{$kukarow['yhtio']}'";
		    $res = mysql_query($query) or pupe_error($query);
		    $rahtikirjanro = mysql_fetch_array($res);

			$query = "	select rahtikirjanro, sum(kilot) paino
						from rahtikirjat
						where yhtio			= '$kukarow[yhtio]'
						and rahtikirjanro	= '{$rahtikirjanro['rahtikirjanro']}'
						and tulostettu != '0000-00-00 00:00:00'
						GROUP BY rahtikirjanro";

			$toimitustapa 	= "";
			$varasto 		= "";
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
			echo "<th>".t("Paino KG")."</th>";
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
					echo "<td style='text-align: right;'>" . round($row['paino'], 2) . "</td>";
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

	if ($tee == '') {
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