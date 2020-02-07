<?php

if ((strpos($file, "maventa") !== false and $trow['asn_sanomat'] == "M" and $ebid == 'MAVENTAXML') or (strpos($file, "finvoice") !== false and $trow['asn_sanomat'] == 'F' and $ebid == 'FINVOICEXML')) {

  $edellinen_tuoteno         = "";
  $runkotuotteita         = 0;
  $runkotuotteiden_indeksit     = array();
  $lapsiarray            = array();
  $budweg_runkotuotteiden_alut  = array('901', '902', '903', '904');

  // Budweg rahti
  if (($trow['toimittajanro'] == "123099" or $trow['toimittajanro'] == "123014") and $laskun_summa_eur > $rivien_summa_yhteensa) {
    $osto_rahti = $laskun_summa_eur - $rivien_summa_yhteensa;
    $osto_rahti_alv = $xvati;
  }

  // Tähän väliin työnnetään runko
  $growindex = 1;

  foreach ($rtuoteno as $i => $_arr) {

    // Örum -> Masi
    // Otetaan rahtituoterivit pois tuotearraysta
    // Automaattinen jälkilaskenta huomaa ostolaskussa ja saapumsiessa eron ja päivittää eron pyöristyseroihin
    if ($kukarow['yhtio'] == 'atarv' and $trow['toimittajanro'] == '3' and ($_arr['tuoteno'] == 'A-100' or $_arr['tuoteno'] == 'A-200')) {
      $rivikohtaiset_rahtikulut += $_arr["rivihinta"];
      unset($rtuoteno[$i]);
      continue;
    }

    $ttoim_row = array();

    $query = "SELECT *
              FROM tuotteen_toimittajat
              JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno AND tuote.status != 'P')
              WHERE tuotteen_toimittajat.yhtio      = '{$kukarow['yhtio']}'
              AND tuotteen_toimittajat.liitostunnus = '{$trow['tunnus']}'
              AND tuotteen_toimittajat.toim_tuoteno = '{$_arr['tuoteno']}'";
    $chk_res = pupe_query($query);

    if (mysql_num_rows($chk_res) == 0) {
      // haetaan vaihtoehtoisten tuotenumeroiden (tuotteen_toimittajat_tuotenumerot) kautta tuotteen_toimittajat.toim_tuoteno. Osataan myös hakea vaihtoehtoinen tuotenumero ilman että
      $chk_res = tuotteen_toimittajat_tuotenumerot_haku($_arr['tuoteno'], $laskuttajan_toimittajanumero);

      if (mysql_num_rows($chk_res) > 0) {
        $ttoim_row = mysql_fetch_assoc($chk_res);
        $rtuoteno[$i+$growindex-1]['tuoteno'] = $_arr['tuoteno'] = $ttoim_row['toim_tuoteno'];
      }
    }
    else {
      $ttoim_row = mysql_fetch_assoc($chk_res);
    }

    // Onko tuotekerroin, ($i+$growindex-1) on tän tietueen nykyinen indeksi. Indeksit muuttuu ku lapset splaissataan mukaan loopataavaan arrayseen.
    if (isset($ttoim_row['tuotekerroin']) and $ttoim_row['tuotekerroin'] != 0) {
      $rtuoteno[$i+$growindex-1]['kpl'] = $_arr['kpl'] = round($_arr['kpl'] / $ttoim_row['tuotekerroin'], 2);
    }

    // Yksikköhinta kuntoon
    $rtuoteno[$i+$growindex-1]["hinta"] = round($_arr["rivihinta"] / $_arr["kpl"], 2);

    // Telko-case
    // 300013 = rahtiveloitus
    // 300023 = pakkauskulu
    // 300027 = pakkauskulu
    // Holger Christiansen rahti

    if (($trow['toimittajanro'] == "123335" or str_replace(array("-", " "), "", $trow['toimittajanro']) == "09871371")
      and ($_arr['tuoteno'] == "300013" or $_arr['tuoteno'] == "300023" or $_arr['tuoteno'] == "300027")) {

      if ($_arr['tuoteno'] == "300013") {
        $osto_rahti    += $_arr["rivihinta_verolli"];
        $osto_rahti_alv  = $_arr["alv"];
      }
      else {
        $osto_kulu    += $_arr["rivihinta_verolli"];
        $osto_kulu_alv   = $_arr["alv"];
      }

      unset($rtuoteno[$i+$growindex-1]);
      continue;
    }
    elseif (($trow['toimittajanro'] == "123029" or str_replace(array("-", " "), "", $trow['toimittajanro']) == "DK61048154" or str_replace(array("-", " "), "", $trow['toimittajanro']) == "DK88021614") and $_arr['tuoteno'] == "Freight") {
      $laskun_rahtikulu += $_arr["rivihinta_verolli"];
      $osto_rivi_kulu   += $_arr["rivihinta_verolli"];
      $osto_rahti_alv     = $_arr["alv"];

      unset($rtuoteno[$i+$growindex-1]);
      continue;
    }
    elseif ($trow['toimittajanro'] == "123099" and $_arr['tuoteno'] == "Fragt/freight/fracht") {
      $osto_rahti    += $_arr["rivihinta_verolli"];
      $osto_rahti_alv  = $_arr["alv"];

      unset($rtuoteno[$i+$growindex-1]);
      continue;
    }

    // Budweg-case
    // FTE-case
    if ((($trow['toimittajanro'] == "123099" and substr($edellinen_tuoteno, -3) == substr($_arr['tuoteno'], -3) and in_array(substr($_arr['tuoteno'], 0, 3), $budweg_runkotuotteiden_alut))
        or
        ($trow['toimittajanro'] == "123014" and trim(strtolower($_arr["tuoteno"])) == "corecharge")) and $trow['asn_sanomat'] == 'M' and $edellinen_tuoteno != "") {

      // Katsotaan löytyykö runkotuote (tuoteperheen lapsituote)
      $query = "SELECT tt.toim_tuoteno AS tuoteno, tuote.nimitys
                FROM tuotteen_toimittajat
                JOIN tuoteperhe ON (tuoteperhe.yhtio = tuotteen_toimittajat.yhtio
                  AND tuoteperhe.isatuoteno            = tuotteen_toimittajat.tuoteno
                  AND tuoteperhe.tyyppi                IN ('P','')
                  AND tuoteperhe.ohita_kerays         != '')
                JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio AND tuote.tuoteno = tuoteperhe.tuoteno AND tuote.status != 'P')
                JOIN tuotteen_toimittajat AS tt ON (tt.yhtio = tuotteen_toimittajat.yhtio
                  AND tt.liitostunnus                  = '{$trow['tunnus']}'
                  AND tt.tuoteno                       = tuote.tuoteno
                  AND tt.toim_tuoteno                 != '')
                WHERE tuotteen_toimittajat.yhtio       = '{$kukarow['yhtio']}'
                AND tuotteen_toimittajat.liitostunnus  = '{$trow['tunnus']}'
                AND tuotteen_toimittajat.toim_tuoteno  = '{$edellinen_tuoteno}'
                AND tuotteen_toimittajat.toim_tuoteno != ''";
      $child_chk_res = pupe_query($query);

      if (mysql_num_rows($child_chk_res) > 0) {
        $child_chk_row = mysql_fetch_assoc($child_chk_res);

        $rtuoteno[$i+$growindex-1]["tuoteno"] = $child_chk_row['tuoteno'];
        $rtuoteno[$i+$growindex-1]["lapsi"]   = "yes";
      }
    }

    // Holger Christiansen-case
    if ($trow['toimittajanro'] == "123029" and $trow['asn_sanomat'] == 'M') {

      // Katsotaan löytyykö runkotuote (tuoteperheen lapsituote)
      $query = "SELECT tt.toim_tuoteno AS tuoteno, tuote.nimitys
                FROM tuotteen_toimittajat
                JOIN tuoteperhe ON (tuoteperhe.yhtio = tuotteen_toimittajat.yhtio
                  AND tuoteperhe.isatuoteno            = tuotteen_toimittajat.tuoteno
                  AND tuoteperhe.tyyppi                IN ('P','')
                  AND tuoteperhe.ohita_kerays         != '')
                JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio AND tuote.tuoteno = tuoteperhe.tuoteno AND tuote.status != 'P')
                JOIN tuotteen_toimittajat AS tt ON (tt.yhtio = tuotteen_toimittajat.yhtio
                  AND tt.liitostunnus                  = '{$trow['tunnus']}'
                  AND tt.tuoteno                       = tuote.tuoteno
                  AND tt.toim_tuoteno                 != '')
                WHERE tuotteen_toimittajat.yhtio       = '{$kukarow['yhtio']}'
                AND tuotteen_toimittajat.liitostunnus  = '{$trow['tunnus']}'
                AND tuotteen_toimittajat.toim_tuoteno  = '{$_arr['tuoteno']}'
                AND tuotteen_toimittajat.toim_tuoteno != ''";
      $child_chk_res = pupe_query($query);

      if (mysql_num_rows($child_chk_res) > 0) {
        $child_chk_row = mysql_fetch_assoc($child_chk_res);

        $runkotuotteita += (float) $_arr['kpl'];

        $lapsiarray[0]['tuoteno']       = $child_chk_row['tuoteno'];
        $lapsiarray[0]['ostotilausnro']   = $_arr['ostotilausnro'];
        $lapsiarray[0]['nimitys']      = $child_chk_row['nimitys'];
        $lapsiarray[0]['rivihinta']     = 0;
        $lapsiarray[0]['rivihinta_verolli'] = 0;
        $lapsiarray[0]['alv']         = 0; // Oletetaan aina ALV 0%
        $lapsiarray[0]['ale']         = 0;
        $lapsiarray[0]['hinta']       = 0;
        $lapsiarray[0]['kommentti']     = '';
        $lapsiarray[0]['kpl']         = (float) $_arr['kpl'];
        $lapsiarray[0]['yksikko']      = '';
        $lapsiarray[0]['tilaajanrivinro']   = "";
        $lapsiarray[0]['lapsi']       = "";

        $ealvi[$i+$growindex] = 0; // Oletetaan aina ALV 0%
        $runkotuotteiden_indeksit[] = $i+$growindex;
      }
    }

    $edellinen_tuoteno = $_arr["tuoteno"];

    // laitetaan runko satuloidan kaa samaan arrayseen sillai, että runko tulee heti satulan jälkeen
    if (count($lapsiarray) > 0) {
      array_splice($rtuoteno, $i+$growindex, 0, $lapsiarray);
      $lapsiarray = array();
      $growindex++;
    }
  }

  if ($trow['toimittajanro'] == "123029") {
    if ($laskun_summa_eur - $rivien_summa_yhteensa != 0 and count($runkotuotteiden_indeksit) > 0 and $runkotuotteita > 0) {
      $rungon_hinta = ($laskun_summa_eur - $rivien_summa_yhteensa) / $runkotuotteita;

      foreach ($runkotuotteiden_indeksit as $indx) {
        $rtuoteno[$indx]['hinta']         = round($rungon_hinta, 2);
        $rtuoteno[$indx]['rivihinta']       = round($rtuoteno[$indx]['kpl'] * $rungon_hinta, 2);
        $rtuoteno[$indx]['rivihinta_verolli']  = round($rtuoteno[$indx]['kpl'] * $rungon_hinta, 2);
      }
    }
    elseif ($laskun_summa_eur > $rivien_summa_yhteensa) {
      $osto_rahti = $laskun_summa_eur - $rivien_summa_yhteensa;
      $osto_rahti_alv = $xvati;
    }
  }
}
