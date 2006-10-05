<?php
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Lähetä factoringaineisto").":</font><hr>";


	$query = "	select * 
				from lasku 
				where yhtio		= '$kukarow[yhtio]' 
				and tila		= 'U' 
				and alatila		= 'X' 
				and vienti		= ''
				and maksutyyppi	= ''
				ORDER BY laskunro";
	$res = mysql_query($query) or pupe_error($query);
	
	echo "<br><br><table>";
	echo "<tr><th>#</th><th>".t("Laskunumero")."</th><th>".t("Päivämäärä")."</th><th>".t("Asiakas")."</th><th>".t("Maksuehto")."</th><th>".t("Arvo")."</th></tr>";
	
	$factlask=1;
	$factoring_sisalto="";
	
	while ($laskurow = mysql_fetch_array($res)) {
	
		$query  = "	select * 
					from maksuehto 
					where yhtio		= '$kukarow[yhtio]' 
					and tunnus		= '$laskurow[maksuehto]'
					and factoring  != ''";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result)!=0) {
			
			//tässä meillä on lasku joka kuuluu lähettää factoring yhtiöön
			$masrow = mysql_fetch_array($result);
			
			
			if($tee == 'LAHETA') {	
				//kerätään factoring aineiston sisältö				
				$sisalto 		= "";
				$fakt_lahetys 	= "";
				$sivu 			= 1;				
				
				require("seb_factoring.inc");			
				
				$factoring_sisalto .= $sisalto;	
							
				$query  = "update lasku set maksutyyppi='F' where yhtio='$kukarow[yhtio]' and tunnus = '$laskurow[tunnus]'";
				$sult = mysql_query($query) or pupe_error($query);
				
			}
			
			echo "<tr><td>$factlask</td><td>$laskurow[laskunro]</td><td>$laskurow[tapvm]</td><td>$laskurow[nimi]</td><td>$masrow[teksti]</td><td align='right'>$laskurow[arvo] $laskurow[valkoodi]</td></tr>";
			
			$factlask++;			
		}
	}
	echo "</table><br><br>";
	
	if($tee == 'LAHETA') {	
		if ($factoring_sisalto != '') {
			$sisalto = $factoring_sisalto;
			$fakt_lahetys 	= "OK";
			
			require("seb_factoring.inc");
		}				
	}
	else {			
		if ($factlask > 1) {
			echo "	<form action='$PHP_SELF' name='find' method='post'>
					<input type='hidden' name='tee' value='LAHETA'>
					<input type='Submit' value='".t("Lähetä aineisto")."'></form>";	
		}
	}
?>
