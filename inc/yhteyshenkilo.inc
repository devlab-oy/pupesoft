<?php

$otsikko     = 'Yhteyshenkilöt';
$otsikko_nappi   = 'yhteyshenkilö';

if ($from == "") {
  // HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen
  // lukitse_avaimeen muuttujat
  $kentat = "tunnus,
             if(tyyppi = 'T',
               (SELECT nimi FROM toimi
               WHERE yhteyshenkilo.yhtio = toimi.yhtio
               AND yhteyshenkilo.liitostunnus = toimi.tunnus
               AND yhteyshenkilo.tyyppi = 'T'),
               (SELECT nimi from asiakas
               WHERE yhteyshenkilo.yhtio = asiakas.yhtio
               AND yhteyshenkilo.liitostunnus = asiakas.tunnus
               AND yhteyshenkilo.tyyppi = 'A')) AS 'asiakas/toimittaja',
             nimi,
             titteli,
             concat_ws('<br>', puh, gsm, fax) AS 'puh/gsm/fax',
             email,
             www,
             nimitarkenne,
             osoite,
             postino,
             postitp,
             maa";

  $hakukentat = "tunnus,
                 liitostunnus,
                 nimi,
                 titteli,
                 puh/gsm/fax,
                 email,
                 www,
                 nimitarkenne,
                 osoite,
                 postino,
                 postitp,
                 maa";
}
else {
  // HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen
  // lukitse_avaimeen muuttujat
  $kentat = "tunnus,
             nimi,
             liitostunnus AS HIDDEN_liitostunnus";

  $hakukentat = "tunnus,
                 nimi,
                 liitostunnus";
}

if ($laji == "T") {
  $rajauslisa = " and yhteyshenkilo.tyyppi='T' ";
}
else {
  $rajauslisa = " and yhteyshenkilo.tyyppi='A' ";
}

$jarjestys = 'nimi';
