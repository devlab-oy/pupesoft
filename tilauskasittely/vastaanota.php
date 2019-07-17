<?php

if (!isset($echotaanko) or (isset($echotaanko) and $echotaanko) ) {
  require "../inc/parametrit.inc";
}

if (!isset($tee))           $tee  = "";
if (!isset($toim))          $toim = "";
if (!isset($etsi))          $etsi = "";
if (!isset($id))            $id   = 0;
if (!isset($boob))          $boob = "";
if (!isset($maa))           $maa  = "";
if (!isset($varastorajaus)) $varastorajaus = 0;
if (!isset($echotaanko))    $echotaanko = true;

if ($tee == 'yhdista') {

  if (!empty($yhdistettavat_siirtolistat)) {
    $query = "LOCK TABLES avainsana WRITE";
    pupe_query($query);

    $query = "SELECT selite
              FROM avainsana
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laji    = 'SIIRTO_VASTNRO'";
    $result = pupe_query($query);
    $row = mysql_fetch_assoc($result);

    $vastaanottonro = is_numeric($row['selite']) ? (int) $row['selite'] + 1 : 1;

    if (trim($row['selite']) == '') {

      $query = "INSERT INTO avainsana SET
                yhtio        = '{$kukarow['yhtio']}',
                perhe        = '666',
                kieli        = '{$kukarow['kieli']}',
                laji         = 'SIIRTO_VASTNRO',
                nakyvyys     = '',
                selite       = '{$vastaanottonro}',
                selitetark   = '',
                selitetark_2 = '',
                selitetark_3 = '',
                jarjestys    = 0,
                laatija      = '{$kukarow['kuka']}',
                luontiaika   = now(),
                muutospvm    = now(),
                muuttaja     = '{$kukarow['kuka']}'";
      pupe_query($query);
    }
    else {
      $query = "UPDATE avainsana
                SET selite  = '{$vastaanottonro}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND laji    = 'SIIRTO_VASTNRO'";
      pupe_query($query);
    }

    // poistetaan lukko
    $query = "UNLOCK TABLES";
    pupe_query($query);

    $yhdistettavat_siirtolistat = mysql_real_escape_string($yhdistettavat_siirtolistat);

    $query = "UPDATE lasku SET
              siirtolistan_vastaanotto = '{$vastaanottonro}'
              WHERE yhtio              = '{$kukarow['yhtio']}'
              AND tunnus               IN ({$yhdistettavat_siirtolistat})";
    pupe_query($query);
  }

  $tee = '';
}

if ($echotaanko) {
  echo "  <script type='text/javascript'>
      $(function() {

        $('#siirtotable').on('click', '#yhdista_kaikki', function() {

          if ($('input.siirtolistan_vastaanotto:checked').length > 0) {
            $('input.siirtolistan_vastaanotto').each(function() {
              $(this).prop('checked', false);
            });
          }
          else {
            $('input.siirtolistan_vastaanotto').each(function() {
              $(this).prop('checked', true);
            });
          }
        });

        $('#siirtotable').on('click', '#yhdistabutton', function(e) {

          e.preventDefault();

          if ($('.siirtolistan_vastaanotto:checked').length > 1) {

            nrot = [];

            $('.siirtolistan_vastaanotto:checked').each(function() {
              nrot.push($(this).val());
            });

            $('#yhdistettavat_siirtolistat').val(nrot.join());
            $('#yhdistaformi').submit();
          }
          else {
            alert('", t("Valitse vähintään 2 siirtolistaa"), "');
          }
        });
      });
    </script>";

  if ($toim == "MYYNTITILI") {
    echo "<font class='head'>".t("Toimita myyntitili asiakkaalle").":</font><hr>";
  }
  elseif ($toim == "MYYNTITILIVASTAANOTA") {
    echo "<font class='head'>".t("Vastaanota myyntitili asiakkaalta").":</font><hr>";
  }
  else {
    echo "<font class='head'>".t("Vastaanota siirtolista").":</font><hr>";
  }
}

if ($tee == 'kommentista') {
  if (!empty($id)) {

    $query = "SELECT tunnus, kommentti
              from tilausrivi
              where tyyppi='G'
              and otunnus IN ($id)
              and yhtio   = '$kukarow[yhtio]'";
    $alkuresult = pupe_query($query);

    while ($alkurow = mysql_fetch_assoc($alkuresult)) {

      $bpaikka = explode(' ', $alkurow["kommentti"]);

      $ka = count($bpaikka)-1;

      $ok = 1;

      if (!is_numeric($bpaikka[$ka]))   $ok = 0;
      if (!is_numeric($bpaikka[$ka-1]))   $ok = 0;
      if (!is_numeric($bpaikka[$ka-2]))   $ok = 0;


      if ($ok == 1) {
        $tunnus[] = $alkurow["tunnus"];
        $t1[$alkurow["tunnus"]] = $bpaikka[$ka-3];
        $t2[$alkurow["tunnus"]] = $bpaikka[$ka-2];
        $t3[$alkurow["tunnus"]] = $bpaikka[$ka-1];
        $t4[$alkurow["tunnus"]] = $bpaikka[$ka];
      }
      else {
        $tunnus[] = $alkurow["tunnus"];
        $t1[$alkurow["tunnus"]] = $bpaikka[$ka-2];
        $t2[$alkurow["tunnus"]] = $bpaikka[$ka-1];
        $t3[$alkurow["tunnus"]] = $bpaikka[$ka];
        $t4[$alkurow["tunnus"]] = '0';
      }

    }
  }

}

if ($tee == 'mikrotila') {

  echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='id' value='$id'>";
  echo "<input type='hidden' name='id_talteen' value='$id_talteen'>";
  echo "<input type='hidden' name='varastorajaus' value='$varastorajaus'>";
  echo "<input type='hidden' name='maa' value='$maa'>";
  echo "<input type='hidden' name='varasto' value='$row[clearing]'>";
  echo "<input type='hidden' name='tee' value='failista'>";

  echo "  <font class='message'>".t("Tiedostomuoto").":</font><br><br>

      <table>
      <tr><th colspan='5'>".t("Tabulaattorilla eroteltu tekstitiedosto").".</th></tr>
      <tr><td>".t("Tuoteno")."</td><td>".t("Määrä")."</td><td>".t("Kommentti")."</td><td>".t("Lähettävä varastopaikka")."</td><td>".t("Vastaanottava varastopaikka")."</td></tr>
      </table>
      <br>
      <table>
      <tr>";

  echo "
      <th>".t("Valitse tiedosto").":</th>
      <td><input name='userfile' type='file'></td>
      <td class='back'><input type='submit' value='".t("Läheta")."'></td>
      </tr>
      </table>
      </form>";
  exit;
}

if ($tee == 'failista') {
  if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
    $timeparts = explode(" ", microtime());
    $starttime = $timeparts[1].substr($timeparts[0], 1);

    $path_parts = pathinfo($_FILES['userfile']['name']);
    $name  = strtoupper($path_parts['filename']);
    $ext  = strtoupper($path_parts['extension']);

    if ($ext != "TXT" and $ext != "CSV") {
      die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
    }

    if ($_FILES['userfile']['size']==0) {
      die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
    }

    $file=fopen($_FILES['userfile']['tmp_name'], "r") or die (t("Tiedoston avaus epäonnistui")."!");

    // luetaan tiedosto alusta loppuun...
    $rivi = fgets($file, 4096);

    while (!feof($file)) {

      $tuoteno    = '';
      $varattu    = '';
      $teksti     = '';
      $avarasto   = '';
      $bvarasto  = '';

      // luetaan rivi tiedostosta..
      $rivi = explode("\t", pupesoft_cleanstring($rivi));

      $tuoteno  = $rivi[0];
      $varattu  = $rivi[1];
      $teksti   = $rivi[2];
      $avarasto = $rivi[3];
      $bvarasto = $rivi[4];

      if ($bvarasto!='' and $avarasto!='' and $tuoteno!='') {

        $paikka = explode('#', $avarasto);

        $query = "SELECT *
                  from tilausrivi
                  where tyyppi='G'
                  and otunnus   IN ($id)
                  and  tuoteno  = '$tuoteno'
                  and yhtio     = '$kukarow[yhtio]'
                  and hyllyalue = '$paikka[0]'
                  and hyllynro  = '$paikka[1]'
                  and hyllyvali = '$paikka[2]'
                  and hyllytaso = '$paikka[3]'";
        $alkuresult = pupe_query($query);

        if (mysql_num_rows($alkuresult) == 1) {
          $alkurow = mysql_fetch_assoc($alkuresult);

          $bpaikka = explode('#', $bvarasto);

          $tunnus[] = $alkurow["tunnus"];
          $t1[$alkurow["tunnus"]] = $bpaikka[0];
          $t2[$alkurow["tunnus"]] = $bpaikka[1];
          $t3[$alkurow["tunnus"]] = $bpaikka[2];
          $t4[$alkurow["tunnus"]] = $bpaikka[3];

        }
      }

      $rivi = fgets($file, 4096);
    }
  }
}

