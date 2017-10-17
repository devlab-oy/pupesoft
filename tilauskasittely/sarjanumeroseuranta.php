<?php

// otetaan sis‰‰n voidaan ottaa $myyntirivitunnus tai $ostorivitunnus
// ja $from niin tiedet‰‰n mist‰ tullaan ja minne palata

if (strpos($_SERVER['SCRIPT_NAME'], "sarjanumeroseuranta.php") !== false) {
  if (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == "NAYTATILAUS") {
    $_REQUEST['nayta_pdf'] = 1;
    $nayta_pdf             = 1;
  }

  require "../inc/parametrit.inc";
}

if ($tee == "NAYTATILAUS") {
  require_once "pdflib/phppdflib.class.php";

  $viivakoodityyppi = "viivakoodi";
  $malli            = "PDF";
  $pdf              = new pdffile;
  $toim             = "SARJA";

  $pdf->set_default('margin-top', 0);
  $pdf->set_default('margin-bottom', 0);
  $pdf->set_default('margin-left', 0);
  $pdf->set_default('margin-right', 0);

  $query = "SELECT sarjanumero, tuoteno
            FROM sarjanumeroseuranta
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tunnus  IN ({$valitut_sarjat})";

  $sarja_result = pupe_query($query);

  while ($sarjarow = mysql_fetch_assoc($sarja_result)) {
    $tuoteno = $sarjarow["tuoteno"];

    require "inc/tulosta_tuotetarrat_pdf.inc";
  }

  $filename = "/tmp/sarjanumerotarra-" . md5(uniqid(mt_rand(), true)) . ".pdf";
  $file     = fopen($filename, "w");

  fwrite($file, $pdf->generate());
  fclose($file);

  echo file_get_contents($filename);

  unlink($filename);
  exit;
}

// ekotetaan javascripti‰ jotta saadaan pdf:‰t uuteen ikkunaan
js_openFormInNewWindow();

