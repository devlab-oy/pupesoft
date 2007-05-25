<?php
require ("inc/parametrit.inc");

echo "<font class='head'>".t('Matkalaskut')."</font><hr><br><br>";

//	tarkastetaan ett‰ k‰ytt‰j‰lle voidaan perustaa matkalaskuja
$query = "	SELECT * FROM toimi WHERE yhtio='$kukarow[yhtio]' and nimi='$kukarow[nimi]'";
$result = mysql_query($query) or pupe_error($query);

if(mysql_num_rows($result)==1) {
	$trow=mysql_fetch_array($result);
}
else {
	die("<font class='error'>".t("Lis‰‰ itsesi ensin toimittajaksi.")."</font>");
}

if($tee=="UUSI") {
	if ($ytunnus != '') {
		require ("inc/asiakashaku.inc");

		if($asiakasid>0) {
			// Perustetaan lasku
			$query = "INSERT into lasku set
						yhtio 			= '$kukarow[yhtio]',
						valkoodi 		= 'EUR',
						hyvak1 			= '$kukarow[kuka]',
						hyvak2 			= '$trow[oletus_hyvak2]',
						hyvak3 			= '$trow[oletus_hyvak3]',
						hyvak4 			= '$trow[oletus_hyvak4]',
						hyvak5 			= '$trow[oletus_hyvak5]',
						hyvaksyja_nyt 	= '$kukarow[kuka]',
						ytunnus 		= '$ytunnus',
						tilinumero 		= '$trow[tilinumero]',
						nimi 			= '$trow[nimi]',
						nimitark 		= '".t("Matkalasku")."',
						osoite 			= '$trow[osoite]',
						osoitetark 		= '$trow[osoitetark]',
						postino 		= '$trow[postino]',
						postitp 		= '$trow[postitp]',
						maa 			=  '$trow[maa]',
						toim_nimi 		= '$asiakasrow[nimi]',
						toim_nimitark 	= '".t("Matkalasku")."',
						toim_osoite 	= '$asiakasrow[osoite]',
						toim_postino 	= '$asiakasrow[postino]',
						toim_postitp 	= '$asiakasrow[postitp]',
						toim_maa 		= '$asiakasrow[maa]',
						vienti 			= '$asiakasrow[vienti]',
						ebid 			= '',
						tila 			= 'H',
						swift 			= '$trow[swift]',
						pankki1 		= '$trow[pankki1]',
						pankki2 		= '$trow[pankki2]',
						pankki3 		= '$trow[pankki3]',
						pankki4 		= '$trow[pankki4]',
						vienti_kurssi 	= '1',
						laatija 		= '$kukarow[kuka]',
						luontiaika 		= now(),
						liitostunnus 	= '$asiakasid',
						hyvaksynnanmuutos = '$trow[oletus_hyvaksynnanmuutos]',
						suoraveloitus 	= '',
						tilaustyyppi	= 'M'";

			$result = mysql_query($query) or pupe_error($query);
			$tilausnumero = mysql_insert_id();

			$query = "INSERT into laskun_lisatiedot set
						yhtio = '$kukarow[yhtio]',
						otunnus = '$tilausnumero'";

			$result = mysql_query($query) or pupe_error($query);
			
			$tee="MUOKKAA";
		}
		else {
			$tee="";
		}	
	}
	else {
		echo "<font class='error'>".t("VIRHE!!! Anna asiakkaan nimi")."</font><br>";
		$tee="";
	}	
}

