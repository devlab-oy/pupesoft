<?php
	include "../inc/parametrit.inc";
    require_once "../inc/tilinumero.inc";

	echo "<font class='head'>".t("Kohdistamattomien suorituksien selaus")."</font><hr>";
	
	if ($tila == 'suoritus_asiakaskohdistus_kaikki') {
		//kohdistetaan tästä kaikki helpot
		require ("suoritus_asiakaskohdistus_kaikki.php");
		
		$tila = "";
	
	}
	
	if ($tila == 'komm') {
		$query = "UPDATE suoritus set viesti = '$komm' WHERE tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$tila = 'tarkenna';
	}
	
	if ($tila == 'tulostakuitti') {

		//Haetaan kirjoitin
		$query  = "	select komento from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus = '$kukarow[kirjoitin]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sinulla ei ole oletuskirjoitinta").".</font><br>";
		}
		else {		
			$kirjoitinrow = mysql_fetch_array($result);
			$tulostakuitti = $kirjoitinrow["komento"];

			$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$asiakas_tunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$asiakasrow = mysql_fetch_array($result);

			require ("../tilauskasittely/tulosta_kuitti.inc");

			// pdffän piirto
			$firstpage = alku();
			rivi($firstpage);
			loppu($firstpage);
    		
			$pdffilenimi = "/tmp/kuitti-".md5(uniqid(mt_rand(), true)).".pdf";
    		
			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
			fclose($fh);
    		
			// itse print komento...Koska ei ole luotettavaa tapaa tehdä kahta kopiota, niin printataan kahdesti
			$line = exec("$tulostakuitti $pdffilenimi");
    			$line = exec("$tulostakuitti $pdffilenimi");

			//poistetaan tmp file samantien kuleksimasta...
			$line = exec("rm -f $pdffilenimi");
    		
			echo "<font class='message'>".t("Kuittikopio (2 kpl) tulostettu").".</font><br>";
		}

		// nollataan muuttujat niin ei mene mikään sekasin
		$tila			= "";
		$summa			= "";
		$selite			= "";
		$asiakas_tunnus	= "";
	}
	
	if ($tila=="kohdista") {
			$myyntisaamiset=0;
			switch ($vastatili) {
				case 'myynti' :
					$myyntisaamiset=$yhtiorow['myyntisaamiset'];
					break;
				case 'factoring' :
					$myyntisaamiset=$yhtiorow['factoringsaamiset'];
					break;
				case 'konserni' :
					$myyntisaamiset=$yhtiorow['konsernimyyntisaamiset'];
					break;
				default :
					echo t("Virheellinen vastatilitieto")."!";
					exit;
			}
			$query = "	SELECT * 
						FROM suoritus
						WHERE tunnus='$tunnus' and yhtio ='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result)==1) {
				
				$suoritus = mysql_fetch_array($result);		
				
				// Suoritus kuntoon
				$query = "	UPDATE suoritus 
							SET asiakas_tunnus='$atunnus' 
							WHERE tunnus='$tunnus' AND yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				
				// Tiliöinti on voinut muuttua				
				$query = "	UPDATE tiliointi 
							set tilino='$myyntisaamiset' 
							where yhtio='$kukarow[yhtio]' AND tunnus='$suoritus[ltunnus]' AND korjattu=''";
				$result = mysql_query($query) or pupe_error($query);
				
				echo "<font class='message'>".t("Suoritus kohdistettu")."</font><br>";
			}
			else {
				echo "<font class='error'>".t("Suoritus kateissa")."</font><br>";
				exit;
			}
			$tila = '';
	}


	if ($tila=='tarkenna') {
		echo"<font class='head'>".t("Suorituksen kohdistaminen asiakkaaseen")."<hr></font>";

		$query = "	SELECT suoritus.yhtio, concat_ws('/',yriti.oletus_rahatili, yriti.nimi) tilino,tilino_maksaja,nimi_maksaja,viite,viesti,suoritus.summa,maksupvm,kirjpvm, concat_ws('/',tili.tilino, tili.nimi) vastatili, asiakas_tunnus, tili.tilino ttilino
					FROM suoritus
					LEFT JOIN tiliointi ON tiliointi.yhtio=suoritus.yhtio AND tiliointi.tunnus=suoritus.ltunnus AND tiliointi.korjattu=''
					LEFT JOIN tili ON tili.yhtio=suoritus.yhtio and tili.tilino=tiliointi.tilino
					LEFT JOIN yriti ON yriti.yhtio=suoritus.yhtio and yriti.tilino=suoritus.tilino
					WHERE suoritus.tunnus=$tunnus AND suoritus.yhtio ='$kukarow[yhtio]'";

		// tulostetaan suoritus(, sekä samalla viitteellä&summalla olevat laskut.)
		$result = mysql_query($query) or pupe_error($query);


		echo "<table>";

		for ($i = 1; $i < mysql_num_fields($result)-2; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
		}
		echo "</tr>";

		if ($suoritus=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i=1; $i<mysql_num_fields($result)-2; $i++) {
				echo "<td>$suoritus[$i]</td>";
			}
			if (!isset($haku[2]))
				$haku[2] = $suoritus['nimi_maksaja'];
			$asiakas_tunnus = $suoritus['asiakas_tunnus'];
			$suoritus_summa= $suoritus['summa'];
			$komm = $suoritus['viesti'];
			echo "</tr>";
		}

		echo "</table><br>";

		// Mahdollisuus muuttaa viestiä
		
		echo "<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name='tunnus' value='$tunnus'>
				<input type = 'hidden' name='tila' value='komm'>
				<table><tr><th>".t("Lisää kommentti")."</th>
				<td><input type = 'text' name = 'komm' size='40' value = '$komm'></td>
				<td><input type = 'submit' value = '".t("Lisää")."'></td></tr></table></form>";
		// Nyt selataan

		$kentat = 'tunnus, ytunnus, nimi, postitp';
		$array = split(",", $kentat);
		$count = count($array);
		for ($i=0; $i<=$count; $i++) {
			if (strlen($haku[$i]) > 0) {
				$siivottu = preg_replace('/\b(oy|ab)\b/i', '', strtolower($haku[$i]));
				$siivottu = preg_replace('/^\s*/', '', $siivottu);
				$siivottu = preg_replace('/\s*$/', '', $siivottu);
				$old   = array("[","{","\\","|","]","}");
				$new   = array("ä","ä", "ö","ö","å","å");
				$siivottu = str_replace($old, $new, $siivottu);
				$lisa .= " and " . $array[$i] . " like '%$siivottu%'";
				$ulisa .= "&haku[" . $i . "]=".urlencode($siivottu);
			}
		}
		//haetaan omat asiakkaat
		$query = "	SELECT $kentat, konserniyhtio
						FROM asiakas
						WHERE yhtio = '$kukarow[yhtio]'  $lisa
						ORDER BY nimi";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr><form action = '$PHP_SELF?tunnus=$tunnus&tila=$tila' method = 'post'>";

		for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
			echo "<th>".t(mysql_field_name($result,$i));

			if($i == 2) {
				echo "<br><input type='text' name = 'haku[$i]' value = '$siivottu' size='30'></th>";
			}
			else {
				echo "<br><input type='text' name = 'haku[$i]' value = '$haku[$i]' size='15'></th>";
			}
		}
		echo "<th valign='bottom'><input type='Submit' value = '".t("Etsi")."'></th></form></tr>";

		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
				if ($i == 2) {
					echo "	<td>$trow[2]</td>";
				}
				else {
					if(strlen($trow[$i]) <= 15){
						echo "<td>$trow[$i]</td>";
					}
					else {
						echo "<td>".substr($trow[$i],0,14)."...</td>";
					}
				}
			}
			echo "<td><form action='$PHP_SELF' method='post'>
					<input type='hidden' name='tila' value='kohdista'>
					<input type='hidden' name='atunnus' value='$trow[0]'>
					<input type='hidden' name='tunnus' value='$tunnus'>";

			$sel1='checked';
			$sel2='';

			if ($yhtiorow['factoringsaamiset'] == $trow['ttilino']) {
				$sel2 = $sel1;
				$sel1 = '';
			}

			if ($trow['konserniyhtio'] != '') {
				$sel1 = '';
				$sel2 = '';
				echo "<input type='radio' name='vastatili' value='konserni' checked>".t("Konsernisaamiset")."<br>";
			}
			
			echo "<input type='radio' name='vastatili' value='myynti' $sel1> ".t("Myyntisaamiset")."<br>
				<input type='radio' name='vastatili' value='factoring' $sel2> ".t("Factoringsaamiset")."<br>";

			echo "<input type='submit' value='".t("kohdista")."'></form></td>";
			echo "</tr>";
		}
		echo "</table>";
	}

	if ($tila == '') {

		echo "<br><font class='message'>".t("Valitse x kohdistaaksesi suorituksia asiakkaisiin tai")." <a href='$PHP_SELF?tila=suoritus_asiakaskohdistus_kaikki'>".t("tästä")."</a> ".t("kaikki helpot").".</font><br><br>";

		$tila = '';
		$kentat = 'nimi_maksaja, kirjpvm, summa, valkoodi, tilino, viite, viesti, asiakas_tunnus, tunnus';
	    $kentankoko = array(15,10,8,5,15,15,20,10,10);
		$array = split(",", $kentat);
		$count = count($array);
		for ($i=0; $i<=$count; $i++) {
				// tarkastetaan onko hakukentässä jotakin
				if (strlen($haku[$i]) > 0) {
					$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
					$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
				}
		}
		if (strlen($ojarj) > 0) {
			$jarjestys = $array[$ojarj];
		}
		else{
			$jarjestys = 'kirjpvm';
		}

		$maxrows = 200;
		$query = "	SELECT ".$kentat."
					FROM suoritus
					WHERE yhtio ='$kukarow[yhtio]' and kohdpvm='0000-00-00'  $lisa
				 	ORDER BY $jarjestys";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF?tila=$tila' method = 'post'>";

	        echo "<table><tr><th>x</th>";

	        for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
	        	echo "<th><a href='$PHP_SELF?tila=$tila&ojarj=".$i.$ulisa."'>" . t(mysql_field_name($result,$i))."</a></th>";
	        }

		echo "<th></th></tr>";
		echo "<tr><td></td>";

		for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
			echo "<td><input type='text' size='$kentankoko[$i]' name = 'haku[$i]' value = '$haku[$i]'></td>";
		}
		echo "<td><input type='submit' value='".t("Etsi")."'></td></tr>";
		echo "</form>";

		$row = 0;
	    while ($maksurow=mysql_fetch_array ($result)) {

			for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
				switch ($i) {
				case 0:
					if ($maksurow["asiakas_tunnus"]!=0) {
						echo "<td></td><td><a href='$PHP_SELF?tunnus=$maksurow[tunnus]&tila=tarkenna'>$maksurow[$i]</a></td>";
					}
					else {
						echo "<td><a href='$PHP_SELF?tunnus=$maksurow[tunnus]&tila=tarkenna'>x</a></td><td>$maksurow[$i]</td>";
					}
					break;
				case 3:
	                echo "<td>";
	                echo tilinumero_print($maksurow[$i]);
	                echo "</td>";
					break;
				case 1:
					//if ($maksurow["asiakas_tunnus"]!=0) {
						//echo "<td><a href='../crm/asiakasmemo.php?ytunnus=$maksurow[asiakas_tunnus]'>$maksurow[$i]</a></td>";
					//}
					//else {
						echo "<td>$maksurow[$i]</td>";
					//}
					break;
			 	default:
		    		echo "<td>$maksurow[$i]</td>";
		    		break;
		    	}
			}
			
			// tehdään nappi kuitin tulostukseen
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tila' value='tulostakuitti'>";
			echo "<input type='hidden' name='asiakas_tunnus' value='$maksurow[asiakas_tunnus]'>";
			echo "<input type='hidden' name='summa' value='$maksurow[summa]'>";
			echo "<input type='hidden' name='selite' value='$maksurow[viesti]'>";
			echo "<td><input type='submit' value='".t("Tulosta kuitti")."'></td></tr>";
			echo "</form>";

			$row++;
		}

		echo "</table>";
		if($row >= $maxrows) {
			echo "".t("Kysely on liian iso esitettäväksi, ainoastaan ensimmäiset")." $row ".t("riviä on näkyvillä. Ole hyvä, ja rajaa hakuehtoja").".";
		}
	}
		//echo "Query: ". $query;
	include "../inc/footer.inc";
?>
