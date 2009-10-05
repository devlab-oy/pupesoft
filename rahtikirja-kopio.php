<?php

	require("inc/parametrit.inc");

	$logistiikka_yhtio = '';
	$logistiikka_yhtiolisa = '';

	if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
		$logistiikka_yhtio = $konsernivarasto_yhtiot;
		$logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

		if ($lasku_yhtio != '') {
			$kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);

			$yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
		}
	}
	else {
		$logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
	}

	echo "<font class='head'>".t("Rahtikirjakopio")."</font><hr>";

	if ($tee == 'tulosta' and (!isset($rtunnukset) or count($rtunnukset) == 0)) {
		echo "<font class='error'>Et valinnut yhtään rahtikirjaa!</font><br>";
		$tee = "";
	}

	if ($tee == 'tulosta') {

		// rahtikirjojen tulostus vaatii seuraavat muuttujat:
		// $toimitustapa_varasto	toimitustavan selite!!!!varastopaikan tunnus
		// $tee						tässä pitää olla teksti tulosta

		if ($yksittainen == "ON") {
			//Tässä on haettava tulostettavan tilauksen tiedot
			$query = "	SELECT toimitustapa, tulostuspaikka, group_concat(otsikkonro) otsikkonro
						FROM rahtikirjat
						WHERE yhtio			= '$kukarow[yhtio]'
						and rahtikirjanro	= '$rtunnukset[0]'
						GROUP BY 1,2
						LIMIT 1";
			$ores  = mysql_query($query) or pupe_error($query);
			$rrow  = mysql_fetch_array($ores);

			$toimitustapa	= $rrow["toimitustapa"];
			$varasto		= $rrow["tulostuspaikka"];
			$sel_ltun		= explode(",", $rrow["otsikkonro"]);
		}
		else {
			//Tässä on haettava tulostettavien tilausten tunnukset
			$query = "	SELECT group_concat(otsikkonro) otsikkonro
						FROM rahtikirjat
						WHERE yhtio			= '$kukarow[yhtio]'
						and rahtikirjanro	in ('".implode("','", $rtunnukset)."')";
			$ores  = mysql_query($query) or pupe_error($query);
			$rrow  = mysql_fetch_array($ores);

			$sel_ltun = explode(",", $rrow["otsikkonro"]);
		}

		$toimitustapa_varasto = $toimitustapa."!!!!".$kukarow['yhtio']."!!!!".$varasto;
		$tee				  = "tulosta";

		require ("rahtikirja-tulostus.php");

		$tee = '';
		echo "<br>";

	}

	if ($tee == 'valitse') {

		if ($toimitustapa != '') {
			list($toimitustapa, $yhtio) = explode("!!!!", $toimitustapa);
			$kukarow['yhtio'] = $yhtio;
		}

		if ($otunnus == "") {
			$query = "	SELECT yhtio, rahtikirjanro, sum(kilot) paino
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
		            	and yhtio = '$kukarow[yhtio]'";
		    $res = mysql_query($query) or pupe_error($query);
		    $rahtikirjanro = mysql_fetch_array($res);

			$query = "	SELECT rahtikirjanro, sum(kilot) paino, yhtio
						from rahtikirjat
						where yhtio			= '$kukarow[yhtio]'
						and rahtikirjanro	= '$rahtikirjanro[rahtikirjanro]'
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
			echo "<input type='hidden' name='lasku_yhtio' value='$logistiikka_yhtio'>";
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
			if ($logistiikka_yhtio != '') echo "<th>",t("Yhtiö"),"</th>";
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
					$query = "SELECT otsikkonro, tulostettu from rahtikirjat where yhtio='$kukarow[yhtio]' and rahtikirjanro='$row[rahtikirjanro]' limit 1";
					$ores  = mysql_query($query) or pupe_error($query);
					$rrow  = mysql_fetch_array($ores);

					$query = "SELECT ytunnus, nimi, nimitark, toim_osoite, toim_postino, toim_postitp, tunnus from lasku where yhtio='$kukarow[yhtio]' and tunnus='$rrow[otsikkonro]'";
					$ores  = mysql_query($query) or pupe_error($query);
					$orow  = mysql_fetch_array($ores);

					echo "<tr>";
					if ($logistiikka_yhtio != '') echo "<td>$row[yhtio]</td>";
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

			echo t("Rahtikirjatulostin"),"<br>";
			echo "<select name='komento'>";
			echo "<option value=''>".t("Oletustulostimelle")."</option>";

			while ($kirrow = mysql_fetch_array($kirre)) {
				echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
			}

			echo "</select><br><br>";


			echo t("Tulosta osoitelaput"),"<br>";


			mysql_data_seek($kirre, 0);

			echo "<td>";
			echo "<select name='valittu_rakiroslapp_tulostin'>";
			echo "<option value=''>",t("Ei tulosteta"),"</option>";

			while ($kirrow = mysql_fetch_array($kirre)) {
				echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
			}

			echo "</select><br><br>";

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

		$query  = "SELECT * FROM toimitustapa WHERE nouto='' and $logistiikka_yhtiolisa order by jarjestys, selite";
		$result = mysql_query($query) or pupe_error($query);

		echo "<tr><th>".t("Valitse toimitustapa").":</th>";
		echo "<td><select name='toimitustapa'>";

		while ($row = mysql_fetch_array($result)) {
			if ($toimitustapa==$row['selite']) $sel=" selected ";
			else $sel = "";

			echo "<option value='$row[selite]!!!!$row[yhtio]' $sel>".t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV");
			if ($logistiikka_yhtio != '') {
				echo " ($row[yhtio])";
			}
			echo "</option>";
		}

		echo "</select></td></tr>";

		// haetaan kaikki varastot
		$query  = "SELECT tunnus, nimitys, yhtio FROM varastopaikat WHERE $logistiikka_yhtiolisa";
		$result = mysql_query($query) or pupe_error($query);

		// jos löytyy enemmän kuin yksi, tehdään varasto popup..
		if (mysql_num_rows($result)>1) {
			echo "<tr><th>".t("Valitse varasto").":</th>";
			echo "<td><select name='varasto'>";

			while ($row = mysql_fetch_array($result)) {
				if ($varasto==$row['tunnus']) $sel=" selected ";
				else $sel = "";
				echo "<option value='$row[tunnus]' $sel>$row[nimitys]";
				if ($logistiikka_yhtio != '') {
					echo " ($row[yhtio])";
				}
				echo "</option>";
			}

			echo "</select></td></tr>";
		}
		else {
			$row = mysql_fetch_array($result);
			echo "<input type='hidden' name='varasto' value='$row[tunnus]'>";
			echo "<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>";
		}

		echo "</table><br>";
		echo "<input type='submit' value='".t("Tulosta")."'>";
		echo "</form>";
	}

	require("inc/footer.inc");
?>
