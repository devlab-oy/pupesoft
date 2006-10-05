<?php

	function budjetti_summa ($taso, $alku, $loppu, $lisa) {
		global $kukarow;
		$omalisa = str_replace(" kustp ", " kustannuspaikka ", $lisa);
		$alku = str_replace("-", "", $alku);
		$loppu = str_replace("-", "", $loppu);
		$query = "SELECT sum(if(kausi='$loppu',summa,0)) ka, sum(summa) va, sum(if(kausi='$loppu',1,0)) kac, count(summa) vac 
					 FROM budjetti
					 WHERE yhtio='$kukarow[yhtio]' and taso = '$taso' and kausi >= '$alku' and kausi <='$loppu' $omalisa";	 
		$aresult = mysql_query($query) or pupe_error($query);

		$ka='n/a';
		$va='n/a';
			
		if (mysql_num_rows($aresult) > 0) {
			$budjettirow=mysql_fetch_array($aresult);
			$ka=$budjettirow['ka'];
			$va=$budjettirow['va'];
		}
		if ($budjettirow['kac'] == 0) $ka = 'n/a';
		if ($budjettirow['vac'] == 0) $va = 'n/a';
		//echo "$taso, $ka, $va, $omalisa<br>";
		return array ($ka, $va);					
	}
	 
	function  rivi ($otsikko, $taso1, $taso2, $taso3, $taso4, $taso5, $taso6) {
		echo "<tr><th>$otsikko</th>";
		if ($taso1==0) $taso1='';
		if ($taso2==0) $taso2='';
		if ($taso3==0) $taso3='';
		if ($taso4==0) $taso4='';
		if ($taso5==0) $taso5='';
		if ($taso6==0) $taso6='';
		echo "<td align = 'right'>$taso1</td>";
		echo "<td align = 'right'>$taso3</td>";
		echo "<th align = 'right'>$taso5</th>";
		echo "<td align = 'right'>$taso2</td>";
		echo "<td align = 'right'>$taso4</td>";
		echo "<th align = 'right'>$taso6</th>";
		echo "</tr>";
	}					
	
    echo "<font class='message'>".t("Tase/tuloslaskelma")."</font><hr>";

    if ($tee == '1') {
    	if ((($plvv==0) and ($plvk!=0)) or (($plvv!=0) and ($plvk==0))) {
    		echo "<font class='error'>".t("Anna sekä kausi että vuosi")."</font><br>";
    		$tee='';
    	} 
    }
    
	if ($tee == '') {
		echo "<form action = 'raportit.php' method='post'>
				<input type = 'hidden' name = 'toim' value = 'tase'>
				<input type = 'hidden' name = 'tee' value = '1'>
				<table><tr>
				<th>".t("Tyyppi")."</th>
				<td><select name = 'tyyppi'>
				<option value='4'>".t("Sisäinen tuloslaskelma")."
				<option value='3'>".t("Ulkoinen tuloslaskelma")."
				<option value='2'>".t("Vastattavaa")."
				<option value='1'>".t("Vastaavaa")."
				</select></td></tr>
				<tr>
				<th>".t("Ajalta")."</th>
				<td><select name='alvv'>";

		for ($i = date("Y"); $i >= date("Y")-4; $i--) {
			if ($i == date("Y")) $sel = "selected";
			else $sel = "";
			echo "<option value='$i' $sel>$i</option>";
		}

		echo "</select>
				<select name='alvk'>
				<option value = '01'>01
				<option value = '02'>02
				<option value = '03'>03
				<option value = '04'>04
				<option value = '05'>05
				<option value = '06'>06
				<option value = '07'>07
				<option value = '08'>08
				<option value = '09'>09
				<option value = '10'>10
				<option value = '11'>11
				<option value = '12'>12
				</select></td></tr>
				<tr>
				<th>".t("Poikkeava alkuaika")."</th>
				<td><select name='plvv'>
				<option value = '0'>".t("Ei valintaa");

		for ($i = date("Y"); $i >= date("Y")-4; $i--) {
			//if ($i == date("Y")) $sel = "selected";
			//else $sel = "";
			echo "<option value='$i' $sel>$i</option>";
		}
		
		echo "</select>
				<select name='plvk'>
				<option value = '0'>".t("Ei valintaa")."
				<option value = '01'>01
				<option value = '02'>02
				<option value = '03'>03
				<option value = '04'>04
				<option value = '05'>05
				<option value = '06'>06
				<option value = '07'>07
				<option value = '08'>08
				<option value = '09'>09
				<option value = '10'>10
				<option value = '11'>11
				<option value = '12'>12
				</select></td></tr>";
		echo "<tr><th>".t("Vain kustannuspaikka")."</th>";
		$query = "SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'K'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<td><select name='kustp'><option value=' '>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
		}
		echo "</select></td>";
		echo "</tr>";
		echo "<tr><th>".t("Vain kohde")."</th>";
		$query = "SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'O'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<td><select name='kohde'><option value=' '>Ei valintaa";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
		}
		echo "</select></td>";
		echo "</tr>";
		echo "<tr><th>".t("Vain projekti")."</th>";
		$query = "SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'P'
					ORDER BY nimi";
		$vresult = mysql_query($query) or pupe_error($query);
		echo "<td><select name='proj'><option value=' '>".t("Ei valintaa")."";
		while ($vrow=mysql_fetch_array($vresult)) {
			$sel="";
			if ($trow[$i] == $vrow['tunnus']) {
				$sel = "selected";
			}
			echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]";
		}
		echo "</select></td></tr>";

		echo "<tr><th>".t("Raportointitaso")."</th>
				<td><select name='rtaso'>
					<option value='5'>".t("Tili")."
					<option value='4'>*
					<option value='3'>**
					<option value='2'>***
					<option value='1'>****
					</select></td></tr>";

		echo "<tr><th>".t("Vertailu")."</th>
				<td><input type='radio' name='vertailu' value='ed' checked> ".t("Edellinen vastaava")."
				<input type='radio' name='vertailu' nimi='budj'> ".t("Budjetti")."</td></tr>";
				
		echo "<tr><th>".t("Jaa luvut 1000:lla")."</th>
				<td><input type='checkbox' name='tarkkuus' checked> ";
		echo t("Desimaaleja")."
				<select name='desi'>
					<option value='2'>2
					<option value='1'>1
					<option value='0' selected>0
					</select></td></tr>";
		echo "<tr><th></th>
		      <td><input type = 'submit' value = '".t("Näytä")."'></form></td><tr></table>";
		exit;
	}

	$timeparts = explode(" ",microtime());
	$phptime = $timeparts[1].substr($timeparts[0],1);

