<?php

//print "<pre>";

/*
Kenttä  Tieto          Pakollisuus  Muoto  Pituus  E/P  Sisältö
-------------------------------------------------------------------------------------------
1    Sovellustunnus      *       AN     4     EP   LUM2 Palautteella VLU2
2    Tietuetunnus       *       N     1     EP   9
3    Aineistolaji       *       N     1     EP   0 tai 9 = palaute
4    Varalla                AN     1     EP
5    Maksajan Y-tunnus     *       AN     11     EP
6    Asiakastunnuksen lisäosa        AN     5     EP
7    Varalla                AN     2     EP
8    Hyväksymiskoodi            N     1     P   0 = hyväksytty 4 = hylätty
9    Maksujen lukumäärä     *       N     5     EP
10    Laskujen lukumäärä          N     5     EP   Jos on laskutietueita
11    Maksujen valuuttamäärä   *       N     15     EP   13 kok.+ 2 desim.
12    Varalla                AN     549   EP
-------------------------------------------------------------------------------------------
    Yhteensä                  600
*/

$ulos  = "LUM2";                        // Sovellustunnus
$ulos .= "9";                                                   // Tietuetunnus
$ulos .= "0";                                                   // Aineistolaji
$ulos .= sprintf ('%1s',     ' ');                           // Varalla
  $ulos .= sprintf ('%011.11s',  $ytunnus);                      // Maksajan Y-tunnus
$ulos .= sprintf ('%5.5s',    $lum_st_5kentta);              // Asiakastunnuksen lisäosa
$ulos .= sprintf ('%2s',     ' ');                           // Varalla
$ulos .= sprintf ('%01d',     '0');                           // Hyväksymiskoodi
$ulos .= sprintf ('%05d',     $makskpl);                      // Maksujen lukumäärä
$ulos .= sprintf ('%05d',     '0');                           // Laskujen lukumäärä
$ulos .= sprintf ('%015d',    round($makssumma * 100, 0));    // Maksujen valuuttamäärä
$ulos .= sprintf ('%549s',     ' ');                           // Varalla

//print strlen($ulos) . "/" . $ulos;
//print "</pre>";

fputs($toot,$ulos . "\r\n");
