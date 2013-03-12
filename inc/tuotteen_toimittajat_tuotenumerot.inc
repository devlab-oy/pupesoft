<?php

	$otsikko = t('Toimittajan vaihtoehtoiset tuotekoodit');
	$otsikko_nappi	= t("vaihtoehtoinen tuotekoodi");

	if ($from == "") {
		$kentat = "tunnus, toim_tuoteno_tunnus, tuoteno, viivakoodi";
	}
	else {
		$kentat = "tunnus, tuoteno, viivakoodi";
	}

	$jarjestys = 'tuoteno, viivakoodi';

	$jarjestys = 'tuoteno';
