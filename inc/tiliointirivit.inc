<?php

	// Annetaan mahdollisuus tehdä uusi tiliöinti, jos siihen on oikeus
	require "alvpopup.inc";

	// Onko tosite liitetty keikkaan
	$query = "SELECT laskunro from lasku where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus='$tunnus'";
	$keikres = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($keikres) > 0) {
		echo "<br><br><font class='message'>".t("Lasku on liitetty keikkaan, alv tiliöintejä ei voi muuttaa")."!</font>";
	}

	echo "<br><br>";
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Tili")."</th>";
	echo "<th>".t("Tarkenne")."</th>";
	echo "<th>".t("Summa")."</th>";
	echo "<th>".t("Vero")."</th>";
	echo "<th>".t("Tapvm")."</th>";
	echo "<th>".t("Selite")."</th>";

	if ($viivatut == 'on') {
		echo "<th>".t("Laadittu")." / ".t("Poistettu")."</th>";
	}
	echo "<th></th>";
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

		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K' and kaytossa != 'E'
					ORDER BY nimi";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$ulos = "<select name = 'kustp' style='width: 140px'><option value = ' '>".t("Ei kustannuspaikkaa");

			while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
				$valittu = "";
				if ($kustannuspaikkarow["tunnus"] == $kustp) {
					$valittu = "selected";
				}
				$ulos .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[nimi]";
			}
			$ulos .= "</select><br>";
		}

		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O' and kaytossa != 'E'
					ORDER BY nimi";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$ulos .= "<select name = 'kohde' style='width: 140px'><option value = ' '>".t("Ei kohdetta");

			while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
				$valittu = "";
				if ($kustannuspaikkarow["tunnus"] == $kohde) {
					$valittu = "selected";
				}
				$ulos .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[nimi]";
			}
			$ulos .= "</select><br>";
		}

		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = 'P' and kaytossa != 'E'
					ORDER BY nimi";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$ulos .= "<select name = 'projekti' style='width: 140px'><option value = ' '>".t("Ei projektia");

			while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
				$valittu = "";
				if ($kustannuspaikkarow["tunnus"] == $projekti) {
					$valittu = "selected";
				}
				$ulos .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[nimi]";
			}
			$ulos .= "</select>";
		}

		echo "<tr>
				<td valign='top'>
				<form name = 'uusi' action = '$PHP_SELF#$tunnus' method='post'>
				<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
				<input type='hidden' name = 'tee' value = 'U'>
				<input type='hidden' name = 'tunnus' value = '$tunnus'>
				<input type='hidden' name = 'viivatut' value = '$viivatut'>
				<input type='hidden' name = 'laji' value = '$laji'>
				<input type='hidden' name = 'tositenro' value = '$tositenro'>
				<input type='hidden' name = 'vv' value = '$vv'>
				<input type='hidden' name = 'kk' value = '$kk'>
				<input type='hidden' name = 'iframe' value = '$iframe'>
				<input type='hidden' name = 'iframe_id' value = '$iframe_id'>";

		if ($tiliulos == '') {
			livesearch_kentta("uusi", "TILIHAKU", "tili", 170, $tili, "EISUBMIT");
		}
		else {
			echo "$tiliulos";
		}

		echo "</td>
			<td valign='top'>$ulos</td>
			<td valign='top'>
			<input type='text' name='summa' value = '$summa' size='8'>
			<input type='hidden' name='vanhasumma' value = '$summa'>
			<input type='hidden' name='vanhasumma_valuutassa' value = '$summa_valuutassa'>
			<input type='hidden' name='vanhasumma_valkoodi' value = '$valkoodi'>
			<input type='hidden' name='alv_tili' value = '$alv_tili'>
			</td>";

		// jos ollaan liitetty lasku keikkaan, ei anneta laittaa veroa
		if (mysql_num_rows($keikres) == 0) {
			// Jos alvit on hardkoodattu tileihin (hui, kauheaa, mutta joku niin tahtoo), niin salasanat.php:ssä
			// on määritettävä $hardcoded_alv=1;
			if ($hardcoded_alv != 1) {
				echo "<td align='right' valign='top'>".alv_popup('vero', $vero)."</td>";
			}
			else {
				echo "<td align='right' valign='top'></td>";
			}
		}
		else {
			echo "<td align='right' valign='top'><input type='hidden' name='vero' value='0'>0</td>";
		}

		echo "<td valign='top'>";
		
		// haetaan kaikki tiliöintien tapahtumapäivät plus unionilla tämä päivä mukaan
		$query = "	(SELECT distinct(tapvm) pvm
					FROM tiliointi
					WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and korjattu = '' and tapvm >= '$yhtiorow[tilikausi_alku]')
					UNION
					(SELECT CURDATE() pvm)
					UNION
					(SELECT if ('$trow[tapvm]' >= '$yhtiorow[tilikausi_alku]', '$trow[tapvm]', CURDATE()) pvm)
					UNION
					(SELECT mapvm pvm FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus' and mapvm >= '$yhtiorow[tilikausi_alku]')";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 1) {
			echo "<select name = 'tiliointipvm'>";
			while ($tiliointirow = mysql_fetch_array($result)) {
				$valittu = "";
				if ($tiliointirow["pvm"] == $tiliointipvm) $valittu = "selected";
				echo "<option value = '$tiliointirow[pvm]' $valittu>".tv1dateconv($tiliointirow["pvm"])."</option>";
			}
			echo "</select>";
		}
		else {
			$tiliointirow = mysql_fetch_array($result);
			// tapahtumapäivä on vanhalla tilikaudella, ei anneta lisätä kun tälle päivälle
			if (strtotime($tiliointirow["pvm"]) < strtotime($yhtiorow['tilikausi_alku'])) {
				$tiliointirow["pvm"] = date("Y-m-d");
			}
			echo tv1dateconv($tiliointirow["pvm"])."<input type='hidden' name='tiliointipvm' value ='$tiliointirow[pvm]'>";
		}
		echo "</td>";

		echo "<td valign='top'>";
		echo "<input type='text' name='selite' value = '$selite'>";
		echo "</td>";

		if ($viivatut == 'on') {
			echo "<td></td>";
		}

		if ($virhe != "") $virhe .= "<br>";

		echo "<td valign='top'><input type='Submit' value = '".t("Lisää")."'></td>";
		
		if (strpos($_SERVER['SCRIPT_NAME'], "hyvak.php") === FALSE) {
			echo "<td valign='top' class='back' nowrap><font class='error'>$virhe</font><input type='checkbox' name = 'jaksota'> ".t("Jaksota")."</td>";
		}
		
		echo "</tr>";
		echo "</form>";
		$formi = 'uusi';
		$kentta = 'tili';
	}

	$lisa = '';
	if ($viivatut != 'on') {
		$lisa = " and tiliointi.korjattu = ''";
	}

	$query = "	SELECT *,
				concat_ws(' / ', a.nimi, b.nimi, c.nimi) tarkenne,
				tili.nimi tilinimi,
				tiliointi.selite selite,
				tiliointi.laatija laatija,
				tiliointi.laadittu laadittu,
				tiliointi.tunnus tunnus,
				la.nimi laatija_nimi,
				ko.nimi korjattu_nimi
				FROM tiliointi
				LEFT JOIN tili ON tili.yhtio = tiliointi.yhtio and tili.tilino = tiliointi.tilino
				LEFT JOIN kustannuspaikka a ON a.yhtio = '$kukarow[yhtio]' and a.tunnus = tiliointi.kustp and a.tyyppi = 'K'
				LEFT JOIN kustannuspaikka b ON b.yhtio = '$kukarow[yhtio]' and b.tunnus = tiliointi.kohde and b.tyyppi = 'O'
				LEFT JOIN kustannuspaikka c ON c.yhtio = '$kukarow[yhtio]' and c.tunnus = tiliointi.projekti and c.tyyppi = 'P'
				LEFT JOIN kuka la ON (la.yhtio = tiliointi.yhtio and la.kuka = tiliointi.laatija)
				LEFT JOIN kuka ko ON (ko.yhtio = tiliointi.yhtio and ko.kuka = tiliointi.korjattu)
				WHERE tiliointi.yhtio = '$kukarow[yhtio]'
				and tiliointi.ltunnus = '$tunnus'
				$lisa
				ORDER BY tiliointi.tapvm, tiliointi.tilino, tiliointi.tunnus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<br><br><font class='message'>".t("Laskulla ei ole vielä tiliöintirivejä")."</font><br><br>";
	}

	$maara = mysql_num_rows($result);

	while ($trow = mysql_fetch_array ($result)) {

		$class = "";
		if ($trow["korjattu"] != '') {
			$class = "tumma";
		}

		echo "<tr class='aktiivi'>";
		echo "<td valign='top' class='$class' nowrap>$trow[tilino] &raquo; $trow[tilinimi]</td>";
		echo "<td valign='top' class='$class'>$trow[tarkenne]</td>";
		echo "<td valign='top' class='$class' align='right' nowrap>$trow[summa]</td>";
		echo "<td valign='top' class='$class' align='right'>". (float) $trow["vero"]. "</td>";
		echo "<td align='center' valign='top' class='$class'>".tv1dateconv($trow["tapvm"])."</td>";
		echo "<td valign='top' class='$class'>$trow[selite]";
		if ($trow["summa_valuutassa"] != 0 and $trow["valkoodi"] != "" and $trow["valkoodi"] != $yhtiorow["valkoodi"]) {
			echo " ($trow[summa_valuutassa] $trow[valkoodi])";
		}
		echo "</td>";

		if ($viivatut == "on") {
			echo "<td class='$class' valign='top'>".tv1dateconv($trow["laadittu"], "pitkä")." &raquo; $trow[laatija_nimi]";
			if ($trow["korjattu"] != "") {
				echo "<br>".tv1dateconv($trow["korjausaika"], "pitkä")." &raquo; $trow[korjattu_nimi]";
			}
			echo "</td>";
		}


		$tilikausi_loppu = strtotime($yhtiorow['tilikausi_loppu']);
		$tilikausi_alku = strtotime($yhtiorow['tilikausi_alku']);
		$tiliointi_pvm = strtotime($trow['tapvm']);

		if ((mysql_num_rows($keikres) == 0 or $trow["vero"] == 0) and ($tiliointi_pvm <= $tilikausi_loppu and $tiliointi_pvm >= $tilikausi_alku) and ($trow["lukko"] == "" or $kukarow["taso"] == 3) and $trow["korjattu"] == "" and $maara > 1 and $oikeurow["paivitys"] == 1 and ($kukarow["taso"] == 2 or $kukarow["taso"] == 3)) {
			//Eli ei näytetä muuta-nappia, jos ei oikeuksia,
			//jos rivi on poistettu TAI se on tiliöinnin viimeinen TAI se on lukittu,
			//jos lasku on liitetty keikkaan ja rivillä on veroa
			//jos sen saa poistaa niin on hankalaa löytää heittävät tositteet...
			echo "<td>
					<form action = '$PHP_SELF#$tunnus' method='post'>
					<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
					<input type='hidden' name='tee' value = 'P'>
					<input type='hidden' name='tunnus' value = '$tunnus'>
					<input type='hidden' name='viivatut' value = '$viivatut'>
					<input type='hidden' name='laji' value = '$laji'>
					<input type='hidden' name='vv' value = '$vv'>
					<input type='hidden' name='kk' value = '$kk'>
					<input type='hidden' name='ptunnus' value = '$trow[tunnus]'>
					<input type='hidden' name = 'iframe' value = '$iframe'>
					<input type='hidden' name = 'iframe_id' value = '$iframe_id'>
					<input type='Submit' value = '".t("Muuta")."'>
					</td></tr></form>"; // Tuossa tarkkana
		}
		else {
			if ($maara > 1) {

				if (mysql_num_rows($keikres) != 0 or $trow['lukko'] != '' or $oikeurow['paivitys'] != 1 or ($kukarow["taso"] != 2 and $kukarow["taso"] != 3) or ($tiliointi_pvm > $tilikausi_loppu or $tiliointi_pvm < $tilikausi_alku)) {
					echo "<td class='$class'>";
					//Rivi on poistettu
					if ($trow['korjattu'] != '') {
						echo "<img src=pics/lullacons/mini-trash.png>";
					}
					else {
						echo "<img src=pics/lullacons/error.png>";
					}
					echo "</td></tr>";
				}
				else {
					echo "<td class='$class'><img src=pics/lullacons/mini-trash.png></td></tr>";
				}
			}
			else {
				echo "<td class='$class'>".t("Viimeinen")."</td></tr>";
			}
		}

		if ($trow['korjattu'] == '') {
			$yhtsum += $trow['summa']; //Riviä ei ole poistettu
		}
	}

	$yhtok = t("Tiliöinti kesken");
	$class = "error";
	$ok    = 0;
	
	if (round($yhtsum, 2) == 0) {
		$yhtok = t("Tiliöinti ok");
		$class = "ok";
		$ok    = 1;
	}

	if ($viivatut == 'on') {
		$viivatut = 'checked';
		$colspan = '3';
	}
	else {
		$viivatut = '';
		$colspan = '2';
	}

	echo "<tr>";
	echo "<td colspan='2'><font class='$class'>$yhtok</font></td>";
	echo "<td align='right'><font class='$class'>". sprintf("%.02f", $yhtsum) . "</font></td>";
	echo "<td colspan='2'></td>";
	echo "<td colspan='$colspan'>";
	
	echo "<form action = '$PHP_SELF#$tunnus' method='post'>
			<input type='hidden' name = 'tee' value='E'>
			<input type='hidden' name = 'lopetus' value = '$lopetus'>
			<input type='hidden' name = 'tunnus' value = '$tunnus'>
			<input type='hidden' name = 'iframe' value = '$iframe'>
			<input type='hidden' name = 'iframe_id' value = '$iframe_id'>";
			
	echo "<input type='checkbox' name='viivatut' onclick='submit();' $viivatut> ";
	echo t('Näytä poistetut');
	echo "</form>";
	echo "</td>";

	echo "</tr>";

	echo "<tr>";

	$query = "	SELECT liitostunnus, tilaustyyppi
				FROM lasku
				WHERE yhtio = '{$kukarow["yhtio"]}' and tunnus = '$tunnus'";
	$lasres = mysql_query($query) or pupe_error($query);
	$mtklasrow = mysql_fetch_array($lasres);

	//	Saadaanko muokata superina?
	$query = "	SELECT yhtio
				FROM oikeu
				WHERE yhtio	= '$kukarow[yhtio]'
				and kuka	= '$kukarow[kuka]'
				and nimi	= 'matkalasku.php'
				and alanimi = 'SUPER'";
	$oires = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($oires) > 0) {
		$su = "SUPER";
	}
	else {
		$su = "";
	}

	$query = "	SELECT tunnus
				FROM toimi
				WHERE yhtio = '$kukarow[yhtio]'
				AND tunnus = '$mtklasrow[liitostunnus]'
				AND laskun_erittely != ''";
	$toimires = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($toimires)==1) {
		echo "	<td class='back'><form action = '".$palvelin2."matkalasku.php' method='get'>
					<input type='hidden' name = 'tilausnumero' value='$tunnus'>
					<input type='hidden' name = 'tee' value='ERITTELE'>
					<input type='hidden' name = 'toim' value='$su'>";
					
		if (strpos($_SERVER['SCRIPT_NAME'], "hyvak.php") !== FALSE) {
			echo "<input type='hidden' name = 'lopetus' value='$lopetus/SPLIT/".$palvelin2."hyvak.php////tunnus=$tunnus'>";
		}
		else {
			echo "<input type='hidden' name = 'lopetus' value='$lopetus/SPLIT/".$palvelin2."muutosite.php////tee=E//tunnus=$tunnus'>";
		}
					
		echo "<input type='Submit' value='".t("Kululaskun erittely")."'></form></td>";
	}

	if ($mtklasrow["tilaustyyppi"] == "M") {
		echo "<td class='back'><form action = '".$palvelin2."matkalasku.php' method='post'>
					<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
					<input type='hidden' name = 'tilausnumero' value='$tunnus'>
					<input type='hidden' name = 'tee' value='MUOKKAA'>
					<input type='hidden' name = 'toim' value='$su'>";

		if (strpos($_SERVER['SCRIPT_NAME'], "hyvak.php") !== FALSE) {
			echo "<input type='hidden' name = 'lopetus' value='$lopetus/SPLIT/".$palvelin2."hyvak.php////tunnus=$tunnus'>";
		}
		else {
			echo "<input type='hidden' name = 'lopetus' value='$lopetus/SPLIT/".$palvelin2."muutosite.php////tee=E//tunnus=$tunnus'>";
		}
					
		echo "<input type='Submit' value='".t("Tarkastele matkalaskua")."'>
					</form></td>";
	}

	echo "</tr>";
	echo "</table><br><br>";

?>