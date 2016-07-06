<?php

echo "<pre>";

if (!$fd = fopen($filename, "r")) die("Filen '$filename' avaus epäonnistui!");

$toimpvm = date("Y")."-".date("n")."-".date("j");

if ($tyyppi == 'yct') {
  echo t("Uusi tilaus")."<br>";
  $asno = '90113';
  echo "<hr>";
  echo t("Tilaus").": ".t("asiakas")." '" . $asno .  "' ";

  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '$kukarow[yhtio]'
            AND ytunnus = '$asno'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo t("Asiakasta ei löydy tai järjestelmässä on usea asiakas samalla ytunnuksella")."<br>";
  }
  else {
    $asiakasrow = mysql_fetch_array($result);

    $tnimi     = $asiakasrow['nimi'];
    $tnimitark   = $asiakasrow['nimitark'];
    $tosoite   = $asiakasrow['osoite'];
    $tpostino   = $asiakasrow['postino'];
    $tpostitp   = $asiakasrow['postitp'];
    $toim_maa   = $asiakasrow['maa'];

    $query = "INSERT into lasku SET
              yhtio_nimi      = '$yhtiorow[nimi]',
              yhtio_osoite    = '$yhtiorow[osoite]',
              yhtio_postino   = '$yhtiorow[postino]',
              yhtio_postitp   = '$yhtiorow[postitp]',
              yhtio_maa       = '$yhtiorow[maa]',
              yhtio_ovttunnus = '$yhtiorow[ovttunnus]',
              yhtio_kotipaikka= '$yhtiorow[kotipaikka]',
              laatija         = '$kukarow[kuka]',
              luontiaika      = NOW(),
              ytunnus         = '$asiakasrow[ytunnus]',
              nimi            = '$asiakasrow[nimi]',
              nimitark        = '$asiakasrow[nimitark]',
              osoite          = '$asiakasrow[osoite]',
              postino         = '$asiakasrow[postino]',
              postitp         = '$asiakasrow[postitp]',
              maa             = '$asiakasrow[maa]',
              toim_nimi       = '$tnimi',
              toim_nimitark   = '$tnimitark',
              toim_osoite     = '$tosoite',
              toim_postino    = '$tpostino',
              toim_postitp    = '$tpostitp',
              toim_maa        = '$toim_maa',
              verkkotunnus    = '$asiakasrow[verkkotunnus]',
              tila            = 'L',
              alatila         = '',
              tilaustyyppi    = 'N',
              valkoodi        = '$yhtiorow[valkoodi]',
              kohdistettu     = 'K',
              erikoisale      = '0',
              varasto         = '14',
              eilahetetta     = 'o',
              laskutusvkopv   = '$asiakasrow[laskutusvkopv]',
              toimaika        = '$toimpvm',
              kerayspvm       = '$toimpvm',
              maksuehto       = '$asiakasrow[maksuehto]',
              toimitustapa    = '$asiakasrow[toimitustapa]',
              toimitusehto    = '$asiakasrow[toimitusehto]',
              myyja           = '$kukarow[tunnus]',
              alv             = '$asiakasrow[alv]',
              ovttunnus       = '$asiakasrow[ovttunnus]',
              toim_ovttunnus  = '$asiakasrow[toim_ovttunnus]',
              chn             = '$asiakasrow[chn]',
              liitostunnus    = '$asiakasrow[tunnus]',
              piiri           = '$asiakasrow[piiri]',
              yhtio           = '$kukarow[yhtio]'";
    $result = pupe_query($query);
    $id = mysql_insert_id($GLOBALS["masterlink"]);

    if (strlen(trim($asiakasrow['laskutus_nimi'])) == 0) {
      $asiakasrow['laskutus_nimi']     = $asiakasrow['nimi'];
      $asiakasrow['laskutus_nimitark']   = $asiakasrow['nimitark'];
      $asiakasrow['laskutus_osoite']     = $asiakasrow['osoite'];
      $asiakasrow['laskutus_postino']   = $asiakasrow['postino'];
      $asiakasrow['laskutus_postitp']   = $asiakasrow['postitp'];
      $asiakasrow['laskutus_maa']     = $asiakasrow['maa'];
    }

    $query = "INSERT INTO laskun_lisatiedot SET
              laskutus_nimi     = '$asiakasrow[laskutus_nimi]',
              laskutus_nimitark = '$asiakasrow[laskutus_nimitark]',
              laskutus_osoite   = '$asiakasrow[laskutus_osoite]',
              laskutus_postino  = '$asiakasrow[laskutus_postino]',
              laskutus_postitp  = '$asiakasrow[laskutus_postitp]',
              laskutus_maa      = '$asiakasrow[laskutus_maa]',
              yhtio             = '$kukarow[yhtio]',
              otunnus           = '$id',
              laatija           = '$kukarow[kuka]',
              luontiaika        = now()";
    $lisatiedot_result = pupe_query($query);

    $kukarow['kesken'] = $id;
    //$laskurow['tunnus'] = $id

    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$id'";
    $result = pupe_query($query);
    $laskurow = mysql_fetch_array($result);

    echo t("nro")." $id<br>";
  }
}

