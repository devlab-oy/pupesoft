<?php

/*
  Korjataan käteismyyntejä, jotka menneet pieleen, kun axapta-siirtojen ja konversion tiedoissa eroja

  Tällä on viimeksi ajettu pelkkä Vaasa
*/

if (php_sapi_name() == 'cli') {
  // otetaan includepath aina rootista
  $pupe_root_polku = dirname(dirname(__FILE__));
  //$pupe_root_polku = "/Users/satu/Sites/pupesoft";

  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL);
//  ini_set("display_errors", 1);

  ini_set("memory_limit", "5G");

  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $yhtio     = trim($argv[1]);
  $ekalasku  = trim($argv[2]);
  $vikalasku = trim($argv[3]);
  $alkupvm   = trim($argv[4]);
  $loppupvm  = trim($argv[5]);

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

$laatija      = "konversio";
$laatija2     = "('futursoft', 'konversio')";
$laatija3     = "futursoft";
$hyllynro     = '';
$hyllytaso    = '';
$hyllyvali    = '';
$ketjutus     = '';
$toimitustapa = 'Oletus';
$toimitusehto = 'EXW';
$laskuttaja   = $laatija;
/*
CREATE INDEX viitetxt_index ON lasku (yhtio, tila, alatila, viitetxt);
CREATE INDEX tilaajanrivinro_index ON tilausrivi (yhtio, tyyppi, tilaajanrivinro, suuntalava);
*/

// 1. Päivitetään eka ux:lle sama viitetxt kuin lx:llä, pitää ajaa uusiksi limit 1:llä
$query = "SELECT *
          FROM lasku
          WHERE yhtio  = '{$yhtio}'
          AND tila     = 'U'
          AND alatila  = 'X'
          AND laskunro >= '{$ekalasku}'      #'-10000130831'
          AND laskunro <= '{$vikalasku}'     #'-10000130801'
          order by kassalipas, tapvm, maksuehto, tunnus";
$reslasku = pupe_query($query);

while ($myyntilasku = mysql_fetch_assoc($reslasku)) {

  $query = "SELECT *
            FROM lasku
            WHERE yhtio           = '{$yhtio}'
            AND tila              = 'L'
            AND alatila           = 'X'
            AND laskunro          = '{$myyntilasku['laskunro']}'
            AND kassalipas        = '{$myyntilasku['kassalipas']}'
            AND maksuehto         = '{$myyntilasku['maksuehto']}'
            AND yhtio_toimipaikka = '{$myyntilasku['yhtio_toimipaikka']}'
            LIMIT 1";
  $restilaus = pupe_query($query);
  $myyntitilaus = mysql_fetch_assoc($restilaus);

  if ($myyntitilaus['viitetxt'] != $myyntilasku['viitetxt'] or $myyntitilaus['yhtio_toimipaikka'] != $myyntilasku['yhtio_toimipaikka']) {
    $upd = "UPDATE lasku
            SET viitetxt      = '{$myyntitilaus['viitetxt']}',
            yhtio_toimipaikka = '{$myyntitilaus['yhtio_toimipaikka']}',
            muutospvm         = now(),
            muuttaja          = 'korjaus'
            WHERE yhtio       = '{$yhtio}'
            AND tunnus        = '{$myyntilasku['tunnus']}'";
    pupe_query($upd);
  }
}

// 2. Sitten katsotaan onko käteismyyntirivit pupessa oikealla asiakkaalla
// Vaasan ajoa varten lisätty kassalipas- rajaus
$qry = "SELECT *
        FROM lasku
        WHERE yhtio  = '{$yhtio}'
        AND tila     = 'U'
        AND alatila  = 'X'
        and kassalipas = 48
        AND laskunro >= '{$ekalasku}'      #'-10000130831'
        AND laskunro <= '{$vikalasku}'     #'-10000130801'
        order by kassalipas, tapvm, maksuehto, tunnus";
$reslasku = pupe_query($qry);

