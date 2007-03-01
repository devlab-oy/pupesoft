<?php

// Annetaan mahdollisuus tehdä uusi tiliöinti, jos siihen on oikeus
		require "alvpopup.inc";
		
		echo "<table><tr>";
		echo "<th>".t("Tili")."</th><th>".t("Tarkenne")."</th><th>".t("Summa")."</th><th>".t("Vero")."</th><th>".t("Tapvm/Selite")."</th>";
		if ($viivatut == 'on') echo "<th>Poistettu/Laadittu</th>";
		if ($seutunnus != 0) { // Tämä toimii vain selaajatarkista.php:n yhteydessä
			echo "<th><a href = '$PHP_SELF?tee=E&tunnus=$seutunnus&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut#$seutunnus'>>></a></th>";
		}
		else {
			echo "<th></th>";
		}
		echo "</tr>";

		if ($oikeurow['paivitys'] == 1) {
			if ($ok != 1) {
	// Annetaan tyhjät tiedot, jos rivi oli virheetön
				$tili = '';
				$kustp = '';
				$kohde = '';
				$projekti = '';
				$summa = '';
				$tiliointipvm = '';
				$vero = alv_oletus();
				$selite = '';
			}

			$query = "SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);
			$ulos = "<select name = 'kustp'><option value = ' '>".t("Ei kustannuspaikkaa");
			while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
				$valittu = "";
				if ($kustannuspaikkarow[0] == $kustp) {
					$valittu = "selected";
				}
				$ulos .= "<option value = '$kustannuspaikkarow[0]' $valittu>$kustannuspaikkarow[1]";
			}
			$ulos .= "</select><br>";

			$query = "SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);
			$ulos .= "<select name = 'kohde'><option value = ' '>".t("Ei kohdetta");
			while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
				$valittu = "";
				if ($kustannuspaikkarow[0] == $kohde) {
					$valittu = "selected";
				}
				$ulos .= "<option value = '$kustannuspaikkarow[0]' $valittu>$kustannuspaikkarow[1]";
			}
			$ulos .= "</select><br>";

			$query = "SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);
			$ulos .= "<select name = 'projekti'><option value = ' '>".t("Ei projektia");
			while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
				$valittu = "";
				if ($kustannuspaikkarow[0] == $projekti) {
					$valittu = "selected";
				}
				$ulos .= "<option value = '$kustannuspaikkarow[0]' $valittu>$kustannuspaikkarow[1]";
			}
			$ulos .= "</select>";

			echo "<tr>
					<td><form name = 'uusi' action = '$PHP_SELF#$tunnus' method='post'>
					<input type='hidden' name='tee' value = 'U'>
					<input type='hidden' name='tunnus' value = '$tunnus'>
					<input type='hidden' name='viivatut' value = '$viivatut'>
					<input type='hidden' name='laji' value = '$laji'>
					<input type='hidden' name='tositenro' value = '$tositenro'>
					<input type='hidden' name='vv' value = '$vv'>
					<input type='hidden' name='kk' value = '$kk'>";

			if ($tiliulos == '') {
				echo "<input type='text' name='tili' value = '$tili' size='8'>";
			}
			else {
				echo "$tiliulos";
			}

			list($laspvm,$suopvm) = split(" ",$laskunpvm);
			$query = "SELECT distinct(tapvm)
					FROM tiliointi
					WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and korjattu = ''";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) > 1) {
				echo "<br>".t("Tapvm");
				echo "<select name = 'tiliointipvm'>";
				while ($tiliointirow=mysql_fetch_array($result)) {
					$valittu = "";
					$pselite = "";
					if ($tiliointirow[0] == $tiliointipvm) $valittu = "selected";
					if ($tiliointirow[0] == $laspvm) $pselite = t("Laskun pvm");
					if ($tiliointirow[0] == $suopvm) $pselite = t("Suoritus pvm");
					echo "<option value = '$tiliointirow[0]' $valittu>$tiliointirow[0] $pselite";
				}
				echo "</select>";
			}
			else {
				$tiliointirow=mysql_fetch_array($result);
				echo "$tiliointirow[0]<input type='hidden' name='tiliointipvm' value ='$tiliointirow[0]'>";
			}
			echo "</td>
				<td>$ulos</td>
				<td><input type='text' name='summa' value = '$summa' size='8'></td>";
