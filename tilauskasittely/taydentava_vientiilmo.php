<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "../inc/parametrit.inc";

echo "<font class='head'>".t("Tulosta täydentävä vienti-ilmoitus")."</font><hr><br>";

if ($tee == 'TULOSTA') {

  $pp = date('d');
  $kk = date('m');
  $vv = date('Y');
  $ajopvm = $pp.".".$kk.".".$vv;

  if (isset($kka) or isset($ppa) or isset($vva)) {
    if (!checkdate($kka, $ppa, $vva)) {
      echo "<font class='error'>".t("Päivämäärä virheellinen")."!</font><br>";
      exit;
    }
  }
  if (isset($kkl) or isset($ppl) or isset($vvl)) {
    if (!checkdate($kkl, $ppl, $vvl)) {
      echo "<font class='error'>".t("Päivämäärä virheellinen")."!</font><br>";
      exit;
    }
  }

  $alku  = "$ppa.$kka.$vva";
  $loppu = "$ppl.$kkl.$vvl";

  //tarkastuslistan funktiot
  require "taydentava_vientiilmo_tarkastuslista.inc";
  //paperilistan funktiot
  require "taydentava_vientiilmo_paperilista.inc";
  //atktullauksen funktiot
  require "taydentava_vientiilmo_atktietue.inc";

  $query = "SELECT *
            FROM lasku
            WHERE vienti                       = 'K'
            and tila                           = 'U'
            and alatila                        = 'X'
            and tapvm                          >= '$vva-$kka-$ppa'
            and tapvm                          <= '$vvl-$kkl-$ppl'
            and tullausnumero                 != ''
            and yhtio                          = '$kukarow[yhtio]'
            and lasku.kauppatapahtuman_luonne != '999'
            ORDER BY laskunro";
  $laskuresult = pupe_query($query);

  if (mysql_num_rows($laskuresult) == 0) {
    echo t("VIRHE: Aineisto on tyhjä, täydentävää vienti-ilmoitusta ei voida lähettää")."!";
    exit;
  }


  //Avataan failit johon kirjotetaan

  list($usec, $sec) = explode(' ', microtime());
  mt_srand((float) $sec + ((float) $usec * 100000));

  //mikä pdffaili kyseessä
  //$pdfnro = 1;
  //$laskufaili = array();

  $tarkfaili = "/tmp/TVI_Tarkastuslista-".md5(uniqid(mt_rand(), true)).".txt";
  $fhtark = fopen($tarkfaili, "w+");

  $paperifaili = "/tmp/TVI_Paperilista-".md5(uniqid(mt_rand(), true)).".txt";
  $fhpaperi = fopen($paperifaili, "w+");

  $atkfaili = "/tmp/TVI_Atktietue-".md5(uniqid(mt_rand(), true)).".txt";
  $fhatk = fopen($atkfaili, "w+");

  ///* NIY *///
  //$laskufaili[$pdfnro] = "/tmp/".$pdfnro.".TVI_Vientilaskut-".md5(uniqid(mt_rand(), true)).".pdf";
  //$fhpdf = fopen($laskufaili[$pdfnro], "w+");

  //aloitellaan ekan sivun tekemistä
  $tarksivu   = 0;
  $tarkrivi   = 1;
  $atkrivi   = 1;

  $paperisivu = 1;
  $paperirivi = 1;

  //määritelään muuttujat
  $tark   = '';
  $paperi = '';
  $atk   = '';

  //piirretään ekat otsikot
  paperi_otsikko();

  $paperirivi += 10;

  //koko aineiston laskutusarvo
  $laskutusarvo = 0;
  //koko aineiston tietuemäärä
  $tietuemaara = 0;
  //summa per pvm
  $pvmyht = 0;

  //speciaalilaskuri laskujen kopioille
  $speclask = 0;

  //Katsomme tapahtuiko virheitä
  $virhe = 0;

  while ($laskurow = mysql_fetch_array($laskuresult)) {

    ///* Laskun tietoja tarkistetaan*///
    if ($laskurow["maa_maara"] == '') {
      echo t("Laskunumero").": $laskurow[laskunro]. ".t("Määrämaa puuttuu")."!<br>";
      $virhe++;
    }
    else {
      $query = "SELECT distinct koodi
                FROM maat
                WHERE koodi='$laskurow[maa_maara]'";
      $maaresult = pupe_query($query);

      if (mysql_num_rows($maaresult) == 0) {
        echo t("Laskunumero").": $laskurow[laskunro]. ".t("Määrämaa on virheellinen")."!<br>";
        $virhe++;
      }
    }

    if ($laskurow["kauppatapahtuman_luonne"] <= 0) {
      echo t("Laskunumero").": $laskurow[laskunro]. ".t("Kauppatapahtuman luonne puuttuu")."!<br>";
      $virhe++;
    }

    if ($laskurow["kuljetusmuoto"] == '') {
      echo t("Laskunumero").": $laskurow[laskunro]. ".t("Kuljetusmuoto puuttuu")."!<br>";
      $virhe++;
    }

    if ($laskurow["sisamaan_kuljetus"] == '') {
      echo t("Laskunumero").": $laskurow[laskunro]. ".t("Sisämaan kuljetus puuttuu")."!<br>";
      $virhe++;
    }

    if ($laskurow["sisamaan_kuljetusmuoto"] == '') {
      echo t("Laskunumero").": $laskurow[laskunro]. ".t("Sisämaan kuljetusmuoto puuttuu")."!<br>";
      $virhe++;
    }

    if ($laskurow["sisamaan_kuljetus_kansallisuus"] == '') {
      //echo "Laskunumero: $laskurow[laskunro]. Sisämaan kuljetuksen kansallisuus puuttuu!<br>";
      //$virhe++;
    }
    else {
      $query = "SELECT distinct koodi
                FROM maat
                WHERE koodi='$laskurow[sisamaan_kuljetus_kansallisuus]'";
      $maaresult = pupe_query($query);

      if (mysql_num_rows($maaresult) == 0) {
        echo t("Laskunumero").": $laskurow[laskunro]. ".t("Sisämaan kuljetuksen kansallisuus on virheellinen")."!<br>";
        $virhe++;
      }
    }

    if ($laskurow["kontti"] == '') {
      echo t("Laskunumero").": $laskurow[laskunro]. ".t("Konttitieto puuttuu")."!<br>";
      $virhe++;
    }

    if ($laskurow["aktiivinen_kuljetus_kansallisuus"]  == '') {
      echo t("Laskunumero").": $laskurow[laskunro]. ".t("Aktiivisen kuljetuksen kansallisuus puuttuu")."!<br>";
      $virhe++;
    }
    else {
      $query = "SELECT distinct koodi
                FROM maat
                WHERE koodi='$laskurow[aktiivinen_kuljetus_kansallisuus]'";
      $maaresult = pupe_query($query);

      if (mysql_num_rows($maaresult) == 0) {
        echo t("Laskunumero").": $laskurow[laskunro]. ".t("Aktiivisen kuljetuksen kansallisuus on virheellinen")."!<br>";
        $virhe++;
      }
    }

    if ($laskurow["poistumistoimipaikka_koodi"] == '') {
      echo t("Laskunumero").": $laskurow[laskunro]. ".t("Poistumistoimipaikkakoodi puuttuu")."!<br>";
      $virhe++;
    }

    if ($laskurow["bruttopaino"] == '') {
      echo t("Laskunumero").": $laskurow[laskunro]. ".t("Bruttopaino puuttuu")."!<br>";
      $virhe++;
    }
    ///* Laskun tietojen tarkistus loppuu*///


    ///* katsotaan, että laskulla on rivejä, hyvitysrivejä ei huolita mukaan vienti-ilmoitukseen, silloin erä hylätään tullissa *///
    $cquery = "SELECT
               tuote.tullinimike1,
               tuote.tullinimike2,
               tuote.tullikohtelu,
               (SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa,
               if(sum(tilausrivi.rivihinta)>0,sum(tilausrivi.rivihinta),0.01) rivihinta
               FROM tilausrivi use index (uusiotunnus_index)
               JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa = '')
               LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]'
               WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]'
               and tilausrivi.yhtio         = '$kukarow[yhtio]'
               and tilausrivi.kpl           > 0
               GROUP BY tuote.tullinimike1, tuote.tullinimike2, tuote.tullikohtelu, alkuperamaa";
    $cresult = pupe_query($cquery);

    if (mysql_num_rows($cresult) == 0) {
      ///* Tarkistetaan, että tilausrivejä löytyy*///
      echo "Laskunumero: $laskurow[laskunro], ".t("VIRHE: Laskulla ei ole yhtään tilausriviä tai laskun summa oli nolla")."!<br>";
    }
    else {

      ///* Ylimääräisten erien vaikutus *///
      $extrat = abs($laskurow["lisattava_era"])-abs($laskurow["vahennettava_era"]);

      ///* Rivien summasta ja ylimääräisistä eristä tulee laskun vientiarvo *///

      $vientiarvo = 0;
      $laskunarvo = 0;

      while ($crow = mysql_fetch_array($cresult)) {
        $vientiarvo += $crow["rivihinta"];
        $laskunarvo += $crow["rivihinta"];
      }

      $vientiarvo += $extrat;
      $vientiarvo  = sprintf('%.2f', $vientiarvo);
      $laskunarvo  = sprintf('%.2f', $laskunarvo);


      /* Ei vielä tehdä laskunkopioita tässä...
      //laitetaan koostesivu laskupinkan väliin
      if ($tapvm != $laskurow["tapvm"] && $speclask > 0) {
          $firstpage = $pdf->new_page("a4");
          $pdf->enable('template');
          $tid = $pdf->template->create();
          $pdf->template->size($tid, 600, 830);

          $query = "SELECT min(laskunro), max(laskunro), count(*)
                    FROM lasku
                    WHERE vienti='K' and tila='U' and tullausnumero!='' and tapvm ='$tapvm' and yhtio='$kukarow[yhtio]'";
          $csresult = pupe_query($query);
          $csrow = mysql_fetch_array($csresult);

          $pdf->draw_text(50,  810, $yhtiorow["nimi"], $firstpage);

          $pdf->draw_text(50,  790, t("Päivämäärä").":", $firstpage);
          $pdf->draw_text(150, 790, $tapvm, $firstpage);
          $pdf->draw_text(50,  770, t("Laskunumerot").":", $firstpage);
          $pdf->draw_text(150, 770, $csrow[0]."-".$csrow[1], $firstpage);
          $pdf->draw_text(50,  750, t("Kappaleet").":", $firstpage);
          $pdf->draw_text(150, 750, $csrow[2], $firstpage);
      }
      $speclask++;
      */


      // aloitellaan laskun teko
      // defaultteja
      $tapvm = $laskurow["tapvm"];
      $kala = 540;
      $lask = 1;
      $sivu = 1;
      //$firstpage = alku();

      //Koko aineiston tullausarvo
      $laskutusarvo += $vientiarvo;

      if ($tarkrivi >= 40 || $laskurow["tapvm"] != $edtapvm) {
        if ($tarksivu >= 1) {
          tark_yht();
        }

        $pvmyht = 0;

        if ($tarksivu >= 1) {
          $tark .= chr(12);
        }

        $tarksivu++;
        $tarkrivi = 1;
        tark_otsikko();

        $tarkrivi += 5;
      }
      if ($paperirivi >= 40) {
        $paperi .= chr(12);
        $paperisivu++;
        $paperirivi = 1;
        paperi_otsikko();

        $paperirivi += 10;
      }

      ///* Lasketaan laskun kokonaispaino *///
      //hetaan kaikki otunnukset jotka löytyvät tän uusiotunnuksen alta
      $query = "SELECT distinct otunnus
                FROM tilausrivi
                WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]'
                and tilausrivi.yhtio='$kukarow[yhtio]'";
      $uresult = pupe_query($query);

      $tunnukset = '';

      while ($urow = mysql_fetch_array($uresult)) {
        $tunnukset  .= "'".$urow['otunnus']."',";
      }

      $tunnukset = substr($tunnukset, 0, -1);

      //haetaan kollimäärä ja bruttopaino
      $query = "SELECT *
                FROM rahtikirjat
                WHERE otsikkonro in ($tunnukset)
                and yhtio='$kukarow[yhtio]'";
      $rahtiresult = pupe_query($query);

      $kilot  = 0;

      while ($rahtirow = mysql_fetch_array($rahtiresult)) {
        $kilot  += $rahtirow["kilot"];
      }

      //Haetaan kaikki tilausrivit
      $query = "SELECT
                tuote.tullinimike1,
                tuote.tullinimike2,
                tuote.tullikohtelu,
                (SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa,
                if(sum(tilausrivi.rivihinta)>0,sum(tilausrivi.rivihinta),0.01) rivihinta,
                sum(tilausrivi.kpl) kpl,
                round(sum((tilausrivi.rivihinta/$laskunarvo)*$kilot),0) nettop,
                tullinimike.su,
                tullinimike.su_vientiilmo,
                tilausrivi.nimitys,
                tilausrivi.tuoteno,
                tilausrivi.tunnus
                FROM tilausrivi use index (uusiotunnus_index)
                JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = ''
                LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]'
                WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]'
                and tilausrivi.yhtio         = '$kukarow[yhtio]'
                and tilausrivi.kpl           > 0
                GROUP BY tuote.tullinimike1, tuote.tullinimike2, tuote.tullikohtelu, alkuperamaa";
      $riviresult = pupe_query($query);

      //piirretään rivi tarkastuslistaan
      tark_rivi();

      $tarkrivi++;
      //ja lasketaan summa per päivä
      $pvmyht += $vientiarvo;

      //piirretään otsikorivi paperilistaan
      paperi_otsikkorivi();

      $paperirivi += 4;

      //piirretään atktullauksen erätietue
      $atkrivi = 1;
      $vtietue = 2 + mysql_num_rows($riviresult);
      atk_eratietue();

      $atk .= "\n";
      $atkrivi++;

      //piirretään atktullauksen arvotietue
      atk_arvotietue();
      $atk .= "\n";
      $atkrivi++;

      while ($rivirow = mysql_fetch_array($riviresult)) {
        //vähän tarkistuksia
        if ($rivirow["tullinimike1"] == '') {
          echo t("1. Rivin tunnus").":$rivirow[tunnus]. ".t("Tuoteno").": $rivirow[tuoteno]. ".t("Tullinimike puuttuu")."!<br>";
          $virhe++;
        }
        else {
          $query = "SELECT cn
                    FROM tullinimike
                    WHERE cn='$rivirow[tullinimike1]' and kieli = '$yhtiorow[kieli]'";
          $cnresult = pupe_query($query);

          if (mysql_num_rows($cnresult) != 1) {
            echo t("1. Rivin tunnus").":$rivirow[tunnus]. ".t("Tuoteno").": $rivirow[tuoteno]. ".t("Tullinimike on virheellinen")."!<br>";
            $virhe++;
          }

        }

        if ($rivirow["tullikohtelu"] == '') {
          echo t("2. Rivin tunnus").":$rivirow[tunnus]. ".t("Tuoteno").": $rivirow[tuoteno]. ".t("Tullikohtelu puuttuu!")."<br>";
          $virhe++;
        }
        elseif (strlen($rivirow["tullikohtelu"]) != 4) {
          echo t("3. Rivin tunnus").":$rivirow[tunnus]. ".t("Tuoteno").": $rivirow[tuoteno]. ".t("Tullikohtelu on virheellinen!")."<br>";
          $virhe++;
        }
        elseif (!is_numeric($rivirow["tullikohtelu"])) {
          echo t("4. Rivin tunnus").":$rivirow[tunnus]. ".t("Tuoteno").": $rivirow[tuoteno]. ".t("Tullikohtelu on virheellinen, vain numeeriset arvot ovat sallittuja!")."<br>";
          $virhe++;
        }

        //laskun rivi
        //$row = $rivirow;
        //rivi($firstpage);

        //tullausarvo lisäerineen
        $tullarvo = round(($rivirow["rivihinta"] / $laskunarvo * $extrat) + $rivirow["rivihinta"], 2);
        $tullarvo = sprintf('%.2f', $tullarvo);

        $tietuemaara++;

        if ($tarkrivi >= 40) {
          $tark .= chr(12);
          $tarksivu++;
          $tarkrivi = 1;
          tark_otsikko();

          $tarkrivi += 5;
        }
        if ($paperirivi >= 40) {
          $paperi .= chr(12);
          $paperisivu++;
          $paperirivi = 1;
          paperi_otsikko();

          $paperirivi += 10;
        }

        //piirretään paperille nimikerivi
        paperi_nimikerivi();

        $paperirivi++;

        //piirretään atktullaukseen nimikerivi
        atk_nimiketietue();
        $atk .= "\n";

        $atkrivi++;
      }

      //ja laskun vikalle sivulle
      //loppu($firstpage);
      //alvierittely ($firstpage, $kala);


      //kirjoitetaan  muuttujat failiin, säästetään muistia nollaamalla muuttujat
      fwrite($fhtark, $tark);
      $tark = '';

      fwrite($fhatk, $atk);
      $atk = '';

      fwrite($fhpaperi, $paperi);
      $paperi = '';

      ///*kirjoitetaan pdf-objektit failiin ja luodaan uusi pdf-faili. Muuten muisti loppuu*///
      //fwrite($fhpdf, $pdf->generate());
      //fclose($fhpdf);

      //unset($pdf);

      //$pdfnro++;
      //$laskufaili[$pdfnro] = "/tmp/".$pdfnro.".TVI_Vientilaskut-".md5(uniqid(mt_rand(), true)).".pdf";
      //$fhpdf = fopen($laskufaili[$pdfnro], "w+");

      //PDF parametrit
      /*
      $pdf = new pdffile;

      $pdf->set_default('margin-top',   0);
      $pdf->set_default('margin-bottom',   0);
      $pdf->set_default('margin-left',   0);
      $pdf->set_default('margin-right',   0);
      //* PDF-kikkailut loppuu tähän*///
    }
  }

  /*
  //laitetaan se koostesivu laskupinkan loppuun
  $firstpage = $pdf->new_page("a4");
  $pdf->enable('template');
  $tid = $pdf->template->create();
  $pdf->template->size($tid, 600, 830);

  $query = "SELECT min(laskunro), max(laskunro), count(*)
            FROM lasku
            WHERE vienti='K' and tila='U' and tullausnumero!='' and tapvm ='$tapvm' and yhtio='$kukarow[yhtio]'";
  $csresult = pupe_query($query);
  $csrow = mysql_fetch_array($csresult);

  $pdf->draw_text(50,  810, $yhtiorow["nimi"], $firstpage);
  $pdf->draw_text(50,  790, t("Päivämäärä").":", $firstpage);
  $pdf->draw_text(150, 790, $tapvm, $firstpage);
  $pdf->draw_text(50,  770, t("Laskunumerot").":", $firstpage);
  $pdf->draw_text(150, 770, $csrow[0]."-".$csrow[1], $firstpage);
  $pdf->draw_text(50,  750, t("Kappaleet").":", $firstpage);
  $pdf->draw_text(150, 750, $csrow[2], $firstpage);

  */

  //vielä vikalle sivulle piirrettävät
  $paperisivu++;

  tark_yht();
  paperi_loppu();

  //kirjoitetaan pdf-objektit ja muut kamat failiin
  //fwrite($fhpdf, $pdf->generate());

  fwrite($fhtark, $tark);
  $tark = '';

  fwrite($fhatk, $atk);
  $atk = '';

  fwrite($fhpaperi, $paperi);
  $paperi = '';

  //suljetaan failit
  fclose($fhatk);
  fclose($fhpaperi);
  //fclose($fhpdf);
  fclose($fhtark);

  //paperilista pitää saada kauniiksi
  $params = array(
    'chars'    => 169,
    'filename' => $paperifaili,
    'margin'   => 0,
    'mode'     => 'landscape',
  );

  // konveroidaan postscriptiksi
  $filenimi1_ps = pupesoft_a2ps($params);

  //tarkastuslistalle sama juttu
  $params = array(
    'chars'    => 121,
    'filename' => $tarkfaili,
    'margin'   => 0,
    'mode'     => 'landscape',
  );

  // konveroidaan postscriptiksi
  $filenimi2_ps = pupesoft_a2ps($params);

  //lopuks käännetään vielä pdf:iks ja lähetetään sähköpostiin, voi sitten tulostella kun siltä tuntuu
  $line3 = exec("ps2pdf -sPAPERSIZE=a4 {$filenimi1_ps} ".$paperifaili.".pdf");

  //tarkastuslistalle sama juttu
  $line4 = exec("ps2pdf -sPAPERSIZE=a4 {$filenimi2_ps} ".$tarkfaili.".pdf");

  //mergataan pdf-ät yhdeksi failiksi joka sit lähetetään käyttäjälle
  //$kaikkilaskut = "/tmp/TVI_Kaikki_Vientilaskut-".md5(uniqid(mt_rand(), true)).".pdf";

  ///* Katsotaan voidaanko rappari lähettää tulliin *///

  if ($virhe > 0) {
    //virheitä on sattunut
    echo "<br><br>".t("Korjaa ensin kaikki virheet. Ja kokeile sitten uudestaan").".<br><br>";
    system("rm -f $tarkfaili");
    system("rm -f {$filenimi2_ps}");
    system("rm -f ".$tarkfaili.".pdf");
    system("rm -f {$filenimi1_ps}");
    system("rm -f ".$paperifaili.".pdf");
    system("rm -f $paperifaili");
    system("rm -f $atkfaili");
    exit;
  }

  ///*Kasataan käyttäjälle lähetettävä meili *///
  //tässa on kaikki failit jotka tarvitaan
  $bound = uniqid(time()."_") ;

  $header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
  $header .= "MIME-Version: 1.0\n" ;
  $header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

  $content = "--$bound\n";

  /*
  $content .= "Content-Type: application/pdf; name=\"Laskut.pdf\"\n" ;
  $content .= "Content-Transfer-Encoding: base64\n" ;
  $content .= "Content-Disposition: inline; filename=\"Laskut.pdf\"\n\n";
  $nimi    = $laskufaili;
  $handle  = fopen($nimi, "r");
  $sisalto = fread($handle, filesize($nimi));
  fclose($handle);
  $content .= chunk_split(base64_encode($sisalto));
  $content .= "\n" ;

  $content .= "--$bound\n";
  */

  $content .= "Content-Type: application/pdf; name=\"".t("Tarkastuslista").".pdf\"\n" ;
  $content .= "Content-Transfer-Encoding: base64\n" ;
  $content .= "Content-Disposition: inline; filename=\"".t("Tarkastuslista").".pdf\"\n\n";

  $nimi    = $tarkfaili.".pdf";
  $handle  = fopen($nimi, "r");
  $sisalto = fread($handle, filesize($nimi));
  fclose($handle);
  $content .= chunk_split(base64_encode($sisalto));
  $content .= "\n" ;

  $content .= "--$bound\n";

  $content .= "Content-Type: application/pdf; name=\"".t("Taydentava-Ilmoitus").".pdf\"\n" ;
  $content .= "Content-Transfer-Encoding: base64\n" ;
  $content .= "Content-Disposition: inline; filename=\"".t("Taydentava-Ilmoitus").".pdf\"\n\n";
  $nimi    = $paperifaili.".pdf";
  $handle  = fopen($nimi, "r");
  $sisalto = fread($handle, filesize($nimi));
  fclose($handle);
  $content .= chunk_split(base64_encode($sisalto));
  $content .= "\n" ;

  $content .= "--$bound--\n";

  mail($kukarow["eposti"], mb_encode_mimeheader(t("Täydentävä vienti-ilmoitus"), "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");

  ///* Tässä tehään täydentävä ilmoitus sähköiseen muotoon *///
  //PGP-encryptaus atklabeli
  $label  = '';
  $label .= t("lähettäjä").": $yhtiorow[nimi]\n";
  $label .= t("sisältö").": vientitullaus/sisäkaupantilasto\n";
  $label .= t("kieli").": ASCII\n";
  $label .= t("jakso").": $alku - $loppu\n";
  $label .= t("koko aineiston tietuemäärä").": $tietuemaara\n";
  $label .= t("koko aineiston vienti-, verotus- tai laskutusarvo").": $laskutusarvo\n";

  $message = '';

  $recipient = "pgp-key Customs Finland <ascii.vienti@tulli.fi>";         // tämä on tullin virallinen avain

  if ($lahetys == "test") {
    $recipient = "pgp-testkey Customs Finland <test.ascii.vienti@tulli.fi>";   // tämä on tullin testiavain
  }

  $message = $label;
  require "../inc/gpg.inc";
  $label = $encrypted_message;

  $recipient = "pgp-key Customs Finland <ascii.vienti@tulli.fi>";         // tämä on tullin virallinen avain

  if ($lahetys == "test") {
    $recipient = "pgp-testkey Customs Finland <test.ascii.vienti@tulli.fi>";   // tämä on tullin testiavain
  }

  $nimi   = $atkfaili;
  $handle  = fopen($nimi, "r");
  $message = fread($handle, filesize($nimi));
  fclose($handle);

  require "../inc/gpg.inc";
  $atk = $encrypted_message;

  //Kasataan tulliin lähetettävä meili
  $bound = uniqid(time()."_") ;

  $header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
  $header .= "MIME-Version: 1.0\n" ;
  $header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

  $content = "--$bound\n" ;

  $content .= "Content-Type: application/pgp-encrypted;\n" ;
  $content .= "Content-Transfer-Encoding: base64\n" ;
  $content .= "Content-Disposition: attachment; filename=\"otsikko.pgp\"\n\n";
  $content .= chunk_split(base64_encode($label));
  $content .= "\n" ;

  $content .= "--$bound\n" ;

  $content .= "Content-Type: application/pgp-encrypted;\n" ;
  $content .= "Content-Transfer-Encoding: base64\n" ;
  $content .= "Content-Disposition: attachment; filename=\"tietue.pgp\"\n\n";
  $content .= chunk_split(base64_encode($atk));
  $content .= "\n" ;

  $content .= "--$bound--\n" ;

  if ($lahetys == "tuli") {
    // lähetetään meili tulliin
    $to = 'ascii.vienti@tulli.fi';      // tämä on tullin virallinen osoite
    mail($to, "", $content, $header, "-f $yhtiorow[postittaja_email]");
    echo "<font class='message'>".t("Tiedot lähetettiin tulliin").".</font><br><br>";
  }
  elseif ($lahetys == "test") {
    // lähetetään TESTI meili tulliin
    $to = 'test.ascii.vienti@tulli.fi';    // tämä on tullin testiosoite
    mail($to, "", $content, $header, "-f $yhtiorow[postittaja_email]");
    echo "<font class='message'>".t("Testitiedosto lähetettiin tullin testipalvelimelle").".</font><br><br>";
  }
  else {
    echo "<font class='message'>".t("Tietoja EI lähetetty tulliin").".</font><br><br>";
  }

  echo "<br><br>".t("Sähköpostit lähetetty! Kaikki on valmista")."!";

  //Dellataan vielä viimeiset failit
  system("rm -f $tarkfaili");
  system("rm -f {$filenimi2_ps}");
  system("rm -f ".$tarkfaili.".pdf");
  system("rm -f {$filenimi1_ps}");
  system("rm -f ".$paperifaili.".pdf");
  system("rm -f $paperifaili");
  system("rm -f $atkfaili");

}