// Luetaan tilikartta tarvittavassa järjestyksessä
	$tyyppi = (int) $tyyppi;
	$taso = "ulkoinen_taso";
	$tasotyyppi = "U";
	if ($tyyppi == 4) { // Sisäinen tuloslaskelma!!!
		$taso = "sisainen_taso";
		$tasotyyppi = "S";
		$tyyppi = 3;
	}
	$lisa = "SUBSTRING(". $taso . ",1,1) = '$tyyppi'";

	$query = "SELECT $taso, tilino
				FROM tili
				WHERE yhtio='$kukarow[yhtio]' and $lisa
				ORDER BY $taso, tilino";

	$result = mysql_query($query) or pupe_error($query);

	$timeparts = explode(" ",microtime());
	$valitime = $timeparts[1].substr($timeparts[0],1);
	$aika1= round($valitime-$phptime,4);

//	echo "$query<br>" . mysql_num_rows($result);

	$plvv = (int) $plvv;
	$plvk = (int) $plvk;
	if ($plvv==0) {
// Tehdään yhteensopiva kausi
// Pohditaan nyt hetki tilikautta, koska sen nyt osaamme
		$alvv = (int) $alvv;
		$alvk = (int) $alvk;
		$query = "SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]' and '$alvv-$alvk-01' >= tilikausi_alku and '$alvv-$alvk-01' <= tilikausi_loppu";
		$tkresult = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($tkresult) != 1) {
			echo "<font class='error'>".t("Sopivaa yrityksen tilikautta ei löytynyt")."</font>";
			exit;
		}
		$tilikaudetrow=mysql_fetch_array($tkresult);

		$kausi 		= $alvv   ."-" . sprintf("%02d",$alvk);
		$edkausi	= $alvv-1 ."-" . sprintf("%02d",$alvk);
		$alku		= substr($tilikaudetrow['tilikausi_alku'], 0, 7);
		$loppu		= $kausi;
		$edalku		= substr($tilikaudetrow['tilikausi_alku'], 0, 4) - 1 . substr($tilikaudetrow['tilikausi_alku'], 4, 3);
		$edloppu	= substr($kausi, 0, 4) - 1 . substr($kausi, 4, 3);
	}
	else {
// Poikkeava seurantatajakso
		$kausi 		= $alvv   ."-" . sprintf("%02d",$alvk);
		$edkausi	= $alvv-1 ."-" . sprintf("%02d",$alvk);
		$alku		= $plvv   ."-" . sprintf("%02d",$plvk);
		$loppu		= $kausi;
		$edalku		= $plvv-1 ."-" . sprintf("%02d",$plvk);
		$edloppu	= substr($kausi, 0, 4) - 1 . substr($kausi, 4, 3);
	}
	
	echo "<font class='message'>";
	echo t("Kausi").": $kausi ".t("Vuosi").": $alku - $loppu<br>";
	if ($vertailu == 'ed') echo t("Vertailukausi").": $edkausi ".t("Vertailuvuosi").": $edalku -  $edloppu<br>";
	echo "</font>";

