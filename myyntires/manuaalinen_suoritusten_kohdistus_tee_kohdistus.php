<?php
// estetään sivun lataus suoraan
if (!empty($HTTP_GET_VARS["oikeus"]) ||
    !empty($HTTP_POST_VARS["oikeus"]) ||
    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
    !isset($oikeus)) {

  echo "<p>".t("Kielletty toiminto")."!</p>";
  exit;
}

// $suoritus_tunnus
// $lasku_tunnukset[]
// $lasku_tunnukset_kale[]

//echo  "<p>Suorituksen tunnus: $suoritus_tunnus";

// Tarkistetaan muutama asia

if ((!($lasku_tunnukset_kale)) and (!($lasku_tunnukset))) {
	echo "<font class='error'>".t("Olet kohdistamassa, mutta et ole valinnut mitään kohdistettavaa")."!</font>";
	exit;
}	


if ($osasuoritus == '1') {
	if (sizeof($lasku_tunnukset) != 1) {
		echo "<font class='error'>".t("Osasuoritukseen ei ole valittu yhtä laskua")."</font>";
		exit;
	}

	if ($lasku_tunnukset_kale) {
		echo "<font class='error'>".t("Osasuoritukseen ei voi valita käteisalennusta")."</font>";
		exit;
	}
}

$query = "LOCK TABLES yriti READ, yhtio READ, tili READ, lasku WRITE, suoritus WRITE, tiliointi WRITE, sanakirja WRITE";
$result = mysql_query($query) or pupe_error($query);

// haetaan suorituksen tiedot
$query = "	SELECT suoritus.tunnus tunnus, suoritus.asiakas_tunnus asiakas_tunnus, suoritus.tilino tilino, suoritus.summa summa,
			yriti.oletus_rahatili kassatilino, tiliointi.tilino myyntisaamiset_tilino,
			yhtio.myynninkassaale kassa_ale_tilino,
			yhtio.pyoristys pyoristys_tilino,
			suoritus.asiakas_tunnus asiakastunnus,
			suoritus.kirjpvm maksupvm,
			suoritus.ltunnus ltunnus,
			suoritus.nimi_maksaja nimi_maksaja
			FROM suoritus, yriti, yhtio, tiliointi
			WHERE suoritus.ltunnus!=0 AND suoritus.tunnus='$suoritus_tunnus' AND
			yriti.yhtio='$kukarow[yhtio]' AND
			yhtio.yhtio=yriti.yhtio AND
			yriti.tilino=suoritus.tilino AND
			suoritus.kohdpvm='0000-00-00' AND
			tiliointi.yhtio='$kukarow[yhtio]' AND
			tiliointi.tunnus=suoritus.ltunnus AND
			tiliointi.korjattu=''";

$result = mysql_query($query) or pupe_error($query);
$suoritus = mysql_fetch_object ($result) or pupe_error('<br>Joku suoritukseen liittyvä tieto on kateissa (tämä on paha ongelma)'. $query);

// otetaan talteen, jos suorituksen kassatilillä on kustannuspaikka.. tarvitaan jos suoritukselle jää saldoa
$query = "select * from tiliointi WHERE aputunnus='$suoritus->ltunnus' AND yhtio='$kukarow[yhtio]' and tilino='$suoritus->kassatilino' and korjattu=''";
$result = mysql_query($query) or pupe_error($query);
$apurow = mysql_fetch_array($result);
$apukustp = $apurow["kustp"];

// haetaan laskujen tiedot
$laskujen_summa=0;

