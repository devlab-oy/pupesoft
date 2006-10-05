<?php
	
	if (file_exists("../inc/parametrit.inc")) {
		require ("../inc/parametrit.inc");
	}
	else {
		require ("parametrit.inc");
	}
	
	if ($toim == '') {
		$selain = "varaosaselain";
	}
	else {
		$selain = $toim."selain";
	}

	
	echo "<font class='head'>".t(ucfirst(strtolower($selain)))."</font><hr>";
	
	if (strtoupper($yhtiorow['kieli']) != 'FI') {
		$selain .= $yhtiorow['kieli'];
	}
	
	$query    = "select * from lasku where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$result   = mysql_query($query, $link) or pupe_error($query);
	$laskurow = mysql_fetch_array($result);
	
	// Tarkistetaan tilausrivi
	if ($tee == 'TI') {
		if ($kpl > 0) {
			$query = "	SELECT *
						FROM tuote
						WHERE tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]' and myyntihinta>0";				
			$tuoteresult = mysql_query($query) or pupe_error($query);		
						
			if(mysql_num_rows($tuoteresult) == 1) {
				
				$trow = mysql_fetch_array($tuoteresult);
								
				$hinta 	= ""; 
				$netto 	= ""; 
				$ale 	= "";
				$var	= "";
				$alv	= "";			
				$toimaika 	= $laskurow["toimaika"];
				$kerayspvm	= $laskurow["kerayspvm"];

				require ('lisaarivi.inc');
				
				echo "<br><font class='message'>".t("Lisättiin tilaukselle tuote").": $tuoteno</font><br><br>";

				$tee 		= "Y";
				$tuoteno	= '';
				$kpl		= '';
				$var		= '';
				$hinta		= '';
				$netto		= '';
				$ale		= '';
				$rivitunnus	= '';
				$kommentti	= '';
				$kerayspvm	= '';
				$toimaika	= '';
				$paikka		= '';
				$alv		= '';							
			}
			else {
				$varaosavirhe = t("VIRHE: Tuotetta ei löydy")."!<br>";
			}
		}
		else {
			$varaosavirhe = t("VIRHE: Syötä kappalemäärä")."!<br>";
			$kpl = "";
		}
		$tee = "Y";
	}
	
	echo "	<script language='javascript'> 
					function DisableText() {
						var count = document.forms.length;
						
						for (i=1; i<count; i++) {						
							var count2 = document.forms[i].elements.length;
							
							for (j=0; j<count2; j++) {
								var element = document.forms[i].elements[j]; 
								
								if (element.type == 'text' && element.value == '') { 
									element.disabled=true; 
								}  								
							}
						}
					}
				</script> ";
				
	$con   = mysql_connect($dbhostvosa, $dbuservosa, $dbpassvosa) or die("Yhteys tietokantaan epaonnistui!");
	$boo   = mysql_select_db ($dbkantavosa) or die ("Tietokanta ei löydy palvelimelta..");
	
	$query = "select distinct merkki from $selain order by merkki";
	$res   = mysql_query($query,$con);
	
	echo "<form action='$PHP_SELF' name='varaosaselain' method='post'>";
	echo "<input type='hidden' name='toim' value='$toim'";
	echo "<table><tr>";
	echo "<td><select name='merkki' onchange='submit()'>";
	
	
	echo "<option value=''>".t("Valitse merkki")."</option>";
	
	
	while ($rivi=mysql_fetch_array($res))
	{
		$selected='';
		if ($merkki==$rivi[0]) $selected=' SELECTED';
		echo "<option value='$rivi[0]'$selected>$rivi[0]</option>";
	}
	
	echo "</select></td>";
	
	if ($merkki!=$oldmerkki)
	{
		$cc='';
		$malli='';
		$vm='';
	}
	
	echo "<td><select name='cc' onchange='submit()'>";
	echo "<option value=''>".t("Valitse CC")."</option>";
	if ($merkki!='')
	{
		$query = "select distinct cc from $selain where merkki='$merkki' order by cc";
		$res   = mysql_query($query,$con);
	
		while ($rivi=mysql_fetch_array($res))
		{
			$selected='';
			if ($cc==$rivi[0]) $selected=' SELECTED';
			echo "<option value='$rivi[0]'$selected>$rivi[0]</option>";
		}
	}
	echo "</select></td>";
	
	if ($cc!=$oldcc)
	{
		$malli='';
		$vm='';
	}
	
	echo "<td><select name='malli' onchange='submit()'>";
	echo "<option value=''>".t("Valitse Malli")."</option>";
	if ($cc!='')
	{
		$query = "select distinct malli from $selain where merkki='$merkki' and cc='$cc' order by malli";
		$res   = mysql_query($query,$con);
	
		while ($rivi=mysql_fetch_array($res))
		{
			$selected='';
			if ($malli==$rivi[0]) $selected=' SELECTED';
			echo "<option value='$rivi[0]'$selected>$rivi[0]</option>";
		}
	}
	echo "</select></td>";
	
	if ($malli!=$oldmalli)
	{
		$vm='';
	}
	
	echo "<td><select name='vm' onchange='submit()'>";
	echo "<option value=''>".t("Valitse Vuosi")."</option>";
	if ($malli!='')
	{
		$query = "select distinct vm from $selain where merkki='$merkki' and cc='$cc' and malli='$malli' order by vm";
		$res   = mysql_query($query,$con);
	
		while ($rivi=mysql_fetch_array($res))
		{
			$selected='';
			if ($vm==$rivi[0]) $selected=' SELECTED';
			echo "<option value='$rivi[0]'$selected>$rivi[0]</option>";
		}
	}
	echo "</select></td>";
	
	echo "<input type='hidden' name='oldmerkki' value='$merkki'>";
	echo "<input type='hidden' name='oldcc' value='$cc'>";
	echo "<input type='hidden' name='oldmalli' value='$malli'>";
	echo "<input type='hidden' name='oldvm' value='$vm'>";
	echo "</tr></table>";
	echo "</form>\n";
	
	if ($vm!='')
	{
		$query = "select tuoteno, tuoteryhma from $selain where merkki='$merkki' and cc='$cc' and malli='$malli' and vm='$vm' order by tuoteryhma";
		$res   = mysql_query($query,$con);
	
		echo "<br><font class='header'>".t("Tuotteet").":</font><br><br>";
		
		if ($kukarow['extranet'] != '') {
			require ("connect.inc");
		}
		else {
			require ("../inc/connect.inc");
		}
		echo "<table>";
		echo "<tr><th>".t("Nimitys")."</th><th>".t("Tuotenumero")."</th><th>".t("Myyntihinta")."</th><th>".t("Varastossa")."</th><th>".t("Lisää tilaukseen")."</th></tr>";
		
		while ($rivi=mysql_fetch_array($res))
		{
			$tuote = $rivi[0];
	//		$wrong = array(" ","-",".","Ö","Ä","Å","ö","ä","å");	//mitä korvataan
	//		$right = array("" ,"" ,"" ,"O","A","A","o","a","a");	//millä korvataan
	//		$tuote = str_replace($wrong, $right, $tuote);
	//		$tuote = ereg_replace("^0*", "", $tuote);				//vielä etunollat pois
			
			$query = "select myyntihinta, ei_saldoa from tuote where tuoteno='$tuote' and yhtio='$kukarow[yhtio]'";
			$res2  = mysql_query($query, $link) or pupe_error($query);
			$row2  = mysql_fetch_array($res2);
	
			$eka      = 0;
			$vapaana  = 0;
			$vapaana2 = 0;
			
			// Katsotaan myytävissä oleva määrä
			if ($row2["ei_saldoa"] == '') {
				$query = "	select * 
							from varastopaikat 
							where yhtio = '$kukarow[yhtio]' 
							and tyyppi  = ''";
				$varastoresult = mysql_query($query, $link) or pupe_error($query);
				
				while ($varastorow = mysql_fetch_array ($varastoresult)) {
					//saldo
					$query = "	select sum(saldo) saldo 
								from tuotepaikat 
								where tuoteno  = '$tuote' 
								and yhtio      = '$kukarow[yhtio]'
								and hyllyalue >= '$varastorow[alkuhyllyalue]' 
								and hyllynro  >= '$varastorow[alkuhyllynro]' 
								and hyllyalue <= '$varastorow[loppuhyllyalue]' 
								and hyllynro  <= '$varastorow[loppuhyllynro]'";
					$alkuresult = mysql_query($query, $link) or pupe_error($query);
					$alkurow = mysql_fetch_array($alkuresult);
		
					//ennakkopoistot
					$query = "	SELECT sum(varattu) varattu
								FROM tilausrivi
								WHERE tyyppi  in ('L','G','V') 
								and yhtio 	   = '$kukarow[yhtio]' 
								and tuoteno    = '$tuote' 
								and varattu    > '0'
								and hyllyalue >= '$varastorow[alkuhyllyalue]' 
								and hyllynro  >= '$varastorow[alkuhyllynro]' 
								and hyllyalue <= '$varastorow[loppuhyllyalue]' 
								and hyllynro  <= '$varastorow[loppuhyllynro]'";
					$varatutresult = mysql_query($query, $link) or pupe_error($query);
					$varatutrow = mysql_fetch_array($varatutresult);
		
					$vapaana = $alkurow["saldo"] - $varatutrow["varattu"];
					$vapaana2 += $vapaana;
				}
			}
			else {
				$vapaana2 = 1;
			}
			
			
			if (mysql_num_rows($res2)=='1') {			
				$ruuhinta = "$row2[myyntihinta] $yhtiorow[valkoodi]";
			}
			else { 
				$ruuhinta = '-';
			}
	
			
			
			echo "<tr><td>$rivi[tuoteryhma]</td><td>$rivi[tuoteno]</td><td align='right'>$ruuhinta</td>";
			
			
			if ($vapaana2 > 0) {
				echo "<td class='green'>".t("On")."</td>";
			}
			else {
				echo "<td class='red'>".t("Ei")."</td>";
			}				
			
			if ($kukarow["kesken"] != 0) {
					
				if ($tuoteno == '' or $tuote == $tuoteno) {
					if ($kukarow["extranet"] == '') {
						$miniliittyma = "JOO";
					}
					
					echo "<td>";
					
					$miniliittyma = "JOO";
					$hi_tuoteno = $tuote;
					
					require('syotarivi.inc');				
					
					echo "</td>";
				}
			}
			else {
				echo "<td>".t("Sinulla ei ole tilausta auki")."!</td>";
			}
			
			echo "</tr>\n";
		}
	
		echo "</table>\n";
	
	}
	if ($kukarow['extranet'] != '') {
		require ("footer.inc");
	}
	else {
		require ("../inc/footer.inc");
	}
?>