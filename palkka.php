<?php
require ("inc/parametrit.inc");
enable_ajax ();

if ($tee == "tiedosto") {
	
}
	echo "<font class='head'>";
	echo t ( "Palkanlaskun siirtotiedoston luonti" ), ":</font><hr>";


// tää on tällänän kikka.. älkää seotko.. en jaksa pyärittää toimia joka formista vaikka pitäs..
$PHP_SELF = $PHP_SELF . "?toim=$toim";

$kuka = $_POST[kuka];
$nimi = $_POST[nimi];
$postiaika = $_POST[vuosi]."-".$_POST[kk]."-".$_POST[pv];

	//poista tai muuta
		if (($_POST[rivimuuta] == "Muuta" or $_POST[kuittaa] == "Kuittaa" )  and $_POST[tunnus] != "") {
			if ($_POST[kuittaa] == "Kuittaa"){
				$kuittaus = $kukarow[kuka];
				$hyvaksy = "hyvaksytty";
			}
			$query = "UPDATE palkka	set tyolaji = '$_POST[tyolaji]', palkkalaji = '$_POST[palkkalaji]', maara = '$_POST[maara]', hinta = '$_POST[hinta]' ,
					pvmalku = '$_POST[pvmalku]'	, pvmloppu = '$_POST[pvmloppu]', aikaalku = '$_POST[aikaalku]', aikaloppu = '$_POST[aikaloppu]', kuittaus = '$kuittaus', tyyppi = '$hyvaksy' 			
					WHERE tunnus = '$_POST[tunnus]'";
					$kukarivit = mysql_query ( $query ) or pupe_error ( $query );
						
		}
		if ($_POST[rivipoista] == "Poista" and $_POST[tunnus] != "")	{		
				$query = "	DELETE 
						FROM palkka					
						WHERE tunnus = '$_POST[tunnus]'";
						$kukarivit = mysql_query ( $query ) or pupe_error ( $query );
						unset($kukarivit);
		}




	
	if ($toimi == "LISAA"){
		
		$hinta = trim($_POST[hinta]);
		$hinta = str_replace(",",".",$hinta);
		
		if ($hinta == "" or $hinta == 0){
			$hinta = $kukarow[palkka];
		}
		
		$maara = trim($_POST[maara]);
		$maara = str_replace(",",".",$maara);
		
		$alkoiklo = trim($_POST[alkoiklo]);
		$alkoiklo = str_replace(".",":",$alkoiklo);
		$alkoiklo = str_replace(",",":",$alkoiklo);
		
		if ($alkoiklo == ""){
			$alkoiklo = 0;
		}
		$alkoikloarray = explode(":",$alkoiklo);
		if ($alkoikloarray[1] == ""){
			$alkoikloarray[1] = "00:00";			
		}else{$alkoikloarray[1] = $alkoikloarray[1].":00";}
		$alkoiklo = $alkoikloarray[0].":".$alkoikloarray[1];
		
		$loppuiklo = trim($_POST[loppuiklo]);
		$loppuiklo = str_replace(".",":",$loppuiklo);
		$loppuiklo = str_replace(",",":",$loppuiklo);
		if ($loppuiklo == ""){
			$loppuiklo = 0;
		}
		$loppuikloarray = explode(":",$loppuiklo);
		if ($loppuikloarray[1] == ""){
			$loppuikloarray[1] = "00:00";			
		}else{$loppuikloarray[1] = $loppuikloarray[1].":00";}
		$loppuiklo =$loppuikloarray[0].":".$loppuikloarray[1];
		
		
		$lpvm = $apvm = strtotime($_POST[pvm]." 00:00:00");
		$apvm = strtotime('+'. $alkoiklo .'hours', $apvm);
		$lpvm = strtotime('+'. $loppuiklo .'hours', $lpvm);	
		$alkustringit = date('Y-m-d', $apvm);
		#$loppustringit = date('Y-m-d H:i', $lpvm);		

		
		$luonti = date("Y-m-d H:i:s");
		
		switch ($_POST[palkkalaji]) {
	    case "tuntipalkka":
	        $palkkakoodi = "002";
	        break;
	    case "urakkapalkka":
	        $palkkakoodi = "001";
	        break;
	    case "arkipyhakorvaus":
	        $palkkakoodi = "601";
	        break;
	    case "kokopaivaraha":
	        $palkkakoodi = "801";
	        break;
	    case "puolipaivaraha":
	        $palkkakoodi = "802";
	        break;    
	    default:
	        $palkkakoodi = "002";
	}
		$query = "	INSERT INTO palkka (yhtio, kuka, laatija, luontiaika, henkilo, palkkakoodi, palkkalaji, tyolaji, maara, hinta, pvmalku,  aikaalku, aikaloppu, tyyppi)
					VALUES ('$kukarow[yhtio]','$kuka','$kukarow[kuka]','$luonti','$nimi','$palkkakoodi','$_POST[palkkalaji]','$_POST[tyolaji]','$maara','$hinta','$_POST[pvm]','$alkoiklo','$loppuiklo','palkka')			
					";
		$kukares = mysql_query ( $query ) or pupe_error ( $query );
		$maara = '';
		$hinta = '';
		$apvm = '';
		$lpvm = '';
		$alkoiklo = '';
		$loppuiklo = '';
		
		
		#if ($kukarivit){$laskuri = 1;}
	}#<<<<<<<<if ($toimi == "LISAA")
	
	#Jos palkansaajaa ei ole vielä valittu, haetaan nimet ja palkka>>>>>>>>>>>>>>>
	if ($kuka == ""){
	$query = "	SELECT *
					FROM kuka					
					WHERE  yhtio = '$kukarow[yhtio]'
					and  tunnus = '$_POST[selkuka]'";
	$kukares = mysql_query ( $query ) or pupe_error ( $query );
	$kukarow1 = mysql_fetch_array ( $kukares );
	$kuka = $kukarow1[kuka];
	$nimi = $kukarow1[nimi];
	$palkka = $kukarow1[palkka];
	}#<<<<<<<<<<<<<<<<<Jos palkansaajaa ei ole vielä valittu
	
			
			$date = new DateTime('now');
			$vuosi = $date->format('Y');
			$kk = $date->format('m');
			$pv = $date->format('d');
			
			$date->modify('first day of this month');
			$alkupv = $date->format('d');
			$alkukk = $date->format('m');
			
			$date->modify('last day of this month');
			$loppukk = $date->format('m');
			$loppupv = $date->format('d');
		
		

	
	echo "<table>";		
	echo "	<tr>
			<th align='left'>" . ( "Palkkakausi" ) . "</th>
			<th align='left'>" . ( "PV" ) . "</th>
			<th align='left'>" . ( "KK" ) . "</th>
			<th align='left'>" . ( "VVVV" ) . "</th>
				</tr>";	
	echo "<form name='vaihda' action='$PHP_SELF' method='post' >";
	echo "<input type='hidden' name='tee' value='MUUTA'>";
	echo "<tr>  <td>Palkkakausi alku</td>
				<td><input name='alkupv' value='$alkupv' size='3' type='text'></td>
				<td><input name='alkukk' value='$alkukk' size='3' type='text'></td>
				<td><input name='vuosi' value='$vuosi' size='5' type='text'></td>
				";

	
	#echo "<form name='vaihda' action='$PHP_SELF' method='post' >";
	#echo "<input type='hidden' name='tee' value='MUUTA'>";
	echo "<tr>  <td>Palkkakausi loppu</td>
				<td><input name='loppupv' value='$loppupv' size='3' type='text'></td>
				<td><input name='loppukk' value='$loppukk' size='3' type='text'></td>
				<td><input name='vuosi' value='$vuosi' size='5' type='text'></td>
				";
				$muuta1 = $vuosi."-".$loppukk."-".$loppupv ;
				$muuta = strtotime ( $muuta1 );
				$maksupv = date ( 'd' , strtotime ( '-1 weekdays', $muuta) );
				$maksukk = date ( 'm' , strtotime ( '-1 weekdays', $muuta) );
				echo "<tr>  <td>Maksupäivä</td>
				<td><input name='maksupv' value='$maksupv' size='3' type='text'></td>
				<td><input name='loppukk' value='$maksukk' size='3' type='text'></td>
				<td><input name='vuosi' value='$vuosi' size='5' type='text'></td>
				<td align='left'><input name='vaihdaaika' value ='Vaihda aika' type='submit'></td></tr></form>";
				
	;echo "</table>";echo "<table>";

	$pkausialku =$alkupv.".".$alkukk.".".$vuosi;
	$pkausiloppu =$loppupv.".".$loppukk.".".$vuosi;
	$maksupvm =$maksupv.".".$maksukk.".".$vuosi;
	
	if ($_POST[rivimuuta] = "" or $_POST[tee] = "MUUTA"){
		#$pvm = new DateTime("$vuosi-$kk-$pv");
	}

	if ($_POST[tee] == "MUUTA"){
		$laskuri = 2;		
	}
	$hinta = "";
	$maara = "";

	
	$query = "	SELECT * FROM palkka WHERE yhtio = '$kukarow[yhtio]' 
	 and  pvmalku >= '$pkausialku' and pvmalku <= '$pkausiloppu'   AND kuittaus = '' 	ORDER BY kuka, pvmalku, aikaalku ASC";
	$kukarivit = mysql_query ( $query ) or pupe_error ( $query );
	
