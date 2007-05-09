<?php
	$otsikko = 'Tilikartta';
	$kentat = "	tunnus, tilino, nimi, sisainen_taso,
				(select nimi from taso where yhtio='$kukarow[yhtio]' and tyyppi='S' and taso = BINARY sisainen_taso) sisainen_nimi, 
				ulkoinen_taso,
				(select nimi from taso where yhtio='$kukarow[yhtio]' and tyyppi='U' and taso = BINARY ulkoinen_taso) ulkoinen_nimi";
	$jarjestys = 'tilino';
?>