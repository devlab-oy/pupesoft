<?php

if (isset($_POST["toim"]) and $_POST["toim"] == "yhtion_parametrit") {
  $apucss             = $_POST["css"];
  $apucssclassic      = $_POST["css_classic"];
  $apucssextranet     = $_POST["css_extranet"];
  $apucssverkkokauppa = $_POST["css_verkkokauppa"];
  $apuwebseuranta     = $_POST["web_seuranta"];
}
else {
  unset($apucss);
  unset($apucssclassic);
  unset($apucssextranet);
  unset($apucssverkkokauppa);
  unset($apuwebseuranta);
}

if (strpos($_SERVER['SCRIPT_NAME'], "yllapito.php")  !== FALSE) {
  require "inc/parametrit.inc";
  echo "<script src='yllapito.js'></script>";
}

// Rails infraan siirretyt ylläpitonäkymät, eli $pupenext_yllapitonakymat, määritellään parametrit.inc:tiedostossa.
if (array_key_exists($toim, $pupenext_yllapitonakymat)) {

  $psx_url = $pupenext_yllapitonakymat[$toim];

  echo "<font class='head'>", t("Virhe"), "</font><hr>";

  echo "<br />";

  echo "<a href='{$psx_url}'>";
  echo t("%s ylläpito on siirtynyt uuteen ympäristöön", '', $toim);
  echo " &raquo;</a>";

  echo "<br />";

  require "inc/footer.inc";
  exit;
}

if (function_exists("js_popup")) {
  echo js_popup(-100);
}

if (isset($livesearch_tee) and $livesearch_tee == "TILIHAKU") {
  livesearch_tilihaku();
  exit;
}

if (isset($livesearch_tee) and $livesearch_tee == "TUOTERYHMAHAKU") {
  livesearch_tuoteryhmahaku();
  exit;
}

//Jotta määritelty rajattu näkymä olisi myös käyttöoikeudellisesti tiukka
$aputoim = $toim;
$toimi_array = explode('!!!', $toim);
$a_lisa = "";

$toim = $toimi_array[0];
if (isset($toimi_array[1])) $alias_set = $toimi_array[1];
if (isset($toimi_array[2])) $rajattu_nakyma = $toimi_array[2];

if ($toim == "toimi" or $toim == "asiakas" or $toim == "tuote" or $toim == "avainsana") {
  enable_ajax();
}

// Tuotteita voidaan rajata status -kentällä
$tuote_status_rajaus_lisa = "";
$tuote_status_lisa = "";

if (isset($status) and $toim == 'tuote') {
  $tuote_status_rajaus_lisa = " and tuote.status = '".pupesoft_cleanstring($status)."' ";
  $tuote_status_lisa = "&status=$status";
}

// Setataan muuttujat
if (!isset($rajauslisa))          $rajauslisa = "";
if (!isset($del))                 $del = "";
if (!isset($errori))              $errori = "";
if (!isset($from))                $from = "";
if (!isset($haku))                $haku = "";
if (!isset($js_open_yp))          $js_open_yp = "";
if (!isset($laji))                $laji = "";
if (!isset($limit))               $limit = "";
if (!isset($lisa))                $lisa = "";
if (!isset($lopetus))             $lopetus = "";
if (!isset($nayta_eraantyneet))   $nayta_eraantyneet = "";
if (!isset($nayta_poistetut))     $nayta_poistetut = "";
if (!isset($ojarj))               $ojarj = "";
if (!isset($oletus))              $oletus = "";
if (!isset($osuu))                $osuu = "";
if (!isset($otsikko_lisatiedot))  $otsikko_lisatiedot = "";
if (!isset($otsikko_nappi))       $otsikko_nappi = "";
if (!isset($prospektlisa))        $prospektlisa = "";
if (!isset($ryhma))               $ryhma = "";
if (!isset($tunnus))              $tunnus = "";
if (!isset($ulisa))               $ulisa = "";
if (!isset($upd))                 $upd = "";
if (!isset($uusi))                $uusi = "";
if (!isset($uusilukko))           $uusilukko = "";
if (!isset($alias_set))           $alias_set = "";
if (!isset($rajattu_nakyma))      $rajattu_nakyma = "";
if (!isset($lukossa))             $lukossa = "";
if (!isset($lukitse_laji))        $lukitse_laji = "";
if (!isset($mista))               $mista = "";

// Tutkitaan vähän alias_settejä ja rajattua näkymää
$al_lisa = " and selitetark_2 = 'Default' and nakyvyys != '' ";
$al_lisa_defaultit = " and selitetark_2 = 'Default'";

if ($alias_set != '') {
  if ($rajattu_nakyma != '') {
    $al_lisa = " and selitetark_2 = '{$alias_set}' and nakyvyys != '' ";
    $al_lisa_defaultit = " and selitetark_2 = '{$alias_set}'";
  }
  else {
    $al_lisa = " and (selitetark_2 = '{$alias_set}' or selitetark_2 = 'Default') and nakyvyys != '' ";
    $al_lisa_defaultit = " and (selitetark_2 = '{$alias_set}' or selitetark_2 = 'Default')";
  }
}

$al_lisa_defaultit .= $tunnus == '' ? " and (nakyvyys != '' or selitetark_4 != '') " : " and nakyvyys != '' ";

// pikkuhäkki, ettei rikota css kenttää
if (isset($_POST["toim"]) and $_POST["toim"] == "yhtion_parametrit") {
  if (isset($apucss)) {
    $t[$cssi] = mysql_real_escape_string($apucss);
  }
  if (isset($apucssclassic)) {
    $t[$css_classici] = mysql_real_escape_string($apucssclassic);
  }
  if (isset($apucssextranet)) {
    $t[$css_extraneti] = mysql_real_escape_string($apucssextranet);
  }
  if (isset($apucssverkkokauppa)) {
    $t[$css_verkkokauppai] = mysql_real_escape_string($apucssverkkokauppa);
  }
  if (isset($apuwebseuranta)) {
    $t[$web_seurantai] = mysql_real_escape_string($apuwebseuranta);
  }
}

require "inc/$toim.inc";

if ($otsikko == "") {
  $otsikko = $toim;
}
if ($otsikko_nappi == "") {
  $otsikko_nappi = $toim;
}

echo "<font class='head'>".t("$otsikko")."</font><hr>";

if ($otsikko_lisatiedot != "") {
  echo $otsikko_lisatiedot;
}

// Kun tehdään päivityksiä omasta ikkunasta
js_open_yllapito();

