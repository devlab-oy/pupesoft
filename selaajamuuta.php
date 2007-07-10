<?php
	require "inc/parametrit.inc";

	echo "
		<script>
		var DH = 0;var an = 0;var al = 0;var ai = 0;if (document.getElementById) {ai = 1; DH = 1;}else {if (document.all) {al = 1; DH = 1;} else { browserVersion = parseInt(navigator.appVersion); if ((navigator.appName.indexOf('Netscape') != -1) && (browserVersion == 4)) {an = 1; DH = 1;}}} function fd(oi, wS) {if (ai) return wS ? document.getElementById(oi).style:document.getElementById(oi); if (al) return wS ? document.all[oi].style: document.all[oi]; if (an) return document.layers[oi];}
		function pw() {return window.innerWidth != null? window.innerWidth: document.body.clientWidth != null? document.body.clientWidth:null;}
		function mouseX(evt) {if (evt.pageX) return evt.pageX; else if (evt.clientX)return evt.clientX + (document.documentElement.scrollLeft ?  document.documentElement.scrollLeft : document.body.scrollLeft); else return null;}
		function mouseY(evt) {if (evt.pageY) return evt.pageY; else if (evt.clientY)return evt.clientY + (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop); else return null;}
		function popUp(evt,oi) {if (DH) {var wp = pw(); ds = fd(oi,1); dm = fd(oi,0); st = ds.visibility; if (dm.offsetWidth) ew = dm.offsetWidth; else if (dm.clip.width) ew = dm.clip.width; if (st == \"visible\" || st == \"show\") { ds.visibility = \"hidden\"; } else {tv = mouseY(evt) + 20; lv = mouseX(evt) - (ew/4); if (lv < 2) lv = 2; else if (lv + ew > wp) lv -= ew/2; if (!an) {lv += 'px';tv += 'px';} ds.left = lv; ds.top = tv; ds.visibility = \"visible\";}}}
		</script>
	";
	
	echo "<font class='head'>".t("Tiliöintien muutos/selailu")."</font><hr>";

	if ((($tee == 'U') or ($tee == 'P') or ($tee == 'M') or ($tee == 'J')) and ($oikeurow['paivitys'] != 1)) {
		echo "<b>".t("Yritit päivittää vaikka simulla ei ole siihen oikeuksia")."</b>";
		exit;
	}
	
	if ($tunnus != 0) {
		$query = "SELECT ebid, nimi, concat_ws(' ', tapvm, mapvm) laskunpvm FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
		$result = mysql_query ($query) or die ("Kysely ei onnistu $query");
		if (mysql_num_rows($result) > 0) {
			$laskurow=mysql_fetch_array ($result);
			$laskunpvm = $laskurow['laskunpvm'];
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
	echo "<div id='ylos' style='height:50px;width:800px;overflow:auto;z-index:30;'>
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
	if ($vv < 2000) $vv += 2000;
	$lvv=$vv;
	$lkk=$kk;
	$lkk++;
	if ($lkk > 12) {
		$lkk='01';
		$lvv++;
	}
	
	
	$query = "	SELECT tunnus, nimi, $pvm, summa, comments
				FROM lasku use index (yhtio_tila_tapvm)
				WHERE yhtio = '$kukarow[yhtio]' and $pvm >= '$vv-$kk-01' and $pvm < '$lvv-$lkk-01' and $lajiv
				ORDER BY tapvm desc, summa desc";

	$result = mysql_query($query) or pupe_error($query);
	$loppudiv ='';
	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Haulla ei löytynyt yhtään laskua")."</font>";

	}
	else {
		echo "<div id='vasen' style='height:300px;width:330px;overflow:auto;float:left;z-index:20;'><table><tr>";
		for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
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
			for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
				if ($i==1) {
					if (strlen($trow[$i])==0) $trow[$i]="(tyhjä)";
					echo "$tyylia<a href name='$trow[tunnus]'><a href='$PHP_SELF?tee=E&tunnus=$trow[tunnus]&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut#$trow[tunnus]'>$trow[$i]</a></a>$tyylil";
				}
				else {
						
					echo "$tyylia$trow[$i]$tyylil";
				}
			}
			
			if ($trow['comments'] != '') {
				$loppudiv .= "<div id='".$trow['tunnus']."' style='position:absolute; z-index:1; visibility:hidden; width:500px; background:#555555; color:#FFFFFF; border: 1px solid; padding:5px; overflow:visible;'>";
				$loppudiv .= $trow["comments"]."<br></div>";
				echo "<td valign='top'><a class='menu' onmouseout=\"popUp(event,'".$trow['tunnus']."')\" onmouseover=\"popUp(event,'".$trow['tunnus']."')\"><img src='pics/lullacons/alert.png'></a></td>";
			}
			else 
				echo "<td></td>";
			
			echo "</tr>";
		}
	}
	echo "</tr></table></div>";


// Oikealle tiliöinnit
	echo "<div id='oikea' style='height:300px;width:750px;overflow:auto;float:none;z-index:10;'>";
	
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
	echo "<div id='alas' style='height:200px;width:1010px;overflow:auto; float:none;'>";
	if ($tunnus != '') {
		if (strlen($laskurow['ebid']) > 0) {
			$ebid = $laskurow['ebid'];
			require "inc/ebid.inc";
			echo "<iframe src='$url' name='alaikkuna' width='1000px' align='bottom' scrolling='auto'></iframe>";
		}
		else {
			//	Onko kuva tietokannassa?
			$query = "select * from liitetiedostot where yhtio='{$kukarow[yhtio]}' and liitos='lasku' and liitostunnus='{$laskurow["tunnus"]}'";
			$liiteres=mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($liiteres)>0) {
				while($liiterow=mysql_fetch_array($liiteres)) {
					echo "<a href='view.php?id={$liiterow["tunnus"]}'>{$liiterow["selite"]}</a><br>";
				}
			}
			else {
				echo "<font class='message'>".t("Paperilasku! Kuvaa ei ole saatavilla ($laskurow[ebid])")."</font>";
			}
		}
	}
	else {
		echo "<font class='message'> ".t("Laskua ei ole valittu")."</font>";
	}
	echo "</div>";
	echo $loppudiv;
	require "inc/footer.inc";
?>
