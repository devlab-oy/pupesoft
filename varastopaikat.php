<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Varastopaikat")."</font><hr>";

// Tarkistetaan syˆtetyt tieedot
if ($tee == 'update') {
	//Katotaan osuuko alkuhyllyalue johonkin varastoon
	$query = "	SELECT tunnus
				FROM varastopaikat
				WHERE
				tunnus != '$tunnus'
				and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$alkuhyllyalue') ,5,'0'),lpad(upper('$alkuhyllynro') ,5,'0'))
				and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$alkuhyllyalue') ,5,'0'),lpad(upper('$alkuhyllynro') ,5,'0'))
				and yhtio = '$kukarow[yhtio]'";
	$vares = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($vares) == 0) {
			//Katotaan osuuko loppuhyllyalue johonkin varastoon
			$query = "	SELECT tunnus
						FROM varastopaikat
						WHERE
						tunnus != '$tunnus'
						and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$loppuhyllyalue') ,5,'0'),lpad(upper('$loppuhyllynro') ,5,'0'))
						and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$loppuhyllyalue') ,5,'0'),lpad(upper('$loppuhyllynro') ,5,'0'))
						and yhtio = '$kukarow[yhtio]'";
			$vares = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($vares) == 0) {
				//Katotaan onko jo joku varasto syˆtetyn alueen sis‰ll‰
				$query = "	SELECT tunnus
							FROM varastopaikat
							WHERE
							tunnus != '$tunnus'
							and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper('$loppuhyllyalue') ,5,'0'),lpad(upper('$loppuhyllynro') ,5,'0'))
							and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper('$alkuhyllyalue') ,5,'0'),lpad(upper('$alkuhyllynro') ,5,'0'))
							and yhtio = '$kukarow[yhtio]'";
				$vares = mysql_query($query) or pupe_error($query);
			}
	}

	if (mysql_num_rows($vares) != 0) {
		echo "<br><font class='error'>".t("VIRHE: P‰‰llekk‰isi‰ varastoalueita")."!</font><br>";
		$tee = "edit";
	}
	else {
		if ($printteri2 == '' or $printteri4 == '' or $printteri6 == '') {
			echo "<br><font class='error'>".t("VIRHE: Rahtikirja tulostimet ei saa olla tyhji‰")."!</font><br>";
			$tee = "edit";
		}
	}
}

// p‰ivitet‰‰n varastopaikat...
if ($tee == 'update') {

	$alkuhyllyalue  = trim(strtoupper($alkuhyllyalue));
	$alkuhyllynro   = trim(strtoupper($alkuhyllynro));
	$loppuhyllyalue = trim(strtoupper($loppuhyllyalue));
	$loppuhyllynro  = trim(strtoupper($loppuhyllynro));

	$query  = "	update varastopaikat set
				alkuhyllyalue	= '$alkuhyllyalue',
				alkuhyllynro	= '$alkuhyllynro',
				loppuhyllyalue	= '$loppuhyllyalue',
				loppuhyllynro	= '$loppuhyllynro',
				printteri1		= '$printteri1',
				printteri2		= '$printteri2',
				printteri3		= '$printteri3',
				printteri4		= '$printteri4',
				printteri5		= '$printteri5',
				printteri6		= '$printteri6',
				printteri7		= '$printteri7',
				tyyppi			= '$tyyppi',
				nimitys			= '$nimitys',
				nimi			= '$nimi',
				nimitark		= '$nimitark',
				osoite			= '$osoite',
				postino			= '$postino',
				postitp			= '$postitp',
				maa				= '$maa',
				sallitut_maat	= '$sallitut_maat'
				where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	echo "<h2>".t("Varastopaikat p‰ivitetty")."</h2>";

	$tee = ''; // n‰ytet‰‰n printerit
}

// poistetaan varastopaikat
if ($tee == 'poista') {
	$query = "DELETE from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	$tee = ''; // n‰ytet‰‰n printterit
}

// tehd‰‰n uusi varastopaikat
if ($tee == 'uusi') {
	$query = "INSERT into varastopaikat (yhtio, alkuhyllyalue, alkuhyllynro, loppuhyllyalue, loppuhyllynro) values ('$kukarow[yhtio]','     ','     ','     ','     ')";
	$result = mysql_query($query) or pupe_error($query);
	$tunnus = mysql_insert_id($link);

	$tee = 'edit'; // menn‰‰n muokkausruutuun
}

