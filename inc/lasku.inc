<?php
$otsikko = 'Laskut';
$otsikko_nappi = 'lasku';

$uusilukko = "LUKOSSA";

$vuosisitten = date("Y-m-01", mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));

if ($laji == "T") {
  $kentat = "tunnus, tunnus 'tilaus', laskunro, tapvm, ytunnus, nimi, summa, valkoodi";
  $hakukentat = "tunnus, tunnus, laskunro, tapvm, ytunnus, nimi, summa, valkoodi";

  $jarjestys = 'laskunro DESC';

  $rajauslisa = " and lasku.tila = 'L' and lasku.alatila='X' and lasku.tapvm >= '$vuosisitten' ";
}
else {
  $kentat = 'tunnus, laskunro, tapvm, ytunnus, nimi, summa, valkoodi, tunnus';
  $jarjestys = 'laskunro DESC';

  if ($haku[1] == '') {
    $rajauslisa = " and lasku.tila = 'U' and lasku.alatila='X' and lasku.tapvm >= '$vuosisitten' ";
  }
  else {
    $rajauslisa = " and lasku.tila = 'U' and lasku.alatila='X' ";
  }
}
