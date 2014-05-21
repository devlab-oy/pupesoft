<?php

//  print "<pre>";

/*
Kenttä  Tieto                Pakollisuus  Muoto  Pituus  E/P    Sisältö
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
1    Sovellustunnus            *       AN     4     EP     LUM2 Palautteella VLU2
2     Tietuetunnus             *       N     1     EP     1
3     Hyväksymiskoodi                  N     1     P     0=hyväksytty 4=hylätty 5=poistettu
4    Saajan nimi ja osoite         *       AN     140   EP     4x35
5    Saajan maan maakoodi         *       AN     2     EP
6    Saajan pankin SWIFT-koodi              AN     11     EP     8 tai 11 merkkiä
7    Saajan pankin nimi ja paikkakunta          AN     140   EP     4x35
8    Saajan tilinumero                  AN     34     EP
9    Maksun aihe saajalle tiedoksi            AN     140   EP     4x35
10    Maksun valuuttamäärä         *       N     15     EP     13 kok.+ 2 desim.
11    Valuuttakoodi (ISO-standardi)     *       AN     3     EP
12    Varalla                      AN     3     EP
13    Kurssisopimusnumero                AN     14     EP
14    Maksutyyppi             *       AN     1     EP     M=maksumääräys P=pikamääräys Q=SWIFT-shekki S=shekki T=tilisiirto Nordeassa (Suomi) K=oma tilisiirto Nordeassa (Suomi)
15    Palvelumaksu             *       AN     1     EP     J tai T
16    Veloituksen päivämäärä              AN     8     EP     ppkkvvvv
17    Maksun vasta-arvo                 N     15     P     13 kok.+ 2 desim.
18    Maksun kurssi                    N     11     P     5 kok.+ 6 desim.
19    Veloitettava tili           *       N     14     EP
20    Veloitettavan tilin valuuttalaji   *       AN     3     EP
21    Veloitettava määrä                N     15     EP     13 kok.+ 2 desim.
22    Arkistotunnus tai hylkäyksen syy          AN     20     P
23    Palautteen rahayksikkö              AN     1     EP
24    Varalla                      AN     3     EP
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    Yhteensä                        600
*/

$ulos  = "LUM2";                          // Sovellustunnus
$ulos .= "1";                                                       // Tietuetunnus
$ulos .= "0";                                                       // Hyväksymiskoodi
$ulos .= sprintf ('%-140.140s',  $laskunimi1." ".$laskunimi2." ".$laskunimi3); // Saajan nimi ja osoite
$ulos .= sprintf ('%-2.2s',    strtoupper($laskumaakoodi));        // Saajan maan maakoodi
$ulos .= sprintf ('%-11.11s',  strtoupper($laskuswift));           // Saajan pankin SWIFT-koodi
$ulos .= sprintf ('%-35.35s',  $laskupankki1);                     // Saajan pankin nimi ja paikkakunta
$ulos .= sprintf ('%-35.35s',  $laskupankki2);
$ulos .= sprintf ('%-35.35s',  $laskupankki3);
$ulos .= sprintf ('%-35.35s',  $laskupankki4);
$ulos .= sprintf ('%-34.34s',  strtoupper($laskutilino));          // Saajan tilinumero
$ulos .= sprintf ('%-140.140s',  $laskuaihe);                        // Maksun aihe saajalle tiedoksi
$ulos .= sprintf ('%015d',     round($laskusumma * 100, 0));       // Maksun valuuttamäärä
$ulos .= sprintf ('%-3.3s',   strtoupper($laskuvaluutta));        // Valuuttakoodi (ISO-standardi)
$ulos .= sprintf ('%3s',    '');                // Varalla
$ulos .= sprintf ('%14s',    '');                // Kurssisopimusnumero
$ulos .= sprintf ('%-1.1s',   $lum_maksutapa);          // Maksutyyppi
$ulos .= "J";                            // Palvelumaksu
  $ulos .= sprintf ('%02d', substr($maksupvm, 8, 2));          // Veloituksen päivämäärä (laitetaan numeerinen nollalla pädätty vaikka speksissä on alpha!)
  $ulos .= sprintf ('%02d', substr($maksupvm, 5, 2));
