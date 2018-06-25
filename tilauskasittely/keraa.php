<?php

if (php_sapi_name() != 'cli' and strpos($_SERVER['SCRIPT_NAME'], "keraa.php") !== FALSE) {
  require "../inc/parametrit.inc";

  if ($yhtiorow['kerays_riveittain'] == 'K' and $ajax_toiminto == 'kerattavatrivit') {

    $tunnus = (int) $tunnus;
    $hyllyalue = mysql_real_escape_string($hyllyalue);
    $hyllynro = mysql_real_escape_string($hyllynro);
    $hyllyvali = mysql_real_escape_string($hyllyvali);
    $hyllytaso = mysql_real_escape_string($hyllytaso);
    $poikkeama_kasittely = mysql_real_escape_string($poikkeama_kasittely);

    if (trim($poikkeava_maara) == "") {
      $poikkeava_maara = 'null';
    }
    else {
      $poikkeava_maara = (float) $poikkeava_maara;
    }

    $query = "INSERT INTO kerattavatrivit SET
              tilausrivi_id       = '{$tunnus}',
              hyllyalue           = '{$hyllyalue}',
              hyllynro            = '{$hyllynro}',
              hyllyvali           = '{$hyllyvali}',
              hyllytaso           = '{$hyllytaso}',
              poikkeava_maara     = {$poikkeava_maara},
              poikkeama_kasittely = '{$poikkeama_kasittely}',
              keratty             = 1,
              created_at          = now()
              ON DUPLICATE KEY UPDATE
              hyllyalue           = '{$hyllyalue}',
              hyllynro            = '{$hyllynro}',
              hyllyvali           = '{$hyllyvali}',
              hyllytaso           = '{$hyllytaso}',
              poikkeava_maara     = {$poikkeava_maara},
              poikkeama_kasittely = '{$poikkeama_kasittely}',
              updated_at          = now()";
    $result = pupe_query($query);

    echo $tunnus;
    exit;
  }

  require 'valmistuslinjat.inc';
  require 'validation/Validation.php';
  js_popup();
}

if ($toim == "VASTAANOTA_REKLAMAATIO" and !in_array($yhtiorow['reklamaation_kasittely'], array('U', 'X'))) {
  echo "<font class='error'>", t("HUOM: Ohjelma on käytössä vain kun käytetään laajaa reklamaatioprosessia"), "!</font>";
  exit;
}

if ($yhtiorow['kerays_riveittain'] == 'K') {
  echo "<script type='text/javascript'>
          $(function() {
            $('.kerattavatrivit').on('click', function(e) {
              e.preventDefault();

              var tunnus = $(this).val();
              var hyllyalue, hyllynro, hyllyvali, hyllytaso,
                  paikka_option = $('select[name=\"varastorekla['+tunnus+']\"] > option:selected').val(),
                  poikkeava_maara = $('input[name=\"maara['+tunnus+']\"]').val(),
                  poikkeama_kasittely = $('select[name=\"poikkeama_kasittely['+tunnus+']\"] > option:selected').val();

              if (paikka_option != undefined && paikka_option.length > 0) {
                hyllypaikka = paikka_option.split('###');
                hyllyalue = hyllypaikka[0];
                hyllynro  = hyllypaikka[1];
                hyllyvali = hyllypaikka[2];
                hyllytaso = hyllypaikka[3];
              }

              $.ajax({
                async: false,
                type: 'POST',
                data: {
                  ajax_toiminto: 'kerattavatrivit',
                  tunnus: tunnus,
                  hyllyalue: hyllyalue,
                  hyllynro: hyllynro,
                  hyllyvali: hyllyvali,
                  hyllytaso: hyllytaso,
                  poikkeava_maara: poikkeava_maara,
                  poikkeama_kasittely: poikkeama_kasittely,
                  no_head: 'yes',
                  ohje: 'off'
                },
                url: '{$_SERVER['SCRIPT_NAME']}'
              }).success(function(tunnus) {
                console.log('success: '+tunnus);
                $('#kerattavatrivit_info_'+tunnus).html('<font class=\"ok\">OK</font>');
                $('button[name=\"kerattavatrivit['+tunnus+']\"]').html('", t("Päivitä"), "');

                var kerattavatrivit_ok_rows_count = 0;

                $('.kerattavatrivit_info').each(function() {
                  if ($(this).html() == '<font class=\"ok\">OK</font>') {
                    kerattavatrivit_ok_rows_count += 1;
                  }
                });

                if ($('#total_rivi_count').val() == kerattavatrivit_ok_rows_count) {
                  $('#real_submit').show();
                }
              }).error(function(tunnus) {
                console.log(tunnus);
                $('#kerattavatrivit_info_'+tunnus).html('<font class=\"error\">", t("Virhe"), "</font>');
              });
            });
          });
        </script>";
}

$logistiikka_yhtio = '';
$logistiikka_yhtiolisa = '';

if (!isset($toim))         $toim         = '';
if (!isset($id))           $id           = 0;
if (!isset($tee))          $tee          = '';
if (!isset($jarj))         $jarj         = '';
if (!isset($etsi))         $etsi         = '';
if (!isset($tuvarasto))    $tuvarasto    = '';
if (!isset($tumaa))        $tumaa        = '';
if (!isset($tutoimtapa))   $tutoimtapa   = '';
if (!isset($tutyyppi))     $tutyyppi     = '';
if (!isset($rahtikirjaan)) $rahtikirjaan = '';
if (!isset($sorttaus))     $sorttaus     = '';
if (!isset($keraajalist))  $keraajalist  = '';
if (!isset($sel_lahete))   $sel_lahete   = array();
if (!isset($sel_oslapp))   $sel_oslapp   = array();

$keraysvirhe = 0;
$virherivi   = 0;
$muuttuiko   = '';
$_tarkista_varastot = true;

if (isset($indexvas) and $indexvas == 1 and $tuvarasto == '') {

  $keraakaikistares = t_avainsana("KERAAKAIKISTA");

  if (mysql_num_rows($keraakaikistares) > 0) {

    $keraakaikistarow = mysql_fetch_assoc($keraakaikistares);

    if ($keraakaikistarow['selitetark'] == 'a') {
      $_tarkista_varastot = false;
    }
  }

  // jos käyttäjällä on oletusvarasto, valitaan se
  if ($_tarkista_varastot and $kukarow['oletus_varasto'] != 0) {
    $tuvarasto = $kukarow['oletus_varasto'];
  }
  //  Varastorajaus jos käyttäjällä on joku varasto valittuna
  elseif ($_tarkista_varastot and $kukarow['varasto'] != '' and $kukarow['varasto'] != 0) {
    // jos käyttäjällä on monta varastoa valittuna, valitaan ensimmäinen
    $tuvarasto   = strpos($kukarow['varasto'], ',') !== false ? array_shift(explode(",", $kukarow['varasto'])) : $kukarow['varasto'];
  }
  else {
    $tuvarasto   = "KAIKKI";
  }
}

if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
  $logistiikka_yhtio = $konsernivarasto_yhtiot;
  $logistiikka_yhtiolisa = "yhtio IN ({$logistiikka_yhtio})";

  if (isset($lasku_yhtio) and $lasku_yhtio != '') {
    $kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);

    $yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
  }
}
else {
  $logistiikka_yhtiolisa = "yhtio = '{$kukarow['yhtio']}'";
}

if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
  require_once "inc/unifaun_send.inc";

  if (php_sapi_name() != 'cli' and strpos($_SERVER['SCRIPT_NAME'], "keraa.php") !== FALSE) {
    echo "  <script type='text/javascript' language='JavaScript'>
          $(document).ready(function() {
            $('input[name^=\"keraysera_maara\"]').keyup(function(){
              var rivitunnukset = $(this).attr('id').split(\"_\", 2);
              var yhteensa = 0;

              $('input[id^=\"'+rivitunnukset[0]+'\"]').each(function(){
                yhteensa += Number($(this).val().replace(',', '.'));
              });

              if (parseFloat(yhteensa) == parseFloat($('#'+rivitunnukset[0]+'_varattu').html().replace(',', '.'))) {
                yhteensa = '';
              }

              $('#maara_'+rivitunnukset[0]).val(yhteensa);
              $('#maaran_paivitys_'+rivitunnukset[0]).html(yhteensa);
            });
          });
        </script>";
  }
}

if ($toim == 'SIIRTOLISTA') {
  echo "<font class='head'>", t("Kerää siirtolista"), ":</font><hr>";
  $tila = "'G'";
  $tyyppi = "'G'";
  $tilaustyyppi = " and tilaustyyppi != 'M' ";
}
elseif ($toim == 'SIIRTOTYOMAARAYS') {
  echo "<font class='head'>", t("Kerää sisäinen työmääräys"), ":</font><hr>";
  $tila = "'S'";
  $tyyppi = "'G'";
  $tilaustyyppi = " and tilaustyyppi = 'S' ";
}
elseif ($toim == 'MYYNTITILI') {
  echo "<font class='head'>", t("Kerää myyntitili"), ":</font><hr>";
  $tila = "'G'";
  $tyyppi = "'G'";
  $tilaustyyppi = " and tilaustyyppi = 'M' ";
}
elseif ($toim == 'VALMISTUS') {
  echo "<font class='head'>", t("Kerää valmistus"), ":</font><hr>";
  $tila = "'V'";
  $tyyppi = "'V','L','W'";
  $tilaustyyppi = "";
}
elseif ($toim == 'VALMISTUSMYYNTI') {
  echo "<font class='head'>", t("Kerää tilaus tai valmistus"), ":</font><hr>";
  $tila = "'V','L'";
  $tyyppi = "'V','L'";
  $tilaustyyppi = "";
}
elseif ($toim == 'VASTAANOTA_REKLAMAATIO') {
  echo "<font class='head'>", t("Hyllytä reklamaatio tai palautus"), ":</font><hr>";
  $tila       = "'C'";
  $alatilarekla   = "'C'";
  $tyyppi     = "'L'";
  $tilaustyyppi   = " and tilaustyyppi = 'R'";
}
else {
  echo "<font class='head'>", t("Kerää tilaus"), ":</font><hr>";
  if ($yhtiorow['kerayserat'] != '' and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
    $tila = "'L','G'";
    $alatila = "'A'";
    $tyyppi = "'L','G'";
  }
  else {
    $tila = "'L'";
    $alatila = "'A'";
    $tyyppi = "'L'";
  }
  $tilaustyyppi = "";
}

if ($toim != '') {
  $yhtiorow['karayksesta_rahtikirjasyottoon'] = '';
}
else {

  if ($yhtiorow['kerayserat'] == 'K') {
    if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != 'Y') {
      $yhtiorow['karayksesta_rahtikirjasyottoon'] = '';
    }
  }
  elseif (isset($id) and $id > 0) {
    // Nouto keississä ei mennä rahtikirjan syöttöön (paisti jos on vientiä)
    $query = "SELECT toimitustapa.tunnus
              FROM toimitustapa, lasku, maksuehto
              WHERE toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa
              and lasku.yhtio          = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
              and toimitustapa.yhtio   = '$kukarow[yhtio]'
              and lasku.tunnus         = '$id'
              and ((toimitustapa.nouto is null or toimitustapa.nouto='') or lasku.vienti!='')
              and maksuehto.jv         = ''";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      $yhtiorow['karayksesta_rahtikirjasyottoon'] = '';
    }
  }
}

if (isset($real_submit)) {
  $real_submit = 'yes';
}
else {
  $real_submit = 'no';
}

$var_lisa = "";

if ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") {
  $var_lisa .= ",'J'";
}

if ($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q") {
  $var_lisa .= ",'P'";
}

if ($tee == 'PAKKAUKSET' and ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and isset($kerayserat_asiakas_chk) and $kerayserat_asiakas_chk == 'A'))) {

  if (trim($pakkaukset_kaikille) == "") {
    echo "<br /><font class='error'>", t("Pakkausvalinta ei saa olla tyhjää"), "!</font><br />";
  }
  else {
    $query = "SELECT *
              from lasku
              where yhtio = '{$kukarow['yhtio']}'
              and tunnus  = '{$id}'";
    $testresult = pupe_query($query);
    $laskurow = mysql_fetch_assoc($testresult);

    if ($laskurow['kerayslista'] > 0) {
      //haetaan kaikki tälle klöntille kuuluvat otsikot
      $query = "SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
                FROM lasku
                WHERE yhtio      = '{$kukarow['yhtio']}'
                AND kerayslista  = '{$id}'
                AND kerayslista != 0
                AND tila         IN ({$tila})
                {$tilaustyyppi}
                HAVING tunnukset IS NOT NULL";
      $toimresult = pupe_query($query);

      //jos rivejä löytyy niin tiedetään, että tämä on keräysklöntti
      if (mysql_num_rows($toimresult) > 0) {
        $toimrow = mysql_fetch_assoc($toimresult);
        $tilausnumeroita = $toimrow["tunnukset"];
      }
      else {
        $tilausnumeroita = $id;
      }
    }
    else {
      $tilausnumeroita = $id;
    }

    tee_keraysera_painon_perusteella($laskurow, $tilausnumeroita, $pakkaukset_kaikille);
  }
}

if ($tee == 'P') {

  if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
    $query = "SELECT GROUP_CONCAT(DISTINCT otunnus SEPARATOR ', ') AS 'tilaukset'
              FROM kerayserat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND nro     = '{$id}'";
    $testresult = pupe_query($query);
    $testrow = mysql_fetch_assoc($testresult);

    $tilausnumeroita = $testrow['tilaukset'];
  }
  else {
    $query = "SELECT kerayslista
              from lasku
              where yhtio = '$kukarow[yhtio]'
              and tunnus  = '$id'";
    $testresult = pupe_query($query);
    $testrow = mysql_fetch_assoc($testresult);

    if ($testrow['kerayslista'] > 0) {
      //haetaan kaikki tälle klöntille kuuluvat otsikot
      $query = "SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
                FROM lasku
                WHERE yhtio      = '$kukarow[yhtio]'
                and kerayslista  = '$id'
                and kerayslista != 0
                and tila         in ($tila)
                $tilaustyyppi
                HAVING tunnukset is not null";
      $toimresult = pupe_query($query);

      //jos rivejä löytyy niin tiedetään, että tämä on keräysklöntti
      if (mysql_num_rows($toimresult) > 0) {
        $toimrow = mysql_fetch_assoc($toimresult);
        $tilausnumeroita = $toimrow["tunnukset"];
      }
      else {
        $tilausnumeroita = $id;
      }
    }
    else {
      $tilausnumeroita = $id;
    }
  }

  // katotaan aluks onko yhtään tuotetta sarjanumeroseurannassa tällä keräyslistalla
  $query = "SELECT tilausrivi.tunnus, tilausrivi.tuoteno, tilausrivi.varattu, tuote.sarjanumeroseuranta
            FROM tilausrivi use index (yhtio_otunnus)
            JOIN tuote on tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.sarjanumeroseuranta!=''
            WHERE tilausrivi.yhtio='$kukarow[yhtio]' and
            tilausrivi.otunnus in ($tilausnumeroita) and
            tilausrivi.tyyppi  in ('L','G')
            and tilausrivi.var not in ('P','J','O','S')";
  $toimresult = pupe_query($query);

  if (mysql_num_rows($toimresult) > 0) {

    while ($toimrow = mysql_fetch_assoc($toimresult)) {

      if ($toim == 'SIIRTOTYOMAARAYS' or $toim == 'SIIRTOLISTA') {
        $tunken = "siirtorivitunnus";
      }
      elseif ($toimrow["varattu"] < 0) {
        $tunken = "ostorivitunnus";
      }
      else {
        $tunken = "myyntirivitunnus";
      }

      if ($toimrow["sarjanumeroseuranta"] == "S" or $toimrow["sarjanumeroseuranta"] == "T" or $toimrow["sarjanumeroseuranta"] == "V") {
        $query = "SELECT count(distinct sarjanumero) kpl, min(sarjanumero) sarjanumero
                  FROM sarjanumeroseuranta
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tuoteno = '$toimrow[tuoteno]'
                  and $tunken = '$toimrow[tunnus]'";
        $sarjares2 = pupe_query($query);
        $sarjarow = mysql_fetch_assoc($sarjares2);

        if ($sarjarow["kpl"] != abs($toimrow["varattu"])) {
          echo "<font class='error'>".t("Sarjanumeroseurannassa oleville tuotteille on liitettävä sarjanumero ennen keräystä")."! ".t("Tuote").": $toimrow[tuoteno].</font><br>";
          $keraysvirhe++;
        }
      }
      else {

        //Siivotaan hieman käyttäjän syöttämää kappalemäärää
        $eratsekkpl = (float) str_replace( ",", ".", $maara[$toimrow["tunnus"]]);

        // Muutetaanko eräseurattavan tuotteen kappalemäärää
        if (trim($maara[$toimrow["tunnus"]]) != '' and $eratsekkpl >= 0) {
          if ($eratsekkpl < $toimrow["varattu"]) {
            //Jos erä on keksitty käsin täältä keräyksestä
            $query = "SELECT *
                      FROM sarjanumeroseuranta
                      WHERE yhtio          = '$kukarow[yhtio]'
                      and tuoteno          = '$toimrow[tuoteno]'
                      and myyntirivitunnus = '$toimrow[tunnus]'
                      and ostorivitunnus   = '$toimrow[tunnus]'";
            $lisa_res = pupe_query($query);

            if (mysql_num_rows($lisa_res) == 1) {
              $lisa_row = mysql_fetch_assoc($lisa_res);

              $query = "UPDATE sarjanumeroseuranta
                        SET era_kpl  = '$eratsekkpl'
                        WHERE yhtio          = '$kukarow[yhtio]'
                        and tuoteno          = '$toimrow[tuoteno]'
                        and ostorivitunnus   = '$lisa_row[myyntirivitunnus]'
                        and myyntirivitunnus = 0";
              $lisa_res = pupe_query($query);
            }

            $keraysvirhe++;
          }
          elseif ($eratsekkpl < $toimrow["varattu"]) {
            $query = "DELETE FROM sarjanumeroseuranta
                      WHERE yhtio          = '$kukarow[yhtio]'
                      and tuoteno          = '$toimrow[tuoteno]'
                      and myyntirivitunnus = '$toimrow[tunnus]'";
            $sarjares2 = pupe_query($query);
            $keraysvirhe++;
          }
        }
        else {
          $eratsekkpl = $toimrow["varattu"];
        }

        // Päivitetään erä
        if ($era_new_paikka[$toimrow["tunnus"]] != $era_old_paikka[$toimrow["tunnus"]]) {

          list($myy_hyllyalue, $myy_hyllynro, $myy_hyllyvali, $myy_hyllytaso, $myy_era) = explode("#", $era_new_paikka[$toimrow["tunnus"]]);

          $query = "DELETE FROM sarjanumeroseuranta
                    WHERE yhtio          = '$kukarow[yhtio]'
                    and tuoteno          = '$toimrow[tuoteno]'
                    and myyntirivitunnus = '$toimrow[tunnus]'";
          $sarjares2 = pupe_query($query);

          if ($era_new_paikka[$toimrow["tunnus"]] != "") {

            $oslisa = "";

            if ($toimrow["varattu"] > 0) {
              $query = "SELECT *
                        FROM sarjanumeroseuranta
                        WHERE yhtio          = '$kukarow[yhtio]'
                        and tuoteno          = '$toimrow[tuoteno]'
                        and hyllyalue        = '$myy_hyllyalue'
                        and hyllynro         = '$myy_hyllynro'
                        and hyllytaso        = '$myy_hyllytaso'
                        and hyllyvali        = '$myy_hyllyvali'
                        and sarjanumero      = '$myy_era'
                        and myyntirivitunnus = 0
                        and ostorivitunnus   > 0
                        LIMIT 1";
              $lisa_res = pupe_query($query);

              if (mysql_num_rows($lisa_res) > 0) {
                $lisa_row = mysql_fetch_assoc($lisa_res);
                $oslisa = " ostorivitunnus ='$lisa_row[ostorivitunnus]', ";
              }
              else {
                $oslisa = " ostorivitunnus ='', ";
              }
            }

            $query = "INSERT into sarjanumeroseuranta
                      SET yhtio     = '$kukarow[yhtio]',
                      tuoteno       = '$toimrow[tuoteno]',
                      lisatieto     = '$lisa_row[lisatieto]',
                      $tunken     = '$toimrow[tunnus]',
                      $oslisa
                      kaytetty      = '$lisa_row[kaytetty]',
                      era_kpl       = '',
                      laatija       = '$kukarow[kuka]',
                      luontiaika    = now(),
                      takuu_alku    = '$lisa_row[takuu_alku]',
                      takuu_loppu   = '$lisa_row[takuu_loppu]',
                      parasta_ennen = '$lisa_row[parasta_ennen]',
                      hyllyalue     = '$myy_hyllyalue',
                      hyllynro      = '$myy_hyllynro',
                      hyllytaso     = '$myy_hyllytaso',
                      hyllyvali     = '$myy_hyllyvali',
                      sarjanumero   = '$myy_era'";
            $lisa_res = pupe_query($query);

            $query = "UPDATE tilausrivi
                      SET hyllyalue   = '$myy_hyllyalue',
                      hyllynro    = '$myy_hyllynro',
                      hyllytaso   = '$myy_hyllytaso',
                      hyllyvali   = '$myy_hyllyvali'
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$toimrow[tunnus]'";
            $lisa_res = pupe_query($query);
          }
        }

        if ($eratsekkpl != 0) {
          $query = "SELECT count(*) kpl
                    FROM sarjanumeroseuranta
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tuoteno = '$toimrow[tuoteno]'
                    and $tunken = '$toimrow[tunnus]'";
          $sarjares2 = pupe_query($query);
          $sarjarow = mysql_fetch_assoc($sarjares2);

          if ($sarjarow["kpl"] != 1) {
            echo "<font class='error'>".t("Eränumeroseurannassa oleville tuotteille on liitettävä eränumero ennen keräystä")."! ".t("Tuote").": $toimrow[tuoteno].</font><br>";
            $keraysvirhe++;
          }
        }
      }
    }
  }

  // Tarkistetaan onko syötetty pakkauskirjaimet
  if ($yhtiorow['kerayserat'] == 'P' or $yhtiorow['kerayserat'] == 'A') {

    $ok_chk = true;

    // jos keräyserät on A, eli asiakkaan takan pitää olla keräyserät päällä, tarkistetaan se ensiksi
    if ($yhtiorow['kerayserat'] == 'A') {
      $query = "SELECT asiakas.kerayserat
                FROM lasku
                JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus AND asiakas.kerayserat = 'A')
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND lasku.tunnus  IN ({$tilausnumeroita})";
      $chk_res = pupe_query($query);

      if (mysql_num_rows($chk_res) == 0) $ok_chk = false;
    }

    if ($ok_chk) {
      for ($y=0; $y<count($kerivi); $y++) {
        $que0 = "SELECT tilausrivi.tunnus
                 FROM tilausrivi
                 JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
                 WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                 AND tilausrivi.tunnus  = '{$kerivi[$y]}'";
        $tark = pupe_query($que0);

        if (mysql_num_rows($tark) == 1 and trim($keraysera_pakkaus[$kerivi[$y]]) == '') $virherivi++;
      }

      if ($virherivi != 0) {
        echo "<font class='error'>", t("HUOM: Tuotteita ei viety hyllyyn. Syötä pakkauskirjain"), "!</font><br /><br />";
        $keraysvirhe++;

        $virherivi = 0;
      }
    }
  }

  // Tarkistetaan syötetyt varastopaikat
  if ($toim == 'VASTAANOTA_REKLAMAATIO') {
    for ($a=0; $a < count($kerivi); $a++) {
      // varastorekla on dropdown ja vertaushylly on kannasta
      if ((trim($varastorekla[$kerivi[$a]]) == trim($vertaus_hylly[$kerivi[$a]])) and $reklahyllyalue[$kerivi[$a]] != '' and $reklahyllynro[$kerivi[$a]] != '') {
        if (kuuluukovarastoon($reklahyllyalue[$kerivi[$a]], $reklahyllynro[$kerivi[$a]], '') == 0) {
          echo "<font class='error'>".t("VIRHE: Tuotenumerolle")." ".$rivin_puhdas_tuoteno[$kerivi[$a]]." ".t("annettu paikka")." ".$reklahyllyalue[$kerivi[$a]]."-".$reklahyllynro[$kerivi[$a]]."-".$reklahyllyvali[$kerivi[$a]]."-".$reklahyllytaso[$kerivi[$a]]." ".t("ei kuulu mihinkään varastoon")."!</font><br>";
          $virherivi++;
        }
      }

      if ((trim($varastorekla[$kerivi[$a]]) != trim($vertaus_hylly[$kerivi[$a]])) and $reklahyllyalue[$kerivi[$a]] != '') {
        echo "<font class='error'>".t("VIRHE: Tuotenumerolle")." ".$rivin_puhdas_tuoteno[$kerivi[$a]]." ".t("voi antaa vain yhden paikan per rivi")."</font><br>";
        $virherivi++;
      }
    }
  }

  if ($virherivi != 0 and $toim == 'VASTAANOTA_REKLAMAATIO') {
    echo "<font class='error'>". t("HUOM: Tuotteita ei viety hyllyyn. Korjaa virheet")."!</font><br><br>";
    $keraysvirhe++;
  }
}

