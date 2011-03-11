<?php

	//Tarvitsemme parametrin‰
	//$suora_tuoteno jossa on k‰sitelt‰v‰ tuotenumero
	//$suora_kpl jossa on tilattava m‰‰r‰
	//$row jossa on tilausrivin kaikki tiedot
	//$varastoista jossa vain sallitut suoratoimitusvarastot

	$suoratoim_array = array();
	$suoratoim_totaali = 0;
	$suortoimvarastolisa = "";

	if (isset($varastoista) and $varastoista != "") {
		$suortoimvarastolisa = " AND varastopaikat.tunnus IN ($varastoista)";
	}

	// Katsotaan onko t‰lle tuotteelle yht‰‰n sis‰ist‰ toimittajaa
	// ja ett‰ toimittajan tiedoissa on varmasti kaikki EDI mokkulat p‰‰ll‰
	// ja oletusvienti on jotain vaihto-omaisuutta
	$query = "	SELECT tyyppi_tieto, liitostunnus, toim_tuoteno
				FROM tuotteen_toimittajat use index (yhtio_tuoteno), toimi use index (PRIMARY)
				WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
				AND tuotteen_toimittajat.tuoteno = '$suora_tuoteno'
				AND toimi.yhtio         = tuotteen_toimittajat.yhtio
				AND toimi.tunnus        = tuotteen_toimittajat.liitostunnus
				AND toimi.tyyppi        = 'S'
				AND toimi.tyyppi_tieto != ''
				AND toimi.edi_palvelin != ''
				AND toimi.edi_kayttaja != ''
				AND toimi.edi_salasana != ''
				AND toimi.edi_polku    != ''
				AND toimi.oletus_vienti in ('C','F','I')";
	$superjtres  = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($superjtres) > 0) {
		$omapaikatraja = 1;
	}

	// K‰‰yd‰‰n l‰pi suoratoimittajan paikat
	while ($superrow = mysql_fetch_array($superjtres)) {
		$query = "	SELECT yhtio.nimi, yhtio.yhtio, yhtio.tunnus, varastopaikat.tunnus vartunnus, varastopaikat.nimitys, hyllyalue, hyllynro, hyllyvali, hyllytaso,
					if(varastopaikat.maa = '$yhtiorow[maa]', 1,2) as sorttaus, sum(saldo) saldo, varastopaikat.maa varastomaa
					FROM tuotepaikat use index (tuote_index)
					JOIN yhtio use index (yhtio_index) on (yhtio.yhtio=tuotepaikat.yhtio)
					JOIN varastopaikat on tuotepaikat.yhtio = varastopaikat.yhtio
					AND concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					AND concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					$suortoimvarastolisa
					WHERE tuotepaikat.yhtio 	= '$superrow[tyyppi_tieto]'
					AND tuoteno 				= '$superrow[toim_tuoteno]'
					AND varastopaikat.tyyppi 	= ''
					GROUP BY 1,2,3,4
					ORDER BY sorttaus, varastopaikat.nimitys";
		$kres2 = mysql_query($query) or pupe_error($query);

		while ($krow = mysql_fetch_array($kres2)) {

			list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($superrow['toim_tuoteno'], '', $krow['vartunnus'], $krow['yhtio']);

			// sitten katotaan ollaanko me jo varattu niit‰ JT rivej‰ toimittajalta
			$query ="	SELECT sum(jt) varattu
						FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						WHERE yhtio			= '$kukarow[yhtio]'
						AND tyyppi			= 'L'
						AND laskutettuaika	= '0000-00-00'
						AND var				= 'S'
						AND tuoteno			= '$superrow[toim_tuoteno]'
						AND tilaajanrivinro	= '$superrow[liitostunnus]'
						AND hyllyalue 		= '$krow[hyllyalue]'
						AND hyllynro 		= '$krow[hyllynro]'
						AND hyllyvali 		= '$krow[hyllyvali]'
						AND hyllytaso 		= '$krow[hyllytaso]'";
			$krtre = mysql_query($query) or pupe_error($query);
			$krtu2 = mysql_fetch_array($krtre);

			// Katotaan myyt‰viss‰ oleva m‰‰r‰
			$myytavissa = sprintf("%.02f", $myytavissa - $krtu2["varattu"]);

			// n‰ytet‰‰n drop-downi vain jos on tarpeeksi saldoa..
			if ($myytavissa > 0 or ($row["tilaajanrivinro"] == $superrow["liitostunnus"] and $krow["hyllyalue"] == $row["hyllyalue"] and $krow["hyllynro"] == $row["hyllynro"] and $krow["hyllyvali"] == $row["hyllyvali"] and $krow["hyllytaso"] == $row["hyllytaso"])) {
				$sel = "";

				if ($row["tilaajanrivinro"] == $superrow["liitostunnus"] and $krow["hyllyalue"] == $row["hyllyalue"] and $krow["hyllynro"] == $row["hyllynro"] and $krow["hyllyvali"] == $row["hyllyvali"] and $krow["hyllytaso"] == $row["hyllytaso"]) {
					$sel = "SELECTED";
				}

				$lisa = "";
				if ($myytavissa < $suora_kpl) {
					$lisa = "&&&$myytavissa";
				}

				$varastomaa = '';
				if (strtoupper($krow['varastomaa']) != strtoupper($yhtiorow['maa'])) {
					$varastomaa = strtoupper($krow['varastomaa']);
				}

				if ($mista == 'selaus') {
					if ($myytavissa < $suora_kpl) {
						$montako = $myytavissa;
					}
					else {
						$montako = $suora_kpl;
					}

					$paikat .= "<option value='@@@$superrow[liitostunnus]#$krow[hyllyalue]#$krow[hyllynro]#$krow[hyllyvali]#$krow[hyllytaso]'>$varastomaa $krow[nimi] - $krow[nimitys] ($myytavissa)</option>";
					$paikatlask = $paikatlask + 1;
					$suoratoim_totaali += $myytavissa;
				}
				else {
					// tallennetaan riville toimittajan tunnus
					$paikat .= "<option value='@@@$superrow[liitostunnus]#$krow[hyllyalue]#$krow[hyllynro]#$krow[hyllyvali]#$krow[hyllytaso]$lisa' $sel>$varastomaa $krow[nimi] - $krow[nimitys] ($myytavissa)</option>";
					$paikatlask = $paikatlask + 2;

					$suoratoim_array[] = "@@@$superrow[liitostunnus]#$krow[hyllyalue]#$krow[hyllynro]#$krow[hyllyvali]#$krow[hyllytaso]$lisa";
				}
			}
		}
	}

?>