while ($myyntilasku = mysql_fetch_assoc($reslasku)) {

  $uxtunnus = $myyntilasku['tunnus'];

  $query = "SELECT *
            FROM lasku
            WHERE yhtio           = '{$yhtio}'
            AND tila              = 'L'
            AND alatila           = 'X'
            and kassalipas        = 48
            AND laskunro          = '{$myyntilasku['laskunro']}'
            AND kassalipas        = '{$myyntilasku['kassalipas']}'
            AND maksuehto         = '{$myyntilasku['maksuehto']}'
            AND yhtio_toimipaikka = '{$myyntilasku['yhtio_toimipaikka']}'
            AND viitetxt          = '{$myyntilasku['viitetxt']}'";
  $restilaus = pupe_query($query);

  $myyntitilaus = mysql_fetch_assoc($restilaus);
  //echo "150: $myyntilasku[laskunro], $myyntilasku[tunnus], $myyntilasku[viitetxt] \n";

  $fquery = "SELECT *
             FROM futur_myyntper
             WHERE MYP_MYYNTINRO = '{$myyntilasku['viitetxt']}'";
  $flasku = pupe_query($fquery);

  if (mysql_num_rows($flasku) != 0) {
    $futurlasku = mysql_fetch_assoc($flasku);
    //$uxtunnus = 0;
    //$lxtunnus = 0;

    $asiakasnro   = $futurlasku['MYP_ASIAKASNRO'];
    if (($asiakasnro < 0 or $asiakasnro == 9999 or $asiakasnro == 70014) and $futurlasku['MYP_LASKUNRO'] == 0)
    //$asiakasnro = 70500;
      $asiakasnro = 60600;            // Vaasan käteisasiakasnro
    $query = "SELECT *
              FROM asiakas
              WHERE yhtio    = '{$yhtio}'
              AND asiakasnro = '{$asiakasnro}'
              LIMIT 1";
    $asresult = pupe_query($query);

    if (mysql_num_rows($asresult) == 0) {
      //$asiakasnro = 70500;
      $asiakasnro = 60600;            // Vaasan käteisasiakasnro
      $query = "SELECT *
                FROM asiakas
                WHERE yhtio    = '{$yhtio}'
                AND asiakasnro = '{$asiakasnro}'
                LIMIT 1";
      $asresult = pupe_query($query);
      $asiakasrow = mysql_fetch_assoc($asresult);
    }
    else {
      $asiakasrow = mysql_fetch_assoc($asresult);
    }

    // echo "$asiakasnro, $asiakasrow[tunnus], $myyntilasku[liitostunnus], $uxtunnus, $myyntitilaus[tunnus] \n";

    if ($asiakasrow['tunnus'] != $myyntilasku['liitostunnus'] or $myyntilasku['arvo'] == 0) {
      // Jos käteismyynti oikealle asiakkaalle, korjataan liitostunnus ux:lle ja lx:lle
      $upd = "UPDATE lasku
              SET liitostunnus = '{$asiakasrow['tunnus']}',
              arvo             = '{$myyntitilaus['arvo']}',
              arvo_valuutassa  = '{$myyntitilaus['arvo']}',
              summa            = '{$myyntitilaus['summa']}',
              summa_valuutassa = '{$myyntitilaus['summa']}',
              muutospvm        = now(),
              muuttaja         = 'korjausV'
              WHERE yhtio      = '{$yhtio}'
              AND tunnus       = '{$myyntilasku['tunnus']}'";
      pupe_query($upd);
    }

    if ($asiakasrow['tunnus'] != $myyntilasku['liitostunnus']) {
      $upd = "UPDATE lasku
              SET liitostunnus = '{$asiakasrow['tunnus']}',
              muutospvm        = now(),
              muuttaja         = 'korjausV'
              WHERE yhtio = '{$yhtio}'
              AND tunnus  = '{$myyntitilaus['tunnus']}'";
      pupe_query($upd);
    }

    $qry = "SELECT *
            FROM tilausrivi
            WHERE yhtio = '{$yhtio}'
            AND otunnus = '{$myyntitilaus['tunnus']}'
            ORDER BY hyllyalue, tilaajanrivinro, suuntalava";
    $rivi = pupe_query($qry);

    $count = mysql_num_rows($rivi);

    while ($tilausrivi = mysql_fetch_assoc($rivi)) {

      if ($tilausrivi['uusiotunnus'] != $myyntilasku['tunnus'] and $tilausrivi['tilaajanrivinro'] == $myyntilasku['viitetxt']) {
        $query = "UPDATE tilausrivi
                  SET uusiotunnus = '{$myyntilasku['tunnus']}'
                  WHERE yhtio = '{$yhtio}'
                  AND tunnus  = '{$tilausrivi['tunnus']}'";
        pupe_query($query);
      }
    }
  }
}

// 3. Jos rivejä väärin mennyt ux:n/lx:n  alle, niin haetaan niille omat otsikot
$laskunro = 0;

$qry2 = "SELECT *
         FROM tilausrivi
         WHERE yhtio         = '{$yhtio}'
         AND tyyppi          = 'L'
         AND tilaajanrivinro >= '371597'
         AND tilaajanrivinro <= '380937'
         AND suuntalava      > 0
         AND laatija         = 'konversio'
         AND laskutettuaika  >= '{$alkupvm}'       #'2013-08-01'
         AND laskutettuaika  <= '{$loppupvm}'      #'2013-08-31'
         ORDER BY hyllyalue, tilaajanrivinro, suuntalava";
$rivi2 = pupe_query($qry2);

