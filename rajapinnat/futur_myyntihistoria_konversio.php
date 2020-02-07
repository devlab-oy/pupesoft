<?php

/* Tonylle terkkuja:
   jos myyntiriviltä puuttuu sekä tuotekoodi että tuotteen nimi, ohitin ne.
  ajoin laskunumerovälejä, koska muuten kaatui muistin vähyyteen, ei varmaan riittävän fiksua koodia
*/

if (php_sapi_name() == 'cli') {
  // otetaan includepath aina rootista
  $pupe_root_polku = dirname(dirname(__FILE__));
  //$pupe_root_polku = "/Users/satu/Sites/pupesoft";

  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
  ini_set("display_errors", 0);

  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $yhtio = trim($argv[1]);
  $alkupvm = trim($argv[2]);
  $loppupvm = trim($argv[3]);

  //yhtiötä ei ole annettu
  if (empty($yhtio)) {
    echo "\nUsage: php ".basename($argv[0])." yhtio\n\n";
    die;
  }

  $yhtiorow = hae_yhtion_parametrit($yhtio);

  // Haetaan käyttäjän tiedot
  $query = "SELECT *
            FROM kuka
            WHERE yhtio = '$yhtio'
            AND kuka    = 'admin'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    die ("User admin not found");
  }

  // Adminin oletus
  $kukarow = mysql_fetch_assoc($result);
}
else {
  die("Konversion voi ajaa vain komentoriviltä");
}


if (!function_exists("futurmuunnos_tuote")) {
  function futurmuunnos_tuote($toimittaja, $jotain) {
    $valmis = $toimittaja != '3' ? $jotain.'-'.$toimittaja : $jotain;
    return $valmis;
  }


}

ini_set("memory_limit", "5G");

$laatija     = "konversio";
$laatija2     = "('futursoft', 'konversio')";
$laatija3     = "futursoft";
$hyllynro    = '';
$hyllytaso    = '';
$hyllyvali    = '';
$toimitustapa  = 'Oletus';
$toimitusehto  = 'EXW';
$laskuttaja    = $laatija;

/*
if (isset($myyntilasku['MYP_EKIRJE_LAHPVM'])) {
  $tapvm = $myyntilasku['MYP_EKIRJE_LAHPVM'] . " 23:59:59";
}
else {
  $tapvm = $myyntilasku['MYP_AIKALEIMA'];
}
*/

// tony testaa: -1972260088
//   Laskut: 84979, 84980, 84981
$query = "SELECT *
          FROM futur_myyntper
          #WHERE MYP_LASKUNRO = 226207
          #WHERE MYP_LASKUNRO > 117373 and MYP_LASKUNRO < 123001
          #WHERE MYP_MYYNTINRO = '377794';
        WHERE MYP_PVM >= '$alkupvm' and MYP_PVM <= '$loppupvm';";
$reslasku = mysql_query($query);

$count = mysql_num_rows($reslasku);
$ii = 0;

if ($count == 0) echo "Tais tulla jekkumestarin erikoinen? Ei oo myyntihistoriaa taulussa.";

