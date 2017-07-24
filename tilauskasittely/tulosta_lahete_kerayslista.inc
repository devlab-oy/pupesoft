<?php

require_once "pdflib/phppdflib.class.php";

$norm["height"]       = 10;
$norm["font"]         = "Times-Roman";

$pieni["height"]       = 8;
$pieni["font"]         = "Times-Roman";

$boldi["height"]       = 10;
$boldi["font"]         = "Times-Bold";

$pieni_boldi["height"]  = 8;
$pieni_boldi["font"]    = "Times-Bold";

$keraaja_font["height"] = 12;
$keraaja_font["font"]   = "Helvetica";

$iso["height"]         = 14;
$iso["font"]         = "Helvetica-Bold";

$iso_boldi["height"]    = 24;
$iso_boldi["font"]       = "Helvetica-Bold";

$rectparam["width"]     = 0.3;
$rivinkorkeus        = 17;

if (!function_exists('alku_kerayslista')) {
  function alku_kerayslista($params) {
    global $kukarow, $yhtiorow;

    if (skippa_kerayslista_pdf($params)) {
      return $params;
    }

    // Luodaan muuttujat
    extract($params);

    if ($kerayslistatyyppi == 'EXCEL1') {

      include 'inc/pupeExcel.inc';

      $thispage    = new pupeExcel();
      $boldi = array("bold" => TRUE);

      $thispage->writeString(0, 0, "Outbound Order", $boldi);
      $thispage->writeString(1, 0, $laskurow['tunnus']);
      $thispage->writeString(2, 0, "Delivery address", $boldi);
      $thispage->writeString(3, 0, $laskurow['toim_nimi']." ".$laskurow['toim_nimitark']);
      $thispage->writeString(4, 0, $laskurow['toim_osoite']);
      $thispage->writeString(5, 0, $laskurow['toim_postino']);
      $thispage->writeString(6, 0, $laskurow['toim_postitp']);
      $thispage->writeString(7, 0, $laskurow['toim_maa']);

      $kala = 9;
    }
    elseif ($kerayslistatyyppi == 'EXCEL2' or $kerayslistatyyppi == 'EXCEL3') {

      include 'inc/pupeExcel.inc';

      $thispage    = new pupeExcel();
      $boldi = array("bold" => TRUE);

      $thispage->writeString(0, 0, "Level", $boldi);
      $thispage->writeString(0, 1, "Ordernr", $boldi);
      $thispage->writeString(0, 2, "Company", $boldi);
      $thispage->writeString(0, 3, "Contact", $boldi);
      $thispage->writeString(0, 4, "Address", $boldi);
      $thispage->writeString(0, 5, "PostalCode", $boldi);
      $thispage->writeString(0, 6, "City", $boldi);
      $thispage->writeString(0, 7, "Country", $boldi);

      $thispage->writeString(1, 0, "Header");
      $thispage->writeString(1, 1, $laskurow['tunnus']);
      $thispage->writeString(1, 2, $laskurow['toim_nimi']." ".$laskurow['toim_nimitark']);
      $thispage->writeString(1, 3, $laskurow['tilausyhteyshenkilo']);
      $thispage->writeString(1, 4, $laskurow['toim_osoite']);
      $thispage->writeString(1, 5, $laskurow['toim_postino']);
      $thispage->writeString(1, 6, $laskurow['toim_postitp']);
      $thispage->writeString(1, 7, $laskurow['toim_maa']);

      $kala = 3;
    }
    else {
      //PDF parametrit
      if ($pdf === NULL) {
        $pdf = new pdffile;
        $pdf->set_default('margin-top',   0);
        $pdf->set_default('margin-bottom',   0);
        $pdf->set_default('margin-left',   0);
        $pdf->set_default('margin-right',   0);
      }

      $thispage = $pdf->new_page("a4");

      $page[$sivu] = $thispage;

      tulosta_logo_pdf($pdf, $thispage, $laskurow);

      $_ker_chk = ($yhtiorow['kerayserat'] == 'K' and isset($kerayseran_numero) and $kerayseran_numero > 0);
      $_rek_chk = ($toim == "VASTAANOTA_REKLAMAATIO" and in_array($yhtiorow['reklamaation_kasittely'], array('U', 'X')));

      $_ker_rek = ($_ker_chk or $_rek_chk);

      if ($_ker_rek) {

        $query = "SELECT count(distinct liitostunnus) asiakkaita
                  FROM lasku
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  IN ($tilausnumeroita)";
        $chk_res = pupe_query($query);
        $chk_row = mysql_fetch_assoc($chk_res);

        if ($chk_row["asiakkaita"] > 1) {
          $laskurow['nimi']       = t("Useita");
          $laskurow['nimitark']    = "";
          $laskurow['osoite']      = "";
          $laskurow['postino']    = "";
          $laskurow['postitp']    = "";
          $laskurow['maa']      = "";
          $laskurow['toim_nimi']    = t("Useita");
          $laskurow['toim_nimitark']  = "";
          $laskurow['toim_osoite']  = "";
          $laskurow['toim_postino']  = "";
          $laskurow['toim_postitp']  = "";
          $laskurow['toim_maa']    = "";

          $laskurow['myyja']      = "";
          $laskurow['laatija']    = "";
          $laskurow['maksuehto']    = "";
          $laskurow['toimitusehto']  = "";
          $laskurow['toimitustapa']  = "";
          $laskurow['kohde']      = "";
          $laskurow['viesti']      = "";
          $laskurow['comments']    = "";
          $laskurow['ohjausmerkki']  = "";
          $laskurow['sisviesti2']    = "";
          $laskurow['tilausyhteyshenkilo']  = "";
          $laskurow['asiakkaan_tilausnumero']  = "";
        }
      }

      // haetaan maksuehdon tiedot
      $query  = "SELECT *
                 FROM maksuehto
                 WHERE yhtio='$kukarow[yhtio]'
                 AND tunnus='$laskurow[maksuehto]'";
      $masresult = pupe_query($query);
      $masrow = mysql_fetch_assoc($masresult);

      //maksuehto tekstinä
      $maksuehto = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli);

      $pdf->draw_text(550, 815, $laskurow["hyvaksynnanmuutos"],   $thispage, $iso);
      $prioriteetin_kuvaus = ($laskurow["hyvaksynnanmuutos"] != "") ? t_avainsana("ASIAKASLUOKKA", $kieli, "and avainsana.selite = '{$laskurow["hyvaksynnanmuutos"]}'", "", "", "selitetark") : "X";

      if ($tyyppi == "SIIRTOLISTA") {
        $pdf->draw_text(310, 815, t("Siirtolista", $kieli),     $thispage, $iso);
      }
      elseif ($tyyppi == "REKLAMAATIO") {
        $pdf->draw_text(310, 815, t("Reklamaatio/Purkulista", $kieli), $thispage, $iso);
      }
      elseif ($tyyppi == "TAKUU") {
        $pdf->draw_text(310, 815, t("Takuu", $kieli),     $thispage, $iso);
      }
      elseif ($tyyppi == "MYYNTITILI") {
        $pdf->draw_text(310, 815, t("Myyntitili", $kieli),     $thispage, $iso);
      }
      else {
        $pdf->draw_text(310, 815, t("Keräyslista", $kieli),     $thispage, $iso);
      }

      $pdf->draw_text(310, 803, t("Sivu", $kieli),         $thispage, $norm);

      //Vasen sarake
      //$pdf->draw_rectangle(737, 20,  674, 300, $thispage, $rectparam);
      $pdf->draw_text(50, 729, t("Ostaja", $kieli),       $thispage, $pieni);
      $pdf->draw_text(50, 717, $laskurow["nimi"],       $thispage, $norm);
      $pdf->draw_text(50, 707, $laskurow["nimitark"],      $thispage, $norm);
      $pdf->draw_text(50, 697, $laskurow["osoite"],       $thispage, $norm);
      $pdf->draw_text(50, 687, $laskurow["postino"]." ".$laskurow["postitp"], $thispage, $norm);
      $pdf->draw_text(50, 677, $laskurow["maa"],         $thispage, $norm);


      //$pdf->draw_rectangle(674, 20,  611, 300, $thispage, $rectparam);
      $pdf->draw_text(50, 656, t("Toimitusosoite", $kieli),   $thispage, $pieni);
      $pdf->draw_text(50, 644, $laskurow["toim_nimi"],     $thispage, $boldi);
      $pdf->draw_text(50, 634, $laskurow["toim_nimitark"],   $thispage, $boldi);
      $pdf->draw_text(50, 624, $laskurow["toim_osoite"],     $thispage, $boldi);
      $pdf->draw_text(50, 614, $laskurow["toim_postino"]." ".$laskurow["toim_postitp"], $thispage, $boldi);
      $pdf->draw_text(50, 604, $laskurow["toim_maa"],     $thispage, $boldi);

      //Oikea sarake
      $pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);

      if ($yhtiorow['kerayserat'] == 'K' and isset($kerayseran_numero) and $kerayseran_numero > 0) {
        $pdf->draw_text(310, 792, t("Keräyserä", $kieli),       $thispage, $pieni);
        $pdf->draw_text(310, 782, $kerayseran_numero,        $thispage, $boldi);
      }
      else {
        list($ff_string, $ff_font) = pdf_fontfit($tilausnumeroita, 270, $pdf, $boldi);
        $pdf->draw_text(310, 792, t("Tilausnumero(t)", $kieli),     $thispage, $pieni);
        $pdf->draw_text(310, 782, $ff_string, $thispage, $ff_font);
      }

      $pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
      $pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 771, t("Keräysaika", $kieli),           $thispage, $pieni);

      $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

      if ($laskurow["keraysvko"] != '') {
        $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["kerayspvm"]));

        if ($laskurow['keraysvko'] != '7') {
          $taika .= "/".$DAY_ARRAY[$laskurow["keraysvko"]];
        }

        $pdf->draw_text(310, 761, $taika,                 $thispage, $boldi);
      }
      else {
        $pdf->draw_text(310, 761, tv1dateconv($laskurow["kerayspvm"]),   $thispage, $boldi);
      }

      $pdf->draw_text(430, 771, t("Toimitusaika", $kieli),         $thispage, $pieni);

      if ($laskurow["toimvko"] != '') {
        $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

        if ($laskurow['toimvko'] != '7') {
          $taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
        }

        $pdf->draw_text(430, 761, $taika,                 $thispage, $boldi);
      }
      else {
        $pdf->draw_text(430, 761, tv1dateconv($laskurow["toimaika"]),   $thispage, $boldi);
      }

      if (($asrow["asiakasnro"] == "" or $asrow["asiakasnro"] == 0) and $laskurow["ytunnus"] != "") {
        $asrow["asiakasnro"] = tulosta_ytunnus($laskurow["ytunnus"], $laskurow["maa"], $laskurow["vienti"]);
      }

      $pdf->draw_rectangle(758, 300, 737, 580, $thispage, $rectparam);
      $pdf->draw_rectangle(758, 420, 737, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 750, t("Asiakasnumero", $kieli),     $thispage, $pieni);
      $pdf->draw_text(310, 740, $asrow["asiakasnro"],       $thispage, $norm);
      $pdf->draw_text(430, 750, t("Käsitelty pvm", $kieli),        $thispage, $pieni);
      $pdf->draw_text(430, 740, tv1dateconv($laskurow['h1time'], "pitka"),     $thispage, $norm);


      $pdf->draw_rectangle(737, 300, 716, 580, $thispage, $rectparam);
      $pdf->draw_rectangle(737, 420, 716, 580, $thispage, $rectparam);

      if ($yhtiorow['kerayserat'] == 'K' and isset($kerayseran_numero) and $kerayseran_numero > 0) {

        $pdf->draw_text(310, 729, t("Kerääjä", $kieli),     $thispage, $pieni);

        //etsitään myyjän nimi
        $query  = "SELECT kuka.nimi
                   FROM kerayserat
                   JOIN kuka ON (kuka.yhtio = kerayserat.yhtio AND kuka.kuka = kerayserat.laatija)
                   WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                   AND kerayserat.nro     = '{$kerayseran_numero}'
                   LIMIT 1";
        $keraaja_res = pupe_query($query);
        $keraaja_row = mysql_fetch_assoc($keraaja_res);

        $pdf->draw_text(310, 719, $keraaja_row["nimi"], $thispage, $norm);

      }
      else {
        $pdf->draw_text(310, 729, t("Myyjä", $kieli),     $thispage, $pieni);

        //etsitään myyjän nimi
        $query  = "SELECT nimi, kuka
                   FROM kuka
                   WHERE tunnus = '$laskurow[myyja]'
                   AND yhtio    = '$kukarow[yhtio]'";
        $myyresult = pupe_query($query);
        $myyrow = mysql_fetch_assoc($myyresult);

        $pdf->draw_text(310, 719, $myyrow["nimi"],                     $thispage, $norm);
      }

      $pdf->draw_text(430, 729, t("Tilauspvm", $kieli),                 $thispage, $pieni);
      $pdf->draw_text(430, 719, tv1dateconv($laskurow["luontiaika"], "pitka"),     $thispage, $norm);

      $ttapae = $laskurow["toimitustapa"];

      //etsitään löytyykö rahtisopimusta
      $rahtirow = hae_rahtisopimusnumero($ttapae, $laskurow["ytunnus"], $laskurow["liitostunnus"], false, $laskurow["rahtisopimus"]);

      if ($rahtirow["rahtisopimus"] != "") {
        $ttapae .= " / ".$rahtirow["rahtisopimus"];
      }
      if ($laskurow["rahtivapaa"] == 'o' and onko_rahtituote_syotetty($laskurow) == 0) {
        $ttapae .= " / ". t("Rahtivapaa", $kieli);
      }

      $pdf->draw_rectangle(716, 300, 695, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 708, t("Toimitustapa", $kieli)." / ".t("Rahtisopimus", $kieli), $thispage, $pieni);
      $pdf->draw_text(310, 698, $ttapae,     $thispage, $boldi);

      $pdf->draw_rectangle(695, 300, 674, 580, $thispage, $rectparam);

      $toimehto = t_avainsana("TOIMEHTO", $kieli, " and concat(selite,' ',selitetark) = '{$laskurow["toimitusehto"]}'", "", "", "selitetark");
      if ($toimehto == "") $toimehto = $laskurow["toimitusehto"];
      else $toimehto = substr($laskurow["toimitusehto"], 0, 3)." ".$toimehto;

      $pdf->draw_text(310, 687, t("Toimitusehto", $kieli),   $thispage, $pieni);
      $pdf->draw_text(310, 677, $toimehto,           $thispage, $norm);

      $pdf->draw_rectangle(674, 300, 653, 580, $thispage, $rectparam);
      $pdf->draw_text(310, 666, t("Maksuehto", $kieli),     $thispage, $pieni);
      $pdf->draw_text(310, 656, $maksuehto,           $thispage, $norm);

      $pdf->draw_rectangle(653, 300, 632, 580, $thispage, $rectparam);

      if ($myyrow['kuka'] != $laskurow['laatija']) {
        //etsitään laatijan nimi
        $query  = "SELECT nimi
                   from kuka
                   where kuka='$laskurow[laatija]' and yhtio='$kukarow[yhtio]'";
        $laaresult = pupe_query($query);
        $laarow = mysql_fetch_assoc($laaresult);

        $pdf->draw_text(310, 645, t("Laatija", $kieli), $thispage, $pieni);
        $pdf->draw_text(310, 635, $laarow["nimi"], $thispage, $norm);
      }
      else {
        $pdf->draw_text(310, 645, "", $thispage, $pieni);
        $pdf->draw_text(310, 635, "", $thispage, $norm);
      }

      if ($laskurow['pakkaamo'] > 0 and $yhtiorow['pakkaamolokerot'] != '') {
        $keraaja_teksti = "";

        $query = "SELECT nimi, lokero
                  FROM pakkaamo
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tunnus  = '$laskurow[pakkaamo]'
                  LIMIT 1";
        $pakkaamo_fetch_res = pupe_query($query);

        if (mysql_num_rows($pakkaamo_fetch_res) == 1) {
          $pakkaamo_fetch_row = mysql_fetch_assoc($pakkaamo_fetch_res);

          $keraaja_teksti = t("Pakkaamo", $kieli).": $pakkaamo_fetch_row[nimi] ".t("Lokero", $kieli).": $pakkaamo_fetch_row[lokero]";
        }

        $pdf->draw_rectangle(632, 300, 611, 580, $thispage, $rectparam);
        $pdf->draw_text(310, 624, t("Kerääjälle", $kieli),   $thispage, $pieni);
        $pdf->draw_text(310, 614, $keraaja_teksti,   $thispage, $keraaja_font);
      }


      $komm = "";

      if ($prioriteetin_kuvaus != "X") {
        $komm .= "\n$prioriteetin_kuvaus";
      }

      if ($laskurow['ohjelma_moduli'] == "MAGENTO" and !empty($laskurow['toim_puh'])) {
        $laskurow['sisviesti2'] .= "\n".t("Asiakkaan puh", $kieli).": $laskurow[toim_puh]";
      }

      if (trim($laskurow['tilausyhteyshenkilo']) != '') {
        $komm .= "\n".t("Tilaaja", $kieli).":###".$laskurow['tilausyhteyshenkilo'];
      }

      if (trim($laskurow['asiakkaan_tilausnumero']) != '') {
        $komm .= "\n".t("Tilauksenne", $kieli).":###".$laskurow['asiakkaan_tilausnumero'];
      }

      if (trim($laskurow['kohde']) != '') {
        $komm .= "\n".t("Kohde", $kieli).":###".$laskurow['kohde'];
      }

      if (trim($laskurow['viesti']) != '') {
        $komm .= "\n".t("Tilausviite", $kieli).":###".$laskurow['viesti'];
      }

      if (trim($laskurow['comments']) != '') {
        $komm .= "\n".t("Kommentti", $kieli).":###".wordwrap(str_replace("\n", "\n###", $laskurow['comments']), 70, "\n###");
      }

      if (trim($laskurow['ohjausmerkki']) != '') {
        $komm .= "\n".t("Ohjausmerkki", $kieli).":###".wordwrap(str_replace("\n", "\n###", $laskurow['ohjausmerkki']), 70, "\n###");
      }

      if (trim($laskurow['sisviesti2']) != '') {
        $komm .= "\n".t("Kommentti", $kieli)." 2:###".wordwrap(str_replace("\n", "\n###", $laskurow['sisviesti2']), 70, "\n###");
      }

      // Jos JT-tilaus niin tarkistetaan monta jälkitilausta on tehty.
      if ($laskurow['clearing'] == 'JT-TILAUS') {

        // vanha_otunnus on alkuperäinen tilausnumero
        // Haetaan kaikkien tällä tilauksella olevien tilausrivien alkuperäiset tilausnumerot
        $query = "SELECT group_concat(DISTINCT tlt2.vanha_otunnus) alkuperaiset_tilaukset
                  FROM tilausrivi
                  JOIN tilausrivin_lisatiedot as tlt2 USE INDEX (tilausrivitunnus) ON (tlt2.yhtio = tilausrivi.yhtio AND tlt2.tilausrivitunnus = tilausrivi.tunnus)
                  WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
                  AND tilausrivi.otunnus  IN ($tilausnumeroita)
                  and tilausrivi.var     != 'O'
                  AND tilausrivi.tyyppi  != 'D'";
        $query_result = pupe_query($query);
        $isa_tilaus = mysql_fetch_assoc($query_result);

        // Lasketaan kuinka monta toimitettua tilausriviä löytyy per alkuperäinen tilaus
        if ($isa_tilaus['alkuperaiset_tilaukset'] != NULL) {
          $query = "SELECT tlt2.vanha_otunnus, count(distinct DATE(tilausrivi.toimitettuaika))+1 as toimituksia
                    FROM tilausrivin_lisatiedot as tlt2
                    LEFT JOIN tilausrivi on (tilausrivi.tunnus = tlt2.tilausrivitunnus AND tilausrivi.tyyppi != 'D' AND tilausrivi.toimitettuaika != '0000-00-00 00:00:00')
                    WHERE tlt2.yhtio        = '{$kukarow['yhtio']}'
                    and tilausrivi.var     != 'O'
                    AND tlt2.vanha_otunnus  IN ({$isa_tilaus['alkuperaiset_tilaukset']})
                    GROUP BY tlt2.vanha_otunnus
                    ORDER BY toimituksia";
          $query_result = pupe_query($query);

          // Jos yksikin tilaus jossa ei ole toimitettuja rivejä
          while ($ensimmainen_toimitus_row = mysql_fetch_assoc($query_result)) {
            $komm .= "\n{$ensimmainen_toimitus_row['toimituksia']}. ".t("toimitus tilauksesta")." {$ensimmainen_toimitus_row['vanha_otunnus']}";
          }
        }
      }

      // Jos keräyslistat/lähetteet tulostetaan heti, niin voidaan tulostaa JT tieto mukaan
      if (trim($laskurow['clearing']) == 'JT-TILAUS' and $yhtiorow["lahetteen_tulostustapa"] != "K" and $yhtiorow["lahetteen_tulostustapa"] != "L") {
        $komm .= "\n*** ".t("JÄLKITOIMITUS")." ***";
      }

      //Tulostetaan laskun kommentti
      if (trim($komm) != '') {
        $kommentit = explode("\n", trim($komm));

        $pohja  = 577;
        $maxoik = 0;

        foreach ($kommentit as $kommentti) {
          if (strpos($kommentti, "###") !== FALSE) {
            list($o, $v) = explode("###", $kommentti);

            $oikpos = $pdf->strlen($o, $boldi);

            if ($oikpos > $maxoik) {
              $maxoik = $oikpos;
            }
          }
        }

        foreach ($kommentit as $kommentti) {

          if (strpos($kommentti, "###") !== false) {
            list($o, $v) = explode("###", $kommentti);

            $pdf->draw_text(35, $pohja, trim($o), $thispage, $boldi);
            $pdf->draw_text(35+$maxoik+5, $pohja, trim($v), $thispage, $norm);
          }
          else {
            $pdf->draw_text(35, $pohja, trim($kommentti), $thispage, $norm);
          }

          $pohja = $pohja - 12;
        }
        $kala = $pohja-30;
      }
      else {
        $kala = 560;
      }

      // Viivakoodi
      if ($yhtiorow['kerayserat'] == 'K' and isset($kerayseran_numero) and $kerayseran_numero > 0) {
        $barcode = strlen($kerayseran_numero) < 6 ? str_pad((int) $kerayseran_numero, 6, "0", STR_PAD_LEFT) : $kerayseran_numero;
      }
      else {
        $barcode  = $laskurow["tunnus"];
      }

      $data = viivakoodi(trim($barcode), "code128", 150, 30, "");
      $image = $pdf->jfif_embed($data);
      $pdf->image_place($image, 805, 440, $thispage, array("scale" => 0.3));

      // Tehdään asiakasnumero-viivakoodi
      if ($kukarow['yhtio'] == 'hair' and $asrow["asiakasnro"] > 0) {
        if ($laskurow['pakkaamo'] > 0 and $yhtiorow['pakkaamolokerot'] != '') {
          $bottom_barcode = 582;
          $bottom_barcode_text = 578;
        }
        else {
          $bottom_barcode = 605;
          $bottom_barcode_text = 600;
        }

        $data = viivakoodi(trim($asrow["asiakasnro"]), "code128", 70, 10, "");
        $image = $pdf->jfif_embed($data);
        $pdf->image_place($image, $bottom_barcode, 475, $thispage, array("scale" => 0.5));

        $oikpos = $pdf->strlen(t("Asnro")." ".$asrow["asiakasnro"], $norm);
        $pdf->draw_text(580-$oikpos, $bottom_barcode_text, t("Asnro")." ".$asrow["asiakasnro"], $thispage, $norm);
      }
    }
    if (isset($thispage)) {
      $params['thispage']=$thispage;
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    $return = rivi_otsikot_kerayslista($return);

    return $return;
  }
}

