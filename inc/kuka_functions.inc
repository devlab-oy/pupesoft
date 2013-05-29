<?php

if (!function_exists('hae_kayttajaan_linkitetty_extranet_kayttaja')) {

	function hae_kayttajaan_linkitetty_extranet_kayttaja($kuka) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT kuka.*
					FROM kuka
					JOIN extranet_kayttajan_lisatiedot
					ON ( extranet_kayttajan_lisatiedot.yhtio = kuka.yhtio
					AND extranet_kayttajan_lisatiedot.liitostunnus = kuka.tunnus
					AND extranet_kayttajan_lisatiedot.selite = '{$kuka}'
					AND extranet_kayttajan_lisatiedot.laji = 'LINKITYS' )
					WHERE kuka.yhtio = '{$kukarow['yhtio']}'";
		$result = pupe_query($query);

		return mysql_fetch_assoc($result);
	}
}