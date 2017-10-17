<?php

if (!function_exists("paperi_alku")) {
  function paperi_alku() {
    global $pdf, $asiakastiedot, $yhteyshenkilo, $yhtiorow, $kukarow, $kala, $sivu, $rectparam, $norm, $pieni, $bold, $kaatosumma, $kieli, $vmehto, $trow;

    $firstpage = $pdf->new_page("a4");

    //Haetaan yhteyshenkilon tiedot
    $apuqu = "SELECT *
              from kuka
              where yhtio='$kukarow[yhtio]' and tunnus='$yhteyshenkilo'";
    $yres = pupe_query($apuqu);
    $yrow = mysql_fetch_assoc($yres);

    if (strlen(trim($asiakastiedot['laskutus_nimi'])) == 0) {
      $asiakastiedot['laskutus_nimi']        = $asiakastiedot['nimi'];
      $asiakastiedot['laskutus_nimitark']    = $asiakastiedot['nimitark'];
      $asiakastiedot['laskutus_osoite']      = $asiakastiedot['osoite'];
      $asiakastiedot['laskutus_postino']     = $asiakastiedot['postino'];
      $asiakastiedot['laskutus_postitp']     = $asiakastiedot['postitp'];
      $asiakastiedot['laskutus_maa']         = $asiakastiedot['maa'];
    }
    
    //Otsikko
    //$pdf->draw_rectangle(830, 20,  800, 580, $firstpage, $rectparam);
    $pdf->draw_text(30, 815,  $yhtiorow["nimi"], $firstpage);
    $pdf->draw_text(280, 815, t("Korkoerittely", $kieli), $firstpage, $bold);
    $pdf->draw_text(430, 815, t("Sivu", $kieli)." ".$sivu, $firstpage, $norm);

    //Vasen sarake
    //$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
    $pdf->draw_text(50, 729, t("Laskutusosoite", $kieli), $firstpage, $pieni);
    $pdf->draw_text(50, 717, $asiakastiedot["laskutus_nimi"], $firstpage, $norm);
    $pdf->draw_text(50, 707, $asiakastiedot["laskutus_nimitark"], $firstpage, $norm);
    $pdf->draw_text(50, 697, $asiakastiedot["laskutus_osoite"], $firstpage, $norm);
    $pdf->draw_text(50, 687, $asiakastiedot["laskutus_postino"]." ".$asiakastiedot["laskutus_postitp"], $firstpage, $norm);
    $pdf->draw_text(50, 677, $asiakastiedot["laskutus_maa"], $firstpage, $norm);

    //Oikea sarake
    $pdf->draw_rectangle(800, 300, 779, 580, $firstpage, $rectparam);
    $pdf->draw_rectangle(800, 420, 779, 580, $firstpage, $rectparam);
    $pdf->draw_text(310, 792, t("Päivämäärä", $kieli), $firstpage, $pieni);

    if (basename($_SERVER["SCRIPT_FILENAME"]) != 'muutosite.php')
      $pdf->draw_text(310, 782, tv1dateconv(date('Y-m-d')), $firstpage, $norm);
    else
      $pdf->draw_text(310, 782, tv1dateconv($trow['tapvm']), $firstpage, $norm);

    $pdf->draw_text(430, 792, t("Asiaa hoitaa", $kieli), $firstpage, $pieni);
    $pdf->draw_text(430, 782, $yrow["nimi"], $firstpage, $norm);

    $pdf->draw_rectangle(779, 300, 758, 580, $firstpage, $rectparam);
    $pdf->draw_rectangle(779, 420, 758, 580, $firstpage, $rectparam);
    $pdf->draw_text(310, 771, t("Eräpäivä", $kieli), $firstpage, $pieni);


    if (basename($_SERVER["SCRIPT_FILENAME"]) != 'muutosite.php') {
      // Etsitään maksuehto
      $query = "SELECT *
                FROM maksuehto
                WHERE tunnus='$vmehto' and yhtio='$kukarow[yhtio]'";
      $presult = pupe_query($query);
      $xrow = mysql_fetch_assoc($presult);

      if ($xrow['abs_pvm'] === null) {

        $seurday   = sprintf('%02d', date("j", mktime(0, 0, 0, date("n"), date("j")+$xrow["rel_pvm"],  date("Y"))));
        $seurmonth = sprintf('%02d', date("n", mktime(0, 0, 0, date("n"), date("j")+$xrow["rel_pvm"],  date("Y"))));
        $seuryear  = sprintf('%02d', date("Y", mktime(0, 0, 0, date("n"), date("j")+$xrow["rel_pvm"],  date("Y"))));

        $erapvm = $seuryear."-".$seurmonth."-".$seurday;
      }
      else {
        $erapvm = "'$xrow[abs_pvm]'";
      }
    }
    else {
      $erapvm = $trow['erpcm'];
    }

    $pdf->draw_text(310, 761, tv1dateconv($erapvm), $firstpage, $norm);

    $pdf->draw_text(430, 771, t("Puhelin", $kieli),  $firstpage, $pieni);
    $pdf->draw_text(430, 761, $yrow["puhno"],     $firstpage, $norm);

    $pdf->draw_rectangle(758, 300, 737, 580, $firstpage, $rectparam);
    $pdf->draw_rectangle(758, 420, 737, 580, $firstpage, $rectparam);
    $pdf->draw_text(310, 750, t("Ytunnus/Asiakasnumero", $kieli),   $firstpage, $pieni);
    $pdf->draw_text(310, 740, $asiakastiedot["ytunnus"],       $firstpage, $norm);

    $pdf->draw_text(430, 750, t("Sähköposti", $kieli),   $firstpage, $pieni);
    $pdf->draw_text(430, 740, $yrow["eposti"],       $firstpage, $norm);

    $kala = 620;

    //Laskurivien otsikkotiedot
    //eka rivi
    $pdf->draw_text(30,  $kala, t("Laskunumero", $kieli),    $firstpage, $pieni);
    $pdf->draw_text(100, $kala, t("Päivämäärä", $kieli),    $firstpage, $pieni);
    $pdf->draw_text(160, $kala, t("Eräpäivä", $kieli),      $firstpage, $pieni);
    $pdf->draw_text(220, $kala, t("Maksettu", $kieli),      $firstpage, $pieni);
    $pdf->draw_text(280, $kala, t("Myöhässä pv.", $kieli),    $firstpage, $pieni);
    $pdf->draw_text(340, $kala, t("Viivästykorko-%", $kieli),  $firstpage, $pieni);
    $pdf->draw_text(425, $kala, t("Laskun summa", $kieli),    $firstpage, $pieni);
    $pdf->draw_text(520, $kala, t("Korko", $kieli),        $firstpage, $pieni);

    $kala -= 15;

    return $firstpage;
  }
}

