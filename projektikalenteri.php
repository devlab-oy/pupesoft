<?php
/*
	JOS toim="HAKU" niin annetaan käyttää hakutoimintoa
*/
require('inc/parametrit.inc');

//echo "data:<pre>".print_r($_REQUEST, true)."</pre>"; //muuttujien debuggaus
if($nayta_pdf != 1) {
	js_popup();
	js_showhide();
	enable_ajax();	
}

require('inc/kalenteri.inc');

if ($tee == "tuntiyhteenveto") {
	//tsekataan onko projektille uhrattu ihan tuntejakin
	$query = "	SELECT minuuttimaara, tyyppi, (
					SELECT sum(minuuttimaara) 
					FROM kulunvalvonta kv 
					WHERE kv.yhtio='$kukarow[yhtio]' and kv.otunnus='$projekti') 
				AS minuutitsumma, (
					SELECT nimi 
					FROM kuka 
					WHERE kuka=kulunvalvonta.kuka and yhtio='$kukarow[yhtio]') 
				AS nimi
				FROM kulunvalvonta
				WHERE yhtio='$kukarow[yhtio]' and otunnus='$projekti'
				GROUP BY kuka";
	$result = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($result) == 0) {
		echo "<br><font class='info'>" .t("Ei työtunteja") . "</font>";
	}
	else {
		//tulostetaan tuntiyhteenveto
		$tuntipalkka = 10;
		$minuuttipalkka = $tuntipalkka/60;
		$kokonaiskustannukset = 0;
		echo "<br><table><tr><th>".t("Nimi") ."</th><th>".t("Työn laatu")."</th><th>".t("Työaika")."</th><th>".t("Kustannus")."(EUR)</th></tr>";
		while ($tyoerittelyt = mysql_fetch_array($result)) {
			$tyotunnit = sprintf("%02d",floor($tyoerittelyt["minuuttimaara"]/60));
			$tyominuutit = sprintf("%02d",$tyoerittelyt["minuuttimaara"]%60);
			$nimi = $tyoerittelyt["nimi"];
			$tyonlaatu = $tyoerittelyt["tyyppi"];
			$minuuttisumma = $tyoerittelyt["minuutitsumma"];
			$kustannus = round(($minuuttipalkka * $tyoerittelyt["minuuttimaara"]),2);
			$kokonaiskustannukset = $kokonaiskustannukset + round($kustannus,2);
			echo "<tr align='center'><td align='left'>$nimi</td><td>$tyonlaatu</td><td>$tyotunnit:$tyominuutit</td><td>". sprintf("%.2f",$kustannus) ."</td></tr>";
		}
		$aikayhteensa_tunnit = sprintf("%02d",floor($minuuttisumma/60));
		$aikayhteensa_minuutit = sprintf("%02d", $minuuttisumma%60);
		echo "<tr><td colspan='2' align='right'>".t("Yhteensä")."</td><td align='center'>$aikayhteensa_tunnit:$aikayhteensa_minuutit</td><td align='center'>". sprintf("%.2f",$kokonaiskustannukset) . "</td></tr>";
		echo "</table>";
	}
	//tapetaan koska ajaxilla tehty
	die();
}