#>>>>>>>näytä tehdyt rivit ja muuta kuittaamattomat
echo "<br><br><br>";
echo "<table >";
echo "<tr> <th>Kuittaus</th>  <th>Työntekijä</th><th>Palkkalaji</th><th>Työlaji</th><th>Määrä</th><th>Hinta</th><th></th><th>Pvm</th><th>Alkoi</th><th>Loppui</th> <th>Tila</th> </tr>";
// keeps getting the next row until there are no more to get
while($kukarivi = mysql_fetch_array( $kukarivit )) {
	// Print out the contents of each row into a table, palkkarivit
	$alku = date('G:i',strtotime($kukarivi[aikaalku]));
	$loppu = date('G:i',strtotime($kukarivi[aikaloppu]));

	echo "<form name='muutarivi' action='$PHP_SELF' value='1' method='post' >
			<tr class='aktiivi'><td> $kukarivi[kuittaus]</td><td> $kukarivi[kuka]</td><td><input type='text' name='tyolaji' value=$kukarivi[tyolaji] ></td><td><input type='text' name='palkkalaji' value=$kukarivi[palkkalaji] >
			</td><td><input type='text' size='8' name='maara' value=$kukarivi[maara] ></td>";

				$nayttohinta = $kukarivi[hinta];
			
			echo "<td><input type='text' size='8' name='hinta' value= '$kukarivi[hinta]' ></td><td>";
			
			$vkpv = date('D', strtotime($kukarivi[pvmalku]));
				switch ($vkpv) {
			    case "Mon":
			       $vkpv = "Ma";
			        break;
			    case "Tue":
			       $vkpv = "Ti";
			        break;
			    case "Wed":
			        $vkpv = "Ke";
			        break;
			    case "Thu":
			        $vkpv = "To";
			        break;
		        case "Fri":
		        	$vkpv = "Pe";
		        break;
		        case "Sat":
		        	$vkpv = "La";
		        break;
		        case "Sun":
		        	$vkpv = "Su";
		        break;
				}
			echo "$vkpv</td><td>";
			echo "<input type='text' size='10' name='pvmalku' value='$kukarivi[pvmalku]'></td><td>";
			
			echo "<input type='text' size='5'  name='aikaalku' value='$alku'>";
			#echo date('d.m.Y G:i', strtotime($kukarivi[pvmalku]));
			echo "</td><td>";
			echo "<input type='date' size='5' name='aikaloppu' value='$loppu'>";
			#echo date('d.m.Y G:i', strtotime($kukarivi[pvmloppu]));
			echo "<input type='hidden' name='tunnus' value = '$kukarivi[tunnus]'></td><td> $kukarivi[tyyppi]</td><td class='back' nowrap=''>
			<input type='hidden' name='tee' value='MUUTA'>
			
			
			<input type='hidden' name='kuka' value = '$kuka'>
			<input type='hidden' name='nimi' value = '$nimi'>
			<input type='hidden' name='palkka' value = '$palkka'>
			<input type='submit' name='rivimuuta' value = 'Muuta'>
			<input type='submit' name='rivipoista' value = 'Poista'>";
			
			
				echo "<input type='submit' name='kuittaa' value = 'Kuittaa'>";
			
			echo "</td></tr></form>";
			
	
}

