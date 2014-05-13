<?php

  //print "<pre>";

  /*
  Kentt‰  Tieto          Pakollisuus  Muoto  Pituus  E/P  Sis‰ltˆ
  -------------------------------------------------------------------------------------------
  1    Sovellustunnus      *       AN     4     EP   LUM2 Palautteella VLU2
  2    Tietuetunnus       *       N     1     EP   9
  3    Aineistolaji       *       N     1     EP   0 tai 9 = palaute
  4    Varalla                AN     1     EP
  5    Maksajan Y-tunnus     *       AN     11     EP
  6    Asiakastunnuksen lis‰osa        AN     5     EP
  7    Varalla                AN     2     EP
  8    Hyv‰ksymiskoodi            N     1     P   0 = hyv‰ksytty 4 = hyl‰tty
  9    Maksujen lukum‰‰r‰     *       N     5     EP
  10    Laskujen lukum‰‰r‰          N     5     EP   Jos on laskutietueita
  11    Maksujen valuuttam‰‰r‰   *       N     15     EP   13 kok.+ 2 desim.
  12    Varalla                AN     549   EP
  -------------------------------------------------------------------------------------------
      Yhteens‰                  600
  */

  $ulos  = "LUM2";                        // Sovellustunnus
  $ulos .= "9";                                                   // Tietuetunnus
  $ulos .= "0";                                                   // Aineistolaji
  $ulos .= sprintf ('%1s',     ' ');                           // Varalla
    $ulos .= sprintf ('%011.11s',  $ytunnus);                      // Maksajan Y-tunnus
  $ulos .= sprintf ('%5.5s',    $lum_st_5kentta);              // Asiakastunnuksen lis‰osa
  $ulos .= sprintf ('%2s',     ' ');                           // Varalla
  $ulos .= sprintf ('%01d',     '0');                           // Hyv‰ksymiskoodi
  $ulos .= sprintf ('%05d',     $makskpl);                      // Maksujen lukum‰‰r‰
  $ulos .= sprintf ('%05d',     '0');                           // Laskujen lukum‰‰r‰
  $ulos .= sprintf ('%015d',    round($makssumma * 100, 0));    // Maksujen valuuttam‰‰r‰
  $ulos .= sprintf ('%549s',     ' ');                           // Varalla

  //print strlen($ulos) . "/" . $ulos;
  //print "</pre>";

  fputs($toot,$ulos . "\r\n");
