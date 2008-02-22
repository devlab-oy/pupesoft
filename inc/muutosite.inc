<?php

require_once ("inc/tilinumero.inc");

function erottele ($pvm) {
	$paluu['pp'] = substr($pvm,-2);
	$paluu['kk'] = substr($pvm,5,2);
	$paluu['vv'] = substr($pvm,0,4);
	
	return $paluu;
}

function kopioitiliointi ($tunnus, $viivaaja) {
	//Tehd‰‰n kopio tiliˆinnist‰ $tunnus
	//Jos $viivaajassa on jotain tehd‰‰n uusi vienti yliviivattuna
	global $kukarow;
	
	$query = "SELECT * FROM tiliointi WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($result) != 1) {
		echo "Tiliˆintirivi kateissa Systeemivirhe!";
		exit;
	}
	
	$tiliointirow = mysql_fetch_array ($result);
	$query = "INSERT into tiliointi set ";
	
	for ($i=0; $i<mysql_num_fields($result); $i++) {
		if (mysql_field_name($result, $i) != 'tunnus') {
			if ($viivaaja != '') {
				if (mysql_field_name($result, $i) == 'korjattu') {
					$query .= "korjattu ='".$viivaaja."',";
				}
				elseif (mysql_field_name($result, $i) == 'korjausaika') {
					$query .= "korjausaika = now(),";
				}
				else
					$query .= mysql_field_name($result,$i) . "='" . $tiliointirow[$i] . "',";
			}
			else
				$query .= mysql_field_name($result,$i) . "='" . $tiliointirow[$i] . "',";
		}
	}

	$query=substr($query,0,-1);
	$result = mysql_query($query) or pupe_error($query);
}

$query = "	SELECT *
			FROM lasku
			WHERE tunnus = '$tunnus'";
$result = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($result) != 1) {
	echo t("Tositetta")." $tunnus ".t("ei lˆytynytk‰‰n")."!";
	exit;
}
$trow = mysql_fetch_array($result);

//Poistetaan suoraveloitus
if ($tila == 'A') {	
	$query = "	UPDATE lasku SET 
				suoraveloitus = '',
				tila = if(hyvak1 != '', 'H', 'M'),
				hyvaksyja_nyt = hyvak1,
				h1time = '',
				h2time = '',
				h3time = '',
				h4time = '',
				h5time = ''
				WHERE tunnus = '$tunnus' and 
				yhtio = '$kukarow[yhtio]' and 
				suoraveloitus != '' and
				maksu_tili = ''";
	$result = mysql_query($query) or pupe_error($query);
	$tee = 'E';
	$tila = '';

	// haetaan laskun tiedot uudestaan
	$query = "	SELECT *
				FROM lasku
				WHERE tunnus = '$tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 1) {
		echo t("Tositetta")." $tunnus ".t("ei lˆytynytk‰‰n")."!";
		exit;
	}

	$trow = mysql_fetch_array($result);
}