if ($from == "yllapito") {
  echo "
  <script LANGUAGE='JavaScript'>
    function resizeIframe(frameid, maxheight){

      try {
        currentfr=window.parent.document.getElementById(frameid);
      }
      catch (err) {
        currentfr=document.getElementById(frameid);
      }

      currentfr.height = 100;
      currentfr.style.height = 100;

      setTimeout(\"currentfr.style.display='block';\", 1);

      if (currentfr && !window.opera){

        var height = 100;

        try {
          height = currentfr.contentDocument.body.offsetHeight;
        }
        catch (err) {
          height = currentfr.Document.body.scrollHeight;
        }

        if (height > maxheight) {
          height = maxheight;
        }

        currentfr.height = height+20;
        currentfr.style.height = height+20;

        setTimeout(\"currentfr.style.display='block';\", 1);
      }
    }

  </script>";
}

// Saako paivittaa
if ($oikeurow['paivitys'] != '1') {
  if ($uusi == 1) {
    echo "<b>".t("Sinulla ei ole oikeutta lisätä tätä tietoa")."</b><br>";
    $uusi = '';
    exit;
  }
  if ($del == 1 or $del == 2) {
    echo "<b>".t("Sinulla ei ole oikeutta poistaa tätä tietoa")."</b><br>";
    $del = '';
    $tunnus = 0;
    exit;
  }
  if ($upd == 1) {
    echo "<b>".t("Sinulla ei ole oikeutta muuttaa tätä tietoa")."</b><br>";
    $upd = '';
    $uusi = 0;
    $tunnus = 0;
    exit;
  }
}

// Tietue poistetaan
if ($del == 1) {

  $query = "SELECT *
            FROM $toim
            WHERE tunnus = '$tunnus'";
  $result = pupe_query($query);
  $trow = mysql_fetch_array($result);

  $query = "DELETE from $toim
            WHERE tunnus='$tunnus'";
  $result = pupe_query($query);

  // Jos poistamme ifamesta tietoja niin päivitetään varsinaisen tietueen muutospvm, jotta verkkokauppasiirto huomaa, että tietoja on muutettu
  if ($lukitse_avaimeen != "") {
    if ($toim == "tuotteen_avainsanat" or $toim == "tuotteen_toimittajat") {
      $query = "UPDATE tuote
                SET muuttaja = '$kukarow[kuka]', muutospvm=now()
                WHERE yhtio = '$kukarow[yhtio]'
                and tuoteno = '$lukitse_avaimeen'";
      $result = pupe_query($query);
    }
    elseif ($toim == "liitetiedostot" and $lukitse_laji == "tuote") {
      $query = "UPDATE tuote
                SET muuttaja = '$kukarow[kuka]', muutospvm=now()
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$lukitse_avaimeen'";
      $result = pupe_query($query);
    }
  }
  if ($toim == "hinnasto") {
        $query = "UPDATE tuote
                  SET muuttaja = '$kukarow[kuka]', muutospvm=now()
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tuoteno = '$trow[tuoteno]'";
        $result = pupe_query($query);
  }
  synkronoi($kukarow["yhtio"], $toim, $tunnus, $trow, "");

  //  Jos poistetaan perheen osa palataan perheelle
  if ($seuraavatunnus > 0) $tunnus = $seuraavatunnus;
  else $tunnus = 0;

  $seuraavatunnus = 0;
}

if ($del == 2) {
  if (count($poista_check) > 0) {
    foreach ($poista_check as $poista_tunnus) {
      $query = "SELECT *
                FROM $toim
                WHERE tunnus = '$poista_tunnus'";
      $result = pupe_query($query);
      $trow = mysql_fetch_array($result);

      $query = "DELETE from $toim
                WHERE tunnus='$poista_tunnus'";
      $result = pupe_query($query);

      if ($toim == "hinnasto") {
        $query = "UPDATE tuote
                  SET muuttaja = '$kukarow[kuka]', muutospvm=now()
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tuoteno = '$trow[tuoteno]'";
        $result = pupe_query($query);
      }
      synkronoi($kukarow["yhtio"], $toim, $tunnus, $trow, "");
    }
  }
}

// Jotain päivitetään tietokontaan
if ($upd == 1) {

  // Luodaan puskuri, jotta saadaan taulukot kuntoon
  $query = "SELECT *
            FROM $toim
            WHERE tunnus = '$tunnus'";
  $result = pupe_query($query);
  $trow = mysql_fetch_array($result);

  //  Tehdään muuttujista linkit jolla luomme otsikolliset avaimet!
  for ($i=1; $i < mysql_num_fields($result)-1; $i++) {
    if (isset($t["{$i}_uusi"]) and $t["{$i}_uusi"] != "") {
      $t[$i] = $t["{$i}_uusi"];
    }
    $t[mysql_field_name($result, $i)] = &$t[$i];
  }

  // Tarkistetaan
  $errori = '';
  $virhe  = array();

  for ($i=1; $i < mysql_num_fields($result); $i++) {

    //Päivämäärä spesiaali
    if (isset($tpp[$i])) {
      if ($tvv[$i] < 1000 and $tvv[$i] > 0) $tvv[$i] += 2000;

      $t[$i] = sprintf('%04d', $tvv[$i])."-".sprintf('%02d', $tkk[$i])."-".sprintf('%02d', $tpp[$i]);

      if (!@checkdate($tkk[$i], $tpp[$i], $tvv[$i]) and ($tkk[$i]!= 0 or $tpp[$i] != 0)) {
        $virhe[$i] = t("Virheellinen päivämäärä");
        $errori = 1;
      }
    }

    // Tarkistetaan saako käyttäjä päivittää tätä kenttää
    $al_nimi = mysql_field_name($result, $i);

    $query = "SELECT *
              FROM avainsana
              WHERE yhtio = '{$kukarow['yhtio']}'
              and laji    = 'MYSQLALIAS'
              and selite  = '{$toim}.{$al_nimi}'
              {$al_lisa_defaultit}";
    $al_res = pupe_query($query);
    $pakollisuuden_tarkistus_rivi = mysql_fetch_assoc($al_res);

    if (mysql_num_rows($al_res) == 0 and $rajattu_nakyma != '' and isset($t[$i])) {
      $virhe[$i] = t("Sinulla ei ole oikeutta päivittää tätä kenttää");
      $errori = 1;
    }

    if ($tunnus == '' and $t[$i] == '' and $pakollisuuden_tarkistus_rivi['selitetark_4'] != '') {
      $t[$i] = $pakollisuuden_tarkistus_rivi['selitetark_4'];
    }

    $tiedostopaate = "";

    $funktio = $toim."tarkista";

    if (!function_exists($funktio)) {
      require "inc/$funktio.inc";
    }

    if (function_exists($funktio)) {
      @$funktio($t, $i, $result, $tunnus, $virhe, $trow);
    }

    if (isset($virhe[$i]) and $virhe[$i] != "") {
      $errori = 1;
    }

    if (mysql_num_rows($al_res) != 0 and strtoupper($pakollisuuden_tarkistus_rivi['selitetark_3']) == "PAKOLLINEN") {
      if (((mysql_field_type($result, $i) == 'real' or  mysql_field_type($result, $i) == 'int') and (float) str_replace(",", ".", $t[$i]) == 0) or
        (mysql_field_type($result, $i) != 'real' and mysql_field_type($result, $i) != 'int' and trim($t[$i]) == "")) {
        $virhe[$i] .= t("Tieto on pakollinen")."!";
        $errori = 1;
      }
    }

    //  Tarkastammeko liitetiedoston?
    if (is_array($tiedostopaate) and is_array($_FILES["liite_$i"]) and $_FILES["liite_$i"]["size"] > 0) {
      $viesti = tarkasta_liite("liite_$i", $tiedostopaate);

      if ($viesti !== true) {
        $virhe[$i] = $viesti;
        $errori = 1;
      }
    }
  }

  // Jos toimittaja/asiakas merkataan poistetuksi niin unohdetaan kaikki errortsekit...
  if (((isset($toimtyyppi) and $toimtyyppi == "P") or (isset($toimtyyppi) and $toimtyyppi == "PP") or (isset($asiak_laji) and $asiak_laji == "P")) and $errori != '') {
    unset($virhe);
    $errori = "";
  }
  elseif ($errori != '' and isset($yllapitonappi)) {
    echo "<font class='error'>".t("Jossain oli jokin virhe! Ei voitu paivittää!")."</font>";
  }

  // Luodaan tietue
  if ($errori == "") {

    $onko_tama_insert = $tunnus == "" ? true : false;

    if ($onko_tama_insert) {

      // Taulun ensimmäinen kenttä on aina yhtiö
      $query = "INSERT into $toim SET yhtio='$kukarow[yhtio]', laatija='$kukarow[kuka]', luontiaika=now(), muuttaja='$kukarow[kuka]', muutospvm=now() ";

      if ($toim == 'tuotteen_toimittajat' and isset($paivita_tehdas_saldo_paivitetty) and is_array($paivita_tehdas_saldo_paivitetty) and count($paivita_tehdas_saldo_paivitetty) == 2) $query .= ", tehdas_saldo_paivitetty = now() ";

      for ($i=1; $i < mysql_num_fields($result); $i++) {

        // Tuleeko tämä columni käyttöliittymästä
        if (isset($t[$i])) {

          if ($toim == 'tuotteen_toimittajat' and mysql_field_name($result, $i) == 'tehdas_saldo_paivitetty') continue;

          if (is_array($_FILES["liite_$i"]) and $_FILES["liite_$i"]["size"] > 0) {
            $id = tallenna_liite("liite_$i", "Yllapito", 0, "Yhtio", "$toim.".mysql_field_name($result, $i), $t[$i]);

            if ($id !== false) {
              $t[$i] = $id;
            }
          }

          if (mysql_field_type($result, $i) == 'real') {
            $t[$i] = $t[$i] != "NULL" ? "'".(float) str_replace(",", ".", $t[$i])."'" : $t[$i];
            $query .= ", ". mysql_field_name($result, $i)." = {$t[$i]} ";
          }
          elseif (mysql_field_type($result, $i) == 'int' and $t[$i] == "NULL") {
            $query .= ", ". mysql_field_name($result, $i)." = NULL ";
          }
          else {
            $query .= ", ". mysql_field_name($result, $i)." = '".trim($t[$i])."' ";
          }
        }
        else {
          // columni ei tullut käyttöliittymästä, katsotaan onko meillä sille silti joku oletusarvo aliaksissa
          $al_nimi = mysql_field_name($result, $i);

          $oletus_tarkistus_query = "SELECT *
                                     FROM avainsana
                                     WHERE yhtio       = '$kukarow[yhtio]'
                                     and laji          = 'MYSQLALIAS'
                                     and selite        = '$toim.$al_nimi'
                                     and selitetark_2  = '$alias_set'
                                     and selitetark_4 != ''";
          $oletus_tarkistus_result = pupe_query($oletus_tarkistus_query);

          if (mysql_num_rows($oletus_tarkistus_result) == 1) {
            $oletuksen_tarkistus_rivi = mysql_fetch_assoc($oletus_tarkistus_result);
            $oletusarvo = trim($oletuksen_tarkistus_rivi['selitetark_4']);
            $query .= ", $al_nimi = '$oletusarvo' ";
          }
        }
      }
    }
    // Päivitetään
    else {

      //  Jos poistettiin jokin liite, poistetaan se nyt
      if (isset($poista_liite) and is_array($poista_liite)) {
        foreach ($poista_liite as $key => $val) {
          if ($val > 0) {
            $delquery = " DELETE FROM liitetiedostot WHERE yhtio = '$kukarow[yhtio]' and liitos = 'Yllapito' and tunnus = '$val'";
            $delres = pupe_query($delquery);
            if (mysql_affected_rows() == 1) {
              $t[$key] = "";
            }
          }
        }
      }

      // Taulun ensimmäinen kenttä on aina yhtiö
      $query = "UPDATE $toim SET muuttaja='$kukarow[kuka]', muutospvm=now() ";

      if ($toim == 'tuotteen_toimittajat' and isset($paivita_tehdas_saldo_paivitetty) and is_array($paivita_tehdas_saldo_paivitetty) and count($paivita_tehdas_saldo_paivitetty) == 2) $query .= ", tehdas_saldo_paivitetty = now() ";

      $tehdas_saldo_chk = 0;

      for ($i=1; $i < mysql_num_fields($result); $i++) {
        if (isset($t[$i]) or (isset($_FILES["liite_$i"]) and is_array($_FILES["liite_$i"]))) {

          if ($toim == 'tuotteen_toimittajat' and mysql_field_name($result, $i) == 'tehdas_saldo') $tehdas_saldo_chk = $t[$i];

          if ($toim == 'tuotteen_toimittajat' and mysql_field_name($result, $i) == 'tehdas_saldo_paivitetty') {

            if ($trow['tehdas_saldo'] != $tehdas_saldo_chk and (!isset($paivita_tehdas_saldo_paivitetty) or (is_array($paivita_tehdas_saldo_paivitetty) and count($paivita_tehdas_saldo_paivitetty) == 1))) {
              $query .= ", tehdas_saldo_paivitetty = now() ";
            }

            continue;
          }

          if (isset($_FILES["liite_$i"]) and is_array($_FILES["liite_$i"]) and $_FILES["liite_$i"]["size"] > 0) {
            $id = tallenna_liite("liite_$i", "Yllapito", 0, "Yhtio", "$toim.".mysql_field_name($result, $i), $t[$i]);
            if ($id !== false) {
              $t[$i] = $id;
            }
          }

          if ($toim == 'tuote' and mysql_field_name($result, $i) == 'vienti' and !empty($maaryhma_vienti)) {
            $t[$i] = $maaryhma_vienti;
          }

          if (mysql_field_type($result, $i) == 'real') {
            $t[$i] = $t[$i] != "NULL" ? "'".(float) str_replace(",", ".", $t[$i])."'" : $t[$i];

            $query .= ", ". mysql_field_name($result, $i)." = {$t[$i]} ";
          }
          elseif (mysql_field_type($result, $i) == 'int' and $t[$i] == "NULL") {
            $query .= ", ". mysql_field_name($result, $i)." = NULL ";
          }
          else {
            $query .= ", ". mysql_field_name($result, $i)." = '".trim($t[$i])."' ";
          }
        }
      }

      $query .= " where yhtio='$kukarow[yhtio]' and tunnus = $tunnus";
    }

    $result = pupe_query($query);

    if ($onko_tama_insert) {
      $tunnus = mysql_insert_id($GLOBALS["masterlink"]);
    }

    if ($tunnus > 0 and $toim == "tuotteen_toimittajat_tuotenumerot") {

      $query = "SELECT tt.tuoteno, ttt.tuoteno as ttt_tuoteno, toimi.toimittajanro
                FROM tuotteen_toimittajat_tuotenumerot AS ttt
                JOIN tuotteen_toimittajat AS tt ON (tt.yhtio = ttt.yhtio AND tt.tunnus = ttt.toim_tuoteno_tunnus)
                JOIN toimi ON (toimi.yhtio = tt.yhtio AND toimi.tunnus = tt.liitostunnus AND toimi.asn_sanomat IN ('K','L','M','F'))
                WHERE ttt.yhtio = '{$kukarow['yhtio']}'
                AND ttt.tunnus  = '{$tunnus}'";
      $toim_tuoteno_chk_res = pupe_query($query);

      if (mysql_num_rows($toim_tuoteno_chk_res) == 1) {

        $toim_tuoteno_chk_row = mysql_fetch_assoc($toim_tuoteno_chk_res);

        $query = "UPDATE asn_sanomat SET
                  tuoteno               = '{$toim_tuoteno_chk_row['tuoteno']}'
                  WHERE yhtio           = '{$kukarow['yhtio']}'
                  AND status           != 'X'
                  AND tuoteno           = ''
                  AND toim_tuoteno      = '{$toim_tuoteno_chk_row['ttt_tuoteno']}'
                  AND toimittajanumero  = '{$toim_tuoteno_chk_row['toimittajanro']}'";
        $upd_res = pupe_query($query);
      }
    }

    if ($onko_tama_insert and $tunnus > 0 and isset($tee_myos_tuotteen_toimittaja_liitos) and isset($liitostunnus) and $toim == "tuote" and $tee_myos_tuotteen_toimittaja_liitos == 'JOO' and $liitostunnus != '') {

      $query = "SELECT *
                FROM tuote
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$tunnus}'";
      $tuote_chk_res = pupe_query($query);

      $query = "SELECT *
                FROM toimi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$liitostunnus}'";
      $toimi_chk_res = pupe_query($query);

      if (mysql_num_rows($tuote_chk_res) == 1 and mysql_num_rows($toimi_chk_res) == 1) {
        $tuote_chk_row = mysql_fetch_assoc($tuote_chk_res);
        $toimi_chk_row = mysql_fetch_assoc($toimi_chk_res);

        $toimittaja_liitos_ostohinta = pupesoft_cleannumber($toimittaja_liitos_ostohinta);
        $toimittaja_liitos_tuoteno = pupesoft_cleanstring($toimittaja_liitos_tuoteno);

        $query = "INSERT INTO tuotteen_toimittajat SET
                  yhtio        = '{$kukarow['yhtio']}',
                  tuoteno      = '{$tuote_chk_row['tuoteno']}',
                  liitostunnus = '{$liitostunnus}',
                  alkuperamaa  = '{$toimi_chk_row['maa']}',
                  laatija      = '{$kukarow['kuka']}',
                  ostohinta    = '$toimittaja_liitos_ostohinta',
                  toim_tuoteno = '$toimittaja_liitos_tuoteno',
                  luontiaika   = now(),
                  muutospvm    = now(),
                  muuttaja     = '{$kukarow['kuka']}'";
        $tuotteen_toimittaja_insertti = pupe_query($query);
      }
    }

    if ($toim == "tuote") {
      generoi_hinnastot($tunnus);
    }

    $array_chk = array(
      $paivita_myos_avoimet_tilaukset,
      $paivita_myos_toimitustapa,
      $paivita_myos_maksuehto,
      $paivita_myos_kanavointitieto
    );

    if ($tunnus > 0 and count(array_filter($array_chk, 'strlen')) > 0 and $toim == "asiakas") {

      $query = "SELECT *
                FROM asiakas
                WHERE tunnus = '$tunnus'
                and yhtio    = '$kukarow[yhtio]'";
      $otsikres = pupe_query($query);

      if (mysql_num_rows($otsikres) == 1) {
        $otsikrow = mysql_fetch_array($otsikres);

        $query = "SELECT tunnus, tila, alatila, sisviesti1
                  FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
                  WHERE yhtio       = '$kukarow[yhtio]'
                  and (
                      (tila IN ('L','N','R','V','E','C') AND alatila != 'X')
                      OR
                      (tila = 'T' AND alatila in ('','A'))
                      OR
                      (tila IN ('A','0'))
                    )
                  and liitostunnus  = '$otsikrow[tunnus]'
                  and tapvm         = '0000-00-00'";
        $laskuores = pupe_query($query);

        while ($laskuorow = mysql_fetch_array($laskuores)) {

          if (trim($otsikrow["toim_nimi"]) == "") {
            $otsikrow["toim_nimi"]    = $otsikrow["nimi"];
            $otsikrow["toim_nimitark"]  = $otsikrow["nimitark"];
            $otsikrow["toim_osoite"]  = $otsikrow["osoite"];
            $otsikrow["toim_postino"]  = $otsikrow["postino"];
            $otsikrow["toim_postitp"]  = $otsikrow["postitp"];
            $otsikrow["toim_maa"]    = $otsikrow["maa"];
          }

          if (trim($otsikrow["laskutus_nimi"]) == "") {
            $otsikrow["laskutus_nimi"]     = $otsikrow["nimi"];
            $otsikrow["laskutus_nimitark"] = $otsikrow["nimitark"];
            $otsikrow["laskutus_osoite"]   = $otsikrow["osoite"];
            $otsikrow["laskutus_postino"]  = $otsikrow["postino"];
            $otsikrow["laskutus_postitp"]  = $otsikrow["postitp"];
            $otsikrow["laskutus_maa"]     = $otsikrow["maa"];
          }

          $paivita_sisviesti1 = "";

          // Päivitetäänkö sisviesti1?
          if ($trow["sisviesti1"] != "" and $otsikrow["sisviesti1"] != $trow["sisviesti1"] and strpos($laskuorow["sisviesti1"], $trow["sisviesti1"]) !== FALSE) {
            $paivita_sisviesti1 = ", sisviesti1 = replace(sisviesti1, '{$trow["sisviesti1"]}', '{$otsikrow["sisviesti1"]}') ";

          }
          // Lisätään uusi sisviesti1, jos sitä ei vielä ole laskulla
          elseif (strpos($laskuorow["sisviesti1"], $otsikrow["sisviesti1"]) === FALSE) {
            $paivita_sisviesti1 = ", sisviesti1 = trim(concat(sisviesti1,' ', '{$otsikrow["sisviesti1"]}')) ";
          }

          // Ei päivitetää toimitettujen ja rahtikirjasyötettyjen myyntitilausten toimitustapoja
          if ($paivita_myos_toimitustapa != "" and $laskuorow["tila"] != 'L' or ($laskuorow["tila"] == 'L' and ($laskuorow["alatila"] == 'A' or $laskuorow["alatila"] == 'C'))) {
            $query = "UPDATE lasku SET
                      toimitustapa = '{$otsikrow['toimitustapa']}'
                      WHERE yhtio  = '{$kukarow['yhtio']}'
                      and tunnus   = '{$laskuorow['tunnus']}'";
            $updaresult = pupe_query($query);
          }

          if ($paivita_myos_maksuehto != "") {
            $query = "UPDATE lasku SET
                      maksuehto = '{$otsikrow['maksuehto']}'
                      WHERE yhtio  = '{$kukarow['yhtio']}'
                      and tunnus   = '{$laskuorow['tunnus']}'";
            $updaresult = pupe_query($query);
          }

          if ($paivita_myos_kanavointitieto != "") {
            $query = "UPDATE lasku SET
                      chn          = '{$otsikrow['chn']}',
                      verkkotunnus = '{$otsikrow['verkkotunnus']}'
                      WHERE yhtio  = '{$kukarow['yhtio']}'
                      and tunnus   = '{$laskuorow['tunnus']}'";
            $updaresult = pupe_query($query);
          }

          if ($paivita_myos_avoimet_tilaukset) {
            $query = "UPDATE lasku
                      SET ytunnus    = '$otsikrow[ytunnus]',
                      ovttunnus      = '$otsikrow[ovttunnus]',
                      nimi           = '$otsikrow[nimi]',
                      nimitark       = '$otsikrow[nimitark]',
                      osoite         = '$otsikrow[osoite]',
                      postino        = '$otsikrow[postino]',
                      postitp        = '$otsikrow[postitp]',
                      maa            = '$otsikrow[maa]',
                      chn            = '$otsikrow[chn]',
                      verkkotunnus   = '$otsikrow[verkkotunnus]',
                      vienti         = '$otsikrow[vienti]',
                      toim_ovttunnus = '$otsikrow[toim_ovttunnus]',
                      toim_nimi      = '$otsikrow[toim_nimi]',
                      toim_nimitark  = '$otsikrow[toim_nimitark]',
                      toim_osoite    = '$otsikrow[toim_osoite]',
                      toim_postino   = '$otsikrow[toim_postino]',
                      toim_postitp   = '$otsikrow[toim_postitp]',
                      toim_maa       = '$otsikrow[toim_maa]',
                      laskutusvkopv  = '$otsikrow[laskutusvkopv]'
                      $paivita_sisviesti1
                      WHERE yhtio    = '$kukarow[yhtio]'
                      and tunnus     = '$laskuorow[tunnus]'";
            $updaresult = pupe_query($query);

            $query = "UPDATE laskun_lisatiedot
                      SET kolm_ovttunnus  = '$otsikrow[kolm_ovttunnus]',
                      kolm_nimi         = '$otsikrow[kolm_nimi]',
                      kolm_nimitark     = '$otsikrow[kolm_nimitark]',
                      kolm_osoite       = '$otsikrow[kolm_osoite]',
                      kolm_postino      = '$otsikrow[kolm_postino]',
                      kolm_postitp      = '$otsikrow[kolm_postitp]',
                      kolm_maa          = '$otsikrow[kolm_maa]',
                      laskutus_nimi     = '$otsikrow[laskutus_nimi]',
                      laskutus_nimitark = '$otsikrow[laskutus_nimitark]',
                      laskutus_osoite   = '$otsikrow[laskutus_osoite]',
                      laskutus_postino  = '$otsikrow[laskutus_postino]',
                      laskutus_postitp  = '$otsikrow[laskutus_postitp]',
                      laskutus_maa      = '$otsikrow[laskutus_maa]'
                      WHERE yhtio       = '$kukarow[yhtio]'
                      and otunnus       = '$laskuorow[tunnus]'";
            $updaresult = pupe_query($query);
          }
        }
      }
    }

    if ($tunnus > 0 and isset($paivita_myos_avoimet_tilaukset) and $toim == "yhtio") {

      $query = "SELECT *
                FROM yhtio
                WHERE tunnus = '$tunnus'
                and yhtio    = '$kukarow[yhtio]'";
      $otsikres = pupe_query($query);

      if (mysql_num_rows($otsikres) == 1) {
        $otsikrow = mysql_fetch_array($otsikres);

        $query = "SELECT *
                  FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
                  WHERE yhtio = '$kukarow[yhtio]'
                  and (
                      (tila IN ('L','N','R','V','E') AND alatila != 'X')
                      OR
                      (tila = 'T' AND alatila in ('','A'))
                      OR
                      (tila IN ('A','0'))
                    )
                  and tapvm   = '0000-00-00'";
        $laskuores = pupe_query($query);

        while ($laskuorow = mysql_fetch_array($laskuores)) {

          $upda_yhtionimi     = $otsikrow["nimi"];
          $upda_yhtioosoite     = $otsikrow["osoite"];
          $upda_yhtiopostino    = $otsikrow["postino"];
          $upda_yhtiopostitp    = $otsikrow["postitp"];
          $upda_yhtiomaa       = $otsikrow["maa"];
          $upda_yhtioovttunnus   = $otsikrow["ovttunnus"];
          $upda_yhtiokotipaikka  = $otsikrow["kotipaikka"];
          $upda_yhtioalv_tilino  = $otsikrow["alv"];

          if ($laskuorow["maa"] != "" and $laskuorow["maa"] != $otsikrow["maa"]) {
            // tutkitaan ollaanko siellä alv-rekisteröity
            $alhqur = "SELECT vat_numero from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$laskuorow[maa]' and vat_numero != ''";
            $alhire = pupe_query($alhqur);

            // ollaan alv-rekisteröity, aina kotimaa myynti ja alvillista
            if (mysql_num_rows($alhire) == 1) {
              $alhiro = mysql_fetch_assoc($alhire);

              // haetaan maan oletusalvi
              $query = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='ALVULK' and selitetark='o' and selitetark_2='$laskuorow[maa]'";
              $alhire = pupe_query($query);

              if (mysql_num_rows($alhire) == 1) {

                // haetaan sen yhteystiedot
                $alhqur = "SELECT * from yhtion_toimipaikat where yhtio='$kukarow[yhtio]' and maa='$maa' and vat_numero = '$alhiro[vat_numero]'";
                $alhire = pupe_query($alhqur);

                if (mysql_num_rows($alhire) == 1) {
                  $apualvrow  = mysql_fetch_assoc($alhire);

                  $upda_yhtionimi     = $apualvrow["nimi"];
                  $upda_yhtioosoite       = $apualvrow["osoite"];
                  $upda_yhtiopostino      = $apualvrow["postino"];
                  $upda_yhtiopostitp      = $apualvrow["postitp"];
                  $upda_yhtiomaa         = $apualvrow["maa"];
                  $upda_yhtioovttunnus    = $apualvrow["vat_numero"];
                  $upda_yhtiokotipaikka   = $apualvrow["kotipaikka"];
                  $upda_yhtioalv_tilino  = $apualvrow["toim_alv"];
                }
              }
            }
          }

          $query = "UPDATE lasku
                    SET  yhtio_nimi    = '$upda_yhtionimi',
                    yhtio_osoite     = '$upda_yhtioosoite',
                    yhtio_postino    = '$upda_yhtiopostino',
                    yhtio_postitp    = '$upda_yhtiopostitp',
                    yhtio_maa        = '$upda_yhtiomaa',
                    yhtio_ovttunnus  = '$upda_yhtioovttunnus',
                    yhtio_kotipaikka = '$upda_yhtiokotipaikka',
                    alv_tili         = '$upda_yhtioalv_tilino'
                    WHERE yhtio      = '$kukarow[yhtio]'
                    and tunnus       = '$laskuorow[tunnus]'";
          $updaresult = pupe_query($query);
        }
      }
    }

    if ($tunnus > 0 and isset($paivita_myos_avoimet_tilaukset) and $toim == "toimi") {

      $query = "SELECT *
                FROM toimi
                WHERE tunnus = '$tunnus'
                and yhtio    = '$kukarow[yhtio]'";
      $otsikres = pupe_query($query);

      if (mysql_num_rows($otsikres) == 1) {
        $otsikrow = mysql_fetch_array($otsikres);

        $query = "SELECT *
                  FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
                  WHERE yhtio       = '$kukarow[yhtio]'
                  and tila          IN ('H','M','P')
                  and liitostunnus  = '$otsikrow[tunnus]'
                  and tapvm        != '0000-00-00'";
        $laskuores = pupe_query($query);

        while ($laskuorow = mysql_fetch_assoc($laskuores)) {

          if ($yhtiorow['ostolaskujen_paivays'] == "1" and $laskuorow["lapvm"] != '0000-00-00') {
            $ltpp = substr($laskuorow["lapvm"], 8, 2);
            $ltpk = substr($laskuorow["lapvm"], 5, 2);
            $ltpv = substr($laskuorow["lapvm"], 0, 4);
          }
          else {
            $ltpp = substr($laskuorow["tapvm"], 8, 2);
            $ltpk = substr($laskuorow["tapvm"], 5, 2);
            $ltpv = substr($laskuorow["tapvm"], 0, 4);
          }

          if ($otsikrow["oletus_erapvm"] > 0) $oletus_erapvm = date("Y-m-d", mktime(0, 0, 0, $ltpk, $ltpp+$otsikrow["oletus_erapvm"], $ltpv));
          else $oletus_erapvm = $laskuorow["erpcm"];

          if ($otsikrow["oletus_kapvm"] > 0) $oletus_kapvm  = date("Y-m-d", mktime(0, 0, 0, $ltpk, $ltpp+$otsikrow["oletus_kapvm"], $ltpv));
          else $oletus_kapvm = $laskuorow["kapvm"];

          $otsikrow["oletus_kasumma"] = round($laskuorow["summa"] * $otsikrow['oletus_kapro'] / 100, 2);

          if ($otsikrow["oletus_kasumma"] != 0) {
            $otsikrow["oletus_olmapvm"] = $oletus_kapvm;
          }
          else {
            $otsikrow["oletus_olmapvm"] = $oletus_erapvm;
          }

          $komm = "";

          // Jos lasku on hyväksytty ja muutetaan hyväksyntään liittyviä tietoja
          if ($laskuorow["hyvak1"] != "" and $laskuorow["hyvak1"] != "verkkolas" and laskun_hyvaksyjia() and $laskuorow["h1time"] != "0000-00-00 00:00:00" and (
              ($oletus_erapvm > 0 and $laskuorow["erpcm"] != $oletus_erapvm) or
              ($oletus_erapvm > 0 and $laskuorow["kapvm"] != $oletus_kapvm) or
              ($laskuorow["kasumma"] != $otsikrow["oletus_kasumma"]) or
              ($laskuorow["tilinumero"] != $otsikrow["tilinumero"]) or
              ($laskuorow["ultilno"] != $otsikrow["ultilno"]) or
              ($laskuorow["pankki_haltija"] != $otsikrow["pankki_haltija"]) or
              ($laskuorow["swift"] != $otsikrow["swift"]) or
              ($laskuorow["pankki1"] != $otsikrow["pankki1"]) or
              ($laskuorow["pankki2"] != $otsikrow["pankki2"]) or
              ($laskuorow["pankki3"] != $otsikrow["pankki3"]) or
              ($laskuorow["pankki4"] != $otsikrow["pankki4"]) or
              ($laskuorow["hyvaksynnanmuutos"] != $otsikrow["oletus_hyvaksynnanmuutos"]) or
              ($laskuorow["suoraveloitus"] != $otsikrow["oletus_suoraveloitus"]) or
              ($laskuorow["sisviesti1"] != $otsikrow["ohjeitapankille"]))) {

            //echo "<br><table>";
            //echo "<tr><td>Lasku palautetaan hyväksyntään</td><td>$laskuorow[summa]</td></tr>";
            //echo "<tr><td>".$laskuorow["erpcm"]."</td><td>".$oletus_erapvm."</td></tr>";
            //echo "<tr><td>".$laskuorow["kapvm"]."</td><td>".$oletus_kapvm."</td></tr>";
            //echo "<tr><td>".$laskuorow["kasumma"]."</td><td>".$otsikrow["oletus_kasumma"]."</td></tr>";
            //echo "<tr><td>".$laskuorow["tilinumero"]."</td><td>".$otsikrow["tilinumero"]."</td></tr>";
            //echo "<tr><td>".$laskuorow["ultilno"]."</td><td>".$otsikrow["ultilno"]."</td></tr>";
            //echo "<tr><td>".$laskuorow["pankki_haltija"]."</td><td>".$otsikrow["pankki_haltija"]."</td></tr>";
            //echo "<tr><td>".$laskuorow["swift"]."</td><td>".$otsikrow["swift"]."</td></tr>";
            //echo "<tr><td>".$laskuorow["pankki1"]."</td><td>".$otsikrow["pankki1"]."</td></tr>";
            //echo "<tr><td>".$laskuorow["pankki2"]."</td><td>".$otsikrow["pankki2"]."</td></tr>";
            //echo "<tr><td>".$laskuorow["pankki3"]."</td><td>".$otsikrow["pankki3"]."</td></tr>";
            //echo "<tr><td>".$laskuorow["pankki4"]."</td><td>".$otsikrow["pankki4"]."</td></tr>";
            //echo "<tr><td>".$laskuorow["hyvaksynnanmuutos"]."</td><td>".$otsikrow["oletus_hyvaksynnanmuutos"]."</td></tr>";
            //echo "<tr><td>".$laskuorow["suoraveloitus"]."</td><td>".$otsikrow["oletus_suoraveloitus"]."</td></tr>";
            //echo "<tr><td>".$laskuorow["sisviesti1"]."</td><td>".$otsikrow["ohjeitapankille"]."</td></tr>";
            //echo "</table>";

            $laskuorow["tila"]    = "H";

            $laskuorow["hyvak1"]  = $otsikrow["oletus_hyvak1"];
            $laskuorow["hyvak2"]  = $otsikrow["oletus_hyvak2"];
            $laskuorow["hyvak3"]  = $otsikrow["oletus_hyvak3"];
            $laskuorow["hyvak4"]  = $otsikrow["oletus_hyvak4"];
            $laskuorow["hyvak5"]  = $otsikrow["oletus_hyvak5"];

            $laskuorow["h1time"]  = "0000-00-00 00:00:00";
            $laskuorow["h2time"]  = "0000-00-00 00:00:00";
            $laskuorow["h3time"]  = "0000-00-00 00:00:00";
            $laskuorow["h4time"]  = "0000-00-00 00:00:00";
            $laskuorow["h5time"]  = "0000-00-00 00:00:00";

            $laskuorow["hyvaksyja_nyt"] = $otsikrow["oletus_hyvak1"];

            $komm = "(" . $kukarow['nimi'] . "@" . date('Y-m-d') .") ".t("Lasku palautettiin hyväksyntään koska toimittajan tietojen päivitys muutti laskun tietoja.")."<br>";
          }

          // Matkalasku
          if ($laskuorow["tilaustyyppi"] == "M") {
            $query = "SELECT nimi
                      FROM kuka
                      WHERE yhtio = '$kukarow[yhtio]'
                      and kuka    = '$otsikrow[nimi]'";
            $kukores = pupe_query($query);
            $kukorow = mysql_fetch_assoc($kukores);

            $otsikrow_nimi = $kukorow["nimi"];
            $otsikrow_nimitark = t("Matkalasku");
          }
          else {
            $otsikrow_nimi = $otsikrow["nimi"];
            $otsikrow_nimitark = $otsikrow["nimitark"];
          }

          $query = "UPDATE lasku
                    SET erpcm       = '$oletus_erapvm',
                    kapvm             = '$oletus_kapvm',
                    kasumma           = '$otsikrow[oletus_kasumma]',
                    olmapvm           = '$otsikrow[oletus_olmapvm]',
                    hyvak1            = '$laskuorow[hyvak1]',
                    hyvak2            = '$laskuorow[hyvak2]',
                    hyvak3            = '$laskuorow[hyvak3]',
                    hyvak4            = '$laskuorow[hyvak4]',
                    hyvak5            = '$laskuorow[hyvak5]',
                    h1time            = '$laskuorow[h1time]',
                    h2time            = '$laskuorow[h2time]',
                    h3time            = '$laskuorow[h3time]',
                    h4time            = '$laskuorow[h4time]',
                    h5time            = '$laskuorow[h5time]',
                    hyvaksyja_nyt     = '$laskuorow[hyvaksyja_nyt]',
                    ytunnus           = '$otsikrow[ytunnus]',
                    tilinumero        = '$otsikrow[tilinumero]',
                    nimi              = '$otsikrow_nimi',
                    nimitark          = '$otsikrow_nimitark',
                    osoite            = '$otsikrow[osoite]',
                    osoitetark        = '$otsikrow[osoitetark]',
                    postino           = '$otsikrow[postino]',
                    postitp           = '$otsikrow[postitp]',
                    maa               = '$otsikrow[maa]',
                    tila              = '$laskuorow[tila]',
                    ultilno           = '$otsikrow[ultilno]',
                    pankki_haltija    = '$otsikrow[pankki_haltija]',
                    swift             = '$otsikrow[swift]',
                    pankki1           = '$otsikrow[pankki1]',
                    pankki2           = '$otsikrow[pankki2]',
                    pankki3           = '$otsikrow[pankki3]',
                    pankki4           = '$otsikrow[pankki4]',
                    comments          = trim(concat('$komm', comments)),
                    hyvaksynnanmuutos = '$otsikrow[oletus_hyvaksynnanmuutos]',
                    suoraveloitus     = '$otsikrow[oletus_suoraveloitus]',
                    sisviesti1        = '$otsikrow[ohjeitapankille]'
                    WHERE yhtio       = '$kukarow[yhtio]'
                    and tunnus        = '$laskuorow[tunnus]'";
          $updaresult = pupe_query($query);
        }
      }
    }

    // Jos päivitämme ifamesta tietoja niin päivitetään varsinaisen tietueen muutospvm, jotta verkkokauppasiirto huomaa, että tietoja on muutettu
    if (isset($lukitse_avaimeen) and $lukitse_avaimeen != "") {
      if ($toim == "tuotteen_avainsanat" or $toim == "tuotteen_toimittajat") {
        $query = "UPDATE tuote
                  SET muuttaja = '$kukarow[kuka]', muutospvm=now()
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tuoteno = '$lukitse_avaimeen'";
        $result = pupe_query($query);
      }
      elseif ($toim == "liitetiedostot" and $lukitse_laji == "tuote") {
        $query = "UPDATE tuote
                  SET muuttaja = '$kukarow[kuka]', muutospvm=now()
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$lukitse_avaimeen'";
        $result = pupe_query($query);
      }
    }

    //  Tämä funktio tekee myös oikeustarkistukset!
    synkronoi($kukarow["yhtio"], $toim, $tunnus, $trow, "");

    if ($lopetus != '' and (isset($yllapitonappi) or isset($paivita_myos_avoimet_tilaukset))) {
      //unohdetaan tämä jos loopatan takaisin yllapito.php:seen, eli silloin metasta ei ole mitään hyötyä
      if (strpos($lopetus, "yllapito.php") === FALSE) {
        $lopetus .= "//yllapidossa=$toim//yllapidontunnus=$tunnus";
        lopetus($lopetus, "META");
      }
    }

    $uusi = 0;

    if ((isset($yllapitonappi) or isset($paivita_myos_avoimet_tilaukset)) and $lukossa != "ON" or isset($paluunappi)) {
      $tmp_tuote_tunnus  = $tunnus;
      $tunnus  = 0;
    }
  }
}

