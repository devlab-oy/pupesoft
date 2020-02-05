<?php
/*
Tarvitaan indexit:
  CREATE INDEX tilausaika ON futur_ostotil (OT_TILAUSIAKA);
  CREATE INDEX tilausnumero ON futur_ostotilriv (OT_NRO);
*/

if (php_sapi_name() == 'cli') {
  // otetaan includepath aina rootista
  $pupe_root_polku = dirname(dirname(__FILE__));
  // $pupe_root_polku = "/Users/satu/Sites/pupesoft";
  // $pupe_root_polku = "/Users/tony/Sites/pupesoft";
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
  ini_set("display_errors", 0);

  require "inc/connect.inc";
  require "inc/functions.inc";
  require "tilauskasittely/luo_myyntitilausotsikko.inc";

  // Logitetaan ajo
  cron_log();

  $yhtio = trim($argv[1]);
  $tp = trim($argv[2]);
  $alkupvm = trim($argv[3]);
  $loppupvm = trim($argv[4]);


  //yhtiötä ei ole annettu
  if (empty($yhtio)) {
    echo "\nUsage: php ".basename($argv[0])." yhtio toimipaikka alkupvm loppupvm\n\n";
    die;
  }

  $yhtiorow = hae_yhtion_parametrit($yhtio);

  if ($tp == 0 or $tp == '') {
    echo "\nUsage: php ".basename($argv[0])." yhtio toimipaikka alkupvm loppupvm\n\n";
    die;
  }
  else {
    $toimipaikka = konvertoi_toimipaikka($tp, $yhtio);
  }

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

  // Haetaan toimipaikan tiedot
  $query = "SELECT *
            FROM yhtion_toimipaikat
            WHERE yhtio = '$yhtio'
            AND tunnus  = '$toimipaikka'";
  $retp = pupe_query($query);
  $toimipaikkarow = mysql_fetch_assoc($retp);

  // Haetaan toimipaikan varasto
  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio     = '$yhtio'
            AND toimipaikka = '$toimipaikka'";
  $revar = pupe_query($query);
  $varastorow = mysql_fetch_assoc($revar);
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

$laatija     = "palvelin".$tp;
$hyllynro    = '0';
$hyllytaso    = '0';
$hyllyvali    = '0';
$hyllyalue    = hae_hyllyalue($tp, $yhtio);
// ini_set("memory_limit", "6G");

$query = "SELECT *
          FROM futur_ostotil
          WHERE OT_TILAUSAIKA >= '$alkupvm' AND OT_TILAUSAIKA <= '$loppupvm' AND OT_NRO > 0 # = 5301466
          #WHERE OT_NRO in (5000000,5000001)
          # WHERE OT_NRO > 4600000  # Näin Porille (46)
          ";

$resosto = mysql_query($query);
$count = mysql_num_rows($resosto);
$ii = 0;
while ($ostotil = mysql_fetch_assoc($resosto)) {

  $ii++;
  // echo "\nOstotilausrivi $ii / $count (ostotilaus $ostotil[OT_NRO])";

  $otnro     = $ostotil['OT_NRO'] * -1;
  $luontiaika    = $ostotil['OT_TILAUSAIKA'];
  $vanhatunnus   = 0;
  $astunnus     = 0;
  //  $hyllyalue    = '';
  $query = "SELECT *
            FROM toimi
            WHERE yhtio       = '{$yhtio}'
            AND toimittajanro = '{$ostotil['OT_TOIMITTAJANRO']}'
            /* AND laji != 'P'  */
            LIMIT 1";
  $toimresult = pupe_query($query);
  $toimrow = mysql_fetch_assoc($toimresult);

  // jos ei löydy yhtä niin skipataan koko homma
  if (mysql_num_rows($toimresult) != 1) {
    echo "\nToimittaja {$ostotil['OT_TOIMITTAJANRO']} puuttuu (tai löytyy useampi), ei tehdä mitään.";
    continue;
  }

  $alviprosentti = 0;
  //  $hyllyalue = hae_hyllyalue($myyntilasku['OT_TOIMIPISTENRO'], $yhtio);
  //  if ($hyllyalue == '') $hyllyalue = 'A00';
  $tila     = 'K';
  $alatila   = 'X';
  $arvo     = 0;
  $saappvm = $ostotil['OT_TILAUSAIKA'];    // käytetään otsikon tilausaikaa ellei tuloutettu kuten esim. PKS
  $ostotil['OT_VIITTEENNE'] = pupesoft_cleanstring($ostotil['OT_VIITTEENNE']);
  $toimrow['vienti'] = pupesoft_cleanstring($toimrow['vienti']);
  $comments = pupesoft_cleanstring($ostotil['OT_VIITTEEMME']);

  $query = "SELECT laskunro, tunnus
            FROM lasku
            WHERE yhtio  = '$yhtio'
            AND tila     = '$tila'
            AND alatila  = '$alatila'
            AND laskunro = '$otnro'
            AND laatija  = '$laatija'";
  $kxresult = pupe_query($query);

  if (mysql_num_rows($kxresult) == 0) {

    $query = "INSERT INTO lasku SET
              aktiivinen_kuljetus              = '',
              aktiivinen_kuljetus_kansallisuus = '',
              alatila                          = '$alatila',
              alv                              = '$alviprosentti',
              alv_tili                         = '',
              arvo                             = '$arvo',
              arvo_valuutassa                  = '$arvo',
              asiakkaan_tilausnumero           = '',
              bruttopaino                      = '',
              chn                              = '',
              clearing                         = '',
              comments                         = '$comments',
              ebid                             = '',
              eilahetetta                      = '',
              erikoisale                       = '',
              erpcm                            = '',
              factoringsiirtonumero            = '',
              h1time                           = '',
              h2time                           = '',
              h3time                           = '',
              h4time                           = '',
              h5time                           = '',
              hinta                            = '',
              huolitsija                       = '',
              hyvak1                           = '',
              hyvak2                           = '',
              hyvak3                           = '',
              hyvak4                           = '',
              hyvak5                           = '',
              hyvaksyja_nyt                    = '',
              hyvaksynnanmuutos                = '',
              jakelu                           = '',
              jaksotettu                       = '',
              jtkielto                         = '',
              kapvm                            = '',
              kassalipas                       = '',
              kasumma                          = '',
              kasumma_valuutassa               = '',
              kate                             = '',
              kauppatapahtuman_luonne          = '',
              kerayslista                      = '',
              kerayspvm                        = '',
              keraysvko                        = '',
              ketjutus                         = '',
              kirjoitin                        = '',
              kohde                            = '',
              kohdistettu                      = 'X',
              kontti                           = '',
              kuljetus                         = '',
              kuljetusmuoto                    = '',
              laatija                          = '$laatija',
              lahetepvm                        = '',
              lapvm                            = '',
              laskunro                         = '$otnro',
              laskutettu                       = '$saappvm',
              laskuttaja                       = '$laskuttaja',
              laskutusvkopv                    = '',
              liitostunnus                     = '{$toimrow['tunnus']}',
              lisattava_era                    = '',
              luontiaika                       = '{$luontiaika}',
              maa                              = '{$toimrow['maa']}',
              maa_alkupera                     = '',
              maa_lahetys                      = '{$toimrow['maa']}',
              maa_maara                        = '',
              maksaja                          = '',
              maksuaika                        = '',
              maksuehto                        = '',
              maksuteksti                      = '',
              maksutyyppi                      = '',
              maksu_kurssi                     = '',
              maksu_tili                       = '',
              mapvm                            = '',
              muutospvm                        = '',
              muuttaja                         = '',
              myyja                            = '',
              nimi                             = '{$toimrow['nimi']}',
              nimitark                         = '{$toimrow['nimitark']}',
              noutaja                          = '',
              olmapvm                          = '',
              osatoimitus                      = '',
              osoite                           = '{$toimrow['osoite']}',
              osoitetark                       = '',
              ovttunnus                        = '{$toimrow['ovttunnus']}',
              pakkaamo                         = '',
              pankki1                          = '',
              pankki2                          = '',
              pankki3                          = '',
              pankki4                          = '',
              pankki_haltija                   = '',
              piiri                            = '',
              poistumistoimipaikka             = '',
              poistumistoimipaikka_koodi       = '',
              popvm                            = '',
              postino                          = '{$toimrow['postino']}',
              postitp                          = '{$toimrow['postitp']}',
              pyoristys                        = '',
              pyoristys_valuutassa             = '',
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
              summa                            = '{$ostotil['OT_SUMMA']}',
              summa_valuutassa                 = '{$ostotil['OT_SUMMA']}',
              suoraveloitus                    = '',
              swift                            = '',
              tapvm                            = '$saappvm',         # '{$ostotil['OT_SAAPUMISAIKA']}',
              tila                             = '$tila',
              tilaustyyppi                     = '',
              tilausvahvistus                  = '',
              tilausyhteyshenkilo              = '',
              tilinumero                       = '',
              toimaika                         = '$saappvm',         # '{$ostotil['OT_SAAPUMISAIKA']}',
              toimitusehto                     = '',
              toimitustapa                     = '',
              toimvko                          = '',
              toim_maa                         = '',
              toim_nimi                        = '{$toimipaikkarow['nimi']}',
              toim_nimitark                    = '',
              toim_osoite                      = '{$toimipaikkarow['osoite']}',
              toim_ovttunnus                   = '',
              toim_postino                     = '{$toimipaikkarow['postino']}',
              toim_postitp                     = '{$toimipaikkarow['postitp']}',
              tullausnumero                    = '',
              tulostusalue                     = '',
              tunnus                           = '',
              tunnusnippu                      = '',
              ultilno                          = '',
              vahennettava_era                 = '',
              vakuutus                         = '',
              valkoodi                         = 'EUR',
              vanhatunnus                      = '',
              varasto                          = '{$varastorow['tunnus']}',
              verkkotunnus                     = '{$toimrow['verkkotunnus']}',
              vienti                           = '{$toimrow['vienti']}',
              vientipaperit_palautettu         = '',
              vienti_kurssi                    = '1',
              viesti                           = '{$ostotil['OT_VIITTEENNE']}',
              viikorkoeur                      = '',
              viikorkopros                     = '',
              viite                            = '',
              viitetxt                         = '',
              yhtio                            = '$yhtio',
              yhtio_kotipaikka                 = '',
              yhtio_maa                        = '',
              yhtio_nimi                       = '',
              yhtio_osoite                     = '',
              yhtio_ovttunnus                  = '',
              yhtio_postino                    = '',
              yhtio_postitp                    = '',
              yhtio_toimipaikka                = '$toimipaikka',
              ytunnus                          = '{$toimrow['ytunnus']}'";
    pupe_query($query);
    $kxtunnus = mysql_insert_id();
  }
  else {
    $kxlasku_row = mysql_fetch_assoc($kxresult);
    $kxtunnus = $kxlasku_row['tunnus'];
  }

  $loppusumma = 0;
  $vanhin_tapvm = "0000-00-00";

  $query = "SELECT *
            FROM futur_ostotilriv
            WHERE OTR_NRO = '{$ostotil['OT_NRO']}'";
  $resrivi = mysql_query($query);

  while ($ostorivi = mysql_fetch_assoc($resrivi)) {

    if ($ostorivi['OTR_TUOTEKOODI'] != '') {
      if ($ostorivi['OTR_VEROKANTA'] > 0) {
        $alvkerroin = (100 + $ostorivi['OTR_VEROKANTA']) / 100;
      }
      else {
        $alvkerroin = 1;
      }
      $tuoteno = pupesoft_cleanstring($ostorivi['OTR_TUOTEKOODI']);
      $tuoteno = futurmuunnos_tuote($ostorivi['OTR_TOIMITTAJANRO'], $tuoteno);

      $query = "SELECT *
                FROM tuote
                WHERE yhtio    = '$yhtio'
                   AND tuoteno = '$tuoteno'";
      $tuoteresult = pupe_query($query);

      if (mysql_num_rows($tuoteresult) == '1') {
        $tuoterow = mysql_fetch_assoc($tuoteresult);
        $tuoteosasto  = $tuoterow['osasto'];
        $tuotetry    = $tuoterow['try'];
      }
      else {
        $qu = "INSERT INTO tuote SET
               yhtio       = '$yhtio',
               tuoteno     = '$tuoteno',
               nimitys     = '$nimitys',
               lyhytkuvaus = 'Ostohistoriakonversion perustama',
               osasto      = '',
               try         = '',
               kuvaus      = '',
               yksikko     = 'KPL',
               status      = 'P',
               alv         = 24,
               muuta       = '{$ostorivi['OTR_TOIMITTAJANRO']}',
               luontiaika  = now(),
               laatija     = '$laatija'";

        pupe_query($qu);

        $tuoteosasto  = '';
        $tuotetry    = '';
        echo "\n Tuote: $tuoteno puuttuu!";
      }

      $query = "SELECT *
                FROM tilausrivi
                WHERE yhtio         = '$yhtio'
                AND tyyppi          = 'O'
                AND tuoteno         = '$tuoteno'
                AND laadittu        = '$luontiaika'
                AND tilaajanrivinro = '{$ostorivi['OTR_NRO']}'
                AND jaksotettu      = '{$ostorivi['OTR_RIVINRO']}'
                AND otunnus         = '{$kxtunnus}'
                AND uusiotunnus     = '{$kxtunnus}'
                AND laatija         = '$laatija'
                AND laskutettu      = '$laatija'";
      $tilriviresult = mysql_query($query) or pupe_error($query);

      if (mysql_num_rows($tilriviresult) == 0) {

        $hyllynro  = '00';
        $hyllyvali = '0';
        $hyllytaso = '0';
        $alviprosentti = $ostorivi['OTR_VEROKANTA'];
        $summa = $ostorivi['OTR_RIVISUMMA'] / $alvkerroin;
        $loppusumma = $loppusumma + $summa;
        if (isset($ostorivi['OTR_TOIMITETTUMAARA']) ) {
          $toim_maara = $ostorivi['OTR_TOIMITETTUMAARA'] + $ostorivi['OTR_PERUTETTUMAARA'];
        }
        else {
          $toim_maara = $ostorivi['OTR_TILATTUMAARA'];
        }
        $ahinta = $summa / $toim_maara;
        $nimitys = pupesoft_cleanstring($ostorivi['OTR_NIMI']);
        $kommentti = pupesoft_cleanstring($ostorivi['OTR_VIITE']);
        // lopussa tallennetaan laskun tapvm riveiltä löytyvän tulohetken mukaan, viimeisin tuloleima otsikolle
        if ($vanhin_tapvm < $ostorivi['OTR_SAAPUNUT']) $vanhin_tapvm = $ostorivi['OTR_SAAPUNUT'];

        $query5 = "INSERT INTO tilausrivi SET
                   ale1                 = '{$ostorivi['OTR_ALEPROS']}',
                   ale2                 = '',
                   ale3                 = '',
                   alv                  = '$alviprosentti',
                   erikoisale           = '',
                   hinta                = '$ahinta',
                   hinta_valuutassa     = '',
                   hyllyalue            = '$hyllyalue',
                   hyllynro             = '$hyllynro',
                   hyllytaso            = '$hyllytaso',
                   hyllyvali            = '$hyllyvali',
                   jaksotettu           = '{$ostorivi['OTR_RIVINRO']}',
                   jt                   = '',
                   kate                 = '',
                   keratty              = '$laatija',
                   kerattyaika          = '{$ostorivi['OTR_SAAPUNUT']}',
                   kerayspvm            = '{$ostorivi['OTR_SAAPUNUT']}',
                   kommentti            = '$kommentti',
                   kpl                  = '$toim_maara',
                   kpl2                 = '',
                   laadittu             = '$luontiaika',
                   laatija              = '$laatija',
                   laskutettu           = '$laatija',
                   laskutettuaika       = '{$ostorivi['OTR_SAAPUNUT']}',
                   netto                = '',
                   nimitys              = '$nimitys',
                   osasto               = '$tuoteosasto',
                   otunnus              = '$kxtunnus',
                   perheid              = '',
                   perheid2             = '',
                   rivihinta            = '$summa',
                   rivihinta_valuutassa = '$summa',
                   tilaajanrivinro      = '{$ostorivi['OTR_NRO']}',  # futurin tilausnro
                   tilkpl               = '{$ostorivi['OTR_TILATTUMAARA']}',
                   toimaika             = '{$ostorivi['OTR_SAAPUNUT']}',
                   toimitettu           = '$laatija',
                   toimitettuaika       = '{$ostorivi['OTR_SAAPUNUT']}',
                   try                  = '$tuotetry',
                   tuoteno              = '$tuoteno',
                   tyyppi               = 'O',
                   uusiotunnus          = '$kxtunnus',
                   var                  = '',
                   var2                 = '',
                   varattu              = '',
                   yhtio                = '$yhtio',
                   yksikko              = 'KPL'";
        pupe_query($query5);
        $rivitunnus = mysql_insert_id();

        $rivitunnus = $rivitunnus * -1;       // ettei jälkilaskenta koske myyntihistoriariveihin, kun toimipisteet tulevat pupeen eri aikaan

        $query2 = "INSERT INTO tapahtuma SET
                   hinta      = '$ahinta',
                   hyllyalue  = '$hyllyalue',
                   hyllynro   = '$hyllynro',
                   hyllytaso  = '$hyllytaso',
                   hyllyvali  = '$hyllyvali',
                   kpl        = '$toim_maara',
                   kplhinta   = '$ahinta',
                   laadittu   = '{$ostorivi['OTR_SAAPUNUT']}',
                   laatija    = '$laatija',
                   laji       = 'tulo',
                   rivitunnus = '$rivitunnus',
                   selite     = '$toimrow[nimi] $toimrow[nimitark] ($ahinta) [$summa]',
                   tuoteno    = '$tuoteno',
                   yhtio      = '$yhtio'";
        pupe_query($query2);
      }
      else {
        echo "\nTilausrivi löytyi jo! ostotilaus $otnro ja rivinumero: $ostorivi[OTR_RIVINRO] ja tuoteno: $tuoteno";
      }
    }
    else {
      echo "\nTuotenumero on tyhjä ostotilaukselta $otnro! (hinta: $ostorivi[OTR_OSTOHINTA1] kpl: $ostorivi[OTR_TOIMITETTUMAARA] rivinumero: $ostorivi[OTR_RIVINRO])";
    }
  }

  if ($vanhin_tapvm != "0000-00-00") {
    $upd_query = "UPDATE lasku SET
                  tapvm            = '$vanhin_tapvm',
                  summa            = '$loppusumma' * '$alvkerroin',
                  summa_valuutassa = '$loppusumma' * '$alvkerroin',
                  arvo             = '$loppusumma',            # futurin otsikon summa ei mätsää rivisummiin
                  arvo_valuutassa ='$loppusumma'
                  WHERE yhtio      = '$yhtio'
                  AND tunnus       = '$kxtunnus'";
    pupe_query($upd_query);
  }
  /*
  if (round($loppusumma, 2) != round($ostotil['OT_SUMMA'], 2)) echo "\nOstotilauksella $otnro tuotteiden summa ja otsikon summa heittää! (otsikko: $ostotil[OT_SUMMA] ja tuotteet: $loppusumma)\n";

  } */
}

function hae_hyllyalue($hyllyalue, $yhtio) {
  $hyllyalue = (string)$hyllyalue;

  if ($yhtio == 'atarv') {
    //selitteellä ei tehdä mitään, debuggausta varten
    $hyllyalue_array = array(
      '8'   => array('hyllyalue' => '74', 'nimi' => 'AUTOASI FORSSA'),
      '33'   => array('hyllyalue' => '92', 'nimi' => 'AUTOASI VAASA'),
      '24'   => array('hyllyalue' => '83', 'nimi' => 'AUTOASI TAMPERE'),
      '28'   => array('hyllyalue' => '88', 'nimi' => 'AUTOASI LAPPEENRANTA'),
      '29'   => array('hyllyalue' => '89', 'nimi' => 'AUTOASI KOTKA'),
      '34'   => array('hyllyalue' => '90', 'nimi' => 'AUTOASI KOUVOLA'),
      '4'   => array('hyllyalue' => '71', 'nimi' => 'AUTOASI HÄMEENLINNA'),
      '19'   => array('hyllyalue' => '80', 'nimi' => 'AUTOASI MUURALA'),
      '9'   => array('hyllyalue' => '75', 'nimi' => 'AUTOASI LAUNE'),
      '22'   => array('hyllyalue' => '82', 'nimi' => 'AUTOASI NUMMELA'),
      '11'   => array('hyllyalue' => '76', 'nimi' => 'AUTOASI HYVINKÄÄ'),
      '13'   => array('hyllyalue' => '77', 'nimi' => 'AUTOASI MÄNTSÄLÄ'),
      '35'   => array('hyllyalue' => '85', 'nimi' => 'AUTOASI MIKKELI'),
      '5'   => array('hyllyalue' => '72', 'nimi' => 'AUTOASI PKS TUKKUMYYNTI'),
      '17'   => array('hyllyalue' => '79', 'nimi' => 'AUTOASI ROVANIEMI'),
      '00'   => array('hyllyalue' => '00', 'nimi' => 'Hallinto'),
      '26'   => array('hyllyalue' => '84', 'nimi' => 'AUTOASI TURKU'),
      '21'   => array('hyllyalue' => '81', 'nimi' => 'AUTOASI IISALMI'),
      '1'   => array('hyllyalue' => '70', 'nimi' => 'AUTOASI LAHTI'),
      '6'   => array('hyllyalue' => '73', 'nimi' => 'AUTOASI OLARI'),
      '42'   => array('hyllyalue' => '87', 'nimi' => 'AUTOASI PORVOO'),
      '16'   => array('hyllyalue' => '78', 'nimi' => 'AUTOASI OULU'),
      '36'   => array('hyllyalue' => '86', 'nimi' => 'AUTOASI JYVÄSKYLÄ Vasarakatu'),
      '37'   => array('hyllyalue' => '86', 'nimi' => 'AUTOASI JYVÄSKYLÄ Sepänkatu'),
      '38'   => array('hyllyalue' => '91', 'nimi' => 'AUTOASI KUOPIO'),
      '26'   => array('hyllyalue' => '84', 'nimi' => 'AUTOASI TURKU/SALO'),
      '39'   => array('hyllyalue' => '93', 'nimi' => 'AUTOASI SEINÄJOKI'),
      '46'   => array('hyllyalue' => '94', 'nimi' => 'AUTOASI PORI'),
    );

    if (array_key_exists($hyllyalue, $hyllyalue_array)) {
      return $hyllyalue_array[$hyllyalue]['hyllyalue'];
    }
    else {
      echo "\n Hyllyaluetta ".$hyllyalue." ei löytynyt.\n";
      return "";
    }
  }
  else {
    return "";
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
      '22'   => array('id'   => '23', 'nimi' => 'AUTOASI NUMMELA'),
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
      '37'   => array('id'   => '37', 'nimi' => 'AUTOASI JYVÄSKYLÄ Sepänkatu'),
      '38'   => array('id'   => '38', 'nimi' => 'AUTOASI KUOPIO'),
      '26'   => array('id'   => '39', 'nimi' => 'AUTOASI TURKU/SALO'),
      '39'   => array('id'   => '40', 'nimi' => 'AUTOASI SEINÄJOKI'),
      '46'   => array('id'   => '41', 'nimi' => 'AUTOASI PORI'),
    );

    if (array_key_exists($toimipaikka, $toimipaikka_array)) {
      return (int)$toimipaikka_array[$toimipaikka]['id'];
    }
    else {
      echo "\n Toimipaikkaa ".$toimipaikka." ei löytynyt.\n";
      return (int)$toimipaikka;
    }
  }
  else {
    return (int)$toimipaikka;
  }
}