if (!function_exists("paperi_rivi")) {
  function paperi_rivi($firstpage, $summa) {
    global $firstpage, $pdf, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $kieli;

    if ($lask >= 37) {
      $sivu++;
      paperi_loppu($firstpage, '');
      $firstpage = paperi_alku();
      $kala = 605;
      $lask = 1;
    }

    $pdf->draw_text(30,  $kala, $row["laskunro"],      $firstpage, $norm);
    $pdf->draw_text(100, $kala, tv1dateconv($row["tapvm"]), $firstpage, $norm);
    $pdf->draw_text(160, $kala, tv1dateconv($row["erpcm"]), $firstpage, $norm);
    $pdf->draw_text(220, $kala, tv1dateconv($row["mapvm"]), $firstpage, $norm);
    $pdf->draw_text(285, $kala, $row["ika"],        $firstpage, $norm);
    $pdf->draw_text(340, $kala, $row["viikorkopros"],    $firstpage, $norm);


    $oikpos = $pdf->strlen($row["summa"], $norm);
    $pdf->draw_text(475-$oikpos, $kala, $row["summa"], $firstpage, $norm);

    $oikpos = $pdf->strlen($row["korkosumma"], $norm);
    $pdf->draw_text(540-$oikpos, $kala, $row["korkosumma"], $firstpage, $norm);

    $kala = $kala - 13;

    $lask++;
    $summa+=$row["korkosumma"];
    return $summa;
  }
}

