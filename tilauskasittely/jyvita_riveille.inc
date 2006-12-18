<?php

/*
	Tänne tarvitaan $jyvsumma joka on loppusumma joka tilaukselle halutaan ja $tilausnumero
	Jos jyvsumma on negatiivinen on se alennuksen määrä
	Jos jyvsumma sisältää % merkin annetaan erikoisalennusta
	$yhtiorow["jyvita_alennus"] 
		''  = Alennus jokaisen rivin hintaan
		'P' = Alennus annetaan tuotteella vain projektilla
		'T' = Alennus annetaan tuotteella aina
*/	

if(strpos($jyvsumma, "%") !== false) {
	$jyvsumma = str_replace("%","", $jyvsumma);
	$anna_ea = "TOKI";
}

$jyvsumma = round(str_replace(",",".", $jyvsumma),2);

//	Ollaan annettu alennuksen määrä
if($jyvsumma < 0) {
	$jyvsumma = $arvo + $jyvsumma;
}

// otetaan talteen
$ed_arvo = round($arvo,2);

if($anna_ea == "TOKI" and ($jyvsumma < 0 or $jyvsumma >= 100)) {
	echo "<font class='error'>".t("Erikoisalennus tulee olla")." 0 - 100%!!</font><br>";		
}
elseif($jyvsumma <= 0 and $anna_ea != "TOKI") {
	echo "<font class='error'>".t("Kamaa ei kannata myydä tappiolla")."!!</font><br>";		
}
else {
	$jerotus = $arvo-$jyvsumma;
	$jpros = $jyvsumma/$arvo;
	
	//	 Nollataan otsikko ja lasketaan alet uudestaan
	$query = "UPDATE lasku SET erikoisale = '0' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero' and tilaustyyppi = 'N' and tila IN ('V','N','G','L') and alatila NOT IN ('V','X')";
	$upresult = mysql_query($query) or pupe_error($query);				
	
	// 	Annetaan erikoisale
	if($anna_ea == "TOKI") {
		$query = "UPDATE lasku SET erikoisale = '$jyvsumma' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero' and tilaustyyppi = 'N' and tila IN ('V','N','G','L') and alatila NOT IN ('V','X')";
		$upresult = mysql_query($query) or pupe_error($query);				
	}
	// 	Alennus lisätään alennustuotteelle 
	//	TODO tämä on osittain turhaa koska projektia ei ole vilä köytössä kohta on!
	elseif($yhtiorow["jyvita_alennus"] == "T" or ($yhtiorow["jyvita_alennus"] == "P" and $toim == "PROJEKTI")) {
		echo "jyvitetään tuotteella";
		$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[alennus_tuotenumero]'";
		$rhire = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($rhire) == 1) {
			$trow  = mysql_fetch_array($rhire);

			$hinta		= round(($arvo - $jyvsumma),2);
			$alv 		= '';
			$trow["alv"]= $laskurow["alv"];
			$nimitys	= $trow["nimitys"];

			require("alv.inc");

			$query  = "insert into tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) values  (now(), '$hinta', 'N', '-1', '-1', '$tilausnumero', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$alv', '')";
			$addtil = mysql_query($query) or pupe_error($query);			
		}
		else {
			echo "VIRHE: Alennusta ei voitu antaa alennustuote puuttuu!<br>";
		}
	}
	//	Jyvitetään riveille uusihinta
	else {
		
		// loopataan tätä läpi, jotta saadaan hinnat oikein pennilleen, kerralla tämä ei tunnu natsaavan kohilleen
		$kiekat = 0;
		$jerotus = 1;
		while($jerotus <> 0) {
			
			$kiekat++;
			
			$jysumma = round($jyvsumma,2);
			
			$jpros = $jyvsumma/$arvo;
			//	Rullataan tilausrivejä
			$query ="SELECT tunnus, tuoteno, hinta, tilkpl FROM tilausrivi where yhtio = '$kukarow[yhtio]' and tilkpl > 0 and otunnus = '$tilausnumero' and rivihinta = 0 and uusiotunnus = 0";
			$result = mysql_query($query) or pupe_error($query);
			while ($tirow=mysql_fetch_array($result)) {
				$uushinta = round($tirow['hinta']*$jpros,2);
				
				$query = "UPDATE tilausrivi SET hinta = '$uushinta' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tirow[tunnus]'";
				$upresult = mysql_query($query) or pupe_error($query);
				$viimeisintunnus = $tirow["tunnus"];
			}
			
			// tarkastus
			$query ="SELECT sum((hinta * (1-(ale/100))) * (varattu + jt)) uusiarvo FROM tilausrivi where yhtio = '$kukarow[yhtio]' and otunnus = '$tilausnumero' and rivihinta = 0 and uusiotunnus = 0";
			$result = mysql_query($query) or pupe_error($query);
			$tarow = mysql_fetch_array($result);
			$arvo = round($tarow["uusiarvo"],2);
			$jerotus = round(($arvo - $jyvsumma),2);
					
			//	Ei haluta jäädä luuppiin
			//	TODO jos meillä on useita kappaleita myytävänä niin tuo hinta ei mene aina sentilleen oikein koska hinta * kpl ei natsaa. Voisiko tämän saada toimimaan?
			if($kiekat > 4) {
				$jerotus = 0;
				echo "HUOM! jyvittelystä jäi $jerotus jäljelle.<br>";
			}
		}		
	}
	
	//  haetaan laskun tiedot uudestaan jotta uusi erikoisale toimii
	$query   	= "	select *from lasku where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$result  	= mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($result);
}

$jyvsumma='';
$tila='';
$tee='';
$kiekat = "";
$arvo = "";
$anna_ea = "";

?>