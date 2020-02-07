<?php

require "functions.inc";

$yhtio   = mysql_real_escape_string(trim($_GET['yhtio']));
$tuoteno = mysql_real_escape_string(trim($_GET['tuoteno']));
$maara   = (int) $_GET['maara'];

if ($yhtio != "") {
  $kukarow["yhtio"] = $yhtio;
}
else {
  $kukarow["yhtio"] = "artr";
}

if ($tuoteno != '') {

  $con = mysql_connect("193.185.248.50", "pupesoft", "pupe1") or die("Yhteys tietokantaan epaonnistui!!");
  mysql_select_db("pupesoft") or die ("Tietokanta ei löydy palvelimelta..");

  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '$kukarow[yhtio]'
            AND tuoteno = '$tuoteno'";
  $result = mysql_query($query) or die($query);

  if (mysql_num_rows($result) == 1) {

    // katotaan paljonko on myytävissä
    list(, , $myytavissa) = saldo_myytavissa($tuoteno);

    // jos meillä on tarpeeksi myytävää
    if ($myytavissa >= $maara and $myytavissa > 0) {
      echo "SAATAVUUS=1\n";
    }
    elseif ($myytavissa > 0) {
      echo "SAATAVUUS=2\n";
    }
    else {
      echo "SAATAVUUS=0\n";
    }

    // haetaan korvaavia tuotteita
    $query  = "SELECT *
               FROM korvaavat use index (yhtio_tuoteno)
               WHERE yhtio = '$kukarow[yhtio]'
               AND tuoteno = '$tuoteno'";
    $kores  = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($kores) > 0) {

      $kkrow  = mysql_fetch_array($kores);
      $query  = "SELECT tuoteno
                 FROM korvaavat use index (yhtio_id)
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND id      = '$kkrow[id]'
                 ORDER BY jarjestys, tuoteno";
      $kores  = mysql_query($query) or pupe_error($query);
      $nexti  = 0;

      while ($korow = mysql_fetch_array($kores)) {
        if ($nexti == 1) {
          echo "KORVAAVA=$korow[tuoteno]\n";
          $nexti = 2; // muutetaan lippu niin tiedetään että seuraava löyty
          break;
        }
        if ($korow['tuoteno'] == $tuoteno) {
          $nexti = 1; // meidän tulee ottaa seuraava tuote, koska se on tämän tuotteen jälkeen seuraava korvaava
        }
      }

      // ei löydetty nextiä vaikka ois pitäny, oltiin ilmeisesti sitte vikassa tuotteessa, haetaan eka korvaava
      if ($nexti == 1) {
        $query = "SELECT tuoteno
                  FROM korvaavat use index (yhtio_id)
                  WHERE yhtio  = '$kukarow[yhtio]'
                  AND id       = '$kkrow[id]'
                  AND tuoteno != '$tuoteno'
                  ORDER BY jarjestys, tuoteno
                  LIMIT 1";
        $kores  = mysql_query($query) or pupe_error($query);

        if (mysql_num_rows($kores) == 1) {
          $korow = mysql_fetch_array($kores);
          echo "KORVAAVA=$korow[tuoteno]\n";
        }
      }
    }

    /*
      //Haetaan varastot ja chekataan missä maassa kamaa on
      $query  = "select count(*) lkm, group_concat(tunnus SEPARATOR ',') tunnukset, maa ";
      $query .= "from varastopaikat ";
      $query .= "where yhtio = '$kukarow[yhtio]' and tyyppi = '' ";
      $query .= "group by maa order by maa";
      $varres  = mysql_query($query) or pupe_error($query);

      while ($varrow = mysql_fetch_array($varres)) {
        if ($varrow["lkm"] > 0) {
          $tunnus = explode(",", $varrow["tunnukset"]);
          $myytavissa_yht = 0;

          for ($i=0; $i < $varrow["lkm"]; $i++) {
            list(, , $myytavissa) = saldo_myytavissa($tuoteno, '', $tunnus[$i]);
            $myytavissa_yht += $myytavissa;
          }

          // jos meillä on tarpeeksi myytävää
          if ($myytavissa_yht >= $maara and $myytavissa_yht > 0) {
            echo "SAATAVUUS_".$varrow['maa']."=1\n";
          }
          elseif ($myytavissa_yht > 0) {
            echo "SAATAVUUS_".$varrow['maa']."=2\n";
          }
          else {
            echo "SAATAVUUS_".$varrow['maa']."=0\n";
          }
        }

      }
*/

  } // end löytyykö
  else {
    // tuotetta ei löydy
    echo "SAATAVUUS=-1\n";
  }

} // end if tuoteno
