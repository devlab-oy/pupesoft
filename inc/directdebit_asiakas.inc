<?php
$otsikko = 'Asiakkaan Direct Debit-sopimukset';
$otsikko_nappi = 'sopimus';

$kentat = 'tunnus, (SELECT nimitys from directdebit where directdebit_asiakas.yhtio=directdebit.yhtio and directdebit_asiakas.directdebit_id=directdebit.tunnus) directdebit_id';
$hakukentat = "tunnus, liitostunnus";

$jarjestys = 'tunnus';