if ($tee == '') {
  if (!isset($kka))
    $kka = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
  if (!isset($vva))
    $vva = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
  if (!isset($ppa))
    $ppa = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));

  if (!isset($kkl))
    $kkl = date("m");
  if (!isset($vvl))
    $vvl = date("Y");
  if (!isset($ppl))
    $ppl = date("d");

  //syötetään ajanjakso
  echo "<table>";
  echo "<form method = 'post'>";
  echo "<input type='hidden' name='tee' value='TULOSTA'>
      <input type='hidden' name='toim' value='$toim'>";

  echo "<tr><td class='back'></td><th>".t("pp")."</th><th>".t("kk")."</th><th>".t("vvvv")."</th></tr>";

  echo "<tr><th>".t("Syötä alkupäivämäärä")." </th>
    <td><input type='text' name='ppa' value='$ppa' size='5'></td>
    <td><input type='text' name='kka' value='$kka' size='5'></td>
    <td><input type='text' name='vva' value='$vva' size='7'></td>
    </tr><tr><th>".t("Syötä loppupäivämäärä")." </th>
    <td><input type='text' name='ppl' value='$ppl' size='5'></td>
    <td><input type='text' name='kkl' value='$kkl' size='5'></td>
    <td><input type='text' name='vvl' value='$vvl' size='7'></td></tr>";

  $sel[$lahetys]  = "SELECTED";

  echo "
    <tr>
      <th>".t("Tietojen lähetys sähköpostilla")."</th>
      <td colspan='3'>
      <select name='lahetys'>
      <option value='tuli' $sel[tuli]>".t("Lähetä aineisto tulliin")."</option>
      <option value='test' $sel[test]>".t("Lähetä testiaineisto tullin testipalvelimelle")."</option>
      <option value=''>".t("Älä lähetä aineistoa tulliin")."</option>
      </select>
    </tr>
  ";

  echo "</table>";

  echo "<br><input type='submit' value='".t("Tulosta")."'>";
  echo "</form>";

}

require '../inc/footer.inc';
