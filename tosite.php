<?php

if (!isset($link)) require "inc/parametrit.inc";

if (isset($_POST['ajax_toiminto']) and trim($_POST['ajax_toiminto']) != '') {
  require "inc/tilioinnin_toiminnot.inc";
}

enable_ajax();

if (isset($livesearch_tee) and $livesearch_tee == "TILIHAKU") {
  livesearch_tilihaku();
  exit;
}

// Talletetaan käyttäjän nimellä tositteen/liitteen kuva, jos sellainen tuli
// koska, jos tulee virheitä tiedosto katoaa. Kun kaikki on ok, annetaan sille oikea nimi
if ($tee == 'I' and isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name'])) {
  $retval = tarkasta_liite("userfile", array("PNG", "JPG", "GIF", "PDF", "XLS"));

  if ($retval === true) {
    $kuva = tallenna_liite("userfile", "lasku", 0, "");
  }
  else {
    echo $retval;
    $tee = "";
  }
}

if (!isset($tiliointirivit)) $tiliointirivit = array();
if (!isset($iliitos)) $iliitos = array();
if (!isset($ed_iliitos)) $ed_iliitos = array();
if (!isset($ed_iliitostunnus)) $ed_iliitostunnus = array();

if (isset($tullaan) and $tullaan == 'muutosite') {
  $iliitos = unserialize(urldecode($iliitos));
  $ed_iliitos = $iliitos;
  $ed_iliitostunnus = unserialize(urldecode($ed_iliitostunnus));
  $tiliointirivit = unserialize(urldecode($tiliointirivit));
}

if (isset($tiliointirivit) and !is_array($tiliointirivit)) $tiliointirivit = unserialize(urldecode($tiliointirivit));

if (isset($muutparametrit)) {
  list($tee, $kuitti, $kuva, $maara, $tpp, $tpk, $tpv, $summa, $valkoodi, $alv_tili, $nimi, $comments, $selite, $liitos, $liitostunnus, $tunnus, $tiliointirivit, $MAX_FILE_SIZE, $itili, $ikustp, $ikohde, $isumma, $ivero, $iselite, $iliitos, $ed_iliitostunnus, $ed_iliitos) = explode("#!#", $muutparametrit);

  $itili        = unserialize(urldecode($itili));
  $ikustp        = unserialize(urldecode($ikustp));
  $ikohde        = unserialize(urldecode($ikohde));
  $isumma        = unserialize(urldecode($isumma));
  $ivero        = unserialize(urldecode($ivero));
  $iselite      = unserialize(urldecode($iselite));
  $iliitos       = unserialize(urldecode($iliitos));
  $ed_iliitostunnus   = unserialize(urldecode($ed_iliitostunnus));
  $ed_iliitos     = unserialize(urldecode($ed_iliitos));
  $tiliointirivit   = unserialize(urldecode($tiliointirivit));
}

if ($toimittajaid > 0) {
  for ($i = 1; $i <= count($iliitos); $i++) {
    if ($iliitos[$i] == 'T' and $iliitostunnus[$i] == $toimittajaid) {
      $ed_iliitostunnus[$i] = $toimittajaid;
      $ed_iliitos[$i] = 'T';
    }
    elseif ($iliitos[$i] == 'T' and isset($iliitostunnus) and !isset($iliitostunnus[$i]) and $ed_iliitostunnus[$i] == $toimittajaid) {
      $ed_iliitostunnus[$i] = '';
      $ed_iliitos[$i] = '';
    }
  }
}
elseif ($asiakasid > 0) {
  for ($i = 1; $i <= count($iliitos); $i++) {
    if ($iliitos[$i] == 'A' and $iliitostunnus[$i] == $asiakasid) {
      $ed_iliitostunnus[$i] = $asiakasid;
      $ed_iliitos[$i] = 'A';
    }
    elseif ($iliitos[$i] == 'A' and isset($iliitostunnus) and !isset($iliitostunnus[$i]) and $ed_iliitostunnus[$i] == $asiakasid) {
      $ed_iliitostunnus[$i] = '';
      $ed_iliitos[$i] = '';
    }
  }
}

$muutparametrit = $tee."#!#".$kuitti."#!#".$kuva."#!#".$maara."#!#".$tpp."#!#".$tpk."#!#".$tpv."#!#".$summa."#!#".$valkoodi."#!#".$alv_tili."#!#".$nimi."#!#".$comments."#!#".$selite."#!#".$liitos."#!#".$liitostunnus."#!#".$tunnus."#!#".urlencode(serialize($tiliointirivit))."#!#".$MAX_FILE_SIZE."#!#".urlencode(serialize($itili))."#!#".urlencode(serialize($ikustp))."#!#".urlencode(serialize($ikohde))."#!#".urlencode(serialize($isumma))."#!#".urlencode(serialize($ivero))."#!#".urlencode(serialize($iselite))."#!#".urlencode(serialize($iliitos))."#!#".urlencode(serialize($ed_iliitostunnus))."#!#".urlencode(serialize($ed_iliitos));

echo "<script type=\"text/javascript\" charset=\"utf-8\">

  $(document).ready(function(){
    $('#liitosbox_checkall').click(function() {

      if ($(this).is(':checked')) {
        $('.liitosbox').attr('checked', 'checked');
      }
      else {
        $('.liitosbox').attr('checked', '');
      }
    });
  });
  </script>";


echo "<font class='head'>".t("Uusi muu tosite")."</font><hr>\n";

$kurssi = 1;

// Jos syotetään nimi niin ei liitetä asiakasta eikä toimittajaa
if ($nimi != "") {
  $toimittajaid   = 0;
  $asiakasid     = 0;
  $toimittaja_y  = "";
  $asiakas_y    = "";
}

if ($toimittaja_y != '') {
  $ytunnus = $toimittaja_y;
  $toimittajaid = 0;
  $asiakasid = 0;

  require "inc/kevyt_toimittajahaku.inc";

  if ($toimittajaid > 0) {
    unset($teetosite);
  }

  if ($monta == 0) {
    unset($teetosite);
  }
  elseif ($toimittajaid == 0) {
    require "inc/footer.inc";
    exit;
  }
}

if ($asiakas_y != '') {
  $ytunnus = $asiakas_y;
  $asiakasid = 0;
  $toimittajaid = 0;

  require "inc/asiakashaku.inc";

  if ($asiakasid > 0) {
    unset($teetosite);
  }

  if ($monta == 0) {
    unset($teetosite);
  }
  elseif ($asiakasid == 0) {
    require "inc/footer.inc";
    exit;
  }
}