if ($errori != '' and $_POST["toim"] == "yhtion_parametrit") {
  // jos tuli virhe, niin laitetaan takaisin css:t ilman mysql_real_escape_stringiä
  if (isset($apucss)) {
    $t[$cssi] = $apucss;
  }
  if (isset($apucssclassic)) {
    $t[$css_classici] = $apucssclassic;
  }
  if (isset($apucssextranet)) {
    $t[$css_extraneti] = $apucssextranet;
  }
  if (isset($apucssverkkokauppa)) {
    $t[$css_verkkokauppai] = $apucssverkkokauppa;
  }
  if (isset($apuwebseuranta)) {
    $t[$web_seurantai] = $apuwebseuranta;
  }
}

if ($errori == "" and ($del == 1 or $del == 2 or $upd == 1) and isset($js_open_yp) and $js_open_yp != "") {

  if ($toim == "perusalennus") {
    $query = "SELECT ryhma value, concat_ws(' - ', ryhma, selite) text
              FROM perusalennus
              WHERE tunnus = '$tmp_tuote_tunnus'
              and yhtio    = '$kukarow[yhtio]'";
    $otsikres = pupe_query($query);
    $otsikrow = mysql_fetch_assoc($otsikres);
  }
  elseif ($toim == "avainsana") {
    $query = "SELECT selite value, concat_ws(' ', selite, selitetark) text
              FROM avainsana
              WHERE tunnus = '$tmp_tuote_tunnus'
              and yhtio    = '$kukarow[yhtio]'";
    $otsikres = pupe_query($query);
    $otsikrow = mysql_fetch_assoc($otsikres);
  }
  elseif ($toim == "yhteyshenkilo") {
    $kentta = "nimi";

    if ($js_open_yp == "yhteyshenkilo_tekninen" or $js_open_yp == "yhteyshenkilo_kaupallinen") {
      $kentta = "tunnus";
    }
    $query = "SELECT $kentta value, nimi text
              FROM yhteyshenkilo
              WHERE tunnus = '$tmp_tuote_tunnus'
              and tyyppi   = 'A'
              and yhtio    = '$kukarow[yhtio]'";
    $otsikres = pupe_query($query);
    $otsikrow = mysql_fetch_assoc($otsikres);
  }
  else {
    $otsikrow = array("value" => "", "text" => "");
  }

  echo "  <script LANGUAGE='JavaScript'>

      //  Paivitetaan ja valitaan select option
      var elementti = \"$js_open_yp\";
      var elementit = new Array();
      var ele;
      var newOpt;

      // Yhetyshekiloilla spessujuttu
      if (elementti.substring(0,14) == \"yhteyshenkilo_\") {
        elementit[0] = \"yhteyshenkilo_tekninen\";
        elementit[1] = \"yhteyshenkilo_kaupallinen\";
        elementit[2] = \"yhteyshenkilo_tilaus\";
      }
      else {
        elementit[0] = elementti;
      }

      for (ele in elementit) {

        newOpt = window.opener.document.createElement('option');
        newOpt.text = \"".$otsikrow["text"]."\";
        newOpt.value = \"".$otsikrow["value"]."\";

        sel = window.opener.document.getElementById(elementit[ele]);

        if (sel != null) {
          try {
            sel.add(newOpt, sel.options[1]);
          }
          catch(ex) {
            sel.add(newOpt, 1);
          }

          if (elementit[ele] == elementti) {
            //  Valitaan uusi arvo
            sel.selectedIndex = 1;
          }
        }
      }

      window.close();

      </script>";
}

