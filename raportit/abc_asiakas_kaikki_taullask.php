<?php

	echo "<font class='head'>".t("ABC-Analyysiä: ABC-pitkälistaus")."<hr></font>";

	//ryhmäjako
	$ryhmanimet   = array('A-50','B-30','C-20');
	$ryhmaprossat = array(50.00,30.00,20.00);

	// tutkaillaan saadut muuttujat
	$osasto = trim($osasto);
	$try    = trim($try);

	if ($osasto == "") $osasto = trim($osasto2);
	if ($try    == "") $try = trim($try2);
	if ($try    != "") $osasto = "";

	// piirrellään formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='tee' value='PITKALISTA'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<input type='hidden' name='aja' value='AJA'>";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Syötä tai valitse osasto").":</th>";
	echo "<td><input type='text' name='osasto' size='10'></td>";

	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='ASIAKASOSASTO'
				ORDER BY selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='osasto2'>";
	echo "<option value=''>".t("Osasto")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($osasto == $srow[0]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}

	echo "</select></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Syötä tai valitse ryhmä").":</th>";
	echo "<td><input type='text' name='try' size='10'></td>";

	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='ASIAKASRYHMA'
				ORDER BY selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='try2'>";
	echo "<option value=''>".t("Ryhmä")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		if ($try == $srow[0]) $sel = "selected";
		else $sel = "";
		echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
	}

	echo "</select></td><td class='back'><input type='submit' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";

	if ($aja == "AJA") {
		
		if ($osasto != '') {
			$osastolisa = " and osasto='$osasto' ";
		}
		if ($try != '') {
			$trylisa = " and try='$try' ";
		}

		echo "<pre>";
		echo "ABC\t";

		if ($trylisa != '') {
			echo "Ryhmän luokka";
		}

		echo "Ytunnus\t";
		echo "Nimi\t";
		echo "Osasto\t";
		echo "Ryhmä\t";
		echo "Myyjä\t";
		echo "Myynti$yhtiorow[valkoodi]\t";
		echo "Kate\t";
		echo "Kate%\t";
		echo "Kateosuus\t";
		//echo "MyyntieraKpl\t";
		//echo "Myyntiera$yhtiorow[valkoodi]\t";
		//echo "Myyntirivit\t";
		echo "Puuterivit\t";
		echo "Palvelutaso\t";
		//echo "KustannusYht\t";
		echo "\n";


		$query = "	SELECT
					distinct luokka
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi='$abcchar'
					ORDER BY luokka";
		$luokkares = mysql_query($query) or pupe_error($query);

		while($luokkarow = mysql_fetch_array($luokkares)) {


			//kauden yhteismyynnit ja katteet
			$query = "	SELECT
						sum(summa) yhtmyynti,
						sum(kate)  yhtkate
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi='$abcchar'
						$osastolisa
						$trylisa
						and luokka = '$luokkarow[luokka]'";
			$sumres = mysql_query($query) or pupe_error($query);
			$sumrow = mysql_fetch_array($sumres);
			$sumrow['yhtkate'] = (float) $sumrow['yhtkate'];
			$sumrow['yhtmyynti'] = (float) $sumrow['yhtmyynti'];

			//haetaan rivien arvot
			$query = "	SELECT
						luokka,
						luokka_try,
						tuoteno,
						osasto,
						try,
						osto_rivia,
						summa,
						kate,
						katepros,
						kate/$sumrow[yhtkate] * 100	kateosuus,
						myyntierankpl,
						myyntieranarvo,
						rivia,
						kpl,
						puuterivia,
						palvelutaso,
						kustannus_yht
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi='$abcchar'
						$osastolisa
						$trylisa
						and luokka = '$luokkarow[luokka]'
						ORDER BY kate desc";
			$res = mysql_query($query) or pupe_error($query);


			while($row = mysql_fetch_array($res)) {

				//haetaan asiakkaan tiedot
				$query = "	SELECT *
							FROM asiakas
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$row[tuoteno]'";
				$asres = mysql_query($query) or pupe_error($query);
				$asrow = mysql_fetch_array($asres);

				$l = $row["luokka"];

				echo "$ryhmanimet[$l]\t";

				if ($trylisa != '') {
					$a = $row["luokka_try"];

					echo "$ryhmanimet[$a]\t";
				}

				echo "$asrow[ytunnus]\t";
				echo "$asrow[nimi]\t";
				echo "$row[osasto]\t";
				echo "$row[try]\t";
				echo str_replace(".",",",sprintf('%.0f',$row["osto_rivia"]))."\t";
				echo str_replace(".",",",sprintf('%.1f',$row["summa"]))."\t";
				echo str_replace(".",",",sprintf('%.1f',$row["kate"]))."\t";
				echo str_replace(".",",",sprintf('%.1f',$row["katepros"]))."\t";
				echo str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."\t";
				//echo str_replace(".",",",sprintf('%.1f',$row["myyntierankpl"]))."\t";
				//echo str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."\t";
				//echo str_replace(".",",",sprintf('%.0f',$row["rivia"]))."\t";
				//echo str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."\t";
				echo str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."\t";
				//echo str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."\t";
				echo "\n";

			}
		}

		echo "</pre>";
	}
?>