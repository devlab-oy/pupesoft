<?php

	// ajax funkkari!
	if ($haku == "asiakasnro") {
		
		if (isset($asiakasnro_select)) {
			$apu_asiakasnumero = $asiakasnro_select;
		}
		else {
			$apu_asiakasnumero = $t[$pointer];
		}
		
		unset($vresult);
		
		while (!isset($vresult) or mysql_num_rows($vresult) >= 1) {
			$apu_asiakasnumero++;
			$query = "	SELECT asiakasnro
						FROM asiakas USE INDEX (asno_index)
						WHERE yhtio = '$kukarow[yhtio]'
						AND asiakasnro = '$apu_asiakasnumero'";
			$vresult = mysql_query($query) or pupe_error($query);
		}

		echo "<input type='text' name='asiakasnro_select' id='t[$pointer]' value='$apu_asiakasnumero'>";
		echo "<input type='hidden' name='t[$pointer]' value='$apu_asiakasnumero'>";
		echo "<input type='button' name='etsi_vapasno' id='etsi_vapasno' value='".t("Etsi seuraava vapaa asiakasnumero")."' onClick='javascript:document.getElementById(\"etsi_vapasno\").value = \"".t("Hakua suoritetaan").".\";javascript:document.getElementById(\"t[$pointer]\").disabled = true;javascript:document.getElementById(\"etsi_vapasno\").disabled = true;javascript:ajaxPost(\"mainform\", \"?haku=asiakasnro&ohje=off&pointer=$pointer\", \"asiakasnumero_etsi\", false, false)'>";
		exit;
	}

	// ajax funkkari!
	if ($haku == "ytunnus") {
		
		if (isset($ytunnus_select)) {
			$apu_asiakasnumero = $ytunnus_select;
		}
		else {
			$apu_asiakasnumero = $t[$pointer];
		}
		
		unset($vresult);

		while (!isset($vresult) or mysql_num_rows($vresult) >= 1) {
			$apu_asiakasnumero++;
			$query = "	SELECT ytunnus
						FROM asiakas USE INDEX (ytunnus_index)
						WHERE yhtio = '$kukarow[yhtio]'
						AND ytunnus = '$apu_asiakasnumero'";
			$vresult = mysql_query($query) or pupe_error($query);
		}

		echo "<input type='text' name='ytunnus_select' id='t[$pointer]' value='$apu_asiakasnumero'>";
		echo "<input type='hidden' name='t[$pointer]' value='$apu_asiakasnumero'>";
		echo "<input type='button' name='etsi_vapytunnus' id='etsi_vapytunnus' value='".t("Etsi seuraava vapaa ytunnus")."' onClick='javascript:document.getElementById(\"etsi_vapytunnus\").value = \"".t("Hakua suoritetaan").".\";javascript:document.getElementById(\"t[$pointer]\").disabled = true;javascript:document.getElementById(\"etsi_vapytunnus\").disabled = true;javascript:ajaxPost(\"mainform\", \"?haku=ytunnus&ohje=off&pointer=$pointer\", \"ytunnus_etsi\", false, false)'>";
		exit;
	}

	enable_ajax();
	
	$otsikko 	= 'Asiakkaat';
	$kentat 	= "tunnus, ytunnus, concat_ws('<br>', concat_ws(' ',nimi,nimitark), concat_ws(' ',toim_nimi,toim_nimitark)) 'Nimi/Toim_nimi', concat_ws('<br>', postitp, toim_postitp) 'Postitp/Toim_postitp', concat_ws('<br>', ovttunnus, toim_ovttunnus) 'Ovttunnus/Toim_ovttunnus', laji HIDDEN_laji ";
	$hakukentat = "tunnus, ytunnus, nimi/toim_nimi, postitp/toim_postitp, ovttunnus/toim_ovttunnus";
	$jarjestys 	= 'selaus, nimi';


	if ($laji == "H") {
		$rajauslisa = " and asiakas.laji = 'H' ";
	}
	elseif ($laji == "Y") {
		$rajauslisa = " and asiakas.laji = '' ";
	}
	elseif ($laji == "P") {
		$rajauslisa = " and asiakas.laji = 'P' ";
	}
	else {
		if ($nayta_poistetut == "") {
			$rajauslisa = " and asiakas.laji != 'P' ";
		}
		else {
			$rajauslisa	= "";
		}
	}

?>