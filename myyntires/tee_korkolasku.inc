<?php

//luodaan korkolasku

//sisään otetaan $liitostunnus. $liitostunnus tulee paperikorkolasku.php-failista
//ja maksuehdon tunnus $vmehto
//ja $loppusumma jossa on maksettavan koron määrä. $loppusumma tulee paperikorkolasku.php-failista

//Haetaan asiakkaan tiedot
$query = "SELECT * FROM asiakas WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$liitostunnus'";
$result = pupe_query($query);

if (mysql_num_rows($result) != 1) {
  printf("<font class='error'>".t("Oho, asiakas katosi (%d)")."</font><br>", $ytunnus);
  exit;
}
$asrow = mysql_fetch_array($result);

//Etsitään tämä tässä tilanteessa käytettävä maksuehto
$apuqu = "SELECT * FROM maksuehto WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$vmehto'";
$meapu = pupe_query($apuqu);

if (mysql_num_rows($meapu) != 1) {
  printf("<font class='error'>".t("Oho, maksuehto katosi (%d)")."</font><br>", $vmehto);
  exit;
}
$merow = mysql_fetch_array($meapu);

$crlf = array("\r", "\n"); // poistetaan rivinvaihdot kommentista
$comments = str_replace($crlf, " ", $comments);

//Haetaan tuotteen tiedot
$query = "SELECT *
          FROM tuote
          WHERE yhtio = '$kukarow[yhtio]'
          AND tuoteno = 'Korko'";
$result = pupe_query($query);

