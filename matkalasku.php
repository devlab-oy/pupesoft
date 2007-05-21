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
						otunnus = 'EUR'";

			$result = mysql_query($query) or pupe_error($query);
			
			$tee="MUOKKAA";
		}
	}
	
    if ($ytunnus == '') {
		echo "<br><table>";
		echo "<tr>
				<th>".t("Asiakkaan nimi").": </th>
				<form action = '$PHP_SELF' method = 'post'>
				<input type='hidden' name='tee' value='$tee'>
				<td class='back'><input type='text' size='30' name='ytunnus'></td>
				<td class='back'><input type='submit' value='".t("Jatka")."'></td>
				</tr>";
		echo "</form>";
		echo "</table>";
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

		echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='$tee'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<table><tr>";
		echo "<th colspan='4'>".t('Lis‰‰ kulu').":</th>";
		echo "<td class='back'><input type='Submit' name='PV' value='".t("Kotimaanp‰iv‰raha")."'></td>";
		echo "<td class='back'><input type='Submit' name='PVU' value='".t("Ulkomaanp‰iv‰raha")."'></td>";
		echo "<td class='back'><input type='Submit' name='KM' value='".t("Kilmometrikorvaus")."'></td>";
		echo "<td class='back'><input type='Submit' name='MUU' value='".t("Muu korvaus")."'></td>";				
		echo "</tr></table>";
		echo "</form>";	

		echo "</form></table><br>";
		
		$tyyppi="";
		if(isset($PV)) {
			$tyyppi="A";
		}

		if(isset($PVU)) {
			$tyyppi="B";
		}

		if(isset($KM)) {
			$tyyppi="C";
		}

		if(isset($MUU)) {
			$tyyppi="D";
		}			
		
		if($tyyppi!="") {
			
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='$tee'>";
			echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";		
			echo "<table><tr>";

			$query = "	select * from tuote where yhtio='$kukarow[yhtio]' and tuotetyyppi='$tyyppi'";
			$tres=mysql_fetch_array($query) or pupe_error($query);
			$trow=mysql_fetch_array($tres);

			
			echo "<td class='back'><input type='submit' name='tyhjenna' value='".t("Tyhjenn‰")."'></td></tr></table></form>";
		}
	}
}

if($tee == "") {
	echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
	echo "<input type='hidden' name='tee' value='UUSI'>";
	echo "<table><tr>";
	echo "<th>".t('Perusta uusi matkalasku')."</th>";
	echo "<td class='back'><input type='Submit' value='".t("Perusta")."'></td>";
	echo "</tr></table>";
	echo "</form>";	
}


require("inc/footer.inc");
?>