if($tee == "tuloslaskelma") {
	$query = "	SELECT tiliointi.*
				FROM laskun_lisatiedot
				JOIN tiliointi ON tiliointi.yhtio=laskun_lisatiedot.yhtio and tiliointi.projekti=laskun_lisatiedot.projekti
				WHERE laskun_lisatiedot.yhtio='$kukarow[yhtio]' and laskun_lisatiedot.otunnus='$projekti' and laskun_lisatiedot.projekti > 0
				orDER BY laadittu";
	$result = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($result) == 0) {
		$kikkare = "<font class='info'>" . t("Ei tiliöintejä") . "</font>";
	}
	else {
		$mul_proj[0] = mysql_result($result,0,"projekti");
		$eka_tiliointi = mysql_result($result,0,"laadittu");
		
		$tuloslaskelma = muistista("tuloslaskelma", "tuloslaskelma");
		
		if(count($tuloslaskelma) == 0 or $tuloslaskelma === false) {
			$tuloslaskelma["tyyppi"]	= 3;
			$tuloslaskelma["rtaso"]		= "5";
		}
		
		if(count($vaihda) > 0) {
			foreach($vaihda as $v => $a) {
				$tuloslaskelma[$v] = $a;
			}
		}
		
		foreach($tuloslaskelma as $v => $a) {
			$$v = $a;
		}
		
		/*
			Pakolliset muuttujat
		*/
		//eka tiliointivuosi
		$plvv = substr($eka_tiliointi,0,4); 
		//eka tiliointikuukausi
		$plvk = substr($eka_tiliointi,6,2);
		//vika tiliöintivuosi ja kuukausi NYT
		$alvv			= date('Y');
		$alvk			= date('m');
		$summaatulos	= "yes";
		$tltee			= "aja";
		$from			= "PROJEKTIKALENTERI";
		$tarkkuus 		= 1;
		$desi 			= 0;
		
		require("raportit/tuloslaskelma.php");
		if(!muistiin("tuloslaskelma", "tuloslaskelma", $tuloslaskelma)) {
			die("OHO! Joku ei nyt vaan onnistu! (dementia iskee, ei tallennu muistiin)");
		}
	}	
	die();
}

