<?php

require ("../inc/parametrit.inc");
?>
<script language='javascript' type='text/javascript'>
	function tarkista() {
		if($('#kassalipas').val() == '' || $('#summa').val() == '' || $('#kateisoton_luonne').val() == '') {
			alert($('#tarvittavia_tietoja').html());
			return false;
		}

		if($('#userfile').val() != '' && $('#kuvaselite').val() == '') {
			alert($('#kuva_selite_alert').html());
			return false;
		}

		return true;
	}
</script>
<?php
echo "<font class='head'>".t("K‰teisotto kassalippaasta")."</font><hr>";
echo "<div id='tarvittavia_tietoja'style='display:none;'>".t("Tarvittavia tietoja puuttuu")."</div>";
echo "<div id='kuva_selite_alert'style='display:none;'>".t("Anna liitteelle selite")."</div>";

$kassalippaat = hae_kassalippaat();
$kateisoton_luonteeet = hae_kateisoton_luonteet();

$request_params = array(
	'kassalipas' => $kassalipas_tunnus,
	'summa'=> $summa,
	'kateisoton_luonne' => $kateisoton_luonne,
	'yleinen_kommentti' => $yleinen_kommentti,
	'userfile' => $userfile,
	'kuvaselite' => $kuvaselite
);

if ($tee == 'kateisotto') {
	$voiko_kateisoton_tehda = tarkista_saako_laskua_muuttaa(date('Y-m-d'));
	if($voiko_kateisoton_tehda) {

		$voiko_kateisoton_tehda = validoi_liitetiedosto($_FILES);
		if($voiko_kateisoton_tehda) {
			//tehd‰‰n k‰teisotto
			//
			//haetaan kassalipas row
			$kassalipas = hae_kassalipas($kassalipas_tunnus);

			//tarkistetaan, onko kassalipas jo t‰sm‰ytetty
			$kassalippaan_tasmaytys = tarkista_kassalippaan_tasmaytys($kassalipas['tunnus']);
			if($kassalippaan_tasmaytys['ltunnukset'] != '' and $kassalippaan_tasmaytys['selite'] != '') {
				echo "<font class='error'>".t("T‰m‰n p‰iv‰n valittu kassalipas on jo t‰sm‰ytetty")."</font>";
			}
			else {
				$lasku_tunnus = tee_kateisotto($kassalipas, $summa, $kateisoton_luonne, $yleinen_kommentti);
				echo "<font class='message'>".t("K‰teisotto tehtiin onnistuneesti")."</font>";

				if(!empty($lasku_tunnus) and is_uploaded_file($_FILES['userfile']['tmp_name'])) {
					tallenna_liite("userfile", "lasku", $lasku_tunnus, $request_params['kuvaselite'], "", 0, 0, "");
				}
			}
		}
	}
	else {
		echo "<font class='error'>".t("VIRHE: Tilikausi on p‰‰ttynyt %s. Et voi merkit‰ laskua maksetuksi p‰iv‰lle %s", "", $yhtiorow['tilikausi_alku'], date('Y-m-d'))."!</font>";
	}

	echo_kateisotto_form($kassalippaat, $kateisoton_luonteeet);
}
else {
	echo_kateisotto_form($kassalippaat, $kateisoton_luonteeet, $request_params);
}

function tarkista_kassalippaan_tasmaytys($kassalipas_tunnus) {
	global $kukarow;

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
				AND lasku.tapvm   = CURDATE()";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function tee_kateisotto($kassalipas, $summa, $kateisoton_luonne, $yleinen_kommentti) {
	global $kukarow;
	
	//luodaan laskuotsiko
	$lasku_tunnus = tee_laskuotsikko($kassalipas, $summa, $yleinen_kommentti);

	tee_tiliointi($lasku_tunnus, $kassalipas, $summa, $kateisoton_luonne);

	tee_tiliointi($lasku_tunnus, $kassalipas, -1*$summa, '');

	return $lasku_tunnus;
}

function tee_laskuotsikko($kassalipas, $summa, $yleinen_kommentti) {
	global $kukarow;

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
				kassalipas = '{$kassalipas['tunnus']}',
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
				selite = '".t("K‰teisotto kassalippaasta").": {$kassalipas['nimi']}',
				vero = 0.00";

	pupe_query($query);
}

function tarkista_saako_laskua_muuttaa($tapahtumapaiva) {
	global $kukarow, $yhtiorow;

	if (strtotime($yhtiorow['tilikausi_alku']) < strtotime($tapahtumapaiva) and strtotime($yhtiorow['tilikausi_loppu']) > strtotime($tapahtumapaiva)) {
		return true;
	}
	else {
		return false;
	}

}

function validoi_liitetiedosto($_FILES) {
	$return = true;
	if(!empty($_FILES['userfile'])) {
		switch ($_FILES['userfile']['error']) {
			case 1:
			case 2:
				$errormsg .= t("Kuva on liian suuri, suurin sallittu koko on")." ".ini_get('post_max_size'). '<br/>';
				$return = false;
				break;
			case 3:
				$errormsg .= t("Kuvan lataus keskeytyi")."!<br/>";
				$return = false;
				break;
			case 6:
			case 7:
			case 8:
				$errormsg .= t("Tallennus ep‰onnistui")."!<br/>";
				$return = false;
				break;
			case 0:
				//	OK tallennetaan
				$return = true;
				break;
		}
		
		$query = "SHOW variables like 'max_allowed_packet'";
		$result = pupe_query($query);
		$varirow = mysql_fetch_row($result);
		if ($_FILES['userfile']['size'] > $varirow[1]) {
			$errormsg .= t("Liitetiedosto on liian suuri")."! ($varirow[1]) <br/>";
			$return = false;
		}

	}

	echo "<font class='error'>". $errormsg . "</font>";

	return $return;
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

function echo_kateisotto_form($kassalippaat, $kateisoton_luonteet, $request_params = array()) {
	echo "<form name='kateisotto' method='POST' enctype='multipart/form-data'>";
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
	echo "	<tr>
				<th>".t("Valitse tiedosto")."</th>
				<td><input id='userfile' name='userfile' type='file'></td>
				</tr>
				<th>".t("Liitteen kuvaus")."</th>
				<td><input id='kuvaselite' type='text' size='40' name='kuvaselite'></td>
		</tr>";

	echo "</table>";

	echo "<td class='back'><input name='submit' type='submit' value='".t("L‰het‰")."' onClick='return tarkista();'></td>";

	echo "</form>";
}

require("../inc/footer.inc");

?>
