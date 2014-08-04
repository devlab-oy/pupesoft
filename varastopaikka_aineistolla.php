<?php

if (strpos($_SERVER['SCRIPT_NAME'], "varastopaikka_aineistolla.php")  !== FALSE) {
  require "inc/parametrit.inc";
  echo "<font class='head'>".t("Tuotteen varastopaikkojen muutos aineistolla")."</font><hr>";
}

if (!isset($tee) or (isset($varasto_valinta) and $varasto_valinta == '')) $tee = "";
if (!isset($virheviesti)) $virheviesti = "";

if ($tee == "AJA") {
  $virhe = 0;
  $kaikki_tiedostorivit = array();
  // Tutkitaan ja hutkitaan
  // Lˆytyykˆ tiedosto?
  if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
    $kasiteltava_tiedosto_path = $_FILES['userfile']['tmp_name'];
    list ($devnull, $tyyppi) = explode(".", $_FILES['userfile']['name']);

    $tyyppi = strtoupper($tyyppi);

    if ($tyyppi == "XLSX" or $tyyppi == "TXT") {

      echo "<br><br><font class='message'>".t("Luetaan l‰hetetty tiedosto")."...<br><br></font>";

      $kaikki_tiedostorivit = pupeFileReader($kasiteltava_tiedosto_path, $tyyppi);

      // Poistetaan tyhj‰t solut arraysta
      foreach ($kaikki_tiedostorivit as &$tiedr) {
        $tiedr = array_filter($tiedr);
      }

      $kaikki_tiedostorivit = array_filter($kaikki_tiedostorivit);

      // Siivous ja validitytsekit
      foreach ($kaikki_tiedostorivit as $rowkey => &$tiedr) {
        // Indeksit:
        $tuoteno       = $tiedr[0] = pupesoft_cleanstring($tiedr[0]);              // 0 - Tuotenumero
        $kpl         = $tiedr[1] = str_replace(",", ".", pupesoft_cleanstring($tiedr[1]));  // 1 - M‰‰r‰
        $lahdevarastopk   = $tiedr[2] = str_replace(" ", "", pupesoft_cleanstring($tiedr[2]));  // 2 - L‰hdevarastopaikka
        $kohdevarastopk   = $tiedr[3] = str_replace(" ", "", pupesoft_cleanstring($tiedr[3]));  // 3 - Kohdevarastopaikka
        $kom         = $tiedr[4] = pupesoft_cleanstring($tiedr[4]);              // 4 - Kommentti
        $poistetaanko_lahde = $tiedr[5] = str_replace(" ", "", strtoupper(pupesoft_cleanstring($tiedr[5])));  // 5 - Poistetaanko l‰hdevarastopaikka

        if ($poistetaanko_lahde != 'X') $tiedr[5] = '';

        // Jos joku pakollisista tiedoista on tyhj‰ tai v‰‰rin hyl‰t‰‰n koko rivi
        if (in_array("", array($tuoteno, $kpl, $lahdevarastopk, $kohdevarastopk)) or $lahdevarastopk == $kohdevarastopk or (!is_numeric($kpl) and $kpl != 'X')) {
          $seliseli = "";
          if (in_array("", array($tuoteno, $kpl, $lahdevarastopk, $kohdevarastopk))) $seliseli .= "-".t("Tuotenumero, kappalem‰‰r‰, l‰hde- tai kohdevarastopaikka ei saa olla tyhj‰").".<br>";
          if ($lahdevarastopk == $kohdevarastopk) $seliseli .= "-".t("L‰hde- ja kohdevarastopaikka olivat identtisi‰").".<br>";
          if (!is_numeric($kpl) and $kpl != 'X') $seliseli .= "-".t("Kappalem‰‰r‰ksi kelpaa %s tai numeerinen arvo", "", "X").".<br>";
          if ($seliseli != '') $seliseli = "<br>".$seliseli;

          echo "<font class='error'>".t("Virhe sis‰‰nluettavan tiedoston rivill‰ %s, rivi‰ ei huomioida", "", $rowkey+1)."...$seliseli</font><br>";

          unset($kaikki_tiedostorivit[$rowkey]);
          continue;
        }

        // LƒHDEVARASTOPAIKKA
        list($lhyllyalue, $lhyllynro, $lhyllyvali, $lhyllytaso) = explode("-", $lahdevarastopk);

        // Tarkistetaan onko tuotepaikka ja tuote olemassa
        $query = "SELECT tuotepaikat.*
                  FROM tuotepaikat use index (tuote_index)
                  JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno)
                  WHERE tuotepaikat.yhtio   = '$kukarow[yhtio]'
                  and tuotepaikat.tuoteno   = '$tuoteno'
                  and tuotepaikat.hyllyalue = '$lhyllyalue'
                  and tuotepaikat.hyllynro  = '$lhyllynro'
                  and tuotepaikat.hyllyvali = '$lhyllyvali'
                  and tuotepaikat.hyllytaso = '$lhyllytaso'";
        $tvresult = pupe_query($query);

        if (mysql_num_rows($tvresult) == 0) {
          unset($kaikki_tiedostorivit[$rowkey]);
          continue;
        }
        else {
          $tvrow = mysql_fetch_assoc($tvresult);

          $tiedr[2] = $tvrow['tunnus'];

          list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, '', $varasto_valinta, '', $lhyllyalue, $lhyllynro, $lhyllyvali, $lhyllytaso);

          if ($kpl == "X" or $kpl > $myytavissa) $tiedr[1] = $myytavissa;
        }

        // Tarkistetaan onko annettu l‰hdevarastopaikka valitussa varastossa
        $lahdetsekki = kuuluukovarastoon($lhyllyalue, $lhyllynro, $varasto_valinta);

        if ($lahdetsekki == 0) {
          echo "<font class='error'>".t("Tuotteen %s l‰hdevarastopaikka %s %s %s %s ei ole valitussa varastossa", "", $tuoteno, $lhyllyalue, $lhyllynro, $lhyllyvali, $lhyllytaso)."!</font><br>";
          unset($kaikki_tiedostorivit[$rowkey]);
          continue;
        }

        // KOHDEVARASTOPAIKKA
        list($ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso) = explode("-", $kohdevarastopk);

        // Tarkistetaan onko annettu kohdevarastopaikka valitussa varastossa
        $kohdetsekki = kuuluukovarastoon($ahyllyalue, $ahyllynro, $varasto_valinta);

        if ($kohdetsekki == 0) {
          echo "<font class='error'>".t("Tuotteen %s kohdevarastopaikka %s %s %s %s ei ole valitussa varastossa", "", $tuoteno, $ahyllyalue, $ahyllynro, $ahyllyvali, $ahyllytaso)."!</font><br>";
          unset($kaikki_tiedostorivit[$rowkey]);
          continue;
        }

        // Onko kohdetuotepaikka olemassa
        $query_ktp = "SELECT *
                      FROM tuotepaikat use index (tuote_index)
                      WHERE tuoteno = '$tuoteno'
                      AND yhtio     = '$kukarow[yhtio]'
                      AND hyllyalue = '$ahyllyalue'
                      AND hyllynro  = '$ahyllynro'
                      AND hyllyvali = '$ahyllyvali'
                      AND hyllytaso = '$ahyllytaso'";
        $kvresult = pupe_query($query_ktp);

        // Jos kohdetuotepaikkaa ei lˆydy, yritet‰‰n perustaa sellainen
        if (mysql_num_rows($kvresult) == 0) {
          $tee         = "UUSIPAIKKA";
          $kutsuja      = "varastopaikka_aineistolla.php";
          $ahalytysraja = 0;
          $atilausmaara = 0;

          // Jos l‰hdepaikka poistetaan, niin siirret‰‰n halytysraja ja tilausmaara uudelle paikalle.
          if ($poistetaanko_lahde == "X") {
            $ahalytysraja = $tvrow['halytysraja'];
            $atilausmaara = $tvrow['tilausmaara'];
          }

          require "muuvarastopaikka.php";
        }

        if (isset($failure)) unset($kaikki_tiedostorivit[$rowkey]);
        else {
          // Jos tehtiin uusi paikka niin haetaan tunnus siirtoa varten
          $kvresult = pupe_query($query_ktp);
          if (mysql_num_rows($kvresult) == 0) $virhe = 1;
          else {
            $ressi = mysql_fetch_assoc($kvresult);
            $tiedr[3] = $ressi['tunnus'];
          }
        }

        if (in_array('', array($tiedr[2], $tiedr[3]))) $virhe = 1;
        if ($tee == "PALATTIIN_MUUSTA") $tee = "AJA";
      }
    }
    else {
      $virheviesti .= t("Tiedostoformaattia ei tueta")."!<br>";
      $virhe = 1;
    }
  }
  else {
    $virheviesti .= t("Tiedostovalinta virheellinen")."!<br>";
    $virhe = 1;
  }

  if (count($kaikki_tiedostorivit) < 1 and $virhe == 0) {
    $virheviesti .= t("Tiedostosta ei lˆytynyt yht‰‰n validia rivi‰")."!<br>";
    $virhe = 1;
  }

  // Jos kaikki on ok ja soluja on viel‰ j‰ljell‰
  if ($virhe == 0) {

    echo "<br><br><font class='message'>".t("Siirret‰‰n %s tuotepaikan saldo", "", count($kaikki_tiedostorivit))."...<br><br></font>";

    foreach ($kaikki_tiedostorivit as $tkey => $tval) {
      // Parametrit muuvarastopaikka.phplle
      // $asaldo  = siirrett‰v‰ m‰‰r‰
      // $mista   = tuotepaikan tunnus josta otetaan
      // $minne   = tuotepaikan tunnus jonne siirret‰‰n
      // $tuoteno = tuotenumero jota siirret‰‰n
      $tuoteno = $tval[0];        // 0 - Tuotenumero
      $asaldo  = $tval[1];        // 1 - M‰‰r‰
      $mista   = $tval[2];        // 2 - L‰hdevarastopaikka - tunnus
      $minne   = $tval[3];        // 3 - Kohdevarastopaikka - tunnus
      $selite  = $tval[4];        // 4 - Kommentti - menee hyllysiirto-funkkarin tapahtuman selitteen loppuun
      $poistetaanko_lahde = $tval[5];    // 5 - Poistetaanko l‰hdevarastopaikka

      if ($asaldo != 0) {
        $tee = "N";
        $kutsuja = "varastopaikka_aineistolla.php";
        require "muuvarastopaikka.php";
      }

      // Merkataan viel‰ l‰hdevarastopaikka poistettavaksi jos se ei ole oletuspaikka
      if ($poistetaanko_lahde == 'X') {
        $query = "UPDATE tuotepaikat
                  SET poistettava = 'D'
                  WHERE tuoteno = '{$tuoteno}'
                  AND yhtio     = '{$kukarow['yhtio']}'
                  AND tunnus    = '$mista'
                  AND oletus    = ''";
        pupe_query($query);

        if (mysql_affected_rows() != 0) {
          echo "<font class='message'>".t("Tuotteen %s l‰hdevarastopaikka merkattiin poistettavaksi", "", $tuoteno)."!</font><br>";
        }
        else {
          echo "<font class='error'>".t("Tuotteen %s l‰hdevarastopaikka on oletuspaikka tai jo merkattu poistettavaksi", "", $tuoteno)."!</font><br>";
        }
      }
    }

    if ($tee == 'MEGALOMAANINEN_ONNISTUMINEN')   echo "<br><font class='message'>".t("Siirto valmis")."!</font><br><br>";
    $tee = "";
    $kutsuja = "";
  }
  else {
    $tee = "VALITSE_TIEDOSTO";
    $kutsuja = "";
  }
}

