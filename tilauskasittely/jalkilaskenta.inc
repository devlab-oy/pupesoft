<?php

/* Tälle on annettava

$tuoteno   = korjattava tuote
$pvm     = mihin päivään asti korjataan
$uusihinta   = mikä on tuon pvm:n oikea ostohinta
$rivitunnus = mikä on tapahtuman tehneen rivin tunnus

Palautetaan $jlerror, joka on 1, jos jotain epätavallista sattui

*/

$jalkilaskenta_debug = 0; // 0 = ei echoilla debug koodia, 1 = ehchoillaa infoa, 2 = echoillaan infoa plus queryt

// Jos kaikki on ok, palautetaan 0, muuten 1
if (!function_exists("korjaalaskut")) {
  function korjaalaskut($tuoteno, $kehahin, $alku, $loppu, $korjtunnus) {
    global $kukarow, $yhtiorow, $jalkilaskenta_debug, $jalkilaskenta_debug_text, $varastotili,
    $varastonmuutostili;

    if ($korjtunnus != 0) {
      $lisa = " and tilausrivi.tunnus = '$korjtunnus' ";
    }
    else {
      $lisa = "";
    }

    // etsitään tuotteen kaikki myyntitilausrivit
    $query = "SELECT tilausrivi.otunnus oltunnus,
              tilausrivi.uusiotunnus ltunnus,
              tilausrivi.tunnus ttunnus,
              tilausrivi.kpl,
              tilausrivi.kate,
              tilausrivi.kate_korjattu,
              tilausrivi.rivihinta,
              tilausrivi.laskutettuaika,
              tilausrivi.alv,
              LEFT(tapahtuma.laadittu, 10) laadittu
              FROM tapahtuma
              JOIN tilausrivi ON tapahtuma.yhtio = tilausrivi.yhtio and tapahtuma.rivitunnus = tilausrivi.tunnus and tilausrivi.tyyppi = 'L'
              WHERE tapahtuma.yhtio  = '$kukarow[yhtio]'
              and tapahtuma.laji     = 'laskutus'
              and tapahtuma.tuoteno  = '$tuoteno'
              and tapahtuma.laadittu >= '$alku'
              and tapahtuma.laadittu <= '$loppu'
              $lisa
              ORDER BY tapahtuma.laadittu ASC";
    $tilausriviresult = pupe_query($query);

    if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
    if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>".t("Löydettiin tuotteelle")." $tuoteno ".mysql_num_rows($tilausriviresult)." ".t("tilausriviä").".</font><br>";

    if ($yhtiorow["tarkenteiden_prioriteetti"] == "T") {
      // Tässä tapauksessa toimipaikka ohittaa priossa muut vaikka vat_numero ois tyhjä
      $toimpriolisa = "";
    }
    else {
      $toimpriolisa = " ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ";
    }

    while ($tilausrivi = mysql_fetch_assoc($tilausriviresult)) {

      // haetaan laskun tiedot
      $query = "SELECT maa, yhtio_toimipaikka, laskunro, nimi, tapvm, tunnus, jaksotettu
                FROM lasku
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$tilausrivi[ltunnus]'";
      $laskures = pupe_query($query);
      $lasku    = mysql_fetch_assoc($laskures);

      // Katotaan mille kustannuspaikalle tuote on alunperin tiliöity
      // HUOM: sama pätkä kuin teelaskuntiliointi.inc:ssä
      $query = "SELECT
                if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.kustp, 0) > 0,             tuotteen_alv.kustp,           if({$toimpriolisa} ifnull(yhtion_toimipaikat.kustp, 0) > 0,         yhtion_toimipaikat.kustp,           if(ifnull(tuote.kustp, 0) > 0,             tuote.kustp,           if(ifnull(asiakas.kustannuspaikka, 0) > 0,         asiakas.kustannuspaikka,     if(ifnull(yhtion_toimipaikat.kustp, 0) > 0,           yhtion_toimipaikat.kustp,           yhtio.myynti_kustp))))) kustp,
                if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.kohde, 0) > 0,             tuotteen_alv.kohde,           if({$toimpriolisa} ifnull(yhtion_toimipaikat.kohde, 0) > 0,         yhtion_toimipaikat.kohde,           if(ifnull(tuote.kohde, 0) > 0,             tuote.kohde,           if(ifnull(asiakas.kohde, 0) > 0,             asiakas.kohde,           if(ifnull(yhtion_toimipaikat.kohde, 0) > 0,           yhtion_toimipaikat.kohde,           yhtio.myynti_kohde))))) kohde,
                if(ifnull(yhtion_toimipaikat.vat_numero, '') != '' and ifnull(tuotteen_alv.projekti, 0) > 0,           tuotteen_alv.projekti,           if({$toimpriolisa} ifnull(yhtion_toimipaikat.projekti, 0) > 0,         yhtion_toimipaikat.projekti,         if(ifnull(tuote.projekti, 0) > 0,           tuote.projekti,         if(ifnull(asiakas.projekti, 0) > 0,           asiakas.projekti,         if(ifnull(yhtion_toimipaikat.projekti, 0) > 0,           yhtion_toimipaikat.projekti,         yhtio.myynti_projekti))))) projekti
                FROM tilausrivi
                JOIN lasku on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)
                LEFT JOIN yhtio ON (yhtio.yhtio = lasku.yhtio)
                LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
                LEFT JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
                LEFT JOIN tuotteen_alv ON (tuotteen_alv.yhtio = tilausrivi.yhtio and tuotteen_alv.tuoteno = tilausrivi.tuoteno and tuotteen_alv.maa = '$lasku[maa]' and tuotteen_alv.maa != '')
                LEFT JOIN yhtion_toimipaikat ON (yhtion_toimipaikat.yhtio = tilausrivi.yhtio and yhtion_toimipaikat.tunnus = '$lasku[yhtio_toimipaikka]')
                LEFT JOIN asiakas ON (tilausrivi.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus)
                WHERE tilausrivi.tunnus = '$tilausrivi[ttunnus]'
                AND tilausrivi.tyyppi   = 'L'
                AND tilausrivi.yhtio    = '$kukarow[yhtio]'";
      $xresult = pupe_query($query);
      $xrivi  = mysql_fetch_assoc($xresult);

      // katotaan saadaanko tehdä tiliöintejä
      // tehdään tilikausi_alku ja korjauksen_alku muuttujista PHP:ssä vertailukelpoisia
      $tilikausi_alku = (int) str_replace("-", "", $yhtiorow["tilikausi_alku"]);
      $korjaus_alku = (int) str_replace("-", "", $tilausrivi["laadittu"]);

      // Ok, löydämmekö vastaavan tiliöinnin laskulta
      $query = "SELECT *
                FROM tiliointi
                 WHERE yhtio  = '$kukarow[yhtio]'
                 AND ltunnus  = '$tilausrivi[ltunnus]'
                 AND tilino   = '{$varastonmuutostili}'
                 AND kustp    = '$xrivi[kustp]'
                 AND kohde    = '$xrivi[kohde]'
                 AND projekti = '$xrivi[projekti]'
                AND korjattu  = ''
                ORDER BY tapvm DESC, tunnus DESC
                LIMIT 1";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {

        // tutkaillaan ensiks josko kustannuspaikat, kohteet tai projektit on muuttunut...
        $query = "SELECT *
                  FROM tiliointi
                   WHERE yhtio = '$kukarow[yhtio]'
                   and ltunnus = '$tilausrivi[ltunnus]'
                   and tilino  = '{$varastonmuutostili}'
                  and korjattu = ''
                  ORDER BY tapvm DESC, summa DESC
                  LIMIT 1";
        $result = pupe_query($query);
      }

      // otetaan löydettyjen rivien määrä talteen
      $tilikpl = mysql_num_rows($result);

      $uusi_varastonmuutos = 0;

      // Haetaan tapahtumalta tämän tuotteen uusi varastonmuutos, koska tapahtuma on päivitetty jo aiemmassa loopissa (tämän funktiokutsun ulkopuolella)
      $query = "SELECT IFNULL(SUM(tapahtuma.hinta * tapahtuma.kpl) * -1, 0) AS tapahtuman_hinta
                FROM tapahtuma USE INDEX (yhtio_laji_rivitunnus)
                WHERE tapahtuma.yhtio    = '{$kukarow['yhtio']}'
                AND tapahtuma.laji       IN ('laskutus', 'korjaus')
                AND tapahtuma.rivitunnus = '{$tilausrivi['ttunnus']}'";
      $hinta_chk_res = pupe_query($query);
      $hinta_chk_row = mysql_fetch_assoc($hinta_chk_res);

      $uusi_varastonmuutos = (float) $hinta_chk_row['tapahtuman_hinta'];

      $kate_muuttuja = $tilausrivi['kate_korjattu'] === NULL ? $tilausrivi['kate'] : $tilausrivi['kate_korjattu'];
      $vanha_varastonmuutos = ($tilausrivi['rivihinta'] - $kate_muuttuja);

      // lasketaan uusi kate
      $uusikate = $tilausrivi['rivihinta'] - $tilausrivi['kpl'] * $kehahin;

      // debug
      if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>Uusikate = $tilausrivi[rivihinta] - $tilausrivi[kpl] * $kehahin (muutos = $muutos)</font><br>";

      // jos ei löydetä varastonmuutostiliöintiä ja meillä on varastonmuutosta
      if ($tilikpl == 0 and $uusi_varastonmuutos <> 0) {
        /*
        katsotaan onko $lasku[tapvm] suljetulla tilikaudella, jos on, laitetaan pvm ensimmäinen sallittu pvm (eli avoimen tilikauden ensimmäinen pvm)
        */

        $pvm = $tilikausi_alku > $korjaus_alku ? $yhtiorow['tilikausi_alku'] : $lasku['tapvm'];

        if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>Luodaan uudet tiliöinnit, tarkista tiliöinnit. laskunro: $lasku[laskunro] ($lasku[nimi]) varasto: $uusi_varastonmuutos varastonmuutos: $uusi_varastonmuutos</font><br>";

        list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varasto"], $xrivi["kustp"], $xrivi["kohde"], $xrivi["projekti"]);

        $uusi_varastonmuutos_kp = round($uusi_varastonmuutos, 2);
        $uusi_varasto_kp = round($uusi_varastonmuutos * -1, 2);

        $_tilino = " tilino = '{$varastotili}',";

        // jos on asiakasvarasto-ominaisuudet käytössä, niin
        // varastotili voi mahdollisesti olla toinen.
        if (!empty($yhtiorow["ennakkolaskun_asiakasvarasto"]) and !empty($yhtiorow["asiakasvarasto"])) {

          $query = "SELECT *
                    FROM maksupositio
                    WHERE otunnus = '{$lasku['jaksotettu']}'
                    AND osuus = 100
                    AND yhtio = '{$kukarow['yhtio']}'";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 1) {
            $_tilino = " tilino = '{$yhtiorow["ennakkolaskun_asiakasvarasto"]}',";
          }
        }

        // Kirjataan 'varasto'-tilille
        $query = "INSERT into tiliointi set
                  yhtio    = '$kukarow[yhtio]',
                  ltunnus  = '$lasku[tunnus]',
                  $_tilino
                  kustp    = '{$kustp_ins}',
                  kohde    = '{$kohde_ins}',
                  projekti = '{$projekti_ins}',
                  tapvm    = '{$pvm}',
                  summa    = $uusi_varasto_kp,
                  vero     = 0,
                  selite   = 'Varastostamyynti $lasku[nimi]',
                  lukko    = '',
                  laatija  = '$kukarow[kuka]',
                  laadittu = now()";
        $laskutusres = pupe_query($query);
        if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

        list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varastonmuutos"], $xrivi["kustp"], $xrivi["kohde"], $xrivi["projekti"]);

        // Kirjataan 'varastonmuutos'-tilille
        $query = "INSERT into tiliointi set
                  yhtio    = '$kukarow[yhtio]',
                  ltunnus  = '$lasku[tunnus]',
                  tilino   = '{$varastonmuutostili}',
                  kustp    = '{$kustp_ins}',
                  kohde    = '{$kohde_ins}',
                  projekti = '{$projekti_ins}',
                  tapvm    = '{$pvm}',
                  summa    = $uusi_varastonmuutos_kp,
                  vero     = 0,
                  selite   = 'Varastostamyynti $lasku[nimi]',
                  lukko    = '',
                  laatija  = '$kukarow[kuka]',
                  laadittu = now()";
        $laskutusres = pupe_query($query);
        if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

        // korjataan laskun kate
        $query = "UPDATE lasku SET kate = round(kate - $tilausrivi[kate] + $uusikate, 2), kate_korjattu = kate WHERE tunnus='$tilausrivi[ltunnus]'";
        if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
        $katekorjres = pupe_query($query);

        // korjataan tilauksen kate
        $query = "UPDATE lasku SET kate = round(kate - $tilausrivi[kate] + $uusikate, 2), kate_korjattu = kate WHERE tunnus='$tilausrivi[oltunnus]'";
        if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
        $katekorjres = pupe_query($query);

        // ja lopuksi korjataan tilausrivin kate
        $query = "UPDATE tilausrivi SET kate = round($uusikate, 6), kate_korjattu = kate WHERE tunnus='$tilausrivi[ttunnus]'";
        if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
        $katekorjres = pupe_query($query);

      }

      $muutos = 0;

      // jos löydetään varastonmuutostiliöinti
      if ($tilikpl == 1) {

        $tiliointi1 = mysql_fetch_assoc($result);

        // jos on asiakasvarasto-ominaisuudet käytössä, niin
        // varastotili voi mahdollisesti olla toinen.
        // koitetaan siis hakea myös sillä (ei voi olla molempia
        // samanaikaisesti tositteella).
        if (!empty($yhtiorow["ennakkolaskun_asiakasvarasto"]) and !empty($yhtiorow["asiakasvarasto"])) {
          $_tilino = " AND tilino in ('{$varastotili}','{$yhtiorow["ennakkolaskun_asiakasvarasto"]}')";
        }
        else {
          $_tilino = " AND tilino = '{$varastotili}'";
        }

        // Etsitään varastotiliöinti
        $query = "SELECT *
                  FROM tiliointi
                   WHERE yhtio = '$kukarow[yhtio]'
                  and ltunnus  = '$tilausrivi[ltunnus]'
                  $_tilino
                  and kustp    = '$xrivi[kustp]'
                  and kohde    = '$xrivi[kohde]'
                  and projekti = '$xrivi[projekti]'
                  and korjattu = ''
                  ORDER BY tapvm DESC, tunnus DESC
                  LIMIT 1";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 0) {

          $query = "SELECT *
                    FROM tiliointi
                     WHERE yhtio = '$kukarow[yhtio]'
                    and ltunnus  = '$tilausrivi[ltunnus]'
                    $_tilino
                    and korjattu = ''
                    ORDER BY tapvm DESC, tunnus DESC
                    LIMIT 1";
          $result = pupe_query($query);
        }

        if (mysql_num_rows($result) != 1) {

          echo "<font class='error'>".t("Emme osanneet korjata kirjanpitoa eikä katteita!")." ".t("Laskulta ei löydy sopivaa tiliöintiä (varasto)")."
          tuoteno:                  $tuoteno,
          tilino:                   {$varastonmuutostili},
          kustp:                    $xrivi[kustp],
          kohde:                    $xrivi[kohde],
          projekti:                  $xrivi[projekti],
          tiliöinnin summa:               summa - $tilausrivi[kate] + $uusikate,
          laskun ($tilausrivi[ltunnus]) kate:     kate - $tilausrivi[kate] + $uusikate,
          tilausrivin ($tilausrivi[ttunnus]) kate:   $uusikate
          </font><br><br>";

          if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

          continue;
        }

        // Lasketaan muutos
        $muutos = round($uusi_varastonmuutos - $vanha_varastonmuutos, 2);

        $tiliointi2 = mysql_fetch_assoc($result);
      }

      if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>".t("Laskun")." $tilausrivi[ltunnus] ".t("varastonmuutostiliöinti löytyi").".</font><br>";

      // Jos varastonmuutos muuttuu
      if ($muutos <> 0) {

        /*

        jos ollaan suljetulla tilikaudella ($tiliointi1[tapvm]) niin ei voida ylikirjata, vaan tehdään uusi vastakirjaus ja uusi oikeakirjaus avoimen kauden ensimmäiselle päivälle
        else yliviivataan normaalisti

        */

        if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>".t("Laskun")." $tilausrivi[ltunnus] ".t("varastotiliöinti löytyi").".</font><br>";

        $pvm = $tilikausi_alku > (int) str_replace("-", "", $tiliointi1["tapvm"]) ? $yhtiorow['tilikausi_alku'] : "";

        // Ollaan avoimella tilikaudella
        if ($pvm == "") {

          // Yliviivataan alkuperäinen varastonmuutostiliöinti
          $query = "UPDATE tiliointi SET korjattu = '{$kukarow['kuka']}', korjausaika = now() WHERE tunnus='{$tiliointi1['tunnus']}'";
          $katekorjres = pupe_query($query);

          if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

          if (stripos($tiliointi1['selite'], t("Jälkilaskennan korjaus")) === FALSE and stripos($tiliointi1['selite'], "Jälkilaskennan korjaus") === FALSE) {
            $tiliointi1['selite'] = "{$tiliointi1['selite']} ".t("Jälkilaskennan korjaus");
          }

          // Tehdään uusi varastonmuutostiliöinti
          $params = array(
            'summa'     => ($tiliointi1['summa'] + $muutos),
            'korjattu'     => '',
            'korjausaika'   => '',
            'laatija'     => $kukarow['kuka'],
            'laadittu'     => date('Y-m-d H:i:s'),
            'selite'     => $tiliointi1['selite'],
          );

          // Tehdään vastakirjaus alkuperäiselle varastonmuutostiliöinnille
          kopioitiliointi($tiliointi1['tunnus'], "", $params); // kopioidaan vanha tiliöintirivi

          // Yliviivataan alkuperäinen varastotiliöinti
          $query = "UPDATE tiliointi SET korjattu = '{$kukarow['kuka']}', korjausaika = now() WHERE tunnus='{$tiliointi2['tunnus']}'";
          $katekorjres = pupe_query($query);

          if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

          if (stripos($tiliointi2['selite'], t("Jälkilaskennan korjaus")) === FALSE and stripos($tiliointi2['selite'], "Jälkilaskennan korjaus") === FALSE) {
            $tiliointi2['selite'] = "{$tiliointi2['selite']} ".t("Jälkilaskennan korjaus");
          }

          // Tehdään uusi varastotiliöinti
          $params = array(
            'summa'     => ($tiliointi2['summa'] - $muutos),
            'korjattu'     => '',
            'korjausaika'   => '',
            'laatija'     => $kukarow['kuka'],
            'laadittu'     => date('Y-m-d H:i:s'),
            'selite'     => $tiliointi2['selite'],
          );

          // Tehdään vastakirjaus alkuperäiselle varastonmuutostiliöinnille
          kopioitiliointi($tiliointi2['tunnus'], "", $params); // kopioidaan vanha tiliöintirivi

          // korjataan laskun kate
          $query = "UPDATE lasku SET kate = round(kate - $tilausrivi[kate] + $uusikate, 2), kate_korjattu = kate WHERE tunnus='$tilausrivi[ltunnus]'";
          $katekorjres = pupe_query($query);

          if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

          // korjataan tilauksen kate
          $query = "UPDATE lasku SET kate = round(kate - $tilausrivi[kate] + $uusikate, 2), kate_korjattu = kate WHERE tunnus='$tilausrivi[oltunnus]'";
          $katekorjres = pupe_query($query);

          if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

          // ja lopuksi korjataan tilausrivin kate
          $query = "UPDATE tilausrivi SET kate = round($uusikate, 6), kate_korjattu = kate WHERE tunnus='$tilausrivi[ttunnus]'";
          $katekorjres = pupe_query($query);

          if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
        }
        else {

          /*
          VARASTONMUUTOS
          */

          $params = array(
            'tapvm' => $yhtiorow['tilikausi_alku'],
            'summa' => ($tiliointi1['summa'] * -1),
            'summa_valuutassa' => 0,
            'korjattu' => '',
            'korjausaika' => '',
            'laatija' => $kukarow['kuka'],
            'laadittu' => date('Y-m-d H:i:s'),
            'selite' => "{$tiliointi1['selite']} ".t("Jälkilaskennan korjaus"),
          );

          // Tehdään vastakirjaus alkuperäiselle varastonmuutostiliöinnille
          kopioitiliointi($tiliointi1['tunnus'], "", $params); // kopioidaan vanha tiliöintirivi

          $params = array(
            'tapvm' => $yhtiorow['tilikausi_alku'],
            'summa' => ($tiliointi1['summa'] + $muutos),
            'summa_valuutassa' => 0,
            'korjattu' => '',
            'korjausaika' => '',
            'laatija' => $kukarow['kuka'],
            'laadittu' => date('Y-m-d H:i:s'),
            'selite' => "{$tiliointi1['selite']} ".t("Jälkilaskennan korjaus"),
          );

          // Tehdään uusi varastonmuutostiliöinti oikeilla arvoilla
          kopioitiliointi($tiliointi1['tunnus'], "", $params); // kopioidaan vanha tiliöintirivi

          /*
          VARASTO
          */

          $params = array(
            'tapvm' => $yhtiorow['tilikausi_alku'],
            'summa' => ($tiliointi2['summa'] * -1),
            'summa_valuutassa' => 0,
            'korjattu' => '',
            'korjausaika' => '',
            'laatija' => $kukarow['kuka'],
            'laadittu' => date('Y-m-d H:i:s'),
            'selite' => "{$tiliointi2['selite']} ".t("Jälkilaskennan korjaus"),
          );

          //  Tehdään vastakirjaus alkuperäiselle varastotiliöinnille
          kopioitiliointi($tiliointi2['tunnus'], "", $params); // kopioidaan vanha tiliöintirivi

          $params = array(
            'tapvm' => $yhtiorow['tilikausi_alku'],
            'summa' => ($tiliointi2['summa'] - $muutos),
            'summa_valuutassa' => 0,
            'korjattu' => '',
            'korjausaika' => '',
            'laatija' => $kukarow['kuka'],
            'laadittu' => date('Y-m-d H:i:s'),
            'selite' => "{$tiliointi2['selite']} ".t("Jälkilaskennan korjaus"),
          );

          // Tehdään uusi varastotiliöinti oikeilla arvoilla
          kopioitiliointi($tiliointi2['tunnus'], "", $params); // kopioidaan vanha tiliöintirivi

          /*
            Ollaan suljetulla kaudella ja päivitetään vain kate_korjattu
          */
          $query = "UPDATE lasku SET kate_korjattu = round(IFNULL(kate_korjattu, kate) - {$kate_muuttuja} + {$uusikate}, 2) WHERE tunnus = '{$tilausrivi['ltunnus']}'";
          $katekorjres = pupe_query($query);

          if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

          $query = "UPDATE lasku SET kate_korjattu = round(IFNULL(kate_korjattu, kate) - {$kate_muuttuja} + {$uusikate}, 2) WHERE tunnus = '{$tilausrivi['oltunnus']}'";
          $katekorjres = pupe_query($query);

          if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

          $query = "UPDATE tilausrivi SET kate_korjattu = round({$uusikate}, 6) WHERE tunnus = '{$tilausrivi['ttunnus']}'";
          $katekorjres = pupe_query($query);

          if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
        }
      }
    }

  }
}

