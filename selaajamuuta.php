<?php
	require "inc/parametrit.inc";

	echo "<font class='head'>".t("Tiliöintien muutos/selailu")."</font><hr>";

	if ((($tee == 'U') or ($tee == 'P') or ($tee == 'M') or ($tee == 'J')) and ($oikeurow['paivitys'] != 1)) {
		echo "<b>".t("Yritit päivittää vaikka simulla ei ole siihen oikeuksia")."</b>";
		exit;
	}
	
	if ($tunnus != 0) {
		$query = "SELECT ebid, nimi, concat_ws(' ', tapvm, mapvm) laskunpvm FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
		$result = mysql_query ($query) or die ("Kysely ei onnistu $query");
		if (mysql_num_rows($result) > 0) {
			$trow=mysql_fetch_array ($result);
			$laskunpvm = $trow['laskunpvm'];
		}
		else {
			echo "".t("Lasku katosi")." $tunnus";
			exit;
		}	
	}
	
	if ($laji=='') $laji='O';
	if($laji=='M') $selm='SELECTED';
	if($laji=='O') $selo='SELECTED';
	if($laji=='MM') $selmm='SELECTED';
	if($laji=='OM') $selom='SELECTED';
	if($laji=='X') $selx='SELECTED';
	
	if($laji=='M') $lajiv="tila = 'U'";
	if($laji=='O') $lajiv="tila in ('H', 'Y', 'M', 'P', 'Q')";

	$pvm='tapvm';
	if($laji=='OM') {
		$lajiv="tila = 'Y'";
		$pvm='mapvm';
	}
	if ($laji == 'MM') {
		$lajiv="tila = 'U'";
		$pvm='mapvm';
	}	
	if($laji=='X') $lajiv="tila = 'X'";

	// mikä kuu/vuosi nyt on
	$year = date("Y");
	$kuu  = date("n");
	// poimitaan erikseen edellisen kuun viimeisen päivän vv,kk,pp raportin oletuspäivämääräksi
	if($vv=='') $vv = date("Y",mktime(0,0,0,$kuu,0,$year));
	if($kk=='') $kk = date("n",mktime(0,0,0,$kuu,0,$year));
	if(strlen($kk)==1) $kk = "0" . $kk; 


//Ylös hakukriteerit
	if ($viivatut == 'on') $viivacheck='checked';
	echo "<div id='ylos' style='height:30px;width:800px;overflow:auto;'>
			<form name = 'valinta' action = '$PHP_SELF' method='post'>
			<table>
			<tr><th>".t("Anna kausi, muodossa kk-vvvv").":</th>
			<td><input type = 'text' name = 'kk' value='$kk' size=2></td>
			<td><input type = 'text' name = 'vv' value='$vv' size=4></td>
			<th>".t("Mitkä tositteet listataan").":</th>
			<td><select name='laji'>
			<option value='M' $selm>".t("myyntilaskut")."
			<option value='O' $selo>".t("ostolaskut")."
			<option value='MM' $selmm>".t("myyntilaskut maksettu")."
			<option value='OM' $selom>".t("ostolaskut maksettu")."
			<option value='X' $selx>".t("muut")."
			</select></td>
			<td><input type='checkbox' name='viivatut' $viivacheck> ".t("Korjatut")."</td>
			<td class='back'><input type = 'submit' value = '".t("Valitse")."'></td>
			</tr>
			</table>
			</form></div>";
	$formi = 'valinta';
	$kentta = 'kk';