if ($tee == 'paikat') {

  $virheita = 0;

  //käydään kaikki rivit läpi ja tarkastetaan varastopaika ja perustetaan uusia jos on tarvis
  foreach ($tunnus as $tun) {

    $t1[$tun] = trim($t1[$tun]);
    $t2[$tun] = trim($t2[$tun]);
    $t3[$tun] = trim($t3[$tun]);
    $t4[$tun] = trim($t4[$tun]);

    $t1[$tun] = strtoupper($t1[$tun]);

    $query = "SELECT tilausrivi.tuoteno, tuote.ei_saldoa, tilausrivi.tunnus, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso
              FROM tilausrivi
              JOIN tuote on tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
              WHERE tilausrivi.tunnus   = '$tun'
              and tilausrivi.yhtio      = '$kukarow[yhtio]'
              and tilausrivi.tyyppi     = 'G'
              and tilausrivi.toimitettu = ''";
    $result = pupe_query($query);
    $tilausrivirow = mysql_fetch_assoc($result);

    if (mysql_num_rows($result) != 1) {
      if ($echotaanko) {
        echo "<font class='error'>".t("VIRHE: Riviä ei löydy tai se on jo siirretty uudelle paikalle")."!</font><br>";
      }
    }
    elseif ($tilausrivirow["ei_saldoa"] == "") {

      // Jaahas mitäs tuotepaikalle pitäisi tehdä
      if (isset($rivivarasto[$tun]) and $rivivarasto[$tun] != 'x' and $rivivarasto[$tun] != '') {
        // Varastopaikka vaihdettiin pop-upista, siellä on paikan tunnus
        // tehdään uusi paikka jos valittiin paikaton lapsivarasto
        if (substr($rivivarasto[$tun], 0, 1) == 'V') {
          $uusi_paikka = lisaa_tuotepaikka($tilausrivirow["tuoteno"], '', '', '', '', '', '', 0, 0, substr($rivivarasto[$tun], 1));
          $ptunnus = $uusi_paikka['tuotepaikan_tunnus'];
        }
        else {
          $ptunnus = $rivivarasto[$tun];
        }

        $query = "SELECT tuotepaikat.*, inventointilistarivi.tunnus as inventointilistatunnus
                  from tuotepaikat
                  LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                    AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                    AND inventointilistarivi.tila              = 'A')
                  WHERE tuotepaikat.yhtio                      = '{$kukarow['yhtio']}'
                  and tuotepaikat.tunnus                       = '{$ptunnus}'
                  and tuotepaikat.tuoteno                      = '{$tilausrivirow['tuoteno']}'";
      }
      else {
        $query = "SELECT tuotepaikat.*, inventointilistarivi.tunnus as inventointilistatunnus
                  from tuotepaikat
                  LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
                    AND inventointilistarivi.tuotepaikkatunnus = tuotepaikat.tunnus
                    AND inventointilistarivi.tila              = 'A')
                  WHERE tuotepaikat.yhtio                      = '{$kukarow['yhtio']}'
                  and tuotepaikat.hyllyalue                    = '{$t1[$tun]}'
                  and tuotepaikat.hyllynro                     = '{$t2[$tun]}'
                  and tuotepaikat.hyllyvali                    = '{$t3[$tun]}'
                  and tuotepaikat.hyllytaso                    = '{$t4[$tun]}'
                  and tuotepaikat.tuoteno                      = '{$tilausrivirow['tuoteno']}'";
      }

      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {

        // Tämä on uusi kokonaan varastopaikka tälle tuotteelle, joten perustetaan se
        // Jos tuotteen eka paikka, se on oletuspaikka

        $query  = "SELECT tunnus
                   FROM varastopaikat
                   WHERE yhtio = '$kukarow[yhtio]'
                   AND tunnus  = '$varasto'
                   AND concat(rpad(upper(alkuhyllyalue), 5, '0'),lpad(upper(alkuhyllynro), 5, '0')) <= concat(rpad(upper('$t1[$tun]'), 5, '0'),lpad(upper('$t2[$tun]'), 5, '0'))
                   AND concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper('$t1[$tun]'), 5, '0'),lpad(upper('$t2[$tun]'), 5, '0'))";
        $vares = pupe_query($query);

        if (mysql_num_rows($vares) == 1) {

          $query = "SELECT tuoteno
                    FROM tuotepaikat
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tuoteno = '$tilausrivirow[tuoteno]'";
          $aresult = pupe_query($query);

          if (mysql_num_rows($aresult) == 0) {
            $oletus = 'X';
            echo t("oletus")." ";
          }
          else {
            $oletus = '';
          }

          if ($t1[$tun] != '' and $t2[$tun] != '' and $t3[$tun] != '' and $t4[$tun] != '') {
            $lisatty_paikka = lisaa_tuotepaikka($tilausrivirow["tuoteno"], $t1[$tun], $t2[$tun], $t3[$tun], $t4[$tun], "Varastosiirron vastaanotossa", $oletus, 0, 0, 0);

            if ($echotaanko) {
              if ($toim == "MYYNTITILI") {
                echo "<font class='message'>".t("Tuote")." $tilausrivirow[tuoteno] ".t("siirretty myyntitiliin").".</font><br>";
              }
              else {
                echo "<font class='message'>".t("Perustan")." ".t("Tuotenumerolle")." $tilausrivirow[tuoteno] ".t("perustetaan uusi paikka")." $t1[$tun]-$t2[$tun]-$t3[$tun]-$t4[$tun]</font><br>";
              }
            }

          }
          else {
            if ($echotaanko) {
              echo "<font class='error'>".t("VIRHE: Tuotenumerolle")." $tilausrivirow[tuoteno]. ".t("ei voitu perustaa tyhjää varastopaikkaa")."!</font><br>";
            }
            $virheita++;
          }
        }
        else {
          if ($echotaanko) {
            echo "<font class='error'>".t("VIRHE: Syöttämäsi varastopaikka ei kuulu kohdevaraston alueeseen")."!</font><br>";
          }

          $t1[$tun] = '';
          $t2[$tun] = '';
          $t3[$tun] = '';
          $t4[$tun] = '';

          $virheita++;
        }
      }
      else {
        $paikkarow = mysql_fetch_assoc($result);

        if ($paikkarow["inventointilistatunnus"] !== null) {
          if ($echotaanko) {
            echo "<font class='error'>$paikkarow[hyllyalue]-$paikkarow[hyllynro]-$paikkarow[hyllyvali]-$paikkarow[hyllytaso] ".t("VIRHE: Kohdepaikalla on inventointi kesken, ei voida jatkaa")."!</font><br>";
          }
          $virheita++;
        }

        $t1[$tun] = $paikkarow['hyllyalue'];
        $t2[$tun] = $paikkarow['hyllynro'];
        $t3[$tun] = $paikkarow['hyllyvali'];
        $t4[$tun] = $paikkarow['hyllytaso'];
      }

      if ($virheita === 0) {

        // Päivitetään syötetyt paikat tilausrivin_lisätietoihin
        $query = "UPDATE tilausrivi
                  JOIN tilausrivin_lisatiedot on (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio  and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
                  SET
                  tilausrivin_lisatiedot.kohde_hyllyalue = '{$t1[$tun]}',
                  tilausrivin_lisatiedot.kohde_hyllynro  = '{$t2[$tun]}',
                  tilausrivin_lisatiedot.kohde_hyllyvali = '{$t3[$tun]}',
                  tilausrivin_lisatiedot.kohde_hyllytaso = '{$t4[$tun]}'
                  WHERE tilausrivi.tunnus                = '$tun'
                  and tilausrivi.yhtio                   = '$kukarow[yhtio]'
                  and tilausrivi.tyyppi                  = 'G'
                  and tilausrivi.toimitettu              = ''";
        $result = pupe_query($query);
      }
    }

    if (isset($eankoodi[$tun]) and $eankoodi[$tun] != '') {
      $query = "UPDATE tuote
                SET eankoodi = '$eankoodi[$tun]',
                muuttaja      = '$kukarow[kuka]',
                muutospvm     = now()
                WHERE yhtio   = '$kukarow[yhtio]'
                AND tuoteno   = '$tilausrivirow[tuoteno]'
                AND eankoodi != '$eankoodi[$tun]'";
      pupe_query($query);
    }

    //haetaan antavan varastopaikan tunnus
    $query = "SELECT inventointilistarivi.tunnus
              FROM inventointilistarivi
              WHERE inventointilistarivi.yhtio   = '{$kukarow['yhtio']}'
              and inventointilistarivi.hyllyalue = '{$tilausrivirow['hyllyalue']}'
              and inventointilistarivi.hyllynro  = '{$tilausrivirow['hyllynro']}'
              and inventointilistarivi.hyllyvali = '{$tilausrivirow['hyllyvali']}'
              and inventointilistarivi.hyllytaso = '{$tilausrivirow['hyllytaso']}'
              and inventointilistarivi.tuoteno   = '{$tilausrivirow['tuoteno']}'
              AND inventointilistarivi.tila      = 'A'";
    $presult = pupe_query($query);
    $prow = mysql_fetch_assoc($presult);

    if ($prow["tunnus"] !== null) {
      if ($echotaanko) {
        echo "<font class='error'>$tilausrivirow[hyllyalue]-$tilausrivirow[hyllynro]-$tilausrivirow[hyllyvali]-$tilausrivirow[hyllytaso] ".t("VIRHE: Lähdepaikalla on inventointi kesken, ei voida jatkaa")."!</font><br>";
      }
      $virheita++;
    }
  }

  if ($virheita == 0 and $vainlistaus == '') {
    $tee = 'valmis';
  }
  elseif ($vainlistaus == '') {
    $tee = '';
  }

  if ($echotaanko) {
    echo "<br><br>";
  }
}

