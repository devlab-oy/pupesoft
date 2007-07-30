<?php

require('inc/parametrit.inc');
// poimitaan kuluva päivä, raportin timestampille
$today = date("Y-m-d");
echo "<font class='head'>Siirto ulkoiseen kirjanpitoon</font><hr>";

if ($kausi == "") {
//Näytetään käyttöliittymä
	echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
		<table>
		<tr><td>Anna kausi</td><td><input type = 'text' name = 'kausi' size=8> Esim 2007-01</td></tr>
		<tr><td>Summaa tapahtumat</td><td><input type = 'checkbox' name = 'summataan' checked></td></tr>
		<tr><td></td><td><input type = 'submit' value = 'Valitse'></td></tr>
		</table></form>";
	$formi = 'valinta';
	$kentta = 'kausi';
	require "inc/footer.inc";
	exit;
}

function teetietue ($yhtio, $tosite, $summa, $ltunnus, $tapvm, $tilino, $kustp, $projekti, $ytunnus, $nimi, $selite) {
	//Kustannuspaikan koodien haku - start
	//$query = "SELECT nimi FROM kustannuspaikka WHERE yhtio = '$yhtio' and tunnus = '$kustp'";
	$query = "SELECT tarkenne FROM kustannuspaikka WHERE yhtio = '$yhtio' and tunnus = '$kustp'";
	$vresult = mysql_query($query)
				or die ("Kysely ei onnistu $query");
	$kustprow = mysql_fetch_array($vresult);

	//Projekti
	//$query = "SELECT nimi FROM kustannuspaikka WHERE yhtio = '$yhtio' and tunnus = '$projekti'";
	$query = "SELECT tarkenne FROM kustannuspaikka WHERE yhtio = '$yhtio' and tunnus = '$projekti'";
	$vresult = mysql_query($query)
				or die ("Kysely ei onnistu $query");
	$projprow = mysql_fetch_array($vresult);

	// Kustannuspaikan koodien haku - end
	$ulos = 'TKB';							//tietuetyyppi
	$ulos .= sprintf ('%-8s',  $tapvm);				//päivämäärä
	$ulos .= sprintf ('%-08d', $tosite); 				//tositelaji ja tositenumero
	$ulos .= sprintf ('%03d', '0'); 				//???? tositenumeron tarkenne 1
	$ulos .= sprintf ('%03d', '0'); 				//???? tositenumeron tarkenne 2
	$ulos .= sprintf ('%06d', $tilino);				//tili
	if ((int) $kustprow['tarkenne']==0) $kustprow['tarkenne'] = ""; //tsekataan ettei seurantakohteille  tule turhia etunollia
		$ulos .= sprintf ('%6.6s', $kustprow['tarkenne']);
	if ((int) $projprow['tarkenne']==0) $projprow['tarkenne'] = ""; //tsekataan ettei seurantakohteille  tule turhia etunollia
		$ulos .= sprintf ('%6.6s', $projprow['tarkenne']);
	$ulos .= sprintf ('%-10s', ' '); 				//???? projektilaji
	$ulos .= sprintf ('%04d', $row['jakso']);			//jakso
	if ($summa > 0) {
		$etu   = '+';
		$summa = sprintf ('%016d',  round(100 * $summa,2));
		$maara = sprintf ('%015d',  round(100 * $summa,2));
	}
	else {
		$etu = '-';
		$summa = sprintf ('%016d',  round(100 * $summa * -1,2));
		$maara = sprintf ('%015d',  round(100 * $summa * -1,2));
	}

	$ulos .= $etu;						//rahamäärän etumerkki
	$ulos .= $summa;					//rahamäärä
	$ulos .= $etu;						//määrän etumerkki
	$ulos .= $maara;					//määrä
	$ulos .= sprintf ('%-72.72s', $nimi . "/" . $selite);	//liikekumppanin nimi + tiliöinnin selite
//	$ulos .= sprintf ('%08d', '0'); 			//asiakasnumero
	$ulos .= sprintf ('%-8.8s', ''); 			//asiakasnumero
	$ulos .= sprintf ('%-2.2s', ' ');			//???? laskulaji
//	$ulos .= sprintf ('%06.6d', '0'); 			//laskun numero
	$ulos .= sprintf ('%-6.6s', ' '); 			//laskun numero
	$ulos .= sprintf ('%-6.6s', ' ');			//???? kustannuslaji
	$ulos .= sprintf ('%-8.8s', ' ');			//???? ryhmä3
	$ulos .= sprintf ('%-6.6s', ' ');			//???? ryhmä3 laji
	$ulos .= sprintf ('%-8.8s', ' ');			//???? ryhmä4
	$ulos .= sprintf ('%-6.6s', ' ');			//???? ryhmä4 laji
	$ulos .= '+';						//???? määrä kahden etumerkki
	$ulos .= sprintf ('%015d', '0');			//???? määrä 2
	$ulos .= '+';						//???? määrä kolmen etumerkki
	$ulos .= sprintf ('%015d', '0');			//???? määrä 3
	$ulos .= sprintf ('%-4s', ' ');				//???? yritysnumero
	$ulos .= sprintf ('%-20s', ' ');			//???? maksatuserätunnus
	$ulos .= sprintf ('%-3s', 'EUR');			//rahayksikön valuutta
	$palautus .= $ulos."\r\n";
//	echo "$palautus<br>";
	return $palautus;
}

