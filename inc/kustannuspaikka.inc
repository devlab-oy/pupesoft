<?php
  $otsikko = 'Tarkenteet';
  $kentat = 'tunnus, nimi, koodi, tyyppi, tunnus, kaytossa HIDDEN_kaytossa';
  $jarjestys = 'tyyppi, nimi';
  
  
  if ($nayta_poistetut == "") {
    $rajauslisa .= " and kustannuspaikka.kaytossa != 'E' ";
  }
  else {
    $rajauslisa  .= "";
  }
