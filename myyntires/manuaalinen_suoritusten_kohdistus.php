<?php
require "../inc/parametrit.inc";

$oikeus = true; // requireissa tarkistetaan tämän avulla, onko käyttäjällä oikeutta tehdä ko. toimintoa

if ($tila == 'tee_kohdistus') {
  require('manuaalinen_suoritusten_kohdistus_tee_kohdistus.php');
}
if ($tila == 'suorituksenvalinta') {
  require('manuaalinen_suoritusten_kohdistus_suorituksen_valinta.php');
}

if ($tila == 'kohdistaminen') {
  require('manuaalinen_suoritusten_kohdistus_suorituksen_kohdistus.php');
}

if ($tila == '') { // asiakkaan valintasivu
  require('manuaalinen_suoritusten_kohdistus_asiakkaan_valinta.php');
}

?>
