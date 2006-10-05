<?php
    require("../inc/parametrit.inc");
	
	echo "<font class='head'>".t("Tulosta kotitullauskirjanpito")."</font><hr><br>";
	
	if ($tee == 'TULOSTA') {
		$tulostimet[0] = 'Kotitullauskirjanpito';
		
		if (count($komento) == 0) {
			require("../inc/valitse_tulostin.inc");
		}
		
		if (isset($kka) or isset($ppa) or isset($vva)) {
			if (!checkdate($kka, $ppa, $vva)) {
				echo "<font class='error'>".t("Virheellinen päivämäärä")."!</font><br>";
				exit;
			}
		}
		if (isset($kkl) or isset($ppl) or isset($vvl)) {
			if (!checkdate($kkl, $ppl, $vvl)) {
				echo "<font class='error'>".t("Virheellinen päivämäärä")."!</font><br>";
				exit;
			}
		}
		
		$pp = date('d');
		$kk = date('m');
		$vv = date('Y');
		$ajopvm = $vv."-".$kk."-".$pp;
		
		$ulos = '';
		
		$alku  = "$vva-$kka-$ppa";
		$loppu = "$vvl-$kkl-$ppl";
		
		function alku () {
			global $ulos, $yhtiorow, $ajopvm, $alku, $loppu, $sivu;
			
			$ulos .= sprintf ('%-40.40s',	$yhtiorow["nimi"]);
			$ulos .= sprintf ('%-40.40s',	t("Kotitullauskirjanpito"));
			$ulos .= sprintf ('%-40.40s', 	t("Ajopäivä").": ".$ajopvm);
			$ulos .= "\r\n";
			
			$ulos .= sprintf ('%-40.40s', 	t("Jakso").": ".$alku." - ".$loppu);
			$ulos .= sprintf ('%-40.40s', 	t("Sivu").": ".$sivu);
			$ulos .= "\r\n";
			
			
			$ulos .= sprintf ('%-12.12s',	t("VientiPvm"));
			$ulos .= sprintf ('%-10.10s', 	t("Nimike"));
			$ulos .= sprintf ('%-8.8s', 	t("Kohtelu"));
			$ulos .= sprintf ('%-11.11s', 	t("Määrä")." (Kg)");
			$ulos .= sprintf ('%-12.12s', 	t("Kulj.muodot"));
			$ulos .= sprintf ('%-25.25s', 	t("Kuljetusvälineet"));
			$ulos .= sprintf ('%-6.6s', 	t("Tulli"));
			$ulos .= sprintf ('%-9.9s', 	t("Kauppat").".");
			$ulos .= sprintf ('%-9.9s', 	t("Määrämaa"));
			$ulos .= sprintf ('%-13.13s', 	t("Laskunumero"));
			$ulos .= "\r\n";
			
			$ulos .= "-----------------------------------------------------------------------------------------------------------------------";
			$ulos .= "\r\n";
			
			return $ulos;
		}
		
		
		$query = "	SELECT *
					FROM lasku
					WHERE vienti='K' and tila='U'
					and tullausnumero != '' 
					and tapvm >= '$vva-$kka-$ppa'
					and tapvm <= '$vvl-$kkl-$ppl'
					and yhtio='$kukarow[yhtio]'
					ORDER BY laskunro";
		$sresult = mysql_query($query) or pupe_error($query);
		
		//aloitellaan ekan sivun tekemistä
		$sivu = 1;
		$rivi = 1;
		
		alku();
		$rivi += 4;
			
		while($laskurow = mysql_fetch_array($sresult)) {
			if ($rivi >= 40) {
				$ulos .= chr(12);
				$rivi = 1;
				$sivu++;
				alku();
				$rivi += 4;
			}
			
			//hetaan kaikki otunnukset jotka löytyvät tän uusiotunnuksen alta jotta saadaan painot laskettua
			$query = "	SELECT distinct otunnus
						FROM tilausrivi
						WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]' 
						and tilausrivi.yhtio='$kukarow[yhtio]'";
			$uresult = mysql_query($query) or pupe_error($query);
			
			$tunnukset = '';
			
			while($urow = mysql_fetch_array($uresult)) {
				$tunnukset  .= "'".$urow['otunnus']."',";
			}
			
			$tunnukset = substr($tunnukset,0,-1);
			
			//haetaan kollimäärä ja bruttopaino
			$query = "	SELECT *
						FROM rahtikirjat
						WHERE otsikkonro in ($tunnukset) 
						and yhtio='$kukarow[yhtio]'";
			$rahtiresult = mysql_query($query) or pupe_error($query);
		
			$kilot  = 0;
			
			while($rahtirow = mysql_fetch_array($rahtiresult)) {
				$kilot  += $rahtirow["kilot"];
			}
						
			//Haetaan kaikki nimikkeet
			$query = "	SELECT tuote.tullinimike1, tilausrivi.uusiotunnus, tuote.tullikohtelu, round(sum((tilausrivi.rivihinta/$laskurow[summa])*$kilot),2) nettop
						FROM tilausrivi, tuote, tullinimike
						WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]' 
						and tilausrivi.yhtio = '$kukarow[yhtio]' 
						and tuote.yhtio = '$kukarow[yhtio]' 
						and tuote.tullinimike1 = tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]'
						and tuote.tuoteno = tilausrivi.tuoteno
						and tuote.ei_saldoa = ''
						and tilausrivi.kpl > 0
						GROUP BY 1,2,3
						ORDER BY 2,1";
			$riviresult = mysql_query($query) or pupe_error($query);
			
			while($rivirow = mysql_fetch_array($riviresult)) { 
				$rivi++;
				if ($rivi >= 45) {
					$ulos .= chr(12);
					$rivi = 1;
					$sivu++;
					alku();
					$rivi += 4;
				}
								
				$ulos .= sprintf ('%-12.12s',		$laskurow["tapvm"]);
				$ulos .= sprintf ('%-10.10s', 		$rivirow["tullinimike1"]);
				$ulos .= sprintf ('%-8.8s', 		$rivirow["tullikohtelu"]);
				$ulos .= sprintf ('%-11.11s', 		$rivirow["nettop"]);
				$ulos .= sprintf ('%-12.12s', 		$laskurow["sisamaan_kuljetusmuoto"]."/".$laskurow["kuljetusmuoto"]);
				
				$ulos .= sprintf ('%-12.12s',		$laskurow["sisamaan_kuljetus"]." ".$laskurow["sisamaan_kuljetus_kansallisuus"]);
				$ulos .= " ";
				$ulos .= sprintf ('%-12.12s',		$laskurow["aktiivinen_kuljetus"]." ".$laskurow["aktiivinen_kuljetus_kansallisuus"]);
				
				$ulos .= sprintf ('%-6.6s',			$laskurow["poistumistoimipaikka_koodi"]);
				$ulos .= sprintf ('%-9.9s',			$laskurow["kauppatapahtuman_luonne"]);	
				$ulos .= sprintf ('%-9.9s',			$laskurow["maa_maara"]);
				$ulos .= sprintf ('%-13.13s',		$laskurow["laskunro"]);
				$ulos .= "\r\n";
			}	
		}		
		
		//Avataan failit johon kirjotetaan
		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));
		$faili = "/tmp/Kotitullauskirjanpito-".md5(uniqid(mt_rand(), true)).".txt";
		
		$fh = fopen($faili, "w+");
		fwrite($fh, $ulos);
		fclose($fh);
				
		$line = exec("a2ps -o ".$faili.".ps --no-header --columns=1 -r --chars-per-line=120 --medium=A4 --margin=0 --borders=0 $faili");
	
		// itse print komento...
		$line = exec($komento["Kotitullauskirjanpito"]." ".$faili.".ps");
		
		//poistetaan tmp file samantien kuleksimasta...
		system("rm -f $faili");
		system("rm -f ".$faili.".ps");
		
		echo " ".t("Kotitullauskirjanpito tulostuu")."...<br><br>";
		$tee = '';	
	
	}
	
	if ($tee == '') {
		if (!isset($kka))
			$kka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		if (!isset($vva))
			$vva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		if (!isset($ppa))
			$ppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			
		if (!isset($kkl))
			$kkl = date("m");
		if (!isset($vvl))
			$vvl = date("Y");
		if (!isset($ppl))
			$ppl = date("d");

		
		//syötetään ajanjakso
		echo "<table>";
		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<input type='hidden' name='tee' value='TULOSTA'>
				<input type='hidden' name='toim' value='$toim'>";
		    
		echo "<tr><td class='back'></td><th>".t("pp")."</th><th>".t("kk")."</th><th>".t("vvvv")."</th></tr>";
		
		echo "<tr><th>".t("Syötä alkupäivämäärä")." </th>
			<td><input type='text' name='ppa' value='$ppa' size='5'></td>
			<td><input type='text' name='kka' value='$kka' size='5'></td>
			<td><input type='text' name='vva' value='$vva' size='7'></td>
			</tr><tr><th>".t("Syötä loppupäivämäärä")." </th>
			<td><input type='text' name='ppl' value='$ppl' size='5'></td>
			<td><input type='text' name='kkl' value='$kkl' size='5'></td>
			<td><input type='text' name='vvl' value='$vvl' size='7'></td>";
		
		echo "<td><input type='submit' value='".t("Tulosta")."'></td></tr>";
		echo "</form>";
		echo "</table>";
	}
	
	require ('../inc/footer.inc');	
	
?>
