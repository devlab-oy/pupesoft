<?php

//katsotaan että kukarow kesken ja $tilausnumero stemmaavat keskenään
if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero != '' or $kukarow["kesken"] != 0)) {
	echo "<br><br><br>".t("VIRHE: Tilaus ei ole aktiivisena")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
	exit;
}

echo otsikko($toim);

// Etsitään asiakasta
if (($nimi != '' and !isset($jatka) and $asiakasid == 0 and !isset($vaihdatoim_asiakas)) or ($tnimi != '' and !isset($jatka) and isset($vaihdatoim_asiakas))) {

	//tehdään asiakashaku yhteensopivuus
	$ytunnus = $nimi;
	$muutparametrit = 0;
	$kutsuja = "otsik.inc";

	if (isset($vaihdatoim_asiakas) and $tnimi != "") {
		$ytunnus = $tnimi;
		$osoite = $tosoite;
		$postitp = $tpostitp;
		$postino = $tpostino;
		$tila = "vaihdatoim_asiakas";
		$muutparametrit = $asiakasid;
		$kutsuja = "";
	}

	$asiakasid = "";
	$ahlopetus = "tilauskasittely/tilaus_myynti.php////toim=$toim//tee=$tee//tilausnumero=$tilausnumero//mista=$mista//tila=$tila//from=ASIAKASYLLAPITO";
	$lause = "<font class='head'>".t("Valitse asiakas").":</font><hr><br>";

	require ("inc/asiakashaku.inc");

	if ($asiakasid == '' and $monta > 1) {
		//Löytyi monta sopivaa, näytetään formi, mutta ei otsikkoa
		$tila = 'EI_NAYTETA';
	}
}

if ($asiakasid != "" and $tiedot_laskulta == "") {
	// asiakashaussa pitää olla vakio columni kohdistettu, jonka arvo on O että saadaan OLETUS toimitustapa haettua asiakashaunkin yhteydessä
	if ($toim == 'VAURIOPOYTAKIRJA') {
		$puhlisa = " if (asiakas.puhelin!='', asiakas.puhelin, asiakas.gsm) kotipuh, asiakas.tyopuhelin tyopuh,";
	}
	else {
		$puhlisa = "";
	}

	$squery = "	SELECT asiakas.*, $puhlisa asiakas.tunnus liitostunnus, 'O' kohdistettu, '' kohde, '' kauppatapahtuman_luonne
				FROM asiakas
				WHERE tunnus = '$asiakasid'";

	$srow_from = "ASIAKAS";

	$sresult = pupe_query($squery);
	$srow = mysql_fetch_assoc($sresult);
}

if ($tila == "" and !isset($jatka)) {
	if (!empty($srow)) {
		vauriopoytakirja_otsikko();
	}
	else {
		asiakkaan_valinta_formi();
	}
}

function asiakkaan_valinta_formi() {
	global $lopetus, $ruutulimit, $toim, $projektilla, $from, $tilausnumero, $mista, $orig_tila, $orig_alatila, $srow;

	echo "<form method='post' autocomplete='off' name='paaformi' enctype='multipart/form-data'>";
	echo "<input type='hidden' name='tee' value='OTSIK'>";
	echo "<input type='hidden' name='lopetus' value='$lopetus'>";
	echo "<input type='hidden' name='ruutulimit' value = '$ruutulimit'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<input type='hidden' name='projektilla' value='$projektilla'>";
	echo "<input type='hidden' name='from' value='$from'>";
	echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
	echo "<input type='hidden' name='mista' value='$mista'>";
	echo "<input type='hidden' name='orig_tila' value='$orig_tila'>";
	echo "<input type='hidden' name='orig_alatila' value='$orig_alatila'>";

	echo "<table style='border-collapse:collapse;'>";

	echo "<tr>";
	echo "<th colspan='2' align='left' valign='top'>".t("Ostaja").":</th>";
	echo "</tr>";

	echo "<tr>";
	echo "<td valign='top'>".t("Nimi").": </td>";
	echo "<td>";
	echo "<input type='text' name='nimi' size='35' value='{$srow['nimi']}' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td></td>";
	echo "<td>";
	echo "<input type='text' name='nimitark' size='35' value='{$srow['nimitark']}' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td valign='top'>".t("Osoite").": </td>";
	echo "<td>";
	echo "<input type='text' name='osoite' size='35' value='{$srow['osoite']}' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td valign='top'>".t("Postitp").": </td>";
	echo "<td>";
	echo "<input type='text' name='postino' size='10' value='{$srow['postino']}' /> <input type='text' name='postitp' size='21' value='{$srow['postitp']}' />";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "<input type='submit' value='".t("Hae")."' />";

	echo "</form>";
}

