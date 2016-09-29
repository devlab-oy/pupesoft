<?php

require "inc/parametrit.inc";

if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

  // Tämä on Pretaxin palkkaohjelmiston normaali siirtomuoto ver 2
  // Tuetaan myös M2 matkalaskuohjelmista
  // Tuetaan myös M2 matkalaskuohjelmista

  if ($_FILES['userfile']['size'] == 0) {
    die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
  }

  if ($tiedostomuoto == "EMCE") {
    $path_parts = pathinfo($_FILES['userfile']['name']);
    $ext = strtoupper($path_parts['extension']);

    $filerivit = pupeFileReader($_FILES['userfile']['tmp_name'], $ext);

    // Otetaan päivämäärä kakkosriviltä
    preg_match("/\- ([0-9]{2,2}\.[0-9]{2,2}\.[0-9]{4,4})/", $filerivit[1][3], $match);

    list($tpp, $tpk, $tpv) = explode(".", $match[1]);

    // Kolme ekaa rivi veks
    unset($filerivit[0]);
    unset($filerivit[1]);
    unset($filerivit[2]);
  }
  else {
    $file  = fopen($_FILES['userfile']['tmp_name'], "r") or die (t("Tiedoston avaus epäonnistui")."!");

    $filerivit = array();

    while ($rivi = fgets($file)) {
      $filerivit[] = $rivi;
    }

    fclose($file);
    unset($_FILES['userfile']['tmp_name']);
    unset($_FILES['userfile']['error']);
  }

  $maara = 1;
  $flip  = 0;

  foreach ($filerivit as $rivi) {

    //  M2 matkalaskuohjelma
    if ($tiedostomuoto == "M2MATKALASKU") {
      if (!isset($tpv)) {
        $tpv=substr($rivi, 639, 4);
        $tpk=substr($rivi, 643, 2);
        $tpp=substr($rivi, 645, 2);
      }

      if ($flip == 1) { // Seuraavalla rivillä tulee veronmäärä. Lisätään se!
        $maara--;
        $alv = (float) substr($rivi, 24, 12);
        if (substr($rivi, 23, 1) == 'K') $alv *= -1;
        $isumma[$maara] += $alv;
        $flip = 0;
      }
      else {
        $isumma[$maara] = (float) substr($rivi, 24, 12);
        if (substr($rivi, 23, 1) == 'K') $isumma[$maara] *= -1;
        $itili[$maara]  = (int) substr($rivi, 13, 4);
        $ikustp[$maara] = (int) substr($rivi, 228, 5);

        // Etsitäään vastaava kustannuspaikka
        $query = "SELECT tunnus
                  FROM kustannuspaikka
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and tyyppi    = 'P'
                  and kaytossa != 'E'
                  and nimi      = '$ikustp[$maara]'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 1) {
          $row = mysql_fetch_assoc($result);
          $ikustp[$maara] = $row["tunnus"];
        }

        $iselite[$maara] = "Matkalasku ". $tpp . "." . $tpk . "." . $tpv . " " . trim(substr($rivi, 240, 50)) . " " . trim(substr($rivi, 431, 60));
        $ivero[$maara] = (float) substr($rivi, 332, 5);
        if ($ivero[$maara] != 0.0) $flip = 1;
      }
    }
    // Tämä on Pretaxin palkkaohjelmiston normaali siirtomuoto ver 2 (Major Blue Palkat)
    elseif ($tiedostomuoto == "PRETAX") {
      if (!isset($tpv)) {
        $tpv=substr($rivi, 39, 4);
        $tpk=substr($rivi, 43, 2);
        $tpp=substr($rivi, 45, 2);
      }
      $isumma[$maara]  = (float) substr($rivi, 117, 16) / 100;
      $itili[$maara]   = (int) substr($rivi, 190, 7);

      // Kustannuspaikka
      $ikustp_tsk     = trim(substr($rivi, 198, 3));
      $ikustp[$maara]  = 0;

      if ($ikustp_tsk != "") {
        $query = "SELECT tunnus
                  FROM kustannuspaikka
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and tyyppi    = 'K'
                  and kaytossa != 'E'
                  and nimi      = '$ikustp_tsk'";
        $ikustpres = pupe_query($query);

        if (mysql_num_rows($ikustpres) == 1) {
          $ikustprow = mysql_fetch_assoc($ikustpres);
          $ikustp[$maara] = $ikustprow["tunnus"];
        }
      }

      if ($ikustp_tsk != "" and $ikustp[$maara] == 0) {
        $query = "SELECT tunnus
                  FROM kustannuspaikka
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and tyyppi    = 'K'
                  and kaytossa != 'E'
                  and koodi     = '$ikustp_tsk'";
        $ikustpres = pupe_query($query);

        if (mysql_num_rows($ikustpres) == 1) {
          $ikustprow = mysql_fetch_assoc($ikustpres);
          $ikustp[$maara] = $ikustprow["tunnus"];
        }
      }

      if (is_numeric($ikustp_tsk) and (int) $ikustp_tsk > 0 and $ikustp[$maara] == 0) {

        $ikustp_tsk = (int) $ikustp_tsk;

        $query = "SELECT tunnus
                  FROM kustannuspaikka
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and tyyppi    = 'K'
                  and kaytossa != 'E'
                  and tunnus    = '$ikustp_tsk'";
        $ikustpres = pupe_query($query);

        if (mysql_num_rows($ikustpres) == 1) {
          $ikustprow = mysql_fetch_assoc($ikustpres);
          $ikustp[$maara] = $ikustprow["tunnus"];
        }
      }

      $iselite[$maara] = "Palkkatosite ". $tpp . "." . $tpk . "." . $tpv;
    }
    // Tämä on Palkka.fi CSV siirtomuoto
    elseif ($tiedostomuoto == "PALKKAFI") {
      /*
        Aineisto sarkaineroteltu tekstitiedosto
        0 tyyppi
        1 tili
        2 tilin nimi
        3 summa
        4
        5
        6
        7
      */

      $kentat = explode(";", $rivi);

      //  Trimmataan kaikki
      foreach ($kentat as &$k) {
        $k = pupesoft_cleanstring(trim($k));
        $k = pupesoft_csvstring($k);
      }

      // Ekalla rivillä on otsikkotiedot
      if (empty($palkkafiekarivi)) {
        // Otetaan päivämäärävälin loppu päivämääräksi
        list($tpp, $tpk, $tpv) = explode(".", $kentat[6]);
        $palkkafiekarivi = TRUE;
        continue;
      }
      else {
        $itili[$maara]   = $kentat[1];
        $isumma[$maara]  = (float) str_replace(",", ".", $kentat[3]);
        $iselite[$maara] = "Palkkatosite ". $tpp . "." . $tpk . "." . $tpv;
        $selite = "Palkkatosite ". $tpp . "." . $tpk . "." . $tpv;
      }
    }
    // Tämä on EMCE KIrjanpitoyhteenveto xls-tiedosto
    elseif ($tiedostomuoto == "EMCE") {

      $kentat = $rivi;

      //  Trimmataan kaikki
      foreach ($kentat as &$k) {
        $k = pupesoft_cleanstring(trim($k));
        $k = pupesoft_csvstring($k);
      }

      if (!empty($kentat[0]) and (!empty($kentat[2]) or !empty($kentat[3]))) {
        // Tässä tulee päärivi. Eli koko summa, ksutannuspaikat yhteensä
        if (!empty($kentat[2])) {
          $isumma[$maara] = sprintf('%.2f', round($kentat[2], 2));
        }
        else {
          $isumma[$maara] = sprintf('%.2f', round($kentat[3] * -1, 2));
        }

        $itili[$maara] = $kentat[0];
        $ikustp[$maara] = 0;
        $iselite[$maara] = "Palkkatosite ". $tpp . "." . $tpk . "." . $tpv;
        $selite = "Palkkatosite ". $tpp . "." . $tpk . "." . $tpv;

        $edmaara = $maara;
      }
      elseif (!empty($edmaara) and !empty($kentat[6]) and is_numeric($kentat[6]) and !empty($kentat[7]) and !empty($kentat[8])) {
        // Tässä tulee ksutannuspaikkarivi
        $ikustp_tsk = $kentat[6];
        $ikustp[$maara] = 0;

        if ($ikustp_tsk != "") {
          $query = "SELECT tunnus
                    FROM kustannuspaikka
                    WHERE yhtio   = '$kukarow[yhtio]'
                    and tyyppi    = 'K'
                    and kaytossa != 'E'
                    and nimi      = '$ikustp_tsk'";
          $ikustpres = pupe_query($query);

          if (mysql_num_rows($ikustpres) == 1) {
            $ikustprow = mysql_fetch_assoc($ikustpres);
            $ikustp[$maara] = $ikustprow["tunnus"];
          }
        }

        if ($ikustp_tsk != "" and $ikustp[$maara] == 0) {
          $query = "SELECT tunnus
                    FROM kustannuspaikka
                    WHERE yhtio   = '$kukarow[yhtio]'
                    and tyyppi    = 'K'
                    and kaytossa != 'E'
                    and koodi     = '$ikustp_tsk'";
          $ikustpres = pupe_query($query);

          if (mysql_num_rows($ikustpres) == 1) {
            $ikustprow = mysql_fetch_assoc($ikustpres);
            $ikustp[$maara] = $ikustprow["tunnus"];
          }
        }

        if ($kentat[8] == "D") {
          $isumma[$maara] = sprintf('%.2f', round($kentat[7], 2));
        }
        else {
          $isumma[$maara] = sprintf('%.2f', round($kentat[7] * -1, 2));
        }

        $itili[$maara] = $itili[$edmaara];
        $iselite[$maara] = "Palkkatosite ". $tpp . "." . $tpk . "." . $tpv;

        // Vähennetään kustannuspaikalle tiliöitävä summa pääriviltä
        $isumma[$edmaara] = sprintf('%.2f', round($isumma[$edmaara] - $isumma[$maara], 2));

        // Pääriville ei jäänyt mitään, koko summa oli kustannuspaikoilla
        // Unsetataan siis päärivi
        if ($isumma[$edmaara] == 0) {
          unset($isumma[$edmaara]);
          unset($itili[$edmaara]);
          unset($iselite[$edmaara]);
          unset($ikustp[$edmaara]);
          unset($edmaara);
        }
      }
      else {
        continue;
      }
    }
    elseif ($tiedostomuoto == "AMMATTILAINEN") {

      /*
        Aineisto sarkaineroteltu tekstitiedosto
        0 tilinumero
        1 kustannuspaikka
        2 selite
        3 summa
        4 summa 2
        5 tositepvm
        6 projekti
        7 henkilö
      */

      // Haistellaan onko puolipisteerottelu tai tabierottelu
      $kentat = explode("\t", $rivi);

      if (count($kentat) < 5) {
        $kentat = explode(";", $rivi);
      }

      //  Trimmataan kaikki
      foreach ($kentat as &$k) {
        $k = pupesoft_cleanstring(trim($k));
        $k = pupesoft_csvstring($k);
      }

      // Tili
      $itili[$maara] = $kentat[0];

      // Poimitaan kustannuspaikka ja projekti, perustetaan jos puuttuu
      $ikustp[$maara]   = "";
      $iprojekti[$maara]  = "";

      foreach (array(1=>"K", 6=>"P") as $x => $tsk_tyyppi) {

        $tsk_nimi = $kentat[$x];

        if (strlen($tsk_nimi) > 0) {

          if ($tsk_tyyppi == "K") {
            $tsk_tyyppinimi = "Kustannuspaikka";
          }
          elseif ($tsk_tyyppi == "P") {
            $tsk_tyyppinimi = "Projekti";
          }

          //  tarkastetaan löytyykö oikea tsk!
          $tsk = "";

          if ($tsk_nimi != "") {
            $query = "SELECT tunnus
                      FROM kustannuspaikka
                      WHERE yhtio   = '$kukarow[yhtio]'
                      and tyyppi    = '$tsk_tyyppi'
                      and kaytossa != 'E'
                      and nimi      = '$tsk_nimi'";
            $tskres = pupe_query($query);

            if (mysql_num_rows($tskres) == 1) {
              $tskrow = mysql_fetch_assoc($tskres);
              $tsk = $tskrow["tunnus"];
            }
          }

          if ($tsk_nimi != "" and $tsk == 0) {
            $query = "SELECT tunnus
                      FROM kustannuspaikka
                      WHERE yhtio   = '$kukarow[yhtio]'
                      and tyyppi    = '$tsk_tyyppi'
                      and kaytossa != 'E'
                      and koodi     = '$tsk_nimi'";
            $tskres = pupe_query($query);

            if (mysql_num_rows($tskres) == 1) {
              $tskrow = mysql_fetch_assoc($tskres);
              $tsk = $tskrow["tunnus"];
            }
          }

          if (is_numeric($tsk_nimi) and (int) $tsk_nimi > 0 and $tsk == 0) {

            $tsk_nimi = (int) $tsk_nimi;

            $query = "SELECT tunnus
                      FROM kustannuspaikka
                      WHERE yhtio   = '$kukarow[yhtio]'
                      and tyyppi    = '$tsk_tyyppi'
                      and kaytossa != 'E'
                      and tunnus    = '$tsk_nimi'";
            $tskres = pupe_query($query);

            if (mysql_num_rows($tskres) == 1) {
              $tskrow = mysql_fetch_assoc($tskres);
              $tsk = $tskrow["tunnus"];
            }
          }

          if ($tsk == 0) {
            echo "<font class='error'>".t("Kustannuspaikkaa ei löydy").": $tsk_nimi</font><br>";
          }
          else {
            if ($tsk_tyyppi == "K") {
              $ikustp[$maara] = $tsk;
            }
            elseif ($tsk_tyyppi == "P") {
              $iprojekti[$maara] = $tsk;
            }
          }
        }
      }

      // Summa
      if ($kentat[3] != "") {
        $isumma[$maara]  = (float) str_replace(",", ".", $kentat[3]);
      }
      else {
        $isumma[$maara]  = ((float) str_replace(",", ".", $kentat[4]));
      }

      //Tositepvm
      if (!isset($tpv)) {
        $tpv=substr($kentat[5], 6, 4);
        $tpk=substr($kentat[5], 3, 2);
        $tpp=substr($kentat[5], 0, 2);
      }

      // Selite
      $iselite[$maara] = "Palkkatosite $tpp.$tpk.$tpv / ".$kentat[2];
      $selite = "Palkkatosite $tpp.$tpk.$tpv / ".$kentat[2];
      // Liitetään tähän henkilönumero jos se haluttiin aineistoon
      if (!empty($kentat[7])) {
        $iselite[$maara] .= " / #".$kentat[7];
      }
    }
    elseif ($tiedostomuoto == "TIKON") {
     /*
       Aineisto puolipiste tekstitiedosto
       00 PVM      Windows muotoinen päiväys
       01 TOSITE   Kp:n muoto LL-TTTTTTT esim. 10-123456, 5, 10-9
       02 TILI     6-numeroa
       03 EUR      numeromuotoinen
       04 KPA      8-merkkiä
       05 KUSTLAJI 6-merkkiä
       06 PR       8-merkkiä
       07 PRL      6-merkkiä
       08 R3       8-merkkiä
       09 R3L      6-merkkiä
       10 R4       8-merkkiä
       11 R4L      6-merkkiä
       12 JAKSO    VVKK 9609
       13 MÄÄRÄ    numeromuotoinen, yksi desimaali
       14 MÄÄRÄ2   numeromuotoinen, yksi desimaali
       15 MÄÄRÄ3   numeromuotoinen, yksi desimaali
       16 ASIAKAS  9-numeroa
       17 LASKU    Tikon laskunmuoto LL-TTTTTT
       18 SELITE   72-merkkiä
     */

      // Haistellaan onko puolipisteerottelu tai tabierottelu
      $kentat = explode(";", $rivi);

      // Trimmataan kaikki
      foreach ($kentat as &$k) {
        $k = pupesoft_cleanstring($k);
      }

      if ($kentat[2] == "TILI") {
        continue;
      }

      // Tili
      $itili[$maara] = $kentat[2];

      // Poimitaan kustannuspaikka ja projekti, perustetaan jos puuttuu
      $ikustp[$maara]   = "";
      $iprojekti[$maara]  = "";

      // Summa
      $isumma[$maara]  = (float) str_replace(",", ".", $kentat[3]);

      // Tositepvm
      if (!isset($tpv)) {
        list($tpp, $tpk, $tpv) = explode(".", $kentat[0]);
      }

      // Kustannuspaikka
      $ikustp_tsk = $kentat[4];
      $ikustp[$maara] = 0;

      if ($ikustp_tsk != "") {
        $query = "SELECT tunnus
                  FROM kustannuspaikka
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and tyyppi    = 'K'
                  and kaytossa != 'E'
                  and nimi      = '$ikustp_tsk'";
        $ikustpres = pupe_query($query);

        if (mysql_num_rows($ikustpres) == 1) {
          $ikustprow = mysql_fetch_assoc($ikustpres);
          $ikustp[$maara] = $ikustprow["tunnus"];
        }
      }

      if ($ikustp_tsk != "" and $ikustp[$maara] == 0) {
        $query = "SELECT tunnus
                  FROM kustannuspaikka
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and tyyppi    = 'K'
                  and kaytossa != 'E'
                  and koodi     = '$ikustp_tsk'";
        $ikustpres = pupe_query($query);

        if (mysql_num_rows($ikustpres) == 1) {
          $ikustprow = mysql_fetch_assoc($ikustpres);
          $ikustp[$maara] = $ikustprow["tunnus"];
        }
      }

      // Selite
      $iselite[$maara] = "Palkkatosite $tpp.$tpk.$tpv / ".$kentat[18];
      $selite = "Palkkatosite $tpp.$tpk.$tpv / ".$kentat[18];
    }

    $maara++;
  }

  // Tositteelle kommentti ekalta riviltä
  $comments = $iselite[1];
  $valkoodi = $yhtiorow["valkoodi"];

  // Poistetaan tyhjät päärivit välistä.
  if ($tiedostomuoto == "EMCE") {
    $isumma = array_values($isumma);
    $itili = array_values($itili);
    $iselite = array_values($iselite);
    $ikustp = array_values($ikustp);

    // Nollaindexiä ei käsitellä, joten laitetaan sinne skeidaa
    array_unshift($isumma, "");
    array_unshift($itili, "");
    array_unshift($iselite, "");
    array_unshift($ikustp, "");
  }

  $gokfrom = "palkkatosite"; // Pakotetaan virhe
  $tee = 'I';

  require 'tosite.php';
  exit;
}

echo "<font class='head'>".t("Palkka- ja matkalaskuaineiston sisäänluku")."</font><hr>";
echo "<form method='post' name='sendfile' enctype='multipart/form-data'>
    <table>
    <tr><th>".t("Valitse tiedostomuoto")."</th><td>
    <select name = 'tiedostomuoto'>
    <option value ='PRETAX'>Pretax palkkatosite</option>
    <option value ='AMMATTILAINEN'>Ammattilainen/Aboa/Heeros palkanlaskenta</option>
    <option value ='PALKKAFI'>Palkka.fi (Kirjanpidon tosite CSV)</option>
    <option value ='EMCE'>EmCe Palkkahallinto</option>
    <option value ='TIKON'>Tikon kirjanpito</option>
    <option value ='M2MATKALASKU'>M2 Matkalasku</option>
    </select>
    </td></tr>
    <tr><th>".t("Valitse tiedosto").":</th>
      <td><input name='userfile' type='file'></td>
      <td class='back'><input type='submit' value='".t("Lähetä")."'></td>
    </tr>
    </table>
    </form>";

require "inc/footer.inc";