if ($tee == "") {

  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND nimitys != ''
            AND tyyppi  != 'P'
            ORDER BY tyyppi,nimitys";
  $vresult = pupe_query($query);

  echo "  <form name='varasto' method='post'>
      <input type='hidden' name='tee' value='VALITSE_TIEDOSTO'>
      <table>
      <tr><th>".t("Valitse kohdevarasto").":</th>
      <td><select name='varasto_valinta'><option value = ''>".t("Ei varastoa")."</option>";
  while ($varasto = mysql_fetch_assoc($vresult)) {
    $sel = "";
    if ($varasto_valinta != '' and $varasto_valinta == $varasto['tunnus']) $sel = "SELECTED";
    echo "<option value='{$varasto['tunnus']}' $sel>{$varasto['nimitys']}</option>";
  }

  echo "  </select></td><td class='back'><font class='error'>{$virheviesti}</font></td></tr>
      </table>
      <br><input type = 'submit' value = '".t("Hae")."'>
      </form>";
}

if ($tee == 'VALITSE_TIEDOSTO' and $varasto_valinta != '') {

  $ohje_sarake_1 = t("Siirrett‰v‰n tuotteen tuotenumero");
  $ohje_sarake_2 = t("Siirrett‰v‰ m‰‰r‰. Siirett‰v‰ kappalem‰‰r‰ ei voi ylitt‰‰ tuotteen myyt‰viss‰ olevaa m‰‰r‰‰. Kappalem‰‰r‰ksi voi syˆtt‰‰ avainsanan %s jolloin k‰ytet‰‰n automaattisesti koko myyt‰viss‰ olevaa m‰‰r‰‰. K‰ytt‰m‰ll‰ avainsanaa %s k‰sitell‰‰n myˆs nollasaldoiset tuotteet.", "", "X");
  $ohje_sarake_3 = t("Varastopaikka mist‰ siirret‰‰n. Hyllyalue-hyllynumero-hyllyv‰li-hyllytaso v‰liviivalla eroteltuna.");
  $ohje_sarake_4 = t("Varastopaikka mihin siirret‰‰n. Hyllyalue-hyllynumero-hyllyv‰li-hyllytaso v‰liviivalla eroteltuna. Jos paikkaa ei lˆydy niin sellainen luodaan annetuilla parametreill‰");
  $ohje_sarake_5 = t("Kommentti liitet‰‰n hyllysiirron yhteydess‰ teht‰v‰‰n tapahtumaan");
  $ohje_sarake_6 = t("Arvolla %s l‰hdepaikka poistetaan siirron j‰lkeen, muuten l‰hdepaikkaa ei poisteta. L‰hdepaikan h‰lytysraja ja tilausm‰‰r‰ siirret‰‰n kohdepaikalle jos l‰hdepaikka merkit‰‰n poistettavaksi.", "", "X");

  $ahlopetus   = $palvelin2."varastopaikka_aineistolla.php////tee=''//kutsuja=''";

  echo "  <table>
      <tr><th colspan='6'>".t("Sarkaineroteltu tekstitiedosto tai excel-tiedosto.")."</th></tr>
      <tr><td title='{$ohje_sarake_1}'>".t("Tuotenumero")."</td>
        <td title='{$ohje_sarake_2}'>".t("M‰‰r‰")."</td>
        <td title='{$ohje_sarake_3}'>".t("L‰hdepaikka")."</td>
        <td title='{$ohje_sarake_4}'>".t("Kohdepaikka")."</td>
        <td title='{$ohje_sarake_5}'>".t("Kommentti")."</td>
        <td title='{$ohje_sarake_6}'>".t("Poistetaanko l‰hdepaikka lopuksi")."</td></tr>
      </table><br><font class='message'>".t("Lis‰tietoja saat kohdistamalla kursorin yll‰oleviin sarakkeisiin")."</font><br><br>";

  echo "  <form name='tiedosto' method='post' enctype='multipart/form-data'>
      <input type='hidden' name='lopetus' value='{$ahlopetus}'>
      <input type='hidden' name='varasto_valinta' value='$varasto_valinta'>
      <input type='hidden' name='tee' value='AJA'>
      <table>
      <tr><th>".t("Valitse tiedosto").":</th>
      <td><input name='userfile' type='file'></td>
      </tr>
      </table>
      <br><input type='submit' value='".t("L‰het‰")."'> <font class='error'>{$virheviesti}</font>
      </form>";
}

require "inc/footer.inc";