// Tasolla 1 ei saa muuttaa tapvm_‰‰ tai maksutili‰
if ($kukarow['taso'] != 1) {

	// Laskun maksutilintarkastus
	if ($tila == 'T') {
		$pankkitili = $tili;
		require ("inc/pankkitilinoikeellisuus.php");
		if ($pankkitili == '') {
			echo "<font class='error'>".t("Pankkitili on virheellinen")."</font><br>";
			$tee='E';
			$tila='';
		}
		else {
			$tili=$pankkitili;
		}
	}

	// Jaaha p‰ivit‰mme maksutilin!
	if ($tila == 'T') {
		$query = "SELECT comments, tilinumero FROM lasku
				WHERE hyvaksyja_nyt = '$kukarow[kuka]' and yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) !=1) {
			echo t('lasku kateissa') . "$tunnus</font>";
			exit;
		}

		$trow=mysql_fetch_array ($result);

		$kommentti = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ". sprintf(t('Laskun maksutili muutettiin. Vanha oli %s.'), tilinumero_print($trow['tilinumero'])) ."<br>" . $trow['comments'];

		//P‰ivitet‰‰n lasku
		$query = "UPDATE lasku set tilinumero='$tili',
						comments = '$kommentti' WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$tee='E';
		$tila='X';
	}

	// Laskun maksutilintarkastus (ulkomaat)
	if ($tila == 'TU') {
		if (($ultilno == '') or ($swift == '')) {
			echo "<font class='error'>".t("IBAN & SWIFT on annettava")."</font><br>";
			$tee='E';
			$tila='';
		}
		$ultilno = strtoupper($ultilno);
		$swift = strtoupper($swift);
	}

	// Jaaha p‰ivit‰mme maksutilin! (ulkomaat)
	if ($tila == 'TU') {
		$query = "SELECT comments, ultilno, swift FROM lasku
				WHERE hyvaksyja_nyt = '$kukarow[kuka]' and yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) !=1) {
			echo t('lasku kateissa') . "$tunnus</font>";
			exit;
		}

		$trow=mysql_fetch_array ($result);

		$kommentti = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ". sprintf(t('Laskun maksutili muutettiin. Vanha oli %s %s.'), $trow['ultilno'], $trow['swift']) ."<br>" . $trow['comments'];

		//P‰ivitet‰‰n lasku
		$query = "UPDATE lasku set ultilno='$ultilno', swift='$swift',
						comments = '$kommentti' WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$tee='E';
		$tila='X';
	}
	//T‰‰ll‰ tarkistetaan pvm
	if ($tila == 'P') {
		//	$kk = substr($trow[1],5,2);
		//	$vv = substr($trow[1],0,4);

		if ($vv < 1000) $vv += 2000;
		$val = checkdate($kk, $pp, $vv);

		if (!$val) {
			echo "<font class='error'>".t("Virheellinen tapahtumapvm")." $vv-$kk-$pp</font><br>";
			$tee = 'E';
			$tila = '';
		}

		$pvm = date("Y-m-d", mktime (0,0,0,$kk,$pp,$vv));
		
		if ($pvm < $yhtiorow['tilikausi_alku']) {
			echo "<font class='error'>".t("Tapahtumapvm ei ole kuluvalla tilikaudella")." $pvm</font><br>";
			$tee = 'E';
			$tila = '';
		}

		if ($trow['tapvm'] < $yhtiorow['tilikausi_alku']) {
			echo "<font class='error'>".t("Lasku ei ole kuluvalla tilikaudella")." $trow[tapvm]</font><br>";
			$tee = 'E';
			$tila = '';
		}
	}

	//T‰‰ll‰ p‰ivitet‰‰n muun tositteen pvm
	if ($tila == 'P') {

		$query = "UPDATE lasku SET tapvm = '$vv-$kk-$pp' WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]' and tapvm = '$trow[tapvm]'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "LOCK table tiliointi WRITE";
		$result = mysql_query($query) or pupe_error($query);
		
		$query = "SELECT tunnus FROM tiliointi WHERE ltunnus = '$tunnus' and yhtio = '$kukarow[yhtio]' and tapvm = '$trow[tapvm]' and korjattu=''";
		$result = mysql_query($query) or pupe_error($query);
		
		while ($xrow=mysql_fetch_array ($result)) {
			kopioitiliointi($xrow['tunnus'], $kukarow['kuka']);
		}
		
		$query = "	UPDATE tiliointi SET tapvm = '$vv-$kk-$pp', laadittu=now(), laatija='$kukarow[kuka]'
					WHERE ltunnus = '$tunnus' and yhtio = '$kukarow[yhtio]' and tapvm = '$trow[tapvm]' and korjattu=''";
		$result = mysql_query($query) or pupe_error($query);
		
		$query = "UNLOCK table";
		$result = mysql_query($query) or pupe_error($query);

		$tee = 'E';
		$tila = 'X';
	}
}

// T‰‰ll‰ tarkistetaan laskun uusi er‰pvm
if ($tila == 'R') {
	if ($vv < 1000) $vv += 2000;
	$val = checkdate($kk, $pp, $vv);
	if (!$val) {
		echo "<font class='error'>".t("Virheellinen er‰pvm")." $vv-$kk-$pp</font><br>";
		$tee = 'E';
		$tila = '';
	}
	else {
		$pvm = date("Y-m-d", mktime (0,0,0,$kk,$pp,$vv));
		if ($pvm < $trow['erapcm']) {
			echo "<font class='error'>".t("Er‰pvm on ennen laskunp‰iv‰yst‰")." $pvm, $trow[tapvm]</font><br>";
			$tee = 'E';
			$tila = '';
		}
	}
	if ($trow['mapvm'] != '0000-00-00') {
		echo "<font class='error'>".t("Lasku on  maksettu!")." $trow[mapvm]</font><br>";
		$tee = 'E';
		$tila = '';
	}
}

//T‰‰ll‰ p‰ivitet‰‰n laskun uusi er‰pvm
if ($tila == 'R') {

	$rlisa='';

	// Vanha er‰pvm on myˆs oletusmaksupvm, joten sekin on muutettava
	if ($trow['olmapvm'] == $trow['erpcm']) {
		$rlisa = ", olmapvm = '$pvm'";
	}

	$query = "UPDATE lasku SET erpcm = '$pvm' $rlisa WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";

	$result = mysql_query($query) or pupe_error($query);
	$tee = 'E';
	$tila = 'X';
}

//T‰‰ll‰ p‰ivitet‰‰n maksuehto
if ($tila == 'maksuehto') {
	$query = "UPDATE lasku SET maksuehto = '$maksuehto' WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);
	$tee = 'E';
	$tila = 'X';
}

