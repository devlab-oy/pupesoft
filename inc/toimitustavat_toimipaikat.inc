<?php

$otsikko   = "Toimitustapojen toimipaikat";
$kentat     = "tunnus, (SELECT selite FROM toimitustapa WHERE toimitustapa.yhtio = '{$kukarow['yhtio']}' AND toimitustapa.tunnus = toimitustapa_tunnus) toimitustapa_tunnus, IF(toimipaikka_tunnus = 0, '".t('Ei toimipaikkaa')."', (SELECT nimi FROM yhtion_toimipaikat WHERE yhtion_toimipaikat.yhtio = '{$kukarow['yhtio']}' AND yhtion_toimipaikat.tunnus = toimipaikka_tunnus)) toimipaikka";
$jarjestys   = "toimitustapa_tunnus ASC, toimipaikka_tunnus ASC";