while ($myyntilasku = mysql_fetch_assoc($reslasku)) {

  $ii++;
  echo "\nMyyntilasku $ii / $count (myyntilasku $myyntilasku[MYP_LASKUNRO])";

  $laskunro     = $myyntilasku['MYP_LASKUNRO'];
  $asiakasnro   = $myyntilasku['MYP_ASIAKASNRO'];
  if ($asiakasnro < 0 or $myyntilasku['MYP_LASKUNRO'] == 0) $asiakasnro = 70500;       // käteismyyntiä, kun asno negatiivinen
  $luontiaika    = $myyntilasku['MYP_PVM'];
  $vanhatunnus   = 0;
  $astunnus     = 0;
  $hyllyalue    = '';
  $toimipaikka  = '';
  $nimi = $myyntilasku['MYP_LASKUTUSOSOITE'];
  if ($nimi == '') $nimi = $myyntilasku['MYP_TOIMITUSOSOITE'];
  if ($laskunro == 0) $laskunro = ((str_replace('-', '', $myyntilasku['MYP_PVM'])) - 20000000) + 10000000000;
  $laskunro = $laskunro * -1;
  $viitteemme = $myyntilasku['MYP_VIITTEEMME'];

  // echo "\n $laskunro, $asiakasnro, $myyntilasku[MYP_LASKUNRO], $myyntilasku[MYP_ASIAKASNRO], $myyntilasku[MYP_PVM] \n";

  $toimosoitearray = array();
  $laskosoitearray = array();

  if ($myyntilasku['MYP_TOIMITUSOSOITE'] != '') {
    $toimosoitearray = explode("\n", $myyntilasku['MYP_TOIMITUSOSOITE']);
    $toimnimi      = pupesoft_cleanstring($toimosoitearray[0]);
    $toimosoite      = pupesoft_cleanstring($toimosoitearray[1]);
    $toimpostino    = substr($toimosoitearray[2], 0, 5);
    $toimpostitp    = pupesoft_cleanstring(substr($toimosoitearray[2], 6));
  }
  if ($myyntilasku['MYP_LASKUTUSOSOITE'] != '') {
    $laskosoitearray   = explode("\n", $myyntilasku['MYP_LASKUTUSOSOITE']);
    $laskutusnimi    = pupesoft_cleanstring($laskosoitearray[0]);
    $laskutusosoite    = pupesoft_cleanstring($laskosoitearray[1]);
    $laskutuspostino  = substr($laskosoitearray[2], 0, 5);
    $laskutuspostitp  = pupesoft_cleanstring(substr($laskosoitearray[2], 6));
  }

  $query = "SELECT *
            FROM asiakas
            WHERE yhtio    = '{$yhtio}'
            AND asiakasnro = '{$asiakasnro}'
            /* AND laji != 'P'  */
            LIMIT 1";
  $asresult = pupe_query($query);

  if (mysql_num_rows($asresult) == 0) {
    $query = "INSERT INTO asiakas SET
              yhtio            = '{$yhtio}',
              ytunnus          = '{$asiakasnro}',
              asiakasnro       = '{$asiakasnro}',
              nimi             = 'Tuntematon Futursoft asiakas',
              laji             = 'P',
              laatija          = '$laatija',
              laskutus_nimi    = '$laskutusnimi',
              laskutus_osoite  = '$laskutusosoite',
              laskutus_postino = '$laskutuspostino',
              laskutus_postitp = '$laskutuspostitp',
              luontiaika       = NOW(),
              toim_nimi        = '$toimnimi',
              toim_osoite      = '$toimosoite',
              toim_postino     = '$toimpostino',
              toim_postitp     = '$toimpostitp'";
    $asresult = pupe_query($query);
    $astunnus = mysql_insert_id();

    $query = "SELECT *
              FROM asiakas
              WHERE tunnus = '$astunnus'";
    $asresult = pupe_query($query);
    $asiakasrow = mysql_fetch_assoc($asresult);

    // echo "\n Asiakas $myyntilasku[MYP_ASIAKASNRO] puuttuu \n";
  }
  else {
    $asiakasrow = mysql_fetch_assoc($asresult);
  }

  $laskutusnimi     = ($asiakasrow['laskutus_nimi'] == '') ? $asiakasrow['nimi'] : $asiakasrow['laskutus_nimi'];
  $laskutusnimitark   = ($asiakasrow['laskutus_nimitark'] == '') ? $asiakasrow['nimitark'] : $asiakasrow['laskutus_nimitark'];
  $laskutusosoite   = ($asiakasrow['laskutus_osoite'] == '') ? $asiakasrow['osoite'] : $asiakasrow['laskutus_osoite'];
  $laskutuspostino   = ($asiakasrow['laskutus_postino'] == '') ? $asiakasrow['postino'] : $asiakasrow['laskutus_postino'];
  $laskutuspostit    = ($asiakasrow['laskutus_postitp'] == '') ? $asiakasrow['postitp'] : $asiakasrow['laskutus_postitp'];
  $laskutusmaa    = ($asiakasrow['laskutus_maa'] == '') ? $asiakasrow['maa'] : $asiakasrow['laskutus_maa'];

  $toimnimi       = ($asiakasrow['toim_nimi'] == '') ? $asiakasrow['nimi'] : $asiakasrow['toim_nimi'];
  $toimnimitark     = ($asiakasrow['toim_nimitark'] == '') ? $asiakasrow['nimitark'] : $asiakasrow['toim_nimitark'];
  $toimosoite       = ($asiakasrow['toim_osoite'] == '') ? $asiakasrow['osoite'] : $asiakasrow['toim_osoite'];
  $toimovttunnus     = ($asiakasrow['toim_ovttunnus'] == '') ? $asiakasrow['ovttunnus'] : $asiakasrow['toim_ovttunnus'];
  $toimpostino     = ($asiakasrow['toim_postino'] == '') ? $asiakasrow['postino'] : $asiakasrow['toim_postino'];
  $toimpostitp     = ($asiakasrow['toim_postitp'] == '') ? $asiakasrow['postitp'] : $asiakasrow['toim_postitp'];
  $toimmaa       = ($asiakasrow['toim_maa'] == '') ? $asiakasrow['maa'] : $asiakasrow['toim_maa'];

  // $laskunro = $myyntilasku['MYP_LASKUNRO'] * -1;
  $kaalepava = ($myyntilasku['MYP_KASSAALEPV'] = 0) ? '' : $myyntilasku['MYP_KASSAALEPV'];

  //if (isset($myyntilasku['MYP_EKIRJE_LAHPVM'])) {
  //  $tapvm = $myyntilasku['MYP_EKIRJE_LAHPVM'] . " 23:59:59";
  //}
  //else {

  $tapvm = $myyntilasku['MYP_PVM'];      // oli $myyntilasku['MYP_AIKALEIMA'];

  //}

  $tallpvm = $myyntilasku['MYP_PVM'];   // oli $myyntilasku['MYP_AIKALEIMA'];
  $alviprosentti = 0;
  $ketjutus = '';
  $maksuehto = konvertoi_maksuehto($myyntilasku['MYP_MAKSUTAPAKOODI'], $yhtio);
  $toimipaikka = konvertoi_toimipaikka($myyntilasku['MYP_TOIMIPISTENRO'], $yhtio);
  if ($toimipaikka == '' or $toimipaikka == 0) $toimipaikka = '1';

  //  echo "\n 201: $kassalipas, $asiakasnro, $toimipaikka, $myyntilasku[MYP_TOIMIPISTENRO], $myyntilasku[MYP_MYYNTINRO] \n";

  // Maksuehto ja kassalipas juttu käteismyyntejä varten
  if ($myyntilasku['MYP_LASKUNRO'] == 0) {
    if ($myyntilasku['MYP_KATEISMYYNTI'] !=0 or $maksuehto == 1517) {
      $maksuehto = 1551;
    }
    else {
      $maksuehto = 1572;
    }

    $kustannuspaikka = konvertoi_kustannuspaikka($myyntilasku['MYP_TOIMIPISTENRO'], $yhtio);
    //haetaan kustannuspaikan kassalippaan tiedot
    $query = "SELECT *
              FROM kassalipas
              WHERE yhtio = '{$yhtio}'
              AND kustp   = '{$kustannuspaikka}'";
    $kpresult = pupe_query($query);
    if (mysql_num_rows($kpresult) != 0) {
      $kassalipas_row = mysql_fetch_assoc($kpresult);
      $kassalipas = $kassalipas_row['tunnus'];
    }
    else {
      $kassalipas = 33;
    }
  }
  else {
    $kassalipas = '';
  }

  $hyllyalue = hae_hyllyalue($myyntilasku['MYP_TOIMIPISTENRO'], $yhtio);
  if ($hyllyalue == '') $hyllyalue = 'A00';
  $tallpvm = $myyntilasku['MYP_AIKALEIMA'];
  $toimituspvm = $myyntilasku['MYP_AIKALEIMA'];

  // echo "\n 228: $kassalipas, $asiakasnro, $maksuehto, $myyntilasku[MYP_YHTEENSA], $myyntilasku[MYP_MYYNTINRO] \n";

  // Myyntper: ei päivitetty MYP_YLATEKSTI, MYP_ALATEKSTI
  if ($kassalipas == '') {
    $query = "SELECT laskunro, tunnus, summa, arvo, laatija, kate
              FROM lasku
              WHERE yhtio  = '$yhtio'
              AND tila     = 'U'
              AND alatila  = 'X'
              AND laskunro = '$laskunro'
              AND laatija  in $laatija2 ";
    $uxresult = pupe_query($query);
  }
  else {
    $query = "SELECT laskunro, tunnus, summa, arvo, laatija, kate
              FROM lasku
              WHERE yhtio    = '$yhtio'
              AND tila       = 'U'
              AND alatila    = 'X'
              AND laskunro   = '$laskunro'
              AND maksuehto  = '$maksuehto'
              AND kassalipas = '$kassalipas'
              AND laatija    in $laatija2 ";
    $uxresult = pupe_query($query);
  }

  if (mysql_num_rows($uxresult) == 0) {
    $query = "INSERT INTO lasku SET
              aktiivinen_kuljetus              = '',
              aktiivinen_kuljetus_kansallisuus = '',
              alatila                          = 'X',
              alv                              = '$alviprosentti',
              alv_tili                         = '',
              arvo                             = '{$myyntilasku['MYP_VEROTON_YHT']}',
              arvo_valuutassa                  = '{$myyntilasku['MYP_VEROTON_YHT']}',
              asiakkaan_tilausnumero           = '',
              bruttopaino                      = '',
              chn                              = '$asiakasrow[chn]',
              clearing                         = '',
              comments                         = '$viitteemme',
              ebid                             = '',
              eilahetetta                      = '',
              erikoisale                       = '',
              erpcm                            = '{$myyntilasku['MYP_ERAPVM']}',
              factoringsiirtonumero            = '',
              h1time                           = '$tallpvm',
              h2time                           = '',
              h3time                           = '',
              h4time                           = '',
              h5time                           = '',
              hinta                            = '',
              huolitsija                       = '',
              hyvak1                           = '$laatija',
              hyvak2                           = '',
              hyvak3                           = '',
              hyvak4                           = '',
              hyvak5                           = '',
              hyvaksyja_nyt                    = '',
              hyvaksynnanmuutos                = '',
              jakelu                           = '',
              jaksotettu                       = '',
              jtkielto                         = '',
              kapvm                            = '{$myyntilasku['MYP_KASSAALEPV']}',
              kassalipas                       = '$kassalipas',
              kasumma                          = '',
              kasumma_valuutassa               = '{$myyntilasku['MYP_KASSAALEPROS']}', # tallennetaan kassa-aleprossa toistaiseksi tähän
              kate                             = '{$myyntilasku['MYP_KATETTA']}',
              kauppatapahtuman_luonne          = '',
              kerayslista                      = '',
              kerayspvm                        = '{$myyntilasku['MYP_TOIMITUSPVM']}',
              keraysvko                        = '',
              ketjutus                         = '$ketjutus',
              kirjoitin                        = '',
              kohde                            = '',
              kohdistettu                      = '',
              kontti                           = '',
              kuljetus                         = '',
              kuljetusmuoto                    = '',
              laatija                          = '$laatija',
              lahetepvm                        = '{$myyntilasku['MYP_PVM']}',
              lapvm                            = '{$myyntilasku['MYP_PVM']}',
              laskunro                         = '$laskunro',
              laskutettu                       = '$tapvm',
              laskuttaja                       = '$laskuttaja',
              laskutusvkopv                    = '',
              liitostunnus                     = '$asiakasrow[tunnus]',
              lisattava_era                    = '',
              luontiaika                       = '$luontiaika',
              maa                              = '$asiakasrow[maa]',
              maa_alkupera                     = '',
              maa_lahetys                      = '',
              maa_maara                        = '',
              maksaja                          = '',
              maksuaika                        = '',
              maksuehto                        = '$maksuehto',    # konvertoi_maksuehto($myyntilasku[MYP_MAKSUTAPAKOODI], $yhtio),
              maksuteksti                      = '',
              maksutyyppi                      = '',
              maksu_kurssi                     = '',
              maksu_tili                       = '',
              mapvm                            = '{$myyntilasku['MYP_ERAPVM']}',
              muutospvm                        = '',
              muuttaja                         = '',
              myyja                            = '',
              nimi                             = '$asiakasrow[nimi]',
              nimitark                         = '$asiakasrow[nimitark]',
              noutaja                          = '',
              olmapvm                          = '',
              osatoimitus                      = '',
              osoite                           = '$asiakasrow[osoite]',
              osoitetark                       = '',
              ovttunnus                        = '$asiakasrow[ovttunnus]',
              pakkaamo                         = '',
              pankki1                          = '',
              pankki2                          = '',
              pankki3                          = '',
              pankki4                          = '',
              pankki_haltija                   = '',
              piiri                            = '$asiakasrow[piiri]',
              poistumistoimipaikka             = '',
              poistumistoimipaikka_koodi       = '',
              popvm                            = '',
              postino                          = '$asiakasrow[postino]',
              postitp                          = '$asiakasrow[postitp]',
              pyoristys                        = '{$myyntilasku['MYP_PYORISTYS']}',
              pyoristys_valuutassa             = '{$myyntilasku['MYP_PYORISTYS']}',
              rahti                            = '',
              rahtisopimus                     = '',
              rahtivapaa                       = '',
              rahti_etu                        = '',
              rahti_etu_alv                    = '',
              rahti_huolinta                   = '',
              saldo_maksettu                   = '{$myyntilasku['MYP_YHTEENSA']}',
              saldo_maksettu_valuutassa        = '{$myyntilasku['MYP_YHTEENSA']}',
              sisainen                         = '',
              sisamaan_kuljetus                = '',
              sisamaan_kuljetusmuoto           = '',
              sisamaan_kuljetus_kansallisuus   = '',
              sisviesti1                       = '',
              sisviesti2                       = '{$myyntilasku['MYP_MYYNTINRO']}',
              splittauskielto                  = '',
              summa                            = '{$myyntilasku['MYP_YHTEENSA']}',
              summa_valuutassa                 = '{$myyntilasku['MYP_YHTEENSA']}',
              suoraveloitus                    = '',
              swift                            = '',
              tapvm                            = '{$myyntilasku['MYP_PVM']}',
              tila                             = 'U',
              tilaustyyppi                     = '',
              tilausvahvistus                  = '',
              tilausyhteyshenkilo              = '',
              tilinumero                       = '',
              toimaika                         = '{$myyntilasku['MYP_PVM']}',
              toimitusehto                     = '$toimitusehto',
              toimitustapa                     = '$toimitustapa',
              toimvko                          = '',
              toim_maa                         = '$toimmaa',
              toim_nimi                        = '$toimnimi',
              toim_nimitark                    = '$toimnimitark',
              toim_osoite                      = '$toimosoite',
              toim_ovttunnus                   = '$toimovttunnus',
              toim_postino                     = '$toimpostino',
              toim_postitp                     = '$toimpostitp',
              tullausnumero                    = '',
              tulostusalue                     = '',
              tunnus                           = '',
              tunnusnippu                      = '',
              ultilno                          = '',
              vahennettava_era                 = '',
              vakuutus                         = '',
              valkoodi                         = 'EUR',
              vanhatunnus                      = '',
              varasto                          = '',
              verkkotunnus                     = '$asiakasrow[verkkotunnus]',
              vienti                           = '',
              vientipaperit_palautettu         = '',
              vienti_kurssi                    = '1',
              viesti                           = '{$myyntilasku['MYP_VIITTEENNE']}',
              viikorkoeur                      = '',
              viikorkopros                     = '13',
              viite                            = '{$myyntilasku['MYP_VIITE']}',
              viitetxt                         = '{$myyntilasku['MYP_MYYNTINRO']}',
              yhtio                            = '$yhtio',
              yhtio_kotipaikka                 = '$yhtiorow[kotipaikka]',
              yhtio_maa                        = '$yhtiorow[maa]',
              yhtio_nimi                       = '$yhtiorow[nimi]',
              yhtio_osoite                     = '$yhtiorow[osoite]',
              yhtio_ovttunnus                  = '$yhtiorow[ovttunnus]',
              yhtio_postino                    = '$yhtiorow[postino]',
              yhtio_postitp                    = '$yhtiorow[postitp]',
              yhtio_toimipaikka                = '$toimipaikka',
              ytunnus                          = '$asiakasrow[ytunnus]'";
    $result = pupe_query($query);
    $uxtunnus = mysql_insert_id();
    // echo "\n uxtunnus= $uxtunnus \n";
    $query3 = "INSERT INTO laskun_lisatiedot SET
               asiakkaan_kohde                          = '',
               kantaasiakastunnus                       = '',
               kasinsyotetty_viite                      = '',
               kolm_maa                                 = '',
               kolm_nimi                                = '',
               kolm_nimitark                            = '',
               kolm_osoite                              = '',
               kolm_ovttunnus                           = '',
               kolm_postino                             = '',
               kolm_postitp                             = '',
               laatija                                  = '$laatija',
               laskutus_maa                             = '$laskutusmaa',    # tähän laskulta tiedot
               laskutus_nimi                            = '$laskutusnimi',
               laskutus_nimitark                        = '$laskutusnimitark',
               laskutus_osoite                          = '$laskutusosoite',
               laskutus_postino                         = '$laskutuspostino',
               laskutus_postitp                         = '$laskutuspostitp',
               luontiaika                               = '$luontiaika',
               muutospvm                                = '$luontiaika',
               muuttaja                                 = '$laatija',
               otunnus                                  = '$uxtunnus',
               projekti                                 = '',
               projektipaallikko                        = '',
               rahlaskelma_ekaerpcm                     = '',
               rahlaskelma_erankasittelymaksu           = '',
               rahlaskelma_hetu_tarkistus               = '',
               rahlaskelma_huoltokirja                  = '',
               rahlaskelma_jaannosvelka_vaihtokohteesta = '',
               rahlaskelma_kayttoohjeet                 = '',
               rahlaskelma_kayttotarkoitus              = '',
               rahlaskelma_kuntotestitodistus           = '',
               rahlaskelma_luottoaikakk                 = '',
               rahlaskelma_lyhennystapa                 = '',
               rahlaskelma_maksuerienlkm                = '',
               rahlaskelma_marginaalikorko              = '',
               rahlaskelma_muutluottokustannukset       = '',
               rahlaskelma_nfref                        = '',
               rahlaskelma_opastus                      = '',
               rahlaskelma_perustamiskustannus          = '',
               rahlaskelma_poikkeava_era                = '',
               rahlaskelma_rahoitettava_positio         = '',
               rahlaskelma_sopajankorko                 = '',
               rahlaskelma_takuukirja                   = '',
               rahlaskelma_tilinavausmaksu              = '',
               rahlaskelma_viitekorko                   = '',
               rekisteilmo_kieli                        = '',
               rekisteilmo_laminointi                   = '',
               rekisteilmo_lisatietoja                  = '',
               rekisteilmo_omistaja                     = '',
               rekisteilmo_omistajankotikunta           = '',
               rekisteilmo_omistajienlkm                = '',
               rekisteilmo_paakayttokunta               = '',
               rekisteilmo_rekisterinumero              = '',
               rekisteilmo_suoramarkkinointi            = '',
               rekisteilmo_tyyppi                       = '',
               rivihintoja_ei_nayteta                   = '',
               saate                                    = '',
               seuranta                                 = '',
               sopimus_alkupvm                          = '',
               sopimus_kk                               = '',
               sopimus_loppupvm                         = '',
               sopimus_pp                               = '',
               toimitusehto2                            = '',
               ulkoinen_tarkenne                        = '',
               tunnusnippu_tarjous                      = '',
               vakuutushak_alkamispaiva                 = '',
               vakuutushak_kaskolaji                    = '',
               vakuutushak_maksuerat                    = '',
               vakuutushak_moottori                     = '',
               vakuutushak_perusomavastuu               = '',
               vakuutushak_runko_takila_purjeet         = '',
               vakuutushak_vakuutusyhtio                = '',
               vakuutushak_varusteet                    = '',
               vakuutushak_yhteensa                     = '',
               yhteyshenkilo_kaupallinen                = '',
               yhteyshenkilo_tekninen                   = '',
               yhtio                                    = '$yhtio'";
    $result = pupe_query($query3);
  }
  else {
    $uxrow = mysql_fetch_assoc($uxresult);
    $uxtunnus = $uxrow['tunnus'];
    /*
      $maksettu = 0;

      // echo "\n 506: $kassalipas, $asiakasnro, $uxtunnus, $myyntilasku[MYP_YHTEENSA], $uxrow[summa], $uxrow[laskunro], $myyntilasku[MYP_MYYNTINRO] \n";

      // laatija = 'konversio', päivitetään laskun loppusumma
      // TODO, päivitetään laskulle summat, mikäli löytynyt lasku on tehty konversion toimesta (konversio avoimista myyntilaskuista ei sisältänyt laskun summaa, vaan siellä summaksi meni avoin saldo)
      if ($kassalipas == 0 or $uxrow['laatija'] == $laatija or $uxrow['laatija'] == $laatija3) {
        $katsumma = $uxrow['summa'] + $myyntilasku['MYP_YHTEENSA'];
        $katsumma_veroton = $uxrow['arvo'] + $myyntilasku['MYP_VEROTON_YHT'];
        $kate = $uxrow['kate'] + $myyntilasku['MYP_KATETTA'];
      }
      else {
        $katsumma = $uxrow['summa'];
        $katsumma_veroton = $uxrow['arvo'];
        $kate = $uxrow['kate'];
      }

      if ($kassalipas != 0 and $kassalipas != '') $maksettu = $katsumma;

      if (($uxrow['laatija'] == $laatija or $uxrow['laatija'] == $laatija3)  and $uxrow['summa'] != $myyntilasku['MYP_YHTEENSA']) {
        if ($kassalipas == '') {

          // echo "\n 526: kassalipas=0, $uxtunnus  \n";

          $query = "UPDATE lasku
                    SET summa              = '{$katsumma}',
                     summa_valuutassa = '{$katsumma}',
                    arvo              = '{$katsumma_veroton}',
                    arvo_valuutassa   = '{$katsumma_veroton}',
                    saldo_maksettu    = '{$maksettu}',
                    kate              = '{$kate}'
                    WHERE yhtio       = '{$yhtio}'
                    AND tunnus        = '{$uxtunnus}'";
          pupe_query($query);
        }
        else {

          //echo "\n 539: kassalipas= $kassalipas, $uxtunnus \n";

          $query = "UPDATE lasku
                    SET arvo              = '{$katsumma_veroton}',
                    arvo_valuutassa = '{$katsumma_veroton}',
                    saldo_maksettu  = '{$maksettu}',
                    kate            = '{$kate}'
                    WHERE yhtio     = '{$yhtio}'
                    AND tunnus      = '{$uxtunnus}'";
          pupe_query($query);
        }
      }
      */
  }

  // Tässä lisätään käteismyyntien LX-otsikko, ellei sitä ole jo olemassa
  $lxtunnus = 0;

  if ($myyntilasku['MYP_LASKUNRO'] == 0) {
    $querylx = "SELECT laskunro, tunnus, summa, arvo, kate
                FROM lasku
                WHERE yhtio    = '$yhtio'
                AND tila       = 'L'
                AND alatila    = 'X'
                AND laskunro   = '$laskunro'
                AND maksuehto  = '$maksuehto'
                AND kassalipas = '$kassalipas'
                AND laatija    in $laatija2 ";
    $lxresult = pupe_query($querylx);

    if (mysql_num_rows($lxresult) == 0) {

      $query1 = "INSERT INTO lasku SET
                 aktiivinen_kuljetus              = '',
                 aktiivinen_kuljetus_kansallisuus = '',
                 alatila                          = 'X',
                 alv                              = '$alviprosentti',
                 alv_tili                         = '',
                 arvo                             = '{$myyntilasku['MYP_VEROTON_YHT']}',
                 arvo_valuutassa                  = '{$myyntilasku['MYP_VEROTON_YHT']}',
                 asiakkaan_tilausnumero           = '',
                 bruttopaino                      = '',
                 chn                              = '$asiakasrow[chn]',
                 clearing                         = '',
                 comments                         = '$viitteemme',
                 ebid                             = '',
                 eilahetetta                      = '',
                 erikoisale                       = '',
                 erpcm                            = '{$myyntilasku['MYP_ERAPVM']}',
                 factoringsiirtonumero            = '',
                 h1time                           = '$tallpvm',
                 h2time                           = '',
                 h3time                           = '',
                 h4time                           = '',
                 h5time                           = '',
                 hinta                            = '',
                 huolitsija                       = '',
                 hyvak1                           = '$laatija',
                 hyvak2                           = '',
                 hyvak3                           = '',
                 hyvak4                           = '',
                 hyvak5                           = '',
                 hyvaksyja_nyt                    = '',
                 hyvaksynnanmuutos                = '',
                 jakelu                           = '',
                 jaksotettu                       = '',
                 jtkielto                         = '',
                 kapvm                            = '{$myyntilasku['MYP_KASSAALEPV']}',
                 kassalipas                       = '$kassalipas',
                 kasumma                          = '',
                 kasumma_valuutassa               = '{$myyntilasku['MYP_KASSAALEPROS']}',
                 kate                             = '{$myyntilasku['MYP_KATETTA']}',
                 kauppatapahtuman_luonne          = '',
                 kerayslista                      = '',
                 kerayspvm                        = '{$myyntilasku['MYP_PVM']}',
                 keraysvko                        = '',
                 ketjutus                         = '$ketjutus',
                 kirjoitin                        = '',
                 kohde                            = '',
                 kohdistettu                      = '',
                 kontti                           = '',
                 kuljetus                         = '',
                 kuljetusmuoto                    = '',
                 laatija                          = '$laatija',
                 lahetepvm                        = '{$myyntilasku['MYP_PVM']}',
                 lapvm                            = '{$myyntilasku['MYP_PVM']}',
                 laskunro                         = '$laskunro',
                 laskutettu                       = '$tapvm',
                 laskuttaja                       = '$laskuttaja',
                 laskutusvkopv                    = '',
                 liitostunnus                     = '$asiakasrow[tunnus]',
                 lisattava_era                    = '',
                 luontiaika                       = '$luontiaika',
                 maa                              = '$asiakasrow[maa]',
                 maa_alkupera                     = '',
                 maa_lahetys                      = '',
                 maa_maara                        = '',
                 maksaja                          = '',
                 maksuaika                        = '',
                 maksuehto                        = '$maksuehto',
                 maksuteksti                      = '',
                 maksutyyppi                      = '',
                 maksu_kurssi                     = '',
                 maksu_tili                       = '',
                 mapvm                            = '',
                 muutospvm                        = '',
                 muuttaja                         = '',
                 myyja                            = '',
                 nimi                             = '$asiakasrow[nimi]',
                 nimitark                         = '$asiakasrow[nimitark]',
                 noutaja                          = '',
                 olmapvm                          = '',
                 osatoimitus                      = '',
                 osoite                           = '$asiakasrow[osoite]',
                 osoitetark                       = '',
                 ovttunnus                        = '$asiakasrow[ovttunnus]',
                 pakkaamo                         = '',
                 pankki1                          = '',
                 pankki2                          = '',
                 pankki3                          = '',
                 pankki4                          = '',
                 pankki_haltija                   = '',
                 piiri                            = '$asiakasrow[piiri]',
                 poistumistoimipaikka             = '',
                 poistumistoimipaikka_koodi       = '',
                 popvm                            = '',
                 postino                          = '$asiakasrow[postino]',
                 postitp                          = '$asiakasrow[postitp]',
                 pyoristys                        = '{$myyntilasku['MYP_PYORISTYS']}',
                 pyoristys_valuutassa             = '{$myyntilasku['MYP_PYORISTYS']}',
                 rahti                            = '',
                 rahtisopimus                     = '',
                 rahtivapaa                       = '',
                 rahti_etu                        = '',
                 rahti_etu_alv                    = '',
                 rahti_huolinta                   = '',
                 saldo_maksettu                   = '',
                 saldo_maksettu_valuutassa        = '',
                 sisainen                         = '',
                 sisamaan_kuljetus                = '',
                 sisamaan_kuljetusmuoto           = '',
                 sisamaan_kuljetus_kansallisuus   = '',
                 sisviesti1                       = '',
                 sisviesti2                       = '',
                 splittauskielto                  = '',
                 summa                            = '{$myyntilasku['MYP_YHTEENSA']}',
                 summa_valuutassa                 = '{$myyntilasku['MYP_YHTEENSA']}',
                 suoraveloitus                    = '',
                 swift                            = '',
                 tapvm                            = '{$myyntilasku['MYP_PVM']}',
                 tila                             = 'L',
                 tilaustyyppi                     = '',
                 tilausvahvistus                  = '',
                 tilausyhteyshenkilo              = '',
                 tilinumero                       = '',
                 toimaika                         = '{$myyntilasku['MYP_PVM']}',
                 toimitusehto                     = '$toimitusehto',
                 toimitustapa                     = '$toimitustapa',
                 toimvko                          = '',
                 toim_maa                         = '$asiakasrow[toim_maa]',
                 toim_nimi                        = '$asiakasrow[toim_nimi]',
                 toim_nimitark                    = '$asiakasrow[toim_nimitark]',
                 toim_osoite                      = '$asiakasrow[toim_osoite]',
                 toim_ovttunnus                   = '$asiakasrow[toim_ovttunnus]',
                 toim_postino                     = '$asiakasrow[toim_postino]',
                 toim_postitp                     = '$asiakasrow[toim_postitp]',
                 tullausnumero                    = '',
                 tulostusalue                     = '',
                 tunnus                           = '',
                 tunnusnippu                      = '',
                 ultilno                          = '',
                 vahennettava_era                 = '',
                 vakuutus                         = '',
                 valkoodi                         = 'EUR',
                 vanhatunnus                      = '',
                 varasto                          = '',
                 verkkotunnus                     = '$asiakasrow[verkkotunnus]',
                 vienti                           = '',
                 vientipaperit_palautettu         = '',
                 vienti_kurssi                    = '1',
                 viesti                           = '{$myyntilasku['MYP_VIITTEENNE']}',
                 viikorkoeur                      = '',
                 viikorkopros                     = '13',
                 viite                            = '{$myyntilasku['MYP_VIITE']}',
                 viitetxt                         = '{$myyntilasku['MYP_MYYNTINRO']}',
                 yhtio                            = '$yhtio',
                 yhtio_kotipaikka                 = '$yhtiorow[kotipaikka]',
                 yhtio_maa                        = '$yhtiorow[maa]',
                 yhtio_nimi                       = '$yhtiorow[nimi]',
                 yhtio_osoite                     = '$yhtiorow[osoite]',
                 yhtio_ovttunnus                  = '$yhtiorow[ovttunnus]',
                 yhtio_postino                    = '$yhtiorow[postino]',
                 yhtio_postitp                    = '$yhtiorow[postitp]',
                 yhtio_toimipaikka                = '$toimipaikka',
                 ytunnus                          = '$asiakasrow[ytunnus]'";
      pupe_query($query1);
      $lxtunnus = mysql_insert_id();

      $query3 = "INSERT INTO laskun_lisatiedot SET
                 asiakkaan_kohde                          = '',
                 kantaasiakastunnus                       = '',
                 kasinsyotetty_viite                      = '',
                 kolm_maa                                 = '',
                 kolm_nimi                                = '',
                 kolm_nimitark                            = '',
                 kolm_osoite                              = '',
                 kolm_ovttunnus                           = '',
                 kolm_postino                             = '',
                 kolm_postitp                             = '',
                 laatija                                  = '$laatija',
                 laskutus_maa                             = '$laskutusmaa',
                 laskutus_nimi                            = '$laskutusnimi',
                 laskutus_nimitark                        = '$laskutusnimitark',
                 laskutus_osoite                          = '$laskutusosoite',
                 laskutus_postino                         = '$laskutuspostino',
                 laskutus_postitp                         = '$laskutuspostitp',
                 luontiaika                               = '$luontiaika',
                 muutospvm                                = '$luontiaika',
                 muuttaja                                 = '$laatija',
                 otunnus                                  = '$lxtunnus',
                 projekti                                 = '',
                 projektipaallikko                        = '',
                 rahlaskelma_ekaerpcm                     = '',
                 rahlaskelma_erankasittelymaksu           = '',
                 rahlaskelma_hetu_tarkistus               = '',
                 rahlaskelma_huoltokirja                  = '',
                 rahlaskelma_jaannosvelka_vaihtokohteesta = '',
                 rahlaskelma_kayttoohjeet                 = '',
                 rahlaskelma_kayttotarkoitus              = '',
                 rahlaskelma_kuntotestitodistus           = '',
                 rahlaskelma_luottoaikakk                 = '',
                 rahlaskelma_lyhennystapa                 = '',
                 rahlaskelma_maksuerienlkm                = '',
                 rahlaskelma_marginaalikorko              = '',
                 rahlaskelma_muutluottokustannukset       = '',
                 rahlaskelma_nfref                        = '',
                 rahlaskelma_opastus                      = '',
                 rahlaskelma_perustamiskustannus          = '',
                 rahlaskelma_poikkeava_era                = '',
                 rahlaskelma_rahoitettava_positio         = '',
                 rahlaskelma_sopajankorko                 = '',
                 rahlaskelma_takuukirja                   = '',
                 rahlaskelma_tilinavausmaksu              = '',
                 rahlaskelma_viitekorko                   = '',
                 rekisteilmo_kieli                        = '',
                 rekisteilmo_laminointi                   = '',
                 rekisteilmo_lisatietoja                  = '',
                 rekisteilmo_omistaja                     = '',
                 rekisteilmo_omistajankotikunta           = '',
                 rekisteilmo_omistajienlkm                = '',
                 rekisteilmo_paakayttokunta               = '',
                 rekisteilmo_rekisterinumero              = '',
                 rekisteilmo_suoramarkkinointi            = '',
                 rekisteilmo_tyyppi                       = '',
                 rivihintoja_ei_nayteta                   = '',
                 saate                                    = '',
                 seuranta                                 = '',
                 sopimus_alkupvm                          = '',
                 sopimus_kk                               = '',
                 sopimus_loppupvm                         = '',
                 sopimus_pp                               = '',
                 toimitusehto2                            = '',
                 ulkoinen_tarkenne                        = '',
                 tunnusnippu_tarjous                      = '',
                 vakuutushak_alkamispaiva                 = '',
                 vakuutushak_kaskolaji                    = '',
                 vakuutushak_maksuerat                    = '',
                 vakuutushak_moottori                     = '',
                 vakuutushak_perusomavastuu               = '',
                 vakuutushak_runko_takila_purjeet         = '',
                 vakuutushak_vakuutusyhtio                = '',
                 vakuutushak_varusteet                    = '',
                 vakuutushak_yhteensa                     = '',
                 yhteyshenkilo_kaupallinen                = '',
                 yhteyshenkilo_tekninen                   = '',
                 yhtio                                    = '$yhtio'";
      $result = pupe_query($query3);
      $loppusumma = 0;
    }
    else {
      $lxlasku = mysql_fetch_assoc($lxresult);
      $lxtunnus = $lxlasku['tunnus'];
      /*
        $yhtsumma = $lxlasku['summa'] + $myyntilasku['MYP_YHTEENSA'];
        $yhtarvo = $lxlasku['arvo'] + $myyntilasku['MYP_VEROTON_YHT'];
        $yhtkate = $lxlasku['kate'] + $myyntilasku['MYP_KATETTA'];

        // echo "\n 787: $kassalipas, $asiakasnro, $lxtunnus, $lxlasku[tunnus], $myyntilasku[MYP_YHTEENSA], $lxlasku[summa], $yhtsumma, $uxrow[laskunro] \n";
        // echo "\n 822: $lxtunnus, $kassalipas, $yhtsumma ";

        $query = "UPDATE   lasku
                  SET   summa        = '{$yhtsumma}',
                      summa_valuutassa = '{$yhtsumma}',
                      arvo             = '{$yhtarvo}',
                      arvo_valuutassa  = '{$yhtarvo}',
                      kate             = '{$yhtkate}'
                  WHERE   yhtio        = '{$yhtio}'
                  AND    tunnus        = '{$lxtunnus}'";
        pupe_query($query);
        //echo "\n$query\n";
        */
    }
  }

  $query = "SELECT *
            FROM futur_myyntriv
            WHERE MYR_MYYNTINRO = '{$myyntilasku['MYP_MYYNTINRO']}'";
  $resrivi = mysql_query($query);

  while ($myyntirivi = mysql_fetch_assoc($resrivi)) {

    // echo "\n 830: $myyntirivi[MYR_TUOTEKOODI], length($myyntirivi[MYR_TUOTEKOODI]), $myyntirivi[MYR_NIMI] <br><br>";

    if (!isset($myyntirivi['MYR_TUOTEKOODI']) and $myyntirivi['MYR_TUOTEKOODI'] == '' and substr($myyntirivi['MYR_NIMI'], 0, 10) == 'Lähete nro') {

      $lahetenro = trim(substr($myyntirivi['MYR_NIMI'], 10, 9));
      $loppusumma = 0;
      $katesumma = 0;
      $lxtunnus = 0;
      $viitteenne = 'viitteenne';
      $sisviesti = pupesoft_cleanstring($myyntirivi['MYR_NIMI']);

      // LX-otsikko
      $query = "SELECT laskunro, tunnus
                FROM lasku
                WHERE yhtio    = '$yhtio'
                AND tila       = 'L'
                AND alatila    = 'X'
                AND laskunro   = '$laskunro'
                AND viitetxt   = '$myyntilasku[MYP_MYYNTINRO]'
                AND sisviesti2 = '{$sisviesti}'
                AND laatija    = '$laatija'";
      $lxresult = pupe_query($query);

      if (mysql_num_rows($lxresult) == 0 and $myyntilasku['MYP_LASKUNRO'] > 0) {

        $query1 = "INSERT INTO lasku SET
                   aktiivinen_kuljetus              = '',
                   aktiivinen_kuljetus_kansallisuus = '',
                   alatila                          = 'X',
                   alv                              = '$alviprosentti',
                   alv_tili                         = '',
                   arvo                             = '',
                   arvo_valuutassa                  = '',
                   asiakkaan_tilausnumero           = '',
                   bruttopaino                      = '',
                   chn                              = '$asiakasrow[chn]',
                   clearing                         = '',
                   comments                         = '$viitteemme',
                   ebid                             = '',
                   eilahetetta                      = '',
                   erikoisale                       = '',
                   erpcm                            = '{$myyntilasku['MYP_ERAPVM']}',
                   factoringsiirtonumero            = '',
                   h1time                           = '$tallpvm',
                   h2time                           = '',
                   h3time                           = '',
                   h4time                           = '',
                   h5time                           = '',
                   hinta                            = '',
                   huolitsija                       = '',
                   hyvak1                           = '$laatija',
                   hyvak2                           = '',
                   hyvak3                           = '',
                   hyvak4                           = '',
                   hyvak5                           = '',
                   hyvaksyja_nyt                    = '',
                   hyvaksynnanmuutos                = '',
                   jakelu                           = '',
                   jaksotettu                       = '',
                   jtkielto                         = '',
                   kapvm                            = '{$myyntilasku['MYP_KASSAALEPV']}',
                   kassalipas                       = '',
                   kasumma                          = '',
                   kasumma_valuutassa               = '{$myyntilasku['MYP_KASSAALEPROS']}',
                   kate                             = '$katesumma',
                   kauppatapahtuman_luonne          = '',
                   kerayslista                      = '',
                   kerayspvm                        = '{$myyntilasku['MYP_PVM']}',
                   keraysvko                        = '',
                   ketjutus                         = '$ketjutus',
                   kirjoitin                        = '',
                   kohde                            = '',
                   kohdistettu                      = '',
                   kontti                           = '',
                   kuljetus                         = '',
                   kuljetusmuoto                    = '',
                   laatija                          = '$laatija',
                   lahetepvm                        = '{$myyntilasku['MYP_PVM']}',
                   lapvm                            = '{$myyntilasku['MYP_PVM']}',
                   laskunro                         = '$laskunro',
                   laskutettu                       = '$tapvm',
                   laskuttaja                       = '$laskuttaja',
                   laskutusvkopv                    = '',
                   liitostunnus                     = '$asiakasrow[tunnus]',
                   lisattava_era                    = '',
                   luontiaika                       = '$luontiaika',
                   maa                              = '$asiakasrow[maa]',
                   maa_alkupera                     = '',
                   maa_lahetys                      = '',
                   maa_maara                        = '',
                   maksaja                          = '',
                   maksuaika                        = '',
                   maksuehto                        = '$maksuehto',
                   maksuteksti                      = '',
                   maksutyyppi                      = '',
                   maksu_kurssi                     = '',
                   maksu_tili                       = '',
                   mapvm                            = '',
                   muutospvm                        = '',
                   muuttaja                         = '',
                   myyja                            = '',
                   nimi                             = '$asiakasrow[nimi]',
                   nimitark                         = '$asiakasrow[nimitark]',
                   noutaja                          = '',
                   olmapvm                          = '',
                   osatoimitus                      = '',
                   osoite                           = '$asiakasrow[osoite]',
                   osoitetark                       = '',
                   ovttunnus                        = '$asiakasrow[ovttunnus]',
                   pakkaamo                         = '',
                   pankki1                          = '',
                   pankki2                          = '',
                   pankki3                          = '',
                   pankki4                          = '',
                   pankki_haltija                   = '',
                   piiri                            = '$asiakasrow[piiri]',
                   poistumistoimipaikka             = '',
                   poistumistoimipaikka_koodi       = '',
                   popvm                            = '',
                   postino                          = '$asiakasrow[postino]',
                   postitp                          = '$asiakasrow[postitp]',
                   pyoristys                        = '{$myyntilasku['MYP_PYORISTYS']}',
                   pyoristys_valuutassa             = '{$myyntilasku['MYP_PYORISTYS']}',
                   rahti                            = '',
                   rahtisopimus                     = '',
                   rahtivapaa                       = '',
                   rahti_etu                        = '',
                   rahti_etu_alv                    = '',
                   rahti_huolinta                   = '',
                   saldo_maksettu                   = '',
                   saldo_maksettu_valuutassa        = '',
                   sisainen                         = '',
                   sisamaan_kuljetus                = '',
                   sisamaan_kuljetusmuoto           = '',
                   sisamaan_kuljetus_kansallisuus   = '',
                   sisviesti1                       = '',
                   sisviesti2                       = '{$sisviesti}',
                   splittauskielto                  = '',
                   summa                            = '$loppusumma',
                   summa_valuutassa                 = '$loppusumma',
                   suoraveloitus                    = '',
                   swift                            = '',
                   tapvm                            = '{$myyntilasku['MYP_PVM']}',
                   tila                             = 'L',
                   tilaustyyppi                     = '',
                   tilausvahvistus                  = '',
                   tilausyhteyshenkilo              = '',
                   tilinumero                       = '',
                   toimaika                         = '{$myyntilasku['MYP_PVM']}',
                   toimitusehto                     = '$toimitusehto',
                   toimitustapa                     = '$toimitustapa',
                   toimvko                          = '',
                   toim_maa                         = '$asiakasrow[toim_maa]',
                   toim_nimi                        = '$asiakasrow[toim_nimi]',
                   toim_nimitark                    = '$asiakasrow[toim_nimitark]',
                   toim_osoite                      = '$asiakasrow[toim_osoite]',
                   toim_ovttunnus                   = '$asiakasrow[toim_ovttunnus]',
                   toim_postino                     = '$asiakasrow[toim_postino]',
                   toim_postitp                     = '$asiakasrow[toim_postitp]',
                   tullausnumero                    = '',
                   tulostusalue                     = '',
                   tunnus                           = '',
                   tunnusnippu                      = '',
                   ultilno                          = '',
                   vahennettava_era                 = '',
                   vakuutus                         = '',
                   valkoodi                         = 'EUR',
                   vanhatunnus                      = '',
                   varasto                          = '',
                   verkkotunnus                     = '$asiakasrow[verkkotunnus]',
                   vienti                           = '',
                   vientipaperit_palautettu         = '',
                   vienti_kurssi                    = '1',
                   viesti                           = '{$myyntilasku['MYP_VIITTEENNE']}',
                   viikorkoeur                      = '',
                   viikorkopros                     = '13',
                   viite                            = '{$myyntilasku['MYP_VIITE']}',
                   viitetxt                         = '{$myyntilasku['MYP_MYYNTINRO']}',
                   yhtio                            = '$yhtio',
                   yhtio_kotipaikka                 = '$yhtiorow[kotipaikka]',
                   yhtio_maa                        = '$yhtiorow[maa]',
                   yhtio_nimi                       = '$yhtiorow[nimi]',
                   yhtio_osoite                     = '$yhtiorow[osoite]',
                   yhtio_ovttunnus                  = '$yhtiorow[ovttunnus]',
                   yhtio_postino                    = '$yhtiorow[postino]',
                   yhtio_postitp                    = '$yhtiorow[postitp]',
                   yhtio_toimipaikka                = '$toimipaikka',
                   ytunnus                          = '$asiakasrow[ytunnus]'";
        pupe_query($query1);
        $lxtunnus = mysql_insert_id();

        $query3 = "INSERT INTO laskun_lisatiedot SET
                   asiakkaan_kohde                          = '',
                   kantaasiakastunnus                       = '',
                   kasinsyotetty_viite                      = '',
                   kolm_maa                                 = '',
                   kolm_nimi                                = '',
                   kolm_nimitark                            = '',
                   kolm_osoite                              = '',
                   kolm_ovttunnus                           = '',
                   kolm_postino                             = '',
                   kolm_postitp                             = '',
                   laatija                                  = '$laatija',
                   laskutus_maa                             = '$laskutusmaa',
                   laskutus_nimi                            = '$laskutusnimi',
                   laskutus_nimitark                        = '$laskutusnimitark',
                   laskutus_osoite                          = '$laskutusosoite',
                   laskutus_postino                         = '$laskutuspostino',
                   laskutus_postitp                         = '$laskutuspostitp',
                   luontiaika                               = '$luontiaika',
                   muutospvm                                = '$luontiaika',
                   muuttaja                                 = '$laatija',
                   otunnus                                  = '$lxtunnus',
                   projekti                                 = '',
                   projektipaallikko                        = '',
                   rahlaskelma_ekaerpcm                     = '',
                   rahlaskelma_erankasittelymaksu           = '',
                   rahlaskelma_hetu_tarkistus               = '',
                   rahlaskelma_huoltokirja                  = '',
                   rahlaskelma_jaannosvelka_vaihtokohteesta = '',
                   rahlaskelma_kayttoohjeet                 = '',
                   rahlaskelma_kayttotarkoitus              = '',
                   rahlaskelma_kuntotestitodistus           = '',
                   rahlaskelma_luottoaikakk                 = '',
                   rahlaskelma_lyhennystapa                 = '',
                   rahlaskelma_maksuerienlkm                = '',
                   rahlaskelma_marginaalikorko              = '',
                   rahlaskelma_muutluottokustannukset       = '',
                   rahlaskelma_nfref                        = '',
                   rahlaskelma_opastus                      = '',
                   rahlaskelma_perustamiskustannus          = '',
                   rahlaskelma_poikkeava_era                = '',
                   rahlaskelma_rahoitettava_positio         = '',
                   rahlaskelma_sopajankorko                 = '',
                   rahlaskelma_takuukirja                   = '',
                   rahlaskelma_tilinavausmaksu              = '',
                   rahlaskelma_viitekorko                   = '',
                   rekisteilmo_kieli                        = '',
                   rekisteilmo_laminointi                   = '',
                   rekisteilmo_lisatietoja                  = '',
                   rekisteilmo_omistaja                     = '',
                   rekisteilmo_omistajankotikunta           = '',
                   rekisteilmo_omistajienlkm                = '',
                   rekisteilmo_paakayttokunta               = '',
                   rekisteilmo_rekisterinumero              = '',
                   rekisteilmo_suoramarkkinointi            = '',
                   rekisteilmo_tyyppi                       = '',
                   rivihintoja_ei_nayteta                   = '',
                   saate                                    = '',
                   seuranta                                 = '',
                   sopimus_alkupvm                          = '',
                   sopimus_kk                               = '',
                   sopimus_loppupvm                         = '',
                   sopimus_pp                               = '',
                   toimitusehto2                            = '',
                   ulkoinen_tarkenne                        = '',
                   tunnusnippu_tarjous                      = '',
                   vakuutushak_alkamispaiva                 = '',
                   vakuutushak_kaskolaji                    = '',
                   vakuutushak_maksuerat                    = '',
                   vakuutushak_moottori                     = '',
                   vakuutushak_perusomavastuu               = '',
                   vakuutushak_runko_takila_purjeet         = '',
                   vakuutushak_vakuutusyhtio                = '',
                   vakuutushak_varusteet                    = '',
                   vakuutushak_yhteensa                     = '',
                   yhteyshenkilo_kaupallinen                = '',
                   yhteyshenkilo_tekninen                   = '',
                   yhtio                                    = '$yhtio'";
        $result = pupe_query($query3);
        $lxlasku = mysql_fetch_assoc($lxresult);
      }

      if ($lxtunnus == 0) $lxtunnus = $lxlasku['tunnus'];

    }
    elseif ($myyntirivi['MYR_TUOTEKOODI'] != '' and substr($myyntirivi['MYR_NIMI'], 0, 10) != 'Lähete nro' and substr($myyntirivi['MYR_NIMI'], 0, 13) != '----------  L' and $myyntirivi['MYR_RIVISUMMA'] != 0) {
      // echo "\n 1105: $myyntirivi[MYR_TUOTEKOODI], $myyntirivi[MYR_TOIMITTAJANRO] ";
      $lahetenrorivi = $myyntirivi['MYR_LAHETENRO'];

      // echo "\n 1103: $myyntirivi[MYR_MYYNTINRO], $myyntirivi[MYR_TOIMITTAJANRO], $myyntirivi[MYR_TUOTEKOODI], $myyntirivi[MYR_MYYNTIHINTA] \n";

      $tuoteno = pupesoft_cleanstring($myyntirivi['MYR_TUOTEKOODI']);
      $tuoteno = futurmuunnos_tuote($myyntirivi['MYR_TOIMITTAJANRO'], $tuoteno);
      $summa = round($myyntirivi['MYR_RIVISUMMA'] / ((100 + $myyntirivi['MYR_VEROKANTA']) / 100), 2);
      $ahinta = round($myyntirivi['MYR_MYYNTIHINTA'] / ((100 + $myyntirivi['MYR_VEROKANTA']) / 100), 2);
      $rivikate = round($myyntirivi['MYR_KATE'] / ((100 + $myyntirivi['MYR_VEROKANTA']) / 100), 2);
      $loppusumma = $loppusumma + $summa;
      $katesumma = $katesumma + $rivikate;
      $nimitys = pupesoft_cleanstring($myyntirivi['MYR_NIMI']);

      $query = "SELECT *
                FROM tuote
                WHERE yhtio     = '$yhtio'
                    AND tuoteno = '$tuoteno' limit 1";
      $tuoteresult = pupe_query($query);

      if (mysql_num_rows($tuoteresult) == 1) {
        $tuoterow = mysql_fetch_assoc($tuoteresult);
        $tuoteosasto  = $tuoterow['osasto'];
        $tuotetry    = $tuoterow['try'];
        // echo "\n 1127: $tuoterow[try], $tuoterow[tuoteno] ";
      }
      else {
        $qu = "INSERT INTO tuote SET
               yhtio       = '$yhtio',
               tuoteno     = '$tuoteno',
               nimitys     = '$nimitys',
               lyhytkuvaus = 'Myyntihistoriakonversion perustama',
               osasto      = '',
               try         = '',
               kuvaus      = '',
               yksikko     = 'KPL',
               status      = 'P',
               alv         = 24,
               muuta       = '{$myyntirivi['MYR_TOIMITTAJANRO']}',
               luontiaika  = now(),
               laatija     = '$laatija'";

        pupe_query($qu);
        $tuoteosasto  = '';
        $tuotetry    = '';
        echo "\n Tuote: $tuoteno lisätty, status=P! \n";
      }

      if ($myyntirivi['MYR_MAARA'] == '0') {
        $var   = 'P';
      }
      else {
        $var = '';
      }

      $alviprosentti = $myyntirivi['MYR_VEROKANTA'];

      $hyllynro  = '0';
      $hyllyvali = '0';
      $hyllytaso = '0';

      $query4 = "SELECT otunnus
                 FROM tilausrivi
                 WHERE yhtio         = '$yhtio'
                 AND tyyppi          = 'L'
                     AND tuoteno     = '$tuoteno'
                 AND laadittu        = '$luontiaika'
                 AND otunnus         = '$lxtunnus'
                 AND tilaajanrivinro = '{$myyntirivi['MYR_MYYNTINRO']}'
                 AND suuntalava      = '{$myyntirivi['MYR_RIVINRO']}'
                 AND tilkpl          = '{$myyntirivi['MYR_MAARA']}'";
      $riviresult = pupe_query($query4);

      if (mysql_num_rows($riviresult) == '0') {
        $kommentti = pupesoft_cleanstring($myyntirivi['MYR_VIITE']);
        $okhinta = $myyntirivi['MYR_OSTOHINTA'];

        $query5 = "INSERT INTO tilausrivi SET
                   ale1                 = '{$myyntirivi['MYR_ALEPROS']}',
                   ale2                 = '',
                   ale3                 = '',
                   alv                  = '{$myyntirivi['MYR_VEROKANTA']}',
                   erikoisale           = '',
                   hinta                = '$ahinta',
                   hinta_valuutassa     = '',
                   hyllyalue            = '$hyllyalue',
                   hyllynro             = '$hyllynro',
                   hyllytaso            = '$hyllytaso',
                   hyllyvali            = '$hyllyvali',
                   jaksotettu           = '',
                   jt                   = '',
                   kate                 = '$rivikate',
                   keratty              = '$laatija',
                   kerattyaika          = '$luontiaika',
                   kerayspvm            = '$luontiaika',
                   kommentti            = '{$kommentti}',
                   kpl                  = '{$myyntirivi['MYR_MAARA']}',
                   kpl2                 = '',
                   laadittu             = '$luontiaika',
                   laatija              = '$laatija',
                   laskutettu           = '$laatija',
                   laskutettuaika       = '$tapvm',
                   netto                = '',
                   nimitys              = '{$nimitys}',
                   osasto               = '$tuoteosasto',
                   otunnus              = '$lxtunnus',
                   perheid              = '',
                   perheid2             = '',
                   rivihinta            = '$summa',
                   rivihinta_valuutassa = '$summa',
                   suuntalava           = '{$myyntirivi['MYR_RIVINRO']}',    # FUTURIN rivinumero myyntiriviltä
                   tilaajanrivinro      = '{$myyntirivi['MYR_MYYNTINRO']}',  # FUTURIN myyntinumero
                   tilkpl               = '{$myyntirivi['MYR_MAARA']}',
                   toimaika             = '$luontiaika',
                   toimitettu           = '$laatija',
                   toimitettuaika       = '$luontiaika',
                   try                  = '$tuotetry',
                   tuoteno              = '$tuoteno',
                   tyyppi               = 'L',
                   uusiotunnus          = '$uxtunnus',
                   var                  = '$var',
                   var2                 = '',
                   varattu              = '',
                   yhtio                = '$yhtio',
                   yksikko              = 'KPL'";
        $result = pupe_query($query5);
        $rivitunnus = mysql_insert_id();

        $query = "INSERT INTO tilausrivin_lisatiedot
                  SET yhtio        = '$yhtio',
                  positio                = '',
                  tilausrivilinkki       = '',
                  toimittajan_tunnus     = '',
                  ei_nayteta             = '',
                  tilausrivitunnus       = '$rivitunnus',
                  erikoistoimitus_myynti = '',
                  vanha_otunnus          = '$lxtunnus',
                  jarjestys              = '',
                   luontiaika            = '$toimituspvm',
                  laatija                = '$laatija'";
        $result = pupe_query($query);

        $rivitunnus = $rivitunnus * -1;       // ettei jälkilaskenta koske myyntihistoriariveihin, kun toimipisteet tulevat pupeen eri aikaan
        $kpl = $myyntirivi['MYR_MAARA'] * -1;
        $query2 = "INSERT INTO tapahtuma SET
                   hinta      = '$okhinta',
                   hyllyalue  = '$hyllyalue',
                   hyllynro   = '$hyllynro',
                   hyllytaso  = '$hyllytaso',
                   hyllyvali  = '$hyllyvali',
                   kpl        = '$kpl',
                   kplhinta   = '$ahinta',
                   laadittu   = '$toimituspvm',
                   laatija    = '$laatija',
                   laji       = 'laskutus',
                   rivitunnus = '$rivitunnus',
                   selite     = '$asiakasrow[nimi] $asiakasrow[nimitark] ($ahinta) [$summa]',
                   tuoteno    = '$tuoteno',
                   yhtio      = '$yhtio'";
        $result2 = pupe_query($query2);
      }
    }
    elseif (!isset($myyntirivi['MYR_TUOTEKOODI']) and substr($myyntirivi['MYR_NIMI'], 0, 13) == '----------  L' and $myyntilasku['MYP_LASKUNRO'] > 0) {
      $verollinen_summa = (100 + $alviprosentti) / 100 * $loppusumma;

      // echo "\n 1308: $loppusumma, $kassalipas, $lxtunnus ";

      $query = "  update lasku
              set arvo       = '{$loppusumma}',
              arvo_valuutassa   = '{$loppusumma}',
              summa        = '{$verollinen_summa}',
              summa_valuutassa  = '{$verollinen_summa}',
              kate         = '{$katesumma}',
              alv         = '{$alviprosentti}'
              where tunnus = '{$lxtunnus}'";
      pupe_query($query);
      $loppusumma = 0;
      $verollinen_summa = 0;
      $katesumma = 0;
    }
  }

  if ($loppusumma != 0 and $myyntilasku['MYP_LASKUNRO'] > 0) {
    $verollinen_summa = (100 + $alviprosentti) / 100 * $loppusumma;

    // echo "\n 1328: $loppusumma, $kassalipas, $lxtunnus ";

    $query = "  update lasku
            set arvo       = '{$loppusumma}',
            arvo_valuutassa   = '{$loppusumma}',
            summa        = '{$verollinen_summa}',
            summa_valuutassa  = '{$verollinen_summa}',
            kate         = '{$katesumma}',
            alv         = '{$alviprosentti}'
            where tunnus = '{$lxtunnus}'";
    pupe_query($query);
    $loppusumma = 0;
    $verollinen_summa = 0;
    $katesumma = 0;
  }
}