echo "<SCRIPT type='text/javascript'>
    <!--
      function sarjanumeronlisatiedot_popup(tunnus) {
        window.open('$PHP_SELF?tunnus='+tunnus+'&toiminto=sarjanumeronlisatiedot_popup', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=0,top=0,width=1000,height=700');
      }
    //-->
    </SCRIPT>";

if ($toiminto == "sarjanumeronlisatiedot_popup") {
  @include 'ajoneuvomyynti/sarjanumeron_lisatiedot_popup.inc';

  if ($kukarow["extranet"] != "") {
    $hinnat = 'MY';
  }
  else {
    $hinnat = '';
  }

  list($divitx, , , , ) = sarjanumeronlisatiedot_popup($tunnus, '', '', $hinnat, '');
  echo "$divitx";
  exit;
}

// Tarkastetaan k‰sitell‰‰nkˆ lis‰tietoja
if (table_exists("sarjanumeron_lisatiedot")) {
  $oletussarja = "JOO";
  $sarjanumeronLisatiedot = TRUE;
}
else {
  $sarjanumeronLisatiedot = FALSE;
}

if ($toiminto == "luouusitulo") {
  require 'sarjanumeroseuranta_luouusitulo.inc';
}

echo "<font class='head'>".t("Sarjanumeroseuranta")."</font><hr>";

$tunnuskentta = "";
$rivitunnus = "";
$hyvitysrivi = "";
$is_uploaded_file = is_uploaded_file($_FILES['userfile']['tmp_name']);

if ($myyntirivitunnus != "") {
  $tunnuskentta = "myyntirivitunnus";
  $rivitunnus = $myyntirivitunnus;
}

if ($ostorivitunnus != "") {
  $tunnuskentta = "ostorivitunnus";
  $rivitunnus = $ostorivitunnus;
}

if ($siirtorivitunnus != "") {
  $tunnuskentta = "siirtorivitunnus";
  $rivitunnus = $siirtorivitunnus;
}

$sarjalopetus = "";

if ($lopetus != "") {
  $sarjalopetus .= "$lopetus/SPLIT/";
}

$sarjalopetus .= "{$palvelin2}tilauskasittely/sarjanumeroseuranta.php////$tunnuskentta=$rivitunnus//from=$from//aputoim=$aputoim//muut_siirrettavat=$muut_siirrettavat//toiminto=$toiminto//sarjatunnus=$sarjatunnus//otunnus=$otunnus//sarjanumero_haku=$sarjanumero_haku//tuoteno_haku=$tuoteno_haku//nimitys_haku=$nimitys_haku//varasto_haku=$varasto_haku//ostotilaus_haku=$ostotilaus_haku//myyntitilaus_haku=$myyntitilaus_haku";

// Haetaan tilausrivin tiedot
if ($from != '' and $rivitunnus != "") {
  $query    = "SELECT tilausrivi.*,
               tuote.sarjanumeroseuranta,
               tuote.automaattinen_sarjanumerointi,
               tuote.yksikko,
               tilausrivin_lisatiedot.osto_vai_hyvitys,
               tuote.kehahin
               FROM tilausrivi use index (PRIMARY)
               JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
               LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
               WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
               and tilausrivi.tunnus  = '$rivitunnus'";
  $sarjares = pupe_query($query);
  $rivirow  = mysql_fetch_assoc($sarjares);

  $query    = "SELECT *
               FROM lasku use index (PRIMARY)
               WHERE yhtio = '$kukarow[yhtio]'
               and tunnus  = '$rivirow[otunnus]'";
  $sarjares = pupe_query($query);
  $laskurow  = mysql_fetch_assoc($sarjares);

  //Jotta jt:tkin toimisi
  $rivirow["varattu"] = $rivirow["varattu"] + $rivirow["jt"];

  // jos varattu on nollaa ja kpl ei niin otetaan kpl (esim varastoon viedyt ostotilausrivit)
  if ($rivirow["varattu"] == 0 and $rivirow["kpl"] != 0) {
    $rivirow["varattu"] = $rivirow["kpl"];
  }

  // Valmistuksesiss‰ n‰in
  if ($from == "valmistus" or $from == "VALMISTAASIAKKAALLE" or $from == "VALMISTAVARASTOON") {
    if ($rivirow["tyyppi"] == "V") {
      $valmiste_raakaine = "RAAKA-AINE";
    }
    else {
      $valmiste_raakaine = "VALMISTE";
    }
  }

  if ($rivirow["varattu"] < 0 and ($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "REKLAMAATIO" or $from == "TYOMAARAYS" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA" or $from == "KORJAA")) {
    // t‰ss‰ muutetaan myyntirivitunnus ostorivitunnukseksi jos $rivirow["varattu"] eli kappalem‰‰r‰ on negatiivinen
    $tunnuskentta     = "ostorivitunnus";
    $rivirow["varattu"] = abs($rivirow["varattu"]);
    $hyvitysrivi     = "ON";
  }
  elseif ($rivirow["varattu"] < 0 and ($from == "riviosto" or $from == "kohdista")) {
    // t‰ss‰ muutetaan ostorivitunnus myyntirivitunnukseksi jos $rivirow["varattu"] eli kappalem‰‰r‰ on negatiivinen
    $tunnuskentta     = "myyntirivitunnus";
    $rivirow["varattu"] = abs($rivirow["varattu"]);
    $ostonhyvitysrivi   = "ON";
  }
}

// Liitet‰‰n kululasku sarjanumeroon
if ($toiminto == "kululaskut") {
  require 'kululaskut.inc';
  exit;
}

// Ollaan poistamassa sarjanumero-olio kokonaan
if ($toiminto == 'POISTA') {
  $query = "DELETE
            FROM sarjanumeroseuranta
            WHERE yhtio          = '$kukarow[yhtio]'
            and tunnus           = '$sarjatunnus'
            and myyntirivitunnus = 0
            and ostorivitunnus   = 0";
  $dellares = pupe_query($query);

  $sarjanumero  = "";
  $lisatieto    = "";
  $sarjatunnus  = "";
  $toiminto    = "";
  $kaytetty    = "";

  echo "<font class='message'>".t("Sarjanumero poistettu")."!</font><br><br>";
}

// Halutaan muuttaa sarjanumeron tietoja
if ($toiminto == 'MUOKKAA') {
  if (isset($PAIVITA)) {
    $query = "SELECT tunnus, kaytetty, ostorivitunnus, myyntirivitunnus, tuoteno, perheid
              FROM sarjanumeroseuranta
              WHERE tunnus = '$sarjatunnus'";
    $sarres = pupe_query($query);
    $sarrow = mysql_fetch_assoc($sarres);

    $era_kpl = (float) str_replace(",", ".", $era_kpl);

    $sarjanumero_lisa = isset($sarjanumero) ? "sarjanumero = '{$sarjanumero}'," : "";

    if ($rivirow["sarjanumeroseuranta"] == "E" or $rivirow["sarjanumeroseuranta"] == "F" or $rivirow["sarjanumeroseuranta"] == "G") {

      $query = "UPDATE sarjanumeroseuranta
                SET lisatieto   = '$lisatieto',
                {$sarjanumero_lisa}
                kaytetty      = '$kaytetty',
                muuttaja      = '$kukarow[kuka]',
                muutospvm     = now(),
                era_kpl       = '$era_kpl',
                parasta_ennen = '$pevva-$pekka-$peppa'
                WHERE yhtio   = '$kukarow[yhtio]'
                and tunnus    = '$sarjatunnus'";
      pupe_query($query);
    }
    else {
      $query = "UPDATE sarjanumeroseuranta
                SET lisatieto   = '$lisatieto',
                {$sarjanumero_lisa}
                kaytetty      = '$kaytetty',
                muuttaja      = '$kukarow[kuka]',
                muutospvm     = now(),
                takuu_alku    = '$tvva-$tkka-$tppa',
                takuu_loppu   = '$tvvl-$tkkl-$tppl',
                era_kpl       = '$era_kpl',
                parasta_ennen = '$pevva-$pekka-$peppa'
                WHERE yhtio   = '$kukarow[yhtio]'
                and tunnus    = '$sarjatunnus'";
      pupe_query($query);

      if ($kaytetty != '') {
        $query = "UPDATE tilausrivi
                  SET alv=alv+500
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  in ($sarrow[ostorivitunnus], $sarrow[myyntirivitunnus])
                  and laskutettuaika='0000-00-00'
                  and alv     < 500";
        pupe_query($query);
      }
      else {
        $query = "UPDATE tilausrivi
                  SET alv=alv-500
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  in ($sarrow[ostorivitunnus], $sarrow[myyntirivitunnus])
                  and laskutettuaika='0000-00-00'
                  and alv     >= 500";
        pupe_query($query);
      }
    }

    if ($sarjanumeronLisatiedot) {
      $query = "SELECT *
                FROM sarjanumeron_lisatiedot
                WHERE yhtio      = '$kukarow[yhtio]'
                and liitostunnus = '$sarjarow[tunnus]'";
      $lisares = pupe_query($query);
      $lisarow = mysql_fetch_assoc($lisares);

      $query = "UPDATE tilausrivi
                SET nimitys = '{$lisarow["merkki"]} {$lisarow["malli"]}'
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$sarrow[ostorivitunnus]'";
      pupe_query($query);
    }
    elseif (trim($nimitys_nimitys) != "") {
      $query = "UPDATE tilausrivi
                SET nimitys = '$nimitys_nimitys'
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$sarrow[ostorivitunnus]'";
      pupe_query($query);
    }

    echo "<font class='message'>".t("P‰vitettiin sarjanumeron tiedot")."!</font><br><br>";

    $sarjanumero  = "";
    $lisatieto    = "";
    $sarjatunnus  = "";
    $toiminto    = "";
    $kaytetty    = "";
    $era_kpl    = "";
  }
  else {
    $query = "SELECT sarjanumeroseuranta.*,
              tuote.tuoteno,
              tuote.nimitys,
              tuote.sarjanumeroseuranta,
              tilausrivi_myynti.laskutettuaika AS myynti_laskaika
              FROM sarjanumeroseuranta
              LEFT JOIN tuote use index (tuoteno_index) ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
              LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
              WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
              and sarjanumeroseuranta.tunnus  = '$sarjatunnus'";
    $muutares = pupe_query($query);

    if (mysql_num_rows($muutares) == 1) {

      echo "<table><tr><td class='back' width='50%'>";

      $muutarow = mysql_fetch_assoc($muutares);

      echo "<form method='post' action='sarjanumeroseuranta.php' name='muokkaaformi'>
          <input type='hidden' name='muut_siirrettavat'  value='$muut_siirrettavat'>
          <input type='hidden' name='$tunnuskentta'     value='$rivitunnus'>
          <input type='hidden' name='from'         value='$from'>
          <input type='hidden' name='lopetus'       value='$lopetus'>
          <input type='hidden' name='aputoim'       value='$aputoim'>
          <input type='hidden' name='otunnus'       value='$otunnus'>
          <input type='hidden' name='toiminto'       value='MUOKKAA'>
          <input type='hidden' name='sarjatunnus'     value='$sarjatunnus'>
          <input type='hidden' name='sarjanumero_haku'   value='$sarjanumero_haku'>
          <input type='hidden' name='tuoteno_haku'     value='$tuoteno_haku'>
          <input type='hidden' name='nimitys_haku'     value='$nimitys_haku'>
          <input type='hidden' name='varasto_haku'     value='$varasto_haku'>
          <input type='hidden' name='ostotilaus_haku'   value='$ostotilaus_haku'
          <input type='hidden' name='myyntitilaus_haku'  value='$myyntitilaus_haku'>
          <input type='hidden' name='lisatieto_haku'     value='$lisatieto_haku'>";

      if ($muutarow["sarjanumeroseuranta"] == "E" or $muutarow["sarjanumeroseuranta"] == "F" or $muutarow["sarjanumeroseuranta"] == "G") {
        echo "<font class='message'>".t("Muuta er‰numerotietoja").":</font>";
      }
      else {
        echo "<font class='message'>".t("Muuta sarjanumerotietoja").":</font>";
      }

      echo "<table>";
      echo "<tr><th>".t("Tuotenumero")."</th><td>$muutarow[tuoteno] $muutarow[nimitys]</td></tr>";

      if ($muutarow["sarjanumeroseuranta"] == "E" or $muutarow["sarjanumeroseuranta"] == "F" or $muutarow["sarjanumeroseuranta"] == "G") {
        echo "<tr><th>".t("Er‰numero")."</th>";
      }
      else {
        echo "<tr><th>".t("Sarjanumero")."</th>";
      }

      $query = "SELECT max(substring(sarjanumero, position('-' IN sarjanumero)+1)+0)+1 sarjanumero
                FROM sarjanumeroseuranta
                WHERE yhtio='$kukarow[yhtio]'
                and tuoteno     = '$muutarow[tuoteno]'
                and sarjanumero like '".t("PUUTTUU")."-%'";
      $vresult = pupe_query($query);
      $vrow = mysql_fetch_assoc($vresult);

      if ($vrow["sarjanumero"] > 0) {
        $nxt = t("PUUTTUU")."-".$vrow["sarjanumero"];
      }
      else {
        $nxt = t("PUUTTUU")."-1";
      }

      $query = "SELECT max(substring(sarjanumero, position('-' IN sarjanumero)+1)+0)+1 sarjanumero
                FROM sarjanumeroseuranta
                WHERE yhtio='$kukarow[yhtio]'
                and tuoteno     = '$muutarow[tuoteno]'
                and sarjanumero like '".t("EI SARJANUMEROA")."-%'";
      $vresult = pupe_query($query);
      $vrow = mysql_fetch_assoc($vresult);

      if ($vrow["sarjanumero"] > 0) {
        $nxt2 = t("EI SARJANUMEROA")."-".$vrow["sarjanumero"];
      }
      else {
        $nxt2 = t("EI SARJANUMEROA")."-1";
      }

      //jos ei saa muuttaa niin disabloidaan sarjanumeron muokkauskentt‰, Jos myyntirivi on
      // laskutettu niin ei muokata
      if ((strpos($_SERVER['SCRIPT_NAME'], "sarjanumeroseuranta.php") !== false or
          $PHP_SELF == "sarjanumeroseuranta.php" or
          strpos($_SERVER['SCRIPT_NAME'], "tervetuloa.php") !== false or
          $PHP_SELF == "tervetuloa.php") and
        ($muutarow["myynti_laskaika"] == "" or $muutarow["myynti_laskaika"] == "0000-00-00" or
          (substr($muutarow['sarjanumero'], 0, $viiva) == "PUUTTUU" or
            substr($muutarow['sarjanumero'], 0, $viiva) == t("PUUTTUU") or
            substr($muutarow['sarjanumero'], 0, $viiva) == t("PUUTTUU", $yhtiorow["kieli"])))
      ) {
        $disabled = "";
        $style = "";
      }
      else {
        $disabled = "disabled";
        $style = "style='display:none;'";
      }

      echo "<td>
              <input id='sarjanumero'
                     type='text'
                     size='30'
                     name='sarjanumero'
                     value='$muutarow[sarjanumero]'
                     {$disabled}>";

      echo "<span id='sarjanumeroLinkit' {$style}>";
      echo "<a onclick='document.muokkaaformi.sarjanumero.value=\"{$nxt}\";'>
              <u>".t("Sarjanumero ei tiedossa")."</u>
            </a>
            <a onclick='document.muokkaaformi.sarjanumero.value=\"{$nxt2}\";'>
              <u>".t("Ei Sarjanumeroa")."</u>
            </a>";
      echo "</span>";

      if ($muutarow["myyntirivitunnus"] > 0) {
        if ($muutarow["sarjanumeroseuranta"] == "E" or $muutarow["sarjanumeroseuranta"] == "F" or $muutarow["sarjanumeroseuranta"] == "G") {
          echo "<br><br><font class='error'>".t("HUOM: Er‰numero on liitetty tilaukseen! Tilauksen tiedot muuttuvat jos muokkaat er‰numeroa.")."</font>";
        }
        else {
          echo "<br><br><font class='error'>".t("HUOM: Sarjanumero on liitetty tilaukseen! Tilauksen tiedot muuttuvat jos muokkaat sarjanumeroa.")."</font>";
        }
      }

      echo "</td></tr>";

      if ($muutarow["sarjanumeroseuranta"] == "E" or $muutarow["sarjanumeroseuranta"] == "F" or $muutarow["sarjanumeroseuranta"] == "G") {
        if ($muutarow["era_kpl"] >= 0 and $muutarow["myyntirivitunnus"] == 0 and ($muutarow["ostorivitunnus"] == 0 or $from == "kohdista")) {
          echo "<tr><th>".t("Er‰n suuruus")."</th><td><input type='text' size='30' name='era_kpl' value='$muutarow[era_kpl]'></td></tr>";
        }
        else {
          echo "<tr><th>".t("Er‰n suuruus")."</th><td>$muutarow[era_kpl]</td></tr>";
          echo "<input type='hidden' name='era_kpl' value='$muutarow[era_kpl]'>";
        }
      }

      if ($muutarow["sarjanumeroseuranta"] == "F") {

        $pevva = substr($muutarow["parasta_ennen"], 0, 4);
        $pekka = substr($muutarow["parasta_ennen"], 5, 2);
        $peppa = substr($muutarow["parasta_ennen"], 8, 2);

        echo "<tr><th>".t("Parasta ennen")."</th><td>
          <input type='text' name='peppa' value='$peppa' size='3'>
          <input type='text' name='pekka' value='$pekka' size='3'>
          <input type='text' name='pevva' value='$pevva' size='5'></td></tr>";
      }

      if ($muutarow["sarjanumeroseuranta"] == "S" and !$sarjanumeronLisatiedot) {
        $query  = "SELECT tilausrivi.nimitys nimitys, tilausrivi.tunnus ostotunnus
                   FROM tilausrivi
                   WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                   and tilausrivi.tunnus  = '$muutarow[ostorivitunnus]'";
        $nimires = pupe_query($query);
        $nimirow = mysql_fetch_assoc($nimires);

        echo "<tr><th>".t("Nimitys")."</th><td><input type='text' name='nimitys_nimitys' value='$nimirow[nimitys]' size='45'></td>";
      }

      echo "<tr><th>".t("Lis‰tieto")."</th><td><textarea rows='4' cols='44' name='lisatieto'>$muutarow[lisatieto]</textarea></td></tr>";

      if ($muutarow["sarjanumeroseuranta"] == "S") {

        $chk = "";
        if ($muutarow["kaytetty"] == 'K') {
          $chk = "CHECKED";
        }

        echo "<tr><th>".t("K‰ytetty")."</th><td><input type='checkbox' name='kaytetty' value='K' $chk></td></tr>";

        $tvva = substr($muutarow["takuu_alku"], 0, 4);
        $tkka = substr($muutarow["takuu_alku"], 5, 2);
        $tppa = substr($muutarow["takuu_alku"], 8, 2);

        $tvvl = substr($muutarow["takuu_loppu"], 0, 4);
        $tkkl = substr($muutarow["takuu_loppu"], 5, 2);
        $tppl = substr($muutarow["takuu_loppu"], 8, 2);

        echo "<tr><th>".t("Takuu")."</th><td>
        <input type='text' name='tppa' value='$tppa' size='3'>
        <input type='text' name='tkka' value='$tkka' size='3'>
        <input type='text' name='tvva' value='$tvva' size='5'>
        - <input type='text' name='tppl' value='$tppl' size='3'>
        <input type='text' name='tkkl' value='$tkkl' size='3'>
        <input type='text' name='tvvl' value='$tvvl' size='5'></td>";
      }

      echo "<td class='back'><input type='submit' name='PAIVITA' value='".t("P‰ivit‰")."'></td>";
      echo "</tr></table>";
      echo "</form>";

      echo "</td><td class='back ptop'>";

      echo "<iframe id='liitetiedostot_iframe'
                    name='liitetiedostot_iframe'
                    class='right'
                    style='width:100%;border:0px;display:block;'
                    frameborder='0'
                    src='{$palvelin2}yllapito.php" .
        "?toim=liitetiedostot" .
        "&from=yllapito" .
        "&haku[7]=@sarjanumeroseuranta" .
        "&haku[8]=@{$muutarow["tunnus"]}" .
        "&lukitse_avaimeen={$muutarow["tunnus"]}" .
        "&lukitse_laji=sarjanumeroseuranta&ohje=off'></iFrame>";

      echo "</td></tr></table><br><br>";
    }
    else {
      echo t("Muutettava sarjanumero on kadonnut")."!!!!<br>";
    }
  }
}

// Ollaan syˆtetty uusi
if ($toiminto == 'LISAA' and (trim($sarjanumero) != '' or $is_uploaded_file === true)) {
  $sarjanumero_array = array();

  if ($is_uploaded_file === true) {
    $kasiteltava_tiedoto_path = $_FILES['userfile']['tmp_name'];
    $path_parts = pathinfo($_FILES['userfile']['name']);
    $ext = strtoupper($path_parts['extension']);

    $excelrivit = pupeFileReader($kasiteltava_tiedoto_path, $ext);

    foreach($excelrivit as $rivi) {
      $_sarjanumero = trim($rivi[0]);

      if (empty($_sarjanumero)) {
        continue;
      }

      $sarjanumero_array[] = $_sarjanumero;
    }
  }
  else {
    $sarjanumero = trim($sarjanumero);
    $sarjanumero_array[] = $sarjanumero;
  }

  $era_kpl    = (float) str_replace(",", ".", $era_kpl);
  $insok      = "OK";

  foreach ($sarjanumero_array as $sarjanumero) {
    // E = Er‰numeroseuranta. Osto-Myynti / Keskihinta-varastonarvo
    // F = Er‰numeroseuranta parasta-ennen p‰iv‰ll‰. Osto-Myynti / Keskihinta-varastonarvo
    // G = Er‰numeroseuranta. Osto-Myynti / In-Out varastonarvo
    // S = Sarjanumeroseuranta. Osto-Myynti / In-Out varastonarvo
    // T = Sarjanumeroseuranta. Myynti / Keskihinta-varastonarvo
    // V = Sarjanumeroseuranta. Osto-Myynti / Keskihinta-varastonarvo
    if ($rivirow["sarjanumeroseuranta"] == "S" or $rivirow["sarjanumeroseuranta"] == "T" or $rivirow["sarjanumeroseuranta"] == "V") {
      $query = "SELECT *
                FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
                WHERE yhtio     = '$kukarow[yhtio]'
                and sarjanumero = '$sarjanumero'
                and tuoteno     = '$rivirow[tuoteno]'
                and (ostorivitunnus = 0 or myyntirivitunnus = 0)";
    }
    else {
      if ($era_kpl <= 0) {
        $insok = "EI";
        echo "<font class='error'>".t("Er‰lle on syˆtett‰v‰ kappalem‰‰r‰")." $rivirow[tuoteno]/$sarjanumero.</font><br><br>";
      }

      if ((float) $era_kpl > $rivirow["varattu"]) {
        $insok = "EI";
        echo "<font class='error'>".t("Er‰n koko on liian suuri")." $rivirow[varattu]/$era_kpl.</font><br><br>";
      }

      if ($from == "KERAA" and (float) $era_kpl != $rivirow["varattu"]) {
        $insok = "EI";
        echo "<font class='error'>".t("Er‰n koko on oltava sama kuin rivin m‰‰r‰")." $rivirow[varattu]/$era_kpl.</font><br><br>";
      }

      if ($from == "KERAA" and $rivirow["hyllyalue"] == "") {
        // Haetaan oletuspaikka
        $query = "SELECT *
                  FROM tuotepaikat
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and tuoteno  = '$rivirow[tuoteno]'
                  and oletus  != ''";
        $result = pupe_query($query);
        $saldorow = mysql_fetch_assoc($result);

        $rivirow["hyllyalue"] = $saldorow["hyllyalue"];
        $rivirow["hyllynro"]  = $saldorow["hyllynro"];
        $rivirow["hyllyvali"] = $saldorow["hyllyvali"];
        $rivirow["hyllytaso"] = $saldorow["hyllytaso"];
      }

      if ($from == "KERAA" and $tunnuskentta == "myyntirivitunnus") {
        $tunnuskentta = "ostorivitunnus";
      }

      // Samaan ostoriviin ei voida liitt‰‰ samaa er‰numeroa useaan kertaan, mutta muuten er‰numerot eiv‰t ole uniikkeja.
      $query = "SELECT *
                FROM sarjanumeroseuranta use index (yhtio_sarjanumero)
                WHERE yhtio          = '$kukarow[yhtio]'
                and sarjanumero      = '$sarjanumero'
                and tuoteno          = '$rivirow[tuoteno]'
                and $tunnuskentta   = '$rivitunnus'
                and myyntirivitunnus = 0";
    }

    $sarjares = pupe_query($query);
    $tun = 0;

    if ($insok == "OK" and mysql_num_rows($sarjares) == 0) {

      if ($rivirow["sarjanumeroseuranta"] == "E") {
        $sarjanumero = strtoupper($sarjanumero);
      }

      //jos ollaan syˆtetty kokonaan uusi sarjanuero
      $query = "INSERT into sarjanumeroseuranta
                (yhtio, tuoteno, sarjanumero, lisatieto, $tunnuskentta, kaytetty, era_kpl, laatija, luontiaika, takuu_alku, takuu_loppu, hyllyalue, hyllynro, hyllyvali, hyllytaso, parasta_ennen)
                VALUES ('$kukarow[yhtio]','$rivirow[tuoteno]','$sarjanumero','$lisatieto','','$kaytetty','$era_kpl','$kukarow[kuka]',now(),'$tvva-$tkka-$tppa','$tvvl-$tkkl-$tppl', '$rivirow[hyllyalue]', '$rivirow[hyllynro]', '$rivirow[hyllyvali]', '$rivirow[hyllytaso]', '$pevva-$pekka-$peppa')";
      $sarjares = pupe_query($query);
      $tun = mysql_insert_id($GLOBALS["masterlink"]);

      if ($sarjanumeronLisatiedot and $oletussarja == "JOO" and ($rivirow["sarjanumeroseuranta"] == "S" or $rivirow["sarjanumeroseuranta"] == "T" or $rivirow["sarjanumeroseuranta"] == "V")) {
        $query = "SELECT *
                  FROM tuote
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tuoteno = '$rivirow[tuoteno]'";
        $tuoteres = pupe_query($query);
        $tuoterow = mysql_fetch_assoc($tuoteres);

        $query = "SELECT selitetark
                  FROM avainsana
                  WHERE yhtio      = '$kukarow[yhtio]'
                  and laji         = 'SARJANUMERON_LI'
                  and selite       = 'MERKKI'
                  and selitetark_2 = '$tuoterow[tuotemerkki]'
                  ORDER BY jarjestys, selitetark_2";
        $vresult = pupe_query($query);
        $vrow = mysql_fetch_assoc($vresult);

        $query = "INSERT INTO sarjanumeron_lisatiedot
                  SET yhtio      = '$kukarow[yhtio]',
                  liitostunnus       = '$tun',
                  laatija            = '$kukarow[kuka]',
                  luontiaika         = now(),
                  Leveys             = '$tuoterow[tuoteleveys]',
                  Pituus             = '$tuoterow[tuotepituus]',
                  Varirunko          = '$tuoterow[vari]',
                  Suurin_henkiloluku = '$tuoterow[suurin_henkiloluku]',
                  Runkotyyppi        = '$tuoterow[runkotyyppi]',
                  Materiaali         = '$tuoterow[materiaali]',
                  Koneistus          = '$tuoterow[koneistus]',
                  Tyyppi             = '$tuoterow[laitetyyppi]',
                  Kilpi              = '$tuoterow[kilpi]',
                  Sprinkleri         = '$tuoterow[sprinkleri]',
                  Teho_kw            = '$tuoterow[teho_kw]',
                  Malli              = '$tuoterow[nimitys]',
                  Merkki             = '$vrow[selitetark]'";
        $lisatietores_apu = pupe_query($query);
      }

      if ($rivirow["sarjanumeroseuranta"] == "S" or $rivirow["sarjanumeroseuranta"] == "T" or $rivirow["sarjanumeroseuranta"] == "V") {
        echo "<font class='message'>".t("Lis‰ttiin sarjanumero")." $sarjanumero.</font><br><br>";
      }
      else {
        echo "<font class='message'>".t("Lis‰ttiin er‰numero")." $sarjanumero.</font><br><br>";
      }
    }
    elseif ($insok == "OK" and mysql_num_rows($sarjares) == 1) {
      $sarjarow = mysql_fetch_assoc($sarjares);
      $tun = $sarjarow['tunnus'];

      echo "<font class='error'>".t("Sarjanumero lˆytyy jo").": $sarjanumero.</font><br><br>";
    }

    // Yritet‰‰n liitt‰‰ luotu sarjanumero t‰h‰n riviin
    if ($rivitunnus > 0 and $tun > 0) {
      if ($valitut_sarjat != "") {
        $valitut_sarjat = $valitut_sarjat.",".$tun;
      }
      else {
        $valitut_sarjat = $tun;
      }

      $sarjataan = explode(",", $valitut_sarjat);
      $sarjat    = explode(",", $valitut_sarjat);
      $formista  = "kylla";
    }
  }

  $sarjanumero  = "";
  $lisatieto    = "";
  $kaytetty    = "";
  $era_kpl    = "";
}

// Ollaan valittu joku tunnus listasta ja halutaan liitt‰‰ se tilausriviin tai poistaa se tilausrivilt‰
if ($from != '' and $rivitunnus != "" and $formista == "kylla") {

  $lisaysok = "OK";

  // Jos t‰m‰ on er‰seurantaa niin tehd‰‰n tsekit lis‰t‰‰n kaikki t‰m‰n er‰n sarjanumerot
  if (count($sarjataan) > 0 and ($rivirow["sarjanumeroseuranta"] == "E" or $rivirow["sarjanumeroseuranta"] == "F" or $rivirow["sarjanumeroseuranta"] == "G")) {
    $ktark = implode(",", $sarjataan);

    $query = "SELECT sum(abs(era_kpl)) kpl
              FROM sarjanumeroseuranta
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  in ($ktark)";
    $sarres = pupe_query($query);
    $sarrow = mysql_fetch_assoc($sarres);

    if (($from == "KERAA" or $from == "valmistus" or $from == "VALMISTAASIAKKAALLE" or $from == "VALMISTAVARASTOON") and $rivirow["varattu"] != $sarrow["kpl"]) {
      echo "<font class='error'>".t('Riviin voi liitt‰‰ vain')." $rivirow[varattu] ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[yksikko]'", "", "", "selite").".</font><br><br>";

      $lisaysok = "";
    }
    elseif ($rivirow["varattu"] < $sarrow["kpl"]) {
      echo "<font class='error'>".t('Riviin voi liitt‰‰ enint‰‰n')." $rivirow[varattu] ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[yksikko]'", "", "", "selite").".</font><br><br>";

      $lisaysok = "";
    }
  }
  // Tutkitaan koitetaanko salaa liitt‰‰ enempi kuin $rivirow["varattu"]
  elseif (count($sarjataan) > 0) {
    $ktark = implode(",", $sarjataan);

    $query = "SELECT GROUP_CONCAT(tunnus) AS tunnukset,
              COUNT(DISTINCT tunnus) AS tunnusKpl
              FROM sarjanumeroseuranta
              WHERE yhtio = '$kukarow[yhtio]'
              and $tunnuskentta = $rivitunnus
              and tunnus  not in (".$ktark.")";
    $result = pupe_query($query);
    $_vap = mysql_fetch_assoc($result);

    if ($_vap["tunnusKpl"] > 0) {

      // Jos rivin kappalem‰‰r‰ on v‰hennetty sarjanumeroiden liitt‰misen j‰lkeen
      // voi olla tarvetta ottaa kohdistuksia pois ylim‰‰r‰isilt‰ riveilt‰
      $query = "UPDATE sarjanumeroseuranta
                SET $tunnuskentta = ''
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  IN ({$_vap['tunnukset']})";
      pupe_query($query);

      $liityht = count($sarjataan) + $_vap["tunnusKpl"];

      if ($liityht > $rivirow["varattu"]) {
        echo "<font class='error'>".t('Riviin voi liitt‰‰ enint‰‰n')." $rivirow[varattu] ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[yksikko]'", "", "", "selite").".</font><br><br>";

        $lisaysok = "";
      }
    }
  }

  // Tutkitaan ettei liitet‰ sek‰ uusia ett‰ vanhoja sarjanumeroita samaan riviin
  if (count($sarjataan) > 0) {
    $ktark = implode(",", $sarjataan);

    $query = "SELECT distinct kaytetty
              FROM sarjanumeroseuranta
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  in (".$ktark.")";
    $sarres = pupe_query($query);

    if (mysql_num_rows($sarres) > 1) {
      echo "<font class='error'>".t('Riviin ei voi liitt‰‰ sek‰ k‰ytettyj‰ ett‰ uusia sarjanumeroita')."</font><br><br>";

      $lisaysok = "";
    }
  }

  // jos olemme ruksanneet v‰hemm‰n tai yht‰ paljon kuin tuotteita on rivill‰, voidaan p‰ivitt‰‰ muutokset
  if ($rivirow["varattu"] >= count($sarjataan) and $lisaysok == "OK") {
    foreach ($sarjat as $sarjatun) {
      $query = "SELECT tunnus, era_kpl, kaytetty, $tunnuskentta trivitunnus
                FROM sarjanumeroseuranta
                WHERE tunnus in ($sarjatun)";
      $sarres = pupe_query($query);
      $sarrow = mysql_fetch_assoc($sarres);

      $query = "UPDATE sarjanumeroseuranta
                set $tunnuskentta = '',
                muuttaja    = '$kukarow[kuka]',
                muutospvm   = now()
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  in ($sarjatun)";
      $sarjares = pupe_query($query);

      if ($tunnuskentta == "myyntirivitunnus" and $rivitunnus > 0) {
        // T‰‰ll‰ pit‰isi poistaa laitetaulusta myyntirivitunnus
        $spessukveri = "SELECT *
                        FROM sarjanumeroseuranta
                        WHERE tunnus = '$sarjatun'";
        $spessures = pupe_query($spessukveri);
        $spessurivi = mysql_fetch_assoc($spessures);

        $laiteupdate = "UPDATE laite
                        SET paikka = '',
                        muutospvm    = now(),
                        muuttaja     = '{$kukarow['kuka']}'
                        WHERE yhtio  = '{$kukarow['yhtio']}'
                        AND sarjanro = '{$spessurivi['sarjanumero']}'
                        AND tuoteno  = '{$spessurivi['tuoteno']}'
                        AND paikka   = '{$rivitunnus}'";
        pupe_query($laiteupdate);
      }

      if ($sarrow["kaytetty"] == 'K') {
        $query = "UPDATE tilausrivi
                  SET alv=alv-500
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$sarrow[trivitunnus]'
                  and alv     >= 500";
        $sarjares = pupe_query($query);
      }
    }
  }
  elseif (($rivirow["varattu"] < count($sarjataan) and ($rivirow["sarjanumeroseuranta"] == "S" or $rivirow["sarjanumeroseuranta"] == "T" or $rivirow["sarjanumeroseuranta"] == "V")) or (($rivirow["sarjanumeroseuranta"] == "E" or $rivirow["sarjanumeroseuranta"] == "F" or $rivirow["sarjanumeroseuranta"] == "G") and ($rivirow["varattu"] == 0 or count($sarjataan) == 0))) {
    echo "<font class='error'>".sprintf(t('Riviin voi liitt‰‰ enint‰‰n %s sarjanumeroa'), abs($rivirow["varattu"])).". ".$rivirow["varattu"]." ".count($sarjataan)."</font><br><br>";
  }

  if ((($rivirow["varattu"] >= count($sarjataan) and count($sarjataan) > 0) or(($rivirow["sarjanumeroseuranta"] == "E" or $rivirow["sarjanumeroseuranta"] == "F" or $rivirow["sarjanumeroseuranta"] == "G") and $rivirow["varattu"] > 0 and count($sarjataan) > 0)) and $lisaysok == "OK") {
    foreach ($sarjataan as $sarjatun) {
      if ($tunnuskentta == "ostorivitunnus") {
        //Hanskataan sarjanumeron varastopaikkaa
        $paikkalisa = "  ,
                hyllyalue = '$rivirow[hyllyalue]',
                hyllynro  = '$rivirow[hyllynro]',
                hyllyvali = '$rivirow[hyllyvali]',
                hyllytaso = '$rivirow[hyllytaso]'";
      }
      else {
        $paikkalisa = "";
      }

      $query = "UPDATE sarjanumeroseuranta
                SET $tunnuskentta = '$rivitunnus',
                muuttaja    = '$kukarow[kuka]',
                muutospvm   = now()
                $paikkalisa
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$sarjatun'";
      pupe_query($query);

      // Tutkitaan oliko t‰m‰ sarjanumero k‰ytettytuote?
      $query = "SELECT *, $tunnuskentta rivitunnus
                FROM sarjanumeroseuranta
                WHERE tunnus = '$sarjatun'";
      $sarres = pupe_query($query);
      $sarjarow = mysql_fetch_assoc($sarres);

      // P‰ivitet‰‰n laitetaulu kun ruksataan sarjanumero myyntiriville
      $kveri = "UPDATE laite
                SET paikka = '{$rivitunnus}',
                muutospvm    = now(),
                muuttaja     = '{$kukarow['kuka']}'
                WHERE yhtio  = '{$kukarow['yhtio']}'
                AND sarjanro = '{$sarjarow['sarjanumero']}'
                AND tuoteno  = '{$sarjarow['tuoteno']}'";
      $kverires = pupe_query($kveri);

      if (mysql_affected_rows() == 0) {
        $kveri = "INSERT INTO laite
                  SET yhtio = '{$kukarow['yhtio']}',
                  luontiaika = now(),
                  sarjanro   = '{$sarjarow['sarjanumero']}',
                  paikka     = '{$rivitunnus}',
                  tuoteno    = '{$sarjarow['tuoteno']}',
                  laatija    = '{$kukarow['kuka']}'";
        pupe_query($kveri);
      }

      if ($sarjarow["kaytetty"] == 'K') {
        $query = "UPDATE tilausrivi
                  SET alv=alv+500
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$sarjarow[rivitunnus]'
                  and alv     < 500";
        pupe_query($query);
      }

      if ($rivirow["sarjanumeroseuranta"] == "S" and $sarjanumeronLisatiedot) {
        $query = "SELECT *
                  FROM sarjanumeron_lisatiedot
                  WHERE yhtio      = '$kukarow[yhtio]'
                  and liitostunnus = '$sarjarow[tunnus]'";
        $lisares = pupe_query($query);
        $lisarow = mysql_fetch_assoc($lisares);

        $query = "UPDATE tilausrivi
                  SET nimitys = '$lisarow[Merkki] $lisarow[Malli]'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$sarjarow[rivitunnus]'";
        pupe_query($query);
      }

      if ($tunnuskentta == 'ostorivitunnus' and $from == "kohdista") {
        //Tutkitaan lˆytyykˆ JT-rivi joka m‰pp‰ytyy t‰h‰n ostoriviin
        $query = "SELECT tilausrivi.tunnus
                  FROM tilausrivin_lisatiedot
                  JOIN tilausrivi ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivi.tyyppi != 'D')
                  WHERE tilausrivi.yhtio                      = '$kukarow[yhtio]'
                  and tilausrivin_lisatiedot.tilausrivilinkki = '$rivirow[tunnus]'";
        $varastoon_result = pupe_query($query);

        while ($varastoon_row = mysql_fetch_assoc($varastoon_result)) {
          // Liitet‰‰n asiaakkaan tilausrivi t‰h‰n ostoriviin
          if ($rivirow["sarjanumeroseuranta"] == "E" or $rivirow["sarjanumeroseuranta"] == "F" or $rivirow["sarjanumeroseuranta"] == "G") {
            // Ei lis‰t‰ er‰‰, jos riville on jo valittu er‰....
            $query = "SELECT tunnus
                      FROM sarjanumeroseuranta
                      WHERE yhtio          = '$kukarow[yhtio]'
                      and tuoteno          = '$sarjarow[tuoteno]'
                      and myyntirivitunnus = '$varastoon_row[tunnus]'";
            $sarjares = pupe_query($query);

            if (mysql_num_rows($sarjares) == 0) {
              $query = "INSERT into sarjanumeroseuranta
                        SET yhtio        = '$sarjarow[yhtio]',
                        tuoteno          = '$sarjarow[tuoteno]',
                        lisatieto        = '$sarjarow[lisatieto]',
                        myyntirivitunnus = '$varastoon_row[tunnus]',
                        ostorivitunnus   = '$sarjarow[rivitunnus]',
                        kaytetty         = '$sarjarow[kaytetty]',
                        era_kpl          = '',
                        laatija          = '$kukarow[kuka]',
                        luontiaika       = now(),
                        takuu_alku       = '$sarjarow[takuu_alku]',
                        takuu_loppu      = '$sarjarow[takuu_loppu]',
                        parasta_ennen    = '$sarjarow[parasta_ennen]',
                        hyllyalue        = '$sarjarow[hyllyalue]',
                        hyllynro         = '$sarjarow[hyllynro]',
                        hyllytaso        = '$sarjarow[hyllytaso]',
                        hyllyvali        = '$sarjarow[hyllyvali]',
                        sarjanumero      = '$sarjarow[sarjanumero]'";
              $sarjares = pupe_query($query);
            }
          }
          else {
            $query = "UPDATE sarjanumeroseuranta
                      SET myyntirivitunnus = '$varastoon_row[tunnus]',
                      muuttaja             = '$kukarow[kuka]',
                      muutospvm            = now()
                      WHERE yhtio          = '$kukarow[yhtio]'
                      and tunnus           = '$sarjatun'
                      and myyntirivitunnus = 0";
            $sarjares = pupe_query($query);

          }
        }
      }

      //Tutkitaan lis‰varusteita
      if ($tunnuskentta == 'myyntirivitunnus' and $from != "riviosto" and $from != "kohdista") {
        //Hanskataan sarjanumerollisten tuotteiden lis‰varusteet
        if ($sarjatun > 0 and $rivitunnus > 0) {
          require "sarjanumeron_lisavarlisays.inc";

          $palautus = lisavarlisays($sarjatun, $rivitunnus);

          if ($palautus != "OK") {
            echo "<font class='error'>$palautus</font><br><br>";

            $query = "UPDATE sarjanumeroseuranta
                      SET $tunnuskentta='',
                      muuttaja  = '$kukarow[kuka]',
                      muutospvm = now()
                      $paikkalisa
                      WHERE yhtio='$kukarow[yhtio]'
                      and tunnus='$sarjatun'";
            $sarjares = pupe_query($query);
          }
        }
      }
    }
  }
}

