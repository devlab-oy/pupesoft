<?php

	$query = "SELECT tapvm FROM lasku WHERE tunnus = '{$tunnus}'";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 0) {
		echo "<b>".t("Lasku katosi")."</b><br>\n";
		exit;
	}
	$laskurow = mysql_fetch_assoc($result);

	$summa = round($summa, 2);

	if ($vero != 0) { // Netotetaan alvi
		$alv = round($summa - $summa / (1 + ($vero / 100)),2);
		$summa -= $alv;
	}

	if ((!isset($kpexport) or $kpexport != 1) and strtoupper($yhtiorow['maa']) == 'FI') $tositenro = 0; // Jos tätä ei tarvita

	// jos on valittu tiliointipvm popupista
	if (isset($tiliointipvm) and $tiliointipvm != ''){
		$laskurow['tapvm'] = $tiliointipvm;
	}

	$tapvmlisa = "'{$laskurow['tapvm']}'";

	// jos tiliöinti on suljetulla tilikaudella, laitetaan uusi tiliöinti kuluvalle tilikaudelle eli tälle päivälle
	if (isset($suljettu_tilikausi) and $suljettu_tilikausi) {
		$tapvmlisa = "now()";
	}

	// jos ollaan muokkaamassa tiliöintiä ja se on ollut valuutassa
	if (isset($vanhasumma) and isset($vanhasumma_valuutassa) and isset($vanhasumma_valkoodi) and $vanhasumma != "" and $vanhasumma_valuutassa != "" and $vanhasumma_valkoodi != "") {
		// lasketaan kurssi
		$vanhasumma_kurssi = $vanhasumma_valuutassa / $vanhasumma;
		// ja uudet arvot valuutassa
		$summa_valuutassa = round($summa * $vanhasumma_kurssi, 2);
		$alv_valuutassa = round($alv * $vanhasumma_kurssi, 2);
		$valkoodi = $vanhasumma_valkoodi;
	}

	// jos ollaan saatu poikkeava alvtili käytetään sitä, varmistetaan että se ei ole tyhjää ja että on numero, jos ei niin yhtion takaa
	if (!isset($alv_tili) or $alv_tili == "" or !is_numeric($alv_tili)) {
		$alv_tili = $yhtiorow["alv"];
	}

	if (!isset($valkoodi) or $valkoodi == "") {
		$valkoodi = $yhtiorow["valkoodi"];
	}

	if (!isset($summa_valuutassa)) {
		$summa_valuutassa = $summa;
	}

	list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($tili, $kustp, $kohde, $projekti);

	if (trim($liitostunnus) == '') {
		$liitos = '';
		$liitostunnus = 0;
	}

	// Ei rikota rivinvaihtoja
	$selite = str_ireplace("(br)", "<br>", $selite);

	if ($paivitetaanko) {

		$query = "	SELECT *
					FROM tiliointi
					WHERE yhtio 		 = '{$kukarow['yhtio']}'
					AND ltunnus 		 = '{$tunnus}'
					AND tunnus 			 = '{$tiliointirivit[$i]}'
					AND liitos 			 = '{$liitos}'
					AND liitostunnus 	 = '{$liitostunnus}'
					AND tilino 			 = '{$tili}'
					AND kustp 			 = '{$kustp_ins}'
					AND kohde 			 = '{$kohde_ins}'
					AND projekti 		 = '{$projekti_ins}'
					AND summa 			 = '{$summa}'
					AND summa_valuutassa = '{$summa_valuutassa}'
					AND valkoodi 		 = '{$valkoodi}'
					AND tapvm 			 = {$tapvmlisa},
					AND tosite 			 = '{$tositenro}'
					AND vero 			 = '{$vero}'
					AND selite 			 = '{$selite}'";
		$tilrivi_chk_res = pupe_query($query);

		if (mysql_num_rows($tilrivi_chk_res) == 0) {

			$query = "	UPDATE tiliointi SET
						korjattu 	= '{$kukarow['kuka']}',
			      		korjausaika = now()
			      		WHERE ltunnus 	= '{$tunnus}'
			      		AND yhtio 		= '{$kukarow['yhtio']}'
			      		AND korjattu 	= ''
						AND tunnus 		= '{$tiliointirivit[$i]}'";
			$ylikirjaus_result = pupe_query($query);

			$query = "	INSERT into tiliointi set
						yhtio 			 = '{$kukarow['yhtio']}',
						ltunnus 		 = '{$tunnus}',
						liitos			 = '{$liitos}',
						liitostunnus	 = '{$liitostunnus}',
						tilino 			 = '{$tili}',
						kustp    		 = '{$kustp_ins}',
						kohde	 		 = '{$kohde_ins}',
						projekti 		 = '{$projekti_ins}',
						tapvm 			 = {$tapvmlisa},
						summa 			 = '{$summa}',
						summa_valuutassa = '{$summa_valuutassa}',
						valkoodi 		 = '{$valkoodi}',
						vero 			 = '{$vero}',
						selite 			 = '{$selite}',
						lukko 			 = '',
						tosite 			 = '{$tositenro}',
						laatija 		 = '{$kukarow['kuka']}',
						laadittu 		 = now()";
		}

	}
	else {
		$query = "	INSERT into tiliointi set
					yhtio 			 = '{$kukarow['yhtio']}',
					ltunnus 		 = '{$tunnus}',
					liitos			 = '{$liitos}',
					liitostunnus	 = '{$liitostunnus}',
					tilino 			 = '{$tili}',
					kustp    		 = '{$kustp_ins}',
					kohde	 		 = '{$kohde_ins}',
					projekti 		 = '{$projekti_ins}',
					tapvm 			 = {$tapvmlisa},
					summa 			 = '{$summa}',
					summa_valuutassa = '{$summa_valuutassa}',
					valkoodi 		 = '{$valkoodi}',
					vero 			 = '{$vero}',
					selite 			 = '{$selite}',
					lukko 			 = '',
					tosite 			 = '{$tositenro}',
					laatija 		 = '{$kukarow['kuka']}',
					laadittu 		 = now()";
	}

	$result = pupe_query($query);
	$isa = mysql_insert_id(); // Näin löydämme tähän liittyvät alvit....

	// Tiliöidään alv
	if ($vero != 0) {

		if (!isset($alv_valuutassa)) {
			$alv_valuutassa = $alv;
		}

		$query = "	INSERT into tiliointi set
					yhtio 			 = '{$kukarow['yhtio']}',
					ltunnus 		 = '{$tunnus}',
					tilino 			 = '{$alv_tili}',
					kustp 			 = 0,
					kohde 			 = 0,
					projekti 		 = 0,
					tapvm 			 = {$tapvmlisa},
					summa 			 = '{$alv}',
					summa_valuutassa = '{$alv_valuutassa}',
					valkoodi 		 = '{$valkoodi}',
					vero 			 = 0,
					selite 			 = '{$selite}',
					lukko 			 = '1',
					laatija 		 = '{$kukarow['kuka']}',
					laadittu 		 = now(),
					aputunnus 		 = {$isa}";
		$result = pupe_query($query);
	}

?>