if ($osasuoritus==1) {
	//*** Tässä yritetään hoitaa osasuoritus mahdollisimman elegantisti ***

	//Haetaan osasuoritettava lasku
	$query = "SELECT summa-saldo_maksettu AS summa, 0 AS alennus, tunnus FROM lasku WHERE tunnus = $lasku_tunnukset[0] and  mapvm='0000-00-00'";
	$result = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($result) != 1) {
		echo "<font class='error'>".t("Osasuoritettava lasku katosi! (joku maksoi sen sinua ennen?)")."</font><br>";
		exit;
	}
	
	$lasku=mysql_fetch_object($result);
	$ltunnus=$lasku->tunnus;
	$maksupvm=$suoritus->maksupvm;
	$suoritussumma=$suoritus->summa;
	
	include "manuaalinen_suoritusten_kohdistus_tee_korkolasku.php";

	//Aloitetaan kirjanpidon kirjaukset
	//kassatili
	$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, tilino, summa, ltunnus, selite, kustp)
            	VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus->maksupvm','$suoritus->kassatilino', $suoritus->summa, '$ltunnus','Manuaalisesti kohdistettu suoritus (osasuoritus)','$apukustp')";
	$result = mysql_query($query) or pupe_error($query);
        
	
	// myyntisaamiset
	$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
            	VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus->maksupvm', '$ltunnus', '$suoritus->myyntisaamiset_tilino', -1 * $suoritus->summa,'Manuaalisesti kohdistettu suoritus (osasuoritus)')";
	//echo "<font class='message'>Myyntisaamiset: $query</font><br>";
	$result = mysql_query($query) or pupe_error($query);

	
	//jos tämän suorituksen jälkeen ei enää jää maksettavaa niin merkataan lasku maksetuksi
	if ($lasku->summa == $suoritus->summa) {
		 $lisa = ", mapvm=now()";
	}
	
	$query = "	UPDATE lasku 
				SET viikorkoeur='$korkosumma', saldo_maksettu=saldo_maksettu+$suoritus->summa $lisa 
				WHERE tunnus=$ltunnus 
				AND yhtio='$kukarow[yhtio]'";
	//echo "<font class='message'>Laskun merkitseminen maksetuksi: $query</font><br>";
	$result = mysql_query($query) or pupe_error($query);

	//Merkataan suoritus käytetyksi ja yliviivataan sen tiliöinnit
	$query = "UPDATE suoritus SET kohdpvm=now(), summa=0 WHERE tunnus=$suoritus->tunnus AND yhtio='$kukarow[yhtio]'";
	//echo "<font class='message'>Suorituksen päivitys: $query</font><br>";
	$result = mysql_query($query) or pupe_error($query);

	// Luetaan ketjussa olevat tapahtumat ja poistetaan ne (=merkataan korjatuksi)	
	$query = "SELECT aputunnus, ltunnus FROM tiliointi WHERE tunnus=$suoritus->ltunnus AND yhtio='$kukarow[yhtio]'";
	//echo "<font class='message'>$query</font><br>";
	$result = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($result) != 1) {
		die ("Tiliöinti1 kateissa " . $suoritus->tunnus);
	}
	$tiliointi = mysql_fetch_object ($result);
	
	$query = "SELECT tunnus FROM tiliointi WHERE aputunnus=$suoritus->ltunnus AND yhtio='$kukarow[yhtio]'";
	//echo "<font class='message'>$query</font><br>";
	$result = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($result) != 1) {
		die ("Tiliöinti2 kateissa " . $suoritus->tunnus);
	}
	
	$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE tunnus=$suoritus->ltunnus AND yhtio='$kukarow[yhtio]'";
	//echo "<font class='message'>$query</font><br>";
	$result = mysql_query($query) or pupe_error($query);
	
	$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE aputunnus=$suoritus->ltunnus AND yhtio='$kukarow[yhtio]'";
	//echo "<font class='message'>$query</font><br>";
	$result = mysql_query($query) or pupe_error($query);

}
	