if ($tee == "tiedosto") {	
		
		$myfile = fopen("/var/www/html/pupesoft/dataout/palkat_".$vuosi.$kk.$pv.".csv", "w") or die("Unable to open file!");
		fwrite($myfile, "\xEF\xBB\xBF"); 
		$txt = utf8_encode("Henkilötunnus;Kauden alkupvm;Kauden päät.pvm;Maksupvm;Palkkalaji;Määrä;A-hinta;Summa\r\n");
		fwrite($myfile, $txt);
		
		$query = "	SELECT sotu, '$pkausialku' AS pkausialku, '$pkausiloppu' AS pkausiloppu, '$maksupvm' AS maksupvm, palkkakoodi, sum(maara) as maara, palkka.hinta, '' as summa, palkka.kuka 
					FROM  palkka, kuka where palkka.yhtio = '$kukarow[yhtio]'  AND kuka.yhtio = '$kukarow[yhtio]'  AND palkka.kuka = kuka.kuka
					AND  pvmalku >= '$pkausialku' AND pvmalku <= '$pkausiloppu'  AND tyyppi = 'hyvaksytty' AND kuittaus != '' group BY kuka, palkkakoodi, hinta";
												     #AND tyyppi = 'hyvaksytty' AND kuittaus != ''

		
		$kukarivit = mysql_query ( $query ) or pupe_error ( $query );
		while($kukarivi = mysql_fetch_array( $kukarivit )) {
			#$txt = $kukarivi[kuka].";".$kukarivi[sotu].";".$kukarivi[pkausialku].";".$kukarivi[pkausiloppu].";".$kukarivi[maksupvm].";".$kukarivi[palkkakoodi].";".str_replace(".",",",$kukarivi[maara]).";".str_replace(".",",",$kukarivi[hinta]).";".str_replace(".",",",$kukarivi[summa]). "\r\n";
			$txt = $kukarivi[sotu].";".$kukarivi[pkausialku].";".$kukarivi[pkausiloppu].";".$kukarivi[maksupvm].";".$kukarivi[palkkakoodi].";".str_replace(".",",",$kukarivi[maara]).";".str_replace(".",",",$kukarivi[hinta]).";".str_replace(".",",",$kukarivi[summa]). "\r\n";
		fwrite($myfile, $txt);
			
			
		}
			
		if (fclose($myfile)) {
			echo "Palkansiirtotiedosto tehty.";

			$query = "	Update  palkka set tyyppi = 'lukittu' where palkka.yhtio = '$kukarow[yhtio]'  
					AND  pvmalku >= '$pkausialku' AND pvmalku <= '$pkausiloppu'  AND tyyppi = 'hyvaksytty' AND kuittaus != '' ";
												     

		
		$kukarivit = mysql_query ( $query ) or pupe_error ( $query );
			
		}else{
			echo "Palkansiirtotiedosto epäonnistui, jotain pielessä!";
		}

}

echo "</table>";
	echo "<form name='palkkafile' action='$PHP_SELF' method='post' >";
	echo "<br>"	;
	echo "<tr><td><input type='hidden' name='pv' value='$pv' size='3' type='text'></td>
				<td><input type='hidden' name='tee' value='tiedosto' size='3' type='text'></td>
				<td><input type='hidden' name='kk' value='$kk' size='3' type='text'></td>
				<td><input type='hidden' name='vuosi' value='$vuosi' size='5' type='text'></td>
				<td align='left'><input name='palkat' value ='OK tee tiedosto' type='submit'></td></tr></form>";
	



require ("../inc/footer.inc");
?>