//T‰‰ll‰ tarkistetaan laskun uusi kassa-alepvm
if ($tila == 'B') {

	if ($vv < 1000) $vv += 2000;
	$val = checkdate($kk, $pp, $vv);

	if (!$val) {
		echo "<font class='error'>".t("Virheellinen kassa-alepvm")." $vv-$kk-$pp</font><br>";
		$tee = 'E';
		$tila = '';
	}
	else {
		$pvm = date("Y-m-d", mktime (0,0,0,$kk,$pp,$vv));
		if ($pvm < $trow['tapvm']) {
			echo "<font class='error'>".t("Kassa-alepvm on ennen laskunp‰iv‰yst‰")."</font><br>";
			$tee = 'E';
			$tila = '';
		}
		if ($pvm > $trow['erpcm']) {
			echo "<font class='error'>".t("Kassa-alepvm on er‰pvmn j‰lkeen")."</font><br>";
			$tee = 'E';
			$tila = '';
		}
	}

	if ($trow['mapvm'] != '0000-00-00') {
		echo "<font class='error'>".t("Lasku on  maksettu!")." $trow[mapvm]</font><br>";
		$tee = 'E';
		$tila = '';
	}
}

//T‰‰ll‰ p‰ivitet‰‰n laskun uusi kassa-alepvm
if ($tila == 'B') {
	$query = "UPDATE lasku SET kapvm = '$pvm' WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	$query = "UPDATE lasku SET olmapvm=if(kapvm<now(),if(kapvm='0000-00-00',erpcm,kapvm),erpcm) WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	$tee = 'E';
	$tila = 'X';
}

//T‰‰ll‰ tarkistetaan kapvm ja muutenkin
if ($tila == 'C') {

	if ($vv < 1000) $vv += 2000;
	$val = checkdate($kk, $pp, $vv);

	if (!$val) {
		echo "<font class='error'>".t("Virheellinen kassapvm")." $vv-$kk-$pp</font><br>";
		$tee = 'E';
		$tila = '';
	}

	//Poistetaan p‰iv‰ys
	if ($summa == 0) {
		$vv='0000';
		$kk='00';
		$pp='00';
	}

	if ($trow['mapvm'] != '0000-00-00') {
		echo "<font class='error'>".t("Lasku on  maksettu!")." $trow[mapvm]</font><br>";
		$tee = 'E';
		$tila = '';
	}
}

//T‰‰ll‰ p‰ivitet‰‰n laskun uusi kassa-ale ja kapvm
if ($tila == 'C') {
	// Pilkut pisteiksi
	$summa = str_replace ( ",", ".", $summa);
	$summa = $summa * 1;

	// P‰ivitet‰‰n lasku
	$query = "UPDATE lasku SET kasumma = '$summa', kapvm='$vv-$kk-$pp' WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
    $result = mysql_query($query) or pupe_error($query);

	//P‰ivitet‰‰n kenties oletusmaksup‰iv‰
 	$query = "UPDATE lasku SET olmapvm=if(kapvm<now(), erpcm, kapvm) WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
    $result = mysql_query($query) or pupe_error($query);
	$tee = 'E';
	$tila = 'X';
}

//T‰‰ll‰ tarkistetaan laskun uusi summa
if ($tila == 'Q') {

	if ($trow['tapvm'] < $yhtiorow['tilikausi_alku']) {
		echo "<font class='error'>".t("Tapahtuma ei ole kuluvalla tilikaudella")." $trow[tapvm]</font><br>";
		$tee = 'E';
		$tila = '';
	}

	if ($trow['mapvm'] != '0000-00-00' and $trow['suoraveloitus'] == "") {
		echo "<font class='error'>".t("Lasku on maksettu. Summaa ei voi en‰‰ muuttaa")." $trow[mapvm]</font><br>";
		$tee = 'E';
		$tila = '';
	}
}

//T‰‰ll‰ p‰ivitet‰‰n laskun uusi summa
if ($tila == 'Q') {

	// Pilkut pisteiksi
	$summa = str_replace ( ",", ".", $summa);
	$summa = $summa * 1;

	// P‰ivitet‰‰n ostovelkatapahtuma siis euroina!
	$query = "LOCK table tiliointi WRITE";
	$result = mysql_query($query) or pupe_error($query);

	$query = "SELECT tunnus FROM tiliointi WHERE (tilino = '$yhtiorow[ostovelat]' or tilino='$yhtiorow[konserniostovelat]') and ltunnus = '$tunnus' and yhtio = '$kukarow[yhtio]' and korjattu=''";
	$result = mysql_query($query) or pupe_error($query);

	while ($xrow=mysql_fetch_array ($result)) {
		kopioitiliointi($xrow['tunnus'], $kukarow['kuka']);
	}

	$query = "	UPDATE tiliointi SET
				summa = round(-1 * $summa * $trow[vienti_kurssi],2),
				laadittu = now(),
				laatija='$kukarow[kuka]'
				WHERE (tilino = '$yhtiorow[ostovelat]' or tilino='$yhtiorow[konserniostovelat]') and
				ltunnus = '$tunnus' and
				yhtio = '$kukarow[yhtio]' and
				korjattu=''";
    $result = mysql_query($query) or pupe_error($query);

	$query = "UNLOCK table";
    $result = mysql_query($query) or pupe_error($query);

    // P‰ivitet‰‰n lasku
	$query = "	UPDATE lasku SET
				summa = $summa
				WHERE tunnus = '$tunnus' and
				yhtio = '$kukarow[yhtio]'";
    $result = mysql_query($query) or pupe_error($query);

	$tee = 'E';
	$tila = 'X';
}

