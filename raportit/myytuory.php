<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Myynnit tuoteryhmittäin")."</font><hr>";

	flush();

	if ($tee != '') {
		$query = "	SELECT distinct osasto, try
					FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
					WHERE yhtio='$kukarow[yhtio]' and tyyppi='L' and
					laskutettuaika >= date_sub('$vv-$kk-$pp',interval 12 month) and
					laskutettuaika <= '$vv-$kk-$pp'
					ORDER BY osasto, try";
		$result = mysql_query($query) or pupe_error($query);

		$kate30=0;
		$kate90=0;
		$kateVA=0;
		$myyn30=0;
		$myyn90=0;
		$myynVA=0;
		$varTOT=0;

		$yhtkate30=0;
		$yhtkate90=0;
		$yhtkateVA=0;
		$yhtmyyn30=0;
		$yhtmyyn90=0;
		$yhtmyynVA=0;
		$yhtvarTOT=0;

		$ulos  = "".t("Myynnit tuoteryhmittäin").": $pp-$kk-$vv\r\n\r\n";
		$ulos .= "".t("Os")."\t";
		$ulos .= "".t("Try")."\t";
		$ulos .= "".t("Myyn 30pv")."\t";
		$ulos .= "".t("Kate 30pv")."\t";
		$ulos .= "".t("K% 30pv")."\t";
		$ulos .= "".t("Kpl 30pv")."\t";
		$ulos .= "".t("Myyn 90pv")."\t";
		$ulos .= "".t("Kate 90pv")."\t";
		$ulos .= "".t("K% 90pv")."\t";
		$ulos .= "".t("Kpl 90pv")."\t";
		$ulos .= "".t("Myyn VA")."\t";
		$ulos .= "".t("Kate VA")."\t";
		$ulos .= "".t("K% VA")."\t";
		$ulos .= "".t("Kpl VA")."\t";
		$ulos .= "".t("Varasto (arvio)")."\t";
		$ulos .= "".t("Kierto")."\t";
		$ulos .= "\r\n";

		$edosasto = '';
		$lask = 0;

		while ($trow = mysql_fetch_array($result)) {

			$query = "	SELECT
						osasto,
						try,
						sum(if(tilausrivi.laskutettuaika >= date_sub('$vv-$kk-$pp',interval 30 day), rivihinta,0)) summa30,
						sum(if(tilausrivi.laskutettuaika >= date_sub('$vv-$kk-$pp',interval 30 day), kate,0)) kate30,
						sum(if(tilausrivi.laskutettuaika >= date_sub('$vv-$kk-$pp',interval 30 day), kpl,0)) kpl30,
						sum(if(tilausrivi.laskutettuaika >= date_sub('$vv-$kk-$pp',interval 90 day), rivihinta,0)) summa90,
						sum(if(tilausrivi.laskutettuaika >= date_sub('$vv-$kk-$pp',interval 90 day), kate,0)) kate90,
						sum(if(tilausrivi.laskutettuaika >= date_sub('$vv-$kk-$pp',interval 90 day), kpl,0)) kpl90,
						sum(if(tilausrivi.laskutettuaika >= date_sub('$vv-$kk-$pp',interval 12 month), rivihinta-kate,0)) myynti12,
						sum(if(tilausrivi.laskutettuaika >= '$vv-01-01', rivihinta,0)) summaVA,
						sum(if(tilausrivi.laskutettuaika >= '$vv-01-01', kate,0)) kateVA,
						sum(if(tilausrivi.laskutettuaika >= '$vv-01-01', kpl,0)) kplVA
						FROM tilausrivi use index (yhtio_tyyppi_osasto_try_laskutettuaika)
						WHERE yhtio='$kukarow[yhtio]' and tyyppi='L' and osasto='$trow[osasto]' and try='$trow[try]'
						and laskutettuaika >= date_sub('$vv-$kk-$pp',interval 12 month) and laskutettuaika <= '$vv-$kk-$pp'
						group by 1,2";

			$eresult = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($eresult);

			//varastonmuutos
			$muutosval = 0;
			$query = "	SELECT sum(kpl*hinta) muutos
						FROM tuote use index (osasto_try_index), tapahtuma use index (yhtio_tuote_laadittu)
						WHERE tuote.yhtio='$kukarow[yhtio]' and
						tuote.osasto='$trow[osasto]' and
						tuote.try='$trow[try]' and
						tuote.ei_saldoa='' and
						tapahtuma.yhtio=tuote.yhtio and
						tapahtuma.tuoteno=tuote.tuoteno and
						tapahtuma.laadittu > '$vv-$kk-$pp 23:59:59'";
			$result5 = mysql_query($query) or pupe_error($query);
			$rowMUUTOS = mysql_fetch_array($result5);
			$muutosval = $rowMUUTOS["muutos"];

			//varaston arvo
			$varastonarvo = 0;
			$query = "	SELECT sum(tuotepaikat.saldo*if(epakurantti1pvm='0000-00-00', kehahin, kehahin/2)) varasto
						FROM tuotepaikat, tuote
						WHERE tuote.tuoteno 	  = tuotepaikat.tuoteno
						and tuote.yhtio 		  = '$kukarow[yhtio]'
						and tuotepaikat.yhtio 	  = '$kukarow[yhtio]'
						and tuote.osasto 		  = '$trow[osasto]'
						and tuote.try 			  = '$trow[try]'
						and tuote.ei_saldoa 	  = ''
						and tuote.epakurantti2pvm = '0000-00-00'";
			$result4 = mysql_query($query) or pupe_error($query);
			$rowARVO = mysql_fetch_array($result4);
			$varastonarvo = round($rowARVO["varasto"] - $muutosval, 2);

			if ($varastonarvo <> 0)
				$kierto = round($row['myynti12'] / $varastonarvo, 2);
			else
				$kierto = 0;

			if (($trow["osasto"] != $edosasto) and ($lask > 0)) {
				if (($varTOT == 0 and $myynVA == 0) or $yht == 1) {
					//ei nayteta mitaan, en osaa koodata nyt maanantaina kauniimmin....
				}
				else {

					$kate30pros="0.00";
					$kate90pros="0.00";
					$kateVApros="0.00";

					if ($myyn30 > 0)
						$kate30pros = sprintf("%.02f",round($kate30/$myyn30*100,2));

					if ($myyn90 > 0)
						$kate90pros = sprintf("%.02f",round($kate90/$myyn90*100,2));

					if ($myynVA > 0)
						$kateVApros = sprintf("%.02f",round($kateVA/$myynVA*100,2));


					$ulos .= "".t("Osasto")." $edosasto ".t("yhteensä").":\t\t";
					$ulos .= "$myyn30\t";
					$ulos .= "$kate30\t";
					$ulos .= "$kate30pros\t";
					$ulos .= "$kpl30\t";
					$ulos .= "$myyn90\t";
					$ulos .= "$kate90\t";
					$ulos .= "$kate90pros\t";
					$ulos .= "$kpl90\t";
					$ulos .= "$myynVA\t";
					$ulos .= "$kateVA\t";
					$ulos .= "$kateVApros\t";
					$ulos .= "$kplVA\t";
					$ulos .= "$varTOT\t";
					$ulos .= "\r\n";

					$ulos .= "\r\n";
				}
				$kate30=0;
				$kate90=0;
				$kateVA=0;
				$myyn30=0;
				$myyn90=0;
				$myynVA=0;
				$kpl30=0;
				$kpl90=0;
				$kplVA=0;
				$varTOT=0;
			}

			$kate30+=$row["kate30"];
			$kate90+=$row["kate90"];
			$kateVA+=$row["kateVA"];
			$myyn30+=$row["summa30"];
			$myyn90+=$row["summa90"];
			$myynVA+=$row["summaVA"];
			$kpl30 +=$row["kpl30"];
			$kpl90 +=$row["kpl90"];
			$kplVA +=$row["kplVA"];
			$varTOT+=$varastonarvo;

			$yhtkate30+=$row["kate30"];
			$yhtkate90+=$row["kate90"];
			$yhtkateVA+=$row["kateVA"];
			$yhtmyyn30+=$row["summa30"];
			$yhtmyyn90+=$row["summa90"];
			$yhtmyynVA+=$row["summaVA"];
			$yhtkpl30 +=$row["kpl30"];
			$yhtkpl90 +=$row["kpl90"];
			$yhtkplVA +=$row["kplVA"];
			$yhtvarTOT+=$varastonarvo;


			$kate30pros="0.00";
			$kate90pros="0.00";
			$kateVApros="0.00";

			if ($row["summa30"] > 0)
				$kate30pros = sprintf("%.02f",round($row["kate30"]/$row["summa30"]*100,2));

			if ($row["summa90"] > 0)
				$kate90pros = sprintf("%.02f",round($row["kate90"]/$row["summa90"]*100,2));

			if ($row["summaVA"] > 0)
				$kateVApros = sprintf("%.02f",round($row["kateVA"]/$row["summaVA"]*100,2));

			if ($varastonarvo == 0 and $row["summaVA"] == 0) {
				//ei nayteta mitaan, en osaa koodata nyt maanantaina kauniimmin....
			}
			else {
				$ulos .= "$trow[osasto]\t$trow[try]\t";
				$ulos .= "$row[summa30]\t";
				$ulos .= "$row[kate30]\t";
				$ulos .= "$kate30pros\t";
				$ulos .= "$row[kpl30]\t";
				$ulos .= "$row[summa90]\t";
				$ulos .= "$row[kate90]\t";
				$ulos .= "$kate90pros\t";
				$ulos .= "$row[kpl90]\t";
				$ulos .= "$row[summaVA]\t";
				$ulos .= "$row[kateVA]\t";
				$ulos .= "$kateVApros\t";
				$ulos .= "$row[kplVA]\t";
				$ulos .= "$varastonarvo\t";
				$ulos .= "$kierto\t";
				$ulos .= "\r\n";
			}

			$lask++;
			$edosasto = $trow["osasto"];
		}

		if (($varTOT == 0 and $myynVA == 0) or $yht == 1) {
			//ei nayteta mitaan, en osaa koodata nyt maanantaina kauniimmin....
		}
		else {
			$kate30pros="0.00";
			$kate90pros="0.00";
			$kateVApros="0.00";

			if ($myyn30 > 0)
				$kate30pros = sprintf("%.02f",round($kate30/$myyn30*100,2));

			if ($myyn90 > 0)
				$kate90pros = sprintf("%.02f",round($kate90/$myyn90*100,2));

			if ($myynVA > 0)
				$kateVApros = sprintf("%.02f",round($kateVA/$myynVA*100,2));

			$ulos .= "".t("Osasto")." $edosasto ".t("yhteensä").":\t\t";
			$ulos .= "$myyn30\t";
			$ulos .= "$kate30\t";
			$ulos .= "$kate30pros\t";
			$ulos .= "$kpl30\t";
			$ulos .= "$myyn90\t";
			$ulos .= "$kate90\t";
			$ulos .= "$kate90pros\t";
			$ulos .= "$kpl90\t";
			$ulos .= "$myynVA\t";
			$ulos .= "$kateVA\t";
			$ulos .= "$kateVApros\t";
			$ulos .= "$kplVA\t";
			$ulos .= "$varTOT\t";
			$ulos .= "\r\n";
			$ulos .= "\r\n";

			if ($yhtmyyn30 > 0)
				$yhtkate30pros = sprintf("%.02f",round($yhtkate30/$yhtmyyn30*100,2));

			if ($yhtmyyn90 > 0)
				$yhtkate90pros = sprintf("%.02f",round($yhtkate90/$yhtmyyn90*100,2));

			if ($yhtmyynVA > 0)
				$yhtkateVApros = sprintf("%.02f",round($yhtkateVA/$yhtmyynVA*100,2));


			///* Kaikkiyhteensä *///
			$ulos .= "".t("Kaikki yhteensä").":\t\t";
			$ulos .= "$yhtmyyn30\t";
			$ulos .= "$yhtkate30\t";
			$ulos .= "$yhtkate30pros\t";
			$ulos .= "$kpl30\t";
			$ulos .= "$yhtmyyn90\t";
			$ulos .= "$yhtkate90\t";
			$ulos .= "$yhtkate90pros\t";
			$ulos .= "$kpl90\t";
			$ulos .= "$yhtmyynVA\t";
			$ulos .= "$yhtkateVA\t";
			$ulos .= "$yhtkateVApros\t";
			$ulos .= "$kplVA\t";
			$ulos .= "$yhtvarTOT\t";
			$ulos .= "\r\n";




		}

		$bound = uniqid(time()."_") ;

		$header  = "From: <$yhtiorow[postittaja_email]>\n";
		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

		$content = "--$bound\n";

		$content .= "Content-Type: application/vnd.ms-excel; name=\"Excel-".t("mytuory")."-$kukarow[yhtio].xls\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: attachment; filename=\"Excel-".t("mytuory")."-$kukarow[yhtio].xls\"\n\n";


		$content .= chunk_split(base64_encode(str_replace('.',',',$ulos)));
		$content .= "\n" ;

		$content .= "--$bound\n";

		$content .= "Content-Type: text/x-comma-separated-values; name=\"OpenOffice-".t("mytuory")."-$kukarow[yhtio].csv\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: attachment; filename=\"OpenOffice-".t("mytuory")."-$kukarow[yhtio].csv\"\n\n";


		$content .= chunk_split(base64_encode($ulos));
		$content .= "\n" ;

		$content .= "--$bound\n";

		$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - ".t("Myynnit tuoteryhmittäin raportti")."", $content, $header, "-f $yhtiorow[postittaja_email]");

		if ($boob===FALSE) echo " - ".t("Email lähetys epäonnistui")."!<br>";
		else echo "<font class='message'>".t("Lähetettiin osoitteeseen").": $kukarow[eposti].</font><br>";
	}


	//Käyttöliittymä
	echo "<br>";
	echo "<font class='message'>".t("Raportti lähetetään sähköpostiisi.")."</font><br><br>";

	echo "<table><form method='post' action='$PHP_SELF'>";

	if (!isset($kk))
		$kk = date(m);
	if (!isset($vv))
		$vv = date(Y);
	if (!isset($pp))
		$pp = date(d);

	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<tr><th>".t("Syötä päivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='pp' value='$pp' size='3'></td>
			<td><input type='text' name='kk' value='$kk' size='3'></td>
			<td><input type='text' name='vv' value='$vv' size='5'></td>";

	if ($yht == 1) $chk2 = "CHECKED";

	echo "<tr><th>".t("Piilota yhteensärivit")."</th>
			<td colspan='3'><input type='checkbox' name='yht' value='1' $chk2></td>";

	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";

	require ("../inc/footer.inc");
?>