// n‰ytet‰‰n muokkausruutu
if ($tee == 'edit') {
	$query  = "	SELECT *
				from varastopaikat
				where yhtio = '$kukarow[yhtio]'
				and tunnus = '$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$row    = mysql_fetch_array($result);

	echo "<form method='post'>
	<input type='hidden' name='tee' value='update'>
	<input type='hidden' name='tunnus' value='$row[tunnus]'>";

	echo "<table>\n";

	for ($i=1; $i<mysql_num_fields($result)-1; $i++) {

		if (substr(mysql_field_name($result,$i),0,9) == 'printteri') {

			echo "<tr><th>";

			switch (mysql_field_name($result,$i)) {
				// tehd‰‰n selkokieliset nimet... t‰m‰ tietysti olisi pit‰nyt tehd‰ tietokantaan suoraan mutta en jaksa en‰‰ muuttaa. :)
				case "printteri1":
					echo t("L‰hete/Ker‰yslista");
					break;
				case "printteri2":
					echo t("Rahtikirja matriisi");
					break;
				case "printteri3":
					echo t("Osoitelappu");
					break;
				case "printteri4":
					echo t("Rahtikirja A5");
					break;
				case "printteri5":
					echo t("Lasku");
					break;
				case "printteri6":
					echo t("Rahtikirja A4");
					break;
				case "printteri7":
					echo t("JV-lasku/-kuitti");
					break;
				default:
					echo t(mysql_field_name($result,$i));
			}

			echo "</th><td>";

			$query = "	SELECT *
						FROM kirjoittimet
						WHERE yhtio='$kukarow[yhtio]'
						AND komento != 'EDI'
						ORDER BY kirjoitin";
			$kires = mysql_query($query) or pupe_error($query);

			echo "<select name='".mysql_field_name($result,$i)."'>";
			echo "<option value=''>".t("Ei kirjoitinta")."</option>";

			while ($kirow=mysql_fetch_array($kires)) {
				if ($kirow["tunnus"]==$row[$i]) $select='SELECTED';
				else $select = '';

				echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
			}

			echo "</select>";

			echo "</td></tr>\n";
		}
		elseif (mysql_field_name($result,$i) == 'tyyppi') {
			echo "<tr><th>".t("Tyyppi")."</th><td>";

			$sel1 = $sel2 = '';

			if($row["tyyppi"] == "") {
				$sel1 = "SELECTED";
			}
			elseif($row["tyyppi"] == "V") {
				$sel2 = "SELECTED";
			}
			elseif($row["tyyppi"] == "E") {
				$sel3 = "SELECTED";
			}

			echo "<select name='tyyppi'>";
			echo "<option value=''  $sel1>".t("Normaalivarasto josta kaikki myyv‰t")."</option>";
			echo "<option value='V' $sel2>".t("Erikoisvarasto josta voi myyd‰")."</option>";
			echo "<option value='E' $sel3>".t("Erikoisvarasto josta automaattisesti ei myyd‰")."</option>";
			echo "</select>";

			echo "</td></tr>\n";
		}
		elseif (mysql_field_name($result, $i) == "maa") {
			$query = "	SELECT distinct koodi, nimi
						FROM maat
						WHERE nimi != ''
						ORDER BY koodi";
			$vresult = mysql_query($query) or pupe_error($query);
			echo "<tr><th>".t("Maa")."</th><td><select name='maa'>";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if (strtoupper($row['maa']) == strtoupper($vrow[0])) {
					$sel = "selected";
				}
				elseif($row['maa'] == "" and strtoupper($vrow[0]) == strtoupper($yhtiorow["maa"])) {
					$sel = "selected";
				}
				echo "<option value = '".strtoupper($vrow[0])."' $sel>".t($vrow[1])."</option>";
			}

			echo "</select></td></tr>";
			//echo "kala $trow[$i]<br>";
		}
		else {
			echo "<tr><th>".t(mysql_field_name($result,$i))."</th><td><input type='text' name='".mysql_field_name($result,$i)."' value='$row[$i]'></td></tr>\n";
		}
	}

	echo "</table>";

	echo "<input type='submit' value='".t("P‰ivit‰")."'></form>";
}

// n‰ytet‰‰n kaikki yhtion varastopaikat...
if ($tee == '') {

	$query  = "	SELECT varastopaikat.*
				FROM varastopaikat
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tyyppi, nimitys";
	$result = mysql_query($query) or pupe_error($query);

	echo "<table><tr>";
	echo "<th>".t("alkuhyllyalue")."</th>";
	echo "<th>".t("alkuhyllynro")."</th>";
	echo "<th>".t("loppuhyllyalue")."</th>";
	echo "<th>".t("loppuhyllynro")."</th>";
	echo "<th>".t("tyyppi")."</th>";
	echo "<th>".t("nimitys")."</th>";
	echo "<th colspan='2'></th></tr>";

	while ($row = mysql_fetch_array($result)) {
		echo "<tr>";
		echo "<td>$row[alkuhyllyalue]</td>";
		echo "<td>$row[alkuhyllynro]</td>";
		echo "<td>$row[loppuhyllyalue]</td>";
		echo "<td>$row[loppuhyllynro]</td>";
		echo "<td>$row[tyyppi]</td>";
		echo "<td>$row[nimitys]</td>";

		echo "<form method='post'>
		<input type='hidden' name='tee' value='edit'>
		<input type='hidden' name='tunnus' value='$row[tunnus]'>
		<td><input type='submit' value='".t("Muokkaa")."'></td></form>";

		echo "<SCRIPT LANGUAGE=JAVASCRIPT>
					function verify(){
							msg = '".t("Haluatko todella poistaa t‰m‰n tietueen?")."';
							return confirm(msg);
					}
			</SCRIPT>";

		echo "<form method='post' onSubmit = 'return verify()'>
		<input type='hidden' name='tee' value='poista'>
		<input type='hidden' name='tunnus' value='$row[tunnus]'>
		<td><input type='submit' value='".t("Poista")."'></td></form>";

		echo "</tr>";
	}

	echo "</table><br>";

	echo "<form method='post'>
	<input type='hidden' name='tee' value='uusi'>
	<input type='submit' value='".t("Tee uusi varastoalue")."'>
	</form>";
}

require ("inc/footer.inc");

?>