if (mysql_num_rows($result) == 1) {

  $tuorow = mysql_fetch_array($result);

  if (trim($asrow["valkoodi"]) != '') {
    $query = "SELECT nimi, kurssi, tunnus
              FROM valuu
              WHERE yhtio = '$kukarow[yhtio]'
              and nimi    = '$yhtiorow[valkoodi]'
              and kurssi  > 0
              LIMIT 1";
    $vres = pupe_query($query);

    if (mysql_num_rows($vres) == 1) {
      $vrow = mysql_fetch_array($vres);

      $valkoodi  = $vrow["nimi"];
      $kurssi   = $vrow["kurssi"];
    }
    else {
      $valkoodi  = $yhtiorow["valkoodi"];
      $kurssi    = 1;
    }
  }
  else {
    $valkoodi  = $yhtiorow["valkoodi"];
    $kurssi    = 1;
  }


  $query = "INSERT INTO lasku SET
            yhtio_nimi      = '$yhtiorow[nimi]',
            yhtio_osoite    = '$yhtiorow[osoite]',
            yhtio_postino   = '$yhtiorow[postino]',
            yhtio_postitp   = '$yhtiorow[postitp]',
            yhtio_maa       = '$yhtiorow[maa]',
            yhtio_ovttunnus = '$yhtiorow[ovttunnus]',
            yhtio_kotipaikka= '$yhtiorow[kotipaikka]',
            ytunnus         = '$asrow[ytunnus]',
            nimi            = '$asrow[nimi]',
            nimitark        = '$asrow[nimitark]',
            osoite          = '$asrow[osoite]',
            postino         = '$asrow[postino]',
            postitp         = '$asrow[postitp]',
            maa             = '$asrow[maa]',
            toim_nimi       = '$asrow[toim_nimi]',
            toim_nimitark   = '$asrow[toim_nimitark]',
            toim_osoite     = '$asrow[toim_osoite]',
            toim_postino    = '$asrow[toim_postino]',
            toim_postitp    = '$asrow[toim_postitp]',
            toim_maa        = '$asrow[toim_maa]',
            verkkotunnus    = '$asrow[verkkotunnus]',
            maksuehto       = '$merow[tunnus]',
            toimitustapa    = '',
            myyja           = '$kukarow[tunnus]',
            alv             = '0',
            ovttunnus       = '$asrow[ovttunnus]',
            toim_ovttunnus  = '$asrow[toim_ovttunnus]',
            viesti          = 'Korkolasku',
            chn             = '$asrow[chn]',
            clearing        = 'KORKOLASKU',
            maksuteksti     = '$merow[teksti]',
            laskutusvkopv   = '$asrow[laskutusvkopv]',
            yhtio           = '$kukarow[yhtio]',
            tila            = 'N',
            alatila         = '',
            vienti          = '$asrow[vienti]',
            tilaustyyppi    = 'N',
            toimaika        = now(),
            ketjutus        = 'o',
            sisainen        = 'o',
            eilahetetta     = 'o',
            valkoodi        = '$valkoodi',
            vienti_kurssi   = '$kurssi',
            viikorkopros    = '0',
            laatija         = '$kukarow[kuka]',
            liitostunnus    = '$asrow[tunnus]',
            piiri           = '$asrow[piiri]',
            olmapvm         = now(),
            luontiaika      = now()";
  $result = pupe_query($query);
  $otunnus = mysql_insert_id($GLOBALS["masterlink"]);

  if (strlen(trim($asrow['laskutus_nimi'])) == 0) {
    $asrow['laskutus_nimi']     = $asrow['nimi'];
    $asrow['laskutus_nimitark']   = $asrow['nimitark'];
    $asrow['laskutus_osoite']     = $asrow['osoite'];
    $asrow['laskutus_postino']     = $asrow['postino'];
    $asrow['laskutus_postitp']     = $asrow['postitp'];
    $asrow['laskutus_maa']       = $asrow['maa'];
  }

  $query = "INSERT INTO laskun_lisatiedot SET
            laskutus_nimi     = '$asrow[laskutus_nimi]',
            laskutus_nimitark = '$asrow[laskutus_nimitark]',
            laskutus_osoite   = '$asrow[laskutus_osoite]',
            laskutus_postino  = '$asrow[laskutus_postino]',
            laskutus_postitp  = '$asrow[laskutus_postitp]',
            laskutus_maa      = '$asrow[laskutus_maa]',
            laatija           = '$kukarow[kuka]',
            luontiaika        = now(),
            otunnus           = '$otunnus',
            yhtio             = '$kukarow[yhtio]'";
  $lisatiedot_result = pupe_query($query);

  $query = "INSERT INTO tilausrivi SET
            laatija  = '$kukarow[kuka]',
            laadittu = now(),
            toimaika = now(),
            yhtio    = '$kukarow[yhtio]',
            tuoteno  = '$tuorow[tuoteno]',
            varattu  = '1',
            tilkpl   = '1',
            ale1     = '0',
            netto    = 'N',
            jt       = 0,
            yksikko  = '',
            try      = '$tuorow[try]',
            osasto   = '$tuorow[osasto]',
            kpl      = 0,
            alv      = '0',
            hinta    = '$loppusumma',
            nimitys  = '$tuorow[nimitys]',
            otunnus  = '$otunnus',
            tyyppi   = 'L',
            var      = ''";
  $result = pupe_query($query);

  $kukarow["kesken"] = $otunnus;

  $query = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
  $result = pupe_query($query);
  $laskurow = mysql_fetch_array($result);

  if ($yhtiorow["kasittelykulu_tuotenumero"] != '') {

    $query = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[kasittelykulu_tuotenumero]'";
    $result = pupe_query($query);
    $trow = mysql_fetch_array($result);

    $kasittelykulu = laskuval($kasittelykulu, $laskurow["vienti_kurssi"]);

    $ytunnus         = $laskurow["ytunnus"];
    $kpl             = 1;
    $tuoteno         = $yhtiorow["kasittelykulu_tuotenumero"];
    $toimaika        = $laskurow["toimaika"];
    $kerayspvm       = $laskurow["kerayspvm"];
    $hinta           = $kasittelykulu;
    $netto           = "N";
    $var             = "H";
    $alv             = "";
    $paikka          = "";
    $varasto         = "";
    $rivitunnus      = "";
    $korvaavakielto  = "";
    $varataan_saldoa = "";
    $jtkielto        = $laskurow['jtkielto'];

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      ${'ale'.$alepostfix} = '';
    }

    require "tilauskasittely/lisaarivi.inc";
  }

  $valittu_tulostin = $kirjoitin;

  require "../tilauskasittely/tilaus-valmis.inc";
}
else {
  echo "<br><br><font class='error'>Korko-tuotetta ei löydy! Ole hyvä ja perusta tuote tuotenumerolla 'korko'.</font>";
  exit;
}