function rivit($result, $laji, $yhtio, $summataan) {
	
	while ($row = mysql_fetch_array($result))
	{
		//echo "Käsittelen riviä<br>";
//		if (($laji == 41) and ($row['tilino'] == 2939)) $row['tilino'] = 29391; // splitataan ALVit alatileille
//		if (($laji == 30) and ($row['tilino'] == 2939)) $row['tilino'] = 29392; // splitataan ALVit alatileille

		if ($row["ltunnus"] != $vltunnus) {
			//echo "Uusi tosite<br>";
			$alaraja = $laji*1000000;
			$ylaraja = ($laji+1)*1000000;

			$query  = "	SELECT max(tosite)
						FROM tiliointi
						WHERE yhtio='$yhtio' and tosite > $alaraja and tosite < $ylaraja";
			$tresult = mysql_query($query) or pupe_error($query);
			$trow = mysql_fetch_array($tresult);

			if ($laji == 30) {
				$tosite = $laji.sprintf ('%06d', $row['laskunro']);
			}
			else {
				if ($trow[0] == 0)
					$trow[0] = $laji*1000000;
				$tosite = $trow[0]+1;
			}

			$vltunnus = $row["ltunnus"];
		}
		if ($summataan != '') {
			if (($summataan != '') and 
				(($sltunnus != $vltunnus) or ($stapvm != $row['tapvm']) or
				 ($stilino != $row['tilino']) or ($skustp != $row['kustp']) or
				 ($sprojekti != $row['projekti']))) {
				//echo "Summaus loppu!<br>";
				if ($summa != 0) {
					$palautus .= teetietue($yhtio, $stosite, $summa, $sltunnus, $stapvm, $stilino, $skustp, $sprojekti, $sytunnus, $snimi, $sselite); 
				}
				$stosite=$tosite;
				$summa=0;
				$sltunnus=$row['ltunnus'];
				$stapvm=$row['tapvm'];
				$stilino=$row['tilino'];
				$skustp=$row['kustp'];
				$sprojekti=$row['projekti'];
				$sytunnus=$row['ytunnus'];
				$snimi=$row['nimi'];
				$sselite=$row['selite'];
			}
		}
		else {
				$palautus .= teetietue($yhtio, $stosite, $summa, $sltunnus, $stapvm, $stilino, $skustp, $sprojekti, $sytunnus, $snimi, $sselite); 
				$stosite=$tosite;
				$summa=0;
				$sltunnus=$row['ltunnus'];
				$stapvm=$row['tapvm'];
				$stilino=$row['tilino'];
				$skustp=$row['kustp'];
				$sprojekti=$row['projekti'];
				$sytunnus=$row['ytunnus'];
				$snimi=$row['nimi'];
				$sselite=$row['selite'];
		}

		$summa += $row['summa'];
		$yhdistetty++;

		$query  = "	UPDATE tiliointi set tosite=$tosite
					WHERE tunnus=$row[tunnus]";
		$tresult = mysql_query($query) or pupe_error($query);
	}
	if ($summa != 0) {
		$palautus .= teetietue($yhtio, $stosite, $summa, $sltunnus, $stapvm, $stilino, $skustp, $sprojekti, $sytunnus, $snimi, $sselite);
	}
	return $palautus;
}




