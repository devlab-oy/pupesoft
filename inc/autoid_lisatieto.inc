<?php

	$otsikko = 'Autoid-lisätiedot';
	$otsikko_nappi = 'Autoid-lisatieto';

	$kentat = "tunnus, typnr, UPPER(tyyppi) tyyppi, pre, std, eco, IF(autoid_lukittu, '".t("Kyllä")."', '".t("Ei")."') autoid_lukittu";
	$jarjestys = 'typnr, tyyppi, pre, std, eco, autoid_lukittu';