if($tee=="MUOKKAA") {
	if((int)$tilausnumero==0) {
		echo "<font class='error'>".t("Matkalaskun numero puuttuu")."</font>";
		$tee="";
	}
	else {
		
		$query 	= "	select *
						from lasku
						where tunnus='$tilausnumero' and yhtio='$kukarow[yhtio]' and tilaustyyppi='M' and tila='H'";
		$result  	= mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($result)==0) {
			die("<font class='error'>".t("Matkalaskun numero puuttuu")."</font>");
		}
		else {
			$laskurow   = mysql_fetch_array($result);
		}
		
		// kirjoitellaan otsikko
		echo "<table>";

		// t‰ss‰ alotellaan koko formi.. t‰m‰ pit‰‰ kirjottaa aina
		echo "	<form name='tilaus' action='$PHP_SELF' method='post' autocomplete='off'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='tee' value='$tee'>";

		echo "<tr>";
		echo "<th align='left'>".t("Asiakas").":</th>";

		echo "<td>$laskurow[toim_nimi]<br>$laskurow[toim_nimitark]<br>$laskurow[toim_osoite]<br>$laskurow[toim_postino] $laskurow[toim_postitp]</td>";

		echo "</tr>";
		echo "</form></table><br>";

		echo "<table><tr>";
		echo "<th colspan='4'>".t('Lis‰‰ kulu').":</th>";
		
		echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='$tee'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<input type='hidden' name='tyyppi' value='A'>";		
		echo "<td class='back'><input type='Submit' value='".t("Kotimaanp‰iv‰raha")."'></td>";
		echo "</form>";	

		echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='$tee'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<input type='hidden' name='tyyppi' value='B'>";		
		echo "<td class='back'><input type='Submit' value='".t("Ulkomaanp‰iv‰raha")."'></td>";
		echo "</form>";	

		echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='$tee'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<input type='hidden' name='tyyppi' value='C'>";		
		echo "<td class='back'><input type='Submit' value='".t("Kilmometrikorvaus")."'></td>";
		echo "</form>";	

		echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='$tee'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<input type='hidden' name='tyyppi' value='D'>";		
		echo "<td class='back'><input type='Submit' value='".t("Muu korvaus")."'></td>";
		echo "</form>";			

		echo "</form></table><br><br>";
		
		//	Koitetaan lis‰t‰ uusi rivi!
		if($tuoteno!="") {
			
			//	Tarkastetaan ett‰ p‰iv‰rahalle on puolip‰iv‰raha
			$query = "	select * from tuote where yhtio='$kukarow[yhtio]' and tuotetyyppi='$tyyppi' and tuoteno='$tuoteno'";
			$tres=mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($tres)<>1) {
				die("<font class='error'>".t("VIRHE!!! Viranomaistuote puuttuu")."</font><br>");
			}
			else {
				$trow=mysql_fetch_array($tres);
			}
			
			/*
				P‰iv‰rahoilla ratkaistaan p‰iv‰t
				Samalla oletetaan ett‰ puolip‰iv‰rana on aina P+tuoteno
			*/
			if($tyyppi=="A" or $tyyppi=="B") {
				
				//	Tarkastetaan ett‰ p‰iv‰rahalle on puolip‰iv‰raha
				$query = "	select * from tuote where yhtio='$kukarow[yhtio]' and tuotetyyppi='E' and tuoteno='P$trow[tuoteno]'";
				$tres2=mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($tres2)<>1) {
					die("<font class='error'>".t("VIRHE!!! Viranomaistuote2 puuttuu")."</font><br>");
				}
				else {
					$trow2=mysql_fetch_array($tres2);
				}
				
				$alkupp =  $alkupp;
				$alkukk = (int) $alkukk;
				$alkuvv = (int) $alkuvv;
				$alkuhh = (int) $alkuhh;
				$alkumm = (int) $alkumm;
				
				$loppupp = (int) $loppupp;
				$loppukk = (int) $loppukk;
				$loppuvv = (int) $loppuvv;
				$loppuhh = (int) $loppuhh;
				$loppumm = (int) $loppumm;
				
				if(($alkupp>=1 and $alkupp<=31) and ($alkukk>=1 and $alkukk<=12) and $alkuvv>0 and ($alkuhh>=0 and $alkuhh<=24) and ($loppupp>=1 and $loppupp<=31) and ($loppukk>=1 and $loppukk<=12) and $loppuvv>0 and ($loppuhh>=0 and $loppuhh<=24)) {
					$alku=mktime($alkuhh, $alkumm, 0, $alkukk, $alkupp, $alkuvv);
					$loppu=mktime($loppuhh, $loppumm, 0, $loppukk, $loppupp, $loppuvv);
					
					$paivat=$puolipaivat=$ylitunnit=$tunnit=0;
					
					//	montako tuntia on oltu matkalla?
					$tunnit=($loppu-$alku)/3600;
					$paivat=floor($tunnit/24);
					
					$ylitunnit=$tunnit-($paivat*24);
					
					if($ylitunnit>10) {
						$paivat++;
					}
					elseif($ylitunnit>6) {
						$puolipaivat++;
					}
					
					if($paivat>0) {
						$tuoteno_array[$trow["tuoteno"]]=$trow["tuoteno"];
					}
					
					if($puolipaivat>0) $tuoteno_array[$trow2["tuoteno"]]=$trow2["tuoteno"];
					
					echo "SAATIIN p‰ivarahoja: $paivat puolip‰iv‰rahoja: $puolipaivat<br>";
					
				}
				else {
					$errori = "<font class='error'>".t("VIRHE!!! P‰iv‰rahalle on annettava alku ja loppuaika")."</font><br>";
				}
			}
			
			//	Lis‰t‰‰n annetut rivit
			
		}
		
		$hakulisa;
		switch ($tyyppi) {
			case "A":
				$tyyppi_nimi="kotimaanp‰iv‰raha";
				break;
			case "A":
				$tyyppi_nimi="ulkomaanp‰iv‰raha";
				break;
			case "A":
				$tyyppi_nimi="kilmometrikorvaus";
				break;
			case "A":
				$tyyppi_nimi="muu korvaus";
				break;
		}
		
		if($tyyppi!="") {
			
			echo "<font class='message'>".t("Lis‰‰ $tyyppi_nimi")."</font><hr><br>$errori";
			
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='$tee'>";
			echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";			
			echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";		
			echo "<table><tr>";

			
			$query = "	select * from tuote where yhtio='$kukarow[yhtio]' and tuotetyyppi='$tyyppi'";
			$tres=mysql_query($query) or pupe_error($query);

			$valinta="";
			if(mysql_num_rows($tres)>1){
				$valinta = "<select name='tuoteno'>";
				while($trow=mysql_fetch_array($tres)) {
					$valinta .= "<option value='$trow[tuoteno]'>$trow[nimitys]</option>";
				}
				$valinta .= "</select>";
			}
			elseif(mysql_num_rows($tres)==1) {
				$trow=mysql_fetch_array($tres);
				$valinta = "<input type='hidden' name='tuoteno' value='$trow[tuoteno]'>";
			}
			else {
				die("<font class='error'>".t("VIRHE!!! Viranomaistuote puuttuu")."</font><br>");
			}
			
			if(!isset($alkukk)) $alkukk=date("m");
			if(!isset($alkuvv)) $alkuvv=date("Y");

			if(!isset($loppukk)) $loppukk=date("m");
			if(!isset($loppuvv)) $loppuvv=date("Y");
			
			if($tyyppi=="A") {
				echo "<th>".t("Alku")."</th><th>".t("Loppu")."</th></tr>";
				echo "<tr><td>$valinta<input type='text' name='alkupp' value='$alkupp' size='3'> <input type='text' name='alkukk' value='$alkukk' size='3'> <input type='text' name='alkuvv' value='$alkuvv' size='5'> ".t("klo").":<input type='text' name='alkuhh' value='$alkuhh' size='3'>:<input type='text' name='alkumm' value='$alkumm' size='3'></td>
						<td><input type='text' name='loppupp' value='$loppupp' size='3'> <input type='text' name='loppukk' value='$loppukk' size='3'> <input type='text' name='loppuvv' value='$loppuvv' size='5'> ".t("klo").":<input type='text' name='loppuhh' value='$loppuhh' size='3'>:<input type='text' name='loppumm' value='$loppumm' size='3'></td>";
				$cols=2;
			}
			
			echo "<td class='back'><input type='submit' name='lisaa' value='".t("Lis‰‰")."'></td></tr>";
			
			
			echo "<tr><th colspan='$cols'>".t("Kommentti")."</th></tr>";
			echo "<tr><td colspan='$cols'><input type='text' name='kommentti' value='$kommentti' size='60'></td>";			
			echo "<td class='back'><input type='submit' name='tyhjenna' value='".t("Tyhjenn‰")."'></td></tr></table></form>";
		}
	}
}

if($tee == "") {    
	echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='UUSI'>";
	echo "<table><tr>";
	echo "<th>".t('Perusta uusi matkalasku asiakkaalle')."</th>";
	echo "<td class='back'><input type='text' size='30' name='ytunnus'></td>";	
	echo "<td class='back'><input type='Submit' value='".t("Perusta")."'></td>";
	echo "</tr></table>";
	echo "</form>";
	
	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				and tila = 'H'
				and tilaustyyppi = 'M'";
	$result=mysql_query($query) or pupe_error($query);
	if(mysql_num_rows($result)) {
		
		echo "<br><br><font class='message'>".t("Avoimet matkalaskut")."</font><hr>";
		
		echo "<table><tr><th>".t("Asiakas")."</th><tr>";
		while($row=mysql_fetch_array($result)) {
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='MUOKKAA'>";
			echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";			
			echo "<tr>";
			echo "<td>$row[toim_nimi]</td>";
			echo "<td class='back'><input type='Submit' value='".t("Muokkaa")."'></td>";
			echo "</tr>";
			echo "</form>";

		}
		echo "</table>";
	}
}


require("inc/footer.inc");
?>
