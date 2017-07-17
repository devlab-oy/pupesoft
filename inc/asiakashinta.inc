<?php

$otsikko     = 'Asiakkaan sopimushinnat';
$otsikko_nappi   = 'asiakashinta';

if ($from == "") {
  // HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
  $kentat = "asiakashinta.tunnus,
  concat_ws('<br>', asiakashinta.asiakas,
  (SELECT asiakas.nimi
   FROM asiakas
   WHERE asiakashinta.yhtio = asiakas.yhtio
   AND asiakashinta.asiakas = asiakas.tunnus)) Asiakas,
  asiakashinta.ytunnus 'Ytunnus',
  (SELECT concat(dynaaminen_puu.tunnus, '<br>', dynaaminen_puu.nimi)
   FROM dynaaminen_puu
   WHERE dynaaminen_puu.tunnus = asiakashinta.asiakas_segmentti
   AND dynaaminen_puu.yhtio = asiakashinta.yhtio) 'Asiakaspuu',
  (SELECT concat(avainsana.selite, '<br>', avainsana.selitetark) FROM avainsana
   WHERE avainsana.laji = 'asiakasryhma'
   AND avainsana.selite = asiakashinta.asiakas_ryhma
   AND avainsana.yhtio = asiakashinta.yhtio
   AND avainsana.kieli = '{$yhtiorow["kieli"]}') 'Asiakasryhma',
  (SELECT concat(avainsana.selite, '<br>', avainsana.selitetark)
   FROM avainsana
   WHERE avainsana.laji = 'piiri'
   AND avainsana.selite = asiakashinta.piiri
   AND avainsana.yhtio = asiakashinta.yhtio
   AND avainsana.kieli = '{$yhtiorow["kieli"]}') 'Piiri',
  (SELECT concat(tuote.tuoteno, '<br>', LEFT (tuote.nimitys,35))
   FROM tuote
   WHERE tuote.tuoteno = asiakashinta.tuoteno
   AND tuote.yhtio = asiakashinta.yhtio) 'Tuoteno',
  asiakashinta.ryhma 'Alennusryhmä',
  asiakashinta.hinta,
  asiakashinta.laji,
  concat_ws('<br>', asiakashinta.minkpl, asiakashinta.maxkpl) 'Min/Maxkpl',
  concat_ws('<br>', asiakashinta.alkupvm, asiakashinta.loppupvm) 'Alku/Loppu'";
}
else {
  // HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
  $kentat = "asiakashinta.tunnus,
  concat_ws('<br>', asiakashinta.tuoteno,
    (SELECT LEFT(tuote.nimitys,25)
    FROM tuote
    WHERE tuote.tuoteno = asiakashinta.tuoteno
    AND tuote.yhtio = asiakashinta.yhtio)
  , asiakashinta.ryhma) 'Tuoteno/Alennusryhmä',
  asiakashinta.hinta,
  asiakashinta.laji,
  concat_ws('<br>', asiakashinta.minkpl, asiakashinta.maxkpl) 'Min/Maxkpl',
  concat_ws('<br>', asiakashinta.alkupvm, asiakashinta.loppupvm) 'Alku/Loppu'";
}

// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
$hakukentat = "tunnus, asiakas, ytunnus, asiakas_segmentti,asiakas_ryhma,piiri, tuoteno, ryhma, hinta, laji, minkpl/maxkpl, alkupvm/loppupvm";

$jarjestys = 'ytunnus, asiakas_ryhma, tuoteno, ryhma, laji, minkpl, alkupvm, loppupvm';

if ($nayta_eraantyneet != "") {
  $rajauslisa = " and asiakashinta.loppupvm > '0000-00-00' and asiakashinta.loppupvm < now() ";
}
