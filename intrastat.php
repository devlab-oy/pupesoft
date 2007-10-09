<?php

	require('inc/parametrit.inc');

/*
	// n‰m‰ pit‰‰ ajaa jos p‰ivitt‰‰ uudet tullinimikkeet:

	update tullinimike set su=trim(su);
	update tullinimike set su='' where su='-';
	update tullinimike set su_vientiilmo='NAR' where su='p/st';
	update tullinimike set su_vientiilmo='MIL' where su='1 000 p/st';
	update tullinimike set su_vientiilmo='MIL' where su='1000 p/st';
	update tullinimike set su_vientiilmo='LPA' where su='l alc. 100';
	update tullinimike set su_vientiilmo='LTR' where su='l';
	update tullinimike set su_vientiilmo='KLT' where su='1 000 l';
	update tullinimike set su_vientiilmo='KLT' where su='1000 l';
	update tullinimike set su_vientiilmo='TJO' where su='TJ';
	update tullinimike set su_vientiilmo='MWH' where su='1 000 kWh';
	update tullinimike set su_vientiilmo='MWH' where su='1000 kWh';
	update tullinimike set su_vientiilmo='MTQ' where su='m≥';
	update tullinimike set su_vientiilmo='MTQ' where su='m3';
	update tullinimike set su_vientiilmo='GRM' where su='g';
	update tullinimike set su_vientiilmo='MTK' where su='m≤';
	update tullinimike set su_vientiilmo='MTK' where su='m2';
	update tullinimike set su_vientiilmo='MTR' where su='m';
	update tullinimike set su_vientiilmo='NPR' where su='pa';
	update tullinimike set su_vientiilmo='CEN' where su='100 p/st';

*/

	// tuonti vai vienti
	if ($tapa == "tuonti") {
		$laji = "A";
		$tilastoloppu = '001';
	}
	else {
		$tapa = "vienti";
		$laji = "D";
		$tilastoloppu = '002';
	}

	echo "<font class='head'>Intrastat-ilmoitukset</font><hr>";

	if ($tee == "tulosta") {

		// tehd‰‰n kauniiseen muotoon annetun kauden eka ja vika pvm
		$vva = date("Y",mktime(0, 0, 0, $kk, 1, $vv));
		$kka = date("m",mktime(0, 0, 0, $kk, 1, $vv));
		$ppa = date("d",mktime(0, 0, 0, $kk, 1, $vv));
		$vvl = date("Y",mktime(0, 0, 0, $kk+1, 0, $vv));
		$kkl = date("m",mktime(0, 0, 0, $kk+1, 0, $vv));
		$ppl = date("d",mktime(0, 0, 0, $kk+1, 0, $vv));

		//tuonti vai vienti
		if ($tapa == "tuonti") {
			$where1 = " and (lasku.vienti = 'F' or lasku.ultilno = '-2') ";
			$where2 = " and lasku.ultilno = '-2' ";
			$where3 = " and lasku.ultilno = '-2' ";
		}
		else {
			$tapa = "vienti";
			$where1 = " and lasku.ultilno = '-1' ";
			$where2 = " and (lasku.vienti = 'E' or lasku.ultilno = '-1') ";
			$where3 = " and lasku.ultilno = '-1' ";
		}
		
		if ($lisavar == "S") {
			$lisavarlisa = "  and (tilausrivi.perheid2=0 or tilausrivi.perheid2=tilausrivi.tunnus) ";
		}
		else {
			$lisavarlisa = "";	
		}

		if ($vaintullinimike != "") {
			$vainnimikelisa = " and tuote.tullinimike1 = '$vaintullinimike' ";
			$vainnimikegroup = " tilausrivi.tunnus, ";
		}
		else {
			$vainnimikelisa = "";
			$vainnimikegroup = "";			
		}
		
		// t‰ss‰ tulee sitten nimiketietueet unionilla
		$query = "	(SELECT
					tuote.tullinimike1,
					lasku.maa_lahetys,
					(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa,
					lasku.maa_maara,
					lasku.laskunro,
					tuote.tuoteno,
					lasku.kauppatapahtuman_luonne,
					lasku.kuljetusmuoto,
					round(sum(tilausrivi.kpl),0) kpl,
					tullinimike.su_vientiilmo su,
					if (round(sum((tilausrivi.kpl * tilausrivi.hinta * lasku.vienti_kurssi *
					(SELECT if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno LIMIT 1)
					/ lasku.summa) * lasku.bruttopaino), 0) > 0.5,
					round(sum((tilausrivi.kpl * tilausrivi.hinta * lasku.vienti_kurssi *
					(SELECT if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno LIMIT 1)
					/ lasku.summa) * lasku.bruttopaino), 0), 1) as paino,
					if(round(sum(tilausrivi.rivihinta),0) > 0.50, round(sum(tilausrivi.rivihinta),0), 1) rivihinta,
					group_concat(lasku.tunnus) as kaikkitunnukset,
					group_concat(distinct tilausrivi.perheid2) as perheid2set,
					group_concat(concat(\"'\",tuote.tuoteno,\"'\") SEPARATOR ',') as kaikkituotteet,
					'Keikka' as tapa
					FROM lasku use index (yhtio_tila_mapvm)
					JOIN tilausrivi use index (uusiotunnus_index) ON tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.yhtio=lasku.yhtio and tilausrivi.kpl > 0 $lisavarlisa
					JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '' $vainnimikelisa
					LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tullinimike.cn != ''
					WHERE lasku.kohdistettu = 'X'
					and lasku.tila = 'K'
					and lasku.vanhatunnus = 0
					$where1
					and lasku.kauppatapahtuman_luonne != '999'
					and lasku.yhtio = '$kukarow[yhtio]'
					and lasku.mapvm >= '$vva-$kka-$ppa'
					and lasku.mapvm <= '$vvl-$kkl-$ppl'
					GROUP BY $vainnimikegroup tuote.tullinimike1, lasku.maa_lahetys, alkuperamaa, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne)

					UNION

					(SELECT
					tuote.tullinimike1,
					lasku.maa_lahetys,
					(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa,
					lasku.maa_maara,
					lasku.laskunro,
					tuote.tuoteno,
					lasku.kauppatapahtuman_luonne,
					lasku.kuljetusmuoto,
					round(sum(tilausrivi.kpl),0) kpl,
					tullinimike.su_vientiilmo su,
					if(round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0), if(round(sum(tilausrivi.kpl*tuote.tuotemassa),0) > 0.5, round(sum(tilausrivi.kpl*tuote.tuotemassa),0),1)) paino,
					if(round(sum(tilausrivi.rivihinta),0) > 0.50,round(sum(tilausrivi.rivihinta),0), 1) rivihinta,
					group_concat(lasku.tunnus) as kaikkitunnukset,
					group_concat(distinct tilausrivi.perheid2) as perheid2set,
					group_concat(concat(\"'\",tuote.tuoteno,\"'\") SEPARATOR ',') as kaikkituotteet,
					'Lasku' as tapa					
					FROM lasku use index (yhtio_tila_tapvm)
					JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.otunnus=lasku.tunnus and tilausrivi.yhtio=lasku.yhtio and tilausrivi.kpl > 0 $lisavarlisa
					JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '' $vainnimikelisa
					LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tullinimike.cn != ''
					WHERE lasku.tila = 'L'
					and lasku.alatila = 'X'
					and lasku.vienti != 'K'
					$where2
					and lasku.kauppatapahtuman_luonne != '999'
					and lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tapvm >= '$vva-$kka-$ppa'
					and lasku.tapvm <= '$vvl-$kkl-$ppl'
					GROUP BY $vainnimikegroup tuote.tullinimike1, lasku.maa_lahetys, alkuperamaa, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne)

					UNION

					(SELECT
					tuote.tullinimike1,
					lasku.maa_lahetys,
					(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa,
					lasku.maa_maara,
					lasku.tunnus laskunro,
					tuote.tuoteno,
					lasku.kauppatapahtuman_luonne,
					lasku.kuljetusmuoto,
					round(sum(tilausrivi.kpl),0) kpl,
					tullinimike.su_vientiilmo su,
					if(round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0), if(round(sum(tilausrivi.kpl*tuote.tuotemassa),0) > 0.5, round(sum(tilausrivi.kpl*tuote.tuotemassa),0),1)) paino,
					if(round(sum(tilausrivi.rivihinta),0) > 0.50, round(sum(tilausrivi.rivihinta),0), 1) rivihinta,
					group_concat(lasku.tunnus) as kaikkitunnukset,
					group_concat(distinct tilausrivi.perheid2) as perheid2set,
					group_concat(concat(\"'\",tuote.tuoteno,\"'\") SEPARATOR ',') as kaikkituotteet,
					'Siirtolista' as tapa
					FROM lasku use index (yhtio_tila_tapvm)
					JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.otunnus=lasku.tunnus and tilausrivi.yhtio=lasku.yhtio and tilausrivi.kpl > 0 $lisavarlisa
					JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '' $vainnimikelisa
					LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]' and tullinimike.cn != ''
					WHERE lasku.tila = 'G'
					and lasku.alatila = 'V'
					$where3
					and lasku.kauppatapahtuman_luonne != '999'
					and lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tapvm >= '$vva-$kka-$ppa'
					and lasku.tapvm <= '$vvl-$kkl-$ppl'
					GROUP BY $vainnimikegroup tuote.tullinimike1, lasku.maa_lahetys, alkuperamaa, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne)

					ORDER BY laskunro, tuoteno ";
		$result = mysql_query($query) or pupe_error($query);

		$nim     = "";
		$lask    = 1;
		$arvoyht = 0;
		$virhe   = 0;

		// tehd‰‰n kaunista ruutukamaa
		$ulos = "<table>";
		$ulos .= "<tr>";
		$ulos .= "<th>Laskunro</th>";
		$ulos .= "<th>Tuoteno</th>";
		$ulos .= "<th>Nimitys</th>";
		$ulos .= "<th>Tullinimike</th>";
		$ulos .= "<th>KT</th>";
		$ulos .= "<th>AM</th>";
		$ulos .= "<th>LM</th>";
		$ulos .= "<th>MM</th>";
		$ulos .= "<th>KM</th>";
		$ulos .= "<th>Rivihinta</th>";
		$ulos .= "<th>Paino</th>";
		$ulos .= "<th>Toinen paljous</th>";
		$ulos .= "<th>Kpl</th>";
		
		if ($lisavar == "S") {
			$ulos .= "<th>Tehdaslis‰varusteet</th>";
		}
		
		$ulos .= "<th>Virhe</th>";
		$ulos .= "</tr>";

		// tehd‰‰n tilastoarvot listausta
		$tilastoarvot = "<table>
			<tr>
			<th>#</th>
			<th>Tullinimike</th>
			<th>Alkuper‰maa</th>
			<th>L‰hetysmaa</th>
			<th>M‰‰r‰maa</th>
			<th>Kuljetusmuoto</th>
			<th>Kauppat. luonne</th>
			<th>Tilastoarvo</th>
			<th>Paino</th>
			<th>2-paljous</th>
			<th>2-paljous m‰‰r‰</th>
			<th>Laskutusarvo</th>
			</tr>";
		// 1. L‰hett‰j‰tietue

		// ytunnus konekielell‰
		$ytunnus = sprintf ('%08d', str_replace('-','',$yhtiorow["ytunnus"]));

		// Suomen maatunnus intrastatiksi
		$maatunnus = "0037";

		// ytunnuksen lis‰osa
		$ylisatunnus = $yhtiorow["int_koodi"];

		$lah  = sprintf ('%-3.3s', 		"KON");
		$lah .= sprintf ('%-17.17s', $maatunnus.$ytunnus.$ylisatunnus);
		$lah .= "\r\n";

		// 2. Otsikkotietue
		// p‰iv‰n numero
		$pvanumero = sprintf ('%03d',date('z')+1);
		$vuosi = sprintf ('%02d', substr($vv,-2));
		$kuuka = sprintf ('%02d', $kk);

		$ots  = sprintf ('%-3.3s', 		"OTS");																									//tietuetunnus
		$ots .= sprintf ('%-13.13s', 	date("y").$yhtiorow["tilastotullikamari"].$pvanumero.$yhtiorow["intrastat_sarjanro"].$tilastoloppu);	//Tilastonumero
		$ots .= sprintf ('%-1.1s',		$laji);																									//Onko tuotia vai vienti‰, kts alkua...
		$ots .= sprintf ('%-4.4s',		$vuosi.$kuuka);																							//tilastointijakso
		$ots .= sprintf ('%-3.3s',		"T"); 																									//tietok‰sittelykoodi
		$ots .= sprintf ('%-13.13s',	""); 																									//virheellisen tilastonro, tyhj‰ksi j‰tet‰‰n....
		$ots .= sprintf ('%-17.17s', 	"FI".$ytunnus.$ylisatunnus);																			//tiedoantovelvollinen
		$ots .= sprintf ('%-17.17s', 	"");																									//t‰h‰n vois laittaa asiamiehen tiedot...
		$ots .= sprintf ('%-10.10s', 	"");																									//t‰h‰n vois laittaa asiamiehen lis‰tiedot...
		$ots .= sprintf ('%-17.17s', 	$yhtiorow["tilastotullikamari"]);																		//tilastotullikamari
		$ots .= sprintf ('%-3.3s',	 	$yhtiorow["valkoodi"]);																					//valuutta
		$ots .= "\r\n";

		while ($row = mysql_fetch_array($result)) {

			// tehd‰‰n tarkistukset
			require ("inc/intrastat_tarkistukset.inc");

			if ($row["perheid2set"] != "0" and $lisavar == "S") {
				$query  = "	SELECT ";
				
				if ($row["tapa"] != "Keikka") {
					$query .= "	if (round(sum((tilausrivi.kpl * tilausrivi.hinta * lasku.vienti_kurssi *
								(SELECT if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno LIMIT 1)
								/ lasku.summa) * lasku.bruttopaino), 0) > 0.5,
								round(sum((tilausrivi.kpl * tilausrivi.hinta * lasku.vienti_kurssi *
								(SELECT if(tuotteen_toimittajat.tuotekerroin=0,1,tuotteen_toimittajat.tuotekerroin) FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno LIMIT 1)
								/ lasku.summa) * lasku.bruttopaino), 0), 1) as paino,
								if(round(sum(tilausrivi.rivihinta),0) > 0.50, round(sum(tilausrivi.rivihinta),0), 1) rivihinta";
				}
				elseif ($row["tapa"] != "Lasku") {
					$query .= "	if(round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0), if(round(sum(tilausrivi.kpl*tuote.tuotemassa),0) > 0.5, round(sum(tilausrivi.kpl*tuote.tuotemassa),0),1)) paino,
								if(round(sum(tilausrivi.rivihinta),0) > 0.50,round(sum(tilausrivi.rivihinta),0), 1) rivihinta";
				}
				else {
					$query .= "	if(round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0), if(round(sum(tilausrivi.kpl*tuote.tuotemassa),0) > 0.5, round(sum(tilausrivi.kpl*tuote.tuotemassa),0),1)) paino,
								round(sum(tilausrivi.rivihinta), 0) rivihinta";
				}

				$query .= "	FROM tilausrivi use index (yhtio_perheid2)
							JOIN lasku ON tilausrivi.otunnus = lasku.tunnus and tilausrivi.yhtio = lasku.yhtio 
							JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = ''
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
							and tilausrivi.kpl > 0
							and tilausrivi.perheid2 > 0
							and tilausrivi.perheid2 != tilausrivi.tunnus
							and tilausrivi.perheid2 in ($row[perheid2set])";
				$lisavarres = mysql_query($query) or pupe_error($query);
				$lisavarrow = mysql_fetch_array($lisavarres);
				
				$row["paino"] 		+= $lisavarrow["paino"];
				$row["rivihinta"] 	+= $lisavarrow["rivihinta"];
			}
				
			// 3. Nimiketietue
			$nim .= sprintf ('%-3.3s', 		"NIM");																								//tietuetunnus
			$nim .= sprintf ('%05d', 		$lask);																								//j‰rjestysnumero
			$nim .= sprintf ('%-8.8s', 		$row["tullinimike1"]);																				//Tullinimike CN
			$nim .= sprintf ('%-2.2s', 		$row["kauppatapahtuman_luonne"]);																	//kauppatapahtuman luonne

			if ($tapa == "tuonti") {
				$nim .= sprintf ('%-2.2s', 	$row["alkuperamaa"]);																				//alkuper‰maa
				$nim .= sprintf ('%-2.2s', 	$row["maa_lahetys"]);																				//l‰hetysmaa
				$nim .= sprintf ('%-2.2s', 	"");
			}
			else {
				$nim .= sprintf ('%-2.2s', 	"");
				$nim .= sprintf ('%-2.2s', 	"");
				$nim .= sprintf ('%-2.2s', 	$row["maa_maara"]);																					//m‰‰r‰maa
			}

			$nim .= sprintf ('%-1.1s', 		$row["kuljetusmuoto"]);																				//kuljetusmuoto
			$nim .= sprintf ('%010d', 		$row["rivihinta"]);																					//tilastoarvo
			$nim .= sprintf ('%-15.15s',	"");																								//ilmoitajan viite...
			$nim .= sprintf ('%-3.3s',		"WT");																								//m‰‰r‰ntarkennin 1
			$nim .= sprintf ('%-3.3s',		"KGM");																								//paljouden lajikoodi
			$nim .= sprintf ('%010d', 		$row["paino"]);																						//nettopaino
			$nim .= sprintf ('%-3.3s',		"AAE");																								//m‰‰r‰ntarkennin 2, muu paljous

			if ($row["su"] != '') {
				$nim .= sprintf ('%-3.3s',		$row["su"]); 																					//2 paljouden lajikoodi
				$nim .= sprintf ('%010d', 		$row["kpl"]);																					//2 paljouden m‰‰r‰
			}
			else {
				$nim .= sprintf ('%-3.3s',		""); 																							//2 paljouden lajikoodi
				$nim .= sprintf ('%010d', 		"");																							//2 paljouden m‰‰r‰
			}

			$nim .= sprintf ('%010d', 		$row["rivihinta"]);																					//nimikkeen laskutusarvo
			$nim .= "\r\n";

			// tehd‰‰n kaunista ruutukamaa
			$ulos .= "<tr class='aktiivi'>";
			$ulos .= "<td valign='top'>".$row["laskunro"]."</td>";
			$ulos .= "<td valign='top'>".$row["tuoteno"]."</td>";
			$ulos .= "<td valign='top'>".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."</td>";
 			$ulos .= "<td valign='top'><a href='intrastat.php?tee=tulosta&tapa=$tapa&kk=$kk&vv=$vv&outputti=$outputti&lahetys=nope&lisavar=$lisavar&vaintullinimike=$row[tullinimike1]'>$row[tullinimike1]</></td>";	//Tullinimike CN
			$ulos .= "<td valign='top'>".$row["kauppatapahtuman_luonne"]."</td>";
			$ulos .= "<td valign='top'>".$row["alkuperamaa"]."</td>";
			$ulos .= "<td valign='top'>".$row["maa_lahetys"]."</td>";
			$ulos .= "<td valign='top'>".$row["maa_maara"]."</td>";
			$ulos .= "<td valign='top'>".$row["kuljetusmuoto"]."</td>";
			$ulos .= "<td valign='top' align='right'>".$row["rivihinta"]."</td>";
			$ulos .= "<td valign='top' align='right'>".$row["paino"]."</td>";
			$ulos .= "<td valign='top'>".$row["su"]."</td>";
			$ulos .= "<td valign='top' align='right'>".$row["kpl"]."</td>";
			
			if ($lisavar == "S") {
				if ($row["perheid2set"] != "0") {
					$ulos .= "<td valign='top'>Tehdaslis‰varusteet:<br>Paino: $lisavarrow[paino]<br>Arvo: $lisavarrow[rivihinta]</td>";
				}
				else {
					$ulos .= "<td valign='top'></td>";
				}
			}
			
			$ulos .= "<td valign='top'><font class='error'>".$virhetxt."</font></td>";
			$ulos .= "</tr>";

			// tehd‰‰n tilastoarvolistausta
			$tilastoarvot .= "<tr>";
			$tilastoarvot .= "<td>$lask</td>";																									//j‰rjestysnumero
			$tilastoarvot .= "<td><a href='intrastat.php?tee=tulosta&tapa=$tapa&kk=$kk&vv=$vv&outputti=$outputti&lahetys=nope&lisavar=$lisavar&vaintullinimike=$row[tullinimike1]'>$row[tullinimike1]</></td>";	//Tullinimike CN

			if ($tapa == "tuonti") {
				$tilastoarvot .= "<td>$row[alkuperamaa]</td>";																					//alkuper‰maa
				$tilastoarvot .= "<td>$row[maa_lahetys]</td>";																					//l‰hetysmaa
				$tilastoarvot .= "<td></td>";
			}
			else {
				$tilastoarvot .= "<td></td>";
				$tilastoarvot .= "<td></td>";
				$tilastoarvot .= "<td>$row[maa_maara]</td>";																					//m‰‰r‰maa
			}

			$tilastoarvot .= "<td>$row[kuljetusmuoto]</td>";																					//kuljetusmuoto
			$tilastoarvot .= "<td>$row[kauppatapahtuman_luonne]</td>";																			//kauppatapahtuman luonne
			$tilastoarvot .= "<td>$row[rivihinta]</td>";																						//tilastoarvo
			$tilastoarvot .= "<td>$row[paino]</td>";																							//nettopaino

			if ($row["su"] != '') {
				$tilastoarvot .= "<td>$row[su]</td>"; 																							//2 paljouden lajikoodi
				$tilastoarvot .= "<td>$row[kpl]</td>";																							//2 paljouden m‰‰r‰
			}
			else {
				$tilastoarvot .= "<td></td>"; 																									//2 paljouden lajikoodi
				$tilastoarvot .= "<td></td>";																									//2 paljouden m‰‰r‰
			}

			$tilastoarvot .= "<td>$row[rivihinta]</td>";																						//nimikkeen laskutusarvo
			$tilastoarvot .= "</tr>";

			// summaillaan
			$lask++;
			$arvoyht		+= $row["rivihinta"];
			$bruttopaino	+= $row["paino"];
			$totsumma		+= $row["rivihinta"];
			$totkpl			+= $row["kpl"];
		}

		// 4. Summatietue
		$sum  = sprintf ('%-3.3s', 		"SUM");																									//tietuetunnus
		$sum .= sprintf ('%018d', 		$lask-1);																								//nimikkeiden lukum‰‰r‰
		$sum .= sprintf ('%018d', 		$arvoyht);																								//laskutusarvo yhteens‰
		$sum .= "\r\n";

		// tehd‰‰n kaunista ruutukamaa
		$ulos .= "<tr>";
		$ulos .= "<th colspan='9'>Yhteens‰:</th>";
		$ulos .= "<th>$totsumma</th>";
		$ulos .= "<th>$bruttopaino</th>";
		$ulos .= "<th></th>";
		$ulos .= "<th>$totkpl</th>";
		$ulos .= "<th></th>";
		
		if ($lisavar == "S") {
			$ulos .= "<th></th>";
		}
		
		$ulos .= "</tr>";
		$ulos .= "</table>";

		// tehd‰‰n tilaustoarvolistausta
		$tilastoarvot .= "<tr>";
		$tilastoarvot .= "<th colspan='7'>Yhteens‰:</td>";
		$tilastoarvot .= "<th>$arvoyht</th>";
		$tilastoarvot .= "<th colspan='4'></th>";
		$tilastoarvot .= "</tr>";
		$tilastoarvot .= "</table>";

		// ei virheit‰ .. ja halutaan l‰hett‰‰ jotain meilej‰
		if ($virhe == 0 and $lahetys != "nope") {

			//PGP-encryptaus labeli
			$label  = '';
			$label .= "l‰hett‰j‰: $yhtiorow[nimi]\r\n";
			$label .= "sis‰ltˆ: vientitullaus/sis‰kaupantilasto\r\n";
			$label .= "kieli: ASCII\r\n";
			$label .= "jakso: $vv$kk\r\n";
			$label .= "koko aineiston tietuem‰‰r‰: $lask-1\r\n";
			$label .= "koko aineiston vienti-, verotus- tai laskutusarvo: $arvoyht\r\n";

			$recipient = "pgp-key Customs Finland <ascii.intra@tulli.fi>"; 				// t‰m‰ on tullin virallinen avain
			//$recipient = "pgp-testkey Customs Finland <test.ascii.intra@tulli.fi>"; 	// t‰m‰ on tullin testiavain

			$message = '';
			$message = $label;
			require("inc/gpg.inc");
			$otsikko_gpg = $encrypted_message;
			$otsikko_plain = $message;

			//PGP-encryptaus atktietue
			$recipient = "pgp-key Customs Finland <ascii.intra@tulli.fi>"; 				// t‰m‰ on tullin virallinen avain
			// $recipient = "pgp-testkey Customs Finland <test.ascii.intra@tulli.fi>"; 	// t‰m‰ on tullin testiavain

			$message = '';
			$message = $lah.$ots.$nim.$sum;
			require("inc/gpg.inc");
			$tietue_gpg = $encrypted_message;
			$tietue_plain = $message;

			$bound = uniqid(time()."_") ;

			$header  = "From: <$yhtiorow[admin_email]>\n";
			$header .= "MIME-Version: 1.0\n";
			$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n";

			$content = "--$bound\n";

			$content .= "Content-Type: application/pgp-encrypted;\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"otsikko.pgp\"\n\n";
			$content .= chunk_split(base64_encode($otsikko_gpg));
			$content .= "\n";

			$content .= "--$bound\n";

			$content .= "Content-Type: application/pgp-encrypted;\n";
			$content .= "Content-Transfer-Encoding: base64\n";
			$content .= "Content-Disposition: attachment; filename=\"tietue.pgp\"\n\n";
			$content .= chunk_split(base64_encode($tietue_gpg));
			$content .= "\n";

			$content .= "--$bound\n";

			if ($lahetys == "tuli" or $lahetys == "mole") {
				// l‰hetet‰‰n meili tulliin
				$to = 'ascii.intrastat@tulli.fi'; 			// t‰m‰ on tullin virallinen osoite
				//$to = 'test.ascii.intrastat@tulli.fi'; 	// t‰m‰ on tullin testiosoite

				mail($to, "", $content, $header, "-f $yhtiorow[admin_email]");
				echo "<font class='message'>Tiedot l‰hetettiin tulliin.</font><br><br>";
			}
			else {
				echo "<font class='message'>Tietoja EI l‰hetetty tulliin.</font><br><br>";
			}

			// liitet‰‰n mukaan myˆs salaamattomat tiedostot
			$content .= "Content-Type: text/plain;\n" ;
			$content .= "Content-Transfer-Encoding: base64\n" ;
			$content .= "Content-Disposition: attachment; filename=\"otsikko.txt\"\n\n";
			$content .= chunk_split(base64_encode($otsikko_plain));
			$content .= "\n";

			$content .= "--$bound\n";

			$content .= "Content-Type: text/plain;\n";
			$content .= "Content-Transfer-Encoding: base64\n";
			$content .= "Content-Disposition: attachment; filename=\"tietue.txt\"\n\n";
			$content .= chunk_split(base64_encode($tietue_plain));
			$content .= "\n";

			$content .= "--$bound\n";

			// katotaan l‰hetet‰‰nkˆ meili k‰ytt‰j‰lle
			if (($lahetys == "mina" or $lahetys == "mole") and $kukarow["eposti"] != "") {
				// j‰ l‰hetet‰‰n k‰ytt‰j‰lle
				mail($kukarow["eposti"], "$yhtiorow[nimi] - Intrastat $tapa-ilmoitus $vv/$kk ($kukarow[kuka])", $content, $header, "-f $yhtiorow[admin_email]");
			}

			// ja aina adminille
			mail($yhtiorow["admin_email"], "$yhtiorow[nimi] - Intrastat $tapa-ilmoitus $vv/$kk ($kukarow[kuka])", $content, $header, "-f $yhtiorow[admin_email]");

		}
		else {
			if ($virhe != 0) {
				echo "<font class='error'>Aineistossa on virheit‰! Korjaa virheet!</font><br>";
			}
			echo "<font class='error'>Aineistoa EI l‰hetetty minnek‰‰n.</font><br><br>";
		}

		// echotaan oikea taulukko ruudulle
		if ($outputti == "tilasto") {
			echo "$tilastoarvot";
		}
		else {
			echo "$ulos";
		}

	}

	// K‰yttˆliittym‰
	if (!isset($kk)) $kk = date("m");
	if (!isset($vv)) $vv = date("Y");

	if ($tapa == "vientituonti") $tapa = "vienti";
	if ($tapa == "tuontivienti") $tapa = "tuonti";

	$sel1[$outputti] = "SELECTED";
	$sel2[$tapa]     = "SELECTED";
	$sel3[$lahetys]  = "SELECTED";
	$sel4[$lisavar]  = "SELECTED";

	echo "<br>

	<form method='post' action='$PHP_SELF'>
	<input type='hidden' name='tee' value='tulosta'>

	<table>
		<tr>
			<th>Valitse ilmoitus</th>
			<td>
				<select name='tapa'>
				<option value='vienti' $sel2[vienti]>Vienti-ilmoitus</option>
				<option value='tuonti' $sel2[tuonti]>Tuonti-ilmoitus</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>Syˆt‰ kausi (kk-vvvv)</th>
			<td>
				<input type='text' name='kk' value='$kk' size='3'>
				<input type='text' name='vv' value='$vv' size='5'>
			</td>
		</tr>
		<tr>
			<th>N‰yt‰ ruudulla</th>
			<td>
				<select name='outputti'>
				<option value='normi'   $sel1[normi]>Normaalilistaus</option>
				<option value='tilasto' $sel1[tilasto]>Tilastoarvolistaus</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>Tietojen l‰hetys s‰hkˆpostilla</th>
			<td>
			<select name='lahetys'>
			<option value='nope' $sel3[nope]>ƒl‰ l‰het‰ aineistoa minnek‰‰n</option>
			<option value='mina' $sel3[mina]>L‰het‰ aineisto vain minulle</option>
			<option value='tuli' $sel3[tuli]>L‰het‰ aineisto vain tulliin</option>
			<option value='mole' $sel3[mole]>L‰het‰ aineisto tulliin sek‰ minulle</option>
			</select>
		</tr>
		<tr>
			<th>Tehdaslis‰varusteet</th>
			<td>
			<select name='lisavar'>
			<option value='O' $sel4[O]>Omilla riveill‰‰n</option>
			<option value='S' $sel4[S]>Yhdistet‰‰n laitteeseen</option>
			</select>
		</tr>
	</table>

	<br>
	<input type='submit' value='Luo aineisto'>
	</form>";

	require("inc/footer.inc");

?>