while ($tilausrivi2 = mysql_fetch_assoc($rivi2)) {
  if ($laskunro == 0 or $tilaajanrivinro  != $tilausrivi2['tilaajanrivinro']) {
    $uxtunnus = 0;
    $lxtunnus = 0;

    $qry1 = "SELECT tunnus, laskunro
             FROM lasku
             WHERE yhtio  = '{$yhtio}'
             AND tila     = 'U'
             AND alatila  = 'X'
             AND kassalipas = 48
             AND viitetxt = '{$tilausrivi2['tilaajanrivinro']}'";
    $reslasku = pupe_query($qry1);

    $count_ux = mysql_num_rows($reslasku);
    if (mysql_num_rows($reslasku) > 0 ) {
      $myyntilasku = mysql_fetch_assoc($reslasku);
      $uxtunnus = $myyntilasku['tunnus'];
    }
    else {
      $uxtunnus = 0;
    }
    $qry2 = "SELECT tunnus, laskunro
             FROM lasku
             WHERE yhtio  = '{$yhtio}'
             AND tila     = 'L'
             AND alatila  = 'X'
             AND kassalipas = 48
             AND viitetxt = '{$tilausrivi2['tilaajanrivinro']}'";
    $restilaus = pupe_query($qry2);
    $count_lx = mysql_num_rows($restilaus);

    if (mysql_num_rows($restilaus) > 0 ) {
      $myyntitilaus = mysql_fetch_assoc($restilaus);
      $lxtunnus = $myyntitilaus['tunnus'];
    }
    else {
      $lxtunnus = 0;
    }
  }

  if (($count_ux == 0 or $count_lx == 0) and $tilaajanrivinro!= $tilausrivi2['tilaajanrivinro']) {
    $tilausrivitunnus = $tilausrivi2['tunnus'];
    $tilaajanrivinro  = $tilausrivi2['tilaajanrivinro'];
    echo "Rivit289: $count_ux, $count_lx, $tilausrivi2[otunnus], $tilausrivi2[tilaajanrivinro], $laskunro \n";

    $fquery = "SELECT *
               FROM futur_myyntper
               WHERE MYP_MYYNTINRO = '{$tilausrivi2['tilaajanrivinro']}'
               AND MYP_LASKUNRO    = 0";
    $flasku = pupe_query($fquery);

    if (mysql_num_rows($flasku) != 0) {
      $futurlasku = mysql_fetch_assoc($flasku);

      $luontiaika   = $futurlasku['MYP_PVM'];
      $toimipaikka  = '';
      $nimi = $futurlasku['MYP_LASKUTUSOSOITE'];
      if ($nimi == '') $nimi = $futurlasku['MYP_TOIMITUSOSOITE'];
      $laskunro = $futurlasku['MYP_LASKUNRO'];
      //if ($laskunro == 0)
      $laskunro = ((str_replace('-', '', $futurlasku['MYP_PVM'])) - 20000000) + 10000000000;
      $laskunro = -1 * $laskunro;

      $asiakasnro   = $futurlasku['MYP_ASIAKASNRO'];

      if (($asiakasnro < 0 or $asiakasnro == 9999 or $asiakasnro == 70014) and $futurlasku['MYP_LASKUNRO'] == 0) {
        //$asiakasnro = 70500;
        $asiakasnro = 60600;            // Vaasan käteisasiakasnro
      }

      $query = "SELECT *
                FROM asiakas
                WHERE yhtio    = '{$yhtio}'
                AND asiakasnro = '{$asiakasnro}'
                LIMIT 1";
      $asresult = pupe_query($query);

      if (mysql_num_rows($asresult) == 0) {
        //$asiakasnro = 70500;
        $asiakasnro = 60600;            // Vaasan käteisasiakasnro
        $query = "SELECT *
                  FROM asiakas
                  WHERE yhtio    = '{$yhtio}'
                  AND asiakasnro = '{$asiakasnro}'
                  LIMIT 1";
        $asresult = pupe_query($query);
        $asiakasrow = mysql_fetch_assoc($asresult);
      }
      else {
        $asiakasrow = mysql_fetch_assoc($asresult);
      }

      $liitostunnus     = $asiakasrow['tunnus'];
      $laskutusnimi     = ($asiakasrow['laskutus_nimi'] == '') ? $asiakasrow['nimi'] : $asiakasrow['laskutus_nimi'];
      $laskutusnimitark = ($asiakasrow['laskutus_nimitark'] == '') ? $asiakasrow['nimitark'] : $asiakasrow['laskutus_nimitark'];
      $laskutusosoite   = ($asiakasrow['laskutus_osoite'] == '') ? $asiakasrow['osoite'] : $asiakasrow['laskutus_osoite'];
      $laskutuspostino  = ($asiakasrow['laskutus_postino'] == '') ? $asiakasrow['postino'] : $asiakasrow['laskutus_postino'];
      $laskutuspostitp  = ($asiakasrow['laskutus_postitp'] == '') ? $asiakasrow['postitp'] : $asiakasrow['laskutus_postitp'];
      $laskutusmaa      = ($asiakasrow['laskutus_maa'] == '') ? $asiakasrow['maa'] : $asiakasrow['laskutus_maa'];

      $toimnimi         = ($asiakasrow['toim_nimi'] == '') ? $asiakasrow['nimi'] : $asiakasrow['toim_nimi'];
      $toimnimitark     = ($asiakasrow['toim_nimitark'] == '') ? $asiakasrow['nimitark'] : $asiakasrow['toim_nimitark'];
      $toimosoite       = ($asiakasrow['toim_osoite'] == '') ? $asiakasrow['osoite'] : $asiakasrow['toim_osoite'];
      $toimovttunnus    = ($asiakasrow['toim_ovttunnus'] == '') ? $asiakasrow['ovttunnus'] : $asiakasrow['toim_ovttunnus'];
      $toimpostino      = ($asiakasrow['toim_postino'] == '') ? $asiakasrow['postino'] : $asiakasrow['toim_postino'];
      $toimpostitp      = ($asiakasrow['toim_postitp'] == '') ? $asiakasrow['postitp'] : $asiakasrow['toim_postitp'];
      $toimmaa          = ($asiakasrow['toim_maa'] == '') ? $asiakasrow['maa'] : $asiakasrow['toim_maa'];

      $kaalepava = ($futurlasku['MYP_KASSAALEPV'] = 0) ? '' : $futurlasku['MYP_KASSAALEPV'];

      $tapvm = $futurlasku['MYP_PVM'];      // oli $myyntilasku['MYP_AIKALEIMA'];

      $tallpvm = $futurlasku['MYP_PVM'];   // oli $myyntilasku['MYP_AIKALEIMA'];
      echo "355: $futurlasku[MYP_TOIMIPISTENRO], $futurlasku[MYP_LASKUNRO], $futurlasku[MYP_MAKSUTAPAKOODI], $futurlasku[MYP_LASKUNRO], $tilausrivi2[tilaajanrivinro] \n";

      $maksuehto = konvertoi_maksuehto($futurlasku['MYP_MAKSUTAPAKOODI'], $yhtio);
      $toimipaikka = konvertoi_toimipaikka($futurlasku['MYP_TOIMIPISTENRO'], $yhtio);
      if ($toimipaikka == '' or $toimipaikka == 0) $toimipaikka = '1';

      //  echo "\n 201: $kassalipas, $asiakasnro, $toimipaikka, $myyntilasku[MYP_TOIMIPISTENRO], $myyntilasku[MYP_MYYNTINRO] \n";

      // Maksuehto ja kassalipas juttu käteismyyntejä varten
      if ($futurlasku['MYP_LASKUNRO'] == 0) {
        if ($futurlasku['MYP_KATEISMYYNTI'] !=0 or $maksuehto == 1517) {
          $maksuehto = 1551;
        }
        else {
          $maksuehto = 1572;
        }
      }

      $kustannuspaikka = konvertoi_kustannuspaikka($futurlasku['MYP_TOIMIPISTENRO'], $yhtio);

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
        $kassalipas = 99;    // Jos puuta heinää
      }

      //$hyllyalue = hae_hyllyalue($futurlasku['MYP_TOIMIPISTENRO'], $yhtio);
      //if ($hyllyalue == '') $hyllyalue = 'A00';
      $tallpvm = $futurlasku['MYP_AIKALEIMA'];
      $toimituspvm = $futurlasku['MYP_AIKALEIMA'];
      $viitteemme = $futurlasku['MYP_VIITTEEMME'];

      $toimosoitearray = array();
      $laskosoitearray = array();

      if ($futurlasku['MYP_TOIMITUSOSOITE'] != '') {
        $toimosoitearray = explode("\n", $myyntilasku['MYP_TOIMITUSOSOITE']);
        $toimnimi        = pupesoft_cleanstring($toimosoitearray[0]);
        $toimosoite      = pupesoft_cleanstring($toimosoitearray[1]);
        $toimpostino     = substr($toimosoitearray[2], 0, 5);
        $toimpostitp     = pupesoft_cleanstring(substr($toimosoitearray[2], 6));
      }
      if ($futurlasku['MYP_LASKUTUSOSOITE'] != '') {
        $laskosoitearray = explode("\n", $myyntilasku['MYP_LASKUTUSOSOITE']);
        $laskutusnimi    = pupesoft_cleanstring($laskosoitearray[0]);
        $laskutusosoite  = pupesoft_cleanstring($laskosoitearray[1]);
        $laskutuspostino = substr($laskosoitearray[2], 0, 5);
        $laskutuspostitp = pupesoft_cleanstring(substr($laskosoitearray[2], 6));
      }

      if ($count_lx == 0) {
        $query1 = "INSERT INTO lasku SET
                   aktiivinen_kuljetus              = '',
                   aktiivinen_kuljetus_kansallisuus = '',
                   alatila                          = 'X',
                   alv                              = '{$tilausrivi2['alv']}',
                   alv_tili                         = '',
                   arvo                             = '{$futurlasku['MYP_VEROTON_YHT']}',
                   arvo_valuutassa                  = '{$futurlasku['MYP_VEROTON_YHT']}',
                   asiakkaan_tilausnumero           = '',
                   bruttopaino                      = '',
                   chn                              = '$asiakasrow[chn]',
                   clearing                         = '',
                   comments                         = '$viitteemme',
                   ebid                             = '',
                   eilahetetta                      = '',
                   erikoisale                       = '',
                   erpcm                            = '{$futurlasku['MYP_ERAPVM']}',
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
                   kapvm                            = '{$futurlasku['MYP_KASSAALEPV']}',
                   kassalipas                       = '$kassalipas',
                   kasumma                          = '',
                   kasumma_valuutassa               = '{$futurlasku['MYP_KASSAALEPROS']}',
                   kate                             = '{$futurlasku['MYP_KATETTA']}',
                   kauppatapahtuman_luonne          = '',
                   kerayslista                      = '',
                   kerayspvm                        = '{$futurlasku['MYP_PVM']}',
                   keraysvko                        = '',
                   ketjutus                         = '$ketjutus',
                   kirjoitin                        = '',
                   kohde                            = '',
                   kohdistettu                      = '',
                   kontti                           = '',
                   kuljetus                         = '',
                   kuljetusmuoto                    = '',
                   laatija                          = '$laatija',
                   lahetepvm                        = '{$futurlasku['MYP_PVM']}',
                   lapvm                            = '{$futurlasku['MYP_PVM']}',
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
                   muutospvm                        = now(),
                   muuttaja                         = 'korjausV',
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
                   pyoristys                        = '{$futurlasku['MYP_PYORISTYS']}',
                   pyoristys_valuutassa             = '{$futurlasku['MYP_PYORISTYS']}',
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
                   summa                            = '{$futurlasku['MYP_YHTEENSA']}',
                   summa_valuutassa                 = '{$futurlasku['MYP_YHTEENSA']}',
                   suoraveloitus                    = '',
                   swift                            = '',
                   tapvm                            = '{$futurlasku['MYP_PVM']}',
                   tila                             = 'L',
                   tilaustyyppi                     = '',
                   tilausvahvistus                  = '',
                   tilausyhteyshenkilo              = '',
                   tilinumero                       = '',
                   toimaika                         = '{$futurlasku['MYP_PVM']}',
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
                   viesti                           = '{$futurlasku['MYP_VIITTEENNE']}',
                   viikorkoeur                      = '',
                   viikorkopros                     = '13',
                   viite                            = '{$futurlasku['MYP_VIITE']}',
                   viitetxt                         = '{$futurlasku['MYP_MYYNTINRO']}',
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
                   muuttaja                                 = 'korjausV',
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

      }

      if ($count_ux == 0) {
        $query1 = "INSERT INTO lasku SET
                   aktiivinen_kuljetus              = '',
                   aktiivinen_kuljetus_kansallisuus = '',
                   alatila                          = 'X',
                   alv                              = '{$tilausrivi2['alv']}',
                   alv_tili                         = '',
                   arvo                             = '{$futurlasku['MYP_VEROTON_YHT']}',
                   arvo_valuutassa                  = '{$futurlasku['MYP_VEROTON_YHT']}',
                   asiakkaan_tilausnumero           = '',
                   bruttopaino                      = '',
                   chn                              = '$asiakasrow[chn]',
                   clearing                         = '',
                   comments                         = '$viitteemme',
                   ebid                             = '',
                   eilahetetta                      = '',
                   erikoisale                       = '',
                   erpcm                            = '{$futurlasku['MYP_ERAPVM']}',
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
                   kapvm                            = '{$futurlasku['MYP_KASSAALEPV']}',
                   kassalipas                       = '$kassalipas',
                   kasumma                          = '',
                   kasumma_valuutassa               = '{$futurlasku['MYP_KASSAALEPROS']}',
                   kate                             = '{$futurlasku['MYP_KATETTA']}',
                   kauppatapahtuman_luonne          = '',
                   kerayslista                      = '',
                   kerayspvm                        = '{$futurlasku['MYP_PVM']}',
                   keraysvko                        = '',
                   ketjutus                         = '$ketjutus',
                   kirjoitin                        = '',
                   kohde                            = '',
                   kohdistettu                      = '',
                   kontti                           = '',
                   kuljetus                         = '',
                   kuljetusmuoto                    = '',
                   laatija                          = '$laatija',
                   lahetepvm                        = '{$futurlasku['MYP_PVM']}',
                   lapvm                            = '{$futurlasku['MYP_PVM']}',
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
                   mapvm                            = '$tapvm',
                   muutospvm                        = now(),
                   muuttaja                         = 'korjausV',
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
                   pyoristys                        = '{$futurlasku['MYP_PYORISTYS']}',
                   pyoristys_valuutassa             = '{$futurlasku['MYP_PYORISTYS']}',
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
                   summa                            = '{$futurlasku['MYP_YHTEENSA']}',
                   summa_valuutassa                 = '{$futurlasku['MYP_YHTEENSA']}',
                   suoraveloitus                    = '',
                   swift                            = '',
                   tapvm                            = '{$futurlasku['MYP_PVM']}',
                   tila                             = 'U',
                   tilaustyyppi                     = '',
                   tilausvahvistus                  = '',
                   tilausyhteyshenkilo              = '',
                   tilinumero                       = '',
                   toimaika                         = '{$futurlasku['MYP_PVM']}',
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
                   viesti                           = '{$futurlasku['MYP_VIITTEENNE']}',
                   viikorkoeur                      = '',
                   viikorkopros                     = '13',
                   viite                            = '{$futurlasku['MYP_VIITE']}',
                   viitetxt                         = '{$futurlasku['MYP_MYYNTINRO']}',
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
        $uxtunnus = mysql_insert_id();

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
                   muuttaja                                 = 'korjausV',
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
    }
  }

  if ($lxtunnus > 0 and $uxtunnus > 0 and ($tilausrivi2['otunnus'] != $lxtunnus or $tilausrivi2['uusiotunnus'] != $uxtunnus)) {
    echo "Rivit908: $tilausrivi2[otunnus], $lxtunnus, $tilausrivi2[uusiotunnus], $uxtunnus, $tilausrivitunnus, $tilausrivi2[tunnus] \n";

    $query = "UPDATE tilausrivi
              SET otunnus = '$lxtunnus',
              uusiotunnus = '$uxtunnus'
              WHERE yhtio = '{$yhtio}'
              AND tunnus  = '{$tilausrivi2['tunnus']}'";
    pupe_query($query);
  }
}