if (!function_exists("paperi_loppu")) {
  function paperi_loppu($firstpage, $summa, $masrow = "") {

    global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kaatosumma, $kieli, $toimipaikkarow;

    //yhteensärivi
    if ($summa == '') {
      $pdf->draw_text(420, 75, t('Jatkuu...', $kieli), $firstpage, $norm);
    }
    else {
      $pdf->draw_text(420, 75, t('Yhteensä', $kieli).":", $firstpage, $norm);

      $oikpos = $pdf->strlen($summa, $norm);
      $pdf->draw_text(540-$oikpos, 75, $summa, $firstpage, $norm);
      $pdf->draw_text(545, 75, $yhtiorow['valkoodi'], $firstpage, $norm);
    }

    //Alimmat kolme laatikkoa, yhtiötietoja
    $pdf->draw_rectangle(70, 20, 20, 580,  $firstpage, $rectparam);
    $pdf->draw_rectangle(70, 207, 20, 580,  $firstpage, $rectparam);
    $pdf->draw_rectangle(70, 394, 20, 580,  $firstpage, $rectparam);

    $pdf->draw_text(30, 55, $toimipaikkarow["nimi"],    $firstpage, $pieni);
    $pdf->draw_text(30, 45, $toimipaikkarow["osoite"],  $firstpage, $pieni);
    $pdf->draw_text(30, 35, $toimipaikkarow["postino"]."  ".$toimipaikkarow["postitp"],  $firstpage, $pieni);
    $pdf->draw_text(30, 25, $toimipaikkarow["maa"],    $firstpage, $pieni);

    $pdf->draw_text(217, 55, t("Puhelin", $kieli).":",    $firstpage, $pieni);
    $pdf->draw_text(247, 55, $toimipaikkarow["puhelin"],      $firstpage, $pieni);
    $pdf->draw_text(217, 45, t("Fax", $kieli).":",      $firstpage, $pieni);
    $pdf->draw_text(247, 45, $toimipaikkarow["fax"],        $firstpage, $pieni);
    $pdf->draw_text(217, 35, t("Email", $kieli).":",    $firstpage, $pieni);
    $pdf->draw_text(247, 35, $toimipaikkarow["email"],      $firstpage, $pieni);

    $pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",    $firstpage, $pieni);
    $pdf->draw_text(444, 55, $toimipaikkarow["vat_numero"],      $firstpage, $pieni);
    $pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",  $firstpage, $pieni);
    $pdf->draw_text(444, 45, $toimipaikkarow["kotipaikka"],    $firstpage, $pieni);
    $pdf->draw_text(404, 35, t("Enn.per.rek", $kieli),    $firstpage, $pieni);
    $pdf->draw_text(404, 25, t("Alv.rek", $kieli),      $firstpage, $pieni);

  }
}

require 'pdflib/phppdflib.class.php';

//echo "<font class='message'>Korkoerittely tulostuu...</font>";
flush();

//PDF parametrit
$pdf = new pdffile;
$pdf->set_default('margin-top',   0);
$pdf->set_default('margin-bottom',   0);
$pdf->set_default('margin-left',   0);
$pdf->set_default('margin-right',   0);
$rectparam["width"] = 0.3;

$norm["height"] = 10;
$norm["font"] = "Times-Roman";

$pieni["height"] = 8;
$pieni["font"] = "Times-Roman";

$bold["height"] = 14;
$bold["font"] = "Helvetica-Bold";

// defaultteja
$lask = 1;
$sivu = 1;

// aloitellaan laskun teko
$xquery='';
for ($i=0; $i < count($lasku_tunnus); $i++) {
  if ($i != 0) {
    $xquery=$xquery . ",";
  }

  $xquery=$xquery . "$lasku_tunnus[$i]";
}

if (basename($_SERVER["SCRIPT_FILENAME"]) == 'muutosite.php') {
  $query = "SELECT tunnus
            FROM kuka
            WHERE yhtio = '$kukarow[yhtio]'
            and kuka    = '$trow[laatija]'";
  $result = pupe_query($query);
  $yrow = mysql_fetch_assoc($result);
  $yhteyshenkilo = $yrow['tunnus'];

  $params = array(
    'liitostunnus' => $trow['liitostunnus'],
    'tapvm' => $trow['tapvm'],
  );
}
else {
  $params = array(
    'tunnukset' => $xquery,
  );
}

$korkorivit = laske_korko($params);

