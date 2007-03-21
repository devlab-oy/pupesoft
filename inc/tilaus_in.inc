<?php
echo "<pre>";
if (!$fd = fopen($filename, "r")) die("Filen '$filename' avaus epäonnistui!");

$toimpvm = date(Y)."-".date(n)."-".date(j);

if ($tyyppi == 'yct') {
	echo t("Uusi tilaus")."<br>";
	$asno = '90113';
	echo "<hr>";
	echo t("Tilaus").": ".t("asiakas")." '" . $asno .  "' ";	
	
	$query = 	"SELECT * FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' and ytunnus='$asno'";
	$result = mysql_query($query) or pupe_error($query);
		
	if (mysql_num_rows($result) != 1) {
		echo t("Asiakasta ei löydy tai järjestelmässä on usea asiakas samalla ytunnuksella")."<br>";
	}
	
	else {
		$asiakasrow = mysql_fetch_array($result);
					
		$tnimi 		= $asiakasrow['nimi'];
		$tnimitark 	= $asiakasrow['nimitark'];
		$tosoite 	= $asiakasrow['osoite'];
		$tpostino 	= $asiakasrow['postino'];
		$tpostitp 	= $asiakasrow['postitp'];
		$toim_maa 	= $asiakasrow['maa'];

		$query = "	INSERT into lasku SET
					laatija 		= '$kukarow[kuka]',
					luontiaika		= NOW(),
					ytunnus			= '$asiakasrow[ytunnus]',
					nimi 			= '$asiakasrow[nimi]',
					nimitark 		= '$asiakasrow[nimitark]',
					osoite 			= '$asiakasrow[osoite]',
					postino 		= '$asiakasrow[postino]',
					postitp 		= '$asiakasrow[postitp]',
					maa 			= '$asiakasrow[maa]',
					toim_nimi 		= '$tnimi',
					toim_nimitark 	= '$tnimitark',
					toim_osoite 	= '$tosoite',
					toim_postino 	= '$tpostino',
					toim_postitp 	= '$tpostitp',
					toim_maa 		= '$toim_maa',
					verkkotunnus	= '$asiakasrow[verkkotunnus]',
					tila 			= 'L',
					alatila 		= '',
					tilaustyyppi 	= 'N',
					valkoodi 		= '$yhtiorow[valkoodi]',
					kohdistettu 	= 'K',
					erikoisale 		= '0',
					varasto 		= '14',
					eilahetetta 	= 'o',
					laskutusvkopv 	= '$asiakasrow[laskutusvkopv]',
					toimaika 		= '$toimpvm',
					kerayspvm 		= '$toimpvm',
					maksuehto 		= '$asiakasrow[maksuehto]',
					toimitustapa 	= '$asiakasrow[toimitustapa]',
					toimitusehto 	= '$asiakasrow[toimitusehto]',
					myyja 			= '$kukarow[tunnus]',
					alv 			= '$asiakasrow[alv]',
					ovttunnus 		= '$asiakasrow[ovttunnus]',
					toim_ovttunnus 	= '$asiakasrow[toim_ovttunnus]',
					chn				= '$asiakasrow[chn]',
					liitostunnus	= '$asiakasrow[tunnus]',
					yhtio 			= '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$id = mysql_insert_id();
		$kukarow['kesken'] = $id;
		//$laskurow['tunnus'] = $id
		
		$query = "	SELECT * 
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$id'";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);
		
		echo t("nro")." $id<br>";
	}
}