function vauriopoytakirja_otsikko() {
	global $lopetus, $ruutulimit, $toim, $projektilla, $from, $tilausnumero, $mista, $orig_tila, $orig_alatila, $srow, $asiakasid, $ylatila, $alatila, $asentaja, $tyojono;

	echo "<form method='post' autocomplete='off' name='paaformi' enctype='multipart/form-data'>";

	echo "<input type='hidden' name='tee' value='OTSIK'>";
	echo "<input type='hidden' name='lopetus' value='$lopetus'>";
	echo "<input type='hidden' name='ruutulimit' value = '$ruutulimit'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<input type='hidden' name='projektilla' value='$projektilla'>";
	echo "<input type='hidden' name='from' value='$from'>";
	echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
	echo "<input type='hidden' name='mista' value='$mista'>";
	echo "<input type='hidden' name='orig_tila' value='$orig_tila'>";
	echo "<input type='hidden' name='orig_alatila' value='$orig_alatila'>";
	echo "	<input type='hidden' name='asiakasid' value='$asiakasid'>
			<input type='hidden' name='ylatila' 						 value = '$ylatila'>
			<input type='hidden' name='alatila' 						 value = '$alatila'>
			<input type='hidden' name='otsikolta' 						 value = 'YES'>
			<input type='hidden' name='laskutusvkopv' 					 value = '$srow[laskutusvkopv]'>
			<input type='hidden' name='ovttunnus' 						 value = '$srow[ovttunnus]'>
			<input type='hidden' name='toim_ovttunnus' 					 value = '$srow[toim_ovttunnus]'>
			<input type='hidden' name='kolm_ovttunnus' 					 value = '$srow[kolm_ovttunnus]'>
			<input type='hidden' name='chn' 							 value = '$srow[chn]'>
			<input type='hidden' name='verkkotunnus' 					 value = '$srow[verkkotunnus]'>
			<input type='hidden' name='ytunnus' 						 value = '$srow[ytunnus]'>
			<input type='hidden' name='kolm_maa' 						 value = '$srow[kolm_maa]'>
			<input type='hidden' name='tunnusnippu' 					 value = '$srow[tunnusnippu]'>
			<input type='hidden' name='projekti'						 value = '$srow[projekti]'>
			<input type='hidden' name='jaksotettu'	 					 value = '$srow[jaksotettu]'>
			<input type='hidden' name='tunnusnippu_tarjous' 			 value = '$srow[tunnusnippu_tarjous]'>
			<input type='hidden' name='kauppatapahtuman_luonne' 		 value = '$srow[kauppatapahtuman_luonne]'>
			<input type='hidden' name='asentaja' 						 value = '$asentaja'>
			<input type='hidden' name='tyojono' 						 value = '$tyojono'>
			<input type='hidden' name='liitostunnus' 					 value = '$srow[liitostunnus]'>";

	$dynaamiset_kentat = hae_dynaamiset_kentat();
	echo "<table class='vauriopoytakirja_otsikko'>";

	echo "<tr>";
	echo "<th colspan='4'>".t('Vauriopöytäkirja')."</th>";
	echo "</tr>";

	echo "<tr>";

	echo "<td>";
	echo t('Työ alkoi');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='poistui_paiva' /> <input type='text' name='poistui_aika' />";
	echo "</td>";

	echo "<td>";
	echo t('Työ päättyi');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='valmis_paiva' /> <input type='text' name='valmis_aika' />";
	echo "</td>";

	echo "</tr>";
	echo "</table>";

	echo "<table class='vauriopoytakirja_otsikko'>";

	echo "<tr>";
	echo "<th colspan='4'>".t('Tapahtumapaikka')."</th>";
	echo "</tr>";

	echo "<tr>";

	echo "<td>";
	echo t('Kaupunki').'/'.t('Kunta');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='tapahtumapaikka_kaupunki' />";
	echo "</td>";

	echo "<td rowspan='2' align='center'>";
	echo t('Verkostoalue');
	echo "<br/>";
	echo "<input type='text' name='jalleenmyyja' />";
	echo "</td>";

	echo "<td rowspan='2' align='center'>";
	echo t('TLA');
	echo "<br/>";
	echo "<input type='text' name='viite' />";
	echo "</td>";

	echo "</tr>";

	echo "<tr>";

	echo "<td>";
	echo t('Paikka').'/'.t('osoite');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='tapahtumapaikka_osoite' />";
	echo "</td>";

	echo "</tr>";

	echo "<tr>";
	echo "<th colspan='4'>".t('Vaurio tapahtui').":</th>";
	echo "</tr>";

	echo "<tr>";

	$vaurion_kohde = search_array_key_for_value_recursive($dynaamiset_kentat, 'selite', 'vikakoodi');
	$vaurion_kohde = $vaurion_kohde[0];
	$radio_values = explode("#", $vaurion_kohde['selitetark_3']);

	echo "<td>";
	echo "<table>";
	foreach ($radio_values as $i => $value) {
		if (($i + 1) % 2 == 1)
			echo "<tr>";

		$selec = ($srow[$vaurion_kohde['selite']] == $value) ? "CHECKED" : "";
		echo "<td><input type='radio' name='{$vaurion_kohde['selite']}' value='$value' $selec>$value</input>";

		if (($i + 1) % 2 == 0)
			echo "</tr>";
	}
	echo "</table>";
	echo "</td>";

	$kaapelin_tyyppi = search_array_key_for_value_recursive($dynaamiset_kentat, 'selite', 'merkki');
	$kaapelin_tyyppi = $kaapelin_tyyppi[0];
	$checkbox_arvot = explode("#", $kaapelin_tyyppi['selitetark_3']);

	echo "<td>";
	echo "<table>";
	foreach ($checkbox_arvot as $i => $checkbox) {
		if (($i + 1) % 3 == 1)
			echo "<tr>";

		$valinnat = explode('#', $srow[$kaapelin_tyyppi['selite']]);
		$selec = (in_array(str_replace(" ", "", $checkbox), $valinnat)) ? "CHECKED" : "";
		echo "<td><input type='checkbox' name='{$kaapelin_tyyppi['selite']}[]' value='".str_replace(" ", "", $checkbox)."' $selec>$checkbox</td>";

		if (($i + 1) % 3 == 0)
			echo "</tr>"; // Joka kolmanella rivillä break
	}
	echo "</table>";
	echo "</td>";

	echo "<td align='center'>";
	echo t('Operaattorin tiketti numero');
	echo "<br/>";
	echo "<input type='text' name='takuunumero' />";
	echo "</td>";

	$kiireellisyys = search_array_key_for_value_recursive($dynaamiset_kentat, 'selite', 'prioriteetti');
	$kiireellisyys = $kiireellisyys[0];
	$radio_values = explode("#", $kiireellisyys['selitetark_3']);

	echo "<td>";
	echo t('Kiireellisyys');
	echo "<table>";
	foreach ($radio_values as $i => $value) {
		if (($i + 1) % 2 == 1)
			echo "<tr>";

		$selec = ($srow[$kiireellisyys['selite']] == $value) ? "CHECKED" : "";
		echo "<td><input type='radio' name='{$kiireellisyys['selite']}' value='$value' $selec>$value</input>";

		if (($i + 1) % 2 == 0)
			echo "</tr>";
	}
	echo "</table>";
	echo "</td>";

	echo "</tr>";

	echo "</table>";

	echo "<table class='vauriopoytakirja_otsikko'>";

	echo "<tr>";
	echo "<th>".t('Ilmoitettu poliisiviranoimaiselle').":</th>";
	echo "<td>";
	echo "<input type='radio' name='ilmoitettu_poliisille' /> ".t('Kyllä')." <input type='radio' name='ilmoitettu_poliisille' /> ".t('Ei');
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t('Vahinkotapahtuma').":</th>";
	echo "<td>";
	echo "<input type='checkbox' name='valokuvia_saatavilla' /> ".t('Valokuvia saatavilla');
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td colspan='2'>";
	echo "<textarea class='vahinkotapahtuma_textarea' name='vahinkotapahtuma'>";
	echo "</textarea>";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	aiheuttaja(array());
	aiheuttaja(array(), 'laskutusosoite');

	echo "<table class='vauriopoytakirja_otsikko'>";

	echo "<tr>";

	echo "<td>";
	echo t('Vakuutusyhtiö');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='vakuutusyhtiö' />";
	echo "</td>";

	echo "<td>";
	echo t('Auton tai koneen rekisterinumero');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='rekisterinumero' />";
	echo "</td>";

	echo "</tr>";

	echo "<tr>";

	echo "<td>";
	echo t('Aiheuttaja kieltäytyy korvausvastuusta');
	echo "</td>";

	echo "<td colspan='3'>";
	echo "<input type='radio' name='aiheuttaja_kieltaytyy' /> ".t('Kyllä')."<input type='radio' name='aiheuttaja_kieltaytyy' /> ".t('Ei');
	echo "</td>";

	echo "</tr>";

	echo "<tr>";

	echo "<td>";
	echo t('Kaapelinäyttö suoritettu');
	echo "</td>";

	echo "<td>";
	echo "<input type='radio' name='kaapelinaytto_suoritettu' /> ".t('Kyllä')."<input type='radio' name='kaapelinaytto_suoritettu' /> ".t('Ei');
	echo "</td>";

	echo "<td>";
	echo t('Näyttöpohjakirja lähetetty').'('.t('pakollinen').')';
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='nayttopoytakirja_lahetetty' />";
	echo "</td>";

	echo "</tr>";

	echo "<tr>";

	echo "<td>";
	echo t('Karttakaivuu');
	echo "</td>";

	echo "<td>";
	echo "<input type='checkbox' name='karttakaivuu' />";
	echo "</td>";

	echo "<td>";
	echo t('Näyttäjä');
	echo "<br/>";
	echo t('Yhteystiedot');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='nayttaja' />";
	echo "<br/>";
	echo "<input type='text' name='nayttaja_yhteystiedot' />";
	echo "</td>";

	echo "</tr>";

	echo "</table>";

	echo "<table class='vauriopoytakirja_otsikko'>";

	echo "<tr>";
	echo "<th colspan='4'>".t('Lisätietoja TSF:lle')."</th>";
	echo "</tr>";

	echo "<tr>";
	echo "<td colspan='4'>";
	echo "<textarea class='vahinkotapahtuma_textarea' name='lisatietoja'>";
	echo "</textarea>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";

	echo "<th>".t('Selvityksen antaja')."</th>";

	echo "<td>";
	echo "<input type='text' name='selvityksen_antaja' />";
	echo "</td>";

	echo "<th>".t('Puhelin')."</th>";

	echo "<td>";
	echo "<input type='text' name='selvityksen_antaja_puhelin' />";
	echo "</td>";

	echo "</tr>";

	echo "</table>";

	echo "<input type='submit' value='".t('Jatka')."' />";

	echo "</form>";
}