if($projekti > 0) {
	//	Tämä on siis kalenteri jota meidän pitäisi käsitellä
	$kaleDIV = "projektikalenteri_$projekti";

	//	Jos kalenteria ei ole vielä määritetty niin se pitää tehdä uudestaan
	if($kaleID != $kaleDIV) {
		$otunnus = $projekti;
		
		$kaleID 							= $kaleDIV;
		$kalenteri["div"] 					= $kaleDIV;
		$kalenteri["URL"] 					= "projektikalenteri.php";
		$kalenteri["url_params"]			= array("projekti", "otunnus");
		$kalenteri["liitostunnus"] 			= $liitostunnus;
		$kalenteri["nakyma"]				= "RIVINAKYMA_VIIKKO";
		$kalenteri["tunnusnippu"]			= $projekti;	
		$kalenteri["sallittu_nakyma"]		= array("KUUKAUSINAKYMA", "VIIKKONAKYMA", "PAIVANAKYMA", "RIVINAKYMA_PAIVA", "RIVINAKYMA_VIIKKO");
		$kalenteri["laskutilat"]			= "'L','R','N'";
		

		$kalenteri["kalenteri_tyypit"]				= array("kalenteri", "Muistutus", "projektitapahtuma");
		$kalenteri["kalenteri_nayta_tyyppi"]		= array("projektitapahtuma");

		$kalenteri["kalenteri_tuntidata"]			= array("projektitunnit");
		$kalenteri["kalenteri_nayta_tuntidata"]		= array("");
		
		$kalenteri["kalenteri_tilausdata"]			= array("tilaus", "toimitus", "valmistus", "tyomaarays");
		$kalenteri["kalenteri_nayta_tilausdata"]	= array("tilaus", "toimitus", "valmistus", "tyomaarays");
		
		$kalenteri["kalenteri_ketka"]		= array("kaikki");
		$kalenteri["kalenteri_nayta_kuka"]	= array("");
		
		$kalenteri["kalenteri_jako"]    	= array("tilaus");
		
		alusta_kalenteri($kalenteri);
		$tee_div = "JOO";
	}
	
		
	//	Liitetään tämän käyttäjän tekemät memot yms aina mukaan.
	$data = kalequery();
	//echo "data:<pre>".print_r($data, true)."</pre>";
	
	$tuloslaskelma = muistista("tuloslaskelma", "tuloslaskelma");
	$selTyyppi = array($tuloslaskelma["tyyppi"] => "SELECTED");
	$selKaikki = array($tuloslaskelma["kaikkikaudet"] => "SELECTED");
	$selTaso = array($tuloslaskelma["rtaso"] => "SELECTED");
	
	$kalePost = "
			<table>
				<tr>
					<td class='back'><a href='#' onclick=\"var a = getElementById('tuloslaskelmaContainer'); if(a.style.display == 'none') { a.style.display =  'block'; } else{ a.style.display = 'none';} return false; \">Näytä/piilota talousdata</a></td>
					<td class='back'><a href='#' onclick=\"var a = getElementById('tuntiyhteenvetoContainer'); if(a.style.display == 'none') { a.style.display =  'block'; } else{ a.style.display = 'none';} return false; \">Näytä/piilota tuntiyhteenveto</a></td>
				</tr>
				<tr>
					<td class='back'>
						<div id='tuloslaskelmaContainer' style='display: none; float: left;'>
							<div id='tuloslaskelma'></div>

								<script type='text/javascript' language='JavaScript'>
									sndReq('tuloslaskelma', 'projektikalenteri.php?tee=tuloslaskelma&projekti=$projekti', false, false); 
								</script>
								<table><tr><td colspan='2' class='back'>&nbsp;</td></tr><tr><th>".t("Tyyppi")."</th><th>". t("Kaudet") . "</th><th>". t("Taso") . "</th></tr>
								<tr>
									<td>			
										<select id='kikkare' onchange=\"sndReq('tuloslaskelma', 'projektikalenteri.php?tee=tuloslaskelma&projekti=$projekti&vaihda[tyyppi]='+this.options[this.selectedIndex].value, false, false); return false;\">
											<option value='1' ".$selTyyppi["1"].">".t("Vastaavaa (varat)")."</option>
											<option value='2' ".$selTyyppi["2"].">".t("Vastattavaa (velat)")."</option>
											<option value='3' ".$selTyyppi["3"].">".t("Ulkoinen tuloslaskelma")."</option>
										</select>
									</td>
									<td>			
										<select id='kikkare' onchange=\"sndReq('tuloslaskelma', 'projektikalenteri.php?tee=tuloslaskelma&projekti=$projekti&vaihda[kaikkikaudet]='+this.options[this.selectedIndex].value, false, false); return false;\">
											<option value='n' ".$selKaikki["n"].">".t("Älä näytä kausia")."</option>
											<option value='o' ".$selKaikki["o"].">".t("Näytä kaikki kaudet")."</option>
										</select>
									</td>
									<td>
										<select id='kikkare' onchange=\"sndReq('tuloslaskelma', 'projektikalenteri.php?tee=tuloslaskelma&projekti=$projekti&vaihda[rtaso]='+this.options[this.selectedIndex].value, false, false); return false;\">
											<option value='TILI' ".$selTaso["TILI"]. ">".t("Tilitaso")."</option>
											<option value='5' ".$selTaso["5"]." >".t("Yhteenveto")."</option>
										</select>
									</td>
								</tr>
								</table>
						</div>
					</td>
					<td class='back'>
						<div id='tuntiyhteenvetoContainer' style='display: none; float: left;'>
							<div id='tuntiyhteenveto'></div>
							<script type='text/javascript' language='JavaScript'>
								sndReq('tuntiyhteenveto', 'projektikalenteri.php?tee=tuntiyhteenveto&projekti=$projekti', false, false); 
							</script>
						</div>
					</td>
				</tr>					
			</table>";
	
	if($tee_div == "JOO") {
		//tuloslaskelma 
			
		echo "<font class='head'>".t("Projektikalenteri")."</font><hr><br><br>
				<div id='$kaleDIV'>".kalenteri($data, $kalePost)."</div>";		
	}
	else {
		echo kalenteri($data, $kalePost);
	}	
}

