<?php

	if(isset($_POST["tee"])) $tee = $_POST["tee"];
	else $tee = "";	
	if(isset($_POST["kaunisnimi"])) $kaunisnimi = $_POST["kaunisnimi"];
	else $kaunisnimi = "";
	if(isset($_POST["valkoodi"])) $kaunisnimi = $_POST["valkoodi"];
	else $valkoodi = "";


	if($tee == 'lataa_tiedosto') $lataa_tiedosto = 1;

	require('inc/parametrit.inc');

	if ($tee == "lataa_tiedosto") {
		readfile("dataout/".$filenimi);
		exit;
	}
	else {
		echo "<font class='head'>".t("Nordea Factoring siirtotiedosto").":</font><hr><br>";
	}

	if ($tee == 'TARKISTA') {
		if (strtoupper($valkoodi) != strtoupper($ed_valkoodi)) {
			$tee = "";
		}
		else {
			$tee = "TULOSTA";
		}
	}

	if ($tee == '') {
		//Käyttöliittymä
		echo "<br>";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<table>";
		echo "<input type='hidden' name='tee' value='TARKISTA'>";

		if ($valkoodi == '') {
			$valkoodi = $yhtiorow["valkoodi"];
		}

		echo "<input type='hidden' name='ed_valkoodi' value='$valkoodi'>";

		$query = "	SELECT *
	                FROM factoring
	             	WHERE yhtio = '$kukarow[yhtio]'
					and factoringyhtio = 'NORDEA'
					and valkoodi = '$valkoodi'";
		$fres = mysql_query($query) or pupe_error($query);
		$frow = mysql_fetch_array($fres);

		$query = "	SELECT min(laskunro) eka, max(laskunro) vika
					FROM lasku
					JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.factoring='NORDEA'
					WHERE lasku.yhtio	= '$kukarow[yhtio]'
					and lasku.tila	  	= 'U'
					and lasku.alatila	= 'X'
					and lasku.summa		!= 0
					and lasku.factoringsiirtonumero = 0
					and lasku.valkoodi	= '$valkoodi'";
		$aresult = mysql_query ($query) or pupe_error($query);
		$arow = mysql_fetch_array($aresult);

		$query = "	SELECT nimi, tunnus
	                FROM valuu
	             	WHERE yhtio = '$kukarow[yhtio]'
	               	ORDER BY jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<tr><th>Sopimusnumero:</th><td>$frow[sopimusnumero]</td>";
		echo "<tr><th>Valitse valuutta:</th><td><select name='valkoodi' onchange='submit();'>";

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel="";
			if ($vrow['nimi'] == $valkoodi) {
					$sel = "selected";
			}
			echo "<option value = '$vrow[nimi]' $sel>$vrow[nimi]</option>";
		}

		echo "</select></td></tr>";

		echo "<tr>
				<th>Syötä laskuvälin alku:</th>
				<td><input type='text' name='ppa' value='$arow[eka]' size='10'></td>
				</tr>
				<tr>
				<th>Syötä laskuvälin loppu:</th>
				<td><input type='text' name='ppl' value='$arow[vika]' size='10'></td>
				</tr>";

		$query = "	SELECT max(factoringsiirtonumero)+1 seuraava
					FROM lasku
					WHERE  yhtio		= '$kukarow[yhtio]'
					and lasku.tila	  	= 'U'
					and lasku.alatila	= 'X'
					and lasku.summa		!= 0
					and lasku.factoringsiirtonumero > 0";
		$aresult = mysql_query ($query) or pupe_error($query);
		$arow = mysql_fetch_array($aresult);

		echo "<tr><th>Siirtoluettelon numero:</th>
				<td><input type='text' name='factoringsiirtonumero' value='$arow[seuraava]' size='6'></td>";


		echo "<td class='back'><input type='submit' value='Luo siirtoaineisto'></td></tr></table><br><br>";
	}

	if ($tee == 'TULOSTA') {

		$luontipvm	= date("ymd");
		$luontiaika	= date("Hi");

		$query = "	SELECT *
					FROM factoring
					WHERE yhtio = '$kukarow[yhtio]'
					and factoringyhtio = 'NORDEA'
					and valkoodi = '$valkoodi'";
		$fres = mysql_query($query) or pupe_error($query);
		$frow = mysql_fetch_array($fres);


		//Luodaan erätietue
		$ulos  = sprintf ('%-4.4s', 	"KRFL");									//sovellustunnus
		$ulos .= sprintf ('%01.1s',	 	"0");										//tietuetunnus
		$ulos .= sprintf ('%-17.17s', 	str_replace('-','',$yhtiorow["ytunnus"]));	//myyjän ytunnus
		$ulos .= sprintf ('%06.6s',	 	$luontipvm);								//aineiston luontipvm
		$ulos .= sprintf ('%04.4s',   	$luontiaika);								//luontikaika
		$ulos .= sprintf ('%06.6s',	 	$frow["sopimusnumero"]);					//sopimusnumero
		$ulos .= sprintf ('%-3.3s', 	$valkoodi);									//valuutta
		$ulos .= sprintf ('%-2.2s', 	"MR");										//rahoitusyhtiön tunnus
		$ulos .= sprintf ('%-30.30s', 	$kukarow["nimi"]);							//siirtäjän nimi
		$ulos .= sprintf ('%06.6s', 	$factoringsiirtonumero);					//siirtoluettelon numero
		$ulos .= sprintf ('%-37.37s', 	"");										//
		$ulos .= sprintf ('%-63.63s', 	"");										//
		$ulos .= sprintf ('%-221.221s', "");										//
		$ulos .= "\r\n";


		if ($ppl == '') {
			$ppl = $ppa;
		}

		if ($ppa == '' or $ppl == '' or $ppl < $ppa) {
			echo "Huono laskunumeroväli!";
			exit;
		}

		$dquery = "	SELECT lasku.yhtio
					FROM lasku
					JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.factoring='NORDEA'
					WHERE lasku.yhtio	  = '$kukarow[yhtio]'
					and lasku.tila	  = 'U'
					and lasku.alatila	  = 'X'
					and lasku.summa 	 != 0
					and lasku.laskunro >= '$ppa'
					and lasku.laskunro <= '$ppl'
					and lasku.factoringsiirtonumero = 0
					and lasku.valkoodi	= '$valkoodi'";
		$dresult = mysql_query ($dquery) or pupe_error($dquery);

		if (mysql_num_rows($dresult) == 0) {
			echo "Huono laskunumeroväli! Yhtään Nordeaan siirettävää laskua ei löytynyt!";
			exit;
		}

		$query = "	SELECT if(lasku.summa >= 0, '01', '02') tyyppi,
					lasku.ytunnus,
					lasku.nimi,
					lasku.nimitark,
					lasku.osoite,
					lasku.postino,
					lasku.postitp,
					lasku.maakoodi,
					lasku.laskunro,
					round(lasku.viikorkopros*100,0) viikorkopros,
					round(abs(lasku.summa*100),0) summa,
					round(abs(lasku.kasumma*100),0) kasumma,
					lasku.toim_nimi,
					lasku.toim_nimitark,
					lasku.toim_osoite,
					lasku.toim_postino,
					lasku.toim_postitp,
					lasku.toim_maa,
					lasku.maa,
					lasku.viite,
					DATE_FORMAT(lasku.tapvm, '%y%m%d') tapvm,
					DATE_FORMAT(lasku.erpcm, '%y%m%d') erpcm,
					DATE_FORMAT(lasku.kapvm, '%y%m%d') kapvm,
					lasku.tunnus,
					lasku.valkoodi,
					lasku.vienti_kurssi,
					lasku.liitostunnus
					FROM lasku
					JOIN maksuehto ON lasku.yhtio=maksuehto.yhtio and lasku.maksuehto=maksuehto.tunnus and maksuehto.factoring='NORDEA'
					WHERE lasku.yhtio	= '$kukarow[yhtio]'
					and lasku.tila	  	= 'U'
					and lasku.alatila	= 'X'
					and lasku.summa		!= 0
					and lasku.laskunro >= '$ppa'
					and lasku.laskunro <= '$ppl'
					and lasku.factoringsiirtonumero = 0
					and lasku.valkoodi	= '$valkoodi'";
		$laskures = mysql_query ($query) or pupe_error($query);

		if (mysql_num_rows($laskures) > 0) {

			$laskukpl  = 0;
			$vlaskukpl = 0;
			$vlaskusum = 0;
			$hlaskukpl = 0;
			$hlaskusum = 0;

			$laskuvirh = 0;

			echo "<table>";
			echo "<tr><th>Tyyppi</th><th>Laskunumero</th><th>Nimi</th><th>Summa</th><th>Valuutta</th></tr>";

			while ($laskurow = mysql_fetch_array($laskures)) {
				
				// Haetaan asiakkaan tiedot
				$query  = "	SELECT *
							FROM asiakas
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus  = '$laskurow[liitostunnus]'";
				$asires = mysql_query($query) or pupe_error($query);
				$asirow = mysql_fetch_array($asires);


				//Valuuttalaskuissa laskun loppusummma lasketaan tilausriveiltä
				if ($laskurow["vienti_kurssi"] != 0 and $laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {					
					$aquery = "	SELECT
								sum(round(if(alv<500, (1+(alv/100)), (1+((alv-500)/100))) * tilausrivi.rivihinta / $laskurow[vienti_kurssi], 2)) summa
								FROM tilausrivi
								WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]' 
								and tilausrivi.yhtio = '$kukarow[yhtio]'";
					$ares = mysql_query($aquery) or pupe_error($aquery);
					$arow = mysql_fetch_array($ares);
				
					if ($yhtiorow["laskunsummapyoristys"] == 'o' or $asirow["laskunsummapyoristys"] == 'o') {
				        $arow["summa"] = round($arow["summa"],0);
					}
					
					$laskurow["summa"] 		= round($arow["summa"] * 100, 0);
					$laskurow["kasumma"]	= round($laskurow["kasumma"] / $laskurow["vienti_kurssi"] * 100, 0);
				}

				if ($asirow["asiakasnro"] == 0 or !is_numeric($asirow["asiakasnro"]) or strlen($asirow["asiakasnro"]) > 6) {
					$laskuvirh++;
				}

				//luodaan ostajatietue
				$ulos .= sprintf ('%-4.4s', 	"KRFL");										//sovellustunnus
				$ulos .= sprintf ('%01.1s',	 	"1");											//tietuetunnus
				$ulos .= sprintf ('%06.6s',	 	$frow["sopimusnumero"]);						//sopimusnumero
				$ulos .= sprintf ('%06.6s',	 	$asirow["asiakasnro"]);							//ostajan numero aka asiakasnumero
				$ulos .= sprintf ('%-4.4s', 	"");
				$ulos .= sprintf ('%-10.10s', 	str_replace('-','',$laskurow["ytunnus"]));		//ostajan ytunnus
				$ulos .= sprintf ('%-30.30s', 	$laskurow["nimi"]);								//ostajan nimi
				$ulos .= sprintf ('%-30.30s',  	$laskurow["nimitark"]);							//ostajan nimitark
				$ulos .= sprintf ('%-20.20s', 	$laskurow["osoite"]);							//ostajan osoite
				$ulos .= sprintf ('%-20.20s', 	$laskurow["postino"]." ".$laskurow["postitp"]);	//ostajan postino ja postitp
				$ulos .= sprintf ('%-13.13s', 	"");
				$ulos .= sprintf ('%-30.30s', 	"");
				$ulos .= sprintf ('%-13.13s', 	"");
				$ulos .= sprintf ('%-13.13s', 	"");
				$ulos .= sprintf ('%-2.2s', 	"FI");											//kieli
				$ulos .= sprintf ('%-3.3s', 	$laskurow["valkoodi"]);							//valuutta
				$ulos .= sprintf ('%04.4s', 	$laskurow["viikorkopros"]);						//viivastyskorko
				$ulos .= sprintf ('%03.3s', 	"0");
				$ulos .= sprintf ('%06.6s',   	"0");

				if ($laskurow["maa"] != $yhtiorow["maakoodi"] and $laskurow["maa"] != '') {
					$ulos .= sprintf ('%-10.10s', $laskurow["maa"]);
				}
				else {
					$ulos .= sprintf ('%-10.10s', 	"");
				}

				$ulos .= sprintf ('%-172.172s',"");
				$ulos .= "\r\n";

				//luodaan laskutietue
				$ulos .= sprintf ('%-4.4s', 	"KRFL");													//sovellustunnus
				$ulos .= sprintf ('%01.1s',	 	"3");														//tietuetunnus
				$ulos .= sprintf ('%06.6s',	 	$frow["sopimusnumero"]);									//sopimusnumero
				$ulos .= sprintf ('%06.6s',	 	$asirow["asiakasnro"]);										//ostajan numero aka asiakasnumero
				$ulos .= sprintf ('%-4.4s',   	"");														//varalla
				$ulos .= sprintf ('%010.10s',	$laskurow["laskunro"]);										//laskunro
				$ulos .= sprintf ('%06.6s',	 	$laskurow["tapvm"]);										//laskun päiväys
				$ulos .= sprintf ('%-3.3s', 	$laskurow["valkoodi"]);										//valuutta
				$ulos .= sprintf ('%06.6s', 	$laskurow["tapvm"]);										//laskun arvopäivä
				$ulos .= sprintf ('%02.2s', 	$laskurow["tyyppi"]);										//laskun tyyppi 01-veloitus 02-hyvitys 03-viivästyskorkolasku jne...
				$ulos .= sprintf ('%012.12s', 	$laskurow["summa"]);										//summa etumerkitön, sentteinä
				$ulos .= sprintf ('%06.6s', 	$laskurow["erpcm"]);										//eräpäivä

				if($laskurow["kasumma"] > 0) {
					$ulos .= sprintf ('%06.6s', $laskurow["kapvm"]);										//kassa-ale1 pvm
				}
				else{
					$ulos .= sprintf ('%06.6s', "0");
				}

				$ulos .= sprintf ('%06.6s', 	"0");
				$ulos .= sprintf ('%06.6s', 	"0");
				$ulos .= sprintf ('%06.6s', 	"0");

				$ulos .= sprintf ('%012.12s',	"0");

				if($laskurow["kasumma"] > 0) {
					$ulos .= sprintf ('%012.12s', $laskurow["kasumma"]);									//kassa-ale1 valuutassa
				}
				else {
					$ulos .= sprintf ('%012.12s', "0");
				}

				$ulos .= sprintf ('%012.12s',	"0");
				$ulos .= sprintf ('%012.12s', 	"0");
				$ulos .= sprintf ('%012.12s', 	"0");

				$ulos .= sprintf ('%024.24s',  	"0");

				if($laskurow["kasumma"] > 0) {
					$ulos .= sprintf ('%01.1s', "1");														//kassa-ale1 koodi 0-ei alennusta, 1-alennus
				}
				else {
					$ulos .= sprintf ('%01.1s', "0");
				}

				$ulos .= sprintf ('%01.1s', 	"0");
				$ulos .= sprintf ('%01.1s', 	"0");
				$ulos .= sprintf ('%01.1s', 	"0");

				$ulos .= sprintf ('%02.2s',   	"0");
				$ulos .= sprintf ('%010.10s',  	"0");
				$ulos .= sprintf ('%04.4s',   	"0");														//alv (ei välitetä)
				$ulos .= sprintf ('%-30.30s', 	$laskurow["toim_nimi"]);									//toimituspaikan nimi
				$ulos .= sprintf ('%06.6s',	 	$asirow["asiakasnro"]);										//asiakasnro
				$ulos .= sprintf ('%010.10s', 	str_replace('-','',$laskurow["ytunnus"]));					//toim  ytunnus
				$ulos .= sprintf ('%-20.20s', 	$laskurow["toim_osoite"]);									//toim osoite
				$ulos .= sprintf ('%-20.20s', 	$laskurow["toim_postino"]." ".$laskurow["toim_postitp"]);	//toim postitp ja postino
				$ulos .= sprintf ('%-30.30s', 	"");
				$ulos .= sprintf ('%013.13s', 	"0");
				$ulos .= sprintf ('%-30.30s', 	"");
				$ulos .= sprintf ('%06.6s', 	"0");

				if ($laskurow["toim_maa"] != $yhtiorow["maakoodi"] and $laskurow["toim_maa"] != '') {
					$ulos .= sprintf ('%-10.10s', $laskurow["toim_maa"]);
				}
				else {
					$ulos .= sprintf ('%-10.10s', 	"");
				}

				$ulos .= sprintf ('%03.3s', 	"0");
				$ulos .= sprintf ('%020.20s', 	$laskurow["viite"]);
				$ulos .= sprintf ('%-8.8s', 	"");
				$ulos .= "\r\n";

				echo "<tr>";

				$laskukpl++;
				if ($laskurow["tyyppi"] == "01") {
					$vlaskukpl++;
					$vlaskusum += $laskurow["summa"];

					echo "<td>Veloituslasku</td><td>$laskurow[laskunro]</td><td>$laskurow[nimi]</td><td align='right'>".sprintf('%.2f', $laskurow["summa"]/100)."</td><td>$laskurow[valkoodi]</td>";
				}
				if ($laskurow["tyyppi"] == "02") {
					$hlaskukpl++;
					$hlaskusum += $laskurow["summa"];

					echo "<td>Hyvityslasku:</td><td>$laskurow[laskunro]</td><td>$laskurow[nimi]</td><td align='right'>".sprintf('%.2f', $laskurow["summa"]/100)."</td><td>$laskurow[valkoodi]</td>";
				}

				if ($asirow["asiakasnro"] == 0 or !is_numeric($asirow["asiakasnro"]) or strlen($asirow["asiakasnro"]) > 6) {
					echo "<td><font class='error'>VIRHE: Asiakasnumero: $asirow[asiakasnro] ei kelpaa!</font></td>";
				}
			}

			if ($laskuvirh > 0) {
				echo "</table>";
				echo "<br><br>";
				echo "Aineistossa oli virheitä! Korjaa ne ja aja uudestaan!";
			}
			else {
				$dquery = "	UPDATE lasku, maksuehto
							SET lasku.factoringsiirtonumero = '$factoringsiirtonumero'
							WHERE lasku.yhtio	= '$kukarow[yhtio]'
							and lasku.tila	  	= 'U'
							and lasku.alatila	= 'X'
							and lasku.summa		!= 0
							and lasku.laskunro >= '$ppa'
							and lasku.laskunro <= '$ppl'
							and lasku.factoringsiirtonumero = 0
							and lasku.valkoodi	= '$valkoodi'
							and lasku.yhtio = maksuehto.yhtio
							and lasku.maksuehto = maksuehto.tunnus
							and maksuehto.factoring = 'NORDEA'";
				$dresult = mysql_query ($dquery) or pupe_error($dquery);

				//luodaan summatietue
				$ulos .= sprintf ('%-4.4s', 	"KRFL");
				$ulos .= sprintf ('%01.1s', 	"9");
				$ulos .= sprintf ('%-17.17s', 	str_replace('-','',$yhtiorow["ytunnus"]));
				$ulos .= sprintf ('%06.6s', 	$luontipvm);
				$ulos .= sprintf ('%04.4s',   	$luontiaika);
				$ulos .= sprintf ('%06.6s', 	$laskukpl);
				$ulos .= sprintf ('%06.6s', 	$vlaskukpl);
				$ulos .= sprintf ('%013.13s', 	$vlaskusum);
				$ulos .= sprintf ('%06.6s', 	$hlaskukpl);
				$ulos .= sprintf ('%013.13s', 	$hlaskusum);
				$ulos .= sprintf ('%06.6s', 	"0");
				$ulos .= sprintf ('%013.13s', 	"0");
				$ulos .= sprintf ('%06.6s', 	"0");
				$ulos .= sprintf ('%013.13s', 	"0");
				$ulos .= sprintf ('%013.13s', 	"0");
				$ulos .= sprintf ('%-273.273s',	"");
				$ulos .= "\r\n";

				//keksitään uudelle failille joku varmasti uniikki nimi:
				$filenimi = "Nordeasiirto-$factoringsiirtonumero.txt";

				//kirjoitetaan faili levylle..
				$fh = fopen("dataout/".$filenimi, "w");
				if (fwrite($fh, $ulos) === FALSE) die("Kirjoitus epäonnistui $filenimi");
				fclose($fh);

				echo "<tr><td class='back'><br></td></tr>";

				echo "<tr><td class='back' colspan='2'></td><th>Yhteensä $vlaskukpl veloituslaskua</th><td align='right'>".sprintf('%.2f', $vlaskusum/100)."</td><td>$laskurow[valkoodi]</td></tr>";
				echo "<tr><td class='back' colspan='2'></td><th>Yhteensä $hlaskukpl hyvityslaskua</th><td align='right'> ".sprintf('%.2f', $hlaskusum/100)."</td><td>$laskurow[valkoodi]</td></tr>";

				echo "</table>";
				echo "<br><br>";
				echo "<table>";
				echo "<tr><th>Tallenna siirtoaineisto levylle:</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='SOLOMYSA.DAT'>";
				echo "<input type='hidden' name='filenimi' value='$filenimi'>";
				echo "<td><input type='submit' value='Tallenna'></td></form>";
				echo "</tr></table>";
			}
		}
		else {
			echo "<br><br>Yhtään siirrettävää laskua ei ole!<br>";
			$tee = "";
		}
	}

	if ($tee != "lataa_tiedosto") {
		require ("inc/footer.inc");
	}
?>