// Hoidetaan tarkkuus
	if ($tarkkuus != '')
		$tarkkuus=1000;
	else 
		$tarkkuus=1;
	
// Desimaalit
	$muoto = "%.". (int) $desi . "f";


// Onko meillä lisärajoitteita??

	$lisa='';
	if (strlen(trim($kustp)) > 0) {
		$lisa .= " and kustp = '" . $kustp . "'";
	}
	if (strlen(trim($proj)) > 0) {
		$lisa .= " and projekti = '" . $proj . "'";
	}
	if (strlen(trim($kohde)) > 0) {
		$lisa .= " and kohde = '" . $kohde . "'";
	}

// Nyt tileille saldot

	echo "<table>";
	echo "<th></th>";
	
	echo "<th>".t("ka")."</th>";
	if ($vertailu=='ed') 
		echo "<th>".t("edka")."</th>";
	else 
		echo "<th>".t("budj ka")."</th>";

	echo "<th>".t("ka ind")."</th>";
	echo "<th>".t("va")."</th>";
	if ($vertailu=='ed') 
		echo "<th>".t("edva")."</th>";
	else 
		echo "<th>".t("budj va")."</th>";
	echo "<th>".t("va ind")."</th>";
	echo "</tr>";

	$tamataso = "99999";
	$taso1 = "Z";
	$taso2 = "Z";
	$taso3 = "Z";
	$taso4 = "Z";
	$taso41 = 0;
	$taso42 = 0;

	while ($tilirow=mysql_fetch_array ($result)) {

// Ensin lasketaan kaikki kaudet ja sitten yhdistellään ne
// Huomaa tuo tapvm<'$yhtiorow[1]' and tapvm>='$yhtiorow[0]' eli otetaan mukaan ensimmäinen päivä, mutta
// hylätään ylärajalta yksi päivä koska se on aina muotoa vvvv-kk-1 eli eka jota EI oteta mukaan

//	$query =	"SELECT DATE_FORMAT(tapvm, '%Y-%m') tapvm, tiliointi.tilino, nimi, sum(tiliointi.summa) summa, $taso
//					 FROM tiliointi, tili
//					 WHERE tiliointi.tilino = tili.tilino and tiliointi.yhtio=tili.yhtio and
//				       tiliointi.tilino = '$tilirow[tilino]'
//				       and DATE_FORMAT(tapvm, '%Y-%m') <= '$loppu'
//				       and DATE_FORMAT(tapvm, '%Y-%m') >='$edalku'
//				       and tiliointi.yhtio='$kukarow[yhtio]'
//				       and tiliointi.korjattu=''
//				       $lisa
//					 GROUP BY DATE_FORMAT(tapvm, '%Y-%m')";


		if ($vertailu == 'ed') {
			$query = "SELECT DATE_FORMAT(tapvm, '%Y-%m') tapvm, tiliointi.tilino, nimi, sum(tiliointi.summa) summa, $taso
					 FROM tili
					 left join tiliointi on tiliointi.tilino = tili.tilino and tiliointi.yhtio=tili.yhtio
				       and DATE_FORMAT(tapvm, '%Y-%m') <= '$loppu'
				       and DATE_FORMAT(tapvm, '%Y-%m') >='$edalku'
				       and tiliointi.korjattu=''
				       $lisa
					 WHERE tili.yhtio='$kukarow[yhtio]' and tili.tilino = '$tilirow[tilino]'
					 GROUP BY DATE_FORMAT(tapvm, '%Y-%m')";
			$aresult = mysql_query($query) or pupe_error($query);
		}
		else { 
			$query = "SELECT DATE_FORMAT(tapvm, '%Y-%m') tapvm, tiliointi.tilino, nimi, sum(tiliointi.summa) summa, $taso
					 FROM tili
					 left join tiliointi on tiliointi.tilino = tili.tilino and tiliointi.yhtio=tili.yhtio
				       and DATE_FORMAT(tapvm, '%Y-%m') <= '$loppu'
				       and DATE_FORMAT(tapvm, '%Y-%m') >='$alku'
				       and tiliointi.korjattu=''
				       $lisa
					 WHERE tili.yhtio='$kukarow[yhtio]' and tili.tilino = '$tilirow[tilino]'
					 GROUP BY DATE_FORMAT(tapvm, '%Y-%m')";
			$aresult = mysql_query($query) or pupe_error($query);
			list($edka, $edva) = budjetti_summa($tilirow[$taso], $alku, $loppu, $lisa);
		}

		while ($tiliointirow=mysql_fetch_array ($aresult)) {
			$otsik = "<a href ='raportit.php?toim=paakirja&tee=P&alvv=$alvv&alvk=$alvk&kohde=$kohde&proj=$proj&kustp=$kustp&tili=$tiliointirow[tilino]'>";
			$otsik .= $tiliointirow['tilino'] . "</a> " . $tiliointirow['nimi'];
			if ($tiliointirow['tapvm'] == $kausi) {
				$ka=$tiliointirow['summa'];
			}
			if (($tiliointirow['tapvm'] >= $alku) and ($tiliointirow['tapvm'] <= $loppu)) {
				$va += $tiliointirow['summa'];
			}
			if ($vertailu=='ed') {
				if ($tiliointirow['tapvm'] == $edkausi) {
					$edka=$tiliointirow['summa'];
				}
				if (($tiliointirow['tapvm'] >= $edalku) and ($tiliointirow['tapvm'] <= $edloppu)) {
					$edva += $tiliointirow['summa'];
				}
			}
		}


		
		if (($ka != 0) or ($va != 0) or ($edka != 0) or ($edva != 0)) {
// Tässä hoidetaan tasot

			if (((trim(substr($tilirow[0], 4, 1)) != $taso4) || (trim(substr($tilirow[0], 3, 1)) != $taso3))
				 && ($taso4 != ''))  {
				if (($taso41 != 0) || ($taso42 != 0) || ($taso43 != 0) || ($taso44 != 0)) {
					if ($rtaso > 3) {
						if ($vertailu != 'ed') {
							list($budjka, $budjva) = budjetti_summa(substr($tasox,0,5), $alku, $loppu, $lisa);
							$taso43=$budjka;
							$taso44=$budjva;
						}
						if ($taso43==0) $edkaind=0;
						else $edkaind=round($taso41/$taso43*100,0);

						if ($taso44==0) $edvaind=0;
						else $edvaind=round($taso42/$taso44*100,0);
						
						rivi ($tnimi[4]." ".t("yhteensä")." *",
						sprintf($muoto,$taso41/$tarkkuus),
						sprintf($muoto,$taso42/$tarkkuus),
						sprintf($muoto,$taso43/$tarkkuus),
						sprintf($muoto,$taso44/$tarkkuus),
						$edkaind,
						$edvaind);		
					}
				}
				$taso4 = trim(substr($tilirow[0], 4, 1));
				if ($tlaji[4] == 'O') {
					$taso41=0;
					$taso42=0;
					$taso43=0;
					$taso44=0;
				}
			}
			if (((trim(substr($tilirow[0], 3, 1)) != $taso3) || (trim(substr($tilirow[0], 2, 1)) != $taso2))
				 && ($taso3 != ''))  {
				if (($taso31 != 0) || ($taso32 != 0) || ($taso33 != 0) || ($taso34 != 0)) {
					if ($rtaso > 2) {
						if ($vertailu != 'ed') {
							list($budjka, $budjva) = budjetti_summa(substr($tasox,0,4), $alku, $loppu, $lisa);
							$taso33=$budjka;
							$taso34=$budjva;
						}
						if ($taso33==0) $edkaind=0;
						else $edkaind=round($taso31/$taso33*100,0);

						if ($taso34==0) $edvaind=0;
						else $edvaind=round($taso32/$taso34*100,0);
												
						rivi ($tnimi[3]." ".t("yhteensä")." **",
						sprintf($muoto,$taso31/$tarkkuus),
						sprintf($muoto,$taso32/$tarkkuus),
						sprintf($muoto,$taso33/$tarkkuus),
						sprintf($muoto,$taso34/$tarkkuus),
						$edkaind,
						$edvaind);

						echo "</tr>";
					}
				}
				$taso3 = trim(substr($tilirow[0], 3, 1));
				$taso4 = trim(substr($tilirow[0], 4, 1));
				if ($tlaji[3] == 'O') {
					$taso31=0;
					$taso32=0;
					$taso33=0;
					$taso34=0;
					$taso41=0;
					$taso42=0;
					$taso43=0;
					$taso44=0;
				}
			}
			if (((trim(substr($tilirow[0], 2, 1)) != $taso2) || (trim(substr($tilirow[0], 1, 1)) != $taso1))
					&& ($taso2 != '')) {
				if (($taso21 != 0) || ($taso22 != 0) || ($taso23 != 0) || ($taso24 != 0)) {
					if ($rtaso > 1) {
						if ($vertailu != 'ed') {
							list($budjka, $budjva) = budjetti_summa(substr($tasox,0,3), $alku, $loppu, $lisa);
							$taso23=$budjka;
							$taso24=$budjva;
						}
						if ($taso23==0) $edkaind=0;
						else $edkaind=round($taso21/$taso23*100,0);

						if ($taso24==0) $edvaind=0;
						else $edvaind=round($taso22/$taso24*100,0);

						rivi ($tnimi[2]." ".t("yhteensä")." ***",
						sprintf($muoto,$taso21/$tarkkuus),
						sprintf($muoto,$taso22/$tarkkuus),
						sprintf($muoto,$taso23/$tarkkuus),
						sprintf($muoto,$taso24/$tarkkuus),
						$edkaind,
						$edvaind);
					}
				}
				$taso2 = trim(substr($tilirow[0], 2, 1));
				$taso3 = trim(substr($tilirow[0], 3, 1));
				$taso4 = trim(substr($tilirow[0], 4, 1));
				if ($tlaji[2] == 'O') {
					$taso21=0;
					$taso22=0;
					$taso23=0;
					$taso24=0;
					$taso31=0;
					$taso32=0;
					$taso33=0;
					$taso34=0;
					$taso41=0;
					$taso42=0;
					$taso43=0;
					$taso44=0;
				}
			}
			if ((trim(substr($tilirow[0], 1, 1)) != $taso1) && ($taso1 != '')) {
				if (($taso11 != 0) || ($taso12 != 0) || ($taso13 != 0) || ($taso14 != 0)) {
					if ($vertailu != 'ed') {
						list($budjka, $budjva) = budjetti_summa(substr($tasox,0,2), $alku, $loppu, $lisa);
						$taso13=$budjka;
						$taso14=$budjva;
					}
					if ($taso13==0) $edkaind=0;
					else $edkaind=round($taso11/$taso13*100,0);

					if ($taso14==0) $edvaind=0;
					else $edvaind=round($taso12/$taso14*100,0);
					
					rivi ($tnimi[1]." ".t("yhteensä")." ****",
						sprintf($muoto,$taso11/$tarkkuus),
						sprintf($muoto,$taso12/$tarkkuus),
						sprintf($muoto,$taso13/$tarkkuus),
						sprintf($muoto,$taso14/$tarkkuus),
						$edkaind,
						$edvaind);
					echo "<tr><th colspan='7'><br></th></tr>";
				}
				$taso1 = trim(substr($tilirow[0], 1, 1));
				$taso2 = trim(substr($tilirow[0], 2, 1));
				$taso3 = trim(substr($tilirow[0], 3, 1));
				$taso4 = trim(substr($tilirow[0], 4, 1));
				
				if ($tlaji[1] == 'O') {
					$taso11=0;
					$taso12=0;
					$taso13=0;
					$taso14=0;
					$taso21=0;
					$taso22=0;
					$taso23=0;
					$taso24=0;
					$taso31=0;
					$taso32=0;
					$taso33=0;
					$taso34=0;
					$taso41=0;
					$taso42=0;
					$taso43=0;
					$taso44=0;
				}
			}
// Vähän otsikoita peliin...
			if ($tilirow[0] != $tamataso) {
// Minkä tason otsikot tarvitaan?
				$loput = 0;
				for ($i=0; $i < 6; $i++) {
					if (((substr($tilirow[0],$i,1) != substr($tamataso,$i,1)) or ($loput == 1)) and ((trim(substr($tilirow[0],$i,1))) != '')) {
						$loput = 1; // Jos tämä taso tarvittiin tarvitaan kaikki muutkin
						$hakutaso = substr($tilirow[0],0,$i+1);
						$query = "SELECT *
									FROM taso
									WHERE yhtio='$kukarow[yhtio]' and taso = '$hakutaso' and tyyppi = '$tasotyyppi'";
						$xresult = mysql_query($query) or pupe_error($query);
						$tasorow[0] = "Taso '$hakutaso'";
						if (mysql_num_rows($xresult) != 0) {
							$tasorow=mysql_fetch_array($xresult);
						}
						if ($rtaso == 5) { // Otsikot vain tilitason raporttiin
							echo "<tr><th colspan='7'>$tasorow[nimi]</font></th>";
							//echo "<th></th>";
							//echo "<th></th>";
							//echo "<th></th>";
							//echo "<th></th>";
							//echo "<th></th>";
							//echo "<th></th>";
							echo "</tr>";
						}
						$tnimi[$i] = $tasorow['nimi'];
						$tlaji[$i] = $tasorow['laji'];
					}
				}
				$tamataso = $tilirow[0];
			}
			if ($rtaso == 5) { // Tilitaso

				if ($vertailu == 'ed') {
					if ($edka==0) $edkaind=0;
					else $edkaind=round($ka/$edka*100,0);

					if ($edva==0) $edvaind=0;
					else $edvaind=round($va/$edva*100,0);

					rivi ($otsik,
						sprintf($muoto,$ka/$tarkkuus),
						sprintf($muoto,$va/$tarkkuus),
						sprintf($muoto,$edka/$tarkkuus),
						sprintf($muoto,$edva/$tarkkuus),
						$edkaind,
						$edvaind);
				}
				else rivi ($otsik,
					sprintf($muoto,$ka/$tarkkuus),
					sprintf($muoto,$va/$tarkkuus),
					'',
					'',
					'',
					'');
			}
			
			$taso01 += $ka;
			$taso02 += $va;
			$taso03 += $edka;
			$taso04 += $edva;
			$taso11 += $ka;
			$taso12 += $va;
			$taso13 += $edka;
			$taso14 += $edva;
			$taso21 += $ka;
			$taso22 += $va;
			$taso23 += $edka;
			$taso24 += $edva;
			$taso31 += $ka;
			$taso32 += $va;
			$taso33 += $edka;
			$taso34 += $edva;
			$taso41 += $ka;
			$taso42 += $va;
			$taso43 += $edka;
			$taso44 += $edva;
			$tasox = $tilirow[0];
			$ka=0;
			$va=0;
			$edka=0;
			$edva=0;
		}
	}
	
	if ((($taso41 != 0) || ($taso42 != 0) || ($taso43 != 0) || ($taso44 != 0)) && ($taso4 != ''))  {
		if ($rtaso > 3) {
			if ($vertailu != 'ed') {
				list($budjka, $budjva) = budjetti_summa(substr($tasox,0,5), $alku, $loppu, $lisa);
				$taso43=$budjka;
				$taso44=$budjva;
			}
			if ($taso43==0) $edkaind=0;
			else $edkaind=round($taso41/$taso43*100,0);

			if ($taso44==0) $edvaind=0;
			else $edvaind=round($taso42/$taso44*100,0);

			rivi ($tnimi[4]." ".t("yhteensä")." *",
				sprintf($muoto,$taso41/$tarkkuus),
				sprintf($muoto,$taso42/$tarkkuus),
				sprintf($muoto,$taso43/$tarkkuus),
				sprintf($muoto,$taso44/$tarkkuus),
				$edkaind,
				$edvaind);
		}
	}
	if ((($taso31 != 0) || ($taso32 != 0) || ($taso33 != 0) || ($taso34 != 0)) && ($taso3 != ''))  {
		if ($rtaso > 2) {
			if ($vertailu != 'ed') {
				list($budjka, $budjva) = budjetti_summa(substr($tasox,0,4), $alku, $loppu, $lisa);
				$taso33=$budjka;
				$taso34=$budjva;
			}
			if ($taso33==0) $edkaind=0;
			else $edkaind=round($taso31/$taso33*100,0);

			if ($taso34==0) $edvaind=0;
			else $edvaind=round($taso32/$taso34*100,0);
			
			rivi ($tnimi[3]." ".t("yhteensä")." **",
				sprintf($muoto,$taso31/$tarkkuus),
				sprintf($muoto,$taso32/$tarkkuus),
				sprintf($muoto,$taso33/$tarkkuus),
				sprintf($muoto,$taso34/$tarkkuus),
				$edkaind,
				$edvaind);
		}
	}
	if (($taso21 != 0) || ($taso22 != 0) || ($taso23 != 0) || ($taso24 != 0)) {
		if ($rtaso > 1) {
			if ($vertailu != 'ed') {
				list($budjka, $budjva) = budjetti_summa(substr($tasox,0,3), $alku, $loppu, $lisa);
				$taso23=$budjka;
				$taso24=$budjva;
			}
			if ($taso23==0) $edkaind=0;
			else $edkaind=round($taso21/$taso23*100,0);

			if ($taso24==0) $edvaind=0;
			else $edvaind=round($taso22/$taso24*100,0);
			
			rivi ($tnimi[2]." ".t("yhteensä")." ***",
				sprintf($muoto,$taso21/$tarkkuus),
				sprintf($muoto,$taso22/$tarkkuus),
				sprintf($muoto,$taso23/$tarkkuus),
				sprintf($muoto,$taso24/$tarkkuus),
				$edkaind,
				$edvaind);
		}
	}
	if (($taso11 != 0) || ($taso12 != 0) || ($taso13 != 0) || ($taso14 != 0)) {
		if ($vertailu != 'ed') {
			list($budjka, $budjva) = budjetti_summa(substr($tasox,0,2), $alku, $loppu, $lisa);
			$taso13=$budjka;
			$taso14=$budjva;
		}
		if ($taso13==0) $edkaind=0;
		else $edkaind=round($taso11/$taso13*100,0);

		if ($taso14==0) $edvaind=0;
		else $edvaind=round($taso12/$taso14*100,0);

		rivi ($tnimi[1]." ".t("yhteensä")." ****",
			sprintf($muoto,$taso11/$tarkkuus),
			sprintf($muoto,$taso12/$tarkkuus),
			sprintf($muoto,$taso13/$tarkkuus),
			sprintf($muoto,$taso14/$tarkkuus),
			$edkaind,
			$edvaind);
	}

	if ($vertailu != 'ed') {
		list($budjka, $budjva) = budjetti_summa(substr($tasox,0,1), $alku, $loppu, $lisa);
		$taso03=$budjka;
		$taso04=$budjva;
	}
	if ($taso03==0) $edkaind=0;
	else $edkaind=round($taso01/$taso03*100,0);

	if ($taso04==0) $edvaind=0;
	else $edvaind=round($taso02/$taso04*100,0);
	
	rivi (t("yhteensä")." ****",
		sprintf($muoto,$taso01/$tarkkuus),
		sprintf($muoto,$taso02/$tarkkuus),
		sprintf($muoto,$taso03/$tarkkuus),
		sprintf($muoto,$taso04/$tarkkuus),
		$edkaind,
		$edvaind);

	echo "</table></font></body></html>";

	$timeparts = explode(" ",microtime());
	$endtime = $timeparts[1].substr($timeparts[0],1);
	$aika2= round($endtime-$phptime,4);

	print "<br>".t("Väliaika")." $aika1, ".t("kokoaika")." $aika2.";
?>
