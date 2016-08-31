<?php

echo "<h2>" . t('Aineistojen lähetys pankkiin') . "</h2>";

if ($tee == 'valitse') {
  $pankkiyhteys  = hae_pankkiyhteys($pankkiyhteys_tunnus);
  $tuetut_pankit = tuetut_pankit();
  $pankki        = $tuetut_pankit[$pankkiyhteys['pankki']];
  $files         = scandir("{$pupe_root_polku}/dataout/{$pankki['lyhyt_nimi']}_error");
  $files         = array_diff($files, array('.', '..'));

  echo "<div>";
  echo t("Pankkiin lähetettäviä tiedostoja on %s kappaletta", '', count($files));
  echo "</div>";
}
