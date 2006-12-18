<?php
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Lähetä factoringaineisto").":</font><hr>";


	$query = "	SELECT lasku.* 
				FROM lasku 
				JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.factoring='SEB'
				WHERE lasku.yhtio		= '$kukarow[yhtio]' 
				and lasku.tila			= 'U' 
				and lasku.alatila		= 'X' 
				and lasku.vienti		= ''
				and lasku.factoringsiirtonumero = 0
				ORDER BY lasku.laskunro";
	$res = mysql_query($query) or pupe_error($query);
	
	echo "<br><br><table>";
	echo "<tr><th>#</th><th>".t("Laskunumero")."</th><th>".t("Päivämäärä")."</th><th>".t("Asiakas")."</th><th>".t("Maksuehto")."</th><th>".t("Arvo")."</th></tr>";
	
	$factlask=1;
	$factoring_sisalto="";
	
	$query = "	SELECT max(factoringsiirtonumero)+1 seuraava
				FROM lasku
				WHERE lasku.yhtio		= '$kukarow[yhtio]' 
				and lasku.tila			= 'U' 
				and lasku.alatila		= 'X' 
				and lasku.vienti		= ''
				and lasku.factoringsiirtonumero > 0";
	$aresult = mysql_query ($query) or pupe_error($query);
	$arow = mysql_fetch_array($aresult);
	
	while ($laskurow = mysql_fetch_array($res)) {
	
		$query  = "	select * 
					from maksuehto 
					where yhtio		= '$kukarow[yhtio]' 
					and tunnus		= '$laskurow[maksuehto]'";
		$result = mysql_query($query) or pupe_error($query);
		$masrow = mysql_fetch_array($result);
		
		$query = "	SELECT *
					FROM factoring
					WHERE yhtio = '$kukarow[yhtio]'
					and factoringyhtio = 'SEB'
					and valkoodi = '$laskurow[valkoodi]'";
		$fres = mysql_query($query) or pupe_error($query);
		$frow = mysql_fetch_array($fres);
			
		if($tee == 'LAHETA') {	
			//kerätään factoring aineiston sisältö				
			$sisalto 		= "";
			$fakt_lahetys 	= "";
			$sivu 			= 1;				
			
			require("seb_factoring.inc");			
			
			$factoring_sisalto .= $sisalto;	
						
			$query  = "update lasku set factoringsiirtonumero='$arow[seuraava]' where yhtio='$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]'";
			$sult = mysql_query($query) or pupe_error($query);
			
		}
			
		echo "<tr><td>$factlask</td><td>$laskurow[laskunro]</td><td>$laskurow[tapvm]</td><td>$laskurow[nimi]</td><td>$masrow[teksti]</td><td align='right'>$laskurow[arvo] $laskurow[valkoodi]</td></tr>";	
		$factlask++;
	}
	echo "</table><br><br>";
	
	if($tee == 'LAHETA' and $factoring_sisalto != '') {
		$sisalto = $factoring_sisalto;
		$fakt_lahetys 	= "OK";
		
		require("seb_factoring.inc");			
	}
	elseif ($factlask > 1) {
		echo "	<form action='$PHP_SELF' name='find' method='post'>
				<input type='hidden' name='tee' value='LAHETA'>
				<input type='Submit' value='".t("Lähetä aineisto")."'></form>";
	}
?>
