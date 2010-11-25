<?php

	//k‰ytet‰‰n tilausriveill‰ olevia tuotteita
	$query = "	SELECT tilausrivi.*, if(varattu!=0, varattu, kpl) varattu,
				trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka,
				tuote.ei_saldoa, tuote.kehahin, tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm,
				tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso
				FROM tilausrivi
				JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.otunnus = '$tilrivirow[otunnus]'
				and tilausrivi.perheid = '$tilrivirow[perheid]'
				and tilausrivi.tyyppi  = 'V'
				ORDER by tilausrivi.tuoteno";
	$perheresult = mysql_query($query) or pupe_error($query);

	$uusiarvo = 0;

	while ($perherow = mysql_fetch_array($perheresult)) {

		// Perutaan t‰m‰ valmistusrivi kokonaan
		if ($perutaan == "JOO") {
			$perherow["varattu"] = 0;
		}

		// Katsotaan mik‰ tuotteen keskihankintahinta oli valmistusta tehdess‰
		$query = "	SELECT *
					FROM tapahtuma
					WHERE yhtio 	= '$kukarow[yhtio]'
					and laji 		= 'kulutus'
					and rivitunnus	= '$perherow[tunnus]'
					LIMIT 1";
		$tuoteresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tuoteresult) == 0) {
			// Raaka-aine list‰tty j‰lkik‰teen tehd‰‰n tapahtuma
			if 		($perherow['epakurantti100pvm'] != '0000-00-00') $perherow['kehahin'] = 0;
			elseif 	($perherow['epakurantti75pvm'] != '0000-00-00')  $perherow['kehahin'] = round($perherow['kehahin'] * 0.25, 6);
			elseif 	($perherow['epakurantti50pvm'] != '0000-00-00')  $perherow['kehahin'] = round($perherow['kehahin'] * 0.5,  6);
			elseif 	($perherow['epakurantti25pvm'] != '0000-00-00')  $perherow['kehahin'] = round($perherow['kehahin'] * 0.75, 6);

			// Kpl nolla koska homma korjataan $kaantopisteen_saldomuutos-homman kautta
			$query = "	INSERT into tapahtuma set
						yhtio    	= '$kukarow[yhtio]',
						tuoteno  	= '$perherow[tuoteno]',
						laji     	= 'kulutus',
						kpl      	=  0,
						kplhinta 	= '$perherow[kehahin]',
						hinta    	= '$perherow[kehahin]',
						rivitunnus	= '$perherow[tunnus]',
						hyllyalue	= '$perherow[hyllyalue]',
						hyllynro	= '$perherow[hyllynro]',
						hyllyvali	= '$perherow[hyllyvali]',
						hyllytaso	= '$perherow[hyllytaso]',
						selite   	= '".t("K‰ytettiin tuotteen")." $tilrivirow[tuoteno] ".t("valmistamiseen")." ".t("tyˆm‰‰r‰yksell‰")." $tilrivirow[otunnus]',
						laatija  	= '$kukarow[kuka]',
						laadittu 	= now()";
			$tapahtumaresult = mysql_query($query) or pupe_error($query);
			$tapahtumaid = mysql_insert_id();

			$query = "	SELECT *
						FROM tapahtuma
						WHERE yhtio = '$kukarow[yhtio]'
						and laji 	= 'kulutus'
						and tunnus	= '$tapahtumaid'";
			$tuoteresult = mysql_query($query) or pupe_error($query);
		}
		$tuoterow = mysql_fetch_array($tuoteresult);

		$arvo = $tuoterow['hinta'] * $perherow['varattu'];
		$uusiarvo += $arvo;

		//Tutkitan onko raaka-aineiden k‰yttˆm‰‰ri‰ muutettu
		if ($perherow["kpl"] != $perherow["varattu"]) {
			echo "<br><font class='message'>Raaka aineen $perherow[tuoteno] kulutusta muutettu! $perherow[kpl] --> $perherow[varattu]. Teemme j‰lkilaskennan raaka-aineelle.</font><br>";

			//Suoritetaan j‰lkilaskenta
			$query = "	SELECT *
						from tapahtuma
						where yhtio='$kukarow[yhtio]' and laji='kulutus' and rivitunnus='$perherow[tunnus]'
						order by tunnus";
			$res = mysql_query($query) or pupe_error($query);
			$osavalm_lukumaara = mysql_num_rows($res);

			//Lasketaan k‰‰ntˆpisteen saldomuutos joka johtuu raaka-aineen kulutuksen muutoksesta
			$kaantopisteen_saldomuutos = round(($perherow["kpl"] - $perherow["varattu"]) / $osavalm_lukumaara, 2);

			while ($taparow = mysql_fetch_array($res)) {

				//Etsit‰‰n k‰‰ntˆpiste
				$query = "	SELECT *
							FROM tapahtuma
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$taparow[tuoteno]'
							and laji in ('tulo','valmistus')
							and laadittu >= '$taparow[laadittu]'
							LIMIT 1";
				$kaantopisteres = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($kaantopisteres) > 0) {
					$kaantopisterow = mysql_fetch_array($kaantopisteres);

					$uusihinta 		 = $kaantopisterow["kplhinta"];
					$tuoteno		 = $kaantopisterow["tuoteno"];
					$pvm			 = $kaantopisterow["laadittu"];
					$rivitunnus 	 = $kaantopisterow["rivitunnus"];
					$tapahtumatunnus = $kaantopisterow["tunnus"];

					echo "<font class='message'>Korjataan raaka-aineen $tuoteno tapahtumat $pvm ($rivitunnus) l‰htien. Raaka-aineen kulutusmuutos: $kaantopisteen_saldomuutos.</font><br>";

					jalkilaskentafunktio($tuoteno, $pvm, $uusihinta, $rivitunnus, $tapahtumatunnus, $kaantopisteen_saldomuutos);
				}
				else {
					echo "<font class='message'>Raaka-aine ei vaatinut j‰lkilaskentaa. Raaka-aineen kulutusmuutos: $kaantopisteen_saldomuutos.</font><br>";
				}

				//P‰ivitet‰‰n myˆs tapahtuman kappalem‰‰r‰
				$query = "	UPDATE tapahtuma
							SET kpl		= kpl+round($kaantopisteen_saldomuutos,2)
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus 	= '$taparow[tunnus]'";
				$updresult = mysql_query($query) or pupe_error($query);
			}


			//P‰ivitet‰‰n saldo oikeaksi vasta j‰lkilaskennan j‰lkeen kun valmistusmuutos otetaan huomioon
			//Pistet‰‰n saldo aina oletuspaikalle
			$mtos = $perherow["kpl"] - $perherow["varattu"];

			$query = "	UPDATE tuotepaikat
						SET saldo=saldo+$mtos
						WHERE yhtio = '$kukarow[yhtio]'
						and tuoteno = '$perherow[tuoteno]'
						and oletus != ''
						LIMIT 1";
			$result = mysql_query($query) or pupe_error($query);

			if ($perutaan == "JOO") {
				$query = "	UPDATE tilausrivi
							SET varattu=kpl, kpl=0, toimitettuaika='0000-00-00 00:00:00'
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$perherow[tunnus]'";
			}
			else {
				$query = "	UPDATE tilausrivi
							SET kpl=$perherow[varattu], varattu=0
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus = '$perherow[tunnus]'";
			}

			$updresult = mysql_query($query) or pupe_error($query);
		}
	}

	if ($perutaan == "JOO") {
		//P‰ivitet‰‰n myˆs tapahtuman kappalem‰‰r‰
		$query = "	UPDATE tapahtuma
					SET kpl	= 0,
					selite  = concat(selite,' Valmistus peruttu')
					WHERE yhtio 	= '$kukarow[yhtio]'
					and rivitunnus 	= '$tilrivirow[tunnus]'";
		$updresult = mysql_query($query) or pupe_error($query);

		$query = "	UPDATE tuotepaikat
					SET saldo=saldo-$valmkpl
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tilrivirow[tuoteno]'
					and oletus != ''
					LIMIT 1";
		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='message'>".t("Valmisteen valmistusmuutos").": $valmkpl.</font><br>";

		$query = "	SELECT sum(kpl) valmistetut
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilrivirow[otunnus]'
					and perheid = '$tilrivirow[perheid]'
					and tuoteno = '$tilrivirow[tuoteno]'
					and tyyppi = 'D'";
		$resisa = mysql_query($query) or pupe_error($query);
		$rowisa = mysql_fetch_assoc($resisa);

		$query = "	UPDATE tilausrivi
					SET varattu = '$rowisa[valmistetut]', kpl=0, toimitettuaika='0000-00-00 00:00:00'
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilrivirow[otunnus]'
					and tunnus  = '$tilrivirow[tunnus]'";
		$updresult = mysql_query($query) or pupe_error($query);

		$query = "	UPDATE tilausrivi
					SET kpl=0, varattu=0
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilrivirow[otunnus]'
					and perheid = '$tilrivirow[perheid]'
					and tuoteno = '$tilrivirow[tuoteno]'
					and tyyppi  = 'D'";
		$updresult = mysql_query($query) or pupe_error($query);
	}
	else {

		// Katsotaan onko useita valmisteita ja tallennetaan arvot muuttujaan niin kuin ne oli!
		if (!isset($kaikenkaikkiaan_arvot_silloin[$tilrivirow["perheid"]])) {
			$query = "	SELECT tapahtuma.tuoteno, sum(tapahtuma.kplhinta*tapahtuma.kpl) summa
						FROM tilausrivi
						JOIN tapahtuma ON tapahtuma.yhtio = tilausrivi.yhtio and tapahtuma.laji='valmistus' and tapahtuma.tuoteno = tilausrivi.tuoteno and tapahtuma.rivitunnus = tilausrivi.tunnus
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus = '$tilrivirow[otunnus]'
						and tilausrivi.perheid = '$tilrivirow[perheid]'
						and tilausrivi.tyyppi in ('W','M')
						GROUP BY tapahtuma.tuoteno";
			$uvresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($uvresult) > 0) {
				while ($valmisteet = mysql_fetch_assoc($uvresult)) {
					$kaikenkaikkiaan_arvot_silloin[$tilrivirow["perheid"]][$valmisteet["tuoteno"]] = $valmisteet["summa"];
					$kaikenkaikkiaan_arvot_silloin_yht[$tilrivirow["perheid"]] += $valmisteet["summa"];
				}
			}
		}

		if (isset($kaikenkaikkiaan_arvot_silloin[$tilrivirow["perheid"]]) and isset($kaikenkaikkiaan_arvot_silloin_yht[$tilrivirow["perheid"]]) and $kaikenkaikkiaan_arvot_silloin_yht[$tilrivirow["perheid"]] > 0) {
			$kaikenkaikkiaan_arvot = $kaikenkaikkiaan_arvot_silloin[$tilrivirow["perheid"]][$tilrivirow["tuoteno"]] / $kaikenkaikkiaan_arvot_silloin_yht[$tilrivirow["perheid"]];
		}
		else {
			$kaikenkaikkiaan_arvot = 1;
		}

		// Suoritetaan j‰lkilaskenta valmisteelle
		$query = "	SELECT *
					from tapahtuma
					where yhtio = '$kukarow[yhtio]'
					and laji = 'valmistus'
					and rivitunnus = '$tilrivirow[tunnus]'
					order by tunnus";
		$res = mysql_query($query) or pupe_error($query);
		$osavalm_lukumaara = mysql_num_rows($res);

		$query = "	SELECT sum(kpl) valmistetut
					FROM tilausrivi
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tilausrivi.otunnus = '$tilrivirow[otunnus]'
					and tilausrivi.perheid = '$tilrivirow[perheid]'
					and tilausrivi.tuoteno = '$tilrivirow[tuoteno]'
					and tilausrivi.tyyppi = 'D'";
		$resisa = mysql_query($query) or pupe_error($query);
		$rowisa = mysql_fetch_assoc($resisa);

		while ($taparow = mysql_fetch_array($res)) {

			$uusihinta 		 = round(($uusiarvo*$kaikenkaikkiaan_arvot) / $valmkpl,6);
			$tuoteno		 = $taparow["tuoteno"];
			$pvm			 = $taparow["laadittu"];
			$rivitunnus 	 = $taparow["rivitunnus"];
			$tapahtumatunnus = $taparow["tunnus"];

			// Lasketaan k‰‰ntˆpisteen saldomuutos
			$kaantopisteen_saldomuutos = round(($taparow["kpl"] * ($valmkpl/$rowisa["valmistetut"]))-$taparow["kpl"], 2);

			echo "<font class='message'>Korjataan tuotteen $tuoteno tapahtumat $pvm ($rivitunnus) l‰htien. Uusi ostohinta $uusihinta. ";

			if ($kaantopisteen_saldomuutos != 0) echo "Valmisteen kappalemuutos: $kaantopisteen_saldomuutos.";

			echo "</font><br>";

			jalkilaskentafunktio($tuoteno, $pvm, $uusihinta, $rivitunnus, $tapahtumatunnus, $kaantopisteen_saldomuutos);

			if ($kaantopisteen_saldomuutos != 0) {
				//P‰ivitet‰‰n myˆs tapahtuman kappalem‰‰r‰
				$query = "	UPDATE tapahtuma
							SET kpl	= kpl+$kaantopisteen_saldomuutos,
							selite  = concat(selite,'\nValmistus korjattu. Valmisteen kappalemuutos: $kaantopisteen_saldomuutos.')
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus 	= '$taparow[tunnus]'";
				$updresult = mysql_query($query) or pupe_error($query);

				$query = "	UPDATE tuotepaikat
							SET saldo=saldo+$kaantopisteen_saldomuutos
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$taparow[tuoteno]'
							and oletus != ''
							LIMIT 1";
				$result = mysql_query($query) or pupe_error($query);
			}
		}

		if ($valmkpl != $rowisa["valmistetut"]) {
			$query = "	UPDATE tilausrivi
						SET varattu = 0
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tilrivirow[otunnus]'
						and tunnus  = '$tilrivirow[tunnus]'";
			$updresult = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE tilausrivi
						SET kpl=round(kpl*($valmkpl/$rowisa[valmistetut]),2), varattu=kpl
						WHERE yhtio = '$kukarow[yhtio]'
						and otunnus = '$tilrivirow[otunnus]'
						and perheid = '$tilrivirow[tunnus]'
						and tyyppi  = 'D'";
			$updresult = mysql_query($query) or pupe_error($query);
		}
	}
?>