function konvertoi_maksuehto($maksuehto, $yhtio) {
  $maksuehto = (string)$maksuehto;

  if ($yhtio == 'atarv') {
    //selitteellä ei tehdä mitään, debuggausta varten
    $maksuehto_array = array(
      '1'     => array('id'   => '1551', 'selite' => 'Käteinen'),
      '2'     => array('id'   => '1551', 'selite' => 'Käteinen'),
      '3'     => array('id'   => '1551', 'selite' => 'Käteinen'),
      '4'      => array('id'   => '1535', 'selite' => 'Lasku 7pv'),
      '6'     => array('id'   => '1551', 'selite' => 'Käteinen'),
      '100'   => array('id'   => '1520', 'selite' => 'Lasku 14 pv'),
      '101'   => array('id'   => '1515', 'selite' => 'Lasku 21 pv'),
      '102'   => array('id'   => '1513', 'selite' => 'Lasku 30 pv'),
      '103'   => array('id'   => '2019', 'selite' => 'Lasku 14 pv netto, 7 pv - 2 %'),
      '104'   => array('id'   => '1551', 'selite' => 'Käteinen'),
      '106'   => array('id'   => '1551', 'selite' => 'Käteinen'),
      '107'   => array('id'   => '1514', 'selite' => '14 pv -2% 30 pv netto'),
      '108'   => array('id'   => '1551', 'selite' => 'Käteinen'),
      '110'   => array('id'   => '1551', 'selite' => 'Käteinen'),
      '111'   => array('id'   => '1525', 'selite' => '30 pv -2% 60 pv netto'),
      '112'   => array('id'   => '1500', 'selite' => '45 pv netto'),
      '113'   => array('id'   => '1496', 'selite' => '60 pv netto'),
      '115'   => array('id'   => '1538', 'selite' => '90 pv netto'),
      '116'   => array('id'   => '1494', 'selite' => '14 pv -3% 60 pv netto'),
    );

    if (array_key_exists($maksuehto, $maksuehto_array)) {
      return (int)$maksuehto_array[$maksuehto]['id'];
    }
    else {
      echo "\n Maksuehdolle ".$maksuehto." ei löytynyt paria. Käytetään maksuehtoa 1517 \n";
      return '1517';
    }
  }
  else {
    return (int)$maksuehto;
  }
}


