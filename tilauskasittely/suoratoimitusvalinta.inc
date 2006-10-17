<?php

	//Tarvitsemme parametrinä
	//$suora_tuoteno jossa on käsiteltävä tuotenumero
	//$suora_kpl jossa on tilattava määrä

	$suoratoim_array = array();

	// Katsotaan onko tälle tuotteelle yhtään sisäistä toimittajaa
	// ja että toimittajan tiedoissa on varmasti kaikki EDI mokkulat päällä
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

	// Kääydään läpi suoratoimittajan paikat
	while ($superrow = mysql_fetch_array($superjtres)) {

		$query = "	select yhtio.nimi, yhtio.yhtio, yhtio.tunnus, varastopaikat.tunnus, varastopaikat.nimitys, hyllyalue, hyllynro, hyllyvali, hyllytaso, 
					if(varastopaikat.maa = '$yhtiorow[maakoodi]', 1,2) as sorttaus, sum(saldo) saldo, varastopaikat.maa varastomaa
					from tuotepaikat
					join yhtio on yhtio.yhtio=tuotepaikat.yhtio
					join varastopaikat on tuotepaikat.yhtio = varastopaikat.yhtio
					and concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0')) >= concat(rpad(upper(alkuhyllyalue) ,3,'0'),lpad(alkuhyllynro ,2,'0'))
					and concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0')) <= concat(rpad(upper(loppuhyllyalue) ,3,'0'),lpad(loppuhyllynro ,2,'0'))
					where tuotepaikat.yhtio 	= '$superrow[tyyppi_tieto]'
					and tuoteno 				= '$superrow[toim_tuoteno]'
					and varastopaikat.tyyppi 	= ''
					group by 1,2,3,4
					order by sorttaus, varastopaikat.nimitys";
		$kres2  = mysql_query($query) or pupe_error($query);
		
		while ($krow  = mysql_fetch_array($kres2)) {

			// katotaan ennakkopoistot toimittavalta yritykseltä
			$query = "	select sum(varattu) varattu
						from tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
						where yhtio = '$krow[yhtio]'
						and tyyppi  = 'L'
						and varattu > 0
						and tuoteno = '$superrow[toim_tuoteno]'";
			$krtre = mysql_query($query) or pupe_error($query);
			$krtur = mysql_fetch_array($krtre);

			// sitten katotaan ollaanko me jo varattu niitä JT rivejä toimittajalta
			$query ="	select sum(jt) varattu
						from tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
						where yhtio			= '$kukarow[yhtio]'
						and tyyppi			= 'L'
						and laskutettuaika	= '0000-00-00'
						and tuoteno			= '$superrow[toim_tuoteno]'
						and tilaajanrivinro	= '$superrow[liitostunnus]'
						and hyllyalue 		= '$krow[hyllyalue]'
						and hyllynro 		= '$krow[hyllynro]'
						and hyllyvali 		= '$krow[hyllyvali]'
						and hyllytaso 		= '$krow[hyllytaso]'";
			$krtre = mysql_query($query) or pupe_error($query);
			$krtu2 = mysql_fetch_array($krtre);

			// katotaan tuotteen saldo
			$saldo = sprintf("%.02f",$krow["saldo"] - $krtur["varattu"] - $krtu2["varattu"]);

			// näytetään drop-downi vain jos on tarpeeksi saldoa..
			if ($saldo > 0 or ($row["tilaajanrivinro"] == $superrow["liitostunnus"] and $krow["hyllyalue"] == $row["hyllyalue"] and $krow["hyllynro"] == $row["hyllynro"] and $krow["hyllyvali"] == $row["hyllyvali"] and $krow["hyllytaso"] == $row["hyllytaso"])) {
				$sel = "";

				if ($row["tilaajanrivinro"] == $superrow["liitostunnus"] and $krow["hyllyalue"] == $row["hyllyalue"] and $krow["hyllynro"] == $row["hyllynro"] and $krow["hyllyvali"] == $row["hyllyvali"] and $krow["hyllytaso"] == $row["hyllytaso"]) {
					$sel = "SELECTED";
				}

				$lisa = "";
				if ($saldo < $suora_kpl) {
					$lisa = "&&&$saldo";
				}

				$varastomaa = '';
				if (strtoupper($krow['varastomaa']) != strtoupper($yhtiorow['maakoodi'])) {
					$varastomaa = strtoupper($krow['varastomaa']);
				}

				// tallennetaan riville toimittajan tunnus
				$paikat .= "<option value='@@@$superrow[liitostunnus]#$krow[hyllyalue]#$krow[hyllynro]#$krow[hyllyvali]#$krow[hyllytaso]$lisa' $sel>$varastomaa $krow[nimi] - $krow[nimitys] ($saldo)</option>";
				$paikatlask=$paikatlask+2;

				$suoratoim_array[] = "@@@$superrow[liitostunnus]#$krow[hyllyalue]#$krow[hyllynro]#$krow[hyllyvali]#$krow[hyllytaso]$lisa";
			}
		}
	}
?>