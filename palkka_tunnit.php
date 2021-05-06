<?php
require ("inc/parametrit.inc");
enable_ajax ();

if ($toim == "TIEDOSTO") {
	echo "<font class='head'>";
	echo t ( "Palkanlaskun siirtotiedoston luonti" ), ":</font><hr>";
}else{
echo "<font class='head'>";
echo t ( "Työtuntien syöttö" ), ":</font><hr>";

// tää on tällänän kikka.. älkää seotko.. en jaksa pyärittää toimia joka formista vaikka pitäs..
$PHP_SELF = $PHP_SELF . "?toim=$toim";

$kuka = $_POST[kuka];
$nimi = $_POST[nimi];
$postiaika = $_POST[vuosi]."-".$_POST[kk]."-".$_POST[pv];

	//poista tai muuta
		if (($_POST[rivimuuta] == "Muuta" or $_POST[kuittaa] == "Kuittaa" )  and $_POST[tunnus] != "") {
			if ($_POST[kuittaa] == "Kuittaa"){
				$kuittaus = $kukarow[kuka];
			}
			$query = "UPDATE palkka	set tyolaji = '$_POST[tyolaji]', palkkalaji = '$_POST[palkkalaji]', maara = '$_POST[maara]', hinta = '$_POST[hinta]' ,
					pvmalku = '$_POST[pvmalku]'	, pvmloppu = '$_POST[pvmloppu]', aikaalku = '$_POST[aikaalku]', aikaloppu = '$_POST[aikaloppu]', kuittaus = '$kuittaus'	, tyyppi = 'hyvaksytty' 		
					WHERE tunnus = '$_POST[tunnus]'";
					$kukarivit = mysql_query ( $query ) or pupe_error ( $query );
					/**$kukarivi = mysql_fetch_array ( $kukarivit );
					$palkkalaji = $kukarivi[palkkalaji];
					$tyolaji = $kukarivi[tyolaji];
					if ($kukarivi[hinta] != 0) $hinta = $kukarivi[hinta];
					$pvm = substr($kukarivi[pvmalku], 0,10);
					$alkoiklo = substr($kukarivi[pvmalku],11,5);
					
					$alkoiklo = date_format($kukarivi[pvmalku],'G:i');
					$loppuiklo = date_format($kukarivi[pvmloppu],'G:i');**/			
		}
		if ($_POST[rivipoista] == "Poista" and $_POST[tunnus] != "")	{		
				$query = "	DELETE 
						FROM palkka					
						WHERE tunnus = '$_POST[tunnus]'";
						$kukarivit = mysql_query ( $query ) or pupe_error ( $query );
						unset($kukarivit);
		}
		$test = strpos($kukarow[profiilit],"Admin");
	
			if (strpos($kukarow[profiilit],"Admin") == "true" ){
				$isAdmin = " OR kuka.asema = 'MP' ";
			}
		if ($tee == "") {			
			echo "<br>";
			echo "<table>";			
			echo "<form action='$PHP_SELF' method='post' name='kayttajaformi2' id='kayttajaformi2'><input type='hidden' name='tee' value='MUUTA'>";
			echo "<tr><td>";			
			echo "<select name='selkuka'>";
					$query = "	SELECT kuka.nimi, kuka.kuka, kuka.tunnus, if(count(oikeu.tunnus) > 0, 0, 1) aktiivinen
									FROM kuka
									LEFT JOIN oikeu ON oikeu.yhtio=kuka.yhtio and oikeu.kuka=kuka.kuka
									WHERE  kuka.yhtio = '$kukarow[yhtio]'
									and  (kuka.asema = 'TT' $isAdmin ) 
									GROUP BY 1,2,3
									ORDER BY aktiivinen,  kuka.nimi";
					$kukares = mysql_query ( $query ) or pupe_error ( $query );			
			echo "<optgroup label='" . t ( "Tunti- ja urakkatyöt" ) . "'>";
			echo "<option value=''>Valitse työntekijä</option>";	
			
					while ( $kurow = mysql_fetch_array ( $kukares ) ) {
						if ($selkukarow ["tunnus"] == $kurow ["tunnus"])
							$sel = "selected";
						else
							$sel = "";
						
						echo "<option value='$kurow[tunnus]' $sel>$poislisa $kurow[nimi] ($kurow[kuka])</option>";				
					}
					
			echo "<td><input type='hidden' name='kuka' value= $kurow[kuka]>";
			echo "</optgroup></select></td><td><input type='submit' value='" . t ( "Lisää tunteja" ) . "'></th></tr></form>";
			echo "<tr>";
			echo "</table>";
		}

#>>>>> lisää rivejä
if ($tee == "MUUTA") {
	
	if ($toimi == "LISAA"){
		
		$hinta = trim($_POST[hinta]);
		$hinta = str_replace(",",".",$hinta);
		
		if ($hinta == "" or $hinta == 0){
			$hinta = $_POST[palkka];
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
	    case "sairaslomapalkka":
	        $palkkakoodi = "001";
	        break;   
	    default:
	        $palkkakoodi = "002";
	}
		$query = "	INSERT INTO palkka (yhtio, kuka, laatija, luontiaika, henkilo, palkkakoodi, palkkalaji, tyolaji, maara, hinta, pvmalku,  aikaalku, aikaloppu, tyyppi)
					VALUES ('$kukarow[yhtio]','$kuka','$kukarow[kuka]','$luonti','$nimi','$palkkakoodi','$_POST[palkkalaji]','$_POST[tyolaji]','$maara','$hinta','$_POST[pvm]','$alkoiklo','$loppuiklo','Kesken')			
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
	
	$query = "	SELECT  * 
					FROM palkka					
					WHERE yhtio = '$kukarow[yhtio]'
					and  kuka = '$kuka'	ORDER BY pvmalku DESC";
	$kukarivit = mysql_query ( $query ) or pupe_error ( $query );
		if ($kukarivit){$laskuri = 1;}
	$query = "	SELECT  MAX( pvmalku) as pvmalku 
					FROM palkka					
					WHERE yhtio = '$kukarow[yhtio]'
					and  kuka = '$kuka' ";
	$kukamax = mysql_query ( $query ) or pupe_error ( $query );
	
	
		if ($_POST[vaihdaaika]){
			$uusipalkkapv = strtotime($postiaika);
		}elseif (mysql_num_rows ( $kukamax ) > 0) {
			$row = mysql_fetch_assoc ( $kukamax );
			$uusipalkkapv = strtotime($row[pvmalku]) ;
				if ($laskuri == 1){$laskuri = 2;}			
			#$uusipalkkapv = strtotime('next monday',$uusipalkkapv);
		}else{
			$pvm = time();
			$uusipalkkapv = time();
		}
		$vuosi = date('Y',$uusipalkkapv);
		$kk = date('m',$uusipalkkapv);
		$pv = date('d',$uusipalkkapv);

	
	echo "<table>";	
	
	echo "<tr>Aloita päivämäärästä?";
	echo "	<tr>
			<th align='left'>" . ( "PV" ) . "</th>
			<th align='left'>" . ( "KK" ) . "</th>
			<th align='left'>" . ( "VVVV" ) . "</th>
				</tr>";		
	echo "<form name='vaihda' action='$PHP_SELF' method='post' >";
	echo "<input type='hidden' name='tee' value='MUUTA'>";
	echo "<input type='hidden' name='kuka' value = '$kuka'>";
	echo "<input type='hidden' name='nimi' value = '$nimi'>";
	echo "<input type='hidden' name='palkka' value = '$palkka'>";			
	echo "<tr><td><input name='pv' value='$pv' size='3' type='text'></td>
				<td><input name='kk' value='$kk' size='3' type='text'></td>
				<td><input name='vuosi' value='$vuosi' size='5' type='text'></td>
				<td align='left'><input name='vaihdaaika' value ='Vaihda aika' type='submit'></td></tr></form>";
	
	if ($_POST[rivimuuta] = "" or $_POST[tee] = "MUUTA")
		$pvm = new DateTime("$vuosi-$kk-$pv");

		echo "	<tr><th align='left'>" . ( "$nimi" ) . "</th></tr>
			<tr>
			<th align='left'>" . ( "Pvm" ) . "</th>
			<th align='left'>" . ( "Työlaji" ) . "</th>
			<th align='left'>" . ( "Palkkalaji" ) . "</th>
			<th align='left'>" . ( "Tunnit/urakka kpl" ) . "</th>
			<th align='left'>" . ( "urakkahinta/kpl" ) . "</th>
			<th align='left'>" . ( "Alkoi klo" ) . "</th>
			<th align='left'>" . ( "Loppui klo" ) . "</th>
				</tr>";
		
	#>>>>>>>tehdään syöttörivit
	if ($_POST[tee] == "MUUTA"){
		$laskuri = 2;
		
	}
	$hinta = "";
	$maara = "";
	while ($i < $laskuri){
		$vkpv = $pvm->format('D');
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
		#$vkpv = t ($vkpv, "fi");
		$pvtext = $vkpv ." ". $pvm->format('d.m');
		$mysqlpvm = $pvm->format('Y-m-d');
		
		echo "<form name='lisaa' action='$PHP_SELF' value='lisaa' method='post' >";
		echo "<tr>";
		echo "<input type='hidden' name='tee' value='MUUTA'>";
		echo "<input type='hidden' name='toimi' value='LISAA'>";
		echo "<input type='hidden' name='kuka' value = '$kuka'>";
		echo "<input type='hidden' name='nimi' value = '$nimi'>";
		echo "<input type='hidden' name='palkka' value = '$palkka'>";
		echo "<input type='hidden' name='pvm' value = '$mysqlpvm'>";			
		echo "<td align='left'> $pvtext ";
		echo "<td align='left'> <select name='tyolaji' >
	 	 <optgroup >
	    <option value='tuotanto'>Tuotanto</option>
	    <option  value='lahetykset' selected='selected'>Lähetykset</option>
	 	 </optgroup></select>";
		
		//if ()
		echo "<td align='left'> <select name='palkkalaji' >
	  	<optgroup >
	    <option value='urakkapalkka'>Urakka</option>
	    <option  value='tuntipalkka' selected='selected'>Tuntipalkka</option>
	    <option value='arkipyhakorvaus'>Palkallinen pyhä</option>
	    <option value='kokopaivaraha'>Kokopäiväraha</option>
	    <option value='puolipaivaraha'>Puolipäivaraha</option>
	    <option value='sairaslomapalkka'>Sairaslomapalkka</option>
	    
	 	 </optgroup></select>";
		echo "<td align='left'><input type='text' size='8' name='maara' value = '$maara'>";	
		echo "<td align='left'><input type='text' size='8' name='hinta' value = '$hinta'>";
		echo "<td align='left'><input type='text' size='8' name='alkoiklo' value = '$alkoiklo'>";
		echo "<td align='left'><input type='text' size='8' name='loppuiklo' value = '$loppuiklo'>";
		echo "<input type='hidden' name='alkupvm' value = '$alkupvm'>";
		echo "<td align='left'><input type='submit' name='tunnit' value = 'Lisää'></td></tr></form>";
		
		$i++;
		$pvm->modify('+1 day');
	}#<<<<<while ($i < $laskuri){
	echo "</table>";
}#<<<<<<<< lisää rivejä

#>>>>>>>näytä tehdyt rivit ja muuta kuittaamattomat
echo "<br><br><br>";
echo "<table >";
echo "<tr> <th>Kuittaus</th> <th>Palkkalaji</th><th>Työlaji</th><th>Määrä</th><th>Hinta</th><th>Pvm</th><th>Alkoi</th><th>Loppui</th> </tr>";
// keeps getting the next row until there are no more to get
while($kukarivi = mysql_fetch_array( $kukarivit )) {
	// Print out the contents of each row into a table, palkkarivit
	$alku = date('G:i',strtotime($kukarivi[aikaalku]));
	$loppu = date('G:i',strtotime($kukarivi[aikaloppu]));
	if ($kukarivi[kuittaus] == ""){
		#$palkka = $kukarivi[hinta];
	echo "<form name='muutarivi' action='$PHP_SELF' value='1' method='post' >
			<tr class='aktiivi'><td> $kukarivi[kuittaus]</td><td><input type='text' name='tyolaji' value=$kukarivi[tyolaji] ></td><td><input type='text' name='palkkalaji' value=$kukarivi[palkkalaji] >
			</td><td><input type='text' size='8' name='maara' value=$kukarivi[maara] ></td>";
			# <input type="text" name="palkkalaji" value=$kukarivi[palkkalaji] readonly>
			
	$test = strpos($kukarow[profiilit],"Admin");
	
			if (strpos($kukarow[profiilit],"Admin") == "true" or ($kukarow[kuka] == $kuka)){
				$nayttohinta = $kukarivi[hinta];
			}else {
				$nayttohinta = "##";
			}
			echo "<td>$nayttohinta<input type='hidden' size='8' name='hinta' value= '$kukarivi[hinta]' ></td><td>";
			echo "<input type='text' size='10' name='pvmalku' value='$kukarivi[pvmalku]'></td><td>";;
			
			echo "<input type='text' size='8'  name='aikaalku' value='$alku'>";
			#echo date('d.m.Y G:i', strtotime($kukarivi[pvmalku]));
			echo "</td><td>";
			echo "<input type='date' size='8' name='aikaloppu' value='$loppu'>";
			#echo date('d.m.Y G:i', strtotime($kukarivi[pvmloppu]));
			echo "<input type='hidden' name='tunnus' value = '$kukarivi[tunnus]'></td><td class='back' nowrap=''>
			<input type='hidden' name='tee' value='MUUTA'>
			
			
			<input type='hidden' name='kuka' value = '$kuka'>
			<input type='hidden' name='nimi' value = '$nimi'>
			<input type='hidden' name='palkka' value = '$palkka'>
			<input type='submit' name='rivimuuta' value = 'Muuta'>
			<input type='submit' name='rivipoista' value = 'Poista'>";
			
			if (strpos($kukarow[profiilit],"Admin") !== ""){
				echo "<input type='submit' name='kuittaa' value = 'Kuittaa'>";
			}
			echo "</td></tr></form>";
			
	}else{
		//kuitattuja ei voi muuttaa
		echo "<tr><td>$kukarivi[kuittaus]</td><td>$kukarivi[tyolaji]</td><td> $kukarivi[palkkalaji]</td><td>$kukarivi[maara]</td>
			<td>$nayttohinta</td><td>$kukarivi[pvmalku] </td><td>$alku</td><td>$loppu</td>
			</tr>
		 	";
		
	} 
} 

echo "</table>";
}

require ("../inc/footer.inc");
?>

