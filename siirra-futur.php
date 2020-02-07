#!/usr/bin/php
<?php

$handle1 = opendir("/home") or die("/home feilas\n");

while ($file = readdir($handle1)) {

  // katotaan löytyykö homedirikasta OUT -hakemisto
  if (is_dir("/home/$file/out")) {

    // dirikka löyty, katotaan onko siellä filejä
    $handle2 = opendir("/home/$file/out");

    while ($nimi = readdir($handle2)) {

      $nimi = "/home/$file/out/$nimi";

      if (is_file($nimi)) {

        // faili löyty, avataan se lukua varten
        $fp = fopen($nimi, "r") or die ("$nimi ei aukea!\n");
        $fileok = 1; // lippu päälle

        // luetaan filen rivit
        while (!feof($fp)) {
          $rivi = fgets($fp);

          if (substr($rivi, 0, 3) == "*IE" or substr($rivi, 0, 9) == "ICHG__END") {
            // jos löydettiin tiedoston loppu, voidaan file siirtää
            $fileok = 0; // lippu alas
            break;
          }
        }

        // suljetaan file
        fclose($fp);

        // jos file oli ok, siirretään se
        if ($fileok == 0) {

          $conn_id = ftp_connect("JOKU IPOSOITE TAHAN");

          // jos connectio ok, kokeillaan loginata
          if ($conn_id) {
            $login_result = ftp_login($conn_id, "elma", "eokttt");
          }

          // jos login ok kokeillaan uploadata
          if ($login_result) {
            $upload = ftp_put($conn_id, "elma/edi/autolink_orders/".basename($nimi), $nimi, FTP_ASCII);
          }

          if ($conn_id) {
            ftp_close($conn_id);
          }

          // jos mikätahansa feilas niin echotaan virhe
          if ($conn_id === FALSE or $login_result === FALSE or $upload === FALSE) {
            echo "Futursoft tilauksen $nimi siirto epäonnistui!\n";
          }
          else {
            // jos onnistui dellataan file pois
            system("rm -f $nimi");
          }

        }
        else {

          // Jos faili on vanhempi kuin 2 minuuttia nin dellataan se!
          if (time() - filemtime($nimi) > 120) {
            echo "VIRHE: Futursoft tilauksesta $nimi on puuttunut loppumerkki tai siirto on ollut kesken yli 2 minuuttia, tiedosto poistettiin!\n";
            system("rm -f $nimi");
          }
          else {
            echo "VIRHE: Futursoft tilauksesta $nimi puuttuu loppumerkki tai siirto on vielä kesken!\n";
          }
        }
      }
    }

    // suljetaan home handle
    closedir($handle2);
  }

}

// suljetaan handle
closedir($handle1);
