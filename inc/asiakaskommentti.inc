<?php

$otsikko     = 'Asiakkaan kommentit';
$otsikko_nappi   = 'asiakaskommentti';

if ($from == "") {
// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
$kentat = "asiakaskommentti.tunnus, 
  concat_ws('<br>',asiakaskommentti.ytunnus, 
    (SELECT LEFT(asiakas.nimi,25) 
    from asiakas
    where asiakaskommentti.yhtio = asiakas.yhtio
    and asiakaskommentti.ytunnus = asiakas.ytunnus
    limit 1)) Asiakas,
  concat_ws('<br>', asiakaskommentti.tuoteno, 
  (SELECT LEFT(tuote.nimitys,25)
  FROM tuote
  WHERE tuote.tuoteno = asiakaskommentti.tuoteno
  AND tuote.yhtio = asiakaskommentti.yhtio)) Tuote,
  asiakaskommentti.kommentti,
  if (asiakaskommentti.tyyppi = 'S', 'Sisäinen', '') Tyyppi";
}
else {
  // HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
  $kentat = "asiakaskommentti.tunnus, 
    asiakaskommentti.ytunnus, 
    concat_ws('<br>', asiakaskommentti.tuoteno, 
    (SELECT LEFT(tuote.nimitys,20)
    FROM tuote
    WHERE tuote.tuoteno = asiakaskommentti.tuoteno
    AND tuote.yhtio = asiakaskommentti.yhtio)) Tuote, 
    asiakaskommentti.kommentti,
    if (asiakaskommentti.tyyppi = 'S', 'Sisäinen', '') Tyyppi";
}

$hakukentat = 'tunnus, ytunnus, tuoteno, kommentti';
  
$jarjestys = 'ytunnus, tuoteno';