//Onko aikaisempia ei vietyjä rivejä?
//$query  = "	SELECT tapvm, tilino, summa, selite 
//			FROM tiliointi
//			WHERE yhtio='$kukarow[yhtio]' and tosite=0 and
//				left(tapvm,7)<'$kausi' and korjattu != '' and tapvm >= '$yhtiorow[tilikausi_alku]'";

$query  = "SELECT left(tapvm, 7) kausi, count(distinct(ltunnus)) kpl 
			FROM tiliointi
			WHERE yhtio='$kukarow[yhtio]' and tosite=0 and
				left(tapvm,7)<'$kausi' and korjattu = '' and tapvm >= '$yhtiorow[tilikausi_alku]'
			group by 1 ";

$result = mysql_query($query) or pupe_error($query);


if (mysql_num_rows($result)>0) {
	echo "<font class='error'>Nämä tiliöinnit ovat siirtämättä edellisiltä kausilta</font>";
	echo "<table><tr>";
	for ($i = 0; $i < mysql_num_fields($result); $i++) {
		echo "<th>" . mysql_field_name($result,$i)."</th>";
	}
	echo "</tr>";

	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		for ($i=0; $i<mysql_num_fields($result); $i++) {
			echo "<td>$trow[$i]</td>";
		}
		echo "</tr>";
	}
	echo "</tr></table><br><br>";
}

//Tarkistetaan aineisto tikon-mielessä
$tikonerr=0;
// mapvm=tapvm
$query  = "	SELECT tapvm, nimi, summa, tunnus
			FROM lasku
			WHERE yhtio='$kukarow[yhtio]' and mapvm=tapvm and mapvm!='0000-00-00'
			and ((left(lasku.tapvm,7)='$kausi') or (left(lasku.tapvm,7)='$kausi'))";

$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result)>0) {
	echo "<font class='error'>Näilla laskuilla laskunpvm ja maksupvm ovat samat. Tämä aiheuttaa ongelmia siirrossa.</font>";
	echo "<table><tr>";
	for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
		echo "<th>" . mysql_field_name($result,$i)."</th>";
	}
	echo "</tr>";

	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
			echo "<td>$trow[$i]</td>";
		}
		echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[tunnus]'>Korjaa</a></td>";
		echo "</tr>";
	}
	echo "</tr></table>";
	echo "<font class='error'>Nämä on korjattava ennenkuin siirto voidaan tehdä!</font><br><br>";
	$tikonerr=1;
}
//tapvm:n tilioinnit puuttuvat?
$query  = "	SELECT lasku.tapvm, nimi, lasku.summa, lasku.tunnus, count(*) kpl
			FROM lasku
			LEFT JOIN tiliointi ON lasku.yhtio=tiliointi.yhtio and
				lasku.tunnus=tiliointi.ltunnus and
				tiliointi.tapvm=lasku.tapvm and
				korjattu = ''
			WHERE lasku.yhtio='$kukarow[yhtio]'
			and ((left(lasku.tapvm,7)='$kausi') or (left(lasku.tapvm,7)='$kausi'))
			and tila != 'X' and lasku.tila != 'D'
			GROUP BY 1,2,3,4
			HAVING kpl < 2";

$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result)>0) {
	echo "<font class='error'>Näiltä laskuita puuttuvat kaikki laskupvm:n tiliöinnit. Se on virhe.</font>";
	echo "<table><tr>";
	for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
		echo "<th>" . mysql_field_name($result,$i)."</th>";
	}
	echo "</tr>";

	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
			echo "<td>$trow[$i]</td>";
		}
		echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[tunnus]'>Korjaa</a></td>";
		echo "</tr>";
	}
	echo "</tr></table>";
	echo "<font class='error'>Näma on korjattava ennenkuin siirto voidaan tehdä!</font><br><br>";
	$tikonerr=1;
}
//mapvm:n tilioinnit puuttuvat?
$query  = "	SELECT lasku.tapvm, nimi, lasku.summa, lasku.tunnus, count(*) kpl
			FROM lasku
			LEFT JOIN tiliointi ON lasku.yhtio=tiliointi.yhtio and
				lasku.tunnus=tiliointi.ltunnus and
				tiliointi.tapvm=lasku.mapvm and
				korjattu = ''
			WHERE lasku.yhtio='$kukarow[yhtio]'
			and ((left(lasku.tapvm,7)='$kausi') or (left(lasku.tapvm,7)='$kausi'))
			and mapvm != '0000-00-00'
			GROUP BY 1,2,3,4
			HAVING kpl < 2";

