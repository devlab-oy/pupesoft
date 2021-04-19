<?php
	require "inc/parametrit.inc";

	js_popup();	
	enable_ajax();
	
		
	echo "<font class='head'>".t("Tiliöintien muutos/selailu")."</font><hr><br>";

	if ((($tee == 'U') or ($tee == 'P') or ($tee == 'M') or ($tee == 'J')) and ($oikeurow['paivitys'] != 1)) {
		echo "<b>".t("Yritit päivittää vaikka simulla ei ole siihen oikeuksia")."</b>";
		exit;
	}
	
	if($tee == "1") {
		
	}

	if($tee == "kuittaa") {
		if($tunnus >0) {
			$query = "	UPDATE lasku
						SET 
						tarkistettu = now(),
						tarkistanut = '$kukarow[kuka]'
						WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
			$res = mysql_query($query) or pupe_error($query);
			$tarkistamattomat=1;
		}
		$tee = "";
	}
	
	if($tee == "") {
		
			if ($laji=='') $laji='O';
			if($laji=='M') $selm='SELECTED';
			if($laji=='O') $selo='SELECTED';
			if($laji=='MM') $selmm='SELECTED';
			if($laji=='OM') $selom='SELECTED';
			if($laji=='X') $selx='SELECTED';

			if($laji=='M') $lajiv="tila = 'U'";
			if($laji=='O') $lajiv="tila in ('H', 'Y', 'M', 'P', 'Q')";

			$pvm='tapvm';
			if($laji=='OM') {
				$lajiv="tila = 'Y'";
				$pvm='mapvm';
			}
			if ($laji == 'MM') {
				$lajiv="tila = 'U'";
				$pvm='mapvm';
			}	
			if($laji=='X') $lajiv="tila = 'X'";
			
			if($tarkistamattomat != "") {
				$lajiv .= "and tarkistanut=''";
			}
			
			// mikä kuu/vuosi nyt on
			$year = date("Y");
			$kuu  = date("n");
			// poimitaan erikseen edellisen kuun viimeisen päivän vv,kk,pp raportin oletuspäivämääräksi
			if($vv=='') $vv = date("Y",mktime(0,0,0,$kuu,0,$year));
			if($kk=='') $kk = date("n",mktime(0,0,0,$kuu,0,$year));
			if(strlen($kk)==1) $kk = "0" . $kk; 


			//Ylös hakukriteerit
			if ($viivatut == 'on') $viivacheck='checked';
			if ($tarkistamattomat != "") $tarkcheck='checked';
			echo "<div id='ylos' style=''>
					<form name = 'valinta' action = '$PHP_SELF' method='post'>
					<table>
					<tr><th>".t("Anna kausi, muodossa kk-vvvv").":</th>
					<td><input type = 'text' name = 'kk' value='$kk' size=2></td>
					<td><input type = 'text' name = 'vv' value='$vv' size=4></td>
					<th>".t("Mitkä tositteet listataan").":</th>
					<td><select name='laji'>
					<option value='M' $selm>".t("myyntilaskut")."
					<option value='O' $selo>".t("ostolaskut")."
					<option value='MM' $selmm>".t("myyntilaskut maksettu")."
					<option value='OM' $selom>".t("ostolaskut maksettu")."
					<option value='X' $selx>".t("muut")."
					</select></td>
					<td><input type='checkbox' name='viivatut' $viivacheck> ".t("Korjatut")."</td>
					<td><input type='checkbox' name='tarkistamattomat' $tarkcheck> ".t("Tarkistamattomat")."</td>
					<td class='back'><input type = 'submit' value = '".t("Valitse")."'></td>
					</tr>
					</table>
					</form>
				</div><br>";
			$formi = 'valinta';
			$kentta = 'kk';
		
			if ($vv < 2000) $vv += 2000;
			$lvv=$vv;
			$lkk=$kk;
			$lkk++;
			if ($lkk > 12) {
				$lkk='01';
				$lvv++;
			}


			$query = "	SELECT tunnus, nimi, $pvm, summa, if(tarkistettu!='0000-00-00 00:00:00', concat_ws('@', tarkistanut, tarkistettu), '') tarkistettu, comments, ebid
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and $pvm >= '$vv-$kk-01' and $pvm < '$lvv-$lkk-01' and $lajiv
						ORDER BY tapvm desc, summa desc";

			$result = mysql_query($query) or pupe_error($query);
			$loppudiv = $kalascript = "";
			$eka = true;
			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Haulla ei löytynyt yhtään laskua")."</font>";

			}
			else {
				$seutunnus = 0;
				echo "<div id='laskuluettelo' style='width: 900px; height: 200px; overflow: scroll;'>
				<table><tr>";
				echo "<th></th>";
				for ($i = 1; $i < mysql_num_fields($result)-2; $i++) {
					echo "<th>" . t(mysql_field_name($result,$i))."</th>";
				}
				echo "</tr>";
				echo "<tr>";
				while ($trow=mysql_fetch_array ($result)) {

					if ($trow['comments'] != '') {
						$loppudiv .= "<div id='div_".$trow['tunnus']."' class='popup' style='width:250px'>";
						$loppudiv .= $trow["comments"]."<br></div>";
						echo "<td valign='top' class='tooltip' id='$trow[tunnus]'><img src='pics/lullacons/alert.png'></td>";
					}
					else 
						echo "<td></td>";

					for ($i=1; $i<mysql_num_fields($result)-2; $i++) {
						echo "<td>$trow[$i]</td>";
					}
										
					$kuvalisa = "";
					if(in_array($laji, array("M", "MM"))) {
						$kuvalisa = "document.getElementById('laskukuva').src='tilauskasittely/tulostakopio.php?otunnus=$trow[tunnus]&toim=LASKU&tee=NAYTATILAUS';";
					}
					else {
						$laskut = ebid($trow['tunnus'], true);
						if(is_array($laskut)) {
							$lasku = $laskut[0];
						}
						else {
							$lasku = $laskut;
						}
						
						if($lasku != "") {
							$kuvalisa = "document.getElementById('laskukuva').src='$lasku';";
						}
					}
					
					$tiliointilisa = "document.getElementById('tilioinnit').src='muutosite.php?tee=E&tunnus=$trow[tunnus]';";
					if($trow["tarkistettu"] == "") {
						$seuraavalinkkilisa = "document.getElementById('kuittaus').innerHTML=unescape('%3Ccenter%3E%3Cbr%3E%3Ca%20href%3D%27$PHP_SELF%3Ftee%3Dkuittaa%26tunnus%3D$trow[tunnus]%26laji%3D$laji%26vv%3D$vv%26kk%3D$kk%27%3E%3Cfont%20class%3D%27error%27%3EKuittaa%20tarkastetuksi%20ja%20valitse%20seuraava%3C%2Ffont%3E%3C%2Fa%3E%3C%2Fcenter%3E%3Cbr%3E');";
					}
					else {
						$seuraavalinkkilisa = "document.getElementById('kuittaus').innerHTML=unescape('%3Ccenter%3E%3Cbr%3E%3Cfont%20class%3D%27message%27%3ELasku%20on%20tarkistettu%20$trow[tarkistettu]%3C%2Ffont%3E%3Cbr%3E%3C%2Fcenter%3E%3Cbr%3E');";
					}

					echo "<td id='rivi_$trow[tunnus]' class='back'><a href name='$trow[tunnus]'><a href='' onclick=\"$tiliointilisa $kuvalisa $seuraavalinkki return false;\">Avaa</a></td>";
					echo "</tr>";
					
					if($eka===true) {
						$kalascript = "
							<SCRIPT TYPE='text/javascript' LANGUAGE='JavaScript'>
								$kuvalisa
								$tiliointilisa
								$seuraavalinkkilisa
							</SCRIPT>";
							$eka = false;
					}
				}
			}
			echo "</tr></table>
			</div>
			<iFrame id='tilioinnit' style='width: 900px; height: 500px; overflow: scroll;'></iFrame><br>
			<div id='kuittaus' style='width: 900px;'></div>
			<iFrame id='laskukuva' style='width: 900px; height: 800px; overflow: scroll;'></iFrame>
			$kalascript";
		
	}
	
	require "inc/footer.inc";
?>