$ulos .= sprintf ('%04d', substr($maksupvm, 0, 4));
$ulos .= sprintf ('%015d',     '0');                 // Maksun vasta-arvo
$ulos .= sprintf ('%011d',     '0');                 // Maksun kurssi
$ulos .= sprintf ('%14.14s',   $yritystilino);            // Veloitettava tili
$ulos .= sprintf ('%-3.3s',   strtoupper($laskuyritivaluutta));   // Veloitettavan tilin valuuttalaji
$ulos .= sprintf ('%015d',     '0');                               // Veloitettava määrä
$ulos .= sprintf ('%20s',     ' ');                               // Arkistotunnus tai hylkäyksen syy
$ulos .= "1";                            // Palautteen rahayksikkö
$ulos .= sprintf ('%-3.3s',   ' ');                               // Varalla

//print strlen($ulos) . "/" . $ulos;

fputs($toot,$ulos . "\r\n");

// Jos meillä on ohjeita pankille, niin lisätään maksutietueen lisätietue
if (trim($ohjeitapankille) != '') {

  /*
  Kenttä  Tieto                  Pakollisuus  Muoto  Pituus  E/P    Sisältö
  -----------------------------------------------------------------------------------------------------------------------
  1     Sovellustunnus               *          AN    4     EP     LUM2 Palautteella VLU2
  2     Tietuetunnus               *          AN    1     EP     L
  3     Hyväksymiskoodi                       N      1     P     0=hyväksytty 4=hylätty 5=poistettu
  4     Veloituskurssi                       N     11     P     5 kok.+ 6 desim.
  5     Varalla                           N     20     EP
  6     Ohjeita pankille                       AN     350   EP    10x35
  7     Asiakkaan omat tiedot                     AN     50     EP    16+34
  8     Vastaanottaja-/shekkipankin SWIFT-koodi           AN     11     P
  9     Kentän 8 nimi ja osoite                   AN     70     P    2x35
  10     Maksun numero                         AN     14     P
  11     Ulkomaisen pankin arvopäivä                 AN     8     P    ppkkvvvv
  12     Nordea Pankin palvelumaksu                 N     15     P
  13     Palvelumaksun veloitustili                  N     14     P
  14     Varalla                           AN     31     EP
  -----------------------------------------------------------------------------------------------------------------------
       Yhteensä                    600
  */

  $ohjeitapankille = str_replace(array("\n", "\r", "\t"), " ", trim($ohjeitapankille));

  $ulos  = "LUM2";                  // Sovellustunnus
  $ulos .= "L";                                       // Tietuetunnus
  $ulos .= "0";                                       // Hyväksymiskoodi
  $ulos .= sprintf ('%011d',    '0');               // Veloituskurssi
  $ulos .= sprintf ('%020d',    '0');               // Varalla
  $ulos .= sprintf ('%-350.350s', $ohjeitapankille);  // Ohjeita pankille
  $ulos .= sprintf ('%-50.50s',   ' ');               // Asiakkaan omat tiedot
  $ulos .= sprintf ('%-11.11s',   ' ');               // Vastaanottaja-/shekkipankin SWIFT-koodi
  $ulos .= sprintf ('%-70.70s',   ' ');               // Kentän 8 nimi ja osoite
  $ulos .= sprintf ('%-14.14s',   ' ');               // Maksun numero
  $ulos .= sprintf ('%-8.8s',   ' ');               // Ulkomaisen pankin arvopäivä
  $ulos .= sprintf ('%015d',     '0');               // Nordea Pankin palvelumaksu
  $ulos .= sprintf ('%014d',     '0');               // Palvelumaksun veloitustili
  $ulos .= sprintf ('%-31.31s',   ' ');               // Varalla

  //print strlen($ulos) . "/" . $ulos;

  fputs($toot,$ulos . "\r\n");
}

//print "</pre>";