// Rakennetaan hakumuuttujat kuntoon
if (isset($hakukentat)) {
  $array = explode(",", str_replace(" ", "", $hakukentat));
}
else {
  $array = explode(",", str_replace(" ", "", $kentat));
}

$count = count($array);

for ($i=0; $i<=$count; $i++) {
  if (isset($haku[$i]) and strlen($haku[$i]) > 0) {

    // @-merkki eteen, tarkka haku
    if ($haku[$i]{0} == "@") {
      $tarkkahaku = TRUE;
      $hakuehto = " = '".substr($haku[$i], 1)."' ";
    }
    elseif ($array[$i] == "laskunro") {
      $tarkkahaku = TRUE;
      $hakuehto = " = '{$haku[$i]}' ";
    }
    elseif ($toim == 'asiakas' and $array[$i] == "toimipaikka") {
      $tarkkahaku = FALSE;
      $hakuehto = " = '{$haku[$i]}' ";
    }
    else {
      $tarkkahaku = FALSE;
      $hakuehto = " like '%{$haku[$i]}%' ";
    }

    if ($from == "" and ((($toim == 'rahtisopimukset' or $toim == 'asiakasalennus' or $toim == 'kohde' or $toim == 'asiakashinta') and trim($array[$i]) == 'asiakas') or ($toim == 'yhteyshenkilo' and trim($array[$i]) == 'liitostunnus'))) {
      if (!is_numeric($haku[$i])) {
        $ashak = "SELECT group_concat(tunnus) tunnukset
                  FROM asiakas
                  WHERE yhtio = '$kukarow[yhtio]'
                  and nimi {$hakuehto}";
        $ashakres = pupe_query($ashak);
        $ashakrow = mysql_fetch_assoc($ashakres);

        if ($ashakrow["tunnukset"] != "") {
          $lisa .= " and {$array[$i]} in (" . $ashakrow["tunnukset"] . ")";
        }
        else {
          $lisa .= " and {$array[$i]} = NULL ";
        }
      }
      else {
        $lisa .= " and {$array[$i]} = '{$haku[$i]}' ";
      }
    }
    elseif ($toim == 'asiakas' and $yhtiorow['toimipaikkakasittely'] == 'L' and trim($array[$i]) == "toimipaikka") {
      if (strpos($hakuehto, 'kaikki') === false) $lisa .= " AND asiakas.toimipaikka {$hakuehto} ";
    }
    elseif ($from == "" and $toim == 'toimi' and $alias_set == "KAYTTAJA") {
      $ashak = "SELECT group_concat(concat('\'',kuka,'\'')) kukat
                FROM kuka
                WHERE yhtio = '$kukarow[yhtio]'
                and (nimi {$hakuehto} or kuka {$hakuehto})";
      $ashakres = pupe_query($ashak);
      $ashakrow = mysql_fetch_assoc($ashakres);

      if ($ashakrow["kukat"] != "") {
        $lisa .= " and {$array[$i]} in (" . $ashakrow["kukat"] . ")";
      }
      else {
        $lisa .= " and {$array[$i]} = NULL ";
      }
    }
    elseif (trim($array[$i]) == 'ytunnus' and !$tarkkahaku) {
      $lisa .= " and REPLACE(REPLACE({$array[$i]}, '-', ''), '+', '') like '%".str_replace(array('-', '+'), '', $haku[$i])."%' ";
    }
    elseif ($from == "yllapito" and $toim == "tuotteen_toimittajat_tuotenumerot" and trim($array[$i]) == "tuoteno") {
      $lisa .= " and toim_tuoteno_tunnus {$hakuehto} ";
    }
    elseif ($from == "" and $toim == "tuotteen_toimittajat_tuotenumerot" and trim($array[$i]) == "toim_tuoteno_tunnus") {

      $tutohaku = "SELECT group_concat(tunnus) tunnus
                   FROM tuotteen_toimittajat
                   WHERE yhtio = '$kukarow[yhtio]'
                   and toim_tuoteno $hakuehto";
      $tutores = pupe_query($tutohaku);
      $tutorow = mysql_fetch_assoc($tutores);

      if ($tutorow['tunnus'] != "") {
        $lisa .= " and toim_tuoteno_tunnus in ({$tutorow['tunnus']})";
      }
      else {
        $lisa .= " and toim_tuoteno_tunnus = NULL ";
      }
    }
    elseif ($from == "yllapito" and $toim == "tuotteen_toimittajat_pakkauskoot" and trim($array[$i]) == "pakkauskoko") {
      $lisa .= " and toim_tuoteno_tunnus {$hakuehto} ";
    }
    elseif ($from == "" and $toim == "tuotteen_toimittajat_pakkauskoot" and trim($array[$i]) == "toim_tuoteno_tunnus") {

      $tutohaku = "SELECT group_concat(tunnus) tunnus
                   FROM tuotteen_toimittajat
                   WHERE yhtio = '$kukarow[yhtio]'
                   and toim_tuoteno $hakuehto";
      $tutores = pupe_query($tutohaku);
      $tutorow = mysql_fetch_assoc($tutores);

      if ($tutorow['tunnus'] != "") {
        $lisa .= " and toim_tuoteno_tunnus in ({$tutorow['tunnus']}) ";
      }
      else {
        $lisa .= " and toim_tuoteno_tunnus = NULL ";
      }
    }
    elseif ($from == "yllapito" and ($toim == 'rahtisopimukset' or $toim == 'asiakasalennus' or $toim == 'kohde' or $toim == 'asiakashinta') and trim($array[$i]) == 'asiakas') {

      list($a, $b) = explode("/", $haku[$i]);

      if ((int) $a > 0) $a_lisa .= " asiakas = '$a' ";
      else $a_lisa = "";

      if ((is_numeric($b) and $b > 0) or (!is_numeric($b) and $b != "")) $b_lisa .= " ytunnus = '$b' ";
      else $b_lisa = "";

      if ($a_lisa != "" and $b_lisa != "") {
        $lisa .= " and ($a_lisa or $b_lisa) ";
      }
      elseif ($a_lisa != "") {
        $lisa .= " and $a_lisa ";
      }
      elseif ($b_lisa != "") {
        $lisa .= " and $b_lisa ";
      }
    }
    elseif ($from == "" and $toim == 'tuotteen_toimittajat' and trim($array[$i]) == 'nimi') {
      if (!is_numeric($haku[$i])) {
        $ashak = "SELECT group_concat(tunnus) tunnukset
                  FROM toimi
                  WHERE yhtio = '$kukarow[yhtio]'
                  and nimi {$hakuehto}";
        $ashakres = pupe_query($ashak);
        $ashakrow = mysql_fetch_assoc($ashakres);

        if ($ashakrow["tunnukset"] != "") {
          $lisa .= " and liitostunnus in ({$ashakrow["tunnukset"]})";
        }
        else {
          $lisa .= " and liitostunnus = NULL ";
        }
      }
      else {
        $lisa .= " and liitostunnus = '{$haku[$i]}'";
      }
    }
    elseif ($from == "" and ($toim == 'rahtisopimukset' or $toim == 'asiakasalennus' or $toim == 'kohde' or $toim == 'asiakashinta') and trim($array[$i]) == 'ytunnus') {

      if (!is_numeric($haku[$i])) {
        // haetaan laskutus-asiakas
        $ashak = "SELECT group_concat(distinct concat('\'',ytunnus,'\'')) tunnukset
                  FROM asiakas
                  WHERE yhtio = '$kukarow[yhtio]'
                  and (nimi {$hakuehto} or ytunnus {$hakuehto})";
        $ashakres = pupe_query($ashak);
        $ashakrow = mysql_fetch_assoc($ashakres);

        if ($ashakrow["tunnukset"] != "") {
          $lisa .= " and {$array[$i]} in ({$ashakrow["tunnukset"]})";
        }
        else {
          $lisa .= " and {$array[$i]} = NULL ";
        }
      }
      else {
        $lisa .= " and {$array[$i]} = '{$haku[$i]}'";
      }
    }
    elseif ($toim == 'puun_alkio' and $i == 6) {
      $lisa .= " AND (SELECT nimi FROM dynaaminen_puu WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = puun_alkio.puun_tunnus AND laji = '{$laji}' AND nimi {$hakuehto}) {$hakuehto} ";
    }
    elseif ($toim == 'pakkauskoodit' and ($i == 2 or $i == 1)) {

      if ($i == 2) {
        $lisa .= "AND rahdinkuljettaja {$hakuehto}";
      }
      else {
        $lisa .= "AND pakkaus {$hakuehto}";
      }
    }
    elseif ($toim == 'varaston_hyllypaikat' and ($i == 1 or $i == 2)) {
      if ($i == 2 and $haku[$i] != '') {
        $lisa .= " AND varaston_hyllypaikat.reservipaikka {$hakuehto} ";
      }
      else {
        $lisa .= " AND varaston_hyllypaikat.keraysvyohyke {$hakuehto} ";
      }
    }
    elseif ($toim == 'toimitustavat_toimipaikat' and ($i == 1 or $i == 2)) {
      if ($i == 1) {
        $lisa .= " AND toimitustapa_tunnus {$hakuehto}";
      }
      else {
        if (!is_numeric($haku[$i])) {
          $query = "SELECT tunnus
                    FROM yhtion_toimipaikat
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND nimi LIKE '%{$haku[$i]}%'";
          $toimipaikkares = pupe_query($query);
          $toimipaikkarow = mysql_fetch_assoc($toimipaikkares);

          $lisa .= " AND toimipaikka_tunnus = '{$toimipaikkarow['tunnus']}' ";
        }
        else {
          $lisa .= " AND toimipaikka_tunnus {$hakuehto} ";
        }
      }
    }
    elseif (strpos($array[$i], "/") !== FALSE) {
      $lisa .= " and (";

      foreach (explode("/", $array[$i]) as $spl) {
        $lisa .= "{$spl} {$hakuehto} or ";
      }

      $lisa = substr($lisa, 0, -3).")";
    }
    elseif (($yhtiorow['livetuotehaku_hakutapa'] == "F" or $yhtiorow['livetuotehaku_hakutapa'] == "G") and $toim == 'tuote' and ($array[$i] == "tuoteno" or $array[$i] == "nimitys") and !$tarkkahaku) {
      $lisa .= " and match ($array[$i]) against ('{$haku[$i]}*' IN BOOLEAN MODE) ";
    }
    else {
      $lisa .= " and {$array[$i]} {$hakuehto} ";
    }

    $ulisa .= "&haku[$i]=".urlencode($haku[$i]);
  }
  elseif (!isset($haku[$i])) {
    if ($toim == 'asiakas' and $yhtiorow['toimipaikkakasittely'] == 'L' and trim($array[$i]) == "toimipaikka") {
      if ($kukarow['toimipaikka'] != 0) {
        $lisa .= " AND asiakas.toimipaikka = '{$kukarow['toimipaikka']}' ";
      }
    }
  }
}

