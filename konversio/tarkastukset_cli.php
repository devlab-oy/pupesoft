<?php

$filepath = dirname(__FILE__);
require_once("{$filepath}/../inc/functions.inc");

$tiedostot = lue_tiedostot('/tmp/konversio/tarkastukset/');
foreach ($tiedostot as $tiedosto) {
  echo $tiedosto."\n";
  exec("php tarkastukset.php {$tiedosto}");
}

function lue_tiedostot($polku) {
  $tiedostot = array();
  $handle = opendir($polku);
  if ($handle) {
    while (false !== ($tiedosto = readdir($handle))) {
      if ($tiedosto != "." && $tiedosto != ".." && $tiedosto != '.DS_Store') {
        if (is_file($polku.$tiedosto)) {
          $tiedostot[] = $polku.$tiedosto;
        }
      }
    }
    closedir($handle);
  }

  return $tiedostot;
}
