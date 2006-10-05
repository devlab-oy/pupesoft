<?php
	///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Myynti asiakkaittain").":</font><hr>";

	if ($tee != '') {
		$where1 = '';
		$where2 = '';

		if ($osasto != '') {
			$osastot = split(" ",$osasto);

			for($i = 0; $i < sizeof($osastot); $i++) {
				$osastot[$i] = trim($osastot[$i]);

				if ($osastot[$i] != '') {
					if (strpos($osastot[$i],"-")) {

						$osastot2 = split("-",$osastot[$i]);

						for($ia = $osastot2[0]; $ia<= $osastot2[1]; $ia++) {
							$where1 .= "'".$ia."',";
						}
					}
					else {
						$where1 .= "'".$osastot[$i]."',";
					}
				}
			}
			$where1 = substr($where1,0,-1);
			$where1 = " tilausrivi.osasto in (".$where1.") ";
	    }

		if ($try != '') {
			$tryt = split(" ",$try);

			for($i = 0; $i < sizeof($tryt); $i++) {
				$tryt[$i] = trim($tryt[$i]);

				if ($tryt[$i] != '') {
					if (strpos($tryt[$i],"-")) {
						$tryt2 = split("-",$tryt[$i]);
						for($ia = $tryt2[0]; $ia<= $tryt2[1]; $ia++) {
							$where2 .= "'".$ia."',";
						}
					}
					else {
						$where2 .= "'".$tryt[$i]."',";
					}
				}
			}
			$where2 = substr($where2,0,-1);
			$where2 = " tilausrivi.try in (".$where2.") ";
		}

		if (strlen($where1) > 0) {
			$where = $where1." and ";
		}
		if (strlen($where2) > 0) {
			$where = $where2." and ";
		}
		if (strlen($where2) > 0 && strlen($where1) > 0) {
			$where = $where1." and ".$where2." and ";
		}

		$tila = "U";
		if ($yhtio == "artr") $tila = "L";

		$uusiotunnus = "uusiotunnus";
		if ($yhtio == "artr") $uusiotunnus = "otunnus";

		$lisa = 'tuote.aleryhma,';
		$lisa2 = ',2,3,4,5';
		$asnimi = 'lasku';
		if ($summaa != '') {
			$lisa = '';
			$lisa2 = '';
			$asnimi = 'asiakas';
		}

		$query = "	SELECT lasku.ytunnus, $asnimi.nimi, $asnimi.nimitark, asiakas.piiri, $lisa
					sum(rivihinta) summa,
					sum(tilausrivi.kate) kate,
					sum(tilausrivi.kpl) kpl
					FROM tilausrivi, lasku, asiakas, tuote
					WHERE $where
					lasku.yhtio='$kukarow[yhtio]'
					and tilausrivi.yhtio=lasku.yhtio
					and tilausrivi.yhtio=asiakas.yhtio
					and tilausrivi.yhtio=tuote.yhtio
					and lasku.liitostunnus=asiakas.tunnus
					and tilausrivi.$uusiotunnus=lasku.tunnus
					and tilausrivi.tuoteno=tuote.tuoteno
					and lasku.tila='$tila'
					and lasku.alatila='X'
					and tapvm >= '$vva-$kka-$ppa'
					and tapvm <= '$vvl-$kkl-$ppl'
					GROUP BY 1 $lisa2
					ORDER BY nimi, nimitark";
		$result = mysql_query($query) or pupe_error($query);

		$rivi = '';
		$rivi .= t("Ytunnus")."\t";
		$rivi .= t("Nimi")."\t";
		$rivi .= t("Nimitark")."\t";
		if ($summaa == '') $rivi .= t("Alennus")."\t";
		$rivi .= t("Piiri")."\t";
		$rivi .= t("Kpl")."\t";
		$rivi .= t("Summa")."\t";
		$rivi .= t("Kate")."\t";
		$rivi .= t("Katepros")."\n";

		while ($lrow = mysql_fetch_array($result)) {

			if ($summaa == '') {
				$ale = 0;
				//haetaan tuoteryhmmän alennusryhmä
				$query = "	SELECT alennus
							FROM asiakasalennus
							WHERE yhtio='$kukarow[yhtio]' and ryhma = '$lrow[aleryhma]' and ytunnus = '$lrow[ytunnus]'";
				$hresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($hresult) != 0) {
					$hrow = mysql_fetch_array ($hresult);

					if ($hrow["alennus"] > 0) {
						$ale = $hrow[0];
					}
				}
				else {
					// Pudotaan perusalennukseen
					$query = "	SELECT alennus
								FROM perusalennus
								WHERE yhtio='$kukarow[yhtio]' and ryhma = '$lrow[aleryhma]'";
					$hresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($hresult) != 0) {
						$hrow=mysql_fetch_array ($hresult);

						if ($hrow["alennus"] > 0) {
							$ale = $hrow[0];
						}
					}
				}
			}

			$katepros=0;

			if ($lrow["summa"] != 0) {
				$katepros = round($lrow["kate"]/$lrow["summa"]*100,2);
			}

			$rivi .= $lrow["ytunnus"]."\t";
			$rivi .= $lrow["nimi"]."\t";
			$rivi .= $lrow["nimitark"]."\t";
			if ($summaa == '') $rivi .= $ale."\t";
			$rivi .= $lrow["piiri"]."\t";
			$rivi .= $lrow["kpl"]."\t";
			$rivi .= $lrow["summa"]."\t";
			$rivi .= $lrow["kate"]."\t";
			$rivi .= $katepros."\n";

		}

		$bound = uniqid(time()."_") ;

		$header  = "From: <mailer@pupesoft.com>\n";
		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

		$content = "--$bound\n";

		$content .= "Content-Type: application/vnd.ms-excel; name=\"".t("Excel-raportti")."-$kukarow[yhtio].xls\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: attachment; filename=\"".t("Excel-raportti")."-$kukarow[yhtio].xls\"\n\n";


		$content .= chunk_split(base64_encode(str_replace('.',',',$rivi)));
		$content .= "\n" ;

		$content .= "--$bound\n";

		$content .= "Content-Type: text/x-comma-separated-values; name=\"".t("OpenOffice-raportti")."-$kukarow[yhtio].csv\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: attachment; filename=\"".t("OpenOffice-raportti")."-$kukarow[yhtio].csv\"\n\n";


		$content .= chunk_split(base64_encode($rivi));
		$content .= "\n" ;

		$content .= "--$bound--\n";

		$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - ".t("Myynnit asiakkaittain raportti")." $ppa-$kka-$vva $ppl-$kkl-$vvl ".t("osasto").":$osasto ".t("try").":$try", $content, $header);

		if ($boob===FALSE) echo " - ".t("Email lähetys epäonnistui")."!<br>";
		else echo "".t("Lähetettiin osoitteeseen").": $kukarow[eposti].<br>";

	}


	//Käyttöliittymä
	echo "<br>";
	echo "".t("Raportti lähetetään sähköpostiisi")."<br><br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

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

	$chk = "";
	if (trim($summaa) != '') {
		$chk = "CHECKED";
	}

	echo "<input type='hidden' name='tee' value='kaikki'>";
	echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>";

	echo "<tr><th>".t("Syötä osasto (välilyönnillä eroteltuna)").":</th>
			<td colspan='3'><input type='text' name='osasto' value='$osasto' size='15'></td></tr>
		<tr><th>".t("Syötä tuoteryhmä (välilyönnillä eroteltuna)").":</th>
			<td colspan='3'><input type='text' name='try' value='$try' size='15'></td></tr>";
	echo "<tr><th>".t("Summaa myynnit (1 asiakas/rivi)").":</th><td colspan='3'><input type='checkbox' name='summaa' $chk></td>";

	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";

	require ("../inc/footer.inc");
?>