function aiheuttaja($params, $tyyppi = 'aiheuttaja') {
	if ($tyyppi == 'aiheuttaja') {
		$otsikko = t('Aiheuttaja');
	}
	else {
		$otsikko = t('Laskutusosoite');
	}
	echo "<table class='vauriopoytakirja_otsikko'>";

	echo "<tr>";
	echo "<th colspan='4'>{$otsikko}</th>";
	echo "</tr>";

	if ($tyyppi == 'aiheuttaja') {
		echo "<tr>";
		echo "<td colspan='4'>";
		echo "<input type='checkbox' name='aiheuttaja_tuntematon' /> ".t('Aiheuttaja tuntematon');
		echo "</td>";
		echo "</tr>";
	}

	echo "<tr>";
	echo "<td>";
	echo t('Nimi');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='nimi' />";
	echo "</td>";

	echo "<td>";
	echo t('Puhelinnumero');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='puhelinnumero' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>";
	echo t('Osoite');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='osoite' />";
	echo "</td>";

	echo "<td>";
	echo t('Matkapuhelin');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='matkapuhelin' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>";
	echo t('Postilokero');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='postilokero' />";
	echo "</td>";

	echo "<td>";
	echo t('Y-Tunnus');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='ytunnus' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>";
	echo t('Postinumero');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='postino' />";
	echo "</td>";

	echo "<td>";
	echo t('Postitoimipaikka');
	echo "</td>";

	echo "<td>";
	echo "<input type='text' name='postitp' />";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "</form>";
}

function otsikko($toim) {
	if ($toim == "VAURIOPOYTAKIRJA") {
		return "<font class='head'>".t("Vauriopöytäkirjan otsikko")."</font><hr>";
	}
}

function hae_dynaamiset_kentat() {
	global $kukarow, $yhtiorow;

	$dynaamiset_kentat_result = t_avainsana("TYOM_TYOKENTAT", "", "and avainsana.selitetark != ''");

	$dynaamiset_kentat = array();
	while ($dynaaminen_kentta = mysql_fetch_assoc($dynaamiset_kentat_result)) {
		$dynaamiset_kentat[] = $dynaaminen_kentta;
	}

	return $dynaamiset_kentat;
}