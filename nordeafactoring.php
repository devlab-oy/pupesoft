<?php
	
	if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
	if($_POST["kaunisnimi"] != '') $kaunisnimi=$_POST["kaunisnimi"];
	
	require('inc/parametrit.inc');
	
	if ($tee == "lataa_tiedosto") {
		readfile("dataout/".$filenimi);
	}
	else {
		echo "<font class='head'>".t("Nordea Factoring siirtotiedosto").":</font><hr><br>";
	}
	
	if ($tee == '') {
		//Käyttöliittymä
		echo "<br>";
		echo "<table><form method='post' action='$PHP_SELF'>";
	
		echo "	<input type='hidden' name='tee' value='TULOSTA'>";
	
		$query = "	SELECT min(laskunro) eka, max(laskunro) vika
					FROM lasku
					JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.factoring='N'
					WHERE lasku.yhtio	= '$kukarow[yhtio]' 
					and lasku.tila	  	= 'U' 
					and lasku.alatila	= 'X' 
					and lasku.summa		!= 0
					and lasku.factoringsiirtonumero = 0";
		$aresult = mysql_query ($query) or pupe_error($query);
		$arow = mysql_fetch_array($aresult);
		
		echo "<tr>
				<th>Syötä laskuvälin alku:</th>
				<td><input type='text' name='ppa' value='$arow[eka]' size='10'></td>
				</tr>
				<tr>
				<th>Syötä laskuvälin loppu:</th>
				<td><input type='text' name='ppl' value='$arow[vika]' size='10'></td>
				</tr>";
		
		$query = "	SELECT max(factoringsiirtonumero)+1 seuraava
					FROM lasku
					WHERE  yhtio		= '$kukarow[yhtio]'
					and lasku.tila	  	= 'U' 
					and lasku.alatila	= 'X' 
					and lasku.summa		!= 0
					and lasku.factoringsiirtonumero > 0";
		$aresult = mysql_query ($query) or pupe_error($query);
		$arow = mysql_fetch_array($aresult);
				
		echo "<tr><th>Siirtoluettelon numero:</th>
				<td><input type='text' name='factoringsiirtonumero' value='$arow[seuraava]' size='6'></td>";
		
		
		echo "<td class='back'><input type='submit' value='Luo siirtoaineisto'></td></tr></table><br><br>";
	}
	
	if ($tee == 'TULOSTA') {

		$luontipvm	= date("Ymd");
		$luontiaika	= date("Hi");
				
		//Luodaan erätietue
		$ulos  = sprintf ('%-4.4s', 	"KRFL");									//sovellustunnus
		$ulos .= sprintf ('%01d',	 	"0");										//tietuetunnus
		$ulos .= sprintf ('%-17.17s', 	str_replace('-','',$yhtiorow["ytunnus"]));	//myyjän ytunnus
		$ulos .= sprintf ('%06d',	 	$luontipvm);								//aineiston luontipvm
		$ulos .= sprintf ('%04d',   	$luontiaika);								//luontikaika
		$ulos .= sprintf ('%06d',	 	$yhtiorow["factoringsopimus"]);				//sopimusnumero
		$ulos .= sprintf ('%-3.3s', 	$yhtiorow["valkoodi"]);						//valuutta
		$ulos .= sprintf ('%-2.2s', 	"MR");										//rahoitusyhtiön tunnus
		$ulos .= sprintf ('%-30.30s', 	$kukarow["nimi"]);							//siirtäjän nimi
		$ulos .= sprintf ('%06d', 		$factoringsiirtonumero);					//siirtoluettelon numero						
		$ulos .= sprintf ('%-37.37s', 	"");										//
		$ulos .= sprintf ('%-63.63s', 	"");										//
		$ulos .= sprintf ('%-221.221s', "");										//
		$ulos .= "\r\n";
		
		
		if ($ppl == '') {
			$ppl = $ppa;
		}
		
		if ($ppa == '' or $ppl == '' or $ppl < $ppa) {
			echo "Huono laskunumeroväli!";
			exit;		
		}
		
		$dquery = "	SELECT lasku.yhtio 
					FROM lasku
					JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.factoring='N'
					WHERE lasku.yhtio	  = '$kukarow[yhtio]' 
					and lasku.tila	  = 'U' 
					and lasku.alatila	  = 'X' 
					and lasku.summa 	 != 0
					and lasku.laskunro >= '$ppa'
					and lasku.laskunro <= '$ppl'
					and lasku.factoringsiirtonumero = 0";
		$dresult = mysql_query ($dquery) or pupe_error($dquery);
		
		if (mysql_num_rows($dresult) == 0) {
			echo "Huono laskunumeroväli! Yhtään Nordeaan siirettävää laskua ei löytynyt!";
			exit;	
		}
	
		$query = "	SELECT if(lasku.summa >= 0, '01', '02') tyyppi,
					lasku.ytunnus,
					lasku.nimi,
					lasku.nimitark,
					lasku.osoite,
					lasku.postino,
					lasku.postitp,
					lasku.maakoodi,
					lasku.laskunro,
					round(lasku.viikorkopros*100,0) viikorkopros,
					round(abs(lasku.summa*100),0) summa,
					lasku.toim_nimi,
					lasku.toim_nimitark,
					lasku.toim_osoite,
					lasku.toim_postino,
					lasku.toim_postitp,
					lasku.toim_maa,
					lasku.viite,
					DATE_FORMAT(lasku.tapvm, '%y%m%d') tapvm,
					DATE_FORMAT(lasku.erpcm, '%y%m%d') erpcm,
					lasku.tunnus,
					lasku.valkoodi,
					lasku.liitostunnus
					FROM lasku
					JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.factoring='N'
					WHERE lasku.yhtio	= '$kukarow[yhtio]' 
					and lasku.tila	  	= 'U' 
					and lasku.alatila	= 'X' 
					and lasku.summa		!= 0
					and lasku.laskunro >= '$ppa'
					and lasku.laskunro <= '$ppl'
					and lasku.factoringsiirtonumero = 0";
		$laskures = mysql_query ($query) or pupe_error($query);
		
		if (mysql_num_rows($laskures) > 0) {
			
			$laskukpl  = 0;
			$vlaskukpl = 0;
			$vlaskusum = 0;
			$hlaskukpl = 0;
			$hlaskusum = 0;
			
			echo "<table>";
			echo "<tr><th>Tyyppi</th><th>Laskunumero</th><th>Nimi</th><th>Summa</th><th>Valuutta</th></tr>";
						
			while ($laskurow = mysql_fetch_array($laskures)) {
			
				$dquery = "	UPDATE lasku
							SET factoringsiirtonumero = '$factoringsiirtonumero'
							WHERE  yhtio	= '$kukarow[yhtio]' 
							and tunnus		= '$laskurow[tunnus]'";
				$dresult = mysql_query ($dquery) or pupe_error($dquery);
				
				
				$query  = "	SELECT *
							FROM asiakas
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$laskurow[liitostunnus]'";
				$asires = mysql_query($query) or pupe_error($query);
				$asirow = mysql_fetch_array($asires);
				
				//luodaan ostajatietue
				$ulos .= sprintf ('%-4.4s', 	"KRFL");										//sovellustunnus
				$ulos .= sprintf ('%01d',	 	"1");											//tietuetunnus
				$ulos .= sprintf ('%06d',	 	$yhtiorow["factoringsopimus"]);					//sopimusnumero
				$ulos .= sprintf ('%06d',	 	$asirow["asiakasnro"]);							//ostajan numero aka asiakasnumero
				$ulos .= sprintf ('%-4.4s', 	"");
				$ulos .= sprintf ('%-10.10s', 	str_replace('-','',$laskurow["ytunnus"]));		//ostajan ytunnus
				$ulos .= sprintf ('%-30.30s', 	$laskurow["nimi"]);								//ostajan nimi
				$ulos .= sprintf ('%-30.30s',  	$laskurow["nimitark"]);							//ostajan nimitark
				$ulos .= sprintf ('%-20.20s', 	$laskurow["osoite"]);							//ostajan osoite
				$ulos .= sprintf ('%-20.20s', 	$laskurow["postino"]." ".$laskurow["postitp"]);	//ostajan postino ja postitp
				$ulos .= sprintf ('%-13.13s', 	"");
				$ulos .= sprintf ('%-30.30s', 	"");
				$ulos .= sprintf ('%-13.13s', 	"");
				$ulos .= sprintf ('%-13.13s', 	"");
				$ulos .= sprintf ('%-2.2s', 	"FI");											//kieli
				$ulos .= sprintf ('%-3.3s', 	$laskurow["valkoodi"]);							//valuutta
				$ulos .= sprintf ('%04d', 		$laskurow["viikorkopros"]);						//viivastyskorko
				$ulos .= sprintf ('%03d', 		"0");
				$ulos .= sprintf ('%06d',   	"0");
				
				if ($laskurow["maa"] != $yhtiorow["maakoodi"] and $laskurow["maa"] != '') {
					$ulos .= sprintf ('%-10.10s', $laskurow["maa"]);
				}
				else {
					$ulos .= sprintf ('%-10.10s', 	"");
				}
				
				$ulos .= sprintf ('%-172.172s',"");
				$ulos .= "\r\n";
				
				//luodaan laskutietue
				$ulos .= sprintf ('%-4.4s', 	"KRFL");													//sovellustunnus
				$ulos .= sprintf ('%01d',	 	"3");														//tietuetunnus
				$ulos .= sprintf ('%06d',	 	$yhtiorow["factoringsopimus"]);								//sopimusnumero
				$ulos .= sprintf ('%06d',	 	$asirow["asiakasnro"]);										//ostajan numero aka asiakasnumero
				$ulos .= sprintf ('%-4.4s',   	"");														//varalla
				$ulos .= sprintf ('%010d',	 	$laskurow["laskunro"]);										//laskunro
				$ulos .= sprintf ('%06d',	 	$laskurow["tapvm"]);										//laskun päiväys
				$ulos .= sprintf ('%-3.3s', 	$laskurow["valkoodi"]);										//valuutta
				$ulos .= sprintf ('%06d', 		$laskurow["tapvm"]);										//laskun arvopäivä
				$ulos .= sprintf ('%02d', 		$laskurow["tyyppi"]);										//laskun tyyppi 01-veloitus 02-hyvitys 03-viivästyskorkolasku jne...
				$ulos .= sprintf ('%012d', 		$laskurow["summa"]);										//summa etumerkitön, sentteinä
				$ulos .= sprintf ('%06d', 		$laskurow["erpcm"]);										//eräpäivä
				$ulos .= sprintf ('%06d', 		"0");														//kassa-ale1 pvm
				$ulos .= sprintf ('%06d', 		"0");
				$ulos .= sprintf ('%06d', 		"0");
				$ulos .= sprintf ('%06d', 		"0");
				$ulos .= sprintf ('%012d',		"0");
				$ulos .= sprintf ('%012d', 		"0");														//kassa-ale1 valuutassa
				$ulos .= sprintf ('%012d',		"0");
				$ulos .= sprintf ('%012d', 		"0");
				$ulos .= sprintf ('%012d', 		"0");
				$ulos .= sprintf ('%024d',  	"0");
				$ulos .= sprintf ('%01d', 		"0");														//kassa-ale1 koodi 01-ei alennus 1-alennus
				$ulos .= sprintf ('%01d', 		"0");
				$ulos .= sprintf ('%01d', 		"0");
				$ulos .= sprintf ('%01d', 		"0");
				$ulos .= sprintf ('%02d',   	"0");
				$ulos .= sprintf ('%010d',  	"0");
				$ulos .= sprintf ('%04d',   	"0");														//alv (ei välitetä)
				$ulos .= sprintf ('%-30.30s', 	$laskurow["toim_nimi"]);									//toimituspaikan nimi
				$ulos .= sprintf ('%06d',	 	$asirow["asiakasnro"]);										//asiakasnro
				$ulos .= sprintf ('%010d', 		str_replace('-','',$laskurow["ytunnus"]));					//toim  ytunnus
				$ulos .= sprintf ('%-20.20s', 	$laskurow["toim_osoite"]);									//toim osoite
				$ulos .= sprintf ('%-20.20s', 	$laskurow["toim_postino"]." ".$laskurow["toim_postitp"]);	//toim postitp ja postino
				$ulos .= sprintf ('%-30.30s', 	"");
				$ulos .= sprintf ('%013d', 		"0");
				$ulos .= sprintf ('%-30.30s', 	"");
				$ulos .= sprintf ('%06d', 		"0");
				
				if ($laskurow["toim_maa"] != $yhtiorow["maakoodi"] and $laskurow["toim_maa"] != '') {
					$ulos .= sprintf ('%-10.10s', $laskurow["toim_maa"]);
				}
				else {
					$ulos .= sprintf ('%-10.10s', 	"");
				}
				
				$ulos .= sprintf ('%03d', 		"0");
				$ulos .= sprintf ('%020d', 	$laskurow["viite"]);
				$ulos .= sprintf ('%-8.8s', 	"");
				$ulos .= "\r\n";
			
				$laskukpl++;
				if ($laskurow["tyyppi"] == "01") {
					$vlaskukpl++;
					$vlaskusum += $laskurow["summa"];
					
					echo "<tr><td>Veloituslasku</td><td>$laskurow[laskunro]</td><td>$laskurow[nimi]</td><td align='right'>".($laskurow["summa"]/100)."</td><td>$laskurow[valkoodi]</td></tr>";
				}
				if ($laskurow["tyyppi"] == "02") {
					$hlaskukpl++;
					$hlaskusum += $laskurow["summa"];
					
					echo "<tr><td>Hyvityslasku:</td><td>$laskurow[laskunro]</td><td>$laskurow[nimi]</td><td align='right'>".($laskurow["summa"]/100)."</td><td>$laskurow[valkoodi]</td></tr>";
				}
			
			}
			
			//luodaan summatietue
			$ulos .= sprintf ('%-4.4s', 	"KRFL");
			$ulos .= sprintf ('%01d', 		"9");
			$ulos .= sprintf ('%-17.17s', 	str_replace('-','',$yhtiorow["ytunnus"]));
			$ulos .= sprintf ('%06d', 		$luontipvm);
			$ulos .= sprintf ('%04d',   	$luontiaika);
			$ulos .= sprintf ('%06d', 		$laskukpl);
			$ulos .= sprintf ('%06d', 		$vlaskukpl);
			$ulos .= sprintf ('%013d', 		$vlaskusum);
			$ulos .= sprintf ('%06d', 		$hlaskukpl);
			$ulos .= sprintf ('%013d', 		$hlaskusum);
			$ulos .= sprintf ('%06d', 		"0");
			$ulos .= sprintf ('%013d', 		"0");
			$ulos .= sprintf ('%06d', 		"0");
			$ulos .= sprintf ('%013d', 		"0");
			$ulos .= sprintf ('%013d', 		"0");
			$ulos .= sprintf ('%-273.273s',	"");
			$ulos .= "\r\n";
			
			//keksitään uudelle failille joku varmasti uniikki nimi:
			$filenimi = "Nordeasiirto-$factoringsiirtonumero.txt";
			
			//kirjoitetaan faili levylle..
			$fh = fopen($filenimi, "w");
			if (fwrite($fh, $ulos) === FALSE) die("Kirjoitus epäonnistui $filenimi");
			fclose($fh);
			
			echo "<tr><td class='back'><br></td></tr>";
			
			echo "<tr><td class='back' colspan='2'></td><th>Yhteensä $vlaskukpl veloituslaskua</th><td align='right'>".round($vlaskusum/100,2)."</td><td>$laskurow[valkoodi]</td></tr>";
			echo "<tr><td class='back' colspan='2'></td><th>Yhteensä $hlaskukpl hyvityslaskua</th><td align='right'> ".round($hlaskusum/100,2)."</td><td>$laskurow[valkoodi]</td></tr>";
			
			echo "</table>";
			echo "<br><br>";
			echo "<table>";
			echo "<tr><th>Tallenna siirtoaineisto levylle:</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='SOLOMYSA.DAT'>";
			echo "<input type='hidden' name='filenimi' value='$filenimi'>";
			echo "<td><input type='submit' value='Tallenna'></td></form>";
			echo "</tr></table>";
		}
		else {
			echo "<br><br>Yhtään siirrettävää laskua ei ole!<br>";
			$tee = "";
		}
	}
	
	if ($tee != "lataa_tiedosto") {
		require ("inc/footer.inc");
	}
?>