// N‰ytet‰‰n koneella olevat sarjanumerot
$lisa  = "";
$lisa2 = "";

if (isset($ostotilaus_haku) and $ostotilaus_haku != "") {
  if (is_numeric($ostotilaus_haku)) {
    if ($ostotilaus_haku == 0) {
      $lisa .= " and lasku_osto.tunnus is null ";
    }
    else {
      $lisa .= " and lasku_osto.tunnus='$ostotilaus_haku' ";
    }
  }
  else {
    $lisa .= " and match (lasku_osto.nimi) against ('$ostotilaus_haku*' IN BOOLEAN MODE) ";
  }
}

if (isset($myyntitilaus_haku) and $myyntitilaus_haku != "") {
  if (is_numeric($myyntitilaus_haku)) {
    if ($myyntitilaus_haku == 0) {
      $lisa .= " and (lasku_myynti.tunnus is null or lasku_myynti.tila = 'T') and sarjanumeroseuranta.myyntirivitunnus != -1 ";
    }
    else {
      $lisa .= " and lasku_myynti.tunnus='$myyntitilaus_haku' ";
    }
  }
  else {
    $lisa .= "   and match (lasku_myynti.nimi) against ('$myyntitilaus_haku*' IN BOOLEAN MODE)
          and (lasku_myynti.tila is null or lasku_myynti.tila != 'D')
            and (lasku_osto.tila is null or lasku_osto.tila != 'D')";
  }
}

