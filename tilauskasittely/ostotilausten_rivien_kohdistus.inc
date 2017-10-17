<?php

if (!isset($hiddenit))           $hiddenit = "";
if (!isset($nayta_siirrettavat)) $nayta_siirrettavat = "";
if (!isset($ulisa))              $ulisa = "";
if (!isset($varaosavirhe))       $varaosavirhe = "";
if (!isset($lisa1))              $lisa1 = "";
if (!isset($tilaajanrivinro))    $tilaajanrivinro = "";
if (!isset($haku))               $haku = "";
if (!isset($ala_kohdista_mitaan))$ala_kohdista_mitaan = "";
if (!isset($kohdista_koko_sivu)) $kohdista_koko_sivu = "";
if (!isset($nayta_kohdistetut))  $nayta_kohdistetut = "";
if (!isset($nayta_kaikki))       $nayta_kaikki = "";
if (!isset($ojarj))              $ojarj = "";
if (!isset($tila))               $tila = "";

$query  = "SELECT *
           FROM lasku
           WHERE tunnus  = '$otunnus'
           AND yhtio     = '$kukarow[yhtio]'
           AND tila      = 'K'
           AND alatila  != 'X'";
$result = pupe_query($query);

if (mysql_num_rows($result) == 1) {
  $laskurow = mysql_fetch_assoc($result);
}
else {
  echo "<font style='error'>".t("VIRHE: Saapumista ei en‰‰ lˆydy")."!</font>";
  exit;
}

echo "  <SCRIPT LANGUAGE=JAVASCRIPT>
      function submit_tallenna(){
        document.paaformi.action='$PHP_SELF';
        document.paaformi.submit();
      }
      function submit_kohd_kaikki(){
        document.paaformi.action='$PHP_SELF?kohdista_koko_sivu=1';
        document.paaformi.submit();
      }
      function submit_kohd_eimit(){
        document.paaformi.action='$PHP_SELF?ala_kohdista_mitaan=1';
        document.paaformi.submit();
      }

    </SCRIPT>";

//Kukarow kesken p‰ivitet‰‰n kohdistuksessa jotta kukaan ei voi samalla vied‰ varaston ja tehd‰ kohdistuksia
if ($kukarow["kesken"] == 0 or $kukarow["kesken"] != $laskurow["tunnus"]) {
  $query = "UPDATE kuka
            SET kesken = '$laskurow[tunnus]'
            WHERE yhtio = '$kukarow[yhtio]' AND
            kuka        = '$kukarow[kuka]' AND
            session     = '$session'";
  $result = pupe_query($query);
}

// katsotaan onko t‰lle keikalle jo liitetty vaihto-omaisuuslaskuja (kotimaa, eu tai ei-eu)
$kulurow = saapumisen_laskut($laskurow);

//nyt kohdistus on onnistunut sentilleen. Ollan valmiita ja tulostetaan purkulistat
if ($tila == 'valmis') {

  // p‰ivitet‰‰n kohdistettu=K kun kohdistus on pennilleen ok...
  if ($laskunsumma == 0 and ($laskurow["summa"] == 0 or $kulurow["abssumma"] != 0)) {
    $query = "UPDATE lasku
              SET kohdistettu='K'
              WHERE tunnus='$otunnus'
              and yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);
  }

  $query = "  UPDATE tilausrivi set toimaika='$laskurow[toimaika]' WHERE otunnus='$otunnus' and tyyppi='O' and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  //Vapautetaan 'tilaus'
  $query = "UPDATE kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
  $result = pupe_query($query);

  $otunnus  = "";
  $toiminto = "";
  $tee     = "";
  $ytunnus  = $laskurow["ytunnus"];
}

//muokataan tilausrivi‰
if ($tila == 'muokkaa_rivi' and $tee != 'TI') {

  $query = "SELECT tilausrivi.*, tuote.sarjanumeroseuranta, tuote.myyntihinta_maara
            FROM tilausrivi use index (PRIMARY)
            LEFT JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
            WHERE tilausrivi.tunnus = '$riviid'
            and tilausrivi.yhtio    = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo t("Tilausrivi‰ ei lˆydy")."! $query";
    exit;
  }
  $trow = mysql_fetch_assoc($result);

  $query = "DELETE from tilausrivi
            WHERE tunnus = '$riviid' and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  // Tehd‰‰n pari juttua jos tuote on sarjanuerosaurannassa
  if ($trow["sarjanumeroseuranta"] != '') {

    //Nollataan sarjanumero
    if ($trow["varattu"] > 0) {
      $tunken = "ostorivitunnus";
    }
    else {
      $tunken = "myyntirivitunnus";
    }

    $query = "SELECT group_concat(tunnus) tunnukset
              FROM sarjanumeroseuranta
              WHERE yhtio = '$kukarow[yhtio]'
              and tuoteno = '$trow[tuoteno]'
              and $tunken = '$trow[tunnus]'";
    $sarjares = pupe_query($query);
    $sarjarow = mysql_fetch_assoc($sarjares);

    //Pidet‰‰n sarjatunnus muistissa
    $osto_sarjatunnus = $sarjarow["tunnukset"];

    $query = "UPDATE sarjanumeroseuranta
              SET $tunken = 0
              WHERE yhtio = '$kukarow[yhtio]'
              and tuoteno = '$trow[tuoteno]'
              and $tunken = '$trow[tunnus]'";
    $sarjares = pupe_query($query);
  }


  $tuoteno     = $trow["tuoteno"];
  $hinta       = $trow["hinta"];
  $kpl       = $trow["varattu"];
  $toimaika     = $trow["toimaika"];
  $kerayspvm    = $trow["kerayspvm"];
  $alv       = $trow["alv"];
  $var       = $trow["var"];
  $perheid2    = $trow["perheid2"];
  $kommentti     = $trow["kommentti"];
  $rivinotunnus  = $trow["otunnus"];
  $rivitunnus    = $riviid;

  if ($trow["myyntihinta_maara"] != 0 and $hinta != 0) {
    $hinta = hintapyoristys($hinta * $trow["myyntihinta_maara"]);
  }

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    ${'ale'.$alepostfix} = $trow["ale{$alepostfix}"];
  }

  echo t("Muuta rivi‰").":<br>";

  //pidet‰‰n kaikki muuttujat tallessa
  $muut_siirrettavat = $alku."!°!".$nayta_kohdistetut."!°!".$ojarj."!°!".$nayta_kaikki."!°!".$toimittajaid;

  for ($i = 0; $i < 10; $i++) {
    if (isset($haku[$i]) and $haku[$i] != '') {
      $muut_siirrettavat .= "!°!$i#".$haku[$i];
    }
  }

  //rivien splittausvaihtoehtot n‰kyviin
  $automatiikka = 'ON';

  $saapuminen_ostorivin_muokkaus = 1;

  require 'syotarivi_ostotilaus.inc';
  require '../inc/footer.inc';
  exit;
}

//Lis‰t‰‰n sarjanumeroita
if ($tila == "sarjanumerot") {
  //pidet‰‰n kaikki muuttujat tallessa
  $muut_siirrettavat = $alku."!°!".$nayta_kohdistetut."!°!".$ojarj."!°!".$nayta_kaikki."!°!".$toimittajaid;

  for ($i = 0; $i < 10; $i++) {
    if (isset($haku[$i]) and $haku[$i] != '') {
      $muut_siirrettavat .= "!°!$i#".$haku[$i];
    }
  }

  $PHP_SELF = "sarjanumeroseuranta.php";

  require "sarjanumeroseuranta.php";
  exit;
}

//kohdistetaan tiedostosta
if ($tila == 'FAILISTA') {
  require 'ostotilausten_rivien_kohdistus_tiedostosta.inc';
}

//annetaan mahis tehd‰ kokonaan uusi rivi
if ($tila == 'uusirivi') {

  // Liitet‰‰n rivi t‰h‰n is‰riviin
  if ($perheid2 > 0) {
    $query = "UPDATE tilausrivi SET perheid2='$perheid2' where yhtio='$kukarow[yhtio]' and tunnus='$perheid2' and tyyppi='O'";
    $result = pupe_query($query);
  }

  echo t("Tee uusi rivi").":<br>";

  //pidet‰‰n kaikki muuttujat tallessa
  $muut_siirrettavat = $alku."!°!".$nayta_kohdistetut."!°!".$ojarj."!°!".$nayta_kaikki."!°!".$toimittajaid;

  if (is_array($haku)) {
    foreach ($haku as $i => $hakusana) {
      if (isset($haku[$i]) and $haku[$i] != '') {
        $muut_siirrettavat .= "!°!$i#".$haku[$i];
      }
    }
  }

  $rivinotunnus = $otunnus;
  $toimaika = date('Y')."-".date('m')."-".date('d');

  require 'syotarivi_ostotilaus.inc';
  require '../inc/footer.inc';
  exit;
}

//poistetaan rivi, tyhjennet‰‰n muuttujat, n‰ytet‰‰n lista
if ($tee == 'TI' and isset($tyhjenna)) {

  unset($tila);
  unset($tee);

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    unset(${'ale'.$alepostfix});
    unset(${'ale_array'.$alepostfix});
    unset(${'kayttajan_ale'.$alepostfix});
  }

  unset($alv);
  unset($alv_array);
  unset($hinta);
  unset($hinta_array);
  unset($kayttajan_alv);
  unset($kayttajan_hinta);
  unset($kayttajan_kpl);
  unset($kayttajan_netto);
  unset($kayttajan_var);
  unset($kerayspvm);
  unset($kommentti);
  unset($kpl);
  unset($kpl_array);
  unset($netto);
  unset($netto_array);
  unset($paikat);
  unset($paikka);
  unset($paikka_array);
  unset($perheid);
  unset($perheid2);
  unset($rivinumero);
  unset($rivitunnus);
  unset($toimaika);
  unset($tuotenimitys);
  unset($tuoteno);
  unset($var);
  unset($variaatio_tuoteno);
  unset($var_array);
}

//tarkastetaan tilausrivi
if ($tee == 'TI' or $tee == "Y") {
  // Parametreja joita tarkistarivi tarvitsee
  $toim_tarkistus   = "EI";
  $prow["tuoteno"]   = $tuoteno;
  $prow["var"]    = $rivinvar;
  $kukarow["kesken"]  = $rivinotunnus;

  $kpl   = str_replace(',', '.', $kpl);
  $hinta   = str_replace(',', '.', $hinta);

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    ${'ale'.$alepostfix} = str_replace(',', '.', ${'ale'.$alepostfix});
  }

  if (checkdate($toimkka, $toimppa, $toimvva)) {
    $toimaika = $toimvva."-".$toimkka."-".$toimppa;
  }

  $query = "SELECT myyntihinta_maara
            FROM tuote
            WHERE yhtio  = '$kukarow[yhtio]'
            AND  tuoteno = '$prow[tuoteno]'";
  $result = pupe_query($query);
  $trow = mysql_fetch_assoc($result);

  if ($hinta == "") {
    $query = "SELECT *
              FROM tuotteen_toimittajat
              WHERE tuoteno    = '$prow[tuoteno]'
              and yhtio        = '$kukarow[yhtio]'
              and liitostunnus = '$laskurow[liitostunnus]'";
    $rarres1 = pupe_query($query);
    $hinrow1 = mysql_fetch_assoc($rarres1);

    $prow["hinta"] = $hinrow1["ostohinta"];
  }
  else {
    $prow["hinta"] = $hinta;
  }

  if ($trow["myyntihinta_maara"] != 0) {
    $prow["hinta"] = round($prow["hinta"] / $trow["myyntihinta_maara"], $yhtiorow["hintapyoristys"]);
  }
  else {
    $prow["hinta"] = round($prow["hinta"], $yhtiorow["hintapyoristys"]);
  }

  $multi = "";
  require "../inc/tuotehaku.inc";

  $prow["tuoteno"]      = $tuoteno;
  $oikeusostohintapaiv  = tarkista_oikeus("yllapito.php", "tuotteen_toimittajat", "check");

  require 'tarkistarivi_ostotilaus.inc';

  //n‰ytet‰‰n virhe ja annetaan mahis korjata se
  if ($varaosavirhe != '' or $tuoteno == "") {

    //rivien splittausvaihtoehtot n‰kyviin
    $automatiikka = 'ON';

    echo t("Muuta rivi‰").":<br>";
    require 'syotarivi_ostotilaus.inc';
    require '../inc/footer.inc';
    exit;
  }
}

