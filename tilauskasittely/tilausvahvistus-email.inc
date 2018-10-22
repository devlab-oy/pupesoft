<?php

// tarvitaan $laskurow array
// tarvitaan $yhtiorow array

$query = "SELECT *
          FROM asiakas
          WHERE yhtio = '$kukarow[yhtio]'
          AND tunnus  = '$laskurow[liitostunnus]'";
$tilvahresult = pupe_query($query);
$tilvahasiakas = mysql_fetch_array($tilvahresult);

$kieli = strtolower($tilvahasiakas['kieli']);

$virexx = 0;
$orig_komento = $komento;
$komento = array();

// asiakkaan sähköpostiin
if (strpos($laskurow['tilausvahvistus'], 'S') !== FALSE) {
  if (substr($orig_komento, 0, 14) == 'vaintahanemail') {
    $komento["Tilausvahvistus"] = "asiakasemail".substr($orig_komento, 14);
  }
  else {
    $query = "SELECT *
              FROM yhteyshenkilo
              WHERE yhtio      = '$kukarow[yhtio]'
              AND liitostunnus = '$laskurow[liitostunnus]'
              AND nimi         = '$laskurow[tilausyhteyshenkilo]'
              and tyyppi       = 'A'";
    $yhteyshenkiloresult = pupe_query($query);
    $yhteyshenkiloasiakas = mysql_fetch_array($yhteyshenkiloresult);

    if ($yhteyshenkiloasiakas['email'] != '') {
      $tilvahasiakas['email'] = $yhteyshenkiloasiakas['email'];
    }

    if ($tilvahasiakas['email'] == '') {
      $tilvah_nimi = $tilvahasiakas["nimi"];
      if ($tilvahasiakas["toim_nimi"] != "" and $tilvahasiakas["toim_nimi"] != $tilvahasiakas["nimi"]) {
        $tilvah_nimi .= " / ".$tilvahasiakas["toim_nimi"];
      }
      if ($silent == "") {
        echo "<font class='error'>".t("TILAUSVAHVISTUS: Asiakkaalla ei ole sähköpostiosoitetta")."! $tilvah_nimi ($tilvahasiakas[ytunnus])</font><br><br>";
      }
      $virexx++;
    }
    else {
      // komentoon asiakasemail
      $komento["Tilausvahvistus"] = "asiakasemail".$tilvahasiakas['email'];
    }
  }
}

// omaan sähköpostiin
if (strpos($laskurow['tilausvahvistus'], 'O') !== FALSE) {

  if ($kukarow['eposti'] == '') {
    if ($silent == "") {
      echo "<font class='error'>".t("TILAUSVAHVISTUS: Sinulla ei ole sähköpostiosoitetta")."!</font><br><br>";
    }
    $virexx++;
  }
  else {
    // komentoon email
    $komento["Tilausvahvistus"] = "email";
  }
}

// lähetetään PDF vahvistus jos meillä on P kirjain vahvistuksessa
if ($virexx == 0 and strpos($laskurow['tilausvahvistus'], 'P') !== FALSE) {
  $toim_save = $toim;
  if ($toim != "YLLAPITOSOPIMUS") $toim = "TILAUSVAHVISTUS";

  require "tulosta_tilausvahvistus_pdf.inc";
  $toim = $toim_save;
}