if (isset($lisatieto_haku) and $lisatieto_haku != "") {
  $lisa .= "   and sarjanumeroseuranta.lisatieto like '%$lisatieto_haku%'
        and (lasku_myynti.tila is null or lasku_myynti.tila != 'D')
          and (lasku_osto.tila is null or lasku_osto.tila != 'D')";
}

if (isset($tuoteno_haku) and $tuoteno_haku != "") {
  $lisa .= "   and sarjanumeroseuranta.tuoteno like '%$tuoteno_haku%'
        and (lasku_myynti.tila is null or lasku_myynti.tila != 'D')
          and (lasku_osto.tila is null or lasku_osto.tila != 'D') ";
}

if (isset($sarjanumero_haku) and $sarjanumero_haku != "") {
  $lisa .= " and sarjanumeroseuranta.sarjanumero like '%$sarjanumero_haku%' ";
}

if (isset($varasto_haku) and $varasto_haku != "") {
  $lisa .= "   and varastopaikat.nimitys like '%$varasto_haku%'
        and (lasku_myynti.tila is null or lasku_myynti.tila != 'D')
          and (lasku_osto.tila is null or lasku_osto.tila != 'D')";
}

if (isset($tervetuloa_haku) and $tervetuloa_haku != "") {
  $lisa .= "   and (lasku_osto.laatija='$kukarow[kuka]' or lasku_myynti.laatija='$kukarow[kuka]' or lasku_myynti.myyja='$kukarow[tunnus]')
        and (lasku_myynti.tila is null or lasku_myynti.tila != 'D')
          and (lasku_osto.tila is null or lasku_osto.tila != 'D')";
}

