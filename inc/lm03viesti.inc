<?php

  //print "<pre>";

  $ulos = "LM03";                        // 01 Aineistotunnus
  $ulos .= sprintf('%1.1s', $laskutapahtuma{0});        // 02 Tietuetunnus (1 = maksutapahtuma, 2 = erittelytapahtuma)
  $ulos .= "9";                        // 03 Tapahtumalaji (9 = viestitietue)
    $ulos .= sprintf('%-14.14s', $lmp_mt_4kentta);        // 04 Sampon speksissä Maksajan tilinumero / Nordean speksissä "varalla"
  $ulos .= sprintf('%-30.30s', $laskunimi1);          // 05 Saajan tarkenne 1 / Nordean speksissä "varalla"
  $ulos .= sprintf('%-20.20s', $laskunimi2);          // 06 Saajan tarkenne 2 / Nordean speksissä "varalla"
  $ulos .= sprintf('%-20.20s', $laskunimi3);          // 07 Saajan tarkenne 3 / Nordean speksissä "varalla"
  $ulos .= sprintf('%-14.14s', $laskutilno);          // 08 Saajan tilinumero
  $ulos .= sprintf('%35.35s', $viestitietue_viesti_1);    // 09 Viesti 1
  $ulos .= sprintf('%35.35s', $viestitietue_viesti_2);    // 10 Viesti 2
  $ulos .= sprintf('%35.35s', $viestitietue_viesti_3);    // 11 Viesti 3
  $ulos .= sprintf('%35.35s', $viestitietue_viesti_4);    // 12 Viesti 4
  $ulos .= sprintf('%35.35s', $viestitietue_viesti_5);    // 13 Viesti 5
  $ulos .= sprintf('%21.21s', '');              // 14 Varalla

  //print strlen($ulos) . "/" . $ulos;
  //print "</pre>";

  fputs($toot, $ulos."\r\n");