else {
	//*** Tässä käsitellään tavallinen (ja paljon monimutkaisempi) suoritus ***

	// haetaan laskujen tiedot
	$laskujen_summa=0;

	if($lasku_tunnukset) {
		$query = "SELECT summa-saldo_maksettu AS summa, 0 AS alennus, tunnus FROM lasku WHERE tunnus IN (";

		for ($i=0;$i<sizeof($lasku_tunnukset);$i++) {
			if($i!=0) $query=$query . ",";
			
			$query=$query . "$lasku_tunnukset[$i]";
		}

		$query=$query . ") AND lasku.yhtio='$kukarow[yhtio]' and mapvm='0000-00-00'";
		//echo "<font class='message'> Laskujen summaquery: $query</font><br>";
		$result = mysql_query($query) or pupe_error($query);
	    
		if (mysql_num_rows($result) != sizeof($lasku_tunnukset)) {
			echo "<font class='error'>".t("Joku laskuista katosi (joku maksoi sen sinua ennen?)")." '".mysql_num_rows($result)."' '".sizeof($lasku_tunnukset)."'</font><br>";
			exit;
		}
		
		while($lasku = mysql_fetch_object($result)){
			$laskut[] = $lasku;
			$laskujen_summa+=$lasku->summa;
		}
	}
	
	//alennukset
	if($lasku_tunnukset_kale) {
		$query = "SELECT summa-saldo_maksettu AS summa, kasumma AS alennus, tunnus FROM lasku WHERE tunnus IN (";

		for ($i=0;$i<sizeof($lasku_tunnukset_kale);$i++) {
			if($i!=0) $query=$query . ",";
			
			$query=$query . "$lasku_tunnukset_kale[$i]";
		}

		$query=$query . ") AND lasku.yhtio='$kukarow[yhtio]' and mapvm='0000-00-00'";
		//echo "<font class='message'> Laskujen kasummaquery: $query</font><br>";

		$result = mysql_query($query) or pupe_error($query);
	    
		if (mysql_num_rows($result) != sizeof($lasku_tunnukset_kale)) {
			echo "<font class='error'>".t("Joku laskuista katosi (joku maksoi sen sinua ennen?)")." '".mysql_num_rows($result)."' '".sizeof($lasku_tunnukset_kale)."'</font><br>";
			exit;
		}

	    while($lasku = mysql_fetch_object($result)){
			$laskut[] = $lasku;
			$laskujen_summa+=$lasku->summa - $lasku->alennus;
		}
	}
	$kaatosumma = $suoritus->summa - $laskujen_summa;
	$kaatosumma = round($kaatosumma,2);

	if ($kaatosumma != 0) echo "<font class='message'>".t("Tilitapahtumalle jää pyöristyksen jälkeen")." $kaatosumma</font> ";
	//Jos heittoa ja kirjataan kassa-alennuksiin etsitään joku sopiva lasku (=iso summa)

	if(($kaatosumma!=0) and($pyoristys_virhe_ok==1)) {
		echo "<font class='message'>".t("Kirjataan kassa-aleen")."</font> ";
		$query = "SELECT tunnus FROM lasku WHERE tunnus IN (";

		for ($i=0;$i<sizeof($lasku_tunnukset_kale);$i++) {
			if($i!=0) $query=$query . ",";
			
			$query=$query . "$lasku_tunnukset_kale[$i]";
		}
	    	
		for ($i=0;$i<sizeof($lasku_tunnukset);$i++) {
			if((sizeof($lasku_tunnukset_kale)>0) and ($i==0)) $query=$query . ",";
			if($i!=0) $query=$query . ",";
			
			$query=$query . "$lasku_tunnukset[$i]";
		}

		$query=$query . ") AND lasku.yhtio='$kukarow[yhtio]' ORDER BY summa desc LIMIT 1";
		//echo "<font class='message'> Laskujen kaatoquery: $query</font><br>";

		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Kaikki laskut katosivat")." (sys err)</font<br>";
			exit;
		}
		else {
			$kohdistuslasku = mysql_fetch_object($result);
		}	    
	}

	// Tiliöidään myyntisaamiset

	if ($laskut) {
		foreach ($laskut as $lasku) {
			
			// lasketaan korko
			$ltunnus=$lasku->tunnus;
			$maksupvm=$suoritus->maksupvm;
			$suoritussumma=$suoritus->summa;
			include "manuaalinen_suoritusten_kohdistus_tee_korkolasku.php";
		      	
			//Kohdistammeko pyöristykset ym:t tähän?
		 	if(($kaatosumma != 0) and ($pyoristys_virhe_ok==1) and ($lasku->tunnus == $kohdistuslasku->tunnus)) {
		 		echo "<font class='message'>".t("Sijoitin lisäkassa-alen laskulle").":" . $kohdistuslasku->tunnus . "</font> ";
		 		$lasku->alennus = round($lasku->alennus - $kaatosumma,2);
		  		echo "<font class='message'>".t("Uusi kassa-ale").":" . $lasku->alennus . "</font> ";
				$kaatosumma = 0;			
		 	}

			// tilioidaan kassa-alennukset
			if($lasku->alennus != 0) {	// Kassa-alessa on huomioitava alv, joka voi olla useita vientejä (uhhhh tai iiikkkkk)

				$totkasumma = 0;
				$query = "	SELECT * from tiliointi WHERE ltunnus='$lasku->tunnus' and yhtio = '$kukarow[yhtio]' and
							tilino<>$yhtiorow[myyntisaamiset] and tilino<>$yhtiorow[konsernimyyntisaamiset] and tilino<>$yhtiorow[alv] and
							tilino<>$yhtiorow[varasto] and tilino<>$yhtiorow[varastonmuutos] and
							tilino<>$yhtiorow[pyoristys] and korjattu = ''";
				$yresult = mysql_query($query) or pupe_error($query);
				//echo "<font class='message'>Kassa-ale alv etsintä: $query</font><br>";
	
				if (mysql_num_rows($yresult) == 0) { // Jotain meni pahasti pieleen
					echo "<font class='error'>".t("En löytänyt laskun myynnin vientejä! Alv varmaankin heittää")."</font> ";
					$query="INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite, vero)
							VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus->maksupvm', '$lasku->tunnus', '$suoritus->kassa_ale_tilino', '$lasku->alennus', 'Manuaalisesti kohdistettu suoritus (alv ongelma)', '0')";
					//echo "<font class='message'>Kassa-alet (ongelma alvin kanssa): $query</font><br>";
	
					$result = mysql_query($query) or pupe_error($query);
				}
				else {
					while ($tiliointirow=mysql_fetch_array ($yresult)) {
						// Kuinka paljon on tämän viennin osuus
						$summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) * -1 / $lasku->summa * $lasku->alennus,2);
	
						if ($tiliointirow['vero'] != 0) { // Netotetaan alvi
							//$alv:ssa on alennuksen alv:n maara
							$alv = round($summa - $summa / (1 + ($tiliointirow['vero'] / 100)),2);
							//$summa on alviton alennus
							$summa -= $alv;
						}
						// Kuinka plajon olemme kumulatiivisesti tiliöineet
						$totkasumma += $summa + $alv;
							
						$query="INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite, vero)
								VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus->maksupvm', '$lasku->tunnus', '$suoritus->kassa_ale_tilino', $summa, 'Manuaalisesti kohdistettu suoritus', '$tiliointirow[vero]')";
						//echo "<font class='message'>Kassa-alet: $query</font><br>";
						$result = mysql_query($query) or pupe_error($query);
	
						$isa = mysql_insert_id ($link); // Näin löydämme tähän liittyvät alvit....
	
						if ($tiliointirow['vero'] != 0) {
	
							$query = "	INSERT into tiliointi set
										yhtio ='$kukarow[yhtio]',
										ltunnus = '$lasku->tunnus',
										tilino = '$yhtiorow[alv]',
										tapvm = '$suoritus->maksupvm',
										summa = $alv,
										vero = '',
										selite = '$selite',
										lukko = '1',
										laatija = '$kukarow[kuka]',
										laadittu = now(),
										aputunnus = $isa";
							//echo "<font class='message'>Kassa-alen alv: $query</font><br>";
							$xresult = mysql_query($query) or pupe_error($query);
						}
					}
					
					//Hoidetaan mahdolliset pyöristykset
					$heitto = $totkasumma - $lasku->alennus;
					if (abs($heitto) >= 0.01) {
						echo "<font class='message'>".t("Kassa-alvpyöristys")." $heitto</font> ";
						$query = "	UPDATE tiliointi SET summa = summa - $totkasumma + $lasku->alennus
									WHERE tunnus = '$isa' and yhtio='$kukarow[yhtio]'";
						$xresult = mysql_query($query) or pupe_error($query);
						$isa=0; //Vähän turvaa
					}
				}
			}
					        	
			// kassatili
			$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, tilino, summa, ltunnus, selite, kustp)
						VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus->maksupvm','$suoritus->kassatilino',$lasku->summa - $lasku->alennus, $lasku->tunnus,'Manuaalisesti kohdistettu suoritus','$apukustp')";
			//echo "<font class='message'>Kassa: $query</font><br>";
			$result = mysql_query($query) or pupe_error($query);

			// myyntisaamiset
			$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
						VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus->maksupvm', '$lasku->tunnus', '$suoritus->myyntisaamiset_tilino',-1 * $lasku->summa,'Manuaalisesti kohdistettu suoritus')";
			//echo "<font class='message'>Myyntisaamiset: $query</font><br>";
			$result = mysql_query($query) or pupe_error($query);

			$query = "UPDATE lasku SET mapvm='$suoritus->maksupvm',  viikorkoeur='$korkosumma', saldo_maksettu=0 WHERE tunnus=$lasku->tunnus AND yhtio='$kukarow[yhtio]'";
			//echo "<font class='message'>Laskun merkitseminen maksetuksi: $query</font><br>";
			$result = mysql_query($query) or pupe_error($query);
		}
				
		//echo "<font class='message'>Kassatiliönti: $query</font><br>";
		$uusi_saldo=$kaatosumma;
		//echo "<font class='message'>Tilitapahtuman stemmuutus alkaa arvolla $uusi_saldo (kaatosumma on $kaatosumma)</font><br>";
			
		$query = "UPDATE suoritus SET kohdpvm=now(), summa=$uusi_saldo WHERE tunnus=$suoritus->tunnus AND yhtio='$kukarow[yhtio]'";
		//echo "<font class='message'>Suorituksen päivitys: $query</font><br>";
		$result = mysql_query($query) or pupe_error($query);

		// Luetaan ketjussa olevat tapahtumat ja poistetaan ne (=merkataan korjatuksi)
			
		$query = "SELECT aputunnus, ltunnus FROM tiliointi WHERE tunnus=$suoritus->ltunnus AND yhtio='$kukarow[yhtio]'";
		//echo "<font class='message'>$query</font><br>";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) != 1) {
			die ("Tiliöinti1 kateissa " . $suoritus->tunnus);
		}
		$tiliointi = mysql_fetch_object ($result);
			
		$query = "SELECT tunnus FROM tiliointi WHERE aputunnus=$suoritus->ltunnus AND yhtio='$kukarow[yhtio]'";
		//echo "<font class='message'>$query</font><br>";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) != 1) {
			die ("Tiliöinti2 kateissa " . $suoritus->tunnus);
		}

		$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE tunnus=$suoritus->ltunnus AND yhtio='$kukarow[yhtio]'";
		//echo "<font class='message'>$query</font><br>";
		$result = mysql_query($query) or pupe_error($query);
			
		$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE aputunnus=$suoritus->ltunnus AND yhtio='$kukarow[yhtio]'";
		//echo "<font class='message'>$query</font><br>";
		$result = mysql_query($query) or pupe_error($query);
			
		if ($uusi_saldo != 0) { // Suoritukselle jää vielä saldoa, joten kirjataan se
			//Myyntisaamiset
			$query="INSERT INTO tiliointi(yhtio,laatija,laadittu,tapvm,ltunnus,tilino,summa,selite) values ('$kukarow[yhtio]','$kukarow[kuka]',now(),'$suoritus->maksupvm','$tiliointi->ltunnus','$suoritus->myyntisaamiset_tilino',-1 * $uusi_saldo,'Käsin syötetty suoritus')";		
			$result = mysql_query($query) or pupe_error($query);
			$ttunnus = mysql_insert_id($link);
			
			//Kassatili
			$query="INSERT INTO tiliointi(yhtio,laatija,laadittu,tapvm,ltunnus,tilino,summa,selite,aputunnus,lukko,kustp) values ('$kukarow[yhtio]','$kukarow[kuka]',now(),'$suoritus->maksupvm','$tiliointi->ltunnus','$suoritus->kassatilino',$uusi_saldo,'Käsin syötetty suoritus',$ttunnus,'1','$apukustp')";
			$result = mysql_query($query) or pupe_error($query);

			// Päivitetään osoitin
			$query = "UPDATE suoritus SET ltunnus = '$ttunnus', kohdpvm = '0000-00-00' WHERE tunnus=$suoritus->tunnus AND yhtio='$kukarow[yhtio]'";
			//echo "<font class='message'>Suorituksen päivitys: $query</font><br>";
			$result = mysql_query($query) or pupe_error($query);
		}
	}
}

echo "<br><font class='message'>".t("Kohdistus onnistui").".</font><br>";
$query = "UNLOCK TABLES";
$result = mysql_query($query) or pupe_error($query);
$tila="suorituksenvalinta";
$asiakas_tunnus = $suoritus->asiakas_tunnus;
?>