if (!function_exists('uusi_sivu_kerayslista')) {
  function uusi_sivu_kerayslista($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    $thispage = $pdf->new_page("a4");

    tulosta_logo_pdf($pdf, $thispage, $laskurow);

    $pdf->draw_text(550, 815, $laskurow["hyvaksynnanmuutos"],       $thispage, $iso);

    if ($tyyppi == "SIIRTOLISTA") {
      $pdf->draw_text(310, 815, t("Siirtolista", $kieli),         $thispage, $iso);
    }
    elseif ($tyyppi == "REKLAMAATIO") {
      $pdf->draw_text(310, 815, t("Reklamaatio/Purkulista", $kieli),     $thispage, $iso);
    }
    elseif ($tyyppi == "TAKUU") {
      $pdf->draw_text(310, 815, t("Takuu", $kieli),     $thispage, $iso);
    }
    elseif ($tyyppi == "MYYNTITILI") {
      $pdf->draw_text(310, 815, t("Myyntitili", $kieli),         $thispage, $iso);
    }
    else {
      $pdf->draw_text(310, 815, t("Keräyslista", $kieli),         $thispage, $iso);
    }

    $pdf->draw_text(310, 803, t("Sivu", $kieli),             $thispage, $norm);

    //Oikea sarake
    $pdf->draw_rectangle(800, 300, 779, 580, $thispage, $rectparam);

    if ($yhtiorow['kerayserat'] == 'K' and isset($kerayseran_numero) and $kerayseran_numero > 0) {
      $pdf->draw_text(310, 792, t("Keräyserä", $kieli),       $thispage, $pieni);
      $pdf->draw_text(310, 782, $kerayseran_numero,        $thispage, $boldi);
    }
    else {
      list($ff_string, $ff_font) = pdf_fontfit($tilausnumeroita, 270, $pdf, $boldi);
      $pdf->draw_text(310, 792, t("Tilausnumero(t)", $kieli),     $thispage, $pieni);
      $pdf->draw_text(310, 782, $ff_string, $thispage, $ff_font);
    }

    $pdf->draw_rectangle(779, 300, 758, 580, $thispage, $rectparam);
    $pdf->draw_rectangle(779, 420, 758, 580, $thispage, $rectparam);
    $pdf->draw_text(310, 771, t("Keräysaika", $kieli),           $thispage, $pieni);

    if ($laskurow["keraysvko"] != '') {
      $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

      $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["kerayspvm"]));

      if ($laskurow['keraysvko'] != '7') {
        $taika .= "/".$DAY_ARRAY[$laskurow["keraysvko"]];
      }

      $pdf->draw_text(310, 761, $taika,                 $thispage, $boldi);
    }
    else {
      $pdf->draw_text(310, 761, tv1dateconv($laskurow["kerayspvm"]),   $thispage, $boldi);
    }

    $pdf->draw_text(430, 771, t("Toimitusaika", $kieli),         $thispage, $pieni);

    if ($laskurow["toimvko"] != '') {
      $DAY_ARRAY = array(1 => t("Ma", $kieli), t("Ti", $kieli), t("Ke", $kieli), t("To", $kieli), t("Pe", $kieli), t("La", $kieli), t("Su", $kieli));

      $taika = t("Vko", $kieli)." ".date("W", strtotime($laskurow["toimaika"]));

      if ($laskurow['toimvko'] != '7') {
        $taika .= "/" .$DAY_ARRAY[$laskurow["toimvko"]];
      }

      $pdf->draw_text(430, 761, $taika,                 $thispage, $boldi);
    }
    else {
      $pdf->draw_text(430, 761, tv1dateconv($laskurow["toimaika"]),   $thispage, $boldi);
    }

    // Viivakoodi
    if ($yhtiorow['kerayserat'] == 'K' and isset($kerayseran_numero) and $kerayseran_numero > 0) {
      $barcode = strlen($kerayseran_numero) < 6 ? str_pad((int) $kerayseran_numero, 6, "0", STR_PAD_LEFT) : $kerayseran_numero;
    }
    else {
      $barcode  = $laskurow["tunnus"];
    }

    $data = viivakoodi(trim($barcode), "code128", 150, 30, "");
    $image = $pdf->jfif_embed($data);
    $pdf->image_place($image, 805, 440, $thispage, array("scale" => 0.3));

    $kala = 715;

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    $return = rivi_otsikot_kerayslista($return);

    return $return;
  }
}

