<?php

if (!function_exists('luo_ostotilausotsikko')) {

  function luo_ostotilausotsikko(array $params) {

    global $kukarow, $yhtiorow;

    /*   Pakolliset parametrit
     *   $params['liitostunnus'];
     *
     *   Muut parametrit
     *   $params['ostotilauksen_kasittely'];
     *   $params["nimi"];
     *   $params["nimitark"];
     *  $params["osoite"];
     *   $params["postino"];
     *   $params["postitp"];
     *   $params["maa"];
     *   $params['ohjausmerkki']
     *   $params['jakelu']
     *   $params['myyntitil_kerayspvm']
     *   $params['myytil_myyja']
     *   $params['myytil_sisviesti1']
     *  $params['myytil_viesti']
     *   $params['myytil_toimaika']
     *   $params['myytil_toimitustapa']
     *   $params['myytil_liitostunnus']
     */

    $params['ostotilauksen_kasittely'] = (!empty($params['ostotilauksen_kasittely'])) ? $params['ostotilauksen_kasittely'] : '';
    $params["nimi"] = (!empty($params["nimi"])) ? $params["nimi"] : '';
    $params["nimitark"] = (!empty($params["nimitark"])) ? $params["nimitark"] : '';
    $params["osoite"] = (!empty($params["osoite"])) ? $params["osoite"] : '';
    $params["postino"] = (!empty($params["postino"])) ? $params["postino"] : '';
    $params["postitp"] = (!empty($params["postitp"])) ? $params["postitp"] : '';
    $params["maa"] = (!empty($params["maa"])) ? $params["maa"] : '';
    $params['ohjausmerkki'] = (!empty($params['ohjausmerkki'])) ? $params['ohjausmerkki'] : '';
    $params['jakelu'] = (!empty($params['jakelu'])) ? $params['jakelu'] : '';
    $params['myyntitil_kerayspvm'] = (!empty($params['myyntitil_kerayspvm'])) ? $params['myyntitil_kerayspvm'] : '';
    $params['myytil_myyja'] = (!empty($params['myytil_myyja'])) ? $params['myytil_myyja'] : '';
    $params['myytil_sisviesti1'] = (!empty($params['myytil_sisviesti1'])) ? $params['myytil_sisviesti1'] : '';
    $params['myytil_viesti'] = (!empty($params['myytil_viesti'])) ? $params['myytil_viesti'] : '';
    $params['myytil_toimaika'] = (!empty($params['myytil_toimaika'])) ? $params['myytil_toimaika'] : '';
    $params['myytil_toimitustapa'] = (!empty($params['myytil_toimitustapa'])) ? $params['myytil_toimitustapa'] : '';
    $params['myytil_liitostunnus'] = (!empty($params['myytil_liitostunnus'])) ? $params['myytil_liitostunnus'] : '';
    $params['varasto'] = (!empty($params['varasto'])) ? $params['varasto'] : $kukarow['oletus_ostovarasto'];

    $chn = (!empty($params['ostotilauksen_kasittely']) and $params['ostotilauksen_kasittely'] == "GEN") ? "GEN" : "";

    $ostotil_tiltyyp_res = t_avainsana("OSTOTIL_TILTYYP");

    if (mysql_num_rows($ostotil_tiltyyp_res) > 0) {
      // Otetaan eka...
      $ostotil_tiltyyp_row = mysql_fetch_assoc($ostotil_tiltyyp_res);
      $tilaustyyppi = $ostotil_tiltyyp_row['selite'];
    }
    else {
      // oletuksena normaali
      $tilaustyyppi = 2;
    }

    $params['tilaustyyppi'] = (!empty($params['tilaustyyppi'])) ? $params['tilaustyyppi'] : $tilaustyyppi;

    $tiltoi = hae_toimittaja($params['liitostunnus']);

    // Jos ostotilausta ei ole luotu myyntitilaukselta (tilaus_osto.php ~545), käytetään toimittajan oletuksia
    if (empty($params['nimi'])) {
      $toim_nimi     = $tiltoi["nimi"];
      $toim_nimitark   = $tiltoi["nimitark"];
      $toim_osoite   = $tiltoi["osoite"];
      $toim_postino   = $tiltoi["postino"];
      $toim_postitp   = $tiltoi["postitp"];
      $toim_maa     = $tiltoi["maa"];
    }
    else {
      $toim_nimi     = $params["nimi"];
      $toim_nimitark   = $params["nimitark"];
      $toim_osoite   = $params["osoite"];
      $toim_postino   = $params["postino"];
      $toim_postitp   = $params["postitp"];
      $toim_maa     = $params["maa"];
    }

    $valkoodi = $tiltoi["oletus_valkoodi"];

    if ($valkoodi == '') {
      $valkoodi = $yhtiorow["valkoodi"];
    }

    $vquery = "SELECT nimi, kurssi, tunnus
               FROM valuu
               WHERE yhtio = '$kukarow[yhtio]'
               and nimi    = '$valkoodi'";
    $vresult = pupe_query($vquery);
    $vrow = mysql_fetch_array($vresult);

    $ostotilauksen_kasittely_wherelisa = "";

    // Käsitellään myyntitilauksen otsikolla setattu "ostotilauksen käsittely"
    switch ($params['ostotilauksen_kasittely']) {
      case '5':
      case '4':
        $ostotilauksen_kasittely_wherelisa .= "
          and toim_nimi    = '{$toim_nimi}'
          and toim_nimitark  = '{$toim_nimitark}'
          and toim_osoite    = '{$toim_osoite}'
          and toim_postino  = '{$toim_postino}'
          and toim_postitp  = '{$toim_postitp}'
          and toim_maa    = '{$toim_maa}'
          and varasto     = '{$params['varasto']}'
          and ohjausmerkki   = '{$params['ohjausmerkki']}'
          and chn        = '$chn'
          and tilaustyyppi  = '{$params['tilaustyyppi']}'";
        break;
      case '2':
      case '3':
        $ostotilauksen_kasittely_wherelisa .= "
          and toim_nimi    = '{$toim_nimi}'
          and toim_nimitark  = '{$toim_nimitark}'
          and toim_osoite    = '{$toim_osoite}'
          and toim_postino  = '{$toim_postino}'
          and toim_postitp  = '{$toim_postitp}'
          and toim_maa    = '{$toim_maa}'
          and chn        = '$chn'
          and tilaustyyppi  = '{$params['tilaustyyppi']}'";
        break;
      case '1':
      case '0':
      default:
        $ostotilauksen_kasittely_wherelisa .= "
          and toim_nimi    = '{$toim_nimi}'
          and toim_nimitark  = '{$toim_nimitark}'
          and toim_osoite    = '{$toim_osoite}'
          and toim_postino  = '{$toim_postino}'
          and toim_postitp  = '{$toim_postitp}'
          and toim_maa    = '{$toim_maa}'
          and left(luontiaika,10) = left(now(),10)
          and laatija     = '{$kukarow['kuka']}'
          and chn        = '$chn'
          and tilaustyyppi  = '{$params['tilaustyyppi']}'";
        break;
    }

    $query = "SELECT *
              from lasku
              where yhtio      = '$kukarow[yhtio]'
              and liitostunnus = '$tiltoi[tunnus]'
              and tila         = 'O'
              and alatila      = ''
              {$ostotilauksen_kasittely_wherelisa}";
    $ostotilaus_result = pupe_query($query);

    // ei löydy, tehdään uus otsikko
    if (mysql_num_rows($ostotilaus_result) == 0) {

      $query = "INSERT INTO lasku SET
                alatila             = '',
                chn                 = '$chn',
                huolitsija          = '{$tiltoi['huolitsija']}',
                jakelu              = '{$params['jakelu']}',
                kerayspvm           = '{$params['myyntitil_kerayspvm']}',
                kuljetus            = '{$tiltoi['kuljetus']}',
                laatija             = '{$kukarow['kuka']}',
                liitostunnus        = '{$tiltoi['tunnus']}',
                luontiaika          = now(),
                maa                 = '{$tiltoi['maa']}',
                maksuteksti         = '{$tiltoi['maksuteksti']}',
                myyja               = '{$params['myytil_myyja']}',
                nimi                = '{$tiltoi['nimi']}',
                nimitark            = '{$tiltoi['nimitark']}',
                ohjausmerkki        = '{$params['ohjausmerkki']}',
                osoite              = '{$tiltoi['osoite']}',
                ovttunnus           = '{$tiltoi['ovttunnus']}',
                postino             = '{$tiltoi['postino']}',
                postitp             = '{$tiltoi['postitp']}',
                sisviesti1          = '{$params['myytil_sisviesti1']}',
                tila                = 'O',
                tilaustyyppi        = '{$params['tilaustyyppi']}',
                tilausyhteyshenkilo = '{$kukarow['nimi']}',
                toimaika            = '{$params['myytil_toimaika']}',
                toimitusehto        = '{$tiltoi['toimitusehto']}',
                toimitustapa        = '{$params['myytil_toimitustapa']}',
                toim_maa            = '{$toim_maa}',
                toim_nimi           = '{$toim_nimi}',
                toim_nimitark       = '{$toim_nimitark}',
                toim_osoite         = '{$toim_osoite}',
                toim_ovttunnus      = '{$tiltoi['ovttunnus']}',
                toim_postino        = '{$toim_postino}',
                toim_postitp        = '{$toim_postitp}',
                valkoodi            = '{$valkoodi}',
                vanhatunnus         = {$params['toimipaikka']},
                varasto             = '{$params['varasto']}',
                verkkotunnus        = '{$params['myytil_liitostunnus']}',
                vienti_kurssi       = '{$vrow['kurssi']}',
                viesti              = '{$params['myytil_viesti']}',
                viikorkopros        = '{$yhtiorow['viivastyskorko']}',
                yhtio               = '{$kukarow['yhtio']}',
                ytunnus             = '{$tiltoi['ytunnus']}'";
      pupe_query($query);
      $ostotilaus_tunnus = mysql_insert_id();

      $query = "SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$ostotilaus_tunnus}'";
      $ostotilaus_result = pupe_query($query);

      $ostotilaus_row = mysql_fetch_assoc($ostotilaus_result);
    }
    else {
      $ostotilaus_row = mysql_fetch_assoc($ostotilaus_result);
    }

    return $ostotilaus_row;
  }
}