if (isset($nimitys_haku) and $nimitys_haku != "") {
  $lisa2 = " HAVING nimitys like '%$nimitys_haku%' ";
}

if ($rivirow["sarjanumeroseuranta"] == "E" and $from == "KERAA") {
  $lisa .= " and sarjanumeroseuranta.era_kpl > 0 ";
}

if ($lisa == "") {
  $lisa = " and sarjanumeroseuranta.myyntirivitunnus != -1
        and (lasku_myynti.tila is null or lasku_myynti.tila != 'D')
        and (lasku_osto.tila is null or lasku_osto.tila != 'D') ";

  if (isset($ostonhyvitysrivi) and $ostonhyvitysrivi != "ON") {
    $lisa .= " and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00') ";
  }
}

$query  = "SELECT sarjanumeroseuranta.*,
           if (sarjanumeroseuranta.lisatieto = '', if (tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), concat(if (tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys), '<br><i>',left(sarjanumeroseuranta.lisatieto,50),'</i>')) nimitys,
           lasku_osto.tunnus                  osto_tunnus,
           lasku_osto.nimi                    osto_nimi,
           lasku_myynti.tunnus                  myynti_tunnus,
           lasku_myynti.nimi                  myynti_nimi,
           lasku_myynti.tila                  myynti_tila,
           (tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)    ostohinta,
           tilausrivi_osto.perheid2              osto_perheid2,
           tilausrivi_osto.tunnus                osto_rivitunnus,
           tilausrivi_osto.uusiotunnus              osto_uusiotunnus,
           tilausrivi_osto.laskutettuaika            osto_laskaika,
           tilausrivi_osto.laatija                osto_rivilaatija,
           tilausrivi_osto.toimitettuaika            osto_toimitettuaika,
           tilausrivi_myynti.laskutettuaika          myynti_laskaika,
           tilausrivi_myynti.toimitettuaika          myynti_toimitettuaika,
           DATEDIFF(now(), tilausrivi_osto.laskutettuaika)    varpvm,
           (tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)  myyntihinta,
           varastopaikat.nimitys                varastonimi,
           tuote.sarjanumeroseuranta              sarjaseutyyppi,
           concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka";

if ((($from == "riviosto" or $from == "kohdista") and $ostonhyvitysrivi == "ON") or (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "REKLAMAATIO" or $from == "TYOMAARAYS" or $from == "TARJOUS" or ($from == "KERAA" and $aputoim == "") or $from == "KORJAA" or $valmiste_raakaine == "RAAKA-AINE") and $hyvitysrivi != "ON")) {
  //Myyd‰‰n sarjanumeroita
  $query .= "  FROM sarjanumeroseuranta
        LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
        LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
        LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
        LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
        LEFT JOIN tuote ON tuote.yhtio=sarjanumeroseuranta.yhtio and tuote.tuoteno = sarjanumeroseuranta.tuoteno
        LEFT JOIN varastopaikat ON (sarjanumeroseuranta.yhtio = varastopaikat.yhtio
          AND varastopaikat.tunnus = sarjanumeroseuranta.varasto)
        WHERE
        sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
        and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
        and (sarjanumeroseuranta.myyntirivitunnus in (0, $rivitunnus) or lasku_myynti.tila='T')
        $lisa
        $lisa2
        ORDER BY sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus";
  $sarjaresiso = pupe_query($query);
}
elseif (($from == "KERAA" and $aputoim == "SIIRTOLISTA") or $from == "SIIRTOLISTA") {
  //Sis‰inen tyˆm‰‰r‰ys sarjanumeroita
  $query .= "  ,
        tilausrivin_lisatiedot.osto_vai_hyvitys
        FROM sarjanumeroseuranta
        LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
        LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
        LEFT JOIN tilausrivin_lisatiedot ON tilausrivi_osto.yhtio=tilausrivin_lisatiedot.yhtio   and tilausrivi_osto.tunnus=tilausrivin_lisatiedot.tilausrivitunnus
        LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
        LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
        LEFT JOIN tuote ON tuote.yhtio=sarjanumeroseuranta.yhtio and tuote.tuoteno = sarjanumeroseuranta.tuoteno
        LEFT JOIN varastopaikat ON (sarjanumeroseuranta.yhtio = varastopaikat.yhtio
          AND varastopaikat.tunnus = sarjanumeroseuranta.varasto)
        WHERE
        sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
        and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
        and (sarjanumeroseuranta.myyntirivitunnus in (0, $rivitunnus) or lasku_myynti.tila='T')
        and sarjanumeroseuranta.siirtorivitunnus in (0, $rivitunnus)
        $lisa
        $lisa2
        ORDER BY sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus";
  $sarjaresiso = pupe_query($query);
}
elseif (($from == "KERAA" and $aputoim == "SIIRTOTYOMAARAYS") or $from == "SIIRTOTYOMAARAYS") {
  //Sis‰inen tyˆm‰‰r‰ys sarjanumeroita
  $query .= "  ,
        tilausrivin_lisatiedot.osto_vai_hyvitys
        FROM sarjanumeroseuranta
        LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
        LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
        LEFT JOIN tilausrivin_lisatiedot ON tilausrivi_osto.yhtio=tilausrivin_lisatiedot.yhtio   and tilausrivi_osto.tunnus=tilausrivin_lisatiedot.tilausrivitunnus
        LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
        LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
        LEFT JOIN tuote ON tuote.yhtio=sarjanumeroseuranta.yhtio and tuote.tuoteno = sarjanumeroseuranta.tuoteno
        LEFT JOIN varastopaikat ON (sarjanumeroseuranta.yhtio = varastopaikat.yhtio
          AND varastopaikat.tunnus = sarjanumeroseuranta.varasto)
        WHERE
        sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
        and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
        and (sarjanumeroseuranta.siirtorivitunnus in (0, $rivitunnus))
        and (tilausrivi_myynti.laskutettuaika is null or tilausrivi_myynti.laskutettuaika = '0000-00-00' or (tilausrivi_myynti.laskutettuaika > '0000-00-00' and (tilausrivi_myynti.laskutettuaika <= '$yhtiorow[tilikausi_loppu]' and  tilausrivi_myynti.laskutettuaika >= '$yhtiorow[tilikausi_alku]')))
        $lisa
        $lisa2
        ORDER BY sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus";
  $sarjaresiso = pupe_query($query);
}
elseif ((($from == "riviosto" or $from == "kohdista" or $valmiste_raakaine == "VALMISTE") and $ostonhyvitysrivi != "ON") or (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "REKLAMAATIO" or $from == "TYOMAARAYS" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA" or $from == "KORJAA") and $hyvitysrivi == "ON")) {
  // Ostetaan sarjanumeroita
  $query .= "  FROM sarjanumeroseuranta
        LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
        LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
        LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
        LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
        LEFT JOIN tuote ON tuote.yhtio=sarjanumeroseuranta.yhtio and tuote.tuoteno = sarjanumeroseuranta.tuoteno
        LEFT JOIN varastopaikat ON (sarjanumeroseuranta.yhtio = varastopaikat.yhtio
          AND varastopaikat.tunnus = sarjanumeroseuranta.varasto)
        WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
        and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
        and sarjanumeroseuranta.ostorivitunnus in (0, $rivitunnus)
        $lisa";

  if ($rivirow["sarjanumeroseuranta"] == "S" or $rivirow["sarjanumeroseuranta"] == "T" or $rivirow["sarjanumeroseuranta"] == "V") {
    $query  .= " GROUP BY sarjanumeroseuranta.ostorivitunnus, sarjanumeroseuranta.sarjanumero ";
  }
  else {
    $query  .= " GROUP BY sarjanumeroseuranta.tunnus ";
  }

  $query .= "  $lisa2
        ORDER BY sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus";
  $sarjaresiso = pupe_query($query);
}
elseif ($from == "INVENTOINTI") {
  // Inventoidaan
  $query .= "  FROM sarjanumeroseuranta

        LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
        LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
        LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
        LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
        LEFT JOIN tuote ON tuote.yhtio=sarjanumeroseuranta.yhtio and tuote.tuoteno = sarjanumeroseuranta.tuoteno
        LEFT JOIN varastopaikat ON (sarjanumeroseuranta.yhtio = varastopaikat.yhtio
          AND varastopaikat.tunnus = sarjanumeroseuranta.varasto)
        WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
        and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
        and sarjanumeroseuranta.ostorivitunnus in (0, $rivitunnus)
        and sarjanumeroseuranta.myyntirivitunnus = 0
        $lisa
        GROUP BY sarjanumeroseuranta.ostorivitunnus, sarjanumeroseuranta.sarjanumero
        $lisa2
        ORDER BY sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus";
  $sarjaresiso = pupe_query($query);
}
elseif ($lisa != "" or $lisa2 != "") {
  // Listataan
  $query .= "  FROM sarjanumeroseuranta
        LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
        LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
        LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
        LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.otunnus
        LEFT JOIN tuote use index (tuoteno_index) ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
        LEFT JOIN varastopaikat ON (sarjanumeroseuranta.yhtio = varastopaikat.yhtio
          AND varastopaikat.tunnus = sarjanumeroseuranta.varasto)
        WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
        $lisa
        $lisa2
        ORDER BY sarjanumeroseuranta.tuoteno, sarjanumeroseuranta.sarjanumero, sarjanumeroseuranta.tunnus
        LIMIT 250";
  $sarjaresiso = pupe_query($query);
}

if ($rivirow["tuoteno"] != '') {

  echo "<table>";
  echo "<tr><th>".t("Tuotenumero")."</th><td>$rivirow[tuoteno] $rivirow[nimitys]</td></tr>";
  echo "<tr><th>".t("M‰‰r‰")."</th><td>$rivirow[varattu] ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[yksikko]'", "", "", "selite")."</td></tr>";
  echo "<tr><th>", t("Kehahinta"), "</th>";
  echo "<td>";
  if ($rivirow['sarjanumeroseuranta'] == 'T' or $rivirow['sarjanumeroseuranta'] == 'V' or $rivirow['sarjanumeroseuranta'] == 'E' or $rivirow['sarjanumeroseuranta'] == 'F') {
    echo $rivirow['kehahin'];
  }
  elseif ($kehahin != '') {
    echo $kehahin;
  }
  else {
    echo "&nbsp;";
  }
  echo "</td>";
  echo "</tr>";
  echo "</table><br>";
}