if (!function_exists('rivi_otsikot_kerayslista')) {
  function rivi_otsikot_kerayslista($params) {
    global $kukarow, $yhtiorow;

    // Luodaan muuttujat
    extract($params);

    if ($kerayslistatyyppi == 'EXCEL1') {
      $thispage->writeString($kala, 0, "Code", $boldi);
      $thispage->writeString($kala, 1, "Description", $boldi);
      $thispage->writeString($kala, 2, "EAN code", $boldi);
      $thispage->writeString($kala, 3, "Pcs", $boldi);
      $thispage->writeString($kala, 4, "Supplier", $boldi);
      $thispage->writeString($kala, 5, "Port of delivery", $boldi);
      $thispage->writeString($kala, 6, "Destination(country)", $boldi);
      $thispage->writeString($kala, 7, "Reference", $boldi);
      $thispage->writeString($kala, 8, "Customer order number", $boldi);

      $kala++;
    }
    elseif ($kerayslistatyyppi == 'EXCEL2' or $kerayslistatyyppi == 'EXCEL3') {
      $thispage->writeString($kala, 0, "Items", $boldi);
      $thispage->writeString($kala, 1, "Code", $boldi);
      $thispage->writeString($kala, 2, "Description", $boldi);
      $thispage->writeString($kala, 3, "EAN code", $boldi);
      $thispage->writeString($kala, 4, "Pcs", $boldi);

      if ($kerayslistatyyppi == 'EXCEL3') {
        $thispage->writeString($kala, 5, "Price", $boldi);
        $thispage->writeString($kala, 6, "Row price", $boldi);
      }

      $kala++;
    }
    else {
      $pdf->draw_rectangle($kala+30, 20, 20, 580, $thispage, $rectparam);

      $pdf->draw_rectangle($kala+30, 20,  $kala+10, 40,  $thispage,   $rectparam);
      $pdf->draw_rectangle($kala+30, 40,  $kala+10, 120, $thispage,   $rectparam);
      $pdf->draw_rectangle($kala+30, 120, $kala+10, 320, $thispage,   $rectparam);
      $pdf->draw_rectangle($kala+30, 320, $kala+10, 420, $thispage,   $rectparam);
      $pdf->draw_rectangle($kala+30, 420, $kala+10, 470, $thispage,   $rectparam);
      $pdf->draw_rectangle($kala+30, 470, $kala+10, 520, $thispage,   $rectparam);
      $pdf->draw_rectangle($kala+30, 520, $kala+10, 580, $thispage,   $rectparam);

      $pdf->draw_text(25,  $kala+15, "#",                  $thispage, $norm);
      $pdf->draw_text(45,  $kala+15, t("Paikka", $kieli),          $thispage, $norm);
      $pdf->draw_text(125, $kala+15, t("Tuotenumero/Tuotenimi", $kieli),  $thispage, $norm);
      $pdf->draw_text(330, $kala+15, t("Toimittajan tuoteno", $kieli),  $thispage, $norm);
      $pdf->draw_text(425, $kala+15, t("Hyllyssä", $kieli),        $thispage, $norm);
      $pdf->draw_text(473, $kala+15, t("Tilattu", $kieli),        $thispage, $norm);
      $pdf->draw_text(530, $kala+15, t("Toimitus", $kieli),        $thispage, $norm);

      $kala -= 3;
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('rivi_kerayslista')) {
  function rivi_kerayslista($params) {
    global $kukarow, $yhtiorow, $asrow, $onkolaajattoimipaikat;

    if (skippa_kerayslista_pdf($params)) {
      return $params;
    }

    // Luodaan muuttujat
    extract($params);

    if ($kerayslistatyyppi == 'EXCEL1') {
      $thispage->writeString($kala, 0, $row["tuoteno"]);
      $thispage->writeString($kala, 1, $row["nimitys"]);
      $thispage->writeString($kala, 2, $row["eankoodi"]);
      $thispage->writeString($kala, 7, $laskurow["viesti"]);
      $thispage->writeString($kala, 8, $laskurow["asiakkaan_tilausnumero"]);

      if ($row["var"] == "" or  $row["var"] == "H") {
        $thispage->write($kala, 3, $row["tilkpl"]);
      }

      $thispage->writeString($kala, 4, "");
      $thispage->writeString($kala, 5, "");
      $thispage->writeString($kala, 6, "");

      $kala++;
    }
    elseif ($kerayslistatyyppi == 'EXCEL2' or $kerayslistatyyppi == 'EXCEL3') {
      $thispage->writeString($kala, 0, "");
      $thispage->writeString($kala, 1, $row["tuoteno"]);
      $thispage->writeString($kala, 2, $row["nimitys"]);
      $thispage->writeString($kala, 3, $row["eankoodi"]);

      if ($row["var"] == "" or  $row["var"] == "H") {
        $thispage->write($kala, 4, $row["tilkpl"]);
      }

      if ($kerayslistatyyppi == 'EXCEL3') {
        if ($row["var"] == "" or  $row["var"] == "H") {
          $thispage->writeString($kala, 5, (float) round($row['kplhinta'], 2));
          $thispage->writeString($kala, 6, (float) round($row['rivihinta'], 2));
        }
      }

      $kala++;
    }
    else {
      // viivat rivien väliin...
      $x[0] = 20;
      $x[1] = 580;
      $y[0] = $y[1] = $kala + $rivinkorkeus - 4;

      if ((($perheid == 0 and $perheid2 == 0) or $perheid != $row["perheid"] or $perheid2 != $row["perheid2"]) and isset($perheid) and isset($perheid2)) {
        $pdf->draw_line($x, $y, $thispage, $rectparam);
      }

      //jos ollaan liian pitkällä tehdään uusi otsikko...
      if ($kala < 95) {

        $sivu++;

        // Luodaan palautettavat
        $return = compact(array_keys($params));

        $params = loppu_kerayslista($return);
        $params = uusi_sivu_kerayslista($params);

        // Luodaan muuttujat
        extract($params);

        $page[$sivu] = $thispage;
      }

      if ($row["var"] == "" or  $row["var"] == "H"
        or ($row["var"] == "P" and ($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q"))
        or ($row["var"] == "J" and ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q"))
        or ($row["var"] == "J" and $yhtiorow["lahetteen_tulostustapa"] == "I")) {

        $varastopaikka = hyllyalue('', $row['hyllyalue'], 'riisuttu') . " " . $row["hyllynro"] . " " . $row["hyllyvali"] . " " . $row["hyllytaso"];

        if (strpos($varastopaikka, "!!M") !== FALSE) {
          $varastopaikka = t("Myyntitili", $kieli);
        }

        $row["perhe_kommentti1"] = "";
        $row["perhe_kommentti2"] = "";

        // Info tuoteperheestä
        if ($row["perheid"] > 0 and $perheid != $row["perheid"]) {
          $numrows = 0;

          $query = "SELECT vanhatunnus
                    FROM lasku
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$row[otunnus]'";
          $vtunres = pupe_query($query);
          $vtunrow = mysql_fetch_assoc($vtunres);

          if ($vtunrow["vanhatunnus"] != 0) {
            $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                      FROM lasku use index (yhtio_vanhatunnus)
                      WHERE yhtio     = '$kukarow[yhtio]'
                      and vanhatunnus = '$vtunrow[vanhatunnus]'";
            $perheresult = pupe_query($query);
            $numrows = mysql_num_rows($perheresult);
          }

          if ($numrows == 0 or $vtunrow["vanhatunnus"] == 0) {
            $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                      FROM lasku use index (PRIMARY)
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$row[otunnus]'";
            $perheresult = pupe_query($query);
          }

          if (mysql_num_rows($perheresult) > 0) {
            $perherow = mysql_fetch_assoc($perheresult);

            $query = "SELECT distinct tilausrivi.tuoteno, tilausrivi.nimitys, varattu
                      FROM tilausrivi
                      JOIN tuote tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
                      WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                      and tilausrivi.otunnus  in ($perherow[tunnukset])
                      AND tilausrivi.tyyppi  != 'D'
                      and tilausrivi.var     != 'O'
                      and tilausrivi.perheid  = '$row[perheid]'
                      ORDER BY tilausrivi.tunnus";
            $perheresult = pupe_query($query);

            if (mysql_num_rows($perheresult) > 1) {
              $perherow = mysql_fetch_assoc($perheresult);

              $row["perhe_kommentti1"] = $perherow["tuoteno"]." (".$row["varattu"].") ".$perherow["nimitys"];

              while ($perherow = mysql_fetch_assoc($perheresult)) {
                $row["perhe_kommentti2"] .= strtoupper($perherow["tuoteno"]).", ";
              }

              $row["perhe_kommentti2"] = substr($row["perhe_kommentti2"], 0, -2);
            }
          }
        }

        if (trim($row["perhe_kommentti1"]) != '') {
          $plen = $pdf->strlen(t("Tuoteperhe", $kieli).": ", $norm);
          $pdf->draw_text(23, $kala, t("Tuoteperhe", $kieli).": ", $thispage, $norm);
          list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti1"], 500, $pdf, $norm);
          $pdf->draw_text($plen+23, $kala, $ff_string, $thispage, $ff_font);
          $kala -= $rivinkorkeus;

          if (trim($row["perhe_kommentti2"]) != '') {
            $plen = $pdf->strlen(t("Sisältää tuotteet", $kieli).": ", $norm);
            $pdf->draw_text(23, $kala, t("Sisältää tuotteet", $kieli).": ", $thispage, $norm);
            list($ff_string, $ff_font) = pdf_fontfit($row["perhe_kommentti2"], 500, $pdf, $norm);
            $pdf->draw_text($plen+23, $kala, $ff_string, $thispage, $ff_font);
            $kala -= $rivinkorkeus;
          }

          //jos ollaan liian pitkällä tehdään uusi otsikko...
          if ($kala < 95) {

            $sivu++;

            // Luodaan palautettavat
            $return = compact(array_keys($params));

            $params = loppu_kerayslista($return);
            $params = uusi_sivu_kerayslista($params);

            // Luodaan muuttujat
            extract($params);

            $page[$sivu] = $thispage;
          }
        }

        if (($yhtiorow['kerayserat'] == 'K' and isset($kerayseran_numero) and $kerayseran_numero > 0) or ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $asrow['kerayserat'] == 'A'))) {
          $pdf->draw_text(23,  $kala, $rivinumerot[$row["ker_tunnus"]],  $thispage, $pieni);
        }
        else {
          $pdf->draw_text(23,  $kala, $rivinumerot[$row["tunnus"]],  $thispage, $pieni);
        }

        list($ff_string, $ff_font) = pdf_fontfit($varastopaikka, 88, $pdf, $norm);
        $pdf->draw_text(42,  $kala, $ff_string,           $thispage, $ff_font);
        $pdf->draw_text(125,  $kala, $row["tuoteno"],        $thispage, $norm);

        $query = "SELECT tuote.*, group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno
                  FROM tuote
                  LEFT JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
                  WHERE tuote.yhtio='$kukarow[yhtio]' and tuote.tuoteno = '$row[tuoteno]'
                  GROUP BY tuote.tuoteno";
        $tresult = pupe_query($query);
        $trow   = mysql_fetch_assoc($tresult);

        // lisätään tuotteen takaata kommenni kerääjälle, tämä tulostuu vain keräyslistaan ja purkulistaan.
        if (trim($trow['kerayskommentti']) !="") {
          $row['kommentti'] .= "\n".$trow['kerayskommentti'];
        }

        // jos rekkulalla on suoraan toimittajalle palautettvia rivejä
        if (isset($row['asiakkaan_positio']) and $row['asiakkaan_positio'] > 0) {

          $query = "SELECT nimi
                    FROM asiakas
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$row['asiakkaan_positio']}'";
          $asiakas_check_res = pupe_query($query);
          $asiakas_check_row   = mysql_fetch_assoc($asiakas_check_res);

          $row['kommentti'] .= "\n".t("Palutetaan toimittajalle").": ".$asiakas_check_row['nimi'];
        }

        //varaston sisäisen siirron kohdetuotepaikka, tsekataan onko tietoja ja onko kyseessä varmasti varaston sisäinen siirto. Jos, laitetaan siirtolistalle kohdetuotepaikasta infoa.
        $query = "SELECT kohde_hyllyalue, kohde_hyllynro, kohde_hyllyvali, kohde_hyllytaso
                  FROM tilausrivin_lisatiedot as tlt2
                  WHERE yhtio          = '$kukarow[yhtio]'
                  and tilausrivitunnus = '$row[tunnus]'";
        $kohderesult = pupe_query($query);
        $kohderow   = mysql_fetch_assoc($kohderesult);
        $varastopaikkakohde = '';

        if (($laskurow['varasto'] == $laskurow['clearing'] or $yhtiorow['varastosiirto_kohdepaikka'] == "K") and $kohderow['kohde_hyllyalue'] != '' and $kohderow['kohde_hyllynro'] != '' and $kohderow['kohde_hyllyvali'] != '' and $kohderow['kohde_hyllytaso'] != '') {
          $varastopaikkakohde = $kohderow["kohde_hyllyalue"] . " " . $kohderow["kohde_hyllynro"] . " " . $kohderow["kohde_hyllyvali"] . " " . $kohderow["kohde_hyllytaso"];
          $row['kommentti'] .= "\n".t("Vie paikalle", $kieli)." ".$varastopaikkakohde;
        }

        $pdf->draw_text(330, $kala, pdf_substr($trow["toim_tuoteno"], 80, $pdf, $norm), $thispage, $norm);

        $rivipaikkahyllyssa  = 0;
        $rivivarastohyllyssa = 0;

        if ($trow["ei_saldoa"] == '') {
          //katotaan mihin varastooon tilausrivillä tuotepaikka kuuluu
          $rivipaikka = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);

          if ($onkolaajattoimipaikat and $laskurow['yhtio_toimipaikka'] != 0) {

            $query  = "SELECT GROUP_CONCAT(tunnus) AS tunnukset
                       FROM varastopaikat
                       WHERE yhtio      = '{$kukarow['yhtio']}'
                       AND tyyppi      != 'P'
                       AND toimipaikka  = '{$laskurow['yhtio_toimipaikka']}'";
            $vares = pupe_query($query);
            $varow = mysql_fetch_assoc($vares);

            if (!empty($varow['tunnukset'])) {
              $rivipaikka = $varow['tunnukset'];
            }
          }

          //saldot per varastopaikka
          if ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") {
            $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                      tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                      sarjanumeroseuranta.sarjanumero era,
                      concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
                      varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
                       FROM tuote
                      JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                      JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                        AND varastopaikat.tunnus                = tuotepaikat.varasto
                        AND varastopaikat.tunnus                IN ({$rivipaikka}))
                      JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
                      and sarjanumeroseuranta.tuoteno           = tuote.tuoteno
                      and sarjanumeroseuranta.hyllyalue         = tuotepaikat.hyllyalue
                      and sarjanumeroseuranta.hyllynro          = tuotepaikat.hyllynro
                      and sarjanumeroseuranta.hyllyvali         = tuotepaikat.hyllyvali
                      and sarjanumeroseuranta.hyllytaso         = tuotepaikat.hyllytaso
                      and sarjanumeroseuranta.myyntirivitunnus  = 0
                      and sarjanumeroseuranta.era_kpl          != 0
                      WHERE tuote.yhtio                         = '$kukarow[yhtio]'
                      and tuote.tuoteno                         = '$row[tuoteno]'
                      GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
                      ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
          }
          else {
            $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                      tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                      '' era,
                      concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
                      varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
                       FROM tuote
                      JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                      JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                        AND varastopaikat.tunnus = tuotepaikat.varasto
                        AND varastopaikat.tunnus IN ({$rivipaikka}))
                      WHERE tuote.yhtio          = '$kukarow[yhtio]'
                      and tuote.tuoteno          = '$row[tuoteno]'
                      ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
          }
          $sresult = pupe_query($query);

          if (mysql_num_rows($sresult) > 0) {
            while ($saldorow = mysql_fetch_assoc($sresult)) {
              if (!empty($yhtiorow["saldo_kasittely"])) {
                $saldoaikalisa = date("Y-m-d");
              }
              else {
                $saldoaikalisa = "";
              }

              list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($saldorow["tuoteno"], '', '', '', $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], '', $saldoaikalisa, $saldorow["era"]);

              if ($saldorow['hyllyalue'] == $row['hyllyalue'] and $saldorow['hyllynro'] == $row['hyllynro'] and $saldorow['hyllyvali'] == $row['hyllyvali'] and $saldorow['hyllytaso'] == $row['hyllytaso']) {
                $rivipaikkahyllyssa  += $hyllyssa;
              }

              $rivivarastohyllyssa += $hyllyssa;
            }
          }

          if ($rivipaikkahyllyssa != $rivivarastohyllyssa) {
            $oikpos = $pdf->strlen($rivipaikkahyllyssa." (".$rivivarastohyllyssa.")", $norm);
            $pdf->draw_text(465-$oikpos, $kala, $rivipaikkahyllyssa." (".$rivivarastohyllyssa.")",  $thispage, $norm);
          }
          else {
            $oikpos = $pdf->strlen($rivipaikkahyllyssa, $norm);
            $pdf->draw_text(465-$oikpos, $kala, $rivipaikkahyllyssa,  $thispage, $norm);
          }

          if ($tyyppi == "SIIRTOLISTA" or $tyyppi == "MYYNTITILI") {
            list(, $kohde_hyllyssa, ) = saldo_myytavissa($trow["tuoteno"], '', $laskurow["clearing"]);
          }
        }

        $oikpos = $pdf->strlen(($row["tilkpl"]*1), $boldi);
        $pdf->draw_text(504-$oikpos, $kala, ($row["tilkpl"]*1),   $thispage, $boldi);

        $pdf->draw_text(505, $kala, t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite"),   $thispage, $pieni);

        if ($row["keratty"] != '' and !in_array($row["var"], array('J', 'P'))) {
          $oikpos = $pdf->strlen($row['varattu']+$row['kpl'], $norm);
          $pdf->draw_text(575-$oikpos, $kala, $row['varattu']+$row['kpl'],   $thispage, $norm);
        }
        else {
          if ($row["var"] == 'J') { // jälkitoimitus
            $oikpos = $pdf->strlen(t("**JT**", $kieli), $norm);
            $pdf->draw_text(575-$oikpos, $kala, t("**JT**", $kieli),   $thispage, $norm);
          }
          elseif ($row["var"] == 'P') { // puute
            $oikpos = $pdf->strlen(t("PUUTE", $kieli), $norm);
            $pdf->draw_text(575-$oikpos, $kala, t("PUUTE", $kieli),   $thispage, $norm);
          }
          elseif ($row["var"] == 'H') { // väkisin hyväksytty
            $oikpos = $pdf->strlen("...........", $norm);
            $pdf->draw_text(575-$oikpos, $kala, "...........",   $thispage, $norm);
          }
        }

        $kala = $kala - $rivinkorkeus + 5;

        if ($yhtiorow['kerayslista_kerayspaikka'] == 'K') {
          $query = "SELECT *
                    FROM tuotepaikat
                    WHERE yhtio      = '{$kukarow['yhtio']}'
                    AND tuoteno      = '{$row['tuoteno']}'
                    AND varasto      = '{$row['varasto']}'
                    AND hyllypaikka != CONCAT('{$row['hyllyalue']}', '{$row['hyllynro']}', '{$row['hyllyvali']}', '{$row['hyllytaso']}')
                    ORDER BY saldo DESC";
          $paikkaresult = pupe_query($query);
          $paikkarow = mysql_fetch_assoc($paikkaresult);

          $varastopaikka_vara = hyllyalue('', $paikkarow['hyllyalue'], 'riisuttu') . " " . $paikkarow["hyllynro"] . " " . $paikkarow["hyllyvali"] . " " . $paikkarow["hyllytaso"];
          list($ff_string, $ff_font) = pdf_fontfit($varastopaikka_vara, 88, $pdf, $norm);
          $pdf->draw_text(42,  $kala, $ff_string, $thispage, $ff_font);
        }

        list($ff_string, $ff_font) = pdf_fontfit($row["nimitys"], 350, $pdf, $norm);
        $pdf->draw_text(125, $kala, $ff_string, $thispage, $ff_font);

        if ($tyyppi == "SIIRTOLISTA" or $tyyppi == "MYYNTITILI") {
          $pdf->draw_text(465-$oikpos, $kala, $kohde_hyllyssa,   $thispage, $norm);
        }

        if ($trow["tuotemassa"] > 0) {
          $ypaino = (float) round(($row['varattu']+$row['kpl'])*$trow["tuotemassa"], 2);

          $oikpos = $pdf->strlen((float) $trow["tuotemassa"]." / ".$ypaino." kg", $pieni);
          $pdf->draw_text(520-$oikpos, $kala, (float) $trow["tuotemassa"]." / ".$ypaino." kg",  $thispage, $pieni);

          $paino += $ypaino;
        }

        $kala = $kala - $rivinkorkeus;

        // Tulostataan myyntiera
        if ($trow['myynti_era'] > 1) {
          $oikpos = $pdf->strlen(round(($row['varattu']+$row['kpl'])/$trow['myynti_era'], 2)." me", $norm);
          $pdf->draw_text(525-$oikpos, $kala+7, round(($row['varattu']+$row['kpl'])/$trow['myynti_era'], 2)." me",  $thispage, $pieni);
        }

        if ($yhtiorow["siirtolistatyyppi"] == "A") {

          if ($trow['myynti_era'] > 1) {
            $kala = $kala - $rivinkorkeus;
          }
          else {
            $kala = $kala-7;
          }

          $oikpos = $pdf->strlen((float) $trow["myyntihinta"]." ".$yhtiorow["valkoodi"], $pieni);
          $pdf->draw_text(520-$oikpos, $kala+15, (float) $trow["myyntihinta"]." ".$yhtiorow["valkoodi"],  $thispage, $pieni);

          if ($trow['myynti_era'] > 1) {
            $kala = $kala + $rivinkorkeus;
          }
        }

        //Haetan sarjanumeron tiedot
        if ($row["sarjanumeroseuranta"] != "") {
          if ($tyyppi == "SIIRTOLISTA" or $tyyppi == "MYYNTITILI") {
            $sarjanutunnus = "siirtorivitunnus";
          }
          elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["varattu"] + $row["kpl"] < 0 and $row["var2"] == "EIOST") {
            $sarjanutunnus = "ostorivitunnus";
          }
          elseif ($row["uusiotunnus"] > 0 and $row["osto_vai_hyvitys"] == '' and $row["varattu"] + $row["kpl"] < 0 and ($row['sarjanumeroseuranta'] == 'S' or $row['sarjanumeroseuranta'] == 'G')) {
            $sarjanutunnus = "myyntirivitunnus";
          }
          elseif ($row["varattu"]+$row["kpl"] < 0) {
            $sarjanutunnus = "ostorivitunnus";
          }
          else {
            $sarjanutunnus = "myyntirivitunnus";
          }

          $query = "SELECT DISTINCT sarjanumero, parasta_ennen
                    FROM sarjanumeroseuranta
                    WHERE yhtio      = '$kukarow[yhtio]'
                    and tuoteno      = '$row[tuoteno]'
                    and $sarjanutunnus='$row[tunnus]'
                    and sarjanumero != ''
                    ORDER BY sarjanumero";
          $sarjares = pupe_query($query);

          if (mysql_num_rows($sarjares) > 0) {
            if ($row["kommentti"] != '') {
              $row["kommentti"] .= "\n";
            }

            if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
              $row["kommentti"] .= t("E:nro", $kieli).": ";
            }
            else {
              $row["kommentti"] .= t("S:nro", $kieli).": ";
            }

            while ($sarjarow = mysql_fetch_assoc($sarjares)) {
              if ($row["sarjanumeroseuranta"] == "F") {
                $row["kommentti"] .= $sarjarow["sarjanumero"]." ".t("Parasta ennen", $kieli).": ".tv1dateconv($sarjarow["parasta_ennen"]).", ";
              }
              else {
                $row["kommentti"] .= $sarjarow["sarjanumero"].", ";
              }
            }

            $row["kommentti"] = substr($row["kommentti"], 0, -2);
          }
        }

        if ($yhtiorow['kerayserat'] == 'K' and isset($kerayseran_numero) and $kerayseran_numero > 0) {
          if ($row["kommentti"] != '') {
            $row["kommentti"] .= "\n";
          }

          $row['kommentti'] .= t("Tilausnumero", $kieli).": {$row['otunnus']}";

          //etsitään myyjän nimi
          $query  = "SELECT kuka.nimi
                     FROM lasku
                     JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.tunnus = lasku.myyja AND kuka.yhtio = lasku.yhtio)
                     WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                     AND lasku.tunnus  = '{$row['otunnus']}'";
          $myyresult = pupe_query($query);
          $myyrow = mysql_fetch_assoc($myyresult);

          $row['kommentti'] .= "\n".t("Myyjä", $kieli).": {$myyrow['nimi']}";
        }

        // Trimmataan ja otetaan "yhteensäkommentti" pois
        $row["kommentti"] = trim(poista_rivin_yhteensakommentti($row["kommentti"]));

        if ((
            ($yhtiorow['kerayserat'] == 'K' and isset($kerayseran_numero) and $kerayseran_numero > 0)
            or ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $asrow['kerayserat'] == 'A'))
          )
          and !empty($row['pakkausnro'])) {
          $pakkaus_kirjain = chr((64+$row['pakkausnro']));

          $pdf->draw_text(42, $kala+5, $pakkaus_kirjain, $thispage, $iso_boldi);

          $kala -= 10;

          $query = "SELECT CONCAT(pakkaus, ' ', pakkauskuvaus) pakkaus
                    FROM pakkaus
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  = '{$row['pakkaus']}'";
          $pak_nimi_res = pupe_query($query);
          $pak_nimi_row = mysql_fetch_assoc($pak_nimi_res);

          $pdf->draw_text(42, $kala, trim($pak_nimi_row['pakkaus']), $thispage, $norm);

          if ($row['kommentti'] == '') {
            $kala -= 20;
          }
        }

        if ($row["kommentti"] != '') {
          if ($kala-$norm["height"]-5 < 95) {

            $sivu++;

            // Luodaan palautettavat
            $return = compact(array_keys($params));

            $params = loppu_kerayslista($return);
            $params = uusi_sivu_kerayslista($params);

            // Luodaan muuttujat
            extract($params);

            $page[$sivu] = $thispage;
          }

          $pohja = $pdf->draw_paragraph($kala+$norm["height"]+5, 125, 60, 470, $row["kommentti"],  $thispage, $norm);
          $kala = $pohja - $rivinkorkeus;
        }
        elseif ($trow['myynti_era'] > 1) {
          $kala = $kala - $rivinkorkeus;
        }

        $perheid  = $row["perheid"];
        $perheid2 = $row["perheid2"];
      }
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('loppu_kerayslista')) {
  function loppu_kerayslista($params) {
    global $kukarow, $yhtiorow;

    if (skippa_kerayslista_pdf($params)) {
      return $params;
    }

    // Luodaan muuttujat
    extract($params);

    if ($kerayslistatyyppi == 'EXCEL1' or $kerayslistatyyppi == 'EXCEL2' or $kerayslistatyyppi == 'EXCEL3') {
      // Tässä pärjätään ilman footteria....
    }
    else {
      // yhtiötiedot
      $y_tiedot_ok = FALSE;
      $y_nimi = $laskurow["yhtio_nimi"];
      $y_osoite = $laskurow["yhtio_osoite"];
      $y_postino = $laskurow["yhtio_postino"];
      $y_postitp = $laskurow["yhtio_postitp"];
      $y_maa = $laskurow["yhtio_maa"];
      $y_kotipaikka = $laskurow["yhtio_kotipaikka"];
      $y_vatnumero = preg_replace("/^0037/", "", $laskurow["yhtio_ovttunnus"]);
      $y_iban = $yhtiorow['pankkiiban1'];
      $y_swift = $yhtiorow['pankkiswift1'];

      if ((int) $laskurow["yhtio_toimipaikka"] != 0) {
        // haetaan toimipaikan tiedot
        $alhqur = "SELECT
                   puhelin,
                   fax,
                   email,
                   www,
                   vat_numero
                   FROM yhtion_toimipaikat
                   WHERE yhtio='$laskurow[yhtio]'
                   AND tunnus='$laskurow[yhtio_toimipaikka]'";
        $alhire = pupe_query($alhqur);

        if (mysql_num_rows($alhire) == 1) {
          $alhiro = mysql_fetch_assoc($alhire);

          $y_tiedot_ok  = TRUE;
          $y_puhelin    = $alhiro['puhelin'];
          $y_fax      = $alhiro['fax'];
          $y_email    = $alhiro['email'];
          $y_www      = $alhiro['www'];

          if (!empty($alhiro['vat_numero'])) {
            $y_vatnumero = $alhiro['vat_numero'];
          }
        }
      }

      // jos meillä on lasku menossa ulkomaille ja se laskutetaan eri ovttunnuksella
      if ($laskurow["yhtio_ovttunnus"] != "" and $laskurow["yhtio_ovttunnus"] != $yhtiorow["ovttunnus"]) {
        // haetaan toimipaikan tiedot
        $alhqur = "SELECT puhelin, fax, email, www from yhtion_toimipaikat where yhtio='$laskurow[yhtio]' and vat_numero='$laskurow[yhtio_ovttunnus]'";
        $alhire = pupe_query($alhqur);

        if (mysql_num_rows($alhire) == 1) {
          $alhiro = mysql_fetch_assoc($alhire);

          $y_tiedot_ok  = TRUE;
          $y_puhelin    = $alhiro['puhelin'];
          $y_fax      = $alhiro['fax'];
          $y_email    = $alhiro['email'];
          $y_www      = $alhiro['www'];
        }
      }

      // jos ei olla haettu muualta, niin haetaan tiedot yhtiöltä
      if (!$y_tiedot_ok) {
        // haetaan toimipaikan tiedot
        $alhqur = "SELECT puhelin, fax, email, www from yhtio where yhtio='$laskurow[yhtio]'";
        $alhire = pupe_query($alhqur);

        if (mysql_num_rows($alhire) == 1) {
          $alhiro = mysql_fetch_assoc($alhire);

          $y_puhelin    = $alhiro['puhelin'];
          $y_fax      = $alhiro['fax'];
          $y_email    = $alhiro['email'];
          $y_www      = $alhiro['www'];
        }
      }

      // Alimmat kolme laatikkoa, yhtiötietoja
      $pdf->draw_rectangle(70, 20, 20, 580,  $thispage, $rectparam);
      $pdf->draw_rectangle(70, 207, 20, 580,  $thispage, $rectparam);
      $pdf->draw_rectangle(70, 394, 20, 580,  $thispage, $rectparam);

      $pdf->draw_text(30, 55, $y_nimi,    $thispage, $pieni);
      $pdf->draw_text(30, 45, $y_osoite,    $thispage, $pieni);
      $pdf->draw_text(30, 35, $y_postino."  ".$y_postitp,  $thispage, $pieni);
      $pdf->draw_text(30, 25, $y_maa,      $thispage, $pieni);

      $pdf->draw_text(217, 55, t("Puhelin", $kieli).":",      $thispage, $pieni);
      $pdf->draw_text(257, 55, $y_puhelin,            $thispage, $pieni);
      $pdf->draw_text(217, 45, t("Fax", $kieli).":",      $thispage, $pieni);
      $pdf->draw_text(257, 45, $y_fax,              $thispage, $pieni);
      $pdf->draw_text(217, 35, t("Sähköposti", $kieli).":",    $thispage, $pieni);
      $pdf->draw_text(257, 35, $y_email,              $thispage, $pieni);
      $pdf->draw_text(217, 25, t("Web", $kieli).":",        $thispage, $pieni);
      $pdf->draw_text(257, 25, $y_www,              $thispage, $pieni);

      $pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",      $thispage, $pieni);
      $pdf->draw_text(444, 55, tulosta_ytunnus($y_vatnumero, $y_maa, $laskurow["vienti"]), $thispage, $pieni);
      $pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",    $thispage, $pieni);
      $pdf->draw_text(444, 45, $y_kotipaikka,    $thispage, $pieni);

      if ($tots != 1) {
        $pdf->draw_text(530, 72, t("Jatkuu", $kieli)."...",   $thispage, $norm);
      }

      if ($tots == 1) {
        if ($paino != 0) {
          $pdf->draw_text(440, 25, t("Tilauksen paino", $kieli).":",  $thispage, $norm);
          $oikpos = $pdf->strlen($paino." kg", $boldi);
          $pdf->draw_text(575-$oikpos, 25, $paino." kg",        $thispage, $boldi);
        }

        // viivat rivien väliin...
        $x[0] = 20;
        $x[1] = 580;
        $y[0] = $y[1] = $kala + $rivinkorkeus - 4;

        $pdf->draw_line($x, $y, $thispage, $rectparam);

        for ($pp=1; $pp<=$sivu; $pp++) {
          $pp_print = $pp;
          $sivu_print = $sivu;

          # Jos tulostetetaan monta keräyslistaa samaan PDF-dokkariin
          if (!empty($tama_lista_sivu)) {
            $pp_print = $pp-$tama_lista_sivu+1;
            $sivu_print = $sivu-$tama_lista_sivu+1;
          }

          $pdf->draw_text(330, 803, "$pp_print / $sivu_print", $page[$pp], $norm);
        }
      }
    }

    // Luodaan palautettavat
    $return = compact(array_keys($params));

    return $return;
  }
}