if ($tee == 'valmis') {

  $virheita = 0;

  // Tehdään lukko, jotta vain yksi ajo kerrallaan
  $lock_params = array(
    "return"   => true,
  );

  if (!pupesoft_flock($lock_params)) {
    echo "Ruuhkaa varastosiirtojen vastaanotossa, yritä myöhemmin uudelleen!";
    exit();
  }

  //käydään kaikki riviti läpi ja siirretään saldoja
  foreach ($tunnus as $tun) {

    //nollataan nämä tärkeät
    $tuoteno  = '';
    $mista    = '';
    $minne    = '';
    $asaldo    = '';

    $t1[$tun]=strtoupper($t1[$tun]);

    $query = "SELECT tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso, tilausrivi.varattu, tuote.ei_saldoa, tuote.sarjanumeroseuranta
              FROM tilausrivi
              JOIN tuote on tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
              WHERE tilausrivi.tunnus   = '$tun'
              and tilausrivi.yhtio      = '$kukarow[yhtio]'
              and tilausrivi.tyyppi     = 'G'
              and tilausrivi.toimitettu = ''";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 1) {
      if ($echotaanko) {
        echo "<font class='error'>".t("VIRHE: Riviä ei löydy tai se on jo siirretty uudelle paikalle")."!</font><br>";
      }
    }
    else {
      $tilausrivirow = mysql_fetch_assoc($result);

      $tuoteno = $tilausrivirow["tuoteno"];
      $asaldo  = $tilausrivirow["varattu"];
      $tee    = "";

      if ($asaldo != 0 and $tilausrivirow["ei_saldoa"] == "") {

        $tkpl = $asaldo;
        //haetaan antavan varastopaikan tunnus
        $query = "SELECT tunnus
                  FROM tuotepaikat
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and hyllyalue = '$tilausrivirow[hyllyalue]'
                  and hyllynro  = '$tilausrivirow[hyllynro]'
                  and hyllyvali = '$tilausrivirow[hyllyvali]'
                  and hyllytaso = '$tilausrivirow[hyllytaso]'
                  and tuoteno   = '$tilausrivirow[tuoteno]'";
        $presult = pupe_query($query);

        if (mysql_num_rows($presult) == 0) {
          if ($echotaanko) {
            echo "<font style='error'>".t("VIRHE: Antavaa varastopaikkaa ei löydy")."$tuoteno!</font>";
            exit;
          }
          else {
            // jos antavaa paikka ei löydy, niin lisätään se jos ajetan komentoriviltä
            echo "<font style='error'>".t("VIRHE: Antavaa varastopaikkaa ei löydy")."$tuoteno!</font>";
            $lisatty_paikka = lisaa_tuotepaikka($tilausrivirow["tuoteno"], $tilausrivirow["hyllyalue"], $tilausrivirow["hyllynro"], $tilausrivirow["hyllyvali"], $tilausrivirow["hyllytaso"], 'Varastosiirron vastaanotossa, koska lähdepaikka oli kateissa', '', 0, 0, 0);
            $mista = $lisatty_paikka["tuotepaikan_tunnus"];
          }
        }
        else {
          $prow = mysql_fetch_assoc($presult);
          $mista = $prow["tunnus"];
        }

        //haetaan vastaanottavan varastopaikan tunnus
        $query = "SELECT tunnus
                  FROM tuotepaikat
                  WHERE yhtio   = '$kukarow[yhtio]'
                  and hyllyalue = '$t1[$tun]'
                  and hyllynro  = '$t2[$tun]'
                  and hyllyvali = '$t3[$tun]'
                  and hyllytaso = '$t4[$tun]'
                  and tuoteno   = '$tilausrivirow[tuoteno]'";
        $presult = pupe_query($query);

        if (mysql_num_rows($presult) != 1) {
          if ($echotaanko) {
            echo "<font style='error'>".t("VIRHE: Vastaanottavaa varastopaikkaa ei löydy")."$tuoteno!</font>";
          }
        }
        else {
          $prow = mysql_fetch_assoc($presult);

          $minne = $prow["tunnus"];
          $uusiol = $prow["tunnus"];
        }

        //laitetaan sarjanumerot kuntoon
        if ($tilausrivirow["sarjanumeroseuranta"] != "") {
          $query = "SELECT tunnus, era_kpl, sarjanumero
                    FROM sarjanumeroseuranta
                    WHERE siirtorivitunnus = '$tun'
                    and yhtio              = '$kukarow[yhtio]'";
          $sarjares = pupe_query($query);

          $sarjano_array = array();

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            if ($tilausrivirow["sarjanumeroseuranta"] == "E" or $tilausrivirow["sarjanumeroseuranta"] == "F" or $tilausrivirow["sarjanumeroseuranta"] == "G") {
              // eränumeroseurannassa pitää etsiä ostotunnuksella erä josta kappaleeet otetaan

              // koitetaan löytää vapaita ostettuja eriä mitä myydä
              $query =   "SELECT era_kpl, tunnus, ostorivitunnus
                          FROM sarjanumeroseuranta
                          WHERE yhtio          = '$kukarow[yhtio]'
                          and tuoteno          = '$tilausrivirow[tuoteno]'
                          and ostorivitunnus   > 0
                          and myyntirivitunnus = 0
                          and sarjanumero      = '$sarjarow[sarjanumero]'
                          and era_kpl          > 0
                          and hyllyalue        = '$tilausrivirow[hyllyalue]'
                          and hyllynro         = '$tilausrivirow[hyllynro]'
                          and hyllyvali        = '$tilausrivirow[hyllyvali]'
                          and hyllytaso        = '$tilausrivirow[hyllytaso]'
                          ORDER BY era_kpl DESC, tunnus
                          LIMIT 1";
              $erajaljella_res = pupe_query($query);

              // jos löytyy ostettuja eriä myytäväks niin mennään tänne
              if (mysql_num_rows($erajaljella_res) == 1) {
                $erajaljella_row = mysql_fetch_assoc($erajaljella_res);



                $sarjano_array[] = $erajaljella_row["tunnus"];
                $sarjano_kpl_array[$erajaljella_row["tunnus"]] = $erajaljella_row["era_kpl"];
              }
            }
            else {
              $sarjano_array[] = $sarjarow["tunnus"];
            }
          }
        }

        // muuvarastopaikka.php palauttaa tee=X jos törmättiin johonkin virheeseen
        $tee = "N";
        $kutsuja = "vastaanota.php";

        require "muuvarastopaikka.php";

        if (isset($eancheck[$tun]) and $eancheck[$tun] != '' and (int) $kirjoitin > 0) {
          $query = "SELECT komento from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus = '$kirjoitin'";
          $komres = pupe_query($query);
          $komrow = mysql_fetch_assoc($komres);

          $komento = $komrow['komento'];

          for ($a = 0; $a < $tkpl; $a++) {
            require "inc/tulosta_tuotetarrat_tec.inc";
          }
        }

        if ($tee != 'X') {
          if (isset($oletuspaiv) and $oletuspaiv != '') {
            if ($echotaanko) {
              echo "<font class='message'>".t("Siirretään oletuspaikka")."</font><br><br>";
            }

            $query = "UPDATE tuotepaikat
                      SET oletus   = '',
                      muuttaja      = '$kukarow[kuka]',
                      muutospvm     = now()
                      WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]'";
            pupe_query($query);

            $query = "UPDATE tuotepaikat
                      SET oletus = 'X',
                      muuttaja      = '$kukarow[kuka]',
                      muutospvm     = now()
                      WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]' and tunnus='$uusiol'";
            pupe_query($query);
          }
        }
      }

      if ($tee != 'X') {

        if ($_poikkeavalaskutuspvm != '') {
          $_laadittu = $_poikkeavalaskutuspvm." 23:59:59";
          $_tapvm = $_poikkeavalaskutuspvm;
        }
        else {
          $_laadittu = date("Y-m-d H:i:s");
          $_tapvm = date("Y-m-d");
        }

        // jos kaikki meni ok niin päivitetään rivi vastaanotetuksi, laitetaan rivihinnaks tuotteen myyntihinat (tätä käytetään sit intrastatissa jos on tarve)
        $query = "UPDATE tilausrivi, tuote
                  SET tilausrivi.toimitettu  = '$kukarow[kuka]',
                  toimitettuaika          = '$_laadittu',
                  kpl                     = varattu,
                  varattu                 = 0,
                  rivihinta               = round(tilausrivi.kpl * tuote.myyntihinta / if('$yhtiorow[alv_kasittely]' = '', (1+tuote.alv/100), 1), '$yhtiorow[hintapyoristys]')
                  WHERE tilausrivi.tunnus = '$tun'
                  and tilausrivi.yhtio    = '$kukarow[yhtio]'
                  and tilausrivi.tyyppi   = 'G'
                  and tuote.yhtio         = tilausrivi.yhtio
                  and tuote.tuoteno       = tilausrivi.tuoteno";
        pupe_query($query);

        //Irrotetaan sarjanumerot
        if ($tilausrivirow["sarjanumeroseuranta"] != "") {
          $query = "UPDATE sarjanumeroseuranta
                    SET siirtorivitunnus = 0
                    WHERE siirtorivitunnus = '$tun'
                    and yhtio              = '$kukarow[yhtio]'";
          pupe_query($query);
        }

        if ($toim == "MYYNTITILI") {
          $uprquery = "UPDATE tilausrivi SET
                       hyllyalue   = '$t1[$tun]',
                       hyllynro    = '$t2[$tun]',
                       hyllyvali   = '$t3[$tun]',
                       hyllytaso   = '$t4[$tun]'
                       WHERE yhtio = '$kukarow[yhtio]'
                       and tunnus  = '$tun'";
          pupe_query($uprquery);
        }
      }

      if ($tee == "X") {
        // Summataan virhecountteria
        $virheita++;
      }
    }
  }

  $_jt_toimita_t = ($yhtiorow["automaattinen_jt_toimitus_siirtolista"] == "T");

  if ($_jt_toimita_t) {
    $query = "SELECT paivitys
              FROM oikeu
              WHERE yhtio = '$kukarow[yhtio]'
              and kuka    = '$kukarow[kuka]'
              and nimi    = 'tilauskasittely/jtselaus.php'
              and alanimi = ''";
    $jtoikeudetres = pupe_query($query);
    $_jtoikeus = (mysql_num_rows($jtoikeudetres));
  }
  else {
    $_jtoikeus = TRUE;
  }

  $_jt_toimita            = ($yhtiorow["automaattinen_jt_toimitus_siirtolista"] != "");
  $_jt_toimita_toimitus   = ($yhtiorow["automaattinen_jt_toimitus_siirtolista"] != "K");
  $_jt_toimita_sallittu   = ($_jtoikeus and $_jt_toimita);
  $_normisiirto           = (!isset($_kirjanpidollinen_varastosiirto) or $_kirjanpidollinen_varastosiirto == false);

  if ($_jt_toimita_sallittu and $_normisiirto) {
    $jtrivit              = array();
    $jtrivit_paikat       = array();
    $varastoon            = '';
    $automaaginen         = 'tosi_automaaginen';

    if ($yhtiorow['automaattinen_jt_toimitus_siirtolista'] == 'S' and !empty($tunnus)) {

      $automaaginen = 'vakisin';

      //Haetaan JT-rivit jotka mäppäytyvät siirtolistariveihin
      $query = "SELECT tilausrivin_lisatiedot.tilausrivilinkki AS jtrivi,
                tilausrivin_lisatiedot.kohde_hyllyalue hyllyalue,
                tilausrivin_lisatiedot.kohde_hyllynro hyllynro,
                tapahtuma.tunnus AS tapahtumatunnus
                FROM tilausrivin_lisatiedot
                JOIN tilausrivi
                ON ( tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio
                  AND tilausrivi.tunnus                     = tilausrivin_lisatiedot.tilausrivitunnus )
                JOIN tapahtuma
                ON ( tapahtuma.yhtio = tilausrivin_lisatiedot.yhtio
                  AND tapahtuma.laji                        = 'siirto'
                  AND tapahtuma.rivitunnus                  = tilausrivin_lisatiedot.tilausrivitunnus
                  AND tapahtuma.kpl                         > 0)
                WHERE tilausrivin_lisatiedot.yhtio          = '{$kukarow['yhtio']}'
                AND tilausrivin_lisatiedot.tilausrivitunnus IN (".implode(',', $tunnus).")";
      $varastoon_result = pupe_query($query);

      while ($varastoon_row = mysql_fetch_assoc($varastoon_result)) {
        // Mitkä myyntitilausrivit vastaanotettiin tällä siirtolistalla
        $jtrivit[$varastoon_row["jtrivi"]] = $varastoon_row["jtrivi"];
        // Katotaan mille paikalle nää meni, jotta myyntitilaus voidaan laukasta tältä paikalta
        $jtrivit_paikat[$varastoon_row["jtrivi"]] = $varastoon_row["tapahtumatunnus"];

        // haetaan $varastoon vain kerran
        if ($varastoon == '') {
          $varastoon = array(kuuluukovarastoon($varastoon_row['hyllyalue'], $varastoon_row['hyllynro']));
        }
      }
    }
    else {
      // kohdevarasto voi olla siirtolistalla vain yksi varasto, joten tehään tää loopin (~388) ulkopuolella yhden kerran (viimeinen rivi)
      $query = "SELECT tilausrivin_lisatiedot.kohde_hyllyalue hyllyalue,
                tilausrivin_lisatiedot.kohde_hyllynro hyllynro
                FROM tilausrivin_lisatiedot
                WHERE tilausrivin_lisatiedot.yhtio          = '{$kukarow['yhtio']}'
                AND tilausrivin_lisatiedot.tilausrivitunnus = '$tun'";
      $varastoon_result = pupe_query($query);
      $varastoon_row = mysql_fetch_assoc($varastoon_result);

      $varastoon = array(kuuluukovarastoon($varastoon_row['hyllyalue'], $varastoon_row['hyllynro']));
    }

    jt_toimita("", "", $varastoon, $jtrivit, $jtrivit_paikat, $automaaginen, "JATKA", '', '', '', '');

    if ($_jt_toimita_toimitus) {
      jt_toimita("", "", "", "", "", "dummy", "TOIMITA");
    }
  }

  if ($echotaanko) {
    echo "<br><br>";
  }

  if ($virheita == 0) {

    //päivitetään otsikko vastaanotetuksi ja tapvmmään päivä
    $query  = "SELECT otunnus, sum(rivihinta) rivihinta
               FROM tilausrivi
               WHERE yhtio = '$kukarow[yhtio]'
               AND otunnus IN ($id)
               AND tyyppi  = 'G'
               GROUP BY 1";
    $result = pupe_query($query);

    while ($apusummarow = mysql_fetch_assoc($result)) {
      // Nää oli tossa updatessa mutta muuttujia ei ollut eikä tullut
      //bruttopaino     = '$aputoimirow[bruttopaino]',
      //lisattava_era     = '$aputoimirow[lisattava_era]',
      //vahennettava_era  = '$aputoimirow[vahennettava_era]'

      $query = "UPDATE lasku
                SET alatila    = 'V',
                tapvm        = '$_tapvm',
                summa        = '$apusummarow[rivihinta]'
                WHERE tunnus = '{$apusummarow['otunnus']}'
                and yhtio    = '$kukarow[yhtio]'
                and tila     = 'G'";
      pupe_query($query);
    }
  }
}