if ($toimittajaid > 0) {

  $query = "SELECT * FROM toimi WHERE tunnus = '{$toimittajaid}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo t("Toimittajaa")." {$ytunnus} ".t("ei löytynytkään")."!";
    exit;
  }

  $toimrow = mysql_fetch_assoc($result);
}

if ($asiakasid > 0) {

  $query = "SELECT * FROM asiakas WHERE tunnus = '{$asiakasid}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo t("Asiakasta")." {$ytunnus} ".t("ei löytynytkään")."!";
    exit;
  }

  $asrow = mysql_fetch_assoc($result);
}

// Tarkistetetaan syötteet perustusta varten
if ($tee == 'I') {
  $totsumma = 0;
  $summa = str_replace(",", ".", $summa);
  $gok  = 0;
  $tpk += 0;
  $tpp += 0;
  $tpv += 0;

  if (isset($gokfrom) and ($gokfrom == "palkkatosite" or $gokfrom == "avaavatase")) {
    $gok = 1;
  }

  $tapvmvirhe = "";

  if (!checkdate($tpk, $tpp, $tpv)) {
    $tapvmvirhe = "<font class='error'>".t("Virheellinen tapahtumapvm")."</font>";
    $gok = 1;
  }

  if ($valkoodi != $yhtiorow["valkoodi"] and $gok == 0) {

    // koitetaan hakea maksupäivän kurssi
    $query = "SELECT *
              FROM valuu_historia
              WHERE kotivaluutta = '{$yhtiorow['valkoodi']}'
              AND valuutta       = '{$valkoodi}'
              AND kurssipvm      <= '{$tpv}-{$tpk}-{$tpp}'
              ORDER BY kurssipvm DESC
              LIMIT 1";
    $valuures = pupe_query($query);

    if (mysql_num_rows($valuures) == 1) {
      $valuurow = mysql_fetch_assoc($valuures);
      $kurssi = $valuurow["kurssi"];
    }
    else {
      echo "<font class='error'>".t("Ei löydetty sopivaa kurssia!")."</font><br>\n";
      $gok = 1;
    }
  }

  if (is_uploaded_file($_FILES['tositefile']['tmp_name'])) {
    //  ei koskaan päivitetä automaattisesti
    $tee = "";

    $retval = tarkasta_liite("tositefile", array("XLSX", "XLS", "ODS", "SLK", "XML", "GNUMERIC", "CSV", "TXT"));

    if ($retval === true) {

      /**
       * PHPExcel kirjasto *
       */


      if (@include "PHPExcel/Classes/PHPExcel/IOFactory.php");
      elseif (@include "PHPExcel/PHPExcel/IOFactory.php");

      /**
       * Tunnistetaan tiedostomuoto *
       */
      $inputFileType = PHPExcel_IOFactory::identify($_FILES['tositefile']['tmp_name']);

      /**
       * Luodaan readeri *
       */
      $objReader = PHPExcel_IOFactory::createReader($inputFileType);

      /**
       * Ladataan vain solujen datat (ei formatointeja jne) *
       */
      if ($inputFileType != "CSV") {
        $objReader->setReadDataOnly(true);
      }

      if ($inputFileType == "CSV") {
        $objReader->setDelimiter("\t");
        $objReader->setInputEncoding("ISO-8859-1");
      }

      /**
       * Ladataan file halutuilla parametreilla *
       */
      $objPHPExcel = $objReader->load($_FILES['tositefile']['tmp_name']);

      /**
       * Laitetaan solut arrayseen *
       */
      $excelrivi = array();

      /**
       * Aktivoidaan eka sheetti*
       */
      $objPHPExcel->setActiveSheetIndex(0);

      /**
       * Loopataan rivit/sarakkeet *
       */
      foreach ($objPHPExcel->getActiveSheet()->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $rowIndex = ($row->getRowIndex())-1;

        foreach ($cellIterator as $cell) {
          $colIndex = (PHPExcel_Cell::columnIndexFromString($cell->getColumn()))-1;

          $excelrivi[$rowIndex][$colIndex] = utf8_decode($cell->getCalculatedValue());
        }
      }

      // Otetaan tiedoston otsikkorivi
      $otsikot = array_shift($excelrivi);

      $maara = 1;

      foreach ($excelrivi as $erivi) {
        foreach ($erivi as $e => $eriv) {

          if (strtolower($otsikot[$e]) == "kustp" or strtolower($otsikot[$e]) == "kohde" or strtolower($otsikot[$e]) == "projekti") {
            // Kustannuspaikka, kohde, projekti
            $kukopr = strtolower($otsikot[$e]);

            if ($kukopr == "kustp") {
              $ikukopr_tyyppi = "K";
            }
            elseif ($kukopr == "kohde") {
              $ikukopr_tyyppi = "O";
            }
            elseif ($kukopr == "projekti") {
              $ikukopr_tyyppi = "P";
            }

            ${"i".$kukopr}[$maara] = 0;

            $ikukopr_tsk = trim($eriv);

            if ($ikukopr_tsk != "") {
              $query = "SELECT tunnus
                        FROM kustannuspaikka
                        WHERE yhtio   = '{$kukarow['yhtio']}'
                        and tyyppi    = '$ikukopr_tyyppi'
                        and kaytossa != 'E'
                        and nimi      = '{$ikukopr_tsk}'";
              $ikustpres = pupe_query($query);

              if (mysql_num_rows($ikustpres) == 1) {
                $ikustprow = mysql_fetch_assoc($ikustpres);
                ${"i".$kukopr}[$maara] = $ikustprow["tunnus"];
              }
            }

            if ($ikukopr_tsk != "" and ${"i".$kukopr}[$maara] == 0) {
              $query = "SELECT tunnus
                        FROM kustannuspaikka
                        WHERE yhtio   = '{$kukarow['yhtio']}'
                        and tyyppi    = '$ikukopr_tyyppi'
                        and kaytossa != 'E'
                        and koodi     = '{$ikukopr_tsk}'";
              $ikustpres = pupe_query($query);

              if (mysql_num_rows($ikustpres) == 1) {
                $ikustprow = mysql_fetch_assoc($ikustpres);
                ${"i".$kukopr}[$maara] = $ikustprow["tunnus"];
              }
            }

            if (is_numeric($ikukopr_tsk) and (int) $ikukopr_tsk > 0 and ${"i".$kukopr}[$maara] == 0) {

              $ikukopr_tsk = (int) $ikukopr_tsk;

              $query = "SELECT tunnus
                        FROM kustannuspaikka
                        WHERE yhtio   = '{$kukarow['yhtio']}'
                        and tyyppi    = '$ikukopr_tyyppi'
                        and kaytossa != 'E'
                        and tunnus    = '{$ikukopr_tsk}'";
              $ikustpres = pupe_query($query);

              if (mysql_num_rows($ikustpres) == 1) {
                $ikustprow = mysql_fetch_assoc($ikustpres);
                ${"i".$kukopr}[$maara] = $ikustprow["tunnus"];
              }
            }
          }
          elseif (strtolower($otsikot[$e]) == "summa") {
            ${"i".strtolower($otsikot[$e])}[$maara] = sprintf("%.2f", round($eriv, 2));
          }
          else {
            ${"i".strtolower($otsikot[$e])}[$maara] = $eriv;
          }

        }

        $maara++;
      }

      //  Lisätään vielä 2 tyhjää riviä loppuun
      $maara += 2;
      $gokfrom = "filesisaan";
    }
    else {

      //  Liitetiedosto ei kelpaa
      echo $retval;
      $tee = "";
    }
  }
  elseif (isset($_FILES['tositefile']['error']) and $_FILES['tositefile']['error'] != 4) {
    // nelonen tarkoittaa, ettei mitään fileä uploadattu.. eli jos on joku muu errori niin ei päästetä eteenpäin
    echo "<font class='error'>".t("Tositetiedoston sisäänluku epäonnistui")."! (Error: ".$_FILES['userfile']['error'].")</font><br>\n";
    $tee = "";
  }

  // turvasumma kotivaluutassa
  $turvasumma = sprintf("%.2f", round($summa * $kurssi, 2));
  // turvasumma valuutassa
  $turvasumma_valuutassa = $summa;

  $kuittiok = 0; // Onko joku vienneistä kassa-tili, jotta kuitti voidaan tulostaa
  $isumma_valuutassa = array();

  foreach ($ed_iliitostunnus as $liit_indx => $liit) {
    $iliitostunnus[$liit_indx] = $liit;
    $iliitos[$liit_indx] = $ed_iliitos[$liit_indx];
  }

  for ($i=1; $i<$maara; $i++) {

    // Käsitelläänkö rivi??
    if (strlen($itili[$i]) > 0 or strlen($isumma[$i]) > 0) {

      $isumma[$i] = str_replace(",", ".", $isumma[$i]);

      // Oletussummalla korvaaminen mahdollista
      if ($turvasumma_valuutassa > 0) {
        // Summan vastaluku käyttöön
        if (substr($isumma[$i], -1) == "%") {

          $isummanumeric = preg_replace("/[^0-9\.]/", "", $isumma[$i]);

          if ($isumma[$i]{0} == '-') {
            $isumma[$i] = sprintf("%.2f", round(-1 * ($turvasumma_valuutassa * ($isummanumeric/100)), 2));
          }
          else {
            $isumma[$i] = sprintf("%.2f", round(1 * ($turvasumma_valuutassa * ($isummanumeric/100)), 2));
          }
        }
        elseif ($isumma[$i] == '-') {
          $isumma[$i] = -1 * $turvasumma_valuutassa;
        }
        elseif ($isumma[$i] == '+') {
          $isumma[$i] = 1 * $turvasumma_valuutassa;
        }
        // Kopioidaan summa
        elseif (strlen($itili[$i]) > 0 and $isumma[$i] == 0) {
          $isumma[$i] = $turvasumma_valuutassa;
        }
      }

      // otetaan valuuttasumma talteen
      $isumma_valuutassa[$i] = $isumma[$i];
      // käännetään kotivaluuttaan
      $isumma[$i] = sprintf("%.2f", round($isumma[$i] * $kurssi, 2));

      if (strlen($selite) > 0 and strlen($iselite[$i]) == 0) { // Siirretään oletusselite tiliöinneille
        $iselite[$i] = $selite;
      }

      if (strlen($iselite[$i]) == 0 and strlen($comments) == 0) { // Selite ja kommentti puuttuu
        $ivirhe[$i] = t('Riviltä puuttuu selite').'<br>';
        $gok = 1;
      }

      if ($isumma[$i] == 0) { // Summa puuttuu
        $ivirhe[$i] .= t('Riviltä puuttuu summa').'<br>';
        $gok = 1;
      }

      $ulos       = "";
      $virhe       = "";
      $tili       = $itili[$i];
      $summa       = $isumma[$i];
      $totsumma       += $summa;
      $selausnimi   = "itili['.$i.']"; // Minka niminen mahdollinen popup on?
      $vero       = "";
      $tositetila   = "X";
      $kustp_tark    = $ikustp[$i];
      $kohde_tark    = $ikohde[$i];
      $projekti_tark  = $iprojekti[$i];
      $liitos      = mysql_real_escape_string($iliitos[$i]);
      $liitostunnus  = mysql_real_escape_string($iliitostunnus[$i]);

      if (isset($toimittajaid) and $toimittajaid > 0) {
        $tositeliit = $toimrow["tunnus"];
      }
      elseif (isset($asiakasid) and $asiakasid > 0) {
        $tositeliit = $asrow['tunnus'];
      }
      else {
        $tositeliit = 0;
      }

      require "inc/tarkistatiliointi.inc";

      if ($vero!='') $ivero[$i]=$vero; //Jos meillä on hardkoodattuvero, otetaan se käyttöön

      if (isset($ivirhe[$i])) {
        $ivirhe[$i] .= $virhe;
      }

      if (!isset($ivirhe[$i]) and strlen($virhe) > 0) {
        $ivirhe[$i] = $virhe;
      }

      $iulos[$i] = $ulos;

      if ($ok == 0) { // Sieltä kenties tuli päivitys tilinumeroon
        if ($itili[$i] != $tili) { // Annetaan käyttäjän päättää onko ok
          $itili[$i] = $tili;
          $gok = 1; // Tositetta ei kirjoiteta kantaan vielä
        }
        else {
          if ($itili[$i] == $yhtiorow['kassa']) $kassaok = 1;
        }
      }
      else {
        $gok = $ok; // Nostetaan virhe ylemmälle tasolle
      }
    }
  }

  if (count($isumma_valuutassa) == 0) {
    $gok = 1;
  }

  $kuittivirhe = "";

  if ($kuitti != '') {
    if ($kassaok == 0) {
      $gok = 1;
      $kuittivirhe = "<font class='error'>".t("Pyysit kuittia, mutta kassatilille ei ole vientejä")."</font><br>\n";
    }

    if ($nimi == '' and $toimrow["nimi"] == '' and $asrow['nimi'] == '') {
      $gok = 1;
      $kuittivirhe .= "<font class='error'>".t("Kuitille on annettava nimi tai asiakas tai toimittaja")."</font><br>\n";
    }
  }

  $heittovirhe = 0;

  if (abs($totsumma) >= 0.01 and $heittook == '') {
    $heittovirhe = 1;
    $gok = 1;
  }

  // jos loppusumma on isompi kuin tietokannassa oleva tietuen koko (10 numeroa + 2 desimaalia), niin herjataan
  if ($summa != '' and abs($summa) > 0) {
    if (abs($summa) > 9999999999.99) {
      echo "<font class='error'>".t("VIRHE: liian iso summa")."!</font><br/>\n";
      $gok=1;
    }
  }

  // Jossain tapahtui virhe
  if ($gok == 1) {
    if ($gokfrom == "") {
      echo "<br><font class='error'>".t("HUOM").": ".t("Jossain oli virheitä/muutoksia")."!</font><br>\n";
    }

    $tee = '';
  }

  $summa = $turvasumma;
}