function konvertoi_toimipaikka($toimipaikka, $yhtio) {
  $toimipaikka = (string)$toimipaikka;

  if ($yhtio == 'atarv') {
    //selitteellä ei tehdä mitään, debuggausta varten
    $toimipaikka_array = array(
      '8'   => array('id'   => '20', 'nimi' => 'AUTOASI FORSSA'),
      '33'   => array('id'   => '14', 'nimi' => 'AUTOASI VAASA'),
      '24'   => array('id'   => '15', 'nimi' => 'AUTOASI TAMPERE'),
      '28'   => array('id'   => '16', 'nimi' => 'AUTOASI LAPPEENRANTA'),
      '29'   => array('id'   => '17', 'nimi' => 'AUTOASI KOTKA'),
      '34'   => array('id'   => '18', 'nimi' => 'AUTOASI KOUVOLA'),
      '4'   => array('id'   => '19', 'nimi' => 'AUTOASI HÄMEENLINNA'),
      '19'   => array('id'   => '21', 'nimi' => 'AUTOASI MUURALA'),
      '9'   => array('id'   => '22', 'nimi' => 'AUTOASI LAUNE'),
      '22'   => array('id'   => '27', 'nimi' => 'AUTOASI NUMMELA'),   //PKS
      '11'   => array('id'   => '24', 'nimi' => 'AUTOASI HYVINKÄÄ'),
      '13'   => array('id'   => '25', 'nimi' => 'AUTOASI MÄNTSÄLÄ'),
      '35'   => array('id'   => '26', 'nimi' => 'AUTOASI MIKKELI'),
      '5'   => array('id'   => '27', 'nimi' => 'AUTOASI PKS TUKKUMYYNTI'),
      '17'   => array('id'   => '28', 'nimi' => 'AUTOASI ROVANIEMI'),
      '00'   => array('id'   => '29', 'nimi' => 'Hallinto'),
      '26'   => array('id'   => '30', 'nimi' => 'AUTOASI TURKU'),
      '21'   => array('id'   => '31', 'nimi' => 'AUTOASI IISALMI'),
      '1'   => array('id'   => '32', 'nimi' => 'AUTOASI LAHTI'),
      '6'   => array('id'   => '33', 'nimi' => 'AUTOASI OLARI'),
      '42'   => array('id'   => '34', 'nimi' => 'AUTOASI PORVOO'),
      '16'   => array('id'   => '35', 'nimi' => 'AUTOASI OULU'),
      '36'   => array('id'   => '36', 'nimi' => 'AUTOASI JYVÄSKYLÄ Vasarakatu'),
      '37'   => array('id'   => '36', 'nimi' => 'AUTOASI JYVÄSKYLÄ Sepänkatu'),     // Vasarakatu
      '38'   => array('id'   => '38', 'nimi' => 'AUTOASI KUOPIO'),
      '26'   => array('id'   => '30', 'nimi' => 'AUTOASI TURKU/SALO'),        // Turku
      '39'   => array('id'   => '40', 'nimi' => 'AUTOASI SEINÄJOKI'),
      '46'   => array('id'   => '41', 'nimi' => 'AUTOASI PORI'),
    );

    if (array_key_exists($toimipaikka, $toimipaikka_array)) {
      return (int)$toimipaikka_array[$toimipaikka]['id'];
    }
    else {
      echo "\n Toimipaikkaa ".$toimipaikka." ei löytynyt.\n";
      return '29';
    }
  }
  else {
    return (int)$toimipaikka;
  }
}