if (!function_exists('korjaatapahtuma')) {
  function korjaatapahtuma($tapahtuma_tunnus, $selite_txt, $upd_hinta, $upd_kplhinta = FALSE) {
    global $kukarow, $yhtiorow, $jalkilaskenta_debug, $jalkilaskenta_debug_text;

    // Haetaan ensimmäinen
    $query = "SELECT LEFT(laadittu, 10) laadittu, rivitunnus, tunnus, selite, kplhinta, hinta, kpl, laji
              FROM tapahtuma
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$tapahtuma_tunnus}'";
    $tun_chk_res = pupe_query($query);

    if (mysql_num_rows($tun_chk_res) != 1) return FALSE;

    $tun_chk_row = mysql_fetch_assoc($tun_chk_res);

    // Jos tämä selite on jo selitteessä, ei lisätä sitä uudestaan
    if ($selite_txt != "" and strpos($tun_chk_row['selite'], $selite_txt) !== FALSE) {
      $selite_txt = "";
    }

    if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

    // Inventoinnissa korjaustapahtuman rivitunnus on alkuperäisen inventointitapahtuman tunnus (kaikissa muissa keisseissä rivitunnus pointtaa tilausriviin)
    if ($tun_chk_row['laji'] == 'Inventointi' or $tun_chk_row['laji'] == 'siirto') $tun_chk_row['rivitunnus'] = $tun_chk_row['tunnus'];

    // Poistetaan vanhat korjaustapahtumat
    // Tämä keissi on tärkeä, jos tilikauden alkua ollaan siirretty taaksepäin historiaan
    $query = "DELETE
              FROM tapahtuma
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND laji       = 'korjaus'
              AND rivitunnus > 0
              AND rivitunnus = '{$tun_chk_row['rivitunnus']}'
              AND laadittu   >= '{$yhtiorow['tilikausi_alku']} 00:00:00'";
    $del_res = pupe_query($query);

    if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

    $tilikausi_alku = (int) str_replace("-", "", $yhtiorow["tilikausi_alku"]);
    $korjaus_alku   = (int) str_replace("-", "", $tun_chk_row["laadittu"]);

    // Korjataan tapahtumia suljetulla tilikaudella
    if ($tilikausi_alku > $korjaus_alku) {

      // Etsitään tuorein tämän tapahtumanrivin hinta
      // Koska voidaan jälkilaskea tulo uudestaan, mutta avoin kausi on eri
      // Esim. aluksi tilikausi on suljettu 10.03.2013, tehdään korjaukset 10.03.2013 päivälle
      // Tämän jälkeen siirretään tilikausi alkamaan 20.03.2013, uudet korjaukset tehdään 20.03.2013 päivälle
      // Hinta halutaan hakea kuitenkin 10.03.2013 korjauksilta
      $query = "SELECT kplhinta, hinta
                FROM tapahtuma
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND laji       IN ('{$tun_chk_row['laji']}', 'korjaus')
                AND rivitunnus > 0
                AND rivitunnus = '{$tun_chk_row['rivitunnus']}'
                ORDER BY laadittu DESC, tunnus DESC
                LIMIT 1";
      $hinta_chk_res = pupe_query($query);
      $hinta_chk_row = mysql_fetch_assoc($hinta_chk_res);

      $params = array(
        'kpl'     => ($tun_chk_row['kpl'] * -1),
        'selite'   => "{$tun_chk_row['selite']}{$selite_txt}",
        'laadittu'   => "{$yhtiorow['tilikausi_alku']} 00:00:00",
        'laatija'   => $kukarow['kuka'],
        'laji'    => "korjaus",
      );

      $params['hinta'] = $hinta_chk_row['hinta'];
      if ($upd_kplhinta !== FALSE) $params['kplhinta'] = $hinta_chk_row['kplhinta'];

      // Laitetaan inventoinnin korjaustapahtumassa alkuperäisen tapahtuman tunnus rivitunnukseksi
      if ($tun_chk_row['laji'] == 'Inventointi' or $tun_chk_row['laji'] == 'siirto') $params['rivitunnus'] = $tun_chk_row['tunnus'];

      kopioitapahtuma($tapahtuma_tunnus, $params);

      $params = array(
        'selite'   => "{$tun_chk_row['selite']}{$selite_txt}",
        'laadittu'  => "{$yhtiorow['tilikausi_alku']} 00:00:00",
        'laatija'   => $kukarow['kuka'],
        'laji'     => "korjaus",
      );

      // Laitetaan inventoinnin korjaustapahtumassa alkuperäisen tapahtuman tunnus rivitunnukseksi
      if ($tun_chk_row['laji'] == 'Inventointi' or $tun_chk_row['laji'] == 'siirto') $params['rivitunnus'] = $tun_chk_row['tunnus'];

      $params['hinta'] = $upd_hinta;
      if ($upd_kplhinta !== FALSE) $params['kplhinta'] = $upd_kplhinta;

      kopioitapahtuma($tapahtuma_tunnus, $params);
    }
    else {
      $selitelisa = $selite_txt != "" ? ", selite = concat(selite, '{$selite_txt}')" : "";
      $kplhintalisa = $upd_kplhinta !== FALSE ? ", kplhinta = '{$upd_kplhinta}'" : "";

      // Päivitetään korjattava tapahtuma
      $query = "UPDATE tapahtuma SET
                hinta       = '{$upd_hinta}'
                {$kplhintalisa}
                {$selitelisa}
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$tapahtuma_tunnus}'";
      $paivresult = pupe_query($query);

      if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
    }
  }
}