echo "<table>";
echo "<tr>";
echo "<th>".t("Sarjanumero")."</th>";
echo "<th>".t("Tuoteno")."</th>";
echo "<th>".t("Nimitys")."</th>";
echo "<th>".t("Varastopaikka")."</th>";
echo "<th>".t("Ostotilaus")."</th>";
echo "<th>".t("Myyntitilaus")."</th>";

if (($sarjarow[$tunnuskentta] == 0 or $sarjarow[$tunnuskentta] == $rivitunnus) and $rivitunnus != '') {
  echo "<th>".t("Valitse")."</th>";
}

echo "<th>".t("Lis‰tiedot")."</th>";
echo "</tr>";

echo "  <SCRIPT LANGUAGE=JAVASCRIPT>
      function verify(){
        msg = '".t("Haluatko todella poistaa t‰m‰n sarjanumeron")."?';

        if (confirm(msg)) {
          return true;
        }
        else {
          skippaa_tama_submitti = true;
          return false;
        }
      }
    </SCRIPT>";

if (strpos($_SERVER['SCRIPT_NAME'], "sarjanumeroseuranta.php") !== FALSE or $PHP_SELF == "sarjanumeroseuranta.php") {
  echo "<form name='haku' action='sarjanumeroseuranta.php' method='post'>";
  echo "<input type='hidden' name='$tunnuskentta'   value = '$rivitunnus'>";
  echo "<input type='hidden' name='from'         value = '$from'>";
  echo "<input type='hidden' name='lopetus'       value = '$lopetus'>";
  echo "<input type='hidden' name='aputoim'       value = '$aputoim'>";
  echo "<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>";
  echo "<input type='hidden' name='toiminto'       value = '$toiminto'>";
  echo "<input type='hidden' name='sarjatunnus'     value = '$sarjatunnus'>";
  echo "<input type='hidden' name='otunnus'       value = '$otunnus'>";
  echo "<tr>";
  echo "<td><input type='text' size='10' name='sarjanumero_haku'     value='$sarjanumero_haku'></td>";
  echo "<td><input type='text' size='10' name='tuoteno_haku'       value='$tuoteno_haku'></td>";
  echo "<td><input type='text' size='10' name='nimitys_haku'       value='$nimitys_haku'></td>";
  echo "<td><input type='text' size='10' name='varasto_haku'       value='$varasto_haku'></td>";
  echo "<td><input type='text' size='10' name='ostotilaus_haku'     value='$ostotilaus_haku'></td>";
  echo "<td><input type='text' size='10' name='myyntitilaus_haku'    value='$myyntitilaus_haku'></td>";

  if (($sarjarow[$tunnuskentta] == 0 or $sarjarow[$tunnuskentta] == $rivitunnus) and $rivitunnus != '') {
    echo "<td></td>";
  }

  echo "<td></td>";
  echo "<td class='back'><input type='submit' value='Hae'></td>";
  echo "</tr>";
  echo "</form>";
}

echo "<form method='post' action='sarjanumeroseuranta.php'>";
echo "<input type='hidden' name='$tunnuskentta'   value='$rivitunnus'>";
echo "<input type='hidden' name='from'         value='$from'>";
echo "<input type='hidden' name='lopetus'       value='$lopetus'>";
echo "<input type='hidden' name='aputoim'       value='$aputoim'>";
echo "<input type='hidden' name='muut_siirrettavat' value='$muut_siirrettavat'>";
echo "<input type='hidden' name='toiminto'       value='$toiminto'>";
echo "<input type='hidden' name='sarjatunnus'     value='$sarjatunnus'>";
echo "<input type='hidden' name='otunnus'       value='$otunnus'>";
echo "<input type='hidden' name='formista'       value='kylla'>";
echo "<input type='hidden' name='sarjanumero_haku'   value='$sarjanumero_haku'>";
echo "<input type='hidden' name='tuoteno_haku'     value='$tuoteno_haku'>";
echo "<input type='hidden' name='nimitys_haku'     value='$nimitys_haku'>";
echo "<input type='hidden' name='varasto_haku'     value='$varasto_haku'>";
echo "<input type='hidden' name='ostotilaus_haku'   value='$ostotilaus_haku'>";
echo "<input type='hidden' name='myyntitilaus_haku'  value='$myyntitilaus_haku'>";
echo "<input type='hidden' name='lisatieto_haku'   value='$lisatieto_haku'>";
echo "<input type='hidden' name='kehahin'       value='$kehahin'>";

$valitut_sarjat = array();