if ($tee == 'P') {

  $tilausnumerot = array();
  $poikkeamat = array();
  $ookoot = array();

  if ((int) $keraajanro > 0) {
    $query = "SELECT *
              from kuka
              where yhtio    = '$kukarow[yhtio]'
              and keraajanro = '$keraajanro'";

  }
  else {
    $query = "SELECT *
              from kuka
              where yhtio = '$kukarow[yhtio]'
              and kuka    = '$keraajalist'";
  }

  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("VIRHE: Kerääjää %s ei löydy", "", $keraajanro)."!</font><br><br>";
    $keraysvirhe++;
  }
  else {
    $keraaja   = mysql_fetch_assoc($result);
    $who     = $keraaja['kuka'];
    $keraamaton = 0;

    $query0 = "SELECT kerayserat.pakkaus, kerayserat.pakkausnro, kerayserat.sscc, kerayserat.sscc_ulkoinen, kerayserat.tunnus
               FROM kerayserat
               WHERE kerayserat.yhtio = '$kukarow[yhtio]'
               AND kerayserat.nro     = '{$id}'
               GROUP BY 1,2
               ORDER BY kerayserat.pakkausnro";
    $pnresult = pupe_query($query0);

    while ($prow = mysql_fetch_assoc($pnresult)) {
      $pakkaus = array('pakkausnro' => $prow['pakkausnro'], 'sscc' => $prow['sscc'], 'sscc_ulkoinen' => $prow['sscc_ulkoinen'], 'pakkaus' => $prow['pakkaus'], 'tunnus' => $prow['tunnus']);
      $pakkaukset[] = $pakkaus;
    }

    for ($i=0; $i < count($kerivi); $i++) {

      $query1 = "SELECT if (kerattyaika='0000-00-00 00:00:00', 'keraamaton', 'keratty') status
                 FROM tilausrivi
                 WHERE tunnus = '$kerivi[$i]'
                 AND yhtio    = '$kukarow[yhtio]'";
      $ktresult = pupe_query($query1);
      $statusrow = mysql_fetch_assoc($ktresult);

      if ($statusrow["status"] == "keraamaton") {
        if ($kerivi[$i] > 0) {

          $apui = $kerivi[$i];

          //Kysessä voi olla keräysklöntti, haetaan muuttuneen rivin otunnus
          $query1 = "SELECT otunnus
                     FROM tilausrivi
                     WHERE tunnus = '$apui'
                     and yhtio    = '$kukarow[yhtio]'";
          $result  = pupe_query($query1);
          $otsikko = mysql_fetch_assoc($result);

          //Haetaan otsikon kaikki tiedot
          $query1 = "SELECT lasku.*,
                     laskun_lisatiedot.laskutus_nimi,
                     laskun_lisatiedot.laskutus_nimitark,
                     laskun_lisatiedot.laskutus_osoite,
                     laskun_lisatiedot.laskutus_postino,
                     laskun_lisatiedot.laskutus_postitp,
                     laskun_lisatiedot.laskutus_maa,
                     asiakas.kerayserat,
                     asiakas.kieli,
                     asiakas.kerayspoikkeama
                     FROM lasku
                     JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
                     LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                     WHERE lasku.tunnus = '$otsikko[otunnus]'
                     and lasku.yhtio    = '$kukarow[yhtio]'";
          $result = pupe_query($query1);
          $otsikkorivi = mysql_fetch_assoc($result);

          //Haetaan tilausrivin kaikki tiedot
          $query1 = "SELECT *
                     FROM tilausrivi
                     WHERE tunnus = '$apui'
                     and yhtio    = '$kukarow[yhtio]'";
          $result = pupe_query($query1);
          $tilrivirow = mysql_fetch_assoc($result);

          // Alkuperäinen perheid talteen, nollataan se myöhemmin, jos lapsia saa jättää ykis jt:ksi
          $rperheid  = $tilrivirow['perheid'];

          //Aloitellaan tilausrivi päivitysqueryä
          $query = "UPDATE tilausrivi
                    SET yhtio = yhtio ";

          if ($tilrivirow["var"] != "J" and $keraysvirhe == 0 and $real_submit == 'yes') {
            //Muut kuin JT-rivit päivitetään aina kerätyiksi jos virhetsekit menivät ok
            $query .= ", keratty = '$who',
                   kerattyaika = now()";
          }

          // Käyttäjä on syöttänyt jonkun luvun, päivitetään vaikka virhetsekit menisivät pepulleen
          if (trim($maara[$apui]) != '') {

            // Siivotaan hieman käyttäjän syöttämää kappalemäärää
            $maara[$apui] = str_replace(",", ".", $maara[$apui]);
            $maara[$apui] = (float) $maara[$apui];

            // Kerätään tietoa poikkeama-maileja varten
            $poikkeamat[$tilrivirow["otunnus"]][$i]["tuoteno"] = $tilrivirow["tuoteno"];
            $poikkeamat[$tilrivirow["otunnus"]][$i]["nimitys"] = $tilrivirow["nimitys"];
            $poikkeamat[$tilrivirow["otunnus"]][$i]["tilkpl"]  = $tilrivirow["tilkpl"];
            $poikkeamat[$tilrivirow["otunnus"]][$i]["var"]     = $tilrivirow["var"];
            $poikkeamat[$tilrivirow["otunnus"]][$i]["maara"]   = $maara[$apui];

            $rotunnus = 0;

            if ($tilrivirow["var"] == 'P' and $maara[$apui] > 0) {

              // Puuterivi löytyi poistetaan VAR
              $query .= " , var    = ''
                          , varattu  = '".$maara[$apui]."'";

              //Poistetaan 'tuote loppu'-kommentti jos tuotetta sittenkin löytyi
              $puurivires = t_avainsana("PUUTEKOMM");

              if (mysql_num_rows($puurivires) > 0) {
                $puurivirow = mysql_fetch_assoc($puurivires);

                $korvataan_pois = $puurivirow["selite"];
              }
              else {
                $korvataan_pois = t("Tuote Loppu.");
              }

              $query .= "  , kommentti  = replace(kommentti, '$korvataan_pois', '') ";

              // PUUTE-riville tehdään osatoimitus ja loput jätetään puuteriviksi
              if ($maara[$apui] < $tilrivirow['tilkpl']) {

                $poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Jätettiin puuteriviksi.";

                $rotunnus   = $tilrivirow['otunnus'];
                $rtyyppi    = $tilrivirow['tyyppi'];
                $rtilkpl    = round($tilrivirow['tilkpl']-$maara[$apui], 2);
                $rvarattu   = 0;
                $rjt        = 0;
                $rvar       = $tilrivirow['var'];
                $keratty    = "'$who'";
                $kerattyaik = "now()";
                $rkomm      = $tilrivirow["kommentti"];
              }
            }
            elseif ($tilrivirow["var"] == 'J' and $maara[$apui] > 0) {
              // JT-rivi löytyi, poistetaan VAR ja merkataan rivi kerätyksi, jos virhetsekit ok
              if ($keraysvirhe == 0) {
                $query .= ", keratty = '$who',
                             kerattyaika = now()";
              }

              $query .= " , var     = ''
                          , jt      = 0
                          , varattu = '".$maara[$apui]."'";

              if ($yhtiorow["varaako_jt_saldoa"] == "") {
                $jtsek = $tilrivirow['jt'];
              }
              else {
                $jtsek = $tilrivirow['jt']+$tilrivirow['varattu'];
              }

              // JT-riville tehdään osatoimitus ja loput jätetään jälkitoimitukseen
              if ($maara[$apui] < $jtsek) {

                $poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Jätettiin JT-riviksi.";

                $rotunnus   = $tilrivirow['otunnus'];
                $rtyyppi    = $tilrivirow['tyyppi'];
                $rtilkpl    = round($jtsek-$maara[$apui], 2);

                if ($yhtiorow["varaako_jt_saldoa"] == "") {
                  $rvarattu = 0;
                  $rjt      = round($jtsek-$maara[$apui], 2);
                }
                else {
                  $rvarattu = round($jtsek-$maara[$apui], 2);
                  $rjt      = 0;
                }

                $rvar       = $tilrivirow['var'];
                $keratty    = "''";
                $kerattyaik = "''";
                $rkomm      = $tilrivirow["kommentti"];

                if ($yhtiorow["kerayserat"] == '' and $tilrivirow["perheid"] != 0) {
                  $rperheid = 0;
                }
              }
            }
            elseif (($tilrivirow["var"] == 'J' or $tilrivirow["var"] == 'P') and $maara[$apui] == 0 and $poikkeama_kasittely[$apui] == "MI") {

              $poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "JT/Puuterivi nollattiin.";

              // Varastomiehellä on nyt oikeus nollata myös JT-rivi jos hän saa lähetettyä $poikkeama_kasittely[$apui] == "MI"
              if ($keraysvirhe == 0) {
                $query .= ", keratty = '$who',
                       kerattyaika = now()";
              }

              $query .= "  , var      = ''
                    , jt      = 0
                    , varattu    = 0";
            }
            elseif ((!isset($poikkeama_kasittely[$apui]) or $poikkeama_kasittely[$apui] == "") and $maara[$apui] >= 0 and $maara[$apui] < $tilrivirow['varattu'] and ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS' or $otsikkorivi['clearing'] == 'JT-TILAUS')) {
              // Jos tämä on toimitettava ennakkotilaus tai jt-tilaus niin yritetään laittaa poikkeama jollekin sopivalle otsikolle
              // Tähän haaraan ei mennä jos poikkeamat ohjataan manuaalisesti

              $query .= ", varattu='".$maara[$apui]."'";

              if ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS') {

                $poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Siirrettiin takaisin ennakkotilaukselle.";

                $ejttila    = "((tila='E' and alatila='A') or (tila='D' and alatila='E'))";

                $rotunnus  = 0;
                $rtyyppi  = "E";
                $rtilkpl   = round($tilrivirow['varattu']-$maara[$apui], 2);
                $rvarattu  = round($tilrivirow['varattu']-$maara[$apui], 2);
                $rjt      = 0;
                $rvar    = "";
                $keratty  = "''";
                $kerattyaik  = "''";
                $rkomm     = $tilrivirow["kommentti"];
              }

              if ($otsikkorivi['clearing'] == 'JT-TILAUS') {

                $poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Siirrettiin takaisin JT-tilaukselle.";

                $ejttila    = "(lasku.tila != 'N' or lasku.alatila != '')";

                $rotunnus  = 0;
                $rtyyppi  = "L";
                $rtilkpl   = round($tilrivirow['varattu']-$maara[$apui], 2);

                if ($yhtiorow["varaako_jt_saldoa"] == "") {
                  $rvarattu  = 0;
                  $rjt      = round($tilrivirow['varattu']-$maara[$apui], 2);
                }
                else {
                  $rvarattu  = round($tilrivirow['varattu']-$maara[$apui], 2);
                  $rjt      = 0;
                }

                $rvar    = "J";
                $keratty  = "''";
                $kerattyaik  = "''";
                $rkomm     = $tilrivirow["kommentti"];

                if ($yhtiorow["kerayserat"] == '' and $tilrivirow["perheid"] != 0) {
                  $rperheid = 0;
                }
              }

              // Etsitään sopiva otsikko jolle rivi laitetaan
              // Samat ehdot kuin tee_jt_tilaus.inc:ssä rivillä ~180
              $query1 = "SELECT lasku.yhtio, lasku.tunnus, lasku.tila, lasku.alatila
                         FROM lasku
                         LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus)
                         WHERE $ejttila
                         and lasku.yhtio                         = '$otsikkorivi[yhtio]'
                         and lasku.ytunnus                       = '$otsikkorivi[ytunnus]'
                         and lasku.nimi                          = '$otsikkorivi[nimi]'
                         and lasku.nimitark                      = '$otsikkorivi[nimitark]'
                         and lasku.osoite                        = '$otsikkorivi[osoite]'
                         and lasku.postino                       = '$otsikkorivi[postino]'
                         and lasku.postitp                       = '$otsikkorivi[postitp]'
                         and lasku.toim_nimi                     = '$otsikkorivi[toim_nimi]'
                         and lasku.toim_nimitark                 = '$otsikkorivi[toim_nimitark]'
                         and lasku.toim_osoite                   = '$otsikkorivi[toim_osoite]'
                         and lasku.toim_postino                  = '$otsikkorivi[toim_postino]'
                         and lasku.toim_postitp                  = '$otsikkorivi[toim_postitp]'
                         and lasku.toimitustapa                  = '$otsikkorivi[toimitustapa]'
                         and lasku.maksuehto                     = '$otsikkorivi[maksuehto]'
                         and lasku.vienti                        = '$otsikkorivi[vienti]'
                         and lasku.alv                           = '$otsikkorivi[alv]'
                         and lasku.ketjutus                      = '$otsikkorivi[ketjutus]'
                         and lasku.kohdistettu                   = '$otsikkorivi[kohdistettu]'
                         and lasku.toimitusehto                  = '$otsikkorivi[toimitusehto]'
                         and lasku.valkoodi                      = '$otsikkorivi[valkoodi]'
                         and lasku.vienti_kurssi                 = '$otsikkorivi[vienti_kurssi]'
                         and lasku.erikoisale                    = '$otsikkorivi[erikoisale]'
                         and lasku.eilahetetta                   = '$otsikkorivi[suoraan_laskutukseen]'
                         and lasku.piiri                         = '$otsikkorivi[piiri]'
                         and lasku.kolmikantakauppa              = '{$otsikkorivi['kolmikantakauppa']}'
                         and laskun_lisatiedot.laskutus_nimi     = '$otsikkorivi[laskutus_nimi]'
                         and laskun_lisatiedot.laskutus_nimitark = '$otsikkorivi[laskutus_nimitark]'
                         and laskun_lisatiedot.laskutus_osoite   = '$otsikkorivi[laskutus_osoite]'
                         and laskun_lisatiedot.laskutus_postino  = '$otsikkorivi[laskutus_postino]'
                         and laskun_lisatiedot.laskutus_postitp  = '$otsikkorivi[laskutus_postitp]'
                         and laskun_lisatiedot.laskutus_maa      = '$otsikkorivi[laskutus_maa]'
                         ORDER BY tunnus desc
                         LIMIT 1";
              $stresult = pupe_query($query1);

              // Sopiva otsikko löytyi
              if (mysql_num_rows($stresult) > 0) {
                $strow = mysql_fetch_assoc($stresult);

                // Sopivin otsikko oli dellattu, elvytetään se!
                if ($otsikkorivi['clearing'] == 'ENNAKKOTILAUS' and $strow["tila"] == "D") {

                  // E, A - Ennakkotilaus lepäämässä
                  $ukysx  = "UPDATE lasku SET tila = 'E', alatila = 'A', comments = '' WHERE yhtio = '$strow[yhtio]' and tunnus = '$strow[tunnus]'";
                  $ukysxres  = pupe_query($ukysx);
                }
                elseif ($otsikkorivi['clearing'] == 'JT-TILAUS' and $strow["tila"] == "D") {

                  // N, T - Myyntitilaus odottaa JT-tuotteita
                  $ukysx  = "UPDATE lasku SET tila = 'N', alatila = 'T', comments = '' WHERE yhtio = '$strow[yhtio]' and tunnus = '$strow[tunnus]'";
                  $ukysxres  = pupe_query($ukysx);
                }

                $rotunnus = $strow["tunnus"];
              }
              else {
                // Laitetaan tälle otsikolle, voi mennä solmuun, mutta ei katoa ainakaan kokonaan
                $rotunnus = $tilrivirow['otunnus'];
              }

            }
            elseif ($tilrivirow["var"] != 'J' and $tilrivirow["var"] != 'P') {
              // Jos tämä on normaali rivi
              if ($maara[$apui] < 0) {
                // Jos kerääjä kuittaa alle nollan niin ei tehdä mitään
                $query .= ", varattu = varattu";

                $poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Määrä nollaa pienempi. Poikkeamaa ei hyväksytty.";
              }
              elseif ($maara[$apui] >= 0 and $maara[$apui] < $tilrivirow['varattu']) {

                $query .= ", varattu = '".$maara[$apui]."'";

                if (isset($poikkeama_kasittely[$apui]) and $poikkeama_kasittely[$apui] != "") {

                  if ($maara[$apui] == 0) {
                    // Mitätöidään nollarivi koska poikkeamalle kuitenkin tehdään jotain fiksua
                    $query .= ", tyyppi = 'D', kommentti=trim(concat(kommentti, ' Mitätöitiin koska keräyspoikkeamasta tehtiin: ".$poikkeama_kasittely[$apui]."'))";

                    //vapautetaan tämän tilausrivi sarjanumero(t)
                    $queryv = "SELECT otunnus
                               FROM tilausrivi
                               WHERE yhtio = '{$kukarow['yhtio']}'
                               AND tunnus  = '{$apui}'";
                    $vapaut = pupe_query($queryv);
                    $vapaurow = mysql_fetch_assoc($vapaut);

                    vapauta_sarjanumerot($toim, $vapaurow["otunnus"], "AND tilausrivi.tunnus = '{$apui}'");
                  }

                  $rotunnus  = $tilrivirow['otunnus'];
                  $rtyyppi  = $tilrivirow['tyyppi'];
                  $rtilkpl   = round($tilrivirow['varattu']-$maara[$apui], 2);
                  $rvarattu  = round($tilrivirow['varattu']-$maara[$apui], 2);
                  $rjt      = 0;
                  $rvar    = $tilrivirow['var'];
                  $keratty  = "''";
                  $kerattyaik  = "''";
                  $rkomm     = $tilrivirow['kommentti'];

                  if ($yhtiorow["kerayserat"] == '' and $tilrivirow["perheid"] != 0) {
                    $rperheid = 0;
                  }
                }
              }
              else {
                //Päivitetään vain määrä jos se on isompi kuin alkuperäinen varattumäärä
                $query .= ", varattu = '".$maara[$apui]."'";
              }
            }

            if (isset($poikkeama_kasittely[$apui]) and $poikkeama_kasittely[$apui] != "" and $rotunnus != 0) {
              // Käyttäjän valitsemia poikkeamakäsittelysääntöjä
              if ($poikkeama_kasittely[$apui] == "PU") {

                $poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Jätettiin puuteriviksi.";

                // Riville tehdään osatoimitus ja loput jätetään puuteriviksi
                $rvarattu  = 0;
                $rjt      = 0;
                $rvar    = "P";
                $keratty  = "'$who'";
                $kerattyaik  = "now()";
                $puurivires = t_avainsana("PUUTEKOMM");

                if (mysql_num_rows($puurivires) > 0) {
                  $puurivirow = mysql_fetch_assoc($puurivires);

                  // Tilausrivin systeemikommentti
                  $rkomm = $puurivirow["selite"];
                }
                else {
                  // Tilausrivin systeemikommentti
                  $rkomm = t("Tuote Loppu.", $otsikkorivi["kieli"]);
                }
              }
              elseif ($poikkeama_kasittely[$apui] == "JT") {

                $poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Jätettiin JT-riviksi.";

                // Riville tehdään osatoimitus ja loput jätetään jälkkäriin
                if ($yhtiorow["varaako_jt_saldoa"] == "") {
                  $rvarattu  = 0;
                  $rjt      = $rtilkpl;
                }
                else {
                  $rvarattu  = $rtilkpl;
                  $rjt      = 0;
                }

                $rvar    = "J";
                $keratty  = "''";
                $kerattyaik  = "''";
                $rkomm     = $tilrivirow["kommentti"];
                if ($yhtiorow["kerayserat"] == '' and $tilrivirow["perheid"] != 0) {
                  $rperheid = 0;
                }
              }
              elseif ($poikkeama_kasittely[$apui] == "MI") {

                $poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Mitätöitiin.";

                // Riville tehdään osatoimitus ja loput mitätöidään
                $rotunnus  = 0;
              }
              elseif ($poikkeama_kasittely[$apui] == "UR") {

                $poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Siirrettiin uudelle riville.";

                // Riville tehdään osatoimitus ja loput kopsataan uudelle riville
                $rvarattu  = $rtilkpl;
                $rjt      = 0;
                $rvar    = "";
                $keratty  = "''";
                $kerattyaik  = "''";
                $rkomm     = $tilrivirow["kommentti"];
                if ($yhtiorow["kerayserat"] == '' and $tilrivirow["perheid"] != 0) {
                  $rperheid = 0;
                }
              }
              elseif ($poikkeama_kasittely[$apui] == "UT") {
                // Riville tehdään osatoimitus ja loput siirretään ihan uudelle tilaukselle
                if (!isset($tilausnumerot[$tilrivirow["otunnus"]])) {

                  // Jotta saadaa lasku kopsattua kivasti jos se splittaantuu
                  $laspliq = "SELECT *
                              FROM lasku
                              WHERE yhtio = '$kukarow[yhtio]'
                              and tunnus  = '$tilrivirow[otunnus]'";
                  $laskusplitres = pupe_query($laspliq);
                  $laskusplitrow = mysql_fetch_assoc($laskusplitres);

                  if ($laskusplitrow["tunnusnippu"] == 0) {
                    // Laitetaan uusi tilaus osatoimitukseksi alkuperäiselle tilaukselle
                    $kysely  = "UPDATE lasku SET tunnusnippu=tunnus WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tilrivirow[otunnus]'";
                    $insres  = pupe_query($kysely);

                    $laskusplitrow["tunnusnippu"] = $tilrivirow["otunnus"];
                  }

                  $fields = "yhtio";
                  $values = "'$kukarow[yhtio]'";

                  // Ei monisteta tunnusta
                  for ($islpit=1; $islpit < mysql_num_fields($laskusplitres)-1; $islpit++) {

                    $fieldname = mysql_field_name($laskusplitres, $islpit);

                    $fields .= ", ".$fieldname;

                    switch ($fieldname) {
                    case 'vanhatunnus':
                      $values .= ", '$tilrivirow[otunnus]'";
                      break;
                    case 'laatija':
                      $values .= ", '$kukarow[kuka]'";
                      break;
                    case 'luontiaika':
                      $values .= ", now()";
                      break;
                    case 'alatila':
                      $values .= ", ''";
                      break;
                    case 'tila':
                      $values .= ", 'N'";
                      break;
                    case 'kate_korjattu':
                    case 'lahetetty_ulkoiseen_varastoon':
                      $values .= ", NULL";
                      break;
                    default:
                      $values .= ", '".$laskusplitrow[$fieldname]."'";
                    }
                  }

                  $kysely  = "INSERT INTO lasku ($fields) VALUES ($values)";
                  $insres  = pupe_query($kysely);
                  $tilausnumerot[$tilrivirow["otunnus"]] = mysql_insert_id($GLOBALS["masterlink"]);

                  $kysely2 = "SELECT laskutus_nimi, laskutus_nimitark, laskutus_osoite, laskutus_postino, laskutus_postitp, laskutus_maa, laatija, luontiaika, otunnus
                              FROM laskun_lisatiedot
                              WHERE yhtio = '$kukarow[yhtio]'
                              AND otunnus = '$tilrivirow[otunnus]'";
                  $lisatiedot_result = pupe_query($kysely2);
                  $lisatiedot_row = mysql_fetch_assoc($lisatiedot_result);

                  $fields = "yhtio";
                  $values = "'$kukarow[yhtio]'";

                  // Ei monisteta tunnusta
                  for ($ijk = 0; $ijk < mysql_num_fields($lisatiedot_result); $ijk++) {

                    $fieldname = mysql_field_name($lisatiedot_result, $ijk);

                    $fields .= ", ".$fieldname;

                    switch ($fieldname) {
                    case 'otunnus':
                      $values .= ", '".$tilausnumerot[$tilrivirow["otunnus"]]."'";
                      break;
                    case 'laatija':
                      $values .= ", '$kukarow[kuka]'";
                      break;
                    case 'luontiaika':
                      $values .= ", now()";
                      break;
                    default:
                      $values .= ", '".$lisatiedot_row[$fieldname]."'";
                    }
                  }

                  $kysely2  = "INSERT INTO laskun_lisatiedot ($fields) VALUES ($values)";
                  $insres  = pupe_query($kysely2);
                }

                $poikkeamat[$tilrivirow["otunnus"]][$i]["loput"] = "Siirrettiin tilaukselle ".$tilausnumerot[$tilrivirow["otunnus"]].".";

                $rotunnus  = $tilausnumerot[$tilrivirow["otunnus"]];
                $rvarattu  = $rtilkpl;
                $rjt      = 0;
                $rvar    = "";
                $keratty  = "''";
                $kerattyaik  = "''";
                $rkomm     = $tilrivirow["kommentti"];

                if ($yhtiorow["kerayserat"] == '' and $tilrivirow["perheid"] != 0) {
                  $rperheid = 0;
                }
              }
            }

            // Tässä tehdään uusi rivi
            if ($rotunnus != 0) {

              // Aina jos rivi splitataan niin päivitetään alkuperäisen rivin tilkpl
              $query .= ", tilkpl = '".$maara[$apui]."'";

              $ale_query_insert_lisa = '';

              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                $ale_query_insert_lisa .= " ale{$alepostfix} = '".$tilrivirow["ale{$alepostfix}"]."', ";
              }

              $querys = "INSERT into tilausrivi set
                         hyllyalue       = '$tilrivirow[hyllyalue]',
                         hyllynro        = '$tilrivirow[hyllynro]',
                         hyllytaso       = '$tilrivirow[hyllyvali]',
                         hyllyvali       = '$tilrivirow[hyllytaso]',
                         tilaajanrivinro = '$tilrivirow[tilaajanrivinro]',
                         laatija         = '$kukarow[kuka]',
                         laadittu        = now(),
                         yhtio           = '$kukarow[yhtio]',
                         tuoteno         = '$tilrivirow[tuoteno]',
                         varattu         = '$rvarattu',
                         yksikko         = '$tilrivirow[yksikko]',
                         kpl             = '0',
                         tilkpl          = '$rtilkpl',
                         jt              = '$rjt',
                         {$ale_query_insert_lisa}
                         erikoisale      = '{$tilrivirow['erikoisale']}',
                         alv             = '$tilrivirow[alv]',
                         netto           = '$tilrivirow[netto]',
                         hinta           = '$tilrivirow[hinta]',
                         kerayspvm       = '$tilrivirow[kerayspvm]',
                         otunnus         = '$rotunnus',
                         tyyppi          = '$rtyyppi',
                         toimaika        = '$tilrivirow[toimaika]',
                         kommentti       = '$rkomm',
                         var             = '$rvar',
                         try             = '$tilrivirow[try]',
                         osasto          = '$tilrivirow[osasto]',
                         perheid         = '$rperheid',
                         perheid2        = '$tilrivirow[perheid2]',
                         nimitys         = '$tilrivirow[nimitys]',
                         jaksotettu      = '$tilrivirow[jaksotettu]'";
              $riviresult = pupe_query($querys);
              $lisatty_tun = mysql_insert_id($GLOBALS["masterlink"]);

              //Kopioidaan tilausrivin lisatiedot
              $querys = "SELECT *
                         FROM tilausrivin_lisatiedot
                         WHERE tilausrivitunnus='$tilrivirow[tunnus]'
                         and yhtio ='$kukarow[yhtio]'";
              $monistares2 = pupe_query($querys);

              if (mysql_num_rows($monistares2) > 0) {
                $monistarow2 = mysql_fetch_assoc($monistares2);

                $querys = "INSERT INTO tilausrivin_lisatiedot
                           SET yhtio        = '$kukarow[yhtio]',
                           positio                = '$monistarow2[positio]',
                           tilausrivilinkki       = '$monistarow2[tilausrivilinkki]',
                           toimittajan_tunnus     = '$monistarow2[toimittajan_tunnus]',
                           ei_nayteta             = '$monistarow2[ei_nayteta]',
                           tilausrivitunnus       = '$lisatty_tun',
                           erikoistoimitus_myynti = '$monistarow2[erikoistoimitus_myynti]',
                           vanha_otunnus          = '$monistarow2[vanha_otunnus]',
                           jarjestys              = '$monistarow2[jarjestys]',
                           luontiaika             = now(),
                           laatija                = '$kukarow[kuka]'";
                $riviresult = pupe_query($querys);
              }

              $queryera = "SELECT sarjanumeroseuranta FROM tuote WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tilrivirow[tuoteno]'";
              $sarjares2 = pupe_query($queryera);
              $erarow = mysql_fetch_assoc($sarjares2);

              if ($erarow['sarjanumeroseuranta'] == 'E' or $erarow['sarjanumeroseuranta'] == 'F') {
                echo "<font class='error'>".t("Eränumeroseurannassa oleville tuotteille on liitettävä eränumero ennen keräystä")."! ".t("Tuote").": $tilrivirow[tuoteno].</font><br>";
              }
            }

            // Päivitetään tuoteperheiden saldottomat jäsenet oikeisiin määriin (ne voi olla alkuperäiselläkin lähetteellä == vanhatunnus)
            if ($tilrivirow["perheid"] != 0 and trim($maara[$apui]) != ''
              and ($tilrivirow["var"] != 'J' and $tilrivirow["var"] != 'P'
                or ($tilrivirow["var"] == 'P' and $maara[$apui] > 0)
                or ($tilrivirow["var"] == 'J' and $maara[$apui] > 0)
                or (($tilrivirow["var"] == 'J' or $tilrivirow["var"] == 'P') and $maara[$apui] == 0 and $poikkeama_kasittely[$apui] == "MI"))
            ) {
              $query1 = "SELECT tilausrivi.tunnus, tilausrivi.tuoteno
                         FROM tilausrivi
                         JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.ei_saldoa != '')
                         WHERE tilausrivi.otunnus  in ('$tilrivirow[otunnus]', '$otsikkorivi[vanhatunnus]')
                         and tilausrivi.tunnus    != '$apui'
                         and tilausrivi.perheid    = '$tilrivirow[perheid]'
                         and tilausrivi.yhtio      = '$kukarow[yhtio]'";
              $result = pupe_query($query1);

              while ($tilrivirow2 = mysql_fetch_assoc($result)) {
                $query2 = "SELECT kerroin
                           FROM tuoteperhe
                           WHERE yhtio    = '$kukarow[yhtio]'
                           AND isatuoteno = '$tilrivirow[tuoteno]'
                           AND tuoteno    = '$tilrivirow2[tuoteno]'";
                $result2 = pupe_query($query2);

                // oltiin muokkaamassa isätuotteen kappalemäärää, päivitetään saldottomien lasten määrät kertoimella
                if (mysql_num_rows($result2) == 1) {
                  $kerroinrow = mysql_fetch_assoc($result2);
                  if ($kerroinrow["kerroin"] == 0) $kerroinrow["kerroin"] = 1;
                  $tilrivimaara = round($maara[$apui] * $kerroinrow["kerroin"], 2);

                  $query1 = "UPDATE tilausrivi
                             SET varattu = '$tilrivimaara'
                             WHERE tunnus = '$tilrivirow2[tunnus]'
                             AND yhtio    = '$kukarow[yhtio]'";
                  $result1 = pupe_query($query1);
                }
              }
            }

            $muuttuiko = 'kylsemuuttu';
          }
          else {
            // kerätään ok tilaukset talteen,
            // ja tarkistetaan lopuksi tuliko puutteita
            if ($otsikkorivi['kerayspoikkeama'] == 3) {
              $ookoot[$tilrivirow["otunnus"]]["otunnus"] = $tilrivirow["otunnus"];
            }
          }

          if ($keraysvirhe == 0 and ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $otsikkorivi['kerayserat'] == 'A'))) {

            $kerattylisa = (trim($maara[$apui]) == '' or $maara[$apui] < 0) ? ", kpl_keratty = kpl" : ", kpl_keratty = '{$maara[$apui]}'";

            $pakkauskirjain = (int) abs(ord($keraysera_pakkaus[$kerivi[$i]]) - 64);
            $monesko = -1;

            //varmistetaan, että haetaan pakkauskirjaimen mukaiset tiedot, jos kyseessä on uusi pakkaus niin se käsitellään seuraavassa ($monesko = -1)
            //tämä siksi, koska pakkauskirjaimien järjestystä on voitu muuttaa
            for ($x = 0; $x < count($pakkaukset); $x++) {
              if ($pakkaukset[$x]['pakkausnro'] == $pakkauskirjain) {
                $monesko = $x;
                break;

              }
            }

            //tehhään uuelle pakkaukselle sscc:t jos ollaan koetettu lisää niit ja tälle pakkauskirjaimelle ei ole vielä tehty sscc:tä
            //pakkausten tiedot on järjestetty pakkaukset muuttujaan siten, että paikalla 0 = A numerona 1, paikalla 1 = B numerona 2 jne.
            //jos ollaan jo tehty jo sscc tälle pakkauskirjaimelle niin ei tehdä sille uusia sscc:tä vaan setataan vain $monesko muuttuja oikeaks et saadaan haettua oikean pakkauksen tiedot
            if ($monesko == -1) {
              $monesko = $pakkauskirjain - 1;

              if (!isset($pakkaukset[$monesko]['sscc'])) {
                $pakkaukset[$monesko]['sscc'] = uusi_sscc_nro();

                if (!empty($yhtiorow['ean'])) {
                  $_selitetark = t_avainsana("GS1_SSCC", "", "and avainsana.selite = '{$otsikkorivi['toimitustapa']}'", "", "", "selitetark");

                  if ($_selitetark == '') {
                    $_selitetark = t_avainsana("GS1_SSCC", "", "and avainsana.selite = 'kaikki'", "", "", "selitetark");
                  }

                  if ($_selitetark != '') {
                    $expansioncode = $_selitetark;

                    $pakkaukset[$monesko]['sscc_ulkoinen'] = gs1_sscc($expansioncode, $pakkaukset[$monesko]['sscc'], $monesko);
                  }
                  else {
                    $pakkaukset[$monesko]['sscc_ulkoinen'] = $pakkaukset[$monesko]['sscc'];
                  }
                }
                else {
                  $pakkaukset[$monesko]['sscc_ulkoinen'] = $pakkaukset[$monesko]['sscc'];
                }
              }
            }

            $query_ins = "UPDATE kerayserat SET
                          pakkausnro     = '{$pakkauskirjain}',
                          sscc           = '{$pakkaukset[$monesko]['sscc']}',
                          sscc_ulkoinen  = '{$pakkaukset[$monesko]['sscc_ulkoinen']}'
                          {$kerattylisa}
                          WHERE yhtio    = '{$kukarow['yhtio']}'
                          AND tilausrivi = '{$kerivi[$i]}'";
            $keraysera_ins_res = pupe_query($query_ins);
          }

          if (($toim == 'VASTAANOTA_REKLAMAATIO' or $yhtiorow["kerayspoikkeama_kasittely"] == 'P') and $keraysvirhe == 0) {

            if (trim($varastorekla[$apui]) != '' and trim($vertaus_hylly[$apui]) != trim($varastorekla[$apui])) {

              // Ollaan valittu varastopaikka dropdownista
              if (isset($varastorekla[$apui]) and $varastorekla[$apui] != 'x' and $varastorekla[$apui] != '' and strpos($varastorekla[$apui], "###") === false) {
                // tehdään uusi paikka jos valittiin paikaton lapsivarasto
                if (substr($varastorekla[$apui], 0, 1) == 'V') {
                  $uusi_paikka = lisaa_tuotepaikka($tilrivirow["tuoteno"], '', '', '', '', '', '', 0, 0, substr($varastorekla[$apui], 1));
                  $ptunnus = $uusi_paikka['tuotepaikan_tunnus'];
                }
                else {
                  $ptunnus = $varastorekla[$apui];
                }

                $query_xxx = "SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso
                              FROM tuotepaikat
                              WHERE yhtio = '{$kukarow['yhtio']}'
                              and tunnus  = '{$ptunnus}'
                              and tuoteno = '{$tilrivirow['tuoteno']}'";
                $_result_paikka = pupe_query($query_xxx);
                $_row_paikka = mysql_fetch_assoc($_result_paikka);

                $reklahyllyalue = $_row_paikka['hyllyalue'];
                $reklahyllynro  = $_row_paikka['hyllynro'];
                $reklahyllyvali = $_row_paikka['hyllyvali'];
                $reklahyllytaso = $_row_paikka['hyllytaso'];
              }
              else {
                list($reklahyllyalue, $reklahyllynro, $reklahyllyvali, $reklahyllytaso) = explode("###", $varastorekla[$apui]);
              }
            }
            elseif (trim($vertaus_hylly[$apui]) == trim($varastorekla[$apui]) and $reklahyllyalue[$apui] != '') {
              // Ollaan syötetty varastopaikka käsin
              $reklahyllyalue = $reklahyllyalue[$apui];
              $reklahyllynro  = $reklahyllynro[$apui];
              $reklahyllyvali = $reklahyllyvali[$apui];
              $reklahyllytaso = $reklahyllytaso[$apui];
            }
            else {
              // Otetaan tuotteen oletuspaikka
              list($reklahyllyalue, $reklahyllynro, $reklahyllyvali, $reklahyllytaso) = explode("###", $vertaus_hylly[$apui]);
            }

            // Lisätään paikat tilausriville
            $query .= ", hyllyalue = '$reklahyllyalue', hyllynro = '$reklahyllynro', hyllyvali = '$reklahyllyvali', hyllytaso = '$reklahyllytaso'";
          }

          //päivitetään alkuperäinen rivi
          $query .= " WHERE tunnus='$apui' and yhtio='$kukarow[yhtio]'";
          $result = pupe_query($query);

          // jos keräyserät on käytössä, päivitetään kerätyt kappalemäärät keräyserään
          if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
            $query_ker = "SELECT tunnus
                          FROM kerayserat
                          WHERE yhtio    = '{$kukarow['yhtio']}'
                          AND nro        = '$id'
                          AND tilausrivi = '{$apui}'";
            $keraysera_chk_res = pupe_query($query_ker);

            while ($keraysera_chk_row = mysql_fetch_assoc($keraysera_chk_res)) {
              if (!is_numeric(trim($keraysera_maara[$keraysera_chk_row['tunnus']]))) {
                $keraysera_maara[$keraysera_chk_row['tunnus']] = 0;
              }

              // Päivitetään kerääjä ja aika vain jos niitä ei olla jo aikaisemmin laitettu (optiscan tai kardex...)
              $query_upd = "UPDATE kerayserat
                            SET kpl_keratty = '{$keraysera_maara[$keraysera_chk_row['tunnus']]}',
                            keratty     = if(keratty = '', '{$kukarow['kuka']}', keratty),
                            kerattyaika = if(kerattyaika = '0000-00-00 00:00:00', now(), kerattyaika)
                            WHERE yhtio = '{$kukarow['yhtio']}'
                            AND tunnus  = '{$keraysera_chk_row['tunnus']}'";
              $keraysera_update_res = pupe_query($query_upd);
            }

            if ($tilrivirow["perheid"] != 0 and trim($maara[$apui]) != ''
              and ($tilrivirow["var"] != 'J' and $tilrivirow["var"] != 'P'
                or ($tilrivirow["var"] == 'P' and $maara[$apui] > 0)
                or ($tilrivirow["var"] == 'J' and $maara[$apui] > 0)
                or (($tilrivirow["var"] == 'J' or $tilrivirow["var"] == 'P') and $maara[$apui] == 0 and $poikkeama_kasittely[$apui] == "MI"))
            ) {
              // haetaan lapset joilla on ohita_kerays täpätty ja tehdään poikkeama myös niille
              $query_lapset = "SELECT tilausrivi.tunnus, tilausrivi.varattu
                               FROM tilausrivi
                               JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus and tilausrivin_lisatiedot.ohita_kerays != '')
                               WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
                               AND tilausrivi.otunnus  = '{$tilrivirow['otunnus']}'
                               AND tilausrivi.perheid  = '{$tilrivirow['perheid']}'
                               AND tilausrivi.tunnus  != '{$apui}'";
              $lapset_chk_res = pupe_query($query_lapset);

              while ($lapset_chk_row = mysql_fetch_assoc($lapset_chk_res)) {

                if (round($lapset_chk_row["varattu"], 2) != round($maara[$apui] * ($lapset_chk_row["varattu"]/$tilrivirow["varattu"]), 2)) {
                  $query_upd = "UPDATE tilausrivi
                                SET varattu = round({$maara[$apui]} * ({$lapset_chk_row["varattu"]}/{$tilrivirow["varattu"]}), 2)
                                WHERE yhtio = '{$kukarow['yhtio']}'
                                AND tunnus  = '{$lapset_chk_row['tunnus']}'";
                  $keraysera_update_res = pupe_query($query_upd);
                }
              }
            }
          }

          // Pitää lisätä päivityksen yhteydessä myös tuotepaikka...
          if ($toim == 'VASTAANOTA_REKLAMAATIO' and $keraysvirhe == 0) {

            $select = "SELECT *
                       FROM tuotepaikat
                       WHERE yhtio   = '$kukarow[yhtio]'
                       AND hyllyalue = '$reklahyllyalue'
                       AND hyllynro  = '$reklahyllynro'
                       AND hyllyvali = '$reklahyllyvali'
                       AND hyllytaso = '$reklahyllytaso'
                       AND tuoteno   = '{$rivin_puhdas_tuoteno[$apui]}'";
            $hakures = pupe_query($select);
            $sresults = mysql_fetch_assoc($hakures);

            if (mysql_num_rows($hakures) == 0) {
              lisaa_tuotepaikka($rivin_puhdas_tuoteno[$apui], $reklahyllyalue, $reklahyllynro, $reklahyllyvali, $reklahyllytaso, "Reklamaation vastaanotossa", "", 0, 0, 0);
            }
          }
        }

        //Keräämätön rivi
        $keraamaton++;
      }
      else {
        echo t("HUOM: Tämä rivi oli jo kerätty! Ei voida kerätä uudestaan.")."<br>";
      }
    }
  }

  if ($keraysvirhe > 0) {
    $tee = '';
  }

  // Jos ei ole puutteita ja halutaan silti
  // lähettää keräyspoikkeama-sähköposti
  if (!empty($ookoot)) {
    foreach ($ookoot as $tilaus) {

      // tsekataan että kyseiselle tilaukselle ei ole jo puuterivejä
      // sillä tilauksella voi olla molempia rivejä samanaikaisesti
      if (!array_key_exists($tilaus["otunnus"], $poikkeamat)) {
        $poikkeamat[$tilaus["otunnus"]] = $tilaus["otunnus"];
        $muuttuiko = 'kylsemuuttu';
      }
    }
  }

  // Jos keräyspoikkeamia syntyi, niin lähetetään mailit myyjälle ja asiakkaalle
  if ($muuttuiko == 'kylsemuuttu') {
    foreach ($poikkeamat as $poikkeamatilaus => $poikkeamatilausrivit) {

      $qry = "SELECT tila
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = $poikkeamatilaus";
      $res = pupe_query($qry);
      $ptilarow = mysql_fetch_assoc($res);
      $ptila = $ptilarow['tila'];

      // Siirtolistoilla käyttäjä pitää joinata hyvak1 kentällä (+ niissä ei ole asiakasta)
      if ($ptila == 'G') {
        $query = "SELECT lasku.*,
                  kuka.kieli AS kieli,
                  kuka.nimi AS kukanimi,
                  kuka.eposti AS kukamail
                  FROM lasku
                  LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio
                    AND kuka.kuka     = lasku.hyvak1
                    AND kuka.extranet = '')
                  WHERE lasku.tunnus  = '$poikkeamatilaus'
                  AND lasku.yhtio     = '$kukarow[yhtio]'";
      }
      else {
        $query = "SELECT lasku.*,
                  asiakas.email,
                  asiakas.kerayspoikkeama,
                  asiakas.keraysvahvistus_lahetys,
                  asiakas.kieli,
                  kuka.nimi AS kukanimi,
                  kuka.eposti AS kukamail,
                  kuka_ext.nimi AS kuka_ext_nimi
                  FROM lasku
                  JOIN asiakas ON (asiakas.yhtio = lasku.yhtio
                    AND asiakas.tunnus     = lasku.liitostunnus)
                  LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio
                    AND kuka.tunnus        = lasku.myyja
                    AND kuka.extranet      = '')
                  LEFT JOIN kuka AS kuka_ext ON (kuka_ext.yhtio = lasku.yhtio
                    AND kuka_ext.kuka      = lasku.laatija
                    AND kuka_ext.extranet != '')
                  WHERE lasku.tunnus       = '$poikkeamatilaus'
                  AND lasku.yhtio          = '$kukarow[yhtio]'";
      }

      $result = pupe_query($query);
      $laskurow = mysql_fetch_assoc($result);

      $toimtapaquery = "  SELECT osoitelappu
                          FROM toimitustapa
                          WHERE yhtio = '{$laskurow['yhtio']}'
                          AND selite = '{$laskurow['toimitustapa']}'";
      $toimtaparesult = pupe_query($toimtapaquery);
      $toimtaparow = mysql_fetch_assoc($toimtaparesult);

      $kieli = $laskurow["kieli"];

      $rivit = '';

      $_plain_text_mail = ($yhtiorow['kerayspoikkeama_email'] == 'P');

      // Jos ei ole poikkeamarivejä, niin infotaan siitä
      if (!is_array($poikkeamatilausrivit)) {

        if ($_plain_text_mail) {
          $rivit .= t("Ei keräyspoikkeamia", $kieli)."\r\n";
        }
        else {
          $rivit .= t("Ei keräyspoikkeamia", $kieli);
        }
        $poikkeamatilausrivit = array();
      }

      foreach ($poikkeamatilausrivit as $poikkeama) {

        $poikkeama['nimitys'] = t_tuotteen_avainsanat($poikkeama, 'nimitys', $kieli);

        $enariquery = " SELECT eankoodi
                        FROM tuote
                        WHERE yhtio = '{$laskurow['yhtio']}'
                        AND tuoteno = '{$poikkeama['tuoteno']}'";
        $enariresult = pupe_query($enariquery);
        $enarirow = mysql_fetch_assoc($enariresult);

        if ($_plain_text_mail) {
          $rivit .= t("Nimitys", $kieli).": {$poikkeama['nimitys']}\r\n";
          $rivit .= t("Tuotenumero", $kieli).": {$poikkeama['tuoteno']}\r\n";

          if ($toimtaparow['osoitelappu'] == 'osoitelappu_kesko') {
            $_puutemaara = $poikkeama["tilkpl"] - $poikkeama["maara"];
            $rivit .= t("Eankoodi", $kieli).": {$enarirow['eankoodi']}\r\n";
            $rivit .= t("Puutekappale", $kieli).": ".(float) $_puutemaara."\r\n";
          }
          else {
            $rivit .= t("Tilattu", $kieli).": ".(float) $poikkeama["tilkpl"]."\r\n";
            $rivit .= t("Toimitetaan", $kieli).": ".(float) $poikkeama["maara"]."\r\n";
          }

          if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
            $rivit .= t("Poikkeaman käsittely", $kieli).": {$poikkeama['loput']}\r\n";
          }

          $rivit .= "\r\n";
        }
        else {
          $rivit .= "<tr>";
          $rivit .= "<td>$poikkeama[nimitys]</td>";
          $rivit .= "<td>$poikkeama[tuoteno]</td>";

          if ($toimtaparow['osoitelappu'] == 'osoitelappu_kesko') {
            $_puutemaara = $poikkeama["tilkpl"] - $poikkeama["maara"];
            $rivit .= "<td>". $enarirow['eankoodi']."   </td>";
            $rivit .= "<td>". (float) $_puutemaara."</td>";
          }
          else {
            $rivit .= "<td>". (float) $poikkeama["tilkpl"]."   </td>";
            $rivit .= "<td>". (float) $poikkeama["maara"]."</td>";
          }

          if ($yhtiorow["kerayspoikkeama_kasittely"] != '') $rivit .= "<td>$poikkeama[loput]</td>";
          $rivit .= "</tr>";
        }
      }

      $header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";

      if ($_plain_text_mail) {
        $header .= "Content-type: text/plain; charset=\"iso-8859-1\"\r\n";

        $ulos = t("Keräyspoikkeamat", $kieli)."\r\n\r\n";

        $ulos .= t("Yhtiö", $kieli)."\r\n";
        $ulos .= "{$yhtiorow['nimi']}\r\n";
        $ulos .= "{$yhtiorow['osoite']}\r\n";
        $ulos .= "{$yhtiorow['postino']} {$yhtiorow['postitp']}";
        $ulos .= "\r\n\r\n";

        $ulos .= t("Ostaja", $kieli).":\r\n";
        $ulos .= "{$laskurow['nimi']}\r\n";
        $ulos .= "{$laskurow['nimitark']}\r\n";
        $ulos .= "{$laskurow['osoite']}\r\n";
        $ulos .= "{$laskurow['postino']} {$laskurow['postitp']}";
        $ulos .= "\r\n\r\n";

        $ulos .= t("Toimitusosoite", $kieli).":\r\n";
        $ulos .= "{$laskurow['toim_nimi']}\r\n";
        $ulos .= "{$laskurow['toim_nimitark']}\r\n";
        $ulos .= "{$laskurow['toim_osoite']}\r\n";
        $ulos .= "{$laskurow['toim_postino']} {$laskurow['toim_postitp']}";
        $ulos .= "\r\n\r\n";

        $ulos .= t("Laadittu", $kieli).": ".tv1dateconv($laskurow['luontiaika'])."\r\n";
        $ulos .= t("Tilausnumero", $kieli).": {$laskurow['tunnus']}\r\n";
        $ulos .= t("Tilausviite", $kieli).": {$laskurow['viesti']}\r\n";
        $ulos .= t("Toimitustapa", $kieli).": ";
        $ulos .= t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli);
        $ulos .= "\r\n";
        $ulos .= t("Myyjä", $kieli).": {$laskurow['kukanimi']}\r\n";

        if ($laskurow['comments'] != '') {
          $ulos .= t("Kommentti", $kieli).": {$laskurow['comments']}\r\n";
        }

        $ulos .= "\r\n";
        $ulos .= $rivit;

        $ulos .= "\r\n";
        $ulos .= t("Tämä on automaattinen viesti. Tähän sähköpostiin ei tarvitse vastata.", $kieli)."\r\n\r\n";
      }
      else {
        $header .= "Content-type: text/html; charset=\"iso-8859-1\"\n";

        if ($yhtiorow["kayttoliittyma"] == "U") {
          $css = $yhtiorow['css'];
        }
        else {
          $css = $yhtiorow['css_classic'];
        }

        $ulos  = "<html>\n<head>\n";
        $ulos .= "<style type='text/css'>$css</style>\n";

        $ulos .= "<title>$yhtiorow[nimi]</title>\n";
        $ulos .= "</head>\n";

        $ulos .= "<body>\n";

        $ulos .= "<font class='head'>".t("Keräyspoikkeamat", $kieli)."</font><hr><br><br><table>";

        $ulos .= "<tr><th>".t("Yhtiö", $kieli)."</th></tr>";
        $ulos .= "<tr><td>$yhtiorow[nimi]</td></tr>";
        $ulos .= "<tr><td>$yhtiorow[osoite]</td></tr>";
        $ulos .= "<tr><td>$yhtiorow[postino] $yhtiorow[postitp]</td></tr>";
        $ulos .= "</table><br><br>";

        $ulos .= "<table>";
        $ulos .= "<tr><th>".t("Ostaja", $kieli).":</th><th>".t("Toimitusosoite", $kieli).":</th></tr>";

        $ulos .= "<tr><td>$laskurow[nimi]</td><td>$laskurow[toim_nimi]</td></tr>";
        $ulos .= "<tr><td>$laskurow[nimitark]</td><td>$laskurow[toim_nimitark]</td></tr>";
        $ulos .= "<tr><td>$laskurow[osoite]</td><td>$laskurow[toim_osoite]</td></tr>";
        $ulos .= "<tr><td>$laskurow[postino] $laskurow[postitp]</td><td>$laskurow[toim_postino] $laskurow[toim_postitp]</td></tr>";
        $ulos .= "</table><br><br>";

        $ulos .= "<table>";
        $ulos .= "<tr><th>".t("Laadittu", $kieli).":</th><td>".tv1dateconv($laskurow['luontiaika'])."</td></tr>";
        $ulos .= "<tr><th>".t("Tilausnumero", $kieli).":</th><td>$laskurow[tunnus]</td></tr>";
        $ulos .= "<tr><th>".t("Tilausviite", $kieli).":</th><td>$laskurow[viesti]</td></tr>";
        $ulos .= "<tr><th>".t("Toimitustapa", $kieli).":</th><td>".t_tunnus_avainsanat($laskurow['toimitustapa'], "selite", "TOIMTAPAKV", $kieli)."</td></tr>";
        $ulos .= "<tr><th>".t("Myyjä", $kieli).":</th><td>$laskurow[kukanimi]</td></tr>";

        if ($laskurow['comments'] != '') {
          $ulos .= "<tr><th>".t("Kommentti", $kieli).":</th><td>".$laskurow['comments']."</td></tr>";
        }

        $ulos .= "</table><br><br>";

        $ulos .= "<table>";

        if ($toimtaparow['osoitelappu'] == 'osoitelappu_kesko' and !empty($poikkeamatilausrivit)) {
          $ulos .= "<tr><th>".t("Nimitys", $kieli)."</th><th>".t("Tuotenumero", $kieli)."</th><th>".t("Eankoodi", $kieli)."</th><th>".t("Puutekappale", $kieli)."</th>";
        }
        elseif (empty($poikkeamatilausrivit)) {
          $ulos .= "<tr>";
        }
        else {
          $ulos .= "<tr><th>".t("Nimitys", $kieli)."</th><th>".t("Tuotenumero", $kieli)."</th><th>".t("Tilattu", $kieli)."</th><th>".t("Toimitetaan", $kieli)."</th>";
        }

        if ($yhtiorow["kerayspoikkeama_kasittely"] != '' and !empty($poikkeamatilausrivit)) $ulos .= "<th>".t("Poikkeaman käsittely", $kieli)."</th>";
        $ulos .= "</tr>";
        $ulos .= $rivit;
        $ulos .= "</table><br><br>";

        $ulos .= t("Tämä on automaattinen viesti. Tähän sähköpostiin ei tarvitse vastata.", $kieli)."<br><br>";
        $ulos .= "</body></html>";
      }

      $sellahetetyyppi = (!isset($sellahetetyyppi)) ? $laskurow['lahetetyyppi'] : $sellahetetyyppi;
      $ei_puutteita = (strpos($sellahetetyyppi, "_eipuute") === FALSE);
      $kerpoik_myyjaasiakas = (in_array($laskurow["kerayspoikkeama"], array(0, 3)));

      // korvataan poikkeama-meili keräysvahvistuksella JOS lähetetään keräysvahvistus per toimitus
      // JA lähetetyyppi sisältää puuterivejä
      if (($laskurow["keraysvahvistus_lahetys"] == 'o' or ($yhtiorow["keraysvahvistus_lahetys"] == 'o' and $laskurow["keraysvahvistus_lahetys"] == '')) and $kerpoik_myyjaasiakas and $ei_puutteita) {
        $laskurow["kerayspoikkeama"] = 2;
      }

      $boob = "";
      $_subject_lisa = "";

      $_ctype = $_plain_text_mail ? "text" : "html";

      if ($toimtaparow['osoitelappu'] == 'osoitelappu_kesko') {
        $_subject_lisa = " ".$laskurow['asiakkaan_tilausnumero'];
      }

      // Lähetetään keräyspoikkeama asiakkaalle
      if ($laskurow["email"] != '' and $kerpoik_myyjaasiakas) {

        // Sähköpostin lähetykseen parametrit
        $parametri = array(
          "to"           => $laskurow["email"],
          "cc"           => "",
          "subject"      => "{$yhtiorow['nimi']} - ".t("Keräyspoikkeamat", $kieli)."{$_subject_lisa}",
          "ctype"        => $_ctype,
          "body"         => $ulos,
          "attachements" => "",
        );

        pupesoft_sahkoposti($parametri);
      }

      // Lähetetään keräyspoikkeama myyjälle
      if ($laskurow["kukamail"] != '' and ($kerpoik_myyjaasiakas or $laskurow["kerayspoikkeama"] == 2)) {

        $uloslisa = "";

        if (($laskurow["email"] == '' or $boob === FALSE) and $kerpoik_myyjaasiakas) {
          $uloslisa .= t("Asiakkaalta puuttuu sähköpostiosoite! Keräyspoikkeamia ei voitu lähettää!")."<br><br>";
        }
        elseif ($laskurow["kerayspoikkeama"] == 2) {
          $uloslisa .= t("Asiakkaalle on merkitty että hän ei halua keräyspoikkeama ilmoituksia!")."<br><br>";
        }
        else {
          $uloslisa .= t("Tämä viesti on lähetetty myös asiakkaalle")."!<br><br>";
        }

        $uloslisa .= t("Tilauksen keräsi").": $keraaja[nimi]<br><br>";

        $ulos = str_replace("</font><hr><br><br><table>", "</font><hr><br><br>$uloslisa<table>", $ulos);

        // Sähköpostin lähetykseen parametrit
        $parametri = array(
          "to"           => $laskurow["kukamail"],
          "cc"           => "",
          "subject"      => "{$yhtiorow['nimi']} - ".t("Keräyspoikkeamat", $kieli)."{$_subject_lisa}",
          "ctype"        => $_ctype,
          "body"         => $ulos,
          "attachements" => "",
        );

        pupesoft_sahkoposti($parametri);
      }

      if ($laskurow['kuka_ext_nimi'] != '' and $yhtiorow['extranet_kerayspoikkeama_email'] != '') {
        $uloslisa .= t("Tilauksen keräsi").": $keraaja[nimi]<br><br>";
        $ulos = str_replace("</font><hr><br><br><table>", "</font><hr><br><br>$uloslisa<table>", $ulos);

        // Sähköpostin lähetykseen parametrit
        $parametri = array(
          "to"           => $yhtiorow["extranet_kerayspoikkeama_email"],
          "cc"           => "",
          "subject"      => "{$yhtiorow['nimi']} - ".t("Keräyspoikkeamat", $kieli)."{$_subject_lisa}",
          "ctype"        => $_ctype,
          "body"         => $ulos,
          "attachements" => "",
        );

        pupesoft_sahkoposti($parametri);
      }

      unset($ulos);
      unset($header);
    }
  }

  if ($tee == 'P' and $real_submit == 'yes') {
    if ($keraamaton > 0) {

      $chk_pakkaukset = array();

      if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {

        $query = "SELECT kerayserat.pakkaus,
                  kerayserat.pakkausnro,
                  group_concat(distinct kerayserat.otunnus) otunnukset
                  FROM kerayserat
                  JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
                  WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                  AND kerayserat.nro     = '{$id}'
                  AND kerayserat.otunnus IN ({$tilausnumeroita})
                  AND kerayserat.tila    = 'K'
                  GROUP BY 1,2
                  ORDER BY kerayserat.pakkausnro";
        $chk_pak_res = pupe_query($query);

        while ($chk_pak_row = mysql_fetch_assoc($chk_pak_res)) {
          $chk_pakkaukset[$chk_pak_row['pakkaus']][$chk_pak_row['pakkausnro']] = explode(",", $chk_pak_row['otunnukset']);
        }
      }

      if ($toim == "VASTAANOTA_REKLAMAATIO") {
        $hakualatila = 'C';
      }
      else {
        $hakualatila = 'A';
      }

      // Jos tilauksella oli yhtään keräämätöntä riviä
      $query = "SELECT lasku.tunnus, lasku.vienti, lasku.tila, lasku.alatila,
                lasku.toimitustavan_lahto,
                lasku.ytunnus,
                lasku.toim_osoite,
                lasku.toim_postino,
                lasku.toim_postitp,
                toimitustapa.rahtikirja,
                toimitustapa.tulostustapa,
                toimitustapa.nouto,
                lasku.varasto,
                lasku.toimitustapa,
                lasku.jaksotettu,
                lasku.yhtio,
                lasku.kohdistettu,
                lasku.liitostunnus,
                lasku.ohjelma_moduli,
                lasku.mapvm,
                lasku.chn
                FROM lasku
                LEFT JOIN toimitustapa ON (lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite)
                where lasku.yhtio = '$kukarow[yhtio]'
                and lasku.tunnus  in ($tilausnumeroita)
                and lasku.tila    in ($tila)
                and lasku.alatila = '$hakualatila'";
      $lasresult = pupe_query($query);

      $lask_nro = "";
      $extra    = "";

      while ($laskurow = mysql_fetch_assoc($lasresult)) {

        $query = "SELECT kuljetusohje
                  FROM asiakas
                  WHERE yhtio = '{$laskurow['yhtio']}'
                  AND tunnus  = '{$laskurow['liitostunnus']}';";
        $resul = pupe_query($query);

        if (mysql_num_rows($resul) == 1) {
          $temprow = mysql_fetch_assoc($resul);
          $asiakkaan_kuljetusohje = $temprow["kuljetusohje"];
        }
        else {
          $asiakkaan_kuljetusohje = "";
        }

        if ($laskurow["tila"] == 'L' and $laskurow["vienti"] == '' and $laskurow["tulostustapa"] == "X" and $laskurow["nouto"] == "") {

          // Jos meillä on maksupositioita laskulla, tulee se siirtää alatilaan J
          if ($laskurow['jaksotettu'] != 0) {
            $alatilak = "J";
          }
          else {
            $alatilak = "D";
          }

          // Päivitetään myös rivit toimitetuiksi
          $query = "UPDATE tilausrivi
                    SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
                    WHERE otunnus   = '$laskurow[tunnus]'
                    and var         not in ('P','J','O','S')
                    and yhtio       = '$kukarow[yhtio]'
                    and keratty    != ''
                    and toimitettu  = ''
                    and tyyppi      = 'L'";
          $yoimresult = pupe_query($query);

          // Etukäteen maksetut tilaukset pitää muuttaa takaisin "maksettu"-tilaan
          $query = "UPDATE lasku SET
                    alatila      = 'X'
                    WHERE yhtio  = '$kukarow[yhtio]'
                    AND tunnus   = '$laskurow[tunnus]'
                    AND mapvm   != '0000-00-00'
                    AND chn      = '999'";
          $yoimresult  = pupe_query($query);

          if ($laskurow['mapvm'] != '0000-00-00' and $laskurow['chn'] == '999') {
            $alatilak = "X";
          }

          // Etukäteen maksettu Magentotilaus laskutetaan, jos ei ole jo laskuttunut
          if ($laskurow['ohjelma_moduli'] == 'MAGENTOJT') {
            laskuta_magentojt($laskurow['tunnus']);
          }
        }
        elseif ($laskurow["tila"] == 'G' and $laskurow["vienti"] == '' and $laskurow["tulostustapa"] == "X" and $laskurow["nouto"] == "") {
          // Jos meillä on maksupositioita laskulla, tulee se siirtää alatilaan J
          if ($laskurow['jaksotettu'] != 0) {
            $alatilak = "J";
          }
          else {
            $alatilak = "D";
          }
        }
        elseif ($toim == "VASTAANOTA_REKLAMAATIO" and $laskurow["tila"] == 'C' and $laskurow["alatila"] == "C") {
          $alatilak = "D";
          $extra = ", tila = 'L' ";
        }
        else {
          $alatilak = "C";
        }

        $_siirtolista         = ($laskurow['tila'] == 'G');
        $_siirrolla_ei_lahtoa = ($laskurow['toimitustavan_lahto'] == 0);
        $_laaja_toimipaikka   = ($yhtiorow['toimipaikkakasittely'] == "L");

        if ($_siirtolista and $_siirrolla_ei_lahtoa and $_laaja_toimipaikka) {
          paivita_siirtolistan_toimipaikka($laskurow['tunnus']);
        }

        if ($_siirtolista) {

          $query = "SELECT SUM(varattu) keratty
                    FROM tilausrivi
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND otunnus = '{$laskurow['tunnus']}'
                    AND tyyppi  = 'G'
                    AND var     not in ('P','J')";
          $_keraamaton_chk_res = pupe_query($query);
          $_ker_chk_row = mysql_fetch_assoc($_keraamaton_chk_res);

          if ($_ker_chk_row['keratty'] == 0) {
            $alatilak = 'X';
          }
        }

        if ($yhtiorow['vahvistusviesti_asiakkaalle'] == "Y") {
          require_once "inc/jt_ja_tyomaarays_valmis_viesti.inc";
          laheta_vahvistusviesti($zoner_tunnarit["username"], $zoner_tunnarit["salasana"], $id);
        }

        // Lasku päivitetään vasta kuin tilausrivit on päivitetty...
        $query  = "UPDATE lasku SET
                   alatila     = '$alatilak'
                   $extra
                   WHERE yhtio = '$kukarow[yhtio]'
                   AND tunnus  = '$laskurow[tunnus]'";
        $result = pupe_query($query);

        if ($lask_nro == '') {
          $lask_nro = $laskurow['tunnus'];
        }

        if ($yhtiorow['kerayserat'] != 'K' and $yhtiorow['pakkaamolokerot'] != '') {
          // jos meillä on pakkaamolokerot käytössä (eli pakkaamotsydeema), niin esisyötetään rahtikirjat jos käyttäjä on antanut kollien/rullakkojen määrät
          // ei kuiteskaan tehdä tästä virallisesti esisyötettyä rahtikirjaa!
          if (isset($pakkaamo_kolli) and $pakkaamo_kolli != '') {
            $pakkaamo_kolli = (int) $pakkaamo_kolli;

            $query  = "INSERT INTO rahtikirjat SET
                       kollit         = '$pakkaamo_kolli',
                       pakkaus        = 'KOLLI',
                       pakkauskuvaus  = 'KOLLI',
                       rahtikirjanro  = '$lask_nro',
                       otsikkonro     = '$lask_nro',
                       tulostuspaikka = '$laskurow[varasto]',
                       yhtio          = '$kukarow[yhtio]',
                       viesti         = '$asiakkaan_kuljetusohje'";
            $result_rk = pupe_query($query);
          }

          if (isset($pakkaamo_rullakko) and $pakkaamo_rullakko != '') {
            $pakkaamo_rullakko = (int) $pakkaamo_rullakko;

            $query  = "INSERT INTO rahtikirjat SET
                       kollit         = '$pakkaamo_rullakko',
                       pakkaus        = 'Rullakko',
                       pakkauskuvaus  = 'Rullakko',
                       rahtikirjanro  = '$lask_nro',
                       otsikkonro     = '$lask_nro',
                       tulostuspaikka = '$laskurow[varasto]',
                       yhtio          = '$kukarow[yhtio]',
                       viesti         = '$asiakkaan_kuljetusohje'";
            $result_rk = pupe_query($query);
          }
        }

        if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
          $query = "SELECT kerayserat.pakkaus as kerayseran_pakkaus,
                    IFNULL(pakkaus.pakkaus, 'MUU KOLLI') pakkaus,
                    IFNULL(pakkaus.pakkauskuvaus, 'MUU KOLLI') pakkauskuvaus,
                    IFNULL(pakkaus.oma_paino, 0) oma_paino,
                    IF(pakkaus.puukotuskerroin is not null and pakkaus.puukotuskerroin > 0, pakkaus.puukotuskerroin, 1) puukotuskerroin,
                    SUM(tuote.tuotemassa * kerayserat.kpl_keratty) tuotemassa,
                    SUM(tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * kerayserat.kpl_keratty) as kuutiot,
                    COUNT(distinct kerayserat.pakkausnro) AS kollit
                    FROM kerayserat
                    LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
                    JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
                    JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                    WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                    AND kerayserat.nro     = '{$id}'
                    AND kerayserat.otunnus = '{$laskurow['tunnus']}'
                    AND kerayserat.tila    = 'K'
                    GROUP BY 1,2,3,4,5
                    ORDER BY kerayserat.pakkausnro";
          $keraysera_res = pupe_query($query);

          while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {

            $kilot = round($keraysera_row["tuotemassa"] + $keraysera_row["oma_paino"], 2);
            $kuutiot = round($keraysera_row["kuutiot"] * $keraysera_row["puukotuskerroin"], 4);

            $tulostettulisa = "";

            // Merkataan tieto tulostetuksi jos tulostustapa on hetitulostus ja lappu on jo tullut Unifaunista
            if ($laskurow['tulostustapa'] == 'H' and preg_match("/rahtikirja_unifaun_(ps|uo|xp)_siirto\.inc/", $laskurow["rahtikirja"])) {
              $tulostettulisa = " , tulostettu = now() ";
            }

            if (count($chk_pakkaukset) > 0) {
              $counter = 0;

              foreach ($chk_pakkaukset[$keraysera_row['kerayseran_pakkaus']] as $_pak_arr) {

                foreach ($_pak_arr as $_pak_nro => $_tunn) {
                  if ($laskurow['tunnus'] == $_tunn) $counter += (1 / count($_pak_arr));
                }
              }

              if ($counter != 0) {
                $keraysera_row['kollit'] = $counter;
              }
            }

            // Insertöidään aina rahtikirjan tiedot per tilaus
            $query_ker  = "INSERT INTO rahtikirjat SET
                           kollit         = '{$keraysera_row['kollit']}',
                           kilot          = '{$kilot}',
                           kuutiot        = '{$kuutiot}',
                           pakkauskuvaus  = '{$keraysera_row['pakkauskuvaus']}',
                           pakkaus        = '{$keraysera_row['pakkaus']}',
                           rahtikirjanro  = '{$laskurow['tunnus']}',
                           otsikkonro     = '{$laskurow['tunnus']}',
                           tulostuspaikka = '{$laskurow['varasto']}',
                           toimitustapa   = '{$laskurow['toimitustapa']}',
                           yhtio          = '{$kukarow['yhtio']}',
                           merahti        = '{$laskurow['kohdistettu']}',
                           viesti         = '$asiakkaan_kuljetusohje'
                           {$tulostettulisa}";
            $ker_res = pupe_query($query_ker);
          }

          if ($laskurow['tulostustapa'] == 'E' and
            (($laskurow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") or
              ($laskurow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != ""))) {

            // Katotaan jääkö meille tässä vaiheessa tyhjiä kolleja?
            $query = "SELECT pakkausnro, sscc_ulkoinen, sum(kpl_keratty) kplkeratty
                      FROM kerayserat
                      WHERE yhtio        = '{$kukarow['yhtio']}'
                      AND nro            = '$id'
                      AND otunnus        = '{$laskurow['tunnus']}'
                      AND tila           = 'K'
                      AND sscc_ulkoinen != '0'
                      GROUP BY 1,2
                      HAVING kplkeratty = 0";
            $keraysera_res = pupe_query($query);

            while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {
              if ($laskurow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' and $unifaun_ps_host != "" and $unifaun_ps_user != "" and $unifaun_ps_pass != "" and $unifaun_ps_path != "") {
                $unifaun = new Unifaun($unifaun_ps_host, $unifaun_ps_user, $unifaun_ps_pass, $unifaun_ps_path, $unifaun_ps_port, $unifaun_ps_fail, $unifaun_ps_succ);
              }
              elseif ($laskurow["rahtikirja"] == 'rahtikirja_unifaun_uo_siirto.inc' and $unifaun_uo_host != "" and $unifaun_uo_user != "" and $unifaun_uo_pass != "" and $unifaun_uo_path != "") {
                $unifaun = new Unifaun($unifaun_uo_host, $unifaun_uo_user, $unifaun_uo_pass, $unifaun_uo_path, $unifaun_uo_port, $unifaun_uo_fail, $unifaun_uo_succ);
              }

              $mergeid = md5($laskurow["toimitustavan_lahto"].$laskurow["ytunnus"].$laskurow["toim_osoite"].$laskurow["toim_postino"].$laskurow["toim_postitp"]);

              $unifaun->_discardParcel($mergeid, $keraysera_row['sscc_ulkoinen']);
              $unifaun->ftpSend();
            }
          }

          // jos kyseessä on toimitustapa jonka rahtikirja on hetitulostus
          if ($laskurow['tulostustapa'] == 'H' and $laskurow["nouto"] == "") {
            // päivitetään keräyserän tila "Rahtikirja tulostettu"-tilaan
            $query = "UPDATE kerayserat
                      SET tila = 'R'
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND nro     = '$id'
                      AND otunnus = '{$laskurow['tunnus']}'";
            $tila_upd_res = pupe_query($query);
          }
          else {
            // päivitetään keräyserän tila "Kerätty"-tilaan
            $query = "UPDATE kerayserat
                      SET tila = 'T'
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND nro     = '$id'
                      AND otunnus = '{$laskurow['tunnus']}'";
            $tila_upd_res = pupe_query($query);
          }
        }
      }

      // Tutkitaan vielä aivan lopuksi mihin tilaan me laitetaan tämä otsikko
      // Keräysvaiheessahan tilausrivit muuttuvat ja tarkastamme nyt tilanteen uudestaan
      // Tämä tehdään vain myyntitilauksille
      if (stripos($tila, "L") !== FALSE) {
        $kutsuja = "keraa.php";

        $query = "SELECT *
                  FROM lasku
                  WHERE tunnus in ($tilausnumeroita)
                  and yhtio    = '$kukarow[yhtio]'
                  and tila     = 'L'";
        $lasresult = pupe_query($query);

        while ($laskurow = mysql_fetch_assoc($lasresult)) {
          require "tilaus-valmis-valitsetila.inc";
        }
      }

      if ($toim != 'VASTAANOTA_REKLAMAATIO') {
        // Tulostetaan uusi lähete jos käyttäjä valitsi drop-downista printterin
        // Paitsi jos tilauksen tila päivitettiin sellaiseksi, että lähetettä ei kuulu tulostaa
        $query = "SELECT lasku.*,
                  if(asiakas.keraysvahvistus_email != '', asiakas.keraysvahvistus_email, asiakas.email) email,
                  asiakas.keraysvahvistus_lahetys,
                  toimitustapa.nouto
                  FROM lasku
                  LEFT JOIN asiakas on lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus
                  LEFT JOIN toimitustapa ON (lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite)
                  WHERE lasku.tunnus in ($tilausnumeroita)
                  and lasku.yhtio    = '$kukarow[yhtio]'
                  and lasku.alatila  in ('C','D')";
        $lasresult = pupe_query($query);

        $tilausnumeroita_backup        = $tilausnumeroita;
        $lahete_tulostus_paperille     = 0;
        $lahete_tulostus_paperille_vak = 0;
        $lahete_tulostus_emailiin      = 0;
        $lahete_tulostus_ruudulle      = 0;
        $laheteprintterinimi           = "";
        $onko_nouto                    = "";
        $lahetekpl_alkuperainen = $lahetekpl;
        $toimitustapa_tarroille = "";

        while ($laskurow = mysql_fetch_assoc($lasresult)) {

          // Nollataan tämä:
          $komento        = "";
          $oslapp         = "";
          $vakadr_komento = "";
          $onko_nouto     = $laskurow['nouto'];

          if (empty($toimitustapa_tarroille)) {
            $toimitustapa_tarroille = $laskurow['toimitustapa'];
          }

          if ($yhtiorow["vak_erittely"] == "K" and $yhtiorow["kerayserat"] == "K" and $vakadrkpl > 0 and $vakadr_tulostin !='' and $toim == "") {
            //haetaan lähetteen tulostuskomento
            $query   = "SELECT *
                        from kirjoittimet
                        where yhtio = '$kukarow[yhtio]'
                        and tunnus  = '$vakadr_tulostin'";
            $kirres  = pupe_query($query);
            $kirrow  = mysql_fetch_assoc($kirres);
            $vakadr_komento = $kirrow['komento'];

            $onko_vak = tulosta_vakadr_erittely($laskurow["tunnus"], $vakadr_komento, $tee);

            if ($vakadr_komento != 'email' and $onko_vak) $lahete_tulostus_paperille_vak++;
          }

          //Lähetetulostin
          if ($valittu_tulostin == "-88") {
            $komento = "-88";
            $laheteprintterinimi = t("PDF Ruudulle");
          }
          else {
            $valittu_tulostin_valittu = $valittu_tulostin;

            // Katsotaan onko avainsanoihin määritelty varaston toimipaikan läheteprintteriä
            if (!empty($laskurow['yhtio_toimipaikka'])) {
              $avainsana_where = " and avainsana.selite       = '{$laskurow['varasto']}'
                                   and avainsana.selitetark   = '{$laskurow['yhtio_toimipaikka']}'
                                   and avainsana.selitetark_2 = 'printteri1'";

              $tp_tulostin = t_avainsana("VARTOIMTULOSTIN", '', $avainsana_where, '', '', "selitetark_3");

              if (!empty($tp_tulostin)) {
                $valittu_tulostin_valittu = $tp_tulostin;
              }
            }

            if ($valittu_tulostin_valittu != "") {
              // haetaan lähetteen tulostuskomento
              $query   = "SELECT *
                          from kirjoittimet
                          where yhtio = '$kukarow[yhtio]'
                          and tunnus  = '$valittu_tulostin_valittu'";
              $kirres  = pupe_query($query);
              $kirrow  = mysql_fetch_assoc($kirres);
              $komento = $kirrow['komento'];

              $laheteprintterinimi = $kirrow["kirjoitin"];
            }
          }

          if ($valittu_oslapp_tulostin == "-88") {
            $oslapp = "-88";
          }
          elseif ($valittu_oslapp_tulostin != "") {
            //haetaan osoitelapun tulostuskomento
            $query  = "SELECT *
                       from kirjoittimet
                       where yhtio = '$kukarow[yhtio]'
                       and tunnus  = '$valittu_oslapp_tulostin'";
            $kirres = pupe_query($query);
            $kirrow = mysql_fetch_assoc($kirres);

            $oslapp = $kirrow['komento'];
            $oslapp_mediatyyppi = $kirrow['mediatyyppi'];
          }

          @include 'inc/pks_lahete.inc';

          if ($laskurow['tila'] == 'G' and !isset($valittu_uista)) {
            $lahetekpl = $yhtiorow["oletus_lahetekpl_siirtolista"];
          }

          $_keraysvahvistus_lahetys = array('k', 'L', 'M', 'N', 'Q', 'P');

          if (($komento != "" and $lahetekpl > 0)
            or ($laskurow["tila"] != 'V'
              and ((in_array($laskurow["keraysvahvistus_lahetys"], $_keraysvahvistus_lahetys)
                  or (in_array($yhtiorow["keraysvahvistus_lahetys"], $_keraysvahvistus_lahetys)
                    and $laskurow["keraysvahvistus_lahetys"] == ''))
                or (($laskurow["keraysvahvistus_lahetys"] == 'o'
                    or ($yhtiorow["keraysvahvistus_lahetys"] == 'o'
                      and $laskurow["keraysvahvistus_lahetys"] == ''))
                  and $laskurow['email'] != ""))
            )
          ) {

            list($komento, $koontilahete, $koontilahete_tilausrivit) = koontilahete_check($laskurow, $komento);

            if ((is_array($komento) and count($komento) > 0) or (!is_array($komento) and $komento != "")) {

              // Lasketaan kuinka monta lähetettä tulostuu paperille (muuttujat valuu optiscan.php:seen)
              if (is_array($komento)) {
                foreach ($komento as $paprulleko) {
                  if ($paprulleko == '-88') {
                    $lahete_tulostus_ruudulle++;
                  }
                  elseif ($paprulleko != 'email' and substr($paprulleko, 0, 12) != 'asiakasemail') {
                    $lahete_tulostus_paperille++;
                  }
                  else {
                    $lahete_tulostus_emailiin++;
                  }
                }
              }
              elseif ($komento == "-88") {
                $lahete_tulostus_ruudulle++;
              }
              elseif ($komento != 'email' and substr($komento, 0, 12) != 'asiakasemail') {
                $lahete_tulostus_paperille++;
              }
              else {
                $lahete_tulostus_emailiin++;
              }

              $sellahetetyyppi = (!isset($sellahetetyyppi)) ? "" : $sellahetetyyppi;
              $kieli = (!isset($kieli)) ? "" : $kieli;

              $params = array(
                'laskurow'                 => $laskurow,
                'sellahetetyyppi'          => $sellahetetyyppi,
                'extranet_tilausvahvistus' => "",
                'naytetaanko_rivihinta'    => "",
                'tee'                      => $tee,
                'toim'                     => $toim,
                'komento'                  => $komento,
                'lahetekpl'                => $lahetekpl,
                'kieli'                    => $kieli,
                'koontilahete'             => $koontilahete,
                'koontilahete_tilausrivit' => $koontilahete_tilausrivit,
              );

              pupesoft_tulosta_lahete($params);

              if ($lahete_tulostus_paperille > 0) echo "<br>".t("Tulostettiin %s paperilähetettä", "", $lahete_tulostus_paperille).".";
              //if ($lahete_tulostus_ruudulle > 0) echo "<br>".t("Tulostettiin %s lähetettä ruudulle", "", $lahete_tulostus_ruudulle).".";
              if ($lahete_tulostus_emailiin > 0) echo "<br>".t("Lähetettiin %s sähköistä lähetettä", "", $lahete_tulostus_emailiin).".";
              if ($lahete_tulostus_emailiin == 0 and $lahete_tulostus_paperille == 0 and $lahete_tulostus_ruudulle == 0) echo "<br>".t("Lähetteitä ei tulostettu").".";
            }
          }

          if (($yhtiorow['karayksesta_rahtikirjasyottoon'] == 'Y' and $onko_nouto == '') or ($yhtiorow['karayksesta_rahtikirjasyottoon'] == 'H' and $rahtikirjalle != "")) {
            $valittu_oslapp_tulostin = "";
            $oslapp = '';
            $oslappkpl = 0;
          }

          // Tulostetaan osoitelappu
          if ($valittu_oslapp_tulostin != "" and $oslapp != '' and $oslappkpl > 0) {
            $tunnus = $laskurow["tunnus"];

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
            elseif ($oslarow['osoitelappu'] == 'oslap_mg' and $yhtiorow['kerayserat'] == 'K' and $toim == "") {

              $query = "SELECT kerayserat.otunnus, pakkaus.pakkaus, kerayserat.pakkausnro
                        FROM kerayserat
                        LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
                        WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                        AND kerayserat.otunnus IN ({$tilausnumeroita_backup})
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
        }

        // Tulostetaan lavatarrat
        if ($valittu_lavatarra_tulostin != "") {
          require "inc/lavakeraysparametrit.inc";
          require_once "lavatarra_pdf.inc";

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
                    WHERE tilausrivi.otunnus  in ($tilausnumeroita_backup)
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

          $query   = "SELECT *
                      from kirjoittimet
                      where yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$valittu_lavatarra_tulostin'";
          $kirres  = pupe_query($query);
          $kirrow  = mysql_fetch_assoc($kirres);
          $lavatarra_komento = $kirrow['komento'];

          $params_lavatarra = array(
            'norm'              => $norm,
            'pieni'             => $pieni,
            'pieni_boldi'       => $pieni_boldi,
            'boldi'             => $boldi,
            'iso'               => $iso,
            'iso_boldi'         => $iso_boldi,
            'rectparam'         => $rectparam,
            'komento'           => $lavatarra_komento,
            'toimitustapa'      => $toimitustapa_tarroille,
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

        if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
          $query = "UPDATE lasku
                    SET alatila = 'B'
                    WHERE yhtio  = '{$kukarow['yhtio']}'
                    AND alatila != 'X'
                    AND tunnus   IN ({$tilausnumeroita_backup})";
          $alatila_upd_res = pupe_query($query);
        }

        echo "<br><br>";
      }
    }

    $boob    = '';
    $header  = '';
    $content = '';
    $rivit   = '';

    if ($yhtiorow['karayksesta_rahtikirjasyottoon'] == 'Y' or ($yhtiorow['karayksesta_rahtikirjasyottoon'] == 'H' and $rahtikirjalle != "")) {

      if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
        // Jos nyt jostain syystä, esim back-nappuloinnin takia tulee tyhjänä niin ei kuolla erroriin
        if ($tilausnumeroita_backup == "") $tilausnumeroita_backup = 0;

        $wherelisa = " AND lasku.alatila = 'B' AND lasku.tunnus IN ({$tilausnumeroita_backup}) ";
        $joinlisa  = " JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio AND toimitustapa.selite = lasku.toimitustapa AND toimitustapa.nouto = '') ";
      }
      else {
        $wherelisa = " AND lasku.alatila = 'C' AND lasku.tunnus = '{$id}' ";
        $joinlisa  = "";
      }

      // toimitustapa ei saa olla nouto.
      $query = "SELECT lasku.tunnus
                FROM lasku
                {$joinlisa}
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND lasku.tila    = 'L'
                {$wherelisa}";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        $rahtikirjaan = 'mennaan';
        $_tilnrot = explode(",", $tilausnumeroita_backup);
        $id = $_tilnrot[0];
      }
      else {
        $tilausnumeroita = '';
        $rahtikirjaan = '';
        $id = 0;
      }
    }
    else {
      $tilausnumeroita  = '';
      $rahtikirjaan    = '';
      $id         = 0;
    }
  }
}