if($toim == "HAKU") {
	echo "	<form action='$PHP_SELF?toim=$toim' method='post' name='haku'>
			<input type='hidden' name='toim' value='$toim'>
			<table>
				<tr><th>".t("Projekti")."</th><td><input type='text' size='15' name='projekti' value='$projekti'></td><td class='back'>&nbsp;</td></tr>
				<tr><th>".t("Asiakas")."</th><td><input type='text' size='15' name='asiakas' value='$asiakas'></td><td class='back'>&nbsp;</td></tr>				
				<tr><th>".t("Seuranta")."</th><td><input type='text' size='15' name='seuranta' value='$seuranta'></td><td class='back'>&nbsp;</td></tr>
				<tr><th>".t("Kohde")."</th><td><input type='text' size='15' name='kohde' value='$kohde'></td><td class='back' colspan='2' align='right'><input type='submit' value='".t("Hae")."'></td></tr>
			</table>
			</form>
			<br><br>";
			

}
elseif($projekti == 0) {
	echo "<font class='head'>".t("Avoimet projektit")."</font><hr><br><br>";
}

if((int) $projekti == 0 and ($toim != "HAKU" or $projekti != "" or $asiakas != "" or $seuranta != "" or $kohde != "")) {
	
	if ($toim == 'HAKU') {
		//alustetaan nyt varmuuden vuoks...
		$rajaus = "";


		$projekti = mysql_escape_string($projekti);
		$seuranta = mysql_escape_string($seuranta);
		$asiakas = mysql_escape_string($asiakas);
		$kohde = mysql_escape_string($kohde);

		if ($projekti != "") {
			$rajaus .= "and lasku.tunnus='$projekti'";
		}
		if ($seuranta != "") {
			$rajaus .= "and laskun_lisatiedot.seuranta='$seuranta'";
		}
		if ($asiakas != "") {
			$rajaus .= "and (lasku.nimi LIKE '%$asiakas%' or lasku.nimitark LIKE '%$asiakas%')";
		}
		if ($kohde != "") {
			$rajaus .= "and asiakkaan_kohde.kohde LIKE '%$kohde%'";
		}
	}
	else {
		$rajaus = " and lasku.alatila!='X'";
	}
	
	$query = "	SELECT lasku.tunnus, concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, laskun_lisatiedot.seuranta, laskun_lisatiedot.projektipaallikko, asiakkaan_kohde.kohde kohde
				FROM lasku
				LEFT JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
				LEFT JOIN kuka pp ON pp.yhtio=lasku.yhtio and pp.tunnus=laskun_lisatiedot.projektipaallikko
				LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=lasku.yhtio and asiakkaan_kohde.tunnus=laskun_lisatiedot.asiakkaan_kohde 
				WHERE lasku.yhtio = '$kukarow[yhtio]' 
				and lasku.tila = 'R' 
				$rajaus
				orDER BY tunnusnippu DESC";
				
				
	$result = mysql_query($query) or pupe_error($query);
	
	if(mysql_num_rows($result) > 0) {
		echo "	<table>
					<tr>
						<th>".t("Projekti")."</th>
						<th>".t("Seuranta")."</th>
						<th>".t("Asiakas")."</th>
						<th>".t("Kohde")."</th>
						<th>".t("Projektipäällikkö")."</th>
					</tr>";
		while($row = mysql_fetch_array($result)) {
			echo "<tr class='aktiivi'>
						<td>$row[tunnus]</td>
						<td>$row[seuranta]</td>
						<td>$row[asiakas]</td>
						<td>$row[kohde]</td>
						<td>$row[ppaall]</td>
						<td class='back'>
							<form action='$PHP_SELF' method='post'>
								<input type='hidden' name='projekti' value='$row[tunnus]'>
								<input type='submit' value='".t("Avaa kalenteri")."'>
							</form>
						</td>
					</tr>";
		}

		echo "</table>";
	}
	else {
		echo t("Ei avoimia projekteja")."!<br>";
	}
}




$ei_kelloa = 1;

require ("inc/footer.inc");

?>