//otetaan asiakastiedot ekalta laskulta
$asiakastiedot = $korkorivit[0];

if ($asiakastiedot["yhtio_toimipaikka"] != '' and $asiakastiedot["yhtio_toimipaikka"] != 0) {
  $toimipaikkaquery = "SELECT *
                       FROM yhtion_toimipaikat
                       WHERE yhtio = '$kukarow[yhtio]'
                       AND tunnus  = '$asiakastiedot[yhtio_toimipaikka]'";
  $toimipaikkares = pupe_query($toimipaikkaquery);
  $toimipaikkarow = mysql_fetch_assoc($toimipaikkares);
}
else {
  $toimipaikkarow["nimi"]     = $yhtiorow["nimi"];
  $toimipaikkarow["osoite"]     = $yhtiorow["osoite"];
  $toimipaikkarow["postino"]     = $yhtiorow["postino"];
  $toimipaikkarow["postitp"]     = $yhtiorow["postitp"];
  $toimipaikkarow["maa"]       = $yhtiorow["maa"];
  $toimipaikkarow["puhelin"]     = $yhtiorow["puhelin"];
  $toimipaikkarow["fax"]       = $yhtiorow["fax"];
  $toimipaikkarow["email"]     = $yhtiorow["email"];
  $toimipaikkarow["vat_numero"]   = $yhtiorow["ytunnus"];
  $toimipaikkarow["kotipaikka"]   = $yhtiorow["kotipaikka"];
}

$query = "SELECT *
          FROM asiakas
          WHERE yhtio='$kukarow[yhtio]'
          AND tunnus = '$asiakastiedot[liitostunnus]'";
$asiakasresult = pupe_query($query);
$asiakastiedot = mysql_fetch_assoc($asiakasresult);

$query = "SELECT *
          FROM maksuehto
          WHERE yhtio='$kukarow[yhtio]'
          AND tunnus = '$asiakastiedot[maksuehto]'";
$mehtores = pupe_query($query);
$mehtorow = mysql_fetch_assoc($mehtores);

//Otetaan tässä asiakkaan kieli talteen
$kieli = $asiakastiedot["kieli"];

//Liitostunnusta tarvitaan tee_korkolasku.inc failissa
$liitostunnus  = $asiakastiedot["tunnus"];

$firstpage = paperi_alku();

//karhu_begin_work();
$summa = 0;

foreach ($korkorivit as $row) {
  $summa = paperi_rivi($firstpage, $summa);
  //päivitetään korkosumma
  if (basename($_SERVER["SCRIPT_FILENAME"]) != 'muutosite.php') {
    $query = "UPDATE lasku
              SET viikorkoeur = '$row[korkosumma]'
              WHERE tunnus = '$row[tunnus]'
              and yhtio    = '$kukarow[yhtio]'";
    $eurresult = pupe_query($query);
  }
}

$loppusumma = sprintf('%.2f', $summa);

paperi_loppu($firstpage, $loppusumma, $mehtorow);

if (basename($_SERVER["SCRIPT_FILENAME"]) != 'muutosite.php') {
  //keksitään uudelle failille joku varmasti uniikki nimi:
  list($usec, $sec) = explode(' ', microtime());
  mt_srand((float) $sec + ((float) $usec * 100000));
  $pdffilenimi = "/tmp/korkoerittely-".md5(uniqid(mt_rand(), true)).".pdf";

  //kirjoitetaan pdf faili levylle..
  $fh = fopen($pdffilenimi, "w");
  if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
  fclose($fh);

  // itse print komento...
  $query = "SELECT komento
            from kirjoittimet
            where yhtio = '$kukarow[yhtio]'
            and tunnus  = '$kirjoitin'";
  $kires = pupe_query($query);

  if (mysql_num_rows($kires) == 1) {
    $kirow = mysql_fetch_assoc($kires);

    if ($kirow["komento"] == 'email') {
      $liite = $pdffilenimi;
      $kutsu = "korkoerittely-$asiakastiedot[ytunnus]";
      $ctype = "pdf";
      require "inc/sahkoposti.inc";
    }
    else {
      $line  = exec("$kirow[komento] $pdffilenimi");
    }
  }
}
else {
  echo $pdf->generate();
}

unset($pdf);
unset($firstpage);