//  Säilytetään ohjeen tila
if ($from != "") {
  $ulisa .= "&ohje=off&from=$from&lukitse_avaimeen=".urlencode($lukitse_avaimeen)."&lukitse_laji=$lukitse_laji";
}

//  Pidetään oletukset tallessa!
if (is_array($oletus)) {
  foreach ($oletus as $o => $a) {
    $ulisa.="&oletus[$o]=$a";
  }
}

// Nyt selataan
if ($tunnus == 0 and $uusi == 0 and $errori == '') {
  if (($toim == "asiakasalennus" or $toim == "asiakashinta" or $toim == "hinnasto" or $toim == "puun_alkio") and $oikeurow['paivitys'] == 1) {
    print " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
      <!--

      function toggleAll(toggleBox) {

        var currForm = toggleBox.form;
        var isChecked = toggleBox.checked;
        var nimi = toggleBox.name;

        for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
          if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,3) == nimi) {
            currForm.elements[elementIdx].checked = isChecked;
          }
        }
      }

      function verifyMulti(){
        msg = '".t("Haluatko todella poistaa tietueet?")."';

        if (confirm(msg)) {
          return true;
        }
        else {
          skippaa_tama_submitti = true;
          return false;
        }
      }

      //-->
      </script>";
  }

  if ($limit != "NO") {
    $limiitti = " LIMIT 350";
  }
  else {
    $limiitti = "";
  }

  if (strlen($ojarj) > 0) {
    list($ojar, $osuu) = explode("_", $ojarj);
    $jarjestys = "$ojar $osuu ";
  }
  elseif (!isset($jarjestys)) {
    $jarjestys = "{$toim}.tunnus";
  }

  if ($osuu == '') {
    $osuu  = 'asc';
    $edosuu  = 'asc';
  }
  elseif ($osuu == 'desc') {
    $edosuu = 'asc';
  }
  else {
    $osuu   = 'asc';
    $edosuu = 'desc';
  }

  // Ei näytetä seuraavia avainsanoja avainsana-ylläpitolistauksessa
  $avainsana_query_lisa = $toim == "avainsana" ? " AND laji NOT IN ('MYSQLALIAS', 'HALYRAP', 'SQLDBQUERY', 'KKOSTOT') " : "";

  $query = "SELECT {$kentat}
            FROM $toim
            WHERE yhtio = '$kukarow[yhtio]'
            $lisa
            $rajauslisa
            $prospektlisa
            $avainsana_query_lisa
            $tuote_status_rajaus_lisa
            $ryhma
            ORDER BY $jarjestys
            $limiitti";
  $result = pupe_query($query);


  if ($toim != "yhtio" and $toim != "yhtion_parametrit" and $uusilukko == "") {

    echo "  <form action = 'yllapito.php?ojarj=$ojarj$ulisa";

    if (isset($liitostunnus)) echo "&liitostunnus={$liitostunnus}";

    echo "' method = 'post'>
        <input type = 'hidden' name = 'uusi' value = '1'>
        <input type = 'hidden' name = 'toim' value = '$aputoim'>
        <input type = 'hidden' name = 'mista' value = '{$mista}' />
        <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
        <input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
        <input type = 'hidden' name = 'limit' value = '$limit'>
        <input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
        <input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
        <input type = 'hidden' name = 'laji' value = '$laji'>
        <input type = 'hidden' name = 'lopetus_muut' value = '$lopetus_muut'>
        <input type = 'submit' value = '".t("Uusi $otsikko_nappi")."'></form>";
  }

  if (mysql_num_rows($result) >= 350) {
    echo "  <form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post'>
        <input type = 'hidden' name = 'toim' value = '$aputoim'>
        <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
        <input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
        <input type = 'hidden' name = 'limit' value = 'NO'>
        <input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
        <input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
        <input type = 'hidden' name = 'laji' value = '$laji'>
        <input type = 'submit' value = '".t("Näytä kaikki")."'>";

    if ($toim == "asiakas" and $yhtiorow['toimipaikkakasittely'] == 'L') {
      for ($i = 1; $i < mysql_num_fields($result); $i++) {
        if (mysql_field_name($result, $i) == "toimipaikka" and $haku[$i] != 'kaikki') {
          echo "<input type = 'hidden' name = 'haku[$i]' value = '@{$haku[$i]}'>";
          break;
        }
      }
    }

    echo "</form>";
  }

  if ($toim == "asiakas" or $toim == "toimi" or $toim == "tuote" or $toim == "yriti" or $toim == "lahdot" or $toim == "toimitustavan_lahdot") {
    echo "  <form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post'>
        <input type = 'hidden' name = 'toim' value = '$aputoim'>
        <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
        <input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
        <input type = 'hidden' name = 'limit' value = '$limit'>
        <input type = 'hidden' name = 'nayta_poistetut' value = 'YES'>
        <input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
        <input type = 'hidden' name = 'laji' value = '$laji'>
        <input type = 'submit' value = '".t("Näytä poistetut")."'></form>";
  }

  if ($toim == "asiakasalennus" or $toim == "asiakashinta" or $toim == "hinnasto") {
    echo "  <form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post'>
        <input type = 'hidden' name = 'toim' value = '$aputoim'>
        <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
        <input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
        <input type = 'hidden' name = 'limit' value = 'NO'>
        <input type = 'hidden' name = 'nayta_eraantyneet' value = 'YES'>
        <input type = 'hidden' name = 'laji' value = '$laji'>
        <input type = 'submit' value = '".t("Näytä erääntyneet")."'></form>";
  }

  if (!in_array($yhtiorow['livetuotehaku_hakutapa'], array('F', 'G')) and $toim == "tuote" and $uusi != 1 and $errori == '' and isset($tmp_tuote_tunnus) and $tmp_tuote_tunnus > 0) {

    $query = "SELECT *
              FROM tuote
              WHERE tunnus = '$tmp_tuote_tunnus'";
    $nykyinenresult = pupe_query($query);
    $nykyinentuote = mysql_fetch_array($nykyinenresult);

    $query = "SELECT tunnus
              FROM tuote use index (tuoteno_index)
              WHERE tuote.yhtio = '$kukarow[yhtio]'
              and tuote.tuoteno < '$nykyinentuote[tuoteno]'
              ORDER BY tuoteno desc
              LIMIT 1";
    $noperes = pupe_query($query);
    $noperow = mysql_fetch_array($noperes);


    echo "<form action = 'yllapito.php' method = 'post'>";
    echo "<input type = 'hidden' name = 'toim' value = '$aputoim'>";
    echo "<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>";
    echo "<input type = 'hidden' name = 'limit' value = '$limit'>";
    echo "<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>";
    echo "<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>";
    echo "<input type = 'hidden' name = 'laji' value = '$laji'>";
    echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
    echo "<input type = 'hidden' name = 'tunnus' value = '$noperow[tunnus]'>";
    echo " <input type='submit' value='".t("Edellinen tuote")."'>";
    echo "</form>";

    $query = "SELECT tunnus
              FROM tuote use index (tuoteno_index)
              WHERE tuote.yhtio = '$kukarow[yhtio]'
              and tuote.tuoteno > '$nykyinentuote[tuoteno]'
              ORDER BY tuoteno
              LIMIT 1";
    $yesres = pupe_query($query);
    $yesrow = mysql_fetch_array($yesres);

    echo "<form action = 'yllapito.php' method = 'post'>";
    echo "<input type = 'hidden' name = 'toim' value = '$aputoim'>";
    echo "<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>";
    echo "<input type = 'hidden' name = 'limit' value = '$limit'>";
    echo "<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>";
    echo "<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>";
    echo "<input type = 'hidden' name = 'laji' value = '$laji'>";
    echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
    echo "<input type = 'hidden' name = 'tunnus' value = '$yesrow[tunnus]'>";
    echo " <input type='submit' value='".t("Seuraava tuote")."'>";
    echo "</form>";

  }

  echo "  <br><br><table><tr class='aktiivi'>
      <form action='yllapito.php?ojarj=$ojarj$ulisa' method='post'>
      <input type = 'hidden' name = 'toim' value = '$aputoim'>
      <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
      <input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
      <input type = 'hidden' name = 'limit' value = '$limit'>
      <input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
      <input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
      <input type = 'hidden' name = 'laji' value = '$laji'>";

  if ($from != "" and mysql_num_rows($result) > 0) {
    for ($i = 1; $i < mysql_num_fields($result); $i++) {
      if (strpos(strtoupper(mysql_field_name($result, $i)), "HIDDEN") === FALSE) {
        echo "<th>".t(mysql_field_name($result, $i))."</th>";
      }
    }
  }
  elseif ($from == "") {
    for ($i = 1; $i < mysql_num_fields($result); $i++) {
      if (strpos(strtoupper(mysql_field_name($result, $i)), "HIDDEN") === FALSE) {

        echo "<th><a href='yllapito.php?toim=$aputoim&lopetus=$lopetus&ojarj=".($i+1)."_".$edosuu."$ulisa&limit=$limit&nayta_poistetut=$nayta_poistetut&nayta_eraantyneet=$nayta_eraantyneet&laji=$laji'{$tuote_status_lisa}>" . t(mysql_field_name($result, $i)) . "</a>";

        if (mysql_num_fields($result) <= 6 and mysql_field_len($result, $i) > 10) $size='15';
        elseif (mysql_field_len($result, $i) < 5)  $size='5';
        else $size='10';

        if ($toim == 'varaston_hyllypaikat' and ($i == 1 or $i == 2)) {
          if (!isset($haku[$i])) $haku[$i] = "";

          echo "<br />";
          echo "<select name='haku[{$i}]'>";

          if ($i == 1) {
            echo "<option value=''></option>";

            $query = "SELECT nimitys, tunnus FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' ORDER BY nimitys";
            $keraysvyohyke_chk_res = pupe_query($query);

            while ($keraysvyohyke_chk_row = mysql_fetch_assoc($keraysvyohyke_chk_res)) {

              $sel = (isset($haku[$i]) and $haku[$i] == "@".$keraysvyohyke_chk_row['tunnus']) ? ' selected' : '';

              echo "<option value='@{$keraysvyohyke_chk_row['tunnus']}'{$sel}>{$keraysvyohyke_chk_row['nimitys']}</option>";
            }
          }
          else {

            $sel = array_fill_keys(array($haku[$i]), ' selected') + array('@E' => '', '@K' => '');

            echo "<option value=''></option>";
            echo "<option value='@E'{$sel['@E']}>", t("Ei"), "</option>";
            echo "<option value='@K'{$sel['@K']}>", t("Kyllä"), "</option>";
          }

          echo "</select>";
        }
        elseif ($toim == "toimi" and mysql_field_name($result, $i) == "toimittajaryhma") {
          echo "<br />";
          echo "<select name='haku[{$i}]'>";
          echo "<option value=''></option>";

          $_ryhmares = t_avainsana('TOIMITTAJARYHMA');

          while ($toimittajaryhmarow = mysql_fetch_assoc($_ryhmares)) {
            $sel = (isset($haku[$i]) and $haku[$i] == "@".$toimittajaryhmarow['selite']) ? ' selected' : '';
            $_teksti = $toimittajaryhmarow['selitetark'] != '' ? "{$toimittajaryhmarow['selite']} {$toimittajaryhmarow['selitetark']}" : $toimittajaryhmarow['selite'];
            echo "<option value='@{$toimittajaryhmarow['selite']}'{$sel}>{$_teksti}</option>";
          }

          echo "</select>";
        }
        elseif ($toim == "asiakas" and $yhtiorow['toimipaikkakasittely'] == 'L' and mysql_field_name($result, $i) == "toimipaikka") {

          echo "<br />";
          echo "<select name='haku[{$i}]'>";

          echo "<option value='0'>", t("Ei toimipaikkaa"), "</option>";

          $sel = strtolower($haku[$i]) == "kaikki" ? "selected" : "";

          echo "<option value='kaikki' {$sel}>", t("Kaikki toimipaikat"), "</option>";

          $sel = '';

          $query = "SELECT DISTINCT nimi, tunnus
                    FROM yhtion_toimipaikat
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    ORDER BY nimi";
          $toimipaikka_chk_res = pupe_query($query);

          while ($toimipaikka_chk_row = mysql_fetch_assoc($toimipaikka_chk_res)) {

            $sel = (isset($haku[$i]) and $haku[$i] == "@".$toimipaikka_chk_row['tunnus']) ? ' selected' : '';

            if (!isset($haku[$i]) and $kukarow['toimipaikka'] != 0 and $kukarow['toimipaikka'] == $toimipaikka_chk_row['tunnus']) $sel = 'selected';

            echo "<option value='@{$toimipaikka_chk_row['tunnus']}'{$sel}>{$toimipaikka_chk_row['nimi']}</option>";
          }

          echo "</select>";
        }
        elseif ($toim == "pakkauskoodit") {
          if (strtolower(mysql_field_name($result, $i)) == 'koodi') {
            echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result, $i) ."'>";
          }
          elseif (strtolower(mysql_field_name($result, $i)) == 'rahdinkuljettaja') {
            $query = "SELECT nimi, koodi
                      FROM rahdinkuljettajat
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      ORDER BY nimi";
            $rahdinkuljettaja_chk_res = pupe_query($query);

            echo "<br><select name='haku[{$i}]'>";
            echo "<option value=''>", t("Kaikki rahdinkuljettajat"), "</option>";

            while ($rahkulj_chk_row = mysql_fetch_assoc($rahdinkuljettaja_chk_res)) {
              $sel = (isset($haku[$i]) and $haku[$i] == "@".$rahkulj_chk_row['koodi']) ? ' selected' : '';

              echo "<option value='@{$rahkulj_chk_row['koodi']}'{$sel}>{$rahkulj_chk_row['nimi']}</option>";
            }

            echo "</select>";
          }
          elseif (strtolower(mysql_field_name($result, $i)) == 'pakkaus') {
            $query = "SELECT pakkaus, pakkauskuvaus, tunnus
                      FROM pakkaus
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      ORDER BY pakkaus, pakkauskuvaus";
            $pakkaus_chk_res = pupe_query($query);

            echo "<br><select name='haku[{$i}]'>";
            echo "<option value=''>", t("Kaikki pakkaukset"), "</option>";

            while ($pakkaus_chk_row = mysql_fetch_assoc($pakkaus_chk_res)) {
              $sel = (isset($haku[$i]) and $haku[$i] == "@".$pakkaus_chk_row['tunnus']) ? ' selected' : '';

              echo "<option value='@{$pakkaus_chk_row['tunnus']}'{$sel}>{$pakkaus_chk_row['pakkaus']} - {$pakkaus_chk_row['pakkauskuvaus']}</option>";
            }

            echo "</select>";
          }
        }
        elseif (strpos(strtoupper($array[$i]), "SELECT") === FALSE or ($toim == 'puun_alkio' and strpos(strtoupper($array[$i]), "SELECT") == TRUE)) {
          // jos meidän kenttä ei ole subselect niin tehdään hakukenttä
          if (!isset($haku[$i])) $haku[$i] = "";

          echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result, $i) ."'>";
        }
        echo "</th>";
      }
    }

    if (($toim == "asiakasalennus" or $toim == "asiakashinta" or $toim == "hinnasto" or $toim == "puun_alkio") and $oikeurow['paivitys'] == 1) {
      echo "<th>".t("Poista")."</th>";
    }

    echo "<td class='back' valign='bottom'>&nbsp;&nbsp;<input type='submit' class='hae_btn' value='".t("Etsi")."'></td></form>";
    echo "</tr>";

  }

  if (($toim == "asiakasalennus" or $toim == "asiakashinta" or $toim == "hinnasto" or $toim == "puun_alkio") and $oikeurow['paivitys'] == 1) {
    echo "<tr><form action='yllapito.php?ojarj=$ojarj$ulisa' name='ruksaus' method='post' onSubmit = 'return verifyMulti()'>

        <input type = 'hidden' name = 'toim' value = '$aputoim'>
        <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
        <input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
        <input type = 'hidden' name = 'limit' value = '$limit'>
        <input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
        <input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
        <input type = 'hidden' name = 'laji' value = '$laji'>
        <input type = 'hidden' name = 'del' value = '2'></tr>";
  }

  while ($trow = mysql_fetch_array($result)) {
    echo "<tr class='aktiivi'>";

    if (($toim == "asiakas" and $trow["HIDDEN_laji"] == "P") or
      ($toim == "toimi" and $trow["HIDDEN_tyyppi"] == "P") or
      ($toim == "yriti" and $trow["HIDDEN_kaytossa"] == "E") or
      ($toim == "tuote" and $trow["HIDDEN_status"] == "P") or
      ($toim == "lahdot" and $trow["HIDDEN_aktiivi"] == "E") or
      ($toim == "toimitustavan_lahdot" and $trow["HIDDEN_aktiivi"] == "E")) {

      $fontlisa1 = "<font style='text-decoration: line-through'>";
      $fontlisa2 = "</font>";
    }
    else {
      $fontlisa1 = "";
      $fontlisa2 = "";
    }

    for ($i=1; $i < mysql_num_fields($result); $i++) {
      if (strpos(strtoupper(mysql_field_name($result, $i)), "HIDDEN") === FALSE) {

        // Ei näytetä henkilötunnuksen loppuosaa selausnäkymässä
        if (stripos(mysql_field_name($result, $i), "ytunnus") !== FALSE) {
          $trow[$i] = tarkistahetu($trow[$i]);
        }

        if ($i == 1) {
          if (trim($trow[1]) == '' or (is_float($trow[1]) and $trow[1] == 0)) $trow[1] = t("*tyhjä*");

          echo "<td><a name='$trow[0]' href='yllapito.php?mista=$mista&ojarj=$ojarj$ulisa&toim=$aputoim&tunnus=$trow[0]&limit=$limit&nayta_poistetut=$nayta_poistetut&nayta_eraantyneet=$nayta_eraantyneet&laji=$laji{$tuote_status_lisa}";

          if ($from == "" and $lopetus == "") {
            echo "&lopetus=".$palvelin2."yllapito.php////mista=$mista//ojarj=$ojarj".str_replace("&", "//", $ulisa)."//toim=$aputoim//limit=$limit//nayta_poistetut=$nayta_poistetut//nayta_eraantyneet=$nayta_eraantyneet//laji=$laji///$trow[0]";
          }
          elseif ($lopetus == "" and $lopetus_muut != "") {
            echo "&lopetus_muut=$lopetus_muut";
          }
          else {
            echo "&lopetus=$lopetus";
          }

          echo "'>";

          if (mysql_field_name($result, $i) == 'liitedata') {

            if ($lukitse_laji == "tuote" and $lukitse_avaimeen > 0 and in_array($trow[1], array("image/jpeg", "image/jpg", "image/gif", "image/png", "image/bmp"))) {
              echo "<img src='".$palvelin2."view.php?id=$trow[0]' height='80px'><br>".t("Muokkaa liitettä");
            }
            else {
              list($liitedata1, $liitedata2) = explode("/", $trow[1]);

              $path_parts = pathinfo($trow[4]);
              $ext = $path_parts['extension'];

              if (file_exists("pics/tiedostotyyppiikonit/".strtoupper($liitedata2).".ico")) {
                echo "<img src='".$palvelin2."pics/tiedostotyyppiikonit/".strtoupper($liitedata2).".ico' height='80px'><br>".t("Muokkaa liitettä");
              }
              elseif (file_exists("pics/tiedostotyyppiikonit/".strtoupper($ext).".ico")) {
                echo "<img src='".$palvelin2."pics/tiedostotyyppiikonit/".strtoupper($ext).".ico' height='80px'><br>".t("Muokkaa liitettä");
              }
              else {
                echo $trow[1]."<br>".t("Muokkaa liitettä");
              }
            }
          }
          elseif (mysql_field_name($result, $i) == 'toim_tuoteno_tunnus') {
            $query = "SELECT tt.toim_tuoteno
                      FROM tuotteen_toimittajat AS tt
                      WHERE tt.yhtio = '{$kukarow['yhtio']}'
                      AND tt.tunnus  = '{$trow[$i]}'";
            $toim_tuoteno_chk_res = pupe_query($query);
            $toim_tuoteno_chk_row = mysql_fetch_assoc($toim_tuoteno_chk_res);

            echo $toim_tuoteno_chk_row['toim_tuoteno'];
          }
          else {
            echo $trow[1];
          }

          echo "</a></td>";
        }
        else {
          if (mysql_field_type($result, $i) == 'real' or mysql_field_type($result, $i) == 'int') {
            echo "<td style='text-align:right'>$fontlisa1 $trow[$i] $fontlisa2</td>";
          }
          elseif (mysql_field_name($result, $i) == 'koko') {
            echo "<td>$fontlisa1 ".size_readable($trow[$i])." $fontlisa2</td>";
          }
          elseif (mysql_field_name($result, $i) == 'toim_tuoteno') {
            echo "<td>$fontlisa1 $trow[$i] $fontlisa2</td>";
          }
          else {

            if (!function_exists("ps_callback")) {
              function ps_callback($matches) {
                return tv1dateconv($matches[0]);
              }
            }

            $trow[$i] = preg_replace_callback("/[0-9]{4}\-[0-9]{2}\-[0-9]{2}/", "ps_callback", $trow[$i]);

            echo "<td>$fontlisa1 $trow[$i] $fontlisa2</td>";
          }
        }
      }
    }

    if ($from == "" and ($toim == "asiakasalennus" or $toim == "asiakashinta" or $toim == "hinnasto" or $toim == "puun_alkio") and $oikeurow['paivitys'] == 1) {
      echo "<td><input type = 'checkbox' name = 'poista_check[]' value = '$trow[0]'></td>";
    }

    echo "</tr>";
  }

  if ($from == "" and ($toim == "asiakasalennus" or $toim == "asiakashinta" or $toim == "hinnasto" or $toim == "puun_alkio") and $oikeurow['paivitys'] == 1) {
    $span = mysql_num_fields($result)-2;
    echo "<tr>";
    echo "<td class='back'><input type = 'submit' value = '".t("Poista ruksatut tietueet")."'></td>";
    echo "<td class='back' colspan='$span' align='right'>".t("Ruksaa kaikki")."</td>";
    echo "<td class='back'><input type = 'checkbox' name = 'poi' onclick='toggleAll(this)'></td>";
    echo "</tr>";

    echo "</form>";
  }

  echo "</table>";
}

