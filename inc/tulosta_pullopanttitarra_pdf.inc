<?php

if (@include_once "pdflib/phppdflib.class.php") {
}
else {
  include_once "phppdflib.class.php";
}

// Haetaan asiakkaan kieli
$querykiel = "SELECT kieli FROM asiakas WHERE yhtio = '$kukarow[yhtio]' and ytunnus = '$laskurow[ytunnus]'";
$kielresult = pupe_query($querykiel);
$kielrow = mysql_fetch_array($kielresult);

if ($kielrow['kieli'] != '') {
  $kieli = strtolower($kielrow['kieli']);
}

$iso["height"]         = 25;
$iso["font"]           = "Helvetica";

$keski["height"]         = 20;
$keski["font"]           = "Helvetica";

$rectparam["width"]    = 0.3;
$rivinkorkeus          = 15;

//muutamat funktiot...
if (!function_exists('tulosta_pullopanttitarra_pdf')) {
  function tulosta_pullopanttitarra_pdf ($laskurow) {
    global $kukarow, $yhtiorow, $keski, $iso;

    $sivu = 0;
    $tarrat = "";

    $sorttaus = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);
    $sorttaus = str_replace("tilausrivi.", "r.", $sorttaus);

    $query = "SELECT r.tuoteno, r.nimitys, r.tunnus, r.varattu - IFNULL(sarjanumeroita.kpl, 0) puuttuu, {$sorttaus}
              FROM tilausrivi r
              INNER JOIN tuote t
                ON (t.yhtio = r.yhtio AND t.tuoteno = r.tuoteno)
              LEFT JOIN
              (
                SELECT sns.yhtio, sns.myyntirivitunnus, COUNT(*) kpl
                FROM sarjanumeroseuranta sns
                WHERE sns.yhtio = '$kukarow[yhtio]'
                GROUP BY sns.yhtio, sns.myyntirivitunnus
              ) sarjanumeroita
                ON (sarjanumeroita.yhtio = r.yhtio AND sarjanumeroita.myyntirivitunnus = r.tunnus)
              WHERE t.pullopanttitarratulostus_kerayksessa = 'T'
                AND r.var NOT IN ('P','J','O','S')
                AND r.yhtio = '$kukarow[yhtio]'
                AND r.otunnus = '$laskurow[tunnus]'
              HAVING puuttuu > 0
              ORDER BY sorttauskentta";
    $sarjanumerot = pupe_query($query);

    while ($tilausrivi = mysql_fetch_assoc($sarjanumerot)) {
      for ($tarralaskuri = 1; $tarralaskuri <= $tilausrivi['puuttuu']; $tarralaskuri++) {
        $query = "SELECT tilausrivi.*,
                     tuote.sarjanumeroseuranta,
                     tuote.automaattinen_sarjanumerointi,
                     tuote.yksikko,
                     tilausrivin_lisatiedot.osto_vai_hyvitys,
                     tuote.kehahin
                   FROM tilausrivi use index (PRIMARY)
                   JOIN tuote
                     ON tilausrivi.yhtio = tuote.yhtio AND tilausrivi.tuoteno = tuote.tuoteno
                   LEFT JOIN tilausrivin_lisatiedot
                     ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
                   WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                     AND tilausrivi.tunnus = '$tilausrivi[tunnus]'";
        $rivires = pupe_query($query);
        $rivirow = mysql_fetch_assoc($rivires);

        if ($rivirow["hyllyalue"] == "") {
          $query = "SELECT *
                  FROM tuotepaikat
                  WHERE yhtio    = '$kukarow[yhtio]'
                    AND tuoteno  = '$rivirow[tuoteno]'
                    AND oletus  != ''";
          $result = pupe_query($query);
          $saldorow = mysql_fetch_assoc($result);

          $rivirow["hyllyalue"] = $saldorow["hyllyalue"];
          $rivirow["hyllynro"] = $saldorow["hyllynro"];
          $rivirow["hyllyvali"] = $saldorow["hyllyvali"];
          $rivirow["hyllytaso"] = $saldorow["hyllytaso"];
        }

        $query = "INSERT INTO sarjanumeroseuranta (
                  yhtio, tuoteno, sarjanumero, lisatieto,
                  ostorivitunnus, myyntirivitunnus, kaytetty, era_kpl,
                  laatija, luontiaika, muutospvm, muuttaja,
                  hyllyalue, hyllynro, hyllyvali, hyllytaso
                )
                VALUES
                (
                  '$yhtiorow[yhtio]', '$rivirow[tuoteno]', '" .generoi_sarjanumero($rivirow, 'myyntirivitunnus') . "', '',
                  0, {$tilausrivi[tunnus]}, '', 0,
                  '$kukarow[kuka]', now(), '$kukarow[kuka]', now(),
                  '$rivirow[hyllyalue]', '$rivirow[hyllynro]', '$rivirow[hyllyvali]', '$rivirow[hyllytaso]'
                )";
        pupe_query($query);

        $query = "UPDATE laite
                SET paikka     = '{$tilausrivi['tunnus']}',
                    muutospvm  = now(),
                    muuttaja   = '{$kukarow['kuka']}'
                WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND sarjanro = '{$tilausrivi['sarjanumero']}'
                  AND tuoteno  = '{$tilausrivi['tuoteno']}'";
        pupe_query($query);

        if (mysql_affected_rows() == 0) {
          $query = "INSERT INTO laite
                  SET yhtio      = '{$kukarow['yhtio']}',
                      luontiaika = now(),
                      sarjanro   = '{$tilausrivi['sarjanumero']}',
                      paikka     = '{$tilausrivi['tunnus']}',
                      tuoteno    = '{$tilausrivi['tuoteno']}',
                      laatija    = '{$kukarow['kuka']}'";
          pupe_query($query);
        }
      }
    }

    $sorttaus = generoi_sorttauskentta($yhtiorow["tilauksen_jarjestys"]);
    $query = "SELECT tilausrivi.tuoteno,
                tilausrivi.nimitys,
                tilausrivi.tunnus,
                sarjanumeroseuranta.sarjanumero,
                tuote.lyhytkuvaus AS kylma_aine,
                {$sorttaus}
              FROM tilausrivi
                JOIN sarjanumeroseuranta ON (sarjanumeroseuranta.yhtio = tilausrivi.yhtio AND sarjanumeroseuranta.myyntirivitunnus = tilausrivi.tunnus)
                LEFT JOIN tilausrivi aine ON (aine.yhtio = tilausrivi.yhtio AND aine.tunnus = tilausrivi.perheid)
                LEFT JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = aine.tuoteno)
              WHERE tilausrivi.yhtio = '{$yhtiorow['yhtio']}'
                AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
              ORDER BY sorttauskentta";
    $tuotetiedot_res = (pupe_query($query));

    while ($tilausrivi = mysql_fetch_assoc($tuotetiedot_res)){

      $pdf_zebra_tarra = new pdffile;

      // Tehd‰‰n viivakoodi pdf:n‰, jotta siit‰ saadaan riitt‰v‰n tarkka tarratulostimelle ja k‰ytet‰‰n viivakoodin pdf:‰‰ tarran pohjana
      $filename = pdf_viivakoodi(trim($tilausrivi["sarjanumero"]), "code128", 180, 32, "");
      $pdf_zebra_tarra->enable('import');

      ob_start();

      $handle = fopen($filename, "r");
      $d = fread($handle, filesize($filename));

      fclose($handle);

      $pdf_zebra_tarra->import->append($d);
      $page_zebra_tarra = $pdf_zebra_tarra->import->get_pages();

      ob_end_clean();
  
      $pdf_zebra_tarra->set_default('margin-top',   20);
      $pdf_zebra_tarra->set_default('margin-bottom', 0);
      $pdf_zebra_tarra->set_default('margin-left',   0);
      $pdf_zebra_tarra->set_default('margin-right',  0);

      $data = file_get_contents("../pics/mustavalkologo.jpg", true);
      
      $image = $pdf_zebra_tarra->jfif_embed($data);
      $pdf_zebra_tarra->image_place($image, 160, -50, $page_zebra_tarra[$sivu], array("scale" => 0.3));

      $pdf_zebra_tarra->draw_text(-40, 120, $yhtiorow["www"], $page_zebra_tarra[$sivu], $iso);
      $posti_osoite = $yhtiorow["osoite"].", ".$yhtiorow["postino"]." ".$yhtiorow["postitp"];
      $pdf_zebra_tarra->draw_text(-40, 95, $posti_osoite, $page_zebra_tarra[$sivu], $iso);

      $pdf_zebra_tarra->draw_text(280, 205, "Tilausnro:", $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(400, 205, $laskurow["tunnus"], $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(280, 180, "Pvm:", $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(400, 180, date("d.m.Y"), $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(280, 155, "Sarjanro:", $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(400, 155, $tilausrivi["sarjanumero"], $page_zebra_tarra[$sivu], $iso);

      $pdf_zebra_tarra->draw_text(-40, 70, t("Veloitukseton palautus viimeist‰‰n:"), $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(360, 70, date("d.m.Y", strtotime("+6 months")), $page_zebra_tarra[$sivu], $iso);
      $pdf_zebra_tarra->draw_text(-40, 45, $tilausrivi["kylma_aine"], $page_zebra_tarra[$sivu], $iso);

      $fh = fopen($filename, "w");
      if (fwrite($fh, $pdf_zebra_tarra->generate()) === FALSE) die("PDF create error $filename");
      fclose($fh);

      $tarrat = $tarrat." ".$filename;
    }

    zebra_tarra_tulostus_ruudulle($tarrat);
  }
}

if (!function_exists('zebra_tarra_tulostus_ruudulle')) {
    function zebra_tarra_tulostus_ruudulle($tarrat) {
   
    // Keksit‰‰n uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdffilenimi = "/tmp/Pullopanttitarra-".md5(uniqid(mt_rand(), true)).".pdf";

    // Kootaan tarrat yhteen pdf:‰‰n
    shell_exec("gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress -sOutputFile={$pdffilenimi} {$tarrat}");

    echo file_get_contents($pdffilenimi);
  }
}