if (is_resource($sarjaresiso) and mysql_num_rows($sarjaresiso) > 0) {
  while ($sarjarow = mysql_fetch_assoc($sarjaresiso)) {

    $sarjarow["nimitys"] = str_replace("\n", "<br>", $sarjarow["nimitys"]);

    //katsotaan onko sarjanumerolle liitetty kulukeikka
    $query  = "SELECT *
               from lasku
               where yhtio      = '$kukarow[yhtio]'
               and tila         = 'K'
               and alatila      = 'S'
               and liitostunnus = '$sarjarow[tunnus]'
               and ytunnus      = '$sarjarow[tunnus]'";
    $keikkares = pupe_query($query);

    unset($kulurow);
    unset($keikkarow);

    if (mysql_num_rows($keikkares) == 1) {
      $keikkarow = mysql_fetch_assoc($keikkares);
    }

    echo "<tr>";
    echo "<td class='ptop'>
            <a href='$PHP_SELF" .
      "?toiminto=MUOKKAA" .
      "&$tunnuskentta=$rivitunnus" .
      "&from=$from" .
      "&aputoim=$aputoim" .
      "&otunnus=$otunnus" .
      "&sarjatunnus=$sarjarow[tunnus]" .
      "&sarjanumero_haku=$sarjanumero_haku" .
      "&tuoteno_haku=".urlencode($tuoteno_haku)."" .
      "&nimitys_haku=$nimitys_haku" .
      "&varasto_haku=$varasto_haku" .
      "&ostotilaus_haku=$ostotilaus_haku" .
      "&myyntitilaus_haku=$myyntitilaus_haku" .
      "&lisatieto_haku=$lisatieto_haku" .
      "&muut_siirrettavat=$muut_siirrettavat&lopetus=$sarjalopetus'>".
      strtoupper($sarjarow["sarjanumero"]).
      "</a>
            <a name='$sarjarow[sarjanumero]'></a>";

    if ($rivirow["sarjanumeroseuranta"] == "E" or $rivirow["sarjanumeroseuranta"] == "F" or $rivirow["sarjanumeroseuranta"] == "G") {

      // Tarkistetaan onko t‰t‰ er‰‰ jo myyty/ker‰tty....
      if ($tunnuskentta == 'ostorivitunnus' and $from == "kohdista") {
        $query = "SELECT tilausrivi.tunnus
                  FROM sarjanumeroseuranta
                  JOIN tilausrivi ON tilausrivi.yhtio=tilausrivi.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.myyntirivitunnus and (tilausrivi.keratty != '' or tilausrivi.toimitettu != '') and tilausrivi.var in ('','H')
                  WHERE sarjanumeroseuranta.yhtio        = '$kukarow[yhtio]'
                  and sarjanumeroseuranta.ostorivitunnus = '$rivirow[tunnus]'";
        $lisat_res = pupe_query($query);
        $lisat_row = mysql_fetch_assoc($lisat_res);

        // Lukitaan sarjanumero
        $sarjarow["osto_laskaika"] = date("Y-m-d");
        $sarjarow["myyntirivitunnus"] = $lisat_row["tunnus"];
      }

      if ($sarjarow["era_kpl"] < 0) {
        echo "<br>".t("Er‰ss‰").": ".abs($sarjarow["era_kpl"])." ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[yksikko]'", "", "", "selite")."<br><font class='error'>".t("Er‰ on jo myyty")."!</font>";
      }
      else {
        echo "<br>".t("Er‰ss‰").": $sarjarow[era_kpl] ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[yksikko]'", "", "", "selite");
      }
    }

    echo "</td>";
    echo "<td colspan='2' class='ptop'><a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($sarjarow["tuoteno"])."&lopetus=$sarjalopetus'>$sarjarow[tuoteno]</a><br>$sarjarow[nimitys]";

    if ($sarjarow["takuu_alku"] != '' and $sarjarow["takuu_alku"] != '0000-00-00') {
      echo "<br>".t("Takuu").": ".tv1dateconv($sarjarow["takuu_alku"])." - ".tv1dateconv($sarjarow["takuu_loppu"]);
    }

    if ($rivirow["sarjanumeroseuranta"] != "E" and $rivirow["sarjanumeroseuranta"] != "F" and $rivirow["sarjanumeroseuranta"] != "G" and ($sarjarow["myynti_laskaika"] == "0000-00-00" or $sarjarow["myynti_laskaika"] == "")) {
      echo "<br>".t("Varastointiaika").": ".$sarjarow["varpvm"]." ".t("pva").". (".tv1dateconv($sarjarow["osto_laskaika"]).")";
    }

    echo "</td>";
    echo "<td class='ptop'>$sarjarow[varastonimi]<br>$sarjarow[tuotepaikka]</td>";

    if ($sarjarow["ostorivitunnus"] == 0) {
      $sarjarow["ostorivitunnus"] = "";
    }
    if ($sarjarow["myyntirivitunnus"] == 0) {
      $sarjarow["myyntirivitunnus"] = "";
    }

    $echoostuns = "";

    if ($sarjarow["osto_uusiotunnus"] > 0) {
      $ostuns = $sarjarow["osto_uusiotunnus"];

      $query = "SELECT laskunro FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tila = 'K' and tunnus = '$sarjarow[osto_uusiotunnus]'";
      $keikkares = pupe_query($query);

      if (mysql_num_rows($keikkares) > 0) {
        $keikkarow2 = mysql_fetch_assoc($keikkares);
        $echoostuns = t("Saapuminen").": ".$keikkarow2["laskunro"];
      }
    }
    else {
      $ostuns = $sarjarow["osto_tunnus"];
    }

    if ($echoostuns == '') {
      $echoostuns = $ostuns;
    }

    if ($sarjarow["osto_rivilaatija"] == "Invent" and $sarjarow["osto_nimi"] == "") {
      $ostoekotus = "$echoostuns ".t("Inventointi");
    }
    else {
      $ostoekotus = "<a href='{$palvelin2}raportit/asiakkaantilaukset.php?toim=OSTO&tee=NAYTATILAUS&tunnus=$ostuns&lopetus=$sarjalopetus'>$echoostuns $sarjarow[osto_nimi]</a>";
    }

    echo "<td colspan='2' class='ptop'>$ostoekotus<br>";

    $fnlina1 = "";
    $fnlina2 = "";

    if ($sarjarow["myynti_tila"] == 'T') {
      $fnlina1 = "<font class='message'>(".t("Tarjous").": ";
      $fnlina2 = ")</font>";
    }

    if ($sarjarow["myyntirivitunnus"] == -1) {
      $sarjarow["myynti_nimi"] = t("Inventointi");
    }

    if (($sarjarow["siirtorivitunnus"] > 0 and $tunnuskentta != 'siirtorivitunnus') or ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"])) {

      if ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"]) {
        $ztun = $sarjarow["osto_perheid2"];
      }
      else {
        $ztun = $sarjarow["siirtorivitunnus"];
      }

      $query = "SELECT tilausrivi.tunnus, tilausrivi.tuoteno, sarjanumeroseuranta.sarjanumero, tyyppi, otunnus
                FROM tilausrivi
                LEFT JOIN sarjanumeroseuranta ON (tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus)
                WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.tunnus='$ztun'";
      $siires = pupe_query($query);
      $siirow = mysql_fetch_assoc($siires);

      if ($siirow["tyyppi"] == "O") {
        // pultattu kiinni johonkin
        $fnlina1 .= "";
        $fnlina2 .= "<br><br>".t("Varattu lis‰varusteena").":<br>".$siirow["tuoteno"]." <a href='{$palvelin2}tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($siirow["tuoteno"])."&sarjanumero_haku=".urlencode($siirow["sarjanumero"])."&lopetus=$sarjalopetus'>$siirow[sarjanumero]</a>";
      }
      elseif ($siirow["tyyppi"] == "G") {
        // jos t‰m‰ on jollain siirtolistalla
        $fnlina1 .= "<font class='message'>(".t("Kesken siirtolistalla").": ";
        $fnlina2 .= ")</font>";
        $sarjarow["myynti_nimi"] .= $siirow["otunnus"];
      }
    }

    if ($sarjarow["myynti_tunnus"] > 0) {
      echo "$fnlina1 <a href='{$palvelin2}raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$sarjarow[myynti_tunnus]&lopetus=$sarjalopetus'>$sarjarow[myynti_tunnus] $sarjarow[myynti_nimi]</a> $fnlina2</td>";
    }
    else {
      echo "$fnlina1 $sarjarow[myynti_nimi] $fnlina2</td>";
    }

    if (($sarjarow[$tunnuskentta] == 0 or $sarjarow["myynti_tila"] == 'T' or $sarjarow[$tunnuskentta] == $rivitunnus) and $rivitunnus != '') {
      $chk = "";
      if ($sarjarow[$tunnuskentta] == $rivitunnus) {
        $chk = "CHECKED";

        // T‰t‰ voidaan tarvita myˆhemmin
        $valitut_sarjat[] = $sarjarow["tunnus"];
      }

      if ($tunnuskentta == "ostorivitunnus" and $sarjarow["kpl"] != 0) {
        echo "<td class='ptop'>".t("Lukittu")."</td>";
      }
      elseif ($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "REKLAMAATIO" or $from == "TYOMAARAYS" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "KERAA" or $from == "KORJAA" or $from == "riviosto" or $from == "valmistus" or $from == "VALMISTAASIAKKAALLE" or $from == "VALMISTAVARASTOON" or $from == "kohdista" or $from == "INVENTOINTI") {

        if  (($from != "SIIRTOTYOMAARAYS" and $laskurow["tila"] != "G" and $from != "SIIRTOLISTA" and $sarjarow["siirtorivitunnus"] > 0) or
          (($from == "riviosto" or $from == "kohdista") and $ostonhyvitysrivi != "ON" and $sarjarow["osto_laskaika"] > '0000-00-00' and ($sarjarow["siirtorivitunnus"] > 0 or $sarjarow["myyntirivitunnus"] > 0)) or
          ($valmiste_raakaine == "RAAKA-AINE" and ($sarjarow["myynti_toimitettuaika"] > "0000-00-00 00:00:00" or $sarjarow["myynti_laskaika"] > "0000-00-00")) or
          ($valmiste_raakaine == "VALMISTE" and ($sarjarow["osto_toimitettuaika"] > "0000-00-00 00:00:00" or $sarjarow["osto_laskaika"] > "0000-00-00")) or
          ($from == "SIIRTOTYOMAARAYS" and $sarjarow["ostorivitunnus"] == 0)) {
          $dis = "DISABLED";
        }
        else {
          $dis = "";
        }

        echo "<input type='hidden' name='sarjat[]' value='$sarjarow[tunnus]'>";
        echo "<td class='ptop'><input type='checkbox' name='sarjataan[]' value='$sarjarow[tunnus]' $chk onclick='submit();' $dis></td>";
      }
      else {
        echo "<td class='ptop'></td>";
      }
    }

    echo "<td class='ptop' nowrap>";

    //otetaan viivan paikka, jotta saadaan k‰‰nnetty‰ jos PUUTTUU ei olekkaan suomeksi
    $viiva = strpos($sarjarow['sarjanumero'], "-");
    if ($viiva === FALSE) {
      $viiva = 0;
    }

    if ($sarjarow['sarjaseutyyppi'] == "S" and $sarjarow['ostorivitunnus'] > 0 and ($from == "" or $from == "SIIRTOTYOMAARAYS") and ($sarjarow["myynti_laskaika"] == "0000-00-00" or $sarjarow["myynti_laskaika"] == "" or ($sarjarow['myynti_laskaika'] <= $yhtiorow["tilikausi_loppu"] and $sarjarow['myynti_laskaika'] >= $yhtiorow["tilikausi_alku"]))) {
      if ($keikkarow["tunnus"] > 0) {
        $keikkalisa = "&otunnus=$keikkarow[tunnus]";
      }
      else {
        $keikkalisa = "&luouusikeikka=OK&liitostunnus=$sarjarow[tunnus]";
      }

      echo "<a href='$PHP_SELF?toiminto=kululaskut$keikkalisa&keikanalatila=S&lopetus=$sarjalopetus'>".t("Liit‰ kululasku")."</a><br>";
    }

    if ($sarjanumeronLisatiedot) {
      $query = "SELECT *
                FROM sarjanumeron_lisatiedot
                WHERE yhtio      = '$kukarow[yhtio]'
                and liitostunnus = '$sarjarow[tunnus]'";
      $lisares = pupe_query($query);
      $lisarow = mysql_fetch_assoc($lisares);

      if ($lisarow["tunnus"] != 0) {
        $ylisa = "&tunnus=$lisarow[tunnus]";
      }
      else {
        $ylisa = "&liitostunnus=$sarjarow[tunnus]&uusi=1";
      }

      echo "<a href='{$palvelin2}yllapito.php?toim=sarjanumeron_lisatiedot!!!!!!TRUE$ylisa&lopetus=$sarjalopetus'>".t("Lis‰tiedot")."</a><br>";
      echo "<a href='#' onClick=\"javascript:sarjanumeronlisatiedot_popup('$sarjarow[tunnus]')\">".t("Lis‰tietoikkuna")."</a><br>";
    }

    if ($sarjarow['ostorivitunnus'] == 0 and $sarjarow['myyntirivitunnus'] == 0 and $keikkarow["tunnus"] == 0 and $sarjarow["era_kpl"] >= 0) {
      echo "<a href='$PHP_SELF?toiminto=POISTA&$tunnuskentta=$rivitunnus&from=$from&aputoim=$aputoim&otunnus=$otunnus&sarjatunnus=$sarjarow[tunnus]&sarjanumero_haku=$sarjanumero_haku&tuoteno_haku=$tuoteno_haku&nimitys_haku=$nimitys_haku&varasto_haku=$varasto_haku&ostotilaus_haku=$ostotilaus_haku&myyntitilaus_haku=$myyntitilaus_haku&lisatieto_haku=$lisatieto_haku&muut_siirrettavat=$muut_siirrettavat' onclick=\"return verify()\">".t("Poista")."</a><br>";
    }

    $query = "SELECT tunnus
              FROM liitetiedostot
              WHERE yhtio      = '$kukarow[yhtio]'
              and liitos       = 'sarjanumeroseuranta'
              and liitostunnus = '$sarjarow[tunnus]'
              ORDER BY jarjestys, tunnus";
    $lisares = pupe_query($query);
    $liilask = 1;

    while ($lisarow = mysql_fetch_assoc($lisares)) {
      echo js_openUrlNewWindow("{$palvelin2}view.php?id={$lisarow[tunnus]}", t("Liite")." ".$liilask, "", 800, 600);
      echo "<br>";
      $liilask++;
    }

    echo "</td>";
    echo "</tr>";
  }
}
echo "</form>";
echo "</table>";

//Kursorinohjaus
if ($rivirow["sarjanumeroseuranta"] == "T" or $rivirow["sarjanumeroseuranta"] == "V") {
  $formi  = "sarjaformi";
  $kentta = "sarjanumero";
}
else {
  $formi  = "haku";
  $kentta = "sarjanumero_haku";
}

if ($toiminto == '') {
  $sarjanumero = '';
  $lisatieto = '';
  $chk = '';
}

