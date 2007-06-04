<?php
	require "inc/parametrit.inc";
	$turvayhtiorow=$yhtiorow; // T‰t‰ alkuper‰ist‰ voidaan joskus tarvita
	if ($tee == 'DS') { // Perutaan maksuun meno
		if (is_array($lasku)) {
			foreach ($lasku as $peruttava) {
				$tee='DS';
				$query = "SELECT round(if(kapvm=olmapvm,summa-kasumma,summa) * maksu_kurssi,2) summa, maksu_tili, maa, olmapvm, maksu_tili, tilinumero, ultilno, yhtio
							FROM lasku
							WHERE tunnus = '$peruttava' and tila='P'";
				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) != 1) {
					echo "<font class='error'>".t('Lasku katosi tai se ehdittiin juuri siirt‰‰ pankkiin')."</font><br>";
				}
				else {
					$trow=mysql_fetch_array ($result);
					$query = "SELECT * FROM yhtio WHERE yhtio = '$yhtio'";
					$result = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($result) != 1) {
						echo "<font class='error'>".t('Laskun yritys katosi')."</font><br>";
						exit;
					}
					$yhtiorow=mysql_fetch_array ($result);

					if ($trow['summa'] > 0)  { //Vastaava m‰‰r‰ rahaa on oltava veloituspuolella
						$query = "SELECT sum(if(alatila='K', summa - kasumma, summa)) summa FROM lasku
									WHERE yhtio='$trow[yhtio]' and tila='P' and olmapvm = '$trow[olmapvm]' and
											maksu_tili = '$trow[maksu_tili]' and
											if(maa='$yhtiorow[maa]', tilinumero, ultilno) =  if('$trow[maa]'='$yhtiorow[maa]', '$trow[tilinumero]', '$trow[ultilno]') and tunnus != '$peruttava'";
						$result = mysql_query($query) or pupe_error($query);
						if (mysql_num_rows($result) != 1) {
							echo "<b>".t("Hyvityshaulla ei lˆytynyt mit‰‰n")."</b>$query";
							exit;
						}
						$veloitusrow=mysql_fetch_array ($result);

						if ($veloitusrow['summa'] < 0) {
							echo "<font class='error'>".t("Jos poistat t‰m‰n laskun maksatuksesta, on asiakkaalle valittu liikaa hyvityksi‰.")." ($veloitusrow[summa])</font><br>";
							$tee = 'DP';
						}

					}
					if ($tee == 'DS') {
						$query = "UPDATE lasku set
											maksaja = '',
											maksuaika = '0000-00-00',
											maksu_kurssi = '0',
											maksu_tili = '',
											tila = 'M',
											alatila = '',
											olmapvm=if(kapvm<now(),if(kapvm='0000-00-00',erpcm,kapvm),erpcm)
											WHERE tunnus='$peruttava' and tila='P'";
						$updresult = mysql_query($query) or pupe_error($query);
						if (mysql_affected_rows() != 1) { // Jotain meni pieleen
							echo "System error Debug --> $query<br>";
							exit;
						}
						$query = "UPDATE yriti set
												maksulimitti = maksulimitti + $trow[summa]
												WHERE tunnus = '$trow[maksu_tili]'";
						$updresult = mysql_query($query) or pupe_error($query);
						$tee = '';
					}
				}
			}
		}
	}
	if (is_array($maksut)) {
		foreach ($maksut as $yhtio => $valitut) {
			foreach ($valitut as $tee => $arvo) {
				if (($tee != "") and ($arvo='on')) {				
					// Onko $yhtio validi
					$query = "SELECT *
								FROM yhtio
								WHERE konserni='$yhtiorow[konserni]' and yhtio='$yhtio'";
					$result = mysql_query($query) or pupe_error($query);
	 		
					if (mysql_num_rows($result) == 0) {
						echo "<font class='error'>".t("Yhtiˆ-muutuja on mennyt rikki")."!</font>";
						exit;
					}
					$apurow=mysql_fetch_array($result);
					echo "<font class='message'>";
					printf(t('Maksan yrityksen %s laskuja'), $apurow['nimi']);
					echo"</font><br>";		
					// Etsit‰‰n aluksi yrityksen oletustili
					$query = "SELECT yriti.tunnus, yriti.nimi, yriti.tilino, yriti.maksulimitti
								FROM yriti, kuka
								WHERE kuka.yhtio='$yhtio' and kuka.kuka='$kukarow[kuka]' and yriti.tunnus=kuka.oletustili";
					$result = mysql_query($query) or pupe_error($query);
			
					if (mysql_num_rows($result) == 0) {
						echo "<font class='error'>".t("K‰ytt‰j‰ll‰ ei ollut oletustili‰")."!</font>";
					}
					else $oltilrow=mysql_fetch_array($result);

					if ((($tee == "nk") or ($tee == "nt") or ($tee == "nu") or ($tee == "nko") or ($tee == "nto")) and ($arvo=='on')) {
						$valinta = "olmapvm <= now()" ; // Oletetaan kaikki er‰‰ntyneet
						if (substr($tee,0,2) == "nt")
							$valinta = "olmapvm = now()";

						if ($tee == "nu") $valinta = "olmapvm < date_add(now(), interval 7 day) and olmapvm > now()";
						
						$query = "SELECT valuu.kurssi, round(if(kapvm>=now(),summa-kasumma,summa) * valuu.kurssi,2) summa,
									lasku.nimi, lasku.tunnus
									FROM lasku, valuu
									WHERE lasku.valkoodi = valuu.nimi and
											valuu.yhtio = '$yhtio' and
											lasku.yhtio = valuu.yhtio and
											summa > 0 and tila = 'M' and $valinta
											and if(lasku.maa='$yhtiorow[maa]',lasku.tilinumero, lasku.ultilno) <>  ALL (SELECT distinct if(lasku.maa='$apurow[maa]',lasku.tilinumero, lasku.ultilno) tilinumero FROM lasku WHERE lasku.yhtio = '$yhtiorow[yhtio]' and tila = 'M' and summa < 0)
											ORDER BY olmapvm, summa  desc";
						$result = mysql_query($query) or pupe_error($query);
						$erapvm = 'olmapvm = if(now()<=kapvm,kapvm,if(now()<=erpcm,erpcm,now()))';
					}
					else {
						$query = "SELECT valuu.kurssi, round(if(kapvm>=now(),summa-kasumma,summa) * valuu.kurssi,2) summa,
									lasku.nimi, lasku.tunnus
									FROM lasku,
									valuu
									WHERE lasku.yhtio = '$yhtio' and tila = 'M'
									and if(lasku.maa='$apurow[maa]',lasku.tilinumero, lasku.ultilno) = '$tee'
									and if(kasumma > 0,kapvm,erpcm) <= now()
									and lasku.valkoodi = valuu.nimi and valuu.yhtio = '$yhtio'";
						$result = mysql_query($query) or pupe_error($query);
						$erapvm = 'olmapvm=now()';
					}
					echo "<font class='message'>";
					if (($tee == "nk") or ($tee == "nt") or ($tee == "nu"))
						printf(t('Maksan %d laskua')."<br>",mysql_num_rows($result));
					else 
						printf(t('Yrit‰n maksaa %d laskua')."<br>",mysql_num_rows($result));
										
					while ($tiliointirow=mysql_fetch_array ($result)) {
						$query = "SELECT maksulimitti FROM yriti WHERE yhtio='$yhtio' and tunnus='$oltilrow[tunnus]'";
						$yrires = mysql_query($query) or pupe_error($query);
						if (mysql_num_rows($yrires) != 1) {
							echo "Maksutili katosi! Systeemivirhe! $query";
							exit;
						}
						$mayritirow=mysql_fetch_array ($yrires);
						if ($mayritirow['maksulimitti'] < $tiliointirow['summa']) {
							echo "<br><font class='error'>".t("Maksutilin limitti ylittyi! Laskujen maksu keskeytettiin")."</font>";
							break;
						}
						$query = "UPDATE lasku set
										maksaja = '$kukarow[kuka]',
										maksuaika = now(),
										maksu_kurssi = '$tiliointirow[kurssi]',
										maksu_tili = '$oltilrow[tunnus]',
										tila = 'P',
										alatila = if(kapvm>=now(),'K',''),
										$erapvm
										WHERE tunnus='$tiliointirow[tunnus]' and yhtio = '$yhtio' and tila='M'";
						$updresult = mysql_query($query) or pupe_error($query);
						if (mysql_affected_rows() != 1) { // Jotain meni pieleen
							echo "System error Debug --> $query<br>";
							exit;
						}
						$query = "UPDATE yriti set
											maksulimitti = maksulimitti - $tiliointirow[summa]
											WHERE tunnus = '$oltilrow[0]' and yhtio = '$yhtio'";
						$updresult = mysql_query($query) or pupe_error($query);
						echo "($tiliointirow[nimi], $tiliointirow[summa]) ";
					}
					echo "</font><br>";
					$tee = '';
				}
			}
		}
	}
			
	if(is_array($tilit)) {
		foreach($tilit as $yhtio => $tili) {
			// Sitten oletustili p‰‰lle, jos sit‰ pyydettiin!
			//echo "$yhtio, $tili, $kukarow[kuka]<br>";
			$query = "UPDATE kuka set
					  oletustili = '$tili'
					  WHERE kuka = '$kukarow[kuka]' and yhtio='$yhtio'";
			$result = mysql_query($query) or pupe_error($query);
		}
	}

	echo "<font class='head'>".t("Laskujen maksatus - moniyritysn‰kˆkulma")."</font><hr>";

	if ($hyvitykset!='') {
		echo "<font class='head'>".t("Hyvitykset, jotka voisi k‰ytt‰‰")."</font><hr>";
		// Mitk‰ yritykset valitaan?
		$query = "SELECT *
				FROM yhtio
				WHERE konserni='$turvayhtiorow[konserni]'
				ORDER BY yhtio";
		$konserniresult = mysql_query($query) or pupe_error($query);
		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'hyvitykset' value ='1'><table>";
		while ($konsernirow=mysql_fetch_array ($konserniresult)) {
			// Onko hyvityksi‰, jotka voisi k‰ytt‰‰
			$query = "SELECT lasku.nimi, mahdolliset.tilinumero, sum(if(lasku.summa < 0, summa, 0)) hyvitykset,sum(if(lasku.summa < 0, 1, 0)) hyvityksetkpl, sum(if(lasku.kasumma=0,lasku.summa, if(lasku.kapvm<now(),lasku.summa-lasku.kasumma, lasku.summa))) summa, count(*) kpl
					  FROM lasku,
					  (SELECT distinct if(lasku.maa='$konsernirow[maa]',lasku.tilinumero, lasku.ultilno) tilinumero FROM lasku WHERE lasku.yhtio = '$konsernirow[yhtio]' and tila = 'M' and summa < 0) as mahdolliset
					  WHERE lasku.yhtio = '$konsernirow[yhtio]' and tila = 'M'
					  		and if(lasku.maa='$konsernirow[maa]',lasku.tilinumero, lasku.ultilno) = mahdolliset.tilinumero and
					  		if(kasumma > 0,kapvm,erpcm) <= now()
		  			  GROUP BY lasku.yhtio, mahdolliset.tilinumero";
			$laskuresult = mysql_query($query) or pupe_error($query);
		  	if (mysql_num_rows($laskuresult) > 0) {		  	
		  		echo "<tr><th>$konsernirow[nimi]</th>";
		  		// Tehd‰‰n tarvittaessa sopiva popup
				$query = "	SELECT yriti.*, if(yriti.tunnus=kuka.oletustili,1,0) oletustili
							FROM yriti, kuka
							WHERE kuka.yhtio = '$konsernirow[yhtio]' and kuka.kuka='$kukarow[kuka]' and yriti.yhtio=kuka.yhtio
							ORDER BY yriti.nimi";
				$vresult = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($vresult) == 0) {
					echo "<font class='error'>".t("Yrityksell‰ ei ole pankkitilej‰")."!</font></td></tr>";
				}
				elseif (mysql_num_rows($vresult) > 1) {
					echo "<th colspan='6'><select name='tilit[$konsernirow[yhtio]]' onchange='submit()'>";
					while ($vrow=mysql_fetch_array($vresult)) {
						$sel="";
						if ($vrow['oletustili'] != 0) {
							$sel = "selected";
							$maksulimitti = $vrow['maksulimitti'];
						}
						echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi] ($vrow[tilino]) $vrow[maksulimitti]";
					}
					echo "</select></th>";
				}
				else {
					echo "<th colspan='6'>$oltilrow[nimi] ($oltilrow[tilino]) $oltilrow[maksulimitti]</th>";
				}
				echo "</tr>";
		  		echo "<tr>";
			  	for ($i = 0; $i < mysql_num_fields($laskuresult); $i++) {
					echo "<th>" . t(mysql_field_name($laskuresult,$i))."</th>";
				}
				echo "<th>Maksa</th>";
				echo "</tr>";
				while ($laskurow=mysql_fetch_array ($laskuresult)) {
			  		echo "<tr>";
			  		for ($i = 0; $i < mysql_num_fields($laskuresult); $i++) {
						if ((mysql_fieldname($laskuresult,$i) == 'summa') and ($laskurow['summa'] >  $maksulimitti))
							echo "<td><font class='error'>$laskurow[$i]</font></td>";
						else
							echo "<td>$laskurow[$i]</td>";
					}
					if (($laskurow['summa'] > 0) and ($laskurow['summa'] <=  $maksulimitti))
							echo "<td><input type='checkbox' name = 'maksut[".$konsernirow['yhtio']."][".$laskurow['tilinumero']."]'></td>";
					else 
						echo "<td></td>";
					echo "</tr>";
				}
			}
		}
		echo "<tr><td colspan='7' align='right'><input type='submit' name = 'nyt' value='".t('Maksa')."'></td></tr></table></form><br>";
		require "inc/footer.inc";
		exit;
	}

	// Mitk‰ yritykset valitaan?
	$query = "SELECT *
				FROM yhtio
				WHERE konserni='$yhtiorow[konserni]'
				ORDER BY yhtio";
	$yhtioresult = mysql_query($query) or pupe_error($query);
	
	echo "<table><tr>";
	echo"<tr><th colspan='2'></th><th>Er‰‰ntyneet</th><th></th><th>T‰n‰‰n er‰‰ntyy</th><th></th><th>Seuraavat 7 pv</th><th></th><form action = '$PHP_SELF' method='post'><th>
	<input type='hidden' name = 'hyvitykset' value ='1'>
	<input type='submit' value = 'Hyvitykset'></th></form></tr>";
	echo "<form action = '$PHP_SELF' method='post'>";
	while ($yhtiorow=mysql_fetch_array ($yhtioresult)) {		
		echo "<td>$yhtiorow[nimi]</td>";
		echo "<td>";
		// Etsit‰‰n aluksi yrityksen oletustili
		$query = "SELECT yriti.tunnus, yriti.nimi, yriti.tilino, yriti.maksulimitti
					FROM yriti, kuka
					WHERE kuka.yhtio='$yhtiorow[yhtio]' and kuka.kuka='$kukarow[kuka]' and yriti.tunnus=kuka.oletustili";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("K‰ytt‰j‰ll‰ ei ollut oletustili‰")."!</font><br>";
			$query = "	SELECT tunnus, nimi
							FROM yriti
							WHERE yhtio = '$yhtiorow[yhtio]' LIMIT 1";
			$result = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result) == 1) {
				$vrow=mysql_fetch_array($result);
				$query = "UPDATE kuka set
					  					oletustili = '$vrow[tunnus]'
					  		 WHERE kuka = '$kukarow[kuka]' and yhtio='$yhtiorow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				echo "<font class='error'>".t("P‰ivitin k‰ytt‰j‰lle oletustilin")." ($vrow[nimi])!</font><br>";
			}
		}
		else $oltilrow=mysql_fetch_array($result);
		
		// Tehd‰‰n tarvittaessa sopiva popup
		$query = "	SELECT *
					FROM yriti
					WHERE yhtio = '$yhtiorow[yhtio]'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($vresult) == 0) {
			echo "<font class='error'>".t("Yrityksell‰ ei ole pankkitilej‰")."!</font></td></tr>";
		}
		elseif (mysql_num_rows($vresult) > 1) {
			echo "<select name='tilit[$yhtiorow[yhtio]]' onchange='submit()'>";
			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($oltilrow['tunnus'] == $vrow['tunnus']) {
					$sel = "selected";
				}
				echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi] ($vrow[tilino]) $vrow[maksulimitti]";
			}
			echo "</select></td>";
		}
		else {
			echo "$oltilrow[nimi] ($oltilrow[tilino]) $oltilrow[maksulimitti]</td>";
		}
		if ($oltilrow['tunnus'] != 0) { //Meill‰ on jokin tili
			$query = "SELECT sum(if(olmapvm<now(),round(if(kapvm>=now(),summa-kasumma,summa)*kurssi,2),0)) vanhat,
							sum(round(if(olmapvm=now(),if(kapvm>=now(),summa-kasumma,summa),0)*kurssi,2)) tanaan,
							sum(if(olmapvm>now(),round(if(kapvm>=now(),summa-kasumma,summa)*kurssi,2),0)) uudet,
							sum(if(olmapvm<now(),1,0)) vanhatmaara,
							sum(if(olmapvm=now(),1,0)) tanaanmaara,
							sum(if(olmapvm>now(),1,0)) uudetmaara
				FROM lasku, valuu
				WHERE lasku.yhtio='$yhtiorow[yhtio]' and summa > 0 and
					   tila = 'M' and olmapvm <= date_add(now(), interval 7 day) and valuu.yhtio=lasku.yhtio and valuu.nimi=lasku.valkoodi and if(lasku.maa='$yhtiorow[maa]',lasku.tilinumero, lasku.ultilno) <>  ALL (SELECT distinct if(lasku.maa='$konsernirow[maa]',lasku.tilinumero, lasku.ultilno) tilinumero FROM lasku WHERE lasku.yhtio = '$yhtiorow[yhtio]' and tila = 'M' and summa < 0)";
			$result = mysql_query($query) or pupe_error($query);
			$sumrow=mysql_fetch_array ($result);
			
			if ($sumrow['vanhat'] > 0)
				echo "<td><a href='$PHP_SELF?erittely=$yhtiorow[yhtio]'>$sumrow[vanhat]</a> ($sumrow[vanhatmaara])</td><td>";
			else 
				echo "<td>";
			
			if ($sumrow['vanhat'] > 0) {
				if ($oltilrow['maksulimitti'] < $sumrow['vanhat']) {
					echo "<font class='error'>".t("Saldo ei riit‰")."</font><br>";
					echo "<input type='checkbox' name = 'maksut[$yhtiorow[yhtio]][nko]'>". t('Maksa osin');
				}
				else {
					echo "<input type='checkbox' name = 'maksut[$yhtiorow[yhtio]][nk]''>". t('Maksa');
				}
			}
			else {
				echo "<font class='message'>".t("Ei maksuja")."</font></td><td></td>";
			}
			echo "</td>";
			
			if ($sumrow['tanaan'] > 0)
				echo "<td><a href='$PHP_SELF?erittely=$yhtiorow[yhtio]&nyt=1'>$sumrow[tanaan]</a> ($sumrow[tanaanmaara])</td><td>";
			else
				echo "<td>";
				
			if ($sumrow['tanaan'] > 0) {
				if ($oltilrow['maksulimitti'] < $sumrow['tanaan']) {
					echo "<font class='error'>".t("Saldo ei riit‰")."</font><br>";
					echo "<input type='checkbox' name = 'maksut[$yhtiorow[yhtio]][nto]'>". t('Maksa osin');
				}
				else {
					echo "<input type='checkbox' name = 'maksut[$yhtiorow[yhtio]][nt]'>". t('Maksa');
				}
			}
			else {
				echo "<font class='message'>".t("Ei maksuja")."</font></td><td>";
			}
			echo "</td>";

			if ($sumrow['uudet'] > 0)
				echo "<td><a href='$PHP_SELF?erittely=$yhtiorow[yhtio]&nyt=2'>$sumrow[uudet]</a> ($sumrow[uudetmaara])</td><td>";
			else 
				echo "<td>";

			if ($sumrow['uudet'] > 0) {
				echo "<input type='checkbox' name = 'maksut[$yhtiorow[yhtio]][nu]'>". t('Maksa');
			}
			else {
				echo "<font class='message'>".t("Ei maksuja")."</font></td><td>";
			}
			echo "</td>";
			echo "<td>";
			$query = "SELECT count(*) maara FROM lasku
			  				WHERE yhtio = '$yhtiorow[yhtio]' and tila = 'P' and maksaja = '$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);
			$sumrow=mysql_fetch_array ($result);
			if ($sumrow[maara]>0) {
				echo "<a href='$PHP_SELF?yhtio=$yhtiorow[yhtio]&tee=DP'>".t('Maksuun valitut')."</a> ($sumrow[maara])";
			}
			else {
				echo "<font class='message'>".t("Ei valittuja maksuja")."</font>";
			}
			echo "</td></tr>";
		}
	}
	echo "<tr><td colspan='9' align='right'><input type='submit' name = 'nyt' value='".t('Maksa')."'></td></tr>";
	echo "</table></form>";
	if ($erittely!='') {
		//Kenen laskut
		$query = "SELECT *
				FROM yhtio
				WHERE konserni='$turvayhtiorow[konserni]' and yhtio='$erittely'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 1) {
			$yhtiorow=mysql_fetch_array ($result);
			echo "<font class='message'>$yhtiorow[nimi]</font><br>";
			$lisa='';
			if($nyt==1) $lisa=' and olmapvm=now()';
			if($nyt==2) $lisa=' and olmapvm < date_add(now(), interval 60 day) and olmapvm > now()'; 
			$query = "SELECT lasku.nimi,
				  lasku.kapvm, lasku.erpcm, lasku.valkoodi,
				  lasku.summa - lasku.kasumma 'kassa-alella',
				  round((lasku.summa - lasku.kasumma) * valuu.kurssi,2) 'kotivaluutassa',
				  lasku.summa, round(lasku.summa * valuu.kurssi,2) 'kotivaluutassa',
				  lasku.ebid
				  FROM lasku, valuu
				  WHERE lasku.yhtio = '$erittely' and
					valuu.yhtio = '$erittely' and
					tila = 'M' and summa > 0 and
					lasku.valkoodi = valuu.nimi $lisa
				  ORDER BY olmapvm, kotivaluutassa desc";

			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
			 	echo "<b>".t("Haulla ei lˆytynyt yht‰‰n laskua")."</b>";
			}
			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "</tr>";

			while ($trow=mysql_fetch_array ($result)) {
		        echo "<tr>";
		        for ($i=0; $i<mysql_num_fields($result); $i++) { // ei n‰ytet‰ tunnusta
					if (mysql_fieldname($result,$i) == 'ebid') {
						if (strlen($trow[$i]) > 0) {
							$ebid = $trow[$i];
							require "inc/ebid.inc";
							echo "<td><a href='$url'>".t("N‰yt‰ lasku")."</a></td>";
						}
						else {
							//	Onko kuva tietokannassa?
							echo "<td valign='top'>";
							$query = "select * from liitetiedostot where yhtio='{$kukarow[yhtio]}' and liitos='lasku' and liitostunnus='{$laskurow["tunnus"]}'";
							$liiteres=mysql_query($query) or pupe_error($query);
							if(mysql_num_rows($liiteres)>0) {
								while($liiterow=mysql_fetch_array($liiteres)) {
									echo "<a href='view.php?id={$liiterow["tunnus"]}'>{$liiterow["selite"]}</a><br>";
								}
							}
							else {
								echo t("Paperilasku");
							}
							echo "</td>";		
						}
					}
					else {
						echo "<td>$trow[$i]</td>";
					}
				}
/*				// Ok, mutta onko meill‰ varaa makssa kyseinen lasku???
				if ($trow[5] <= $yritirow[2]) {

					echo "<td><form action = 'maksa.php' method='post'>";
					if ($trow[4] != $trow[6]) {
						$ruksi='checked';
						if ($trow['kapvm'] < date("Y-m-d")) {
								$ruksi=''; // Ooh, maksamme myˆs‰ss‰
						}
						echo "".t("K‰yt‰ kassa-ale")." <input type='Checkbox' name='kaale' $ruksi><br>";
					}
					if ($trow['olmapvm'] != date("Y-m-d")) {
						if ($trow['olmapvm'] < date("Y-m-d")) {
								echo "<font class='error'>".t("Er‰‰ntynyt maksetaan heti")."</font><br>";
						}
						else {
							echo "".t("Maksetaan heti")."<input type='Checkbox' name='poikkeus' $ruksi><br>";
						}
					}
					if ($trow['summa'] < 0) { //Hyvitykset voi hoitaa ilman pankkiinl‰hetyst‰
						echo "".t("ƒl‰ l‰het‰ pankkiin")."<input type='Checkbox' name='eipankkiin'><br>";
					}

					echo "<input type='hidden' name = 'tee' value='H'>
						<input type='hidden' name = 'tunnus' value='$trow[9]'>
						<input type='hidden' name = 'valuu' value='$valuu'>
						<input type='hidden' name = 'erapvm' value='$erapvm'>
						<input type='hidden' name = 'kaikki' value='$kaikki'>
						<input type='hidden' name = 'tapa' value='$tapa'>
						<input type='Submit' value='".t("Maksa")."'></td></form>";
				}
				else {
				// ei ollutkaan varaa!!
					echo "<td>".t("Tilin limitti ei riit‰")."!</td>";
				}
	*/
				echo "</tr>";
			}
			echo "</table>";
		}
	}
	if ($tee == 'DP') { //N‰ytet‰‰n kaikki omat maksatukseen valitut
		$query = "SELECT maksaja, yriti.nimi, lasku.nimi,
			  olmapvm, lasku.valkoodi,
			  round(if(alatila='K',(summa - kasumma) * kurssi, summa * kurssi),2) 'kotivaluutassa',
			  if(alatila='k','*','') kale, lasku.tunnus peru
			  FROM lasku, valuu, yriti
			  WHERE lasku.yhtio = '$yhtio' and
				valuu.yhtio = lasku.yhtio and
				valuu.yhtio = yriti.yhtio and
				lasku.maksu_tili = yriti.tunnus and
				tila = 'P' and
				lasku.valkoodi = valuu.nimi and maksaja = '$kukarow[kuka]'
			  ORDER BY maksu_tili, olmapvm, kotivaluutassa";

		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
		 	echo "<font class='error'>".t('Pankkiin l‰hetett‰vi‰ laskuja ei lˆydy')."</font><br>";
		}
		echo "<br>".t("Pankkiin l‰htev‰t maksut")."<hr>";
	   echo "<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name = 'tee' value='DS'>
					<input type='hidden' name = 'yhtio' value='$yhtio'>";
		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		while ($trow=mysql_fetch_array ($result)) {
	        echo "<tr>";
	        for ($i=0; $i<mysql_num_fields($result); $i++) {
	        	if ($i==mysql_num_fields($result)-1) {
	        		echo "<td>
					<input type='checkbox' name = 'lasku[]' value='$trow[peru]'></td>";

	        	}
	        	else {
	        		if (mysql_field_name($result,$i) == 'olmapvm') {
	        			echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[peru]'>$trow[$i]</a></td>";
	        		}
	        		else {
	        			echo "<td>$trow[$i]</td>";
	        		}
	        	}
	        }
	        echo "</tr>";
		}
		echo "<tr><td colspan='8' align='right'><input type='Submit' value='".t('Peru')."'></td></tr></table></form>";
	}
	require "inc/footer.inc";
?>
