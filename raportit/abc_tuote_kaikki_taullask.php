<?php

	$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');
	$ryhmaprossat = array(30.00,20.00,15.00,15.00,10.00,5.00,3.00,2.00,0.00);

	echo "<font class='head'>".t("ABC-Analyysiä: ABC-pitkälistaus")."<hr></font>";

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
				WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'";
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
	echo "<th>".t("Syötä tai valitse tuoteryhmä").":</th>";
	echo "<td><input type='text' name='try' size='10'></td>";

	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='TRY'";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='try2'>";
	echo "<option value=''>".t("Tuoteryhmä")."</option>";

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
		
		echo "<pre>";
		echo "ABC\t";
		echo "Tuoteno\t";
		echo "Toim_tuoteno\t";
		echo "Nimitys\t";
		echo "Merkki\t";
		echo "Osasto\t";
		echo "Try\t";
		echo "Myynti$yhtiorow[valkoodi]\t";
		echo "Kate\t";
		echo "Kate%\t";
		echo "Kateosuus\t";
		echo "Vararvo\t";
		echo "Kierto\t";
		echo "MyyntieraKpl\t";
		echo "Myyntiera$yhtiorow[valkoodi]\t";
		echo "Myyntirivit\t";
		echo "Puuterivit\t";
		echo "Palvelutaso\t";
		echo "OstoeraKPL\t";
		echo "Ostoera$yhtiorow[valkoodi]\t";
		echo "Ostorivit\t";
		echo "KustannusMyynti\t";
		echo "KustannusOsto\t";
		echo "KustannusYht\t";
		echo "Kate-Kustannus\t";
		echo "Tuotepaikka\t";
		echo "Saldo\t";
		echo "\n";

		if ($osasto != '') {
			$osastolisa = " and osasto='$osasto' ";
		}
		if ($try != '') {
			$trylisa = " and try='$try' ";
		}

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
						tuoteno,
						osasto,
						try,
						summa,
						kate,
						katepros,
						if ($sumrow[yhtkate] = 0, 0, kate/$sumrow[yhtkate]*100)	kateosuus,
						vararvo,
						varaston_kiertonop,
						myyntierankpl,
						myyntieranarvo,
						rivia,
						kpl,
						puuterivia,
						palvelutaso,
						ostoerankpl,
						ostoeranarvo,
						osto_rivia,
						osto_kpl,
						osto_summa,
						kustannus,
						kustannus_osto,
						kustannus_yht,
						kate - kustannus_yht total
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi='$abcchar'
						$osastolisa
						$trylisa
						and luokka = '$luokkarow[luokka]'
						ORDER BY summa desc";
			$res = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($res)) {

				//tuotenimi
				$query = "	SELECT tuote.nimitys, tuote.tuotemerkki, group_concat(distinct tuotteen_toimittajat.toim_tuoteno) toim_tuoteno
							FROM tuotteen_toimittajat
							JOIN tuote ON tuote.yhtio=tuotteen_toimittajat.yhtio and tuote.tuoteno=tuotteen_toimittajat.tuoteno
							WHERE tuotteen_toimittajat.tuoteno='$row[tuoteno]'
							and tuotteen_toimittajat.yhtio='$kukarow[yhtio]'
							group by tuote.tuoteno";
				$tuoresult = mysql_query($query) or pupe_error($query);
				$tuorow = mysql_fetch_array($tuoresult);

				//haetaan varastopaikat ja saldot
				$query = "	select concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka, saldo
							from tuotepaikat
							where tuoteno='$row[tuoteno]'
							and yhtio='$kukarow[yhtio]'";
				$paikresult = mysql_query($query) or pupe_error($query);

				while ($paikrow = mysql_fetch_array($paikresult)) {

					$l = $row["luokka"];

					echo "$ryhmanimet[$l]\t";
					echo "$row[tuoteno]\t";
					echo "$tuorow[toim_tuoteno]\t";
					echo "$tuorow[nimitys]\t";
					echo "$tuorow[tuotemerkki]\t";
					echo "$row[osasto]\t";
					echo "$row[try]\t";
					echo str_replace(".",",",sprintf('%.1f',$row["summa"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["kate"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["katepros"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["kateosuus"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["vararvo"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["varaston_kiertonop"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["myyntierankpl"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["myyntieranarvo"]))."\t";
					echo str_replace(".",",",sprintf('%.0f',$row["rivia"]))."\t";
					echo str_replace(".",",",sprintf('%.0f',$row["puuterivia"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["palvelutaso"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["ostoerankpl"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["ostoeranarvo"]))."\t";
					echo str_replace(".",",",sprintf('%.0f',$row["osto_rivia"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["kustannus"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["kustannus_osto"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["kustannus_yht"]))."\t";
					echo str_replace(".",",",sprintf('%.1f',$row["total"]))."\t";
					echo "$paikrow[paikka]\t";
					echo str_replace(".",",",sprintf('%.0f',$paikrow["saldo"]))."\t";
					echo "\n";
				}
			}
		}

		echo "</pre>";
	}
?>
