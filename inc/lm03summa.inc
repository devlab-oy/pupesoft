<?php

//print "<pre>";

$ulos = "LM03";                              // 01 Aineistotunnus
$ulos .= "9";                              // 02 Tietuetunnus (Summatietue)
$ulos .= "0";                              // 03 Tapahtumalaji
$ulos .= sprintf('%-14.14s', $lmp_st_4kentta);              // 04 Maksajan tilinumero
$ulos .= sprintf('%09d', $yrityytunnus);                // 05 Maksajan tunnus
$ulos .= sprintf('%02d', substr($luonti['year'], 2, 2));        // 06 Tiedoston luontipäivä vv
$ulos .= sprintf('%02d', $luonti['mon']);                // 06 Tiedoston luontipäivä kk
$ulos .= sprintf('%02d', $luonti['mday']);                // 06 Tiedoston luontipäivä pp
$ulos .= sprintf('%06d', $makskpl);                    // 07 Maksujen kappalemäärä
$ulos .= sprintf('%013d', round($makssumma * 100, 0));          // 08 Maksujen rahamäärä
$ulos .= sprintf('%6.6s', '');                      // 09 Varalla
$ulos .= sprintf('%13.13s', '');                    // 10 Varalla
$ulos .= sprintf('%6.6s', '');                      // 11 Varalla
$ulos .= sprintf('%-35.35s', $luonti['hours'] . $luonti['minutes']);  // 12 Erän yksilöintitieto
$ulos .= sprintf('%17.17s', '');                    // 13 OVT-tunnus (ei käytössä)
$ulos .= sprintf('%169.169s', '');                    // 14 Varalla

//print strlen($ulos) . "/" . $ulos;
//print "</pre>";

fputs($toot, $ulos."\r\n");