$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result)>0) {
	echo "<font class='error'>Näiltä laskuita puuttuvat kaikki maksupvm:n tiliöinnit. Se on virhe.</font>";
	echo "<table><tr>";
	for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
		echo "<th>" . mysql_field_name($result,$i)."</th>";
	}
	echo "</tr>";

	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
			echo "<td>$trow[$i]</td>";
		}
		echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[tunnus]'>Korjaa</a></td>";
		echo "</tr>";
	}
	echo "</tr></table>";
	echo "<font class='error'>Näma on korjattava ennenkuin siirto voidaan tehdä!</font>";
	$tikonerr=1;
}

if ($tikonerr!=0) {
	require ("inc/footer.inc");
	exit;
}


//tiedoston polku ja nimi
// ORIG $nimi = '/var/data/tikon_out/TIKON-' . $yhtio . "-" . date("ymd.His-s") . ".dat";
//$nimi = "/var/data/tikon_out/TIKON-" . $kukarow['yhtio'] . "-" . date("ymd.His-s") .".dat";

$nimi = "/var/data/tikon_out/$kukarow[yhtio]/TIKON-$kukarow[yhtio]-".date("ymd.His-s").".dat";

$hakemisto = dirname($nimi);
if (!is_dir($hakemisto)) {
	mkdir($hakemisto);
	if (is_dir($hakemisto))
		echo "<font class='message'>".t("Loin hakemiston"). "$hakemisto</font><br>";
	else {
		echo "<font class='error'>".t("Yritin luoda hakemistoa, mutta se ei onnistu. Ota yhteyttä järjestelmän ylläpitoon")." $hakemisto</font><br>";
		exit;
	}
}	

//$nimi = "/tmp/TIKON-$kukarow[yhtio]-".date("ymd.His-s").".txt";
//avataan tiedosto
$toot = fopen($nimi,"w+");

echo "Yrityksen $yhtiorow[nimi] kirjanpidolliset tapahtumat kaudella $kausi. ";
if ($summataan=='') echo "Tapahtumia ei summata."; else echo "Tapahtumat summataan.";
echo "<br><br>Raportti otettu $today.<br><br>";

//haetaan myyntisaamiset
$query  = "	SELECT date_format(tiliointi.tapvm, '%d%m%Y') tapvm,
			date_format(tiliointi.tapvm, '%y%m') jakso, tilino, kustp, projekti, tiliointi.summa
			summa, selite, ytunnus, ltunnus, mapvm, tiliointi.tunnus tunnus, lasku.laskunro laskunro, nimi
			FROM tiliointi, lasku
			WHERE tiliointi.yhtio='$kukarow[yhtio]' and tiliointi.yhtio=lasku.yhtio
			and lasku.tunnus=tiliointi.ltunnus and tosite='' and lasku.tila='L'
			and lasku.tapvm=tiliointi.tapvm and left(lasku.tapvm,7)='$kausi' and korjattu='' and lasku.tila != 'D'
			ORDER BY ltunnus, tiliointi.tapvm, tilino, kustp, projekti";

$result_ms = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result_ms) > 0) {
	fputs($toot, rivit($result_ms, 30, $kukarow["yhtio"], $summataan));
}
echo "Myyntisaamisia ".mysql_num_rows($result_ms)." kappaletta<br>";


//haetaan ostovelat
$query  = "	SELECT date_format(tiliointi.tapvm, '%d%m%Y') tapvm,
			date_format(tiliointi.tapvm, '%y%m') jakso, tilino, kustp, projekti, tiliointi.summa
			summa, selite, ytunnus, ltunnus, mapvm, tiliointi.tunnus tunnus, nimi
			FROM tiliointi, lasku
			WHERE tiliointi.yhtio='$kukarow[yhtio]' and tiliointi.yhtio=lasku.yhtio
			and lasku.tunnus=tiliointi.ltunnus and tosite=''
			and lasku.tila!='X' and lasku.tila!='L'
			and lasku.tapvm=tiliointi.tapvm and left(lasku.tapvm,7)='$kausi' and korjattu='' and lasku.tila != 'D'
			ORDER BY ltunnus, tiliointi.tapvm, tilino, kustp, projekti";

