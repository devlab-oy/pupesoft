<?php
	
	$query = "SELECT selite, selitetark FROM avainsana WHERE yhtio = '$kukarow[yhtio]' and laji = 'asavainsana' GROUP BY 1 ORDER BY jarjestys, selitetark";
	$vresult = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($vresult)>0) {
		
		if ($asavtee == 'asinsert') {
			if ($liitostunnus != '' and $uuslaji != '' and $uussana != '' and isset($puttoni)) {
				$query = "	INSERT INTO asiakkaan_avainsanat SET
							yhtio = '$kukarow[yhtio]',
							liitostunnus = '$liitostunnus', 
							laji = '$uuslaji',
							avainsana = '$uussana',
							laatija = '$kukarow[kuka]',
							luontiaika = now()";
				$result = mysql_query($query) or pupe_error($query);
			}
			$asavtee = "";
		}
		elseif ($asavtee == 'aspoista') {
			if ($poistunnus != '') {
				$query = "	DELETE FROM asiakkaan_avainsanat WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$poistunnus'";
				$result = mysql_query($query) or pupe_error($query);
			}
			$asavtee = "";
		}
		
		
		echo "<br><br><br><font class='head'>".t("Asiakkaan avainsanat")."</font><hr>";

		echo "<table>";

		echo "<tr><th>".t("Laji")."</th><th>".t("Arvo")."</th><td class='back'></td></tr>";

		$query = "	SELECT asiakkaan_avainsanat.tunnus, avainsana.selitetark as laji, avainsana 
					FROM asiakkaan_avainsanat, avainsana
					WHERE asiakkaan_avainsanat.yhtio = avainsana.yhtio and asiakkaan_avainsanat.laji = avainsana.selite 
					and asiakkaan_avainsanat.yhtio = '$kukarow[yhtio]' and liitostunnus = '$tunnus' and avainsana.laji = 'asavainsana' GROUP BY avainsana ORDER BY selitetark, jarjestys, avainsana";
		$result = mysql_query($query) or pupe_error($query);
		while ($row = mysql_fetch_array($result)) {
			echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";
			echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
			echo "<input type='hidden' name='asavtee' value='aspoista'>";
			echo "<input type='hidden' name='toim' value='$aputoim'>";
			echo "<input type='hidden' name='tunnus' value='$tunnus'>";
			echo "<input type='hidden' name='poistunnus' value='$row[tunnus]'>";
			echo "<tr><td>$row[laji]</td><td>$row[avainsana]</td><td class='back'><input type='submit' value='".t("Poista avainsana")."'></td></tr>";
			echo "</form>";	
		}

		$lajidd = "<select name='uuslaji' onchange='submit()'>";
		
		$laskuri = 0;
		$valittu = '';
		
		while ($vrow = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($uuslaji == $vrow['selite']) {
				$sel = 'SELECTED';
				$valittu  = $uuslaji;
			}
			
			$lajidd .= "<option value='$vrow[selite]' $sel>$vrow[selitetark]</option>";
			
			if ($laskuri == 0) {
				$defaultti = $vrow['selite'];
			}
			$laskuri ++;
			
		}
		$lajidd .= "</select>";
		
		if ($valittu == '') {
			$valittu = $defaultti;
		}
		
		$query = "SELECT distinct selitetark_2 FROM avainsana WHERE yhtio = '$kukarow[yhtio]' and laji = 'asavainsana' and selite = '$valittu'";
		
		if ($valittu == 'toimitustapa') {
			$query = "SELECT distinct selite AS selitetark_2 FROM toimitustapa WHERE yhtio = '$kukarow[yhtio]' ORDER BY jarjestys, selite";
		}
		
		$sanadd = "<select name='uussana'>";
		
		$result = mysql_query($query) or pupe_error($query);
		while ($row = mysql_fetch_array($result)) {
			$sanadd .= "<option value='$row[selitetark_2]' $sel>$row[selitetark_2]</option>";
		}
		$sanadd .= "</select>";
		
		echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";
		echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
		echo "<input type='hidden' name='asavtee' value='asinsert'>";
		echo "<input type='hidden' name='toim' value='$aputoim'>";
		echo "<input type='hidden' name='tunnus' value='$tunnus'>";
		echo "<input type='hidden' name='liitostunnus' value='$tunnus'>";
		echo "<tr><td>$lajidd</td><td>$sanadd</td><td class='back'><input name='puttoni' type='submit' value='".t("Perusta avainsana")."'></td></tr>";
		echo "</form>";		


		echo "</table>";
	}	
	
?>