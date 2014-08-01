<?php
//Kun tullaan tänne tarvitaan $komento joka on se komento jolla tulostetaan esim. lpr -P tarrakirjoitin

$paikka = strtoupper($paikka);

$viivapaikka = str_replace('-', '.', $paikka);

if (strlen($paikka) <= 12) {
  $sivu = "
*** ETIKETIN KOKO ***
{D0230,0800,0200|}
{AX;+000,+000,+00|}
{AY;+05,0|}
{C|}

*** VIIVAKOODI ***
{XB00;0020,0010,9,3,03,0,0080,+0000000000,000,0,00|}
{RB00;$viivapaikka|}

*** TEKSTI ***
{PC000;0003,0190,30,40,G,00,B|}
{RC000;$paikka|}

*** TULOSTUS  ***
{XS;I,0001,0002C3200|}

".chr(12);
}
else {
  $sivu = "
*** ETIKETIN KOKO ***
{D0230,0800,0200|}
{AX;+000,+000,+00|}
{AY;+05,0|}
{C|}

*** VIIVAKOODI ***
{XB00;0020,0010,9,3,02,0,0080,+0000000000,000,0,00|}
{RB00;$viivapaikka|}

*** TEKSTI ***
{PC000;0003,0190,20,40,G,00,B|}
{RC000;$paikka|}

*** TULOSTUS  ***
{XS;I,0001,0002C3200|}

".chr(12);
}

// Käytetään lpr funktiota
lpr($sivu, 0, $komento);