function konvertoi_kustannuspaikka($kustannuspaikka, $yhtio) {
  $kustannuspaikka = (string)$kustannuspaikka;

  if ($yhtio == 'atarv') {
    //selitteellä ei tehdä mitään, debuggausta varten
    $kustannuspaikka_array = array(
      '8'   => array('id'   => '495', 'nimi' => 'AUTOASI FORSSA, 2080'),
      '33'   => array('id'   => '739', 'nimi' => 'AUTOASI VAASA, 1000'),
      '24'   => array('id'   => '504', 'nimi' => 'AUTOASI TAMPERE, 2240'),
      '28'   => array('id'   => '600', 'nimi' => 'AUTOASI LAPPEENRANTA, 4028'),
      '29'   => array('id'   => '601', 'nimi' => 'AUTOASI KOTKA, 4029'),
      '34'   => array('id'   => '602', 'nimi' => 'AUTOASI KOUVOLA, 4040'),
      '4'   => array('id'   => '492', 'nimi' => 'AUTOASI HÄMEENLINNA, 2040'),
      '19'   => array('id'   => '501', 'nimi' => 'AUTOASI MUURALA, 2190'),
      '9'   => array('id'   => '496', 'nimi' => 'AUTOASI LAUNE, 2090'),
      '22'   => array('id'   => '503', 'nimi' => 'AUTOASI NUMMELA, 2220'),
      '11'   => array('id'   => '497', 'nimi' => 'AUTOASI HYVINKÄÄ, 2110'),
      '13'   => array('id'   => '498', 'nimi' => 'AUTOASI MÄNTSÄLÄ, 2130'),
      '35'   => array('id'   => '575', 'nimi' => 'AUTOASI MIKKELI, 2270'),
      '5'   => array('id'   => '493', 'nimi' => 'AUTOASI PKS TUKKUMYYNTI, 2050'),
      '17'   => array('id'   => '500', 'nimi' => 'AUTOASI ROVANIEMI, 2170'),
      '00'   => array('id'   => '489', 'nimi' => 'Hallinto, 2000, Lahti'),
      '26'   => array('id'   => '505', 'nimi' => 'AUTOASI TURKU, 2260'),
      '21'   => array('id'   => '502', 'nimi' => 'AUTOASI IISALMI, 2210'),
      '1'   => array('id'   => '489', 'nimi' => 'AUTOASI LAHTI, 2010'),
      '6'   => array('id'   => '494', 'nimi' => 'AUTOASI OLARI, 2060'),
      '42'   => array('id'   => '447', 'nimi' => 'AUTOASI PORVOO, 2310'),
      '16'   => array('id'   => '499', 'nimi' => 'AUTOASI OULU, 2160'),
      '36'   => array('id'   => '590', 'nimi' => 'AUTOASI JYVÄSKYLÄ Vasarakatu, 2280'),
      '37'   => array('id'   => '590', 'nimi' => 'AUTOASI JYVÄSKYLÄ Sepänkatu, 2290'),
      '38'   => array('id'   => '614', 'nimi' => 'AUTOASI KUOPIO, 2380'),
      '39'   => array('id'   => '681', 'nimi' => 'AUTOASI SEINÄJOKI, 2410'),
      '46'   => array('id'   => '703', 'nimi' => 'AUTOASI PORI, 2420'),
    );

    if (array_key_exists($kustannuspaikka, $kustannuspaikka_array)) {
      return (int)$kustannuspaikka_array[$kustannuspaikka]['id'];
    }
    else {
      echo "\n kustannuspaikkaa ".$kustannuspaikka." ei löytynyt.\n";
      return '489';  // Lahti
    }
  }
  else {
    return (int)$kustannuspaikka;
  }
}


