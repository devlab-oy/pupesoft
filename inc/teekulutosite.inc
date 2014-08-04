<?php

// Kirjoitetaan lasku
$selite = str_replace("'", " ", $vientiselite); // Poistaa SQL-virheen mahdollisuuden

$tpv = substr($pvm, 0, 2);
$tpk = substr($pvm, 2, 2);
$tpp = substr($pvm, 4, 2);

if ($tpv < 1000) $tpv += 2000;

$query = "INSERT into lasku set
          yhtio         = '$yritirow[yhtio]',
          nimi          = 'Kulu $yritirow[nimi] $maksaa',
          hyvak1        = '$yritirow[hyvak]',
          hyvaksyja_nyt = '$yritirow[hyvak]',
          tapvm         = '$tpv-$tpk-$tpp',
          tila          = 'X',
          laatija       = 'tiliote',
          luontiaika    = now()";
$result = pupe_query($query);
$kulutunnus = mysql_insert_id();

list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yritirow["oletus_rahatili"]);

// Rahatili
$query = "INSERT into tiliointi set
          yhtio    = '$yritirow[yhtio]',
          ltunnus  = '$kulutunnus',
          tilino   = '$yritirow[oletus_rahatili]',
          kustp    = '{$kustp_ins}',
          kohde    = '{$kohde_ins}',
          projekti = '{$projekti_ins}',
          tapvm    = '$tpv-$tpk-$tpp',
          summa    = '$maara',
          vero     = 0,
          lukko    = '',
          selite   = '$selite',
          laatija  = 'tiliote',
          laadittu = now()";
$result = pupe_query($query);
$vasumma = $maara * -1;

list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yritirow["oletus_kulutili"], $yritirow["oletus_kustp"], $yritirow["oletus_kohde"], $yritirow["oletus_projekti"]);

// Pankin kuluja
$query = "INSERT into tiliointi set
          yhtio    = '$yritirow[yhtio]',
          ltunnus  = '$kulutunnus',
          tilino   = '$yritirow[oletus_kulutili]',
          kustp    = '{$kustp_ins}',
          kohde    = '{$kohde_ins}',
          projekti = '{$projekti_ins}',
          tapvm    = '$tpv-$tpk-$tpp',
          summa    = '$vasumma',
          vero     = 0,
          lukko    = '',
          selite   = '$selite',
          laatija  = 'tiliote',
          laadittu = now()";
$result = pupe_query($query);
$_tiliointi_id = mysql_insert_id();

$query = "UPDATE tiliotedata SET
          kasitelty       = now(),
          tiliointitunnus = {$_tiliointi_id}
          WHERE tunnus    = '$tiliotedataid'";
$dummyresult = pupe_query($query);

$query = "UPDATE tiliotedata
          SET kuitattu = 'tiliote',
          kuitattuaika = now()
          WHERE yhtio  = '$yritirow[yhtio]'
          AND perheid  = '$tiliotedatape'";
$dummyresult = pupe_query($query);

// Merkataan käsitellyiksi & lisätään linkki
echo "<font class='message'>*** ".t("tosite luotu")." ***</font><br>\n";
