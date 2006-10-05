<?php
	require "inc/parametrit.inc";
	if ($tee != 'PERUSTA') {
		echo "<font class='head'>".t("Kopioi tuote")."</font><hr>";
	}

	
	$orkkistee = $tee;
	
	if ($tee== 'HAKU') {
		require "inc/tuotehaku.inc";
	}
	
	if ($tee== 'PERUSTA') {
		$query = "	SELECT tunnus
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) != 0 ) {
			$orkkistee = 'HAKU';
			$tee = 'Y';
			$varaosavirhe = t("VIRHE: Uudella tuotenumerolla")." $uustuoteno ".t("löytyy jo tuote, ei voida perustaa")."!";
		}
		else {
			$query = "	SELECT *
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
			$stresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($stresult) == 0 ) {
				$orkkistee = '';
				$tee = 'Y';
				$varaosavirhe = t("VIRHE: Vanha tuote")." $tuoteno ".t("on kadonnut, ei uskalleta tehdä mitään")."!";
			}
			else {
				$otsikkorivi = mysql_fetch_array($stresult);
				
				// tehdään vanhasta tuotteesta 1:1 kopio...
				$query = "insert into tuote set ";
				for ($i=0; $i<mysql_num_fields($stresult); $i++) {

					// tuotenumeroksi tietenkin uustuoteno
					if (mysql_field_name($stresult,$i)=='tuoteno') {
						$query .= "tuoteno='$uustuoteno',";
					}
					// laatijaksi klikkaaja
					elseif (mysql_field_name($stresult,$i)=='laatija') {
						$query .= "laatija='$kukarow[kuka]',";
					}
					// luontiaika
					elseif (mysql_field_name($stresult,$i)=='luontiaika') {
						$query .= mysql_field_name($stresult,$i)."=now(),";
					}
					// nämä kentät tyhjennetään
					elseif (mysql_field_name($stresult,$i)=='kehahin' or
							mysql_field_name($stresult,$i)=='vihahin' or
							mysql_field_name($stresult,$i)=='vihapvm' or
							mysql_field_name($stresult,$i)=='epakurantti1pvm' or
							mysql_field_name($stresult,$i)=='epakurantti2pvm' or
							mysql_field_name($stresult,$i)=='muutospvm') {
						$query .= mysql_field_name($stresult,$i)."='',";
					}
					// ja kaikki muut paitsi tunnus sellaisenaan
					elseif (mysql_field_name($stresult,$i)!='tunnus') {
						$query .= mysql_field_name($stresult,$i)."='".$otsikkorivi[$i]."',";
					}
				}
				$query = substr($query,0,-1);

				$stresult = mysql_query($query) or pupe_error($query);
				$id = mysql_insert_id();
				
				$query = "	SELECT *
							FROM tuotteen_toimittajat
							WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
				$stresult = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($stresult) != 0 ) {
					while ($otsikkorivi = mysql_fetch_array($stresult)) {
						// tehdään vanhoista tuotteen_toimittajista 1:1 kopio...
						$query = "insert into tuotteen_toimittajat set ";
						for ($i=0; $i<mysql_num_fields($stresult); $i++) {

							// tuotenumeroksi tietenkin uustuoteno
							if (mysql_field_name($stresult,$i)=='tuoteno') {
								$query .= "tuoteno='$uustuoteno',";
							}
							// ja kaikki muut paitsi tunnus sellaisenaan
							elseif (mysql_field_name($stresult,$i)!='tunnus') {
								$query .= mysql_field_name($stresult,$i)."='".$otsikkorivi[$i]."',";
							}
						}
						$query = substr($query,0,-1);

						$astresult = mysql_query($query) or pupe_error($query);
						$id2 = mysql_insert_id();
					}	
				}
				
				$tee = 'VALMIS';
			}
		}
	}
	
	if ($tee == 'VALMIS') {
		//yllapito.php?toim=tuote&tunnus=359062
		echo "<font class='message'>".t("Kopioitiin onnistuneesti tämä tuote. Nyt voit muutella tietoja.")."</font><br>";
		
		$query = "	SELECT tunnus
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
		$result = mysql_query($query) or pupe_error($query);
		$rivi = mysql_fetch_array($result);
		
		$orkkistee = 'VALMIS';
		
		$toim = 'tuote';
		$tunnus = $rivi['tunnus'];
		$tee = '';
		
		require "yllapito.php";
		
		$tee = 'VALMIS';
	}
	
	if ($ulos != "" and $tee != 'VALMIS') {
			$formi  = 'hakua';
			echo "<form action = '$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='HAKU'>";
			echo "<table><tr>";
			echo "<td>".t("Valitse listasta").":</td>";
			echo "<td>$ulos</td>";
			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
			echo "</tr></table>";
			echo "</form>";
	}
	
	if ($tee=='Y') {
		echo "<font class='error'>$varaosavirhe</font>";
		$tee = $orkkistee;
	}
	
	if ($tee == 'HAKU' and $ulos == '') {
		$formi  = 'performi';
		$kentta = 'uustuoteno';	

		echo "<table><tr>";
		echo "<th>".t("Anna uusi tuotenumero")."<br>".t("joka perustetaan")."</th>";
		
		echo "<tr><form action='$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='PERUSTA'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<td><input type='text' name='uustuoteno' size='22' maxlength='20' value=''></td>";
		echo "<td class='back'><input type='Submit' value='".t("Kopioi")."'></td>";
		echo "</form></tr></table>";
	}
	
	if($tee == '' and $orkkistee != 'VALMIS'){
		$formi  = 'formi';
		$kentta = 'tuoteno';	

		echo "<table><tr>";
		echo "<th>".t("Anna tuotenumero josta")."<br>".t("haluat kopioda tiedot")."</th>";
		
		echo "<tr><form action='$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='HAKU'>";
		echo "<td><input type='text' name='tuoteno' size='22' maxlength='20' value=''></td>";
		echo "<td class='back'><input type='Submit' value='".t("Jatka")."'></td>";
		echo "</form></tr></table>";
	}
	
	if ($tee != 'VALMIS') {
		require "inc/footer.inc";
	}
?>