//rivi on tarkistettu ja se lisataan tietokantaan
if ($varaosavirhe == '' and ($tee == "TI" or $tee == "Y") and $tuoteno != '') {
  $kukarow["kesken"] = $rivinotunnus;

  if (!is_array($tuoteno_array) and trim($tuoteno) != "") {
    $tuoteno_array[] = $tuoteno;
  }

  //K‰ytt‰j‰n syˆtt‰m‰ hinta ja ale ja netto, pit‰‰ s‰ilˆ‰ jotta tuotehaussakin voidaan syˆtt‰‰ n‰m‰
  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    ${'kayttajan_ale'.$alepostfix} = ${'ale'.$alepostfix};
  }

  $kayttajan_hinta  = $hinta;
  $kayttajan_netto   = $netto;
  $kayttajan_var    = $var;
  $kayttajan_kpl    = $kpl;
  $kayttajan_alv    = $alv;

  foreach ($tuoteno_array as $tuoteno) {

    $query  = "SELECT *
               FROM tuote
               WHERE tuoteno = '$tuoteno'
               and yhtio     = '$kukarow[yhtio]'
               and ei_saldoa = ''";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      //Tuote lˆytyi
      $trow = mysql_fetch_assoc($result);
    }
    else {
      //Tuotetta ei lˆydy, arvataan muutamia muuttujia
      $trow["alv"] = $laskurow["alv"];
    }

    if (checkdate($toimkka, $toimppa, $toimvva)) {
      $toimaika = $toimvva."-".$toimkka."-".$toimppa;
    }
    if (checkdate($kerayskka, $keraysppa, $keraysvva)) {
      $kerayspvm = $keraysvva."-".$kerayskka."-".$keraysppa;
    }
    if ($toimaika == "" or $toimaika == "0000-00-00") {
      $toimaika = $laskurow["toimaika"];
    }
    if ($kerayspvm == "" or $kerayspvm == "0000-00-00") {
      $kerayspvm = $laskurow["kerayspvm"];
    }

    if ($kukarow['oletus_ostovarasto'] != 0) {
      $varasto = $kukarow["oletus_ostovarasto"];
    }
    elseif ($kukarow['oletus_varasto'] != 0) {
      $varasto = $kukarow["oletus_varasto"];
    }
    else {
      $varasto = $laskurow["varasto"];
    }

    //Tehd‰‰n muuttujaswitchit
    if (is_array($hinta_array)) {
      $hinta = $hinta_array[$tuoteno];
    }
    else {
      $hinta = $kayttajan_hinta;
    }

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
      if (is_array(${'ale_array'.$alepostfix})) {
        ${'ale'.$alepostfix} = ${'ale_array'.$alepostfix}[$tuoteno];
      }
      else {
        ${'ale'.$alepostfix} = ${'kayttajan_ale'.$alepostfix};
      }
    }

    if (is_array($netto_array)) {
      $netto = $netto_array[$tuoteno];
    }
    else {
      $netto = $kayttajan_netto;
    }

    if (is_array($var_array)) {
      $var = $var_array[$tuoteno];
    }
    else {
      $var = $kayttajan_var;
    }

    if (is_array($kpl_array)) {
      $kpl = $kpl_array[$tuoteno];
    }
    else {
      $kpl = $kayttajan_kpl;
    }

    if (is_array($alv_array)) {
      $alv = $alv_array[$tuoteno];
    }
    else {
      $alv = $kayttajan_alv;
    }

    if ($kpl != 0) {
      require 'lisaarivi.inc';
    }

    $hinta   = '';
    $netto   = '';
    $var   = '';
    $kpl   = '';
    $alv   = '';
    $paikka  = '';

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
      ${'ale'.$alepostfix} = '';
    }
  }

  if ($lisavarusteita == "ON" and $perheid2 > 0) {
    //P‰ivitet‰‰n is‰lle perheid jotta tiedet‰‰n, ett‰ lis‰varusteet on nyt lis‰tty
    $query = "UPDATE tilausrivi set
              perheid2    = '$perheid2'
              where yhtio = '$kukarow[yhtio]'
              and tunnus  = '$perheid2'";
    $updres = pupe_query($query);
  }

  unset($tee);
  unset($tila);

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    unset(${'ale'.$alepostfix});
    unset(${'ale_array'.$alepostfix});
    unset(${'kayttajan_ale'.$alepostfix});
  }

  unset($alv);
  unset($alv_array);
  unset($hinta);
  unset($hinta_array);
  unset($kayttajan_alv);
  unset($kayttajan_hinta);
  unset($kayttajan_kpl);
  unset($kayttajan_netto);
  unset($kayttajan_var);
  unset($kerayspvm);
  unset($kommentti);
  unset($kpl);
  unset($kpl_array);
  unset($netto);
  unset($netto_array);
  unset($paikat);
  unset($paikka);
  unset($paikka_array);
  unset($perheid);
  unset($perheid2);
  unset($rivinumero);
  unset($rivitunnus);
  unset($toimaika);
  unset($tuotenimitys);
  unset($tuoteno);
  unset($var);
  unset($variaatio_tuoteno);
  unset($var_array);
}

//korjataan siirrett‰v‰t muuttujat taas talteen
if (isset($muut_siirrettavat)) {
  $muut = explode('!°!', $muut_siirrettavat);

  $alku         = $muut[0];
  $nayta_kohdistetut   = $muut[1];
  $ojarj         = $muut[2];
  $nayta_kaikki     = $muut[3];
  $toimittajaid     = $muut[4];

  for ($i = 4; $i < count($muut); $i++) {
    list($h, $s) = explode('#', $muut[$i]);
    $haku[$h] = $s;
  }
}

//kohdistetaan rivej‰ laskulle
if ($tila == 'tee_siirto') {

  if ($ala_kohdista_mitaan != "") {
    unset($rivi_siirrettavat_tunnukset);
  }

  if ($kohdista_koko_sivu != "") {
    $rivi_siirrettavat_tunnukset = $rivi_kaikki_tunnukset;
  }

  //Tarksitetaan viel‰ ennen p‰ivityst‰, ettei saapumsita ole poistettu ja infotaan k‰ytt‰j‰‰, jos n‰in on k‰ynyt
  $query = "SELECT tila
            FROM lasku
            WHERE yhtio     = '{$kukarow[yhtio]}'
            AND tila        = 'K'
            AND alatila     = ''
            AND vanhatunnus = 0
            AND tunnus      = '$otunnus'";
  $res = pupe_query($query);

  if (mysql_num_rows($res) == 1) {
    //Siirret‰‰n rivit
    if (is_array($rivi_siirrettavat_tunnukset)) {

      $q1 = "LOCK TABLE lasku WRITE, tilausrivi WRITE, tilausrivin_lisatiedot WRITE";
      $r = pupe_query($q1);

      $keikkakesken = 0;

      $lock_params = array(
        "locktime" => 0,
        "lockfile" => "$kukarow[yhtio]-keikka.lock",
        "return"   => TRUE
      );

      if (!pupesoft_flock($lock_params)) {
        list($keikkakesken, $_kuka, $_timestamp) = explode(";", file_get_contents("/tmp/$kukarow[yhtio]-keikka.lock"));
      }

      foreach ($rivi_siirrettavat_tunnukset as $valittu) {

        list($valittutunnus, $valittuuusiotunnus) = explode("###", $valittu);

        if ($valittuuusiotunnus != $keikkakesken) {
          // Katostaan, ett‰ keikka jolta rivi siirret‰‰n ei ole loppuunlaskettu
          $query  = "SELECT tunnus
                     FROM lasku USE INDEX (PRIMARY)
                     WHERE tunnus    = '$valittuuusiotunnus'
                     and yhtio       = '$kukarow[yhtio]'
                     and tila        = 'K'
                     and vanhatunnus = 0
                     and alatila     = ''";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 1) {

            $kpl_lisa = "";

            // Ollaanko syˆtetty siirrett‰v‰ m‰‰r‰?
            if (isset($siirrettavat_kpl[$valittutunnus])) {
              $query = "SELECT *
                        FROM tilausrivi
                        WHERE tunnus = '$valittutunnus'
                        and yhtio    = '$kukarow[yhtio]'";
              $rivires = pupe_query($query);
              $rivirow = mysql_fetch_assoc($rivires);

              $siirrettavat_kpl[$valittutunnus] = (float) str_replace(',', '.', $siirrettavat_kpl[$valittutunnus]);

              // Jos luku ei ole ok niin unsetataan se ja siirret‰‰n koko rivi
              if ($siirrettavat_kpl[$valittutunnus] > 0 and $siirrettavat_kpl[$valittutunnus] < $rivirow["kpl"]) {

                $jaljelle_jaa = $rivirow["kpl"] - $siirrettavat_kpl[$valittutunnus];
                $kpl_lisa = ", tilkpl='".$siirrettavat_kpl[$valittutunnus]."', kpl='".$siirrettavat_kpl[$valittutunnus]."' ";

                // Splitataan rivi, uusi rivi j‰‰ vanhalle keikalle
                $rfields = "yhtio";
                $rvalues = "'$kukarow[yhtio]'";

                for ($i=1; $i < mysql_num_fields($rivires)-1; $i++) {

                  $fieldname = mysql_field_name($rivires, $i);

                  $rfields .= ", $fieldname";

                  switch ($fieldname) {
                  case 'laatija':
                    $rvalues .= ", '$kukarow[kuka]'";
                    break;
                  case 'laadittu':
                    $rvalues .= ", now()";
                    break;
                  case 'tilkpl':
                  case 'kpl':
                    $rvalues .= ", '$jaljelle_jaa'";
                    break;
                  case 'kate_korjattu':
                    $rvalues .= ", NULL";
                    break;
                  default:
                    $rvalues .= ", '".$rivirow[$fieldname]."'";
                  }
                }

                $kysely = "INSERT into tilausrivi ($rfields) VALUES ($rvalues)";
                $insres = pupe_query($kysely);
                $insid  = mysql_insert_id($GLOBALS["masterlink"]);

                $query = "SELECT *
                          FROM tilausrivin_lisatiedot
                          WHERE tilausrivitunnus = '$valittutunnus'
                          and yhtio              = '$kukarow[yhtio]'";
                $rivires = pupe_query($query);

                if (mysql_num_rows($rivires) > 0) {
                  $rivirow = mysql_fetch_assoc($rivires);

                  // Splitataan rivin lis‰tiedot, uusi rivi j‰‰ vanhalle keikalle
                  $rfields = "yhtio";
                  $rvalues = "'$kukarow[yhtio]'";

                  for ($i=1; $i < mysql_num_fields($rivires)-1; $i++) {

                    $fieldname = mysql_field_name($rivires, $i);

                    $rfields .= ", $fieldname";

                    switch ($fieldname) {
                    case 'laatija':
                      $rvalues .= ", '$kukarow[kuka]'";
                      break;
                    case 'luontiaika':
                      $rvalues .= ", now()";
                      break;
                    case 'tilausrivitunnus':
                      $rvalues .= ", '$insid'";
                      break;
                    default:
                      $rvalues .= ", '".$rivirow[$fieldname]."'";
                    }
                  }

                  $kysely = "INSERT into tilausrivin_lisatiedot ($rfields) VALUES ($rvalues)";
                  $insres = pupe_query($kysely);
                }
              }
            }


            // Siiret‰‰n rivi t‰lle keikalle
            $query = "UPDATE tilausrivi USE INDEX (uusiotunnus_index)
                      SET uusiotunnus     = '$otunnus',
                      erikoisale_saapuminen = '$laskurow[erikoisale_saapuminen]',
                      kerattyaika           = now(),
                      keratty               = '$kukarow[kuka]',
                      suuntalava            = 0
                      $kpl_lisa
                      WHERE yhtio           = '$kukarow[yhtio]'
                      and uusiotunnus       = '$valittuuusiotunnus'
                      and (tunnus = '$valittutunnus' or perheid2 = '$valittutunnus')";
            $result = pupe_query($query);

            // Nollataan kohdistus keikalta jolta rivi siirret‰‰n
            $query = "UPDATE lasku USE INDEX (PRIMARY)
                      SET kohdistettu = ''
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$valittuuusiotunnus'
                      and tila    = 'K'";
            $result = pupe_query($query);
          }
          else {
            echo "<font class='error'>".t("VIRHE: Saapuminen jolta rivi siirret‰‰n ei lˆydy")."</font><br>!";
          }
        }
        else {
          echo "<font class='error'>".t("VIRHE: Saapuminen jolta rivi siirret‰‰n on lukittu")."</font><br>!";
        }
      }

      $q6 = "UNLOCK TABLES";
      $r = pupe_query($q6);
    }
  } else {
    echo "<font class='error'>".t("VIRHE: Saapumiselle ei voi liitt‰‰ rivej‰, koska se on poistettu")."!</font><br>";
  }

  $tila = '';
}

