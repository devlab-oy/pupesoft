<?php

/*
	Tänne tarvitaan: 
	$jysum joka on loppusumma joka tilaukselle halutaan
	$tilausnumero
	
	Jos jyvsumma on negatiivinen on se alennuksen määrä
	Jos jyvsumma sisältää % merkin annetaan erikoisalennusta
	
	$yhtiorow["jyvita_alennus"] 
		''  = Alennus jokaisen rivin hintaan
		'P' = Alennus annetaan tuotteella vain projektilla
		'T' = Alennus annetaan tuotteella aina
*/	

if(strpos($jysum, "%") !== false) {
	$jysum = str_replace("%","", $jysum);
	$anna_ea = "TOKI";
}

$jysum = round(str_replace(",",".", $jysum),2);

//Lasketaan tilauksen arvot
if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
	$hinta_riv = "(tilausrivi.hinta/$laskurow[vienti_kurssi])";
}
else {
	$hinta_riv = "tilausrivi.hinta";
}

$query = "	SELECT 
			sum(if(varattu+jt>0, $hinta_riv * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)), 0)) plus_summa,
			sum(if(varattu+jt<0, $hinta_riv * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100)), 0)) nega_summa
			FROM tilausrivi 
			WHERE yhtio = '$kukarow[yhtio]' 
			and otunnus = '$tilausnumero' 
			and rivihinta = 0 
			and uusiotunnus = 0";
$result = mysql_query($query) or pupe_error($query);
$tarow = mysql_fetch_array($result);

$plus_summa = $tarow["plus_summa"];
$nega_summa = $tarow["nega_summa"];

if($anna_ea == "TOKI" and ($jysum < 0 or $jysum >= 100)) {
	echo "<font class='error'>".t("Erikoisalennus tulee olla")." 0 - 100%!!</font><br>";		
}
elseif($jysum <= 0 and $anna_ea != "TOKI") {
	echo "<font class='error'>".t("Kamaa ei kannata myydä tappiolla")."!!</font><br>";		
}
else {
	
	//	 Nollataan otsikko ja lasketaan alet uudestaan
	$query = "UPDATE lasku SET erikoisale = '0' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero' and tilaustyyppi = 'N' and tila IN ('V','N','G','L') and alatila NOT IN ('V','X')";
	$upresult = mysql_query($query) or pupe_error($query);				
	
	if($anna_ea == "TOKI") {
		// 	Annetaan erikoisale
		$query = "UPDATE lasku SET erikoisale = '$jysum' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero' and tilaustyyppi = 'N' and tila IN ('V','N','G','L') and alatila NOT IN ('V','X')";
		$upresult = mysql_query($query) or pupe_error($query);

		$laskurow["erikoisale"] = $jysum;				
	}
	elseif($yhtiorow["jyvita_alennus"] == "T" or ($yhtiorow["jyvita_alennus"] == "P" and $toim == "PROJEKTI")) {
		// 	Alennus lisätään alennustuotteelle 
		//	TODO tämä on osittain turhaa koska projektia ei ole vielä köytössä, kohta on!
		
		echo "jyvitetään tuotteella";
		
		$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[alennus_tuotenumero]'";
		$rhire = mysql_query($query) or pupe_error($query);
		
		if(mysql_num_rows($rhire) == 1) {
			$trow  = mysql_fetch_array($rhire);

			$hinta		= round(($summa - $jysum),2);
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
	else {
		//	Jyvitetään riveille uusihinta ja tallennetaan syötetty summa lasku.hinta-kenttään			
		// huomioidaan hyvitysrivit ja ei muuteta niitten hintoja
		$halu_summa = $jysum - $nega_summa;
		
		$jypro = $halu_summa/$plus_summa;

		// Rullataan tilausrivejä
		$query = "	SELECT tunnus, tuoteno, hinta, tilkpl, ale
					FROM tilausrivi 
					where yhtio 	 = '$kukarow[yhtio]' 
					and varattu + jt > 0 
					and otunnus 	 = '$tilausnumero' 
					and rivihinta 	 = 0 
					and uusiotunnus  = 0
					order by hinta";
		$result = mysql_query($query) or pupe_error($query);
				
		while ($tirow = mysql_fetch_array($result)) {
			
			$uushinta = round($tirow['hinta'] * $jypro,2);
						
			$query = "UPDATE tilausrivi SET hinta = '$uushinta' WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tirow[tunnus]'";
			$upresult = mysql_query($query) or pupe_error($query);
			
		}
		
		$jysum = round($jysum,2);
		
		//  Päivitetään laskulle hinta-kenttään syötetty summa
		$query	= "update lasku set hinta = '$jysum' where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
		$result	= mysql_query($query) or pupe_error($query);
		
		$laskurow["hinta"] = $jysum;
	}
}

$jysum		= '';
$tila		= '';
$tee		= '';
$kiekat 	= "";
$summa 		= "";
$anna_ea 	= "";
$hinta		= "";

?>