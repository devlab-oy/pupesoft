<?php
	require "inc/parametrit.inc";
	require "inc/alvpopup.inc";
	
	echo "<font class='head'>".t("Uusi konsernitosite")."</font><hr>";

	if ($tee == 'I') { // Tarkistetetaan syötteet perustusta varten
		$summa = str_replace ( ",", ".", $summa);
		$tpk += 0;
		$tpp += 0;
		$tpv += 0;
		if ($tpv < 1000) $tpv += 2000;
		
		$val = checkdate($tpk, $tpp, $tpv);
		if (!$val) {
			echo "<font class='error'>" . t('Virheellinen tapahtumapvm') . "</font><br>";
			$gok = 1; //  Tositetta ei kirjoiteta kantaan vielä
		}

// Talletetaan käyttäjän nimellä tositteen/liitteen kuva, jos sellainen tuli
// koska, jos tulee virheitä tiedosto katoaa. Kun kaikki on ok, annetaan sille oikea nimi
//              echo "$userfile_size koko $userfile nimi --> ";
		if($userfile_size > 0) {
// Tallennuspaikka on etsittävä
			$polku = $yhtiorow['lasku_polku_abs'];
			$faili = $userfile;
// Generoidaan tiedostonimi
			$fnimi = "/" . $kukarow['kuka'] . ".jpg";
			$fnimi = $polku . $fnimi;
//                      echo "$fnimi <br>";
// Talletetaan laskun kuva oikeaan paikkaan
			if(!move_uploaded_file($userfile , $fnimi)) {
				echo "".t("Laskun")." $userfile ".t("tallennus epäonnistui paikkaan")." $fnimi<br>";
			}
		}


		$turvasumma = $summa;
		$totsumma[$kukarow['yhtio']] = 0;
		$totmaara = $omaara+$vmaara; // Yhteensä tarvittavien tiliöintirivien määrä
		for ($i=0; $i<$totmaara; $i++) {
			if ($i == $omaara) { //Vaihdetaan yritystä!
				$turvayhtio=$kukarow['yhtio'];
				$kukarow['yhtio']=$vastaanottaja;
				$totsumma[$kukarow['yhtio']] = 0;
			}
			if ((strlen($itili[$i]) > 0) or (strlen($isumma[$i]) > 0)) { // Käsitelläänkö rivi??
				$isumma[$i] = str_replace ( ",", ".", $isumma[$i]);
				if ((strlen($selite) > 0) && (strlen($iselite[$i]) == 0)) { // Siirretään oletusselite tiliöinneille
					$iselite[$i] = $selite;
				}
				if (strlen($iselite[$i]) == 0) { // Selite puuttuu
					$ivirhe[$i] = t('Riviltä puuttuu selite').'<br>';
					$gok = 1;
				}

				if ($turvasumma > 0) {  // Oletussummalla korvaaminen mahdollista
					if ($isumma[$i] == '-') { // Summan vastaluku käyttöön
						$isumma[$i] = -1 * $turvasumma;
					}
					else {
						if ($isumma[$i] == 0)  { // Kopioidaan summa
							$isumma[$i] = $turvasumma;
						}
					}
				}
				if ($isumma[$i] == 0) { // Summa puuttuu
					$ivirhe[$i] .= t('Riviltä puuttuu summa').'<br>';
					$gok = 1;
				}
				$totsumma[$kukarow['yhtio']] += $isumma[$i];
				
				if ((strlen($itili[$i]) != 5) and (strlen($itili[$i]) != 0)) { // Onko tämä konsernitili
					$ivirhe[$i] .= t('Tili ei ole konsernitili (Ne ovat 5 merkkiä pitkiä)') . '<br>';
					$gok = 1;
				}
				
			
				$ulos='';
				$virhe = '';
				$tili = $itili[$i];
				$summa = $isumma[$i];
				$selausnimi = 'itili[' . $i .']'; // Minka niminen mahdollinen popup on?
				require "inc/tarkistatiliointi.inc";
				$ivirhe[$i] .= $virhe;
				$iulos[$i] = $ulos;
				if ($ok==0) { // Sieltä kenties tuli päivitys tilinumeroon
					if ($itili[$i] != $tili) { // Annetaan käyttäjän päättää onko ok
						$itili[$i] = $tili;
						$gok = 1; // Tositetta ei kirjoiteta kantaan vielä
					}
				}
				else {
					$gok = 1;
				}
				$gok = $ok; // Nostetaan virhe ylemmälle tasolle
			}
		}
		
		if ($totsumma[$kukarow['yhtio']] != -1 * $totsumma[$turvayhtio]) {
			echo "<font class='error'>".t("Yrityksille menevät tiliöinnit eivät täsmää")."</font><br>";
			$tee = '';
		}
		if ($gok == 1) { // Jossain tapahtui virhe
			echo "<font class='error'>".t("Jossain oli virheitä/muutoksia!")."</font><br>";
			$tee = '';
		}
		$summa = $turvasumma;
		$kukarow['yhtio']=$turvayhtio; //Palautetaan oletusyhtio
	}