while ((!feof($fd)) and ($error!=1)) {


  $tietue = fgets($fd, 4096);
  $tunnus = substr($tietue, 0, 1);

  if ($tunnus == '@') {
    $mail = 'jep';
    $mailiosoite = substr($tietue, 1);
  }

  if (($tyyppi == 'yct') and ($id != '') and ($tunnus != '@')) {

    $tuoteno = substr($tietue, 0, 20);
    $maara = substr($tietue, 20, 20);
    $rivihinta = substr($tietue, 40, 20);

    $tuoteno = trim($tuoteno);
    $maara = trim($maara);
    $rivihinta = trim($rivihinta);
    $ale = '15';

    if ($tuoteno != '') {
      $kplhinta = abs($rivihinta) / abs($maara);
    }

    $query = "SELECT tuoteno
              FROM tuote
              WHERE yhtio = '$kukarow[yhtio]'
              AND tuoteno = '$tuoteno'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0 and $tuoteno != '') {
      echo t("Tuotenumeroa")." $tuoteno ".t("ei löytynyt korjaa asia muuten ei tapahdu mitään")."!";
      $tilanne = '0';
      break;
    }

    elseif ($tuoteno != '') {

      $tilanne = '1';
      echo t("Uusi rivi")." $id\t$tuoteno\t$maara\t$kplhinta\t$ale\t$rivihinta<br>";

      $query = "SELECT *
                FROM tuote
                WHERE yhtio = '$kukarow[yhtio]'
                AND tuoteno = '$tuoteno'";
      $result = pupe_query($query);
      $trow = mysql_fetch_array($result);

      $loyty = 0;
      $alue = 'Y00';

      if ($trow['ei_saldoa'] == '') {

        $query = "SELECT *
                  FROM tuotepaikat
                  WHERE yhtio   = '$trow[yhtio]'
                  AND tuoteno   = '$trow[tuoteno]'
                  AND hyllyalue = 'Y00'
                  AND hyllynro  = '0'
                  AND hyllytaso = '0'
                  AND hyllyvali = '0'
                  AND saldo     > 0";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 0) {
          $loyty = 0;
        }
        else {
          $alue = 'Y00';
          $loyty = 1;
        }

        if ($loyty == 0) {

          $query = "SELECT *
                    FROM tuotepaikat
                    WHERE yhtio   = '$trow[yhtio]'
                    AND tuoteno   = '$trow[tuoteno]'
                    AND hyllyalue = 'Y01'
                    AND hyllynro  = '0'
                    AND hyllytaso = '0'
                    AND hyllyvali = '0'
                    AND saldo     > 0";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 0) {
            $loyty = 0;
          }
          else {
            $alue = 'Y01';
            $loyty = 1;
          }
        }

        if ($loyty == 0) {

          $query = "SELECT *
                    FROM tuotepaikat
                    WHERE yhtio   = '$trow[yhtio]'
                    AND tuoteno   = '$trow[tuoteno]'
                    AND hyllyalue = 'Y00'
                    AND hyllynro  = '0'
                    AND hyllytaso = '0'
                    AND hyllyvali = '0'";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 0) {
            $loyty = 0;
          }
          else {
            $alue = 'Y00';
            $loyty = 1;
          }
        }

        if ($loyty == 0) {

          $query = "SELECT *
                    FROM tuotepaikat
                    WHERE yhtio   = '$trow[yhtio]'
                    AND tuoteno   = '$trow[tuoteno]'
                    AND hyllyalue = 'Y01'
                    AND hyllynro  = '0'
                    AND hyllytaso = '0'
                    AND hyllyvali = '0'";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 0) {
            $loyty = 0;
          }
          else {
            $alue = 'Y01';
            $loyty = 1;
          }
        }

        if ($loyty == 0) {

          $alue = 'Y00';

          echo t("Perustetaan tuotepaikka")." $alue-0-0-0 ".t("tuotteelle")." $trow[tuoteno]<br>";

          lisaa_tuotepaikka($trow["tuoteno"], $alue, 0, 0, 0, "Tilauksen sisäänluvussa", "", 0, 0, 0);
        }
      }

      if (!in_array($yhtiorow['tuotekommentti_tilausriville'], array('', 'M'))) {
        $trow['tilausrivi_kommentti'] = '';
      }

      $query = "INSERT into tilausrivi set
                varattu        = '$maara',
                tilkpl         = '$maara',
                jt             = '0',
                otunnus        = '$id',
                var            = '',
                kerayspvm      = now(),
                laatija        = '$kukarow[kuka]',
                laadittu       = now(),
                toimitettu     = '',
                toimitettuaika = '',
                toimaika       = now(),
                laskutettu     = '',
                laskutettuaika = '',
                yhtio          = '$trow[yhtio]',
                tuoteno        = '$trow[tuoteno]',
                ale1           = '$ale',
                netto          = '$trow[netto]',
                yksikko        = '$trow[yksikko]',
                try            = '$trow[try]',
                osasto         = '$trow[osasto]',
                kpl            = '0',
                alv            = '$trow[alv]',
                hinta          = '$kplhinta',
                nimitys        = '$trow[nimitys]',
                tyyppi         = 'L',
                kommentti      = '$trow[tilausrivi_kommentti]'";

      if ($trow['ei_saldoa'] != '') $query .= ", keratty = 'saldoton', kerattyaika = now()";

      if ($trow['ei_saldoa'] == '') {
        $query .= ", hyllyalue  = '$alue'";
        $query .= ", hyllynro  = '0'";
        $query .= ", hyllytaso  = '0'";
        $query .= ", hyllyvali  = '0'";
      }

      $rivperustettu += 1;

      $result = pupe_query($query);
    }
  }
  else {
    switch ($tunnus) {
    case '1' :
      echo "<hr>";
      $asno = substr($tietue, 19, 6);
      echo t("Tilaus").": ".t("asiakas")." '" . $asno .  "' ";

      $query = "SELECT ytunnus
                FROM asiakas
                WHERE yhtio = '$kukarow[yhtio]'
                AND ytunnus = '$asno'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        $query = "INSERT INTO asiakas SET
                  yhtio   = '$kukarow[yhtio]',
                  ytunnus = '$asno',
                  nimi    = '$asno'";
        $result = pupe_query($query);
        $aperustettu += 1;
      }

      $query = "SELECT *
                FROM asiakas
                WHERE yhtio = '$kukarow[yhtio]'
                AND ytunnus = '$asno'";
      $result = pupe_query($query);

      $asiakasrow = mysql_fetch_array($result);

      $tnimi     = $asiakasrow['nimi'];
      $tnimitark   = $asiakasrow['nimitark'];
      $tosoite   = $asiakasrow['osoite'];
      $tpostino   = $asiakasrow['postino'];
      $tpostitp   = $asiakasrow['postitp'];
      $toim_maa   = $asiakasrow['maa'];

      $query = "INSERT into lasku SET
                yhtio_nimi       = '$yhtiorow[nimi]',
                yhtio_osoite     = '$yhtiorow[osoite]',
                yhtio_postino    = '$yhtiorow[postino]',
                yhtio_postitp    = '$yhtiorow[postitp]',
                yhtio_maa        = '$yhtiorow[maa]',
                yhtio_ovttunnus  = '$yhtiorow[ovttunnus]',
                yhtio_kotipaikka = '$yhtiorow[kotipaikka]',
                laatija          = '$kukarow[kuka]',
                luontiaika       = now(),
                ytunnus          = '$asiakasrow[ytunnus]',
                nimi             = '$asiakasrow[nimi]',
                nimitark         = '$asiakasrow[nimitark]',
                osoite           = '$asiakasrow[osoite]',
                postino          = '$asiakasrow[postino]',
                postitp          = '$asiakasrow[postitp]',
                maa              = '$asiakasrow[maa]',
                toim_nimi        = '$tnimi',
                toim_nimitark    = '$tnimitark',
                toim_osoite      = '$tosoite',
                toim_postino     = '$tpostino',
                toim_postitp     = '$tpostitp',
                toim_maa         = '$toim_maa',
                verkkotunnus     = '$asiakasrow[verkkotunnus]',
                tila             = 'L',
                alatila          = 'A',
                toimaika         = '$toimpvm',
                kerayspvm        = '$toimpvm',
                maksuehto        = '$asiakasrow[maksuehto]',
                toimitustapa     = '$asiakasrow[toimitustapa]',
                myyja            = '$kukarow[tunnus]',
                alv              = '',
                comments         = '$comments',
                ovttunnus        = '$asiakasrow[ovttunnus]',
                toim_ovttunnus   = '$asiakasrow[toim_ovttunnus]',
                liitostunnus     = '$asiakasrow[tunnus]',
                viesti           = '$viesti',
                chn              = '$asiakasrow[chn]',
                piiri            = '$asiakasrow[piiri]',
                yhtio            = '$kukarow[yhtio]'";
      $result = pupe_query($query);
      $id = mysql_insert_id($GLOBALS["masterlink"]);

      if (strlen(trim($asiakasrow['laskutus_nimi'])) == 0) {
        $asiakasrow['laskutus_nimi']     = $asiakasrow['nimi'];
        $asiakasrow['laskutus_nimitark']   = $asiakasrow['nimitark'];
        $asiakasrow['laskutus_osoite']     = $asiakasrow['osoite'];
        $asiakasrow['laskutus_postino']   = $asiakasrow['postino'];
        $asiakasrow['laskutus_postitp']   = $asiakasrow['postitp'];
        $asiakasrow['laskutus_maa']     = $asiakasrow['maa'];
      }

      $query = "INSERT INTO laskun_lisatiedot SET
                laskutus_nimi     = '$asiakasrow[laskutus_nimi]',
                laskutus_nimitark = '$asiakasrow[laskutus_nimitark]',
                laskutus_osoite   = '$asiakasrow[laskutus_osoite]',
                laskutus_postino  = '$asiakasrow[laskutus_postino]',
                laskutus_postitp  = '$asiakasrow[laskutus_postitp]',
                laskutus_maa      = '$asiakasrow[laskutus_maa]',
                yhtio             = '$kukarow[yhtio]',
                otunnus           = '$id',
                laatija           = '$kukarow[kuka]',
                luontiaika        = now()";
      $lisatiedot_result = pupe_query($query);

      echo t("nro")." $id ";
      break;
    case '2' :
      $tuoteno = substr($tietue, 4, 15);
      $nimitys = substr($tietue, 19, 80);
      $maara = substr($tietue, 99, 10);
      $hinta = substr($tietue, 109, 10);
      $alv = substr($tietue, 119, 5);

      $query = "SELECT tuoteno
                FROM tuote
                WHERE yhtio = '$kukarow[yhtio]'
                AND tuoteno = '$tuoteno'";
      $result = pupe_query($query);

      //$tuoteno = $tuoteno * 1;

      if (mysql_num_rows($result) == 0) {
        $query = "INSERT INTO tuote SET
                  yhtio       = '$kukarow[yhtio]',
                  tuoteno     = '$tuoteno',
                  nimitys     = '$nimitys',
                  myyntihinta = $hinta,
                  yksikko     = 'kpl'";
        $result = pupe_query($query);
        $tperustettu += 1;
      }
      $query = "SELECT *
                FROM tuote
                WHERE yhtio = '$kukarow[yhtio]'
                AND tuoteno = '$tuoteno'";
      $result = pupe_query($query);
      $trow = mysql_fetch_array($result);

      $query = "INSERT INTO tilausrivi SET
                laatija   = '$kukarow[kuka]',
                laadittu  = now(),
                yhtio     = '$kukarow[yhtio]',
                tuoteno   = '$tuoteno',
                varattu   = $maara,
                yksikko   = '$trow[yksikko]',
                kpl       = 0,
                alv       = $alv,
                hinta     = $hinta,
                kerayspvm = '$toimpvm',
                nimitys   = '$nimitys',
                otunnus   = '$id',
                tyyppi    = 'L',
                toimaika  = '$toimpvm'";

      if ($trow['ei_saldoa'] != '') $query .= ", keratty = 'saldoton', kerattyaika = now()";

      $rperustettu += 1;

      $result = pupe_query($query);
      break;

    case 'A' :
      echo "<hr>";

      $asno = substr($tietue, 19, 6);

      echo t("Tilaus").": ".t("asiakas")." '" . $asno .  "' ";

      $query = "SELECT ytunnus
                FROM asiakas
                WHERE yhtio = '$kukarow[yhtio]'
                AND ytunnus = '$asno'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        $query = "INSERT INTO asiakas SET
                  yhtio   = '$kukarow[yhtio]',
                  ytunnus = '$asno',
                  nimi    = '$asno'";
        $result = pupe_query($query);
        $aperustettu += 1;
      }

      $query = "SELECT *
                FROM asiakas
                WHERE yhtio = '$kukarow[yhtio]'
                AND ytunnus = '$asno'";
      $result = pupe_query($query);

      $asiakasrow = mysql_fetch_array($result);

      $tnimi     = $asiakasrow['nimi'];
      $tnimitark   = $asiakasrow['nimitark'];
      $tosoite   = $asiakasrow['osoite'];
      $tpostino   = $asiakasrow['postino'];
      $tpostitp   = $asiakasrow['postitp'];
      $toim_maa   = $asiakasrow['maa'];

      $query = "INSERT into lasku SET
                yhtio_nimi       = '$yhtiorow[nimi]',
                yhtio_osoite     = '$yhtiorow[osoite]',
                yhtio_postino    = '$yhtiorow[postino]',
                yhtio_postitp    = '$yhtiorow[postitp]',
                yhtio_maa        = '$yhtiorow[maa]',
                yhtio_ovttunnus  = '$yhtiorow[ovttunnus]',
                yhtio_kotipaikka = '$yhtiorow[kotipaikka]',
                laatija          = '$kukarow[kuka]',
                luontiaika       = NOW(),
                ytunnus          = '$asiakasrow[ytunnus]',
                nimi             = '$asiakasrow[nimi]',
                nimitark         = '$asiakasrow[nimitark]',
                osoite           = '$asiakasrow[osoite]',
                postino          = '$asiakasrow[postino]',
                postitp          = '$asiakasrow[postitp]',
                maa              = '$asiakasrow[maa]',
                toim_nimi        = '$tnimi',
                toim_nimitark    = '$tnimitark',
                toim_osoite      = '$tosoite',
                toim_postino     = '$tpostino',
                toim_postitp     = '$tpostitp',
                toim_maa         = '$toim_maa',
                verkkotunnus     = '$asiakasrow[verkkotunnus]',
                tila             = 'L',
                alatila          = 'A',
                toimaika         = '$toimpvm',
                kerayspvm        = '$toimpvm',
                maksuehto        = '$asiakasrow[maksuehto]',
                toimitustapa     = '$asiakasrow[toimitustapa]',
                myyja            = '$kukarow[tunnus]',
                alv              = '',
                comments         = '$comments',
                ovttunnus        = '$asiakasrow[ovttunnus]',
                toim_ovttunnus   = '$asiakasrow[toim_ovttunnus]',
                viesti           = '$viesti',
                liitostunnus     = '$asiakasrow[tunnus]',
                chn              = '$asiakasrow[chn]',
                piiri            = '$asiakasrow[piiri]',
                yhtio            = '$kukarow[yhtio]'";
      $result = pupe_query($query);
      $id = mysql_insert_id($GLOBALS["masterlink"]);

      if (strlen(trim($asiakasrow['laskutus_nimi'])) == 0) {
        $asiakasrow['laskutus_nimi']     = $asiakasrow['nimi'];
        $asiakasrow['laskutus_nimitark']   = $asiakasrow['nimitark'];
        $asiakasrow['laskutus_osoite']     = $asiakasrow['osoite'];
        $asiakasrow['laskutus_postino']   = $asiakasrow['postino'];
        $asiakasrow['laskutus_postitp']   = $asiakasrow['postitp'];
        $asiakasrow['laskutus_maa']     = $asiakasrow['maa'];
      }

      $query = "INSERT INTO laskun_lisatiedot SET
                laskutus_nimi     = '$asiakasrow[laskutus_nimi]',
                laskutus_nimitark = '$asiakasrow[laskutus_nimitark]',
                laskutus_osoite   = '$asiakasrow[laskutus_osoite]',
                laskutus_postino  = '$asiakasrow[laskutus_postino]',
                laskutus_postitp  = '$asiakasrow[laskutus_postitp]',
                laskutus_maa      = '$asiakasrow[laskutus_maa]',
                yhtio             = '$kukarow[yhtio]',
                otunnus           = '$id',
                laatija           = '$kukarow[kuka]',
                luontiaika        = now()";
      $lisatiedot_result = pupe_query($query);

      echo t("nro")." $id ";
      break;

    default:
      echo t("luotiin")." $rperustettu ".t("rivia ok")."!<br>";
    }
  }
}

