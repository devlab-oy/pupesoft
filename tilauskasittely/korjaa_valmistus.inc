<?php

	//k‰ytet‰‰n tilausriveill‰ olevia tuotteita
	$query = "	SELECT tilausrivi.*, if(varattu!=0, varattu, kpl) varattu, 
				trim(concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso)) paikka, 
				tuote.ei_saldoa, tuote.kehahin, tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm
				FROM tilausrivi
				JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				and tilausrivi.otunnus = '$tilrivirow[otunnus]'
				and tilausrivi.perheid = '$tilrivirow[perheid]'
				and tilausrivi.tyyppi = 'V'
				ORDER by tilausrivi.tuoteno";
	$perheresult = mysql_query($query) or pupe_error($query);

	$uusiarvo 	= 0;

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
			
			$query = "	INSERT into tapahtuma set
						yhtio    	= '$kukarow[yhtio]',
						tuoteno  	= '$perherow[tuoteno]',
						laji     	= 'kulutus',
						kpl      	=  round($perherow[varattu]*-1 ,2),
						kplhinta 	= '$perherow[kehahin]',
						hinta    	= '$perherow[kehahin]',
						rivitunnus	= '$perherow[tunnus]',
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
		if($perherow["kpl"] != $perherow["varattu"]) {
			echo "<br><font class='message'>Raaka aineen $perherow[tuoteno] kulutusta muutettu! $perherow[kpl] --> $perherow[varattu]. Teemme j‰lkilaskennan raaka-aineelle.</font><br>";

			//Suoritetaan j‰lkilaskenta
			$query   = "SELECT *
						from tapahtuma
						where yhtio='$kukarow[yhtio]' and laji='kulutus' and rivitunnus='$perherow[tunnus]'
						order by tunnus";
			$res     = mysql_query($query) or pupe_error($query);

			$osavalm_lukumaara = mysql_num_rows($res);

			//Lasketaan k‰‰ntˆpisteen saldomuutos joka johtuu raaka-aineen kulutuksen muutokseta
			$kaantopisteen_saldomuutos = ($perherow["kpl"] - $perherow["varattu"]) / $osavalm_lukumaara;
			
			while($taparow = mysql_fetch_array($res)) {

				//Etsit‰‰n k‰‰ntˆpiste
				$query = "SELECT * FROM tapahtuma WHERE yhtio = '$kukarow[yhtio]' and tuoteno='$taparow[tuoteno]' and laji in ('tulo','valmistus') and laadittu >= '$taparow[laadittu]' LIMIT 1";
				$kaantopisteres = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($kaantopisteres) > 0) {
					$kaantopisterow = mysql_fetch_array($kaantopisteres);

					$uusihinta 		 = $kaantopisterow["kplhinta"];
					$tuoteno		 = $kaantopisterow["tuoteno"];
					$pvm			 = $kaantopisterow["laadittu"];
					$rivitunnus 	 = $kaantopisterow["rivitunnus"];
					$tapahtumatunnus = $kaantopisterow["tunnus"];

					echo "<font class='message'>Korjataan raaka-aineen $tuoteno tapahtumat $pvm ($rivitunnus) l‰htien. Raaka-aineen kulutusmuutos: $kaantopisteen_saldomuutos.</font><br>";

					require("jalkilaskenta.inc");
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

			$query = "	UPDATE tilausrivi
						SET kpl=$perherow[varattu], varattu=0
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus = '$perherow[tunnus]'";
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
		
		echo "<font class='message'>Valmisteen valmistusmuutos: $valmkpl.</font><br>";

		$query = "	UPDATE tilausrivi
					SET kpl=0, varattu=0
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilrivirow[otunnus]'
					and tunnus  = '$tilrivirow[tunnus]'";
		$updresult = mysql_query($query) or pupe_error($query);
		
		$query = "	UPDATE tilausrivi
					SET kpl=0, varattu=0
					WHERE yhtio = '$kukarow[yhtio]'
					and otunnus = '$tilrivirow[otunnus]'
					and perheid = '$tilrivirow[tunnus]'
					and tyyppi  = 'D'";
		$updresult = mysql_query($query) or pupe_error($query);
	}
	else {
		// Suoritetaan j‰lkilaskenta valmisteelle
		$query   = "SELECT *
					from tapahtuma
					where yhtio='$kukarow[yhtio]' and laji='valmistus' and rivitunnus='$tilrivirow[tunnus]'
					order by tunnus";
		$res     = mysql_query($query) or pupe_error($query);

		while($taparow = mysql_fetch_array($res)) {

			$uusihinta 		 = round($uusiarvo / $valmkpl,6);
			$tuoteno		 = $taparow["tuoteno"];
			$pvm			 = $taparow["laadittu"];
			$rivitunnus 	 = $taparow["rivitunnus"];
			$tapahtumatunnus = $taparow["tunnus"];

			echo "<br><font class='message'>Korjataan tuotteen $tuoteno tapahtumat $pvm ($rivitunnus) l‰htien. Uusi ostohinta $uusihinta.</font><br>";

			require("jalkilaskenta.inc");
		}
	}
?>