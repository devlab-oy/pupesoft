<?php

//Kun tullaan tänne tarvitaan $komento joka on se komento jolla tulostetaan esim. lpr -P tarrakirjoitin
//ja tarvitaan myös $tuoteno

if ($malli == 'Zebra_hylly') {
  $query = "  SELECT tuote.*, concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) tuotepaikka
        FROM tuote
        JOIN tuotepaikat on (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno and tuotepaikat.oletus = 'X')
        WHERE tuote.yhtio = '$kukarow[yhtio]'
        AND tuote.tuoteno = '$tuoteno'";
}
else {
  $query = "  SELECT tuote.*
        FROM tuote
        WHERE tuote.yhtio = '$kukarow[yhtio]'
        AND tuote.tuoteno = '$tuoteno'";
}

$tarrares = pupe_query($query);

// haetaan yhtiötiedot Pupesta
$yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);

if (mysql_num_rows($tarrares) == 1) {
  $tarrarow = mysql_fetch_array($tarrares);

  // jos tullaan yksinkertaisesta näkymästä tulostetaan tuoteno eankoodin sijaan
  if ($toim == 'YKS') {
    $tarrarow['eankoodi'] = $tarrarow['tuoteno'];
  }

  // jos eankoodi kenttä on tyhjä ei viittitä edes lähteä tulostelemaan
  if (($tarrarow['eankoodi'] != 0 or $tarrarow['eankoodi'] != "0") and $tarrarow['eankoodi'] != '') {
    $eankoodi = trim($tarrarow['eankoodi']);
    $nimitys = $tarrarow['nimitys'];
    $nimitys2 = '';

    // jos nimitys on pidempi kuin 25 merkkiä niin se ei mahdu yhdelle riville vaan pitää jakaa kahtia
    if (strlen($nimitys) > 25 and $malli == 'Zebra') {
      if (strpos($nimitys, " ")) {
        $nimipalat = explode(' ',$nimitys);

        $merkkimaara = 0;
        $nimitys = "";
        $nimitys2 = "";

        foreach ($nimipalat as $nimipala) {
          if (strlen($nimitys)+strlen($nimipala) <= 25 or $merkkimaara == 0) {
            $nimitys .= $nimipala." ";
          }
          else {
            $nimitys2 .= $nimipala." ";
          }
          $merkkimaara += strlen($nimipala);
        }
      }

      $nimitys = substr($nimitys,0,25);
      $nimitys2 = substr($nimitys2,0,25);
    }
    $tuoteno = $tarrarow['tuoteno'];

    // tässä tehdään aineisto jota Zebra tulostin tajuaa
    /* Esimerkki
    D15
    N
    B125,0,0,3,2,5,45,B,"HC3310CPT40H     "
    A560,0,0,2,1,2,N,
    A120,75,0,3,1,2,N,"PALL PROFILE STAR        ""  ""         0"
    A120,115,0,3,1,2,N,"SUODATINPANOS            "
    P1
    */

    if ($malli == 'Zebra') {
      $sivu =  "D15\n"; //DENSITY eli kirjainten vahvuus
      $sivu .= "N\n";
      $sivu .= "B125,0,0,3,2,5,45,B,"; //alku
      $sivu .= "\"";
      $sivu .= sprintf("%-17s",$tuoteno);
      $sivu .= "\"";
      $sivu .= "\n";
      $sivu .= "A560,0,0,2,1,2,N,";
      $sivu .= "\n";
      $sivu .= "A120,75,0,3,1,2,N,";
      $sivu .= "\"";
      $sivu .= sprintf("%-25s",$nimitys);
      $sivu .= "\"\"";
      $sivu .= "  ";
      $sivu .= "\"\"";
      $sivu .= sprintf("%10s",$tarrarow["tarrakpl"]);
      $sivu .= "\"";
      $sivu .= "\n";
      $sivu .= "A120,115,0,3,1,2,N,";
      $sivu .= "\"";
      $sivu .= sprintf("%-25s",$nimitys2);
      $sivu .= "\"";
      $sivu .= "\n";
      $sivu .= "P".$tkpl;
      $sivu .= "\n";

      //konvertoidaan ääkköset printterin ymmärtämään muotoon
      $from = array ('ä','å','ö','Ä','Å','Ö');
      $to   = array (chr(132),chr(134),chr(148),chr(142),chr(143),chr(153));   // DOS charset
      $sivu = str_replace($from, $to, $sivu);                  // Tehdään käännös

      $sivu = escapeshellarg($sivu);
    }
    elseif ($malli == 'Zebra_hylly') {
      // tämä haara on Zebra_hyllytarra, joka liimataan hyllyyn
      // Toimii ja suunniteltu mallille GX20t
      // Tarrankoko 57 mm x 51 mm

      // Ohjelmointimanuaali löytyy : http://www.zebra.com/id/zebra/na/en/index/products/printers/desktop/gx420t.4.tabs.html
      // "Programmin guide"

      $pituus_nimitys = 40; // voidaan määrittää mistä kohdasta katkaistaan nimitys.

      if (strlen($nimitys) > $pituus_nimitys ) {
        if (strpos($nimitys, " ")) {
          $nimipalat = explode(' ',$nimitys);

          $merkkimaara = 0;
          $nimitys = "";
          $nimitys2 = "";

          foreach ($nimipalat as $nimipala) {
            if (strlen($nimitys)+strlen($nimipala) <= $pituus_nimitys or $merkkimaara == 0) {
              $nimitys .= $nimipala." ";
            }
            else {
              $nimitys2 .= $nimipala." ";
            }
            $merkkimaara += strlen($nimipala);
          }
        }

        $nimitys = substr($nimitys,0,$pituus_nimitys);
        $nimitys2 = substr($nimitys2,0,$pituus_nimitys);
      }

      $pituus_tuoteno = 25; // voidaan määrittää mistä kohdasta katkaistaan tuoteno.

      if (strlen($tuoteno) > $pituus_tuoteno ) {
        if (strpos($tuoteno, " ")) {
          $nimipalat = explode(' ',$tuoteno);

          $merkkimaara = 0;
          $tuoteno = "";
          $tuoteno2 = "";

          foreach ($nimipalat as $nimipala) {
            if (strlen($tuoteno)+strlen($nimipala) <= $pituus_tuoteno or $merkkimaara == 0) {
              $tuoteno .= $nimipala." ";
            }
            else {
              $tuoteno2 .= $nimipala." ";
            }
            $merkkimaara += strlen($nimipala);
          }
        }

        $tuoteno = substr($nimitys,0,$pituus_tuoteno);
        $tuoteno2 = substr($nimitys2,0,$pituus_tuoteno);
      }

      $sivu  = "^XA\n";    // vakio alku, pakollinen
      $sivu .= "^LH100\n";  // offset vasemmasta
      $sivu .= "^LT000\n";  // offset ylhäältä
      $sivu .= "^FO100,40\n^AQN,18,8\n^FD$nimitys\n^FS";
      $sivu .= "^FO100,63\n^AQN,18,8\n^FD$nimitys2\n^FS";
      $sivu .= "^FO100,100\n^ASN,20,10\n^FDTuoteno:\n^FS";
      $sivu .= "^FO100,130\n^ARN,20,10\n^FD$tuoteno\n^FS";
      $sivu .= "^FO100,160\n^ARN,20,10\n^FD$tuoteno2\n^FS";

      $sivu .= "^MD10";                        // TUMMUUS, , vakio on 8 mutta se ei riitä viivakoodille.
      $sivu .= "^PQ$tkpl";                    // Tulostettavien lukumäärä
      //$sivu .= "^FO200,200\n^BEN,100,Y,N^FD$eankoodi\n^FS";    // viivakoodi EAN-13
      $sivu .= "^BY1,3,100\n";                  // kutistaa viivakoodia. Vakioasetus olisi ^BY2,2
      $sivu .= "^FO150,200\n^BCN,100,Y,N,N\n^FD$eankoodi\n^FS";    // Code 128

      $sivu .= "^FO100,320\n^ASN,20,12\n^FDVarasto: ".$tarrarow['tuotepaikka']."\n^FS"; // varastopaikka, oletus
      $sivu .= "^FO200,360\n^AQN,18,8\n^FD$yhtiorow[nimi]\n^FS";  // Tulostetaan Firma

      $sivu .= "\n^XZ";  // pakollinen lopetus

    }
    else {
      // tämä haara on Zebra_tuote, joka liimataan tuotteeseen
      // Toimii ja suunniteltu mallille GX420t
      // Tarrakoko 76 mm x 25 mm

      $pituus = 50; // voidaan määrittää mistä kohdasta katkaistaan teksti.

      if (strlen($nimitys) > $pituus ) {
        if (strpos($nimitys, " ")) {
          $nimipalat = explode(' ',$nimitys);

          $merkkimaara = 0;
          $nimitys = "";
          $nimitys2 = "";

          foreach ($nimipalat as $nimipala) {
            if (strlen($nimitys)+strlen($nimipala) <= $pituus or $merkkimaara == 0) {
              $nimitys .= $nimipala." ";
            }
            else {
              $nimitys2 .= $nimipala." ";
            }
            $merkkimaara += strlen($nimipala);
          }
        }

        $nimitys = substr($nimitys,0,$pituus);
        $nimitys2 = substr($nimitys2,0,$pituus);
      }


      $sivu  = "^XA\n";    // vakio alku, pakollinen
      $sivu .= "^LH50\n";  // offset vasemmasta
      $sivu .= "^LT000\n";  // offset ylhäältä
      $sivu .= "^FO85,20\n^ASN,20,12\n^FDTuoteno: $tuoteno\n^FS";
      $sivu .= "^FO85,65\n^AQN,18,8\n^FD$nimitys\n^FS";
      $sivu .= "^FO85,88\n^AQN,18,8\n^FD$nimitys2\n^FS";
      $sivu .= "^MD10";                        // TUMMUUS, vakio on 8 mutta se ei riitä viivakoodille.
      $sivu .= "^PQ$tkpl";                    // Tulostettavien lukumäärä
      $sivu .= "^FO80,120\n^BCN,40,Y,N,N\n^FD$eankoodi\n^FS";    // viivakoodi CODE-128
      $sivu .= "^FO260,180\n^AQN,18,8\n^FD$yhtiorow[nimi]\n^FS";  // Tulostetaan Firma

      $sivu .= "\n^XZ";  // pakollinen lopetus
    }

    //keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $filenimi = "/tmp/Zebra-tarrat-".md5(uniqid(mt_rand(), true)).".txt";
    $fh = file_put_contents($filenimi, $sivu);

    $line = exec("$komento $filenimi");
    system("rm -f $filenimi");
  }
  else {
    echo "<font class='error'>".t("Tuotteella")." $tuoteno ".t("ei ole viivakoodia järjestelmässä joten tarrojen tulostus taitaa olla turhaa")."!!!<br></font>";
  }
}
else {
  echo "<font class='error'>".t("Tuotetta")." $tuoteno ".t("ei löydy järjestelmästä")."!!!<br></font>";
}
