<?php
	require "inc/parametrit.inc";
	require "inc/alvpopup.inc";

	echo "<font class='head'>".t("Uusi muu tosite")."</font><hr>";

	$kurssi = 1;

	// Tarkistetetaan syötteet perustusta varten
	if ($tee == 'I') {
		$totsumma = 0;
		$summa = str_replace ( ",", ".", $summa);
		$gok  = 0;
		$tpk += 0;
		$tpp += 0;
		$tpv += 0;
		if ($tpv < 1000) $tpv += 2000;

		$val = checkdate($tpk, $tpp, $tpv);
		if (!$val) {
			echo "<b>".t("Virheellinen tapahtumapvm")."</b><br>";
			$gok = 1; //  Tositetta ei kirjoiteta kantaan vielä
		}

		if ($valkoodi != $yhtiorow["valkoodi"] and $gok == 0) {
			// koitetaan hakea maksupäivän kurssi
			$query = "	SELECT *
						FROM valuu_historia
						WHERE kotivaluutta = '$yhtiorow[valkoodi]'
						AND valuutta = '$valkoodi'
						AND kurssipvm <= '$tpv-$tpk-$tpp'
						LIMIT 1";
			$valuures = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($valuures) == 1) {
				$valuurow = mysql_fetch_array($valuures);
				$kurssi = $valuurow["kurssi"];
			}
			else {
				echo "<font class='error'>".t("Ei löydetty sopivaa kurssia!")."</font><br>";
				$gok = 1; //  Tositetta ei kirjoiteta kantaan vielä
			}
		}

		// Talletetaan käyttäjän nimellä tositteen/liitteen kuva, jos sellainen tuli
		// koska, jos tulee virheitä tiedosto katoaa. Kun kaikki on ok, annetaan sille oikea nimi
		if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
			$retval = tarkasta_liite("userfile", array("PNG", "JPG", "GIF", "PDF"));
			if($retval === true) {
				$kuva = tallenna_liite("userfile", "lasku", 0, "");
			}
			else {
				echo $retval;
				$tee = "N";
			}
		}

		if (is_uploaded_file($_FILES['tositefile']['tmp_name'])) {
			//	ei koskaan päivitetä automaattisesti
			$tee = "N";

			$retval = tarkasta_liite("tositefile", array("TXT", "CSV", "XLS"));
			if($retval === true) {
				list($name, $ext) = split("\.", $_FILES['tositefile']['name']);

				if (strtoupper($ext)=="XLS") {
					require_once ('excel_reader/reader.php');

					// ExcelFile
					$data = new Spreadsheet_Excel_Reader();

					// Set output Encoding.
					$data->setOutputEncoding('CP1251');
					$data->setRowColOffset(0);
					$data->read($_FILES['tositefile']['tmp_name']);
				}

				echo "<font class='message'>".t("Tutkaillaan mitä olet lähettänyt").".<br></font>";

				// luetaan eka rivi tiedostosta..
				if (strtoupper($ext) == "XLS") {
					$otsikot = array();

					for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
						$otsikot[] = strtoupper(trim($data->sheets[0]['cells'][0][$excej]));
					}
				}
				else {
					$file	 = fopen($_FILES['tositefile']['tmp_name'],"r") or die (t("Tiedoston avaus epäonnistui")."!");

					$rivi    = fgets($file);
					$otsikot = explode("\t", strtoupper(trim($rivi)));
				}

				// luetaan tiedosto loppuun ja tehdään array koko datasta
				$excelrivi[][] = array();

				if (strtoupper($ext) == "XLS") {
					for ($excei = 0; $excei < $data->sheets[0]['numRows']; $excei++) {
						for ($excej = 0; $excej <= $data->sheets[0]['numCols']; $excej++) {
							$excelrivi[$excei][$excej] = $data->sheets[0]['cells'][$excei][$excej];
						}
					}
				}
				else {
					$rivi = fgets($file);

					$excei = 1;

					while (!feof($file)) {
						// luetaan rivi tiedostosta..
						$poista	 = array("'", "\\");
						$rivi	 = str_replace($poista,"",$rivi);
						$rivi	 = explode("\t", trim($rivi));

						$excej = 0;
						foreach ($rivi as $riv) {
							$excelrivi[$excei][$excej] = $riv;
							$excej++;
						}
						$excei++;

						// luetaan seuraava rivi failista
						$rivi = fgets($file);
					}
					fclose($file);
				}

				$maara = 0;
				foreach ($excelrivi as $erivi) {
					foreach ($erivi as $e => $eriv) {
						${"i".strtolower($otsikot[$e])}[$maara] = $eriv;
					}
					$maara++;
				}

				//	Lisätään vielä 2 tyhjää riviä loppuun
				$maara+=2;

			}
			else {

				//	Liitetiedosto ei kelpaa
				echo $retval;
				$tee = "N";
			}
		}
		elseif (isset($_FILES['tositefile']['error']) and $_FILES['tositefile']['error'] != 4) {
			// nelonen tarkoittaa, ettei mitään fileä uploadattu.. eli jos on joku muu errori niin ei päästetä eteenpäin
			echo "<font class='error'>".t("Tositetiedoston sisäänluku epäonnistui")."! (Error: ".$_FILES['userfile']['error'].")</font><br>";
			$tee = "N";
		}

		// turvasumma kotivaluutassa
		$turvasumma = round($summa * $kurssi, 2);
		// turvasumma valuutassa
		$turvasumma_valuutassa = $summa;

		$kuittiok = 0; // Onko joku vienneistä kassa-tili, jotta kuitti voidaan tulostaa
		$isumma_valuutassa = array();

		for ($i=1; $i<$maara; $i++) {
			if (strlen($itili[$i]) > 0 or strlen($isumma[$i]) > 0) { // Käsitelläänkö rivi??

				$isumma[$i] = str_replace ( ",", ".", $isumma[$i]);

				// otetaan valuuttasumma talteen
				$isumma_valuutassa[$i] = $isumma[$i];
				// käännetään kotivaluuttaan
				$isumma[$i] = round($isumma[$i] * $kurssi, 2);

				if (strlen($selite) > 0 and strlen($iselite[$i]) == 0) { // Siirretään oletusselite tiliöinneille
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
						if ((strlen($itili[$i]) > 0) && ($isumma[$i] == 0))  { // Kopioidaan summa
							$isumma[$i] = $turvasumma;
						}
					}
				}
				if ($isumma[$i] == 0) { // Summa puuttuu
					$ivirhe[$i] .= t('Riviltä puuttuu summa').'<br>';
					$gok = 1;
				}

				$ulos='';
				$virhe = '';
				$tili = $itili[$i];
				$summa = $isumma[$i];
				$totsumma += $summa;
				$selausnimi = 'itili[' . $i .']'; // Minka niminen mahdollinen popup on?
				$vero='';
				require "inc/tarkistatiliointi.inc";
				if ($vero!='') $ivero[$i]=$vero; //Jos meillä on hardkoodattuvero, otetaan se käyttöön
				$ivirhe[$i] .= $virhe;
				$iulos[$i] = $ulos;
				if ($ok==0) { // Sieltä kenties tuli päivitys tilinumeroon
					if ($itili[$i] != $tili) { // Annetaan käyttäjän päättää onko ok
						$itili[$i] = $tili;
						$gok = 1; // Tositetta ei kirjoiteta kantaan vielä
					}
					else {
						if ($itili[$i] == $yhtiorow['kassa']) $kassaok=1;
					}
				}
				else {
					$gok = $ok; // Nostetaan virhe ylemmälle tasolle
				}
			}

		}

		if (count($isumma_valuutassa) == 0) {
			$gok=1;
			echo "<font class='error'>".t("Et syöttänyt yhtään tiliöintiriviä")."!</font><br>";
		}

		if ($kuitti != '') {
			if ($kassaok==0) {
				$gok=1;
				echo "<font class='error'>".t("Pyysit kuittia, mutta kassatilille ei ole vientejä")."</font><br>";
			}
			if ($nimi == '') {
				$gok=1;
				echo "<font class='error'>".t("Kuitille on annettava nimi")."</font><br>";
			}
		}

		if (abs($totsumma) >= 0.01 and $heittook  == '') {
				$gok=1;
				echo "<font class='error'>".t("Tositteen summat eivät mene tasan")."</font><br>";
		}

 		// Jossain tapahtui virhe
		if ($gok == 1) {
			echo "<font class='error'>".t("Jossain oli virheitä/muutoksia")."!</font><br><br>";
			$tee = 'N';
		}

		$summa = $turvasumma;
	}

	// Kirjoitetaan tosite jos tiedot ok!
	if ($tee == 'I') {

		$query = "	INSERT into lasku set
					yhtio = '$kukarow[yhtio]',
					tapvm = '$tpv-$tpk-$tpp',
					nimi = '$nimi',
					tila = 'X',
					laatija = '$kukarow[kuka]',
					luontiaika = now()";
		$result = mysql_query($query) or pupe_error($query);

//		echo "$query <br>";
		$tunnus = mysql_insert_id ($link);

		if ($kuva) {
			// päivitetään kuvalle vielä linkki toiseensuuntaa
			$query = "UPDATE liitetiedostot set liitostunnus='$tunnus', selite='$selite $summa' where tunnus='$kuva'";
			$result = mysql_query($query) or pupe_error($query);
		}

		// Tehdään tiliöinnit
		for ($i=1; $i<$maara; $i++) {
			if (strlen($itili[$i]) > 0) {
				$tili				= $itili[$i];
				$kustp				= $ikustp[$i];
				$kohde				= $ikohde[$i];
				$projekti			= $iprojekti[$i];
				$summa				= $isumma[$i];
				$vero				= $ivero[$i];
				$selite 			= $iselite[$i];
				$summa_valuutassa	= $isumma_valuutassa[$i];
				$valkoodi 			= $valkoodi;

				require("inc/teetiliointi.inc");

				$itili[$i]				= '';
				$ikustp[$i]				= '';
				$ikohde[$i]				= '';
				$iprojekti[$i]			= '';
				$isumma[$i]				= '';
				$ivero[$i]				= '';
				$iselite[$i]			= '';
				$isumma_valuutassa[$i]	= '';
			}
		}
		if ($kuitti != '') require("inc/kuitti.inc");

		$tee="";
		$selite="";
		$fnimi="";
		$summa="";
		$nimi="";
		$kuitti="";
		$kuva = "";
		$turvasumma_valuutassa = "";
		$valkoodi = "";

		echo "<font class='message'>".t("Tosite luotu")."!</font>";

		echo "	<form action = 'muutosite.php' method='post'>
				<input type='hidden' name='tee' value='E'>
				<input type='hidden' name='tunnus' value='$tunnus'>
				<input type='Submit' value='".t("Näytä tosite")."'>
				</form><br><hr><br>";
	}

	if ($kukarow['kirjoitin'] == '') echo "<font class='message'>".t("Sinulla ei ole oletuskirjoitinta. Et voi tulostaa kuitteja")."!</font><br>";

	// Tehdään haluttu määrä tiliöintirivejä

	$sel = array();
	$sel[$maara] = "selected";

	echo "<form action = 'tosite.php' method='post'>
		<input type='hidden' name='tee'value='N'>
		<input type='hidden' name='tpp' maxlength='2' size=2 value='$tpp'>
		<input type='hidden' name='tpk' maxlength='2' size=2 value='$tpk'>
		<input type='hidden' name='tpv' maxlength='4' size=4 value='$tpv'>
		<table>
		<tr>
		<th>".t("Tiliöintirivien määrä")."</th>
		<td>
		<select name='maara'>
		<option $sel[3] value='3'>2</option>
		<option $sel[5] value='5'>4</option>
		<option $sel[9] value='9'>8</option>
		<option $sel[17] value='17'>16</option>
		<option $sel[33] value='33'>32</option>
		<option $sel[151] value='151'>150</option>
		</select>
		</td><td class='back'><input type = 'submit' value = '".t("Perusta")."'></td>
		</tr>
		</table></form><br>";

	$tee='N'; // mennään suoraan uudelle tositteelle..

	// Uusi tosite
	if ($tee == 'N') {

		if ($maara=='') $maara='3'; //näytetään defaulttina kaks

		//päivämäärän tarkistus
		$tilalk = split("-", $yhtiorow["tilikausi_alku"]);
		$tillop = split("-", $yhtiorow["tilikausi_loppu"]);

		$tilalkpp = $tilalk[2];
		$tilalkkk = $tilalk[1]-1;
		$tilalkvv = $tilalk[0];

		$tilloppp = $tillop[2];
		$tillopkk = $tillop[1]-1;
		$tillopvv = $tillop[0];

		echo "	<SCRIPT LANGUAGE=JAVASCRIPT>

				function verify(){
					var pp = document.tosite.tpp;
					var kk = document.tosite.tpk;
					var vv = document.tosite.tpv;

					pp = Number(pp.value);
					kk = Number(kk.value)-1;
					vv = Number(vv.value);

					if (vv < 1000) {
						vv = vv+2000;
					}

					var dateSyotetty = new Date(vv,kk,pp);
					var dateTallaHet = new Date();
					var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

					var tilalkpp = $tilalkpp;
					var tilalkkk = $tilalkkk;
					var tilalkvv = $tilalkvv;
					var dateTiliAlku = new Date(tilalkvv,tilalkkk,tilalkpp);
					dateTiliAlku = dateTiliAlku.getTime();


					var tilloppp = $tilloppp;
					var tillopkk = $tillopkk;
					var tillopvv = $tillopvv;
					var dateTiliLoppu = new Date(tillopvv,tillopkk,tilloppp);
					dateTiliLoppu = dateTiliLoppu.getTime();

					dateSyotetty = dateSyotetty.getTime();

					if(dateSyotetty < dateTiliAlku || dateSyotetty > dateTiliLoppu) {
						var msg = '".t("VIRHE: Syötetty päivämäärä ei sisälly kuluvaan tilikauteen")."!';

						if(alert(msg)) {
							return false;
						}
						else {
							return false;
						}
					}

					if(ero >= 30) {
						var msg = '".t("Oletko varma, että haluat päivätä laskun yli 30pv menneisyyteen")."?';
						return confirm(msg);
					}
					if(ero <= -14) {
						var msg = '".t("Oletko varma, että haluat päivätä laskun yli 14pv tulevaisuuteen")."?';
						return confirm(msg);
					}

					if (vv < dateTallaHet.getFullYear()) {
						if (5 < dateTallaHet.getDate()) {
							var msg = '".t("Oletko varma, että haluat päivätä laskun menneisyyteen")."?';
							return confirm(msg);
						}
					}
					else if (vv == dateTallaHet.getFullYear()) {
						if (kk < dateTallaHet.getMonth() && 5 < dateTallaHet.getDate()) {
							var msg = '".t("Oletko varma, että haluat päivätä laskun menneisyyteen")."?';
							return confirm(msg);
						}
					}


				}
			</SCRIPT>";

		$formi = 'tosite';
		$kentta = 'tpp';

		echo "<br>";
		echo "<font class='message'>Syötä tositteen otsikkotiedot:</font>";

		echo "<form name = 'tosite' action = 'tosite.php' method='post' enctype='multipart/form-data' onSubmit = 'return verify()'>";
		echo "<input type='hidden' name='tee' value='I'>";
		echo "<input type='hidden' name='maara' value='$maara'>";
		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Tositteen päiväys")."</th>";
		echo "<td><input type='text' name='tpp' maxlength='2' size='2' value='$tpp'>";
		echo "<input type='text' name='tpk' maxlength='2' size='2' value='$tpk'>";
		echo "<input type='text' name='tpv' maxlength='4' size='4' value='$tpv'> ".t("ppkkvvvv")."</td>";
		echo "</tr>";
		echo "<tr><th>".t("Summa")."</th><td><input type='text' name='summa' value='$turvasumma_valuutassa'>";

		$query = "	SELECT nimi, tunnus
					FROM valuu
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);

		echo " <select name='valkoodi'>";

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel="";
			if (($vrow['nimi'] == $yhtiorow["valkoodi"] and $valkoodi == "") or ($vrow["nimi"] == $valkoodi)) {
				$sel = "selected";
			}
			echo "<option value='$vrow[nimi]' $sel>$vrow[nimi]</option>";
		}

		echo "</select>";
		echo "</td></tr>";
		echo "<tr><th>".t("Nimi")."</th><td><input type='text' name='nimi' value='$nimi'>";

		if ($kuitti != '') {
			$kuitti = 'checked';
		}
		if ($kukarow['kirjoitin'] != '') {
			echo " ".t("Tulosta kuitti")." <input type='checkbox' name='kuitti' $kuitti>";
		}
		echo "</td>";
		echo "</tr>";

		if(is_readable("excel_reader/reader.php")) {
			$excel = ".xls, ";
		}
		else {
			$excel = "";
		}

		echo "<th>".t("Selite")."</th>";
		echo "<td><input type='text' name='selite' value='$selite' maxlength='150' size=60></td>";
		echo "</tr>";

		echo "<tr><th>".t("Tositteen kuva/liite")."</th>";

		if (strlen($kuva) > 0) {
			echo "<td>".t("Kuva jo tallessa")."!<input name='kuva' type='hidden' value = '$kuva'></td>";
		}
		else {
			echo "<input type='hidden' name='MAX_FILE_SIZE' value='8000000'>";
			echo "<td><input name='userfile' type='file'></td></tr>";
		}

		echo "</table>";

		echo "<br><font class='message'>Lue tositteen rivit tiedostosta:</font>";

		echo "<table>
				<tr>
					<th>".t("Valitse tiedosto")."</th>
					<td><input type='file' name='tositefile' onchage='submit()'>  <font class='info'>".t("Vain $excel.txt ja .cvs tiedosto sallittuja")."</td>
				</tr>
			</table>";

		echo "<br><font class='message'>Tai syötä tositteen rivit käsin:</font>";


		for ($i=1; $i<$maara; $i++) {

			echo "<table>";
			echo "<tr>";
			echo "<th width='200'>".t("Tili")."</th>";
			echo "<th>".t("Tarkenne")."</th>";
			echo "<th>".t("Summa")."</th>";
			echo "<th>".t("Vero")."</th>";
			echo "</tr>";

			echo "<tr>";

			if ($iulos[$i] == '') {
				//Annetaan selväkielinen nimi
				$tilinimi = '';

				if ($itili[$i] != '') {
					$query = "	SELECT nimi
								FROM tili
								WHERE yhtio = '$kukarow[yhtio]' and tilino = '$itili[$i]'";
					$vresult = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($vresult) == 1) {
						$vrow = mysql_fetch_array($vresult);
						$tilinimi = "<br>".$vrow['nimi'];
					}
				}
				echo "<td width='200' valign='top'><input type='text' size='13' name='itili[$i]' value='$itili[$i]''>$tilinimi</td>";
			}
			else {
				echo "<td width='200' valign='top'>$iulos[$i]</td>";
			}

			// Tehdään kustannuspaikkapopup
			$query = "SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K' and kaytossa <> 'E'
						ORDER BY nimi";
			$vresult = mysql_query($query) or pupe_error($query);
			echo "<td><select name='ikustp[$i]'>";
			echo "<option value =' '>".t("Ei kustannuspaikkaa")."";

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
			echo "<option value =' '>".t("Ei kohdetta")."";

			while ($vrow = mysql_fetch_array($vresult)) {
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
			echo "<option value = ' '>".t("Ei projektia")."";

			while ($vrow = mysql_fetch_array($vresult)) {
				$sel="";
				if ($iprojekti[$i] == $vrow[0]) {
					$sel = "selected";
				}
				echo "<option value ='$vrow[0]' $sel>$vrow[1]";
			}
			echo "</select></td>";
			echo "<td valign='top'><input size='13' type='text' name='isumma[$i]' value='$isumma_valuutassa[$i]'> $valkoodi<br><br>$isumma[$i]</td>";

			if ($hardcoded_alv != 1) {
				echo "<td valign='top'>" . alv_popup('ivero['.$i.']', $ivero[$i]) . "</td>";
			}
			else {
				echo "<td></td>";
			}

			echo "<td class='back'><font class='error'>$ivirhe[$i]</font></td>";
			echo "</tr>";

			echo "<tr><td>".t("Selite")."</td><td colspan='3'><input type='text' name='iselite[$i]' value='$iselite[$i]' maxlength='150' size=60></td></tr>";

			echo "</table><br>";
		}

		if ($gok==1) {
			echo "<table cellpadding='2'>";
			echo "<tr>";
			echo "<th>".t("Tosite yhteensä")."</th>";
			echo "<td>";

			$heittotila == '';

			if ($heittook != '') {
				$heittotila = 'checked';
			}

			$totsumma = round($totsumma,2);

			if (abs($totsumma) >= 0.01) {
				echo "<font class='error'>$totsumma</font>";

				if ($valkoodi != $yhtiorow["valkoodi"]) {
					// turvasumma kotivaluutassa
					$totsumma_valuutassa = round($totsumma / $kurssi, 2);
					echo " <font class='error'>($totsumma_valuutassa $valkoodi)</font>";
				}

				echo "</td><td>";
				echo "<input type='checkbox' name='heittook' $heittotila> ".t("Hyväksy heitto");

			}
			else {
				echo " $totsumma ".t("Tiliöinti ok!");
			}
			echo "</td>";
			echo "</tr>";
			echo "</table><br>";
		}

		echo "<input type='submit' value='".t("Tee tosite")."'></form>";

	}

	require "inc/footer.inc";
?>