//T‰‰ll‰ tarkistetaan laskun uusi maksupvm
if ($tila == 'S') {
	//	$kk = substr($trow[7],5,2);
	//	$vv = substr($trow[7],0,4);

	if ($vv < 1000) $vv += 2000;
	$val = checkdate($kk, $pp, $vv);

	if (!$val) {
			echo "<font class='error'>".t("Virheellinen maksupvm")." $vv-$kk-$pp</font><br>";
			$tee = 'E';
			$tila = '';
	}
	else {
			$pvm = date("Y-m-d", mktime (0,0,0,$kk,$pp,$vv));
			if ($pvm < $trow['tapvm']) {
					echo "<font class='error'>".t("Maksupvm on ennen laskunp‰iv‰yst‰")." $pvm, $trow[tapvm]</font><br>";
					$tee = 'E';
					$tila = '';
			}
			if ($pvm < $yhtiorow['tilikausi_alku']) {
				echo "<font class='error'>".t("Maksupvm ei ole kuluvalla tilikaudella")." $pvm, $trow[tapvm]</font><br>";
					$tee = 'E';
					$tila = '';
			}
	}

	if ($trow['mapvm'] == '0000-00-00') {
		echo "<font class='error'>".t("Laskua ei ole maksettu!")."</font><br>";
		$tee = 'E';
		$tila = '';
	}
}

//T‰‰ll‰ p‰ivitet‰‰n laskun uusi maksupvm
if ($tila == 'S') {

	$query = "	UPDATE lasku SET
				mapvm = '$pvm'
				WHERE tunnus = '$tunnus' and
				yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	// Jos noi oli sama ei tehd‰ mit‰‰n
	if ($trow['mapvm'] != $trow['tapvm']) {

		$query = "LOCK table tiliointi WRITE";
		$result = mysql_query($query) or pupe_error($query);

		$query = "SELECT tunnus FROM tiliointi WHERE ltunnus = '$tunnus' and yhtio = '$kukarow[yhtio]' and tapvm = '$trow[mapvm]' and korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);

		while ($xrow=mysql_fetch_array ($result)) {
			kopioitiliointi($xrow['tunnus'], $kukarow['kuka']);
		}

		$query = "UPDATE tiliointi SET tapvm = '$pvm', laadittu=now(), laatija='$kukarow[kuka]' WHERE ltunnus = '$tunnus' and yhtio = '$kukarow[yhtio]' and tapvm = '$trow[mapvm]' and korjattu=''";
		$result = mysql_query($query) or pupe_error($query);

		$query = "UNLOCK table";
    	$result = mysql_query($query) or pupe_error($query);
   	}
	else {
		echo "<font class='message'>".t("En siirt‰nyt muita tiliˆintej‰, koska tapahtuma- ja maksupvmt olivat samat")."<br>".t("Siirr‰ nyt suoritustapahtumat k‰sin")."</font>";

		$query = "LOCK table tiliointi WRITE";
		$result = mysql_query($query) or pupe_error($query);

		// Ei myyntitilaus
		if ($trow['tila'] != 'U') {

			$query = "	SELECT tunnus
						FROM tiliointi
						WHERE ltunnus = '$tunnus' and
						yhtio = '$kukarow[yhtio]' and
						(tilino = '$yhtiorow[ostovelat]' or tilino='$yhtiorow[konserniostovelat]') and
						abs(round($trow[summa] * $trow[vienti_kurssi],2) - summa) <= 0.01 and
						korjattu = ''";
			$result = mysql_query($query) or pupe_error($query);

			while ($xrow=mysql_fetch_array ($result)) {
				kopioitiliointi($xrow['tunnus'], $kukarow['kuka']);
			}

			$query = "	UPDATE tiliointi SET
						tapvm = '$pvm',
						laadittu = now(),
						laatija = '$kukarow[kuka]'
						WHERE ltunnus = '$tunnus' and
						yhtio = '$kukarow[yhtio]' and
						(tilino = '$yhtiorow[ostovelat]' or tilino='$yhtiorow[konserniostovelat]') and
						abs(round($trow[summa] * $trow[vienti_kurssi],2) - summa) <= 0.01 and
						korjattu = ''";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			$query = "	SELECT tunnus
						FROM tiliointi
						WHERE ltunnus = '$tunnus' and
						yhtio = '$kukarow[yhtio]' and
						tilino in ('$yhtiorow[myyntisaamiset]', '$yhtiorow[konsernimyyntisaamiset]') and
						abs($trow[summa] + summa) <= 0.01 and
						korjattu = ''";
			$result = mysql_query($query) or pupe_error($query);

			while ($xrow=mysql_fetch_array ($result)) {
				kopioitiliointi($xrow['tunnus'], $kukarow['kuka']);
			}

			$query = "	UPDATE tiliointi SET
						tapvm = '$pvm',
						laadittu = now(),
						laatija = '$kukarow[kuka]'
						WHERE ltunnus = '$tunnus' and
						yhtio = '$kukarow[yhtio]' and
						tilino in ('$yhtiorow[myyntisaamiset]', '$yhtiorow[konsernimyyntisaamiset]') and
						abs($trow[summa] + summa) <= 0.01 and
						korjattu = ''";
			$result = mysql_query($query) or pupe_error($query);
		}
		$query = "UNLOCK table";
    	$result = mysql_query($query) or pupe_error($query);
	}
	$tee = 'E';
	$tila = 'X';
}

