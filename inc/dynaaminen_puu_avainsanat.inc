<?php

$otsikko    = 'Dynaamisen puun avainsanat';
$otsikko_nappi  = 'dynaamisen puun avainsana';

$kentat = "dynaaminen_puu_avainsanat.tunnus,
(SELECT selitetark from avainsana where dynaaminen_puu_avainsanat.yhtio = avainsana.yhtio and dynaaminen_puu_avainsanat.laji = avainsana.selite and avainsana.laji = 'DPAVAINSANALAJI' LIMIT 1) laji,
dynaaminen_puu_avainsanat.kieli,
dynaaminen_puu_avainsanat.avainsana,
dynaaminen_puu_avainsanat.tarkenne,
dynaaminen_puu_avainsanat.liitostunnus HIDDEN_liitostunnus";

$hakukentat = "dynaaminen_puu_avainsanat.tunnus, dynaaminen_puu_avainsanat.laji, dynaaminen_puu_avainsanat.kieli, dynaaminen_puu_avainsanat.avainsana, dynaaminen_puu_avainsanat.tarkenne, dynaaminen_puu_avainsanat.liitostunnus";

$jarjestys = 'dynaaminen_puu_avainsanat.laji, dynaaminen_puu_avainsanat.avainsana, dynaaminen_puu_avainsanat.tarkenne';