if (php_sapi_name() != 'cli' and strpos($_SERVER['SCRIPT_NAME'], "keraa.php") !== FALSE) {
  if ($id == '') {
    $id = 0;
    if ($logistiikka_yhtio != '' and $konsernivarasto_yhtiot != '') {
      $logistiikka_yhtio = $konsernivarasto_yhtiot;
    }
  }

  if ($id == 0) {

    $valmistuslinjat = hae_valmistuslinjat();

    $formi  = "find";
    $kentta  = "etsi";

    echo "<span id='hakutable'>";
    echo "<form name='find' method='post'>";
    echo "<input type='hidden' name='toim' value='{$toim}'>";
    echo "<input type='hidden' id='jarj' name='jarj' value='{$jarj}'>";

    echo "<table>";
    echo "<tr>";
    echo "<th>", t("Valitse varasto"), ":</th>";
    echo "<td>";
    echo "<select name='tuvarasto' onchange='submit()'>";

    $query = "SELECT yhtio, tunnus, nimitys
              FROM varastopaikat
              WHERE {$logistiikka_yhtiolisa} AND tyyppi != 'P'
              ORDER BY yhtio, tyyppi, nimitys";
    $result = pupe_query($query);

    echo "<option value='KAIKKI'>", t("Näytä kaikki"), "</option>";

    while ($row = mysql_fetch_assoc($result)) {
      $sel = '';

      if ($row['tunnus'] == $tuvarasto) {
        $sel = 'selected';
        $tuvarasto = $row['tunnus'];
      }

      echo "<option value='{$row['tunnus']}' {$sel}>{$row['nimitys']}";

      if ($logistiikka_yhtio != '') {
        echo " ({$row['yhtio']})";
      }

      echo "</option>";
    }

    echo "</select>";

    $query = "SELECT DISTINCT maa
              FROM varastopaikat
              WHERE maa != ''
              AND {$logistiikka_yhtiolisa} AND tyyppi != 'P'
              ORDER BY maa";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 1) {
      echo "<select name='tumaa' onchange='submit()'>";
      echo "<option value=''>", t("Kaikki"), "</option>";

      while ($row = mysql_fetch_assoc($result)) {
        $sel = '';

        if ($row['maa'] == $tumaa) {
          $sel = 'selected';
          $tumaa = $row['maa'];
        }

        echo "<option value='{$row['maa']}' {$sel}>{$row['maa']}</option>";
      }
      echo "</select>";
    }

    echo "</td>";
    echo "<th>", t("Valitse tilaustyyppi"), ":</th>";
    echo "<td>";
    echo "<select name='tutyyppi' onchange='submit()'>";

    $sel = array($tutyyppi => 'selected') + array('NORMAA' => '', 'ENNAKK' => '', 'JTTILA' => '', 'VALMISTUS' => '');

    echo "<option value='KAIKKI'>", t("Näytä kaikki"), "</option>";
    echo "<option value='NORMAA' {$sel['NORMAA']}>", t("Näytä normaalitilaukset"), "</option>";
    echo "<option value='ENNAKK' {$sel['ENNAKK']}>", t("Näytä ennakkotilaukset"), "</option>";
    echo "<option value='JTTILA' {$sel['JTTILA']}>", t("Näytä jt-tilaukset"), "</option>";
    echo "<option value='VALMISTUS' {$sel['VALMISTUS']}>", t("Näytä jt-tilaukset valmistuksesta"), "</option>";

    echo "</select>";
    echo "</td>";
    echo "</tr>";

    if (!isset($tuoteno)) $tuoteno = '';
    if (!isset($pp)) $pp = '';
    if (!isset($kk)) $kk = '';
    if (!isset($vv)) $vv = '';

    echo "<tr>";
    echo "<th>".t('Tuotenumero')."</th>";
    echo "<td>";
    echo "<input type='text' name='tuoteno' value='{$tuoteno}' />";
    echo "</td>";

    echo "<th>".t('Keräyspäivä')." (pp-kk-vvvv)</th>";
    echo "<td>";
    echo "  <input type='text' name='pp' value='{$pp}' size='3'>
        <input type='text' name='kk' value='{$kk}' size='3'>
        <input type='text' name='vv' value='{$vv}' size='5'>";
    echo "</td>";
    echo "</tr>";

    if (!empty($valmistuslinjat)) {
      echo "<tr>";
      echo "<th>".t('Valmistuslinja')."</th>";
      echo "<td>";

      echo "<select name='valmistuslinja'>";
      echo "<option value='' >".t('Ei valintaa')."</option>";
      foreach ($valmistuslinjat as $_valmistuslinja) {
        $sel = "";
        if ($_valmistuslinja['selite'] == $valmistuslinja) {
          $sel = "SELECTED";
        }
        echo "<option value='{$_valmistuslinja['selite']}' {$sel}>{$_valmistuslinja['selitetark']}</option>";
      }
      echo "</select>";

      echo "</td>";

      echo "<th></th>";
      echo "<td>";
      echo "</td>";
      echo "</tr>";
    }

    echo "<tr><th>", t("Valitse toimitustapa"), ":</th><td><select name='tutoimtapa' onchange='submit()'>";

    $query = "SELECT selite, MIN(tunnus) tunnus
              FROM toimitustapa
              WHERE {$logistiikka_yhtiolisa}
              GROUP BY selite
              ORDER BY selite";
    $result = pupe_query($query);

    echo "<option value='KAIKKI'>", t("Näytä kaikki"), "</option>";

    while ($row = mysql_fetch_assoc($result)) {
      $sel = '';

      if ($row['selite'] == $tutoimtapa) {
        $sel = 'selected';
        $tutoimtapa = $row['selite'];
      }

      echo "<option value='{$row['selite']}' {$sel}>", t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV"), "</option>";
    }

    echo "</select></td>";

    echo "<th>", t("Etsi tilausta"), ":</th><td><input type='text' name='etsi'>";
    echo "<input type='submit' class='hae_btn' value = '".t("Etsi")."'></td></tr>";
    echo "</table>";
    echo "</form>";
    echo "</span>";

    $haku = '';
    $kerayserahaku = '';

    if (!is_numeric($etsi) and $etsi != '') {
      $haku .= "AND lasku.nimi LIKE '%{$etsi}%'";
    }

    if (is_numeric($etsi) and $etsi != '') {
      if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
        $query = "SELECT nro
                  FROM kerayserat
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND (otunnus = '{$etsi}' or nro = '{$etsi}')";
        $nro_chk_res = pupe_query($query);
        $nro_chk_row = mysql_fetch_assoc($nro_chk_res);

        $kerayserahaku = "AND kerayserat.nro = '{$nro_chk_row['nro']}'";
      }
      else {
        $haku .= "AND lasku.tunnus = '{$etsi}'";
      }
    }

    if ($tuvarasto != '' and $tuvarasto != 'KAIKKI') {
      $haku .= " AND lasku.varasto = '{$tuvarasto}' ";
    }

    if ($tumaa != '') {
      $query = "SELECT GROUP_CONCAT(tunnus) tunnukset
                FROM varastopaikat
                WHERE maa != ''
                AND {$logistiikka_yhtiolisa}
                AND maa    = '{$tumaa}'";
      $maare = pupe_query($query);
      $maarow = mysql_fetch_assoc($maare);
      $haku .= " AND lasku.varasto IN ({$maarow['tunnukset']}) ";
    }

    if ($tutoimtapa != '' and $tutoimtapa != 'KAIKKI') {
      $haku .= " AND lasku.toimitustapa = '{$tutoimtapa}' ";
    }

    if ($tutyyppi != '' and $tutyyppi != 'KAIKKI') {
      if ($tutyyppi == "NORMAA") {
        $haku .= " AND lasku.clearing = '' ";
      }
      elseif ($tutyyppi == "ENNAKK") {
        $haku .= " AND lasku.clearing = 'ENNAKKOTILAUS' ";
      }
      elseif ($tutyyppi == "JTTILA") {
        $haku .= " AND lasku.clearing = 'JT-TILAUS' ";
      }
      elseif ($tutyyppi == "VALMISTUS") {
        $haku .= " AND lasku.sisviesti2 = 'Tehty valmistuksen kautta' ";
      }
    }

    if ($jarj != "") {
      $jarjx = " ORDER BY {$jarj}";
    }
    else {
      $jarjx = ($yhtiorow['kerayserat'] == 'K' and $toim == "") ? " ORDER BY kerayserat.nro" : " ORDER BY laadittu";
    }

    if ($toim == "VASTAANOTA_REKLAMAATIO") {
      $alatilareklamaatio = 'C';
    }
    else {
      $alatilareklamaatio = 'A';
    }

    $tilausrivi_join_ehto = "";
    if (isset($tuoteno) and $tuoteno != '') {
      $tilausrivi_join_ehto = "  AND tilausrivi.tuoteno = '{$tuoteno}'";
    }
    $valmistuslinja_where = "";
    if (isset($valmistuslinja) and $valmistuslinja != '') {
      $valmistuslinja_where = "  AND lasku.kohde = '{$valmistuslinja}'";
    }

    $kerayspaiva_where = "";
    if (!empty($pp) and !empty($kk) and !empty($vv)) {
      $paiva = "{$vv}-{$kk}-{$pp}";
      $valid = FormValidator::validateContent($paiva, 'paiva');

      if ($valid) {
        $kerayspaiva_where = "  AND lasku.kerayspvm = '{$paiva}'";
      }
    }

    $siirtolista_where = '';
    if ($toim == "SIIRTOLISTA" and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
      $siirtolista_where = " AND lasku.toimitustavan_lahto = 0 ";
    }

    if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
      $asiakas_join = "JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)";

      if ($yhtiorow['kerayserat'] != '' and $yhtiorow['siirtolistan_tulostustapa'] == 'U') {
        $asiakas_join = "";
      }
      $query = "SELECT lasku.yhtio AS 'yhtio',
                lasku.yhtio_nimi AS 'yhtio_nimi',
                kerayserat.nro AS 'keraysera',
                GROUP_CONCAT(DISTINCT lasku.toimitustapa ORDER BY lasku.toimitustapa SEPARATOR '<br />') AS 'toimitustapa',
                GROUP_CONCAT(DISTINCT lasku.prioriteettinro ORDER BY lasku.prioriteettinro SEPARATOR ', ') AS prioriteetti,
                GROUP_CONCAT(DISTINCT concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark, CONCAT(\"(\", lasku.ytunnus, \")\")) SEPARATOR '<br />') AS 'asiakas',
                GROUP_CONCAT(DISTINCT lasku.tunnus ORDER BY lasku.tunnus SEPARATOR ', ') AS 'tunnus',
                COUNT(DISTINCT tilausrivi.tunnus) AS 'riveja',
                kuka.nimi as keraaja_nimi,
                kuka.keraajanro as keraaja_nro,
                kerayserat.ohjelma_moduli,
                min(lasku.toimaika) toimaika,
                min(lasku.ytunnus) ytunnus,
                min(lasku.kerayspvm) kerayspvm
                FROM lasku USE INDEX (tila_index)
                JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (
                  tilausrivi.yhtio           = lasku.yhtio AND
                  tilausrivi.otunnus         = lasku.tunnus AND
                  tilausrivi.tyyppi          IN ({$tyyppi}) AND
                  tilausrivi.var             IN ('', 'H') AND
                  tilausrivi.keratty         = ''
                  {$tilausrivi_join_ehto}
                  AND tilausrivi.kerattyaika = '0000-00-00 00:00:00' AND
                  ((tilausrivi.laskutettu = '' AND tilausrivi.laskutettuaika   = '0000-00-00') OR lasku.mapvm != '0000-00-00'))
                JOIN kerayserat ON (kerayserat.yhtio = lasku.yhtio AND kerayserat.otunnus = lasku.tunnus AND kerayserat.tila = 'K' {$kerayserahaku})
                {$asiakas_join}
                LEFT JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = lasku.hyvak3)
                WHERE lasku.{$logistiikka_yhtiolisa}
                AND lasku.tila               IN ({$tila})
                AND lasku.alatila            IN ({$alatila})
                {$valmistuslinja_where}
                {$kerayspaiva_where}
                {$haku}
                GROUP BY 1,2,3
                {$jarjx}";
    }
    else {
      $query = "SELECT distinct
                if (lasku.kerayslista!=0, lasku.kerayslista, lasku.tunnus) tunnus,
                group_concat(DISTINCT lasku.tunnus SEPARATOR '<br>') tunnukset,
                min(lasku.ytunnus) ytunnus,
                min(concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark)) asiakas,
                min(lasku.luontiaika) laadittu,
                min(lasku.h1time) h1time,
                min(lasku.lahetepvm) lahetepvm,
                min(lasku.kerayspvm) kerayspvm,
                min(lasku.toimaika) toimaika,
                min(lasku.yhtio_toimipaikka) yhtio_toimipaikka,
                group_concat(DISTINCT lasku.laatija) laatija,
                group_concat(DISTINCT lasku.toimitustapa SEPARATOR '<br>') toimitustapa,
                group_concat(DISTINCT concat_ws('\n\n', if (comments!='',concat('".t("Lähetteen lisätiedot").":\n',comments),NULL), if (sisviesti2!='',concat('".t("Keräyslistan lisätiedot").":\n',sisviesti2),NULL)) SEPARATOR '\n') ohjeet,
                min(if (lasku.hyvaksynnanmuutos = '', 'X', lasku.hyvaksynnanmuutos)) prioriteetti,
                min(if (lasku.clearing = '', 'N', if (lasku.clearing = 'JT-TILAUS', 'J', if (lasku.clearing = 'ENNAKKOTILAUS', 'E', '')))) t_tyyppi,
                #(select nimitys from varastopaikat where varastopaikat.tunnus=min(lasku.varasto)) varastonimi,
                count(*) riveja,
                count(distinct lasku.tunnus) tilauksia,
                lasku.yhtio yhtio,
                lasku.yhtio_nimi yhtio_nimi
                from lasku use index (tila_index)
                JOIN tilausrivi use index (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D' {$tilausrivi_join_ehto})
                WHERE lasku.{$logistiikka_yhtiolisa}
                {$valmistuslinja_where}
                {$kerayspaiva_where}
                {$siirtolista_where}
                and lasku.tila                in ({$tila})
                and lasku.alatila             = '{$alatilareklamaatio}'
                and tilausrivi.tyyppi         in ({$tyyppi})
                and tilausrivi.var            in ('', 'H' {$var_lisa})
                and tilausrivi.keratty        = ''
                and tilausrivi.kerattyaika    = '0000-00-00 00:00:00'
                and ((tilausrivi.laskutettu    = ''
                and tilausrivi.laskutettuaika = '0000-00-00') or lasku.mapvm != '0000-00-00')
                {$haku}
                {$tilaustyyppi}
                GROUP BY tunnus
                {$jarjx}";
    }

    $result = pupe_query($query);

    //jos haetaan numerolla ja löydetään yksi osuma, siirrytään suoraan keräämään
    if (mysql_num_rows($result) == 1 and is_numeric($etsi) and $etsi != '') {
      $row = mysql_fetch_assoc($result);

      if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
        $id = $row["keraysera"];
      }
      else {
        $id = $row["tunnus"];
      }

      echo "  <script language='javascript'>
          $('#hakutable').hide();
        </script> ";
    }
    elseif (mysql_num_rows($result) > 0) {
      //piirretään taulukko...
      echo "<br><table>";
      echo "<tr>";
      if ($logistiikka_yhtio != '') {
        echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='yhtio'; document.forms['find'].submit();\">", t("Yhtiö"), "</a></th>";
      }

      if ($toim == "VASTAANOTA_REKLAMAATIO") {
        echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='yhtio_toimipaikka'; document.forms['find'].submit();\">", t("Toimipaikka"), "</a></th>";
      }

      echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='prioriteetti'; document.forms['find'].submit();\">", t("Pri"), "</a><br>";
      //echo "<a href='#' onclick=\"getElementById('jarj').value='varastonimi'; document.forms['find'].submit();\">".t("Varastoon")."</a></th>";

      if ($yhtiorow['kerayserat'] == '' or $toim != "") {
        echo "<a href='#'>", t("Varastoon"), "</a>";
      }

      echo "</th>";

      if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
        echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='keraysera'; document.forms['find'].submit();\">", t("Erä"), "</a></th>";
      }

      echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='tunnus'; document.forms['find'].submit();\">", t("Tilaus"), "</a></th>";

      echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='ytunnus'; document.forms['find'].submit();\">", t("Asiakas"), "</a><br>
          <a href='#' onclick=\"getElementById('jarj').value='asiakas'; document.forms['find'].submit();\">", t("Nimi"), "</a></th>";


      echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='laadittu'; document.forms['find'].submit();\">", t("Laadittu"), "</a><br>
            <a href='#' onclick=\"getElementById('jarj').value='lasku.h1time'; document.forms['find'].submit();\">", t("Valmis"), "</a><br>
          <a href='#' onclick=\"getElementById('jarj').value='lasku.lahetepvm'; document.forms['find'].submit();\">", t("Tulostettu"), "</a></th>";

      echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='kerayspvm'; document.forms['find'].submit();\">", t("Keräysaika"), "</a><br>
          <a href='#' onclick=\"getElementById('jarj').value='toimaika'; document.forms['find'].submit();\">", t("Toimitusaika"), "</a></th>";

      if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
        echo "  <th valign='top'>
              <a href='#' onclick=\"getElementById('jarj').value='keraaja_nimi'; document.forms['find'].submit();\">", t("Kerääjän nimi"), "</a>
              <br/>
              <a href='#' onclick=\"getElementById('jarj').value='keraaja_nro'; document.forms['find'].submit();\">", t("Kerääjän numero"), "</a>
            </th>";
      }

      echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='toimitustapa'; document.forms['find'].submit();\">", t("Toimitustapa"), "</a></th>";
      echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='riveja'; document.forms['find'].submit();\">", t("Riv"), "</a></th>";
      echo "<th valign='top'>", t("Kerää"), "</th>";

      echo "</tr></form>";

      $riveja_yht = 0;

      while ($row = mysql_fetch_assoc($result)) {
        echo "<tr class='aktiivi'>";

        if ($logistiikka_yhtio != '') {
          echo "<td valign='top'>{$row['yhtio_nimi']}</td>";
        }

        if ($toim == "VASTAANOTA_REKLAMAATIO") {
          if (!empty($row['yhtio_toimipaikka'])) {
            $_tp_res = hae_yhtion_toimipaikat($kukarow['yhtio'], $row['yhtio_toimipaikka']);
            $_tp_row = mysql_fetch_assoc($_tp_res);

            echo "<td valign='top'>{$_tp_row['nimi']}</td>";
          }
          else {
            echo "<td valign='top'></td>";
          }
        }

        if (isset($row['ohjeet']) and trim($row["ohjeet"]) != "") {
          echo "<div id='div_{$row['tunnus']}' class='popup' style='width: 500px;'>";
          echo t("Tilaukset"), ": {$row['tunnukset']}<br />";
          echo t("Laatija"), ": {$row['laatija']}<br /><br />";
          echo str_replace("\n", "<br />", $row["ohjeet"]), "<br />";
          echo "</div>";

          echo "<td valign='top' class='tooltip' id='{$row['tunnus']}'>{$row['t_tyyppi']} {$row['prioriteetti']} <img src='{$palvelin2}pics/lullacons/info.png' />";
        }
        else {
          if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
            echo "<td valign='top'>{$row['prioriteetti']}";
          }
          else {
            echo "<td valign='top'>{$row['t_tyyppi']} {$row['prioriteetti']}";
          }
        }

        if (isset($row['varastonimi'])) echo "<br>{$row['varastonimi']}";

        echo "</td>";

        $_moduuli = '';
        if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {

          if ($row['ohjelma_moduli'] != 'PUPESOFT') $_moduuli = "<br><font class='error'>{$row['ohjelma_moduli']}</font>";
          echo "<td valign='top'>{$row['keraysera']}{$_moduuli}</td>";
        }

        if ($toim == "VASTAANOTA_REKLAMAATIO") {
          echo "<td valign='top'>{$row['tunnukset']}</td>";
        }
        else {
          echo "<td valign='top'>{$row['tunnus']}</td>";
        }

        if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
          echo "<td valign='top'>{$row['asiakas']}</td>";
        }
        elseif ($toim == "VASTAANOTA_REKLAMAATIO" and $row['tilauksia'] > 1) {
          echo "<td valign='top'>", t("Useita"), "</td>";
        }
        else {
          echo "<td valign='top'>{$row['ytunnus']}<br />{$row['asiakas']}</td>";
        }

        if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
          echo "<td valign='top' nowrap align='right'></td>";
          echo "<td valign='top' nowrap align='right'></td>";
        }
        else {
          $laadittu_e  = tv1dateconv($row["laadittu"], "P", "LYHYT");
          $h1time_e   = tv1dateconv($row["h1time"], "P", "LYHYT");
          $lahetepvm_e = tv1dateconv($row["lahetepvm"], "P", "LYHYT");
          $lahetepvm_e = str_replace(substr($h1time_e, 0, strpos($h1time_e, " ")), "", $lahetepvm_e);
          $h1time_e   = str_replace(substr($laadittu_e, 0, strpos($laadittu_e, " ")), "", $h1time_e);

          echo "<td valign='top' nowrap align='right'>{$laadittu_e}<br />{$h1time_e}<br />{$lahetepvm_e}</td>";
          echo "<td valign='top' nowrap align='right'>", tv1dateconv($row["kerayspvm"], "", "LYHYT"), "<br />", tv1dateconv($row["toimaika"], "", "LYHYT"), "</td>";
        }

        if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
          echo "<td valign='top'>{$row['keraaja_nimi']}<br/>{$row['keraaja_nro']}</td>";
        }

        echo "<td valign='top'>{$row['toimitustapa']}</td>";
        echo "<td valign='top'>{$row['riveja']}</td>";

        $riveja_yht += $row['riveja'];

        echo "<td valign='top'><form method='post'>";

        if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
          echo "<input type='hidden' name='id' value='{$row['keraysera']}' />";
        }
        else {
          echo "<input type='hidden' name='id' value='{$row['tunnus']}' />";
        }

        echo "  <input type='hidden' name='toim' value='{$toim}' />
            <input type='hidden' name='lasku_yhtio' value='{$row['yhtio']}' />
            <input type='submit' name='tila' value='", t("Kerää"), "' /></form></td></tr>";
      }

      $spanni = $logistiikka_yhtio != '' ? 7 : 6;

      $spanni = ($yhtiorow['kerayserat'] == 'K' and $toim == "") ? $spanni + 1 : $spanni;

      echo "<tr>";
      echo "<td colspan='{$spanni}' style='text-align:right;' class='back'>", t("Rivejä yhteensä"), ":</td>";
      echo "<td valign='top' class='back'>{$riveja_yht}</td>";
      echo "</tr>";
      echo "</table>";
    }
    else {
      echo "<font class='message'>", t("Yhtään keräämätöntä tilausta ei löytynyt"), "...</font>";
    }
  }

  if ($id != 0 and (!isset($rahtikirjaan) or $rahtikirjaan == '')) {
    // päivitä kerätyt formi
    $formi  = "rivit";
    $kentta  = "keraajanro";

    $otsik_row = array();
    $keraysklontti = FALSE;

    if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
      $query = "SELECT lasku.varasto, GROUP_CONCAT(DISTINCT lasku.tunnus SEPARATOR ', ') AS 'tilaukset'
                FROM kerayserat
                JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus)
                WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                AND kerayserat.nro     = '{$id}'
                GROUP BY 1";
      $testresult = pupe_query($query);
      $testrow = mysql_fetch_assoc($testresult);

      $lp_varasto = $testrow["varasto"];
      $tilausnumeroita = $testrow['tilaukset'];
    }
    else {
      $query = "SELECT kerayslista, varasto
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$id}'";
      $testresult = pupe_query($query);
      $testrow = mysql_fetch_assoc($testresult);

      // Koko klöntti kuuluu aina samaan varastoon joten otetaan tämä tässä talteen
      $lp_varasto = $testrow["varasto"];

      if ($testrow['kerayslista'] > 0) {
        //haetaan kaikki tälle klöntille kuuluvat otsikot
        $query = "SELECT GROUP_CONCAT(DISTINCT tunnus ORDER BY tunnus SEPARATOR ',') tunnukset
                  FROM lasku
                  WHERE yhtio      = '$kukarow[yhtio]'
                  and kerayslista  = '$id'
                  and kerayslista != 0
                  and tila         in ($tila)
                  $tilaustyyppi
                  HAVING tunnukset is not null";
        $toimresult = pupe_query($query);

        //jos rivejä löytyy niin tiedetään, että tämä on keräysklöntti
        if (mysql_num_rows($toimresult) > 0) {
          $toimrow = mysql_fetch_assoc($toimresult);
          $tilausnumeroita = $toimrow["tunnukset"];
          $keraysklontti = true;
        }
        else {
          $tilausnumeroita = $id;
        }
      }
      else {
        $tilausnumeroita = $id;
      }
    }

    echo "<table>";

    if ($toim == 'SIIRTOLISTA') {
      echo "<tr><th align='left'>", t("Siirtolista"), "</th><td>{$id}</td></tr>";
    }
    if ($toim == 'MYYNTITILI') {
      echo "<tr><th align='left'>", t("Myyntitili"), "</th><td>{$id}</td></tr>";
    }
    else {
      if ($toim == "VASTAANOTA_REKLAMAATIO") {
        $alatilareklamaatio = 'C';
      }
      else {
        $alatilareklamaatio = 'A';
      }

      $query = "SELECT
                lasku.*,
                toimitustapa.tulostustapa,
                toimitustapa.nouto,
                toimitustapa.rahtikirja,
                asiakas.kerayserat
                FROM lasku
                LEFT JOIN toimitustapa ON (lasku.yhtio = toimitustapa.yhtio and lasku.toimitustapa = toimitustapa.selite)
                LEFT JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)
                WHERE lasku.tunnus in ({$tilausnumeroita})
                and lasku.yhtio    = '{$kukarow['yhtio']}'
                and lasku.tila     in ({$tila})
                and lasku.alatila  = '{$alatilareklamaatio}'";
      $result = pupe_query($query);
      $otsik_row = mysql_fetch_assoc($result);

      echo "<tr><th>", t("Tilaus"), "</th><th>", t("Ostaja"), "</th><th>", t("Toimitusosoite"), "</th></tr>";

      $_ker_chk = ($yhtiorow['kerayserat'] == 'K' and $toim == "");
      $_rek_chk = ($toim == "VASTAANOTA_REKLAMAATIO");

      $_ker_rek = ($_ker_chk or $_rek_chk);

      if ($_ker_rek) {

        mysql_data_seek($result, 0);

        while ($otsik_row = mysql_fetch_assoc($result)) {
          echo "<tr>";
          echo "<td>";
          echo "{$otsik_row['tunnus']}<br />{$otsik_row['clearing']}";

          if ($toim == 'VASTAANOTA_REKLAMAATIO') {
            echo "<br><form action='tilaus_myynti.php' method='POST'>";
            echo "<input type='hidden' name='toim' value = 'REKLAMAATIO'>";
            echo "<input type='hidden' name='tilausnumero' value = '{$otsik_row['tunnus']}'>";
            echo "<input type='hidden' name='mista' value = 'keraa'>";
            echo "<input type='submit' value='", t("Muokkaa"), "'/> ";
            echo "</form>";
          }

          echo "</td>";
          echo "<td>{$otsik_row['nimi']} {$otsik_row['nimitark']}<br />{$otsik_row['osoite']}<br />{$otsik_row['postino']} {$otsik_row['postitp']}<br />", maa($otsik_row["maa"]), "</td>";
          echo "<td>{$otsik_row['toim_nimi']} {$otsik_row['toim_nimitark']}<br />{$otsik_row['toim_osoite']}<br />{$otsik_row['toim_postino']} {$otsik_row['toim_postitp']}<br />", maa($otsik_row["toim_maa"]), "</td>";
          echo "</tr>";
        }

        mysql_data_seek($result, 0);

        $otsik_row = mysql_fetch_assoc($result);
      }
      else {

        echo "<tr><td>".str_replace(",", ", ", $tilausnumeroita)."<br>{$otsik_row['clearing']}";

        if ($toim == 'VASTAANOTA_REKLAMAATIO') {
          echo "<br><form action='tilaus_myynti.php' method='POST'>";
          echo "<input type='hidden' name='toim' value = 'REKLAMAATIO'>";
          echo "<input type='hidden' name='tilausnumero' value = '{$tilausnumeroita}'>";
          echo "<input type='hidden' name='mista' value = 'keraa'>";
          echo "<input type='submit' value='", t("Muokkaa"), "'/> ";
          echo "</form>";
        }
        echo "</td>";
        echo "<td>{$otsik_row['nimi']} {$otsik_row['nimitark']}<br />{$otsik_row['osoite']}<br />{$otsik_row['postino']} {$otsik_row['postitp']}<br />", maa($otsik_row["maa"]), "</td>";
        echo "<td>{$otsik_row['toim_nimi']} {$otsik_row['toim_nimitark']}<br />{$otsik_row['toim_osoite']}<br />{$otsik_row['toim_postino']} {$otsik_row['toim_postitp']}<br />", maa($otsik_row["toim_maa"]), "</td></tr>";
      }
    }

    echo "</table>";

    $select_lisa   = "tilausrivi.tilkpl, tilausrivi.varattu, tilausrivi.jt,";
    $where_lisa   = "";
    $pjat_sortlisa   = "";

    if ($toim == "VALMISTUS") {
      $sorttauskentta = generoi_sorttauskentta($yhtiorow["valmistus_kerayslistan_jarjestys"]);
      $order_sorttaus = $yhtiorow["valmistus_kerayslistan_jarjestys_suunta"];

      if ($yhtiorow["valmistus_kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

      // Summataan rivit yhteen (HUOM: unohdetaan kaikki perheet!)
      if ($yhtiorow["valmistus_kerayslistan_jarjestys"] == "S") {
        $select_lisa = "sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, group_concat(tilausrivi.tunnus) rivitunnukset,";
        $where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
      }
    }
    else {
      $sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
      $order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

      if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

      // Summataan rivit yhteen (HUOM: unohdetaan kaikki perheet!)
      if ($yhtiorow["kerayslistan_jarjestys"] == "S") {
        $select_lisa = "sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, group_concat(tilausrivi.tunnus) rivitunnukset,";
        $where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
      }
    }

    $asiakas_join_lisa = "";

    // Jos keräyserät käytössä, pitää hakea asiakkaankin tiedot (myyntipuolella toim="")
    if ($yhtiorow['kerayserat'] != '' and $toim == "" and $yhtiorow['siirtolistan_tulostustapa'] != 'U') {
      $select_lisa .= "asiakas.kerayserat,";
      $asiakas_join_lisa = "JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus)";
    }

    if ($otsik_row['kerayserat'] == "H") {
      require "inc/lavakeraysparametrit.inc";

      $select_lisa .= $lavakeraysparam;
      $pjat_sortlisa = "tilausrivin_lisatiedot.alunperin_puute,lavasort,";
    }

    $query = "SELECT
              tilausrivi.tyyppi,
              tilausrivi.tuoteno,
              tilausrivi.nimitys,
              tilausrivi.tuoteno puhdas_tuoteno,
              tilausrivi.hyllyalue hyllyalue,
              tilausrivi.hyllynro hyllynro,
              tilausrivi.hyllyvali hyllyvali,
              tilausrivi.hyllytaso hyllytaso,
              concat_ws(' ',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) varastopaikka,
              concat_ws('###',tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) varastopaikka_rekla,
              tuote.ei_saldoa,
              tuote.sarjanumeroseuranta,
              tilausrivi.keratty,
              tilausrivi.tunnus,
              tilausrivi.var,
              lasku.jtkielto,
              $select_lisa
              $sorttauskentta,
              if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
              tilausrivin_lisatiedot.ohita_kerays
              FROM tilausrivi
              LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
              JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno
              JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
              $asiakas_join_lisa
              WHERE tilausrivi.yhtio     = '$kukarow[yhtio]'
              and tilausrivi.otunnus     in ($tilausnumeroita)
              and tilausrivi.var         in ('', 'H' $var_lisa)
              and tilausrivi.tyyppi      in ($tyyppi)
              and tilausrivi.kerattyaika = '0000-00-00 00:00:00'
              $where_lisa
              ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
    $result = pupe_query($query);
    $riveja = mysql_num_rows($result);

    if ($riveja > 0) {

      if ($otsik_row['kerayserat'] == "H") {
        //generoidaan rivinumerot
        $rivinumerot = array();

        $kal = 1;

        while ($rnrow = mysql_fetch_assoc($result)) {
          $rivinumerot[$rnrow["tunnus"]] = $kal;
          $kal++;
        }

        mysql_data_seek($result, 0);
      }

      $row_chk = mysql_fetch_assoc($result);
      mysql_data_seek($result, 0);

      if ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $row_chk['kerayserat'] == 'A')) {
        echo "<form name = 'pakkaukset' method='post' autocomplete='off'>";
        echo "  <input type='hidden' name='tee' value='PAKKAUKSET'>
            <input type='hidden' name='toim' value='{$toim}'>
            <input type='hidden' name='id'  value='{$id}'>
            <input type='hidden' name='kerayserat_asiakas_chk' value='{$row_chk['kerayserat']}' />";

        $query = "SELECT *
                  FROM pakkaus
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND paino   != 0
                  ORDER BY paino ASC";
        $pakkausres = pupe_query($query);

        if (mysql_num_rows($pakkausres) > 0) {
          echo "<br />";
          echo "<table><tr>";
          echo "<th>", t("Pakkaus"), "</th>";
          echo "<td><select name='pakkaukset_kaikille' onchange='submit();'>";

          echo "<option value=''>", t("Valitse pakkaus kaikille riveille"), "</option>";

          // kaikilla pitäisi olla sama pakkaus, joten pre-selectoidaan se
          $query = "SELECT pakkaus
                    FROM kerayserat
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND otunnus IN ($tilausnumeroita)";
          $ker_pak_chk_res = pupe_query($query);
          $ker_pak_chk_row = mysql_fetch_assoc($ker_pak_chk_res);

          if (!isset($pakkaukset_kaikille) and $ker_pak_chk_row['pakkaus'] != 0) $pakkaukset_kaikille = $ker_pak_chk_row['pakkaus'];

          while ($pakkausrow = mysql_fetch_assoc($pakkausres)) {

            $sel = (isset($pakkaukset_kaikille) and $pakkaukset_kaikille == $pakkausrow['tunnus']) ? " selected" : "";

            echo "<option value='{$pakkausrow['tunnus']}'{$sel}>{$pakkausrow['pakkaus']} {$pakkausrow['pakkauskuvaus']}</option>";
          }

          echo "</select></td>";
          echo "</tr></table>";
        }

        echo "</form>";
      }

      echo "<form name = 'rivit' method='post' autocomplete='off'>";
      echo "  <input type='hidden' name='tee' value='P'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='id'  value='$id'>";

      echo "<br>";
      echo "<table>";
      echo "<tr><th>".t("Kerääjä")."</th><td><input type='text' size='5' name='keraajanro'> ".t("tai")." ";
      echo "<select name='keraajalist'>";

      if ($yhtiorow['kerayserat'] == 'K' and $keraajalist == "") {

        $query = "SELECT kerayserat.laatija
                  FROM kerayserat
                  WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                  AND kerayserat.otunnus IN ({$tilausnumeroita})
                  LIMIT 1";
        $keraaja_res = pupe_query($query);
        $keraaja_row = mysql_fetch_assoc($keraaja_res);

        $keraajalist = $keraaja_row['laatija'];
      }

      $query = "SELECT *
                from kuka
                where yhtio  = '$kukarow[yhtio]'
                and extranet = ''
                and (keraajanro > 0 or kuka = '$kukarow[kuka]')";
      $kuresult = pupe_query($query);

      while ($kurow = mysql_fetch_assoc($kuresult)) {

        $selker = "";

        if ($keraajalist == "" and $kurow["kuka"] == $kukarow["kuka"]) {
          $selker = "SELECTED";
        }
        elseif ($keraajalist == $kurow["kuka"]) {
          $selker = "SELECTED";
        }

        echo "<option value='$kurow[kuka]' $selker>$kurow[nimi]</option>";
      }

      echo "</select></td></tr>";

      if ($otsik_row['pakkaamo'] > 0 and $yhtiorow['pakkaamolokerot'] != '') {
        $query = "SELECT nimi, lokero
                  FROM pakkaamo
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tunnus  = '$otsik_row[pakkaamo]'";
        $lokero_chk_res = pupe_query($query);

        if (mysql_num_rows($lokero_chk_res) > 0) {
          $lokero_chk_row = mysql_fetch_assoc($lokero_chk_res);
          echo "<tr><th>".t("Pakkaamo")."</th><td>$lokero_chk_row[nimi]</td></tr><tr><th>".t("Lokero")."</th><td>$lokero_chk_row[lokero]</td></tr>";
        }
      }

      if ($otsik_row["tulostustapa"] != "X" and $yhtiorow['karayksesta_rahtikirjasyottoon'] == 'H' and $keraysklontti === FALSE) {
        echo "<tr><th>".t("Siirry rahtikirjan syöttöön")."</th><td><input type='checkbox' name='rahtikirjalle'>".t("Kyllä")."</td></tr>";
      }

      echo "</table><br>";

      $colspanni = 4;

      $_toimtuoteno_otsikko = "";
      if ($yhtiorow['kerays_riveittain'] == 'K') {
        $_toimtuoteno_otsikko = "<th>".t("Toimittajan tuoteno")."</th>";
        $colspanni++;
      }

      echo "<table id='maintable'>
          <tr>";

      if (!empty($rivinumerot)) {
        echo "<th>#</th>";
      }

      echo "<th>".t("Paikka")."</th>
            <th>".t("Tuoteno")."</th>
            $_toimtuoteno_otsikko
            <th>".t("Nimitys")."</th>
            <th>".t("Määrä")."</th>
            <th>".t("Poikkeava määrä")."</th>";

      if ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $row_chk['kerayserat'] == 'A')) {
        echo "<th>", t("Pakkaus"), "</th>";
        $colspanni++;
      }

      if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
        echo "<th>".t("Poikkeaman käsittely")."</th>";
        $colspanni++;
      }

      if ($yhtiorow['kerays_riveittain'] == 'K') {
        echo "<th>", t("Merkkaa kerätyksi"), "</th>";
        $colspanni++;
      }

      echo "</tr>";

      $i = 0;
      $oslappkpl   = 0;
      $kerattavatrivit_count = 0;
      $total_rivi_count = mysql_num_rows($result);
      $lavanumero=1;
      $lava_referenssiluku=0;

      echo "<input type='hidden' id='total_rivi_count' value='{$total_rivi_count}' />";

      while ($row = mysql_fetch_assoc($result)) {

        if ($yhtiorow['kerays_riveittain'] == 'K') {

          $query = "SELECT *,
                    concat_ws('###',hyllyalue, hyllynro, hyllyvali, hyllytaso) hyllypaikka
                    FROM kerattavatrivit
                    WHERE tilausrivi_id = '{$row['tunnus']}'";
          $kerattavatrivitres = pupe_query($query);
          $kerattavatrivitrow = mysql_fetch_assoc($kerattavatrivitres);

          if (!empty($kerattavatrivitrow['keratty'])) $kerattavatrivit_count++;
        }

        if ($row['var'] == 'P') {
          // jos kyseessä on puuterivi
          $puute       = t("PUUTE");
          $row['varattu']  = $row['tilkpl'];
        }
        elseif ($row['var'] == 'J') {
          // jos kyseessä on JT-rivi
          $puute       = t("**JT**");

          if ($yhtiorow["varaako_jt_saldoa"] == "") {
            $row['varattu']  = $row['jt'];
          }
          else {
            $row['varattu']  = $row['jt']+$row['varattu'];
          }
        }
        elseif ($row['var']=='H') {
          // jos kyseessä on väkisinhyväksytty-rivi
          $puute       = "...........";
        }
        else {
          $puute      = '';
          $ker      = '';
        }

        $poikkeava_maara_disabled = "";

        // Verkkokaupassa etukäteen maksettu tuote!
        if ($otsik_row["mapvm"] != '' and $otsik_row["mapvm"] != '0000-00-00') {
          $row["varattu"] = $row["tilkpl"];
          $poikkeava_maara_disabled = "disabled";
          $puute .= " ".t("Verkkokaupassa etukäteen maksettu tuote!");
        }

        // Reklamaation määrät lyödään lukkoon "vastaanota reklamaatio" vaiheessa
        if ($toim == 'VASTAANOTA_REKLAMAATIO') {
          $poikkeava_maara_disabled = "disabled";
        }

        if ($otsik_row['kerayserat'] == "H") {
          if ($lava_referenssiluku >= lavakerayskapasiteetti) {
            $lavanumero++;
            $lava_referenssiluku=0;
          }

          if ($lava_referenssiluku == 0) {
            echo "<tr><th class='spec' colspan='6'>".t("Lava")." $lavanumero:</td></tr>";
          }

          $lava_referenssiluku += ($row["tilkpl"] * $row['lavakoko']);
        }

        if ($row['ei_saldoa'] != '') {
          echo "<tr class='aktiivi'>";

          if (!empty($rivinumerot)) {
            echo "<td>{$rivinumerot[$row["tunnus"]]}</td>";
          }

          echo "<td>*</td>
              <td>$row[tuoteno]</td>
              <td>$row[nimitys]</td>
              <td>$row[varattu]</td>
              <td>".t("Saldoton tuote")."</td>";

          if ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $row['kerayserat'] == 'A')) {
            echo "<td></td>";
          }

          if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
            echo "<td></td>";
          }

          echo "</tr>";

          if ((($toim == "VALMISTUS" and $yhtiorow["valmistus_kerayslistan_jarjestys"] == "S") or $yhtiorow["kerayslistan_jarjestys"] == "S") and strpos($row["rivitunnukset"], ",") !== FALSE) {
            foreach (explode(",", $row["rivitunnukset"]) as $tunn) {
              $tunn = trim($tunn);
              echo "<input type='hidden' name='kerivi[]' value='$tunn'>";
            }
          }
          else {
            echo "<input type='hidden' name='kerivi[]' value='$row[tunnus]'><input type='hidden' name='maara[$row[tunnus]]' value=''>";
          }
        }
        else {
          echo "<tr class='aktiivi'>";

          if (!empty($rivinumerot)) {
            echo "<td>{$rivinumerot[$row["tunnus"]]}</td>";
          }

          echo "<td>";

          // Voidaan vaihtaa tuotepaikka (VASTAANOTA_REKLAMAATIO ja kerayspoikkeama_kasittely == 'P')
          // tai perustaa kokonaan uusi paikka (VASTAANOTA_REKLAMAATIO)
          if ($toim == 'VASTAANOTA_REKLAMAATIO' or $yhtiorow["kerayspoikkeama_kasittely"] == 'P') {

            $s1_options = array();
            $s2_options = array();
            $s3_options = array();

            if ($toim == 'VASTAANOTA_REKLAMAATIO') {
              $vares = varaston_lapsivarastot($otsik_row['varasto'], $row['puhdas_tuoteno']);

              while ($varow = mysql_fetch_assoc($vares)) {
                $status = $varow['status'];
                ${$status."_options"}[] = $varow;
              }

              $counts = array(
                's1' => count($s1_options),
                's2' => count($s2_options),
                's3' => count($s3_options)
              );
            }

            if (!isset($reklahyllyalue[$row["tunnus"]])) $reklahyllyalue[$row["tunnus"]] = "";
            if (!isset($reklahyllynro[$row["tunnus"]]))  $reklahyllynro[$row["tunnus"]]  = "";
            if (!isset($reklahyllyvali[$row["tunnus"]])) $reklahyllyvali[$row["tunnus"]] = "";
            if (!isset($reklahyllytaso[$row["tunnus"]])) $reklahyllytaso[$row["tunnus"]] = "";

            $query = "SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso,
                      concat_ws(' ',hyllyalue, hyllynro, hyllyvali, hyllytaso) varastopaikka,
                      concat_ws('###',hyllyalue, hyllynro, hyllyvali, hyllytaso) varastopaikka_rekla,
                      concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
                      FROM tuotepaikat
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tuoteno = '$row[puhdas_tuoteno]'
                      order by oletus desc, sorttauskentta";
            $results2 = pupe_query($query);

            echo "<select name='varastorekla[$row[tunnus]]'>";

            while ($rivi = mysql_fetch_assoc($results2)) {
              $sel = '';
              if (trim($row['varastopaikka_rekla']) == trim($rivi['varastopaikka_rekla']) or
                ($yhtiorow['kerays_riveittain'] == 'K' and trim($kerattavatrivitrow['hyllypaikka']) == trim($rivi['varastopaikka_rekla']))) {
                $sel = "SELECTED";
              }
              echo "<option value='$rivi[varastopaikka_rekla]' $sel>$rivi[varastopaikka]</option>";
            }


            if ($counts['s1'] > 0) {
              echo "<optgroup label=", t("Kohdevaraston-paikat"), ">";
              foreach ($s1_options as $tp) {
                echo "<option value='", $tp['tunnus'], "'>";
                echo $tp['hyllyalue'], ' ', $tp['hyllynro'], ' ', $tp['hyllyvali'], ' ', $tp['hyllytaso'];
                echo "</option>";
              }
              echo "</optgroup>";
            }

            if ($counts['s2'] > 0) {
              echo "<optgroup label=", t("Lapsivarastojen-paikat"), ">";
              foreach ($s2_options as $tp) {
                echo "<option value='", $tp['tunnus'], "'>";
                echo $tp['hyllyalue'], ' ', $tp['hyllynro'], ' ', $tp['hyllyvali'], ' ', $tp['hyllytaso'];
                echo "</option>";
              }
              echo "</optgroup>";
            }

            if ($counts['s3'] > 0) {
              echo "<optgroup label=", t("Paikattomat-lapsivarastot"), ">";
              foreach ($s3_options as $va) {
                echo "<option value='V", $va['tunnus'], "'>";
                echo $va['nimitys'];
                echo "</option>";
              }
              echo "</optgroup>";
            }

            echo "</select><br />";

            if ($toim == 'VASTAANOTA_REKLAMAATIO') {
              echo hyllyalue("reklahyllyalue[{$row['tunnus']}]", $reklahyllyalue[$row["tunnus"]]), "
                                <input type='text' size='5' name='reklahyllynro[$row[tunnus]]'  value = '{$reklahyllynro[$row["tunnus"]]}'>
                                <input type='text' size='5' name='reklahyllyvali[$row[tunnus]]' value = '{$reklahyllyvali[$row["tunnus"]]}'>
                                <input type='text' size='5' name='reklahyllytaso[$row[tunnus]]' value = '{$reklahyllytaso[$row["tunnus"]]}'>";
            }
          }
          else {
            echo "$row[varastopaikka]";
          }

          $_toimtuoteno_rivi = "";
          if ($yhtiorow['kerays_riveittain'] == 'K') {

            // tuotteen päätoimittajan toim_tuoteno
            $tuto_query = " SELECT toim_tuoteno
                            FROM tuotteen_toimittajat
                            WHERE yhtio = '{$kukarow['yhtio']}'
                            AND tuoteno = '{$row['puhdas_tuoteno']}'
                            ORDER BY if(jarjestys=0,9999,jarjestys)
                            LIMIT 1";
            $tuto_results = pupe_query($tuto_query);
            $tuto_row = mysql_fetch_assoc($tuto_results);

            $_toimtuoteno_rivi = "<td>{$tuto_row['toim_tuoteno']}</td>";
          }

          echo "<input type='hidden' name='vertaus_hylly[$row[tunnus]]' value='$row[varastopaikka_rekla]'>";
          echo "</td>";
          echo "<td>$row[tuoteno]<input type='hidden' name='rivin_puhdas_tuoteno[$row[tunnus]]' value='$row[puhdas_tuoteno]'></td>";
          echo $_toimtuoteno_rivi;
          echo "<td>$row[nimitys]</td>";
          echo "<td class='text-right' id='{$row['tunnus']}_varattu'>".(float) $row[varattu]."<input type='hidden' name='rivin_varattu[$row[tunnus]]' value='$row[varattu]'></td>";
          echo "<td>";

          //  kaikki gruupatut tunnukset mukaan!
          if ((($toim == "VALMISTUS" and $yhtiorow["valmistus_kerayslistan_jarjestys"] == "S") or $yhtiorow["kerayslistan_jarjestys"] == "S") and strpos($row["rivitunnukset"], ",") !== FALSE) {
            foreach (explode(",", $row["rivitunnukset"]) as $tunn) {
              $tunn = trim($tunn);
              echo "<input type='hidden' name='kerivi[]' value='$tunn'>";
            }
          }
          else {
            if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
              echo "<span id='maaran_paivitys_{$row['tunnus']}'></span>";

              if ($row['ohita_kerays'] != "") {
                // ohita_kerays tuotteet ei mee keräyseriin
                $keraysera_row['kpl'] = $row["varattu"];
              }
              else {
                $query = "SELECT sum(kpl) kpl
                          FROM kerayserat
                          WHERE yhtio    = '{$kukarow['yhtio']}'
                          AND nro        = '$id'
                          AND tilausrivi = '{$row['tunnus']}'
                          ORDER BY pakkausnro ASC";
                $keraysera_res = pupe_query($query);
                $keraysera_row = mysql_fetch_assoc($keraysera_res);
              }

              // Katotaan jo tässä vaiheessa onko erässä eri määrä kuin tilausrivillä.
              // Määrä voi olla eri, koska keräyseriin menee vain kokonaislukuja ja tilausrivillä voi olla desimaalilukuja
              $erapoikkeamamaara = "";

              if ($row["varattu"] != (float) $keraysera_row['kpl']) {
                $erapoikkeamamaara = (float) $keraysera_row['kpl'];
              }

              echo "<input type='hidden' name='maara[$row[tunnus]]' id='maara_{$row['tunnus']}' value='$erapoikkeamamaara' />";
            }
            else {
              if (!isset($maara[$i])) {
                if ($yhtiorow['kerays_riveittain'] == 'K' and $kerattavatrivitrow['poikkeava_maara'] !== null) {
                  $maara[$i] = $kerattavatrivitrow['poikkeava_maara'];
                }
                else {
                  $maara[$i] = "";
                }
              }

              if ($poikkeava_maara_disabled != "") {
                echo "<input type='hidden' name='maara[$row[tunnus]]' value=''>";
              }
              else {
                echo "<input type='text' size='4' name='maara[$row[tunnus]]' value='$maara[$i]'>";
              }
              echo $puute;
            }
            echo "<input type='hidden' name='kerivi[]' value='$row[tunnus]'>";
          }

          if ($toim == 'SIIRTOTYOMAARAYS' or $toim == 'SIIRTOLISTA') {
            $tunken1 = "siirtorivitunnus";
            $tunken2 = "siirtorivitunnus";
          }
          elseif ($row["varattu"] < 0) {
            $tunken1 = "ostorivitunnus";
            $tunken2 = "myyntirivitunnus";
          }
          else {
            $tunken1 = "myyntirivitunnus";
            $tunken2 = "myyntirivitunnus";
          }

          if ($row["tyyppi"] != "W" and ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "T" or $row["sarjanumeroseuranta"] == "V")) {

            $query = "SELECT count(*) kpl, min(sarjanumero) sarjanumero
                      from sarjanumeroseuranta
                      where yhtio = '$kukarow[yhtio]'
                      and tuoteno = '$row[puhdas_tuoteno]'
                      and $tunken1 = '$row[tunnus]'";
            $sarjares = pupe_query($query);
            $sarjarow = mysql_fetch_assoc($sarjares);

            if ($sarjarow["kpl"] == abs($row["varattu"])) {
              echo " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($row["puhdas_tuoteno"])."&$tunken2=$row[tunnus]&from=KERAA&aputoim=$toim&otunnus=$id#".urlencode($sarjarow["sarjanumero"])."' class='green'>".t("S:nro OK")."</font></a>)";
            }
            else {
              echo " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($row["puhdas_tuoteno"])."&$tunken2=$row[tunnus]&from=KERAA&aputoim=$toim&otunnus=$id#".urlencode($sarjarow["sarjanumero"])."'>".t("S:nro")."</a>)";
            }
          }
          elseif (in_array($row["sarjanumeroseuranta"], array("E", "F", "G"))) {

            if ($row["sarjanumeroseuranta"] == "F") {
              $pepvmlisa1 = " sarjanumeroseuranta.parasta_ennen, ";
              $pepvmlisa2 = ", 18";
            }
            else {
              $pepvmlisa1 = "";
              $pepvmlisa2 = "";
            }

            $query = "SELECT
                      sarjanumeroseuranta.sarjanumero era,
                      tuote.ei_saldoa,
                      tuote.tuoteno,
                      tuote.vakkoodi,
                      tuote.yhtio,
                      tuotepaikat.hyllyalue,
                      tuotepaikat.hyllynro,
                      tuotepaikat.hyllytaso,
                      tuotepaikat.hyllyvali,
                      tuotepaikat.oletus,
                      varastopaikat.erikoistoimitus_alarajasumma,
                      varastopaikat.maa varastomaa,
                      varastopaikat.nimitys,
                      varastopaikat.tunnus varasto,
                      varastopaikat.tyyppi varastotyyppi,
                      concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
                      if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi,
                      $pepvmlisa1
                      group_concat(sarjanumeroseuranta.ostorivitunnus) ostorivitunnus
                       FROM tuote
                      JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                      JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                        AND varastopaikat.tunnus                = tuotepaikat.varasto)
                      JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
                      and sarjanumeroseuranta.tuoteno           = tuote.tuoteno
                      and sarjanumeroseuranta.hyllyalue         = tuotepaikat.hyllyalue
                      and sarjanumeroseuranta.hyllynro          = tuotepaikat.hyllynro
                      and sarjanumeroseuranta.hyllyvali         = tuotepaikat.hyllyvali
                      and sarjanumeroseuranta.hyllytaso         = tuotepaikat.hyllytaso
                      and sarjanumeroseuranta.myyntirivitunnus  = 0
                      and sarjanumeroseuranta.era_kpl          != 0
                      WHERE tuote.yhtio                         = '$kukarow[yhtio]'
                      and tuote.tuoteno                         = '$row[puhdas_tuoteno]'
                      GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17 $pepvmlisa2
                      ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
            $omavarastores = pupe_query($query);

            $paikat   = "<option value=''>".t("Valitse erä")."</option>";
            $selpaikka   = "";

            $query  = "SELECT sarjanumeroseuranta.sarjanumero era, sarjanumeroseuranta.parasta_ennen
                       FROM sarjanumeroseuranta
                       WHERE yhtio = '$kukarow[yhtio]'
                       and tuoteno = '$row[puhdas_tuoteno]'
                       and $tunken1 = '$row[tunnus]'
                       LIMIT 1";
            $sarjares = pupe_query($query);
            $sarjarow = mysql_fetch_assoc($sarjares);

            echo t("Erä").": ";

            while ($alkurow = mysql_fetch_assoc($omavarastores)) {
              if ($alkurow["hyllyalue"] != "!!M" and
                ($alkurow["varastotyyppi"] != "E" or
                  $laskurow["varasto"] == $alkurow["varasto"] or
                  ($alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]))) {

                if (!empty($yhtiorow["saldo_kasittely"])) {
                  $saldoaikalisa = date("Y-m-d");
                }
                else {
                  $saldoaikalisa = "";
                }

                list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["puhdas_tuoteno"], '', '', '', $alkurow["hyllyalue"], $alkurow["hyllynro"], $alkurow["hyllyvali"], $alkurow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $alkurow["era"]);

                $myytavissa = (float) $myytavissa;
                $lisa_row   = array();

                if ($alkurow["ostorivitunnus"] != "" and in_array($row["sarjanumeroseuranta"], array("E", "F", "G"))) {
                  //Jos erä on keksitty käsin täältä keräyksestä
                  $query = "SELECT tyyppi, (varattu+kpl+jt) kpl, tunnus, laskutettu
                            FROM tilausrivi
                            WHERE yhtio = '$kukarow[yhtio]'
                            and tuoteno = '$row[puhdas_tuoteno]'
                            and tunnus  in ($alkurow[ostorivitunnus])";
                  $lisa_res = pupe_query($query);
                  $lisa_row = mysql_fetch_assoc($lisa_res);
                }

                // varmistetaan, että tämä erä on käytettävissä, eli ostorivitunnus pointtaa ostoriviin, hyvitysriviin tai laskutettuun myyntiriviin tai tähän riviin itsessään
                if (($lisa_row["tyyppi"] == "O" or $lisa_row["kpl"] < 0 or $lisa_row["laskutettu"] != "" or $lisa_row["tunnus"] == $row["tunnus"]) and
                  (in_array($yhtiorow["puute_jt_oletus"], array('H', 'O')) or
                    $myytavissa >= $row["varattu"] or
                    ($row["var"] != "P"
                      and $alkurow["hyllyalue"] == $row["hyllyalue"]
                      and $alkurow["hyllynro"] == $row["hyllynro"]
                      and $alkurow["hyllyvali"] == $row["hyllyvali"]
                      and $alkurow["hyllytaso"] == $row["hyllytaso"]
                      and $sarjarow["era"] == $alkurow["era"]))) {

                  $sel = "";

                  if ($sarjarow["era"] == $alkurow["era"] and !in_array($row["var"], array("P", "S")) and $alkurow["hyllyalue"] == $row["hyllyalue"] and $alkurow["hyllynro"] == $row["hyllynro"] and $alkurow["hyllyvali"] == $row["hyllyvali"] and $alkurow["hyllytaso"] == $row["hyllytaso"]) {
                    $sel = "SELECTED";

                    $selpaikka = "$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]#$alkurow[era]";
                  }
                  elseif (isset($_POST) and $_POST["era_new_paikka"][$row["tunnus"]] == "$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]#$alkurow[era]") {
                    $sel = "SELECTED";

                    $selpaikka = "$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]#$alkurow[era]";
                  }

                  $paikat .= "<option value='$alkurow[hyllyalue]#$alkurow[hyllynro]#$alkurow[hyllyvali]#$alkurow[hyllytaso]#$alkurow[era]' $sel>";

                  if (strtoupper($alkurow['varastomaa']) != strtoupper($yhtiorow['maa'])) {
                    $paikat .= strtoupper($alkurow['varastomaa'])." ";
                  }

                  $paikat .= "$alkurow[hyllyalue] $alkurow[hyllynro] $alkurow[hyllyvali] $alkurow[hyllytaso], $alkurow[era]";
                  $paikat .= " ($myytavissa)";

                  if ($row["sarjanumeroseuranta"] == "F") {
                    $paikat .= " ".tv1dateconv($alkurow["parasta_ennen"]);
                  }

                  $paikat .= "</option>";
                }
              }
            }

            $subbari = " onchange='submit();'";

            if (($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") and $yhtiorow["kerayspoikkeama_kasittely"] != '') {
              $subbari = "";
            }

            echo "<select name='era_new_paikka[$row[tunnus]]' $subbari>".$paikat."</select>";
            echo "<input type='hidden' name='era_old_paikka[$row[tunnus]]' value='$selpaikka'>";
            echo " (<a href='sarjanumeroseuranta.php?tuoteno=".urlencode($row["puhdas_tuoteno"])."&$tunken2=$row[tunnus]&from=KERAA&aputoim=$toim&otunnus=$id#".urlencode($sarjarow["sarjanumero"])."'>".t("E:nro")."</a>)";
          }

          echo "</td>";

          if ($yhtiorow['kerayserat'] == 'P' or ($yhtiorow['kerayserat'] == 'A' and $row['kerayserat'] == 'A')) {

            $query = "SELECT *
                      FROM kerayserat
                      WHERE yhtio    = '{$kukarow['yhtio']}'
                      AND tilausrivi = '{$row['tunnus']}'";
            $keraysera_res = pupe_query($query);
            $keraysera_row = mysql_fetch_assoc($keraysera_res);

            $pakkauskirjain = chr(64+$keraysera_row['pakkausnro']);

            $oslappkpl = $yhtiorow["oletus_oslappkpl"] != 0 ? ($oslappkpl + 1) : 0;

            echo "<td><input type='text' size='4' name='keraysera_pakkaus[{$row['tunnus']}]' value='{$pakkauskirjain}' /></td>";
          }

          if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {

            $selpk_JT = $selpk_PU = $selpk_UR = $selpk_UT = $selpk_MI = '';

            echo "<td><select name='poikkeama_kasittely[$row[tunnus]]'>";

            if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
              $selpk_UR = "SELECTED";
            }
            elseif ($yhtiorow["kerayspoikkeama_kasittely"] == 'J') {

              if ($row["jtkielto"] == "o") {
                $selpk_PU = "SELECTED";
              }
              else {
                $selpk_JT = "SELECTED";
              }
            }
            elseif ($yhtiorow["kerayspoikkeama_kasittely"] == 'U') {
              $selpk_PU = "SELECTED";
            }
            else {
              echo "<option value='' SELECTED>".t("Ei käsitellä")."</option>";
            }

            // selpk_JT
            // selpk_PU
            // selpk_UR
            // selpk_UT
            // selpk_MI
            if ($yhtiorow['kerays_riveittain'] == 'K' and !empty($kerattavatrivitrow['poikkeama_kasittely'])) {
              ${'selpk_'.strtoupper($kerattavatrivitrow['poikkeama_kasittely'])} = 'selected';
            }

            echo "<option value='JT' $selpk_JT>".t("JT")."</option>";
            echo "<option value='PU' $selpk_PU>".t("Puute")."</option>";

            if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
              echo "<option value='UR' $selpk_UR>".t("Uusi rivi")."</option>";
            }

            echo "<option value='UT' {$selpk_UT}>".t("Uusi tilaus")."</option>";
            echo "<option value='MI' {$selpk_MI}>".t("Mitätöi")."</option>";
            echo "</select></td>";
          }

          if ($yhtiorow['kerays_riveittain'] == 'K') {
            echo "<td nowrap>";
            echo "<button class='kerattavatrivit' name='kerattavatrivit[{$row['tunnus']}]' value='{$row['tunnus']}'>";
            echo !empty($kerattavatrivitrow['keratty']) ? t("Päivitä") : t("Kerää");
            echo "</button>";
            echo "<div class='kerattavatrivit_info' id='kerattavatrivit_info_{$row['tunnus']}' style='display: inline; margin-left: 5px;'>";
            echo !empty($kerattavatrivitrow['keratty']) ? "<font class='ok'>OK</font>" : '';
            echo "</div>";
            echo "</td>";
          }

          echo "</tr>";

          if ($yhtiorow['kerayserat'] == 'K' and $toim == "") {
            $query = "SELECT *
                      FROM kerayserat
                      WHERE yhtio    = '{$kukarow['yhtio']}'
                      AND nro        = '$id'
                      AND tilausrivi = '{$row['tunnus']}'
                      ORDER BY pakkausnro ASC";
            $keraysera_res = pupe_query($query);

            echo "<tr><td colspan='{$colspanni}'>";

            while ($keraysera_row = mysql_fetch_assoc($keraysera_res)) {
              echo chr((64+$keraysera_row['pakkausnro'])), " <input type='text' name='keraysera_maara[{$keraysera_row['tunnus']}]' id='{$row['tunnus']}_{$keraysera_row['tunnus']}' value='".(float) $keraysera_row['kpl']."' size='4' />&nbsp;";
            }

            echo "</td></tr>";
          }
        }

        $i++;
      }

      // Jos kyseessä ei ole valmistus tulostetaan virallinen lähete
      $sel     = "SELECTED";
      $lahetekpl  = 0;

      if ($toim != 'VALMISTUS' and $otsik_row["tila"] != 'V') {
        $oslappkpl   = $oslappkpl != 0 ? $oslappkpl : $yhtiorow["oletus_oslappkpl"];
        $lahetekpl   = $yhtiorow["oletus_lahetekpl"];
        $vakadrkpl  = $yhtiorow["oletus_lahetekpl"];
      }

      // Lavakeräyksessä ei tarvita normaalia lähetettä eikä osoitelappua
      if ($otsik_row['kerayserat'] == "H") {
        $oslappkpl   = 0;
        $lahetekpl   = 0;
      }

      $spanni = 4;

      if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '') {
        $spanni = 5;
      }

      if ($yhtiorow['kerays_riveittain'] != '') {
        $spanni += 2;
      }

      if ($yhtiorow["lahete_tyyppi_tulostus"] != '') {
        $spanni += 1;
      }

      if ($toim != 'VASTAANOTA_REKLAMAATIO' and ($otsik_row['pakkaamo'] == 0 or $yhtiorow['pakkaamolokerot'] == '')) {

        //tulostetaan faili ja valitaan sopivat printterit
        if ($lp_varasto == 0) {
          $query = "SELECT *
                    from varastopaikat
                    where yhtio  = '$kukarow[yhtio]'
                    AND tyyppi  != 'P'
                    order by alkuhyllyalue,alkuhyllynro
                    limit 1";
        }
        else {
          $query = "SELECT *
                    from varastopaikat
                    where yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$lp_varasto'
                    order by alkuhyllyalue,alkuhyllynro";
        }
        $kirre = pupe_query($query);

        if (mysql_num_rows($kirre) > 0 and $yhtiorow['pakkaamolokerot'] == '') {

          $prirow = mysql_fetch_assoc($kirre);

          // käteinen muuttuja viritetään tilaus-valmis.inc:issä jos maksuehto on käteinen
          // ja silloin pitää kaikki lähetteet tulostaa aina printteri5:lle (lasku printteri)
          if ($kateinen == 'X') {
            $sel_lahete[$prirow['printteri5']] = "SELECTED";  // laskuprintteri
            $sel_oslapp[$prirow['printteri5']] = "SELECTED";  // osoitelappuprintteri
          }
          else {
            $sel_lahete[$prirow['printteri1']] = "SELECTED";  // laskuprintteri
            $sel_oslapp[$prirow['printteri3']] = "SELECTED";  // osoitelappuprintteri
          }
        }

        // Katsotaan onko avainsanoihin määritelty varaston toimipaikan läheteprintteriä
        $query = "SELECT varasto, yhtio_toimipaikka
                  FROM lasku
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  IN ({$tilausnumeroita})";
        $var_tp_res = pupe_query($query);
        $var_tp_row = mysql_fetch_assoc($var_tp_res);

        $avainsana_where = " and avainsana.selite       = '{$var_tp_row['varasto']}'
                             and avainsana.selitetark   = '{$var_tp_row['yhtio_toimipaikka']}'
                             and avainsana.selitetark_2 = 'printteri1'";

        $tp_tulostin = t_avainsana("VARTOIMTULOSTIN", '', $avainsana_where, '', '', "selitetark_3");

        if (!empty($tp_tulostin)) {
          $sel_lahete[$tp_tulostin] = "SELECTED";  // laskuprintteri
        }

        // Haetaan lähetetulostin käyttäjän takaa
        if (!empty($kukarow['lahetetulostin'])) {
          $sel_lahete = array();
          $sel_lahete[$kukarow['lahetetulostin']] = 'selected';
        }

        if (strpos($tila, 'G') !== false) {
          $lahetekpl = $yhtiorow["oletus_lahetekpl_siirtolista"];
        }

        echo "<tr><th>".t("Lähete").":</th><th colspan='$spanni'>";

        $query = "SELECT *
                  FROM kirjoittimet
                  WHERE yhtio  = '$kukarow[yhtio]'
                  AND komento != 'EDI'
                  ORDER by kirjoitin";
        $kirre = pupe_query($query);

        echo "<select name='valittu_tulostin'>";
        echo "<option value=''>".t("Ei tulosteta")."</option>";

        while ($kirrow = mysql_fetch_assoc($kirre)) {
          $sel = (isset($sel_lahete[$kirrow["tunnus"]])) ? " selected" : "";

          echo "<option value='{$kirrow['tunnus']}'{$sel}>{$kirrow['kirjoitin']}</option>";
        }

        $sel = (isset($sel_lahete["-88"])) ? " selected" : "";
        echo "<option value='-88' $sel>".t("PDF Ruudulle")."</option>";
        echo "</select> ".t("Kpl").": <input type='text' maxlength='2' size='4' name='lahetekpl' value='$lahetekpl'>";
        echo "<input type='hidden' name='valittu_uista' value='1' />";

        if ($yhtiorow["lahete_tyyppi_tulostus"] != '') {
          echo " ".t("Lähetetyyppi").": <select name='sellahetetyyppi'>";

          $lahetetyyppi = pupesoft_lahetetyyppi($id);

          $vresult = t_avainsana("LAHETETYYPPI");

          while ($row = mysql_fetch_assoc($vresult)) {
            $sel = "";
            if ($row["selite"] == $lahetetyyppi) $sel = 'selected';

            echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
          }

          echo "</select>";
        }

        echo "</th>";

        if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
          echo "<th>&nbsp;</th>";
        }

        echo "</tr>";
      }

      if ($yhtiorow["vak_erittely"] == "K" and $yhtiorow["kerayserat"] == "K" and $toim == "") {
        echo "<tr>";
        echo "<th>".t("VAK/ADR-erittely").":</th>";
        echo "<th colspan='$spanni'>";

        $query = "SELECT *
                  FROM kirjoittimet
                  WHERE yhtio  = '{$kukarow["yhtio"]}'
                  AND komento != 'EDI'
                  ORDER by kirjoitin";
        $kirre = pupe_query($query);

        echo "<select name='vakadr_tulostin'>";
        echo "<option value=''>".t("Ei tulosteta")."</option>";

        while ($kirrow = mysql_fetch_assoc($kirre)) {
          $sel = (isset($sel_lahete[$kirrow["tunnus"]])) ? " selected" : "";

          echo "<option value='{$kirrow['tunnus']}'{$sel}>{$kirrow['kirjoitin']}</option>";
        }

        echo "</select> ".t("Kpl").": <input type='text' maxlength='2' size='4' name='vakadrkpl' value='$vakadrkpl'>";
        echo "</th>";
        echo "</tr>";
      }

      if ($toim != 'VASTAANOTA_REKLAMAATIO' and $otsik_row['pakkaamo'] > 0 and $yhtiorow['pakkaamolokerot'] != '') {
        echo "<tr><th>".t("Kolli")."</th><th colspan='$spanni'><input type='text' name='pakkaamo_kolli' size='5'/></th>";

        if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
          echo "<th>&nbsp;</th>";
        }
        echo "</tr>";
      }

      echo "<tr>";

      if ($toim != 'VASTAANOTA_REKLAMAATIO' and ($yhtiorow['karayksesta_rahtikirjasyottoon'] == '' or $otsik_row["tulostustapa"] == "X" or $otsik_row["nouto"] != "") and ($otsik_row['pakkaamo'] == 0 or $yhtiorow['pakkaamolokerot'] == '')) {
        echo "<th>".t("Osoitelappu").":</th>";

        echo "<th colspan='$spanni'>";

        mysql_data_seek($kirre, 0);

        echo "<select name='valittu_oslapp_tulostin'>";
        echo "<option value=''>".t("Ei tulosteta")."</option>";

        while ($kirrow = mysql_fetch_assoc($kirre)) {
          $sel = (isset($sel_oslapp[$kirrow["tunnus"]])) ? " selected" : "";

          echo "<option value='$kirrow[tunnus]'{$sel}>$kirrow[kirjoitin]</option>";
        }

        $sel = (isset($sel_oslapp["-88"])) ? " selected" : "";
        echo "<option value='-88' $sel>".t("PDF Ruudulle")."</option>";
        echo "</select> ".t("Kpl").": ";

        $oslappkpl_hidden = 0;
        $disabled = '';

        // jos unifaun + hetitulostus tai erätulostus
        // --> ei tulosteta osoitelappuja Pupessa
        $_ei_koonti = ($otsik_row['tulostustapa'] == 'H' or $otsik_row['tulostustapa'] == 'E');
        $_onko_unifaun = preg_match("/rahtikirja_unifaun_(ps|uo|xp)_siirto\.inc/", $otsik_row["rahtikirja"]);

        if (!empty($oslappkpl) and $_onko_unifaun and $_ei_koonti) {
          $yhtiorow["oletus_oslappkpl"] = 0;
          $oslappkpl = 0;
        }

        if ($yhtiorow["oletus_oslappkpl"] != 0 and ($yhtiorow['kerayserat'] == 'P' or $yhtiorow['kerayserat'] == 'A')) {

          $kaikki_ok = true;

          if ($yhtiorow['kerayserat'] == 'A') {
            $query = "SELECT kerayserat
                      FROM asiakas
                      WHERE yhtio    = '{$kukarow['yhtio']}'
                      AND tunnus     = '{$otsik_row['liitostunnus']}'
                      AND kerayserat = 'A'";
            $asiakas_chk_res = pupe_query($query);

            if (mysql_num_rows($asiakas_chk_res) == 0) $kaikki_ok = false;
          }

          if ($kaikki_ok) {
            $oslappkpl_hidden = 1;
            $oslappkpl = '';
            $disabled = 'disabled';
          }
        }

        echo "<input type='text' maxlength='2' size='4' name='oslappkpl' value='$oslappkpl' {$disabled}>";

        if ($oslappkpl_hidden != 0) {
          echo "<input type='hidden' name='oslappkpl' value='{$oslappkpl_hidden}' />";
        }

        echo "</th>";

        if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
          echo "<th></th>";
        }
        echo "</tr>";
      }

      if ($otsik_row['pakkaamo'] > 0 and $yhtiorow['pakkaamolokerot'] != '') {
        echo "<th>".t("Rullakko")."</th><th colspan='$spanni'><input type='text' name='pakkaamo_rullakko' size='5'/></th>";

        if ($yhtiorow["kerayspoikkeama_kasittely"] != '') {
          echo "<th></th>";
        }
        echo "</tr>";
      }

      if ($otsik_row['kerayserat'] == "H") {
        echo "<th>".t("Lavatarra").":</th>";

        echo "<th colspan='$spanni'>";

        mysql_data_seek($kirre, 0);

        echo "<select name='valittu_lavatarra_tulostin'>";
        echo "<option value=''>".t("Ei tulosteta")."</option>";

        while ($kirrow = mysql_fetch_assoc($kirre)) {
          $sel = (isset($sel_oslapp[$kirrow["tunnus"]])) ? " selected" : "";

          echo "<option value='$kirrow[tunnus]'{$sel}>$kirrow[kirjoitin]</option>";
        }

        echo "</select>";
        echo "</th>";
        echo "</tr>";
      }

      echo "</table><br>";

      echo "<input type='hidden' name='tilausnumeroita' id='tilausnumeroita' value='$tilausnumeroita'>";
      echo "<input type='hidden' name='lasku_yhtio' value='$otsik_row[yhtio]'>";

      if ($yhtiorow['kerays_riveittain'] == '' or $kerattavatrivit_count == $total_rivi_count) {
        $hidden = "";
      }
      else {
        $hidden = "style='display:none;'";
      }

      if ($toim == 'VASTAANOTA_REKLAMAATIO') {
        echo "<input type='submit' name='real_submit' id='real_submit' value='".t("Tuotteet hyllytetty ja reklamaatio valmis laskutukseen")."'>";
      }
      elseif ($otsik_row["tulostustapa"] != "X" or $otsik_row["nouto"] != "") {
        echo "<input type='submit' name='real_submit' id='real_submit' value='".t("Merkkaa kerätyksi")."' {$hidden}>";
      }
      else {
        echo "<input type='submit' name='real_submit' id='real_submit' value='".t("Merkkaa toimitetuksi")."' {$hidden}>";
      }

      echo "</form>";

      if ($otsik_row["tulostustapa"] != "X" and $otsik_row['nouto'] == '' and $yhtiorow['karayksesta_rahtikirjasyottoon'] == 'Y') {
        echo "<br><br><font class='message'>".t("Siirryt automaattisesti rahtikirjan syöttöön")."!</font>";
      }
      elseif ($otsik_row["tulostustapa"] != "X" and $yhtiorow['karayksesta_rahtikirjasyottoon'] == 'H' and $keraysklontti === FALSE) {
        echo "<br><br><font class='message'>".t("Voit halutessasi siirtyä rahtikirjan syöttöön")."!</font>";
      }
    }
    else {
      echo t("Tällä tilauksella ei ole yhtään kerättävää riviä!");
    }
  }

  if (isset($rahtikirjaan) and $rahtikirjaan == 'mennaan') {
    if ($valittu_tulostin == "-88" or $valittu_oslapp_tulostin == "-88") {
      // Jos ollaan valittu PDF ruudulle, ja pysähdytään näyttämään tulostusnapit,
      // piirretään samalla napit verkkokaupan liite -liitetiedostojen näyttöä varten.
      $liitetiedostot = tilauksen_liitetiedostot($tilausnumeroita, 'VK');

      foreach ($liitetiedostot as $key => $tunnus) {
        $key++;
        $submit_value = t('Liite') . ": {$key}";

        echo "<form target='_blank' class='multisubmit' method='get' action='{$palvelin2}view.php'>";
        echo "<input type='hidden' name='id' value='{$tunnus}'>";
        echo "<input type='submit' value='{$submit_value}'>";
        echo "</form>";

        echo "<br><br>";
      }

      echo "<a href={$palvelin2}rahtikirja.php?toim=lisaa&id=$id&rakirno=$id&tunnukset=$tilausnumeroita&mista=keraa.php'>".t("Siirry rahtikirjan syöttöön")."</a>";
    }
    else {
      echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL={$palvelin2}rahtikirja.php?toim=lisaa&id=$id&rakirno=$id&tunnukset=$tilausnumeroita&mista=keraa.php'>";
    }
  }

  require "inc/footer.inc";
}