// Tulostetaan vastaanotetut listaus
if (($tee == "OK" or $tee == "paikat") and !empty($id) and $toim != "MYYNTITILI") {

  if (isset($listaus) and (int) $listaus > 0) {

    $_id = explode(",", $id);

    $query  = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='{$_id[0]}'";
    $result = pupe_query($query);
    $laskurow = mysql_fetch_assoc($result);

    $query = "SELECT komento from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus = '$listaus'";
    $komres = pupe_query($query);
    $komrow = mysql_fetch_assoc($komres);
    $komento["Vastaanotetut"] = $komrow['komento'];

    $otunnus = $id;
    $mista = 'vastaanota';

    require 'tulosta_purkulista.inc';
  }

  $id      = 0;
  $varasto = "";
}
elseif ($tee == "OK" and !empty($id) and $toim == "MYYNTITILI") {
  $id      = 0;
  $varasto = "";
}

// meillä ei ole valittua tilausta
if (empty($id) and $echotaanko) {

  $formi  = "find";
  $kentta = "etsi";

  // tehdään etsi valinta
  echo "<form name='find' method='post'>";
  echo "<input type='hidden' name='toim' value='$toim'>";

  echo "<table>";
  echo "<tr>";
  echo "<th>";

  if ($toim == "MYYNTITILI" or $toim == "MYYNTITILIVASTAANOTA") {
    echo t("Etsi myyntitili");
  }
  else {
    echo t("Etsi siirtolistaa");
  }

  echo "</th>";
  echo "<td><input type='text' name='etsi'></td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>".t("Varasto")."</th>";
  echo "<td><select name='varastorajaus'>";
  echo "<option value=''>" . t('Kaikki varastot') . "</option>";

  $query  = "SELECT tunnus, nimitys, maa
             FROM varastopaikat
             WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
             ORDER BY tyyppi, nimitys";
  $vares = pupe_query($query);

  while ($varow = mysql_fetch_assoc($vares)) {

    $sel = '';
    if ( (!empty($varastorajaus) and $varow['tunnus'] == $varastorajaus) or ($varastorajaus === 0 and $kukarow['oletus_varasto'] == $varow['tunnus']) ) {
      $sel = 'selected';
    }

    $varastomaa = '';
    if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
      $varastomaa = strtoupper($varow['maa']);
    }

    echo "<option value='$varow[tunnus]' $sel>$varastomaa $varow[nimitys]</option>";
  }

  echo "</select></td></tr>";
  if ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikkares = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikkares) > 0) {
    $toimipaikat_result = hae_yhtion_toimipaikat($kukarow['yhtio']);
    $toimipaikat = array();
    $toimipaikat[] = array(
      'tunnus' => 'kaikki',
      'nimi' => t('Kaikki toimipaikat')
    );
    $toimipaikat[] = array(
      'tunnus' => '0',
      'nimi' => t('Ei toimipaikkaa')
    );
    while ($toimipaikka = mysql_fetch_assoc($toimipaikat_result)) {
      $toimipaikat[] = $toimipaikka;
    }
    echo "<tr>";
    echo "<th>";
    echo t('Toimipaikka');
    echo "</th>";
    echo "<td>";
    echo "<select name='toimipaikkarajaus'>";
    $sel = '';
    foreach ($toimipaikat as $toimipaikka) {
      if  ((isset($toimipaikkarajaus) and $toimipaikkarajaus === $toimipaikka['tunnus']) or (!isset($toimipaikkarajaus) and $kukarow['toimipaikka'] == $toimipaikka['tunnus']) ) {
        $sel = 'SELECTED';
      }

      echo "<option value='{$toimipaikka['tunnus']}' {$sel}>{$toimipaikka['nimi']}</option>";
      $sel = "";
    }
    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }

  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $vresult = pupe_query($query);
  echo "<tr><th>" . t('Maa') . "</th><td><select name='maa'>";

  echo "<option value=''>".t('Kaikki maat')."</option>";

  while ($maarow = mysql_fetch_assoc($vresult)) {
    $sel = (isset($maa) and $maarow['koodi'] == $maa) ? 'selected' : '';

    echo "<option value='{$maarow['koodi']}' $sel>{$maarow['nimi']}</option>";
  }

  echo "</select></td><td class='back'><input type='submit' class='hae_btn' value='".t("Etsi")."'></td></tr></table></form><br>";

  $haku = '';
  if (is_string($etsi) and $etsi != "")  $haku = " and lasku.nimi LIKE '%$etsi%' ";
  if (is_numeric($etsi) and $etsi > 0) $haku = " and lasku.tunnus='$etsi' ";

  $myytili = " and tilaustyyppi != 'M' ";

  if ($toim == "MYYNTITILI") {
    $myytili = " and lasku.tilaustyyppi = 'M' ";
  }

  $varasto = '';

  if (isset($varastorajaus) and !empty($varastorajaus)) {
    $varasto .= ' AND lasku.clearing = '.(int) $varastorajaus;
  }
  elseif ($varastorajaus === 0 and !empty($kukarow['oletus_varasto'])) {
    $varasto .= ' AND lasku.clearing = '.(int) $kukarow['oletus_varasto'];
  }


  if ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikkares = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikkares) > 0) {
    if (isset($toimipaikkarajaus) and $toimipaikkarajaus != 'kaikki') {
      $varasto .= " AND lasku.yhtio_toimipaikka = {$toimipaikkarajaus}";
    }
    elseif (!isset($toimipaikkarajaus) and $yhtiorow['toimipaikkakasittely'] == "L") {
      // rajataan vaikka käyttäjällä ei ole toimipaikkaa
      $varasto .= " AND lasku.yhtio_toimipaikka = {$kukarow['toimipaikka']}";
    }
    elseif (!isset($toimipaikkarajaus) and $kukarow['toimipaikka'] != 0) {
      // rajataan vain kun käyttäjällä on toimipaikka
      $varasto .= " AND lasku.yhtio_toimipaikka = {$kukarow['toimipaikka']}";
    }
  }

  if (isset($maa) and !empty($maa)) {
    $varasto .= " AND varastopaikat.maa = '".mysql_real_escape_string($maa)."'";
  }

  $lahdotrajaus = "";

  if ($toim == "" and $yhtiorow['siirtolistat_vastaanotetaan_per_lahto'] == 'K' and !empty($haku)) {

    $query = "SELECT GROUP_CONCAT(DISTINCT lasku.toimitustavan_lahto) lahto
              FROM lasku
              LEFT JOIN lahdot
              ON ( lahdot.yhtio = lasku.yhtio
                AND lahdot.tunnus = lasku.toimitustavan_lahto )
              WHERE lasku.tila    = 'G'
              {$haku}
              {$myytili}
              and lasku.yhtio     = '{$kukarow['yhtio']}'
              and lasku.alatila   in ('C','B','D')";
    $lahto_chk_res = pupe_query($query);
    $lahto_chk_row = mysql_fetch_assoc($lahto_chk_res);

    if ($lahto_chk_row['lahto'] != "") {
      $lahdotrajaus = "and lasku.toimitustavan_lahto IN ({$lahto_chk_row['lahto']})";
      $haku = "";
    }
  }

  $group_per_lahto = $yhtiorow['siirtolistat_vastaanotetaan_per_lahto'];

  $query = "SELECT IF(siirtolistan_vastaanotto = 0, 'x', siirtolistan_vastaanotto) siirtolistan_vastaanotto,
            IF((siirtolistan_vastaanotto != 0 OR toimitustavan_lahto = 0 OR '{$group_per_lahto}' = ''), 'x', toimitustavan_lahto) lahto,
            IF(siirtolistan_vastaanotto = 0, lasku.clearing, 0) clearing,
            lahdot.aktiivi AS lahdon_aktiivi,
            GROUP_CONCAT(DISTINCT tilausrivi.otunnus) otunnus
            FROM tilausrivi
            JOIN lasku on lasku.yhtio = tilausrivi.yhtio
              and lasku.tunnus         = tilausrivi.otunnus
              and lasku.tila           = 'G'
              and lasku.alatila        in ('C','B','D')
              {$lahdotrajaus}
              $myytili
            LEFT JOIN varastopaikat ON lasku.clearing=varastopaikat.tunnus
            LEFT JOIN lahdot
            ON ( lahdot.yhtio = lasku.yhtio
              AND lahdot.tunnus        = lasku.toimitustavan_lahto )
            where tilausrivi.yhtio     = '$kukarow[yhtio]'
            and tilausrivi.toimitettu  = ''
            and tilausrivi.keratty    != ''
            $varasto
            GROUP BY 1,2,3,4
            ORDER BY siirtolistan_vastaanotto, lahto, clearing";
  $tilre = pupe_query($query);

  $selectlisa = $toim == "" ? ", viesti AS viite" : "";

  if ($toim == "" and $yhtiorow['siirtolistat_vastaanotetaan_per_lahto'] == 'K') {
    $groupbylisa = "GROUP BY 1,2,3,4,5,6,7,8";
  }
  else {
    $groupbylisa = "";
  }

  if ($toim == "MYYNTITILI") {
    $qnimi1 = 'Myyntitili';
    $qnimi2 = 'Vastaanottaja';
  }
  else {
    $qnimi1 = 'Siirtolista';
    $qnimi2 = 'Vastaanottava varasto';
  }

  if (mysql_num_rows($tilre) > 0) {

    echo "<table id='siirtotable'>";
    echo "<tr>";

    if ($toim == "") {
      echo "<th align='left'>", t("Vastaanottonumero"), "<br>";
      echo "<form id='yhdistaformi' method='post' action=''>";
      echo "<input type='hidden' name='toim' value='' />";
      echo "<input type='hidden' name='tee' value='yhdista' />";
      echo "<input type='hidden' id='yhdistettavat_siirtolistat' name='yhdistettavat_siirtolistat' value='' />";
      echo "<input type='button' id='yhdistabutton' value='", t("Yhdistä"), "' /> ";
      echo "<input type='checkbox' id='yhdista_kaikki' value='' />";
      echo "</form>";
      echo "</th>";

      if ($yhtiorow['siirtolistat_vastaanotetaan_per_lahto'] == 'K') {
        echo "<th align='left'>", t("Lähtö"), "</th>";
      }
    }

    echo "<th align='left'>", t($qnimi1), "</th>";
    echo "<th align='left'>", t($qnimi2), "</th>";

    if ($toim == "") {
      echo "<th align='left'>", t("Viite"), "</th>";
    }

    echo "<th align='left'>", t("Laadittu"), "</th>";
    echo "<th align='left'>", t("Laatija"), "</th>";

    echo "</tr>";
  }

  $ed_lahto = $ed_vastaanottonro = null;

  while ($tilrow = mysql_fetch_assoc($tilre)) {

    $_suljettu_lahto = (!is_null($tilrow['lahdon_aktiivi']) and $tilrow['lahdon_aktiivi'] != 'S');

    if ($_suljettu_lahto) {
      //Ei näytetä siirtolistoja, joihin on liitetty aukioleva lähtö
      continue;
    }

    // etsitään sopivia tilauksia
    $query = "SELECT varasto,
              tunnus,
              IF((siirtolistan_vastaanotto != 0 OR toimitustavan_lahto = 0 OR '{$group_per_lahto}' = ''), '', toimitustavan_lahto) lahto,
              IF(siirtolistan_vastaanotto = 0, '', siirtolistan_vastaanotto) siirtolistan_vastaanotto,
              nimi,
              date_format(luontiaika, '%Y-%m-%d') laadittu,
              laatija
              {$selectlisa}
              FROM lasku
              WHERE tunnus IN ({$tilrow['otunnus']})
              and tila     = 'G'
              {$haku}
              {$myytili}
              and yhtio    = '{$kukarow['yhtio']}'
              and alatila  in ('C','B','D')
              {$groupbylisa}
              ORDER by siirtolistan_vastaanotto, lahto, laadittu DESC";
    $result = pupe_query($query);

    //piirretään taulukko...
    if (mysql_num_rows($result) != 0) {

      while ($row = mysql_fetch_assoc($result)) {

        $_vastaanottonro = $row['siirtolistan_vastaanotto'];
        $_lahto = $row['lahto'];

        if (!is_null($ed_vastaanottonro) and $ed_vastaanottonro != $_vastaanottonro) {
          echo "<tr><td class='back'>&nbsp;</td></tr>";
        }
        elseif (!is_null($ed_lahto) and $ed_lahto != $_lahto and $_vastaanottonro == '') {
          echo "<tr><td class='back'>&nbsp;</td></tr>";
        }

        echo "<tr class='aktiivi'>";

        if ($toim == "") {
          echo "<td>";
          echo "<input type='checkbox' class='siirtolistan_vastaanotto' name='siirtolistan_vastaanotto[]' value='{$row['tunnus']}' /> {$_vastaanottonro}";
          echo "</td>";

          if ($yhtiorow['siirtolistat_vastaanotetaan_per_lahto'] == 'K') {
            echo "<td>{$_lahto}</td>";
          }
        }

        echo "<td>{$row['tunnus']}</td>";
        echo "<td>{$row['nimi']}</td>";

        if ($toim == "") {
          echo "<td>{$row['viite']}</td>";
        }

        echo "<td>", tv1dateconv($row['laadittu']), "</td>";
        echo "<td>{$row['laatija']}</td>";

        if ($toim == "" and ($yhtiorow['siirtolistat_vastaanotetaan_per_lahto'] == 'K' or $row['siirtolistan_vastaanotto'] != '')) {
          $_id =  ($_lahto != '' or $_vastaanottonro != '') ? $tilrow['otunnus'] : $row['tunnus'];
        }
        else {
          $_id = $row['tunnus'];
        }

        echo "<td class='back'>";
        echo "<form method='post'>
              <input type='hidden' name='id' value='{$_id}'>
            <input type='hidden' name='varastorajaus' value='{$varastorajaus}'>
            <input type='hidden' name='maa' value='{$maa}'>
              <input type='hidden' name='toim' value='{$toim}'>";

        if ($toim == "MYYNTITILI") {
          if (is_null($ed_vastaanottonro) or $ed_vastaanottonro != $_vastaanottonro) {
            echo "<input type='submit' name='tila' value='".t("Toimita")."'>";
          }
          elseif (is_null($ed_lahto) or ($ed_lahto != $_lahto and $_vastaanottonro == '')) {
            echo "<input type='submit' name='tila' value='".t("Toimita")."'>";
          }
          elseif (is_null($ed_lahto) or ($ed_lahto == '' and $_vastaanottonro == '')) {
            echo "<input type='submit' name='tila' value='".t("Toimita")."'>";
          }
        }
        else {
          if (is_null($ed_vastaanottonro) or $ed_vastaanottonro != $_vastaanottonro) {
            echo "<input type='submit' name='tila' value='".t("Vastaanota")."'>";
          }
          elseif (is_null($ed_lahto) or ($ed_lahto != $_lahto and $_vastaanottonro == '')) {
            echo "<input type='submit' name='tila' value='".t("Vastaanota")."'>";
          }
          elseif (is_null($ed_lahto) or ($ed_lahto == '' and $_vastaanottonro == '')) {
            echo "<input type='submit' name='tila' value='".t("Vastaanota")."'>";
          }
        }

        echo "</form></td></tr>";

        $ed_lahto = $_lahto;
        $ed_vastaanottonro = $_vastaanottonro;
      }
    }
  }

  if (mysql_num_rows($tilre) > 0) {
    echo "</table>";
  }
  else {
    if ($toim == "MYYNTITILI") {
      echo "<font class='message'>".t("Yhtään toimitettavaa myyntitiliä ei löytynyt")."...</font>";
    }
    elseif ($toim == "MYYNTITILIVASTAANOTA") {
      echo "<font class='message'>".t("Yhtään vastaanotettavaa myyntitiliä ei löytynyt")."...</font>";
    }
    else {
      echo "<font class='message'>".t("Yhtään vastaanotettavaa siirtolistaa ei löytynyt")."...</font>";
    }
  }
}

