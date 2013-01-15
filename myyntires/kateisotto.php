<?php

require ("../inc/parametrit.inc");
?>
<script language='javascript' type='text/javascript'>
	function tarkista() {
		if($('#kassalipas').val() == '' || $('#summa').val() == '' || $('#kateisoton_luonne').val() == '') {
			alert($('#tarvittavia_tietoja').html());
			return false;
		}

		return true;
	}
</script>
<?php
echo "<font class='head'>".t("K‰teisotto kassalippaasta")."</font><br/>";
echo "<div id='tarvittavia_tietoja'style='display:none;'>".t("Tarvittavia tietoja puuttuu")."</div>";

$kassalippaat = hae_kassalippaat();
$kateisoton_luonteeet = hae_kateisoton_luonteet();

$request_params = array(
	'kassalipas' => $kassalipas_tunnus,
	'summa'=> $summa,
	'kateisoton_luonne' => $kateisoton_luonne,
	'yleinen_kommentti'=>$yleinen_kommentti
);
echo_kateisotto_form($kassalippaat, $kateisoton_luonteeet, $request_params);

if ($tee == 'kateisotto') {
	//tehd‰‰n k‰teisotto
	//
	//haetaan kassalipas row
	$kassalipas = hae_kassalipas($kassalipas_tunnus);

	//tarkistetaan, onko kassalipas jo t‰sm‰ytetty
	$kassalippaan_tasmaytys = tarkista_kassalippaan_tasmaytys($kassalipas['tunnus']);
	if($kassalippaan_tasmaytys['ltunnukset'] != '' and $kassalippaan_tasmaytys['selite'] != '') {
		echo "<font class='error'>".t("T‰m‰n p‰iv‰n valittu kassalipas on jo t‰sm‰ytetty")."</font>";
		exit;
	}

	tee_kateisotto($kassalipas, $summa, $kateisoton_luonne, $yleinen_kommentti);

	echo "<font class='message'>".t("K‰teisotto tehtiin onnistuneesti")."</font>";
}

function tarkista_kassalippaan_tasmaytys($kassalipas_tunnus) {
	global $kukarow;

	$now = date('Y-m-d');

	$query = "	SELECT group_concat(distinct lasku.tunnus) ltunnukset,
				group_concat(distinct tiliointi.selite) selite
				FROM lasku
				JOIN kassalipas
				ON ( kassalipas.yhtio = lasku.yhtio and kassalipas.tunnus = {$kassalipas_tunnus} )
				JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio
				AND tiliointi.ltunnus = lasku.tunnus
				AND tiliointi.selite LIKE concat(kassalipas.nimi,'%')
				AND tiliointi.korjattu = '')
				WHERE lasku.yhtio = '{$kukarow['yhtio']}'
				AND lasku.tila 	  = 'X'
				AND lasku.tapvm   = '$now'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function tee_kateisotto($kassalipas, $summa, $kateisoton_luonne, $yleinen_kommentti) {
	global $kukarow;
	
	//luodaan laskuotsiko
	$lasku_tunnus = tee_laskuotsikko($kassalipas, $summa, $yleinen_kommentti);

	tee_tiliointi($lasku_tunnus, $kassalipas, $summa, $kateisoton_luonne);

	tee_tiliointi($lasku_tunnus, $kassalipas, -1*$summa, '');
}

function tee_laskuotsikko($kassalipas, $summa, $yleinen_kommentti) {
	global $kukarow;

	$kateis_maksuehto = hae_kateis_maksuehto();
	//tila = X ja alatila = '' -> Muu tosite
	//tila = X alatila = '' tilaustyyppi = O ---> T‰m‰ tarkoittaa, ett‰ K‰teisotto
	$query = "	INSERT INTO lasku
				SET yhtio = '{$kukarow['yhtio']}',
				summa = '$summa',
				comments = '{$yleinen_kommentti}',
				tila = 'X',
				alatila = '',
				tilaustyyppi = 'O',
				laatija = '{$kukarow['kuka']}',
				luontiaika = NOW(),
				tapvm = NOW(),
				mapvm = NOW(),
				kassalipas = '{$kassalipas['tunnus']}',
				maksuehto = '{$kateis_maksuehto['tunnus']}',
				nimi = '".t("K‰teisotto kassalippaasta:")." {$kassalipas['nimi']}'";
				
	pupe_query($query);

	return mysql_insert_id();
}

function tee_tiliointi($lasku_tunnus, $kassalipas, $summa, $kateisoton_luonne) {
	global $kukarow;

	if($kateisoton_luonne != '') {
		$kateisoton_luonne_row = hae_kateisoton_luonne($kateisoton_luonne);
		$kateisoton_luonne_row['kustp'] = $kassalipas['kustp'];
	}
	else {
		//t‰m‰ on myyntisaamisia varten
		$kateisoton_luonne_row['tilino'] = $kassalipas['kassa'];
		$kateisoton_luonne_row['kustp'] = $kassalipas['kustp'];
	}
	$query = "	INSERT INTO tiliointi
				SET yhtio = '{$kukarow['yhtio']}',
				laatija = '{$kukarow['kuka']}',
				laadittu = NOW(),
				ltunnus = '{$lasku_tunnus}',
				tilino = '{$kateisoton_luonne_row['tilino']}',
				kustp = '{$kateisoton_luonne_row['kustp']}',
				tapvm = NOW(),
				summa = {$summa},
				summa_valuutassa = {$summa},
				valkoodi = 'EUR',
				selite = '".t("K‰teisotto kassalippaasta:")." {$kassalipas['nimi']}',
				vero = 0.00";

	pupe_query($query);
}