//Muutetaan laskun tila
if ($tila == 'W') {
	if ($trow['tapvm'] < $yhtiorow['tilikausi_alku']) {
		echo "<font class='error'>".t("Tapahtuma ei ole kuluvalla tilikaudella")." $trow[tapvm]</font><br>";
		$tee = 'E';
		$tila = '';
	}

	$query = "select laskunro from lasku where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus='$trow[tunnus]'";
	$keikres = mysql_query($query) or pupe_error($query);

	if (!(($trow['tila'] == 'H' or $trow['tila'] == 'M' or $trow['tila'] == 'P' or $trow['tila'] == 'Q' or $trow['tila'] == 'Y') and mysql_num_rows($keikres) == 0 and $trow['tapvm'] >= $yhtiorow['tilikausi_alku'])) {
		echo "<font class='error'>".t("Laskun tila tai tapvm on muuttunut tai se on liitetty keikkaan")."</font><br>";
		$tee = 'E';
		$tila = '';
	}
}

//Muutetaan laskun tila
if ($tila == 'W') {

	//Poistetaan kirjanpidosta mahdoliset varastoviennit
	$query = "	UPDATE tiliointi
				SET korjattu = '$kukarow[kuka]',
				korjausaika = now()
				WHERE ltunnus = '$tunnus' and
				yhtio ='$kukarow[yhtio]' and
				korjattu = '' and
				(tilino = '$yhtiorow[varasto]' or tilino = '$yhtiorow[varastonmuutos]' or
				tilino = '$yhtiorow[matkalla_olevat]' or tilino = '$yhtiorow[raaka_ainevarasto]' or
				tilino = '$yhtiorow[raaka_ainevarastonmuutos]')";
	$result = mysql_query($query) or pupe_error($query);

	$query = "	UPDATE lasku
				SET vienti = '$vienti'
				WHERE tunnus = '$tunnus' AND
				yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	//Tehd‰‰n varastotiliˆinnit
	if ($vienti != 'A' and $vienti != 'D' and $vienti != 'G' and $vienti != '') {

		$query = "	SELECT kustp, kohde, projekti, summa 
					from tiliointi
					WHERE yhtio		= '$kukarow[yhtio]'
					and ltunnus		= '$tunnus'
					and tapvm		= '$trow[tapvm]'
					and tilino 	   != '$yhtiorow[alv]'
					and tilino 	   != '$yhtiorow[ostovelat]'
					and tilino 	   != '$yhtiorow[konserniostovelat]'
					and korjattu	= '' 
					group by 1,2,3";
		$result = mysql_query($query) or pupe_error($query);

		while ($tiliointirow = mysql_fetch_array ($result)) {

			// jos raaka-ainevarastoon
			if ($vienti == "J" or $vienti == "K" or $vienti == "L") {
				$vientitili = $yhtiorow["raaka_ainevarastonmuutos"];
			}
			else {
				$vientitili = $yhtiorow["varastonmuutos"];
			}

			$query = "	INSERT into tiliointi set
						yhtio 		= '$kukarow[yhtio]',
						ltunnus 	= '$tunnus',
						tilino 		= '$vientitili',
						kustp 		= '$tiliointirow[kustp]',
						kohde 		= '$tiliointirow[kohde]',
						projekti 	= '$tiliointirow[projekti]',
						tapvm 		= '$trow[tapvm]',
						summa 		= $tiliointirow[summa] * -1,
						vero 		= '',
						lukko 		= '',
						tosite 		= '$tiliointirow[tosite]',
						laatija 	= '$kukarow[kuka]',
						laadittu 	= now()";
			$xresult = mysql_query($query) or pupe_error($query);

		}

		// jos rahti/tai huolintalasku niin varastoon muuten matkalle
		if ($vienti == "B" or $vienti == "E" or $vienti == "H") {
			$vientitili = $yhtiorow["varasto"];
		}
		else {
			$vientitili = $yhtiorow["matkalla_olevat"];
		}

		//Haetaan kululaskun kaikki verotiliˆinnit jotta voidaan tallentaa myˆs veroton summa
		$query = "	SELECT sum(summa) summa
					from tiliointi
					where yhtio		= '$kukarow[yhtio]'
					and ltunnus		= '$tunnus'
					and tilino  	= '$yhtiorow[alv]'
					and korjattu 	= ''";
		$alvires = mysql_query($query) or pupe_error($query);
		$alvirow = mysql_fetch_array($alvires);
				
		//Ostoreskontralaskun veroton arvo
		$vientisumma = round($trow["summa"]*$trow["vienti_kurssi"],2) - $alvirow["summa"];

		$query = "	INSERT into tiliointi set
					yhtio 		= '$kukarow[yhtio]',
					ltunnus 	= '$tunnus',
					tilino 		= '$vientitili',
					kustp 		= '',
					tapvm 		= '$trow[tapvm]',
					summa 		= $vientisumma,
					vero 		= '',
					lukko 		= '',
					tosite 		= '$tiliointirow[tosite]',
					laatija 	= '$kukarow[kuka]',
					laadittu 	= now()";
		$xresult = mysql_query($query) or pupe_error($query);
	}

	$tee = 'E';
	$tila = 'X';
}

