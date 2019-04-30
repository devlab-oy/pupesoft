<?php

if (@include_once "pdflib/phppdflib.class.php") {
}
else {
  include_once "phppdflib.class.php";
}

$normi["height"]        = 12;
$normi["font"]          = "Helvetica";

$rectparam["width"]    = 0.3;
$rivinkorkeus          = 15;

if (!function_exists('tulosta_siirtoraportti_pdf')) {
  function tulosta_siirtoraportti_pdf ($params) {
    global $yhtiorow, $normi;

    #error_log(print_r($params, true));
    $params = explode(",", $params);

    // Haetaan tuotteen ostohinta
    $query = "SELECT tuote.tuoteno,
                tuotteen_toimittajat.ostohinta
              FROM tuote
                JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = tuote.tuoteno)
              WHERE tuote.yhtio = '{$yhtiorow["yhtio"]}'
                AND tuote.tuoteno = '{$params[2]}'";
    $tuotetiedot = mysql_fetch_assoc(pupe_query($query));

    $ostohinta = $params[0] * $tuotetiedot["ostohinta"];

    // Haetaan lähettävä varasto
    $query = "SELECT varastopaikat.nimitys
              FROM tuotepaikat
                JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio AND varastopaikat.tunnus = tuotepaikat.varasto)
              WHERE tuotepaikat.yhtio = '{$yhtiorow["yhtio"]}'
                AND tuotepaikat.tunnus = {$params[3]}
              GROUP BY tuotepaikat.tuoteno";
    $lahettava = mysql_fetch_assoc(pupe_query($query));

    // Haetaan vastaanottava varasto
    $query = "SELECT varastopaikat.nimitys
              FROM tuotepaikat
                JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio AND varastopaikat.tunnus = tuotepaikat.varasto)
              WHERE tuotepaikat.yhtio = '{$yhtiorow["yhtio"]}'
                AND tuotepaikat.tunnus = {$params[4]}";
    $vastaanottava = mysql_fetch_assoc(pupe_query($query));

    $sivu = 0;

    $pdf_siirtoraportti = new pdffile;

    $thispage = $pdf_siirtoraportti->new_page("a4");
    $page_siirtoraportti[$sivu] = $thispage;

    $pdf_siirtoraportti->set_default('margin-top',   20);
    $pdf_siirtoraportti->set_default('margin-bottom', 0);
    $pdf_siirtoraportti->set_default('margin-left',   0);
    $pdf_siirtoraportti->set_default('margin-right',  0);

    $pdf_siirtoraportti->draw_text(10, 680, date("d.m.Y"), $page_siirtoraportti[$sivu], $normi);

    $pdf_siirtoraportti->draw_text(10, 650, t("Siirretty:"), $page_siirtoraportti[$sivu], $normi);
    $pdf_siirtoraportti->draw_text(60, 650, $lahettava["nimitys"], $page_siirtoraportti[$sivu], $normi);
    $pdf_siirtoraportti->draw_text(110 + strlen($lahettava["nimitys"]), 650, "----->", $page_siirtoraportti[$sivu], $normi);
    $pdf_siirtoraportti->draw_text(150 + strlen($lahettava["nimitys"]), 650, $vastaanottava["nimitys"], $page_siirtoraportti[$sivu], $normi);

    $pdf_siirtoraportti->draw_text(10, 620, t("Tuotetta:"), $page_siirtoraportti[$sivu], $normi);
    $pdf_siirtoraportti->draw_text(70, 620, $params[2], $page_siirtoraportti[$sivu], $normi);
    
    $pdf_siirtoraportti->draw_text(240 + strlen($params[2]), 620, $params[0], $page_siirtoraportti[$sivu], $normi);
    $pdf_siirtoraportti->draw_text(270 + strlen($params[2]) + strlen($params[0]), 620, t("kpl"), $page_siirtoraportti[$sivu], $normi);

    $pdf_siirtoraportti->draw_text(10, 605, t("Ostohinta yhteensä:"), $page_siirtoraportti[$sivu], $normi);
    $pdf_siirtoraportti->draw_text(125, 605, $ostohinta, $page_siirtoraportti[$sivu], $normi);

    $pdf_siirtoraportti->draw_text(170 + strlen($ostohinta), 605, t("alv"), $page_siirtoraportti[$sivu], $normi);
    $pdf_siirtoraportti->draw_text(190 + strlen($ostohinta), 605, "0", $page_siirtoraportti[$sivu], $normi);

    siirtoraportti_tulostus_ruudulle($pdf_siirtoraportti);
  }
}

if (!function_exists('siirtoraportti_tulostus_ruudulle')) {
    function siirtoraportti_tulostus_ruudulle($pdf_siirtoraportti) {
   
    // Keksitään uudelle failille joku varmasti uniikki nimi:
    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $pdffilenimi = "/tmp/Siirtoraportti-".md5(uniqid(mt_rand(), true)).".pdf";

    // Kirjoitetaan pdf faili levylle..
    $fh = fopen($pdffilenimi, "w");
    if (fwrite($fh, $pdf_siirtoraportti->generate()) === FALSE) die("PDF create error $pdffilenimi");
    fclose($fh);

    echo file_get_contents($pdffilenimi);
  }
}
