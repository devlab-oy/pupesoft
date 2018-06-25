<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;
$compression = FALSE;

if (isset($_REQUEST["komento"]) and in_array("PDF_RUUDULLE", $_REQUEST["komento"])) {
  $_REQUEST["tee"] = $_POST["tee"] = $_GET["tee"] = "NAYTATILAUS";
}

if ((isset($_REQUEST["tee"]) and $_REQUEST["tee"] == 'NAYTATILAUS') or
  (isset($_POST["tee"]) and $_POST["tee"] == 'NAYTATILAUS') or
  (isset($_GET["tee"]) and $_GET["tee"] == 'NAYTATILAUS')) $nayta_pdf = 1; //Generoidaan .pdf-file

if (@include "../inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

if ($tee == 'lataa_tiedosto') {
  $filepath = "/tmp/".$tmpfilenimi;
  if (file_exists($filepath)) {
    readfile($filepath);
    unlink($filepath);
  }
  exit;
}

if (!isset($logistiikka_yhtio)) $logistiikka_yhtio = "";
if (!isset($logistiikka_yhtiolisa)) $logistiikka_yhtiolisa = "";
if (!isset($asiakasid)) $asiakasid = "";
if (!isset($toimittajaid)) $toimittajaid = "";
if (!isset($tee)) $tee = "";
if (!isset($laskunro)) $laskunro = "";
if (!isset($ytunnus)) $ytunnus = "";
if (!isset($lopetus)) $lopetus = "";
if (!isset($toim) or $toim == "") $toim = "LASKU";
if (!isset($tila)) $tila = "";
if (!isset($tunnus)) $tunnus = "";
if (!isset($laskunroloppu)) $laskunroloppu = "";
if (!isset($kerayseran_numero)) $kerayseran_numero = "";
if (!isset($kerayseran_tilaukset)) $kerayseran_tilaukset = "";
if (!isset($toimipaikka)) $toimipaikka = $kukarow['toimipaikka'] != 0 ? $kukarow['toimipaikka'] : "";

if (empty($kieli) and $yhtiorow['pdf_ruudulle_kieli'] == "") $kieli = $yhtiorow['kieli'];

$kaikkilomakepohjat = FALSE;
$onkolaajattoimipaikat = ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikat_res) > 0) ? TRUE : FALSE;

if ($toim == "OSOITELAPPU") {
  //osoitelapuille on vähän eri päivämäärävaatimukset kuin muilla
  if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
}
elseif ($toim == "TARJOUS" or $toim == "TARJOUS!!!VL" or $toim == "TARJOUS!!!BR" or $toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL" or $toim == "MYYNTISOPIMUS!!!BR" or $toim == "OSAMAKSUSOPIMUS" or $toim == "LUOVUTUSTODISTUS" or $toim == "VAKUUTUSHAKEMUS" or $toim == "REKISTERIILMOITUS") {
  //Näissä kaupoissa voi kestää vähän kauemmin
  if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
  if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
  if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
}
else {
  if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
  if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
  if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
}

if (!isset($kkl)) $kkl = date("m");
if (!isset($vvl)) $vvl = date("Y");
if (!isset($ppl)) $ppl = date("d");

if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '' and ($toim == "LAHETE" or $toim == "KOONTILAHETE" or $toim == "OSOITELAPPU" or $toim == "KERAYSLISTA")) {
  $logistiikka_yhtio = $konsernivarasto_yhtiot;
  $logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

  if ($lasku_yhtio != '') {
    $kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);
    $yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
  }
}
else {
  $logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
}

if ($tee == 'NAYTAHTML') {
  if ($logistiikka_yhtio != '' and $konsernivarasto_yhtiot != '') {
    echo "<font class='head'>", t("Yhtiön"), " $yhtiorow[nimi] ", t("tilaus"), " $tunnus:</font><hr>";
  }
  elseif ($toim != "LASKU") {
    echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
  }
  else {
    echo "<font class='head'>".t("Lasku").":</font><hr>";
  }

  require "raportit/naytatilaus.inc";

  echo "<br><br>";
  $tee = "ETSILASKU";
}

if ($toim == 'OSTO' or $toim == 'HAAMU' or $toim == 'PURKU' or $toim == 'TARIFFI' or $toim == 'VASTAANOTTORAPORTTI') {
  $query_ale_lisa = generoi_alekentta('O');
}
else {
  $query_ale_lisa = generoi_alekentta('M');
}

if ($toim == "OSTO") {
  $fuse = t("Ostotilaus");
}
if ($toim == "HAAMU") {
  $fuse = t("Ostotilaus haamu");
}
if ($toim == "PURKU") {
  $fuse = t("Purkulista");
}
if ($toim == "VASTAANOTETUT") {
  $fuse = t("Vastaanotetut");
}
if ($toim == "VASTAANOTTORAPORTTI") {
  $fuse = t("Vastaanottoraportti");
}
if ($toim == "TARIFFI") {
  $fuse = t("Tariffilista");
}
if ($toim == "SIIRTOLISTA") {
  $fuse = t("Siirtolista");
}
if ($toim == "VALMISTUS") {
  $fuse = t("Valmistus");
}
if ($toim == "LASKU") {
  $fuse = t("Lasku");
}
if ($toim == "VIENTILASKU") {
  $fuse = t("Vientilasku");
}
if ($toim == "TUOTETARRA") {
  $fuse = t("Tuotetarra");
}
if ($toim == "TILAUSTUOTETARRA") {
  $fuse = t("Tilauksen tuotetarrat");
}
if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {
  $fuse = t("Työmääräys");
}
if ($toim == "HUOLTOSAATE") {
  $fuse = t("Huoltosaate");
}
if ($toim == "SAD") {
  $fuse = t("Sad-lomake");
}
if ($toim == "LAHETE" or $toim == "KOONTILAHETE") {
  $fuse = t("Lähete");
}
if ($toim == "DGD") {
  $fuse = "DGD - Multimodal Dangerous Goods Form";
}
if ($toim == "PAKKALISTA") {
  //  Tämä on about yhdistetty vienti-erittely ja lähete
  $fuse = t("Pakkalista");
}
if ($toim == "KERAYSLISTA") {
  $fuse = t("Keräyslista");
}
if ($toim == "OSOITELAPPU") {
  $fuse = t("Osoitelappu");
}
if ($toim == "VIENTIERITTELY") {
  $fuse = t("Vientierittely");
}
if ($toim == "PROFORMA") {
  $fuse = t("Proforma");
}
if ($toim == "TILAUSVAHVISTUS") {
  $fuse = t("Tilausvahvistus");
}
if ($toim == "YLLAPITOSOPIMUS") {
  $fuse = t("Ylläpitosopimus");
}
if ($toim == "TARJOUS" or $toim == "TARJOUS!!!VL" or $toim == "TARJOUS!!!BR") {
  $fuse = t("Tarjous");
}
if ($toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL" or $toim == "MYYNTISOPIMUS!!!BR") {
  $fuse = t("Myyntisopimus");
}
if ($toim == "OSAMAKSUSOPIMUS") {
  $fuse = t("Osamaksusopimus");
}
if ($toim == "LUOVUTUSTODISTUS") {
  $fuse = t("Luovutustodistus");
}
if ($toim == "VAKUUTUSHAKEMUS") {
  $fuse = t("Vakuutushakemus");
}
if ($toim == "REKISTERIILMOITUS") {
  $fuse = t("Rekisteröinti-ilmoitus");
}
if ($toim == "REKLAMAATIO") {
  $fuse = t("Reklamaatio/Purkulista");
}
if ($toim == "TAKUU") {
  $fuse = t("Takuu");
}
if ($toim == "LAVATARRA") {
  $fuse = t("Lavatarra");
}
if ($toim == "LAVAKERAYSTARRA") {
  $fuse = t("Lavakeraystarra");
}

if (isset($muutparametrit) and $muutparametrit != '') {
  $muut = explode('/', $muutparametrit);

  $vva     = $muut[0];
  $kka     = $muut[1];
  $ppa     = $muut[2];
  $vvl     = $muut[3];
  $kkl    = $muut[4];
  $ppl     = $muut[5];
}

if ($tee != 'NAYTATILAUS') {
  echo "<font class='head'>".sprintf(t("Tulosta %s kopioita"), $fuse).":</font><hr><br>";
}

if ($laskunro <> 0 and $laskunroloppu <> 0 and $laskunro < $laskunroloppu) {
  $tee = "TULOSTA";

  $tulostukseen = array();

  for ($las = $laskunro; $las<=$laskunroloppu; $las++) {
    //hateaan laskun kaikki tiedot
    $query = "SELECT tunnus
              FROM lasku
              WHERE tila   = 'U'
              and alatila  = 'X'
              and laskunro = '$las'
              and yhtio    = '$kukarow[yhtio]'";
    $rrrresult = pupe_query($query);

    if ($laskurow = mysql_fetch_assoc($rrrresult)) {
      $tulostukseen[] = $laskurow["tunnus"];
    }
  }

  $laskunro    = "";
  $laskunroloppu  = "";
}

// Extranettaajat voivat ottaa kopioita omista laskuistaan ja lähetteistään
if ($kukarow["extranet"] != "") {
  if ($kukarow["oletus_asiakas"] > 0 and ($toim == "LAHETE" or $toim == "KOONTILAHETE" or $toim == "LASKU" or $toim == "VIENTILASKU" or $toim == "TILAUSVAHVISTUS")) {
    $query  = "SELECT *
               FROM asiakas
               WHERE yhtio = '$kukarow[yhtio]'
               and tunnus  = '$kukarow[oletus_asiakas]'";
    $vieres = pupe_query($query);

    if (mysql_num_rows($vieres) == 1) {
      $asiakasrow = mysql_fetch_assoc($vieres);

      $asiakasid   = $asiakasrow["tunnus"];
      $ytunnus  = $asiakasrow["ytunnus"];
      $kieli = $asiakasrow["kieli"] != '' ? $asiakasrow["kieli"] : $kieli;
    }
    else {
      exit;
    }
  }
  elseif ($toim == "HUOLTOPYYNTOKOPIO" and !empty($tyom_tunnus) and !empty($pdffilenimi)) {
    if ($tee == 'NAYTATILAUS') {
      echo file_get_contents($pdffilenimi);
    }
    $tee = '';
  }
  else {
    // Extranet kaatuu tähän
    exit;
  }
}
elseif (($tee == "" or $tee == 'ETSILASKU') and $toim != 'SIIRTOLISTA') {
  $muutparametrit = $vva."/".$kka."/".$ppa."/".$vvl."/".$kkl."/".$ppl;

  if ($ytunnus != '' or (int) $asiakasid > 0 or (int) $toimittajaid > 0) {
    if ($toim == 'OSTO' or $toim == 'PURKU' or $toim == 'TARIFFI' or $toim == 'TUOTETARRA' or $toim == 'VASTAANOTETUT' or $toim == 'HAAMU'  or $toim == 'VASTAANOTTORAPORTTI') {
      require "../inc/kevyt_toimittajahaku.inc";
    }
    else {
      if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '' and ($toim == "LAHETE" or $toim == "KOONTILAHETE" or $toim == "OSOITELAPPU" or $toim == "KERAYSLISTA")) {
        $konserni = $yhtiorow['konserni'];
      }
      require "inc/asiakashaku.inc";
    }

    if ($ytunnus == "") {
      $tee = "";
    }
  }
}

if (($tee == "" or $tee == 'ETSILASKU') and $toim == 'SIIRTOLISTA') {
  if ($lahettava_varasto != '' or $vastaanottava_varasto != '') {
    $tee = "ETSILASKU";
  }
  else {
    $tee = "";
  }

  if ($otunnus > 0) {
    $tee = 'ETSILASKU';
  }
}

