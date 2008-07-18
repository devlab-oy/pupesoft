<?php

	// Annetaan mahdollisuus tehdä uusi tiliöinti, jos siihen on oikeus
	require "alvpopup.inc";

	echo "<table><tr>";
			// Selaajatarkista.php:n yhteydessä selite omalle riville
	if (isset($seutunnus)) {
		echo "<th>".t("Tili")."</th><th>".t("Tarkenne")."</th><th>".t("Summa")."</th><th>".t("Vero")."</th>";
		if ($viivatut == 'on') echo "<th></th>";
		echo "</tr><tr><th colspan='3'>".t("Tapvm/Selite")."</th>";
	}
	else {
		echo "<th>".t("Tili")."</th><th>".t("Tarkenne")."</th><th>".t("Summa")."</th><th>".t("Vero")."</th><th>".t("Tapvm/Selite")."</th>";
	}

	if ($viivatut == 'on') {
		echo "<th>Poistettu/Laadittu</th>";
	}

	// Tämä toimii vain selaajatarkista.php:n yhteydessä
	if (isset($seutunnus)) {
		echo "<th><a href = '$PHP_SELF?tee=E&tunnus=$seutunnus&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut#$seutunnus'>>></a></th>";
	}
	else {
		echo "<th></th>";
	}
	echo "</tr>";

	if ($oikeurow['paivitys'] == 1 and ($kukarow['taso'] == 2 or $kukarow['taso'] == 3)) {

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

		// haetaan kaikki tiliöintien tapahtumapäivät plus unionilla tämä päivä mukaan
		$query = "	(SELECT distinct(tapvm)
					FROM tiliointi
					WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and korjattu = '' and tapvm >= '$yhtiorow[tilikausi_alku]')
					UNION
					(SELECT CURDATE())";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 1) {
			echo " ".t("Tapvm");
			echo ": <select name = 'tiliointipvm'>";

			while ($tiliointirow=mysql_fetch_array($result)) {
				$valittu = "";
				$pselite = "";
				if ($tiliointirow[0] == $tiliointipvm) $valittu = "selected";
				if ($tiliointirow[0] == $laspvm) $pselite = t("Laskun pvm");
				if ($tiliointirow[0] == $suopvm) $pselite = t("Suoritus pvm");
				if ($tiliointirow[0] == date("Y-m-d")) $pselite = t("Tämä pvm");
				echo "<option value = '$tiliointirow[0]' $valittu>$tiliointirow[0] $pselite</option>";
			}
			echo "</select>";
		}
		else {
			$tiliointirow = mysql_fetch_array($result);

			// tapahtumapäivä on vanhalla tilikaudella, ei anneta lisätä kun tälle päivälle
			if (strtotime($tiliointirow[0]) < strtotime($yhtiorow['tilikausi_alku'])) {
				$tiliointirow[0] = date("Y-m-d");
			}

			echo " ".t("Tapvm").": $tiliointirow[0]<input type='hidden' name='tiliointipvm' value ='$tiliointirow[0]'>";
		}
		echo "</td>
			<td>$ulos</td>
			<td>
			<input type='text' name='summa' value = '$summa' size='8'>
			<input type='hidden' name='vanhasumma' value = '$summa'>
			<input type='hidden' name='vanhasumma_valuutassa' value = '$summa_valuutassa'>
			<input type='hidden' name='vanhasumma_valkoodi' value = '$valkoodi'>
			</td>";

		// Jos alvit on hardkoodattu tileihin (hui, kauheaa, mutta joku niin tahtoo), niin salasanat.php:ssä
		// on määritettävä $hardcoded_alv=1;
		if ($hardcoded_alv != 1) {
			echo "<td>".alv_popup('vero', $vero)."</td>";
		}
		else {
			echo "<td></td>";
		}


		// Selaajatarkista.php:n yhteydessä selite omalle riville
		if (isset($seutunnus)) {
			if ($viivatut == 'on') echo "<td></td>";
			echo "</tr><tr><td colspan='3'>";
		}
		else {
			echo "<td>";
		}

		echo "<input type='text' name='selite' value = '$selite'>
				<br>".t("Jaksota")."<input type='checkbox' name = 'jaksota'></td>";

		if ($viivatut == 'on') {
			echo "<td></td>";
		}

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
		$slisa = ", if(tiliointi.korjattu='',concat_ws('@', tiliointi.laatija, tiliointi.laadittu),concat_ws('@', tiliointi.korjattu, tiliointi.korjausaika)) korjaus, tiliointi.korjattu poistettu";
	}

	$slAsa = "concat_ws('/',tiliointi.tilino,tili.nimi)";

	if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {
		$slAsa = "concat_ws('/',if(tosite<>0,concat_ws('-',left(tosite,2), 1*right(tosite,6)), '".t('#NA')."'),tiliointi.tilino,tili.nimi)";
	}

	$query = "	SELECT lukko, tiliointi.tunnus, $slAsa, concat_ws('/', a.nimi, b.nimi, c.nimi),
				tiliointi.summa, vero, concat_ws('/', tapvm, selite) selite
				$slisa, tapvm, tiliointi.summa_valuutassa
				FROM tiliointi
				LEFT JOIN tili ON tili.yhtio = tiliointi.yhtio and tili.tilino = tiliointi.tilino
				LEFT JOIN kustannuspaikka a ON a.yhtio = '$kukarow[yhtio]' and a.tunnus = tiliointi.kustp and a.tyyppi = 'K'
				LEFT JOIN kustannuspaikka b ON b.yhtio = '$kukarow[yhtio]' and b.tunnus = tiliointi.kohde and b.tyyppi = 'O'
				LEFT JOIN kustannuspaikka c ON c.yhtio = '$kukarow[yhtio]' and c.tunnus = tiliointi.projekti and c.tyyppi = 'P'
				WHERE tiliointi.yhtio = '$kukarow[yhtio]' and tiliointi.ltunnus = '$tunnus' $lisa
				ORDER BY tiliointi.tapvm, tiliointi.tilino, tiliointi.tunnus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<br><br><font class='message'>".t("Laskulla ei ole vielä tiliöintirivejä")."</font><br><br>";
	}

	$maara = mysql_num_rows($result);

	while ($trow = mysql_fetch_array ($result)) {
		//Taas näitä null-juttuja
		if ($trow['korjattu'] == '@0000-00-00 00:00:00') $trow['korjattu'] = '';
		if ($trow['korjattu'] == '0000-00-00 00:00:00') $trow['korjattu'] = '';

		echo "<tr>";
		if ($viivatut == 'on') {
			$poistasarake = 2;
		}
		else {
			$poistasarake = 1;
		}

		for ($i=2; $i < mysql_num_fields($result)-$poistasarake; $i++) {


			// Tämä toimii vain selaajatarkista.php:n yhteydessä
			if (isset($seutunnus) and mysql_field_name($result,$i)=='selite') {
				if ($viivatut == 'on' and $trow['poistettu'] != '') {
					echo "<th></th></tr><tr><th colspan='3'>";
				}
				else {
					echo "<td></td></tr><tr><td colspan='3'>";
				}

			}
			else {
				if ($viivatut == 'on' and $trow['poistettu'] != '') {
					echo "<th>";
				}
				else {
					echo "<td>";
				}
			}

			echo $trow[$i];

			if ($viivatut == 'on' and $trow['poistettu'] != '') {
				echo "</th>";
			}
			else {
				echo "</td>";
			}

		}

		$tilikausi_loppu = strtotime($yhtiorow['tilikausi_loppu']);
		$tilikausi_alku = strtotime($yhtiorow['tilikausi_alku']);
		$tiliointi_pvm = strtotime($trow['tapvm']);

		if (($tiliointi_pvm <= $tilikausi_loppu and $tiliointi_pvm >= $tilikausi_alku) and ($trow["lukko"] == "" or $kukarow["taso"] == 3) and $trow["poistettu"] == "" and $maara > 1 and $oikeurow["paivitys"] == 1 and ($kukarow["taso"] == 2 or $kukarow["taso"] == 3)) {
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

				if ($trow['lukko'] != '' or $oikeurow['paivitys'] != 1 or ($kukarow["taso"] != 2 and $kukarow["taso"] != 3) or ($tiliointi_pvm > $tilikausi_loppu or $tiliointi_pvm < $tilikausi_alku)) {
					echo "<td><img src=pics/lullacons/error.png>";
					//Rivi on poistettu
					if ($trow['poistettu'] != '') {
						echo "<img src=pics/lullacons/mini-trash.png>";
					}
					echo "</td></tr>";
				}
				else {
					echo "<td><img src=pics/lullacons/mini-trash.png></td></tr>";
				}
			}
			else {
				echo "<td>".t("Viimeinen")."</td></tr>";
			}
		}

		list($kala,$selite) = split("/", $trow['selite']);

		if (strpos($trow['Tapvm/Selite'], "/") === FALSE) {
			$selite="";
		}

		if ($trow['poistettu'] == '') {
			$yhtsum += $trow['summa']; //Riviä ei ole poistettu
		}
	}

	$yhtok = "".t("Tiliöinti kesken")."...";
	if (round($yhtsum,2) == 0) {
		$yhtok = "".t("Tiliöinti ok")."!";
	}

	if (isset($seutunnus)) {
		echo "<tr><td>$yhtok</td><td></td><td>" . round($yhtsum,2) . "</td>";
	}
	else {
		echo "<tr><td>$yhtok</td><td></td><td>";
		if (round($yhtsum,2) == 0) {
			echo abs(round($yhtsum,2));
		}
		else {
			echo round($yhtsum,2);
		}
		echo "</td><td></td>";
	}

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

	if (!isset($seutunnus)) echo "<td></td>";

	echo "</tr>";

	echo "<tr>";

	$query = "	SELECT liitostunnus, tilaustyyppi
				FROM lasku
				WHERE yhtio = '{$kukarow["yhtio"]}' and tunnus = '$tunnus'";
	$lasres = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($lasres);

	//	Saadaanko muokata superina?
	$query = "	SELECT yhtio
				FROM oikeu
				WHERE yhtio	= '$kukarow[yhtio]'
				and kuka	= '$kukarow[kuka]'
				and nimi	= 'matkalasku.php'
				and alanimi = 'SUPER'";
	$oires = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($oires) > 0) {
		$su = "SUPER";
	}
	else {
		$su = "";
	}

	$query = "select tunnus from toimi where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]' and laskun_erittely != ''";
	$toimires = mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($toimires)==1) {
		echo "	<td class='back'><form action = 'matkalasku.php' method='get'>
					<input type='hidden' name = 'tilausnumero' value='$tunnus'>
					<input type='hidden' name = 'tee' value='MUOKKAA'>
					<input type='hidden' name = 'toim' value='$su'>
					<input type='hidden' name = 'lopetus' value='".urlencode("muutosite.php?tee=E&tunnus=$tunnus")."'>
					<input type='Submit' value='".t("Kululaskun erittely")."'>
					</form>
				</td>";
	}

	if($laskurow["tilaustyyppi"] == "M") {
		echo "<td class='back'><form action = 'matkalasku.php' method='post'>
					<input type='hidden' name = 'tilausnumero' value='$tunnus'>
					<input type='hidden' name = 'tee' value='MUOKKAA'>
					<input type='hidden' name = 'toim' value='$su'>
					<input type='hidden' name = 'lopetus' value='".urlencode("muutosite.php?tee=E&tunnus=$tunnus")."'>
					<input type='Submit' value='".t("Tarkastele matkalaskua")."'>
					</form></td>";
	}

	echo "</tr>";
	echo "</table><br><br>";

?>
