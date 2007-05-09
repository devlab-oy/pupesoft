<?php
	$otsikko = 'Tilikartta';
	$kentat = "tunnus, tilino, nimi, (select concat_ws(' - ', taso, nimi) from taso where yhtio='$kukarow[yhtio]' and tyyppi='S' and taso=sisainen_taso) sisainen_taso, (select concat_ws(' - ', taso, nimi) from taso where yhtio='$kukarow[yhtio]' and tyyppi='U' and taso=ulkoinen_taso) ulkoinen_taso";
	$jarjestys = 'tilino';
?>