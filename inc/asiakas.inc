<?php

	// ajax funkkari!
	if ($haku == "asiakasnro" or $haku == "ytunnus") {

		if (isset($asiakasnro_select)) {
			$apu_asiakasnumero = (int) $asiakasnro_select;
		}
		elseif (isset($ytunnus_select)) {
			$apu_asiakasnumero = (int) $ytunnus_select;
		}
		else {
			$apu_asiakasnumero = (int) $t[$pointer];
		}
		
		//jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksilöivät tiedot on oltava konsernitasolla-yksilölliset
		$query = "	SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
					FROM yhtio 
					JOIN yhtion_parametrit ON yhtion_parametrit.yhtio=yhtio.yhtio
					where konserni	 = '$yhtiorow[konserni]' 
					and (synkronoi = '$toim' or synkronoi like '$toim,%' or synkronoi like '%,$toim,%' or synkronoi like '%,$toim')";
		$vresult2 = mysql_query($query) or pupe_error($query);			
		$srowapu = mysql_fetch_array($vresult2);
		
		if ($srowapu["yhtiot"] != "") {
			$tarkyhtio = $srowapu["yhtiot"];
		}
		else {
			$tarkyhtio = "'$kukarow[yhtio]'";
		}

		unset($vresult);

		$counteri = 0;

		if ($apu_asiakasnumero == 0) {

			if ($yhtiorow["asiakasnumeroinnin_aloituskohta"] != "") {
				$apu_asiakasnumero = $yhtiorow["asiakasnumeroinnin_aloituskohta"];
			}
			else {
				$apu_asiakasnumero = 0;
			}

			$query = "	SELECT MAX($haku+0) $haku
						FROM asiakas USE INDEX (asno_index)
						WHERE yhtio in ($tarkyhtio)
						AND asiakasnro+0 >= $apu_asiakasnumero";
			$vresult = mysql_query($query) or pupe_error($query);
			$vrow = mysql_fetch_assoc($vresult);

			if ($vrow[$haku] != '') {
				$apu_asiakasnumero = $vrow[$haku] + 1;
			}
		}
		else {
			while (!isset($vresult) or mysql_num_rows($vresult) >= 1) {
				$apu_asiakasnumero++;

				$query = "	SELECT $haku
							FROM asiakas USE INDEX (asno_index)
							WHERE yhtio in ($tarkyhtio)
							AND asiakasnro+0 = $apu_asiakasnumero";
				$vresult = mysql_query($query) or pupe_error($query);
			
				$counteri++;

				if ($counteri > 999) {
					$apu_asiakasnumero = t('Ei löytynyt');
					break;
				}
			}
		}

		if ($haku == 'asiakasnro') {
			echo "<input type='text' name='asiakasnro_select' id='t[$pointer]' value='$apu_asiakasnumero'>";
			echo "<input type='hidden' name='t[$pointer]' value='$apu_asiakasnumero'>";
			echo "<input type='button' name='etsi_vapasno' id='etsi_vapasno' value='".t("Seuraava vapaa")."' onClick='javascript:document.getElementById(\"etsi_vapasno\").value = \"".t("Hakua suoritetaan").".\";javascript:document.getElementById(\"t[$pointer]\").disabled = true;javascript:document.getElementById(\"etsi_vapasno\").disabled = true;javascript:ajaxPost(\"mainform\", \"?haku=asiakasnro&ohje=off&pointer=$pointer\", \"asiakasnumero_etsi\", false, false)'>";
		}
		else {
			echo "<input type='text' name='ytunnus_select' id='t[$pointer]' value='$apu_asiakasnumero'>";
			echo "<input type='hidden' name='t[$pointer]' value='$apu_asiakasnumero'>";
			echo "<input type='button' name='etsi_vapytunnus' id='etsi_vapytunnus' value='".t("Seuraava vapaa")."' onClick='javascript:document.getElementById(\"etsi_vapytunnus\").value = \"".t("Hakua suoritetaan").".\";javascript:document.getElementById(\"t[$pointer]\").disabled = true;javascript:document.getElementById(\"etsi_vapytunnus\").disabled = true;javascript:ajaxPost(\"mainform\", \"?haku=ytunnus&ohje=off&pointer=$pointer\", \"ytunnus_etsi\", false, false)'>";
		}
		exit;
	}

	enable_ajax();
	
	$otsikko 	= 'Asiakkaat';
	
	$kentat 	= "tunnus, ytunnus, concat_ws('<br>', concat_ws(' ', nimi, nimitark), concat_ws(' ', toim_nimi, toim_nimitark)) 'Nimi/Toim_nimi', concat_ws('<br>', postitp, toim_postitp) 'Postitp/Toim_postitp', asiakasnro 'Asiakasnumero', concat_ws('<br>', ovttunnus, toim_ovttunnus) 'Ovttunnus/Toim_ovttunnus', laji HIDDEN_laji ";
	$hakukentat = "tunnus, ytunnus, nimi/nimitark/toim_nimi/toim_nimitark, postitp/toim_postitp, asiakasnro, ovttunnus/toim_ovttunnus";

	$jarjestys 	= 'selaus, nimi';


	if ($laji == "H") {
		// Yksityisasiakkaat
		$rajauslisa = " and asiakas.laji = 'H' ";
	}
	elseif ($laji == "Y") {
		// Yritykset asiakkaat
		$rajauslisa = " and asiakas.laji = '' ";
	}
	elseif ($laji == "R") {
		// Prospektit
		$rajauslisa = " and asiakas.laji = 'R' ";
	}
	elseif ($laji == "P") {
		// Poistetut
		$rajauslisa = " and asiakas.laji = 'P' ";
	}
	elseif ($laji == "K") {
		// Korjaamo
		$rajauslisa = " and asiakas.laji = 'K' ";
	}
	else {
		if ($nayta_poistetut == "") {
			$rajauslisa = " and asiakas.laji not in ('P','R') ";
		}
		else {
			$rajauslisa = " and asiakas.laji != 'R' ";
		}
	}
?>