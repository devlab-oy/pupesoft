<?php

$otsikko     = 'Toimitustavan lähdöt';
$otsikko_nappi   = 'toimitusaikojen lähtö';

$kentat   = "tunnus, liitostunnus, asiakasluokka, varasto, if(lahdon_viikonpvm = 0,'Sunnuntai',if(lahdon_viikonpvm = 1,'Maanantai',if(lahdon_viikonpvm = 2,'Tiistai',if(lahdon_viikonpvm = 3,'Keskiviikko',if(lahdon_viikonpvm = 4,'Torstai',if(lahdon_viikonpvm = 5,'Perjantai','Lauantai')))))) as 'Viikonpäivä', lahdon_kellonaika as 'Lähdön kellonaika', terminaalialue, aktiivi HIDDEN_aktiivi";
$jarjestys   = "varasto, lahdon_viikonpvm, terminaalialue, lahdon_kellonaika";

if ($nayta_poistetut == "") {
  $rajauslisa .= " and toimitustavan_lahdot.aktiivi != 'E' ";
}
else {
  $rajauslisa  .= "";
}