// lähetetään textimuotoinen tilausvahvistusmeili jos miellä _EI OLE_ P kirjainta vahvistuksessa
if ($virexx == 0 and strpos($laskurow['tilausvahvistus'], 'P') === FALSE) {

  if ($yhtiorow["tilausvahvistus_lahetys"] == 1 and $laskurow["vanhatunnus"] > 0) {
    $query = "SELECT group_concat(tunnus) tunnukset
              FROM lasku use index (yhtio_vanhatunnus)
              WHERE yhtio     = '$kukarow[yhtio]'
              and vanhatunnus = '$laskurow[vanhatunnus]'
              and tila        IN ('N','L','A')";
    $abures = pupe_query($query);
    $aburow = mysql_fetch_assoc($abures);

    if ($aburow["tunnukset"] != "") {
      $wherelisa = " tilausrivi.otunnus IN ($aburow[tunnukset])";
      $laskurow["nippu"] = $aburow["tunnukset"];
    }
    else {
      $wherelisa = "tilausrivi.otunnus = '$laskurow[tunnus]'";
      $laskurow["nippu"] = $laskurow["tunnus"];
    }
  }
  elseif ($laskurow["tunnusnippu"] > 0 and $laskurow["clearing"] != "JT-TILAUS") {
    $query = "SELECT group_concat(tunnus) tunnukset
              FROM lasku
              WHERE yhtio     = '$kukarow[yhtio]'
              and tunnusnippu = '$laskurow[tunnusnippu]'
              and tila        IN ('N','L','A')";
    $abures = pupe_query($query);
    $aburow = mysql_fetch_assoc($abures);

    if ($aburow["tunnukset"] != "") {
      $wherelisa = " tilausrivi.otunnus IN ($aburow[tunnukset])";
      $laskurow["nippu"] = $aburow["tunnukset"];
    }
    else {
      $wherelisa = "tilausrivi.otunnus = '$laskurow[tunnus]'";
      $laskurow["nippu"] = $laskurow["tunnus"];
    }
  }
  else {
    $wherelisa = "tilausrivi.otunnus = '$laskurow[tunnus]'";
    $laskurow["nippu"] = $laskurow["tunnus"];
  }

  $ale_query_select_lisa = '';

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    $ale_query_select_lisa .= " tilausrivi.ale{$alepostfix},";
  }

  if ($laskurow["tila"] == "L" or $laskurow["tila"] == "N") {
    $tyyppilisa = " and tilausrivi.tyyppi in ('L') ";
  }
  else {
    $tyyppilisa = " and tilausrivi.tyyppi in ('L','G','W','E','T') ";
  }

  if (($laskurow["tunnusnippu"] > 0 or $yhtiorow["tilausvahvistus_lahetys"] == 1) and $laskurow['vanhatunnus'] > 0) {
    $tulostettava_tunnus = $laskurow['vanhatunnus'];
  }
  else {
    $tulostettava_tunnus = $laskurow['tunnus'];
  }

  $query = "SELECT tilausrivi.tunnus, tuote.eankoodi, tuote.myyntihinta, tuote.aleryhma, tilausrivi.tuoteno,
            if(tilausrivi.var not in ('P','J','O','S'), tilausrivi.varattu, 0) varattu,
            if(tilausrivi.var = 'J', tilausrivi.jt+tilausrivi.varattu , 0) jt,
            tilausrivi.toimaika, tilausrivi.kommentti, tilausrivi.tilkpl, tilausrivi.alv,
            tilausrivi.nimitys,  tilausrivi.hinta, {$ale_query_select_lisa} tilausrivi.kommentti, tilausrivi.var,
            tuote.status
            FROM tilausrivi
            LEFT JOIN tuote USING (yhtio, tuoteno)
            WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
            $tyyppilisa
            and $wherelisa
            and tilausrivi.tuoteno != '{$yhtiorow['ennakkomaksu_tuotenumero']}'
            ORDER BY tilausrivi.tunnus";
  $tilvahresult = pupe_query($query);

  $query = "SELECT nimi, eposti
            FROM kuka
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$laskurow[myyja]'";
  $kresult = pupe_query($query);

  $krow = mysql_fetch_array($kresult);

  if (mysql_num_rows($tilvahresult) > 0) {

    if ($laskurow["tilaustyyppi"] == 'E') {
      $ulos = sprintf("%-50.s", $yhtiorow['nimi'])                .t("Tilausvahvistus Ennakkomyynti", $kieli)."\n";
    }
    else {
      $ulos = sprintf("%-50.s", $yhtiorow['nimi'])                .t("Tilausvahvistus", $kieli)."\n";
    }

    $ulos .= sprintf("%-50.s", $yhtiorow['osoite'])."\n";
    $ulos .= sprintf("%-50.s", $yhtiorow['postino']." ".$yhtiorow['postitp'])  .tv1dateconv($laskurow['luontiaika'])."\n";
    $ulos .= sprintf("%-50.s", $yhtiorow['maa'])."\n";
    $ulos .= "\n";
    $ulos .= sprintf("%-50.s", t("Tilaaja", $kieli).":")              .t("Toimitusosoite", $kieli).":\n";
    $ulos .= sprintf("%-50.s", $laskurow['nimi'])                .$laskurow['toim_nimi']."\n";
    $ulos .= sprintf("%-50.s", $laskurow['nimitark'])              .$laskurow['toim_nimitark']."\n";
    $ulos .= sprintf("%-50.s", $laskurow['osoite'])                .$laskurow['toim_osoite']."\n";
    $ulos .= sprintf("%-50.s", $laskurow['postino']." ".$laskurow['postitp'])  .$laskurow['toim_postino']." ".$laskurow['toim_postitp']."\n";
    $ulos .= sprintf("%-50.s", $laskurow['maa'])                  .$laskurow['toim_maa']."\n";
    $ulos .= "\n";

    $useita = "EI";

    if ($laskurow["nippu"] > 0) {
      $query = "SELECT distinct(toimaika)
                FROM tilausrivi
                WHERE yhtio   = '$kukarow[yhtio]'
                $tyyppilisa
                and otunnus   IN ($laskurow[nippu])
                and toimaika != '0000-00-00'";
      $toimaikares = pupe_query($query);

      if (mysql_num_rows($toimaikares) > 1) {
        $useita = "JOO";
      }
    }

    if ($useita == "JOO") {
      $taika = t("Useita", $kieli);
    }
    elseif ($laskurow["toimvko"] != '') {
      $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

      $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

      if ($laskurow['toimvko'] != '7') {
        $taika .= "/".$DAY_ARRAY[$laskurow["toimvko"]];
      }
    }
    else {
      if ($laskurow["toimaika"] == '0000-00-00') $taika = t("Avoin", $kieli);
      else $taika = tv1dateconv($laskurow["toimaika"]);
    }

    $ulos .= sprintf("%-50.s", t("Toimitustapa", $kieli).": ".t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli))    .t("Toimitusaika", $kieli).": ".$taika."\n";
    $ulos .= sprintf("%-50.s", t("Tilausnumero", $kieli).": ". $tulostettava_tunnus)      .t("Tilausviite", $kieli).": ".$laskurow['viesti']."\n";
    $ulos .= sprintf("%-50.s", t("Tilaaja", $kieli).": ".$laskurow['tilausyhteyshenkilo'])  .t("Tilaajan tilausnumero", $kieli).": ".$laskurow['asiakkaan_tilausnumero']."\n";
    $ulos .= sprintf("%-50.s", t("Kohde", $kieli).": ".$laskurow['kohde'])          .t("Myyjä", $kieli).": ".$krow['nimi']."\n";

    $toimehto = t_avainsana("TOIMEHTO", $kieli, " and concat(selite,' ',selitetark) = '{$laskurow["toimitusehto"]}'", "", "", "selitetark");
    if ($toimehto == "") $toimehto = $laskurow["toimitusehto"];
    else $toimehto = substr($laskurow["toimitusehto"], 0, 3)." ".$toimehto;

    $ulos .= sprintf("%-50.s", t("Toimitusehto", $kieli).": ".$toimehto)."\n";

    if (($naytatvale != 3 or $naytatvale != 4) and $laskurow['erikoisale'] > 0) {
      $ulos .= sprintf("%-50.s", t("Erikoisalennus", $kieli).": ".$laskurow['erikoisale']."%\n");
    }

    if ($yhtiorow['alv_kasittely'] != '') {
      $ulos .= sprintf("%-50.s", t("Hinnat ovat verottomia.", $kieli)."\n");
    }
    else {
      $ulos .= sprintf("%-50.s", t("Hinnat sisältävät arvonlisäveron.", $kieli)."\n");
    }

    if ($laskurow['comments']!='')
      $ulos .= t("Kommentti", $kieli).": ".$laskurow['comments']."\n";

    $ulos .= "\n";

    // headerit
    $ulos .= sprintf("%-4.4s",   t("Nro", $kieli));
    $ulos .= sprintf("%-30.30s", t("Nimitys", $kieli));
    $ulos .= sprintf("%-20.20s", t("Tuotenumero", $kieli));
    $ulos .= sprintf("%-12.12s", t("EAN", $kieli));
    $ulos .= sprintf("%9.9s",    t("Määrä", $kieli));

    if ($naytatvale != 3) {
      $hinta_pituus = (8 + $yhtiorow['hintapyoristys'] - 2);
      $ulos .= sprintf("%".$hinta_pituus.".".$hinta_pituus."s", t("Hinta", $kieli));
      $ulos .= sprintf("%4.4s", t("Val", $kieli));
      if ($naytatvale != 4) {
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $ulos .= sprintf("%7.7s", t("Ale{$alepostfix}", $kieli));
        }
      }
    }

    $ulos .= "\n";
    $ulos .= "----------------------------------------------------------------------------------------------\n";

    $rivino = 0;
    $liite  = t("Rivino", $kieli)."\t".t("Nimitys", $kieli)."\t".t("Tuoteno", $kieli)."\t".t("Varattu", $kieli);

    // kaikilla muilla paitsi kolmosella näytetään nämä
    if ($naytatvale != 3) {
      $liite .= "\t".t("Hinta", $kieli);
      if ($naytatvale != 4) {
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $liite .= "\t".t("Ale{$alepostfix}", $kieli);
        }
      }
    }

    $liite .= "\n";

    while ($tvtilausrivirow = mysql_fetch_array($tilvahresult)) {
      $rivino++;

      $tvtilausrivirow['nimitys'] = t_tuotteen_avainsanat($tvtilausrivirow, 'nimitys', $kieli);

      if (strlen($tvtilausrivirow['nimitys']) > 29) {
        $nimitysloput = substr($tvtilausrivirow['nimitys'], 29);
        $tvtilausrivirow['nimitys'] = substr($tvtilausrivirow['nimitys'], 0, 29);
      }
      else {
        $nimitysloput = '';
      }

      $ulos .= sprintf("%03.0f", $rivino)." ";
      $ulos .= sprintf("%-30.s", $tvtilausrivirow['nimitys']);
      $ulos .= sprintf("%-20.s", $tvtilausrivirow['tuoteno']);
      $ulos .= sprintf("%-13.s", $tvtilausrivirow['eankoodi']);

      if ($laskurow["tilaustyyppi"] == 'E') {
        $ulos .= sprintf("%8.s"  , $tvtilausrivirow['tilkpl']);
      }
      elseif ($tvtilausrivirow['var']!= 'J') {
        $ulos .= sprintf("%8.s"  , $tvtilausrivirow['varattu']);
      }
      else {
        $ulos .= sprintf("%8.s"  , $tvtilausrivirow['jt'])." ".t("Jälkitoimitukseen", $kieli);
      }

      if ($naytatvale==3) {
        // mennään ohi koska ei haluta nähdä hintoja
      }
      elseif ($naytatvale==2) {
        $ulos .= sprintf("%".(8+$yhtiorow['hintapyoristys']-2).".s"  , round(laskuval($tvtilausrivirow['myyntihinta'], $laskurow['vienti_kurssi']), $yhtiorow['hintapyoristys']))." ";
      }
      else {
        $ulos .= sprintf("%".(8+$yhtiorow['hintapyoristys']-2).".s"  , round(laskuval($tvtilausrivirow['hinta'], $laskurow['vienti_kurssi']), $yhtiorow['hintapyoristys']))." ";
      }

      // näytetään valkoodi kaikilla muilla paitsi kolmosella, koska kolmosella ei näytetä mitään hintoja
      if ($naytatvale != 3) {
        $ulos .= sprintf("%3.s", $laskurow['valkoodi'])." ";
      }

      if ($naytatvale == 3 or $naytatvale == 4) {
        // kolmosella ei haluta näyttää myöskään ale:a
      }
      elseif ($naytatvale==2) {
        $ulos .= sprintf("%6.s"  , $tvtilausrivirow['aleryhma']);
      }
      else {
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $ulos .= sprintf("%5.s"  , $tvtilausrivirow["ale{$alepostfix}"])."%";
        }
      }

      $ulos .= "\n";

      if ($nimitysloput != "") {
        $ulos .= "    ".sprintf("%-77.77s", $nimitysloput)."\n";
      }

      if ($tvtilausrivirow['kommentti'] != "") {
        $ulos .= "     * ".sprintf("%-75.75s", $tvtilausrivirow['kommentti'])."\n";
      }

      // Lisätään mahdollinen saapumisaika tuoterivin alle, jos kyseessä on jt-rivi
      $saapumisaika = jt_saapumisaika_tilausvahvistus($tvtilausrivirow, $tvtilausrivirow["status"]);

      if (!empty($saapumisaika)) {
        $ulos .= $saapumisaika;
      }

      // Tehrään liitetiedosto
      $liite .= $rivino."\t".$tvtilausrivirow['nimitys']."\t".$tvtilausrivirow['tuoteno']."\t".$tvtilausrivirow['varattu']."\t";

      if ($naytatvale==3) {
        // ei hintoja kolmosella
      }
      elseif ($naytatvale==2) {
        $liite .= round(laskuval($tvtilausrivirow['myyntihinta'], $laskurow['vienti_kurssi']), $yhtiorow['hintapyoristys'])."\t";
      }
      else {
        $liite .= round(laskuval($tvtilausrivirow['hinta'], $laskurow['vienti_kurssi']), $yhtiorow['hintapyoristys'])."\t";
      }

      if ($naytatvale == 3 or $naytatvale == 4) {
        // kolmosella ei haluta näyttää myöskään ale:a
      }
      elseif ($naytatvale == 2) {
        $liite .= $tvtilausrivirow['aleryhma'];
      }
      else {
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $liite .= $tvtilausrivirow["ale{$alepostfix}"];
        }
      }
      $liite .= "\n";
    }

    $ulos .= "\n\n";
    $ulos .= t("Tämä on automaattinen viesti. Tähän sähköpostiin ei tarvitse vastata.", $kieli)."\n";

    if (strpos($_SERVER['SCRIPT_NAME'], "tulostakopio.php") === FALSE and $yhtiorow['tilausvahvistus_tallenna'] == 'K') {
      $tilausvahvistus_tallenna = "/tmp/Tilausvahvistus-".md5(uniqid(mt_rand(), true)).".txt";
      file_put_contents($tilausvahvistus_tallenna, $ulos);
      $_attachments = array(0 => array(
          "filename"        => $tilausvahvistus_tallenna,
          "newfilename"     => "tilvahv.txt",
          "ctype"           => "text")
      );
    }
    else {
      $tilausvahvistus_tallenna = "";
      $_attachments = "";
    }

  } // end rivejä löytyi
  if (strpos($laskurow['tilausvahvistus'], 'S') !== FALSE) {
    // Sähköpostin lähetykseen parametrit
    $parametri = array(
      "to"           => $tilvahasiakas['email'],
      "cc"           => "",
      "subject"      => $yhtiorow['nimi']." - ".t("Tilausvahvistus", $kieli)." $tulostettava_tunnus",
      "ctype"        => "text",
      "body"         => $ulos,
      "attachements" => $_attachments
    );

    pupesoft_sahkoposti($parametri);
  }


  if (strpos($laskurow['tilausvahvistus'], 'O') !== FALSE) {

    // Sähköpostin lähetykseen parametrit
    $parametri = array(
      "to"           => $kukarow['eposti'],
      "cc"           => "",
      "subject"      => $yhtiorow['nimi']." - ".t("Tilausvahvistus", $kieli)." $tulostettava_tunnus",
      "ctype"        => "text",
      "body"         => $ulos,
      "attachements" => $_attachments
    );

    pupesoft_sahkoposti($parametri);
  }
}
$ulos = "";
