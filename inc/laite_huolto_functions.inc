<?php

function hae_laitteelle_mahdolliset_huoltosyklit($laite_tyyppi = '', $laite_koko = '', $paikan_olosuhde = '') {
	global $kukarow, $yhtiorow;

	$huoltosykli_where = "";
	if ($laite_tyyppi != '') {
		$huoltosykli_where .= "	AND huoltosykli.tyyppi = '{$laite_tyyppi}'";
	}
	if ($laite_koko != '') {
		$huoltosykli_where .= "	AND huoltosykli.koko = '{$laite_koko}'";
	}
	if ($paikan_olosuhde != '') {
		$huoltosykli_where .= "	AND huoltosykli.olosuhde = '{$paikan_olosuhde}'";
	}
	
	$query = "	SELECT huoltosykli.tunnus,
				concat_ws(' - ', tuote.nimitys, huoltosykli.tyyppi, huoltosykli.koko,
					(
						SELECT selitetark
						FROM avainsana
						WHERE avainsana.yhtio = huoltosykli.yhtio
						AND avainsana.selite = huoltosykli.olosuhde
						AND avainsana.laji = 'OLOSUHDE'
					)
				) as dropdown_text,
				huoltosykli.huoltovali,
				huoltosykli.pakollisuus
				FROM huoltosykli
				JOIN tuote
				ON ( tuote.yhtio = huoltosykli.yhtio
					AND tuote.tuoteno = huoltosykli.toimenpide )
				WHERE huoltosykli.yhtio = '{$kukarow['yhtio']}'
				{$huoltosykli_where}";
	$huoltosykli_result = pupe_query($query);
	$huoltosyklit = array();
	while ($huoltosykli = mysql_fetch_assoc($huoltosykli_result)) {
		$huoltosyklit[] = $huoltosykli;
	}

	return $huoltosyklit;
}