// 4. Jos uusiotunnus puuttuu päivitetään myös se oikeille laskuille
$uxtunnus = 0;
$qry2 = "SELECT *
         FROM tilausrivi
         WHERE yhtio         = '{$yhtio}'
         AND tyyppi          = 'L'
         AND tilaajanrivinro >= '371597'
         AND tilaajanrivinro <= '380937'
         AND suuntalava      > 0
         AND laatija         = 'konversio'
         AND uusiotunnus     = 0
         AND laskutettuaika  >= '{$alkupvm}'       #'2013-08-01'
         AND laskutettuaika  <= '{$loppupvm}'      #'2013-08-31'
         ORDER BY hyllyalue, tilaajanrivinro, suuntalava";
$rivi2 = pupe_query($qry2);

while ($tilausrivi2 = mysql_fetch_assoc($rivi2)) {
  if ($uxtunnus == 0 or $tilaajanrivinro  != $tilausrivi2['tilaajanrivinro']) {
    $uxtunnus = 0;
    $tilaajanrivinro= $tilausrivi2['tilaajanrivinro'];

    $qry1 = "SELECT tunnus, laskunro, kassalipas
             FROM lasku
             WHERE yhtio  = '{$yhtio}'
             AND tila     = 'L'
             AND alatila  = 'X'
             AND viitetxt = '{$tilausrivi2['tilaajanrivinro']}'";
    $restilaus = pupe_query($qry1);

    if (mysql_num_rows($restilaus) > 0 ) {
      $myyntitilaus = mysql_fetch_assoc($restilaus);
      $lx_kassalipas = $myyntitilaus['kassalipas'];
      $qry1 = "SELECT tunnus, laskunro, kassalipas
               FROM lasku
               WHERE yhtio  = '{$yhtio}'
               AND tila     = 'U'
               AND alatila  = 'X'
               AND laskunro = '{$myyntitilaus['laskunro']}'";
      $reslasku = pupe_query($qry1);

      if (mysql_num_rows($reslasku) > 0 ) {
        $myyntilasku = mysql_fetch_assoc($reslasku);
        $uxtunnus = $myyntilasku['tunnus'];
        $ux_kassalipas = $myyntilasku['kassalipas'];
      }
      else {
        $uxtunnus = 0;
      }
    }
    else {
      $uxtunnus = 0;
    }
  }
  if ($uxtunnus != 0) {
    echo "$uxtunnus, $tilausrivi2[otunnus], $tilausrivi2[uusiotunnus], $tilausrivi2[tunnus], $lx_kassalipas, $ux_kassalipas \n";
    $query = "UPDATE tilausrivi
              SET uusiotunnus   = '{$uxtunnus}'
              WHERE   yhtio = '{$yhtio}'
              AND    tunnus = '{$tilausrivi2['tunnus']}'";
    pupe_query($query);
  }
}

