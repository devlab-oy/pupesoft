<?php
	require('inc/parametrit.inc');
	
	echo "<font class='head'>Nordea Factoring siirtotiedosto:</font><hr><br>";
	
	if ($tee == '') {
		//Käyttöliittymä
		echo "<br>";
		echo "<table><form method='post' action='$PHP_SELF'>";
	
		echo "	<input type='hidden' name='tee' value='TULOSTA'>";
	
		echo "<tr>
				<th>Syötä laskuvälin alku</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				</tr>
				<tr>
				<th>Syötä laskuvälin loppu</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'>
				</td>
				</tr>";
		
		$query = "	SELECT max(factoringsiirtonumero) factoringsiirtonumero
					FROM lasku
					WHERE  yhtio='$kukarow[yhtio]'";
		$aresult = mysql_query ($query) or pupe_error("$query");
		$arow = mysql_fetch_array($aresult);
		
		$arow["factoringsiirtonumero"]++;
		
		echo "<tr><th>Siirtoluettelon numero:</th>
				<td><input type='text' name='factoringsiirtonumero' value='$arow[0]' size='6'></td>";
		
		
		echo "<td class='back'><input type='submit' value='Lähetä'></td></tr></table><br><br>";
	}
	
	if ($tee == 'TULOSTA') {

		$luontipvm	= date("Ymd");
		$luontiaika	= date("Hi");
		
		//luodaan eratietue
		
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
		
		
		if ($ppl == '')
			$ppl = $ppa;
		
		if ($ppa == '' or $ppl == '') {
			echo "Huono laskunumeroväli!";
			exit;		
		}	
	
		$kentat1 = "if(arvo >= 0, '01', '02') tyyppi,
					ytunnus,
					asiakasnro,
					nimi,
					nimitark,
					osoite,
					postino,
					postitp,
					laskunro,
					viivastyskorko,
					round(abs(summa*100),0) summa,
					toim_nimi,
					toim_nimitark,
					toim_osoite,
					toim_postino,
					toim_postitp,
					viite,
					DATE_FORMAT(tapvm, '%y%m%d') paivays,
					DATE_FORMAT(erpcm, '%y%m%d') erapvm,
					tunnus";
		
		$taulu1 = "lasku";
		$mitka1 = "yhtio='$kukarow[yhtio]' and tila='U' and alatila='X' 
					and laskunro >= '$ppa'
					and laskunro <= '$ppl'";
		
	
		$query = "SELECT $kentat1
				FROM   $taulu1
				WHERE  $mitka1";
		$bresult = mysql_query ($query) 
			or die ("Kysely ei onnistu $query");
		
		if (mysql_num_rows($bresult) > 0) {
			
			$laskukpl  = 0;
			$vlaskukpl = 0;
			$vlaskusum = 0;
			$hlaskukpl = 0;
			$hlaskusum = 0;
			$ulos1 = "";
			
			while ($brow = mysql_fetch_array($bresult)) {
			
				$dquery = "	UPDATE lasku
							SET factoringsiirtonumero='$factoringsiirtonumero'
							WHERE  yhtio='$kukarow[yhtio]' and tunnus='$brow[tunnus]'";
				$dresult = mysql_query ($dquery) 
					or die ("Kysely ei onnistu $dquery");
				//$drow = mysql_fetch_array($dresult);
				
				//luodaan ostajatietue
				$ulos1 .= sprintf ('%-4.4s', 	"KRFL");								//sovellustunnus
				$ulos1 .= sprintf ('%01d',	 	"1");									//tietuetunnus
				$ulos1 .= sprintf ('%06d',	 	$arow["rahoitussopnro"]);				//sopimusnumero
				$ulos1 .= sprintf ('%06d',	 	$brow["asiakasnro"]);					//ostajan numero aka asiakasnumero
				$ulos1 .= sprintf ('%-4.4s', 	"");
				$ulos1 .= sprintf ('%-10.10s', 	str_replace('-','',$brow["ytunnus"]));	//ostajan ytunnus
				$ulos1 .= sprintf ('%-30.30s', 	$brow["nimi"]);							//ostajan nimi
				$ulos1 .= sprintf ('%-30.30s',  $brow["nimitark"]);						//ostajan nimitark
				$ulos1 .= sprintf ('%-20.20s', 	$brow["osoite"]);						//ostajan osoite
				$ulos1 .= sprintf ('%-20.20s', 	$brow["postino"]." ".$brow["postitp"]);	//ostajan postino ja postitp
				$ulos1 .= sprintf ('%-13.13s', 	"");
				$ulos1 .= sprintf ('%-30.30s', 	"");
				$ulos1 .= sprintf ('%-13.13s', 	"");
				$ulos1 .= sprintf ('%-13.13s', 	"");
				$ulos1 .= sprintf ('%-2.2s', 	"FI");									//kieli
				$ulos1 .= sprintf ('%-3.3s', 	"EUR");									//valuutta
				$ulos1 .= sprintf ('%04d', 		round($brow["viivastyskorko"]*100,0));	//viivastyskorko
				$ulos1 .= sprintf ('%03d', 		"0");
				$ulos1 .= sprintf ('%06d',   	"0");
				$ulos1 .= sprintf ('%-10.10s', 	"");
				$ulos1 .= sprintf ('%-172.172s',"");
				$ulos1 .= "\r\n";
				
				//luodaan laskutietue
				$ulos1 .= sprintf ('%-4.4s', 	"KRFL");								//sovellustunnus
				$ulos1 .= sprintf ('%01d',	 	"3");									//tietuetunnus
				$ulos1 .= sprintf ('%06d',	 	$arow["rahoitussopnro"]);				//sopimusnumero
				$ulos1 .= sprintf ('%06d',	 	$brow["asiakasnro"]);					//ostajan numero aka asiakasnumero
				$ulos1 .= sprintf ('%-4.4s',   	"");
				$ulos1 .= sprintf ('%010d',	 	$brow["laskunro"]);
				$ulos1 .= sprintf ('%06d',	 	$brow["paivays"]);
				$ulos1 .= sprintf ('%-3.3s', 	"EUR");
				$ulos1 .= sprintf ('%06d', 		$brow["paivays"]);
				$ulos1 .= sprintf ('%02d', 		$brow["tyyppi"]);
				$ulos1 .= sprintf ('%012d', 	$brow["summa"]);
				$ulos1 .= sprintf ('%06d', 		$brow["erapvm"]);
				$ulos1 .= sprintf ('%06d', 		"0");
				$ulos1 .= sprintf ('%06d', 		"0");
				$ulos1 .= sprintf ('%06d', 		"0");
				$ulos1 .= sprintf ('%06d', 		"0");
				$ulos1 .= sprintf ('%012d',		"0");
				$ulos1 .= sprintf ('%012d', 	"0");
				$ulos1 .= sprintf ('%012d',		"0");
				$ulos1 .= sprintf ('%012d', 	"0");
				$ulos1 .= sprintf ('%012d', 	"0");
				$ulos1 .= sprintf ('%024d',  	"0");
				$ulos1 .= sprintf ('%01d', 		"0");
				$ulos1 .= sprintf ('%01d', 		"0");
				$ulos1 .= sprintf ('%01d', 		"0");
				$ulos1 .= sprintf ('%01d', 		"0");
				$ulos1 .= sprintf ('%02d',   	"0");
				$ulos1 .= sprintf ('%010d',  	"0");
				$ulos1 .= sprintf ('%04d',   	"0");
				$ulos1 .= sprintf ('%-30.30s', 	$brow["toim_nimi"]);								//toimituspaikan nimi
				$ulos1 .= sprintf ('%06d',	 	$brow["asiakasnro"]);								//asiakasnro
				$ulos1 .= sprintf ('%010d', 	str_replace('-','',$brow["toim_ytunnus"]));			//toim  ytunnus
				$ulos1 .= sprintf ('%-20.20s', 	$brow["toim_osoite"]);								//toim osoite
				$ulos1 .= sprintf ('%-20.20s', 	$brow["toim_postino"]." ".$brow["toim_postitp"]);	//toim postitp ja postino
				$ulos1 .= sprintf ('%-30.30s', 	"");
				$ulos1 .= sprintf ('%013d', 	"0");
				$ulos1 .= sprintf ('%-30.30s', 	"");
				$ulos1 .= sprintf ('%06d', 		"0");
				$ulos1 .= sprintf ('%-10.10s', 	"");
				$ulos1 .= sprintf ('%03d', 		"0");
				$ulos1 .= sprintf ('%020d', 	$brow["viite"]);
				$ulos1 .= sprintf ('%-8.8s', 	"");
				$ulos1 .= "\r\n";
			
				$laskukpl++;
				if ($brow["tyyppi"] == "01") {
					$vlaskukpl++;
					$vlaskusum += $brow["summa"];
					echo "Veloituslasku: Summa --> $brow[summa]<br>";
					
				}
				if ($brow["tyyppi"] == "02") {
					$hlaskukpl++;
					$hlaskusum += $brow["summa"];
					echo "Hyvityslasku: Summa ---> $brow[summa]<br>";
				}
			
			}
			
			echo "Yhteensä $vlaskukpl veloituslaskua ".round($vlaskusum/100,2)." EUR.<br>";
			echo "Yhteensä $hlaskukpl hyvityslaskua ".round($hlaskusum/100,2)." EUR.<br>";
			
			//luodaan summatietue
			$ulos2  = sprintf ('%-4.4s', 	"KRFL");
			$ulos2 .= sprintf ('%01d', 		"9");
			$ulos2 .= sprintf ('%-17.17s', 	str_replace('-','',$arow["ytunnus"]));
			$ulos2 .= sprintf ('%06d', 		$arow["luontipvm"]);
			$ulos2 .= sprintf ('%04d',   	$arow["luontiaika"]);
			$ulos2 .= sprintf ('%06d', 		$laskukpl);
			$ulos2 .= sprintf ('%06d', 		$vlaskukpl);
			$ulos2 .= sprintf ('%013d', 	$vlaskusum);
			$ulos2 .= sprintf ('%06d', 		$hlaskukpl);
			$ulos2 .= sprintf ('%013d', 	$hlaskusum);
			$ulos2 .= sprintf ('%06d', 		"0");
			$ulos2 .= sprintf ('%013d', 	"0");
			$ulos2 .= sprintf ('%06d', 		"0");
			$ulos2 .= sprintf ('%013d', 	"0");
			$ulos2 .= sprintf ('%013d', 	"0");
			$ulos2 .= sprintf ('%-273.273s',"");
			$ulos2 .= "\n";
			
			if ($kukarow["eposti"] != '') {
				$tietue = $ulos.$ulos1.$ulos2;
				
				$bound = uniqid(time()."_") ;
			
                require_once 'Mail.php';

                $headers = array(
                    'From' => '<siirto@victoria.fi>',
                    'To'   => $kukarow['eposti'],
                    'Subject' => 'Factoring siirto',
                    'MIME-Version' => '1.0',
                    //'Content-Type' => 'multipart/mixed; boundary="'.$bound.'"',
                    'Content-Type' => 'text/plain',
                    'Content-Transfer-Encoding' =>  '7bit',
                    'Content-Disposition' => 'attachment; filename="siirto.txt"'
                );

                $params = array(
                    'host' => 'smtp.eunet.fi'
                );

                $mail = Mail::factory('smtp', $params);

				if(PEAR::isError($mail->send($kukarow['eposti'], $headers, $tietue))) {
				    echo "<br>Tiedosto lähetettiin sähköpostiosoitteeseen {$kukarow['eposti']}.";
                }
			}
			else {
				echo "<br>Tiedostoa ei voitu lähettää! Sähköpostiosoitetta ei löydy!";
			}
		}
		else {
			echo "<br><br>Yhtään siirrettävää laskua ei ole!<br>";
			$tee = "";
		}
	}
	
	require ("inc/footer.inc");
?>