if ($tee != 'NAYTATILAUS') {

  js_popup(-100);

  //syötetään tilausnumero
  echo "<form method='post' autocomplete='off' name='hakuformi'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='tee' value='ETSILASKU'>";

  echo "<table>";

  if (trim($toim) == "SIIRTOLISTA") {

    echo "<tr><th>".t("Lähettävä varasto")."</th><td colspan='3'>";

    $query = "SELECT *
              FROM varastopaikat
              WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
              ORDER BY tyyppi, nimitys";
    $vtresult = pupe_query($query);

    echo "<select name='lahettava_varasto'>";
    echo "<option value=''>".t("Valitse varasto")."</option>";

    while ($vrow = mysql_fetch_assoc($vtresult)) {
      if ($lahettava_varasto == $vrow["tunnus"]) {
        $sel = "SELECTED";
      }
      else {
        $sel = "";
      }

      echo "<option value='$vrow[tunnus]' $sel>$vrow[maa] $vrow[nimitys]</option>";
    }
    echo "</select>";

    echo "</td></tr>";

    echo "<tr><th>".t("Vastaanottava varasto")."</th><td colspan='3'>";

    $query = "SELECT *
              FROM varastopaikat
              WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
              ORDER BY tyyppi, nimitys";
    $vtresult = pupe_query($query);

    echo "<select name='vastaanottava_varasto'>";
    echo "<option value=''>".t("Valitse varasto")."</option>";

    while ($vrow = mysql_fetch_assoc($vtresult)) {
      if ($vastaanottava_varasto == $vrow["tunnus"]) {
        $sel = "SELECTED";
      }
      else {
        $sel = "";
      }
      echo "<option value='$vrow[tunnus]' $sel>$vrow[maa] $vrow[nimitys]</option>";
    }
    echo "</select>";

    echo "</td></tr>";

    echo "<tr><th>".t("Tilausnumero")."</th><td colspan='3'><input type='text' size='15' name='otunnus'></td></tr>";
  }
  else {
    if (((int) $asiakasid > 0 or (int) $toimittajaid > 0)) {
      if ($toim == "OSTO" or $toim == "PURKU" or $toim == "TARIFFI" or $toim == "TUOTETARRA" or $toim == 'VASTAANOTETUT' or $toim == "HAAMU" or $toim == 'VASTAANOTTORAPORTTI') {
        echo "<th>".t("Toimittajan nimi")."</th><td colspan='3'>$toimittajarow[nimi]<input type='hidden' name='toimittajaid' value='$toimittajaid'></td>";

        if ($kukarow["extranet"] == "") {
          echo "<td><a href='$PHP_SELF?lopetus=$lopetus&toim=$toim&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka'>".t("Vaihda toimittaja")."</a></td>";
        }

        echo "</tr>";
      }
      else {
        echo "<th>".t("Asiakas")."</th><td colspan='3'>$asiakasrow[nimi]<input type='hidden' name='asiakasid' value='$asiakasid'></td>";

        if ($kukarow["extranet"] == "") {
          echo "<td><a href='$PHP_SELF?lopetus=$lopetus&toim=$toim&ppl=$ppl&vvl=$vvl&kkl=$kkl&ppa=$ppa&vva=$vva&kka=$kka'>".t("Vaihda asiakas")."</a></td>";
        }

        echo "</tr>";
      }
    }
    else {
      if ($toim == "OSTO" or $toim == "PURKU" or $toim == "TARIFFI" or $toim == "TUOTETARRA" or $toim == 'VASTAANOTETUT' or $toim == "HAAMU" or $toim == 'VASTAANOTTORAPORTTI') {
        echo "<th>".t("Toimittajan nimi")."</th><td colspan='3'><input type='text' name='ytunnus' value='$ytunnus' size='15'></td></tr>";
      }
      else {
        echo "<th>".t("Asiakas")."</th><td colspan='3'><input type='text' name='ytunnus' value='$ytunnus' size='15'> ", asiakashakuohje(), "</td></tr>";
      }

      if (empty($tee)) {
        $formi  = 'hakuformi';
        $kentta = 'ytunnus';
      }
    }

    echo "<tr><th>".t("Tilausnumero")."</th><td colspan='3'><input type='text' size='15' name='otunnus'></td></tr>";

    if ($yhtiorow['kerayserat'] == 'K' and $toim == "KERAYSLISTA") {
      echo "<tr><th>".t("Keräyserä")."</th><td colspan='3'><input type='text' size='15' name='kerayseran_numero'></td></tr>";
    }

    echo "<tr>";

    if ($toim == "PURKU" or $toim == "TARIFFI" or $toim == "VASTAANOTTORAPORTTI") {
      echo "<th>".t("Saapumisnumero")."</th>";
    }
    else {
      echo "<th>".t("Laskunumero")."</th>";
    }

    echo "<td colspan='3'><input type='text' size='15' name='laskunro'></td>";

    if ($kukarow["extranet"] == "" and $toim != "VASTAANOTTORAPORTTI") {
      echo "<td colspan='3'><input type='text' size='15' name='laskunroloppu'></td>";
    }

    echo "</tr>";

    if ($onkolaajattoimipaikat and $toim == "VASTAANOTTORAPORTTI") {

      $sel = $toimipaikka == 0 ? "selected" : "";

      echo "<tr>";
      echo "<th>", t("Toimipaikka"), "</th>";
      echo "<td colspan='3'>";
      echo "<select name='toimipaikka'>";
      echo "<option value='kaikki'>", t("Kaikki toimipaikat"), "</option>";
      echo "<option value='0' {$sel}>", t("Ei toimipaikkaa"), "</option>";

      while ($toimipaikat_row = mysql_fetch_assoc($toimipaikat_res)) {
        $sel = $toimipaikat_row['tunnus'] == $toimipaikka ? "selected" : "";
        echo "<option value='{$toimipaikat_row['tunnus']}' {$sel}>{$toimipaikat_row['nimi']}</option>";
      }

      echo "</select>";
      echo "</td><td class='back'></td>";
      echo "</tr>";
    }
  }

  echo "<tr><th>".t("Alkupäivämäärä (pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppa' value='$ppa' size='3'></td>
      <td><input type='text' name='kka' value='$kka' size='3'></td>
      <td><input type='text' name='vva' value='$vva' size='5'></td>
      </tr><tr><th>".t("loppupäivämäärä (pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppl' value='$ppl' size='3'></td>
      <td><input type='text' name='kkl' value='$kkl' size='3'></td>
      <td><input type='text' name='vvl' value='$vvl' size='5'></td>
      <td class='back'><input type='submit' class='hae_btn' value='".t("Etsi")."'></td>";
  echo "</table>";
  echo "</form><br>";
}

if ($tee == "ETSILASKU") {

  // ekotetaan javascriptiä jotta saadaan pdf:ät uuteen ikkunaan
  js_openFormInNewWindow();

  $where1   = "";
  $where2   = "";
  $where3   = "";
  $where4    = "";
  $where5    = "";
  $joinlisa   = "";

  if (isset($asiakasrow)) {
    $wherenimi = "  and lasku.nimi      = '$asiakasrow[nimi]'
             and lasku.nimitark    = '$asiakasrow[nimitark]'
             and lasku.osoite    = '$asiakasrow[osoite]'
             and lasku.postino    = '$asiakasrow[postino]'
             and lasku.postitp    = '$asiakasrow[postitp]'
             and lasku.toim_nimi    = '$asiakasrow[toim_nimi]'
             and lasku.toim_nimitark  = '$asiakasrow[toim_nimitark]'
             and lasku.toim_osoite  = '$asiakasrow[toim_osoite]'
             and lasku.toim_postino  = '$asiakasrow[toim_postino]'
              and lasku.toim_postitp  = '$asiakasrow[toim_postitp]' ";
  }

  $reklaprosessi = "";
  // Pitkä prosessi aina reklamaation ja takuun käsittelyssä
  if (($toim == "REKLAMAATIO" or $toim == "TAKUU") and $yhtiorow['reklamaation_kasittely'] == 'U') {
    $reklaprosessi = "PITKA";
  }
  // Lyhyt prosessi aina reklamaation ja takuun käsittelssä
  elseif (($toim == "REKLAMAATIO" or $toim == "TAKUU") and $yhtiorow['reklamaation_kasittely'] == '') {
    $reklaprosessi = "LYHYT";
  }
  // Pitkä prosessi vain reklamaation käsittelyssä (takuut lyhyellä)
  elseif ($toim == "REKLAMAATIO" and $yhtiorow['reklamaation_kasittely'] == 'X') {
    $reklaprosessi = "PITKA";
  }
  // Lyhyt prosessi takuiden käisttelyssä (reklamaatiot pitkällä)
  elseif ($toim == "TAKUU" and $yhtiorow['reklamaation_kasittely'] == 'X') {
    $reklaprosessi = "LYHYT";
  }

  if ($reklaprosessi == "PITKA") {
    $where1 .= " lasku.tila in ('C','L') ";

    $where2 .= " and lasku.alatila in('C','D','X')";

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'
           and lasku.ytunnus = '$ytunnus'";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($reklaprosessi == "LYHYT") {

    if ($toim == "TAKUU") {
      $where1 .= " lasku.tila in ('L','N','C') and lasku.tilaustyyppi = 'U' ";
    }
    else {
      $where1 .= " lasku.tila in ('L','N','C') and lasku.tilaustyyppi = 'R' ";
    }

    $where2 .= " and lasku.alatila in ('','A','B','C','J','D') ";

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "OSTO") {
    //ostotilaus kyseessä, ainoa paperi joka voidaan tulostaa on itse tilaus
    $where1 .= " lasku.tila = 'O' AND lasku.tilaustyyppi != 'O' ";

    if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "HAAMU") {
    //ostotilaus kyseessä, ainoa paperi joka voidaan tulostaa on itse tilaus
    $where1 .= " lasku.tila IN ('D', 'O') and lasku.tilaustyyppi = 'O' ";

    if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "PURKU") {
    //ostolasku jolle on kohdistettu rivejä. Tälle oliolle voidaan tulostaa purkulista
    $where1 .= " lasku.tila = 'K' and lasku.vanhatunnus = 0";

    if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "VASTAANOTETUT") {

    $where1 = " lasku.tila = 'G' and alatila in ('V','X') ";

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "VASTAANOTTORAPORTTI") {
    //ostolasku jolle on kohdistettu rivejä. Tälle oliolle voidaan tulostaa Vastaanottoraportti
    $where1 .= " lasku.tila = 'K' ";

    if ($onkolaajattoimipaikat and $toim == "VASTAANOTTORAPORTTI" and $toimipaikka != 'kaikki') {
      $where2 .= " and lasku.yhtio_toimipaikka = '{$toimipaikka}' ";
    }

    if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "TARIFFI") {
    //ostolasku jolle on kohdistettu rivejä. Tälle oliolle voidaan tulostaa tariffilista
    $where1 .= " lasku.tila = 'K' and lasku.kohdistettu in ('K','X') ";

    if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "TUOTETARRA") {
    //Ostolasku, tuotetarrat. Tälle oliolle voidaan tulostaa tuotetarroja
    $where1 .= " lasku.tila = 'K' and lasku.kohdistettu in ('K','X') ";

    if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "TILAUSTUOTETARRA") {
    //Ostolasku, tuotetarrat. Tälle oliolle voidaan tulostaa tuotetarroja
    $where1 .= " lasku.tila in ('L','V','W','N')";

    if ($toimittajaid > 0) $where2 .= " and lasku.liitostunnus='$toimittajaid'";

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59' ";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "SIIRTOLISTA") {
    //ostolasku jolle on kohdistettu rivejä. Tälle oliolle voidaan tulostaa siirtolista
    $where1 .= " lasku.tila = 'G' ";

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
             and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

    if ($lahettava_varasto != '') {
      $where2 .= " and lasku.varasto = '$lahettava_varasto'";
    }
    if ($vastaanottava_varasto != '') {
      $where2 .= " and lasku.clearing = '$vastaanottava_varasto'";
    }

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "VALMISTUS") {
    //valmistuslista
    $where1 .= " lasku.tila = 'V' ";

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "LASKU" or $toim == "VIENTILASKU") {

    //myyntilasku. Tälle oliolle voidaan tulostaa laskun kopio
    $where1 .= " lasku.tila = 'U' ";

    if ($toim == "VIENTILASKU") {
      $where1 .= " and lasku.vienti != '' ";
    }

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where3 .= " and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
           and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59' ";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_tapvm) ";
  }

  if ($toim == "SAD") {
    //myyntilasku. Tälle oliolle voidaan tulostaa laskun kopio
    $where1 .= " lasku.tila = 'U' and lasku.vienti = 'K' ";

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where3 .= " and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
           and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59'";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_tapvm) ";
  }

  if (in_array($toim, array("LAHETE", "KOONTILAHETE", "PAKKALISTA", "DGD"))) {
    //myyntitilaus. Tulostetaan lähete.
    $where1 .= " lasku.tila in ('L','N','V','G') ";

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "KERAYSLISTA") {

    if ($yhtiorow['kerayserat'] == 'K' and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
      $where1 .= " lasku.tila in ('L','N','V','G') ";
    }
    else {
      //myyntitilaus. Tulostetaan lähete.
      $where1 .= " lasku.tila in ('L','N','V') and lasku.alatila not in ('F','FF') ";
    }

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "OSOITELAPPU") {
    //myyntitilaus. Tulostetaan osoitelappuja.
    $where1 .= " lasku.tila in ('L','G') ";

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "VIENTIERITTELY") {
    //myyntitilaus. Tulostetaan vientieruttely.
    $where1 .= " lasku.tila = 'U' and lasku.vienti != '' ";

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where3 .= " and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
           and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59'";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_tapvm) ";
  }

  if ($toim == "PROFORMA") {
    //myyntitilaus. Tulostetaan proforma.
    $where1 .= " lasku.tila in ('L','N','V','E')";

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "TILAUSVAHVISTUS") {
    //myyntitilaus.
    $where1 .= " lasku.tila in ('E','N','L','R','A','V')";

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "YLLAPITOSOPIMUS") {
    //myyntitilaus.
    $where1 .= " lasku.tila in ('0')";

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "TARJOUS" or $toim == "TARJOUS!!!VL" or $toim == "TARJOUS!!!BR" or $toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL" or $toim == "MYYNTISOPIMUS!!!BR" or $toim == "OSAMAKSUSOPIMUS" or $toim == "LUOVUTUSTODISTUS" or $toim == "VAKUUTUSHAKEMUS" or $toim == "REKISTERIILMOITUS") {
    // Tulostellaan venemyyntiin liittyviä osia
    $where1 .= " lasku.tila in ('L','T','N','A') ";

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA" or $toim == "HUOLTOSAATE") {
    /// Työmääräys
    $where1 .= " lasku.tila in ('L','A','N','S','T')";

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where2 .= " and lasku.tilaustyyppi in ('A')";

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "VAKADR") {
    //myyntitilaus. Tulostetaan vakadr-kopio.
    $where1 .= " lasku.tila in ('L') ";

    if (strlen($ytunnus) > 0 and substr($ytunnus, 0, 1) == '£') {
      $where2 .= $wherenimi;
    }
    elseif ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid'";
    }

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
           and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

    $joinlisa = "JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi != 'D' and tilausrivi.var != 'O')
           JOIN kerayserat on (kerayserat.yhtio = tilausrivi.yhtio and kerayserat.tilausrivi = tilausrivi.tunnus)
           JOIN tuote on (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.vakkoodi not in ('','0'))";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if ($toim == "LAVAKERAYSTARRA" or $toim == "LAVATARRA") {
    //myyntitilaus. Tulostetaan lavatarra-kopio.

    if ($toim == "LAVAKERAYSTARRA") {
      $where1 .= " lasku.tila = 'L' and lasku.alatila in ('A','B','C','D','E') ";
    }
    else {
      $where1 .= " lasku.tila = 'L' and lasku.alatila in ('B','C','D','E') ";
    }

    $where1 .= " and (lasku.kerayslista = lasku.tunnus or lasku.kerayslista = 0) ";

    if ($asiakasid > 0) {
      $where2 .= " and lasku.liitostunnus  = '$asiakasid' ";
    }

    $where3 .= " and lasku.luontiaika >='$vva-$kka-$ppa 00:00:00'
                 and lasku.luontiaika <='$vvl-$kkl-$ppl 23:59:59'";

    $joinlisa = " JOIN asiakas on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus and asiakas.kerayserat = 'H') ";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (yhtio_tila_luontiaika) ";
  }

  if (strlen($laskunro) <> 0 and strpos($laskunro, ",") !== FALSE) {
    $where2 .= " and lasku.laskunro IN ('".str_replace(",", "','", $laskunro)."') ";

    $where3 = "";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (lasno_index) ";
  }
  elseif ($laskunro <> 0) {
    $where2 .= " and lasku.laskunro = '$laskunro' ";

    $where3 = "";

    if (!isset($jarj)) $jarj = " lasku.tunnus ";
    $use = " use index (lasno_index) ";
  }

  if ($otunnus > 0) {
    //katotaan löytyykö lasku ja sen kaikki tilaukset
    $query = "SELECT l2.laskunro, l2.tapvm
              FROM lasku l1
              JOIN lasku l2 ON l1.yhtio=l2.yhtio and l2.tila='U' and l1.tapvm=l2.tapvm and l1.laskunro=l2.laskunro and l2.tunnus!='$otunnus'
              WHERE l1.tunnus = '$otunnus'
              and l1.$logistiikka_yhtiolisa
              LIMIT 1";
    $laresult = pupe_query($query);
    $larow = mysql_fetch_assoc($laresult);

    if ($larow["laskunro"] > 0 and $toim != "DGD") {
      $where2 .= " and lasku.laskunro = '$larow[laskunro]' and lasku.tapvm='$larow[tapvm]' ";

      $where3 = "";

      if (!isset($jarj)) $jarj = " lasku.tunnus ";
      $use = " use index (lasno_index) ";
    }
    else {
      $where2 .= " and lasku.tunnus = '$otunnus' ";

      $where3 = "";

      if (!isset($jarj)) $jarj = " lasku.tunnus ";
      $use = " use index (PRIMARY) ";
    }
  }

  if ($kerayseran_numero > 0) {
    //katotaan löytyykö lasku ja sen kaikki tilaukset
    $query = "SELECT group_concat(otunnus) tilaukset
              FROM kerayserat
              WHERE nro = '$kerayseran_numero'
              and kerayserat.$logistiikka_yhtiolisa";
    $laresult = pupe_query($query);
    $larow = mysql_fetch_assoc($laresult);

    if ($larow["tilaukset"] != "") {
      $kerayseran_tilaukset = $larow["tilaukset"];

      $where2 .= " and lasku.tunnus in ({$larow["tilaukset"]}) ";
      $joinlisa = " JOIN kerayserat ON (kerayserat.yhtio = lasku.yhtio and kerayserat.otunnus = lasku.tunnus) ";
    }
    else {
      $where2 .= " and lasku.tunnus = 0 ";
      $kerayseran_tilaukset = "";
    }
  }

  if (!isset($ascdesc)) $ascdesc = "desc";

  // Mihin järjestykseen laitetaan
  if ($jarj != '') {
    $jarj = "ORDER BY {$jarj} {$ascdesc}";
  }

  if ($toim != "HAAMU") {
    $where4 = " and lasku.tila != 'D' ";
  }

  if ($toim == "DGD") {
    $joinlisa = "JOIN rahtikirjat ON (rahtikirjat.yhtio = lasku.yhtio and rahtikirjat.otsikkonro=lasku.tunnus)";
  }

  if ($toim == "VASTAANOTTORAPORTTI") {
    $joinlisa = "JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.tyyppi != 'D' and tilausrivi.var != 'O')";
    $where5 = " AND tilausrivi.kpl != 0 ";
  }

  // Etsitään muutettavaa tilausta
  $query = "SELECT distinct
            lasku.tunnus,
            if (lasku.laskunro=0, '', lasku.laskunro) laskunro,
            lasku.ytunnus,
            lasku.nimi,
            lasku.nimitark,
            if (lasku.tapvm = '0000-00-00', left(lasku.luontiaika,10), lasku.tapvm) pvm,
            lasku.toimaika,
            if (kuka.nimi !='' and kuka.nimi is not null, kuka.nimi, lasku.laatija) laatija,
            lasku.summa,
            lasku.toimaika Toimitusaika,
            lasku.toimitustapa,
            lasku.tila,
            lasku.alatila,
            lasku.yhtio,
            lasku.yhtio_nimi,
            lasku.erikoisale,
            lasku.liitostunnus,
            lasku.viesti
            FROM lasku {$use}
            LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = lasku.laatija)
            {$joinlisa}
            WHERE {$where1} {$where2} {$where3} {$where5}
            and lasku.{$logistiikka_yhtiolisa}
            {$where4}
            {$jarj}
            LIMIT 300";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    if ($kukarow["extranet"] == "") {

      echo " <script type=\"text/javascript\" language=\"JavaScript\">
          <!--

          function toggleAll(toggleBox) {

            var currForm = toggleBox.form;
            var isChecked = toggleBox.checked;
            var nimi = toggleBox.name;

            for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
              if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,8) == nimi) {
                currForm.elements[elementIdx].checked = isChecked;
              }
            }
          }

          //-->
          </script>";

      echo "  <form method='post'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='tee' value='$tee'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='tila' value='monta'>
          <input type='hidden' name='ytunnus' value='$ytunnus'>
          <input type='hidden' name='asiakasid' value='$asiakasid'>
          <input type='hidden' name='toimittajaid' value='$toimittajaid'>
          <input type='hidden' name='ppa' value='$ppa'>
          <input type='hidden' name='kka' value='$kka'>
          <input type='hidden' name='vva' value='$vva'>
          <input type='hidden' name='ppl' value='$ppl'>
          <input type='hidden' name='kkl' value='$kkl'>
          <input type='hidden' name='vvl' value='$vvl'>
          <input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]'>
          <input type='hidden' name='mista' value='tulostakopio'>
          <input type='hidden' name='toimipaikka' value='{$toimipaikka}' />
          <input type='submit' value='".t("Tulosta useita kopioita")."'></form><br>";
    }
    echo "<table><tr>";

    if ($logistiikka_yhtio != '') {
      echo "<th valign='top'>", t("Yhtiö"), "</th>";
    }

    $hreffi = "{$PHP_SELF}?tee={$tee}&ppl={$ppl}&vvl={$vvl}&kkl={$kkl}&ppa={$ppa}&vva={$vva}&kka={$kka}&toim={$toim}&ytunnus={$ytunnus}&asiakasid={$asiakasid}&toimittajaid={$toimittajaid}";

    if (isset($lahettava_varasto) and $lahettava_varasto != '') {
      $hreffi .= "&lahettava_varasto={$lahettava_varasto}";
    }

    if (isset($vastaanottava_varasto) and $vastaanottava_varasto != '') {
      $hreffi .= "&vastaanottava_varasto={$vastaanottava_varasto}";
    }

    if ($ascdesc == "desc") $ascdesc = "asc";
    else $ascdesc = "desc";

    $hreffi .= "&ascdesc={$ascdesc}";

    echo "<th valign='top'>";

    if (!in_array($toim, array("VASTAANOTTORAPORTTI", "PURKU"))) {
      echo "<a href='{$hreffi}&jarj=lasku.tunnus'>", t("Tilausnro"), "</a><br>";
    }

    echo "<a href='{$hreffi}&jarj=lasku.laskunro'>", t("Laskunro"), "</a></th>";

    echo "<th valign='top'><a href='{$hreffi}&jarj=lasku.ytunnus'>", t("Ytunnus"), "</a><br><a href='{$hreffi}&jarj=lasku.nimi'>", t("Nimi"), "</a></th>";

    if ($toim == "LAVAKERAYSTARRA" or $toim == "LAVATARRA") {
      echo "<th valign='top'><a href='{$hreffi}&jarj=toimitustapa'>", t("Toimitustapa"), "</a></th>";
    }

    echo "<th valign='top'><a href='{$hreffi}&jarj=pvm'>", t("Pvm"), "</a><br><a href='{$hreffi}&jarj=lasku.toimaika'>", t("Toimaika"), "</a></th>";
    echo "<th valign='top'><a href='{$hreffi}&jarj=lasku.laatija'>", t("Laatija"), "</a></th>";

    if ($toim == "SIIRTOLISTA") {
      echo "<th valign='top'><a href='{$hreffi}&jarj=lasku.viesti'>", t("Viite"), "</a></th>";
    }

    if ($kukarow['hinnat'] == 0) {
      echo "<th valign='top'><a href='{$hreffi}&jarj=summa'>", t("Summa"), "</a></th>";
    }

    echo "<th valign='top'><a href='{$hreffi}&jarj=lasku.tila,lasku.alatila'>", t("Tyyppi"), "</a></th>";

    if ($tila == 'monta') {
      echo "<th valign='top'>".t("Tulosta")."</th>";

      echo "  <form method='post' name='tulosta' autocomplete='off'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='mista' value='tulostakopio'>
          <input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]'>
          <input type='hidden' name='tee' value='TULOSTA'>";
    }

    echo "</tr>";

    $oikmuutosite = tarkista_oikeus("muutosite.php");

    while ($row = mysql_fetch_assoc($result)) {
      echo "<tr>";

      $ero="td";
      if ($tunnus == $row['tunnus']) $ero="th";

      echo "<tr>";
      if ($logistiikka_yhtio != '') echo "<$ero valign='top'>$row[yhtio_nimi]</$ero>";

      echo "<$ero valign='top'>";

      if ($row['tila'] != "U" and !in_array($toim, array("VASTAANOTTORAPORTTI", "PURKU"))) {
        echo $row['tunnus'];
      }

      if ($row['tila'] == "U" and $oikmuutosite) {
        echo "<br><a href = '{$palvelin2}muutosite.php?tee=E&tunnus=$row[tunnus]&lopetus=$PHP_SELF////asiakasid=$asiakasid//ytunnus=$ytunnus//kka=$kka//vva=$vva//ppa=$ppa//kkl=$kkl//vvl=$vvl//ppl=$ppl//toim=$toim//tee=$tee//otunnus=$otunnus//laskunro=$laskunro//laskunroloppu=$laskunroloppu'>$row[laskunro]</a>";
      }
      else {
        echo "<br>$row[laskunro]";
      }
      echo "</$ero>";
      echo "<$ero valign='top'>", tarkistahetu($row['ytunnus']), "<br>$row[nimi]<br>$row[nimitark]</$ero>";

      if ($toim == "LAVAKERAYSTARRA" or $toim == "LAVATARRA") {
        echo "<$ero valign='top'>$row[toimitustapa]</$ero>";
      }

      echo "<$ero valign='top'>".tv1dateconv($row["pvm"])."<br>".tv1dateconv($row["toimaika"])."</$ero>";
      echo "<$ero valign='top'>$row[laatija]</$ero>";

      if ($toim == "SIIRTOLISTA") {
        echo "<{$ero} valign='top'>{$row['viesti']}</{$ero}>";
      }

      if ($kukarow['hinnat'] == 0) {
        if ($toim != "LASKU" and $toim != "VIENTILASKU" and $row["summa"] == 0) {

          if ($toim == "OSTO") {
            $kerroinlisa1 = " * if (tuotteen_toimittajat.tuotekerroin=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) ";
            $kerroinlisa2 = " LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus='$row[liitostunnus]' ";
          }
          else {
            $kerroinlisa1 = "";
            $kerroinlisa2 = "";
          }

          $query = "SELECT round(sum(tilausrivi.hinta $kerroinlisa1 * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}), 2) summa
                    FROM tilausrivi
                    $kerroinlisa2
                    WHERE tilausrivi.yhtio = '$row[yhtio]'
                    and tilausrivi.otunnus = '$row[tunnus]'
                    and (tilausrivi.var != 'O' or tilausrivi.tyyppi='O')
                    and tilausrivi.tyyppi  not in ('D','V')";
          $sumres = pupe_query($query);
          $sumrow = mysql_fetch_assoc($sumres);

          echo "<$ero valign='top' align='right'>$sumrow[summa]</$ero>";
        }
        else {
          echo "<$ero valign='top' align='right'>$row[summa]</$ero>";
        }
      }

      $laskutyyppi = $row["tila"];
      $alatila     = $row["alatila"];

      //tehdään selväkielinen tila/alatila
      if (file_exists("../inc/laskutyyppi.inc")) {
        require '../inc/laskutyyppi.inc';
      }
      elseif (file_exists("inc/laskutyyppi.inc")) {
        require 'inc/laskutyyppi.inc';
      }
      else {
        require 'laskutyyppi.inc';
      }

      echo "<$ero valign='top'>".t("$laskutyyppi")." ".t("$alatila")."</$ero>";

      if ($tila != 'monta') {
        echo "<td class='back' valign='top'>";

        if ($kukarow["extranet"] == "") {
          echo "  <form method='post'>
              <input type='hidden' name='lopetus' value='$lopetus'>
              <input type='hidden' name='tee' value='NAYTAHTML'>
              <input type='hidden' name='toim' value='$toim'>
              <input type='hidden' name='tunnus' value='$row[tunnus]'>
              <input type='hidden' name='ytunnus' value='$ytunnus'>
              <input type='hidden' name='asiakasid' value='$asiakasid'>
              <input type='hidden' name='toimittajaid' value='$toimittajaid'>
              <input type='hidden' name='kerayseran_numero' value='$kerayseran_numero'>
              <input type='hidden' name='kerayseran_tilaukset' value='$kerayseran_tilaukset'>
              <input type='hidden' name='otunnus' value='$otunnus'>
              <input type='hidden' name='laskunro' value='$laskunro'>
              <input type='hidden' name='ppa' value='$ppa'>
              <input type='hidden' name='kka' value='$kka'>
              <input type='hidden' name='vva' value='$vva'>
              <input type='hidden' name='ppl' value='$ppl'>
              <input type='hidden' name='kkl' value='$kkl'>
              <input type='hidden' name='vvl' value='$vvl'>
              <input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
              <input type='hidden' name='mista' value='tulostakopio'>
              <input type='submit' value='".t("Näytä ruudulla")."'></form>
              <br>";
        }

        echo "<form id='tulostakopioform_$row[tunnus]' name='tulostakopioform_$row[tunnus]' action='$PHP_SELF' method='post' autocomplete='off'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='kerayseran_numero' value='$kerayseran_numero'>
            <input type='hidden' name='kerayseran_tilaukset' value='$kerayseran_tilaukset'>
            <input type='hidden' name='otunnus' value='$row[tunnus]'>
            <input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='tee' value='NAYTATILAUS'>
            <input type='hidden' name='mista' value='tulostakopio'>
            <input type='submit' value='".t("Näytä pdf")."' onClick=\"js_openFormInNewWindow('tulostakopioform_$row[tunnus]', 'tulostakopio_$row[tunnus]'); return false;\"></form>";

        if ($kukarow["extranet"] == "") {
          echo "<br>
            <form method='post' autocomplete='off'>
            <input type='hidden' name='kerayseran_numero' value='$kerayseran_numero'>
            <input type='hidden' name='kerayseran_tilaukset' value='$kerayseran_tilaukset'>
            <input type='hidden' name='otunnus' value='$row[tunnus]'>
            <input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='tee' value='TULOSTA'>
            <input type='hidden' name='mista' value='tulostakopio'>
            <input type='submit' value='".t("Tulosta")."'></form>";
        }

        echo "</td>";
      }

      if ($tila == 'monta') {
        echo "<td valign='top'><input type='checkbox' name='tulostukseen[]' value='$row[tunnus]'></td>";
      }

      echo "</tr>";
    }

    if ($tila == 'monta') {
      echo "<tr><td colspan='6' class='back' align='right'>".t("Ruksaa kaikki").":</td><td class='back'><input type='checkbox' name='tulostuk' onclick='toggleAll(this)'></td></tr>";
      echo "<tr><td colspan='8' class='back'></td><td class='back'><input type='submit' value='".t("Tulosta")."'></form></td></tr>";
    }

    echo "</table>";
  }
  else {
    echo "<font class='error'>".t("Ei tilauksia")."...</font><br><br>";
  }
}