// Kirjoitetaan tosite jos tiedot ok!
if ($tee == 'I' and isset($teetosite)) {

  if (trim($nimi) != '') {
    $qlisa = " nimi = '{$nimi}',";
  }

  $paivitetaanko = false;

  if (isset($tunnus) and trim($tunnus) > 0) {

    $tunnus = (int) $tunnus;

    $query = "UPDATE lasku SET
              tapvm       = '{$tpv}-{$tpk}-{$tpp}',
              {$qlisa}
              alv_tili    = '{$alv_tili}',
              comments    = '{$comments}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$tunnus}'";
    $result = pupe_query($query);
    $paivitetaanko = true;
  }
  else {

    $tositealatila = "";

    if (isset($avaavatase) and $avaavatase == 'joo') {
      $tositealatila = "A";
    }

    $query = "INSERT into lasku set
              yhtio      = '{$kukarow['yhtio']}',
              tapvm      = '{$tpv}-{$tpk}-{$tpp}',
              {$qlisa}
              tila       = 'X',
              alatila    = '$tositealatila',
              alv_tili   = '{$alv_tili}',
              comments   = '{$comments}',
              laatija    = '{$kukarow['kuka']}',
              luontiaika = now()";
    $result = pupe_query($query);
    $tunnus = mysql_insert_id($GLOBALS["masterlink"]);
  }

  if (isset($avaavatase) and $avaavatase == 'joo') {
    $query = "UPDATE tilikaudet SET
              avaava_tase = '{$tunnus}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$tilikausi}'";
    $avaavatase_result = pupe_query($query);

    // Kirjataan avaava_tase uudestaan. Yliviivataan vanhat ja tehdään uudet
    if ($paivitetaanko) {
      $query = "UPDATE tiliointi SET
                korjattu          = '{$kukarow['kuka']}',
                    korjausaika   = now()
                    WHERE ltunnus = '{$tunnus}'
                    AND yhtio     = '{$kukarow['yhtio']}'
                    AND korjattu  = ''";
      $ylikirjaus_result = pupe_query($query);

      // Yliviivatiin kaikki, eli tehdään uudet ei päivitetä
      $paivitetaanko = FALSE;
    }
  }

  if ($kuva) {
    // päivitetään kuvalle vielä linkki toiseensuuntaa
    $query = "UPDATE liitetiedostot
              set liitostunnus = '{$tunnus}', selite = '{$selite} {$summa}'
              where tunnus = '{$kuva}'";
    $result = pupe_query($query);
  }

  foreach ($ed_iliitostunnus as $liit_indx => $liit) {
    $iliitostunnus[$liit_indx] = $liit;
    $iliitos[$liit_indx] = $ed_iliitos[$liit_indx];
  }

  // Tehdään tiliöinnit
  for ($i=1; $i<$maara; $i++) {
    if (strlen($itili[$i]) > 0) {

      $tili        = trim($itili[$i]);
      $kustp        = $ikustp[$i];
      $kohde        = $ikohde[$i];
      $projekti      = $iprojekti[$i];
      $summa        = $isumma[$i];
      $vero        = $ivero[$i];
      $selite       = $iselite[$i];
      $summa_valuutassa  = $isumma_valuutassa[$i];
      $valkoodi       = $valkoodi;
      $liitos        = mysql_real_escape_string($iliitos[$i]);
      $liitostunnus    = mysql_real_escape_string($iliitostunnus[$i]);

      require "inc/teetiliointi.inc";

      $itili[$i]        = '';
      $ikustp[$i]        = '';
      $ikohde[$i]        = '';
      $iprojekti[$i]      = '';
      $isumma[$i]        = '';
      $ivero[$i]        = '';
      $iselite[$i]      = '';
      $isumma_valuutassa[$i]  = '';
      $iliitos[$i]      = '';
      $iliitostunnus[$i]    = '';
    }
  }

  if ($kuitti != '') {
    require "inc/kuitti.inc";
  }

  $alv_tili       = "";
  $asiakas_y       = "";
  $comments       = "";
  $ed_iliitos     = "";
  $ed_iliitostunnus   = "";
  $ikohde       = "";
  $ikustp       = "";
  $iliitos       = "";
  $iprojekti       = "";
  $iselite       = "";
  $isumma       = "";
  $itili         = "";
  $ivero         = "";
  $maara         = "";
  $nimi         = "";
  $selite       = "";
  $summa         = "";
  $tee         = "";
  $teetosite       = "";
  $tiliointirivit   = "";
  $toimittaja_y     = "";
  $tositesum       = "";
  $tpk         = "";
  $tpp         = "";
  $tpv         = "";
  $valkoodi       = "";
  $avaavatase     = "";
  $gokfrom       = "";
  $tilikausi       = "";
  $asiakasid      = "";
  $toimittajaid    = "";

  if ($lopetus != '' and $tullaan == "muutosite") {
    lopetus($lopetus, "META");
    exit;
  }
  else {
    echo "<font class='message'>".t("Tosite luotu")."!</font>\n";
    echo "  <form action = 'muutosite.php' method='post'>
        <input type='hidden' name='tee' value='E'>
        <input type='hidden' name='lopetus' value='{$lopetus}'>
        <input type='hidden' name='tunnus' value='{$tunnus}'>
        <input type='submit' value='".t("Näytä tosite")."'>
        </form><br><hr><br>";
  }

  $tullaan = "";
  $tunnus = "";
}
else {
  $tee = "";
}

