<?php
	require "inc/parametrit.inc";
	require "inc/alvpopup.inc";

	echo "<font class='head'>".t("Uusi muu tosite")."</font><hr>";

	// Tarkistetetaan syˆtteet perustusta varten
	if ($tee == 'I') {
		$totsumma = 0;
		$summa = str_replace ( ",", ".", $summa);

		$tpk += 0;
		$tpp += 0;
		$tpv += 0;
		if ($tpv < 1000) $tpv += 2000;

		$val = checkdate($tpk, $tpp, $tpv);
		if (!$val) {
			echo "<b>".t("Virheellinen tapahtumapvm")."</b><br>";
			$gok = 1; //  Tositetta ei kirjoiteta kantaan viel‰
		}

		// Talletetaan k‰ytt‰j‰n nimell‰ tositteen/liitteen kuva, jos sellainen tuli
		// koska, jos tulee virheit‰ tiedosto katoaa. Kun kaikki on ok, annetaan sille oikea nimi
		if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {

			// otetaan file extensio
			$path_parts = pathinfo($_FILES['userfile']['name']);
			$ext = $path_parts['extension'];
			if (strtoupper($ext) == "JPEG") $ext = "jpg";

			// laskun polku
			$polku = $yhtiorow['lasku_polku_abs'];
			// uniikki filenimi (t‰m‰ tallennetaan kantaan, huom eka / on pakollinen, sill‰ erotetaan scannatut ja verkkolaskut!!)
			// otetaan nimest‰ 31 ekaa merkki‰ ja lis‰t‰‰n .EXT niin tulee 35 merkki‰ joka on tietokannassa kent‰n pituus.. kenties ois pit‰ny muuttaa tietokantaaa???
			$fnimi = substr("/".$kukarow['yhtio']."-".$kukarow['kuka']."-".md5(uniqid(rand(),true)),0,31).".$ext";
			// t‰nne siirret‰‰n
			$lopnimi = $polku.$fnimi;

			// extensio pit‰‰ olla oikein
			if (strtoupper($ext) != "JPG" and strtoupper($ext) != "PNG" and strtoupper($ext) != "GIF" and strtoupper($ext) != "PDF") {
				echo "<font class='error'>".t("Ainoastaan .jpg .gif .png .pdf tiedostot sallittuja")."!</font>";
				$tee = "N";
				$fnimi = "";
			}
			// ja file jonkun kokonen
			elseif ($_FILES['userfile']['size'] == 0) {
				echo "<font class='error'>".t("Tiedosto on tyhj‰")."!</font>";
				$tee = "N";
				$fnimi = "";
			}
			// Talletetaan laskun kuva oikeaan paikkaan
			elseif (!move_uploaded_file($_FILES['userfile']['tmp_name'] , $lopnimi)) {
				echo t("Laskun")." ".$_FILES['userfile']['tmp_name']." ".t("tallennus ep‰onnistui paikkaan")." $fnimi<br>";
				$tee = "N";
				$fnimi = "";
			}
		}
		elseif (isset($_FILES['userfile']['error']) and $_FILES['userfile']['error'] != 4) {
			// nelonen tarkoittaa, ettei mit‰‰n file‰ uploadattu.. eli jos on joku muu errori niin ei p‰‰stet‰ eteenp‰in
			echo "<font class='error'>".t("Laskun kuvan l‰hetys ep‰onnistui")."! (Error: ".$_FILES['userfile']['error'].")</font><br>";
			$tee = "N";
		}

		if (is_uploaded_file($_FILES['tositefile']['tmp_name'])) {

			//	ei koskaan p‰ivitet‰ automaattisesti
			$tee = "N";

			// otetaan file extensio
			$path_parts = pathinfo($_FILES['tositefile']['name']);
			$ext = strtoupper($path_parts['extension']);

			// extensio pit‰‰ olla oikein
			if (!in_array($ext, array("XLS","CSV","TXT"))) {
				echo "<font class='error'>".t("Ainoastaan .xls .cvs .txt tiedostot sallittuja")."! $ext</font>";
				$fnimi = "";
			}
			// ja file jonkun kokonen
			elseif ($_FILES['tositefile']['size'] == 0) {
				echo "<font class='error'>".t("Tiedosto on tyhj‰")."!</font>";
				$fnimi = "";
			}
			else {
				if(in_array($ext, array("TXT","CSV"))) {
					$file=file($_FILES['tositefile']['tmp_name'], FILE_IGNORE_NEW_LINES);
				}
				else {
					require_once ('excel_reader/reader.php');

					// ExcelFile
					$data = new Spreadsheet_Excel_Reader();

					// Set output Encoding.
					$data->setOutputEncoding('CP1251');
					$data->setRowColOffset(0);
					$data->read($_FILES['tositefile']['tmp_name']);

					$file = $data->sheets[0]['cells'];
				}

				$maara=0;
				foreach($file as $key => $value) {


					if($maara==0) {
						if(is_array($value)) {
							foreach($value as $v8) {
								$otsikot[]=strtolower($v8);
							}
						}
						else {
							$rivi=explode("\t",strtolower($key));
							$otsikot=$rivi;
						}
					}
					else {
						if(is_array($value)) {
							$rivi=$value;
						}
						else {
							$rivi=explode("\t",$key);
						}

						if(count($rivi)<>count($otsikot)) {
							echo "<font class='error'>".t("VIRHE!!! aineistovirhe")."rivilla: $maara (".count($rivi)." != ".count($otsikot).")</font>";
							break;
						}

						$rivi=array_combine($otsikot, $rivi);

						$isumma[$maara] 	= (float) $rivi["summa"];
						$itili[$maara]  	= (int) $rivi["tilino"];
						$ikustp[$maara] 	= (int) $rivi["kustp"];
						$ikustp[$maara]		= (int) $rivi["kustp"];
						$ikohde[$maara]		= (int) $rivi["kohde"];
						$ivero[$maara]		= (int) $rivi["alv"];
						$iselite[$maara] 	= $rivi["selite"];
					}

					$maara++;
				}

				//	Lis‰t‰‰n viel‰ 2 tyhj‰‰ rivi‰ loppuun
				$maara+=2;
			}
		}
		elseif (isset($_FILES['tositefile']['error']) and $_FILES['tositefile']['error'] != 4) {
			// nelonen tarkoittaa, ettei mit‰‰n file‰ uploadattu.. eli jos on joku muu errori niin ei p‰‰stet‰ eteenp‰in
			echo "<font class='error'>".t("Tositetiedoston sis‰‰nluku ep‰onnistui")."! (Error: ".$_FILES['userfile']['error'].")</font><br>";
			$tee = "N";
		}

		$turvasumma = $summa;
		$kuittiok = 0; // Onko joku vienneist‰ kassa-tili, jotta kuitti voidaan tulostaa

		for ($i=1; $i<$maara; $i++) {
			if ((strlen($itili[$i]) > 0) or (strlen($isumma[$i]) > 0)) { // K‰sitell‰‰nkˆ rivi??

				$isumma[$i] = str_replace ( ",", ".", $isumma[$i]);
				if ((strlen($selite) > 0) && (strlen($iselite[$i]) == 0)) { // Siirret‰‰n oletusselite tiliˆinneille
					$iselite[$i] = $selite;
				}
				if (strlen($iselite[$i]) == 0) { // Selite puuttuu
					$ivirhe[$i] = t('Rivilt‰ puuttuu selite').'<br>';
					$gok = 1;
				}

				if ($turvasumma > 0) {  // Oletussummalla korvaaminen mahdollista
					if ($isumma[$i] == '-') { // Summan vastaluku k‰yttˆˆn
						$isumma[$i] = -1 * $turvasumma;
					}
					else {
						if ((strlen($itili[$i]) > 0) && ($isumma[$i] == 0))  { // Kopioidaan summa
							$isumma[$i] = $turvasumma;
						}
					}
				}
				if ($isumma[$i] == 0) { // Summa puuttuu
					$ivirhe[$i] .= t('Rivilt‰ puuttuu summa').'<br>';
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
				if ($vero!='') $ivero[$i]=$vero; //Jos meill‰ on hardkoodattuvero, otetaan se k‰yttˆˆn
				$ivirhe[$i] .= $virhe;
				$iulos[$i] = $ulos;
				if ($ok==0) { // Sielt‰ kenties tuli p‰ivitys tilinumeroon
					if ($itili[$i] != $tili) { // Annetaan k‰ytt‰j‰n p‰‰tt‰‰ onko ok
						$itili[$i] = $tili;
						$gok = 1; // Tositetta ei kirjoiteta kantaan viel‰
					}
					else {
						if ($itili[$i] == $yhtiorow['kassa']) $kassaok=1;
					}
				}
				else {
					$gok = $ok; // Nostetaan virhe ylemm‰lle tasolle
				}
			}

		}

		if ($kuitti != '') {
			if ($kassaok==0) {
				$gok=1;
				echo "<font class='error'>".t("Pyysit kuittia, mutta kassatilille ei ole vientej‰")."</font><br>";
			}
			if ($nimi == '') {
				$gok=1;
				echo "<font class='error'>".t("Kuitille on annettava nimi")."</font><br>";
			}
		}

		if (abs($totsumma) >= 0.01 and $heittook  == '') {
				$gok=1;
				echo "<font class='error'>".t("Tositteen summat eiv‰t mene tasan")."</font><br>";
		}

 		// Jossain tapahtui virhe
		if ($gok == 1) {
			echo "<font class='error'>".t("Jossain oli virheit‰/muutoksia")."!</font><br>";
			$tee = 'N';
		}

		$summa = $turvasumma;
	}

	// Kirjoitetaan tosite jos tiedot ok!
	if ($tee == 'I') {

		// Talletetaan tositteen/liitteen kuva, jos sellainen tuli
		if(strlen($fnimi) > 0) {
			$ebid = $fnimi;
		}
		else {
			$ebid = "";
		}

		$query = "	INSERT into lasku set
						yhtio = '$kukarow[yhtio]',
						tapvm = '$tpv-$tpk-$tpp',
						ebid = '$ebid',
						nimi = '$nimi',
						tila = 'X',
						laatija = '$kukarow[kuka]',
						luontiaika = now()";
		$result = mysql_query($query) or pupe_error($query);

//		echo "$query <br>";
		$tunnus = mysql_insert_id ($link);

		// Tehd‰‰n tiliˆinnit
		for ($i=1; $i<$maara; $i++) {
			if (strlen($itili[$i]) > 0) {
				$tili = $itili[$i];
				$kustp = $ikustp[$i];
				$kohde = $ikohde[$i];
				$projekti = $iprojekti[$i];
				$summa = $isumma[$i];
				$vero = $ivero[$i];
				$selite = $iselite[$i];
				require "inc/teetiliointi.inc";
				$itili[$i]='';
				$ikustp[$i]='';
				$ikohde[$i]='';
				$iprojekti[$i]='';
				$isumma[$i]='';
				$ivero[$i]='';
				$iselite[$i]='';
			}
		}
		if ($kuitti != '') require("inc/kuitti.inc");
		$tee="";
		$selite="";
		$fnimi="";
		$summa="";
		$nimi="";
		$kuitti="";

		echo "<font class='message'>".t("Tosite luotu")."</font><br>";
	}

	if ($kukarow['kirjoitin'] == '') echo "<font class='message'>".t("Sinulla ei ole oletuskirjoitinta. Et voi tulostaa kuitteja")."!</font><br>";

	// Tehd‰‰n haluttu m‰‰r‰ tiliˆintirivej‰

	echo "<form action = 'tosite.php' method='post'>
		<input type='hidden' name='tee'value='N'>
		<input type='hidden' name='tpp' maxlength='2' size=2 value='$tpp'>
		<input type='hidden' name='tpk' maxlength='2' size=2 value='$tpk'>
		<input type='hidden' name='tpv' maxlength='4' size=4 value='$tpv'>
		<table>
		<tr>
		<td>".t("Tiliˆintirivien m‰‰r‰")."</td>
		<td>
		<select name='maara'>
		<option value ='3'>2
		<option value ='5'>4
		<option value ='9'>8
		<option value ='17'>16
		<option value ='33'>32
		</select>
		</td><td><input type = 'submit' value = '".t("Perusta")."'></td>
		</tr>
		</table></form><br>";

	$tee='N'; // menn‰‰n suoraan uudelle tositteelle..

	// Uusi tosite
	if ($tee == 'N') {

		if ($maara=='') $maara='3'; //n‰ytet‰‰n defaulttina kaks

		//p‰iv‰m‰‰r‰n tarkistus
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
						var msg = '".t("VIRHE: Syˆtetty p‰iv‰m‰‰r‰ ei sis‰lly kuluvaan tilikauteen")."!';

						if(alert(msg)) {
							return false;
						}
						else {
							return false;
						}
					}

					if(ero >= 30) {
						var msg = '".t("Oletko varma, ett‰ haluat p‰iv‰t‰ laskun yli 30pv menneisyyteen")."?';
						return confirm(msg);
					}
					if(ero <= -14) {
						var msg = '".t("Oletko varma, ett‰ haluat p‰iv‰t‰ laskun yli 14pv tulevaisuuteen")."?';
						return confirm(msg);
					}


				}
			</SCRIPT>";

		$formi = 'tosite';
		$kentta = 'tpp';

		echo "<font class='message'>Syˆt‰ tositteen otsikkotiedot:</font>";

		echo "<form name = 'tosite' action = 'tosite.php' method='post' enctype='multipart/form-data' onSubmit = 'return verify()'>
				<input type='hidden' name='tee' value='I'>
				<input type='hidden' name='maara' value='$maara'>
		      <table><tr>
		      <td>".t("Tositteen p‰iv‰ys")."</td>
		      <td><input type='text' name='tpp' maxlength='2' size='2' value='$tpp'>
			<input type='text' name='tpk' maxlength='2' size='2' value='$tpk'>
			<input type='text' name='tpv' maxlength='4' size='4' value='$tpv'> ".t("ppkkvvvv")."</td>
		      </tr>";
		echo "<tr><td>".t("Summa")."</td><td><input type='text' name='summa' value='$summa'></td></tr>";
		echo "<tr><td>".t("Nimi")."</td><td><input type='text' name='nimi' value='$nimi'>";

		if ($kuitti != '') $kuitti = 'checked';
		if ($kukarow['kirjoitin'] != '') echo " ".t("Tulosta kuitti")." <input type='checkbox' name='kuitti' $kuitti>";
		echo "</td></tr>";

		if(is_readable("excel_reader/reader.php")) {
			$excel = ".xls, ";
		}
		else {
			$excel = "";
		}

		echo "	<td colspan = '2'>".t("Selite")." <input type='text' name='selite' value='$selite' maxlength='150' size=60></td>
				</tr>";

		// annetaan mahdollisuus lis‰t‰ laskun kuva vaan jos dirikka on oikein ja writable...
		if (is_writable($yhtiorow['lasku_polku_abs'])) {
			echo "<tr><td>".t("Tositteen kuva/liite")."</td>";

			if (strlen($fnimi) > 0) {
				echo "<td>".t("Kuva jo tallessa")."!<input name='fnimi' type='hidden' value = '$fnimi'></td>";
			}
			else {
				echo "<input type='hidden' name='MAX_FILE_SIZE' value='8000000'>";
				echo "<td><input name='userfile' type='file'></td></tr>";
			}
		}

		echo "</table>";

		echo "<br><font class='message'>Lue tositteen rivit tiedostosta:</font>";

		echo "<table>
				<tr>
					<th>".t("Valitse tiedosto")."</th>
					<td><input type='file' name='tositefile' onchage='submit()'>  <font class='info'>".t("Vain $excel.txt ja .cvs tiedosto sallittuja")."</td>
				</tr>
			</table>";

		echo "<br><font class='message'>Tai syˆt‰ tositteen rivit k‰sin:</font>";


		echo "<table>
		      <tr><th>".t("Tili")."</th><th>".t("Tarkenne")."</th>
		      <th>".t("Summa")."</th><th>".t("Vero")."</th></tr>";

		for ($i=1; $i<$maara; $i++) {
			echo "<tr>";

			if ($iulos[$i] == '') {
				//Annetaan selv‰kielinen nimi
				$tilinimi='';

				if ($itili[$i] != '') {
					$query = "SELECT nimi
								FROM tili
								WHERE yhtio = '$kukarow[yhtio]' and tilino = '$itili[$i]'";
					$vresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($vresult) == 1) {
						$vrow=mysql_fetch_array($vresult);
						$tilinimi = $vrow['nimi'] . "<br>";
					}
				}
				echo "<td>$tilinimi<input type='text' name='itili[$i]' value='$itili[$i]''></td>";
			}
			else {
				echo "<td>$iulos[$i]</td>";
			}

			// Tehd‰‰n kustannuspaikkapopup
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

			// Tehd‰‰n kohdepopup
			$query = "SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O' and kaytossa <> 'E'
						ORDER BY nimi";
			$vresult = mysql_query($query) or pupe_error($query);
			echo "<select name='ikohde[$i]'>";
			echo "<option value =' '>".t("Ei kohdetta")."";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($ikohde[$i] == $vrow[0]) {
					$sel = "selected";
				}
				echo "<option value ='$vrow[0]' $sel>$vrow[1]";
			}
			echo "</select><br>";

			// Tehd‰‰n projektipopup
			$query = "SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P' and kaytossa <> 'E'
						ORDER BY nimi";
			$vresult = mysql_query($query) or pupe_error($query);
			echo "<select name='iprojekti[$i]'>";
			echo "<option value =' '>".t("Ei projektia")."";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($iprojekti[$i] == $vrow[0]) {
					$sel = "selected";
				}
				echo "<option value ='$vrow[0]' $sel>$vrow[1]";
			}
			echo "</select></td>";

			echo "<td><input type='text' name='isumma[$i]' value='$isumma[$i]'></td>";

			if ($hardcoded_alv!=1) {
				echo "<td>" . alv_popup('ivero['.$i.']', $ivero[$i]) . "</td>";
			}
			else {
				echo "<td></td>";
			}

			echo  "<td><font class='error'>$ivirhe[$i]</font></td>";

			echo "</tr>";

			echo "<tr><td colspan = '5'>
			      ".t("Selite").":<input type='text' name='iselite[$i]' value='$iselite[$i]' maxlength='150' size=60><br><br></td></tr>";
		}

		if ($gok==1) {
			echo "<tr><td>".t("Tosite yhteens‰")."</td><td>";
			$heittotila == '';
			if ($heittook != '') $heittotila = 'checked';
			if (abs($totsumma) >= 0.01) echo t("Hyv‰ksy heitto")."<input type='checkbox' name='heittook' $heittotila>";
			echo "</td><td>";
			$totsumma = round($totsumma,2);
			if (abs($totsumma) >= 0.01) echo "<font class='error'>$totsumma</font>";
			else echo "$totsumma";
			echo "</td><td></td><td></td></tr>";
		}

		echo "</table><input type='Submit' value = '".t("Tee tosite")."'></form>";

	}

	require "inc/footer.inc";
?>