if ($tee == "TULOSTA" or $tee == 'NAYTATILAUS') {

  if (!isset($kappaleet)) $kappaleet = 0;

  //valitaan tulostin heti alkuun, jos se ei ole vielä valittu
  if ($toim == "OSTO" or $toim == "HAAMU") {
    $tulostimet[0] = 'Ostotilaus';
  }
  elseif ($toim == "PURKU") {
    $tulostimet[0] = 'Purkulista';
  }
  elseif ($toim == "VASTAANOTETUT") {
    $tulostimet[0] = 'Vastaanotetut';
  }
  elseif ($toim == "VASTAANOTTORAPORTTI") {
    $tulostimet[0] = 'Vastaanottoraportti';
  }
  elseif ($toim == "TARIFFI") {
    $tulostimet[0] = 'Tariffilista';
  }
  elseif ($toim == "LASKU" or $toim == "VIENTILASKU") {
    $tulostimet[0] = 'Lasku';
  }
  elseif ($toim == "TUOTETARRA") {
    $tulostimet[0] = 'Tuotetarrat';
  }
  elseif ($toim == "TILAUSTUOTETARRA") {
    $tulostimet[0] = 'Tilauksen tuotetarrat';
  }
  elseif ($toim == "SIIRTOLISTA") {
    $tulostimet[0] = 'Siirtolista';
  }
  elseif ($toim == "VALMISTUS") {
    $tulostimet[0] = 'Valmistus';
  }
  elseif ($toim == "SAD") {
    $tulostimet[0] = 'SAD-lomake';

    if ($yhtiorow["sad_lomake_tyyppi"] == "T") {
      $tulostimet[1] = 'SAD-lomake lisäsivu';
    }
  }
  elseif ($toim == "LAHETE" or $toim == "KOONTILAHETE") {
    $tulostimet[0] = 'Lähete';
  }
  elseif ($toim == "DGD") {
    $tulostimet[0] = 'DGD';
  }
  elseif ($toim == "PAKKALISTA") {
    $tulostimet[0] = 'Pakkalista';
  }
  elseif ($toim == "KERAYSLISTA") {
    $tulostimet[0] = 'Keräyslista';
  }
  elseif ($toim == "LAVAKERAYSTARRA" or $toim == "LAVATARRA") {
    $tulostimet[0] = 'Lavatarra';
  }
  elseif ($toim == "OSOITELAPPU") {
    $tulostimet[0] = 'Osoitelappu';
  }
  elseif ($toim == "VIENTIERITTELY") {
    $tulostimet[0] = 'Vientierittely';
  }
  elseif ($toim == "PROFORMA") {
    $tulostimet[0] = 'Lasku';
  }
  elseif ($toim == "TILAUSVAHVISTUS") {
    $tulostimet[0] = 'Tilausvahvistus';
  }
  elseif ($toim == "YLLAPITOSOPIMUS") {
    $tulostimet[0] = 'Yllapitosopimus';
  }
  elseif (($toim == "TARJOUS"  or $toim == "TARJOUS!!!VL" or $toim == "TARJOUS!!!BR")) {
    $tulostimet[0] = 'Tarjous';
  }
  elseif (($toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL" or $toim == "MYYNTISOPIMUS!!!BR")) {
    $tulostimet[0] = 'Myyntisopimus';
  }
  elseif ($toim == "OSAMAKSUSOPIMUS") {
    $tulostimet[0] = 'Osamaksusopimus';
  }
  elseif ($toim == "LUOVUTUSTODISTUS") {
    $tulostimet[0] = 'Luovutustodistus';
  }
  elseif ($toim == "VAKUUTUSHAKEMUS") {
    $tulostimet[0] = 'Vakuutushakemus';
  }
  elseif ($toim == "REKISTERIILMOITUS") {
    $tulostimet[0] = 'Rekisteröinti_ilmoitus';
  }
  elseif (($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA")) {
    $tulostimet[0] = 'Työmääräys';
  }
  elseif ($toim == "HUOLTOSAATE") {
    $tulostimet[0] = 'Huoltosaate';
  }
  elseif ($toim == "REKLAMAATIO" or $toim == "TAKUU") {
    $tulostimet[0] = 'Keräyslista';
  }
  elseif ($toim == "VAKADR") {
    $tulostimet[0] = 'VAK_ADR';
  }

  if ($kukarow['kuka'] == "admin" and $kappaleet === "POHJAT") {
    $kaikkilomakepohjat = TRUE;
    $kappaleet = 1;
  }

  if ($toim != "OSOITELAPPU" and $kappaleet > 0) {
    foreach ($tulostimet as $tulostin) {
      if ($komento[$tulostin] != 'email' and substr($komento[$tulostin], 0, 12) != 'asiakasemail') {
        $komento[$tulostin] .= " -# $kappaleet ";
      }
    }
  }

  if (isset($tulostukseen)) {
    $tilausnumero = implode(",", $tulostukseen);
  }

  if ($otunnus == '' and $laskunro == '' and $tilausnumero == '') {
    echo "<font class='error'>".t("VIRHE: Et valinnut mitään tulostettavaa")."!</font>";
    exit;
  }

  if ((!isset($komento) or count($komento) == 0) and $tee == 'TULOSTA') {
    require "inc/valitse_tulostin.inc";
  }

  //hateaan laskun kaikki tiedot
  $query = "SELECT *
            FROM lasku
            WHERE";

  if (isset($tilausnumero) and $tilausnumero != '') {
    $query .= " tunnus in ($tilausnumero) ";
  }
  else {
    if ($otunnus > 0) {
      $query .= " tunnus='$otunnus' ";
    }
    if ($laskunro > 0) {
      if ($otunnus > 0) {
        $query .= " and laskunro='$laskunro' ";
      }
      else {
        $query .= " tila='U' and laskunro='$laskunro' ";
      }
    }
  }

  $query .= " AND yhtio ='$kukarow[yhtio]'";
  $rrrresult = pupe_query($query);

  while ($laskurow = mysql_fetch_assoc($rrrresult)) {

    //  Haetaan asiakkaan kieli niin hekin ymmärtävät..
    $query = "SELECT kieli from asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus='$laskurow[liitostunnus]'";
    $kielires = pupe_query($query);
    $kielirow = mysql_fetch_assoc($kielires);
    if ($kielirow['kieli'] != '') {
      $kieli = $kielirow['kieli'];
    }

    if ($toim == "TARJOUS") {
      if ($kukarow['toimipaikka'] != $laskurow['yhtio_toimipaikka'] and $yhtiorow['myyntitilauksen_toimipaikka'] == 'A') {
        $kukarow['toimipaikka'] = $laskurow['yhtio_toimipaikka'];
        $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
      }
    }
    if ($toim == "VAKADR") {
      tulosta_vakadr_erittely($laskurow["tunnus"], $komento["VAK_ADR"], $tee);
      $tee = '';
    }

    if ($toim == "OSTO") {
      $otunnus = $laskurow["tunnus"];
      $mista = 'tulostakopio';

      // Tehdään tämä, jotta saadaan toimipaikan tiedot oikein tilaukselle
      $kukarow['toimipaikka'] = $laskurow['vanhatunnus'];
      $yhtiorow               = hae_yhtion_parametrit($kukarow["yhtio"]);

      require 'tulosta_ostotilaus.inc';
      $tee = '';
    }

    if ($toim == "HAAMU") {
      $otunnus = $laskurow["tunnus"];
      $mista = 'tulostakopio';
      require 'tulosta_ostotilaus.inc';
      $tee = '';
    }

    if ($toim == "PURKU") {
      $otunnus = $laskurow["tunnus"];
      $mista = 'tulostakopio';
      require 'tulosta_purkulista.inc';
      $tee = '';
    }

    if ($toim == "VASTAANOTETUT") {
      $otunnus = $laskurow["tunnus"];
      $mista = 'vastaanota';
      require 'tulosta_purkulista.inc';
      $tee = '';
    }

    if ($toim == "VASTAANOTTORAPORTTI") {
      $otunnus = $laskurow["tunnus"];
      require 'tulosta_vastaanottoraportti.inc';
      $tee = '';
    }

    if ($toim == "TUOTETARRA") {
      $otunnus = $laskurow["tunnus"];
      require 'tulosta_tuotetarrat.inc';
      $tee = '';
    }

    if ($toim == "TILAUSTUOTETARRA") {
      $otunnus = $laskurow["tunnus"];
      require 'tulosta_tilaustuotetarrat.inc';
      tulosta_tilaustuotetarrat($otunnus, 0, $komento["Tilauksen tuotetarrat"], $tee);
      $tee = '';
    }

    if ($toim == "TARIFFI") {
      $otunnus = $laskurow["tunnus"];
      require 'tulosta_tariffilista.inc';
      $tee = '';
    }

    if ($toim == "SAD") {
      $uusiotunnus = $laskurow["tunnus"];

      if ($yhtiorow["sad_lomake_tyyppi"] == "T" and $tee != 'NAYTATILAUS') {

        require 'tulosta_sadvientiilmo_teksti.inc';

        if ($paalomake != '') {
          lpr($paalomake, 0, $komento["SAD-lomake"]);

          echo t("SAD-lomake tulostuu")."...<br>";

          if ($lisalomake != "") {
            lpr($lisalomake, 0, $komento["SAD-lomake lisäsivu"]);

            echo t("SAD-lomakkeen lisäsivu tulostuu")."...<br>";
          }
        }
      }
      else {
        require_once 'pdflib/phppdflib.class.php';

        require 'tulosta_sadvientiilmo.inc';

        //keksitään uudelle failille joku varmasti uniikki nimi:
        list($usec, $sec) = explode(' ', microtime());
        mt_srand((float) $sec + ((float) $usec * 100000));
        $pdffilenimi = "/tmp/SAD_Lomake_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

        //kirjoitetaan pdf faili levylle..
        $fh = fopen($pdffilenimi, "w");
        if (fwrite($fh, $pdf2->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
        fclose($fh);

        // itse print komento...
        if ($komento["SAD-lomake"] == 'email') {
          $liite = $pdffilenimi;

          $kutsu = t("SAD-lomake", $kieli);

          if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
            $kutsu .= ", ".trim($laskurow["nimi"]);
          }

          require "inc/sahkoposti.inc";
        }
        elseif ($tee == 'NAYTATILAUS') {
          //Työnnetään tuo pdf vaan putkeen!
          echo file_get_contents($pdffilenimi);
        }
        elseif ($komento["SAD-lomake"] != '' and $komento["SAD-lomake"] != 'edi') {
          $line = exec($komento["SAD-lomake"]." ".$pdffilenimi);
        }

        if ($tee != 'NAYTATILAUS') {
          echo t("SAD-lomake tulostuu")."...<br>";
        }
      }

      $tee = '';
    }

    if ($toim == "VIENTIERITTELY") {
      $uusiotunnus = $laskurow["tunnus"];

      require_once 'pdflib/phppdflib.class.php';
      require 'tulosta_vientierittely.inc';

      //keksitään uudelle failille joku varmasti uniikki nimi:
      list($usec, $sec) = explode(' ', microtime());
      mt_srand((float) $sec + ((float) $usec * 100000));
      $pdffilenimi = "/tmp/Vientierittely_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

      //kirjoitetaan pdf faili levylle..
      $fh = fopen($pdffilenimi, "w");
      if (fwrite($fh, $Xpdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
      fclose($fh);

      // itse print komento...
      if ($komento["Vientierittely"] == 'email') {
        $liite = $pdffilenimi;

        $kutsu = t("Vientierittely", $kieli);

        if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
          $kutsu .= ", ".trim($laskurow["nimi"]);
        }

        require "inc/sahkoposti.inc";
      }
      elseif ($tee == 'NAYTATILAUS') {
        //Työnnetään tuo pdf vaan putkeen!
        echo file_get_contents($pdffilenimi);
      }
      elseif ($komento["Vientierittely"] != '' and $komento["Vientierittely"] != 'edi') {
        $line = exec($komento["Vientierittely"]." ".$pdffilenimi);
      }

      if ($tee != 'NAYTATILAUS') {
        echo t("Vientierittely tulostuu")."...<br>";
      }

      $tee = '';
    }

    if ($toim == "LASKU" or $toim == 'PROFORMA' or $toim == "VIENTILASKU") {

      if (@include_once "tilauskasittely/tulosta_lasku.inc");
      elseif (@include_once "tulosta_lasku.inc");
      else exit;

      if ($kaikkilomakepohjat) {

        $laskut = array();

        foreach (pupesoft_laskutyypit() as $sellaskutyyppi => $sellaskutyyppiteksti) {
          $laskut[] = array($sellaskutyyppi, "$sellaskutyyppiteksti ($sellaskutyyppi)");
        }

        foreach ($laskut as $lasku) {
          $sellaskutyyppi = $lasku[0]."##".$lasku[1];

          tulosta_lasku($laskurow["tunnus"], $kieli, $tee, $toim, $komento["Lasku"], "", "", $sellaskutyyppi);

          if ($tee != 'NAYTATILAUS') {
            echo t("Lasku tulostuu")."...<br>";
            $tee = '';
          }
        }
      }
      else {
        tulosta_lasku($laskurow["tunnus"], $kieli, $tee, $toim, $komento["Lasku"], "", "");

        if ($tee != 'NAYTATILAUS') {
          echo t("Lasku tulostuu")."...<br>";
          $tee = '';
        }
      }
    }

    $tilausvahvistus_onkin_kerayslista = '';
    $excel_lahete_hinnoilla = '';

    // Tilausvahvistus ja Tarjous ei voi olla samanaikaisesti, tutkitaan molemmat
    // Tarjoukset käsitellään excel-tulostuksen näkökulmasta kuten tilausvahvistukset
    $komento_tilausvahvistus = empty($komento['Tilausvahvistus']) ? '' : $komento['Tilausvahvistus'];
    $komento_tilausvahvistus = empty($komento['Tarjous']) ? $komento_tilausvahvistus : $komento['Tarjous'];

    $pos  = strpos($komento_tilausvahvistus, "excel_lahete_geodis_wilson");
    $pos2 = strpos($komento_tilausvahvistus, "excel_lahete_hinnoilla");

    if ($pos !== FALSE and $toim == "TILAUSVAHVISTUS") {
      $toim = "KERAYSLISTA";
      $tilausvahvistus_onkin_kerayslista = "JOO";
    }

    if ($pos2 !== FALSE and in_array($toim, array("TILAUSVAHVISTUS", "TARJOUS"))) {
      $toim = "KERAYSLISTA";

      if ($toim == "TILAUSVAHVISTUS") {
        $excel_lahete_hinnoilla = "JOO";
      }
      else {
        $excel_tarjous = "JOO";
      }
    }

    if ($toim == "TILAUSVAHVISTUS" or $toim == "YLLAPITOSOPIMUS") {

      if (isset($seltvtyyppi) and $seltvtyyppi != "") {
        // Jos alkuperäisessä TV-ssä oli JT-rivit optzione täpätty niin laitetaan se tännekin.
        if (strpos($laskurow['tilausvahvistus'], 'JT') !== FALSE) $seltvtyyppi .= "JT";

        // Jos alkuperäisessä TV-ssä oli tuotperheet yhdessä optzione täpätty niin laitetaan se tännekin.
        if (strpos($laskurow['tilausvahvistus'], 'Y') !== FALSE) $seltvtyyppi .= "Y";

        $laskurow['tilausvahvistus'] = $seltvtyyppi;
      }

      if ($kaikkilomakepohjat) {

        $tilausvahvistukset = array();

        foreach (pupesoft_tilausvahvistustyypit() as $seltvtyyppi => $seltvtyyppiteksti) {
          $tilausvahvistukset[] = array($seltvtyyppi, "$seltvtyyppiteksti ($seltvtyyppi)", "");
          $tilausvahvistukset[] = array($seltvtyyppi, "$seltvtyyppiteksti. Ei rivihintaa. ($seltvtyyppi)", "ei_rivihintaa");
        }

        foreach ($tilausvahvistukset as $tilva) {
          $laskurow['tilausvahvistus'] = $tilva[0];
          $laskurow['lahetepohjateksti'] = $tilva[1];
          $naytetaanko_rivihinta = $tilva[2];

          $params_tilausvahvistus = array(
            'tee'            => $tee,
            'toim'            => $toim,
            'kieli'            => $kieli,
            'komento'          => $komento,
            'laskurow'          => $laskurow,
            'naytetaanko_rivihinta'    => $naytetaanko_rivihinta,
            'extranet_tilausvahvistus'  => $extranet_tilausvahvistus,
          );

          laheta_tilausvahvistus($params_tilausvahvistus);
        }
      }
      else {
        $params_tilausvahvistus = array(
          'tee'            => $tee,
          'toim'            => $toim,
          'kieli'            => $kieli,
          'komento'          => $komento,
          'laskurow'          => $laskurow,
          'naytetaanko_rivihinta'    => $naytetaanko_rivihinta,
          'extranet_tilausvahvistus'  => $extranet_tilausvahvistus,
        );

        laheta_tilausvahvistus($params_tilausvahvistus);
      }

      $tee = '';
    }

    if ($toim == "TARJOUS" or $toim == "TARJOUS!!!VL" or $toim == "TARJOUS!!!BR") {
      $otunnus = $laskurow["tunnus"];
      list ($toimalku, $hinnat) = explode("!!!", $toim);

      require_once "tulosta_tarjous.inc";

      tulosta_tarjous($otunnus, $komento["Tarjous"], $kieli, $tee, $hinnat,
        $verolliset_verottomat_hinnat, $naytetaanko_rivihinta, $naytetaanko_tuoteno,
        $liita_tuotetiedot, $naytetaanko_yhteissummarivi);

      $tee = '';
    }

    if ($toim == "MYYNTISOPIMUS" or $toim == "MYYNTISOPIMUS!!!VL" or $toim == "MYYNTISOPIMUS!!!BR") {

      $otunnus = $laskurow["tunnus"];
      list ($toimalku, $hinnat) = explode("!!!", $toim);

      require_once "tulosta_myyntisopimus.inc";

      tulosta_myyntisopimus($otunnus, $komento["Myyntisopimus"], $kieli, $tee, $hinnat);

      $tee = '';
    }

    if ($toim == "OSAMAKSUSOPIMUS") {

      $otunnus = $laskurow["tunnus"];

      require_once "tulosta_osamaksusoppari.inc";

      tulosta_osamaksusoppari($otunnus, $komento["Osamaksusopimus"], $kieli, $tee);

      $tee = '';
    }

    if ($toim == "LUOVUTUSTODISTUS") {

      $otunnus = $laskurow["tunnus"];

      require_once "tulosta_luovutustodistus.inc";

      tulosta_luovutustodistus($otunnus, $komento["Luovutustodistus"], $kieli, $tee);

      $tee = '';
    }

    if ($toim == "VAKUUTUSHAKEMUS") {

      $otunnus = $laskurow["tunnus"];

      require_once "tulosta_vakuutushakemus.inc";

      tulosta_vakuutushakemus($otunnus, $komento["Vakuutushakemus"], $kieli, $tee);

      $tee = '';
    }

    if ($toim == "REKISTERIILMOITUS") {

      $otunnus = $laskurow["tunnus"];

      require_once "tulosta_rekisteriilmoitus.inc";

      tulosta_rekisteriilmoitus($otunnus, $komento["Rekisteröinti_ilmoitus"], $kieli, $tee);

      $tee = '';
    }

    if ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYS_ASENTAJA") {

      require_once "tyomaarays/tulosta_tyomaarays.inc";

      if ($tyomtyyppi == 'Z') {
        $kappaleet = $kappaleet == 0 ? 1 : $kappaleet;
        tulosta_tyomaaraystarra_zebra($laskurow, $komento["Työmääräys"], $kappaleet);
      }
      else {

        //Tehdään joini
        $query = "SELECT tyomaarays.*, lasku.*
                  FROM lasku
                  LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
                  WHERE lasku.yhtio = '$kukarow[yhtio]'
                  and lasku.tunnus  = '$laskurow[tunnus]'";
        $result = pupe_query($query);
        $laskurow = mysql_fetch_assoc($result);

        //haetaan asiakkaan tiedot
        $query = "SELECT luokka, puhelin, if (asiakasnro!='', asiakasnro, ytunnus) asiakasnro, asiakasnro as asiakasnro_aito
                  FROM asiakas
                  WHERE tunnus = '$laskurow[liitostunnus]'
                  and yhtio    = '$kukarow[yhtio]'";
        $result = pupe_query($query);
        $asrow = mysql_fetch_assoc($result);

        $sorttauskentta = generoi_sorttauskentta($yhtiorow["tyomaarayksen_jarjestys"]);
        $order_sorttaus = $yhtiorow["tyomaarayksen_jarjestys_suunta"];

        if ($yhtiorow["tyomaarayksen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
        else $pjat_sortlisa = "";

        //työmääräyksen rivit
        $query = "SELECT tilausrivi.*,
                  round(tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1), '$yhtiorow[hintapyoristys]') hinta,
                  round(tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}, $yhtiorow[hintapyoristys]) rivihinta_verollinen,
                  round(tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
                  $sorttauskentta,
                  if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
                  if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
                  tuote.sarjanumeroseuranta
                  FROM tilausrivi
                  JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
                  JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
                  WHERE tilausrivi.otunnus  = '{$laskurow["tunnus"]}'
                  and tilausrivi.yhtio      = '$kukarow[yhtio]'
                  and tilausrivi.tyyppi    != 'D'
                  and tilausrivi.var       != 'O'
                  ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
        $result = pupe_query($query);

        //generoidaan rivinumerot
        $rivinumerot = array();

        $kal = 1;

        while ($row = mysql_fetch_assoc($result)) {
          $rivinumerot[$row["tunnus"]] = $kal;
          $kal++;
        }

        mysql_data_seek($result, 0);

        if ($toim == "SIIRTOTYOMAARAYS") {
          $tyyppi = "SISAINEN";
        }
        elseif ((isset($tyomtyyppi) and $tyomtyyppi == "O") or $kukarow['hinnat'] != 0) {
          $tyyppi = "O";
        }
        elseif (isset($tyomtyyppi) and $tyomtyyppi == "P") {
          $tyyppi = "P";
        }
        elseif (isset($tyomtyyppi) and $tyomtyyppi == "A") {
          $tyyppi = "";
        }
        elseif (isset($tyomtyyppi) and $tyomtyyppi == "Q") {
          $tyyppi = "Q";
        }
        else {
          $tyyppi = $yhtiorow["tyomaaraystyyppi"];
        }

        $params_tyomaarays = array(
          "asrow"           => $asrow,
          "boldi"           => $boldi,
          "edtuotetyyppi"   => "",
          "iso"             => $iso,
          "kala"            => 0,
          "kieli"           => $kieli,
          "komento"      => $komento["Työmääräys"],
          "laskurow"        => $laskurow,
          "lineparam"       => $lineparam,
          "norm"            => $norm,
          "page"            => NULL,
          "pdf"             => NULL,
          "perheid"         => 0,
          "perheid2"        => 0,
          "pieni"           => $pieni,
          "pieni_boldi"     => $pieni_boldi,
          "rectparam"       => $rectparam,
          "returnvalue"     => 0,
          "rivinkorkeus"    => $rivinkorkeus,
          "rivinumerot"     => $rivinumerot,
          "row"             => NULL,
          "sivu"            => 1,
          "tee"             => $tee,
          "thispage"      => NULL,
          "toim"            => $toim,
          "tots"        => 0,
          "tyyppi"          => $tyyppi, );

        // Aloitellaan lomakkeen teko
        $params_tyomaarays = tyomaarays_alku($params_tyomaarays);

        if ($yhtiorow["tyomaarayksen_palvelutjatuottet"] == "") {
          // Ekan sivun otsikot
          $params_tyomaarays['kala'] -= $params_tyomaarays['rivinkorkeus']*3;
          $params_tyomaarays = tyomaarays_rivi_otsikot($params_tyomaarays);
        }

        while ($row = mysql_fetch_assoc($result)) {
          $params_tyomaarays["row"] = $row;
          $params_tyomaarays = tyomaarays_rivi($params_tyomaarays);
        }

        if ($yhtiorow['tyomaarays_tulostus_lisarivit'] == 'L') {
          $params_tyomaarays["tots"] = 1;
          $params_tyomaarays = tyomaarays_loppu_lisarivit($params_tyomaarays);
        }
        else {
          $params_tyomaarays["tots"] = 1;
          $params_tyomaarays = tyomaarays_loppu($params_tyomaarays);
        }

        //tulostetaan sivu
        tyomaarays_print_pdf($params_tyomaarays);
      }

      $tee = '';
    }

    if ($toim == "HUOLTOSAATE") {

      require_once "tyomaarays/tulosta_huoltosaate.inc";

      //Tehdään joini
      $query = "SELECT tyomaarays.*, lasku.*
                FROM lasku
                LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
                WHERE lasku.yhtio = '$kukarow[yhtio]'
                and lasku.tunnus  = '$laskurow[tunnus]'";
      $result = pupe_query($query);
      $laskurow = mysql_fetch_assoc($result);

      $sorttauskentta = generoi_sorttauskentta($yhtiorow["tyomaarayksen_jarjestys"]);
      $order_sorttaus = $yhtiorow["tyomaarayksen_jarjestys_suunta"];

      if ($yhtiorow["tyomaarayksen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
      else $pjat_sortlisa = "";

      // Huoltosaatteen rivit
      $query = "SELECT tilausrivi.*,
                round(tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}, $yhtiorow[hintapyoristys]) rivihinta_verollinen,
                round(tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
                $sorttauskentta,
                if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
                if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
                tuote.sarjanumeroseuranta
                FROM tilausrivi
                JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
                JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
                WHERE tilausrivi.otunnus  = '{$laskurow["tunnus"]}'
                and tilausrivi.yhtio      = '$kukarow[yhtio]'
                and tilausrivi.tyyppi    != 'D'
                and tilausrivi.var       != 'O'
                ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
      $result = pupe_query($query);

      $params_huoltosaate = array(
        "_hsiso"       => $_hsiso,
        "kala"         => 0,
        "kieli"        => $kieli,
        "komento"      => $komento["Huoltosaate"],
        "laskurow"     => $laskurow,
        "_hsnorm"      => $_hsnorm,
        "page"         => NULL,
        "pdf"          => NULL,
        "_hspieni"     => $_hspieni,
        "_hsrectparam" => $_hsrectparam,
        "returnvalue"  => 0,
        "row"          => NULL,
        "sivu"         => 1,
        "tee"          => $tee,
        "thispage"     => NULL,
        "yhteensa"     => 0);

      // Aloitellaan lomakkeen teko
      $params_huoltosaate = huoltosaate_alku($params_huoltosaate);

      // Ekan sivun otsikot
      $params_huoltosaate = huoltosaate_rivi_otsikot($params_huoltosaate);

      mysql_data_seek($result, 0);

      $yhteensa = 0;

      while ($row = mysql_fetch_assoc($result)) {
        $yhteensa += $row['rivihinta_verollinen'];
        $params_huoltosaate["row"] = $row;
        $params_huoltosaate = huoltosaate_rivi($params_huoltosaate);
      }

      $params_huoltosaate["yhteensa"] = $yhteensa;
      $params_huoltosaate = huoltosaate_loppu($params_huoltosaate);

      //tulostetaan sivu
      huoltosaate_print_pdf($params_huoltosaate);

      $tee = '';
    }

    if ($toim == "VALMISTUS") {

      // katotaan miten halutaan sortattavan
      // haetaan asiakkaan tietojen takaa sorttaustiedot
      $order_sorttaus = '';

      $asiakas_apu_query = "SELECT lahetteen_jarjestys, lahetteen_jarjestys_suunta, email
                            FROM asiakas
                            WHERE yhtio = '$kukarow[yhtio]'
                            and tunnus  = '$laskurow[liitostunnus]'";
      $asiakas_apu_res = pupe_query($asiakas_apu_query);

      if (mysql_num_rows($asiakas_apu_res) == 1) {
        $asiakas_apu_row = mysql_fetch_array($asiakas_apu_res);
        $sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["lahetteen_jarjestys"] != "" ? $asiakas_apu_row["lahetteen_jarjestys"] : $yhtiorow["lahetteen_jarjestys"]);
        $order_sorttaus = $asiakas_apu_row["lahetteen_jarjestys_suunta"] != "" ? $asiakas_apu_row["lahetteen_jarjestys_suunta"] : $yhtiorow["lahetteen_jarjestys_suunta"];
      }
      else {
        $sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);
        $order_sorttaus = $yhtiorow["lahetteen_jarjestys_suunta"];
      }

      if ($yhtiorow["lahetteen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
      else $pjat_sortlisa = "";

      $query = "SELECT tilausrivi.*,
                $sorttauskentta,
                if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
                tuote.valmistuslinja,
                tuote.sarjanumeroseuranta
                FROM tilausrivi use index (yhtio_otunnus)
                JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
                WHERE tilausrivi.otunnus  = '$laskurow[tunnus]'
                and tilausrivi.yhtio      = '$kukarow[yhtio]'
                and tilausrivi.var        in ('','H')
                and tilausrivi.tyyppi    != 'D'
                ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, if(tilausrivi.tyyppi = 'V', '2', '1'), tilausrivi.tunnus";
      $result = pupe_query($query);

      require_once "tulosta_valmistus.inc";

      $tilausnumeroita = $laskurow["tunnus"];

      //generoidaan rivinumerot
      $rivinumerot = array();

      $kal = 1;

      while ($row = mysql_fetch_assoc($result)) {
        $rivinumerot[$row["tunnus"]] = $kal;
        $kal++;
      }

      mysql_data_seek($result, 0);

      unset($pdf);
      unset($page);

      $sivu  = 1;
      $paino = 0;

      // Aloitellaan lähetteen teko
      $page[$sivu] = alku_valm();

      while ($row = mysql_fetch_assoc($result)) {
        $row['ed_tyyppi'] = $_rivityyppi;

        rivi_valm($page[$sivu]);

        $_rivityyppi = $row['tyyppi'];
      }

      loppu_valm($page[$sivu], 1);
      print_pdf_valm($komento["Valmistus"]);

      $tee = '';
    }

    if ($toim == "LAHETE" or $toim == "KOONTILAHETE" or $toim == "PAKKALISTA") {
      if ($toim == "KOONTILAHETE") {

        $query = "SELECT lasku.*,
                  asiakas.keraysvahvistus_lahetys,
                  if(asiakas.keraysvahvistus_email != '', asiakas.keraysvahvistus_email, asiakas.email) email
                  FROM lasku
                  LEFT JOIN asiakas ON (lasku.yhtio = asiakas.yhtio AND lasku.liitostunnus = asiakas.tunnus)
                  WHERE lasku.tunnus IN ({$laskurow['tunnus']})
                  and lasku.yhtio    = '{$kukarow['yhtio']}'";
        $alk_til_res = pupe_query($query);
        $laskurow_chk = mysql_fetch_assoc($alk_til_res);

        list($komento, $koontilahete, $koontilahete_tilausrivit) = koontilahete_check($laskurow_chk, $komento);

        if ($komento != "" and $koontilahete == 0) {
          $koontilahete = $laskurow['tunnus'];
          $koontilahete_tilausrivit = 0;
        }
        elseif ($komento != "" and $koontilahete != 0) {
          $laskurow['email'] = $laskurow_chk['email'];
        }
      }
      else {
        $koontilahete = 0;
        $koontilahete_tilausrivit = 0;
      }

      if ($kaikkilomakepohjat) {

        $lahetteet = array();

        foreach (pupesoft_lahetetyypit() as $sellahetetyyppi => $sellahetetyyppiteksti) {
          $lahetteet[] = array($sellahetetyyppi, "$sellahetetyyppiteksti ($sellahetetyyppi)", "");
        }

        foreach ($lahetteet as $lahete) {
          $sellahetetyyppi = $lahete[0];
          $laskurow['lahetepohjateksti'] = $lahete[1];
          $naytetaanko_rivihinta = $lahete[2];

          $params = array(
            'laskurow'                 => $laskurow,
            'sellahetetyyppi'          => $sellahetetyyppi,
            'extranet_tilausvahvistus' => $extranet_tilausvahvistus,
            'naytetaanko_rivihinta'    => $naytetaanko_rivihinta,
            'tee'                      => $tee,
            'toim'                     => $toim,
            'komento'                  => $komento,
            'lahetekpl'                => "",
            'kieli'                    => $kieli,
            'koontilahete'             => $koontilahete,
            'koontilahete_tilausrivit' => $koontilahete_tilausrivit,
          );

          pupesoft_tulosta_lahete($params);
        }
      }
      else {
        $params = array(
          'laskurow'                 => $laskurow,
          'sellahetetyyppi'          => $sellahetetyyppi,
          'extranet_tilausvahvistus' => $extranet_tilausvahvistus,
          'naytetaanko_rivihinta'    => $naytetaanko_rivihinta,
          'tee'                      => $tee,
          'toim'                     => $toim,
          'komento'                  => $komento,
          'lahetekpl'                => "",
          'kieli'                    => $kieli,
          'koontilahete'             => $koontilahete,
          'koontilahete_tilausrivit' => $koontilahete_tilausrivit,
        );

        pupesoft_tulosta_lahete($params);
      }

      $tee = '';
    }

    if ($toim == "DGD") {

      $query = "SELECT group_concat(DISTINCT otsikkonro) AS tunnukset
                FROM rahtikirjat
                WHERE yhtio                         = '{$kukarow["yhtio"]}'
                AND rahtikirjanro                   = (SELECT rahtikirjanro
                                     FROM rahtikirjat
                                     WHERE yhtio    = '{$kukarow["yhtio"]}'
                                     AND otsikkonro = '{$laskurow["tunnus"]}')";
      $rahti_result = pupe_query($query);

      $tunnukset = mysql_fetch_assoc($rahti_result);
      $tunnukset = $tunnukset["tunnukset"];

      require_once "tilauskasittely/tulosta_dgd.inc";

      $params_dgd = array(
        'kieli'      => 'en',
        'laskurow'    => $laskurow,
        'page'      => NULL,
        'pdf'      => NULL,
        'row'      => NULL,
        'sivu'      => 0,
        'tee'      => $tee,
        'toim'      => $toim,
        'norm'      => $norm,
        'otunnukset' => $tunnukset,
      );

      // Aloitellaan DGD:n teko
      $params_dgd = alku_dgd($params_dgd);
      $params_dgd = rivi_dgd($params_dgd);
      $params_dgd = loppu_dgd($params_dgd);

      //tulostetaan sivu
      $params_dgd["komento"] = $komento["DGD"];
      print_pdf_dgd($params_dgd);

      $tee = '';
    }

    if ($toim == "KERAYSLISTA" or $toim == "SIIRTOLISTA" or $toim == "REKLAMAATIO" or $toim == "TAKUU") {

      require_once "tulosta_lahete_kerayslista.inc";

      $otunnus = $laskurow["tunnus"];
      $tilausnumeroita = $otunnus;

      if ($laskurow['kerayslista'] > 0) {
        //haetaan kaikki tälle klöntille kuuluvat otsikot
        $query = "SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
                  FROM lasku
                  WHERE yhtio     = '{$kukarow['yhtio']}'
                  AND tila        = '{$laskurow['tila']}'
                  AND kerayslista = '{$laskurow['kerayslista']}'
                  HAVING tunnukset IS NOT NULL";
        $toimresult = pupe_query($query);

        //jos rivejä löytyy niin tiedetään, että tämä on keräysklöntti
        if (mysql_num_rows($toimresult) > 0) {
          $toimrow = mysql_fetch_assoc($toimresult);
          $tilausnumeroita = $toimrow["tunnukset"];
        }
      }

      //haetaan asiakkaan tiedot
      $query = "SELECT luokka,
                puhelin,
                if (asiakasnro!='', asiakasnro, ytunnus) asiakasnro,
                asiakasnro as asiakasnro_aito,
                kerayserat,
                kieli
                FROM asiakas
                WHERE tunnus = '$laskurow[liitostunnus]'
                and yhtio    = '$kukarow[yhtio]'";
      $result = pupe_query($query);
      $asrow = mysql_fetch_assoc($result);

      $query = "SELECT ulkoinen_jarjestelma
                FROM varastopaikat
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$laskurow[varasto]'";
      $result = pupe_query($query);
      $varastorow = mysql_fetch_assoc($result);

      $select_lisa = "";
      $where_lisa = "";
      $lisa1 = "";
      $pjat_sortlisa = "";
      $kerayslistatyyppi = "";

      if ($excel_lahete_hinnoilla != '') {
        $kerayslistatyyppi = "EXCEL3";
      }
      elseif ($varastorow["ulkoinen_jarjestelma"] == "G" or $tilausvahvistus_onkin_kerayslista != "") {
        $kerayslistatyyppi = "EXCEL2";
      }
      elseif ($varastorow["ulkoinen_jarjestelma"] == "C") {
        $kerayslistatyyppi = "EXCEL1";
      }
      elseif ($excel_tarjous != '') {
        $kerayslistatyyppi = "EXCEL3";
      }
      elseif ($asrow['kerayserat'] == 'H') {
        $kerayslistatyyppi = "LAVAKERAYS";
      }

      // keräyslistalle ei oletuksena tulosteta saldottomia tuotteita
      if ($yhtiorow["kerataanko_saldottomat"] == '') {
        $lisa1 = " and tuote.ei_saldoa = '' ";
      }

      if ($laskurow["tila"] == "V") {
        $sorttauskentta = generoi_sorttauskentta($yhtiorow["valmistus_kerayslistan_jarjestys"]);
        $order_sorttaus = $yhtiorow["valmistus_kerayslistan_jarjestys_suunta"];

        if ($yhtiorow["valmistus_kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

        // Summataan rivit yhteen (HUOM: unohdetaan kaikki perheet!)
        if ($yhtiorow["valmistus_kerayslistan_jarjestys"] == "S") {
          $select_lisa = "sum(tilausrivi.kpl) kpl, sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, '' perheid, '' perheid2, ";
          $where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
        }
      }
      else {
        $sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
        $order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

        if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

        // Summataan rivit yhteen (HUOM: unohdetaan kaikki perheet!)
        if ($yhtiorow["kerayslistan_jarjestys"] == "S") {
          $select_lisa = "sum(tilausrivi.kpl) kpl, sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, '' perheid, '' perheid2, ";
          $where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
        }
      }
      $query_ale_lisa = generoi_alekentta('M');

      $ale_query_select_lisa = generoi_alekentta_select('erikseen', 'M');
      $ale_query_select_lisa_y = generoi_alekentta_select('yhteen', 'M');

      // Asiakaaalla lavakerays
      if ($asrow['kerayserat'] == 'H') {
        require "inc/lavakeraysparametrit.inc";

        $select_lisa .= $lavakeraysparam;
        $pjat_sortlisa = "tilausrivin_lisatiedot.alunperin_puute,lavasort,";
      }

      // keräyslistan rivit
      if (isset($kerayseran_tilaukset) and trim($kerayseran_tilaukset) != '' and ($yhtiorow['kerayserat'] == 'K' or $yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $asrow['kerayserat'] == 'A'))) {
        $query = "SELECT tilausrivi.*,
                  $ale_query_select_lisa_y aleyhteensa,
                  round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
                  round(tilausrivi.hinta * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') kplhinta,
                  tuote.sarjanumeroseuranta,
                  kerayserat.kpl as tilkpl,
                  kerayserat.kpl as varattu,
                  kerayserat.tunnus as ker_tunnus,
                  kerayserat.pakkaus,
                  kerayserat.pakkausnro,
                  {$sorttauskentta}
                  FROM kerayserat
                  JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi AND tilausrivi.tyyppi != 'D')
                  JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno {$lisa1})
                  WHERE kerayserat.otunnus  IN ({$kerayseran_tilaukset})
                  and tilausrivi.var       != 'O'
                  AND kerayserat.yhtio      = '{$kukarow['yhtio']}'
                  ORDER BY sorttauskentta";
      }
      else {
        $query = "SELECT tilausrivi.*,
                  $ale_query_select_lisa_y aleyhteensa,
                  round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
                  round(tilausrivi.hinta * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') kplhinta,
                  $select_lisa
                  $sorttauskentta,
                  if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
                  if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
                  tuote.sarjanumeroseuranta,
                  tuote.eankoodi,
                  abs(tilausrivin_lisatiedot.asiakkaan_positio) asiakkaan_positio
                  FROM tilausrivi
                  JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
                  LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
                  JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
                  WHERE tilausrivi.otunnus  in ($tilausnumeroita)
                  and tilausrivi.yhtio      = '$kukarow[yhtio]'
                  and tilausrivi.tyyppi    != 'D'
                  and tilausrivi.var       != 'O'
                  $lisa1
                  $where_lisa
                  ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
      }
      $riresult = pupe_query($query);

      //generoidaan rivinumerot
      $rivinumerot = array();

      $kal = 1;

      while ($row = mysql_fetch_assoc($riresult)) {
        if ($yhtiorow['kerayserat'] == 'K' and isset($kerayseran_tilaukset) and trim($kerayseran_tilaukset) != '') {
          $rivinumerot[$row["ker_tunnus"]] = $kal;
        }
        else {
          $rivinumerot[$row["tunnus"]] = $kal;
        }
        $kal++;
      }

      mysql_data_seek($riresult, 0);

      if ($toim == "SIIRTOLISTA") {
        $tyyppi = "SIIRTOLISTA";
      }
      elseif ($toim == "REKLAMAATIO") {
        $tyyppi = "REKLAMAATIO";
      }
      elseif ($toim == "TAKUU") {
        $tyyppi = "TAKUU";
      }
      else {
        $tyyppi = "";
      }

      $kerayseran_numero = (!isset($kerayseran_numero)) ? 0 : $kerayseran_numero;
      $tilausnumeroita = ($kerayseran_tilaukset != "") ? $kerayseran_tilaukset : $tilausnumeroita;

      $params_kerayslista = array(
        'asrow'             => $asrow,
        'boldi'             => $boldi,
        'iso'               => $iso,
        'iso_boldi'         => $iso_boldi,
        'kala'              => 0,
        'kieli'             => $kieli,
        'komento'           => '',
        'laskurow'          => $laskurow,
        'norm'              => $norm,
        'page'              => NULL,
        'paino'             => 0,
        'pdf'               => NULL,
        'perheid'           => 0,
        'perheid2'          => 0,
        'pieni'             => $pieni,
        'pieni_boldi'       => $pieni_boldi,
        'rectparam'         => $rectparam,
        'rivinkorkeus'      => $rivinkorkeus,
        'rivinumerot'       => $rivinumerot,
        'row'               => NULL,
        'sivu'              => 1,
        'tee'               => $tee,
        'thispage'          => NULL,
        'tilausnumeroita'   => $tilausnumeroita,
        'toim'              => $toim,
        'tots'              => 0,
        'tyyppi'            => $tyyppi,
        'kerayseran_numero' => $kerayseran_numero,
        'kerayslistatyyppi' => $kerayslistatyyppi,);

      // Aloitellaan keräyslistan teko
      $params_kerayslista = alku_kerayslista($params_kerayslista);

      while ($row = mysql_fetch_assoc($riresult)) {
        $params_kerayslista["row"] = $row;
        $params_kerayslista = rivi_kerayslista($params_kerayslista);
      }

      $params_kerayslista["tots"] = 1;
      $params_kerayslista = loppu_kerayslista($params_kerayslista);

      if ($toim == "SIIRTOLISTA" and isset($komento["Siirtolista"])) {
        $params_kerayslista["komento"] = $komento["Siirtolista"];
      }
      elseif (isset($komento["Keräyslista"])) {
        $params_kerayslista["komento"] = $komento["Keräyslista"];
      }

      if ($tilausvahvistus_onkin_kerayslista != '') {
        $params_kerayslista['uusiotsikko'] = "Lähete";
      }
      elseif ($excel_lahete_hinnoilla != '') {
        $params_kerayslista['uusiotsikko'] = "Tilausvahvistus";
      }
      elseif ($excel_tarjous != '') {
        $params_kerayslista['uusiotsikko'] = "Tarjous";
      }

      //tulostetaan sivu
      print_pdf_kerayslista($params_kerayslista);

      $tee = '';
    }

    if ($toim == "LAVAKERAYSTARRA" or $toim == "LAVATARRA") {

      require "inc/lavakeraysparametrit.inc";

      if ($toim == "LAVAKERAYSTARRA") {
        require_once "inc/tulosta_lavakeraystarrat_tec.inc";
      }
      else {
        require_once "lavatarra_pdf.inc";
      }

      $otunnus = $laskurow["tunnus"];
      $tilausnumeroita = $otunnus;

      if ($laskurow['kerayslista'] > 0) {
        //haetaan kaikki tälle klöntille kuuluvat otsikot
        $query = "SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
                  FROM lasku
                  WHERE yhtio     = '{$kukarow['yhtio']}'
                  AND tila        = '{$laskurow['tila']}'
                  AND kerayslista = '{$laskurow['kerayslista']}'
                  HAVING tunnukset IS NOT NULL";
        $toimresult = pupe_query($query);

        //jos rivejä löytyy niin tiedetään, että tämä on keräysklöntti
        if (mysql_num_rows($toimresult) > 0) {
          $toimrow = mysql_fetch_assoc($toimresult);
          $tilausnumeroita = $toimrow["tunnukset"];
        }
      }

      $lisa1 = "";
      $select_lisa = $lavakeraysparam;
      $pjat_sortlisa = "tilausrivin_lisatiedot.alunperin_puute,lavasort,";
      $where_lisa = "";

      // keräyslistalle ei oletuksena tulosteta saldottomia tuotteita
      if ($yhtiorow["kerataanko_saldottomat"] == '') {
        $lisa1 = " and tuote.ei_saldoa = '' ";
      }

      $sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
      $order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

      if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

      // Summataan rivit yhteen (HUOM: unohdetaan kaikki perheet!)
      if ($yhtiorow["kerayslistan_jarjestys"] == "S") {
        $select_lisa = "sum(tilausrivi.kpl) kpl, sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, '' perheid, '' perheid2, ";
        $where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
      }

      // ignoorataan rivien haussa pakkauksien kulutuotteet
      $query = "SELECT group_concat(pakkausveloitus_tuotenumero) pakkausveloitus_tuotenumero
                FROM pakkaus
                WHERE yhtio = '$kukarow[yhtio]'
                AND trim(pakkausveloitus_tuotenumero) != ''";
      $pakvel_result = pupe_query($query);
      $pakvel_row = mysql_fetch_assoc($pakvel_result);

      if (!empty($pakvel_row['pakkausveloitus_tuotenumero'])) {
        $lisa1 .= " and tilausrivi.tuoteno not in ('".str_replace(",", "','", $pakvel_row['pakkausveloitus_tuotenumero'])."')";
      }

      // rivit
      $query = "SELECT tilausrivi.*,
                $select_lisa
                $sorttauskentta,
                if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
                tuote.myynti_era,
                tuote.mallitarkenne
                FROM tilausrivi
                JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
                LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
                JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
                WHERE tilausrivi.otunnus  in ($tilausnumeroita)
                and tilausrivi.yhtio      = '$kukarow[yhtio]'
                and tilausrivi.tyyppi    != 'D'
                and tilausrivi.var       != 'O'
                $lisa1
                $where_lisa
                ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
      $riresult = pupe_query($query);

      $lavanumero = 1;
      $lava_referenssiluku = 0;
      $lavat = array();
      $rivinumerot = array();
      $kal = 1;

      while ($row = mysql_fetch_assoc($riresult)) {
        if (empty($lavat[$lavanumero][$row['otunnus']])) {
          $lavat[$lavanumero][$row['otunnus']] = 0;
        }

        if ($lava_referenssiluku >= lavakerayskapasiteetti) {
          $lavanumero++;
          $lava_referenssiluku=0;
        }

        // myynti_era = 1 / mallitarkenne = 400 poikkeus
        if ((int) $row['myynti_era'] == 1 and (int) $row['mallitarkenne'] == 400) {
          $row['myynti_era'] = 6;
        }

        $lavat[$lavanumero][$row['otunnus']] += round(($row['varattu']+$row['kpl'])/$row['myynti_era'], 2);
        $lava_referenssiluku += ($row['tilkpl'] * $row['lavakoko']);

        $rivinumerot[$row["tunnus"]] = $kal;
        $kal++;
      }

      if ($toim == "LAVAKERAYSTARRA") {
        // Lavakeraystarrat
        tulosta_lavakeraystarrat_tec($riresult, $rivinumerot, $komento["Lavatarra"]);
      }
      else {
        $params_lavatarra = array(
          'norm'              => $norm,
          'pieni'             => $pieni,
          'pieni_boldi'       => $pieni_boldi,
          'boldi'             => $boldi,
          'iso'               => $iso,
          'iso_boldi'         => $iso_boldi,
          'rectparam'         => $rectparam,
          'komento'           => $komento["Lavatarra"],
          'toimitustapa'      => $laskurow['toimitustapa'],
          'pdf'               => NULL,
          'lavanumero'        => 0,
          'tilaukset'         => NULL,
          'tee'               => $tee,
          'thispage'          => NULL,);


        foreach ($lavat as $lava => $tilaukset) {
          ksort($tilaukset);
          $params_lavatarra['lavanumero'] = $lava;
          $params_lavatarra['tilaukset'] = $tilaukset;
          $params_lavatarra = sivu_lavatarra($params_lavatarra);
        }

        print_pdf_lavatarra($params_lavatarra);
      }
      $tee = '';
    }

    if ($toim == "OSOITELAPPU") {
      $tunnus = $laskurow["tunnus"];
      $oslapp = $komento["Osoitelappu"];
      $kappaleet = $kappaleet == 0 ? 1 : $kappaleet;
      $oslappkpl = $tee == 'NAYTATILAUS' ? 1 : $kappaleet;

      if ($oslapp != '' or $tee == 'NAYTATILAUS') {

        $query = "SELECT GROUP_CONCAT(DISTINCT if (tunnusnippu>0, concat(tunnusnippu,'/',tunnus),tunnus) ORDER BY tunnus SEPARATOR ', ') tunnukset
                  FROM lasku
                  WHERE yhtio      = '$kukarow[yhtio]'
                  and tila         = 'L'
                  and kerayslista  = '$laskurow[kerayslista]'
                  and kerayslista != 0";
        $toimresult = pupe_query($query);
        $toimrow = mysql_fetch_assoc($toimresult);

        $tilausnumeroita = $toimrow["tunnukset"];

        $query = "SELECT osoitelappu
                  FROM toimitustapa
                  WHERE yhtio = '$kukarow[yhtio]'
                  and selite  = '$laskurow[toimitustapa]'";
        $oslares = pupe_query($query);
        $oslarow = mysql_fetch_assoc($oslares);

        if ($oslarow['osoitelappu'] == 'intrade') {
          require 'osoitelappu_intrade_pdf.inc';
        }
        elseif ($oslarow['osoitelappu'] == 'osoitelappu_kesko') {
          require 'osoitelappu_kesko_pdf.inc';
        }
        elseif ($oslarow['osoitelappu'] == 'hornbach') {
          require 'osoitelappu_hornbach_pdf.inc';
        }
        elseif ($oslarow['osoitelappu'] == 'oslap_mg' and $yhtiorow['kerayserat'] == 'K') {

          // Yritetään komennon avulla löytää oikea tulostin....
          $query  = "SELECT mediatyyppi
                     FROM kirjoittimet
                     WHERE yhtio = '$kukarow[yhtio]'
                     AND komento = '".mysql_real_escape_string($oslapp)."'
                     LIMIT 1";
          $kirres = pupe_query($query);
          $kirrow = mysql_fetch_assoc($kirres);

          $oslapp_mediatyyppi = $kirrow['mediatyyppi'];

          $query = "SELECT kerayserat.otunnus, pakkaus.pakkaus, kerayserat.pakkausnro
                    FROM kerayserat
                    LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
                    WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                    AND kerayserat.otunnus = '{$laskurow['tunnus']}'
                    GROUP BY 1,2,3
                    ORDER BY kerayserat.otunnus, kerayserat.pakkausnro";
          $pak_chk_res = pupe_query($query);

          $pak_num = mysql_num_rows($pak_chk_res);

          while ($pak_chk_row = mysql_fetch_assoc($pak_chk_res)) {

            for ($i = 1; $i <= $oslappkpl; $i++) {

              $params = array(
                'tilriv' => $pak_chk_row['otunnus'],
                'komento' => $oslapp,
                'mediatyyppi' => $oslapp_mediatyyppi,
                'pakkauskoodi' => $pak_chk_row['pakkaus'],
                'montako_laatikkoa_yht' => $pak_num,
                'toim_nimi' => $laskurow['toim_nimi'],
                'toim_nimitark' => $laskurow['toim_nimitark'],
                'toim_osoite' => $laskurow['toim_osoite'],
                'toim_postino' => $laskurow['toim_postino'],
                'toim_postitp' => $laskurow['toim_postitp'],
              );

              tulosta_oslap_mg($params);
            }
          }
        }
        else {
          require "osoitelappu_pdf.inc";
        }
      }
      $tee = '';
    }

    // Siirrytään takaisin sieltä mistä tultiin
    if ($lopetus != '' and !isset($nayta_pdf)) {
      lopetus($lopetus, "META");
    }
  }
}

if ($tee != 'NAYTATILAUS') {
  if (@include "inc/footer.inc");
  elseif (@include "footer.inc");
}
