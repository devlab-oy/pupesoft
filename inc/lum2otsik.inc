<?php

  //print "<pre>";

  /*
  Kenttä  Tieto              Pakollisuus  Muoto  Pituus  E/P  Sisältö
  --------------------------------------------------------------------------------------------------------
  1     Sovellustunnus           *      AN    4     EP  LUM2 Palautteella VLU2
  2     Tietuetunnus           *       N     1     EP   0
  3     Aineistolaji           *       N     1     EP  0=eräsyöttö 9=palaute
  4     Varalla                    AN    1     EP
  5     Maksajan Y-tunnus        *       AN     11     EP  Etunollilla ja ilman väliviivaa
  6     Asiakastunnuksen lisäosa          AN    5     EP
  7     Varalla                    AN     2     EP
  8     Hyväksymiskoodi                N     1     P   0=hyväksytty 4=hylätty
  9     Aineiston/palautteen luontiaika  *       N     10     EP   vvkkpphhmm
  10     Varalla                    N     14     EP
  11     Veloituksen päivämäärä            N     8     EP vvvvkkpp
  12     Varalla                    AN     542   EP
  --------------------------------------------------------------------------------------------------------
      Yhteensä                      600
  */

  $ulos  = "LUM2";                      // Sovellustunnus
  $ulos .= "0";                                           // Tietuetunnus
  $ulos .= "0";                                           // Aineistolaji
  $ulos .= sprintf ('%1.1s',     ' ');                   // Varalla
    $ulos .= sprintf ('%011.11s',  $ytunnus);          // Maksajan Y-tunnus
  $ulos .= sprintf ('%5.5s',    $lum_et_5kentta);      // Asiakastunnuksen lisäosa
  $ulos .= sprintf ('%2.2s',     ' ');                   // Varalla
  $ulos .= sprintf ('%1.1s',     '0');                   // Hyväksymiskoodi

  $luonti = getdate();

  // Pvm 10 merkkiä
  $ulos .= sprintf ('%02d', substr($luonti['year'], 2, 2));  // Aineiston/palautteen luontiaika
  $ulos .= sprintf ('%02d', $luonti['mon']);
  $ulos .= sprintf ('%02d', $luonti['mday']);
  $ulos .= sprintf ('%02d', $luonti['hours']);
  $ulos .= sprintf ('%02d', $luonti['minutes']);

  $ulos .= sprintf ('%014d', '0');              // Varalla
  $ulos .= sprintf ('%04d', $luonti['year']);          // Veloituksen päivämäärä (Sampolla kenttä on vain tyyppiä "Varalla")
  $ulos .= sprintf ('%02d', $luonti['mon']);
  $ulos .= sprintf ('%02d', $luonti['mday']);
  $ulos .= sprintf ('%542s', ' ');              // Varalla

  //print strlen($ulos) . "/" . $ulos;
  //print "</pre>";

  fputs($toot,$ulos . "\r\n");