if (!empty($id) and $echotaanko) {

  if ($toim == "MYYNTITILI") {
    $qnimi1 = 'Myyntitili';
    $qnimi2 = 'Vastaanottaja';
  }
  elseif ($toim == "MYYNTITILIVASTAANOTA") {
    $qnimi1 = 'Myyntitili';
    $qnimi2 = 'Vastaanottava varasto';
  }
  else {
    $qnimi1 = 'Siirtolista';
    $qnimi2 = 'Vastaanottava varasto';
  }

  if (!empty($id_talteen)) $id = $id_talteen;

  //tässä on valittu tilaus
  $query = "SELECT tunnus '$qnimi1',
            nimi '$qnimi2',
            date_format(luontiaika, '%Y-%m-%d')
            laadittu,
            laatija,
            clearing,
            if(nimi = toim_nimi OR toim_nimi = '', nimi, concat(nimi, '<br>', toim_nimi)) asiakkaan_nimi,
            liitostunnus asiakkaan_tunnus
            FROM lasku
            WHERE tunnus IN ({$id})
            and tila     = 'G'
            and yhtio    = '$kukarow[yhtio]'
            and alatila  in ('B','C','D')";
  $result = pupe_query($query);

  echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
      <!--
      $(document).ready(function(){
        var taytasarake = function() {

          var sarake_id = $(this).attr('id').replace('taytasarake_', '');
          var teksti = $(this).val();

          $('input[id^='+sarake_id+']').each(
            function() {
              $(this).val(teksti);
              $(this).trigger('change');
            }
          );
        };

        $('input[id^=taytasarake_]').on('keyup change blur', taytasarake);
      });
      //-->
      </script>";

  echo "<table>";
  echo "<tr>";

  for ($y=0; $y < mysql_num_fields($result)-1; $y++) {
    echo "<th align='left'>".t(mysql_field_name($result, $y))."</th>";
  }

  if ($toim == "") {
    echo "<th>".t("Lue paikat tiedostosta")."</th>";
    echo "<th>".t("Lue paikat rivikommentista")."</th>";
  }

  echo "</tr>";

  if (empty($id_talteen)) $id_talteen = $id;

  $_clearing = '';

  while ($row = mysql_fetch_array($result)) {
    echo "<tr>";

    for ($y=0; $y<mysql_num_fields($result)-1; $y++) {
      echo "<td>$row[$y]</td>";
    }

    if ($toim == "") {
      echo "<form method='post'>";
      echo "<input type='hidden' name='id' value='{$row[0]}'>";
      echo "<input type='hidden' name='id_talteen' value='{$id_talteen}'>";
      echo "<input type='hidden' name='varastorajaus' value='$varastorajaus'>";
      echo "<input type='hidden' name='maa' value='$maa'>";
      echo "<input type='hidden' name='varasto' value='$row[clearing]'>";
      echo "<input type='hidden' name='tee' value='mikrotila'>";
      echo "<td>";
      echo "<input type='submit' value='".t("Valitse tiedosto")."'>";
      echo "</td>";
      echo "</form>";

      echo "<form method='post'>";
      echo "<input type='hidden' name='id' value='{$row[0]}'>";
      echo "<input type='hidden' name='id_talteen' value='{$id_talteen}'>";
      echo "<input type='hidden' name='varastorajaus' value='$varastorajaus'>";
      echo "<input type='hidden' name='maa' value='$maa'>";
      echo "<input type='hidden' name='varasto' value='$row[clearing]'>";
      echo "<input type='hidden' name='tee' value='kommentista'>";
      echo "<td>";
      echo "<input type='submit' value='".t("Lue rivikommentista")."'>";
      echo "</td>";
      echo "</form>";
    }

    $_clearing = $row['clearing'];

    echo "</tr>";
  }

  echo "</table><br>";

  if (!empty($id_talteen)) $id = $id_talteen;

  //hakukentät
  echo "<form method='post' name='siirtolistaformi'>";
  echo "<input type='hidden' name='id' value='{$id}'>";
  echo "<input type='hidden' name='id_talteen' value='{$id_talteen}'>";
  echo "<input type='hidden' name='varastorajaus' value='$varastorajaus'>";
  echo "<input type='hidden' name='maa' value='$maa'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='tee' value='paikat'>";

  if ($toim == "") {
    echo "<input type='hidden' name='varasto' value='{$_clearing}'>";
  }
  else {
    $query = "SELECT tunnus
              FROM varastopaikat
              WHERE yhtio        = '$kukarow[yhtio]'
              and alkuhyllyalue  = '!!M'
              and loppuhyllyalue = '!!M'";
    $tresult = pupe_query($query);
    $mrow = mysql_fetch_assoc($tresult);
    echo "<input type='hidden' name='varasto' value='$mrow[tunnus]'>";
  }

  if ($toim == "" and tarkista_oikeus('muuvarastopaikka.php', '', 1)) {
    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Päivitetään oletuspaikka")."</th>";
    echo "<td>";

    $chk = $oletuspaiv != '' ? 'checked' : '';

    echo "<input type='checkbox' name='oletuspaiv' $chk>";
    echo "</td>";
    echo "</tr>";
    echo "</table><br>";
  }

  //vastaanottavan varaston tiedot
  $query  = "SELECT *
             FROM varastopaikat
             WHERE yhtio = '$kukarow[yhtio]'
             and tunnus  = '{$_clearing}'";
  $vares = pupe_query($query);
  $varow2 = mysql_fetch_assoc($vares);

  $lisa = " and concat(rpad(upper('$varow2[alkuhyllyalue]'),  5, '0'),lpad(upper('$varow2[alkuhyllynro]'),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) ";
  $lisa .= " and concat(rpad(upper('$varow2[loppuhyllyalue]'), 5, '0'),lpad(upper('$varow2[loppuhyllynro]'), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) ";

  //siirtolistan rivit
  $query = "SELECT tilausrivi.nimitys,
            tilausrivi.tuoteno,
            tilausrivi.otunnus,
            tilausrivi.tunnus,
            tilausrivi.varattu,
            tilausrivi.toimitettu,
            tilausrivi.hyllyalue,
            tilausrivi.hyllynro,
            tilausrivi.hyllyvali,
            tilausrivi.hyllytaso,
            tuote.ei_saldoa,
            tilausrivin_lisatiedot.kohde_hyllyalue,
            tilausrivin_lisatiedot.kohde_hyllynro,
            tilausrivin_lisatiedot.kohde_hyllyvali,
            tilausrivin_lisatiedot.kohde_hyllytaso,
            concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllyvali, tilausrivi.hyllytaso) paikka,
            concat(lpad(upper(tilausrivi.hyllyalue), 5, '0'), lpad(upper(tilausrivi.hyllynro), 5, '0'), lpad(upper(tilausrivi.hyllyvali), 5, '0'), lpad(upper(tilausrivi.hyllytaso), 5, '0')) sorttauskentta
            FROM tilausrivi
            JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno
            JOIN tilausrivin_lisatiedot on (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio  and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
            WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
            and tilausrivi.otunnus  IN ({$id})
            and tilausrivi.tyyppi   = 'G'
            and tilausrivi.varattu != 0
            and var                 not in ('P','J','O','S')
            ORDER BY sorttauskentta, tuoteno";
  $result = pupe_query($query);

  echo "<table>";

  //itse rivit
  echo "<tr>";

  if ($toim == "" and $yhtiorow['siirtolistat_vastaanotetaan_per_lahto'] == 'K') {
    echo "<th>", t("Siirtolista"), "</th>";
  }

  echo "<th>".t("Nimitys")."</th>";
  echo "<th>".t("Tuoteno")."</th>";
  echo "<th>".t("Paikka")."</th>";
  echo "<th>".t("Määrä")."</th>";

  if ($toim == "MYYNTITILI") {
    echo "<th>".t("Asiakas")."</th>";
  }
  else {
    echo "<th>".t("Hyllyalue")."</th>";
    echo "<th>".t("Hyllynro")."</th>";
    echo "<th>".t("Hyllyväli")."</th>";
    echo "<th>".t("Hyllytaso")."</th>";
    echo "<th>".t("Varastopaikka")."</th>";
    echo "<th>".t("Paikan Lähde")."</th>";
    echo "<th>".t("EANkoodi")."</th>";
    echo "<th>".t("Tarrat")."</th></tr>";
  }

  while ($rivirow = mysql_fetch_assoc($result)) {

    // Näytetäänkö rivin vai tuotteen varastopaikka
    $lahde = "Tuote";

    if ($rivirow["kohde_hyllyalue"] != "" and $rivirow["kohde_hyllynro"] != "" and $rivirow["kohde_hyllyvali"] != "" and $rivirow["kohde_hyllytaso"] != "" ) {
      $privirow['t1'] = $rivirow["kohde_hyllyalue"];
      $privirow['t2'] = $rivirow["kohde_hyllynro"];
      $privirow['t3'] = $rivirow["kohde_hyllyvali"];
      $privirow['t4'] = $rivirow["kohde_hyllytaso"];
      $lahde = "Rivi";
    }
    elseif ($rivirow["ei_saldoa"] == "") {
      $query = "SELECT tuotepaikat.hyllyalue t1, tuotepaikat.hyllynro t2, tuotepaikat.hyllyvali t3, tuotepaikat.hyllytaso t4,
                concat(lpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta
                FROM tuotepaikat
                WHERE yhtio = '$kukarow[yhtio]'
                and tuoteno = '$rivirow[tuoteno]'
                $lisa
                ORDER BY sorttauskentta";
      $presult = pupe_query($query);
      $privirow = mysql_fetch_assoc($presult);
    }
    else {
      $privirow = array();
    }

    if ($rivirow["ei_saldoa"] != "") {
      $privirow['t1'] = "SALDOTON-TUOTE";
      $lahde = "Tuote";
    }
    elseif ($privirow['t1'] == '' and $toim == "") { // ei löytynyt varastopaikkaa
      $privirow['t1'] = "";
      $privirow['t2'] = "";
      $privirow['t3'] = "";
      $privirow['t4'] = "";
      $lahde = "Vastaanottavaa paikkaa ei löydy";
    }

    if (isset($tunnus)) {
      foreach ($tunnus as $tun) {
        if ($tun == $rivirow["tunnus"]) {
          $privirow["t1"] = $t1[$tun];
          $privirow["t2"] = $t2[$tun];
          $privirow["t3"] = $t3[$tun];
          $privirow["t4"]  = $t4[$tun];
        }
      }
    }

    echo "<tr>";
    echo "<input type='hidden' name='tunnus[]' value='$rivirow[tunnus]'>";

    if ($toim == "" and $yhtiorow['siirtolistat_vastaanotetaan_per_lahto'] == 'K') {
      echo "<td>{$rivirow['otunnus']}</td>";
    }

    echo "<td>".t_tuotteen_avainsanat($rivirow, 'nimitys')."</td>";
    echo "<td>$rivirow[tuoteno]</td>";

    if ($rivirow["ei_saldoa"] != "") {
      echo "<td></td>";
    }
    elseif ($rivirow["hyllyalue"] == "!!M") {
      $asiakkaan_tunnus = (int) $rivirow["hyllynro"].$rivirow["hyllyvali"].$rivirow["hyllytaso"];
      $query = "SELECT if(nimi = toim_nimi OR toim_nimi = '', nimi, concat(nimi, ' / ', toim_nimi)) asiakkaan_nimi
                FROM asiakas
                WHERE yhtio = '{$kukarow["yhtio"]}'
                AND tunnus  = '$asiakkaan_tunnus'";
      $asiakasresult = pupe_query($query);
      $asiakasrow = mysql_fetch_assoc($asiakasresult);
      echo "<td>{$asiakasrow["asiakkaan_nimi"]}</td>";
    }
    else {
      echo "<td>$rivirow[paikka]</td>";
    }

    echo "<td>$rivirow[varattu]</td>";

    // Myyntitileillä on speciaali vastaanotto
    if ($toim == "MYYNTITILI") {
      if ($rivirow["kohde_hyllyalue"] == '') {
        // Tehdään asiakkaan tunnuksesta myyntitili-varastopaikka
        list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = myyntitili_varastopaikka($row["asiakkaan_tunnus"]);
      }
      else {
        $hyllyalue = $rivirow["kohde_hyllyalue"];
        $hyllynro  = $rivirow["kohde_hyllynro"];
        $hyllyvali = $rivirow["kohde_hyllyvali"];
        $hyllytaso = $rivirow["kohde_hyllytaso"];
      }

      echo "<td>";
      echo "<input type='hidden' name='t1[$rivirow[tunnus]]' value='$hyllyalue' maxlength='5' size='5'>";
      echo "<input type='hidden' name='t2[$rivirow[tunnus]]' value='$hyllynro' maxlength='5' size='5'>";
      echo "<input type='hidden' name='t3[$rivirow[tunnus]]' value='$hyllyvali' maxlength='5' size='5'>";
      echo "<input type='hidden' name='t4[$rivirow[tunnus]]' value='$hyllytaso' maxlength='5' size='5'>";
      echo "{$row["asiakkaan_nimi"]}";
      echo "</td>";
    }
    else {

      if ($rivirow["ei_saldoa"] != "") {
        echo "<td colspan='6'></td>";
        echo "<input type='hidden' name='t1[$rivirow[tunnus]]' value='$privirow[t1]' maxlength='5' size='5'>";
      }
      else {
        echo "<td><input type='text' id='t1[$rivirow[tunnus]]' name='t1[$rivirow[tunnus]]' value='$privirow[t1]' maxlength='5' size='5'></td>";
        echo "<td><input type='text' id='t2[$rivirow[tunnus]]' name='t2[$rivirow[tunnus]]' value='$privirow[t2]' maxlength='5' size='5'></td>";
        echo "<td><input type='text' id='t3[$rivirow[tunnus]]' name='t3[$rivirow[tunnus]]' value='$privirow[t3]' maxlength='5' size='5'></td>";
        echo "<td><input type='text' id='t4[$rivirow[tunnus]]' name='t4[$rivirow[tunnus]]' value='$privirow[t4]' maxlength='5' size='5'></td>";

        $vares = varaston_lapsivarastot($varow2['tunnus'], $rivirow['tuoteno']);

        $s1_options = array();
        $s2_options = array();
        $s3_options = array();

        while ($varow = mysql_fetch_assoc($vares)) {
          $status = $varow['status'];
          ${$status."_options"}[] = $varow;
        }

        $counts = array(
          's1' => count($s1_options),
          's2' => count($s2_options),
          's3' => count($s3_options)
        );

        if (array_sum($counts) > 1) {
          echo "<td><select name='rivivarasto[$rivirow[tunnus]]'><option value='x'>Ei muutosta";

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

          echo "</select></td>";
        }
        else {
          echo "<input type='hidden' name='rivivarasto[$rivirow[tunnus]]' value=''>";

          if (array_sum($counts) == 1) {
            arsort($counts);
            reset($counts);
            $key = key($counts);
            echo "<td>";
            echo ${$key."_options"}[0]['hyllyalue'], ' ';
            echo ${$key."_options"}[0]['hyllynro'], ' ';
            echo ${$key."_options"}[0]['hyllyvali'], ' ';
            echo ${$key."_options"}[0]['hyllytaso'];
            echo "</td>";
          }
          else {
            echo "<td><font class='error'>".t("Ei varastopaikkaa")."!</font></td>";
          }
        }

        // Valikko, josta voi valita muun varastopaikan
        echo "<td>$lahde</td>";
      }

      //haetaan eankoodi tuotteelta
      $query  = "SELECT eankoodi
                 FROM tuote
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tuoteno = '$rivirow[tuoteno]'";
      $eanres = pupe_query($query);
      $eanrow = mysql_fetch_assoc($eanres);
      $eankoodi = $eanrow['eankoodi'];

      if ($eankoodi== 0) {
        $eankoodi = '';
      }
      echo "<td><input type='text' name='eankoodi[$rivirow[tunnus]]' value='$eankoodi' maxlength='13' size='13'></td>";

      //annetaan mahdollisuus tulostaa tuotetarroja
      $echk = '';
      if (isset($eancheck[$rivirow['tunnus']]) and $eancheck[$rivirow['tunnus']] != '') {
        $echk = "CHECKED";
      }
      echo "<td align='center'><input type='checkbox' name='eancheck[$rivirow[tunnus]]' $echk></td>";
    }

    echo "</tr>";
  }

  if ($toim != "MYYNTITILI") {
    $_colspan = 4;

    if ($toim == "" and $yhtiorow['siirtolistat_vastaanotetaan_per_lahto'] == 'K') $_colspan++;

    echo "<tr><td colspan='{$_colspan}' class='back' align='right' valign='center'>", t("Täytä kaikki kentät"), ":</td>";
    echo "<td><input type='text' id='taytasarake_t1' maxlength='5' size='5'></td>";
    echo "<td><input type='text' id='taytasarake_t2' maxlength='5' size='5'></td>";
    echo "<td><input type='text' id='taytasarake_t3' maxlength='5' size='5'></td>";
    echo "<td><input type='text' id='taytasarake_t4' maxlength='5' size='5'></td>";
    echo "</tr>";
  }

  echo "</table><br>";

  if ($toim != "MYYNTITILI") {
    echo "<table><tr><td>";
    echo t("Kirjoitin johon tuotetarrat tulostetaan")."<br>";

    $query = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]'";
    $kires = pupe_query($query);

    echo "<select name='kirjoitin'>";
    echo "<option value=''>".t("Ei kirjoitinta")."</option>";

    while ($kirow = mysql_fetch_assoc($kires)) {
      if (isset($kirjoitin) and $kirow['tunnus'] == $kirjoitin) $select='SELECTED';
      else $select = '';

      echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
    }

    echo "</select></td><td>";

    echo t("Kirjoitin johon vastaanotetut listaus tulostetaan")."<br>";

    mysql_data_seek($kires, 0);
    echo "<select name='listaus'>";
    echo "<option value='$kirow[tunnus]'>".t("Ei kirjoitinta")."</option>";

    while ($kirow = mysql_fetch_assoc($kires)) {
      if (isset($listaus) and $kirow['tunnus'] == $listaus) $select='SELECTED';
      elseif ($toim == '' and !isset($listaus) and $kirow['tunnus'] == $varow2['printteri9']) $select = 'selected';
      else $select = '';

      echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
    }

    echo "</select></td><td>";
    echo t("Vain listaus")."<br>";
    echo "<input type='checkbox' name='vainlistaus'></td><td>";
    echo t("Järjestä tuotenumeron mukaan")."<br><input type='checkbox' name='jarjestys'></td>";
    echo "</table>";
  }
  echo "<br>";

  if ($toim == "MYYNTITILI") {
    echo "<input type='submit' name='Laheta' value='".t("Toimita myyntitili")."'>";
  }
  elseif ($toim == "MYYNTITILIVASTAANOTA") {
    echo "<input type='submit' name='Laheta' value='".t("Vastaanota myyntitili")."'>";
  }
  else {
    echo "<input type='submit' name='Laheta' value='".t("Vastaanota siirtolista")."'>";
  }

  echo "</form>";

}

if ($echotaanko) {
  require "inc/footer.inc";
}