$result_ov = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result_ov) > 0) {
	fputs($toot, rivit($result_ov, 41, $kukarow["yhtio"], $summataan));
}
echo "Ostovelkoja, ".mysql_num_rows($result_ov)." kappaletta<br>";


//tehdään uusi kysely jossa yhdistetään suoritukset ja rahatapahtumat = TILIOTE
$query  = "	SELECT date_format(tiliointi.tapvm, '%d%m%Y') tapvm,
			date_format(tiliointi.tapvm, '%y%m') jakso, tilino, kustp, projekti,tiliointi.summa
			summa, selite, ytunnus, ltunnus, mapvm, tiliointi.tunnus tunnus
			FROM tiliointi, lasku
			WHERE tiliointi.yhtio='$kukarow[yhtio]' and tiliointi.yhtio=lasku.yhtio
			and lasku.tunnus=tiliointi.ltunnus and tosite='' and
			((lasku.tila!='X' and lasku.tapvm!=tiliointi.tapvm and left(tiliointi.tapvm,7)='$kausi') or
			(lasku.tila ='X' and left(tiliointi.tapvm,7)='$kausi')) and korjattu='' and lasku.tila != 'D'
			ORDER BY tiliointi.tapvm, ltunnus, tilino, kustp, projekti";

$result_mrt = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result_mrt) > 0) {
	fputs($toot, rivit($result_mrt, 11, $kukarow["yhtio"], $summataan));
}
echo "Tiliotteiden tiliöinnit,  ".mysql_num_rows($result_mrt)." tapahtumaa<br>";

fclose($toot);
echo "Done!<br><br>";


// be add-on: TULOSTETAAN KAIKKI KYSEISET TULOKSET NÄYTÖLLE
//---- tulostetaan myyntisaamiset näytölle ------
echo "<br><font class=head>$yhtiorow[nimi] myyntisaamiset, $kausi:</font><br><br>
	Raportti otettu $today.<br><br>";

//Tehdään uusi kysely, jossa muutetaan lajittelujärjestys
if ($summataan=='')
	$query  = "	SELECT date_format(tiliointi.tapvm, '%e.%c.%Y') tapvm, lasku.nimi,
			tiliointi.summa summa, tilino, k.tarkenne kustp, p.tarkenne proj,
			selite, lasku.laskunro laskunro, tosite, mapvm, tiliointi.tunnus tunnus, date_format(tiliointi.tapvm, '%y%m') jakso, ytunnus, ltunnus
			FROM tiliointi, lasku
			left join kustannuspaikka k on tiliointi.yhtio=k.yhtio and k.tyyppi = 'k' and kustp = k.tunnus
			left join kustannuspaikka p on tiliointi.yhtio=p.yhtio and p.tyyppi = 'p' and projekti = p.tunnus
			WHERE tiliointi.yhtio='$kukarow[yhtio]' and tiliointi.yhtio=lasku.yhtio
			and lasku.tunnus=tiliointi.ltunnus and lasku.tila='L'
			and lasku.tapvm=tiliointi.tapvm and left(lasku.tapvm,7)='$kausi' and korjattu='' and lasku.tila != 'D'
			ORDER BY tosite, tiliointi.tapvm, ltunnus, tilino, k.nimi, p.nimi";
else $query  = "SELECT date_format(tiliointi.tapvm, '%e.%c.%Y') tapvm, lasku.nimi,
			sum(tiliointi.summa) summa, tilino, k.tarkenne kustp, p.tarkenne proj,
			selite, lasku.laskunro laskunro, tosite, mapvm, tiliointi.tunnus tunnus, date_format(tiliointi.tapvm, '%y%m') jakso, ytunnus, ltunnus
			FROM tiliointi, lasku
			left join kustannuspaikka k on tiliointi.yhtio=k.yhtio and k.tyyppi = 'k' and kustp = k.tunnus
			left join kustannuspaikka p on tiliointi.yhtio=p.yhtio and p.tyyppi = 'p' and projekti = p.tunnus
			WHERE tiliointi.yhtio='$kukarow[yhtio]' and tiliointi.yhtio=lasku.yhtio
			and lasku.tunnus=tiliointi.ltunnus and lasku.tila='L'
			and lasku.tapvm=tiliointi.tapvm and left(lasku.tapvm,7)='$kausi' and korjattu='' and lasku.tila != 'D'
			GROUP BY tosite, tiliointi.tapvm, ltunnus, tilino, kustp, projekti
			ORDER BY tosite, tiliointi.tapvm, ltunnus, tilino, kustp, projekti";