if (!function_exists("hae_varastotilit")) {
  function hae_varastotilit($tuote_row) {
    global $yhtiorow, $varastonmuutostili, $varastotili;

    $varastotili = $yhtiorow["varasto"];
    $varastonmuutostili = $yhtiorow["varastonmuutos"];

    // Katsotaan, onko tuote raaka-aine ja jos on, tiliöidään muutokset raaka-ainetileille
    if ($yhtiorow["raaka_aine_tiliointi"] == "Y") {
      if ($tuote_row["tuotetyyppi"] == "R") {
        $varastotili = $yhtiorow["raaka_ainevarasto"];
        $varastonmuutostili = $yhtiorow["raaka_ainevarastonmuutos"];
      }
    }

    return array($varastotili, $varastonmuutostili);
  }
}

$jlerror=0;

if ($tuoteno != '') {

  if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<hr><font class='message'>".t("Aloitellaan käsittely tuotteelle")." $tuoteno.</font><br>";

  //Tuote
  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '$kukarow[yhtio]'
            and tuoteno = '$tuoteno'";
  $result = pupe_query($query);
  $tuote = mysql_fetch_assoc($result);

  list($varastotili, $varastonmuutostili) = hae_varastotilit($tuote);

  if (mysql_num_rows($result) == 1 and ($tuote["sarjanumeroseuranta"] == "S" or $tuote["sarjanumeroseuranta"] == "G")) {
    //Tähän haaraan mennään jos tuotteella on sarjanumeroseurantaa ja varastonarvo lasketaan suoraan osto ja myyntiriveiltä
    if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "Tuotenumero $tuoteno on sarjanumeroseurannassa<br>";

    $query  = "SELECT tilausrivi.yhtio, tapahtuma.tunnus tapahtuma_tunnus
               FROM tilausrivi
               JOIN tapahtuma ON (tapahtuma.yhtio = tilausrivi.yhtio and tapahtuma.rivitunnus = tilausrivi.tunnus AND tapahtuma.laji != 'korjaus')
               WHERE tilausrivi.yhtio         = '$kukarow[yhtio]'
               and ((tilausrivi.tyyppi = 'O' and tilausrivi.kpl > 0) or (tilausrivi.tyyppi = 'L' and tilausrivi.kpl < 0))
               and tilausrivi.laskutettuaika != '0000-00-00'
               and tilausrivi.tunnus          = '$rivitunnus'
               and tilausrivi.tuoteno         = '$tuoteno'";
    $rxiviresult = pupe_query($query);

    if (mysql_num_rows($rxiviresult) == 1 and is_numeric($uusihinta)) {

      $rxivirow = mysql_fetch_assoc($rxiviresult);

      // Päivitetään ostotilausrivin rivihinta
      $query  = "UPDATE tilausrivi
                 SET rivihinta = round($uusihinta*kpl,2)
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$rivitunnus'
                 and tuoteno = '$tuoteno'";
      $result = pupe_query($query);

      $selite_txt = ",<br>".t("Jälkilaskennan ostohinta", $yhtiorow['kieli']).": $uusihinta (".tv1dateconv(date("Y-m-d")).")";

      // Päivitetään korjattava tapahtuma
      korjaatapahtuma($rxivirow['tapahtuma_tunnus'], $selite_txt, $uusihinta, $uusihinta);

      // Haetaan kaikki tiedot sarjanumerosta
      $query = "SELECT distinct myyntirivitunnus
                FROM sarjanumeroseuranta
                WHERE yhtio         = '$kukarow[yhtio]'
                and tuoteno         = '$tuoteno'
                and ostorivitunnus  = '$rivitunnus'
                and ostorivitunnus != 0";
      $sarjares = pupe_query($query);
      if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

      while ($sarjarow = mysql_fetch_assoc($sarjares)) {

        // Hetaaan laskutetut myntirivit
        $query = "SELECT tilausrivi.*, tapahtuma.laadittu, tapahtuma.tunnus tapahtuma_tunnus
                  FROM tilausrivi
                  JOIN tapahtuma ON (tapahtuma.yhtio = tilausrivi.yhtio and tapahtuma.rivitunnus = tilausrivi.tunnus and tapahtuma.laji != 'korjaus')
                  WHERE tilausrivi.yhtio         = '$kukarow[yhtio]'
                  and tilausrivi.tunnus          = '$sarjarow[myyntirivitunnus]'
                  and tilausrivi.tyyppi          = 'L'
                  and tilausrivi.uusiotunnus    != 0
                  and tilausrivi.laskutettuaika != '0000-00-00'
                  and tilausrivi.kpl            != 0";
        $rivires = pupe_query($query);

        if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

        if (mysql_num_rows($rivires) == 1) {
          $rivirow = mysql_fetch_assoc($rivires);

          // Haetaan nyt tämän myyntirivin kaikki ostohinnat
          $uusisarjahin = sarjanumeron_ostohinta("myyntirivitunnus", $rivirow["tunnus"]);

          if ($uusisarjahin != 0 and $uusihinta <> $uusisarjahin) {
            if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>Tän tuotteen ostohinta:$uusihinta - Myyyntirivin ($rivirow[tunnus]) ostohinta keskimäärin:$uusisarjahin</font><br>";

            $uusihinta = $uusisarjahin;
          }

          $uusikehahin = $uusihinta;

          // Korjaus
          if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>Korjataan in-out hintoja! Laskuja (1) Uusi hinta: $uusihinta $rivirow[uusiotunnus]</font><br>";

          korjaatapahtuma($rivirow['tapahtuma_tunnus'], "", $uusihinta, FALSE);
          korjaalaskut($tuoteno, $uusihinta, $rivirow["laadittu"], $rivirow["laadittu"], $rivirow["tunnus"]);
        }
        else {
          if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>Ei korjattavaa (tuotetta ei ole myyty)! Uusi hinta: $uusihinta $rivirow[uusiotunnus]</font><br>";
        }
      }
    }
    else {
      echo "<font class='error'>".t("Tilausriviä tai hintaa ei löydy")."</font><br>";
      $jlerror=1;
    }
  }
  elseif (mysql_num_rows($result) == 1) {
    //Tähän haaraan mennään jos tuotteella ei ole sarjanumeroseurantaa eli ylläpidetään keskihankintahintaa tuotteella

    //Alkusaldo
    $query  = "SELECT sum(saldo) saldo
               FROM tuotepaikat
               WHERE yhtio = '$kukarow[yhtio]'
               AND tuoteno = '$tuoteno'";
    $result = pupe_query($query);
    $saldo  = mysql_fetch_assoc($result);

    //Kääntöpiste
    $query = "SELECT *
              FROM tapahtuma
              WHERE yhtio    = '$kukarow[yhtio]'
              AND laji       in ('tulo','valmistus')
              AND tuoteno    = '$tuoteno'
              AND rivitunnus = '$rivitunnus'";
    $result = pupe_query($query);

    //taaksepäin yhteensopivuuden vuoksi kokeillaan vielä näin jos ei rivitunnuksella löydy
    if (mysql_num_rows($result) == 0) {
      echo "<font class='error'>".t("Tarkkaa kääntöpisteen tapahtumaa ei löytynyt! Käytetään päivän ensimmäistä tuloa")." $rivitunnus @ $pvm.</font><br>";

      $query = "SELECT *
                FROM tapahtuma
                WHERE yhtio    = '$kukarow[yhtio]'
                AND tuoteno    = '$tuoteno'
                AND rivitunnus > 0
                AND left(laadittu,10) = '$pvm'
                AND laji       in ('tulo','valmistus')
                LIMIT 1";
      $result = pupe_query($query);
    }
    //Jos samalla rivitunuksella löytyy monta tapahtumaa, niin oletamme että olemme saaneet sisään $tapahtumatunnus muuttujan
    elseif (mysql_num_rows($result) > 1) {
      echo "<font class='error'>".t("Samalla rivitunnuksella löytyi useita kääntöpisteitä! Käytämme sekä rivitunnusta että tapahtumatunnusta.")."</font><br>";

      $query = "SELECT *
                FROM tapahtuma
                WHERE yhtio    = '$kukarow[yhtio]'
                AND laji       in ('tulo','valmistus')
                AND tuoteno    = '$tuoteno'
                AND rivitunnus = '$rivitunnus'
                AND tunnus     = '$tapahtumatunnus'";
      $result = pupe_query($query);
    }

    if (mysql_num_rows($result) == 0) {
      echo "<font class='error'>".t("Ei tuloa tuolla päivällä")."</font><br>";
      if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
      $jlerror=1;
    }
    else {
      $tapahtuma = mysql_fetch_assoc($result);
      $pvm = $tapahtuma["laadittu"]; // kääntöpisteen tarkka aika talteen
    }

    if (($tuote['epakurantti25pvm'] != '0000-00-00') or ($tuote['epakurantti50pvm'] != '0000-00-00') or ($tuote['epakurantti75pvm'] != '0000-00-00') or ($tuote['epakurantti100pvm'] != '0000-00-00')) {
      echo "<font class='error'>".t("Tuote %s on epäkurantti! Jälkilaskentaa ei suoriteta", "", $tuoteno)."!</font><br>";

      if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
      $jlerror = 1;
    }

    if ($jlerror != 1) {

      $query = "SELECT sum(kpl) saldomuutos, sum(if(laji='Epäkurantti',1,0)) epaku
                FROM tapahtuma
                WHERE yhtio    = '$kukarow[yhtio]'
                AND tuoteno    = '$tuoteno'
                AND laadittu   >= '$pvm'
                AND rivitunnus >= 0
                AND kpl        <> 0";
      $result = pupe_query($query);

      if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

      if (mysql_num_rows($result) > 0) {

        $trow = mysql_fetch_assoc($result);

        // epäkuranttisuutta ei osata poistaa --> ai ai!
        if ($trow['epaku'] > 0) {
          echo "<font class='error'>".t("Tuotteella on epäkuranttisuuden muutos. Emme osaa toimia!")."</font><br>";
          $jlerror=1;
        }

        $seliselilisa = "";
        $uusisaldo = $saldo["saldo"];

        if ($trow["saldomuutos"] != 0) {
          $uusisaldo = round($uusisaldo-$trow["saldomuutos"], 2);
          $seliselilisa = " ($saldo[saldo] - $trow[saldomuutos]) ";
        }

        // tämä muuttuja tulee valmistusten korjauksesta, ja muutellaan saldoa jos niin pitää tehdä
        if (isset($kaantopisteen_saldomuutos) and $kaantopisteen_saldomuutos != 0) {
          $uusisaldo = round($uusisaldo+$kaantopisteen_saldomuutos, 2);
          $seliselilisa .= " + $kaantopisteen_saldomuutos ";
        }

        // haetaan tuotteelle edellinen kehahin
        // skipataan myös siirto, vaikka siellä voikin olla hinta (siirron aikana hinta ei voi muuttua)
        $query = "SELECT hinta, tunnus, rivitunnus
                  FROM tapahtuma
                  WHERE yhtio    = '$kukarow[yhtio]'
                  and tuoteno    = '$tuoteno'
                  and laadittu   < '$pvm'
                  AND rivitunnus >= 0
                  and laji       not in ('poistettupaikka', 'uusipaikka', 'siirto')
                  ORDER BY laadittu DESC, tunnus DESC
                  LIMIT 1";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $trow = mysql_fetch_assoc($result);
          $uusikehahin = $trow["hinta"];

          // Katsotaan löytyykö tälle tapahtumariville korjausrivi ja otetaan edellinen kehahin sieltä
          $query = "SELECT hinta
                    FROM tapahtuma
                    WHERE yhtio    = '{$kukarow['yhtio']}'
                    AND laji       = 'korjaus'
                    AND tuoteno    = '$tuoteno'
                    AND rivitunnus > 0
                    AND rivitunnus = '{$trow['rivitunnus']}'
                    ORDER BY laadittu DESC, tunnus DESC
                    LIMIT 1";
          $hinta_chk_res = pupe_query($query);

          if (mysql_num_rows($hinta_chk_res) == 1) {
            $hinta_chk_row = mysql_fetch_assoc($hinta_chk_res);
            $uusikehahin = $hinta_chk_row['hinta'];
          }
        }
        else {
          $uusikehahin = 0;
        }

        // Nyt ollaan takaisin peruspäivässä.
        if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<br><font class='error'>".t("Olemme kääntöpisteessä. Saldo kääntöpisteessä:")." $uusisaldo $seliselilisa ".t("Kehahin kääntöpisteessä:")." $uusikehahin</font><br>";

        if ($jlerror == 0) {

          // haetaan ostotilausrivin tiedot
          $query = "SELECT *
                    FROM tilausrivi
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tunnus  = '$rivitunnus'";
          $result = pupe_query($query);
          $apujlrow = mysql_fetch_assoc($result);

          // lasketaan uusi rivihinta
          $testiapukala = round($uusihinta * $apujlrow["kpl"], $yhtiorow['hintapyoristys']);

          // päivitetään ostotilausrivin rivihinta
          $query  = "UPDATE tilausrivi
                     SET rivihinta = '$testiapukala'
                     WHERE yhtio = '$kukarow[yhtio]'
                     and tunnus  = '$rivitunnus'";
          $result = pupe_query($query);

          // Nyt lasketaan kaikki hankintahinnat uusiksi
          $query = "SELECT tuoteno, laadittu, laji, kpl, kplhinta, hinta, tunnus, rivitunnus
                    FROM tapahtuma
                    WHERE yhtio     = '$kukarow[yhtio]'
                    AND tuoteno     = '$tuoteno'
                    AND laadittu    >= '$pvm'
                    AND rivitunnus  >= 0
                    AND laji       != 'korjaus'
                    AND kpl         <> 0
                    ORDER BY laadittu, tunnus";
          $result = pupe_query($query);

          if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

          if (mysql_num_rows($result) > 0) {
            $jlerror   = 0;
            $eka     = 1;
            $vlaadittu   = "3000-01-01 00:00:00"; // varmuuden vuoks ettei korjailla nollasta lähtien jos käy jotain todellaa weird...
            $korjattavia_laskuja = 0;

            if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>Lasketaan uudet hinnat tuotteelle: $tuote[tuoteno] Sort: Asc</font><br><br>";

            $alkuperainen_hinta_ostotilaukselta = $uusihinta;

            while ($trow = mysql_fetch_assoc($result) and $jlerror == 0) {

              // Jos samalla sekunilla on useita tuloja, joudumme muokkaamaan $uusihinta-muuttujaa (rivi ~873). Tässä palautetaan oikea arvo.
              $uusihinta = $alkuperainen_hinta_ostotilaukselta;

              // Inventoinnissa korjaustapahtuman rivitunnus on alkuperäisen inventointitapahtuman tunnus (kaikissa muissa keisseissä rivitunnus pointtaa tilausriviin)
              $rivitunnuslisa = ($trow['laji'] == 'Inventointi' or $trow['laji'] == 'siirto') ? $trow['tunnus'] : $trow['rivitunnus'];

              // Nollataan selite
              $selite_txt = "";

              // Haetaan tuotteen edellinen kehari
              // Katsotaan löytyykö tälle tapahtumariville korjausrivi ja otetaan edellinen kehahin sieltä
              $query = "SELECT kplhinta, hinta
                        FROM tapahtuma
                        WHERE yhtio    = '{$kukarow['yhtio']}'
                        AND laji       = 'korjaus'
                        AND rivitunnus > 0
                        AND rivitunnus = '{$rivitunnuslisa}'
                        ORDER BY laadittu DESC, tunnus DESC
                        LIMIT 1";
              $hinta_chk_res = pupe_query($query);

              if (mysql_num_rows($hinta_chk_res) == 1) {
                $trow_temp = mysql_fetch_assoc($hinta_chk_res);

                $trow['hinta'] = $trow_temp['hinta'];
                $trow['kplhinta'] = $trow_temp['kplhinta'];
              }

              $edellinen_tapahtuman_kehahin = $trow['hinta'];

              // kun löydetään eka tulo, voidaan alkaa hommiin...
              if (($trow['laji'] == 'tulo' or $trow['laji'] == 'valmistus') and $eka == 1) {
                $vlaadittu = $trow['laadittu'];
                $eka = 0;
              }

              // jos kyseessä on tulo ja ollaan jo löydetty eka tulo
              if (($trow['laji'] == 'tulo' or $trow['laji'] == 'valmistus') and $eka == 0) {

                // Jos ollaan käsittelemässä kääntöpistettä laitetaan keskihankintahinnaksi uusi OIKEA keskihankintahinta
                if ($trow['rivitunnus'] == $rivitunnus) {
                  $trow['kplhinta'] = $uusihinta;
                  $selite_txt = ",<br>".t("Jälkilaskennan ostohinta", $yhtiorow['kieli']).": $uusihinta (".tv1dateconv(date("Y-m-d")).")";
                }
                else {
                  $uusihinta = $trow['kplhinta'];
                }

                // jos ollaan käsitelty jo jotain laskuja, niin nyt on sitte alkamassa uusi tulo ni korjataan laskut ja nollataan laskurit
                if ($korjattavia_laskuja != 0) {
                  if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>Korjataan $tuoteno! Saldo: $uusisaldo Keskihinta: $uusikehahin Alku: $vlaadittu Loppu: $trow[laadittu] Case: 1</font><br>";
                  korjaalaskut($tuoteno, $uusikehahin, $vlaadittu, $trow['laadittu'], 0);
                  $korjattavia_laskuja = 0;
                }

                if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>Tulo $trow[laadittu]. $trow[kpl] kappaletta. Saldo ennen tuloa: $uusisaldo Kehahin ennen tuloa: $uusikehahin Sort: Asc</font><br>";

                // kehahin matikka. lasketaan uusi kehahin jos jakolaskun molemmat puolet on plussalla, sekä saldo ENNEN tapahtumaa on plussalla
                if ($uusisaldo != 0 and $uusisaldo + $trow['kpl'] != 0 and $uusikehahin * $uusisaldo + $trow['kplhinta'] * $trow['kpl'] != 0) {

                  if ($jalkilaskenta_debug >= 1) {
                    $jalkilaskenta_debug_text .= "<font class='message'>JÄLKILASKENTA: Uusi kehahin (Matikka): ($uusikehahin * $uusisaldo + $trow[kplhinta] * $trow[kpl]) / ($uusisaldo + $trow[kpl])";
                  }

                  $uusikehahin = round(($uusikehahin * $uusisaldo + $trow['kplhinta'] * $trow['kpl']) / ($uusisaldo + $trow['kpl']), 6);

                  if ($jalkilaskenta_debug >= 1) {
                    $jalkilaskenta_debug_text .= " = $uusikehahin</font><br>";
                  }
                }
                else {
                  // jos saldo on nolla ennen tuloa tai tulon jälkeen niin kehari on ostari
                  $uusikehahin = $trow['kplhinta'];

                  if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>JÄLKILASKENTA: Uusi kehahin (Ei matikkaa): uusikehahin = ostohinta = $uusikehahin</font><br>";
                }

                // otetaan tämä päivä laskennan alkupäiväksi
                $vlaadittu = $trow['laadittu'];

                // summaillaan uutta saldoa
                $uusisaldo = round($uusisaldo+$trow['kpl'], 2);
              }

              // jos kyseessä on laskutus ja ollaan löydetty eka tulo, summaillaan kappaleita
              if ($trow['laji'] == 'laskutus' and $eka == 0) {
                // haetaan myyntitilausrivi
                $query = "SELECT kpl, rivihinta, kate
                          from tilausrivi
                          where yhtio = '$kukarow[yhtio]'
                          and tunnus  = '$trow[rivitunnus]'";
                $myyntitil_apures = pupe_query($query);
                $myyntitil_apurow = mysql_fetch_assoc($myyntitil_apures);

                $myyntitil_uusikate = $myyntitil_apurow['rivihinta'] - $myyntitil_apurow['kpl'] * $uusikehahin;

                // plussaillaan tätä vaan jos keskihankintahinta tai kate muuttuu, niin tiedetään pitääkö tehdä jälkilaskentaa
                if ($trow["hinta"] != $uusikehahin or $myyntitil_apurow["kate"] != $myyntitil_uusikate) {
                  $korjattavia_laskuja++;
                }

                // summaillaan uutta saldoa
                $uusisaldo = round($uusisaldo+$trow['kpl'], 2);
              }

              // jos kyseessä on varastosiirto ja ollaan löydetty jo eka tulo
              if ($trow['laji'] == 'siirto' and $eka == 0) {

                // katotaan saadaanko tehdä tiliöintejä
                // tehdään tilikausi_alku ja korjauksen_alku muuttujista PHP:ssä vertailukelpoisia
                $tilikausi_alku = (int) str_replace("-", "", $yhtiorow["tilikausi_alku"]);
                $korjaus_alku   = (int) str_replace("-", "", substr($trow["laadittu"], 0, 10));

                // Haetaan tiliöinnit, näitä on kolme (varasto ja varastonmuutos ja 'osto')
                $query = "SELECT *
                          FROM tiliointi
                          WHERE yhtio         = '{$kukarow['yhtio']}'
                          AND tapahtumatunnus = '{$trow['tunnus']}'
                          AND tapahtumatunnus > 0
                          AND korjattu        = ''
                          LIMIT 3";
                $sum_chk_res = pupe_query($query);

                while ($sum_chk_row = mysql_fetch_assoc($sum_chk_res)) {

                  if ($sum_chk_row["summa"] > 0) {
                    $vasisum = round(abs($trow["kpl"] * $uusikehahin), 2);
                  }
                  else {
                    $vasisum = round(abs($trow["kpl"] * $uusikehahin) * -1, 2);
                  }

                  $pvm_chk = $tilikausi_alku > $korjaus_alku ? $yhtiorow['tilikausi_alku'] : "";

                  if ($pvm_chk == "") {

                    $params = array(
                      'summa'       => $vasisum,
                      'summa_valuutassa'   => $vasisum,
                      'laatija'       => $kukarow['kuka'],
                      'laadittu'       => date('Y-m-d H:i:s'),
                    );

                    // Tehdään uusi tiliöinti
                    kopioitiliointi($sum_chk_row['tunnus'], "", $params);

                    // yliviivataan alkuperäinen tiliöinti
                    $query = "UPDATE tiliointi
                              SET korjattu = '$kukarow[kuka]',
                              korjausaika = now()
                              WHERE yhtio = '$kukarow[yhtio]'
                              AND tunnus  = '$sum_chk_row[tunnus]'";
                    $invupd = pupe_query($query);

                    if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
                  }
                  else {

                    $params = array(
                      'tapvm'       => $yhtiorow['tilikausi_alku'],
                      'tapahtumatunnus'  => 0,
                      'summa'       => round($sum_chk_row["summa"] * -1, 2),
                      'summa_valuutassa'   => round($sum_chk_row["summa"] * -1, 2),
                      'laatija'       => $kukarow['kuka'],
                      'laadittu'       => date('Y-m-d H:i:s'),
                    );

                    // Tehdään vastakirjaus tilikauden ensimmäiselle päivälle
                    kopioitiliointi($sum_chk_row['tunnus'], "", $params);

                    $params = array(
                      'tapvm'       => $yhtiorow['tilikausi_alku'],
                      'summa'       => $vasisum,
                      'summa_valuutassa'   => $vasisum,
                      'laatija'       => $kukarow['kuka'],
                      'laadittu'       => date('Y-m-d H:i:s'),
                    );

                    // Tehdään uusi kirjaus oikeilla hinnoilla tilikauden ensimmäiselle päivälle
                    kopioitiliointi($sum_chk_row['tunnus'], "", $params);

                    // Nollataan alkuperäisen tiliöinnin tapahtumatunnus, ku se ei oo enää "voimassa"
                    $query = "UPDATE tiliointi
                              SET tapahtumatunnus  = 0
                              WHERE yhtio = '$kukarow[yhtio]'
                              AND tunnus  = '$sum_chk_row[tunnus]'";
                    $invupd = pupe_query($query);
                  }
                }
              }

              // jos kyseessä on inventointi ja ollaan löydetty jo eka tulo
              if ($trow['laji'] == 'Inventointi' and $eka == 0) {

                // etsitään tuotteen tiliöinti
                $query = "SELECT *
                          FROM lasku use index (tila_viite)
                          WHERE yhtio = '{$kukarow['yhtio']}'
                          AND tila    = 'X'
                          AND viite   = '{$trow['tunnus']}'";
                $invresult = pupe_query($query);

                if (mysql_num_rows($invresult) == 1) {
                  // löydettiin inventointi tosite
                  $invrow = mysql_fetch_assoc($invresult);

                  if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>Korjattiin tiliöinti! ".t("Inventoitu").": $trow[tuoteno] @ $trow[laadittu]</font><br>";

                  // haetaan varastotiliöinti
                  $query = "SELECT *
                            FROM tiliointi
                            WHERE yhtio  = '$kukarow[yhtio]'
                            AND ltunnus  = '$invrow[tunnus]'
                            AND tilino   = '{$varastotili}'
                            and korjattu = ''
                            ORDER BY tapvm DESC, tunnus DESC
                            LIMIT 1";
                  $invupd = pupe_query($query);
                  $invrow = mysql_fetch_assoc($invupd);

                  if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";

                  // katotaan saadaanko tehdä tiliöintejä
                  // tehdään tilikausi_alku ja korjauksen_alku muuttujista PHP:ssä vertailukelpoisia
                  $tilikausi_alku = (int) str_replace("-", "", $yhtiorow["tilikausi_alku"]);
                  $korjaus_alku   = (int) str_replace("-", "", substr($trow["laadittu"], 0, 10));

                  // Lasketaan onko luku muuttunut
                  $inventoinnin_varastonmuutos = round($trow["kpl"] * $uusikehahin - $invrow['summa'], 2);

                  // Tehdään muutoksia vaan jos summa muuttuu
                  if ($inventoinnin_varastonmuutos != 0) {

                    // Haetaan kaikki yhtiön mahdolliset varastotilit
                    $query = "SELECT ifnull(group_concat(DISTINCT concat('\'', avainsana.selitetark_2, '\'') SEPARATOR ', '), '') tilit
                              FROM avainsana
                              JOIN tili ON (tili.yhtio = avainsana.yhtio AND tili.tilino = avainsana.selitetark_2)
                              WHERE avainsana.yhtio       = '{$kukarow['yhtio']}'
                              AND avainsana.laji          = 'INVEN_LAJI'
                              AND avainsana.selitetark_2 != ''";
                    $invupd = pupe_query($query);
                    $inv_varasto_tilit = mysql_fetch_assoc($invupd);

                    $varasto_tilit = "'{$yhtiorow['varasto']}', '{$yhtiorow['varastonmuutos']}', '{$yhtiorow['varastonmuutos_inventointi']}'";

                    if ($yhtiorow["raaka_aine_tiliointi"] == "Y") {
                      $varasto_tilit .= ", '{$yhtiorow["raaka_ainevarasto"]}', " .
                        "'{$yhtiorow["raaka_ainevarastonmuutos"]}'";
                    }

                    if ($inv_varasto_tilit['tilit'] != '') {
                      $varasto_tilit .= ", {$inv_varasto_tilit['tilit']}";
                    }

                    // Katsotaan onko tositteella muita kun varastokirjauksia
                    $query = "SELECT count(*) luku
                              FROM tiliointi
                              WHERE yhtio  = '{$kukarow['yhtio']}'
                              AND ltunnus  = '{$invrow['ltunnus']}'
                              AND tilino   NOT IN ($varasto_tilit)
                              AND korjattu = ''";
                    $invupd = pupe_query($query);
                    $invcount = mysql_fetch_assoc($invupd);

                    $pvm_chk = $tilikausi_alku > $korjaus_alku ? $yhtiorow['tilikausi_alku'] : "";

                    // jos tositteella on vaan varastokirjauksia, niin korjataan tosite
                    if ($invcount['luku'] == 0) {

                      // Ollaan avoimella tilikaudella
                      if ($pvm_chk == "") {

                        // Haetaan varastonmuutostiliöinti
                        $query = "SELECT *
                                  FROM tiliointi
                                  WHERE yhtio   = '{$kukarow['yhtio']}'
                                  AND tilino   != '{$varastotili}'
                                  AND ltunnus   = '{$invrow['ltunnus']}'
                                  AND korjattu  = ''
                                  ORDER BY tapvm DESC, tunnus DESC
                                  LIMIT 1";
                        $sum_chk_res = pupe_query($query);
                        $sum_chk_row = mysql_fetch_assoc($sum_chk_res);

                        $params = array(
                          'summa'       => round($trow["kpl"] * $uusikehahin * -1, 2),
                          'summa_valuutassa'   => round($trow["kpl"] * $uusikehahin * -1, 2),
                          'laatija'       => $kukarow['kuka'],
                          'laadittu'       => date('Y-m-d H:i:s'),
                        );

                        // Tehdään uusi varastonmuutostiliöinti
                        kopioitiliointi($sum_chk_row['tunnus'], "", $params);

                        $params = array(
                          'summa'       => round($trow["kpl"] * $uusikehahin, 2),
                          'summa_valuutassa'   => round($trow["kpl"] * $uusikehahin, 2),
                          'laatija'       => $kukarow['kuka'],
                          'laadittu'       => date('Y-m-d H:i:s'),
                        );

                        // Tehdään uusi varastotiliöinti
                        kopioitiliointi($invrow['tunnus'], "", $params);

                        // yliviivataan vanhat tiliöinnit
                        $query = "UPDATE tiliointi
                                  SET korjattu = '$kukarow[kuka]',
                                  korjausaika  = now()
                                  WHERE yhtio  = '$kukarow[yhtio]'
                                  AND ltunnus  = '$invrow[ltunnus]'
                                  AND tunnus   IN ('{$invrow['tunnus']}','{$sum_chk_row['tunnus']}')
                                  AND korjattu = ''";
                        $invupd = pupe_query($query);

                        if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
                      }
                      else {

                        // Haetaan varastonmuutostiliöinnin summa
                        $query = "SELECT *
                                  FROM tiliointi
                                  WHERE yhtio   = '{$kukarow['yhtio']}'
                                  AND tilino   != '{$varastotili}'
                                  AND ltunnus   = '{$invrow['ltunnus']}'
                                  AND korjattu  = ''
                                  ORDER BY tapvm DESC, tunnus DESC
                                  LIMIT 1";
                        $sum_chk_res = pupe_query($query);
                        $sum_chk_row = mysql_fetch_assoc($sum_chk_res);

                        $params = array(
                          'tapvm'       => $yhtiorow['tilikausi_alku'],
                          'summa'       => round($sum_chk_row['summa'] * -1, 2),
                          'summa_valuutassa'   => round($sum_chk_row['summa_valuutassa'] * -1, 2),
                          'laatija'       => $kukarow['kuka'],
                          'laadittu'       => date('Y-m-d H:i:s'),
                        );

                        // Tehdään vastakirjaus alkuperäiselle varastonmuutostiliöinnille tilikauden ensimmäiselle päivälle
                        kopioitiliointi($sum_chk_row['tunnus'], "", $params); // kopioidaan vanha tiliöintirivi

                        $params = array(
                          'tapvm'       => $yhtiorow['tilikausi_alku'],
                          'summa'       => round($trow['kpl'] * $uusikehahin * -1, 2),
                          'summa_valuutassa'   => round($trow['kpl'] * $uusikehahin * -1, 2),
                          'laatija'       => $kukarow['kuka'],
                          'laadittu'       => date('Y-m-d H:i:s'),
                        );

                        // Tehdään uusi varastonmuutostiliöinti oikeilla hinnoilla tilikauden ensimmäiselle päivälle
                        kopioitiliointi($sum_chk_row['tunnus'], "", $params); // kopioidaan vanha tiliöintirivi

                        $params = array(
                          'tapvm'       => $yhtiorow['tilikausi_alku'],
                          'summa'       => round($invrow['summa'] * -1, 2),
                          'summa_valuutassa'   => round($invrow['summa_valuutassa'] * -1, 2),
                          'laatija'       => $kukarow['kuka'],
                          'laadittu'       => date('Y-m-d H:i:s'),
                        );

                        // Tehdään vastakirjaus alkuperäiselle varastotiliöinnille tilikauden ensimmäiselle päivälle
                        kopioitiliointi($invrow['tunnus'], "", $params); // kopioidaan vanha tiliöintirivi

                        $params = array(
                          'tapvm'       => $yhtiorow['tilikausi_alku'],
                          'summa'       => round($trow['kpl'] * $uusikehahin, 2),
                          'summa_valuutassa'   => round($trow['kpl'] * $uusikehahin, 2),
                          'laatija'       => $kukarow['kuka'],
                          'laadittu'       => date('Y-m-d H:i:s'),
                        );

                        // Tehdään uusi varastotiliöinti oikeilla hinnoilla tilikauden ensimmäiselle päivälle
                        kopioitiliointi($invrow['tunnus'], "", $params); // kopioidaan vanha tiliöintirivi
                      }
                    }
                    else {
                      // Jos on jotain muita kirjauksia (käsin säädetty, omaan käyttöönotto, tms)
                      // korjataan vaan VARASTO kirjaus, niin tosite jää heittämään ja sen löytää helposti

                      // Ollaan avoimella tilikaudella
                      if ($pvm_chk == "") {

                        // yliviivataan varastotiliöinti
                        $query = "UPDATE tiliointi
                                  SET korjattu = '$kukarow[kuka]',
                                  korjausaika  = now()
                                  WHERE yhtio  = '$kukarow[yhtio]'
                                  AND ltunnus  = '$invrow[ltunnus]'
                                  AND korjattu = ''
                                  AND tilino   = '{$varastotili}'";
                        if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
                        $invupd = pupe_query($query);

                        // kirjotetaan uudet tiliönnit
                        $query = "INSERT INTO tiliointi SET
                                  yhtio    = '$kukarow[yhtio]',
                                  ltunnus  = '$invrow[ltunnus]',
                                  tilino   = '{$varastotili}',
                                  kustp    = '{$invrow['kustp']}',
                                  kohde    = '{$invrow['kohde']}',
                                  projekti = '{$invrow['projekti']}',
                                  tapvm    = '$invrow[tapvm]',
                                  summa    = round($trow[kpl] * $uusikehahin,2),
                                  vero     = 0,
                                  lukko    = '',
                                  selite   = '$invrow[selite]',
                                  laatija  = '$kukarow[kuka]',
                                  laadittu = now()";
                        if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
                        $invupd = pupe_query($query);
                      }
                      else {

                        $params = array(
                          'tapvm'       => $yhtiorow['tilikausi_alku'],
                          'summa'       => round($invrow['summa'] * -1, 2),
                          'summa_valuutassa'   => round($invrow['summa_valuutassa'] * -1, 2),
                          'laatija'       => $kukarow['kuka'],
                          'laadittu'       => date('Y-m-d H:i:s'),
                        );

                        // Tehdään vastakirjaus alkuperäiselle varastotiliöinnille tilikauden ensimmäiselle päivälle
                        kopioitiliointi($invrow['tunnus'], "", $params); // kopioidaan vanha tiliöintirivi

                        $params = array(
                          'tapvm'       => $yhtiorow['tilikausi_alku'],
                          'summa'       => round($trow['kpl'] * $uusikehahin, 2),
                          'summa_valuutassa'   => round($trow['kpl'] * $uusikehahin, 2),
                          'laatija'       => $kukarow['kuka'],
                          'laadittu'       => date('Y-m-d H:i:s'),
                        );

                        // Tehdään uusi varastotiliöinti oikeilla hinnoilla tilikauden ensimmäiselle päivälle
                        kopioitiliointi($invrow['tunnus'], "", $params); // kopioidaan vanha tiliöintirivi
                      }
                    }
                  }
                }
                else {
                  $invapuluku = $trow["kpl"] * $uusikehahin;
                  echo "<font class='error'>".t("Törmättiin inventointitapahtumaan, mutta ei osattu korjata sen tiliöintejä! Korjaa tiliöinti käsin!")." ".t("Inventoitu").": $trow[tuoteno] @ $trow[laadittu] ".t("Summa").": $trow[kpl] * $uusikehahin = $invapuluku</font><br>";
                }

                // summaillaan uutta saldoa
                $uusisaldo = round($uusisaldo+$trow['kpl'], 2);
              }

              // jos kyseessä on kulutus ja ollaan löydetty jo eka tulo
              if ($trow['laji'] == 'kulutus' and $eka == 0) {

                if ($trow["hinta"] != $uusikehahin) {
                  // HUOM: $korjattavat_valmistukset_ind, $korjattavat_valmistukset MUUTTUJAT OVAT GLOBAALEJA

                  $query = "SELECT otunnus
                            FROM tilausrivi
                            WHERE yhtio = '$kukarow[yhtio]'
                            AND tunnus  = '$trow[rivitunnus]'";
                  $invres = pupe_query($query);
                  $invrow = mysql_fetch_assoc($invres);

                  // Alustetaan jos ei ole olemassa
                  if (!is_array($korjattavat_valmistukset)) $korjattavat_valmistukset = array();
                  if (!isset($korjattavat_valmistukset_ind)) $korjattavat_valmistukset_ind = 0;

                  // Globaali array johon otetaan ne valmistukset jotka pitäisi korjata
                  // koska jonkun sen valmistuksen raaka-aineen kehahinta muuttui tässä jälkilaskennassa
                  if (($invrow["otunnus"]) > 0 and !in_array($invrow["otunnus"], $korjattavat_valmistukset)) {

                    // Katotaan mihin väliin tämä kuuluu työntää
                    $xx_ok = FALSE;

                    // Insertoitavan valmistuksen valmistusaika
                    $query = "SELECT avg(if(tilausrivi.toimitettuaika='0000-00-00 00:00:00' or tilausrivi.tyyppi not in ('V','W'), NULL, date_format(tilausrivi.toimitettuaika, '%Y%m%d%H%i%s'))) toimitettuaika
                              FROM tilausrivi
                              WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                              AND tilausrivi.otunnus = '$invrow[otunnus]'";
                    $tilre = pupe_query($query);
                    $insaika = mysql_fetch_assoc($tilre);

                    for ($xx_ind = $korjattavat_valmistukset_ind; $xx_ind < count($korjattavat_valmistukset); $xx_ind++) {
                      $thisaika = array("toimitettuaika" => 0);
                      $nextaika = array("toimitettuaika" => 0);

                      $query = "SELECT avg(if(tilausrivi.toimitettuaika='0000-00-00 00:00:00' or tilausrivi.tyyppi not in ('V','W'), NULL, date_format(tilausrivi.toimitettuaika, '%Y%m%d%H%i%s'))) toimitettuaika
                                FROM tilausrivi
                                WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                                AND tilausrivi.otunnus = '".$korjattavat_valmistukset[$xx_ind]."'";
                      $tilre = pupe_query($query);
                      $thisaika = mysql_fetch_assoc($tilre);

                      if (isset($korjattavat_valmistukset[($xx_ind+1)])) {
                        $query = "SELECT avg(if(tilausrivi.toimitettuaika='0000-00-00 00:00:00' or tilausrivi.tyyppi not in ('V','W'), NULL, date_format(tilausrivi.toimitettuaika, '%Y%m%d%H%i%s'))) toimitettuaika
                                  FROM tilausrivi
                                  WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                                  AND tilausrivi.otunnus = '".$korjattavat_valmistukset[($xx_ind+1)]."'";
                        $tilre = pupe_query($query);
                        $nextaika = mysql_fetch_assoc($tilre);
                      }

                      if ($insaika["toimitettuaika"] > $thisaika["toimitettuaika"] and $insaika["toimitettuaika"] < $nextaika["toimitettuaika"]) {
                        array_splice($korjattavat_valmistukset, ($xx_ind+1), 0, $invrow["otunnus"]);
                        $xx_ok = TRUE;
                      }
                    }

                    if (!$xx_ok) {
                      $korjattavat_valmistukset[] = $invrow["otunnus"];
                    }

                    if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>".t("Törmättiin kulutustapahtumaan, valmistus pitäisi laskea uudestaan").": $invrow[otunnus]! Vanha hinta: $trow[hinta], uusi hinta: $uusikehahin</font><br>";
                  }
                }

                // summaillaan uutta saldoa
                $uusisaldo = round($uusisaldo+$trow['kpl'], 2);
              }

              // jos ollaa löydetty eka tulo voidaan aina päivitetään tapahtuma jos se on tulo, valmistus, laskutus, inventointi, kulutus tai siirto
              if ($eka == 0) {

                // päivitetään hinta vain jos hinta on eri
                // tai korjataan tapahtumat jos tapahtuma on lukitulla tilikaudella
                // tai meillä on riville korjaustapahtumia
                // tai ollaan kääntöpisteessa
                if ($edellinen_tapahtuman_kehahin != $uusikehahin or mysql_num_rows($hinta_chk_res) == 1 or $trow['rivitunnus'] == $rivitunnus) {

                  if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>Korjataan tapahtuma! Keskihinta: $uusikehahin Vanha hinta: $trow[hinta]</font><br>";

                  $upd_kplhinta = in_array($trow["laji"], array("kulutus", "tulo", "valmistus")) ? $uusihinta : FALSE;

                  // Päivitetään korjattava tapahtuma
                  korjaatapahtuma($trow['tunnus'], $selite_txt, $uusikehahin, $upd_kplhinta);
                }
              }
            }

            // jos meillä on jotain korjattavia laskuja, jota ei korjattu whilen sisällä ni korjataan ne ny
            if ($korjattavia_laskuja != 0 and $eka == 0) {
              if ($jalkilaskenta_debug >= 1) $jalkilaskenta_debug_text .= "<font class='message'>Korjataan! Saldo: $uusisaldo Keskihinta: $uusikehahin Alku: $vlaadittu Loppu: 3000-01-01 Case: 2</font><br>";
              korjaalaskut($tuoteno, $uusikehahin, $vlaadittu, '3000-01-01 00:00:00', 0);
            }
          }

          // päivitetään lopulta tuotteelle uusi kehahin ja vihahin
          $query = "UPDATE tuote
                    SET kehahin = '$uusikehahin',
                    vihahin     = round('$uusihinta','$yhtiorow[hintapyoristys]'),
                    vihapvm     = now()
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tuoteno = '$tuoteno'";
          if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
          $result = pupe_query($query);
        }
        else {
          echo "<font class='error'>".t("Tuotteella ei tapahtumia")."</font><br><br>";
          if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
          $jlerror=1;
        }
      }
      else {
        echo "<font class='error'>".t("Tuotetta ei löydy")."</font><br>";
        if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
        $jlerror=1;
      }
    }
  }
  else {
    echo "<font class='error'>".t("Tuotetta ei löydy")."</font><br>";

    if ($jalkilaskenta_debug > 1) $jalkilaskenta_debug_text .= "$query<br>";
    $jlerror=1;
  }
}
