<?php

$otsikko       = "Tuotteen toimittajat";
$otsikko_nappi = "tuotteen toimittaja";

if ($nayta_nimitys == "on") {
  $nimitys_kentta = "nimitys,";
  $nimitys_lisa   =
    "(SELECT nimitys
    FROM tuote
    WHERE tuote.yhtio = tuotteen_toimittajat.yhtio
    AND tuote.tuoteno = tuotteen_toimittajat.tuoteno ) AS nimitys,";
}
else {
  $nimitys_kentta = "";
  $nimitys_lisa   = "";
}

// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen
// lukitse_avaimeen muuttujat
$kentat = "tunnus,
           tuoteno,
           {$nimitys_lisa}
           toim_tuoteno,
           (SELECT nimi
            FROM toimi
            WHERE tuotteen_toimittajat.yhtio = toimi.yhtio
            AND tuotteen_toimittajat.liitostunnus = toimi.tunnus) AS nimi";

$hakukentat = "tunnus, tuoteno, {$nimitys_kentta} toim_tuoteno, nimi";

$jarjestys = 'tuoteno';
