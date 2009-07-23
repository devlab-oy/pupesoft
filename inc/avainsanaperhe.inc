<?php

	echo "<font class='head'>".t("Avainsanan kieliversiot")."</font><hr>";

	if ($oikeurow['paivitys'] != '1') { // Saako päivittää
		echo "<br><font class='error'>".t("Sun natsat ei riitä tähä hommaan poika")."!</font><br>";
		exit;
	}

	if ((int) $avaintunnus == 0 and $uusi == 1) {
		
		echo "	<form action='#' method='POST'>
				<input type='button' onclick='javascript:document.mainform.submit();' name='kikkeli' value='".t("Lisää uusi käännös")."'>
				</form>";
	}
	elseif((int) $perhe == 0) {
		echo "<font class='error'>".t("Avainsana katosi")."!</font>";	
	}
	else {

		if ($kikkeli == 1) {			
			$tee = "uusi";
		}

		// tehdään tarkastuksia ja muuta kivaa
		if ($tee != "uusi" and $tee != "poista" and $tee != "") {
			if (trim($al_selite) == "") {
				$avainerrorit["selite"] = "<font class='error'>".t("Selite on pakollinen")."</font>";
			}
			
			if (count($avainerrorit) > 0) {
				if ($tee == "insert") $tee = "uusi";
				else $tee = "";
			}
		}

		//	synkkausta varten!
		if($avaintunnus > 0) {
			$query = "SELECT * from avainsana where yhtio='$kukarow[yhtio]' and tunnus='$avaintunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$orgirow = mysql_fetch_array($result) or pupe_error($query);			
		}		

		// päivitetään toimittaja
		if ($tee == "update") {			
			$query = "	UPDATE avainsana set
						muuttaja		='$kukarow[kuka]', 
						muutospvm		= now(), 
						perhe			= '$perhe', 
						kieli			= '$al_kieli', 
						laji			= '$al_laji', 
						selite			= '$al_selite', 
						selitetark		= '$al_selitetark', 
						selitetark_2	= '$al_selitetark_2', 
						selitetark_3	= '$al_selitetark_3', 
						jarjestys		= '$al_jarjestys'
						where yhtio = '$kukarow[yhtio]' and tunnus = '$avaintunnus'";
			$result = mysql_query($query) or pupe_error($query);
			
			synkronoi("avainsana", $avaintunnus, $orgirow);
			$tee = "";
		}

		// poistetaan toimittaja
		if ($tee == "poista") {
			$query = "DELETE from avainsana where yhtio='$kukarow[yhtio]' and tunnus='$avaintunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$tee = "";
			
			synkronoi("avainsana", $avaintunnus, $orgirow);			
		}

		// lisätään toimittaja
		if ($tee == "insert") {
			$query = "	INSERT into avainsana set
						yhtio 			= '$kukarow[yhtio]',
						laatija			= '$kukarow[kuka]', 
						luontiaika		= now(),
						muuttaja		= '$kukarow[kuka]', 
						muutospvm		= now(), 
						perhe			= '$perhe', 
						kieli			= '$al_kieli', 
						laji			= '$al_laji', 
						selite			= '$al_selite', 
						selitetark		= '$al_selitetark', 
						selitetark_2	= '$al_selitetark_2', 
						selitetark_3	= '$al_selitetark_3', 
						jarjestys		= '$al_jarjestys'";						
			$result = mysql_query($query) or pupe_error($query);
			
			synkronoi("avainsana", mysql_insert_id(), $origrow);			
			$tee = "";
		}

		// uuden syöttö
		if ($tee == "uusi") {

			echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";

			echo "<table>";
			echo "<tr><th>".t("Selite")."</th><td><input type='text' name='al_selite' 				value='$al_selite'></td><td class='back'>".$avainerrorit["selite"]."</td></tr>";
			echo "<tr><th>".t("Selitetark")."</th><td><input type='text' name='al_selitetark' 		value='$al_selitetark'></td><td class='back'>".$avainerrorit["selitetark"]."</td></tr>";
			echo "<tr><th>".t("Selitetark_2")."</th><td><input type='text' name='al_selitetark_2' 	value='$al_selitetark_2'></td><td class='back'>".$avainerrorit["selitetark_2"]."</td></tr>";
			echo "<tr><th>".t("Selitetark_3")."</th><td><input type='text' name='al_selitetark_3' 	value='$al_selitetark_3'></td><td class='back'>".$avainerrorit["selitetark_3"]."</td></tr>";
			
			echo "<tr><th>".t("Kieli")."</th><td><select name='al_kieli'>";
			
			$query  = "SHOW columns FROM sanakirja";
			$fields =  mysql_query($query);

			while ($apurow = mysql_fetch_array($fields)) {
				if (strlen($apurow[0]) == 2 and (strpos($kielet,$apurow[0]) === false or $apurow[0] == $trow[$i])) {
					$sel = "";
					if ($trow[$i] == $apurow[0]) {
						$sel = "SELECTED";
					}
					elseif ($trow[$i] == "" and $apurow[0] == $yhtiorow["kieli"]) {
						$sel = "SELECTED";
					}
					echo "<option value='$apurow[0]' $sel>$apurow[0] - ".maa($apurow[0])."</option>";
				}
			}

			echo "</select></td></tr>";				
			echo "<tr><th>".t("Järjestys")."</th><td><input type='text' name='al_jarjestys' 	value='$al_jarjestys'></td><td class='back'>".$avainerrorit["jarjestys"]."</td></tr>";								
			echo "</table><hr>";
			
			echo "<input type='hidden' name='al_laji' value = '$al_laji'>";
			echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
			echo "<input type='hidden' name='tee' value='insert'>";
			echo "<input type='hidden' name='toim' value='$aputoim'>";
			echo "<input type='hidden' name='tunnus' value='$tunnus'>";
			echo "<input type='hidden' name='avaintunnus' value='$avaintunnus'>";
			echo "<input type='hidden' name='laji' value='$laji'>";
			echo "<input type='submit' value='".t("Perusta avainsana")."'></form>";
		}

		// selataan vanhoja
		if ($tee == "") {
			$query  = "	SELECT * 
						from avainsana 
						where yhtio	= '$kukarow[yhtio]'
						and laji 	= '$al_laji'
						and perhe	= '$perhe' 
						and tunnus	!='$tunnus' 
						and perhe 	> 0
						order by tunnus";
			$result = mysql_query($query) or pupe_error($query);

			while ($trow = mysql_fetch_array($result)) {
				
				echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";
				echo "<table>";
				echo "<tr><th>".t("Selite")."</th><td><input type='text' name='al_selite' 				value='$trow[selite]'></td><td class='back'>".$avainerrorit["selite"]."</td></tr>";
				echo "<tr><th>".t("Selitetark")."</th><td><input type='text' name='al_selitetark' 		value='$trow[selitetark]'></td><td class='back'>".$avainerrorit["selitetark"]."</td></tr>";
				echo "<tr><th>".t("Selitetark_2")."</th><td><input type='text' name='al_selitetark_2' 	value='$trow[selitetark_2]'></td><td class='back'>".$avainerrorit["selitetark_2"]."</td></tr>";
				echo "<tr><th>".t("Selitetark_3")."</th><td><input type='text' name='al_selitetark_3' 	value='$trow[selitetark_3]'></td><td class='back'>".$avainerrorit["selitetark_3"]."</td></tr>";

				echo "<tr><th>".t("Kieli")."</th><td><select name='al_kieli'>";

				$query  = "SHOW columns FROM sanakirja";
				$fields =  mysql_query($query);

				while ($apurow = mysql_fetch_array($fields)) {
					if (strlen($apurow[0]) == 2 and (strpos($kielet, $apurow[0]) === false or $apurow[0] == $trow["kieli"])) {
						$sel = "";
						if ($trow["kieli"] == $apurow[0]) {
							$sel = "SELECTED";
						}
						
						echo "<option value='$apurow[0]' $sel>$apurow[0] - ".maa($apurow[0])."</option>";
					}
				}

				echo "</select></td></tr>";									
				echo "<tr><th>".t("Järjestys")."</th><td><input type='text' name='al_jarjestys' 	value='$trow[jarjestys]'></td><td class='back'>".$avainerrorit["jarjestys"]."</td></tr>";				
				echo "<tr><td class='back'>";
				
				echo "<input type='hidden' name='al_laji' value = '$al_laji'>";
				echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
				echo "<input type='hidden' name='tee' value='update'>";
				echo "<input type='hidden' name='toim' value='$aputoim'>";
				echo "<input type='hidden' name='tunnus' value='$tunnus'>";
				echo "<input type='hidden' name='avaintunnus'  value='$trow[tunnus]'>";
				echo "<input type='hidden' name='toimittajatunnus'  value='$trow[liitostunnus]'>";
				echo "<input type='submit' value='".t("Päivitä avainsana")."'></td></form>";
				
				if ($rajattu_nakyma == '') {
					echo "<form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>";
					echo "<td class='back'>";
					echo "<input type='hidden' name='tee' value='poista'>";
					echo "<input type='hidden' name='lopetus' value = '$lopetus'>";
					echo "<input type='hidden' name='toim' value='$aputoim'>";
					echo "<input type='hidden' name='tunnus' value='$tunnus'>";
					echo "<input type='hidden' name='avaintunnus'  value='$trow[tunnus]'>";
					echo "<input type='submit' value='".t("Poista avainsana")."'></td></form>";
				}

				echo "</tr>";
				echo "</table>";
			}
			
			if ($rajattu_nakyma == '') {
				echo "<br><form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>
				<input type='hidden' name='al_laji' value = '$al_laji'>
				<input type='hidden' name='tee' value='uusi'>
				<input type='hidden' name='lopetus' value = '$lopetus'>
				<input type='hidden' name='toim' value='$aputoim'>
				<input type='hidden' name='tunnus' value='$tunnus'>
				<input type='hidden' name='laji' value='$laji'>
				<input type='submit' value='".t("Lisää uusi käännös")."'></form>";
			}
			echo "<hr><br><br>";
		}
	}

?>
