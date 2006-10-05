<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Piirin myynnit asiakkaittain")."</font><hr>";

	if ($tee != '') {
		$where1 = '';

		if ($piiri != '') {
			$piirit = split(" ",$piiri);

			for($i = 0; $i < sizeof($piirit); $i++) {
				$piirit[$i] = trim($piirit[$i]);

				if ($piirit[$i] != '') {
					if (strpos($piirit[$i],"-")) {

						$piirit2 = split("-",$piirit[$i]);

						for($ia = $piirit2[0]; $ia<= $piirit2[1]; $ia++) {
							$where1 .= "'".$ia."',";
						}
					}
					else {
						$where1 .= "'".$piirit[$i]."',";
					}
				}
			}
			$where1 = substr($where1,0,-1);
			$where1 = " asiakas.piiri in (".$where1.") and ";
		}

		//edellinen vuosi
		$vvaa = $vva - '1';
		$vvll = $vvl - '1';

		$query = "	SELECT lasku.ytunnus, asiakas.nimi, asiakas.nimitark, asiakas.piiri,
					sum(if(lasku.tapvm >= '$vvaa-$kka-$ppa' and lasku.tapvm <= '$vvll-$kkl-$ppl', lasku.kate,0)) kateedyht,
					sum(if(lasku.tapvm >= '$vvaa-$kka-$ppa' and lasku.tapvm <= '$vvll-$kkl-$ppl', lasku.arvo,0)) myyntiedyht,
					sum(if(lasku.tapvm >= '$vva-$kka-$ppa'  and lasku.tapvm <= '$vvl-$kkl-$ppl',  lasku.kate,0)) katecuryht,
					sum(if(lasku.tapvm >= '$vva-$kka-$ppa'  and lasku.tapvm <= '$vvl-$kkl-$ppl',  lasku.arvo,0)) myynticuryht
					FROM asiakas, lasku use index (yhtio_tila_liitostunnus_tapvm)
					WHERE $where1
					asiakas.yhtio='$kukarow[yhtio]'
					and lasku.yhtio=asiakas.yhtio
					and lasku.liitostunnus=asiakas.tunnus
					and lasku.tila='U'
					and lasku.alatila='X'
					and ((lasku.tapvm >= '$vvaa-$kka-$ppa' and lasku.tapvm <= '$vvll-$kkl-$ppl') or (lasku.tapvm >= '$vva-$kka-$ppa' and lasku.tapvm <= '$vvl-$kkl-$ppl'))
					GROUP BY lasku.ytunnus, piiri
					ORDER BY asiakas.piiri, lasku.ytunnus";
		$result = mysql_query($query) or pupe_error($query);

		$rivi = '';
		$rivi .= "".t("Piiri")."\t";
		$rivi .= "".t("Ytunnus")."\t";
		$rivi .= "".t("Nimi")."\t";
		$rivi .= "".t("Nimitark")."\t";

		$rivi .= "".t("Myynti nyt")." ($ppa.$kka.$vva - $ppl.$kkl.$vvl)\t";
		$rivi .= "".t("Myynti ed")."  ($ppa.$kka.$vvaa - $ppl.$kkl.$vvll)\t";
		$rivi .= "".t("Indeksi Myynti")."\t";

		$rivi .= "".t("Kate nyt")." ($ppa.$kka.$vva - $ppl.$kkl.$vvl)\t";
		$rivi .= "".t("Kate ed")."  ($ppa.$kka.$vvaa - $ppl.$kkl.$vvll)\t";
		$rivi .= "".t("Indeksi Kate")."\r\n";

		$lask = 0;
		$yhtcur			= 0;
		$yhted			= 0;
		$yhtkatecur		= 0;
		$yhtkateed		= 0;

		$kaikkiyhtcur		= 0;
		$kaikkiyhted		= 0;
		$kaikkiyhtkatecur	= 0;
		$kaikkiyhtkateed	= 0;

		while ($lrow = mysql_fetch_array($result)) {
			if ($lrow["piiri"] != $edpiiri && $lask > 0) {

				///* Edellinen piiri yhteensä *///
				$rivi .= $edpiiri."\t";
				$rivi .= ""."\t";
				$rivi .= "".t("Yhteensä").""."\t";
				$rivi .= ""."\t";

				$rivi .= $yhtcur."\t";
				$rivi .= $yhted."\t";;

				$yhtindarvo30 = 0;
				if ($yhted != 0) {
					$yhtindarvo30 = round( $yhtcur / $yhted, 1);
				}
				$rivi .= $yhtindarvo30."\t";

				$rivi .= $yhtkatecur."\t";
				$rivi .= $yhtkateed."\t";

				$yhtindarvoVA = 0;
				if ($yhtkateed != 0) {
					$yhtindarvoVA = round( $yhtkatecur / $yhtkateed, 1);
				}
				$rivi .= $yhtindarvoVA."\r\n\r\n";


				$edpiiri		= '';
				$yhtcur			= 0;
				$yhted			= 0;
				$yhtkatecur		= 0;
				$yhtkateed		= 0;

			}

			$rivi .= $lrow["piiri"]."\t";
			$rivi .= $lrow["ytunnus"]."\t";
 			$rivi .= $lrow["nimi"]."\t";
 			$rivi .= $lrow["nimitark"]."\t";


			$rivi .= $lrow["myynticuryht"]."\t";
			$rivi .= $lrow["myyntiedyht"]."\t";

			$indarvo30 = 0;
			if ($lrow["myyntiedyht"] != 0) {
				$indarvo30 = round( $lrow["myynticuryht"] / $lrow["myyntiedyht"], 1);
			}
			$rivi .= $indarvo30."\t";



			$rivi .= $lrow["katecuryht"]."\t";
			$rivi .= $lrow["kateedyht"]."\t";

			$indarvoVA = 0;
			if ($lrow["kateedyht"] != 0) {
				$indarvoVA = round( $lrow["katecuryht"] / $lrow["kateedyht"], 1);
			}
			$rivi .= $indarvoVA."\r\n";

			//Summat
			$edpiiri		 = $lrow["piiri"];

			$yhtcur			+= $lrow["myynticuryht"];
			$yhted			+= $lrow["myyntiedyht"];
			$yhtkatecur		+= $lrow["katecuryht"];
			$yhtkateed		+= $lrow["kateedyht"];

			$kaikkiyhtcur		+= $lrow["myynticuryht"];
			$kaikkiyhted		+= $lrow["myyntiedyht"];
			$kaikkiyhtkatecur	+= $lrow["katecuryht"];
			$kaikkiyhtkateed	+= $lrow["kateedyht"];

			$lask++;
		}

		///* Vika piiri yhteensä *///
		$rivi .= $edpiiri."\t";
		$rivi .= ""."\t";
		$rivi .= "".t("Yhteensä").""."\t";
		$rivi .= ""."\t";

		$rivi .= $yhtcur."\t";
		$rivi .= $yhted."\t";;

		$yhtindarvo30 = 0;
		if ($yhted != 0) {
			$yhtindarvo30 = round( $yhtcur / $yhted * 100, 1);
		}
		$rivi .= $yhtindarvo30."\t";

		$rivi .= $yhtkatecur."\t";
		$rivi .= $yhtkateed."\t";

		$yhtindarvoVA = 0;
		if ($yhtkateed != 0) {
			$yhtindarvoVA = round( $yhtkatecur / $yhtkateed * 100, 1);
		}
		$rivi .= $yhtindarvoVA."\r\n\r\n";

		///* Kaikki piirit yhteensä *///
		$rivi .= "".t("Kaikki piirit").""."\t";
		$rivi .= ""."\t";
		$rivi .= "".t("Yhteensä").""."\t";
		$rivi .= ""."\t";

		$rivi .= $kaikkiyhtcur."\t";
		$rivi .= $kaikkiyhted."\t";;
		$Kaikkiyhtindarvo30 = 0;
		if ($kaikkiyhted != 0) {
			$Kaikkiyhtindarvo30 = round( $kaikkiyhtcur / $kaikkiyhted, 1);
		}
		$rivi .= $Kaikkiyhtindarvo30."\t";

		$rivi .= $kaikkiyhtkatecur."\t";
		$rivi .= $kaikkiyhtkateed."\t";
		$KaikkiyhtindarvoVA = 0;
		if ($kaikkiyhtkateed != 0) {
			$KaikkiyhtindarvoVA = round( $kaikkiyhtkatecur / $kaikkiyhtkateed , 1);
		}
		$rivi .= $KaikkiyhtindarvoVA."\r\n";


		$bound = uniqid(time()."_") ;

		$header  = "From: <mailer@pupesoft.com>\r\n";
		$header .= "MIME-Version: 1.0\r\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

		$content = "--$bound\r\n";

		$content .= "Content-Type: application/vnd.ms-excel; name=\"".t("Excel-raportti")."-$kukarow[yhtio].xls\"\r\n" ;
		$content .= "Content-Transfer-Encoding: base64\r\n" ;
		$content .= "Content-Disposition: attachment; filename=\"".t("Excel-raportti")."-$kukarow[yhtio].xls\"\r\n\r\n";


		$content .= chunk_split(base64_encode(str_replace('.',',',$rivi)));
		$content .= "\r\n" ;

		$content .= "--$bound\r\n";

		$content .= "Content-Type: text/x-comma-separated-values; name=\"".t("OpenOffice-raportti")."-$kukarow[yhtio].csv\"\r\n" ;
		$content .= "Content-Transfer-Encoding: base64\r\n" ;
		$content .= "Content-Disposition: attachment; filename=\"".t("OpenOffice-raportti")."-$kukarow[yhtio].csv\"\r\n\r\n";


		$content .= chunk_split(base64_encode($rivi));
		$content .= "\r\n" ;

		$content .= "--$bound\r\n";

		$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - ".t("Piirin myynnit asiakkaittain raportti")."", $content, $header);

		if ($boob===FALSE) echo "<font class='error'>".t("Email lähetys epäonnistui")."!</font><br>";
		else echo "<font class='message'>".t("Lähetettiin osoitteeseen").": $kukarow[eposti].</font><br>";

		$piiri="";
	}


	//Käyttöliittymä
	echo "<font class='message'>".t("Raportti lähetetään sähköpostiisi").".</font><br><br>";

	echo "<table><form name='piiri' method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='kaikki'>";

	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");


	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	echo "<tr><th>".t("Syötä piiri (välilyönnillä tai väliviivalla eroteltuna)").":</th>
			<td colspan='3'><input type='text' name='piiri' value='$piiri' size='15'></td></tr>
		</table>";

	echo "<br><input type='submit' value='".t("Aja raportti")."'>";
	echo "</form>";

	// kursorinohjausta
	$formi  = "piiri";
	$kentta = "piiri";

	require ("../inc/footer.inc");

?>