$result_ms = mysql_query($query) or die ("$query epäonnistui!<br>".mysql_error());


//Tässä tehdään sarakeotsikot
echo "<table><tr>";
	for ($i = 0; $i < mysql_num_fields($result_ms)-4; $i++) {
	echo "<th>" . mysql_field_name($result_ms,$i)."</th>";
}
echo "</tr>";
//Käydään läpi kaikki laskurivit
while ($laskurow=mysql_fetch_array ($result_ms)) {
	echo "<tr>";
//Käydään läpi kaikki laskurivin tiedot
	for ($i=0; $i<mysql_num_fields($result_ms)-4; $i++) {
		echo "<td>$laskurow[$i]</td>";
		}
	echo "</tr>";
	}
echo "</table><br><br>";
//---- myyntisaamiset end -----------------------
//---- tulostetaan ostovelat näytölle -----------
echo "<br><font class=head>$yhtiorow[nimi] ostovelkojen tiliöinnit, $kausi:</font><br><br>
		Raportti otettu $today.<br><br>";
//Tehdään uusi kysely, jossa muutetaan lajittelujärjestys
if ($summataan=='')
	$query  = "	SELECT date_format(tiliointi.tapvm, '%e.%c.%Y') tapvm, lasku.nimi,
			tiliointi.summa summa, tilino, k.tarkenne kustp, p.tarkenne proj,
			selite,  tosite, mapvm, tiliointi.tunnus tunnus, ytunnus, date_format(tiliointi.tapvm, '%y%m') jakso, ltunnus
			FROM tiliointi, lasku
			left join kustannuspaikka k on tiliointi.yhtio=k.yhtio and k.tyyppi = 'k' and kustp = k.tunnus
			left join kustannuspaikka p on tiliointi.yhtio=p.yhtio and p.tyyppi = 'p' and projekti = p.tunnus
			WHERE tiliointi.yhtio='$kukarow[yhtio]' and tiliointi.yhtio=lasku.yhtio
			and lasku.tunnus=tiliointi.ltunnus and lasku.tila!='X' and lasku.tila!='L'
			and lasku.tapvm=tiliointi.tapvm and left(lasku.tapvm,7)='$kausi' and korjattu='' and lasku.tila != 'D'
			ORDER BY tosite, tiliointi.tapvm, tilino, k.tarkenne, p.tarkenne";
else $query  = "SELECT date_format(tiliointi.tapvm, '%e.%c.%Y') tapvm, lasku.nimi,
			sum(tiliointi.summa) summa, tilino, k.tarkenne kustp, p.tarkenne proj,
			selite,  tosite, mapvm, tiliointi.tunnus tunnus, ytunnus, date_format(tiliointi.tapvm, '%y%m') jakso, ltunnus
			FROM tiliointi, lasku
			left join kustannuspaikka k on tiliointi.yhtio=k.yhtio and k.tyyppi = 'k' and kustp = k.tunnus
			left join kustannuspaikka p on tiliointi.yhtio=p.yhtio and p.tyyppi = 'p' and projekti = p.tunnus
			WHERE tiliointi.yhtio='$kukarow[yhtio]' and tiliointi.yhtio=lasku.yhtio
			and lasku.tunnus=tiliointi.ltunnus and lasku.tila!='X' and lasku.tila!='L'
			and lasku.tapvm=tiliointi.tapvm and left(lasku.tapvm,7)='$kausi' and korjattu='' and lasku.tila != 'D'
			GROUP BY tosite, tiliointi.tapvm, tilino, k.tarkenne, p.tarkenne
			ORDER BY tosite, tiliointi.tapvm, tilino, k.tarkenne, p.tarkenne";
$result_ov = mysql_query($query) or pupe_error($query);
//Tässä tehdään sarakeotsikot
echo "<table><tr>";
for ($i =0; $i < mysql_num_fields($result_ov)-5; $i++) {
	echo "<th>" . mysql_field_name($result_ov,$i)."</th>";
	}