function konvertoi_maksuehto($maksuehto, $yhtio) {
  $maksuehto = (string) $maksuehto;

  if ($yhtio == 'artr') {
    //selitteellä ei tehdä mitään, debuggausta varten
    $maksuehto_array = array(
      '1'     => array('id'   => '1551', 'selite' => 'Käteinen'),
      '2'     => array('id'   => '1551', 'selite' => 'Käteinen'),
      '3'     => array('id'   => '1551', 'selite' => 'Käteinen'),
      '4'     => array('id'   => '1535', 'selite' => 'Lasku 7pv'),
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
  $toimipaikka = (string) $toimipaikka;

  if ($yhtio == 'artr') {
    //selitteellä ei tehdä mitään, debuggausta varten
    $toimipaikka_array = array(
      '8'    => array('id'   => '20', 'nimi' => 'AUTOASI FORSSA'),
      '33'   => array('id'   => '14', 'nimi' => 'AUTOASI VAASA'),
      '24'   => array('id'   => '15', 'nimi' => 'AUTOASI TAMPERE'),
      '28'   => array('id'   => '16', 'nimi' => 'AUTOASI LAPPEENRANTA'),
      '29'   => array('id'   => '17', 'nimi' => 'AUTOASI KOTKA'),
      '34'   => array('id'   => '18', 'nimi' => 'AUTOASI KOUVOLA'),
      '4'    => array('id'   => '19', 'nimi' => 'AUTOASI HÄMEENLINNA'),
      '19'   => array('id'   => '21', 'nimi' => 'AUTOASI MUURALA'),
      '9'    => array('id'   => '22', 'nimi' => 'AUTOASI LAUNE'),
      '22'   => array('id'   => '23', 'nimi' => 'AUTOASI NUMMELA'),
      '11'   => array('id'   => '24', 'nimi' => 'AUTOASI HYVINKÄÄ'),
      '13'   => array('id'   => '25', 'nimi' => 'AUTOASI MÄNTSÄLÄ'),
      '35'   => array('id'   => '26', 'nimi' => 'AUTOASI MIKKELI'),
      '5'    => array('id'   => '27', 'nimi' => 'AUTOASI PKS TUKKUMYYNTI'),
      '17'   => array('id'   => '28', 'nimi' => 'AUTOASI ROVANIEMI'),
      '00'   => array('id'   => '29', 'nimi' => 'Hallinto'),
      '26'   => array('id'   => '30', 'nimi' => 'AUTOASI TURKU'),
      '21'   => array('id'   => '31', 'nimi' => 'AUTOASI IISALMI'),
      '1'    => array('id'   => '32', 'nimi' => 'AUTOASI LAHTI'),
      '6'    => array('id'   => '33', 'nimi' => 'AUTOASI OLARI'),
      '42'   => array('id'   => '34', 'nimi' => 'AUTOASI PORVOO'),
      '16'   => array('id'   => '35', 'nimi' => 'AUTOASI OULU'),
      '36'   => array('id'   => '36', 'nimi' => 'AUTOASI JYVÄSKYLÄ Vasarakatu'),
      '37'   => array('id'   => '37', 'nimi' => 'AUTOASI JYVÄSKYLÄ Sepänkatu'),
      '38'   => array('id'   => '38', 'nimi' => 'AUTOASI KUOPIO'),
      '26'   => array('id'   => '30', 'nimi' => 'AUTOASI TURKU/SALO'),
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
  $kustannuspaikka = (string) $kustannuspaikka;

  if ($yhtio == 'artr') {
    //selitteellä ei tehdä mitään, debuggausta varten
    $kustannuspaikka_array = array(
      '8'   => array('id'   => '788', 'nimi' => 'AUTOASI FORSSA, 2080'),
      '33'   => array('id'   => '783', 'nimi' => 'AUTOASI VAASA, 1000'),
      '24'   => array('id'   => '797', 'nimi' => 'AUTOASI TAMPERE, 2240'),
      '28'   => array('id'   => '805', 'nimi' => 'AUTOASI LAPPEENRANTA, 4028'),
      '29'   => array('id'   => '806', 'nimi' => 'AUTOASI KOTKA, 4029'),
      '34'   => array('id'   => '807', 'nimi' => 'AUTOASI KOUVOLA, 4040'),
      '4'   => array('id'   => '785', 'nimi' => 'AUTOASI HÄMEENLINNA, 2040'),
      '19'   => array('id'   => '794', 'nimi' => 'AUTOASI MUURALA, 2190'),
      '9'   => array('id'   => '789', 'nimi' => 'AUTOASI LAUNE, 2090'),
      '22'   => array('id'   => '786', 'nimi' => 'AUTOASI NUMMELA, 2220'),
      '11'   => array('id'   => '790', 'nimi' => 'AUTOASI HYVINKÄÄ, 2110'),
      '13'   => array('id'   => '791', 'nimi' => 'AUTOASI MÄNTSÄLÄ, 2130'),
      '35'   => array('id'   => '799', 'nimi' => 'AUTOASI MIKKELI, 2270'),
      '5'   => array('id'   => '786', 'nimi' => 'AUTOASI PKS TUKKUMYYNTI, 2050'),
      '17'   => array('id'   => '793', 'nimi' => 'AUTOASI ROVANIEMI, 2170'),
      //'00'   => array('id'   => '489', 'nimi' => 'Hallinto, 2000, Lahti'),
      '26'   => array('id'   => '798', 'nimi' => 'AUTOASI TURKU, 2260'),
      '21'   => array('id'   => '795', 'nimi' => 'AUTOASI IISALMI, 2210'),
      '1'   => array('id'   => '784', 'nimi' => 'AUTOASI LAHTI, 2010'),
      '6'   => array('id'   => '787', 'nimi' => 'AUTOASI OLARI, 2060'),
      '42'   => array('id'   => '801', 'nimi' => 'AUTOASI PORVOO, 2310'),
      '16'   => array('id'   => '792', 'nimi' => 'AUTOASI OULU, 2160'),
      '36'   => array('id'   => '800', 'nimi' => 'AUTOASI JYVÄSKYLÄ Vasarakatu, 2280'),
      '37'   => array('id'   => '800', 'nimi' => 'AUTOASI JYVÄSKYLÄ Sepänkatu, 2290'),
      '38'   => array('id'   => '802', 'nimi' => 'AUTOASI KUOPIO, 2380'),
      '39'   => array('id'   => '803', 'nimi' => 'AUTOASI SEINÄJOKI, 2410'),
      '46'   => array('id'   => '804', 'nimi' => 'AUTOASI PORI, 2420'),
    );

    if (array_key_exists($kustannuspaikka, $kustannuspaikka_array)) {
      return (int) $kustannuspaikka_array[$kustannuspaikka]['id'];
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
  $hyllyalue = (string) $hyllyalue;

  if ($yhtio == 'artr') {
    //selitteellä ei tehdä mitään, debuggausta varten
    $hyllyalue_array = array(
      '8'  => array('hyllyalue' => '74', 'nimi' => 'AUTOASI FORSSA'),
      '33' => array('hyllyalue' => '92', 'nimi' => 'AUTOASI VAASA'),
      '24' => array('hyllyalue' => '83', 'nimi' => 'AUTOASI TAMPERE'),
      '28' => array('hyllyalue' => '88', 'nimi' => 'AUTOASI LAPPEENRANTA'),
      '29' => array('hyllyalue' => '89', 'nimi' => 'AUTOASI KOTKA'),
      '34' => array('hyllyalue' => '90', 'nimi' => 'AUTOASI KOUVOLA'),
      '4'  => array('hyllyalue' => '71', 'nimi' => 'AUTOASI HÄMEENLINNA'),
      '19' => array('hyllyalue' => '80', 'nimi' => 'AUTOASI MUURALA'),
      '9'  => array('hyllyalue' => '75', 'nimi' => 'AUTOASI LAUNE'),
      '22' => array('hyllyalue' => '82', 'nimi' => 'AUTOASI NUMMELA'),
      '11' => array('hyllyalue' => '76', 'nimi' => 'AUTOASI HYVINKÄÄ'),
      '13' => array('hyllyalue' => '77', 'nimi' => 'AUTOASI MÄNTSÄLÄ'),
      '35' => array('hyllyalue' => '85', 'nimi' => 'AUTOASI MIKKELI'),
      '5'  => array('hyllyalue' => '72', 'nimi' => 'AUTOASI PKS TUKKUMYYNTI'),
      '17' => array('hyllyalue' => '79', 'nimi' => 'AUTOASI ROVANIEMI'),
      '00' => array('hyllyalue' => '00', 'nimi' => 'Hallinto'),
      '26' => array('hyllyalue' => '84', 'nimi' => 'AUTOASI TURKU'),
      '21' => array('hyllyalue' => '81', 'nimi' => 'AUTOASI IISALMI'),
      '1'  => array('hyllyalue' => '70', 'nimi' => 'AUTOASI LAHTI'),
      '6'  => array('hyllyalue' => '73', 'nimi' => 'AUTOASI OLARI'),
      '42' => array('hyllyalue' => '87', 'nimi' => 'AUTOASI PORVOO'),
      '16' => array('hyllyalue' => '78', 'nimi' => 'AUTOASI OULU'),
      '36' => array('hyllyalue' => '86', 'nimi' => 'AUTOASI JYVÄSKYLÄ Vasarakatu'),
      '37' => array('hyllyalue' => '86', 'nimi' => 'AUTOASI JYVÄSKYLÄ Sepänkatu'),
      '38' => array('hyllyalue' => '91', 'nimi' => 'AUTOASI KUOPIO'),
      '39' => array('hyllyalue' => '93', 'nimi' => 'AUTOASI SEINÄJOKI'),
      '46' => array('hyllyalue' => '94', 'nimi' => 'AUTOASI PORI'),
    );

    if (array_key_exists($hyllyalue, $hyllyalue_array)) {
      return (int) $hyllyalue_array[$hyllyalue]['hyllyalue'];
    }
    else {
      echo "\n Hyllyaluetta ".$hyllyalue." ei löytynyt.\n";
      return (int) $hyllyalue;
    }
  }
  else {
    return (int) $hyllyalue;
  }
}