if ($tee == '') {
  if ($maara=='') $maara = '3'; //näytetään defaulttina kaks

  //päivämäärän tarkistus
  $tilalk = explode("-", $yhtiorow["tilikausi_alku"]);
  $tillop = explode("-", $yhtiorow["tilikausi_loppu"]);

  $tilalkpp = $tilalk[2];
  $tilalkkk = $tilalk[1]-1;
  $tilalkvv = $tilalk[0];

  $tilloppp = $tillop[2];
  $tillopkk = $tillop[1]-1;
  $tillopvv = $tillop[0];

  echo "  <script language='javascript'>
        function tositesumma() {
          var summa = 0;

          for (var i=0; i<document.tosite.elements.length; i++) {
                 if (document.tosite.elements[i].type == 'text' && document.tosite.elements[i].name.substring(0,6) == 'isumma') {

              if (document.tosite.elements[i].value == '+') {
                summa+=1.0*document.tosite.summa.value.replace(',','.');
              }
              else if (document.tosite.elements[i].value == '-') {
                summa-=1.0*document.tosite.summa.value.replace(',','.');
              }
              else {
                summa+=1.0*document.tosite.elements[i].value.replace(',','.');
              }
            }
            }

          document.tosite.tositesum.value=Math.round(summa*100)/100;
        }
      </script> ";

  echo "  <script language='javascript'>
        function selitejs() {

          var selitetxt = document.tosite.selite.value;

          for (var i=0; i<document.tosite.elements.length; i++) {
                 if (document.tosite.elements[i].type == 'text' && document.tosite.elements[i].name.substring(0,7) == 'iselite') {
              document.tosite.elements[i].value=selitetxt;
            }
            }
        }
      </script> ";

  echo "  <SCRIPT LANGUAGE=JAVASCRIPT>

      function verify(){
        var pp = document.tosite.tpp;
        var kk = document.tosite.tpk;
        var vv = document.tosite.tpv;

        pp = Number(pp.value);
        kk = Number(kk.value)-1;
        vv = Number(vv.value);

        if (vv < 1000) {
          vv = vv+2000;
        }

        var dateSyotetty = new Date(vv,kk,pp);
        var dateTallaHet = new Date();
        var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

        var tilalkpp = {$tilalkpp};
        var tilalkkk = {$tilalkkk};
        var tilalkvv = {$tilalkvv};
        var dateTiliAlku = new Date(tilalkvv,tilalkkk,tilalkpp);
        dateTiliAlku = dateTiliAlku.getTime();


        var tilloppp = {$tilloppp};
        var tillopkk = {$tillopkk};
        var tillopvv = {$tillopvv};
        var dateTiliLoppu = new Date(tillopvv,tillopkk,tilloppp);
        dateTiliLoppu = dateTiliLoppu.getTime();

        dateSyotetty = dateSyotetty.getTime();

        if(dateSyotetty < dateTiliAlku || dateSyotetty > dateTiliLoppu) {
          var msg = '".t("VIRHE: Syötetty päivämäärä ei sisälly kuluvaan tilikauteen")."!';
          alert(msg);

          skippaa_tama_submitti = true;
          return false;
        }

        if(ero >= 30) {
          var msg = '".t("Oletko varma, että haluat päivätä laskun yli 30pv menneisyyteen")."?';

          if (confirm(msg)) {
            return true;
          }
          else {
            skippaa_tama_submitti = true;
            return false;
          }
        }
        if(ero <= -14) {
          var msg = '".t("Oletko varma, että haluat päivätä laskun yli 14pv tulevaisuuteen")."?';

          if (confirm(msg)) {
            return true;
          }
          else {
            skippaa_tama_submitti = true;
            return false;
          }
        }

        if (vv < dateTallaHet.getFullYear()) {
          if (5 < dateTallaHet.getDate()) {
            var msg = '".t("Oletko varma, että haluat päivätä laskun menneisyyteen")."?';

            if (confirm(msg)) {
              return true;
            }
            else {
              skippaa_tama_submitti = true;
              return false;
            }
          }
        }
        else if (vv == dateTallaHet.getFullYear()) {
          if (kk < dateTallaHet.getMonth() && 5 < dateTallaHet.getDate()) {
            var msg = '".t("Oletko varma, että haluat päivätä laskun menneisyyteen")."?';

            if (confirm(msg)) {
              return true;
            }
            else {
              skippaa_tama_submitti = true;
              return false;
            }
          }
        }
      }
    </SCRIPT>";

  $formi = 'tosite';
  $kentta = 'tpp';

  if ((isset($gokfrom) and $gokfrom == 'avaavatase') or (isset($avaavatase) and $avaavatase == 'joo')) {
    echo "<br><font class='error'>".t("Tilikauden tulos kirjattu")."!<br><br>".t("HUOM: Avaavaa tasetta ei ole vielä kirjattu")."!<br>".t("HUOM: Tarkista tämä tosite ja tee kirjaukset klikkamalla *Tee tosite*-nappia")."!</font><br>";
  }

  echo "<br>\n";
  echo "<font class='head'>".t("Tositteen otsikkotiedot").":</font>\n";

  echo "<form name='tosite' action='tosite.php' method='post' enctype='multipart/form-data' onSubmit = 'return verify()' autocomplete='off'>\n";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>\n";
  echo "<input type='hidden' name='tee' value='I'>\n";

  echo "<input type='hidden' name='tiliointirivit' value='", urlencode(serialize($tiliointirivit)), "' />";
  echo "<input type='hidden' name='tunnus' value='{$tunnus}' />";

  if (isset($tullaan) and $tullaan == 'muutosite' and ((!isset($toimittajaid) and !isset($asiakasid)) or ($toimittajaid == 0 and $asiakasid == 0))) {
    echo "<input type='hidden' name='ed_iliitostunnus' value='", urlencode(serialize($ed_iliitostunnus)), "' />";
    echo "<input type='hidden' name='ed_iliitos' value='", urlencode(serialize($ed_iliitos)), "' />";
    echo "<input type='hidden' name='iliitos' value='", urlencode(serialize($iliitos)), "' />";
    echo "<input type='hidden' name='tullaan' value='{$tullaan}' />";
  }

  if ((isset($gokfrom) and $gokfrom == 'avaavatase') or (isset($tilikausi) and is_numeric($tilikausi))) {
    echo "<input type='hidden' name='avaavatase' value='joo' />";
    echo "<input type='hidden' name='tilikausi'  value='{$tilikausi}' />";
  }

  // Uusi tosite
  // Tehdään haluttu määrä tiliöintirivejä
  $tilmaarat = array("3", "5", "9", "13", "17", "21", "25", "29", "33", "41", "51", "101", "151", "201", "301", "401", "501", "601", "701", "801", "901", "1001");

  if (isset($gokfrom) and $gokfrom != "") {
    // Valitaan sopiva tiliöintimäärä kun tullaan palkkatositteelta
    foreach ($tilmaarat as $tilmaara) {
      if ($tilmaara > $maara) {
        $maara = $tilmaara;
        break;
      }
    }
  }

  if (isset($tunnus) and $tunnus > 0 and count($tiliointirivit) > 0) {

    $query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tila = 'X' and tunnus = '{$tunnus}'";
    $lasku_chk_res = pupe_query($query);
    $lasku_chk_row = mysql_fetch_assoc($lasku_chk_res);

    $comments = $lasku_chk_row['comments'];

    $itili = $ikustp = $ikohde = $iprojekti = $isumma = $isumma_valuutassa = $ivero = $iselite = array();

    $skipattuja = 0;

    foreach ($tiliointirivit as $xxx => $rivix) {

      $query = "SELECT *
                FROM tiliointi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND ltunnus = '{$tunnus}'
                AND tunnus  = '{$rivix}'";
      $info_res = pupe_query($query);
      $info_row = mysql_fetch_assoc($info_res);

      if ($info_row['korjattu'] != '') {
        $skipattuja++;
        continue;
      }

      $xxx -= $skipattuja;

      $itili[$xxx] = $info_row['tilino'];
      $ikustp[$xxx] = $info_row['kustp'];
      $ikohde[$xxx] = $info_row['kohde'];
      $iprojekti[$xxx] = $info_row['projekti'];
      $isumma[$xxx] = $info_row['summa'];
      $isumma_valuutassa[$xxx] = $info_row['summa_valuutassa'];
      $ivero[$xxx] = $info_row['vero'];
      $iselite[$xxx] = $info_row['selite'];
    }

    $maara = (!isset($maara) or $maara < (count($tiliointirivit) + 1 - $skipattuja)) ? count($tiliointirivit) + 1 - $skipattuja : $maara;
  }

  if (!in_array($maara, $tilmaarat)) {
    $tilmaarat[] = $maara;
    sort($tilmaarat);
  }

  $sel = array();
  $sel[$maara] = "selected";

  echo "<table>
    <tr>
    <th>".t("Tiliöintirivien määrä")."</th>
    <td>
    <select name='maara' onchange='submit();'>";

  foreach ($tilmaarat as $tilmaara) {
    echo "<option {$sel[$tilmaara]} value='{$tilmaara}'>".($tilmaara-1)."</option>";
  }

  echo "</select></td>";

  echo "<th nowrap>".t("Valitse toimittaja")."</th>";
  echo "<td>";

  echo "<input type = 'text' name = 'toimittaja_y' size='20'></td><td class='back'><input type = 'submit' class='hae_btn' value = '".t("Etsi")."'>";

  echo "</td>\n";
  echo "</tr>\n";

  $tpp = !isset($tpp) ? date('d') : $tpp;
  $tpk = !isset($tpk) ? date('m') : $tpk;
  $tpv = !isset($tpv) ? date('Y') : $tpv;

  echo "<tr>\n";
  echo "<th>".t("Tositteen päiväys")."</th>\n";
  echo "<td><input type='text' name='tpp' maxlength='2' size='2' value='{$tpp}'>\n";
  echo "<input type='text' name='tpk' maxlength='2' size='2' value='{$tpk}'>\n";
  echo "<input type='text' name='tpv' maxlength='4' size='4' value='{$tpv}'> ".t("ppkkvvvv")." {$tapvmvirhe}</td>\n";

  echo "<th nowrap>".t("tai")." ".t("valitse asiakas")."</th>";
  echo "<td>";

  echo "<input type = 'text' name = 'asiakas_y' size='20'></td><td class='back'><input type = 'submit' class='hae_btn' value = '".t("Etsi")."'>";

  echo "</td>\n";
  echo "</tr>\n";

  if (!isset($turvasumma_valuutassa)) $turvasumma_valuutassa = $summa;

  echo "<tr><th>".t("Summa")."</th><td><input type='text' name='summa' value='{$turvasumma_valuutassa}' onchange='javascript:tositesumma();' onkeyup='javascript:tositesumma();'>\n";

  $query = "SELECT nimi, tunnus
            FROM valuu
            WHERE yhtio = '{$kukarow['yhtio']}'
            ORDER BY jarjestys";
  $vresult = pupe_query($query);

  echo " <select name='valkoodi'>\n";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel="";
    if (($vrow['nimi'] == $yhtiorow["valkoodi"] and $valkoodi == "") or ($vrow["nimi"] == $valkoodi)) {
      $sel = "selected";
    }
    echo "<option value='{$vrow['nimi']}' {$sel}>{$vrow['nimi']}</option>\n";
  }

  echo "</select>\n";
  echo "</td>\n";
  echo "<th>".t("tai")." ".t("Syötä nimi")."</th><td><input type='text' size='20' name='nimi' value='{$nimi}'></td></tr>\n";


  echo "<tr><th>".t("Tositteen kuva/liite")."</th>\n";

  if (strlen($kuva) > 0) {
    echo "<td>".t("Kuva jo tallessa")."!<input name='kuva' type='hidden' value = '{$kuva}'></td>\n";
  }
  else {
    echo "<td><input type='hidden' name='MAX_FILE_SIZE' value='8000000'><input name='userfile' type='file'></td>\n";
  }

  echo "<th>".t("Tulosta kuitti")."</th><td>";

  if ($kukarow['kirjoitin'] > 0) {

    if ($kuitti != '') {
      $kuitti = 'checked';
    }

    echo "<input type='checkbox' name='kuitti' {$kuitti}>\n";
  }
  else {
    echo "<font class='message'>".t("Sinulla ei ole oletuskirjoitinta. Et voi tulostaa kuitteja")."!</font>\n";
  }

  echo " {$kuittivirhe}</td></tr>\n";

  // tutkitaan ollaanko jossain toimipaikassa alv-rekisteröity
  $query = "SELECT *
            FROM yhtion_toimipaikat
            WHERE yhtio     = '{$kukarow['yhtio']}'
            and maa        != ''
            and vat_numero != ''
            and toim_alv   != ''";
  $alhire = pupe_query($query);

  // ollaan alv-rekisteröity
  if (mysql_num_rows($alhire) >= 1) {

    echo "<tr>\n";
    echo "<th>".t("Alv tili")."</th><td colspan='3'>\n";
    echo "<select name='alv_tili'>\n";
    echo "<option value='{$yhtiorow['alv']}'>{$yhtiorow['alv']} - {$yhtiorow['nimi']}, {$yhtiorow['kotipaikka']}, {$yhtiorow['maa']}</option>\n";

    while ($vrow = mysql_fetch_assoc($alhire)) {
      $sel = "";
      if ($alv_tili == $vrow['toim_alv']) {
        $sel = "selected";
      }
      echo "<option value='{$vrow['toim_alv']}' {$sel}>{$vrow['toim_alv']} - {$vrow['nimi']}, {$vrow['kotipaikka']}, {$vrow['maa']}</option>\n";
    }

    echo "</select>\n";
    echo "</td>\n";
    echo "</tr>\n";
  }
  else {
    $tilino_alv = $yhtiorow["alv"];
    echo "<input type='hidden' name='alv_tili' value='{$tilino_alv}'>\n";
  }

  echo "<tr>\n";
  echo "<th>".t("Tositteen kommentti")."</th>\n";
  echo "<td colspan='3'><input type='text' name='comments' value='{$comments}' size='60'></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<th>".t("Tiliöintien selitteet")."</th>\n";
  echo "<td colspan='3'><input type='text' name='selite' value='{$selite}' maxlength='150' size='60' onchange='javascript:selitejs();' onkeyup='javascript:selitejs();'></td>\n";
  echo "</tr>\n";

  echo "<tr>
      <th>".t("Lue tositteen rivit tiedostosta")."</th>
      <td colspan='3'><input type='file' name='tositefile' onchage='submit()'></td>
      </tr>\n";

  if ($asiakasid > 0 or $toimittajaid > 0) {
    echo "<tr>\n";
    echo "<td colspan='4' class='back'><br></td>\n";
    echo "</tr>\n";

    if ($asiakasid > 0) {
      echo "<tr>\n";
      echo "<th>".t("Valittu asiakas")."</th>\n";
      echo "<td colspan='2'><input type='hidden' name='asiakasid' value='{$asiakasid}'>{$asrow['ytunnus']} {$asrow['nimi']} {$asrow['nimitark']}<br>{$asrow['toim_ovttunnus']} {$asrow['toim_nimi']} {$asrow['toim_nimitark']} {$asrow['toim_postitp']}</td>\n";
      echo "<td>".t("Liitä valittu asiakas kaikkiin tiliöinteihin").": <input type='checkbox' id='liitosbox_checkall'/></td>";
      echo "</tr>\n";
    }
    if ($toimittajaid > 0) {
      echo "<tr>\n";
      echo "<th>".t("Valittu toimittaja")."</th>\n";
      echo "<td colspan='2'><input type='hidden' name='toimittajaid' value='{$toimittajaid}'>{$toimrow['ytunnus']} {$toimrow['nimi']}</td>\n";
      echo "<td>".t("Liitä valittu toimittaja kaikkiin tiliöinteihin").": <input type='checkbox' id='liitosbox_checkall'/></td>";
      echo "</tr>\n";
    }
  }

  echo "</table>\n";
  echo "<br><font class='head'>".t("Syötä tositteen rivit").":</font>\n";

  echo "<table>\n";

  for ($i=1; $i<$maara; $i++) {

    if ($i == 1) {
      echo "<tr>\n";
      echo "<th width='200'>".t("Tili")."</th>\n";
      echo "<th>".t("Tarkenne")."</th>\n";
      echo "<th>".t("Summa")."</th>\n";
      echo "<th>".t("Vero")."</th>\n";
      echo "<th>", t("Liitos"), "</th>";

      if ($asiakasid > 0) {
        echo "<th>", t("Liitä valittu asiakas"), "</th>";
      }
      if ($toimittajaid > 0) {
        echo "<th>", t("Liitä valittu toimittaja"), "</th>";
      }

      echo "</tr>\n";
    }

    echo "<tr>\n";

    if (!isset($iulos[$i]) or $iulos[$i] == '') {
      //Annetaan selväkielinen nimi
      $tilinimi = '';

      if (isset($itili[$i]) and $itili[$i] != '') {
        $query = "SELECT nimi
                  FROM tili
                  WHERE yhtio = '{$kukarow['yhtio']}' and tilino = '{$itili[$i]}'";
        $vresult = pupe_query($query);

        if (mysql_num_rows($vresult) == 1) {
          $vrow = mysql_fetch_assoc($vresult);
          $tilinimi = $vrow['nimi'];
        }
      }
      echo "<td width='200' class='ptop'\">".livesearch_kentta("tosite", "TILIHAKU", "itili[$i]", 170, $itili[$i], "EISUBMIT", "ivero[$i]")." {$tilinimi}</td>\n";
    }
    else {
      echo "<td width='200' class='ptop'>{$iulos[$i]}</td>\n";
    }

    echo "<td>\n";

    $query = "SELECT tunnus, nimi, koodi
              FROM kustannuspaikka
              WHERE yhtio   = '{$kukarow['yhtio']}'
              and tyyppi    = 'K'
              and kaytossa != 'E'
              ORDER BY koodi+0, koodi, nimi";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      echo "<select name = 'ikustp[{$i}]' style='width: 140px'><option value = ' '>".t("Ei kustannuspaikkaa");

      while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
        $valittu = "";
        if (isset($ikustp[$i]) and $kustannuspaikkarow["tunnus"] == $ikustp[$i]) {
          $valittu = "SELECTED";
        }
        echo "<option value = '{$kustannuspaikkarow['tunnus']}' {$valittu}>{$kustannuspaikkarow['koodi']} {$kustannuspaikkarow['nimi']}\n";
      }
      echo "</select><br>\n";
    }

    $query = "SELECT tunnus, nimi, koodi
              FROM kustannuspaikka
              WHERE yhtio   = '{$kukarow['yhtio']}'
              and tyyppi    = 'O'
              and kaytossa != 'E'
              ORDER BY koodi+0, koodi, nimi";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      echo "<select name = 'ikohde[{$i}]' style='width: 140px'><option value = ' '>".t("Ei kohdetta");

      while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
        $valittu = "";
        if (isset($ikohde[$i]) and $kustannuspaikkarow["tunnus"] == $ikohde[$i]) {
          $valittu = "SELECTED";
        }
        echo "<option value = '{$kustannuspaikkarow['tunnus']}' {$valittu}>{$kustannuspaikkarow['koodi']} {$kustannuspaikkarow['nimi']}\n";
      }
      echo "</select><br>\n";
    }

    $query = "SELECT tunnus, nimi, koodi
              FROM kustannuspaikka
              WHERE yhtio   = '{$kukarow['yhtio']}'
              and tyyppi    = 'P'
              and kaytossa != 'E'
              ORDER BY koodi+0, koodi, nimi";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      echo "<select name = 'iprojekti[{$i}]' style='width: 140px'><option value = ' '>".t("Ei projektia");

      while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
        $valittu = "";
        if (isset($iprojekti[$i]) and $kustannuspaikkarow["tunnus"] == $iprojekti[$i]) {
          $valittu = "SELECTED";
        }
        echo "<option value = '{$kustannuspaikkarow['tunnus']}' {$valittu}>{$kustannuspaikkarow['koodi']} {$kustannuspaikkarow['nimi']}\n";
      }
      echo "</select>\n";
    }

    echo "</td>\n";
    echo "<td align='right'>
          <input type='text' size='13' style='text-align: right;' name='isumma[{$i}]' value='{$isumma_valuutassa[$i]}' onchange='javascript:tositesumma();' onkeyup='javascript:tositesumma();'> {$valkoodi}<br>
          <input type='text' size='13' style='text-align: right; border: 0px solid; outline: none;' name='pupedevnull' value='{$isumma[$i]}' readonly='readonly'> {$valkoodi}
          </td>\n";

    if (!isset($hardcoded_alv) or $hardcoded_alv != 1) {
      echo "<td class='ptop'>" . alv_popup('ivero['.$i.']', $ivero[$i]) . "</td>\n";
    }
    else {
      echo "<td></td>\n";
    }

    echo "<td>";

    $cspan = 0;

    if (isset($iliitos[$i]) and trim($iliitos[$i]) != '' and ((isset($iliitostunnus[$i]) and trim($iliitostunnus[$i]) != '') or (isset($ed_iliitostunnus[$i]) and trim($ed_iliitostunnus[$i]) != ''))) {

      $tunnus_chk = (isset($iliitostunnus[$i]) and trim($iliitostunnus[$i]) != '') ? $iliitostunnus[$i] : $ed_iliitostunnus[$i];
      $taulu_chk = $tunnus_chk == $ed_iliitostunnus[$i] ? $ed_iliitos[$i] : $iliitos[$i];

      if ($taulu_chk == "A") {
        $taulu = "asiakas";
      }
      elseif ($taulu_chk == "T") {
        $taulu = "toimi";
      }
      else {
        $taulu = "SQLERROR";
      }

      $query = "SELECT nimi, nimitark
                FROM {$taulu}
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$tunnus_chk}'";
      $asiakasres = pupe_query($query);
      $asiakasrow = mysql_fetch_assoc($asiakasres);

      echo "{$asiakasrow['nimi']} {$asiakasrow['nimitark']}";
    }

    echo "</td>";

    if ($toimittajaid > 0) {
      $cspan++;

      echo "<td>";
      $chk = (isset($iliitostunnus[$i]) and trim($iliitostunnus[$i]) == $toimittajaid) ? ' checked' : ((isset($ed_iliitostunnus[$i]) and trim($ed_iliitostunnus[$i]) == $toimittajaid) ? ' checked' : '');

      echo "<input type='hidden' name='ed_iliitostunnus[{$i}]' value='{$ed_iliitostunnus[$i]}' />";
      echo "<input type='hidden' name='ed_iliitos[$i]' value='{$ed_iliitos[$i]}' />";
      echo "<input type='hidden' name='iliitos[{$i}]' value='T' />";
      echo "<input type='checkbox' class='liitosbox' name='iliitostunnus[{$i}]' value='{$toimittajaid}' {$chk} /> ";
      echo "</td>\n";
    }

    if ($asiakasid > 0) {
      $cspan++;

      echo "<td>";
      $chk = (isset($iliitostunnus[$i]) and trim($iliitostunnus[$i]) == $asiakasid) ? ' checked' : ((isset($ed_iliitostunnus[$i]) and trim($ed_iliitostunnus[$i]) == $asiakasid) ? ' checked' : '');

      echo "<input type='hidden' name='ed_iliitostunnus[{$i}]' value='{$ed_iliitostunnus[$i]}' />";
      echo "<input type='hidden' name='ed_iliitos[$i]' value='{$ed_iliitos[$i]}' />";
      echo "<input type='hidden' name='iliitos[{$i}]' value='A' />";
      echo "<input type='checkbox' class='liitosbox' name='iliitostunnus[{$i}]' value='{$asiakasid}' {$chk} /> ";
      echo "</td>\n";
    }


    echo "<td class='back'>";
    if (isset($ivirhe[$i])) echo "<font class='error'>{$ivirhe[$i]}</font>";
    echo "</td>\n";
    echo "</tr>\n";

    // Ei rikota rivinvaihtoja
    $iselite[$i] = str_ireplace("<br>", "(br)", $iselite[$i]);

    echo "<tr><td colspan='".(5+$cspan)."' nowrap><input type='text' name='iselite[{$i}]' value='{$iselite[$i]}' maxlength='150' size='80' placeholder='".t("Selite")."'></td></tr>\n";
    echo "<tr style='height: 5px;'></tr>\n";
  }

  echo "<tr><th colspan='2'>".t("Tosite yhteensä").":</th><td><input type='text' size='13' style='text-align: right;' name='tositesum' value='' readonly> {$valkoodi}</td></tr>\n";
  echo "</table><br>\n";

  echo "<script language='javascript'>javascript:tositesumma();</script>";

  if ($heittovirhe == 1) {

    $heittotila = '';

    if ($heittook != '') {
      $heittotila = 'checked';
    }

    echo "<font class='error'>".t("HUOM: Tosite ei täsmää").":</font> <input type='checkbox' name='heittook' {$heittotila}> ".t("Hyväksy heitto").".<br><br>";
  }

  echo "<input type='submit' name='teetosite' value='".t("Tee tosite")."'></form>\n";

}

require "inc/footer.inc";
