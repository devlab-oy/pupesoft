#!/usr/bin/php
<?php

$handle1 = opendir("/home/tilvah-siirto") or die("/home/tilvah-siirto feilas\n");

// käydään läpi tilausvahvistus dirikka
while ($file = readdir($handle1)) {

  $fullfile = "/home/tilvah-siirto/$file";

  // jos löytyy file
  if (is_file($fullfile)) {

    // otetaan filenimestä homedirikan nimi
    list($home, $nimi) = explode("-", $file);

    // jos homedirikka löytyy
    if (is_dir("/home/$home")) {

      $cmd = "mv -f $fullfile /home/$home/in/$nimi";
      $result = system($cmd);

      if ($result != "") {
        echo "Virhe tilausvahvistuksen siirrossa! $result ($cmd)";
        system("mv -f $fullfile /home/tilvah-siirto/error"); // siirretään error dirikkaan
      }
    }
    else {
      echo "Virhe tilausvahvistuksen siirrossa! Futursoft tilausvahvistus asiakkaalle $home mutta asiakkaan hakemistoa ei löydy!\n";
      system("mv -f $fullfile /home/tilvah-siirto/error"); // siirretään error dirikkaan
    }
  }
}

// suljetaan handle
closedir($handle1);
