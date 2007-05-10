<?php

	//Tarvitsemme parametrin‰
	//$suora_tuoteno jossa on k‰sitelt‰v‰ tuotenumero
	//$suora_kpl jossa on tilattava m‰‰r‰
	//$row jossa on tilausrivin kaikki tiedot
	//$varastoista jossa vain sallitut suoratoimitusvarastot
	
	$suoratoim_array 	= array();
	$suoratoim_totaali 	= 0;
	
	if($varastoista!="") {
		$suortoimvarastolisa = " and varastopaikat.tunnus IN ($varastoista)";
	}
	
	// Katsotaan onko t‰lle tuotteelle yht‰‰n sis‰ist‰ toimittajaa
	// ja ett‰ toimittajan tiedoissa on varmasti kaikki EDI mokkulat p‰‰ll‰
	// ja oletusvienti on jotain vaihto-omaisuutta
	$query = "	select tyyppi_tieto, liitostunnus, toim_tuoteno
				from tuotteen_toimittajat, toimi
				where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
				and tuotteen_toimittajat.tuoteno = '$suora_tuoteno'
				and toimi.yhtio         = tuotteen_toimittajat.yhtio
				and toimi.tunnus        = tuotteen_toimittajat.liitostunnus
				and toimi.tyyppi        = 'S'
				and toimi.tyyppi_tieto != ''
				and toimi.edi_palvelin != ''
				and toimi.edi_kayttaja != ''
				and toimi.edi_salasana != ''
				and toimi.edi_polku    != ''
				and toimi.oletus_vienti in ('C','F','I')";
	$superjtres  = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($superjtres) > 0) {
		$omapaikatraja 	= 1;
	}

	// K‰‰yd‰‰n l‰pi suoratoimittajan paikat
	while ($superrow = mysql_fetch_array($superjtres)) {
		$query = "	select yhtio.nimi, yhtio.yhtio, yhtio.tunnus, varastopaikat.tunnus vartunnus, varastopaikat.nimitys, hyllyalue, hyllynro, hyllyvali, hyllytaso,
					if(varastopaikat.maa = '$yhtiorow[maa]', 1,2) as sorttaus, sum(saldo) saldo, varastopaikat.maa varastomaa
					from tuotepaikat
					join yhtio on yhtio.yhtio=tuotepaikat.yhtio
					join varastopaikat on tuotepaikat.yhtio = varastopaikat.yhtio
					and concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					and concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
					$suortoimvarastolisa
					where tuotepaikat.yhtio 	= '$superrow[tyyppi_tieto]'
					and tuoteno 				= '$superrow[toim_tuoteno]'
					and varastopaikat.tyyppi 	= ''
					group by 1,2,3,4
					order by sorttaus, varastopaikat.nimitys";
		$kres2  = mysql_query($query) or pupe_error($query);

		while ($krow  = mysql_fetch_array($kres2)) {

			list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($superrow['toim_tuoteno'], '', $krow['vartunnus'], $krow['yhtio']);
			
			// sitten katotaan ollaanko me jo varattu niit‰ JT rivej‰ toimittajalta
			$query ="	select sum(jt) varattu
						from tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						where yhtio			= '$kukarow[yhtio]'
						and tyyppi			= 'L'
						and laskutettuaika	= '0000-00-00'
						and var				= 'S'
						and tuoteno			= '$superrow[toim_tuoteno]'
						and tilaajanrivinro	= '$superrow[liitostunnus]'
						and hyllyalue 		= '$krow[hyllyalue]'
						and hyllynro 		= '$krow[hyllynro]'
						and hyllyvali 		= '$krow[hyllyvali]'
						and hyllytaso 		= '$krow[hyllytaso]'";
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
					$paikatlask=$paikatlask+1;
					$suoratoim_totaali+=$myytavissa;
				}
				else {
					// tallennetaan riville toimittajan tunnus
					$paikat .= "<option value='@@@$superrow[liitostunnus]#$krow[hyllyalue]#$krow[hyllynro]#$krow[hyllyvali]#$krow[hyllytaso]$lisa' $sel>$varastomaa $krow[nimi] - $krow[nimitys] ($myytavissa)</option>";
					$paikatlask=$paikatlask+2;

					$suoratoim_array[] = "@@@$superrow[liitostunnus]#$krow[hyllyalue]#$krow[hyllynro]#$krow[hyllyvali]#$krow[hyllytaso]$lisa";
				}
			}
		}
	}
?>