if ($tila == 'K') {
		$query = "	SELECT comments
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) !=1) {
			echo t('lasku kateissa') . "$tunnus</font>";
			exit;
		}

		$trow=mysql_fetch_array($result);

		if ($komm != '') {
			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") " . trim($komm) . "<br>" . $trow['comments'];
			$query = "UPDATE lasku set comments = '$komm' WHERE tunnus='$tunnus'";
			$result = mysql_query($query) or pupe_error($query);
		}

		$tee = 'E';
		$tila = 'X';
}

if ($tila == '') {

	$tila = 'E';
	echo "<table>";

	if ($trow['tila'] == 'X') {

		if ($trow['tapvm'] >= $yhtiorow['tilikausi_alku']) {
			$pvm=erottele($trow['tapvm']);
			
			echo "<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name='tila' value='P'>
				<input type = 'hidden' name='tee' value='M'>
				<input type = 'hidden' name='tunnus' value='$tunnus'>
				<tr><th>".t("Muuta tapahtumapvm")."</th>
				<td><input type = 'text' name = 'pp' maxlength='2' size='2' value = '$pvm[pp]'>
				<input type = 'text' name = 'kk' maxlength='2' size='2' value = '$pvm[kk]'>
				<input type = 'text' name = 'vv' maxlength='4' size='4' value = '$pvm[vv]'>
				<td><input type = 'submit' value = '".t("P‰ivit‰")."'></td></tr></form>";
		}
	}
	else {
		if (($trow['tapvm'] >= $yhtiorow['tilikausi_alku']) and ($kukarow['taso'] !=1 )) {
			$pvm = erottele($trow['tapvm']);
			echo "<form action = '$PHP_SELF' method='post'>
					<input type = 'hidden' name='tila' value='P'>
					<input type = 'hidden' name='tee' value='M'>
					<input type = 'hidden' name='tunnus' value='$tunnus'>
					<tr><th>".t("Muuta tapahtumapvm")."</th>
					<td><input type = 'text' name = 'pp' maxlength='2' size='2' value = '$pvm[pp]'>
					<input type = 'text' name = 'kk' maxlength='2' size='2' value = '$pvm[kk]'>
					<input type = 'text' name = 'vv' maxlength='4' size='4' value = '$pvm[vv]'>
					<td><input type = 'submit' value = '".t("P‰ivit‰")."'></td></tr></form>";
		}

		// Lasku on maksamatta tai suoraveloitus
		if (($trow['tila'] < 'N') or ($trow['suoraveloitus']!='')) {

			// ei myyntitilaus
			if  ($trow['tila'] != 'L' and $trow['tila'] != 'U') {

				if ($trow['tapvm'] >= $yhtiorow['tilikausi_alku']) {
					echo "<form action = '$PHP_SELF' method='post'>
						<input type = 'hidden' name='tila' value='Q'>
						<input type = 'hidden' name='tee' value='M'>
						<input type = 'hidden' name='tunnus' value='$tunnus'>
						<tr><th>".t("Muuta summa")."</th><td><input type = 'text' name = 'summa' value='$trow[summa]'>
						<td><input type = 'submit' value = '".t("P‰ivit‰")."'></td></tr></form>";
				}

				$pvm = erottele($trow['kapvm']);

				echo "<form action = '$PHP_SELF' method='post'>
						<input type = 'hidden' name='tila' value='C'>
						<input type = 'hidden' name='tee' value='M'>
						<input type = 'hidden' name='tunnus' value='$tunnus'>
						<tr><th>".t("Muuta kassa-ale ja/tai kapvm")."</th>
						<td><input type = 'text' name = 'summa' value='$trow[kasumma]'>
							<input type = 'text' name = 'pp' maxlength='2' size='2' value = '$pvm[pp]'>
							<input type = 'text' name = 'kk' maxlength='2' size='2' value = '$pvm[kk]'>
							<input type = 'text' name = 'vv' maxlength='4' size='4' value = '$pvm[vv]'></td>
						<td><input type = 'submit' value = '".t("P‰ivit‰")."'></td></tr></form>";
			}
		}

		// Lasku on maksamatta tai myyntilasku
		if (($trow['tila'] < 'N') or ($trow['tila'] == 'U' and $trow['mapvm']=='0000-00-00')) {
			$pvm = erottele($trow['erpcm']);

			echo "<form action = '$PHP_SELF' method='post'>
					<input type = 'hidden' name='tila' value='R'>
					<input type = 'hidden' name='tee' value='M'>
					<input type = 'hidden' name='tunnus' value='$tunnus'>
					<tr><th>".t("Muuta er‰pvm")."</th><td>
					<input type = 'text' name = 'pp' maxlength='2' size='2' value = '$pvm[pp]'>
					<input type = 'text' name = 'kk' maxlength='2' size='2' value = '$pvm[kk]'>
					<input type = 'text' name = 'vv' maxlength='4' size='4' value = '$pvm[vv]'></td>
					<td><input type = 'submit' value = '".t("P‰ivit‰")."'></td></tr></form>";

			if (($trow['kapvm'] != '0000-00-00') and ($trow['tila'] == 'U')) {
				$pvm = erottele($trow['kapvm']);

				echo "<form action = '$PHP_SELF' method='post'>
						<input type = 'hidden' name='tila' value='B'>
						<input type = 'hidden' name='tee' value='M'>
						<input type = 'hidden' name='tunnus' value='$tunnus'>
						<tr><th>".t("Muuta kassa-alepvm")."</th><td>
						<input type = 'text' name = 'pp' maxlength='2' size='2' value = '$pvm[pp]'>
						<input type = 'text' name = 'kk' maxlength='2' size='2' value = '$pvm[kk]'>
						<input type = 'text' name = 'vv' maxlength='4' size='4' value = '$pvm[vv]'></td>
						<td><input type = 'submit' value = '".t("P‰ivit‰")."'></td></tr></form>";
			}

			// voidaan vaihtaa myyntilaskun mapvm jos se ei ole viel‰ maksettu
			if ($trow['tila'] == 'U' and $trow['mapvm'] == '0000-00-00') {

				echo "<form action = '$PHP_SELF' method='post'>
						<input type = 'hidden' name='tila' value='maksuehto'>
						<input type = 'hidden' name='tee' value='M'>
						<input type = 'hidden' name='tunnus' value='$tunnus'>
						<tr><th>".t("Vaihda maksuehto")."</th>";

						echo "<td><select name='maksuehto'>";

						$query = "	SELECT maksuehto.tunnus, concat_ws(' ', ".avain('selectcon','MEHTOKATXT_').",  ".avain('selectcon2','MEHTOTXT_').") mehto
									FROM maksuehto
									".avain('join','MEHTOKATXT_')."
									".avain('join2','MEHTOTXT_')."
									WHERE maksuehto.yhtio 	= '$kukarow[yhtio]'
									and maksuehto.kaytossa 	= ''
									and (maksuehto.sallitut_maat = '' or maksuehto.sallitut_maat like '%$trow[maa]%')
									order by maksuehto.jarjestys, mehto, maksuehto.tunnus";
						$mresult = mysql_query($query) or pupe_error($query);

						while ($row = mysql_fetch_array($mresult)) {
							$sel = "";
							if ($trow["maksuehto"] == $row["tunnus"]) {
								$sel = 'selected';
							}
							echo "<option value='$row[0]' $sel>$row[1]";
						}
						echo "</select></td>";
						echo "<td><input type = 'submit' value = '".t("P‰ivit‰")."'></td></tr></form>";
			}
		}
	}

	// Lasku on suoritettu
	if ((($trow['tila'] == 'Y') or ($trow['tila'] == 'U')) and ($trow['mapvm'] != '0000-00-00')) {
		if ($trow['mapvm'] >= $yhtiorow['tilikausi_alku']) {
			$pvm=erottele($trow['mapvm']);
			echo "<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name='tila' value='S'>
				<input type = 'hidden' name='tee' value='M'>
				<input type = 'hidden' name='tunnus' value='$tunnus'>
				<tr><th>".t("Muuta suorituspvm")."</th><td>
				<input type = 'text' name = 'pp' maxlength='2' size='2' value = '$pvm[pp]'>
				<input type = 'text' name = 'kk' maxlength='2' size='2' value = '$pvm[kk]'>
				<input type = 'text' name = 'vv' maxlength='4' size='4' value = '$pvm[vv]'>
				<td><input type = 'submit' value = '".t("P‰ivit‰")."'></td></tr></form>";
		}
	}

	// katotaan onko lasku liitetty keikkaan
	$query = "select laskunro from lasku where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus='$trow[tunnus]'";
	$keikres = mysql_query($query) or pupe_error($query);

	if (($trow['tila'] == 'H' or $trow['tila'] == 'M' or $trow['tila'] == 'P' or $trow['tila'] == 'Q' or $trow['tila'] == 'Y') and mysql_num_rows($keikres) == 0 and $trow['tapvm'] >= $yhtiorow['tilikausi_alku']) {

		if ($trow['vienti']=='A') $vientia='selected';
		if ($trow['vienti']=='B') $vientib='selected';
		if ($trow['vienti']=='C') $vientic='selected';
		if ($trow['vienti']=='D') $vientid='selected';
		if ($trow['vienti']=='E') $vientie='selected';
		if ($trow['vienti']=='F') $vientif='selected';
		if ($trow['vienti']=='G') $vientig='selected';
		if ($trow['vienti']=='H') $vientih='selected';
		if ($trow['vienti']=='I') $vientii='selected';
		if ($trow['vienti']=='J') $vientij='selected';
		if ($trow['vienti']=='K') $vientik='selected';
		if ($trow['vienti']=='L') $vientil='selected';

		echo "<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name='tila' value='W'>
				<input type = 'hidden' name='tee' value='M'>
				<input type = 'hidden' name='tunnus' value='$tunnus'>
				<tr><th>".t("Muuta laskun tyyppi")."</th>";

		echo "
				<td>
					<select name='vienti'>
						<option value='A' $vientia>".t("Kotimaa")."</option>
						<option value='B' $vientib>".t("Kotimaa huolinta/rahti")."</option>
						<option value='C' $vientic>".t("Kotimaa vaihto-omaisuus")."</option>
						<option value='J' $vientij>".t("Kotimaa raaka-aine")."</option>

						<option value='D' $vientid>".t("EU")."</option>
						<option value='E' $vientie>".t("EU huolinta/rahti")."</option>
						<option value='F' $vientif>".t("EU vaihto-omaisuus")."</option>
						<option value='K' $vientik>".t("EU raaka-aine")."</option>

						<option value='G' $vientig>".t("ei-EU")."</option>
						<option value='H' $vientih>".t("ei-EU huolinta/rahti")."</option>
						<option value='I' $vientii>".t("ei-EU vaihto-omaisuus")."</option>
						<option value='L' $vientil>".t("ei-EU raaka-aine")."</option>
					</select>
				<td><input type = 'submit' value = '".t("P‰ivit‰")."'></td></tr></form>";
	}

	// jos meill‰ on suoraveloitus jolla ei ole maksutili‰ (eli tiliˆintej‰ ei ole tehty), se voidaan poistaa jos hyv‰ksytt‰v‰n‰, valmismaksatukseen tai odottaa suoritusta
	if ($trow['suoraveloitus'] != "" and $trow["maksu_tili"] == "" and in_array($trow['tila'], array('Q','M','H'))) {
		echo "	<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name='tee' value='M'>
				<input type = 'hidden' name='tila' value='A'>
				<input type = 'hidden' name='tunnus' value='$tunnus'>
				<tr>
				<th>".t("Poista suoraveloitus")."</th>
				<td>Lasku siirtyy takaisin hyv‰ksynt‰‰n</td>
				<td><input type = 'submit' value = '".t("Poista")."'></td>
				</tr>
				</form>";
	}

	echo "<form action = '$PHP_SELF?tee=M&tila=K' method='post'>
				<input type = 'hidden' name='tunnus' value='$tunnus'>
				<tr><th>".t("Lis‰‰ kommentti")."</th>
				<td><input type = 'text' name = 'komm' size='40' value = '$komm'></td>
				<td><input type = 'submit' value = '".t("Lis‰‰")."'></td></tr></form>";

	//if (($trow['tila'] == 'H' or $trow["tila"] == 'M') and $kukarow['taso'] != 1) {

	if ($trow['tila'] == 'H' and $kukarow['kuka'] == $trow['hyvaksyja_nyt'] and $kukarow['taso'] != 1) {
		if (strtoupper($trow['maa']) == strtoupper($yhtiorow['maa'])) {
			echo "<form action = '$PHP_SELF?tee=M&tila=T' method='post'>
				<input type = 'hidden' name='tunnus' value='$tunnus'>
				<tr><th>".t("Muuta toimittajan pankkitili‰")."</th>
				<td><input type = 'text' name = 'tili' size='40' value = '".tilinumero_print($trow['tilinumero'])."'></td>
				<td><input type = 'submit' value = '".t("Muuta")."'></td></tr></form>";
		}
		else {
			echo "<form action = '$PHP_SELF?tee=M&tila=TU' method='post'>
				<input type = 'hidden' name='tunnus' value='$tunnus'>
				<tr><th>".t('Muuta toimitajan ulkomaanmaksutili‰')."</th>
				<td><input type = 'text' name = 'ultilno' size='40' value = '$trow[ultilno]'></td><td></td></tr>
				<tr><th>".t('SWIFT')."</th>
				<td><input type = 'text' name = 'swift' size='40' value = '$trow[swift]'></td>
				<td><input type = 'submit' value = '".t("P‰ivit‰")."'></td></tr></form>";
		}
	}
	echo "</table><br><br>";
}

$tee='';
if ($kukarow['taso']==1 and $phpnimi=='hyvak.php') $tunnus=''; // Estet‰‰n v‰‰r‰ k‰yttˆliittym‰
if ($phpnimi=='muutosite.php') $tee='E'; // Estet‰‰n v‰‰r‰ k‰yttˆliittym‰

?>