echo "</tr>";
//Käydään läpi kaikki laskurivit
while ($laskurow=mysql_fetch_array ($result_ov)) {
	echo "<tr>";
//Käydään läpi kaikki laskurivin tiedot
	for ($i=0; $i<mysql_num_fields($result_ov)-5; $i++) {
		echo "<td>$laskurow[$i]</td>";
	}

	echo "</tr>";
}
echo "</table><br><br>";
//--- ostovelat end -----------------------------
//---- tulostetaan rahatapahtumat näytölle ------
echo "<br><font class=head>$yhtiorow[nimi] tiliotteen tiliöinnit, $kausi:</font><br><br>
		Raportti otettu $today.<br><br>";
//Tehdään uusi kysely, jossa yhdistetään suoritukset ja muut rahatapahtumat sekä muutetaan lajittelujärjestys
if ($summataan=='')
	$query  = "	SELECT date_format(tiliointi.tapvm, '%e.%c.%Y') tapvm, lasku.nimi,
			tiliointi.summa summa, tilino,
			selite, tosite, date_format(tiliointi.tapvm, '%y%m') jakso, k.tarkenne kustp, p.tarkenne proj, ltunnus, ytunnus, mapvm, tiliointi.tunnus tunnus
			FROM tiliointi, lasku
			left join kustannuspaikka k on tiliointi.yhtio=k.yhtio and k.tyyppi = 'k' and kustp = k.tunnus
			left join kustannuspaikka p on tiliointi.yhtio=p.yhtio and p.tyyppi = 'p' and projekti = p.tunnus
			WHERE tiliointi.yhtio='$kukarow[yhtio]' and tiliointi.yhtio=lasku.yhtio
			and lasku.tunnus=tiliointi.ltunnus and
			((lasku.tila!='X' and lasku.tapvm!=tiliointi.tapvm and left(tiliointi.tapvm,7)='$kausi') or
			(lasku.tila ='X' and left(tiliointi.tapvm,7)='$kausi')) and korjattu='' and tila != 'D'
			ORDER BY tiliointi.tapvm, tosite, tilino, k.tarkenne, p.tarkenne";
else $query  = "	SELECT date_format(tiliointi.tapvm, '%e.%c.%Y') tapvm, lasku.nimi,
			sum(tiliointi.summa) summa, tilino,
			selite, tosite, date_format(tiliointi.tapvm, '%y%m') jakso, k.tarkenne kustp, p.tarkenne proj, ltunnus, ytunnus, mapvm, tiliointi.tunnus tunnus
			FROM tiliointi, lasku
			left join kustannuspaikka k on tiliointi.yhtio=k.yhtio and k.tyyppi = 'k' and kustp = k.tunnus
			left join kustannuspaikka p on tiliointi.yhtio=p.yhtio and p.tyyppi = 'p' and projekti = p.tunnus
			WHERE tiliointi.yhtio='$kukarow[yhtio]' and tiliointi.yhtio=lasku.yhtio
			and lasku.tunnus=tiliointi.ltunnus and
			((lasku.tila!='X' and lasku.tapvm!=tiliointi.tapvm and left(tiliointi.tapvm,7)='$kausi') or
			(lasku.tila ='X' and left(tiliointi.tapvm,7)='$kausi')) and korjattu='' and tila != 'D'
			GROUP BY tiliointi.tapvm, tosite, tilino, k.tarkenne, p.tarkenne
			ORDER BY tiliointi.tapvm, tosite, tilino, k.tarkenne, p.tarkenne";
$result_rt = mysql_query($query) or die ("$query epäonnistui!<br>".mysql_error());
//Tässä tehdään sarakeotsikot
echo "<table><tr>";
for ($i = 0; $i < mysql_num_fields($result_rt)-7; $i++) {
	echo "<th>" . mysql_field_name($result_rt,$i)."</th>";
}
echo "</tr>";
//Käydään läpi kaikki laskurivit
while ($laskurow=mysql_fetch_array ($result_rt)) {
	echo "<tr>";
	//Käydään läpi kaikki laskurivin tiedot
	for ($i=0; $i<mysql_num_fields($result_rt)-7; $i++) {
		echo "<td>$laskurow[$i]</td>";
	}
	echo "</tr>";
}
echo "</table><br><br>";
//---- rahatapahtumat end -----------------------
//end be add-on: TULOSTETAAN KAIKKI KYSEISET TULOKSET NÄYTÖLLE


?>

