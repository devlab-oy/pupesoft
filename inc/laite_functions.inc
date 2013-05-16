<?php

if (!function_exists('hae_laite')) {

	function hae_laite($laite_tunnus) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT laite.*,
					ta.selite as laite_tyyppi,
					ta2.selite as laite_koko
					FROM laite
					JOIN tuotteen_avainsanat ta
					ON ( ta.yhtio = laite.yhtio
						AND ta.tuoteno = laite.tuoteno
						AND ta.laji = 'sammutin_tyyppi' )
					JOIN tuotteen_avainsanat ta2
					ON ( ta2.yhtio = laite.yhtio
						AND ta2.tuoteno = laite.tuoteno
						AND ta2.laji = 'sammutin_koko' )
					WHERE laite.yhtio = '{$kukarow['yhtio']}'
					AND laite.tunnus = '{$laite_tunnus}'";
		$result = pupe_query($query);

		return mysql_fetch_assoc($result);
	}
}