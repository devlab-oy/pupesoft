<?php

  $ulos = "";
  $jatko = 1; // oletetaan normaali käsittely
  $tyyppi = 1; // oletetaan rivin näkyvyys

  if (mysql_field_name($result, $i) == "yhtio") {
    $tyyppi = 0;
  }

  if (mysql_field_name($result, $i) == "kayttaja" or
    mysql_field_name($result, $i) == "kaytettyaika") {
    $tyyppi = 2;
  }