// Jos alvit on hardkoodattu tileihin (hui, kauheaa, mutta joku niin tahtoo), niin salasanat.php:ssä
// on määritettävä $hardcoded_alv=1;
			if ($hardcoded_alv!=1) {
				echo "<td>".alv_popup('vero', $vero)."</td>";
			}
			else {
				echo "<td></td>";
			}

			echo "<td>";

			echo "<input type='text' name='selite' value = '$selite'>
					<br>".t("Jaksota")."<input type='checkbox' name = 'jaksota'></td>";
			if ($viivatut == 'on') echo "<td></td>";
			echo "<td><font class='error'>$virhe</font><input type='Submit' value = '".t("Lisää")."'></td></form></tr>";
			$formi = 'uusi';
			$kentta = 'tili';
		}
		$lisa='';
		$slisa='';

		if ($viivatut != 'on') {
			$lisa = " and tiliointi.korjattu=''";
		}
		else {
			$slisa = ", if(korjattu='',concat_ws('@', laatija, laadittu),concat_ws('@', korjattu, korjausaika)) korjaus, korjattu poistettu";
		}

		$slAsa = "concat_ws('/',tiliointi.tilino,tili.nimi)";
		
		if ($kpexport == 1 or strtoupper($yhtiorow['maakoodi']) != 'FI') {
			$slAsa = "concat_ws('/',if(tosite<>0,concat_ws('-',left(tosite,2), 1*right(tosite,6)), '".t('#NA')."'),tiliointi.tilino,tili.nimi)";
		}
		
		
		$query = "	SELECT lukko, tiliointi.tunnus, $slAsa, tiliointi.kustp,
					tiliointi.kohde, tiliointi.projekti,
					tiliointi.summa, vero, concat_ws('/', tapvm,selite)
					$slisa
					FROM tiliointi
					LEFT JOIN tili ON tili.yhtio = tiliointi.yhtio and tili.tilino = tiliointi.tilino
					WHERE tiliointi.yhtio = '$kukarow[yhtio]' and tiliointi.ltunnus = '$tunnus' $lisa
					ORDER BY tiliointi.tapvm, tiliointi.tilino, tiliointi.tunnus";

		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) == 0) {
			echo "<br><br><font class='message'>".t("Laskulla ei ole vielä tiliöintirivejä")."</font><br><br>";
		}
		$maara = mysql_num_rows($result);


		while ($trow=mysql_fetch_array ($result)) {
			//Taas näitä null-juttuja
			if ($trow['korjattu'] == '@0000-00-00 00:00:00') $trow['korjattu'] = '';
			if ($trow['korjattu'] == '0000-00-00 00:00:00') $trow['korjattu'] = '';			

			echo "<tr>";
			if ($viivatut=='on') $poistasarake=1; else $poistasarake=0;
			for ($i=2; $i<mysql_num_fields($result)-$poistasarake; $i++) {
				if (($viivatut == 'on') && ($trow['poistettu'] != '')) {
					echo "<th>";
				}
				else {
					echo "<td>";
				}
				if ($i == 3) { // here we go again...
					if (strlen($trow[$i]) > 0) { // Meillä on kustannuspaikka
						$query = "SELECT nimi
                          				FROM kustannuspaikka
                          				WHERE yhtio = '$kukarow[yhtio]' and
												tunnus = '$trow[$i]' and
												tyyppi = 'K'";
						$xresult = mysql_query($query) or pupe_error($query);
						$xrow=mysql_fetch_array ($xresult);
						echo "$xrow[0]/";
					}
					$i++;
					if (strlen($trow[$i]) > 0) { // Meillä on kohde
						$query = "SELECT nimi
									FROM kustannuspaikka
									WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$trow[$i]' and tyyppi = 'O'";
						$xresult = mysql_query($query) or pupe_error($query);
						$xrow=mysql_fetch_array ($xresult);
						echo "$xrow[0]/";
					}
					$i++;
					if (strlen($trow[$i]) > 0) { // Meillä on projekti
						$query = "SELECT nimi
									FROM kustannuspaikka
									WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$trow[$i]' and tyyppi = 'P'";
						$xresult = mysql_query($query) or pupe_error($query);
						$xrow=mysql_fetch_array ($xresult);
						echo "$xrow[0]/";
					}
				}
				else {
					echo $trow[$i];
				}
				if (($viivatut == 'on') && ($trow['poistettu'] != '')) {
					echo "</th>";
				}
				else {
					echo "</td>";
				}

			}
			if (($trow['lukko']=='') && ($trow['poistettu'] == '') && ($maara > 1) && ($oikeurow['paivitys'] == 1)) {
				// Eli ei näytetä muuta-nappia, jos ei oikeuksia, 
				//jos rivi on poistettu TAI se on tiliöinnin viimeinen TAI se on lukittu, 
				//jos sen saa poistaa niin on hankalaa löytää heittävät tositteet...
				echo "<td>
						<form action = '$PHP_SELF#$tunnus' method='post'>
						<input type='hidden' name='tee' value = 'P'>
						<input type='hidden' name='tunnus' value = '$tunnus'>
						<input type='hidden' name='viivatut' value = '$viivatut'>
						<input type='hidden' name='laji' value = '$laji'>
						<input type='hidden' name='vv' value = '$vv'>
						<input type='hidden' name='kk' value = '$kk'>
						<input type='hidden' name='ptunnus' value = '$trow[1]'>
						<input type='Submit' value = '".t("Muuta")."'>
						</td></tr></form>"; // Tuossa tarkkana

			}
			else {
				if ($maara > 1) {
					if (($trow['lukko']=='1') or ($oikeurow['paivitys'] != 1)) {
						echo "<td>".t("Lukittu")."";
						if ($trow['poistettu'] != '') { //Rivi on poistettu
							echo " ".t("ja poistettu")."";
						}
						echo "</td></tr>";
					}
					else {
						echo "<td>".t("Poistettu")."</td></tr>";
					}
				}
				else {
					echo "<td>".t("Viimeinen")."</td></tr>";
				}
			}
			list($kala,$selite) = split("/", $trow[8]);

			if (strpos($trow['Tapvm/Selite'], "/")===FALSE){
				$selite="";
			}
			if ($trow['poistettu'] == '') $yhtsum += $trow[6]; //Riviä ei ole poistettu
		}

		$yhtok = "".t("Tiliöinti kesken")."...";
		if (round($yhtsum,2) == 0) {
			$yhtok = "".t("Tiliöinti ok")."!";
		}
		echo "<tr><td>$yhtok</td><td></td><td>" . round($yhtsum,2) . "</td><td></td>";
		
		echo "<td>";		
		// Tehdään boxi, jolla saadaan poistetut näkyviin
		echo "<form action = '$PHP_SELF#$tunnus' method='post'><input type='hidden' name=tee value='E'>
			<input type='hidden' name=tunnus value = '$tunnus'>";
		
		echo t('Näytä poistetut');
		
		if ($viivatut == 'on') $viivatut='checked'; 
		else $viivatut='';
		
		echo "<input type='checkbox' name='viivatut' onclick='submit();' $viivatut></form>";
		echo "</td>";
		
		if ($viivatut != '') echo "<td></td>";
		
		echo "<td></td></tr>";
		echo "</table><br><br>";
?>