// Vasemmalle laskuluettelo
	
	$query = "	SELECT tunnus, nimi, $pvm, summa
				FROM lasku use index (yhtio_tila_tapvm)
				WHERE yhtio = '$kukarow[yhtio]' and left($pvm,7) = '$vv-$kk' and $lajiv
				ORDER BY tapvm desc, summa desc";

	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Haulla ei löytynyt yhtään laskua")."</font>";

	}
	else {
		echo "<div id='vasen' style='height:300px;width:300px;overflow:auto;float:left;'><table><tr>";
		for ($i = 1; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";
		echo "<tr>";
		while ($trow=mysql_fetch_array ($result)) {
			if ($seuraava == 1) { // Tässä on seuraavan laskun tunnus. Siirretään se seuraava nappulaan (, joka tehdään inc/tiliointirivit.inc:ssä)
				$seutunnus = $trow['tunnus'];
				$seuraava = 0;
			}
			$tyylia='<td>';
			$tyylil='</td>';
			if ($trow['tunnus']==$tunnus) {
				$tyylia='<th>';
				$tyylil='</th>';
				$seuraava = 1;
			}
			for ($i=1; $i<mysql_num_fields($result); $i++) {
				if ($i==1) {
					if (strlen($trow[$i])==0) $trow[$i]="(tyhjä)";
					echo "$tyylia<a href name='$trow[tunnus]'><a href='$PHP_SELF?tee=E&tunnus=$trow[tunnus]&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut#$trow[tunnus]'>$trow[$i]</a></a>$tyylil";
				}
				else {
						
					echo "$tyylia$trow[$i]$tyylil";
				}
			}
			echo "</tr>";
		}
	}
	echo "</tr></table></div>";


// Oikealle tiliöinnit
	echo "<div id='oikea' style='height:300px;width:750px;overflow:auto;float:none;'>";
	
	if ($tee == 'P') { // Olemassaolevaa tiliöintiä muutetaan, joten yliviivataan rivi ja annetaan perustettavaksi
		$query = "SELECT tilino, kustp, kohde, projekti, summa, vero, selite, tapvm
					FROM tiliointi
					WHERE tunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 0) {
			echo "".t("Tiliöintiä ei löydy")."! $query";
			exit;
		}
		$tiliointirow=mysql_fetch_array($result);
		$tili = $tiliointirow[0];
		$kustp = $tiliointirow[1];
		$kohde = $tiliointirow[2];
		$projekti = $tiliointirow[3];
		$summa = $tiliointirow[4];
		$vero = $tiliointirow[5];
		$selite = $tiliointirow[6];
		$tiliointipvm = $tiliointirow['tapvm'];
		$ok = 1;

// Etsitään kaikki tiliöintirivit, jotka kuuluvat tähän tiliöintiin ja lasketaan niiden summa

		$query = "SELECT sum(summa) FROM tiliointi
					WHERE aputunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]' and tiliointi.korjattu='' GROUP BY aputunnus";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 0) {
			$summarow=mysql_fetch_array($result);
			$summa += $summarow[0];
			$query = "UPDATE tiliointi SET korjattu = '$kukarow[kuka]', korjausaika = now()
						WHERE aputunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]' and tiliointi.korjattu=''";
			$result = mysql_query($query) or pupe_error($query);
		}

		$query = "UPDATE tiliointi
					SET korjattu = '$kukarow[kuka]', korjausaika = now()
					WHERE tunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$tee = "E"; // Näytetään miltä tosite nyt näyttää
	}

	if ($tee == 'U') { // Lisätään tiliöintirivi
		$summa = str_replace ( ",", ".", $summa);
		$selausnimi = 'tili'; // Minka niminen mahdollinen popup on?
		require "inc/tarkistatiliointi.inc";
		$tiliulos=$ulos;
		$ulos='';


		$tee = 'E';
		if ($ok != 1) {
			require "inc/teetiliointi.inc";
			if ($jaksota == 'on') {
				$tee = 'U';
				require "inc/jaksota.inc"; // Jos jotain jaksotetaan on $tee J
			}
		}
	}
	if (($tee == 'E') or ($tee=='F')) { // Tositeen näyttö muokkausta varten
		if ($tee == 'F') {
// Laskun tilausrivit
			require "inc/tilausrivit.inc";
			$tee = '';
		}
		else {
// Tositteen tiliöintirivit...
			require "inc/tiliointirivit.inc";
			$tee = "";
		}
// Tehdään nappula, jolla voidaan vaihtaa näkymäksi tilausrivit/tiliöintirivit
		if ($tee == 'F') {
			$ftee = 'E';
			$fnappula = 'tiliöinnit';
		}
		else {
			$ftee = 'F';
			$fnappula = 'tilausrivit';
		}
		
		echo "<a href = '$PHP_SELF?tee=$ftee&tunnus=$tunnus&laji=$laji&vv=$vv&kk=$kk&viivatut=on#$tunnus'>$fnappula</a>";
	}
	echo "</div>";
	
//Alaosan laskun kuva
	echo "<div id='alas' style='height:100px;width:800px;overflow:auto; float:none;'>";
	if ($tunnus != '') {
		if (strlen($trow['ebid']) > 0) {
			$ebid = $trow['ebid'];
			require "inc/ebid.inc";
			echo "<iframe src='$url' name='alaikkuna' width='100%' align='bottom' scrolling='auto'></iframe>";
		}
		else {
			echo "<font class='message'>".t("Paperilasku! Kuvaa ei ole saatavilla")."</font>";
		}
	}
	else {
		echo "<font class='message'> ".t("Laskua ei ole valittu")."</font>";
	}
	echo "</div>";
	require "inc/footer.inc";
?>