// jos loppusumma on isompi kuin tietokannassa oleva tietuen koko (10 numeroa + 2 desimaalia), niin herjataan
if ($tila == 'tee_kohdistus' and is_array($rivi_hinta) !== FALSE and is_array($rivi_kpl) !== FALSE) {

  foreach ($rivi_hinta as $tun => $hinta_chk) {
    $loppusumma_check = $rivi_kpl[$tun] * $hinta_chk;

    if (count($rivi_valitut_tunnukset) > 0 or count($rivi_kaikki_tunnukset) > 0) {

      if ($kohdista_koko_sivu != "") {
        $rivi_valitut_tunnukset = $rivi_kaikki_tunnukset;
      }
      elseif ($ala_kohdista_mitaan != "") {
        unset($rivi_valitut_tunnukset);
      }

      if (isset($rivi_valitut_tunnukset) and count($rivi_valitut_tunnukset) > 0) {

        foreach ($rivi_valitut_tunnukset as $valittu) {

          list($valittutunnus, $valittuotunnus) = explode("###", $valittu);

          if ($valittutunnus == $tun) {
            if (abs($loppusumma_check) > 9999999999.99) {
              echo "<font class='error'>", t("VIRHE: liian iso loppusumma"), "!</font><br/>";
              $tila = '';
              break;
            }
          }
        }
      }
    }
  }
}

//kohdistetaan rivej‰ laskulle
if ($tila == 'tee_kohdistus') {

  if ($ala_kohdista_mitaan != "") {
    unset($rivi_valitut_tunnukset);
  }

  if ($kohdista_koko_sivu != "") {
    $rivi_valitut_tunnukset = $rivi_kaikki_tunnukset;
  }

  //Tehd‰‰n uusia lis‰varusterivej‰
  if (is_array($lisavarusterivi_kpl)) {

    foreach ($lisavarusterivi_kpl as $tunnus => $kpl) {
      $kpl    = str_replace(",", ".", $kpl);
      $perheid2 = substr($tunnus, 0, strpos($tunnus, '##'));
      $tuoteno  = substr($tunnus,  strpos($tunnus, '##')+2, strpos($tunnus, '#*#')-strpos($tunnus, '##')-2);
      $hinta     = str_replace(",", ".", $lisavarusterivi_hinta[$tunnus]);
      $potunnus = substr($tunnus, strpos($tunnus, '#*#')+3);

      if (abs($kpl) > 0 or strtoupper($kpl) == "DEL") {
        if (abs($kpl) > 0) {
          $query  = "SELECT *
                     FROM tuote
                     LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus='$toimittajaid'
                     where tuote.yhtio    = '$kukarow[yhtio]'
                     and tuote.tuoteno    = '$tuoteno'
                      and tuote.ei_saldoa = ''";
          $lisaresult = pupe_query($query);
          $lisarow = mysql_fetch_assoc($lisaresult);

          if ($hinta == "") {
            $hinta = $lisarow["ostohinta"];
          }

          $query = "INSERT into tilausrivi
                    (perheid2, hinta, nimitys, tuoteno, tilkpl, varattu, otunnus, yhtio, tyyppi, laatija, laadittu) values
                    ('$perheid2','$hinta','$lisarow[nimitys]','$tuoteno', '$kpl', '$kpl', '$potunnus', '$kukarow[yhtio]', 'O', '$kukarow[kuka]', now())";
          $updre = pupe_query($query);
        }
      }

      unset($lisaa_lisavarusteita);
    }

    $query = "UPDATE tilausrivi SET perheid2='$perheid2' where yhtio='$kukarow[yhtio]' and tunnus='$perheid2' and tyyppi='O'";
    $result = pupe_query($query);
  }

  //Kohdistetaan kaikki rivit
  // siirretty!
  if (count($rivi_kaikki_tunnukset) > 0) {

    $tunnukset2 = array();

    foreach ($rivi_kaikki_tunnukset as $valittu) {
      list($valittutunnus, $valittuotunnus) = explode("###", $valittu);

      $tunnukset2[$valittutunnus] = $valittutunnus;
    }

    $_tunnukset = implode(",", $tunnukset2);

    $query = "UPDATE tilausrivi use index (PRIMARY) SET
              uusiotunnus           = 0,
              kerattyaika           = '0000-00-00 00:00:00',
              keratty               = '',
              erikoisale_saapuminen = 0
              WHERE yhtio           = '{$kukarow["yhtio"]}'
              AND tunnus            IN ({$_tunnukset})
              AND laskutettuaika    = '0000-00-00'";
    $result = pupe_query($query);
  }

  //Kohdistetaan yksi rivi
  // siirretty!
  if (is_array($rivi_valitut_tunnukset)) {

    $tunnukset2 = array();

    foreach ($rivi_valitut_tunnukset as $valittu) {
      list($valittutunnus, $valittuotunnus) = explode("###", $valittu);

      $tunnukset2[$valittutunnus] = $valittutunnus;
    }

    //Tarksitetaan viel‰ ennen p‰ivityst‰, ettei saapumsita ole poistettu ja infotaan k‰ytt‰j‰‰, jos n‰in on k‰ynyt
    $query = "SELECT tila
              FROM lasku
              WHERE yhtio     = '{$kukarow[yhtio]}'
              AND tila        = 'K'
              AND alatila     = ''
              AND vanhatunnus = 0
              AND tunnus      = '$otunnus'";
    $res = pupe_query($query);

    if (mysql_num_rows($res) == 1) {
      $query = "UPDATE tilausrivi
                SET uusiotunnus     = '$otunnus',
                kerattyaika           = now(),
                keratty               = '$kukarow[kuka]',
                erikoisale_saapuminen = '$laskurow[erikoisale_saapuminen]'
                WHERE yhtio           = '$kukarow[yhtio]'
                AND tunnus            in (".implode(",", $tunnukset2).")";
      $result = pupe_query($query);

      $query = "UPDATE tilausrivi
                SET uusiotunnus     = '$otunnus',
                kerattyaika           = now(),
                keratty               = '$kukarow[kuka]',
                erikoisale_saapuminen = '$laskurow[erikoisale_saapuminen]'
                WHERE yhtio           = '$kukarow[yhtio]'
                AND perheid2          in (".implode(",", $tunnukset2).")
                AND perheid2          > 0";
      $result = pupe_query($query);
    } else {
      echo "<font class='error'>".t("VIRHE: Saapumiselle ei voi liitt‰‰ rivej‰, koska se on poistettu")."!</font><br>";
    }
  }
  $tila = '';

  // katotaan ollaanko muutettunimityksi‰ ja p‰ivitet‰‰n ne
  if (is_array($rivi_nimitys)) {
    foreach ($rivi_nimitys as $tunnus => $nimitys) {
      $query  = "UPDATE tilausrivi set nimitys='$nimitys' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
      $result = pupe_query($query);
    }
  }

  // katotaan ollaanko muutettu hintaa tai kappaleita ja p‰ivitet‰‰n ne
  if (is_array($rivi_hinta)) {
    foreach ($rivi_hinta as $tunnus => $hinta) {
      $hinta  = str_replace(",", ".", $hinta);
      $query  = "UPDATE tilausrivi set hinta='$hinta' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and hinta<>'$hinta'";
      $result = pupe_query($query);
    }
  }

  // katotaan ollaanko muutettualeja ja p‰ivitet‰‰n ne
  // siirretty!
  if (is_array($rivi_ale1)) {
    // ale1
    foreach ($rivi_ale1 as $tunnus => $ale) {
      $ale  = str_replace(",", ".", $ale);
      $query  = "UPDATE tilausrivi set ale1='$ale' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and ale1<>'$ale'";
      $result = pupe_query($query);
    }
  }

  if (is_array($rivi_ale2)) {
    // ale2
    foreach ($rivi_ale2 as $tunnus => $ale) {
      $ale  = str_replace(",", ".", $ale);
      $query  = "UPDATE tilausrivi set ale2='$ale' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and ale2<>'$ale'";
      $result = pupe_query($query);
    }
  }

  if (is_array($rivi_ale3)) {
    // ale3
    foreach ($rivi_ale3 as $tunnus => $ale) {
      $ale  = str_replace(",", ".", $ale);
      $query  = "UPDATE tilausrivi set ale3='$ale' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and ale3<>'$ale'";
      $result = pupe_query($query);
    }
  }

  if (is_array($rivi_erikoisale)) {
    // ale3
    foreach ($rivi_erikoisale as $tunnus => $ale) {
      $ale  = str_replace(",", ".", $ale);
      $query  = "UPDATE tilausrivi set erikoisale='$ale' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and erikoisale<>'$ale'";
      $result = pupe_query($query);
    }
  }

  if (isset($rivi_myyntihinta) and is_array($rivi_myyntihinta) and tarkista_oikeus('yllapito.php', 'tuote', 1) and $yhtiorow['myyntihinta_paivitys_saapuminen'] == 'K') {

    $tuoteno_array = array();

    foreach ($rivi_myyntihinta as $tunnus => $_myyntihinta) {

      $query = "SELECT tuoteno
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$tunnus}'";
      $chk_res = pupe_query($query);

      $_myyntihinta = str_replace(',', '.', $_myyntihinta);

      if (mysql_num_rows($chk_res) == 1 and trim($_myyntihinta) != '' and is_numeric($_myyntihinta)) {

        $chk_row = mysql_fetch_assoc($chk_res);

        // Tarkistetaan onko myyntihinta muuttunut alkuper‰iseen verrattuna
        $query = "SELECT myyntihinta
                  FROM tuote
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$chk_row['tuoteno']}'";
        $tuoteno_chk_res = pupe_query($query);
        $tuoteno_chk_row = mysql_fetch_assoc($tuoteno_chk_res);

        $a = (int) ($tuoteno_chk_row['myyntihinta'] * 100000);
        $b = (int) ($_myyntihinta * 100000);

        if ($a != $b and !isset($tuoteno_array[$chk_row['tuoteno']])) {
          $tuoteno_array[$chk_row['tuoteno']] = $_myyntihinta;
        }
      }
    }

    // tuotteen myyntihinta
    if (count($tuoteno_array) > 0) {

      foreach ($tuoteno_array as $_tuoteno => $_myyntihinta) {

        $query = "UPDATE tuote SET
                  myyntihinta = '{$_myyntihinta}',
                  muutospvm   = now(),
                  muuttaja    = '$kukarow[kuka]'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$_tuoteno}'";
        pupe_query($query);
      }
    }
  }

  // katotaan p‰ivitet‰‰nkˆ toimitettuaikoja
  if (is_array($rivi_toimitettuaika_vv)) {
    foreach ($rivi_toimitettuaika_vv as $tunnus => $toimitettuvv) {

      $toimitettukk = $rivi_toimitettuaika_kk[$tunnus];
      $toimitettupp = $rivi_toimitettuaika_pp[$tunnus];

      $query  = "UPDATE tilausrivin_lisatiedot set suoratoimitettuaika='$toimitettuvv-$toimitettukk-$toimitettupp' where yhtio='$kukarow[yhtio]' and tilausrivitunnus='$tunnus'";
      $result = pupe_query($query);
    }
  }

  if (is_array($rivi_kuluhinta)) {
    // katotaan ollaanko muutettu kuluhintaa tai kappaleita ja p‰ivitet‰‰n ne
    foreach ($rivi_kuluhinta as $tunnus => $kulu) {
      $kulu   = str_replace(",", ".", $kulu);
      $query  = "UPDATE tilausrivi set kate='$kulu' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus' and kate<>'$kulu'";
      $result = pupe_query($query);
    }
  }

  // siirretty!
  if (is_array($rivi_kpl)) {
    foreach ($rivi_kpl as $tunnus => $kpl) {
      $kpl    = str_replace(",", ".", $kpl);
      $query  = "SELECT kpl, varattu, tuoteno
                 FROM tilausrivi
                 WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
      $result = pupe_query($query);
      $rivi  = mysql_fetch_assoc($result);

      // jos t‰t‰ rivi‰ ei ole viel‰ viety varastoon ni p‰ivitet‰‰n varattu kentt‰‰
      if ($rivi["kpl"] == 0 and $rivi["varattu"] != 0 and $kpl != 0) {
        $query = "UPDATE tilausrivi
                  set varattu='$kpl'
                  where yhtio='$kukarow[yhtio]'
                  and tunnus='$tunnus'
                  and varattu<>'$kpl'";
        $result = pupe_query($query);

        $jalkitoimitukseen_kpl = $rivi_kpl_alkuperainen[$tunnus] - $kpl;

        // Kopioidaan rivin erotus uudelle tilausriville
        if ($yhtiorow["saapumisen_jt_kasittely"] == "K" and $jalkitoimitukseen_kpl > 0 and $kpl > 0 and $rivi_kpl_alkuperainen[$tunnus] > 0) {
          // J‰tet‰‰n loppurivi JT:seen
          $parametrit = array (
            "method" => "POST",
            "url" => "{$palvelin2}tilauskasittely/keikka.php",
            "headers" => array ('Accept:text/html'),
            "cookie" => "pupesoft_session={$_COOKIE['pupesoft_session']}",
            "posttype" => "array",
            "data" => array (
              "keikan_otunnus" => $otunnus,
              "tunnus" => $tunnus,
              "ajax_toiminto" => 'rivi_kpl_splittaus',
              "no_head" => 'yes',
              "ohje" => 'off',
              "uuden_rivin_kpl" => $jalkitoimitukseen_kpl,
            )
          );

          $return = pupesoft_rest($parametrit);
        }
      }

      if ($rivi["kpl"] == 0 and strtoupper($kpl) == "DEL") {
        $query = "DELETE FROM tilausrivi
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$tunnus'
                  and tyyppi  = 'O'";
        $result = pupe_query($query);

        //Nollataan sarjanumero
        if ($rivi["varattu"] > 0) {
          $tunken = "ostorivitunnus";
        }
        else {
          $tunken = "myyntirivitunnus";
        }

        $query = "UPDATE sarjanumeroseuranta
                  SET $tunken = 0
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tuoteno = '$rivi[tuoteno]'
                  and $tunken = '$tunnus'";
        $sarjares = pupe_query($query);
      }
    }
  }
}