// Nyt näytetään vanha tai tehdään uusi(=tyhjä)
if ($tunnus > 0 or $uusi != 0 or $errori != '') {
  if ($oikeurow['paivitys'] != 1) {
    echo "<b>".t("Sinulla ei ole oikeuksia päivittää tätä tietoa")."</b><br>";
  }

  if ($from == "") {
    $ankkuri = "#$tunnus";
  }
  else {
    $ankkuri = "";
  }

  if ($toim == "lasku" or $toim == "laskun_lisatiedot") {
    echo "<SCRIPT LANGUAGE=JAVASCRIPT>
          function verify(){
            msg = '".t("Oletko varma, että haluat muuttaa kirjanpitoaineiston tietoja jälkikäteen")."?';

            if (confirm(msg)) {
              return true;
            }
            else {
              skippaa_tama_submitti = true;
              return false;
            }
          }
      </SCRIPT>";

    $javalisasubmit = "onSubmit = 'return verify()'";
  }
  else {
    $javalisasubmit = "";
  }

  echo "<form action = 'yllapito.php?ojarj=$ojarj$ulisa$ankkuri' name='mainform' id='mainform' method = 'post' autocomplete='off' $javalisasubmit enctype='multipart/form-data'>";
  echo "<input type = 'hidden' name = 'toim' value = '$aputoim'>";
  echo "<input type = 'hidden' name = 'mista' value = '{$mista}' />";
  echo "<input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>";
  echo "<input type = 'hidden' name = 'limit' value = '$limit'>";
  echo "<input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>";
  echo "<input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>";
  echo "<input type = 'hidden' name = 'laji' value = '$laji'>";
  echo "<input type = 'hidden' name = 'tunnus' value = '$tunnus'>";
  echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
  echo "<input type = 'hidden' name = 'upd' value = '1'>";

  if (isset($status) and $toim == 'tuote') {
    echo "<input type = 'hidden' name = 'status' value = '$status'>";
  }

  // Kokeillaan geneeristä
  $query = "SELECT *
            FROM $toim
            WHERE tunnus = '$tunnus'";
  $result = pupe_query($query);
  $trow = mysql_fetch_array($result);

  echo "<table><tr><td class='back pnopad ptop'>";

  echo "<table>";

  if ($uusi == '1' and $toim == 'tuote') {

    $query = "SELECT tunnus, nimi, toimittajanro
              FROM toimi
              WHERE yhtio       = '{$kukarow['yhtio']}'
              AND pikaperustus != ''
              AND tyyppi       != 'P'
              ORDER BY nimi";
    $toimiresult = pupe_query($query);

    if (mysql_num_rows($toimiresult) > 0) {

      echo "<input type='hidden' name='tee_myos_tuotteen_toimittaja_liitos' value='JOO'>";
      echo "<tr>";
      echo "<th align='left'>".t("Toimittaja")."</th>";
      echo "<td>";
      echo "<select name='liitostunnus' />";
      echo "<option value=''>".t("Ei toimittajaa")."</option>";

      while ($toimirow = mysql_fetch_assoc($toimiresult)) {
        $selected = (isset($liitostunnus) and $toimirow['tunnus'] == $liitostunnus) ? 'SELECTED': '';
        $toimittajanrolisa = (trim($toimirow['toimittajanro']) != '') ? "(".$toimirow['toimittajanro'].")" : '';
        echo "<option value='{$toimirow['tunnus']}' {$selected}> {$toimirow['nimi']} $toimittajanrolisa</option>";
      }

      echo "</select>";
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th align='left'>".t("Ostohinta")."</th>";
      echo "<td>";
      echo "<input type='text' size='35' name='toimittaja_liitos_ostohinta'>";
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th align='left'>".t("Toimittajan tuotenumero")."</th>";
      echo "<td>";
      echo "<input type='text' size='35' name='toimittaja_liitos_tuoteno'>";
      echo "</td>";
      echo "</tr>";
    }
  }

  for ($i=0; $i < mysql_num_fields($result) - 1; $i++) {

    // Intrastat_kurssi kenttä näytetään vain jos yrityksen maa on EE
    if ($yhtiorow['maa'] != 'EE' and mysql_field_name($result, $i) == 'intrastat_kurssi') {
      continue;
    }

    $nimi = "t[$i]";

    if (isset($t[$i])) {
      $trow[$i] = $t[$i];
    }
    // Haetaan passatut oletukset arvoiksi!
    elseif ($uusi == 1) {
      if (isset($oletus[mysql_field_name($result, $i)])) {
        $trow[$i] = $oletus[mysql_field_name($result, $i)];
      }
    }

    if (strlen($trow[$i]) > 35) {
      $size = strlen($trow[$i])+2;
    }
    elseif (mysql_field_len($result, $i)>10) {
      $size = '35';
    }
    elseif (mysql_field_len($result, $i)<5) {
      $size = '5';
    }
    else {
      $size = '10';
    }

    $maxsize = mysql_field_len($result, $i); // Jotta tätä voidaan muuttaa

    //Haetaan tietokantasarakkeen nimialias
    $al_nimi   = mysql_field_name($result, $i);
    $al_row    = array();

    $query = "SELECT *
              FROM avainsana
              WHERE yhtio = '$kukarow[yhtio]'
              and laji    = 'MYSQLALIAS'
              and selite  = '$toim.$al_nimi'
              $al_lisa";
    $al_res = pupe_query($query);

    if (mysql_num_rows($al_res) > 0) {
      $al_row = mysql_fetch_array($al_res);

      if ($al_row["selitetark"] != '') {
        $otsikko = str_ireplace("(BR)", "<br>", t($al_row["selitetark"]));
      }
      else {
        $otsikko = t(mysql_field_name($result, $i));
      }

      // jos ollaan tekemässä uutta tietuetta ja meillä on mysql-aliaksista oletusarvo
      if ($tunnus == "" and $trow[$i] == "" and $al_row["selitetark_4"] != "") {
        $trow[$i] = $al_row["selitetark_4"];
      }
    }
    else {
      switch (mysql_field_name($result, $i)) {
      case "printteri0":
        $otsikko = t("Keräyslista");
        break;
      case "printteri1":
        $otsikko = t("Lähete");
        break;
      case "printteri2":
        $otsikko = t("Tuotetarrat");
        break;
      case "printteri3":
        $otsikko = t("Osoitelappu");
        break;
      case "printteri4":
        $otsikko = t("Rahtikirja A5");
        break;
      case "printteri5":
        $otsikko = t("Lasku");
        break;
      case "printteri6":
        $otsikko = t("Rahtikirja A4");
        break;
      case "printteri7":
        $otsikko = t("JV-lasku/-kuitti");
        break;
      case "printteri8":
        $otsikko = t("Reittietiketti");
        break;
      case "printteri9":
        $otsikko = t("Reklamaatioiden ja siirtolistojen vastaanoton purkulista");
        break;
      case "printteri10":
        $otsikko = t("Lämpösiirto");
        break;
      case "isa_varasto":
        $otsikko = t("Isävarasto");
        break;
      default:
        if (isset($mysqlaliasarraysetti) and isset($mysqlaliasarray[$mysqlaliasarraysetti][mysql_field_name($result, $i)])) {
          $otsikko = t($mysqlaliasarray[$mysqlaliasarraysetti][mysql_field_name($result, $i)]);
        }
        else {
          $otsikko = t(mysql_field_name($result, $i));
        }
      }
    }

    require "inc/$toim"."rivi.inc";

    if (mysql_num_rows($al_res) == 0 and $rajattu_nakyma != '') {
      $ulos = "";
      $tyyppi = 0;
    }

    // Näitä kenttiä ei ikinä saa päivittää käyttöliittymästä
    if (mysql_field_name($result, $i) == "laatija" or
      mysql_field_name($result, $i) == "muutospvm" or
      mysql_field_name($result, $i) == "muuttaja" or
      mysql_field_name($result, $i) == "luontiaika") {
      $tyyppi = 2;
    }

    // $tyyppi --> 0 riviä ei näytetä ollenkaan
    // $tyyppi --> 1 rivi näytetään normaalisti
    // $tyyppi --> 1.5 rivi näytetään normaalisti ja se on päivämääräkenttä
    // $tyyppi --> 2 rivi näytetään, mutta sitä ei voida muokata, eikä sen arvoa pävitetä (riviä ei näytetä kun tehdään uusi)
    // $tyyppi --> 3 rivi näytetään, mutta sitä ei voida muokata, mutta sen arvo päivitetään (riviä ei näytetä kun tehdään uusi)
    // $tyyppi --> 4 riviä ei näytetä ollenkaan, mutta sen arvo päivitetään
    // $tyyppi --> 5 liitetiedosto

    if ($tyyppi == 1 or
      $tyyppi == 1.5 or
      ($tyyppi == 2 and $tunnus!="") or
      ($tyyppi == 3 and $tunnus!="")  or
      $tyyppi == 5) {
      echo "<tr>";

      $infolinkki = "";

      // Jos riviltä löytyy selitetark_5 niin piirretään otsikon perään tooltip-kysymysmerkki
      if (!empty($al_row) and $al_row['selitetark_5'] != '') {
        $siistiselite = str_replace('.', '_', $al_row['selite']);
        $infolinkki = "<div style='float: right;'><a class='tooltip' id='{$al_row['tunnus']}_{$siistiselite}'><img src='{$palvelin2}pics/lullacons/info.png'></a></div>";

        // Tehdään helppi-popup
        echo "<div id='div_{$al_row['tunnus']}_{$siistiselite}' class='popup'>{$al_row['selitetark']}<br><br>{$al_row['selitetark_5']}</div>";
      }
      echo "<th align='left'>$otsikko $infolinkki</th>";
    }

    if ($jatko == 0) {
      echo $ulos;
    }
    elseif ($tyyppi == 1) {
      echo "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'></td>";
    }
    elseif ($tyyppi == 1.5) {
      $vva = substr($trow[$i], 0, 4);
      $kka = substr($trow[$i], 5, 2);
      $ppa = substr($trow[$i], 8, 2);

      echo "<td>
          <input type = 'text' name = 'tpp[$i]' value = '$ppa' size='3' maxlength='2'>
          <input type = 'text' name = 'tkk[$i]' value = '$kka' size='3' maxlength='2'>
          <input type = 'text' name = 'tvv[$i]' value = '$vva' size='5' maxlength='4'></td>";
    }
    elseif ($tyyppi == 2 and $tunnus != "") {
      echo "<td>$trow[$i]</td>";
    }
    elseif ($tyyppi == 3 and $tunnus != "") {
      echo "<td>$trow[$i]<input type = 'hidden' name = '$nimi' value = '$trow[$i]'></td>";
    }
    elseif ($tyyppi == 4) {
      echo "<input type = 'hidden' name = '$nimi' value = '$trow[$i]'>";
    }
    elseif ($tyyppi == 5) {
      echo "<td>";

      if ($trow[$i] > 0) {
        echo "<a href='view.php?id=".$trow[$i]."' target='Attachment'>".t("Näytä liitetiedosto")."</a><input type = 'hidden' name = '$nimi' value = '$trow[$i]'> ".("Poista").": <input type = 'checkbox' name = 'poista_liite[$i]' value = '$trow[$i]'>";
      }
      else {
        echo "<input type = 'text' name = '$nimi' value = '$trow[$i]'>";
      }

      echo "<input type = 'file' name = 'liite_$i'></td>";
    }

    if (isset($virhe[$i])) {
      echo "<td class='back'><font class='error'>$virhe[$i]</font></td>\n";
    }

    if ($tyyppi == 1 or
      $tyyppi == 1.5 or
      ($tyyppi == 2 and $tunnus!="") or
      ($tyyppi == 3 and $tunnus!="")  or
      $tyyppi == 5) {
      echo "</tr>";
    }
  }
  echo "</table>";

  if ($uusi == 1) {
    $nimi = t("Perusta $otsikko_nappi");
  }
  else {
    $nimi = t("Päivitä $otsikko_nappi");
  }

  echo "<br><input type = 'submit' name='yllapitonappi' value = '{$nimi}'>";

  if (($toim == "asiakas" or $toim == "yhtio") and $uusi != 1) {
    echo "<br><br>";

    $chktxt = "{$nimi} ".t("ja päivitä tiedot myös avoimille tilauksille");
    echo "<input type='checkbox' name='paivita_myos_avoimet_tilaukset' value='OK'> {$chktxt}";
    echo "<div id='div_paivita_myos_avoimet_tilaukset_popup' class='popup' style='width: 400px;'>";
    echo t("Päivitettävät kentät");
    echo "<ul>";

    if ($toim == "yhtio") {
      $paivitettavat_kentat = array(
        'yhtio_nimi',
        'yhtio_osoite',
        'yhtio_postino',
        'yhtio_postitp',
        'yhtio_maa',
        'yhtio_ovttunnus',
        'yhtio_kotipaikka',
        'alv_tili',
      );
    }
    else {
      $paivitettavat_kentat = array(
        'ytunnus',
        'ovttunnus',
        'nimi',
        'nimitark',
        'osoite',
        'postino',
        'postitp',
        'maa',
        'chn',
        'verkkotunnus',
        'vienti',
        'toim_ovttunnu',
        'toim_nimi',
        'toim_nimitark',
        'toim_osoite',
        'toim_postino',
        'toim_postitp',
        'toim_maa',
        'laskutusvkopv',
        'kolm_ovttunnus',
        'kolm_nimi',
        'kolm_nimitark',
        'kolm_osoite',
        'kolm_postino',
        'kolm_postitp',
        'kolm_maa',
        'laskutus_nimi',
        'laskutus_nimitark',
        'laskutus_osoite',
        'laskutus_postino',
        'laskutus_postitp',
        'laskutus_maa',
      );
    }

    foreach ($paivitettavat_kentat as $kentta) {
      echo "<li>".ucfirst($kentta)."</li>";
    }

    echo "</ul>";
    echo "</div>";

    echo "&nbsp;<img src='{$palvelin2}pics/lullacons/info.png' class='tooltip' id='paivita_myos_avoimet_tilaukset_popup' />";

    if ($toim == "asiakas") {
      $chktxt = t("Päivitä myös toimitustapa avoimille tilauksille");
      echo "<br><input type = 'checkbox' name='paivita_myos_toimitustapa' value = 'OK'> {$chktxt}";

      $chktxt = t("Päivitä myös maksuehto avoimille tilauksille");
      echo "<br><input type = 'checkbox' name='paivita_myos_maksuehto' value = 'OK'> {$chktxt}";

      $chktxt = t("Päivitä vain verkkolaskutunnus ja kanavointitieto avoimille tilauksille");
      echo "<br><input type = 'checkbox' name='paivita_myos_kanavointitieto' value = 'OK'> {$chktxt}";
    }
  }
  if ($toim == "toimi" and $uusi != 1) {
    $chktxt = "{$nimi} ".t("ja päivitä tiedot myös avoimille laskuille");
    echo "<br><input type='checkbox' name='paivita_myos_avoimet_tilaukset' value='OK'> {$chktxt}";
    echo "<div id='div_paivita_myos_avoimet_tilaukset_popup' class='popup' style='width: 400px;'>";
    echo t("Päivitettävät kentät");
    echo "<ul>";

    $paivitettavat_kentat = array(
      'erpcm',
      'kapvm',
      'kasumma',
      'olmapvm',
      'hyvak1',
      'hyvak2',
      'hyvak3',
      'hyvak4',
      'hyvak5',
      'h1time',
      'h2time',
      'h3time',
      'h4time',
      'h5time',
      'hyvaksyja_nyt',
      'ytunnus',
      'tilinumero',
      'nimi',
      'nimitark',
      'osoite',
      'osoitetark',
      'postino',
      'postitp',
      'maa',
      'tila',
      'ultilno',
      'pankki_haltija',
      'swift',
      'pankki1',
      'pankki2',
      'pankki3',
      'pankki4',
      'comments',
      'hyvaksynnanmuutos',
      'suoraveloitus',
      'sisviesti1',
    );

    foreach ($paivitettavat_kentat as $kentta) {
      echo "<li>".ucfirst($kentta)."</li>";
    }

    echo "</ul>";
    echo "</div>";

    echo "&nbsp;<img src='{$palvelin2}pics/lullacons/info.png' class='tooltip' id='paivita_myos_avoimet_tilaukset_popup' />";

  }

  if ($lukossa == "ON") {
    echo "<input type='hidden' name='lukossa' value = '{$lukossa}'>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "<input type = 'submit' name='paluunappi' value = '".t("Palaa avainsanoihin")."'>";
  }

  echo "</td>";
  echo "<td class='back pnopad ptop'>";

  if ($errori == '' and $toim == "sarjanumeron_lisatiedot") {
    @include "inc/arviokortti.inc";
  }

  // Ylläpito.php:n formi kiinni vasta tässä
  echo "</form>";

  $lopetus_muut  = $palvelin2;
  $lopetus_muut .= "yllapito.php?toim=$toim";
  $lopetus_muut .= "//tunnus=$trow[tunnus]";

  if (!empty($lopetus)) {
    $lopetus_muut = "$lopetus/SPLIT/$lopetus_muut";
  }

  if ($trow["tunnus"] > 0 and $errori == '' and $toim == "yhtio") {
    echo "<iframe id='yhtion_toimipaikat_iframe' name='yhtion_toimipaikat_iframe' src='yllapito.php?toim=yhtion_toimipaikat&from=yllapito&ohje=off&haku[4]=@$trow[yhtio]&lukitse_avaimeen=$trow[yhtio]' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
  }

  if ($trow["tunnus"] > 0 and $errori == '' and $toim == "lasku") {
    echo "<iframe id='laskun_lisatiedot_iframe' name='laskun_lisatiedot_iframe' src='yllapito.php?toim=laskun_lisatiedot&from=yllapito&ohje=off&haku[1]=@$trow[tunnus]&lukitse_avaimeen=$trow[tunnus]' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
  }

  if ($trow["tunnus"] > 0 and $errori == '' and $toim == "asiakas") {

    if (($toikrow = tarkista_oikeus("yllapito.php", "asiakasalennus%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='asiakasalennus_iframe' name='asiakasalennus_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&ohje=off&haku[1]=$trow[tunnus]/$trow[ytunnus]&lukitse_avaimeen=$trow[tunnus]' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "asiakashinta%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='asiakashinta_iframe' name='asiakashinta_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&ohje=off&haku[1]=$trow[tunnus]/$trow[ytunnus]&lukitse_avaimeen=$trow[tunnus]' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "asiakaskommentti%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='asiakaskommentti_iframe' name='asiakaskommentti_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&ohje=off&haku[1]=@$trow[ytunnus]&lukitse_avaimeen=$trow[ytunnus]' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "asiakkaan_avainsanat%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='asiakkaan_avainsanat_iframe' name='asiakkaan_avainsanat_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&ohje=off&haku[5]=@$trow[tunnus]&lukitse_avaimeen=$trow[tunnus]' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "directdebit_asiakas%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='directdebit_asiakas_iframe' name='directdebit_asiakas_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&ohje=off&haku[1]=@$trow[tunnus]&lukitse_avaimeen=$trow[tunnus]' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "puun_alkio&laji=asiakas%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='puun_alkio_iframe' name='puun_alkio_iframe' src='yllapito.php?toim=$toikrow[alanimi]&lukitse_laji=asiakas&from=yllapito&ohje=off&haku[1]=@$trow[tunnus]&lukitse_avaimeen=$trow[tunnus]&lopetus_muut=$lopetus_muut' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "puun_alkio&laji=tuote&mista=asiakas%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='puun_alkio_iframe' name='puun_alkio_iframe' src='yllapito.php?toim={$toikrow['alanimi']}&mista=asiakas&lukitse_laji=tuote&from=yllapito&ohje=off&haku[1]=@{$trow['tunnus']}&lukitse_avaimeen={$trow['tunnus']}&lopetus_muut=$lopetus_muut' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
      echo "<br />";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "rahtisopimukset%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='rahtisopimukset_iframe' name='rahtisopimukset_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&ohje=off&haku[1]=$trow[tunnus]/$trow[ytunnus]&lukitse_avaimeen=$trow[tunnus]' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }
    if (($toikrow = tarkista_oikeus("yllapito.php", "kohde%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='kohde_iframe' name='kohde_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&ohje=off&haku[2]=$trow[tunnus]&lukitse_avaimeen=$trow[tunnus]' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }
  }

  if ($trow["tunnus"] > 0 and $errori == '' and ($toim == "toimi" or $toim == "asiakas")) {

    if (($toikrow = tarkista_oikeus("yllapito.php", "yhteyshenkilo%", "", "OK", $toimi_array)) !== FALSE) {

      if ($toim == "asiakas") {
        $laji = "A";
      }
      elseif ($toim == "toimi") {
        $laji = "T";
      }

      echo "<iframe id='yhteyshenkilo_iframe' name='yhteyshenkilo_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&laji=$laji&ohje=off&haku[2]=@$tunnus&lukitse_avaimeen=$tunnus' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }
  }

  if ($trow["tunnus"] > 0 and $errori == '' and $toim == "toimi") {

    if (($toikrow = tarkista_oikeus("yllapito.php", "vaihtoehtoiset_verkkolaskutunnukset%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='vaihtoehtoiset_verkkolaskutunnukset_iframe' name='vaihtoehtoiset_verkkolaskutunnukset_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&laji=$laji&ohje=off&haku[3]=@$tunnus&lukitse_avaimeen=$tunnus' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }
  }

  if ($trow["tunnus"] > 0 and $errori == '' and ($toim == "toimitustapa" or ($toim == "avainsana" and $from != "yllapito"))) {

    if (isset($perhe) and $perhe > 0) {
      $la_tunnus = $perhe;
    }
    else {
      $la_tunnus = $tunnus;
    }

    if ($toim == "toimitustapa") {
      $laji = "TOIMTAPAKV";
      $urilisa = "&haku[3]=@$tunnus";
    }
    elseif ($toim == "avainsana") {
      $laji = $al_laji;
      $urilisa = "&haku[8]=@$la_tunnus";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "avainsana%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='avainsana_iframe' name='avainsana_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&lukitse_laji=$laji&ohje=off&haku[2]=@$laji$urilisa&lukitse_avaimeen=$la_tunnus' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }
  }

  if ($trow["tunnus"] > 0 and $errori == '' and $toim == "yhteensopivuus_tuote") {
    if (($toikrow = tarkista_oikeus("yllapito.php", "yhteensopivuus_tuote_lisatiedot%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='yhteensopivuus_tuote_lisatiedot_iframe' name='yhteensopivuus_tuote_lisatiedot_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&haku[5]=@$tunnus&lukitse_avaimeen=$tunnus' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }
  }

  if ($trow["tunnus"] > 0 and $errori == '' and $toim == "toimitustapa") {
    if (($toikrow = tarkista_oikeus("yllapito.php", "toimitustavan_lahdot%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='toimitustavan_lahdot_iframe' name='toimitustavan_lahdot_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&haku[1]=@$tunnus&ohje=off&lukitse_avaimeen=$tunnus' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }
  }

  if ($trow["tunnus"] > 0 and $errori == '' and $from != "yllapito" and ($toim == 'lasku' or $toim == 'asiakas' or $toim == "sarjanumeron_lisatiedot" or $toim == "tuote" or $toim == "avainsana" or $toim == "toimi")) {
    if (($toikrow = tarkista_oikeus("yllapito.php", "liitetiedostot%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='liitetiedostot_iframe' name='liitetiedostot_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&ohje=off&haku[7]=@$toim&haku[8]=@$tunnus&lukitse_avaimeen=$tunnus&lukitse_laji=$toim' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }
  }

  if ($trow["tunnus"] > 0 and $errori == '' and $toim == 'toimitustapa') {
    if (($toikrow = tarkista_oikeus("yllapito.php", "toimitustavat_toimipaikat%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<br>";
      echo "<iframe id='toimitustavat_iframe' name='toimitustavat_iframe' src='yllapito.php?toim=toimitustavat_toimipaikat&from=yllapito&ohje=off&haku[1]=@{$tunnus}&lukitse_avaimeen={$tunnus}' style='width: 600px; height: 300px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }
  }

  if ($trow["tunnus"] > 0 and $errori == "" and $from != "yllapito" and $toim == "tuote" and $laji != "V") {

    $lukitse_avaimeen = urlencode($tuoteno);

    if (($toikrow = tarkista_oikeus("yllapito.php", "tuotteen_toimittajat%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='tuotteen_toimittajat_iframe' name='tuotteen_toimittajat_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&ohje=off&haku[1]=@$lukitse_avaimeen&lukitse_avaimeen=$lukitse_avaimeen' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame><br />";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "toimittajaalennus%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='toimittajaalennus_iframe' name='toimittajaalennus_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&ohje=off&haku[3]=@$lukitse_avaimeen&lukitse_avaimeen=$lukitse_avaimeen' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "toimittajahinta%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='toimittajahinta_iframe' name='toimittajahinta_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&ohje=off&haku[3]=@$lukitse_avaimeen&lukitse_avaimeen=$lukitse_avaimeen' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "tuotteen_avainsanat%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='tuotteen_avainsanat_iframe' name='tuotteen_avainsanat_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&ohje=off&haku[1]=@$lukitse_avaimeen&lukitse_avaimeen=$lukitse_avaimeen' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "puun_alkio&laji=tuote%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='puun_alkio_iframe' name='puun_alkio_iframe' src='yllapito.php?toim=$toikrow[alanimi]&lukitse_laji=tuote&from=yllapito&ohje=off&haku[1]=@$lukitse_avaimeen&lukitse_avaimeen=$lukitse_avaimeen&lopetus_muut=$lopetus_muut' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "hinnasto%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='hinnasto_iframe' name='hinnasto_iframe' src='yllapito.php?toim=$toikrow[alanimi]&lukitse_laji=tuote&from=yllapito&ohje=off&haku[1]=@$lukitse_avaimeen&lukitse_avaimeen=$lukitse_avaimeen&lopetus_muut=$lopetus_muut' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }
  }

  if ($trow["tunnus"] > 0 and $errori == '' and $toim == "auto_vari") {
    if (($toikrow = tarkista_oikeus("yllapito.php", "auto_vari_tuote%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='auto_vari_tuote_iframe' name='auto_vari_tuote_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&haku[1]=@$trow[varikoodi]&ohje=off&lukitse_avaimeen=$trow[varikoodi]' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }
    if (($toikrow = tarkista_oikeus("yllapito.php", "auto_vari_korvaavat%", "", "OK", $toimi_array)) !== FALSE) {
      echo "<iframe id='auto_vari_korvaavat_iframe' name='auto_vari_korvaavat_iframe' src='yllapito.php?toim=$toikrow[alanimi]&from=yllapito&haku[1]=@$trow[varikoodi]&ohje=off&lukitse_avaimeen=$trow[varikoodi]' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
    }
  }

  if ($trow["tunnus"] > 0 and $errori == '' and $toim == "asiakas") {

    $query = "SELECT kuka.kuka, kuka.nimi
              FROM kuka
              WHERE kuka.yhtio        = '$kukarow[yhtio]'
              AND kuka.aktiivinen     = 1
              AND kuka.oletus_asiakas = {$trow["tunnus"]}
              ORDER BY kuka.nimi";
    $extkukares = pupe_query($query);

    if (mysql_num_rows($extkukares) > 0) {

      echo "<br><font class='head'>".t("Extranet-käyttäjät")."</font><hr>";
      echo "<table>";
      echo "<tr><th>".t("Käyttäjätunnus")."</th><th>".t("Nimi")."</th></tr>";

      while ($extkukarow = mysql_fetch_assoc($extkukares)) {
        echo "<tr><td>{$extkukarow["kuka"]}</td><td>{$extkukarow["nimi"]}</td></tr>";
      }

      echo "</table>";
    }
  }

  echo "</td></tr>";

  // Määritellään mitä tietueita saa poistaa
  if ($toim == "auto_vari" or
    $toim == "auto_vari_tuote" or
    $toim == "auto_vari_korvaavat" or
    $toim == "autoid_lisatieto" or
    $toim == "puun_alkio" or
    $toim == "toimitustavan_lahdot" or
    $toim == "pakkauskoodit" or
    $toim == "keraysvyohyke" or
    $toim == "avainsana" or
    $toim == "asiakasalennus" or
    $toim == "asiakashinta" or
    $toim == "perusalennus" or
    $toim == "yhteensopivuus_tuote" or
    $toim == "yhteensopivuus_tuote_lisatiedot" or
    ($toim == "toimitustapa" and $poistolukko == "") or
    $toim == "toimitustavat_toimipaikat" or
    $toim == "hinnasto" or
    $toim == "rahtimaksut" or
    $toim == "rahtisopimukset" or
    $toim == "etaisyydet" or
    $toim == "tuotteen_avainsanat" or
    $toim == "toimittajaalennus" or
    $toim == "vaihtoehtoiset_verkkolaskutunnukset" or
    $toim == "toimittajahinta" or
    $toim == "varaston_tulostimet" or
    $toim == "asiakaskommentti" or
    $toim == "yhteyshenkilo" or
    $toim == "autodata_tuote" or
    $toim == "korvaavat_kiellot" or
    $toim == "tuotteen_toimittajat" or
    $toim == "tuotteen_toimittajat_pakkauskoot" or
    $toim == "tuotteen_toimittajat_tuotenumerot" or
    $toim == "extranet_kayttajan_lisatiedot" or
    $toim == "asiakkaan_avainsanat" or
    $toim == "directdebit_asiakas" or
    $toim == "rahtisopimukset" or
    $toim == "hyvityssaannot" or
    $toim == "varaston_hyllypaikat" or
    $toim == "tuotteen_orginaalit" or
    $toim == "yhtion_toimipaikat_parametrit" or
    $toim == "kohde" or
    ($toim == "liitetiedostot" and $poistolukko == "") or
    ($toim == "tuote" and $poistolukko == "") or
    ($toim == "toimi" and $kukarow["taso"] == "3")) {

    // Tehdään "poista tietue"-nappi
    if ($uusi != 1 and $toim != "yhtio" and $toim != "yhtion_parametrit") {
      echo "<SCRIPT LANGUAGE=JAVASCRIPT>
            function verify(){
              msg = '".t("Haluatko todella poistaa tämän tietueen?")."';

              if (confirm(msg)) {
                return true;
              }
              else {
                skippaa_tama_submitti = true;
                return false;
              }
            }
        </SCRIPT>";

      if ($rajattu_nakyma == '' or $rajattu_nakyma == "true_poisto") {

        if (!isset($seuraavatunnus)) $seuraavatunnus = 0;

        echo "<tr><td class='back pnopad'>";
        echo "<br />
          <form action = 'yllapito.php?ojarj=$ojarj$ulisa' method = 'post' onSubmit = 'return verify()' enctype='multipart/form-data'>
          <input type = 'hidden' name = 'toim' value = '$aputoim'>
          <input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
          <input type = 'hidden' name = 'limit' value = '$limit'>
          <input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
          <input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
          <input type = 'hidden' name = 'laji' value = '$laji'>
          <input type = 'hidden' name = 'tunnus' value = '$tunnus'>
          <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
          <input type = 'hidden' name = 'del' value ='1'>
          <input type='hidden' name='seuraavatunnus' value = '$seuraavatunnus'>
          <input type = 'submit' class='poista_btn' value = '".t("Poista $otsikko_nappi")."'>
          </form>";
        echo "</td></tr>";
      }
    }
  }

  if ($trow["tunnus"] > 0 and $errori == '' and $toim == 'tuotteen_toimittajat') {

    if (($toikrow = tarkista_oikeus("yllapito.php", "tuotteen_toimittajat_pakkauskoot%", "", "OK", $toimi_array)) !== FALSE) {
      $lukitse_avaimeen = urlencode($toim_tuoteno_tunnus);

      echo "<tr><td class='back'></td></tr>";
      echo "<tr><td class='back'>";
      echo "<iframe id='tuotteen_toimittajat_pakkauskoot_iframe' name='tuotteen_toimittajat_pakkauskoot_iframe' src='yllapito.php?toim={$toikrow['alanimi']}&from=yllapito&ohje=off&haku[1]=@{$lukitse_avaimeen}&lukitse_avaimeen={$lukitse_avaimeen}' style='width: 600px; border: 0px; display: block;' border='0' frameborder='0'></iFrame>";
      echo "</td></tr>";
    }

    if (($toikrow = tarkista_oikeus("yllapito.php", "tuotteen_toimittajat_tuotenumerot%", "", "OK", $toimi_array)) !== FALSE) {
      $lukitse_avaimeen = urlencode($toim_tuoteno_tunnus);

      echo "<tr><td class='back'></td></tr>";
      echo "<tr><td class='back'>";
      echo "<iframe id='tuotteen_toimittajat_tuotenumerot_iframe' name='tuotteen_toimittajat_tuotenumerot_iframe' src='yllapito.php?toim={$toikrow['alanimi']}&from=yllapito&ohje=off&haku[1]=@{$lukitse_avaimeen}&lukitse_avaimeen={$lukitse_avaimeen}' style='width: 600px; border: 0px; display: block;' frameborder='0'></iFrame>";
      echo "</td></tr>";
    }
  }

  echo "</table>";
}
elseif ($toim != "yhtio" and $toim != "yhtion_parametrit"  and $uusilukko == "" and $from == "") {
  echo "<br>
      <form action = 'yllapito.php?ojarj=$ojarj$ulisa";
  if (isset($liitostunnus)) echo "&liitostunnus={$liitostunnus}";
  if (isset($status) and $toim == 'tuote') echo "&status={$status}";
  echo "' method = 'post'>

      <input type = 'hidden' name = 'toim' value = '$aputoim'>
      <input type = 'hidden' name = 'js_open_yp' value = '$js_open_yp'>
      <input type = 'hidden' name = 'limit' value = '$limit'>
      <input type = 'hidden' name = 'nayta_poistetut' value = '$nayta_poistetut'>
      <input type = 'hidden' name = 'nayta_eraantyneet' value = '$nayta_eraantyneet'>
      <input type = 'hidden' name = 'laji' value = '$laji'>
      <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
      <input type = 'hidden' name = 'uusi' value = '1'>
      <input type = 'submit' value = '".t("Uusi $otsikko_nappi")."'></form>";
}

if ($from == "yllapito") {
  if ((int) $tunnus == 0 and (int) $uusi == 0 and $errori == '') {
    $jcsmaxheigth = ", 300";
  }
  else {
    $jcsmaxheigth = "";
  }

  echo "<script LANGUAGE='JavaScript'>resizeIframe('{$toim}_iframe' $jcsmaxheigth);</script>";
}
elseif ($from != "yllapito") {
  require "inc/footer.inc";
}