if ($rivirow["tyyppi"] != 'V') {
  if ($rivirow["tuoteno"] != '') {
    echo "<form name='sarjaformi' action='sarjanumeroseuranta.php' method='post'>
          <input type='hidden' name='$tunnuskentta' value='$rivitunnus'>
          <input type='hidden' name='from' value='$from'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='aputoim' value='$aputoim'>
          <input type='hidden' name='otunnus' value='$otunnus'>
          <input type='hidden' name='muut_siirrettavat' value='$muut_siirrettavat'>
          <input type='hidden' name='toiminto' value='LISAA'>
          <input type='hidden' name='valitut_sarjat' value='".implode(",", $valitut_sarjat)."'>";

    if ($rivirow["tuoteno"] != '' and ($rivirow["sarjanumeroseuranta"] == "E" or $rivirow["sarjanumeroseuranta"] == "F" or $rivirow["sarjanumeroseuranta"] == "G")) {
      $query = "SELECT max(substring(sarjanumero, position('-' IN sarjanumero)+1)+0)+1 sarjanumero
                FROM sarjanumeroseuranta
                WHERE yhtio     = '$kukarow[yhtio]'
                and tuoteno     = '$rivirow[tuoteno]'
                and sarjanumero like '".t("Er‰")."-%'";
      $vresult = pupe_query($query);
      $vrow = mysql_fetch_assoc($vresult);

      if ($vrow["sarjanumero"] > 0) {
        $nxt = t("Er‰")."-".$vrow["sarjanumero"];
      }
      else {
        $nxt = t("Er‰")."-1";
      }

      echo "<br><table>";
      echo "<tr><th colspan='2'>".t("Lis‰‰ uusi er‰numero")."</th></tr>";
      echo "<tr><th>".t("Er‰numero")."</th><td><input type='text' size='30' name='sarjanumero' value='$sarjanumero'></td><td class='back'><a href='#' onclick='document.sarjaformi.sarjanumero.value=\"$nxt\";'>".t("Seuraava er‰")."</a></td></tr>";

      echo "<tr><th>".t("Er‰n suuruus")."</th><td><input type='text' size='30' name='era_kpl' value='$era_kpl'></td></tr>";

      if ($rivirow["sarjanumeroseuranta"] == "F") {
        echo "<tr><th>".t("Parasta ennen")."</th><td>
        <input type='text' name='peppa' value='' size='3'>
        <input type='text' name='pekka' value='' size='3'>
        <input type='text' name='pevva' value='' size='5'></td>";
      }

      if ($rivirow['sarjanumeroseuranta'] == 'G') {
        echo "<input type='hidden' name='kehahin' value='$kehahin'>";
      }
      else {
        echo "<input type='hidden' name='kehahin' value='$rivirow[kehahin]'>";
      }

      echo "<tr><th>".t("Lis‰tieto")."</th><td><textarea rows='4' cols='27' name='lisatieto'>$lisatieto</textarea></td></tr>";
    }
    elseif ($rivirow["sarjanumeroseuranta"] == "T") {
      echo "<br><table>";
      echo "<tr><th colspan='2'>".t("Lis‰‰ uusi sarjanumero")."</th></tr>";
      echo "<tr><th>".t("Sarjanumero")."</th><td><input type='text' size='30' name='sarjanumero' value='$sarjanumero'></td></tr>";
      echo "<input type='hidden' name='kehahin' value='$rivirow[kehahin]'>";
    }
    else {
      $query = "SELECT max(substring(sarjanumero, position('-' IN sarjanumero)+1)+0)+1 sarjanumero
                FROM sarjanumeroseuranta
                WHERE yhtio='$kukarow[yhtio]'
                and tuoteno     = '$rivirow[tuoteno]'
                and sarjanumero like '".t("PUUTTUU")."-%'";
      $vresult = pupe_query($query);
      $vrow = mysql_fetch_assoc($vresult);

      if ($vrow["sarjanumero"] > 0) {
        $nxt = t("PUUTTUU")."-".$vrow["sarjanumero"];
      }
      else {
        $nxt = t("PUUTTUU")."-1";
      }

      $query = "SELECT max(substring(sarjanumero, position('-' IN sarjanumero)+1)+0)+1 sarjanumero
                FROM sarjanumeroseuranta
                WHERE yhtio='$kukarow[yhtio]'
                and tuoteno     = '$rivirow[tuoteno]'
                and sarjanumero like '".t("EI SARJANUMEROA")."-%'";
      $vresult = pupe_query($query);
      $vrow = mysql_fetch_assoc($vresult);

      if ($vrow["sarjanumero"] > 0) {
        $nxt2 = t("EI SARJANUMEROA")."-".$vrow["sarjanumero"];
      }
      else {
        $nxt2 = t("EI SARJANUMEROA")."-1";
      }

      if ($sarjanumero == "" and $from != "PIKATILAUS" and $from != "RIVISYOTTO") {
        $sarjanumero = generoi_sarjanumero($rivirow, $tunnuskentta);
      }

      echo "<br><table>";
      echo "<tr><th colspan='2'>".t("Lis‰‰ uusi sarjanumero")."</th></tr>";
      echo "<tr><th>".t("Sarjanumero")."</th><td><input type='text' size='30' name='sarjanumero' value='$sarjanumero'></td><td class='back'><a onclick='document.sarjaformi.sarjanumero.value=\"$nxt\";'><u>".t("Sarjanumero ei tiedossa")."</u></a> <a onclick='document.sarjaformi.sarjanumero.value=\"$nxt2\";'><u>".t("Ei Sarjanumeroa")."</u></a></td></tr>";
      echo "<tr><th>".t("Lis‰tieto")."</th><td><textarea rows='4' cols='27' name='lisatieto'>$lisatieto</textarea></td></tr>";

      if ($rivirow["sarjanumeroseuranta"] == "S") {
        $chk = "";
        if ($kaytetty == "K") {
          $chk = "CHECKED";
        }
        elseif ($sarjanumero == "" and $rivirow["osto_vai_hyvitys"] == "O") {
          $chk = "CHECKED";
        }

        echo "<tr><th>".t("K‰ytetty")."</th><td><input type='checkbox' name='kaytetty' value='K' $chk></td></tr>";

        echo "<tr><th>".t("Takuu")."</th><td>
        <input type='text' name='tppa' value='' size='3'>
        <input type='text' name='tkka' value='' size='3'>
        <input type='text' name='tvva' value='' size='5'>
        -
        <input type='text' name='tppl' value='' size='3'>
        <input type='text' name='tkkl' value='' size='3'>
        <input type='text' name='tvvl' value='' size='5'></td>";
      }

      if ($rivirow['sarjanumeroseuranta'] == 'V') {
        echo "<input type='hidden' name='kehahin' value='$rivirow[kehahin]'>";
      }
      elseif ($rivirow['sarjanumeroseuranta'] == 'S') {
        echo "<input type='hidden' name='kehahin' value='$kehahin'>";
      }
    }

    echo "<td class='back'><input type='submit' value='".t("Lis‰‰")."'></td>";
    echo "</form>";
    echo "</tr></table>";

    echo "<br>";
    echo "<form method='post' action='sarjanumeroseuranta.php' enctype='multipart/form-data' autocomplete='off'>";
    echo "<input type='hidden' name='$tunnuskentta' value='$rivitunnus'>";
    echo "<input type='hidden' name='from' value='$from'>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<input type='hidden' name='aputoim' value='$aputoim'>";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='muut_siirrettavat' value='$muut_siirrettavat'>";
    echo "<input type='hidden' name='toiminto' value='LISAA'>";
    echo "<input type='hidden' name='valitut_sarjat' value='".implode(",", $valitut_sarjat)."'>";
    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Lis‰‰ excelist‰")."</th>";
    echo "<td><input type='file' name='userfile'></td>";
    echo "<td class='back' colspan='2'><input type='submit' value='".t("Lis‰‰")."'></td>";
    echo "</tr>";
    echo "</table>";
    echo "</form>";
  }
}

echo "<br>";

if ($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "REKLAMAATIO" or $from == "TYOMAARAYS" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "SIIRTOTYOMAARAYS" or $from == "VALMISTAASIAKKAALLE" or $from == "VALMISTAVARASTOON") {
  echo "<form method='post' action='tilaus_myynti.php'>
    <input type='hidden' name='toim' value='$from'>
    <input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
    <input type='submit' value='".t("Takaisin tilaukselle")."'>
    </form>";
}

if ($from == "riviosto") {
  echo "<form method='post' action='tilaus_osto.php'>
    <input type='hidden' name='tee' value='Y'>
    <input type='hidden' name='aktivoinnista' value='true'>
    <input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
    <input type='submit' value='".t("Takaisin tilaukselle")."'>
    </form>";
}

if ($from == "kohdista") {
  echo "<form method='post' action='keikka.php'>
    <input type='hidden' name='toiminto' value='kohdista'>
    <input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
    <input type='hidden' name='otunnus' value='$otunnus'>
    <input type='submit' value='".t("Takaisin saapumiseen")."'>
    </form>";
}

if ($from == "KERAA") {
  echo "<form method='post' action='keraa.php'>
    <input type='hidden' name='toim' value='$aputoim'>
    <input type='hidden' name='id'   value='$otunnus'>
    <input type='submit' value='".t("Takaisin ker‰ykseen")."'>
    </form>";
}

if ($from == "valmistus") {
  echo "<form method='post' action='valmista_tilaus.php'>
    <input type='hidden' name='toim' value='$aputoim'>
    <input type='hidden' name='tee' value='VALMISTA'>
    <input type='hidden' name='tulin' value='VALINNASTA'>
    <input type='hidden' name='valmistettavat' value = '$muut_siirrettavat'>
    <input type='hidden' name='otunnus' value='$otunnus'>
    <input type='submit' value='".t("Takaisin valmistukselle")."'>
    </form>";
}

if ($from == "KORJAA") {
  $urli = lopetus($lopetus, "", TRUE);

  echo "<form method='post' action='$urli'>
    <input type='hidden' name='id'   value='$otunnus'>
    <input type='submit' value='".t("Takaisin laitemyyntien tarkistukseen")."'>
    </form>";
}

if ($from == "INVENTOINTI") {
  $urli = lopetus($lopetus, "", TRUE);

  echo "<form method='post' action='$urli'>
    <input type='hidden' name='id'   value='$otunnus'>
    <input type='submit' value='".t("Takaisin inventointiin")."'>
    </form>";
}

if (!empty($valitut_sarjat)) {
  echo "<br><br>
          <form method='post' id='sarjanumerotarrat' name='sarjanumerotarrat'>
            <input type='hidden' name='ostorivitunnus' value='{$ostorivitunnus}'>
            <input type='hidden' name='from' value='{$from}'>
            <input type='hidden' name='lopetus' value='{$lopetus}'>
            <input type='hidden' name='aputoim' value='{$aputoim}'>
            <input type='hidden' name='otunnus' value='{$otunnus}'>
            <input type='hidden' name='muut_siirrettavat' value='{$muut_siirrettavat}'>
            <input type='hidden' name='tee' value='NAYTATILAUS'>
            <input type='hidden' name='valitut_sarjat' value='" . implode(",", $valitut_sarjat) . "'>
            <input type='submit' value='" . t("Tulosta tarrat valituille sarjanumeroille") . "' onClick=\"js_openFormInNewWindow('sarjanumerotarrat', ''); return false;\">
          </form>";
}

if (strpos($_SERVER['SCRIPT_NAME'], "sarjanumeroseuranta.php")  !== FALSE) {
  require "inc/footer.inc";
}

/**
 *
 * @param array   $tuote        Tarvitaan kentat tuoteno, tunnus ja automaattinen_sarjanumerointi
 * @param string  $tunnuskentta Tarvitaan, jos kaytossa on generointitapa 1
 *
 * @return string Sarjanumero
 */


function generoi_sarjanumero($tuote, $tunnuskentta = "") {
  global $kukarow;

  switch ($tuote["automaattinen_sarjanumerointi"]) {
  case 1:
    if (empty($tunnuskentta)) break;

    $query = "SELECT max(substring(sarjanumero, position('-' IN sarjanumero) + 1) + 0) + 1 sarjanumero
              FROM sarjanumeroseuranta
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND tuoteno = '{$tuote["tuoteno"]}'
              AND {$tunnuskentta} = '{$tuote["tunnus"]}'";

    $result = pupe_query($query);
    $row    = mysql_fetch_assoc($result);

    $sarjanumero = $tuote["nimitys"];

    if ($row["sarjanumero"] > 0) {
      $sarjanumero = $sarjanumero . "-" . $row["sarjanumero"];
    }
    else {
      $sarjanumero = $sarjanumero . "-1";
    }

    break;
  case 2:
    $query = "SELECT max(substring(sarjanumero, 6)) AS kuluvan_vuoden_suurin_numero
              FROM sarjanumeroseuranta
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND substring(sarjanumero, 1, 4) = year(now())";

    $result = pupe_query($query);
    $row    = mysql_fetch_assoc($result);

    if ($row["kuluvan_vuoden_suurin_numero"]) {
      $sarjanumero = date("Y") . "-" . ($row["kuluvan_vuoden_suurin_numero"] + 1);
    }
    else {
      $sarjanumero = date("Y") . "-1000";
    }

    break;

  default:
    $sarjanumero = "";
  }

  return $sarjanumero;
}
