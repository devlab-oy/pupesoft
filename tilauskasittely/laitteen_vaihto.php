<?php

require('../inc/parametrit.inc');

//AJAX requestit tänne
if ($ajax_request) {
	exit;
}

//functionc.inc require pitää olla ajax requestin jälkeen koska muute mennee headerit ketuiks
require('../inc/functions.inc');

echo "<font class='head'>".t("Laitteen vaihto").":</font>";
echo "<hr/>";
echo "<br/>";
?>
<style>

</style>
<script>

</script>

<?php

$request = array(
	'tee'				 => $tee,
	'tilausrivi_tunnus'	 => $tilausrivi_tunnus,
	'lasku_tunnus'		 => $lasku_tunnus,
	'uusi_laite'		 => $uusi_laite,
);

if ($request['tee'] == 'vaihda_laite') {
	$request['uusi_laite'] = luo_uusi_laite($request['uusi_laite']);

	//vanha työmääräys rivi pitää asettaa var P tilaan
	aseta_tyomaarays_var($request['tilausrivi_tunnus'], 'P');

	//pitää luoda uusi työmääräys rivi johon tulee laitteen vaihto tuote tjsp
	$request['vaihto_toimenpide'] = hae_vaihtotoimenpide_tuote();
	$request['vaihto_toimenpide_tyomaarays_tilausrivi_tunnus'] = luo_uusi_tyomaarays_rivi($request['vaihto_toimenpide'], $request['lasku_tunnus']);

	//vaihtotoimenpide ja uusi laite pitää linkata toisiinsa
	linkkaa_uusi_laite_vaihtotoimenpiteeseen($request['uusi_laite'], $request['vaihto_toimenpide_tyomaarays_tilausrivi_tunnus']);

	//työmääräykselle pitää liittää uusi laite
	luo_uusi_tyomaarays_rivi($request['uusi_laite'], $request['lasku_tunnus']);

	//@TODO SELVITÄ PITÄÄKÖ TYÖJONO NÄKYMÄSSÄ NYKÄ VAR P TUOTTEITA.
	//@TODO TODELLA TÄRKEÄÄ. KOSKA VANHA LAITE ON POISTETTU NIIN PITÄÄ KÄYDÄ SEN TYÖMÄÄRÄYKSET LÄPI JA MERKATA NE POISTETUKSI KOSKA LAITETTA EI SIIS OLE OLEMASSA ENÄÄ.
}
else {
	$request['laite'] = hae_laite_ja_asiakastiedot($request['tilausrivi_tunnus']);
	$request['paikat'] = hae_paikat();

	echo_laitteen_vaihto_form($request);
}

require('../inc/footer.inc');

function luo_uusi_laite($uusi_laite) {
	global $kukarow, $yhtiorow;

	$uusi_laite['yhtio'] = $kukarow['yhtio'];
	$query = "INSERT INTO
				laite (".implode(", ", array_keys($uusi_laite)).")
				VALUES('".implode("', '", array_values($uusi_laite))."')";
	pupe_query($query);

	$query = "	SELECT laite.*,
				tuote.nimitys as tuote_nimitys,
				tuote.myyntihinta as tuote_hinta,
				tuote.try as tuote_try
				FROM laite
				JOIN tuote
				ON ( tuote.yhtio = laite.yhtio
					AND tuote.tuoteno = laite.tuoteno )
				WHERE laite.yhtio = '{$kukarow['yhtio']}'
				AND laite.tunnus = '".mysql_insert_id()."'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function aseta_tyomaarays_var($tilausrivi_tunnus, $var) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE tilausrivi
				SET var = '{$var}'
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tunnus = '{$tilausrivi_tunnus}'";
	pupe_query($query);
}

function luo_uusi_tyomaarays_rivi($tuote, $lasku_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	INSERT INTO tilausrivi
				SET hyllyalue = '',
				hyllynro = '',
				hyllyvali = '',
				hyllytaso = '',
				tilaajanrivinro = '',
				laatija = '{$kukarow['kuka']}',
				laadittu = now(),
				yhtio = '{$kukarow['yhtio']}',
				tuoteno = '{$tuote['tuoteno']}',
				varattu = '0',
				yksikko = '',
				kpl = '0',
				kpl2= '',
				tilkpl = '1',
				jt= '1',
				ale1 = '0',
				erikoisale = '0.00',
				alv = '0',
				netto= '',
				hinta = '{$tuote['hinta']}',
				kerayspvm = CURRENT_DATE,
				otunnus = '{$lasku_tunnus}',
				tyyppi = 'L',
				toimaika = CURRENT_DATE,
				kommentti = '',
				var = 'J',
				try= '{$tuote['try']}',
				osasto= '0',
				perheid= '0',
				perheid2= '0',
				tunnus = '0',
				nimitys = '{$tuote['tuote_nimitys']}',
				jaksotettu= ''";
	pupe_query($query);

	$toimenpide_tilausrivi_tunnus = mysql_insert_id();

	$query = "	INSERT INTO tilausrivin_lisatiedot
				SET yhtio= '{$kukarow['yhtio']}',
				positio = '',
				toimittajan_tunnus= '',
				tilausrivitunnus= '{$toimenpide_tilausrivi_tunnus}',
				jarjestys= '0',
				vanha_otunnus= '{$lasku_tunnus}',
				ei_nayteta= '',
				ohita_kerays = '',
				luontiaika= now(),
				laatija = '{$kukarow['kuka']}'";
	pupe_query($query);

	return $toimenpide_tilausrivi_tunnus;
}