//n‰ytet‰‰n rivilista ja ruksataan kohdistettavia rivej‰
if ($tila == '') {

  list(
    $hintarow,
    $valitutrivit,
    $alvit,
    $alvti
  ) = paivita_saapumisen_summa($laskurow, $kulurow, $toimittajaid, $otunnus);

  // Jos k‰ytt‰j‰ on syˆtt‰nyt alvillisen hinnan niin lis‰t‰‰n alvi
  if ((int) $laskurow["rahti_etu_alv"] != 0) {
    $laskurow["rahti_etu"] = round($laskurow["rahti_etu"] * (1 + ($laskurow["rahti_etu_alv"] / 100)), 6);
  }

  // Jos k‰ytt‰j‰ on syˆtt‰nyt alvillisen pyˆristyksen niin lis‰t‰‰n alvi
  if ((int) $laskurow["pyoristys_erot_alv"] != 0) {
    $laskurow["pyoristys_erot"] = round($laskurow["pyoristys_erot"] * (1 + ($laskurow["pyoristys_erot_alv"] / 100)), 6);
  }

  $valittusumma       = sprintf("%.2f", round($hintarow["hinta"], 2));
  $laskunsumma        = sprintf("%.2f", round($kulurow["summa"] - $laskurow["rahti_etu"] - $laskurow['pyoristys_erot']- $valittusumma - $kulurow['osto_kulut'], 2));
  $checksumma         = $kulurow["summa"] - $laskurow["rahti_etu"] - $laskurow['pyoristys_erot'] - $hintarow["hinta"] - $kulurow['osto_kulut'];
  $kohdistettava_summa   = sprintf("%.2f", round($kulurow["summa"] - $laskurow["rahti_etu"] - $laskurow['pyoristys_erot'] - $kulurow['osto_kulut'], 2));

  if ($laskunsumma != 0.00 and $checksumma < 0.01 and $checksumma > -0.01 and $kohdistettava_summa == $valittusumma) {
    $laskunsumma = 0.00;
  }

  // Tietokannan kent‰t on m‰‰ritelt‰v‰ n‰in jotta meid‰n dynaamiset haut toimisivat
  $kentat_array = array();
  $kentat_array[] = "tilausrivi.otunnus otunnus";
  $kentat_array[] = "tilausrivi.tuoteno tuoteno";
  $kentat_array[] = "GROUP_CONCAT(DISTINCT tuotteen_toimittajat.toim_tuoteno ORDER BY toim_tuoteno SEPARATOR ',') toim_tuoteno";
  $kentat_array[] = "tilausrivi.nimitys nimitys";
  $kentat_array[] = "tilausrivi.varattu+tilausrivi.kpl siskpl";
  $kentat_array[] = "round((tilausrivi.varattu+tilausrivi.kpl)*if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4) ulkkpl";
  $kentat_array[] = "tilausrivi.hinta hinta";
  $kentat_array[] = "tuotteen_toimittajat.valuutta valuutta";

  // niin monta alekentt‰‰ haetaan kun on myynnin_alekentti‰, muutetaab jossain vaiheessa oston_alekentiksi.
  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    $kentat_array[] = "tilausrivi.ale$alepostfix ale$alepostfix"; // 8,9,10
  }

  $query_ale_lisa = generoi_alekentta('O');

  $kentat_array[] = "$alvti alv";

  if (empty($saap_erikoisale_ui)) {
    $kentat_array[] = "tilausrivi.erikoisale";
  }

  $kentat_array[] = "round((tilausrivi.varattu+tilausrivi.kpl) * tilausrivi.hinta * if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),'$yhtiorow[hintapyoristys]') rivihinta";

  if (!empty($saap_rivihinta_ui) and $saap_rivihinta_ui == "verobveron") {
    $alerivihinta_alvit = 0;
  }
  else {
    $alerivihinta_alvit = $alvit;
  }

  $kentat_array[] = "round((tilausrivi.varattu+tilausrivi.kpl) * if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin) * tilausrivi.hinta * {$query_ale_lisa} *
              (1+(if ((SELECT max(kaytetty) kaytetty
                  FROM sarjanumeroseuranta
                  WHERE sarjanumeroseuranta.yhtio=tilausrivi.yhtio
                  and sarjanumeroseuranta.tuoteno=tilausrivi.tuoteno
                  and ((tilausrivi.varattu+tilausrivi.kpl < 0 and sarjanumeroseuranta.myyntirivitunnus=tilausrivi.tunnus) or (tilausrivi.varattu+tilausrivi.kpl > 0 and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus))) = 'K', 0, $alerivihinta_alvit)/100)) ,'$yhtiorow[hintapyoristys]') alerivihinta";

  if ($yhtiorow['myyntihinta_paivitys_saapuminen'] == 'K') {
    $kentat_array[] = "tuote.myyntihinta myyntihinta";
  }

  $kentat_array[] = "tilausrivi.toimaika toimaika";
  $kentat_array[] = "tilausrivi.kommentti kommentti";
  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") $kentat_array[] = "lasku.laskunro keikka";

  $kennim_array = array();
  $kennim_array[] = t('Tilaus');
  $kennim_array[] = t('Tuoteno');
  $kennim_array[] = t('Toim_tuoteno');
  $kennim_array[] = t('Nimitys');
  $kennim_array[] = t('M‰‰r‰');
  $kennim_array[] = t('M‰‰r‰/Ulk');
  $kennim_array[] = t('Hinta');
  $kennim_array[] = t('Valuutta');

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    $kennim_array[] = t('Ale').$alepostfix;
  }

  $kennim_array[] = t('Alv');

  if (empty($saap_erikoisale_ui)) {
    $kennim_array[] = t('Tilauksen')."<br>".t('Erikoisale');
  }

  $kennim_array[] = t('Rivihinta');

  if (!empty($saap_rivihinta_ui) and $saap_rivihinta_ui == "verobveron") {
    $kennim_array[] = t('-Ale');
  }
  else {
    $kennim_array[] = t('+Alv -Ale');
  }


  if ($yhtiorow['myyntihinta_paivitys_saapuminen'] == 'K') {
    $kennim_array[] = t('Myyntihinta');
  }

  $kennim_array[] = t('Toimaika');
  $kennim_array[] = t('Kommentti');
  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") $kennim_array[] = t('Saapuminen');

  $kenhak_array = array();
  $kenhak_array[] = 'otunnus';
  $kenhak_array[] = 'tuoteno';
  $kenhak_array[] = 'toim_tuoteno';
  $kenhak_array[] = 'nimitys';
  $kenhak_array[] = 'siskpl';
  $kenhak_array[] = 'ulkkpl';
  $kenhak_array[] = 'hinta';
  $kenhak_array[] = 'valuutta';

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    $kenhak_array[] = t('Ale').$alepostfix;
  }

  $kenhak_array[] = 'alv';

  if (empty($saap_erikoisale_ui)) {
    $kenhak_array[] = 'erikoisale';
  }

  $kenhak_array[] = 'rivihinta';
  $kenhak_array[] = 'alerivihinta';

  if ($yhtiorow['myyntihinta_paivitys_saapuminen'] == 'K') {
    $kenhak_array[] = 'myyntihinta';
  }

  $kenhak_array[] = 'toimaika';
  $kenhak_array[] = 'kommentti';
  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") $kenhak_array[] = 'laskunro';

  $kenkok_array = array();
  $kenkok_array[] = 6;
  $kenkok_array[] = 15;
  $kenkok_array[] = 15;
  $kenkok_array[] = 17;

  $kenkok_array[] = 7;
  $kenkok_array[] = 7;
  $kenkok_array[] = 7;
  $kenkok_array[] = 7;

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    $kenkok_array[] = 4;
  }

  $kenkok_array[] = 4;
  $kenkok_array[] = 6;

  if (empty($saap_erikoisale_ui)) {
    $kenkok_array[] = 8;
  }

  $kenkok_array[] = 8;

  if ($yhtiorow['myyntihinta_paivitys_saapuminen'] == 'K') {
    $kenkok_array[] = 7;
  }

  $kenkok_array[] = 12;
  $kenkok_array[] = 12;

  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") $kenkok_array[] = 6;

  foreach ($kenhak_array as $i => $kentta) {
    // tarkastetaan onko hakukent‰ss‰ jotakin
    if (isset($haku[$i]) and strlen($haku[$i]) > 0) {
      if ($kentta == "toimaika" and strpos($haku[$i], ".") !== false) {
        $_aika = explode(".", $haku[$i]);

        if (count($_aika) == 2) {
          $lisa1 .= " $kentta like '%{$_aika["1"]}-{$_aika["0"]}%' and";
        }
        elseif (count($_aika) == 3) {
          $lisa1 .= " $kentta like '%{$_aika["2"]}-{$_aika["1"]}-{$_aika["0"]}%' and";
        }
      }
      else {
        $lisa1 .= " $kentta like '%$haku[$i]%' and";
      }

      //pidet‰‰n muistissa linkkej‰ varten
      $ulisa .= "&haku[$i]=$haku[$i]";

      //pidet‰‰n muistissa formeja varten
      $hiddenit .= "<input type='hidden' name='haku[$i]' value='$haku[$i]'>\n";
    }
  }

  //Kipan sivunvaihto
  if (!isset($alku) or $alku == '') {
    $alku = 0;
  }

  $jarjestys = "";

  if (strlen($ojarj) > 0) {
    $jarjestys = $kenhak_array[$ojarj].", ";
  }

  $jarjestys .= " perheid2, rivitunnus ";

  $limit     = "";
  $lisa2     = "";
  $uusilisa   = "";
  $alku     = (int) $alku;

  if ($alku < 0) {
    $alku = 0;
  }

  $unionwhere1 = " and lasku.tila = 'O' and alatila in ('A','X') ";
  $unionwhere2 = " and lasku.tila = 'K' ";
  $tilriindex  = "yhtio_otunnus";

  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
    $uusilisa   = "uusi";
    $tilriindex = "uusiotunnus_index";

    $limit = " LIMIT $alku, 50 ";

    $unionwhere1 = " and lasku.tila = 'K' and lasku.alatila = '' ";
    $unionwhere2 = "";

    $lisa2 = " and tilausrivi.uusiotunnus not in ($otunnus,0) ";
  }
  elseif ($nayta_kohdistetut == "jees") {
    $limit = "";
    $lisa2 = " and tilausrivi.uusiotunnus in ($otunnus) ";
  }
  elseif ($nayta_kohdistetut == "eitod") {
    $limit = " LIMIT $alku, 50 ";
    $lisa2 = " and tilausrivi.uusiotunnus = 0 ";
  }
  else {
    $limit = " LIMIT $alku, 50 ";
    $lisa2 = " and tilausrivi.uusiotunnus in ($otunnus,0) ";
  }

  if ($nayta_kaikki == "kaikki") {
    $limit = "";
  }

  $kentat = "";
  foreach ($kentat_array as $kentta) {
    $kentat .= $kentta.", ";
  }

  if ($lisa1 != '') {
    $lisa1 = " HAVING ".substr($lisa1, 0, -3);
    $limit = "";
    $alku = 0;
  }

  $onkolaajattoimipaikat = ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikat_res) > 0) ? TRUE : FALSE;

  if ($onkolaajattoimipaikat and isset($toimipaikka)) {
    if ($toimipaikka == 'kaikki') {
      $toimipaikkalisa = "";
    }
    else {
      $toimipaikka = (int) $toimipaikka;
      $toimipaikkalisa = "AND (lasku.vanhatunnus = '{$toimipaikka}' or lasku.vanhatunnus = 0)";
    }
  }
  elseif ($onkolaajattoimipaikat) {
    $toimipaikkalisa = "AND (lasku.vanhatunnus = '{$laskurow['yhtio_toimipaikka']}' or lasku.vanhatunnus = 0)";
  }
  else {
    $toimipaikkalisa = "";
  }

  $query = "SELECT $kentat
            varattu,
            tilausrivi.perheid,
            tilausrivi.perheid2,
            tilausrivi.kpl varastossa_kpl,
            tilausrivi.tunnus rivitunnus,
            tilausrivi.uusiotunnus,
            concat_ws('/', tilkpl, round(tilkpl * if (tuotteen_toimittajat.tuotekerroin<=0 or tuotteen_toimittajat.tuotekerroin is null,1,tuotteen_toimittajat.tuotekerroin),4)) 'tilattu',
            tuote.kehahin keskihinta,
            tuotteen_toimittajat.ostohinta,
            tilausrivi.yksikko tilyksikko,
            if(tuotteen_toimittajat.toim_yksikko!='', tuotteen_toimittajat.toim_yksikko, tilausrivi.yksikko) toimyksikko,
            tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllytaso, tilausrivi.hyllyvali,
            tuote.keraysvyohyke,
            tilausrivin_lisatiedot.suoratoimitettuaika,
            lasku.comments,
            tuote.alv myyntialv,
            tuote.sarjanumeroseuranta,
            tuote.automaattinen_sarjanumerointi,
            tuote.kuluprosentti
            FROM lasku
            JOIN tilausrivi use index ($tilriindex) ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.{$uusilisa}otunnus=lasku.tunnus and tilausrivi.tyyppi='O' AND tilausrivi.kpl+tilausrivi.varattu != 0 $tilaajanrivinro $lisa2
            LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
            JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno  and tuote.ei_saldoa = '')
            LEFT JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus=lasku.liitostunnus
            LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio AND tuotteen_alv.tuoteno = tilausrivi.tuoteno AND tuotteen_alv.maa = '$laskurow[maa]')
            WHERE lasku.yhtio      = '$kukarow[yhtio]'
            and lasku.liitostunnus = '$laskurow[liitostunnus]'
            {$toimipaikkalisa}";

  // Tehd‰‰n unionilla niin saadaan sek‰ keikat ett‰ osotilaukset nopsasti haettua suoraan indeksist‰
  if ($unionwhere2 != "") {
    $query = "($query $unionwhere1 GROUP BY tilausrivi.tunnus $lisa1) UNION ($query $unionwhere2 GROUP BY tilausrivi.tunnus $lisa1) ORDER BY $jarjestys $limit";
  }
  else {
    $query = "$query $unionwhere1 GROUP BY tilausrivi.tunnus $lisa1 ORDER BY $jarjestys $limit";
  }

  $result = pupe_query($query);

  echo "<table><tr><td class='back'>";

  // aloitellaan laskun otsikko taulu
  $chk1 = "";
  $chk2 = "";
  $chk3 = "";
  $chk4 = "";
  $chk5 = "";
  $chk6 = "";

  if ($nayta_kohdistetut == "jees") {
    $chk1 = "checked";
  }

  if ($nayta_kohdistetut == "eitod") {
    $chk2 = "checked";
  }

  if ($nayta_kaikki == "kaikki") {
    $chk3 = "checked";
  }

  if ($nayta_kohdistetut == "") {
    $chk4 = "checked";
  }

  if ($laskunsumma == 0 and $valitutrivit > 0) { // jos ollaan saatu kohdistus nollaan ja ollaan valittu jotain
    $ok = "<font class='ok'>";
    $okend = "</font>";
  }
  else {
    $ok = "";
    $okend = "";
  }

  // jos kululaskua ei ole viel‰ liitetty, oletetaan ett‰ k‰ytet‰‰n toimittajan oletus valuuttaa
  if ($kulurow["valkoodi"] == "") $kulurow["valkoodi"] = $laskurow["valkoodi"];

  $query  = "SELECT sum(kpl+varattu) kappaleet, count(*) rivit from tilausrivi where yhtio='$kukarow[yhtio]' and uusiotunnus='$laskurow[tunnus]' and tyyppi='O'";
  $kappaleetres = pupe_query($query);
  $kappaleetrow = mysql_fetch_assoc($kappaleetres);

  //n‰ytet‰‰n mitk‰ n‰ytet‰‰n
  echo "<form name='formi' method='post'>";
  echo "<input type='hidden' name='toiminto' value='kohdista'>";
  echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
  echo "<input type='hidden' name='alku' value='$alku'>";
  echo "<input type='hidden' name='otunnus' value='$otunnus'>";
  echo "<input type='hidden' name='tee' value='K'>";
  echo $hiddenit;
  echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";

  echo "<table>
  <tr>
    <th>".t("Saapuminen").":</th><td>$laskurow[laskunro]</td>
    <th>".t("Laskun summa").":</th><td style='text-align:right'>$kulurow[summa] $kulurow[valkoodi]</td>
    <th>".t("Perusn‰kym‰").":</th><td><INPUT type='radio' $chk4 name='nayta_kohdistetut' value='' onclick='submit()'></td>
  </tr>
  <tr>
    <th>".t("Toimittaja").":</th><td>$laskurow[nimi]</td>
    <th>".t("Laskun kulut").":</th><td style='text-align:right'><span id='laskun_kulut'>".sprintf("%.2f", $kulurow['osto_kulut'])."</span> $kulurow[valkoodi]</td>
    <th>".t("N‰yt‰ kaikki kohdistetut").":</th><td><INPUT type='radio' $chk1 name='nayta_kohdistetut' value='jees' onclick='submit()'></td>
  </tr>
  <tr>
    <th>".t("N‰yt‰ lasku").":</th><td>";

  js_popup();

  $query = "SELECT l2.tunnus
            FROM lasku l1
            JOIN lasku l2 ON l1.yhtio=l2.yhtio and l1.vanhatunnus=l2.tunnus
            WHERE l1.yhtio     = '$kukarow[yhtio]'
            and l1.tila        = 'K'
            and l1.laskunro    = '$laskurow[laskunro]'
            and l1.vanhatunnus > 0";
  $muutkeikres = pupe_query($query);

  while ($muutkeikrow = mysql_fetch_assoc($muutkeikres)) {
    echo ebid($muutkeikrow['tunnus']);
  }

  echo "</td>
    <th>".t("Eturahti").":</th><td style='text-align:right'>".sprintf("%.2f", $laskurow["rahti_etu"])." $kulurow[valkoodi]</td>
    <th>".t("N‰yt‰ vain kohdistamattomat").":</th><td><INPUT type='radio' $chk2 name='nayta_kohdistetut' value='eitod' onclick='submit()'></td>
    </tr>
    <tr>
    <th>".t("Saapumisen erikoisale").":</th><td><span id='keikan_erikoisale'>".($laskurow["erikoisale_saapuminen"]*1)."</span>%</td>
    <th>".t("Pyˆristyserot").":</th><td style='text-align:right'>".sprintf("%.2f", $laskurow["pyoristys_erot"])." $kulurow[valkoodi]</td>
    <th>".t("N‰yt‰ kaikki yhdell‰ sivulla").":</th><td><INPUT type='checkbox' $chk3 name='nayta_kaikki' value='kaikki' onclick='submit()'></td>
    </tr>
    <tr>";

  echo "<th>", t("N‰ytet‰‰n rivit"), ":</th>";

  if ($chk3 == "checked") {
    $sivu  = 1;
    $alku  = 0;
    $loppu = mysql_num_rows($result);
  }
  else {
    $loppu = $alku + 50;
    $sivu = round($loppu / 50, 0);
  }

  echo "<td>{$alku} - {$loppu}  ", t("Sivu"), ": {$sivu}</td>";
  echo "<th>".t("Kohdistettava summa").":</th><td style='text-align:right'><span id='kohdistettava_summa'>$kohdistettava_summa</span> $kulurow[valkoodi]</td>";

  if ($onkolaajattoimipaikat) {

    echo "<th>", t("N‰yt‰ toimipaikan rivej‰"), "</th>";
    echo "<td>";

    $sel = (isset($toimipaikka) and is_numeric($toimipaikka) and $toimipaikka == 0) ? "selected" : "";

    echo "<select name='toimipaikka' onchange='submit();'>";
    echo "<option value='kaikki'>", t("Kaikki toimipaikat"), "</option>";
    echo "<option value='0' {$sel}>", t("Ei toimipaikkaa"), "</option>";

    while ($toimipaikat_row = mysql_fetch_assoc($toimipaikat_res)) {

      $sel = '';

      if (isset($toimipaikka)) {
        if ($toimipaikka == $toimipaikat_row['tunnus']) {
          $sel = ' selected';
          $toimipaikka = $toimipaikat_row['tunnus'];
        }
      }
      else {
        if ($laskurow['yhtio_toimipaikka'] == $toimipaikat_row['tunnus']) {
          $sel = ' selected';
          $toimipaikka = $toimipaikat_row['tunnus'];
        }
      }

      echo "<option value='{$toimipaikat_row['tunnus']}'{$sel}>{$toimipaikat_row['nimi']}</option>";
    }

    echo "</select>";
  }
  else {
    echo "<th></th><td>";
  }

  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th></th><td></td>";
  echo "<th>".t("Kohdistettu summa").":</th><td id='kohdistettu_summa_td' style='text-align:right'>$ok <span id='kohdistettu_summa'>$valittusumma</span> $kulurow[valkoodi]$okend</td>";
  echo "<th>".t("Rivej‰").":</th><td style='text-align:right'><span id='kohdistettu_rivit'>$kappaleetrow[rivit]</span></td>";
  echo "</tr>";

  echo "<tr>";

  echo "<th></th><td></td>";

  echo "<th>".t("J‰ljell‰").":</th><td style='text-align:right'>$ok $laskunsumma $kulurow[valkoodi]$okend</td>";
  echo "<th>".t("Kappaleita").":</th><td style='text-align:right'><span id='kohdistettu_kpl'>".(float) $kappaleetrow['kappaleet']."</span></td>";
  echo "</tr>";

  echo "</table>";
  echo "</form>";

  // otsikko valmis

  echo "<table>";
  echo "<tr>";

  echo "<td class='back'>";

  if (empty($nayta_siirrettavat)) {
    echo "<input type='button' onclick='submit_tallenna();' value='".t("Tallenna muutokset")."'>";
    echo "<input type='button' onclick='submit_kohd_kaikki();' value='".t("Kohdista kaikki rivit")."'>";
    echo "<input type='button' onclick='submit_kohd_eimit();' value='".t("Poista kaikki kohdistukset")."'>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value='kohdista'>";
    echo "<input type='hidden' name='tila' value='uusirivi'>";
    echo "<input type='hidden' name='tee' value='K'>";
    echo "<input type='hidden' name='alku' value='$alku'>";
    echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
    echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
    echo $hiddenit;
    echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
    echo "<input type='submit' value='".t("Tee uusi rivi")."'></form>";

    if ($onkologmaster and $laskurow['sisviesti3'] == '') {
      echo "<form method='post'>
          <input type='hidden' name='toimittajaid' value='$toimittajaid'>
          <input type='hidden' name='toiminto' value='saapuminen_ulkoiseen_jarjestelmaan'>
          <input type='hidden' name='tee' value='K'>
          <input type='hidden' name='alku' value='$alku'>
          <input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>
          <input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>
          $hiddenit
          <input type='hidden' name='ojarj' value=\"$ojarj\">
          <input type='hidden' name='otunnus' value='$otunnus'>
          <input type='hidden' name='toimipaikka' value='{$toimipaikka}'>
          <input type='submit' value='".t("L‰het‰ ulkoiseen j‰rjestelm‰‰n")."'>
          </form>";
    }

    echo "<form name='formi' method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value='kohdista'>";
    echo "<input type='hidden' name='alku' value='$alku'>";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='tee' value='$tee'>";
    echo "<input type='hidden' name='tila' value='FAILISTA'>";
    echo $hiddenit;
    echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";
    echo "<input type='submit' value='".t("Kohdista tiedostosta")."'>";
    echo "</form>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value='kohdista'>";
    echo "<input type='hidden' name='tila' value=''>";
    echo "<input type='hidden' name='nayta_siirrettavat' value='YES'>";
    echo "<input type='hidden' name='tee' value='$tee'>";
    echo "<input type='hidden' name='alku' value='$alku'>";
    echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
    echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
    echo $hiddenit;
    echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
    echo "<input type='submit' value='".t("Siirr‰ rivej‰ toiselta saapumiselta")."'></form>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value='kohdista'>";
    echo "<input type='hidden' name='laskunsumma' value='$laskunsumma'>";
    echo "<input type='hidden' name='tee' value='K'>";
    echo "<input type='hidden' name='tila' value='valmis'>";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
    echo "<input type='submit' value='".t("Kohdistus valmis")."'></form>";
  }

  echo "</td></tr></table><br>";

  echo "<table class='ostorivien_kohdistus'>";

  echo "<tr>";

  //Sivunvaihdot
  $seuraava  = $alku + 50;
  $edellinen = $alku - 50;

  if ($edellinen < 0) {
    $edellinen = 0;
  }

  $viimenen_sivu = mysql_num_rows($result)+$alku-50;

  echo "  <td class='back' colspan='12' align='center'>
      <a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&otunnus=$otunnus&alku=0&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&nayta_siirrettavat=$nayta_siirrettavat&ojarj=$ojarj$ulisa'><<<</a>
      &nbsp;&nbsp;
      <a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&otunnus=$otunnus&alku=$edellinen&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&nayta_siirrettavat=$nayta_siirrettavat&ojarj=$ojarj$ulisa'><< ".t("Edellinen sivu")."</a>
      <a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&otunnus=$otunnus&alku=$seuraava&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&nayta_siirrettavat=$nayta_siirrettavat&ojarj=$ojarj$ulisa'>".t("Seuraava sivu")." >></a>
      &nbsp;&nbsp;
      <a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&otunnus=$otunnus&alku=$viimenen_sivu&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&nayta_siirrettavat=$nayta_siirrettavat&ojarj=$ojarj$ulisa'>>>></a>
      </td>";

  echo "</tr>";

  echo "<tr></tr><tr><th>x</th>";

  //hakukent‰t
  echo "<form method='post' autocomplete='off'>";
  echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
  echo "<input type='hidden' name='otunnus' value='$otunnus'>";
  echo "<input type='hidden' name='toiminto' value='kohdista'>";

  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
    echo "<input type='hidden' name='nayta_siirrettavat' value='$nayta_siirrettavat'>";
  }

  echo "<input type='hidden' name='alku' value='$alku'>";
  echo "<input type='hidden' name='tila' value='$tila'>";
  echo "<input type='hidden' name='tee' value='$tee'>";
  echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
  echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
  echo "<input type='hidden' name='ojarj' value='$ojarj'>";

  $href = "$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&ojarj";

  echo "<th nowrap class='ptop'>";
  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") echo "<a href='$href=14".$ulisa."'>$kennim_array[14]</a><br>";
  echo "<a href='$href=0".$ulisa."'>$kennim_array[0]</a></th>";

  echo "<th nowrap class='ptop'><a href='$href=1".$ulisa."'>$kennim_array[1]</a><br><a href='$href=2".$ulisa."'>$kennim_array[2]</a></th>";

  echo "<th nowrap class='ptop'>";
  echo "<a href='$href=3".$ulisa."'>$kennim_array[3]</a><br />";
  echo "</th>";

  echo "<th nowrap class='ptop'><a href='$href=4".$ulisa."'>$kennim_array[4]</a><br><a href='$href=5".$ulisa."'>$kennim_array[5]</a></th>";
  echo "<th nowrap class='ptop'><a href='$href=6".$ulisa."'>$kennim_array[6]</a><br><a href='$href=7".$ulisa."'>$kennim_array[7]</a></th>";

  $pointteri = 8;

  for ($alepostfix=1; $alepostfix <= $yhtiorow["oston_alekentat"]; $alepostfix++) {
    echo "<th nowrap class='ptop'>$kennim_array[$pointteri]";
    $pointteri++;

    if ($alepostfix == $yhtiorow["oston_alekentat"]) {
      echo "<br>$kennim_array[$pointteri]";
      $pointteri++;
    }

    echo "</th>";
  }

  if (empty($saap_erikoisale_ui)) {
    echo "<th nowrap class='ptop'><a href='$href=".($pointteri).$ulisa."'>$kennim_array[$pointteri]</a></th>";
    $pointteri++;
  }

  echo "<th nowrap class='ptop'><a href='$href=".($pointteri).$ulisa."'>$kennim_array[$pointteri]</a><br>";
  $pointteri++;

  echo "<a href='$href=".($pointteri).$ulisa."'>$kennim_array[$pointteri]</a></th>";
  $pointteri++;

  if ($yhtiorow['myyntihinta_paivitys_saapuminen'] == 'K') {
    echo "<th nowrap class='ptop'><a href='{$href}={$pointteri}{$ulisa}'>{$kennim_array[$pointteri]}</a></th>";
    $pointteri++;
  }

  echo "<th nowrap class='ptop'><a href='$href=".($pointteri).$ulisa."'>$kennim_array[$pointteri]</a><br>";
  $pointteri++;

  echo "<a href='$href=".($pointteri).$ulisa."'>$kennim_array[$pointteri]</a></th>";
  $pointteri++;

  echo "<th></th>";
  echo "</tr>";

  // hakukent‰t alkaa t‰st‰
  echo "<tr><td></td>";

  echo "<td nowrap class='ptop'>";
  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") echo "<input type='text' size='$kenkok_array[14]' name='haku[14]' value='$haku[14]'><br>";
  echo "<input type='text' size='$kenkok_array[0]' name='haku[0]' value='$haku[0]'></td>";

  echo "<td nowrap class='ptop'><input type='text' size='$kenkok_array[1]' name='haku[1]' value='$haku[1]'><br><input type='text' size='$kenkok_array[2]' name='haku[2]' value='$haku[2]'></td>";

  echo "<td nowrap class='ptop'>";
  echo "<input type='text' size='$kenkok_array[3]' name='haku[3]' value='$haku[3]'>";
  echo "</td>";

  echo "<td nowrap class='ptop'><input type='text' size='$kenkok_array[4]' name='haku[4]' value='$haku[4]'><br><input type='text' size='$kenkok_array[5]' name='haku[5]' value='$haku[5]'></td>";
  echo "<td nowrap class='ptop' align='right'><input type='text' size='$kenkok_array[6]' name='haku[6]' value='$haku[6]'><br><input type='text' size='$kenkok_array[7]' name='haku[7]' value='$haku[7]'></td>";

  $pointteri = 8;

  for ($alepostfix=1; $alepostfix <= $yhtiorow["oston_alekentat"]; $alepostfix++) {
    echo "<td nowrap class='ptop' align='right'><input type='text' size='$kenkok_array[$pointteri]' name='haku[$pointteri]' value='$haku[$pointteri]'>";
    $pointteri++;

    if ($alepostfix == $yhtiorow["oston_alekentat"]) {
      echo "<br><input type='text' size='$kenkok_array[$pointteri]' name='haku[$pointteri]' value='$haku[$pointteri]'>";
      $pointteri++;
    }

    echo "</td>";
  }

  if (empty($saap_erikoisale_ui)) {
    echo "<td nowrap class='ptop'><input type='text' size='$kenkok_array[$pointteri]' name='haku[$pointteri]' value='$haku[$pointteri]'></td>";
    $pointteri++;
  }

  echo "<td nowrap class='ptop'><input type='text' size='$kenkok_array[$pointteri]' name='haku[$pointteri]' value='$haku[$pointteri]'><br>";
  $pointteri++;
  echo "<input type='text' size='$kenkok_array[$pointteri]' name='haku[$pointteri]' value='$haku[$pointteri]'></td>";
  $pointteri++;

  echo "<td nowrap class='ptop'><input type='text' size='$kenkok_array[$pointteri]' name='haku[$pointteri]' value='$haku[$pointteri]'><br>";
  $pointteri++;

  if ($yhtiorow['myyntihinta_paivitys_saapuminen'] == 'K') {
    echo "<td nowrap class='ptop'><input type='text' size='$kenkok_array[$pointteri]' name='haku[$pointteri]' value='$haku[$pointteri]'><br>";
    $pointteri++;
  }

  echo "<input type='text' size='$kenkok_array[$pointteri]' name='haku[$pointteri]' value='$haku[$pointteri]'></td>";

  $toimipaikka = isset($toimipaikka) ? $toimipaikka : "kaikki";

  echo "<td nowrap class='ptop'><input type='hidden' name='toimipaikka' value='{$toimipaikka}' /></td>";
  echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Etsi")."'></td>";
  echo "</tr></form>";

  // Katsotaan lˆytyikˆ yht‰‰n tilausrivi‰
  if (mysql_num_rows($result) == 0) {
    echo "<tr><td colspan='5' class='back'>".t("Yht‰‰n kohdistettavaa rivi‰ ei lˆydy")."!</td></tr>";
  }

  //itse rivit
  echo "  <SCRIPT LANGUAGE=JAVASCRIPT>
        function submit_juppe(ankkuri){
          document.paaformi.action='$PHP_SELF#'+ankkuri;
          document.paaformi.submit();
        }
      </SCRIPT>";

  echo "<form name='paaformi' method='post'>";
  echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
  echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}' />";
  echo "<input type='hidden' name='toiminto' value='kohdista'>";

  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
    echo "<input type='hidden' name='nayta_siirrettavat' value='$nayta_siirrettavat'>";
    echo "<input type='hidden' name='tila' value='tee_siirto'>";
  }
  else {
    echo "<input type='hidden' name='tila' value='tee_kohdistus'>";
  }

  echo "<input type='hidden' name='otunnus' value='$otunnus'>";
  echo "<input type='hidden' name='alku' value='$alku'>";
  echo "<input type='hidden' name='tee' value='K'>";
  echo "<input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>";
  echo "<input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>";
  echo $hiddenit;
  echo "<input type='hidden' name='ojarj' value=\"$ojarj\">";

  $tuoeyoikeus = tarkista_oikeus('yllapito.php', 'tuote', 1);

  $rivilask = 0;

  while ($rivirow = mysql_fetch_assoc($result)) {

    echo "<tr class='aktiivi'>";

    if ($rivirow["uusiotunnus"] > 0) {
      $chk = "checked";
      $td  = "td class='tumma ptop'";
    }
    else {
      $chk = '';

      if ($rivirow["perheid2"] > 0 and $rivirow["perheid2"] != $rivirow["rivitunnus"]) {
        $td = "td class='spec ptop'";
      }
      else {
        $td = "td class='ptop'";
      }
    }

    if ($rivirow["perheid"] > 0 or $rivirow["perheid2"] > 0) {
      if ($rivirow["perheid"] == 0) {
        $pklisa = " and perheid2 = '$rivirow[perheid2]'";
      }
      else {
        $pklisa = " and (perheid = '$rivirow[perheid]' or perheid2 = '$rivirow[perheid]')";
      }
    }

    if ($rivirow["rivitunnus"] == $rivirow["perheid2"] and $rivilask != 0) {
      echo "<tr><td class='back'><br></td></tr>";
    }

    $onkosuoratoimitus = FALSE;
    $onkosuoratoimitus_suoraan_asiakkaalle = FALSE;

    $onko_suoratoimitus_res = onko_suoratoimitus($rivirow['rivitunnus']);

    //Tutkitaan lˆytyykˆ JT-rivi joka m‰pp‰ytyy t‰h‰n ostoriviin
    if ($varastoon_row = mysql_fetch_assoc($onko_suoratoimitus_res)) {
      $onkosuoratoimitus = TRUE;

      if ($varastoon_row["suoraan_laskutukseen"] != "") {
        $rivirow["kommentti"] .= "<br><font class='info'>".t("Suoratoimitus asiakkaalle").":</font> ".$varastoon_row["nimi"];
        $onkosuoratoimitus_suoraan_asiakkaalle = TRUE;
      }
      else {
        $rivirow["kommentti"] .= "<br><font class='info'>".t("Tilattu asiakkaalle").":</font> ".$varastoon_row["nimi"];
      }
    }
    else {
      // katsotaan onko tuotetta j‰lkitoimituksessa
      $query = "SELECT sum(varattu+jt) kpl
                FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika)
                WHERE yhtio        = '{$kukarow['yhtio']}'
                AND tyyppi         = 'L'
                AND tuoteno        = '{$rivirow['tuoteno']}'
                AND laskutettuaika = '0000-00-00'
                AND var            = 'J'";
      $jt_check_res = pupe_query($query);
      $jt_check_row = mysql_fetch_assoc($jt_check_res);

      if ($jt_check_row['kpl'] > 0) {
        $rivirow['kommentti'] .= "<br/>".t("J‰lkitoimituksessa")." {$jt_check_row['kpl']} ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[tilyksikko]'", "", "", "selite");
      }
    }

    $rivilask++;

    // Tuoteperheiden lapsille ei n‰ytet‰ rivinumeroa
    if ($rivirow["perheid"] == $rivirow["rivitunnus"] or ($rivirow["perheid2"] == $rivirow["rivitunnus"] and $rivirow["perheid"] == 0)) {
      $query = "SELECT count(*) kpl
                from tilausrivi
                where yhtio = '$kukarow[yhtio]'
                and otunnus in ($rivirow[otunnus],$otunnus)
                $pklisa";
      $pkres = pupe_query($query);
      $pkrow = mysql_fetch_assoc($pkres);

      $pknum     = $pkrow['kpl'];
      $borderlask = $pkrow['kpl'];

    }
    elseif ($rivirow["perheid"] == 0 and $rivirow["perheid2"] == 0) {
      $pknum     = 0;
      $borderlask = 0;
    }

    $classlisa   = "";
    $class     = "";
    $classeka  = "";

    if ($borderlask == 1 and $pknum == 1) {
      $classeka  = " style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;' ";
      $classlisa = " style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
      $class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

      $borderlask--;
    }
    elseif ($borderlask == $pknum and $pknum > 0) {
      $classeka  = " style='border-top: 1px solid; border-left: 1px solid; ";
      $classlisa = " style='border-top: 1px solid; border-right: 1px solid;' ";
      $class    .= " style='border-top: 1px solid;' ";
      $borderlask--;
    }
    elseif ($borderlask == 1) {
      if ($pknum > 1) {
        $classeka  = " style='border-bottom: 1px solid; border-left: 1px solid; ";
        $classlisa =" style='font-style:italic; border-bottom: 1px solid; border-right: 1px solid;' ";
        $class    .= " style='font-style:italic; border-bottom: 1px solid;' ";
      }
      else {
        $classeka  = " style='border-bottom: 1px solid; border-left: 1px solid; ";
        $classlisa = $class." style='border-bottom: 1px solid; border-right: 1px solid;' ";
        $class    .= " style='border-bottom: 1px solid;' ";
      }

      $borderlask--;
    }
    elseif ($borderlask > 0 and $borderlask < $pknum) {
      $classeka  = " style='border-left: 1px solid;' ";
      $classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
      $class    .= " style='font-style:italic;' ";
      $borderlask--;
    }

    // n‰ytet‰‰n vaan tsekpoksi jos rivi‰ saa viel‰ ru(n)kata...
    if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
      echo "<$td $classeka>";
      echo "<input type='checkbox' name='rivi_siirrettavat_tunnukset[]' value='$rivirow[rivitunnus]###$rivirow[uusiotunnus]' onclick='submit_juppe($rivirow[rivitunnus])'>";
      echo "<input type='hidden'   name='rivi_kaikki_tunnukset[]'  value='$rivirow[rivitunnus]###$rivirow[uusiotunnus]'>";
    }
    elseif ($rivirow["varastossa_kpl"] == 0) {
      echo "<$td $classeka>";
      echo "<a name='$rivirow[rivitunnus]'>";

      //tsekataan is‰n status
      $query = "SELECT kpl
                from tilausrivi
                where yhtio = '$kukarow[yhtio]'
                and tunnus  = '$rivirow[perheid2]'";
      $isapkres = pupe_query($query);
      $isapkrow = mysql_fetch_assoc($isapkres);

      if ($rivirow["rivitunnus"] == $rivirow["perheid2"] or $rivirow["perheid2"] == 0 or $isapkrow["kpl"] != 0) {
        echo "<input type='checkbox' name='rivi_valitut_tunnukset[]' value='$rivirow[rivitunnus]###$rivirow[otunnus]' onclick='submit_juppe($rivirow[rivitunnus])' $chk>";
      }

      echo "<input type='hidden'   name='rivi_kaikki_tunnukset[]'  value='$rivirow[rivitunnus]###$rivirow[otunnus]'>";
      echo "<input type='hidden'   name='rivi_summa' value='$rivirow[alerivihinta]'>";
      echo "</$td>";

    }
    elseif ($rivirow["varastossa_kpl"] != 0) {
      echo "<$td $classeka>x</$td>";
    }
    else {
      echo "<$td $classeka></$td>";
    }

    echo "<$td $class>";
    if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") echo "$rivirow[keikka]<br>";

    // tooltip jos ostotilauksella on kommentti
    if ($rivirow["comments"] != '') {
      echo "
      <div id='div_comment_$rivirow[rivitunnus]' class='popup' style='width: 400px;'>
      {$rivirow["comments"]}
      </div>
      <a class='tooltip' id='comment_$rivirow[rivitunnus]'>$rivirow[otunnus]</a></$td>";
    }
    else {
      echo "$rivirow[otunnus]</$td>";
    }
    echo "<$td $class nowrap>";
    echo js_openUrlNewWindow("{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($rivirow["tuoteno"]), $rivirow["tuoteno"], "", 1000, 1000);
    echo "<br>$rivirow[toim_tuoteno]";
    echo "&nbsp;";

    $_src = "{$palvelin2}pics/lullacons/info.png";
    $_title = t("N‰yt‰ saldo");

    $id = md5(uniqid());

    echo "<img title='{$_title}' src='{$_src}' class='hae_saldo' id='{$id}' />";

    // tehd‰‰n pop-up divi jos keikalla on kommentti...
    $pop_yks = t_avainsana("Y", "", "and avainsana.selite='$rivirow[tilyksikko]'", "", "", "selite");

    echo "<div id='div_{$id}' style='display:none;'>";
    echo "<ul>";
    echo "<li>".t("Tilattu").": ".(float) $rivirow["tilattu"]." $pop_yks</li>";
    echo "<li>".t("Varattu").": ".(float) $rivirow["varattu"]." $pop_yks</li>";
    echo "<li>".t("Keskihinta").": ".(float) $rivirow["keskihinta"]." $rivirow[valuutta]</li>";
    echo "<li>".t("Ostohinta").": ".(float) $rivirow["ostohinta"]." $rivirow[valuutta]</li>";
    echo "<li>".t("Kuluprosentti").": ".(float) $rivirow["kuluprosentti"]." %</li>";

    echo "<input type='hidden' id='{$id}_tuoteno' value='{$rivirow['tuoteno']}' />";
    echo "<input type='hidden' id='{$id}_varasto' value='{$rivirow['varasto']}' />";
    echo "<span id='span_{$id}'></span>";
    echo "</ul>";
    echo "</div>";

    if ($rivirow["sarjanumeroseuranta"] != "" and $rivirow["sarjanumeroseuranta"] != "T") {
      if ($rivirow["siskpl"] < 0) {
        $tunken = "sarjanumeroseuranta.myyntirivitunnus";
        $tunind = "yhtio_myyntirivi";
      }
      else {
        $tunken = "sarjanumeroseuranta.ostorivitunnus";
        $tunind = "yhtio_ostorivi";
      }

      if ($rivirow["sarjanumeroseuranta"] == "S" or $rivirow["sarjanumeroseuranta"] == "V") {
        $query = "SELECT count(distinct sarjanumero) kpl, min(sarjanumero) sarjanumero
                  FROM sarjanumeroseuranta
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tuoteno = '$rivirow[tuoteno]'
                  and $tunken = '$rivirow[rivitunnus]'";

        $snro_ok = t("S:nro ok");
        $snro   = t("S:nro");
      }
      else {
        $query = "SELECT sum(sarjanumeroseuranta.era_kpl*if(tilausrivi.tunnus is not null, if(tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt > 0 or tilausrivi.tyyppi = 'O', 1, -1), 1)) kpl, min(sarjanumeroseuranta.sarjanumero) sarjanumero
                  FROM sarjanumeroseuranta
                  LEFT JOIN tilausrivi ON (tilausrivi.yhtio = sarjanumeroseuranta.yhtio and tilausrivi.tunnus = sarjanumeroseuranta.myyntirivitunnus)
                  WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
                  and sarjanumeroseuranta.tuoteno = '$rivirow[tuoteno]'
                  and $tunken = '$rivirow[rivitunnus]'";

        $snro_ok = t("E:nro ok");
        $snro   = t("E:nro");
      }

      $sarjares = pupe_query($query);
      $sarjarow2 = mysql_fetch_assoc($sarjares);

      if ($sarjarow2["kpl"] == abs($rivirow["siskpl"])) {
        echo "<br>(<a href='$PHP_SELF?tila=sarjanumerot&toiminto=kohdista&toimittajaid=$toimittajaid&tuoteno=".urlencode($rivirow["tuoteno"])."&ostorivitunnus=$rivirow[rivitunnus]&from=kohdista&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&ojarj=$ojarj$ulisa#$sarjarow2[sarjanumero]' class='green'>$snro_ok</font></a>)";
      }
      else {
        echo "<br>(<a href='$PHP_SELF?tila=sarjanumerot&toiminto=kohdista&toimittajaid=$toimittajaid&tuoteno=".urlencode($rivirow["tuoteno"])."&ostorivitunnus=$rivirow[rivitunnus]&from=kohdista&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&ojarj=$ojarj$ulisa#$sarjarow2[sarjanumero]' style='color:#DD0000;'>$snro</a>)";
      }
    }
    echo "</$td>";

    echo "<{$td} {$class}>";

    if ($rivirow["automaattinen_sarjanumerointi"] == 1 and empty($nayta_siirrettavat)) {
      echo "<input size='15' type='text' name='rivi_nimitys[$rivirow[rivitunnus]]' value='$rivirow[nimitys]'>";
    }
    else {
      echo t_tuotteen_avainsanat($rivirow, 'nimitys');
    }

    echo "</{$td}>";

    // jos rivi‰ ei ole viel‰ viety varastoon, voidaan muuttaa kappaleita
    if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES" and $rivirow["sarjanumeroseuranta"] == "" and !$onkosuoratoimitus) {
      echo "<$td $class align='right'><input id='maara_{$rivirow["rivitunnus"]}' size='5' type='text' name='siirrettavat_kpl[$rivirow[rivitunnus]]' value='". (float) $rivirow["siskpl"]."' style='text-align:right'> ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[tilyksikko]'", "", "", "selite")."<br>". (float) $rivirow["ulkkpl"]."&nbsp;&nbsp;".t_avainsana("Y", "", "and avainsana.selite='$rivirow[toimyksikko]'", "", "", "selite")."</$td>";
    }
    elseif ($rivirow["varattu"] != 0 and !$onkosuoratoimitus) {
      echo "<$td $class align='right'><input input id='maara_{$rivirow["rivitunnus"]}' size='5' type='text' name='rivi_kpl[$rivirow[rivitunnus]]' value='". (float) $rivirow["siskpl"]."' style='text-align:right'> ".t_avainsana("Y", "", "and avainsana.selite='$rivirow[tilyksikko]'", "", "", "selite")."<br>". (float) $rivirow["ulkkpl"]."&nbsp;&nbsp;".t_avainsana("Y", "", "and avainsana.selite='$rivirow[toimyksikko]'", "", "", "selite");
      echo "<input type='hidden' name='rivi_kpl_alkuperainen[$rivirow[rivitunnus]]' value='". (float) $rivirow["siskpl"]."'";
      echo"</$td>";
    }
    else {
      echo "<$td $class align='right'>". (float) $rivirow["siskpl"]."<br>". (float) $rivirow["ulkkpl"]."</$td>";
    }

    if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
      echo "<$td $class align='right'>".hintapyoristys($rivirow["hinta"], 6)."<br>$kulurow[valkoodi]&nbsp;</$td>";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
        echo "<$td $class align='right'>". (float) $rivirow["ale$alepostfix"];

        if ($alepostfix == $yhtiorow["oston_alekentat"]) {
          if ($laskurow['vienti']=='C' or $laskurow['vienti']=='J') {
            echo "<br>". (float) $rivirow["alv"];
          }
          else {
            echo "<br>0";
          }
        }

        echo "</$td>";
      }

    }
    else {
      echo "<$td $class align='right'>";

      $hinta_name = "rivi_hinta[$rivirow[rivitunnus]]";

      echo
      "<input size='7' type='text' name='{$hinta_name}' value='" .
        hintapyoristys($rivirow["hinta"], 6) .
        "' style='text-align:right'>";
      echo "<br>$kulurow[valkoodi]</$td>";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
        echo "<$td $class align='right'>";
        echo "<input size='4' type='text' class='ale-kentta-{$rivirow["rivitunnus"]}' name='rivi_ale{$alepostfix}[$rivirow[rivitunnus]]' value='". (float) $rivirow["ale$alepostfix"]."' style='text-align:right'>";

        if ($alepostfix == $yhtiorow["oston_alekentat"]) {
          if ($laskurow['vienti']=='C' or $laskurow['vienti']=='J') {
            echo "<br>". (float) $rivirow["alv"]."&nbsp;";
          }
          else {
            echo "<br>0&nbsp;";
          }
        }

        echo "</$td>";
      }

    }

    if (empty($saap_erikoisale_ui)) {
      echo "<$td $class align='right'><input size='4' type='text' name='rivi_erikoisale[$rivirow[rivitunnus]]' class='ale-kentta-{$rivirow["rivitunnus"]}' value='". (float) $rivirow["erikoisale"]."' style='text-align:right'></$td>";
    }

    echo "<$td $class align='right'>". (float) $rivirow["rivihinta"]."<br>". (float) $rivirow["alerivihinta"]."</$td>";

    if ($yhtiorow['myyntihinta_paivitys_saapuminen'] == 'K') {

      echo "<{$td} {$class} align='right'>";

      if ($tuoeyoikeus) {
        $myyntihinta_name = "rivi_myyntihinta[{$rivirow['rivitunnus']}]";

        echo
        "<input type='text' size='7' name='{$myyntihinta_name}' value='" .
          hintapyoristys($rivirow['myyntihinta'], 6) .
          "' style='text-align: right;' />";
      }
      else {
        echo hintapyoristys($rivirow['myyntihinta'], 6);
      }

      if (($rivirow["myyntialv"] > 0 and $yhtiorow["alv_kasittely"] == "")) {
        $alvi = $rivirow["myyntialv"];
      }
      else {
        $alvi = 0;
      }

      $ulkkpl = isset($rivirow["ulkkpl"]) ? $rivirow["ulkkpl"] : 0;
      $siskpl = isset($rivirow["siskpl"]) ? $rivirow["siskpl"] : 0;

      $javascript =
        "$(function () {
           var myyntihintaInput = $('input[name=\"{$myyntihinta_name}\"]');
           var hintaInput = $('input[name=\"{$hinta_name}\"]');
           var maaraInput = $('#maara_{$rivirow["rivitunnus"]}');
           var aleInputit = $('.ale-kentta-{$rivirow["rivitunnus"]}');
           var alv = {$alvi};
           var tuotekerroin = {$ulkkpl};
           var siskpl = {$siskpl};

           var paivitaKate = function() {
             var kateContainer = $('#kate_{$rivirow['rivitunnus']}');
             var myyntihinta = myyntihintaInput.val().replace(',','.');

             if (maaraInput.val()) {
               var maara = maaraInput.val().replace(',','.');
             } else {
               var maara = siskpl;
             }

             myyntihinta = alv > 0 ? (myyntihinta / (1 + alv / 100)) : myyntihinta;

             var hinta = parseFloat(hintaInput.val().replace(',','.'));

             for (var i = 0;i < aleInputit.length;i++) {
               var ale = parseFloat($(aleInputit[i]).val());

               if (ale > 0) {
                 hinta = hinta - hinta * (ale / 100);
               }
             }

             if (tuotekerroin != maara) {
               hinta = tuotekerroin * hinta / maara;
             }

             if (myyntihinta > 0 && hinta > 0) {
               var kate = ((myyntihinta - hinta) / myyntihinta * 100).toFixed() + ' %';
             }
             else {
               kate = '0 %'
             }

             kateContainer.text(kate);
           };

           myyntihintaInput.on('input', paivitaKate);
           hintaInput.on('input', paivitaKate);
           maaraInput.on('input', paivitaKate);

           for (var i = 0;i < aleInputit.length;i++) {
             $(aleInputit[i]).on('input', paivitaKate);
           }

           paivitaKate();
         });";

      echo "<script>{$javascript}</script>";

      echo "<br>
            " . t("Kate") . ": <span id='kate_{$rivirow['rivitunnus']}'></span>";

      echo "</{$td}>";
    }

    echo "<$td $class>";

    if ($rivirow["toimaika"] != "0000-00-00") {
      echo tv1dateconv($rivirow["toimaika"]);
    }

    echo "<br>$rivirow[kommentti]";

    if ($onkosuoratoimitus_suoraan_asiakkaalle) {

      $toimitettuaikavv = substr($rivirow["suoratoimitettuaika"], 0, 4);
      $toimitettuaikakk = substr($rivirow["suoratoimitettuaika"], 5, 2);
      $toimitettuaikapp = substr($rivirow["suoratoimitettuaika"], 8, 2);

      if ($toimitettuaikavv == 0) $toimitettuaikavv = "";
      if ($toimitettuaikakk == 0) $toimitettuaikakk = "";
      if ($toimitettuaikapp == 0) $toimitettuaikapp = "";

      if ($rivirow["varastossa_kpl"] == 0) {
        echo "<br><font class='info'>".t("Toimitettu asiakkaalle").":</font><br>
            <input size='4' type='text' name='rivi_toimitettuaika_pp[$rivirow[rivitunnus]]' value='$toimitettuaikapp' style='text-align:right'>
            <input size='4' type='text' name='rivi_toimitettuaika_kk[$rivirow[rivitunnus]]' value='$toimitettuaikakk' style='text-align:right'>
            <input size='4' type='text' name='rivi_toimitettuaika_vv[$rivirow[rivitunnus]]' value='$toimitettuaikavv' style='text-align:right'>";
      }
      elseif ($toimitettuaikavv != "") {
        echo "<br><font class='info'>".t("Toimitettu asiakkaalle").":</font> ".tv1dateconv("$toimitettuaikavv-$toimitettuaikakk-$toimitettuaikapp");
      }
    }

    echo "</$td>";

    //Tutkitaan tuotteiden lis‰varusteita
    $query  = "SELECT tuote.tuoteno, tuote.nimitys, tuotteen_toimittajat.toim_tuoteno
               FROM tuoteperhe
               JOIN tuote ON (tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tuoteno=tuote.tuoteno and tuote.ei_saldoa = '')
                LEFT JOIN tuotteen_toimittajat ON tuotteen_toimittajat.yhtio=tuote.yhtio and tuotteen_toimittajat.tuoteno=tuote.tuoteno and tuotteen_toimittajat.liitostunnus='$toimittajaid'
               where tuoteperhe.yhtio    = '$kukarow[yhtio]'
               and tuoteperhe.isatuoteno = '$rivirow[tuoteno]'
               and tuoteperhe.tyyppi     = 'L'
               order by tuoteperhe.tuoteno";
    $lisaresult = pupe_query($query);

    echo "<$td $classlisa>";

    if ($rivirow["varastossa_kpl"] == 0) {
      echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&ojarj=$ojarj$ulisa&riviid=$rivirow[rivitunnus]&tila=muokkaa_rivi'><img src='{$palvelin2}pics/lullacons/doc-option-edit.png'></a><br>";
    }

    if (empty($nayta_siirrettavat) and $rivirow["rivitunnus"] == $rivirow["perheid2"] and mysql_num_rows($lisaresult) > 0) {
      echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&ojarj=$ojarj$ulisa&lisaa_lisavarusteita=$rivirow[rivitunnus]'>".t("Lis‰varusteet")."</a><br>";
    }

    if (empty($nayta_siirrettavat) and ($rivirow["sarjanumeroseuranta"] == "S" or $rivirow["sarjanumeroseuranta"] == "V") and ($rivirow["rivitunnus"] == $rivirow["perheid2"] or $rivirow["perheid2"] == 0)) {
      echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&otunnus=$otunnus&alku=$alku&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&ojarj=$ojarj$ulisa&riviid=$rivirow[rivitunnus]&tila=uusirivi&perheid2=$rivirow[rivitunnus]'>".t("Liit‰ rivi")."</a>";
    }

    echo "</$td>";

    if (mysql_num_rows($lisaresult) > 0 and ($rivirow["perheid2"] == 0 or (isset($lisaa_lisavarusteita) and $lisaa_lisavarusteita > 0))) {

      echo "</tr>\n";

      while ($lisavar_row = mysql_fetch_assoc($lisaresult)) {
        echo "<tr>
            <$td colspan='2' class='back'></$td>
            <$td>$lisavar_row[tuoteno]<br>$lisavar_row[toim_tuoteno]</$td>";

        echo "<$td>".t_tuotteen_avainsanat($lisavar_row, 'nimitys')."</$td>";
        echo "<$td align='right'><input size='5' type='text' name='lisavarusterivi_kpl[$rivirow[rivitunnus]##$lisavar_row[tuoteno]#*#$rivirow[otunnus]]' style='text-align:right'></$td>";
        echo "<$td align='right'><input size='7' type='text' name='lisavarusterivi_hinta[$rivirow[rivitunnus]##$lisavar_row[tuoteno]#*#$rivirow[otunnus]]' style='text-align:right'><br>$kulurow[valkoodi]</$td>";
        echo "</tr>";
      }
    }
  }

  echo "<tr><td class='back' colspan='12' align='center'>";
  echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&otunnus=$otunnus&alku=$edellinen&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&nayta_siirrettavat=$nayta_siirrettavat&ojarj=$ojarj$ulisa'><< ".t("Edellinen sivu")."</a> ";
  echo "<a href='$PHP_SELF?toiminto=kohdista&toimittajaid=$toimittajaid&otunnus=$otunnus&alku=$seuraava&nayta_kohdistetut=$nayta_kohdistetut&nayta_kaikki=$nayta_kaikki&tila=$tila&nayta_siirrettavat=$nayta_siirrettavat&ojarj=$ojarj$ulisa'>".t("Seuraava sivu")." >></a>";
  echo "</th></tr>";
  echo "</table>";

  echo "<br>";

  echo "<table>";
  echo "<tr>";
  echo "<td class='back'>";
  echo "<input type='submit' value='".t("Tallenna muutokset")."'>";
  echo "<input type='submit' name='kohdista_koko_sivu'  value='".t("Kohdista kaikki rivit")."'>";

  if (isset($nayta_siirrettavat) and $nayta_siirrettavat == "YES") {
    echo "</form>";
  }
  else {
    echo "<input type='submit' name='ala_kohdista_mitaan' value='".t("Poista kaikki kohdistukset")."'>";
    echo "</form>";

    echo "<form method='post'>
        <input type='hidden' name='toimittajaid' value='$toimittajaid'>
        <input type='hidden' name='toiminto' value='kohdista'>
        <input type='hidden' name='tila' value='uusirivi'>
        <input type='hidden' name='tee' value='K'>
        <input type='hidden' name='alku' value='$alku'>
        <input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>
        <input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>
        $hiddenit
        <input type='hidden' name='ojarj' value=\"$ojarj\">
        <input type='hidden' name='otunnus' value='$otunnus'>
        <input type='hidden' name='toimipaikka' value='{$toimipaikka}'>
        <input type='submit' value='".t("Tee uusi rivi")."'>
        </form>";

    if ($onkologmaster and $laskurow['sisviesti3'] == '') {
      echo "<form method='post'>
          <input type='hidden' name='toimittajaid' value='$toimittajaid'>
          <input type='hidden' name='toiminto' value='saapuminen_ulkoiseen_jarjestelmaan'>
          <input type='hidden' name='tee' value='K'>
          <input type='hidden' name='alku' value='$alku'>
          <input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>
          <input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>
          $hiddenit
          <input type='hidden' name='ojarj' value=\"$ojarj\">
          <input type='hidden' name='otunnus' value='$otunnus'>
          <input type='hidden' name='toimipaikka' value='{$toimipaikka}'>
          <input type='submit' value='".t("L‰het‰ ulkoiseen j‰rjestelm‰‰n")."'>
          </form>";
    }

    echo "<form name='formi' method='post'>
        <input type='hidden' name='toimittajaid' value='$toimittajaid'>
        <input type='hidden' name='toiminto' value='kohdista'>
        <input type='hidden' name='alku' value='$alku'>
        <input type='hidden' name='otunnus' value='$otunnus'>
        <input type='hidden' name='tee' value='$tee'>
        <input type='hidden' name='tila' value='FAILISTA'>
        $hiddenit
        <input type='hidden' name='ojarj' value=\"$ojarj\">
        <input type='hidden' name='toimipaikka' value='{$toimipaikka}'>
        <input type='submit' value='".t("Kohdista tiedostosta")."'>
        </form>";

    echo "<form method='post'>
        <input type='hidden' name='toimittajaid' value='$toimittajaid'>
        <input type='hidden' name='toiminto' value='kohdista'>
        <input type='hidden' name='tila' value=''>
        <input type='hidden' name='nayta_siirrettavat' value='YES'>
        <input type='hidden' name='tee' value='$tee'>
        <input type='hidden' name='alku' value='$alku'>
        <input type='hidden' name='nayta_kohdistetut' value='$nayta_kohdistetut'>
        <input type='hidden' name='nayta_kaikki' value='$nayta_kaikki'>
        $hiddenit
        <input type='hidden' name='ojarj' value=\"$ojarj\">
        <input type='hidden' name='otunnus' value='$otunnus'>
        <input type='hidden' name='toimipaikka' value='{$toimipaikka}'>
        <input type='submit' value='".t("Siirr‰ rivej‰ toiselta saapumiselta")."'>
        </form>";

    echo "<form method='post'>
        <input type='submit' value='".t("Kohdistus valmis")."'>
        <input type='hidden' name='toimittajaid' value='$toimittajaid'>
        <input type='hidden' name='toiminto' value='kohdista'>
        <input type='hidden' name='laskunsumma' value='$laskunsumma'>
        <input type='hidden' name='tee' value='K'>
        <input type='hidden' name='tila' value='valmis'>
        <input type='hidden' name='toimipaikka' value='{$toimipaikka}'>
        <input type='hidden' name='otunnus' value='$otunnus'>
        </form>";
  }

  echo "</td>";
  echo "</tr></table>";

  $tila = "eityhja";
}