if (!function_exists('print_pdf_kerayslista')) {
  function print_pdf_kerayslista($params) {
    global $kukarow, $yhtiorow, $palvelin2;

    // Luodaan muuttujat
    extract($params);

    $returnvalue = 0;

    if (isset($komento) and $komento == "-88") {
      js_openFormInNewWindow();

      echo "<br><form id='tulostakopioform_{$laskurow['tunnus']}' name='tulostakopioform_{$laskurow['tunnus']}' method='post' action='{$palvelin2}tilauskasittely/tulostakopio.php' autocomplete='off'>
            <input type='hidden' name='otunnus' value='{$laskurow['tunnus']}'>
            <input type='hidden' name='toim' value='KERAYSLISTA'>
            <input type='hidden' name='tee' value='NAYTATILAUS'>
            <input type='submit' value='".t("Keräyslista").": {$laskurow['tunnus']}' onClick=\"js_openFormInNewWindow('tulostakopioform_{$laskurow['tunnus']}', ''); return false;\"></form><br>";
    }
    elseif ($kerayslistatyyppi == 'EXCEL1' or $kerayslistatyyppi == 'EXCEL2' or $kerayslistatyyppi == 'EXCEL3') {
      $excelnimi = $thispage->close();

      $liite['excel'] = "/tmp/".$excelnimi;
      $ctype['excel']= "excel";

      if ($tyyppi == "SIIRTOLISTA") {
        $kutsu = t("Siirtolista", $kieli);
        echo t("Siirtolista tulostuu")."...<br>";
      }
      elseif ($tyyppi == "REKLAMAATIO") {
        $kutsu = t("Reklamaatio/Purkulista", $kieli);
        echo t("Reklamaatio/Purkulista tulostuu")."...<br>";
      }
      elseif ($tyyppi == "TAKUU") {
        $kutsu = t("Takuu", $kieli);
        echo t("Takuu tulostuu")."...<br>";
      }
      elseif ($tyyppi == "MYYNTITILI") {
        $kutsu = t("Myyntitili", $kieli);
        echo t("Myyntitili tulostuu")."...<br>";
      }
      elseif (isset($uusiotsikko)) {
        $kutsu = t($uusiotsikko, $kieli).": $laskurow[tunnus]";
        echo t("{$uusiotsikko} toimitetaan sähköpostiosoitteeseen")."...{$kukarow['eposti']}<br>";
      }
      else {
        $kutsu = t("Keräyslista", $kieli).": $laskurow[tunnus]";
        echo t("Keräyslista tulostuu")."...<br>";
      }

      if ($kukarow["extranet"] == '') {
        require "inc/sahkoposti.inc";
      }
      else {
        require "sahkoposti.inc";
      }
    }
    else {
      //keksitään uudelle failille joku varmasti uniikki nimi:
      list($usec, $sec) = explode(' ', microtime());
      mt_srand((float) $sec + ((float) $usec * 100000));
      $pdffilenimi = "/tmp/Kerayslista-".md5(uniqid(mt_rand(), true)).".pdf";

      //kirjoitetaan pdf faili levylle..
      $fh = fopen($pdffilenimi, "w");
      if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $pdffilenimi");
      fclose($fh);

      // itse print komento...
      if ($komento == 'email') {
        $liite = $pdffilenimi;

        if ($tyyppi == "SIIRTOLISTA") {
          $kutsu = t("Siirtolista", $kieli);
          echo t("Siirtolista tulostuu")."...<br>";
        }
        elseif ($tyyppi == "REKLAMAATIO") {
          $kutsu = t("Reklamaatio/Purkulista", $kieli);
          echo t("Reklamaatio/Purkulista tulostuu")."...<br>";
        }
        elseif ($tyyppi == "TAKUU") {
          $kutsu = t("Takuu", $kieli);
          echo t("Takuu tulostuu")."...<br>";
        }
        elseif ($tyyppi == "MYYNTITILI") {
          $kutsu = t("Myyntitili", $kieli);
          echo t("Myyntitili tulostuu")."...<br>";
        }
        else {
          $kutsu = t("Keräyslista", $kieli);
          echo t("Keräyslista tulostuu")."...<br>";
        }

        if ($kukarow["extranet"] == '') {
          require "inc/sahkoposti.inc";
        }
        else {
          require "sahkoposti.inc";
        }
      }
      elseif ($tee == 'NAYTATILAUS') {
        //Työnnetään tuo pdf vaan putkeen!
        echo file_get_contents($pdffilenimi);
      }
      elseif ($komento != '' and $komento != 'edi') {
        echo t("Keräyslista tulostuu")."...<br>";
        $line = exec("$komento $pdffilenimi", $output, $returnvalue);
      }
    }
  }
}

if (!function_exists('skippa_kerayslista_pdf')) {
  function skippa_kerayslista_pdf($params) {

    extract($params);

    if (isset($komento) and $komento == "-88") {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}
