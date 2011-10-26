<?php

	if (!isset($link)) require "inc/parametrit.inc";

	enable_ajax();

	if ($livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	echo "<font class='head'>".t("Uusi konsernitosite")."</font><hr>";

	// Tarkistetetaan sy�tteet perustusta varten
	if ($tee == 'I') {

		$summa = str_replace ( ",", ".", $summa);
		$tpk += 0;
		$tpp += 0;
		$tpv += 0;

		if ($tpv < 1000) $tpv += 2000;

		$val = checkdate($tpk, $tpp, $tpv);

		if (!$val) {
			echo "<font class='error'>" . t('Virheellinen tapahtumapvm') . "</font><br>";
			$gok = 1; //  Tositetta ei kirjoiteta kantaan viel�
		}

		$kuva = false;
		if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {

			$filetype = $_FILES['userfile']['type'];
			$filesize = $_FILES['userfile']['size'];
			$filename = $_FILES['userfile']['name'];

			$data = mysql_real_escape_string(file_get_contents($_FILES['userfile']['tmp_name']));

			// lis�t��n kuva
			$query = "	INSERT INTO liitetiedostot set
						yhtio      = '{$kukarow['yhtio']}',
						liitos     = 'lasku',
						laatija    = '{$kukarow['kuka']}',
						luontiaika = now(),
						data       = '$data',
						filename   = '$filename',
						filesize   = '$filesize',
						filetype   = '$filetype'";
			$result = mysql_query($query) or pupe_error($query);
			$liitostunnus = mysql_insert_id();
			$fnimi = $liitostunnus;
		}

		$turvasumma = $summa;
		$totsumma[$kukarow['yhtio']] = 0;
		$totmaara = $omaara+$vmaara; // Yhteens� tarvittavien tili�intirivien m��r�

		for ($i=0; $i<$totmaara; $i++) {

			//Vaihdetaan yrityst�!
			if ($i == $omaara) {
				$turvayhtio = $kukarow['yhtio'];
				$kukarow['yhtio'] = $vastaanottaja;
				$totsumma[$kukarow['yhtio']] = 0;
			}

			// K�sitell��nk� rivi??
			if (strlen($itili[$i]) > 0 or strlen($isumma[$i]) > 0) {

				$isumma[$i] = str_replace (",", ".", $isumma[$i]);

				// Siirret��n oletusselite tili�inneille
				if (strlen($selite) > 0 and strlen($iselite[$i]) == 0) {
					$iselite[$i] = $selite;
				}

				// Selite puuttuu
				if (strlen($iselite[$i]) == 0) {
					$ivirhe[$i] = t('Rivilt� puuttuu selite').'<br>';
					$gok = 1;
				}

				// Oletussummalla korvaaminen mahdollista
				if ($turvasumma > 0) {
					if ($isumma[$i] == '-') {
						// Summan vastaluku k�ytt��n
						$isumma[$i] = -1 * $turvasumma;
					}
					elseif ($isumma[$i] == 0) {
						// Kopioidaan summa
						$isumma[$i] = $turvasumma;
					}
				}

				// Summa puuttuu
				if ($isumma[$i] == 0) {
					$ivirhe[$i] .= t('Rivilt� puuttuu summa').'<br>';
					$gok = 1;
				}

				$totsumma[$kukarow['yhtio']] += $isumma[$i];

				$ulos			= "";
				$virhe			= "";
				$tili			= $itili[$i];
				$summa			= $isumma[$i];
				$selausnimi		= "itili[$i]"; // Minka niminen mahdollinen popup on?
				$tositetila 	= "X";
				$tositeliit 	= 0;
				$kustp_tark		= $ikustp[$i];
				$kohde_tark		= $ikohde[$i];
				$projekti_tark	= $iprojekti[$i];

				require ("inc/tarkistatiliointi.inc");

				$ivirhe[$i] .= $virhe;
				$iulos[$i] = $ulos;

				// Sielt� kenties tuli p�ivitys tilinumeroon
				if ($ok == 0) {
					// Annetaan k�ytt�j�n p��tt�� onko ok
					if ($itili[$i] != $tili) {
						$itili[$i] = $tili;
						$gok = 1; // Tositetta ei kirjoiteta kantaan viel�
					}
				}
				else {
					$gok = 1;
				}
				$gok = $ok; // Nostetaan virhe ylemm�lle tasolle
			}
		}

		if ($totsumma[$kukarow['yhtio']] != -1 * $totsumma[$turvayhtio]) {
			echo "<font class='error'>".t("Yrityksille menev�t tili�innit eiv�t t�sm��")."!</font><br><br>";
			$tee = '';
		}

		if ($gok == 1) { // Jossain tapahtui virhe
			echo "<font class='error'>".t("Jossain oli virheit�/muutoksia!")."</font><br><br>";
			$tee = '';
		}

		$summa = $turvasumma;
		$kukarow['yhtio']=$turvayhtio; //Palautetaan oletusyhtio
	}

	// Kirjoitetaan tosite jos tiedot ok!
	if ($tee == 'I') {

		$query = "	INSERT into lasku set
					yhtio = '$kukarow[yhtio]',
					tapvm = '$tpv-$tpk-$tpp',
					tila = 'X',
					laatija = '$kukarow[kuka]',
					luontiaika = now()";
		$result = mysql_query($query) or pupe_error($query);
		$tunnus = mysql_insert_id($link);
		$turvatunnus = $tunnus;

		if ($fnimi) {
			// p�ivitet��n kuvalle viel� linkki toiseensuuntaa
			$query = "	UPDATE liitetiedostot SET
						liitostunnus = '$tunnus',
						selite = '$selite $summa'
						WHERE tunnus = '$fnimi'";
			$result = mysql_query($query) or pupe_error($query);
		}

		$totsumma = 0;
		$totmaara = $omaara+$vmaara; // Yhteens� tarvittavien tili�intirivien m��r�

		for ($i=0; $i<$totmaara; $i++) {

			// Vaihtuuko yritys?
			if ($i == $omaara) {
				// Vastavienti

				if ($totsumma < 0) {
					$tili = $yhtiorow['konsernisaamiset'];
				}
				else {
					$tili = $yhtiorow['konsernivelat'];
				}

				$selite = t('Konsernitositteen vastavienti');
				$summa = $totsumma * -1;
				$vero = 0;

				require ("inc/teetiliointi.inc");

				// Aloitetaan vieraan yrityksen tili�innit
				$totsumma = 0;
				$turvayhtio = $kukarow['yhtio'];
				$kukarow['yhtio'] = $vastaanottaja;

				$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

				$query = "	INSERT into lasku set
							yhtio = '$kukarow[yhtio]',
							tapvm = '$tpv-$tpk-$tpp',
							tila = 'X',
							laatija = '$kukarow[kuka]',
							luontiaika = now()";
				$result = mysql_query($query) or pupe_error($query);
				$tunnus = mysql_insert_id ($link);

				if (!empty($fnimi)) {

					// kopioidaan liitetiedosto toiselta rivilt�
					$query = "SELECT * from liitetiedostot where tunnus='$fnimi'";
					$res = mysql_query($query) or pupe_error($query);
					$liite = mysql_fetch_assoc($res);

					// n�m� arvot vaihdetaan ainoastaan
					$liite['liitostunnus'] = $tunnus;
					$liite['yhtio']        = $kukarow['yhtio'];
					unset($liite['tunnus']);

					$cols = array();
					foreach ($liite as $col => $val) {
						$cols[] =  "$col = '" . mysql_real_escape_string($val) . "'";
					}

					$ins = "INSERT into liitetiedostot set " . implode(', ', $cols);
					mysql_query($ins) or pupe_error($ins);

					$fnimi = '';
				}

			}

			if (strlen($itili[$i]) > 0) {
				// Tehd��n tili�innit
				$tili = $itili[$i];
				$kustp = $ikustp[$i];
				$kohde = $ikohde[$i];
				$projekti = $iprojekti[$i];
				$summa = $isumma[$i];
				$totsumma += $summa; // Vastavieti� varten
				$vero = $ivero[$i];
				$selite = $iselite[$i];
				require ("inc/teetiliointi.inc");
			}

			$itili[$i] = '';
			$ikustp[$i] = '';
			$ikohde[$i] = '';
			$iprojekti[$i] = '';
			$isumma[$i] = '';
			$ivero[$i] = '';
			$iselite[$i] = '';
		}

		// Vastavienti
		if ($totsumma < 0) {
			$tili = $yhtiorow['konsernisaamiset'];
		}
		else {
			$tili = $yhtiorow['konsernivelat'];
		}

		$summa = $totsumma * -1;
		$vero = 0;
		$selite = t('Konsernitositteen vastavienti');

		require ("inc/teetiliointi.inc");

		$kukarow['yhtio'] = $turvayhtio;
		$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

		$tee = '';
		$selite = '';
		$summa = 0;

		echo "<font class='message'>".t("Konsernitosite luotu")."!</font>";

		echo "	<form action='muutosite.php' method='post'>
				<input type='hidden' name='tee' value='E'>
				<input type='hidden' name='tunnus' value='$turvatunnus'>
				<input type='submit' value='".t("N�yt� tosite")."'>
				</form><br><hr><br>";
	}

	// Uusi tosite
	if ($tee == '') {

		if ($vastaanottaja == '') {
			$query = "	SELECT yhtio, nimi
						FROM yhtio
						WHERE konserni = '$yhtiorow[konserni]'
						AND trim(konserni) != ''
						AND yhtio != '$kukarow[yhtio]'";
			$yresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($yresult) < 1) {
				echo "<font class='error'>".t("Konsernissasi ei ole yht��n yrityst�. N�in ollen et voi k�ytt�� t�t� toimintoa")."</font>";
				require ("inc/footer.inc");
				exit;
			}

			echo "<form name = 'tosite' action = '$PHP_SELF' method='post'>";
			echo "<font class='message'>" . t("Valitse vastaanottava yritys") . " </font> ";
			echo "<select name='vastaanottaja'>";

			while ($yrow = mysql_fetch_array($yresult)) {
				$sel = '';
				if ($yrow['yhtio'] == $vastaanottaja) {
					$sel = "selected";
				}
				echo "<option value='$yrow[0]' $sel>$yrow[1]</option>";
			}
			echo "</select>";
			echo " <input type='Submit' value = '" . t("Valitse") ."'></form>";
		}
		else {
			if ($omaara == 0 or $vmaara == 0) {
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
					<td>".t("Oman yrityksen tili�intirivien m��r�")."</td>
					<td>
					<select name='omaara'>
					<option value ='1'>1
					<option value ='2'>2
					<option value ='4'>4
					<option value ='8'>8
					</select>
					</td>
					</tr><tr>
					<td>".t("Konserniyrityksen tili�intirivien m��r�")."</td>
					<td>
					<select name='vmaara'>
					<option value ='1'>1
					<option value ='2'>2
					<option value ='4'>4
					<option value ='8'>8
					</select>
					</td><td class='back'><input type = 'submit' value = '".t("Perusta") ."'></td>
					</tr></table></form><br>";

			$formi = 'tosite';
			$kentta = 'tpp';

			echo "<font class='message'>Sy�t� tositteen otsikkotiedot:</font>";

			echo "<form name = 'tosite' action = '$PHP_SELF' method='post' enctype='multipart/form-data'>
					<input type='hidden' name='tee' value='I'>
					<input type='hidden' name='omaara' value='$omaara'>
					<input type='hidden' name='vmaara' value='$vmaara'>
					<input type='hidden' name='vastaanottaja' value='$vastaanottaja'>
					<table>";

			echo "<tr>";
			echo "<th>".t("Tositteen p�iv�ys")."</th>";
			echo "<td><input type='text' name='tpp' maxlength='2' size=2 value='$tpp'>";
			echo "<input type='text' name='tpk' maxlength='2' size=2 value='$tpk'>";
			echo "<input type='text' name='tpv' maxlength='4' size=4 value='$tpv'> ".t("ppkkvvvv")."</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>".t("Summa")."</th>";
			echo "<td><input type='text' name='summa' value='$summa'></td>";
			echo "</tr>";

			echo "<th>".t("Selite"). "</th>";
			echo "<td><input type='text' name='selite' value='$selite' maxlength='150' size=60></td>";
			echo "</tr>";
			echo "<tr>";

			echo "<th>".t("Tositteen kuva/liite")."</th>";

			if (!empty($fnimi)) {
				echo "<td>".t("Liite jo tallessa")."!<input name='fnimi' type='hidden' value = '$fnimi'></td></tr></table>";
			}
			else {
				echo "<td><input name='userfile' type='file' value = '$userfile_name'></td></tr></table>";
			}

			echo "<br><font class='message'>".t("Oma yritys").": $yhtiorow[nimi]</font>";

			$totmaara = $omaara+$vmaara; // Yhteens� tarvittavien tili�intirivien m��r�

			for ($i=0; $i<$totmaara; $i++) {

				// Valitaan vastaanottava konsernin j�sen
				if ($i == $omaara) {
					$turvayhtio=$kukarow['yhtio'];

					$kukarow['yhtio'] = $vastaanottaja;
					$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
					echo "<font class='message'>".t("Vastaanottava yritys").": $yhtiorow[nimi]</font>";
				}

				echo "<table>";
				echo "<tr>";
				echo "<th width='200'>".t("Tili")."</th>";
				echo "<th>".t("Tarkenne")."</th>";
				echo "<th>".t("Summa")."</th>";
				echo "<th>".t("Vero")."</th>";
				echo "</tr>";

				echo "<tr>";
				if ($iulos[$i] == '') {
					//Annetaan selv�kielinen nimi
					$tilinimi = '';

					if ($itili[$i] != '') {
						$query = "	SELECT nimi
									FROM tili
									WHERE yhtio = '$kukarow[yhtio]'
									AND tilino = '$itili[$i]'";
						$vresult = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($vresult) == 1) {
							$vrow = mysql_fetch_array($vresult);
							$tilinimi = "<br>".$vrow['nimi'];
						}
					}
					echo "<td width='200' valign='top'>".livesearch_kentta("tosite", "TILIHAKU", "itili[$i]", 170, $itili[$i], "EISUBMIT")." $tilinimi</td>";
				}
				else {
					echo "<td>$iulos[$i]</td>";
				}

				// Tehd��n kustannuspaikkapopup
				$query = "	SELECT tunnus, nimi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = 'K'
							and kaytossa != 'E'
							ORDER BY koodi+0, koodi, nimi";
				$vresult = mysql_query($query) or pupe_error($query);

				echo "<td><select name='ikustp[$i]'>";
				echo "<option value =' '>".t("Ei kustannuspaikkaa");

				while ($vrow = mysql_fetch_array($vresult)) {
					$sel = "";
					if ($ikustp[$i] == $vrow[0]) {
						$sel = "selected";
					}
					echo "<option value ='$vrow[0]' $sel>$vrow[1]";
				}
				echo "</select><br>";

				// Tehd��n kohdepopup
				$query = "	SELECT tunnus, nimi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = 'O'
							and kaytossa != 'E'
							ORDER BY koodi+0, koodi, nimi";
				$vresult = mysql_query($query) or pupe_error($query);

				echo "<select name='ikohde[$i]'>";
				echo "<option value =' '>".t("Ei kohdetta");

				while ($vrow = mysql_fetch_array($vresult)) {
					$sel = "";
					if ($ikohde[$i] == $vrow[0]) {
						$sel = "selected";
					}
					echo "<option value ='$vrow[0]' $sel>$vrow[1]";
				}
				echo "</select><br>";

				// Tehd��n projektipopup
				$query = "	SELECT tunnus, nimi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = 'P'
							and kaytossa != 'E'
							ORDER BY koodi+0, koodi, nimi";
				$vresult = mysql_query($query) or pupe_error($query);

				echo "<select name='iprojekti[$i]'>";
				echo "<option value =' '>".t("Ei projektia");

				while ($vrow = mysql_fetch_array($vresult)) {
					$sel = "";
					if ($iprojekti[$i] == $vrow[0]) {
						$sel = "selected";
					}
					echo "<option value ='$vrow[0]' $sel>$vrow[1]";
				}
				echo "</select></td>";

				echo "<td><input type='text' name='isumma[$i]' value='$isumma[$i]'></td>";

				echo "<td>" . alv_popup('ivero['.$i.']', $ivero[$i]) . "</td>";

				echo  "<td class='back'>$ivirhe[$i]</td>";

				echo "</tr>";

				echo "<tr><td colspan = '4'>".t("Selite").":<input type='text' name='iselite[$i]' value='$iselite[$i]' maxlength='150' size=60></td></tr>";
				echo "</table><br>";
			}

			echo "</table>";
			echo "<br>";
			echo "<input type='Submit' value = '".t("Tee tosite")."'></form>";
		}
	}

	require ("inc/footer.inc");

?>