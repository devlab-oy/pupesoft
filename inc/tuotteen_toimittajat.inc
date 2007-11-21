<?php

	echo "<font class='head'>".t("Tuotteen toimittajat")."</font><hr>";

	if ($oikeurow['paivitys'] != '1') { // Saako p‰ivitt‰‰
		echo "<br><font class='error'>".t("Sun natsat ei riit‰ t‰h‰ hommaan poika")."!</font><br>";
		exit;
	}

	if ($tuoteno == "" and $uusi == 1) {
		
		echo "	
		<form action='#' method='POST'>
		<input type='button' onclick='javascript:document.mainform.submit();' name='kikkeli' value='".t("Lis‰‰ uusi toimittaja")."'>
		</form>";
	}
	elseif($tuoteno == "") {
		echo "<font class='error'>".t("Tuote katosi")."!</font>";	
	}
	else {

		$ostohinta 		= str_replace(',','.', $ostohinta);
		$tuotekerroin 	= str_replace(',','.', $tuotekerroin);
		$osto_era 		= str_replace(',','.', $osto_era);

		if ($kikkeli == 1) {			
			$tee = "uusi";
		}

		// tehd‰‰n tarkastuksia ja muuta kivaa
		if ($tee != "uusi" and $tee != "poista" and $tee != "") {
			require ("inc/tuotteen_toimittajattarkista.inc");
		}

		//	synkkausta varten!
		if($toimtunnus>0) {
			$query = "select * from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and tunnus='$toimtunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$orgirow=mysql_fetch_array($result) or pupe_error($query);			
		}		

		// p‰ivitet‰‰n toimittaja
		if ($tee == "update") {			
			$query = "	update tuotteen_toimittajat set
						yhtio			= '$kukarow[yhtio]',
						tuoteno			= '$tuoteno',
						toimittaja		= '$tuotoimirow[ytunnus]',
						osto_era		= '$osto_era',
						ostohinta		= '$ostohinta',
						alennus			= '$alennus',
						valuutta		= '$valuutta',
						toim_tuoteno	= '$toim_tuoteno',
						toim_nimitys	= '$toim_nimitys',
						toim_yksikko	= '$toim_yksikko',						
						tuotekerroin	= '$tuotekerroin',
						alkuperamaa		= '$alkuperamaa',
						toimitusaika	= '$toimitusaika',
						pakkauskoko		= '$pakkauskoko',
						liitostunnus	= '$tuotoimirow[tunnus]'
						where yhtio = '$kukarow[yhtio]' and tunnus = '$toimtunnus'";
			$result = mysql_query($query) or pupe_error($query);
			
			synkronoi("tuotteen_toimittajat", $toimtunnus, $orgirow);
			$tee = "";
		}

		// poistetaan toimittaja
		if ($tee == "poista") {
			$query = "delete from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and tunnus='$toimtunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$tee = "";
			
			synkronoi("tuotteen_toimittajat", $toimtunnus, $orgirow);			
		}

		// lis‰t‰‰n toimittaja
		if ($tee == "insert") {
			$query = "	insert into tuotteen_toimittajat set
						yhtio			= '$kukarow[yhtio]',
						tuoteno			= '$tuoteno',
						toimittaja		= '$tuotoimirow[ytunnus]',
						osto_era		= '$osto_era',
						ostohinta		= '$ostohinta',
						alennus			= '$alennus',
						valuutta		= '$valuutta',
						toim_tuoteno	= '$toim_tuoteno',
						toim_nimitys	= '$toim_nimitys',
						toim_yksikko	= '$toim_yksikko',						
						tuotekerroin	= '$tuotekerroin',
						alkuperamaa		= '$alkuperamaa',
						toimitusaika	= '$toimitusaika',
						pakkauskoko		= '$pakkauskoko',
						liitostunnus	= '$tuotoimirow[tunnus]'";
			$result = mysql_query($query) or pupe_error($query);
			synkronoi("tuotteen_toimittajat", mysql_insert_id(), $origrow);			
			$tee = "";
		}

		// uuden syˆttˆ
		if ($tee == "uusi") {

			// teh‰‰n maa dropdowni
			$query = "	SELECT distinct koodi, nimi
						FROM maat
						WHERE nimi != ''
						ORDER BY koodi";
			$vresult = mysql_query($query) or pupe_error($query);

			$maat  = "<select name='alkuperamaa'>";
			$maat .= "<option value=''></option>";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel = "";
				if ($vrow[0] == $alkuperamaa) $sel = "selected";
				$maat .= "<option value='$vrow[0]' $sel>".t("$vrow[1]")."</option>";
			}
			$maat .= "</select>";

			// teh‰‰n valuutat dropdowni
			$query = "	SELECT nimi, tunnus
						FROM valuu
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY jarjestys";
			$vresult = mysql_query($query) or pupe_error($query);

			$valuu = "<select name='valuutta'>";
			while ($vrow = mysql_fetch_array($vresult)) {
				$sel = "";
				if ($valuutta == $vrow['nimi']) $sel = "selected";
				$valuu .= "<option value='$vrow[nimi]' $sel>$vrow[nimi]</option>";
			}
			$valuu .= "</select>";

			echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";

			echo "<table>";
			echo "<tr><th>".t("toimittaja")."</th>	<td>";

			if ($toim_valinta[0] != "") {
				echo "$toim_valinta[0]";
			}
			elseif($tuotoimirow["tunnus"] > 0) {
				echo "<input type='hidden' name='toimittajatunnus' 	value='$tuotoimirow[tunnus]'>$tuotoimirow[nimi] $tuotoimirow[nimitark] - $tuotoimirow[ovttunnus]";
			}
			else {
				echo "<input type='text' name='toimittaja' 	value='$toimittaja'>";
			}

			echo "</td><td class='back'>".$toimerrorit[0]["toimittaja"]."</td></tr>";

			
			echo "<tr><th>".t("osto_era")."</th>			<td><input type='text' name='osto_era' 		value='$osto_era'>			</td><td class='back'>".$toimerrorit[0]["osto_era"]."</td></tr>";
			echo "<tr><th>".t("pakkauskoko")."</th>			<td><input type='text' name='pakkauskoko'	value='$pakkauskoko'>		</td><td class='back'>".$toimerrorit[0]["pakkauskoko"]."</td></tr>";
			echo "<tr><th>".t("toimitusaika")."</th>		<td><input type='text' name='toimitusaika'	value='$toimitusaika'>		</td><td class='back'>".$toimerrorit[0]["toimitusaika"]."</td></tr>";
			echo "<tr><th>".t("ostohinta")."</th>			<td><input type='text' name='ostohinta' 	value='$ostohinta'>			</td><td class='back'>".$toimerrorit[0]["ostohinta"]."</td></tr>";
			echo "<tr><th>".t("alennus")."</th>				<td><input type='text' name='alennus'	 	value='$alennus'>			</td><td class='back'>".$toimerrorit[0]["alennus"]."</td></tr>";
			echo "<tr><th>".t("valuutta")."</th>			<td>$valuu																</td><td class='back'>".$toimerrorit[0]["valuutta"]."</td></tr>";
			echo "<tr><th>".t("toim_tuoteno")."</th>		<td><input type='text' name='toim_tuoteno' 	value='$toim_tuoteno'>		</td><td class='back'>".$toimerrorit[0]["toim_tuoteno"]."</td></tr>";
			echo "<tr><th>".t("toim_nimitys")."</th>		<td><input type='text' name='toim_nimitys'	value='$trow[toim_nimitys]'></td><td class='back'>".$toimerrorit[0]["toim_nimitys"]."</td></tr>";
			echo "<tr><th>".t("toim_yksikko")."</th>		<td><input type='text' name='toim_yksikko' maxlength='3' size='4' 	value='$toim_yksikko'>		</td><td class='back'>".$toimerrorit[0]["toim_yksikko"]."</td></tr>";			
			echo "<tr><th>".t("tuotekerroin")."</th>		<td><input type='text' name='tuotekerroin'	value='$tuotekerroin'>		</td><td class='back'>".$toimerrorit[0]["tuotekerroin"]."</td></tr>";
			echo "<tr><th>".t("alkuperamaa")."</th>			<td>$maat																</td><td class='back'>".$toimerrorit[0]["alkuperamaa"]."</td></tr>";
			
			echo "</table><hr>";

			echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
			echo "<input type='hidden' name='tee' value='insert'>";
			echo "<input type='hidden' name='toim' value='$aputoim'>";
			echo "<input type='hidden' name='tunnus' value='$tunnus'>";
			echo "<input type='hidden' name='toimtunnus' value='$toimtunnus'>";
			echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
			echo "<input type='submit' value='".t("Perusta toimittaja")."'></form>";
		}

		// selataan vanhoja
		if ($tee == "") {
			$query  = "select * from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
			$result = mysql_query($query) or pupe_error($query);

			while ($trow = mysql_fetch_array($result)) {

				// teh‰‰n maa dropdowni
				$query = "	SELECT distinct koodi, nimi
							FROM maat
							WHERE nimi != ''
							ORDER BY koodi";
				$vresult = mysql_query($query) or pupe_error($query);

				$maat  = "<select name='alkuperamaa'>";
				$maat .= "<option value=''></option>";

				while ($vrow=mysql_fetch_array($vresult)) {
					$sel = "";
					if ($vrow[0] == $trow["alkuperamaa"]) $sel = "selected";
					$maat .= "<option value='$vrow[0]' $sel>".t("$vrow[1]")."</option>";
				}
				$maat .= "</select>";

				// teh‰‰n valuutat dropdowni
				$query = "	SELECT nimi, tunnus
							FROM valuu
							WHERE yhtio = '$kukarow[yhtio]'
							ORDER BY jarjestys";
				$vresult = mysql_query($query) or pupe_error($query);

				$valuu = "<select name='valuutta'>";
				while ($vrow = mysql_fetch_array($vresult)) {
					$sel = "";
					if ($trow["valuutta"] == $vrow['nimi']) $sel = "selected";
					$valuu .= "<option value='$vrow[nimi]' $sel>$vrow[nimi]</option>";
				}
				$valuu .= "</select>";

				// haetaan toimittajan nimi jos ytunnuksessa ei ole vikaaa
				if ($toimerrorit[$trow["tunnus"]]["toimittaja"] == "") {
					$query   = "select nimi, nimitark from toimi where yhtio='$kukarow[yhtio]' and tunnus='$trow[liitostunnus]'";
					$vresult = mysql_query($query) or pupe_error($query);
					$tvrosw  = mysql_fetch_array($vresult);
				}
				else {
					$tvrosw  = array();
				}

				echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";
				echo "<table>";
				echo "<tr><th>".t("toimittaja")."</th>	<td>";

				if ($toim_valinta[$toimtunnus] != "") {
					echo "$toim_valinta[$toimtunnus]";
				}
				else {
					echo "$trow[toimittaja] $tvrosw[nimi] $tvrosw[nimitark] ($trow[liitostunnus])";
				}

				echo "</td><td class='back'>".$toimerrorit[$trow["tunnus"]]["toimittaja"]."</td></tr>";
				
				if ($alias_set != '') {
					if ($rajattu_nakyma != '') {
						$al_lisa = " and selitetark_2 = '$alias_set' ";
					}
					else {
						$al_lisa = " and (selitetark_2 = '$alias_set' or selitetark_2 = '') ";
					}
				}

				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji='MYSQLALIAS'
							and selite='tuotteen_toimittajat.osto_era'
							$al_lisa";
				$al_res = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($al_res) > 0) {
					$al_row = mysql_fetch_array($al_res);

					echo "<tr><th>$al_row[selitetark]</th><td><input type='text' name='osto_era'value='$trow[osto_era]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["osto_era"]."</td></tr>";
				}
				elseif ($rajattu_nakyma == '') {
						echo "<tr><th>".t("osto_era")."</th><td><input type='text' name='osto_era'value='$trow[osto_era]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["osto_era"]."</td></tr>";
				}
				else {
					echo "<input type='hidden' name='osto_era'value='$trow[osto_era]'>";
				}
				
				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji='MYSQLALIAS'
							and selite='tuotteen_toimittajat.pakkauskoko'
							$al_lisa";
				$al_res = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($al_res) > 0) {
					$al_row = mysql_fetch_array($al_res);

					echo "<tr><th>$al_row[selitetark]</th><td><input type='text' name='pakkauskoko'value='$trow[pakkauskoko]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["pakkauskoko"]."</td></tr>";
				}
				elseif ($rajattu_nakyma == '') {
						echo "<tr><th>".t("pakkauskoko")."</th><td><input type='text' name='pakkauskoko'value='$trow[pakkauskoko]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["pakkauskoko"]."</td></tr>";
				}
				else {
					echo "<input type='hidden' name='pakkauskoko'value='$trow[pakkauskoko]'>";
				}
				
				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji='MYSQLALIAS'
							and selite='tuotteen_toimittajat.toimitusaika'
							$al_lisa";
				$al_res = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($al_res) > 0) {
					$al_row = mysql_fetch_array($al_res);

					echo "<tr><th>$al_row[selitetark]</th><td><input type='text' name='toimitusaika'value='$trow[toimitusaika]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["toimitusaika"]."</td></tr>";
				}
				elseif ($rajattu_nakyma == '') {
						echo "<tr><th>".t("toimitusaika")."</th><td><input type='text' name='toimitusaika'value='$trow[toimitusaika]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["toimitusaika"]."</td></tr>";
				}
				else {
					echo "<input type='hidden' name='toimitusaika'value='$trow[toimitusaika]'>";
				}
				
				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji='MYSQLALIAS'
							and selite='tuotteen_toimittajat.ostohinta'
							$al_lisa";
				$al_res = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($al_res) > 0) {
					$al_row = mysql_fetch_array($al_res);

					echo "<tr><th>$al_row[selitetark]</th><td><input type='text' name='ostohinta' value='$trow[ostohinta]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["ostohinta"]."</td></tr>";
				}
				elseif ($rajattu_nakyma == '') {
						echo "<tr><th>".t("ostohinta")."</th><td><input type='text' name='ostohinta' value='$trow[ostohinta]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["ostohinta"]."</td></tr>";
				}
				else {
					echo "<input type='hidden' name='ostohinta'value='$trow[ostohinta]'>";
				}
				
				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji='MYSQLALIAS'
							and selite='tuotteen_toimittajat.alennus'
							$al_lisa";
				$al_res = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($al_res) > 0) {
					$al_row = mysql_fetch_array($al_res);

					echo "<tr><th>$al_row[selitetark]</th><td><input type='text' name='alennus' value='$trow[alennus]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["alennus"]."</td></tr>";
				}
				elseif ($rajattu_nakyma == '') {
						echo "<tr><th>".t("alennus")."</th><td><input type='text' name='alennus' value='$trow[alennus]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["alennus"]."</td></tr>";
				}
				else {
					echo "<input type='hidden' name='alennus'value='$trow[alennus]'>";
				}
				
				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji='MYSQLALIAS'
							and selite='tuotteen_toimittajat.valuutta'
							$al_lisa";
				$al_res = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($al_res) > 0) {
					$al_row = mysql_fetch_array($al_res);

					echo "<tr><th>$al_row[selitetark]</th><td>$valuu</td><td class='back'>".$toimerrorit[$trow["tunnus"]]["valuutta"]."</td></tr>";
				}
				elseif ($rajattu_nakyma == '') {
						echo "<tr><th>".t("valuutta")."</th><td>$valuu</td><td class='back'>".$toimerrorit[$trow["tunnus"]]["valuutta"]."</td></tr>";
				}
				else {
					echo "<input type='hidden' name='valuutta' value='$trow[valuutta]'>";
				}
				
				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji='MYSQLALIAS'
							and selite='tuotteen_toimittajat.toim_tuoteno'
							$al_lisa";
				$al_res = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($al_res) > 0) {
					$al_row = mysql_fetch_array($al_res);

					echo "<tr><th>$al_row[selitetark]</th><td><input type='text' name='toim_tuoteno' value='$trow[toim_tuoteno]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["toim_tuoteno"]."</td></tr>";
				}
				elseif ($rajattu_nakyma == '') {
						echo "<tr><th>".t("toim_tuoteno")."</th><td><input type='text' name='toim_tuoteno' value='$trow[toim_tuoteno]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["toim_tuoteno"]."</td></tr>";
				}
				else {
					echo "<input type='hidden' name='toim_tuoteno' value='$trow[toim_tuoteno]'>";
				}
				
				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji='MYSQLALIAS'
							and selite='tuotteen_toimittajat.toim_nimitys'
							$al_lisa";
				$al_res = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($al_res) > 0) {
					$al_row = mysql_fetch_array($al_res);

					echo "<tr><th>$al_row[selitetark]</th><td><input type='text' name='toim_nimitys' value='$trow[toim_nimitys]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["toim_nimitys"]."</td></tr>";
				}
				elseif ($rajattu_nakyma == '') {
						echo "<tr><th>".t("toim_nimitys")."</th><td><input type='text' name='toim_nimitys' value='$trow[toim_nimitys]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["toim_nimitys"]."</td></tr>";
				}
				else {
					echo "<input type='hidden' name='toim_nimitys' value='$trow[toim_nimitys]'>";
				}

				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji='MYSQLALIAS'
							and selite='tuotteen_toimittajat.toim_yksikko'
							$al_lisa";
				$al_res = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($al_res) > 0) {
					$al_row = mysql_fetch_array($al_res);

					echo "<tr><th>$al_row[selitetark]</th><td><input type='text' name='toim_yksikko' maxlength='3' size='4' value='$trow[toim_yksikko]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["toim_yksikko"]."</td></tr>";
				}
				elseif ($rajattu_nakyma == '') {
						echo "<tr><th>".t("toim_yksikko")."</th><td><input type='text' name='toim_yksikko' maxlength='3' size='4' value='$trow[toim_yksikko]'></td><td class='back'>".$toimerrorit[$trow["tunnus"]]["toim_yksikko"]."</td></tr>";
				}
				else {
					echo "<input type='hidden' name='toim_yksikko' maxlength='3' size='4' value='$trow[toim_yksikko]'>";
				}
				
				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji='MYSQLALIAS'
							and selite='tuotteen_toimittajat.tuotekerroin'
							$al_lisa";
				$al_res = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($al_res) > 0) {
					$al_row = mysql_fetch_array($al_res);

					echo "<tr><th>$al_row[selitetark]</th><td><input type='text' name='tuotekerroin'	value='$trow[tuotekerroin]'><td class='back'>".$toimerrorit[$trow["tunnus"]]["tuotekerroin"]."</td></tr>";
				}
				elseif ($rajattu_nakyma == '') {
						echo "<tr><th>".t("tuotekerroin")."</th><td><input type='text' name='tuotekerroin'	value='$trow[tuotekerroin]'><td class='back'>".$toimerrorit[$trow["tunnus"]]["tuotekerroin"]."</td></tr>";
				}
				else {
					echo "<input type='hidden' name='tuotekerroin'value='$trow[tuotekerroin]'>";
				}
				
				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							and laji='MYSQLALIAS'
							and selite='tuotteen_toimittajat.alkuperamaa'
							$al_lisa";
				$al_res = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($al_res) > 0) {
					$al_row = mysql_fetch_array($al_res);

					echo "<tr><th>$al_row[selitetark]</th><td>$maat</td><td class='back'>".$toimerrorit[$trow["tunnus"]]["alkuperamaa"]."</td></tr>";
				}
				elseif ($rajattu_nakyma == '') {
						echo "<tr><th>".t("alkuperamaa")."</th><td>$maat</td><td class='back'>".$toimerrorit[$trow["tunnus"]]["alkuperamaa"]."</td></tr>";
				}
				else {
					echo "<input type='hidden' name='alkuperamaa'value='$trow[alkuperamaa]'>";
				}

				echo "<tr><td class='back'>";
				echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
				echo "<input type='hidden' name='tee' value='update'>";
				echo "<input type='hidden' name='toim' value='$aputoim'>";
				echo "<input type='hidden' name='tunnus' value='$tunnus'>";
				echo "<input type='hidden' name='tuoteno' value='$trow[tuoteno]'>";
				echo "<input type='hidden' name='toimtunnus'  value='$trow[tunnus]'>";
				echo "<input type='hidden' name='toimittajatunnus'  value='$trow[liitostunnus]'>";
				echo "<input type='submit' value='".t("P‰ivit‰ toimittaja")."'></td></form>";
				
				if ($rajattu_nakyma == '') {
					echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";
					echo "<td class='back'>";
					echo "<input type='hidden' name='tee' value='poista'>";
					echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
					echo "<input type='hidden' name='toim' value='$aputoim'>";
					echo "<input type='hidden' name='tunnus' value='$tunnus'>";
					echo "<input type='hidden' name='tuoteno' value='$trow[tuoteno]'>";
					echo "<input type='hidden' name='toimtunnus'  value='$trow[tunnus]'>";
					echo "<input type='submit' value='".t("Poista toimittaja")."'></td></form>";
				}

				echo "</tr>";
				echo "</table>";
			}
			
			if ($rajattu_nakyma == '') {
				echo "<br><form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>
				<input type='hidden' name='tee' value='uusi'>
				<input type='hidden' name='lopetus' value = '$lopetus'>
				<input type='hidden' name='toim' value='$aputoim'>
				<input type='hidden' name='tunnus' value='$tunnus'>
				<input type='hidden' name='tuoteno' value='$tuoteno'>
				<input type='submit' value='".t("Lis‰‰ uusi toimittaja")."'></form>";
			}
			echo "<hr><br><br>";
		}
	}

?>