// Kirjoitetaan tosite jos tiedot ok!

	if ($tee == 'I') {
// Talletetaan tositteen/liitteen kuva, jos sellainen tuli
//              echo "$userfile_size koko $userfile nimi --> ";
		if(strlen($fnimi) > 0) {
// Tallennuspaikka on etsittävä
			$polku = $yhtiorow['lasku_polku_abs'];
			$faili = $fnimi;
			$fnimi = "";
// Generoidaan tiedostonimi
			srand ((double) microtime() * 1000000);
			for ($i=0; $i<25; $i++) {
				$fnimi = $fnimi . chr(rand(65,90)) ;
			}
			$fnimi = "/" . $fnimi . ".jpg";
			$ebid = $fnimi;
			$fnimi = $polku . $fnimi;
//                      echo "$fnimi <br>";
// Talletetaan laskun kuva oikeaan paikkaan
			if(!rename($faili , $fnimi)) {
				echo "".t("Laskun")." $faili ".t("tallennus epäonnistui paikkaan")." $fnimi<br>";
			}
		}

		$query = "	INSERT into lasku set
						yhtio = '$kukarow[yhtio]',
						tapvm = '$tpv-$tpk-$tpp',
						ebid = '$ebid',
						tila = 'X',
						laatija = '$kukarow[kuka]',
						luontiaika = now()";
		$result = mysql_query($query) or pupe_error($query);
		$tunnus = mysql_insert_id ($link);
		
		$totsumma = 0;
		$totmaara = $omaara+$vmaara; // Yhteensä tarvittavien tiliöintirivien määrä
		for ($i=0; $i<$totmaara; $i++) {
	//Vaihtuuko yritys?
			if ($i == $omaara) {
			//Vastavienti
				if ($totsumma < 0) $tili = $yhtiorow['konsernisaamiset']; else $tili = $yhtiorow['konsernivelat'];
				$selite = t('Konsernitositteen vastavienti');
				$summa = $totsumma * -1;
				$vero = 0;
				require "inc/teetiliointi.inc";
	// Aloitetaan vieraan yrityksen tiliöinnit
				$totsumma = 0;
				$turvayhtio=$kukarow['yhtio'];
				$kukarow['yhtio']=$vastaanottaja;
				$query = "SELECT *
							  FROM yhtio
							  WHERE  yhtio = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) != 1) {
					echo "".t("Yritystä")." $kukarow[yhtio] ".t("ei löytynytkään")."!";
					
					require "inc/footer.inc";
					exit;
				}
				$yhtiorow=mysql_fetch_array ($result);
				$query = "	INSERT into lasku set
								yhtio = '$kukarow[yhtio]',
								tapvm = '$tpv-$tpk-$tpp',
								ebid = '$ebid',
								tila = 'X',
								laatija = '$kukarow[kuka]',
								luontiaika = now()";
				$result = mysql_query($query) or pupe_error($query);
				$tunnus = mysql_insert_id ($link);
			}
			if (strlen($itili[$i]) > 0) {
		// Tehdään tiliöinnit
				$tili = $itili[$i];
				$kustp = $ikustp[$i];
				$kohde = $ikohde[$i];
				$projekti = $iprojekti[$i];
				$summa = $isumma[$i];
				$totsumma += $summa; // Vastavietiä varten
				$vero = $ivero[$i];
				$selite = $iselite[$i];
				require "inc/teetiliointi.inc";
			}
			$itili[$i]='';
			$ikustp[$i]='';
			$ikohde[$i]='';
			$iprojekti[$i]='';
			$isumma[$i]='';
			$ivero[$i]='';
			$iselite[$i]='';
		}
	//Vastavienti
		if ($totsumma < 0) $tili = $yhtiorow['konsernisaamiset']; else $tili = $yhtiorow['konsernivelat'];
		$summa = $totsumma * -1;
		$vero = 0;
		$selite = t('Konsernitositteen vastavienti');
		require "inc/teetiliointi.inc";

		$kukarow['yhtio']=$turvayhtio;
		$query = "SELECT *
					  FROM yhtio
					  WHERE  yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) != 1) {
			echo "Yritystä $kukarow[yhtio] ei löytynytkään!";
			
			require "inc/footer.inc";
			exit;
		}
		$yhtiorow=mysql_fetch_array ($result);
		$tee='';
		$selite='';
		$summa=0;
	}

	if ($tee == '') { // Uusi tosite
		if ($vastaanottaja == '') {
			$query = "	SELECT yhtio, nimi
							FROM yhtio
							WHERE konserni='$yhtiorow[konserni]' and trim(konserni) != ''
									and yhtio!='$kukarow[yhtio]'";
			$yresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($yresult) < 2) {
				echo "<font class='error'>".t("Konsernissasi ei ole yhtään yritystä. Näin ollen et voi käyttää tätä toimintoa")."</font>";
				
				require "inc/footer.inc";
				exit;
			}
			echo "<form name = 'tosite' action = '$PHP_SELF' method='post'>
					<font class='message'>" . t("Valitse vastaanottava yritys") . "</font>";
			echo "<select name='vastaanottaja'>";
			while ($yrow = mysql_fetch_array($yresult)) {
				$sel = '';
				if ($yrow['yhtio'] == $vastaanottaja) {
					$sel = "selected";
				}
				echo "<option value='$yrow[0]' $sel>$yrow[1] ($yrow[0])</option>";
			}
			echo "</select><input type='Submit' value = '" . t("Valitse") ."'></form>";
		}
		else {
			if (($omaara == 0) or ($vmaara == 0)) {
				$omaara = 1;
				$vmaara = 1;
			}
			echo "<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='vastaanottaja' value='$vastaanottaja'>
					<input type='hidden' name='tpp' maxlength='2' size=2 value='$tpp'>
					<input type='hidden' name='tpk' maxlength='2' size=2 value='$tpk'>
					<input type='hidden' name='tpv' maxlength='4' size=4 value='$tpv'>
					<table>
					<tr>
					<td>".t("Oman yrityksen tiliöintirivien määrä")."</td>
					<td>
					<select name='omaara'>
					<option value ='1'>1
					<option value ='2'>2
					<option value ='4'>4
					<option value ='8'>8
					</select>
					</td>
					<td>".t("Konserniyrityksen tiliöintirivien määrä")."</td>
					<td>
					<select name='vmaara'>
					<option value ='1'>1
					<option value ='2'>2
					<option value ='4'>4
					<option value ='8'>8
					</select>
					</td><td><input type = 'submit' value = '".t("Perusta") ."'></td>
					</tr></table></form>";
						
			$formi = 'tosite';
			$kentta = 'tpp';
			echo "<form name = 'tosite' action = '$PHP_SELF' method='post' enctype='multipart/form-data'>
					<input type='hidden' name='tee' value='I'>
					<input type='hidden' name='omaara' value='$omaara'>
					<input type='hidden' name='vmaara' value='$vmaara'>
					<input type='hidden' name='vastaanottaja' value='$vastaanottaja'>
			      <table><tr>
			      <td>".t("Tositteen päiväys")."</td>
			      <td><input type='text' name='tpp' maxlength='2' size=2 value='$tpp'>
						<input type='text' name='tpk' maxlength='2' size=2 value='$tpk'>
						<input type='text' name='tpv' maxlength='4' size=4 value='$tpv'> ".t("ppkkvvvv")."</td>
			      </tr>";
			echo "<td>".t("Summa")."</td>";

			echo "<td><input type='text' name='summa' value='$summa'></td></tr>";
			echo "<td colspan = '2'>".t("Selite"). " <input type='text' name='selite' value='$selite' maxlength='150' size=60></td></tr>
			      <tr><td>".t("Mahdollinen tositteen kuva/liite")."</td>";
			if (strlen($fnimi) > 0) {
				echo "<td>".t("Liite jo tallessa")."!<input name='fnimi' type='hidden' value = '$fnimi'></td></tr></table>";
			}
			else {
				echo "<td><input name='userfile' type='file' value = '$userfile_name'></td></tr></table>";
			}

			echo "<br><br><table>
			      <tr><th>".t("Tili")."</th><th>".t("Tarkenne")."</th>
			      <th>".t("Summa")."</th><th>".t("Vero")."</th></tr>";			
			$totmaara = $omaara+$vmaara; // Yhteensä tarvittavien tiliöintirivien määrä
			for ($i=0; $i<$totmaara; $i++) {
	// Valitaan vastaanottava konsernin jäsen
				if ($i == $omaara) { 
					$turvayhtio=$kukarow['yhtio'];
					$kukarow['yhtio']=$vastaanottaja;
					$query = "SELECT *
								  FROM yhtio
								  WHERE  yhtio = '$kukarow[yhtio]'";
					$result = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($result) != 1) {
						echo "".t("Yritystä")." $kukarow[yhtio] ".t("ei löytynytkään")."!";
						
						require "inc/footer.inc";
						exit;
					}
					$yhtiorow=mysql_fetch_array ($result);
					echo "<tr><th colspan = '5'>".t("Vastaanottava yritys")." $yhtiorow[nimi] ($yhtiorow[yhtio])</th></tr>";
				}
				echo "<tr>";
				if ($iulos[$i] == '') {
					echo "<td><input type='text' name='itili[$i]' value='$itili[$i]''></td>";
				}
				else {
					echo "<td>$iulos[$i]</td>";
				}
	// Tehdään kustannuspaikkapopup
				$query = "SELECT tunnus, nimi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K' and kaytossa <> 'E'
							ORDER BY nimi";
				$vresult = mysql_query($query) or pupe_error($query);
				echo "<td><select name='ikustp[$i]'>";
				echo "<option value =' '>".t("Ei kustannuspaikkaa");
				while ($vrow=mysql_fetch_array($vresult)) {
					$sel="";
					if ($ikustp[$i] == $vrow[0]) {
						$sel = "selected";
					}
					echo "<option value ='$vrow[0]' $sel>$vrow[1]";
				}
				echo "</select><br>";

	// Tehdään kohdepopup
				$query = "SELECT tunnus, nimi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O' and kaytossa <> 'E'
							ORDER BY nimi";
				$vresult = mysql_query($query) or pupe_error($query);
				echo "<select name='ikohde[$i]'>";
				echo "<option value =' '>".t("Ei kohdetta");
				while ($vrow=mysql_fetch_array($vresult)) {
					$sel="";
					if ($ikohde[$i] == $vrow[0]) {
						$sel = "selected";
					}
					echo "<option value ='$vrow[0]' $sel>$vrow[1]";
				}
				echo "</select><br>";

	// Tehdään projektipopup
				$query = "SELECT tunnus, nimi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P' and kaytossa <> 'E'
							ORDER BY nimi";
				$vresult = mysql_query($query) or pupe_error($query);
				echo "<select name='iprojekti[$i]'>";
				echo "<option value =' '>".t("Ei projektia");
				while ($vrow=mysql_fetch_array($vresult)) {
					$sel="";
					if ($iprojekti[$i] == $vrow[0]) {
						$sel = "selected";
					}
					echo "<option value ='$vrow[0]' $sel>$vrow[1]";
				}
				echo "</select></td>";

				echo "<td><input type='text' name='isumma[$i]' value='$isumma[$i]'></td>";

				echo "<td>" . alv_popup('ivero['.$i.']', $ivero[$i]) . "</td>";

				echo  "<td>$ivirhe[$i]</td>";

				echo "</tr>";

				echo "<tr><td colspan = '5'>
				      ".t("Selite").":<input type='text' name='iselite[$i]' value='$iselite[$i]' maxlength='150' size=60></td></tr>";
			}
			echo "</table><input type='Submit' value = '".t("Tee tosite")."'></form>";
		}
	}

	require "inc/footer.inc";
?>
