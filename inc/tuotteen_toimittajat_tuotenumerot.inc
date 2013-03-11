<?php

	$otsikko = t('Tuotteen toimittajan vaihtoehtoiset tuotenumerot');
	$otsikko_nappi	= t("vaihtoehtoinen tuotenumero");

	if ($from == "") {
		$kentat = "tunnus, toim_tuoteno_tunnus, tuoteno, viivakoodi";
	}
	else {
		$kentat = "tunnus, tuoteno, viivakoodi";
	}

	$jarjestys = 'tuoteno, viivakoodi';

	$jarjestys = 'tuoteno';
