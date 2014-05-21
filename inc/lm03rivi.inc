<?php

//print "<pre>";

$ulos = "LM03";                        // 01 Aineistotunnus
$ulos .= sprintf('%1.1s', substr($laskutapahtuma, 0, 1));        // 02 Tietuetunnus (1 = maksutapahtuma, 2 = erittelytapahtuma)
$ulos .= sprintf('%1.1s', substr($laskutapahtuma, 1, 1));        // 03 Tapahtumalaji (0 = maksu, 2 = hyvitys)
$ulos .= sprintf('%-14.14s', $lmp_mt_4kentta);        // 04 Sampon speksissä Maksajan tilinumero / Nordean speksissä "varalla"
$ulos .= sprintf('%-30.30s', $laskunimi1);          // 05 Saajan tarkenne 1
$ulos .= sprintf('%-20.20s', $laskunimi2);          // 06 Saajan tarkenne 2
$ulos .= sprintf('%-20.20s', $laskunimi3);          // 07 Saajan tarkenne 3
$ulos .= sprintf('%-14.14s', $laskutilno);          // 08 Saajan tilinumero
$ulos .= sprintf('%3.3s', '');                // 09 Sampon speksissä Maksunaihe / Nordean Speksissä "varalla"
$ulos .= sprintf('%1.1s', $laskutyyppi);          // 10 Viestityyppi (Viestityypit: 1, 2, 5, 6, 7)
$ulos .= sprintf('%-70.70s', $laskuviesti);          // 11 Viesti
$ulos .= sprintf('%2.2s', '');                // 12 Varalla
$ulos .= sprintf('%02d', substr($luonti['year'], 2, 2));  // 13 Sampon speksissä Eräpäivä vv / Nordean Speksissä "varalla"
$ulos .= sprintf('%02d', $luonti['mon']);          // 13 Sampon speksissä Eräpäivä kk / Nordean Speksissä "varalla"
$ulos .= sprintf('%02d', $luonti['mday']);          // 13 Sampon speksissä Eräpäivä pp / Nordean Speksissä "varalla"
$ulos .= sprintf('%012d', round($laskusumma * 100, 0));    // 14 Rahasumma
$ulos .= sprintf('%1.1s', '');                // 15 Varalla
$ulos .= sprintf('%4.4s', '');                // 16 Varalla
$ulos .= sprintf('%12.12s', '');              // 17 Varalla
$ulos .= sprintf('%-20.20s', $laskusis1);          // 18 Sisäinen viite
$ulos .= sprintf('%-20.20s', $laskusis2);          // 19 Kustannuspaikka
$ulos .= sprintf('%5.5s', '');                // 20 Sampon speksissä Postinumero (Pakollinen maksuosoituksissa)    HUOM: Kentän tyyppi N maksutapahtuma-tietueelle (tietuetunnus 1), mutta kenttä on "varalla" sekä tyyppiä AN erittelytapahtuma-tietueessa (tietuetunnus 2) / Nordean Speksissä "varalla"
$ulos .= sprintf('%10.10s', '');              // 21 Sampon speksissä Postitoimipaikka (Pakollinen maksuosoituksissa)  HUOM: Kenttä on "varalla" erittelytapahtuma-tietueessa (tietuetunnus 2) / Nordean Speksissä "varalla"
$ulos .= sprintf('%30.30s', '');              // 22 Sampon speksissä Saajan jakeluosoite                HUOM: Kenttä on "varalla" erittelytapahtuma-tietueessa (tietuetunnus 2) / Nordean Speksissä "varalla"

//print strlen($ulos) . "/" . $ulos;
//print "</pre>";

fputs($toot, $ulos."\r\n");
