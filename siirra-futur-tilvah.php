#!/usr/bin/php
<?php

$handle1 = opendir("/home/tilvah-siirto") or die("/home/tilvah-siirto feilas\n");

// k�yd��n l�pi tilausvahvistus dirikka
while ($file = readdir($handle1)) {

  $fullfile = "/home/tilvah-siirto/$file";

  // jos l�ytyy file
  if (is_file($fullfile)) {

    // otetaan filenimest� homedirikan nimi
    list($home, $nimi) = explode("-", $file);

    // jos homedirikka l�ytyy
    if (is_dir("/home/$home")) {

      $cmd = "mv -f $fullfile /home/$home/in/$nimi";
      $result = system($cmd);

      if ($result != "") {
        echo "Virhe tilausvahvistuksen siirrossa! $result ($cmd)";
        system("mv -f $fullfile /home/tilvah-siirto/error"); // siirret��n error dirikkaan
      }
    }
    else {
      echo "Virhe tilausvahvistuksen siirrossa! Futursoft tilausvahvistus asiakkaalle $home mutta asiakkaan hakemistoa ei l�ydy!\n";
      system("mv -f $fullfile /home/tilvah-siirto/error"); // siirret��n error dirikkaan
    }
  }
}

// suljetaan handle
closedir($handle1);