function hae_laite_ja_asiakastiedot($tilausrivi_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	SELECT laite.*,
				tilausrivi.tunnus as tilausrivi_tunnus,
				paikka.nimi as paikka_nimi,
				paikka.tunnus as paikka_tunnus,
				kohde.nimi as kohde_nimi,
				asiakas.nimi as asiakas_nimi,
				lasku.tunnus as lasku_tunnus
				FROM tilausrivi
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
					AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
				JOIN lasku
				ON ( lasku.yhtio = tilausrivi.yhtio
					AND lasku.tunnus = tilausrivi.otunnus )
				JOIN laite
				ON ( laite.yhtio = tilausrivin_lisatiedot.yhtio
					AND laite.tunnus = tilausrivin_lisatiedot.asiakkaan_positio )
				JOIN paikka
				ON ( paikka.yhtio = laite.yhtio
					AND paikka.tunnus = laite.paikka )
				JOIN kohde
				ON ( kohde.yhtio = paikka.yhtio
					AND kohde.tunnus = paikka.kohde )
				JOIN asiakas
				ON ( asiakas.yhtio = kohde.yhtio
					AND asiakas.tunnus = kohde.asiakas )
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.tunnus = '{$tilausrivi_tunnus}'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function hae_vaihtotoimenpide_tuote() {
	global $kukarow, $yhtiorow;

	$query = "	SELECT tuote.*,
				tuote.nimitys as tuote_nimitys
				FROM tuote
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tuoteno = 'LAITTEEN_VAIHTO'";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}

function linkkaa_uusi_laite_vaihtotoimenpiteeseen($uusi_laite, $tilausrivi_tunnus) {
	global $kukarow, $yhtiorow;

	$query = "	UPDATE tilausrivi
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
					AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
				SET asiakkaan_positio = '{$uusi_laite['tunnus']}'
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.tunnus = '{$tilausrivi_tunnus}'";
	pupe_query($query);
}

function hae_paikat() {
	global $kukarow, $yhtiorow;

	$query = "	SELECT *
				FROM paikka
				WHERE yhtio = '{$kukarow['yhtio']}'";
	$result = pupe_query($query);

	$paikat = array();
	while ($paikka = mysql_fetch_assoc($result)) {
		$paikat[] = $paikka;
	}

	return $paikat;
}

function echo_laitteen_vaihto_form($request = array()) {
	global $kukarow, $yhtiorow, $oikeurow;

	echo "<form class='multisubmit' method='POST' action=''>";
	echo "<input type='hidden' name='tee' value='vaihda_laite' />";
	echo "<input type='hidden' name='tilausrivi_tunnus' value='{$request['laite']['tilausrivi_tunnus']}' />";
	echo "<input type='hidden' name='lasku_tunnus' value='{$request['laite']['lasku_tunnus']}' />";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Asiakas")."</th>";
	echo "<td>{$request['laite']['asiakas_nimi']}</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Kohde")."</th>";
	echo "<td>{$request['laite']['kohde_nimi']}</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Paikka")."</th>";
	echo "<td>{$request['laite']['paikka_nimi']}</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Vaihdettava laite")."</th>";
	echo "<td>{$request['laite']['tuoteno']}</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t("Uusi laite")."</th>";
//	$toim = 'laite';
//	$uusi = 1;
//	$valittu_paikka = $request['laite']['paikka_tunnus'];
//	require('yllapito.php');
	echo "<td>";
	echo '
	<table>
	<tbody>

	<tr>
	<th align="left">tuoteno</th>
	<td>
	<input type="text" name="uusi_laite[tuoteno]" value="" size="35" maxlength="60">
	</td>
	</tr>

	<tr>
	<th align="left">sarjanro</th>
	<td>
	<input type="text" name="uusi_laite[sarjanro]" value="" size="35" maxlength="60">
	</td>
	</tr>

	<tr>
	<th align="left">valm_pvm</th>
	<td>
	<input type="text" name="uusi_laite[valm_pvm]" value="" size="10" maxlength="10">
	</td>
	</tr>

	<tr>
	<th align="left">oma_numero</th>
	<td>
	<input type="text" name="uusi_laite[oma_numero]" value="" size="35" maxlength="20">
	</td>
	</tr>

	<tr>
	<th align="left">omistaja</th>
	<td>
	<input type="text" name="uusi_laite[omistaja]" value="" size="35" maxlength="60">
	</td>
	</tr>

	<tr>
	<th align="left">paikka</th>
	<td>';
	echo "<select name='uusi_laite[paikka]>";
	foreach ($request['paikat'] as $paikka) {
		$selected = ($paikka['tunnus'] == $request['laite']['paikka_tunnus']) ? 'SELECTED' : '';
		echo "<option value='{$paikka['tunnus']}' $selected>{$paikka['nimi']}</option>";
	}
	echo "</select>";
	echo '</td>
	</tr>

	<tr>
	<th align="left">sijainti</th>
	<td>
	<input type="text" name="uusi_laite[sijainti]" value="" size="35" maxlength="60">
	</td>
	</tr>

	</tbody>
	</table>';
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "<input type='submit' value='".t("Vaihda laite")."' />";
	echo "</form>";
}