while ((!feof ($fd)) and ($error!=1)) {
	
	
	$tietue = fgets($fd, 4096);
	$tunnus = substr($tietue, 0,1);
	
	if ($tunnus == '@') {
		$mail = 'jep';
		$mailiosoite = substr($tietue, 1);
	}
	
	if (($tyyppi == 'yct') and ($id != '') and ($tunnus != '@')) {	
		
		$tuoteno = substr($tietue, 0, 20);
		$maara = substr($tietue, 20, 20);
		$rivihinta = substr($tietue, 40, 20);

		$tuoteno = trim($tuoteno);
		$maara = trim($maara);
		$rivihinta = trim($rivihinta);
		$ale = '15';
		
		
		
		if ($tuoteno != '') {
			$kplhinta = abs($rivihinta) / abs($maara);
		//echo "tuoteno:$tuoteno<br>$maara<br>$rivihinta<br>$ale<br>$kplhinta<br>";
		}
		
		$query = "SELECT tuoteno FROM tuote
					WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0 and $tuoteno != '') {
			echo "".t("Tuotenumeroa")." $tuoteno ".t("ei löytynyt korjaa asia muuten ei tapahdu mitään")."!";
			$tilanne = '0';
			break;
		}
		
		else if ($tuoteno != '') {

			$tilanne = '1';
			echo "".t("Uusi rivi")." $id\t$tuoteno\t$maara\t$kplhinta\t$ale\t$rivihinta<br>";
			
			$query = "SELECT * FROM tuote
					WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
			$result = mysql_query($query) or pupe_error($query);
			$trow = mysql_fetch_array($result);
			
			$loyty = 0;
			$alue = 'Y00';
			
			if ($trow['ei_saldoa'] == '') {
				$query = 	"SELECT * FROM tuotepaikat
							WHERE yhtio='$trow[yhtio]' and tuoteno='$trow[tuoteno]' 
							and hyllyalue = 'Y00' and hyllynro	= '0' and hyllytaso	= '0' and hyllyvali	= '0' and saldo > 0";
				$result = mysql_query($query) or pupe_error($query);
				
				if (mysql_num_rows($result) == 0) {
					$loyty = 0;
				}
				else {
					$alue = 'Y00';
					$loyty = 1;
				}
				
				if ($loyty == 0) {
					$query = 	"SELECT * FROM tuotepaikat
								WHERE yhtio='$trow[yhtio]' and tuoteno='$trow[tuoteno]' 
								and hyllyalue = 'Y01' and hyllynro	= '0' and hyllytaso	= '0' and hyllyvali	= '0' and saldo > 0";
					$result = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($result) == 0) {
						$loyty = 0;
					}
					else {
						$alue = 'Y01';
						$loyty = 1;
					}
				}
				
				if ($loyty == 0) {
					$query = 	"SELECT * FROM tuotepaikat
								WHERE yhtio='$trow[yhtio]' and tuoteno='$trow[tuoteno]' 
								and hyllyalue = 'Y00' and hyllynro	= '0' and hyllytaso	= '0' and hyllyvali	= '0'";
					$result = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($result) == 0) {
						$loyty = 0;
					}
					else {
						$alue = 'Y00';
						$loyty = 1;
					}
				}
				
				if ($loyty == 0) {
					$query = 	"SELECT * FROM tuotepaikat
								WHERE yhtio='$trow[yhtio]' and tuoteno='$trow[tuoteno]' 
								and hyllyalue = 'Y01' and hyllynro	= '0' and hyllytaso	= '0' and hyllyvali	= '0'";
					$result = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($result) == 0) {
						$loyty = 0;
					}
					else {
						$alue = 'Y01';
						$loyty = 1;
					}
				}
				
				if ($loyty == 0) {
					
					$alue = 'Y00';
					
					echo "".t("Perustetaan tuotepaikka")." $alue-0-0-0 ".t("tuotteelle")." $trow[tuoteno]<br>";
					$query = "	INSERT into tuotepaikat set
								hyllyalue	= '$alue',
								hyllynro	= '0',
								hyllytaso	= '0',
								hyllyvali	= '0',
								tuoteno		= '$trow[tuoteno]',
								yhtio		= '$trow[yhtio]'";
					$result = mysql_query($query) or pupe_error($query);
				}

			}
			$query = "	INSERT into tilausrivi set
						varattu 		= '$maara',
						tilkpl 			= '$maara',
						jt				= '0',
						otunnus 		= '$id',
						var				= '',
						keratty			= '',
						kerattyaika		= '',
						kerayspvm 		= now(),
						laatija			= '$kukarow[kuka]',
						laadittu		= now(),
						toimitettu		= '',
						toimitettuaika	= '',
						toimaika 		= now(),
						laskutettu		= '',
						laskutettuaika	= '',
						yhtio 			= '$trow[yhtio]',
						tuoteno 		= '$trow[tuoteno]',
						ale 			= '$ale',
						netto 			= '$trow[netto]',
						yksikko 		= '$trow[yksikko]',
						try 			= '$trow[try]',
						osasto 			= '$trow[osasto]',
						kpl 			= '0',
						alv 			= '$trow[alv]',
						hinta 			= '$kplhinta',
						nimitys 		= '$trow[nimitys]',
						tyyppi 			= 'L',
						kommentti 		= '$trow[kommentti]'";
	
			//if ($trow['ei_saldoa']!='') {
			//	$query .= ", kerattyaika = now()";
			//}
			if ($trow['ei_saldoa'] == '') {	
				$query .= ", hyllyalue	= '$alue'";
				$query .= ", hyllynro	= '0'";
				$query .= ", hyllytaso	= '0'";
				$query .= ", hyllyvali	= '0'";
			}
			$rivperustettu += 1;
	
			$result = mysql_query($query) or pupe_error($query);
		}
	}
	
	else {	
		switch ($tunnus) {
			case '1' :
				echo "<hr>";
				$asno = substr($tietue, 19, 6);
				echo "".t("Tilaus").": ".t("asiakas")." '" . $asno .  "' ";
	
				$query = "SELECT ytunnus FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and ytunnus='$asno'";
				$result = mysql_query($query) or pupe_error($query);
	
				if (mysql_num_rows($result) == 0) {
					$query = "INSERT into asiakas
								SET yhtio='$kukarow[yhtio]', ytunnus='$asno',
									nimi = '$asno'";
					$result = mysql_query($query) or pupe_error($query);
					$aperustettu += 1;
				}
				$query = "SELECT * FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and ytunnus='$asno'";
				$result = mysql_query($query) or pupe_error($query);
	
				$asiakasrow = mysql_fetch_array($result);
	
				$tnimi = $asiakasrow['nimi'];
				$tnimitark = $asiakasrow['nimitark'];
				$tosoite = $asiakasrow['osoite'];
				$tpostino = $asiakasrow['postino'];
				$tpostitp = $asiakasrow['postitp'];
				$toim_maa = $asiakasrow['maa'];
	
				$query = "INSERT into lasku SET
					laatija = '$kukarow[kuka]',
					luontiaika=NOW(),
					ytunnus= '$asiakasrow[ytunnus]',
					nimi = '$asiakasrow[nimi]',
					nimitark = '$asiakasrow[nimitark]',
					osoite = '$asiakasrow[osoite]',
					postino = '$asiakasrow[postino]',
					postitp = '$asiakasrow[postitp]',
					maa = '$asiakasrow[maa]',
					toim_nimi = '$tnimi',
					toim_nimitark = '$tnimitark',
					toim_osoite = '$tosoite',
					toim_postino = '$tpostino',
					toim_postitp = '$tpostitp',
					toim_maa = '$toim_maa',
					verkkotunnus='$asiakasrow[verkkotunnus]',
					tila = 'L',
					alatila = 'A',
					toimaika = '$toimpvm',
					kerayspvm = '$toimpvm',
					maksuehto = '$asiakasrow[maksuehto]',
					toimitustapa = '$asiakasrow[toimitustapa]',
					myyja = '$kukarow[tunnus]',
					alv = '',
					comments = '$comments',
					ovttunnus = '$asiakasrow[ovttunnus]',
					toim_ovttunnus = '$asiakasrow[toim_ovttunnus]',
					liitostunnus='$asiakasrow[tunnus]',
					viesti = '$viesti',
					chn='$asiakasrow[chn]',
					yhtio = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				$id = mysql_insert_id();
				echo "".t("nro")." $id ";
				break;
			case '2' :
				$tuoteno = substr($tietue, 4, 15);
				$nimitys = substr($tietue, 19, 80);
				$maara = substr($tietue, 99, 10);
				$hinta = substr($tietue, 109, 10);
				$alv = substr($tietue, 119, 5);
	//			echo "Tuote: '" . $tuoteno .  "' '" . $nimitys .  "'<br>";
	//			echo "Maara: '" . $maara .  "' Hinta '" . $hinta .  "' Alv '" . $alv . "'<br>";
	
				$query = "SELECT tuoteno FROM tuote
							WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
				$result = mysql_query($query) or pupe_error($query);
	
				//$tuoteno = $tuoteno * 1;
	
				if (mysql_num_rows($result) == 0) {
					$query = "INSERT into tuote
								SET yhtio='$kukarow[yhtio]', tuoteno='$tuoteno',
									nimitys = '$nimitys', myyntihinta = $hinta, yksikko = 'kpl'";
					$result = mysql_query($query) or pupe_error($query);
					$tperustettu += 1;
				}
				$query = "SELECT * FROM tuote
							WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
				$result = mysql_query($query) or pupe_error($query);
				$trow = mysql_fetch_array($result);
	
				$query = "INSERT into tilausrivi set
					laatija = '$kukarow[kuka]',
					laadittu = now(),
					yhtio = '$kukarow[yhtio]',
					tuoteno = '$tuoteno',
					varattu = $maara,
					yksikko = '$trow[yksikko]',
					kpl = 0,
					alv = $alv,
					hinta = $hinta,
					kerayspvm = '$toimpvm',
					nimitys = '$nimitys',
					otunnus = '$id',
					tyyppi = 'L',
					toimaika = '$toimpvm'";
	
				if ($trow['ei_saldoa']!='')
					$query .= ", kerattyaika = now()";
	
				$rperustettu += 1;
	
				$result = mysql_query($query) or pupe_error($query);
				break;
			
			case 'A' :
				echo "<hr>";
				$asno = substr($tietue, 19, 6);
				echo "".t("Tilaus").": ".t("asiakas")." '" . $asno .  "' ";
	
				$query = "SELECT ytunnus FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and ytunnus='$asno'";
				$result = mysql_query($query) or pupe_error($query);
	
				if (mysql_num_rows($result) == 0) {
					$query = "INSERT into asiakas
								SET yhtio='$kukarow[yhtio]', ytunnus='$asno',
									nimi = '$asno'";
					$result = mysql_query($query) or pupe_error($query);
					$aperustettu += 1;
				}
				$query = "SELECT * FROM asiakas
							WHERE yhtio='$kukarow[yhtio]' and ytunnus='$asno'";
				$result = mysql_query($query) or pupe_error($query);
	
				$asiakasrow = mysql_fetch_array($result);
	
				$tnimi = $asiakasrow['nimi'];
				$tnimitark = $asiakasrow['nimitark'];
				$tosoite = $asiakasrow['osoite'];
				$tpostino = $asiakasrow['postino'];
				$tpostitp = $asiakasrow['postitp'];
				$toim_maa = $asiakasrow['maa'];
	
				$query = "INSERT into lasku SET
					laatija = '$kukarow[kuka]',
					luontiaika=NOW(),
					ytunnus= '$asiakasrow[ytunnus]',
					nimi = '$asiakasrow[nimi]',
					nimitark = '$asiakasrow[nimitark]',
					osoite = '$asiakasrow[osoite]',
					postino = '$asiakasrow[postino]',
					postitp = '$asiakasrow[postitp]',
					maa = '$asiakasrow[maa]',
					toim_nimi = '$tnimi',
					toim_nimitark = '$tnimitark',
					toim_osoite = '$tosoite',
					toim_postino = '$tpostino',
					toim_postitp = '$tpostitp',
					toim_maa = '$toim_maa',
					verkkotunnus='$asiakasrow[verkkotunnus]',
					tila = 'L',
					alatila = 'A',
					toimaika = '$toimpvm',
					kerayspvm = '$toimpvm',
					maksuehto = '$asiakasrow[maksuehto]',
					toimitustapa = '$asiakasrow[toimitustapa]',
					myyja = '$kukarow[tunnus]',
					alv = '',
					comments = '$comments',
					ovttunnus = '$asiakasrow[ovttunnus]',
					toim_ovttunnus = '$asiakasrow[toim_ovttunnus]',
					viesti = '$viesti',
					liitostunnus='$asiakasrow[tunnus]',
					chn='$asiakasrow[chn]',
					yhtio = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				$id = mysql_insert_id();
				echo "".t("nro")." $id ";
				break;
			
			default:
				echo "".t("luotiin")." $rperustettu ".t("rivia ok")."!<br>";
		}
	}
}
if (($tyyppi == 'yct') and ($id != '')) {
	if ($tilanne == '0') {
		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa tilaus_in.inc")."<br>";
	
		$query = "	UPDATE lasku set
					tila		= 'D',
					alatila		= 'L',
					comments = '$komm'
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$id'";
		$result = mysql_query($query) or pupe_error($query);
		
		$query = "	UPDATE tilausrivi set
					tyyppi		= 'D'
					WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$id'";
		$result = mysql_query($query) or pupe_error($query);
		
		echo "".t("Ei voitu tehdä tilausta loppuun vaan se mitätöitiin")."!";
	}
	else {
		require("../tilauskasittely/tilaus-valmis.inc");
		echo "".t("luotiin")." $rivperustettu ".t("rivia ok")."!<br>";
		//echo "<br>mail: $mail";
		//echo "<br>tunnus: $tunnus";
		//echo "<br>osoite: $mailiosoite";
		//echo "<br>id: $id";
		if ($mail == 'jep') {
			$rivi = '';
			$rivi .= "".t("Tuoteno")."\t".t("määrä")."\t".t("oletussaldo")."\t".t("hälytysraja")."\t".t("oletuspaikka")."\r\n";
			
			$query = "SELECT tuoteno, tilkpl FROM tilausrivi
					WHERE yhtio='$kukarow[yhtio]' and otunnus='$id'";
			$resulttil = mysql_query($query) or pupe_error($query);
			//echo "<br>$query";
			while ($tilrow = mysql_fetch_array ($resulttil)) {
				$tuoteno = $tilrow['tuoteno'];
				//echo "<br>tuoteno: $tuoteno";
				$query = "SELECT halytysraja FROM tuotepaikat
					 	 WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and hyllyalue = 'Y01'"; 
				$resultyc = mysql_query($query) or pupe_error($query);
			
				if (mysql_num_rows($resultyc) != 0) {
					$trowyc = mysql_fetch_array($resultyc);	
					$hraja = $trowyc['halytysraja'];
				}
				else {
					$hraja = '0';
				}
				
				$query = "SELECT saldo, concat_ws('-',hyllyalue,hyllynro,hyllytaso,hyllyvali) alue FROM tuotepaikat
						  WHERE yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno' and hyllyalue != 'Y01' and oletus = 'X' and saldo > '0'"; 
				$resultmuu = mysql_query($query) or pupe_error($query);	
		
				if (mysql_num_rows($resultmuu) != 0) {
					$trowmuu = mysql_fetch_array($resultmuu);
					$oletsaldo = $trowmuu['saldo'];
					$alue = $trowmuu['alue'];
				}
				else {
					$oletsaldo = '0';
					$alue = 'eiole';
				}
				
				$tilrow['tilkpl'] = str_replace('.',',',$tilrow['tilkpl']);
				$oletsaldo = str_replace('.',',',$oletsaldo);
				$hraja = str_replace('.',',',$hraja);
				
				$rivi .= "$tilrow[tuoteno]\t$tilrow[tilkpl]\t$oletsaldo\t$hraja\t$alue\r\n";
			}
			echo "<br>".t("Lähetetään meili osoitteeseen")." $mailiosoite";
			$bound = uniqid(time()."_") ;
			$ny = date("d-m-Y");
			
			$header   = "From: <$yhtiorow[postittaja_email]>\r\n";
	        $header  .= "MIME-Version: 1.0\r\n" ;
	        $header  .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;
			
			$content = "--$bound\r\n";

			$content .= "Content-Type: application/vnd.ms-excel; name=\"Excel-".t("tarveraportti")."$ny.xls\"\r\n" ;
			$content .= "Content-Transfer-Encoding: base64\r\n" ;
			$content .= "Content-Disposition: attachment; filename=\"Excel-".t("tarveraportti")."$ny.xls\"\r\n\r\n";
			
			$content .= chunk_split(base64_encode($rivi));
			$content .= "\r\n" ;
			
			$content .= "--$bound\r\n";
			
			$boob = mail($mailiosoite, "".t("tarveraportti")."".date("d.m.Y"), $content, $header);
		}
	}
}
if ($error==1) echo "<font class='error'>".t("Käsittelyssä tapahtui virhe")."</font><br>";

fclose ($fd);
echo "</pre>";
?>