function hae_hyllyalue($hyllyalue, $yhtio) {
  $hyllyalue = (string)$hyllyalue;

  if ($yhtio == 'atarv') {
    //selitteellä ei tehdä mitään, debuggausta varten
    $hyllyalue_array = array(
      '8'      => array('hyllyalue' => '74', 'nimi' => 'AUTOASI FORSSA'),
      '33'   => array('hyllyalue' => '92', 'nimi' => 'AUTOASI VAASA'),
      '24'   => array('hyllyalue' => '83', 'nimi' => 'AUTOASI TAMPERE'),
      '28'   => array('hyllyalue' => '88', 'nimi' => 'AUTOASI LAPPEENRANTA'),
      '29'   => array('hyllyalue' => '89', 'nimi' => 'AUTOASI KOTKA'),
      '34'   => array('hyllyalue' => '90', 'nimi' => 'AUTOASI KOUVOLA'),
      '4'      => array('hyllyalue' => '71', 'nimi' => 'AUTOASI HÄMEENLINNA'),
      '19'   => array('hyllyalue' => '80', 'nimi' => 'AUTOASI MUURALA'),
      '9'      => array('hyllyalue' => '75', 'nimi' => 'AUTOASI LAUNE'),
      '22'   => array('hyllyalue' => '82', 'nimi' => 'AUTOASI NUMMELA'),
      '11'   => array('hyllyalue' => '76', 'nimi' => 'AUTOASI HYVINKÄÄ'),
      '13'   => array('hyllyalue' => '77', 'nimi' => 'AUTOASI MÄNTSÄLÄ'),
      '35'   => array('hyllyalue' => '85', 'nimi' => 'AUTOASI MIKKELI'),
      '5'       => array('hyllyalue' => '72', 'nimi' => 'AUTOASI PKS TUKKUMYYNTI'),
      '17'   => array('hyllyalue' => '79', 'nimi' => 'AUTOASI ROVANIEMI'),
      '00'   => array('hyllyalue' => '00', 'nimi' => 'Hallinto'),
      '26'   => array('hyllyalue' => '84', 'nimi' => 'AUTOASI TURKU'),
      '21'   => array('hyllyalue' => '81', 'nimi' => 'AUTOASI IISALMI'),
      '1'      => array('hyllyalue' => '70', 'nimi' => 'AUTOASI LAHTI'),
      '6'      => array('hyllyalue' => '73', 'nimi' => 'AUTOASI OLARI'),
      '42'   => array('hyllyalue' => '87', 'nimi' => 'AUTOASI PORVOO'),
      '16'   => array('hyllyalue' => '78', 'nimi' => 'AUTOASI OULU'),
      '36'   => array('hyllyalue' => '86', 'nimi' => 'AUTOASI JYVÄSKYLÄ Vasarakatu'),
      '37'   => array('hyllyalue' => '86', 'nimi' => 'AUTOASI JYVÄSKYLÄ Sepänkatu'),
      '38'   => array('hyllyalue' => '91', 'nimi' => 'AUTOASI KUOPIO'),
      '39'   => array('hyllyalue' => '93', 'nimi' => 'AUTOASI SEINÄJOKI'),
      '46'   => array('hyllyalue' => '94', 'nimi' => 'AUTOASI PORI'),
    );

    if (array_key_exists($hyllyalue, $hyllyalue_array)) {
      return (int)$hyllyalue_array[$hyllyalue]['hyllyalue'];
    }
    else {
      echo "\n Hyllyaluetta ".$hyllyalue." ei löytynyt.\n";
      return (int)$hyllyalue;
    }
  }
  else {
    return (int)$hyllyalue;
  }
}
