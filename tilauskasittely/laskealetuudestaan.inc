<?php
	//Jos tullaan jtselauksesta niin huijataan
	if ($mista== 'jtselaus') {
		$kukarow['kesken']=$laskurow['tunnus'];

		// Nyt alennukset ja hinnat ja alvi lasketaan automaattisesti uudestaan
		$query = "select * from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
		$jresultlask = mysql_query($query) or pupe_error($query);	
	}
	elseif ($mista== 'jtrivit_tilaukselle.inc') {
		// Nyt alennukset ja hinnat ja alvi lasketaan automaattisesti uudestaan
		$query = "select * from tilausrivi where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
		$jresultlask = mysql_query($query) or pupe_error($query);
	}
	elseif ($mista== 'jtrivit_tilaukselle.php' and $kukarow['extranet'] != '') {
		// Nyt alennukset ja hinnat ja alvi lasketaan automaattisesti uudestaan
		$query = "select * from tilausrivi where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
		$jresultlask = mysql_query($query) or pupe_error($query);
		
		$laskurow["ytunnus"] = $kukarow["oletus_asiakas"];
	}
	


	while ($jjrow = mysql_fetch_array($jresultlask)) {
		
		$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$jjrow[tuoteno]'";
		$tres  = mysql_query($query) or pupe_error($query);
		$trow  = mysql_fetch_array($tres);
		
		$query = "	SELECT alennus
					FROM asiakasalennus
					WHERE yhtio='$kukarow[yhtio]' and ryhma = '$trow[aleryhma]' and ytunnus = '$laskurow[ytunnus]'";
		$aleresult = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($aleresult) != 0) {
			$alerow = mysql_fetch_array ($aleresult);
			//jos rivi on katemyyty tai käyttäjä haluaa, lasketaan ale uudestaan
			if (($alerow["alennus"]{0} == "-" and $trow["kehahin"] > 0) or $laskeuusix != "") {

				$jkpl	= $jjrow['tilkpl'];										
				$debug	= 0;
				$netto	= $jjrow['netto'];
		
				if ($debug == 1) {
					echo "vienti=$vienti alv=$alv kpl=$jkpl netto=$netto akplhinta=$akplhinta aale=$aale rowtilkpl=$jjrow[tilkpl] rowalv=$jjrow[alv] rowhinta=$jjrow[hinta] rowale=$jjrow[ale] apuroytunnus=$apuro[ytunnus] ytunnus=$ytunnus apuroytunnus=$apuro[ytunnus] mista=$mista tunnus=$tunnus<br>";
					echo "jrowtunnus=$jjrow[tunnus]<br>";
			    }
	    
			    if ($netto != "N") {
					$hinta 	= 0;
					$ale	= 0;
					if ($kukarow["extranet"] == '') {
				       	require ("../inc/alehinta.inc"); // haetaan tuotteelle asiakaskohtaisia hintoja..
					}
					else {
				       	require ("alehinta.inc"); // haetaan tuotteelle asiakaskohtaisia hintoja..
					}	
				}
    
				//lasketaan alvit
				$alv = $jjrow["alv"];
				
				require ("alv.inc");

				$query = "update tilausrivi set hinta='$hinta', ale='$ale', netto='$netto', alv='$alv' where yhtio='$kukarow[yhtio]' and tunnus='$jjrow[tunnus]'";
				$tres  = mysql_query($query) or pupe_error($query);
				
				if ($debug == 1) {
					echo "$query<br>";
			    }
				
				$netto='';
				$hinta=0;
				$ale=0;
				$hinta=0;
				$ale=0;
				$alv=0;
				$jkpl=0;
			}
		}
	}
?>