if (($tyyppi == 'yct') and ($id != '')) {

  if ($tilanne == '0') {
    $komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa tilaus_in.inc")."<br>";

    $query = "UPDATE lasku SET
              tila        = 'D',
              alatila     = 'L',
              comments    = '$komm'
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$id'";
    $result = pupe_query($query);

    $query = "UPDATE tilausrivi SET
              tyyppi      = 'D'
              WHERE yhtio = '$kukarow[yhtio]'
              AND otunnus = '$id'";
    $result = pupe_query($query);

    echo t("Ei voitu tehdä tilausta loppuun vaan se mitätöitiin")."!";
  }
  else {
    require "../tilauskasittely/tilaus-valmis.inc";

    echo t("luotiin")." $rivperustettu ".t("rivia ok")."!<br>";

    if ($mail == 'jep') {
      $rivi = '';
      $rivi .= t("Tuoteno")."\t".t("määrä")."\t".t("oletussaldo")."\t".t("hälytysraja")."\t".t("oletuspaikka")."\r\n";

      $query = "SELECT tuoteno, tilkpl
                FROM tilausrivi
                WHERE yhtio = '$kukarow[yhtio]'
                AND otunnus = '$id'";
      $resulttil = pupe_query($query);

      while ($tilrow = mysql_fetch_array($resulttil)) {

        $tuoteno = $tilrow['tuoteno'];

        $query = "SELECT halytysraja
                  FROM tuotepaikat
                    WHERE yhtio = '$kukarow[yhtio]'
                  AND tuoteno   = '$tuoteno'
                  AND hyllyalue = 'Y01'";
        $resultyc = pupe_query($query);

        if (mysql_num_rows($resultyc) != 0) {
          $trowyc = mysql_fetch_array($resultyc);
          $hraja = $trowyc['halytysraja'];
        }
        else {
          $hraja = '0';
        }

        $query = "SELECT saldo, concat_ws('-',hyllyalue,hyllynro,hyllytaso,hyllyvali) alue
                  FROM tuotepaikat
                    WHERE yhtio  = '$kukarow[yhtio]'
                  AND tuoteno    = '$tuoteno'
                  AND hyllyalue != 'Y01'
                  AND oletus     = 'X'
                  AND saldo      > '0'";
        $resultmuu = pupe_query($query);

        if (mysql_num_rows($resultmuu) != 0) {
          $trowmuu = mysql_fetch_array($resultmuu);
          $oletsaldo = $trowmuu['saldo'];
          $alue = $trowmuu['alue'];
        }
        else {
          $oletsaldo = '0';
          $alue = 'eiole';
        }

        $tilrow['tilkpl'] = str_replace('.', ',', $tilrow['tilkpl']);
        $oletsaldo = str_replace('.', ',', $oletsaldo);
        $hraja = str_replace('.', ',', $hraja);

        $rivi .= "$tilrow[tuoteno]\t$tilrow[tilkpl]\t$oletsaldo\t$hraja\t$alue\r\n";
      }

      echo "<br>".t("Lähetetään meili osoitteeseen")." $mailiosoite";
      $bound = uniqid(time()."_") ;
      $ny = date("d-m-Y");

      $header   = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\r\n";
      $header  .= "MIME-Version: 1.0\r\n" ;
      $header  .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

      $content = "--$bound\r\n";

      $content .= "Content-Type: application/vnd.ms-excel; name=\"Excel-".t("tarveraportti")."$ny.xls\"\r\n" ;
      $content .= "Content-Transfer-Encoding: base64\r\n" ;
      $content .= "Content-Disposition: attachment; filename=\"Excel-".t("tarveraportti")."$ny.xls\"\r\n\r\n";

      $content .= chunk_split(base64_encode($rivi));
      $content .= "\r\n" ;

      $content .= "--$bound\r\n";

      $boob = mail($mailiosoite, mb_encode_mimeheader(t("tarveraportti")." ".date("d.m.Y"), "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
    }
  }
}

if ($error==1) echo "<font class='error'>".t("Käsittelyssä tapahtui virhe")."</font><br>";

fclose($fd);
echo "</pre>";
