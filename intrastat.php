<?php
	require('inc/parametrit.inc');

/*
	// nämä pitää ajaa jos päivittää uudet tullinimikkeet:

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
	update tullinimike set su_vientiilmo='MTQ' where su='m³';
	update tullinimike set su_vientiilmo='MTQ' where su='m3';
	update tullinimike set su_vientiilmo='GRM' where su='g';
	update tullinimike set su_vientiilmo='MTK' where su='m²';
	update tullinimike set su_vientiilmo='MTK' where su='m2';
	update tullinimike set su_vientiilmo='MTR' where su='m';
	update tullinimike set su_vientiilmo='NPR' where su='pa';
	update tullinimike set su_vientiilmo='CEN' where su='100 p/st';

*/

	echo "<font class='head'>".t("Intrastat")." $toim-".t("ilmoitus").":</font><hr>";

	//tuoti vai vienti
	if ($toim == "tuonti") {
		$laji = "A";
		// kohdistettu X kun viety varastoon..
		$where = " lasku.kohdistettu='X' and tila = 'K' and lasku.vienti='F' ";
		$tilastoloppu = '001';
	}
	elseif ($toim == "vienti") {
		$laji = "D";
		$where = " tila='U' and alatila='X' and lasku.vienti='E' ";
		$tilastoloppu = '002';
	}
	else {
		echo t("Koita nyt päättää, haluutko tuontia vai vientiä")."!";
		exit;
	}

	if ($tee == "tulosta") {

		// tehdään kaunista ruutukamaa
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
		$ulos .= "<th>Virhe</th>";
		$ulos .= "</tr>";

		//1. Lähettäjätietue

		//ytunnus konekielellä
		$ytunnus = sprintf ('%08d', str_replace('-','',$yhtiorow["ytunnus"]));

		//Suomen maatunnus intrastatiksi
		$maatunnus = "0037";

		//ytunnuksen lisäosa
		$ylisatunnus = $yhtiorow["int_koodi"];

		$lah  = sprintf ('%-3.3s', 		"KON");
		$lah .= sprintf ('%-17.17s', $maatunnus.$ytunnus.$ylisatunnus);
		$lah .= "\r\n";

		//2. Otsikkotietue
		//päivän numero
		$pvanumero = sprintf ('%03d',date('z')+1);

		$ots  = sprintf ('%-3.3s', 		"OTS");						//tietuetunnus
		$ots .= sprintf ('%-13.13s', 	date("y").$yhtiorow["tilastotullikamari"].$pvanumero.$yhtiorow["intrastat_sarjanro"].$tilastoloppu);	//Tilastonumero
		$ots .= sprintf ('%-1.1s',		$laji);						//Onko tuotia vai vientiä, kts alkua...

		//vuosi kahdella
		$vuosi = sprintf ('%02d', substr($vv,-2));
		//kuukausi kahdella
		$kuuka = sprintf ('%02d', $kk);

		$ots .= sprintf ('%-4.4s',		$vuosi.$kuuka);				//tilastointijakso
		$ots .= sprintf ('%-3.3s',		"T"); 						//tietokäsittelykoodi
		$ots .= sprintf ('%-13.13s',	""); 						//virheellisen tilastonro, tyhjäksi jätetään....
		$ots .= sprintf ('%-17.17s', 	t("FI").$ytunnus.$ylisatunnus);//tiedoantovelvollinen
		$ots .= sprintf ('%-17.17s', 	"");						//tähän vois laittaa asiamiehen tiedot...
		$ots .= sprintf ('%-10.10s', 	"");						//tähän vois laittaa asiamiehen lisätiedot...
		$ots .= sprintf ('%-17.17s', 	$yhtiorow["tilastotullikamari"]);	//tilastotullikamari
		$ots .= sprintf ('%-3.3s',	 	$yhtiorow["valkoodi"]);						//valuutta
		$ots .= "\r\n";

		//tässä tulee sitten nimiketietueet
		$query = "	SELECT
						tuote.tullinimike1,
						lasku.maa_lahetys,
						(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa,
						lasku.maa_maara,
						lasku.laskunro,
						tuote.tuoteno,
						lasku.kauppatapahtuman_luonne,
						lasku.kuljetusmuoto,
						round(sum(tilausrivi.kpl),0) kpl,
						tullinimike.su_vientiilmo su,";

		if ($toim == 'vienti') {
			$query .= "	if(round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.rivihinta/lasku.summa)*lasku.bruttopaino),0), if(round(sum(tilausrivi.kpl*tuote.tuotemassa),0) > 0.5, round(sum(tilausrivi.kpl*tuote.tuotemassa),0),1)) paino,
						if(round(sum(tilausrivi.rivihinta),0) > 0.50,round(sum(tilausrivi.rivihinta),0), 1) rivihinta
						FROM lasku use index (yhtio_tila_tapvm)";
		}
		else {
			$query .= "	if(round(sum((tilausrivi.kpl*tilausrivi.hinta/lasku.summa)*lasku.bruttopaino),0) > 0.5, round(sum((tilausrivi.kpl*tilausrivi.hinta/lasku.summa)*lasku.bruttopaino),0), 1) paino,
						if(round(sum(tilausrivi.kpl*tuote.kehahin),0) > 0.50, round(sum(tilausrivi.kpl*tuote.kehahin),0), 1) rivihinta
						FROM lasku use index (yhtio_tila_mapvm)";
		}

		// tehdään kauniiseen muotoon annetun kauden eka ja vika pvm
		$vva = date("Y",mktime(0, 0, 0, $kk, 1, $vv));
		$kka = date("m",mktime(0, 0, 0, $kk, 1, $vv));
		$ppa = date("d",mktime(0, 0, 0, $kk, 1, $vv));
		$vvl = date("Y",mktime(0, 0, 0, $kk+1, 0, $vv));
		$kkl = date("m",mktime(0, 0, 0, $kk+1, 0, $vv));
		$ppl = date("d",mktime(0, 0, 0, $kk+1, 0, $vv));

		$query .= "	JOIN tilausrivi use index (uusiotunnus_index) ON tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.yhtio=lasku.yhtio and tilausrivi.kpl > 0
					JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = ''
					LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]'
					WHERE $where
					and lasku.kauppatapahtuman_luonne != '999'
					and lasku.yhtio='$kukarow[yhtio]'";

		if ($toim == 'vienti') {
			$query .= "	and tapvm>='$vva-$kka-$ppa'
						and tapvm<='$vvl-$kkl-$ppl'";
		}
		else {
			$query .= "	and mapvm>='$vva-$kka-$ppa'
						and mapvm<='$vvl-$kkl-$ppl'";
		}

		$query .= "GROUP BY tuote.tullinimike1, lasku.maa_lahetys, alkuperamaa, lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne
					ORDER BY laskunro, tuoteno";
		$result = mysql_query($query) or pupe_error($query);

		$nim     = "";
		$lask    = 1;
		$arvoyht = 0;
		$virhe   = 0;

		while ($row = mysql_fetch_array($result)) {

			// tehdään tarkistukset
			require ("inc/intrastat_tarkistukset.inc");

			//3. Nimiketietue
			$nim .= sprintf ('%-3.3s', 		"NIM");					//tietuetunnus
			$nim .= sprintf ('%05d', 		$lask);					//järjestysnumero
			$nim .= sprintf ('%-8.8s', 		$row["tullinimike1"]);	//Tullinimike CN
			$nim .= sprintf ('%-2.2s', 		$row["kauppatapahtuman_luonne"]);	//kauppatapahtuman luonne

			if ($toim == "tuonti") {
				$nim .= sprintf ('%-2.2s', 	$row["alkuperamaa"]);	//alkuperämaa
			}
			else {
				$nim .= sprintf ('%-2.2s', 	"");
			}
			if ($toim == "tuonti") {
				$nim .= sprintf ('%-2.2s', 	$row["maa_lahetys"]);	//lähetysmaa
			}
			else {
				$nim .= sprintf ('%-2.2s', 	"");
			}
			if ($toim == "vienti") {
				$nim .= sprintf ('%-2.2s', 	$row["maa_maara"]);		//määrämaa
			}
			else {
				$nim .= sprintf ('%-2.2s', 	"");
			}

			$nim .= sprintf ('%-1.1s', 		$row["kuljetusmuoto"]);	//kuljetusmuoto
			$nim .= sprintf ('%010d', 		$row["rivihinta"]);		//tilastoarvo
			$nim .= sprintf ('%-15.15s',	"");					//ilmoitajan viite...
			$nim .= sprintf ('%-3.3s',		"WT");					//määräntarkennin 1
			$nim .= sprintf ('%-3.3s',		"KGM");					//paljouden lajikoodi
			$nim .= sprintf ('%010d', 		$row["paino"]);			//nettopaino
			$nim .= sprintf ('%-3.3s',		"AAE");					//määräntarkennin 2, muu paljous

			if ($row["su"] != '') {
				$nim .= sprintf ('%-3.3s',		$row["su"]); 		//2 paljouden lajikoodi
				$nim .= sprintf ('%010d', 		$row["kpl"]);		//2 paljouden määrä
			}
			else {
				$nim .= sprintf ('%-3.3s',		""); 				//2 paljouden lajikoodi
				$nim .= sprintf ('%010d', 		"");				//2 paljouden määrä
			}
			$nim .= sprintf ('%010d', 		$row["rivihinta"]);		//nimikkeen laskutusarvo
			$nim .= "\r\n";

			$lask++;
			$arvoyht += $row["rivihinta"];

			// tehdään kaunista ruutukamaa
			$ulos .= "<tr>";
			$ulos .= "<td>".$row["laskunro"]."</td>";
			$ulos .= "<td>".$row["tuoteno"]."</td>";
			$ulos .= "<td>".$row["nimitys"]."</td>";
			$ulos .= "<td>".$row["tullinimike1"]."</td>";
			$ulos .= "<td>".$row["kauppatapahtuman_luonne"]."</td>";
			$ulos .= "<td>".$row["alkuperamaa"]."</td>";
			$ulos .= "<td>".$row["maa_lahetys"]."</td>";
			$ulos .= "<td>".$row["maa_maara"]."</td>";
			$ulos .= "<td>".$row["kuljetusmuoto"]."</td>";
			$ulos .= "<td align='right'>".$row["rivihinta"]."</td>";
			$ulos .= "<td align='right'>".$row["paino"]."</td>";
			$ulos .= "<td>".$row["su"]."</td>";
			$ulos .= "<td align='right'>".$row["kpl"]."</td>";
			$ulos .= "<td><font class='error'>".$virhetxt."</font></td>";
			$ulos .= "</tr>";

			$bruttopaino += $row["paino"];
			$totsumma += $row["rivihinta"];
			$totkpl += $row["kpl"];
		}

		//4. Summatietue
		$sum  = sprintf ('%-3.3s', 		"SUM");						//tietuetunnus
		$sum .= sprintf ('%018d', 		$lask-1);					//nimikkeiden lukumäärä
		$sum .= sprintf ('%018d', 		$arvoyht);					//laskutusarvo yhteensä
		$sum .= "\r\n";

		$ulos .= "<tr>";
		$ulos .= "<th colspan='9'>Yhteensä:</th>";
		$ulos .= "<th>$totsumma</th>";
		$ulos .= "<th>$bruttopaino</th>";
		$ulos .= "<th></th>";
		$ulos .= "<th>$totkpl</th>";
		$ulos .= "<th></th>";
		$ulos .= "</tr>";
		$ulos .= "</table>";

		// ei virheitä ja meillä on jotain lähetettävää...
		if ($virhe == 0 and mysql_num_rows($result) > 0) {

			//PGP-encryptaus labeli
			$label  = '';
			$label .= "lähettäjä: $yhtiorow[nimi]\n";
			$label .= "".t("sisältö: vientitullaus/sisäkaupantilasto")."\n";
			$label .= "kieli: ASCII\n";
			$label .= "jakso: $vv$kk\n";
			$label .= "".t("koko aineiston tietuemäärä").": $lask-1\n";
			$label .= "".t("koko aineiston vienti-, verotus- tai laskutusarvo").": $arvoyht\n";

			//kirjoitetaan  faili levylle..
			$fh = fopen("/tmp/INTRASTATlabel.txt", "w+");
			fwrite($fh, $label);
			fclose($fh);

			$message = '';
			$recipient = "pgp-key Customs Finland <ascii.intra@tulli.fi>";
			$message = $label;
			require("inc/gpg.inc");
			$label = $encrypted_message;

			//PGP-encryptaus atktietue
			$message = '';
			$recipient = "pgp-key Customs Finland <ascii.intra@tulli.fi>";
			$message = $lah.$ots.$nim.$sum;
			require("inc/gpg.inc");

			$atk = $encrypted_message;

			//kirjoitetaan  faili levylle..
			$fh = fopen("/tmp/INTRASTATtietueet.pgp", "w+");
			fwrite($fh, $atk);
			fclose($fh);

			$bound = uniqid(time()."_") ;

			$header  = "From: <juppe@arwidson.fi>\r\n";
			$header .= "MIME-Version: 1.0\r\n" ;
			$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

			$content = "--$bound\r\n";

			$content .= "Content-Type: application/pgp-encrypted;\r\n" ;
			$content .= "Content-Transfer-Encoding: base64\r\n" ;
			$content .= "Content-Disposition: attachment; filename=\"otsikko.pgp\"\r\n\r\n";
			$content .= chunk_split(base64_encode($label));
			$content .= "\r\n";

			$content .= "--$bound\r\n";

			$content .= "Content-Type: application/pgp-encrypted;\r\n" ;
			$content .= "Content-Transfer-Encoding: base64\r\n" ;
			$content .= "Content-Disposition: attachment; filename=\"tietue.pgp\"\r\n\r\n";
			$content .= chunk_split(base64_encode($atk));
			$content .= "\r\n" ;

			$content .= "--$bound\r\n";

			$to = 'ascii.intrastat@tulli.fi';
			mail($to, "", $content, $header, "-f juppe@arwidson.fi");

			$to = $yhtiorow["admin_email"];
			mail($to, "", $content, $header);

			echo "<font class='message'>".t("Lähetys onnistui")."!</font><br>";
		}
		else {
			echo "<font class='error'>".t("Lähetys epäonnistui")."! ".t("Korjaa virheesi")."!</font><br><br>";
		}

		// echotaan taulukko ruudulle
		echo "$ulos<br>";
	}

	//Käyttöliittymä
	echo "<br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	if (!isset($kk)) $kk = date(m);
	if (!isset($vv)) $vv = date(Y);

	echo "<input type='hidden' name='tee' value='tulosta'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<tr><th>".t("Syötä päivämäärä (kk-vvvv)")."</th>
			<td><input type='text' name='kk' value='$kk' size='3'></td>
			<td><input type='text' name='vv' value='$vv' size='5'></td>";
	echo "<td class='back'><input type='submit' value='".t("Lähetä tulliin")."'></td></tr></table>";


	require("inc/footer.inc");
?>