function hae_kateis_maksuehto() {
	global $kukarow;

	$query = "	SELECT *
				FROM maksuehto
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND kateinen = 'p'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function hae_kassalipas($kassalipas_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM kassalipas
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$kassalipas_tunnus}'";
	$result = pupe_query($query);

	//tarkistetaan, ett‰ kassalippaan takaa lˆytyy tilinumerot, jos ei lˆydy niin laitetaan yhtiorown takaa defut
	$kassalipas = mysql_fetch_assoc($result);

	if($kassalipas['kassa'] == '') {
		$kassalipas['kassa'] = $yhtiorow['kassa'];
	}
	if($kassalipas['pankkikortti'] == '') {
		$kassalipas['pankkikortti'] = $yhtiorow['pankkikortti'];
	}
	if($kassalipas['luottokortti'] == '') {
		$kassalipas['luottokortti'] = $yhtiorow['luottokortti'];
	}

	return $kassalipas;
}

//Haetaan kaikki kassalippaat, joihin k‰ytt‰j‰ll‰ on oikeus
//$kukarow['kassalipas'] pit‰‰ sis‰ll‰‰n kassalippaan joihin oikeus. Jos tyhj‰ --> k‰ytt‰j‰ll‰ oikeus kaikkiin kassalippaisiin
function hae_kassalippaat() {
	global $kukarow;

	if ($kukarow['kassalipas'] != '') {
		$sallitut_kassalipppaat = "AND tunnus IN ({$kukarow['kassalipas']})";
	}
	
	$query = "	SELECT *
				FROM kassalipas
				WHERE yhtio = '{$kukarow['yhtio']}'
				{$sallitut_kassalipppaat}";
	$result = pupe_query($query);
	
	$kassalippaat = array();
	while($kassalipas = mysql_fetch_assoc($result)) {
		$kassalippaat[] = $kassalipas;
	}
	
	return $kassalippaat;
}

function hae_kateisoton_luonne($avainsana_tunnus) {
	global $kukarow;

	$query = "	SELECT avainsana.tunnus,
				avainsana.selite,
				avainsana.selitetark,
				tili.tilino,
				tili.kustp
				FROM avainsana
				JOIN tili
				ON ( tili.yhtio = avainsana.yhtio AND tili.tunnus = avainsana.selite )
				WHERE avainsana.yhtio = '{$kukarow['yhtio']}'
				AND avainsana.laji='KATEISOTTO'
				and avainsana.tunnus = '{$avainsana_tunnus}'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function hae_kateisoton_luonteet() {
	global $kukarow;

	$query = "	SELECT avainsana.tunnus,
				avainsana.selite,
				avainsana.selitetark,
				tili.tilino
				FROM avainsana
				JOIN tili
				ON ( tili.yhtio = avainsana.yhtio AND tili.tunnus = avainsana.selite )
				WHERE avainsana.yhtio = '{$kukarow['yhtio']}'
				AND avainsana.laji='KATEISOTTO'";
	$result = pupe_query($query);

	$kateisoton_luonteet = array();
	while($kateisoton_luonne = mysql_fetch_assoc($result)) {
		$kateisoton_luonteet[] = $kateisoton_luonne;
	}

	return $kateisoton_luonteet;
}

function echo_kateisotto_form($kassalippaat, $kateisoton_luonteet, $request_params) {
	echo "<form name='kateisotto' method='POST'>";
	echo "<input type='hidden' name='tee' value='kateisotto'/>";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Kassalipas")."</th>";
	echo "<td>";
	echo "<select name='kassalipas_tunnus' id='kassalipas'>";
	echo "<option value=''>".t("Valitse kassalipas")."</option>";
	$sel = "";
	foreach ($kassalippaat as $kassalipas) {
		if($kassalipas['tunnus'] == $request_params['kassalipas']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$kassalipas['tunnus']}' $sel>{$kassalipas['nimi']}</option>";
		$sel = "";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Summa")."</th>";
	echo "<td><input type='text' name='summa' id='summa' value='{$request_params['summa']}'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Mihin tarkoitukseen k‰teisotto tehd‰‰n")."</th>";
	echo "<td>";
	echo "<select name='kateisoton_luonne' id='kateisoton_luonne'>";
	echo "<option value=''>".t("Valitse tarkoitus")."</option>";
	$sel = "";
	foreach ($kateisoton_luonteet as $luonne) {
		if($luonne['tunnus'] == $request_params['kateisoton_luonne']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$luonne['tunnus']}' $sel>{$luonne['selitetark']} - {$luonne['tilino']}</option>";
		$sel = "";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Yleinen kommentti")."</th>";
	echo "<td><input type='text' name='yleinen_kommentti' id='yleinen_kommentti' value='{$request_params['yleinen_kommentti']}'></td>";
	echo "</tr>";

	echo "<td class='back'><input name='submit' type='submit' value='".t("L‰het‰")."' onClick='return tarkista();'></td>";

	echo "</table>";
	echo "</form>";
}
?>
